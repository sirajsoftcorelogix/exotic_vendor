<?php
require_once 'models/product/product.php';
$productModel = new product($conn);
class ProductsController {
    public function product_list() {
        is_login();
        global $productModel;
        //$search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Users per page, default 50
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // If user select value from dropdown
        $offset = ($page_no - 1) * $limit;
        //Advanced Search Filters
        $filters = [];
        if (!empty($_GET['item_code'])) {
            $filters['item_code'] = $_GET['item_code'];            
        }        
        if (!empty($_GET['item_name'])) {
            $filters['title'] = $_GET['item_name'];            
        }
        if (!empty($_GET['vendor_name']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_name'] = $_GET['vendor_name'];            
        }
        $products_data = $productModel->getAllProducts($limit, $offset, $filters);
        // Assuming a method countAllProducts exists to get total count
        $total_records = $productModel->countAllProducts($filters);
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
        // prepare comma separated itemcodes (no spaces)
        $itm = implode(',', $codes);
        $url = 'https://www.exoticindia.com/vendor-api/product/fetch?itemcodes=' . urlencode($itm);

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        // Initialize cURL
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            echo json_encode(['success' => false, 'message' => 'API request failed: ' . $error]);
            exit;
        }

        $apiResult = json_decode($response, true);
        if (!is_array($apiResult)) {
            echo json_encode(['success' => false, 'message' => 'Invalid API response format.']);
            exit;
        }
        // print_array($apiResult);
        // exit;
        // Normalize to an array of items (API may return data or a list or a single object)
        /*if (isset($apiResult['data']) && is_array($apiResult['data'])) {
            $items = $apiResult['data'];
        } elseif (isset($apiResult['items']) && is_array($apiResult['items'])) {
            $items = $apiResult['items'];
        } elseif (array_values($apiResult) === $apiResult && count($apiResult) > 0) {
            // numeric indexed array
            $items = $apiResult;
        } else {
            // single object
            $items = [$apiResult];
        }*/
        $items = $apiResult; 
        //print_array($items);
        if (count($items) === 0) {
            echo json_encode(['success' => false, 'message' => 'No items found in API response.']);
            exit;
        }
        
        foreach ($items as $apiItem) {
            // detect item code from common keys
            $code = trim($apiItem['itemcode'] ?? $apiItem['item_code'] ?? $apiItem['sku'] ?? '');
            if ($code === '') {
            $failed[] = '(unknown)';
            continue;
            }

            // map API item to DB fields (adjust as needed)
            $item = [];
            $item['item_code'] = $code;
            $item['sku'] = $apiItem['sku'] ?? '';
            $item['title'] = $apiItem['title'] ?? '';
            $item['image'] = 'https://cdn.exoticindia.com/images/products/original/'.$apiItem['image'] ?? '';
            $item['groupname'] = $apiItem['groupname'] ?? '';
            $item['local_stock'] = isset($apiItem['local_stock']) ? (int)$apiItem['local_stock'] : (isset($apiItem['stock']) ? (int)$apiItem['stock'] : 0);
            $item['itemprice'] = isset($apiItem['price']) ? floatval($apiItem['price']) : (isset($apiItem['itemprice']) ? floatval($apiItem['itemprice']) : 0.0);
            $item['finalprice'] = isset($apiItem['finalprice']) ? floatval($apiItem['finalprice']) : $item['itemprice'];
            $item['color'] = $apiItem['color'] ?? '';
            $item['size'] =  isset($apiItem['size']) ? $apiItem['size'] : '';
            $item['material'] = isset($apiItem['material']) ? $apiItem['material'] : '';
            $item['cost_price'] = isset($apiItem['cost_price']) ? (float)$apiItem['cost_price'] : 0.0;           
            $item['gst'] = isset($apiItem['gst']) ? (float)$apiItem['gst'] : 0.0;
            $item['hsn'] = isset($apiItem['hscode']) ? $apiItem['hscode'] : '';
            $item['description'] = isset($apiItem['snippet_description']) ? $apiItem['snippet_description'] : '';
            $item['asin'] = isset($apiItem['asin']) ? $apiItem['asin'] : '';
            $item['upc'] = isset($apiItem['upc']) ? $apiItem['upc'] : '';
            $item['location'] = isset($apiItem['location']) ? $apiItem['location'] : '';
            $item['fba_in'] = isset($apiItem['fba_in']) ? (int)$apiItem['fba_in'] : 0;
            $item['fba_us'] = isset($apiItem['fba_us']) ? (int)$apiItem['fba_us'] : 0;
            $item['leadtime'] = isset($apiItem['leadtime']) ? $apiItem['leadtime'] : '';
            $item['instock_leadtime'] = isset($apiItem['instock_leadtime']) ? $apiItem['instock_leadtime'] : '';
            $item['permanently_available'] = isset($apiItem['permanently_available']) ? (int)$apiItem['permanently_available'] : 0;
            $item['numsold'] = isset($apiItem['numsold']) ? (int)$apiItem['numsold'] : 0;
            $item['numsold_india'] = isset($apiItem['numsold_india']) ? (int)$apiItem['numsold_india'] : 0;
            $item['numsold_global'] = isset($apiItem['numsold_global']) ? (int)$apiItem['numsold_global'] : 0;
            $item['lastsold'] = isset($apiItem['lastsold']) ? $apiItem['lastsold'] : '';
            $item['vendor'] = isset($apiItem['vendor']) ? $apiItem['vendor'] : '';
            $item['shippingfee'] = isset($apiItem['shippingfee']) ? (float)$apiItem['shippingfee'] : 0.0;
            $item['sourcingfee'] = isset($apiItem['sourcingfee']) ? (float)$apiItem['sourcingfee'] : 0.0;
            $item['price'] = isset($apiItem['price']) ? (float)$apiItem['price'] : 0.0;
            $item['price_india'] = isset($apiItem['price_india']) ? (float)$apiItem['price_india'] : 0.0;
            $item['price_india_suggested'] = isset($apiItem['price_india_suggested']) ? (float)$apiItem['price_india_suggested'] : 0.0;
            $item['mrp_india'] = isset($apiItem['mrp_india']) ? (float)$apiItem['mrp_india'] : 0.0;
            $item['permanent_discount'] = isset($apiItem['permanent_discount']) ? (float)$apiItem['permanent_discount'] : 0.0;
            $item['discount_global'] = isset($apiItem['discount_global']) ? (float)$apiItem['discount_global'] : 0.0;
            $item['discount_india'] = isset($apiItem['discount_india']) ? (float)$apiItem['discount_india'] : 0.0;
            $item['product_weight'] = isset($apiItem['product_weight']) ? (float)$apiItem['product_weight'] : 0.0;
            $item['product_weight_unit'] = isset($apiItem['product_weight_unit']) ? $apiItem['product_weight_unit'] : '';
            $item['prod_height'] = isset($apiItem['prod_height']) ? (int)$apiItem['prod_height'] : 0;
            $item['prod_width'] = isset($apiItem['prod_width']) ? (int)$apiItem['prod_width'] : 0;
            $item['prod_length'] = isset($apiItem['prod_length']) ? (int)$apiItem['prod_length'] : 0;
            $item['length_unit'] = isset($apiItem['length_unit']) ? $apiItem['length_unit'] : '';
            $item['created_at'] = date('Y-m-d H:i:s');
            $item['updated_at'] = date('Y-m-d H:i:s');

            // check if product exists
            // if(!empty($item['sku'])){
            //     $existing = $productModel->findBySku($item['sku']);
            // }else{
                $existing = $productModel->findByItemCodeSizeColor($item['item_code'], $item['size'], $item['color']);
            //}
            
            
            if ($existing) {            
            $ok = $productModel->updateProduct($existing['id'], $item);
            if ($ok) $updated++;
            else $failed[] = $code;
            } else {            
            $id = $productModel->createProduct($item);
            if ($id) $created++;
            else $failed[] = $code;
            }
            // echo "Processed item code: " . $code . "\n";
            // echo "Created: $created, Updated: $updated, Failed: " . count($failed) . "\n";
            // print_r($existing);
            //varient
            if (isset($apiItem['variations'])) {
                foreach ($apiItem['variations'] as $variant) {
                    $variantItem = $item; // start with base item
                    $variantItem['item_code'] = $apiItem['itemcode'] ?? $apiItem['item_code'];
                    $variantItem['sku'] = $variant['sku'] ?? '';
                    $variantItem['size'] = $variant['size'] ?? '';
                    $variantItem['color'] = $variant['color'] ?? '';
                    $variantItem['title'] = $variant['title'] ?? $item['title'];                    
                    $variantItem['local_stock'] = isset($variant['local_stock']) ? (int)$variant['local_stock'] : 0;
                    $variantItem['itemprice'] = isset($variant['price']) ? floatval($variant['price']) : 0.0;
                    $variantItem['finalprice'] = isset($variant['finalprice']) ? floatval($variant['finalprice']) : $variantItem['itemprice'];
                    $variantItem['cost_price'] = isset($variant['cost_price']) ? (float)$variant['cost_price'] : 0.0;
                    $variantItem['gst'] = isset($variant['gst']) ? (float)$variant['gst'] : 0.0;
                    $variantItem['hsn'] = isset($variant['hsn']) ? $variant['hsn'] : '';
                    $variantItem['description'] = isset($variant['snippet_description']) ? $variant['snippet_description'] : '';
                    $variantItem['image'] = isset($variant['image']) ? 'https://cdn.exoticindia.com/images/products/original/'.$variant['image'] : $item['image'];
                    $variantItem['asin'] = isset($variant['asin']) ? $variant['asin'] : '';
                    $variantItem['upc'] = isset($variant['upc']) ? $variant['upc'] : '';
                    $variantItem['location'] = isset($variant['location']) ? $variant['location'] : '';
                    $variantItem['fba_in'] = isset($variant['fba_in']) ? (int)$variant['fba_in'] : 0;
                    $variantItem['fba_us'] = isset($variant['fba_us']) ? (int)$variant['fba_us'] : 0;
                    $variantItem['leadtime'] = isset($variant['leadtime']) ? (int)$variant['leadtime'] : 0;
                    $variantItem['instock_leadtime'] = isset($variant['instock_leadtime']) ? (int)$variant['instock_leadtime'] : 0;
                    $variantItem['permanently_available'] = isset($variant['permanently_available']) ? (int)$variant['permanently_available'] : 0;
                    $variantItem['numsold'] = isset($variant['numsold']) ? (int)$variant['numsold'] : 0;
                    $variantItem['numsold_india'] = isset($variant['numsold_india']) ? (int)$variant['numsold_india'] : 0;
                    $variantItem['numsold_global'] = isset($variant['numsold_global']) ? (int)$variant['numsold_global'] : 0;
                    $variantItem['lastsold'] = isset($variant['lastsold']) ? (int)$variant['lastsold'] : 0;
                    $variantItem['vendor'] = isset($variant['vendor']) ? $variant['vendor'] : '';
                    $variantItem['shippingfee'] = isset($variant['shippingfee']) ? (float)$variant['shippingfee'] : 0.0;
                    $variantItem['sourcingfee'] = isset($variant['sourcingfee']) ? (float)$variant['sourcingfee'] : 0.0;
                    $variantItem['price'] = isset($variant['price']) ? (float)$variant['price'] : 0.0;
                    $variantItem['price_india'] = isset($variant['price_india']) ? (float)$variant['price_india'] : 0.0;
                    $variantItem['price_india_suggested'] = isset($variant['price_india_suggested']) ? (float)$variant['price_india_suggested'] : 0.0;
                    $variantItem['mrp_india'] = isset($variant['mrp_india']) ? (float)$variant['mrp_india'] : 0.0;
                    $variantItem['permanent_discount'] = isset($variant['permanent_discount']) ? (float)$variant['permanent_discount'] : 0.0;
                    $variantItem['discount_global'] = isset($variant['discount_global']) ? (float)$variant['discount_global'] : 0.0;
                    $variantItem['discount_india'] = isset($variant['discount_india']) ? (float)$variant['discount_india'] : 0.0;
                    $variantItem['created_at'] = date('Y-m-d H:i:s');
                    $variantItem['updated_at'] = date('Y-m-d H:i:s');
                    // check if variant exists
                    $existingVar = $productModel->findByItemCodeSizeColor($variantItem['item_code'], $variantItem['size'], $variantItem['color']);
                    if ($existingVar) {            
                        $ok = $productModel->updateProduct($existingVar['id'], $variantItem);
                        if ($ok) $updated++;
                        else $failed[] = $variantItem['item_code'];
                    } else {            
                        $id = $productModel->createProduct($variantItem);
                        if ($id) $created++;
                        else $failed[] = $variantItem['item_code'];
                    }
                }
            }

            // tiny sleep to be gentle on third-party API/service
            usleep(100000); // 100ms
        }

        echo json_encode(['success' => true, 'message' => 'Products processed successfully', 'created' => $created, 'updated' => $updated, 'failed' => $failed]);
        exit;
    }
    public function getProductDetailsHTML() {
        is_login();
        global $productModel, $commanModel;
        //print_array($_GET);
        $item_code = isset($_GET['item_code']) ? $_GET['item_code'] : 0;
        if ($item_code != 0) {
            $order = $productModel->getProductByItemCode($item_code);
            
            if ($order) {
                //renderPartial('views/products/partial_product_details.php', ['products' => $order]);
                renderTemplateClean('views/products/partial_product_details.php', ['products' => $order], 'Product Details');
            } else {
                echo '<p>Order details not found.</p>';
            }
        } else {
            echo '<p>Invalid Order Number.</p>';
        }
        exit;
    }
    
}