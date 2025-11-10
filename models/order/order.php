<?php 

class Order{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllOrders($filters = [], $limit = 50, $offset = 0) {

        //$sql = "SELECT vp_orders.id as order_id, vp_orders.*, purchase_orders.*, vp_vendors.vendor_name as vendor_name, vp_users.name as staff_name FROM vp_orders INNER JOIN purchase_orders ON vp_orders.po_number = purchase_orders.po_number INNER JOIN vp_vendors ON vp_vendors.id = purchase_orders.vendor_id INNER JOIN vp_users ON vp_users.id = purchase_orders.user_id WHERE 1=1";
        $sql = "SELECT vp_orders.id as order_id, vp_orders.*, purchase_orders.id, purchase_orders.po_number, purchase_orders.vendor_id, purchase_orders.po_date, purchase_orders.expected_delivery_date, purchase_orders.total_cost, vp_vendors.vendor_name as vendor_name, vp_users.name as staff_name FROM vp_orders LEFT JOIN purchase_orders ON vp_orders.po_id = purchase_orders.id LEFT JOIN vp_vendors ON purchase_orders.vendor_id = vp_vendors.id LEFT JOIN vp_users ON purchase_orders.user_id = vp_users.id  WHERE 1=1";
        $params = [];
        if (!empty($filters['order_number'])) {
            $sql .= " AND vp_orders.order_number LIKE ?";
            $params[] = '%' . $filters['order_number'] . '%';
        }
        if (!empty($filters['item_code'])) {
            $sql .= " AND vp_orders.item_code LIKE ?";
            $params[] = '%' . $filters['item_code'] . '%';
        }
        if (!empty($filters['po_no'])) {
            $sql .= " AND vp_orders.po_number LIKE ?";
            $params[] = '%' . $filters['po_no'] . '%';
        }
        if (!empty($filters['order_from']) && !empty($filters['order_till'])) {
            $sql .= " AND vp_orders.order_date BETWEEN ? AND ?";
            $params[] = $filters['order_from'].' 00:00:00';
            $params[] = $filters['order_till'].' 23:59:59';
        }
        if (!empty($filters['title'])) {
            $sql .= " AND vp_orders.title LIKE ?";
            $params[] = '%' . $filters['title'] . '%';
        }
        if (!empty($filters['min_amount'])) {
            $sql .= " AND vp_orders.total_price >= ?";
            $params[] = $filters['min_amount'];
        }
        if (!empty($filters['max_amount'])) {
            $sql .= " AND vp_orders.total_price <= ?";
            $params[] = $filters['max_amount'];
        }
        if (!empty($filters['status_filter']) && $filters['status_filter'] !== 'all') {
            if ($filters['status_filter'] === 'pending') {
                //$sql .= " AND (vp_orders.po_number IS NULL OR vp_orders.po_number = '')";
                $sql .= " AND vp_orders.status = 'pending'";
            } elseif ($filters['status_filter'] === 'processed') {
                //$sql .= " AND (vp_orders.po_number IS NOT NULL AND vp_orders.po_number != '')";
                $sql .= " AND vp_orders.status IN ('ready_for_packing','po_pending','po_approved','po_inprogress','item_received','added_to_picklist','store_transfer','ready_for_qc','sent_for_repair')";
            } elseif ($filters['status_filter'] === 'dispatch') {
                $sql .= " AND vp_orders.status IN ('ready_for_dispatch')";
            } elseif ($filters['status_filter'] === 'shipped') {
                $sql .= " AND vp_orders.status = 'shipped'";
            } elseif (!empty($filters['status_filter'])) {
                $sql .= " AND vp_orders.status = '" . $filters['status_filter'] . "'";
            } 
        }
        if (!empty($filters['country'])) {
			if($filters['country']=='overseas'){
				$sql .= " AND (vp_orders.shipping_country != 'IN' OR vp_orders.country != 'IN')";
			}else{
				$sql .= " AND (vp_orders.shipping_country = '" . $filters['country'] . "' OR vp_orders.country = '" . $filters['country'] . "' )";
			}
            //$params[] = '%' . $filters['country'] . '%';
        } 
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND vp_orders.groupname LIKE ?";
            $params[] = '%' . $filters['category'] . '%';
        }
        if (!empty($filters['options']) && $filters['options'] === 'express') {
            $sql .= " AND vp_orders.options LIKE '%express%'";
        }
        // Add sorting based on filter
        if (!empty($filters['sort']) && in_array(strtolower($filters['sort']), ['asc', 'desc'])) {
            $sql .= " ORDER BY vp_orders.order_date " . strtoupper($filters['sort']);
        } else {
            $sql .= " ORDER BY vp_orders.order_date DESC"; // Default sort order
        }

        $sql .= " LIMIT ? OFFSET ?";
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
                //$sql .= " AND (po_number IS NOT NULL AND po_number != '')";
                $sql .= " AND vp_orders.status IN ('ready_for_packing','po_pending','po_approved','po_inprogress','item_received','added_to_picklist','store_transfer','ready_for_qc','sent_for_repair')";
            } elseif ($filters['status_filter'] === 'dispatch') {
                $sql .= " AND vp_orders.status IN ('ready_for_dispatch')";
            } elseif ($filters['status_filter'] === 'shipped') {
                $sql .= " AND vp_orders.status = 'shipped'";
            } elseif (!empty($filters['status_filter'])) {
                $sql .= " AND vp_orders.status = '" . $filters['status_filter'] . "'";
            }
        }
        if (!empty($filters['country'])) {
            $sql .= " AND vp_orders.shipping_country = '" . $filters['country'] . "'";
            //$params[] = '%' . $filters['country'] . '%';
        } 
        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $sql .= " AND groupname LIKE ?";
            $params[] = '%' . $filters['category'] . '%';
        }
        if (!empty($filters['options']) && $filters['options'] === 'express') {
            $sql .= " AND options LIKE '%express%'";
        }
        if (!empty($filters['sort']) && in_array(strtolower($filters['sort']), ['asc', 'desc'])) {
            $sql .= " ORDER BY order_date " . strtoupper($filters['sort']);
        } else {
            $sql .= " ORDER BY order_date DESC"; // Default sort order
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
        //print_array($data);
        //echo "<br>";
        // Assuming $data is an associative array with keys matching the database columns
        if (empty($data) || !is_array($data)) {
            return ['success' => false, 'message' => 'Data is empty or not an array.'];
        }
        $required = ['order_number', 'item_code'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Missing required field: {$field}"];
            }
        }

        // ✅ Check for duplicate combination
        $checkSql = "SELECT 1 FROM vp_orders WHERE order_number = ? AND item_code = ? LIMIT 1";
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
            'cost_price', 'location', 'order_date','processed_time','numsold','product_weight','product_weight_unit',
            'prod_height',
            'prod_width',
            'prod_length',
            'length_unit',
            'backorder_status',
            'backorder_percent',
            'backorder_delay',
            'payment_type',
            'coupon',
            'coupon_reduce',
            'giftvoucher',
            'giftvoucher_reduce',
            'credit',
            'vendor',
            'country',
            'material',
            'status',
            'esd'
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
            // If the incoming value is an array, encode it to JSON to avoid "Array to string conversion"
            if (is_array($value)) {
                $value = json_encode($value);
            }
            $values[] = $value;

            if (is_int($value)) {
                $types .= 'i';
            } elseif (is_float($value) || is_double($value)) {
                $types .= 'd';
            } else {
                // treat null and other types as string
                $types .= 's';
            }
        }

        // Bind dynamically
        $stmt->bind_param($types, ...$values);

        // After execute
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
        $insertId = $this->db->insert_id; // ✅ use db object, not stmt
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
                    //'price' => $row['unit_price'],
                    'gst' => $row['gst'],
                    'hsn' => $row['hsn'],
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
        $sql = "UPDATE order_import_log SET end_time = ?, successful_imports = ?, total_orders = ?, error = ?, max_ordered_time = ?, add_product_log = ?, log_details = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssdisssd', $data['end_time'], $data['successful_imports'], $data['total_orders'], $data['error'], $data['max_ordered_time'], $data['add_product_log'], $data['log_details'], $log_id);
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
    public function getLastImportLog() {
        $sql = "SELECT * FROM order_import_log ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);   
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return null;
    }
    public function addProducts($data) {
        if(empty($data['item_code'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        // Check for existing products with the same item_code
        $existingProducts = [];
        $sql = "SELECT * FROM vp_products WHERE item_code = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $data['item_code']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $existingProducts[] = $row;
        }
        if (!empty($existingProducts)) {
            return ['success' => false, 'message' => 'Product with item_code '.$data['item_code'].' already exists.'];
        }
               
        if(!empty($data)) {
        $sql = "INSERT INTO vp_products (item_code, title, description, size, color, groupname, subcategories, itemprice, finalprice, image, gst, hsn, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, cost_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssssiissdisiiisi', 
            $data['item_code'], 
            $data['title'],
            $data['description'],
            $data['size'],
            $data['color'],
            $data['groupname'],
            $data['subcategories'],
            $data['itemprice'],
            $data['finalprice'],
            $data['image'],
            $data['gst'],
            $data['hsn'],
            $data['product_weight'],
            $data['product_weight_unit'],
            $data['prod_height'],
            $data['prod_width'],
            $data['prod_length'],
            $data['length_unit'],   
            $data['cost_price']
        );
        $stmt->execute();
        if ($stmt->error) {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
        //return $stmt->insert_id;
        return ['success' => true, 'message' => 'Product added successfully.', 'insert_id' => $stmt->insert_id];

        } else {
            return false;
        }
    }
    function updateStatus($order_id, $data) {
        if(empty($order_id) || empty($data['status'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        $sql = "UPDATE vp_orders SET status = ?, remarks = ?, esd = ?, priority = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssssi', $data['status'], $data['remarks'], $data['esd'], $data['priority'], $order_id);
        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }
    }
    function getOrderByOrderNumber($order_number) {
        $sql = "SELECT * FROM vp_orders WHERE order_number = ?";
        $stmt = $this->db->prepare($sql);   
        $stmt->bind_param('s', $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            // If only one row is found, return an associative array for backward compatibility,
            // otherwise return all matching rows as an array of associative arrays.
            /*if ($result->num_rows === 1) {
                return $result->fetch_assoc();
            }*/
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return null;
    }
    function adminOrderStatusList($admin = true) {
        $sql = "SELECT * FROM vp_order_status WHERE admin_id != 0 ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $result->fetch_all(MYSQLI_ASSOC);
        }
        //array list with slug as key
        $statusList = [];
        foreach ($result as $row) {
            $statusList[$row['admin_id']] = $row['slug'];
        }
        return $statusList;
    }
    function updateImportedOrder($data) {
        // Validate inputs
        if (empty($data['order_number']) || empty($data['item_code'])) {
            return ['success' => false, 'message' => 'Order number or item code is missing.'];
        }

        // Prepare SQL statement
        $sql = "UPDATE vp_orders SET 
                shipping_country = ?, title = ?, description = ?, size = ?, color = ?, 
                groupname = ?, subcategories = ?, currency = ?, itemprice = ?, finalprice = ?, 
                image = ?, marketplace_vendor = ?, quantity = ?, options = ?, gst = ?, hsn = ?, 
                local_stock = ?, cost_price = ?, location = ?, order_date = ?, processed_time = ?,
                numsold = ?, product_weight = ?, product_weight_unit = ?,
                prod_height = ?, prod_width = ?, prod_length = ?, length_unit = ?,
                backorder_status = ?, backorder_percent = ?, backorder_delay = ?,
                payment_type = ?, coupon = ?, coupon_reduce = ?, giftvoucher = ?, giftvoucher_reduce = ?,
                credit = ?, vendor = ?, country = ?, material = ?, status = ?, esd = ?, updated_at = ?
                WHERE order_number = ? AND item_code = ?";
        $stmt = $this->db->prepare($sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }

        // Bind parameters
        // Prepare values in the same order as the SQL placeholders
        $values = [
            $data['shipping_country'], $data['title'], $data['description'], $data['size'], $data['color'],
            $data['groupname'], $data['subcategories'], $data['currency'], $data['itemprice'], $data['finalprice'],
            $data['image'], $data['marketplace_vendor'], $data['quantity'], json_encode($data['options']), $data['gst'], $data['hsn'],
            $data['local_stock'], $data['cost_price'], $data['location'], $data['order_date'], $data['processed_time'],
            $data['numsold'], $data['product_weight'], $data['product_weight_unit'],
            $data['prod_height'], $data['prod_width'], $data['prod_length'], $data['length_unit'],
            $data['backorder_status'], $data['backorder_percent'], $data['backorder_delay'],
            $data['payment_type'], $data['coupon'], $data['coupon_reduce'], $data['giftvoucher'], $data['giftvoucher_reduce'],
            $data['credit'], $data['vendor'], $data['country'], $data['material'], $data['status'], $data['esd'], $data['updated_at'],
            $data['order_number'], $data['item_code']
        ];

        // Build types string dynamically based on actual PHP types
        $types = '';
        foreach ($values as $val) {
            if (is_int($val)) {
                $types .= 'i';
            } elseif (is_float($val) || is_double($val)) {
                $types .= 'd';
            } else {
                // treat null and other types as string
                $types .= 's';
            }
        }

        // mysqli_stmt::bind_param requires arguments passed by reference when using call_user_func_array
        $refs = [];
        foreach ($values as $key => $value) {
            $refs[$key] = &$values[$key];
        }
        array_unshift($refs, $types);
        call_user_func_array([$stmt, 'bind_param'], $refs);
        // print binded parameters for debugging
        //print_array($refs);        

        //foreach ($refs as $ref) {
            //if ($ref === $types) continue; // Skip types string
            //echo $ref . "\n";
        //}
        //print_r($stmt);
        //comment the below line after execution on 09-06-2024
        // if ($stmt->execute()) {
        //     return ['success' => true, 'message' => 'Order updated successfully.', 'affected_rows' => $stmt->affected_rows, 'order_number' => $data['order_number'], 'item_code' => $data['item_code']];
        // } else {
        //     return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        // }
    }
}
?>