<?php
require_once 'vendor/autoload.php';
require_once 'models/grns/grn.php';
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/comman/tables.php';
require_once 'models/user/user.php';
require_once 'models/grns/PoGrnStock.php';
require_once 'models/product/product.php';
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
        is_login();
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
        //$purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemByIdNew($poId);
        //print_r($purchaseOrderItems);
        $data['items'] = $purchaseOrderItemsModel->getPurchaseOrderItemFromProduct($poId);
        //print_array($data['items']);
        // fetch exotic addresses 
        $data['exotic_address'] = $commanModel->get_exotic_address();
        $data['users'] = $usersModel->getAllUsers();
        //$data['selectStockStmt'] = $res;
        // render clean for mobile users
        if (isMobile()){
            renderTemplateClean('views/grns/create_grn.php', $data, 'Create Goods Receipt Note');
            return;
        }else{
            renderTemplate('views/grns/create_grn.php', $data, 'Create Goods Receipt Note');
        }
        
       
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
        $warehouseId = isset($_POST['warehouse_id']) ? (int) $_POST['warehouse_id'] : 0;
        if ($warehouseId <= 0) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Please select a warehouse.']);
            return;
        }

        $poNumber = trim((string) ($_POST['po_number'] ?? ''));
        $userId = (int) ($_POST['received_by'] ?? $_SESSION['user']['id'] ?? 0);

        // Begin DB transaction
        try {
            $conn->begin_transaction();

            $poItems = $purchaseOrderItemsModel->getPurchaseOrderItemFromProduct($poId);
            $lastGrnId = 0;
            $vendorSyncLines = [];

            foreach ($poItems as $index => $item) {
                $qtyReceived = isset($qtyReceivedArr[$index]) ? (float) $qtyReceivedArr[$index] : 0;
                if ($qtyReceived <= 0) {
                    continue;
                }

                $itemCode = trim((string) ($item['item_code'] ?? $item['order_number'] ?? ''));
                $size = trim((string) ($item['size'] ?? ''));
                $color = trim((string) ($item['color'] ?? ''));
                $productId = isset($item['product_id']) && $item['product_id'] !== ''
                    ? (int) $item['product_id']
                    : 0;
                $sku = trim((string) ($_POST['sku'][$index] ?? ''));

                $grnId = $grnModel->createGrn([
                    'po_id' => $poId,
                    'po_number' => $poNumber,
                    'sku' => $sku,
                    'qty_received' => $qtyReceived,
                    'qty_acceptable' => $qtyAcceptableArr[$index] ?? 0,
                    'location' => $warehouseId,
                    'received_by' => $userId,
                    'remarks' => $remarksArr[$index] ?? '',
                    'received_date' => $grnDate,
                ]);
                if ($grnId === false) {
                    throw new Exception('Failed to create GRN');
                }
                $lastGrnId = (int) $grnId;

                PoGrnStock::applyGrnReceipt($conn, (int) $grnId, $warehouseId, [
                    'sku' => $sku,
                    'qty' => $qtyReceived,
                    'product_id' => $productId,
                    'item_code' => $itemCode,
                    'size' => $size,
                    'color' => $color,
                ], $userId, $poNumber);

                $vendorSyncLines[] = [
                    'item_code' => $itemCode,
                    'size' => $size,
                    'color' => $color,
                    'qty' => $qtyReceived,
                ];
            }

            if ($lastGrnId <= 0) {
                throw new Exception('Please enter received quantity for at least one item.');
            }

            // Optionally update purchase order status to received 
            if (method_exists($purchaseOrderModel, 'updateStatus')) {
                //$purchaseOrderModel->updateStatus($poId, 'received');
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
            $productModel = new product($conn);
            $vendorNote = $this->syncPoGrnLocalStockToVendor($vendorSyncLines, $productModel);
            $response = ['success' => true, 'grn_id' => $lastGrnId];
            if ($vendorNote !== '') {
                $response['vendor_sync_warning'] = $vendorNote;
            }
            echo json_encode($response);
            return;

        } catch (Exception $e) {
            // rollback and return error
            if (isset($conn)) $conn->rollback();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $e->getMessage(),'try_again' => true]);
            return;
        }
    }
    public function downloadQrCode() {
        is_login();
        global $purchaseOrderModel;
        // fetch po details to create grn
        $poId = $_GET['po_id'] ?? null;
        if (!$poId) {
            renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Purchase Order ID is required to download QR Code.']], 'Error');
            return;
        }

        $purchaseOrder = $purchaseOrderModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Purchase Order not found.']], 'Error');
            return;
        }

        // generate QR code
        $qrCode = new Endroid\QrCode\QrCode(
            data: base_url('?page=grns&action=create&po_id='.$poId),
            size: 400,
            margin: 10
        );

        $writer = new Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);
        $qrPng = $result->getString();

        // Add PO number text on top of QR image using GD if available
        if (function_exists('imagecreatefromstring') && ($qrImg = @imagecreatefromstring($qrPng)) !== false) {
            $qrW = imagesx($qrImg);
            $qrH = imagesy($qrImg);
            $paddingTop = 50; // space for PO number text
            $newH = $qrH + $paddingTop;

            $newImg = imagecreatetruecolor($qrW, $newH);
            // white background
            $white = imagecolorallocate($newImg, 255, 255, 255);
            imagefilledrectangle($newImg, 0, 0, $qrW, $newH, $white);

            // copy QR image down below the text area
            imagecopy($newImg, $qrImg, 0, $paddingTop, 0, 0, $qrW, $qrH);

            // PO number text
            $poText = $purchaseOrder['po_number'] ?? '';
            $black = imagecolorallocate($newImg, 0, 0, 0);

            // Try to use a TTF font from the project's fonts folder, fallback to built-in font
            $fontFile = __DIR__ . '/../fonts/DejaVuSans.ttf';
            $fontSize = 18; // pts

            if (file_exists($fontFile) && function_exists('imagettfbbox')) {
                $bbox = imagettfbbox($fontSize, 0, $fontFile, $poText);
                $textWidth = $bbox[2] - $bbox[0];
                $x = max(0, intval(($qrW - $textWidth) / 2));
                // y - baseline: put baseline around half of paddingTop
                $y = intval(($paddingTop / 2) + ($fontSize / 2));
                imagettftext($newImg, $fontSize, 0, $x, $y, $black, $fontFile, $poText);
            } else {
                // fallback small font
                $font = 5;
                $textWidth = imagefontwidth($font) * strlen($poText);
                $x = max(0, intval(($qrW - $textWidth) / 2));
                $y = intval(($paddingTop - imagefontheight($font)) / 2);
                imagestring($newImg, $font, $x, $y, $poText, $black);
            }

            // send headers and output
            header('Content-Type: image/png');
            header('Content-Disposition: attachment; filename="'.$purchaseOrder['po_number'].'_QRCode.png"');
            imagepng($newImg);

            imagedestroy($newImg);
            imagedestroy($qrImg);
            exit;
        }

        // Fallback: send original QR PNG if GD is unavailable
        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="'.$purchaseOrder['po_number'].'_QRCode.png"');
        echo $qrPng;
        exit;

    }

    /**
     * Push local_stock_delta to vendor API for PO GRN receipt lines.
     *
     * @param list<array{item_code:string,size:string,color:string,qty:float}> $receiptLines
     */
    private function syncPoGrnLocalStockToVendor(array $receiptLines, product $productModel): string
    {
        $byVariant = [];
        foreach ($receiptLines as $line) {
            $itemCode = trim((string) ($line['item_code'] ?? ''));
            $size = trim((string) ($line['size'] ?? ''));
            $color = trim((string) ($line['color'] ?? ''));
            $qty = (float) ($line['qty'] ?? 0);
            if ($itemCode === '' || $qty <= 0) {
                continue;
            }
            $key = strtolower($itemCode) . '|' . $size . '|' . $color;
            if (!isset($byVariant[$key])) {
                $byVariant[$key] = [
                    'item_code' => $itemCode,
                    'size' => $size,
                    'color' => $color,
                    'qty' => 0.0,
                ];
            }
            $byVariant[$key]['qty'] += $qty;
        }

        $failures = [];
        foreach ($byVariant as $variant) {
            $delta = (int) round((float) ($variant['qty'] ?? 0));
            if ($delta <= 0) {
                continue;
            }
            $result = $productModel->applyLocalStockDeltaAndRefreshFromVendorApi(
                $variant['item_code'],
                $delta,
                $variant['size'],
                $variant['color']
            );
            if (empty($result['success'])) {
                $failures[] = $variant['item_code'] . ' — ' . trim((string) ($result['message'] ?? 'vendor local stock sync failed'));
            }
        }

        if ($failures === []) {
            return '';
        }

        $shown = array_slice($failures, 0, 3);
        $suffix = count($failures) > 3 ? ' (and ' . (count($failures) - 3) . ' more)' : '';

        return 'Vendor local stock update issue: ' . implode('; ', $shown) . $suffix . '.';
    }
}
?>