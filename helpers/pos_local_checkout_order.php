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
    if (empty($addrRes['success'])) {
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
 * Publish a temp local POS order to Exotic using the stored checkout payload.
 *
 * @return array{success:bool,message?:string,old_order_number?:string,new_order_number?:string}
 */
function pos_local_checkout_publish_to_exotic(mysqli $conn, string $tempOrderNumber): array
{
    $tempOrderNumber = trim($tempOrderNumber);
    if ($tempOrderNumber === '' || !pos_local_checkout_is_temp_order_number($tempOrderNumber)) {
        return ['success' => false, 'message' => 'Not a local temp order pending Exotic sync.'];
    }

    $sync = pos_local_checkout_load_pending_sync_payload($tempOrderNumber);
    if ($sync === null) {
        return [
            'success' => false,
            'message' => 'No saved Exotic checkout payload for this order. Cannot publish automatically.',
        ];
    }

    require_once dirname(__DIR__) . '/integrations/exotic/Clients/RetailApiClient.php';
    require_once dirname(__DIR__) . '/integrations/exotic/Support/CartResponseParser.php';
    require_once dirname(__DIR__) . '/integrations/exotic/Support/OrderResponseParser.php';

    $postBody = is_array($sync['post_body'] ?? null) ? $sync['post_body'] : [];
    if ($postBody === []) {
        return ['success' => false, 'message' => 'Saved checkout payload is empty.'];
    }

    $query = is_array($sync['cart_query'] ?? null) ? $sync['cart_query'] : [];
    $headers = is_array($sync['cart_extra_headers'] ?? null) ? $sync['cart_extra_headers'] : [];
    $client = RetailApiClient::create($conn);
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

    $listLines = is_array($sync['list_line_prices'] ?? null) ? $sync['list_line_prices'] : [];
    if ($listLines !== []) {
        $editRes = pos_local_checkout_exotic_edit_order_prices($client, $apiOrderNumber, $listLines);
        if (!CartResponseParser::isSuccess($editRes)) {
            $em = CartResponseParser::extractUserMessage($editRes);
            if ($em === '') {
                $em = 'HTTP ' . (int)($editRes['code'] ?? 0);
            }

            return [
                'success' => false,
                'message' => 'Exotic order ' . $apiOrderNumber . ' was created but line prices were rejected: ' . $em,
                'new_order_number' => $apiOrderNumber,
            ];
        }
    }

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
