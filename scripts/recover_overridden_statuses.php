<?php
/**
 * Recover Local Statuses of Pending Orders Script
 *
 * Reverts order lines that were overridden back to 'pending' by sync_order_statuses
 * to their pre-sync local workflow status (admin_id = 0 / picklist / PO statuses).
 *
 * CLI Usage:
 *   php scripts/recover_overridden_statuses.php             (dry-run preview mode)
 *   php scripts/recover_overridden_statuses.php --execute   (apply database changes)
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/config.php';

if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found at {$configPath}\n");
    exit(1);
}

$config = require $configPath;
$dbCfg = $config['db'] ?? null;

if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "Invalid db config.\n");
    exit(1);
}

mysqli_report(MYSQLI_REPORT_OFF);

// Establish database connection
$conn = @new mysqli((string)$dbCfg['host'], (string)$dbCfg['user'], (string)$dbCfg['pass'], (string)$dbCfg['name'], (int)($dbCfg['port'] ?? 3306));
if ($conn->connect_error) {
    $conn = @new mysqli('127.0.0.1', 'root', '', (string)$dbCfg['name'], (int)($dbCfg['port'] ?? 3306));
}
if ($conn->connect_error) {
    fwrite(STDERR, "Database connection failed: " . $conn->connect_error . "\n");
    exit(1);
}

if (!empty($dbCfg['charset'])) {
    $conn->set_charset((string)$dbCfg['charset']);
}

// Parse CLI flags
$execute = false;
if ($isCli && isset($argv)) {
    foreach ($argv as $arg) {
        if ($arg === '--execute' || $arg === '-e') {
            $execute = true;
        }
    }
}

if (!$isCli && (($_GET['execute'] ?? '') === '1' || ($_GET['execute'] ?? '') === 'true')) {
    $execute = true;
}

$dryRun = !$execute;

require_once $projectRoot . '/models/order/order.php';
$ordersModel = new Order($conn);

echo "========================================================\n";
echo "Exotic India - Order Status Recovery Script\n";
echo "========================================================\n";
echo "Mode      : " . ($dryRun ? "DRY-RUN (Preview changes only)" : "EXECUTE (Live database updates)") . "\n";
echo "Timestamp : " . date('Y-m-d H:i:s') . "\n";
echo "--------------------------------------------------------\n\n";

$result = $ordersModel->recoverOverriddenOrderStatuses($dryRun, 0);

echo "Candidate Lines Found : " . $result['checked_candidates'] . "\n";
echo "Lines Restored        : " . $result['restored_lines'] . "\n\n";

if (!empty($result['details'])) {
    echo "--- RECOVERY DETAILS ---\n";
    foreach ($result['details'] as $item) {
        echo sprintf(
            "Line ID: %-6d | Order #: %-10s | Item: %-10s | Reverted 'pending' => %-20s | %s\n",
            $item['line_id'],
            $item['order_number'],
            $item['item_code'],
            $item['new_status'],
            $item['reason']
        );
    }
    echo "\n";
} else {
    echo "No pending order lines found needing local status recovery.\n\n";
}

if ($dryRun && $result['checked_candidates'] > 0) {
    echo "To apply these recovery changes, run:\n";
    echo "  php scripts/recover_overridden_statuses.php --execute\n\n";
}

echo "Done.\n";
