<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/helpers/courier/shiprocket_token.php';
require_once __DIR__ . '/models/courier/CourierAccount.php';

/**
 * Shiprocket API client — primary credentials from Courier accounts (partner_code = shiprocket).
 * Falls back to legacy shiprocket_api_tokens / vendor API when needed.
 */
class ShiprocketService
{
    private $conn;
    /** @var array<string, mixed>|null */
    private ?array $accountRow = null;
    /** @var array<string, mixed>|null */
    private ?array $credentials = null;
    private bool $accountLoaded = false;
    private string $baseUrl = 'https://apiv2.shiprocket.in';
    private string $lastAuthError = '';

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getLastAuthError(): string
    {
        return $this->lastAuthError;
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
        $this->lastAuthError = '';

        $token = $this->resolveCourierAccountToken();
        if ($token !== '') {
            return $token;
        }

        $token = $this->resolveLegacyToken();
        if ($token !== '') {
            $this->persistTokenToCourierAccount($token);

            return $token;
        }

        if ($this->lastAuthError === '') {
            if ($this->getAccountId() > 0) {
                $this->lastAuthError = 'Shiprocket account found but token is empty. Paste token into Courier accounts credentials JSON and save.';
            } else {
                $this->lastAuthError = 'No active Shiprocket account in Courier accounts. Add partner Shiprocket with your credentials JSON.';
            }
        }

        return '';
    }

    public function handleUnauthorized(): string
    {
        $this->clearCachedToken();
        $this->lastAuthError = '';

        $loginToken = $this->loginViaCourierCredentials();
        if ($loginToken !== '') {
            return $loginToken;
        }

        $legacyToken = $this->refreshLegacyTokenFromVendorApi();
        if ($legacyToken !== '') {
            $this->persistTokenToCourierAccount($legacyToken);

            return $legacyToken;
        }

        if ($this->lastAuthError === '') {
            $this->lastAuthError = 'Shiprocket token expired or invalid. Update token in Courier accounts (or add API email/password).';
        }

        return '';
    }

    private function ensureAccount(): void
    {
        if ($this->accountLoaded) {
            return;
        }
        $this->accountLoaded = true;

        $accountModel = new CourierAccount($this->conn);
        $accounts = $accountModel->listActiveAccountsByPartnerCode('shiprocket');
        if (!$accounts) {
            $this->accountRow = null;
            $this->credentials = [];
            return;
        }

        $selectedAccount = null;
        $selectedCredentials = [];

        foreach ($accounts as $account) {
            $accountId = (int) ($account['id'] ?? 0);
            if ($accountId <= 0) {
                continue;
            }
            $creds = $accountModel->getCredentialsJson($accountId);
            if (extractShiprocketToken($creds) !== '') {
                $selectedAccount = $account;
                $selectedCredentials = $creds;
                break;
            }
        }

        if ($selectedAccount === null) {
            $selectedAccount = $accounts[0];
            $selectedCredentials = $accountModel->getCredentialsJson((int) ($selectedAccount['id'] ?? 0));
        }

        $this->accountRow = $selectedAccount;
        $this->credentials = $selectedCredentials;

        $urlInfo = resolveCourierCredentialUrls($this->credentials, 'shiprocket');
        $apiBase = trim((string) ($urlInfo['api_base_url'] ?? ''));
        if ($apiBase === '') {
            $apiBase = trim((string) ($this->credentials['production_api_base_url'] ?? ''));
        }
        if ($apiBase === '') {
            $apiBase = trim((string) ($this->credentials['sandbox_api_base_url'] ?? ''));
        }
        if ($apiBase === '') {
            $apiBase = 'https://apiv2.shiprocket.in';
        }
        $this->baseUrl = rtrim($apiBase, '/');
    }

    private function resolveCourierAccountToken(): string
    {
        $this->ensureAccount();
        if (!$this->accountRow) {
            return '';
        }

        $token = extractShiprocketToken(is_array($this->credentials) ? $this->credentials : []);
        if ($token !== '') {
            return $token;
        }

        return $this->loginViaCourierCredentials();
    }

    private function loginViaCourierCredentials(): string
    {
        $this->ensureAccount();
        if (!is_array($this->credentials)) {
            return '';
        }

        $email = trim((string) ($this->credentials['email'] ?? ''));
        $password = (string) ($this->credentials['password'] ?? '');
        if ($email === '' || $password === '') {
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
        $token = is_array($decoded) ? normalizeShiprocketBearerToken((string) ($decoded['token'] ?? '')) : '';
        if ($token === '') {
            $this->lastAuthError = 'Shiprocket login failed (HTTP ' . $httpCode . '). Check API user email/password in Courier accounts.';
            return '';
        }

        $this->persistTokenToCourierAccount($token);

        return $token;
    }

    private function persistTokenToCourierAccount(string $token): void
    {
        $accountId = $this->getAccountId();
        if ($accountId <= 0 || !is_array($this->credentials)) {
            return;
        }

        $this->credentials['token'] = $token;
        $accountModel = new CourierAccount($this->conn);
        $environment = (string) ($this->credentials['environment'] ?? 'production');
        $accountModel->saveCredentialsJson(
            $accountId,
            json_encode($this->credentials, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            $environment
        );
    }

    private function clearCachedToken(): void
    {
        $this->ensureAccount();
        if (is_array($this->credentials)) {
            unset($this->credentials['token']);
        }
    }

    private function resolveLegacyToken(): string
    {
        $sql = 'SELECT token, expires_at FROM shiprocket_api_tokens ORDER BY id DESC LIMIT 1';
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $token = normalizeShiprocketBearerToken((string) ($row['token'] ?? ''));
            $expiresAt = strtotime((string) ($row['expires_at'] ?? ''));
            if ($token !== '' && $expiresAt > time()) {
                return $token;
            }
        }

        return $this->refreshLegacyTokenFromVendorApi();
    }

    private function refreshLegacyTokenFromVendorApi(): string
    {
        $url = 'https://www.exoticindia.com/vendor-api/order/shiprocket-token';
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded',
        ];
        $postData = ['makeRequestOf' => 'vendors-orderjson'];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = is_string($response) ? json_decode($response, true) : null;
        $token = is_array($data) ? normalizeShiprocketBearerToken((string) ($data['shiprocket_token'] ?? '')) : '';
        if ($token === '') {
            if ($this->lastAuthError === '') {
                $this->lastAuthError = 'Legacy Shiprocket token refresh failed (HTTP ' . $httpCode . ').';
            }
            return '';
        }

        $expireAt = !empty($data['shiprocket_expiry'])
            ? date('Y-m-d H:i:s', (int) $data['shiprocket_expiry'])
            : date('Y-m-d H:i:s', time() + 3600);

        $escapedToken = $this->conn->real_escape_string($token);
        $escapedExpireAt = $this->conn->real_escape_string($expireAt);
        $this->conn->query(
            "UPDATE shiprocket_api_tokens
             SET token = '{$escapedToken}',
                 expires_at = '{$escapedExpireAt}',
                 updated_at = NOW()
             ORDER BY id DESC LIMIT 1"
        );

        return $token;
    }
}
