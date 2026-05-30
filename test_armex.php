<?php

require_once __DIR__ . '/models/courier/CourierAccount.php';
require_once __DIR__ . '/aramex_service.php';

/**
 * Manual Aramex sandbox rate test.
 *
 * Usage:
 *   php test_armex.php <courier_account_id>
 */

$accountId = isset($argv[1]) ? (int) $argv[1] : 0;
if ($accountId <= 0) {
    fwrite(STDERR, "Usage: php test_armex.php <courier_account_id>\n");
    exit(1);
}

$cfg = require __DIR__ . '/config.php';
$db = $cfg['db'] ?? [];
$conn = new mysqli(
    (string) ($db['host'] ?? '127.0.0.1'),
    (string) ($db['user'] ?? ''),
    (string) ($db['pass'] ?? ''),
    (string) ($db['name'] ?? ''),
    (int) ($db['port'] ?? 3306)
);
if ($conn->connect_error) {
    fwrite(STDERR, "DB connection failed: {$conn->connect_error}\n");
    exit(1);
}
$conn->set_charset((string) ($db['charset'] ?? 'utf8mb4'));
$GLOBALS['conn'] = $conn;

require_once __DIR__ . '/helpers/courier/Gateway/CourierGateway.php';

$gateway = new CourierGateway($conn);
$result = $gateway->getRates([
    'order_number' => 'TEST-INTL',
    'partner_account_id' => $accountId,
    'weight' => 1.2,
    'chargeable_weight_kg' => 1.2,
    'length' => 20,
    'breadth' => 15,
    'height' => 10,
    'destination_country' => 'US',
    'destination' => [
        'city' => 'New York',
        'postcode' => '10001',
        'country_code' => 'US',
    ],
]);

echo "=== Aramex international rates ===\n";
print_r($result);
$conn->close();
