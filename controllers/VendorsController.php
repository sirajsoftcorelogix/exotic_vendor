<?php
require_once 'models/vendor/vendor.php';
require_once 'models/country/country.php';
require_once 'models/country/state.php';
require_once 'models/teams/Teams.php';

$vendorsModel = new Vendor($conn);
$countryModel = new Country($conn);
$stateModel = new State($conn);
$teamModel = new Teams($conn);

global $root_path;
global $domain;
class VendorsController {
    public function index() {
        is_login();
        global $vendorsModel;
        global $countryModel;
        global $stateModel;
        global $teamModel;

        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $groupname_filter = isset($_GET['groupname_filter']) ? trim($_GET['groupname_filter']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        $category_filter = isset($_GET['category_filter']) ? trim($_GET['category_filter']) : '';
        $team_filter = isset($_GET['team_filter']) ? trim($_GET['team_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $vendors_data = $vendorsModel->getAllVendorsListing($page_no, $limit, $search, $status_filter, $category_filter, $team_filter, $groupname_filter);

        $countryList = $countryModel->getAllCountries();
        $stateList = $stateModel->getAllStates(105); // India ID = 105
        $teamList = $teamModel->getAllTeams();
        $groupnameList = getCategoryFromTable();
        
        $data = [
            'vendors' => $vendors_data["vendors"],
            'page_no' => $page_no,
            'total_pages' => $vendors_data["totalPages"],
            'search' => $search,
            'totalPages'   => $vendors_data["totalPages"],
            'currentPage'  => $vendors_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $vendors_data["totalRecords"],
            'status_filter'=> $status_filter,
            'groupname_filter'=> $groupname_filter,
            'category_filter'=> $category_filter,
            'team_filter'=> $team_filter,
            'countryList' => $countryList["countries"],
            'stateList' => $stateList["states"],
            'category' => $vendorsModel->listCategory(),
            'teamList' => $teamList,
            'groupnameList' => $groupnameList
        ];
        
        renderTemplate('views/vendors/index.php', $data, 'Manage Vendors');
    }
    public function addVendorRecord() {
        is_login();
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if ($id > 0) {
                $result = $vendorsModel->updateVendor($id, $data);
                // Call external vendor API after local update:
                // - vendormodify if remote vendor_id exists
                // - vendorcreate if remote vendor_id is missing
                if (isset($result['success']) && $result['success'] === true) {
                    $vendor = $vendorsModel->getVendorById($id);
                    $editGroups = $data['editGroupname'] ?? '';
                    if (is_array($editGroups)) {
                        $editGroups = implode(',', array_values(array_unique(array_filter(array_map('trim', $editGroups), static function ($v) {
                            return $v !== '';
                        }))));
                    } else {
                        $editGroups = trim((string)$editGroups);
                    }
                    $editVendorType = $this->resolveVendorTypeFromGroups($editGroups);
                    $postData = [
                        'name' => isset($data['editVendorName']) ? trim($data['editVendorName']) : '',
                        'groupname' => $editGroups,
                        'vendor_type' => $editVendorType,
                        'webpage' => (isset($data['editWebpage']) && (string)$data['editWebpage'] === '1') ? '1' : '0'
                    ];

                    $remoteVendorId = trim((string)($vendor['vendor_id'] ?? ''));
                    if ($remoteVendorId !== '') {
                        $postData['vendor_id'] = $remoteVendorId;
                        $result['api_response'] = $this->modifyVendorExternal($postData);
                    } else {
                        $createApiResponse = $this->createVendorExternal($postData);
                        $result['api_response'] = $createApiResponse;
                        if (!empty($createApiResponse)) {
                            $apiData = json_decode($createApiResponse, true);
                            if (is_array($apiData) && isset($apiData['vendor_id'])) {
                                $vendorsModel->updateVendorRemoteId($id, $apiData['vendor_id']);
                            }
                        }
                    }
                }
            } else {
                $result = $vendorsModel->addVendor($data);
                
                // Call external API if vendor creation was successful
                if (isset($result['success']) && $result['success'] === true) {
                    $localVendorId = $result['inserted_id'] ?? 0;
                    $addGroups = $data['groupname'] ?? '';
                    if (is_array($addGroups)) {
                        $addGroups = implode(',', array_values(array_unique(array_filter(array_map('trim', $addGroups), static function ($v) {
                            return $v !== '';
                        }))));
                    } else {
                        $addGroups = trim((string)$addGroups);
                    }
                    $addVendorType = '';
                    if ($addGroups !== '') {
                        $first = trim((string)explode(',', $addGroups)[0]);
                        if ($first !== '') {
                            $addVendorType = 'vendor_' . $first;
                        }
                    }
                    
                    $postData = [
                        'name' => isset($data['addVendorName']) ? trim($data['addVendorName']) : '',
                        'groupname' => $addGroups,
                        'vendor_type' => $addVendorType,
                        'webpage' => (isset($data['addWebpage']) && (string)$data['addWebpage'] === '1') ? '1' : '0'
                    ];
                    $createApiResponse = $this->createVendorExternal($postData);
                    $result['api_response'] = $createApiResponse; // Include API response in the result
                    
                    // Update vendor with remote vendor_id from API response
                    if (!empty($createApiResponse) && $localVendorId > 0) {
                        $apiData = json_decode($createApiResponse, true);
                        if (is_array($apiData) && isset($apiData['vendor_id'])) {
                            $remoteVendorId = $apiData['vendor_id'];
                            $updateResult = $vendorsModel->updateVendorRemoteId($localVendorId, $remoteVendorId);
                        }
                    }
                }
            }
            echo json_encode($result);
        }
        exit;
    }
    public function createVendorExternal($postData){
        if (!empty($postData)) {
            $apiUrl = 'https://www.exoticindia.com/vendor-api/product/vendorcreate';
            
            $headers = [
                'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
                'x-adminapitest: 1',
                'Content-Type: application/x-www-form-urlencoded'
            ];
            
            $ch = curl_init($apiUrl);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $apiResponse = curl_exec($ch);
            $apiError = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($apiResponse === false) {
                error_log('Vendor API call failed: ' . $apiError);
            } else {
                error_log('Vendor API response: HTTP ' . $httpCode);
            }
            return $apiResponse;
        }
    }

    private function resolveVendorTypeFromGroups(string $groupsCsv): string
    {
        $first = trim((string)explode(',', $groupsCsv)[0]);
        if ($first === '') {
            return '';
        }
        $key = strtolower($first);
        $map = [
            'sculptures' => 'vendor_sculptures',
            'sculpture' => 'vendor_sculptures',
            'statues' => 'vendor_statues',
            'homeandliving' => 'vendor_homeandliving',
            'paintings' => 'vendor_paintings',
            'textiles' => 'vendor_textiles',
            'jewelry' => 'vendor_jewelry',
            'book' => 'vendor_book',
        ];
        return $map[$key] ?? ('vendor_' . $key);
    }

    public function modifyVendorExternal(array $postData): string
    {
        if (empty($postData['vendor_id'])) {
            return json_encode(['success' => false, 'message' => 'vendor_id is required for vendormodify']);
        }

        $apiUrl = 'https://www.exoticindia.com/vendor-api/product/vendormodify';
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $apiError = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($apiResponse === false) {
            error_log('Vendor modify API call failed: ' . $apiError);
            return json_encode(['success' => false, 'message' => 'Vendor modify API call failed', 'error' => $apiError]);
        }

        error_log('Vendor modify API response: HTTP ' . $httpCode);
        return (string)$apiResponse;
    }
    public function deleteVendorExternal($vendorId){
        $vendorId = trim((string)$vendorId);
        if ($vendorId === '') {
            return ['success' => false, 'message' => 'Remote vendor_id is missing.'];
        }

        $apiUrl = 'https://www.exoticindia.com/vendor-api/product/vendordelete';
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['vendor_id' => $vendorId]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $apiResponse = curl_exec($ch);
        $apiError = curl_error($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($apiResponse === false) {
            return ['success' => false, 'message' => 'Vendor delete API call failed: ' . $apiError];
        }

        $decoded = json_decode((string)$apiResponse, true);
        if ($httpCode >= 400) {
            $msg = is_array($decoded) && !empty($decoded['message']) ? (string)$decoded['message'] : 'HTTP ' . $httpCode;
            return ['success' => false, 'message' => 'Vendor delete API failed: ' . $msg];
        }

        if (is_array($decoded)) {
            if ((isset($decoded['success']) && $decoded['success'] === false) || (isset($decoded['status']) && strtolower((string)$decoded['status']) === 'error')) {
                $msg = !empty($decoded['message']) ? (string)$decoded['message'] : 'Remote delete returned failure.';
                return ['success' => false, 'message' => 'Vendor delete API failed: ' . $msg];
            }
        }

        return ['success' => true, 'message' => 'Remote vendor deleted.'];
    }
    public function delete() {
        global $vendorsModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $vendor = $vendorsModel->getVendorById($id);
            if (!$vendor || !is_array($vendor)) {
                echo json_encode(['success' => false, 'message' => 'Vendor not found.']);
                exit;
            }

            $guard = $vendorsModel->canDeleteVendor($id);
            if (empty($guard['success'])) {
                echo json_encode($guard);
                exit;
            }

            $remoteVendorId = trim((string)($vendor['vendor_id'] ?? ''));
            if ($remoteVendorId !== '') {
                $remoteDelete = $this->deleteVendorExternal($remoteVendorId);
                if (empty($remoteDelete['success'])) {
                    echo json_encode([
                        'success' => false,
                        'message' => $remoteDelete['message'] ?? 'Remote vendor delete failed.'
                    ]);
                    exit;
                }
            }

            $result = $vendorsModel->deleteVendor($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid vendor ID.'.$id]);
        }
        exit;
    }
    public function getVendorDetails() {
        global $vendorsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $vendor = $vendorsModel->getVendorById($id);
            $vendor['categories'] = $vendorsModel->getVendorCategories($id);
            $vendor['teamIds'] = $vendorsModel->getVendorTeams($id);
            if ($vendor) {
                echo json_encode($vendor);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Vendor not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid vendor ID.']);
        }
        exit;
    }
    public function getAllCountries() {
        global $countryModel;
        $countries = $countryModel->getAllCountries();
        echo json_encode($countries);
        exit;
    }
    public function getStatesByCountry($country_id) {
        global $stateModel;
        $states = $stateModel->getAllStates($country_id);
        echo json_encode($states["states"]);
        exit;
    }
    public function getBankDetails() {
        global $vendorsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $bankdtls = $vendorsModel->getBankDetailsById($id);
            if (!empty($bankdtls) && is_array($bankdtls)) {
                echo json_encode($bankdtls);
            } else {
                echo json_encode(['status' => 'success', 'message' => '']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid vendor ID.']);
        }
        exit;
    }
    public function addBankDetails() {
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $data['bdStatus'] = 1;
            $vendor_id = isset($data['vendor_id']) ? (int)$data['vendor_id'] : 0;
            $bankdtls = $vendorsModel->getBankDetailsById($vendor_id);
            if ($bankdtls) {
                $result = $vendorsModel->updateBankDetails($data);
            } else {
                $result = $vendorsModel->saveBankDetails($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function getTeamMembers() {
        global $vendorsModel;
        $team_id = isset($_GET['teamId']) ? $_GET['teamId'] : 0;
        if ($team_id > 0) {
            $members = $vendorsModel->getTeamMembers($team_id);
            echo json_encode($members);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid team ID.']);
        }
        exit;
    }
    public function checkVendorName() {
        global $vendorsModel;
        if (!isset($_GET['vendorName']) || empty($_GET['vendorName'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Vendor Name.']);
            exit;
        }

        $vendorName = trim($_GET['vendorName']);
        $result = $vendorsModel->checkVendorName($vendorName);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    public function checkEmail() {
        global $vendorsModel;
        if (!isset($_GET['email']) || empty($_GET['email'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Email Address.']);
            exit;
        }

        $email = trim($_GET['email']);
        $result = $vendorsModel->checkEmail($email);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }
    public function checkPhoneNumber() {
        global $vendorsModel;

        if (!isset($_GET['phone']) || empty($_GET['phone'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid Phone Number.']);
            exit;
        }

        $phone = trim($_GET['phone']);
        $result = $vendorsModel->checkPhoneNumber($phone);
        header('Content-Type: application/json');
        echo json_encode($result);
        exit;

    }
    public function importSalesAnalyticsData($from_date = "", $to_date = "") {
        ini_set('max_execution_time', 0);
        /*if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }*/
        $groupname = ["book", "sculptures", "paintings", "jewelry", "textiles", "homelandliving"];
        if($from_date == "" && $to_date == "") {
            $from_date = "2025-10-27";//date('Y-m-d');
            $to_date = "2025-10-31";//date('Y-m-d', strtotime('-1 days'));
        }
        //echo $from_date . " -- ". $to_date; exit;
        $url = 'https://www.exoticindia.com/vendor-api/data/sale_data'; // Production API new endpoint

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $dataResult = array();
        for($i = 0; $i < count($groupname)-1; $i++) {
            $gpName = $groupname[$i];
            $postData = [
                'groupname' => $gpName,
                'from_date' => $from_date,
                'to_date' => $to_date
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
           
            if ($response === false) {
                renderTemplateClean('views/errors/error.php', ['message' => 'API request failed: ' . $error], 'API Error');
                return;
            }

            $result = json_decode($response, true);
            $dataResult[$gpName] = $result;
            ignore_user_abort(true);
            usleep (500000);
        } //end for loop

        print_array($dataResult);
        exit;
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
        print_array($orders); exit;
        /*$imported = 0; $totalorder = 0;
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
                    'esd' => $esd
                    ];
					$totalorder++;                
                    
                    $data = $ordersModel->insertOrder($rdata);
                    $result[] = $data;
                    //add products
                    $pdata[] = $ordersModel->addProducts($rdata);                   
                    //$vdata = $ordersModel->addVendorIfNotExists($rdata['vendor']);
                    if (isset($data['success']) && $data['success'] == 1) {                        
                        $imported++;
                    } 
                   // print_array($rdata);                   
            }
           
        }*/
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        /*if($log_id > 0){
            $log_update_data = [
                'end_time' => date('Y-m-d H:i:s'),
                'successful_imports' => $imported,
                'total_orders' => $totalorder,
                'error' => isset($error) ? $error : '',
                'log_details' => json_encode($result),
                'max_ordered_time' => $order['processed_time'] ?? '',
                'from_date' => $from_date,
                'to_date' => $to_date,
                'add_product_log' => NULL,//json_encode($pdata)
            ];
            //print_array($log_update_data);
            $ordersModel->updateOrderImportLog($log_id, $log_update_data);
        }*/
        /*renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            'products' => json_encode($pdata)
        ], 'Import Orders Result');*/
        $data = [
            'vendors' => $orders,
        ];

        renderTemplate('views/vendors/sales_analytics.php', $data, 'Sales Analytics');
    }
    public function vendorProductsMap() {
        is_login();
        global $vendorsModel;
        $v_id = isset($_GET['v_id']) ? (int)$_GET['v_id'] : 0;
        if ($v_id > 0) {
            $products = $vendorsModel->getProductsByVendorId($v_id);
            $vendor = $vendorsModel->getVendorById($v_id);
            $mappingProducts = $vendorsModel->getmappingProductsByVendorId($v_id);
            $data = [
                'products' => $products,
                'vendor' => $vendor,
                'mappingProducts' => $mappingProducts
            ];
            renderTemplate('views/vendors/vendor_products_map.php', $data, 'Vendor Products Mapping');
        } else {
            renderTemplateClean('views/errors/error.php', ['message' => ['type'=>'error','text'=>'Invalid Vendor ID.']], 'Error');
        }
    }
    public function generateProductBlock() {
        is_login();
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
            $item_code = isset($_POST['item_code']) ? trim($_POST['item_code']) : '';
            if ($vendor_id > 0 && !empty($item_code)) {
                $product = $vendorsModel->getProductByCode($item_code);
                if ($product) {
                    ob_start();
                    ?>
                    <div class="product-card group relative border border-gray-300 rounded-md p-3 flex items-start bg-white hover:border-gray-400 transition-colors" data-item-code="<?php echo ($product['item_code']); ?>">
                        <input type="hidden" name="product_codes[]" value="<?php echo ($product['id']); ?>" />
                        <input type="hidden" name="item_codes[]" value="<?php echo ($product['item_code']); ?>" />
                        <!-- Floating Delete Button -->
                        <button onclick="deleteCard(this)"
                                class="absolute -top-3 -right-3 w-8 h-8 bg-brand-red text-white rounded-full flex items-center justify-center shadow-md hover:bg-red-600 transition-colors z-10 cursor-pointer">
                            &times;
                        </button>

                        <!-- Product Image -->
                         <div class="w-24 h-32 flex-shrink-0 border border-gray-200 mr-4 product-placeholder rounded-sm">
                            <img src="<?php echo ($product['image']) ?? ""; ?>" />
                        </div>

                        <!-- Product Details -->
                        <div class="flex-1">
                            <h3 class="text-md font-semibold text-gray-800 group-hover:text-gray-900 transition-colors">
                                <?php echo ($product['title']) ?? ""; ?>
                            </h3>
                        </div>
                    </div>
                    <?php
                    $html = ob_get_clean();
                    echo json_encode(['success' => true, 'html' => $html]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'No product found with the given item code.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
            }
        }
        exit;
    }
    public function saveVendorProductsMap() {
        is_login();
        global $vendorsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $vendor_id = isset($_POST['vendor_id']) ? (int)$_POST['vendor_id'] : 0;
            $product_codes = isset($_POST['product_codes']) ? $_POST['product_codes'] : [];
            $item_codes = isset($_POST['item_codes']) ? $_POST['item_codes'] : [];
            if ($vendor_id > 0 && is_array($product_codes)) {
                $result = $vendorsModel->saveVendorProductsMapping($vendor_id, $product_codes, $item_codes);
                $_SESSION["mapping_message"] = $result['message'];
                header("location: " . base_url('?page=vendors&action=list'));
                exit;
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
            }
        }
        exit;
    }
    public function UpdateVendorCode() {
        is_login();
        global $vendorsModel;
        if($_SESSION["user"]["role_id"] != 1) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
            exit;
        }
        $result = $vendorsModel->updateVendorCode();
        echo json_encode($result);
        exit;
    }
    public function fetchAllVendors(){
        global $vendorsModel;
        $categories = getCategoryFromTable();
        $groupname = reset($categories); // Get the first category name
        $apivendors = [];
        foreach($categories as $groupname) {
        $url = 'https://www.exoticindia.com/vendor-api/product/vendorlist?groupname=' . urlencode($groupname);
        
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded'
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            echo json_encode(['status' => 'error', 'message' => 'API request failed: ' . $error]);
            exit;
        }
        
        $result = json_decode($response, true);
        //echo "Group: " . $groupname . "\n";
        //print_array($result);
        $apivendors[$groupname] = $result['vendors'] ?? [];
        }
        //print_array($apivendors);
        $result = $vendorsModel->saveVendorsFromAPI($apivendors);
        echo json_encode($result);
        exit;
    }
    public function getAllVendors(){
        global $vendorsModel;
        $groupname = isset($_GET['groupname']) ? trim($_GET['groupname']) : '';
        if(empty($groupname)) {
            echo json_encode(['status' => 'error', 'message' => 'Group name is required.']);
            exit;
        }
        //fetch category by groupname
        //echo "Groupname received: " . $groupname; // Debugging line
        $groupname = getGroupnameByCategory($groupname);
        //echo "Mapped groupname: " . $groupname; // Debugging line
        $vendors = $vendorsModel->getVendorsByGroup($groupname);
        echo json_encode($vendors);
        exit;
    }
}
?>