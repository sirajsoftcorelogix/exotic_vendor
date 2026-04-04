<?php
/**
 * Test: Is the Bearer Token encrypted and needs Sek decryption?
 */

$config = require 'config.php';
$alankitConfig = $config['alankit'] ?? [];

echo "=== TOKEN DECRYPTION TEST ===\n\n";

require_once 'helpers/RsaEncryptor.php';
require_once 'models/invoice/AlankitIrnClient.php';

try {
    $client = new AlankitIrnClient(
        $alankitConfig['username'],
        $alankitConfig['password'],
        $alankitConfig['subscription_key'],
        $alankitConfig['app_key'],
        $alankitConfig['gstin'],
        true
    );
    
    echo "Step 1: Authenticating...\n";
    if (!$client->authenticate()) {
        echo "❌ Authentication failed\n";
        exit(1);
    }
    echo "✅ Authenticated\n\n";
    
    // Get token and sek via reflection
    $reflection = new ReflectionClass($client);
    
    $tokenProperty = $reflection->getProperty('token');
    $tokenProperty->setAccessible(true);
    $token = $tokenProperty->getValue($client);
    
    $sekProperty = $reflection->getProperty('sek');
    $sekProperty->setAccessible(true);
    $encryptedSek = $sekProperty->getValue($client);
    
    $appKeyProperty = $reflection->getProperty('appKey');
    $appKeyProperty->setAccessible(true);
    $appKey = $appKeyProperty->getValue($client);
    
    echo "Step 2: Analyzing Token\n";
    echo "Token value: " . $token . "\n";
    echo "Token length: " . strlen($token) . " characters\n";
    echo "Token (hex): " . bin2hex($token) . "\n\n";
    
    // Check if token is base64
    echo "Step 3: Checking if token looks like base64...\n";
    $decoded = base64_decode($token, true);
    if ($decoded !== false && strlen($decoded) > 0) {
        echo "✅ Token decodes as base64!\n";
        echo "Decoded length: " . strlen($decoded) . " bytes\n";
        echo "Decoded (hex): " . bin2hex($decoded) . "\n";
        echo "Decoded (raw): " . substr($decoded, 0, 100) . "\n\n";
        
        // Try decrypting with Sek
        echo "Step 4: Attempting to decrypt token with decrypted Sek...\n";
        
        // First decrypt Sek
        $encryptedData = base64_decode($encryptedSek, true);
        $keyBytes = hash('sha256', $appKey, true);
        $iv = str_repeat("\0", 16);
        
        $decryptedSek = openssl_decrypt(
            $encryptedData,
            'aes-256-ctr',
            $keyBytes,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        echo "Decrypted Sek length: " . strlen($decryptedSek) . " bytes\n";
        
        // Try AES-256-ECB decryption of token
        $tokenDecrypted = openssl_decrypt(
            $decoded,
            'aes-256-ecb',
            $decryptedSek,
            OPENSSL_RAW_DATA
        );
        
        if ($tokenDecrypted !== false) {
            echo "✅ Token decrypted with AES-256-ECB!\n";
            echo "Decrypted token: " . $tokenDecrypted . "\n";
            echo "Decrypted token (hex): " . bin2hex($tokenDecrypted) . "\n\n";
            
            // Try using the decrypted token
            echo "Step 5: Testing IRN request with decrypted token...\n";
            testWithToken(
                "https://developers.eraahi.com/eInvoiceGateway/eicore/v1.03/Invoice",
                json_encode(['Data' => 'test']),
                $alankitConfig['subscription_key'],
                $tokenDecrypted,
                $alankitConfig['gstin'],
                $alankitConfig['username']
            );
        } else {
            echo "❌ AES-256-ECB decryption failed\n";
            
            // Try AES-128-ECB
            echo "\nTrying AES-128-ECB...\n";
            $tokenDecrypted = openssl_decrypt(
                $decoded,
                'aes-128-ecb',
                substr($decryptedSek, 0, 16),
                OPENSSL_RAW_DATA
            );
            
            if ($tokenDecrypted !== false) {
                echo "✅ Token decrypted with AES-128-ECB!\n";
                echo "Decrypted token: " . $tokenDecrypted . "\n";
            }
        }
    } else {
        echo "❌ Token does not decode as base64\n";
    }
    
    // Check if token is hex
    echo "\nStep 6: Checking if token is hex-encoded...\n";
    if (ctype_xdigit($token)) {
        echo "✅ Token contains only hex characters\n";
        $hexDecoded = hex2bin($token);
        echo "Hex decoded length: " . strlen($hexDecoded) . " bytes\n";
        echo "Hex decoded: " . substr($hexDecoded, 0, 100) . "\n";
    } else {
        echo "❌ Token is not hex-encoded\n";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}

function testWithToken($url, $data, $subscriptionKey, $token, $gstin, $username) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_POST, true);
    
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Ocp-Apim-Subscription-Key: ' . $subscriptionKey,
        'Authorization: Bearer ' . $token,
        'Gstin: ' . $gstin,
        'user_name: ' . $username
    ];
    
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: " . $httpCode . "\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "Response Status: " . ($decoded['Status'] ?? 'unknown') . "\n";
        if (isset($decoded['ErrorDetails'])) {
            echo "Error: " . json_encode($decoded['ErrorDetails']) . "\n";
        }
    }
}

echo "\n=== END OF TEST ===\n";
?>
