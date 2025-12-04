<?php 
class PurchaseOrderItem {
    private $conn;  
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getAllPurchaseOrderItems() {
        $query = "SELECT * FROM vp_po_items";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $items = [];    
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }   
        return $items;
    }
    public function getPurchaseOrderItemById($po_id) {
        $query = "SELECT * FROM vp_po_items WHERE purchase_orders_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function getPurchaseOrderItemByIdNew($po_id) {
        $query = "SELECT poi.*,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.product_weight
                        ELSE vo.product_weight
                    END AS product_weight,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.product_weight_unit
                        ELSE vo.product_weight_unit
                    END AS product_weight_unit,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.prod_height
                        ELSE vo.prod_height
                    END AS prod_height,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.prod_width
                        ELSE vo.prod_width
                    END AS prod_width,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.prod_length
                        ELSE vo.prod_length
                    END AS prod_length,
                    CASE 
                        WHEN poi.product_id IS NOT NULL AND poi.product_id != '' 
                        THEN vp.length_unit
                        ELSE vo.length_unit
                    END AS length_unit
                FROM vp_po_items poi
                LEFT JOIN vp_products vp ON poi.product_id = vp.id
                LEFT JOIN vp_orders vo ON poi.order_number = vo.order_number
                WHERE poi.purchase_orders_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function createPurchaseOrderItem($data) {
        $query = "INSERT INTO vp_po_items (purchase_orders_id, order_number, title, image, hsn, gst, quantity, price, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ? )";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iisssiddd", 
            $data['purchase_orders_id'],
            $data['order_number'], 
            $data['title'],
            $data['image'],
            $data['hsn'],
            $data['gst'],
            $data['quantity'],
            $data['price'],
            $data['amount']
        );
        if ($stmt->execute()) {
            return $this->conn->insert_id; // Return the ID of the newly created item
        } else {
            return false; // Return false on failure
        }
    }
    public function createCustomPoItem($data) {
        $query = "INSERT INTO vp_po_items (purchase_orders_id, item_code, product_id, title, image, hsn, gst, quantity, price, amount) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isissidddd", 
            $data['purchase_orders_id'],
            $data['item_code'],
            $data['product_id'], 
            $data['title'],
            $data['image'],
            $data['hsn'],
            $data['gst'],
            $data['quantity'],
            $data['price'],
            $data['amount']
        );
        if ($stmt->execute()) {
            return $this->conn->insert_id; // Return the ID of the newly created item
        } else {
            return false; // Return false on failure
        }
    }
    public function deletePurchaseOrderItem($id) {
        $query = "DELETE FROM vp_po_items WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }
    public function updatePurchaseOrderItems($id, $data) {
        $query = "UPDATE vp_po_items SET title = ?, gst = ?, quantity = ?, price = ?, amount = ? WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("siiddi",
            $data['title'],             
            $data['gst'],
            $data['quantity'],
            $data['price'],
            $data['amount'],
            $id
        );
        return $stmt->execute();
    }
    public function deletePurchaseOrderItemsByPOId($po_id) {
        $query = "DELETE FROM vp_po_items WHERE purchase_orders_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $po_id);
        return $stmt->execute();
    }
}