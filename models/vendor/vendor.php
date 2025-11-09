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
        if (isset($secretKey)) {
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
        } else {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }
    }
    public function saveBankDetails($data) {
        global $secretKey;
        if (isset($secretKey)) {
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
        } else {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }
        
    }
    public function updateBankDetails($data) {
        global $secretKey;
        if (isset($secretKey)) {
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
        } else {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }
    }
    public function addVendor($data) {
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

        $sql = "INSERT INTO vp_vendors (vendor_name, contact_name, vendor_email, vendor_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, rating, notes, user_id, team_id, agent_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssiiis',
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
            $_SESSION["user"]["id"],
            $data['addTeam'],
            $data['addAgent'],
            $data['addStatus']
        );
        if ($stmt->execute()) {
            // Get the last inserted vendor id
            $vendor_id = $this->conn->insert_id;
            $cat_status = '';
            // Add vendor categories if provided
            if (!empty($data['addVendorCategory']) && is_array($data['addVendorCategory'])) {
               $cat_status = $this->addVendorCategory($vendor_id, $data['addVendorCategory']);
            }
            return ['success' => true, 'message' => 'Vendor added successfully.', 'category_status' => $cat_status];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateVendor($id, $data) {
        $sql = "UPDATE vp_vendors SET vendor_name = ?, contact_name = ?, vendor_email = ?, vendor_phone = ?, alt_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, rating = ?, notes = ?, user_id = ?, team_id = ?, agent_id = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssiiisi', 
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
            $_SESSION["user"]["id"],
            $data['editTeam'],
            $data['editAgent'],
            $data['editStatus'],
            $id
        );
        if ($stmt->execute()) {
            // Get the last inserted vendor id
            $vendor_id = $id;
            $cat_status = '';
            // Add vendor categories if provided
            if (!empty($data['addVendorCategory']) && is_array($data['addVendorCategory'])) {
               $cat_status = $this->addVendorCategory($vendor_id, $data['addVendorCategory']);
            }
            return ['success' => true, 'message' => 'Vendor updated successfully.','cat_status'=>$cat_status];
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
    public function listCategory(){
        $sql = "SELECT * FROM category WHERE is_active=1 ORDER BY parent_id ASC, display_name ASC";
        $result = $this->conn->query($sql);
        $category = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
            $parent_id = $row['parent_id'];
            if (!isset($category[$parent_id])) {
                $category[$parent_id] = [];
            }
            $category[$parent_id][] = $row;
            }
        }
        return $category;
    }
    public function addVendorCategory($vendor_id,$category){       
        if (empty($vendor_id)) {
            return ['success' => false, 'message' => 'Vendor ID is required.'];
        }

        if (empty($category) || !is_array($category)) {
            return ['success' => false, 'message' => 'Category is required and must be an array.'];
        }

        // Delete previous categories for this vendor
        $deleteSql = "DELETE FROM vendors_category WHERE vendor_id = ?";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $vendor_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new categories
        $sql = "INSERT INTO vendors_category (vendor_id, category_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        foreach ($category as $cat_id) {
            $stmt->bind_param('ii', $vendor_id, $cat_id);
            $stmt->execute();
        }
        $stmt->close();

        return ['success' => true, 'message' => 'Categories updated successfully.'];
    }
    public function getVendorCategories($vendor_id) {
        $sql = "SELECT category_id FROM vendors_category WHERE vendor_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category_id'];
        }
        return $categories;
    }
    public function getTeamMembers($team_id) {
        $sql = "SELECT id, name FROM vp_users WHERE is_active = 1 AND team_id = '$team_id' ORDER BY name ASC";
        $result = $this->conn->query($sql);
        $teamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $teamMembers[] = $row;
            }
        }
        return $teamMembers;
    }
    public function searchVendors($term) {
        $term = $this->conn->real_escape_string($term);
        $sql = "SELECT id, vendor_name FROM vp_vendors WHERE vendor_name LIKE '%$term%' LIMIT 10";
        $result = $this->conn->query($sql);
        $vendors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }
        return $vendors;
    }
}
?>