<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';

/**
 * Blue Dart Express API client (skeleton).
 *
 * @see https://www.bluedart.com/web/guest/home
 */
class BlueDartService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $defaults = [
            'login_id' => '',
            'licence_key' => '',
            'customer_code' => '',
            'origin_area' => '',
            'customer_pincode' => '110052',
            'rating_currency' => 'INR',
            'default_product_code' => '',
            'api_base_url' => 'https://api.bluedart.com/servlet/RoutingServlet',
        ];

        if (!is_array($config)) {
            $this->config = $defaults;
            return;
        }

        $urls = resolveCourierCredentialUrls($config);
        $this->config = array_merge($defaults, [
            'login_id' => (string) ($config['login_id'] ?? ''),
            'licence_key' => (string) ($config['licence_key'] ?? ''),
            'customer_code' => (string) ($config['customer_code'] ?? ''),
            'origin_area' => (string) ($config['origin_area'] ?? ''),
            'customer_pincode' => (string) ($config['customer_pincode'] ?? $defaults['customer_pincode']),
            'rating_currency' => strtoupper((string) ($config['rating_currency'] ?? $defaults['rating_currency'])),
            'default_product_code' => (string) ($config['default_product_code'] ?? ''),
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
     * Pincode serviceability / transit check (implement when API docs are confirmed).
     *
     * @param array<string, mixed> $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function checkServiceability(array $payload): array
    {
        return $this->request('POST', '/serviceability', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function getRates(array $payload): array
    {
        return $this->request('POST', '/rates', $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function createShipment(array $payload): array
    {
        return $this->request('POST', '/shipments', $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        $loginId = trim((string) ($this->config['login_id'] ?? ''));
        $licenceKey = trim((string) ($this->config['licence_key'] ?? ''));
        if ($loginId === '' || $licenceKey === '') {
            return ['success' => false, 'error' => 'Blue Dart login_id and licence_key are required.'];
        }

        $url = rtrim((string) $this->config['api_base_url'], '/') . $path;
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
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
            return ['success' => false, 'error' => $curlError ?: 'Blue Dart request failed.', 'http_code' => $httpCode];
        }

        $decoded = json_decode($raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300;

        if (!$ok) {
            $message = is_array($decoded)
                ? ($decoded['message'] ?? $decoded['error'] ?? $raw)
                : $raw;
            return [
                'success' => false,
                'error' => is_string($message) ? $message : 'Blue Dart API error.',
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
