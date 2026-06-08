<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/models/courier/CourierAccount.php';

/**
 * Shiprocket API client — credentials from Courier accounts (partner_code = shiprocket).
 */
class ShiprocketService
{
    private $conn;
    /** @var array<string, mixed>|null */
    private ?array $accountRow = null;
    /** @var array<string, mixed>|null */
    private ?array $credentials = null;
    private string $baseUrl = 'https://apiv2.shiprocket.in';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAccountId(): int
    {
        $this->ensureAccount();

        return (int) ($this->accountRow['id'] ?? 0);
    }

    public function getDefaultPickupLocation(): string
    {
        $this->ensureAccount();

        return trim((string) ($this->credentials['pickup_location'] ?? ''));
    }

    public function apiUrl(string $path): string
    {
        $this->ensureAccount();
        $path = '/' . ltrim($path, '/');

        return rtrim($this->baseUrl, '/') . $path;
    }

    public function getToken(): string
    {
        $this->ensureAccount();
        if ($this->credentials === null) {
            return '';
        }

        $token = trim((string) ($this->credentials['token'] ?? ''));
        if ($token !== '') {
            return $token;
        }

        return $this->loginAndPersistToken();
    }

    private function ensureAccount(): void
    {
        if ($this->credentials !== null) {
            return;
        }

        $accountModel = new CourierAccount($this->conn);
        $accounts = $accountModel->listActiveAccountsByPartnerCode('shiprocket');
        if (!$accounts) {
            error_log('ShiprocketService: no active Shiprocket account in Courier accounts.');
            $this->credentials = [];
            return;
        }

        $this->accountRow = $accounts[0];
        $accountId = (int) ($this->accountRow['id'] ?? 0);
        $this->credentials = $accountId > 0 ? $accountModel->getCredentialsJson($accountId) : [];

        $urlInfo = resolveCourierCredentialUrls($this->credentials, 'shiprocket');
        $apiBase = trim((string) ($urlInfo['api_base_url'] ?? ''));
        if ($apiBase === '') {
            $apiBase = 'https://apiv2.shiprocket.in';
        }
        $this->baseUrl = rtrim($apiBase, '/');
    }

    private function loginAndPersistToken(): string
    {
        if ($this->credentials === null) {
            return '';
        }

        $email = trim((string) ($this->credentials['email'] ?? ''));
        $password = (string) ($this->credentials['password'] ?? '');
        if ($email === '' || $password === '') {
            error_log('ShiprocketService: token missing and email/password not set in Courier accounts.');
            return '';
        }

        $url = $this->apiUrl('/v1/external/auth/login');
        $payload = json_encode([
            'email' => $email,
            'password' => $password,
        ]);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
        ]);
        $responseRaw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = is_string($responseRaw) ? json_decode($responseRaw, true) : null;
        $token = is_array($decoded) ? trim((string) ($decoded['token'] ?? '')) : '';
        if ($token === '') {
            error_log('ShiprocketService: login failed (HTTP ' . $httpCode . ').');
            return '';
        }

        $accountId = $this->getAccountId();
        if ($accountId > 0) {
            $creds = $this->credentials;
            $creds['token'] = $token;
            $accountModel = new CourierAccount($this->conn);
            $environment = (string) ($creds['environment'] ?? 'production');
            $accountModel->saveCredentialsJson(
                $accountId,
                json_encode($creds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                $environment
            );
            $this->credentials = $creds;
        }

        return $token;
    }
}
