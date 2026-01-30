<?php
class pos {
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
        string $category = '',
        string $productCode = '',
        string $productName = '',
        string $orderColumn = 'product_name',
        string $orderDir = 'asc'
    ): array {
        // 1) total records (no filters)
        $totalSql = "SELECT COUNT(*) AS cnt FROM vp_products WHERE is_active = 1";
        $totalStmt = $this->db->prepare($totalSql);
        $totalStmt->execute();
        $recordsTotal = (int)$totalStmt->fetchColumn();

        // 2) filtered query base
        $where = " WHERE is_active = 1 ";
        $params = [];

        // category filter
        if ($category !== '' && $category !== 'all') {
            $where .= " AND category_slug = :category ";
            $params[':category'] = $category;
        }

        // explicit product code filter
        if ($productCode !== '') {
            $where .= " AND product_code LIKE :product_code ";
            $params[':product_code'] = '%' . $productCode . '%';
        }

        // explicit product name filter
        if ($productName !== '') {
            $where .= " AND product_name LIKE :product_name ";
            $params[':product_name'] = '%' . $productName . '%';
        }

        // global search (applies to code + name)
        if ($searchValue !== '') {
            $where .= " AND (product_code LIKE :search OR product_name LIKE :search) ";
            $params[':search'] = '%' . $searchValue . '%';
        }

        // guard order column & direction
        $allowedColumns = ['product_code', 'product_name', 'category_slug', 'stock_qty', 'price', 'image_url'];
        if (!in_array($orderColumn, $allowedColumns, true)) {
            $orderColumn = 'product_name';
        }
        $orderDir = strtolower($orderDir) === 'desc' ? 'DESC' : 'ASC';

        // 3) filtered count
        $countSql = "SELECT COUNT(*) AS cnt FROM vp_products " . $where;
        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $k => $v) {
            $countStmt->bindValue($k, $v);
        }
        $countStmt->execute(); 
        $recordsFiltered = (int)$countStmt->fetchColumn();

        // 4) data query
        $dataSql = "
            SELECT
                id,
                category_slug,
                product_code,
                product_name,
                stock_qty,
                price,
                image_url
            FROM vp_products
            $where
            ORDER BY $orderColumn $orderDir
            LIMIT :start, :length
        ";

        $dataStmt = $this->db->prepare($dataSql);
        foreach ($params as $k => $v) {
            $dataStmt->bindValue($k, $v);
        }
        $dataStmt->bindValue(':start', $start, PDO::PARAM_INT);
        $dataStmt->bindValue(':length', $length, PDO::PARAM_INT);
        $dataStmt->execute();
        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'recordsTotal'    => $recordsTotal,
            'recordsFiltered' => $recordsFiltered,
            'data'            => $rows,
        ];
    }
}
