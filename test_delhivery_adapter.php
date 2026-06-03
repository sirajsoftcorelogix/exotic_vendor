<?php
/**
 * Test DelhiveryAdapter directly
 * URL: http://localhost/exotic_vendor/test_delhivery_adapter.php
 */

require_once __DIR__ . '/helpers/courier/Adapters/DelhiveryAdapter.php';
require_once __DIR__ . '/models/courier/CourierAccount.php';
require_once __DIR__ . '/models/courier/CourierShipment.php';
require_once __DIR__ . '/config.php';

$cfg = require __DIR__ . '/config.php';
$db = $cfg['db'] ?? [];
$conn = new mysqli(
    (string) ('localhost'),
    (string) ('root'),
    (string) (''),
    (string) ('vp_test'),
    (int) (3306)
);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: {$conn->connect_error}\n");
    exit(1);
}
$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));
$GLOBALS['conn'] = $conn;

// Create test request
$testRequest = [
    'order_number' => 'TEST-001',
    'partner_code' => 'delhivery',
    'partner_account_id' => 0,
    'weight' => 2.5,
    'length' => 30,
    'breadth' => 20,
    'height' => 10,
    'chargeable_weight_kg' => 2.5,
    'cod' => 0,
    'destination_country' => 'IN',
    'destination' => [
        'postcode' => '110001',
        'city' => 'Delhi',
        'state' => 'Delhi',
        'country_code' => 'IN',
    ],
    'is_international' => false,
];

// Create adapter
$accountModel = new CourierAccount($conn);
$shipmentModel = new CourierShipment($conn);
$adapter = new DelhiveryAdapter($accountModel, $shipmentModel);

// Get rates
$result = $adapter->getRates($testRequest);

// Display results
header('Content-Type: application/json');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
