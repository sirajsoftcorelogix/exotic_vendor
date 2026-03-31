<?php
class pos
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    // old method (still usable if needed somewhere else)
    public function getProducts()
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE is_active = 1");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * DataTables / AJAX products
     */
    public function getProductsDataTable(
        int $start,
        int $length,
        string $searchValue = '',
        string $productName = '',
        string $orderColumn = 'title',
        string $orderDir = 'asc',
        string $category = '',
        string $productCode = '',
        $minPrice = '',
        $maxPrice = '',
        string $stockFilter = ''
    ): array {

        $warehouseId = $_SESSION['warehouse_id'] ?? 0;

        /* ================= TOTAL PRODUCTS ================= */
        $totalSql = "SELECT COUNT(*) FROM vp_products WHERE is_active = 1";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute();
        $totalStmt->bind_result($recordsTotal);
        $totalStmt->fetch();
        $totalStmt->close();

        /* ================= FILTERS ================= */
        $where  = " WHERE p.is_active = 1 ";
        $params = [];
        $types  = "";

        // CATEGORY
        if (!empty($category) && $category != 'allProducts') {
            $where .= " AND p.groupname = ? ";
            $params[] = $category;
            $types .= "s";
        }

        // PRODUCT NAME
        if ($productName !== '') {
            $where .= " AND (p.title LIKE ? OR p.item_code LIKE ?) ";
            $params[] = "%{$productName}%";
            $params[] = "%{$productName}%";
            $types .= "ss";
        }

        // PRODUCT CODE (NEW)
        if ($productCode !== '') {
            $where .= " AND p.item_code LIKE ? ";
            $params[] = "%{$productCode}%";
            $types .= "s";
        }

        // GLOBAL SEARCH
        if ($searchValue !== '') {
            $where .= " AND (p.item_code LIKE ? OR p.title LIKE ?) ";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $types .= "ss";
        }

        // PRICE FILTER (NEW)
        if ($minPrice !== '') {
            $where .= " AND p.itemprice >= ? ";
            $params[] = $minPrice;
            $types .= "d";
        }

        if ($maxPrice !== '') {
            $where .= " AND p.itemprice <= ? ";
            $params[] = $maxPrice;
            $types .= "d";
        }

        if (!empty($category) && $category != 'allProducts') {
            $where .= " AND p.groupname = ? ";
            $params[] = $category;
            $types .= "s";
        }

        if ($productName !== '') {
            $where .= " AND (p.title LIKE ? OR p.item_code LIKE ?) ";
            $params[] = "%{$productName}%";
            $params[] = "%{$productName}%";
            $types .= "ss";
        }

        if ($searchValue !== '') {
            $where .= " AND (p.item_code LIKE ? OR p.title LIKE ?) ";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $types .= "ss";
        }

        /* ================= ORDER ================= */
        $allowedColumns = [
            'item_code',
            'title',
            'groupname',
            'size',
            'color',
            'image',
            'stock_qty',
            'price'
        ];

        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'title';
        }

        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        /* ================= COUNT FILTERED ================= */
        $countSql = "
    SELECT COUNT(*)
    FROM vp_products p
    JOIN (
        SELECT sm1.product_id, sm1.running_stock
        FROM vp_stock_movements sm1
        JOIN (
            SELECT product_id, MAX(id) AS max_id
            FROM vp_stock_movements
            WHERE warehouse_id = ?
            GROUP BY product_id
        ) latest ON latest.max_id = sm1.id
        WHERE sm1.running_stock > 0
    ) sm ON sm.product_id = p.id
    $where
    ";

        $countStmt = $this->db->prepare($countSql);

        $countTypes = "i" . $types;
        $countParams = array_merge([$warehouseId], $params);

        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $countStmt->bind_result($recordsFiltered);
        $countStmt->fetch();
        $countStmt->close();

        /* ================= DATA QUERY ================= */
        $dataSql = "
    SELECT
        p.id,
        p.item_code,
        p.sku,
        p.material,
        p.title,
        p.groupname,
        p.size,
        p.color,
        p.image,
        p.hsn,
        p.product_weight,
        p.product_weight_unit,
        p.prod_height,
        p.prod_width,
        p.prod_length,
        p.length_unit,
        p.cost_price,
        sm.running_stock AS stock_qty,
        p.itemprice AS price
    FROM vp_products p
    JOIN (
        SELECT sm1.product_id, sm1.running_stock
        FROM vp_stock_movements sm1
        JOIN (
            SELECT product_id, MAX(id) AS max_id
            FROM vp_stock_movements
            WHERE warehouse_id = ?
            GROUP BY product_id
        ) latest ON latest.max_id = sm1.id
        WHERE sm1.running_stock > 0
    ) sm ON sm.product_id = p.id
    $where
    ORDER BY p.$orderColumn $orderDir
    LIMIT ?, ?
    ";

        $dataStmt = $this->db->prepare($dataSql);

        $dataTypes = "i" . $types . "ii";
        $dataParams = array_merge([$warehouseId], $params, [$start, $length]);

        $dataStmt->bind_param($dataTypes, ...$dataParams);
        $dataStmt->execute();

        $result = $dataStmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $dataStmt->close();

        return [
            'recordsTotal'    => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data'            => $rows
        ];
    }
    public function getProductsDataTable_bk(
        int $start,
        int $length,
        string $searchValue = '',
        string $productName = '',
        string $orderColumn = 'title',
        string $orderDir = 'asc',
        string $category = ''
    ): array {

        /* =========================
     * 1) Total records
     * ========================= */
        $totalSql = "SELECT COUNT(*) FROM vp_products WHERE is_active = 1";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute();
        $totalStmt->bind_result($recordsTotal);
        $totalStmt->fetch();
        $totalStmt->close();

        /* =========================
     * 2) Filters
     * ========================= */
        $where  = " WHERE is_active = 1 ";
        $params = [];
        $types  = "";

        // ✅ Category filter (match groupname)
        if (!empty($category) && $category != 'allProducts') {
            $where .= " AND groupname = ? ";
            $params[] = $category;
            $types   .= "s";
        }

        if ($productName !== '') {
            $where .= " AND (title LIKE ? OR item_code LIKE ?) ";
            $params[] = "%{$productName}%";
            $params[] = "%{$productName}%";
            $types   .= "ss";
        }

        if ($searchValue !== '') {
            $where .= " AND (item_code LIKE ? OR title LIKE ?) ";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $types   .= "ss";
        }

        /* =========================
     * 3) Order guard
     * ========================= */
        $allowedColumns = [
            'item_code',
            'title',
            'groupname',     // ✅ allow ordering by groupname too
            'size',
            'color',
            'image',
            'local_stock',
            'itemprice'
        ];

        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'title';
        }

        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        /* =========================
     * 4) Filtered count
     * ========================= */
        $countSql = "SELECT COUNT(*) FROM vp_products $where";
        $countStmt = $this->db->prepare($countSql);

        if (!empty($params)) {
            $countStmt->bind_param($types, ...$params);
        }

        $countStmt->execute();
        $countStmt->bind_result($recordsFiltered);
        $countStmt->fetch();
        $countStmt->close();

        /* =========================
     * 5) Data query
     * ========================= */
        $dataSql = " 
        SELECT
            id,
            item_code,
            sku,
            material,
            title,
            groupname,
            size,
            color,
            image,
            hsn,
            product_weight,
            product_weight_unit,
            prod_height,
            prod_width,
            prod_length,
            length_unit,
            cost_price,
            local_stock AS stock_qty,
            itemprice AS price
        FROM vp_products
        $where
        ORDER BY $orderColumn $orderDir
        LIMIT ?, ?
    ";

        $dataStmt = $this->db->prepare($dataSql);

        // add pagination params
        $paramsWithLimit = $params;
        $paramsWithLimit[] = $start;
        $paramsWithLimit[] = $length;

        $typesWithLimit = $types . "ii";

        $dataStmt->bind_param($typesWithLimit, ...$paramsWithLimit);

        $dataStmt->execute();
        $result = $dataStmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $dataStmt->close();

        return [
            'recordsTotal'    => (int) $recordsTotal,
            'recordsFiltered' => (int) $recordsFiltered,
            'data'            => $rows
        ];
    }

    public function getStockReport(array $filters = []): array
    {
        $warehouseId = isset($filters['warehouse_id']) ? (int)$filters['warehouse_id'] : (isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : 0);
        $search = trim((string)($filters['search'] ?? ''));
        $category = trim((string)($filters['category'] ?? ''));
        $stockStatus = trim((string)($filters['stock_status'] ?? 'all'));
        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 200;
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 1000) {
            $limit = 1000;
        }

        $where = " WHERE sm.warehouse_id = ? AND sm_newer.id IS NULL AND p.is_active = 1 ";
        $params = [$warehouseId];
        $types = "i";

        if ($category !== '' && $category !== 'allProducts') {
            $where .= " AND p.groupname = ? ";
            $params[] = $category;
            $types .= "s";
        }

        if ($search !== '') {
            $where .= " AND (p.item_code LIKE ? OR p.title LIKE ? OR p.sku LIKE ?) ";
            $like = '%' . $search . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= "sss";
        }

        if ($stockStatus === 'out') {
            $where .= " AND sm.running_stock = 0 ";
        } elseif ($stockStatus === 'low') {
            $where .= " AND sm.running_stock BETWEEN 1 AND 5 ";
        } elseif ($stockStatus === 'in') {
            $where .= " AND sm.running_stock > 0 ";
        }

        $sql = "
            SELECT
                p.id,
                p.item_code,
                p.sku,
                p.title,
                p.groupname,
                p.size,
                p.color,
                p.image,
                p.itemprice AS sell_price,
                p.cost_price,
                sm.running_stock AS stock_qty
            FROM vp_stock_movements sm
            LEFT JOIN vp_stock_movements sm_newer
                ON sm.product_id = sm_newer.product_id
               AND sm.warehouse_id = sm_newer.warehouse_id
               AND sm.id < sm_newer.id
            INNER JOIN vp_products p
                ON p.id = sm.product_id
            $where
            ORDER BY stock_qty ASC, p.title ASC
            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
}
