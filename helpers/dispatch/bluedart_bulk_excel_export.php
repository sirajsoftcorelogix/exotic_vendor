<?php

declare(strict_types=1);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../courier/bluedart_rate_helpers.php';

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Column headers aligned with Blue Dart bulk waybill / ImportData Excel import.
 *
 * @return list<string>
 */
function bluedartBulkExcelColumnHeaders(): array
{
    return [
        'ConsigneeName',
        'ConsigneeAddress1',
        'ConsigneeAddress2',
        'ConsigneeAddress3',
        'ConsigneePincode',
        'ConsigneeMobile',
        'ConsigneeTelephone',
        'ConsigneeEmailID',
        'ProductCode',
        'SubProductCode',
        'PieceCount',
        'ActualWeight',
        'PackType',
        'DeclaredValue',
        'CollactableAmount',
        'CreditReferenceNo',
        'InvoiceNo',
        'Length',
        'Breadth',
        'Height',
        'PickupDate',
        'PickupTime',
        'RegisterPickup',
        'PortalOrderNo',
        'PortalBoxNo',
        'ServiceLabel',
    ];
}

/**
 * @param array<string, mixed> $orderInfo
 * @return array<string, string|int|float>
 */
function bluedartBulkExcelBuildRow(array $box, array $orderInfo, int $boxNo): array
{
    $firstName = trim((string) ($orderInfo['shipping_first_name'] ?? $orderInfo['first_name'] ?? ''));
    $lastName = trim((string) ($orderInfo['shipping_last_name'] ?? $orderInfo['last_name'] ?? ''));
    $consigneeName = trim($firstName . ' ' . $lastName);
    if ($consigneeName === '') {
        $consigneeName = trim((string) ($box['customer_name'] ?? 'Customer'));
    }
    $consigneeName = substr($consigneeName, 0, 30);

    $addressLines = bluedartBulkExcelSplitAddress(
        trim((string) ($orderInfo['shipping_address_line1'] ?? '')),
        trim((string) ($orderInfo['shipping_address_line2'] ?? '')),
        trim((string) ($orderInfo['shipping_city'] ?? '')),
        trim((string) ($orderInfo['shipping_state'] ?? ''))
    );

    $pincode = preg_replace('/\D/', '', (string) ($orderInfo['shipping_zipcode'] ?? '')) ?? '';
    $mobile = preg_replace('/\D/', '', (string) ($orderInfo['shipping_mobile'] ?? $orderInfo['mobile'] ?? '')) ?? '';
    if (strlen($mobile) > 10) {
        $mobile = substr($mobile, -10);
    }

    $metadata = is_array($box['metadata'] ?? null) ? $box['metadata'] : [];
    $productCode = strtoupper(trim((string) ($metadata['product_code'] ?? '')));
    $subProductCode = strtoupper(trim((string) ($metadata['sub_product_code'] ?? '')));
    $packType = strtoupper(trim((string) ($metadata['pack_type'] ?? 'L')));
    if ($packType === '') {
        $packType = 'L';
    }

    $productType = trim((string) ($box['product_type'] ?? ''));
    $courierId = trim((string) ($box['courier_id'] ?? ''));
    if ($productCode === '' && preg_match('/^bluedart_\d+_([A-Z])_([PC])$/i', $courierId, $m)) {
        $productCode = strtoupper($m[1]);
        $subProductCode = strtoupper($m[2]);
    }
    if ($productCode === '' && preg_match('/^([A-Z])_([PC])$/', strtoupper($productType), $m)) {
        $productCode = $m[1];
        $subProductCode = $m[2];
    }

    $isCod = !empty($box['is_cod']);
    if ($subProductCode === '' || !in_array($subProductCode, ['P', 'C'], true)) {
        $subProductCode = $isCod ? 'C' : 'P';
    }
    if ($productCode === '' || strlen($productCode) !== 1) {
        $productCode = 'A';
    }

    $serviceLabel = trim((string) ($box['courier_name'] ?? ''));
    if ($serviceLabel === '') {
        $serviceLabel = 'Blue Dart ' . $productCode . '/' . $subProductCode;
    }

    $weightKg = max(0.01, (float) ($box['weight'] ?? 0));
    $declaredValue = round(max(0.01, (float) ($box['declared_value'] ?? 0)), 2);
    $collectable = $isCod ? $declaredValue : 0.0;

    $orderNumber = preg_replace('/\D/', '', (string) ($box['order_number'] ?? ''));
    $creditRef = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($box['credit_reference'] ?? '')));
    if ($creditRef === '') {
        $creditRef = $orderNumber;
        if ($boxNo > 0) {
            $creditRef .= 'B' . $boxNo;
        }
        $creditRef = substr($creditRef . substr((string) time(), -4), 0, 20);
    } else {
        $creditRef = substr($creditRef, 0, 20);
    }

    $lengthCm = max(1.0, (float) ($box['length_cm'] ?? 1));
    $breadthCm = max(1.0, (float) ($box['width_cm'] ?? 1));
    $heightCm = max(1.0, (float) ($box['height_cm'] ?? 1));

    return [
        'ConsigneeName' => $consigneeName,
        'ConsigneeAddress1' => $addressLines[0],
        'ConsigneeAddress2' => $addressLines[1],
        'ConsigneeAddress3' => $addressLines[2],
        'ConsigneePincode' => substr($pincode, 0, 6),
        'ConsigneeMobile' => $mobile,
        'ConsigneeTelephone' => '',
        'ConsigneeEmailID' => substr(trim((string) ($orderInfo['shipping_email'] ?? $orderInfo['email'] ?? '')), 0, 70),
        'ProductCode' => $productCode,
        'SubProductCode' => $subProductCode,
        'PieceCount' => max(1, (int) ($box['piece_count'] ?? 1)),
        'ActualWeight' => number_format($weightKg, 2, '.', ''),
        'PackType' => $packType,
        'DeclaredValue' => $declaredValue,
        'CollactableAmount' => $collectable,
        'CreditReferenceNo' => $creditRef,
        'InvoiceNo' => substr(trim((string) ($box['invoice_number'] ?? '')), 0, 20),
        'Length' => round($lengthCm, 2),
        'Breadth' => round($breadthCm, 2),
        'Height' => round($heightCm, 2),
        'PickupDate' => date('d/m/Y'),
        'PickupTime' => date('Hi'),
        'RegisterPickup' => 'false',
        'PortalOrderNo' => (string) ($box['order_number'] ?? ''),
        'PortalBoxNo' => $boxNo > 0 ? (string) $boxNo : '1',
        'ServiceLabel' => $serviceLabel,
    ];
}

/**
 * @return list<string>
 */
function bluedartBulkExcelSplitAddress(string $line1, string $line2, string $city, string $state): array
{
    $parts = [];
    if ($line1 !== '') {
        $parts[] = $line1;
    }
    if ($line2 !== '') {
        $parts[] = $line2;
    }
    $tail = trim($city . ($city !== '' && $state !== '' ? ', ' : '') . $state);
    if ($tail !== '') {
        $parts[] = $tail;
    }

    $lines = ['', '', ''];
    $idx = 0;
    foreach ($parts as $part) {
        $remaining = substr($part, 0, 90);
        while ($remaining !== '' && $idx < 3) {
            $lines[$idx] = substr($remaining, 0, 30);
            $remaining = substr($remaining, 30);
            $idx++;
        }
    }

    return $lines;
}

function bluedartBulkExcelSheetName(string $productCode, string $serviceLabel, string $productType): string
{
    $productCode = strtoupper(trim($productCode));
    $haystack = strtolower($serviceLabel . ' ' . $productType);

    if ($productCode === 'E' || preg_match('/\b(surface|ground)\b/i', $haystack)) {
        return 'BlueDart Surface';
    }
    if ($productCode === 'A' || $productCode === 'D' || preg_match('/\b(air|apex|priority)\b/i', $haystack)) {
        return 'BlueDart Air';
    }

    return 'BlueDart Surface';
}

/**
 * @param list<array<string, mixed>> $boxes
 * @return array{success:bool,message?:string,sheets?:array<string, list<array<string, string|int|float>>>,skipped?:list<string>}
 */
function bluedartBulkExcelPrepareSheets(array $boxes, callable $orderInfoResolver): array
{
    $sheets = [
        'BlueDart Air' => [],
        'BlueDart Surface' => [],
    ];
    $skipped = [];

    foreach ($boxes as $box) {
        if (!is_array($box)) {
            continue;
        }

        $orderNumber = trim((string) ($box['order_number'] ?? ''));
        if ($orderNumber === '') {
            $skipped[] = 'Skipped a box with no order number.';
            continue;
        }

        $orderInfo = $orderInfoResolver($orderNumber);
        if (!is_array($orderInfo) || $orderInfo === []) {
            $skipped[] = 'Order #' . $orderNumber . ' not found in database.';
            continue;
        }

        $boxNo = (int) ($box['box_no'] ?? 0);
        $row = bluedartBulkExcelBuildRow($box, $orderInfo, $boxNo);
        $sheetName = bluedartBulkExcelSheetName(
            (string) ($row['ProductCode'] ?? 'A'),
            (string) ($row['ServiceLabel'] ?? ''),
            (string) ($box['product_type'] ?? '')
        );
        $sheets[$sheetName][] = $row;
    }

    $totalRows = count($sheets['BlueDart Air']) + count($sheets['BlueDart Surface']);
    if ($totalRows === 0) {
        return [
            'success' => false,
            'message' => 'No Blue Dart rows to export. Select Blue Dart on at least one box with items.',
            'skipped' => $skipped,
        ];
    }

    foreach (array_keys($sheets) as $sheetName) {
        if ($sheets[$sheetName] === []) {
            unset($sheets[$sheetName]);
        }
    }

    return [
        'success' => true,
        'sheets' => $sheets,
        'skipped' => $skipped,
    ];
}

/**
 * @param array<string, list<array<string, string|int|float>>> $sheets
 */
function bluedartBulkExcelStreamDownload(array $sheets, string $filename): void
{
    if ($sheets === []) {
        throw new RuntimeException('No Blue Dart sheets to export.');
    }

    $spreadsheet = new Spreadsheet();
    $headers = bluedartBulkExcelColumnHeaders();
    $sheetIndex = 0;

    foreach ($sheets as $sheetName => $rows) {
        $worksheet = $sheetIndex === 0
            ? $spreadsheet->getActiveSheet()
            : $spreadsheet->createSheet();
        $safeTitle = substr(preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '', $sheetName) ?? 'Sheet', 0, 31);
        $worksheet->setTitle($safeTitle !== '' ? $safeTitle : 'Sheet');

        foreach ($headers as $colIndex => $header) {
            $col = Coordinate::stringFromColumnIndex($colIndex + 1);
            $worksheet->setCellValue($col . '1', $header);
        }

        $rowNum = 2;
        foreach ($rows as $row) {
            foreach ($headers as $colIndex => $header) {
                $col = Coordinate::stringFromColumnIndex($colIndex + 1);
                $worksheet->setCellValue($col . (string) $rowNum, $row[$header] ?? '');
            }
            $rowNum++;
        }

        $worksheet->freezePane('A2');
        $sheetIndex++;
    }

    $spreadsheet->setActiveSheetIndex(0);

    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (headers_sent()) {
        throw new RuntimeException('Export response headers were already sent.');
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: max-age=0');
    header('Pragma: public');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}
