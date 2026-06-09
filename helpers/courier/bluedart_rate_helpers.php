<?php

require_once __DIR__ . '/Support/CourierUiFormat.php';

/**
 * Blue Dart rate helpers — Shiprocket price fallback and service-type mapping.
 */

/** @return list<array{product_code:string,sub_product_code:string,pack_type:string,feature:string,label:string}> */
function bluedartMapServiceTypeToProducts(string $serviceType): array
{
    $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $serviceType));
    if ($normalized === '') {
        return [];
    }

    $map = [
        'etailprepaidair' => [
            ['product_code' => 'A', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart eTail PrePaid Air'],
        ],
        'etailcodair' => [
            ['product_code' => 'A', 'sub_product_code' => 'C', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart eTail COD Air'],
        ],
        'etailprepaidground' => [
            ['product_code' => 'E', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart eTail PrePaid Ground'],
        ],
        'etailprepaidsurface' => [
            ['product_code' => 'E', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart eTail PrePaid Surface'],
        ],
        'etailsurface' => [
            ['product_code' => 'E', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart eTail Surface'],
        ],
        'surface' => [
            ['product_code' => 'E', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart Surface'],
        ],
        'air' => [
            ['product_code' => 'A', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart Air'],
        ],
    ];

    return $map[$normalized] ?? [];
}

/**
 * @param array<string, mixed> $credentials
 * @return list<array<string, mixed>>
 */
function bluedartResolveRateProductsFromCredentials(array $credentials): array
{
    $configured = $credentials['rate_products'] ?? [];
    if (is_array($configured) && $configured !== []) {
        $rows = [];
        foreach ($configured as $row) {
            if (is_array($row) && trim((string) ($row['product_code'] ?? '')) !== '') {
                $rows[] = $row;
            }
        }
        if ($rows !== []) {
            return $rows;
        }
    }

    $sources = [];
    foreach (['allowed_service_types', 'default_service_type', 'default_product_code'] as $key) {
        $value = $credentials[$key] ?? null;
        if (is_array($value)) {
            foreach ($value as $item) {
                $sources[] = (string) $item;
            }
        } elseif (is_string($value) && trim($value) !== '') {
            $sources[] = $value;
        }
    }

    $rows = [];
    foreach ($sources as $serviceType) {
        foreach (bluedartMapServiceTypeToProducts($serviceType) as $mapped) {
            $rows[] = $mapped;
        }
    }

    return $rows;
}

/** @param mixed $value */
function bluedartParseInrAmount($value): ?float
{
    if ($value === null || $value === '') {
        return null;
    }
    if (is_numeric($value)) {
        $amount = (float) $value;
        return $amount > 0 ? $amount : null;
    }
    return null;
}

/**
 * @param array<string, mixed> $serviceabilityPayload Shiprocket serviceability API `data` key
 * @return list<array{name:string,price:float,etd:string,rating:float,id:mixed}>
 */
function bluedartExtractShiprocketQuotes(array $serviceabilityPayload): array
{
    $companies = $serviceabilityPayload['data']['available_courier_companies'] ?? [];
    if (!is_array($companies)) {
        return [];
    }

    $quotes = [];
    foreach ($companies as $row) {
        if (!is_array($row)) {
            continue;
        }
        $name = trim((string) ($row['courier_name'] ?? ''));
        if ($name === '' || !preg_match('/blue\s*dart|bluedart/i', $name)) {
            continue;
        }
        $price = bluedartParseInrAmount($row['freight_charge'] ?? $row['rate'] ?? null);
        if ($price === null) {
            continue;
        }
        $quotes[] = [
            'id' => $row['courier_company_id'] ?? null,
            'name' => $name,
            'price' => $price,
            'etd' => (string) ($row['etd'] ?? $row['estimated_delivery_days'] ?? 'N/A'),
            'rating' => (float) ($row['rating'] ?? 0),
        ];
    }

    return $quotes;
}

/**
 * @param array<string, mixed> $gatewayResult
 * @param list<array{name:string,price:float,etd?:string,rating?:float,id?:mixed}> $shiprocketQuotes
 * @return array<string, mixed>
 */
function bluedartEnrichGatewayResultWithShiprocketPrices(array $gatewayResult, array $shiprocketQuotes): array
{
    if ($shiprocketQuotes === []) {
        return $gatewayResult;
    }

    if (empty($gatewayResult['success']) || !is_array($gatewayResult['couriers'] ?? null)) {
        return bluedartBuildGatewayResultFromShiprocket($shiprocketQuotes, $gatewayResult);
    }

    $couriers = $gatewayResult['couriers'];
    foreach ($couriers as $i => $courier) {
        if (!is_array($courier)) {
            continue;
        }
        $match = bluedartMatchShiprocketQuoteForCourier($courier, $shiprocketQuotes);
        if ($match === null) {
            continue;
        }
        $couriers[$i]['price'] = $match['price'];
        $couriers[$i]['price_source'] = 'shiprocket';
        $couriers[$i]['price_label'] = 'Price via Shiprocket';
        $metadata = is_array($courier['metadata'] ?? null) ? $courier['metadata'] : [];
        $metadata['price_source'] = 'shiprocket';
        $metadata['price_label'] = 'Price via Shiprocket';
        $metadata['shiprocket_courier_id'] = $match['id'] ?? null;
        $metadata['shiprocket_courier_name'] = $match['name'] ?? '';
        if (!empty($match['rating'])) {
            $couriers[$i]['rating'] = (float) $match['rating'];
        }
        $couriers[$i]['metadata'] = $metadata;
    }

    $gatewayResult['couriers'] = $couriers;
    return $gatewayResult;
}

/**
 * @param list<array{name:string,price:float,etd?:string,rating?:float,id?:mixed}> $shiprocketQuotes
 * @param array<string, mixed> $priorResult
 * @return array<string, mixed>
 */
function bluedartBuildGatewayResultFromShiprocket(array $shiprocketQuotes, array $priorResult = []): array
{
    $accountId = (int) ($priorResult['debug']['account_id'] ?? 0);
    $couriers = [];
    foreach ($shiprocketQuotes as $idx => $quote) {
        $slug = preg_replace('/[^a-z0-9]+/i', '_', strtolower($quote['name'])) ?: ('sr_' . $idx);
        $couriers[] = [
            'id' => 'bluedart_sr_' . ($accountId > 0 ? $accountId . '_' : '') . $slug,
            'name' => $quote['name'],
            'price' => (float) $quote['price'],
            'currency' => 'INR',
            'etd' => (string) ($quote['etd'] ?? 'N/A'),
            'rating' => (float) ($quote['rating'] ?? 0),
            'price_source' => 'shiprocket',
            'price_label' => 'Price via Shiprocket',
            'partner_code' => 'bluedart',
            'partner_account_id' => $accountId,
            'product_type' => strtoupper($slug),
            'service_code' => strtoupper($slug),
            'metadata' => [
                'price_source' => 'shiprocket',
                'price_label' => 'Price via Shiprocket',
                'etd_source' => 'shiprocket',
                'shiprocket_courier_id' => $quote['id'] ?? null,
                'shiprocket_courier_name' => $quote['name'] ?? '',
            ],
        ];
    }

    if ($couriers === []) {
        return $priorResult;
    }

    return [
        'success' => true,
        'provider' => 'bluedart',
        'couriers' => CourierUiFormat::formatQuotes($couriers),
        'debug' => array_merge(
            is_array($priorResult['debug'] ?? null) ? $priorResult['debug'] : [],
            ['shiprocket_fallback' => true, 'shiprocket_quotes' => $shiprocketQuotes]
        ),
    ];
}

/**
 * @param array<string, mixed> $courier
 * @param list<array{name:string,price:float,etd?:string,rating?:float,id?:mixed}> $shiprocketQuotes
 * @return array{name:string,price:float,etd?:string,rating?:float,id?:mixed}|null
 */
function bluedartMatchShiprocketQuoteForCourier(array $courier, array $shiprocketQuotes): ?array
{
    if (count($shiprocketQuotes) === 1) {
        return $shiprocketQuotes[0];
    }

    $haystack = strtolower(
        (string) ($courier['name'] ?? '')
        . ' ' . (string) ($courier['product_type'] ?? '')
        . ' ' . (string) ($courier['service_code'] ?? '')
    );

    $best = null;
    $bestScore = -1;
    foreach ($shiprocketQuotes as $quote) {
        $name = strtolower((string) ($quote['name'] ?? ''));
        $score = 0;
        if (str_contains($haystack, 'air') && str_contains($name, 'air')) {
            $score += 3;
        }
        if ((str_contains($haystack, 'surface') || str_contains($haystack, 'ground') || str_contains($haystack, '_e_'))
            && (str_contains($name, 'surface') || str_contains($name, 'ground'))) {
            $score += 3;
        }
        if (str_contains($name, 'blue') && str_contains($haystack, 'blue')) {
            $score += 1;
        }
        if ($score > $bestScore) {
            $bestScore = $score;
            $best = $quote;
        }
    }

    return $best ?? $shiprocketQuotes[0];
}

/**
 * Resolve Blue Dart waybill shipper codes from credentials (with pincode API fallback for origin_area).
 *
 * @param array<string, mixed> $credentials
 * @return array{customer_code:string,origin_area:string,origin_area_source:string,errors:list<string>}
 */
function bluedartResolveWaybillCredentials(BlueDartService $service, array $credentials, string $shipperPincode, bool $isCod): array
{
    $errors = [];
    $customerCode = bluedartPickCredentialScalar($credentials, [
        'customer_code',
        'CustomerCode',
        'customerCode',
    ]);

    if ($customerCode === '' && $isCod) {
        $customerCode = bluedartPickCredentialScalar($credentials, [
            'cod_customer_code',
            'codCustomerCode',
            'CODCustomerCode',
        ]);
    }

    $originArea = bluedartPickCredentialScalar($credentials, [
        'origin_area',
        'OriginArea',
        'customer_area',
        'pickup_area',
        'shipper_area',
    ]);
    $originAreaSource = $originArea !== '' ? 'credentials' : '';

    if ($originArea === '') {
        $originArea = $service->resolveOriginAreaForPincode($shipperPincode);
        if ($originArea !== '') {
            $originAreaSource = 'pincode_api';
        }
    }

    if ($customerCode === '') {
        $errors[] = 'customer_code (6-digit Blue Dart account code, e.g. 851756)';
    }
    if ($originArea === '') {
        $errors[] = 'origin_area (3-letter area code, e.g. DEL for Delhi — or ensure shipper pincode resolves via Blue Dart pincode API)';
    }

    return [
        'customer_code' => $customerCode,
        'origin_area' => $originArea,
        'origin_area_source' => $originAreaSource,
        'errors' => $errors,
    ];
}

/**
 * @param array<string, mixed> $credentials
 * @param list<string> $keys
 */
function bluedartPickCredentialScalar(array $credentials, array $keys): string
{
    foreach ($keys as $key) {
        if (!array_key_exists($key, $credentials)) {
            continue;
        }
        $value = trim((string) $credentials[$key]);
        if ($value !== '') {
            return $value;
        }
    }

    return '';
}

/**
 * Merge DHL gateway auth fields from common key names / nested JSON shapes.
 *
 * @param array<string, mixed> $config
 * @return array<string, mixed>
 */
function bluedartNormalizeGatewayAuthConfig(array $config): array
{
    $sources = [$config];
    foreach (['api', 'auth', 'dhl', 'gateway', 'credentials'] as $nestedKey) {
        if (!empty($config[$nestedKey]) && is_array($config[$nestedKey])) {
            $sources[] = $config[$nestedKey];
        }
    }

    $consumerKey = '';
    $consumerSecret = '';
    $jwtToken = '';
    foreach ($sources as $source) {
        if ($consumerKey === '') {
            $consumerKey = bluedartPickCredentialScalar($source, [
                'consumer_key',
                'ConsumerKey',
                'consumerKey',
                'client_id',
                'clientId',
                'ClientID',
                'ClientId',
                'api_key',
                'APIKey',
                'dhl_client_id',
                'dhl_consumer_key',
            ]);
        }
        if ($consumerSecret === '') {
            $consumerSecret = bluedartPickCredentialScalar($source, [
                'consumer_secret',
                'ConsumerSecret',
                'consumerSecret',
                'client_secret',
                'clientSecret',
                'ClientSecret',
                'api_secret',
                'APISecret',
                'dhl_client_secret',
                'dhl_consumer_secret',
            ]);
        }
        if ($jwtToken === '') {
            $jwtToken = bluedartPickCredentialScalar($source, [
                'jwt_token',
                'JWTToken',
                'jwtToken',
                'jwt',
                'access_token',
                'accessToken',
                'bearer_token',
            ]);
        }
    }

    if ($consumerKey !== '') {
        $config['consumer_key'] = $consumerKey;
    }
    if ($consumerSecret !== '') {
        $config['consumer_secret'] = $consumerSecret;
    }
    if ($jwtToken !== '') {
        $config['jwt_token'] = $jwtToken;
    }

    return $config;
}

/**
 * JWT token request credential pairs, in try order.
 *
 * When DHL portal consumer_key/secret are absent, falls back to:
 *   consumer_key ≈ login_id (then customer_code), consumer_secret ≈ licence_key
 *
 * @param array<string, mixed> $config
 * @return list<array{client_id:string,client_secret:string,label:string}>
 */
function bluedartBuildJwtAuthAttempts(array $config): array
{
    $config = bluedartNormalizeGatewayAuthConfig($config);

    $licenceKey = bluedartPickCredentialScalar($config, [
        'shipment_licence_key',
        'licence_key',
        'LicenceKey',
        'tracking_licence_key',
    ]);
    $loginId = bluedartPickCredentialScalar($config, ['login_id', 'LoginID']);
    $customerCode = bluedartPickCredentialScalar($config, ['customer_code', 'CustomerCode', 'customerCode']);

    $explicitKey = bluedartPickCredentialScalar($config, [
        'consumer_key',
        'ConsumerKey',
        'consumerKey',
        'client_id',
        'ClientID',
        'dhl_client_id',
        'dhl_consumer_key',
    ]);
    $explicitSecret = bluedartPickCredentialScalar($config, [
        'consumer_secret',
        'ConsumerSecret',
        'consumerSecret',
        'client_secret',
        'clientSecret',
        'dhl_client_secret',
        'dhl_consumer_secret',
    ]);

    $attempts = [];
    $seen = [];

    $push = static function (string $clientId, string $clientSecret, string $label) use (&$attempts, &$seen): void {
        $clientId = trim($clientId);
        $clientSecret = trim($clientSecret);
        if ($clientId === '' || $clientSecret === '') {
            return;
        }
        $sig = strtolower($clientId) . '|' . $clientSecret;
        if (isset($seen[$sig])) {
            return;
        }
        $seen[$sig] = true;
        $attempts[] = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'label' => $label,
        ];
    };

    if ($explicitKey !== '' && $explicitSecret !== '') {
        $push($explicitKey, $explicitSecret, 'dhl_portal');
    }
    if ($loginId !== '' && $licenceKey !== '') {
        $push($loginId, $licenceKey, 'login_id_licence_key');
    }
    if ($customerCode !== '' && $licenceKey !== '') {
        $push($customerCode, $licenceKey, 'customer_code_licence_key');
    }

    return $attempts;
}

/**
 * @param array<string, mixed> $credentials
 * @return array{ready:bool,message:string,hints:list<string>}
 */
function bluedartDescribeGatewayAuthStatus(array $credentials): array
{
    $normalized = bluedartNormalizeGatewayAuthConfig($credentials);
    $hasJwt = bluedartPickCredentialScalar($normalized, ['jwt_token', 'JWTToken']) !== '';
    $attempts = bluedartBuildJwtAuthAttempts($normalized);
    $hasProfile = bluedartPickCredentialScalar($normalized, ['login_id', 'LoginID']) !== ''
        && bluedartPickCredentialScalar($normalized, ['shipment_licence_key', 'licence_key']) !== '';

    if ($hasJwt || $attempts !== []) {
        return ['ready' => true, 'message' => '', 'hints' => []];
    }

    $hints = [
        'Set login_id + licence_key (mapped to JWT as consumer_key / consumer_secret), or add DHL Developer Portal consumer_key + consumer_secret, or paste jwt_token.',
    ];
    if (!$hasProfile) {
        $hints[] = 'Ensure login_id and licence_key (or shipment_licence_key) are saved in Courier accounts.';
    }

    return [
        'ready' => false,
        'message' => 'Blue Dart REST gateway authentication is not configured.',
        'hints' => $hints,
    ];
}
