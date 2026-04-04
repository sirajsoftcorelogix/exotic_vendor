<?php
/**
 * Alankit Sek Decryption Test v3
 * Focus on CTR mode with different IV approaches
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];
$appKey = $alankitConfig['app_key'] ?? 'f8c4e2d1a9b3c5e7f9a1b3c5e7f9a1b3';

echo "=== ALANKIT SEK DECRYPTION TEST v3 - CTR MODE IV TESTING ===\n\n";

require_once 'helpers/RsaEncryptor.php';
require_once 'models/invoice/AlankitIrnClient.php';

try {
    $client = new AlankitIrnClient(
        $alankitConfig['username'],
        $alankitConfig['password'],
        $alankitConfig['subscription_key'],
        $appKey,
        $alankitConfig['gstin'],
        $alankitConfig['force_refresh_access_token'] ?? true
    );
    
    if ($client->authenticate()) {
        $reflection = new ReflectionClass($client);
        $sekProperty = $reflection->getProperty('sek');
        $sekProperty->setAccessible(true);
        $sek = $sekProperty->getValue($client);
        
        if ($sek) {
            $encryptedData = base64_decode($sek, true);
            echo "Encrypted data: " . strlen($encryptedData) . " bytes\n";
            echo "Encrypted hex: " . bin2hex($encryptedData) . "\n\n";
            
            $keyBytes = hash('sha256', $appKey, true);
            
            // Test 1: IV = first 16 bytes of ciphertext
            echo "=== TEST 1: CTR with IV = first 16 bytes of ciphertext ===\n";
            $iv = substr($encryptedData, 0, 16);
            $cipherData = substr($encryptedData, 16);
            echo "IV (hex): " . bin2hex($iv) . "\n";
            echo "Cipher data: " . strlen($cipherData) . " bytes\n";
            $result = openssl_decrypt($cipherData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 2: IV = sha256 of appkey
            echo "=== TEST 2: CTR with IV = SHA-256 hash of AppKey ===\n";
            $iv = hash('sha256', $appKey, true);
            echo "IV (hex): " . bin2hex($iv) . "\n";
            $result = openssl_decrypt($encryptedData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 3: IV = md5 of appkey
            echo "=== TEST 3: CTR with IV = MD5 hash of AppKey ===\n";
            $iv = md5($appKey, true);
            echo "IV (hex): " . bin2hex($iv) . "\n";
            $result = openssl_decrypt($encryptedData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 4: IV = "0" repeated 16 times (already tested but for reference)
            echo "=== TEST 4: CTR with IV = 16 zero bytes (original) ===\n";
            $iv = str_repeat("\0", 16);
            echo "IV (hex): " . bin2hex($iv) . "\n";
            $result = openssl_decrypt($encryptedData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 5: Try GCM mode (authenticated encryption)
            echo "=== TEST 5: AES-256-GCM with zero IV ===\n";
            $iv = str_repeat("\0", 12); // GCM typically uses 12-byte IV
            $tag = ''; // Will be set if using openssl_decrypt with GCM
            $result = openssl_decrypt($encryptedData, 'aes-256-gcm', $keyBytes, OPENSSL_RAW_DATA, $iv, null);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 6: Maybe it's OCB mode
            echo "=== TEST 6: AES-256-OCB with zero IV ===\n";
            $iv = str_repeat("\0", 15); // OCB uses 15-byte IV
            $result = openssl_decrypt($encryptedData, 'aes-256-ocb', $keyBytes, OPENSSL_RAW_DATA, $iv, null);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
            
            // Test 7: Try with MD5 hash as key (16 bytes) + AES-128-CTR with zero IV
            echo "=== TEST 7: AES-128-CTR with MD5 hash key ===\n";
            $keyBytes128 = md5($appKey, true);
            $iv = str_repeat("\0", 16);
            $result = openssl_decrypt($encryptedData, 'aes-128-ctr', $keyBytes128, OPENSSL_RAW_DATA, $iv);
            if ($result !== false) {
                echo "✅ Decrypted: " . substr($result, 0, 200) . "\n";
                testIfValidJson($result);
            } else {
                echo "❌ Failed: " . openssl_error_string() . "\n";
            }
            echo "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

function testIfValidJson($data) {
    // Try to parse as JSON
    $json = json_decode($data, true);
    if ($json !== null) {
        echo "✅✅✅ VALID JSON DECODED! ✅✅✅\n";
        echo json_encode($json, JSON_PRETTY_PRINT) . "\n";
        return true;
    }
    
    // Check if data starts with JSON-like characters
    $data = trim($data);
    if (strpos($data, '{') === 0 || strpos($data, '[') === 0) {
        echo "⚠️  Looks like JSON but failed to parse: " . substr($data, 0, 100) . "...\n";
        return false;
    }
    
    // Check if data looks like Base64
    if (preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', substr($data, 0, 50))) {
        echo "⚠️  Looks like Base64, trying to decode...\n";
        $decoded = base64_decode($data, true);
        if ($decoded) {
            echo "✅ Base64 decoded: " . substr($decoded, 0, 100) . "\n";
            testIfValidJson($decoded);
        }
        return false;
    }
    
    echo "Data preview (first 200 chars): " . substr($data, 0, 200) . "\n";
    return false;
}

echo "=== END OF TEST ===\n";
?>
