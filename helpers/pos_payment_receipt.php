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
 * Priority: vp_order_info.total → pos_payments.order_amount → vp_invoices.total_amount → line subtotal minus reductions.
 */
function pos_payment_resolve_order_total(mysqli $conn, string $orderNumber): float
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return 0.0;
    }

    // POS checkout stores the payable total on payment rows — prefer over imported vp_order_info
    // when custom_reduce on vp_orders makes imported totals stale (e.g. 7.28 vs 11.20).
    $stmt = $conn->prepare(
        'SELECT MAX(order_amount) AS order_total FROM pos_payments WHERE order_number = ? AND order_amount > 0'
    );
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total = round((float)($row['order_total'] ?? 0), 2);
        if ($total > 0) {
            return $total;
        }
    }

    $stmt = $conn->prepare('SELECT total FROM vp_order_info WHERE order_number = ? LIMIT 1');
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total = round((float)($row['total'] ?? 0), 2);
        if ($total > 0) {
            return $total;
        }
    }

    $stmt = $conn->prepare(
        'SELECT total_amount FROM vp_invoices WHERE order_number = ? ORDER BY id DESC LIMIT 1'
    );
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $total = round((float)($row['total_amount'] ?? 0), 2);
        if ($total > 0) {
            return $total;
        }
    }

    $stmt = $conn->prepare(
        'SELECT
            IFNULL(SUM(o.finalprice * o.quantity), 0) AS subtotal,
            IFNULL(MAX(o.custom_reduce), 0) AS custom_reduce,
            IFNULL(MAX(oi.coupon_reduce), 0) AS coupon_reduce,
            IFNULL(MAX(oi.giftvoucher_reduce), 0) AS gift_reduce,
            IFNULL(MAX(oi.credit), 0) AS credit
         FROM vp_orders o
         LEFT JOIN vp_order_info oi ON oi.order_number = o.order_number
         WHERE o.order_number = ?'
    );
    if ($stmt) {
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $subtotal = round((float)($row['subtotal'] ?? 0), 2);
        $reductions = round(
            (float)($row['custom_reduce'] ?? 0)
            + (float)($row['coupon_reduce'] ?? 0)
            + (float)($row['gift_reduce'] ?? 0)
            + (float)($row['credit'] ?? 0),
            2
        );
        if ($subtotal > 0) {
            return max(0.0, round($subtotal - $reductions, 2));
        }
    }

    return 0.0;
}

function pos_payment_is_cod_mode(string $mode): bool
{
    return strtolower(trim($mode)) === 'cod';
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

function pos_payment_sum_allocated(mysqli $conn, string $orderNumber): float
{
    return round(
        pos_payment_sum_paid($conn, $orderNumber) + pos_payment_sum_cod_pending($conn, $orderNumber),
        2
    );
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
           AND LOWER(TRIM(payment_mode)) = \'cod\'
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
 * Checkout plan complete: collected + pending COD obligation covers order total.
 * Row pending ₹0 on a COD line means this — not that payment was fully collected.
 */
function pos_payment_is_allocation_complete(mysqli $conn, string $orderNumber): bool
{
    $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
    if ($orderTotal <= 0) {
        return false;
    }

    $collected = pos_payment_sum_paid($conn, $orderNumber);
    $codObligation = pos_payment_sum_cod_pending($conn, $orderNumber);

    return $collected + $codObligation + 0.02 >= $orderTotal;
}

/**
 * Create or return a proforma invoice for advance/COD checkout (before full collection).
 *
 * @return array{success:bool,attempted:bool,fully_paid:bool,invoice_id:int,created:bool,message?:string}
 */
function pos_payment_ensure_proforma_invoice_for_order(mysqli $conn, string $orderNumber): array
{
    $orderNumber = trim($orderNumber);
    $empty = [
        'success' => false,
        'attempted' => true,
        'fully_paid' => false,
        'invoice_id' => 0,
        'created' => false,
    ];
    if ($orderNumber === '') {
        $empty['message'] = 'Order number missing';
        return $empty;
    }

    if (pos_payment_is_fully_paid($conn, $orderNumber)) {
        return pos_payment_finalize_invoice_for_order($conn, $orderNumber);
    }

    if (!pos_payment_is_allocation_complete($conn, $orderNumber)
        || pos_payment_sum_cod_pending($conn, $orderNumber) <= 0.001) {
        $empty['message'] = 'Proforma invoice is available when advance plus COD covers the order total.';
        return $empty;
    }

    require_once __DIR__ . '/../models/PosInvoice/invoice.php';
    $invoiceModel = new POSInvoice($conn);
    $existing = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
    if ($existing) {
        $invoiceId = (int)($existing['id'] ?? 0);
        if ($invoiceId > 0) {
            require_once __DIR__ . '/../controllers/PosInvoiceController.php';
            $posInv = new PosInvoiceController();
            $posInv->repairPosInvoiceMetadataForOrder($invoiceId, $orderNumber);
        }

        return [
            'success' => true,
            'attempted' => true,
            'fully_paid' => false,
            'invoice_id' => $invoiceId,
            'created' => false,
        ];
    }

    require_once __DIR__ . '/../controllers/PosInvoiceController.php';
    $posInv = new PosInvoiceController();
    $created = $posInv->createAutoInvoiceForOrder($orderNumber, '', false);
    if (!empty($created['success']) && !empty($created['invoice_id'])) {
        return [
            'success' => true,
            'attempted' => true,
            'fully_paid' => false,
            'invoice_id' => (int)$created['invoice_id'],
            'created' => true,
        ];
    }

    $empty['message'] = (string)($created['message'] ?? 'Proforma invoice could not be created.');
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
 * When order is fully paid: finalize proforma or create a final tax invoice.
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

    if (!pos_payment_is_fully_paid($conn, $orderNumber)) {
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
    if ($orderTotalOverride !== null && $orderTotalOverride > 0) {
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
            $paymentStage,
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
        $paymentStage,
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

    return [
        'success' => true,
        'payment_id' => $newId,
        'warehouse_id_used' => $whEff,
        'error' => null,
        'order_amount' => $orderAmtSnap,
        'pending_amount' => $pendingAmtSnap,
    ];
}
