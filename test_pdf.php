<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Suppress SERVER_NAME warning by setting it
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'localhost';
}

try {
    require_once 'settings/routes.php';
    require_once 'helpers/html_helpers.php';
    require_once 'models/comman/tables.php';
    require_once 'models/invoice/invoice.php';
    
    global $conn;
    $invoiceModel = new Invoice($conn);
    
    // Get all invoices to find one
    $allInvoices = $invoiceModel->getAllInvoices(10, 0);
    
    if (empty($allInvoices)) {
        echo "No invoices found in database\n";
        exit;
    }
    
    echo "Found " . count($allInvoices) . " invoices\n";
    
    // Get first invoice
    $invoice = $allInvoices[0];
    $invoice_id = $invoice['id'];
    
    echo "Using invoice ID: " . $invoice_id . "\n";
    echo "Invoice number: " . $invoice['invoice_number'] . "\n";
    
    $items = $invoiceModel->getInvoiceItems($invoice_id);
    echo "Items count: " . count($items) . "\n";
    
    // Check template
    $templatePath = __DIR__ . '/templates/invoices/tax_invoice.html';
    echo "Template path: " . $templatePath . "\n";
    echo "Template exists: " . (file_exists($templatePath) ? "YES" : "NO") . "\n";
    
    if (!file_exists($templatePath)) {
        echo "ERROR: Template file not found!\n";
        exit;
    }
    
    // Try to generate PDF
    require_once 'vendor/autoload.php';
    
    echo "Creating mPDF instance...\n";
    
    $mpdf = new \Mpdf\Mpdf([
        'mode' => 'utf-8',
        'format' => 'A4',
        'tempDir' => sys_get_temp_dir()
    ]);
    
    echo "mPDF instance created successfully\n";
    
    // Test basic HTML
    $basicHtml = '<html><body>Test PDF</body></html>';
    $mpdf->WriteHTML($basicHtml);
    
    $filename = 'test_invoice_' . $invoice_id . '.pdf';
    $mpdf->Output($filename, 'F'); // Save to file
    
    echo "PDF generated: " . $filename . "\n";
    
    // Check if file was created
    if (file_exists($filename)) {
        echo "File size: " . filesize($filename) . " bytes\n";
        echo "SUCCESS!\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>
