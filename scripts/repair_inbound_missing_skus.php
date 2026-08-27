<?php
/**
 * CLI Script: Backfill/Repair missing SKUs in vp_inbound and vp_variations
 *
 * Usage:
 *   php scripts/repair_inbound_missing_skus.php
 */

$root = dirname(__DIR__);
require_once $root . DIRECTORY_SEPARATOR . 'helpers' . DIRECTORY_SEPARATOR . 'html_helpers.php';

$config = require $root . DIRECTORY_SEPARATOR . 'config.php';
$dbCfg = $config['db'] ?? null;

if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    die("Error: Invalid DB configuration in config.php\n");
}

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
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "=== Backfilling missing SKUs in vp_inbound ===\n";

// 1. Repair vp_inbound main rows
$res = $conn->query("SELECT id, Item_code, size, color FROM vp_inbound WHERE (sku IS NULL OR TRIM(sku) = '') AND Item_code IS NOT NULL AND TRIM(Item_code) != ''");
$inboundRows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];

$inboundUpdated = 0;
$stmtInb = $conn->prepare("UPDATE vp_inbound SET sku = ? WHERE id = ?");

foreach ($inboundRows as $row) {
    $id = (int) $row['id'];
    $itemCode = trim((string) $row['Item_code']);
    $size = (string) ($row['size'] ?? '');
    $color = (string) ($row['color'] ?? '');

    $generatedSku = generateItemSku($itemCode, $size, $color);
    if ($generatedSku !== '') {
        $stmtInb->bind_param('si', $generatedSku, $id);
        $stmtInb->execute();
        $inboundUpdated++;
        echo "Updated vp_inbound #{$id}: Item_code={$itemCode} -> SKU={$generatedSku}\n";
    }
}
$stmtInb->close();

echo "Repaired {$inboundUpdated} rows in vp_inbound.\n\n";

echo "=== Backfilling missing SKUs in vp_variations ===\n";

// 2. Repair vp_variations rows
$sqlVar = "SELECT v.id, v.it_id, v.color, v.size, i.Item_code 
           FROM vp_variations v 
           JOIN vp_inbound i ON i.id = v.it_id 
           WHERE (v.sku IS NULL OR TRIM(v.sku) = '') AND i.Item_code IS NOT NULL AND TRIM(i.Item_code) != ''";
$resVar = $conn->query($sqlVar);
$varRows = $resVar ? $resVar->fetch_all(MYSQLI_ASSOC) : [];

$varUpdated = 0;
$stmtVar = $conn->prepare("UPDATE vp_variations SET sku = ? WHERE id = ?");

foreach ($varRows as $row) {
    $varId = (int) $row['id'];
    $itemCode = trim((string) $row['Item_code']);
    $size = (string) ($row['size'] ?? '');
    $color = (string) ($row['color'] ?? '');

    $generatedSku = generateItemSku($itemCode, $size, $color);
    if ($generatedSku !== '') {
        $stmtVar->bind_param('si', $generatedSku, $varId);
        $stmtVar->execute();
        $varUpdated++;
        echo "Updated vp_variations #{$varId} (Inbound #{$row['it_id']}): Item_code={$itemCode} -> SKU={$generatedSku}\n";
    }
}
$stmtVar->close();

echo "Repaired {$varUpdated} rows in vp_variations.\n";
echo "=== Done ===\n";
