<?php 
require 'vendor/autoload.php';

require_once 'models/order/purchaseOrder.php';
require_once 'models/order/order.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/order/po_invoice.php';
require_once 'models/product/product.php';
require_once 'models/grns/grn.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//use Endroid\QrCode\QrCode;
//use Endroid\QrCode\Writer\PngWriter;


$purchaseOrdersModel = new PurchaseOrder($conn);
$ordersModel = new Order($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);
$poInvoiceModel = new POInvoice($conn);
$productModel = new Product($conn);
$grnModel = new grn($conn);
global $root_path;
 
class PurchaseOrdersController {
    public function index() {
        is_login();
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        // Apply filters
        $filters = [];
        // if (!empty($_GET['search_text'])) {
        //     $filters['search_text'] = $_GET['search_text'];
        // }
        if (!empty($_GET['status'])) {
            $filters['status_filter'] = $_GET['status'];
        }
        if(!empty($_GET['po_from']) && !empty($_GET['po_to'])){
            $filters['po_from'] = $_GET['po_from'];
            $filters['po_to'] = $_GET['po_to'];
        }
        if (!empty($_GET['item_code'])) {
            $filters['item_code'] = $_GET['item_code'];
        }
        if (!empty($_GET['item_category'])) {
            $filters['item_category'] = $_GET['item_category'];
        }
        if (!empty($_GET['item_sub_category'])) {
            $filters['item_sub_category'] = $_GET['item_sub_category'];
        }
        if (!empty($_GET['po_amount_from']) && !empty($_GET['po_amount_to'])) {
            $filters['po_amount_from'] = $_GET['po_amount_from'];
            $filters['po_amount_to'] = $_GET['po_amount_to'];
        }
        if (!empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        if (!empty($_GET['vendor_name'])) {
            $filters['vendor_name'] = $_GET['vendor_name'];
        }
        if (!empty($_GET['due_date'])) {
            $filters['due_date'] = $_GET['due_date'];
        }
        if (!empty($_GET['po_type'])) {
            $filters['po_type'] = $_GET['po_type'];
        }else{
            $filters['po_type'] = 'normal';
        }
        // Fetch all purchase orders
        $purchaseOrders = $purchaseOrdersModel->getAllPurchaseOrders($filters);
       
        // Calculate total pages
        $total_orders = count($purchaseOrders);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Paginate orders
        $orders = array_slice($purchaseOrders, $offset, $limit);
        //get order items for each purchase order
        foreach ($orders as &$order) {
            $order['items'] = $purchaseOrderItemsModel->getPurchaseOrderItemById($order['id']);
        }
        //print_array($orders);
        renderTemplate('views/purchase_orders/index.php', [
            'purchaseOrders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'search' => $_GET['search_text'] ?? '',
            'status_filter' => $_GET['status_filter'] ?? '',
        ], 'Manage Purchase Orders');
    }
    public function stockPurchase() {
        is_login();
        global $purchaseOrdersModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        // Apply filters
        $filters = [];
        // if (!empty($_GET['search_text'])) {
        //     $filters['search_text'] = $_GET['search_text'];
        // }
        if (!empty($_GET['status'])) {
            $filters['status_filter'] = $_GET['status'];
        }
        if(!empty($_GET['po_from']) && !empty($_GET['po_to'])){
            $filters['po_from'] = $_GET['po_from'];
            $filters['po_to'] = $_GET['po_to'];
        }
        if (!empty($_GET['item_code'])) {
            $filters['item_code'] = $_GET['item_code'];
        }
        if (!empty($_GET['item_category'])) {
            $filters['item_category'] = $_GET['item_category'];
        }
        if (!empty($_GET['item_sub_category'])) {
            $filters['item_sub_category'] = $_GET['item_sub_category'];
        }
        if (!empty($_GET['po_amount_from']) && !empty($_GET['po_amount_to'])) {
            $filters['po_amount_from'] = $_GET['po_amount_from'];
            $filters['po_amount_to'] = $_GET['po_amount_to'];
        }
        if (!empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        if (!empty($_GET['vendor_name'])) {
            $filters['vendor_name'] = $_GET['vendor_name'];
        }
        if (!empty($_GET['due_date'])) {
            $filters['due_date'] = $_GET['due_date'];
        }
        if (!empty($_GET['po_type'])) {
            $filters['po_type'] = $_GET['po_type'];
        }else{
            $filters['po_type'] = 'custom';
        }
        // Fetch all purchase orders
        $purchaseOrders = $purchaseOrdersModel->getAllPurchaseOrders($filters);
        // Calculate total pages
        $total_orders = count($purchaseOrders);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Paginate orders
        $orders = array_slice($purchaseOrders, $offset, $limit);

        renderTemplate('views/stock_purchase/index.php', [
            'purchaseOrders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'search' => $_GET['search_text'] ?? '',
            'status_filter' => $_GET['status_filter'] ?? '',
        ], 'Manage Purchase Orders');
    }
    public function createPurchaseOrder(){
        is_login();
        global $purchaseOrdersModel;
        global $ordersModel;        
        global $vendorsModel;
        global $domain;
        global $usersModel;
        global $commanModel;
        //print_r($_POST);
        $itemIds = isset($_POST['poitem']) ? $_POST['poitem'] : [];
        if (empty($itemIds)) {
            if(isset($_SESSION['poitem']) && !empty($_SESSION['poitem'])){
                $itemIds = $_SESSION['poitem'];
            }else{
                // No items selected, handle the error (e.g., redirect back with an error message)
                // For simplicity, we'll just exit here
                //echo json_encode(['success' => false, 'message' => 'No items selected for Purchase Order.']);
                renderTemplate('views/errors/not_found.php', ['message' => 'No items selected for Purchase Order.'], 'No items selected for Purchase Order');
                exit;
            }
            
        }
        if(!empty($itemIds)){
            $_SESSION['poitem'] = $itemIds;
        }
        //check if any of the selected orders is already in a purchase order
        foreach ($itemIds as $id) {
            $order = $ordersModel->getOrderById($id);
            //print_array($order);
            if ($order && $order['po_id'] > 0) {
                renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'One or more selected orders are already included in a Purchase Order.']], 'Error');
                exit;
            }
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
        $data['exotic_address'] = $commanModel->get_exotic_address();
        $data['templates'] = $commanModel->get_payment_terms_and_conditions();
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
        $vendor = isset($_POST['vendor_id']) ? $_POST['vendor_id'] : '';
        $user_id = isset($_POST['user_id']) ? $_POST['user_id'] : '';
        $deliveryDueDate = isset($_POST['delivery_due_date']) ? $_POST['delivery_due_date'] : '';
        $deliveryAddress = isset($_POST['delivery_address']) ? $_POST['delivery_address'] : '';
        $total_gst = isset($_POST['total_gst']) ? $_POST['total_gst'] : [];        
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $rate = isset($_POST['rate']) ? $_POST['rate'] : [];
        //$total = isset($_POST['total']) ? $_POST['total'] : 0;
        $grand_total = isset($_POST['grand_total']) ? $_POST['grand_total'] : 0;
        $subtotal = isset($_POST['subtotal']) ? $_POST['subtotal'] : 0;
        $shipping_cost = isset($_POST['shipping_cost']) ? $_POST['shipping_cost'] : 0;
        $gst = isset($_POST['gst']) ? $_POST['gst'] : [];
        $orderid = isset($_POST['orderid']) ? $_POST['orderid'] : []; 
        $data = isset($_POST) ? $_POST : [];  
        $terms_and_conditions = isset($_POST['terms_and_conditions']) ? $_POST['terms_and_conditions'] : '';
        $status = isset($_POST['status']) ? $_POST['status'] : 'pending';
        $item_code = isset($_POST['item_code']) ? $_POST['item_code'] : [];
        $sku = isset($_POST['sku']) ? $_POST['sku'] : [];

        // print_array($_POST);
        //     exit;
        if (empty($vendor) || empty($deliveryDueDate) || empty($deliveryAddress) || empty($total_gst) || empty($user_id)) {
            echo json_encode(['success' => false, 'message' => 'All fields are required.']);
            exit;
        }
        // Create the purchase order  
        $poData = [
            'po_number' => '', // Example PO number, you can customize this
            'vendor_id' => $vendor,
            'user_id' => $user_id,
            'expected_delivery_date' => $deliveryDueDate,
            'delivery_address' => $deliveryAddress,
            'total_gst' => $total_gst,
            'grand_total' => $grand_total,
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping_cost,
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
            'terms_and_conditions' => $terms_and_conditions,
            'status' => $status,
        ];
        $poId = $purchaseOrdersModel->createPurchaseOrder($poData);
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create Purchase Order.']);
            exit;
        }
        // Generate unique po_number
        $po_number = 'PO-' . date('Y') . '-' . str_pad($poId, 6, '0', STR_PAD_LEFT);

        // Update the purchase order with po_number
        $purchaseOrdersModel->updatePurchaseOrderNumber($poId, ['po_number' => $po_number]);

        // Create purchase order items
        $itemsCreated = true;
        foreach ($gst as $index => $gstValue) {
            $items = [
                'purchase_orders_id' => $poId,
                'order_number' => isset($data['ordernumber'][$index]) ? $data['ordernumber'][$index] : '',
                'title' => isset($data['title'][$index]) ? $data['title'][$index] : '',
                'image' => isset($data['img'][$index]) ? $data['img'][$index] : '',
                'hsn' => isset($data['hsn'][$index]) ? $data['hsn'][$index] : '',
                'gst' => $gstValue,
                'quantity' => isset($quantity[$index]) ? $quantity[$index] : 0,
                'price' => isset($rate[$index]) ? $rate[$index] : 0,
                'amount' => isset($rate[$index]) ? $rate[$index] * (1 + ($gstValue / 100)) * (isset($quantity[$index]) ? $quantity[$index] : 0) : 0,
                'item_code' => isset($item_code[$index]) ? $item_code[$index] : '',
                'size' => isset($data['size'][$index]) ? $data['size'][$index] : '',
                'color' => isset($data['color'][$index]) ? $data['color'][$index] : '',
                'sku' => isset($sku[$index]) ? $sku[$index] : '',
            ];
            //Print_array($items);
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

        //Update order status        
        $statusupdate = [];
        foreach($orderid as $index=>$id){
           $statusupdate[] = $ordersModel->updateOrderStatus($id, 'processing', $po_number, $poId, $deliveryDueDate);
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
        is_login();
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $vendorsModel;
        global $usersModel;
        global $poInvoiceModel;
        global $domain;
        global $commanModel;
        global $grnModel;

        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;

        if (!$poId) {
            //echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            renderTemplate('views/errors/not_found.php', ['message' => 'Invalid Purchase Order ID.'], 'Invalid Purchase Order ID');
            exit;
        }
        $data = [];
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            renderTemplate('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Purchase Order not found.']], 'Error');
            return;
        }
        $data['purchaseOrder'] = $purchaseOrder;
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
        $data['items'] = $purchaseOrderItems;
        $data['vendors'] = $vendorsModel->getAllVendors();
        //$data['items'] = $purchaseOrdersModel->getAllPurchaseOrderItems();
        $data['domain'] = $domain;
        //print_array($purchaseOrder);        
        $data['invoice'] = $poInvoiceModel->getInvoiceByPOId($poId,'invoice');
        $data['proforma'] = $poInvoiceModel->getInvoiceByPOId($poId,'performa');
        $data['status_log'] = $purchaseOrdersModel->get_po_status_log($poId);
        $data['poId'] = $poId;
        $pmt = $poInvoiceModel->getPaymentsByPoId($poId);
        $data['payment'] = $pmt ?? [];
        //$data['vendor_bank'] = $vendorsModel->getBankDetailsById($purchaseOrder['vendor_id']);        
        $data['vendor_bank'] = $poInvoiceModel->getBankDetailsById($purchaseOrder['vendor_id']);
        $data['total_amount_paid'] = $poInvoiceModel->findTotalAmountPaid($poId);
        $data['challan'] = $poInvoiceModel->getChallanByPoId($poId);
        $data['grns'] = $grnModel->getGrnsByPoId($poId);
        //print_array($data['payment']);
       
        
        $address = $commanModel->get_exotic_address();
        foreach($address as $addr){
            if($addr['id'] == $purchaseOrder['delivery_address']){
                $data['purchaseOrder']['delivery_address'] = $addr['address'];
                break;
            }
        }
        $users = $usersModel->getAllUsers();
        foreach($users as $id => $user){
            if($id == $purchaseOrder['user_id']){
                $data['purchaseOrder']['user_name'] = $user;
                break;
            }
        }   
        $data['users'] = $users;
         //print_array($data);
         // Render the create purchase order form
         //echo json_encode(['success' => true, 'data' => $purchaseOrder]);
         //exit;
        renderTemplate('views/purchase_orders/view.php', $data, 'View Purchase Order');

        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $purchaseOrder]);
        exit;
    }
    function addPayment() {
        global $purchaseOrdersModel;
        global $poInvoiceModel;
        global $vendorsModel;
        
        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
        $invoiceId = isset($_POST['invoice_id']) ? $_POST['invoice_id'] : 0;
        $vendorId = isset($_POST['vendor_id']) ? $_POST['vendor_id'] : 0;
        $paymentDate = isset($_POST['payment_date']) ? $_POST['payment_date'] : '';
        $payment_mode = isset($_POST['payment_mode']) ? $_POST['payment_mode'] : '';
        $paymentNote = isset($_POST['payment_note']) ? $_POST['payment_note'] : '';
        $bankTransactionReferenceNo = isset($_POST['bank_transaction_reference_no']) ? $_POST['bank_transaction_reference_no'] : '';
        $amountPaid = isset($_POST['amount_paid']) ? $_POST['amount_paid'] : 0;
        if (!$bankTransactionReferenceNo || !$paymentDate || !$payment_mode || !$amountPaid) {
            echo json_encode(['success' => false, 'message' => 'All * fields are required.']);
            exit;
        }

        $vendorBankAccountNumber = isset($_POST['vendor_bank_account_number']) ? $_POST['vendor_bank_account_number'] : '';
        $vendorBankAccountName = isset($_POST['vendor_bank_account_name']) ? $_POST['vendor_bank_account_name'] : '';
        $vendorBankName = isset($_POST['vendor_bank_name']) ? $_POST['vendor_bank_name'] : '';
        $vendorBankIfscCode = isset($_POST['vendor_bank_ifsc_code']) ? $_POST['vendor_bank_ifsc_code'] : '';
        $vendorBranchName = isset($_POST['vendor_branch_name']) ? $_POST['vendor_branch_name'] : '';
        if( !$vendorBankAccountNumber || !$vendorBankAccountName || !$vendorBankName || !$vendorBankIfscCode || !$vendorBranchName ) {
           
            echo json_encode(['success' => false, 'message' => 'Vendor bank details are required.']);
            exit;
           
        }
        // if(!$invoiceId){
        //     echo json_encode(['success' => false, 'message' => 'Invoice ID is required.']);
        //     exit;
        // }
        // Add payment to the invoice
        $paymentData = [
            'id' => isset($_POST['id']) ? $_POST['id'] : 0,
            'po_id' => $poId,
            'invoice_id' => $invoiceId,
            'vendor_id' => $vendorId,
            'payment_date' => $paymentDate,
            'payment_mode' => $payment_mode,
            'payment_type' => isset($_POST['payment_type']) ? $_POST['payment_type'] : 'full',
            'payment_note' => $paymentNote,
            'payment_currency' => isset($_POST['payment_currency']) ? $_POST['payment_currency'] : 'INR',
            'vendor_bank_account_number' => $vendorBankAccountNumber,
            'vendor_bank_account_name' => $vendorBankAccountName,
            'vendor_bank_name' => $vendorBankName,
            'vendor_bank_ifsc_code' => $vendorBankIfscCode,
            'vendor_branch_name' => $vendorBranchName,
            'bank_transaction_reference_no' => $bankTransactionReferenceNo,
            'payment_note' => $paymentNote,
            'amount_paid' => $amountPaid,
            'user_id' => $_SESSION['user']['id'] ?? 0,
        ];
        //print_array($paymentData);
        $isAdded = $poInvoiceModel->addPayment($paymentData);
        if (!$isAdded) {
            echo json_encode(['success' => false, 'message' => 'Failed to add payment.']);
            exit;
        }

        // Optionally, update the purchase order status if needed
        //$purchaseOrdersModel->updateStatus($poId, 'partially_paid');

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Payment added successfully.']);
        exit;
    }   
    function getPayments(){
        global $poInvoiceModel;
        $Id = isset($_GET['id']) ? $_GET['id'] : 0;
        if (!$Id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Payment ID.']);
            exit;
        }
        $payments = $poInvoiceModel->getPaymentsById($Id);
        if ($payments === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch payments.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $payments]);
        exit;        
    }
    function removePayment(){
        is_login();       
        global $poInvoiceModel;
        $Id = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;
        if (!$Id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Payment ID.']);
            exit;
        }
        $isDeleted = $poInvoiceModel->deletePayment($Id);
        if (!$isDeleted) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete payment.']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Payment deleted successfully.']);
        exit;        
    }
    function editPurchaseOrder() {
        is_login();
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $vendorsModel;
        global $usersModel;
        global $domain;
        global $commanModel;

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
        $data['deliveryAddresses'] = $commanModel->get_exotic_address();
        $data['templates'] = $commanModel->get_payment_terms_and_conditions();
        renderTemplate('views/purchase_orders/edit.php', $data, 'Edit Purchase Order');

        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $purchaseOrder]);
        exit;
    }
    function updatePurchaseOrderPost() {
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
            'total_cost' => $_POST['grand_total'],
            'subtotal' => $_POST['subtotal'],
            //'shipping_cost' => $_POST['shipping_cost'],
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : '',
            'terms_and_conditions' => isset($_POST['terms_and_conditions']) ? $_POST['terms_and_conditions'] : '',
            'status' => isset($_POST['status']) ? $_POST['status'] : 'pending',
        ];
        
        // Update the purchase order
        $isUpdated = $purchaseOrdersModel->updatePurchaseOrder($poId, $poData);
        if (!$isUpdated) {
            echo json_encode(['success' => false, 'message' => 'Failed to update Purchase Order.']);
            exit;
        }
        //echo $isUpdated;
        $gst = isset($_POST['gst']) ? $_POST['gst'] : [];
        $quantity = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        $amount = isset($_POST['amount']) ? $_POST['amount'] : [];
        $price = isset($_POST['price']) ? $_POST['price'] : [];
        $data = isset($_POST) ? $_POST : [];
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
                'amount' => isset($price[$index]) ? $price[$index] * (1 + ($gstValue / 100)) * (isset($quantity[$index]) ? $quantity[$index] : 0) : 0,
                
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
        //echo 'Itme:'.$itemsUpdated;

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order updated successfully.']);
        exit;
    }
    function viewOrderItems() {
        global $ordersModel;

        $search = isset($_GET['search']) ? $_GET['search'] : 0;

        if (!$search) {
            $orderItems = $ordersModel->getOrderItems('');
        }else{
            $orderItems = $ordersModel->getOrderItems($search);
        }
        if ($orderItems === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch order items.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $orderItems]);
        echo json_encode($orderItems);
        exit;
    }
    public function updateStatus() {
        global $purchaseOrdersModel;
        global $commanModel;
        $po_id = $_POST['po_id'] ?? 0;
        $status = $_POST['status'] ?? '';
        // Validate and update status in DB...
        if (!$po_id || !$status) {
            echo json_encode(['success' => false, 'message' => 'Invalid input.']);
            exit;
        }
        if ($status === 'cancelled') {
            $reason = $_POST['reason'] ?? '';
            if (empty($reason)) {
                echo json_encode(['success' => false, 'message' => 'Cancellation reason is required.']);
                exit;
            }
            // Update cancellation reason in DB
            $purchaseOrdersModel->updateCancellationReason($po_id, $reason);
        }

        $isUpdated = $purchaseOrdersModel->updateStatus($po_id, $status);
        if (!$isUpdated) {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
            exit;
        }
        //log status change
        $logData = [
            'po_id' => $po_id,
            'status' => $status,
            'changed_by' => $_SESSION['user']['id'],
            'change_date' => date('Y-m-d H:i:s')
        ];
        $commanModel->add_po_status_log($logData);

        // Return JSON:
        echo json_encode(['success' => true]);
        exit;
    }
    public function deletePurchaseOrder() {
        global $purchaseOrdersModel;
        global $ordersModel;
        global $purchaseOrderItemsModel;

        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        //check if purchase order is cancelled
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if ($purchaseOrder['status'] != 'cancelled') {
            echo json_encode(['success' => false, 'message' => 'Only cancelled Purchase Orders can be deleted.']);
            exit;
        }
        //delete purchase order items
        $isItemsDeleted = $purchaseOrderItemsModel->deletePurchaseOrderItemsByPOId($poId);
        if (!$isItemsDeleted) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete Purchase Order items.']);
            exit;
        }   
        // Delete the purchase order
        $isDeleted = $purchaseOrdersModel->deletePurchaseOrder($poId);
        if (!$isDeleted) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete Purchase Order.']);
            exit;
        }
        
        // Update order status
        $statusUpdate = $ordersModel->updateOrderStatusByPO($poId, 'pending');

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Purchase Order deleted successfully.', 'status' => $statusUpdate]);
        exit;
    }
    function downloadPurchaseOrder_old() {
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;

        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }

        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }else{
            // Fetch purchase order items
            $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
            //print_array($purchaseOrderItems);
           
            if ($purchaseOrderItems === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch Purchase Order items.']);
                exit;
            }
            $tbody = '';
            foreach ($purchaseOrderItems as $index => $item) {
                $tbody .= '<tr>';
                $tbody .= '<td style="width:5% !important; border:1px solid #000; padding:6px; text-align:center;">' . ($index + 1) . '</td>';
                $tbody .= '<td style="width:30% !important; border:1px solid #000; padding:6px;">';
                $tbody .= '<b>' . htmlspecialchars($item['title']) . ' |</b><br>';                
                $tbody .= '</td>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['hsn']) . '</td>';
                $tbody .= '<td style="width:10% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['quantity']) . '</td>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['price'], 2) . '</td>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['gst']) . '%</td>';
                $tbody .= '<td style="width:16% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['amount'], 2) . '</td>';
                $tbody .= '</tr>';
                
            }
        }
        
        
        // // Generate PDF or any other format for download
        // require_once('vendor/tc/vendor/autoload.php'); // Adjust path if needed

        // // Create new PDF document
        // $pdf = new TCPDF();
        // $pdf->SetCreator('Hedayat Technologies');
        // $pdf->SetAuthor('Exotic India Art Pvt. Ltd.');
        // $pdf->SetTitle('Purchase Order #568217');
        // $pdf->setFont('helvetica', '', 10);

        // Generate PDF or any other format for download
        require_once('vendor/tc/vendor/autoload.php'); // Adjust path if needed

        // Create new PDF document
        //$pdf = new TCPDF();
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Hedayat Technologies');
        $pdf->SetAuthor('Exotic India Art Pvt. Ltd.');
        $pdf->SetTitle('Purchase Order #568217');
        //$pdf->SetFont('notosansdisplay', '', 12);
        $pdf->SetMargins(10, 10, 10);
        $pdf->AddPage();
        $fontname = TCPDF_FONTS::addTTFfont('vendor/tc/fonts/MangalRegular.ttf', 'TrueTypeUnicode', '', 96);
        $pdf->SetFont($fontname, '', 14, '', false);    
        $temphtml = file_get_contents('templates/purchaseOrder/PurchaseOrder.html');
        // Define HTML content
        $html = str_replace(
            ['{{po_number}}', '{{date}}', '{{delivery_due}}', '{{tbody}}', '{{subtotal}}', '{{shipping}}', '{{gst}}', '{{grand_total}}'],
            [$purchaseOrder['po_number'], date('d M Y', strtotime($purchaseOrder['created_at'])), date('d M Y', strtotime($purchaseOrder['expected_delivery_date'])), $tbody, $purchaseOrder['subtotal'], $purchaseOrder['shipping_cost'], $purchaseOrder['total_gst'], $purchaseOrder['total_cost']],
            $temphtml
        );

        // Output the HTML content
        $pdf->writeHTML($html, true, false, true, false, '');
        ob_end_clean();
        // Force download
        $pdf->Output($purchaseOrder['po_number'] . '.pdf', 'D');
        //echo 'Hedyat';
        //echo json_encode(['success' => true, 'message' => 'Purchase Order downloaded successfully.']);
        //exit;
    }
    function downloadPurchaseOrder($generateOnly = 0) {    
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $vendorsModel;
        global $usersModel;

        $design_format = 'smallImageWithPrice';
        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;
        if($generateOnly){
            $poId = $generateOnly;
        }
        //download as design format required
        //print_array($_POST);
        if (isset($_POST['design_format']) && isset($_POST['po_id'])) {
            $design_format = $_POST['design_format'];
            $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
        } 
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }

        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }else{
            // Fetch purchase order items
            $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
            //$purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemFromProduct($poId);
            //$purchaseOrderItems = $purchaseOrderItemsModel->getPoItemByIdProduct($poId);
            //print_array($purchaseOrderItems);
           
            if ($purchaseOrderItems === false) {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch Purchase Order items.']);
                exit;
            }
            
            $user = $usersModel->getUserById($purchaseOrder['user_id']);
            $contactPerson = '';
            if($user){
                $contactPerson = 'Contact Person: '.$user['name'].'<br>';
                $contactPerson .= 'Phone: '.$user['phone'].'<br>';
                $purchaseOrder['created_by'] = $user['name'];
                $purchaseOrder['created_email'] = $user['email'];
            }
            //vendor info
            $vendorInfo = '';
            $vendor = $vendorsModel->getVendorById($purchaseOrder['vendor_id']);
            if($vendor && !empty($vendor['address'])){
                
                $vendorInfo = '<span style="font-size:14px; font-weight:bold;">' . htmlspecialchars($vendor['vendor_name'] ?? '') . '</span><br>';
                $vendorInfo .= htmlspecialchars($vendor['address']); 
                $vendorInfo .= ', '.htmlspecialchars($vendor['city']);               
                $vendorInfo .= ', '.htmlspecialchars($vendor['state']);
                $vendorInfo .= ', '.htmlspecialchars($vendor['country']).' - '.$vendor['postal_code'];
                $vendorInfo .= '<span style="font-size:12px;">';
                if(!empty($vendor['vendor_phone'])){
                    $vendorInfo .= '<br>Phone: '.htmlspecialchars($vendor['vendor_phone']);
                }
                if(!empty($vendor['vendor_email'])){
                    $vendorInfo .= '<br>Email: '.htmlspecialchars($vendor['vendor_email']);
                }
                
                if(!empty($vendor['contact_person'])){
                    $vendorInfo .= '<br>Contact Person: '.htmlspecialchars($vendor['contact_person']);
                }
                if(!empty($vendor['gst_number'])){
                    $vendorInfo .= '<br>GST No.: '.htmlspecialchars($vendor['gst_number']);
                }
                if(!empty($vendor['pan_number'])){
                    $vendorInfo .= '<br>PAN No.: '.htmlspecialchars($vendor['pan_number']);
                }
                $vendorInfo .= '</span>';
            }else{
                $vendorInfo = 'N/A';
            }
            $fontMap = [
                'hindi' => 'noto_devanagari',
                'tamil' => 'noto_tamil',
                'bengali' => 'noto_bengali',
                'gujarati' => 'noto_gujarati',
                'english' => 'sans-serif', // fallback
            ];
            require_once 'Text/LanguageDetect.php'; 
            $detector = new Text_LanguageDetect();
            
            $tbody = ''; $thead= ''; $summary_rows = '';
            foreach ($purchaseOrderItems as $index => $item) {
                $text = explode(':', $item['title']);
                $lang1 = $detector->detectSimple($text[0]); // returns 'hi', 'ta', etc.
                $lang2 = isset($text[1]) ? $detector->detectSimple($text[1]) : 'english';
                $font = $fontMap[$lang1] ?? 'sans-serif'; // fallback if unknown
                $font2 = $fontMap[$lang2] ?? 'sans-serif'; // fallback if unknown
                if($design_format == 'smallImageWithPrice'){ 
                $tbody .= '<tr>';
                $tbody .= '<td style="width:5% !important; border:1px solid #000; padding:6px; text-align:center;">' . ($index + 1) . '</td>';
                $tbody .= '<td style="width:37% !important; border:1px solid #000; padding:6px;">';
                //$tbody .= '<p style="font-family: ' . $font . ';">' . $text[0] . ' | ' . $font . '</p>'.'<p style="font-family: ' . $font2 . ';">' . $text[1] . ' | ' . $lang2 . '</p>';
                $tbody .= '<p>' . htmlspecialchars($item['title'] ?? '') . ' - ' . htmlspecialchars($item['order_number'] ?? '');
                    if (!empty($item['prod_height']) || !empty($item['prod_width']) || !empty($item['prod_length'])) {
                        $tbody .= ' <br> Dimensions: ';
                        if ($item['prod_height'] != '0.00') {
                            $tbody .= ' Height: ' . htmlspecialchars($item['prod_height']);
                        }
                        if ($item['prod_width'] != '0.00') {
                            $tbody .= ' Width: ' . htmlspecialchars($item['prod_width']);
                        }
                        if ($item['prod_length'] != '0.00') {
                            $tbody .= ' Depth: ' . $item['prod_length'] . $item['length_unit'];
                        }
                        if ($item['product_weight'] != '0.00') {
                            $tbody .= ' Weight: ' . $item['product_weight'] . $item['product_weight_unit'];
                        }
                    }
                    /*if (!empty($item['size'])) {
                        $tbody .= ' Size: ' . htmlspecialchars($item['size']);
                    }
                    if (!empty($item['color'])) {
                        $tbody .= ' Color: ' . htmlspecialchars($item['color']);
                    }
                    if (!empty($item['material'])) {
                        $tbody .= ' Material: ' . htmlspecialchars($item['material']);
                    }*/
                $tbody .= '</p>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; text-align:center;"><img src="' . htmlspecialchars($item['image']) . '" style="width:auto; max-height:150px;"></td>';
                $tbody .= '<td style="width:8% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['quantity']) . '</td>';
                //if($item['price'] < 0){                
                $tbody .= '<td style="width:13% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['price'], 2) . '</td>';
                $tbody .= '<td style="width:8% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['gst']) . '%</td>';
                $tbody .= '<td style="width:16% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['amount'], 2) . '</td>';
                //}
                $tbody .= '</tr>';
                
                }else if($design_format == 'largeImageWithoutPrice'){
                    $tbody .= '<tr style="border:1px solid #000;">';
                    $tbody .= '<td style="width:50%; padding:20px; vertical-align:top;">';
                    $tbody .= '<p> <b>Order details:</b> <br><br>' . htmlspecialchars($item['title'] ?? '') . ' <br>';
                    if (!empty($item['prod_height']) || !empty($item['prod_width']) || !empty($item['prod_length'])) {
                        $tbody .= '<b>Dimensions:</b>';
                        if ($item['prod_height'] != '0.00') {
                        $tbody .= '<br> Height:' . $item['prod_height'] ;
                        }
                        if ($item['prod_width'] != '0.00') {
                        $tbody .=  '<br> Width:' . htmlspecialchars($item['prod_width'] ?? '');
                        }
                        if ($item['prod_length'] != '0.00') {
                        $tbody .=   '<br> Depth:' . htmlspecialchars($item['prod_length'] ?? '') . $item['length_unit'].' <br>';
                        }
                        if ($item['product_weight'] != '0.00') {
                        $tbody .= '<b>Weight:</b> ' . htmlspecialchars($item['product_weight']) . $item['product_weight_unit']. '<br>';
                        }
                    }
                    $tbody .= '</p>';
                    $tbody .= '</td>';
                    $tbody .= '<td style="width:50%; padding:20px; border:1px solid #000;">';
                    $tbody .=  '<img src="' . htmlspecialchars($item['image'] ?? '') . '" style="width:auto; max-height:400px;">';
                    $tbody .= '</td>';
                    $tbody .= '</tr>';
                }else if($design_format == 'largeImageWithPrice'){
                    $tbody .= '<tr style="border:1px solid #000;">';
                    $tbody .= '<td style="width:50%; padding:20px; vertical-align:top;">';
                    $tbody .= '<p> <b>Order details:</b> <br><br>' . htmlspecialchars($item['title'] ?? '') . ' <br>';
                    if (!empty($item['prod_height']) || !empty($item['prod_width']) || !empty($item['prod_length'])) {
                        $tbody .= '<b>Dimensions:</b>';
                        if ($item['prod_height'] != '0.00') {
                        $tbody .= '<br> Height:' . $item['prod_height'] ;
                        }
                        if ($item['prod_width'] != '0.00') {
                        $tbody .=  '<br> Width:' . htmlspecialchars($item['prod_width'] ?? '');
                        }
                        if ($item['prod_length'] != '0.00') {
                        $tbody .=   '<br> Depth:' . htmlspecialchars($item['prod_length'] ?? '') . $item['length_unit']. ' <br>';
                        }
                        if ($item['product_weight'] != '0.00') {
                        $tbody .= '<b>Weight:</b> ' . htmlspecialchars($item['product_weight']) . $item['product_weight_unit']. '<br>';
                        }
                    }
                    $tbody .= '</p>';
                    $tbody .= '<hr class="border-t mx-5  border-gray-400">';
                    $tbody .= '<p><br>';
                    $tbody .= '<b>Quantity:</b> ' . htmlspecialchars($item['quantity'] ?? '') . '<br>';
                    $tbody .= '<b>Unit Price:</b> ₹' . number_format($item['price'] ?? 0, 2) . '<br>';
                    $tbody .= '<b>GST:</b> ' . htmlspecialchars($item['gst'] ?? '') . '%<br>';
                    $tbody .= '<b>Amount:</b> ₹' . number_format($item['amount'] ?? 0, 2) . '<br>';
                    $tbody .= '</p>';
                    $tbody .= '</td>';
                    $tbody .= '<td style="width:50%; padding:20px; border:1px solid #000;">';
                    $tbody .=  '<img src="' . htmlspecialchars($item['image'] ?? '') . '" style="width:auto; max-height:400px;">';
                    $tbody .= '</td>';
                    $tbody .= '</tr>';
                } else if($design_format == 'smallImageWithoutPrice'){ 
                $tbody .= '<tr>';
                $tbody .= '<td style="width:10% !important; border:1px solid #000; padding:6px; text-align:center;">' . ($index + 1) . '</td>';
                $tbody .= '<td style="width:50% !important; border:1px solid #000; padding:6px;">';
                //$tbody .= '<p style="font-family: ' . $font . ';">' . $text[0] . ' | ' . $font . '</p>'.'<p style="font-family: ' . $font2 . ';">' . $text[1] . ' | ' . $lang2 . '</p>';
                $tbody .= '<p>' . htmlspecialchars($item['title'] ?? '') . ' - ' . htmlspecialchars($item['order_number'] ?? '');
                if (!empty($item['prod_height']) || !empty($item['prod_width']) || !empty($item['prod_length'])) {
                    $tbody .= ' <br> Dimensions: ';
                    if ($item['prod_height'] != '0.00') {
                        $tbody .= ' Height: ' . htmlspecialchars($item['prod_height']);
                    }
                    if ($item['prod_width'] != '0.00') {
                        $tbody .= ' Width: ' . htmlspecialchars($item['prod_width']);
                    }
                    if ($item['prod_length'] != '0.00') {
                        $tbody .= ' Depth: ' . $item['prod_length'] . $item['length_unit'];
                    }
                    if ($item['product_weight'] != '0.00') {
                        $tbody .= ' Weight: ' . $item['product_weight'] . $item['product_weight_unit'];
                    }
                }
                $tbody .= '</p>';
                $tbody .= '<td style="width:20% !important; border:1px solid #000; text-align:center;"><img src="' . htmlspecialchars($item['image']) . '" style="width:auto; max-height:150px;"></td>';
                $tbody .= '<td style="width:10% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['quantity']) . '</td>';
                
                $tbody .= '</tr>';
                
                }
                
            }
            if($design_format == 'smallImageWithPrice'){
                $summary_rows .= '
                        <table>
                            <tr>
                                <th style="padding:5px 10px; text-align:right; font: size 17px; font-weight:bold;">Subtotal:</th>
                                <td style="padding:5px 10px; text-align:right; font-size:17px;">'.$purchaseOrder['subtotal'].'</td>
                            </tr>
                            <tr>
                                <th style="padding:5px 10px; text-align:right; font-size:17px; font-weight:bold;">Shipping:</th>
                                <td style="padding:5px 10px; text-align:right; font-size:17px;">'.$purchaseOrder['shipping_cost'].'</td>
                            </tr>
                            <tr>
                                <th style="padding:5px 10px; text-align:right; font-size:17px; font-weight:bold;">GST:</th>
                                <td style="padding:5px 10px; text-align:right; font-size:17px;">'.$purchaseOrder['total_gst'].'</td>
                            </tr>
                            <tr>
                                <th style="padding-left:5px; background-color: #495057; color: #fff; font-weight: bold; border-top: 2px solid #000; font-size: 17px;"> Grand Total:</th>
                                <td style="padding:5px 10px; background-color: #495057; color: #fff; font-weight: bold; border-top: 2px solid #000; font-size: 17px;">'.$purchaseOrder['total_cost'].'</td>
                            </tr>
                        </table>
                    ';
            }
        }
       
        //require_once('vendor/autoload.php');
        define('_MPDF_TTFONTPATH',  __DIR__ . '/../templates/fonts/');      

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'default_font_size' => 12,
            'autoScriptToLang' => true,
            'autoLangToFont' => true
        ]);

        $mpdf->fontdata['noto_devanagari'] = [
            'R' => 'NotoSansDevanagari-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        
        $mpdf->fontdata['noto_tamil'] = [
            'R' => 'NotoSansTamil-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        $mpdf->fontdata['noto_bengali'] = [
            'R' => 'NotoSansBengali-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        $mpdf->fontdata['noto_gujarati'] = [
            'R' => 'NotoSansGujarati-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        if($design_format == 'smallImageWithPrice' ){
        
            $thead = '<tr>    
                <th style="width:5% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">#</th>
                <th style="width:37% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:left; font-size:11px;">Description</th>
                <th style="width:13% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">Image</th>
                <th style="width:8% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">Qty</th>
                <th style="width:13% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:right; font-size:11px;">Unit Price</th>
                <th style="width:8% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">GST</th>
                <th style="width:16% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:right; font-size:11px;">Amount</th>
            </tr>';     
        }else if($design_format == 'largeImageWithoutPrice' || $design_format == 'largeImageWithPrice'){
            /*$thead = '<tr>    
                <th style="width:50% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:left; font-size:11px;">Description</th>
                <th style="width:50% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">Image</th>
            </tr>';*/
            $thead = '';
        } else if($design_format == 'smallImageWithoutPrice' ){
        
            $thead = '<tr>    
                <th style="width:10% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">#</th>
                <th style="width:50% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:left; font-size:11px;">Description</th>
                <th style="width:20% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">Image</th>
                <th style="width:10% !important; border:1px solid #000; background-color:#000000 !important; color:#fff; padding:6px; text-align:center; font-size:11px;">Qty</th>                
            </tr>';     
        }
        $term = '<div style="font-size:10px; font-weight:bold; margin-bottom:10px;">Terms & Conditions</div>' . nl2br($purchaseOrder['terms_and_conditions']);
        if(empty($purchaseOrder['terms_and_conditions'])){
            $term = '';
        }
       // Generate QR code for PO number or any URL/text
        /*$result = (new Builder())
        ->data(base_url('?page=grns&action=create&po_id=').$purchaseOrder['po_number']) // or any URL/text
        ->size(200)
        ->margin(10)
        ->build();*/
        // $result = (new \Endroid\QrCode\Builder\Builder())
        // ->withData('https://example.com/po/'.$purchaseOrder['po_number']) // your dynamic URL
        // ->withSize(200)
        // ->withMargin(10)
        // ->build();

        //$qrCode = new QrCode(base_url('?page=grns&action=create&po_id=').$purchaseOrder['po_number']);
        $qrCode = new Endroid\QrCode\QrCode(
            data: base_url('?page=grns&action=create&po_id='.$poId),
            size: 400,
            margin: 10
        );
        //$qrCode->margin(10);

        $writer = new Endroid\QrCode\Writer\PngWriter();
        $result = $writer->write($qrCode);       
        $qrBase64 = base64_encode($result->getString());

        // Step 4: Create <img> tag for embedding in mPDF
        $qrImgTag = '<img src="data:image/png;base64,'.$qrBase64.'" style="width:150px; height:150px;">';        
   
        $temphtml = file_get_contents('templates/purchaseOrder/PurchaseOrder.html');
        
        $html = str_replace(
            ['{{po_number}}', '{{date}}', '{{delivery_due}}','{{thead}}', '{{tbody}}', '{{summary_rows}}', '{{terms}}', '{{vendor_info}}', '{{contact_person}}', '{{qr_code}}'],
            [
                $purchaseOrder['po_number'],
                date('d M Y', strtotime($purchaseOrder['created_at'])),
                date('d M Y', strtotime($purchaseOrder['expected_delivery_date'])),
                $thead,
                $tbody,
                $summary_rows,
                $term,
                $vendorInfo,
                $contactPerson,
                $qrImgTag
            ],
            $temphtml
        );
        //echo $html; exit;
        // $html = '
        //     <h2 style="font-family: sans-serif;">English: Hello World</h2>
        //     <p style="font-family: noto_devanagari;">हिंदी: नमस्ते दुनिया</p>
        //     <p style="font-family: noto_tamil;">தமிழ்: வணக்கம் உலகம்</p>
        //     <p style="font-family: noto_bengali;">বাংলা: হ্যালো ওয়ার্ল্ড</p>
        //     <p style="font-family: noto_gujarati;">ગુજરાતી: હેલો વર્લ્ડ</p>
        //     ';
        $mpdf->WriteHTML($html);
        if($generateOnly){
            $filePath = __DIR__ . '/../generated_pdfs/' . $purchaseOrder['po_number'] . '.pdf';
            $mpdf->Output($filePath, 'F'); // Save the PDF to a file
            return $filePath; // Return the file path for further use
        }else{
            $mpdf->Output($purchaseOrder['po_number'] . '.pdf', 'D');
        }

    }
    function toggleStar() {
        global $purchaseOrdersModel;

        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
       
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }

        // Toggle the star flag
        $isToggled = $purchaseOrdersModel->toggleStar($poId);
        if ($isToggled === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to toggle star flag.']);
            exit;
        }

        // If everything is successful, return success response
        echo json_encode(['success' => true, 'message' => 'Star flag toggled successfully.', 'flag_star' => $isToggled]);
        exit;
    }   
    function emailToVendor() {
        global $purchaseOrdersModel;
        global $vendorsModel;
        
        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID']);
            exit;
        }

        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }

        $vendor = $vendorsModel->getVendorById($purchaseOrder['vendor_id']);
        if (!$vendor) {
            echo json_encode(['success' => false, 'message' => 'Vendor not found.']);
            exit;
        }
        //print_array($vendor);
        // Here you would typically generate the PDF and attach it to the email
        $pdfFilePath = $this->downloadPurchaseOrder($poId); // This will generate the PDF
        $attachments = [];
        
        if (file_exists($pdfFilePath)) {
            $attachments[] = $pdfFilePath;
        }

        // For simplicity, we'll just send a basic email without attachment

        $to = $vendor['vendor_email'];
        $subject = "Purchase Order " . $purchaseOrder['po_number'] . " from Exotic India Art Pvt. Ltd.";
        $message = "Dear " . $vendor['contact_name'] . ",\n\nPlease find attached the Purchase Order " . $purchaseOrder['po_number'] . ".  dated " . date('d-m-Y') . ". \nWe request you to review the order details and confirm acceptance at the earliest.";
        $message .= "\n\n PO Number: " . $purchaseOrder['po_number'];
        $message .= "\n Expected Delivery Date: " . date('d-m-Y', strtotime($purchaseOrder['expected_delivery_date']));
        $message .= "\n Delivery Location: " . $purchaseOrder['delivery_address'];
        $message .= "\n Kindly acknowledge receipt of this PO and share an estimated delivery schedule.\n\nThank you for your continued support.";

        $message .= "\n\n\nBest regards,\nExotic India Art Pvt. Ltd.";
        $headers = "From: onboarding@exoticindia.com";

        // Send the email
        $mail = new PHPMailer(true);
        try {
            //Server settings
            $mail->isSMTP();
            $mail->Host       = smtpHost; // Set the SMTP server to send through
            $mail->SMTPAuth   = true;
            $mail->Username   = smtpUser;
            $mail->Password   = smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = smtpPort;

            //Recipients
            $mail->setFrom('onboarding@exoticindia.com', 'Exotic India Art Pvt. Ltd.');
            $mail->addAddress($to, $vendor['contact_name']);
            // Attachments
            foreach ($attachments as $filePath) {
                $mail->addAttachment($filePath);
            }

            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = nl2br($message);

            $mail->send();
            echo json_encode(['success' => true, 'message' => 'Purchase order emailed to vendor successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to send email to ' . $to . ': ' . $mail->ErrorInfo]);
        }
        exit;
    }
    function uploadInvoice() {
        global $purchaseOrdersModel;
        global $poInvoiceModel;
        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
        $id = isset($_POST['id']) ? $_POST['id'] : 0; // for update

        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        
        
        if (!isset($_FILES['file_input']) || $_FILES['file_input']['error'] !== 0) {
            if(isset($id) && $id){
                //update without file
                $poInvoiceData = [
                    'po_id' => $poId,
                    'invoice_type' => $_POST['invoice_type'] ?? NULL,
                    'invoice_no' => $_POST['invoice_no'] ?? '',
                    'invoice_date' => $_POST['invoice_date'] ?? '',
                    'gst_reg' => $_POST['gst_reg'] ?? 0,
                    'sub_total' => $_POST['sub_total'] ?? 0,
                    'gst_total' => $_POST['gst_total'] ?? 0,
                    'shipping' => empty($_POST['shipping']) ? 0.00 : $_POST['shipping'],
                    'grand_total' => $_POST['grand_total'] ?? 0,
                ];
                //print_array($poInvoiceData);
                $isUpdated = $poInvoiceModel->updateInvoice($id, $poInvoiceData);
                if (!$isUpdated) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update invoice details in database.']);
                    exit;
                }
                echo json_encode(['success' => true, 'message' => 'Invoice updated successfully.']);
                exit;
            }
            echo json_encode(['success' => false, 'message' => 'File upload error.']);
            exit;
        }
        if (!isset($_FILES['file_input']) || $_FILES['file_input']['error'] !== 0) {
            echo json_encode(['success' => false, 'message' => 'File upload error.']);
            exit;
        }
        $uploadDir = __DIR__ . '/../uploads/invoices/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $fileTmpPath = $_FILES['file_input']['tmp_name'];
        $fileName = $_FILES['file_input']['name'];
        $fileSize = $_FILES['file_input']['size'];
        $fileType = $_FILES['file_input']['type'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        // Sanitize file name
        $newFileName = 'PO_' . $poId . '_' . time() . '.' . $fileExtension;

        // Check if file type is allowed (e.g., pdf, jpg, png)
        $allowedfileExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($fileExtension, $allowedfileExtensions)) {
            echo json_encode(['success' => false, 'message' => 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions)]);
            exit;
        }

        $dest_path = $uploadDir . $newFileName;

        if (move_uploaded_file($fileTmpPath, $dest_path)) {
            $invoice = 'uploads/invoices/' . $newFileName;
            $poInvoiceData = [
                'po_id' => $poId,
                'invoice_type' => $_POST['invoice_type'] ?? NULL,
                'invoice_no' => $_POST['invoice_no'] ?? '',
                'invoice_date' => $_POST['invoice_date'] ?? '',
                'gst_reg' => $_POST['gst_reg'] ?? 0,
                'sub_total' => $_POST['sub_total'] ?? 0,
                'gst_total' => $_POST['gst_total'] ?? 0,
                'shipping' => $_POST['shipping'] ?? 0,
                'grand_total' => $_POST['grand_total'] ?? 0,
                'invoice' => $invoice
            ];
            if(isset($_POST['invoice_type']) && $_POST['invoice_type'] == 'invoice'){                
                //update purchase order invoice path
                $isUpdatedInv = $purchaseOrdersModel->updateInvoicePath($poId, 'uploads/invoices/' . $newFileName);
                if (!$isUpdatedInv) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update purchase order invoice path.']);
                    exit;
                }
            }
            
            if(isset($id) && $id){
                //update
                $isUpdated = $poInvoiceModel->updateInvoice($id, $poInvoiceData);
                if (!$isUpdated) {
                    echo json_encode(['success' => false, 'message' => 'Failed to update invoice details in database.']);
                    exit;
                }
                echo json_encode(['success' => true, 'message' => 'Invoice updated successfully.', 'invoice_path' => 'uploads/invoices/' . $newFileName]);
                exit;
            }
            // Save invoice details to database
            $isSaved = $poInvoiceModel->addPoInvoice($poInvoiceData);
            if (!$isSaved) {
                echo json_encode(['success' => false, 'message' => 'Failed to save invoice details to database.']);
                exit;
            }
            
            echo json_encode(['success' => true, 'message' => 'Invoice uploaded successfully', 'invoice_path' => 'uploads/invoices/' . $newFileName]);
        } else {
            echo json_encode(['success' => false, 'message' => 'There was an error moving the uploaded file.']);
        }
    }
    
    public function previewPDF() {
        global $vendorsModel;
        global $usersModel;
        // Collect POST data (from form, not DB)
        $data = $_POST;

        // Prepare variables for template
        $po_number = 'PREVIEW PO'; // or generate a temp number
        $date = date('d M Y');
        $delivery_due = isset($data['delivery_due_date']) ? date('d M Y', strtotime($data['delivery_due_date'])) : '';
        $subtotal = $data['subtotal'] ?? 0;
        $shipping = $data['shipping_cost'] ?? 0;
        $gst = $data['total_gst'] ?? 0;
        $grand_total = $data['grand_total'] ?? 0;
        $terms = '<p>' . nl2br(htmlspecialchars($data['terms_and_conditions'] ?? '')) . '</p>';
        $vendor_id = $data['vendor'] ?? '';
        $user_id = $data['user_id'] ?? '';

        $user = $usersModel->getUserById($user_id);
        $contactPerson = '';
        if($user){
            $contactPerson = 'Contact Person: '.$user['name'].'<br>';
            $contactPerson .= 'Phone: '.$user['phone'].'<br>';
            $purchaseOrder['created_by'] = $user['name'];
            $purchaseOrder['created_email'] = $user['email'];
        }

        // Fetch vendor info
        $vendorInfo = '';
        $vendor = $vendorsModel->getVendorById($vendor_id);
        if($vendor && !empty($vendor['address'])){
            
            $vendorInfo = '<span style="font-size:14px; font-weight:bold;">' . htmlspecialchars($vendor['vendor_name'] ?? '') . '</span><br>';
            $vendorInfo .= htmlspecialchars($vendor['address']);            
            $vendorInfo .= ', '.htmlspecialchars($vendor['city']);               
            $vendorInfo .= ', '.htmlspecialchars($vendor['state']);
            $vendorInfo .= ', '.htmlspecialchars($vendor['country']).' - '.$vendor['postal_code'];
            $vendorInfo .= '<span style="font-size:12px;">';
            if(!empty($vendor['vendor_phone'])){
                $vendorInfo .= '<br>Phone: '.htmlspecialchars($vendor['vendor_phone']);
            }
            if(!empty($vendor['vendor_email'])){
                $vendorInfo .= '<br>Email: '.htmlspecialchars($vendor['vendor_email']);
            }
            // if(!empty($vendor['website'])){
            //     $vendorInfo .= '<br>Website: '.htmlspecialchars($vendor['website']);
            // }
            if(!empty($vendor['contact_person'])){
                $vendorInfo .= '<br>Contact Person: '.htmlspecialchars($vendor['contact_person']);
            }
            if(!empty($vendor['gst_number'])){
                $vendorInfo .= '<br>GST No.: '.htmlspecialchars($vendor['gst_number']);
            }
            if(!empty($vendor['pan_number'])){
                    $vendorInfo .= '<br>PAN No.: '.htmlspecialchars($vendor['pan_number']);
                }
            $vendorInfo .= '</span>';
        }else{
            $vendorInfo = 'N/A';
        }
        // Build tbody from items
        $tbody = '';
        if (!empty($data['title']) && is_array($data['title'])) {
            foreach ($data['title'] as $i => $title) {
                $tbody .= '<tr>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:center;">' . ($i + 1) . '</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px;">' . htmlspecialchars($title) . '</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:center;"><img src="' . htmlspecialchars($data['img'][$i] ?? '') . '" style="width:auto; max-height:150px;"></td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($data['quantity'][$i] ?? '') . '</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:right;">₹' . number_format(isset($data['rate'][$i]) ? $data['rate'][$i] : 0, 2) . '</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($data['gst'][$i] ?? '') . '%</td>';
                // Calculate amount including GST
                $qty = isset($data['quantity'][$i]) ? $data['quantity'][$i] : 0;
                $rate = isset($data['rate'][$i]) ? $data['rate'][$i] : 0;
                $gstValue = isset($data['gst'][$i]) ? $data['gst'][$i] : 0;
                $amount = $qty * $rate * (1 + ($gstValue / 100));
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($amount, 2) . '</td>';
                $tbody .= '</tr>';
            }
        }
        //summary rows can be added similarly if needed
        $summary_rows = '
                        <table>
                            <tr>
                                <th style="padding:5px 10px; text-align:right; font: size 17px; font-weight:bold;">Subtotal:</th>
                                <td style="padding:5px 10px; text-align:right; font-size:17px;">'.$data['subtotal'].'</td>
                            </tr>
                           
                            <tr>
                                <th style="padding:5px 10px; text-align:right; font-size:17px; font-weight:bold;">GST:</th>
                                <td style="padding:5px 10px; text-align:right; font-size:17px;">'.$data['total_gst'].'</td>
                            </tr>
                            <tr>
                                <th style="padding-left:5px; background-color: #495057; color: #fff; font-weight: bold; border-top: 2px solid #000; font-size: 17px;"> Grand Total:</th>
                                <td style="padding:5px 10px; background-color: #495057; color: #fff; font-weight: bold; border-top: 2px solid #000; font-size: 17px;">'.$data['grand_total'].'</td>
                            </tr>
                        </table>
                    ';
            

        // Load template
        $temphtml = file_get_contents('templates/purchaseOrder/PurchaseOrder.html');
        $html = str_replace(
            ['{{po_number}}', '{{date}}', '{{delivery_due}}', '{{tbody}}', '{{summary_rows}}', '{{terms}}', '{{vendor_info}}', '{{contact_person}}','{{qr_code}}'],
            [$po_number, $date, $delivery_due, $tbody,  $summary_rows, $terms, $vendorInfo, $contactPerson, ''],
            $temphtml
        );

        // Generate PDF (using mPDF or TCPDF)
        require_once('vendor/autoload.php');
        //$mpdf = new \Mpdf\Mpdf(['mode' => 'utf-8', 'default_font_size' => 12]);
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'default_font_size' => 12,
            'autoScriptToLang' => true,
            'autoLangToFont' => true
        ]);
        // Add watermark
        $mpdf->SetWatermarkText('PREVIEW PO');
        $mpdf->showWatermarkText = true;

        $mpdf->fontdata['noto_devanagari'] = [
            'R' => 'NotoSansDevanagari-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        
        $mpdf->fontdata['noto_tamil'] = [
            'R' => 'NotoSansTamil-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        $mpdf->fontdata['noto_bengali'] = [
            'R' => 'NotoSansBengali-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        $mpdf->fontdata['noto_gujarati'] = [
            'R' => 'NotoSansGujarati-Regular.ttf',
            'useOTL' => 0xFF,
        ];
        $mpdf->WriteHTML($html);

        // Save to temp file
        $fileName = 'po_preview_' . uniqid() . '.pdf';
        //$filePath = sys_get_temp_dir() . '/' . $fileName;
        $mpdf->Output('tmp/'.$fileName, 'F');

        // Return URL to temp file (make sure your web server can serve from /tmp or move to a public temp folder)
        echo json_encode([
            'success' => true,
            'message' => 'PDF preview generated successfully.',
            'temp_file_path' => 'tmp/' . $fileName,
            'pdf_url' => 'tmp/' . $fileName
        ]);
        exit;
    }
    public function getPoDetails() {
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $poInvoiceModel;
        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;        
        $invoiceType = isset($_GET['invoice_type']) ? $_GET['invoice_type'] : 'invoice'; // default to 'invoice'
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }
        
        $purchaseOrderItems = $purchaseOrderItemsModel->getPurchaseOrderItemById($poId);
        $poInvoice = $poInvoiceModel->getInvoiceByPoId($poId, $invoiceType);
        if ($poInvoice && isset($poInvoice['invoice_date'])) {
        $poInvoice['invoice_date'] = date('Y-m-d', strtotime($poInvoice['invoice_date']));
        }

        echo json_encode(['success' => true, 'data' => [
            'purchaseOrder' => $purchaseOrder,
            'items' => $purchaseOrderItems,
            'invoiceData' => $poInvoice ?? '',
        ]]);
        exit;
    }
    public function deleteInvoice() {
        global $poInvoiceModel;

        $invoiceId = isset($_POST['invoice_id']) ? $_POST['invoice_id'] : 0;

        if (!$invoiceId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Invoice ID.']);
            exit;
        }

        $invoice = $poInvoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            echo json_encode(['success' => false, 'message' => 'Invoice not found.']);
            exit;
        }

        //$invoicePath = __DIR__ . '/../' . ($invoice['invoice_type'] === 'performa' ? $invoice['performa'] : $invoice['invoice']);
        $invoicePath = __DIR__ . '/../' . $invoice['invoice'];
         // Delete the file from the server if it exists
        if (file_exists($invoicePath)) {
            unlink($invoicePath); // Delete the file
        }

        // Delete the invoice record from the database $invoice['invoice_type']
        $isDeleted = $poInvoiceModel->updateFile($invoiceId, 'invoice', '');
        if (!$isDeleted) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete invoice from database.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Invoice deleted successfully.']);
    }
    public function getPO(){
        // echo 'hedayat';
        // print_array($_POST);
        global $purchaseOrdersModel;
        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }
        echo json_encode(['success' => true, 'data' => $purchaseOrder]);
        exit;
    }
    public function addChallan(){
        global $poInvoiceModel;
        global $purchaseOrdersModel;
        
        $poId = isset($_POST['po_id']) ? $_POST['po_id'] : 0;
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $purchaseOrder = $purchaseOrdersModel->getPurchaseOrder($poId);
        if (!$purchaseOrder) {
            echo json_encode(['success' => false, 'message' => 'Purchase Order not found.']);
            exit;
        }
        // Validate required fields
        if (empty($_POST['delivery_challan_no']) || empty($_POST['delivery_challan_date']) || empty($_POST['vendor_id'])) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }
        
        // file upload handling can be added here if needed
        if (!empty($_FILES['delivery_challan_copy']['name'])) {
            if ($_FILES['delivery_challan_copy']['error'] !== 0) {
                echo json_encode(['success' => false, 'message' => 'File upload error.']);
                exit;
            }
            $uploadDir = __DIR__ . '/../uploads/challans/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $fileTmpPath = $_FILES['delivery_challan_copy']['tmp_name'];
            $fileName = $_FILES['delivery_challan_copy']['name'];
            $fileSize = $_FILES['delivery_challan_copy']['size'];
            $fileType = $_FILES['delivery_challan_copy']['type'];
            $fileNameCmps = explode(".", $fileName);
            $fileExtension = strtolower(end($fileNameCmps));

            // Sanitize file name
            $newFileName = 'CHALLAN_' . $poId . '_' . time() . '.' . $fileExtension;

            // Check if file type is allowed (e.g., pdf, jpg, png)
            $allowedfileExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
            if (!in_array($fileExtension, $allowedfileExtensions)) {
                echo json_encode(['success' => false, 'message' => 'Upload failed. Allowed file types: ' . implode(',', $allowedfileExtensions)]);
                exit;
            }
            //size limit can be added here if needed
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($fileSize > $maxFileSize) {
                echo json_encode(['success' => false, 'message' => 'Upload failed. File size exceeds 5MB limit.']);
                exit;
            }

            $dest_path = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                $_POST['delivery_challan_copy'] = 'uploads/challans/' . $newFileName;
            } else {
                echo json_encode(['success' => false, 'message' => 'There was an error moving the uploaded file.']);
                exit;
            }
        } else {
            $_POST['delivery_challan_copy'] = '';
        }

        $challanData = [
            'po_id' => $poId,
            'invoice_id' => $_POST['invoice_id'] ?? 0,
            'delivery_challan_no' => $_POST['delivery_challan_no'] ?? '',
            'delivery_challan_date' => $_POST['delivery_challan_date'] ?? '',
            'mode_of_transport' => $_POST['mode_of_transport'] ?? '',
            'vehicle_no' => $_POST['vehicle_no'] ?? '',
            'transport_purpose' => $_POST['transport_purpose'] ?? '',
            'vendor_id' => $_POST['vendor_id'] ?? 0,
            'delivery_challan_copy' => $_POST['delivery_challan_copy'] ?? '',
            'user_id' => $_SESSION['user_id'] ?? 0
        ];
        //echo $_POST['id'];exit;
        if (!empty($_POST['id'])) {
            // Update existing challan
            $isUpdated = $poInvoiceModel->updateChallan($_POST['id'], $challanData);
            if (!$isUpdated) {
                echo json_encode(['success' => false, 'message' => 'Failed to update challan.']);
                exit;
            }
            echo json_encode(['success' => true, 'message' => 'Challan updated successfully.']);
            exit;
        }
        $isAdded = $poInvoiceModel->addChallan($challanData);
        if (!$isAdded) {
            echo json_encode(['success' => false, 'message' => 'Failed to add challan.']);
            exit;
        }
        echo json_encode(['success' => true, 'message' => 'Challan added successfully.']);
        exit;
    }
    public function getChallans(){
        global $poInvoiceModel;
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $challans = $poInvoiceModel->getChallansById($id);
        echo json_encode(['success' => true, 'data' => $challans]);
        exit;
    }
    public function deleteChallan() {
        global $poInvoiceModel;

        $challanId = isset($_REQUEST['id']) ? $_REQUEST['id'] : 0;

        if (!$challanId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Challan ID.']);
            exit;
        }

        $challan = $poInvoiceModel->getChallansById($challanId);
        if (!$challan) {
            echo json_encode(['success' => false, 'message' => 'Challan not found.']);
            exit;
        }

        $challanPath = __DIR__ . '/../' . $challan[0]['delivery_challan_copy'];
        if (file_exists($challanPath)) {
            unlink($challanPath); // Delete the file
        }

        // Delete the challan record from the database
        $isDeleted = $poInvoiceModel->deleteChallan($challanId);
        if (!$isDeleted) {
            echo json_encode(['success' => false, 'message' => 'Failed to delete challan from database.']);
            exit;
        }

        echo json_encode(['success' => true, 'message' => 'Challan deleted successfully.']);
    }
    public function vendorSearch(){
        global $vendorsModel;
        $term = isset($_GET['query']) ? $_GET['query'] : '';
        $vendors = $vendorsModel->searchVendors($term);
        echo json_encode(['success' => true, 'data' => $vendors]);
        exit;
    }
    public function customPO(){
        global $vendorsModel;
        global $usersModel;
        global $commanModel;
        global $domain;
        global $productModel;
       
        $itemIds = isset($_POST['cpoitem']) ? $_POST['cpoitem'] : [];            
        $data = [];
        foreach ($itemIds as $id) {
            $data['data'][] = $productModel->getProduct($id);            
        }
        //print_array($data['data']);
        //$vendors = $vendorsModel->getAllVendors();
        $data['vendors'] = $vendorsModel->getAllVendors();
        //$data['items'] = $purchaseOrdersModel->getAllPurchaseOrderItems();
        $data['domain'] = $domain;
        //print_array($data);
        $data['users'] = $usersModel->getAllUsers();
        $data['exotic_address'] = $commanModel->get_exotic_address();
        $data['templates'] = $commanModel->get_payment_terms_and_conditions();
        renderTemplate('views/purchase_orders/custom_po.php', $data, 'Create Custom Purchase Order');
    }
    function productItems() {
        global $productModel;

        $search = isset($_GET['search']) ? $_GET['search'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        if($type == 'item_code'){
            $orderItems = $productModel->getProductItemsByCode($search);

        }elseif (!$search) {
            $orderItems = $productModel->getProductItems('');
        }else{
            $orderItems = $productModel->getProductItems($search);
        }
        if ($orderItems === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch order items.']);
            exit;
        }

        //echo json_encode(['success' => true, 'data' => $orderItems]);
        echo json_encode($orderItems);
        exit;
    }
    function customPOSave() {
        global $purchaseOrdersModel;
        global $purchaseOrderItemsModel;
        global $domain;

        $data = $_POST;

        // Validate required fields
        if (empty($data['vendor']) || empty($data['delivery_due_date']) || empty($data['title']) || empty($data['quantity']) || empty($data['rate']) || empty($data['delivery_address'])) {
            echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
            exit;
        }
        
        // Prepare purchase order data
        $purchaseOrderData = [
            'po_number' => '',
            'po_type' => 'custom',
            'vendor_id' => $data['vendor'],
            'expected_delivery_date' => $data['delivery_due_date'],
            'user_id' => $data['user_id'] ?? '',
            'created_email' => $data['created_email'] ?? '',
            'delivery_address' => $data['delivery_address'] ?? '',
            'subtotal' => $data['subtotal'] ?? 0,
            'shipping_cost' => $data['shipping_cost'] ?? 0,
            'total_gst' => $data['total_gst'] ?? 0,
            'total_cost' => $data['grand_total'] ?? 0,
            'terms_and_conditions' => $data['terms_and_conditions'] ?? '',
            'status' => 'pending',
            'flag_star' => 0
        ];
        //print_array($purchaseOrderData);
        // Save purchase order
        $poId = $purchaseOrdersModel->addPurchaseOrder($purchaseOrderData);
        if (!$poId) {
            echo json_encode(['success' => false, 'message' => 'Failed to save purchase order.']);
            exit;
        }
        // Generate and update PO number
        $poNumber = 'CPO-' . date('Y') . '-' . str_pad($poId, 6, '0', STR_PAD_LEFT);
        $isUpdated = $purchaseOrdersModel->updatePurchaseOrderNumber($poId, ['po_number' => $poNumber]);
        if (!$isUpdated) {
            echo json_encode(['success' => false, 'message' => 'Failed to update PO number.']);
            exit;
        }
        //print_array($_FILES);
        // Save purchase order items
         // Create purchase order items
        $itemsCreated = true;
        foreach ($data['gst'] as $index => $gstValue) {            
            // image upload and save logic
            $filesArray = null;
            if (isset($_FILES['image'])) {
                $filesArray = $_FILES['image'];
            } elseif (isset($_FILES['img_files'])) {
                $filesArray = $_FILES['img_files'];
            } elseif (isset($_FILES['img_upload'])) {
                $filesArray = $_FILES['img_upload'];
            } elseif (isset($_FILES['img'])) {
                $filesArray = $_FILES['img'];
            }

            // Ensure upload directory exists
            $uploadDir = __DIR__ . '/../uploads/po_items/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $imgPath = ''; // default if no upload and no existing value
            //echo "Processing item index $index\n";
            // Handle uploaded file for this item index if present
            if ($filesArray) {
                //echo "updoading file for index $index: $fileName\n";
                // support both multiple and single file input structures
                $fileName = is_array($filesArray['name']) ? ($filesArray['name'][$index] ?? '') : ($filesArray['name'] ?? '');
                $fileTmp  = is_array($filesArray['tmp_name']) ? ($filesArray['tmp_name'][$index] ?? '') : ($filesArray['tmp_name'] ?? '');
                $fileErr  = is_array($filesArray['error']) ? ($filesArray['error'][$index] ?? 4) : ($filesArray['error'] ?? 4);
                $fileSize = is_array($filesArray['size']) ? ($filesArray['size'][$index] ?? 0) : ($filesArray['size'] ?? 0);

                if (!empty($fileName) && $fileErr === UPLOAD_ERR_OK && is_uploaded_file($fileTmp)) {
                    $fileNameCmps = explode('.', $fileName);
                    $fileExtension = strtolower(end($fileNameCmps));
                    $allowedfileExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                    
                    if (in_array($fileExtension, $allowedfileExtensions)) {
                        $newFileName = 'POITEM_' . $poId . '_' . $index . '_' . time() . '.' . $fileExtension;
                        $dest_path = $uploadDir . $newFileName;
                        $imgPath = $domain.'/uploads/po_items/' . $newFileName;
                        //set permissions                        
                        if (move_uploaded_file($fileTmp, $dest_path)) {
                            // store relative web path                                                       
                            @chmod($dest_path, 0644);

                        } else {
                            // failed to move, keep existing POST image if provided
                            $imgPath = isset($data['img'][$index]) ? $data['img'][$index] : '';
                        }
                    } else {
                        // invalid extension, keep existing POST image if provided
                        $imgPath = isset($data['img'][$index]) ? $data['img'][$index] : '';
                    }
                } else {
                    // no upload for this index, use provided img value (could be URL or previously uploaded path)
                    $imgPath = isset($data['img'][$index]) ? $data['img'][$index] : '';
                }
            } else {
                // no file input at all, use provided img value
                $imgPath = isset($data['img'][$index]) ? $data['img'][$index] : '';
            }

            // Ensure items image points to the resolved path
            $data['img'][$index] = $imgPath;
            $items = [
                'purchase_orders_id' => $poId,
                'sku' => isset($data['sku'][$index]) ? $data['sku'][$index] : '',
                'item_code' => isset($data['item_code'][$index]) ? $data['item_code'][$index] : '',
                'product_id' => isset($data['product_id'][$index]) ? $data['product_id'][$index] : '',
                'title' => isset($data['title'][$index]) ? $data['title'][$index] : '',
                'image' => isset($data['img'][$index]) ? $data['img'][$index] : '',
                'hsn' => isset($data['hsn'][$index]) ? $data['hsn'][$index] : '',
                'gst' => $gstValue,
                'quantity' => isset($data['quantity'][$index]) ? $data['quantity'][$index] : 0,
                'price' => isset($data['rate'][$index]) ? $data['rate'][$index] : 0,
                'amount' => isset($data['rate'][$index]) ? $data['rate'][$index] * (1 + ($gstValue / 100)) : 0
            ];
            //print_array($items);
            $itemId = $purchaseOrderItemsModel->createCustomPoItem($items);
            if (!$itemId) {
                $itemsCreated = false;
                break; // Stop processing if any item creation fails
            }
        }

        echo json_encode(['success' => true, 'message' => 'Custom Purchase Order created successfully.', 'po_id' => $poId, 'item_ids' => $itemsCreated]);
        exit;
    }
}