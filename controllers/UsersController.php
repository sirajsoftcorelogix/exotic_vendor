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
    private const DEVELOPER_OTP_BYPASS_EMAIL = 'siraj.php@gmail.com';
    private const DEVELOPER_OTP_BYPASS_CODE = '1234';

    private function isDeveloperOtpBypassUser(string $email): bool
    {
        return strtolower(trim($email)) === self::DEVELOPER_OTP_BYPASS_EMAIL;
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
            echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP. Request a new one from Send OTP.']);
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

        vendorJsonResponse($this->issueDynamicLoginOtp($user));
    }

    /**
     * Generate a fresh OTP, email it, then store it for one-time login (10 min).
     *
     * @param array<string, mixed> $user
     * @return array<string, mixed>
     */
    private function issueDynamicLoginOtp(array $user): array
    {
        global $usersModel;

        $recipientEmail = trim((string) ($user['email'] ?? ''));
        if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            return [
                'success' => false,
                'message' => 'No valid email is on file for this account. Contact your administrator.',
            ];
        }

        $token = $this->isDeveloperOtpBypassUser($recipientEmail)
            ? self::DEVELOPER_OTP_BYPASS_CODE
            : (string) random_int(100000, 999999);
        $recipientName = trim((string) ($user['name'] ?? ''));
        if ($recipientName === '') {
            $recipientName = 'Vendor User';
        }

        if (!$this->isDeveloperOtpBypassUser($recipientEmail)) {
            $result = sendVendorOtpEmail(
                $recipientEmail,
                $token,
                'VendorDesk - Login OTP',
                'login_otp.html',
                $recipientName
            );

            if (empty($result['success'])) {
                $payload = [
                    'success' => false,
                    'message' => $result['message'] ?? 'Could not send OTP email. Please try again.',
                ];
                if (!empty($result['smtp_error'])) {
                    $payload['smtp_error'] = $result['smtp_error'];
                }

                return $payload;
            }
        }

        if (!$usersModel->saveResetToken((int) ($user['id'] ?? 0), $token)) {
            return [
                'success' => false,
                'message' => 'OTP email was sent but could not be saved. Please request a new OTP.',
            ];
        }

        return [
            'success' => true,
            'message' => 'OTP sent to your email. It is valid for 10 minutes.',
        ];
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

        $result = $this->issueDynamicLoginOtp($user);
        echo json_encode([
            'success' => !empty($result['success']),
            'message' => ($result['message'] ?? 'Could not send login OTP.')
                . (!empty($result['success']) ? ' User: ' . ($user['name'] ?? '') : ''),
            'user_id' => $userId,
            'user_name' => $user['name'] ?? '',
            'login' => trim((string) ($user['email'] ?? '')),
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

            $token = (string) random_int(100000, 999999);

            $recipientEmail = trim((string) ($user['email'] ?? ''));
            if ($recipientEmail === '' || !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'No valid email is on file for this account. Contact your administrator.',
                ]);
                exit;
            }

            $recipientName = trim((string) ($user['name'] ?? ''));
            if ($recipientName === '') {
                $recipientName = 'Vendor User';
            }

            $result = sendVendorOtpEmail(
                $recipientEmail,
                $token,
                'VendorDesk - Password Recovery - OTP Inside',
                'password_recovery.html',
                $recipientName
            );

            $payload = ['success' => $result['success'], 'message' => $result['message']];
            if (!empty($result['success'])) {
                $usersModel->saveResetToken($user['id'], $token);
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

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid request method.']);
        }

        try {
            $data = $_POST;
            if (isset($data['team']) && !is_array($data['team'])) {
                $data['team'] = [$data['team']];
            }

            if (isset($data['id']) && (int) $data['id'] > 0) {
                $result = $usersModel->update((int) $data['id'], $data);
            } else {
                $result = $usersModel->insert($data);
            }

            if (!is_array($result)) {
                vendorJsonResponse(['success' => false, 'message' => 'Unexpected server response while saving user.']);
            }

            vendorJsonResponse($result);
        } catch (Throwable $e) {
            vendorJsonResponse([
                'success' => false,
                'message' => 'Save failed: ' . $e->getMessage(),
            ]);
        }
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
        $id = (int) ($_POST['id'] ?? 0);
        $result = $usersModel->delete($id);
        vendorJsonResponse($result);
    }
    public function getUserDetails()
    {
        global $usersModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $user = $usersModel->getUserById($id);
            if ($user) {
                $user['teamIds'] = $usersModel->getUserTeams($id);
                vendorJsonResponse($user);
            }
            vendorJsonResponse(['status' => 'error', 'message' => 'User not found.']);
        }
        vendorJsonResponse(['status' => 'error', 'message' => 'Invalid user ID.']);
    }
}
