<?php 
require_once 'models/order/order.php';
$ordersModel = new Order($conn);
global $root_path;
global $domain;
class OrdersController { 
     
    public function index() {
        $orders = $ordersModel->getAllOrders();
        renderTemplate('views/orders/index.php', ['orders' => $orders], 'Manage Orders');
    }
    public function viewOrder() {
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
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        //print_r($response);
        $error = curl_error($ch);
        curl_close($ch);

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
        $imported = 0;
        foreach ($orders['orders'] as $order) { 
            // Map API fields to your table columns
            $result = $ordersModel->insertOrder([
                'order_number' => $order['orderid'] ?? '',
                'title' => $order['cart']['title'] ?? '',
                'item_code' => $order['cart']['itemcode'] ?? '',
                'size' => $order['cart']['size'] ?? '',
                'color' => $order['cart']['color'] ?? '',
                'description' => $order['cart']['description'] ?? '',
                'image' => $order['cart']['image'] ?? '',
                'marketplace_vendor' => $order['cart']['marketplace_vendor'] ?? '',
                'quantity' => $order['cart']['qty'] ?? '',
                'options' => $order['cart']['options'] ?? 0,
                
                // Add other fields as needed
            ]);
            if ($result) $imported++;
        }

        renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'total' => count($orders)
        ], 'Import Orders Result');
    }
}
?>

               