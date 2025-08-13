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
        $sql = "INSERT INTO vp_vendors (contact_name, vendor_email, company_name, vendor_phone, gst_number, pan_number, address, city, state, country, postal_code, business_type, document_path, logo_path, email_verified_at, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssss', 
            $data['contact_name'], 
            $data['email'],
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
        return $stmt->execute();
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