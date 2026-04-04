<?php
/**
 * Alankit IRN Generation Test
 * Test the complete IRN generation workflow with the fixed decryptSek
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];

echo "=== ALANKIT IRN GENERATION TEST ===\n\n";

require_once 'models/invoice/AlankitIrnClient.php';

try {
    // Create client
    $client = new AlankitIrnClient(
        $alankitConfig['username'],
        $alankitConfig['password'],
        $alankitConfig['subscription_key'],
        $alankitConfig['app_key'],
        $alankitConfig['gstin'],
        $alankitConfig['force_refresh_access_token'] ?? true
    );
    
    // Test invoice data
    $invoiceData = [
        'invoice_type' => 'B2B',
        'invoice_number' => 'INV-2026-00111',
        'invoice_date' => '01/04/2026',
        'seller_gstin' => '07AGAPA5363L002',
        'seller_name' => 'Exotic Vendor Ltd',
        'seller_address' => '123 Business Street',
        'seller_city' => 'New Delhi',
        'seller_state_code' => '07',
        'seller_pincode' => '110001',
        'seller_phone' => '9876543210',
        'seller_email' => 'vendor@exotic.com',
        'buyer_gstin' => '27AAFCD1234A2Z5',
        'buyer_name' => 'Test Buyer Company',
        'buyer_address' => '456 Customer Avenue',
        'buyer_city' => 'Mumbai',
        'buyer_state_code' => '27',
        'buyer_pincode' => '400001',
        'buyer_phone' => '9123456789',
        'buyer_email' => 'buyer@test.com',
        'transport_mode' => '1',
        'vehicle_number' => 'MH01AB1234',
        'vehicle_type' => 'R',
        'line_items' => [
            [
                'item_name' => 'Test Product',
                'hsn' => '1001',
                'quantity' => 10,
                'unit' => 'NOS',
                'unit_price' => 1000,
                'tax_rate' => 18,
                'tax_amount' => 1800
            ]
        ],
        'subtotal' => 10000,
        'tax_amount' => 1800,
        'discount_amount' => 0,
        'total_amount' => 11800
    ];
    
    echo "Initiating IRN generation for Invoice: " . $invoiceData['invoice_number'] . "\n";
    echo "Seller GSTIN: " . $invoiceData['seller_gstin'] . "\n";
    echo "Buyer GSTIN: " . $invoiceData['buyer_gstin'] . "\n";
    echo "Total Amount: ₹" . $invoiceData['total_amount'] . "\n\n";
    
    // Generate IRN
    $result = $client->generateIrn($invoiceData);
    
    echo "=== RESULT ===\n";
    if ($result['status']) {
        echo "✅ SUCCESS!\n";
        echo "IRN: " . ($result['irn'] ?? 'N/A') . "\n";
        echo "Ack Number: " . ($result['ack_number'] ?? 'N/A') . "\n";
        echo "Ack Date: " . ($result['ack_date'] ?? 'N/A') . "\n";
        
        if (isset($result['response']) && is_array($result['response'])) {
            echo "\nFull Response:\n";
            echo json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    } else {
        echo "❌ FAILED\n";
        echo "Message: " . ($result['message'] ?? 'Unknown error') . "\n";
        
        if (isset($result['response'])) {
            echo "\nResponse:\n";
            echo json_encode($result['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END OF TEST ===\n";
?>
