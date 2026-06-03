<?php
require_once 'models/user/user.php';
$usersModel = new User($conn);
//$root_path1 = $_SERVER['SERVER_NAME'];
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once __DIR__ . '/../helpers/mail_helper.php';

global $root_path;
global $domain;

class UsersController
{
    /** Dev-only login while SMTP is down (no admin OTP required). */
    private const DEV_LOGIN_EMAIL = 'siraj.php@gmail.com';
    private const DEV_LOGIN_OTP = '1234';

    /** Set false when SMTP email OTP is restored. */
    private const SKIP_EMAIL_OTP = true;

    private function isDevLoginEmail(string $login): bool
    {
        return strtolower(trim($login)) === strtolower(self::DEV_LOGIN_EMAIL);
    }

    public function login()
    {
        // echo "This is the login page.";
        renderTemplateClean('views/users/login.php', [], 'Login');
    }
    public function loginProcess()
    {
        global $usersModel;
        $login = trim($_POST['login'] ?? '');
        $otp = $_POST['otp'] ?? '';

        if (empty($login) || empty($otp)) {
            echo json_encode(['success' => false, 'message' => 'Please enter both email and OTP.']);
            exit;
        }
        $logininfo = $usersModel->loginWithOtp($login, $otp);
        if ($logininfo) {
            echo json_encode(['success' => true, 'message' => 'Login successful.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid OTP or email.']);
        }
    }

    public function sendLoginOtp()
    {
        global $usersModel;
        $login = trim($_POST['login'] ?? '');
        if (empty($login)) {
            vendorJsonResponse(['success' => false, 'message' => 'Please enter your email or phone.']);
        }

        $user = $usersModel->findByLogin($login);
        if (!$user) {
            vendorJsonResponse(['success' => false, 'message' => 'User not found.']);
        }

        if ($this->isDevLoginEmail($login)) {
            $usersModel->saveResetToken($user['id'], self::DEV_LOGIN_OTP);
            vendorJsonResponse([
                'success' => true,
                'message' => 'OTP ready. Use 1234 to sign in.',
            ]);
        }

        if (self::SKIP_EMAIL_OTP) {
            $existing = trim((string) ($user['remember_token'] ?? ''));
            if ($existing !== '') {
                vendorJsonResponse([
                    'success' => true,
                    'message' => 'Enter the login OTP provided by your administrator.',
                ]);
            }
            vendorJsonResponse([
                'success' => false,
                'message' => 'No login OTP assigned. Ask an administrator to generate one from the Users list.',
            ]);
        }

        $token = (string) random_int(100000, 999999);
        $usersModel->saveResetToken($user['id'], $token);

        $result = sendVendorOtpEmail(
            $login,
            $token,
            'VendorDesk - Login OTP',
            'login_otp.html'
        );

        $payload = [
            'success' => $result['success'],
            'message' => $result['message'],
        ];
        if (!empty($result['smtp_error'])) {
            $payload['smtp_error'] = $result['smtp_error'];
        }
        vendorJsonResponse($payload);
    }

    public function generateLoginOtp()
    {
        is_login();
        global $usersModel;

        header('Content-Type: application/json; charset=UTF-8');

        $userId = (int) ($_POST['user_id'] ?? $_GET['user_id'] ?? 0);
        if ($userId < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid user.']);
            exit;
        }

        $user = $usersModel->getUserById($userId);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        $otp = (string) random_int(100000, 999999);
        if (!$usersModel->saveResetToken($userId, $otp)) {
            echo json_encode(['success' => false, 'message' => 'Could not save OTP.']);
            exit;
        }

        $loginHint = trim((string) ($user['email'] ?? ''));
        if ($loginHint === '') {
            $loginHint = trim((string) ($user['phone'] ?? ''));
        }

        echo json_encode([
            'success' => true,
            'message' => 'Login OTP generated for ' . ($user['name'] ?? 'user') . '.',
            'otp' => $otp,
            'user_id' => $userId,
            'login' => $loginHint,
            'user_name' => $user['name'] ?? '',
        ]);
        exit;
    }

    public function logout()
    {
        global $domain;
        session_start();
        session_destroy();
        header('Location: ' . $domain . '?page=users&action=login');
        exit;
    }
    public function forgotPassword()
    {

        renderTemplateClean('views/users/forgot_password.php', [], 'Forgot Password');
    }

    public function sendResetLink()
    {
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

            if (self::SKIP_EMAIL_OTP) {
                $token = (string) random_int(100000, 999999);
                $usersModel->saveResetToken($user['id'], $token);
                echo json_encode([
                    'success' => true,
                    'message' => 'OTP generated. Share it with the user: ' . $token,
                    'token' => $token,
                ]);
                exit;
            }

            $token = (string) random_int(100000, 999999);
            $usersModel->saveResetToken($user['id'], $token);

            $result = sendVendorOtpEmail(
                $login,
                $token,
                'VendorDesk - Password Recovery - OTP Inside',
                'password_recovery.html'
            );

            $payload = ['success' => $result['success'], 'message' => $result['message']];
            if ($result['success']) {
                $payload['token'] = $token;
            } elseif (!empty($result['smtp_error'])) {
                $payload['smtp_error'] = $result['smtp_error'];
            }
            echo json_encode($payload);
        } else {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
        }
        exit;
    }
    public function changePassword()
    {
        renderTemplate('views/users/change_password.php', [], 'Change Password');
    }
    public function changePasswordProcess()
    {
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
    public function verifyResetToken()
    {
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
    public function resetPassword()
    {
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
    public function resetPasswordProcess()
    {
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
    public function updateCaptcha()
    {
        // Generate a random 5-character alphanumeric string
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $captcha = '';
        for ($i = 0; $i < 5; $i++) {
            $captcha .= $characters[rand(0, strlen($characters) - 1)];
        }
        @session_start();
        $_SESSION['captcha'] = $captcha;
        echo json_encode(['success' => 'true', 'captcha' => $captcha]);
        exit;
    }
    public function validateCaptcha()
    {
        @session_start();
        $captcha = $_POST['captcha'] ?? '';
        if ($captcha === $_SESSION['captcha'] ?? '') {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid captcha.']);
        }
        exit;
    }
    public function index()
    {

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

        $users_data = $usersModel->getAllUsersListing($page_no, $limit, $search, $role_filter, $status_filter);
        $roles = $usersModel->getAllRoles();
        $teams = $usersModel->getAllTeams();
        $warehouses = $usersModel->getAllWarehouses();
        // print_r($warehouses);exit;
        $data = [
            'users' => $users_data["users"],
            'roles_list' => $roles,
            'teams_list' => $teams,
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
            'status_filter' => $status_filter,
            'warehouses_list' => $warehouses,

        ];

        // View expects $data['…'] while renderTemplate extract() flattens keys; provide nested $data.
        renderTemplate('views/users/index.php', array_merge($data, ['data' => $data]), 'Users');
    }
    public function addEditUser()
    {
        is_login();
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
                $data['teamIds'] = $usersModel->getUserTeams($id);
            }
        } catch (Exception $e) {
            $data['message'] = ['success' => false, 'error' => $e->getMessage()];
        }
        renderTemplate('views/users/add_edit_user.php', $data, 'Add/Edit User');
    }
    public function addPost()
    {
        is_login();
        global $usersModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            if (isset($data['id']) && $data['id'] > 0) {
                $result = $usersModel->update($data['id'], $data);
            } else {
                $data['id'] = 0; // Ensure id is set for insert
                $result = $usersModel->insert($data);
            }
            echo json_encode($result);
        }
        exit;
    }
    function checkPasswords($password, $confirmPassword)
    {
        if (empty($password)) {
            return "Password cannot be empty.";
        }

        if ($password !== $confirmPassword) {
            return "Passwords do not match.";
        }

        /*if (strlen($password) < 8) {
            return "Password must be at least 8 characters long.";
        }*/

        return true;
    }
    public function updateUserProfile()
    {
        is_login();
        global $usersModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            $resultChk = $this->checkPasswords($password, $confirmPassword);

            if ($resultChk !== true) {
                $result = [
                    'success' => false,
                    'message' => 'Error occurred. ' . $resultChk
                ];
                echo json_encode($result);
                exit;
            }
            $data = $_POST;
            if (isset($data['id']) && $data['id'] > 0) {
                $result = $usersModel->updateUserPriofile($data['id'], $data);
            } else {
                $result = [
                    'success' => false,
                    'message' => 'Error occurred. Please check your input and fill all required fields correctly.'
                ];
            }
            echo json_encode($result);
        }
        exit;
    }

    public function delete()
    {
        global $usersModel;
        $id = $_POST['id'] ?? 0;
        $result = $usersModel->delete($id);

        echo json_encode($result);
        exit;
    }
    public function getUserDetails()
    {
        global $usersModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $user = $usersModel->getUserById($id);
            $user['teamIds'] = $usersModel->getUserTeams($id);
            // echo '<pre>'; print_r($user); exit;
            if ($user) {

                echo json_encode($user);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid user ID.']);
        }
        exit;
    }
}
