<?php
class vendor {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getAllVendors() {
        $sql = "SELECT * FROM vp_vendors ORDER BY contact_name ASC";
        $result = $this->conn->query($sql);
        $vendors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }
        return $vendors;
    }
    public function getVendorById($id) {
        $sql = "SELECT * FROM vp_vendors WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function addVendor($data) {
        // Check if vendor_email already exists
        $checkEmailSql = "SELECT id FROM vp_vendors WHERE vendor_email = ?";
        $checkEmailStmt = $this->conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param('s', $data['vendor_email']);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();
        if ($checkEmailStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Vendor email already exists. Please use a different email.'];
        }
        $checkEmailStmt->close();

        // Check if gst_number already exists (if provided)
        if (!empty($data['gst_number'])) {
            $checkGstSql = "SELECT id FROM vp_vendors WHERE gst_number = ?";
            $checkGstStmt = $this->conn->prepare($checkGstSql);
            $checkGstStmt->bind_param('s', $data['gst_number']);
            $checkGstStmt->execute();
            $checkGstStmt->store_result();
            if ($checkGstStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'GST number already exists. Please use a different GST number.'];
            }
            $checkGstStmt->close();
        }

        // Check if pan_number already exists (if provided)
        if (!empty($data['pan_number'])) {
            $checkPanSql = "SELECT id FROM vp_vendors WHERE pan_number = ?";
            $checkPanStmt = $this->conn->prepare($checkPanSql);
            $checkPanStmt->bind_param('s', $data['pan_number']);
            $checkPanStmt->execute();
            $checkPanStmt->store_result();
            if ($checkPanStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'PAN number already exists. Please use a different PAN number.'];
            }
            $checkPanStmt->close();
        }

        $sql = "INSERT INTO vp_vendors (contact_name, vendor_email, company_name, vendor_phone, gst_number, pan_number, address, city, state, country, postal_code, business_type, document_path, logo_path, email_verified_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssss', 
            $data['contact_name'], 
            $data['vendor_email'],
            $data['company_name'],
            $data['phone'],
            $data['gst_number'],
            $data['pan_number'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['country'],
            $data['postal_code'],
            $data['business_type'],
            $data['document_path'],
            $data['logo_path'],
            $data['email_verified_at'],
            $data['is_active']
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vendor added successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateVendor($id, $data) {
        $sql = "UPDATE vp_vendors SET contact_name = ?, vendor_email = ?, company_name = ?, vendor_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, business_type = ?, document_path = ?, logo_path = ?, email_verified_at = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssssi', 
            $data['contact_name'],
            $data['vendor_email'],
            $data['company_name'],
            $data['phone'], 
            $data['gst_number'],
            $data['pan_number'],
            $data['address'],
            $data['city'],
            $data['state'],
            $data['country'],
            $data['postal_code'],
            $data['business_type'],
            $data['document_path'],
            $data['logo_path'],
            $data['email_verified_at'],
            $data['is_active'],
            $id
        );
        return $stmt->execute();

        
    }
    public function deleteVendor($id) {
        $sql = "DELETE FROM vp_vendors WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

}
?>