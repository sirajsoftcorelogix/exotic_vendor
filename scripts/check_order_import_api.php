<?php
/**
 * One request to the order-import API (same shape as OrdersController::importOrders). No DB writes.
 *
 * CLI (project root):
 *   php scripts/check_order_import_api.php
 *   php scripts/check_order_import_api.php --orderid=1234567
 *   php scripts/check_order_import_api.php --connect-timeout=15 --max-time=120
 *   php scripts/check_order_import_api.php --ipv4   (force IPv4; try if default fails on your host)
 *
 * HTTP (lock down in Nginx): ?secret_key=...&orderid=...&ipv4=1
 */
declare(strict_types=1);

$cli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$cli) {
    header('Content-Type: text/plain; charset=utf-8');
}

const ORDER_IMPORT_PROBE_SECRET = 'b2d1127032446b78ce2b8911b72f6b155636f6898af2cf5d3aafdccf46778801';
const ORDER_FETCH_URL = 'https://www.exoticindia.com/vendor-api/order/fetch';

function order_import_headers(): array
{
    return [
        'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
        'x-adminapitest: 1',
        'Content-Type: application/x-www-form-urlencoded',
    ];
}

$orderid = null;
$from = null;
$to = null;
$connectTimeout = 15;
$maxTime = 120;
$forceIpv4 = false;

if ($cli) {
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if ($arg === '--ipv4') {
            $forceIpv4 = true;
        } elseif (preg_match('/^--orderid=(.+)$/', $arg, $m)) {
            $orderid = trim($m[1]);
        } elseif (preg_match('/^--from=(\d+)$/', $arg, $m)) {
            $from = (int) $m[1];
        } elseif (preg_match('/^--to=(\d+)$/', $arg, $m)) {
            $to = (int) $m[1];
        } elseif (preg_match('/^--connect-timeout=(\d+)$/', $arg, $m)) {
            $connectTimeout = max(1, (int) $m[1]);
        } elseif (preg_match('/^--max-time=(\d+)$/', $arg, $m)) {
            $maxTime = max(1, (int) $m[1]);
        }
    }
} else {
    if (!hash_equals(ORDER_IMPORT_PROBE_SECRET, (string) ($_GET['secret_key'] ?? ''))) {
        http_response_code(403);
        echo "Forbidden\n";
        exit(1);
    }
    $orderid = isset($_GET['orderid']) ? trim((string) $_GET['orderid']) : null;
    $from = isset($_GET['from']) ? (int) $_GET['from'] : null;
    $to = isset($_GET['to']) ? (int) $_GET['to'] : null;
    $connectTimeout = isset($_GET['connect_timeout']) ? max(1, (int) $_GET['connect_timeout']) : 15;
    $maxTime = isset($_GET['max_time']) ? max(1, (int) $_GET['max_time']) : 120;
    $forceIpv4 = isset($_GET['ipv4']) && $_GET['ipv4'] !== '0' && $_GET['ipv4'] !== '';
}

$post = ['makeRequestOf' => 'vendors-orderjson'];
if ($orderid !== null && $orderid !== '') {
    $post['orderid'] = $orderid;
} else {
    $post['from_date'] = $from ?? strtotime('-1 day');
    $post['to_date'] = $to ?? time();
}

$ch = curl_init(ORDER_FETCH_URL);
$curlOpts = [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query($post),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => order_import_headers(),
    CURLOPT_CONNECTTIMEOUT => $connectTimeout,
    CURLOPT_TIMEOUT => $maxTime,
];
if ($forceIpv4 && defined('CURL_IPRESOLVE_V4')) {
    $curlOpts[CURLOPT_IPRESOLVE] = CURL_IPRESOLVE_V4;
}
curl_setopt_array($ch, $curlOpts);

$t0 = microtime(true);
$body = curl_exec($ch);
$t1 = microtime(true);

$errno = curl_errno($ch);
$errstr = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

$http = (int) ($info['http_code'] ?? 0);
echo "URL: " . ORDER_FETCH_URL . "\n";
if ($forceIpv4) {
    echo "ip_resolve: v4\n";
}
echo "POST: " . http_build_query($post) . "\n";
echo sprintf(
    "time: total=%.3fs wall=%.3fs | namelookup=%.3f connect=%.3f starttransfer=%.3f\n",
    (float) ($info['total_time'] ?? 0),
    $t1 - $t0,
    (float) ($info['namelookup_time'] ?? 0),
    (float) ($info['connect_time'] ?? 0),
    (float) ($info['starttransfer_time'] ?? 0)
);
if (!empty($info['primary_ip'])) {
    echo 'primary_ip: ' . $info['primary_ip'] . "\n";
}
echo "http_code: {$http}\n";
echo 'curl_errno: ' . $errno . ($errstr !== '' ? " ({$errstr})" : '') . "\n";
echo 'response_bytes: ' . ($body === false ? 0 : strlen((string) $body)) . "\n";

if ($body !== false && $body !== '') {
    $j = json_decode((string) $body, true);
    if (is_array($j)) {
        $n = isset($j['orders']) && is_array($j['orders']) ? count($j['orders']) : '?';
        $tp = $j['total_pages'] ?? '?';
        echo "json: orders={$n} total_pages={$tp}\n";
    } else {
        echo "json: (not object)\n";
    }
}

$fail = $errno !== 0 || ($http !== 0 && ($http < 200 || $http >= 300));
exit($fail ? 2 : 0);
