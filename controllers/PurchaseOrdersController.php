<?php 
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/order.php';
$purchaseOrdersModel = new PurchaseOrder($conn);
$ordersModel = new Order($conn);
global $root_path;
global $domain; 
class PurchaseOrdersController {
    public function index() {
        global $purchaseOrdersModel;
        // Fetch all purchase orders
        $purchaseOrders = $purchaseOrdersModel->getAllPurchaseOrders();
        renderTemplate('views/purchase_orders/index.php', ['purchaseOrders' => $purchaseOrders], 'Manage Purchase Orders');
    }
    public function createPurchaseOrder(){
        global $purchaseOrdersModel;
        global $ordersModel;
        //print_r($_POST);
        $itemIds = isset($_POST['poitem']) ? $_POST['poitem'] : [];
        if (empty($itemIds)) {
            echo json_encode(['success' => false, 'message' => 'No items selected for Purchase Order.']);
            exit;
        }
        $data = [];
        foreach ($itemIds as $id) {
            $data[] = $ordersModel->getOrderById($id);            
        }
        //print_array($data);
        // Render the create purchase order form
        renderTemplate('views/purchase_orders/create.php', $data, 'Create Purchase Order');
        exit;
    }  

}

