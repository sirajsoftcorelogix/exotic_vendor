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
    public function createPurchaseOrder($data) {
        $sql = "INSERT INTO purchase_orders (po_number, vendor_id, expected_delivery_date, delivery_address, notes, total_gst, total_cost) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("sisssdd",
            $data['po_number'],  
            $data['vendor_id'], 
            $data['expected_delivery_date'], 
            $data['delivery_address'], 
            $data['notes'],
            $data['total_gst'], 
            $data['grand_total']
        );
        if ($stmt->execute()) {
            return $this->db->insert_id; // Return the ID of the newly created purchase order
        }
        return false; // Return false on failure
    }
    public function cancelPurchaseOrder($id) {
        $sql = "UPDATE purchase_orders SET status = 'cancelled' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function getPurchaseOrder($id) {
        $sql = "SELECT * FROM purchase_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}