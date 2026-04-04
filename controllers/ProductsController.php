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
        if (!empty($_GET['item_group'])) {
            $filters['groupname'] = trim($_GET['item_group']);
        }
        if (!empty($_GET['sku'])) {
            $filters['sku'] = trim($_GET['sku']);
        }
        if (isset($_GET['low_stock']) && $_GET['low_stock'] !== '') {
            $filters['low_stock'] = (int)$_GET['low_stock'] ? 1 : 0;
        }
        if (isset($_GET['permanently_available']) && $_GET['permanently_available'] !== '') {
            $filters['permanently_available'] = (int)$_GET['permanently_available'] ? 1 : 0;
        }
        if (!empty($_GET['size'])) {
            $filters['size'] = trim($_GET['size']);
        }
        if (!empty($_GET['color'])) {
            $filters['color'] = trim($_GET['color']);
        }
        if (isset($_GET['local_stock']) && $_GET['local_stock'] !== '') {
            $filters['local_stock'] = (int)$_GET['local_stock'];
        }
        if (!empty($_GET['marketplace'])) {
            $filters['marketplace'] = trim($_GET['marketplace']);
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
    public function stock_transfer_list() {
        is_login();
        global $conn;

        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);

        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50;
        $offset = ($page_no - 1) * $limit;

        $filters = [
            'transfer_order_no' => trim($_GET['transfer_order_no'] ?? ''),
            'dispatch_date' => trim($_GET['dispatch_date'] ?? ''),
            'requested_by' => isset($_GET['requested_by']) ? (int)$_GET['requested_by'] : 0,
            'dispatch_by' => isset($_GET['dispatch_by']) ? (int)$_GET['dispatch_by'] : 0,
            'from_warehouse' => isset($_GET['from_warehouse']) ? (int)$_GET['from_warehouse'] : 0,
            'to_warehouse' => isset($_GET['to_warehouse']) ? (int)$_GET['to_warehouse'] : 0,
            'item_number' => trim($_GET['item_number'] ?? ''),
        ];

        $transferData = $stockTransferModel->listTransfers($limit, $offset, $filters);

        // Pull users for filters
        $users = [];
        $userQuery = "SELECT id, name FROM vp_users WHERE is_active = 1 ORDER BY name ASC";
        $userResult = mysqli_query($conn, $userQuery);
        if ($userResult) {
            while ($row = mysqli_fetch_assoc($userResult)) {
                $users[] = $row;
            }
        }

        // Pull warehouses for filters
        $warehouses = [];
        $warehouseQuery = "SELECT id, address_title FROM exotic_address WHERE is_active = 1 ORDER BY address_title ASC";
        $warehouseResult = mysqli_query($conn, $warehouseQuery);
        if ($warehouseResult) {
            while ($row = mysqli_fetch_assoc($warehouseResult)) {
                $warehouses[] = $row;
            }
        }

        $data = [
            'transfers' => $transferData['records'],
            'page_no' => $page_no,
            'total_records' => $transferData['total'],
            'limit' => $limit,
            'filters' => $filters,
            'users' => $users,
            'warehouses' => $warehouses,
        ];

        renderTemplate('views/products/stock_transfer_list.php', $data, 'Stock Transfer Log');
    }

    public function stock_transfer_edit() {
        is_login();
        global $conn;
        global $usersModel;

        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);

        $transferId = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;
        if ($transferId <= 0) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Invalid transfer id']], 'Error');
            return;
        }

        $transfer = $stockTransferModel->getTransferById($transferId);
        if (!$transfer) {
            renderTemplate('views/errors/error.php', ['message' => ['type' => 'error', 'text' => 'Stock transfer not found']], 'Error');
            return;
        }

        // Fallback for missing transfer_order_no in old records
        if (empty($transfer['transfer_order_no']) && !empty($transfer['from_warehouse']) && !empty($transfer['to_warehouse'])) {
            $transfer['transfer_order_no'] = $stockTransferModel->generateUniqueTransferOrderNo((int)$transfer['from_warehouse'], (int)$transfer['to_warehouse']);
        }

        // Provide warehouse list for editing
        $warehouses = [];
        $warehouseQuery = "SELECT id, address_title FROM exotic_address WHERE is_active = 1 ORDER BY address_title ASC";
        $warehouseResult = mysqli_query($conn, $warehouseQuery);
        if ($warehouseResult) {
            while ($row = mysqli_fetch_assoc($warehouseResult)) {
                $warehouses[] = $row;
            }
        }

        renderTemplate('views/products/stock_transfer_edit.php', [
            'transfer' => $transfer,
            'users' => $usersModel->getAllUsers(),
            'warehouses' => $warehouses,
        ], 'Edit Stock Transfer');
    }

    public function stock_transfer_update() {
        is_login();
        global $conn;

        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);

        $transferId = isset($_POST['transfer_id']) ? (int)$_POST['transfer_id'] : 0;
        if ($transferId <= 0) {
            header('Location: ?page=products&action=stock_transfer_list');
            return;
        }

        $existingFile = $_POST['existing_eway_bill_file'] ?? '';
        $ewayBillFile = $this->handleEwayBillFileUpload($existingFile);

        $fromWarehouse = isset($_POST['from_warehouse']) ? (int)$_POST['from_warehouse'] : 0;
        $toWarehouse = isset($_POST['to_warehouse']) ? (int)$_POST['to_warehouse'] : 0;

        if ($fromWarehouse <= 0 || $toWarehouse <= 0 || $fromWarehouse === $toWarehouse) {
            renderTemplateClean('views/errors/error.php', ['message' => 'Source and destination warehouses must be different'], 'Validation error');
            return;
        }

        $data = [
            'dispatch_date' => $_POST['dispatch_date'] ?? '',
            'est_delivery_date' => $_POST['est_delivery_date'] ?? '',
            'from_warehouse' => $fromWarehouse,
            'to_warehouse' => $toWarehouse,
            'requested_by' => isset($_POST['requested_by']) ? (int)$_POST['requested_by'] : 0,
            'dispatch_by' => isset($_POST['dispatch_by']) ? (int)$_POST['dispatch_by'] : 0,
            'booking_no' => $_POST['booking_no'] ?? '',
            'vehicle_no' => $_POST['vehicle_no'] ?? '',
            'vehicle_type' => $_POST['vehicle_type'] ?? '',
            'driver_name' => $_POST['driver_name'] ?? '',
            'driver_mobile' => $_POST['driver_mobile'] ?? '',
            'status' => $_POST['status'] ?? '',
            'eway_bill_file' => $ewayBillFile,
        ];

        $stockTransferModel->updateTransfer($transferId, $data);

        header('Location: ?page=products&action=stock_transfer_list');
    }

    public function getProductById($id) {
        global $productModel;
        $product = $productModel->getProduct($id);
        return $product;
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
    public function importApiCall($manualCodes = null) {
        global $productModel;
        $t0 = microtime(true);
        $internalCall = ($manualCodes !== null);
        $importTiming = function (int $reqCodes, float $apiMs = 0.0, ?int $apiParentItems = null, ?int $dbWrites = null) use ($t0): array {
            $total = round((microtime(true) - $t0) * 1000, 2);
            $api = round($apiMs, 2);
            return [
                'total_ms' => $total,
                'api_ms' => $api,
                'processing_ms' => max(0, round($total - $api, 2)),
                'requested_codes' => $reqCodes,
                'api_parent_items' => $apiParentItems,
                'db_writes' => $dbWrites,
                'codes_per_second' => ($total > 0 && $reqCodes > 0) ? round($reqCodes / ($total / 1000), 4) : null,
            ];
        };
        $respond = function(array $payload) use ($internalCall) {
            if ($internalCall) {
                return $payload;
            }
            header('Content-Type: application/json');
            echo json_encode($payload);
            exit;
        };
        // Accept JSON body or form-data
        if ($internalCall) {
            // Called from PHP: $ProductsController->importApiCall([$itemCode])
            $itemCodes = $manualCodes;
        } else {
            // Called from JavaScript fetch
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw, true);
            
            if (!$payload || !isset($payload['itemCodes'])) {
                return $respond(['success' => false, 'message' => 'Invalid request.', 'timing' => $importTiming(0)]);
            }
            $itemCodes = $payload['itemCodes'];
        }
        if (!is_array($itemCodes)) {
            return $respond(['success' => false, 'message' => 'Invalid itemCodes.', 'timing' => $importTiming(0)]);
        }
        // Filter and normalize
        $codes = array_values(array_unique(array_map('trim', array_filter($itemCodes))));
        $codes = array_filter($codes);
        if (count($codes) === 0) {
            return $respond(['success' => false, 'message' => 'No item codes provided.', 'timing' => $importTiming(0)]);
        }
        if (count($codes) > 50) {
            return $respond(['success' => false, 'message' => 'Maximum 50 SKUs allowed.', 'timing' => $importTiming(count($codes))]);
        }
        $codesCount = count($codes);

        //exit;
        $created = 0;
        $updated = 0;
        $failed = [];
        $createdIdsByCode = [];
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

        $tApi0 = microtime(true);
        $response = curl_exec($ch);
        $apiMsElapsed = (microtime(true) - $tApi0) * 1000;
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return $respond(['success' => false, 'message' => 'API request failed: ' . $error, 'timing' => $importTiming($codesCount, $apiMsElapsed)]);
        }

        $apiResult = json_decode($response, true);
        if (!is_array($apiResult)) {
            return $respond(['success' => false, 'message' => 'Invalid API response format.', 'timing' => $importTiming($codesCount, $apiMsElapsed)]);
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
            return $respond(['success' => false, 'message' => 'No items found in API response.', 'timing' => $importTiming($codesCount, $apiMsElapsed)]);
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
            $item['image'] = (!empty($apiItem['image']))
                ? ('https://cdn.exoticindia.com/images/products/original/' . $apiItem['image'])
                : '';
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
            if ($id) {
                $created++;
                $createdIdsByCode[$code][] = (int)$id;
            }
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
                        if ($id) {
                            $created++;
                            $createdIdsByCode[$variantItem['item_code']][] = (int)$id;
                        }
                        else $failed[] = $variantItem['item_code'];
                    }
                }
            }

            // tiny sleep to be gentle on third-party API/service
            usleep(100000); // 100ms
        }

        $payload = [
            'success' => true,
            'message' => 'Products processed successfully',
            'created' => $created,
            'updated' => $updated,
            'failed' => $failed,
            'timing' => $importTiming($codesCount, $apiMsElapsed, count($items), $created + $updated),
        ];
        if ($internalCall) {
            $payload['created_ids_by_code'] = $createdIdsByCode;
        }
        return $respond($payload);
    }

    public function bulkImportScreen() {
        is_login();
        global $conn;
        $this->ensureBulkImportTables();

        $jobs = [];
        $sql = "SELECT j.*, u.name AS created_by_name, ea.address_title AS warehouse_name
                FROM product_import_jobs j
                LEFT JOIN vp_users u ON u.id = j.created_by
                LEFT JOIN exotic_address ea ON ea.id = j.warehouse_id
                ORDER BY j.id DESC
                LIMIT 100";
        $res = mysqli_query($conn, $sql);
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $jobs[] = $row;
            }
        }

        global $usersModel;
        $warehouses = $usersModel->getAllWarehouses();

        renderTemplate('views/products/bulk_import.php', [
            'jobs' => $jobs,
            'warehouses' => $warehouses,
        ], 'Bulk Product Import');
    }

    public function bulkImportSampleCsv() {
        is_login();
        $filename = 'bulk_product_import_sample.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Product Code', 'SKU', 'Color', 'Size', 'Qty', 'Location']);
        fputcsv($out, ['GAN035', '', 'orange', 'xl', '10', 'A-01-01']);
        fputcsv($out, ['GAN035', '', '', 'xl', '5', 'B-02-03']);
        fputcsv($out, ['GAN035', '', 'orange', '', '3', 'C-03-03']);
        fputcsv($out, ['SAMPLE004', 'FIXED-SKU-01', 'red', 'M', '2', 'D-04']);
        fclose($out);
        exit;
    }

    public function bulkImportDetail() {
        is_login();
        global $conn;
        $this->ensureBulkImportTables();

        $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($jobId <= 0) {
            header('Location: ?page=products&action=bulk_import');
            exit;
        }

        $this->refreshImportJobCounts($jobId);

        $job = null;
        $stmtJob = $conn->prepare("SELECT j.*, u.name AS created_by_name, ea.address_title AS warehouse_name
            FROM product_import_jobs j
            LEFT JOIN vp_users u ON u.id = j.created_by
            LEFT JOIN exotic_address ea ON ea.id = j.warehouse_id
            WHERE j.id = ? LIMIT 1");
        $stmtJob->bind_param('i', $jobId);
        $stmtJob->execute();
        $jobRes = $stmtJob->get_result();
        $job = $jobRes ? $jobRes->fetch_assoc() : null;
        $stmtJob->close();
        if (!$job) {
            header('Location: ?page=products&action=bulk_import');
            exit;
        }

        $statusFilter = trim((string)($_GET['status'] ?? 'all'));
        $allowed = ['all', 'pending', 'processing', 'success', 'failed'];
        if (!in_array($statusFilter, $allowed, true)) {
            $statusFilter = 'all';
        }

        $pageNo = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
        if (!in_array($limit, [50, 100, 200, 500], true)) {
            $limit = 100;
        }
        $offset = ($pageNo - 1) * $limit;

        $where = " WHERE job_id = ? ";
        if ($statusFilter !== 'all') {
            $where .= " AND status = ? ";
        }

        $countSql = "SELECT COUNT(*) AS cnt FROM product_import_items $where";
        $countStmt = $conn->prepare($countSql);
        if ($statusFilter !== 'all') {
            $countStmt->bind_param('is', $jobId, $statusFilter);
        } else {
            $countStmt->bind_param('i', $jobId);
        }
        $countStmt->execute();
        $countRes = $countStmt->get_result()->fetch_assoc();
        $countStmt->close();
        $totalItems = (int)($countRes['cnt'] ?? 0);
        $totalPages = $limit > 0 ? (int)ceil($totalItems / $limit) : 1;

        $rows = [];
        $listSql = "SELECT
                        pii.id,
                        pii.item_code,
                        pii.import_sku,
                        pii.import_color,
                        pii.import_size,
                        pii.opening_qty,
                        pii.stock_location,
                        pii.status,
                        pii.attempt_count,
                        pii.error_message,
                        pii.created_at,
                        pii.updated_at,
                        pii.processed_at,
                        (
                            SELECT p.sku
                            FROM vp_products p
                            WHERE p.item_code COLLATE utf8mb4_general_ci = pii.item_code COLLATE utf8mb4_general_ci
                              AND IFNULL(NULLIF(TRIM(p.size), ''), '') COLLATE utf8mb4_general_ci <=> IFNULL(NULLIF(TRIM(pii.import_size), ''), '') COLLATE utf8mb4_general_ci
                              AND IFNULL(NULLIF(TRIM(p.color), ''), '') COLLATE utf8mb4_general_ci <=> IFNULL(NULLIF(TRIM(pii.import_color), ''), '') COLLATE utf8mb4_general_ci
                            ORDER BY p.id DESC
                            LIMIT 1
                        ) AS product_sku
                    FROM product_import_items pii
                    $where
                    ORDER BY pii.id ASC
                    LIMIT $limit OFFSET $offset";
        $listStmt = $conn->prepare($listSql);
        if ($statusFilter !== 'all') {
            $listStmt->bind_param('is', $jobId, $statusFilter);
        } else {
            $listStmt->bind_param('i', $jobId);
        }
        $listStmt->execute();
        $listRes = $listStmt->get_result();
        while ($listRes && ($row = $listRes->fetch_assoc())) {
            $rows[] = $row;
        }
        $listStmt->close();

        renderTemplate('views/products/bulk_import_detail.php', [
            'job' => $job,
            'rows' => $rows,
            'status_filter' => $statusFilter,
            'page_no' => $pageNo,
            'limit' => $limit,
            'total_items' => $totalItems,
            'total_pages' => $totalPages
        ], 'Bulk Import Detail');
    }

    private function ensureBulkImportTables() {
        global $conn;
        $sqlJobs = "CREATE TABLE IF NOT EXISTS product_import_jobs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_name VARCHAR(255) NOT NULL,
            created_by INT NOT NULL,
            warehouse_id INT NOT NULL DEFAULT 0,
            file_path VARCHAR(1024) NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            total_items INT NOT NULL DEFAULT 0,
            processed_items INT NOT NULL DEFAULT 0,
            success_items INT NOT NULL DEFAULT 0,
            failed_items INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $sqlJobs);

        $sqlItems = "CREATE TABLE IF NOT EXISTS product_import_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            job_id INT NOT NULL,
            item_code VARCHAR(100) NOT NULL,
            import_sku VARCHAR(100) NOT NULL DEFAULT '',
            import_color VARCHAR(100) NOT NULL DEFAULT '',
            import_size VARCHAR(100) NOT NULL DEFAULT '',
            opening_qty INT NOT NULL DEFAULT 0,
            stock_location VARCHAR(255) NOT NULL DEFAULT '',
            created_product_ids TEXT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'pending',
            attempt_count INT NOT NULL DEFAULT 0,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            processed_at DATETIME NULL,
            INDEX idx_job_status (job_id, status),
            UNIQUE KEY uniq_job_item (job_id, item_code),
            CONSTRAINT fk_product_import_items_job FOREIGN KEY (job_id) REFERENCES product_import_jobs(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        mysqli_query($conn, $sqlItems);

        $this->ensureBulkImportSchemaPatches($conn);
        $this->ensureVpStockMovementsOpeningStockEnum($conn);
    }

    /**
     * Migrate older installs that created import tables before warehouse/qty/location columns existed.
     */
    private function ensureBulkImportSchemaPatches($conn): void {
        if (!$conn) {
            return;
        }
        $r = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'warehouse_id'");
        if (!$r || mysqli_num_rows($r) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN warehouse_id INT NOT NULL DEFAULT 0 AFTER created_by");
        }
        $rSkuCol = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_items LIKE 'import_sku'");
        if (!$rSkuCol || mysqli_num_rows($rSkuCol) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN import_sku VARCHAR(100) NOT NULL DEFAULT '' AFTER item_code");
        }
        $rColColor = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_items LIKE 'import_color'");
        if (!$rColColor || mysqli_num_rows($rColColor) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN import_color VARCHAR(100) NOT NULL DEFAULT '' AFTER import_sku");
        }
        $rColSize = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_items LIKE 'import_size'");
        if (!$rColSize || mysqli_num_rows($rColSize) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN import_size VARCHAR(100) NOT NULL DEFAULT '' AFTER import_color");
        }
        $rFile = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'file_path'");
        if (!$rFile || mysqli_num_rows($rFile) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN file_path VARCHAR(1024) NULL AFTER warehouse_id");
        }
        $r2 = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_items LIKE 'opening_qty'");
        if (!$r2 || mysqli_num_rows($r2) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN opening_qty INT NOT NULL DEFAULT 0 AFTER item_code");
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN stock_location VARCHAR(255) NOT NULL DEFAULT '' AFTER opening_qty");
        }
        $r3 = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_items LIKE 'created_product_ids'");
        if (!$r3 || mysqli_num_rows($r3) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_items ADD COLUMN created_product_ids TEXT NULL AFTER stock_location");
        }

        $rTimStart = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'import_started_at'");
        if (!$rTimStart || mysqli_num_rows($rTimStart) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN import_started_at DATETIME NULL AFTER failed_items");
        }
        $rTimEnd = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'import_completed_at'");
        if (!$rTimEnd || mysqli_num_rows($rTimEnd) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN import_completed_at DATETIME NULL AFTER import_started_at");
        }
        $rLastBatch = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'last_batch_duration_ms'");
        if (!$rLastBatch || mysqli_num_rows($rLastBatch) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN last_batch_duration_ms INT UNSIGNED NULL AFTER import_completed_at");
        }
        $rTotProc = @mysqli_query($conn, "SHOW COLUMNS FROM product_import_jobs LIKE 'total_processing_ms'");
        if (!$rTotProc || mysqli_num_rows($rTotProc) === 0) {
            @mysqli_query($conn, "ALTER TABLE product_import_jobs ADD COLUMN total_processing_ms BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER last_batch_duration_ms");
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchImportJobTimingColumns(\mysqli $conn, int $jobId): array {
        $row = [
            'import_started_at' => null,
            'import_completed_at' => null,
            'last_batch_duration_ms' => null,
            'total_processing_ms' => 0,
        ];
        $stmt = @$conn->prepare('SELECT import_started_at, import_completed_at, last_batch_duration_ms, total_processing_ms FROM product_import_jobs WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return $row;
        }
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($r = $res->fetch_assoc())) {
            $row['import_started_at'] = $r['import_started_at'] ?? null;
            $row['import_completed_at'] = $r['import_completed_at'] ?? null;
            $row['last_batch_duration_ms'] = isset($r['last_batch_duration_ms']) ? (int)$r['last_batch_duration_ms'] : null;
            $row['total_processing_ms'] = isset($r['total_processing_ms']) ? (int)$r['total_processing_ms'] : 0;
        }
        $stmt->close();
        return $row;
    }

    /**
     * @param array{processed_items?:int,pending_items?:int} $stats
     */
    private function applyImportJobBatchTiming(\mysqli $conn, int $jobId, float $batchDurationMs, array $stats): void {
        $batchMs = (int)max(0, round($batchDurationMs));
        if ($batchMs <= 0) {
            return;
        }
        $pending = (int)($stats['pending_items'] ?? 0);
        $completedFragment = $pending <= 0 ? 'import_completed_at = NOW()' : 'import_completed_at = NULL';
        $sql = "UPDATE product_import_jobs SET
            import_started_at = COALESCE(import_started_at, NOW()),
            last_batch_duration_ms = ?,
            total_processing_ms = total_processing_ms + ?,
            $completedFragment
            WHERE id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('iii', $batchMs, $batchMs, $jobId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param array{total_items?:int,processed_items?:int,pending_items?:int} $stats
     * @return array<string, float|int|string|null>
     */
    private function buildImportJobTimingSummary(array $stats, array $timingColumns): array {
        $totalMs = (int)($timingColumns['total_processing_ms'] ?? 0);
        $processed = (int)($stats['processed_items'] ?? 0);
        $pending = (int)($stats['pending_items'] ?? 0);
        $totalItems = (int)($stats['total_items'] ?? 0);
        $avgMsPerProcessed = ($processed > 0 && $totalMs > 0) ? round($totalMs / $processed, 2) : null;
        $etaMs = ($pending > 0 && $processed > 0 && $totalMs > 0) ? (int)round(($totalMs / $processed) * $pending) : null;
        $extrapolate300kMs = ($processed > 0 && $totalMs > 0) ? (int)round(($totalMs / $processed) * $totalItems) : null;
        return [
            'import_started_at' => $timingColumns['import_started_at'] ?? null,
            'import_completed_at' => $timingColumns['import_completed_at'] ?? null,
            'last_batch_duration_ms' => $timingColumns['last_batch_duration_ms'] ?? null,
            'total_processing_ms' => $totalMs,
            'avg_ms_per_processed_item' => $avgMsPerProcessed,
            'eta_pending_ms' => $etaMs,
            'extrapolated_total_job_ms' => $extrapolate300kMs,
        ];
    }

    /**
     * Allow OPENING_STOCK on vp_stock_movements (idempotent if already present).
     */
    private function ensureVpStockMovementsOpeningStockEnum($conn): void {
        if (!$conn) {
            return;
        }
        $res = @mysqli_query($conn, "SHOW COLUMNS FROM vp_stock_movements LIKE 'movement_type'");
        if (!$res) {
            return;
        }
        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            return;
        }
        $type = strtolower((string)($row['Type'] ?? ''));
        if (strpos($type, 'opening_stock') !== false) {
            return;
        }
        @mysqli_query($conn, "ALTER TABLE vp_stock_movements MODIFY COLUMN movement_type ENUM('IN','OUT','TRANSFER_IN','TRANSFER_OUT','OPENING_STOCK') NOT NULL");
    }

    private function parseBulkQtyFromRaw(string $raw): int {
        $x = str_replace([',', ' '], '', trim($raw));
        if ($x === '' || !is_numeric($x)) {
            return 0;
        }
        return max(0, (int)round((float)$x));
    }

    private function bulkImportCellLooksLikeQty(string $cell): bool {
        $b = trim($cell);
        if ($b === '') {
            return false;
        }
        $x = str_replace([',', ' ', "\t"], '', $b);

        return $x !== '' && is_numeric($x);
    }

    /**
     * Effective SKU when upload column B is blank only. If B has a value, that value is used (unchanged).
     * When B is blank: size/color add segments; when both size and color are blank, returns item_code only
     * (same as the original “use item code as SKU” behaviour).
     */
    private function buildBulkImportAutoSku(string $itemCode, string $size, string $color): string {
        $itemCode = trim($itemCode);
        $size = trim($size);
        $color = trim($color);
        if ($size !== '' && $color !== '') {
            return $itemCode . '-' . $size . '-' . $color;
        }
        if ($size !== '' && $color === '') {
            return $itemCode . '-' . $size;
        }
        if ($size === '' && $color !== '') {
            return $itemCode . '--' . $color;
        }

        return $itemCode;
    }

    /**
     * @return array{code:string,sku_raw:string,color:string,size:string,qty:int,location:string}|null
     */
    private function parseBulkImportCsvRowParts(array $row): ?array {
        $code = trim((string)($row[0] ?? ''));
        if ($code === '') {
            return null;
        }
        $nf = count($row);

        // Legacy: A=code, B=qty, C=location (3 fields or fewer trailing empties)
        if ($nf <= 3 && $this->bulkImportCellLooksLikeQty((string)($row[1] ?? ''))) {
            return [
                'code' => $code,
                'sku_raw' => '',
                'color' => '',
                'size' => '',
                'qty' => $this->parseBulkQtyFromRaw((string)($row[1] ?? '0')),
                'location' => trim((string)($row[2] ?? '')),
            ];
        }

        // Older 4-column: A=code, B=sku, C=qty, D=location
        if ($nf === 4 && !array_key_exists(4, $row) && $this->bulkImportCellLooksLikeQty((string)($row[2] ?? ''))) {
            return [
                'code' => $code,
                'sku_raw' => trim((string)($row[1] ?? '')),
                'color' => '',
                'size' => '',
                'qty' => $this->parseBulkQtyFromRaw((string)($row[2] ?? '0')),
                'location' => trim((string)($row[3] ?? '')),
            ];
        }

        // New: A=code, B=sku, C=color, D=size, E=qty, F=location
        if ($nf >= 5 && array_key_exists(4, $row)) {
            return [
                'code' => $code,
                'sku_raw' => trim((string)($row[1] ?? '')),
                'color' => trim((string)($row[2] ?? '')),
                'size' => trim((string)($row[3] ?? '')),
                'qty' => $this->parseBulkQtyFromRaw((string)($row[4] ?? '0')),
                'location' => trim((string)($row[5] ?? '')),
            ];
        }

        // Only A–D and column C is not qty: treat as code, sku, color, size
        if ($nf === 4) {
            return [
                'code' => $code,
                'sku_raw' => trim((string)($row[1] ?? '')),
                'color' => trim((string)($row[2] ?? '')),
                'size' => trim((string)($row[3] ?? '')),
                'qty' => 0,
                'location' => '',
            ];
        }

        return null;
    }

    private function parseBulkImportRowsFromCsv(string $filePath): array {
        $rows = [];
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $rows;
        }
        while (($row = fgetcsv($handle)) !== false) {
            $parts = $this->parseBulkImportCsvRowParts($row);
            if ($parts === null) {
                continue;
            }
            $rows[] = [
                'code' => $parts['code'],
                'sku_raw' => $parts['sku_raw'],
                'color' => $parts['color'],
                'size' => $parts['size'],
                'qty' => $parts['qty'],
                'location' => $parts['location'],
            ];
        }
        fclose($handle);
        return $rows;
    }

    private function xlsxLoadSharedStrings(ZipArchive $zip): array {
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml === false) {
            return $sharedStrings;
        }
        $sx = @simplexml_load_string($sharedXml);
        if ($sx && isset($sx->si)) {
            foreach ($sx->si as $si) {
                $text = '';
                if (isset($si->t)) {
                    $text = (string)$si->t;
                } elseif (isset($si->r)) {
                    foreach ($si->r as $run) {
                        $text .= (string)$run->t;
                    }
                }
                $sharedStrings[] = $text;
            }
        }
        return $sharedStrings;
    }

    private function xlsxCellPlainText(SimpleXMLElement $c, array $sharedStrings): string {
        $t = (string)($c['t'] ?? '');
        if ($t === 's') {
            $idx = (int)$c->v;

            return (string)($sharedStrings[$idx] ?? '');
        }
        if ($t === 'inlineStr') {
            return isset($c->is->t) ? (string)$c->is->t : '';
        }
        if (isset($c->v)) {
            return (string)$c->v;
        }

        return '';
    }

    private function parseBulkImportRowsFromXlsx(string $filePath): array {
        $rows = [];
        if (!class_exists('ZipArchive')) {
            return $rows;
        }
        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            return $rows;
        }

        $sharedStrings = $this->xlsxLoadSharedStrings($zip);
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            $zip->close();

            return $rows;
        }
        $sheet = @simplexml_load_string($sheetXml);
        if ($sheet && isset($sheet->sheetData->row)) {
            foreach ($sheet->sheetData->row as $row) {
                $byCol = [];
                foreach ($row->c as $c) {
                    $ref = (string)($c['r'] ?? '');
                    if ($ref === '' || !preg_match('/^([A-Z]+)/', $ref, $m)) {
                        continue;
                    }
                    $byCol[$m[1]] = trim($this->xlsxCellPlainText($c, $sharedStrings));
                }
                $parts = $this->parseBulkImportXlsxRowParts($byCol);
                if ($parts === null) {
                    continue;
                }
                $rows[] = [
                    'code' => $parts['code'],
                    'sku_raw' => $parts['sku_raw'],
                    'color' => $parts['color'],
                    'size' => $parts['size'],
                    'qty' => $parts['qty'],
                    'location' => $parts['location'],
                ];
            }
        }
        $zip->close();

        return $rows;
    }

    /**
     * @param array<string,mixed> $byCol column letter => cell text
     * @return array{code:string,sku_raw:string,color:string,size:string,qty:int,location:string}|null
     */
    private function parseBulkImportXlsxRowParts(array $byCol): ?array {
        $code = trim((string)($byCol['A'] ?? ''));
        if ($code === '') {
            return null;
        }
        $b = (string)($byCol['B'] ?? '');
        $c = (string)($byCol['C'] ?? '');
        $hasD = isset($byCol['D']) && trim((string)$byCol['D']) !== '';
        $hasE = isset($byCol['E']);

        if (!$hasE && !$hasD && $this->bulkImportCellLooksLikeQty($b)) {
            return [
                'code' => $code,
                'sku_raw' => '',
                'color' => '',
                'size' => '',
                'qty' => $this->parseBulkQtyFromRaw($b),
                'location' => trim((string)($byCol['C'] ?? '')),
            ];
        }

        if ($hasD && !$hasE && $this->bulkImportCellLooksLikeQty($c)) {
            return [
                'code' => $code,
                'sku_raw' => trim($b),
                'color' => '',
                'size' => '',
                'qty' => $this->parseBulkQtyFromRaw($c),
                'location' => trim((string)($byCol['D'] ?? '')),
            ];
        }

        if ($hasE) {
            return [
                'code' => $code,
                'sku_raw' => trim($b),
                'color' => trim($c),
                'size' => trim((string)($byCol['D'] ?? '')),
                'qty' => $this->parseBulkQtyFromRaw((string)($byCol['E'] ?? '0')),
                'location' => trim((string)($byCol['F'] ?? '')),
            ];
        }

        if ($hasD) {
            return [
                'code' => $code,
                'sku_raw' => trim($b),
                'color' => trim($c),
                'size' => trim((string)($byCol['D'] ?? '')),
                'qty' => 0,
                'location' => '',
            ];
        }

        return null;
    }

    private function normalizeBulkImportRows(array $rows): array {
        $headerCodes = ['item_code', 'itemcode', 'item code', 'sku', 'product code', 'product_code', 'productcode'];
        $outByCode = [];
        foreach ($rows as $r) {
            $code = trim((string)($r['code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $lower = strtolower($code);
            if (in_array($lower, $headerCodes, true)) {
                continue;
            }
            $qty = max(0, (int)($r['qty'] ?? 0));
            $location = trim((string)($r['location'] ?? ''));
            $color = trim((string)($r['color'] ?? ''));
            $size = trim((string)($r['size'] ?? ''));
            // Explicit SKU in column B wins. Otherwise auto SKU from item_code ± size/color (or item_code alone).
            $skuIn = trim((string)($r['sku_raw'] ?? $r['sku'] ?? ''));
            $sku = $skuIn !== '' ? $skuIn : $this->buildBulkImportAutoSku($code, $size, $color);
            $outByCode[$code] = ['code' => $code, 'sku' => $sku, 'color' => $color, 'size' => $size, 'qty' => $qty, 'location' => $location];
        }

        return array_values($outByCode);
    }

    private function findProductRowForOpeningStock(mysqli $conn, string $itemCode): ?array {
        $sql = "SELECT id, sku, item_code, size, color FROM vp_products
                WHERE item_code = ?
                ORDER BY (CASE WHEN (size IS NULL OR size = '') AND (color IS NULL OR color = '') THEN 0 ELSE 1 END), id ASC
                LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $itemCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Resolve product for opening stock: match SKU when set, else size/color, else first row for item_code.
     */
    private function findProductRowForBulkImportStock(
        mysqli $conn,
        string $itemCode,
        string $importSku,
        string $importSize,
        string $importColor
    ): ?array {
        $importSku = trim($importSku);
        $sz = trim($importSize);
        $co = trim($importColor);

        if ($importSku !== '') {
            $sql = 'SELECT id, sku, item_code, size, color FROM vp_products WHERE item_code = ? AND sku = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('ss', $itemCode, $importSku);
                $stmt->execute();
                $res = $stmt->get_result();
                $row = $res ? $res->fetch_assoc() : null;
                $stmt->close();
                if ($row) {
                    return $row;
                }
            }
        }

        $sql2 = "SELECT id, sku, item_code, size, color FROM vp_products
                 WHERE item_code = ?
                 AND IFNULL(NULLIF(TRIM(size), ''), '') <=> ?
                 AND IFNULL(NULLIF(TRIM(color), ''), '') <=> ?
                 LIMIT 1";
        $stmt2 = $conn->prepare($sql2);
        if ($stmt2) {
            $stmt2->bind_param('sss', $itemCode, $sz, $co);
            $stmt2->execute();
            $res2 = $stmt2->get_result();
            $row2 = $res2 ? $res2->fetch_assoc() : null;
            $stmt2->close();
            if ($row2) {
                return $row2;
            }
        }

        return $this->findProductRowForOpeningStock($conn, $itemCode);
    }

    /**
     * @return string|null Error message, or null on success
     */
    private function bulkImportApplyOpeningStock(
        mysqli $conn,
        int $warehouseId,
        string $itemCode,
        string $importSku,
        string $importSize,
        string $importColor,
        int $openingQty,
        string $stockLocation,
        int $userId
    ): ?string {
        if ($warehouseId <= 0) {
            return 'Warehouse is required for opening stock.';
        }
        $product = $this->findProductRowForBulkImportStock($conn, $itemCode, $importSku, $importSize, $importColor);
        if (!$product) {
            return 'Product not found for stock movement (item_code/SKU/size/color).';
        }

        $productId = (int)($product['id'] ?? 0);
        if ($productId <= 0) {
            return 'Invalid product id for stock movement.';
        }
        $sku = trim($importSku) !== '' ? trim($importSku) : ((string)($product['sku'] ?? ''));
        $size = trim($importSize);
        $color = trim($importColor);
        $loc = trim($stockLocation);
        if ($loc === '') {
            $loc = '-';
        }
        $qty = max(0, (int)$openingQty);
        $runningStock = $qty; // new opening line baseline
        $reason = 'Migration From Egreen';
        $refType = 'Egreen';

        // Re-import: remove prior bulk opening lines for this variant/warehouse, then insert a fresh movement.
        if (!$conn->begin_transaction()) {
            return 'Could not start transaction for stock movement.';
        }
        $del = $conn->prepare("DELETE FROM vp_stock_movements
            WHERE product_id = ? AND warehouse_id = ? AND item_code = ?
              AND movement_type = 'OPENING_STOCK' AND ref_type = 'Egreen'
              AND IFNULL(NULLIF(TRIM(size), ''), '') <=> ?
              AND IFNULL(NULLIF(TRIM(color), ''), '') <=> ?");
        if (!$del) {
            $conn->rollback();
            return 'Could not prepare stock movement delete (refresh).';
        }
        $del->bind_param('iisss', $productId, $warehouseId, $itemCode, $size, $color);
        if (!$del->execute()) {
            $msg = $del->error;
            $del->close();
            $conn->rollback();
            return 'Stock movement delete failed: ' . $msg;
        }
        $del->close();

        $ins = $conn->prepare("INSERT INTO vp_stock_movements
            (product_id, sku, item_code, size, color, warehouse_id, location, movement_type, quantity, running_stock, ref_type, ref_id, reason, update_by_user)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'OPENING_STOCK', ?, ?, ?, NULL, ?, ?)");
        if (!$ins) {
            return 'Could not prepare stock movement insert.';
        }
        $ins->bind_param('issssisiissi', $productId, $sku, $itemCode, $size, $color, $warehouseId, $loc, $qty, $runningStock, $refType, $reason, $userId);
        if (!$ins->execute()) {
            $msg = $ins->error;
            $ins->close();

            // Fallback for schemas where ref_id is NOT NULL.
            $ins2 = $conn->prepare("INSERT INTO vp_stock_movements
                (product_id, sku, item_code, size, color, warehouse_id, location, movement_type, quantity, running_stock, ref_type, ref_id, reason, update_by_user)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'OPENING_STOCK', ?, ?, ?, '', ?, ?)");
            if (!$ins2) {
                $conn->rollback();
                return 'Stock movement insert failed: ' . $msg;
            }
            $ins2->bind_param('issssisiissi', $productId, $sku, $itemCode, $size, $color, $warehouseId, $loc, $qty, $runningStock, $refType, $reason, $userId);
            if (!$ins2->execute()) {
                $msg2 = $ins2->error;
                $ins2->close();
                $conn->rollback();
                return 'Stock movement insert failed: ' . $msg2;
            }
            $ins2->close();
        } else {
            $ins->close();
        }

        $updL = $conn->prepare('UPDATE vp_products SET local_stock = ? WHERE id = ?');
        if (!$updL) {
            $conn->rollback();
            return 'Could not prepare product stock update.';
        }
        $updL->bind_param('ii', $runningStock, $productId);
        if (!$updL->execute()) {
            $err = $updL->error;
            $updL->close();
            $conn->rollback();
            return 'Product local_stock update failed: ' . $err;
        }
        $updL->close();

        if (!$conn->commit()) {
            return 'Could not commit stock movement transaction.';
        }
        return null;
    }

    private function bulkImportUploadErrorMessage(int $code): string {
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                return 'File exceeds the server upload limit (upload_max_filesize in php.ini). Ask your host to raise it, or split the file.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'File exceeds the maximum size allowed by the form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The file was only partially uploaded. Check your connection and try again.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was received. Ensure you chose a file, keep the tab open during upload, and that the form is not blocked by a browser extension.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Server misconfiguration: missing temporary folder for uploads (check upload_tmp_dir in php.ini).';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Server could not write the uploaded file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Upload failed (PHP error code ' . $code . ').';
        }
    }

    public function bulkImportUpload() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) { return false; }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHP Warning: ' . $message, 'debug' => basename($file) . ':' . $line]);
            exit;
        });
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        if (!isset($_FILES['item_codes_file'])) {
            $iniUpload = ini_get('upload_max_filesize') ?: 'unknown';
            $iniPost = ini_get('post_max_size') ?: 'unknown';
            echo json_encode([
                'success' => false,
                'message' => 'No file upload was received. Confirm the request is POST with multipart data and the field name is item_codes_file.',
                'debug' => 'PHP limits: upload_max_filesize=' . $iniUpload . ', post_max_size=' . $iniPost,
            ]);
            exit;
        }
        $uploadErr = (int)($_FILES['item_codes_file']['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($uploadErr !== UPLOAD_ERR_OK) {
            echo json_encode([
                'success' => false,
                'message' => $this->bulkImportUploadErrorMessage($uploadErr),
                'upload_error' => $uploadErr,
            ]);
            exit;
        }

        $file = $_FILES['item_codes_file'];
        $maxFileBytes = 10 * 1024 * 1024; // 10 MB
        if ((int)($file['size'] ?? 0) > $maxFileBytes) {
            echo json_encode(['success' => false, 'message' => 'File size must be 10 MB or less.']);
            exit;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'xlsx'], true)) {
            echo json_encode(['success' => false, 'message' => 'Only .csv and .xlsx files are supported.']);
            exit;
        }

        $warehouseId = isset($_POST['warehouse_id']) ? (int)$_POST['warehouse_id'] : 0;
        if ($warehouseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select a warehouse.']);
            exit;
        }
        $whStmt = $conn->prepare('SELECT id FROM exotic_address WHERE id = ? AND is_active = 1 LIMIT 1');
        if (!$whStmt) {
            echo json_encode(['success' => false, 'message' => 'Could not validate warehouse.']);
            exit;
        }
        $whStmt->bind_param('i', $warehouseId);
        $whStmt->execute();
        $whRes = $whStmt->get_result();
        $whOk = $whRes && $whRes->num_rows > 0;
        $whStmt->close();
        if (!$whOk) {
            echo json_encode(['success' => false, 'message' => 'Invalid or inactive warehouse.']);
            exit;
        }

        $parsedRows = $ext === 'csv' ? $this->parseBulkImportRowsFromCsv($file['tmp_name']) : $this->parseBulkImportRowsFromXlsx($file['tmp_name']);
        $parsedRows = $this->normalizeBulkImportRows($parsedRows);
        if (empty($parsedRows)) {
            echo json_encode(['success' => false, 'message' => 'No product codes found in file (column A). Expected: A=code, B=SKU (optional), C=color, D=size, E=qty, F=location. If B is empty, SKU = code|code-size|code--color|code-size-color. Legacy: 3 cols code/qty/location or 4 cols code/SKU/qty/location.']);
            exit;
        }

        $createdBy = (int)($_SESSION['user']['id'] ?? 0);
        $fileName = basename((string)$file['name']);

        // Persist uploaded file so it can be deleted on revert.
        $uploadDir = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'bulk_import';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0775, true);
        }
        $safeOriginal = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $fileName);
        $storedName = 'job_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '_' . $safeOriginal;
        $storedPath = $uploadDir . DIRECTORY_SEPARATOR . $storedName;
        $storedOk = @move_uploaded_file($file['tmp_name'], $storedPath);
        $dbFilePath = $storedOk ? ('uploads/bulk_import/' . $storedName) : null;

        // If move failed, parsing was already done from tmp_name; we can still proceed, but revert won't be able to delete the file.
        $stmtJob = $conn->prepare("INSERT INTO product_import_jobs (file_name, created_by, warehouse_id, file_path, status, total_items) VALUES (?, ?, ?, ?, 'pending', ?)");
        $total = count($parsedRows);
        $stmtJob->bind_param('siisi', $fileName, $createdBy, $warehouseId, $dbFilePath, $total);
        $stmtJob->execute();
        $jobId = (int)$stmtJob->insert_id;
        $stmtJob->close();

        $stmtItem = $conn->prepare("INSERT INTO product_import_items (job_id, item_code, import_sku, import_color, import_size, opening_qty, stock_location, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ON DUPLICATE KEY UPDATE import_sku = VALUES(import_sku), import_color = VALUES(import_color), import_size = VALUES(import_size), opening_qty = VALUES(opening_qty), stock_location = VALUES(stock_location), status = 'pending', error_message = NULL, processed_at = NULL");
        foreach ($parsedRows as $pr) {
            $code = $pr['code'];
            $impSku = (string)($pr['sku'] ?? '');
            $ico = trim((string)($pr['color'] ?? ''));
            $isz = trim((string)($pr['size'] ?? ''));
            if ($impSku === '') {
                $impSku = $this->buildBulkImportAutoSku($code, $isz, $ico);
            }
            $oq = (int)$pr['qty'];
            $sl = (string)($pr['location'] ?? '');
            $stmtItem->bind_param('issssis', $jobId, $code, $impSku, $ico, $isz, $oq, $sl);
            $stmtItem->execute();
        }
        $stmtItem->close();

        echo json_encode(['success' => true, 'job_id' => $jobId, 'total_items' => $total]);
        exit;
    }

    public function bulkImportRevert() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) { return false; }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHP Warning: ' . $message, 'debug' => basename($file) . ':' . $line]);
            exit;
        });
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        $jobId = isset($payload['job_id']) ? (int)$payload['job_id'] : 0;
        if ($jobId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid job id.']);
            exit;
        }

        $jobStmt = $conn->prepare("SELECT id, status, file_path FROM product_import_jobs WHERE id = ? LIMIT 1");
        $jobStmt->bind_param('i', $jobId);
        $jobStmt->execute();
        $jobRow = $jobStmt->get_result()->fetch_assoc();
        $jobStmt->close();
        if (!$jobRow) {
            echo json_encode(['success' => false, 'message' => 'Import job not found.']);
            exit;
        }
        if (($jobRow['status'] ?? '') === 'processing') {
            echo json_encode(['success' => false, 'message' => 'This job is processing. Please wait until it finishes, then revert.']);
            exit;
        }

        // Collect affected product_ids before deleting movements so we can recalculate local_stock,
        // and collect products that were created by this job so we can delete them.
        $prodIds = [];
        $createdProductIds = [];

        $itemsStmt = $conn->prepare("SELECT created_product_ids FROM product_import_items WHERE job_id = ?");
        if ($itemsStmt) {
            $itemsStmt->bind_param('i', $jobId);
            $itemsStmt->execute();
            $ires = $itemsStmt->get_result();
            while ($ires && ($ir = $ires->fetch_assoc())) {
                $rawIds = (string)($ir['created_product_ids'] ?? '');
                if ($rawIds !== '') {
                    $decoded = json_decode($rawIds, true);
                    if (is_array($decoded)) {
                        foreach ($decoded as $pid) {
                            $pid = (int)$pid;
                            if ($pid > 0) {
                                $createdProductIds[$pid] = true;
                            }
                        }
                    }
                }
            }
            $itemsStmt->close();
        }
        $prodSql = "SELECT DISTINCT sm.product_id
                    FROM vp_stock_movements sm
                    INNER JOIN product_import_items pii
                      ON sm.ref_type = 'BULK_IMPORT'
                     AND sm.ref_id = CONCAT('import_item:', pii.id)
                    WHERE pii.job_id = ? AND sm.product_id IS NOT NULL";
        $pstmt = $conn->prepare($prodSql);
        if ($pstmt) {
            $pstmt->bind_param('i', $jobId);
            $pstmt->execute();
            $pres = $pstmt->get_result();
            while ($pres && ($r = $pres->fetch_assoc())) {
                $pid = (int)($r['product_id'] ?? 0);
                if ($pid > 0) {
                    $prodIds[$pid] = true;
                }
            }
            $pstmt->close();
        }

        // Delete movements created by this import (by ref_id pattern).
        $delSql = "DELETE sm FROM vp_stock_movements sm
                   INNER JOIN product_import_items pii
                     ON sm.ref_type = 'BULK_IMPORT'
                    AND sm.ref_id = CONCAT('import_item:', pii.id)
                   WHERE pii.job_id = ?";
        $dstmt = $conn->prepare($delSql);
        if (!$dstmt) {
            echo json_encode(['success' => false, 'message' => 'Could not prepare revert delete statement.']);
            exit;
        }
        $dstmt->bind_param('i', $jobId);
        $dstmt->execute();
        $deletedMovements = (int)$dstmt->affected_rows;
        $dstmt->close();

        // Delete products created by API during this import (best-effort).
        $deletedProducts = 0;
        $failedProductDeletes = [];
        $delProdStmt = $conn->prepare("DELETE FROM vp_products WHERE id = ? LIMIT 1");
        if ($delProdStmt) {
            foreach (array_keys($createdProductIds) as $pid) {
                $pid = (int)$pid;
                $delProdStmt->bind_param('i', $pid);
                $ok = $delProdStmt->execute();
                if ($ok && $delProdStmt->affected_rows > 0) {
                    $deletedProducts++;
                } else {
                    $failedProductDeletes[] = $pid;
                }
            }
            $delProdStmt->close();
        }

        // Recalculate local_stock for affected products based on latest remaining movement.
        $recalcStmt = $conn->prepare("SELECT running_stock FROM vp_stock_movements WHERE product_id = ? ORDER BY id DESC LIMIT 1");
        $updStmt = $conn->prepare("UPDATE vp_products SET local_stock = ? WHERE id = ?");
        if ($recalcStmt && $updStmt) {
            foreach (array_keys($prodIds) as $pid) {
                $pid = (int)$pid;
                $recalcStmt->bind_param('i', $pid);
                $recalcStmt->execute();
                $row = $recalcStmt->get_result()->fetch_assoc();
                $stock = $row ? (int)($row['running_stock'] ?? 0) : 0;
                $updStmt->bind_param('ii', $stock, $pid);
                $updStmt->execute();
            }
        }
        if ($recalcStmt) { $recalcStmt->close(); }
        if ($updStmt) { $updStmt->close(); }

        // Delete job (cascade deletes items).
        $delJob = $conn->prepare("DELETE FROM product_import_jobs WHERE id = ? LIMIT 1");
        $delJob->bind_param('i', $jobId);
        $delJob->execute();
        $delJob->close();

        // Delete stored upload file, if present.
        $fileDeleted = false;
        $filePath = trim((string)($jobRow['file_path'] ?? ''));
        if ($filePath !== '') {
            $abs = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $filePath);
            $uploadsBase = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'uploads');
            if ($abs && $uploadsBase && strpos($abs, $uploadsBase) === 0 && is_file($abs)) {
                $fileDeleted = @unlink($abs);
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Import reverted successfully.',
            'deleted_movements' => $deletedMovements,
            'recalculated_products' => count($prodIds),
            'deleted_products' => $deletedProducts,
            'failed_product_deletes' => array_values($failedProductDeletes),
            'file_deleted' => $fileDeleted,
        ]);
        exit;
    }

    private function refreshImportJobCounts(int $jobId): array {
        global $conn;
        $sql = "SELECT
                    COUNT(*) AS total_items,
                    SUM(CASE WHEN status IN ('success','failed') THEN 1 ELSE 0 END) AS processed_items,
                    SUM(CASE WHEN status = 'success' THEN 1 ELSE 0 END) AS success_items,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed_items,
                    SUM(CASE WHEN status IN ('pending','processing') THEN 1 ELSE 0 END) AS pending_items
                FROM product_import_items
                WHERE job_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $total = (int)($res['total_items'] ?? 0);
        $processed = (int)($res['processed_items'] ?? 0);
        $success = (int)($res['success_items'] ?? 0);
        $failed = (int)($res['failed_items'] ?? 0);
        $pending = (int)($res['pending_items'] ?? 0);
        $status = $pending > 0 ? ($processed > 0 ? 'processing' : 'pending') : 'completed';

        $up = $conn->prepare("UPDATE product_import_jobs SET total_items=?, processed_items=?, success_items=?, failed_items=?, status=? WHERE id=?");
        $up->bind_param('iiiisi', $total, $processed, $success, $failed, $status, $jobId);
        $up->execute();
        $up->close();

        return [
            'total_items' => $total,
            'processed_items' => $processed,
            'success_items' => $success,
            'failed_items' => $failed,
            'pending_items' => $pending,
            'status' => $status
        ];
    }

    public function bulkImportProcessBatch() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) { return false; }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHP Warning: ' . $message, 'debug' => basename($file) . ':' . $line]);
            exit;
        });
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $jobId = isset($payload['job_id']) ? (int)$payload['job_id'] : 0;
        if ($jobId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid job id.']);
            exit;
        }

        $picked = [];
        // Pending first, then rows stuck in "processing" (browser closed / fatal mid-batch)
        $stmtPick = $conn->prepare("SELECT id, item_code, import_sku, import_color, import_size, opening_qty, stock_location FROM product_import_items WHERE job_id = ? AND status IN ('pending','processing') ORDER BY (status = 'processing') ASC, id ASC LIMIT 50");
        $stmtPick->bind_param('i', $jobId);
        $stmtPick->execute();
        $res = $stmtPick->get_result();
        while ($row = $res->fetch_assoc()) {
            $picked[] = $row;
        }
        $stmtPick->close();
        $ids = array_map(static function ($r) { return (int)$r['id']; }, $picked);
        $codes = array_map(static function ($r) { return $r['item_code']; }, $picked);

        if (empty($ids)) {
            $stats = $this->refreshImportJobCounts($jobId);
            $tcolsIdle = $this->fetchImportJobTimingColumns($conn, $jobId);
            $jobTimingIdle = $this->buildImportJobTimingSummary($stats, $tcolsIdle);
            echo json_encode([
                'success' => true,
                'message' => 'No pending codes left.',
                'batch_size' => 0,
                'stats' => $stats,
                'job_timing' => $jobTimingIdle,
                'batch_timing' => [
                    'batch_duration_ms' => 0,
                    'rows_in_batch' => 0,
                    'rows_per_second' => null,
                ],
            ]);
            exit;
        }

        $batchT0 = microtime(true);
        $idList = implode(',', array_map('intval', $ids));
        mysqli_query($conn, "UPDATE product_import_items SET status='processing', attempt_count=attempt_count+1, updated_at=NOW() WHERE id IN ($idList)");

        $jobWarehouseId = 0;
        $jwStmt = $conn->prepare('SELECT warehouse_id FROM product_import_jobs WHERE id = ? LIMIT 1');
        if ($jwStmt) {
            $jwStmt->bind_param('i', $jobId);
            $jwStmt->execute();
            $jwRow = $jwStmt->get_result()->fetch_assoc();
            $jwStmt->close();
            $jobWarehouseId = (int)($jwRow['warehouse_id'] ?? 0);
        }

        $batchUserId = (int)($_SESSION['user']['id'] ?? 0);

        $result = $this->importApiCall($codes);
        if (!is_array($result) || !isset($result['success'])) {
            $result = ['success' => false, 'message' => 'Batch import returned invalid response.', 'failed' => $codes];
        }

        if (!empty($result['success'])) {
            $failedCodes = array_values(array_unique(array_map('strval', $result['failed'] ?? [])));
            $createdIdsByCode = is_array($result['created_ids_by_code'] ?? null) ? $result['created_ids_by_code'] : [];
            foreach ($ids as $idx => $id) {
                $code = $codes[$idx];
                $rowPick = $picked[$idx] ?? [];
                $openQty = (int)($rowPick['opening_qty'] ?? 0);
                $stockLoc = (string)($rowPick['stock_location'] ?? '');
                $impSku = trim((string)($rowPick['import_sku'] ?? ''));
                $isz = trim((string)($rowPick['import_size'] ?? ''));
                $ico = trim((string)($rowPick['import_color'] ?? ''));
                if ($impSku === '') {
                    $impSku = $this->buildBulkImportAutoSku((string)$code, $isz, $ico);
                }
                $isFailed = in_array($code, $failedCodes, true);
                if ($isFailed) {
                    $stmtF = $conn->prepare("UPDATE product_import_items SET status='failed', error_message=?, processed_at=NOW() WHERE id=?");
                    $err = 'Failed to import from API response.';
                    $stmtF->bind_param('si', $err, $id);
                    $stmtF->execute();
                    $stmtF->close();
                } else {
                    $createdIds = $createdIdsByCode[$code] ?? [];
                    if (!is_array($createdIds)) {
                        $createdIds = [];
                    }
                    $createdIds = array_values(array_unique(array_map('intval', $createdIds)));
                    $createdJson = !empty($createdIds) ? json_encode($createdIds) : null;
                    if ($createdJson !== null) {
                        $up = $conn->prepare("UPDATE product_import_items SET created_product_ids = ? WHERE id = ?");
                        if ($up) {
                            $up->bind_param('si', $createdJson, $id);
                            $up->execute();
                            $up->close();
                        }
                    }
                    $openErr = $this->bulkImportApplyOpeningStock(
                        $conn,
                        $jobWarehouseId,
                        (string)$code,
                        $impSku,
                        $isz,
                        $ico,
                        $openQty,
                        $stockLoc,
                        $batchUserId
                    );
                    if ($openErr !== null) {
                        $stmtF = $conn->prepare("UPDATE product_import_items SET status='failed', error_message=?, processed_at=NOW() WHERE id=?");
                        $stmtF->bind_param('si', $openErr, $id);
                        $stmtF->execute();
                        $stmtF->close();
                    } else {
                        $stmtS = $conn->prepare("UPDATE product_import_items SET status='success', error_message=NULL, processed_at=NOW() WHERE id=?");
                        $stmtS->bind_param('i', $id);
                        $stmtS->execute();
                        $stmtS->close();
                    }
                }
            }
        } else {
            $error = (string)($result['message'] ?? 'Batch API failed.');
            foreach ($ids as $id) {
                $stmtF = $conn->prepare("UPDATE product_import_items SET status='failed', error_message=?, processed_at=NOW() WHERE id=?");
                $stmtF->bind_param('si', $error, $id);
                $stmtF->execute();
                $stmtF->close();
            }
        }

        $batchDurationMs = (microtime(true) - $batchT0) * 1000;
        $stats = $this->refreshImportJobCounts($jobId);
        $this->applyImportJobBatchTiming($conn, $jobId, $batchDurationMs, $stats);
        $tcols = $this->fetchImportJobTimingColumns($conn, $jobId);
        $jobTiming = $this->buildImportJobTimingSummary($stats, $tcols);
        $rowCount = count($ids);
        $rowsPerSec = ($rowCount > 0 && $batchDurationMs > 0) ? round($rowCount / ($batchDurationMs / 1000), 4) : null;
        echo json_encode([
            'success' => true,
            'batch_size' => $rowCount,
            'import_result' => $result,
            'stats' => $stats,
            'job_timing' => $jobTiming,
            'batch_timing' => [
                'batch_duration_ms' => round($batchDurationMs, 2),
                'rows_in_batch' => $rowCount,
                'rows_per_second' => $rowsPerSec,
            ],
        ]);
        exit;
    }

    public function bulkImportStatus() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) { return false; }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHP Warning: ' . $message, 'debug' => basename($file) . ':' . $line]);
            exit;
        });
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        $jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
        if ($jobId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid job id.']);
            exit;
        }

        $stats = $this->refreshImportJobCounts($jobId);
        $tcols = $this->fetchImportJobTimingColumns($conn, $jobId);
        $jobTiming = $this->buildImportJobTimingSummary($stats, $tcols);

        $failed = [];
        $stmt = $conn->prepare("SELECT item_code, error_message FROM product_import_items WHERE job_id = ? AND status='failed' ORDER BY updated_at DESC LIMIT 20");
        $stmt->bind_param('i', $jobId);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            $failed[] = $row;
        }
        $stmt->close();

        echo json_encode(['success' => true, 'job_id' => $jobId, 'stats' => $stats, 'job_timing' => $jobTiming, 'failed_preview' => $failed]);
        exit;
    }

    public function bulkImportRetry() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        set_error_handler(function ($severity, $message, $file, $line) {
            if (!(error_reporting() & $severity)) { return false; }
            if (ob_get_length()) { ob_clean(); }
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'PHP Warning: ' . $message, 'debug' => basename($file) . ':' . $line]);
            exit;
        });
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = $_POST;
        }
        if (!is_array($payload)) {
            $payload = [];
        }
        $jobId = isset($payload['job_id']) ? (int)$payload['job_id'] : 0;
        $retryType = trim((string)($payload['retry_type'] ?? 'failed')); // failed|pending|all
        if ($jobId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid job id.']);
            exit;
        }
        if (!in_array($retryType, ['failed', 'pending', 'all'], true)) {
            $retryType = 'failed';
        }

        $itemIds = [];
        if (!empty($payload['item_ids']) && is_array($payload['item_ids'])) {
            foreach ($payload['item_ids'] as $rid) {
                $i = (int)$rid;
                if ($i > 0) {
                    $itemIds[$i] = true;
                }
            }
        }
        $itemIds = array_keys($itemIds);

        $where = "job_id = ?";
        if (!empty($itemIds)) {
            // Per-row retry: only failed lines for this job
            $idList = implode(',', $itemIds);
            $where .= " AND status = 'failed' AND id IN ($idList)";
        } elseif ($retryType === 'failed') {
            $where .= " AND status = 'failed'";
        } elseif ($retryType === 'pending') {
            // Re-queue rows still waiting or stuck mid-batch (browser closed / fatal error)
            $where .= " AND status IN ('pending','processing')";
        } else {
            $where .= " AND status IN ('failed','pending','processing')";
        }

        $cntSql = "SELECT COUNT(*) AS c FROM product_import_items WHERE $where";
        $cntStmt = $conn->prepare($cntSql);
        $cntStmt->bind_param('i', $jobId);
        $cntStmt->execute();
        $matched = (int)($cntStmt->get_result()->fetch_assoc()['c'] ?? 0);
        $cntStmt->close();

        $sql = "UPDATE product_import_items
                SET status = 'pending', error_message = NULL, processed_at = NULL, updated_at = NOW()
                WHERE $where";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
            exit;
        }
        $stmt->bind_param('i', $jobId);
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'Update failed: ' . $stmt->error]);
            $stmt->close();
            exit;
        }
        $affected = (int)$stmt->affected_rows;
        $stmt->close();

        $stats = $this->refreshImportJobCounts($jobId);
        $msg = $matched === 0
            ? 'No matching rows to reset for this retry type.'
            : 'Retry queue updated.';
        echo json_encode([
            'success' => true,
            'message' => $msg,
            'matched' => $matched,
            'affected' => $affected,
            'stats' => $stats
        ]);
        exit;
    }

    public function bulkImportDelete() {
        is_login();
        if (ob_get_level() === 0) { ob_start(); }
        header('Content-Type: application/json');
        global $conn;
        $this->ensureBulkImportTables();

        $raw = file_get_contents('php://input');
        $payload = json_decode($raw, true) ?: $_POST;
        $jobId = isset($payload['job_id']) ? (int)$payload['job_id'] : 0;
        if ($jobId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid job id.']);
            exit;
        }

        $stmtCheck = $conn->prepare("SELECT COUNT(*) AS cnt FROM product_import_items WHERE job_id = ? AND status IN ('processing','success','failed')");
        $stmtCheck->bind_param('i', $jobId);
        $stmtCheck->execute();
        $checkRes = $stmtCheck->get_result()->fetch_assoc();
        $stmtCheck->close();
        $hasProcessed = (int)($checkRes['cnt'] ?? 0) > 0;
        if ($hasProcessed) {
            echo json_encode(['success' => false, 'message' => 'Only fully unprocessed jobs can be deleted.']);
            exit;
        }

        mysqli_begin_transaction($conn);
        try {
            $stmtItems = $conn->prepare("DELETE FROM product_import_items WHERE job_id = ?");
            $stmtItems->bind_param('i', $jobId);
            $stmtItems->execute();
            $stmtItems->close();

            $stmtJob = $conn->prepare("DELETE FROM product_import_jobs WHERE id = ?");
            $stmtJob->bind_param('i', $jobId);
            $stmtJob->execute();
            $deletedJobs = $stmtJob->affected_rows;
            $stmtJob->close();

            if ($deletedJobs <= 0) {
                mysqli_rollback($conn);
                echo json_encode(['success' => false, 'message' => 'Job not found or already deleted.']);
                exit;
            }

            mysqli_commit($conn);
            echo json_encode(['success' => true, 'message' => 'Unprocessed import job deleted successfully.']);
            exit;
        } catch (Throwable $e) {
            mysqli_rollback($conn);
            echo json_encode(['success' => false, 'message' => 'Failed to delete import job.', 'debug' => $e->getMessage()]);
            exit;
        }
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

    public function searchProduct() {
        is_login();
        header('Content-Type: application/json');
        global $productModel;
        $q = isset($_GET['q']) ? trim($_GET['q']) : '';

        if ($q === '') {
            echo json_encode(['success' => false, 'message' => 'Please provide item code or SKU']);
            exit;
        }

        // search SKU or item code partial-match
        $products = $productModel->searchProductsBySkuOrItemCode($q);

        if (!$products || count($products) === 0) {
            // fallback exact item code
            $productExactList = $productModel->getProductByItemCode($q);
            if (!empty($productExactList)) {
                $products = is_array($productExactList) ? $productExactList : [$productExactList];
            }
        }

        if (!$products || count($products) === 0) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        echo json_encode(['success' => true, 'products' => $products]);
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
        $purchase_data = $productModel->getPurchaseList($limit, $offset, $filters,'master');
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
        $filters['status'] = isset($_GET['status']) ? $_GET['status'] : 'all';
        $filters['category'] = isset($_GET['category']) ? $_GET['category'] : 'all';
        
        //search
        if (!empty($_GET['search'])) {
            $filters['search'] = trim($_GET['search']);
        }

        //added By filter
        if (!empty($_GET['added_by'])) {
            $filters['added_by'] = (int)$_GET['added_by'];
        }

        //asigned to filter
        if (!empty($_GET['assigned_to'])) {
            $filters['assigned_to'] = (int)$_GET['assigned_to'];
        }

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
            'selected_filters' => $filters,
            'staff_list' => $commanModel->get_staff_list(),
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
        
        if($quantity>0){
            $res = $productModel->addPurchaseTransaction($purchase_list_id, $quantity, $_SESSION['user']['id'], $status, $product_id);            
            echo json_encode($res);
            exit;
        } else if(!empty($status)) {
           $res = $productModel->updatePurchaseListStatusValue($purchase_list_id, $status);
            echo json_encode($res);
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
    public function detail() {
        is_login();
        global $productModel;
        global $commanModel;
        $id = isset($_GET['id']) ? $_GET['id'] : 0;
        if ($id != 0) {
            $order = $productModel->getProduct($id);
            $order['stock_value'] = $order['local_stock'] * $order['cost_price'];
            $order['committed_stock'] = $commanModel->getCommittedStockBySku($order['sku']);
            $order['available_stock'] = $order['local_stock'] - $order['committed_stock'];
            $order['in_purchase_list'] = $commanModel->isInPurchaseList($order['sku']);
            $order['vendors'] = $productModel->getVendorByItemCode($order['item_code']);
            $order['stock_history'] = $productModel->enrichStockHistoryRowsForLedger(
                $productModel->stock_history($order['sku'], 100, 0, (int)$id)
            );
            $order['stocks'] = $productModel->getStockSummaryBySku($order['sku']);
            $order['variants'] = $productModel->getVariantsByItemCode($order['item_code']);
            $order['warehouses'] = $productModel->getAllWarehouses();
            $order['stock_movements'] = $productModel->get_stock_movements($id);
            if ($order) {
                renderTemplate('views/products/product_detail.php', ['products' => $order], 'Product Details');
            } else {
              echo '<p>Product details not found.</p>';
            }
        } else {
            echo '<p>Invalid Product Item Code.</p>';
        }
        exit;
    }
    public function saveStockAdjustment() {
        is_login();
        global $productModel;
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data) throw new Exception('Invalid JSON');

            // Fetch current product details to get SKU, Item Code, Size, Color
            $product = $productModel->getProduct($data['product_id']);
            if (!$product) throw new Exception('Product not found');

            // Merge submitted data with product details
            $insertData = [
                'product_id'    => (int)$product['id'],
                'sku'           => $product['sku'],
                'item_code'     => $product['item_code'],
                'size'          => $product['size'],
                'color'         => $product['color'],
                'quantity'      => (int)$data['quantity'],
                'reason'        => $data['reason'],
                'user_id'       => (int)$data['user_id'],
                'movement_type' => $data['type'],
                'warehouse_id'  => $data['warehouse_id'],
                'location'      => $data['location']
            ];

            $result = $productModel->insertStockMovement($insertData);
            echo json_encode($result);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    public function updateStockLimits() {
        is_login();
        global $productModel;
        
        // Clear buffer and set header for JSON
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);

            if (!$data || !isset($data['product_id'])) {
                throw new Exception('Invalid data received');
            }

            $productId = (int)$data['product_id'];
            $minStock  = (int)($data['min_stock'] ?? 0);
            $maxStock  = (int)($data['max_stock'] ?? 0);

            // Call the model to update only the limits
            $result = $productModel->setProductLimits($productId, $minStock, $maxStock);

            echo json_encode(['success' => $result]);

        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    public function saveProductNotes() {
        is_login();
        global $productModel;
        //echo json_encode(['success' => false, 'message' => 'Invalid request']);
        //exit;
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $product_id = isset($input['product_id']) ? (int)$input['product_id'] : 0;
        $notes = isset($input['notes']) ? trim($input['notes']) : '';
        if ($product_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid product id']);
            exit;
        }
        $res = $productModel->updateProductNotes($product_id, $notes);
        echo json_encode($res);
        exit;
    }
    public function getFilteredStockHistory() {
        // Start output buffering to catch any accidental output
        ob_start();
        
        // Set headers first
        header('Content-Type: application/json');
        header('X-Requested-With: XMLHttpRequest');
        
        // Check login (but won't output on AJAX)
        is_login();
        
        // Clear any buffered output
        ob_end_clean();
        
        global $productModel;
        
        try {
            $_GET = array_map('trim', $_GET);
            
            $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
            $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
            $start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
            $end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';
            $type = isset($_GET['type']) ? trim($_GET['type']) : '';
            $warehouse = isset($_GET['warehouse']) ? trim($_GET['warehouse']) : '';
            // Read pagination from 'page_no' to avoid collision with router 'page' param
            if (isset($_GET['page_no'])) {
                $page = max(1, (int)$_GET['page_no']);
            } else {
                $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            }
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 10;
            
            if ($sku === '' && $product_id <= 0) {
                echo json_encode(['success' => false, 'message' => 'Invalid product filter']);
                exit;
            }
            
            $offset = ($page - 1) * $limit;
            
            $filters = [
                'sku' => $sku,
                'product_id' => $product_id,
                'start_date' => $start_date,
                'end_date' => $end_date,
                'type' => $type,
                'warehouse' => $warehouse
            ];
            
            $history = $productModel->getFilteredStockHistory($filters, $limit, $offset);
            $total = $productModel->getFilteredStockHistoryCount($filters);
            
            // Format the response
            $records = [];
            if (!empty($history)) {
                foreach ($history as $record) {
                    $record['formatted_date'] = date('d M Y', strtotime($record['created_at'] ?? ''));
                    $disp = $productModel->getStockLedgerDisplayForMovement($record);
                    $record['type'] = $disp['ledger_type'];
                    $record['icon'] = $disp['icon'];
                    $record['textColor'] = $disp['text_color_class'];
                    $records[] = $record;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'records' => $records,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
    public function inventoryLedger() {
        is_login();
        global $productModel;
        $sku = isset($_GET['sku']) ? trim($_GET['sku']) : '';
        if ($sku === '') {
            echo '<p>Invalid SKU.</p>';
            exit;
        }
        $stock_history = $productModel->enrichStockHistoryRowsForLedger($productModel->stock_history($sku));
        $warehouses = $productModel->getAllWarehouses();
        $productRow = $productModel->findBySku($sku) ?: [];
        $pid = (int)($productRow['id'] ?? 0);
        $data = [
            'stock_history' => $stock_history,
            'warehouses' => $warehouses,
            'products' => [
                'id' => $pid,
                'sku' => $sku,
                'warehouses' => $warehouses,
            ],
        ];
        renderTemplate('views/products/inventory_ledger.php', $data, 'Inventory Ledger');
    }
    
    public function getTransferStockForm() {
        is_login();
        global $productModel, $conn;

        $product_ids = isset($_GET['product_ids']) ? trim($_GET['product_ids']) : '';
        $transfer_id = isset($_GET['transfer_id']) ? (int)$_GET['transfer_id'] : 0;

        $products = [];

        if (!empty($product_ids)) {
            // Convert comma-separated IDs to array
            $ids = array_map('intval', array_filter(explode(',', $product_ids)));
            if (!empty($ids)) {
                // Fetch product details
                foreach ($ids as $id) {
                    $product = $productModel->getProduct($id);
                    if ($product) {
                        $products[] = $product;
                    }
                }
            }
        }

        // No products selected is allowed for empty transfer creation.

        // Fetch warehouses from exotic_address table
        $warehouses = [];
        $warehouseQuery = "SELECT id, address_title, address FROM exotic_address WHERE is_active = 1 ORDER BY address_title ASC";
        $warehouseResult = mysqli_query($conn, $warehouseQuery);
        if ($warehouseResult) {
            while ($row = mysqli_fetch_assoc($warehouseResult)) {
                $warehouses[] = $row;
            }
        }

        // Fetch users from vp_users table
        $users = [];
        $userQuery = "SELECT id, name FROM vp_users WHERE is_active = 1 ORDER BY name ASC";
        $userResult = mysqli_query($conn, $userQuery);
        if ($userResult) {
            while ($row = mysqli_fetch_assoc($userResult)) {
                $users[] = $row;
            }
        }

        // Load existing transfer for editing (if requested)
        $transfer = null;
        if ($transfer_id > 0) {
            require_once 'models/product/StockTransfer.php';
            $stockTransferModel = new StockTransfer($conn);
            $transfer = $stockTransferModel->getTransferById($transfer_id);
            if ($transfer) {
                // Fallback: ensure transfer order number is set in edit mode
                if (empty($transfer['transfer_order_no']) && !empty($transfer['from_warehouse']) && !empty($transfer['to_warehouse'])) {
                    $transfer['transfer_order_no'] = $stockTransferModel->generateUniqueTransferOrderNo((int)$transfer['from_warehouse'], (int)$transfer['to_warehouse']);
                }

                // Build a map of items keyed by product_id so we can prefill quantities/notes
                $itemsByProduct = [];
                foreach ($transfer['items'] as $item) {
                    if (!empty($item['product_id'])) {
                        $itemsByProduct[(int)$item['product_id']] = $item;
                    }
                }

                // Override product list with transfer items if they exist
                $ids = array_keys($itemsByProduct);
                if (!empty($ids)) {
                    $products = [];
                    foreach ($ids as $id) {
                        $product = $productModel->getProduct($id);
                        if ($product) {
                            // Prefill transfer quantities and notes
                            $product['transfer_qty'] = isset($itemsByProduct[$id]['transfer_qty']) ? (int)$itemsByProduct[$id]['transfer_qty'] : 0;
                            $product['item_notes'] = $itemsByProduct[$id]['item_notes'] ?? '';
                            $products[] = $product;
                        }
                    }
                }

                $product_ids = implode(',', array_unique($ids));
            }
        }

        // Render the transfer stock form as full page
        renderTemplate('views/products/transfer_stock_page.php', [
            'products' => $products,
            'product_ids' => $product_ids,
            'warehouses' => $warehouses,
            'users' => $users,
            'transfer' => $transfer,
        ], $transfer ? 'Edit Transfer Order' : 'New Transfer Order');
    }
    
    public function processTransferStock() {
        is_login();
        global $conn;
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $xhr = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $wantsJson = $xhr || stripos($contentType, 'application/json') !== false || stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;

        if ($wantsJson) {
            header('Content-Type: application/json');
        }
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $payload = ['success' => false, 'message' => 'Invalid request method'];
            if ($wantsJson) {
                echo json_encode($payload);
            } else {
                header('Location: ?page=products&action=stock_transfer');
            }
            exit;
        }
        
        // Get JSON payload or fallback to standard POST data
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data)) {
            $data = $_POST;
        }

        // if item arrays are provided separately, build items array
        if (empty($data['items']) && !empty($data['item_code']) && is_array($data['item_code']) && is_array($data['sku'])) {
            $items = [];
            $count = max(count($data['item_code']), count($data['sku']), count($data['transfer_qty'] ?? []), count($data['item_notes'] ?? []));
            for ($i = 0; $i < $count; $i++) {
                $items[] = [
                    'item_code' => $data['item_code'][$i] ?? '',
                    'sku' => $data['sku'][$i] ?? '',
                    'transfer_qty' => isset($data['transfer_qty'][$i]) ? $data['transfer_qty'][$i] : 0,
                    'item_notes' => $data['item_notes'][$i] ?? '',
                    'product_id' => isset($data['product_id'][$i]) ? $data['product_id'][$i] : 0,
                    'title' => isset($data['title'][$i]) ? $data['title'][$i] : ''
                ];
            }
            $data['items'] = $items;
        }

        // Validate input
        $transfer_order_no = isset($data['transfer_order_no']) ? trim($data['transfer_order_no']) : '';
        $product_ids = isset($data['product_ids']) ? trim($data['product_ids']) : '';

        // fallback transfer order from existing transfer when editing
        $transferId = isset($data['transfer_id']) ? (int)$data['transfer_id'] : 0;
        if (empty($transfer_order_no) && $transferId > 0) {
            $existingTransfer = $stockTransferModel->getTransferById($transferId);
            if (!empty($existingTransfer['transfer_order_no'])) {
                $transfer_order_no = $existingTransfer['transfer_order_no'];
            }
        }
        $from_warehouse = isset($data['from_warehouse']) ? intval($data['from_warehouse']) : 0;
        $to_warehouse = isset($data['to_warehouse']) ? intval($data['to_warehouse']) : 0;
        $dispatch_date = isset($data['dispatch_date']) ? trim($data['dispatch_date']) : '';
        $est_delivery_date = isset($data['est_delivery_date']) ? trim($data['est_delivery_date']) : '';
        $requested_by = isset($data['requested_by']) ? intval($data['requested_by']) : 0;
        $dispatch_by = isset($data['dispatch_by']) ? intval($data['dispatch_by']) : 0;
        
        // Validation
        if (empty($transfer_order_no)) {
            echo json_encode(['success' => false, 'message' => 'Transfer order number is required']);
            exit;
        }
        
        if (empty($product_ids)) {
            echo json_encode(['success' => false, 'message' => 'No products specified']);
            exit;
        }
        
        if ($from_warehouse <= 0 || $to_warehouse <= 0) {
            echo json_encode(['success' => false, 'message' => 'Please select source and destination warehouses']);
            exit;
        }
        
        if ($from_warehouse === $to_warehouse) {
            echo json_encode(['success' => false, 'message' => 'Source and destination warehouses must be different']);
            exit;
        }
        
        // Validate items have transfer quantities
        if (!isset($data['items']) || empty($data['items'])) {
            echo json_encode(['success' => false, 'message' => 'No items found']);
            exit;
        }
        
        $hasItems = false;
        foreach ($data['items'] as $item) {
            $transfer_qty = isset($item['transfer_qty']) ? (int)$item['transfer_qty'] : 0;
            if ($transfer_qty <= 0) {
                echo json_encode(['success' => false, 'message' => 'Transfer quantity must be greater than zero for all selected items']);
                exit;
            }
            $hasItems = true;
        }

        if (!$hasItems) {
            echo json_encode(['success' => false, 'message' => 'Please enter transfer quantity for at least one item']);
            exit;
        }

        // Determine transfer ID (if editing existing transfer) before validating items.
        $transferId = isset($data['transfer_id']) ? (int)$data['transfer_id'] : 0;

        // Validate each item transfer quantity doesn't exceed available stock
        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);

        // When editing, we want to allow keeping the same qty even if stock was already deducted.
        $existingQtyBySku = [];
        $existingTransfer = null;
        if ($transferId > 0) {
            $existingTransfer = $stockTransferModel->getTransferById($transferId);
            if ($existingTransfer && isset($existingTransfer['from_warehouse']) && $existingTransfer['from_warehouse'] == $from_warehouse) {
                foreach ($existingTransfer['items'] as $existingItem) {
                    $skuKey = trim($existingItem['sku'] ?? '');
                    if ($skuKey !== '') {
                        $existingQtyBySku[$skuKey] = (int)$existingItem['transfer_qty'];
                    }
                }
            }
        }

        foreach ($data['items'] as $item) {
            $transfer_qty = (int)$item['transfer_qty'];
            if ($transfer_qty <= 0) {
                continue;
            }

            $sku = trim($item['sku'] ?? '');
            if (empty($sku)) {
                continue;
            }

            $existingQty = $existingQtyBySku[$sku] ?? 0;
            $validation = $stockTransferModel->validateItemStock($sku, $from_warehouse, $transfer_qty, $existingQty);
            if (!$validation['valid']) {
                echo json_encode(['success' => false, 'message' => $validation['message']]);
                exit;
            }
        }
        
        // Handle E-Way Bill file upload/retain/remove
        $existingFile = isset($data['existing_eway_bill_file']) ? trim($data['existing_eway_bill_file']) : '';
        $ewayBillFile = $this->handleEwayBillFileUpload($existingFile);

        // Prepare data for model
        $transferData = [
            'transfer_id' => $transferId,
            'transfer_order_no' => $transfer_order_no,
            'from_warehouse' => $from_warehouse,
            'to_warehouse' => $to_warehouse,
            'dispatch_date' => $dispatch_date,
            'est_delivery_date' => $est_delivery_date,
            'requested_by' => $requested_by,
            'dispatch_by' => $dispatch_by,
            'booking_no' => isset($data['booking_no']) ? trim($data['booking_no']) : '',
            'vehicle_no' => isset($data['vehicle_no']) ? trim($data['vehicle_no']) : '',
            'vehicle_type' => isset($data['vehicle_type']) ? trim($data['vehicle_type']) : '',
            'driver_name' => isset($data['driver_name']) ? trim($data['driver_name']) : '',
            'driver_mobile' => isset($data['driver_mobile']) ? trim($data['driver_mobile']) : '',
            'eway_bill_file' => $ewayBillFile,
            'items' => $data['items'],
            'user_id' => $_SESSION['user_id'] ?? 1
        ];

        // If transfer_id is present, update existing transfer (and related items). Otherwise, create new.
        if ($transferId > 0) {
            // Ensure we use the authoritative transfer order number to update items.
            $existingTransfer = $stockTransferModel->getTransferById($transferId);
            $transferOrderNoToUse = $existingTransfer['transfer_order_no'] ?? $transfer_order_no;

            $updated = $stockTransferModel->updateTransfer($transferId, $transferData);
            $stockTransferModel->replaceTransferItems($transferOrderNoToUse, $data['items']);

            // Keep stock movements in sync with updated item quantities
            $stockTransferModel->syncTransferOutMovements($transferOrderNoToUse, $from_warehouse, $data['items'], $transferData['user_id']);

            $result = [
                'success' => true,
                'message' => $updated ? 'Stock transfer updated successfully' : 'Stock transfer updated (no changes)',
                'transfer_order_no' => $transferOrderNoToUse
            ];

            if (!$wantsJson) {
                header('Location: ?page=products&action=stock_transfer');
                exit;
            }

            echo json_encode($result);
            exit;
        }

        // Call model to create transfer
        $result = $stockTransferModel->createTransfer($transferData);

        if (!$wantsJson) {
            header('Location: ?page=products&action=stock_transfer');
            exit;
        }
        
        echo json_encode($result);
        exit;
    }
    
    private function handleEwayBillFileUpload($existingFile = '')
    {
        $rootPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..');
        $uploadDir = $rootPath . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'stock_trasfer_e_bill';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ewayBillFile = $existingFile;

        $remove = isset($_POST['remove_eway_bill_file']) && ($_POST['remove_eway_bill_file'] === '1' || $_POST['remove_eway_bill_file'] === 'on');

        if ($remove && !empty($existingFile)) {
            $existingPath = $rootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $existingFile);
            if (is_file($existingPath)) {
                @unlink($existingPath);
            }
            $ewayBillFile = '';
        }

        if (isset($_FILES['eway_bill_file']) && isset($_FILES['eway_bill_file']['tmp_name']) && is_uploaded_file($_FILES['eway_bill_file']['tmp_name'])) {
            $file = $_FILES['eway_bill_file'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                if (!empty($existingFile)) {
                    $oldPath = $rootPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $existingFile);
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }

                $originalName = basename($file['name']);
                $safeName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $originalName);
                $filename = uniqid('eway_', true) . '_' . $safeName;
                $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $filename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    $ewayBillFile = 'uploads/stock_trasfer_e_bill/' . $filename;
                }
            }
        }

        return $ewayBillFile;
    }

    public function getLastWarehouse() {
        header('Content-Type: application/json');
        global $conn;
        
        // Load model to get last warehouse
        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);
        
        $warehouse_id = $stockTransferModel->getLastWarehouse();
        
        if ($warehouse_id) {
            echo json_encode([
                'success' => true,
                'warehouse_id' => $warehouse_id
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'warehouse_id' => null
            ]);
        }
        exit;
    }

    public function getTransferOrderNo() {
        header('Content-Type: application/json');
        global $conn;

        $fromWarehouse = isset($_GET['from_warehouse']) ? (int)$_GET['from_warehouse'] : 0;
        $toWarehouse = isset($_GET['to_warehouse']) ? (int)$_GET['to_warehouse'] : 0;

        if ($fromWarehouse <= 0 || $toWarehouse <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid warehouse selection']);
            exit;
        }

        require_once 'models/product/StockTransfer.php';
        $stockTransferModel = new StockTransfer($conn);

        try {
            $nextOrderNo = $stockTransferModel->getNextTransferOrderNo($fromWarehouse, $toWarehouse);
            echo json_encode(['success' => true, 'transfer_order_no' => $nextOrderNo]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }
}