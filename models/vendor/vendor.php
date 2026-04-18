<?php
class vendor {
    /** Stamped on vp_vendors rows created/updated from vendor-api/products/vendorlist import */
    public const VENDORLIST_IMPORT_USER_ID = 1000;

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
        // Vendor Name
        if (!empty($data['addVendorName'])) {
            $checkGstSql = "SELECT id FROM vp_vendors WHERE vendor_name = ?";
            $checkGstStmt = $this->conn->prepare($checkGstSql);
            $checkGstStmt->bind_param('s', $data['addVendorName']);
            $checkGstStmt->execute();
            $checkGstStmt->store_result();
            if ($checkGstStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'Vendor name already exists. Please use a differentVendor name.'];
            }
            $checkGstStmt->close();
        }
        // Phone Number
        if (!empty($data['addPhone'])) {
            $checkGstSql = "SELECT id FROM vp_vendors WHERE vendor_phone = ?";
            $checkGstStmt = $this->conn->prepare($checkGstSql);
            $checkGstStmt->bind_param('s', $data['addPhone']);
            $checkGstStmt->execute();
            $checkGstStmt->store_result();
            if ($checkGstStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'Phone number already exists. Please use a different Phone number.'];
            }
            $checkGstStmt->close();
        }
        //Email
        if (!empty($data['addEmail'])) {
            $checkGstSql = "SELECT id FROM vp_vendors WHERE vendor_email = ?";
            $checkGstStmt = $this->conn->prepare($checkGstSql);
            $checkGstStmt->bind_param('s', $data['addEmail']);
            $checkGstStmt->execute();
            $checkGstStmt->store_result();
            if ($checkGstStmt->num_rows > 0) {
                return ['success' => false, 'message' => 'Email already exists. Please use a different Email.'];
            }
            $checkGstStmt->close();
        }
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
        $vendorCode = generateVendorCode($this->conn);
        $sql = "INSERT INTO vp_vendors (vendor_code, vendor_name, contact_name, vendor_email, country_code, vendor_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, rating, notes, user_id, team_id, agent_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ssssssssssssssssiiis',
            $vendorCode,
            $data['addVendorName'],
            $data['addContactPerson'],
            $data['addEmail'],
            $data['addCountryCode'],
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
            $data['addTeamMember'],
            $data['addStatus']
        );
        if ($stmt->execute()) {
            // Get the last inserted vendor id
            $vendor_id = $this->conn->insert_id;
            $cat_status = $tm_status = '';
            // Add vendor categories if provided
            if (!empty($data['addVendorCategory']) && is_array($data['addVendorCategory'])) {
               $cat_status = $this->addVendorCategory($vendor_id, $data['addVendorCategory']);
            }
            // Add vendor teams
            if (!empty($data['addTeam']) && is_array($data['addTeam'])) {
               $tm_status = $this->addVendorTeams($vendor_id, $data['addTeam']);
            }

            return ['success' => true, 'message' => 'Vendor added successfully.', 'category_status' => $cat_status, 'team_status' => $tm_status];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateVendor($id, $data) {
        $sql = "UPDATE vp_vendors SET vendor_name = ?, contact_name = ?, vendor_email = ?, country_code = ?, vendor_phone = ?, alt_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, rating = ?, notes = ?, user_id = ?, team_id = ?, agent_id = ?, is_active = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('sssssssssssssssiiisi', 
            $data['editVendorName'],
            $data['editContactPerson'],
            $data['editEmail'],
            $data['editCountryCode'],
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
            $data['editTeamMember'],
            $data['editStatus'],
            $id
        );
        if ($stmt->execute()) {
            // Get the last inserted vendor id
            $vendor_id = $id;
            $cat_status = $tm_status = '';
            // Add vendor categories if provided
            if (!empty($data['addVendorCategory']) && is_array($data['addVendorCategory'])) {
               $cat_status = $this->addVendorCategory($vendor_id, $data['addVendorCategory']);
            }
            // Add vendor teams
            if (!empty($data['editTeam']) && is_array($data['editTeam'])) {
               $tm_status = $this->addVendorTeams($vendor_id, $data['editTeam']);
            }
            return ['success' => true, 'message' => 'Vendor updated successfully.','cat_status'=>$cat_status, 'team_status' => $tm_status];
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
    public function getAllVendorsListing($page = 1, $limit = 10, $search = '', $status_filter = '', $category_filter = '', $team_filter = '') {
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
                $where = "WHERE vp.vendor_name LIKE '%$search%' OR vp.contact_name LIKE '%$search%' OR vp.vendor_email LIKE '%$search%' OR vp.vendor_phone LIKE '%$search%' OR vp.city LIKE '%$search%' OR vp.state LIKE '%$search%'";
            }

            if (!empty($status_filter)) {
                $search = $this->conn->real_escape_string($status_filter);   
                $where = "WHERE vp.is_active = '$status_filter'";
            }
            if (!empty($category_filter)) {
                $search = $this->conn->real_escape_string($category_filter);   
                $where = "WHERE vc.category_id = '$category_filter'";
            }
            if (!empty($team_filter)) {
                $search = $this->conn->real_escape_string($team_filter);   
                $where = "WHERE vvtm.team_id = '$team_filter'";
            }
        }

        // total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_vendors AS vp LEFT JOIN vp_users AS vu ON vp.agent_id = vu.id AND vu.is_deleted = 0 LEFT JOIN vendors_category AS vc ON vp.id = vc.vendor_id LEFT JOIN vp_vendor_team_mapping AS vvtm ON vp.id = vvtm.vendor_id $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];
        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT vp.*, vu.name AS agent_name, GROUP_CONCAT(DISTINCT vc.category_id) AS categories, GROUP_CONCAT(DISTINCT vvtm.team_id) AS teams FROM vp_vendors AS vp LEFT JOIN vp_users AS vu ON vp.agent_id = vu.id AND vu.is_deleted = 0 LEFT JOIN vendors_category AS vc ON vp.id = vc.vendor_id LEFT JOIN vp_vendor_team_mapping AS vvtm ON vp.id = vvtm.vendor_id $where GROUP BY vp.id LIMIT $limit OFFSET $offset";
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
        $sql = "SELECT * FROM vp_vendor_category WHERE is_active=1 ORDER BY parent_id ASC, category_name ASC";
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
    public function addVendorTeams($vendor_id,$category){       
        if (empty($vendor_id)) {
            return ['success' => false, 'message' => 'Vendor ID is required.'];
        }

        if (empty($category) || !is_array($category)) {
            return ['success' => false, 'message' => 'Teams is required and must be an array.'];
        }

        // Delete previous categories for this vendor
        $deleteSql = "DELETE FROM vp_vendor_team_mapping WHERE vendor_id = ?";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $vendor_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new categories
        $sql = "INSERT INTO vp_vendor_team_mapping (vendor_id, team_id) VALUES (?, ?)";
        $stmt = $this->conn->prepare($sql);
        foreach ($category as $tm_id) {
            $stmt->bind_param('ii', $vendor_id, $tm_id);
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
    public function getVendorTeams($v_id) {
        $sql = "SELECT team_id FROM vp_vendor_team_mapping WHERE vendor_id = ".$v_id;
        $result = $this->conn->query($sql);
        $vTeamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vTeamMembers[] = $row["team_id"];
            }
        }
        return $vTeamMembers;
    }
    public function getTeamMembers($team_id) {
        $sql = "SELECT vu.id as user_id, vu.name, vt.id as team_id, vt.team_name FROM vp_users AS vu INNER JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id INNER JOIN vp_teams AS vt ON vutm.team_id = vt.id WHERE vu.is_active = 1 AND vu.is_deleted = 0 AND vutm.team_id IN (".$team_id.") ORDER BY vt.team_name, vu.id ASC";
        $result = $this->conn->query($sql);
        $teamMembers = [];
        if ($result && $result->num_rows > 0) {
            while($row = mysqli_fetch_assoc($result)) {
                $teamId = $row['team_id'];
                if (!isset($teamMembers[$teamId])) {
                    $teamMembers[$teamId] = [
                        'team_name' => $row['team_name'],
                        'agents' => []
                    ];
                }
                $teamMembers[$teamId]['agents'][] = [
                    'id' => $row['user_id'],
                    'name' => $row['name']
                ];
            }
        }
        header('Content-Type: application/json');
        echo json_encode(array_values($teamMembers));
        exit;
    }
    public function searchVendors($term) {
        $term = $this->conn->real_escape_string($term);
        $sql = "SELECT * FROM vp_vendors WHERE vendor_name LIKE '%$term%' LIMIT 10";
        $result = $this->conn->query($sql);
        $vendors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }
        return $vendors;
    }
    public function checkVendorName($val) {
        $stmt = $this->conn->prepare("SELECT id FROM vp_vendors WHERE vendor_name = ?");
        $stmt->bind_param("s", $val);
        $stmt->execute();
        $stmt->store_result();
        $response = ['exists' => $stmt->num_rows > 0];
        return $response;
    }
    public function checkEmail($email) {
        $stmt = $this->conn->prepare("SELECT id FROM vp_vendors WHERE vendor_email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $response = ['exists' => $stmt->num_rows > 0];
        return $response;
    }
    public function checkPhoneNumber($val) {
        $stmt = $this->conn->prepare("SELECT id FROM vp_vendors WHERE vendor_phone = ?");
        $stmt->bind_param("s", $val);
        $stmt->execute();
        $stmt->store_result();
        $response = ['exists' => $stmt->num_rows > 0];
        return $response;
    }
    public function getProductsByVendorId($vendor_id) {
        $sql = "SELECT vp.id, vp.item_code, vp.title, vp.sku FROM vp_products AS vp INNER JOIN vp_vendor_products_mapping AS vvpm ON vp.id = vvpm.product_id WHERE vvpm.vendor_id = ? AND vp.is_active = 1 ORDER BY vp.title ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
    public function getProductByCode($item_code) {
        $item_code = $this->conn->real_escape_string($item_code);
        $sql = "SELECT id, item_code, title, sku, `image` FROM vp_products WHERE item_code = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getmappingProductsByVendorId($vendor_id) {
        $sql = "SELECT p.id, p.item_code, p.title, p.sku, p.image, vpm.item_code AS mapped_item_code FROM vp_products AS p INNER JOIN vp_vendor_products_mapping AS vpm ON p.id = vpm.product_id WHERE vpm.vendor_id = ? AND p.is_active = 1 ORDER BY p.title ASC";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $vendor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        return $products;
    }
    public function saveVendorProductsMapping($vendor_id, $product_ids, $item_codes = []) {
        // Delete existing mappings
        $deleteSql = "DELETE FROM vp_vendor_products_mapping WHERE vendor_id = ?";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $vendor_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new mappings
        $insertSql = "INSERT INTO vp_vendor_products_mapping (vendor_id, product_id, item_code) VALUES (?, ?, ?)";
        $insertStmt = $this->conn->prepare($insertSql);
        $i=0;
        foreach ($product_ids as $product_id) {
            $insertStmt->bind_param('iis', $vendor_id, $product_id, $item_codes[$i]);
            $insertStmt->execute();
            $i++;
        }
        $insertStmt->close();

        return ['success' => true, 'message' => 'Vendor products mapping updated successfully.'];
    }
    public function updateVendorCode() {
        $sql = "SELECT id, vendor_code FROM vp_vendors WHERE vendor_code IS NULL OR vendor_code = ''";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            if(empty($row['vendor_code'])) {
                $newCode = generateVendorCode($this->conn);
                $sql = "UPDATE vp_vendors SET vendor_code = ? WHERE id = ?";
                $stmt = $this->conn->prepare($sql);
                $vendorId = $row['id'];
                $stmt->bind_param('si', $newCode, $vendorId);
                $stmt->execute();                
            }
        }
        return ['success' => true, 'message' => 'Vendor codes updated successfully.'];
    }

    /**
     * Normalize vendorlist API JSON into rows: [['remote_id' => string, 'name' => string], ...]
     */
    public function normalizeVendorlistApiResponse($decoded): array {
        $out = [];
        if (!is_array($decoded)) {
            return $out;
        }
        $list = null;
        if (isset($decoded['data']) && is_array($decoded['data'])) {
            $list = $decoded['data'];
        } elseif (isset($decoded['vendors']) && is_array($decoded['vendors'])) {
            $list = $decoded['vendors'];
        } elseif (isset($decoded['items']) && is_array($decoded['items'])) {
            $list = $decoded['items'];
        } elseif ($decoded !== [] && array_keys($decoded) === range(0, count($decoded) - 1)) {
            $list = $decoded;
        }
        if ($list === null) {
            return $out;
        }
        foreach ($list as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rid = $row['id'] ?? $row['vendor_id'] ?? $row['vendorId'] ?? null;
            $name = $row['name'] ?? $row['vendor_name'] ?? $row['vendorName'] ?? '';
            if ($rid === null || trim((string) $name) === '') {
                continue;
            }
            $out[] = [
                'remote_id' => substr((string) $rid, 0, 30),
                'name' => substr(trim((string) $name), 0, 150),
            ];
        }
        return $out;
    }

    /**
     * HTTP GET vendor-api/products/vendorlist for a groupname; returns normalized rows or error detail.
     */
    public function fetchVendorlistFromApi(string $groupname, string $apiBaseUrl = 'https://www.exoticindia.com'): array {
        $groupname = trim($groupname);
        if ($groupname === '') {
            return ['success' => false, 'message' => 'groupname is required.', 'rows' => []];
        }
        $url = rtrim($apiBaseUrl, '/') . '/vendor-api/products/vendorlist?groupname=' . rawurlencode($groupname);
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: VendorPortalVendorlistImport/1.0',
        ];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);
        if ($response === false) {
            return ['success' => false, 'message' => 'cURL error: ' . $cerr, 'rows' => [], 'http_code' => $httpCode];
        }
        if ($httpCode !== 200 && $httpCode !== 201) {
            return ['success' => false, 'message' => 'HTTP ' . $httpCode, 'rows' => [], 'raw' => $response];
        }
        $decoded = json_decode($response, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg(), 'rows' => []];
        }
        $rows = $this->normalizeVendorlistApiResponse($decoded);
        return ['success' => true, 'message' => 'OK', 'rows' => $rows];
    }

    /**
     * Insert or update one vendor keyed by vendor_code + groupname; always sets user_id = VENDORLIST_IMPORT_USER_ID (1000).
     */
    public function upsertVendorFromVendorlist(string $remoteVendorId, string $vendorName, string $groupname): array {
        $remoteVendorId = substr(trim($remoteVendorId), 0, 30);
        $vendorName = substr(trim($vendorName), 0, 150);
        $groupname = substr(trim($groupname), 0, 100);
        if ($remoteVendorId === '' || $vendorName === '') {
            return ['action' => 'skip', 'reason' => 'empty id or name'];
        }
        $uid = self::VENDORLIST_IMPORT_USER_ID;

        $find = $this->conn->prepare(
            'SELECT id FROM vp_vendors WHERE vendor_code = ? AND (groupname <=> ?)'
        );
        $find->bind_param('ss', $remoteVendorId, $groupname);
        $find->execute();
        $res = $find->get_result();
        $existing = $res ? $res->fetch_assoc() : null;
        $find->close();

        if ($existing) {
            $upd = $this->conn->prepare(
                'UPDATE vp_vendors SET vendor_name = ?, user_id = ?, groupname = ?, is_active = \'active\' WHERE id = ?'
            );
            $vid = (int) $existing['id'];
            $upd->bind_param('sisi', $vendorName, $uid, $groupname, $vid);
            if ($upd->execute()) {
                return ['action' => 'updated', 'id' => $vid];
            }
            return ['action' => 'error', 'message' => $upd->error];
        }

        $ins = $this->conn->prepare(
            'INSERT INTO vp_vendors (vendor_code, vendor_name, groupname, user_id, is_active, country) VALUES (?, ?, ?, ?, \'active\', \'India\')'
        );
        $ins->bind_param('sssi', $remoteVendorId, $vendorName, $groupname, $uid);
        if ($ins->execute()) {
            return ['action' => 'inserted', 'id' => (int) $this->conn->insert_id];
        }
        return ['action' => 'error', 'message' => $ins->error];
    }

    /**
     * Fetch vendorlist for groupname and upsert all rows with user_id = 1000.
     */
    public function importVendorlistForGroup(string $groupname, bool $dryRun = true, string $apiBaseUrl = 'https://www.exoticindia.com'): array {
        $fetch = $this->fetchVendorlistFromApi($groupname, $apiBaseUrl);
        if (!$fetch['success']) {
            return ['success' => false, 'message' => $fetch['message'], 'inserted' => 0, 'updated' => 0, 'skipped' => 0];
        }
        $rows = $fetch['rows'];
        if ($dryRun) {
            return [
                'success' => true,
                'message' => 'Dry run — no database writes.',
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'total_api_rows' => count($rows),
                'dry_run' => true,
            ];
        }
        $inserted = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        foreach ($rows as $r) {
            $ret = $this->upsertVendorFromVendorlist($r['remote_id'], $r['name'], $groupname);
            if (($ret['action'] ?? '') === 'inserted') {
                $inserted++;
            } elseif (($ret['action'] ?? '') === 'updated') {
                $updated++;
            } elseif (($ret['action'] ?? '') === 'skip') {
                $skipped++;
            } else {
                $errors[] = $ret['message'] ?? json_encode($ret);
            }
        }
        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Done' : implode('; ', array_slice($errors, 0, 5)),
            'inserted' => $inserted,
            'updated' => $updated,
            'skipped' => $skipped,
            'total_api_rows' => count($rows),
            'dry_run' => $dryRun,
        ];
    }
}
?>