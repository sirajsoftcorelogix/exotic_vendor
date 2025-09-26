<?php 

class Order{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllOrders($filters = [], $limit = 50, $offset = 0) {

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
        if (!empty($filters['po_no'])) {
            $sql .= " AND po_number LIKE ?";
            $params[] = '%' . $filters['po_no'] . '%';
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
        if (!empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
            if ($filters['status_filter'] === 'pending') {
                $sql .= " AND (po_number IS NULL OR po_number = '')";
            } elseif ($filters['status_filter'] === 'processed') {
                $sql .= " AND (po_number IS NOT NULL AND po_number != '')";
            } elseif ($filters['status_filter'] === 'cancelled') {
                $sql .= " AND ('status' = 'cancel')";
            }
        }

        $sql .= " ORDER BY order_date DESC LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        // Add limit and offset to params and types
        $params[] = $limit;
        $params[] = $offset;
        $types = str_repeat('s', count($params) - 2) . 'ii';
        $stmt->bind_param($types, ...$params);
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
    public function getOrdersCount($filters = []) {
        $sql = "SELECT COUNT(*) as count FROM vp_orders WHERE 1=1";
        $params = [];
        if (!empty($filters['order_number'])) {
            $sql .= " AND order_number LIKE ?";
            $params[] = '%' . $filters['order_number'] . '%';
        }
        if (!empty($filters['item_code'])) {
            $sql .= " AND item_code LIKE ?";
            $params[] = '%' . $filters['item_code'] . '%';
        }
        if (!empty($filters['po_no'])) {
            $sql .= " AND po_number LIKE ?";
            $params[] = '%' . $filters['po_no'] . '%';
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
        if (!empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
            if ($filters['status_filter'] === 'pending') {
                $sql .= " AND (po_number IS NULL OR po_number = '')";
            } elseif ($filters['status_filter'] === 'processed') {
                $sql .= " AND (po_number IS NOT NULL AND po_number != '')";
            } elseif ($filters['status_filter'] === 'cancelled') {
                $sql .= " AND ('status' = 'cancel')";
            }
        }

        $stmt = $this->db->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat('s', count($params)), ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return (int)$row['count'];
        }
        return 0;
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
		$table_name = 'vp_orders';
		$InsertFields = [
			'order_number', 'shipping_country', 'title', 'description', 'item_code', 'size', 'color', 
			'groupname', 'subcategories', 'currency', 'itemprice', 'finalprice', 'image', 
			'marketplace_vendor', 'quantity', 'options', 'gst', 'hsn', 'local_stock', 
			'cost_price', 'location', 'order_date'
		];

		// Build SQL query
		$columns = implode(', ', $InsertFields);
		$placeholders = rtrim(str_repeat('?, ', count($InsertFields)), ', ');
		$sql = "INSERT INTO {$table_name} ({$columns}) VALUES ({$placeholders})";

		$stmt = $this->db->prepare($sql);
		if (!$stmt) {
			return ['success' => false, 'error' => 'Prepare failed: ' . $this->db->error];
		}

		$types = '';
		$values = [];
		foreach ($InsertFields as $field) {
			$value = isset($data[$field]) ? $data[$field] : null;
			if (is_int($value)) {
				$types .= 'i';
			} elseif (is_float($value) || is_double($value)) {
				$types .= 'd';
			} else {
				$types .= 's';
			}
			$values[] = $value;
		}

		// Debug (remove later)
		echo "<br>SQL: " . $sql;
		echo "<br>Types: " . $types;
		echo "<pre>"; print_r($values); echo "</pre>";

		// Bind dynamically
		$stmt->bind_param($types, ...$values);

		if (!$stmt->execute()) {
			return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
		}

		echo "</br>".'insert_id: '. $insertId = $stmt->insert_id;
		$stmt->close();

		return ['success' => true, 'insert_id' => $insertId];
    }
	
    public function updateOrderStatus($id, $status, $po_number, $po_id = null, $deliveryDueDate = null) {
        // Validate inputs
        if (empty($id) || empty($status)) {
            return ['success' => false, 'message' => 'ID or status is missing.'];
        }

        // Prepare SQL statement
        $sql = "UPDATE vp_orders SET status = ?, po_number = ?, po_id = ?, delivery_due_date = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        // Bind parameters
        $stmt->bind_param('ssisi', $status, $po_number, $po_id, $deliveryDueDate, $id);

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
        $sql = "SELECT * FROM vp_orders WHERE status = 'pending' AND (order_number LIKE ? OR item_code LIKE ? OR title LIKE ?)";
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
    public function orderImportLog($data) {
        if(empty($data['start_time'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        $sql = "INSERT INTO order_import_log (start_time) VALUES (?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $data['start_time']);
            
        if ($stmt->execute()) {
            return ['success' => true, 'insert_id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
	
    public function updateOrderImportLog($log_id, $data) {
        if(empty($log_id) || empty($data['end_time'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        $sql = "UPDATE order_import_log SET end_time = ?, successful_imports = ?, total_orders = ?, error = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssdis', $data['end_time'], $data['successful_imports'], $data['total_orders'], $data['error'], $log_id);
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
}
?> 