<?php
class Customer
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function getCustomers($search = '', $state = '', $limit = 10, $offset = 0)
    {
        $sql = "SELECT 
                    vc.*,
                    COALESCE(SUM(o.finalprice), 0) AS total_order_amount,
                    MAX(o.order_date) AS last_purchase_date,
                    SUBSTRING_INDEX(GROUP_CONCAT(o.currency ORDER BY o.id DESC SEPARATOR ','), ',', 1) AS currency,
                    SUBSTRING_INDEX(GROUP_CONCAT(voi.state ORDER BY o.id DESC SEPARATOR ','), ',', 1) AS state
                FROM vp_customers AS vc
                LEFT JOIN vp_orders AS o ON o.customer_id = vc.id
                LEFT JOIN vp_order_info AS voi ON voi.order_number = o.order_number";

        // Build WHERE Clause
        $where = [];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $where[] = "(vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?)";
            $searchTerm = "%" . $search . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($state)) {
            $where[] = "voi.state = ?";
            $params[] = $state;
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // --- FIX: GROUP BY vc.id prevents duplicates from order_info ---
        $sql .= " GROUP BY vc.id ";

        // Add Pagination
        $sql .= " ORDER BY vc.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCustomersCount($search = '', $state = '')
    {
        // --- FIX: Use DISTINCT to count unique customers only ---
        $sql = "SELECT COUNT(DISTINCT vc.id) as total FROM vp_customers AS vc
                LEFT JOIN vp_orders AS o ON o.customer_id = vc.id
                LEFT JOIN vp_order_info AS voi ON voi.order_number = o.order_number";

        $where = [];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $where[] = "(vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?)";
            $searchTerm = "%" . $search . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($state)) {
            $where[] = "voi.state = ?";
            $params[] = $state;
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function getUniqueStates()
    {
        // Get distinct states from order info so the dropdown only shows valid options
        $query = "SELECT DISTINCT state FROM vp_order_info WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
        $result = $this->conn->query($query);

        $states = [];
        while ($row = $result->fetch_assoc()) {
            $states[] = $row['state'];
        }
        return $states;
    }
    public function getAllCustomers($limit, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM vp_customers";
        $params = [];
        $types = "";
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($filters['state'])) {
            $where[] = "state = ?";
            $params[] = $filters['state'];
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        array_push($params, $limit, $offset);
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function countAllCustomers($filters = [])
    {

        $sql = "SELECT COUNT(*) as total FROM vp_customers";
        $params = [];
        $types = "";
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($filters['state'])) {
            $where[] = "state = ?";
            $params[] = $filters['state'];
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    public function getCustomerById($customer_id)
    {
        $sql = "SELECT * FROM vp_customers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    public function getOrderItemsByCustomerId($customer_id, $limit = 10, $offset = 0, $filters = [])
    {
        $sql = "SELECT 
                    oi.*, 
                    o.*
                FROM vp_order_info AS oi
                JOIN vp_orders AS o ON oi.order_number = o.order_number WHERE o.customer_id = ?";
        $params = [];
        $types = "i";
        array_push($params, $customer_id);

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR o.item_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        //sort
        $sort = $filters['sort'] ?? 'new_to_old';
        $sql .= " ORDER BY o.order_date " . ($sort === 'old_to_new' ? 'ASC' : 'DESC') . " LIMIT ? OFFSET ?";
        array_push($params, $limit, $offset);
        $types .= "ii";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getCustomerOrderCount($customer_id, $filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM vp_order_info AS oi JOIN vp_orders AS o ON oi.order_number = o.order_number WHERE o.customer_id = ?";
        $params = [];
        $types = "i";
        array_push($params, $customer_id);

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR o.item_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    public function getCustomerTotalSpent($customer_id)
    {
        $sql = "SELECT 
                    COUNT(*) AS total_orders, 
                    SUM(finalprice) AS total_spent, 
                    AVG(finalprice) AS average_order_value
                FROM vp_orders
                WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getCustomerOrderStatusCounts($customer_id)
    {
        $sql = "SELECT 
                    COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN o.status IN ('ready_for_packing', 'po_pending', 'po_approved', 'po_inprogress', 'item_received', 'added_to_picklist', 'store_transfer', 'ready_for_qc', 'sent_for_repair') THEN 1 END) as progress,
                    COUNT(CASE WHEN o.status = 'shipped' THEN 1 END) as completed,
                    COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled
                FROM vp_orders o
                WHERE o.customer_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /** Used when deleting a customer (cleans optional extended rows). */
    public function ensurePosCustomerDetailsTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS pos_customer_details (
                customer_id INT UNSIGNED NOT NULL PRIMARY KEY,
                bill_line1 VARCHAR(255) NOT NULL DEFAULT '',
                bill_line2 VARCHAR(255) NOT NULL DEFAULT '',
                bill_city VARCHAR(128) NOT NULL DEFAULT '',
                bill_state VARCHAR(128) NOT NULL DEFAULT '',
                bill_country VARCHAR(128) NOT NULL DEFAULT '',
                bill_pin VARCHAR(32) NOT NULL DEFAULT '',
                ship_line1 VARCHAR(255) NOT NULL DEFAULT '',
                ship_line2 VARCHAR(255) NOT NULL DEFAULT '',
                ship_city VARCHAR(128) NOT NULL DEFAULT '',
                ship_state VARCHAR(128) NOT NULL DEFAULT '',
                ship_country VARCHAR(128) NOT NULL DEFAULT '',
                ship_pin VARCHAR(32) NOT NULL DEFAULT '',
                gstin VARCHAR(64) NOT NULL DEFAULT '',
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;
    }

    /** All customers with purchase totals across all orders (customer list). */
    public function getAllCustomersWithPurchaseStats(string $search, int $limit, int $offset): array
    {
        $search = trim($search);
        $params = [];
        $types = '';
        $searchSql = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $searchSql = ' WHERE (vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?) ';
            $params = [$term, $term, $term];
            $types = 'sss';
        }

        $sql = "SELECT 
                    vc.id,
                    vc.name,
                    vc.email,
                    vc.phone,
                    COALESCE(SUM(o.finalprice), 0) AS total_order_amount,
                    MAX(o.order_date) AS last_purchase_date,
                    MAX(o.currency) AS currency,
                    COUNT(DISTINCT o.order_number) AS order_count
                FROM vp_customers vc
                LEFT JOIN vp_orders o ON o.customer_id = vc.id
                $searchSql
                GROUP BY vc.id, vc.name, vc.email, vc.phone
                ORDER BY vc.id DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function countAllCustomersWithPurchaseStats(string $search): int
    {
        $search = trim($search);
        $params = [];
        $types = '';
        $searchSql = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $searchSql = ' WHERE (vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?) ';
            $params = [$term, $term, $term];
            $types = 'sss';
        }

        $sql = "SELECT COUNT(*) AS c FROM (
                    SELECT vc.id
                    FROM vp_customers vc
                    $searchSql
                    GROUP BY vc.id
                ) t";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    public function countOrdersForCustomer(int $customerId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_orders WHERE customer_id = ?');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    public function deleteCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'message' => 'Invalid customer.'];
        }
        if ($this->countOrdersForCustomer($customerId) > 0) {
            return ['success' => false, 'message' => 'This customer has orders and cannot be deleted.'];
        }
        $this->ensurePosCustomerDetailsTable();
        $stmt = $this->conn->prepare('DELETE FROM pos_customer_details WHERE customer_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $this->conn->prepare('DELETE FROM vp_customers WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $stmt->bind_param('i', $customerId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok
            ? ['success' => true, 'message' => 'Customer removed.']
            : ['success' => false, 'message' => 'Delete failed.'];
    }

}
