<?php

require_once __DIR__ . '/pos_order_pricing.php';

/**
 * Match a vp_invoice_items row to its vp_orders line (stable when item_code repeats).
 *
 * @param list<array<string, mixed>> $orderLines
 * @param list<int> $consumedLineIds
 */
function pos_invoice_find_order_line_for_item(array $invoiceItem, array $orderLines, array &$consumedLineIds): ?array
{
    $itemCode = trim((string)($invoiceItem['item_code'] ?? ''));

    foreach ($orderLines as $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }
        $lineId = (int)($orderRow['id'] ?? 0);
        if ($lineId > 0 && in_array($lineId, $consumedLineIds, true)) {
            continue;
        }
        if ($itemCode !== '' && trim((string)($orderRow['item_code'] ?? '')) === $itemCode) {
            if ($lineId > 0) {
                $consumedLineIds[] = $lineId;
            }

            return $orderRow;
        }
    }

    foreach ($orderLines as $orderRow) {
        if (!is_array($orderRow)) {
            continue;
        }
        $lineId = (int)($orderRow['id'] ?? 0);
        if ($lineId > 0 && in_array($lineId, $consumedLineIds, true)) {
            continue;
        }
        if ($lineId > 0) {
            $consumedLineIds[] = $lineId;
        }

        return $orderRow;
    }

    return null;
}

/**
 * Expand invoice PDF rows: base item + each addon as separate lines with list/disc prices.
 *
 * @param list<array<string, mixed>> $invoiceItems
 * @param list<array<string, mixed>> $orderLines
 * @return list<array<string, mixed>>
 */
function pos_invoice_expand_items_with_addons(
    array $invoiceItems,
    array $orderLines,
    ?array $invoice = null,
    ?array $orderInfo = null,
    $commanModel = null
): array {
    if ($invoiceItems === [] || $orderLines === []) {
        return $invoiceItems;
    }

    $pricingMap = pos_order_build_line_display_pricing_map($orderLines, $invoice, $orderInfo, $commanModel);
    $consumedLineIds = [];
    $expanded = [];

    foreach ($invoiceItems as $invoiceItem) {
        if (!is_array($invoiceItem)) {
            continue;
        }

        $orderRow = pos_invoice_find_order_line_for_item($invoiceItem, $orderLines, $consumedLineIds);
        if ($orderRow === null) {
            $expanded[] = $invoiceItem;
            continue;
        }

        $lineId = (int)($orderRow['id'] ?? 0);
        $pricing = $pricingMap[$lineId] ?? null;
        $components = is_array($pricing) && is_array($pricing['pricing_components'] ?? null)
            ? $pricing['pricing_components']
            : [];

        if ($components === []) {
            $expanded[] = $invoiceItem;
            continue;
        }

        $qty = max(1, (int)($invoiceItem['quantity'] ?? $orderRow['quantity'] ?? 1));
        $taxRate = (float)($invoiceItem['tax_rate'] ?? $orderRow['gst'] ?? 0);
        $hsn = (string)($invoiceItem['hsn'] ?? $orderRow['hsn'] ?? '');

        foreach ($components as $component) {
            $listIncl = round((float)($component['list_incl'] ?? 0), 2);
            $discIncl = round((float)($component['discounted_incl'] ?? 0), 2);
            if ($listIncl <= 0 && $discIncl <= 0) {
                continue;
            }

            $isAddon = (($component['type'] ?? '') === 'addon');
            $expanded[] = array_merge($invoiceItem, [
                'item_name' => (string)($component['name'] ?? $invoiceItem['item_name'] ?? ''),
                'hsn' => $hsn,
                'quantity' => $qty,
                'tax_rate' => $taxRate,
                'line_total' => $discIncl,
                'pdf_list_unit_incl' => $qty > 0 ? round($listIncl / $qty, 2) : $listIncl,
                'pdf_disc_unit_incl' => $qty > 0 ? round($discIncl / $qty, 2) : $discIncl,
                'pdf_is_addon' => $isAddon,
                'box_no' => $isAddon ? '' : (string)($invoiceItem['box_no'] ?? ''),
            ]);
        }
    }

    return $expanded !== [] ? $expanded : $invoiceItems;
}

/**
 * @param array<string, mixed> $posMeta
 * @param array<int, array<string, mixed>> $pricingMap
 * @return array<string, mixed>
 */
function pos_invoice_apply_pricing_aggregate_to_pos_meta(array $posMeta, array $pricingMap, ?array $orderInfo = null): array
{
    if (!pos_order_line_pricing_should_override_invoice_summary($pricingMap, $orderInfo)) {
        return $posMeta;
    }

    $aggregate = pos_order_aggregate_line_pricing_summary($pricingMap, $orderInfo);
    if ($aggregate === null) {
        return $posMeta;
    }

    $posMeta['subtotal_goods'] = $aggregate['gross_incl'];
    $posMeta['cash_discount'] = $aggregate['custom_reduce'];
    $posMeta['gst_total'] = $aggregate['total_gst'];
    $posMeta['grand_total'] = $aggregate['net_chargeable'];
    $posMeta['discounts_absorbed'] = true;

    if ($aggregate['custom_reduce'] > 0) {
        if (trim((string)($posMeta['custom_discount_mode'] ?? '')) === '') {
            $posMeta['custom_discount_mode'] = 'fixed';
        }
        if ((float)($posMeta['custom_discount_value'] ?? 0) <= 0) {
            $posMeta['custom_discount_value'] = $aggregate['custom_reduce'];
        }
    }

    return $posMeta;
}
