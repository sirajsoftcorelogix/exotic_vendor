<?php

/**
 * Local POS checkout when Exotic /order/create fails: temp order number, vp_orders + vp_order_info,
 * payment + invoice continue locally; publish to Exotic later via stored payload.
 */

function pos_local_checkout_is_temp_order_number(string $orderNumber): bool
{
    $orderNumber = strtoupper(trim($orderNumber));

    return str_starts_with($orderNumber, 'POS-TMP-') || str_starts_with($orderNumber, 'LOCAL-');
}

function pos_local_checkout_generate_temp_order_number(mysqli $conn, int $warehouseId): string
{
    require_once __DIR__ . '/pos_payment_receipt.php';
    $short = pos_payment_resolve_short_code_for_warehouse($conn, $warehouseId);

    try {
        $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
    } catch (Throwable $e) {
        $dt = new DateTime('now');
    }

    for ($attempt = 0; $attempt < 8; ++$attempt) {
        $suffix = $attempt > 0 ? str_pad((string) random_int(10, 99), 2, '0', STR_PAD_LEFT) : '';
        $candidate = 'POS-TMP-' . $short . '-' . $dt->format('ymdHis') . $suffix;
        if (!pos_local_checkout_order_number_exists($conn, $candidate)) {
            return $candidate;
        }
        usleep(100000);
    }

    return 'POS-TMP-' . $short . '-' . $dt->format('ymdHis') . random_int(10, 99);
}

function pos_local_checkout_order_number_exists(mysqli $conn, string $orderNumber): bool
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return false;
    }

    $stmt = $conn->prepare('SELECT 1 FROM vp_orders WHERE order_number = ? LIMIT 1');
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return !empty($row);
}

function pos_local_checkout_sync_storage_dir(): string
{
    return dirname(__DIR__) . '/writable/pos_exotic_sync';
}

function pos_local_checkout_sync_storage_path(string $orderNumber): string
{
    $safe = preg_replace('/[^A-Za-z0-9._-]+/', '_', trim($orderNumber));

    return pos_local_checkout_sync_storage_dir() . '/' . $safe . '.json';
}

function pos_local_checkout_save_pending_sync_payload(string $orderNumber, array $data): bool
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return false;
    }

    $dir = pos_local_checkout_sync_storage_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('[pos_local_checkout] Could not create sync storage dir: ' . $dir);

        return false;
    }

    $payload = array_merge($data, [
        'temp_order_number' => $orderNumber,
        'saved_at' => gmdate('c'),
    ]);
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        return false;
    }

    $path = pos_local_checkout_sync_storage_path($orderNumber);
    $written = file_put_contents($path, $json, LOCK_EX);

    return $written !== false;
}

function pos_local_checkout_load_pending_sync_payload(string $orderNumber): ?array
{
    $path = pos_local_checkout_sync_storage_path($orderNumber);
    if (!is_file($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    if ($raw === false || trim($raw) === '') {
        return null;
    }

    $decoded = json_decode($raw, true);

    return is_array($decoded) ? $decoded : null;
}

function pos_local_checkout_delete_pending_sync_payload(string $orderNumber): void
{
    $path = pos_local_checkout_sync_storage_path($orderNumber);
    if (is_file($path)) {
        @unlink($path);
    }
}

function pos_local_checkout_has_pending_sync(string $orderNumber): bool
{
    return pos_local_checkout_load_pending_sync_payload($orderNumber) !== null;
}

/**
 * Legacy DBs store vp_order_info.order_number as INT; temp POS orders need VARCHAR.
 */
function pos_local_checkout_ensure_order_info_order_number_varchar(mysqli $conn): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $res = $conn->query("SHOW COLUMNS FROM vp_order_info LIKE 'order_number'");
    if (!$res instanceof mysqli_result) {
        return;
    }
    $col = $res->fetch_assoc();
    $res->free();
    if (!is_array($col)) {
        return;
    }

    $type = strtolower(trim((string)($col['Type'] ?? '')));
    if ($type === '' || !str_contains($type, 'int')) {
        return;
    }

    if (!$conn->query('ALTER TABLE vp_order_info MODIFY order_number VARCHAR(100) NOT NULL')) {
        error_log('[pos_local_checkout] vp_order_info.order_number VARCHAR migration failed: ' . $conn->error);
    }
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $cartData
 * @param list<array<string, mixed>> $invoiceLinePrices
 * @param list<array<string, mixed>> $listLinePrices
 * @param array<string, mixed> $createRes
 * @param array<string, string> $postBody
 * @param array{query?:array,extraHeaders?:list<string>} $cartCtx
 *
 * @return array{success:bool,message?:string,order_number?:string}
 */
function pos_local_checkout_try_create_when_api_fails(
    mysqli $conn,
    array $payload,
    array $cartData,
    float $orderTotal,
    int $customerId,
    string $paymentMode,
    string $txn,
    string $localStatus,
    array $invoiceLinePrices,
    array $listLinePrices,
    array $createRes,
    array $postBody,
    array $cartCtx
): array {
    require_once dirname(__DIR__) . '/models/order/order.php';
    $ordersModel = new Order($conn);

    $whId = (int)($_SESSION['warehouse_id'] ?? 0);
    $orderNumber = pos_local_checkout_generate_temp_order_number($conn, $whId);

    $persist = pos_local_checkout_persist_order(
        $conn,
        $ordersModel,
        $orderNumber,
        $cartData,
        $payload,
        $orderTotal,
        $customerId,
        $paymentMode,
        $txn,
        $localStatus,
        $invoiceLinePrices,
        $listLinePrices
    );
    if (empty($persist['success'])) {
        return [
            'success' => false,
            'message' => 'Exotic order/create failed and local save also failed: '
                . (string)($persist['message'] ?? 'unknown'),
        ];
    }

    $apiMsg = trim((string)(is_array($createRes['data'] ?? null)
        ? ($createRes['data']['message'] ?? $createRes['data']['error'] ?? $createRes['data']['errormessage'] ?? '')
        : ''));
    if ($apiMsg === '') {
        $apiMsg = 'HTTP ' . (int)($createRes['code'] ?? 0);
    }

    $remarkLines = [
        'EXOTIC_SYNC_PENDING',
        'Saved locally at POS checkout because Exotic order/create failed.',
        'API: ' . $apiMsg,
        'Publish from order details when Exotic API is available.',
    ];
    $ordersModel->updateOrderRemarks($orderNumber, implode("\n", $remarkLines));

    pos_local_checkout_save_pending_sync_payload($orderNumber, [
        'post_body' => $postBody,
        'cart_query' => is_array($cartCtx['query'] ?? null) ? $cartCtx['query'] : [],
        'cart_extra_headers' => is_array($cartCtx['extraHeaders'] ?? null) ? $cartCtx['extraHeaders'] : [],
        'list_line_prices' => $listLinePrices,
        'api_error' => $apiMsg,
        'warehouse_id' => $whId,
        'customer_id' => $customerId,
    ]);

    return [
        'success' => true,
        'order_number' => $orderNumber,
        'message' => 'Exotic API unavailable — order saved locally as ' . $orderNumber
            . '. Payment recorded; publish to Exotic when the API is working.',
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $cartData
 * @param list<array<string, mixed>> $invoiceLinePrices
 * @param list<array<string, mixed>> $listLinePrices
 *
 * @return array{success:bool,message?:string,lines?:int}
 */
function pos_local_checkout_persist_order(
    mysqli $conn,
    Order $ordersModel,
    string $orderNumber,
    array $cartData,
    array $payload,
    float $orderTotal,
    int $customerId,
    string $paymentMode,
    string $txn,
    string $localStatus,
    array $invoiceLinePrices,
    array $listLinePrices
): array {
    pos_local_checkout_ensure_order_info_order_number_varchar($conn);

    $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
    if (!is_array($items) || count($items) === 0) {
        return ['success' => false, 'message' => 'Cart has no items to save locally.'];
    }

    $invoiceByKey = pos_local_checkout_index_line_prices($invoiceLinePrices, 'price');
    $listByKey = pos_local_checkout_index_line_prices($listLinePrices, 'price');
    $orderDate = date('Y-m-d H:i:s');
    $processedTime = time();
    $cashDiscount = round((float)($payload['receipt_cash_discount'] ?? 0), 2);
    $couponReduce = round((float)($payload['receipt_coupon_discount'] ?? 0), 2);
    $shippingCountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? $payload['confirm_country'] ?? 'IN')), 0, 2));
    if ($shippingCountry === '') {
        $shippingCountry = 'IN';
    }

    $localStatus = strtolower(trim($localStatus));
    if (!in_array($localStatus, ['shipped', 'pending'], true)) {
        $localStatus = 'pending';
    }

    $inserted = 0;
    $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);
    foreach ($items as $row) {
        if (!is_array($row)) {
            continue;
        }
        $itemCode = trim((string)($row['code'] ?? $row['item_code'] ?? $row['itemcode'] ?? ''));
        if ($itemCode === '') {
            continue;
        }

        $size = trim((string)($row['size'] ?? ''));
        $color = trim((string)($row['color'] ?? ''));
        $key = pos_local_checkout_line_key($itemCode, $size, $color);
        $qty = max(1, (int)($row['qty'] ?? $row['quantity'] ?? 1));

        $listUnit = (float)($listByKey[$key]['price'] ?? $row['itemprice'] ?? $row['item_price'] ?? $row['unit_price'] ?? $row['price'] ?? 0);
        if ($listUnit <= 0) {
            $lineTotal = (float)($row['linetotal'] ?? $row['line_total'] ?? $row['finalprice'] ?? 0);
            if ($lineTotal > 0) {
                $listUnit = $lineTotal / $qty;
            }
        }

        $finalUnit = (float)($invoiceByKey[$key]['price'] ?? $row['finalprice'] ?? $row['final_price'] ?? 0);
        if ($finalUnit <= 0) {
            $finalUnit = $listUnit;
        }
        if ($finalUnit <= 0 && $listUnit > 0) {
            $finalUnit = $listUnit;
        }

        $rdata = [
            'sku' => trim((string)($row['sku'] ?? '')),
            'order_number' => $orderNumber,
            'shipping_country' => $shippingCountry,
            'title' => trim((string)($row['name'] ?? $row['title'] ?? $row['product_name'] ?? 'Item')),
            'description' => trim((string)($row['description'] ?? '')),
            'item_code' => $itemCode,
            'size' => $size,
            'color' => $color,
            'groupname' => trim((string)($row['groupname'] ?? '')),
            'subcategories' => trim((string)($row['subcategories'] ?? '')),
            'currency' => trim((string)($row['currency'] ?? 'INR')),
            'itemprice' => number_format(max(0, $listUnit), 2, '.', ''),
            'finalprice' => number_format(max(0, $finalUnit), 2, '.', ''),
            'image' => trim((string)($row['image'] ?? '')),
            'marketplace_vendor' => trim((string)($row['marketplace_vendor'] ?? 'exoticindia')),
            'quantity' => (string)$qty,
            'options' => $row['options'] ?? 0,
            'addons' => Order::normalizeVendorOrderLineAddons($row['addons'] ?? null),
            'gst' => trim((string)($row['gst'] ?? '')),
            'hsn' => trim((string)($row['hscode'] ?? $row['hsn'] ?? '')),
            'local_stock' => is_numeric($row['local_stock'] ?? null) ? (float)$row['local_stock'] : 0.0,
            'cost_price' => (float)($row['cp'] ?? $row['cost_price'] ?? 0),
            'location' => trim((string)($row['location'] ?? '')),
            'order_date' => $orderDate,
            'processed_time' => $processedTime,
            'numsold' => (int)($row['numsold'] ?? 0),
            'product_weight' => (float)($row['product_weight'] ?? 0),
            'product_weight_unit' => trim((string)($row['product_weight_unit'] ?? '')),
            'prod_height' => (float)($row['prod_height'] ?? 0),
            'prod_width' => (float)($row['prod_width'] ?? 0),
            'prod_length' => (float)($row['prod_length'] ?? 0),
            'length_unit' => trim((string)($row['length_unit'] ?? '')),
            'backorder_status' => (int)($row['backorder_status'] ?? 0),
            'backorder_percent' => (int)($row['backorder_percent'] ?? 0),
            'backorder_delay' => trim((string)($row['backorder_delay'] ?? '')),
            'payment_type' => $paymentMode,
            'coupon' => trim((string)($payload['coupon_display_name'] ?? '')),
            'coupon_reduce' => $couponReduce > 0 ? number_format($couponReduce, 2, '.', '') : '',
            'giftvoucher' => '',
            'giftvoucher_reduce' => round((float)($payload['receipt_gift_discount'] ?? 0), 2),
            'credit' => '',
            'vendor' => trim((string)($row['vendor'] ?? '')),
            'country' => $shippingCountry,
            'material' => trim((string)($row['material'] ?? '')),
            'publisher' => trim((string)($row['publisher'] ?? '')),
            'author' => trim((string)($row['author'] ?? '')),
            'shippingfee' => (float)($row['shippingfee'] ?? 0),
            'sourcingfee' => (float)($row['sourcingfee'] ?? 0),
            'status' => $localStatus,
            'esd' => date('Y-m-d', strtotime($orderDate . ' + 3 days')),
            'agent_id' => 0,
            'customer_id' => $customerId,
            'store_name' => $warehouseId > 0 ? (string)$warehouseId : '',
            'custom_reduce' => $inserted === 0 ? $cashDiscount : 0,
        ];

        $res = $ordersModel->insertOrder($rdata);
        if (empty($res['success'])) {
            return [
                'success' => false,
                'message' => (string)($res['message'] ?? $res['error'] ?? 'Could not insert order line'),
            ];
        }
        ++$inserted;
    }

    if ($inserted === 0) {
        return ['success' => false, 'message' => 'No valid cart lines could be saved locally.'];
    }

    $addressOrder = pos_local_checkout_build_address_order_payload(
        $payload,
        $orderNumber,
        $orderTotal,
        $customerId,
        $paymentMode,
        $txn
    );
    $addrRes = $ordersModel->insertAddressInfo($addressOrder, $customerId);
    if (is_array($addrRes)) {
        return [
            'success' => false,
            'message' => 'Order lines saved but address failed: ' . (string)($addrRes['message'] ?? 'unknown'),
        ];
    }

    return ['success' => true, 'lines' => $inserted];
}

/**
 * @param array<string, mixed> $payload
 *
 * @return array<string, mixed>
 */
function pos_local_checkout_build_address_order_payload(
    array $payload,
    string $orderNumber,
    float $orderTotal,
    int $customerId,
    string $paymentMode,
    string $txn
): array {
    $billingCountry = strtoupper(substr(trim((string)($payload['confirm_country'] ?? 'IN')), 0, 2));
    if ($billingCountry === '') {
        $billingCountry = 'IN';
    }
    $shippingCountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? $billingCountry)), 0, 2));
    if ($shippingCountry === '') {
        $shippingCountry = $billingCountry;
    }

    $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
    $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
    if ($sf === '') {
        $sf = trim((string)($payload['confirm_first_name'] ?? ''));
    }
    if ($sl === '') {
        $sl = trim((string)($payload['confirm_last_name'] ?? ''));
    }

    return [
        'orderid' => $orderNumber,
        'total' => $orderTotal,
        'transid' => $txn !== '' ? $txn : ('store.' . gmdate('YmdHis')),
        'currency' => 'INR',
        'payment_type' => $paymentMode,
        'coupon_reduce' => round((float)($payload['receipt_coupon_discount'] ?? 0), 2),
        'giftvoucher_reduce' => round((float)($payload['receipt_gift_discount'] ?? 0), 2),
        'address_info' => [
            'first_name' => trim((string)($payload['confirm_first_name'] ?? '')),
            'last_name' => trim((string)($payload['confirm_last_name'] ?? '')),
            'company' => trim((string)($payload['confirm_company'] ?? '')),
            'address_line1' => trim((string)($payload['confirm_address1'] ?? '')),
            'address_line2' => trim((string)($payload['confirm_address2'] ?? '')),
            'city' => trim((string)($payload['confirm_city'] ?? '')),
            'state' => trim((string)($payload['confirm_state'] ?? '')),
            'state_iso' => trim((string)($payload['confirm_state_iso'] ?? '')),
            'state_code' => trim((string)($payload['confirm_state_code'] ?? '')),
            'country' => $billingCountry,
            'zipcode' => trim((string)($payload['confirm_zip'] ?? '')),
            'mobile' => trim((string)($payload['confirm_phone'] ?? '')),
            'email' => trim((string)($payload['confirm_email'] ?? '')),
            'gstin' => strtoupper(trim((string)($payload['confirm_gstin'] ?? ''))),
            'shipping_first_name' => $sf,
            'shipping_last_name' => $sl,
            'shipping_company' => trim((string)($payload['confirm_scompany'] ?? '')),
            'shipping_address_line1' => trim((string)($payload['confirm_saddress1'] ?? $payload['confirm_address1'] ?? '')),
            'shipping_address_line2' => trim((string)($payload['confirm_saddress2'] ?? $payload['confirm_address2'] ?? '')),
            'shipping_city' => trim((string)($payload['confirm_scity'] ?? $payload['confirm_city'] ?? '')),
            'shipping_state' => trim((string)($payload['confirm_sstate'] ?? $payload['confirm_state'] ?? '')),
            'shipping_state_iso' => trim((string)($payload['confirm_sstate_iso'] ?? '')),
            'shipping_state_code' => trim((string)($payload['confirm_sstate_code'] ?? '')),
            'shipping_country' => $shippingCountry,
            'shipping_zipcode' => trim((string)($payload['confirm_szip'] ?? $payload['confirm_zip'] ?? '')),
            'shipping_mobile' => trim((string)($payload['confirm_sphone'] ?? $payload['confirm_phone'] ?? '')),
            'shipping_email' => trim((string)($payload['confirm_email'] ?? '')),
            'shipping_gstin' => strtoupper(trim((string)($payload['confirm_sgstin'] ?? $payload['confirm_gstin'] ?? ''))),
        ],
    ];
}

/**
 * @param list<array<string, mixed>> $lines
 *
 * @return array<string, array<string, mixed>>
 */
function pos_local_checkout_index_line_prices(array $lines, string $priceField): array
{
    $out = [];
    foreach ($lines as $ln) {
        if (!is_array($ln)) {
            continue;
        }
        $itemCode = trim((string)($ln['itemcode'] ?? $ln['item_code'] ?? $ln['code'] ?? ''));
        if ($itemCode === '') {
            continue;
        }
        $key = pos_local_checkout_line_key(
            $itemCode,
            trim((string)($ln['size'] ?? '')),
            trim((string)($ln['color'] ?? ''))
        );
        $out[$key] = $ln;
    }

    return $out;
}

function pos_local_checkout_line_key(string $itemCode, string $size, string $color): string
{
    return strtolower(trim($itemCode) . '|' . trim($size) . '|' . trim($color));
}

function pos_local_checkout_normalize_facet(?string $val): string
{
    $s = trim((string)$val);
    if ($s === '' || $s === '0' || strcasecmp($s, 'n/a') === 0) {
        return '';
    }

    return $s;
}

function pos_local_checkout_build_variation_from_size_color(string $size, string $color): string
{
    $size = pos_local_checkout_normalize_facet($size);
    $color = pos_local_checkout_normalize_facet($color);
    if ($size === '' && $color === '') {
        return '';
    }
    if ($size === '' && $color !== '') {
        return ':' . $color;
    }
    if ($size !== '' && $color === '') {
        return $size . ':';
    }

    return $size . ':' . $color;
}

function pos_local_checkout_map_pos_payment_mode_to_exotic(string $posMode): string
{
    $m = strtolower(trim($posMode));
    $map = [
        'cash' => 'offline',
        'upi' => 'upi',
        'bank_transfer' => 'bank_transfer',
        'pos_machine' => 'pos_machine',
        'cheque' => 'cheque',
        'razorpay' => 'razorpay',
        'cod' => 'cod',
        'offline' => 'offline',
    ];

    return $map[$m] ?? 'offline';
}

function pos_local_checkout_extract_checkoutdata_from_cart(array $cartData): string
{
    $candidates = [
        $cartData['checkoutdata'] ?? null,
        $cartData['checkout_data'] ?? null,
    ];
    if (isset($cartData['data']) && is_array($cartData['data'])) {
        $candidates[] = $cartData['data']['checkoutdata'] ?? null;
    }
    if (isset($cartData['cart']) && is_array($cartData['cart'])) {
        $candidates[] = $cartData['cart']['checkoutdata'] ?? null;
    }
    foreach ($candidates as $raw) {
        if (is_string($raw) && $raw !== '') {
            return $raw;
        }
    }

    return '';
}

/**
 * @return list<array<string, mixed>>
 */
function pos_local_checkout_load_payments_for_order(mysqli $conn, string $orderNumber): array
{
    $orderNumber = trim($orderNumber);
    if ($orderNumber === '') {
        return [];
    }

    $stmt = $conn->prepare(
        'SELECT payment_mode, payment_amount, transaction_id, payment_status, warehouse_id
         FROM pos_payments WHERE order_number = ? ORDER BY id ASC'
    );
    if (!$stmt) {
        return [];
    }
    $stmt->bind_param('s', $orderNumber);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return is_array($rows) ? $rows : [];
}

function pos_local_checkout_resolve_warehouse_for_order(mysqli $conn, string $orderNumber, ?array $sync): int
{
    if (is_array($sync) && (int)($sync['warehouse_id'] ?? 0) > 0) {
        return (int)$sync['warehouse_id'];
    }

    $payments = pos_local_checkout_load_payments_for_order($conn, $orderNumber);
    foreach ($payments as $row) {
        if ((int)($row['warehouse_id'] ?? 0) > 0) {
            return (int)$row['warehouse_id'];
        }
    }

    return (int)($_SESSION['warehouse_id'] ?? 0);
}

/**
 * @param array<string, mixed> $orderInfo
 * @param list<array<string, mixed>> $payments
 *
 * @return array<string, mixed>
 */
function pos_local_checkout_build_confirm_payload_from_order_info(array $orderInfo, array $payments): array
{
    $splits = [];
    $codAmount = 0.0;
    $primaryMode = 'cash';
    $primaryTxn = '';
    foreach ($payments as $pay) {
        $mode = strtolower(trim((string)($pay['payment_mode'] ?? '')));
        $amount = round((float)($pay['payment_amount'] ?? 0), 2);
        $txn = trim((string)($pay['transaction_id'] ?? ''));
        $splits[] = [
            'mode' => $mode !== '' ? $mode : 'cash',
            'amount' => $amount,
            'transaction_id' => $txn,
        ];
        if ($mode === 'cod') {
            $codAmount += $amount;
        } elseif ($primaryTxn === '' && $txn !== '') {
            $primaryTxn = $txn;
        }
        if ($mode !== 'cod' && $primaryMode === 'cash' && $mode !== '') {
            $primaryMode = $mode;
        }
    }
    if ($codAmount > 0.001) {
        $primaryMode = 'cod';
    }

    return [
        'customer_id' => (int)($orderInfo['customer_id'] ?? 0),
        'payment_mode' => $primaryMode,
        'transaction_id' => $primaryTxn,
        'cod_amount' => round($codAmount, 2),
        'payment_splits' => $splits,
        'confirm_first_name' => trim((string)($orderInfo['first_name'] ?? '')),
        'confirm_last_name' => trim((string)($orderInfo['last_name'] ?? '')),
        'confirm_company' => trim((string)($orderInfo['company'] ?? '')),
        'confirm_address1' => trim((string)($orderInfo['address_line1'] ?? '')),
        'confirm_address2' => trim((string)($orderInfo['address_line2'] ?? '')),
        'confirm_city' => trim((string)($orderInfo['city'] ?? '')),
        'confirm_state' => trim((string)($orderInfo['state'] ?? '')),
        'confirm_state_iso' => trim((string)($orderInfo['state_iso'] ?? '')),
        'confirm_state_code' => trim((string)($orderInfo['state_code'] ?? '')),
        'confirm_country' => trim((string)($orderInfo['country'] ?? 'IN')),
        'confirm_zip' => trim((string)($orderInfo['zipcode'] ?? '')),
        'confirm_phone' => trim((string)($orderInfo['mobile'] ?? '')),
        'confirm_email' => trim((string)($orderInfo['email'] ?? '')),
        'confirm_gstin' => trim((string)($orderInfo['gstin'] ?? '')),
        'confirm_sfirst_name' => trim((string)($orderInfo['shipping_first_name'] ?? '')),
        'confirm_slast_name' => trim((string)($orderInfo['shipping_last_name'] ?? '')),
        'confirm_scompany' => trim((string)($orderInfo['shipping_company'] ?? '')),
        'confirm_saddress1' => trim((string)($orderInfo['shipping_address_line1'] ?? '')),
        'confirm_saddress2' => trim((string)($orderInfo['shipping_address_line2'] ?? '')),
        'confirm_scity' => trim((string)($orderInfo['shipping_city'] ?? '')),
        'confirm_sstate' => trim((string)($orderInfo['shipping_state'] ?? '')),
        'confirm_sstate_iso' => trim((string)($orderInfo['shipping_state_iso'] ?? '')),
        'confirm_sstate_code' => trim((string)($orderInfo['shipping_state_code'] ?? '')),
        'confirm_scountry' => trim((string)($orderInfo['shipping_country'] ?? '')),
        'confirm_szip' => trim((string)($orderInfo['shipping_zipcode'] ?? '')),
        'confirm_sphone' => trim((string)($orderInfo['shipping_mobile'] ?? '')),
        'confirm_sgstin' => trim((string)($orderInfo['shipping_gstin'] ?? '')),
    ];
}

/**
 * @param array<string, mixed> $payload
 * @param array<string, mixed> $cartData
 *
 * @return array<string, string>
 */
function pos_local_checkout_build_order_create_post(array $payload, array $cartData, int $warehouseId): array
{
    $posMode = strtolower(trim((string)($payload['payment_mode'] ?? 'cash')));
    $codAmount = round((float)($payload['cod_amount'] ?? 0), 2);
    if ($codAmount <= 0.001) {
        foreach ($payload['payment_splits'] ?? [] as $splitRow) {
            if (!is_array($splitRow)) {
                continue;
            }
            if (strtolower(trim((string)($splitRow['mode'] ?? ''))) === 'cod') {
                $codAmount += round((float)($splitRow['amount'] ?? 0), 2);
            }
        }
        $codAmount = round($codAmount, 2);
    }
    if ($codAmount > 0.001) {
        $posMode = 'cod';
    }

    $storePaymentMode = pos_local_checkout_map_pos_payment_mode_to_exotic($posMode);
    $paymentType = $codAmount > 0.001 ? 'cod' : $storePaymentMode;
    $checkoutdata = pos_local_checkout_extract_checkoutdata_from_cart($cartData);

    $country = strtoupper(substr(trim((string)($payload['confirm_country'] ?? 'IN')), 0, 2));
    if ($country === '') {
        $country = 'IN';
    }

    $email = trim((string)($payload['confirm_email'] ?? ''));
    if ($email === '') {
        $email = 'pos-dummy-' . bin2hex(random_bytes(4)) . '@exoticindia.com';
    }

    $storeId = $warehouseId > 0 ? (string)$warehouseId : '1';
    $txn = trim((string)($payload['transaction_id'] ?? ''));
    $txnField = $txn !== '' ? $txn : ('store.' . gmdate('YmdHis'));

    $phone = trim((string)($payload['confirm_phone'] ?? ''));
    if ($phone === '') {
        $phone = trim((string)($payload['confirm_sphone'] ?? ''));
    }

    $out = [
        'payment_type' => $paymentType,
        'buynow' => '0',
        'checkoutdata' => $checkoutdata,
        'cod' => $codAmount > 0.001 ? '1' : '0',
        'codcharges' => '0.00',
        'first_name' => trim((string)($payload['confirm_first_name'] ?? '')),
        'last_name' => trim((string)($payload['confirm_last_name'] ?? '')),
        'email' => $email,
        'address1' => trim((string)($payload['confirm_address1'] ?? '')) !== ''
            ? trim((string)$payload['confirm_address1'])
            : 'dummy Address',
        'address2' => trim((string)($payload['confirm_address2'] ?? '')),
        'city' => trim((string)($payload['confirm_city'] ?? '')) !== ''
            ? trim((string)$payload['confirm_city'])
            : 'Delhi',
        'state' => trim((string)($payload['confirm_state'] ?? '')) !== ''
            ? trim((string)$payload['confirm_state'])
            : 'Delhi',
        'zip' => trim((string)($payload['confirm_zip'] ?? '')),
        'country' => $country,
        'phone' => $phone,
        'gstin' => trim((string)($payload['confirm_gstin'] ?? '')),
        'store_payment_details' => $storeId . '|' . $storePaymentMode . '|' . $txnField,
    ];

    pos_local_checkout_append_order_create_shipping_fields($out, $payload);

    foreach ([
        'cardnumber' => '',
        'cardexpmonth' => '',
        'cardexpyear' => '',
        'card_cvv' => '',
        'razorpay_order_id' => '',
        'razorpay_payment_id' => '',
        'razorpay_signature' => '',
        'magiccheckout_done' => '',
        'paypal_transaction_status' => '',
        'paypal_transaction_id' => '',
    ] as $key => $empty) {
        if (!array_key_exists($key, $out)) {
            $out[$key] = $empty;
        }
    }

    if ($storePaymentMode === 'razorpay') {
        $rzPay = trim((string)($payload['razorpay_payment_id'] ?? $txn));
        if ($rzPay !== '') {
            $out['razorpay_payment_id'] = $rzPay;
        }
    }

    return $out;
}

/**
 * @param array<string, string> $out
 * @param array<string, mixed>  $payload
 */
function pos_local_checkout_append_order_create_shipping_fields(array &$out, array $payload): void
{
    $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
    $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
    $sname = trim((string)($payload['confirm_sname'] ?? ''));
    if ($sname === '') {
        $sname = trim($sf . ' ' . $sl);
    }
    if ($sname === '') {
        $sname = trim((string)($out['first_name'] ?? '') . ' ' . (string)($out['last_name'] ?? ''));
    }

    $saddress1 = trim((string)($payload['confirm_saddress1'] ?? ''));
    $saddress2 = trim((string)($payload['confirm_saddress2'] ?? ''));
    $scity = trim((string)($payload['confirm_scity'] ?? ''));
    $sstate = trim((string)($payload['confirm_sstate'] ?? ''));
    $szip = trim((string)($payload['confirm_szip'] ?? ''));
    $sphone = trim((string)($payload['confirm_sphone'] ?? ''));
    $sgstin = strtoupper(trim((string)($payload['confirm_sgstin'] ?? '')));

    if ($saddress1 === '') {
        $saddress1 = (string)($out['address1'] ?? '');
    }
    if ($saddress2 === '') {
        $saddress2 = (string)($out['address2'] ?? '');
    }
    if ($scity === '') {
        $scity = (string)($out['city'] ?? '');
    }
    if ($sstate === '') {
        $sstate = (string)($out['state'] ?? '');
    }
    if ($szip === '') {
        $szip = (string)($out['zip'] ?? '');
    }
    if ($sphone === '') {
        $sphone = (string)($out['phone'] ?? '');
    }
    if ($sgstin === '') {
        $sgstin = strtoupper(trim((string)($out['gstin'] ?? '')));
    }

    $scountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? 'IN')), 0, 2));
    if ($scountry === '') {
        $scountry = (string)($out['country'] ?? 'IN');
    }
    if ($scountry === '') {
        $scountry = 'IN';
    }

    $out['sname'] = $sname;
    $out['saddress1'] = $saddress1;
    $out['saddress2'] = $saddress2;
    $out['scity'] = $scity;
    $out['sstate'] = $sstate;
    $out['szip'] = $szip;
    $out['scountry'] = $scountry;
    $out['sphone'] = $sphone;
}

function pos_local_checkout_clear_exotic_cart(RetailApiClient $client): void
{
    require_once dirname(__DIR__) . '/integrations/exotic/Support/CartResponseParser.php';

    $retrieve = $client->call('/cart/retrieve', 'GET', []);
    if (!CartResponseParser::isSuccess($retrieve)) {
        return;
    }

    $cartData = is_array($retrieve['data'] ?? null) ? $retrieve['data'] : [];
    $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
    if (!is_array($items)) {
        return;
    }

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $cartid = trim((string)($item['cartid'] ?? $item['id'] ?? $item['cart_id'] ?? ''));
        if ($cartid === '') {
            continue;
        }
        $client->call('/cart/delete', 'GET', ['cartid' => $cartid]);
    }
}

/**
 * @param list<array<string, mixed>> $orderLines
 *
 * @return array{success:bool,message?:string,cart_data?:array<string,mixed>,list_line_prices?:list<array<string,string>>}
 */
function pos_local_checkout_rebuild_exotic_cart_from_order_lines(
    RetailApiClient $client,
    array $orderLines,
    float $customReduce
): array {
    require_once dirname(__DIR__) . '/integrations/exotic/Support/CartResponseParser.php';

    $client->call('/cart/addcustomdiscount', 'GET', ['custom_reduce' => '0']);
    pos_local_checkout_clear_exotic_cart($client);

    $listLinePrices = [];
    $added = 0;
    foreach ($orderLines as $row) {
        if (!is_array($row)) {
            continue;
        }
        $itemCode = trim((string)($row['item_code'] ?? ''));
        if ($itemCode === '') {
            continue;
        }

        $size = trim((string)($row['size'] ?? ''));
        $color = trim((string)($row['color'] ?? ''));
        $variation = pos_local_checkout_build_variation_from_size_color($size, $color);
        $qty = max(1, (int)($row['quantity'] ?? 1));

        $post = [
            'buynow' => '0',
            'code' => $itemCode,
            'qty' => (string)$qty,
        ];
        if ($variation !== '') {
            $post['variation'] = $variation;
        }
        $options = $row['options'] ?? '';
        if (is_array($options)) {
            $options = json_encode($options, JSON_UNESCAPED_UNICODE);
        }
        $options = trim((string)$options);
        if ($options !== '' && $options !== '0') {
            $post['options'] = $options;
        }

        $addRes = $client->call('/cart/add', 'POST', [], $post);
        if (!CartResponseParser::isSuccess($addRes)) {
            $em = CartResponseParser::extractUserMessage($addRes);

            return [
                'success' => false,
                'message' => 'Could not add ' . $itemCode . ' to Exotic cart'
                    . ($em !== '' ? ': ' . $em : ''),
            ];
        }

        $unit = (float)($row['itemprice'] ?? 0);
        if ($unit <= 0) {
            $unit = (float)($row['finalprice'] ?? 0);
        }
        if ($unit > 0) {
            $listLinePrices[] = [
                'itemcode' => $itemCode,
                'size' => $size,
                'color' => $color,
                'price' => number_format($unit, 2, '.', ''),
            ];
        }
        ++$added;
    }

    if ($added === 0) {
        return ['success' => false, 'message' => 'No order lines could be added to the Exotic cart.'];
    }

    if ($customReduce > 0.001) {
        $client->call('/cart/addcustomdiscount', 'GET', [
            'custom_reduce' => number_format($customReduce, 2, '.', ''),
        ]);
    }

    $retrieve = $client->call('/cart/retrieve', 'GET', []);
    if (!CartResponseParser::isSuccess($retrieve)) {
        return [
            'success' => false,
            'message' => 'Items were added but cart retrieve failed. Try publish again.',
        ];
    }

    $cartData = is_array($retrieve['data'] ?? null) ? $retrieve['data'] : [];
    if (pos_local_checkout_extract_checkoutdata_from_cart($cartData) === '') {
        return [
            'success' => false,
            'message' => 'Cart was rebuilt but checkoutdata is missing. The Exotic cart session may have expired.',
        ];
    }

    return [
        'success' => true,
        'cart_data' => $cartData,
        'list_line_prices' => $listLinePrices,
    ];
}

/**
 * @param array<string, string> $postBody
 * @param list<array<string, mixed>> $listLinePrices
 *
 * @return array{success:bool,message?:string,api_order_number?:string}
 */
function pos_local_checkout_call_order_create_and_edit_prices(
    RetailApiClient $client,
    array $postBody,
    array $query,
    array $headers,
    array $listLinePrices
): array {
    require_once dirname(__DIR__) . '/integrations/exotic/Support/CartResponseParser.php';
    require_once dirname(__DIR__) . '/integrations/exotic/Support/OrderResponseParser.php';

    $createRes = $client->call('/order/create', 'POST', $query, $postBody, null, $headers);
    if (!CartResponseParser::isSuccess($createRes)) {
        $d = is_array($createRes['data'] ?? null) ? $createRes['data'] : [];
        $msg = trim((string)($d['message'] ?? $d['error'] ?? $d['errormessage'] ?? ''));
        if ($msg === '') {
            $msg = 'Exotic order/create failed (HTTP ' . (int)($createRes['code'] ?? 0) . ').';
        }

        return ['success' => false, 'message' => $msg];
    }

    $apiOrderNumber = OrderResponseParser::extractOrderNumber(is_array($createRes['data'] ?? null) ? $createRes['data'] : []);
    if ($apiOrderNumber === '') {
        return ['success' => false, 'message' => 'Exotic accepted the order but returned no order number.'];
    }

    if ($listLinePrices !== []) {
        $editRes = pos_local_checkout_exotic_edit_order_prices($client, $apiOrderNumber, $listLinePrices);
        if (!CartResponseParser::isSuccess($editRes)) {
            $em = CartResponseParser::extractUserMessage($editRes);
            if ($em === '') {
                $em = 'HTTP ' . (int)($editRes['code'] ?? 0);
            }

            return [
                'success' => false,
                'message' => 'Exotic order ' . $apiOrderNumber . ' was created but line prices were rejected: ' . $em,
                'api_order_number' => $apiOrderNumber,
            ];
        }
    }

    return ['success' => true, 'api_order_number' => $apiOrderNumber];
}

/**
 * @return array{success:bool,message?:string,old_order_number?:string,new_order_number?:string}
 */
function pos_local_checkout_finalize_publish_rename(mysqli $conn, string $tempOrderNumber, string $apiOrderNumber): array
{
    if (strcasecmp($tempOrderNumber, $apiOrderNumber) === 0) {
        pos_local_checkout_delete_pending_sync_payload($tempOrderNumber);
        require_once dirname(__DIR__) . '/models/order/order.php';
        $ordersModel = new Order($conn);
        $ordersModel->updateOrderRemarks(
            $apiOrderNumber,
            'Published to Exotic on ' . date('Y-m-d H:i:s') . '.'
        );

        return [
            'success' => true,
            'message' => 'Order published to Exotic as ' . $apiOrderNumber . '.',
            'old_order_number' => $tempOrderNumber,
            'new_order_number' => $apiOrderNumber,
        ];
    }

    require_once dirname(__DIR__) . '/models/posorder/order.php';
    $posOrderModel = new POSOrder($conn);
    $rename = $posOrderModel->renameOrderNumber($tempOrderNumber, $apiOrderNumber);
    if (empty($rename['success'])) {
        return [
            'success' => false,
            'message' => 'Exotic order ' . $apiOrderNumber . ' was created but local rename failed: '
                . (string)($rename['message'] ?? 'unknown'),
            'new_order_number' => $apiOrderNumber,
        ];
    }

    pos_local_checkout_delete_pending_sync_payload($tempOrderNumber);

    require_once dirname(__DIR__) . '/models/order/order.php';
    $ordersModel = new Order($conn);
    $ordersModel->updateOrderRemarks(
        $apiOrderNumber,
        'Published to Exotic on ' . date('Y-m-d H:i:s') . '. Replaced temp ' . $tempOrderNumber . '.'
    );

    return [
        'success' => true,
        'message' => 'Order published to Exotic. Temp ' . $tempOrderNumber . ' → ' . $apiOrderNumber . '.',
        'old_order_number' => $tempOrderNumber,
        'new_order_number' => $apiOrderNumber,
    ];
}

/**
 * @param list<array<string, mixed>> $orderLines
 *
 * @return array{success:bool,message?:string,post_body?:array<string,string>,list_line_prices?:list<array<string,string>>}
 */
function pos_local_checkout_prepare_publish_from_database(
    mysqli $conn,
    RetailApiClient $client,
    string $tempOrderNumber,
    array $orderLines,
    array $orderInfo,
    int $warehouseId
): array {
    $payments = pos_local_checkout_load_payments_for_order($conn, $tempOrderNumber);
    $confirmPayload = pos_local_checkout_build_confirm_payload_from_order_info($orderInfo, $payments);

    $customReduce = 0.0;
    foreach ($orderLines as $line) {
        if (!is_array($line)) {
            continue;
        }
        $customReduce = (float)($line['custom_reduce'] ?? 0);
        if ($customReduce > 0.001) {
            break;
        }
    }

    $rebuild = pos_local_checkout_rebuild_exotic_cart_from_order_lines($client, $orderLines, $customReduce);
    if (empty($rebuild['success'])) {
        return $rebuild;
    }

    $postBody = pos_local_checkout_build_order_create_post(
        $confirmPayload,
        $rebuild['cart_data'] ?? [],
        $warehouseId
    );
    if (trim((string)($postBody['checkoutdata'] ?? '')) === '') {
        return ['success' => false, 'message' => 'Could not obtain checkoutdata after rebuilding the cart.'];
    }

    return [
        'success' => true,
        'post_body' => $postBody,
        'list_line_prices' => is_array($rebuild['list_line_prices'] ?? null) ? $rebuild['list_line_prices'] : [],
    ];
}

/**
 * @param list<array<string, mixed>> $lines
 *
 * @return array{data: array, code: int, raw: string}
 */
function pos_local_checkout_exotic_edit_order_prices(RetailApiClient $client, string $orderId, array $lines): array
{
    $post = ['orderid' => $orderId];
    $i = 0;
    foreach ($lines as $ln) {
        if (!is_array($ln)) {
            continue;
        }
        $post['itemcode[' . $i . ']'] = trim((string)($ln['itemcode'] ?? ''));
        $post['size[' . $i . ']'] = trim((string)($ln['size'] ?? ''));
        $post['color[' . $i . ']'] = trim((string)($ln['color'] ?? ''));
        $post['price[' . $i . ']'] = trim((string)($ln['price'] ?? ''));
        ++$i;
    }

    return $client->call('/order/pos_editorderprices', 'POST', [], $post);
}

/**
 * Publish a temp local POS order to Exotic. Tries stored checkout payload first; on failure
 * rebuilds the Exotic cart from vp_orders lines (expired cart) and retries order/create.
 *
 * @return array{success:bool,message?:string,old_order_number?:string,new_order_number?:string,cart_rebuilt?:bool,used_stored_payload?:bool}
 */
function pos_local_checkout_publish_to_exotic(mysqli $conn, string $tempOrderNumber): array
{
    $tempOrderNumber = trim($tempOrderNumber);
    if ($tempOrderNumber === '' || !pos_local_checkout_is_temp_order_number($tempOrderNumber)) {
        return ['success' => false, 'message' => 'Not a local temp order pending Exotic sync.'];
    }

    require_once dirname(__DIR__) . '/models/order/order.php';
    require_once dirname(__DIR__) . '/integrations/exotic/Clients/RetailApiClient.php';
    require_once dirname(__DIR__) . '/integrations/exotic/Support/CartResponseParser.php';

    $ordersModel = new Order($conn);
    $orderLines = $ordersModel->getOrderLineItemsByRef($tempOrderNumber);
    $orderInfo = $ordersModel->getAddressInfoByOrderNumber($tempOrderNumber);
    if (!is_array($orderLines) || count($orderLines) === 0) {
        return ['success' => false, 'message' => 'Order lines not found in the local database.'];
    }
    if (!is_array($orderInfo) || $orderInfo === []) {
        return ['success' => false, 'message' => 'Order address info not found in the local database.'];
    }

    $sync = pos_local_checkout_load_pending_sync_payload($tempOrderNumber);
    $warehouseId = pos_local_checkout_resolve_warehouse_for_order($conn, $tempOrderNumber, $sync);
    if ($warehouseId > 0) {
        $_SESSION['warehouse_id'] = $warehouseId;
    }

    $client = RetailApiClient::create($conn);
    $listLinePrices = [];
    $cartRebuilt = false;
    $usedStoredPayload = false;
    $lastError = '';

    if ($sync !== null && is_array($sync['post_body'] ?? null) && $sync['post_body'] !== []) {
        $usedStoredPayload = true;
        $listLinePrices = is_array($sync['list_line_prices'] ?? null) ? $sync['list_line_prices'] : [];
        $attempt = pos_local_checkout_call_order_create_and_edit_prices(
            $client,
            $sync['post_body'],
            is_array($sync['cart_query'] ?? null) ? $sync['cart_query'] : [],
            is_array($sync['cart_extra_headers'] ?? null) ? $sync['cart_extra_headers'] : [],
            $listLinePrices
        );
        if (!empty($attempt['success']) && !empty($attempt['api_order_number'])) {
            $final = pos_local_checkout_finalize_publish_rename($conn, $tempOrderNumber, (string)$attempt['api_order_number']);
            $final['used_stored_payload'] = true;
            $final['cart_rebuilt'] = false;

            return $final;
        }
        $lastError = trim((string)($attempt['message'] ?? ''));
        if (!empty($attempt['api_order_number'])) {
            return [
                'success' => false,
                'message' => $lastError,
                'new_order_number' => (string)$attempt['api_order_number'],
            ];
        }
    }

    $prepared = pos_local_checkout_prepare_publish_from_database(
        $conn,
        $client,
        $tempOrderNumber,
        $orderLines,
        $orderInfo,
        $warehouseId
    );
    if (empty($prepared['success'])) {
        $msg = trim((string)($prepared['message'] ?? ''));
        if ($lastError !== '') {
            $msg = 'Stored checkout failed (' . $lastError . '). Cart rebuild also failed: ' . $msg;
        }

        return ['success' => false, 'message' => $msg !== '' ? $msg : 'Could not publish order to Exotic.'];
    }

    $cartRebuilt = true;
    $listLinePrices = is_array($prepared['list_line_prices'] ?? null) ? $prepared['list_line_prices'] : [];
    $attempt = pos_local_checkout_call_order_create_and_edit_prices(
        $client,
        $prepared['post_body'],
        [],
        [],
        $listLinePrices
    );
    if (empty($attempt['success']) || empty($attempt['api_order_number'])) {
        $msg = trim((string)($attempt['message'] ?? ''));
        if ($usedStoredPayload && $lastError !== '') {
            $msg = 'Stored checkout failed (' . $lastError . '). After rebuilding cart: ' . $msg;
        }

        return [
            'success' => false,
            'message' => $msg !== '' ? $msg : 'Exotic order/create failed after cart rebuild.',
            'cart_rebuilt' => true,
            'used_stored_payload' => $usedStoredPayload,
            'new_order_number' => (string)($attempt['api_order_number'] ?? ''),
        ];
    }

    $final = pos_local_checkout_finalize_publish_rename($conn, $tempOrderNumber, (string)$attempt['api_order_number']);
    $final['cart_rebuilt'] = $cartRebuilt;
    $final['used_stored_payload'] = $usedStoredPayload;
    if ($cartRebuilt && !empty($final['success'])) {
        $final['message'] = (string)($final['message'] ?? '') . ' Cart was rebuilt from order lines before publishing.';
    }

    return $final;
}
