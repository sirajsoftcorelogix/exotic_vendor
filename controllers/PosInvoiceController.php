<?php
require_once 'models/PosInvoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/customer/Customer.php';
require_once 'models/product/product.php';
require_once 'models/order/stock.php';
$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);
class PosInvoiceController
{

    /* ===============================
       PAGE LOAD
    =============================== */
    public function index()
    {
        global $conn;

        $customerModel = new Customer($conn);

        $customers = $customerModel->getAllCustomers(1000, 0, []);

        renderTemplate('views/posinvoice/index.php', [
            'customers' => $customers
        ]);
    }

    /* ===============================
       AJAX LIST
    =============================== */
    public function list_ajax()
    {
        global $conn;

        $warehouseId = $_SESSION['warehouse_id'] ?? 0;

        $sql = "
    SELECT 
        i.id,
        i.invoice_number,
        i.invoice_date,
        i.status,
        i.total_amount,

        o.order_number,
        o.payment_type,

        c.name AS customer_name,

        IFNULL((
            SELECT SUM(amount) 
            FROM pos_payments 
            WHERE invoice_id = i.id
        ),0) AS paid_amount,

        i.total_amount - IFNULL((
            SELECT SUM(amount) 
            FROM pos_payments 
            WHERE invoice_id = i.id
        ),0) AS due_amount

    FROM vp_invoices i

    LEFT JOIN vp_order_info o 
        ON o.id = i.vp_order_info_id

    LEFT JOIN vp_customers c 
        ON c.id = i.customer_id
WHERE IFNULL(o.payment_type,'') = 'offline' AND i.warehouse_id = " . intval($warehouseId) . "
    ";

        if (!empty($_GET['order_number'])) {
            $sql .= " AND o.order_number LIKE '%" . $conn->real_escape_string($_GET['order_number']) . "%'";
        }

        if (!empty($_GET['status'])) {
            $sql .= " AND i.status = '" . $conn->real_escape_string($_GET['status']) . "'";
        }

        if (!empty($_GET['from_date'])) {
            $sql .= " AND i.invoice_date >= '" . $_GET['from_date'] . "'";
        }

        if (!empty($_GET['to_date'])) {
            $sql .= " AND i.invoice_date <= '" . $_GET['to_date'] . "'";
        }

        if (!empty($_GET['type'])) {
            $sql .= " AND o.payment_type = '" . $conn->real_escape_string($_GET['type']) . "'";
        }

        if (!empty($_GET['customer_id'])) {
            $sql .= " AND i.customer_id = " . intval($_GET['customer_id']);
        }

        if (!empty($_GET['amount_min'])) {
            $sql .= " AND i.total_amount >= " . floatval($_GET['amount_min']);
        }

        if (!empty($_GET['amount_max'])) {
            $sql .= " AND i.total_amount <= " . floatval($_GET['amount_max']);
        }


        $sql .= " ORDER BY i.id DESC";

        $res = $conn->query($sql);

        $data = [];

        while ($row = $res->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        exit;
    }
    /* ===============================
       DELETE
    =============================== */
    public function delete()
    {
        global $conn;

        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM vp_invoices WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(["success" => true]);
    }

    /* ===============================
       PREVIEW
    =============================== */
    public function preview()
    {
        global $conn;

        $invoiceId = $_POST['invoice_id'];

        $items = $conn->query("
            SELECT * FROM invoice_items
            WHERE invoice_id = $invoiceId
        ");

        ob_start();
        require "views/invoices/preview_template.php";
        $html = ob_get_clean();

        echo json_encode([
            "success" => true,
            "html" => $html
        ]);
    }
    public function preview_new()
    {
        global $conn;

        $data = json_decode(file_get_contents("php://input"), true);

        $invoiceId = $data['invoice_id'] ?? 0;
        $orderNumber = $data['orderid'] ?? '';

        // ✅ FIX: use order_number (NOT order_id)
        if (!$invoiceId && $orderNumber) {

            $orderNumber = $conn->real_escape_string($orderNumber);

            $res = $conn->query("
            SELECT id FROM vp_invoices 
            WHERE order_number = '$orderNumber'
            LIMIT 1
        ");

            $row = $res->fetch_assoc();
            $invoiceId = $row['id'] ?? 0;
        }

        if (!$invoiceId) {
            echo json_encode([
                "success" => false,
                "message" => "Invoice not found"
            ]);
            exit;
        }

        // ✅ get invoice
        $invoice = $conn->query("
        SELECT * FROM vp_invoices WHERE id = $invoiceId
    ")->fetch_assoc();

        // ✅ template switch
        if ($invoice['status'] === 'proforma') {
            $template = "views/invoices/proforma_invoice.php";
        } else {
            $template = "views/invoices/tax_invoice.php";
        }

        // ✅ items
        $items = $conn->query("
        SELECT * FROM invoice_items WHERE invoice_id = $invoiceId
    ");

        ob_start();
        require $template;
        $html = ob_get_clean();

        echo json_encode([
            "success" => true,
            "html" => $html,
            "invoice_id" => $invoiceId
        ]);
    }
   
   
    public function create_from_payment()
    {
        global $conn;

        $data = json_decode(file_get_contents("php://input"), true);
        $paymentId = (int)$data['payment_id'];

        $payment = $conn->query("
        SELECT * FROM pos_payments WHERE id = $paymentId
    ")->fetch_assoc();

        if (!$payment) {
            echo json_encode(["success" => false]);
            exit;
        }

        $orderNumber = $payment['order_id'];

        // ✅ FIND EXISTING INVOICE
        $invoice = $conn->query("
        SELECT * FROM vp_invoices 
        WHERE order_number = '" . $conn->real_escape_string($orderNumber) . "'
        LIMIT 1
    ")->fetch_assoc();

        if ($invoice) {

            // ✅ UPDATE STATUS ONLY (NO NEW INSERT)
            if ($payment['payment_stage'] === 'final') {

                $conn->query("
                UPDATE vp_invoices 
                SET status = 'final'
                WHERE id = " . $invoice['id'] . "
            ");
            }

            // ✅ LINK PAYMENT
            $conn->query("
            UPDATE pos_payments 
            SET invoice_id = " . $invoice['id'] . "
            WHERE id = $paymentId
        ");

            echo json_encode([
                "success" => true,
                "invoice_id" => $invoice['id']
            ]);
            exit;
        }

        // ❗ fallback (rare)
        echo json_encode([
            "success" => false,
            "message" => "Invoice not found for update"
        ]);
    }
    /* ===============================
       GET SINGLE
    =============================== */
    public function get_single_invoice()
    {
        global $conn;

        $id = $_GET['id'];

        $res = $conn->query("
        SELECT * FROM vp_invoices WHERE id=$id
        ");

        echo json_encode([
            "success" => true,
            "invoice" => $res->fetch_assoc()
        ]);
    }

    /* ===============================
       UPDATE STATUS
    =============================== */
    public function update_status()
    {
        global $conn;

        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("
        UPDATE vp_invoices SET status=? WHERE id=?
        ");

        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        echo json_encode(["success" => true]);
    }

    /* ===============================
       PDF
    =============================== */
    public function generate_pdf_bk()
    {
        global $conn;

        $invoiceId = $_GET['invoice_id'];

        require 'views/invoices/pdf.php';
    }
    public function generatePdf()
    {
        is_login();
        global $invoiceModel;

        try {
            // Clear any output buffers
            if (ob_get_level()) {
                ob_end_clean();
            }

            $invoice_id = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
            if (!$invoice_id) {
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

            $filename = '' . $invoice['invoice_number'] . '.pdf';

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
    private function generateInvoiceHtml($invoice, $items, $type = '')
    {
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
            if ($item['igst'] > 0) {
                $igstRate = ($item['igst'] / $item['quantity']) / ($item['unit_price'] / 100);
                $sgstRate = 0;
                $cgstRate = 0;
            } else {
                $sgstRate = ($item['sgst'] / $item['quantity']) / ($item['unit_price'] / 100);
                $cgstRate = ($item['cgst'] / $item['quantity']) / ($item['unit_price'] / 100);
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
        if (count($items) < 3) {
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
            if ($type === 'tax_invoice') {
                $exchangeText = $invoice['exchange_text'] ?? '';
                $convertedAmount = $invoice['converted_amount'] ?? 0;
            } else {
                $currencyRecord = $this->getCurrencyByCode($currency);
                if (!empty($currencyRecord)) {
                    $exchangeRate = floatval($currencyRecord['rate_export'] ?? 1);
                    $convertedAmount = $totalAmount * $exchangeRate;
                } else {
                    //if currancy record not found then USD exchange rate will be considered if currency is not INR
                    $currencyRecord = $this->getCurrencyByCode('USD');
                    $exchangeRate = floatval($currencyRecord['rate_export'] ?? 1);
                    $convertedAmount = $totalAmount * $exchangeRate;
                }
                $exchangeText = 'Exchange Rate (' . $currencyRecord['currency_unit'] . ' to INR): ' . number_format($exchangeRate, 6);
            }

            $summaryrows .= '
                <tr style="background: #f9f9f9;">
                    <td colspan="13" style="padding: 20px;" class="right bold">' . htmlspecialchars($exchangeText) . '</td>
                    
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
        if (!empty($customer['shipping_address_line1']) && !empty($customer['shipping_address_line2'])) {
            $shipToInfo = '<strong>' . htmlspecialchars($customer['shipping_first_name'] . ' ' . $customer['shipping_last_name'] ?? 'N/A') . '</strong><br>';
            $shipToInfo .= htmlspecialchars($customer['shipping_address_line1'] ?? '') . '';
            $shipToInfo .= htmlspecialchars($customer['shipping_address_line2'] ?? '') . '<br>';
            $shipToInfo .= htmlspecialchars($customer['shipping_city'] ?? '') . ' ' . htmlspecialchars($customer['shipping_state'] ?? '') . ' ' . htmlspecialchars($customer['shipping_zipcode'] ?? '') . '<br>';
            $shipToInfo .= 'Tel: ' . htmlspecialchars($customer['shipping_mobile'] ?? '') . '<br>';
        } else {
            $shipToInfo = $billToInfo; // Use same info unless stored separately
        }
        //print_r($billToInfo);
        // Load template
        if ($invoice['status'] == 'proforma') {
            $templatePath = __DIR__ . '/../templates/invoices/proforma_invoice.html';
        } else {

            $templatePath = __DIR__ . '/../templates/invoices/tax_invoice.html';
        }
        if (!file_exists($templatePath)) {
            return '<p>Error: Invoice template not found at ' . htmlspecialchars($templatePath) . '</p>';
        }

        $temphtml = file_get_contents($templatePath);

        // Replace placeholders
        $html = str_replace(
            ['{{INVOICE_NUMBER}}', '{{INVOICE_DATE}}', '{{BILL_TO_INFO}}', '{{SHIP_TO_INFO}}', '{{ITEM_ROWS}}', '{{SUMMARY_ROWS}}', '{{AMOUNT_IN_WORDS}}', '{{TERM_AND_CONDITIONS}}'],
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
    
    public function create_auto_from_order()
    {
        is_login();
        header('Content-Type: application/json');

        global $conn, $invoiceModel;

        $input = json_decode(file_get_contents('php://input'), true);
        $orderNumber = $input['orderid'] ?? null;

        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }

        // ✅ CHECK EXISTING
        $existing = $invoiceModel->getInvoiceByOrderNumber($orderNumber);
        if ($existing) {
            echo json_encode([
                'success' => true,
                'invoice_id' => $existing['id']
            ]);
            exit;
        }

        // ✅ GET PAYMENT STAGE
        $payment = $conn->query("
        SELECT * FROM pos_payments 
        WHERE order_id = '" . $conn->real_escape_string($orderNumber) . "'
        ORDER BY id DESC LIMIT 1
    ")->fetch_assoc();

        $stage = $payment['payment_stage'] ?? 'final';

        // ✅ SET STATUS
        $status = ($stage === 'final') ? 'final' : 'proforma';

        // ===== GET ORDER ITEMS =====
        $stmt = $conn->prepare("SELECT * FROM vp_orders WHERE order_number = ?");
        $stmt->bind_param("s", $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 0) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }

        // ===== GET ADDRESS =====
        $stmt2 = $conn->prepare("SELECT id FROM vp_order_info WHERE order_number = ? LIMIT 1");
        $stmt2->bind_param("s", $orderNumber);
        $stmt2->execute();
        $info = $stmt2->get_result()->fetch_assoc();

        // ===== BUILD POST =====
        $_POST = [
            'invoice_date' => date('Y-m-d'),
            'customer_id' => $items[0]['customer_id'],
            'vp_order_info_id' => $info['id'],
            'status' => $status,   // 🔥 IMPORTANT
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0
        ];

        foreach ($items as $it) {

            $_POST['order_number'][] = $it['order_number'];
            $_POST['item_code'][] = $it['item_code'];
            $_POST['item_name'][] = $it['title'];
            $_POST['hsn'][] = $it['hsn'];
            $_POST['quantity'][] = $it['quantity'];

            $unit = ($it['finalprice'] / (1 + ($it['gst'] / 100))) / $it['quantity'];

            $_POST['unit_price'][] = $unit;
            $_POST['tax_rate'][] = $it['gst'];

            $_POST['cgst'][] = $it['gst'] / 2;
            $_POST['sgst'][] = $it['gst'] / 2;
            $_POST['igst'][] = 0;

            $_POST['box_no'][] = '';
            $_POST['currency'][] = $it['currency'];

            $_POST['subtotal'] += $unit * $it['quantity'];
            $_POST['tax_amount'] += ($unit * $it['quantity']) * ($it['gst'] / 100);
        }

        $_POST['total_amount'] = $_POST['subtotal'] + $_POST['tax_amount'];

        return $this->createPost();
    }
    public function create_auto_from_order1()
    {
        global $conn;

        $data = json_decode(file_get_contents("php://input"), true);
        $orderNumber = $conn->real_escape_string($data['orderid']);

        // ✅ get order items
        $items = $conn->query("
        SELECT * FROM vp_orders 
        WHERE order_number = '$orderNumber'
    ");

        if ($items->num_rows == 0) {
            echo json_encode(["success" => false, "message" => "Order not found"]);
            exit;
        }

        $orderItems = [];
        while ($row = $items->fetch_assoc()) {
            $orderItems[] = $row;
        }

        // ✅ get payment stage
        $payment = $conn->query("
        SELECT * FROM pos_payments 
        WHERE order_id = '$orderNumber'
        ORDER BY id DESC LIMIT 1
    ")->fetch_assoc();

        $stage = $payment['payment_stage'] ?? 'final';
        $status = ($stage === 'final') ? 'final' : 'proforma';

        // ✅ check existing invoice
        $check = $conn->query("
        SELECT id FROM vp_invoices 
        WHERE order_number = '$orderNumber'
    ");

        if ($check->num_rows > 0) {
            $invoiceId = $check->fetch_assoc()['id'];

            echo json_encode([
                "success" => true,
                "invoice_id" => $invoiceId
            ]);
            exit;
        }

        // ✅ build POST like main system
        $_POST = [
            'invoice_date' => date('Y-m-d'),
            'customer_id' => $orderItems[0]['customer_id'],
            'vp_order_info_id' => $orderItems[0]['vp_order_info_id'] ?? 0,
            'status' => $status,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0
        ];

        foreach ($orderItems as $i => $item) {

            $_POST['order_number'][] = $item['order_number'];
            $_POST['item_code'][] = $item['item_code'];
            $_POST['item_name'][] = $item['title'];
            $_POST['hsn'][] = $item['hsn'];
            $_POST['quantity'][] = $item['quantity'];

            $unit = ($item['finalprice'] / (1 + ($item['gst'] / 100))) / $item['quantity'];

            $_POST['unit_price'][] = $unit;
            $_POST['tax_rate'][] = $item['gst'];

            $_POST['cgst'][] = $item['gst'] / 2;
            $_POST['sgst'][] = $item['gst'] / 2;
            $_POST['igst'][] = 0;

            $_POST['box_no'][] = '';
            $_POST['currency'][] = $item['currency'];

            $_POST['subtotal'] += $unit * $item['quantity'];
            $_POST['tax_amount'] += ($unit * $item['quantity']) * ($item['gst'] / 100);
        }

        $_POST['total_amount'] = $_POST['subtotal'] + $_POST['tax_amount'];

        // ✅ CALL MAIN INVOICE LOGIC 🔥
        return $this->createPost();
    }
    public function createPost()
    {
        is_login();
        global $invoiceModel, $ordersModel, $commanModel, $conn;
        header('Content-Type: application/json');
        //print_r($_POST);
        //exit;
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
                $invoiceData['exchange_text'] = 'Exchange Rate (' . $currencyRecord['currency_unit'] . ' to INR): ' . number_format($exchangeRate, 6);
            }
        } else {
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
        $productModel = new Product($conn);

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
                'image_url' => isset($_POST['image_url'][$idx]) ? trim($_POST['image_url'][$idx]) : '',
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
                'line_total' => $amount + $totalTaxAmount,
                'groupname' => isset($_POST['groupname'][$idx]) ? trim($_POST['groupname'][$idx]) : ''
            ];
            $itemData['product_id'] = $productModel->getProductIdForInvoiceLine(
                (string)$order_number,
                (string)($itemData['item_code'] ?? '')
            );
            //print_r($itemData);
            $result = $invoiceModel->createInvoiceItem($itemData);
            if ($result) {
                $itemCreated++;
            } else {
                $itemsFailed[] = $order_number;
            }
        }
        //save international fields
        if ($isInternational) {
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
        $stockModel = new Stock($conn);
        $stockUpdate = $stockModel->updateStockByInvoiceId((int)$invoiceId);

        // Clear session
        unset($_SESSION['invoice_items']);

        echo json_encode([
            'success' => true,
            'message' => "Invoice created with $itemCreated items",
            'invoice_id' => $invoiceId,
            'invoice_number' => $invoice_number,
            'items_created' => $itemCreated,
            'items_failed' => $itemsFailed,
            'stock_update' => $stockUpdate,
        ]);
        exit;
    }
    private function getCurrencyByCode($code)
    {
        global $commanModel;
        return $commanModel->getRecordByField('currency_master', 'currency_code', strtoupper($code));
    }
}
