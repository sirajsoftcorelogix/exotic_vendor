<?php
/**
 * Repair legacy POS order payable totals (vp_order_info.total + pos_payments snapshots)
 * from invoice notes net payable (grand_total / subtotal − discounts).
 *
 * CLI (from project root):
 *   php scripts/repair_pos_order_payable_totals.php --order=3026759
 *   php scripts/repair_pos_order_payable_totals.php --order=3026759 --execute
 *   php scripts/repair_pos_order_payable_totals.php --order=3026759,3025912 --execute
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: application/json; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function repair_script_fail(string $msg, int $code = 1): void
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
    repair_script_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    repair_script_fail("config.php must define ['db'] with host, name, user, pass.");
}

$argv = $_SERVER['argv'] ?? [];
$execute = false;
$orderNumbers = [];

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
    }
} else {
    $execute = isset($_GET['execute']) && $_GET['execute'] !== '' && $_GET['execute'] !== '0';
    foreach (explode(',', (string)($_GET['order'] ?? '')) as $part) {
        $part = trim($part);
        if ($part !== '') {
            $orderNumbers[] = $part;
        }
    }
}

if ($orderNumbers === []) {
    repair_script_fail('Provide at least one order: --order=3026759');
}

require_once $root . '/helpers/pos_invoice_order_total_repair.php';

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
    repair_script_fail('Database connection failed: ' . $e->getMessage());
}

$results = [];
foreach ($orderNumbers as $orderNumber) {
    $results[] = pos_invoice_repair_order_payable_totals($conn, $orderNumber, [
        'dry_run' => !$execute,
        'patch_notes' => true,
    ]);
}

$output = [
    'execute' => $execute,
    'count' => count($results),
    'results' => $results,
];

$json = json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    repair_script_fail('Failed to encode repair results.');
}

echo $json . PHP_EOL;
