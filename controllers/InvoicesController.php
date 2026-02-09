<?php
require_once 'models/invoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';

$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);

class InvoicesController {
    public function index() {
        is_login();
        global $invoiceModel;
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50;
        $offset = ($page_no - 1) * $limit;
        
        $invoices = $invoiceModel->getAllInvoices($limit, $offset);
        $total_records = $invoiceModel->countAllInvoices();
        
        $data = [
            'invoices' => $invoices,
            'page_no' => $page_no,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit
        ];
        
        renderTemplate('views/invoices/index.php', $data, 'Invoices');
    }
    
    public function create() {
        is_login();
        global $ordersModel, $usersModel, $commanModel;
        
        $itemIds = isset($_POST['poitem']) ? $_POST['poitem'] : [];
        //print_r($itemIds);
        if (empty($itemIds)) {
            if(isset($_SESSION['invoice_items']) && !empty($_SESSION['invoice_items'])){
                $itemIds = $_SESSION['invoice_items'];
            } else {
                renderTemplate('views/errors/not_found.php', ['message' => 'No items selected for Invoice.'], 'No items selected');
                exit;
            }
        }
        
        if(!empty($itemIds)){
            $_SESSION['invoice_items'] = $itemIds;
        }
        
        // Fetch order data for selected items
        $data = [];
        foreach ($itemIds as $id) {
            $order = $ordersModel->getOrderById($id);
            //print_r($order);
            if ($order) {
                $data['data'][] = $order;
                
            }
        }
        //customer info
        $orderNumber = [];
        $data['customer'] = $commanModel->getRecordById('vp_customers', isset($data['data'][0]['customer_id']) ? $data['data'][0]['customer_id'] : 0);
        foreach($data['data'] as $key => $order){
            //same order_number validation
            if(!in_array($order['order_number'], $orderNumber)){
                $orderNumber[] = $order['order_number'];
                $data['customer_address'][$key]= $commanModel->get_customer_address($order['order_number']);  
            }
            //unit_price calculation with finalprice
            $gstRate = isset($order['gst']) ? $order['gst'] : 0;
            $finalPrice = $order['finalprice'];
            $unitPriceBeforeGst = ($finalPrice / (1 + ($gstRate / 100))) / $order['quantity'];
            $data['data'][$key]['unit_price'] = number_format($unitPriceBeforeGst, 2, '.', '');
                    
        }
        //firm info
        $data['firm'] = $commanModel->getRecordById('firm_details', 1);
        //$data['customer_address'] = $commanModel->get_customer_address(isset($data['data'][0]['order_number']) ? $data['data'][0]['order_number'] : 0);
        //address info
        $data['exotic_address'] = $commanModel->get_exotic_address();

        $data['users'] = $usersModel->getAllUsers();
        $data['invoiceModel'] = null; // placeholder for next invoice number logic
        
        renderTemplate('views/invoices/create.php', $data, 'Create Invoice');
        exit;
    }
    
    public function createPost() {
        is_login();
        global $invoiceModel, $ordersModel, $commanModel;
        header('Content-Type: application/json');
        // print_r($_POST);
        // exit;
        // Validate form inputs
        $invoice_date = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d');
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        $vp_order_info_id = isset($_POST['vp_order_info_id']) ? trim($_POST['vp_order_info_id']) : '';
        $currency = isset($_POST['currency']) ? $_POST['currency'] : [];
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
        $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
        $discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
        $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        
        $order_numbers = isset($_POST['order_number']) && is_array($_POST['order_number']) ? $_POST['order_number'] : [];
        $item_codes = isset($_POST['item_code']) && is_array($_POST['item_code']) ? $_POST['item_code'] : [];
        $item_names = isset($_POST['item_name']) && is_array($_POST['item_name']) ? $_POST['item_name'] : [];
        $hsn_codes = isset($_POST['hsn']) && is_array($_POST['hsn']) ? $_POST['hsn'] : [];
        $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? $_POST['quantity'] : [];
        $unit_prices = isset($_POST['unit_price']) && is_array($_POST['unit_price']) ? $_POST['unit_price'] : [];
        $tax_rates = isset($_POST['tax_rate']) && is_array($_POST['tax_rate']) ? $_POST['tax_rate'] : [];
        $cgst = isset($_POST['cgst']) && is_array($_POST['cgst']) ? $_POST['cgst'] : [];
        $sgst = isset($_POST['sgst']) && is_array($_POST['sgst']) ? $_POST['sgst'] : [];
        $igst = isset($_POST['igst']) && is_array($_POST['igst']) ? $_POST['igst'] : [];
        $box_no = isset($_POST['box_no']) && is_array($_POST['box_no']) ? $_POST['box_no'] : [];
        
        if ($customer_id <= 0 || empty($order_numbers)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        //validate if invoice created for same order_number
        foreach ($order_numbers as $order_number) {
            $existingInvoice = $invoiceModel->getInvoiceByOrderNumber($order_number);
            if ($existingInvoice) {
                echo json_encode(['success' => false, 'message' => "Invoice already exists for Order Number: $order_number"]);
                exit;
            }
        }
        //validate currency same for all items
        $firstCurrency = $currency[0] ?? '';
        foreach ($currency as $curr) {
            if ($curr !== $firstCurrency) {
                echo json_encode(['success' => false, 'message' => 'All items must have the same currency']);
                exit;
            }
        }
        // Generate invoice number from global_settings
        $globalSettings = $commanModel->getRecordById('global_settings', 1);
        $invoice_prefix = $globalSettings['invoice_prefix'] ?? 'INV';
        $invoice_series = $globalSettings['invoice_series'] ?? 0;
        $invoice_series++;
        
        // Update global_settings with new invoice_series
        $commanModel->updateRecord('global_settings', ['invoice_series' => $invoice_series], ['id' => 1]);
        
        $invoice_number = $invoice_prefix . '-' . str_pad($invoice_series, 6, '0', STR_PAD_LEFT);
        
        // Create invoice header
        $isInternational = ($firstCurrency && $firstCurrency !== 'INR') ? 1 : 0;
        $invoiceData = [
            'invoice_number' => $invoice_number,
            'invoice_date' => $invoice_date,
            'customer_id' => $customer_id,
            'vp_order_info_id' => $vp_order_info_id,
            'currency' => $currency[0] ?? 'INR',
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'discount_amount' => $discount_amount,
            'total_amount' => $total_amount,
            'status' => isset($_POST['status']) ? trim($_POST['status']) : 'final',
            'created_by' => $_SESSION['user']['id'] ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        if ($currency[0] && $currency[0] !== 'INR') {
            $currencyRecord = $this->getCurrencyByCode($currency[0]);
            if ($currencyRecord) {
                $exchangeRate = floatval($currencyRecord['rate_export'] ?? 1);
                $convertedAmount = $total_amount * $exchangeRate;
                $invoiceData['converted_amount'] = $convertedAmount;
                $invoiceData['exchange_text'] = 'Exchange Rate ('. $currencyRecord['currency_unit'] . ' to INR): ' . number_format($exchangeRate, 6);
            }
        }else{
            $invoiceData['exchange_text'] = '';
            $invoiceData['converted_amount'] = 0.00;
        }
        $invoiceId = $invoiceModel->createInvoice($invoiceData);
        
        if (!$invoiceId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create invoice']);
            exit;
        }
        
        // Create invoice items
        $itemCreated = 0;
        $itemsFailed = [];
        
        foreach ($order_numbers as $idx => $order_number) {
            $quantity = isset($quantities[$idx]) ? (int)$quantities[$idx] : 0;
            $unit_price = isset($unit_prices[$idx]) ? floatval($unit_prices[$idx]) : 0;
            $tax_rate = isset($tax_rates[$idx]) ? floatval($tax_rates[$idx]) : 0;
            
            $amount = $quantity * $unit_price;
            $totalTaxAmount = ($amount * $tax_rate) / 100;
            
            // Calculate SGST/CGST/IGST (assuming 50/50 split for SGST/CGST, IGST is 0)
            //$sgstRate = $tax_rate / 2;
            //$cgstRate = $tax_rate / 2;
            $sgstAmt = ($amount * $sgst[$idx] ?? 0) / 100;
            $cgstAmt = ($amount * $cgst[$idx] ?? 0) / 100;
            $igstAmt = ($amount * $igst[$idx] ?? 0) / 100;
            
            $itemData = [
                'invoice_id' => $invoiceId,
                'order_number' => $order_number,
                'item_code' => isset($item_codes[$idx]) ? trim($item_codes[$idx]) : '',
                'item_name' => isset($item_names[$idx]) ? trim($item_names[$idx]) : '',
                'description' => '',
                'box_no' => isset($box_no[$idx]) ? trim($box_no[$idx]) : '',
                'hsn' => isset($hsn_codes[$idx]) ? trim($hsn_codes[$idx]) : '',
                'quantity' => $quantity,
                'unit_price' => $unit_price,
                'tax_rate' => $tax_rate,
                'cgst' => $cgstAmt,
                'sgst' => $sgstAmt,
                'igst' => $igstAmt,
                'tax_amount' => $totalTaxAmount,
                'line_total' => $amount + $totalTaxAmount
            ];
            //print_r($itemData);
            $result = $invoiceModel->createInvoiceItem($itemData);
            if ($result) {
                $itemCreated++;
            } else {
                $itemsFailed[] = $order_number;
            }
        }
        //save international fields
        if($isInternational){
            $internationalData = [
                'invoice_id' => $invoiceId,
                'pre_carriage_by' => isset($_POST['pre_carriage_by']) ? trim($_POST['pre_carriage_by']) : '',
                'port_of_loading' => isset($_POST['port_of_loading']) ? trim($_POST['port_of_loading']) : '',
                'port_of_discharge' => isset($_POST['port_of_discharge']) ? trim($_POST['port_of_discharge']) : '',
                'country_of_origin' => isset($_POST['country_of_origin']) ? trim($_POST['country_of_origin']) : '',
                'country_of_final_destination' => isset($_POST['country_of_final_destination']) ? trim($_POST['country_of_final_destination']) : '',
                'final_destination' => isset($_POST['final_destination']) ? trim($_POST['final_destination']) : '',
                'usd_export_rate' => isset($_POST['usd_export_rate']) ? floatval($_POST['usd_export_rate']) : 0,
                'ap_cost' => isset($_POST['ap_cost']) ? floatval($_POST['ap_cost']) : 0,
                'freight_charge' => isset($_POST['freight_charge']) ? floatval($_POST['freight_charge']) : 0,
                'insurance_charge' => isset($_POST['insurance_charge']) ? floatval($_POST['insurance_charge']) : 0
            ];
            $invoiceModel->insert_international_invoice_data($internationalData);
        }
        //call irisirp api to generate irn
        //$this->generateIrnForInvoice($invoiceId);

        // Update order status to invoiced
        foreach ($order_numbers as $order_number) {
            $ordersModel->updateOrderByOrderNumber($order_number, ['invoice_id' => $invoiceId]);
        }
        
        // Clear session
        unset($_SESSION['invoice_items']);
        
        echo json_encode([
            'success' => true,
            'message' => "Invoice created with $itemCreated items",
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice_number,
            'items_created' => $itemCreated,
            'items_failed' => $itemsFailed
        ]);
        exit;
    }
    public function generateIrnForInvoice($invoiceId) {
        is_login();
        global $invoiceModel, $commanModel;
        
        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        $items = $invoiceModel->getInvoiceItems($invoiceId);
        
        if (!$invoice || empty($items)) {
            return false;
        }
        
        // Prepare data for IRN generation
        $invoiceData = $commanModel->prepareIrisIrpInvoiceData($invoice, $items);
        
        require_once 'models/invoice/IrisIrpClient.php';
        // Initialize IrisIrpClient
        $irisClient = new IrisIrpClient(
            IRIS_IRP_CLIENT_ID,
            IRIS_IRP_CLIENT_SECRET,
            IRIS_IRP_USERNAME,
            IRIS_IRP_PASSWORD,
            IRIS_IRP_SANDBOX
        );
        
        try {
            // Authenticate
            $irisClient->authenticate();
            
            // Generate IRN
            $response = $irisClient->generateIrn($invoiceData);
            
            if (isset($response['irn'])) {
                // Update invoice with IRN details
                $updateData = [
                    'irn' => $response['irn'],
                    'ack_number' => $response['ack_number'] ?? '',
                    'ack_date' => isset($response['ack_date']) ? date('Y-m-d H:i:s', strtotime($response['ack_date'])) : null,
                    'signed_invoice' => $response['signed_invoice'] ?? '',
                    'qrcode_string' => $response['qr_code'] ?? '',
                    'irn_status' => 'generated'
                ];
                $invoiceModel->updateInvoice($invoiceId, $updateData);
                return true;
            } else {
                // Log error or handle failure
                return false;
            }
        } catch (Exception $e) {
            // Log exception or handle error
            return false;
        }
    }
    public function view() {
        is_login();
        global $invoiceModel;
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo '<p>Invalid Invoice ID.</p>';
            exit;
        }
        
        $invoice = $invoiceModel->getInvoiceById($id);
        $items = $invoiceModel->getInvoiceItems($id);
        
        if (!$invoice) {
            echo '<p>Invoice not found.</p>';
            exit;
        }
        
        $data = [
            'invoice' => $invoice,
            'items' => $items
        ];
        
        renderTemplate('views/invoices/view.php', $data, 'Invoice Details');
    }
    
    public function generatePdf() {
        is_login();
        global $invoiceModel;
        
        try {
            // Clear any output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
            if(!$invoice_id){
            $input = json_decode(file_get_contents('php://input'), true);
            $invoice_id = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;
            }
            if ($invoice_id <= 0) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
                exit;
            }
            
            $invoice = $invoiceModel->getInvoiceById($invoice_id);
            $items = $invoiceModel->getInvoiceItems($invoice_id);
            
            if (!$invoice) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Invoice not found']);
                exit;
            }
            
            //term and conditions fetch
            global $commanModel;
            $firmSettings = $commanModel->getRecordById('global_settings', 1);
            $invoice['terms_and_conditions'] = $firmSettings['terms_and_conditions'] ?? '';
            
            // Generate HTML for PDF
            $html = $this->generateInvoiceHtml($invoice, $items, 'tax_invoice');
            
            if (empty($html)) {
                throw new Exception('Failed to generate invoice HTML');
            }
            
            // Create mPDF instance
            require_once 'vendor/autoload.php';
            
            $filename = 'invoice_' . $invoice_id . '.pdf';
            
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'tempDir' => sys_get_temp_dir()
            ]);
            
            $mpdf->WriteHTML($html);
            
            // Set headers before output
            header('Content-Type: application/pdf; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            // Output PDF to browser
            $mpdf->Output($filename, 'D');
            exit;
            
        } catch (Exception $e) {
            // Clear any output buffers for error response
            if (ob_get_level()) {
                ob_end_clean();
            }
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Error generating PDF: ' . $e->getMessage(),
                'error' => $e->getTraceAsString()
            ]);
            exit;
        }
    }
    
    private function generateInvoiceHtml($invoice, $items, $type = '') {
        global $commanModel;
        
        // Initialize variables
        $itemsrows = '';
        $summaryrows = '';
        $totalSubtotal = 0;
        $totalTax = 0;
        $totalAmount = $invoice['total_amount'] ?? 0;
        $totalQuantity = 0;
        $totalGstAmount = 0;
        $totalSgstAmt = 0;
        $totalCgstAmt = 0;
        $totalIgstAmt = 0;
        
        // Build item rows
        foreach ($items as $idx => $item) {
            // $amount = $item['quantity'] * $item['unit_price'];
            // $taxAmount = ($amount * $item['tax_rate']) / 100;
            // $lineTotal = $amount + $taxAmount;
            
            // $totalSubtotal += $amount;
            // $totalTax += $taxAmount;
            // $totalAmount += $lineTotal;
            $totalQuantity += $item['quantity'];
            $totalGstAmount += $item['tax_amount'];
            
            // // Determine tax type (simplified - assuming SGST/CGST for domestic, IGST for other)
            // $sgstRate = $item['tax_rate'] / 2;
            // $cgstRate = $item['tax_rate'] / 2;
            // $igstRate = 0;
            // $sgstAmt = ($amount * $sgstRate) / 100;
            // $cgstAmt = ($amount * $cgstRate) / 100;
            // $igstAmt = 0;
            
            // $totalSgstAmt += $sgstAmt;
            // $totalCgstAmt += $cgstAmt;
            // $totalIgstAmt += $igstAmt;
            $totalSgstAmt += $item['sgst'];
            $totalCgstAmt += $item['cgst'];
            $totalIgstAmt += $item['igst'];
            if($item['igst'] > 0){
                $igstRate = ($item['igst'] / $item['quantity']) / ($item['unit_price'] /100);
                $sgstRate = 0;
                $cgstRate = 0;
            } else {
                $sgstRate = ($item['sgst'] / $item['quantity']) / ($item['unit_price'] /100);
                $cgstRate = ($item['cgst'] / $item['quantity']) / ($item['unit_price'] /100);
                $igstRate = 0;
            }
            $itemsrows .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['box_no'] ?? '') . '</td>
                        <td class="desc">' . htmlspecialchars($item['item_name'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['hsn'] ?? '') . '</td>
                        <td>' . $item['quantity'] . '</td>
                        <td class="right">' . number_format($item['unit_price'], 2) . '</td>
                        <td class="right">' . number_format($sgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['sgst'], 2) . '</td>
                        <td class="right">' . number_format($cgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['cgst'], 2) . '</td>
                        <td class="right">' . number_format($igstRate, 2) . '</td>
                        <td class="right">' . number_format($item['igst'], 2) . '</td>
                        <td class="right bold">' . number_format($item['line_total'], 2) . '</td>
                    </tr>
            ';
        }
        if(count($items) < 3){
            // Add empty rows to maintain table height
            $rowsToAdd = 3 - count($items);
            for ($i = 0; $i < $rowsToAdd; $i++) {
                $itemsrows .= '
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="desc">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right">&nbsp;</td>
                        <td class="right bold">&nbsp;</td>
                    </tr>
            ';
            }
        }
        // Build summary rows with tax totals
        $discount = $invoice['discount_amount'] ?? 0;
        
        // Add row for tax amount totals (below each SGST/CGST/IGST column)
        $summaryrows .= '
                    <tr style="background: #e8e8e8; border-top: 2px solid #000;">
                        <td colspan="4" class="right bold">Total:</td>
                        <td class="right bold">' . $totalQuantity . '</td>
                        <td ></td>
                        <td class="right bold"></td>
                        <td class="right bold">' . number_format($totalSgstAmt, 2) . '</td>
                        <td class="right bold"></td>
                        <td class="right bold">' . number_format($totalCgstAmt, 2) . '</td>
                        <td class="right bold"></td>
                        <td class="right bold">' . number_format($totalIgstAmt, 2) . '</td>
                        <td class="right bold">' . number_format($totalAmount, 2) . '</td>
                    </tr>
        ';       
       
        
        if ($discount > 0) {
            $summaryrows .= '
                    <tr style="background: #f9f9f9;">
                        <td colspan="10"></td>
                        <td class="right bold">Discount:</td>
                        <td class="right bold">-' . number_format($discount, 2) . '</td>
                    </tr>';
            $totalAmount -= $discount;
        }
        
        // Fetch currency exchange rate and add conversion row
        $currency = $invoice['currency'] ?? 'INR';
        $exchangeRate = 1;
        $convertedAmount = $totalAmount;
        
        if ($currency && $currency !== 'INR') {
            if($type === 'tax_invoice'){
                $exchangeText = $invoice['exchange_text'] ?? '';
                $convertedAmount = $invoice['converted_amount'] ?? 0;
            } else {
                 $currencyRecord = $this->getCurrencyByCode($currency);                 
                if (!empty($currencyRecord)) {
                    $exchangeRate = floatval($currencyRecord['rate_export'] ?? 1);
                    $convertedAmount = $totalAmount * $exchangeRate;
                }else{
                    //if currancy record not found then USD exchange rate will be considered if currency is not INR
                    $currencyRecord = $this->getCurrencyByCode('USD'); 
                    $exchangeRate = floatval($currencyRecord['rate_export'] ?? 1);
                    $convertedAmount = $totalAmount * $exchangeRate;
                }
                $exchangeText = 'Exchange Rate ('. $currencyRecord['currency_unit'] . ' to INR): ' . number_format($exchangeRate, 6);
            }
               
            $summaryrows .= '
                <tr style="background: #f9f9f9;">
                    <td colspan="13" style="padding: 20px;" class="right bold">' . htmlspecialchars($exchangeText) .'</td>
                    
                </tr>
                <tr style="background: #f9f9f9;">
                    <td colspan="12" class="right bold" style="text-align: right;">Converted Amount (INR)</td>
                    <td class="right bold">' . number_format($convertedAmount, 2) . '</td>
                </tr>';
            
        }
        
        $summaryrows .= '
                    <tr style="background: #f0f0f0; border-top: 2px solid #000;">
                        <td colspan="12" class="right bold" style="text-align: right;">Grand Total:</td>                      
                        <td class="right bold" style="border: 1px solid #000; padding: 8px;">' . number_format($totalAmount, 2) . '</td>
                    </tr>
        ';
        
        // Fetch customer and address information
        $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
        $billToInfo = '';
        $shipToInfo = '';
        
        if ($customer) {
            $billToInfo = '<strong>' . htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name'] ?? 'N/A') . '</strong><br>';
            $billToInfo .= htmlspecialchars($customer['address_line1'] ?? '') . '';
            $billToInfo .= htmlspecialchars($customer['address_line2'] ?? '') . '<br>';
            $billToInfo .= htmlspecialchars($customer['city'] ?? '') . ' ' . htmlspecialchars($customer['state'] ?? '') . ' ' . htmlspecialchars($customer['zipcode'] ?? '') . '<br>';
            $billToInfo .= 'Tel: ' . htmlspecialchars($customer['mobile'] ?? '') . '<br>';
        }
        if(!empty($customer['shipping_address_line1']) && !empty($customer['shipping_address_line2'])){
            $shipToInfo = '<strong>' . htmlspecialchars($customer['shipping_first_name'] . ' ' . $customer['shipping_last_name'] ?? 'N/A') . '</strong><br>';
            $shipToInfo .= htmlspecialchars($customer['shipping_address_line1'] ?? '') . '';
            $shipToInfo .= htmlspecialchars($customer['shipping_address_line2'] ?? '') . '<br>';
            $shipToInfo .= htmlspecialchars($customer['shipping_city'] ?? '') . ' ' . htmlspecialchars($customer['shipping_state'] ?? '') . ' ' . htmlspecialchars($customer['shipping_zipcode'] ?? '') . '<br>';
            $shipToInfo .= 'Tel: ' . htmlspecialchars($customer['shipping_mobile'] ?? '') . '<br>';
        }else{
            $shipToInfo = $billToInfo; // Use same info unless stored separately
        }
        //print_r($billToInfo);
        // Load template
        $templatePath = __DIR__ . '/../templates/invoices/tax_invoice.html';
        if (!file_exists($templatePath)) {
            return '<p>Error: Invoice template not found at ' . htmlspecialchars($templatePath) . '</p>';
        }
        
        $temphtml = file_get_contents($templatePath);
        
        // Replace placeholders
        $html = str_replace(
            ['{{INVOICE_NUMBER}}', '{{INVOICE_DATE}}', '{{BILL_TO_INFO}}', '{{SHIP_TO_INFO}}', '{{ITEM_ROWS}}', '{{SUMMARY_ROWS}}', '{{AMOUNT_IN_WORDS}}','{{TERM_AND_CONDITIONS}}'],
            [
                htmlspecialchars($invoice['invoice_number'] ?? 'N/A'),
                date('d M Y', strtotime($invoice['invoice_date'])),
                $billToInfo,
                $shipToInfo,
                $itemsrows,
                $summaryrows,
                numberToWords($totalAmount ?? 0),
                nl2br(htmlspecialchars($invoice['terms_and_conditions'] ?? ''))
            ],
            $temphtml
        );
        
        return $html;
    }
    
    public function previewInvoice() {
        is_login();
        header('Content-Type: application/json');
        
        try {
            global $commanModel;
            $previewData = json_decode(file_get_contents('php://input'), true);
            //print_r($previewData);
            // Get preview data from POST
            $invoiceDate = isset($previewData['invoice_date']) ? $previewData['invoice_date'] : date('Y-m-d');
            $customerId = isset($previewData['customer_id']) ? (int)$previewData['customer_id'] : 0;
            $vpAddressInfoId = isset($previewData['vp_order_info_id']) ? trim($previewData['vp_order_info_id']) : '';
            //$currency = isset($previewData['currency']) ? $previewData['currency'] : [];
            $subtotal = isset($previewData['subtotal']) ? floatval($previewData['subtotal']) : 0;
            $taxAmount = isset($previewData['tax_amount']) ? floatval($previewData['tax_amount']) : 0;
            $discountAmount = isset($previewData['discount_amount']) ? floatval($previewData['discount_amount']) : 0;
            $totalAmount = isset($previewData['total_amount']) ? floatval($previewData['total_amount']) : 0;
            
            // Get items
            $items = isset($previewData['items']) ? (array)$previewData['items'] : [];
            
            if (empty($items)) {
                echo json_encode(['success' => false, 'message' => 'No items to preview']);
                exit;
            }
            //print_array($currency);exit;
            // Build invoice data structure
            $invoice = [
                'invoice_number' => 'PREVIEW',
                'invoice_date' => $invoiceDate,
                'currency' => $items[0]['currency'] ?? 'INR',
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'vp_order_info_id' => $vpAddressInfoId,
                'terms_and_conditions' => ''
            ];
            //print_array($invoice);exit;
            // Get firm settings for terms and conditions
            $firmSettings = $commanModel->getRecordById('global_settings', 1);
            $invoice['terms_and_conditions'] = $firmSettings['terms_and_conditions'] ?? '';
            
            // Convert items to proper format for HTML generation
            $invoiceItems = [];
            foreach ($items as $item) {
                $quantity = floatval($item['quantity'] ?? 0);
                $unitPrice = floatval($item['unit_price'] ?? 0);
                $lineTotal = $quantity * $unitPrice;
                
                // Calculate GST amounts based on unit price and quantity
                $sgstPercent = floatval($item['sgst'] ?? 0);
                $cgstPercent = floatval($item['cgst'] ?? 0);
                $igstPercent = floatval($item['igst'] ?? 0);
                
                $sgstAmount = ($lineTotal * $sgstPercent) / 100;
                $cgstAmount = ($lineTotal * $cgstPercent) / 100;
                $igstAmount = ($lineTotal * $igstPercent) / 100;
                $totalTaxAmount = $sgstAmount + $cgstAmount + $igstAmount;
                
                $invoiceItems[] = [
                    'box_no' => $item['box_no'] ?? '',
                    'item_name' => $item['item_name'] ?? '',
                    'hsn' => $item['hsn'] ?? '',
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'sgst' => $sgstAmount,
                    'cgst' => $cgstAmount,
                    'igst' => $igstAmount,
                    'tax_amount' => $totalTaxAmount,
                    'line_total' => $lineTotal + $totalTaxAmount
                ];
            }
            
            // Generate the invoice HTML using the tax invoice template
            $html = $this->generateInvoiceHtml($invoice, $invoiceItems, 'preview');
            
            if (empty($html)) {
                echo json_encode(['success' => false, 'message' => 'Failed to generate preview HTML']);
                exit;
            }
            
            echo json_encode([
                'success' => true,
                'html' => $html
            ]);
            exit;
            
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Error generating preview: ' . $e->getMessage()
            ]);
            exit;
        }
    }
    public function clearSessionItems() {
        is_login();
        header('Content-Type: application/json');
        
        if (isset($_SESSION['invoice_items'])) {
            unset($_SESSION['invoice_items']);
        }
        
        echo json_encode(['success' => true, 'message' => 'Invoice items cleared from session']);
        exit;
    }
    public function printInvoice() {
        is_login();
        global $invoiceModel;
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) {
            echo '<p>Invalid Invoice ID.</p>';
            exit;
        }
        
        $invoice = $invoiceModel->getInvoiceById($id);
        $items = $invoiceModel->getInvoiceItems($id);
        
        if (!$invoice) {
            echo '<p>Invoice not found.</p>';
            exit;
        }
        
        $data = [
            'invoice' => $invoice,
            'items' => $items
        ];
        
        renderTemplate('views/invoices/print.php', $data, 'Print Invoice');
    }
    public function fetchItems(){
        is_login();
        header('Content-Type: application/json');
        $inputdata = json_decode(file_get_contents('php://input'), true);
        global $ordersModel;
        //print_r($inputdata);exit;
        $customerId = isset($inputdata['customer_id']) ? (int)$inputdata['customer_id'] : 0;
        $itemIds = isset($inputdata['item_ids']) ? $inputdata['item_ids'] : [];
        $search = isset($inputdata['search']) ? $inputdata['search'] : 0;
        // if (empty($itemIds) || !is_array($itemIds)) {
        //     echo json_encode(['success' => false, 'message' => 'No item IDs provided']);
        //     exit;
        // }
        
         $itemsData = [];
        // foreach ($itemIds as $id) {
        //     $order = $ordersModel->getOrderById($id);
        //     if ($order) {
        //         $itemsData[] = $order;
        //     }
        // }
        
       //echo $customerId.'---'.$search;
        $orderItems = $ordersModel->getOrderItemsByCustomerId($customerId,$search,[]);
        
        //calculate unit price before gst
        foreach($orderItems as $key => $order){
            $gstRate = isset($order['gst']) ? $order['gst'] : 0;
            $finalPrice = $order['finalprice'];
            $unitPriceBeforeGst = ($finalPrice / (1 + ($gstRate / 100))) / $order['quantity'];
            $orderItems[$key]['unit_price'] = number_format($unitPriceBeforeGst, 2, '.', '');
        }
        
        echo json_encode(['success' => true, 'items' => $orderItems, 'selected_items' => $itemsData]);
        exit;

    }
    
    // Helper method to get currency by code
    private function getCurrencyByCode($code) {
        global $commanModel;
        return $commanModel->getRecordByField('currency_master', 'currency_code', strtoupper($code));
    }
}
