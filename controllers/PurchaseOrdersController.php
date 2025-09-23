<?php 
require_once 'models/order/purchaseOrder.php';
require_once 'models/order/order.php';
require_once 'models/order/purchaseOrderItem.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/order/po_invoice.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'vendor/autoload.php';

$purchaseOrdersModel = new PurchaseOrder($conn);
$ordersModel = new Order($conn);
$purchaseOrderItemsModel = new PurchaseOrderItem($conn);
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);
$poInvoiceModel = new POInvoice($conn);
global $root_path;
 
class PurchaseOrdersController {
    public function index() {
        is_login();
        global $purchaseOrdersModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        // Apply filters
        $filters = [];
        if (!empty($_GET['search_text'])) {
            $filters['search_text'] = $_GET['search_text'];
        }
        if (!empty($_GET['status_filter'])) {
            $filters['status_filter'] = $_GET['status_filter'];
        }

        // Fetch all purchase orders
        $purchaseOrders = $purchaseOrdersModel->getAllPurchaseOrders($filters);
        // Calculate total pages
        $total_orders = count($purchaseOrders);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Paginate orders
        $orders = array_slice($purchaseOrders, $offset, $limit);

        renderTemplate('views/purchase_orders/index.php', [
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
        $vendor = isset($_POST['vendor']) ? $_POST['vendor'] : '';
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
                'amount' => isset($rate[$index]) ? $rate[$index] * (1 + ($gstValue / 100)) : 0
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
        
        $data['invoice'] = $poInvoiceModel->getInvoiceByPOId($poId);
        $data['status_log'] = $purchaseOrdersModel->get_po_status_log($poId);
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
        //$data['exotic_address'] = $address;
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
            'grand_total' => $_POST['grand_total'],
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

        
        $poId = isset($_GET['po_id']) ? $_GET['po_id'] : 0;
        if($generateOnly){
            $poId = $generateOnly;
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
            
            $tbody = '';
            foreach ($purchaseOrderItems as $index => $item) {
                $text = explode(':', $item['title']);
                $lang1 = $detector->detectSimple($text[0]); // returns 'hi', 'ta', etc.
                $lang2 = isset($text[1]) ? $detector->detectSimple($text[1]) : 'english';
                $font = $fontMap[$lang1] ?? 'sans-serif'; // fallback if unknown
                $font2 = $fontMap[$lang2] ?? 'sans-serif'; // fallback if unknown

                $tbody .= '<tr>';
                $tbody .= '<td style="width:5% !important; border:1px solid #000; padding:6px; text-align:center;">' . ($index + 1) . '</td>';
                $tbody .= '<td style="width:37% !important; border:1px solid #000; padding:6px;">';
                //$tbody .= '<p style="font-family: ' . $font . ';">' . $text[0] . ' | ' . $font . '</p>'.'<p style="font-family: ' . $font2 . ';">' . $text[1] . ' | ' . $lang2 . '</p>';
                $tbody .= '<p>' . htmlspecialchars($item['title']) . '</p>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; text-align:center;"><img src="' . htmlspecialchars($item['image']) . '" style="width:auto; max-height:150px;"></td>';
                $tbody .= '<td style="width:8% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['quantity']) . '</td>';
                $tbody .= '<td style="width:13% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['price'], 2) . '</td>';
                $tbody .= '<td style="width:8% !important; border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($item['gst']) . '%</td>';
                $tbody .= '<td style="width:16% !important; border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($item['amount'], 2) . '</td>';
                $tbody .= '</tr>';
                
            }
        }
        
        require_once('vendor/autoload.php');
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

        $temphtml = file_get_contents('templates/purchaseOrder/PurchaseOrder.html');
        //Define HTML content
        $html = str_replace(
            ['{{po_number}}', '{{date}}', '{{delivery_due}}', '{{tbody}}', '{{subtotal}}', '{{shipping}}', '{{gst}}', '{{grand_total}}', '{{terms}}', '{{vendor_info}}', '{{contact_person}}'],
            [$purchaseOrder['po_number'], date('d M Y', strtotime($purchaseOrder['created_at'])), date('d M Y', strtotime($purchaseOrder['expected_delivery_date'])), $tbody, $purchaseOrder['subtotal'], $purchaseOrder['shipping_cost'], $purchaseOrder['total_gst'], $purchaseOrder['total_cost'], $purchaseOrder['terms_and_conditions'], $vendorInfo, $contactPerson],
            $temphtml
        );
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
                'invoice_no' => $_POST['invoice_no'] ?? '',
                'invoice_date' => $_POST['invoice_date'] ?? '',
                'gst_reg' => $_POST['gst_reg'] ?? 0,
                'sub_total' => $_POST['sub_total'] ?? 0,
                'gst_total' => $_POST['gst_total'] ?? 0,
                'shipping' => $_POST['shipping'] ?? 0,
                'grand_total' => $_POST['grand_total'] ?? 0,
                'invoice' => $invoice,
            ];
            //update purchase order invoice path
            $isUpdatedInv = $purchaseOrdersModel->updateInvoicePath($poId, 'uploads/invoices/' . $newFileName);
            if (!$isUpdatedInv) {
                echo json_encode(['success' => false, 'message' => 'Failed to update purchase order invoice path.']);
                exit;
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
            
            echo json_encode(['success' => true, 'message' => 'Invoice uploaded successfully', 'invoice_path' => 'uploads/invoices/' . $newFileName . ' po:' . $isUpdatedInv]);
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
        $terms = $data['terms_and_conditions'] ?? '';
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
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($data['rate'][$i] ?? 0, 2) . '</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:center;">' . htmlspecialchars($data['gst'][$i] ?? '') . '%</td>';
                $tbody .= '<td style="border:1px solid #000; padding:6px; text-align:right;">₹' . number_format($data['amount'][$i] ?? 0, 2) . '</td>';
                $tbody .= '</tr>';
            }
        }

        // Load template
        $temphtml = file_get_contents('templates/purchaseOrder/PurchaseOrder.html');
        $html = str_replace(
            ['{{po_number}}', '{{date}}', '{{delivery_due}}', '{{tbody}}', '{{subtotal}}', '{{shipping}}', '{{gst}}', '{{grand_total}}', '{{terms}}', '{{vendor_info}}'],
            [$po_number, $date, $delivery_due, $tbody, $subtotal, $shipping, $gst, $grand_total, $terms, $vendorInfo],
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
        $poInvoice = $poInvoiceModel->getInvoiceByPoId($poId);
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

        $invoicePath = __DIR__ . '/../' . $invoice['invoice'];
        if (file_exists($invoicePath)) {
            unlink($invoicePath); // Delete the file
        }

        // Delete the invoice record from the database
        $isDeleted = $poInvoiceModel->updateFile($invoiceId, '');
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
}