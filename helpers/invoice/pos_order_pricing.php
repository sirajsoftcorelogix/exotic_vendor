<?php

/**
 * vp_orders stores finalprice and itemprice as per-unit GST-inclusive amounts
 * (see pos_payment_resolve_order_total: SUM(finalprice * quantity)).
 */

function pos_order_inclusive_unit_price(array $row, string $kind = 'disc'): float
{
    if ($kind === 'list') {
        $unit = (float)($row['itemprice'] ?? 0);
        if ($unit <= 0) {
            $unit = (float)($row['finalprice'] ?? 0);
        }

        return max(0.0, $unit);
    }

    $unit = (float)($row['finalprice'] ?? 0);
    if ($unit <= 0) {
        $unit = (float)($row['itemprice'] ?? 0);
    }

    return max(0.0, $unit);
}

function pos_order_inclusive_line_total(array $row, string $kind = 'disc'): float
{
    $qty = max(1, (int)($row['quantity'] ?? 1));

    return round(pos_order_inclusive_unit_price($row, $kind) * $qty, 2);
}

function pos_order_pretax_unit_price(array $row, string $kind = 'disc'): float
{
    $incl = pos_order_inclusive_unit_price($row, $kind);
    if ($incl <= 0) {
        return 0.0;
    }

    $gst = (float)($row['gst'] ?? 0);
    if ($gst > 0) {
        return round($incl / (1 + ($gst / 100)), 4);
    }

    return round($incl, 4);
}

/**
 * @return array<string, mixed>
 */
function pos_invoice_parse_notes_payload(?string $notes): array
{
    if ($notes === null || trim($notes) === '') {
        return [];
    }

    $decoded = json_decode($notes, true);

    return is_array($decoded) ? $decoded : [];
}

/**
 * @return list<array<string, mixed>>
 */
function pos_invoice_parse_line_items_meta(?string $notes): array
{
    $lines = pos_invoice_parse_notes_payload($notes)['line_items'] ?? null;

    return is_array($lines) ? $lines : [];
}

/**
 * @return array<string, mixed>
 */
function pos_invoice_parse_discount_meta(?string $notes): array
{
    $pos = pos_invoice_parse_notes_payload($notes)['pos_discounts'] ?? null;

    return is_array($pos) ? $pos : [];
}

function pos_order_line_meta_lookup_key(string $itemCode, string $size = '', string $color = ''): string
{
    return strtolower(trim($itemCode)) . '|' . strtolower(trim($size)) . '|' . strtolower(trim($color));
}

/**
 * @param list<array<string, mixed>> $lineItemsMeta
 * @return array<string, mixed>|null
 */
function pos_order_line_meta_for_item(array $item, int $index, array $lineItemsMeta): ?array
{
    if (isset($lineItemsMeta[$index]) && is_array($lineItemsMeta[$index])) {
        return $lineItemsMeta[$index];
    }

    foreach ($lineItemsMeta as $meta) {
        if (!is_array($meta)) {
            continue;
        }
        if (trim((string)($meta['item_code'] ?? '')) !== trim((string)($item['item_code'] ?? ''))) {
            continue;
        }
        if (trim((string)($meta['size'] ?? '')) !== trim((string)($item['size'] ?? ''))) {
            continue;
        }
        if (trim((string)($meta['color'] ?? '')) !== trim((string)($item['color'] ?? ''))) {
            continue;
        }

        return $meta;
    }

    return null;
}

/**
 * Match invoice PDF line columns: list unit (incl.), line taxable (pretax), GST, discount, chargeable (incl.).
 *
 * @param array<string, mixed> $orderRow
 * @param array{
 *   use_igst?: bool,
 *   apply_gst?: bool,
 *   list_incl_unit?: float|null,
 *   disc_incl_unit?: float|null
 * } $options
 * @return array{
 *   listing_price_unit: float,
 *   taxable_value: float,
 *   total_gst: float,
 *   discount_amount: float,
 *   chargeable_value: float
 * }
 */
function pos_order_line_display_pricing(array $orderRow, array $options = []): array
{
    require_once __DIR__ . '/invoice_gst.php';

    $qty = max(1, (int)($orderRow['quantity'] ?? 1));
    $applyGst = !array_key_exists('apply_gst', $options) || !empty($options['apply_gst']);
    $useIgst = !empty($options['use_igst']);
    $taxRate = $applyGst ? (float)($orderRow['gst'] ?? 0) : 0.0;

    $listInclUnit = isset($options['list_incl_unit']) && (float)$options['list_incl_unit'] > 0
        ? (float)$options['list_incl_unit']
        : pos_order_inclusive_unit_price($orderRow, 'list');
    $discInclUnit = isset($options['disc_incl_unit']) && (float)$options['disc_incl_unit'] > 0
        ? (float)$options['disc_incl_unit']
        : pos_order_inclusive_unit_price($orderRow, 'disc');

    if ($listInclUnit < $discInclUnit) {
        $listInclUnit = $discInclUnit;
    }

    $listingPriceUnit = round($listInclUnit, 2);
    $chargeableValue = round($discInclUnit * $qty, 2);
    $listLineTotal = round($listInclUnit * $qty, 2);
    $discountAmount = max(0.0, round($listLineTotal - $chargeableValue, 2));

    $taxableUnit = $taxRate > 0
        ? round($discInclUnit / (1 + ($taxRate / 100)), 2)
        : round($discInclUnit, 2);
    $taxableValue = round($taxableUnit * $qty, 2);

    if ($applyGst && $taxRate > 0) {
        $taxBreakdown = invoice_compute_tax_breakdown_from_incl_unit($discInclUnit, $qty, $taxRate, $useIgst);
        $totalGst = round(
            (float)($taxBreakdown['sgst'] ?? 0)
            + (float)($taxBreakdown['cgst'] ?? 0)
            + (float)($taxBreakdown['igst'] ?? 0),
            2
        );
    } else {
        $totalGst = 0.0;
    }

    return [
        'listing_price_unit' => $listingPriceUnit,
        'taxable_value' => $taxableValue,
        'total_gst' => $totalGst,
        'discount_amount' => $discountAmount,
        'chargeable_value' => $chargeableValue,
    ];
}

/**
 * @param list<array<string, mixed>> $orderLines
 * @param array<string, mixed>|null $invoice
 * @param array<string, mixed>|null $orderInfo
 * @return array<int, array<string, float>>
 */
function pos_order_build_line_display_pricing_map(array $orderLines, ?array $invoice, ?array $orderInfo, $commanModel = null): array
{
    require_once __DIR__ . '/invoice_gst.php';

    $lineItemsMeta = [];
    $posDiscountMeta = [];
    if (is_array($invoice)) {
        $lineItemsMeta = pos_invoice_parse_line_items_meta($invoice['notes'] ?? null);
        $posDiscountMeta = pos_invoice_parse_discount_meta($invoice['notes'] ?? null);
    }

    $applyGst = is_array($invoice)
        ? invoice_should_apply_gst_for_invoice($invoice, $commanModel, $posDiscountMeta !== [] ? $posDiscountMeta : null)
        : invoice_should_apply_gst($orderInfo);
    $resolvedUseIgst = is_array($invoice)
        ? invoice_resolve_uses_igst_for_invoice($invoice, $commanModel)
        : null;
    $useIgst = $resolvedUseIgst ?? invoice_order_info_uses_igst($orderInfo, null, $commanModel);

    $pricingByLineId = [];
    foreach ($orderLines as $index => $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }

        $meta = pos_order_line_meta_for_item($orderRow, (int)$index, $lineItemsMeta);
        $listOverride = is_array($meta) ? (float)($meta['list_unit_incl'] ?? 0) : 0.0;
        $discOverride = is_array($meta) ? (float)($meta['discounted_unit_incl'] ?? 0) : 0.0;

        $lineId = (int)($orderRow['id'] ?? 0);
        if ($lineId <= 0) {
            $lineId = (int)$index;
        }

        $pricingByLineId[$lineId] = pos_order_line_display_pricing($orderRow, [
            'use_igst' => $useIgst,
            'apply_gst' => $applyGst,
            'list_incl_unit' => $listOverride > 0 ? $listOverride : null,
            'disc_incl_unit' => $discOverride > 0 ? $discOverride : null,
        ]);
    }

    return $pricingByLineId;
}

function pos_order_format_pricing_amount(float $amount): string
{
    return number_format($amount, 2);
}
