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
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page
        $offset = ($page - 1) * $limit;

        $orders = $ordersModel->getAllOrders();
        $total_orders = count($orders);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Paginate orders
        $orders = array_slice($orders, $offset, $limit);
        // Render the orders view
        renderTemplate('views/orders/index.php', [
            'orders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page
        ], 'Manage Orders');
    }
        
    public function viewOrder() {
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
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
    
        // Set your date range (example: last 7 days)
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
                'item_code' => $item['itemcode'] ?? '',
                'size' => $item['size'] ?? '',
                'color' => $item['color'] ?? '',
                'description' => $item['description'] ?? '',
                'image' => $item['image'] ?? '',
                'marketplace_vendor' => $item['marketplace_vendor'] ?? '',
                'quantity' => $item['qty'] ?? '',
                'gst' => $item['gst'] ?? '',
                'hsn' => $item['hscode'] ?? '',
                'options' => $item['options'] ?? 0,
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
        
        renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder
        ], 'Import Orders Result');
    }
    public function createPurchaseOrder() {
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

               