<?php

declare(strict_types=1);

/**
 * Daily book replenishment (intended for ~1:00 AM).
 *
 * Windows Task Scheduler example:
 *   php.exe D:\xampp\htdocs\exotic_vendor\scripts\run_daily_book_replenishment.php
 *
 * Usage:
 *   php scripts/run_daily_book_replenishment.php
 *   php scripts/run_daily_book_replenishment.php --dry-run
 *   php scripts/run_daily_book_replenishment.php --date=2026-09-08
 *   php scripts/run_daily_book_replenishment.php --limit=50
 */

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
chdir($root);

function daily_replenish_fail(string $msg, int $code = 1): void
{
    fwrite(STDERR, $msg . PHP_EOL);
    exit($code);
}

$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';
if (!is_file($configPath)) {
    daily_replenish_fail('Missing config.php');
}

/** @var array $config */
$config = require $configPath;
$db = $config['db'] ?? [];
if (!is_array($db) || empty($db['host']) || empty($db['name'])) {
    daily_replenish_fail('config.php must define db host and name.');
}

$argvList = $_SERVER['argv'] ?? [];
$dryRun = in_array('--dry-run', $argvList, true);
$limit = 0;
$salesDate = date('Y-m-d', strtotime('-1 day'));
foreach ($argvList as $arg) {
    if (preg_match('/^--limit=(\d+)$/', (string) $arg, $m)) {
        $limit = max(0, (int) $m[1]);
    }
    if (preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/', (string) $arg, $m)) {
        $salesDate = $m[1];
    }
}

try {
    $conn = new mysqli(
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['user'] ?? ''),
        (string) ($db['pass'] ?? ''),
        (string) ($db['name'] ?? ''),
        (int) ($db['port'] ?? 3306)
    );
} catch (Throwable $e) {
    daily_replenish_fail('DB connect failed: ' . $e->getMessage());
}

if ($conn->connect_error) {
    daily_replenish_fail('DB connect failed: ' . $conn->connect_error);
}
$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));

$colRes = $conn->query("SHOW COLUMNS FROM vp_products LIKE 'numsold_replenishment'");
$hasNumsoldCol = $colRes && $colRes->num_rows > 0;
if ($colRes) {
    $colRes->free();
}
if (!$hasNumsoldCol) {
    $sqlFile = $root . '/sql/alter_vp_products_add_numsold_replenishment.sql';
    $sql = trim(preg_replace('/^--.*$/m', '', (string) file_get_contents($sqlFile)));
    if ($sql !== '' && !$conn->query($sql)) {
        daily_replenish_fail('Could not add numsold_replenishment: ' . $conn->error);
    }
}

$reportRes = $conn->query("SHOW TABLES LIKE 'vp_replenishment_buy_report'");
$hasReport = $reportRes && $reportRes->num_rows > 0;
if ($reportRes) {
    $reportRes->free();
}
if (!$hasReport) {
    $createSql = 'CREATE TABLE IF NOT EXISTS vp_replenishment_buy_report (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        run_date DATE NOT NULL,
        product_id INT UNSIGNED NOT NULL,
        sku VARCHAR(120) NOT NULL DEFAULT \'\',
        item_code VARCHAR(120) NOT NULL DEFAULT \'\',
        title VARCHAR(500) NOT NULL DEFAULT \'\',
        yesterday_sold_qty INT UNSIGNED NOT NULL DEFAULT 0,
        numsold_replenishment INT UNSIGNED NOT NULL DEFAULT 0,
        lookback_months INT UNSIGNED NOT NULL DEFAULT 0,
        lookback_source VARCHAR(32) NOT NULL DEFAULT \'\',
        numsold_source VARCHAR(32) NOT NULL DEFAULT \'\',
        physical_stock INT NOT NULL DEFAULT 0,
        pending_po_qty INT NOT NULL DEFAULT 0,
        available_stock INT NOT NULL DEFAULT 0,
        purchase_threshold_percent INT UNSIGNED NOT NULL DEFAULT 0,
        purchase_threshold_qty INT UNSIGNED NOT NULL DEFAULT 0,
        min_stock_percent INT UNSIGNED NOT NULL DEFAULT 0,
        replenishment_buy_qty INT UNSIGNED NOT NULL DEFAULT 0,
        purchased TINYINT(1) NOT NULL DEFAULT 0,
        purchased_at DATETIME NULL DEFAULT NULL,
        purchased_by INT NULL DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_replenish_buy_run_product (run_date, product_id),
        KEY idx_replenish_buy_purchased (purchased, run_date),
        KEY idx_replenish_buy_sku (sku),
        KEY idx_replenish_buy_product (product_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    if (!$conn->query($createSql)) {
        daily_replenish_fail('Could not create vp_replenishment_buy_report: ' . $conn->error);
    }
    echo "Created table vp_replenishment_buy_report\n";
}

require_once $root . '/helpers/DailyBookReplenishment.php';

$job = new DailyBookReplenishment($conn);
$summary = $job->runForDate($salesDate, $dryRun, $limit);

echo ($dryRun ? "[DRY RUN] " : '') . 'run_date=' . $summary['run_date']
    . ' scanned=' . $summary['scanned']
    . ' books=' . $summary['books']
    . ' triggered=' . $summary['triggered']
    . ' written=' . $summary['written']
    . ' skipped=' . $summary['skipped']
    . PHP_EOL;
