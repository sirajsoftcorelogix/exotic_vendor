<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/helpers/courier/bluedart_rate_helpers.php';

/**
 * Blue Dart Express API gateway client (DHL eCommerce India REST APIs).
 *
 * Rates: Location Finder (serviceability) + Transit Time (ETD). Price is supplied separately via Shiprocket.
 *
 * @see https://developer.dhl.com/api-reference/blue-dart-location-finder
 * @see https://developer.dhl.com/api-reference/blue-dart-transit-time
 */
class BlueDartService
{
    private array $config;
    private ?string $jwtToken = null;

    public function __construct(?array $config = null)
    {
        $defaults = [
            'login_id' => '',
            'shipment_licence_key' => '',
            'tracking_licence_key' => '',
            'licence_key' => '',
            'customer_code' => '',
            'origin_area' => '',
            'customer_pincode' => '110052',
            'rating_currency' => 'INR',
            'default_product_code' => '',
            'default_sub_product_code' => 'P',
            'consumer_key' => '',
            'consumer_secret' => '',
            'jwt_token' => '',
            'api_gateway_base_url' => 'https://apigateway.bluedart.com',
            'token_api_path' => '/in/transportation/token/v1/generate',
            'rate_products' => [],
            'rate_estimate' => [],
            'rate_per_kg_inr' => 0,
            'rate_minimum_inr' => 0,
        ];

        if (!is_array($config)) {
            $this->config = $defaults;
            return;
        }

        resolveCourierCredentialUrls($config, 'bluedart');
        $shipmentLicence = trim((string) (
            $config['shipment_licence_key']
            ?? $config['licence_key']
            ?? ''
        ));

        $gatewayBase = trim((string) ($config['api_gateway_base_url'] ?? ''));
        if ($gatewayBase === '') {
            $gatewayBase = $defaults['api_gateway_base_url'];
        }

        $this->config = array_merge($defaults, [
            'login_id' => (string) ($config['login_id'] ?? ''),
            'shipment_licence_key' => $shipmentLicence,
            'tracking_licence_key' => trim((string) (
                $config['tracking_licence_key']
                ?? $config['tracking_token']
                ?? $shipmentLicence
            )),
            'licence_key' => $shipmentLicence,
            'customer_code' => (string) ($config['customer_code'] ?? ''),
            'origin_area' => (string) ($config['origin_area'] ?? ''),
            'customer_pincode' => (string) ($config['customer_pincode'] ?? $defaults['customer_pincode']),
            'rating_currency' => strtoupper((string) ($config['rating_currency'] ?? $defaults['rating_currency'])),
            'default_product_code' => (string) ($config['default_product_code'] ?? ''),
            'default_sub_product_code' => (string) ($config['default_sub_product_code'] ?? 'P'),
            'consumer_key' => trim((string) ($config['consumer_key'] ?? $config['ClientID'] ?? '')),
            'consumer_secret' => trim((string) ($config['consumer_secret'] ?? $config['clientSecret'] ?? '')),
            'jwt_token' => trim((string) ($config['jwt_token'] ?? $config['JWTToken'] ?? '')),
            'api_gateway_base_url' => rtrim($gatewayBase, '/'),
            'token_api_path' => trim((string) ($config['token_api_path'] ?? $defaults['token_api_path'])),
            'rate_products' => is_array($config['rate_products'] ?? null) ? $config['rate_products'] : [],
            'rate_estimate' => is_array($config['rate_estimate'] ?? null) ? $config['rate_estimate'] : [],
            'rate_per_kg_inr' => (float) ($config['rate_per_kg_inr'] ?? 0),
            'rate_minimum_inr' => (float) ($config['rate_minimum_inr'] ?? 0),
            'shipper' => is_array($config['shipper'] ?? null) ? $config['shipper'] : [],
        ]);
    }

    /** @return array<string, mixed> */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Build rate quotes for a domestic lane.
     *
     * @param array<string, mixed> $params
     * @return array{success:bool,quotes?:list<array<string,mixed>>,error?:string,debug?:array<string,mixed>}
     */
    public function estimateRates(array $params): array
    {
        $loginId = trim((string) ($this->config['login_id'] ?? ''));
        $licenceKey = trim((string) ($this->config['shipment_licence_key'] ?? ''));
        if ($loginId === '' || $licenceKey === '') {
            return [
                'success' => false,
                'error' => 'Blue Dart login_id and shipment_licence_key are required in Courier accounts.',
            ];
        }

        $originPin = preg_replace('/\D/', '', (string) ($params['origin_pin'] ?? ''));
        $destPin = preg_replace('/\D/', '', (string) ($params['dest_pin'] ?? ''));
        if (strlen($originPin) !== 6 || strlen($destPin) !== 6) {
            return [
                'success' => false,
                'error' => 'Valid 6-digit origin and destination pincodes are required for Blue Dart rates.',
            ];
        }

        $weightKg = (float) ($params['billable_weight_kg'] ?? $params['weight_kg'] ?? 0);
        if ($weightKg <= 0) {
            return [
                'success' => false,
                'error' => 'Box weight is required for Blue Dart rates.',
            ];
        }

        $auth = $this->authenticate();
        if (!$auth['success']) {
            return $auth;
        }

        $products = $this->resolveRateProducts();
        if ($products === []) {
            return [
                'success' => false,
                'error' => 'No Blue Dart rate_products configured. Add rate_products[] in Courier accounts credentials JSON.',
            ];
        }

        $quotes = [];
        $debug = ['products' => [], 'auth' => ['token_obtained' => true]];

        foreach ($products as $product) {
            $productCode = strtoupper(trim((string) ($product['product_code'] ?? '')));
            $subProductCode = strtoupper(trim((string) ($product['sub_product_code'] ?? 'P')));
            if ($productCode === '') {
                continue;
            }

            $packType = strtoupper(trim((string) ($product['pack_type'] ?? 'L')));
            $feature = strtoupper(trim((string) ($product['feature'] ?? 'R')));
            $label = trim((string) ($product['label'] ?? $product['name'] ?? ('Blue Dart ' . $productCode . '/' . $subProductCode)));

            $serviceResp = $this->getServicesForPincodeAndProduct($destPin, $productCode, $subProductCode, $packType, $feature);
            $debug['products'][$productCode . '_' . $subProductCode] = [
                'serviceability' => $serviceResp,
            ];

            if (!$serviceResp['success'] || !$this->isServiceabilityOk($serviceResp['data'] ?? null)) {
                continue;
            }

            $transitResp = $this->getDomesticTransitTime($originPin, $destPin, $productCode, $subProductCode);
            $debug['products'][$productCode . '_' . $subProductCode]['transit'] = $transitResp;

            $etd = $this->parseTransitEtd($transitResp['data'] ?? null);

            $quotes[] = [
                'product_code' => $productCode,
                'sub_product_code' => $subProductCode,
                'pack_type' => $packType,
                'feature' => $feature,
                'label' => $label,
                'price' => null,
                'currency' => (string) ($this->config['rating_currency'] ?? 'INR'),
                'etd' => $etd,
                'origin_pin' => $originPin,
                'dest_pin' => $destPin,
                'billable_weight_kg' => $weightKg,
                'etd_source' => $etd !== 'N/A' ? 'bluedart' : '',
                'serviceable' => true,
                'serviceability' => $serviceResp['data'] ?? null,
                'transit' => $transitResp['data'] ?? null,
            ];
        }

        if ($quotes === []) {
            return [
                'success' => false,
                'error' => 'Blue Dart returned no serviceable products for this pincode pair.',
                'debug' => $debug,
            ];
        }

        return [
            'success' => true,
            'quotes' => $quotes,
            'debug' => $debug,
        ];
    }

    /** @return array{success:bool,token?:string,error?:string,debug?:mixed} */
    public function authenticate(bool $forceRefresh = false): array
    {
        if (!$forceRefresh && $this->jwtToken !== null && $this->jwtToken !== '') {
            return ['success' => true, 'token' => $this->jwtToken];
        }

        $cachedToken = trim((string) ($this->config['jwt_token'] ?? ''));
        if (!$forceRefresh && $cachedToken !== '' && $this->looksLikeJwt($cachedToken)) {
            $this->jwtToken = $cachedToken;
            return ['success' => true, 'token' => $cachedToken];
        }

        $clientId = trim((string) ($this->config['consumer_key'] ?? ''));
        $clientSecret = trim((string) ($this->config['consumer_secret'] ?? ''));
        if ($clientId === '' || $clientSecret === '') {
            return [
                'success' => false,
                'error' => 'Blue Dart consumer_key and consumer_secret are required (DHL Developer Portal), or save a valid jwt_token in Courier accounts.',
            ];
        }

        $path = (string) ($this->config['token_api_path'] ?? '/in/transportation/token/v1/generate');
        if ($path === '') {
            $path = '/in/transportation/token/v1/generate';
        }
        if ($path[0] !== '/') {
            $path = '/' . $path;
        }

        $url = rtrim((string) $this->config['api_gateway_base_url'], '/') . $path;
        $payloads = [
            ['ClientID' => $clientId, 'clientSecret' => $clientSecret],
            ['clientID' => $clientId, 'clientSecret' => $clientSecret],
        ];

        $lastError = 'Could not obtain Blue Dart JWT token.';
        foreach ($payloads as $body) {
            $resp = $this->rawRequest('POST', $url, $body, []);
            if (!$resp['success']) {
                $lastError = (string) ($resp['error'] ?? $lastError);
                continue;
            }

            $token = $this->extractJwtToken($resp['data'] ?? null, (string) ($resp['raw'] ?? ''));
            if ($token === '') {
                $lastError = 'Blue Dart token API did not return a JWT. Verify consumer_key / consumer_secret.';
                continue;
            }

            $this->jwtToken = $token;
            return ['success' => true, 'token' => $token];
        }

        // Some gateways accept credentials via headers.
        $headerResp = $this->rawRequest('POST', $url, null, [
            'ClientID: ' . $clientId,
            'clientSecret: ' . $clientSecret,
            'Accept: application/json',
        ]);
        if (!empty($headerResp['success'])) {
            $token = $this->extractJwtToken($headerResp['data'] ?? null, (string) ($headerResp['raw'] ?? ''));
            if ($token !== '') {
                $this->jwtToken = $token;
                return ['success' => true, 'token' => $token];
            }
        }

        return ['success' => false, 'error' => $lastError];
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    public function getServicesForPincode(string $pinCode): array
    {
        return $this->gatewayPost('/in/transportation/finder/v1/GetServicesforPincode', [
            'pinCode' => $pinCode,
            'profile' => $this->buildProfile(),
        ]);
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    public function getServicesForPincodeAndProduct(
        string $pinCode,
        string $productCode,
        string $subProductCode,
        string $packType = 'L',
        string $feature = 'R'
    ): array {
        $profile = $this->buildProfile();
        $attempts = [
            [
                'path' => '/in/transportation/finder/v1/GetServicesforPincodeAndProduct',
                'body' => [
                    'pinCode' => $pinCode,
                    'pProductCode' => $productCode,
                    'pSubProductCode' => $subProductCode,
                    'profile' => $profile,
                ],
            ],
            [
                'path' => '/in/transportation/finder/v1/GetServicesforProduct',
                'body' => [
                    'pinCode' => $pinCode,
                    'ProductCode' => $productCode,
                    'SubProductCode' => $subProductCode,
                    'PackType' => $packType,
                    'Feature' => $feature,
                    'profile' => $profile,
                ],
            ],
            [
                'path' => '/in/transportation/finder/v1/GetServicesforPincodeAndProduct',
                'body' => [
                    'pinCode' => $pinCode,
                    'ProductCode' => $productCode,
                    'SubProductCode' => $subProductCode,
                    'PackType' => $packType,
                    'Feature' => $feature,
                    'profile' => $profile,
                ],
            ],
        ];

        $last = ['success' => false, 'error' => 'Serviceability check failed.'];
        foreach ($attempts as $attempt) {
            $resp = $this->gatewayPost($attempt['path'], $attempt['body']);
            $last = $resp;
            if (!empty($resp['success']) && $this->isServiceabilityOk($resp['data'] ?? null)) {
                return $resp;
            }
        }

        $pinOnly = $this->getServicesForPincode($pinCode);
        if (!empty($pinOnly['success']) && $this->isServiceabilityOk($pinOnly['data'] ?? null)) {
            return $pinOnly;
        }

        return $last;
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    public function getAllProductsAndSubProducts(): array
    {
        return $this->gatewayPost('/in/transportation/allproduct/v1/GetAllProductsAndSubProducts', [
            'profile' => $this->buildProfile(),
        ]);
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    public function getDomesticTransitTime(
        string $originPin,
        string $destPin,
        string $productCode,
        string $subProductCode
    ): array {
        $pickupTime = date('H:i');
        $body = [
            'pPinCodeFrom' => $originPin,
            'pPinCodeTo' => $destPin,
            'pProductCode' => $productCode,
            'pSubProductCode' => $subProductCode,
            'pPudate' => '/Date(' . (time() * 1000) . ')/',
            'pPickupTime' => $pickupTime,
            'profile' => $this->buildProfile(),
        ];

        $paths = [
            '/in/transportation/time-finder/v1/GetDomesticTransitTimeForPinCodeandProduct',
            '/in/transportation/time-finder/v1',
        ];

        foreach ($paths as $path) {
            $resp = $this->gatewayPost($path, $body);
            if (!empty($resp['success'])) {
                return $resp;
            }
            if ((int) ($resp['http_code'] ?? 0) !== 404) {
                return $resp;
            }
        }

        return ['success' => false, 'error' => 'Transit time API unavailable.', 'http_code' => 404];
    }

    /** @return array<string, string> */
    private function buildProfile(): array
    {
        return [
            'Api_type' => 'S',
            'LicenceKey' => (string) ($this->config['shipment_licence_key'] ?? ''),
            'LoginID' => (string) ($this->config['login_id'] ?? ''),
            'Version' => '1.0',
        ];
    }

    /** @return list<array<string, mixed>> */
    private function resolveRateProducts(): array
    {
        $mapped = bluedartResolveRateProductsFromCredentials($this->config);
        if ($mapped !== []) {
            return $mapped;
        }

        $defaultCode = trim((string) ($this->config['default_product_code'] ?? ''));
        if ($defaultCode !== '' && !preg_match('/^etail/i', $defaultCode)) {
            return [[
                'product_code' => $defaultCode,
                'sub_product_code' => (string) ($this->config['default_sub_product_code'] ?? 'P'),
                'pack_type' => 'L',
                'feature' => 'R',
                'label' => 'Blue Dart ' . $defaultCode,
            ]];
        }

        return [
            [
                'product_code' => 'A',
                'sub_product_code' => 'P',
                'pack_type' => 'L',
                'feature' => 'R',
                'label' => 'Blue Dart Air',
            ],
            [
                'product_code' => 'E',
                'sub_product_code' => 'P',
                'pack_type' => 'L',
                'feature' => 'R',
                'label' => 'Blue Dart Surface',
            ],
        ];
    }

    private function estimatePriceInr(float $weightKg, string $productCode, string $subProductCode): ?float
    {
        $key = strtoupper($productCode) . '_' . strtoupper($subProductCode);
        $estimate = is_array($this->config['rate_estimate'] ?? null) ? $this->config['rate_estimate'] : [];
        $perKg = bluedartParseInrAmount($estimate['default_per_kg_inr'] ?? null)
            ?? bluedartParseInrAmount($this->config['rate_per_kg_inr'] ?? null)
            ?? 0.0;
        $minimum = bluedartParseInrAmount($estimate['default_minimum_inr'] ?? null)
            ?? bluedartParseInrAmount($this->config['rate_minimum_inr'] ?? null)
            ?? 0.0;

        $byProduct = $estimate['products'] ?? $estimate['by_product'] ?? null;
        if (is_array($byProduct)) {
            $row = $byProduct[$key] ?? $byProduct[strtolower($key)] ?? null;
            if (is_array($row)) {
                $rowPerKg = bluedartParseInrAmount($row['per_kg_inr'] ?? null);
                if ($rowPerKg !== null) {
                    $perKg = $rowPerKg;
                }
                $rowMinimum = bluedartParseInrAmount($row['minimum_inr'] ?? null);
                if ($rowMinimum !== null) {
                    $minimum = $rowMinimum;
                }
            }
        }

        if ($perKg <= 0 && $minimum <= 0) {
            return null;
        }

        $amount = max($minimum, $perKg * max(0.0, $weightKg));
        return round($amount, 2);
    }

    /** @param mixed $data */
    private function isServiceabilityOk($data): bool
    {
        if ($data === null) {
            return false;
        }

        if (is_array($data)) {
            if ($this->isTruthy($data['IsError'] ?? $data['isError'] ?? null)) {
                return false;
            }
            if ($this->isTruthy($data['Error'] ?? $data['error'] ?? null)) {
                return false;
            }

            foreach (['Serviceable', 'serviceable', 'IsServiceable', 'Available', 'available'] as $flag) {
                if (array_key_exists($flag, $data)) {
                    return $this->isTruthy($data[$flag]);
                }
            }

            // Nested Blue Dart reference objects.
            foreach ($data as $value) {
                if (is_array($value) && $this->isServiceabilityOk($value)) {
                    return true;
                }
            }

            // Non-error payload with pin / area details usually means serviceable.
            if (isset($data['pinCode']) || isset($data['PinCode']) || isset($data['Area']) || isset($data['ServiceCenter'])) {
                return true;
            }

            return $data !== [];
        }

        return false;
    }

    /** @param mixed $data */
    private function parseTransitEtd($data): string
    {
        if (!is_array($data)) {
            return 'N/A';
        }

        foreach ([
            'ExpectedDeliveryDate',
            'expectedDeliveryDate',
            'DeliveryDate',
            'deliveryDate',
            'ETD',
            'etd',
            'Date',
            'date',
        ] as $key) {
            if (!empty($data[$key]) && is_string($data[$key])) {
                return $this->formatEtdString($data[$key]);
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $nested = $this->parseTransitEtd($value);
                if ($nested !== 'N/A') {
                    return $nested;
                }
            }
        }

        return 'N/A';
    }

    private function formatEtdString(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'N/A';
        }

        if (preg_match('/\/Date\((\d+)\)\//', $value, $m)) {
            $ts = (int) (((int) $m[1]) / 1000);
            if ($ts > 0) {
                return date('d M Y', $ts);
            }
        }

        $ts = strtotime($value);
        if ($ts !== false && $ts > 0) {
            return date('d M Y', $ts);
        }

        return $value;
    }

    /** @param array<string, mixed>|null $body */
    private function gatewayPost(string $path, ?array $body): array
    {
        $auth = $this->authenticate();
        if (!$auth['success']) {
            return ['success' => false, 'error' => (string) ($auth['error'] ?? 'Authentication failed.')];
        }

        $url = rtrim((string) $this->config['api_gateway_base_url'], '/') . $path;
        $resp = $this->rawRequest('POST', $url, $body, [
            'JWTToken: ' . (string) $this->jwtToken,
            'Content-Type: application/json',
            'Accept: application/json',
        ]);

        if ((int) ($resp['http_code'] ?? 0) === 401) {
            $this->jwtToken = null;
            $auth = $this->authenticate(true);
            if (!$auth['success']) {
                return ['success' => false, 'error' => (string) ($auth['error'] ?? 'Authentication failed.')];
            }
            $resp = $this->rawRequest('POST', $url, $body, [
                'JWTToken: ' . (string) $this->jwtToken,
                'Content-Type: application/json',
                'Accept: application/json',
            ]);
        }

        return $resp;
    }

    /**
     * @param array<string, mixed>|null $body
     * @param list<string> $extraHeaders
     * @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string}
     */
    private function rawRequest(string $method, string $url, ?array $body, array $extraHeaders): array
    {
        $headers = $extraHeaders;
        if (!in_array('Content-Type: application/json', $headers, true)
            && !in_array('Accept: application/json', $headers, true)
            && $body !== null
        ) {
            $headers[] = 'Content-Type: application/json';
            $headers[] = 'Accept: application/json';
        }

        $encoded = $body !== null
            ? json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : null;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
        ]);
        if ($encoded !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encoded);
        }

        $raw = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [
                'success' => false,
                'error' => $curlError !== '' ? $curlError : 'Blue Dart request failed.',
                'http_code' => $httpCode,
            ];
        }

        $decoded = json_decode($raw, true);
        $ok = $httpCode >= 200 && $httpCode < 300;
        if (!$ok) {
            $message = $this->extractErrorMessage($decoded, $raw);
            return [
                'success' => false,
                'error' => $message,
                'http_code' => $httpCode,
                'data' => $decoded,
                'raw' => $raw,
            ];
        }

        if (is_array($decoded) && $this->responseIndicatesError($decoded)) {
            return [
                'success' => false,
                'error' => $this->extractErrorMessage($decoded, $raw),
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

    /** @param mixed $decoded */
    private function extractErrorMessage($decoded, string $raw): string
    {
        if (is_array($decoded)) {
            if (isset($decoded['error-response']) && is_array($decoded['error-response'])) {
                $msgs = [];
                foreach ($decoded['error-response'] as $row) {
                    if (is_array($row) && !empty($row['msg'])) {
                        $msgs[] = (string) $row['msg'];
                    }
                }
                if ($msgs !== []) {
                    return implode('; ', $msgs);
                }
            }
            foreach (['message', 'error', 'title', 'Message', 'Error'] as $key) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    return $decoded[$key];
                }
            }
        }

        return $raw !== '' ? $raw : 'Blue Dart API error.';
    }

    /** @param array<string, mixed> $decoded */
    private function responseIndicatesError(array $decoded): bool
    {
        if ($this->isTruthy($decoded['IsError'] ?? $decoded['isError'] ?? null)) {
            return true;
        }
        if (isset($decoded['status']) && is_numeric($decoded['status']) && (int) $decoded['status'] >= 400) {
            return true;
        }
        return false;
    }

    /** @param mixed $value */
    private function isTruthy($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value !== 0;
        }
        if (is_string($value)) {
            $v = strtolower(trim($value));
            return in_array($v, ['1', 'true', 'yes', 'y'], true);
        }
        return $value !== null && $value !== false && $value !== '';
    }

    /** @param mixed $data */
    private function extractJwtToken($data, string $raw): string
    {
        if (is_array($data)) {
            foreach (['JWTToken', 'jwtToken', 'token', 'access_token', 'accessToken', 'Token'] as $key) {
                if (!empty($data[$key]) && is_string($data[$key])) {
                    $token = trim($data[$key]);
                    if ($this->looksLikeJwt($token)) {
                        return $token;
                    }
                }
            }
        }

        $raw = trim($raw);
        if ($this->looksLikeJwt($raw)) {
            return $raw;
        }

        return '';
    }

    private function looksLikeJwt(string $value): bool
    {
        if ($value === '') {
            return false;
        }
        if (str_starts_with($value, 'eyJ') && substr_count($value, '.') >= 2) {
            return true;
        }
        // Reject echo responses from token endpoint when credentials are wrong.
        if (str_contains($value, 'ClientID') || str_contains($value, 'clientSecret')) {
            return false;
        }
        return strlen($value) > 40;
    }
}
