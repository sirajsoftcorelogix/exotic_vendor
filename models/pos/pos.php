<?php

require_once dirname(__DIR__, 2) . '/helpers/stock_report_filters.php';

class pos
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * WHERE fragment: exclude vp_products rows with item_level = parent (grouping SKUs, not sellable variants).
     */
    private function sqlExcludeParentItemLevel(string $tableAlias = 'p'): string
    {
        $col = $tableAlias !== '' ? "{$tableAlias}.item_level" : 'item_level';

        return " AND LOWER(TRIM(IFNULL({$col}, ''))) <> 'parent' ";
    }

    /**
     * India sell price base (ex-GST) for POS — price_india only (never global USD itemprice/finalprice).
     */
    private function sqlPosIndiaSellBaseExpr(string $tableAlias = 'p'): string
    {
        return "COALESCE(NULLIF({$tableAlias}.price_india, 0), 0)";
    }

    /**
     * Book products add flat sourcing + shipping fees on top of GST-inclusive Price India (POS display).
     */
    private function sqlPosBookFeesExpr(string $tableAlias = 'p'): string
    {
        $itemtype = "LOWER(TRIM(IFNULL({$tableAlias}.itemtype, '')))";
        $groupname = "LOWER(TRIM(IFNULL({$tableAlias}.groupname, '')))";
        $isBook = "(
            {$itemtype} = 'book'
            OR {$groupname} IN ('book', '-8')
            OR ({$groupname} <> '' AND {$groupname} <> '0' AND {$groupname} LIKE '%book%')
        )";

        return "CASE WHEN {$isBook}
            THEN (COALESCE({$tableAlias}.sourcingfee, 0) + COALESCE({$tableAlias}.shippingfee, 0))
            ELSE 0 END";
    }

    /** GST-inclusive POS list/preview unit price (India base + book fees when applicable). */
    private function sqlPosDisplaySellPriceExpr(string $tableAlias = 'p'): string
    {
        $baseSell = $this->sqlPosIndiaSellBaseExpr($tableAlias);
        $bookFees = $this->sqlPosBookFeesExpr($tableAlias);

        return "(({$baseSell} * (1 + IFNULL({$tableAlias}.gst, 0) / 100)) + {$bookFees})";
    }

    // old method (still usable if needed somewhere else)
    public function getProducts()
    {
        $excludeParent = $this->sqlExcludeParentItemLevel('');
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE is_active = 1{$excludeParent}");
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

        /* ================= FILTERS ================= */
        $where  = ' WHERE p.is_active = 1 ' . $this->sqlExcludeParentItemLevel('p');
        $params = [];
        $types  = "";

        // CATEGORY
        if (!empty($category) && $category != 'allProducts') {
            if (in_array(strtolower($category), ['virtual_codes', 'virtualcodes', 'virtual_code', 'virtual codes'], true)) {
                $where .= " AND p.title LIKE ? ";
                $params[] = "%Virtual Code%";
                $types .= "s";
            } else {
                $where .= " AND p.groupname = ? ";
                $params[] = $category;
                $types .= "s";
            }
        }

        // PRODUCT NAME / SKU search — multiple items when comma/semicolon/newline/tab present.
        if ($productName !== '') {
            appendPosRegisterSearchFilterSql($where, $params, $types, $productName);
        }

        // PRODUCT CODE
        if ($productCode !== '') {
            $where .= " AND (p.item_code LIKE ? OR p.sku LIKE ?) ";
            $params[] = "%{$productCode}%";
            $params[] = "%{$productCode}%";
            $types .= "ss";
        }

        // GLOBAL SEARCH
        if ($searchValue !== '') {
            $where .= " AND (p.item_code LIKE ? OR p.title LIKE ? OR p.sku LIKE ?) ";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $params[] = "%{$searchValue}%";
            $types .= "sss";
        }

        // List price: GST-inclusive Price India; books also include sourcing + shipping fees.
        $baseSell = $this->sqlPosIndiaSellBaseExpr('p');
        $sellPriceExpr = $this->sqlPosDisplaySellPriceExpr('p');
        if ($minPrice !== '') {
            $where .= " AND {$sellPriceExpr} >= ? ";
            $params[] = $minPrice;
            $types .= "d";
        }

        if ($maxPrice !== '') {
            $where .= " AND {$sellPriceExpr} <= ? ";
            $params[] = $maxPrice;
            $types .= "d";
        }

        $hasSearch = (parsePosRegisterSearchTerms($productName) !== [])
            || ($searchValue !== '' || $productCode !== '');

        // Stock scope: align with stock report (getStockReport) — default is "all" rows with a movement row.
        $stockFilter = strtolower(trim((string)$stockFilter));
        if ($stockFilter === 'out') {
            $where .= ' AND COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) = 0 AND (sm_pid.product_id IS NOT NULL OR sm_sku.sku IS NOT NULL) ';
        } elseif ($stockFilter === 'low') {
            $where .= ' AND COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) BETWEEN 1 AND 5 ';
        } elseif ($stockFilter === 'in') {
            $where .= ' AND COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) > 0 ';
        } elseif (!$hasSearch) {
            $where .= ' AND (sm_pid.product_id IS NOT NULL OR sm_sku.sku IS NOT NULL) ';
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
            'price',
            'price_india'
        ];

        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'title';
        }

        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        $orderExpr = 'p.' . $orderColumn;
        $orderSuffix = $orderDir;
        if ($orderColumn === 'stock_qty') {
            $orderExpr = 'COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0)';
        } elseif ($orderColumn === 'price') {
            // GST-inclusive list price; deprioritize rows missing DB price_india (filled via API after query).
            $hasIndiaPrice = "CASE WHEN COALESCE(NULLIF(p.price_india, 0), 0) > 0 THEN 0 ELSE 1 END";
            if ($orderDir === 'ASC') {
                $orderExpr = "{$hasIndiaPrice} ASC, {$sellPriceExpr} ASC";
            } else {
                $orderExpr = "{$hasIndiaPrice} ASC, {$sellPriceExpr} DESC";
            }
            $orderSuffix = '';
        } elseif ($orderColumn === 'price_india') {
            // POS sorting: India base price (ex-GST), not GST-inclusive display price
            $orderExpr = $baseSell;
        }

        // Fast double LEFT JOIN on exact equality columns (product_id and sku) — avoids Cartesian OR joins.
        $stockFrom = "
    FROM vp_products p
    LEFT JOIN (
        SELECT sm1.product_id, sm1.running_stock, sm1.location
        FROM vp_stock_movements sm1
        INNER JOIN (
            SELECT product_id, MAX(id) AS max_id
            FROM vp_stock_movements
            WHERE warehouse_id = ? AND product_id > 0
            GROUP BY product_id
        ) latest ON latest.max_id = sm1.id
        WHERE sm1.warehouse_id = ?
    ) sm_pid ON sm_pid.product_id = p.id
    LEFT JOIN (
        SELECT sm1.sku, sm1.running_stock, sm1.location
        FROM vp_stock_movements sm1
        INNER JOIN (
            SELECT sku, MAX(id) AS max_id
            FROM vp_stock_movements
            WHERE warehouse_id = ? AND sku IS NOT NULL AND sku != ''
            GROUP BY sku
        ) latest ON latest.max_id = sm1.id
        WHERE sm1.warehouse_id = ?
    ) sm_sku ON sm_sku.sku = p.sku
    ";

        /* ================= DATA QUERY ================= */
        $dataSql = "
    SELECT
        p.id,
        p.item_code,
        p.sku,
        p.published,
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
        p.item_level,
        p.itemtype,
        p.price_india,
        p.gst,
        p.sourcingfee,
        p.shippingfee,
        COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) AS stock_qty,
        COALESCE(sm_pid.location, sm_sku.location, '') AS warehouse_location,
        {$sellPriceExpr} AS price
    $stockFrom
    $where
    ORDER BY $orderExpr $orderSuffix, p.id ASC
    LIMIT ?, ?
    ";

        $dataStmt = $this->db->prepare($dataSql);

        $dataTypes = "iiii" . $types . "ii";
        $wh = (int)$warehouseId;
        $dataParams = array_merge([$wh, $wh, $wh, $wh], $params, [$start, $length]);

        $dataStmt->bind_param($dataTypes, ...$dataParams);
        $dataStmt->execute();

        $result = $dataStmt->get_result();
        $rows = $result->fetch_all(MYSQLI_ASSOC);
        $dataStmt->close();

        // Filtered count with exactly same join + where conditions (stable pagination).
        $countSql = "SELECT COUNT(*) AS cnt $stockFrom $where";
        $countStmt = $this->db->prepare($countSql);
        $countTypes = "iiii" . $types;
        $countParams = array_merge([$wh, $wh, $wh, $wh], $params);
        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $countRow = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();
        $recordsFiltered = (int)($countRow['cnt'] ?? 0);

        return [
            'data' => $rows,
            'recordsFiltered' => $recordsFiltered,
            'recordsTotal' => $recordsFiltered,
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
        $excludeParent = $this->sqlExcludeParentItemLevel('');
        $totalSql = "SELECT COUNT(*) FROM vp_products WHERE is_active = 1{$excludeParent}";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute();
        $totalStmt->bind_result($recordsTotal);
        $totalStmt->fetch();
        $totalStmt->close();

        /* =========================
     * 2) Filters
     * ========================= */
        $where  = ' WHERE is_active = 1 ' . $this->sqlExcludeParentItemLevel('');
        $params = [];
        $types  = "";

        // ✅ Category filter (match groupname)
        if (!empty($category) && $category != 'allProducts') {
            if (in_array(strtolower($category), ['virtual_codes', 'virtualcodes', 'virtual_code', 'virtual codes'], true)) {
                $where .= " AND title LIKE ? ";
                $params[] = "%Virtual Code%";
                $types   .= "s";
            } else {
                $where .= " AND groupname = ? ";
                $params[] = $category;
                $types   .= "s";
            }
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

    /**
     * @param array<string, mixed> $filters
     * @return array{join:string,where:string,params:list<mixed>,types:string}|null
     */
    private function buildStockReportQueryContext(array $filters, bool $includeLocation): ?array
    {
        $warehouseId = isset($filters['warehouse_id']) ? (int) $filters['warehouse_id'] : (isset($_SESSION['warehouse_id']) ? (int) $_SESSION['warehouse_id'] : 0);
        $category = trim((string) ($filters['category'] ?? ''));

        if ($warehouseId <= 0) {
            return null;
        }

        require_once dirname(__DIR__, 2) . '/helpers/stock_report_filters.php';

        $search = trim((string) ($filters['search'] ?? ''));
        $hasSearch = parseStockReportSearchTerms($search) !== [];

        // Fast double LEFT JOIN on exact equality columns (product_id and sku) — avoids Cartesian OR joins.
        $join = "
            LEFT JOIN (
                SELECT sm1.product_id, sm1.running_stock, sm1.location
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ? AND product_id > 0
                    GROUP BY product_id
                ) latest ON latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm_pid ON sm_pid.product_id = p.id
            LEFT JOIN (
                SELECT sm1.sku, sm1.running_stock, sm1.location
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT sku, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ? AND sku IS NOT NULL AND sku != ''
                    GROUP BY sku
                ) latest ON latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm_sku ON sm_sku.sku = p.sku
        ";

        $where = ' WHERE p.is_active = 1 ' . $this->sqlExcludeParentItemLevel('p');
        if (!$hasSearch) {
            $where .= ' AND (sm_pid.product_id IS NOT NULL OR sm_sku.sku IS NOT NULL) ';
        }
        $wh = (int)$warehouseId;
        $params = [$wh, $wh, $wh, $wh];
        $types = 'iiii';

        if ($category !== '' && $category !== 'allProducts') {
            if (in_array(strtolower($category), ['virtual_codes', 'virtualcodes', 'virtual_code', 'virtual codes'], true)) {
                $where .= " AND p.title LIKE ? ";
                $params[] = "%Virtual Code%";
                $types .= "s";
            } else {
                $where .= ' AND p.groupname = ? ';
                $params[] = $category;
                $types .= 's';
            }
        }

        if ($hasSearch) {
            appendStockReportSearchFilterSql($where, $params, $types, $search);
        }

        appendStockReportExtraFiltersSql($where, $params, $types, $filters, $this->db);
        appendStockReportStockStatusFiltersSql($where, $filters);
        appendStockReportLocationFilterSql($where, $params, $types, $filters);

        return [
            'join' => $join,
            'stats_join' => "
            LEFT JOIN (
                SELECT product_id, COUNT(*) AS movement_count, MIN(running_stock) AS min_running_stock
                FROM vp_stock_movements
                WHERE product_id > 0
                GROUP BY product_id
            ) sm_stats_pid ON sm_stats_pid.product_id = p.id
            LEFT JOIN (
                SELECT sku, COUNT(*) AS movement_count, MIN(running_stock) AS min_running_stock
                FROM vp_stock_movements
                WHERE sku IS NOT NULL AND sku != ''
                GROUP BY sku
            ) sm_stats_sku ON sm_stats_sku.sku = p.sku
            ",
            'where' => $where,
            'params' => $params,
            'types' => $types,
        ];
    }

    public function getStockReport(array $filters = []): array
    {
        $limit = isset($filters['limit']) ? (int) $filters['limit'] : 200;
        $pageNo = isset($filters['page_no']) ? max(1, (int) $filters['page_no']) : 1;
        if ($limit < 1) {
            $limit = 200;
        }
        if ($limit > 500) {
            $limit = 500;
        }
        $offset = ($pageNo - 1) * $limit;

        $query = $this->buildStockReportQueryContext($filters, true);
        if ($query === null) {
            return [];
        }

        $join = $query['join'];
        $statsJoin = $query['stats_join'] ?? '';
        $where = $query['where'];
        $params = $query['params'];
        $types = $query['types'];

        $sql = "
            SELECT
                p.id,
                p.item_code,
                p.sku,
                p.title,
                p.groupname,
                COALESCE(NULLIF(TRIM(cat.display_name), ''), p.groupname) AS category_display,
                p.size,
                p.color,
                p.image,
                ({$this->sqlPosIndiaSellBaseExpr('p')} * (1 + IFNULL(p.gst, 0) / 100)) AS sell_price,
                p.cost_price,
                COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) AS stock_qty,
                COALESCE(sm_pid.location, sm_sku.location, '') AS location,
                COALESCE(sm_stats_pid.movement_count, sm_stats_sku.movement_count, 0) AS movement_count,
                COALESCE(sm_stats_pid.min_running_stock, sm_stats_sku.min_running_stock) AS min_running_stock
            FROM vp_products p
            $join
            $statsJoin
            LEFT JOIN `category` cat ON cat.category = p.groupname
            $where
            ORDER BY COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) ASC, p.title ASC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $rows;
    }

    public function getStockReportCount(array $filters = []): int
    {
        $query = $this->buildStockReportQueryContext($filters, false);
        if ($query === null) {
            return 0;
        }

        $join = $query['join'];
        $where = $query['where'];
        $params = $query['params'];
        $types = $query['types'];

        $sql = "
            SELECT COUNT(*) AS cnt
            FROM vp_products p
            $join
            $where
        ";

        $stmt = $this->db->prepare($sql);
        if ($stmt === false) {
            return 0;
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($res['cnt'] ?? 0);
    }

    /**
     * Resolve item_level for a catalogue code (sku or item_code), including parent rows.
     */
    public function lookupProductItemLevelForCode(string $code): string
    {
        $code = trim($code);
        if ($code === '' || !$this->db) {
            return '';
        }
        $stmt = $this->db->prepare(
            'SELECT item_level FROM vp_products
             WHERE is_active = 1 AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC
             LIMIT 1'
        );
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string)($row['item_level'] ?? ''));
    }

    /**
     * Resolve single product row for code.
     */
    public function getProductByCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '' || !$this->db) {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT id, item_code, sku, title, image, material, size, color, hsn, gst,
                    price_india, itemprice, finalprice, mrp_india,
                    groupname, itemtype, sourcingfee, shippingfee,
                    product_weight, product_weight_unit,
                    prod_height, prod_width, prod_length, length_unit, item_level, published
             FROM vp_products WHERE is_active = 1
               AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Resolve single product row by id or code.
     */
    public function getProductByIdOrCode(int $productId, string $code = ''): ?array
    {
        if (!$this->db) {
            return null;
        }
        if ($productId > 0) {
            $stmt = $this->db->prepare('SELECT id, item_code, sku, title FROM vp_products WHERE id = ? LIMIT 1');
            if (!$stmt) {
                return null;
            }
            $stmt->bind_param('i', $productId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return $row ?: null;
        }

        $code = trim($code);
        if ($code === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT id, item_code, sku, title FROM vp_products
             WHERE is_active = 1 AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Fetch price fields from vp_products for code.
     */
    public function resolveIndiaSellPriceRowFromVp(string $code): array
    {
        $code = trim($code);
        if ($code === '' || !$this->db) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT price_india, finalprice, itemprice, gst
             FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC LIMIT 1'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: [];
    }

    /**
     * vp_products.gst fallback row.
     */
    public function fetchVpProductGstFallbackRow(string $code): array
    {
        $code = trim($code);
        if ($code === '' || !$this->db) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT gst FROM vp_products WHERE is_active = 1 AND (sku = ? OR item_code = ?)
             ORDER BY (sku = ?) DESC,
                      CASE WHEN LOWER(TRIM(IFNULL(item_level, \'\'))) = \'parent\' THEN 1 ELSE 0 END,
                      id ASC LIMIT 1'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $code, $code, $code);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: [];
    }

    /**
     * All active VP product ids matching sku or item_code.
     *
     * @return list<int>
     */
    public function resolveVpProductIdsForStockLookup(string $code): array
    {
        $code = trim($code);
        if ($code === '' || !$this->db) {
            return [];
        }
        $stmt = $this->db->prepare(
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
     * Latest running_stock and location for one product ID/SKU at one warehouse.
     *
     * @return array{running_stock: float, location: string}
     */
    public function getWarehouseStockSnapshot(int $productId, int $warehouseId, string $sku = ''): array
    {
        $empty = ['running_stock' => 0.0, 'location' => ''];
        $sku = trim($sku);
        if (($productId <= 0 && $sku === '') || $warehouseId <= 0 || !$this->db) {
            return $empty;
        }

        if ($sku === '' && $productId > 0) {
            $sStmt = $this->db->prepare('SELECT sku FROM vp_products WHERE id = ? LIMIT 1');
            if ($sStmt) {
                $sStmt->bind_param('i', $productId);
                $sStmt->execute();
                $sRow = $sStmt->get_result()->fetch_assoc();
                $sStmt->close();
                if ($sRow) {
                    $sku = trim((string)($sRow['sku'] ?? ''));
                }
            }
        }

        $sql = '
            SELECT sm.running_stock, sm.location
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE warehouse_id = ?
                  AND ((product_id = ? AND product_id > 0) OR (sku = ? AND sku IS NOT NULL AND sku != \'\'))
                GROUP BY warehouse_id
            ) latest ON latest.max_id = sm.id
            WHERE sm.warehouse_id = ?
            LIMIT 1';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('iisi', $warehouseId, $productId, $sku, $warehouseId);
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

    /** Latest running_stock for one SKU at one warehouse. */
    public function getWarehouseStockForProductId(int $productId, int $warehouseId, string $sku = ''): float
    {
        return $this->getWarehouseStockSnapshot($productId, $warehouseId, $sku)['running_stock'];
    }

    /** Sum of latest running_stock per warehouse for this product (all locations). */
    public function getTotalStockAcrossWarehouses(int $productId, string $sku = ''): float
    {
        $sku = trim($sku);
        if (($productId <= 0 && $sku === '') || !$this->db) {
            return 0.0;
        }

        if ($sku === '' && $productId > 0) {
            $sStmt = $this->db->prepare('SELECT sku FROM vp_products WHERE id = ? LIMIT 1');
            if ($sStmt) {
                $sStmt->bind_param('i', $productId);
                $sStmt->execute();
                $sRow = $sStmt->get_result()->fetch_assoc();
                $sStmt->close();
                if ($sRow) {
                    $sku = trim((string)($sRow['sku'] ?? ''));
                }
            }
        }

        $sql = '
            SELECT COALESCE(SUM(sm.running_stock), 0) AS t
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE (product_id = ? AND product_id > 0)
                   OR (sku = ? AND sku IS NOT NULL AND sku != \'\')
                GROUP BY warehouse_id
            ) latest ON sm.id = latest.max_id';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('is', $productId, $sku);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return isset($row['t']) ? (float)$row['t'] : 0.0;
    }

    private static $defaultWarehouseCache = false;

    /** Default warehouse row from exotic_address. */
    public function getDefaultWarehouseRow(): ?array
    {
        if (self::$defaultWarehouseCache !== false) {
            return self::$defaultWarehouseCache;
        }
        if (!$this->db) {
            self::$defaultWarehouseCache = null;
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT id, address_title FROM exotic_address WHERE is_active = 1 AND is_default = 1 ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            self::$defaultWarehouseCache = null;
            return null;
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (empty($row['id'])) {
            self::$defaultWarehouseCache = null;
            return null;
        }

        self::$defaultWarehouseCache = [
            'id' => (int)$row['id'],
            'address_title' => trim((string)($row['address_title'] ?? '')),
        ];

        return self::$defaultWarehouseCache;
    }

    /** Footer text from default exotic address */
    public function getDefaultExoticAddressFooterString(): string
    {
        if (!$this->db) {
            return '';
        }
        $stmt = $this->db->prepare(
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
     * POS stock context for a VP product at the session warehouse.
     */
    public function resolvePosStockContext(int $productId, int $currentWarehouseId, string $currentWarehouseName = '', string $sku = ''): array
    {
        $currentWarehouseName = trim($currentWarehouseName);
        $sku = trim($sku);
        $empty = [
            'current_warehouse_id' => $currentWarehouseId,
            'current_warehouse_name' => $currentWarehouseName,
            'current_stock_qty' => 0.0,
            'current_location' => '',
            'total_qty_all_warehouses' => 0.0,
            'default_store_qty' => null,
            'default_store_name' => '',
            'mapped_at_current' => false,
            'mapped_anywhere' => false,
            'alternative_warehouses' => [],
            'default_warehouse' => null,
            'allow_order' => true,
            'enforce_qty_cap' => false,
            'qty_cap' => null,
            'warning_message' => '',
            'warning_type' => 'none',
        ];

        if (($productId <= 0 && $sku === '') || !$this->db) {
            return $empty;
        }

        if ($sku === '' && $productId > 0) {
            $sStmt = $this->db->prepare('SELECT sku FROM vp_products WHERE id = ? LIMIT 1');
            if ($sStmt) {
                $sStmt->bind_param('i', $productId);
                $sStmt->execute();
                $sRow = $sStmt->get_result()->fetch_assoc();
                $sStmt->close();
                if ($sRow) {
                    $sku = trim((string)($sRow['sku'] ?? ''));
                }
            }
        }

        $stockSql = "
            SELECT sm.warehouse_id,
                   COALESCE(ea.address_title, CONCAT('Warehouse #', sm.warehouse_id)) AS warehouse_name,
                   sm.running_stock AS stock_qty,
                   sm.location AS warehouse_location
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE (product_id = ? AND product_id > 0)
                   OR (sku = ? AND sku IS NOT NULL AND sku != '')
                GROUP BY warehouse_id
            ) latest ON latest.max_id = sm.id
            LEFT JOIN exotic_address ea ON ea.id = sm.warehouse_id
            ORDER BY warehouse_name ASC";

        $stockStmt = $this->db->prepare($stockSql);
        if (!$stockStmt) {
            return $empty;
        }
        $stockStmt->bind_param('is', $productId, $sku);
        $stockStmt->execute();
        $rows = $stockStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stockStmt->close();

        $mappedAtCurrent = false;
        $currentStock = 0.0;
        $currentLocation = '';
        $totalQtyAll = 0.0;
        $alternativeWarehouses = [];
        foreach ($rows as $row) {
            $wid = (int)($row['warehouse_id'] ?? 0);
            $stockQty = (float)($row['stock_qty'] ?? 0);
            $totalQtyAll += $stockQty;
            $entry = [
                'warehouse_id' => $wid,
                'warehouse_name' => trim((string)($row['warehouse_name'] ?? '')),
                'stock_qty' => $stockQty,
            ];
            if ($wid === $currentWarehouseId) {
                $mappedAtCurrent = true;
                $currentStock = $stockQty;
                $currentLocation = trim((string)($row['warehouse_location'] ?? ''));
            } elseif ($stockQty > 0) {
                $alternativeWarehouses[] = $entry;
            }
        }

        $mappedAnywhere = !empty($rows);
        $defaultWarehouse = $this->getDefaultWarehouseRow();
        $defaultStoreName = trim((string)($defaultWarehouse['address_title'] ?? ''));
        $defaultStoreQty = null;
        if ($defaultWarehouse !== null && !empty($defaultWarehouse['id'])) {
            $defWhId = (int)$defaultWarehouse['id'];
            foreach ($rows as $row) {
                if ((int)($row['warehouse_id'] ?? 0) === $defWhId) {
                    $defaultStoreQty = (float)($row['stock_qty'] ?? 0);
                    break;
                }
            }
        }
        $storeLabel = $currentWarehouseName !== '' ? $currentWarehouseName : 'this store';

        $altNames = array_values(array_filter(array_map(static function (array $w): string {
            return trim((string)($w['warehouse_name'] ?? ''));
        }, $alternativeWarehouses)));

        $warningMessage = '';
        $warningType = 'none';

        if (!$mappedAnywhere) {
            $warningType = 'unmapped_anywhere';
            $defaultName = trim((string)($defaultWarehouse['address_title'] ?? ''));
            if ($defaultName === '') {
                $defaultName = 'Default Store';
            }
            $warningMessage = 'This item is not mapped to any store. It will be treated as mapped to the default store ('
                . $defaultName . '). You can create an order for ' . $storeLabel . '.';
        } elseif (!$mappedAtCurrent && !empty($altNames)) {
            $warningType = 'unmapped_current';
            $warningMessage = 'This item is not mapped to ' . $storeLabel . '. Stock is available at '
                . implode(', ', $altNames) . '. You can still create an order for ' . $storeLabel . '.';
        } elseif (!$mappedAtCurrent) {
            $warningType = 'unmapped_current';
            $warningMessage = 'This item is not mapped to ' . $storeLabel . '. You can still create an order for ' . $storeLabel . '.';
        } elseif ($currentStock <= 0 && !empty($altNames)) {
            $warningType = 'cross_store';
            $warningMessage = 'Out of stock at ' . $storeLabel . '. Stock is available at '
                . implode(', ', $altNames) . '. You can still create an order.';
        } elseif ($mappedAtCurrent && $currentStock <= 0) {
            $warningType = 'out_of_stock_local';
            $warningMessage = 'This item is out of stock at ' . $storeLabel . '. You can still create an order.';
        }

        $enforceQtyCap = $mappedAtCurrent && $currentStock > 0;

        return [
            'current_warehouse_id' => $currentWarehouseId,
            'current_warehouse_name' => $currentWarehouseName,
            'current_stock_qty' => $currentStock,
            'current_location' => $currentLocation,
            'total_qty_all_warehouses' => $totalQtyAll,
            'default_store_qty' => $defaultStoreQty,
            'default_store_name' => $defaultStoreName,
            'mapped_at_current' => $mappedAtCurrent,
            'mapped_anywhere' => $mappedAnywhere,
            'alternative_warehouses' => $alternativeWarehouses,
            'default_warehouse' => $defaultWarehouse,
            'allow_order' => true,
            'enforce_qty_cap' => $enforceQtyCap,
            'qty_cap' => $enforceQtyCap ? (int) floor($currentStock) : null,
            'warning_message' => $warningMessage,
            'warning_type' => $warningType,
        ];
    }

    /**
     * Other VP rows with the same item_code (excluding the opened variant), with warehouse stock when available.
     *
     * @return list<array{id:int, sku:string, title:string, stock_qty:float}>
     */
    public function fetchSiblingSkusByItemCode(string $itemCode, string $excludeSku, int $warehouseId): array
    {
        if ($itemCode === '' || !$this->db || $excludeSku === '') {
            return [];
        }

        if ($warehouseId <= 0) {
            $sql = 'SELECT id, sku, title, 0 AS stock_qty
                    FROM vp_products
                    WHERE is_active = 1
                      AND LOWER(TRIM(IFNULL(item_level, \'\'))) <> \'parent\'
                      AND item_code = ? AND sku <> ?
                    ORDER BY sku ASC';
            $stmt = $this->db->prepare($sql);
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
            SELECT p.id, p.sku, p.title, COALESCE(sm_pid.running_stock, sm_sku.running_stock, 0) AS stock_qty
            FROM vp_products p
            LEFT JOIN (
                SELECT sm1.product_id, sm1.running_stock
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT product_id, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ? AND product_id > 0
                    GROUP BY product_id
                ) latest ON latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm_pid ON sm_pid.product_id = p.id
            LEFT JOIN (
                SELECT sm1.sku, sm1.running_stock
                FROM vp_stock_movements sm1
                INNER JOIN (
                    SELECT sku, MAX(id) AS max_id
                    FROM vp_stock_movements
                    WHERE warehouse_id = ? AND sku IS NOT NULL AND sku != \'\'
                    GROUP BY sku
                ) latest ON latest.max_id = sm1.id
                WHERE sm1.warehouse_id = ?
            ) sm_sku ON sm_sku.sku = p.sku
            WHERE p.is_active = 1
              AND LOWER(TRIM(IFNULL(item_level, \'\'))) <> \'parent\'
              AND p.item_code = ? AND p.sku <> ?
            ORDER BY p.sku ASC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $wh = (int)$warehouseId;
        $stmt->bind_param('iiiiss', $wh, $wh, $wh, $wh, $itemCode, $excludeSku);
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
}
