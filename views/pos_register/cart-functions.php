<?php
// cart-functions.php

/** Align with POSRegisterController::unwrapProductApiResponse */
function cart_unwrap_product_api_payload(array $data): array
{
    if (!empty($data['data']) && is_array($data['data'])) {
        $inner = $data['data'];
        unset($data['data']);

        return array_merge($data, $inner);
    }

    return $data;
}

/** Raw checkoutdata from GET /cart/retrieve body (after unwrap); aligns with POSRegisterController::checkoutdataFromCartRetrieveBody */
function cart_checkoutdata_from_retrieve_body(array $data)
{
    foreach (['checkoutdata', 'checkoutData', 'CheckOutData'] as $k) {
        if (!array_key_exists($k, $data)) {
            continue;
        }
        $v = $data[$k];
        if ($v === null) {
            continue;
        }
        if (is_string($v) && trim($v) === '') {
            continue;
        }
        if (is_array($v) && $v === []) {
            continue;
        }

        return $v;
    }

    return $data['checkoutdata'] ?? '';
}

function cart_has_usable_checkoutdata(array $cartData): bool
{
    $raw = $cartData['checkoutdata'] ?? null;
    if ($raw === null) {
        return false;
    }
    if (is_string($raw)) {
        return trim($raw) !== '';
    }
    if (is_array($raw)) {
        return $raw !== [];
    }

    return $raw !== '';
}

/** Catalog rows for matching cart_entry → price (incl. express shipping row when present). */
function cart_product_addon_catalog(array $productApiResult): array
{
    $data = cart_unwrap_product_api_payload($productApiResult['data'] ?? []);
    $opts = [];
    if (!empty($data['addon_options']['default_options']) && is_array($data['addon_options']['default_options'])) {
        $opts = $data['addon_options']['default_options'];
    }
    if (!empty($data['express_shipping_option']['price'])) {
        $eso = $data['express_shipping_option'];
        $opts[] = [
            'title' => $eso['title'] ?? '',
            'price' => (float)($eso['price'] ?? 0),
            'cart_entry' => trim((string)($eso['cart_entry'] ?? '')),
        ];
    }

    return $opts;
}

function cart_sum_addon_prices_from_catalog(array $selectedEntries, array $catalogAddons): float
{
    $sum = 0.0;
    foreach ($selectedEntries as $se) {
        $se = trim((string)$se);
        if ($se === '') {
            continue;
        }
        foreach ($catalogAddons as $opt) {
            $ce = trim((string)($opt['cart_entry'] ?? ''));
            if ($ce !== '' && strcasecmp($ce, $se) === 0) {
                $sum += (float)($opt['price'] ?? 0);
                break;
            }
        }
    }

    return $sum;
}

function cart_merge_express_into_addon_unit_sum(
    float $addonsSumPerUnit,
    array $addons,
    array $selectedEntries,
    array $allAddons,
    bool $expressSelected,
    float $shippingPerUnit
): float {
    if (!$expressSelected || $shippingPerUnit <= 0) {
        return $addonsSumPerUnit;
    }

    $expressCounted = 0.0;
    foreach ($addons as $a) {
        if (stripos((string)($a['name'] ?? ''), 'Express') !== false) {
            $expressCounted += (float)($a['value'] ?? 0);
        }
    }
    foreach ($allAddons as $opt) {
        if (stripos((string)($opt['title'] ?? ''), 'express') === false) {
            continue;
        }
        $ce = trim((string)($opt['cart_entry'] ?? ''));
        if ($ce === '') {
            continue;
        }
        foreach ($selectedEntries as $se) {
            if (strcasecmp($ce, trim((string)$se)) === 0) {
                $expressCounted += (float)($opt['price'] ?? 0);
                break;
            }
        }
    }

    if ($expressCounted < $shippingPerUnit - 0.0001) {
        return $addonsSumPerUnit + ($shippingPerUnit - $expressCounted);
    }

    return $addonsSumPerUnit;
}

/** India retail unit price from vp_products (matches POSRegisterController). */
function cart_resolve_india_price_from_vp($mysqli, string $code): float
{
    if ($code === '' || !$mysqli) {
        return 0.0;
    }
    $stmt = $mysqli->prepare(
        'SELECT price_india, price_india_suggested, finalprice, itemprice, gst
         FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC LIMIT 1'
    );
    if (!$stmt) {
        return 0.0;
    }
    $stmt->bind_param('ss', $code, $code);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row) {
        return 0.0;
    }
    foreach (['price_india', 'price_india_suggested', 'finalprice', 'itemprice'] as $k) {
        $f = (float)($row[$k] ?? 0);
        if ($f > 0) {
            $pct = (float)($row['gst'] ?? 0);
            if ($pct > 0) {
                return round($f * (1 + $pct / 100), 2);
            }

            return $f;
        }
    }

    return 0.0;
}

function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null, ?string $apiBaseUrl = null)
{
    // echo "<pre>";
    // print_r($_SESSION['discount_coupon']['discountcoupondetails']);
    // exit;

    $base = $apiBaseUrl ?? 'https://www.exoticindia.com/api';
    $url = rtrim($base, '/') . $endpoint;
    if ($params) {
        $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($params);
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);

    // $headers = [
    //     'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
    //     'x-api-deviceid: POS-Store_1',
    //     'x-api-appplayerid: POS-Web-Terminal',
    //     'x-api-countrycode: IN',
    //     'x-api-euid:' . ($_SESSION['user']['id'] ?? ''),
    //     'User-Agent: ExoticPOS-Web/1.0'
    // ];
    $headers = [
        // 'Content-Type: application/x-www-form-urlencoded',
        'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
        'x-api-deviceid: POS-Store_1',
        'x-api-appplayerid: POS-Web-Terminal',
        'x-api-countrycode: IN',
        'x-api-euid:' . ($_SESSION['user']['id'] ?? ''),
        'User-Agent: ExoticPOS'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    $body = (string)$response;
    $decoded = json_decode($body, true);
    $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];

    return ['data' => $data, 'code' => $httpCode, 'raw' => $body];
}


function get_cart()
{
    global $conn;
    $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
    $voucher = $_SESSION['gift_voucher']['giftvoucherdetails'] ?? '';

    $res = exotic_api_call(
        '/cart/retrieve',
        'GET',
        [
            'discountcoupondetails' => $coupon,
            'giftvoucherdetails' => $voucher,
        ]
    );

    $data = cart_unwrap_product_api_payload($res['data'] ?? []);

    $items = [];
    $subtotal = 0;
    $shipping_total = 0;

    if (!empty($data['cartitems'])) {

        foreach ($data['cartitems'] as $item) {

            // $shipping = (float)($item['express_shipping_cost'] ?? 0);
            $shipping_per_unit = (float)($item['express_shipping_cost'] ?? 0);
            $shipping = $shipping_per_unit * (int)$item['quantity'];
            // $expressSelected = $item['express_shipping_chosen'] ?? false;
            $expressSelected = $item['express_shipping_chosen'] ?? false;

            $unitBase = (float)$item['price'];
            if (!empty($conn)) {
                $vpIndia = cart_resolve_india_price_from_vp($conn, trim((string)$item['code']));
                if ($vpIndia > 0) {
                    $unitBase = $vpIndia;
                }
            }

            $addons = [];
            $addonsSumPerUnit = 0.0;
            $selectedEntries = [];
            $catalog = [];

            if (!empty($item['addons_selected']) && is_array($item['addons_selected'])) {
                foreach ($item['addons_selected'] as $ad) {
                    if (!is_array($ad)) {
                        continue;
                    }
                    $amt = 0.0;
                    foreach (['value', 'price', 'amount'] as $k) {
                        if (isset($ad[$k]) && $ad[$k] !== '' && is_numeric($ad[$k])) {
                            $amt = (float)$ad[$k];
                            break;
                        }
                    }
                    $addonsSumPerUnit += $amt;

                    $cartEntry = trim((string)($ad['cart_entry'] ?? ''));
                    if ($cartEntry === '') {
                        if (stripos((string)($ad['name'] ?? ''), 'Express') !== false) {
                            $cartEntry = 'OPTIONALS_EXPRESS:_blank_:' . $amt;
                        } else {
                            $cartEntry = 'OPTIONALS_SCULPTURES_LACQUER:_blank_:' . $amt;
                        }
                    }
                    $addons[] = [
                        'name' => $ad['name'] ?? '',
                        'value' => $amt,
                        'cart_entry' => $cartEntry,
                    ];
                    $selectedEntries[] = $cartEntry;
                }
            }

            $optStr = trim((string)($item['options'] ?? ''));
            if ($optStr !== '') {
                foreach (explode('|', $optStr) as $chunk) {
                    $chunk = trim($chunk);
                    if ($chunk !== '' && !in_array($chunk, $selectedEntries, true)) {
                        $selectedEntries[] = $chunk;
                    }
                }
            }

            if ($addonsSumPerUnit <= 0 && $selectedEntries !== []) {
                $productRes = exotic_api_call('/product/code', 'GET', ['code' => $item['code']]);
                $catalog = cart_product_addon_catalog($productRes);
                if ($catalog !== []) {
                    $addonsSumPerUnit = cart_sum_addon_prices_from_catalog($selectedEntries, $catalog);
                }
            }

            if ($catalog === [] && $expressSelected && $shipping_per_unit > 0) {
                $productRes = exotic_api_call('/product/code', 'GET', ['code' => $item['code']]);
                $catalog = cart_product_addon_catalog($productRes);
            }

            $addonsSumPerUnit = cart_merge_express_into_addon_unit_sum(
                $addonsSumPerUnit,
                $addons,
                $selectedEntries,
                $catalog,
                (bool)$expressSelected,
                $shipping_per_unit
            );

            $items[] = [
                'cartref' => $item['cartref'],
                'name' => $item['name'],
                'imageurl' => $item['imageurl'],
                'price' => $unitBase,
                'quantity' => (int)$item['quantity'],
                'shipping' => $shipping,
                'shipping_per_unit' => $shipping_per_unit,
                'shipping_title' => $item['express_shipping_option']['title'] ?? '',
                'shipping_longtitle' => $item['express_shipping_option']['longtitle'] ?? '',
                'express_selected' => $expressSelected
            ];

            $unitLine = $unitBase + $addonsSumPerUnit;
            $subtotal += $unitLine * (int)$item['quantity'];
            // echo $expressSelected;
            // exit;
            // Add shipping only if selected
            if ($expressSelected) {
                $shipping_total += $shipping;
            }
        }
    }

    $discount = (float)($data['couponreduction'] ?? 0);
    $gst = (float)($data['gstamount'] ?? 0);

    $grand_total = $subtotal + $shipping_total + $gst - $discount;

    return [
        'items' => $items,
        'subtotal' => $subtotal,
        'shipping_total' => $shipping_total,
        'gst' => $gst,
        'discount' => $discount,
        'grand_total' => $grand_total,
        'checkoutdata' => cart_checkoutdata_from_retrieve_body($data),
        'codcharges' => (float)($data['codcharges_if_chosen'] ?? 0),
    ];
}

function add_to_cart($code, $qty, $variation = '', $options = '', $buyNow = false)
{

    if (empty(trim($code))) {
        return ['success' => false, 'message' => 'Product code is required'];
    }

    $coupon = '';

    if (!empty($_SESSION['discount_coupon'])) {
        if (is_array($_SESSION['discount_coupon'])) {
            $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
        } else {
            $coupon = $_SESSION['discount_coupon']['discountcoupondetails'];
        }
    }

    $postData = http_build_query([
        'buynow'   => $buyNow ? 1 : 0,
        'code'     => trim($code),
        'qty'      => max(1, (int)$qty),
        'variation' => trim($variation),
        'options'  => trim($options),
        'discountcoupondetails' => $coupon
    ]);

    // echo '<pre>'; print_r($postData);exit;
    $result = exotic_api_call('/cart/add', 'POST', [], $postData);

    $response = $result['data'] ?? [];
    $httpCode = $result['code'] ?? 0;

    if ($httpCode >= 200 && $httpCode < 300) {

        $success = isset($response['success'])
            ? $response['success']
            : (stripos(json_encode($response), 'success') !== false ||
                stripos(json_encode($response), 'added') !== false);

        return [
            'success'  => $success,
            'message'  => $response['message'] ?? 'Item added to cart',
            'response' => $response,
            'cartref'  => $response['cartref'] ?? null
        ];
    }

    return [
        'success'  => false,
        'message'  => $response['message'] ?? 'Failed to add to cart (HTTP ' . $httpCode . ')',
        'response' => $response,
        'http_code' => $httpCode
    ];
}
function change_qty($cartref, $newqty)
{
    return exotic_api_call('/cart/modifyqty', 'GET', [
        'cartid' => $cartref,
        'newqty' => $newqty
    ]);
}

function remove_item($cartref)
{
    return change_qty($cartref, 0);
}

function apply_coupon($couponId)
{
    if (empty($couponId)) {
        return [
            'success' => false,
            'message' => 'Coupon code required'
        ];
    }

    $result = exotic_api_call(
        '/cart/addcoupon',
        'GET',
        [
            'couponid' => $couponId
        ],
        null,
        'https://www.exoticindia.com'
    );

    $response = $result['data'] ?? [];
    if ($response === [] && !empty($result['raw'])) {
        $rd = json_decode((string)$result['raw'], true);
        if (json_last_error() === JSON_ERROR_NONE && is_string($rd) && $rd !== '') {
            $response = ['discountcoupondetails' => $rd];
        }
    }
    // echo '<pre>'; print_r($result); exit;
    // Example response: "APP05|P|5"
    if (!empty($response) && !isset($response['error'])) {

        // store validated coupon string in session
        $_SESSION['discount_coupon'] = $response;

        return [
            'success' => true,
            // 'message' => 'Coupon applied successfully',
            'coupon_string' => $response
        ];
    }

    return [
        'success' => false,
        'message' => $response['message'] ?? 'Invalid coupon'
    ];
}

function modify_express_shipping($cartid, $action)
{
    // echo $cartid; exit;
    return exotic_api_call(
        '/cart/modifycartexpress',
        'GET',
        [
            'cartid' => $cartid,
            'action' => $action
        ]
    );
}

/**
 * Map legacy POS payment labels to /order/create payment_type values.
 */
function cart_map_payment_type_for_order_api(string $paymentType): string
{
    $t = strtolower(trim($paymentType));
    $map = [
        'cash' => 'offline',
        'card' => 'cc',
        'offline' => 'offline',
        'cc' => 'cc',
        'razorpay' => 'razorpay',
        'cod' => 'cod',
        'bank_transfer' => 'bank_transfer',
        'pos_machine' => 'pos_machine',
        'specialpay' => 'specialpay',
        'cheque' => 'cheque',
        'demand_draft' => 'demand_draft',
    ];

    return $map[$t] ?? 'offline';
}

/**
 * Billing + shipping for order/create from vp_order_info, session POS customer form, or confirm-address POST.
 * Mirrors POSRegisterController::create_order customer resolution.
 *
 * @return array{billing: array, shipping: array}
 */
function cart_resolve_order_billing_shipping_for_api($mysqli): array
{
    $billing = [];
    $shipping = [];

    $rawCustomerId = $_POST['customer_id'] ?? null;
    if ($rawCustomerId !== null && $rawCustomerId !== '') {
        $customerId = (int)$rawCustomerId;
    } else {
        $customerId = (int)($_SESSION['pos_customer_id'] ?? 0);
    }

    if ($customerId > 0 && $mysqli) {
        $stmt = $mysqli->prepare('SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($info) {
                $billing = [
                    'first_name' => $info['first_name'],
                    'last_name' => $info['last_name'],
                    'email' => $info['email'],
                    'phone' => $info['mobile'],
                    'address1' => $info['address_line1'],
                    'address2' => $info['address_line2'],
                    'city' => $info['city'],
                    'state' => $info['state'],
                    'zip' => $info['zipcode'],
                    'country' => $info['country'] ?: 'IN',
                    'gstin' => $info['gstin'],
                ];

                $shipping = [
                    'sname' => trim($info['shipping_first_name'] . ' ' . $info['shipping_last_name']),
                    'saddress1' => $info['shipping_address_line1'],
                    'saddress2' => $info['shipping_address_line2'],
                    'scity' => $info['shipping_city'],
                    'sstate' => $info['shipping_state'],
                    'szip' => $info['shipping_zipcode'],
                    'scountry' => $info['shipping_country'] ?: 'IN',
                    'sphone' => $info['shipping_mobile'],
                ];
            }
        }
    }

    if (empty($billing) && !empty($_SESSION['pos_customer_form'])) {
        $form = $_SESSION['pos_customer_form'];

        $billing = [
            'first_name' => trim($form['first_name'] ?? ''),
            'last_name' => trim($form['last_name'] ?? ''),
            'email' => trim($form['cus_email'] ?? ''),
            'phone' => trim($form['mobile'] ?? ''),
            'address1' => trim($form['address_line1'] ?? ''),
            'address2' => trim($form['address_line2'] ?? ''),
            'city' => trim($form['city'] ?? ''),
            'state' => trim($form['state'] ?? ''),
            'zip' => trim($form['zipcode'] ?? ''),
            'country' => 'IN',
            'gstin' => trim($form['gstin'] ?? ''),
        ];

        $shipping = [
            'sname' => trim(($form['shipping_first_name'] ?? '') . ' ' . ($form['shipping_last_name'] ?? '')),
            'saddress1' => trim($form['shipping_address_line1'] ?? ''),
            'saddress2' => trim($form['shipping_address_line2'] ?? ''),
            'scity' => trim($form['shipping_city'] ?? ''),
            'sstate' => trim($form['shipping_state'] ?? ''),
            'szip' => trim($form['shipping_zipcode'] ?? ''),
            'scountry' => 'IN',
            'sphone' => trim($form['shipping_mobile'] ?? ''),
        ];
    }

    $confirmFlag = trim((string)($_POST['confirm_address_submit'] ?? ''));
    $applyConfirmPopup = ($confirmFlag === '1')
        || (
            trim((string)($_POST['confirm_first_name'] ?? '')) !== ''
            && trim((string)($_POST['confirm_phone'] ?? '')) !== ''
        );
    if ($applyConfirmPopup) {
        $confirmShippingFirst = trim((string)($_POST['confirm_sfirst_name'] ?? ''));
        $confirmShippingLast = trim((string)($_POST['confirm_slast_name'] ?? ''));
        $confirmShippingFull = trim((string)($_POST['confirm_sname'] ?? ''));
        $resolvedShippingName = trim($confirmShippingFirst . ' ' . $confirmShippingLast);
        if ($resolvedShippingName === '') {
            $resolvedShippingName = $confirmShippingFull;
        }
        if ($resolvedShippingName === '') {
            $resolvedShippingName = trim((string)($shipping['sname'] ?? ''));
        }
        $billing = [
            'first_name' => trim((string)($_POST['confirm_first_name'] ?? ($billing['first_name'] ?? ''))),
            'last_name' => trim((string)($_POST['confirm_last_name'] ?? ($billing['last_name'] ?? ''))),
            'email' => trim((string)($_POST['confirm_email'] ?? ($billing['email'] ?? ''))),
            'phone' => trim((string)($_POST['confirm_phone'] ?? ($billing['phone'] ?? ''))),
            'address1' => trim((string)($_POST['confirm_address1'] ?? ($billing['address1'] ?? ''))),
            'address2' => trim((string)($_POST['confirm_address2'] ?? ($billing['address2'] ?? ''))),
            'city' => trim((string)($_POST['confirm_city'] ?? ($billing['city'] ?? ''))),
            'state' => trim((string)($_POST['confirm_state'] ?? ($billing['state'] ?? ''))),
            'zip' => trim((string)($_POST['confirm_zip'] ?? ($billing['zip'] ?? ''))),
            'country' => trim((string)($_POST['confirm_country'] ?? ($billing['country'] ?? 'IN'))),
            'gstin' => trim((string)($_POST['confirm_gstin'] ?? ($billing['gstin'] ?? ''))),
        ];
        $shipping = [
            'sname' => $resolvedShippingName,
            'saddress1' => trim((string)($_POST['confirm_saddress1'] ?? ($shipping['saddress1'] ?? ''))),
            'saddress2' => trim((string)($_POST['confirm_saddress2'] ?? ($shipping['saddress2'] ?? ''))),
            'scity' => trim((string)($_POST['confirm_scity'] ?? ($shipping['scity'] ?? ''))),
            'sstate' => trim((string)($_POST['confirm_sstate'] ?? ($shipping['sstate'] ?? ''))),
            'szip' => trim((string)($_POST['confirm_szip'] ?? ($shipping['szip'] ?? ''))),
            'scountry' => trim((string)($_POST['confirm_scountry'] ?? ($shipping['scountry'] ?? 'IN'))),
            'sphone' => trim((string)($_POST['confirm_sphone'] ?? ($shipping['sphone'] ?? ''))),
        ];
    }

    if (trim((string)($shipping['sname'] ?? '')) === '') {
        $shipping['sname'] = trim(
            trim((string)($billing['first_name'] ?? '')) . ' ' . trim((string)($billing['last_name'] ?? ''))
        );
    }
    foreach (
        [
            'sphone' => 'phone',
            'saddress1' => 'address1',
            'saddress2' => 'address2',
            'scity' => 'city',
            'sstate' => 'state',
            'szip' => 'zip',
            'scountry' => 'country',
        ] as $sk => $bk
    ) {
        if (trim((string)($shipping[$sk] ?? '')) === '' && trim((string)($billing[$bk] ?? '')) !== '') {
            $shipping[$sk] = $billing[$bk];
        }
    }

    return ['billing' => $billing, 'shipping' => $shipping];
}


// function create_order($cartData, $paymentType = 'cod')
function create_order($cartData, $paymentType = 'cash', $note = '')
{
    global $conn; // mysqli connection

    $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';

    // Always take checkoutdata (and cod line) from live GET /cart/retrieve, not stale caller data.
    $liveCart = get_cart();
    $cartData['checkoutdata'] = $liveCart['checkoutdata'];
    $cartData['codcharges'] = $liveCart['codcharges'] ?? ($cartData['codcharges'] ?? 0);

    $allowedApiPaymentTypes = [
        'offline',
        'cc',
        'razorpay',
        'cod',
        'bank_transfer',
        'pos_machine',
        'specialpay',
        'cheque',
        'demand_draft',
    ];
    $postPayment = trim((string)($_POST['payment_type'] ?? ''));
    if ($postPayment !== '' && in_array($postPayment, $allowedApiPaymentTypes, true)) {
        $apiPaymentType = $postPayment;
    } else {
        $apiPaymentType = cart_map_payment_type_for_order_api($paymentType);
    }

    $resolved = cart_resolve_order_billing_shipping_for_api($conn);
    $billing = $resolved['billing'];
    $shipping = $resolved['shipping'];

    if (empty($billing['first_name']) || empty($billing['phone']) || empty($billing['state']) || empty($billing['zip'])) {
        return [
            'success' => false,
            'message' => 'Customer billing details are required. Select a customer or complete the POS customer form before placing the order.',
        ];
    }

    if (empty($shipping['sname']) || empty($shipping['sphone']) || empty($shipping['sstate'])) {
        return [
            'success' => false,
            'message' => 'Shipping details are required. Complete shipping address and contact on the POS customer form.',
        ];
    }

    $codchargesVal = (float)($cartData['codcharges'] ?? 0);
    if ($apiPaymentType === 'cod' && $codchargesVal > 0) {
        $cod = '1';
        $codCharges = (string)$codchargesVal;
    } else {
        $cod = '0';
        $codCharges = '0';
    }

    $storeId = (string)((int)($_SESSION['warehouse_id'] ?? 0));
    if ($storeId === '0' || $storeId === '') {
        $storeId = 'store';
    }
    $transactionId = trim((string)($_POST['transaction_id'] ?? ''));
    $effectiveTransactionId = $transactionId !== '' ? $transactionId : ('store.' . gmdate('YmdHis'));
    $store_payment_details = $storeId . '|' . $apiPaymentType . '|' . $effectiveTransactionId;

    if (!cart_has_usable_checkoutdata($cartData)) {
        return [
            'success' => false,
            'message' => 'Cart empty or checkout session expired.',
        ];
    }

    $serializedCheckoutdata = serialize($cartData['checkoutdata'] ?? '');

    $razorpay = [
        'razorpay_order_id' => $_POST['razorpay_order_id'] ?? '',
        'razorpay_payment_id' => $_POST['razorpay_payment_id'] ?? '',
        'razorpay_signature' => $_POST['razorpay_signature'] ?? '',
        'magiccheckout_done' => $_POST['magiccheckout_done'] ?? '',
    ];

    $card = [
        'cardnumber' => $_POST['cardnumber'] ?? '',
        'cardexpmonth' => $_POST['cardexpmonth'] ?? '',
        'cardexpyear' => $_POST['cardexpyear'] ?? '',
        'card_cvv' => $_POST['card_cvv'] ?? '',
    ];

    $postData = array_merge(
        [
            'payment_type' => $apiPaymentType,
            'buynow' => '0',
            'checkoutdata' => $serializedCheckoutdata,
            'cod' => $cod,
            'codcharges' => $codCharges,
            'store_payment_details' => $store_payment_details,
        ],
        $billing,
        $shipping,
        $razorpay,
        $card
    );

    $result = exotic_api_call(
        '/order/create',
        'POST',
        ['discountcoupondetails' => $coupon],
        $postData
    );
    // if (empty($cartData['checkoutdata'])) {
    //     echo json_encode([
    //         'success' => false,
    //         'message' => 'Checkout session expired'
    //     ]);
    //     exit;
    // }
    // // header('Content-Type: application/json');
    // if (empty($result['data']['orderid'])) {

    //     echo json_encode([
    //         'success' => false,
    //         'api_response' => $result
    //     ]);

    //     exit;
    // }
    if (!empty($result['data']['orderid'])) {

        $orderId = $result['data']['orderid'];

        /* -----------------------
           SAVE ORDER
        ------------------------*/

        $smartech = $result['data']['smartech'][0] ?? [];

        $params = json_decode($smartech['params'], true);

        $amount = $params['amount'] ?? 0;
        $totalQty = $params['total_prqt'] ?? 0;
        $source = $params['source'] ?? '';
        $link = $params['link'] ?? '';
        $domain = $params['domain'] ?? '';

        $rawResponse = json_encode($result['data']);

        $stmt = $conn->prepare("INSERT INTO pos_orders 
        (api_order_id,payment_mode,total_amount,total_qty,source,invoice_link,domain,raw_response,note)
        VALUES (?,?,?,?,?,?,?,?,?)");

        $stmt->bind_param(
            "isdiissss",
            $orderId,
            $apiPaymentType,
            $amount,
            $totalQty,
            $source,
            $link,
            $domain,
            $rawResponse,
            $note
        );

        $stmt->execute();

        $localOrderId = $stmt->insert_id;

        /* -----------------------
           SAVE ORDER ITEMS
        ------------------------*/

        if (!empty($params['items'])) {

            foreach ($params['items'] as $item) {

                $stmt2 = $conn->prepare("INSERT INTO pos_order_items
                (order_id,product_code,product_name,category,subcategories,image_url,product_url,quantity,selling_price,discounted_price,language,author,publisher)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");

                $stmt2->bind_param(
                    "issssssiddsss",
                    $localOrderId,
                    $item['prid'],
                    $item['product_name'],
                    $item['category'],
                    $item['subcategories'],
                    $item['image'],
                    $item['product_url'],
                    $item['prqt'],
                    $item['selling_price'],
                    $item['discounted_price'],
                    $item['language'],
                    $item['author'],
                    $item['publisher']
                );

                $stmt2->execute();
            }
        }

        unset($_SESSION['discount_coupon']);

        return [
            "success" => true,
            "orderid" => $orderId,
            "message" => "Order placed successfully"
        ];
    } else {

        return [
            "success" => false,
            "message" => "Order failed",
            "api_response" => $result
        ];
    }
}

function get_orders()
{

    return exotic_api_call(
        '/order/retrieve',
        'GET',
        [
            'duration' => 30,
            'offset' => 0,
            'showpackages' => 1
        ]
    );
}

