<?php
class vendor
{
    /** Stamped on vp_vendors rows created/updated from vendor-api/products/vendorlist import */
    public const VENDORLIST_IMPORT_USER_ID = 1000;

    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }
    public function getAllVendors()
    {
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

    /**
     * Active vendors that have a linked Exotic vendor_id (vp_vendors.vendor_id).
     */
    public function getActiveVendorsWithExoticVendorId()
    {
        $sql = "SELECT * FROM vp_vendors
            WHERE is_active = 1
              AND vendor_id IS NOT NULL AND TRIM(vendor_id) <> ''
            ORDER BY vendor_name ASC";
        $result = $this->conn->query($sql);
        $vendors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }

        return $vendors;
    }
    public function getVendorById($id)
    {
        $sql = "SELECT * FROM vp_vendors WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function getVendorByVendorId($vendorId)
    {
        $vendorId = trim((string) $vendorId);
        if ($vendorId === '') {
            return null;
        }
        $sql = "SELECT * FROM vp_vendors WHERE vendor_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $vendorId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getBankDetailsById($vendor_id)
    {
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
    public function saveBankDetails($data)
    {
        global $secretKey;
        if (isset($secretKey)) {
            $sql = "INSERT INTO vendor_bank_details (vendor_id, account_holder_name, account_number, ifsc_code, bank_name, branch_name, is_active) VALUES (?, AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), ?) ON DUPLICATE KEY UPDATE account_holder_name = VALUES(account_holder_name), account_number = VALUES(account_number), ifsc_code = VALUES(ifsc_code), bank_name = VALUES(bank_name), branch_name = VALUES(branch_name), is_active = VALUES(is_active)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'issssssssssi',
                $data['vendor_id'],
                $data['account_name'],
                $secretKey,
                $data['account_number'],
                $secretKey,
                $data['ifsc_code'],
                $secretKey,
                $data['bank_name'],
                $secretKey,
                $data['branch_name'],
                $secretKey,
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
    public function updateBankDetails($data)
    {
        global $secretKey;
        if (isset($secretKey)) {
            $sql = "UPDATE vendor_bank_details SET account_holder_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), account_number = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), ifsc_code = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), bank_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), branch_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))), is_active = ? WHERE vendor_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'ssssssssssii',
                $data['account_name'],
                $secretKey,
                $data['account_number'],
                $secretKey,
                $data['ifsc_code'],
                $secretKey,
                $data['bank_name'],
                $secretKey,
                $data['branch_name'],
                $secretKey,
                $data['bdStatus'],
                $data["vendor_id"]
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

    /**
     * @return array{success:false,message:string}|null null when no duplicate conflict
     */
    private function validateVendorUniqueness(
        ?string $vendorName,
        ?string $phone,
        ?string $email,
        ?string $gst,
        ?string $pan,
        ?int $excludeId = null
    ): ?array {
        $excludeId = ($excludeId !== null && $excludeId > 0) ? $excludeId : null;

        if ($vendorName !== null && trim($vendorName) !== '' && $this->vendorNameExists(trim($vendorName), $excludeId)) {
            return ['success' => false, 'message' => 'Vendor name already exists. Please use a different vendor name.'];
        }
        if ($phone !== null && trim($phone) !== '' && $this->vendorFieldExists('vendor_phone', trim($phone), $excludeId)) {
            return ['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.'];
        }
        if ($email !== null && trim($email) !== '' && $this->vendorFieldExists('vendor_email', trim($email), $excludeId)) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email.'];
        }
        if ($gst !== null && trim($gst) !== '' && $this->vendorFieldExists('gst_number', trim($gst), $excludeId)) {
            return ['success' => false, 'message' => 'GST number already exists. Please use a different GST number.'];
        }
        if ($pan !== null && trim($pan) !== '' && $this->vendorFieldExists('pan_number', trim($pan), $excludeId)) {
            return ['success' => false, 'message' => 'PAN number already exists. Please use a different PAN number.'];
        }

        return null;
    }

    private function vendorNameExists(string $name, ?int $excludeId = null): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $this->conn->prepare('SELECT id FROM vp_vendors WHERE LOWER(TRIM(vendor_name)) = LOWER(TRIM(?)) AND id != ? LIMIT 1');
            $stmt->bind_param('si', $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare('SELECT id FROM vp_vendors WHERE LOWER(TRIM(vendor_name)) = LOWER(TRIM(?)) LIMIT 1');
            $stmt->bind_param('s', $name);
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function vendorFieldExists(string $column, string $value, ?int $excludeId = null): bool
    {
        $allowed = ['vendor_phone', 'vendor_email', 'gst_number', 'pan_number'];
        if (!in_array($column, $allowed, true)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if ($column === 'vendor_email') {
            $sql = "SELECT id FROM vp_vendors WHERE LOWER(TRIM(vendor_email)) = LOWER(TRIM(?)) AND TRIM(COALESCE(vendor_email, '')) <> ''";
        } else {
            $sql = 'SELECT id FROM vp_vendors WHERE ' . $column . ' = ?';
        }

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $value, $excludeId);
        } else {
            $sql .= ' LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $value);
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function normalizeVendorEmail(?string $email): string
    {
        return strtolower(trim((string) $email));
    }

    public function addVendor($data)
    {
        $groupnameValue = '';
        if (isset($data['groupname'])) {
            if (is_array($data['groupname'])) {
                $groups = array_values(array_unique(array_filter(array_map('trim', $data['groupname']), static function ($v) {
                    return $v !== '';
                })));
                $groupnameValue = implode(',', $groups);
            } else {
                $groupnameValue = trim((string)$data['groupname']);
            }
        }
        $duplicate = $this->validateVendorUniqueness(
            isset($data['addVendorName']) ? (string) $data['addVendorName'] : null,
            isset($data['addPhone']) ? (string) $data['addPhone'] : null,
            isset($data['addEmail']) ? (string) $data['addEmail'] : null,
            isset($data['addGstNumber']) ? (string) $data['addGstNumber'] : null,
            isset($data['addPanNumber']) ? (string) $data['addPanNumber'] : null
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $vendorCode = generateVendorCode($this->conn);
        $sql = "INSERT INTO vp_vendors (vendor_code, vendor_name, contact_name, vendor_email, country_code, vendor_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, rating, notes, user_id, team_id, agent_id, is_active, groupname) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'ssssssssssssssssiiiss',
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
            $data['addStatus'],
            $groupnameValue
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

            return ['success' => true, 'message' => 'Vendor added successfully.', 'category_status' => $cat_status, 'team_status' => $tm_status, 'inserted_id' => $vendor_id];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateVendor($id, $data)
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid vendor id.'];
        }

        $existingVendor = $this->getVendorById($id);
        $editEmail = isset($data['editEmail']) ? (string) $data['editEmail'] : null;
        if ($existingVendor && $editEmail !== null
            && $this->normalizeVendorEmail($existingVendor['vendor_email'] ?? '') === $this->normalizeVendorEmail($editEmail)) {
            $editEmail = null;
        }

        $duplicate = $this->validateVendorUniqueness(
            isset($data['editVendorName']) ? (string) $data['editVendorName'] : null,
            isset($data['editPhone']) ? (string) $data['editPhone'] : null,
            $editEmail,
            isset($data['editGstNumber']) ? (string) $data['editGstNumber'] : null,
            isset($data['editPanNumber']) ? (string) $data['editPanNumber'] : null,
            $id
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $groupnameValue = '';
        if (isset($data['editGroupname'])) {
            if (is_array($data['editGroupname'])) {
                $groups = array_values(array_unique(array_filter(array_map('trim', $data['editGroupname']), static function ($v) {
                    return $v !== '';
                })));
                $groupnameValue = implode(',', $groups);
            } else {
                $groupnameValue = trim((string)$data['editGroupname']);
            }
        }
        $sql = "UPDATE vp_vendors SET vendor_name = ?, contact_name = ?, vendor_email = ?, country_code = ?, vendor_phone = ?, alt_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, rating = ?, notes = ?, user_id = ?, team_id = ?, agent_id = ?, is_active = ?, groupname = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(
            'sssssssssssssssiiissi',
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
            $groupnameValue,
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
            return ['success' => true, 'message' => 'Vendor updated successfully.', 'cat_status' => $cat_status, 'team_status' => $tm_status];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function updateVendorRemoteId($vendorId, $remoteVendorId)
    {
        $sql = "UPDATE vp_vendors SET vendor_id = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $remoteVendorId, $vendorId);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Vendor remote ID updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error
        ];
    }
    public function deleteVendor($id)
    {
        $guard = $this->canDeleteVendor((int)$id);
        if (empty($guard['success'])) {
            return $guard;
        }

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

    public function canDeleteVendor(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid vendor ID.'];
        }

        $vendor = $this->getVendorById($id);
        if (!$vendor) {
            return ['success' => false, 'message' => 'Vendor not found.'];
        }

        $remoteVendorId = trim((string) ($vendor['vendor_id'] ?? ''));
        $remoteVendorInt = (int) preg_replace('/\D/', '', $remoteVendorId);

        // vp_inbound.vendor_code stores Exotic vendor_id, not vp_vendors.id
        if ($remoteVendorInt > 0) {
            $checkInboundStmt = $this->conn->prepare('SELECT id FROM vp_inbound WHERE vendor_code = ? LIMIT 1');
            if ($checkInboundStmt) {
                $checkInboundStmt->bind_param('i', $remoteVendorInt);
                $checkInboundStmt->execute();
                $checkInboundStmt->store_result();
                if ($checkInboundStmt->num_rows > 0) {
                    $checkInboundStmt->close();
                    return ['success' => false, 'message' => 'Vendor cannot be deleted because it is mapped in inbound records.'];
                }
                $checkInboundStmt->close();
            }
        }

        if ($remoteVendorInt > 0) {
            $checkProductsStmt = $this->conn->prepare(
                'SELECT id FROM vp_products WHERE vendor_id IN (?, ?) LIMIT 1'
            );
            if (!$checkProductsStmt) {
                return ['success' => false, 'message' => 'Unable to verify product mappings for this vendor.'];
            }
            $checkProductsStmt->bind_param('ii', $id, $remoteVendorInt);
        } else {
            $checkProductsStmt = $this->conn->prepare(
                'SELECT id FROM vp_products WHERE vendor_id = ? LIMIT 1'
            );
            if (!$checkProductsStmt) {
                return ['success' => false, 'message' => 'Unable to verify product mappings for this vendor.'];
            }
            $checkProductsStmt->bind_param('i', $id);
        }

        $checkProductsStmt->execute();
        $checkProductsStmt->store_result();
        if ($checkProductsStmt->num_rows > 0) {
            $checkProductsStmt->close();
            return ['success' => false, 'message' => 'Vendor cannot be deleted because it is mapped in products.'];
        }
        $checkProductsStmt->close();

        return ['success' => true, 'message' => 'Vendor can be deleted.'];
    }
    public function getAllVendorsListing($page = 1, $limit = 10, $search = '', $status_filter = '', $category_filter = '', $team_filter = '', $groupname_filter = '')
    {
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
            $where = "WHERE (vp.vendor_id LIKE '%$search%' OR vp.groupname LIKE '%$search%' OR vp.vendor_name LIKE '%$search%' OR vp.contact_name LIKE '%$search%' OR vp.vendor_email LIKE '%$search%' OR vp.vendor_phone LIKE '%$search%' OR vp.city LIKE '%$search%' OR vp.state LIKE '%$search%') AND vp.is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->conn->real_escape_string($search);
                $where = "WHERE vp.vendor_id LIKE '%$search%' OR vp.groupname LIKE '%$search%' OR vp.vendor_name LIKE '%$search%' OR vp.contact_name LIKE '%$search%' OR vp.vendor_email LIKE '%$search%' OR vp.vendor_phone LIKE '%$search%' OR vp.city LIKE '%$search%' OR vp.state LIKE '%$search%'";
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

        if (!empty($groupname_filter)) {
            $groupname_filter = $this->conn->real_escape_string($groupname_filter);
            if ($where === '') {
                $where = "WHERE vp.groupname LIKE '%$groupname_filter%'";
            } else {
                $where .= " AND vp.groupname LIKE '%$groupname_filter%'";
            }
        }

        // total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_vendors AS vp LEFT JOIN vp_users AS vu ON vp.agent_id = vu.id AND vu.is_deleted = 0 LEFT JOIN vendors_category AS vc ON vp.id = vc.vendor_id LEFT JOIN vp_vendor_team_mapping AS vvtm ON vp.id = vvtm.vendor_id $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];
        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT vp.*, vu.name AS agent_name, GROUP_CONCAT(DISTINCT vc.category_id) AS categories, GROUP_CONCAT(DISTINCT vvtm.team_id) AS teams FROM vp_vendors AS vp LEFT JOIN vp_users AS vu ON vp.agent_id = vu.id AND vu.is_deleted = 0 LEFT JOIN vendors_category AS vc ON vp.id = vc.vendor_id LEFT JOIN vp_vendor_team_mapping AS vvtm ON vp.id = vvtm.vendor_id $where GROUP BY vp.id ORDER BY CASE WHEN vp.vendor_id IS NULL OR TRIM(vp.vendor_id) = '' THEN 1 ELSE 0 END ASC, vp.vendor_id DESC, vp.id DESC LIMIT $limit OFFSET $offset";
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
    public function listCategory()
    {
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
    public function addVendorCategory($vendor_id, $category)
    {
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
    public function addVendorTeams($vendor_id, $category)
    {
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
    public function getVendorCategories($vendor_id)
    {
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
    public function getVendorTeams($v_id)
    {
        $sql = "SELECT team_id FROM vp_vendor_team_mapping WHERE vendor_id = " . $v_id;
        $result = $this->conn->query($sql);
        $vTeamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vTeamMembers[] = $row["team_id"];
            }
        }
        return $vTeamMembers;
    }
    public function getTeamMembers($team_id)
    {
        $sql = "SELECT vu.id as user_id, vu.name, vt.id as team_id, vt.team_name FROM vp_users AS vu INNER JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id INNER JOIN vp_teams AS vt ON vutm.team_id = vt.id WHERE vu.is_active = 1 AND vu.is_deleted = 0 AND vutm.team_id IN (" . $team_id . ") ORDER BY vt.team_name, vu.id ASC";
        $result = $this->conn->query($sql);
        $teamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
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
    public function searchVendors($term)
    {
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
    public function searchRemoteVendors($term)
    {
        $term = $this->conn->real_escape_string($term);
        $sql = "SELECT * FROM vp_vendors WHERE vendor_id > 0 AND vendor_name LIKE '%$term%' LIMIT 10";
        $result = $this->conn->query($sql);
        $vendors = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $vendors[] = $row;
            }
        }
        return $vendors;
    }
    public function checkVendorName($val, $excludeId = null)
    {
        $excludeId = ($excludeId !== null && (int) $excludeId > 0) ? (int) $excludeId : null;
        return ['exists' => $this->vendorNameExists(trim((string) $val), $excludeId)];
    }
    public function checkEmail($email, $excludeId = null)
    {
        $excludeId = ($excludeId !== null && (int) $excludeId > 0) ? (int) $excludeId : null;
        return ['exists' => $this->vendorFieldExists('vendor_email', trim((string) $email), $excludeId)];
    }
    public function checkPhoneNumber($val, $excludeId = null)
    {
        $excludeId = ($excludeId !== null && (int) $excludeId > 0) ? (int) $excludeId : null;
        return ['exists' => $this->vendorFieldExists('vendor_phone', trim((string) $val), $excludeId)];
    }
    public function getProductsByVendorId($vendor_id)
    {
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
    public function getProductByCode($item_code)
    {
        $item_code = $this->conn->real_escape_string($item_code);
        $sql = "SELECT id, item_code, title, sku, `image` FROM vp_products WHERE item_code = ? AND is_active = 1";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $item_code);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getmappingProductsByVendorId($vendor_id)
    {
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
    public function saveVendorProductsMapping($vendor_id, $product_ids, $item_codes = [])
    {
        // Delete existing mappings
        $deleteSql = "DELETE FROM vp_vendor_products_mapping WHERE vendor_id = ?";
        $deleteStmt = $this->conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $vendor_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new mappings
        $insertSql = "INSERT INTO vp_vendor_products_mapping (vendor_id, product_id, item_code) VALUES (?, ?, ?)";
        $insertStmt = $this->conn->prepare($insertSql);
        $i = 0;
        foreach ($product_ids as $product_id) {
            $insertStmt->bind_param('iis', $vendor_id, $product_id, $item_codes[$i]);
            $insertStmt->execute();
            $i++;
        }
        $insertStmt->close();

        return ['success' => true, 'message' => 'Vendor products mapping updated successfully.'];
    }
    public function updateVendorCode()
    {
        $sql = "SELECT id, vendor_code FROM vp_vendors WHERE vendor_code IS NULL OR vendor_code = ''";
        $stmt = $this->conn->prepare($sql);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (empty($row['vendor_code'])) {
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
    public function normalizeVendorlistApiResponse($decoded): array
    {
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
     * Vendorlist API — many backends return 400 on GET and expect POST JSON instead.
     * Tries several request shapes and returns the first HTTP 200/201 with parseable JSON.
     */
    public function fetchVendorlistFromApi(string $groupname, string $apiBaseUrl = 'https://www.exoticindia.com'): array
    {
        $groupname = trim($groupname);
        if ($groupname === '') {
            return ['success' => false, 'message' => 'groupname is required.', 'rows' => []];
        }

        $base = rtrim($apiBaseUrl, '/') . '/vendor-api/products/vendorlist';

        $headerJson = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: VendorPortalVendorlistImport/1.1',
        ];
        $headerForm = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: VendorPortalVendorlistImport/1.1',
        ];
        $headerGet = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
            'User-Agent: VendorPortalVendorlistImport/1.1',
        ];

        $attempts = [
            ['label' => 'GET ?groupname=', 'method' => 'GET', 'url' => $base . '?groupname=' . rawurlencode($groupname), 'headers' => $headerGet, 'body' => null],
            ['label' => 'GET ?groupName=', 'method' => 'GET', 'url' => $base . '?groupName=' . rawurlencode($groupname), 'headers' => $headerGet, 'body' => null],
            ['label' => 'POST JSON {groupname}', 'method' => 'POST', 'url' => $base, 'headers' => $headerJson, 'body' => json_encode(['groupname' => $groupname], JSON_UNESCAPED_UNICODE)],
            ['label' => 'POST JSON {groupName}', 'method' => 'POST', 'url' => $base, 'headers' => $headerJson, 'body' => json_encode(['groupName' => $groupname], JSON_UNESCAPED_UNICODE)],
            ['label' => 'POST form groupname', 'method' => 'POST', 'url' => $base, 'headers' => $headerForm, 'body' => http_build_query(['groupname' => $groupname])],
            ['label' => 'POST form groupName', 'method' => 'POST', 'url' => $base, 'headers' => $headerForm, 'body' => http_build_query(['groupName' => $groupname])],
        ];

        $lastHttp = 0;
        $lastRaw = '';
        $lastUrl = '';
        $lastLabel = '';

        foreach ($attempts as $att) {
            $ch = curl_init($att['url']);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => $att['headers'],
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_SSL_VERIFYPEER => false,
            ];
            if ($att['method'] === 'POST') {
                $opts[CURLOPT_POST] = true;
                $opts[CURLOPT_POSTFIELDS] = $att['body'];
            }
            curl_setopt_array($ch, $opts);
            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            $lastHttp = $httpCode;
            $lastUrl = $att['url'];
            $lastLabel = $att['label'];

            if ($response === false) {
                $lastRaw = $cerr !== '' ? $cerr : '(empty curl response)';
                continue;
            }

            $lastRaw = $response;
            if ($httpCode !== 200 && $httpCode !== 201) {
                continue;
            }

            $decoded = json_decode($response, true);
            if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
                continue;
            }
            $rows = $this->normalizeVendorlistApiResponse($decoded);
            return [
                'success' => true,
                'message' => 'OK',
                'rows' => $rows,
                'attempt_used' => $lastLabel,
                'http_code' => $httpCode,
            ];
        }

        $rawSnippet = $lastRaw;
        if (strlen($rawSnippet) > 4000) {
            $rawSnippet = substr($rawSnippet, 0, 4000) . '…';
        }

        return [
            'success' => false,
            'message' => 'All request variants failed. Last: HTTP ' . $lastHttp . ' (' . $lastLabel . ')',
            'rows' => [],
            'http_code' => $lastHttp,
            'request_url' => $lastUrl,
            'attempt_used' => $lastLabel,
            'raw' => $rawSnippet,
            'hint' => 'If HTTP 400 persists, confirm with API owners: exact method (GET vs POST), parameter name (groupname vs groupName), and whether group slug must match catalog (not display name). Response body is in raw.',
        ];
    }

    /**
     * Insert or update one vendor keyed by vendor_code + groupname; always sets user_id = VENDORLIST_IMPORT_USER_ID (1000).
     */
    public function upsertVendorFromVendorlist(string $remoteVendorId, string $vendorName, string $groupname): array
    {
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
    public function importVendorlistForGroup(string $groupname, bool $dryRun = true, string $apiBaseUrl = 'https://www.exoticindia.com'): array
    {
        $fetch = $this->fetchVendorlistFromApi($groupname, $apiBaseUrl);
        if (!$fetch['success']) {
            return [
                'success' => false,
                'message' => $fetch['message'],
                'inserted' => 0,
                'updated' => 0,
                'skipped' => 0,
                'http_code' => $fetch['http_code'] ?? null,
                'request_url' => $fetch['request_url'] ?? null,
                'attempt_used' => $fetch['attempt_used'] ?? null,
                'raw' => $fetch['raw'] ?? null,
                'hint' => $fetch['hint'] ?? null,
            ];
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
                'attempt_used' => $fetch['attempt_used'] ?? null,
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
            'attempt_used' => $fetch['attempt_used'] ?? null,
        ];
    }
    public function saveVendorsFromAPI($vendors)
    {
        if (!is_array($vendors) || empty($vendors)) {
            return ['success' => false, 'message' => 'Invalid vendors data'];
        }

        $inserted = 0;
        $updated = 0;
        $errors = [];

        foreach ($vendors as $groupname => $vendorList) {
            if (!is_array($vendorList)) {
                continue;
            }

            foreach ($vendorList as $vendor) {
                $vendorId = $vendor['id'] ?? null;
                $vendorName = $vendor['name'] ?? null;

                if ($vendorId === null || $vendorName === null) {
                    continue;
                }

                $vendorId = substr(trim((string)$vendorId), 0, 30);
                $vendorName = substr(trim((string)$vendorName), 0, 150);
                $groupname = substr(trim((string)$groupname), 0, 100);

                // Compare by vendor_id (authoritative key from Admin API)
                $checkStmt = $this->conn->prepare('SELECT id, groupname FROM vp_vendors WHERE vendor_id = ? LIMIT 1');
                $checkStmt->bind_param('s', $vendorId);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                $existing = $result ? $result->fetch_assoc() : null;
                $checkStmt->close();

                if ($existing) {
                    $existingId = (int)$existing['id'];
                    $getGroupStmt = $this->conn->prepare('SELECT groupname FROM vp_vendors WHERE id = ?');
                    $getGroupStmt->bind_param('i', $existingId);
                    $getGroupStmt->execute();
                    $groupRes = $getGroupStmt->get_result();
                    $groupRow = $groupRes->fetch_assoc();
                    $getGroupStmt->close();

                    $existingGroupname = !empty($groupRow['groupname']) ? trim($groupRow['groupname']) : '';

                    // Append new groupname with comma separator if not already present
                    if (!empty($existingGroupname)) {
                        $groupnames = array_map('trim', explode(',', $existingGroupname));
                        if (!in_array($groupname, $groupnames)) {
                            $newGroupname = $existingGroupname . ',' . $groupname;
                        } else {
                            $newGroupname = $existingGroupname;
                        }
                    } else {
                        $newGroupname = $groupname;
                    }

                    // Update vendor_id and groupname if vendor_name exists
                    $updateStmt = $this->conn->prepare('UPDATE vp_vendors SET vendor_id = ?, groupname = ? WHERE id = ?');
                    $updateStmt->bind_param('sss', $vendorId, $newGroupname, $existingId);
                    if ($updateStmt->execute()) {
                        $updated++;
                    } else {
                        $errors[] = 'Update failed for ' . $vendorName . ': ' . $updateStmt->error;
                    }
                    $updateStmt->close();
                } else {
                    // Insert if vendor_id not found
                    $insertStmt = $this->conn->prepare('INSERT INTO vp_vendors (vendor_id, vendor_name, groupname, country, is_active) VALUES (?, ?, ?, \'India\', \'active\')');
                    $insertStmt->bind_param('sss', $vendorId, $vendorName, $groupname);
                    if ($insertStmt->execute()) {
                        $inserted++;
                    } else {
                        $errors[] = 'Insert failed for ' . $vendorName . ': ' . $insertStmt->error;
                    }
                    $insertStmt->close();
                }
            }
        }

        return [
            'success' => empty($errors),
            'message' => empty($errors) ? 'Done' : implode('; ', array_slice($errors, 0, 5)),
            'inserted' => $inserted,
            'updated' => $updated,
            'total' => $inserted + $updated,
            'error_count' => count($errors)
        ];
    }
    public function getVendorsByGroup($groupname)
    {
        $sql = "SELECT id, vendor_id, vendor_name 
                FROM vp_vendors 
                WHERE FIND_IN_SET(?, groupname) > 0
                  AND vendor_id IS NOT NULL AND TRIM(vendor_id) <> ''
                  AND (LOWER(TRIM(CAST(is_active AS CHAR))) = 'active' OR CAST(is_active AS CHAR) = '1')";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('s', $groupname);
        $stmt->execute();
        $result = $stmt->get_result();
        $vendors = [];
        while ($row = $result->fetch_assoc()) {
            $vendors[] = $row;
        }
        return $vendors;
    }
    public function getGroupnames($categoryIds)
    {
        //categoryIds is array, we need to fetch distinct parent_id for all categoryIds and then fetch category_name for those parent_id and return comma separated
        $sql = "SELECT DISTINCT parent_id FROM vp_vendor_category WHERE id IN (" . implode(',', array_fill(0, count($categoryIds), '?')) . ")";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $parentIds = [];
        while ($row = $result->fetch_assoc()) {
            $parentIds[] = $row['parent_id'];
        }

        if (empty($parentIds)) {
            return '';
        }

        // Now fetch category_name for those parent_ids
        $sql = "SELECT category_name FROM vp_vendor_category WHERE id IN (" . implode(',', array_fill(0, count($parentIds), '?')) . ")";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param(str_repeat('i', count($parentIds)), ...$parentIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $categoryNames = [];
        while ($row = $result->fetch_assoc()) {
            $categoryNames[] = $row['category_name'];
        }

        return implode(',', $categoryNames);
    }
}
