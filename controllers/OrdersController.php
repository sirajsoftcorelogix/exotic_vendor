<?php
require_once 'models/order/order.php';
require_once 'models/comman/tables.php';
require_once 'models/searches/saved_search.php';
require_once 'models/order/po_invoice.php';
require_once 'models/product/product.php';
$ordersModel = new Order($conn);
$commanModel = new Tables($conn);
$savedSearchModel = new SavedSearch($conn);
$poInvoiceModel = new POInvoice($conn);
$productModel = new Product($conn);
global $root_path;
global $domain;
class OrdersController
{

    public function index()
    {
        is_login();
        global $ordersModel;
        global $commanModel;
        global $savedSearchModel;
        //sanitize and validate input parameters
        $_GET = sanitizeGet($_GET);
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
        if (!empty($_GET['sku'])) {
            $filters['sku'] = $_GET['sku'];
        }
        if (!empty($_GET['order_from']) && !empty($_GET['order_till'])) {
            $filters['order_from'] = $_GET['order_from'];
            $filters['order_till'] = $_GET['order_till'];
        }

        // if(!empty($_GET['daterange'])){
        //     echo urldecode($_GET['daterange']);
        //     $dateRange = explode(' - ', $_GET['daterange']);       
        //     print_array($dateRange);     
        //     if (count($dateRange) === 2) {
        //         $filters['order_from'] = date('Y-m-d', strtotime($dateRange[0]));
        //         $filters['order_till'] = date('Y-m-d', strtotime($dateRange[1]));
        //     }
        // }
        if (!empty($_GET['item_name'])) {
            $filters['title'] = $_GET['item_name'];
        }
        if (!empty($_GET['min_amount'])) {
            $filters['min_amount'] = $_GET['min_amount'];
        }
        if (!empty($_GET['max_amount'])) {
            $filters['max_amount'] = $_GET['max_amount'];
        }
        if (!empty($_GET['po_no'])) {
            $filters['po_no'] = $_GET['po_no'];
        }
        if (!empty($_GET['status'])) {
            $filters['status_filter'] = $_GET['status'];
        }

        if (!empty($_GET['category']) && $_GET['category'] != 'all') {
            $filters['category'] = $_GET['category'];
        } else {
            $filters['category'] = 'all';
        }
        if (!empty($_GET['country'])) {
            $filters['country'] = $_GET['country'];
        }
        if (!empty($_GET['options']) && $_GET['options'] == 'express') {
            $filters['options'] = 'express';
        }
        if (!empty($_GET['sort'])) {
            $filters['sort'] = strtolower($_GET['sort']);
        } else {
            $filters['sort'] = 'desc'; // Default sort order
        }
        if (!empty($_GET['payment_type']) && $_GET['payment_type'] != 'all') {
            $filters['payment_type'] = $_GET['payment_type'];
        } else {
            $filters['payment_type'] = 'all';
        }
        if (!empty($_GET['staff_name'])) {
            $filters['staff_name'] = $_GET['staff_name'];
        }
        if (!empty($_GET['priority'])) {
            $filters['priority'] = $_GET['priority'];
        }
        if (!empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = $_GET['vendor_id'];
        }
        if (!empty($_GET['agent'])) {
            $filters['agent'] = $_GET['agent'];
        }
        if (!empty($_GET['publisher'])) {
            $filters['publisher'] = $_GET['publisher'];
        }
        if (!empty($_GET['author'])) {
            $filters['author'] = $_GET['author'];
        }
        //unshipped
        if (!empty($_GET['options']) && $_GET['options'] == 'unshipped') {
            $filters['unshipped'] = true;
        }
        //sort-date-range
        if (!empty($_GET['sortdaterange'])) {
            $filters['sortdaterange'] = $_GET['sortdaterange'];
        }


        //order status list
        $statusList = $commanModel->get_order_status_list();
        $order_status_row = $commanModel->get_order_status();
        $countryList = $commanModel->get_counry_list();
        //print_array($order_status_list);
        // Use pagination in the database query for better performance
        //print_r($_GET);
        //print_r($filters);
        $orders = $ordersModel->getAllOrders($filters, $limit, $offset);

        $statusLogsByOrder = [];
        $orderIds = array_map('intval', array_column($orders, 'order_id'));
        if (!empty($orderIds)) {
            global $conn;
            $orderList = implode(',', $orderIds);
            $sql = "SELECT osl.*, u.name AS changed_by_username\n                    FROM vp_order_status_log osl\n                    JOIN vp_users u ON osl.changed_by = u.id\n                    WHERE osl.order_id IN ($orderList)\n                      AND u.is_deleted = 0\n                    ORDER BY osl.order_id ASC, osl.id ASC";
            $result = $conn->query($sql);
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $statusLogsByOrder[$row['order_id']][] = $row;
                }
            }
        }

        foreach ($orders as $key => $order) {
            $orders[$key]['status_log'] = $statusLogsByOrder[$order['order_id']] ?? [];
        }
        // Agent filter: use assignment from vp_order_status_log (change_date) instead of vp_orders.assign_date
        //    if (!empty($_GET['agent'])) {
        //        //sort orders by agent assignment date
        //        usort($orders, function($a, $b) use ($assignmentDates) {
        //             return strtotime($assignmentDates[$a['order_id']])
        //                 - strtotime($assignmentDates[$b['order_id']]);
        //         });            

        //     }
        //print_array($orders);  
        $total_orders = $ordersModel->getOrdersCount($filters);
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        // Prepare saved searches for current user
        $user_id = $_SESSION['user']['id'] ?? 0;
        $saved_searches = [];
        if ($user_id) {
            $saved_searches = $savedSearchModel->getByUser($user_id, 'orders');
        }

        // Render the orders view
        renderTemplate('views/orders/index.php', [
            'orders' => $orders,
            'total_orders' => $total_orders,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'order_status_list' => $order_status_row,
            'status_list' => $statusList,
            'country_list' => $countryList,
            'payment_types' => $ordersModel->getPaymentTypes(),
            'staff_list' => $commanModel->get_staff_list(),
            'filters' => $filters,
            'saved_searches' => $saved_searches
        ], 'Manage Orders');
    }

    public function viewOrder()
    {
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

    public function importOrders()
    {
        //is_login();
        global $ordersModel;
        global $productModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        $lastLog = $ordersModel->getLastImportLog();

        //log create
        $log_data = ['start_time' => date('Y-m-d H:i:s')];
        $log_id = 0;

        if ($logs = $ordersModel->orderImportLog($log_data)) {
            $log_id = $logs['insert_id'];
        }        // Set your date range (example: last 7 days)

        $from_date = strtotime('-1 days');
        //echo "<br>";
        if ($lastLog && !empty($lastLog['max_ordered_time'])) {
            $from_date = $lastLog['max_ordered_time'];
        }
        $to_date = time();
        //$from_date = strtotime(date('12-08-2025 00:00:00')); // Example fixed date
        //$to_date = strtotime(date('13-08-2025 00:00:00'));
        //$from_date = 1755101792; // Example fixed date 12-08-2025 00:00:00
        //$to_date = 1755102092;   // Example fixed date 13-08-2025 23:59:59
        //$url = 'https://www.exoticindia.com/action';
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint

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
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'Invalid API response format.']], 'API Error');
            return;
        }
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        //page
        $page = $orders['total_pages'];
        if ($page > 1) {
            for ($i = 2; $i <= $page; $i++) {
                $postData['page'] = $i;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                $response = curl_exec($ch);
                $error = curl_error($ch);
                curl_close($ch);
                if ($response === false) {
                    renderTemplateClean('views/errors/error.php', ['message' => 'API request failed on page ' . $i . ': ' . $error], 'API Error');
                    return;
                }
                $pageOrders = json_decode($response, true);
                if (is_array($pageOrders) && !empty($pageOrders['orders'])) {
                    $orders['orders'] = array_merge($orders['orders'], $pageOrders['orders']);
                }
            }
        }
        $batch = $this->importVendorOrdersFromApiPayload($orders['orders']);
        $imported = $batch['imported'];
        $totalorder = $batch['total'];
        $result = $batch['result'];
        $pdata = $batch['pdata'];
        $addressdata = $batch['addressdata'];

        $maxOrderedTimeForLog = (int)$to_date;
        foreach ($orders['orders'] ?? [] as $ord) {
            $pt = (int)($ord['processed_time'] ?? 0);
            if ($pt > $maxOrderedTimeForLog) {
                $maxOrderedTimeForLog = $pt;
            }
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count
        if ($log_id > 0) {
            $log_update_data = [
                'end_time' => date('Y-m-d H:i:s'),
                'successful_imports' => $imported,
                'total_orders' => $totalorder,
                'error' => isset($error) ? $error : '',
                'log_details' => NULL, //json_encode($result),
                'max_ordered_time' => $maxOrderedTimeForLog,
                'from_date' => $from_date,
                'to_date' => $to_date,
                'add_product_log' => NULL, //json_encode($pdata)
            ];
            //print_array($log_update_data);
            $ordersModel->updateOrderImportLog($log_id, $log_update_data);
        }
        //print_array($result);
        renderTemplateClean('views/orders/import_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }

    /**
     * Import vendor API order payload into vp_orders (shared by cron import and POS checkout).
     */
    private function importVendorOrdersFromApiPayload(array $ordersList, ?string $onlyOrderNumber = null): array
    {
        global $ordersModel, $productModel;

        $statusList = $ordersModel->adminOrderStatusList('true');
        $skipIds = ['2658982', '2660434', '2662287', '469282', '2664206'];
        $imported = 0;
        $totalorder = 0;
        $result = [];
        $pdata = [];
        $addressdata = [];

        foreach ($ordersList as $order) {
            $orderId = (string)($order['orderid'] ?? '');
            if ($onlyOrderNumber !== null && $orderId !== $onlyOrderNumber) {
                continue;
            }
            if (in_array($orderId, $skipIds, true)) {
                continue;
            }

            $customerdata = $ordersModel->addCustomerIfNotExists($order);

            $cart = $order['cart'] ?? [];
            if (!is_array($cart) || count($cart) === 0) {
                continue;
            }

            foreach ($cart as $item) {
                $orderdate = !empty($order['processed_time']) ? date('Y-m-d H:i:s', $order['processed_time']) : date('Y-m-d H:i:s');
                $esd = '0000-00-00';
                $local_stock_int = (int)floatval($item['local_stock']);
                $lead_time_int = (int)floatval($item['leadtime']);
                if ($item['marketplace_vendor'] == 'exoticindia' || empty($item['marketplace_vendor'])) {
                    if (!empty($local_stock_int) && $local_stock_int > 0) {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + 3 days'));
                    } else {
                        $hasExpress = false;
                        $options = $item['options'] ?? null;
                        if (!empty($options)) {
                            if (is_string($options)) {
                                $decoded = json_decode($options, true);
                                if (is_array($decoded)) {
                                    $hasExpress = in_array('express', $decoded, true);
                                } else {
                                    $hasExpress = stripos($options, 'express') !== false;
                                }
                            } elseif (is_array($options)) {
                                $hasExpress = in_array('express', $options, true);
                            }
                        }
                        if ($hasExpress) {
                            $esd = date('Y-m-d', strtotime($orderdate . ' + 0 days'));
                        } else {
                            $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $lead_time_int . ' days'));
                        }
                    }
                } else {
                    if (!empty($local_stock_int) && $local_stock_int > 0) {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $local_stock_int . ' days'));
                    } else {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + ' . ($lead_time_int) . ' days'));
                    }
                }
                $rdata = [
                    'sku' => $item['sku'] ?? '',
                    'order_number' => $order['orderid'] ?? '',
                    'shipping_country' => $order['shipping_country'] ?? '',
                    'title' => !empty($item['title']) ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $item['title']) : '',
                    'description' => $item['description'] ?? '',
                    'item_code' => $item['itemcode'] ?? '',
                    'size' => $item['size'] ?? '',
                    'color' => $item['color'] ?? '',
                    'groupname' => $item['groupname'] ?? '',
                    'subcategories' => !empty($item['subcategories']) ? preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $item['subcategories']) : '',
                    'currency' => $item['currency'] ?? '',
                    'itemprice' => $item['itemprice'] ?? '',
                    'finalprice' => $item['finalprice'] ?? '',
                    'image' => $item['image'] ?? '',
                    'marketplace_vendor' => $item['marketplace_vendor'] ?? '',
                    'quantity' => $item['qty'] ?? '',
                    'options' => $item['options'] ?? 0,
                    'gst' => $item['gst'] ?? '',
                    'hsn' => $item['hscode'] ?? '',
                    'local_stock' => is_numeric($item['local_stock'] ?? null) ? (float) $item['local_stock'] : 0.0,
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
                    'publisher' => $item['publisher'] ?? '',
                    'author' => $item['author'] ?? '',
                    'shippingfee' => $item['shippingfee'] ?? '',
                    'sourcingfee' => $item['sourcingfee'] ?? '',
                    'status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA')
                        ? 'shipped'
                        : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),
                    'esd' => $esd,
                    'agent_id' => 0,
                ];
                if (strtoupper((string)($order['payment_type'] ?? '')) === 'COD' && (float)($item['itemprice'] ?? 0) >= 5000) {
                    $rdata['status'] = 'cod_confirmation_required';
                    $rdata['agent_id'] = 31;
                }
                $rdata['customer_id'] = $customerdata['customer_id'] ?? 0;
                $rdata['store_name'] = $order['store_name'] ?? '';
                $rdata['custom_reduce'] = $order['custom_reduce'] ?? 0;
                $totalorder++;

                $data = $ordersModel->insertOrder($rdata);
                $result[] = $data;
                $pdata[] = $ordersModel->addProducts($rdata);

                if (isset($data['success']) && $data['success'] == 1) {
                    $imported++;
                }

                $vendorRaw = trim((string)($item['vendor'] ?? ''));
                $vendorNames = array_values(array_unique(array_filter(array_map(
                    static function ($v) {
                        return trim((string)$v);
                    },
                    preg_split('/\s*,\s*/', $vendorRaw)
                ))));
                $firstVendorId = 0;
                foreach ($vendorNames as $vendorname) {
                    $vendorsuccess = $ordersModel->addVendorIfNotExists($vendorname);
                    $currentVendorId = (int)($vendorsuccess['vendor_id'] ?? 0);
                    if ($firstVendorId <= 0 && $currentVendorId > 0) {
                        $firstVendorId = $currentVendorId;
                    }
                }
                if ($firstVendorId > 0) {
                    $productModel->saveProductVendor($rdata['item_code'], $firstVendorId, '');
                }
            }

            try {
                $addressdata[] = $ordersModel->insertAddressInfo($order, $customerdata['customer_id'] ?? 0);
            } catch (\Throwable $e) {
                error_log('[order import insertAddressInfo] ' . $e->getMessage());
                $addressdata[] = ['success' => false, 'message' => $e->getMessage()];
            }
        }

        return [
            'imported' => $imported,
            'total' => $totalorder,
            'result' => $result,
            'pdata' => $pdata,
            'addressdata' => $addressdata,
        ];
    }

    /**
     * True when vp_orders lines and vp_order_info exist (required for POS auto-invoice).
     */
    public function isOrderReadyForPosCheckout(string $orderNumber): bool
    {
        global $conn;

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return false;
        }

        $hasLines = false;
        $stmt = $conn->prepare('SELECT 1 FROM vp_orders WHERE order_number = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $orderNumber);
            $stmt->execute();
            $hasLines = (bool)$stmt->get_result()->fetch_row();
            $stmt->close();
        }
        if (!$hasLines) {
            return false;
        }

        $hasInfo = false;
        $stmtInfo = $conn->prepare('SELECT 1 FROM vp_order_info WHERE order_number = ? LIMIT 1');
        if ($stmtInfo) {
            $stmtInfo->bind_param('s', $orderNumber);
            $stmtInfo->execute();
            $hasInfo = (bool)$stmtInfo->get_result()->fetch_row();
            $stmtInfo->close();
        }

        return $hasInfo;
    }

    /**
     * @return array{ok: bool, orders: array<int, array<string, mixed>>, error: string}
     */
    private function fetchVendorOrderPayloadForCheckout(string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['ok' => false, 'orders' => [], 'error' => 'Order number missing'];
        }

        $url = 'https://www.exoticindia.com/vendor-api/order/fetch';
        $postData = [
            'makeRequestOf' => 'vendors-orderjson',
            'orderid' => $orderNumber,
        ];
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Content-Type: application/x-www-form-urlencoded',
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 45);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return ['ok' => false, 'orders' => [], 'error' => 'Vendor API error: ' . $error];
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['orders']) || !is_array($decoded['orders'])) {
            return ['ok' => false, 'orders' => [], 'error' => 'No order data from vendor API'];
        }

        return ['ok' => true, 'orders' => $decoded['orders'], 'error' => ''];
    }

    /**
     * Fetch and import one order for POS checkout (no secret key, no HTML output).
     */
    public function importSingleOrderForCheckout(string $orderNumber): array
    {
        global $conn;

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['success' => false, 'message' => 'Order number missing', 'imported' => 0, 'total' => 0];
        }

        $hasLines = $this->isOrderReadyForPosCheckout($orderNumber);

        $fetch = $this->fetchVendorOrderPayloadForCheckout($orderNumber);
        if (!$fetch['ok']) {
            return [
                'success' => $hasLines,
                'message' => $hasLines ? 'Order already in system' : $fetch['error'],
                'imported' => 0,
                'total' => 0,
            ];
        }

        $batch = $this->importVendorOrdersFromApiPayload($fetch['orders'], $orderNumber);
        $ready = $this->isOrderReadyForPosCheckout($orderNumber);

        return [
            'success' => $ready,
            'message' => ($batch['imported'] > 0)
                ? 'Imported ' . $batch['imported'] . ' line(s)'
                : ($ready ? 'Order ready in system' : 'Import did not add order lines or address'),
            'imported' => (int)$batch['imported'],
            'total' => (int)$batch['total'],
        ];
    }

    /**
     * Vendor order/fetch may lag right after order/create — retry until DB is ready or attempts exhausted.
     */
    public function importSingleOrderForCheckoutWithRetry(
        string $orderNumber,
        int $maxAttempts = 4,
        int $delaySeconds = 2
    ): array {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['success' => false, 'message' => 'Order number missing', 'imported' => 0, 'total' => 0, 'attempts' => 0];
        }

        $maxAttempts = max(1, min(6, $maxAttempts));
        $delaySeconds = max(0, min(5, $delaySeconds));

        $last = [
            'success' => false,
            'message' => 'Import not attempted',
            'imported' => 0,
            'total' => 0,
            'attempts' => 0,
        ];

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            if ($this->isOrderReadyForPosCheckout($orderNumber)) {
                return [
                    'success' => true,
                    'message' => 'Order ready in database',
                    'imported' => 0,
                    'total' => 0,
                    'attempts' => $attempt,
                ];
            }

            $last = $this->importSingleOrderForCheckout($orderNumber);
            $last['attempts'] = $attempt;

            if ($this->isOrderReadyForPosCheckout($orderNumber)) {
                $last['success'] = true;
                if (trim((string)($last['message'] ?? '')) === '' || stripos((string)$last['message'], 'did not add') !== false) {
                    $last['message'] = 'Order imported and ready for invoice';
                }
                return $last;
            }

            if ($attempt < $maxAttempts && $delaySeconds > 0) {
                sleep($delaySeconds);
            }
        }

        $last['success'] = false;
        if (trim((string)($last['message'] ?? '')) === '') {
            $last['message'] = 'Order not available from vendor API yet';
        }

        return $last;
    }

    public function createPurchaseOrder()
    {
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
    public function getOrderDetails()
    {
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

    public function saveSearch()
    {
        // Save a named search (AJAX)
        is_login();
        header('Content-Type: application/json');
        $user_id = $_SESSION['user']['id'] ?? 0;
        $name = trim($_POST['name'] ?? '');
        $query = trim($_POST['query'] ?? ($_SERVER['QUERY_STRING'] ?? ''));
        if (!$user_id || empty($query)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }
        if (empty($name)) {
            $name = 'Saved Search - ' . date('Y-m-d H:i');
        }
        global $savedSearchModel;
        $data = [
            'user_id' => $user_id,
            'page' => 'orders',
            'name' => $name,
            'query' => $query
        ];
        $res = $savedSearchModel->add($data);
        if (!empty($res['insert_id'])) {
            $record = $savedSearchModel->get($res['insert_id'], $user_id);
            echo json_encode(['success' => true, 'search' => $record]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to save search.']);
        }
        exit;
    }

    public function deleteSearch()
    {
        // Delete saved search (AJAX)
        is_login();
        header('Content-Type: application/json');
        $user_id = $_SESSION['user']['id'] ?? 0;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if (!$user_id || !$id) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters.']);
            exit;
        }
        global $savedSearchModel;
        $ok = $savedSearchModel->delete($id, $user_id);
        if ($ok) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Unable to delete.']);
        }
        exit;
    }

    public function updateStatus()
    {
        is_login();
        global $ordersModel;
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_id = isset($_POST['status_order_id']) ? (int)$_POST['status_order_id'] : 0;
            $new_status = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';
            $remarks = isset($_POST['orderRemarks']) ? trim($_POST['orderRemarks']) : NULL;
            $esd = isset($_POST['esd']) ? trim($_POST['esd']) : NULL;
            $priority = isset($_POST['orderPriority']) ? trim($_POST['orderPriority']) : NULL;
            $agent_id = isset($_POST['agent_id']) ? (int)$_POST['agent_id'] : NULL;
            $previous_agent = isset($_POST['previous_agent']) ? (int)$_POST['previous_agent'] : NULL;
            $agent_name = isset($_POST['agent_name']) ? trim($_POST['agent_name']) : NULL;
            $previous_status = isset($_POST['previousStatus']) ? trim($_POST['previousStatus']) : NULL;
            $previous_esd = isset($_POST['previous_esd']) ? trim($_POST['previous_esd']) : NULL;
            $previous_priority = isset($_POST['previous_priority']) ? trim($_POST['previous_priority']) : NULL;
            $previous_remarks = isset($_POST['previous_remarks']) ? trim($_POST['previous_remarks']) : NULL;

            if ($order_id > 0 && !empty($new_status)) {
                $update_data = [
                    'status' => $new_status,
                    'remarks' => $remarks,
                    'priority' => $priority,
                    'agent_id' => $agent_id
                ];
                // only include ESD if a non-empty value was provided to avoid inserting an empty string into a DATE/DATETIME column
                if ($esd !== NULL && $esd !== '') {
                    $update_data['esd'] = $esd;
                }
                $updated = $ordersModel->updateStatus($order_id, $update_data);

                // commented out on 09-11-2025 as per request
                // call exotic india API to update order status
                $orderval = $ordersModel->getOrderById($order_id);
                $apidata = [
                    'orderid' => $orderval['order_number'],
                    'level' => 'item',
                    'order_status' => $commanModel->getExoticIndiaOrderStatusCode($new_status)['admin_id'],
                    'size' => trim($orderval['size']),
                    'color' => trim($orderval['color']),
                    'itemcode' => trim($orderval['item_code'])
                ];
                //run update if admin id not 0
                if ($apidata['order_status'] > 0) {
                    $resp = $commanModel->updateExoticIndiaOrderStatus($apidata);
                }
                //log status change
                $logData = [
                    'order_id' => $order_id,
                    'status' => 'Status: ' . $new_status,
                    'changed_by' => $_SESSION['user']['id'],
                    'api_response' => NULL, //json_encode($resp),
                    'change_date' => date('Y-m-d H:i:s')
                ];
                //print_array($apidata);
                //print_array($_POST);
                if ($new_status != $_POST['previousStatus']) {
                    $commanModel->add_order_status_log($logData);
                }
                if ($agent_id != $previous_agent) {
                    //log agent change
                    $agentLogData = [
                        'order_id' => $order_id,
                        'status' => 'Agent: ' . $agent_name,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($agentLogData);
                    //update agent_assign_date CURDATE
                    $assign = $commanModel->updateRecord('vp_orders', ['agent_assign_date' => date('Y-m-d H:i:s')], $order_id);
                    //$ordersModel->updateAgentAssignDate($order_id);
                    //set notification to agent

                    $link = base_url('index.php?page=orders&action=get_order_details_html&type=outer&order_number=' . $orderval['order_number']);
                    insertNotification($agent_id, 'Order Assigned', 'Order <a href="' . $link . '" class="text-blue-600 hover:underline" target="_blank">' . $orderval['order_number'] . '</a> has been assigned to you for processing.', $link);
                }
                if ($esd != $previous_esd) {
                    //log esd change
                    $esdLogData = [
                        'order_id' => $order_id,
                        'status' => 'ESD : ' . $esd,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($esdLogData);
                }
                if ($priority != $previous_priority) {
                    //log priority change
                    $priorityLogData = [
                        'order_id' => $order_id,
                        'status' => 'Priority : ' . $priority,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($priorityLogData);
                }
                if ($remarks != $previous_remarks) {
                    //log remarks change
                    $remarksLogData = [
                        'order_id' => $order_id,
                        'status' => 'Notes updated.',
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($remarksLogData);
                }

                if ($updated) {
                    echo json_encode(['success' => true, 'message' => 'Order status updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order status.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order ID or status.']);
            }
        }

        exit;
    }
    public function getOrderDetailsHTML()
    {
        is_login();
        global $ordersModel, $commanModel;
        $order_number = isset($_GET['order_number']) ? (int)$_GET['order_number'] : 0;
        $type = isset($_GET['type']) ? $_GET['type'] : 'inner';
        if ($order_number > 0) {
            $order = $ordersModel->getOrderByOrderNumber($order_number);
            $orderremarks = $ordersModel->getRemarksByOrderNumber($order_number);
            $fullOrderJourny = $ordersModel->getfullOrderJournyByNumber($order_number);
            $customerdetails = $ordersModel->getCustomerNameAndEmailByOrderNumber($order_number);
            $statusList = $commanModel->get_order_status_list();
            $assignmentDates = [];
            foreach ($order as $key => $orders) {
                $order[$key]['status_log'] = $commanModel->get_order_status_log($orders['id']);
                $assignmentDates[$orders['id']] =  $orders[$key]['status_log']['change_date'] ?? '';
            }
            if ($order) {
                if ($type === 'inner')
                    renderPartial('views/orders/partial_order_details.php', ['order' => $order, 'statusList' => $statusList, 'orderremarks' => $orderremarks]);
                else
                    renderTemplate('views/orders/other_partial_order_details.php', ['order' => $order, 'statusList' => $statusList, 'orderremarks' => $orderremarks, 'fullOrderJourny' => $fullOrderJourny, 'customerdetails' => $customerdetails], 'Order Details');
            } else {
                echo '<p>Order details not found.</p>';
            }
        } else {
            echo '<p>Invalid Order Number.</p>';
        }
        exit;
    }
    public function updateImportedOrders()
    {
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        // Set your date range (example: last 7 days)
        //print_array($_GET);
        $from_date = !empty($_GET['from_date']) ? strtotime($_GET['from_date'] . ' 00:00:00') : strtotime('-1 days');
        //echo "<br>";
        // if ($lastLog && !empty($lastLog['max_ordered_time'])) {         
        //     $from_date = $lastLog['max_ordered_time'];
        // }
        $to_date = !empty($_GET['to_date']) ? strtotime($_GET['to_date'] . ' 23:59:59') : time();

        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint

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
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'Invalid API response format.']], 'API Error');
            return;
        }
        // echo "Total Orders Fetched: " . count($orders['orders']) . "<br>";
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0;
        $totalorder = 0;
        foreach ($orders['orders'] as $order) {

            //print_r($order['cart']);
            // Check if the order has the required fields
            // Map API fields to your table columns

            foreach ($order['cart'] as $item) {
                $orderdate =  !empty($order['processed_time']) ? date('Y-m-d H:i:s', $order['processed_time']) : date('Y-m-d H:i:s');
                $esd = '0000-00-00';
                $local_stock_int = (int) floatval($item['local_stock']);
                $lead_time_int = (int) floatval($item['leadtime']);
                if ($item['marketplace_vendor'] == 'exoticindia' || empty($item['marketplace_vendor'])) {
                    if (!empty($local_stock_int) && $local_stock_int > 0) {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + 3 days'));
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
                            $esd = date('Y-m-d', strtotime($orderdate . ' + 0 days'));
                        } else {
                            $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $lead_time_int . ' days'));
                        }
                    }
                } else {
                    if (!empty($local_stock_int) && $local_stock_int > 0) {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + ' . $local_stock_int . ' days'));
                    } else {
                        $esd = date('Y-m-d', strtotime($orderdate . ' + ' . ($lead_time_int) . ' days'));
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
                    'local_stock' => is_numeric($item['local_stock'] ?? null) ? (float) $item['local_stock'] : 0.0,
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
                    'esd' => $esd,
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $totalorder++;

                $data = $ordersModel->updateImportedOrder($rdata);
                $result[] = $data;
                //add products
                //$pdata[] = $ordersModel->addProducts($rdata);                   

                if (isset($data['success']) && $data['success'] == true) {
                    $imported++;
                }
                // print_array($rdata);                   
            }
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count

        renderTemplateClean('views/orders/import_update_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            //'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }
    public function skuUpdateImportedOrders()
    {
        //ini_set('max_execution_time', 300);
        //set_time_limit(300);  
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //order status list
        // $statusList = $ordersModel->adminOrderStatusList('true');
        //last order log fetch
        // Set your date range (example: last 7 days)
        //print_array($_GET);
        $from_date = !empty($_GET['from_date']) ? strtotime($_GET['from_date'] . ' 00:00:00') : strtotime('-1 days');
        //echo "<br>";
        // if ($lastLog && !empty($lastLog['max_ordered_time'])) {         
        //     $from_date = $lastLog['max_ordered_time'];
        // }
        $to_date = !empty($_GET['to_date']) ? strtotime($_GET['to_date'] . ' 23:59:59') : time();
        //$from_date = '1758240000';
        //$to_date = '1758330134';
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint

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
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'Invalid API response format.']], 'API Error');
            return;
        }
        // echo "Total Orders Fetched: " . count($orders['orders']) . "<br>";
        // print_array($orders);
        // exit;
        if (empty($orders['orders'])) {
            //echo "No orders found in the API response.";
            renderTemplateClean('views/errors/error.php', ['message' => ['type' => 'success', 'text' => 'No orders found in the API response.']], 'No Orders Found');
            return;
        }
        $imported = 0;
        $totalorder = 0;
        foreach ($orders['orders'] as $order) {

            //print_r($order['cart']);
            // Check if the order has the required fields
            // Map API fields to your table columns

            foreach ($order['cart'] as $item) {
                $rdata = [
                    'sku' => $item['sku'] ?? '',
                    'order_number' => $order['orderid'] ?? '',
                    'item_code' => $item['itemcode'] ?? '',
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $totalorder++;

                $data = $ordersModel->skuUpdateImportedOrder($rdata);
                $result[] = $data;
                //add products
                //$pdata[] = $ordersModel->addProducts($rdata);                   

                if (isset($data['success']) && $data['success'] == true) {
                    $imported++;
                }
                //print_array($rdata);                   
            }
        }
        //print_array($pdata);
        //print_r($result);
        //update log end time and imported count

        renderTemplateClean('views/orders/import_update_result.php', [
            'imported' => $imported,
            'result' => $result,
            'total' => $totalorder,
            //'products' => json_encode($pdata)
        ], 'Import Orders Result');
    }

    public function showBulkUpdateUI()
    {
        is_login();
        renderTemplateClean('views/orders/update_import_bulk.php', [], 'Bulk Order Status Update');
    }

    public function ordersStatusImportBulk()
    {
        ini_set('max_execution_time', 30000);
        set_time_limit(30000);
        global $ordersModel;
        
        // Security check
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403);
            die('Unauthorized access.');
        }
        
        header('Content-Type: application/json');
        
        // Initialize session for bulk import tracking
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['bulk_import'])) {
            $_SESSION['bulk_import'] = [
                'count' => 0,
                'offset' => 0
            ];
        }
        
        // Configuration
        $MAX_RECORDS = 5000;
        $BATCH_SIZE = 50; // API batch size
        $DB_LIMIT = 500;  // Database fetch limit per execution
        $SLEEP_SECONDS = 5; // Recommended sleep between calls
        
        // Get current count and offset from session
        $totalImported = (int)$_SESSION['bulk_import']['count'];
        $currentOffset = (int)$_SESSION['bulk_import']['offset'];
        
        // Check if we've reached the limit
        if ($totalImported >= $MAX_RECORDS) {
            echo json_encode([
                'success' => true,
                'message' => "✓ Maximum limit of {$MAX_RECORDS} records reached for today. Process completed successfully.",
                'batch_imported' => 0,
                'batch_total_items' => 0,
                'total_imported' => $totalImported,
                'max_limit' => $MAX_RECORDS,
                'progress_percent' => 100,
                'completed' => true,
                'should_continue' => false,
                'next_action' => null
            ]);
            unset($_SESSION['bulk_import']);
            exit;
        }
        
        // Fetch up to 500 orders to update from database using offset
        $odr = $ordersModel->fetchOrdersForUpdateScript($currentOffset);
        // print_array($odr);
        // exit;
        if (empty($odr)) {
            echo json_encode([
                'success' => true,
                'message' => '✓ No more orders found in database. All available orders have been processed.',
                'batch_imported' => 0,
                'batch_total_items' => 0,
                'total_imported' => $totalImported,
                'max_limit' => $MAX_RECORDS,
                'progress_percent' => round(($totalImported / $MAX_RECORDS) * 100, 2),
                'completed' => true,
                'should_continue' => false,
                'next_action' => null
            ]);
            unset($_SESSION['bulk_import']);
            exit;
        }
        
        // Order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        
        // API configuration
        $url = 'https://www.exoticindia.com/vendor-api/order/fetch';
        
        // Chunk orders into batches of 50 for API calls
        $orderChunks = array_chunk(array_filter($odr, function ($order) {
            return !empty($order);
        }), $BATCH_SIZE);
        
        $imported = 0;
        $totalorder = 0;
        $skipped = 0;
        $result = [];
        $errors = [];
        $batchesProcessed = 0;
        $affected_rows = 0;
        
        // Process all batches from this 500-record fetch
        foreach ($orderChunks as $chunkIndex => $chunk) {
            // Check if we'll exceed limit before processing
            if ($totalImported + $imported >= $MAX_RECORDS) {
                break;
            }
            
            $orderIds = implode(',', $chunk);
            $postData = [
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $orderIds
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
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            $response = curl_exec($ch);

            $error = curl_error($ch);
            curl_close($ch);
            
            if (!empty($error)) {
                $errors[] = "API Batch " . ($chunkIndex + 1) . " failed: " . $error;
                continue;
            }
            
            // Process API response
            $respData = json_decode($response, true);
            if (!is_array($respData) || empty($respData['orders'])) {
                continue;
            }
            
            $batchesProcessed++;
            
            foreach ($respData['orders'] as $order) {
                // Check if we'll exceed limit before processing items
                if ($totalImported + $imported >= $MAX_RECORDS) {
                    break 2;
                }
                
                if (!isset($order['cart']) || !is_array($order['cart'])) {
                    continue;
                }
                
                foreach ($order['cart'] as $item) {
                    // Check status other than 1 (pending)
                    if (empty($item['order_status']) || $item['order_status'] == 1) {
                        $rdata = [
                            'sku' => $item['sku'] ?? '',
                            'order_number' => $order['orderid'] ?? '',
                            'item_code' => $item['itemcode'] ?? '',
                            'remote_status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA' || strtoupper($order['payment_type'] ?? '') === 'INDIAAMAZONFBA')
                                ? 'shipped'
                                : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),
                            'updated_at' => date('Y-m-d H:i:s'),
                            'size' => $item['size'] ?? '',
                            'color' => $item['color'] ?? ''
                        ];
                        $totalorder++;

                        $data = $ordersModel->importedStatusUpdateScript($rdata);
                        $result[] = $data;
                        $result['rdata'][] = $rdata;
                        $affected_rows += $data['affected_rows'] ?? 0;

                        if (isset($data['success']) && $data['success'] == true) {
                            $imported++;
                        } else {
                            $skipped++;
                        }
                        
                        // Check if we've reached limit during processing
                        if ($totalImported + $imported >= $MAX_RECORDS) {
                            break 3;
                        }
                    }
                }
            }
        }
        
        // Update total count and offset in session
        $totalImported += $imported;
        $_SESSION['bulk_import']['count'] = $totalImported;
        
        // Update offset for next batch (increase by 500)
        $nextOffset = $currentOffset + $DB_LIMIT;
        $_SESSION['bulk_import']['offset'] = $nextOffset;
        
        // Determine if process should continue
        $completed = ($totalImported >= $MAX_RECORDS);
        $shouldContinue = !$completed && count($odr) === $DB_LIMIT; // More records may exist
        
        if ($completed) {
            unset($_SESSION['bulk_import']);
        }
        
        // Build message
        $messageStatus = $imported > 0 ? '✓' : 'ℹ';
        $message = "{$messageStatus} Batch processing complete: {$imported} updated";
        if ($skipped > 0) {
            $message .= ", {$skipped} skipped";
        }
        if (count($errors) > 0) {
            $message .= ", " . count($errors) . " errors";
        }
        
        // Prepare response
        $response = [
            'success' => true,
            'message' => $message,
            'batch_imported' => $imported,
            'batch_total_items' => $totalorder,
            'batch_skipped' => $skipped,
            'batches_processed' => $batchesProcessed,
            'total_imported' => $totalImported,
            'max_limit' => $MAX_RECORDS,
            'completed' => $completed,
            'should_continue' => $shouldContinue,
            'progress_percent' => round(($totalImported / $MAX_RECORDS) * 100, 2),
            'timestamp' => date('Y-m-d H:i:s'),
            'result' => $result,
            'affected_rows' => $affected_rows,
            'current_offset' => $currentOffset,
            'next_offset' => $nextOffset
        ];
        
        // Add next action if should continue
        if ($shouldContinue) {
            $response['next_action'] = [
                'action' => 'Call ordersStatusImportBulk again',
                'wait_seconds' => $SLEEP_SECONDS,
                'url' => 'index.php?page=orders&action=ordersStatusImportBulk&secret_key=' . EXPECTED_SECRET_KEY
            ];
        }
        
        // Add errors if any
        if (!empty($errors)) {
            $response['errors'] = $errors;
            $response['error_count'] = count($errors);
        }
        
        echo json_encode($response);
        exit;
    }
    public function bulkUpdateStatus()
    {
        is_login();
        global $ordersModel;
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_ids = isset($_POST['order_ids']) ? $_POST['order_ids'] : [];
            $new_status = isset($_POST['orderStatus']) ? $_POST['orderStatus'] : '';
            //print_array($order_ids);
            //print_array($_POST);
            //exit;
            if (!empty($order_ids) && !empty($new_status)) {
                $result = $ordersModel->updateStatusBulk($order_ids, $new_status);
                //log status change for each order
                foreach ($order_ids as $oid) {
                    $logData = [
                        'order_id' => $oid,
                        'status' => 'Status: ' . $new_status,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($logData);
                    //call exotic india API to update order status
                    $orderval = $ordersModel->getOrderById($oid);
                    $apidata = [
                        'orderid' => $orderval['order_number'],
                        'level' => 'item',
                        'order_status' => $commanModel->getExoticIndiaOrderStatusCode($new_status)['admin_id'],
                        'size' => trim($orderval['size']),
                        'color' => trim($orderval['color']),
                        'itemcode' => trim($orderval['item_code'])
                    ];
                    //run update if admin id not 0
                    if ($apidata['order_status'] > 0) {
                        $resp = $commanModel->updateExoticIndiaOrderStatus($apidata);
                    }
                    //notify agent if assigned
                    $orderval = $ordersModel->getOrderById($oid);
                    if (!empty($orderval['agent_id']) && $orderval['agent_id'] > 0) {
                        $link = base_url('index.php?page=orders&action=list&' . $oid);
                        insertNotification($orderval['agent_id'], 'Order Status Updated', 'The status of an order assigned to you has been updated. Please check the order details.', $link);
                    }
                }
                if ($result) {
                    //session poitem array clean

                    echo json_encode($result);
                    //echo json_encode(['success' => true, 'message' => 'Order statuses updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order statuses.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order IDs or status.']);
            }
        }

        exit;
    }
    public function bulkAssignOrder()
    {
        is_login();
        global $ordersModel;
        global $commanModel;
        header('Content-Type: application/json');
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $order_ids = isset($_POST['poitem']) ? $_POST['poitem'] : [];
            $agent_id = isset($_POST['agent_id']) ? $_POST['agent_id'] : '';
            //print_array($order_ids);
            //print_array($_POST);
            //exit;
            if (!empty($order_ids) && !empty($agent_id)) {
                $result = $ordersModel->updateAgentBulk($order_ids, $agent_id);
                //log agent assignment for each order
                $agent_name = $commanModel->getUserNameById($agent_id);
                foreach ($order_ids as $oid) {
                    $logData = [
                        'order_id' => $oid,
                        'status' => 'Agent: ' . $agent_name,
                        'changed_by' => $_SESSION['user']['id'],
                        'api_response' => NULL,
                        'change_date' => date('Y-m-d H:i:s')
                    ];
                    $commanModel->add_order_status_log($logData);
                    //set notification to agent
                    $link = base_url('index.php?page=orders&action=list&' . $oid);
                    insertNotification($agent_id, 'Order Assigned', 'You have been assigned a new order. Please check the order details.', $link);
                }
                if ($result) {
                    //session poitem array clean

                    echo json_encode($result);
                    //echo json_encode(['success' => true, 'message' => 'Order statuses updated successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order statuses.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid order IDs or status.']);
            }
        }
    }

    public function getOrdersCustomerId()
    {
        is_login();
        global $ordersModel;
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);
            $order_ids = $input['order_ids'] ?? [];

            if (empty($order_ids) || !is_array($order_ids)) {
                echo json_encode(['success' => false, 'message' => 'Invalid order IDs.']);
                exit;
            }

            $orders = [];
            foreach ($order_ids as $order_id) {
                $order_id = (int)$order_id;
                $order = $ordersModel->getOrderById($order_id);
                if ($order) {
                    $invId = (int)($order['invoice_id'] ?? 0);
                    $invStatus = $invId > 0 ? $ordersModel->getInvoiceStatusByInvoiceId($invId) : null;
                    $orders[] = [
                        'order_id' => $order_id,
                        'customer_id' => $order['customer_id'] ?? null,
                        'invoice_id' => $order['invoice_id'] ?? null,
                        'invoice_status' => $invStatus,
                    ];
                }
            }

            echo json_encode(['success' => true, 'orders' => $orders]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
        }
        exit;
    }

    public function invoiceList()
    {
        is_login();
        global $poInvoiceModel;
        // $limit = 50;
        // $offset = 0;
        // if (isset($_GET['limit'])) {
        //     $limit = intval($_GET['limit']);
        // }
        // if (isset($_GET['offset'])) {
        //     $offset = intval($_GET['offset']);
        // }
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;
        //search filters
        $filters = [];
        if (isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = intval($_GET['vendor_id']);
        }
        if (isset($_GET['invoice_date_from']) && !empty($_GET['invoice_date_from'])) {
            $filters['invoice_date_from'] = $_GET['invoice_date_from'];
        }
        if (isset($_GET['invoice_date_to']) && !empty($_GET['invoice_date_to'])) {
            $filters['invoice_date_to'] = $_GET['invoice_date_to'];
        }
        //amount range filter
        if (isset($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
            $filters['amount_min'] = floatval($_GET['amount_min']);
        }
        if (isset($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
            $filters['amount_max'] = floatval($_GET['amount_max']);
        }
        //po number filter
        if (isset($_GET['po_number']) && !empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        //utr filter
        if (isset($_GET['utr_number']) && !empty($_GET['utr_number'])) {
            $filters['utr_number'] = $_GET['utr_number'];
        }

        $invoices = $poInvoiceModel->getAllInvoices($limit, $offset, $filters);
        $total_orders = $poInvoiceModel->getTotalInvoices(0, 0, $filters);
        //foreach invoice get po items
        foreach ($invoices as $id => $invoice) {
            $items = $poInvoiceModel->getPOsByInvoiceId($invoice['id']);
            $invoices[$id]['items'] = $items;
        }
        //print_array($invoices);

        renderTemplate('views/purchase_orders/invoice_list.php', ['invoices' => $invoices, 'total_orders' => $total_orders], 'Purchase Order Invoices');
    }
    public function paymentList()
    {
        is_login();
        global $poInvoiceModel;
        // $limit = 50;
        // $offset = 0;
        // if (isset($_GET['limit'])) {
        //     $limit = intval($_GET['limit']);
        // }
        // if (isset($_GET['offset'])) {
        //     $offset = intval($_GET['offset']);
        // }
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;
        //filters 
        $filters = [];
        if (isset($_GET['vendor_id']) && !empty($_GET['vendor_id'])) {
            $filters['vendor_id'] = intval($_GET['vendor_id']);
        }
        if (isset($_GET['payment_date_from']) && !empty($_GET['payment_date_from'])) {
            $filters['payment_date_from'] = $_GET['payment_date_from'];
        }
        if (isset($_GET['payment_date_to']) && !empty($_GET['payment_date_to'])) {
            $filters['payment_date_to'] = $_GET['payment_date_to'];
        }
        //amount range filter
        if (isset($_GET['amount_min']) && is_numeric($_GET['amount_min'])) {
            $filters['amount_min'] = floatval($_GET['amount_min']);
        }
        if (isset($_GET['amount_max']) && is_numeric($_GET['amount_max'])) {
            $filters['amount_max'] = floatval($_GET['amount_max']);
        }
        //po number filter
        if (isset($_GET['po_number']) && !empty($_GET['po_number'])) {
            $filters['po_number'] = $_GET['po_number'];
        }
        //utr filter
        if (isset($_GET['utr_number']) && !empty($_GET['utr_number'])) {
            $filters['utr_number'] = $_GET['utr_number'];
        }

        $payments = $poInvoiceModel->getAllPayments($limit, $offset, $filters);
        $total_payments = $poInvoiceModel->getTotalPayments(0, 0, $filters);
        //print_array($payments);
        renderTemplate('views/purchase_orders/payment_list.php', ['payments' => $payments, 'total_payments' => $total_payments], 'Payments List');
    }

    public function updateNoteAjax()
    {
        is_login();
        global $ordersModel;
        // ob_end_clean();
        // header('Content-Type: application/json; charset=utf-8');


        $order_number = trim($_POST['order_number'] ?? '');
        $remarks      = trim($_POST['remarks'] ?? '');
        // Use the model (assuming $this->model is set in constructor)
        $result = $ordersModel->updateOrderRemarks($order_number, $remarks);
        echo json_encode($result);
        exit;
    }
    public function updateNameEmailAjax()
    {
        is_login();
        global $ordersModel;
        $order_number   = trim($_POST['order_number']   ?? '');
        $customer_name  = trim($_POST['customer_name']  ?? '');
        $customer_phone = trim($_POST['customer_phone'] ?? '');
        $address_line1 = trim($_POST['address_line1'] ?? '');
        $address_line2 = trim($_POST['address_line2'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $zipcode = trim($_POST['zipcode'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $billing_address_line1 = trim($_POST['billing_address_line1'] ?? '');
        $billing_address_line2 = trim($_POST['billing_address_line2'] ?? '');
        $billing_city = trim($_POST['billing_city'] ?? '');
        $billing_zipcode = trim($_POST['billing_zipcode'] ?? '');
        $billing_country = trim($_POST['billing_country'] ?? '');
        if (empty($order_number) || empty($customer_name) || empty($customer_phone)) {
            echo json_encode([
                'success' => false,
                'message' => 'Order number, name, email and phone are required'
            ]);
            exit;
        }
        $result = $ordersModel->updateCustomerNameAndEmail($order_number, $customer_name, $customer_phone, $address_line1, $address_line2, $city, $zipcode, $country, $billing_address_line1, $billing_address_line2, $billing_city, $billing_zipcode, $billing_country);
        echo json_encode($result);
        exit;
    }

    public function getOrderDetailsForDispatch()
    {
        is_login();
        global $ordersModel;
        header('Content-Type: application/json');

        $order_number = isset($_GET['order_number']) ? (int)$_GET['order_number'] : 0;

        if ($order_number <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid order number'
            ]);
            exit;
        }

        try {
            $orders = $ordersModel->getOrderByOrderNumber($order_number);
            //address details from vp_order_info table
            $order_info = $ordersModel->getRemarksByOrderNumber($order_number);

            if (empty($orders)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                exit;
            }

            // Build HTML for order card
            $firstOrder = $order_info;
            $customer_name = $firstOrder['customer_name'] ?? 'Unknown';
            $customer_id = $firstOrder['customer_id'] ?? '-';
            $shipping_address = '';

            // Build shipping address
            if (!empty($firstOrder['address_line1'])) {
                $shipping_address = htmlspecialchars($firstOrder['address_line1']);
                if (!empty($firstOrder['address_line2'])) {
                    $shipping_address .= ', ' . htmlspecialchars($firstOrder['address_line2']);
                }
                if (!empty($firstOrder['city'])) {
                    $shipping_address .= ', ' . htmlspecialchars($firstOrder['city']);
                }
                if (!empty($firstOrder['state'])) {
                    $shipping_address .= ', ' . htmlspecialchars($firstOrder['state']);
                }
                if (!empty($firstOrder['zipcode'])) {
                    $shipping_address .= ', ' . htmlspecialchars($firstOrder['zipcode']);
                }
            }

            // Build order items HTML
            $items_html = '';
            $total_weight = 0;
            $total_amount = 0;
            $items_count = count($orders);

            foreach ($orders as $idx => $order) {
                $quantity = $order['quantity'] ?? 0;
                $finalprice = $order['finalprice'] ?? 0;
                $item_total = $quantity * $finalprice;
                $gst = $order['gst'] ?? 0;
                $payment_type = strtolower($order['payment_type'] ?? '') === 'cod' ? 'COD' : 'Prepaid';
                $product_weight = (float)($order['product_weight'] ?? 0);

                $total_weight += $product_weight * $quantity;
                $total_amount += $item_total;

                $items_html .= '
                <div class="px-4 py-1 text-xs text-gray-700 border-b border-gray-100" data-groupname="' . htmlspecialchars($order['groupname'] ?? '') . '" data-item-id="' . htmlspecialchars($order['id'] ?? '') . '">
                    <div class="grid grid-cols-12 gap-2 items-center">
                        <div class="col-span-2">' . htmlspecialchars($order['order_number'] ?? '') . '</div>
                        <div class="col-span-3">
                            <span class="font-semibold">' . htmlspecialchars($order['title'] ?? 'Product') . '</span> | ' . htmlspecialchars($order['item_code'] ?? '') . '
                        </div>
                        <div class="col-span-1 text-right">' . $quantity . '</div>
                        <div class="col-span-1 text-right">' . number_format($product_weight, 3) . ' kg</div>
                        <div class="col-span-1 text-right">-</div>
                        <div class="col-span-1 text-right">' . $gst . '%</div>
                        <div class="col-span-1 text-right">₹ ' . number_format($item_total, 0) . '</div>
                        <div class="col-span-1 text-right">' . $payment_type . '</div>
                        <div class="col-span-1 flex justify-center gap-2 text-lg">
                            <button class="move-item-btn text-gray-500 hover:text-gray-700" title="Move">📦</button>
                            <button class="remove-item-btn text-red-500 hover:text-red-700" title="Remove">🗑</button>
                        </div>
                    </div>
                </div>';
            }

            // after building items HTML wrap it
            $items_html_wrapped = '<div class="items-container">' . $items_html . '</div>';
            $html = '
        <div class="bg-orange-500 text-white px-4 py-2 flex flex-wrap justify-between items-center rounded-t">
            <div class="font-semibold">
                Customer - ' . htmlspecialchars($customer_id) . '
            </div>
            <div class="text-xs sm:text-sm">
                <span class="font-semibold">Shipping to:</span>
                ' . $shipping_address . '
            </div>
        </div>

        <div class="border border-orange-400 border-t-0 rounded-b bg-white" data-order-number="' . htmlspecialchars($order_number) . '" data-customer-id="' . htmlspecialchars($customer_id) . '" data-customer-name="' . htmlspecialchars($customer_name) . '">
            <div class="px-4 py-2 flex flex-wrap items-center justify-between bg-orange-50 border-b border-orange-200">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-400 text-white text-sm">
                        📦
                    </span>
                    <span class="font-semibold text-gray-800">Box 1</span>
                </div>
                <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">Total Weight (kg):</span>
                        <input type="text" value="' . number_format($total_weight, 3) . '"
                               class="ml-1 border border-gray-300 rounded px-2 py-0.5 w-20 text-xs"/>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-700">Box Size:</span>
                        <select class="border border-gray-300 rounded px-2 py-0.5 text-xs w-28">
                            <option>R1 - 7x4x1</option>
                        </select>
                    </div>
                    <button type="button" data-open-select-items class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1 rounded">
                        + Item
                    </button>
                </div>
            </div>

            <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-2 font-semibold">
                    <div class="col-span-2">Order</div>
                    <div class="col-span-3">Item</div>
                    <div class="col-span-1 text-right">Quantity</div>
                    <div class="col-span-1 text-right">Weight</div>
                    <div class="col-span-1 text-right">Box Size</div>
                    <div class="col-span-1 text-right">GST</div>
                    <div class="col-span-1 text-right">Item Total</div>
                    <div class="col-span-1 text-right">Payment Type</div>
                    <div class="col-span-1 text-center">Actions</div>
                </div>
            </div>

            ' . $items_html_wrapped . '

            <div class="px-4 py-3 flex flex-wrap justify-between items-center text-xs bg-orange-50">
                <div class="flex flex-wrap gap-4 text-gray-700">
                    <span><span class="font-semibold">Order:</span> ' . $items_count . '</span>
                    <span><span class="font-semibold">SKU Count:</span> ' . $items_count . '</span>
                    <span><span class="font-semibold">Total Quantity:</span> ' . array_sum(array_column($orders, 'quantity')) . '</span>
                    <span><span class="font-semibold">Total Weight:</span> ' . number_format($total_weight, 3) . ' kg</span>
                </div>
                <div class="flex flex-wrap gap-4 text-gray-800">
                    <span><span class="font-semibold">Net Total:</span> ₹ ' . number_format($total_amount, 0) . '</span>
                </div>
            </div>
        </div>

        <div class="mt-2 mb-4 flex flex-wrap items-center justify-between">
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm inline-flex items-center gap-2 add-box-btn">
                <span>+ Add Box</span>
            </button>
            <button type="button" class="remove-order-btn text-red-500 hover:text-red-700 text-sm font-semibold px-4 py-2 rounded">
                🗑 Remove Order
            </button>
        </div>';

            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function getOrderItemsForDispatch()
    {
        is_login();
        global $ordersModel;
        header('Content-Type: application/json');

        $order_number = isset($_GET['order_number']) ? (int)$_GET['order_number'] : 0;

        if ($order_number <= 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid order number'
            ]);
            exit;
        }
        //check if invoice already exists for the order number, if yes return error

        // if ($ordersModel->invoiceExists($order_number)) {
        //     echo json_encode([
        //         'success' => false,
        //         'message' => 'Invoice already exists for this order number'
        //     ]);
        //     exit;
        // }

        try {
            require_once __DIR__ . '/../helpers/dispatch/shipping_address_validation.php';

            $orders = $ordersModel->getOrderByOrderNumber($order_number);
            $order_info = $ordersModel->getRemarksByOrderNumber($order_number);
            if (empty($orders)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
                exit;
            }

            $addressCheck = validateShippingAddressForDispatch(is_array($order_info) ? $order_info : []);
            if (empty($addressCheck['valid'])) {
                echo json_encode([
                    'success' => false,
                    'message' => (string) ($addressCheck['message'] ?? 'Shipping address with pincode is required for dispatch.'),
                ]);
                exit;
            }

            // Build shipping address
            $firstOrder = $order_info;
            $ship_country_raw = strtoupper(trim((string)($firstOrder['shipping_country'] ?? '')));
            $domestic_codes = ['', 'IN', 'IND'];
            $is_international = $ship_country_raw !== '' && !in_array($ship_country_raw, $domestic_codes, true);

            $shipping_address = (string) ($addressCheck['address'] ?? '');

            $items_html = '';
            foreach ($orders as $order) {
                if (in_array(strtolower($order['order_status'] ?? ''), ['cancelled', 'returned']) || $order['invoice_id'] > 0 || $order['invoice_id'] !== null) {
                    continue;
                }
                $quantity = $order['quantity'] ?? 0;
                $product_weight = (float)($order['product_weight'] ?? 0);
                $gst = $order['gst'] ?? 0;
                $finalprice = $order['finalprice'] ?? 0;
                $item_total = $quantity * $finalprice;
                $payment_type = strtolower($order['payment_type'] ?? '') === 'cod' ? 'COD' : 'Prepaid';
                $is_express = strpos(strtolower($order['options'] ?? ''), 'express') !== false;
                $items_html .= '
                <tr class="border-b border-gray-100" data-groupname="' . htmlspecialchars($order['groupname'] ?? '') . '" data-item-id="' . htmlspecialchars($order['id'] ?? '') . '" data-is-express="' . ($is_express ? '1' : '0') . '">
                    <td class="p-2">
                        <input type="checkbox" name="order_ids[]" value="' . htmlspecialchars($order['id'] ?? '') . '"/>
                    </td>
                    <td class="p-2">' . htmlspecialchars($order['order_number'] ?? '') . '</td>
                    <td class="p-2">' . htmlspecialchars($order['title'] ?? 'Product') . '</td>
                    <td class="p-2 text-right">' . htmlspecialchars($order['item_code'] ?? '') . '</td>
                    <td class="p-2 text-right">' . $quantity . '</td>
                    <td class="p-2 text-right">' . number_format($product_weight, 3) . ' kg</td>
                    <td class="p-2 text-right">' . $gst . '%</td>
                    <td class="p-2 text-right">₹ ' . number_format($item_total, 0) . '</td>
                    <td class="p-2 text-right">' . $payment_type . '</td>
                </tr>';
            }

            if (trim($items_html) === '') {
                echo json_encode([
                    'success' => false,
                    'message' => 'No dispatchable items found for order #' . $order_number . '.',
                ]);
                exit;
            }

            echo json_encode([
                'success' => true,
                'order_number' => $order_number,
                'customer_name' => htmlspecialchars($order_info['first_name'] ?? '') . ' ' . htmlspecialchars($order_info['last_name'] ?? ''),
                'customer_id' => htmlspecialchars($order_info['customer_id'] ?? ''),
                'shipping_address' => $shipping_address,
                'shipping_country' => $ship_country_raw,
                'is_international' => $is_international,
                'items_html' => $items_html
            ]);
            exit;
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    public function ordersStatusImportBulk2()
    {

        ini_set('max_execution_time', 3000);
        set_time_limit(3000);
        global $ordersModel;
        if (!isset($_GET['secret_key']) || $_GET['secret_key'] !== EXPECTED_SECRET_KEY) {
            http_response_code(403); // Forbidden
            die('Unauthorized access.');
        }
        //fetch order 
        //$odr = $ordersModel->fetchOrdersForUpdate();
        //order status list
        $statusList = $ordersModel->adminOrderStatusList('true');
        //$from_date = '1758240000';
        //$to_date = '1758330134';
        //print_array($odr);
        $orderIds = trim($_POST['order_numbers'] ?? '');
        
        // Validate comma-separated order numbers
        if (empty($orderIds)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'No order numbers provided.'
            ]);
            exit;
        }
        
        // Split and validate order numbers
        $orderNumberArray = array_map('trim', explode(',', $orderIds));
        $orderNumberArray = array_filter($orderNumberArray); // Remove empty values
        
        if (empty($orderNumberArray)) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid order numbers format. Please provide comma-separated order numbers.'
            ]);
            exit;
        }
        
        // Validate each order number is not empty
        foreach ($orderNumberArray as $orderNum) {
            if (empty($orderNum)) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Order numbers cannot contain empty values. Please check your input.'
                ]);
                exit;
            }
        }
        //50 order number limit check
        if (count($orderNumberArray) > 50) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'You can only fetch status for up to 50 orders at a time. Please reduce the number of order numbers provided.'
            ]);
            exit;
        }
        
        // Rejoin with comma for API
        $orderIds = implode(',', $orderNumberArray);
        
        // Set JSON response header
        header('Content-Type: application/json');
        //exit;

        $url = 'https://www.exoticindia.com/vendor-api/order/fetch'; // Production API new endpoint       

        
        $response = [];
        
            
            $postData = [
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $orderIds
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
            
            if (!empty($error)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'API Error: ' . $error
                ]);
                exit;
            }
        
        
        // print_r($headers); 
        // print_r($postData);       
        //print_r($response);
        // exit;
        if (empty($response)) {
            echo json_encode([
                'success' => false,
                'message' => 'No orders found in the API response.'
            ]);
            exit;
        }
        $response = json_decode($response, true);
        $imported = 0;
        $totalorder = 0;
        $result = [];
        print_r($response);
        foreach ($response['orders'] as $order) {
            
            // Check if the order has the required fields
            // Map API fields to your table columns

            foreach ($order['cart'] as $item) {
                //check status other than 1 (pending)
                //if (empty($item['order_status']) || $item['order_status'] == 1) {
                    //continue;

                    $rdata = [
                        'sku' => $item['sku'] ?? '',
                        'order_number' => $order['orderid'] ?? '',                            
                        'item_code' => $item['itemcode'] ?? '',
                        'remote_status' => (strtoupper($order['payment_type'] ?? '') === 'AMAZONFBA' || strtoupper($order['payment_type'] ?? '') === 'INDIAAMAZONFBA')
                            ? 'shipped'
                            : (!empty($statusList[$item['order_status']]) ? $statusList[$item['order_status']] : 'pending'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $totalorder++;
                    print_array($rdata);
                    $data = $ordersModel->importedStatusUpdate2($rdata);
                    $result[] = $data;
                    //add products
                    //$pdata[] = $ordersModel->addProducts($rdata);                   

                    if (isset($data['success']) && $data['success'] == true) {
                        $imported++;
                    }
                //}
                print_array($rdata);                   
            }
        }
        
        //print_array($pdata);
        print_r($result);
        //update log end time and imported count

        echo json_encode([
            'success' => true,
            'message' => "Orders updated successfully. Imported: $imported out of $totalorder",
            'imported' => $imported,
            'total' => $totalorder,
            'result' => $result
        ]);
        exit;
    }
    
}
