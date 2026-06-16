<?php
/**
 * Aramex CreateShipments API Diagnostics
 * Access: ?page=dispatch&action=test_aramex_createshipments
 */

require_once __DIR__ . '/helpers/courier/AramexShipmentBuilder.php';
require_once __DIR__ . '/aramex_service.php';
require_once __DIR__ . '/models/courier/CourierAccount.php';

$accountModel = new CourierAccount($GLOBALS['conn']);
$accounts = $accountModel->listActiveAccountsByPartnerCode('aramex');

if (!$accounts) {
    echo "No Aramex accounts configured.";
    exit;
}

$credentials = $accountModel->getCredentialsJson((int)$accounts[0]['id']);

echo "<h2>Aramex CreateShipments API Diagnostics</h2>";

// Test data
$testContext = [
    'order_number' => 'TEST_ORD_' . date('YmdHis'),
    'address' => [
        'shipping_first_name' => 'John',
        'shipping_last_name' => 'Doe',
        'shipping_address_line1' => '123 Main St',
        'shipping_city' => 'New York',
        'shipping_state' => 'NY',
        'shipping_zipcode' => '10001',
        'shipping_country_code' => 'US',
        'shipping_phone' => '+1 212 555 1234',
        'shipping_mobile' => '+1 212 555 5678',
        'shipping_email' => 'john@example.com',
    ],
    'box' => [
        'pieces' => 1,
        'weight' => 1.0,
    ],
    'invoice' => [
        'invoice_number' => 'INV123',
        'invoice_date' => date('Y-m-d'),
        'total_amount' => 100.00,
        'tax_amount' => 10.00,
    ],
    'items' => [
        [
            'hsn' => '6204',
            'name' => 'Test Item',
            'quantity' => 1,
            'unit_price' => 100.00,
        ]
    ],
    'description' => 'Test Shipment',
    'currency_code' => 'USD',
    'customs_value' => 100.00,
];

try {
    echo "<h3>Step 1: Build CreateShipments Request</h3>";
    $params = AramexShipmentBuilder::buildCreateShipmentsRequest($credentials, $testContext);
    echo "<pre>" . htmlspecialchars(json_encode($params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    
    echo "<h3>Step 2: Initialize Aramex Service</h3>";
    $service = new AramexService($credentials);
    echo "<p style='color:green;'>✓ Service initialized</p>";
    
    echo "<h3>Step 3: Make SOAP Call to CreateShipments</h3>";
    $response = $service->createInternationalShipment($testContext);
    
    echo "<h4>Raw Response:</h4>";
    echo "<pre>" . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    
    if (!empty($response['success'])) {
        echo "<p style='color:green;'><strong>✓ SUCCESS</strong> - Shipment created successfully</p>";
        echo "<h4>Shipment Details:</h4>";
        echo "<pre>" . htmlspecialchars(json_encode($response['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
    } else {
        echo "<p style='color:red;'><strong>✗ FAILED</strong> - Shipment creation failed</p>";
        
        if (!empty($response['errors'])) {
            echo "<h4>Errors:</h4>";
            foreach ((array)$response['errors'] as $err) {
                if (is_array($err)) {
                    echo "<p><strong>" . ($err['code'] ?? 'N/A') . ":</strong> " . ($err['message'] ?? 'No message') . "</p>";
                } elseif (is_object($err)) {
                    echo "<p><strong>" . ($err->Code ?? $err->code ?? 'N/A') . ":</strong> " . ($err->Message ?? $err->message ?? 'No message') . "</p>";
                } else {
                    echo "<p>" . htmlspecialchars(json_encode($err)) . "</p>";
                }
            }
        }
        
        if (!empty($response['raw_response'])) {
            echo "<h4>Full Response Object:</h4>";
            echo "<pre>" . htmlspecialchars(json_encode($response['raw_response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red;'><strong>Exception:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "<h3>Step 4: Check PHP Error Log</h3>";
echo "<p>Check <code>logs/</code> or PHP error log for detailed debugging info:</p>";
echo "<pre>tail -f " . ini_get('error_log') . "</pre>";
?>
