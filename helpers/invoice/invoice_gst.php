<?php

require_once __DIR__ . '/invoice_address_html.php';

function invoice_normalize_gst_state(?string $state): string
{
    return strtoupper(trim((string) $state));
}

function invoice_normalize_country_code(?string $country): string
{
    $country = strtoupper(trim((string) $country));
    if ($country === '' || $country === 'IND' || $country === 'INDIA') {
        return 'IN';
    }

    return strlen($country) >= 2 ? substr($country, 0, 2) : $country;
}

/**
 * Place of supply country: shipping country when shipping address exists, else billing country.
 */
function invoice_resolve_place_of_supply_country(?array $orderInfo): string
{
    if (!is_array($orderInfo)) {
        return 'IN';
    }

    if (invoice_order_info_has_shipping($orderInfo)) {
        $shippingCountry = invoice_normalize_country_code($orderInfo['shipping_country'] ?? '');
        if ($shippingCountry !== '') {
            return $shippingCountry;
        }
    }

    return invoice_normalize_country_code($orderInfo['country'] ?? 'IN');
}

function invoice_order_info_is_overseas(?array $orderInfo): bool
{
    return invoice_resolve_place_of_supply_country($orderInfo) !== 'IN';
}

/**
 * @param mixed $applyExportGst Flag from checkout modal: 1/0, true/false, or null when not set.
 */
function invoice_should_apply_gst(?array $orderInfo, $applyExportGst = null): bool
{
    if (!invoice_order_info_is_overseas($orderInfo)) {
        return true;
    }

    if ($applyExportGst === null || $applyExportGst === '') {
        return false;
    }

    if (is_string($applyExportGst)) {
        return !in_array(strtolower(trim($applyExportGst)), ['0', 'false', 'no', 'off'], true);
    }

    return !empty($applyExportGst);
}

/**
 * @param array<string, mixed> $invoice
 * @param array<string, mixed>|null $posDiscountMeta
 */
function invoice_should_apply_gst_for_invoice(array $invoice, $commanModel, ?array $posDiscountMeta = null): bool
{
    $orderInfoId = (int)($invoice['vp_order_info_id'] ?? 0);
    if ($orderInfoId <= 0 || !is_object($commanModel) || !method_exists($commanModel, 'getRecordById')) {
        return true;
    }

    $orderInfo = $commanModel->getRecordById('vp_order_info', $orderInfoId);

    return invoice_should_apply_gst(
        is_array($orderInfo) ? $orderInfo : null,
        is_array($posDiscountMeta) && array_key_exists('apply_export_gst', $posDiscountMeta)
            ? $posDiscountMeta['apply_export_gst']
            : null
    );
}

/**
 * @return array{cgst_rate: float, sgst_rate: float, igst_rate: float, use_igst: bool}
 */
function invoice_resolve_gst_component_plan(?array $orderInfo, float $gstRate, $applyExportGst = null): array
{
    if (!invoice_should_apply_gst($orderInfo, $applyExportGst)) {
        return [
            'cgst_rate' => 0.0,
            'sgst_rate' => 0.0,
            'igst_rate' => 0.0,
            'use_igst' => false,
        ];
    }

    $useIgst = invoice_order_info_is_overseas($orderInfo)
        || invoice_order_info_uses_igst($orderInfo, null, null);
    $rates = invoice_gst_component_rates($gstRate, $useIgst);
    $rates['use_igst'] = $useIgst;

    return $rates;
}

/**
 * Place of supply: shipping state when a shipping address exists, else billing state.
 */
function invoice_resolve_place_of_supply_state(?array $orderInfo): string
{
    if (!is_array($orderInfo)) {
        return '';
    }

    if (invoice_order_info_has_shipping($orderInfo)) {
        $shippingState = invoice_normalize_gst_state($orderInfo['shipping_state'] ?? '');
        if ($shippingState !== '') {
            return $shippingState;
        }
    }

    return invoice_normalize_gst_state($orderInfo['state'] ?? '');
}

function invoice_resolve_seller_state(?array $firm): string
{
    if (!is_array($firm)) {
        return '';
    }

    $state = invoice_normalize_gst_state($firm['state'] ?? '');
    if ($state !== '') {
        return $state;
    }

    $gst = preg_replace('/\s+/', '', strtoupper((string)($firm['gst'] ?? $firm['gstin'] ?? '')));
    if (preg_match('/^07/', $gst)) {
        return 'DELHI';
    }

    return '';
}

/**
 * Seller state for GST place-of-supply checks (app settings, GSTIN prefix, Delhi default).
 */
function invoice_resolve_firm_seller_state($commanModel = null): string
{
    require_once __DIR__ . '/../app_settings.php';
    $sellerState = invoice_resolve_seller_state(app_setting_firm_details());

    if ($sellerState === '') {
        $sellerState = 'DELHI';
    }

    return $sellerState;
}

function invoice_should_use_igst(?string $supplyState, ?string $sellerState): bool
{
    $supply = invoice_normalize_gst_state($supplyState);
    $seller = invoice_normalize_gst_state($sellerState);

    if ($supply === '' || $seller === '') {
        return false;
    }

    return $supply !== $seller;
}

function invoice_order_info_uses_igst(?array $orderInfo, ?array $firm = null, $commanModel = null): bool
{
    if ($firm === null) {
        $sellerState = invoice_resolve_firm_seller_state($commanModel);
    } else {
        $sellerState = invoice_resolve_seller_state($firm);
        if ($sellerState === '') {
            $sellerState = invoice_resolve_firm_seller_state($commanModel);
        }
    }

    return invoice_should_use_igst(
        invoice_resolve_place_of_supply_state($orderInfo),
        $sellerState
    );
}

/**
 * @param array<string, mixed> $invoice
 */
function invoice_resolve_uses_igst_for_invoice(array $invoice, $commanModel): ?bool
{
    $orderInfoId = (int)($invoice['vp_order_info_id'] ?? 0);
    if ($orderInfoId <= 0 || !is_object($commanModel) || !method_exists($commanModel, 'getRecordById')) {
        return null;
    }

    $orderInfo = $commanModel->getRecordById('vp_order_info', $orderInfoId);
    if (!is_array($orderInfo)) {
        return null;
    }

    require_once __DIR__ . '/../app_settings.php';
    $supplyState = invoice_resolve_place_of_supply_state($orderInfo);
    $sellerState = invoice_resolve_firm_seller_state($commanModel);
    if ($supplyState === '') {
        return null;
    }

    return invoice_should_use_igst($supplyState, $sellerState);
}

/**
 * @return array{cgst_rate: float, sgst_rate: float, igst_rate: float}
 */
function invoice_gst_component_rates(float $gstRate, bool $useIgst): array
{
    if ($useIgst) {
        return [
            'cgst_rate' => 0.0,
            'sgst_rate' => 0.0,
            'igst_rate' => round($gstRate, 4),
        ];
    }

    $halfRate = round($gstRate / 2, 4);

    return [
        'cgst_rate' => $halfRate,
        'sgst_rate' => $halfRate,
        'igst_rate' => 0.0,
    ];
}

/**
 * @return array{sgst: float, cgst: float, igst: float, sgst_rate: float, cgst_rate: float, igst_rate: float}
 */
function invoice_split_tax_total(float $taxTotal, float $taxRate, bool $useIgst): array
{
    if ($useIgst) {
        return [
            'sgst' => 0.0,
            'cgst' => 0.0,
            'igst' => round($taxTotal, 2),
            'sgst_rate' => 0.0,
            'cgst_rate' => 0.0,
            'igst_rate' => round($taxRate, 2),
        ];
    }

    $sgst = round($taxTotal / 2, 2);
    $cgst = round($taxTotal - $sgst, 2);
    $halfRate = round($taxRate / 2, 2);

    return [
        'sgst' => $sgst,
        'cgst' => $cgst,
        'igst' => 0.0,
        'sgst_rate' => $halfRate,
        'cgst_rate' => $halfRate,
        'igst_rate' => 0.0,
    ];
}

/**
 * @return array{sgst: float, cgst: float, igst: float, sgst_rate: float, cgst_rate: float, igst_rate: float}
 */
function invoice_compute_tax_breakdown_from_pretax(
    float $pretaxUnit,
    int $qty,
    float $taxRate,
    bool $useIgst
): array {
    $qty = max(1, $qty);
    $pretaxExtended = round($pretaxUnit * $qty, 2);
    $taxTotal = round($pretaxExtended * $taxRate / 100, 2);

    return invoice_split_tax_total($taxTotal, $taxRate, $useIgst);
}

/**
 * @return array{sgst: float, cgst: float, igst: float, sgst_rate: float, cgst_rate: float, igst_rate: float}
 */
function invoice_compute_tax_breakdown_from_incl_unit(
    float $inclUnit,
    int $qty,
    float $taxRate,
    bool $useIgst
): array {
    $qty = max(1, $qty);
    $lineIncl = round($inclUnit * $qty, 2);
    $pretaxUnit = $taxRate > 0
        ? round($inclUnit / (1 + ($taxRate / 100)), 4)
        : round($inclUnit, 4);
    $pretaxExtended = round($pretaxUnit * $qty, 2);
    $taxTotal = round($lineIncl - $pretaxExtended, 2);

    return invoice_split_tax_total($taxTotal, $taxRate, $useIgst);
}
