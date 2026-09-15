<?php

declare(strict_types=1);

$vendorAutoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Excel column headers for Dispatch Orders Manifest
 *
 * @return list<string>
 */
function bulkDispatchExcelColumnHeaders(): array
{
    return [
        'Batch No',
        'Order Number',
        'Invoice Number',
        'Invoice Date',
        'Customer ID',
        'Customer Name',
        'Shipping Name',
        'Address Line 1',
        'Address Line 2',
        'City',
        'State',
        'Country',
        'Pincode',
        'Mobile / Phone',
        'Email',
        'Item Code / SKU',
        'Item Description / Title',
        'Quantity',
        'Pretax Unit Price',
        'GST Rate (%)',
        'Line Total (Incl. GST)',
        'Payment Mode',
        'Box No',
        'Box Size',
        'Weight (kg)',
        'Dimensions (LxWxH cm)',
        'Courier Partner',
    ];
}

/**
 * Generate and stream an Excel (.xlsx) file for a set of dispatch rows / order line items.
 *
 * @param list<array<string, mixed>> $exportRows
 * @param string $filename
 * @return void
 */
function generateBulkDispatchExcel(array $exportRows, string $filename = 'bulk_dispatch_manifest.xlsx'): void
{
    if (ob_get_length()) {
        ob_end_clean();
    }

    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    $sheet->setTitle('Dispatch Orders');

    $headers = bulkDispatchExcelColumnHeaders();
    $colCount = count($headers);

    // Header styling
    $headerRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . '1';
    $sheet->fromArray([$headers], null, 'A1');

    $sheet->getStyle($headerRange)->getFont()->setBold(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFFFFF'));
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F2937'); // Dark slate
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
    $sheet->getRowDimension(1)->setRowHeight(28);

    $rowIndex = 2;
    foreach ($exportRows as $row) {
        $data = [
            (string)($row['batch_no'] ?? ''),
            (string)($row['order_number'] ?? ''),
            (string)($row['invoice_number'] ?? ''),
            (string)($row['invoice_date'] ?? date('Y-m-d')),
            (string)($row['customer_id'] ?? ''),
            (string)($row['customer_name'] ?? ''),
            (string)($row['shipping_name'] ?? ''),
            (string)($row['address1'] ?? ''),
            (string)($row['address2'] ?? ''),
            (string)($row['city'] ?? ''),
            (string)($row['state'] ?? ''),
            (string)($row['country'] ?? ''),
            (string)($row['zipcode'] ?? ''),
            (string)($row['phone'] ?? ''),
            (string)($row['email'] ?? ''),
            (string)($row['item_code'] ?? ''),
            (string)($row['title'] ?? ''),
            (int)($row['quantity'] ?? 1),
            number_format((float)($row['unit_price'] ?? 0), 2, '.', ''),
            number_format((float)($row['gst_rate'] ?? 0), 2, '.', ''),
            number_format((float)($row['line_total'] ?? 0), 2, '.', ''),
            (string)($row['payment_mode'] ?? 'Prepaid'),
            (string)($row['box_no'] ?? '1'),
            (string)($row['box_size'] ?? ''),
            number_format((float)($row['weight'] ?? 0), 2, '.', ''),
            (string)($row['dimensions'] ?? ''),
            (string)($row['courier_company_id'] ?? $row['courier_name'] ?? ''),
        ];

        $sheet->fromArray([$data], null, 'A' . $rowIndex);
        $sheet->getRowDimension($rowIndex)->setRowHeight(20);
        $rowIndex++;
    }

    $lastRow = max(2, $rowIndex - 1);
    $fullRange = 'A1:' . Coordinate::stringFromColumnIndex($colCount) . $lastRow;

    // Apply thin borders to all data cells
    $sheet->getStyle($fullRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setARGB('D1D5DB');

    // Auto-size columns
    for ($col = 1; $col <= $colCount; $col++) {
        $colLetter = Coordinate::stringFromColumnIndex($col);
        $sheet->getColumnDimension($colLetter)->setAutoSize(true);
    }

    // Set headers for download
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . rawurlencode($filename) . '"');
    header('Cache-Control: max-age=0');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
    header('Cache-Control: cache, must-revalidate');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
