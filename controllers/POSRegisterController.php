<?php
require_once 'models/pos/pos.php';
//require_once 'models/pos/product.php';

class POSRegisterController
{
    private $product;
    private $pos;

    public function __construct($conn)
    {
        $this->pos     = new pos($conn);
    }

    public function index()
    {
        // slug => label
        $categories = getCategories();
        require_once 'models/user/user.php';
        global $conn;   // use existing DB connection
        $usersModel = new User($conn);   //  create instance

        $warehouseName = 'No Warehouse';

        if (!empty($_SESSION['warehouse_id'])) {
            $warehouse = $usersModel->getWarehouseById($_SESSION['warehouse_id']);
            $warehouseName = $warehouse['address_title'] ?? 'No Warehouse';
        }
        // Add "All Products" (slug => label)
        // Put it first:
        $categories = ['allProducts' => 'All Products'] + $categories;

        // slug => svg icon
        $categoryIcons = [
            'allProducts' => '
                ☰
            ',
            'paintings' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path d="M21 15l-5-5L5 21" />
                </svg>
            ',
            'sculptures' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z" />
                </svg>
            ',
            'textiles' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4l4 4-4 4M8 4L4 8l4 4M4 8h16v12H4z" />
                </svg>
            ',
            'jewelry' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="8" />
                    <path d="M12 4v8l4 4" />
                </svg>
            ',
            'homeandliving' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M9 21V9h6v12" />
                </svg>
            ',
            'book' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v15H6.5A2.5 2.5 0 014 14.5v-10A2.5 2.5 0 016.5 2z" />
                </svg>
            ',
        ];

        // Build final array: slug => [label, icon]
        $categoryData = [];
        foreach ($categories as $slug => $label) {
            $categoryData[$slug] = [
                'label' => $label,
                'icon'  => $categoryIcons[$slug] ?? '', // fallback
            ];
        }
        $cartData = $this->get_cart();
        renderTemplate('views/pos_register/index.php', [
            'categories' => $categoryData,
            'warehouse_name' => $warehouseName,
            'cartData' => $this->get_cart()
        ]);
    }

    /**
     * DataTables AJAX endpoint for products list
     */
    public function productsAjax()
    {
        // Prefer infinite-scroll params if provided
        $pageNo  = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : null;
        $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : null;

        if ($pageNo !== null && $perPage !== null) {
            $length = $perPage;
            $start  = ($pageNo - 1) * $perPage;
            $draw   = 0; // not needed for infinite scroll
        } else {
            // DataTables fallback
            $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 0;
            $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
            $length = isset($_GET['length']) ? (int)$_GET['length'] : 12;
            $pageNo = (int) floor($start / max(1, $length)) + 1;
        }

        // DataTables global search
        $searchValue = '';
        if (isset($_GET['search']['value'])) {
            $searchValue = trim($_GET['search']['value']);
        }

        // Custom filters (add back category/product_code if needed)
        $category    = isset($_GET['category']) ? trim($_GET['category']) : '';
        //$productCode = isset($_GET['product_code']) ? trim($_GET['product_code']) : '';
        $productName = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';

        // ordering (allow simple defaults for infinite scroll)
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
        $orderDir         = isset($_GET['order'][0]['dir'])    ? $_GET['order'][0]['dir']         : 'asc';

        $columns = [
            0 => 'image',
            1 => 'item_code',
            2 => 'title',
            3 => 'stock_qty',
            4 => 'price',
        ];
        $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'title';

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $productName,
            $orderColumn,
            $orderDir,
            $category
        );

        // total_pages helpful for frontend
        $totalFiltered = (int) ($result['recordsFiltered'] ?? 0);
        $totalPages = ($length > 0) ? (int) ceil($totalFiltered / $length) : 1;

        $response = [
            'draw'            => $draw,
            'recordsTotal'    => $result['recordsTotal'] ?? 0,
            'recordsFiltered' => $result['recordsFiltered'] ?? 0,
            'data'            => $result['data'] ?? [],
            'current_page'    => $pageNo,
            'per_page'        => $length,
            'total_pages'     => $totalPages,
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Proxy: Add to cart (Exotic India API)
     */
    public function cartAdd()
    {
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        if ($code === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing product code.']);
            exit;
        }

        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        if ($qty < 1) $qty = 1;

        $discountCoupon = isset($_POST['discountcoupondetails']) ? trim($_POST['discountcoupondetails']) : '';
        $giftVoucher = isset($_POST['giftvoucherdetails']) ? trim($_POST['giftvoucherdetails']) : '';
        $variation = isset($_POST['variation']) ? trim($_POST['variation']) : '';
        $options = isset($_POST['options']) ? trim($_POST['options']) : '';

        $query = [];
        if ($discountCoupon !== '') $query['discountcoupondetails'] = $discountCoupon;
        if ($giftVoucher !== '') $query['giftvoucherdetails'] = $giftVoucher;

        $url = 'https://www.exoticindia.com/cart/add';
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $postData = [
            'code' => $code,
            'qty'  => $qty
        ];

        if ($variation !== '') $postData['variation'] = $variation;
        if ($options !== '') $postData['options'] = $options;

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'Content-Type: application/x-www-form-urlencoded',
            'x-adminapitest: 1',
        ];

        if (!empty($_SESSION['x_api_euid'])) {
            $headers[] = 'x-api-euid: ' . $_SESSION['x_api_euid'];
        }

        // Debug: log equivalent curl request
        $curlParts = [];
        $curlParts[] = 'curl -X POST';
        foreach ($headers as $h) {
            $curlParts[] = '-H ' . escapeshellarg($h);
        }
        $curlParts[] = escapeshellarg($url);
        $curlParts[] = '--data ' . escapeshellarg(http_build_query($postData));
        print('[POS cart-add] ' . implode(' ', $curlParts));
        die;


        $capturedEuid = null;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$capturedEuid) {
            $len = strlen($headerLine);
            $header = explode(':', $headerLine, 2);
            if (count($header) < 2) {
                return $len;
            }

            $name = strtolower(trim($header[0]));
            if ($name === 'x-api-euid') {
                $capturedEuid = trim($header[1]);
            }
            return $len;
        });

        $response = curl_exec($ch);
        // print_r($response);
        die;
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!empty($capturedEuid)) {
            $_SESSION['x_api_euid'] = $capturedEuid;
        }

        header('Content-Type: application/json');

        if ($error) {
            http_response_code(502);
            echo json_encode(['error' => $error]);
            exit;
        }

        if ($status) {
            http_response_code($status);
        }

        // Pass-through response (expected to be JSON)
        echo $response;
        exit;
    }

    /**
     * Helper method to fetch registers
     */
    private function getRegisters()
    {
        // TODO: Implement database query
        return [];
    }

    public  function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null)
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


    public function get_cart()
    {
        $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';

        $res = $this->exotic_api_call(
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

    public function add_to_cart($code, $qty, $variation = '', $options = '', $buyNow = false)
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
        $result = $this->exotic_api_call('/cart/add', 'POST', [], $postData);

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
    public  function change_qty($cartref, $newqty)
    {
        return $this->exotic_api_call('/cart/modifyqty', 'GET', [
            'cartid' => $cartref,
            'newqty' => $newqty
        ]);
    }

    public function remove_item($cartref)
    {
        return $this->change_qty($cartref, 0);
    }

    public function apply_coupon($couponId)
    {
        if (empty($couponId)) {
            return [
                'success' => false,
                'message' => 'Coupon code required'
            ];
        }

        $result = $this->exotic_api_call(
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

    public  function modify_express_shipping($cartid, $action)
    {
        // echo $cartid; exit;
        return $this->exotic_api_call(
            '/cart/modifycartexpress',
            'GET',
            [
                'cartid' => $cartid,
                'action' => $action
            ]
        );
    }


    // function create_order($cartData, $paymentType = 'cod')
    public  function create_order($cartData, $paymentType = 'cash', $note = '')
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



        $result = $this->exotic_api_call(
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

    public  function get_orders()
    {

        return $this->exotic_api_call(
            '/order/retrieve',
            'GET',
            [
                'duration' => 30,
                'offset' => 0,
                'showpackages' => 1
            ]
        );
    }
}
