<?php
/**
 * AppKey Generator Helper
 * Generates random unique identifiers for API authentication
 * Supports both legacy 32-char alphanumeric and AES-256 64-char hex formats
 */

class AppKeyGenerator {
    /**
     * Generate a 32-character random AppKey (Legacy format - alphanumeric)
     * Uses alphanumeric characters (a-z, A-Z, 0-9)
     * 
     * @return string 32-character random unique AppKey
     */
    public static function generate() {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $appKey = '';
        $length = 32;
        $charLength = strlen($characters);
        
        for ($i = 0; $i < $length; $i++) {
            $appKey .= $characters[random_int(0, $charLength - 1)];
        }
        
        return $appKey;
    }

    /**
     * Generate a 64-character hex AppKey for AES-256 encryption
     * Returns 32 random bytes encoded as hexadecimal (64 characters)
     * RECOMMENDED: Use this for modern Alankit/Eraahi API integration
     * 
     * @return string 64-character hex AppKey (32 bytes in hex format)
     */
    public static function generateForAES256() {
        return bin2hex(random_bytes(32)); // 32 bytes = 64 hex characters
    }

    /**
     * Generate a 32-character hex AppKey for AES-128 encryption
     * Returns 16 random bytes encoded as hexadecimal (32 characters)
     * 
     * @return string 32-character hex AppKey (16 bytes in hex format)
     */
    public static function generateSecure() {
        return bin2hex(random_bytes(16)); // 16 bytes = 32 hex characters
    }

    /**
     * Validate AppKey format
     * Supports multiple formats:
     * - 32 alphanumeric characters (legacy)
     * - 32 hex characters (AES-128, 16 bytes)
     * - 64 hex characters (AES-256, 32 bytes) - RECOMMENDED
     * 
     * @param string $appKey AppKey to validate
     * @return boolean True if valid format
     */
    public static function validate($appKey) {
        if (!is_string($appKey)) {
            return false;
        }
        
        $length = strlen($appKey);
        
        // Legacy: 32 alphanumeric characters
        if ($length === 32 && ctype_alnum($appKey)) {
            return true;
        }
        
        // Hex formats: 32 or 64 hex characters
        if (($length === 32 || $length === 64) && ctype_xdigit($appKey)) {
            return true;
        }
        
        return false;
    }

    /**
     * Get the byte length of an AppKey
     * 
     * @param string $appKey AppKey to check
     * @return int Number of bytes, or false if invalid
     */
    public static function getByteLength($appKey) {
        $length = strlen($appKey);
        
        // Legacy alphanumeric
        if ($length === 32 && ctype_alnum($appKey)) {
            return 32; // Alphanumeric, not exact bytes, but treated as 32
        }
        
        // Hex: divide by 2
        if (ctype_xdigit($appKey)) {
            return $length / 2;
        }
        
        return false;
    }
}

/**
 * Usage Examples:
 * 
 * // Generate AES-256 AppKey (RECOMMENDED - 64 hex characters)
 * $appKey = AppKeyGenerator::generateForAES256();
 * echo $appKey; // Output: 03c9e7f791d7902bd393a73786b96087f5a2c6089434de7faac5946ca9b87cdb
 * 
 * // Generate AES-128 AppKey (32 hex characters)
 * $appKey = AppKeyGenerator::generateSecure();
 * echo $appKey; // Output: 03c9e7f791d7902bd393a73786b96087
 * 
 * // Generate legacy AppKey (32 alphanumeric characters)
 * $appKey = AppKeyGenerator::generate();
 * echo $appKey; // Output: aB1cD2eF3gH4iJ5kL6mN7oP8qR9sTuVwX
 * 
 * // Validate AppKey
 * if (AppKeyGenerator::validate('03c9e7f791d7902bd393a73786b96087f5a2c6089434de7faac5946ca9b87cdb')) {
 *     echo "Valid AES-256 AppKey (64 hex chars, 32 bytes)";
 * }
 * 
 * // Check byte length
 * $bytes = AppKeyGenerator::getByteLength('03c9e7f791d7902bd393a73786b96087f5a2c6089434de7faac5946ca9b87cdb');
 * echo "Bytes: " . $bytes; // Output: 32
 */
?>
