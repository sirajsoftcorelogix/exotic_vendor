<?php
declare(strict_types=1);

/**
 * Test order line duplicate checking and variant handling for Order import.
 *
 * CLI: php scripts/test_order_import_variant_duplicates.php
 */

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
if (!$isCli) {
    http_response_code(403);
    exit('CLI only.');
}

$projectRoot = dirname(__DIR__);
$configPath = $projectRoot . '/config.php';
if (!is_file($configPath)) {
    fwrite(STDERR, "config.php not found.\n");
    exit(1);
}

$config = require $configPath;
$dbCfg = $config['db'] ?? null;
if (!is_array($dbCfg) || empty($dbCfg['host']) || empty($dbCfg['name'])) {
    fwrite(STDERR, "Invalid db config.\n");
    exit(1);
}

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

require_once $projectRoot . '/models/order/order.php';

$ordersModel = new Order($conn);

$testOrderNum = 'TEST-VAR-8D7D-9999';
$testItemCode = 'TEST_VAR_ITEM_001';

// Step 1: Cleanup any previous test data
$stmt = $conn->prepare('DELETE FROM vp_orders WHERE order_number = ?');
$stmt->bind_param('s', $testOrderNum);
$stmt->execute();
$stmt->close();

echo "1. Cleaned up previous test order lines for {$testOrderNum}.\n";

// Step 2: Insert Variant Line 1 (Size: M, Color: Red)
$line1 = [
    'order_number' => $testOrderNum,
    'item_code' => $testItemCode,
    'sku' => $testItemCode . '-M-RED',
    'size' => 'M',
    'color' => 'Red',
    'title' => 'Test Variant Shirt - Medium Red',
    'quantity' => 1,
    'itemprice' => 1000.0,
    'finalprice' => 1000.0,
    'shipping_country' => 'IN',
    'status' => 'pending',
];

$res1 = $ordersModel->insertOrder($line1);
if (empty($res1['success']) && !is_numeric($res1)) {
    echo "FAILED to insert Line 1: " . json_encode($res1) . "\n";
    exit(1);
}
echo "2. Successfully inserted Variant Line 1 (Red / M).\n";

// Step 3: Insert Variant Line 2 (SAME item_code, DIFFERENT variant: Size: L, Color: Blue)
$line2 = [
    'order_number' => $testOrderNum,
    'item_code' => $testItemCode,
    'sku' => $testItemCode . '-L-BLUE',
    'size' => 'L',
    'color' => 'Blue',
    'title' => 'Test Variant Shirt - Large Blue',
    'quantity' => 2,
    'itemprice' => 1200.0,
    'finalprice' => 1200.0,
    'shipping_country' => 'IN',
    'status' => 'pending',
];

$res2 = $ordersModel->insertOrder($line2);
if (isset($res2['success']) && $res2['success'] === false) {
    echo "FAILED: Variant Line 2 was wrongly rejected as duplicate! Message: " . ($res2['message'] ?? '') . "\n";
    // Cleanup
    $conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
    exit(1);
}
echo "3. Successfully inserted Variant Line 2 (Blue / L) with same item_code!\n";

// Step 4: Re-insert Variant Line 1 (Exact duplicate of Line 1)
$res3 = $ordersModel->insertOrder($line1);
if (isset($res3['success']) && $res3['success'] === false && str_contains($res3['message'] ?? '', 'Duplicate')) {
    echo "4. Correctly rejected re-insert of exact duplicate Variant Line 1.\n";
} else {
    echo "FAILED: Duplicate Variant Line 1 was not rejected! Result: " . json_encode($res3) . "\n";
    $conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
    exit(1);
}

// Step 5: Test findOrderLineByOrderNumberAndItemCode for specific variants
$found1 = $ordersModel->findOrderLineByOrderNumberAndItemCode($testOrderNum, $testItemCode, $line1['sku'], $line1['size'], $line1['color']);
$found2 = $ordersModel->findOrderLineByOrderNumberAndItemCode($testOrderNum, $testItemCode, $line2['sku'], $line2['size'], $line2['color']);

if ($found1 === null || $found2 === null) {
    echo "FAILED: Could not look up individual variant lines via findOrderLineByOrderNumberAndItemCode.\n";
    $conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
    exit(1);
}

if ($found1['id'] === $found2['id']) {
    echo "FAILED: findOrderLineByOrderNumberAndItemCode returned same line ID for two different variants!\n";
    $conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
    exit(1);
}
echo "5. findOrderLineByOrderNumberAndItemCode correctly distinguished Line 1 (ID: {$found1['id']}) and Line 2 (ID: {$found2['id']}).\n";

// Step 6: Test updateImportedOrder for Line 2 specifically
$line2Update = $line2;
$line2Update['id'] = $found2['id'];
$line2Update['quantity'] = 5;
$updRes = $ordersModel->updateImportedOrder($line2Update);

$checkLine1 = $conn->query("SELECT quantity FROM vp_orders WHERE id = " . (int)$found1['id'])->fetch_assoc();
$checkLine2 = $conn->query("SELECT quantity FROM vp_orders WHERE id = " . (int)$found2['id'])->fetch_assoc();

if ((int)$checkLine1['quantity'] !== 1 || (int)$checkLine2['quantity'] !== 5) {
    echo "FAILED: Updating Line 2 modified Line 1 or failed! Line 1 qty: {$checkLine1['quantity']}, Line 2 qty: {$checkLine2['quantity']}\n";
    $conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
    exit(1);
}
echo "6. updateImportedOrder correctly updated Line 2 qty to 5 without altering Line 1.\n";

// Step 7: Cleanup
$conn->query("DELETE FROM vp_orders WHERE order_number = '{$testOrderNum}'");
echo "7. Cleaned up test data.\n\nALL TESTS PASSED SUCCESSFULLY!\n";
