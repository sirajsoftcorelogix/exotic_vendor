<?php 
require_once 'models/order/order.php';
require_once 'models/comman/tables.php';

$ordersModel = new Order($conn);
$commanModel = new Tables($conn);

class OrdersAPIController { 
    
    /**
     * Validate API token from Authorization header or query parameter
     * Accepts Bearer token or token query parameter
     * 
     * @return bool True if token is valid, false otherwise
     */
    private function validateApiToken() {
        // Get token from Authorization header or query parameter
        $token = null;

        // Check Authorization header (Bearer token)
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            if (preg_match('/Bearer\s+(.+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        // Check query parameter if not found in header
        if (!$token && isset($_GET['api_token'])) {
            $token = trim($_GET['api_token']);
        }

        // Check POST parameter if not found in header or query
        if (!$token && isset($_POST['api_token'])) {
            $token = trim($_POST['api_token']);
        }

        // If no token found
        if (!$token) {
            return false;
        }

        // Validate token against database
        return $this->isValidToken($token);
    }

    /**
     * Check if token exists and is valid in database
     * 
     * @param string $token The API token to validate
     * @return bool True if token is valid, false otherwise
     */
    private function isValidToken($token) {
        global $conn;

        // Validate token against order_api_tokens table
        // Assuming a table structure: order_api_tokens (id, user_id, token, created_at, expires_at, is_active)
        $sql = "SELECT id, user_id, is_active, expires_at FROM order_api_tokens WHERE token = ? AND is_active = 1 LIMIT 1";
        $stmt = $conn->prepare($sql);
        
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            // Check if token has expired
            if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
                // Token has expired
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Generate API token for a user
     * This can be called separately to generate tokens
     * 
     * @param int $user_id The user ID
     * @param int $expiryDays Number of days token is valid (default: 365)
     * @return array Token details or error
     */
    public function generateApiToken($user_id = null, $expiryDays = 365) {
        global $conn;

        // If no user_id provided, use current session user
        if (!$user_id && isset($_SESSION['user']['id'])) {
            $user_id = (int)$_SESSION['user']['id'];
        }

        if (!$user_id) {
            return ['success' => false, 'message' => 'Invalid user_id.'];
        }

        // Generate unique token
        $token = bin2hex(random_bytes(32));
        
        // Calculate expiry date
        $expiryDate = date('Y-m-d H:i:s', time() + ($expiryDays * 86400));

        // Insert token into database
        $sql = "INSERT INTO order_api_tokens (user_id, token, created_at, expires_at, is_active) VALUES (?, ?, NOW(), ?, 1)";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $conn->error];
        }

        $stmt->bind_param('iss', $user_id, $token, $expiryDate);
        
        if ($stmt->execute()) {
            return [
                'success' => true,
                'message' => 'API token generated successfully.',
                'token' => $token,
                'expires_at' => $expiryDate,
                'user_id' => $user_id
            ];
        }

        return ['success' => false, 'message' => 'Failed to generate token: ' . $stmt->error];
    }
    /**
     * Update order status via API
     * POST parameters:
     * - order_number (required): Order number (will be used to fetch order_id)
     * - status (required): New order status
     * - remarks (optional): Order remarks/notes
     * - esd (optional): Expected Ship Date
     * - priority (optional): Order priority
     * - agent_id (optional): Assigned agent ID
     * 
     * Returns JSON response
     */
    public function updateOrderStatus() {
        global $ordersModel, $commanModel;
        header('Content-Type: application/json');
        
        // Validate API token
        if (!$this->validateApiToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or missing API token.']);
            exit;
        }
        
        // Validate request method
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
            exit;
        }

        // Get input data from POST or JSON
        $input = $_POST;
        if (empty($input) && file_exists('php://input')) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }

        // Validate required fields
        $order_number = isset($input['order_number']) ? trim($input['order_number']) : '';
        $status = isset($input['status']) ? trim($input['status']) : '';

        if (empty($order_number)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing order_number.']);
            exit;
        }

        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing status.']);
            exit;
        }

        // Get optional fields
        $remarks = isset($input['remarks']) ? trim($input['remarks']) : NULL;
        $esd = isset($input['esd']) ? trim($input['esd']) : NULL;
        $priority = isset($input['priority']) ? trim($input['priority']) : NULL;
        $agent_id = isset($input['agent_id']) ? (int)$input['agent_id'] : NULL;

        // Fetch order by order_number to get order_id
        $order = $ordersModel->getOrderByOrderNumber($order_number);
        if (!$order) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Order not found with order_number: ' . $order_number]);
            exit;
        }

        $order_id = (int)$order['id'];

        // Store previous values for logging
        $previous_status = $order['status'] ?? '';
        $previous_esd = $order['esd'] ?? '';
        $previous_priority = $order['priority'] ?? '';
        $previous_remarks = $order['remarks'] ?? '';
        $previous_agent = $order['agent_id'] ?? NULL;

        try {
            // Prepare update data
            $update_data = [
                'status' => $status,
                'remarks' => $remarks,
                'priority' => $priority,
                'agent_id' => $agent_id
            ];

            // Only include ESD if provided and valid
            if ($esd !== NULL && $esd !== '') {
                $update_data['esd'] = $esd;
            }

            // Update order status
            $updated = $ordersModel->updateStatus($order_id, $update_data);

            if (!$updated) {
                throw new Exception('Failed to update order status in database.');
            }

            // Log status change if status changed
            if ($status !== $previous_status) {
                $logData = [
                    'order_id' => $order_id,
                    'status' => 'Status: ' . $status,
                    'changed_by' => $_SESSION['user']['id'] ?? 0,
                    'api_response' => NULL,
                    'change_date' => date('Y-m-d H:i:s')
                ];
                $commanModel->add_order_status_log($logData);
            }

            // Log agent change if agent changed
            if ($agent_id !== $previous_agent) {
                $agent_name = $commanModel->getUserNameById($agent_id) ?? 'Unknown';
                $agentLogData = [
                    'order_id' => $order_id,
                    'status' => 'Agent: ' . $agent_name,
                    'changed_by' => $_SESSION['user']['id'] ?? 0,
                    'api_response' => NULL,
                    'change_date' => date('Y-m-d H:i:s')
                ];
                $commanModel->add_order_status_log($agentLogData);

                // Update agent assignment date
                $commanModel->updateRecord('vp_orders', ['agent_assign_date' => date('Y-m-d H:i:s')], $order_id);

                // Send notification to assigned agent
                if ($agent_id > 0) {
                    $link = base_url('index.php?page=orders&action=get_order_details_html&type=outer&order_number=' . $order['order_number']);
                    insertNotification($agent_id, 'Order Assigned', 'Order <a href="' . $link . '" class="text-blue-600 hover:underline" target="_blank">' . $order['order_number'] . '</a> has been assigned to you for processing.', $link);
                }
            }

            // Log ESD change if ESD changed
            if ($esd !== $previous_esd) {
                $esdLogData = [
                    'order_id' => $order_id,
                    'status' => 'ESD: ' . ($esd ?? 'N/A'),
                    'changed_by' => $_SESSION['user']['id'] ?? 0,
                    'api_response' => NULL,
                    'change_date' => date('Y-m-d H:i:s')
                ];
                $commanModel->add_order_status_log($esdLogData);
            }

            // Log priority change if priority changed
            if ($priority !== $previous_priority) {
                $priorityLogData = [
                    'order_id' => $order_id,
                    'status' => 'Priority: ' . ($priority ?? 'N/A'),
                    'changed_by' => $_SESSION['user']['id'] ?? 0,
                    'api_response' => NULL,
                    'change_date' => date('Y-m-d H:i:s')
                ];
                $commanModel->add_order_status_log($priorityLogData);
            }

            // Log remarks change if remarks changed
            if ($remarks !== $previous_remarks) {
                $remarksLogData = [
                    'order_id' => $order_id,
                    'status' => 'Notes updated.',
                    'changed_by' => $_SESSION['user']['id'] ?? 0,
                    'api_response' => NULL,
                    'change_date' => date('Y-m-d H:i:s')
                ];
                $commanModel->add_order_status_log($remarksLogData);
            }

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => [
                    'order_id' => $order_id,
                    'previous_status' => $previous_status,
                    'new_status' => $status,
                    'updated_at' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error updating order status: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Bulk update order status
     * POST parameters:
     * - order_numbers (required): Array of order numbers (comma-separated or array)
     * - status (required): New status for all orders
     * 
     * Returns JSON response with results
     */
    public function bulkUpdateOrderStatus() {
        global $ordersModel, $commanModel;
        header('Content-Type: application/json');

        // Validate API token
        if (!$this->validateApiToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or missing API token.']);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
            exit;
        }

        $input = $_POST;
        if (empty($input) && file_exists('php://input')) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }

        // Support both array and comma-separated string
        $order_numbers = isset($input['order_numbers']) ? $input['order_numbers'] : [];
        if (is_string($order_numbers)) {
            $order_numbers = array_map('trim', explode(',', $order_numbers));
        }
        $order_numbers = (array)$order_numbers;
        $order_numbers = array_filter($order_numbers); // Remove empty values
        
        $status = isset($input['status']) ? trim($input['status']) : '';

        if (empty($order_numbers)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing order_numbers.']);
            exit;
        }

        if (empty($status)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing status.']);
            exit;
        }

        try {
            $results = [
                'successful' => 0,
                'failed' => 0,
                'errors' => []
            ];

            foreach ($order_numbers as $order_number) {
                $order_number = trim($order_number);
                if (empty($order_number)) continue;

                // Fetch order by order_number
                $order = $ordersModel->getOrderByOrderNumber($order_number);
                if (!$order) {
                    $results['failed']++;
                    $results['errors'][] = "Order not found with number: {$order_number}";
                    continue;
                }

                $order_id = (int)$order['id'];

                $update_data = ['status' => $status];
                $updated = $ordersModel->updateStatus($order_id, $update_data);

                if ($updated) {
                    $logData = [
                        'order_id' => $order_id,
                        'status' => 'Status: ' . $status,
                        'changed_by' => $_SESSION['user']['id'] ?? 0,
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($logData);
                    $results['successful']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to update order ID {$order_id}.";
                }
            }

            http_response_code(200);
            echo json_encode([
                'success' => $results['failed'] === 0,
                'message' => $results['successful'] . ' order(s) updated successfully.',
                'data' => $results
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error during bulk update: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Get order status history
     * GET parameters:
     * - order_number (required): Order number
     * 
     * Returns JSON with status history
     */
    public function getOrderStatusHistory() {
        global $ordersModel, $commanModel;
        header('Content-Type: application/json');

        // Validate API token
        if (!$this->validateApiToken()) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Invalid or missing API token.']);
            exit;
        }

        $order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

        if (empty($order_number)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or missing order_number.']);
            exit;
        }

        try {
            // Fetch order by order_number
            $order = $ordersModel->getOrderByOrderNumber($order_number);
            if (!$order) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Order not found with order_number: ' . $order_number]);
                exit;
            }

            $order_id = (int)$order['id'];

            $history = $commanModel->get_order_status_log($order_id);

            http_response_code(200);
            echo json_encode([
                'success' => true,
                'order_id' => $order_id,
                'order_number' => $order['order_number'],
                'current_status' => $order['status'],
                'history' => $history
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error fetching status history: ' . $e->getMessage()
            ]);
        }

        exit;
    }

    /**
     * Generate API token endpoint
     * POST parameters:
     * - expiry_days (optional): Number of days until token expires (default: 365)
     * 
     * Returns JSON with generated token
     */
    /**
     * Generate API token via username/password or session
     * POST parameters:
     * - username (optional): User email or phone for credential-based auth
     * - password (optional): User password for credential-based auth
     * - expiry_days (optional): Number of days token is valid (1-3650, default: 365)
     * 
     * Returns JSON response with token
     */
    public function generateToken() {
        global $conn;
        header('Content-Type: application/json');

        // Get input data from POST or JSON
        $input = $_POST;
        if (empty($input) && file_exists('php://input')) {
            $json = file_get_contents('php://input');
            $input = json_decode($json, true) ?? [];
        }

        $username = isset($input['username']) ? trim($input['username']) : '';
        $password = isset($input['password']) ? trim($input['password']) : '';
        $expiryDays = isset($input['expiry_days']) ? (int)$input['expiry_days'] : 365;

        if ($expiryDays <= 0 || $expiryDays > 3650) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid expiry_days. Must be between 1 and 3650.']);
            exit;
        }

        $user_id = null;

        // Try credential-based authentication first
        if (!empty($username) && !empty($password)) {
            // Authenticate user by email or phone
            $sql = "SELECT id, email, phone, password FROM vp_users WHERE (email = ? OR phone = ?) AND is_deleted = 0 LIMIT 1";
            $stmt = $conn->prepare($sql);
            
            if (!$stmt) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Database error']);
                exit;
            }

            $stmt->bind_param('ss', $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user = $result->fetch_assoc();
                
                // Verify password
                // Note: Depending on your password storage, you may need to adjust this
                // If passwords are hashed with password_hash(), use: password_verify($password, $user['password'])
                // If passwords are stored plaintext, use: $password === $user['password']
                if (password_verify($password, $user['password']) || $password === $user['password']) {
                    $user_id = (int)$user['id'];
                } else {
                    http_response_code(401);
                    echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
                    exit;
                }
            } else {
                http_response_code(401);
                echo json_encode(['success' => false, 'message' => 'Invalid username or password.']);
                exit;
            }
        }
        // Fall back to session authentication
        else if (isset($_SESSION['user']['id'])) {
            $user_id = (int)$_SESSION['user']['id'];
        }
        // No valid authentication method provided
        else {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Provide username/password or login to generate API token.']);
            exit;
        }

        try {
            $result = $this->generateApiToken($user_id, $expiryDays);
            
            if ($result['success']) {
                http_response_code(200);
                echo json_encode($result);
            } else {
                http_response_code(500);
                echo json_encode($result);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error generating token: ' . $e->getMessage()
            ]);
        }

        exit;
    }
}
?>
