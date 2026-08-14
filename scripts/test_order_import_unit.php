<?php
declare(strict_types=1);

/**
 * Unit test for Order import variant duplicate fix and match keys.
 *
 * Run via: php scripts/test_order_import_unit.php
 */

require_once __DIR__ . '/../controllers/OrdersController.php';

// Reflected call to test private static method vendorOrderLineMatchKey in OrdersController
$refMethod = new ReflectionMethod('OrdersController', 'vendorOrderLineMatchKey');

$key1 = $refMethod->invoke(null, 'JPA001', 'JPA001-RED-M', 'M', 'Red');
$key2 = $refMethod->invoke(null, 'JPA001', 'JPA001-BLUE-L', 'L', 'Blue');
$key3 = $refMethod->invoke(null, 'JPA001', '', 'S', 'Green');
$key4 = $refMethod->invoke(null, 'JPA001', '', '', '');

echo "Key 1 (Red M): {$key1}\n";
echo "Key 2 (Blue L): {$key2}\n";
echo "Key 3 (Green S): {$key3}\n";
echo "Key 4 (No variant): {$key4}\n";

// Validation 1: All 4 keys must be distinct
$keys = [$key1, $key2, $key3, $key4];
if (count(array_unique($keys)) !== 4) {
    echo "FAILED: vendorOrderLineMatchKey generated duplicate keys for distinct variants!\n";
    exit(1);
}
echo "✓ Pass: vendorOrderLineMatchKey generates distinct keys for distinct variants.\n";

// Validation 2: Same variant produces same key
$key1Repeat = $refMethod->invoke(null, 'jpa001', 'JPA001-RED-M', 'm', 'red');
if ($key1 !== $key1Repeat) {
    echo "FAILED: Case variations produced different keys!\n";
    exit(1);
}
echo "✓ Pass: vendorOrderLineMatchKey is case-insensitive and deterministic.\n";

// Validation 3: Verify mock order line duplicate logic simulation
$mockDbLines = [
    [
        'id' => 101,
        'order_number' => '3093696',
        'item_code' => 'JPA001',
        'sku' => 'JPA001-RED-M',
        'size' => 'M',
        'color' => 'Red',
    ],
    [
        'id' => 102,
        'order_number' => '3093696',
        'item_code' => 'JPA001',
        'sku' => 'JPA001-BLUE-L',
        'size' => 'L',
        'color' => 'Blue',
    ],
];

function mockFindOrderLine(array $dbLines, string $orderNumber, string $itemCode, string $sku, string $size, string $color): ?array {
    $sku = trim($sku);
    $size = trim($size);
    $color = trim($color);

    foreach ($dbLines as $line) {
        if ($line['order_number'] !== $orderNumber || strcasecmp($line['item_code'], $itemCode) !== 0) {
            continue;
        }
        $exSku = trim((string)($line['sku'] ?? ''));
        $exSize = trim((string)($line['size'] ?? ''));
        $exColor = trim((string)($line['color'] ?? ''));

        if ($sku !== '' && strcasecmp($exSku, $sku) === 0) {
            return $line;
        }
        if (($size !== '' || $color !== '') && strcasecmp($exSize, $size) === 0 && strcasecmp($exColor, $color) === 0) {
            return $line;
        }
    }

    return null;
}

function mockIsDuplicate(array $dbLines, string $orderNumber, string $itemCode, string $sku, string $size, string $color): bool {
    $existing = mockFindOrderLine($dbLines, $orderNumber, $itemCode, $sku, $size, $color);
    return $existing !== null;
}

// Test Line 1 (Red M) vs DB (has Line 1 & Line 2)
$dup1 = mockIsDuplicate($mockDbLines, '3093696', 'JPA001', 'JPA001-RED-M', 'M', 'Red');
if (!$dup1) {
    echo "FAILED: Existing Line 1 (Red M) was not detected as duplicate on re-import!\n";
    exit(1);
}
echo "✓ Pass: Existing Line 1 detected as duplicate on re-import.\n";

// Test Line 3 (Yellow XL - New Variant) vs DB (has Line 1 & Line 2)
$dup3 = mockIsDuplicate($mockDbLines, '3093696', 'JPA001', 'JPA001-YELLOW-XL', 'XL', 'Yellow');
if ($dup3) {
    echo "FAILED: New variant Line 3 (Yellow XL) was wrongly flagged as duplicate!\n";
    exit(1);
}
echo "✓ Pass: New variant Line 3 (Yellow XL) is NOT flagged as duplicate.\n";

echo "\nALL UNIT TESTS PASSED SUCCESSFULLY!\n";
