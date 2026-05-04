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
 * Order total (vp_orders) and balance remaining after this payment is applied (pos_payments exclude new row).
 * When $orderTotalOverride is set (>0), use it instead of vp_orders (e.g. Exotic order not imported yet).
 *
 * @return array{order_amount: float, pending_amount: float}
 */
function pos_payment_compute_order_snapshots(mysqli $conn, string $orderNumber, float $thisPaymentAmount, ?float $orderTotalOverride = null): array
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return ['order_amount' => 0.0, 'pending_amount' => 0.0];
    }

    $orderTotal = 0.0;
    if ($orderTotalOverride !== null && $orderTotalOverride > 0) {
        $orderTotal = round($orderTotalOverride, 2);
    } else {
        $stmt = $conn->prepare('SELECT IFNULL(SUM(finalprice), 0) AS t FROM vp_orders WHERE order_number = ?');
        if ($stmt) {
            $stmt->bind_param('s', $orderNumber);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $orderTotal = round((float)($row['t'] ?? 0), 2);
        }
    }

    $paidPrior = 0.0;
    $stmt2 = $conn->prepare('SELECT IFNULL(SUM(payment_amount), 0) AS s FROM pos_payments WHERE order_number = ?');
    if ($stmt2) {
        $stmt2->bind_param('s', $orderNumber);
        $stmt2->execute();
        $row2 = $stmt2->get_result()->fetch_assoc();
        $stmt2->close();
        $paidPrior = round((float)($row2['s'] ?? 0), 2);
    }

    $pendingAfter = round($orderTotal - $paidPrior - $thisPaymentAmount, 2);

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

    $snap = pos_payment_compute_order_snapshots($conn, $orderNumber, $amount, $orderTotalOverride);
    $orderAmtSnap = $snap['order_amount'];
    $pendingAmtSnap = $snap['pending_amount'];

    if ($customerId > 0) {
        $stmt = $conn->prepare(
            'INSERT INTO pos_payments (order_number, receipt_number, customer_id, payment_stage, payment_mode, payment_amount, order_amount, pending_amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', \'success\', NOW())'
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
            'ssissdddssii',
            $orderNumber,
            $receiptNumber,
            $cid,
            $paymentStage,
            $paymentMode,
            $amount,
            $orderAmtSnap,
            $pendingAmtSnap,
            $transactionId,
            $note,
            $userId,
            $whEff
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
         VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', \'success\', NOW())'
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
        'ssssdddssii',
        $orderNumber,
        $receiptNumber,
        $paymentStage,
        $paymentMode,
        $amount,
        $orderAmtSnap,
        $pendingAmtSnap,
        $transactionId,
        $note,
        $userId,
        $whEff
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

    return [
        'success' => true,
        'payment_id' => $newId,
        'warehouse_id_used' => $whEff,
        'error' => null,
        'order_amount' => $orderAmtSnap,
        'pending_amount' => $pendingAmtSnap,
    ];
}
