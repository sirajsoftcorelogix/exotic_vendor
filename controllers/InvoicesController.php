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
        global $invoiceModel, $ordersModel;
        header('Content-Type: application/json');
        
        // Validate form inputs
        $invoice_date = isset($_POST['invoice_date']) ? $_POST['invoice_date'] : date('Y-m-d');
        $customer_id = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        $vp_address_info_id = isset($_POST['vp_address_info_id']) ? trim($_POST['vp_address_info_id']) : '';
        $currency = isset($_POST['currency']) ? trim($_POST['currency']) : 'INR';
        $subtotal = isset($_POST['subtotal']) ? floatval($_POST['subtotal']) : 0;
        $tax_amount = isset($_POST['tax_amount']) ? floatval($_POST['tax_amount']) : 0;
        $discount_amount = isset($_POST['discount_amount']) ? floatval($_POST['discount_amount']) : 0;
        $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
        
        $order_numbers = isset($_POST['order_number']) && is_array($_POST['order_number']) ? $_POST['order_number'] : [];
        $item_codes = isset($_POST['item_code']) && is_array($_POST['item_code']) ? $_POST['item_code'] : [];
        $item_names = isset($_POST['item_name']) && is_array($_POST['item_name']) ? $_POST['item_name'] : [];
        $quantities = isset($_POST['quantity']) && is_array($_POST['quantity']) ? $_POST['quantity'] : [];
        $unit_prices = isset($_POST['unit_price']) && is_array($_POST['unit_price']) ? $_POST['unit_price'] : [];
        $tax_rates = isset($_POST['tax_rate']) && is_array($_POST['tax_rate']) ? $_POST['tax_rate'] : [];
        
        if ($customer_id <= 0 || empty($order_numbers)) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            exit;
        }
        
        // Create invoice header
        $invoiceData = [
            'invoice_date' => $invoice_date,
            'customer_id' => $customer_id,
            'vp_address_info_id' => $vp_address_info_id,
            'currency' => $currency,
            'subtotal' => $subtotal,
            'tax_amount' => $tax_amount,
            'discount_amount' => $discount_amount,
            'total_amount' => $total_amount,
            'status' => isset($_POST['status']) ? trim($_POST['status']) : 'draft',
            'created_by' => $_SESSION['user']['id'] ?? 0,
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $invoiceId = $invoiceModel->createInvoice($invoiceData);
        
        if (!$invoiceId) {
            echo json_encode(['success' => false, 'message' => 'Failed to create invoice']);
            exit;
        }
        
        // Create invoice items
        $itemCreated = 0;
        $itemsFailed = [];
        
        foreach ($order_numbers as $idx => $order_number) {
            $itemData = [
                'invoice_id' => $invoiceId,
                'order_number' => $order_number,
                'item_code' => isset($item_codes[$idx]) ? trim($item_codes[$idx]) : '',
                'item_name' => isset($item_names[$idx]) ? trim($item_names[$idx]) : '',
                'description' => '',
                'box_no' => 0,
                'quantity' => isset($quantities[$idx]) ? (int)$quantities[$idx] : 0,
                'unit_price' => isset($unit_prices[$idx]) ? floatval($unit_prices[$idx]) : 0,
                'tax_rate' => isset($tax_rates[$idx]) ? floatval($tax_rates[$idx]) : 0,
                'cgst' => 0,
                'sgst' => 0,
                'igst' => 0,
                'tax_amount' => 0,
                'line_total' => (isset($quantities[$idx]) ? (int)$quantities[$idx] : 0) * (isset($unit_prices[$idx]) ? floatval($unit_prices[$idx]) : 0)
            ];
            
            $result = $invoiceModel->createInvoiceItem($itemData);
            if ($result) {
                $itemCreated++;
            } else {
                $itemsFailed[] = $order_number;
            }
        }
        
        // Update order status to invoiced
        foreach ($order_numbers as $order_number) {
            // optional: update order status
        }
        
        // Clear session
        unset($_SESSION['invoice_items']);
        
        echo json_encode([
            'success' => true,
            'message' => "Invoice created with $itemCreated items",
            'invoice_id' => $invoiceId,
            'items_created' => $itemCreated,
            'items_failed' => $itemsFailed
        ]);
        exit;
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
        header('Content-Type: application/pdf');
        
        $input = json_decode(file_get_contents('php://input'), true);
        $invoice_id = isset($input['invoice_id']) ? (int)$input['invoice_id'] : 0;
        
        if ($invoice_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid invoice ID']);
            exit;
        }
        
        $invoice = $invoiceModel->getInvoiceById($invoice_id);
        $items = $invoiceModel->getInvoiceItems($invoice_id);
        
        if (!$invoice) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Invoice not found']);
            exit;
        }
        
        // Generate HTML for PDF
        $html = $this->generateInvoiceHtml($invoice, $items);
       
        // Create mPDF instance
        require_once 'vendor/autoload.php';
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10
        ]);
        
        $mpdf->WriteHTML($html);
        $mpdf->Output('invoice_' . $invoice_id . '.pdf', 'D');
        exit;
    }
    
    private function generateInvoiceHtml($invoice, $items) {
        global $commanModel;
        $temphtml = file_get_contents('templates/invoices/tax_invoice.html');
        $html = '
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
                .header { background-color: #f5f5f5; padding: 20px; text-align: center; border-bottom: 2px solid #333; }
                .header h1 { margin: 0; font-size: 24px; }
                .invoice-meta { display: flex; justify-content: space-between; padding: 20px; }
                .invoice-meta-left { }
                .invoice-meta-right { text-align: right; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #f0f0f0; border: 1px solid #ddd; padding: 8px; text-align: left; }
                td { border: 1px solid #ddd; padding: 8px; }
                .totals { margin-top: 20px; width: 100%; }
                .totals-row { display: flex; justify-content: flex-end; }
                .totals-line { width: 300px; display: flex; justify-content: space-between; padding: 8px; border-bottom: 1px solid #ddd; }
                .totals-line.total { border-top: 2px solid #333; border-bottom: 2px solid #333; font-weight: bold; font-size: 16px; }
                .footer { margin-top: 50px; padding: 20px; border-top: 1px solid #ddd; text-align: center; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>TAX INVOICE</h1>
            </div>
            
            <div class="invoice-meta">
                <div class="invoice-meta-left">
                    <p><strong>Invoice Date:</strong> ' . date('d M Y', strtotime($invoice['invoice_date'])) . '</p>
                    <p><strong>Invoice Number:</strong> ' . htmlspecialchars($invoice['invoice_number']) . '</p>
                    <p><strong>Currency:</strong> ' . htmlspecialchars($invoice['currency']) . '</p>
                    <p><strong>Status:</strong> ' . ucfirst($invoice['status']) . '</p>
                </div>
                <div class="invoice-meta-right">
                    <p><strong>Customer ID:</strong> ' . $invoice['customer_id'] . '</p>
                </div>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>S.No</th>
                        <th>Order No</th>
                        <th>Item Code</th>
                        <th>Item Name</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Tax %</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
        ';
        
        foreach ($items as $idx => $item) {
            $amount = $item['quantity'] * $item['unit_price'];
            $html .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['order_number']) . '</td>
                        <td>' . htmlspecialchars($item['item_code']) . '</td>
                        <td>' . htmlspecialchars($item['item_name']) . '</td>
                        <td>' . $item['quantity'] . '</td>
                        <td>' . number_format($item['unit_price'], 2) . '</td>
                        <td>' . $item['tax_rate'] . '%</td>
                        <td>' . number_format($amount, 2) . '</td>
                    </tr>
            ';
        }
        
        $html .= '
                </tbody>
            </table>
            
            <div class="totals">
                <div class="totals-row">
                    <div class="totals-line">
                        <span>Subtotal:</span>
                        <span>₹ ' . number_format($invoice['subtotal'], 2) . '</span>
                    </div>
                </div>
                <div class="totals-row">
                    <div class="totals-line">
                        <span>Tax:</span>
                        <span>₹ ' . number_format($invoice['tax_amount'], 2) . '</span>
                    </div>
                </div>
                <div class="totals-row">
                    <div class="totals-line">
                        <span>Discount:</span>
                        <span>₹ ' . number_format($invoice['discount_amount'], 2) . '</span>
                    </div>
                </div>
                <div class="totals-row">
                    <div class="totals-line total">
                        <span>Total Amount:</span>
                        <span>₹ ' . number_format($invoice['total_amount'], 2) . '</span>
                    </div>
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for your business!</p>
                <p>This is a computer generated invoice and does not require a signature.</p>
            </div>
        </body>
        </html>
        ';
        
        return $html;
    }
}
