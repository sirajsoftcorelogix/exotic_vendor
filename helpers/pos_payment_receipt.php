<?php

/**
 * POS payment receipt / pos_payments.invoice_number:
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
 * Next invoice_number for today: shortCode + YYMMDD + NN (locking matching rows).
 *
 * @throws RuntimeException when NN would exceed 99 for this code and date
 */
function pos_payment_generate_next_invoice_number(mysqli $conn, string $shortCode): string
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
            'SELECT invoice_number FROM pos_payments
             WHERE invoice_number IS NOT NULL AND invoice_number != \'\' AND invoice_number LIKE ?
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
            $inv = trim((string)($row['invoice_number'] ?? ''));
            if ($inv !== '' && preg_match($re, $inv, $m)) {
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

/**
 * Resolved warehouse row id for FK (session unset or invalid).
 *
 * @return int 0 if no exotic_address row exists
 */
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
 * Insert one pos_payments row. Uses concrete warehouse FK; omits customer_id when <= 0 so NULL/DEFAULT applies.
 * If FK fails with a positive customer_id, retries once without customer_id (handles missing vp_customers rows).
 *
 * @return array{success:bool, payment_id:int, warehouse_id_used:int, error:?string}
 */
function pos_payment_insert_row(
    mysqli $conn,
    int $orderPk,
    string $orderNumber,
    string $invoiceNumber,
    int $customerId,
    string $paymentStage,
    string $paymentMode,
    float $amount,
    string $transactionId,
    string $note,
    int $userId,
    int $warehouseId,
    bool $retryWithoutCustomerIfFkFails = true
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

    if ($customerId > 0) {
        $stmt = $conn->prepare(
            'INSERT INTO pos_payments (order_id, order_number, invoice_number, customer_id, payment_stage, payment_mode, amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
             VALUES (?,?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', \'success\', NOW())'
        );
        if (!$stmt) {
            return [
                'success' => false,
                'payment_id' => 0,
                'warehouse_id_used' => $whEff,
                'error' => 'Prepare failed (with customer): ' . $conn->error,
            ];
        }
        $cid = $customerId;
        $stmt->bind_param(
            'ississdssii',
            $orderPk,
            $orderNumber,
            $invoiceNumber,
            $cid,
            $paymentStage,
            $paymentMode,
            $amount,
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
                    $orderPk,
                    $orderNumber,
                    $invoiceNumber,
                    0,
                    $paymentStage,
                    $paymentMode,
                    $amount,
                    $transactionId,
                    $note,
                    $userId,
                    $warehouseId,
                    false
                );
            }

            return ['success' => false, 'payment_id' => 0, 'warehouse_id_used' => $whEff, 'error' => $err];
        }

        $newId = (int)$conn->insert_id;
        $stmt->close();

        return ['success' => true, 'payment_id' => $newId, 'warehouse_id_used' => $whEff, 'error' => null];
    }

    /* No customer_id column → allows NULL when walk-in customer is not selected */
    $stmt = $conn->prepare(
        'INSERT INTO pos_payments (order_id, order_number, invoice_number, payment_stage, payment_mode, amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
         VALUES (?,?,?,?,?,?,?,?,NOW(),?,?, \'INR\', \'success\', NOW())'
    );
    if (!$stmt) {
        return [
            'success' => false,
            'payment_id' => 0,
            'warehouse_id_used' => $whEff,
            'error' => 'Prepare failed (no customer column): ' . $conn->error,
        ];
    }
    $stmt->bind_param(
        'ississdssii',
        $orderPk,
        $orderNumber,
        $invoiceNumber,
        $paymentStage,
        $paymentMode,
        $amount,
        $transactionId,
        $note,
        $userId,
        $whEff
    );

    if (!$stmt->execute()) {
        $err = $stmt->error;
        $stmt->close();

        return ['success' => false, 'payment_id' => 0, 'warehouse_id_used' => $whEff, 'error' => $err];
    }
    $newId = (int)$conn->insert_id;
    $stmt->close();

    return ['success' => true, 'payment_id' => $newId, 'warehouse_id_used' => $whEff, 'error' => null];
}
