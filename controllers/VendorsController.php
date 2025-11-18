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
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        $category_filter = isset($_GET['category_filter']) ? trim($_GET['category_filter']) : '';
        $team_filter = isset($_GET['team_filter']) ? trim($_GET['team_filter']) : '';

        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $vendors_data = $vendorsModel->getAllVendorsListing($page_no, $limit, $search, $status_filter, $category_filter, $team_filter);

        $countryList = $countryModel->getAllCountries();
        $stateList = $stateModel->getAllStates(105); // India ID = 105
        $teamList = $teamModel->getAllTeams();

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
            'category_filter'=> $category_filter,
            'team_filter'=> $team_filter,
            'countryList' => $countryList["countries"],
            'stateList' => $stateList["states"],
            'category' => $vendorsModel->listCategory(),
            'teamList' => $teamList
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
            } else {
                $result = $vendorsModel->addVendor($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function delete() {
        global $vendorsModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
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
        $groupname = ["one among book", "sculptures", "paintings", "jewelry", "textiles", "homelandliving"];
        print_array($groupname); exit;
        echo "1"; exit;
        
        global $vendorsModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        if($from_date == "" && $to_date == "") {
            $from_date = date('Y-m-d H:i:s');
            $to_date = strtotime('-1 days');
        }
        echo $from_date . " -- ". $to_date; exit;

        $from_date = strtotime('-1 days');
        $to_date = time();
        //$from_date = strtotime(date('12-08-2025 00:00:00')); // Example fixed date
        //$to_date = strtotime(date('13-08-2025 00:00:00'));
        //$from_date = 1755101792; // Example fixed date 12-08-2025 00:00:00
        //$to_date = 1755102092;   // Example fixed date 13-08-2025 23:59:59
        //$url = 'https://www.exoticindia.com/action';
        $url = 'https://www.exoticindia.com/vendor-api/data/sale_data'; // Production API new endpoint
       
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
}
?>