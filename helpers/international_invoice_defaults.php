<?php

require_once __DIR__ . '/courier/country_codes.php';

/**
 * Build default values for vp_invoices_international / IRN export fields.
 *
 * @param array<int, array<string, mixed>> $orders
 * @param array<string, mixed>|null $orderAddress vp_order_info row
 * @param array<string, mixed>|null $firm firm_details row
 * @param object|null $commanModel Tables model (currency_master lookup)
 * @param mysqli|null $conn
 * @return array<string, string|float>
 */
function buildInternationalInvoiceDefaults(
    array $orders,
    ?array $orderAddress,
    ?array $firm,
    $commanModel = null,
    $conn = null
): array {
    $order = $orders[0] ?? [];
    $addr = is_array($orderAddress) ? $orderAddress : [];

    $destCountryRaw = trim((string) ($addr['shipping_country'] ?? $addr['country'] ?? ''));
    $destCountryCode = normalizeCountryIso2($destCountryRaw, $conn);
    $destCountryRow = getCountryByIso2($destCountryCode, $conn);
    $destCountryName = trim((string) ($destCountryRow['name'] ?? ''));
    if ($destCountryName === '') {
        $destCountryName = $destCountryCode !== 'IN' ? $destCountryCode : '';
    }

    $destCity = trim((string) ($addr['shipping_city'] ?? $addr['city'] ?? ''));
    $finalDestination = $destCity;
    if ($destCountryName !== '') {
        $finalDestination = $finalDestination !== ''
            ? $finalDestination . ', ' . $destCountryName
            : $destCountryName;
    }

    $portOfDischarge = $destCity !== '' ? $destCity : $destCountryName;
    $loadingCity = trim((string) ($firm['city'] ?? 'New Delhi'));
    $portOfLoading = $loadingCity !== '' ? $loadingCity : 'INABG1';

    $currency = strtoupper(trim((string) ($order['currency'] ?? '')));
    if ($currency === '' || $currency === 'INR') {
        $currency = 'USD';
    }

    $usdExportRate = 0.0;
    if ($commanModel && method_exists($commanModel, 'getRecordByField')) {
        $usdRecord = $commanModel->getRecordByField('currency_master', 'currency_code', 'USD');
        if (is_array($usdRecord)) {
            $usdExportRate = (float) ($usdRecord['rate_export'] ?? 0);
        }
    }

    $orderNumber = trim((string) ($order['order_number'] ?? ''));

    return [
        'pre_carriage_by' => 'Air',
        'port_of_loading' => $portOfLoading,
        'port_of_discharge' => $portOfDischarge,
        'country_of_origin' => 'India',
        'country_of_final_destination' => $destCountryName,
        'final_destination' => $finalDestination,
        'usd_export_rate' => $usdExportRate,
        'ap_cost' => 0.0,
        'freight_charge' => 0.0,
        'insurance_charge' => 0.0,
        'shipping_bill_number' => $orderNumber !== '' ? $orderNumber : (string) random_int(100000, 999999),
        'shipping_bill_date' => date('Y-m-d'),
        'shipping_port' => 'INABG1',
        'shipping_ref_clm' => 'N',
        'shipping_currency' => $currency,
        'shipping_country_code' => $destCountryCode,
        'shipping_exp_duty' => 0.0,
    ];
}

/**
 * Fill missing international POST fields with computed defaults.
 *
 * @param array<string, mixed> $post
 * @return array<string, mixed>
 */
function mergeInternationalInvoiceDefaults(
    array $post,
    array $orders,
    ?array $orderAddress,
    ?array $firm,
    $commanModel = null,
    $conn = null
): array {
    $defaults = buildInternationalInvoiceDefaults($orders, $orderAddress, $firm, $commanModel, $conn);
    $numericFields = [
        'usd_export_rate',
        'ap_cost',
        'freight_charge',
        'insurance_charge',
        'shipping_exp_duty',
    ];

    foreach ($defaults as $field => $defaultValue) {
        $posted = $post[$field] ?? null;
        if (in_array($field, $numericFields, true)) {
            if ($posted === null || $posted === '' || !is_numeric($posted)) {
                $post[$field] = $defaultValue;
            }
            continue;
        }
        if ($posted === null || trim((string) $posted) === '') {
            $post[$field] = $defaultValue;
        }
    }

    return $post;
}
