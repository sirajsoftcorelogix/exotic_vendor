<?php
/**
 * Alankit Token Validation Diagnostic
 * Test different header combinations to resolve "Invalid Token from GSTVitalAuthHandler"
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];

echo "=== ALANKIT TOKEN VALIDATION DIAGNOSTIC ===\n\n";

require_once 'helpers/RsaEncryptor.php';
require_once 'models/invoice/AlankitIrnClient.php';

try {
    // Create client and authenticate
    $client = new AlankitIrnClient(
        $alankitConfig['username'],
        $alankitConfig['password'],
        $alankitConfig['subscription_key'],
        $alankitConfig['app_key'],
        $alankitConfig['gstin'],
        true
    );
    
    echo "Authenticating...\n";
    if (!$client->authenticate()) {
        echo "❌ Authentication failed\n";
        exit(1);
    }
    echo "✅ Authenticated\n\n";
    
    // Get token via reflection
    $reflection = new ReflectionClass($client);
    $tokenProperty = $reflection->getProperty('token');
    $tokenProperty->setAccessible(true);
    $token = $tokenProperty->getValue($client);
    
    $gstiProperty = $reflection->getProperty('gstin');
    $gstiProperty->setAccessible(true);
    $gstin = $gstiProperty->getValue($client);
    
    $usernameProperty = $reflection->getProperty('username');
    $usernameProperty->setAccessible(true);
    $username = $usernameProperty->getValue($client);
    
    $subKeyProperty = $reflection->getProperty('subscriptionKey');
    $subKeyProperty->setAccessible(true);
    $subscriptionKey = $subKeyProperty->getValue($client);
    
    echo "Token Details:\n";
    echo "Token: " . substr($token, 0, 30) . "...\n";
    echo "GSTIN: " . $gstin . "\n";
    echo "Username: " . $username . "\n";
    echo "Subscription Key: " . $subscriptionKey . "\n\n";
    
    // Test payload
    $testPayload = json_encode([
        'Version' => '1.1',
        'test' => true
    ]);
    
    $url = "https://developers.eraahi.com/eInvoiceGateway/eicore/v1.03/Invoice";
    
    // Test 1: Current headers (with GSTIN)
    echo "=== Test 1: Authorization + GSTIN + UserName ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: Bearer ' . $token,
            'Gstin: ' . $gstin,
            'user_name: ' . $username
        ]
    );
    echo "\n";
    
    // Test 2: Without user_name
    echo "=== Test 2: Authorization + GSTIN (no user_name) ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: Bearer ' . $token,
            'Gstin: ' . $gstin
        ]
    );
    echo "\n";
    
    // Test 3: Without GSTIN
    echo "=== Test 3: Authorization + UserName (no GSTIN) ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: Bearer ' . $token,
            'user_name: ' . $username
        ]
    );
    echo "\n";
    
    // Test 4: Only Authorization header
    echo "=== Test 4: Authorization only ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: Bearer ' . $token
        ]
    );
    echo "\n";
    
    // Test 5: Check if token needs to be base64 encoded
    echo "=== Test 5: Authorization with Base64-encoded token ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: Bearer ' . base64_encode($token),
            'Gstin: ' . $gstin
        ]
    );
    echo "\n";
    
    // Test 6: Try different auth format
    echo "=== Test 6: Basic Auth format (not Bearer) ===\n";
    testHeaders(
        $url,
        $testPayload,
        $subscriptionKey,
        [
            'Authorization: ' . $token,
            'Gstin: ' . $gstin
        ]
    );
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

function testHeaders($url, $data, $subscriptionKey, $authHeaders) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POST, true);
    
    // Build headers
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Ocp-Apim-Subscription-Key: ' . $subscriptionKey
    ];
    
    // Add auth headers
    $headers = array_merge($headers, $authHeaders);
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['Data' => $data]));
    
    echo "Headers sent:\n";
    foreach ($headers as $h) {
        // Don't print full token
        if (strpos($h, 'Authorization') !== false) {
            echo "  " . substr($h, 0, 50) . "...\n";
        } else {
            echo "  " . $h . "\n";
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: " . $httpCode . "\n";
    
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Response Status: " . ($decoded['Status'] ?? 'unknown') . "\n";
        if (isset($decoded['ErrorDetails'])) {
            echo "Error: " . json_encode($decoded['ErrorDetails']) . "\n";
        } elseif (isset($decoded['error'])) {
            echo "Error: " . $decoded['error'] . "\n";
        } else {
            echo "Response: " . json_encode(array_slice($decoded, 0, 2)) . "\n";
        }
    } else {
        echo "Response (first 200 chars): " . substr($response, 0, 200) . "\n";
    }
}

echo "\n=== END OF DIAGNOSTIC ===\n";
?>
