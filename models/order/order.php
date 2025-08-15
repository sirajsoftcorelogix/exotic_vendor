<?php 

class Order{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllOrders() {
        $sql = "SELECT * FROM vp_orders ORDER BY order_date DESC";
        $result = $this->db->query($sql);
        $orders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        return $orders;
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
        $required = ['order_number', 'item_code', 'title', 'quantity'];
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
            (order_number, title, item_code, size, color, description, image, marketplace_vendor, quantity, options) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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

        if (!$stmt->execute()) {
            $stmt->close();
            return ['success' => false, 'message' => 'Database error: ' . $stmt->error];
        }

        $insertId = $stmt->insert_id;
        $stmt->close();

        return ['success' => true, 'insert_id' => $insertId];
    }


}
?> 