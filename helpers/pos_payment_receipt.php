<?php

/**
 * POS payment receipt / pos_payments.receipt_number:
 * {short_code}{YYMMDD}{NN} — e.g. KN25050101
 * YYMMDD = calendar day in Asia/Kolkata; NN = 01–99, increments per short_code per day.
 */

function pos_payment_normalize_short_code(?string $raw): string
{
    $s = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string)$raw));

    return $s !== '' ? $s : 'XX';
}

/**
 * YYMMDD (6 digits) for "today" in Asia/Kolkata (matches receipt date usage elsewhere).
 */
function pos_payment_receipt_ymd_suffix(): string
{
    try {
        $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    } catch (\Throwable $e) {
        $dt = new DateTime('now');
    }

    return $dt->format('ymd');
}

/**
 * short_code for session warehouse, else default exotic_address row.
 */
function pos_payment_resolve_short_code_for_warehouse(mysqli $conn, int $warehouseId): string
{
    if ($warehouseId > 0) {
        $stmt = $conn->prepare('SELECT short_code FROM exotic_address WHERE id = ? AND is_active = 1 LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $warehouseId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!empty($row['short_code'])) {
                return pos_payment_normalize_short_code($row['short_code']);
            }
        }
    }

    $stmt = $conn->prepare('SELECT short_code FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1');
    if ($stmt) {
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['short_code'])) {
            return pos_payment_normalize_short_code($row['short_code']);
        }
    }

    return 'XX';
}

/**
 * Next receipt_number for today: shortCode + YYMMDD + NN (locking matching rows).
 *
 * @throws RuntimeException when NN would exceed 99 for this code and date
 */
function pos_payment_generate_next_receipt_number(mysqli $conn, string $shortCode): string
{
    $prefix = pos_payment_normalize_short_code($shortCode);
    $ymd = pos_payment_receipt_ymd_suffix();
    $base = $prefix . $ymd;

    if (!$conn->begin_transaction()) {
        throw new RuntimeException('Unable to begin transaction for receipt number');
    }

    try {
        $like = $base . '%';
        $stmt = $conn->prepare(
            'SELECT receipt_number FROM pos_payments
             WHERE receipt_number IS NOT NULL AND receipt_number != \'\' AND receipt_number LIKE ?
             FOR UPDATE'
        );
        if (!$stmt) {
            throw new RuntimeException('Prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $maxNn = 0;
        $re = '/^' . preg_quote($base, '/') . '(\d{2})$/';
        while ($row = $res->fetch_assoc()) {
            $rn = trim((string)($row['receipt_number'] ?? ''));
            if ($rn !== '' && preg_match($re, $rn, $m)) {
                $maxNn = max($maxNn, (int)$m[1]);
            }
        }
        $stmt->close();

        $nextNn = $maxNn + 1;
        if ($nextNn > 99) {
            throw new RuntimeException('POS receipt daily sequence overflow (>99) for ' . $base);
        }

        $out = $base . str_pad((string)$nextNn, 2, '0', STR_PAD_LEFT);

        if (!$conn->commit()) {
            throw new RuntimeException('Commit failed: ' . $conn->error);
        }

        return $out;
    } catch (Throwable $e) {
        $conn->rollback();
        throw $e;
    }
}

/** @deprecated Use pos_payment_generate_next_receipt_number() */
function pos_payment_generate_next_invoice_number(mysqli $conn, string $shortCode): string
{
    return pos_payment_generate_next_receipt_number($conn, $shortCode);
}

/**
 * Resolved warehouse row id for FK (session unset or invalid).
 *
 * @return int 0 if no exotic_address row exists
 */
/**
 * Logged-in vp_users.id from session (supports both user_id and user['id'] shapes).
 */
function pos_payment_resolve_session_user_id(): int
{
    if (!empty($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    if (!empty($_SESSION['user']['id'])) {
        return (int)$_SESSION['user']['id'];
    }

    return 0;
}

/**
 * Payable order total after discounts (grand total), not raw line list prices.
 * Priority: pos_payments.order_amount → vp_order_info.total → vp_invoices → line subtotal minus reductions.
 */
function pos_payment_resolve_order_total(mysqli $conn, string $orderNumber): float
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0.0;
    }

    // 1. Compute net payable directly from vp_orders lines (source of truth)
    $stmt = $conn->prepare('SELECT status, itemprice, finalprice, quantity, addons, custom_reduce FROM vp_orders WHERE order_number = ?');
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $res = $stmt->get_result();
        $lines = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if (!empty($lines)) {
            $gross = 0.0;
            $customReduce = 0.0;
            $activeCount = 0;

            require_once __DIR__ . '/../models/order/order.php';
            foreach ($lines as $line) {
                if (strtolower(trim((string)($line['status'] ?? ''))) === 'cancelled') {
                    continue;
                }
                $activeCount++;
                $qty = max(1, (int)($line['quantity'] ?? 1));
                $unit = (float)($line['finalprice'] ?? 0);
                if ($unit <= 0) {
                    $unit = (float)($line['itemprice'] ?? 0);
                }
                $gross += round($unit * $qty, 2);

                if (!empty($line['addons'])) {
                    foreach (Order::parseVendorOrderLineAddonsList($line['addons']) as $addonItem) {
                        $gross += round((float)($addonItem['price'] ?? 0) * $qty, 2);
                    }
                }
                $customReduce = max($customReduce, (float)($line['custom_reduce'] ?? 0));
            }

            if ($activeCount > 0) {
                return max(0.0, round($gross, 2));
            }
        }
    }

    // 2. POS payment snapshot (recorded at checkout/payment)
    $stmt = $conn->prepare('SELECT MAX(order_amount) AS order_total FROM pos_payments WHERE order_number = ? AND order_amount > 0');
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['order_total']) && (float)$row['order_total'] > 0) {
            return round((float)$row['order_total'], 2);
        }
    }

    // 3. Fallback for orders without lines (vp_order_info)
    $stmt = $conn->prepare('SELECT total FROM vp_order_info WHERE order_number = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['total']) && (float)$row['total'] > 0) {
            return round((float)$row['total'], 2);
        }
    }

    // 3. Fallback for orders without lines (vp_order_info or non-cancelled vp_invoices)
    $stmt = $conn->prepare('SELECT total FROM vp_order_info WHERE order_number = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['total']) && (float)$row['total'] > 0) {
            return round((float)$row['total'], 2);
        }
    }

    $stmt = $conn->prepare("SELECT i.total_amount FROM vp_invoices i INNER JOIN vp_order_info oi ON oi.id = i.vp_order_info_id WHERE oi.order_number = ? AND LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled' ORDER BY i.id DESC LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!empty($row['total_amount']) && (float)$row['total_amount'] > 0) {
            return round((float)$row['total_amount'], 2);
        }
    }

    return 0.0;
}
    // if ($stmt) {
    //     $stmt->bind_param('s', $orderNumber);
    //     $stmt->execute();
    //     $row = $stmt->get_result()->fetch_assoc();
    //     $stmt->close();
    //     $total = round((float)($row['total_amount'] ?? 0), 2);
    //     if ($total > 0) {
    //         return $total;
    //     }
    // }

    // return 0.0;


/**
 * Align imported vp_orders / vp_order_info with POS checkout payable (vendor import often stores net API total).
 *
 * @param list<array{itemcode?:string,size?:string,color?:string,price?:float|int|string}> $linePrices
 */
function pos_payment_sync_checkout_order_payable(
    mysqli $conn,
    string $orderNumber,
    float $checkoutGrandTotal,
    array $linePrices = []
): void {
    $orderNumber = trim($orderNumber);
    $checkoutGrandTotal = round($checkoutGrandTotal, 2);
    if ($orderNumber === '' || $checkoutGrandTotal <= 0) {
        return;
    }

    $stmt = $conn->prepare('UPDATE vp_order_info SET total = ? WHERE order_number = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('ds', $checkoutGrandTotal, $orderNumber);
        $stmt->execute();
        $stmt->close();
    }

    $stmt = $conn->prepare(
        'UPDATE vp_orders SET custom_reduce = 0 WHERE order_number = ? AND IFNULL(custom_reduce, 0) > 0'
    );
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $stmt->close();
    }

    $updatedLines = 0;
    foreach ($linePrices as $linePriceRow) {
        if (!is_array($linePriceRow)) {
            continue;
        }
        $itemCode = trim((string)($linePriceRow['itemcode'] ?? $linePriceRow['item_code'] ?? ''));
        $unitPrice = round((float)($linePriceRow['price'] ?? 0), 2);
        if ($itemCode === '' || $unitPrice <= 0) {
            continue;
        }

        $size = trim((string)($linePriceRow['size'] ?? ''));
        $color = trim((string)($linePriceRow['color'] ?? ''));

        if ($size !== '' || $color !== '') {
            $upd = $conn->prepare(
                'UPDATE vp_orders
                 SET finalprice = ?
                 WHERE order_number = ?
                   AND item_code = ?
                   AND TRIM(COALESCE(size, \'\')) = ?
                   AND TRIM(COALESCE(color, \'\')) = ?'
            );
            if ($upd) {
                $upd->bind_param('dssss', $unitPrice, $orderNumber, $itemCode, $size, $color);
                $upd->execute();
                $updatedLines += $upd->affected_rows;
                $upd->close();
                continue;
            }
        }

        $upd = $conn->prepare(
            'UPDATE vp_orders SET finalprice = ? WHERE order_number = ? AND item_code = ? LIMIT 1'
        );
        if ($upd) {
            $upd->bind_param('dss', $unitPrice, $orderNumber, $itemCode);
            $upd->execute();
            $updatedLines += $upd->affected_rows;
            $upd->close();
        }
    }

    if ($updatedLines <= 0) {
        $lineStmt = $conn->prepare(
            'SELECT id, quantity FROM vp_orders WHERE order_number = ? ORDER BY id ASC'
        );
        if ($lineStmt) {
            $lineStmt->bind_param('s', $orderNumber);
            $lineStmt->execute();
            $lineRes = $lineStmt->get_result();
            $lines = [];
            while ($lineRow = $lineRes->fetch_assoc()) {
                $lines[] = $lineRow;
            }
            $lineStmt->close();

            if (count($lines) === 1) {
                $qty = max(1, (int)($lines[0]['quantity'] ?? 1));
                $unit = round($checkoutGrandTotal / $qty, 2);
                $lineId = (int)($lines[0]['id'] ?? 0);
                if ($lineId > 0 && $unit > 0) {
                    $upd = $conn->prepare('UPDATE vp_orders SET finalprice = ? WHERE id = ? LIMIT 1');
                    if ($upd) {
                        $upd->bind_param('di', $unit, $lineId);
                        $upd->execute();
                        $upd->close();
                    }
                }
            }
        }
    }

    pos_payment_refresh_order_snapshots($conn, $orderNumber);
}

function pos_payment_is_cod_mode(string $mode): bool
{
    $m = strtolower(trim($mode));
    return $m === 'cod' || $m === 'pay_on_pickup';
}

/**
 * @param list<array{mode?:string,amount?:float|int|string}> $splits
 */
function pos_payment_split_advance_total(array $splits): float
{
    $total = 0.0;
    foreach ($splits as $split) {
        if (!is_array($split) || pos_payment_is_cod_mode((string)($split['mode'] ?? ''))) {
            continue;
        }
        $total += round((float)($split['amount'] ?? 0), 2);
    }

    return round($total, 2);
}

/**
 * @param list<array{mode?:string,amount?:float|int|string}> $splits
 */
function pos_payment_split_cod_total(array $splits): float
{
    $total = 0.0;
    foreach ($splits as $split) {
        if (!is_array($split) || !pos_payment_is_cod_mode((string)($split['mode'] ?? ''))) {
            continue;
        }
        $total += round((float)($split['amount'] ?? 0), 2);
    }

    return round($total, 2);
}

/**
 * @return list<string>
 */
function pos_payment_allowed_modes(): array
{
    return ['cash', 'pay_on_pickup', 'cod', 'upi', 'bank_transfer', 'pos_machine', 'razorpay', 'cheque', 'adminorder', 'waived'];
}

function pos_payment_is_waived_mode(string $mode): bool
{
    return strtolower(trim($mode)) === 'waived';
}

/**
 * @return list<array{0:string,1:string}>
 */
function pos_payment_mode_options_for_view(): array
{
    $labels = [
        'cash' => 'Cash',
        'pay_on_pickup' => 'Pay on Pickup (Store Pay Later)',
        'cod' => 'Cash on Delivery (COD)',
        'upi' => 'UPI',
        'bank_transfer' => 'Bank transfer',
        'pos_machine' => 'POS machine',
        'razorpay' => 'Razorpay',
        'cheque' => 'Cheque',
        'adminorder' => 'Admin Order',
        'waived' => 'Waived (no charge)',
    ];
    $options = [];
    foreach (pos_payment_allowed_modes() as $mode) {
        $options[] = [$mode, $labels[$mode] ?? ucfirst(str_replace('_', ' ', $mode))];
    }

    return $options;
}

/**
 * @param array<string, mixed> $payload
 *
 * @return array{
 *   splits: list<array{mode:string,amount:float,transaction_id:string}>,
 *   total: float,
 *   primary_mode: string,
 *   primary_txn: string
 * }
 */
function pos_payment_resolve_splits_from_payload(array $payload): array
{
    $allowed = pos_payment_allowed_modes();
    $paymentStage = strtolower(trim((string)($payload['payment_stage'] ?? '')));
    if ($paymentStage === 'zero_advance') {
        return [
            'splits' => [],
            'total' => 0.0,
            'primary_mode' => 'pay_on_pickup',
            'primary_txn' => '',
        ];
    }

    $splits = [];
    $raw = $payload['payment_splits'] ?? null;
    if (is_array($raw)) {
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $mode = strtolower(trim((string)($row['mode'] ?? $row['payment_mode'] ?? '')));
            if (!in_array($mode, $allowed, true)) {
                continue;
            }
            $amount = round((float)($row['amount'] ?? $row['payment_amount'] ?? 0), 2);
            if ($amount <= 0 && !pos_payment_is_waived_mode($mode)) {
                continue;
            }
            $splits[] = [
                'mode' => $mode,
                'amount' => pos_payment_is_waived_mode($mode) ? 0.0 : $amount,
                'transaction_id' => trim((string)($row['transaction_id'] ?? '')),
            ];
        }
    }

    if ($splits === []) {
        if (strtolower(trim((string)($payload['payment_stage'] ?? ''))) === 'zero_advance') {
            $amount = round((float)($payload['order_total'] ?? $payload['receipt_order_total'] ?? $payload['amount'] ?? 0), 2);
            $splits[] = [
                'mode' => 'pay_on_pickup',
                'amount' => $amount,
                'transaction_id' => '',
            ];
        } else {
            $mode = strtolower(trim((string)($payload['payment_type'] ?? $payload['payment_mode'] ?? 'cash')));
            $amount = round((float)($payload['amount'] ?? $payload['payment_amount'] ?? 0), 2);
            if ($amount > 0 || pos_payment_is_waived_mode($mode)) {
                $splits[] = [
                    'mode' => in_array($mode, $allowed, true) ? $mode : 'cash',
                    'amount' => pos_payment_is_waived_mode($mode) ? 0.0 : $amount,
                    'transaction_id' => trim((string)($payload['transaction_id'] ?? '')),
                ];
            }
        }
    }

    $total = 0.0;
    foreach ($splits as $split) {
        $total += (float)$split['amount'];
    }
    $total = round($total, 2);

    $primary = $splits[0] ?? ['mode' => 'cash', 'amount' => 0.0, 'transaction_id' => ''];
    foreach ($splits as $split) {
        if ((float)$split['amount'] > (float)$primary['amount']) {
            $primary = $split;
        }
    }

    return [
        'splits' => $splits,
        'total' => $total,
        'primary_mode' => (string)($primary['mode'] ?? 'cash'),
        'primary_txn' => (string)($primary['transaction_id'] ?? ''),
    ];
}

/**
 * @param array{splits?:list<array{mode?:string,amount?:float|int|string,transaction_id?:string}>,total?:float} $splitBundle
 *
 * @return list<string>
 */
function pos_payment_validate_splits(
    array $splitBundle,
    float $targetTotal,
    string $paymentStage,
    ?array $followUpSession = null
): array {
    $paymentStage = strtolower(trim($paymentStage));
    if ($paymentStage === 'zero_advance') {
        return [];
    }

    $errors = [];
    $splits = $splitBundle['splits'] ?? [];
    if ($splits === []) {
        return ['Add at least one payment line.'];
    }

    $isWaivedAllowed = false;
    if (is_array($followUpSession)) {
        $type = strtolower(trim((string) ($followUpSession['follow_up_type'] ?? '')));
        $pricingMode = strtolower(trim((string) ($followUpSession['pricing_mode'] ?? '')));
        if (in_array($type, ['reship', 'replace'], true) && $pricingMode === 'waived') {
            $isWaivedAllowed = true;
        }
    }

    $hasWaived = false;
    foreach ($splits as $split) {
        if (pos_payment_is_waived_mode((string)($split['mode'] ?? ''))) {
            $hasWaived = true;
            break;
        }
    }

    if ($hasWaived && !$isWaivedAllowed) {
        return ['Waived payment mode is only allowed for Reship or Replacement follow-up orders.'];
    }

    if ($isWaivedAllowed && $hasWaived) {
        foreach ($splits as $split) {
            $mode = strtolower(trim((string)($split['mode'] ?? '')));
            if (!pos_payment_is_waived_mode($mode)) {
                return ['For waived follow-up orders, all payment lines must be set to Waived.'];
            }
            $amount = round((float)($split['amount'] ?? 0), 2);
            if ($amount > 0.001) {
                return ['Waived payment line must be zero amount.'];
            }
        }

        return [];
    }

    $advanceTotal = pos_payment_split_advance_total($splits);
    $codTotal = pos_payment_split_cod_total($splits);
    $splitTotal = round($advanceTotal + $codTotal, 2);
    $hasCod = $codTotal > 0.001;
    $paymentStage = strtolower(trim($paymentStage));

    foreach ($splits as $idx => $split) {
        $amount = round((float)($split['amount'] ?? 0), 2);
        $mode = strtolower(trim((string)($split['mode'] ?? '')));
        if (pos_payment_is_waived_mode($mode)) {
            return ['Waived payment mode is only allowed for Reship or Replacement follow-up orders.'];
        }
        if ($amount <= 0) {
            return ['Each payment line must have amount greater than zero (line ' . ($idx + 1) . ').'];
        }
    }

    if ($targetTotal <= 0.001) {
        if ($isWaivedAllowed) {
            return [];
        }

        return ['Order total is zero. Payment waiving is only allowed for Reship or Replacement follow-up orders.'];
    }

    if ($hasCod) {
        $hasPickup = false;
        foreach ($splits as $split) {
            if (strtolower(trim((string)($split['mode'] ?? ''))) === 'pay_on_pickup') {
                $hasPickup = true;
                break;
            }
        }
        $pendingLabel = $hasPickup ? 'Advance plus Pay on Pickup' : 'Advance plus COD';
        if ($splitTotal + 0.02 < $targetTotal) {
            $errors[] = $pendingLabel . ' must equal order total ₹ ' . $targetTotal . '.';
        } elseif ($splitTotal - 0.02 > $targetTotal) {
            $errors[] = $pendingLabel . ' exceeds order total.';
        }
    } else {
        $paymentAmount = round((float)($splitBundle['total'] ?? 0), 2);
        if ($paymentAmount <= 0) {
            return ['Payment amount must be greater than zero.'];
        }

        if ($paymentStage === 'final') {
            if ($paymentAmount + 0.02 < $targetTotal) {
                $errors[] = 'Final payment must match order total ₹ ' . $targetTotal . '.';
            } elseif ($paymentAmount - 0.02 > $targetTotal) {
                $errors[] = 'Over payment is not allowed for final settlement.';
            }
        } elseif ($paymentStage === 'partial' || $paymentStage === 'advance') {
            if ($targetTotal > 0 && $paymentAmount + 0.02 >= $targetTotal) {
                $errors[] = 'Partial / advance must be less than order total ₹ ' . $targetTotal . '.';
            }
        }
    }

    foreach ($splits as $idx => $split) {
        $mode = strtolower(trim((string)($split['mode'] ?? '')));
        $txn = trim((string)($split['transaction_id'] ?? ''));
        if (($mode === 'razorpay' || $mode === 'cheque') && $txn === '') {
            $errors[] = ($mode === 'cheque' ? 'Cheque number' : 'Transaction ID')
                . ' is required for ' . $mode . ' (line ' . ($idx + 1) . ').';
        }
    }

    return $errors;
}

function pos_payment_sum_paid(mysqli $conn, string $orderNumber): float
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0.0;
    }

    $stmt = $conn->prepare(
        'SELECT IFNULL(SUM(payment_amount), 0) AS paid FROM pos_payments WHERE order_number = ? AND LOWER(TRIM(payment_mode)) <> \'cod\''
    );
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round((float)($row['paid'] ?? 0), 2);
}

function pos_payment_sum_cod_pending(mysqli $conn, string $orderNumber): float
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0.0;
    }

    $stmt = $conn->prepare(
        'SELECT IFNULL(SUM(payment_amount), 0) AS cod
         FROM pos_payments
         WHERE order_number = ?
           AND LOWER(TRIM(payment_mode)) = \'cod\'
           AND LOWER(TRIM(COALESCE(payment_status, \'pending\'))) = \'pending\''
    );
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round((float)($row['cod'] ?? 0), 2);
}

function pos_payment_sum_receipt_total(mysqli $conn, string $orderNumber): float
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0.0;
    }

    $stmt = $conn->prepare(
        'SELECT IFNULL(SUM(payment_amount), 0) AS receipt_total FROM pos_payments WHERE order_number = ?'
    );
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round((float)($row['receipt_total'] ?? 0), 2);
}

function pos_payment_sum_allocated(mysqli $conn, string $orderNumber): float
{
    return pos_payment_sum_receipt_total($conn, $orderNumber);
}

/**
 * When non-COD payments complete the order, COD obligation rows are fulfilled.
 */
function pos_payment_mark_cod_collected_if_fully_paid(mysqli $conn, string $orderNumber): void
{
    if (!pos_payment_is_fully_paid($conn, $orderNumber)) {
        return;
    }

    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return;
    }

    $stmt = $conn->prepare(
        'UPDATE pos_payments
         SET payment_status = \'success\'
         WHERE order_number = ?
           AND LOWER(TRIM(payment_mode)) IN (\'cod\', \'pay_on_pickup\')
           AND LOWER(TRIM(COALESCE(payment_status, \'pending\'))) = \'pending\''
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $stmt->close();
}

function pos_payment_compute_pending_amount(float $orderTotal, float $collectedNonCod, float $codObligation): float
{
    return max(0.0, round($orderTotal - $collectedNonCod - $codObligation, 2));
}

function pos_payment_is_fully_paid(mysqli $conn, string $orderNumber): bool
{
    $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    if ($orderTotal <= 0) {
        return false;
    }

    return pos_payment_sum_paid($conn, $orderNumber) + 0.02 >= $orderTotal;
}

/**
 * Receipt plan complete: sum of all payment receipt rows (including COD) covers order total.
 */
function pos_payment_is_allocation_complete(mysqli $conn, string $orderNumber): bool
{
    $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    if ($orderTotal <= 0) {
        return false;
    }

    return pos_payment_sum_receipt_total($conn, $orderNumber) + 0.02 >= $orderTotal;
}

/**
 * POS checkout order exists only after at least one payment receipt row is saved.
 */
function pos_payment_has_recorded_payments(mysqli $conn, string $orderNumber): bool
{
    return pos_payment_sum_receipt_total($conn, $orderNumber) > 0.001;
}

/** @deprecated Use pos_payment_has_recorded_payments() */
function pos_payment_is_invoice_eligible(mysqli $conn, string $orderNumber): bool
{
    return pos_payment_has_recorded_payments($conn, $orderNumber);
}

/**
 * Invoice type from receipt total vs order total.
 *
 * No payment receipts → no order (null).
 * Receipt total >= order → final (COD receipt rows count).
 * Receipt total > 0 but below order → proforma only.
 *
 * @return 'final'|'proforma'|null null when no payments recorded
 */
function pos_payment_resolve_auto_invoice_status(mysqli $conn, string $orderNumber): ?string
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return null;
    }

    if (!pos_payment_has_recorded_payments($conn, $orderNumber)) {
        return null;
    }

    if (pos_payment_is_allocation_complete($conn, $orderNumber)) {
        return 'final';
    }

    // Proforma creation removed - invoice is created only upon full payment/allocation
    return null;
}

/**
 * Proforma creation disabled; delegates to finalization if allocation is complete.
 *
 * @return array{success:bool,attempted:bool,fully_paid:bool,invoice_id:int,created:bool,message?:string}
 */
function pos_payment_ensure_proforma_invoice_for_order(mysqli $conn, string $orderNumber): array
{
    $orderNumber = trim($orderNumber);
    $empty = [
        'success' => true,
        'attempted' => false,
        'fully_paid' => false,
        'invoice_id' => 0,
        'created' => false,
        'message' => 'Proforma creation disabled. Invoice will be created upon full payment.',
    ];
    if ($orderNumber === '') {
        $empty['message'] = 'Order number missing';
        return $empty;
    }

    if (pos_payment_is_allocation_complete($conn, $orderNumber)) {
        return pos_payment_finalize_invoice_for_order($conn, $orderNumber);
    }

    return $empty;
}

/**
 * Recompute order_amount / pending_amount on every pos_payments row for an order (after edits).
 */
function pos_payment_refresh_order_snapshots(mysqli $conn, string $orderNumber): void
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return;
    }

    pos_payment_mark_cod_collected_if_fully_paid($conn, $orderNumber);

    $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    if ($orderTotal <= 0) {
        return;
    }

    $stmt = $conn->prepare(
        'SELECT id, payment_mode, payment_amount, payment_status FROM pos_payments WHERE order_number = ? ORDER BY id ASC'
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $res = $stmt->get_result();
    $stmt->close();

    $collected = 0.0;
    $codObligation = 0.0;
    $upd = $conn->prepare('UPDATE pos_payments SET order_amount = ?, pending_amount = ? WHERE id = ?');
    if (!$upd) {
        return;
    }

    while ($row = $res->fetch_assoc()) {
        $amount = round((float)($row['payment_amount'] ?? 0), 2);
        if (pos_payment_is_cod_mode((string)($row['payment_mode'] ?? ''))) {
            if (strtolower(trim((string)($row['payment_status'] ?? 'pending'))) === 'pending') {
                $codObligation += $amount;
            }
        } else {
            $collected += $amount;
        }
        $pending = pos_payment_compute_pending_amount($orderTotal, $collected, $codObligation);
        $id = (int)($row['id'] ?? 0);
        $upd->bind_param('ddi', $orderTotal, $pending, $id);
        $upd->execute();
    }
    $upd->close();
}

/**
 * Active (non-cancelled) invoice id for an order, if any.
 */
function pos_payment_find_invoice_id(mysqli $conn, string $orderNumber): int
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0;
    }

    $stmt = $conn->prepare(
        'SELECT i.id
         FROM vp_invoices i
         INNER JOIN vp_invoice_items ii ON ii.invoice_id = i.id
         WHERE ii.order_number = ?
           AND LOWER(TRIM(COALESCE(i.status, \'\'))) <> \'cancelled\'
         ORDER BY i.id DESC
         LIMIT 1'
    );
    if (!$stmt) {
        return 0;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row['id'] ?? 0);
}

/**
 * When receipt total covers the order: finalize proforma or create a final tax invoice.
 *
 * @return array{success:bool,attempted:bool,fully_paid:bool,invoice_id:int,created:bool,message?:string}
 */
function pos_payment_finalize_invoice_for_order(mysqli $conn, string $orderNumber): array
{
    $orderNumber = trim($orderNumber);
    $empty = [
        'success' => true,
        'attempted' => false,
        'fully_paid' => false,
        'invoice_id' => 0,
        'created' => false,
    ];
    if ($orderNumber === '') {
        return $empty;
    }

    if (!pos_payment_is_allocation_complete($conn, $orderNumber)) {
        return $empty;
    }

    require_once __DIR__ . '/../models/PosInvoice/invoice.php';
    $invoiceModel = new POSInvoice($conn);
    $existing = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
        if ($existing) {
            $status = strtolower(trim((string)($existing['status'] ?? '')));
            $invoiceId = (int)($existing['id'] ?? 0);
            if ($status === 'final') {
                if ($invoiceId > 0) {
                    require_once __DIR__ . '/../controllers/PosInvoiceController.php';
                    $posInv = new PosInvoiceController();
                    $posInv->repairPosInvoiceMetadataForOrder($invoiceId, $orderNumber);
                }

                return [
                    'success' => true,
                    'attempted' => true,
                    'fully_paid' => true,
                    'invoice_id' => $invoiceId,
                    'created' => false,
                ];
            }
        if (in_array($status, ['proforma', 'draft'], true) && $invoiceId > 0) {
            $stmt = $conn->prepare(
                'UPDATE vp_invoices SET status = \'final\', invoice_date = CURDATE() WHERE id = ?'
            );
            if ($stmt) {
                $stmt->bind_param('i', $invoiceId);
                $stmt->execute();
                $stmt->close();
            }

            require_once __DIR__ . '/../controllers/PosInvoiceController.php';
            $posInv = new PosInvoiceController();
            $posInv->repairPosInvoiceMetadataForOrder($invoiceId, $orderNumber);

            return [
                'success' => true,
                'attempted' => true,
                'fully_paid' => true,
                'invoice_id' => $invoiceId,
                'created' => false,
            ];
        }
    }

    require_once __DIR__ . '/../controllers/PosInvoiceController.php';
    $posInv = new PosInvoiceController();
    $created = $posInv->createAutoInvoiceForOrder($orderNumber, '', true);
    if (!empty($created['success']) && !empty($created['invoice_id'])) {
        return [
            'success' => true,
            'attempted' => true,
            'fully_paid' => true,
            'invoice_id' => (int)$created['invoice_id'],
            'created' => true,
        ];
    }

    return [
        'success' => false,
        'attempted' => true,
        'fully_paid' => true,
        'invoice_id' => 0,
        'created' => false,
        'message' => (string)($created['message'] ?? 'Invoice could not be created.'),
        'require_compliance' => !empty($created['require_compliance']),
        'compliance_code' => $created['compliance_code'] ?? '',
        'customer_id' => (int)($created['customer_id'] ?? 0),
        'gstin' => (string)($created['gstin'] ?? ''),
        'pan' => (string)($created['pan'] ?? ''),
        'residency_status' => (string)($created['residency_status'] ?? ''),
        'missing_fields' => $created['missing_fields'] ?? [],
    ];
}

/**
 * Order total (vp_orders) and balance remaining after this payment is applied (pos_payments exclude new row).
 * When $orderTotalOverride is set (>0), use it instead of vp_orders (e.g. Exotic order not imported yet).
 *
 * @return array{order_amount: float, pending_amount: float}
 */
function pos_payment_compute_order_snapshots(
    mysqli $conn,
    string $orderNumber,
    float $thisPaymentAmount,
    ?float $orderTotalOverride = null,
    string $paymentMode = ''
): array
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return ['order_amount' => 0.0, 'pending_amount' => 0.0];
    }

    $orderTotal = 0.0;
    if ($orderTotalOverride !== null && $orderTotalOverride >= 0) {
        $orderTotal = round($orderTotalOverride, 2);
    } else {
        $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    }

    $collectedPrior = pos_payment_sum_paid($conn, $orderNumber);
    $codObligationPrior = pos_payment_sum_cod_pending($conn, $orderNumber);
    $amount = round($thisPaymentAmount, 2);

    if (pos_payment_is_cod_mode($paymentMode)) {
        $pendingAfter = pos_payment_compute_pending_amount(
            $orderTotal,
            $collectedPrior,
            $codObligationPrior + $amount
        );
    } else {
        $pendingAfter = pos_payment_compute_pending_amount(
            $orderTotal,
            $collectedPrior + $amount,
            $codObligationPrior
        );
    }

    return [
        'order_amount' => $orderTotal,
        'pending_amount' => $pendingAfter,
    ];
}

function pos_payment_fallback_warehouse_id(mysqli $conn): int
{
    $queries = [
        'SELECT id FROM exotic_address WHERE is_active = 1 ORDER BY is_default DESC, id ASC LIMIT 1',
        'SELECT id FROM exotic_address WHERE is_active = 1 ORDER BY id ASC LIMIT 1',
        'SELECT id FROM exotic_address ORDER BY id ASC LIMIT 1',
    ];
    foreach ($queries as $sql) {
        $res = @$conn->query($sql);
        if ($res && ($row = $res->fetch_assoc()) && !empty($row['id'])) {
            return (int)$row['id'];
        }
    }

    return 0;
}

/**
 * Insert one pos_payments row (no order_id column — link by order_number only).
 * Uses concrete warehouse FK; omits customer_id when <= 0 so NULL/DEFAULT applies.
 * If FK fails with a positive customer_id, retries once without customer_id (handles missing vp_customers rows).
 *
 * @return array{success:bool, payment_id:int, warehouse_id_used:int, error:?string, order_amount?:float, pending_amount?:float}
 */
function pos_payment_insert_row(
    mysqli $conn,
    string $orderNumber,
    string $receiptNumber,
    int $customerId,
    string $paymentStage,
    string $paymentMode,
    float $amount,
    string $transactionId,
    string $note,
    int $userId,
    int $warehouseId,
    bool $retryWithoutCustomerIfFkFails = true,
    ?float $orderTotalOverride = null
): array {
    $whEff = $warehouseId > 0 ? $warehouseId : pos_payment_fallback_warehouse_id($conn);
    if ($whEff <= 0) {
        return [
            'success' => false,
            'payment_id' => 0,
            'warehouse_id_used' => 0,
            'error' => 'No warehouse row in exotic_address (warehouse_id FK). Add an active warehouse or set session warehouse.',
        ];
    }

    $snap = pos_payment_compute_order_snapshots($conn, $orderNumber, $amount, $orderTotalOverride, $paymentMode);
    $orderAmtSnap = $snap['order_amount'];
    $pendingAmtSnap = $snap['pending_amount'];
    $paymentStatus = pos_payment_is_cod_mode($paymentMode) ? 'pending' : 'success';

    $stageClean = strtolower(trim($paymentStage));
    $dbPaymentStage = ($stageClean === 'zero_advance') ? 'advance' : $stageClean;

    if ($customerId > 0) {
        $stmt = $conn->prepare(
            'INSERT INTO pos_payments (order_number, receipt_number, customer_id, payment_stage, payment_mode, payment_amount, order_amount, pending_amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', ?, NOW())'
        );
        if (!$stmt) {
            return [
                'success' => false,
                'payment_id' => 0,
                'warehouse_id_used' => $whEff,
                'error' => 'Prepare failed (with customer): ' . $conn->error,
                'order_amount' => $orderAmtSnap,
                'pending_amount' => $pendingAmtSnap,
            ];
        }
        $cid = $customerId;
        $stmt->bind_param(
            'ssissdddssiis',
            $orderNumber,
            $receiptNumber,
            $cid,
            $dbPaymentStage,
            $paymentMode,
            $amount,
            $orderAmtSnap,
            $pendingAmtSnap,
            $transactionId,
            $note,
            $userId,
            $whEff,
            $paymentStatus
        );

        if (!$stmt->execute()) {
            $err = $stmt->error;
            $errno = (int)$conn->errno;
            $stmt->close();
            $isFk = ($errno === 1452 || $errno === 1216 || str_contains(strtolower($err), 'foreign key constraint'));
            if ($retryWithoutCustomerIfFkFails && $isFk) {
                return pos_payment_insert_row(
                    $conn,
                    $orderNumber,
                    $receiptNumber,
                    0,
                    $paymentStage,
                    $paymentMode,
                    $amount,
                    $transactionId,
                    $note,
                    $userId,
                    $warehouseId,
                    false,
                    $orderTotalOverride
                );
            }

            return [
                'success' => false,
                'payment_id' => 0,
                'warehouse_id_used' => $whEff,
                'error' => $err,
                'order_amount' => $orderAmtSnap,
                'pending_amount' => $pendingAmtSnap,
            ];
        }

        $newId = (int)$conn->insert_id;
        $stmt->close();

        pos_payment_update_order_info_payment_mode($conn, $orderNumber);

        return [
            'success' => true,
            'payment_id' => $newId,
            'warehouse_id_used' => $whEff,
            'error' => null,
            'order_amount' => $orderAmtSnap,
            'pending_amount' => $pendingAmtSnap,
        ];
    }

    $stmt = $conn->prepare(
        'INSERT INTO pos_payments (order_number, receipt_number, payment_stage, payment_mode, payment_amount, order_amount, pending_amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
         VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', ?, NOW())'
    );
    if (!$stmt) {
        return [
            'success' => false,
            'payment_id' => 0,
            'warehouse_id_used' => $whEff,
            'error' => 'Prepare failed (no customer column): ' . $conn->error,
            'order_amount' => $orderAmtSnap,
            'pending_amount' => $pendingAmtSnap,
        ];
    }
    $stmt->bind_param(
        'ssssdddssiis',
        $orderNumber,
        $receiptNumber,
        $dbPaymentStage,
        $paymentMode,
        $amount,
        $orderAmtSnap,
        $pendingAmtSnap,
        $transactionId,
        $note,
        $userId,
        $whEff,
        $paymentStatus
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();

        return [
            'success' => false,
            'payment_id' => 0,
            'warehouse_id_used' => $whEff,
            'error' => $err,
            'order_amount' => $orderAmtSnap,
            'pending_amount' => $pendingAmtSnap,
        ];
    }
    $newId = (int)$conn->insert_id;
    $stmt->close();

    pos_payment_update_order_info_payment_mode($conn, $orderNumber);

    return [
        'success' => true,
        'payment_id' => $newId,
        'warehouse_id_used' => $whEff,
        'error' => null,
        'order_amount' => $orderAmtSnap,
        'pending_amount' => $pendingAmtSnap,
    ];
}

/**
 * Ensure vp_order_info table has payment_mode column.
 */
function pos_payment_ensure_order_info_payment_mode_column(mysqli $conn): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $check = $conn->query("SHOW COLUMNS FROM vp_order_info LIKE 'payment_mode'");
    if ($check && $check->num_rows === 0) {
        @$conn->query("ALTER TABLE vp_order_info ADD COLUMN payment_mode VARCHAR(100) NULL DEFAULT NULL AFTER payment_type");
    }
    $done = true;
}

/**
 * Auto fill vp_order_info.payment_mode on the basis of payment_type and pos_payments.payment_mode.
 * 
 * Rules:
 * If payment_type = 'offline':
 *   Check pos_payments for order_number.
 *   If any pos_payments.payment_mode is 'bank_transfer' or 'upi': set value 'YES2971'
 *   Else if pos_payments.payment_mode exists: set value pos_payments.payment_mode
 *   Else: set 'offline'
 * Else:
 *   Set payment_type value
 */
function pos_payment_resolve_order_payment_mode(mysqli $conn, string $orderNumber, ?string $paymentType = null, ?string $givenPaymentMode = null): string
{
    pos_payment_ensure_order_info_payment_mode_column($conn);

    $orderNumber = trim($orderNumber);
    $payType = trim((string)$paymentType);
    $givenMode = trim((string)$givenPaymentMode);

    if ($payType === '' && $orderNumber !== '') {
        $stmt = $conn->prepare('SELECT payment_type FROM vp_order_info WHERE order_number = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $orderNumber);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $row = $res->fetch_assoc()) {
                $payType = trim((string)($row['payment_type'] ?? ''));
            }
            $stmt->close();
        }
    }

    $candidates = array_unique(array_filter([strtolower($payType), strtolower($givenMode)]));

    foreach ($candidates as $cand) {
        if (in_array($cand, ['upi', 'bank_transfer', 'bank-transfer', 'banktransfer', 'cheque'], true)) {
            return 'YES2971';
        }
        if (in_array($cand, ['pos_machine', 'pos'], true)) {
            return 'pos';
        }
    }

    $isCod = in_array('cod', $candidates, true);

    if (in_array('offline', $candidates, true) && $orderNumber !== '') {
        // Check if any payment split is cod
        $stmt = $conn->prepare("SELECT payment_mode FROM pos_payments WHERE order_number = ? AND LOWER(TRIM(payment_mode)) = 'cod' LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $orderNumber);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows > 0) {
                $isCod = true;
            }
            $stmt->close();
        }
    }

    if ($isCod) {
        if ($orderNumber !== '') {
            $stmt = $conn->prepare("SELECT courier_name FROM vp_dispatch_details WHERE order_number = ? AND courier_name IS NOT NULL AND TRIM(courier_name) <> '' ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $orderNumber);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    $courierName = trim((string)($row['courier_name'] ?? ''));
                    $stmt->close();
                    if ($courierName !== '') {
                        return $courierName;
                    }
                } else {
                    $stmt->close();
                }
            }
        }
        return 'COD';
    }

    if (in_array('offline', $candidates, true)) {
        if ($orderNumber !== '') {
            // Check if any payment split is bank_transfer, upi, or cheque
            $stmt = $conn->prepare("SELECT payment_mode FROM pos_payments WHERE order_number = ? AND (LOWER(TRIM(payment_mode)) = 'bank_transfer' OR LOWER(TRIM(payment_mode)) = 'upi' OR LOWER(TRIM(payment_mode)) = 'cheque') LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('s', $orderNumber);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $res->num_rows > 0) {
                    $stmt->close();
                    return 'YES2971';
                }
                $stmt->close();
            }

            // Otherwise fetch the latest payment mode
            $stmt = $conn->prepare('SELECT payment_mode FROM pos_payments WHERE order_number = ? ORDER BY id DESC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $orderNumber);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($res && $row = $res->fetch_assoc()) {
                    $posMode = strtolower(trim((string)($row['payment_mode'] ?? '')));
                    $rawMode = trim((string)($row['payment_mode'] ?? ''));
                    $stmt->close();
                    if ($posMode === 'bank_transfer' || $posMode === 'upi' || $posMode === 'cheque') {
                        return 'YES2971';
                    }
                    if ($posMode === 'pos_machine' || $posMode === 'pos') {
                        return 'pos';
                    }
                    if ($rawMode !== '') {
                        return $rawMode;
                    }
                } else {
                    $stmt->close();
                }
            }
        }
        return 'offline';
    }

    if ($givenMode !== '') {
        return $givenMode;
    }

    return $payType !== '' ? $payType : 'offline';
}

/**
 * Update vp_order_info.payment_mode for given order_number.
 */
function pos_payment_update_order_info_payment_mode(mysqli $conn, string $orderNumber): void
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return;
    }

    $resolvedMode = pos_payment_resolve_order_payment_mode($conn, $orderNumber);

    $stmt = $conn->prepare('UPDATE vp_order_info SET payment_mode = ? WHERE order_number = ?');
    if ($stmt) {
        $stmt->bind_param('ss', $resolvedMode, $orderNumber);
        $stmt->execute();
        $stmt->close();
    }
}
