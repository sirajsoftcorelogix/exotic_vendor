<?php

require_once __DIR__ . '/models/courier/CourierAccount.php';
require_once __DIR__ . '/dhl_service.php';

/**
 * Manual DHL sandbox test.
 *
 * Option A: pass credentials via courier account id:
 *   php test_dhl.php 3
 *
 * Option B: set DHL_TEST_CREDENTIALS_JSON env var with full JSON object.
 */

$accountId = isset($argv[1]) ? (int) $argv[1] : 0;
$config = [];

if ($accountId > 0) {
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
    $model = new CourierAccount($conn);
    $config = $model->getCredentialsJson($accountId);
    $conn->close();
} else {
    $raw = getenv('DHL_TEST_CREDENTIALS_JSON');
    if (is_string($raw) && trim($raw) !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $config = $decoded;
        }
    }
}

if (!$config) {
    fwrite(STDERR, "No credentials. Usage: php test_dhl.php <courier_account_id>\n");
    fwrite(STDERR, "Or set DHL_TEST_CREDENTIALS_JSON with credentials JSON.\n");
    exit(1);
}

$dhl = new DhlService($config);

$ratesPayload = $dhl->buildRatesPayload(
    [
        'postcode' => '10001',
        'city' => 'New York',
        'country_code' => 'US',
    ],
    [
        [
            'weight' => 1.2,
            'dimensions' => [
                'length' => 20,
                'width' => 15,
                'height' => 10,
            ],
        ],
    ]
);

echo "=== DHL getRates (sandbox) ===\n";
$response = $dhl->getRates($ratesPayload);
print_r($response);
