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

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    public function productsAjax()
    {
        $pageNo  = $_GET['page_no'] ?? 1;
        $perPage = $_GET['per_page'] ?? 12;

        $start  = ($pageNo - 1) * $perPage;
        // Fastest POS: fetch one extra row to decide has_more (avoid COUNT(*))
        $length = $perPage + 1;

        $searchValue = $_GET['search']['value'] ?? '';

        $category    = $_GET['category'] ?? '';
        $productName = $_GET['product_name'] ?? '';
        $productCode = $_GET['product_code'] ?? '';

        $minPrice = $_GET['min_price'] ?? '';
        $maxPrice = $_GET['max_price'] ?? '';

        // SORT
        $sortBy = $_GET['sort_by'] ?? '';

        switch ($sortBy) {
            case 'price_low_high':
                $orderColumn = 'itemprice';
                $orderDir = 'asc';
                break;

            case 'price_high_low':
                $orderColumn = 'itemprice';
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
            $maxPrice
        );

        $rows = $result['data'] ?? [];
        $hasMore = count($rows) > (int)$perPage;
        if ($hasMore) {
            $rows = array_slice($rows, 0, (int)$perPage);
        }

        echo json_encode([
            'data' => $rows,
            'current_page' => $pageNo,
            'has_more' => $hasMore,
            'per_page' => (int)$perPage
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
        ob_clean();
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
                'SELECT id, item_code, sku, title, image, material, size, color, hsn,
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

        // echo '<pre>'; print_r($data['addon_options']); exit;
        $product = [
            'requested_code' => $code,
            'item_code' => $dbItemCode,
            'sku' => $dbSku,
            'title' => $this->mergeProductTextField($data['name'] ?? '', $dbRow['title'] ?? ''),
            'image' => $imageResolved,
            'price' => $data['price'] ?? 0,

            'material' => $this->mergeProductTextField($data['material'] ?? '', $dbRow['material'] ?? ''),
            'size' => $this->mergeProductTextField($data['size'] ?? '', $dbRow['size'] ?? ''),
            'color' => $this->mergeProductTextField($data['color'] ?? '', $dbRow['color'] ?? ''),
            'hsn' => $this->mergeProductTextField($data['hsn'] ?? '', $dbRow['hsn'] ?? ''),

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

        //  IMPORTANT: clear buffer
        ob_clean();

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
            'User-Agent: ExoticPOS/1.0'
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


    public function get_cart()
    {
        $coupon = $_SESSION['discount_coupon']['discountcoupondetails'] ?? '';
        $voucher = $_SESSION['gift_voucher']['giftvoucherdetails'] ?? '';
        $res = $this->exotic_api_call(
            '/cart/retrieve',
            'GET',
            [
                'discountcoupondetails' => $coupon,
                'giftvoucherdetails' => $voucher
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
                $addons = [];

                if (!empty($item['addons_selected']) && is_array($item['addons_selected'])) {
                    foreach ($item['addons_selected'] as $ad) {

                        $cartEntry = '';

                        if (stripos($ad['name'], 'Express') !== false) {
                            $cartEntry = 'OPTIONALS_EXPRESS:_blank_:' . $ad['value'];
                        } else {
                            $cartEntry = 'OPTIONALS_SCULPTURES_LACQUER:_blank_:' . $ad['value'];
                        }

                        $addons[] = [
                            'name' => $ad['name'] ?? '',
                            'value' => (float)($ad['value'] ?? 0),
                            'cart_entry' => $cartEntry
                        ];
                    }
                }

                /*  FIXED HERE */
                // $selectedEntries = [];

                // BUILD SELECTED ADDON ENTRIES
                $selectedEntries = [];
                foreach ($addons as $a) {
                    $selectedEntries[] = $a['cart_entry'];
                }

                // ALWAYS DEFINE THIS
                $all_addons = [];

                // FETCH FROM PRODUCT API
                $productRes = $this->exotic_api_call('/product/code', 'GET', [
                    'code' => $item['code']
                ]);

                if (!empty($productRes['data']['addon_options']['default_options'])) {
                    $all_addons = $productRes['data']['addon_options']['default_options'];
                }

                $items[] = [
                    'item_code' => $item['code'],
                    'cartref' => $item['cartref'],
                    'name' => $item['name'],
                    'imageurl' => $item['imageurl'],
                    'price' => (float)$item['price'],
                    'quantity' => (int)$item['quantity'],
                    'shipping' => $shipping,
                    'shipping_per_unit' => $shipping_per_unit,
                    'shipping_title' => $item['express_shipping_option']['title'] ?? '',
                    'shipping_longtitle' => $item['express_shipping_option']['longtitle'] ?? '',
                    'express_selected' => $expressSelected,
                    'addons' => $addons,
                    'all_addons' => $all_addons, //  NOW ALWAYS ARRAY
                    'selected_entries' => $selectedEntries
                ];

                $subtotal += ((float)$item['price'] * (int)$item['quantity']);

                // Add shipping only if selected
                if ($expressSelected) {
                    $shipping_total += $shipping;
                }
            }
        }

        $codcharges = (float)($data['codcharges_if_chosen'] ?? 0);
        $discount = (float)($data['couponreduction'] ?? 0);
        $gst = (float)($data['gstamount'] ?? 0);
        $custom_discount = (float)($data['customreduction'] ?? 0);
        // $custom_discount = (float)($_SESSION['custom_discount'] ?? 0);
        $total_discount = $discount + $custom_discount;
     $final_subtotal = $subtotal - $total_discount; 
        $grand_total = $subtotal + $shipping_total + $gst - $total_discount;

        // $grand_total = $subtotal + $shipping_total + $gst - $total_discount;
        $grand_total = (float)($data['totalamount'] ?? 0);
        return [ 
            'items' => $items,
            'subtotal' => $final_subtotal,
            'shipping_total' => $shipping_total,
            'gst' => $gst,
            'discount' => $discount,
            'custom_discount' => $custom_discount,
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
        $variation = trim($_POST['variation'] ?? '');
        $options   = $_POST['options'] ?? '';
        $buyNow    = false;
        $optionsArray = $_POST['options'] ?? [];
        $toggleOption = $_POST['toggle_option'] ?? '';
        $checked      = $_POST['checked'] ?? 0;
        // ensure array
        if (!is_array($optionsArray)) {
            $optionsArray = [];
        }

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

        // ✅ FINAL OPTIONS STRING
        if (!empty($optionsArray)) {
            $postArray['options'] = implode(',', $optionsArray);
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


        if (!empty($options)) {
            $postArray['options'] = trim($options);
        }

        $postData = http_build_query($postArray);

        $result = $this->exotic_api_call('/cart/add', 'POST', [], $postData);
        // echo '<pre>';
        // print_r($result);
        // exit;
        if (empty($result['data']['cartref'])) {
            $_SESSION['cart_error'] = $result['data']['error'] ?? 'Cart add failed';
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

            $stmt = $conn->prepare("SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
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
        } else {
            $orderId = $result['data']['orderid'];
            // $orderNumber = $result['data']['ordernumber'] ?? $orderId;
            $orderNumber = $result['data']['orderid'];
            $warehouseId = $_SESSION['warehouse_id'];
            $userId = $_SESSION['user']['id'];
            $paymentStage = $_POST['payment_stage'];
            $paymentType = $_POST['payment_type'];
            $amount = $_POST['amount'];
            $transactionId = $_POST['transaction_id'];
            $note = $_POST['note'];
            $paymentDate = date('Y-m-d');
            $stmt = $conn->prepare("
            INSERT INTO pos_payments 
            (order_id, order_number, customer_id, warehouse_id, user_id,payment_stage, payment_mode, amount, transaction_id, note, payment_date)VALUES (?,?,?,?,?,?,?,?,?,?,?)");

            // $stmt->bind_param(
            //     "isiisssdsss",
            //     $orderId,
            //     $orderNumber,
            //     $customerId,
            //     $_SESSION['warehouse_id'],
            //     $_SESSION['user']['id'],
            //     $_POST['payment_stage'],
            //     $_POST['payment_type'],
            //     $_POST['amount'],
            //     $_POST['transaction_id'],
            //     $_POST['note'],
            //     date('Y-m-d')
            // );
            $stmt->bind_param(
                "isiisssdsss",
                $orderId,
                $orderNumber,
                $customerId,
                $warehouseId,
                $userId,
                $paymentStage,
                $paymentType,
                $amount,
                $transactionId,
                $note,
                $paymentDate
            );
            $stmt->execute();
        }

        unset($_SESSION['discount_coupon']);
        unset($_SESSION['pos_customer_form']);
        unset($_SESSION['pos_customer_id']);
        unset($_SESSION['custom_discount']);
        // echo '<pre>'; print_r($result); exit;
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
        $value = floatval($_POST['value'] ?? 0);
        $type  = $_POST['type'] ?? 'fixed';

        if ($value <= 0) {
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
