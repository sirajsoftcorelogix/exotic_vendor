<?php 
class Customer{
    private $conn;  
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getCustomers($search = '', $state = '', $limit = 10, $offset = 0) {
        $sql = "SELECT 
                    vc.*, 
                    order_stats.total_order_amount,
                    order_stats.last_purchase_date,
                    latest_ord.currency,
                    voi.state
                FROM vp_customers AS vc
                LEFT JOIN (
                    SELECT customer_id, SUM(finalprice) AS total_order_amount, MAX(order_date) AS last_purchase_date, MAX(id) AS latest_order_id
                    FROM vp_orders GROUP BY customer_id
                ) AS order_stats ON vc.id = order_stats.customer_id
                LEFT JOIN vp_orders AS latest_ord ON order_stats.latest_order_id = latest_ord.id
                LEFT JOIN vp_order_info AS voi ON latest_ord.order_number = voi.order_number";

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

    public function getTotalCustomersCount($search = '', $state = '') {
        // --- FIX: Use DISTINCT to count unique customers only ---
        $sql = "SELECT COUNT(DISTINCT vc.id) as total FROM vp_customers AS vc
                LEFT JOIN (
                    SELECT customer_id, MAX(id) AS latest_order_id FROM vp_orders GROUP BY customer_id
                ) AS order_stats ON vc.id = order_stats.customer_id
                LEFT JOIN vp_orders AS latest_ord ON order_stats.latest_order_id = latest_ord.id
                LEFT JOIN vp_order_info AS voi ON latest_ord.order_number = voi.order_number";

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

    public function getUniqueStates() {
        // Get distinct states from order info so the dropdown only shows valid options
        $query = "SELECT DISTINCT state FROM vp_order_info WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
        $result = $this->conn->query($query);
        
        $states = [];
        while ($row = $result->fetch_assoc()) {
            $states[] = $row['state'];
        }
        return $states;
    }
}
?>