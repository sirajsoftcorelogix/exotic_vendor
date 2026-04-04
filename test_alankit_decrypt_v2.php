<?php
/**
 * Alankit Sek Decryption Test Script v2
 * Extended testing with different cipher modes and padding options
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];
$appKey = $alankitConfig['app_key'] ?? 'f8c4e2d1a9b3c5e7f9a1b3c5e7f9a1b3';

echo "=== ALANKIT SEK DECRYPTION TEST v2 ===\n";
echo "AppKey: " . $appKey . "\n";
echo "AppKey as UTF-8 bytes: " . bin2hex($appKey) . "\n\n";

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
        echo "✅ Authentication successful\n\n";
        
        $reflection = new ReflectionClass($client);
        $sekProperty = $reflection->getProperty('sek');
        $sekProperty->setAccessible(true);
        $sek = $sekProperty->getValue($client);
        
        if ($sek) {
            echo "✅ Sek received\n";
            $encryptedData = base64_decode($sek, true);
            echo "✅ Encrypted data: " . strlen($encryptedData) . " bytes\n";
            echo "Encrypted hex: " . substr(bin2hex($encryptedData), 0, 64) . "...\n\n";
            
            // ==== NEW TESTS ====
            
            // Test 1: With PKCS7 padding (remove OPENSSL_RAW_DATA)
            echo "=== TEST 1: SHA-256 + AES-256-ECB WITH PKCS7 PADDING ===\n";
            testWithPadding($encryptedData, $appKey, 'aes-256-ecb', 'sha256');
            
            // Test 2: MD5 with PKCS7 padding
            echo "=== TEST 2: MD5 + AES-128-ECB WITH PKCS7 PADDING ===\n";
            testWithPadding($encryptedData, $appKey, 'aes-128-ecb', 'md5');
            
            // Test 3: Try AES-128-CBC instead of ECB
            echo "=== TEST 3: SHA-256 + AES-256-CBC (Raw Data) ===\n";
            testWithCBC($encryptedData, $appKey, 'aes-256-cbc', 'sha256');
            
            // Test 4: Try MD5 + AES-128-CBC
            echo "=== TEST 4: MD5 + AES-128-CBC (Raw Data) ===\n";
            testWithCBC($encryptedData, $appKey, 'aes-128-cbc', 'md5');
            
            // Test 5: Raw AppKey with PKCS7 padding
            echo "=== TEST 5: Raw AppKey (as UTF-8 string) + AES-256-ECB WITH PKCS7 PADDING ===\n";
            testRawWithPadding($encryptedData, $appKey, 'aes-256-ecb', 32);
            
            // Test 6: Check if AppKey is actually Base64 encoded
            echo "=== TEST 6: Try decoding AppKey as Base64 first ===\n";
            testBase64AppKey($encryptedData, $appKey);
            
            // Test 7: Try with different key derivations for CTR or GCM
            echo "=== TEST 7: Try AES-256-CTR mode ===\n";
            testWithCTR($encryptedData, $appKey);
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

function testWithPadding($encryptedData, $appKey, $cipher, $hashMethod) {
    $keyBytes = ($hashMethod === 'sha256') 
        ? hash('sha256', $appKey, true)
        : md5($appKey, true);
    
    echo "Using cipher: $cipher with " . strtoupper($hashMethod) . " hash\n";
    echo "Key (" . strlen($keyBytes) . " bytes): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    
    // Test WITH padding (0 flag)
    $result = openssl_decrypt($encryptedData, $cipher, $keyBytes, 0);
    if ($result !== false) {
        echo "✅ SUCCESS with PKCS7 padding!\n";
        echo "Decrypted (first 100 chars): " . substr($result, 0, 100) . "\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testWithCBC($encryptedData, $appKey, $cipher, $hashMethod) {
    $keyBytes = ($hashMethod === 'sha256') 
        ? hash('sha256', $appKey, true)
        : md5($appKey, true);
    
    echo "Using cipher: $cipher with " . strtoupper($hashMethod) . " hash\n";
    echo "Key (" . strlen($keyBytes) . " bytes): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    
    // CBC requires IV - try empty IV
    $result = openssl_decrypt($encryptedData, $cipher, $keyBytes, OPENSSL_RAW_DATA);
    if ($result !== false) {
        echo "✅ SUCCESS!\n";
        echo "Decrypted: " . substr($result, 0, 100) . "\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testRawWithPadding($encryptedData, $appKey, $cipher, $keyLength) {
    // Use AppKey directly as UTF-8 string, padded to 32 bytes
    $keyBytes = substr($appKey . str_repeat("\0", $keyLength), 0, $keyLength);
    
    echo "Using cipher: $cipher with raw AppKey (padded to $keyLength bytes, no hashing)\n";
    echo "Key (hex): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    
    $result = openssl_decrypt($encryptedData, $cipher, $keyBytes, 0);
    if ($result !== false) {
        echo "✅ SUCCESS with PKCS7 padding!\n";
        echo "Decrypted: " . substr($result, 0, 100) . "\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testBase64AppKey($encryptedData, $appKey) {
    echo "Attempting to decode AppKey as Base64...\n";
    $decodedAppKey = base64_decode($appKey, true);
    
    if ($decodedAppKey !== false && strlen($decodedAppKey) > 0) {
        echo "✅ AppKey decoded from Base64: " . strlen($decodedAppKey) . " bytes\n";
        echo "Decoded (hex): " . bin2hex($decodedAppKey) . "\n";
        
        // Try with decoded AppKey
        $keyBytes = hash('sha256', $decodedAppKey, true);
        echo "Using SHA-256 hash of decoded AppKey\n";
        
        $result = openssl_decrypt($encryptedData, 'aes-256-ecb', $keyBytes, OPENSSL_RAW_DATA);
        if ($result !== false) {
            echo "✅ SUCCESS!\n";
            echo "Decrypted: " . substr($result, 0, 100) . "\n";
            return true;
        } else {
            echo "❌ Failed: " . openssl_error_string() . "\n";
        }
    } else {
        echo "❌ AppKey is not valid Base64\n";
    }
    echo "\n";
    return false;
}

function testWithCTR($encryptedData, $appKey) {
    $keyBytes = hash('sha256', $appKey, true);
    echo "Using AES-256-CTR with SHA-256 hash\n";
    echo "Key (hex): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    
    // CTR needs an IV - try empty IV
    $iv = str_repeat("\0", 16);
    $result = openssl_decrypt($encryptedData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
    if ($result !== false) {
        echo "✅ SUCCESS!\n";
        echo "Decrypted: " . substr($result, 0, 100) . "\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

echo "=== END OF TEST ===\n";
?>
