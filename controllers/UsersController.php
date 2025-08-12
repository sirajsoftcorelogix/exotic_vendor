<?php
require_once 'models/user/user.php';
$usersModel = new User($conn);
//$root_path1 = $_SERVER['SERVER_NAME'];
global $root_path;
global $domain;

class UsersController {
    public function login() {
       // echo "This is the login page.";
        renderTemplateClean('views/users/login.php', [], 'Login');
    }
    public function loginProcess() {
        global $usersModel;
        //echo "Processing login...";
        $login = trim($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($login) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both login and password.']);
            exit;
        }
        $logininfo = $usersModel->login($login, $password);
        if ($logininfo) {            
            echo json_encode(['success' => true, 'message' => 'Login successful.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid login or password.']);
        }
        //require_once 'views/users/login_process.php';
    }

    public function logout() {
        session_start();
        session_destroy();
        header('Location: ' . $domain . '?page=users&action=login');
        exit;
    }   
    public function forgotPassword() {
        
        renderTemplate('views/users/forgot_password.php', [], 'Forgot Password');
    }

    public function sendResetLink() {
        //echo "Sending reset link...";
        global $usersModel;
        $login = trim($_POST['login'] ?? '');
        if (empty($login)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your email or phone.']);
            exit;
        }
        // Lookup user by email or phone
        $user = $usersModel->findByLogin($login);
        if ($user) {
            // Generate token, save to DB, and send email/SMS (implement as needed)
            // ...
            echo json_encode(['success' => true, 'message' => 'Reset link sent.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
        }
        exit;
    }
    public function changePassword() {
        renderTemplate('views/users/change_password.php', [], 'Change Password');
    }
    public function changePasswordProcess() {
        global $usersModel;
        session_start();
        $user = $_SESSION['user'] ?? null;
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Not logged in.']);
            exit;
        }
        $current = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        if ($usersModel->login($user['email'], $current)) {
            // Update password (hash it!)
            $usersModel->updatePassword($user['id'], $new);
            echo json_encode(['success' => true, 'message' => 'Password changed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password incorrect.']);
        }
        exit;
    }
    public function index() {
        global $usersModel;
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $search = isset($_GET['search']) ? trim($_GET['search']) : '';
        $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
        $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'asc';
        $limit = 20;
        $offset = ($page_no - 1) * $limit;

        $users_data = $usersModel->getAll($search, $sort_by, $sort_order, $limit, $offset);
        $total_records = $usersModel->countAll($search);    
        $data = [
            'users' => $users_data,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'search' => $search
        ];
        renderTemplate('views/users/index.php', $data, 'Users');
    }
    public function addEditUser() {
        global $usersModel;
        $data = []; 
        try {
            $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
                if ($id > 0) {
                    $data['message'] = $usersModel->update($id, $_POST);        
                } else {
                    $data['message'] = $usersModel->insert($_POST);
                }
            }
            if ($id > 0) {
                //echo "Hedayat";
                $data['user'] = $usersModel->getUserById($id);
                //print_r($data['user']);
            }
        } catch (Exception $e) {
            $data['message'] = ['success' => false, 'error' => $e->getMessage()];
        }
        renderTemplate('views/users/add_edit_user.php', $data, 'Add/Edit User');
    }
    public function addPost() {
        global $usersModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['role'] = 'user';
            $data['is_active'] = 1;
            //print_r($data);
            if (isset($data['id']) && $data['id'] > 0) {
                $result = $usersModel->update($data['id'], $data);
            }else {
                $data['id'] = 0; // Ensure id is set for insert
                $result = $usersModel->insert($data);
            }
            
            echo json_encode($result);
        }
        exit;
    }
    public function delete() {
        global $usersModel;
        $id = $_POST['id'] ?? 0;
        $result = $usersModel->delete($id);
        echo json_encode($result);
        // if ($usersModel->delete($id)) {
        //     echo json_encode(['success' => true, 'message' => 'User deleted.']);
        // } else {
        //     echo json_encode(['success' => false, 'message' => 'Delete failed.']);
        // }
        exit;
    }

}

?>