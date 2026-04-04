<?php
/**
 * RSA Encryption Helper for Alankit/Eraahi API Authentication
 * Handles public key RSA encryption for secure credential transmission
 */
class RsaEncryptor {
    
    /**
     * Load public key from file
     * @param string $publicKeyPath Path to public.txt file
     * @return resource|false Public key resource or false on failure
     */
    public static function loadPublicKey($publicKeyPath) {
        if (!file_exists($publicKeyPath)) {
            error_log("RSA: Public key file not found at: $publicKeyPath");
            return false;
        }
        
        $publicKeyData = file_get_contents($publicKeyPath);
        if (!$publicKeyData) {
            error_log("RSA: Failed to read public key file");
            return false;
        }
        
        // Remove any whitespace and newlines from the key
        $publicKeyData = trim($publicKeyData);
        $publicKeyData = str_replace(["\n", "\r", " ", "\t"], '', $publicKeyData);
        
        // Check if key already has PEM headers
        if (strpos($publicKeyData, '-----BEGIN') === false) {
            // Format the base64-encoded DER public key for OpenSSL
            // The file contains base64-encoded DER format that needs PEM wrapping
            $publicKeyPem = "-----BEGIN PUBLIC KEY-----\n";
            $publicKeyPem .= wordwrap($publicKeyData, 64, "\n", true);
            $publicKeyPem .= "\n-----END PUBLIC KEY-----";
        } else {
            $publicKeyPem = $publicKeyData;
        }
        
        $publicKey = openssl_pkey_get_public($publicKeyPem);
        if (!$publicKey) {
            error_log("RSA: Failed to parse public key with PEM format. Trying DER format...");
            
            // Try decoding as base64 and using as DER
            $derData = base64_decode($publicKeyData);
            if ($derData === false) {
                error_log("RSA: Failed to base64 decode public key");
                return false;
            }
            
            // Create a temporary file with the DER data
            $tempFile = tempnam(sys_get_temp_dir(), 'rsa_key_');
            file_put_contents($tempFile, $derData);
            
            // Try to load DER format
            $publicKey = openssl_pkey_get_public(file_get_contents($tempFile));
            unlink($tempFile);
            
            if (!$publicKey) {
                error_log("RSA: Failed to parse public key as DER. OpenSSL Error: " . openssl_error_string());
                return false;
            }
        }
        
        error_log("RSA: Successfully loaded public key from: $publicKeyPath");
        return $publicKey;
    }
    
    /**
     * Encrypt data using RSA public key
     * @param string $plaintext Data to encrypt
     * @param resource $publicKey Public key resource
     * @return string|false Base64 encoded encrypted data or false on failure
     */
    public static function encryptData($plaintext, $publicKey) {
        if (!$publicKey) {
            error_log("RSA: Invalid public key provided");
            return false;
        }
        
        $encryptedData = '';
        $success = openssl_public_encrypt($plaintext, $encryptedData, $publicKey, OPENSSL_PKCS1_PADDING);
        
        if (!$success) {
            error_log("RSA: Encryption failed. OpenSSL Error: " . openssl_error_string());
            return false;
        }
        
        // Return base64 encoded encrypted data
        return base64_encode($encryptedData);
    }
    
    /**
     * Full encryption flow: JSON -> Base64 -> RSA Encrypt
     * @param array $data Data array to send
     * @param string $publicKeyPath Path to public.txt
     * @return string|false Encrypted and base64 encoded string or false
     */
    public static function securePayload($data, $publicKeyPath) {
        // Step 1: Convert to JSON
        $jsonData = json_encode($data);
        if ($jsonData === false) {
            error_log("RSA: Failed to JSON encode data: " . json_last_error_msg());
            return false;
        }
        
        // Step 2: Base64 encode the JSON
        $base64Data = base64_encode($jsonData);
        
        // Step 3: Load public key
        $publicKey = self::loadPublicKey($publicKeyPath);
        if (!$publicKey) {
            error_log("RSA: Failed to load public key for encryption from path: $publicKeyPath");
            return false;
        }
        
        // Step 4: RSA encrypt the base64 data
        $encryptedData = self::encryptData($base64Data, $publicKey);
        
        return $encryptedData;
    }
}
