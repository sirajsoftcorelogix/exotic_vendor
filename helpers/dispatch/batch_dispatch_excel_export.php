<?php

declare(strict_types=1);

function ensure_vendor_autoloader(): void
{
    static $loaded = false;
    if ($loaded) {
        return;
    }

    $autoloader = dirname(__DIR__, 2) . '/vendor/autoload.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
        $loaded = true;
    }
}

ensure_vendor_autoloader();

require_once __DIR__ . '/bulk_dispatch_excel_export.php';
require_once __DIR__ . '/bluedart_bulk_excel_export.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Fetch and shape all dispatch rows for a given batch ID or list of item IDs.
 *
 * @param mysqli $conn
 * @param int $batchId
 * @return list<array<string, mixed>>
 */
function fetchBatchDispatchRows(mysqli $conn, int $batchId): array
{
    require_once __DIR__ . '/../../models/invoice/BulkInvoiceBatch.php';
    require_once __DIR__ . '/../../models/order/order.php';
    require_once __DIR__ . '/../../models/invoice/invoice.php';
    require_once __DIR__ . '/../../models/comman/tables.php';

    $bulkBatchModel = new BulkInvoiceBatch($conn);
    $ordersModel = new Order($conn);
    $invoiceModel = new Invoice($conn);
    $commanModel = new Tables($conn);

    $batch = $bulkBatchModel->getBatchById($batchId);
    if (!$batch) {
        return [];
    }

    $batchItems = $bulkBatchModel->getBatchItems($batchId);
    $exportRows = [];

    foreach ($batchItems as $bItem) {
        $itemIds = json_decode((string)($bItem['order_item_ids'] ?? '[]'), true);
        if (!is_array($itemIds) || empty($itemIds)) continue;

        $orderLines = $ordersModel->getOrdersByIds($itemIds);
        if (empty($orderLines)) continue;

        $firstOrderNo = (string)($orderLines[0]['order_number'] ?? '');
        $orderInfo = $ordersModel->getRemarksByOrderNumber($firstOrderNo);
        if (!is_array($orderInfo)) {
            $orderInfo = [];
        }

        $invId = (int)($bItem['invoice_id'] ?? 0);
        $invoice = $invId > 0 ? $invoiceModel->getInvoiceById($invId) : null;
        $invNo = (string)($bItem['invoice_number'] ?? ($invoice['invoice_number'] ?? ''));
        $invDate = (string)($invoice['invoice_date'] ?? date('Y-m-d'));

        $firstName = trim((string)($orderInfo['shipping_first_name'] ?? $orderInfo['first_name'] ?? ''));
        $lastName = trim((string)($orderInfo['shipping_last_name'] ?? $orderInfo['last_name'] ?? ''));
        $shippingName = trim($firstName . ' ' . $lastName);
        if ($shippingName === '') {
            $shippingName = (string)($bItem['customer_name'] ?? 'Customer');
        }

        $address1 = trim((string)($orderInfo['shipping_address_line1'] ?? $orderInfo['address_line1'] ?? ''));
        $address2 = trim((string)($orderInfo['shipping_address_line2'] ?? $orderInfo['address_line2'] ?? ''));
        $city = trim((string)($orderInfo['shipping_city'] ?? $orderInfo['city'] ?? ''));
        $state = trim((string)($orderInfo['shipping_state'] ?? $orderInfo['state'] ?? ''));
        $zipcode = trim((string)($orderInfo['shipping_zipcode'] ?? $orderInfo['zipcode'] ?? ''));
        $phone = trim((string)($orderInfo['shipping_mobile'] ?? $orderInfo['mobile'] ?? ''));
        $email = trim((string)($orderInfo['shipping_email'] ?? $orderInfo['email'] ?? ''));
        $country = trim((string)($orderInfo['shipping_country'] ?? $orderInfo['country'] ?? 'India'));

        $paymentMode = 'Prepaid';
        if (!empty($orderInfo['payment_mode']) && strtolower(trim((string)$orderInfo['payment_mode'])) === 'cod') {
            $paymentMode = 'COD';
        }

        foreach ($orderLines as $line) {
            $qty = max(1, (int)($line['quantity'] ?? 1));
            $unitPrice = (float)($line['itemprice'] ?? 0);
            $gstRate = (float)($line['gst'] ?? 0);
            $lineTotal = (float)($line['finalprice'] ?? ($unitPrice * $qty));

            $exportRows[] = [
                'batch_no' => $batch['batch_no'],
                'order_number' => (string)($line['order_number'] ?? ''),
                'invoice_id' => $invId,
                'invoice_number' => $invNo,
                'invoice_date' => $invDate,
                'customer_id' => (string)$bItem['customer_id'],
                'customer_name' => (string)($bItem['customer_name'] ?? $shippingName),
                'shipping_name' => $shippingName,
                'shipping_first_name' => $firstName ?: $shippingName,
                'shipping_last_name' => $lastName,
                'address1' => $address1,
                'address2' => $address2,
                'city' => $city,
                'state' => $state,
                'country' => $country,
                'zipcode' => $zipcode,
                'phone' => $phone,
                'email' => $email,
                'item_code' => (string)($line['item_code'] ?? ''),
                'title' => (string)($line['title'] ?? 'Product'),
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'gst_rate' => $gstRate,
                'line_total' => $lineTotal,
                'invoice_total' => (float)($bItem['invoice_amount'] ?? $lineTotal),
                'payment_mode' => $paymentMode,
                'box_no' => '1',
                'box_size' => 'R-1',
                'weight' => 0.50,
                'dimensions' => '22x17x5 cm',
                'courier_name' => '',
            ];
        }
    }

    return $exportRows;
}

/**
 * Export Batch Manifest to Delhivery Excel format.
 *
 * @param list<array<string, mixed>> $exportRows
 * @param string $filename
 * @return void
 */
function exportBatchToDelhiveryExcel(array $exportRows, string $filename = 'delhivery_manifest.xlsx'): void
{
    ensure_vendor_autoloader();

    $headers = [
        'Waybill',
        'Order No',
        'Consignee Name',
        'Consignee Address 1',
        'Consignee Address 2',
        'Consignee City',
        'Consignee State',
        'Consignee Pincode',
        'Consignee Mobile',
        'Payment Mode',
        'COD Amount',
        'Declared Value',
        'Weight (g)',
        'Length (cm)',
        'Width (cm)',
        'Height (cm)',
        'Item Description',
        'Quantity',
        'Invoice No',
        'Invoice Date'
    ];

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        if (ob_get_length()) ob_end_clean();
        $csvFilename = str_replace('.xlsx', '.csv', $filename);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($csvFilename) . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);

        foreach ($exportRows as $row) {
            $isCod = strtoupper((string)($row['payment_mode'] ?? '')) === 'COD';
            $invTotal = (float)($row['invoice_total'] ?? $row['line_total'] ?? 0);
            $codAmt = $isCod ? $invTotal : 0.00;

            fputcsv($out, [
                '',
                (string)($row['order_number'] ?? ''),
                (string)($row['shipping_name'] ?? ''),
                (string)($row['address1'] ?? ''),
                (string)($row['address2'] ?? ''),
                (string)($row['city'] ?? ''),
                (string)($row['state'] ?? ''),
                (string)($row['zipcode'] ?? ''),
                (string)($row['phone'] ?? ''),
                $isCod ? 'COD' : 'Prepaid',
                number_format($codAmt, 2, '.', ''),
                number_format($invTotal, 2, '.', ''),
                '500',
                '22',
                '17',
                '5',
                (string)($row['title'] ?? $row['item_code'] ?? ''),
                (int)($row['quantity'] ?? 1),
                (string)($row['invoice_number'] ?? ''),
                (string)($row['invoice_date'] ?? date('Y-m-d')),
            ]);
        }
        fclose($out);
        exit;
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Delhivery Manifest');

    $colCount = count($headers);
    $headerRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . '1';
    $sheet->fromArray([$headers], null, 'A1');

    $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF047857');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(28);

    $rowIndex = 2;
    foreach ($exportRows as $row) {
        $isCod = strtoupper((string)($row['payment_mode'] ?? '')) === 'COD';
        $invTotal = (float)($row['invoice_total'] ?? $row['line_total'] ?? 0);
        $codAmt = $isCod ? $invTotal : 0.00;

        $data = [
            '',
            (string)($row['order_number'] ?? ''),
            (string)($row['shipping_name'] ?? ''),
            (string)($row['address1'] ?? ''),
            (string)($row['address2'] ?? ''),
            (string)($row['city'] ?? ''),
            (string)($row['state'] ?? ''),
            (string)($row['zipcode'] ?? ''),
            (string)($row['phone'] ?? ''),
            $isCod ? 'COD' : 'Prepaid',
            number_format($codAmt, 2, '.', ''),
            number_format($invTotal, 2, '.', ''),
            '500',
            '22',
            '17',
            '5',
            (string)($row['title'] ?? $row['item_code'] ?? ''),
            (int)($row['quantity'] ?? 1),
            (string)($row['invoice_number'] ?? ''),
            (string)($row['invoice_date'] ?? date('Y-m-d')),
        ];

        $sheet->fromArray([$data], null, 'A' . $rowIndex);
        $sheet->getRowDimension($rowIndex)->setRowHeight(20);
        $rowIndex++;
    }

    $lastRow = max(2, $rowIndex - 1);
    $fullRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . $lastRow;
    $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('D1D5DB');

    for ($col = 1; $col <= $colCount; $col++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Export Batch Manifest to Shiprocket Excel format.
 *
 * @param list<array<string, mixed>> $exportRows
 * @param string $filename
 * @return void
 */
function exportBatchToShiprocketExcel(array $exportRows, string $filename = 'shiprocket_manifest.xlsx'): void
{
    ensure_vendor_autoloader();

    $headers = [
        'Order ID',
        'Order Date',
        'Channel',
        'Payment Method',
        'Product Name',
        'Product SKU',
        'Quantity',
        'Unit Price',
        'Tax Rate (%)',
        'Discount Amount',
        'First Name',
        'Last Name',
        'Email',
        'Phone',
        'Address Line 1',
        'Address Line 2',
        'City',
        'State',
        'Pincode',
        'Country',
        'Package Weight (kg)',
        'Package Length (cm)',
        'Package Width (cm)',
        'Package Height (cm)'
    ];

    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        if (ob_get_length()) ob_end_clean();
        $csvFilename = str_replace('.xlsx', '.csv', $filename);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . rawurlencode($csvFilename) . '"');
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);

        foreach ($exportRows as $row) {
            $isCod = strtoupper((string)($row['payment_mode'] ?? '')) === 'COD';

            fputcsv($out, [
                (string)($row['order_number'] ?? ''),
                (string)($row['invoice_date'] ?? date('Y-m-d')),
                'Custom',
                $isCod ? 'COD' : 'Prepaid',
                (string)($row['title'] ?? ''),
                (string)($row['item_code'] ?? ''),
                (int)($row['quantity'] ?? 1),
                number_format((float)($row['unit_price'] ?? 0), 2, '.', ''),
                number_format((float)($row['gst_rate'] ?? 0), 2, '.', ''),
                '0.00',
                (string)($row['shipping_first_name'] ?? ''),
                (string)($row['shipping_last_name'] ?? ''),
                (string)($row['email'] ?? ''),
                (string)($row['phone'] ?? ''),
                (string)($row['address1'] ?? ''),
                (string)($row['address2'] ?? ''),
                (string)($row['city'] ?? ''),
                (string)($row['state'] ?? ''),
                (string)($row['zipcode'] ?? ''),
                (string)($row['country'] ?? 'India'),
                '0.50',
                '22',
                '17',
                '5',
            ]);
        }
        fclose($out);
        exit;
    }

    if (ob_get_length()) {
        ob_end_clean();
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Shiprocket Import');

    $colCount = count($headers);
    $headerRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . '1';
    $sheet->fromArray([$headers], null, 'A1');

    $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD97706');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(28);

    $rowIndex = 2;
    foreach ($exportRows as $row) {
        $isCod = strtoupper((string)($row['payment_mode'] ?? '')) === 'COD';

        $data = [
            (string)($row['order_number'] ?? ''),
            (string)($row['invoice_date'] ?? date('Y-m-d')),
            'Custom',
            $isCod ? 'COD' : 'Prepaid',
            (string)($row['title'] ?? ''),
            (string)($row['item_code'] ?? ''),
            (int)($row['quantity'] ?? 1),
            number_format((float)($row['unit_price'] ?? 0), 2, '.', ''),
            number_format((float)($row['gst_rate'] ?? 0), 2, '.', ''),
            '0.00',
            (string)($row['shipping_first_name'] ?? ''),
            (string)($row['shipping_last_name'] ?? ''),
            (string)($row['email'] ?? ''),
            (string)($row['phone'] ?? ''),
            (string)($row['address1'] ?? ''),
            (string)($row['address2'] ?? ''),
            (string)($row['city'] ?? ''),
            (string)($row['state'] ?? ''),
            (string)($row['zipcode'] ?? ''),
            (string)($row['country'] ?? 'India'),
            '0.50',
            '22',
            '17',
            '5',
        ];

        $sheet->fromArray([$data], null, 'A' . $rowIndex);
        $sheet->getRowDimension($rowIndex)->setRowHeight(20);
        $rowIndex++;
    }

    $lastRow = max(2, $rowIndex - 1);
    $fullRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . $lastRow;
    $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('D1D5DB');

    for ($col = 1; $col <= $colCount; $col++) {
        $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($col))->setAutoSize(true);
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: max-age=0');
    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
