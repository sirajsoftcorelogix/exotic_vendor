<?php 
class PurchaseOrder {
    private $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getAllPurchaseOrders( $filters = [] ) {
        $sql = "SELECT purchase_orders.*, vp_vendors.vendor_name AS vendor_name FROM purchase_orders LEFT JOIN vp_vendors ON purchase_orders.vendor_id = vp_vendors.id WHERE 1=1";
        if (!empty($filters['search_text'])) {
            $searchText = $this->db->real_escape_string($filters['search_text']);
            $sql .= " AND (purchase_orders.po_number LIKE '%$searchText%' OR vp_vendors.contact_name LIKE '%$searchText%')";
        }
        if (!empty($filters['status_filter'])) {
            $statusFilter = $this->db->real_escape_string($filters['status_filter']);
            $sql .= " AND purchase_orders.status = '$statusFilter'";
        }       
        if (!empty($filters['due_date'])) {
            $dueDate = $this->db->real_escape_string($filters['due_date']);
            $sql .= " AND purchase_orders.expected_delivery_date = '$dueDate'";
        }
        
        // po_amount_from and po_amount_to filter
        if (!empty($filters['po_amount_from'])) {
            $poAmountFrom = (float)$filters['po_amount_from'];
            $sql .= " AND purchase_orders.total_cost >= $poAmountFrom";
        }
        if (!empty($filters['po_amount_to'])) {
            $poAmountTo = (float)$filters['po_amount_to'];
            $sql .= " AND purchase_orders.total_cost <= $poAmountTo";
        }
        //po_number filter
        if (!empty($filters['po_number'])) {
            $poNumber = $this->db->real_escape_string($filters['po_number']);
            $sql .= " AND purchase_orders.po_number LIKE '%$poNumber%'";
        }
        //vendor_name filter
        if (!empty($filters['vendor_name'])) {
            $vendorName = $this->db->real_escape_string($filters['vendor_name']);
            $sql .= " AND vp_vendors.vendor_name LIKE '%$vendorName%'";
        }
        //po_date_from and po_date_to filter
        if (!empty($filters['po_from']) && !empty($filters['po_to'])) {
            $poDateFrom = $this->db->real_escape_string($filters['po_from']);
            $poDateTo = $this->db->real_escape_string($filters['po_to']);
            $sql .= " AND purchase_orders.po_date >= '$poDateFrom' AND purchase_orders.po_date < '$poDateTo'";
        }
        // item_category filter left join with vp_order
        // item_category/item_sub_category/item_code filter -> search records in vp_order linked by po_id
        if (!empty($filters['item_category']) || !empty($filters['item_sub_category']) || !empty($filters['item_code'])) {
            $conditions = [];
            if (!empty($filters['item_category'])) {
            $itemCategory = $this->db->real_escape_string($filters['item_category']);
            $conditions[] = "vo.groupname = '$itemCategory'";
            }
            if (!empty($filters['item_sub_category'])) {
            $itemSubCategory = $this->db->real_escape_string($filters['item_sub_category']);
            $conditions[] = "vo.subcategories LIKE '%$itemSubCategory%'";
            }
            if (!empty($filters['item_code'])) {
            $itemCode = $this->db->real_escape_string($filters['item_code']);
            $conditions[] = "vo.item_code LIKE '%$itemCode%'";
            }
            if (!empty($conditions)) {
            // Use EXISTS to search vp_order (alias vo) linked by po_id to avoid breaking the original FROM/WHERE order
            $sql .= " AND EXISTS (SELECT 1 FROM vp_orders vo WHERE vo.po_id = purchase_orders.id AND " . implode(' AND ', $conditions) . ")";
            }
        }

        $sql .= " ORDER BY purchase_orders.id DESC";
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
        $sql = "INSERT INTO purchase_orders (po_number, vendor_id, user_id, expected_delivery_date, delivery_address, notes, terms_and_conditions, total_gst, total_cost, subtotal, shipping_cost, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("siissssdddds",
            $data['po_number'],  
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'], 
            $data['delivery_address'], 
            $data['notes'],
            $data['terms_and_conditions'],
            $data['total_gst'],            
            $data['grand_total'],
            $data['subtotal'],
            $data['shipping_cost'],
            $data['status']
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
        $sql = "SELECT purchase_orders.*, vp_vendors.contact_name AS vendor_name, vp_vendors.vendor_phone AS vendor_phone, vp_vendors.vendor_email AS vendor_email FROM purchase_orders LEFT JOIN vp_vendors ON purchase_orders.vendor_id = vp_vendors.id WHERE purchase_orders.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function updatePurchaseOrder($id, $data) {
        $sql = "UPDATE purchase_orders SET vendor_id = ?, user_id = ?, expected_delivery_date = ?, delivery_address = ?, notes = ?, terms_and_conditions = ?, total_gst = ?, total_cost = ?, subtotal = ?, shipping_cost = ?, status = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("iisssssdddsi",
            $data['vendor_id'],
            $data['user_id'],
            $data['expected_delivery_date'],
            $data['delivery_address'],
            $data['notes'],
            $data['terms_and_conditions'],
            $data['total_gst'],
            $data['total_cost'],
            $data['subtotal'],
            $data['shipping_cost'],
            $data['status'],
            $id
        );
        return $stmt->execute();
    }
    public function updatePurchaseOrderNumber($id, $data) {
        $sql = "UPDATE purchase_orders SET po_number = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si",
            $data['po_number'],
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
        $allowedStatuses = ['pending', 'ordered', 'received', 'cancelled', 'draft']; // Define allowed statuses
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
    public function get_po_status_log($po_id) {
        $sql = "SELECT vpl.*, u.name AS changed_by_username FROM vp_po_status_log vpl LEFT JOIN vp_users u ON vpl.changed_by = u.id WHERE vpl.po_id = ? ORDER BY vpl.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("i", $po_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $logs = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logs[] = $row;
            }
        }
        return $logs;
    }
    public function updateInvoicePath($id, $invoicePath) {
        $sql = "UPDATE purchase_orders SET vendor_invoice = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $invoicePath, $id);
        return $stmt->execute();
    }
    public function updateCancellationReason($id, $reason) {
        $sql = "UPDATE purchase_orders SET cancellation_reason = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param("si", $reason, $id);
        return $stmt->execute();
    }
}