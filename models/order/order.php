<?php 

class Order{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllOrders($filters = []) {

        $sql = "SELECT * FROM vp_orders WHERE 1=1";
        $params = [];
        if (!empty($filters['order_number'])) {
            $sql .= " AND order_number LIKE ?";
            $params[] = '%' . $filters['order_number'] . '%';
        }
        if (!empty($filters['item_code'])) {
            $sql .= " AND item_code LIKE ?";
            $params[] = '%' . $filters['item_code'] . '%';
        }
        if (!empty($filters['order_from']) && !empty($filters['order_till'])) {
            $sql .= " AND order_date BETWEEN ? AND ?";
            $params[] = $filters['order_from'].' 00:00:00';
            $params[] = $filters['order_till'].' 23:59:59';
        }
        if (!empty($filters['title'])) {
            $sql .= " AND title LIKE ?";
            $params[] = '%' . $filters['title'] . '%';
        }
        if (!empty($filters['min_amount'])) {
            $sql .= " AND total_price >= ?";
            $params[] = $filters['min_amount'];
        }
        if (!empty($filters['max_amount'])) {
            $sql .= " AND total_price <= ?";
            $params[] = $filters['max_amount'];
        }
        
        $sql .= " ORDER BY order_date DESC";
        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $orders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        return $orders;
    }
    public function getOrderById($id) {
        $sql = "SELECT * FROM vp_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);   
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    /*public function insertOrder($data) {
        //print_r($data);
        //echo "<br>";
        // Assuming $data is an associative array with keys matching the database columns
        if(empty($data['order_number'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        if(!empty($data)) {
        $sql = "INSERT INTO vp_orders (order_number, title, item_code, size, color, description, image, marketplace_vendor, quantity, options) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssssis', 
            $data['order_number'], 
            $data['title'],
            $data['item_code'],
            $data['size'],
            $data['color'],
            $data['description'],
            $data['image'],
            $data['marketplace_vendor'],
            $data['quantity'],
            $data['options']
        );
        $stmt->execute();
        if ($stmt->error) {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
        return $stmt->insert_id;

        } else {
            return false;
        }
    }*/
    public function insertOrder($data) {
        //print_r($data);
        //echo "<br>";
        // Assuming $data is an associative array with keys matching the database columns
        if (empty($data) || !is_array($data)) {
            return ['success' => false, 'message' => 'Data is empty or not an array.'];
        }
        $required = ['order_number', 'item_code', 'quantity'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // ✅ Check for duplicate combination
        $checkSql = "SELECT COUNT(*) FROM vp_orders WHERE order_number = ? AND item_code = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('ss', $data['order_number'], $data['item_code']);
        $checkStmt->execute();
        $checkStmt->bind_result($count);
        $checkStmt->fetch();
        $checkStmt->close();

        if ($count > 0) {
            return ['success' => false, 'message' => 'Duplicate '.$data['order_number'].'-'.$data['item_code'].' order_number + item_code combination.'];
        }

        // Insert
        $sql = "INSERT INTO vp_orders 
            (order_number, title, item_code, size, color, description, image, marketplace_vendor, quantity, gst, hsn, options, order_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssssssissss', 
            $data['order_number'], 
            $data['title'],
            $data['item_code'],
            $data['size'],
            $data['color'],
            $data['description'],
            $data['image'],
            $data['marketplace_vendor'],
            $data['quantity'],
            $data['gst'],
            $data['hsn'],
            $data['options'],
            $data['order_date']
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return ['success' => true, 'insert_id' => $insertId];
    }
    public function updateOrderStatus($id, $status, $po_number, $po_id) {
        // Validate inputs
        if (empty($id) || empty($status)) {
            return ['success' => false, 'message' => 'ID or status is missing.'];
        }

        // Prepare SQL statement
        $sql = "UPDATE vp_orders SET status = ?, po_number = ?, po_id = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        // Bind parameters
        $stmt->bind_param('ssii', $status, $po_number, $po_id, $id);

        // Execute and check result
        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Execute failed: ' . $stmt->error];
        }

        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        if ($affectedRows === 0) {
            return ['success' => false, 'message' => 'No record updated. ID may not exist.'];
        }

        return ['success' => true, 'message' => 'Status updated successfully.'];
    }
    public function getOrderItems($searchTerm) {
        $sql = "SELECT * FROM vp_orders WHERE order_number LIKE ? OR item_code LIKE ? OR title LIKE ?";
        $stmt = $this->db->prepare($sql);
        $searchTerm = "%{$searchTerm}%";
        $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        $result = $stmt->get_result();
        $orderItems = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orderItems[] = [
                    'id' => $row['id'],
                    'order_number' => $row['order_number'],
                    'order_date' => $row['order_date'],
                    'item_code' => $row['item_code'],
                    'title' => $row['title'],
                    'price' => $row['unit_price'],
                    'gst' => $row['gst'],
                    'description' => $row['description'],
                    'image' => $row['image'],
                    // 'marketplace_vendor' => $row['marketplace_vendor'],
                    'quantity' => $row['quantity'],
                    'options' => $row['options'],
                    'order_date' => $row['order_date'],
                ];
            }
        }
        return $orderItems;
    }
    public function updateOrderStatusByPO($po_id, $status) {
        $sql = "UPDATE vp_orders SET status = ? WHERE po_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $status, $po_id);
        return $stmt->execute();
    }

}
?> 