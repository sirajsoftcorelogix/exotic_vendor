<?php

require_once __DIR__ . '/pos_payment_receipt.php';
require_once __DIR__ . '/invoice/pos_order_pricing.php';
require_once __DIR__ . '/order_cancel_invoice.php';

/**
 * Build pricing snapshot from source order for follow-up checkout.
 *
 * @param list<int> $lineIds Empty = all lines
 * @return array<string, mixed>
 */
function order_follow_up_build_source_pricing_snapshot(
    mysqli $conn,
    string $sourceOrderNumber,
    array $lineIds = []
): array {
    require_once __DIR__ . '/../models/posorder/order.php';
    require_once __DIR__ . '/../models/PosInvoice/invoice.php';
    require_once __DIR__ . '/../models/comman/tables.php';

    $sourceOrderNumber = trim($sourceOrderNumber);
    $empty = [
        'order_number' => $sourceOrderNumber,
        'payable_total' => 0.0,
        'paid_total' => 0.0,
        'lines' => [],
        'invoice_id' => 0,
        'pricing_source' => '',
    ];

    if ($sourceOrderNumber === '') {
        return $empty;
    }

    $orderModel = new POSOrder($conn);
    $orderLines = $orderModel->getOrderByOrderNumber($sourceOrderNumber);
    if (!is_array($orderLines) || $orderLines === []) {
        return $empty;
    }

    $lineIdFilter = array_fill_keys(array_filter(array_map('intval', $lineIds), static fn (int $id): bool => $id > 0), true);
    if ($lineIdFilter !== []) {
        $orderLines = array_values(array_filter($orderLines, static function (array $row) use ($lineIdFilter): bool {
            return isset($lineIdFilter[(int) ($row['id'] ?? 0)]);
        }));
    }

    if ($orderLines === []) {
        return $empty;
    }

    $invoiceModel = new POSInvoice($conn);
    $invoice = null;
    $invoiceId = (int) ($orderLines[0]['invoice_id'] ?? 0);
    if ($invoiceId > 0) {
        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        if ($invoice && strtolower(trim((string) ($invoice['status'] ?? ''))) === 'cancelled') {
            $invoice = null;
            $invoiceId = 0;
        }
    }
    if (!$invoice) {
        $invoice = $invoiceModel->getActiveInvoiceForOrderNumber($sourceOrderNumber);
        $invoiceId = (int) ($invoice['id'] ?? 0);
    }

    $orderInfo = $orderModel->getAddressInfoByOrderNumber($sourceOrderNumber);
    $commanModel = new Tables($conn);
    $pricingMap = pos_order_build_line_display_pricing_map(
        $orderLines,
        is_array($invoice) ? $invoice : null,
        is_array($orderInfo) ? $orderInfo : null,
        $commanModel
    );

    $snapshotLines = [];
    $payableSum = 0.0;
    foreach ($orderLines as $row) {
        if (!is_array($row)) {
            continue;
        }
        $lineId = (int) ($row['id'] ?? 0);
        $pricing = $pricingMap[$lineId] ?? [];
        $paidUnit = (float) ($pricing['disc_incl_unit'] ?? $pricing['unit_incl'] ?? pos_order_inclusive_unit_price($row, 'disc'));
        $qty = max(1, (int) ($row['quantity'] ?? 1));
        $paidLine = round($paidUnit * $qty, 2);
        $payableSum += $paidLine;

        $snapshotLines[] = [
            'order_row_id' => $lineId,
            'item_code' => trim((string) ($row['item_code'] ?? '')),
            'size' => trim((string) ($row['size'] ?? '')),
            'color' => trim((string) ($row['color'] ?? '')),
            'sku' => trim((string) ($row['sku'] ?? '')),
            'quantity' => $qty,
            'list_unit_incl' => (float) ($pricing['list_incl_unit'] ?? pos_order_inclusive_unit_price($row, 'list')),
            'paid_unit_incl' => $paidUnit,
            'paid_line_incl' => $paidLine,
            'gst_pct' => (float) ($row['gst'] ?? 0),
        ];
    }

    $orderPayable = pos_payment_resolve_order_total($conn, $sourceOrderNumber);
    if ($lineIdFilter !== [] && $payableSum > 0 && $orderPayable > $payableSum + 0.02) {
        $payableSum = round($payableSum, 2);
    } elseif ($orderPayable > 0) {
        $payableSum = $orderPayable;
    } else {
        $payableSum = round($payableSum, 2);
    }

    return [
        'order_number' => $sourceOrderNumber,
        'payable_total' => round($payableSum, 2),
        'paid_total' => round(pos_payment_sum_paid($conn, $sourceOrderNumber), 2),
        'lines' => $snapshotLines,
        'invoice_id' => $invoiceId,
        'pricing_source' => $invoiceId > 0 ? 'invoice' : 'vp_orders',
    ];
}

/**
 * @param array<string, mixed> $snapshot
 * @return list<array<string, mixed>>
 */
function order_follow_up_build_pos_line_prices_from_snapshot(array $snapshot): array
{
    $lines = is_array($snapshot['lines'] ?? null) ? $snapshot['lines'] : [];
    $payload = [];
    foreach ($lines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $itemCode = trim((string) ($line['item_code'] ?? ''));
        if ($itemCode === '') {
            continue;
        }
        $payload[] = [
            'item_code' => $itemCode,
            'size' => trim((string) ($line['size'] ?? '')),
            'color' => trim((string) ($line['color'] ?? '')),
            'price' => round((float) ($line['paid_unit_incl'] ?? 0), 2),
            'list_price' => round((float) ($line['list_unit_incl'] ?? 0), 2),
            'qty' => max(1, (int) ($line['quantity'] ?? 1)),
        ];
    }

    return $payload;
}

/**
 * @param list<array<string, mixed>> $orderLines
 * @return list<array<string, mixed>>
 */
function order_follow_up_filter_order_lines(array $orderLines, array $lineIds): array
{
    $lineIdFilter = array_fill_keys(array_filter(array_map('intval', $lineIds), static fn (int $id): bool => $id > 0), true);
    if ($lineIdFilter === []) {
        return $orderLines;
    }

    return array_values(array_filter($orderLines, static function ($row) use ($lineIdFilter): bool {
        return is_array($row) && isset($lineIdFilter[(int) ($row['id'] ?? 0)]);
    }));
}

function order_follow_up_type_label(string $type): string
{
    return match (strtolower(trim($type))) {
        'reship' => 'Reship',
        'replace' => 'Replacement',
        'copy' => 'Copy order',
        default => ucfirst($type),
    };
}

function order_follow_up_pricing_mode_label(string $mode): string
{
    return match (strtolower(trim($mode))) {
        'waived' => 'Waived (₹0)',
        'same_as_original' => 'Same as last order',
        'catalog' => 'Current catalog prices',
        'manual' => 'Manual',
        default => ucfirst(str_replace('_', ' ', $mode)),
    };
}

/**
 * @param array<string, mixed> $cartData
 */
function order_follow_up_extract_cart_payable_total(array $cartData): float
{
    $keys = ['amount_payable', 'totalamount', 'grandtotal', 'grand_total', 'total', 'payable_total', 'net_total'];
    foreach ($keys as $key) {
        if (isset($cartData[$key]) && is_numeric($cartData[$key])) {
            $value = round((float) $cartData[$key], 2);
            if ($value > 0) {
                return $value;
            }
        }
    }

    foreach (['cartsummary', 'summary', 'cart_summary', 'totals'] as $nestedKey) {
        $nested = $cartData[$nestedKey] ?? null;
        if (is_array($nested)) {
            $value = order_follow_up_extract_cart_payable_total($nested);
            if ($value > 0) {
                return $value;
            }
        }
    }

    return 0.0;
}
