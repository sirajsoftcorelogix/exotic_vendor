<?php

namespace Integrations\Alankit\Clients;

use Integrations\Alankit\Config\AlankitConfig;

/**
 * Low-level API Gateway Client for Alankit (Eraahi Gateway).
 * Handles RSA authentication, AES-256-CTR SEK decryption,
 * AES-256-ECB payload encryption/decryption, and cURL requests.
 */
class AlankitApiClient
{
    private AlankitConfig $config;

    public const AUTH_ENDPOINT = '/eInvoiceGateway/eiauth/v1.03/auth';
    public const IRN_GENERATE_ENDPOINT = '/eInvoiceGateway/eirn/v1.03/eInvoicing/generate';
    public const EWAYBILL_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill';
    public const EWAYBILL_CANCEL_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill/cancel';
    public const EWAYBILL_GET_ENDPOINT = '/eInvoiceGateway/eiewb/v1.03/ewaybill/';

    public function __construct(AlankitConfig $config)
    {
        $this->config = $config;
    }

    public function getConfig(): AlankitConfig
    {
        return $this->config;
    }

    /**
     * Build encrypted authentication payload using RSA public key.
     */
    public function createAuthPayload(): ?string
    {
        $authData = [
            'UserName' => $this->config->getUsername(),
            'Password' => $this->config->getPassword(),
            'AppKey' => $this->config->getAppKey(),
            'ForceRefreshAccessToken' => $this->config->getForceRefreshAccessToken(),
        ];

        // Check if helper RsaEncryptor is loaded
        if (class_exists('RsaEncryptor')) {
            return \RsaEncryptor::securePayload($authData, $this->config->getPublicKeyPath());
        }

        $jsonStr = json_encode($authData, JSON_UNESCAPED_SLASHES);
        if (!$jsonStr) {
            return null;
        }

        $b64Str = base64_encode($jsonStr);
        $publicKeyPath = $this->config->getPublicKeyPath();

        if (!file_exists($publicKeyPath)) {
            error_log("AlankitApiClient: Public key file not found: {$publicKeyPath}");
            return null;
        }

        $keyContent = trim((string) file_get_contents($publicKeyPath));
        if ($keyContent === '') {
            return null;
        }

        if (strpos($keyContent, '-----BEGIN') === false) {
            $pem = "-----BEGIN PUBLIC KEY-----\n" . wordwrap($keyContent, 64, "\n", true) . "\n-----END PUBLIC KEY-----";
        } else {
            $pem = $keyContent;
        }

        $pubKey = openssl_pkey_get_public($pem);
        if (!$pubKey) {
            error_log('AlankitApiClient: Failed to parse OpenSSL public key.');
            return null;
        }

        $encrypted = '';
        $success = openssl_public_encrypt($b64Str, $encrypted, $pubKey, OPENSSL_PKCS1_PADDING);
        if (!$success) {
            error_log('AlankitApiClient: RSA encryption failed: ' . openssl_error_string());
            return null;
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt SEK received from Auth endpoint using AppKey.
     * Uses AES-256-CTR mode with SHA-256 hash of AppKey and 16-byte zero IV.
     */
    public function decryptSek(string $encryptedSek): ?string
    {
        if ($encryptedSek === '') {
            return null;
        }

        $appKey = $this->config->getAppKey();
        $aesKey = hash('sha256', $appKey, true); // 32 bytes
        $iv = str_repeat("\0", 16); // 16-byte zero IV

        $cipherData = base64_decode($encryptedSek);
        if ($cipherData === false) {
            return null;
        }

        $decryptedSek = openssl_decrypt($cipherData, 'AES-256-CTR', $aesKey, OPENSSL_RAW_DATA, $iv);
        if ($decryptedSek === false || $decryptedSek === '') {
            error_log('AlankitApiClient: SEK decryption failed: ' . openssl_error_string());
            return null;
        }

        return $decryptedSek;
    }

    /**
     * Encrypt JSON payload using Decrypted SEK in AES-256-ECB mode.
     */
    public function encryptPayload(array $payload, string $decryptedSekB64): ?string
    {
        $jsonStr = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if (!$jsonStr) {
            return null;
        }

        $b64Payload = base64_encode($jsonStr);
        $sekBytes = base64_decode($decryptedSekB64);

        if (!$sekBytes) {
            error_log('AlankitApiClient: Invalid SEK bytes.');
            return null;
        }

        $encrypted = openssl_encrypt($b64Payload, 'AES-256-ECB', $sekBytes, OPENSSL_RAW_DATA);
        if (!$encrypted) {
            error_log('AlankitApiClient: Payload encryption failed: ' . openssl_error_string());
            return null;
        }

        return base64_encode($encrypted);
    }

    /**
     * Decrypt response Data payload using Decrypted SEK in AES-256-ECB mode.
     */
    public function decryptPayload(string $encryptedDataB64, string $decryptedSekB64): ?array
    {
        $cipherBytes = base64_decode($encryptedDataB64);
        $sekBytes = base64_decode($decryptedSekB64);

        if (!$cipherBytes || !$sekBytes) {
            return null;
        }

        $decryptedB64 = openssl_decrypt($cipherBytes, 'AES-256-ECB', $sekBytes, OPENSSL_RAW_DATA);
        if (!$decryptedB64) {
            error_log('AlankitApiClient: Payload decryption failed: ' . openssl_error_string());
            return null;
        }

        $jsonStr = base64_decode($decryptedB64);
        if (!$jsonStr) {
            return null;
        }

        $decoded = json_decode($jsonStr, true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Execute HTTP request to Alankit Gateway.
     *
     * @param string $endpoint Constant or relative endpoint path
     * @param array<string, mixed> $bodyData
     * @param string|null $accessToken
     * @return array<string, mixed>|null
     */
    public function sendRequest(string $endpoint, array $bodyData, ?string $accessToken = null): ?array
    {
        $url = $this->config->getBaseUrl() . $endpoint;

        $headers = [
            'Content-Type: application/json',
            'Gstin: ' . $this->config->getGstin(),
            'user_name: ' . $this->config->getUsername(),
            'Ocp-Apim-Subscription-Key: ' . $this->config->getSubscriptionKey(),
        ];

        if ($accessToken !== null && $accessToken !== '') {
            $headers[] = 'AuthToken: ' . $accessToken;
        }

        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }

        $postFields = json_encode($bodyData, JSON_UNESCAPED_SLASHES);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 0,
        ]);

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '' || !is_string($responseBody) || $responseBody === '') {
            error_log("AlankitApiClient HTTP error for {$url}: " . ($curlError ?: "HTTP {$httpCode} empty body"));
            return null;
        }

        $decoded = json_decode($responseBody, true);
        return is_array($decoded) ? $decoded : ['raw' => $responseBody, 'http_code' => $httpCode];
    }
}
