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

    /**
     * Discard all output buffers (bootstrap whitespace, nested ob_start, notices) so JSON is the only body.
     */
    private function clearBufferedHttpOutput(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    public function index()
    {
        // slug => label
        $categories = getCategories();
        require_once 'models/user/user.php';
        require_once 'models/customer/Customer.php';
        global $conn;   // use existing DB connection
        $usersModel = new User($conn);   //  create instance

        if ((int)($_SESSION['warehouse_id'] ?? 0) <= 0 && $conn instanceof mysqli) {
            $defWh = $this->getDefaultWarehouseRow($conn);
            if ($defWh !== null && !empty($defWh['id'])) {
                $_SESSION['warehouse_id'] = $defWh['id'];
            }
        }

        $warehouseName = 'No Warehouse';

        if (!empty($_SESSION['warehouse_id'])) {
            $warehouse = $usersModel->getWarehouseById($_SESSION['warehouse_id']);
            $warehouseName = $warehouse['address_title'] ?? 'No Warehouse';
        }
        // Add "All Products" (slug => label)
        // Put it first:
        $categories = ['allProducts' => 'All Products'] + $categories;

        // Clear legacy Exotic-cart session keys so nothing re-fires discounts/coupons or stale debug.
        foreach (['cart_success', 'cart_error', 'coupon_message', 'coupon_status'] as $k) {
            unset($_SESSION[$k]);
        }
        unset(
            $_SESSION['discount_coupon'],
            $_SESSION['gift_voucher'],
            $_SESSION['custom_discount'],
            $_SESSION['pos_coupon_api_debug'],
            $_SESSION['pos_order_create_api_debug']
        );

        $customerModel = new Customer($conn);
        $selected_customer = null;
        if (!empty($_SESSION['pos_customer_id'])) {
            $cid = (int)$_SESSION['pos_customer_id'];
            if ($cid > 0) {
                $row = $customerModel->getCustomerById($cid);
                if (!empty($row['id'])) {
                    $nm = (string)($row['name'] ?? '');
                    $ph = (string)($row['phone'] ?? '');
                    $em = (string)($row['email'] ?? '');
                    $selected_customer = [
                        'id' => (int)$row['id'],
                        'name' => $nm,
                        'phone' => $ph,
                        'email' => $em,
                        'text' => trim($nm . ' | ' . $ph . ($em !== '' ? ' | ' . $em : '')),
                    ];
                } else {
                    unset($_SESSION['pos_customer_id']);
                }
            }
        }
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
            // Minimal placeholder until new cart; view must not depend on Exotic retrieve shape.
            'cartData' => [
                'items' => [],
                'grand_total' => 0.0,
                'currency' => 'INR',
            ],
            'selected_customer' => $selected_customer,
        ]);
    }

    /**
     * JSON autocomplete for POS customer field (vp_customers). Requires q length >= 2.
     */
    public function customer_search()
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $len = function_exists('mb_strlen') ? mb_strlen($q, 'UTF-8') : strlen($q);
        if ($len < 2) {
            echo json_encode(['success' => true, 'customers' => []], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        require_once 'models/customer/Customer.php';
        global $conn;
        $customerModel = new Customer($conn);
        $customers = $customerModel->searchCustomersForPos($q, 40);

        echo json_encode([
            'success' => true,
            'customers' => $customers,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * Fetch billing/shipping for POS confirmation popup.
     * Priority: vp_customers (+ pos_customer_details) → last vp_order_info → session pos_customer_form.
     */
    public function customer_order_info()
    {
        is_login();
        global $conn;
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;

        $pick = static function (string ...$parts): string {
            foreach ($parts as $p) {
                $t = trim((string)$p);
                if ($t !== '') {
                    return $t;
                }
            }

            return '';
        };

        require_once 'models/customer/Customer.php';
        $customerModel = new Customer($conn);
        $fromVc = $customerId > 0 ? $customerModel->getCustomerBillingShippingForPos($customerId) : ['billing' => [], 'shipping' => []];
        $billingVc = $fromVc['billing'];
        $shippingVc = $fromVc['shipping'];

        $billingOrder = [];
        $shippingOrder = [];

        if ($customerId > 0) {
            $stmt = $conn->prepare('SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $customerId);
                $stmt->execute();
                $info = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($info) {
                    $billingOrder = [
                        'first_name' => trim((string)($info['first_name'] ?? '')),
                        'last_name' => trim((string)($info['last_name'] ?? '')),
                        'email' => trim((string)($info['email'] ?? '')),
                        'phone' => trim((string)($info['mobile'] ?? '')),
                        'address1' => trim((string)($info['address_line1'] ?? '')),
                        'address2' => trim((string)($info['address_line2'] ?? '')),
                        'city' => trim((string)($info['city'] ?? '')),
                        'state' => trim((string)($info['state'] ?? '')),
                        'zip' => trim((string)($info['zipcode'] ?? '')),
                        'country' => trim((string)($info['country'] ?? 'IN')),
                        'gstin' => trim((string)($info['gstin'] ?? '')),
                    ];
                    $shippingOrder = [
                        'shipping_first_name' => trim((string)($info['shipping_first_name'] ?? '')),
                        'shipping_last_name' => trim((string)($info['shipping_last_name'] ?? '')),
                        'sname' => trim((string)(($info['shipping_first_name'] ?? '').' '.($info['shipping_last_name'] ?? ''))),
                        'saddress1' => trim((string)($info['shipping_address_line1'] ?? '')),
                        'saddress2' => trim((string)($info['shipping_address_line2'] ?? '')),
                        'scity' => trim((string)($info['shipping_city'] ?? '')),
                        'sstate' => trim((string)($info['shipping_state'] ?? '')),
                        'szip' => trim((string)($info['shipping_zipcode'] ?? '')),
                        'scountry' => trim((string)($info['shipping_country'] ?? 'IN')),
                        'sphone' => trim((string)($info['shipping_mobile'] ?? '')),
                    ];
                }
            }
        }

        $billingSession = [];
        $shippingSession = [];
        if (!empty($_SESSION['pos_customer_form'])) {
            $form = $_SESSION['pos_customer_form'];
            $billingSession = [
                'first_name' => trim((string)($form['first_name'] ?? '')),
                'last_name' => trim((string)($form['last_name'] ?? '')),
                'email' => trim((string)($form['cus_email'] ?? '')),
                'phone' => trim((string)($form['mobile'] ?? '')),
                'address1' => trim((string)($form['address_line1'] ?? '')),
                'address2' => trim((string)($form['address_line2'] ?? '')),
                'city' => trim((string)($form['city'] ?? '')),
                'state' => trim((string)($form['state'] ?? '')),
                'zip' => trim((string)($form['zipcode'] ?? '')),
                'country' => trim((string)($form['country'] ?? 'IN')),
                'gstin' => trim((string)($form['gstin'] ?? '')),
            ];
            $shippingSession = [
                'shipping_first_name' => trim((string)($form['shipping_first_name'] ?? '')),
                'shipping_last_name' => trim((string)($form['shipping_last_name'] ?? '')),
                'sname' => trim((string)(($form['shipping_first_name'] ?? '').' '.($form['shipping_last_name'] ?? ''))),
                'saddress1' => trim((string)($form['shipping_address_line1'] ?? '')),
                'saddress2' => trim((string)($form['shipping_address_line2'] ?? '')),
                'scity' => trim((string)($form['shipping_city'] ?? '')),
                'sstate' => trim((string)($form['shipping_state'] ?? '')),
                'szip' => trim((string)($form['shipping_zipcode'] ?? '')),
                'scountry' => trim((string)($form['shipping_country'] ?? 'IN')),
                'sphone' => trim((string)($form['shipping_mobile'] ?? '')),
            ];
        }

        $billing = [
            'first_name' => $pick($billingVc['first_name'] ?? '', $billingOrder['first_name'] ?? '', $billingSession['first_name'] ?? ''),
            'last_name' => $pick($billingVc['last_name'] ?? '', $billingOrder['last_name'] ?? '', $billingSession['last_name'] ?? ''),
            'email' => $pick($billingVc['email'] ?? '', $billingOrder['email'] ?? '', $billingSession['email'] ?? ''),
            'phone' => $pick($billingVc['phone'] ?? '', $billingOrder['phone'] ?? '', $billingSession['phone'] ?? ''),
            'address1' => $pick($billingVc['address1'] ?? '', $billingOrder['address1'] ?? '', $billingSession['address1'] ?? ''),
            'address2' => $pick($billingVc['address2'] ?? '', $billingOrder['address2'] ?? '', $billingSession['address2'] ?? ''),
            'city' => $pick($billingVc['city'] ?? '', $billingOrder['city'] ?? '', $billingSession['city'] ?? ''),
            'state' => $pick($billingVc['state'] ?? '', $billingOrder['state'] ?? '', $billingSession['state'] ?? ''),
            'zip' => $pick($billingVc['zip'] ?? '', $billingOrder['zip'] ?? '', $billingSession['zip'] ?? ''),
            'country' => $pick($billingVc['country'] ?? '', $billingOrder['country'] ?? '', $billingSession['country'] ?? ''),
            'gstin' => $pick($billingVc['gstin'] ?? '', $billingOrder['gstin'] ?? '', $billingSession['gstin'] ?? ''),
        ];

        $shipping = [
            'shipping_first_name' => $pick($shippingVc['shipping_first_name'] ?? '', $shippingOrder['shipping_first_name'] ?? '', $shippingSession['shipping_first_name'] ?? ''),
            'shipping_last_name' => $pick($shippingVc['shipping_last_name'] ?? '', $shippingOrder['shipping_last_name'] ?? '', $shippingSession['shipping_last_name'] ?? ''),
            'sname' => $pick($shippingVc['sname'] ?? '', $shippingOrder['sname'] ?? '', $shippingSession['sname'] ?? ''),
            'saddress1' => $pick($shippingVc['saddress1'] ?? '', $shippingOrder['saddress1'] ?? '', $shippingSession['saddress1'] ?? ''),
            'saddress2' => $pick($shippingVc['saddress2'] ?? '', $shippingOrder['saddress2'] ?? '', $shippingSession['saddress2'] ?? ''),
            'scity' => $pick($shippingVc['scity'] ?? '', $shippingOrder['scity'] ?? '', $shippingSession['scity'] ?? ''),
            'sstate' => $pick($shippingVc['sstate'] ?? '', $shippingOrder['sstate'] ?? '', $shippingSession['sstate'] ?? ''),
            'szip' => $pick($shippingVc['szip'] ?? '', $shippingOrder['szip'] ?? '', $shippingSession['szip'] ?? ''),
            'scountry' => $pick($shippingVc['scountry'] ?? '', $shippingOrder['scountry'] ?? '', $shippingSession['scountry'] ?? ''),
            'sphone' => $pick($shippingVc['sphone'] ?? '', $shippingOrder['sphone'] ?? '', $shippingSession['sphone'] ?? ''),
        ];

        echo json_encode([
            'success' => true,
            'billing' => $billing,
            'shipping' => $shipping,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function stockReport()
    {
        is_login();
        require_once 'models/user/user.php';
        global $conn;
        $usersModel = new User($conn);

        $sessionWh = (int) ($_SESSION['warehouse_id'] ?? 0);
        // Match helpers/html_helpers.php hasPermission(): admin is role_id == 1 (loose, handles string "1").
        $isAdmin = isset($_SESSION['user']['role_id']) && $_SESSION['user']['role_id'] == 1;

        $reportWh = $sessionWh;
        if ($isAdmin && isset($_GET['warehouse_id'])) {
            $reqWh = (int) $_GET['warehouse_id'];
            if ($reqWh > 0) {
                $check = $usersModel->getWarehouseById($reqWh);
                if (!empty($check['id'])) {
                    $reportWh = $reqWh;
                }
            }
        }

        $warehouseName = 'No Warehouse';
        if ($reportWh > 0) {
            $warehouse = $usersModel->getWarehouseById($reportWh);
            $warehouseName = $warehouse['address_title'] ?? 'No Warehouse';
        }

        $filters = [
            'search' => $_GET['search'] ?? '',
            'category' => $_GET['category'] ?? 'allProducts',
            'stock_status' => $_GET['stock_status'] ?? 'all',
            'limit' => $_GET['limit'] ?? 200,
            'page_no' => isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : 1,
            'warehouse_id' => $reportWh,
        ];

        $categories = ['allProducts' => 'All Products'] + getCategories();
        $totalRows = $this->pos->getStockReportCount($filters);
        $rows = $this->pos->getStockReport($filters);
        $limit = (int)($filters['limit'] ?? 200);
        $pageNo = (int)($filters['page_no'] ?? 1);
        $totalPages = $limit > 0 ? (int)ceil($totalRows / $limit) : 1;
        if ($totalPages < 1) {
            $totalPages = 1;
        }

        $warehouses = $isAdmin ? $usersModel->getAllWarehouses() : [];

        renderTemplate('views/pos_register/stock_report.php', [
            'warehouse_name' => $warehouseName,
            'categories' => $categories,
            'filters' => $filters,
            'rows' => $rows,
            'page_no' => $pageNo,
            'limit' => $limit,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'can_change_warehouse' => $isAdmin,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * DataTables AJAX endpoint for products list
     */
    public function productsAjax_bk()
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

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    public function productsAjax()
    {
        $pageNo  = $_GET['page_no'] ?? 1;
        $perPage = $_GET['per_page'] ?? 48;

        $start  = ($pageNo - 1) * $perPage;
        // Fastest POS: fetch one extra row to decide has_more (avoid COUNT(*))
        $length = $perPage + 1;

        $searchValue = $_GET['search']['value'] ?? '';

        $category    = $_GET['category'] ?? '';
        $productName = trim((string)($_GET['product_name'] ?? ''));
        $productCode = trim((string)($_GET['product_code'] ?? ''));
        // Same text in both fields (current POS UI) would AND two LIKE blocks and drop title-only matches.
        if ($productName !== '' && $productCode !== '' && $productName === $productCode) {
            $productCode = '';
        }

        $minPrice = $_GET['min_price'] ?? '';
        $maxPrice = $_GET['max_price'] ?? '';
        // Match stock report default: all rows with latest movement for warehouse (in|out|low|all).
        $stockFilter = strtolower(trim((string)($_GET['stock_filter'] ?? 'all')));

        // SORT
        $sortBy = $_GET['sort_by'] ?? '';

        switch ($sortBy) {
            case 'price_low_high':
                $orderColumn = 'price_india';
                $orderDir = 'asc';
                break;

            case 'price_high_low':
                $orderColumn = 'price_india';
                $orderDir = 'desc';
                break;

            case 'name_asc':
                $orderColumn = 'title';
                $orderDir = 'asc';
                break;

            case 'name_desc':
                $orderColumn = 'title';
                $orderDir = 'desc';
                break;

            default:
                $orderColumn = 'title';
                $orderDir = 'asc';
        }

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $productName,
            $orderColumn,
            $orderDir,
            $category,
            $productCode,
            $minPrice,
            $maxPrice,
            $stockFilter
        );

        $rows = $result['data'] ?? [];
        $totalFiltered = (int)($result['recordsFiltered'] ?? 0);
        if (count($rows) > (int)$perPage) {
            $rows = array_slice($rows, 0, (int)$perPage);
        }
        $hasMore = ((int)$pageNo * (int)$perPage) < $totalFiltered;
        $totalPages = $perPage > 0 ? (int)ceil($totalFiltered / (int)$perPage) : 1;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'data' => $rows,
            'current_page' => $pageNo,
            'has_more' => $hasMore,
            'per_page' => (int)$perPage,
            'total_rows' => $totalFiltered,
            'total_pages' => $totalPages
        ]);
        exit;
    }
    function cleanValue($value)
    {
        if (!$value) return '';

        // remove anything starting with (uuid OR (HTTP
        $value = preg_replace('/\((uuid|HTTP).*$/', '', $value);

        return trim($value);
    }
    function fixImageUrl($path)
    {
        if ($path === null || $path === '') {
            return '';
        }

        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        // Protocol-relative URL
        if (strpos($path, '//') === 0) {
            return 'https:' . $path;
        }
        // Already absolute
        if (strpos($path, 'http') === 0) {
            return $path;
        }

        // Relative path → CDN (ensure single slash join)
        $suffix = $path[0] === '/' ? $path : '/' . $path;
        return 'https://cdn.exoticindia.com' . $suffix;
    }

    /**
     * Product API sometimes returns fields at the root or under a nested "data" object.
     */
    private function unwrapProductApiResponse(array $data): array
    {
        if (!empty($data['data']) && is_array($data['data'])) {
            $inner = $data['data'];
            unset($data['data']);
            // Wrapper keys first; nested product object wins on conflicts (non-empty image/name).
            return array_merge($data, $inner);
        }
        return $data;
    }

    /** Prefer cleaned API text; fallback to VP row when upstream is empty. */
    private function mergeProductTextField($apiRaw, $dbRaw): string
    {
        $fromApi = $this->cleanValue(is_string($apiRaw) ? $apiRaw : '');
        if ($fromApi !== '') {
            return $fromApi;
        }
        return trim((string)$dbRaw);
    }

    /** GST % for POS: prefer API gst_rate / gst_percent / gst, else vp_products.gst. */
    private function mergeGstPercentField(array $apiData, array $dbRow): string
    {
        foreach (['gst_rate', 'gst_percent', 'gst'] as $k) {
            if (!array_key_exists($k, $apiData)) {
                continue;
            }
            $v = $apiData[$k];
            if ($v === null || $v === '') {
                continue;
            }
            if (is_string($v)) {
                $v = preg_replace('/\s*%?\s*$/', '', trim($v));
            }
            if (is_numeric($v)) {
                $f = (float)$v;

                return $f == (int)$f ? (string)(int)$f : rtrim(rtrim(sprintf('%.4f', $f), '0'), '.');
            }
        }

        return trim((string)($dbRow['gst'] ?? ''));
    }

    /** India MRP (₹): prefer API keys, then vp_products.mrp_india. */
    private function mergeMrpRupee(array $apiData, array $dbRow): float
    {
        foreach (['mrp_india', 'mrp', 'max_retail_price', 'list_price', 'mrp_inr'] as $k) {
            if (!array_key_exists($k, $apiData)) {
                continue;
            }
            $v = $apiData[$k];
            if ($v === null || $v === '') {
                continue;
            }
            if (is_numeric($v)) {
                $f = (float)$v;
                if ($f > 0) {
                    return $f;
                }
            }
        }

        $db = isset($dbRow['mrp_india']) ? (float)$dbRow['mrp_india'] : 0.0;

        return $db > 0 ? $db : 0.0;
    }

    /** Resolved GST percent for pricing (same rules as gst_percent label). Returns 0 when unknown. */
    private function resolveGstPercentAsNumber(array $apiData, array $dbRow): float
    {
        $s = trim($this->mergeGstPercentField($apiData, $dbRow));
        if ($s === '') {
            return 0.0;
        }
        $n = (float)$s;

        return $n > 0 ? $n : 0.0;
    }

    /** Multiply unit price by (1 + GST%/100); base is treated as taxable value when GST % is present. */
    private function applyGstInclusiveToUnitPrice(float $base, array $dbRow, array $apiData = []): float
    {
        if ($base <= 0) {
            return $base;
        }
        $pct = $this->resolveGstPercentAsNumber($apiData, $dbRow);
        if ($pct <= 0) {
            return $base;
        }

        return round($base * (1 + $pct / 100), 2);
    }

    /** First positive amount from named keys on an API payload (same keys used elsewhere for catalog sync). */
    private function pickPositivePriceFromApiArray(array $data): float
    {
        foreach (['price_india', 'price_india_suggested', 'price', 'itemprice', 'finalprice'] as $k) {
            if (!array_key_exists($k, $data)) {
                continue;
            }
            $v = $data[$k];
            if ($v === null || $v === '') {
                continue;
            }
            $f = (float)$v;
            if ($f > 0) {
                return $f;
            }
        }

        return 0.0;
    }

    /** POS uses India retail when present (vp_products.price_india), else API / itemprice. */
    private function mergeSellingPrice(array $apiData, array $dbRow): float
    {
        $fromApi = $this->pickPositivePriceFromApiArray($apiData);
        if ($fromApi > 0) {
            return $fromApi;
        }
        foreach (['price_india', 'price_india_suggested', 'finalprice', 'itemprice'] as $k) {
            if (empty($dbRow[$k])) {
                continue;
            }
            $f = (float)$dbRow[$k];
            if ($f > 0) {
                return $f;
            }
        }

        return 0.0;
    }

    /**
     * Base unit (₹) for POS product modal: prefer local vp_products price_india / price_india_suggested
     * (treated as ex-GST), then fall back to mergeSellingPrice (API + DB). Upstream /product/code
     * prices are often already GST-inclusive; using them as input to applyGstInclusiveToUnitPrice
     * double-applied tax (wrong display: (base + GST) * (1 + GST%)).
     */
    private function mergePosProductSellingBaseExGst(array $apiData, array $dbRow): float
    {
        foreach (['price_india', 'price_india_suggested'] as $k) {
            if (empty($dbRow[$k])) {
                continue;
            }
            $f = (float)$dbRow[$k];
            if ($f > 0) {
                return $f;
            }
        }

        return $this->mergeSellingPrice($apiData, $dbRow);
    }

    /**
     * India retail unit price from vp_products for a cart/API product code (sku or item_code).
     */
    private function resolveIndiaSellPriceFromVp($conn, string $code): float
    {
        if ($code === '' || !$conn) {
            return 0.0;
        }
        $stmt = $conn->prepare(
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
                return $this->applyGstInclusiveToUnitPrice($f, $row, []);
            }
        }

        return 0.0;
    }

    /**
     * vp_products.gst fallback only (matches mergeGstPercentField / POS product modal).
     */
    private function fetchVpProductGstFallbackRow($conn, string $code): array
    {
        if ($code === '' || !$conn) {
            return [];
        }
        $stmt = $conn->prepare(
            'SELECT gst FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_array($row) ? $row : [];
    }

    /** First usable image path/URL from a /product/code JSON payload. */
    private function pickRawImageFromProductApiArray(array $data): string
    {
        foreach (['image', 'image_url', 'thumbnail', 'thumb', 'imageurl'] as $k) {
            if (!empty($data[$k]) && is_string($data[$k])) {
                $v = trim($data[$k]);
                if ($v !== '') {
                    return $v;
                }
            }
        }
        return '';
    }

    /**
     * All active VP product ids whose sku or item_code matches (multiple rows per style/variant).
     *
     * @return array<int, int>
     */
    private function resolveVpProductIdsForStockLookup($conn, string $code): array
    {
        if ($code === '' || !$conn) {
            return [];
        }
        $stmt = $conn->prepare(
            'SELECT id FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['id'])) {
                $ids[] = (int)$row['id'];
            }
        }
        $stmt->close();

        return $ids;
    }

    /**
     * Latest movement row for product + warehouse (same join as POS stock): qty + bin/shelf location.
     *
     * @return array{running_stock: float, location: string}
     */
    private function getWarehouseStockSnapshotForProductId($conn, int $productId, int $warehouseId): array
    {
        $empty = ['running_stock' => 0.0, 'location' => ''];
        if ($productId <= 0 || $warehouseId <= 0 || !$conn) {
            return $empty;
        }
        $sql = '
            SELECT sm.running_stock, sm.location
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE warehouse_id = ?
                GROUP BY product_id
            ) latest ON latest.product_id = sm.product_id AND latest.max_id = sm.id
            WHERE sm.warehouse_id = ? AND sm.product_id = ?
            LIMIT 1';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('iii', $warehouseId, $warehouseId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row || !array_key_exists('running_stock', $row)) {
            return $empty;
        }

        return [
            'running_stock' => (float)$row['running_stock'],
            'location' => trim((string)($row['location'] ?? '')),
        ];
    }

    /** Latest running_stock for one SKU at one warehouse (same basis as POS product grid). */
    private function getWarehouseStockForProductId($conn, int $productId, int $warehouseId): float
    {
        return $this->getWarehouseStockSnapshotForProductId($conn, $productId, $warehouseId)['running_stock'];
    }

    /** Sum of latest running_stock per warehouse for this product (all locations). */
    private function getTotalStockAcrossWarehouses($conn, int $productId): float
    {
        if ($productId <= 0 || !$conn) {
            return 0.0;
        }
        $sql = '
            SELECT COALESCE(SUM(sm.running_stock), 0) AS t
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON sm.warehouse_id = latest.warehouse_id
                AND sm.product_id = latest.product_id
                AND sm.id = latest.max_id
            WHERE sm.product_id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('ii', $productId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['t']) ? (float)$row['t'] : 0.0;
    }

    /** Default warehouse row from exotic_address (POS / GRN “default store”). */
    private function getDefaultWarehouseRow($conn): ?array
    {
        if (!$conn) {
            return null;
        }
        $stmt = $conn->prepare(
            'SELECT id, address_title FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (empty($row['id'])) {
            return null;
        }

        return [
            'id' => (int)$row['id'],
            'address_title' => trim((string)($row['address_title'] ?? '')),
        ];
    }

    /**
     * Single-line footer text from exotic_address row marked is_default (receipt / printouts).
     */
    private function getDefaultExoticAddressFooterString(mysqli $conn): string
    {
        $stmt = $conn->prepare(
            'SELECT display_name, address_title, `address` FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return '';
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return '';
        }
        $disp = trim((string)($row['display_name'] ?? ''));
        $title = trim((string)($row['address_title'] ?? ''));
        $addr = trim(preg_replace('/\s+/u', ' ', strip_tags((string)($row['address'] ?? ''))));
        $parts = [];
        if ($disp !== '') {
            $parts[] = $disp;
        }
        if ($addr !== '') {
            $parts[] = $addr;
        } elseif ($title !== '') {
            $parts[] = $title;
        }

        return trim(implode(', ', $parts));
    }

    /**
     * @return string|null Error message, or null if OK / validation skipped (no VP row / no warehouse).
     */
    private function validateQtyAgainstWarehouse($conn, string $code, int $qty): ?string
    {
        if ($qty < 1) {
            return null;
        }
        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            return null;
        }
        $ids = $this->resolveVpProductIdsForStockLookup($conn, trim($code));
        if ($ids === []) {
            return null;
        }
        $avail = 0.0;
        foreach ($ids as $pid) {
            $avail += $this->getWarehouseStockForProductId($conn, $pid, $warehouseId);
        }
        $maxAllowed = (int)floor($avail);
        if ($qty > $maxAllowed) {
            return 'Quantity cannot exceed available stock in this warehouse (' . $maxAllowed . ' available).';
        }

        return null;
    }

    /**
     * Other VP rows with the same item_code (excluding the opened variant), with warehouse stock when available.
     *
     * @return array<int, array{id:int, sku:string, title:string, stock_qty:float|int}>
     */
    private function fetchSiblingSkusByItemCode($conn, string $itemCode, string $excludeSku, int $warehouseId): array
    {
        if ($itemCode === '' || !$conn || $excludeSku === '') {
            return [];
        }

        if ($warehouseId <= 0) {
            $sql = 'SELECT id, sku, title, 0 AS stock_qty
                    FROM vp_products
                    WHERE is_active = 1 AND item_code = ? AND sku <> ?
                    ORDER BY sku ASC';
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('ss', $itemCode, $excludeSku);
            $stmt->execute();
            $res = $stmt->get_result();
            $out = [];
            while ($row = $res->fetch_assoc()) {
                $out[] = [
                    'id' => (int)($row['id'] ?? 0),
                    'sku' => (string)($row['sku'] ?? ''),
                    'title' => (string)($row['title'] ?? ''),
                    'stock_qty' => (float)($row['stock_qty'] ?? 0),
                ];
            }
            $stmt->close();

            return $out;
        }

        $sql = '
            SELECT p.id, p.sku, p.title, COALESCE(sm.running_stock, 0) AS stock_qty
            FROM vp_products p
            LEFT JOIN (
                SELECT sm1.product_id, sm1.running_stock
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ?
                    GROUP BY product_id
                ) latest ON latest.product_id = sm1.product_id AND latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm ON sm.product_id = p.id
            WHERE p.is_active = 1 AND p.item_code = ? AND p.sku <> ?
            ORDER BY p.sku ASC';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('iiss', $warehouseId, $warehouseId, $itemCode, $excludeSku);
        $stmt->execute();
        $res = $stmt->get_result();
        $out = [];
        while ($row = $res->fetch_assoc()) {
            $out[] = [
                'id' => (int)($row['id'] ?? 0),
                'sku' => (string)($row['sku'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'stock_qty' => (float)($row['stock_qty'] ?? 0),
            ];
        }
        $stmt->close();

        return $out;
    }

    public function siblingSkusAjax(): void
    {
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');

        $itemCode = isset($_GET['item_code']) ? trim((string)$_GET['item_code']) : '';
        $excludeSku = isset($_GET['exclude_sku']) ? trim((string)$_GET['exclude_sku']) : '';
        global $conn;
        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);

        echo json_encode([
            'data' => $this->fetchSiblingSkusByItemCode($conn, $itemCode, $excludeSku, $warehouseId),
        ]);
        exit;
    }

    public function getProductApi()
    {
        $code = isset($_GET['code']) ? trim((string)$_GET['code']) : '';

        if ($code === '') {
            $this->clearBufferedHttpOutput();
            header('Content-Type: application/json');
            echo json_encode(['status' => false]);
            exit;
        }

        global $conn;
        $dbItemCode = '';
        $dbSku = '';
        $dbImageRaw = '';
        $dbRow = [];
        if (!empty($conn)) {
            $stmt = $conn->prepare(
                'SELECT id, item_code, sku, title, image, material, size, color, hsn, gst,
                        price_india, price_india_suggested, itemprice, finalprice, mrp_india,
                        product_weight, product_weight_unit,
                        prod_height, prod_width, prod_length, length_unit
                 FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC LIMIT 1'
            );
            if ($stmt) {
                $stmt->bind_param('ss', $code, $code);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row) {
                    $dbRow = $row;
                    $dbItemCode = trim((string)($row['item_code'] ?? ''));
                    $dbSku = trim((string)($row['sku'] ?? ''));
                    $dbImageRaw = trim((string)($row['image'] ?? ''));
                }
            }
        }

        $res = $this->exotic_api_call('/product/code', 'GET', ['code' => $code]);

        $data = $this->unwrapProductApiResponse($res['data'] ?? []);
        $apiImageRaw = $this->pickRawImageFromProductApiArray($data);
        $imageResolved = $this->fixImageUrl($apiImageRaw);
        $imageFromDb = $this->fixImageUrl($dbImageRaw);

        if ($imageResolved === '' && $imageFromDb !== '') {
            $imageResolved = $imageFromDb;
        }

        $data2 = null;
        $sellingPrice = $this->mergePosProductSellingBaseExGst($data, $dbRow);
        $variantDiffers = ($dbItemCode !== '' && strcasecmp($dbItemCode, $code) !== 0);
        // One base item_code fetch when variant response is missing image, price, MRP, or GST (avoids 2–3 sequential /product/code calls).
        if ($variantDiffers) {
            $mrpFromVariant = $this->mergeMrpRupee($data, $dbRow);
            $gstFromVariant = $this->resolveGstPercentAsNumber($data, $dbRow);
            $needBaseFetch =
                ($imageResolved === '')
                || ($sellingPrice <= 0)
                || ($mrpFromVariant <= 0)
                || ($gstFromVariant <= 0);
            if ($needBaseFetch) {
                $res2 = $this->exotic_api_call('/product/code', 'GET', ['code' => $dbItemCode]);
                $data2 = $this->unwrapProductApiResponse($res2['data'] ?? []);
                if ($imageResolved === '') {
                    $imgBase = $this->fixImageUrl($this->pickRawImageFromProductApiArray($data2));
                    if ($imgBase !== '') {
                        $imageResolved = $imgBase;
                    }
                }
                if ($sellingPrice <= 0) {
                    $altSell = $this->mergePosProductSellingBaseExGst($data2, $dbRow);
                    if ($altSell > 0) {
                        $sellingPrice = $altSell;
                    }
                }
            }
        }

        if ($imageResolved === '' && $imageFromDb !== '') {
            $imageResolved = $imageFromDb;
        }

        $gstApiForSell = $data;
        if ($this->resolveGstPercentAsNumber($data, $dbRow) <= 0 && $data2 !== null) {
            $gstApiForSell = $data2;
        }
        $sellingPrice = $this->applyGstInclusiveToUnitPrice($sellingPrice, $dbRow, $gstApiForSell);

        $dimApi = $this->cleanValue($data['dimensions'] ?? '');
        $dbH = isset($dbRow['prod_height']) ? trim((string)$dbRow['prod_height']) : '';
        $dbW = isset($dbRow['prod_width']) ? trim((string)$dbRow['prod_width']) : '';
        $dbL = isset($dbRow['prod_length']) ? trim((string)$dbRow['prod_length']) : '';
        $dbLu = isset($dbRow['length_unit']) ? trim((string)$dbRow['length_unit']) : '';
        $builtDbDims = '';
        if ($dbH !== '' || $dbW !== '' || $dbL !== '') {
            $builtDbDims = implode(' × ', array_filter([$dbH, $dbW, $dbL], static function ($x) {
                return trim((string)$x) !== '';
            }));
            if ($builtDbDims !== '' && $dbLu !== '') {
                $builtDbDims .= ' ' . $dbLu;
            }
        }
        $dimensionsMerged = $dimApi !== '' ? $dimApi : $builtDbDims;

        $wKgApi = trim((string)($data['product_weight_kg'] ?? ''));
        $dbPw = isset($dbRow['product_weight']) ? trim((string)$dbRow['product_weight']) : '';
        $dbPwu = isset($dbRow['product_weight_unit']) ? trim((string)$dbRow['product_weight_unit']) : '';

        $warehouseId = (int)($_SESSION['warehouse_id'] ?? 0);
        $currentWarehouseName = '';
        if ($warehouseId > 0 && !empty($conn)) {
            require_once 'models/user/user.php';
            $usersModel = new User($conn);
            $whRow = $usersModel->getWarehouseById($warehouseId);
            if (!empty($whRow)) {
                $currentWarehouseName = trim((string)($whRow['address_title'] ?? ''));
            }
        }

        $vpId = isset($dbRow['id']) ? (int)$dbRow['id'] : 0;
        $warehouseLocationOut = '';
        if ($vpId > 0 && $warehouseId > 0) {
            $snap = $this->getWarehouseStockSnapshotForProductId($conn, $vpId, $warehouseId);
            $stockQtyOut = $snap['running_stock'];
            $warehouseLocationOut = $snap['location'];
        } else {
            $stockQtyOut = $data['stock'] ?? 0;
        }

        $siblingSkus = [];
        if ($dbItemCode !== '') {
            $siblingSkus = $this->fetchSiblingSkusByItemCode($conn, $dbItemCode, trim($code), $warehouseId);
        }

        $totalQtyAllWarehouses = null;
        $defaultStoreQty = null;
        $defaultStoreName = '';
        if ($vpId > 0) {
            $totalQtyAllWarehouses = $this->getTotalStockAcrossWarehouses($conn, $vpId);
            $defWh = $this->getDefaultWarehouseRow($conn);
            if ($defWh !== null) {
                $defaultStoreName = $defWh['address_title'];
                $defaultStoreQty = $this->getWarehouseStockSnapshotForProductId($conn, $vpId, (int)$defWh['id'])['running_stock'];
            }
        }

        $mrpOut = $this->mergeMrpRupee($data, $dbRow);
        if ($mrpOut <= 0 && $data2 !== null) {
            $altMrp = $this->mergeMrpRupee($data2, $dbRow);
            if ($altMrp > 0) {
                $mrpOut = $altMrp;
            }
        }

        // echo '<pre>'; print_r($data['addon_options']); exit;
        $product = [
            'requested_code' => $code,
            'item_code' => $dbItemCode,
            'sku' => $dbSku,
            'title' => $this->mergeProductTextField($data['name'] ?? '', $dbRow['title'] ?? ''),
            'image' => $imageResolved,
            'price' => $sellingPrice,

            'material' => $this->mergeProductTextField($data['material'] ?? '', $dbRow['material'] ?? ''),
            'size' => $this->mergeProductTextField($data['size'] ?? '', $dbRow['size'] ?? ''),
            'color' => $this->mergeProductTextField($data['color'] ?? '', $dbRow['color'] ?? ''),
            'hsn' => $this->mergeProductTextField($data['hsn'] ?? '', $dbRow['hsn'] ?? ''),
            'gst_percent' => $this->mergeGstPercentField($data, $dbRow),
            'mrp' => $mrpOut,

            //  NEW FIELDS (warehouse running stock when VP row + session warehouse match POS grid)
            'stock_qty' => $stockQtyOut,
            'warehouse_location' => $warehouseLocationOut,
            'total_qty_available' => $totalQtyAllWarehouses,
            'current_warehouse_name' => $currentWarehouseName,
            'default_store_qty' => $defaultStoreQty,
            'default_store_name' => $defaultStoreName,
            'maincategory' => $data['maincategory'] ?? '',
            'dimensions' => $dimensionsMerged,
            'weight' => $wKgApi,
            'product_weight' => $dbPw,
            'product_weight_unit' => $dbPwu,
            'prod_height' => $dbH,
            'prod_width' => $dbW,
            'prod_length' => $dbL,
            'length_unit' => $dbLu,
            'express_shipping_cost' => $data['express_shipping_cost'] ?? 0,
            'express_shipping_option' => $data['express_shipping_option'] ?? null,
            'addon_options' => $data['addon_options'] ?? [],
            'sibling_skus' => $siblingSkus,
        ];

        $this->clearBufferedHttpOutput();

        header('Content-Type: application/json');

        echo json_encode(['data' => $product]);
        exit;
    }

    /**
     * Check product stock in current warehouse and alternatives.
     * Accepts product_id (preferred) or item_code/sku via `q`.
     */
    public function productAvailability()
    {
        global $conn;
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json');

        $productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $currentWarehouseId = (int)($_SESSION['warehouse_id'] ?? 0);

        if ($productId <= 0 && $q === '') {
            echo json_encode(['success' => false, 'message' => 'Missing product identifier.']);
            exit;
        }

        if ($productId <= 0) {
            $sql = "SELECT id, item_code, sku, title
                    FROM vp_products
                    WHERE is_active = 1 AND (sku = ? OR item_code = ?)
                    ORDER BY id ASC
                    LIMIT 1";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Could not prepare product query.']);
                exit;
            }
            $stmt->bind_param('ss', $q, $q);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }
            $productId = (int)$product['id'];
        } else {
            $stmt = $conn->prepare("SELECT id, item_code, sku, title FROM vp_products WHERE id = ? LIMIT 1");
            if (!$stmt) {
                echo json_encode(['success' => false, 'message' => 'Could not prepare product query.']);
                exit;
            }
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$product) {
                echo json_encode(['success' => false, 'message' => 'Product not found.']);
                exit;
            }
        }

        $stockSql = "
            SELECT sm.warehouse_id,
                   COALESCE(ea.address_title, CONCAT('Warehouse #', sm.warehouse_id)) AS warehouse_name,
                   sm.running_stock AS stock_qty
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON latest.max_id = sm.id
            LEFT JOIN exotic_address ea ON ea.id = sm.warehouse_id
            WHERE sm.product_id = ?
            ORDER BY warehouse_name ASC";

        $stockStmt = $conn->prepare($stockSql);
        if (!$stockStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not prepare stock query.']);
            exit;
        }
        $stockStmt->bind_param('ii', $productId, $productId);
        $stockStmt->execute();
        $rows = $stockStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stockStmt->close();

        $currentWarehouse = null;
        $alternativeWarehouses = [];
        foreach ($rows as $row) {
            $wid = (int)($row['warehouse_id'] ?? 0);
            $stockQty = (float)($row['stock_qty'] ?? 0);
            $entry = [
                'warehouse_id' => $wid,
                'warehouse_name' => (string)($row['warehouse_name'] ?? ''),
                'stock_qty' => $stockQty,
            ];
            if ($wid === $currentWarehouseId) {
                $currentWarehouse = $entry;
            } elseif ($stockQty > 0) {
                $alternativeWarehouses[] = $entry;
            }
        }

        if ($currentWarehouse === null) {
            $currentWarehouse = [
                'warehouse_id' => $currentWarehouseId,
                'warehouse_name' => 'Current Store',
                'stock_qty' => 0,
            ];
        }

        $currentAvailable = ((float)$currentWarehouse['stock_qty']) > 0;
        $message = '';
        if (!$currentAvailable && !empty($alternativeWarehouses)) {
            $altNames = array_values(array_filter(array_map(static function ($w) {
                return trim((string)($w['warehouse_name'] ?? ''));
            }, $alternativeWarehouses)));
            $message = 'Product not available in this store, but you still can create an order from another store (' . implode(', ', $altNames) . ')';
        }

        echo json_encode([
            'success' => true,
            'product' => [
                'id' => (int)$product['id'],
                'item_code' => (string)($product['item_code'] ?? ''),
                'sku' => (string)($product['sku'] ?? ''),
                'title' => (string)($product['title'] ?? ''),
            ],
            'current_warehouse' => $currentWarehouse,
            'current_available' => $currentAvailable,
            'alternative_warehouses' => $alternativeWarehouses,
            'message' => $message,
        ]);
        exit;
    }
    /**
     * Same-origin JSON proxy for Exotic retail cart endpoints (browser cannot send x-api-* headers / CORS).
     * Forwards to https://www.exoticindia.com/api via exotic_api_call().
     */
    public function cartApi(): void
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $op = trim((string)($_REQUEST['op'] ?? ''));
        switch ($op) {
            case 'retrieve':
                // Same discount / gift query + header as add/modifyqty so cart totals reflect applied coupon.
                $ctx = $this->exoticCartDiscountContext();
                $this->emitCartApiResponse($this->exotic_api_call(
                    '/cart/retrieve',
                    'GET',
                    $ctx['query'],
                    null,
                    null,
                    $ctx['extraHeaders']
                ));
                return;

            case 'add':
                if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
                    echo json_encode(['success' => false, 'message' => 'POST required'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                    exit;
                }
                $raw = (string)file_get_contents('php://input');
                $body = json_decode($raw, true);
                if (!is_array($body)) {
                    $body = $_POST;
                }
                $split = $this->buildExoticCartAddSplit(is_array($body) ? $body : []);
                $ctxAdd = $this->exoticCartDiscountContext();
                $this->emitCartApiResponse($this->exotic_api_call(
                    '/cart/add',
                    'POST',
                    $split['query'],
                    $split['post'],
                    null,
                    $ctxAdd['extraHeaders']
                ));
                return;

            case 'modifyqty':
                // GET /api/cart/modifyqty?cartid=&newqty=&variationqtylist=&discountcoupondetails=
                // (cart line value is cartref in retrieve JSON; query param name is cartid.)
                $cartid = $this->resolveCartLineIdFromRequest();
                $newqty = trim((string)($_GET['newqty'] ?? $_REQUEST['newqty'] ?? $_GET['qty'] ?? $_REQUEST['qty'] ?? ''));
                $variationqtylist = trim((string)($_GET['variationqtylist'] ?? $_REQUEST['variationqtylist'] ?? ''));
                $discountStr = $this->getSessionDiscountCouponDetailsString();
                $query = [
                    'cartid' => $cartid,
                    'newqty' => $newqty,
                ];
                if ($variationqtylist !== '') {
                    $query['variationqtylist'] = $variationqtylist;
                }
                if ($discountStr !== '') {
                    $query['discountcoupondetails'] = $discountStr;
                }
                $extraHeaders = $discountStr !== ''
                    ? ['x-api-discountcoupondetails:' . $discountStr]
                    : [];
                $this->emitCartApiResponse($this->exotic_api_call('/cart/modifyqty', 'GET', $query, null, null, $extraHeaders));
                return;

            case 'delete':
                $cartid = $this->resolveCartLineIdFromRequest();
                $this->emitCartApiResponse($this->exotic_api_call('/cart/delete', 'GET', [
                    'cartid' => $cartid,
                ]));
                return;

            case 'addcoupon':
                $couponId = trim((string)($_GET['couponid'] ?? $_REQUEST['couponid'] ?? ''));
                $res = $this->exotic_api_call('/cart/addcoupon', 'GET', [
                    'couponid' => $couponId,
                ]);
                if ($this->isExoticCartSuccess($res)) {
                    $data = is_array($res['data'] ?? null) ? $res['data'] : [];
                    $details = $data['discountcoupondetails'] ?? $data['discount_coupon_details'] ?? null;
                    if ($details !== null && $details !== '') {
                        if (is_array($details)) {
                            $_SESSION['pos_exotic_cart_coupon_details'] = json_encode($details, JSON_UNESCAPED_UNICODE);
                            $_SESSION['discount_coupon'] = ['discountcoupondetails' => $details];
                        } else {
                            $_SESSION['pos_exotic_cart_coupon_details'] = (string)$details;
                            $_SESSION['discount_coupon'] = ['discountcoupondetails' => (string)$details];
                        }
                    } elseif ($couponId !== '') {
                        // API may succeed without echoing details; still attach code so retrieve/add/modifyqty send context.
                        $_SESSION['pos_exotic_cart_coupon_details'] = $couponId;
                        $_SESSION['discount_coupon'] = ['discountcoupondetails' => $couponId];
                    }
                }
                $this->emitCartApiResponse($res);
                return;

            case 'removecoupon':
                unset($_SESSION['pos_exotic_cart_coupon_details'], $_SESSION['discount_coupon']);
                $remoteRm = $this->exotic_api_call('/cart/removecoupon', 'GET', []);
                if ($this->isExoticCartSuccess($remoteRm)) {
                    $this->emitCartApiResponse($remoteRm);

                    return;
                }
                // Session is cleared so add/modify no longer sends discountcoupondetails; client still refreshes retrieve.
                echo json_encode([
                    'success' => true,
                    'http_code' => 200,
                    'data' => array_merge(
                        is_array($remoteRm['data'] ?? null) ? $remoteRm['data'] : [],
                        ['message' => 'Coupon cleared for this terminal.']
                    ),
                    'raw' => (string)($remoteRm['raw'] ?? ''),
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;

            case 'customdiscount':
                $this->emitCartApiResponse($this->exotic_api_call('/cart/addcustomdiscount', 'GET', [
                    'custom_reduce' => (string)($_GET['custom_reduce'] ?? $_REQUEST['custom_reduce'] ?? '0'),
                ]));
                return;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown cart op'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
        }
    }

    /**
     * Build Exotic POST /api/cart/add request: query params per API collection, form body buynow/code/qty/variation/options.
     *
     * @param array<string, mixed> $body Decoded JSON from POS (code, qty, variation, options, optional buynow 0|1).
     * @return array{query: array<string, string>, post: array<string, string>}
     */
    private function buildExoticCartAddSplit(array $body): array
    {
        $code = trim((string)($body['code'] ?? ''));
        $qty = (int)($body['qty'] ?? 1);
        if ($qty < 1) {
            $qty = 1;
        }

        // Required: 0 = add to cart, 1 = buy now (https://www.exoticindia.com/api/cart/add)
        $buynow = '0';
        if (array_key_exists('buynow', $body)) {
            $bn = (int)$body['buynow'];
            $buynow = $bn === 1 ? '1' : '0';
        }

        $post = [
            'buynow' => $buynow,
            'code' => $code,
            'qty' => (string)$qty,
        ];

        $variation = isset($body['variation']) ? trim((string)$body['variation']) : '';
        if ($variation !== '') {
            $post['variation'] = $variation;
        }
        if (isset($body['options']) && trim((string)$body['options']) !== '') {
            $post['options'] = (string)$body['options'];
        }

        $stockCheck = isset($body['stock_check_code']) ? trim((string)$body['stock_check_code']) : '';
        if ($stockCheck !== '') {
            $post['stock_check_code'] = $stockCheck;
        }

        $ctx = $this->exoticCartDiscountContext();

        return ['query' => $ctx['query'], 'post' => $post];
    }

    /**
     * Query params + optional x-api-discountcoupondetails header for Exotic cart GETs that must reflect coupons.
     * Omit empty discount/gift params — sending e.g. discountcoupondetails= with no value can break /cart/add upstream.
     *
     * @return array{query: array<string, string>, extraHeaders: list<string>}
     */
    private function exoticCartDiscountContext(): array
    {
        $discountStr = trim($this->getSessionDiscountCouponDetailsString());
        $giftStr = '';
        if (!empty($_SESSION['pos_exotic_cart_gift_voucher'])) {
            $gv = $_SESSION['pos_exotic_cart_gift_voucher'];
            $giftStr = is_string($gv) ? trim($gv) : trim(json_encode($gv, JSON_UNESCAPED_UNICODE));
        } elseif (!empty($_SESSION['gift_voucher'])) {
            $gv = $_SESSION['gift_voucher'];
            $giftStr = is_string($gv) ? trim($gv) : trim(json_encode($gv, JSON_UNESCAPED_UNICODE));
        }
        $query = [];
        if ($discountStr !== '') {
            $query['discountcoupondetails'] = $discountStr;
        }
        if ($giftStr !== '') {
            $query['giftvoucherdetails'] = $giftStr;
        }
        $extraHeaders = [];
        if ($discountStr !== '') {
            $extraHeaders[] = 'x-api-discountcoupondetails:' . $discountStr;
        }

        return ['query' => $query, 'extraHeaders' => $extraHeaders];
    }

    private function getSessionDiscountCouponDetailsString(): string
    {
        if (!empty($_SESSION['pos_exotic_cart_coupon_details'])) {
            $dcd = $_SESSION['pos_exotic_cart_coupon_details'];

            return is_string($dcd) ? $dcd : json_encode($dcd, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($_SESSION['discount_coupon']['discountcoupondetails'])) {
            $v = $_SESSION['discount_coupon']['discountcoupondetails'];

            return is_string($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        return '';
    }

    /**
     * Cart line id from POS (supports cartid / legacy cartref naming).
     */
    private function resolveCartLineIdFromRequest(): string
    {
        foreach (['cartid', 'cart_id', 'cartref'] as $key) {
            $v = $_GET[$key] ?? $_REQUEST[$key] ?? null;
            $t = trim((string)($v ?? ''));
            if ($t !== '') {
                return $t;
            }
        }

        return '';
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     */
    private function isExoticCartSuccess(array $res): bool
    {
        $c = (int)($res['code'] ?? 0);
        if ($c < 200 || $c >= 300) {
            return false;
        }
        $d = $res['data'] ?? [];
        if (!is_array($d)) {
            return true;
        }
        if (array_key_exists('success', $d)) {
            $sv = $d['success'];
            if ($sv === false || $sv === 0 || $sv === '0' || $sv === 'false' || $sv === 'False') {
                return false;
            }
        }
        if (isset($d['status'])) {
            $st = strtolower((string)$d['status']);
            if (in_array($st, ['error', 'fail', 'failed'], true)) {
                return false;
            }
        }
        if (isset($d['error']) && (is_string($d['error']) ? trim($d['error']) !== '' : $d['error'] === true)) {
            return false;
        }

        return true;
    }

    /**
     * Best-effort user-facing message from Exotic cart JSON (shape varies by endpoint).
     *
     * @param array{data?: mixed, raw?: string} $res
     */
    private function extractExoticCartUserMessage(array $res): string
    {
        $d = $res['data'] ?? null;
        if (is_array($d)) {
            foreach (['message', 'Message', 'error', 'Error', 'errormessage', 'msg'] as $k) {
                if (!empty($d[$k]) && is_string($d[$k])) {
                    $t = trim($d[$k]);
                    if ($t !== '') {
                        return $t;
                    }
                }
            }
            foreach (['data', 'result', 'payload'] as $wrap) {
                if (!empty($d[$wrap]) && is_array($d[$wrap])) {
                    $inner = $d[$wrap];
                    foreach (['message', 'error', 'Message', 'errormessage'] as $k) {
                        if (!empty($inner[$k]) && is_string($inner[$k])) {
                            $t = trim($inner[$k]);
                            if ($t !== '') {
                                return $t;
                            }
                        }
                    }
                }
            }
        }
        $raw = trim((string)($res['raw'] ?? ''));
        if ($raw !== '' && strlen($raw) < 400 && strpos($raw, '<') === false) {
            return $raw;
        }

        return '';
    }

    /**
     * @param array{data?: mixed, code?: int, raw?: string} $res
     */
    private function emitCartApiResponse(array $res): void
    {
        $raw = (string)($res['raw'] ?? '');
        if (strlen($raw) > 65536) {
            $raw = substr($raw, 0, 65536) . '…(truncated)';
        }
        $ok = $this->isExoticCartSuccess($res);
        $msg = $this->extractExoticCartUserMessage($res);
        if (!$ok && $msg === '') {
            $msg = 'Cart request failed (HTTP ' . (int)($res['code'] ?? 0) . ').';
        }
        echo json_encode([
            'success' => $ok,
            'message' => $msg,
            'http_code' => (int)($res['code'] ?? 0),
            'data' => $res['data'] ?? [],
            'raw' => $raw,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /**
     * @param list<string> $extraHttpHeaders Additional request headers (full "Name:value" lines).
     */
    public function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null, ?string $apiBaseUrl = null, array $extraHttpHeaders = [])
    {
        // echo "<pre>";
        // print_r($_SESSION['discount_coupon']['discountcoupondetails']);
        // exit;

        $ep = '/' . ltrim((string)$endpoint, '/');
        if (strtoupper((string)$method) === 'POST' && rtrim($ep, '/') === '/order/create'
                && is_file(dirname(__DIR__) . '/.pos_skip_exotic_order_create_api')) {
            $d = ['orderid' => 'LOCAL-' . gmdate('YmdHis')];
            $j = json_encode($d);
            return ['data' => $d, 'code' => 200, 'raw' => $j];
        }


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
        $deviceId = $this->resolveApiDeviceId();
        $headers = [
            'x-api-key: aeRGoUvQLCxztK0Wzxmv9O2VRJ2H1B44',
            'x-api-deviceid: ' . $deviceId,
            'x-api-appplayerid: POS-Web-Terminal',
            'x-api-countrycode: IN',
            // Keep API-issued euid in session; do not use local user id here.
            'x-api-euid:' . (string)($_SESSION['x_api_euid'] ?? ''),
            'User-Agent: ExoticPOS'
        ];
        // Forward optional evolving API session headers when available.
        if (!empty($_SESSION['x_api_jwt'])) {
            $headers[] = 'x-api-jwt:' . (string)$_SESSION['x_api_jwt'];
        }
        if (!empty($_SESSION['x_api_browsehistory'])) {
            $headers[] = 'x-api-browsehistory:' . (string)$_SESSION['x_api_browsehistory'];
        }
        if (!empty($_SESSION['x_api_etd'])) {
            $headers[] = 'x-api-etd:' . (string)$_SESSION['x_api_etd'];
        }
        if (!empty($_SESSION['x_api_etd_pincode'])) {
            $headers[] = 'x-api-etd-pincode:' . (string)$_SESSION['x_api_etd_pincode'];
        }
        foreach ($extraHttpHeaders as $line) {
            if (is_string($line) && $line !== '') {
                $headers[] = $line;
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $capturedHeaders = [];
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$capturedHeaders) {
            $len = strlen($headerLine);
            $header = explode(':', $headerLine, 2);
            if (count($header) < 2) {
                return $len;
            }
            $name = strtolower(trim($header[0]));
            if (in_array($name, ['x-api-euid', 'x-api-jwt', 'x-api-browsehistory', 'x-api-etd', 'x-api-etd-pincode'], true)) {
                $capturedHeaders[$name] = trim($header[1]);
            }
            return $len;
        });

        if ($method === 'POST' && $postData !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POST, true);
            if (is_array($postData)) {
                $encodedPostData = http_build_query($postData);
            } elseif (is_string($postData)) {
                $encodedPostData = $postData;
            } else {
                $encodedPostData = (string)$postData;
            }
            curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPostData);
        }

        $response = curl_exec($ch);
        //   echo '<pre>';
        // print_r($response);
        // exit;
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if (!empty($capturedHeaders['x-api-euid'])) {
            $_SESSION['x_api_euid'] = $capturedHeaders['x-api-euid'];
        }
        if (!empty($capturedHeaders['x-api-jwt'])) {
            $_SESSION['x_api_jwt'] = $capturedHeaders['x-api-jwt'];
        }
        if (!empty($capturedHeaders['x-api-browsehistory'])) {
            $_SESSION['x_api_browsehistory'] = $capturedHeaders['x-api-browsehistory'];
        }
        if (!empty($capturedHeaders['x-api-etd'])) {
            $_SESSION['x_api_etd'] = $capturedHeaders['x-api-etd'];
        }
        if (!empty($capturedHeaders['x-api-etd-pincode'])) {
            $_SESSION['x_api_etd_pincode'] = $capturedHeaders['x-api-etd-pincode'];
        }

        $body = (string)$response;
        $decoded = json_decode($body, true);
        $data = (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) ? $decoded : [];

        return [
            'data' => $data,
            'code' => $httpCode,
            'raw' => $body,
        ];
    }

    /**
     * Use real store/warehouse label for x-api-deviceid.
     * Falls back to POS-Store_<warehouse_id> / POS-Store_1.
     */
    private function resolveApiDeviceId(): string
    {
        $fallbackId = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($fallbackId <= 0) {
            $fallbackId = 1;
        }
        $fallback = 'POS-Store_' . $fallbackId;

        if (empty($_SESSION['warehouse_id'])) {
            return $fallback;
        }

        global $conn;
        if (empty($conn)) {
            return $fallback;
        }

        try {
            $usersModel = new User($conn);
            $warehouse = $usersModel->getWarehouseById((int)$_SESSION['warehouse_id']);
            $name = trim((string)($warehouse['address_title'] ?? ''));
            if ($name === '') {
                return $fallback;
            }
            // Header-safe normalization.
            $name = preg_replace('/\s+/', '_', $name);
            $name = preg_replace('/[^A-Za-z0-9_\-]/', '', (string)$name);
            return $name !== '' ? $name : $fallback;
        } catch (\Throwable $e) {
            return $fallback;
        }
    }
    public function add_customer()
    {
        global $conn;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        require_once 'models/customer/Customer.php';
        $customerModel = new Customer($conn);

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
        if (!$stmt) {
            echo json_encode([
                "success" => false,
                "message" => "Database error (prepare): " . $conn->error
            ]);
            exit;
        }
        $stmt->bind_param("sss", $name, $email, $phone);
        try {
            $executed = $stmt->execute();
        } catch (\mysqli_sql_exception $e) {
            $stmt->close();
            $dup = str_contains($e->getMessage(), 'Duplicate entry')
                || str_contains($e->getMessage(), 'unique_email_phone')
                || $e->getSqlState() === '23000';
            if ($dup) {
                $lookup = $conn->prepare(
                    'SELECT id, name, email, phone FROM vp_customers WHERE email = ? AND phone = ? LIMIT 1'
                );
                if ($lookup) {
                    $lookup->bind_param('ss', $email, $phone);
                    $lookup->execute();
                    $existing = $lookup->get_result()->fetch_assoc();
                    $lookup->close();
                    if (!empty($existing['id'])) {
                        $id = (int)$existing['id'];
                        $_SESSION['pos_customer_id'] = $id;
                        $_SESSION['pos_customer_form'] = $_POST;
                        $customerModel->upsertPosCustomerDetailsFromPost($id, $_POST);
                        echo json_encode([
                            'success' => true,
                            'message' => 'This email and phone are already registered; using existing customer.',
                            'customer' => [
                                'id' => $id,
                                'name' => $existing['name'] ?? $name,
                                'phone' => $existing['phone'] ?? $phone,
                                'email' => $existing['email'] ?? $email,
                            ],
                        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                        exit;
                    }
                }
                echo json_encode([
                    'success' => false,
                    'message' => 'A customer with this email and phone already exists.',
                ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            echo json_encode([
                'success' => false,
                'message' => 'Could not save customer: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if (!$executed) {
            echo json_encode([
                "success" => false,
                "message" => "Could not save customer: " . $stmt->error
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            $stmt->close();
            exit;
        }

        $id = (int)$stmt->insert_id;
        $stmt->close();

        if ($id <= 0) {
            echo json_encode([
                "success" => false,
                "message" => "Customer was not created (no insert id)."
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        /*  STORE FULL BILLING + SHIPPING IN SESSION */
        $_SESSION['pos_customer_id'] = $id;
        $_SESSION['pos_customer_form'] = $_POST;
        $customerModel->upsertPosCustomerDetailsFromPost($id, $_POST);

        echo json_encode([
            "success" => true,
            "customer" => [
                "id" => $id,
                "name" => $name,
                "phone" => $phone,
                "email" => $email
            ]
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);

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

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["success" => true]);
        exit;
    }

    /**
     * POST JSON: address confirm fields + payment_* + customer_id + order_total (from live cart).
     * Proxies Exotic POST /order/create, then inserts pos_payments with receipt number.
     */
    public function checkout_create(): void
    {
        is_login();
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;

        require_once dirname(__DIR__) . '/helpers/pos_payment_receipt.php';

        $raw = (string)file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            echo json_encode(['success' => false, 'message' => 'Invalid JSON body'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $customerId = (int)($payload['customer_id'] ?? 0);
        $sessionCid = (int)($_SESSION['pos_customer_id'] ?? 0);
        if ($customerId <= 0 || $sessionCid <= 0 || $customerId !== $sessionCid) {
            echo json_encode(['success' => false, 'message' => 'Select a customer before checkout.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $orderTotal = round((float)($payload['order_total'] ?? 0), 2);
        if ($orderTotal <= 0) {
            echo json_encode(['success' => false, 'message' => 'Cart total is missing or zero. Refresh the cart and try again.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $paymentAmount = round((float)($payload['payment_amount'] ?? 0), 2);
        $paymentStage = strtolower(trim((string)($payload['payment_stage'] ?? 'final')));
        if ($paymentAmount <= 0) {
            echo json_encode(['success' => false, 'message' => 'Payment amount must be greater than zero.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        if ($paymentStage === 'final') {
            if ($paymentAmount + 0.02 < $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Final payment must match order total ₹ ' . $orderTotal], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
            if ($paymentAmount - 0.02 > $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Over payment is not allowed for final settlement.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        } elseif ($paymentStage === 'partial' || $paymentStage === 'advance') {
            if ($paymentAmount + 0.02 >= $orderTotal) {
                echo json_encode(['success' => false, 'message' => 'Partial / advance must be less than order total ₹ ' . $orderTotal], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }

        $paymentMode = trim((string)($payload['payment_mode'] ?? 'cash'));
        $txn = trim((string)($payload['transaction_id'] ?? ''));
        if ($paymentMode === 'razorpay' && $txn === '') {
            echo json_encode(['success' => false, 'message' => 'Razorpay requires a transaction ID.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $ctx = $this->exoticCartDiscountContext();
        $retrieve = $this->exotic_api_call(
            '/cart/retrieve',
            'GET',
            $ctx['query'],
            null,
            null,
            $ctx['extraHeaders']
        );
        if (!$this->isExoticCartSuccess($retrieve)) {
            echo json_encode([
                'success' => false,
                'message' => 'Could not load cart before checkout. Try refreshing the cart.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $cartData = is_array($retrieve['data'] ?? null) ? $retrieve['data'] : [];
        $items = $cartData['cartitems'] ?? $cartData['cart_items'] ?? $cartData['items'] ?? $cartData['lines'] ?? [];
        if (!is_array($items) || count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'Cart is empty.'], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $postBody = $this->buildOrderCreatePostFromPayload($payload, $cartData);
        if (trim((string)($postBody['checkoutdata'] ?? '')) === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Cart response did not include checkoutdata (required by Exotic order/create). Refresh the cart or add items again.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }
        $createRes = $this->exotic_api_call(
            '/order/create',
            'POST',
            $ctx['query'],
            $postBody,
            null,
            $ctx['extraHeaders']
        );

        $requestBodyDebug = $this->summarizeOrderCreateDebugBody($postBody);
        $_SESSION['pos_order_create_api_debug'] = [
            'at' => gmdate('c'),
            // Backward-compatible fields used by existing UI.
            'http_code' => (int)($createRes['code'] ?? 0),
            'data' => $createRes['data'] ?? [],
            'raw_snippet' => substr((string)($createRes['raw'] ?? ''), 0, 12000),
            // Rich request/response blocks for checkout popup debug.
            'request' => [
                'endpoint' => '/order/create',
                'method' => 'POST',
                'query' => $ctx['query'] ?? [],
                'body' => $requestBodyDebug,
            ],
            'response' => [
                'http_code' => (int)($createRes['code'] ?? 0),
                'data' => $createRes['data'] ?? [],
                'raw_snippet' => substr((string)($createRes['raw'] ?? ''), 0, 12000),
            ],
        ];

        if (!$this->isExoticCartSuccess($createRes)) {
            $d = is_array($createRes['data'] ?? null) ? $createRes['data'] : [];
            $msg = trim((string)($d['message'] ?? $d['error'] ?? $d['errormessage'] ?? ''));
            if ($msg === '') {
                $msg = 'Order create failed (HTTP ' . (int)($createRes['code'] ?? 0) . ').';
            }
            echo json_encode([
                'success' => false,
                'message' => $msg,
                'order_create_debug' => $_SESSION['pos_order_create_api_debug'],
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $orderNumber = $this->extractExoticOrderNumberFromCreateResponse(is_array($createRes['data'] ?? null) ? $createRes['data'] : []);
        if ($orderNumber === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Order was created but no order number was returned. Check Last order-create API in the payment modal.',
                'order_create_debug' => $_SESSION['pos_order_create_api_debug'],
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $short = pos_payment_resolve_short_code_for_warehouse($conn, (int)($_SESSION['warehouse_id'] ?? 0));
        try {
            $receiptNo = pos_payment_generate_next_receipt_number($conn, $short);
        } catch (\Throwable $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Receipt number error: ' . $e->getMessage(),
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $note = trim((string)($payload['payment_note'] ?? ''));
        $userId = pos_payment_resolve_session_user_id();
        $whId = (int)($_SESSION['warehouse_id'] ?? 0);

        $pay = pos_payment_insert_row(
            $conn,
            $orderNumber,
            $receiptNo,
            $customerId,
            $paymentStage,
            $paymentMode,
            $paymentAmount,
            $txn,
            $note,
            $userId,
            $whId,
            true,
            $orderTotal
        );

        if (empty($pay['success'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Exotic order ' . $orderNumber . ' was created but local payment row failed: ' . (string)($pay['error'] ?? 'unknown'),
                'order_number' => $orderNumber,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        require_once 'models/customer/Customer.php';
        $posDetailsModel = new Customer($conn);
        $posDetailsModel->upsertPosCustomerDetailsFromConfirmPayload($customerId, $payload);

        $modeLabel = $this->mapPosPaymentModeLabel($paymentMode);
        $dt = new \DateTime('now', new \DateTimeZone('Asia/Kolkata'));

        $_SESSION['pos_last_checkout_receipt'] = [
            'receipt_number' => $receiptNo,
            'receipt_date_formatted' => $dt->format('d M Y, h:i A'),
            'order_id' => $orderNumber,
            'payment_stage' => $paymentStage,
            'payment_mode_label' => $modeLabel,
            'transaction_id' => $txn,
            'receipt_banner_text' => 'Thank you. Payment of ₹ ' . number_format($paymentAmount, 2, '.', ',') . ' recorded for order ' . $orderNumber . '.',
            'receipt_billing_block' => $this->formatAddressLinesFromPayload($payload, 'billing'),
            'receipt_shipping_block' => $this->formatAddressLinesFromPayload($payload, 'shipping'),
            'receipt_lines' => [],
            'receipt_subtotal_goods' => 0.0,
            'receipt_gst_total' => 0.0,
            'receipt_coupon_discount' => 0.0,
            'receipt_gift_discount' => 0.0,
            'receipt_cash_discount' => 0.0,
            'receipt_grand_total' => $orderTotal,
            'receipt_qty_total' => 0.0,
            'receipt_agg_sgst' => 0.0,
            'receipt_agg_cgst' => 0.0,
            'receipt_agg_igst' => 0.0,
            'receipt_amount_in_words' => '',
            'receipt_amount_received' => $paymentAmount,
            'receipt_pending_amount' => (float)($pay['pending_amount'] ?? 0),
            'import_status' => '',
            'show_invoice_pdf_button' => false,
            'invoice_pdf_url' => '',
            'invoice_pdf_disabled_hint' => 'Import the order into vp_orders to generate a tax invoice.',
            'receipt_company_legal_name' => 'EXOTIC INDIA ART PVT LTD',
            'receipt_company_tagline' => '',
            'receipt_company_gstin' => '',
            'receipt_company_pan' => '',
            'receipt_title_main' => 'PAYMENT RECEIPT',
            'receipt_place_of_supply' => '',
            'receipt_terms' => [
                'Goods once sold will not be taken back.',
                'Subject to jurisdiction of competent courts at New Delhi.',
            ],
            'receipt_office_footer' => '',
            'receipt_signature_date' => $dt->format('d M Y'),
            'payment_history_url' => 'index.php?page=payments&order_number=' . rawurlencode($orderNumber) . '&order_exact=1',
        ];

        echo json_encode([
            'success' => true,
            'message' => 'Order placed.',
            'order_number' => $orderNumber,
            'receipt_number' => $receiptNo,
            'payment_id' => (int)($pay['payment_id'] ?? 0),
            'redirect_url' => 'index.php?page=pos_register&action=checkout-receipt',
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function checkout_receipt(): void
    {
        is_login();
        $row = $_SESSION['pos_last_checkout_receipt'] ?? null;
        if (!is_array($row) || empty($row['receipt_number'])) {
            header('Location: index.php?page=pos_register&action=list');
            exit;
        }
        unset($_SESSION['pos_last_checkout_receipt']);
        renderTemplateClean('views/pos_register/order_confirmation.php', $row, 'Order confirmation');
    }

    /**
     * POST /api/order/create body (application/x-www-form-urlencoded) per ExoticIndia API:
     * payment_type, buynow, checkoutdata (verbatim from cart retrieve), cod/codcharges fixed for counter sale,
     * billing/shipping fields, optional Razorpay / store_payment_details.
     *
     * @param array<string, mixed> $payload JSON from POS (confirm_* billing/shipping, payment_* , transaction_id, …)
     * @param array<string, mixed> $cartData Decoded JSON from GET /cart/retrieve (same session as checkout).
     *
     * @return array<string, string>
     */
    private function buildOrderCreatePostFromPayload(array $payload, array $cartData): array
    {
        $posMode = strtolower(trim((string)($payload['payment_mode'] ?? 'cash')));
        $paymentType = $this->mapPosPaymentModeToExoticPaymentType($posMode);

        /** Exact string from GET /cart/retrieve JSON — posted as-is (only URL-encoded as form field by HTTP client). */
        $checkoutdata = $this->extractCheckoutDataStringFromCart($cartData);

        $sf = trim((string)($payload['confirm_sfirst_name'] ?? ''));
        $sl = trim((string)($payload['confirm_slast_name'] ?? ''));
        $sname = trim((string)($payload['confirm_sname'] ?? ''));
        if ($sname === '') {
            $sname = trim($sf . ' ' . $sl);
        }

        $country = strtoupper(substr(trim((string)($payload['confirm_country'] ?? 'IN')), 0, 2));
        if ($country === '') {
            $country = 'IN';
        }
        $scountry = strtoupper(substr(trim((string)($payload['confirm_scountry'] ?? 'IN')), 0, 2));
        if ($scountry === '') {
            $scountry = 'IN';
        }

        $whId = (int)($_SESSION['warehouse_id'] ?? 0);
        $storeId = $whId > 0 ? (string)$whId : '1';
        $txn = trim((string)($payload['transaction_id'] ?? ''));
        // Exotic validates store_payment_details with gateway-style payment type, not UI mode labels.
        $txnField = $txn !== '' ? $txn : ($paymentType === 'offline' ? 'OFFLINE' : '-');

        $out = [
            'payment_type' => $paymentType,
            'buynow' => '0',
            'checkoutdata' => $checkoutdata,
            'cod' => '0',
            'codcharges' => '0.00',
            'first_name' => trim((string)($payload['confirm_first_name'] ?? '')),
            'last_name' => trim((string)($payload['confirm_last_name'] ?? '')),
            'email' => trim((string)($payload['confirm_email'] ?? '')),
            'address1' => trim((string)($payload['confirm_address1'] ?? '')),
            'address2' => trim((string)($payload['confirm_address2'] ?? '')),
            'city' => trim((string)($payload['confirm_city'] ?? '')),
            'state' => trim((string)($payload['confirm_state'] ?? '')),
            'zip' => trim((string)($payload['confirm_zip'] ?? '')),
            'country' => $country,
            'phone' => trim((string)($payload['confirm_phone'] ?? '')),
            'gstin' => trim((string)($payload['confirm_gstin'] ?? '')),
            'sname' => $sname,
            'saddress1' => trim((string)($payload['confirm_saddress1'] ?? '')),
            'saddress2' => trim((string)($payload['confirm_saddress2'] ?? '')),
            'scity' => trim((string)($payload['confirm_scity'] ?? '')),
            'sstate' => trim((string)($payload['confirm_sstate'] ?? '')),
            'szip' => trim((string)($payload['confirm_szip'] ?? '')),
            'scountry' => $scountry,
            'sphone' => trim((string)($payload['confirm_sphone'] ?? '')),
            'store_payment_details' => $storeId . '|' . $paymentType . '|' . $txnField,
        ];

        if ($paymentType === 'razorpay') {
            $rzPay = trim((string)($payload['razorpay_payment_id'] ?? $txn));
            if ($rzPay !== '') {
                $out['razorpay_payment_id'] = $rzPay;
            }
            $rzo = trim((string)($payload['razorpay_order_id'] ?? ''));
            if ($rzo !== '') {
                $out['razorpay_order_id'] = $rzo;
            }
            $rzs = trim((string)($payload['razorpay_signature'] ?? ''));
            if ($rzs !== '') {
                $out['razorpay_signature'] = $rzs;
            }
            $mc = trim((string)($payload['magiccheckout_done'] ?? ''));
            if ($mc === '1') {
                $out['magiccheckout_done'] = '1';
            }
        }

        return $out;
    }

    /**
     * Exotic India: India payment_type includes razorpay, cod, offline — POS counter sale uses offline (not COD).
     * Cash at counter → offline; UPI/bank/POS/cheque → offline; razorpay → razorpay.
     */
    private function mapPosPaymentModeToExoticPaymentType(string $posMode): string
    {
        $m = strtolower(trim($posMode));
        if ($m === 'razorpay') {
            return 'razorpay';
        }
        if (in_array($m, ['cash', 'upi', 'bank_transfer', 'pos_machine', 'cheque', 'offline', 'cod'], true)) {
            return 'offline';
        }

        return 'offline';
    }

    /**
     * checkoutdata string exactly as in the cart retrieve payload (do not serialize or rebuild).
     *
     * @param array<string, mixed> $cartData
     */
    private function extractCheckoutDataStringFromCart(array $cartData): string
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
     * Keep order/create debug readable in popup; checkoutdata can be very large.
     *
     * @param array<string, string> $postBody
     *
     * @return array<string, string|int>
     */
    private function summarizeOrderCreateDebugBody(array $postBody): array
    {
        $debugBody = $postBody;
        $checkoutRaw = (string)($postBody['checkoutdata'] ?? '');
        $len = strlen($checkoutRaw);
        if ($len > 1200) {
            $debugBody['checkoutdata'] = substr($checkoutRaw, 0, 1200) . ' ... [truncated]';
        }
        $debugBody['checkoutdata_length'] = $len;

        return $debugBody;
    }

    private function extractExoticOrderNumberFromCreateResponse(array $data): string
    {
        foreach (['orderid', 'order_id', 'OrderId', 'ordernumber', 'order_number', 'order_no', 'orderNo'] as $k) {
            if (!empty($data[$k])) {
                return trim((string)$data[$k]);
            }
        }
        if (!empty($data['order']) && is_array($data['order'])) {
            return $this->extractExoticOrderNumberFromCreateResponse($data['order']);
        }
        if (!empty($data['data']) && is_array($data['data'])) {
            return $this->extractExoticOrderNumberFromCreateResponse($data['data']);
        }

        return '';
    }

    private function mapPosPaymentModeLabel(string $mode): string
    {
        $m = strtolower(trim($mode));
        $map = [
            'cash' => 'Cash',
            'cod' => 'Cash',
            'upi' => 'UPI',
            'bank_transfer' => 'Bank transfer',
            'pos_machine' => 'POS machine',
            'razorpay' => 'Razorpay',
            'cheque' => 'Cheque',
        ];

        return $map[$m] ?? strtoupper($m);
    }

    /**
     * @param 'billing'|'shipping' $which
     *
     * @return list<string>
     */
    private function formatAddressLinesFromPayload(array $p, string $which): array
    {
        if ($which === 'shipping') {
            $name = trim((string)($p['confirm_sfirst_name'] ?? '') . ' ' . (string)($p['confirm_slast_name'] ?? ''));
            $lines = [
                $name,
                trim((string)($p['confirm_saddress1'] ?? '')),
                trim((string)($p['confirm_saddress2'] ?? '')),
                trim((string)($p['confirm_scity'] ?? '')) . ', ' . trim((string)($p['confirm_sstate'] ?? '')) . ' ' . trim((string)($p['confirm_szip'] ?? '')),
                trim((string)($p['confirm_scountry'] ?? '')),
                'Ph: ' . trim((string)($p['confirm_sphone'] ?? '')),
            ];
        } else {
            $name = trim((string)($p['confirm_first_name'] ?? '') . ' ' . (string)($p['confirm_last_name'] ?? ''));
            $lines = [
                $name,
                trim((string)($p['confirm_address1'] ?? '')),
                trim((string)($p['confirm_address2'] ?? '')),
                trim((string)($p['confirm_city'] ?? '')) . ', ' . trim((string)($p['confirm_state'] ?? '')) . ' ' . trim((string)($p['confirm_zip'] ?? '')),
                trim((string)($p['confirm_country'] ?? '')),
                'Ph: ' . trim((string)($p['confirm_phone'] ?? '')),
            ];
        }

        return array_values(array_filter(array_map('trim', $lines), static function ($x) {
            return $x !== '' && !preg_match('/^Ph:\s*$/', $x);
        }));
    }
}
