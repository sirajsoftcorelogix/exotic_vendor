<?php
require_once 'models/user/user.php';
$usersModel = new User($conn);
//$root_path1 = $_SERVER['SERVER_NAME'];
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

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
        global $domain;
        session_start();
        session_destroy();
        header('Location: ' . $domain . '?page=users&action=login');
        exit;
    }   
    public function forgotPassword() {
        
        renderTemplateClean('views/users/forgot_password.php', [], 'Forgot Password');
    }

    public function sendResetLink() {
        //echo "Sending reset link...";
        global $usersModel;
        global $domain;
        $login = trim($_POST['login'] ?? '');
        if (empty($login)) {
            echo json_encode(['success' => false, 'message' => 'Please enter your email or phone.']);
            exit;
        }
        // Lookup user by email or phone
        $user = $usersModel->findByLogin($login);
        if ($user) {
            // Generate token, save to DB, and send email/SMS (implement as needed)
            /*$token = bin2hex(random_bytes(6));
            $usersModel->saveResetToken($user['id'], $token);
            
            $resetLink = "$domain/?page=reset_password&token=$token";
            mail($login, "Password Reset", "Click here to reset your password: $resetLink");*/

            // ...
            //for test only
            $token = rand(100000, 999999);
            $usersModel->saveResetToken($user['id'], $token);

             // Send email using PHPMailer
            

            $mail = new PHPMailer(true);
            try {
                //Server settings
                $mail->isSMTP();
                $mail->Host       = 'glacier.mxrouting.net'; // Set SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'vendoradmin@exoticindia.com';   // SMTP username
                $mail->Password   = 'xah5VfXUrdVaju576bpa';     // SMTP password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                //Recipients
                $mail->setFrom('vendoradmin@exoticindia.com', 'Admin');
                $mail->addAddress($login); // Send to user email

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Password Reset OTP';
                $mail->Body    = "Your OTP for password reset is: <b>$token</b>";

                $mail->send();

                echo json_encode(['success' => true, 'message' => 'OTP sent.', 'token' => $token]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
                }
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
    public function verifyResetToken(){
        global $usersModel;
        $token = $_POST['token'] ?? '';
        $login = $_POST['login'] ?? '';
        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Invalid token.']);
            exit;
        }
        if (!$login) {
            echo json_encode(['success' => false, 'message' => 'Invalid login.']);
            exit;
        }
        if (!$usersModel->verifyResetToken($login, $token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token or login.']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Token is valid.']);
    }
    public function resetPassword() {
        global $usersModel;
        $token = $_GET['token'] ?? '';
        $login = $_GET['login'] ?? '';
        if (!$token) {
            echo json_encode(['success' => false, 'message' => 'Invalid token.']);
            exit;
        }
        if (!$login) {
            echo json_encode(['success' => false, 'message' => 'Invalid login.']);
            exit;
        }
        if (!$usersModel->verifyResetToken($login, $token)) {
            echo json_encode(['success' => false, 'message' => 'Invalid token or login.']);
            exit;
        }
        // Validate token and show reset password form

        renderTemplateClean('views/users/reset_password.php', ['token' => $token, 'login' => $login], 'Reset Password');
    }
    public function resetPasswordProcess() {
        global $usersModel;
        $newPassword = $_POST['newPassword'] ?? '';
        $login = $_POST['login'] ?? '';
        if (empty($newPassword) || empty($login)) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $user = $usersModel->findByLogin($login);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }
        if ($usersModel->updatePassword($user['id'], $newPassword)) {
            // Clear the reset token
            $usersModel->saveResetToken($user['id'], null);
            echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
        exit;
    }
    public function updateCaptcha() {
        // Generate a random 5-character alphanumeric string
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $captcha = '';
        for ($i = 0; $i < 5; $i++) {
            $captcha .= $characters[rand(0, strlen($characters) - 1)];
        }
        @session_start();
        $_SESSION['captcha'] = $captcha;
        echo json_encode(['success'=>'true', 'captcha' => $captcha]);        
        exit;
    }
    public function validateCaptcha(){
        @session_start();
        $captcha = $_POST['captcha'] ?? '';
        if ($captcha === $_SESSION['captcha'] ?? '') {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid captcha.']);
        }
        exit;
    }
    public function index() {
        is_login();
        global $usersModel;
        /*$page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;*/
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $role_filter = isset($_GET['role_filter']) ? trim($_GET['role_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';

        $sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'id';
        $sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'asc';

        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown
        
        //$users_data = $usersModel->getAll($search, $sort_by, $sort_order, $limit, $offset);
        //$total_records = $usersModel->countAll($search);
        $users_data = $usersModel->getAllUsersListing($page_no, $limit, $search, $role_filter, $status_filter);
        $data = [
            'users' => $users_data["users"],
            'page_no' => $page_no,
            'total_pages' => $users_data["totalPages"],
            'sort_by' => $sort_by,
            'sort_order' => $sort_order,
            'search' => $search,
            'totalPages'   => $users_data["totalPages"],
            'currentPage'  => $users_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $users_data["totalRecords"],
            'role_filter'  => $role_filter,
            'status_filter'=> $status_filter
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
                $data['user'] = $usersModel->getUserById($id);
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
        exit;
    }
}
?>