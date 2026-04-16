<?php
require_once 'models/product/StockTransfer.php';
require_once 'models/product/product.php';
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

    private function getWarehouseById($warehouseId) {
        $warehouseId = (int)$warehouseId;
        if ($warehouseId <= 0) {
            return null;
        }
        $warehouseIdEsc = (int)$warehouseId;
        $q = "SELECT id, address_title FROM exotic_address WHERE id = {$warehouseIdEsc} LIMIT 1";
        $res = mysqli_query($this->conn, $q);
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            return $row;
        }
        return null;
    }

    public function listGrns() {
        is_login();

        $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;
        $grns = $this->stockTransferModel->listTransferGrns($transferId);
        $transfer = $transferId > 0 ? $this->stockTransferModel->getTransferById($transferId) : null;

        $productModel = new product($this->conn);
        foreach ($grns as &$grnRow) {
            $resolved = null;
            $itemCode = trim((string)($grnRow['item_code'] ?? ''));
            if ($itemCode !== '') {
                $resolved = $productModel->findByItemCodeSizeColor(
                    $itemCode,
                    (string)($grnRow['size'] ?? ''),
                    (string)($grnRow['color'] ?? '')
                );
            }
            if (!$resolved) {
                $sku = trim((string)($grnRow['sku'] ?? ''));
                if ($sku !== '') {
                    $resolved = $productModel->getProductByskuExact($sku);
                }
            }
            $grnRow['label_product_id'] = $resolved && !empty($resolved['id']) ? (int)$resolved['id'] : 0;
            $recv = (int)($grnRow['qty_received'] ?? 0);
            $acc = (int)($grnRow['qty_acceptable'] ?? 0);
            $base = max($recv, $acc);
            $grnRow['label_default_qty'] = $base > 0 ? min(99, $base) : 1;
        }
        unset($grnRow);

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

        // Receiving warehouse is always the transfer destination (single option)
        $destId = (int)($transfer['to_warehouse'] ?? 0);
        if ($destId <= 0) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Transfer has no destination warehouse']], 'Error');
            return;
        }
        $destRow = $this->getWarehouseById($destId);
        if (!$destRow) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Destination warehouse not found']], 'Error');
            return;
        }
        $warehouses = [$destRow];

        // "Received by" — only users assigned to the destination warehouse
        $users = $this->usersModel->getActiveUsersByWarehouseId($destId);
        if ($users === []) {
            renderTemplate('views/errors/error.php', [
                'message' => [
                    'type' => 'error',
                    'text' => 'No active users are assigned to the destination warehouse (' . htmlspecialchars($destRow['address_title'] ?? '') . '). Assign at least one user to this warehouse before creating a GRN.',
                ],
            ], 'Error');
            return;
        }
        $sessionUid = (int)($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0));
        $defaultReceivedBy = 0;
        if ($sessionUid > 0 && isset($users[$sessionUid])) {
            $defaultReceivedBy = $sessionUid;
        } else {
            $defaultReceivedBy = (int)array_key_first($users);
        }

        renderTemplate('views/stock_transfer_grns/stock_trasfer_grn.php', [
            'mode' => 'create',
            'transfer' => $transfer,
            'users' => $users,
            'warehouses' => $warehouses,
            'default_warehouse_id' => (int)($transfer['to_warehouse'] ?? 0),
            'default_received_by' => $defaultReceivedBy
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

        $tid = (int)$grn['transfer_id'];
        if (!$this->stockTransferModel->deleteTransferGrn($grnId)) {
            renderTemplate('views/errors/error.php', [
                'message' => ['type' => 'error', 'text' => 'Could not delete GRN. Stock rollback may have failed; try again or contact support.'],
            ], 'Error');
            return;
        }
        header('Location: ?page=stock_transfer_grns&action=list&transfer_id=' . $tid);
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
        $receivedBy = isset($data['received_by']) ? (int)$data['received_by'] : 0;
        $receivedDate = isset($data['received_date']) ? trim((string)$data['received_date']) : '';
        $remarks = isset($data['grn_remarks']) ? trim((string)$data['grn_remarks'])
            : (isset($data['remarks']) ? trim((string)$data['remarks']) : '');
        $warehouseId = isset($data['warehouse_id']) ? (int)$data['warehouse_id'] : 0;

        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];

        if ($transferId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid transfer ID']);
            return;
        }

        $transfer = $this->stockTransferModel->getTransferById($transferId);
        if (!$transfer) {
            echo json_encode(['success' => false, 'message' => 'Stock transfer not found']);
            return;
        }

        $toWarehouseId = (int)($transfer['to_warehouse'] ?? 0);

        if ($receivedDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $receivedDate)) {
            echo json_encode(['success' => false, 'message' => 'Please enter a valid received date']);
            return;
        }

        if (!empty($transfer['dispatch_date'])) {
            $dispatchTs = strtotime((string)$transfer['dispatch_date']);
            if ($dispatchTs !== false) {
                $dispatchYmd = date('Y-m-d', $dispatchTs);
                if ($receivedDate < $dispatchYmd) {
                    echo json_encode(['success' => false, 'message' => 'Received date cannot be before the transfer dispatch date']);
                    return;
                }
            }
        }

        if ($warehouseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select warehouse']);
            return;
        }

        if ($toWarehouseId <= 0 || $warehouseId !== $toWarehouseId) {
            echo json_encode(['success' => false, 'message' => 'Receiving warehouse must match the transfer destination']);
            return;
        }

        if ($receivedBy <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select who received the goods']);
            return;
        }
        if (!$this->usersModel->userIsActiveAtWarehouse($receivedBy, $toWarehouseId)) {
            echo json_encode(['success' => false, 'message' => 'Selected user is not assigned to the destination warehouse']);
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
