<?php
class product{
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function getProduct($id) {
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE id = ?");
        if ($stmt === false) {
            return null;
        }
        $id = (int)$id;
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }
    public function getAllProducts($limit, $offset, $search = '') {
        $limit = (int)$limit;
        $offset = (int)$offset;
        $search = "%$search%";
        $stmt = $this->db->prepare("SELECT * FROM vp_products WHERE title LIKE ? OR item_code LIKE ? LIMIT ? OFFSET ?");
        if ($stmt === false) {
            return [];
        }
        $stmt->bind_param('ssii', $search, $search, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    public function countAllProducts($search = '') {
        $search = "%$search%";
        $stmt = $this->db->prepare("SELECT COUNT(*) AS cnt FROM vp_products WHERE title LIKE ?");
        if ($stmt === false) {
            return 0;
        }
        $stmt->bind_param('s', $search);
        $stmt->execute();
        $result = $stmt->get_result();  
        if ($result === false) {
            return 0;
        }
        $row = $result->fetch_assoc();
        return isset($row['cnt']) ? (int)$row['cnt'] : 0;
    }
    public function getProductItems($search = '') {
        $searchTerm = "%$search%";
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
}