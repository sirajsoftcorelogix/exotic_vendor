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
        $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes=SRB69,'.$_GET['itemCode']; // Production API new endpoint
       
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
}