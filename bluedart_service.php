<?php

require_once __DIR__ . '/helpers/courier/credential_urls.php';
require_once __DIR__ . '/helpers/courier/bluedart_rate_helpers.php';
require_once __DIR__ . '/helpers/courier/bluedart_legacy_soap.php';

/**
 * Blue Dart domestic client.
 *
 * Default api_mode=legacy uses netconnect SOAP (eShipz-style login_id + licence_key).
 * api_mode=rest uses DHL apigateway.bluedart.com (needs developer.dhl.com consumer_key/secret).
 *
 * Price on rate tiles is supplied separately via Shiprocket when available.
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
            'api_mode' => 'legacy',
            'legacy_waybill_endpoint' => '',
            'legacy_finder_endpoint' => '',
        ];

        if (!is_array($config)) {
            $this->config = $defaults;
            return;
        }

        $config = bluedartNormalizeGatewayAuthConfig($config);
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

        $legacyEndpoints = bluedartResolveLegacyEndpoints($config);
        $apiMode = strtolower(trim((string) ($config['api_mode'] ?? $defaults['api_mode'])));

        $this->config = array_merge($defaults, [
            'api_mode' => $apiMode !== '' ? $apiMode : $defaults['api_mode'],
            'legacy_waybill_endpoint' => $legacyEndpoints['waybill'],
            'legacy_finder_endpoint' => $legacyEndpoints['finder'],
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

        if ($this->usesLegacyApi()) {
            return $this->estimateRatesLegacy($params, $originPin, $destPin, $weightKg);
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

        $attempts = bluedartBuildJwtAuthAttempts($this->config);
        if ($attempts === []) {
            $authStatus = bluedartDescribeGatewayAuthStatus($this->config);
            $detail = (string) ($authStatus['message'] ?? 'Blue Dart API authentication is not configured.');
            if (!empty($authStatus['hints'])) {
                $detail .= ' ' . implode(' ', $authStatus['hints']);
            }
            return [
                'success' => false,
                'error' => $detail,
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
        $lastError = 'Could not obtain Blue Dart JWT token.';

        foreach ($attempts as $attempt) {
            $clientId = (string) $attempt['client_id'];
            $clientSecret = (string) $attempt['client_secret'];
            $label = (string) ($attempt['label'] ?? '');

            $payloads = [
                ['ClientID' => $clientId, 'clientSecret' => $clientSecret],
                ['clientID' => $clientId, 'clientSecret' => $clientSecret],
            ];
            if ($label === 'login_id_licence_key') {
                $payloads[] = ['LoginID' => $clientId, 'LicenceKey' => $clientSecret];
            }

            foreach ($payloads as $body) {
                $resp = $this->rawRequest('POST', $url, $body, []);
                if (!$resp['success']) {
                    $lastError = bluedartSanitizeErrorMessage((string) ($resp['error'] ?? $lastError));
                    if ($label !== '') {
                        $lastError .= ' (auth: ' . $label . ')';
                    }
                    continue;
                }

                $rawBody = (string) ($resp['raw'] ?? '');
                $token = $this->extractJwtToken($resp['data'] ?? null, $rawBody);
                if ($token === '') {
                    $lastError = 'Blue Dart rejected the API credentials for JWT generation'
                        . ($label !== '' ? ' (auth: ' . $label . ')' : '')
                        . '. ' . $this->describeTokenRejection($rawBody)
                        . ' ' . bluedartDhlPortalSetupHint();
                    continue;
                }

                $this->jwtToken = $token;
                return ['success' => true, 'token' => $token];
            }

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
        }

        return ['success' => false, 'error' => bluedartSanitizeErrorMessage($lastError)];
    }

    private function describeTokenRejection(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return 'Empty response from token endpoint.';
        }

        if ($this->isCredentialEchoResponse($raw)) {
            return 'Token endpoint echoed credentials instead of issuing a JWT.';
        }

        $snippet = bluedartSanitizeErrorMessage($this->summarizeRawResponse($raw));
        if ($snippet !== '' && !str_contains(strtolower($snippet), 'redacted')) {
            return $snippet;
        }

        return 'Invalid token response from Blue Dart gateway.';
    }

    private function isCredentialEchoResponse(string $raw): bool
    {
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return preg_match('/clientSecret|clientID|LicenceKey/i', $raw) === 1
                && !str_contains($raw, 'eyJ');
        }

        if (isset($decoded['JWTToken']) || isset($decoded['jwtToken']) || isset($decoded['token'])) {
            return false;
        }

        $keys = array_map(static fn ($k) => strtolower((string) $k), array_keys($decoded));
        $credentialKeys = ['clientid', 'clientsecret', 'loginid', 'licencekey'];
        $matched = 0;
        foreach ($credentialKeys as $ck) {
            if (in_array($ck, $keys, true)) {
                $matched++;
            }
        }

        return $matched >= 2 && count($decoded) <= 5;
    }

    public function usesLegacyApi(): bool
    {
        return bluedartUsesLegacyApi($this->config);
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    public function getServicesForPincode(string $pinCode): array
    {
        if ($this->usesLegacyApi()) {
            return $this->legacyGetServicesForPincode($pinCode);
        }

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
            'Version' => $this->usesLegacyApi() ? '1.3' : '1.0',
        ];
    }

    /** @return array<string, string> */
    private function buildLegacyProfile(): array
    {
        return [
            'Api_type' => 'S',
            'LicenceKey' => (string) ($this->config['shipment_licence_key'] ?? ''),
            'LoginID' => (string) ($this->config['login_id'] ?? ''),
            'Version' => '1.3',
        ];
    }

    /** @return array{success:bool,data?:mixed,error?:string,http_code?:int,raw?:string} */
    private function legacyGetServicesForPincode(string $pinCode): array
    {
        $pinCode = preg_replace('/\D/', '', $pinCode);
        $resp = bluedartLegacySoapRequest(
            (string) ($this->config['legacy_finder_endpoint'] ?? ''),
            'http://tempuri.org/IServiceFinderQuery/GetServicesforPincode',
            'GetServicesforPincode',
            [
                'pinCode' => $pinCode,
                'profile' => $this->buildLegacyProfile(),
            ]
        );

        if (empty($resp['success'])) {
            return $resp;
        }

        $values = is_array($resp['data']['values'] ?? null) ? $resp['data']['values'] : [];
        return [
            'success' => true,
            'http_code' => $resp['http_code'] ?? null,
            'raw' => $resp['raw'] ?? null,
            'data' => $values,
        ];
    }

    /**
     * @return array{success:bool,quotes?:list<array<string,mixed>>,error?:string,debug?:array<string,mixed>}
     */
    private function estimateRatesLegacy(array $params, string $originPin, string $destPin, float $weightKg): array
    {
        $products = $this->resolveRateProducts();
        if ($products === []) {
            return [
                'success' => false,
                'error' => 'No Blue Dart rate_products configured. Add rate_products[] or default_service_type in Courier accounts.',
            ];
        }

        $quotes = [];
        $debug = ['api_mode' => 'legacy', 'products' => []];

        foreach ($products as $product) {
            $productCode = strtoupper(trim((string) ($product['product_code'] ?? '')));
            $subProductCode = strtoupper(trim((string) ($product['sub_product_code'] ?? 'P')));
            if ($productCode === '') {
                continue;
            }

            $packType = strtoupper(trim((string) ($product['pack_type'] ?? 'L')));
            $feature = strtoupper(trim((string) ($product['feature'] ?? 'R')));
            $label = trim((string) ($product['label'] ?? $product['name'] ?? ('Blue Dart ' . $productCode . '/' . $subProductCode)));

            $serviceResp = $this->legacyGetServicesForPincode($destPin);
            $debug['products'][$productCode . '_' . $subProductCode] = ['serviceability' => $serviceResp];

            if (empty($serviceResp['success']) || !$this->isLegacyServiceabilityOk($serviceResp['data'] ?? null)) {
                continue;
            }

            $quotes[] = [
                'product_code' => $productCode,
                'sub_product_code' => $subProductCode,
                'pack_type' => $packType,
                'feature' => $feature,
                'label' => $label,
                'price' => null,
                'currency' => (string) ($this->config['rating_currency'] ?? 'INR'),
                'etd' => 'N/A',
                'origin_pin' => $originPin,
                'dest_pin' => $destPin,
                'billable_weight_kg' => $weightKg,
                'etd_source' => '',
                'serviceable' => true,
                'serviceability' => $serviceResp['data'] ?? null,
            ];
        }

        if ($quotes === []) {
            return [
                'success' => false,
                'error' => 'Blue Dart legacy API returned no serviceable products for this pincode pair.',
                'debug' => $debug,
            ];
        }

        return [
            'success' => true,
            'quotes' => $quotes,
            'debug' => $debug,
        ];
    }

    /** @param mixed $data */
    private function isLegacyServiceabilityOk($data): bool
    {
        if (!is_array($data) || $data === []) {
            return false;
        }

        foreach (['IsError', 'iserror', 'Error', 'error'] as $key) {
            if (array_key_exists($key, $data) && $this->isTruthy($data[$key])) {
                return false;
            }
        }

        foreach (['AreaCode', 'areacode', 'CityDescription', 'citydescription', 'PinCode', 'pincode'] as $key) {
            if (!empty($data[$key])) {
                return true;
            }
        }

        return true;
    }

    /**
     * Resolve origin area (3-letter code, e.g. DEL) from shipper pincode via Location Finder.
     */
    public function resolveOriginAreaForPincode(string $pinCode): string
    {
        $pinCode = preg_replace('/\D/', '', $pinCode);
        if (strlen($pinCode) !== 6) {
            return '';
        }

        $resp = $this->getServicesForPincode($pinCode);
        if (empty($resp['success'])) {
            return '';
        }

        return $this->extractOriginAreaFromServiceability($resp['data'] ?? null);
    }

    /** @param mixed $data */
    public function extractOriginAreaFromServiceability($data): string
    {
        if (!is_array($data)) {
            return '';
        }

        foreach ([
            'OriginArea',
            'originArea',
            'AreaCode',
            'areaCode',
            'PickupAreaCode',
            'pickupAreaCode',
            'Area',
            'area',
            'ServiceCenterCode',
            'serviceCenterCode',
        ] as $key) {
            $value = $this->normalizeOriginAreaValue($data[$key] ?? null);
            if ($value !== '') {
                return $value;
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            $found = $this->extractOriginAreaFromServiceability($value);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    /** @param mixed $value */
    private function normalizeOriginAreaValue($value): string
    {
        if (!is_string($value) && !is_numeric($value)) {
            return '';
        }

        $value = strtoupper(trim((string) $value));
        if ($value === '' || !preg_match('/^[A-Z]{2,4}$/', $value)) {
            return '';
        }

        return $value;
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
            return [
                'success' => false,
                'error' => bluedartSanitizeErrorMessage((string) ($auth['error'] ?? 'Authentication failed.')),
            ];
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
                return [
                    'success' => false,
                    'error' => bluedartSanitizeErrorMessage((string) ($auth['error'] ?? 'Authentication failed.')),
                ];
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
            $message = bluedartSanitizeErrorMessage(
                $this->formatApiError($this->extractErrorMessage($decoded, $raw), $httpCode, $raw)
            );
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
                'error' => bluedartSanitizeErrorMessage(
                    $this->formatApiError($this->extractErrorMessage($decoded, $raw), $httpCode, $raw)
                ),
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

            $statusMsg = $this->extractStatusInformation($decoded);
            if ($statusMsg !== '') {
                return $statusMsg;
            }

            foreach (['message', 'error', 'title', 'Message', 'Error', 'ErrorMessage', 'remark', 'rmk'] as $key) {
                if (!empty($decoded[$key]) && is_string($decoded[$key])) {
                    return $decoded[$key];
                }
            }
        }

        $snippet = $this->summarizeRawResponse($raw);
        return $snippet !== '' ? $snippet : 'Blue Dart API error.';
    }

    private function formatApiError(string $message, int $httpCode, string $raw): string
    {
        $message = trim($message);
        if ($message === '' || $message === 'Blue Dart API error.') {
            if ($httpCode === 401) {
                $message = 'Blue Dart API unauthorized — JWT invalid or expired';
            } elseif ($httpCode === 403) {
                $message = 'Blue Dart API forbidden';
            } elseif ($httpCode > 0) {
                $message = 'Blue Dart API HTTP ' . $httpCode;
            } else {
                $message = 'Blue Dart API error';
            }
        }

        if ($httpCode > 0 && !preg_match('/\b' . preg_quote((string) $httpCode, '/') . '\b/', $message)) {
            $message .= ' (HTTP ' . $httpCode . ')';
        }

        $snippet = $this->summarizeRawResponse($raw);
        if ($snippet !== '' && strlen($snippet) < 280 && !str_contains($message, $snippet)) {
            $message .= ': ' . $snippet;
        }

        return $message;
    }

    private function summarizeRawResponse(string $raw): string
    {
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (is_array(json_decode($raw, true))) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $msg = $this->extractErrorMessage($decoded, '');
                if ($msg !== '' && $msg !== 'Blue Dart API error.') {
                    return $msg;
                }
            }
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($raw)));
        if ($plain === '') {
            return '';
        }

        $plain = strlen($plain) > 240 ? (substr($plain, 0, 237) . '...') : $plain;

        return bluedartSanitizeErrorMessage($plain);
    }

    /** @param array<string, mixed> $decoded */
    private function extractStatusInformation(array $decoded): string
    {
        foreach (['Status', 'status'] as $statusKey) {
            if (empty($decoded[$statusKey]) || !is_array($decoded[$statusKey])) {
                continue;
            }
            $msgs = [];
            foreach ($decoded[$statusKey] as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $info = trim((string) ($row['StatusInformation'] ?? $row['statusInformation'] ?? $row['Message'] ?? ''));
                if ($info !== '') {
                    $msgs[] = $info;
                }
            }
            if ($msgs !== []) {
                return implode('; ', $msgs);
            }
        }

        foreach ($decoded as $value) {
            if (!is_array($value)) {
                continue;
            }
            $nested = $this->extractStatusInformation($value);
            if ($nested !== '') {
                return $nested;
            }
        }

        return '';
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
        // Only accept standard JWT shape (three base64url segments). Reject long error/HTML bodies.
        return str_starts_with($value, 'eyJ') && substr_count($value, '.') >= 2;
    }

    /**
     * Create a domestic waybill (AWB) via Blue Dart GenerateWayBill API.
     *
     * @param array<string, mixed> $shipment Pre-built Request subtree (Consignee, Shipper, Services, Returnadds)
     * @return array{success:bool,awb?:string,pdf_binary?:string,destination_area?:string,error?:string,data?:mixed,http_code?:int}
     */
    public function generateWaybill(array $shipment): array
    {
        if ($this->usesLegacyApi()) {
            return $this->generateWaybillLegacy($shipment);
        }

        $auth = $this->authenticate();
        if (!$auth['success']) {
            return [
                'success' => false,
                'error' => bluedartSanitizeErrorMessage((string) ($auth['error'] ?? 'Blue Dart authentication failed.')),
            ];
        }

        $body = [
            'Request' => $shipment,
            'Profile' => $this->buildProfile(),
        ];

        $configuredPath = trim((string) ($this->config['waybill_api_path'] ?? ''));
        $paths = array_values(array_unique(array_filter([
            $configuredPath !== '' ? $configuredPath : null,
            '/in/transportation/waybill/v1/GenerateWayBill',
            '/waybill/GenerateWayBill',
        ])));

        $last = ['success' => false, 'error' => 'Blue Dart waybill generation failed.'];
        foreach ($paths as $path) {
            $resp = $this->gatewayPost($path, $body);
            $last = $resp;
            if (empty($resp['success'])) {
                $apiError = trim((string) ($resp['error'] ?? ''));
                if ($apiError !== '') {
                    $last['error'] = $apiError . ' [' . $path . ']';
                }
                continue;
            }

            $parsed = $this->parseGenerateWaybillResponse($resp['data'] ?? null);
            if (!empty($parsed['success'])) {
                return array_merge($parsed, [
                    'data' => $resp['data'] ?? null,
                    'http_code' => $resp['http_code'] ?? null,
                ]);
            }

            $last['error'] = (string) ($parsed['error'] ?? 'Blue Dart waybill response did not contain an AWB.');
            $last['data'] = $resp['data'] ?? null;
        }

        return [
            'success' => false,
            'error' => bluedartSanitizeErrorMessage((string) ($last['error'] ?? 'Blue Dart waybill generation failed.')),
            'data' => $last['data'] ?? null,
            'http_code' => $last['http_code'] ?? null,
        ];
    }

    /**
     * Legacy netconnect SOAP waybill (eShipz-style credentials).
     *
     * @param array<string, mixed> $shipment
     * @return array{success:bool,awb?:string,pdf_binary?:string,destination_area?:string,error?:string,data?:mixed,http_code?:int}
     */
    private function generateWaybillLegacy(array $shipment): array
    {
        if (!bluedartHasProfileCredentials($this->config)) {
            return [
                'success' => false,
                'error' => 'Blue Dart legacy waybill requires login_id and licence_key in Courier accounts.',
            ];
        }

        $request = bluedartAdaptShipmentForLegacySoap($shipment);
        $resp = bluedartLegacySoapRequest(
            (string) ($this->config['legacy_waybill_endpoint'] ?? ''),
            'http://tempuri.org/IWayBillGeneration/GenerateWayBill',
            'GenerateWayBill',
            [
                'Request' => $request,
                'Profile' => $this->buildLegacyProfile(),
            ]
        );

        if (empty($resp['success'])) {
            return [
                'success' => false,
                'error' => bluedartSanitizeErrorMessage((string) ($resp['error'] ?? 'Blue Dart legacy waybill failed.')),
                'data' => $resp['data'] ?? null,
                'http_code' => $resp['http_code'] ?? null,
                'endpoint' => $resp['endpoint'] ?? null,
                'soap_variant' => $resp['soap_variant'] ?? null,
                'request_xml' => $resp['request_xml'] ?? null,
                'response_raw' => $resp['response_raw'] ?? '',
                'curl_error' => $resp['curl_error'] ?? '',
                'bytes_received' => $resp['bytes_received'] ?? null,
            ];
        }

        $values = is_array($resp['data']['values'] ?? null) ? $resp['data']['values'] : [];
        $parsed = bluedartParseLegacyWaybillValues($values);
        if (empty($parsed['success'])) {
            return [
                'success' => false,
                'error' => bluedartSanitizeErrorMessage((string) ($parsed['error'] ?? 'Blue Dart legacy waybill failed.')),
                'data' => $values,
                'http_code' => $resp['http_code'] ?? null,
                'endpoint' => $resp['endpoint'] ?? null,
                'soap_variant' => $resp['soap_variant'] ?? null,
                'request_xml' => $resp['request_xml'] ?? null,
                'response_raw' => $resp['response_raw'] ?? '',
            ];
        }

        return array_merge($parsed, [
            'data' => $values,
            'http_code' => $resp['http_code'] ?? null,
        ]);
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function buildWaybillShipmentPayload(array $params): array
    {
        $isCod = !empty($params['cod']);
        $subProduct = strtoupper(trim((string) ($params['sub_product_code'] ?? ($isCod ? 'C' : 'P'))));
        if (!in_array($subProduct, ['P', 'C'], true)) {
            $subProduct = $isCod ? 'C' : 'P';
        }

        $productCode = strtoupper(trim((string) ($params['product_code'] ?? 'A')));
        if ($productCode === '') {
            $productCode = 'A';
        }

        $packType = strtoupper(trim((string) ($params['pack_type'] ?? 'L')));
        if ($packType === '') {
            $packType = 'L';
        }

        $weightKg = max(0.01, (float) ($params['weight_kg'] ?? 0.5));
        $lengthCm = max(1.0, (float) ($params['length_cm'] ?? 1));
        $widthCm = max(1.0, (float) ($params['width_cm'] ?? 1));
        $heightCm = max(1.0, (float) ($params['height_cm'] ?? 1));
        $collectable = $isCod ? round((float) ($params['cod_amount'] ?? $params['declared_value'] ?? 0), 2) : 0.0;
        if ($isCod && $collectable <= 0) {
            $collectable = round((float) ($params['declared_value'] ?? 0.01), 2);
        }

        $creditRef = $this->sanitizeCreditReference((string) ($params['credit_reference'] ?? ''));
        $pickupTime = preg_replace('/\D/', '', (string) ($params['pickup_time'] ?? date('Hi')));
        if (strlen($pickupTime) < 4) {
            $pickupTime = str_pad($pickupTime, 4, '0', STR_PAD_LEFT);
        }
        $pickupTime = substr($pickupTime, 0, 4);

        $services = [
            'AWBNo' => '',
            'ActualWeight' => number_format($weightKg, 2, '.', ''),
            'CollactableAmount' => $isCod ? $collectable : 0,
            'CreditReferenceNo' => $creditRef,
            'Dimensions' => [[
                'Length' => round($lengthCm, 2),
                'Breadth' => round($widthCm, 2),
                'Height' => round($heightCm, 2),
                'Count' => max(1, (int) ($params['piece_count'] ?? 1)),
            ]],
            'PDFOutputNotRequired' => false,
            'PackType' => $packType,
            'PickupDate' => '/Date(' . (time() * 1000) . ')/',
            'PickupTime' => $pickupTime,
            'PieceCount' => (string) max(1, (int) ($params['piece_count'] ?? 1)),
            'ProductCode' => $productCode,
            'SubProductCode' => $subProduct,
            'RegisterPickup' => !empty($params['register_pickup']),
            'Commodity' => [],
            'itemdtl' => [],
        ];

        $invoiceNumber = trim((string) ($params['invoice_number'] ?? ''));
        if ($invoiceNumber !== '') {
            $services['InvoiceNo'] = substr($invoiceNumber, 0, 20);
        }

        $declaredValue = round((float) ($params['declared_value'] ?? $collectable), 2);
        if ($declaredValue > 0) {
            $services['DeclaredValue'] = $declaredValue;
        }

        $consigneeLines = $this->splitAddressLines((string) ($params['consignee_address'] ?? ''), 3, 50);
        $shipperLines = $this->splitAddressLines((string) ($params['shipper_address'] ?? ''), 3, 50);
        $returnLines = $this->splitAddressLines((string) ($params['return_address'] ?? $params['shipper_address'] ?? ''), 3, 50);

        return [
            'Consignee' => [
                'ConsigneeAddress1' => $consigneeLines[0] ?? '',
                'ConsigneeAddress2' => $consigneeLines[1] ?? '',
                'ConsigneeAddress3' => $consigneeLines[2] ?? '',
                'ConsigneeAddressType' => 'R',
                'ConsigneeEmailID' => trim((string) ($params['consignee_email'] ?? '')),
                'ConsigneeMobile' => preg_replace('/\D/', '', (string) ($params['consignee_mobile'] ?? '')),
                'ConsigneeName' => substr(trim((string) ($params['consignee_name'] ?? 'Customer')), 0, 50),
                'ConsigneePincode' => preg_replace('/\D/', '', (string) ($params['consignee_pincode'] ?? '')),
                'ConsigneeTelephone' => '',
            ],
            'Returnadds' => [
                'ReturnAddress1' => $returnLines[0] ?? '',
                'ReturnAddress2' => $returnLines[1] ?? '',
                'ReturnAddress3' => $returnLines[2] ?? '',
                'ReturnContact' => substr(trim((string) ($params['return_contact'] ?? $params['shipper_name'] ?? 'Seller')), 0, 50),
                'ReturnMobile' => preg_replace('/\D/', '', (string) ($params['return_mobile'] ?? $params['shipper_mobile'] ?? '')),
                'ReturnPincode' => preg_replace('/\D/', '', (string) ($params['return_pincode'] ?? $params['shipper_pincode'] ?? '')),
                'ReturnTelephone' => '',
            ],
            'Services' => $services,
            'Shipper' => [
                'CustomerAddress1' => $shipperLines[0] ?? '',
                'CustomerAddress2' => $shipperLines[1] ?? '',
                'CustomerAddress3' => $shipperLines[2] ?? '',
                'CustomerCode' => trim((string) ($params['customer_code'] ?? $this->config['customer_code'] ?? '')),
                'CustomerEmailID' => trim((string) ($params['shipper_email'] ?? '')),
                'CustomerMobile' => preg_replace('/\D/', '', (string) ($params['shipper_mobile'] ?? '')),
                'CustomerName' => substr(trim((string) ($params['shipper_name'] ?? 'Seller')), 0, 50),
                'CustomerPincode' => preg_replace('/\D/', '', (string) ($params['shipper_pincode'] ?? '')),
                'CustomerTelephone' => '',
                'IsToPayCustomer' => false,
                'OriginArea' => trim((string) ($params['origin_area'] ?? $this->config['origin_area'] ?? '')),
                'Sender' => substr(trim((string) ($params['sender'] ?? $this->config['registered_name'] ?? $params['shipper_name'] ?? 'Seller')), 0, 50),
                'VendorCode' => '',
            ],
        ];
    }

    public function saveWaybillLabelPdf(string $awb, string $pdfBinary): ?string
    {
        $awb = preg_replace('/[^A-Za-z0-9_-]/', '', $awb);
        if ($awb === '' || $pdfBinary === '') {
            return null;
        }

        $dir = dirname(__DIR__) . '/tmp/bluedart_labels';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            return null;
        }

        $path = $dir . '/bluedart_' . $awb . '.pdf';
        if (file_put_contents($path, $pdfBinary) === false) {
            return null;
        }

        return $path;
    }

    public function resolveStoredLabelPath(string $awb): ?string
    {
        $awb = preg_replace('/[^A-Za-z0-9_-]/', '', $awb);
        if ($awb === '') {
            return null;
        }

        $path = dirname(__DIR__) . '/tmp/bluedart_labels/bluedart_' . $awb . '.pdf';
        return is_file($path) ? $path : null;
    }

    /** @param mixed $data @return array{success:bool,awb?:string,pdf_binary?:string,destination_area?:string,error?:string} */
    public function parseGenerateWaybillResponse($data): array
    {
        if (!is_array($data)) {
            return ['success' => false, 'error' => 'Invalid Blue Dart waybill response.'];
        }

        $statusNode = $this->findWaybillStatusNode($data);
        if ($statusNode !== null && $this->isTruthy($statusNode['IsError'] ?? $statusNode['isError'] ?? null)) {
            $msg = trim((string) (
                $statusNode['Status'][0]['StatusInformation']
                ?? $statusNode['status'][0]['StatusInformation']
                ?? $statusNode['ErrorMessage']
                ?? $statusNode['error']
                ?? 'Blue Dart waybill generation failed.'
            ));
            return ['success' => false, 'error' => $msg !== '' ? $msg : 'Blue Dart waybill generation failed.'];
        }

        $awb = $this->findScalarInTree($data, ['AWBNo', 'awbNo', 'AWBNumber', 'WaybillNo']);
        if ($awb === '') {
            return ['success' => false, 'error' => 'Blue Dart did not return an AWB number.'];
        }

        $pdfBinary = $this->extractWaybillPdfBinary($data);
        if ($pdfBinary === '') {
            return [
                'success' => false,
                'error' => 'Blue Dart returned AWB ' . $awb . ' but no label PDF (AWBPrintContent).',
                'awb' => $awb,
            ];
        }

        return [
            'success' => true,
            'awb' => $awb,
            'pdf_binary' => $pdfBinary,
            'destination_area' => $this->findScalarInTree($data, ['DestinationArea', 'destinationArea']),
        ];
    }

    /** @param array<string, mixed> $data */
    private function findWaybillStatusNode(array $data): ?array
    {
        foreach ([
            'GenerateWayBillResult',
            'WayBillGenerationStatus',
            'WaybillGenerationStatus',
            'Status',
        ] as $key) {
            if (!empty($data[$key]) && is_array($data[$key])) {
                return $data[$key];
            }
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $nested = $this->findWaybillStatusNode($value);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    /** @param array<string, mixed> $data @param list<string> $keys */
    private function findScalarInTree(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            if (!empty($data[$key]) && (is_string($data[$key]) || is_numeric($data[$key]))) {
                return trim((string) $data[$key]);
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            $found = $this->findScalarInTree($value, $keys);
            if ($found !== '') {
                return $found;
            }
        }

        return '';
    }

    /** @param array<string, mixed> $data */
    private function extractWaybillPdfBinary(array $data): string
    {
        foreach (['AWBPrintContent', 'awbPrintContent', 'PDFPrintContent', 'pdfPrintContent'] as $key) {
            $binary = $this->decodePdfContent($data[$key] ?? null);
            if ($binary !== '') {
                return $binary;
            }
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }
            $binary = $this->extractWaybillPdfBinary($value);
            if ($binary !== '') {
                return $binary;
            }
        }

        return '';
    }

    /** @param mixed $content */
    private function decodePdfContent($content): string
    {
        if ($content === null || $content === '') {
            return '';
        }

        if (is_string($content)) {
            $trimmed = trim($content);
            if ($trimmed === '') {
                return '';
            }
            if (strncmp($trimmed, '%PDF', 4) === 0) {
                return $trimmed;
            }
            $decoded = base64_decode($trimmed, true);
            if (is_string($decoded) && $decoded !== '' && strncmp($decoded, '%PDF', 4) === 0) {
                return $decoded;
            }
        }

        if (is_array($content)) {
            $bytes = [];
            foreach ($content as $byte) {
                if (!is_numeric($byte)) {
                    continue;
                }
                $bytes[] = chr((int) $byte);
            }
            $binary = implode('', $bytes);
            if ($binary !== '' && strncmp($binary, '%PDF', 4) === 0) {
                return $binary;
            }
        }

        return '';
    }

    private function sanitizeCreditReference(string $value): string
    {
        $value = strtoupper(preg_replace('/[^A-Z0-9]/', '', $value));
        if ($value === '') {
            $value = 'EX' . date('ymdHis');
        }
        return substr($value, 0, 20);
    }

    /** @return list<string> */
    private function splitAddressLines(string $address, int $maxLines, int $maxLen): array
    {
        $address = trim(preg_replace('/\s+/', ' ', $address));
        if ($address === '') {
            return [''];
        }

        $lines = [];
        $remaining = $address;
        while ($remaining !== '' && count($lines) < $maxLines) {
            if (strlen($remaining) <= $maxLen) {
                $lines[] = $remaining;
                break;
            }
            $chunk = substr($remaining, 0, $maxLen);
            $breakAt = strrpos($chunk, ' ');
            if ($breakAt !== false && $breakAt > 10) {
                $chunk = substr($chunk, 0, $breakAt);
            }
            $lines[] = trim($chunk);
            $remaining = trim(substr($remaining, strlen($chunk)));
        }

        while (count($lines) < $maxLines) {
            $lines[] = '';
        }

        return $lines;
    }
}
