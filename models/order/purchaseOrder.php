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
        $sql = "INSERT INTO purchase_orders (po_number, vendor_id, user_id, expected_delivery_date, delivery_address, notes, total_gst, total_cost, subtotal, shipping_cost) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siisssdddd",
            $data['po_number'],  
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'], 
            $data['delivery_address'], 
            $data['notes'],
            $data['total_gst'],            
            $data['grand_total'],
            $data['subtotal'],
            $data['shipping_cost']
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
    public function updatePurchaseOrder($id, $data) {
        $sql = "UPDATE purchase_orders SET vendor_id = ?, user_id = ?, expected_delivery_date = ?, delivery_address = ?, notes = ?, total_gst = ?, total_cost = ?, subtotal = ?, shipping_cost = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iissssdddi",
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'],
            $data['delivery_address'],
            $data['notes'],
            $data['total_gst'],
            $data['grand_total'],
            $data['subtotal'],
            $data['shipping_cost'],
            $id
        );
        return $stmt->execute();
    }
    public function deletePurchaseOrder($id) {
        $sql = "DELETE FROM purchase_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function updateStatus($id, $status) {
        $allowedStatuses = ['pending', 'ordered', 'received', 'cancelled']; // Define allowed statuses
        if (!in_array($status, $allowedStatuses)) {
            return false; // Invalid status
        }
        $received_at = ($status === 'received') ? date('Y-m-d H:i:s') : null;
        if ($received_at) {
            $sql = "UPDATE purchase_orders SET status = ?, received_at = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ssi", $status, $received_at, $id);
        } else {
            $sql = "UPDATE purchase_orders SET status = ?, received_at = NULL WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $status, $id);
        }        
        return $stmt->execute();
    }
    public function toggleStar($id) {
        // First, get the current star status
        $sql = "SELECT flag_star FROM purchase_orders WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $newStatus = $row['flag_star'] ? 0 : 1; // Toggle the status
            // Update the star status
            $updateSql = "UPDATE purchase_orders SET flag_star = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->bind_param("ii", $newStatus, $id);
            return $updateStmt->execute();
        }
        return false; // Return false if the purchase order was not found
    }
}