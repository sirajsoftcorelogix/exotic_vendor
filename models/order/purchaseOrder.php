<?php 
class PurchaseOrder {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllPurchaseOrders() {
        $sql = "SELECT * FROM purchase_orders ORDER BY id DESC";
        $result = $this->db->query($sql);   
        $purchaseOrders = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {

                $purchaseOrders[] = $row;
            }
        }
        return $purchaseOrders;
    }   
    
}