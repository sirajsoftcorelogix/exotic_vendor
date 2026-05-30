<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';

/**
 * FedEx REST API client (skeleton).
 *
 * @see https://developer.fedex.com/
 */
class FedExService
{
    private array $config;
    private ?string $accessToken = null;

    public function __construct(?array $config = null)
    {
        $defaults = [
            'client_id' => '',
            'client_secret' => '',
            'account_number' => '',
            'rating_currency' => 'USD',
            'default_service_type' => '',
            'api_base_url' => 'https://apis-sandbox.fedex.com',
            'oauth_path' => '/oauth/token',
        ];

        if (!is_array($config)) {
            $this->config = $defaults;
            return;
        }

        $urls = resolveCourierCredentialUrls($config);
        $this->config = array_merge($defaults, [
            'client_id' => (string) ($config['client_id'] ?? ''),
            'client_secret' => (string) ($config['client_secret'] ?? ''),
            'account_number' => (string) ($config['account_number'] ?? ''),
            'rating_currency' => strtoupper((string) ($config['rating_currency'] ?? $defaults['rating_currency'])),
            'default_service_type' => (string) ($config['default_service_type'] ?? ''),
            'api_base_url' => $urls['api_base_url'] !== ''
                ? rtrim($urls['api_base_url'], '/')
                : $defaults['api_base_url'],
            'shipper' => is_array($config['shipper'] ?? null) ? $config['shipper'] : [],
        ]);
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return array{success:bool,access_token?:string,error?:string,http_code?:int}
     */
    public function authenticate(): array
    {
        $clientId = trim((string) ($this->config['client_id'] ?? ''));
        $clientSecret = trim((string) ($this->config['client_secret'] ?? ''));
        if ($clientId === '' || $clientSecret === '') {
            return ['success' => false, 'error' => 'FedEx client_id and client_secret are required.'];
        }

        $url = rtrim((string) $this->config['api_base_url'], '/') . (string) ($this->config['oauth_path'] ?? '/oauth/token');
        $body = http_build_query([
            'grant_type' => 'client_credentials',
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_TIMEOUT => 30,
        ]);

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'error' => 'FedEx OAuth request failed.', 'http_code' => $httpCode];
        }

        $decoded = json_decode($raw, true);
        if ($httpCode < 200 || $httpCode >= 300 || empty($decoded['access_token'])) {
            return [
                'success' => false,
                'error' => is_array($decoded) ? ($decoded['error_description'] ?? $decoded['errors'][0]['message'] ?? 'OAuth failed') : 'OAuth failed',
                'http_code' => $httpCode,
            ];
        }

        $this->accessToken = (string) $decoded['access_token'];
        return ['success' => true, 'access_token' => $this->accessToken, 'http_code' => $httpCode];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function getRates(array $payload): array
    {
        return $this->request('POST', '/rate/v1/rates/quotes', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function createShipment(array $payload): array
    {
        return $this->request('POST', '/ship/v1/shipments', $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        if ($this->accessToken === null) {
            $auth = $this->authenticate();
            if (empty($auth['success'])) {
                return ['success' => false, 'error' => (string) ($auth['error'] ?? 'FedEx authentication failed.')];
            }
        }

        $url = rtrim((string) $this->config['api_base_url'], '/') . $path;
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->accessToken,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return ['success' => false, 'error' => $curlError ?: 'FedEx request failed.', 'http_code' => $httpCode];
        }

        $decoded = json_decode($raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300;

        if (!$ok) {
            $message = is_array($decoded)
                ? ($decoded['errors'][0]['message'] ?? $decoded['message'] ?? $raw)
                : $raw;
            return [
                'success' => false,
                'error' => is_string($message) ? $message : 'FedEx API error.',
                'http_code' => $httpCode,
                'data' => $decoded,
                'raw' => $raw,
            ];
        }

        return [
            'success' => true,
            'http_code' => $httpCode,
            'data' => $decoded,
            'raw' => $raw,
        ];
    }
}
