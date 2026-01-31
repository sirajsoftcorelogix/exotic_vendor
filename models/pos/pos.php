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
    string $productName = '',
    string $orderColumn = 'title',
    string $orderDir = 'asc'
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
    $where = " WHERE is_active = 1 ";
    $params = [];
    $types  = "";


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
        'category_slug',
        'size',
        'color',
        'image'
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
            title,
            size,
            color,
            image,
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

    if (!empty($paramsWithLimit)) {
        $dataStmt->bind_param($typesWithLimit, ...$paramsWithLimit);
    }

    $dataStmt->execute();
    $result = $dataStmt->get_result();
    $rows = $result->fetch_all(MYSQLI_ASSOC);
    $dataStmt->close();

    return [
        'recordsTotal'    => (int)$recordsTotal,
        'recordsFiltered' => (int)$recordsFiltered,
        'data'            => $rows
    ];
}

}
