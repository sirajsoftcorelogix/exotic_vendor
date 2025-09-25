<?php 
require_once 'models/order/order.php';
$ordersModel = new Order($conn);
global $root_path;
global $domain;
class OrdersController { 
     
    public function index() {
        is_login();
        global $ordersModel;
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
        if (!empty($_GET['status']) && in_array($_GET['status'], ['all', 'processed', 'pending', 'cancelled'])) {
            $filters['status_filter'] = $_GET['status'];
        } else {
            $filters['status_filter'] = 'all';
        }
       

        // Use pagination in the database query for better performance
        $orders = $ordersModel->getAllOrders($filters, $limit, $offset);
        $total_orders = $ordersModel->getOrdersCount($filters);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Render the orders view
        renderTemplate('views/orders/index.php', [
            'orders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page
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
        is_login();
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
         
        //log create
        $log_data = ['start_time' => date('Y-m-d H:i:s')];
        $log_id = 0;
		
        if($logs = $ordersModel->orderImportLog($log_data)){
            $log_id = $logs['insert_id'];
        }        // Set your date range (example: last 7 days)

        $from_date = strtotime('-1 days');
        //echo "<br>";
        $to_date = time();

        $url = 'https://www.exoticindia.com/action';
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'from_date' => $from_date,
            'to_date' => $to_date
        ];

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
        //print_r($orders);
        //exit;
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
                $data = [
                'order_number' => $order['orderid'] ?? '',
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
                'cost_price' => $item['cp'] ?? '',
                'location' => $item['location'] ?? '',
                'order_date' => date('Y-m-d H:i:s', strtotime($order['orderdate'] ?? 'now')),
                 ];
                $totalorder++;
                }
                // Add other fields as needed
           
            //echo "<br>";
            //print_r($data);
            // echo "<br>";
           
            //$totalorder = count($data);
            $rdata = $ordersModel->insertOrder($data);
            $result[] = $rdata;
            //print_array($rdata);
            //if ($result){
                if (isset($rdata['success']) && $rdata['success'] == 1) {
                    // Handle error case
                    //renderTemplateClean('views/errors/error.php', ['message' => $result['message']], 'Import Error');
                    //return;
                     $imported++;
                }
            //} else {
            //     renderTemplateClean('views/errors/error.php', ['message' => 'Failed to insert order.'], 'Import Error');                
            //     return;
            // }
           
        }
        //print_r($result);
        //update log end time and imported count
        if($log_id > 0){
            $log_update_data = [
                'end_time' => date('Y-m-d H:i:s'),
                'successful_imports' => $imported,
                'total_orders' => $totalorder,
                'error' => isset($error) ? $error : '',
                'log_details' => json_encode($result)
            ];
            $ordersModel->updateOrderImportLog($log_id, $log_update_data);
        }
        renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder
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
}
?>

               