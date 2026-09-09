<?php

declare(strict_types=1);

/**
 * Compare Exotic item_sale_nos vs local vp_orders quantity for one item_code.
 *
 * CLI: php scripts/compare_item_sale_nos.php
 *      php scripts/compare_item_sale_nos.php --itemcode=NAG842 --from=2026-01-01 --to=2026-09-30
 */

$root = dirname(__DIR__);
chdir($root);

require_once $root . '/integrations/exotic/vendor_api.php';

$itemCode = 'NAG842';
$fromDate = '2026-01-01';
$toDate = '2026-09-30';
foreach ($_SERVER['argv'] ?? [] as $arg) {
    if (preg_match('/^--itemcode=(.+)$/', (string) $arg, $m)) {
        $itemCode = trim($m[1]);
    }
    if (preg_match('/^--from=(\d{4}-\d{2}-\d{2})$/', (string) $arg, $m)) {
        $fromDate = $m[1];
    }
    if (preg_match('/^--to=(\d{4}-\d{2}-\d{2})$/', (string) $arg, $m)) {
        $toDate = $m[1];
    }
}

$postBody = http_build_query([
    'itemcode' => $itemCode,
    'from_date' => $fromDate,
    'to_date' => $toDate,
]);

$api = exotic_india_api_post(
    '/data/item_sale_nos',
    $postBody,
    ['Content-Type: application/x-www-form-urlencoded']
);

echo "=== API item_sale_nos ===\n";
echo "itemcode={$itemCode} from={$fromDate} to={$toDate} (Y-m-d; unix timestamps are rejected by this API)\n";
echo "http={$api['http_code']} success=" . ($api['success'] ? 'yes' : 'no') . "\n";
if (!$api['success']) {
    echo "message=" . ($api['message'] ?? '') . "\n";
}

$data = $api['data'] ?? [];
$rawPreview = substr((string) ($api['raw'] ?? ''), 0, 2000);
echo "top_keys=" . implode(',', is_array($data) ? array_keys($data) : []) . "\n";

$apiQty = 0;
if (isset($data['total_sold']) && is_numeric($data['total_sold'])) {
    $apiQty = (int) round((float) $data['total_sold']);
}

$orders = [];
if (isset($data['orders']) && is_array($data['orders'])) {
    $orders = $data['orders'];
} elseif (isset($data['data']) && is_array($data['data'])) {
    $orders = $data['data'];
} elseif (array_is_list($data) && $data !== []) {
    $orders = $data;
}

$apiRows = 0;
$sample = [];
if ($apiQty === 0 && $orders !== []) {
    foreach ($orders as $row) {
        if (!is_array($row)) {
            continue;
        }
        $apiRows++;
        $qty = 0;
        foreach (['quantity', 'qty', 'order_qty', 'nos', 'numsold', 'sale_nos', 'total_sold'] as $k) {
            if (isset($row[$k]) && is_numeric($row[$k])) {
                $qty = (int) round((float) $row[$k]);
                break;
            }
        }
        $apiQty += $qty;
        if (count($sample) < 3) {
            $sample[] = array_slice($row, 0, 12, true);
        }
    }
}

echo "api_total_sold={$apiQty} api_order_rows={$apiRows}\n";
echo "api_body=" . json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";
if ($sample !== []) {
    echo "api_sample=" . json_encode($sample, JSON_UNESCAPED_UNICODE) . "\n";
}
if ($data === [] && $rawPreview !== '') {
    echo "api_raw_preview=" . $rawPreview . "\n";
}

$config = require $root . '/config.php';
$db = $config['db'] ?? [];
try {
    $conn = new mysqli(
        (string) ($db['host'] ?? '127.0.0.1'),
        (string) ($db['user'] ?? ''),
        (string) ($db['pass'] ?? ''),
        (string) ($db['name'] ?? ''),
        (int) ($db['port'] ?? 3306)
    );
} catch (Throwable $e) {
    fwrite(STDERR, 'DB connect failed: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
if ($conn->connect_error) {
    fwrite(STDERR, 'DB connect failed: ' . $conn->connect_error . PHP_EOL);
    exit(1);
}
$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));

echo "\n=== Local vp_orders ===\n";

$sql = "SELECT COALESCE(SUM(quantity), 0) AS qty, COUNT(*) AS rows
        FROM vp_orders
        WHERE item_code = ?
          AND order_date BETWEEN ? AND ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('sss', $itemCode, $fromDate, $toDate);
$stmt->execute();
$all = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sql2 = "SELECT COALESCE(SUM(quantity), 0) AS qty, COUNT(*) AS rows
         FROM vp_orders
         WHERE item_code = ?
           AND order_date BETWEEN ? AND ?
           AND status NOT IN ('cancelled', 'returned')";
$stmt = $conn->prepare($sql2);
$stmt->bind_param('sss', $itemCode, $fromDate, $toDate);
$stmt->execute();
$active = $stmt->get_result()->fetch_assoc();
$stmt->close();

$sql3 = "SELECT status, COALESCE(SUM(quantity), 0) AS qty, COUNT(*) AS rows
         FROM vp_orders
         WHERE item_code = ?
           AND order_date BETWEEN ? AND ?
         GROUP BY status
         ORDER BY qty DESC";
$stmt = $conn->prepare($sql3);
$stmt->bind_param('sss', $itemCode, $fromDate, $toDate);
$stmt->execute();
$byStatus = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$sql4 = "SELECT TRIM(IFNULL(size,'')) AS size, TRIM(IFNULL(color,'')) AS color,
                COALESCE(SUM(quantity), 0) AS qty, COUNT(*) AS rows
         FROM vp_orders
         WHERE item_code = ?
           AND order_date BETWEEN ? AND ?
           AND status NOT IN ('cancelled', 'returned')
         GROUP BY TRIM(IFNULL(size,'')), TRIM(IFNULL(color,''))
         ORDER BY qty DESC";
$stmt = $conn->prepare($sql4);
$stmt->bind_param('sss', $itemCode, $fromDate, $toDate);
$stmt->execute();
$byVariant = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$localAll = (int) ($all['qty'] ?? 0);
$localActive = (int) ($active['qty'] ?? 0);
echo "all_statuses qty={$localAll} rows=" . (int) ($all['rows'] ?? 0) . "\n";
echo "excluding cancelled/returned qty={$localActive} rows=" . (int) ($active['rows'] ?? 0) . "\n";
echo "by_status=" . json_encode($byStatus, JSON_UNESCAPED_UNICODE) . "\n";
echo "by_variant=" . json_encode($byVariant, JSON_UNESCAPED_UNICODE) . "\n";

echo "\n=== Compare ===\n";
echo "api_qty={$apiQty} local_all={$localAll} local_active={$localActive}\n";
echo "api_minus_local_all=" . ($apiQty - $localAll) . "\n";
echo "api_minus_local_active=" . ($apiQty - $localActive) . "\n";
