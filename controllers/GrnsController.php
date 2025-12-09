<?php
require_once 'models/grns/grn.php';
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/comman/tables.php';
require_once 'models/user/user.php';
$grnModel = new grn($conn);
$purchaseOrderModel = new PurchaseOrder($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
$commanModel = new Tables($conn);
$usersModel = new User($conn);

class GrnsController {

    public function viewGrn($id) {
        global $grnModel;
        $grnDetails = $grnModel->getGrnDetails($id);
        if (!$grnDetails) {
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'error','text'=>'GRN not found.']], 'GRN Not Found');
            return;
        }
        renderTemplateClean('views/products/grn.php', ['grn' => $grnDetails], 'Goods Receipt Note');
    }

    public function createGrn() {
        global $grnModel;
        global $purchaseOrderModel;
        global $purchaseOrderItemsModel;
        global $conn;
        global $commanModel;
        global $usersModel;
        // fetch po details to create grn
        $poId = $_GET['po_id'] ?? null;
        if (!$poId) {
            renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Purchase Order ID is required to create GRN.']], 'Error');
            return;
        }

        $data = [];
        $purchaseOrder = $purchaseOrderModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Purchase Order not found.']], 'Error');
            return;
        }
        $data['purchaseOrder'] = $purchaseOrder;
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemByIdNew($poId);
        $data['items'] = $purchaseOrderItems;
        // fetch exotic addresses 
        $data['exotic_address'] = $commanModel->get_exotic_address();
        $data['users'] = $usersModel->getAllUsers();
        //$data['selectStockStmt'] = $res;
        // render clean for mobile users
        if (isMobile())
        renderTemplateClean('views/grns/create_grn.php', $data, 'Create Goods Receipt Note');
        renderTemplate('views/grns/create_grn.php', $data, 'Create Goods Receipt Note');
    }

    public function createGrnPost() {
        is_login();
        global $grnModel;
        global $purchaseOrderModel;
        global $purchaseOrderItemsModel;
        global $conn;

        // Expecting purchase_order_id in POST or GET
        $poId = isset($_POST['purchase_order_id']) ? intval($_POST['purchase_order_id']) : (isset($_GET['po_id']) ? intval($_GET['po_id']) : 0);
        if (!$poId) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Purchase Order ID is required.']);
            return;
        }

        // grn date (received date) - accept common field names or default to today
        $grnDate = $_POST['received_date'] ?? $_POST['grn_date'] ?? date('Y-m-d');
        $d = date_create_from_format('Y-m-d', $grnDate);
        if (!$d) {
            $timestamp = strtotime($grnDate);
            if ($timestamp === false) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid GRN date provided.']);
                return;
            }
            $grnDate = date('Y-m-d', $timestamp);
        } else {
            $grnDate = $d->format('Y-m-d');
        }

        // quantities received per item (arrays) and optional warehouse
        $qtyReceivedArr = $_POST['qty_received'] ?? [];
        $remarksArr = $_POST['remarks'] ?? [];
        $qtyAcceptableArr = $_POST['qty_acceptable'] ?? [];
        //$warehouseIdArr = $_POST['warehouse_id'] ?? []; // default warehouse id
        $warehouseId = $_POST['warehouse_id'] ?? 0;

        // Begin DB transaction
        try {
            $conn->begin_transaction();

           

            // Fetch PO items to update stock
            $poItems = $purchaseOrderItemsModel->getPurchaseOrderItemByIdNew($poId);

            // Prepare statements for stock and movements
            $selectStockSql = "SELECT id, current_stock FROM vp_stock WHERE item_code = ? AND COALESCE(size,'') = COALESCE(?, '') AND COALESCE(color,'') = COALESCE(?, '') AND warehouse_id = ? LIMIT 1";
            $selectStockStmt = $conn->prepare($selectStockSql);

            $updateStockSql = "UPDATE vp_stock SET current_stock = ? WHERE id = ?";
            $updateStockStmt = $conn->prepare($updateStockSql);

            $insertStockSql = "INSERT INTO vp_stock (item_code, size, color, warehouse_id, current_stock) VALUES (?, ?, ?, ?, ?)";
            $insertStockStmt = $conn->prepare($insertStockSql);

            $insertMovementSql = "INSERT INTO vp_stock_movements (product_id, item_code, size, color, warehouse_id, movement_type, quantity, running_stock, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $insertMovementStmt = $conn->prepare($insertMovementSql);

            foreach ($poItems as $index => $item) {
                $qtyReceived = isset($qtyReceivedArr[$index]) ? floatval($qtyReceivedArr[$index]) : 0;
                if ($qtyReceived <= 0) continue; // nothing to receive for this item
                //$warehouseId = isset($warehouseIdArr[$index]) ? intval($warehouseIdArr[$index]) : 0;
                $itemCode = $item['item_code'] ?? $item['order_number'] ?? '';
                $size = $item['size'] ?? '';
                $color = $item['color'] ?? '';
                $productId = isset($item['product_id']) && $item['product_id'] !== '' ? intval($item['product_id']) : null;
                 // create GRN
                $data = [];
                $data = [
                    'po_id' => $poId,
                    'po_number' => $_POST['po_number'] ?? '',
                    'item_code' => $itemCode,
                    'color' => $color,
                    'size' => $size,
                    'qty_received' => $qtyReceived,
                    'qty_acceptable' => $qtyAcceptableArr[$index] ?? 0,
                    'location' => $warehouseId,
                    'received_by' => $_POST['received_by'] ?? $_SESSION['user']['id'] ?? 0,
                    'remarks' => $remarksArr[$index] ?? '',
                    'received_date' => $grnDate
                ];
                $grnId = $grnModel->createGrn($data);
                if ($grnId === false) {
                    throw new Exception('Failed to create GRN');
                }
                // ensure select statement prepared
                $selectStockStmt->bind_param('sssi', $itemCode, $size, $color, $warehouseId);
                $selectStockStmt->execute();
                $res = $selectStockStmt->get_result();
                $runningStock = 0;
                if ($row = $res->fetch_assoc()) {
                    $stockId = $row['id'];
                    $currentStock = floatval($row['current_stock']);
                    $runningStock = $currentStock + $qtyReceived;
                    // update stock
                    $updateStockStmt->bind_param('di', $runningStock, $stockId);
                    if (!$updateStockStmt->execute()) {
                        throw new Exception('Failed to update vp_stock for item ' . $itemCode);
                    }
                } else {
                    // insert stock record
                    $runningStock = $qtyReceived;
                    $insertStockStmt->bind_param('sssid', $itemCode, $size, $color, $warehouseId, $runningStock);
                    if (!$insertStockStmt->execute()) {
                        throw new Exception('Failed to insert vp_stock for item ' . $itemCode);
                    }
                    $stockId = $conn->insert_id;
                }

                // insert stock movement (IN)
                $movementType = 'IN';
                $pidBind = $productId ? $productId : 0;
                $insertMovementStmt->bind_param('isssisdd', $pidBind, $itemCode, $size, $color, $warehouseId, $movementType, $qtyReceived, $runningStock);
                if (!$insertMovementStmt->execute()) {
                    throw new Exception('Failed to insert vp_stock_movements for item ' . $itemCode);
                }
            }

            // Optionally update purchase order status to received
            if (method_exists($purchaseOrderModel, 'updateStatus')) {
                $purchaseOrderModel->updateStatus($poId, 'received');
            }

            // commit transaction
            $conn->commit();

            //image upload handling can be added here
            if (isset($_FILES['grn_file']) && !empty($_FILES['grn_file']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/grn_files/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                foreach ($_FILES['grn_file']['tmp_name'] as $key => $tmpName) {
                    $originalName = basename($_FILES['grn_file']['name'][$key]);
                    $targetFile = $uploadDir . $originalName;

                    if (move_uploaded_file($tmpName, $targetFile)) {
                        // Optionally, save file info to vp_grn_files database here
                        $grnModel->uploadGrnFile($poId, 'uploads/grn_files/' . $originalName);
                    } else {
                        throw new Exception('Failed to upload file: ' . $originalName);
                    }
                }
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'grn_id' => $grnId]);
            return;

        } catch (Exception $e) {
            // rollback and return error
            if (isset($conn)) $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage(),'try_again' => true]);
            return;
        }
    }
}
?>