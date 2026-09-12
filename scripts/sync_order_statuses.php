<?php
/**
 * Update Order Statuses Script
 *
 * Browser UI (recommended, uses app header + left menu):
 *   /index.php?page=orders&action=sync_order_statuses
 *   /index.php?page=orders&action=sync_order_statuses&limit=250
 *   /scripts/sync_order_statuses.php  (redirects to the app page)
 *
 * CLI:
 *   php scripts/sync_order_statuses.php --execute --limit=1000 --batch-size=50
 *
 * Plain-text web (legacy, no stop/progress UI):
 *   /scripts/sync_order_statuses.php?format=text&execute=1&limit=250
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
$root = dirname(__DIR__);
$configPath = $root . DIRECTORY_SEPARATOR . 'config.php';

function script_fail(string $msg, int $code = 1): void
{
    global $isCli;
    if ($isCli) {
        fwrite(STDERR, $msg . PHP_EOL);
    } else {
        if (!headers_sent()) {
            http_response_code($code >= 400 && $code < 600 ? $code : 500);
            header('Content-Type: text/plain; charset=utf-8');
        }
        echo $msg . PHP_EOL;
    }
    exit(1);
}

function script_json(array $payload, int $status = 200): void
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
        header('Cache-Control: no-store');
    }
    echo json_encode($payload);
    exit($payload['success'] ?? true ? 0 : 1);
}

function script_read_input(): array
{
    $json = json_decode((string) file_get_contents('php://input'), true);
    if (is_array($json)) {
        return $json;
    }
    return array_merge($_GET, $_POST);
}

function script_parse_order_ids($raw): array
{
    if (is_array($raw)) {
        $parts = $raw;
    } else {
        $parts = preg_split('/[\s,]+/', (string) $raw) ?: [];
    }
    $out = [];
    foreach ($parts as $part) {
        $part = trim((string) $part);
        if ($part !== '') {
            $out[] = $part;
        }
    }
    return array_values(array_unique($out));
}

function script_run_text_sync(Order $ordersModel, bool $dryRun, int $limit, int $batchSize, array $specificOrders): void
{
    echo "========================================================\n";
    echo "Exotic India Order Status Sync Script\n";
    echo "========================================================\n";
    echo 'Mode       : ' . ($dryRun ? 'DRY-RUN (no database changes)' : 'EXECUTE (live database updates)') . "\n";
    echo "Limit      : {$limit} orders max\n";
    echo "Batch Size : {$batchSize} orders per API request\n";
    echo 'Timestamp  : ' . date('Y-m-d H:i:s') . "\n";
    echo "--------------------------------------------------------\n\n";

    $targetOrderNumbers = [];
    if (!empty($specificOrders)) {
        $targetOrderNumbers = $specificOrders;
        echo 'Syncing ' . count($targetOrderNumbers) . ' specific order(s): ' . implode(', ', $targetOrderNumbers) . "\n\n";
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
        echo 'Found ' . count($candidates) . ' candidate order(s). Oldest order date: ' . ($candidates[0]['order_date'] ?? 'N/A') . "\n\n";
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
        echo "Processing Batch {$chunkNum}/{$chunkCount} (" . count($chunk) . ' order IDs)... ';
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
            $stockNote = !empty($d['stock_result']['message']) ? " [Stock: {$d['stock_result']['message']}]" : '';
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

$isAjax = !$isCli && (
    (isset($_GET['ajax']) && (string) $_GET['ajax'] === '1')
    || ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
);
$wantText = !$isCli && isset($_GET['format']) && (string) $_GET['format'] === 'text';
$loopbackOpen = !$isCli
    && getenv('SYNC_STATUS_UI_OPEN') === '1'
    && in_array((string) ($_SERVER['REMOTE_ADDR'] ?? ''), ['127.0.0.1', '::1'], true);

if (!$isCli) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    $webKey = (string) ($config['backfill_logs_web_key'] ?? '');
    $givenKey = (string) ($_GET['key'] ?? '');
    $isLoggedIn = !empty($_SESSION['user_id']) || !empty($_SESSION['user']['id']);
    $keyOk = $webKey !== '' && hash_equals($webKey, $givenKey);

    if (!$isLoggedIn && !$keyOk && !$loopbackOpen) {
        if ($isAjax) {
            script_json(['success' => false, 'message' => 'Web access denied. Log in to the portal or provide ?key=...'], 403);
        }
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        echo "Web access denied. Log in to the portal or provide ?key=...\n";
        exit(0);
    }
}

$connectDb = static function () use ($dbCfg) {
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
    return $conn;
};

if ($isCli || $wantText) {
    if (!$isCli) {
        header('Content-Type: text/plain; charset=utf-8');
        header('X-Robots-Tag: noindex, nofollow');
    }
    try {
        $conn = $connectDb();
    } catch (Throwable $e) {
        script_fail('Database connection failed: ' . $e->getMessage());
    }
    $GLOBALS['conn'] = $conn;
    require_once $root . '/models/order/order.php';
    $ordersModel = new Order($conn);

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
                $specificOrders = script_parse_order_ids(substr($arg, 8));
            }
        }
    } else {
        if (isset($_GET['execute']) && in_array((string) $_GET['execute'], ['1', 'true'], true)) {
            $dryRun = false;
        }
        if (!empty($_GET['limit'])) {
            $limit = max(1, (int) $_GET['limit']);
        }
        if (!empty($_GET['batch_size'])) {
            $batchSize = max(1, min(200, (int) $_GET['batch_size']));
        }
        if (!empty($_GET['order'])) {
            $specificOrders = script_parse_order_ids($_GET['order']);
        }
    }

    script_run_text_sync($ordersModel, $dryRun, $limit, $batchSize, $specificOrders);
    exit(0);
}

$demoUi = $loopbackOpen && getenv('SYNC_STATUS_UI_DEMO') === '1';

if ($isAjax && $demoUi) {
    $input = script_read_input();
    $op = trim((string) ($input['op'] ?? 'count'));
    if ($op === 'count') {
        script_json(['success' => true, 'total' => 180, 'demo' => true]);
    }
    if ($op === 'candidates') {
        $mode = trim((string) ($input['mode'] ?? 'partial'));
        $specific = script_parse_order_ids($input['order_id'] ?? $input['order_numbers'] ?? []);
        $limit = isset($input['limit']) ? max(1, min(100000, (int) $input['limit'])) : 250;
        if ($mode === 'specific' || $specific !== []) {
            $orderNumbers = $specific !== [] ? $specific : ['1001', '1002'];
        } else {
            $count = $mode === 'all' ? 180 : $limit;
            $orderNumbers = [];
            for ($i = 1; $i <= $count; $i++) {
                $orderNumbers[] = (string) (3000000 + $i);
            }
        }
        script_json([
            'success' => true,
            'mode' => $mode,
            'total' => count($orderNumbers),
            'oldest_order_date' => date('Y-m-d H:i:s', strtotime('-30 days')),
            'pending_total' => 180,
            'order_numbers' => $orderNumbers,
            'demo' => true,
        ]);
    }
    if ($op === 'sync_batch') {
        $orderNumbers = script_parse_order_ids($input['order_numbers'] ?? []);
        usleep(400000);
        $details = [];
        foreach (array_slice($orderNumbers, 0, 2) as $orderId) {
            $details[] = [
                'line_id' => (int) $orderId,
                'order_number' => $orderId,
                'item_code' => 'DEMO',
                'sku' => 'DEMO-SKU',
                'old_status' => 'pending',
                'new_status' => 'processed',
                'updated' => false,
            ];
        }
        script_json([
            'success' => true,
            'dry_run' => true,
            'demo' => true,
            'summary' => [
                'checked_orders' => count($orderNumbers),
                'updated_lines' => count($details),
                'unchanged_lines' => max(0, count($orderNumbers) - count($details)),
                'skipped_lines' => 0,
                'details' => $details,
                'errors' => [],
            ],
        ]);
    }
    script_json(['success' => false, 'message' => 'Unknown operation.'], 400);
}

if ($isAjax) {
    try {
        $conn = $connectDb();
    } catch (Throwable $e) {
        script_json(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()], 500);
    }
    $GLOBALS['conn'] = $conn;
    require_once $root . '/models/order/order.php';
    $ordersModel = new Order($conn);
    $input = script_read_input();
    $op = trim((string) ($input['op'] ?? 'count'));
    $userId = (int) ($_SESSION['user']['id'] ?? $_SESSION['user_id'] ?? 0);

    if ($op === 'count') {
        script_json([
            'success' => true,
            'total' => $ordersModel->countNonTerminalOrdersForStatusSync(),
        ]);
    }

    if ($op === 'candidates') {
        $mode = trim((string) ($input['mode'] ?? 'partial'));
        $specific = script_parse_order_ids($input['order_id'] ?? $input['orderid'] ?? $input['order'] ?? $input['order_numbers'] ?? []);
        $limit = isset($input['limit']) ? max(1, min(100000, (int) $input['limit'])) : 250;
        $candidates = [];
        $oldest = null;

        if ($mode === 'specific' || $specific !== []) {
            $orderNumbers = $specific;
        } else {
            $fetchLimit = $mode === 'all' ? 0 : $limit;
            $rows = $ordersModel->getNonTerminalOrdersForStatusSync($fetchLimit);
            $orderNumbers = [];
            foreach ($rows as $row) {
                $orderNumbers[] = (string) $row['order_number'];
            }
            $oldest = $rows[0]['order_date'] ?? null;
        }

        script_json([
            'success' => true,
            'mode' => $mode,
            'total' => count($orderNumbers),
            'oldest_order_date' => $oldest,
            'pending_total' => $ordersModel->countNonTerminalOrdersForStatusSync(),
            'order_numbers' => $orderNumbers,
        ]);
    }

    if ($op === 'sync_batch') {
        $orderNumbers = script_parse_order_ids($input['order_numbers'] ?? $input['order_id'] ?? []);
        $dryRun = !empty($input['dry_run']);
        $res = $ordersModel->syncOrderStatusFromVendorApiBatch($orderNumbers, $dryRun, $userId);
        script_json([
            'success' => true,
            'dry_run' => $dryRun,
            'summary' => $res,
        ]);
    }

    script_json(['success' => false, 'message' => 'Unknown operation.'], 400);
}

// Browser UI now lives in the standard app layout.
$redirectQuery = [
    'page' => 'orders',
    'action' => 'sync_order_statuses',
];
if (isset($_GET['limit']) && (string) $_GET['limit'] !== '') {
    $redirectQuery['limit'] = (int) $_GET['limit'];
}
if (!empty($_GET['order'])) {
    $redirectQuery['order'] = (string) $_GET['order'];
}
if (isset($_GET['execute']) && (string) $_GET['execute'] !== '') {
    $redirectQuery['execute'] = (string) $_GET['execute'];
}

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/scripts/sync_order_statuses.php'));
$appBase = rtrim(dirname(dirname($scriptName)), '/');
$target = ($appBase === '' ? '' : $appBase) . '/index.php?' . http_build_query($redirectQuery);

header('Location: ' . $target, true, 302);
exit;
