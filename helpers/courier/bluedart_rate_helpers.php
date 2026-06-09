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
        'apex' => [
            ['product_code' => 'A', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart Apex'],
        ],
        'ground' => [
            ['product_code' => 'E', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart Ground'],
        ],
        'domesticpriority' => [
            ['product_code' => 'D', 'sub_product_code' => 'P', 'pack_type' => 'L', 'feature' => 'R', 'label' => 'Blue Dart Domestic Priority'],
        ],
    ];

    return $map[$normalized] ?? [];
}

/**
 * @return list<string>
 */
function bluedartSplitServiceTypeTokens(string $value): array
{
    $value = trim($value);
    if ($value === '') {
        return [];
    }

    $tokens = [];
    foreach (preg_split('/\s*,\s*/', $value) ?: [] as $part) {
        $part = trim($part);
        if ($part !== '') {
            $tokens[] = $part;
        }
    }

    return $tokens;
}

/**
 * Expand eShipz-style comma-separated serviceType into Blue Dart product rows.
 *
 * Example: "eTailCODAir,eTailPrePaidAir,Apex,Ground,DomesticPriority"
 *
 * @return list<array{product_code:string,sub_product_code:string,pack_type:string,feature:string,label:string}>
 */
function bluedartResolveProductsFromServiceTypeCatalog(string $catalog, string $defaultSubProduct = 'P'): array
{
    $rows = [];
    $seen = [];

    foreach (bluedartSplitServiceTypeTokens($catalog) as $token) {
        $mapped = bluedartMapServiceTypeToProducts($token);
        if ($mapped === []) {
            $parsed = bluedartParseServiceTypeCode($token, $defaultSubProduct);
            if ($parsed !== null) {
                $mapped = [$parsed];
            }
        }

        foreach ($mapped as $row) {
            $key = strtoupper((string) ($row['product_code'] ?? ''))
                . '_' . strtoupper((string) ($row['sub_product_code'] ?? ''));
            if ($key === '_' || isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $rows[] = $row;
        }
    }

    return $rows;
}

/**
 * Pick one product row from serviceType catalog for waybill generation.
 *
 * @return array{product_code:string,sub_product_code:string,pack_type:string,feature:string,label:string}|null
 */
function bluedartPickProductForShipment(string $catalog, bool $isCod, string $defaultSubProduct = 'P'): ?array
{
    $rows = bluedartResolveProductsFromServiceTypeCatalog($catalog, $defaultSubProduct);
    if ($rows === []) {
        return null;
    }

    $preferredTokens = $isCod
        ? ['etailcodair', 'apex', 'domesticpriority', 'ground', 'etailprepaidair']
        : ['etailprepaidair', 'apex', 'domesticpriority', 'ground', 'etailcodair'];

    foreach ($preferredTokens as $tokenKey) {
        foreach (bluedartSplitServiceTypeTokens($catalog) as $token) {
            $normalized = strtolower(preg_replace('/[^a-z0-9]/', '', $token));
            if ($normalized !== $tokenKey) {
                continue;
            }
            foreach ($rows as $row) {
                $sub = strtoupper((string) ($row['sub_product_code'] ?? ''));
                if ($isCod && $sub === 'C') {
                    return $row;
                }
                if (!$isCod && $sub === 'P') {
                    return $row;
                }
            }
        }
    }

    foreach ($rows as $row) {
        $sub = strtoupper((string) ($row['sub_product_code'] ?? ''));
        if ($isCod && $sub === 'C') {
            return $row;
        }
        if (!$isCod && $sub === 'P') {
            return $row;
        }
    }

    return $rows[0];
}

/**
 * Parse Blue Dart / eShipz "Service Type code" into product + sub-product.
 *
 * Accepts: "E", "A", "E_P", "E/P", "Surface", "Air", etc.
 *
 * @return array{product_code:string,sub_product_code:string,pack_type:string,feature:string,label:string}|null
 */
function bluedartParseServiceTypeCode(string $code, string $defaultSubProduct = 'P'): ?array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return null;
    }

    if (preg_match('/^([A-Z])[_\\/-]([PC])$/', $code, $m)) {
        return [
            'product_code' => $m[1],
            'sub_product_code' => $m[2],
            'pack_type' => 'L',
            'feature' => 'R',
            'label' => 'Blue Dart ' . $m[1] . '/' . $m[2],
        ];
    }

    if (strlen($code) === 1 && ctype_alpha($code)) {
        return [
            'product_code' => $code,
            'sub_product_code' => in_array($defaultSubProduct, ['P', 'C'], true) ? $defaultSubProduct : 'P',
            'pack_type' => 'L',
            'feature' => 'R',
            'label' => 'Blue Dart ' . $code,
        ];
    }

    $mapped = bluedartMapServiceTypeToProducts($code);
    if ($mapped !== []) {
        return $mapped[0];
    }

    return null;
}

/**
 * eShipz / Blue Dart account JSON uses "serviceType" as the key for service type code.
 *
 * @param array<string, mixed> $credentials
 */
function bluedartPickServiceTypeFromCredentials(array $credentials): string
{
    return bluedartPickCredentialScalar($credentials, [
        'serviceType',
        'ServiceType',
        'service_type',
        'service_type_code',
        'default_service_type',
    ]);
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
    $primaryServiceType = bluedartPickServiceTypeFromCredentials($credentials);
    if ($primaryServiceType !== '') {
        if (str_contains($primaryServiceType, ',')) {
            $defaultSub = strtoupper(trim((string) ($credentials['default_sub_product_code'] ?? 'P')));
            $catalogRows = bluedartResolveProductsFromServiceTypeCatalog($primaryServiceType, $defaultSub);
            if ($catalogRows !== []) {
                return $catalogRows;
            }
        }
        $sources[] = $primaryServiceType;
    }
    foreach (['allowed_service_types', 'default_product_code'] as $key) {
        $value = $credentials[$key] ?? null;
        if (is_array($value)) {
            foreach ($value as $item) {
                $sources[] = (string) $item;
            }
        } elseif (is_string($value) && trim($value) !== '') {
            $sources[] = $value;
        }
    }

    $defaultSub = strtoupper(trim((string) ($credentials['default_sub_product_code'] ?? 'P')));
    $rows = [];
    foreach ($sources as $serviceType) {
        $parsed = bluedartParseServiceTypeCode($serviceType, $defaultSub);
        if ($parsed !== null) {
            $rows[] = $parsed;
            continue;
        }
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
/**
 * Strip secrets from Blue Dart errors before showing to users or in API responses.
 */
function bluedartSanitizeErrorMessage(string $message): string
{
    $message = trim($message);
    if ($message === '') {
        return '';
    }

    $decoded = json_decode($message, true);
    if (is_array($decoded)) {
        $message = json_encode(bluedartRedactSensitiveData($decoded), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $message = is_string($message) ? $message : '';
    }

    $message = (string) preg_replace(
        '/"(clientID|ClientID|client_id|clientSecret|client_secret|consumer_key|consumer_secret|LicenceKey|licence_key|shipment_licence_key|LoginID|login_id|jwt_token|JWTToken|access_token|api_key|password|secret)"\s*:\s*"[^"]*"/i',
        '"$1":"[redacted]"',
        $message
    );

    $message = (string) preg_replace(
        '/\b(eyJ[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+)\b/',
        '[redacted-jwt]',
        $message
    );

    return trim($message);
}

/**
 * @param mixed $data
 * @return mixed
 */
function bluedartRedactSensitiveData($data)
{
    if (!is_array($data)) {
        return $data;
    }

    $sensitive = [
        'clientid', 'client_id', 'clientsecret', 'client_secret',
        'consumer_key', 'consumer_secret', 'licencekey', 'licence_key',
        'shipment_licence_key', 'tracking_licence_key', 'loginid', 'login_id',
        'jwt_token', 'jwttoken', 'token', 'access_token', 'api_key', 'password', 'secret',
    ];

    $redacted = [];
    foreach ($data as $key => $value) {
        $normalized = strtolower(preg_replace('/[^a-z0-9_]/', '', (string) $key));
        if (in_array($normalized, $sensitive, true)) {
            $redacted[$key] = '[redacted]';
            continue;
        }
        $redacted[$key] = is_array($value) ? bluedartRedactSensitiveData($value) : $value;
    }

    return $redacted;
}

/**
 * Redact licence/login fields and truncate large binary nodes in SOAP XML for UI display.
 */
function bluedartRedactSoapXml(string $xml): string
{
    if ($xml === '') {
        return '';
    }

    foreach (['LoginID', 'LicenceKey', 'ApiLicenceKey', 'ShipmentLicenceKey', 'TrackingLicenceKey'] as $tag) {
        $xml = (string) preg_replace(
            '/(<' . preg_quote($tag, '/') . '(?:\s[^>]*)?>)([^<]*)(<\/' . preg_quote($tag, '/') . '>)/i',
            '$1[redacted]$3',
            $xml
        );
    }

    return bluedartTruncateSoapForDisplay($xml);
}

/**
 * Shorten AWBPrintContent and overall payload for error popups.
 */
function bluedartTruncateSoapForDisplay(string $xml, int $maxLen = 12000): string
{
    if ($xml === '') {
        return '';
    }

    $xml = (string) preg_replace_callback(
        '/<(AWBPrintContent)(\s[^>]*)?>([\s\S]*?)<\/\1>/i',
        static function (array $matches): string {
            $content = (string) ($matches[3] ?? '');
            if (strlen($content) <= 500) {
                return $matches[0];
            }

            return '<' . $matches[1] . ($matches[2] ?? '') . '>[truncated label content, '
                . strlen($content) . ' chars]</' . $matches[1] . '>';
        },
        $xml
    );

    if (strlen($xml) <= $maxLen) {
        return $xml;
    }

    return substr($xml, 0, $maxLen)
        . "\n...[truncated, total " . strlen($xml) . ' chars]';
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

function bluedartIsPlaceholderCredentialValue(string $value): bool
{
    $value = trim($value);
    if ($value === '') {
        return true;
    }

    return preg_match('/^\(.*\)$/s', $value) === 1
        || preg_match('/from dhl developer portal|keep secret|your blue dart app/i', $value) === 1;
}

/**
 * DHL Developer Portal app credentials (Consumer Key + Secret). Not the same as login_id / licence_key.
 *
 * @param array<string, mixed> $credentials
 */
function bluedartHasDhlPortalCredentials(array $credentials): bool
{
    $normalized = bluedartNormalizeGatewayAuthConfig($credentials);
    $key = bluedartPickCredentialScalar($normalized, [
        'consumer_key',
        'ConsumerKey',
        'consumerKey',
        'client_id',
        'ClientID',
        'dhl_client_id',
        'dhl_consumer_key',
    ]);
    $secret = bluedartPickCredentialScalar($normalized, [
        'consumer_secret',
        'ConsumerSecret',
        'consumerSecret',
        'client_secret',
        'clientSecret',
        'dhl_client_secret',
        'dhl_consumer_secret',
    ]);

    return !bluedartIsPlaceholderCredentialValue($key)
        && !bluedartIsPlaceholderCredentialValue($secret);
}

function bluedartHasCachedJwtToken(array $credentials): bool
{
    $normalized = bluedartNormalizeGatewayAuthConfig($credentials);
    $token = bluedartPickCredentialScalar($normalized, ['jwt_token', 'JWTToken', 'jwtToken']);
    if ($token === '') {
        return false;
    }

    return str_starts_with($token, 'eyJ') && substr_count($token, '.') >= 2;
}

function bluedartDhlPortalSetupHint(): string
{
    return 'Blue Dart REST gateway needs DHL Developer Portal consumer_key and consumer_secret (register at developer.dhl.com). '
        . 'login_id and licence_key are only used in the API Profile — they cannot be used as JWT credentials.';
}

/**
 * JWT token request credential pairs from DHL Developer Portal only.
 *
 * @param array<string, mixed> $config
 * @return list<array{client_id:string,client_secret:string,label:string}>
 */
function bluedartBuildJwtAuthAttempts(array $config): array
{
    $config = bluedartNormalizeGatewayAuthConfig($config);

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

    if (
        !bluedartIsPlaceholderCredentialValue($explicitKey)
        && !bluedartIsPlaceholderCredentialValue($explicitSecret)
    ) {
        $push($explicitKey, $explicitSecret, 'dhl_portal');
    }

    return $attempts;
}

/**
 * Blue Dart account profile credentials (used inside every REST API payload).
 *
 * @param array<string, mixed> $credentials
 */
function bluedartHasProfileCredentials(array $credentials): bool
{
    $normalized = bluedartNormalizeGatewayAuthConfig($credentials);
    $loginId = bluedartPickCredentialScalar($normalized, ['login_id', 'LoginID']);
    $licence = bluedartPickCredentialScalar($normalized, [
        'shipment_licence_key',
        'licence_key',
        'LicenceKey',
    ]);

    return $loginId !== '' && $licence !== '';
}

/**
 * True when waybill can be created with configured API mode.
 *
 * @param array<string, mixed> $credentials
 */
function bluedartCanCreateWaybill(array $credentials): bool
{
    require_once __DIR__ . '/bluedart_legacy_soap.php';

    if (bluedartUsesLegacyApi($credentials)) {
        return bluedartHasProfileCredentials($credentials);
    }

    return bluedartHasCachedJwtToken($credentials) || bluedartHasDhlPortalCredentials($credentials);
}

/**
 * @param array<string, mixed> $credentials
 * @return array{ready:bool,message:string,hints:list<string>}
 */
function bluedartDescribeGatewayAuthStatus(array $credentials): array
{
    require_once __DIR__ . '/bluedart_legacy_soap.php';

    $normalized = bluedartNormalizeGatewayAuthConfig($credentials);
    $hasProfile = bluedartHasProfileCredentials($normalized);

    if (bluedartUsesLegacyApi($normalized)) {
        if ($hasProfile) {
            return ['ready' => true, 'message' => '', 'hints' => []];
        }

        return [
            'ready' => false,
            'message' => 'Blue Dart legacy API credentials are not configured.',
            'hints' => [
                'Add login_id and licence_key (or shipment_licence_key) from your Blue Dart / eShipz account.',
                'Also set customer_code, origin_area, and shipper details for waybill generation.',
            ],
        ];
    }

    $hasJwt = bluedartHasCachedJwtToken($normalized);
    $hasDhlPortal = bluedartHasDhlPortalCredentials($normalized);

    if ($hasJwt || $hasDhlPortal) {
        return ['ready' => true, 'message' => '', 'hints' => []];
    }

    $hints = [bluedartDhlPortalSetupHint()];
    if (!$hasProfile) {
        $hints[] = 'Also ensure login_id and licence_key are saved — they are required in the API Profile on every REST call.';
    }

    return [
        'ready' => false,
        'message' => 'Blue Dart REST gateway JWT is not configured.',
        'hints' => $hints,
    ];
}
