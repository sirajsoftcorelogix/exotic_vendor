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

/**
 * POS invoice notes take precedence; fill gaps from imported Exotic order fields (vp_order_info / vp_orders).
 *
 * @param list<array<string, mixed>> $orderLines
 * @return array<string, mixed>
 */
function pos_order_resolve_discount_meta(?array $invoice, ?array $orderInfo, array $orderLines = []): array
{
    require_once __DIR__ . '/pos_invoice_amount_summary.php';

    $meta = is_array($invoice) ? pos_invoice_parse_discount_meta($invoice['notes'] ?? null) : [];

    $couponReduce = round((float)($meta['coupon_discount'] ?? 0), 2);
    $giftReduce = round((float)($meta['gift_discount'] ?? 0), 2);
    $cashReduce = round((float)($meta['cash_discount'] ?? 0), 2);
    $giftName = trim((string)($meta['gift_voucher_name'] ?? ''));
    $couponCandidates = [
        $meta['coupon_raw'] ?? '',
        $meta['coupon_display_name'] ?? '',
    ];

    if (is_array($orderInfo)) {
        if ($couponReduce <= 0) {
            $couponReduce = round((float)($orderInfo['coupon_reduce'] ?? 0), 2);
        }
        if ($giftReduce <= 0) {
            $giftReduce = round((float)($orderInfo['giftvoucher_reduce'] ?? 0), 2);
        }
        if ($cashReduce <= 0) {
            $cashReduce = round((float)($orderInfo['custom_reduce'] ?? 0), 2);
        }
        $couponCandidates[] = $orderInfo['coupon'] ?? '';
        if ($giftName === '') {
            $giftName = trim((string)($orderInfo['giftvoucher'] ?? ''));
        }
    }

    foreach ($orderLines as $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }
        if ($couponReduce <= 0) {
            $couponReduce = max($couponReduce, round((float)($orderRow['coupon_reduce'] ?? 0), 2));
        }
        if ($giftReduce <= 0) {
            $giftReduce = max($giftReduce, round((float)($orderRow['giftvoucher_reduce'] ?? 0), 2));
        }
        if ($cashReduce <= 0) {
            $cashReduce = max($cashReduce, round((float)($orderRow['custom_reduce'] ?? 0), 2));
        }
        $couponCandidates[] = $orderRow['coupon'] ?? '';
        if ($giftName === '') {
            $giftName = trim((string)($orderRow['giftvoucher'] ?? ''));
        }
    }

    $couponRaw = pos_order_pick_best_coupon_raw($couponCandidates);

    if ($couponReduce > 0) {
        $meta['coupon_discount'] = $couponReduce;
    }
    if ($giftReduce > 0) {
        $meta['gift_discount'] = $giftReduce;
    }
    if ($cashReduce > 0) {
        $meta['cash_discount'] = $cashReduce;
    }
    if ($couponRaw !== '') {
        $meta['coupon_display_name'] = pos_order_parse_coupon_code($couponRaw);
        $meta['coupon_raw'] = $couponRaw;
    }
    if ($giftName !== '') {
        $meta['gift_voucher_name'] = $giftName;
    }

    return $meta;
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
    $discountMeta = pos_order_resolve_discount_meta($invoice, $orderInfo, $orderLines);
    if (is_array($invoice)) {
        $lineItemsMeta = pos_invoice_parse_line_items_meta($invoice['notes'] ?? null);
    }

    $applyGst = is_array($invoice)
        ? invoice_should_apply_gst_for_invoice($invoice, $commanModel, $discountMeta !== [] ? $discountMeta : null)
        : invoice_should_apply_gst($orderInfo);
    $resolvedUseIgst = is_array($invoice)
        ? invoice_resolve_uses_igst_for_invoice($invoice, $commanModel)
        : null;
    $useIgst = $resolvedUseIgst ?? invoice_order_info_uses_igst($orderInfo, null, $commanModel);

    require_once __DIR__ . '/pos_invoice_line_calculation.php';
    $orderLevelDisc = pos_invoice_order_level_discount_total($discountMeta);
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
    $pendingLines = [];
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

        $pendingLines[$lineId] = [
            'order_row' => $orderRow,
            'pricing' => $pricing,
        ];
    }

    $orderWideDiscount = $orderLevelDisc > 0.001
        ? $orderLevelDisc
        : pos_order_resolve_order_custom_reduce($orderLines, $orderInfo);
    $orderWideComponents = pos_order_build_order_wide_pricing_components($pendingLines, $applyGst);
    $orderWideComponents = pos_order_apply_proportional_custom_reduce($orderWideComponents, $orderWideDiscount);
    $orderTaxResult = pos_order_compute_order_component_tax_rows($orderWideComponents, $applyGst);
    $orderWideComponents = $orderTaxResult['components'];
    $componentsByLineId = [];
    foreach ($orderWideComponents as $component) {
        $componentLineId = (int)($component['line_id'] ?? 0);
        $componentsByLineId[$componentLineId][] = $component;
    }

    foreach ($pendingLines as $lineId => $pendingLine) {
        $pricingByLineId[$lineId] = pos_order_enrich_line_display_pricing(
            $pendingLine['order_row'],
            $pendingLine['pricing'],
            [
                'apply_gst' => $applyGst,
                'use_igst' => $useIgst,
                'pricing_components' => $componentsByLineId[$lineId] ?? [],
                'order_custom_reduce' => $orderWideDiscount,
                'discount_meta' => $discountMeta,
                'order_info' => $orderInfo,
            ]
        );
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
function pos_order_build_pricing_components(array $orderRow, float $baseListIncl, float $baseDiscIncl = 0.0): array
{
    $baseName = trim((string)($orderRow['title'] ?? ''));
    if ($baseName === '') {
        $baseName = 'Base item';
    }

    $baseListIncl = round(max(0.0, $baseListIncl), 2);
    if ($baseDiscIncl <= 0.0) {
        $baseDiscIncl = pos_order_inclusive_line_total($orderRow, 'disc');
    }
    if ($baseDiscIncl <= 0.0 || $baseDiscIncl > $baseListIncl) {
        $baseDiscIncl = $baseListIncl;
    }
    $baseDiscIncl = round($baseDiscIncl, 2);
    $baseDiscVal = round(max(0.0, $baseListIncl - $baseDiscIncl), 2);

    $components = [[
        'type' => 'base',
        'name' => $baseName,
        'list_incl' => $baseListIncl,
        'base_discounted_incl' => $baseDiscIncl,
        'base_discount_value' => $baseDiscVal,
    ]];

    foreach (pos_order_line_addon_rows($orderRow) as $addon) {
        $addonList = round((float)($addon['line_incl'] ?? 0), 2);
        $components[] = [
            'type' => 'addon',
            'name' => (string)($addon['name'] ?? ''),
            'list_incl' => $addonList,
            'base_discounted_incl' => $addonList,
            'base_discount_value' => 0.0,
        ];
    }

    return $components;
}

/**
 * Order-level custom_reduce (stored once per order, duplicated on line rows in some imports).
 *
 * @param list<array<string, mixed>> $orderLines
 */
function pos_order_resolve_order_custom_reduce(array $orderLines, ?array $orderInfo = null): float
{
    if (is_array($orderInfo)) {
        $fromInfo = round((float)($orderInfo['custom_reduce'] ?? 0), 2);
        if ($fromInfo > 0) {
            return $fromInfo;
        }
    }

    $max = 0.0;
    foreach ($orderLines as $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }
        $max = max($max, round((float)($orderRow['custom_reduce'] ?? 0), 2));
    }

    return $max;
}

/**
 * Flatten all order lines (base + addons) into one pool for order-wide discount allocation.
 *
 * @param array<int, array{order_row: array<string, mixed>, pricing: array<string, mixed>}> $pendingLines
 * @return list<array<string, mixed>>
 */
function pos_order_build_order_wide_pricing_components(array $pendingLines, bool $applyGst): array
{
    $allComponents = [];
    foreach ($pendingLines as $lineId => $pendingLine) {
        $orderRow = $pendingLine['order_row'];
        $pricing = $pendingLine['pricing'] ?? [];
        $baseListIncl = pos_order_line_list_price_incl($orderRow);
        $baseDiscIncl = isset($pricing['chargeable_value']) && (float)$pricing['chargeable_value'] > 0
            ? (float)$pricing['chargeable_value']
            : pos_order_inclusive_line_total($orderRow, 'disc');
        $gstRate = $applyGst ? (float)($orderRow['gst'] ?? 0) : 0.0;
        foreach (pos_order_build_pricing_components($orderRow, $baseListIncl, $baseDiscIncl) as $component) {
            $component['line_id'] = (int)$lineId;
            $component['gst_rate'] = $gstRate;
            $allComponents[] = $component;
        }
    }

    return $allComponents;
}

/**
 * @param list<array<string, mixed>> $components
 * @return array{taxable_value: float, total_gst: float, components: list<array<string, mixed>>}
 */
function pos_order_compute_order_component_tax_rows(array $components, bool $applyGst): array
{
    $totalTaxable = 0.0;
    $totalGst = 0.0;
    $result = [];

    foreach ($components as $component) {
        $row = $component;
        $discIncl = round((float)($row['discounted_incl'] ?? 0), 2);
        $gstRate = max(0.0, (float)($row['gst_rate'] ?? 0));

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
        $baseDiscValue = isset($component['base_discount_value'])
            ? round((float)$component['base_discount_value'], 2)
            : max(0.0, round($listIncl - (float)($component['base_discounted_incl'] ?? $listIncl), 2));

        $row = $component;
        $row['discount_pct'] = round(100 * $listIncl / $totalList, 4);

        if ($customReduce <= 0.0) {
            $customReduceAllocated = 0.0;
        } elseif ($index === $count - 1) {
            $customReduceAllocated = round($customReduce - $allocated, 2);
        } else {
            $customReduceAllocated = round($customReduce * ($listIncl / $totalList), 2);
            $allocated += $customReduceAllocated;
        }

        $row['custom_reduce_allocated'] = $customReduceAllocated;
        $row['discount_value'] = round($baseDiscValue + $customReduceAllocated, 2);
        $row['discounted_incl'] = round(max(0.0, $listIncl - (float)$row['discount_value']), 2);
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
 * @param array{apply_gst?: bool, use_igst?: bool, pricing_components?: list<array<string, mixed>>, order_custom_reduce?: float, discount_meta?: array<string, mixed>, order_info?: array<string, mixed>|null} $options
 * @return array<string, mixed>
 */
function pos_order_enrich_line_display_pricing(array $orderRow, array $pricing, array $options = []): array
{
    $addonRows = pos_order_line_addon_rows($orderRow);
    $addonsTotal = pos_order_line_addons_total($orderRow);
    $baseListIncl = pos_order_line_list_price_incl($orderRow);

    $applyGst = !array_key_exists('apply_gst', $options) || !empty($options['apply_gst']);
    $gstRate = $applyGst ? (float)($orderRow['gst'] ?? 0) : 0.0;
    $prebuiltComponents = is_array($options['pricing_components'] ?? null) ? $options['pricing_components'] : null;
    $orderCustomReduce = max(0.0, round((float)($options['order_custom_reduce'] ?? 0), 2));

    if ($prebuiltComponents !== null) {
        $components = $prebuiltComponents;
        $taxableValue = 0.0;
        $totalGst = 0.0;
        foreach ($components as $component) {
            $taxableValue += (float)($component['taxable_value'] ?? 0);
            $totalGst += (float)($component['total_gst'] ?? 0);
        }
        $taxResult = [
            'taxable_value' => round($taxableValue, 2),
            'total_gst' => round($totalGst, 2),
            'components' => $components,
        ];
    } else {
        $customReduce = $orderCustomReduce > 0
            ? $orderCustomReduce
            : max(0.0, round((float)($orderRow['custom_reduce'] ?? 0), 2));
        $baseDiscIncl = isset($pricing['chargeable_value']) && (float)$pricing['chargeable_value'] > 0
            ? (float)$pricing['chargeable_value']
            : pos_order_inclusive_line_total($orderRow, 'disc');
        $components = pos_order_build_pricing_components($orderRow, $baseListIncl, $baseDiscIncl);
        $components = pos_order_apply_proportional_custom_reduce($components, $customReduce);
        $taxResult = pos_order_compute_component_tax_rows($components, $gstRate, $applyGst);
        $components = $taxResult['components'];
        $orderCustomReduce = $customReduce;
    }

    $grossIncl = 0.0;
    $netChargeable = 0.0;
    $lineDiscountAllocated = 0.0;
    foreach ($components as $component) {
        $grossIncl += (float)($component['list_incl'] ?? 0);
        $netChargeable += (float)($component['discounted_incl'] ?? 0);
        $lineDiscountAllocated += (float)($component['discount_value'] ?? 0);
    }
    $grossIncl = round($grossIncl, 2);
    $netChargeable = round($netChargeable, 2);
    $lineDiscountAllocated = round($lineDiscountAllocated, 2);

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

    $pricing['base_list_incl'] = $baseListIncl;
    $pricing['base_discount_value'] = (float)($components[0]['discount_value'] ?? 0);
    $pricing['base_discounted_incl'] = (float)($components[0]['discounted_incl'] ?? $baseListIncl);
    $pricing['addon_rows'] = $enrichedAddonRows;
    $pricing['addons_total'] = $addonsTotal;
    $pricing['custom_reduce'] = $lineDiscountAllocated;
    $pricing['order_custom_reduce'] = $orderCustomReduce;
    $pricing['gross_incl'] = $grossIncl;
    $pricing['chargeable_value'] = $netChargeable;
    $pricing['discount_amount'] = max(0.0, round($grossIncl - $netChargeable, 2));
    $pricing['list_price_incl'] = pos_order_line_list_price_incl($orderRow);
    $pricing['taxable_value'] = $taxResult['taxable_value'];
    $pricing['total_gst'] = $taxResult['total_gst'];
    $pricing['pricing_components'] = $components;

    $discountMeta = is_array($options['discount_meta'] ?? null) ? $options['discount_meta'] : [];
    $orderInfo = is_array($options['order_info'] ?? null) ? $options['order_info'] : null;
    $pricing['order_discount_lines'] = pos_order_line_discount_lines($discountMeta, $lineDiscountAllocated, $orderInfo);

    return $pricing;
}

function pos_order_format_pricing_amount(float $amount): string
{
    return number_format($amount, 2);
}

/**
 * Order-level discount rows with human-readable labels (coupon code, gift voucher, custom).
 *
 * @return list<array{label: string, amount: float}>
 */
function pos_order_build_order_level_discount_lines(array $discountMeta, ?array $orderInfo = null): array
{
    require_once __DIR__ . '/pos_invoice_amount_summary.php';

    $lines = [];
    $cash = round((float)($discountMeta['cash_discount'] ?? 0), 2);
    $coupon = round((float)($discountMeta['coupon_discount'] ?? 0), 2);
    $gift = round((float)($discountMeta['gift_discount'] ?? 0), 2);

    if ($cash > 0.001) {
        $lines[] = [
            'label' => pos_order_custom_discount_display_label($discountMeta),
            'amount' => $cash,
        ];
    }

    if ($coupon > 0.001) {
        $couponRaw = pos_order_pick_best_coupon_raw([
            $discountMeta['coupon_raw'] ?? '',
            $discountMeta['coupon_display_name'] ?? '',
            is_array($orderInfo) ? ($orderInfo['coupon'] ?? '') : '',
        ]);
        $lines[] = [
            'label' => pos_order_coupon_discount_label($couponRaw),
            'amount' => $coupon,
        ];
    }

    if ($gift > 0.001) {
        $giftName = trim((string)($discountMeta['gift_voucher_name'] ?? ''));
        if ($giftName === '' && is_array($orderInfo)) {
            $giftName = trim((string)($orderInfo['giftvoucher'] ?? ''));
        }
        $lines[] = [
            'label' => pos_order_gift_voucher_discount_label($giftName),
            'amount' => $gift,
        ];
    }

    return $lines;
}

/**
 * Allocate order-level discount labels to a single line's share of the order discount.
 *
 * @return list<array{label: string, amount: float}>
 */
function pos_order_line_discount_lines(array $discountMeta, float $lineAllocatedDiscount, ?array $orderInfo = null): array
{
    $sourceLines = pos_order_build_order_level_discount_lines($discountMeta, $orderInfo);
    if ($sourceLines === [] || $lineAllocatedDiscount <= 0.001) {
        return [];
    }

    $orderTotal = 0.0;
    foreach ($sourceLines as $row) {
        $orderTotal += (float)($row['amount'] ?? 0);
    }
    $orderTotal = round($orderTotal, 2);
    if ($orderTotal <= 0.001) {
        return [];
    }

    if (count($sourceLines) === 1) {
        return [[
            'label' => (string)($sourceLines[0]['label'] ?? 'Custom Discount:'),
            'amount' => round($lineAllocatedDiscount, 2),
        ]];
    }

    $allocated = 0.0;
    $result = [];
    $lastIndex = count($sourceLines) - 1;
    foreach ($sourceLines as $index => $row) {
        $share = $index === $lastIndex
            ? round($lineAllocatedDiscount - $allocated, 2)
            : round($lineAllocatedDiscount * ((float)($row['amount'] ?? 0) / $orderTotal), 2);
        $allocated += $share;
        if ($share > 0.001) {
            $result[] = [
                'label' => (string)($row['label'] ?? 'Custom Discount:'),
                'amount' => $share,
            ];
        }
    }

    return $result;
}

/**
 * GST-inclusive list price from vp_orders.finalprice (per unit × qty).
 */
function pos_order_line_list_price_incl(array $orderRow): float
{
    return pos_order_inclusive_line_total($orderRow, 'list');
}

/**
 * @param array<int, array<string, mixed>> $linePricingByLineId
 * @return array{gross_incl: float, custom_reduce: float, total_gst: float, net_chargeable: float}|null
 */
function pos_order_aggregate_line_pricing_summary(array $linePricingByLineId, ?array $orderInfo = null): ?array
{
    if ($linePricingByLineId === []) {
        return null;
    }

    $grossIncl = 0.0;
    $totalGst = 0.0;
    $netChargeable = 0.0;
    $customReduce = 0.0;
    $hasGross = false;

    foreach ($linePricingByLineId as $pricing) {
        if (!is_array($pricing) || !array_key_exists('gross_incl', $pricing)) {
            continue;
        }
        $hasGross = true;
        $grossIncl += (float)($pricing['gross_incl'] ?? 0);
        $totalGst += (float)($pricing['total_gst'] ?? 0);
        $netChargeable += (float)($pricing['chargeable_value'] ?? 0);
        $customReduce += (float)($pricing['custom_reduce'] ?? 0);
    }

    if (!$hasGross) {
        return null;
    }

    if (is_array($orderInfo)) {
        $fromInfo = round((float)($orderInfo['custom_reduce'] ?? 0), 2);
        if ($fromInfo > 0) {
            $customReduce = $fromInfo;
        }
    }

    return [
        'gross_incl' => round($grossIncl, 2),
        'custom_reduce' => round($customReduce, 2),
        'total_gst' => round($totalGst, 2),
        'net_chargeable' => round($netChargeable, 2),
    ];
}

/**
 * Summary rows for order-details sidebar (matches line pricing breakdown).
 *
 * @param array{gross_incl: float, custom_reduce: float, total_gst: float, net_chargeable: float} $aggregate
 * @param array<string, mixed> $posMeta
 * @param array<string, mixed>|null $orderInfo
 * @return list<array{label: string, amount: float, note: string, is_grand: bool}>
 */
function pos_order_build_summary_rows_from_line_pricing(array $aggregate, array $posMeta = [], ?array $orderInfo = null): array
{
    require_once __DIR__ . '/pos_invoice_amount_summary.php';

    $absorbedNote = '(included in line totals)';
    $rows = [[
        'label' => 'Total Before Discount (incl. GST)',
        'amount' => (float)$aggregate['gross_incl'],
        'note' => '',
        'is_grand' => false,
    ]];

    foreach (pos_order_build_order_level_discount_lines($posMeta, $orderInfo) as $discountLine) {
        $rows[] = [
            'label' => (string)($discountLine['label'] ?? 'Custom Discount:'),
            'amount' => (float)($discountLine['amount'] ?? 0),
            'note' => '',
            'is_grand' => false,
        ];
    }

    $gst = (float)$aggregate['total_gst'];
    if ($gst > 0.001) {
        $rows[] = [
            'label' => 'Total GST',
            'amount' => $gst,
            'note' => $absorbedNote,
            'is_grand' => false,
        ];
    }

    $rows[] = [
        'label' => 'GRAND Total',
        'amount' => (float)$aggregate['net_chargeable'],
        'note' => '',
        'is_grand' => true,
    ];

    return $rows;
}

/**
 * @param array<int, array<string, mixed>> $linePricingByLineId
 */
function pos_order_line_pricing_should_override_invoice_summary(
    array $linePricingByLineId,
    ?array $orderInfo = null,
    array $orderLines = []
): bool {
    $discountMeta = pos_order_resolve_discount_meta(null, $orderInfo, $orderLines);
    if (pos_invoice_order_level_discount_total($discountMeta) > 0.001) {
        return true;
    }

    foreach ($linePricingByLineId as $pricing) {
        if (!is_array($pricing)) {
            continue;
        }
        if (((float)($pricing['addons_total'] ?? 0)) > 0.001 || ((float)($pricing['custom_reduce'] ?? 0)) > 0.001) {
            return true;
        }
    }

    return false;
}
