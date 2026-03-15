<?php
require_once 'models/pos/pos.php';
require_once 'models/user/user.php';

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
        require_once 'models/customer/Customer.php';
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

        $customerModel = new Customer($conn);
        $customers = $customerModel->getAllCustomers(100, 0, []);
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

        renderTemplate('views/pos_register/index.php', [
            'categories' => $categoryData,
            'warehouse_name' => $warehouseName,
            'cartData' => $this->get_cart(),
            'customers' => $customers
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
        //   echo '<pre>';
        // print_r($response);
        // exit;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return ['data' => json_decode($response, true) ?: [], 'code' => $httpCode];
    }
    public function exotic_api_call_new($endpoint, $method = 'GET', $params = [], $postData = null)
    {
        $url = 'https://www.exoticindia.com/api' . $endpoint;

        if ($params) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init($url);

        $headers = [
            'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
            'x-api-deviceid: POS-Store_1',
            'x-api-appplayerid: POS-Web-Terminal',
            'x-api-countrycode: IN',
            'User-Agent: ExoticPOS-Web/1.0'
        ];

        if (!empty($_SESSION['x_api_euid'])) {
            $headers[] = 'x-api-euid: ' . $_SESSION['x_api_euid'];
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        /* CAPTURE RESPONSE HEADERS */
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header) {
            $len = strlen($header);
            $header = explode(':', $header, 2);

            if (count($header) < 2) return $len;

            $name = strtolower(trim($header[0]));
            $value = trim($header[1]);

            if ($name == 'x-api-euid') {
                $_SESSION['x_api_euid'] = $value;
            }

            if ($name == 'x-api-etd') {
                $_SESSION['x_api_etd'] = $value;
            }

            if ($name == 'x-api-browsehistory') {
                $_SESSION['x_api_browsehistory'] = $value;
            }

            return $len;
        });

        if ($method == 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);

            if (is_array($postData)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData); // multipart
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            }
        }

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        return [
            'data' => json_decode($response, true),
            'code' => $code,
            'raw'  => $response
        ];
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
        $codcharges = (float)($data['codcharges_if_chosen'] ?? 0);
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
            'checkoutdata' => $data['checkoutdata'] ?? '',
            'codcharges' => $codcharges,
            'currency' => $data['fx_type'] ?? 'INR'
        ];
    }


    public function add_to_cart()
    {
        $code      = $_POST['code'] ?? '';
        $qty       = $_POST['qty'] ?? 1;
        $variation = $_POST['variation'] ?? '';
        $options   = $_POST['options'] ?? '';
        $buyNow    = false;

        if (empty(trim($code))) {
            header("Location: ?page=pos_register");
            exit;
        }

        $coupon = '';

        if (!empty($_SESSION['discount_coupon'])) {
            if (is_array($_SESSION['discount_coupon'])) {
                $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
            } else {
                $coupon = $_SESSION['discount_coupon'];
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

        $result = $this->exotic_api_call('/cart/add', 'POST', [], $postData);

        // optional: check success
        if (!empty($result['data']['cartref'])) {
            // item added
        }

        header("Location: ?page=pos_register");
        exit;
    }

    public function change_qty()
    {
        $cartref = $_POST['cartref'] ?? '';
        $qty = $_POST['newqty'] ?? 1;

        $this->exotic_api_call('/cart/modifyqty', 'GET', [
            'cartid' => $cartref,
            'newqty' => $qty
        ]);

        header("Location: ?page=pos_register");
        exit;
    }

    public function remove_item()
    {
        $cartref = $_POST['cartref'] ?? '';

        $qty = 0;

        $this->exotic_api_call('/cart/modifyqty', 'GET', [
            'cartid' => $cartref,
            'newqty' => $qty
        ]);

        header("Location: ?page=pos_register");
        exit;
    }


    public function apply_coupon()
    {
        $couponId = $_POST['coupon'] ?? '';

        if (empty($couponId)) {
            header("Location: ?page=pos_register");
            exit;
        }

        $result = $this->exotic_api_call(
            '/cart/addcoupon',
            'GET',
            [
                'couponid' => $couponId
            ]
        );

        $response = $result['data'] ?? '';

        if (!empty($response) && !isset($response['error'])) {

            // store string coupon
            $_SESSION['discount_coupon'] = $response;
        }

        header("Location: ?page=pos_register");
        exit;
    }

    public function modify_express_shipping()
    {
        $cartid = $_POST['cartid'] ?? '';
        $action = $_POST['action'] ?? '';

        if (!$cartid || !$action) {
            header("Location: ?page=pos_register");
            exit;
        }

        $this->exotic_api_call(
            '/cart/modifycartexpress',
            'GET',
            [
                'cartid' => $cartid,
                'action' => $action
            ]
        );

        header("Location: ?page=pos_register");
        exit;
    }




    public function create_order()
    {
        global $conn;

        header('Content-Type: application/json');
        $paymentType = 'offline';
        /* ================= PAYMENT TYPE ================= */
        // $paymentType = $_POST['payment_type'] ?? 'cod';
        $note = $_POST['note'] ?? '';

        // if (!in_array($paymentType, ['cod', 'razorpay', 'offline', 'cc'])) {
        //     $paymentType = 'offline';
        // }

        $transactionId = $_POST['transaction_id'] ?? '';

        /* ================= USER / STORE ================= */
        $userModel = new User($conn);
        $user_id = $_SESSION['user']['id'] ?? 0;
        $user = $userModel->getUserById($user_id);
        $warehouse_name = $user['warehouse_name'] ?? 'POS';

        $store_payment_details = $warehouse_name . "_" . $paymentType . "_" . $transactionId . "_" . $note;

        /* ================= CART ================= */
        $cartData = $this->get_cart();

        if (empty($cartData['checkoutdata'])) {
            echo json_encode([
                "success" => false,
                "message" => "Cart empty"
            ]);
            exit;
        }


        /* ================= CUSTOMER ================= */

        $billing = [];
        $shipping = [];

        // $customerId = $_POST['customer_id'] ?? 0;
        $customerId = $_POST['customer_id'] ?? ($_SESSION['pos_customer_id'] ?? 0);

        /* ---------- STEP 1 : EXISTING CUSTOMER ---------- */
        if ($customerId > 0) {

            $stmt = $conn->prepare("
        SELECT * FROM vp_order_info
        WHERE customer_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();

            if ($info) {

                /*  EXISTING CUSTOMER WITH ORDER HISTORY */
                $billing = [
                    "first_name" => $info['first_name'],
                    "last_name" => $info['last_name'],
                    "email" => $info['email'],
                    "phone" => $info['mobile'],
                    "address1" => $info['address_line1'],
                    "address2" => $info['address_line2'],
                    "city" => $info['city'],
                    "state" => $info['state'],
                    "zip" => $info['zipcode'],
                    "country" => $info['country'] ?: 'IN',
                    "gstin" => $info['gstin']
                ];

                $shipping = [
                    "sname" => trim($info['shipping_first_name'] . " " . $info['shipping_last_name']),
                    "saddress1" => $info['shipping_address_line1'],
                    "saddress2" => $info['shipping_address_line2'],
                    "scity" => $info['shipping_city'],
                    "sstate" => $info['shipping_state'],
                    "szip" => $info['shipping_zipcode'],
                    "scountry" => $info['shipping_country'] ?: 'IN',
                    "sphone" => $info['shipping_mobile']
                ];
            }
        }

        /* ---------- STEP 2 : NEW CUSTOMER (SESSION FORM) ---------- */
        if (empty($billing) && !empty($_SESSION['pos_customer_form'])) {

            $form = $_SESSION['pos_customer_form'];

            $billing = [
                "first_name" => trim($form['first_name'] ?? ''),
                "last_name" => trim($form['last_name'] ?? ''),
                "email" => trim($form['cus_email'] ?? ''),
                "phone" => trim($form['mobile'] ?? ''),
                "address1" => trim($form['address_line1'] ?? ''),
                "address2" => trim($form['address_line2'] ?? ''),
                "city" => trim($form['city'] ?? ''),
                "state" => trim($form['state'] ?? ''),
                "zip" => trim($form['zipcode'] ?? ''),
                "country" => "IN",
                "gstin" => trim($form['gstin'] ?? '')
            ];

            $shipping = [
                "sname" => trim(($form['shipping_first_name'] ?? '') . " " . ($form['shipping_last_name'] ?? '')),
                "saddress1" => trim($form['shipping_address_line1'] ?? ''),
                "saddress2" => trim($form['shipping_address_line2'] ?? ''),
                "scity" => trim($form['shipping_city'] ?? ''),
                "sstate" => trim($form['shipping_state'] ?? ''),
                "szip" => trim($form['shipping_zipcode'] ?? ''),
                "scountry" => "IN",
                "sphone" => trim($form['shipping_mobile'] ?? '')
            ];
        }
        // echo '<pre>';
        // print_r($billing);
        // exit;

        /* ================= VALIDATION ================= */
        if (!$billing['first_name'] || !$billing['phone'] || !$billing['state'] || !$billing['zip']) {
            echo json_encode(["success" => false, "message" => "Billing missing"]);
            exit;
        }

        if (!$shipping['sname'] || !$shipping['sphone'] || !$shipping['sstate']) {
            echo json_encode(["success" => false, "message" => "Shipping missing"]);
            exit;
        }

        /* ================= COD ================= */

        if ($paymentType == 'cod' && $cartData['codcharges'] > 0) {
            $cod = "1";
            $codCharges = (string)$cartData['codcharges'];
        } else {
            $cod = "0";
            $codCharges = "0";
        }

        /* ================= RAZORPAY ================= */
        $razorpay = [
            "razorpay_order_id" => $_POST['razorpay_order_id'] ?? '',
            "razorpay_payment_id" => $_POST['razorpay_payment_id'] ?? '',
            "razorpay_signature" => $_POST['razorpay_signature'] ?? '',
            "magiccheckout_done" => $_POST['magiccheckout_done'] ?? ''
        ];

        /* ================= CARD ================= */
        $card = [
            "cardnumber" => $_POST['cardnumber'] ?? '',
            "cardexpmonth" => $_POST['cardexpmonth'] ?? '',
            "cardexpyear" => $_POST['cardexpyear'] ?? '',
            "card_cvv" => $_POST['card_cvv'] ?? ''
        ];

        /* ================= FINAL DATA ================= */
        $postData = array_merge([
            "payment_type" => $paymentType,
            "buynow" => "0",
            "checkoutdata" => $cartData['checkoutdata'], // RAW !!!
            "cod" => $cod,
            "codcharges" => $codCharges,
            "store_payment_details" => $store_payment_details
        ], $billing, $shipping, $razorpay, $card);

        $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';

        /* ================= API CALL ================= */
        $result = $this->exotic_api_call(
            '/order/create',
            'POST',
            ['discountcoupondetails' => $coupon],
            $postData
        );

        if (empty($result['data']['orderid'])) {
            echo json_encode([
                "success" => false,
                "message" => "Order API failed",
                "api" => $result
            ]);
            exit;
        }

        unset($_SESSION['discount_coupon']);
        unset($_SESSION['pos_customer_form']);
        unset($_SESSION['pos_customer_id']);
        echo json_encode([
            "success" => true,
            "orderid" => $result['data']['orderid']
        ]);
    }

   
    public function add_customer()
    {
        global $conn;

        $first = $_POST['first_name'] ?? '';
        $last  = $_POST['last_name'] ?? '';
        $phone = $_POST['mobile'] ?? '';
        $email = $_POST['cus_email'] ?? '';

        if (!$first || !$phone) {
            echo json_encode([
                "success" => false,
                "message" => "Name and phone required"
            ]);
            exit;
        }

        $name = trim($first . ' ' . $last);

        $stmt = $conn->prepare("
        INSERT INTO vp_customers (name,email,phone)
        VALUES (?,?,?)
    ");
        $stmt->bind_param("sss", $name, $email, $phone);
        $stmt->execute();

        $id = $stmt->insert_id;

        /*  STORE FULL BILLING + SHIPPING IN SESSION */
        $_SESSION['pos_customer_id'] = $id;
        $_SESSION['pos_customer_form'] = $_POST;

        echo json_encode([
            "success" => true,
            "customer" => [
                "id" => $id,
                "name" => $name,
                "phone" => $phone,
                "email" => $email
            ]
        ]);

        exit;
    }
    public function set_customer()
    {

        // $customerId = $_POST['customer_id'] ?? '';
        $customerId = $_POST['customer_id'] ?? '';

        if ($customerId) {
            $_SESSION['pos_customer_id'] = $customerId;
            unset($_SESSION['pos_customer_form']); // ⭐ VERY IMPORTANT
        } else {
            unset($_SESSION['pos_customer_id']);
        }

        echo json_encode(["success" => true]);
        exit;
    }

    public function remove_coupon()
    {
        if (isset($_SESSION['discount_coupon'])) {
            unset($_SESSION['discount_coupon']);
        }

        $_SESSION['coupon_status'] = "success";

        header("Location: ?page=pos_register");
        exit;
    }
    public function apply_custom_discount()
    {
        $amount = $_POST['amount'] ?? 0;

        $amount = floatval($amount);

        $this->exotic_api_call(
            '/cart/addcustomdiscount',
            'GET',
            ['custom_reduce' => $amount]
        );

        echo json_encode([
            "success" => true
        ]);
        exit;
    }
    
}
