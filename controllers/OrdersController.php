<?php 
require_once 'models/order/order.php';
require_once 'models/comman/tables.php';
require_once 'models/searches/saved_search.php';
require_once 'models/order/po_invoice.php';
$ordersModel = new Order($conn);
$commanModel = new Tables($conn);
$savedSearchModel = new SavedSearch($conn);
$poInvoiceModel = new POInvoice($conn);
global $root_path;
global $domain;
class OrdersController { 
     
    public function index() {
        is_login();
        global $ordersModel;
        global $commanModel;
        global $savedSearchModel;
        //sanitize and validate input parameters
        $_GET = sanitizeGet($_GET);
        // Fetch all orders
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;

        //Advanced Search Filters
        $filters = [];
        if (!empty($_GET['order_number'])) {
            $filters['order_number'] = $_GET['order_number'];            
        }
        if (!empty($_GET['item_code'])) {
            $filters['item_code'] = $_GET['item_code'];            
        }
        if (!empty($_GET['order_from']) && !empty($_GET['order_till'])) {
            $filters['order_from'] = $_GET['order_from'];
            $filters['order_till'] = $_GET['order_till'];
        }
        
        // if(!empty($_GET['daterange'])){
        //     echo urldecode($_GET['daterange']);
        //     $dateRange = explode(' - ', $_GET['daterange']);       
        //     print_array($dateRange);     
        //     if (count($dateRange) === 2) {
        //         $filters['order_from'] = date('Y-m-d', strtotime($dateRange[0]));
        //         $filters['order_till'] = date('Y-m-d', strtotime($dateRange[1]));
        //     }
        // }
        if (!empty($_GET['item_name'])) {
            $filters['title'] = $_GET['item_name'];            
        }
        if (!empty($_GET['min_amount'])) {
            $filters['min_amount'] = $_GET['min_amount'];            
        }
        if (!empty($_GET['max_amount'])) {
            $filters['max_amount'] = $_GET['max_amount'];            
        }
        if(!empty($_GET['po_no'])){
            $filters['po_no'] = $_GET['po_no'];  
        }
        if (!empty($_GET['status'])) {
            $filters['status_filter'] = $_GET['status'];
        } 

        if (!empty($_GET['category']) && $_GET['category'] != 'all') {
            $filters['category'] = $_GET['category'];
        } else {
            $filters['category'] = 'all';
        }
        if(!empty($_GET['country'])){
            $filters['country'] = $_GET['country'];  
        }
        if(!empty($_GET['options']) && $_GET['options'] == 'express'){
            $filters['options'] = 'express';  
        }
        if (!empty($_GET['sort']) && in_array(strtolower($_GET['sort']), ['asc', 'desc'])) {
            $filters['sort'] = strtolower($_GET['sort']);
        } else {
            $filters['sort'] = 'desc'; // Default sort order
        }
        if (!empty($_GET['payment_type']) && $_GET['payment_type'] != 'all') {
            $filters['payment_type'] = $_GET['payment_type'];
        } else {
            $filters['payment_type'] = 'all';
        }
        if (!empty($_GET['staff_name'])) {
            $filters['staff_name'] = $_GET['staff_name'];
        }
        if (!empty($_GET['priority'])) {
            $filters['priority'] = $_GET['priority'];
        }
        if(!empty($_GET['vendor_id'])){
            $filters['vendor_id'] = $_GET['vendor_id'];  
        }
        if(!empty($_GET['agent'])){
            $filters['agent'] = $_GET['agent'];  
        }
        if (!empty($_GET['publisher'])) {
            $filters['publisher'] = $_GET['publisher'];            
        }
        if (!empty($_GET['author'])) {
            $filters['author'] = $_GET['author'];            
        }
        
        //order status list
        $statusList = $commanModel->get_order_status_list();
        $order_status_row = $commanModel->get_order_status();
        $countryList= $commanModel->get_counry_list();
        //print_array($order_status_list);
        // Use pagination in the database query for better performance
        //print_r($_GET);
        //print_r($filters);
        $orders = $ordersModel->getAllOrders($filters, $limit, $offset);             
        foreach ($orders as $key => $order) {
            $orders[$key]['status_log'] = $commanModel->get_order_status_log($order['order_id']);            
        }
        //print_array($orders);  
        $total_orders = $ordersModel->getOrdersCount($filters);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Prepare saved searches for current user
        $user_id = $_SESSION['user']['id'] ?? 0;
        $saved_searches = [];
        if ($user_id) {
            $saved_searches = $savedSearchModel->getByUser($user_id, 'orders');
        }

        // Render the orders view
        renderTemplate('views/orders/index.php', [
            'orders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'order_status_list' => $order_status_row,
            'status_list' => $statusList,
            'country_list' => $countryList,
            'payment_types'=> $ordersModel->getPaymentTypes(),
            'staff_list' => $commanModel->get_staff_list(),
            'filters' => $filters,
            'saved_searches' => $saved_searches
        ], 'Manage Orders');
    }
        
    public function viewOrder() {
        is_login();
        global $ordersModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $order = $ordersModel->getOrderById($id); 
            if ($order) {
                renderTemplate('views/orders/view_order.php', ['order' => $order], 'View Order');
            } else {
                renderTemplate('views/errors/not_found.php', [], 'Order Not Found');
            }   
        } else {
            renderTemplate('views/errors/not_found.php', [], 'Invalid Order ID');
        }
        exit;
    }
	
    public function importOrders() {
        //is_login();
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        $lastLog = $ordersModel->getLastImportLog();
        
        //log create
        $log_data = ['start_time' => date('Y-m-d H:i:s')];
        $log_id = 0;
		
        if($logs = $ordersModel->orderImportLog($log_data)){
            $log_id = $logs['insert_id'];
        }        // Set your date range (example: last 7 days)

        $from_date = strtotime('-1 days');
        //echo "<br>";
        if ($lastLog && !empty($lastLog['max_ordered_time'])) {         
            $from_date = $lastLog['max_ordered_time'];
        }
        $to_date = time();
        //$from_date = strtotime(date('12-08-2025 00:00:00')); // Example fixed date
        //$to_date = strtotime(date('13-08-2025 00:00:00'));
        //$from_date = 1755101792; // Example fixed date 12-08-2025 00:00:00
        //$to_date = 1755102092;   // Example fixed date 13-08-2025 23:59:59
        //$url = 'https://www.exoticindia.com/action';
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint
       
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
        if (!empty($_GET['orderid'])) {
            $postData = [
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $_GET['orderid']
            ];
        }

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        $error = curl_error($ch);
        curl_close($ch);
        // print_r($error);
        // print_r($headers);
        // print_r($response);
        if ($response === false) {
            renderTemplateClean('views/errors/error.php', ['message' => 'API request failed: ' . $error], 'API Error');
            return;
        }

        $orders = json_decode($response, true);
        if (!is_array($orders)) {
            //echo "Invalid API response format.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'Invalid API response format.']], 'API Error');
            return;
        }
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0; $totalorder = 0; $result =[]; $pdata = []; $addressdata = [];
        foreach ($orders['orders'] as $order) { 
            
            //print_r($order['cart']);
            // Check if the order has the required fields
            // Map API fields to your table columns
            //2658982 order_number continue;
            if (in_array($order['orderid'], ['2658982', '2660434','2662287','469282','2664206'])) {
                continue; // Skip invalid orders
            }
            //customer data
            $customerdata = $ordersModel->addCustomerIfNotExists($order);
            //print_array($customerdata);
                foreach ($order['cart'] as $item) {  
                    $orderdate =  !empty($order['processed_time']) ? date('Y-m-d H:i:s', $order['processed_time']) : date('Y-m-d H:i:s'); 
                    $esd = '0000-00-00';
                    $local_stock_int = (int) floatval($item['local_stock']);
                    $lead_time_int = (int) floatval($item['leadtime']);
                    if($item['marketplace_vendor'] == 'exoticindia' || empty($item['marketplace_vendor'])){
                        if(!empty($local_stock_int) && $local_stock_int > 0){
                            $esd = date('Y-m-d', strtotime($orderdate. ' + 3 days'));
                        } else {
                            // Normalize options to array and check for 'express'
                            $hasExpress = false;
                            $options = $item['options'] ?? null;
                            if (!empty($options)) {
                                if (is_string($options)) {
                                    $decoded = json_decode($options, true);
                                    if (is_array($decoded)) {
                                        $hasExpress = in_array('express', $decoded, true);
                                    } else {
                                        // fallback: check substring (case-insensitive) for non-JSON values
                                        $hasExpress = stripos($options, 'express') !== false;
                                    }
                                } elseif (is_array($options)) {
                                    $hasExpress = in_array('express', $options, true);
                                }
                            }
                            if ($hasExpress) {
                                $esd = date('Y-m-d', strtotime($orderdate. ' + 0 days'));
                            } else {
                                $esd = date('Y-m-d', strtotime($orderdate. ' + ' . $lead_time_int . ' days'));
                            }
                        }
                    }else{
                        if(!empty($local_stock_int) && $local_stock_int > 0){
                            $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $local_stock_int . ' days'));                           
                        } else {
                            $esd = date('Y-m-d', strtotime($orderdate. ' + '.($lead_time_int).' days'));                            
                        }
                    }
					$rdata = [
                    'sku' => $item['sku'] ?? '',
					'order_number' => $order['orderid'] ?? '',
					'shipping_country' => $order['shipping_country'] ?? '',
					'title' => !empty($item['title']) ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $item['title']) : '',
					'description' => $item['description'] ?? '',
					'item_code' => $item['itemcode'] ?? '',
					'size' => $item['size'] ?? '',
					'color' => $item['color'] ?? '',
					'groupname' => $item['groupname'] ?? '',
                    'subcategories' => !empty($item['subcategories']) ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $item['subcategories']) : '',
					'currency' => $item['currency'] ?? '',
					'itemprice' => $item['itemprice'] ?? '',
					'finalprice' => $item['finalprice'] ?? '',
					'image' => $item['image'] ?? '',
					'marketplace_vendor' => $item['marketplace_vendor'] ?? '',
					'quantity' => $item['qty'] ?? '',
					'options' => $item['options'] ?? 0,
					'gst' => $item['gst'] ?? '',
					'hsn' => $item['hscode'] ?? '',
					'local_stock' => $item['local_stock'] ?? '',
					'cost_price' => $item['cp'] ?? 0.0,
					'location' => $item['location'] ?? '',
					'order_date' => date('Y-m-d H:i:s', $order['processed_time'] ?? ''),
                    'processed_time' => $order['processed_time'] ?? 0,
                    'numsold' => $item['numsold'] ?? 0,
                    'product_weight' => $item['product_weight'] ?? 0.0,
                    'product_weight_unit' => $item['product_weight_unit'] ?? '',
                    'prod_height' => $item['prod_height'] ?? 0.0,
                    'prod_width' => $item['prod_width'] ?? 0.0,
                    'prod_length' => $item['prod_length'] ?? 0.0,
                    'length_unit' => $item['length_unit'] ?? '',
                    'backorder_status' => $item['backorder_status'] ?? 0,
                    'backorder_percent' => $item['backorder_percent'] ?? 0,
                    'backorder_delay' => $item['backorder_delay'] ?? '',
                    'payment_type' => $order['payment_type'] ?? '',
                    'coupon' => $order['coupon'] ?? '',
                    'coupon_reduce' => $order['coupon_reduce'] ?? '',
                    'giftvoucher' => $order['giftvoucher'] ?? '',
                    'giftvoucher_reduce' => $order['giftvoucher_reduce'] ?? '',
                    'credit' => $order['credit'] ?? '',
                    'vendor' => $item['vendor'] ?? '',
                    'country' => $order['country'] ?? '',
                    'material' => $item['material'] ?? '',
                    'publisher' => $item['publisher'] ?? '',
                    'author' => $item['author'] ?? '',
                    'shippingfee' => $item['shippingfee'] ?? '',
                    'sourcingfee' => $item['sourcingfee'] ?? '',
                    //$orderStatus = productionOrderStatusList()[$item['status']] ?? 'pending',
                    'status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA')
                        ? 'shipped'
                        : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),
                    'esd' => $esd,
                    'agent_id' => 0
                    ];
                    if(strtoupper($order['payment_type']) == 'COD' &&  $item['itemprice'] >= 5000){
                        $rdata['status'] = 'cod_confirmation_required';
                        $rdata['agent_id'] = 31; // Assign to specific agent Ashutosh for COD confirmation
                    }
                    //customer id add
                    $rdata['customer_id'] = $customerdata['customer_id'] ?? 0;
					$totalorder++;                
                    
                    $data = $ordersModel->insertOrder($rdata);
                    $result[] = $data;
                    //add products
                    $pdata[] = $ordersModel->addProducts($rdata);                   
                    //$vdata = $ordersModel->addVendorIfNotExists($rdata['vendor']);
                    if (isset($data['success']) && $data['success'] == 1) {                        
                        $imported++;
                    } 
                    //print_array($rdata);                   
            }
            //add address info
            $addressdata[] = $ordersModel->insertAddressInfo($order, $customerdata['customer_id'] ?? 0);
           //print_array($addressdata);
           //print_array($order);exit;
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        if($log_id > 0){
            $log_update_data = [
                'end_time' => date('Y-m-d H:i:s'),
                'successful_imports' => $imported,
                'total_orders' => $totalorder,
                'error' => isset($error) ? $error : '',
                'log_details' => NULL, //json_encode($result),
                'max_ordered_time' => $order['processed_time'] ?? '',
                'from_date' => $from_date,
                'to_date' => $to_date,
                'add_product_log' => NULL,//json_encode($pdata)
            ];
            //print_array($log_update_data);
            $ordersModel->updateOrderImportLog($log_id, $log_update_data);
        }
        //print_array($result);
        renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }
	
    public function createPurchaseOrder() {
        is_login();
        global $ordersModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $order = $ordersModel->getOrderById($id);
            if ($order) {
                renderTemplate('views/orders/create_purchase_order.php', ['order' => $order], 'Create Purchase Order');
            } else {
                renderTemplate('views/errors/not_found.php', [], 'Order Not Found');
            }
        } else {
            renderTemplate('views/errors/not_found.php', [], 'Invalid Order ID');
        }
        exit;
    }
    public function getOrderDetails() {
        global $ordersModel;
        header('Content-Type: application/json');
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $order = $ordersModel->getOrderById($id);
            if ($order) {
                echo json_encode(['success' => true, 'order' => $order]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Order not found.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid Order ID.']);
        }
        exit;
    }

    public function saveSearch() {
        // Save a named search (AJAX)
        is_login();
        header('Content-Type: application/json');
        $user_id = $_SESSION['user']['id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $query = trim($_POST['query'] ?? ($_SERVER['QUERY_STRING'] ?? ''));
        if (!$user_id || empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }
        if (empty($name)) {
            $name = 'Saved Search - ' . date('Y-m-d H:i');
        }
        global $savedSearchModel;
        $data = [
            'user_id' => $user_id,
            'page' => 'orders',
            'name' => $name,
            'query' => $query
        ];
        $res = $savedSearchModel->add($data);
        if (!empty($res['insert_id'])) {
            $record = $savedSearchModel->get($res['insert_id'], $user_id);
            echo json_encode(['success' => true, 'search' => $record]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to save search.']);
        }
        exit;
    }

    public function deleteSearch() {
        // Delete saved search (AJAX)
        is_login();
        header('Content-Type: application/json');
        $user_id = $_SESSION['user']['id'] ?? 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$user_id || !$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }
        global $savedSearchModel;
        $ok = $savedSearchModel->delete($id, $user_id);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to delete.']);
        }
        exit;
    }

    public function updateStatus() {
        is_login();
        global $ordersModel; 
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = isset($_POST['status_order_id']) ? (int)$_POST['status_order_id'] : 0;
            $new_status = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';
            $remarks = isset($_POST['orderRemarks']) ? trim($_POST['orderRemarks']) : NULL;
            $esd = isset($_POST['esd']) ? trim($_POST['esd']) : NULL;
            $priority = isset($_POST['orderPriority']) ? trim($_POST['orderPriority']) : NULL;
            $agent_id = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : NULL;
            $previous_agent = isset($_POST['previous_agent']) ? (int)$_POST['previous_agent'] : NULL;
            $agent_name = isset($_POST['agent_name']) ? trim($_POST['agent_name']) : NULL;
            $previous_status = isset($_POST['previousStatus']) ? trim($_POST['previousStatus']) : NULL;
            $previous_esd = isset($_POST['previous_esd']) ? trim($_POST['previous_esd']) : NULL;
            $previous_priority = isset($_POST['previous_priority']) ? trim($_POST['previous_priority']) : NULL;
            $previous_remarks = isset($_POST['previous_remarks']) ? trim($_POST['previous_remarks']) : NULL;

            if ($order_id > 0 && !empty($new_status)) {
                $update_data = [
                    'status' => $new_status,
                    'remarks' => $remarks,
                    'priority' => $priority,
                    'agent_id' => $agent_id
                ];
                // only include ESD if a non-empty value was provided to avoid inserting an empty string into a DATE/DATETIME column
                if ($esd !== NULL && $esd !== '') {
                    $update_data['esd'] = $esd;
                }
                $updated = $ordersModel->updateStatus($order_id, $update_data);
               
                // commented out on 09-11-2025 as per request
                // call exotic india API to update order status
                $orderval = $ordersModel->getOrderById($order_id);
                $apidata = [
                    'orderid' => $orderval['order_number'],
                    'level' => 'item',
                    'order_status' => $commanModel->getExoticIndiaOrderStatusCode($new_status)['admin_id'],
                    'size' => trim($orderval['size']),
                    'color' => trim($orderval['color']),
                    'itemcode' => trim($orderval['item_code'])
                ];
                //run update if admin id not 0
                if ($apidata['order_status'] > 0) {
                    $resp = $commanModel->updateExoticIndiaOrderStatus($apidata);
                }
                //log status change
                $logData = [
                    'order_id' => $order_id,
                    'status' => 'Status: '.$new_status,
                    'changed_by' => $_SESSION['user']['id'],
                    'api_response' => NULL, //json_encode($resp),
                    'change_date' => date('Y-m-d H:i:s')
                ];
                //print_array($apidata);
                //print_array($_POST);
                if($new_status != $_POST['previousStatus']){
                    $commanModel->add_order_status_log($logData);
                }
                if($agent_id != $previous_agent){
                    //log agent change
                    $agentLogData = [
                        'order_id' => $order_id,                        
                        'status' => 'Agent: '.$agent_name,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($agentLogData);
                    //set notification to agent
                    $link = base_url('index.php?page=orders&action=list&'.$order_id);
                    insertNotification($agent_id, 'Order Assigned', 'You have been assigned a new order. Please check the order details.', $link);
                }
                if($esd != $previous_esd){
                    //log esd change
                    $esdLogData = [
                        'order_id' => $order_id,                        
                        'status' => 'ESD : '.$esd,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($esdLogData);
                }
                if($priority != $previous_priority){
                    //log priority change
                    $priorityLogData = [
                        'order_id' => $order_id,                        
                        'status' => 'Priority : '.$priority,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($priorityLogData);
                }
                if($remarks != $previous_remarks){
                    //log remarks change
                    $remarksLogData = [
                        'order_id' => $order_id,                        
                        'status' => 'Notes updated.',
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($remarksLogData);
                }   

                if ($updated) {
                    echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
            }
        }

        exit;
    }
    public function getOrderDetailsHTML() {
        is_login();
        global $ordersModel, $commanModel;
        $order_number = isset($_GET['order_number']) ? (int)$_GET['order_number'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : 'inner';
        if ($order_number > 0) {
            $order = $ordersModel->getOrderByOrderNumber($order_number);
            $statusList = $commanModel->get_order_status_list();
            if ($order) {
                if ($type === 'inner')
                    renderPartial('views/orders/partial_order_details.php', ['order' => $order, 'statusList' => $statusList]);
                else
                    renderTemplate('views/orders/other_partial_order_details.php', ['order' => $order, 'statusList' => $statusList], 'Order Details');
            } else {
                echo '<p>Order details not found.</p>';
            }
        } else {
            echo '<p>Invalid Order Number.</p>';
        }
        exit;
    }
    public function updateImportedOrders() {        
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        // Set your date range (example: last 7 days)
        //print_array($_GET);
        $from_date = !empty($_GET['from_date']) ? strtotime($_GET['from_date'] . ' 00:00:00') : strtotime('-1 days');
        //echo "<br>";
        // if ($lastLog && !empty($lastLog['max_ordered_time'])) {         
        //     $from_date = $lastLog['max_ordered_time'];
        // }
        $to_date = !empty($_GET['to_date']) ? strtotime($_GET['to_date'] . ' 23:59:59') : time();
      
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint
       
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
        if (!empty($_GET['orderid'])) {
            $postData = [
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $_GET['orderid']
            ];
        }

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        $error = curl_error($ch);
        curl_close($ch);
        // print_r($error);
        // print_r($headers);
        // print_r($response);
        if ($response === false) {
            renderTemplateClean('views/errors/error.php', ['message' => 'API request failed: ' . $error], 'API Error');
            return;
        }

        $orders = json_decode($response, true);
        if (!is_array($orders)) {
            //echo "Invalid API response format.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'Invalid API response format.']], 'API Error');
            return;
        }
        // echo "Total Orders Fetched: " . count($orders['orders']) . "<br>";
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0; $totalorder = 0;
        foreach ($orders['orders'] as $order) { 
            
            //print_r($order['cart']);
            // Check if the order has the required fields
            // Map API fields to your table columns
                
                foreach ($order['cart'] as $item) {  
                    $orderdate =  !empty($order['processed_time']) ? date('Y-m-d H:i:s', $order['processed_time']) : date('Y-m-d H:i:s'); 
                    $esd = '0000-00-00';
                    $local_stock_int = (int) floatval($item['local_stock']);
                    $lead_time_int = (int) floatval($item['leadtime']);
                    if($item['marketplace_vendor'] == 'exoticindia' || empty($item['marketplace_vendor'])){
                        if(!empty($local_stock_int) && $local_stock_int > 0){
                            $esd = date('Y-m-d', strtotime($orderdate. ' + 3 days'));
                        } else {
                            // Normalize options to array and check for 'express'
                            $hasExpress = false;
                            $options = $item['options'] ?? null;
                            if (!empty($options)) {
                                if (is_string($options)) {
                                    $decoded = json_decode($options, true);
                                    if (is_array($decoded)) {
                                        $hasExpress = in_array('express', $decoded, true);
                                    } else {
                                        // fallback: check substring (case-insensitive) for non-JSON values
                                        $hasExpress = stripos($options, 'express') !== false;
                                    }
                                } elseif (is_array($options)) {
                                    $hasExpress = in_array('express', $options, true);
                                }
                            }
                            if ($hasExpress) {
                                $esd = date('Y-m-d', strtotime($orderdate. ' + 0 days'));
                            } else {
                                $esd = date('Y-m-d', strtotime($orderdate. ' + ' . $lead_time_int . ' days'));
                            }
                        }
                    }else{
                        if(!empty($local_stock_int) && $local_stock_int > 0){
                            $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $local_stock_int . ' days'));                           
                        } else {
                            $esd = date('Y-m-d', strtotime($orderdate. ' + '.($lead_time_int).' days'));                            
                        }
                    }
					$rdata = [
					'order_number' => $order['orderid'] ?? '',
					'shipping_country' => $order['shipping_country'] ?? '',
					'title' => $item['title'] ?? '',
					'description' => $item['description'] ?? '',
					'item_code' => $item['itemcode'] ?? '',
					'size' => $item['size'] ?? '',
					'color' => $item['color'] ?? '',
					'groupname' => $item['groupname'] ?? '',
					'subcategories' => $item['subcategories'] ?? '',
					'currency' => $item['currency'] ?? '',
					'itemprice' => $item['itemprice'] ?? '',
					'finalprice' => $item['finalprice'] ?? '',
					'image' => $item['image'] ?? '',
					'marketplace_vendor' => $item['marketplace_vendor'] ?? '',
					'quantity' => $item['qty'] ?? '',
					'options' => $item['options'] ?? 0,
					'gst' => $item['gst'] ?? '',
					'hsn' => $item['hscode'] ?? '',
					'local_stock' => $item['local_stock'] ?? '',
					'cost_price' => $item['cp'] ?? 0.0,
					'location' => $item['location'] ?? '',
					'order_date' => date('Y-m-d H:i:s', $order['processed_time'] ?? ''),
                    'processed_time' => $order['processed_time'] ?? 0,
                    'numsold' => $item['numsold'] ?? 0,
                    'product_weight' => $item['product_weight'] ?? 0.0,
                    'product_weight_unit' => $item['product_weight_unit'] ?? '',
                    'prod_height' => $item['prod_height'] ?? 0.0,
                    'prod_width' => $item['prod_width'] ?? 0.0,
                    'prod_length' => $item['prod_length'] ?? 0.0,
                    'length_unit' => $item['length_unit'] ?? '',
                    'backorder_status' => $item['backorder_status'] ?? 0,
                    'backorder_percent' => $item['backorder_percent'] ?? 0,
                    'backorder_delay' => $item['backorder_delay'] ?? '',
                    'payment_type' => $order['payment_type'] ?? '',
                    'coupon' => $order['coupon'] ?? '',
                    'coupon_reduce' => $order['coupon_reduce'] ?? '',
                    'giftvoucher' => $order['giftvoucher'] ?? '',
                    'giftvoucher_reduce' => $order['giftvoucher_reduce'] ?? '',
                    'credit' => $order['credit'] ?? '',
                    'vendor' => $item['vendor'] ?? '',
                    'country' => $order['country'] ?? '',
                    'material' => $item['material'] ?? '',
                    //$orderStatus = productionOrderStatusList()[$item['status']] ?? 'pending',
                    'status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA')
                        ? 'shipped'
                        : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),
                    'esd' => $esd,
                    'updated_at' => date('Y-m-d H:i:s')
                    ];
					$totalorder++;                
                    
                    $data = $ordersModel->updateImportedOrder($rdata);
                    $result[] = $data;
                    //add products
                    //$pdata[] = $ordersModel->addProducts($rdata);                   
                    
                    if (isset($data['success']) && $data['success'] == true) {                        
                        $imported++;
                    } 
                   // print_array($rdata);                   
            }
           
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        
        renderTemplateClean('views/orders/import_update_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            //'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }
    public function skuUpdateImportedOrders() {      
        //ini_set('max_execution_time', 300);
        //set_time_limit(300);  
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
       // $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        // Set your date range (example: last 7 days)
        //print_array($_GET);
        $from_date = !empty($_GET['from_date']) ? strtotime($_GET['from_date'] . ' 00:00:00') : strtotime('-1 days');
        //echo "<br>";
        // if ($lastLog && !empty($lastLog['max_ordered_time'])) {         
        //     $from_date = $lastLog['max_ordered_time'];
        // }
        $to_date = !empty($_GET['to_date']) ? strtotime($_GET['to_date'] . ' 23:59:59') : time();
        //$from_date = '1758240000';
        //$to_date = '1758330134';
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint
       
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'from_date' => $from_date,
            'to_date' => $to_date
        ];
        if (!empty($_GET['orderid'])) {
            $postData = [
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $_GET['orderid']
            ];
        }

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        
        $error = curl_error($ch);
        curl_close($ch);
        // print_r($error);
        // print_r($headers);
        // print_r($response);
        if ($response === false) {
            renderTemplateClean('views/errors/error.php', ['message' => 'API request failed: ' . $error], 'API Error');
            return;
        }

        $orders = json_decode($response, true);
        if (!is_array($orders)) {
            //echo "Invalid API response format.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'Invalid API response format.']], 'API Error');
            return;
        }
        // echo "Total Orders Fetched: " . count($orders['orders']) . "<br>";
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0; $totalorder = 0;
        foreach ($orders['orders'] as $order) { 
            
            //print_r($order['cart']);
            // Check if the order has the required fields
            // Map API fields to your table columns
                
            foreach ($order['cart'] as $item) {
                $rdata = [
                'sku' => $item['sku'] ?? '',
                'order_number' => $order['orderid'] ?? '',
                'item_code' => $item['itemcode'] ?? '',					
                'updated_at' => date('Y-m-d H:i:s')
                ];
                $totalorder++;                
                
                $data = $ordersModel->skuUpdateImportedOrder($rdata);
                $result[] = $data;
                //add products
                //$pdata[] = $ordersModel->addProducts($rdata);                   
                
                if (isset($data['success']) && $data['success'] == true) {                        
                    $imported++;
                } 
                //print_array($rdata);                   
            }
           
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        
        renderTemplateClean('views/orders/import_update_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            //'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }
    public function ordersStatusImportBulk() {   
           
        ini_set('max_execution_time', 3000);
        set_time_limit(3000);  
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //fetch order 
        $odr = $ordersModel->fetchOrdersForUpdate();
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //$from_date = '1758240000';
        //$to_date = '1758330134';
        //print_array($odr);
        //exit;
        
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint       
        
        $orderChunks = array_chunk(array_filter($odr, function($order) {
            return !empty($order);
        }), 50);
       
        $response = [];
        foreach ($orderChunks as $key => $chunk) {
            $orderIds = implode(',', $chunk);
            $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $orderIds
            ];

            $headers = [
                'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
                'x-adminapitest: 1',
                'Content-Type: application/x-www-form-urlencoded'
            ];
            // print_r($postData);
            // exit;
            // Initialize cURL
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response[] = curl_exec($ch);
            
            $error = curl_error($ch);
            curl_close($ch);
            //echo $orderIds."<br>Chunk ".($key+1)." Response:<br>";
            //print_array(json_decode($response[0]), true);
            if(!empty($error)){
                break;
            }
            // if($key >= 10){
            //     //limit to 5 chunks per execution
            //     break;
            // }
            
        }
        //print_r($error);
        // print_r($headers);
       
        // echo "Total Orders Fetched: " . count($orders['orders']) . "<br>";
        // print_array($orders);
        // exit;
        if (empty($response)) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0; $totalorder = 0;
        foreach ($response as $resp) {
            $respData = json_decode($resp, true);
            if (!is_array($respData) || empty($respData['orders'])) {
                continue; // Skip invalid or empty responses
            }
            foreach ($respData['orders'] as $order) {             
                //print_r($order);
                // Check if the order has the required fields
                // Map API fields to your table columns
                    
                foreach ($order['cart'] as $item) {
                    //check status other than 1 (pending)
                    if(empty($item['order_status']) || $item['order_status'] == 1){
                        //continue;
                    
                        $rdata = [
                        'sku' => $item['sku'] ?? '',
                        'order_number' => $order['orderid'] ?? '',
                        'item_code' => $item['itemcode'] ?? '',	
                        'status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA' || strtoupper($order['payment_type'] ?? '') === 'INDIAAMAZONFBA')
                            ? 'shipped'
                            : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),				
                        'updated_at' => date('Y-m-d H:i:s')
                        ];
                        $totalorder++;                
                        
                        $data = $ordersModel->importedStatusUpdate2($rdata);
                        $result[] = $data;
                        //add products
                        //$pdata[] = $ordersModel->addProducts($rdata);                   
                        
                        if (isset($data['success']) && $data['success'] == true) {                        
                            $imported++;
                        } 
                    }
                    //print_array($rdata);                   
                }
            
            }
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        
        renderTemplateClean('views/orders/import_update_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            //'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }
    public function bulkUpdateStatus() {
        is_login();
        global $ordersModel; 
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_ids = isset($_POST['order_ids']) ? $_POST['order_ids'] : [];
            $new_status = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';
            //print_array($order_ids);
            //print_array($_POST);
            //exit;
            if (!empty($order_ids) && !empty($new_status)) { 
               $result = $ordersModel->updateStatusBulk($order_ids, $new_status);
               //log status change for each order
               foreach($order_ids as $oid){
                    $logData = [
                        'order_id' => $oid,
                        'status' => 'Status: '.$new_status,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($logData);
                    //call exotic india API to update order status
                    $orderval = $ordersModel->getOrderById($oid);
                    $apidata = [
                        'orderid' => $orderval['order_number'],
                        'level' => 'item',
                        'order_status' => $commanModel->getExoticIndiaOrderStatusCode($new_status)['admin_id'],
                        'size' => trim($orderval['size']),
                        'color' => trim($orderval['color']),
                        'itemcode' => trim($orderval['item_code'])
                    ];
                    //run update if admin id not 0
                    if ($apidata['order_status'] > 0) {
                        $resp = $commanModel->updateExoticIndiaOrderStatus($apidata);
                    }
                    //notify agent if assigned
                    $orderval = $ordersModel->getOrderById($oid);
                    if(!empty($orderval['agent_id']) && $orderval['agent_id'] > 0){
                        $link = base_url('index.php?page=orders&action=list&'.$oid);
                        insertNotification($orderval['agent_id'], 'Order Status Updated', 'The status of an order assigned to you has been updated. Please check the order details.', $link);
                    }
                }    
                if ($result) {
                    //session poitem array clean

                    echo json_encode($result);
                    //echo json_encode(['success' => true, 'message' => 'Order statuses updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order statuses.']);
                }       
                
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order IDs or status.']);
            }
        }

        exit;
    }
    public function bulkAssignOrder(){
        is_login();
        global $ordersModel; 
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_ids = isset($_POST['poitem']) ? $_POST['poitem'] : [];
            $agent_id = isset($_POST['agent_id']) ? $_POST['agent_id'] : '';
            //print_array($order_ids);
            //print_array($_POST);
            //exit;
            if (!empty($order_ids) && !empty($agent_id)) { 
               $result = $ordersModel->updateAgentBulk($order_ids, $agent_id); 
               //log agent assignment for each order
               $agent_name = $commanModel->getUserNameById($agent_id);
               foreach($order_ids as $oid){
                    $logData = [
                        'order_id' => $oid,
                        'status' => 'Agent: '.$agent_name,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($logData);
                    //set notification to agent
                    $link = base_url('index.php?page=orders&action=list&'.$oid);
                    insertNotification($agent_id, 'Order Assigned', 'You have been assigned a new order. Please check the order details.', $link);
                }   
                if ($result) {
                    //session poitem array clean

                    echo json_encode($result);
                    //echo json_encode(['success' => true, 'message' => 'Order statuses updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order statuses.']);
                }       
                
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order IDs or status.']);
            }
        }
    }
    public function invoiceList() {
        is_login();
        global $poInvoiceModel;       
        // $limit = 50;
        // $offset = 0;
        // if (isset($_GET['limit'])) {
        //     $limit = intval($_GET['limit']);
        // }
        // if (isset($_GET['offset'])) {
        //     $offset = intval($_GET['offset']);
        // }
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;
        //search filters
        $filters = [];
        if (isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = intval($_GET['vendor_id']);
        }
        if (isset($_GET['invoice_date_from']) && !empty($_GET['invoice_date_from'])) {
            $filters['invoice_date_from'] = $_GET['invoice_date_from'];
        }
        if (isset($_GET['invoice_date_to']) && !empty($_GET['invoice_date_to'])) {
            $filters['invoice_date_to'] = $_GET['invoice_date_to'];
        }
        //amount range filter
        if (isset($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
            $filters['amount_min'] = floatval($_GET['amount_min']);
        }
        if (isset($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
            $filters['amount_max'] = floatval($_GET['amount_max']);
        }
        //po number filter
        if (isset($_GET['po_number']) && !empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        //utr filter
        if (isset($_GET['utr_number']) && !empty($_GET['utr_number'])) {
            $filters['utr_number'] = $_GET['utr_number'];
        }

        $invoices = $poInvoiceModel->getAllInvoices($limit, $offset, $filters);
        $total_orders = $poInvoiceModel->getTotalInvoices(0, 0, $filters);
        //foreach invoice get po items
        foreach($invoices as $id => $invoice){
            $items = $poInvoiceModel->getPOsByInvoiceId($invoice['id']);
            $invoices[$id]['items'] = $items;            
        }      
        //print_array($invoices);

        renderTemplate('views/purchase_orders/invoice_list.php', ['invoices' => $invoices, 'total_orders' => $total_orders], 'Purchase Order Invoices');
    }
    public function paymentList() {
        is_login();
        global $poInvoiceModel;
        // $limit = 50;
        // $offset = 0;
        // if (isset($_GET['limit'])) {
        //     $limit = intval($_GET['limit']);
        // }
        // if (isset($_GET['offset'])) {
        //     $offset = intval($_GET['offset']);
        // }
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;
        //filters 
        $filters = [];
        if (isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = intval($_GET['vendor_id']);
        }
        if (isset($_GET['payment_date_from']) && !empty($_GET['payment_date_from'])) {
            $filters['payment_date_from'] = $_GET['payment_date_from'];
        }
        if (isset($_GET['payment_date_to']) && !empty($_GET['payment_date_to'])) {
            $filters['payment_date_to'] = $_GET['payment_date_to'];
        }
        //amount range filter
        if (isset($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
            $filters['amount_min'] = floatval($_GET['amount_min']);
        }
        if (isset($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
            $filters['amount_max'] = floatval($_GET['amount_max']);
        }
        //po number filter
        if (isset($_GET['po_number']) && !empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        //utr filter
        if (isset($_GET['utr_number']) && !empty($_GET['utr_number'])) {
            $filters['utr_number'] = $_GET['utr_number'];
        }    

        $payments = $poInvoiceModel->getAllPayments($limit, $offset, $filters);
        $total_payments = $poInvoiceModel->getTotalPayments(0, 0, $filters);
        //print_array($payments);
        renderTemplate('views/purchase_orders/payment_list.php', ['payments' => $payments, 'total_payments' => $total_payments], 'Payments List');
    }    
}
?>

               