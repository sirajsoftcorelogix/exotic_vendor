<?php
class vendor {
    private $conn;
    public function __construct($conn) {
        $this->conn = $conn;
    }
    public function getAllVendors() {
        $sql = "SELECT * FROM vp_vendors WHERE is_active=1 ORDER BY contact_name ASC";
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
    public function getBankDetailsById($vendor_id) {
        global $secretKey;
        $sql = "SELECT vendor_id,
                CAST(AES_DECRYPT(account_holder_name, UNHEX(SHA2(?,512))) AS CHAR) AS account_name,
                CAST(AES_DECRYPT(account_number, UNHEX(SHA2(?,512))) AS CHAR) AS account_number,
                CAST(AES_DECRYPT(ifsc_code, UNHEX(SHA2(?,512))) AS CHAR) AS ifsc_code,
                CAST(AES_DECRYPT(bank_name, UNHEX(SHA2(?,512))) AS CHAR) AS bank_name,
                CAST(AES_DECRYPT(branch_name, UNHEX(SHA2(?,512))) AS CHAR) AS branch_name, is_active FROM vendor_bank_details WHERE vendor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssssi', $secretKey, $secretKey, $secretKey, $secretKey, $secretKey, $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function saveBankDetails($data) {
        global $secretKey;
        $sql = "INSERT INTO vendor_bank_details (vendor_id, account_holder_name, account_number, ifsc_code, bank_name, branch_name, is_active) VALUES (?, AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), ?) ON DUPLICATE KEY UPDATE account_holder_name = VALUES(account_holder_name), account_number = VALUES(account_number), ifsc_code = VALUES(ifsc_code), bank_name = VALUES(bank_name), branch_name = VALUES(branch_name), is_active = VALUES(is_active)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('issssssssssi',
            $data['vendor_id'],
            $data['account_name'], $secretKey,
            $data['account_number'], $secretKey,
            $data['ifsc_code'], $secretKey,
            $data['bank_name'], $secretKey,
            $data['branch_name'], $secretKey,
            $data['bdStatus'],
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Bank details saved successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateBankDetails($data) {
        global $secretKey;
        $sql = "UPDATE vendor_bank_details SET account_holder_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), account_number = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), ifsc_code = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), bank_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), branch_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), is_active = ? WHERE vendor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssii', 
            $data['account_name'], $secretKey, 
            $data['account_number'], $secretKey,
            $data['ifsc_code'], $secretKey,
            $data['bank_name'], $secretKey,
            $data['branch_name'], $secretKey,
            $data['bdStatus'], $data["vendor_id"]
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Bank details updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function addVendor($data) {
        // Check if vendor_email already exists
        /*$checkEmailSql = "SELECT id FROM vp_vendors WHERE vendor_email = ?";
        $checkEmailStmt = $this->conn->prepare($checkEmailSql);
        $checkEmailStmt->bind_param('s', $data['vendor_email']);
        $checkEmailStmt->execute();
        $checkEmailStmt->store_result();
        if ($checkEmailStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Vendor email already exists. Please use a different email.'];
        }
        $checkEmailStmt->close();*/

        // Check if gst_number already exists (if provided)
        if (!empty($data['addGstNumber'])) {
            $checkGstSql = "SELECT id FROM vp_vendors WHERE gst_number = ?";
            $checkGstStmt = $this->conn->prepare($checkGstSql);
            $checkGstStmt->bind_param('s', $data['addGstNumber']);
            $checkGstStmt->execute();
            $checkGstStmt->store_result();
            if ($checkGstStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'GST number already exists. Please use a different GST number.'];
            }
            $checkGstStmt->close();
        }

        // Check if pan_number already exists (if provided)
        if (!empty($data['addPanNumber'])) {
            $checkPanSql = "SELECT id FROM vp_vendors WHERE pan_number = ?";
            $checkPanStmt = $this->conn->prepare($checkPanSql);
            $checkPanStmt->bind_param('s', $data['addPanNumber']);
            $checkPanStmt->execute();
            $checkPanStmt->store_result();
            if ($checkPanStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'PAN number already exists. Please use a different PAN number.'];
            }
            $checkPanStmt->close();
        }

        $sql = "INSERT INTO vp_vendors (vendor_name, contact_name, vendor_email, vendor_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, rating, notes, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssssssssssssss',
            $data['addVendorName'],
            $data['addContactPerson'],
            $data['addEmail'],
            $data['addPhone'],
            $data['addAltPhone'],
            $data['addGstNumber'],
            $data['addPanNumber'],
            $data['addAddress'],
            $data['addCity'],
            $data['addState'],
            $data['addCountry'],
            $data['addPostalCode'],
            $data['addRating'],
            $data['addNotes'],
            $data['addStatus']
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
        $sql = "UPDATE vp_vendors SET vendor_name = ?, contact_name = ?, vendor_email = ?, vendor_phone = ?, alt_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, rating = ?, notes = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssssssssssssssi', 
            $data['editVendorName'],
            $data['editContactPerson'],
            $data['editEmail'],
            $data['editPhone'],
            $data['editAltPhone'],
            $data['editGstNumber'],
            $data['editPanNumber'],
            $data['editAddress'],
            $data['editCity'],
            $data['editState'],
            $data['editCountry'],
            $data['editPostalCode'],
            $data['editRating'],
            $data['editNotes'],
            $data['editStatus'],
            $id
        );
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vendor updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function deleteVendor($id) {
        // Check if Order(s) exists
        $checkOrdersSql = "SELECT id FROM vp_orders WHERE vendor_id = ?";
        $checkOrdersStmt = $this->conn->prepare($checkOrdersSql);
        $checkOrdersStmt->bind_param('i', $id);
        $checkOrdersStmt->execute();
        $checkOrdersStmt->store_result();
        if ($checkOrdersStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Vendor can not be deleted. Order(s) exists in the database.'];
        }
        $checkOrdersStmt->close();

        // Delete Bank details of the Vendor
        $sql = "DELETE FROM vendor_bank_details WHERE vendor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $sql = "DELETE FROM vp_vendors WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vendor deleted successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Delete failed: ' . $stmt->error . '. Please try again later.'
        ];
    }
    public function getAllVendorsListing($page = 1, $limit = 10, $search = '', $status_filter = '') {
        // sanitize
        $page = (int)$page;
        if ($page < 1) $page = 1;

        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;

        // calculate offset
        $offset = ($page - 1) * $limit;

        // 🔹 Build search condition
        $where = "";
        if (!empty($search) && !empty($status_filter)) {
            $search = $this->conn->real_escape_string($search);
            $status_filter = $this->conn->real_escape_string($status_filter);
            $where = "WHERE (vendor_name LIKE '%$search%' OR contact_name LIKE '%$search%' OR vendor_email LIKE '%$search%' OR vendor_phone LIKE '%$search%' OR city LIKE '%$search%' OR state LIKE '%$search%') AND is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE vendor_name LIKE '%$search%' OR contact_name LIKE '%$search%' OR vendor_email LIKE '%$search%' OR vendor_phone LIKE '%$search%' OR city LIKE '%$search%' OR state LIKE '%$search%'";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE is_active = '$status_filter'";
            }
        }

        // total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_vendors $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT * FROM vp_vendors $where LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $vendors = [];
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }

        // return structured data
        return [
            'vendors'        => $vendors,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
    }
}
?>