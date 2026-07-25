<?php

declare(strict_types=1);

/**
 * Remove an erroneous order-level custom_reduce (stale POS cart discount) and realign
 * order info, invoice notes, invoice header, and pos_payments snapshots.
 *
 * @see scripts/repair_pos_stale_custom_discount.php
 */

require_once __DIR__ . '/pos_payment_receipt.php';
require_once __DIR__ . '/invoice/pos_order_pricing.php';

/**
 * @return array<string, mixed>|null
 */
function pos_stale_custom_discount_load_context(mysqli $conn, string $orderNumber): ?array
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, order_number, total, coupon_reduce, giftvoucher_reduce, credit
         FROM vp_order_info
         WHERE order_number = ?
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $orderInfo = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$orderInfo) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, item_code, itemprice, finalprice, quantity, custom_reduce
         FROM vp_orders
         WHERE order_number = ?'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $res = $stmt->get_result();
    $lines = [];
    $maxCustomReduce = 0.0;
    while ($row = $res->fetch_assoc()) {
        $lines[] = $row;
        $maxCustomReduce = max($maxCustomReduce, round((float)($row['custom_reduce'] ?? 0), 2));
    }
    $stmt->close();

    $stmt = $conn->prepare(
        "SELECT i.id, i.invoice_number, i.subtotal, i.tax_amount, i.total_amount, i.discount_amount, i.notes
         FROM vp_invoices i
         INNER JOIN vp_order_info o ON o.id = i.vp_order_info_id
         WHERE o.order_number = ?
           AND i.pos_flag = 1
         ORDER BY i.id DESC
         LIMIT 1"
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare(
        'SELECT id, payment_amount, order_amount, pending_amount
         FROM pos_payments
         WHERE order_number = ?
         ORDER BY id ASC'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $res = $stmt->get_result();
    $payments = [];
    $paidTotal = 0.0;
    while ($row = $res->fetch_assoc()) {
        $payments[] = $row;
        $paidTotal += round((float)($row['payment_amount'] ?? 0), 2);
    }
    $stmt->close();

    $notesMeta = [];
    if (is_array($invoice) && trim((string)($invoice['notes'] ?? '')) !== '') {
        $decoded = json_decode((string)$invoice['notes'], true);
        if (is_array($decoded['pos_discounts'] ?? null)) {
            $notesMeta = $decoded['pos_discounts'];
        }
    }

    return [
        'order_number' => $orderNumber,
        'order_info' => $orderInfo,
        'lines' => $lines,
        'max_custom_reduce' => $maxCustomReduce,
        'invoice' => is_array($invoice) ? $invoice : null,
        'notes_meta' => $notesMeta,
        'payments' => $payments,
        'paid_total' => round($paidTotal, 2),
    ];
}

/**
 * Net payable after clearing custom_reduce (matches pos_payment_resolve_order_total).
 *
 * @param list<array<string, mixed>> $lines
 * @param array<string, mixed> $orderInfo
 */
function pos_stale_custom_discount_compute_net(array $lines, array $orderInfo): float
{
    $subtotal = 0.0;
    foreach ($lines as $line) {
        $qty = max(1, (int)($line['quantity'] ?? 1));
        $subtotal += round((float)($line['finalprice'] ?? 0) * $qty, 2);
    }

    $reductions = round(
        (float)($orderInfo['coupon_reduce'] ?? 0)
        + (float)($orderInfo['giftvoucher_reduce'] ?? 0)
        + (float)($orderInfo['credit'] ?? 0),
        2
    );

    return max(0.0, round($subtotal - $reductions, 2));
}

/**
 * @param array<string, mixed> $options
 *   - dry_run (bool, default true)
 *   - amount (float|null) expected stale custom_reduce; null = any positive
 *   - prefer_paid_total (bool, default true) when fully paid, use paid_total if it matches net+removed
 * @return array<string, mixed>
 */
function pos_stale_custom_discount_repair_order(mysqli $conn, string $orderNumber, array $options = []): array
{
    $orderNumber = trim($orderNumber);
    $dryRun = !array_key_exists('dry_run', $options) || (bool)$options['dry_run'];
    $expectedAmount = array_key_exists('amount', $options) ? round((float)$options['amount'], 2) : null;
    $preferPaidTotal = !array_key_exists('prefer_paid_total', $options) || (bool)$options['prefer_paid_total'];

    $fail = static function (string $message) use ($orderNumber, $dryRun): array {
        return [
            'success' => false,
            'order_number' => $orderNumber,
            'dry_run' => $dryRun,
            'message' => $message,
            'changes' => [],
            'before' => [],
            'after' => null,
        ];
    };

    if ($orderNumber === '') {
        return $fail('Order number is required.');
    }

    $context = pos_stale_custom_discount_load_context($conn, $orderNumber);
    if ($context === null) {
        return $fail('Order not found.');
    }

    $staleAmount = round((float)($context['max_custom_reduce'] ?? 0), 2);
    $notesCash = round((float)($context['notes_meta']['cash_discount'] ?? 0), 2);
    $detectedStale = max($staleAmount, $notesCash);

    if ($detectedStale <= 0.001) {
        return [
            'success' => true,
            'order_number' => $orderNumber,
            'dry_run' => $dryRun,
            'message' => 'No custom discount found on order lines or invoice notes; nothing to repair.',
            'changes' => [],
            'before' => $context,
            'after' => $context,
        ];
    }

    if ($expectedAmount !== null && abs($detectedStale - $expectedAmount) > 0.02) {
        return $fail(
            'Expected stale custom discount ₹' . number_format($expectedAmount, 2)
            . ' but found ₹' . number_format($detectedStale, 2) . '. Aborting.'
        );
    }

    $orderInfo = $context['order_info'];
    $lines = $context['lines'];
    $paidTotal = round((float)($context['paid_total'] ?? 0), 2);
    $orderInfoTotalBefore = round((float)($orderInfo['total'] ?? 0), 2);
    $notesGrandBefore = round((float)($context['notes_meta']['grand_total'] ?? 0), 2);
    $invoiceTotalBefore = round((float)($context['invoice']['total_amount'] ?? 0), 2);

    $netAfterClear = pos_stale_custom_discount_compute_net($lines, $orderInfo);
    $correctedGrand = $netAfterClear;

    if ($preferPaidTotal && $paidTotal > 0 && abs($paidTotal - $netAfterClear) <= 0.02) {
        $correctedGrand = $paidTotal;
    } elseif ($preferPaidTotal && $paidTotal > 0 && abs($paidTotal - ($orderInfoTotalBefore + $detectedStale)) <= 0.02) {
        $correctedGrand = $paidTotal;
    } elseif ($notesGrandBefore > 0 && abs(($notesGrandBefore + $detectedStale) - $netAfterClear) <= 0.02) {
        $correctedGrand = round($notesGrandBefore + $detectedStale, 2);
    }

    $before = [
        'custom_reduce' => $detectedStale,
        'order_info_total' => $orderInfoTotalBefore,
        'notes_grand_total' => $notesGrandBefore,
        'notes_cash_discount' => $notesCash,
        'invoice_total_amount' => $invoiceTotalBefore,
        'paid_total' => $paidTotal,
        'computed_net_after_clear' => $netAfterClear,
    ];

    $after = [
        'custom_reduce' => 0.0,
        'order_info_total' => $correctedGrand,
        'notes_grand_total' => $correctedGrand,
        'notes_cash_discount' => 0.0,
        'invoice_total_amount' => $correctedGrand,
        'paid_total' => $paidTotal,
        'pending_after' => max(0.0, round($correctedGrand - $paidTotal, 2)),
    ];

    $changes = [
        'vp_orders.custom_reduce' => ['from' => $detectedStale, 'to' => 0],
        'vp_order_info.total' => ['from' => $orderInfoTotalBefore, 'to' => $correctedGrand],
        'vp_invoices.notes.pos_discounts.cash_discount' => ['from' => $notesCash, 'to' => 0],
        'vp_invoices.notes.pos_discounts.grand_total' => ['from' => $notesGrandBefore, 'to' => $correctedGrand],
        'vp_invoices.total_amount' => ['from' => $invoiceTotalBefore, 'to' => $correctedGrand],
        'pos_payments.order_amount' => $correctedGrand,
    ];

    if ($dryRun) {
        return [
            'success' => true,
            'order_number' => $orderNumber,
            'dry_run' => true,
            'message' => 'Dry run: no changes written. Re-run with --execute to apply.',
            'stale_amount_removed' => $detectedStale,
            'corrected_grand_total' => $correctedGrand,
            'changes' => $changes,
            'before' => $before,
            'after' => $after,
        ];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare('UPDATE vp_orders SET custom_reduce = 0 WHERE order_number = ?');
        if (!$stmt) {
            throw new RuntimeException('Prepare failed for vp_orders: ' . $conn->error);
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare('UPDATE vp_order_info SET total = ? WHERE order_number = ? LIMIT 1');
        if (!$stmt) {
            throw new RuntimeException('Prepare failed for vp_order_info: ' . $conn->error);
        }
        $stmt->bind_param('ds', $correctedGrand, $orderNumber);
        $stmt->execute();
        $stmt->close();

        $invoice = $context['invoice'];
        if (is_array($invoice) && (int)($invoice['id'] ?? 0) > 0) {
            $invoiceId = (int)$invoice['id'];
            $notesRaw = (string)($invoice['notes'] ?? '');
            $decoded = json_decode($notesRaw, true);
            if (!is_array($decoded)) {
                $decoded = [];
            }
            if (!isset($decoded['pos_discounts']) || !is_array($decoded['pos_discounts'])) {
                $decoded['pos_discounts'] = [];
            }
            $decoded['pos_discounts']['cash_discount'] = 0.0;
            $decoded['pos_discounts']['custom_discount_mode'] = '';
            $decoded['pos_discounts']['custom_discount_value'] = 0.0;
            $decoded['pos_discounts']['grand_total'] = $correctedGrand;
            if ($correctedGrand > 0 && round((float)($decoded['pos_discounts']['subtotal_goods'] ?? 0), 2) <= 0) {
                $decoded['pos_discounts']['subtotal_goods'] = $correctedGrand;
            }

            $patchedJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            if ($patchedJson === false) {
                throw new RuntimeException('Failed to encode patched invoice notes.');
            }

            $invoiceSubtotal = round((float)($invoice['subtotal'] ?? 0), 2);
            $invoiceTax = round((float)($invoice['tax_amount'] ?? 0), 2);
            if ($invoiceSubtotal + $invoiceTax > 0 && abs(($invoiceSubtotal + $invoiceTax) - $correctedGrand) > 0.02) {
                $invoiceTax = max(0.0, round($correctedGrand - $invoiceSubtotal, 2));
            }

            $stmt = $conn->prepare(
                'UPDATE vp_invoices
                 SET notes = ?, total_amount = ?, tax_amount = ?, discount_amount = 0
                 WHERE id = ?
                 LIMIT 1'
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed for vp_invoices: ' . $conn->error);
            }
            $stmt->bind_param('sddi', $patchedJson, $correctedGrand, $invoiceTax, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }

        pos_payment_refresh_order_snapshots($conn, $orderNumber);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();

        return $fail('Repair failed: ' . $e->getMessage());
    }

    return [
        'success' => true,
        'order_number' => $orderNumber,
        'dry_run' => false,
        'message' => 'Stale custom discount removed; order, invoice, and payment snapshots updated.',
        'stale_amount_removed' => $detectedStale,
        'corrected_grand_total' => $correctedGrand,
        'changes' => $changes,
        'before' => $before,
        'after' => $after,
    ];
}
