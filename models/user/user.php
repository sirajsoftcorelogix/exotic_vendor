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
        if (password_verify($password, $user['password'])) {
            @session_start();
            $_SESSION['user'] = $user;
            return true;
        }
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

        $sql = "INSERT INTO vp_users (name, email, phone, password, role, is_active) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        $stmt->bind_param('sssssi', $data['name'], $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['is_active']);
        if ($stmt->execute()) {
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
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, password = ?, role = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->bind_param('ssssiii', $data['name'], $data['email'], $data['phone'], $hashedPassword, $data['role'], $data['is_active'], $id);
        } else {
            $sql = "UPDATE vp_users SET name = ?, email = ?, phone = ?, role = ?, is_active = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssiii', $data['name'], $data['email'], $data['phone'], $data['role'], $data['is_active'], $id);
        }
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'User updated successfully.'];
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
            return ['success' => true, 'message' => 'User deleted successfully. SQL: '.$stmt->error];
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
    public function getRoles() {
        $sql = "SELECT id, role_name FROM roles WHERE active = 1 ORDER BY role_name ASC";
        $result = $this->db->query($sql);
        $roles = [];        
        while ($row = $result->fetch_assoc()) {
            $roles[$row['id']] = $row['role_name'];
        }
        return $roles;
    }
}


?>