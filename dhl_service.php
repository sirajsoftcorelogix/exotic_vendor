<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';

/**
 * DHL Express MyDHL API (REST) client.
 *
 * @see https://developer.dhl.com/api-reference/dhl-express-mydhl-api
 */
class DhlService
{
    private array $config;

    public function __construct(?array $config = null)
    {
        $defaults = [
            'account_number' => '',
            'api_key' => '',
            'api_secret' => '',
            'rating_currency' => 'INR',
            'default_global_product_code' => '',
            'default_local_product_code' => '',
            'api_base_url' => 'https://express.api.dhl.com/mydhlapi/test',
        ];

        if (!is_array($config)) {
            $this->config = $defaults;
            return;
        }

        $urls = resolveCourierCredentialUrls($config);
        $this->config = array_merge($defaults, [
            'account_number' => (string) ($config['account_number'] ?? $defaults['account_number']),
            'api_key' => (string) ($config['api_key'] ?? $defaults['api_key']),
            'api_secret' => (string) ($config['api_secret'] ?? $defaults['api_secret']),
            'rating_currency' => strtoupper((string) ($config['rating_currency'] ?? $defaults['rating_currency'])),
            'default_global_product_code' => (string) ($config['default_global_product_code'] ?? ''),
            'default_local_product_code' => (string) ($config['default_local_product_code'] ?? ''),
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

    /** @return array<string, mixed> */
    public function getShipperDetails(): array
    {
        $s = $this->config['shipper'] ?? [];
        return [
            'postalAddress' => [
                'postalCode' => (string) ($s['postcode'] ?? ''),
                'cityName' => (string) ($s['city'] ?? ''),
                'countryCode' => (string) ($s['country_code'] ?? 'IN'),
                'addressLine1' => (string) ($s['line1'] ?? ''),
                'addressLine2' => (string) ($s['line2'] ?? ''),
                'provinceCode' => (string) ($s['state'] ?? ''),
            ],
            'contactInformation' => [
                'companyName' => (string) ($s['company_name'] ?? ''),
                'fullName' => (string) ($s['full_name'] ?? ''),
                'phone' => (string) ($s['phone'] ?? ''),
                'email' => (string) ($s['email'] ?? ''),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $payload MyDHL rates request body
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function getRates(array $payload): array
    {
        return $this->request('POST', '/rates', $payload);
    }

    /**
     * @param array<string, mixed> $payload MyDHL shipment request body
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function createShipment(array $payload): array
    {
        return $this->request('POST', '/shipments', $payload);
    }

    /**
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function trackShipment(string $trackingNumber): array
    {
        $trackingNumber = trim($trackingNumber);
        if ($trackingNumber === '') {
            return ['success' => false, 'error' => 'Tracking number is required.'];
        }

        return $this->request(
            'GET',
            '/shipments/' . rawurlencode($trackingNumber) . '/tracking',
            null
        );
    }

    /**
     * @param array<string, mixed> $payload MyDHL pickup request body
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    public function createPickup(array $payload): array
    {
        return $this->request('POST', '/pickups', $payload);
    }

    /**
     * Build a minimal international rates payload (extend in DhlAdapter).
     *
     * @param array<string, mixed> $receiver postcode, city, country_code
     * @param array<int, array<string, mixed>> $packages weight + dimensions
     * @return array<string, mixed>
     */
    public function buildRatesPayload(array $receiver, array $packages, ?string $plannedShippingAt = null): array
    {
        $accountNumber = trim((string) ($this->config['account_number'] ?? ''));
        $currency = trim((string) ($this->config['rating_currency'] ?? 'INR'));
        $plannedShippingAt = $plannedShippingAt ?: gmdate('Y-m-d\TH:i:s \G\M\TP');

        $payload = [
            'customerDetails' => [
                'shipperDetails' => $this->getShipperDetails(),
                'receiverDetails' => [
                    'postalAddress' => [
                        'postalCode' => (string) ($receiver['postcode'] ?? ''),
                        'cityName' => (string) ($receiver['city'] ?? ''),
                        'countryCode' => (string) ($receiver['country_code'] ?? ''),
                    ],
                ],
            ],
            'plannedShippingDateAndTime' => $plannedShippingAt,
            'unitOfMeasurement' => 'metric',
            'isCustomsDeclarable' => true,
            'packages' => $packages,
        ];

        if ($accountNumber !== '') {
            $payload['accounts'] = [
                ['typeCode' => 'shipper', 'number' => $accountNumber],
            ];
        }

        if ($currency !== '') {
            $payload['monetaryAmount'] = [
                ['typeCode' => 'declaredValue', 'value' => 1, 'currency' => $currency],
            ];
        }

        return $payload;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    private function request(string $method, string $path, ?array $payload = null): array
    {
        $apiKey = trim((string) ($this->config['api_key'] ?? ''));
        $apiSecret = trim((string) ($this->config['api_secret'] ?? ''));
        if ($apiKey === '' || $apiSecret === '') {
            return ['success' => false, 'error' => 'DHL API key and secret are required.'];
        }

        $url = rtrim((string) $this->config['api_base_url'], '/') . $path;
        $body = $payload !== null ? json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        if ($payload !== null && $body === false) {
            return ['success' => false, 'error' => 'Could not encode request JSON.'];
        }

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Message-Reference: ' . $this->messageReference(),
            'Message-Reference-Date: ' . gmdate('D, d M Y H:i:s') . ' GMT',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_USERPWD => $apiKey . ':' . $apiSecret,
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
            return ['success' => false, 'error' => $curlError ?: 'DHL request failed.', 'http_code' => $httpCode];
        }

        $decoded = json_decode($raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300;

        if (!$ok) {
            $message = is_array($decoded)
                ? ($decoded['detail'] ?? $decoded['title'] ?? $decoded['message'] ?? $raw)
                : $raw;
            return [
                'success' => false,
                'error' => is_string($message) ? $message : 'DHL API error.',
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

    private function messageReference(): string
    {
        return substr(str_replace('-', '', (string) bin2hex(random_bytes(16))), 0, 32);
    }
}
