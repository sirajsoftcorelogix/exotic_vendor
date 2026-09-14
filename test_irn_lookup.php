<?php
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'exotic_vendor');

$conn = new mysqli('127.0.0.1', 'root', '');
// Find database with vp_invoices
$dbName = 'exotic_vendor';
$res = $conn->query("SHOW DATABASES");
while ($row = $res->fetch_row()) {
    $d = $row[0];
    if (in_array($d, ['information_schema', 'mysql', 'performance_schema', 'sys'])) continue;
    $c2 = new mysqli('127.0.0.1', 'root', '', $d);
    $r2 = $c2->query("SHOW TABLES LIKE 'vp_invoices'");
    if ($r2 && $r2->num_rows > 0) {
        $dbName = $d;
        break;
    }
}
$conn = new mysqli('127.0.0.1', 'root', '', $dbName);
$GLOBALS['conn'] = $conn;

require_once 'models/invoice/invoice.php';
require_once 'models/order/order.php';
require_once 'helpers/invoice/invoice_irn_pdf.php';

$orderNumber = '3192530';
$invoiceModel = new Invoice($conn);

$inv = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
echo "Database: $dbName\n";
echo "Invoice for order $orderNumber:\n";
print_r($inv);

if ($inv) {
    $details = invoice_resolve_irn_details($inv, $conn);
    echo "\nIRN Details:\n";
    echo "irn: " . ($details['irn'] ?? 'EMPTY') . "\n";
    echo "ack_number: " . ($details['ack_number'] ?? 'EMPTY') . "\n";
    echo "ack_date: " . ($details['ack_date'] ?? 'EMPTY') . "\n";
    echo "qrcode_string len: " . strlen($details['qrcode_string'] ?? '') . "\n";
    echo "qr_data_uri len: " . strlen($details['qr_data_uri'] ?? '') . "\n";
    if (!empty($details['qr_data_uri'])) {
        echo "qr_data_uri prefix: " . substr($details['qr_data_uri'], 0, 60) . "\n";
    }

    $res = $conn->query("SELECT * FROM vp_domestic_ewb_irn WHERE vp_invoices_id = " . (int)$inv['id']);
    if ($res && $row = $res->fetch_assoc()) {
        echo "\nvp_domestic_ewb_irn row:\n";
        echo "irn: " . ($row['irn'] ?? '') . "\n";
        echo "irn_response len: " . strlen($row['irn_response'] ?? '') . "\n";
        echo "irn_response preview: " . substr($row['irn_response'] ?? '', 0, 300) . "\n";
    }
}
