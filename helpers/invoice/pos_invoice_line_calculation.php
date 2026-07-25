<?php

/**
 * POS invoice line pricing per Excel / GST-inclusive method:
 * - Order-level discount split by list-price proportion
 * - Line net (incl. GST) = list extended − allocated discount
 * - Taxable = net ÷ (1 + GST% / 100); GST = net − taxable
 */

/**
 * Coupon + custom/cash + gift voucher (order-level discounts).
 */
function pos_invoice_order_level_discount_total(array $posMeta): float
{
    return round(
        (float)($posMeta['coupon_discount'] ?? 0)
        + (float)($posMeta['cash_discount'] ?? 0)
        + (float)($posMeta['gift_discount'] ?? 0),
        2
    );
}

/**
 * Proportional shares of a total discount across extended line amounts.
 *
 * @param array<int|numeric-string, float> $extendedByIndex
 * @return array<int|numeric-string, float>
 */
function pos_invoice_proportional_discount_shares(array $extendedByIndex, float $totalDiscount): array
{
    if ($totalDiscount <= 0.001 || $extendedByIndex === []) {
        return [];
    }

    $extendedSum = 0.0;
    foreach ($extendedByIndex as $amount) {
        $extendedSum += round((float)$amount, 2);
    }
    if ($extendedSum <= 0.001) {
        return [];
    }

    $shares = [];
    $remaining = $totalDiscount;
    $keys = array_keys($extendedByIndex);
    $lastKey = $keys[count($keys) - 1];
    foreach ($extendedByIndex as $index => $lineExtended) {
        $lineExtended = round((float)$lineExtended, 2);
        $share = $index === $lastKey
            ? round($remaining, 2)
            : round(($totalDiscount * $lineExtended) / $extendedSum, 2);
        $remaining = round($remaining - $share, 2);
        $shares[$index] = $share;
    }

    return $shares;
}

/**
 * Apply order-level discount to GST-inclusive list unit prices (Excel method).
 *
 * @param list<array{list_incl_unit: float, qty: float|int}> $lines
 * @return list<array{list_incl_unit: float, disc_incl_unit: float, discount_share: float}>
 */
function pos_invoice_apply_list_price_order_discount(array $lines, float $orderLevelDiscount): array
{
    if ($lines === []) {
        return [];
    }

    $extendedByIndex = [];
    foreach ($lines as $index => $line) {
        $listUnit = round((float)($line['list_incl_unit'] ?? 0), 2);
        $qty = max(1, (float)($line['qty'] ?? 1));
        if ($listUnit <= 0) {
            continue;
        }
        $extendedByIndex[$index] = round($listUnit * $qty, 2);
    }

    $shares = $orderLevelDiscount > 0.001
        ? pos_invoice_proportional_discount_shares($extendedByIndex, $orderLevelDiscount)
        : [];

    $out = [];
    foreach ($lines as $index => $line) {
        $listUnit = round((float)($line['list_incl_unit'] ?? 0), 2);
        $qty = max(1, (float)($line['qty'] ?? 1));
        $share = (float)($shares[$index] ?? 0);
        if ($listUnit <= 0) {
            $discUnit = round((float)($line['disc_incl_unit'] ?? 0), 2);
            $out[] = [
                'list_incl_unit' => max($listUnit, $discUnit),
                'disc_incl_unit' => $discUnit,
                'discount_share' => 0.0,
            ];
            continue;
        }

        $discUnit = $share > 0.001
            ? max(0.0, round($listUnit - ($share / $qty), 4))
            : round((float)($line['disc_incl_unit'] ?? $listUnit), 4);

        $out[] = [
            'list_incl_unit' => $listUnit,
            'disc_incl_unit' => round($discUnit, 4),
            'discount_share' => round($share, 2),
        ];
    }

    return $out;
}

/**
 * Resolve invoice grand total from list subtotal minus order-level discounts (Excel summary).
 */
function pos_invoice_expected_grand_total(array $posMeta, float $listSubtotalFallback = 0.0): float
{
    $subtotal = round((float)($posMeta['subtotal_goods'] ?? 0), 2);
    if ($subtotal <= 0 && $listSubtotalFallback > 0) {
        $subtotal = round($listSubtotalFallback, 2);
    }

    $orderLevelDisc = pos_invoice_order_level_discount_total($posMeta);
    if ($subtotal > 0 && $orderLevelDisc > 0.001) {
        return max(0.0, round($subtotal - $orderLevelDisc, 2));
    }

    $grand = round((float)($posMeta['grand_total'] ?? 0), 2);

    return $grand > 0 ? $grand : $subtotal;
}

/**
 * Whether stored line meta likely used the pre-Excel discounted-line allocation.
 *
 * @param list<array<string, mixed>> $lineItemsMeta
 */
function pos_invoice_line_meta_needs_repair(array $lineItemsMeta, array $posMeta): bool
{
    $orderLevelDisc = pos_invoice_order_level_discount_total($posMeta);
    if ($orderLevelDisc <= 0.001 || $lineItemsMeta === []) {
        return false;
    }

    $expectedGrand = pos_invoice_expected_grand_total($posMeta);
    if ($expectedGrand <= 0) {
        return false;
    }

    $actualSum = 0.0;
    foreach ($lineItemsMeta as $meta) {
        if (!is_array($meta)) {
            continue;
        }
        $disc = round((float)($meta['discounted_unit_incl'] ?? 0), 2);
        if ($disc <= 0) {
            return true;
        }
        $actualSum += $disc;
    }

    return abs($actualSum - $expectedGrand) > 0.05;
}
