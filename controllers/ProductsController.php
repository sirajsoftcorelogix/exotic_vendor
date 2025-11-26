<?php
require_once 'models/product/product.php';
$productModel = new product($conn);
class ProductsController {
    public function product_list() {
        is_login();
        global $productModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Users per page, default 50
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // If user select value from dropdown
        $offset = ($page_no - 1) * $limit;

        $products_data = $productModel->getAllProducts($limit, $offset, $search);
        // Assuming a method countAllProducts exists to get total count
        $total_records = $productModel->countAllProducts($search);

        $data = [
            'products' => $products_data,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit

        ];
        renderTemplate('views/products/index.php', $data, 'Products');
    }

    public function viewProduct() {
        is_login();
        global $productModel;
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        $data = [];
        if ($id > 0) {
            $data['product'] = $productModel->getProduct($id);
        }
        renderTemplate('views/products/viewProduct.php', $data, 'Product Details');
    }
    public function updateApiCall(){
        is_login();
        global $productModel;
        //echo "Update API called"; exit;
        if(empty($_GET['itemCode'])){
            echo json_encode(['success' => false, 'message' => 'itemcode invalid to update product.']);
            exit;
        }
        $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes='.$_GET['itemCode']; // Production API new endpoint
       
        // if (!empty($_GET['item_code'])) {
        //     $postData = [
        //         'makeRequestOf' => 'vendors-orderjson',
        //         'item_code' => $_GET['item_code']
        //     ];
        // }

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
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

        $product = json_decode($response, true);
        if (!is_array($product)) {
            //echo "Invalid API response format.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'Invalid API response format.']], 'API Error');
            return;
        }
        // print_array($product);
        // exit;
        if (empty($product)) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'success','text'=>'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        // Process and save orders to the database
        $updatedCount = $productModel->updateProductFromApi($product);
        echo json_encode($updatedCount);
        exit;
        // if ($updatedCount['success']) {
        //     renderTemplateClean('views/success/success.php', ['message' => 'Product updated successfully. Total products updated: ' . $updatedCount['updated_count']], 'Update Successful');
        // } else {
        //     renderTemplateClean('views/errors/error.php', ['message' => $updatedCount['message']], 'Update Failed');
        // }
    }
    public function importApiCall() {
        global $productModel;
        // Accept JSON body or form-data
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!$payload || !isset($payload['itemCodes'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid request.']);
            exit;
        }
        $itemCodes = $payload['itemCodes'];
        if (!is_array($itemCodes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid itemCodes.']);
            exit;
        }
        // Filter and normalize
        $codes = array_values(array_unique(array_map('trim', array_filter($itemCodes))));
        $codes = array_filter($codes);
        if (count($codes) === 0) {
            echo json_encode(['success' => false, 'message' => 'No item codes provided.']);
            exit;
        }
        if (count($codes) > 50) {
            echo json_encode(['success' => false, 'message' => 'Maximum 50 SKUs allowed.']);
            exit;
        }
        
        //exit;
        $created = 0;
        $updated = 0;
        $failed = [];
        echo $itm = implode(", ", $codes);
            //foreach ($codes as $code) {
            // Call vendor API
            $apiUrl = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes=' . implode(", ", $codes);
        $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes='.$itm; // Production API new endpoint
       
        // if (!empty($_GET['item_code'])) {
        //     $postData = [
        //         'makeRequestOf' => 'vendors-orderjson',
        //         'item_code' => $_GET['item_code']
        //     ];
        // }

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];
        echo "Importing ".count($codes)." item codes.<br/>".gettype($itm)."<br/>". implode(", ", $codes);

        // Initialize cURL
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_POST, true);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $json = curl_exec($ch);
        $error = curl_error($ch);
            print_array($json);
            echo "<br/>".$error;
        
            exit;
            // Map fields returned by API to your DB columns
            // Expected structure from API might differ; adapt mapping as needed
            // Example mapping:
            $apiData = $json['data'] ?? $json; // adjust to actual response
            $item = [];
            $item['item_code'] = $apiData['itemcode'];
            $item['title'] = $apiData['title'] ?? ($apiData['title'] ?? '');
            $item['image'] = $apiData['image'] ?? $apiData['image'] ?? '';
            $item['local_stock'] = isset($apiData['local_stock']) ? (int)$apiData['local_stock'] : 0;
            $item['itemprice'] = isset($apiData['price']) ? floatval($apiData['price']) : (isset($apiData['itemprice']) ? floatval($apiData['itemprice']) : 0);
            $item['finalprice'] = isset($apiData['finalprice']) ? $apiData['finalprice'] : $item['itemprice'];
            $item['vendor'] = $apiData['vendor'] ?? $apiData['vendor'] ?? '';
            // add other fields as needed

            // check if product exists
            $existing = $productModel->findByItemCode($item['item_code']);
            if ($existing) {
                // build update data
                $updateData = [
                    'title' => $item['title'],
                    'image' => $item['image'],
                    'local_stock' => $item['local_stock'],
                    'itemprice' => $item['itemprice'],
                    'finalprice' => $item['finalprice'],
                    'vendor' => $item['vendor'],
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $ok = $productModel->updateProduct($existing['id'], $updateData);
                if ($ok) $updated++;
                else $failed[] = $code;
            } else {
                $insertData = [
                    'item_code' => $item['item_code'],
                    'title' => $item['title'],
                    'image' => $item['image'],
                    'local_stock' => $item['local_stock'],
                    'itemprice' => $item['itemprice'],
                    'finalprice' => $item['finalprice'],
                    'vendor' => $item['vendor'],
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $id = $productModel->createProduct($insertData);
                if ($id) $created++;
                else $failed[] = $code;
            }

            // tiny sleep to be gentle on third-party API
            usleep(100000); // 100ms
        //}

        echo json_encode(['success' => true, 'created' => $created, 'updated' => $updated, 'failed' => $failed]);
        exit;
    }
}