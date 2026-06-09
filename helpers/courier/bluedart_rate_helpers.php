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
