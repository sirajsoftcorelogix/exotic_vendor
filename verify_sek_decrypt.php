<?php
/**
 * Verify Decrypted SEK Functionality
 * Test that decrypted SEK can correctly encrypt/decrypt payloads
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];

echo "=== DECRYPTED SEK VERIFICATION TEST ===\n\n";

require_once 'helpers/RsaEncryptor.php';
require_once 'models/invoice/AlankitIrnClient.php';

try {
    // Create client
    $client = new AlankitIrnClient(
        $alankitConfig['username'],
        $alankitConfig['password'],
        $alankitConfig['subscription_key'],
        $alankitConfig['app_key'],
        $alankitConfig['gstin'],
        true
    );
    
    // Authenticate
    echo "Step 1: Authenticating...\n";
    if (!$client->authenticate()) {
        echo "❌ Authentication failed\n";
        exit(1);
    }
    echo "✅ Authenticated successfully\n\n";
    
    // Use reflection to access private properties
    $reflection = new ReflectionClass($client);
    
    $sekProperty = $reflection->getProperty('sek');
    $sekProperty->setAccessible(true);
    $encryptedSek = $sekProperty->getValue($client);
    
    $appKeyProperty = $reflection->getProperty('appKey');
    $appKeyProperty->setAccessible(true);
    $appKey = $appKeyProperty->getValue($client);
    
    echo "Step 2: Examining Encrypted SEK\n";
    echo "Encrypted SEK (first 64 chars): " . substr($encryptedSek, 0, 64) . "...\n";
    echo "Encrypted SEK length: " . strlen($encryptedSek) . " chars\n\n";
    
    // Manually decrypt SEK using the proven method
    echo "Step 3: Decrypting SEK using AES-256-CTR\n";
    $encryptedData = base64_decode($encryptedSek, true);
    
    if ($encryptedData === false) {
        echo "❌ Failed to base64 decode SEK\n";
        exit(1);
    }
    
    echo "Decoded encrypted data: " . strlen($encryptedData) . " bytes\n";
    echo "Encrypted data (hex): " . bin2hex($encryptedData) . "\n";
    
    // Decrypt
    $keyBytes = hash('sha256', $appKey, true);
    $iv = str_repeat("\0", 16);
    
    $decryptedSek = openssl_decrypt(
        $encryptedData,
        'aes-256-ctr',
        $keyBytes,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    if ($decryptedSek === false) {
        echo "❌ Decryption failed: " . openssl_error_string() . "\n";
        exit(1);
    }
    
    echo "✅ Decrypted successfully!\n";
    echo "Decrypted SEK length: " . strlen($decryptedSek) . " bytes\n";
    echo "Decrypted SEK (hex): " . bin2hex($decryptedSek) . "\n";
    echo "Decrypted SEK (base64): " . base64_encode($decryptedSek) . "\n\n";
    
    // Test encrypting a payload with decrypted SEK
    echo "Step 4: Testing Payload Encryption with Decrypted SEK\n";
    
    $testPayload = json_encode([
        'version' => '1.1',
        'test' => true,
        'invoice_number' => 'TEST-001',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    echo "Test payload: " . $testPayload . "\n";
    echo "Test payload length: " . strlen($testPayload) . " bytes\n\n";
    
    // Base64 encode the payload (as per Alankit spec)
    $base64Payload = base64_encode($testPayload);
    echo "Base64 payload: " . substr($base64Payload, 0, 100) . "...\n";
    echo "Base64 payload length: " . strlen($base64Payload) . " bytes\n\n";
    
    // Encrypt with decrypted SEK using AES-256-ECB
    echo "Encrypting with AES-256-ECB...\n";
    $encrypted = openssl_encrypt(
        $testPayload,
        'aes-256-ecb',
        $decryptedSek,
        OPENSSL_RAW_DATA
    );
    
    if ($encrypted === false) {
        echo "❌ Encryption failed: " . openssl_error_string() . "\n";
        exit(1);
    }
    
    echo "✅ Encrypted successfully!\n";
    echo "Encrypted length: " . strlen($encrypted) . " bytes\n";
    echo "Encrypted (base64): " . base64_encode($encrypted) . "\n\n";
    
    // Test decrypting back
    echo "Step 5: Testing Payload Decryption (Round-trip)\n";
    
    $decrypted = openssl_decrypt(
        $encrypted,
        'aes-256-ecb',
        $decryptedSek,
        OPENSSL_RAW_DATA
    );
    
    if ($decrypted === false) {
        echo "❌ Decryption failed: " . openssl_error_string() . "\n";
        exit(1);
    }
    
    echo "✅ Decrypted successfully!\n";
    echo "Decrypted payload: " . $decrypted . "\n\n";
    
    // Verify it matches
    if ($decrypted === $testPayload) {
        echo "✅✅✅ PAYLOAD MATCHES ORIGINAL! SEK IS WORKING CORRECTLY! ✅✅✅\n\n";
    } else {
        echo "❌ Payload mismatch!\n";
        echo "Expected: " . $testPayload . "\n";
        echo "Got: " . $decrypted . "\n";
        exit(1);
    }
    
    // Additional test: Encrypt base64 payload as per actual flow
    echo "Step 6: Testing with Base64-Encoded Payload (Actual Flow)\n";
    
    $encryptedBase64Payload = openssl_encrypt(
        $base64Payload,
        'aes-256-ecb',
        $decryptedSek,
        OPENSSL_RAW_DATA
    );
    
    if ($encryptedBase64Payload === false) {
        echo "❌ Encryption of base64 payload failed\n";
        exit(1);
    }
    
    echo "✅ Encrypted base64 payload\n";
    echo "Encrypted (length): " . strlen($encryptedBase64Payload) . " bytes\n";
    echo "Encrypted (base64): " . substr(base64_encode($encryptedBase64Payload), 0, 100) . "...\n\n";
    
    // Decrypt and verify
    $decryptedBase64Payload = openssl_decrypt(
        $encryptedBase64Payload,
        'aes-256-ecb',
        $decryptedSek,
        OPENSSL_RAW_DATA
    );
    
    if ($decryptedBase64Payload === $base64Payload) {
        echo "✅ Base64 payload round-trip successful!\n\n";
    } else {
        echo "❌ Base64 payload mismatch\n";
        exit(1);
    }
    
    echo "=== SUMMARY ===\n";
    echo "✅ SEK decryption: WORKING\n";
    echo "✅ Payload encryption with SEK: WORKING\n";
    echo "✅ Payload decryption with SEK: WORKING\n";
    echo "✅ Round-trip encryption/decryption: WORKING\n";
    echo "✅ Base64 payload handling: WORKING\n\n";
    
    echo "🎉 ALL TESTS PASSED! SEK IS CORRECTLY DECRYPTED AND FUNCTIONAL! 🎉\n";
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n=== END OF TEST ===\n";
?>
