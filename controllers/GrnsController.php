<?php
require_once 'models/grns/grn.php';
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/purchaseOrderItem.php';
$grnModel = new grn($conn);
$purchaseOrderModel = new PurchaseOrder($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
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
        // fetch po details to create grn
        // print_array($_GET);
        // exit;
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
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
        $data['items'] = $purchaseOrderItems;

        renderTemplate('views/grns/create_grn.php', $data, 'Create Goods Receipt Note');
    }
}
?>