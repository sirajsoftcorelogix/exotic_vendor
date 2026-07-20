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

    require_once __DIR__ . '/pos_invoice_line_calculation.php';
    $orderLevelDisc = pos_invoice_order_level_discount_total($posDiscountMeta);
    $excelAdjusted = [];
    if ($orderLevelDisc > 0.001) {
        $calcInput = [];
        foreach ($orderLines as $index => $orderRow) {
            if (!is_array($orderRow)) {
                continue;
            }
            $meta = pos_order_line_meta_for_item($orderRow, (int)$index, $lineItemsMeta);
            $listOverride = is_array($meta) ? (float)($meta['list_unit_incl'] ?? 0) : 0.0;
            $listUnit = $listOverride > 0 ? $listOverride : pos_order_inclusive_unit_price($orderRow, 'list');
            if ($listUnit <= 0) {
                continue;
            }
            $calcInput[$index] = [
                'list_incl_unit' => $listUnit,
                'disc_incl_unit' => 0.0,
                'qty' => max(1, (int)($orderRow['quantity'] ?? 1)),
            ];
        }
        if ($calcInput !== []) {
            $adjustedRows = pos_invoice_apply_list_price_order_discount(array_values($calcInput), $orderLevelDisc);
            $calcKeys = array_keys($calcInput);
            foreach ($adjustedRows as $i => $row) {
                $excelAdjusted[$calcKeys[$i]] = $row;
            }
        }
    }

    $pricingByLineId = [];
    foreach ($orderLines as $index => $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }

        $meta = pos_order_line_meta_for_item($orderRow, (int)$index, $lineItemsMeta);
        $listOverride = is_array($meta) ? (float)($meta['list_unit_incl'] ?? 0) : 0.0;
        $discOverride = is_array($meta) ? (float)($meta['discounted_unit_incl'] ?? 0) : 0.0;
        if (isset($excelAdjusted[$index])) {
            $listOverride = (float)($excelAdjusted[$index]['list_incl_unit'] ?? $listOverride);
            $discOverride = (float)($excelAdjusted[$index]['disc_incl_unit'] ?? $discOverride);
        }

        $lineId = (int)($orderRow['id'] ?? 0);
        if ($lineId <= 0) {
            $lineId = (int)$index;
        }

        $pricing = pos_order_line_display_pricing($orderRow, [
            'use_igst' => $useIgst,
            'apply_gst' => $applyGst,
            'list_incl_unit' => $listOverride > 0 ? $listOverride : null,
            'disc_incl_unit' => $discOverride > 0 ? $discOverride : null,
        ]);

        $pricingByLineId[$lineId] = pos_order_enrich_line_display_pricing($orderRow, $pricing, [
            'apply_gst' => $applyGst,
            'use_igst' => $useIgst,
        ]);
    }

    return $pricingByLineId;
}

/**
 * @return list<array{name: string, unit_incl: float, line_incl: float}>
 */
function pos_order_line_addon_rows(array $orderRow): array
{
    require_once __DIR__ . '/../../models/order/order.php';

    $qty = max(1, (int)($orderRow['quantity'] ?? 1));
    $parsed = Order::parseVendorOrderLineAddonsList($orderRow['addons'] ?? null);
    $rows = [];
    foreach ($parsed as $addon) {
        $unit = (float)($addon['price'] ?? 0);
        $rows[] = [
            'name' => (string)($addon['name'] ?? ''),
            'unit_incl' => round($unit, 2),
            'line_incl' => round($unit * $qty, 2),
        ];
    }

    return $rows;
}

function pos_order_line_addons_total(array $orderRow): float
{
    $total = 0.0;
    foreach (pos_order_line_addon_rows($orderRow) as $row) {
        $total += (float)($row['line_incl'] ?? 0);
    }

    return round($total, 2);
}

/**
 * Build base + addon list-price components (GST-inclusive, before custom_reduce).
 *
 * @return list<array{type: string, name: string, list_incl: float}>
 */
function pos_order_build_pricing_components(array $orderRow, float $baseListIncl): array
{
    $baseName = trim((string)($orderRow['title'] ?? ''));
    if ($baseName === '') {
        $baseName = 'Base item';
    }

    $components = [[
        'type' => 'base',
        'name' => $baseName,
        'list_incl' => round($baseListIncl, 2),
    ]];

    foreach (pos_order_line_addon_rows($orderRow) as $addon) {
        $components[] = [
            'type' => 'addon',
            'name' => (string)($addon['name'] ?? ''),
            'list_incl' => round((float)($addon['line_incl'] ?? 0), 2),
        ];
    }

    return $components;
}

/**
 * Allocate fixed custom_reduce proportionally by list price (matches POS spreadsheet).
 *
 * @param list<array{type: string, name: string, list_incl: float}> $components
 * @return list<array<string, float|string>>
 */
function pos_order_apply_proportional_custom_reduce(array $components, float $customReduce): array
{
    $totalList = 0.0;
    foreach ($components as $component) {
        $totalList += (float)($component['list_incl'] ?? 0);
    }
    $totalList = round($totalList, 2);
    $customReduce = max(0.0, round($customReduce, 2));

    if ($totalList <= 0.0) {
        return $components;
    }

    $allocated = 0.0;
    $count = count($components);
    $result = [];

    foreach ($components as $index => $component) {
        $listIncl = round((float)($component['list_incl'] ?? 0), 2);
        $row = $component;
        $row['discount_pct'] = round(100 * $listIncl / $totalList, 4);

        if ($customReduce <= 0.0) {
            $row['discount_value'] = 0.0;
        } elseif ($index === $count - 1) {
            $row['discount_value'] = round($customReduce - $allocated, 2);
        } else {
            $row['discount_value'] = round($customReduce * ($listIncl / $totalList), 2);
            $allocated += (float)$row['discount_value'];
        }

        $row['discounted_incl'] = round($listIncl - (float)$row['discount_value'], 2);
        $result[] = $row;
    }

    return $result;
}

/**
 * @param list<array<string, float|string>> $components
 * @return array{taxable_value: float, total_gst: float, components: list<array<string, float|string>>}
 */
function pos_order_compute_component_tax_rows(array $components, float $gstRate, bool $applyGst): array
{
    $totalTaxable = 0.0;
    $totalGst = 0.0;
    $gstRate = max(0.0, $gstRate);
    $result = [];

    foreach ($components as $component) {
        $row = $component;
        $discIncl = round((float)($row['discounted_incl'] ?? 0), 2);

        if ($applyGst && $gstRate > 0 && $discIncl > 0) {
            $taxable = round($discIncl / (1 + ($gstRate / 100)), 2);
            $gst = round($discIncl - $taxable, 2);
        } else {
            $taxable = $discIncl;
            $gst = 0.0;
        }

        $row['taxable_value'] = $taxable;
        $row['total_gst'] = $gst;
        $row['line_total'] = $discIncl;
        $result[] = $row;
        $totalTaxable += $taxable;
        $totalGst += $gst;
    }

    return [
        'taxable_value' => round($totalTaxable, 2),
        'total_gst' => round($totalGst, 2),
        'components' => $result,
    ];
}

/**
 * Add addon totals and proportionally allocated custom_reduce (GST-inclusive API amounts).
 *
 * @param array<string, mixed> $orderRow
 * @param array<string, mixed> $pricing
 * @param array{apply_gst?: bool, use_igst?: bool} $options
 * @return array<string, mixed>
 */
function pos_order_enrich_line_display_pricing(array $orderRow, array $pricing, array $options = []): array
{
    $addonRows = pos_order_line_addon_rows($orderRow);
    $addonsTotal = pos_order_line_addons_total($orderRow);
    $customReduce = max(0.0, round((float)($orderRow['custom_reduce'] ?? 0), 2));
    $baseListIncl = round((float)($pricing['chargeable_value'] ?? 0), 2);

    $applyGst = !array_key_exists('apply_gst', $options) || !empty($options['apply_gst']);
    $gstRate = $applyGst ? (float)($orderRow['gst'] ?? 0) : 0.0;

    $components = pos_order_build_pricing_components($orderRow, $baseListIncl);
    $components = pos_order_apply_proportional_custom_reduce($components, $customReduce);
    $taxResult = pos_order_compute_component_tax_rows($components, $gstRate, $applyGst);
    $components = $taxResult['components'];

    $grossIncl = 0.0;
    $netChargeable = 0.0;
    foreach ($components as $component) {
        $grossIncl += (float)($component['list_incl'] ?? 0);
        $netChargeable += (float)($component['discounted_incl'] ?? 0);
    }
    $grossIncl = round($grossIncl, 2);
    $netChargeable = round($netChargeable, 2);

    $enrichedAddonRows = [];
    foreach ($addonRows as $addon) {
        $match = null;
        foreach ($components as $component) {
            if (($component['type'] ?? '') === 'addon' && (string)($component['name'] ?? '') === (string)($addon['name'] ?? '')) {
                $match = $component;
                break;
            }
        }
        $enrichedAddonRows[] = array_merge($addon, [
            'discount_value' => (float)($match['discount_value'] ?? 0),
            'discounted_incl' => (float)($match['discounted_incl'] ?? ($addon['line_incl'] ?? 0)),
        ]);
    }

    $pricing['base_chargeable'] = $baseListIncl;
    $pricing['base_discount_value'] = (float)($components[0]['discount_value'] ?? 0);
    $pricing['base_discounted_incl'] = (float)($components[0]['discounted_incl'] ?? $baseListIncl);
    $pricing['addon_rows'] = $enrichedAddonRows;
    $pricing['addons_total'] = $addonsTotal;
    $pricing['custom_reduce'] = $customReduce;
    $pricing['gross_incl'] = $grossIncl;
    $pricing['chargeable_value'] = $netChargeable;
    $pricing['taxable_value'] = $taxResult['taxable_value'];
    $pricing['total_gst'] = $taxResult['total_gst'];
    $pricing['pricing_components'] = $components;

    return $pricing;
}

function pos_order_format_pricing_amount(float $amount): string
{
    return number_format($amount, 2);
}
