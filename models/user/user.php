<?php

// This file is part of the Vendor Portal project.
// It is used to manage user-related functionalities such as login, logout, and rendering user views.

class User
{
    private $db;
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Warehouse table referenced by vp_users.warehouse_id FK (test DB uses exotic_address_old).
     */
    private function userWarehouseTable(): string
    {
        static $table = null;
        if ($table !== null) {
            return $table;
        }

        $result = $this->db->query("SHOW TABLES LIKE 'exotic_address_old'");
        $table = ($result && $result->num_rows > 0) ? 'exotic_address_old' : 'exotic_address';

        return $table;
    }

    /**
     * @return array{ok: bool, id: ?int, message?: string}
     */
    private function normalizeWarehouseId($warehouseId): array
    {
        $requestedId = (int) ($warehouseId ?? 0);
        if ($requestedId <= 0) {
            return ['ok' => true, 'id' => null];
        }

        $table = $this->userWarehouseTable();
        $sql = "SELECT id FROM {$table} WHERE id = ? AND is_active = 1 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [
                'ok' => false,
                'id' => null,
                'message' => 'Could not validate warehouse selection.',
            ];
        }

        $stmt->bind_param('i', $requestedId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        if (!$row) {
            return [
                'ok' => false,
                'id' => null,
                'message' => 'Selected warehouse is invalid. Please choose a warehouse from the list.',
            ];
        }

        return ['ok' => true, 'id' => $requestedId];
    }

    /** Bind value for NULLIF(?, 0) warehouse_id columns. */
    private function warehouseBindValue(?int $warehouseId): int
    {
        return ($warehouseId === null || $warehouseId <= 0) ? 0 : $warehouseId;
    }

    public function login($login, $password)
    {
        $sql = "SELECT * FROM vp_users WHERE (email = ? OR phone = ?) AND is_deleted = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            //if (password_verify($password, $user['password'])) {
            @session_start();
            $_SESSION['user'] = $user;
            $_SESSION['user_id'] = (int)($user['id'] ?? 0);
            $_SESSION['warehouse_id'] = $user['warehouse_id'];
            assignAPIToken($user["id"]); // Insert Token for Chat
            return true;
            //}
        }

        return false;
    }

    /**
     * Validate a one-time login OTP (vp_users.remember_token), valid for 10 minutes.
     */
    public function loginWithOtp($login, $otp)
    {
        $login = trim($login);
        $otp = trim((string) $otp);
        if ($login === '' || $otp === '') {
            return false;
        }

        $sql = "SELECT * FROM vp_users
            WHERE (email = ? OR phone = ?)
              AND remember_token = ?
              AND remember_token IS NOT NULL
              AND remember_token != ''
              AND is_deleted = 0
              AND updated_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE)
            LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $login, $login, $otp);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            return false;
        }

        $user = $result->fetch_assoc();
        $this->saveResetToken((int) ($user['id'] ?? 0), null);

        @session_start();
        $_SESSION['user'] = $user;
        $_SESSION['user_id'] = (int) ($user['id'] ?? 0);
        $_SESSION['warehouse_id'] = $user['warehouse_id'];
        assignAPIToken($user['id']);

        return true;
    }
    public function logout()
    {
        session_start();
        session_destroy();
    }
    public function isLoggedIn()
    {
        //session_start();
        return isset($_SESSION['user']);
    }
    public function getUser()
    {
        //session_start();
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }
    public function getUserId()
    {
        //session_start();
        return isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    }
    public function findByLogin($login)
    {
        $sql = "SELECT * FROM vp_users WHERE (email = ? OR phone = ?) AND is_deleted = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getUserById_bk($id)
    {
        $sql = "SELECT * FROM vp_users WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getUserById($id)
    {
        $warehouseTable = $this->userWarehouseTable();
        $sql = "SELECT 
                u.*, 
                ea.address_title AS warehouse_name
            FROM vp_users u
            LEFT JOIN {$warehouseTable} ea 
                ON u.warehouse_id = ea.id
            WHERE u.id = ? AND u.is_deleted = 0
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
    public function updatePassword($userId, $newPassword)
    {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE vp_users SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    public function saveResetToken($id, $token)
    {
        $sql = "UPDATE vp_users SET remember_token = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $token, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    public function verifyResetToken($login, $token)
    {
        $sql = "SELECT * FROM vp_users WHERE (email = ? OR phone = ?) AND remember_token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $login, $login, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    public function insert($data)
    {
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $role = (int) ($data['role'] ?? 0);
        $isActive = (int) ($data['is_active'] ?? 1);
        $warehouseCheck = $this->normalizeWarehouseId($data['warehouse_id'] ?? 0);
        if (!$warehouseCheck['ok']) {
            return ['success' => false, 'message' => $warehouseCheck['message'] ?? 'Invalid warehouse selected.'];
        }
        $warehouseBind = $this->warehouseBindValue($warehouseCheck['id']);

        // Check if email already exists
        $checkSql = "SELECT id FROM vp_users WHERE email = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('s', $email);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email address.'];
        }
        $checkStmt->close();

        if ($password === '') {
            return ['success' => false, 'message' => 'Password is required for new users.'];
        }

        $sql = "INSERT INTO vp_users (name, email, phone, password, role_id, warehouse_id, is_active) VALUES (?, ?, ?, ?, ?, NULLIF(?, 0), ?)";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Insert prepare failed: ' . $this->db->error];
        }
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt->bind_param('ssssiii', $name, $email, $phone, $hashedPassword, $role, $warehouseBind, $isActive);
        if ($stmt->execute()) {
            $user_id = $this->db->insert_id;
            if (!empty($data['team']) && is_array($data['team'])) {
                $tm_status = $this->addUserTeams($user_id, $data['team']);
                if (empty($tm_status['success'])) {
                    return $tm_status;
                }
            }
            return ['success' => true, 'message' => 'User added successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function update($id, $data)
    {
        $id = (int) $id;
        $name = trim((string) ($data['name'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $role = (int) ($data['role'] ?? 0);
        $isActive = (int) ($data['is_active'] ?? 0);
        $warehouseCheck = $this->normalizeWarehouseId($data['warehouse_id'] ?? 0);
        if (!$warehouseCheck['ok']) {
            return ['success' => false, 'message' => $warehouseCheck['message'] ?? 'Invalid warehouse selected.'];
        }
        $warehouseBind = $this->warehouseBindValue($warehouseCheck['id']);
        $password = (string) ($data['password'] ?? '');

        // Check if email already exists for another user
        $checkSql = "SELECT id FROM vp_users WHERE email = ? AND id != ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('si', $email, $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email address.'];
        }
        $checkStmt->close();

        if ($password !== '') {
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, password = ?, role_id = ?, is_active = ?, warehouse_id = NULLIF(?, 0) WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Update prepare failed: ' . $this->db->error];
            }
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param('ssssiiii', $name, $email, $phone, $hashedPassword, $role, $isActive, $warehouseBind, $id);
        } else {
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, role_id = ?, is_active = ?, warehouse_id = NULLIF(?, 0) WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                return ['success' => false, 'message' => 'Update prepare failed: ' . $this->db->error];
            }
            $stmt->bind_param('sssiiii', $name, $email, $phone, $role, $isActive, $warehouseBind, $id);
        }
        if ($stmt->execute()) {
            if (array_key_exists('team', $data)) {
                $tm_status = $this->addUserTeams($id, is_array($data['team']) ? $data['team'] : [$data['team']]);
                if (empty($tm_status['success'])) {
                    return $tm_status;
                }
            }
            return ['success' => true, 'message' => 'User updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function addUserTeams($user_id, $teamIds)
    {
        $userId = (int) $user_id;
        if ($userId <= 0) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if (!is_array($teamIds)) {
            $teamIds = [$teamIds];
        }

        $teamIds = array_values(array_filter(array_map('intval', $teamIds), static function ($teamId) {
            return $teamId > 0;
        }));

        $deleteSql = "DELETE FROM vp_user_team_mapping WHERE user_id = ?";
        $deleteStmt = $this->db->prepare($deleteSql);
        if (!$deleteStmt) {
            return ['success' => false, 'message' => 'Team delete prepare failed: ' . $this->db->error];
        }
        $deleteStmt->bind_param('i', $userId);
        if (!$deleteStmt->execute()) {
            $error = $deleteStmt->error;
            $deleteStmt->close();
            return ['success' => false, 'message' => 'Team delete failed: ' . $error];
        }
        $deleteStmt->close();

        if ($teamIds === []) {
            return ['success' => true, 'message' => 'Teams updated successfully.'];
        }

        $insertSql = "INSERT INTO vp_user_team_mapping (user_id, team_id) VALUES (?, ?)";
        foreach ($teamIds as $teamId) {
            $insertStmt = $this->db->prepare($insertSql);
            if (!$insertStmt) {
                return ['success' => false, 'message' => 'Team insert prepare failed: ' . $this->db->error];
            }
            $insertStmt->bind_param('ii', $userId, $teamId);
            if (!$insertStmt->execute()) {
                $error = $insertStmt->error;
                $insertStmt->close();
                return ['success' => false, 'message' => 'Team insert failed: ' . $error];
            }
            $insertStmt->close();
        }

        return ['success' => true, 'message' => 'Teams updated successfully.'];
    }
    public function getUserTeams($u_id)
    {
        $sql = "SELECT team_id FROM vp_user_team_mapping WHERE user_id = " . $u_id;
        $result = $this->db->query($sql);
        $uTeamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $uTeamMembers[] = $row["team_id"];
            }
        }
        return $uTeamMembers;
    }
    public function updateUserPriofile($id, $data)
    {
        $id = (int) $id;
        $name = trim((string) ($data['name'] ?? ''));
        $phone = trim((string) ($data['phone'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        if ($password !== '') {
            $sql = "UPDATE vp_users SET name = ?, phone = ?, password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param('sssi', $name, $phone, $hashedPassword, $id);
        } else {
            $sql = "UPDATE vp_users SET name = ?, phone = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $name, $phone, $id);
        }
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Your profile updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function delete($id)
    {
        // Soft delete: mark user as deleted and unassign all orders
        $sql = "UPDATE vp_users SET is_deleted = 1, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            // Unassign all dispatches created by this user
            // $this->unassignUserOrders($id);
            return ['success' => true, 'message' => 'User '. $id .' deleted successfully'];
        }
        return ['success' => false, 'error' => 'Failed: ' . $stmt->error];
    }

    public function unassignUserOrders($id)
    {
        // Unassign all vp_oprders Agent_id to null
        $sql = "UPDATE vp_orders SET agent_id = NULL WHERE agent_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        // Unassign all invoices created by this user
        $sql = "UPDATE vp_invoices SET created_by = NULL WHERE created_by = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

        // Unassign all dispatches created by this user
        $sql = "UPDATE vp_dispatch_details SET created_by = NULL WHERE created_by = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();

         // Unassign all purchase_orders to null
        $sql = "UPDATE purchase_orders SET user_id = NULL WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        
        // Unassign all vendor created by this user
        $sql = "UPDATE vp_vendors SET agent_id = NULL WHERE agent_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();


        return true;
    }
    public function getAll($search = '', $sort_by = 'id', $sort_order = 'asc', $limit = 20, $offset = 0)
    {
        $search = '%' . $this->db->real_escape_string($search) . '%';
        $sql = "SELECT * FROM vp_users WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?) AND is_deleted = 0 ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiii', $search, $search, $search, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function countAll($search = '')
    {
        $search = '%' . $this->db->real_escape_string($search) . '%';
        $sql = "SELECT COUNT(*) FROM vp_users WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?) AND is_deleted = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    }
    public function getAllUsers()
    {
        $sql = "SELECT id, name FROM vp_users WHERE is_active = 1 AND is_deleted = 0 ORDER BY name ASC";
        $result = $this->db->query($sql);
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[$row['id']] = $row['name'];
        }
        return $users;
    }

    /**
     * Active users assigned to a specific warehouse (vp_users.warehouse_id).
     *
     * @return array<int, string> Map of user id => display name
     */
    public function getActiveUsersByWarehouseId($warehouseId)
    {
        $warehouseId = (int)$warehouseId;
        if ($warehouseId <= 0) {
            return [];
        }
        $sql = "SELECT id, name FROM vp_users WHERE warehouse_id = ? AND is_active = 1 AND is_deleted = 0 ORDER BY name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[(int)$row['id']] = $row['name'];
        }
        $stmt->close();
        return $users;
    }

    public function userIsActiveAtWarehouse($userId, $warehouseId)
    {
        $userId = (int)$userId;
        $warehouseId = (int)$warehouseId;
        if ($userId <= 0 || $warehouseId <= 0) {
            return false;
        }
        $sql = "SELECT id FROM vp_users WHERE id = ? AND warehouse_id = ? AND is_active = 1 AND is_deleted = 0 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $userId, $warehouseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $ok = $result && $result->num_rows > 0;
        $stmt->close();
        return $ok;
    }
    public function getAllUsersListing($page = 1, $limit = 10, $search = '', $role_filter = '', $status_filter = '')
    {
        $warehouseTable = $this->userWarehouseTable();
        // sanitize
        $page = (int)$page;
        if ($page < 1) $page = 1;
        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;
        // calculate offset
        $offset = ($page - 1) * $limit;
        if (!empty($status_filter)) {
            if ($status_filter == 'active') {
                $status_filter = 1;
            } else if ($status_filter == 'inactive') {
                $status_filter = 0;
            }
        }
        // 🔹 Build search condition
        $where = " WHERE vu.is_deleted = 0";
        if (!empty($search) && is_numeric($role_filter) && is_numeric($status_filter)) {
            $search = $this->db->real_escape_string($search);
            $role_filter = $this->db->real_escape_string($role_filter);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where .= " AND (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.role_id = '$role_filter' AND vu.is_active = '$status_filter'";
        } elseif (!empty($search) && is_numeric($role_filter)) {
            $search = $this->db->real_escape_string($search);
            $role_filter = $this->db->real_escape_string($role_filter);
            $where .= " AND (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.role_id = '$role_filter'";
        } elseif (!empty($search) && is_numeric($status_filter)) {
            $search = $this->db->real_escape_string($search);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where .= " AND (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.is_active = '$status_filter'";
        } elseif (is_numeric($role_filter) && is_numeric($status_filter)) {
            $role_filter = $this->db->real_escape_string($role_filter);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where .= " AND vu.role_id = '$role_filter' AND vu.is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->db->real_escape_string($search);
                $where .= " AND vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%'";
            }

            if (is_numeric($role_filter)) {
                $search = $this->db->real_escape_string($role_filter);
                $where .= " AND vu.role_id = '$role_filter'";
            }

            if (is_numeric($status_filter)) {
                $search = $this->db->real_escape_string($status_filter);
                $where .= " AND vu.is_active = '$status_filter'";
            }
        }
        // total records
        // $sql = "SELECT COUNT(DISTINCT vu.id) AS total FROM vp_users AS vu LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id $where";
        $sql = "SELECT COUNT(DISTINCT vu.id) AS total 
FROM vp_users AS vu 
LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id 
LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id 
LEFT JOIN {$warehouseTable} AS ea ON vu.warehouse_id = ea.id
$where";
        $resultCount = $this->db->query($sql);
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'] ?? 0;

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        // $sql = "SELECT vu.*, GROUP_CONCAT(vt.team_name SEPARATOR ', ') AS team_names FROM vp_users AS vu LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id $where GROUP BY vu.id LIMIT $limit OFFSET $offset;";
       $sql = "SELECT 
        vu.*, 
        ea.address_title AS warehouse_name,
        GROUP_CONCAT(vt.team_name SEPARATOR ', ') AS team_names 
    FROM vp_users AS vu 
    LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id 
    LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id 
    LEFT JOIN {$warehouseTable} AS ea ON vu.warehouse_id = ea.id
    $where 
    GROUP BY vu.id 
    LIMIT $limit OFFSET $offset;";
        $result = $this->db->query($sql);

        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        // return structured data
        return [
            'users'        => $users,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
    }
    public function getAllRoles()
    {
        $sql = "SELECT id, role_name FROM vp_roles WHERE is_active = 1 ORDER BY role_name ASC";
        $result = $this->db->query($sql);
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        return $roles;
    }
    public function getAllTeams()
    {
        $sql = "SELECT id, team_name FROM vp_teams WHERE is_active = 1 ORDER BY team_name ASC";
        $result = $this->db->query($sql);
        $teams = [];
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
        return $teams;
    }
    public function getAllWarehouses()
    {
        $warehouseTable = $this->userWarehouseTable();
        $orderBy = ($warehouseTable === 'exotic_address')
            ? 'is_default DESC, address_title ASC'
            : 'address_title ASC';
        $sql = "SELECT id, address_title FROM {$warehouseTable} WHERE is_active = 1 ORDER BY {$orderBy}";
        $result = $this->db->query($sql);
        $warehouses = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $warehouses[] = $row;
            }
        }
        return $warehouses;
    }

    public function getWarehouseById($id)
    {
        $warehouseTable = $this->userWarehouseTable();
        $sql = "SELECT id, address_title 
            FROM {$warehouseTable} 
            WHERE id = ? AND is_active = 1 
            LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        return $result->fetch_assoc();
    }
}
