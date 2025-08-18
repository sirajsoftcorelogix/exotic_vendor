<?php 
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/order.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/vendor/vendor.php';
$purchaseOrdersModel = new PurchaseOrder($conn);
$ordersModel = new Order($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
$vendorsModel = new Vendor($conn);
global $root_path;
 
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
        global $vendorsModel;
        global $domain;
        //print_r($_POST);
        $itemIds = isset($_POST['poitem']) ? $_POST['poitem'] : [];
        if (empty($itemIds)) {
            echo json_encode(['success' => false, 'message' => 'No items selected for Purchase Order.']);
            exit;
        }
        $data = [];
        foreach ($itemIds as $id) {
            $data['data'][] = $ordersModel->getOrderById($id);            
        }
        $data['vendors'] = $vendorsModel->getAllVendors();
        //$data['items'] = $purchaseOrdersModel->getAllPurchaseOrderItems();
        $data['domain'] = $domain;
        //print_array($data);
        // Render the create purchase order form
        renderTemplate('views/purchase_orders/create.php', $data, 'Create Purchase Order');
        exit;
    }
    public function createPurchaseOrderPost() {        
        global $purchaseOrderItemsModel;
        global $purchaseOrdersModel;
        global $ordersModel;
        // Validate and process the form submission
        $vendor = isset($_POST['vendor']) ? $_POST['vendor'] : '';
        $deliveryDueDate = isset($_POST['delivery_due_date']) ? $_POST['delivery_due_date'] : '';
        $deliveryAddress = isset($_POST['delivery_address']) ? $_POST['delivery_address'] : '';
        $total_gst = isset($_POST['total_gst']) ? $_POST['total_gst'] : [];
        //$quantity = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        //$amount = isset($_POST['amount']) ? $_POST['amount'] : [];
        //$total = isset($_POST['total']) ? $_POST['total'] : 0;
        $grand_total = isset($_POST['grand_total']) ? $_POST['grand_total'] : 0;
        $gst = isset($_POST['gst']) ? $_POST['gst'] : []; 
        $data = isset($_POST) ? $_POST : [];      

        if (empty($vendor) || empty($deliveryDueDate) || empty($deliveryAddress) || empty($total_gst)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        // Create the purchase order  
        $poData = [
            'po_number' => 'PO-' . time(), // Example PO number, you can customize this
            'vendor_id' => $vendor,
            'expected_delivery_date' => $deliveryDueDate,
            'delivery_address' => $deliveryAddress,
            'total_gst' => $total_gst,
            'grand_total' => $grand_total
        ];
        $poId = $purchaseOrdersModel->createPurchaseOrder($poData);
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create Purchase Order.']);
            exit;
        }
        // Create purchase order items
        $itemsCreated = true;
        foreach ($gst as $index => $gstValue) {
            $items = [
                'purchase_orders_id' => $poId,
                'title' => isset($data['title'][$index]) ? $data['title'][$index] : '',
                'hsn' => isset($data['hsn'][$index]) ? $data['hsn'][$index] : '',
                'gst' => $gstValue,
                'quantity' => isset($quantity[$index]) ? $quantity[$index] : 0,
                'price' => isset($amount[$index]) ? $amount[$index] : 0,
                'amount' => isset($amount[$index]) ? $amount[$index] * (1 + ($gstValue / 100)) : 0
            ];
            $itemId = $purchaseOrderItemsModel->createPurchaseOrderItem($items);
            if (!$itemId) {
                $itemsCreated = false;
                break; // Stop processing if any item creation fails
            }
        }
        if (!$itemsCreated) {
            echo json_encode(['success' => false, 'message' => 'Failed to create Purchase Order Items.']);
            exit;
        }
        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order created successfully.', 'po_id' => $poId]);
        exit;


    }
    

}