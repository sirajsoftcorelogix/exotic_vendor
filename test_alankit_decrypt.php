<?php
/**
 * Alankit Sek Decryption Test Script
 * Test different decryption methods with actual Sek and AppKey
 */

// Load config
$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];

$appKey = $alankitConfig['app_key'] ?? 'f8c4e2d1a9b3c5e7f9a1b3c5e7f9a1b3';

echo "=== ALANKIT SEK DECRYPTION TEST ===\n";
echo "AppKey: " . $appKey . "\n";
echo "AppKey Length: " . strlen($appKey) . " characters\n";
echo "Is HEX?: " . (ctype_xdigit($appKey) ? 'YES' : 'NO') . "\n\n";

// First, authenticate to get actual Sek
echo "=== STEP 1: AUTHENTICATE TO GET SEK ===\n";

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
        echo "✅ Authentication successful\n";
        
        // Use reflection to get the private $sek property
        $reflection = new ReflectionClass($client);
        $sekProperty = $reflection->getProperty('sek');
        $sekProperty->setAccessible(true);
        $sek = $sekProperty->getValue($client);
        
        if ($sek) {
            echo "✅ Sek received from API\n";
            echo "Sek (first 64 chars): " . substr($sek, 0, 64) . "...\n";
            echo "Sek Length: " . strlen($sek) . " characters\n\n";
            
            // Now test decryption methods
            echo "=== STEP 2: TEST DECRYPTION METHODS ===\n\n";
            
            // Decode Sek
            $encryptedData = base64_decode($sek, true);
            if ($encryptedData === false) {
                echo "❌ Failed to base64 decode Sek\n";
                exit(1);
            }
            echo "✅ Sek base64 decoded successfully (" . strlen($encryptedData) . " bytes)\n";
            echo "Encrypted Data (hex): " . substr(bin2hex($encryptedData), 0, 64) . "...\n\n";
            
            // Test all methods
            testMethod1($encryptedData, $appKey);
            testMethod2($encryptedData, $appKey);
            testMethod3($encryptedData, $appKey);
            testMethod4($encryptedData, $appKey);
            testMethod5($encryptedData, $appKey);
        } else {
            echo "❌ No Sek received from API\n";
        }
    } else {
        echo "❌ Authentication failed\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

function testMethod1($encryptedData, $appKey) {
    echo "--- METHOD 1: AppKey as HEX → AES-128-ECB ---\n";
    if (ctype_xdigit($appKey) && strlen($appKey) === 32) {
        $keyBytes = hex2bin($appKey);
        echo "AppKey detected as HEX, converted to " . strlen($keyBytes) . " bytes\n";
        $result = openssl_decrypt($encryptedData, 'aes-128-ecb', $keyBytes, OPENSSL_RAW_DATA);
        if ($result !== false) {
            echo "✅ SUCCESS! Decrypted result: " . substr($result, 0, 50) . "...\n";
            echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
            return true;
        } else {
            echo "❌ Failed: " . openssl_error_string() . "\n";
        }
    } else {
        echo "❌ AppKey is not HEX string\n";
    }
    echo "\n";
    return false;
}

function testMethod2($encryptedData, $appKey) {
    echo "--- METHOD 2: SHA-256 hash → AES-256-ECB ---\n";
    $keyBytes = hash('sha256', $appKey, true);
    echo "SHA-256 hash created (" . strlen($keyBytes) . " bytes)\n";
    echo "Key (hex): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    $result = openssl_decrypt($encryptedData, 'aes-256-ecb', $keyBytes, OPENSSL_RAW_DATA);
    if ($result !== false) {
        echo "✅ SUCCESS! Decrypted result: " . substr($result, 0, 50) . "...\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testMethod3($encryptedData, $appKey) {
    echo "--- METHOD 3: MD5 hash → AES-128-ECB ---\n";
    $keyBytes = md5($appKey, true);
    echo "MD5 hash created (" . strlen($keyBytes) . " bytes)\n";
    echo "Key (hex): " . bin2hex($keyBytes) . "\n";
    $result = openssl_decrypt($encryptedData, 'aes-128-ecb', $keyBytes, OPENSSL_RAW_DATA);
    if ($result !== false) {
        echo "✅ SUCCESS! Decrypted result: " . substr($result, 0, 50) . "...\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testMethod4($encryptedData, $appKey) {
    echo "--- METHOD 4: Raw AppKey padded to 16 bytes → AES-128-ECB ---\n";
    $keyBytes = substr($appKey . str_repeat("\0", 16), 0, 16);
    echo "Key created from AppKey (16 bytes)\n";
    echo "Key (hex): " . bin2hex($keyBytes) . "\n";
    $result = openssl_decrypt($encryptedData, 'aes-128-ecb', $keyBytes, OPENSSL_RAW_DATA);
    if ($result !== false) {
        echo "✅ SUCCESS! Decrypted result: " . substr($result, 0, 50) . "...\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

function testMethod5($encryptedData, $appKey) {
    echo "--- METHOD 5: Raw AppKey padded to 32 bytes → AES-256-ECB ---\n";
    $keyBytes = substr($appKey . str_repeat("\0", 32), 0, 32);
    echo "Key created from AppKey (32 bytes)\n";
    echo "Key (hex): " . substr(bin2hex($keyBytes), 0, 64) . "...\n";
    $result = openssl_decrypt($encryptedData, 'aes-256-ecb', $keyBytes, OPENSSL_RAW_DATA);
    if ($result !== false) {
        echo "✅ SUCCESS! Decrypted result: " . substr($result, 0, 50) . "...\n";
        echo "Decrypted (hex): " . substr(bin2hex($result), 0, 64) . "...\n";
        return true;
    } else {
        echo "❌ Failed: " . openssl_error_string() . "\n";
    }
    echo "\n";
    return false;
}

echo "=== END OF TEST ===\n";
?>
