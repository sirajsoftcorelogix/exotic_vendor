<?php 

class Order{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function insertOrder($data) {
        print_r($data);
        // Assuming $data is an associative array with keys matching the database columns
        if(empty($data['order_number']) || empty($data['customer_name']) || empty($data['amount']) || empty($data['order_date'])) {
            return ['success' => false, 'message' => 'Required fields are missing.'];
        }
        if(!empty($data)) {
        $sql = "INSERT INTO vp_orders (order_number, customer_name, amount, order_date) VALUES (?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssds', $data['order_number'], $data['customer_name'], $data['amount'], $data['order_date']);
        $stmt->execute();
        return $stmt->insert_id;
        } else {
            return false;
        }
    }

}
?> 