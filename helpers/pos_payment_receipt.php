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
