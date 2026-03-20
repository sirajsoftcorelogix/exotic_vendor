<?php
require_once 'models/product/StockTransfer.php';
require_once 'models/user/user.php';

class StockTransferGrnController {
    private $conn;
    private $stockTransferModel;
    private $usersModel;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->stockTransferModel = new StockTransfer($conn);
        $this->usersModel = new User($conn);
    }

    public function create() {
        is_login();

        $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;
        if ($transferId <= 0) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Invalid transfer id']], 'Error');
            return;
        }

        $transfer = $this->stockTransferModel->getTransferById($transferId);
        if (!$transfer) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Stock transfer not found']], 'Error');
            return;
        }

        // Provide a list of users for the "received by" dropdown.
        $users = $this->usersModel->getAllUsers();

        // Provide warehouse list for receiving location
        $warehouses = [];
        $warehouseQuery = "SELECT id, address_title FROM exotic_address WHERE is_active = 1 ORDER BY address_title ASC";
        $warehouseResult = mysqli_query($this->conn, $warehouseQuery);
        if ($warehouseResult) {
            while ($row = mysqli_fetch_assoc($warehouseResult)) {
                $warehouses[] = $row;
            }
        }

        renderTemplate('views/stock_transfer_grns/stock_trasfer_grn.php', [
            'transfer' => $transfer,
            'users' => $users,
            'warehouses' => $warehouses,
            'default_warehouse_id' => (int)($transfer['to_warehouse'] ?? 0),
            'default_received_by' => (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0))
        ], 'Stock Transfer GRN');
    }

    public function createPost() {
        is_login();
        header('Content-Type: application/json');

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (stripos($contentType, 'application/json') !== false) {
            $raw = file_get_contents('php://input');
            $data = json_decode($raw, true) ?: [];
        } else {
            $data = $_POST;
            if (isset($data['items']) && is_string($data['items'])) {
                $decoded = json_decode($data['items'], true);
                if (is_array($decoded)) {
                    $data['items'] = $decoded;
                }
            }
        }

        $files = $_FILES['grn_file'] ?? null;

        $transferId = isset($data['transfer_id']) ? (int)$data['transfer_id'] : 0;
        $receivedBy = isset($data['received_by']) ? (int)$data['received_by'] : ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
        $receivedDate = isset($data['received_date']) ? $data['received_date'] : date('Y-m-d');
        $remarks = isset($data['remarks']) ? trim($data['remarks']) : '';
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : 0;

        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        if ($transferId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
            return;
        }

        if ($warehouseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select warehouse']);
            return;
        }

        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'No items found']);
            return;
        }

        $result = $this->stockTransferModel->createTransferGrn([
            'transfer_id' => $transferId,
            'received_by' => $receivedBy,
            'received_date' => $receivedDate,
            'remarks' => $remarks,
            'warehouse_id' => $warehouseId,
            'items' => $items,
            'files' => $files,
            'user_id' => $_SESSION['user']['id'] ?? 0,
        ]);

        echo json_encode($result);
    }
}
