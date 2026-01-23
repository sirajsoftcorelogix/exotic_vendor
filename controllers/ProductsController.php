<?php
require_once 'models/product/product.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
$productModel = new product($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);
class ProductsController {
    public function product_list() {
        is_login();
        global $productModel;
        global $usersModel;
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
            'user' => $usersModel->getAllUsers(),
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
            $item['cost_price'] = isset($apiItem['cp']) ? (float)$apiItem['cp'] : 0.0;           
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
                    $variantItem['cost_price'] = isset($variant['cp']) ? (float)$variant['cp'] : 0.0;
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
        $type = isset($_GET['type']) ? $_GET['type'] : 'inner';
        if ($item_code != 0) {
            $order = $productModel->getProductByItemCode($item_code);
            
            if ($order) {
                if ($type === 'inner')
                    renderPartial('views/products/partial_product_details.php', ['products' => $order]);
                else
                    renderTemplateClean('views/products/other_partial_product_details.php', ['products' => $order], 'Product Details');
                //renderPartial('views/products/partial_product_details.php', ['products' => $order]);
                //renderTemplateClean('views/products/partial_product_details.php', ['products' => $order], 'Product Details');
            } else {
                echo '<p>Order details not found.</p>';
            }
        } else {
            echo '<p>Invalid Order Number.</p>';
        }
        exit;
    }
    public function addVendorMap() {
        is_login();
        global $productModel;
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!$payload) $payload = $_POST;
        $item_code = isset($payload['item_code']) ? trim($payload['item_code']) : '';
        $vendor_id = isset($payload['vendor_id']) ? (int)$payload['vendor_id'] : 0;
        $vendor_code = isset($payload['vendor_code']) ? trim($payload['vendor_code']) : '';
        if ($item_code == '' || $vendor_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }
        $ok = $productModel->saveProductVendor($item_code, $vendor_id, $vendor_code);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Vendor mapping saved.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save vendor mapping.']);
        }
        exit;
    }
    public function getVendorEditForm() {
        is_login();
        global $productModel;
        global $usersModel;
        //print_array($_GET);
        $item_code = isset($_GET['item_code']) ? $_GET['item_code'] : 0;
        $current_vendor = isset($_GET['current_vendor']) ? $_GET['current_vendor'] : '';
        $users = $usersModel->getAllUsers();
        //print_array($users);
        if ($item_code != 0) {
            $vendors = $productModel->getVendorByItemCode($item_code);
            renderTemplateClean('views/products/partial_vendor_edit_form.php', ['vendors' => $vendors, 'current_vendor' => $current_vendor, 'item_code' => $item_code, 'users' => $users], 'Edit Vendor');
            
        } else {
            echo '<p>Invalid Item Code.</p>';
        }
        exit;
    }
    public function removeVendorMapping() {
        is_login();
        global $productModel;
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!$payload) $payload = $_POST;
        $item_code = isset($payload['item_code']) ? trim($payload['item_code']) : '';
        $vendor_id = isset($payload['vendor_id']) ? (int)$payload['vendor_id'] : 0;
        $id = isset($payload['id']) ? (int)$payload['id'] : 0;
        // if ($item_code == '' || $vendor_id <= 0) {
        //     echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
        //     exit;
        // }
        $ok = $productModel->deleteProductVendor($id);
        if ($ok) {
            echo json_encode(['success' => true, 'message' => 'Vendor mapping removed.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove vendor mapping.']);
        }
        exit;
    }
    
    public function updatePriority() {
        // Update vendor priority (AJAX)
        is_login();
        global $productModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $priority = isset($_POST['priority']) ? (int)$_POST['priority'] : 0;
        if ($id <= 0 || $priority < 1 || $priority > 5) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        //print_r($_POST);
        $res = $productModel->updatePriority($id, $priority);
        echo json_encode($res);
        exit;
    }
    
    public function createPurchaseList()
    {
        is_login();
        global $productModel;

        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            exit;
        }

        $agent_id = (int)($_POST['agent_id'] ?? 0);

        // From your form
        $orderIds = (isset($_POST['order_ids']) && is_array($_POST['order_ids'])) ? $_POST['order_ids'] : [];
        $skus     = (isset($_POST['sku']) && is_array($_POST['sku'])) ? $_POST['sku'] : [];

        // quantity can be either:
        // 1) quantity[ORDER_ID] => qty  (recommended)
        // 2) quantity[] aligned with sku[] (fallback)
        $qtysRaw = $_POST['quantity'] ?? [];

        // Date purchased (keep date only if HTML input type=date; else datetime ok)
        $date_purchased = !empty($_POST['date_purchased']) ? $_POST['date_purchased'] : date('Y-m-d H:i:s');

        if ($agent_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select an agent.']);
            exit;
        }

        if (empty($orderIds) && empty($skus)) {
            echo json_encode(['success' => false, 'message' => 'No items selected.']);
            exit;
        }

        $created = 0;
        $failed  = [];
        
        //print_array($orderIds); die;
        // Build a list of items to insert
        // Prefer mapping by order_ids (because quantity is keyed by orderId)
        if (!empty($orderIds)) {
            foreach ($orderIds as $idx => $oid) {
                $oid = (string)$oid;

                $sku = isset($skus[$idx]) ? trim($skus[$idx]) : '';
                if ($sku === '') {
                    $failed[] = ['order_id' => $oid, 'message' => 'SKU missing'];
                    continue;
                }

                $product = $productModel->getProductByskuExact($sku);
                if (!$product || empty($product['id'])) {
                    $failed[] = ['order_id' => $oid, 'sku' => $sku, 'message' => 'Product not found for SKU'];
                    continue;
                }

                // quantity[orderId] preferred
                $qty = 1;
                if (is_array($qtysRaw) && array_key_exists($oid, $qtysRaw)) {
                    $qty = (int)$qtysRaw[$oid];
                } elseif (is_array($qtysRaw) && isset($qtysRaw[$idx])) {
                    // fallback if quantity[] came as indexed array
                    $qty = (int)$qtysRaw[$idx];
                }

                if ($qty < 1) $qty = 1;

                $data = [
                    'user_id'        => $agent_id,
                    'product_id'     => (int)$product['id'],
                    'order_id'       => $oid,
                    'sku'            => $sku,
                    'date_purchased' => $date_purchased,
                    'status'         => 'pending',
                    'edit_by'        => (int)($_SESSION['user']['id'] ?? 0),
                    'quantity'       => $qty
                ];

                //print_array($data); die;

                $res = $productModel->createPurchaseList($data);

                if ($res && !empty($res['success'])) {
                    $created++;
                } else {
                    $failed[] = [
                        'order_id' => $oid,
                        'product_id' => (int)$product['id'],
                        'sku' => $sku,
                        'message' => ($res['message'] ?? 'Insert failed')
                    ];
                }
            }
        } else {
            // If order_ids not posted, insert based on sku[] only
            foreach ($skus as $idx => $sku) {
                $sku = trim($sku);
                if ($sku === '') continue;

                $product = $productModel->getProductByskuExact($sku);
                if (!$product || empty($product['id'])) {
                    $failed[] = ['sku' => $sku, 'message' => 'Product not found for SKU'];
                    continue;
                }

                $qty = 1;
                if (is_array($qtysRaw) && isset($qtysRaw[$idx])) {
                    $qty = (int)$qtysRaw[$idx];
                }
                if ($qty < 1) $qty = 1;

                $data = [
                    'user_id'        => $agent_id,
                    'product_id'     => (int)$product['id'],
                    'order_id'       => '',
                    'sku'            => $sku,
                    'date_purchased' => $date_purchased,
                    'status'         => 'pending',
                    'edit_by'        => (int)($_SESSION['user']['id'] ?? 0),
                    'quantity'       => $qty
                ];

                $res = $productModel->createPurchaseList($data);

                if ($res && !empty($res['success'])) {
                    $created++;
                } else {
                    $failed[] = ['sku' => $sku, 'message' => ($res['message'] ?? 'Insert failed')];
                }
            }
        }

        echo json_encode([
            'success' => true,
            'created' => $created,
            'failed'  => $failed
        ]);
        exit;
    }


    public function masterPurchaseList() {
        is_login();
        global $productModel;
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Users per page, default 50
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // If user select value from dropdown
        $offset = ($page_no - 1) * $limit;
        // Filters: category and status
        $filters = [];
        $filters['status'] = isset($_GET['status']) ? $_GET['status'] : 'pending';
        $filters['category'] = isset($_GET['category']) ? $_GET['category'] : 'all';
        //search
        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }

        //sorting
        $filters['sort_by'] = ($_GET['sort_by_date'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';

        //added By filter
        if (!empty($_GET['added_by'])) {
            $filters['added_by'] = (int)$_GET['added_by'];
        }

        //asigned to filter
        if (!empty($_GET['asigned_to'])) {
            $filters['asigned_to'] = (int)$_GET['asigned_to'];
        }

        //date range filter
        if(!empty($_GET['date_type'])){
            $filters['date_type'] = $_GET['date_type'];
        }
        if(!empty($_GET['date_from'])){
            $filters['date_from'] = $_GET['date_from'];
        }
        if(!empty($_GET['date_to'])){
            $filters['date_to'] = $_GET['date_to']; 
        }
        
        // fetch purchase list and count
        $purchase_data = $productModel->getPurchaseList($limit, $offset, $filters);
        $total_records = $productModel->countPurchaseList($filters);
        //print_array($purchase_data);
        //exit;
        //enrich each purchase row with product details and agent name
        global $commanModel;
        $enriched = [];
        foreach ($purchase_data as $row) {
            //$product = $productModel->getProduct($row['product_id']);
            $agent_name = $commanModel->getUserNameById($row['user_id']);
            $enriched[] = array_merge($row, [
                //'product' => $product,                
                'added_by' => $commanModel->getUserNameById($row['edit_by']),
                'agent_name' => $agent_name,
                'date_added_readable' => date('d M Y', strtotime($row['date_added'])),
                'date_purchased_readable' => ($row['date_purchased'] != '0000-00-00' && $row['date_purchased'] !== NULL) ? date('d M Y', strtotime($row['date_purchased'])) : 'N/A'
            ]);
        }
        
        $data = [
            'categories' => getCategories(),
            'purchase_list' => $enriched,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit,
            'staff_list' => $commanModel->get_staff_list(),
        ];
        renderTemplate('views/products/master_purchase_list.php', $data, 'Master Purchase List');
    }
    public function purchaseList() {
        is_login();
        global $productModel;
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Users per page, default 50
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // If user select value from dropdown
        $offset = ($page_no - 1) * $limit;

        // Filters: category and status
        $filters = [];
        $filters['user_id'] = $_SESSION['user']['id'];
        $filters['status'] = isset($_GET['status']) ? $_GET['status'] : 'pending';
        $filters['category'] = isset($_GET['category']) ? $_GET['category'] : 'all';

        // fetch purchase list and count with filters
        $purchase_data = $productModel->getPurchaseList($limit, $offset, $filters); 
        $total_records = $productModel->countPurchaseList($filters);

        // fetch categories for filter UI
        //$categories = $productModel->getCategories();

        // enrich each purchase row with product details and agent name
        global $commanModel;
        $enriched = [];
        foreach ($purchase_data as $row) {
            $product = $productModel->getProduct($row['product_id']);
            $agent_name = $commanModel->getUserNameById($row['user_id']);
            $enriched[] = array_merge($row, [
                'product' => $product,
                'agent_name' => $agent_name,
                'added_by' => $commanModel->getUserNameById($row['edit_by']),
                'date_added_readable' => date('d M Y', strtotime($row['date_added'])),
                'date_purchased_readable' => ($row['date_purchased'] != '0000-00-00' && $row['date_purchased'] !== NULL) ? date('d M Y', strtotime($row['date_purchased'])) : 'N/A'
            ]);
        }

        $data = [
            'purchase_list' => $enriched,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit,
            'categories' => getCategories(),
            'selected_filters' => $filters
        ];
        // render clean for mobile users
        if (isMobile()){
            renderTemplateClean('views/products/purchase_list.php', $data, 'Purchase List');
            return;
        }else
        renderTemplate('views/products/purchase_list.php', $data, 'Purchase List');
    }

    // Mark a purchase list item as purchased (AJAX)
    public function markPurchased() {
        is_login();
        global $productModel;
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['purchase_list_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }
        $purchase_list_id = (int)$input['purchase_list_id'];
        $user_id = $_SESSION['user']['id'];
        $qty = isset($input['quantity']) ? (int)$input['quantity']:'';
        //$remarks = isset($input['remarks']) ? trim($input['remarks']) : '';
        $res = $productModel->addPurchaseTransaction($purchase_list_id, $qty, $user_id, '');
        echo json_encode($res);
        exit;
    }

    public function markUnPurchased(){
        is_login();
        global $productModel;
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        if (empty($input['purchase_list_id'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }        
        $purchase_list_id = (int)$input['purchase_list_id'];
        $qty =  isset($input['quantity']) ? (int)$input['quantity']:'';
        //$remarks = isset($input['remarks']) ? trim($input['remarks']) : '';
        $user_id = $_SESSION['user']['id'];
        // $res = $productModel->updatePurchaseListStatus($id, 'pending', null);
        $res = $productModel->reversePurchaseTransaction($purchase_list_id, $qty, $user_id, '');
        echo json_encode($res);
        exit;
    }

    // Update quantity and remarks for a purchase list item (AJAX)
    public function updatePurchaseItem() {        
    
        is_login();
        global $productModel;
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true); 
        
        $purchase_list_id = isset($input['id']) ? (int)$input['id'] : 0;
        $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
        $quantity = isset($input['quantity']) ? $input['quantity'] : null;
        //$remarks = isset($input['remarks']) ? trim($input['remarks']) : '';
        $status = isset($input['status']) ? trim($input['status']) : '';
        $expected_time_of_delivery = !empty($input['expected_time_of_delivery'])
                    ? date('Y-m-d', strtotime($input['expected_time_of_delivery']))
                    : '';

        if ($purchase_list_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }
        //$res = $productModel->updatePurchaseItem($id, $quantity, $remarks, $status, $expected_time_of_delivery);
        if($quantity>0){
            $res = $productModel->addPurchaseTransaction($purchase_list_id, $quantity, $_SESSION['user']['id'], $status, $product_id);            
            echo json_encode($res);
            exit;
        } else {
            echo json_encode(['success' => true]);
            exit;
        }        
    }
    public function deletePurchaseItem() {
        is_login();
        global $productModel;
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $id = isset($input['id']) ? (int)$input['id'] : 0;
        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid id']);
            exit;
        }
        $res = $productModel->deletePurchaseItem($id);
        echo json_encode($res);
        exit;
    }
    public function getPurchaseListDetails() {
        is_login();
        global $productModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo '<p>Invalid Purchase Item ID.</p>';
            exit;
        }
        $purchaseItem = $productModel->getPurchaseItemById($id);    
        if (!$purchaseItem) {
            echo '<p>Purchase Item not found.</p>';
            exit;
        }
        //date formatting
        $purchaseItem['date_added_readable'] = date('d M Y', strtotime($purchaseItem['date_added']));
        $purchaseItem['date_purchased_readable'] = $purchaseItem['date_purchased'] ? date('d M Y', strtotime($purchaseItem['date_purchased'])) : '';
        echo json_encode(['success' => true, 'purchaseItem' => $purchaseItem]);
        //renderTemplateClean('views/products/partial_purchase_item_details.php', ['purchaseItem' => $purchaseItem, 'product' => $product], 'Purchase Item Details');
        exit;
    }
}

