<?php
/**
 * Background Queue Processor for Bulk Multi-Customer Invoices
 *
 * Usage via CLI:
 *   php scripts/process_bulk_invoice_queue.php
 *   php scripts/process_bulk_invoice_queue.php --batch-id=5
 *   php scripts/process_bulk_invoice_queue.php --limit=10
 */

declare(strict_types=1);

$isCli = PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg';
$root = dirname(__DIR__);

require_once $root . '/config.php';
require_once $root . '/helpers/html_helpers.php';
require_once $root . '/models/invoice/invoice.php';
require_once $root . '/models/invoice/BulkInvoiceBatch.php';
require_once $root . '/models/order/order.php';
require_once $root . '/models/comman/tables.php';
require_once $root . '/helpers/invoice/InvoiceRequestBuilder.php';
require_once $root . '/helpers/invoice/InvoiceCreationService.php';
require_once $root . '/helpers/invoice/invoice_gst.php';
require_once $root . '/helpers/app_settings.php';

global $conn;
if (!$conn) {
    if ($isCli) {
        fwrite(STDERR, "Database connection failed.\n");
        exit(1);
    }
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit(1);
}

// Parse CLI options
$batchIdFilter = 0;
$limit = 50;

if ($isCli) {
    foreach ($argv as $arg) {
        if (strpos($arg, '--batch-id=') === 0) {
            $batchIdFilter = (int)substr($arg, 11);
        } elseif (strpos($arg, '--limit=') === 0) {
            $limit = max(1, (int)substr($arg, 8));
        }
    }
} else {
    if (isset($_GET['batch_id'])) {
        $batchIdFilter = (int)$_GET['batch_id'];
    }
    if (isset($_GET['limit'])) {
        $limit = max(1, (int)$_GET['limit']);
    }
}

$bulkBatchModel = new BulkInvoiceBatch($conn);
$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$commanModel = new Tables($conn);
$creationService = new InvoiceCreationService($conn, $invoiceModel, $ordersModel, $commanModel);

$processedCount = 0;
$successCount = 0;
$failedCount = 0;

while ($processedCount < $limit) {
    $res = $bulkBatchModel->processNextPendingItem($batchIdFilter, $creationService, $ordersModel);
    if ($res === null) {
        break; // No more pending items
    }
    if (!empty($res['success'])) {
        $successCount++;
    } else {
        $failedCount++;
    }
    $processedCount++;
}

$summary = [
    'success' => true,
    'processed' => $processedCount,
    'successful' => $successCount,
    'failed' => $failedCount,
    'batch_id' => $batchIdFilter,
];

if ($isCli) {
    echo sprintf("Processed: %d, Success: %d, Failed: %d\n", $processedCount, $successCount, $failedCount);
} else {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($summary);
}
