<?php
// cart-functions.php

function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null)
{
    // echo "<pre>";
    // print_r($_SESSION['discount_coupon']['discountcoupondetails']);
    // exit;

    $url = 'https://www.exoticindia.com/api' . $endpoint;
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
        'User-Agent: ExoticPOS-Web/1.0'
    ];

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($method === 'POST' && $postData) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    return ['data' => json_decode($response, true) ?: [], 'code' => $httpCode];
}


function get_cart()
{
    $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';

    $res = exotic_api_call(
        '/cart/retrieve',
        'GET',
        [
            'discountcoupondetails' => $coupon,
            'giftvoucherdetails' => ''
        ]
    );

    $data = $res['data'] ?? [];

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

            $items[] = [
                'cartref' => $item['cartref'],
                'name' => $item['name'],
                'imageurl' => $item['imageurl'],
                'price' => (float)$item['price'],
                'quantity' => (int)$item['quantity'],
                'shipping' => $shipping,
                'shipping_per_unit' => $shipping_per_unit,
                'shipping_title' => $item['express_shipping_option']['title'] ?? '',
                'shipping_longtitle' => $item['express_shipping_option']['longtitle'] ?? '',
                'express_selected' => $expressSelected
            ];

            $subtotal += ((float)$item['price'] * (int)$item['quantity']);
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
        'checkoutdata' => $data['checkoutdata'] ?? ''
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
        ]
    );

    $response = $result['data'] ?? [];
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


// function create_order($cartData, $paymentType = 'cod')
function create_order($cartData, $paymentType = 'cash', $note = '')
{
    global $conn; // mysqli connection

    $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
    // echo "<pre>";
    // print_r($paymentType);
    // exit;
    $postData = [
        "payment_type" => "cod",
        "buynow" => "0",

        // IMPORTANT → put checkoutdata from cart/retrieve here
        'checkoutdata' => $cartData['checkoutdata'],

        // "cardnumber" => "",
        // "cardexpmonth" => "",
        // "cardexpyear" => "",
        // "card_cvv" => "",

        // "razorpay_order_id" => "",
        // "razorpay_payment_id" => "",
        // "razorpay_signature" => "",

        // "magiccheckout_done" => "",
        // "paypal_transaction_status" => "",
        // "paypal_transaction_id" => "",

        "cod" => "0",
        "codcharges" => "0",

        "first_name" => $_SESSION['user']['name'] ?? "POS",
        "last_name" => "User",
        "email" => "test@example.com",

        "address1" => "Test Address",
        "address2" => "",

        "city" => "Ahmedabad",
        "state" => "Gujarat",
        "zip" => "380001",
        "country" => "IN",
        "phone" => $_SESSION['user']['phone'] ?? "9999999999",

        "gstin" => "",

        "sname" => "Test User",
        "saddress1" => "Test Address",
        "saddress2" => "",

        "scity" => "Ahmedabad",
        "sstate" => "Gujarat",
        "szip" => "380001",
        "scountry" => "IN",
        "sphone" => "9999999999"
    ];



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
            $paymentType,
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

    exit;
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

