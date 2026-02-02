<?php 

// This file is part of the Vendor Portal project.
// It is used to manage user-related functionalities such as login, logout, and rendering user views.

class User {
    private $db;    
    public function __construct($db) {
        $this->db = $db;
    }
    public function login($login, $password) {
        $sql = "SELECT * FROM vp_users WHERE email = ? OR phone = ?";
        $stmt = $this->db->prepare($sql);   
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();  
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc(); 
            //if (password_verify($password, $user['password'])) {
                @session_start();
                $_SESSION['user'] = $user;
                assignAPIToken($user["id"]); // Insert Token for Chat
                return true;
            //}
        }   
  
        return false;
    }
    public function logout() {
        session_start();
        session_destroy();
    }
    public function isLoggedIn() {
        //session_start();
        return isset($_SESSION['user']);
    }
    public function getUser() {
        //session_start();
        return isset($_SESSION['user']) ? $_SESSION['user'] : null;
    }
    public function getUserId() {
        //session_start();
        return isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
    }
    public function findByLogin($login) {
        $sql = "SELECT * FROM vp_users WHERE email = ? OR phone = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $login, $login);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function getUserById($id) {
        $sql = "SELECT * FROM vp_users WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    public function updatePassword($userId, $newPassword) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $sql = "UPDATE vp_users SET password = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $hash, $userId);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    public function saveResetToken($id, $token) {
        $sql = "UPDATE vp_users SET remember_token = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('si', $token, $id);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }
    public function verifyResetToken($login, $token) {
        $sql = "SELECT * FROM vp_users WHERE (email = ? OR phone = ?) AND remember_token = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $login, $login, $token);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }
    public function insert($data) {
        // Check if email already exists
        $checkSql = "SELECT id FROM vp_users WHERE email = ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('s', $data['email']);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email address.'];
        }
        $checkStmt->close();

        $sql = "INSERT INTO vp_users (name, email, phone, password, role_id, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param('ssssii', $data['name'], $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['is_active']);
        if ($stmt->execute()) {
            $user_id = $this->db->insert_id;
            // Add vendor teams
            if (!empty($data['team']) && is_array($data['team'])) {
               $tm_status = $this->addUserTeams($user_id, $data['team']);
            }
            return ['success' => true, 'message' => 'User added successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Insert failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function update($id, $data) {
        // Check if email already exists for another user
        $checkSql = "SELECT id FROM vp_users WHERE email = ? AND id != ?";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->bind_param('si', $data['email'], $id);
        $checkStmt->execute();
        $checkStmt->store_result();
        if ($checkStmt->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email address.'];
        }
        $checkStmt->close();

        if (!empty($data['password'])) {
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, password = ?, role_id = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('ssssiii', $data['name'], $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['is_active'], $id);
        } else {
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, role_id = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssiii', $data['name'], $data['email'], $data['phone'], $data['role'], $data['is_active'], $id);
        }
        if ($stmt->execute()) {
            // Add vendor teams
            if (!empty($data['team']) && is_array($data['team'])) {
               $tm_status = $this->addUserTeams($id, $data['team']);
            }
            return ['success' => true, 'message' => 'User updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function addUserTeams($user_id, $teamIds){       
        if (empty($user_id)) {
            return ['success' => false, 'message' => 'User ID is required.'];
        }

        if (empty($teamIds) || !is_array($teamIds)) {
            return ['success' => false, 'message' => 'Teams is required and must be an array.'];
        }

        // Delete previous categories for this vendor
        $deleteSql = "DELETE FROM vp_user_team_mapping WHERE user_id = ?";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->bind_param('i', $user_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // Insert new categories
        $sql = "INSERT INTO vp_user_team_mapping (user_id, team_id) VALUES (?, ?)";
        $stmt = $this->db->prepare($sql);
        foreach ($teamIds as $tm_id) {
            $stmt->bind_param('ii', $user_id, $tm_id);
            $stmt->execute();
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Teams updated successfully.'];
    }
    public function getUserTeams($u_id) {
        $sql = "SELECT team_id FROM vp_user_team_mapping WHERE user_id = ".$u_id;
        $result = $this->db->query($sql);
        $uTeamMembers = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $uTeamMembers[] = $row["team_id"];
            }
        }
        return $uTeamMembers;
    }
    public function updateUserPriofile($id, $data) {

        if (!empty($data['password'])) {
            $sql = "UPDATE vp_users SET name = ?, phone = ?, password = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('sssi', $data['name'], $data['phone'], $hashedPassword, $id);
        } else {
            $sql = "UPDATE vp_users SET name = ?, phone = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('ssi', $data['name'], $data['phone'], $id);
        }
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Your profile updated successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Update failed: ' . $stmt->error . '. Please check your input and fill all required fields correctly.'
        ];
    }
    public function delete($id) {
        $sql = "DELETE FROM vp_users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User deleted successfully.'];
        }
        return ['success' => false, 'error' => 'Failed: ' . $stmt->error];
    }
    public function getAll($search = '', $sort_by = 'id', $sort_order = 'asc', $limit = 20, $offset = 0) {
        $search = '%' . $this->db->real_escape_string($search) . '%';
        $sql = "SELECT * FROM vp_users WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?) ORDER BY $sort_by $sort_order LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ssiii', $search, $search, $search, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    public function countAll($search = '') {
        $search = '%' . $this->db->real_escape_string($search) . '%';
        $sql = "SELECT COUNT(*) FROM vp_users WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sss', $search, $search, $search);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_row()[0];
    }
    public function getAllUsers() {
        $sql = "SELECT id, name FROM vp_users WHERE is_active = 1 ORDER BY name ASC";
        $result = $this->db->query($sql);
        $users = [];        
        while ($row = $result->fetch_assoc()) {
            $users[$row['id']] = $row['name'];
        }
        return $users;
    }
    public function getAllUsersListing($page = 1, $limit = 10, $search = '', $role_filter = '', $status_filter = '') {
        // sanitize
        $page = (int)$page;
        if ($page < 1) $page = 1;
        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;
        // calculate offset
        $offset = ($page - 1) * $limit;
        if (!empty($status_filter)) {
            if($status_filter == 'active') {
                $status_filter = 1;
            } else if($status_filter == 'inactive') {
                $status_filter = 0;
            }
        }
        // 🔹 Build search condition
        $where = "";
        if (!empty($search) && is_numeric($role_filter) && is_numeric($status_filter)) {
            $search = $this->db->real_escape_string($search);
            $role_filter = $this->db->real_escape_string($role_filter);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where = "WHERE (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.role_id = '$role_filter' AND vu.is_active = '$status_filter'";
        } elseif (!empty($search) && is_numeric($role_filter)) {
            $search = $this->db->real_escape_string($search);
            $role_filter = $this->db->real_escape_string($role_filter);
            $where = "WHERE (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.role_id = '$role_filter'";
        } elseif (!empty($search) && is_numeric($status_filter)) {
            $search = $this->db->real_escape_string($search);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where = "WHERE (vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%') AND vu.is_active = '$status_filter'";
        } elseif (is_numeric($role_filter) && is_numeric($status_filter)) {
            $role_filter = $this->db->real_escape_string($role_filter);
            $status_filter = $this->db->real_escape_string($status_filter);
            $where = "WHERE vu.role_id = '$role_filter' AND vu.is_active = '$status_filter'";
        } else {
            if (!empty($search)) {
                $search = $this->db->real_escape_string($search);
                $where = "WHERE vu.name LIKE '%$search%' OR vu.email LIKE '%$search%' OR vu.phone LIKE '%$search%'";
            }

            if (is_numeric($role_filter)) {
                $search = $this->db->real_escape_string($role_filter);
                $where = "WHERE vu.role_id = '$role_filter'";
            }

            if (is_numeric($status_filter)) {
                $search = $this->db->real_escape_string($status_filter);   
                $where = "WHERE vu.is_active = '$status_filter'";
            }
        }
        // total records
        $sql = "SELECT COUNT(DISTINCT vu.id) AS total FROM vp_users AS vu LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id $where";
        $resultCount = $this->db->query($sql);
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'] ?? 0;

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT vu.*, GROUP_CONCAT(vt.team_name SEPARATOR ', ') AS team_names FROM vp_users AS vu LEFT JOIN vp_user_team_mapping AS vutm ON vu.id = vutm.user_id LEFT JOIN vp_teams AS vt ON vutm.team_id = vt.id $where GROUP BY vu.id LIMIT $limit OFFSET $offset;";
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
    public function getAllRoles() {
        $sql = "SELECT id, role_name FROM vp_roles WHERE is_active = 1 ORDER BY role_name ASC";
        $result = $this->db->query($sql);
        $roles = [];        
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        return $roles;
    }
    public function getAllTeams() {
        $sql = "SELECT id, team_name FROM vp_teams WHERE is_active = 1 ORDER BY team_name ASC";
        $result = $this->db->query($sql);
        $teams = [];        
        while ($row = $result->fetch_assoc()) {
            $teams[] = $row;
        }
        return $teams;
    }
}


?>