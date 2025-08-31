<?php 
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/order.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';
$purchaseOrdersModel = new PurchaseOrder($conn);
$ordersModel = new Order($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);
global $root_path;
 
class PurchaseOrdersController {
    public function index() {
        is_login();
        global $purchaseOrdersModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        // Fetch all purchase orders
        $purchaseOrders = $purchaseOrdersModel->getAllPurchaseOrders();
    
        $total_orders = count($purchaseOrders);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Paginate orders
        $orders = array_slice($purchaseOrders, $offset, $limit);

        renderTemplate('views/purchase_orders/index.php', [
            'purchaseOrders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page
        ], 'Manage Purchase Orders');
    }
    public function createPurchaseOrder(){
        is_login();
        global $purchaseOrdersModel;
        global $ordersModel;        
        global $vendorsModel;
        global $domain;
        global $usersModel;
        //print_r($_POST);
        $itemIds = isset($_POST['poitem']) ? $_POST['poitem'] : [];
        if (empty($itemIds)) {
            //echo json_encode(['success' => false, 'message' => 'No items selected for Purchase Order.']);
            renderTemplate('views/errors/not_found.php', ['message' => 'No items selected for Purchase Order.'], 'No items selected for Purchase Order');
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
        $data['users'] = $usersModel->getAllUsers();
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
        $orderid = isset($_POST['orderid']) ? $_POST['orderid'] : []; 
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
            'grand_total' => $grand_total,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
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
        //Update order status
        
        $statusupdate = [];
        foreach($orderid as $index=>$id){
           $statusupdate[] = $ordersModel->updateOrderStatus($id, 'processing');
        }
        
        if (!$itemsCreated) {
            echo json_encode(['success' => false, 'message' => 'Failed to create Purchase Order Items.']);
            exit;
        }
        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order created successfully.', 'po_id' => $poId, 'status'=>$statusupdate,'orderid'=>$orderid]);
        exit;


    }
    public function cancelPurchaseOrder() {
        global $purchaseOrdersModel;
        global $ordersModel;

        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }

        // Cancel the purchase order
        $isCancelled = $purchaseOrdersModel->cancelPurchaseOrder($poId);
        if (!$isCancelled) {
            echo json_encode(['success' => false, 'message' => 'Failed to cancel Purchase Order.']);
            exit;
        }

        // Update order status
        $statusUpdate = $ordersModel->updateOrderStatus($poId, 'cancelled');

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order cancelled successfully.', 'status' => $statusUpdate]);
        exit;
    }
    function viewPurchaseOrder(){
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $vendorsModel;
        global $usersModel;
        global $domain;

        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $data = [];
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        $data['purchaseOrder'] = $purchaseOrder;
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
        $data['items'] = $purchaseOrderItems;
        $data['vendors'] = $vendorsModel->getAllVendors();
        //$data['items'] = $purchaseOrdersModel->getAllPurchaseOrderItems();
        $data['domain'] = $domain;
        //print_array($data);
        $data['users'] = $usersModel->getAllUsers();
        renderTemplate('views/purchase_orders/view.php', $data, 'View Purchase Order');

        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $purchaseOrder]);
        exit;
    }
    function editPurchaseOrder() {
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $vendorsModel;
        global $usersModel;
        global $domain;

        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }

        $data = [];
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        $data['purchaseOrder'] = $purchaseOrder;
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
        $data['items'] = $purchaseOrderItems;
        $data['vendors'] = $vendorsModel->getAllVendors();
        //$data['items'] = $purchaseOrdersModel->getAllPurchaseOrderItems();
        $data['domain'] = $domain;
        //print_array($data);
        $data['users'] = $usersModel->getAllUsers();
        renderTemplate('views/purchase_orders/edit.php', $data, 'Edit Purchase Order');

        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $purchaseOrder]);
        exit;
    }
    function updatePurchaseOrder() {
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;

        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $poData = [
            'vendor_id' => $_POST['vendor_id'],
            'user_id' => $_POST['user_id'],
            'expected_delivery_date' => $_POST['delivery_due_date'],
            'delivery_address' => $_POST['delivery_address'],
            'total_gst' => $_POST['total_gst'],
            'grand_total' => $_POST['grand_total'],
            'subtotal' => $_POST['subtotal'],
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
        ];
        // Update the purchase order
        $isUpdated = $purchaseOrdersModel->updatePurchaseOrder($poId, $poData);
        if (!$isUpdated) {
            echo json_encode(['success' => false, 'message' => 'Failed to update Purchase Order.']);
            exit;
        }
        $gst = isset($_POST['gst']) ? $_POST['gst'] : [];
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $amount = isset($_POST['amount']) ? $_POST['amount'] : [];
        $price = isset($_POST['price']) ? $_POST['price'] : [];
        // Update purchase order items
        $itemsUpdated = true;
        foreach ($gst as $index => $gstValue) {
            $items = [
                'purchase_orders_id' => $poId,
                'title' => isset($data['title'][$index]) ? $data['title'][$index] : '',
                'hsn' => isset($data['hsn'][$index]) ? $data['hsn'][$index] : '',
                'gst' => $gstValue,
                'quantity' => isset($quantity[$index]) ? $quantity[$index] : 0,
                'price' => isset($price[$index]) ? $price[$index] : 0,
                'amount' => isset($amount[$index]) ? $amount[$index] * (1 + ($gstValue / 100)) : 0,
                
            ];
            $id = isset($_POST['item_ids'][$index]) ? $_POST['item_ids'][$index] : 0;
            $itemId = $purchaseOrderItemsModel->updatePurchaseOrderItems($id, $items);
            if (!$itemId) {
                $itemsUpdated = false;
                break; // Stop processing if any item creation fails
            }
        }

        if (!$itemsUpdated) {
            echo json_encode(['success' => false, 'message' => 'Failed to update Purchase Order items.']);
            exit;
        }

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully.']);
        exit;
    }

}