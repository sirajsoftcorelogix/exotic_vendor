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
require_once $root . '/models/comman/comman.php';
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
$commanModel = new Comman($conn);
$creationService = new InvoiceCreationService($conn, $invoiceModel, $ordersModel, $commanModel);

$processedCount = 0;
$successCount = 0;
$failedCount = 0;

while ($processedCount < $limit) {
    $item = $bulkBatchModel->lockNextPendingItem($batchIdFilter);
    if (!$item) {
        break; // No more pending items
    }

    $itemId = (int)$item['id'];
    $batchId = (int)$item['batch_id'];
    $customerId = (int)$item['customer_id'];
    $orderItemIds = json_decode((string)$item['order_item_ids'], true);

    if (!is_array($orderItemIds) || empty($orderItemIds)) {
        $bulkBatchModel->markItemFailed($itemId, "No order item IDs provided for customer #{$customerId}");
        $failedCount++;
        $processedCount++;
        continue;
    }

    try {
        // Fetch order rows from vp_orders
        $orderLines = $ordersModel->getOrdersByIds($orderItemIds);
        if (empty($orderLines)) {
            $bulkBatchModel->markItemFailed($itemId, "Order lines not found for IDs: " . implode(', ', $orderItemIds));
            $failedCount++;
            $processedCount++;
            continue;
        }

        // Get main batch header info for batch_no reference
        $batchHeader = $bulkBatchModel->getBatchById($batchId);
        $batchNo = $batchHeader ? (string)$batchHeader['batch_no'] : '';

        // Determine order_info & IGST vs CGST/SGST usage
        $firstOrderNo = (string)($orderLines[0]['order_number'] ?? '');
        $orderInfo = $ordersModel->getRemarksByOrderNumber($firstOrderNo);
        $useIgst = invoice_order_info_uses_igst(is_array($orderInfo) ? $orderInfo : null, app_setting_firm_details());
        $vpOrderInfoId = (is_array($orderInfo) && isset($orderInfo['id'])) ? (int)$orderInfo['id'] : 0;

        // Build invoice creation request
        $headerOverrides = [
            'customer_id' => $customerId,
            'vp_order_info_id' => $vpOrderInfoId,
            'batch_no' => $batchNo,
            'created_by' => (int)($batchHeader['created_by'] ?? 0),
        ];

        $request = InvoiceRequestBuilder::fromOrderLines(
            $orderLines,
            $headerOverrides,
            [
                'source' => 'bulk_background',
                'use_igst' => $useIgst,
                'duplicate_order_check' => true,
                'update_order_invoice_id' => true
            ]
        );

        // Execute invoice creation
        $res = $creationService->create($request);

        if (!empty($res['success']) && !empty($res['invoice_id'])) {
            $invoiceId = (int)$res['invoice_id'];
            $invoiceNumber = (string)($res['invoice_number'] ?? '');
            $amount = (float)($res['total_amount'] ?? $request['header']['total_amount'] ?? 0.0);

            $bulkBatchModel->markItemCompleted($itemId, $invoiceId, $invoiceNumber, $amount);
            $successCount++;
        } else {
            $errMsg = (string)($res['message'] ?? 'Invoice creation failed with unknown error.');
            $bulkBatchModel->markItemFailed($itemId, $errMsg);
            $failedCount++;
        }
    } catch (Throwable $e) {
        $bulkBatchModel->markItemFailed($itemId, $e->getMessage());
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
