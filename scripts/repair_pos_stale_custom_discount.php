<?php
/**
 * Remove stale POS custom_reduce from a completed order and realign invoice/payment totals.
 *
 * Example (order 3039692 — stale ₹175 from prior terminal cart):
 *   php scripts/repair_pos_stale_custom_discount.php --order=3039692 --amount=175
 *   php scripts/repair_pos_stale_custom_discount.php --order=3039692 --amount=175 --execute
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function stale_discount_script_fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo json_encode(['success' => false, 'message' => $msg], JSON_UNESCAPED_UNICODE) ?: $msg;
    }
    exit(1);
}

if (!is_file($configPath)) {
    stale_discount_script_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    stale_discount_script_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = false;
$orderNumbers = [];
$expectedAmount = null;

if ($isCli) {
    $execute = in_array('--execute', $argv, true);
    foreach ($argv as $arg) {
        if (preg_match('/^--order=(.+)$/', $arg, $m)) {
            foreach (explode(',', (string)$m[1]) as $part) {
                $part = trim($part);
                if ($part !== '') {
                    $orderNumbers[] = $part;
                }
            }
        }
        if (preg_match('/^--amount=([\d.]+)$/', $arg, $m)) {
            $expectedAmount = (float)$m[1];
        }
    }
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
    foreach (explode(',', (string)($_GET['order'] ?? '')) as $part) {
        $part = trim($part);
        if ($part !== '') {
            $orderNumbers[] = $part;
        }
    }
    if (isset($_GET['amount']) && $_GET['amount'] !== '') {
        $expectedAmount = (float)$_GET['amount'];
    }
}

if ($orderNumbers === []) {
    stale_discount_script_fail('Provide at least one order: --order=3039692 [--amount=175] [--execute]');
}

require_once $root . '/helpers/pos_stale_custom_discount_repair.php';

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        (string)$dbCfg['host'],
        (string)$dbCfg['user'],
        (string)$dbCfg['pass'],
        (string)$dbCfg['name'],
        (int)($dbCfg['port'] ?? 3306)
    );
    if (!empty($dbCfg['charset'])) {
        $conn->set_charset((string)$dbCfg['charset']);
    }
} catch (Throwable $e) {
    stale_discount_script_fail('Database connection failed: ' . $e->getMessage());
}

$results = [];
foreach ($orderNumbers as $orderNumber) {
    $opts = ['dry_run' => !$execute];
    if ($expectedAmount !== null) {
        $opts['amount'] = $expectedAmount;
    }
    $results[] = pos_stale_custom_discount_repair_order($conn, $orderNumber, $opts);
}

$output = [
    'execute' => $execute,
    'count' => count($results),
    'results' => $results,
];

$json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    stale_discount_script_fail('Failed to encode repair results.');
}

echo $json . PHP_EOL;
