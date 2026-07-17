<?php

declare(strict_types=1);

/**
 * Repair POS order payable totals for legacy invoices where vp_order_info.total
 * (and pos_payments snapshots) store gross pre-discount amounts while payments
 * were recorded against net payable from invoice notes.
 *
 * Safe to run per order. Does NOT call repairPosInvoiceMetadataForOrder().
 *
 * @see scripts/repair_pos_order_payable_totals.php
 */

require_once __DIR__ . '/pos_payment_receipt.php';

/**
 * @return array<string, mixed>
 */
function pos_invoice_parse_notes_discount_meta(?string $notes): array
{
    if ($notes === null || trim($notes) === '') {
        return [];
    }

    $decoded = json_decode($notes, true);
    if (!is_array($decoded)) {
        return [];
    }

    $pos = $decoded['pos_discounts'] ?? null;

    return is_array($pos) ? $pos : [];
}

/**
 * @param array<string, mixed> $meta
 */
function pos_invoice_sum_pos_discounts(array $meta): float
{
    return round(
        (float)($meta['coupon_discount'] ?? 0)
        + (float)($meta['cash_discount'] ?? 0)
        + (float)($meta['gift_discount'] ?? 0)
        + (float)($meta['line_discount'] ?? 0),
        2
    );
}

/**
 * Resolve net payable from invoice notes / header fallbacks.
 *
 * @param array<string, mixed> $meta pos_discounts block
 */
function pos_invoice_resolve_net_payable(
    array $meta,
    float $invoiceTotalAmount = 0.0,
    float $invoiceDiscountAmount = 0.0
): ?float {
    $subtotalGoods = round((float)($meta['subtotal_goods'] ?? 0), 2);
    $grandTotal = round((float)($meta['grand_total'] ?? 0), 2);
    $discountSum = pos_invoice_sum_pos_discounts($meta);

    if ($subtotalGoods > 0 && $discountSum > 0.001) {
        $computedNet = max(0.0, round($subtotalGoods - $discountSum, 2));

        if ($grandTotal > 0.001) {
            if (abs($grandTotal - $subtotalGoods) < 0.02) {
                return $computedNet;
            }
            if (abs($grandTotal - $computedNet) < 0.02 || abs($grandTotal + $discountSum - $subtotalGoods) < 0.02) {
                return $grandTotal;
            }

            return $computedNet;
        }

        return $computedNet;
    }

    if ($grandTotal > 0) {
        return $grandTotal;
    }

    if ($invoiceDiscountAmount > 0 && $invoiceTotalAmount > 0) {
        return max(0.0, round($invoiceTotalAmount - $invoiceDiscountAmount, 2));
    }

    if ($invoiceTotalAmount > 0) {
        return round($invoiceTotalAmount, 2);
    }

    return null;
}

/**
 * @return array<string, mixed>|null
 */
function pos_invoice_repair_load_order_context(mysqli $conn, string $orderNumber): ?array
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT id, order_number, total, coupon_reduce, giftvoucher_reduce, custom_reduce, credit
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
        "SELECT i.id, i.invoice_number, i.total_amount, i.discount_amount, i.status, i.notes
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
        'SELECT id, receipt_number, payment_amount, order_amount, pending_amount, payment_stage
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
    while ($row = $res->fetch_assoc()) {
        $payments[] = $row;
    }
    $stmt->close();

    $paidTotal = 0.0;
    foreach ($payments as $payment) {
        $paidTotal += round((float)($payment['payment_amount'] ?? 0), 2);
    }
    $paidTotal = round($paidTotal, 2);

    return [
        'order_number' => $orderNumber,
        'order_info' => $orderInfo,
        'invoice' => $invoice ?: null,
        'payments' => $payments,
        'paid_total' => $paidTotal,
    ];
}

/**
 * @param array<string, mixed> $context
 * @return array{net_payable: float, discount_sum: float, notes_meta: array<string, mixed>, notes_grand_total: float}
 */
function pos_invoice_repair_analyze_context(array $context): array
{
    $invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : [];
    $notesMeta = pos_invoice_parse_notes_discount_meta($invoice['notes'] ?? null);
    $discountSum = pos_invoice_sum_pos_discounts($notesMeta);
    $notesGrandTotal = round((float)($notesMeta['grand_total'] ?? 0), 2);

    $netPayable = pos_invoice_resolve_net_payable(
        $notesMeta,
        round((float)($invoice['total_amount'] ?? 0), 2),
        round((float)($invoice['discount_amount'] ?? 0), 2)
    );

    if ($netPayable === null) {
        $paidTotal = round((float)($context['paid_total'] ?? 0), 2);
        if ($paidTotal > 0) {
            $netPayable = $paidTotal;
        }
    }

    if ($netPayable === null) {
        $netPayable = 0.0;
    }

    return [
        'net_payable' => round($netPayable, 2),
        'discount_sum' => $discountSum,
        'notes_meta' => $notesMeta,
        'notes_grand_total' => $notesGrandTotal,
    ];
}

/**
 * @param array<string, mixed> $context
 * @param array<string, mixed> $analysis
 */
function pos_invoice_repair_should_patch_notes(array $context, array $analysis): bool
{
    $invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : [];
    if ($invoice === [] || trim((string)($invoice['notes'] ?? '')) === '') {
        return false;
    }

    $netPayable = round((float)($analysis['net_payable'] ?? 0), 2);
    $notesGrandTotal = round((float)($analysis['notes_grand_total'] ?? 0), 2);
    if ($netPayable <= 0) {
        return false;
    }

    return abs($notesGrandTotal - $netPayable) > 0.02;
}

/**
 * @param array<string, mixed> $options dry_run (bool, default true), patch_notes (bool, default true)
 * @return array{
 *   success: bool,
 *   order_number: string,
 *   dry_run: bool,
 *   message: string,
 *   net_payable: float,
 *   paid_total: float,
 *   pending_after: float,
 *   changes: array<string, mixed>,
 *   before: array<string, mixed>,
 *   after: array<string, mixed>|null
 * }
 */
function pos_invoice_repair_order_payable_totals(mysqli $conn, string $orderNumber, array $options = []): array
{
    $orderNumber = trim($orderNumber);
    $dryRun = !array_key_exists('dry_run', $options) || (bool)$options['dry_run'];
    $patchNotes = !array_key_exists('patch_notes', $options) || (bool)$options['patch_notes'];

    $emptyResult = static function (string $message) use ($orderNumber, $dryRun): array {
        return [
            'success' => false,
            'order_number' => $orderNumber,
            'dry_run' => $dryRun,
            'message' => $message,
            'net_payable' => 0.0,
            'paid_total' => 0.0,
            'pending_after' => 0.0,
            'changes' => [],
            'before' => [],
            'after' => null,
        ];
    };

    if ($orderNumber === '') {
        return $emptyResult('Order number is required.');
    }

    $context = pos_invoice_repair_load_order_context($conn, $orderNumber);
    if ($context === null) {
        return $emptyResult('Order not found in vp_order_info.');
    }

    $analysis = pos_invoice_repair_analyze_context($context);
    $netPayable = round((float)$analysis['net_payable'], 2);
    $paidTotal = round((float)($context['paid_total'] ?? 0), 2);

    if ($netPayable <= 0) {
        return $emptyResult('Could not resolve net payable from invoice notes or payments.');
    }

    $orderInfoTotalBefore = round((float)($context['order_info']['total'] ?? 0), 2);
    $pendingAfter = max(0.0, round($netPayable - $paidTotal, 2));

    $before = [
        'order_info_total' => $orderInfoTotalBefore,
        'paid_total' => $paidTotal,
        'pending_after' => max(0.0, round($orderInfoTotalBefore - $paidTotal, 2)),
        'payments' => $context['payments'],
        'notes_grand_total' => round((float)($analysis['notes_grand_total'] ?? 0), 2),
        'discount_sum' => round((float)($analysis['discount_sum'] ?? 0), 2),
    ];

    $changes = [
        'vp_order_info.total' => [
            'from' => $orderInfoTotalBefore,
            'to' => $netPayable,
        ],
        'pos_payments.order_amount' => $netPayable,
        'pos_payments.pending_amount_recomputed' => true,
    ];

    $patchNotesNeeded = $patchNotes && pos_invoice_repair_should_patch_notes($context, $analysis);
    if ($patchNotesNeeded) {
        $changes['vp_invoices.notes.pos_discounts.grand_total'] = [
            'from' => round((float)($analysis['notes_grand_total'] ?? 0), 2),
            'to' => $netPayable,
        ];
    }

    $needsRepair = abs($orderInfoTotalBefore - $netPayable) > 0.02;
    foreach ($context['payments'] as $payment) {
        $orderAmount = round((float)($payment['order_amount'] ?? 0), 2);
        if ($orderAmount > 0 && abs($orderAmount - $netPayable) > 0.02) {
            $needsRepair = true;
            break;
        }
    }
    if (!$needsRepair && abs($before['pending_after'] - $pendingAfter) > 0.02) {
        $needsRepair = true;
    }
    if ($patchNotesNeeded) {
        $needsRepair = true;
    }

    if (!$needsRepair) {
        return [
            'success' => true,
            'order_number' => $orderNumber,
            'dry_run' => $dryRun,
            'message' => 'No repair needed; totals already align with net payable.',
            'net_payable' => $netPayable,
            'paid_total' => $paidTotal,
            'pending_after' => $pendingAfter,
            'changes' => [],
            'before' => $before,
            'after' => $before,
        ];
    }

    if ($dryRun) {
        return [
            'success' => true,
            'order_number' => $orderNumber,
            'dry_run' => true,
            'message' => 'Dry run: no changes written.',
            'net_payable' => $netPayable,
            'paid_total' => $paidTotal,
            'pending_after' => $pendingAfter,
            'changes' => $changes,
            'before' => $before,
            'after' => [
                'order_info_total' => $netPayable,
                'paid_total' => $paidTotal,
                'pending_after' => $pendingAfter,
                'notes_grand_total' => $patchNotesNeeded ? $netPayable : $before['notes_grand_total'],
            ],
        ];
    }

    $conn->begin_transaction();

    try {
        $stmt = $conn->prepare(
            'UPDATE vp_order_info SET total = ? WHERE order_number = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new RuntimeException('Prepare failed for vp_order_info update: ' . $conn->error);
        }
        $stmt->bind_param('ds', $netPayable, $orderNumber);
        $stmt->execute();
        $stmt->close();

        if ($patchNotesNeeded) {
            $invoice = $context['invoice'];
            $invoiceId = (int)($invoice['id'] ?? 0);
            $notesRaw = (string)($invoice['notes'] ?? '');
            $decoded = json_decode($notesRaw, true);
            if (!is_array($decoded)) {
                $decoded = [];
            }
            if (!isset($decoded['pos_discounts']) || !is_array($decoded['pos_discounts'])) {
                $decoded['pos_discounts'] = [];
            }
            $decoded['pos_discounts']['grand_total'] = $netPayable;
            $patchedJson = json_encode($decoded, JSON_UNESCAPED_UNICODE);
            if ($patchedJson === false) {
                throw new RuntimeException('Failed to encode patched invoice notes JSON.');
            }

            $stmt = $conn->prepare('UPDATE vp_invoices SET notes = ? WHERE id = ? LIMIT 1');
            if (!$stmt) {
                throw new RuntimeException('Prepare failed for vp_invoices notes update: ' . $conn->error);
            }
            $stmt->bind_param('si', $patchedJson, $invoiceId);
            $stmt->execute();
            $stmt->close();
        }

        pos_payment_refresh_order_snapshots($conn, $orderNumber);

        $conn->commit();
    } catch (Throwable $e) {
        $conn->rollback();

        return $emptyResult('Repair failed: ' . $e->getMessage());
    }

    $afterContext = pos_invoice_repair_load_order_context($conn, $orderNumber);
    $afterAnalysis = $afterContext ? pos_invoice_repair_analyze_context($afterContext) : ['notes_grand_total' => 0.0];
    $afterPaid = round((float)($afterContext['paid_total'] ?? $paidTotal), 2);
    $afterOrderTotal = round((float)($afterContext['order_info']['total'] ?? $netPayable), 2);

    return [
        'success' => true,
        'order_number' => $orderNumber,
        'dry_run' => false,
        'message' => 'Repair completed.',
        'net_payable' => $netPayable,
        'paid_total' => $afterPaid,
        'pending_after' => max(0.0, round($netPayable - $afterPaid, 2)),
        'changes' => $changes,
        'before' => $before,
        'after' => [
            'order_info_total' => $afterOrderTotal,
            'paid_total' => $afterPaid,
            'pending_after' => max(0.0, round($afterOrderTotal - $afterPaid, 2)),
            'notes_grand_total' => round((float)($afterAnalysis['notes_grand_total'] ?? 0), 2),
            'payments' => $afterContext['payments'] ?? [],
        ],
    ];
}
