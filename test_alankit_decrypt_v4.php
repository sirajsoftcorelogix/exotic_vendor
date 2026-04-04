<?php
/**
 * Alankit Sek Test v4
 * Hypothesis: Decrypted Sek is binary key material, not JSON
 * Test if decrypted Sek can be used to encrypt/decrypt payload
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];
$appKey = $alankitConfig['app_key'] ?? 'f8c4e2d1a9b3c5e7f9a1b3c5e7f9a1b3';

echo "=== ALANKIT SEK TEST v4 - BINARY KEY MATERIAL HYPOTHESIS ===\n\n";

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
            echo "Encrypted Sek: " . strlen($encryptedData) . " bytes\n";
            echo "Encrypted hex: " . bin2hex($encryptedData) . "\n\n";
            
            // Decrypt using TEST 4 method (AES-256-CTR with zero IV - most promising)
            $keyBytes = hash('sha256', $appKey, true);
            $iv = str_repeat("\0", 16);
            
            $decryptedSek = openssl_decrypt($encryptedData, 'aes-256-ctr', $keyBytes, OPENSSL_RAW_DATA, $iv);
            
            if ($decryptedSek !== false) {
                echo "✅ Sek decrypted successfully\n";
                echo "Decrypted Sek length: " . strlen($decryptedSek) . " bytes\n";
                echo "Decrypted Sek (hex): " . bin2hex($decryptedSek) . "\n";
                echo "Decrypted Sek (base64): " . base64_encode($decryptedSek) . "\n\n";
                
                // Now test if this can be used as encryption key for test payload
                echo "=== TESTING DECRYPTED SEK AS ENCRYPTION KEY ===\n\n";
                
                // Test payload
                $testPayload = '{"test":"data","irn":"12345","status":"success"}';
                echo "Test payload: " . $testPayload . "\n";
                echo "Test payload (base64): " . base64_encode($testPayload) . "\n\n";
                
                // Try encrypting with decrypted Sek using AES-256-ECB
                echo "--- Attempt 1: Encrypt with AES-256-ECB using decrypted Sek (32 bytes) ---\n";
                $encrypted = openssl_encrypt(
                    $testPayload,
                    'aes-256-ecb',
                    $decryptedSek,
                    OPENSSL_RAW_DATA
                );
                if ($encrypted !== false) {
                    echo "✅ Encryption successful (" . strlen($encrypted) . " bytes)\n";
                    echo "Encrypted (hex): " . bin2hex($encrypted) . "\n";
                    
                    // Try to decrypt it back
                    $decrypted = openssl_decrypt(
                        $encrypted,
                        'aes-256-ecb',
                        $decryptedSek,
                        OPENSSL_RAW_DATA
                    );
                    if ($decrypted !== false && $decrypted === $testPayload) {
                        echo "✅ Successfully decrypted back to original!\n";
                        echo "✅ DECRYPTED SEK IS CORRECT!\n";
                    } else {
                        echo "❌ Decryption failed or mismatch\n";
                    }
                } else {
                    echo "❌ Encryption failed\n";
                }
                echo "\n";
                
                // Try with AES-256-CTR
                echo "--- Attempt 2: Encrypt with AES-256-CTR using decrypted Sek ---\n";
                $testIv = str_repeat("\0", 16);
                $encrypted = openssl_encrypt(
                    $testPayload,
                    'aes-256-ctr',
                    $decryptedSek,
                    OPENSSL_RAW_DATA,
                    $testIv
                );
                if ($encrypted !== false) {
                    echo "✅ Encryption successful (" . strlen($encrypted) . " bytes)\n";
                    echo "Encrypted (hex): " . bin2hex($encrypted) . "\n";
                    
                    $decrypted = openssl_decrypt(
                        $encrypted,
                        'aes-256-ctr',
                        $decryptedSek,
                        OPENSSL_RAW_DATA,
                        $testIv
                    );
                    if ($decrypted !== false && $decrypted === $testPayload) {
                        echo "✅ Successfully decrypted back to original!\n";
                        echo "✅ DECRYPTED SEK IS CORRECT!\n";
                    } else {
                        echo "❌ Decryption failed or mismatch\n";
                    }
                } else {
                    echo "❌ Encryption failed\n";
                }
                echo "\n";
                
                // If decrypted Sek is exactly 32 bytes, check if it looks like valid base64
                echo "--- Analysis of Decrypted Sek ---\n";
                echo "Length: " . strlen($decryptedSek) . " bytes (expected 32 for AES-256)\n";
                
                if (strlen($decryptedSek) === 32) {
                    echo "✅ Length is correct for AES-256 key\n";
                }
                
                // Check entropy
                $uniqueBytes = count(array_unique(str_split($decryptedSek)));
                echo "Unique bytes: " . $uniqueBytes . "/256 (higher = more random)\n";
                
                // Try treating it as base64
                $base64Decoded = base64_decode(bin2hex($decryptedSek), true);
                if ($base64Decoded !== false) {
                    echo "Note: Decrypted Sek decodes as valid base64\n";
                }
            } else {
                echo "❌ Failed to decrypt Sek\n";
            }
        }
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== END OF TEST ===\n";
?>
