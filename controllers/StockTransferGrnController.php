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

    private function getWarehouseList() {
        $warehouses = [];
        $warehouseQuery = "SELECT id, address_title FROM exotic_address WHERE is_active = 1 ORDER BY address_title ASC";
        $warehouseResult = mysqli_query($this->conn, $warehouseQuery);
        if ($warehouseResult) {
            while ($row = mysqli_fetch_assoc($warehouseResult)) {
                $warehouses[] = $row;
            }
        }
        return $warehouses;
    }

    public function listGrns() {
        is_login();

        $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;
        $grns = $this->stockTransferModel->listTransferGrns($transferId);
        $transfer = $transferId > 0 ? $this->stockTransferModel->getTransferById($transferId) : null;

        renderTemplate('views/stock_transfer_grns/stock_transfer_grn_list.php', [
            'transfer' => $transfer,
            'grns' => $grns,
            'transferId' => $transferId,
        ], 'Stock Transfer GRN List');
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

        // Ensure dispatch_date is set properly, fallback to today if missing
        if (empty($transfer['dispatch_date'])) {
            $transfer['dispatch_date'] = date('Y-m-d');
        }

        // Cumulative qty already GRN'd for this SKU on this transfer (for remaining / caps in UI)
        if (!empty($transfer['items'])) {
            foreach ($transfer['items'] as &$item) {
                $itemSku = trim($item['sku'] ?? '');
                $item['already_received_on_transfer'] = 0;
                if ($itemSku !== '' && $transferId > 0) {
                    $item['already_received_on_transfer'] = (int)$this->stockTransferModel->getReceivedQtyForTransferSku($transferId, $itemSku);
                }
                $tq = (int)($item['transfer_qty'] ?? 0);
                $item['remaining_to_receive'] = max(0, $tq - $item['already_received_on_transfer']);
            }
            unset($item);
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
            'mode' => 'create',
            'transfer' => $transfer,
            'users' => $users,
            'warehouses' => $warehouses,
            'default_warehouse_id' => (int)($transfer['to_warehouse'] ?? 0),
            'default_received_by' => (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0))
        ], 'Stock Transfer GRN');
    }

    public function edit() {
        is_login();
        $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;
        header('Location: ?page=stock_transfer_grns&action=list&transfer_id=' . $transferId);
        exit;
    }

    public function update() {
        is_login();
        $transferId = isset($_POST['transfer_id']) ? (int)$_POST['transfer_id'] : 0;
        header('Location: ?page=stock_transfer_grns&action=list&transfer_id=' . $transferId);
        exit;
    }

    public function delete() {
        is_login();

        $grnId = isset($_GET['grn_id']) ? (int)$_GET['grn_id'] : 0;
        if ($grnId <= 0) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Invalid GRN id']], 'Error');
            return;
        }

        $grn = $this->stockTransferModel->getTransferGrnById($grnId);
        if (!$grn) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'GRN not found']], 'Error');
            return;
        }

        $this->stockTransferModel->deleteTransferGrn($grnId);
        header('Location: ?page=stock_transfer_grns&action=list&transfer_id=' . (int)$grn['transfer_id']);
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
        $remarks = isset($data['grn_remarks']) ? trim((string)$data['grn_remarks'])
            : (isset($data['remarks']) ? trim((string)$data['remarks']) : '');
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
