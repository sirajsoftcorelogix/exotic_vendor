<?php
/**
 * Update Order Statuses Script
 *
 * Fetches non-terminal orders (status NOT IN 'Cancelled', 'Returned', 'Shipped') ordered by order_date ASC,
 * queries the Exotic India Vendor API (/vendor-api/order/fetch) using `only_status=1` in batches,
 * updates local order statuses, logs changes to `vp_order_status_log`, and triggers stock restore if needed.
 *
 * CLI Usage:
 *   php scripts/sync_order_statuses.php
 *   php scripts/sync_order_statuses.php --execute
 *   php scripts/sync_order_statuses.php --execute --limit=1000 --batch-size=50
 *   php scripts/sync_order_statuses.php --order=3114463,3114147 --execute
 *
 * Web Usage:
 *   http://seller.exoticindia.com/scripts/sync_order_statuses.php?key=SECRET&execute=1
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';

if (!$isCli) {
    header('Content-Type: text/plain; charset=utf-8');
    header('X-Robots-Tag: noindex, nofollow');
}

$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function script_fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        http_response_code($code >= 400 && $code < 600 ? $code : 500);
        echo $msg . PHP_EOL;
    }
    exit(1);
}

if (!is_file($configPath)) {
    script_fail('Missing config.php at ' . $configPath);
}

/** @var array<string, mixed> $config */
$config = require $configPath;

$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    script_fail("config.php must define ['db'] with host, name, user, pass.");
}

// Web Authentication
if (!$isCli) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $webKey = (string) ($config['backfill_logs_web_key'] ?? '');
    $givenKey = (string) ($_GET['key'] ?? '');
    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);

    if (!$isLoggedIn && ($webKey === '' || !hash_equals($webKey, $givenKey))) {
        http_response_code(403);
        echo "Web access denied. Log in to portal or provide ?key=...\n";
        exit(0);
    }
}

// Connect Database
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $conn = new mysqli(
        (string) $dbCfg['host'],
        (string) $dbCfg['user'],
        (string) $dbCfg['pass'],
        (string) $dbCfg['name'],
        (int) ($dbCfg['port'] ?? 3306)
    );
    if (!empty($dbCfg['charset'])) {
        $conn->set_charset((string) $dbCfg['charset']);
    }
} catch (Throwable $e) {
    script_fail('Database connection failed: ' . $e->getMessage());
}

$GLOBALS['conn'] = $conn;
require_once $root . '/models/order/order.php';
$ordersModel = new Order($conn);

// Parse options
$dryRun = true;
$limit = 500;
$batchSize = 50;
$specificOrders = [];

if ($isCli) {
    foreach ($_SERVER['argv'] ?? [] as $arg) {
        if ($arg === '--execute') {
            $dryRun = false;
        } elseif ($arg === '--dry-run') {
            $dryRun = true;
        } elseif (strpos($arg, '--limit=') === 0) {
            $limit = max(1, (int) substr($arg, 8));
        } elseif (strpos($arg, '--batch-size=') === 0) {
            $batchSize = max(1, min(200, (int) substr($arg, 13)));
        } elseif (strpos($arg, '--order=') === 0) {
            $raw = substr($arg, 8);
            $specificOrders = array_filter(array_map('trim', explode(',', $raw)));
        }
    }
} else {
    if (isset($_GET['execute']) && ($_GET['execute'] === '1' || $_GET['execute'] === 'true')) {
        $dryRun = false;
    }
    if (!empty($_GET['limit'])) {
        $limit = max(1, (int) $_GET['limit']);
    }
    if (!empty($_GET['batch_size'])) {
        $batchSize = max(1, min(200, (int) $_GET['batch_size']));
    }
    if (!empty($_GET['order'])) {
        $specificOrders = array_filter(array_map('trim', explode(',', (string) $_GET['order'])));
    }
}

echo "========================================================\n";
echo "Exotic India Order Status Sync Script\n";
echo "========================================================\n";
echo "Mode       : " . ($dryRun ? "DRY-RUN (no database changes)" : "EXECUTE (live database updates)") . "\n";
echo "Limit      : {$limit} orders max\n";
echo "Batch Size : {$batchSize} orders per API request\n";
echo "Timestamp  : " . date('Y-m-d H:i:s') . "\n";
echo "--------------------------------------------------------\n\n";

$targetOrderNumbers = [];

if (!empty($specificOrders)) {
    $targetOrderNumbers = $specificOrders;
    echo "Syncing " . count($targetOrderNumbers) . " specific order(s): " . implode(', ', $targetOrderNumbers) . "\n\n";
} else {
    echo "Fetching pending candidate orders (status NOT IN Cancelled, Returned, Shipped) ordered by order_date ASC...\n";
    $candidates = $ordersModel->getNonTerminalOrdersForStatusSync($limit);
    if (empty($candidates)) {
        echo "No non-terminal orders found awaiting status sync.\n";
        exit(0);
    }
    foreach ($candidates as $cand) {
        $targetOrderNumbers[] = $cand['order_number'];
    }
    echo "Found " . count($candidates) . " candidate order(s). Oldest order date: " . ($candidates[0]['order_date'] ?? 'N/A') . "\n\n";
}

$chunks = array_chunk($targetOrderNumbers, $batchSize);
$totalChecked = 0;
$totalUpdated = 0;
$totalUnchanged = 0;
$totalSkipped = 0;
$allDetails = [];
$allErrors = [];

$startTime = microtime(true);
$chunkCount = count($chunks);

foreach ($chunks as $idx => $chunk) {
    $chunkNum = $idx + 1;
    echo "Processing Batch {$chunkNum}/{$chunkCount} (" . count($chunk) . " order IDs)... ";
    
    $res = $ordersModel->syncOrderStatusFromVendorApiBatch($chunk, $dryRun, 0);
    
    $totalChecked += $res['checked_orders'];
    $totalUpdated += $res['updated_lines'];
    $totalUnchanged += $res['unchanged_lines'];
    $totalSkipped += $res['skipped_lines'];

    if (!empty($res['details'])) {
        $allDetails = array_merge($allDetails, $res['details']);
    }
    if (!empty($res['errors'])) {
        $allErrors = array_merge($allErrors, $res['errors']);
    }

    echo "Done. (Updated/Changed lines: {$res['updated_lines']}, Unchanged: {$res['unchanged_lines']})\n";
}

$elapsed = round(microtime(true) - $startTime, 2);

echo "\n--------------------------------------------------------\n";
echo "Sync Summary:\n";
echo "--------------------------------------------------------\n";
echo "Total Orders Checked   : {$totalChecked}\n";
echo "Total Lines Changed    : {$totalUpdated}\n";
echo "Total Lines Unchanged  : {$totalUnchanged}\n";
echo "Total Lines Skipped    : {$totalSkipped}\n";
echo "Elapsed Time           : {$elapsed}s\n";

if (!empty($allDetails)) {
    echo "\nDetailed Status Changes (" . count($allDetails) . "):\n";
    foreach ($allDetails as $d) {
        $stockNote = !empty($d['stock_result']['message']) ? " [Stock: {$d['stock_result']['message']}]" : "";
        echo "  - Order #{$d['order_number']} (Line #{$d['line_id']}, SKU: {$d['sku']}, Item: {$d['item_code']}): '{$d['old_status']}' -> '{$d['new_status']}'{$stockNote}\n";
    }
}

if (!empty($allErrors)) {
    echo "\nErrors Encounted (" . count($allErrors) . "):\n";
    foreach ($allErrors as $err) {
        echo "  ! {$err}\n";
    }
}

echo "\nCompleted at " . date('Y-m-d H:i:s') . "\n";
