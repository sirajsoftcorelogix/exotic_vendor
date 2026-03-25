<?php
/**
 * Probe order-import vendor API (same request shape as OrdersController::importOrders).
 * Does not write to the database — use for connectivity / latency / HTTP diagnosis.
 *
 * CLI (from project root):
 *   php scripts/check_order_import_api.php
 *   php scripts/check_order_import_api.php --orderid=1234567
 *   php scripts/check_order_import_api.php --from=1700000000 --to=1700086400
 *   php scripts/check_order_import_api.php --connect-timeout=10 --max-time=120
 *   php scripts/check_order_import_api.php --repeat=3
 *   php scripts/check_order_import_api.php --verbose
 *
 * Optional HTTP (restrict with Nginx deny or set ORDER_IMPORT_PROBE_KEY in PHP env):
 *   .../scripts/check_order_import_api.php?key=YOUR_KEY&orderid=1234567&repeat=2
 *   YOUR_KEY must match EXPECTED_SECRET_KEY in bootstrap init (same as order import URLs).
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

/** Must match folders/bootstrap/init/init.php EXPECTED_SECRET_KEY (order import). */
const ORDER_IMPORT_PROBE_SECRET = 'b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801';

const ORDER_FETCH_URL = 'https://www.exoticindia.com/vendor-api/order/fetch';

/** Same headers as OrdersController::importOrders (keep in sync). */
function order_import_api_headers(): array
{
    return [
        'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        'x-adminapitest: 1',
        'Content-Type: application/x-www-form-urlencoded',
    ];
}

function fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . "\n");
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo $msg . "\n";
    }
    exit($code);
}

function curl_errno_hint(int $errno): string
{
    if ($errno === 0) {
        return '';
    }
    if ($errno === 7) {
        return ' | hint: failed to connect (firewall, routing, remote down, or wrong host/port)';
    }
    if ($errno === 28) {
        return ' | hint: operation timed out (slow peer or connect_timeout/max_time too low)';
    }
    if ($errno === 6) {
        return ' | hint: could not resolve host (DNS from this server)';
    }
    return '';
}

function dns_summary_for_url(string $url): string
{
    $host = parse_url($url, PHP_URL_HOST);
    if (!is_string($host) || $host === '') {
        return 'dns: (no host in URL)';
    }
    $ips = @gethostbynamel($host);
    if ($ips === false || $ips === []) {
        return 'dns gethostbynamel(' . $host . '): failed or empty';
    }
    return 'dns gethostbynamel(' . $host . '): ' . implode(', ', $ips);
}

/** @return array{errNo:int,httpCode:int,total:float,lines:string[]} */
function run_order_import_probe(array $postData, array $args): array
{
    $ch = curl_init(ORDER_FETCH_URL);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postData),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => order_import_api_headers(),
        CURLOPT_CONNECTTIMEOUT => $args['connect_timeout'],
        CURLOPT_TIMEOUT => $args['max_time'],
    ]);

    $t0 = microtime(true);
    $body = curl_exec($ch);
    $t1 = microtime(true);

    $errNo = curl_errno($ch);
    $errStr = curl_error($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    $nameLookup = (float) ($info['namelookup_time'] ?? 0);
    $connect = (float) ($info['connect_time'] ?? 0);
    $appConnect = (float) ($info['appconnect_time'] ?? 0);
    $preTransfer = (float) ($info['pretransfer_time'] ?? 0);
    $startTransfer = (float) ($info['starttransfer_time'] ?? 0);
    $total = (float) ($info['total_time'] ?? 0);
    $httpCode = (int) ($info['http_code'] ?? 0);
    $primaryIp = isset($info['primary_ip']) ? (string) $info['primary_ip'] : '';
    $localIp = isset($info['local_ip']) ? (string) $info['local_ip'] : '';
    $effectiveUrl = isset($info['url']) ? (string) $info['url'] : '';

    $lines = [];
    $lines[] = 'URL: ' . ORDER_FETCH_URL;
    $lines[] = 'POST: ' . http_build_query($postData);
    $lines[] = 'PHP wall time (approx): ' . round($t1 - $t0, 4) . ' s';
    $lines[] = 'cURL total_time: ' . round($total, 4) . ' s';
    $lines[] = sprintf(
        'timings: namelookup=%.4f connect=%.4f appconnect=%.4f pretransfer=%.4f starttransfer=%.4f',
        $nameLookup,
        $connect,
        $appConnect,
        $preTransfer,
        $startTransfer
    );
    if ($primaryIp !== '') {
        $lines[] = 'primary_ip (server you connected to): ' . $primaryIp;
    }
    if ($localIp !== '') {
        $lines[] = 'local_ip (outgoing socket): ' . $localIp;
    }
    if ($effectiveUrl !== '') {
        $lines[] = 'effective_url: ' . $effectiveUrl;
    }
    $lines[] = 'http_code: ' . $httpCode;
    $lines[] = 'curl_errno: ' . $errNo . ($errStr !== '' ? (' (' . $errStr . ')') : '') . curl_errno_hint($errNo);
    $lines[] = 'response_bytes: ' . ($body === false ? '0' : (string) strlen((string) $body));

    if ($body !== false && $body !== '') {
        $decoded = json_decode((string) $body, true);
        if (is_array($decoded)) {
            $count = isset($decoded['orders']) && is_array($decoded['orders']) ? count($decoded['orders']) : null;
            $lines[] = 'json: ok'
                . ($count !== null ? (' | orders count (page): ' . $count) : '')
                . (isset($decoded['total_pages']) ? (' | total_pages: ' . $decoded['total_pages']) : '');
        } else {
            $lines[] = 'json: not an object/array (preview below)';
        }
        if ($args['verbose']) {
            $preview = substr((string) $body, 0, 4000);
            $lines[] = '--- body preview ---';
            $lines[] = $preview . (strlen((string) $body) > 4000 ? "\n... [truncated]" : '');
        }
    } elseif ($body === false) {
        $lines[] = 'body: (curl_exec returned false)';
    }

    return [
        'errNo' => $errNo,
        'httpCode' => $httpCode,
        'total' => $total,
        'lines' => $lines,
    ];
}

function parse_cli_args(): array
{
    $o = [
        'orderid' => null,
        'from' => null,
        'to' => null,
        'connect_timeout' => 15,
        'max_time' => 120,
        'verbose' => false,
        'repeat' => 1,
    ];
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if ($arg === '--verbose' || $arg === '-v') {
            $o['verbose'] = true;
            continue;
        }
        if (preg_match('/^--orderid=(.+)$/', $arg, $m)) {
            $o['orderid'] = trim($m[1]);
            continue;
        }
        if (preg_match('/^--from=(\d+)$/', $arg, $m)) {
            $o['from'] = (int) $m[1];
            continue;
        }
        if (preg_match('/^--to=(\d+)$/', $arg, $m)) {
            $o['to'] = (int) $m[1];
            continue;
        }
        if (preg_match('/^--connect-timeout=(\d+)$/', $arg, $m)) {
            $o['connect_timeout'] = max(1, (int) $m[1]);
            continue;
        }
        if (preg_match('/^--max-time=(\d+)$/', $arg, $m)) {
            $o['max_time'] = max(1, (int) $m[1]);
            continue;
        }
        if (preg_match('/^--repeat=(\d+)$/', $arg, $m)) {
            $o['repeat'] = max(1, min(20, (int) $m[1]));
            continue;
        }
    }
    return $o;
}

if (!$isCli) {
    $envKey = getenv('ORDER_IMPORT_PROBE_KEY');
    if ($envKey !== false && $envKey !== '') {
        $got = (string) ($_GET['key'] ?? '');
        if (!hash_equals((string) $envKey, $got)) {
            fail('Forbidden', 403);
        }
    } else {
        $got = (string) ($_GET['secret_key'] ?? $_GET['key'] ?? '');
        if (!hash_equals(ORDER_IMPORT_PROBE_SECRET, $got)) {
            fail('Forbidden: pass secret_key= (same as import) or set ORDER_IMPORT_PROBE_KEY in env and pass key=', 403);
        }
    }
    $args = [
        'orderid' => isset($_GET['orderid']) ? trim((string) $_GET['orderid']) : null,
        'from' => isset($_GET['from']) ? (int) $_GET['from'] : null,
        'to' => isset($_GET['to']) ? (int) $_GET['to'] : null,
        'connect_timeout' => isset($_GET['connect_timeout']) ? max(1, (int) $_GET['connect_timeout']) : 15,
        'max_time' => isset($_GET['max_time']) ? max(1, (int) $_GET['max_time']) : 120,
        'verbose' => isset($_GET['verbose']),
        'repeat' => isset($_GET['repeat']) ? max(1, min(20, (int) $_GET['repeat'])) : 1,
    ];
} else {
    $args = parse_cli_args();
}

$postData = ['makeRequestOf' => 'vendors-orderjson'];
if ($args['orderid'] !== null && $args['orderid'] !== '') {
    $postData['orderid'] = $args['orderid'];
} else {
    $from = $args['from'] ?? null;
    $to = $args['to'] ?? null;
    if ($from === null) {
        $from = strtotime('-1 day');
    }
    if ($to === null) {
        $to = time();
    }
    $postData['from_date'] = $from;
    $postData['to_date'] = $to;
}

$out = [];
$out[] = '=== Order import API probe ===';
$out[] = dns_summary_for_url(ORDER_FETCH_URL);
$out[] = 'php: ' . PHP_VERSION . ' | sapi: ' . PHP_SAPI;
$out[] = 'repeat: ' . $args['repeat'];
$out[] = str_repeat('-', 48);

$lastErrNo = 0;
$lastHttp = 0;
$totals = [];

for ($i = 1; $i <= $args['repeat']; $i++) {
    if ($args['repeat'] > 1) {
        $out[] = '--- run ' . $i . '/' . $args['repeat'] . ' ---';
    }
    $result = run_order_import_probe($postData, $args);
    $lastErrNo = $result['errNo'];
    $lastHttp = $result['httpCode'];
    $totals[] = $result['total'];
    $out = array_merge($out, $result['lines']);
    if ($i < $args['repeat']) {
        $out[] = '';
    }
}

if ($args['repeat'] > 1) {
    $out[] = '';
    $out[] = '=== Summary (' . $args['repeat'] . ' runs) ===';
    $out[] = 'cURL total_time min/max: ' . round(min($totals), 4) . ' / ' . round(max($totals), 4) . ' s';
    $out[] = 'last curl_errno: ' . $lastErrNo . curl_errno_hint($lastErrNo);
    $out[] = 'last http_code: ' . $lastHttp;
}

echo implode("\n", $out) . "\n";

$bad = $lastErrNo !== 0 || ($lastHttp !== 0 && ($lastHttp < 200 || $lastHttp >= 300));
exit($bad ? 2 : 0);
