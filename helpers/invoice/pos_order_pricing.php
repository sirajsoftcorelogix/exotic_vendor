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
