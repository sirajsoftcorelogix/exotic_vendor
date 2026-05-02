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
            'cartData' => $this->get_cart(),
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
     * Fetch latest billing/shipping info used for POS order create confirmation popup.
     */
    public function customer_order_info()
    {
        is_login();
        global $conn;
        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
        $billing = [];
        $shipping = [];

        if ($customerId > 0) {
            $stmt = $conn->prepare("SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param("i", $customerId);
                $stmt->execute();
                $info = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($info) {
                    $billing = [
                        "first_name" => trim((string)($info['first_name'] ?? '')),
                        "last_name" => trim((string)($info['last_name'] ?? '')),
                        "email" => trim((string)($info['email'] ?? '')),
                        "phone" => trim((string)($info['mobile'] ?? '')),
                        "address1" => trim((string)($info['address_line1'] ?? '')),
                        "address2" => trim((string)($info['address_line2'] ?? '')),
                        "city" => trim((string)($info['city'] ?? '')),
                        "state" => trim((string)($info['state'] ?? '')),
                        "zip" => trim((string)($info['zipcode'] ?? '')),
                        "country" => trim((string)($info['country'] ?? 'IN')),
                        "gstin" => trim((string)($info['gstin'] ?? '')),
                    ];
                    $shipping = [
                        "sname" => trim((string)(($info['shipping_first_name'] ?? '') . ' ' . ($info['shipping_last_name'] ?? ''))),
                        "saddress1" => trim((string)($info['shipping_address_line1'] ?? '')),
                        "saddress2" => trim((string)($info['shipping_address_line2'] ?? '')),
                        "scity" => trim((string)($info['shipping_city'] ?? '')),
                        "sstate" => trim((string)($info['shipping_state'] ?? '')),
                        "szip" => trim((string)($info['shipping_zipcode'] ?? '')),
                        "scountry" => trim((string)($info['shipping_country'] ?? 'IN')),
                        "sphone" => trim((string)($info['shipping_mobile'] ?? '')),
                    ];
                }
            }
        }

        if ((empty($billing) || empty($shipping)) && !empty($_SESSION['pos_customer_form'])) {
            $form = $_SESSION['pos_customer_form'];
            if (empty($billing)) {
                $billing = [
                    "first_name" => trim((string)($form['first_name'] ?? '')),
                    "last_name" => trim((string)($form['last_name'] ?? '')),
                    "email" => trim((string)($form['cus_email'] ?? '')),
                    "phone" => trim((string)($form['mobile'] ?? '')),
                    "address1" => trim((string)($form['address_line1'] ?? '')),
                    "address2" => trim((string)($form['address_line2'] ?? '')),
                    "city" => trim((string)($form['city'] ?? '')),
                    "state" => trim((string)($form['state'] ?? '')),
                    "zip" => trim((string)($form['zipcode'] ?? '')),
                    "country" => "IN",
                    "gstin" => trim((string)($form['gstin'] ?? '')),
                ];
            }
            if (empty($shipping)) {
                $shipping = [
                    "sname" => trim((string)(($form['shipping_first_name'] ?? '') . ' ' . ($form['shipping_last_name'] ?? ''))),
                    "saddress1" => trim((string)($form['shipping_address_line1'] ?? '')),
                    "saddress2" => trim((string)($form['shipping_address_line2'] ?? '')),
                    "scity" => trim((string)($form['shipping_city'] ?? '')),
                    "sstate" => trim((string)($form['shipping_state'] ?? '')),
                    "szip" => trim((string)($form['shipping_zipcode'] ?? '')),
                    "scountry" => "IN",
                    "sphone" => trim((string)($form['shipping_mobile'] ?? '')),
                ];
            }
        }

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

    /**
     * GST % for a cart line: same keys as Exotic `/product/code` (see mergeGstPercentField): gst_rate, gst_percent, gst.
     * Cart line keys can override when present; unwrapped product payload fills typical API fields.
     */
    private function resolveCartLineGstPercent(array $cartLineItem, array $productApiResult, $conn): float
    {
        $productData = $this->unwrapProductApiResponse($productApiResult['data'] ?? []);
        $merged = array_merge($cartLineItem, $productData);
        $dbRow = $this->fetchVpProductGstFallbackRow($conn, trim((string)($cartLineItem['code'] ?? '')));

        return $this->resolveGstPercentAsNumber($merged, $dbRow);
    }

    /**
     * Coupon discount rupees from GET /cart/retrieve JSON.
     * Primary key matches legacy POS cart helper (views/pos_register/cart-functions.php): couponreduction.
     * Fallback: orderremarks.coupon_reduce (same shape as order detail templates).
     */
    private function resolveCartRetrieveCouponDiscount(array $data): float
    {
        if (array_key_exists('couponreduction', $data) && is_numeric($data['couponreduction'])) {
            $v = (float)$data['couponreduction'];
            if ($v > 0) {
                return $v;
            }
        }
        if (
            !empty($data['orderremarks'])
            && is_array($data['orderremarks'])
            && isset($data['orderremarks']['coupon_reduce'])
            && is_numeric($data['orderremarks']['coupon_reduce'])
        ) {
            $v = (float)$data['orderremarks']['coupon_reduce'];

            return $v > 0 ? $v : 0.0;
        }

        return 0.0;
    }

    /**
     * First positive numeric value from a payload node by candidate keys.
     */
    private function pickFirstPositiveNumeric(array $node, array $keys): float
    {
        foreach ($keys as $k) {
            if (array_key_exists($k, $node) && is_numeric($node[$k])) {
                $v = (float)$node[$k];
                if ($v > 0) {
                    return $v;
                }
            }
        }

        return 0.0;
    }

    /**
     * Decode checkoutdata that may be array/json/base64-json.
     */
    private function decodeCheckoutdataNode($checkoutdata): array
    {
        if (is_array($checkoutdata)) {
            return $checkoutdata;
        }
        $s = trim((string)$checkoutdata);
        if ($s === '') {
            return [];
        }
        $decoded = json_decode($s, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $b = base64_decode($s, true);
        if ($b === false || $b === '') {
            return [];
        }
        $decoded2 = json_decode($b, true);

        return is_array($decoded2) ? $decoded2 : [];
    }

    /**
     * Coupon discount in rupees: root fields, then JSON (or base64+JSON) checkoutdata.
     * The API may omit couponreduction on the root but still include it in checkoutdata.
     */
    private function extractCartRetrieveCouponDiscountRupees(array $data): float
    {
        $v = $this->resolveCartRetrieveCouponDiscount($data);
        if ($v > 0) {
            return $v;
        }
        $rootAlt = $this->pickFirstPositiveNumeric(
            $data,
            ['coupon_reduction', 'couponreduce', 'coupon_reduce', 'coupondiscount', 'coupon_discount']
        );
        if ($rootAlt > 0) {
            return $rootAlt;
        }

        $checkoutNode = $this->decodeCheckoutdataNode($data['checkoutdata'] ?? null);
        if ($checkoutNode === []) {
            return 0.0;
        }
        $tryNode = static function (array $node): float {
            foreach (['couponreduction', 'coupon_reduction', 'couponreduce', 'coupon_reduce', 'coupondiscount', 'coupon_discount'] as $k) {
                if (array_key_exists($k, $node) && is_numeric($node[$k])) {
                    $x = (float)$node[$k];
                    if ($x > 0) {
                        return $x;
                    }
                }
            }
            if (!empty($node['orderremarks']) && is_array($node['orderremarks'])
                && isset($node['orderremarks']['coupon_reduce']) && is_numeric($node['orderremarks']['coupon_reduce'])) {
                $x = (float)$node['orderremarks']['coupon_reduce'];

                return $x > 0 ? $x : 0.0;
            }

            return 0.0;
        };
        $fromCheckout = $tryNode($checkoutNode);
        if ($fromCheckout > 0) {
            return $fromCheckout;
        }

        return 0.0;
    }

    /**
     * Total GST rupees from GET /cart/retrieve JSON root (same key as views/pos_register/cart-functions.php): gstamount.
     */
    private function resolveCartRetrieveGstTotal(array $data): float
    {
        return $this->pickFirstPositiveNumeric($data, ['gstamount']);
    }

    /**
     * Addon list from /product/code (unwrapped), including express row for cart_entry matching.
     *
     * @return array<int, array<string, mixed>>
     */
    private function productApiAddonCatalogList(array $productApiResult): array
    {
        $data = $this->unwrapProductApiResponse($productApiResult['data'] ?? []);
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

    /**
     * Sum addon unit prices by matching selected cart_entry strings to catalog rows (same as POS modal).
     */
    private function sumAddonPricesFromCatalogMatches(array $selectedEntries, array $catalogAddons): float
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

    /**
     * Express shipping is billed as an add-on in the UI but cart/retrieve may omit it from addons_selected;
     * merge per-unit express cost into the addon sum without double-counting catalog / addons rows.
     */
    private function mergeExpressShippingIntoAddonUnitSum(
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

    /**
     * Human-readable addon lines for POS cart UI (non-express; express has its own row).
     *
     * @param array<int, array<string, mixed>> $addonsFromApi
     * @param array<int, string> $selectedEntries
     * @param array<int, array<string, mixed>> $catalog
     * @return array<int, array{title: string, value: float, cart_entry: string}>
     */
    private function buildPosCartAddonDisplayLines(
        array $addonsFromApi,
        array $selectedEntries,
        array $catalog
    ): array {
        $lines = [];
        $seen = [];

        $resolveTitle = static function (string $cartEntry, string $fallbackName) use ($catalog): string {
            $ce = trim($cartEntry);
            $name = trim($fallbackName);
            if ($name !== '') {
                return $name;
            }
            if ($ce === '') {
                return '';
            }
            foreach ($catalog as $opt) {
                if (strcasecmp(trim((string)($opt['cart_entry'] ?? '')), $ce) === 0) {
                    return trim((string)($opt['title'] ?? '')) ?: $ce;
                }
            }

            return $ce;
        };

        foreach ($addonsFromApi as $ad) {
            if (!is_array($ad)) {
                continue;
            }
            $ce = trim((string)($ad['cart_entry'] ?? ''));
            $title = $resolveTitle($ce, (string)($ad['name'] ?? ''));
            if ($title === '') {
                continue;
            }
            if (stripos($title, 'express') !== false) {
                continue;
            }
            if ($ce !== '') {
                $seen[strtolower($ce)] = true;
            }
            $lines[] = [
                'title' => $title,
                'value' => (float)($ad['value'] ?? 0),
                'cart_entry' => $ce,
            ];
        }

        foreach ($selectedEntries as $entry) {
            $entry = trim((string)$entry);
            if ($entry === '') {
                continue;
            }
            if (isset($seen[strtolower($entry)])) {
                continue;
            }
            $seen[strtolower($entry)] = true;

            $title = $resolveTitle($entry, '');
            if ($title === '') {
                $title = $entry;
            }
            if (stripos($title, 'express') !== false) {
                continue;
            }

            $price = 0.0;
            foreach ($catalog as $opt) {
                if (strcasecmp(trim((string)($opt['cart_entry'] ?? '')), $entry) === 0) {
                    $price = (float)($opt['price'] ?? 0);
                    break;
                }
            }

            $lines[] = [
                'title' => $title,
                'value' => $price,
                'cart_entry' => $entry,
            ];
        }

        return $lines;
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

    private function resolveVpProductIdByCode($conn, string $code): int
    {
        if ($code === '' || !$conn) {
            return 0;
        }
        $stmt = $conn->prepare(
            'SELECT id FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?) ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ss', $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row['id']) ? (int)$row['id'] : 0;
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
        $pid = $this->resolveVpProductIdByCode($conn, trim($code));
        if ($pid <= 0) {
            return null;
        }
        $avail = $this->getWarehouseStockForProductId($conn, $pid, $warehouseId);
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
        // Variant SKU may return no image from upstream; retry with base item_code.
        if (
            $imageResolved === ''
            && $dbItemCode !== ''
            && strcasecmp($dbItemCode, $code) !== 0
        ) {
            $res2 = $this->exotic_api_call('/product/code', 'GET', ['code' => $dbItemCode]);
            $data2 = $this->unwrapProductApiResponse($res2['data'] ?? []);
            $imageResolved = $this->fixImageUrl($this->pickRawImageFromProductApiArray($data2));
        }

        if ($imageResolved === '' && $imageFromDb !== '') {
            $imageResolved = $imageFromDb;
        }

        $sellingPrice = $this->mergePosProductSellingBaseExGst($data, $dbRow);
        if (
            $sellingPrice <= 0
            && $dbItemCode !== ''
            && strcasecmp($dbItemCode, $code) !== 0
        ) {
            if ($data2 === null) {
                $resPrice = $this->exotic_api_call('/product/code', 'GET', ['code' => $dbItemCode]);
                $data2 = $this->unwrapProductApiResponse($resPrice['data'] ?? []);
            }
            $alt = $this->mergePosProductSellingBaseExGst($data2, $dbRow);
            if ($alt > 0) {
                $sellingPrice = $alt;
            }
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

    private function normalizeCheckoutdataToken($checkoutdata): string
    {
        if (is_string($checkoutdata)) {
            return trim($checkoutdata);
        } elseif (is_scalar($checkoutdata)) {
            return trim((string)$checkoutdata);
        } else {
            return '';
        }
    }

    /**
     * @param string      $endpoint   Path beginning with "/", e.g. "/cart/retrieve"
     * @param string|null $apiBaseUrl Base URL without trailing slash. Default API JSON gateway:
     *                                "https://www.exoticindia.com/api".
     *                                Site cart helpers (documented separately from /api/) use
     *                                "https://www.exoticindia.com" — e.g. GET /cart/addcoupon.
     */
    public function exotic_api_call($endpoint, $method = 'GET', $params = [], $postData = null, ?string $apiBaseUrl = null)
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

    /**
     * Debug payload for POS "Order create API" modal (matches cart API debug shape).
     */
    private function buildOrderCreateApiDebug(array $queryParams, array $postData, array $apiResult, array $posContext = []): array
    {
        $url = 'https://www.exoticindia.com/api/order/create';
        if ($queryParams !== []) {
            $url .= (strpos($url, '?') === false ? '?' : '&') . http_build_query($queryParams);
        }

        $bodyForLog = $postData;
        $bodyFormEncodedForLog = '';
        if (is_array($postData)) {
            $bodyFormEncodedForLog = http_build_query($postData);
        } elseif (is_string($postData)) {
            $bodyFormEncodedForLog = $postData;
        } elseif ($postData !== null) {
            $bodyFormEncodedForLog = (string)$postData;
        }
        if (!empty($bodyForLog['cardnumber'])) {
            $bodyForLog['cardnumber'] = '(redacted)';
        }
        if (!empty($bodyForLog['card_cvv'])) {
            $bodyForLog['card_cvv'] = '(redacted)';
        }

        $raw = (string)($apiResult['raw'] ?? '');
        $parsed = $apiResult['data'] ?? [];
        $rawPreview = '';
        if ($raw !== '' && $parsed === []) {
            $rawPreview = function_exists('mb_substr')
                ? mb_substr($raw, 0, 8000, 'UTF-8')
                : substr($raw, 0, 8000);
        }

        $out = [
            'timestamp' => date('c'),
            'triggered_from' => 'payment_modal',
            'payment_modal' => $posContext,
            'request' => [
                'method' => 'POST',
                'url' => $url,
                'query_params' => $queryParams,
                'post_body' => $bodyForLog,
                'post_body_form_encoded' => $bodyFormEncodedForLog,
                'headers' => [
                    'x-api-key' => '(redacted)',
                    'x-api-deviceid' => $this->resolveApiDeviceId(),
                    'x-api-appplayerid' => 'POS-Web-Terminal',
                    'x-api-countrycode' => 'IN',
                    // Must match exotic_api_call() header value.
                    // exotic_api_call() uses $_SESSION['x_api_euid'] captured from previous API responses.
                    'x-api-euid' => (string)($_SESSION['x_api_euid'] ?? ''),
                    'x-api-jwt' => !empty($_SESSION['x_api_jwt']) ? '(present)' : '',
                    'x-api-browsehistory' => (string)($_SESSION['x_api_browsehistory'] ?? ''),
                    'x-api-etd' => (string)($_SESSION['x_api_etd'] ?? ''),
                    'x-api-etd-pincode' => (string)($_SESSION['x_api_etd_pincode'] ?? ''),
                    'User-Agent' => 'ExoticPOS',
                ],
            ],
            'http_code' => (int)($apiResult['code'] ?? 0),
            'response' => is_array($parsed) ? $parsed : [],
            // Explicitly expose the exact result from create-order call point.
            'api_result' => [
                'code' => (int)($apiResult['code'] ?? 0),
                'data' => is_array($parsed) ? $parsed : [],
            ],
        ];

        // Helpful when upstream errors are generic (e.g. "Missing order data").
        if (array_key_exists('checkoutdata', $postData)) {
            $cd = $postData['checkoutdata'];
            $cdStr = is_string($cd) ? $cd : (is_scalar($cd) ? (string)$cd : '');
            $out['request']['checkoutdata_debug'] = [
                'type' => gettype($cd),
                'length' => strlen($cdStr),
                'preview' => strlen($cdStr) > 80 ? substr($cdStr, 0, 80) . '…' : $cdStr,
            ];
        }
        if (array_key_exists('cart_api_checkoutdata', $posContext)) {
            $out['request']['cart_api_checkoutdata'] = $posContext['cart_api_checkoutdata'];
        }
        if ($rawPreview !== '') {
            $out['response_raw_preview'] = $rawPreview;
            $out['api_result']['raw_preview'] = $rawPreview;
        }

        return $out;
    }

    /**
     * Remove every line item from the Exotic India cart API (qty 0 per cartref), same as POS "Remove".
     */
    private function clearRemoteCartLines(array $items): void
    {
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $cartref = trim((string)($item['cartref'] ?? ''));
            if ($cartref === '') {
                continue;
            }
            $this->exotic_api_call('/cart/modifyqty', 'GET', [
                'cartid' => $cartref,
                'newqty' => 0,
            ]);
        }
    }

    private function resolveAddonAmount(array $ad): float
    {
        foreach (['value', 'price', 'amount'] as $k) {
            if (isset($ad[$k]) && $ad[$k] !== '' && is_numeric($ad[$k])) {
                return (float)$ad[$k];
            }
        }

        return 0.0;
    }

    private function buildAddonCartEntry(array $ad, float $amount): string
    {
        $cartEntry = trim((string)($ad['cart_entry'] ?? ''));
        if ($cartEntry !== '') {
            return $cartEntry;
        }

        return stripos((string)($ad['name'] ?? ''), 'Express') !== false
            ? 'OPTIONALS_EXPRESS:_blank_:' . $amount
            : 'OPTIONALS_SCULPTURES_LACQUER:_blank_:' . $amount;
    }

    /**
     * @return array{addons: array<int,array<string,mixed>>, selected_entries: array<int,string>}
     */
    private function extractCartAddonsAndEntries(array $item): array
    {
        $addons = [];
        $selectedEntries = [];

        if (!empty($item['addons_selected']) && is_array($item['addons_selected'])) {
            foreach ($item['addons_selected'] as $ad) {
                if (!is_array($ad)) {
                    continue;
                }
                $amt = $this->resolveAddonAmount($ad);
                $cartEntry = $this->buildAddonCartEntry($ad, $amt);
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
            $optParts = strpos($optStr, '|') !== false ? explode('|', $optStr) : explode(',', $optStr);
            foreach ($optParts as $chunk) {
                $chunk = trim((string)$chunk);
                if ($chunk !== '' && !in_array($chunk, $selectedEntries, true)) {
                    $selectedEntries[] = $chunk;
                }
            }
        }

        return [
            'addons' => $addons,
            'selected_entries' => $selectedEntries,
        ];
    }

    /**
     * @return array{product_res: array<string,mixed>, all_addons: array<int,array<string,mixed>>, unit_base: float, addons_display: array<int,array<string,mixed>>}
     */
    private function resolveCartLineCatalogAndPrice(array $item, $conn, array $addons, array $selectedEntries): array
    {
        $productRes = $this->exotic_api_call('/product/code', 'GET', [
            'code' => $item['code']
        ]);
        $allAddons = $this->productApiAddonCatalogList($productRes);

        $unitBase = (float)($item['price'] ?? 0);
        $vpIndia = $this->resolveIndiaSellPriceFromVp($conn, trim((string)($item['code'] ?? '')));
        if ($vpIndia > 0) {
            $unitBase = $vpIndia;
        }

        return [
            'product_res' => $productRes,
            'all_addons' => $allAddons,
            'unit_base' => $unitBase,
            'addons_display' => $this->buildPosCartAddonDisplayLines($addons, $selectedEntries, $allAddons),
        ];
    }

    private function resolveAddonUnitSum(
        array $addons,
        array $selectedEntries,
        array $allAddons,
        bool $expressSelected,
        float $shippingPerUnit
    ): float {
        $addonsSumPerUnit = 0.0;
        foreach ($addons as $a) {
            $addonsSumPerUnit += (float)($a['value'] ?? 0);
        }
        if ($addonsSumPerUnit <= 0 && $selectedEntries !== [] && $allAddons !== []) {
            $addonsSumPerUnit = $this->sumAddonPricesFromCatalogMatches($selectedEntries, $allAddons);
        }

        return $this->mergeExpressShippingIntoAddonUnitSum(
            $addonsSumPerUnit,
            $addons,
            $selectedEntries,
            $allAddons,
            $expressSelected,
            $shippingPerUnit
        );
    }

    /**
     * Build one normalized POS cart line plus computed totals for subtotal/shipping/gst.
     *
     * @return array{
     *   item: array<string,mixed>,
     *   taxable_line: float,
     *   shipping_component: float,
     *   gst_line: float
     * }
     */
    private function buildPosCartLineFromApiItem(array $item, $conn): array
    {
        $shipping_per_unit = (float)($item['express_shipping_cost'] ?? 0);
        $lineQty = max(1, (int)($item['quantity'] ?? 1));
        $shipping = $shipping_per_unit * $lineQty;
        $expressSelected = (bool)($item['express_shipping_chosen'] ?? false);

        $addonData = $this->extractCartAddonsAndEntries($item);
        $addons = $addonData['addons'];
        $selectedEntries = $addonData['selected_entries'];

        $lineContext = $this->resolveCartLineCatalogAndPrice($item, $conn, $addons, $selectedEntries);
        $productRes = $lineContext['product_res'];
        $allAddons = $lineContext['all_addons'];
        $unitBase = $lineContext['unit_base'];
        $addonsDisplay = $lineContext['addons_display'];

        $addonsSumPerUnit = $this->resolveAddonUnitSum(
            $addons,
            $selectedEntries,
            $allAddons,
            $expressSelected,
            $shipping_per_unit
        );
        $taxableLine = ($unitBase + $addonsSumPerUnit) * $lineQty;
        $gstPercent = $this->resolveCartLineGstPercent($item, $productRes, $conn);
        $gstLine = round(($taxableLine * $gstPercent) / 100, 2);

        return [
            'item' => [
                'item_code' => $item['code'] ?? '',
                'cartref' => $item['cartref'] ?? '',
                'name' => $item['name'] ?? '',
                'imageurl' => $item['imageurl'] ?? '',
                'price' => $unitBase,
                'quantity' => $lineQty,
                'shipping' => $shipping,
                'shipping_per_unit' => $shipping_per_unit,
                'shipping_title' => $item['express_shipping_option']['title'] ?? '',
                'shipping_longtitle' => $item['express_shipping_option']['longtitle'] ?? '',
                'express_selected' => $expressSelected,
                'addons' => $addons,
                'selected_entries' => $selectedEntries,
                'addons_display' => $addonsDisplay,
            ],
            'taxable_line' => $taxableLine,
            'shipping_component' => $expressSelected ? $shipping : 0.0,
            'gst_line' => $gstLine,
        ];
    }

    /**
     * Convert raw /cart/retrieve rows into normalized cart lines and running totals.
     *
     * @return array{items: array<int,array<string,mixed>>, subtotal: float, shipping_total: float, gst_computed: float}
     */
    private function buildPosCartLinesAndTotals(array $cartItems, $conn): array
    {
        $items = [];
        $subtotal = 0.0;
        $shipping_total = 0.0;
        $gst_computed = 0.0;

        foreach ($cartItems as $item) {
            if (!is_array($item)) {
                continue;
            }
            $line = $this->buildPosCartLineFromApiItem($item, $conn);
            $items[] = $line['item'];
            $subtotal += (float)$line['taxable_line'];
            $shipping_total += (float)$line['shipping_component'];
            $gst_computed += (float)$line['gst_line'];
        }

        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'shipping_total' => $shipping_total,
            'gst_computed' => $gst_computed,
        ];
    }

    private function inferMissingCouponDiscount(
        float $couponDiscount,
        bool $couponApplied,
        float $apiTotal,
        float $subtotal,
        float $customDiscount
    ): float {
        if (!($couponDiscount <= 0 && $couponApplied && $apiTotal > 0)) {
            return $couponDiscount;
        }
        // Subtotal is already GST-inclusive in POS totals.
        $inferredCoupon = ($subtotal - $customDiscount) - $apiTotal;
        if ($inferredCoupon > 0.01) {
            return round($inferredCoupon, 2);
        }

        return $couponDiscount;
    }

    /**
     * Parse coupon amount from discountcoupondetails formats like:
     * CODE|P|10  => 10% of subtotal
     * CODE|F|100 => fixed 100
     */
    private function deriveCouponDiscountFromCouponString(string $coupon, float $subtotal): float
    {
        $coupon = trim($coupon);
        if ($coupon === '' || strpos($coupon, '|') === false) {
            return 0.0;
        }
        $parts = explode('|', $coupon);
        if (count($parts) < 3) {
            return 0.0;
        }
        $type = strtoupper(trim((string)$parts[1]));
        $rawValue = trim((string)$parts[2]);
        if (!is_numeric($rawValue)) {
            return 0.0;
        }
        $value = (float)$rawValue;
        if ($value <= 0) {
            return 0.0;
        }
        if ($type === 'P' || $type === '%' || $type === 'PERCENT') {
            return round(($subtotal * $value) / 100, 2);
        }
        return round($value, 2);
    }

    private function buildCouponStringParseDebug(string $coupon, float $subtotal): array
    {
        $raw = trim($coupon);
        $out = [
            'raw_coupon' => $raw,
            'parsed' => false,
            'code' => '',
            'type' => '',
            'raw_value' => '',
            'computed_discount' => 0.0,
        ];
        if ($raw === '' || strpos($raw, '|') === false) {
            return $out;
        }
        $parts = explode('|', $raw);
        if (count($parts) < 3) {
            return $out;
        }
        $type = strtoupper(trim((string)$parts[1]));
        $rawValue = trim((string)$parts[2]);
        if (!is_numeric($rawValue)) {
            return $out;
        }
        $value = (float)$rawValue;
        if ($value <= 0) {
            return $out;
        }
        $computed = ($type === 'P' || $type === '%' || $type === 'PERCENT')
            ? round(($subtotal * $value) / 100, 2)
            : round($value, 2);

        $out['parsed'] = true;
        $out['code'] = trim((string)$parts[0]);
        $out['type'] = $type;
        $out['raw_value'] = $rawValue;
        $out['computed_discount'] = $computed;

        return $out;
    }

    private function resolveGrandTotalFromApi(
        float $apiTotal,
        float $subtotal,
        float $gst,
        float $totalDiscount,
        float $couponDiscount
    ): float {
        // Subtotal is GST-inclusive, so do not add GST again.
        $computedPayable = $subtotal - $totalDiscount;
        if ($apiTotal <= 0) {
            return $computedPayable;
        }
        $apiTotalLooksLikePreTaxSubtotal = ($gst > 0.01 && abs($apiTotal - $subtotal) < 0.05);
        if ($apiTotalLooksLikePreTaxSubtotal) {
            // totalamount matches taxable subtotal only; do not use it as grand total.
            return $computedPayable;
        }
        if ($couponDiscount > 0 && $apiTotal > ($computedPayable + 0.01)) {
            // Coupon applied on API but totalamount didn't drop — prefer computed checkout total.
            return $computedPayable;
        }

        return $apiTotal;
    }

    /**
     * Resolve coupon/custom/gst/grand-total fallbacks from cart payload + computed lines.
     *
     * @return array{
     *   codcharges: float,
     *   coupon_applied: bool,
     *   coupon_discount: float,
     *   custom_discount: float,
     *   gst: float,
     *   grand_total: float,
     *   coupon_debug: array<string,mixed>
     * }
     */
    private function resolveCartFinancials(array $data, string $coupon, float $subtotal, float $gstComputed): array
    {
        $codcharges = (float)($data['codcharges_if_chosen'] ?? 0);
        $coupon_applied = trim($coupon) !== '';
        $coupon_discount = $this->extractCartRetrieveCouponDiscountRupees($data);
        $couponDiscountFromPayload = $coupon_discount;
        $custom_discount = (float)($data['customreduction'] ?? 0);
        $gst = $this->resolveCartRetrieveGstTotal($data);
        if ($gstComputed > 0) {
            $gst = round($gstComputed, 2);
        }
        $apiTotal = isset($data['totalamount']) && is_numeric($data['totalamount']) ? (float)$data['totalamount'] : 0.0;
        $couponStringDebug = $this->buildCouponStringParseDebug($coupon, $subtotal);
        $couponDiscountFromString = 0.0;
        if ($coupon_discount <= 0 && $coupon_applied) {
            $coupon_discount = $this->deriveCouponDiscountFromCouponString($coupon, $subtotal);
            $couponDiscountFromString = $coupon_discount;
        }

        $total_discount = $coupon_discount + $custom_discount;
        $couponBeforeInfer = $coupon_discount;
        $coupon_discount = $this->inferMissingCouponDiscount(
            $coupon_discount,
            $coupon_applied,
            $apiTotal,
            $subtotal,
            $custom_discount
        );
        $total_discount = $coupon_discount + $custom_discount;
        $grand_total = $this->resolveGrandTotalFromApi($apiTotal, $subtotal, $gst, $total_discount, $coupon_discount);
        $couponDiscountFromInference = max(0, round($coupon_discount - $couponBeforeInfer, 2));

        return [
            'codcharges' => $codcharges,
            'coupon_applied' => $coupon_applied,
            'coupon_discount' => $coupon_discount,
            'custom_discount' => $custom_discount,
            'gst' => $gst,
            'grand_total' => $grand_total,
            'coupon_debug' => [
                'coupon_applied' => $coupon_applied,
                'payload_coupon_discount' => $couponDiscountFromPayload,
                'string_parse' => $couponStringDebug,
                'string_coupon_discount' => $couponDiscountFromString,
                'inferred_coupon_discount' => $couponDiscountFromInference,
                'final_coupon_discount' => $coupon_discount,
                'subtotal_for_calc' => $subtotal,
                'gst_for_calc' => $gst,
                'api_totalamount' => $apiTotal,
            ],
        ];
    }

    private function buildCartRetrieveRequestMeta(array $query): array
    {
        return [
            'method' => 'GET',
            'url' => 'https://www.exoticindia.com/api/cart/retrieve?' . http_build_query($query),
            'query_params' => $query,
            'headers' => [
                'x-api-key' => '(redacted)',
                'x-api-deviceid' => $this->resolveApiDeviceId(),
                'x-api-appplayerid' => 'POS-Web-Terminal',
                'x-api-countrycode' => 'IN',
                // Must match exotic_api_call() header value.
                'x-api-euid' => (string)($_SESSION['x_api_euid'] ?? ''),
                'x-api-jwt' => !empty($_SESSION['x_api_jwt']) ? '(present)' : '',
                'x-api-browsehistory' => (string)($_SESSION['x_api_browsehistory'] ?? ''),
                'x-api-etd' => (string)($_SESSION['x_api_etd'] ?? ''),
                'x-api-etd-pincode' => (string)($_SESSION['x_api_etd_pincode'] ?? ''),
                'User-Agent' => 'ExoticPOS',
            ],
        ];
    }

    private function stripCartItemsFromDebugBody(array $data): array
    {
        if (isset($data['cartitems'])) {
            unset($data['cartitems']);
        }

        return $data;
    }

    /**
     * GET /cart/retrieve may nest payload under "data" like the product API.
     */
    private function unwrapCartRetrievePayload(array $data): array
    {
        if (!empty($data['data']) && is_array($data['data'])) {
            $inner = $data['data'];
            unset($data['data']);

            return array_merge($data, $inner);
        }

        return $data;
    }

    /**
     * Raw checkoutdata node from cart retrieve JSON (must match POST /order/create payload source).
     */
    private function checkoutdataFromCartRetrieveBody(array $data)
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

    private function cartHasUsableCheckoutdata(array $cartData): bool
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

    public function get_cart()
    {
        global $conn;
        $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
        $voucher = $_SESSION['gift_voucher']['giftvoucherdetails'] ?? '';
        $cartRetrieveQuery = [
            'discountcoupondetails' => $coupon,
            'giftvoucherdetails' => $voucher,
        ];
        $res = $this->exotic_api_call('/cart/retrieve', 'GET', $cartRetrieveQuery);
        $cartApiRequestMeta = $this->buildCartRetrieveRequestMeta($cartRetrieveQuery);

        $data = $this->unwrapCartRetrievePayload($res['data'] ?? []);

        $lineBuild = $this->buildPosCartLinesAndTotals(
            !empty($data['cartitems']) && is_array($data['cartitems']) ? $data['cartitems'] : [],
            $conn
        );
        $items = $lineBuild['items'];
        $subtotal = $lineBuild['subtotal'];
        $shipping_total = $lineBuild['shipping_total'];
        $gst_computed = $lineBuild['gst_computed'];
        // Keep Sub Total as pre-discount line sum; discount is shown separately in UI.
        $display_subtotal = $subtotal;
        $financials = $this->resolveCartFinancials($data, (string)$coupon, $subtotal, $gst_computed);
        $cartApiBodyForDebug = $this->stripCartItemsFromDebugBody($data);
        $cartApiBodyForDebug['coupon_discount_debug'] = $financials['coupon_debug'];

        return [
            'items' => $items,
            'subtotal' => $display_subtotal,
            'shipping_total' => $shipping_total,
            'gst' => $financials['gst'],
            'coupon_discount' => $financials['coupon_discount'],
            'coupon_applied' => $financials['coupon_applied'],
            'custom_discount' => $financials['custom_discount'],
            'grand_total' => $financials['grand_total'],
            'checkoutdata' => $this->checkoutdataFromCartRetrieveBody($data),
            'codcharges' => $financials['codcharges'],
            // Use normalized values echoed by cart/retrieve for downstream order/create query consistency.
            'discountcoupondetails_effective' => (string)($data['discountcoupondetails'] ?? $coupon),
            'giftvoucherdetails_effective' => (string)($data['giftvoucherdetails'] ?? $voucher),
            // POS register is INR billing; do not inherit API fx_type (can return USD/$).
            'currency' => 'INR',
            'cart_api_http_code' => (int)($res['code'] ?? 0),
            'cart_api_body' => $cartApiBodyForDebug,
            'cart_api_request' => $cartApiRequestMeta,
        ];
    }

    public function add_to_cart()
    {
        $code      = $_POST['code'] ?? '';
        $qty       = $_POST['qty'] ?? 1;
        $variation = trim($_POST['variation'] ?? '');
        $rawOptions = $_POST['options'] ?? '';
        $buyNow    = false;
        $optionsArray = is_array($rawOptions) ? $rawOptions : [];
        $optionsString = is_string($rawOptions) ? trim($rawOptions) : '';
        $toggleOption = $_POST['toggle_option'] ?? '';
        $checked      = $_POST['checked'] ?? 0;

        // ✅ ADD / REMOVE LOGIC
        if (!empty($toggleOption)) {

            if ($checked) {
                // ADD
                if (!in_array($toggleOption, $optionsArray)) {
                    $optionsArray[] = $toggleOption;
                }
            } else {
                // REMOVE
                $optionsArray = array_filter($optionsArray, function ($opt) use ($toggleOption) {
                    return $opt !== $toggleOption;
                });
            }
        }

        // echo '<pre>';
        // print_r($options);
        // exit;
        if (empty($code)) {
            header("Location: ?page=pos_register");
            exit;
        }

        global $conn;
        $qtyInt = max(1, (int)$qty);
        $stockLookup = trim((string)($_POST['stock_check_code'] ?? ''));
        if ($stockLookup === '') {
            $stockLookup = trim((string)$code);
        }
        $stockErr = $this->validateQtyAgainstWarehouse($conn, $stockLookup, $qtyInt);
        if ($stockErr !== null) {
            $_SESSION['cart_error'] = $stockErr;
            header("Location: ?page=pos_register");
            exit;
        }

        $voucher = $_SESSION['gift_voucher']['giftvoucherdetails'] ?? '';
        $coupon  = '';

        if (!empty($_SESSION['discount_coupon'])) {
            $coupon = is_array($_SESSION['discount_coupon'])
                ? ($_SESSION['discount_coupon']['discountcoupondetails'] ?? '')
                : $_SESSION['discount_coupon'];
        }

        $postArray = [
            'buynow'   => $buyNow ? 1 : 0,
            'code'     => trim($code),
            'qty'      => $qtyInt,
            'discountcoupondetails' => $coupon,
            'giftvoucherdetails'    => $voucher
        ];
        //  Variation handling
        if (!empty($variation)) {

            if (strpos($variation, ':') === false || $variation === ':') {
                header("Location: ?page=pos_register&error=invalid_variation");
                exit;
            }
            list($size, $color) = explode(':', $variation) + ['', ''];
            $variation = trim($size) . ':' . trim($color);

            $postArray['variation'] = $variation;
        }

        if ($optionsString !== '') {
            $postArray['options'] = $optionsString;
        } elseif (!empty($optionsArray)) {
            $normalized = [];
            foreach ($optionsArray as $o) {
                $o = trim((string)$o);
                if ($o !== '') {
                    $normalized[] = $o;
                }
            }
            $normalized = array_values(array_unique($normalized));
            if ($normalized !== []) {
                // Match toggle_addon / cart retrieve (pipe-separated cart_entry tokens)
                $postArray['options'] = implode('|', $normalized);
            }
        }

        $postData = http_build_query($postArray);

        $result = $this->exotic_api_call('/cart/add', 'POST', [], $postData);
        // echo '<pre>';
        // print_r($result);
        // exit;
        $apiData = is_array($result['data'] ?? null) ? $result['data'] : [];
        $cartAddOk = !empty($apiData['cartref']);
        if (!$cartAddOk && !empty($apiData['cartitems']) && is_array($apiData['cartitems'])) {
            foreach ($apiData['cartitems'] as $ci) {
                if (!is_array($ci)) {
                    continue;
                }
                if (!empty($ci['cartref'])) {
                    $cartAddOk = true;
                    break;
                }
            }
        }

        if (!$cartAddOk) {
            $apiMsg = trim((string)($result['data']['error'] ?? $result['data']['message'] ?? ''));
            if ($apiMsg === '') {
                $raw = trim((string)($result['raw'] ?? ''));
                if ($raw !== '') {
                    $apiMsg = function_exists('mb_substr')
                        ? mb_substr($raw, 0, 220, 'UTF-8')
                        : substr($raw, 0, 220);
                }
            }
            $httpCode = (int)($result['code'] ?? 0);
            $_SESSION['cart_error'] = 'Cart add failed'
                . ($httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '')
                . ($apiMsg !== '' ? ': ' . $apiMsg : '');
        }

        header("Location: ?page=pos_register");
        exit;
    }
    
    public function toggle_addon()
    {
        $cartref = $_POST['cartref'] ?? '';
        $addonEntry = $_POST['addon_entry'] ?? '';

        if (!$cartref || !$addonEntry) {
            header("Location: ?page=pos_register");
            exit;
        }

        $cartData = $this->get_cart();

        $currentOptions = [];
        $itemCode = '';
        $qty = 1;

        foreach ($cartData['items'] as $item) {

            if ($item['cartref'] == $cartref) {

                $itemCode = $item['item_code'] ?? '';
                $qty = $item['quantity'] ?? 1;

                if (!empty($item['addons'])) {
                    foreach ($item['addons'] as $ad) {
                        $currentOptions[] = $ad['cart_entry'];
                    }
                }
            }
        }

        // TOGGLE
        if (in_array($addonEntry, $currentOptions)) {
            $currentOptions = array_diff($currentOptions, [$addonEntry]);
        } else {
            $currentOptions[] = $addonEntry;
        }

        // BUILD STRING
        $optionsStr = implode('|', $currentOptions);

        // CALL API (IMPORTANT FIX)
        $postData = http_build_query([
            'code' => $itemCode,
            'qty' => $qty,
            'options' => $optionsStr
        ]);

        $this->exotic_api_call('/cart/add', 'POST', [], $postData);

        header("Location: ?page=pos_register");
        exit;
    }

    public function change_qty()
    {
        $cartref = $_POST['cartref'] ?? '';
        $qty = (int)($_POST['newqty'] ?? 1);

        $cartData = $this->get_cart();
        $itemCode = '';
        foreach ($cartData['items'] ?? [] as $item) {
            if (($item['cartref'] ?? '') === $cartref) {
                $itemCode = trim((string)($item['item_code'] ?? ''));
                break;
            }
        }
        if ($itemCode !== '') {
            global $conn;
            $stockErr = $this->validateQtyAgainstWarehouse($conn, $itemCode, $qty);
            if ($stockErr !== null) {
                $_SESSION['cart_error'] = $stockErr;
                header("Location: ?page=pos_register");
                exit;
            }
        }

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


    /**
     * Documented endpoint: GET https://www.exoticindia.com/cart/addcoupon?couponid=...
     * Response includes discountcoupondetails for subsequent cart query strings.
     * `/cart/addcoupon` may return a JSON object (preferred) or a JSON-encoded string
     * like "CODE|P|10". exotic_api_call() only keeps decoded arrays, so plain strings
     * must be normalized here or the coupon is silently ignored.
     */
    private function normalizeCouponApiResponse(array $apiResult): array
    {
        $data = $apiResult['data'] ?? [];
        if (is_array($data) && $data !== []) {
            return $data;
        }
        $raw = (string)($apiResult['raw'] ?? '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        if (is_string($decoded) && $decoded !== '') {
            return ['discountcoupondetails' => $decoded];
        }
        return is_array($decoded) ? $decoded : [];
    }

    public function apply_coupon()
    {
        $couponId = trim((string)($_POST['coupon'] ?? ''));

        if ($couponId === '') {
            $_SESSION['coupon_message'] = 'Coupon code required';
            $_SESSION['coupon_status'] = 'error';
            header('Location: ?page=pos_register');
            exit;
        }

        $couponQuery = ['couponid' => $couponId];
        $couponUrl = 'https://www.exoticindia.com/cart/addcoupon?' . http_build_query($couponQuery);

        $result = $this->exotic_api_call(
            '/cart/addcoupon',
            'GET',
            $couponQuery,
            null,
            'https://www.exoticindia.com'
        );

        $httpCode = (int)($result['code'] ?? 0);
        $httpOk = $httpCode >= 200 && $httpCode < 300;

        $response = $this->normalizeCouponApiResponse($result);
        $apiError = trim((string)($response['error'] ?? ''));

        $fallbackProbe = null;
        if ($httpOk && ($response === [] || $apiError !== '')) {
            $probeQuery = ['discountcoupondetails' => $couponId];
            $probeResult = $this->exotic_api_call('/cart/retrieve', 'GET', $probeQuery);
            $probeData = $probeResult['data'] ?? [];
            $probeError = trim((string)($probeData['error'] ?? ($probeData['message'] ?? '')));
            $probeLooksValid = (
                ((int)($probeResult['code'] ?? 0) >= 200 && (int)($probeResult['code'] ?? 0) < 300)
                && $probeError === ''
                && (!empty($probeData['checkoutdata']) || !empty($probeData['cartitems']))
            );
            if ($probeLooksValid) {
                $response = ['discountcoupondetails' => $couponId];
                $apiError = '';
            }
            $fallbackProbe = [
                'used_entered_coupon_as_discountcoupondetails' => true,
                'request' => [
                    'method' => 'GET',
                    'url' => 'https://www.exoticindia.com/api/cart/retrieve?' . http_build_query($probeQuery),
                    'query_params' => $probeQuery,
                ],
                'http_code' => (int)($probeResult['code'] ?? 0),
                'response_raw' => (string)($probeResult['raw'] ?? ''),
                'response_error' => $probeError,
                'accepted' => $probeLooksValid,
            ];
        }

        $_SESSION['pos_coupon_api_debug'] = [
            'timestamp' => date('c'),
            'request' => [
                'method' => 'GET',
                'url' => $couponUrl,
                'query_params' => $couponQuery,
            ],
            'http_code' => $httpCode,
            'response_body_raw' => (string)($result['raw'] ?? ''),
            'response_normalized' => $response,
        ];
        if ($fallbackProbe !== null) {
            $_SESSION['pos_coupon_api_debug']['fallback_probe'] = $fallbackProbe;
        }

        if ($httpOk && $apiError === '' && $response !== []) {
            $_SESSION['discount_coupon'] = $response;
            $_SESSION['coupon_message'] = 'Coupon applied successfully';
            $_SESSION['coupon_status'] = 'success';
        } else {
            $_SESSION['coupon_message'] = $apiError !== ''
                ? $apiError
                : (!$httpOk
                    ? ('Coupon request failed (HTTP ' . $httpCode . ')')
                    : 'Invalid or expired coupon');
            $_SESSION['coupon_status'] = 'error';
        }

        header('Location: ?page=pos_register');
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

    private function buildStorePaymentDetails(string $paymentType, string $transactionId): string
    {
        global $conn;

        $wid = (int)($_SESSION['warehouse_id'] ?? 0);
        $storeId = 'store';
        if ($conn instanceof mysqli) {
            require_once __DIR__ . '/../helpers/pos_payment_receipt.php';
            $storeId = pos_payment_resolve_short_code_for_warehouse($conn, $wid);
        }

        // Required format: STORE_ID|PAYMENT_MODE|TRANSACTION_ID (STORE_ID = exotic_address.short_code)
        // If no transaction ID is provided (cash/offline etc.), send store.<UTC TIMESTAMP>.
        $effectiveTransactionId = $transactionId !== ''
            ? $transactionId
            : ('store.' . gmdate('YmdHis'));

        return $storeId . '|' . $paymentType . '|' . $effectiveTransactionId;
    }

    private function extractOrderIdFromCreateResponse(array $response): string
    {
        foreach (['orderid', 'order_id', 'order_no', 'id'] as $key) {
            if (!empty($response[$key])) {
                return (string)$response[$key];
            }
        }

        if (!empty($response['order']) && is_array($response['order'])) {
            foreach (['orderid', 'order_id', 'order_no', 'id'] as $key) {
                if (!empty($response['order'][$key])) {
                    return (string)$response['order'][$key];
                }
            }
        }

        return '';
    }

    /**
     * Resolve billing/shipping for POS order/create from:
     * - latest vp_order_info row for existing customer
     * - $_SESSION['pos_customer_form'] for new customers
     * - optional confirm-address POST override
     */
    private function resolveCustomerBillingShippingForOrder(mysqli $conn): array
    {
        $billing = [];
        $shipping = [];

        $rawCustomerId = $_POST['customer_id'] ?? null;
        if ($rawCustomerId !== null && $rawCustomerId !== '') {
            $customerId = (int)$rawCustomerId;
        } else {
            $customerId = (int)($_SESSION['pos_customer_id'] ?? 0);
        }

        // Existing customer: pull last known billing/shipping from vp_order_info
        if ($customerId > 0) {
            $stmt = $conn->prepare("SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->bind_param("i", $customerId);
            $stmt->execute();
            $info = $stmt->get_result()->fetch_assoc();

            if ($info) {
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
                    "gstin" => $info['gstin'],
                ];

                $shipping = [
                    "sname" => trim($info['shipping_first_name'] . " " . $info['shipping_last_name']),
                    "saddress1" => $info['shipping_address_line1'],
                    "saddress2" => $info['shipping_address_line2'],
                    "scity" => $info['shipping_city'],
                    "sstate" => $info['shipping_state'],
                    "szip" => $info['shipping_zipcode'],
                    "scountry" => $info['shipping_country'] ?: 'IN',
                    "sphone" => $info['shipping_mobile'],
                ];
            }
        }

        // New customer form fallback
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
                "gstin" => trim($form['gstin'] ?? ''),
            ];

            $shipping = [
                "sname" => trim(($form['shipping_first_name'] ?? '') . " " . ($form['shipping_last_name'] ?? '')),
                "saddress1" => trim($form['shipping_address_line1'] ?? ''),
                "saddress2" => trim($form['shipping_address_line2'] ?? ''),
                "scity" => trim($form['shipping_city'] ?? ''),
                "sstate" => trim($form['shipping_state'] ?? ''),
                "szip" => trim($form['shipping_zipcode'] ?? ''),
                "scountry" => "IN",
                "sphone" => trim($form['shipping_mobile'] ?? ''),
            ];
        }

        // Confirmation popup override
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
                "first_name" => trim((string)($_POST['confirm_first_name'] ?? ($billing['first_name'] ?? ''))),
                "last_name" => trim((string)($_POST['confirm_last_name'] ?? ($billing['last_name'] ?? ''))),
                "email" => trim((string)($_POST['confirm_email'] ?? ($billing['email'] ?? ''))),
                "phone" => trim((string)($_POST['confirm_phone'] ?? ($billing['phone'] ?? ''))),
                "address1" => trim((string)($_POST['confirm_address1'] ?? ($billing['address1'] ?? ''))),
                "address2" => trim((string)($_POST['confirm_address2'] ?? ($billing['address2'] ?? ''))),
                "city" => trim((string)($_POST['confirm_city'] ?? ($billing['city'] ?? ''))),
                "state" => trim((string)($_POST['confirm_state'] ?? ($billing['state'] ?? ''))),
                "zip" => trim((string)($_POST['confirm_zip'] ?? ($billing['zip'] ?? ''))),
                "country" => trim((string)($_POST['confirm_country'] ?? ($billing['country'] ?? 'IN'))),
                "gstin" => trim((string)($_POST['confirm_gstin'] ?? ($billing['gstin'] ?? ''))),
            ];

            $shipping = [
                "sname" => $resolvedShippingName,
                "saddress1" => trim((string)($_POST['confirm_saddress1'] ?? ($shipping['saddress1'] ?? ''))),
                "saddress2" => trim((string)($_POST['confirm_saddress2'] ?? ($shipping['saddress2'] ?? ''))),
                "scity" => trim((string)($_POST['confirm_scity'] ?? ($shipping['scity'] ?? ''))),
                "sstate" => trim((string)($_POST['confirm_sstate'] ?? ($shipping['sstate'] ?? ''))),
                "szip" => trim((string)($_POST['confirm_szip'] ?? ($shipping['szip'] ?? ''))),
                "scountry" => trim((string)($_POST['confirm_scountry'] ?? ($shipping['scountry'] ?? 'IN'))),
                "sphone" => trim((string)($_POST['confirm_sphone'] ?? ($shipping['sphone'] ?? ''))),
            ];
        }

        // Default shipping to billing when shipping rows are blank (walk-in / same-as-billing).
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

        // Validation
        $bFirst = trim((string)($billing['first_name'] ?? ''));
        $bPhone = trim((string)($billing['phone'] ?? ''));
        $bState = trim((string)($billing['state'] ?? ''));
        $bZip = trim((string)($billing['zip'] ?? ''));
        if ($bFirst === '' || $bPhone === '' || $bState === '' || $bZip === '') {
            return ['success' => false, 'message' => 'Billing missing'];
        }

        $sName = trim((string)($shipping['sname'] ?? ''));
        $sPhone = trim((string)($shipping['sphone'] ?? ''));
        $sState = trim((string)($shipping['sstate'] ?? ''));
        if ($sName === '' || $sPhone === '' || $sState === '') {
            return ['success' => false, 'message' => 'Shipping missing'];
        }

        return ['success' => true, 'billing' => $billing, 'shipping' => $shipping];
    }

    private function resolveCodForPayment(string $paymentType, array $cartSnapshot): array
    {
        $codchargesSnap = (float)($cartSnapshot['codcharges'] ?? 0);
        if ($paymentType === 'cod' && $codchargesSnap > 0) {
            return ['cod' => '1', 'codcharges' => (string)$codchargesSnap];
        }

        return ['cod' => '0', 'codcharges' => '0'];
    }

    /**
     * Map POS Payment Mode field values to /order/create payment_type slugs.
     * Ensures store_payment_details middle segment matches the selected mode (not forced offline).
     */
    private function normalizePosPaymentTypeForOrderCreate(string $raw): string
    {
        $allowed = [
            'offline',
            'cc',
            'razorpay',
            'cod',
            'bank_transfer',
            'pos_machine',
            'specialpay',
            'cheque',
            'demand_draft',
            'upi',
        ];

        $t = strtolower(trim($raw));
        if ($t === '') {
            return 'offline';
        }

        $labelMap = [
            'cash' => 'offline',
            'card' => 'cc',
        ];
        if (isset($labelMap[$t])) {
            $t = $labelMap[$t];
        }

        if (in_array($t, $allowed, true)) {
            return $t;
        }

        return 'offline';
    }

    private function buildRazorpayAndCardFromPost(): array
    {
        $razorpay = [
            "razorpay_order_id" => $_POST['razorpay_order_id'] ?? '',
            "razorpay_payment_id" => $_POST['razorpay_payment_id'] ?? '',
            "razorpay_signature" => $_POST['razorpay_signature'] ?? '',
            "magiccheckout_done" => $_POST['magiccheckout_done'] ?? ''
        ];

        $card = [
            "cardnumber" => $_POST['cardnumber'] ?? '',
            "cardexpmonth" => $_POST['cardexpmonth'] ?? '',
            "cardexpyear" => $_POST['cardexpyear'] ?? '',
            "card_cvv" => $_POST['card_cvv'] ?? ''
        ];

        return ['razorpay' => $razorpay, 'card' => $card];
    }

    /**
     * /order/create expects coupon id in query param (e.g. APPTEST), not expanded
     * cart string variants like APPTEST|d|1.
     */
    private function normalizeCouponForOrderCreateQuery(string $couponRaw): string
    {
        $s = trim($couponRaw);
        if ($s === '') {
            return '';
        }
        if (strpos($s, '|') !== false) {
            $parts = explode('|', $s);
            return trim((string)($parts[0] ?? ''));
        }

        return $s;
    }

    public function create_order()
    {
        global $conn;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        $paymentType = $this->normalizePosPaymentTypeForOrderCreate((string)($_POST['payment_type'] ?? 'offline'));
        $paymentStage = $_POST['payment_stage'] ?? 'final';
        if (!in_array($paymentStage, ['final', 'partial', 'advance'], true)) {
            $paymentStage = 'final';
        }
        $note = $_POST['note'] ?? '';

        $transactionId = trim((string)($_POST['transaction_id'] ?? ''));

        if ($paymentType === 'razorpay' && $transactionId === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Razorpay payment requires a transaction ID.',
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        /* ================= USER / STORE ================= */
        $store_payment_details = $this->buildStorePaymentDetails($paymentType, $transactionId);

        /* ================= CART (GET /cart/retrieve) ================= */
        $cartData = $this->get_cart();

        if (!$this->cartHasUsableCheckoutdata($cartData)) {
            echo json_encode([
                "success" => false,
                "message" => "Cart empty"
            ]);
            exit;
        }

        /* ================= CUSTOMER ================= */
        // Resolve customer billing/shipping (includes validation + confirm popup overrides).
        $rawCustomerId = $_POST['customer_id'] ?? null;
        if ($rawCustomerId !== null && $rawCustomerId !== '') {
            $customerId = (int)$rawCustomerId;
        } else {
            $customerId = (int)($_SESSION['pos_customer_id'] ?? 0);
        }

        $resolvedCustomer = $this->resolveCustomerBillingShippingForOrder($conn);
        if (empty($resolvedCustomer['success'])) {
            echo json_encode([
                "success" => false,
                "message" => $resolvedCustomer['message'] ?? 'Customer missing',
            ]);
            exit;
        }

        $billing = $resolvedCustomer['billing'];
        $shipping = $resolvedCustomer['shipping'];

        /* ================= Fresh cart snapshot for order/create (checkoutdata must match latest retrieve) ================= */
        $orderCartSnapshot = $this->get_cart();
        if (!$this->cartHasUsableCheckoutdata($orderCartSnapshot)) {
            echo json_encode([
                "success" => false,
                "message" => "Cart empty — refresh cart and try again.",
            ]);
            exit;
        }

        /* ================= COD ================= */
        $codResolved = $this->resolveCodForPayment($paymentType, $orderCartSnapshot);
        $cod = $codResolved['cod'];
        $codCharges = $codResolved['codcharges'];

        /* ================= RAZORPAY / CARD ================= */
        $paymentExtra = $this->buildRazorpayAndCardFromPost();
        $razorpay = $paymentExtra['razorpay'];
        $card = $paymentExtra['card'];
        if ($paymentType === 'razorpay' && $transactionId !== '') {
            $razorpay['razorpay_payment_id'] = $transactionId;
        }

        /* ================= FINAL DATA (checkoutdata sourced from GET /cart/retrieve via get_cart()) ================= */
        $postData = array_merge([
            "payment_type" => $paymentType,
            "buynow" => "0",
            "checkoutdata" => $orderCartSnapshot['checkoutdata'] ?? '',
            "cod" => $cod,
            "codcharges" => $codCharges,
            "store_payment_details" => $store_payment_details
        ], $billing, $shipping, $razorpay, $card);
        $postData['checkoutdata'] = $this->normalizeCheckoutdataToken($postData['checkoutdata'] ?? '');

        $effectiveCouponRaw = trim((string)($orderCartSnapshot['discountcoupondetails_effective'] ?? ''));
        $effectiveCoupon = $this->normalizeCouponForOrderCreateQuery($effectiveCouponRaw);
        $effectiveVoucher = trim((string)($orderCartSnapshot['giftvoucherdetails_effective'] ?? ''));
        $orderCreateQuery = [];
        if ($effectiveCoupon !== '') {
            $orderCreateQuery['discountcoupondetails'] = $effectiveCoupon;
        }
        if ($effectiveVoucher !== '') {
            $orderCreateQuery['giftvoucherdetails'] = $effectiveVoucher;
        }

        $apiResult = $this->exotic_api_call('/order/create', 'POST', $orderCreateQuery, $postData);
        $retryMeta = [
            'attempted_no_coupon_retry' => false,
        ];
        // If still failing with "Missing order data", retry once with coupon/voucher removed
        // to eliminate cart-token vs coupon-query mismatch.
        $finalResponse = $apiResult['data'] ?? [];
        $finalErr = trim((string)($finalResponse['error'] ?? $finalResponse['message'] ?? ''));
        $finalHttp = (int)($apiResult['code'] ?? 0);
        $shouldRetryWithoutCoupon = (
            !empty($orderCreateQuery)
            && $finalHttp >= 400
            && stripos($finalErr, 'missing order data') !== false
        );
        if ($shouldRetryWithoutCoupon) {
            $retryMeta['attempted_no_coupon_retry'] = true;
            $retryMeta['no_coupon_retry_reason'] = $finalErr;
            $retryNoCouponQuery = [];
            $retryNoCouponResult = $this->exotic_api_call('/order/create', 'POST', $retryNoCouponQuery, $postData);
            $retryNoCouponResponse = $retryNoCouponResult['data'] ?? [];
            $retryNoCouponHttp = (int)($retryNoCouponResult['code'] ?? 0);
            $retryNoCouponOk = $retryNoCouponHttp < 400 && !empty($retryNoCouponResponse) && empty($retryNoCouponResponse['error']);
            if ($retryNoCouponOk) {
                $orderCreateQuery = $retryNoCouponQuery;
                $apiResult = $retryNoCouponResult;
                $retryMeta['no_coupon_retry_used_for_final_result'] = true;
            } else {
                $retryMeta['no_coupon_retry_used_for_final_result'] = false;
                $retryMeta['no_coupon_retry_http_code'] = $retryNoCouponHttp;
                $retryMeta['no_coupon_retry_error'] = (string)($retryNoCouponResponse['error'] ?? $retryNoCouponResponse['message'] ?? '');
            }
        }
        $orderApiDebug = $this->buildOrderCreateApiDebug(
            $orderCreateQuery,
            $postData,
            $apiResult,
            [
                'payment_type' => $paymentType,
                'payment_stage' => $paymentStage,
                'amount' => $_POST['amount'] ?? '',
                'transaction_id' => $transactionId,
                'customer_id' => (int)$customerId,
                'note' => $note,
                'cart_api_checkoutdata' => $postData['checkoutdata'] ?? '',
                'retry_meta' => $retryMeta,
            ]
        );
        $_SESSION['pos_order_create_api_debug'] = $orderApiDebug;

        $response = $apiResult['data'] ?? [];
        $httpCode = (int)($apiResult['code'] ?? 0);

        if ($httpCode >= 400 || empty($response) || !empty($response['error'])) {
            echo json_encode([
                "success" => false,
                "message" => $response['error'] ?? $response['message'] ?? "Order create API failed",
                "order_api_debug" => $orderApiDebug,
                "api_response" => $response,
            ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
            exit;
        }

        $orderId = $this->extractOrderIdFromCreateResponse($response);

        $posReceiptMeta = [];
        if ($conn instanceof mysqli && $orderId !== null && $orderId !== '') {
            try {
                require_once __DIR__ . '/../helpers/pos_payment_receipt.php';
                $wid = (int)($_SESSION['warehouse_id'] ?? 0);
                $short = pos_payment_resolve_short_code_for_warehouse($conn, $wid);
                $receiptNumber = pos_payment_generate_next_receipt_number($conn, $short);
                $amountPost = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
                $userIdIns = pos_payment_resolve_session_user_id();
                $onStrIns = trim((string)$orderId);

                $insertRes = pos_payment_insert_row(
                    $conn,
                    $onStrIns,
                    $receiptNumber,
                    (int)$customerId,
                    (string)$paymentStage,
                    (string)$paymentType,
                    $amountPost,
                    $transactionId,
                    (string)$note,
                    $userIdIns,
                    $wid,
                    true
                );
                $posReceiptMeta['receipt_number'] = $receiptNumber;
                $posReceiptMeta['payment_id'] = $insertRes['payment_id'];
                $posReceiptMeta['warehouse_id_used'] = $insertRes['warehouse_id_used'];
                if ($insertRes['success']) {
                    $posReceiptMeta['order_amount'] = $insertRes['order_amount'] ?? null;
                    $posReceiptMeta['pending_amount'] = $insertRes['pending_amount'] ?? null;
                }
                if (!$insertRes['success']) {
                    $posReceiptMeta['payment_receipt_saved'] = false;
                    $posReceiptMeta['pos_payment_insert_sql_error'] = (string)($insertRes['error'] ?? 'insert failed');
                } else {
                    $posReceiptMeta['payment_receipt_saved'] = true;
                }
                $orderApiDebug['pos_payment_insert'] = [
                    'attempted' => true,
                    'order_number' => $onStrIns,
                    'receipt_number' => $receiptNumber,
                    'success' => $insertRes['success'],
                    'warehouse_id_used' => $insertRes['warehouse_id_used'],
                    'payment_id' => $insertRes['payment_id'],
                    'error' => $insertRes['error'],
                    'order_amount' => $insertRes['order_amount'] ?? null,
                    'pending_amount' => $insertRes['pending_amount'] ?? null,
                ];
                $_SESSION['pos_order_create_api_debug'] = $orderApiDebug;
            } catch (Throwable $e) {
                $posReceiptMeta['payment_receipt_saved'] = false;
                $posReceiptMeta['pos_payment_insert_exception'] = $e->getMessage();
                $orderApiDebug['pos_payment_insert'] = [
                    'attempted' => true,
                    'success' => false,
                    'exception' => $e->getMessage(),
                ];
                $_SESSION['pos_order_create_api_debug'] = $orderApiDebug;
            }
        }

        echo json_encode(array_merge([
            "success" => true,
            "message" => $response['message'] ?? "Order created successfully",
            "order_id" => $orderId,
            "payment_summary" => [
                "payment_type" => (string)$paymentType,
                "payment_stage" => (string)$paymentStage,
                "amount" => (string)($_POST['amount'] ?? ''),
                "transaction_id" => (string)$transactionId,
                "store_payment_details" => (string)$store_payment_details,
            ],
            "api_response" => $response,
            "order_api_debug" => $orderApiDebug,
        ], $posReceiptMeta), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    /** @return float[] */
    private function parseGstSplitForReceiptLine(float $gstPercent, float $gstAmountFull, bool $intraState): array
    {
        if ($gstAmountFull <= 0 || $gstPercent <= 0) {
            return [
                'sgst_rate' => 0.0,
                'sgst_amt' => 0.0,
                'cgst_rate' => 0.0,
                'cgst_amt' => 0.0,
                'igst_rate' => 0.0,
                'igst_amt' => 0.0,
            ];
        }
        $halfPct = round($gstPercent / 2, 3);
        if ($intraState) {
            $halfAmt = round($gstAmountFull / 2, 2);
            return [
                'sgst_rate' => $halfPct,
                'sgst_amt' => $halfAmt,
                'cgst_rate' => $halfPct,
                'cgst_amt' => $halfAmt,
                'igst_rate' => 0.0,
                'igst_amt' => 0.0,
            ];
        }

        return [
            'sgst_rate' => 0.0,
            'sgst_amt' => 0.0,
            'cgst_rate' => 0.0,
            'cgst_amt' => 0.0,
            'igst_rate' => $gstPercent,
            'igst_amt' => round($gstAmountFull, 2),
        ];
    }

    /**
     * Data for printable POS receipt (items from vp_orders after import; addresses from vp_order_info).
     *
     * @return array<string, mixed>
     */
    private function buildPaymentReceiptContext(
        $conn,
        string $orderId,
        string $paymentType,
        string $paymentStage,
        string $paymentModeLabel,
        string $amountStr,
        string $transactionId,
        string $receiptNumber,
        string $receiptDateFormatted,
        string $warehouseName
    ): array {
        $defaults = [
            'receipt_has_order_data' => false,
            'receipt_lines' => [],
            'receipt_billing_block' => [],
            'receipt_shipping_block' => [],
            'receipt_company_legal_name' => 'EXOTIC INDIA ART PVT LTD',
            'receipt_company_gstin' => '07AADCE1400C1ZJ',
            'receipt_company_pan' => 'AADCE1400C',
            'receipt_company_tagline' => 'AUTHENTIC · CURATED · HERITAGE',
            'receipt_office_footer' => 'EXOTIC INDIA ART PVT LTD, A-16/1, Wazirpur Industrial Estate, Delhi 110052, India',
            'receipt_place_of_supply' => '—',
            'receipt_banner_text' => '',
            'receipt_title_main' => 'PAYMENT RECEIPT',
            'receipt_payment_stage_label' => ucfirst(trim($paymentStage) !== '' ? $paymentStage : 'final'),
            'receipt_payment_mode_detail' => $paymentModeLabel,
            'receipt_subtotal_goods' => 0.0,
            'receipt_gst_total' => 0.0,
            'receipt_agg_sgst' => 0.0,
            'receipt_agg_cgst' => 0.0,
            'receipt_agg_igst' => 0.0,
            'receipt_qty_total' => 0.0,
            'receipt_coupon_discount' => 0.0,
            'receipt_gift_discount' => 0.0,
            'receipt_cash_discount' => 0.0,
            'receipt_grand_total' => 0.0,
            'receipt_amount_received' => 0.0,
            'receipt_pending_amount' => 0.0,
            'receipt_amount_in_words' => '',
            'receipt_terms' => [
                'E.&O.E. — All refunds as per applicable company policies.',
                'Disputes subject to jurisdiction of Courts at Delhi, India.',
                'This document is digitally generated during POS checkout.',
            ],
            'receipt_signature_date' => '',
        ];

        if (!$conn instanceof mysqli) {
            $defaults['receipt_banner_text'] = 'Receipt details could not load (database connection unavailable).';

            return $defaults;
        }

        $footerFromDb = $this->getDefaultExoticAddressFooterString($conn);
        if ($footerFromDb !== '') {
            $defaults['receipt_office_footer'] = $footerFromDb;
        }

        if ($orderId === '') {
            $defaults['receipt_banner_text'] = 'Payment acknowledgement (order reference pending).';
            try {
                $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                $defaults['receipt_signature_date'] = $dt->format('d-m-Y');
            } catch (\Throwable $e) {
                $defaults['receipt_signature_date'] = date('d-m-Y');
            }

            return $defaults;
        }

        try {
            $dtSign = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
            $defaults['receipt_signature_date'] = $dtSign->format('d-m-Y');
        } catch (\Throwable $e) {
            $defaults['receipt_signature_date'] = date('d-m-Y');
        }

        $stageLc = strtolower(trim($paymentStage));
        $defaults['receipt_title_main'] = $stageLc === 'advance'
            ? 'ADVANCE RECEIPT'
            : 'PAYMENT RECEIPT';

        $amtReceived = is_numeric(trim($amountStr)) ? (float)trim($amountStr) : 0.0;

        require_once __DIR__ . '/../models/order/order.php';
        $ordersModel = new Order($conn);

        /** @var array<int,array<string,mixed>>|null $lines */
        $lines = $ordersModel->getOrderByOrderNumber($orderId);
        if (!is_array($lines)) {
            $lines = [];
        }

        /** @var array<string,mixed>|null $addr */
        $addr = $ordersModel->getAddressInfoByOrderNumber($orderId);
        if (!is_array($addr)) {
            $addr = [];
        }

        $billState = trim(strtolower((string)($addr['state'] ?? '')));
        $shipState = trim(strtolower((string)($addr['shipping_state'] ?? '')));
        $billCountry = trim(strtoupper((string)($addr['country'] ?? 'IN')));
        $shipCountry = trim(strtoupper((string)($addr['shipping_country'] ?? '')));
        if ($billCountry === '') {
            $billCountry = 'IN';
        }
        if ($shipCountry === '') {
            $shipCountry = 'IN';
        }
        $intraSameState = (
            ($billCountry === 'IN' || $billCountry === 'INDIA')
            && ($shipCountry === 'IN' || $shipCountry === 'INDIA')
            && $billState !== ''
            && $shipState !== ''
            && $billState === $shipState
        ) || (
            ($billCountry === 'IN' || $billCountry === 'INDIA')
            && ($shipCountry === 'IN' || $shipCountry === 'INDIA')
            && $billState === ''
            && $shipState === ''
        );

        $pos = '';
        if ($shipState !== '') {
            $pos = strtoupper(trim((string)($addr['shipping_state'] ?? '')));
        }
        elseif ($billState !== '') {
            $pos = strtoupper(trim((string)($addr['state'] ?? '')));
        }
        elseif ($warehouseName !== '' && $warehouseName !== '—') {
            $pos = trim($warehouseName);
        }
        $defaults['receipt_place_of_supply'] = $pos !== '' ? $pos : '—';

        $billingName = trim(trim((string)($addr['first_name'] ?? '')) . ' ' . trim((string)($addr['last_name'] ?? '')));
        if ($billingName === '') {
            $billingName = '—';
        }
        $billingLines = [$billingName];
        if (trim((string)($addr['company'] ?? '')) !== '') {
            $billingLines[] = trim((string)$addr['company']);
        }
        $a1 = trim((string)($addr['address_line1'] ?? ''));
        $a2 = trim((string)($addr['address_line2'] ?? ''));
        if ($a1 !== '') {
            $billingLines[] = $a1;
        }
        if ($a2 !== '') {
            $billingLines[] = $a2;
        }
        $bcity = trim((string)($addr['city'] ?? ''));
        $bstate = trim((string)($addr['state'] ?? ''));
        $bz = trim((string)($addr['zipcode'] ?? ''));
        if ($bcity !== '' || $bstate !== '') {
            $billingLines[] = trim(implode(', ', array_filter([$bcity, $bstate], static fn ($x) => $x !== '')));
        }
        if ($bz !== '') {
            $billingLines[] = 'Pin Code : ' . $bz;
        }
        if (trim((string)($addr['mobile'] ?? '')) !== '') {
            $billingLines[] = 'Tel : ' . trim((string)$addr['mobile']);
        }
        if (trim((string)($addr['gstin'] ?? '')) !== '') {
            $billingLines[] = 'GSTIN : ' . trim((string)$addr['gstin']);
        }
        $defaults['receipt_billing_block'] = $billingLines;

        $shippingName = trim(trim((string)($addr['shipping_first_name'] ?? '')) . ' ' . trim((string)($addr['shipping_last_name'] ?? '')));
        if ($shippingName === '') {
            $shippingName = $billingName;
        }
        $shippingLines = [$shippingName];
        if (trim((string)($addr['shipping_company'] ?? '')) !== '') {
            $shippingLines[] = trim((string)$addr['shipping_company']);
        }
        $sa1 = trim((string)($addr['shipping_address_line1'] ?? ''));
        $sa2 = trim((string)($addr['shipping_address_line2'] ?? ''));
        if ($sa1 !== '') {
            $shippingLines[] = $sa1;
        }
        if ($sa2 !== '') {
            $shippingLines[] = $sa2;
        }
        $scity = trim((string)($addr['shipping_city'] ?? ''));
        $sstate = trim((string)($addr['shipping_state'] ?? ''));
        $sz = trim((string)($addr['shipping_zipcode'] ?? ''));
        if ($scity !== '' || $sstate !== '') {
            $shippingLines[] = trim(implode(', ', array_filter([$scity, $sstate], static fn ($x) => $x !== '')));
        }
        if ($sz !== '') {
            $shippingLines[] = 'Pin Code : ' . $sz;
        }
        if (trim((string)($addr['shipping_mobile'] ?? '')) !== '') {
            $shippingLines[] = 'Tel : ' . trim((string)$addr['shipping_mobile']);
        }

        // Shipping GST often same as billing for B2C; show blank if absent.
        $shippingGst = trim((string)($addr['gstin'] ?? ''));
        if ($shippingGst !== '') {
            $shippingLines[] = 'GSTIN : ' . $shippingGst;
        }
        $defaults['receipt_shipping_block'] = $shippingLines !== [] ? $shippingLines : $billingLines;

        $couponDisc = isset($addr['coupon_reduce']) ? (float)$addr['coupon_reduce'] : 0.0;
        if ($couponDisc <= 0 && isset($lines[0]['coupon_reduce'])) {
            $couponDisc = (float)$lines[0]['coupon_reduce'];
        }
        $giftDisc = isset($addr['giftvoucher_reduce']) ? (float)$addr['giftvoucher_reduce'] : 0.0;
        $addrGrand = isset($addr['total']) ? (float)$addr['total'] : 0.0;

        $receiptLines = [];
        $sumGoods = 0.0;
        $sumGst = 0.0;
        $sumQty = 0.0;
        $sumSgstAmt = 0.0;
        $sumCgstAmt = 0.0;
        $sumIgstAmt = 0.0;

        foreach ($lines as $idx => $row) {
            if (!is_array($row)) {
                continue;
            }
            $sn = $idx + 1;
            $title = trim((string)($row['title'] ?? ''));
            if ($title === '') {
                $title = trim((string)($row['item_code'] ?? 'Item'));
            }
            $options = trim((string)($row['options'] ?? ''));
            if ($options !== '' && strlen($options) < 260) {
                $title .= ' · ' . $options;
            }
            $qty = (float)($row['quantity'] ?? 1);
            if ($qty <= 0) {
                $qty = 1.0;
            }
            $unit = (float)($row['itemprice'] ?? 0);
            $lineTot = (float)($row['finalprice'] ?? 0);
            if ($lineTot <= 0 && $unit > 0) {
                $lineTot = $unit * $qty;
            }
            $gstPct = (float)($row['gst'] ?? 0);
            $gstAmount = $gstPct > 0 ? ($lineTot - ($lineTot / (1 + $gstPct / 100.0))) : 0.0;
            $split = $this->parseGstSplitForReceiptLine($gstPct, $gstAmount, $intraSameState);

            $receiptLines[] = [
                'sn' => $sn,
                'title' => $title,
                'hsn' => trim((string)($row['hsn'] ?? '')),
                'qty' => $qty,
                'unit_price' => $unit,
                'sgst_rate' => $split['sgst_rate'],
                'sgst_amt' => $split['sgst_amt'],
                'cgst_rate' => $split['cgst_rate'],
                'cgst_amt' => $split['cgst_amt'],
                'igst_rate' => $split['igst_rate'],
                'igst_amt' => $split['igst_amt'],
                'line_total' => $lineTot,
            ];

            $sumGoods += $lineTot;
            $sumGst += $gstAmount;
            $sumQty += $qty;
            $sumSgstAmt += $split['sgst_amt'];
            $sumCgstAmt += $split['cgst_amt'];
            $sumIgstAmt += $split['igst_amt'];
        }

        $defaults['receipt_has_order_data'] = $lines !== [];

        // Cash discount placeholder (not modeled on import row).
        $cashDisc = 0.0;
        $grand = $addrGrand > 0 ? $addrGrand : max(0.0, $sumGoods - $couponDisc - $giftDisc - $cashDisc);

        $defaults['receipt_subtotal_goods'] = $sumGoods;
        $defaults['receipt_gst_total'] = $sumGst;
        $defaults['receipt_qty_total'] = $sumQty;
        $defaults['receipt_coupon_discount'] = $couponDisc;
        $defaults['receipt_gift_discount'] = $giftDisc;
        $defaults['receipt_cash_discount'] = $cashDisc;
        $defaults['receipt_grand_total'] = $grand;
        $defaults['receipt_lines'] = $receiptLines;
        $defaults['receipt_agg_sgst'] = $sumSgstAmt;
        $defaults['receipt_agg_cgst'] = $sumCgstAmt;
        $defaults['receipt_agg_igst'] = $sumIgstAmt;

        $defaults['receipt_amount_received'] = $amtReceived;
        $defaults['receipt_pending_amount'] = max(0.0, round($grand - $amtReceived, 2));

        if (function_exists('numberToWords')) {
            $defaults['receipt_amount_in_words'] = 'Rs. ' . numberToWords((float)$grand) . ' Only';
        } else {
            $defaults['receipt_amount_in_words'] = 'Rs. ' . number_format($grand, 2, '.', ',') . ' Only';
        }

        // Row‑4 banner: same sentence pattern as print template (amount = payment taken on this slip).
        $formattedAmt = number_format($amtReceived, 2, '.', '');
        if ($stageLc === 'advance') {
            $banner = 'Advance for Rs. ' . $formattedAmt . ' received with thanks against following items';
        } elseif ($stageLc === 'partial') {
            $banner = 'Partial payment of Rs. ' . $formattedAmt . ' received with thanks against following items';
        } else {
            $banner = 'Payment of Rs. ' . $formattedAmt . ' received with thanks against following items';
        }
        $defaults['receipt_banner_text'] = $banner;

        return $defaults;
    }

    /**
     * Receipt date in Asia/Kolkata, e.g. "26th Sep 2026".
     */
    private function formatReceiptDateOrdinalIndia(): string
    {
        try {
            $dt = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        } catch (\Throwable $e) {
            $dt = new DateTime('now');
        }

        $day = (int)$dt->format('j');
        if ($day >= 11 && $day <= 13) {
            $suffix = 'th';
        } else {
            switch ($day % 10) {
                case 1:
                    $suffix = 'st';
                    break;
                case 2:
                    $suffix = 'nd';
                    break;
                case 3:
                    $suffix = 'rd';
                    break;
                default:
                    $suffix = 'th';
            }
        }

        return $day . $suffix . ' ' . $dt->format('M Y');
    }

    public function order_confirmation()
    {
        is_login();
        global $conn;

        $orderId = trim((string)($_GET['order_id'] ?? ''));
        $paymentType = trim((string)($_GET['payment_type'] ?? 'offline'));
        $paymentStage = trim((string)($_GET['payment_stage'] ?? 'final'));
        $amount = trim((string)($_GET['amount'] ?? ''));
        $transactionId = trim((string)($_GET['transaction_id'] ?? ''));
        $importStatus = trim((string)($_GET['import_status'] ?? 'unknown'));

        $warehouseName = '';
        if (!empty($_SESSION['warehouse_id']) && $conn) {
            require_once 'models/user/user.php';
            $usersModel = new User($conn);
            $warehouse = $usersModel->getWarehouseById((int)$_SESSION['warehouse_id']);
            $warehouseName = trim((string)($warehouse['address_title'] ?? ''));
            if ($warehouseName === '') {
                $warehouseName = 'Warehouse #' . (int)$_SESSION['warehouse_id'];
            }
        }
        if ($warehouseName === '' && $conn instanceof mysqli) {
            $defWh = $this->getDefaultWarehouseRow($conn);
            if ($defWh !== null) {
                $warehouseName = trim((string)($defWh['address_title'] ?? ''));
            }
        }
        if ($warehouseName === '') {
            $warehouseName = '—';
        }

        $paymentModeLabels = [
            'offline' => 'Cash / Offline',
            'cc' => 'Credit / Debit Card',
            'razorpay' => 'Razorpay',
            'cod' => 'Cash on Delivery',
            'bank_transfer' => 'Bank Transfer',
            'pos_machine' => 'POS Machine',
            'specialpay' => 'Special Payment',
            'cheque' => 'Cheque',
            'demand_draft' => 'Demand Draft',
            'upi' => 'UPI',
        ];
        $paymentModeLabel = $paymentModeLabels[strtolower($paymentType)] ?? ucfirst(str_replace('_', ' ', $paymentType));

        $receiptNumber = $orderId !== ''
            ? 'EI-POS-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $orderId)
            : 'EI-POS-PENDING';

        if ($conn instanceof mysqli && trim($orderId) !== '') {
            $invStmt = $conn->prepare(
                'SELECT receipt_number FROM pos_payments WHERE order_number = ? ORDER BY id DESC LIMIT 1'
            );
            if ($invStmt) {
                $orderKeyInv = trim((string)$orderId);
                $invStmt->bind_param('s', $orderKeyInv);
                $invStmt->execute();
                $invRow = $invStmt->get_result()->fetch_assoc();
                $invStmt->close();
                $invVal = trim((string)($invRow['receipt_number'] ?? ''));
                if ($invVal !== '') {
                    $receiptNumber = $invVal;
                }
            }
        }

        $receiptDateFormatted = $this->formatReceiptDateOrdinalIndia();

        $paymentHistoryQuery = ['page' => 'payments', 'action' => 'list'];
        $paymentHistoryFilterNumber = trim($orderId);
        $paymentHistoryPk = ctype_digit(trim($orderId)) ? (int)$orderId : 0;
        if ($paymentHistoryPk > 0 && $conn instanceof mysqli) {
            $onStmt = $conn->prepare('SELECT order_number FROM vp_orders WHERE id = ? LIMIT 1');
            if ($onStmt) {
                $onStmt->bind_param('i', $paymentHistoryPk);
                $onStmt->execute();
                $onRow = $onStmt->get_result()->fetch_assoc();
                $onStmt->close();
                $resolved = trim((string)($onRow['order_number'] ?? ''));
                if ($resolved !== '') {
                    $paymentHistoryFilterNumber = $resolved;
                }
            }
            $paymentHistoryQuery['order_id'] = (string)$paymentHistoryPk;
        }
        if ($paymentHistoryFilterNumber !== '') {
            $paymentHistoryQuery['order_number'] = $paymentHistoryFilterNumber;
        }
        $paymentHistoryUrl = 'index.php?' . http_build_query($paymentHistoryQuery);

        $receiptContext = $this->buildPaymentReceiptContext(
            $conn,
            $orderId,
            $paymentType,
            $paymentStage,
            $paymentModeLabel,
            $amount,
            $transactionId,
            $receiptNumber,
            $receiptDateFormatted,
            $warehouseName
        );

        // Tax invoice PDF (same as Invoices → Download): only after invoice exists and full payment on this receipt.
        $orderNumberForInvoice = trim((string)$orderId);
        if ($conn instanceof mysqli && $orderNumberForInvoice !== '' && ctype_digit($orderNumberForInvoice)) {
            $onStmt = $conn->prepare('SELECT order_number FROM vp_orders WHERE id = ? LIMIT 1');
            if ($onStmt) {
                $pk = (int)$orderNumberForInvoice;
                $onStmt->bind_param('i', $pk);
                $onStmt->execute();
                $onRow = $onStmt->get_result()->fetch_assoc();
                $onStmt->close();
                $resolvedOn = trim((string)($onRow['order_number'] ?? ''));
                if ($resolvedOn !== '') {
                    $orderNumberForInvoice = $resolvedOn;
                }
            }
        }

        $invoicePdfUrl = '';
        $showInvoicePdfButton = false;
        $invoicePdfDisabledHint = '';
        if ($conn instanceof mysqli && $orderNumberForInvoice !== '') {
            require_once __DIR__ . '/../models/invoice/invoice.php';
            $invoiceModelForPdf = new Invoice($conn);
            $invoiceHdr = $invoiceModelForPdf->getActiveInvoiceForOrderNumber($orderNumberForInvoice);
            $pendingAmt = (float)($receiptContext['receipt_pending_amount'] ?? 0);
            $stageFinal = strtolower(trim($paymentStage)) === 'final';
            $fullyPaid = $pendingAmt <= 0.009;

            if (!empty($invoiceHdr['id']) && $stageFinal && $fullyPaid) {
                $showInvoicePdfButton = true;
                $invoicePdfUrl = 'index.php?page=invoices&action=generate_pdf&invoice_id=' . (int)$invoiceHdr['id'];
            } elseif (empty($invoiceHdr['id'])) {
                $invoicePdfDisabledHint = 'Tax invoice PDF is available after the order is imported and invoiced.';
            } elseif (!$stageFinal || !$fullyPaid) {
                $invoicePdfDisabledHint = 'Print Invoice is enabled when payment stage is Final and pending amount is zero.';
            }
        }

        renderTemplate('views/pos_register/order_confirmation.php', array_merge([
            'order_id' => $orderId,
            'payment_type' => $paymentType,
            'payment_mode_label' => $paymentModeLabel,
            'payment_stage' => $paymentStage,
            'amount' => $amount,
            'transaction_id' => $transactionId,
            'import_status' => $importStatus,
            'invoice_pdf_url' => $invoicePdfUrl,
            'show_invoice_pdf_button' => $showInvoicePdfButton,
            'invoice_pdf_disabled_hint' => $invoicePdfDisabledHint,
            'payment_history_url' => $paymentHistoryUrl,
            'warehouse_name' => $warehouseName,
            'receipt_number' => $receiptNumber,
            'receipt_date_formatted' => $receiptDateFormatted,
        ], $receiptContext));
    }

    public function add_customer()
    {
        global $conn;

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');

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

    public function remove_coupon()
    {
        // Per API docs: delete coupon by omitting discountcoupondetails on cart requests.
        if (isset($_SESSION['discount_coupon'])) {
            unset($_SESSION['discount_coupon']);
        }

        $_SESSION['coupon_message'] = 'Coupon removed';
        $_SESSION['coupon_status'] = 'success';

        header("Location: ?page=pos_register");
        exit;
    }
    public function apply_custom_discount()
    {
        $value = floatval($_POST['value'] ?? 0);
        $type  = $_POST['type'] ?? 'fixed';

        if ($value <= 0) {
            $this->clearBufferedHttpOutput();
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(["success" => false, "message" => "Invalid discount"]);
            exit;
        }

        //  convert % → fixed
        if ($type === 'percent') {
            $cart = $this->get_cart();
            $value = ($cart['subtotal'] * $value) / 100;
        }

        //  store in session
        $_SESSION['custom_discount'] = $value;

        //  apply in API
        $this->exotic_api_call(
            '/cart/addcustomdiscount',
            'GET',
            ['custom_reduce' => $value]
        );

        $this->clearBufferedHttpOutput();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(["success" => true]);
        exit;
    }
    public function remove_custom_discount()
    {
        unset($_SESSION['custom_discount']);

        $this->exotic_api_call(
            '/cart/addcustomdiscount',
            'GET',
            ['custom_reduce' => 0]
        );

        header("Location: ?page=pos_register");
        exit;
    }
    public function apply_gift_voucher()
    {
        $voucherId = $_POST['voucher'] ?? '';

        if (empty($voucherId)) {
            $_SESSION['coupon_message'] = "Voucher code required";
            $_SESSION['coupon_status'] = "error";
            header("Location: ?page=pos_register");
            exit;
        }

        $result = $this->exotic_api_call(
            '/cart/addgiftvoucher',
            'GET',
            [
                'voucherid' => $voucherId
            ]
        );

        $response = $result['data'] ?? [];

        if (!empty($response) && !isset($response['error'])) {

            $_SESSION['gift_voucher'] = $response;

            $_SESSION['coupon_message'] = "Gift voucher applied successfully";
            $_SESSION['coupon_status'] = "success";
        } else {

            $_SESSION['coupon_message'] = $response['error'] ?? "Invalid gift voucher";
            $_SESSION['coupon_status'] = "error";
        }

        header("Location: ?page=pos_register");
        exit;
    }
}
