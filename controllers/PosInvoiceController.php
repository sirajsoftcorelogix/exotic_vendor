<?php
require_once 'models/PosInvoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/customer/Customer.php';
require_once 'models/product/product.php';
require_once __DIR__ . '/../models/payment/Payment.php';
require_once __DIR__ . '/../helpers/invoice/pos_order_pricing.php';
// Register in $GLOBALS so methods work when this file is required from a function scope (e.g. payments → create invoice).
global $conn;
$GLOBALS['invoiceModel'] = $GLOBALS['invoiceModel'] ?? new POSInvoice($conn);
$GLOBALS['ordersModel'] = $GLOBALS['ordersModel'] ?? new Order($conn);
$GLOBALS['usersModel'] = $GLOBALS['usersModel'] ?? new User($conn);
$GLOBALS['commanModel'] = $GLOBALS['commanModel'] ?? new Tables($conn);
$GLOBALS['paymentModel'] = $GLOBALS['paymentModel'] ?? new Payment($conn);
$invoiceModel = $GLOBALS['invoiceModel'];
$ordersModel = $GLOBALS['ordersModel'];
$usersModel = $GLOBALS['usersModel'];
$commanModel = $GLOBALS['commanModel'];
$paymentModel = $GLOBALS['paymentModel'];
class PosInvoiceController
{

    private function isPosInvoiceAdminUser(): bool
    {
        return isset($_SESSION['user']['role_id']) && (int) $_SESSION['user']['role_id'] === 1;
    }

    private function getSessionWarehouseId(): int
    {
        $warehouseId = (int) ($_SESSION['warehouse_id'] ?? 0);
        if ($warehouseId <= 0 && !empty($_SESSION['user']['warehouse_id'])) {
            $warehouseId = (int) $_SESSION['user']['warehouse_id'];
        }

        return $warehouseId;
    }

    /* ===============================
       PAGE LOAD
    =============================== */
    public function index()
    {
        global $conn, $usersModel;

        $customerModel = new Customer($conn);

        $customers = $customerModel->getAllCustomers(1000, 0, []);
        $isAdminUser = $this->isPosInvoiceAdminUser();

        renderTemplate('views/posinvoice/index.php', [
            'customers' => $customers,
            'warehouses' => $isAdminUser ? $usersModel->getAllWarehouses() : [],
            'can_change_warehouse' => $isAdminUser,
        ]);
    }

    public function userGuide()
    {
        is_login();
        renderTemplate('views/posinvoice/user_guide.php', [], 'Invoice Module — User Guide');
    }

    /* ===============================
       AJAX LIST
    =============================== */
    /** @return array<string, mixed> */
    private function resolvePosInvoiceListFiltersFromRequest(): array
    {
        $filters = [
            'order_number' => $_GET['order_number'] ?? '',
            'invoice_number' => $_GET['invoice_number'] ?? '',
            'status' => $_GET['status'] ?? '',
            'from_date' => $_GET['from_date'] ?? '',
            'to_date' => $_GET['to_date'] ?? '',
            'type' => $_GET['type'] ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'discount_applied' => $_GET['discount_applied'] ?? '',
            'warehouse_id' => null,
        ];

        if ($this->isPosInvoiceAdminUser()) {
            $selectedWarehouseId = trim((string) ($_GET['warehouse_id'] ?? ''));
            if ($selectedWarehouseId !== '' && (int) $selectedWarehouseId > 0) {
                $filters['warehouse_id'] = (int) $selectedWarehouseId;
            }
        } else {
            $filters['warehouse_id'] = $this->getSessionWarehouseId();
        }

        return $filters;
    }

    private function formatPosInvoicePaymentTypeLabel(?string $paymentType): string
    {
        $key = strtolower(trim((string) $paymentType));

        return match ($key) {
            'offline' => 'Offline',
            'cod' => 'Cash',
            'razorpay' => 'Razorpay',
            'bank_transfer' => 'Bank',
            default => $key !== '' ? ucfirst(str_replace('_', ' ', $key)) : '',
        };
    }

    public function list_ajax()
    {
        global $invoiceModel;

        is_login();

        echo json_encode($invoiceModel->searchPosListAjax($this->resolvePosInvoiceListFiltersFromRequest()));
        exit;
    }

    public function export_excel(): void
    {
        global $invoiceModel;

        is_login();

        $rows = $invoiceModel->searchPosListAjax($this->resolvePosInvoiceListFiltersFromRequest());
        if ($rows === []) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No invoices match the current filters.']);
            exit;
        }

        if (count($rows) > 5000) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Too many invoices (' . count($rows) . '). Narrow the date range or filters (max 5,000 rows).',
            ]);
            exit;
        }

        require_once 'vendor/autoload.php';

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('POS Invoices');

            $headers = [
                'ID',
                'Invoice Date',
                'Order Number',
                'Invoice Number',
                'Store / Warehouse',
                'Customer',
                'Customer State',
                'Customer Country',
                'Payment Type',
                'Amount (Net Payable)',
                'Discount Applied',
                'Discount',
                'Paid',
                'Pending',
                'Gross Amount',
                'Status',
            ];
            $sheet->fromArray($headers, null, 'A1');

            $headerRange = 'A1:P1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF3F4F6');

            $rowNum = 2;
            foreach ($rows as $row) {
                $discountAmount = round((float) ($row['discount_amount'] ?? 0), 2);
                $sheet->fromArray([
                    (int) ($row['id'] ?? 0),
                    (string) ($row['invoice_date'] ?? ''),
                    (string) ($row['order_number'] ?? ''),
                    (string) ($row['invoice_number'] ?? ''),
                    (string) ($row['warehouse_name'] ?? ''),
                    (string) ($row['customer_name'] ?? ''),
                    (string) ($row['customer_billing_state'] ?? ''),
                    (string) ($row['customer_billing_country'] ?? ''),
                    $this->formatPosInvoicePaymentTypeLabel($row['payment_type'] ?? ''),
                    round((float) ($row['payable_amount'] ?? 0), 2),
                    $discountAmount > 0.001 ? 'Yes' : 'No',
                    $discountAmount,
                    round((float) ($row['paid_amount'] ?? 0), 2),
                    round((float) ($row['pending_amount'] ?? 0), 2),
                    round((float) ($row['total_amount'] ?? 0), 2),
                    ucfirst(strtolower(trim((string) ($row['status'] ?? '')))),
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            $lastRow = max(2, $rowNum - 1);
            $moneyFormat = '#,##0.00';
            $sheet->getStyle('J2:J' . $lastRow)->getNumberFormat()->setFormatCode($moneyFormat);
            $sheet->getStyle('L2:O' . $lastRow)->getNumberFormat()->setFormatCode($moneyFormat);
            $sheet->getStyle('B2:B' . $lastRow)->getNumberFormat()->setFormatCode('yyyy-mm-dd');

            foreach (range('A', 'P') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'pos_invoices_' . date('Y-m-d_His') . '.xlsx';

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (headers_sent()) {
                throw new \RuntimeException('Export response headers were already sent.');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Could not generate Excel file. Please try again.',
            ]);
        }
        exit;
    }

    public function sales_summary(): void
    {
        global $usersModel;

        is_login();

        $isAdminUser = $this->isPosInvoiceAdminUser();
        $sessionWarehouseId = $this->getSessionWarehouseId();
        $sessionWarehouseName = '';

        if (!$isAdminUser && $sessionWarehouseId > 0) {
            $warehouse = $usersModel->getWarehouseById($sessionWarehouseId);
            $sessionWarehouseName = trim((string) ($warehouse['address_title'] ?? ''));
            if ($sessionWarehouseName === '') {
                $sessionWarehouseName = 'Warehouse #' . $sessionWarehouseId;
            }
        }

        renderTemplate('views/posinvoice/sales_summary.php', [
            'warehouses' => $isAdminUser ? $usersModel->getAllWarehouses() : [],
            'can_change_warehouse' => $isAdminUser,
            'session_warehouse_id' => $sessionWarehouseId,
            'session_warehouse_name' => $sessionWarehouseName,
        ], 'POS Sales Summary');
    }

    public function sales_summary_ajax(): void
    {
        global $invoiceModel;

        is_login();

        echo json_encode($invoiceModel->searchPosSalesSummaryByStore($this->resolvePosInvoiceListFiltersFromRequest()));
        exit;
    }

    /** @return array<string, mixed>|null */
    private function resolvePosSalesStoreDetailFiltersFromRequest(): ?array
    {
        $filters = $this->resolvePosInvoiceListFiltersFromRequest();
        $detailWarehouseId = (int) ($_GET['detail_warehouse_id'] ?? 0);

        if ($this->isPosInvoiceAdminUser()) {
            if ($detailWarehouseId <= 0) {
                $detailWarehouseId = (int) ($filters['warehouse_id'] ?? 0);
            }
        } else {
            $detailWarehouseId = $this->getSessionWarehouseId();
        }

        if ($detailWarehouseId <= 0) {
            return null;
        }

        if (!$this->isPosInvoiceAdminUser() && $detailWarehouseId !== $this->getSessionWarehouseId()) {
            return null;
        }

        $filters['warehouse_id'] = $detailWarehouseId;

        return $filters;
    }

    public function sales_store_detail_ajax(): void
    {
        global $invoiceModel;

        is_login();

        $filters = $this->resolvePosSalesStoreDetailFiltersFromRequest();
        if ($filters === null) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Store not accessible.',
                'rows' => [],
                'totals' => [
                    'invoice_count' => 0,
                    'net_sales' => 0.0,
                    'discount_total' => 0.0,
                    'collected_total' => 0.0,
                    'pending_total' => 0.0,
                    'gross_total' => 0.0,
                    'avg_ticket' => 0.0,
                ],
                'warehouse_id' => 0,
                'warehouse_name' => '',
            ]);
            exit;
        }

        echo json_encode($this->formatPosSalesStoreDetailSummaryForJson(
            $invoiceModel->searchPosSalesStoreDetailSummary($filters)
        ));
        exit;
    }

    /** @param array<string, mixed> $summary @return array<string, mixed> */
    private function formatPosSalesStoreDetailSummaryForJson(array $summary): array
    {
        if (!empty($summary['by_payment_type']['rows']) && is_array($summary['by_payment_type']['rows'])) {
            foreach ($summary['by_payment_type']['rows'] as &$row) {
                $label = $this->formatPosInvoicePaymentTypeLabel($row['group_key'] ?? '');
                $row['group_label'] = $label !== '' ? $label : 'Unknown';
            }
            unset($row);
        }

        if (!empty($summary['by_status']['rows']) && is_array($summary['by_status']['rows'])) {
            foreach ($summary['by_status']['rows'] as &$row) {
                $status = ucfirst(strtolower(trim((string) ($row['group_key'] ?? ''))));
                $row['group_label'] = $status !== '' ? $status : 'Unknown';
            }
            unset($row);
        }

        if (!empty($summary['by_discount']['rows']) && is_array($summary['by_discount']['rows'])) {
            foreach ($summary['by_discount']['rows'] as &$row) {
                $row['group_label'] = ((string) ($row['group_key'] ?? '') === '1') ? 'With discount' : 'Without discount';
            }
            unset($row);
        }

        if (!empty($summary['by_date']['rows']) && is_array($summary['by_date']['rows'])) {
            foreach ($summary['by_date']['rows'] as &$row) {
                $row['group_label'] = (string) ($row['summary_date'] ?? '');
            }
            unset($row);
        }

        return $summary;
    }

    public function sales_store_detail(): void
    {
        global $usersModel;

        is_login();

        $filters = $this->resolvePosSalesStoreDetailFiltersFromRequest();
        if ($filters === null) {
            header('Location: index.php?page=posinvoice&action=sales_summary');
            exit;
        }

        $warehouseId = (int) $filters['warehouse_id'];
        $warehouse = $usersModel->getWarehouseById($warehouseId);
        $warehouseName = trim((string) ($warehouse['address_title'] ?? ''));
        if ($warehouseName === '') {
            $warehouseName = 'Warehouse #' . $warehouseId;
        }

        renderTemplate('views/posinvoice/sales_store_detail.php', [
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'initial_filters' => [
                'from_date' => $_GET['from_date'] ?? '',
                'to_date' => $_GET['to_date'] ?? '',
                'type' => $_GET['type'] ?? '',
                'discount_applied' => $_GET['discount_applied'] ?? '',
                'status' => $_GET['status'] ?? '',
            ],
        ], 'POS Sales — ' . $warehouseName);
    }

    public function export_sales_summary(): void
    {
        global $invoiceModel;

        is_login();

        $summary = $invoiceModel->searchPosSalesSummaryByStore($this->resolvePosInvoiceListFiltersFromRequest());
        $rows = $summary['rows'] ?? [];
        if ($rows === []) {
            header('Content-Type: application/json; charset=utf-8');
            http_response_code(422);
            echo json_encode(['success' => false, 'message' => 'No sales match the current filters.']);
            exit;
        }

        require_once 'vendor/autoload.php';

        try {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('POS Sales Summary');

            $headers = [
                'Store / Warehouse',
                'Invoices',
                'Net Sales',
                'Discounts',
                'Collected',
                'Pending',
                'Gross',
                'Avg Ticket',
            ];
            $sheet->fromArray($headers, null, 'A1');

            $headerRange = 'A1:H1';
            $sheet->getStyle($headerRange)->getFont()->setBold(true);
            $sheet->getStyle($headerRange)->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF3F4F6');

            $rowNum = 2;
            foreach ($rows as $row) {
                $sheet->fromArray([
                    (string) ($row['warehouse_name'] ?? ''),
                    (int) ($row['invoice_count'] ?? 0),
                    round((float) ($row['net_sales'] ?? 0), 2),
                    round((float) ($row['discount_total'] ?? 0), 2),
                    round((float) ($row['collected_total'] ?? 0), 2),
                    round((float) ($row['pending_total'] ?? 0), 2),
                    round((float) ($row['gross_total'] ?? 0), 2),
                    round((float) ($row['avg_ticket'] ?? 0), 2),
                ], null, 'A' . $rowNum);
                $rowNum++;
            }

            $totals = $summary['totals'] ?? [];
            $sheet->fromArray([
                'TOTAL',
                (int) ($totals['invoice_count'] ?? 0),
                round((float) ($totals['net_sales'] ?? 0), 2),
                round((float) ($totals['discount_total'] ?? 0), 2),
                round((float) ($totals['collected_total'] ?? 0), 2),
                round((float) ($totals['pending_total'] ?? 0), 2),
                round((float) ($totals['gross_total'] ?? 0), 2),
                round((float) ($totals['avg_ticket'] ?? 0), 2),
            ], null, 'A' . $rowNum);

            $lastRow = $rowNum;
            $moneyFormat = '#,##0.00';
            $sheet->getStyle('C2:H' . $lastRow)->getNumberFormat()->setFormatCode($moneyFormat);
            $sheet->getStyle('A' . $lastRow . ':H' . $lastRow)->getFont()->setBold(true);

            foreach (range('A', 'H') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            $filename = 'pos_sales_summary_' . date('Y-m-d_His') . '.xlsx';

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            if (headers_sent()) {
                throw new \RuntimeException('Export response headers were already sent.');
            }

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Transfer-Encoding: binary');
            header('Cache-Control: max-age=0');
            header('Pragma: public');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        } catch (\Throwable $e) {
            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            header('Content-Type: application/json; charset=utf-8');
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Could not generate Excel file. Please try again.',
            ]);
        }
        exit;
    }

    /* ===============================
       DELETE
    =============================== */
    public function delete()
    {
        global $invoiceModel;

        $id = (int)($_POST['id'] ?? 0);
        $invoiceModel->deleteInvoice($id);

        echo json_encode(['success' => true]);
        exit;
    }

    private function emitJsonResponse(array $payload, int $statusCode = 200): void
    {
        if (ob_get_level() > 0) {
            ob_clean();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=UTF-8');
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        echo $json !== false ? $json : '{"success":false,"message":"Response encode failed"}';
        exit;
    }

    /**
     * Cancel POS invoice: cancel linked shipments (if any), restore stock, mark invoice cancelled.
     */
    public function cancelInvoice(): void
    {
        is_login();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->emitJsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
        }

        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input) || empty($input['invoice_id'])) {
            $this->emitJsonResponse(['success' => false, 'message' => 'Missing invoice_id'], 400);
        }

        global $conn, $invoiceModel, $commanModel;

        require_once __DIR__ . '/../models/dispatch/dispatch.php';
        require_once __DIR__ . '/../models/order/stock.php';

        $dispatchModel = new Dispatch($conn);
        $invoiceId = (int) $input['invoice_id'];
        $invoice = $invoiceModel->getInvoiceById($invoiceId);

        if (!$invoice) {
            $this->emitJsonResponse(['success' => false, 'message' => 'Invoice not found'], 404);
        }

        if ((int) ($invoice['pos_flag'] ?? 0) !== 1) {
            $this->emitJsonResponse(['success' => false, 'message' => 'Only POS invoices can be cancelled from this screen.'], 403);
        }

        $warehouseId = $this->getSessionWarehouseId();
        if (!$this->isPosInvoiceAdminUser() && $warehouseId > 0 && (int) ($invoice['warehouse_id'] ?? 0) !== $warehouseId) {
            $this->emitJsonResponse(['success' => false, 'message' => 'Invoice not found in your warehouse.'], 403);
        }

        $invStatus = strtolower(trim((string) ($invoice['status'] ?? '')));
        if ($invStatus === 'cancelled') {
            $this->emitJsonResponse(['success' => true, 'message' => 'Invoice already cancelled']);
        }

        $dispatchRecords = $dispatchModel->getDispatchRecordsByInvoiceId($invoiceId);

        try {
            foreach ($dispatchRecords as $record) {
                $shiprocketOrderId = $record['shiprocket_order_id'] ?? '';
                if ($shiprocketOrderId) {
                    $response = $dispatchModel->cancelShiprocketShipment($shiprocketOrderId);
                    if (empty($response['success'])) {
                        $this->emitJsonResponse([
                            'success' => false,
                            'message' => 'Failed to cancel shipment for dispatch ID ' . $record['id'] . ': ' . ($response['message'] ?? 'Unknown error'),
                        ]);
                    }
                    $commanModel->updateRecord('vp_dispatch_details', ['shipment_status' => 'cancelled'], $record['id']);
                }
            }

            $stockModel = new Stock($conn);
            $stockRestore = $stockModel->restoreStockByInvoiceId($invoiceId);
            if (empty($stockRestore['success'])) {
                $this->emitJsonResponse([
                    'success' => false,
                    'message' => 'Shipment cancelled but stock could not be restored: ' . ($stockRestore['message'] ?? 'unknown'),
                    'stock_restore' => $stockRestore,
                ], 500);
            }

            $orderCancelMeta = ['message' => ''];
            try {
                $orderCancelMeta = $this->markPosInvoiceOrdersCancelled($invoiceId);
            } catch (\Throwable $orderSyncEx) {
                error_log('[POS invoice cancel order sync] ' . $orderSyncEx->getMessage());
                $orderCancelMeta = [
                    'order_numbers' => [],
                    'local_updated' => 0,
                    'api_called' => 0,
                    'api_failed' => 0,
                    'message' => 'Invoice cancelled but order status sync failed: ' . $orderSyncEx->getMessage(),
                ];
            }

            $invoiceModel->updateInvoiceStatus($invoiceId, 'cancelled');
            $this->emitJsonResponse([
                'success' => true,
                'message' => 'Invoice cancelled successfully',
                'stock_restore' => $stockRestore,
                'order_cancel_sync' => $orderCancelMeta,
            ]);
        } catch (\Throwable $e) {
            $this->emitJsonResponse(['success' => false, 'message' => 'Error cancelling invoice: ' . $e->getMessage()], 500);
        }
    }

    /**
     * When a POS invoice is cancelled, mark linked vp_orders as cancelled (mirrors checkout shipped sync).
     *
     * @return array{order_numbers: list<string>, local_updated: int, api_called: int, api_failed: int, message: string}
     */
    private function markPosInvoiceOrdersCancelled(int $invoiceId): array
    {
        global $commanModel, $invoiceModel;

        $result = [
            'order_numbers' => [],
            'local_updated' => 0,
            'api_called' => 0,
            'api_failed' => 0,
            'message' => '',
        ];

        $invoiceId = (int) $invoiceId;
        if ($invoiceId <= 0) {
            $result['message'] = 'Invalid invoice id for order cancel sync.';
            return $result;
        }

        $orderNumbers = $invoiceModel->getDistinctOrderNumbersForInvoice($invoiceId);
        $result['order_numbers'] = $orderNumbers;

        if ($orderNumbers === []) {
            $result['message'] = 'No linked POS orders found for this invoice.';
            return $result;
        }

        $statusRow = $commanModel->getExoticIndiaOrderStatusCode('cancelled');
        $adminId = (int) ($statusRow['admin_id'] ?? 0);
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $changeDate = date('Y-m-d H:i:s');

        foreach ($orderNumbers as $orderNumber) {
            $lines = $invoiceModel->getOrderLinesForCancelSync($orderNumber);

            foreach ($lines as $line) {
                $apiRes = null;
                if ($adminId > 0) {
                    $apiRes = $commanModel->updateExoticIndiaOrderStatus([
                        'orderid' => $orderNumber,
                        'level' => 'item',
                        'order_status' => $adminId,
                        'itemcode' => trim((string) ($line['item_code'] ?? '')),
                        'size' => trim((string) ($line['size'] ?? '')),
                        'color' => trim((string) ($line['color'] ?? '')),
                    ]);
                    ++$result['api_called'];
                    if (empty($apiRes['success'])) {
                        ++$result['api_failed'];
                        error_log('[POS invoice cancel status API] Order ' . $orderNumber . ' item ' . (string) ($line['id'] ?? '') . ': ' . (string) ($apiRes['message'] ?? 'failed'));
                    }
                }

                $orderLineId = (int) ($line['id'] ?? 0);
                if ($orderLineId > 0) {
                    $apiResponseLog = '';
                    if ($adminId > 0 && is_array($apiRes)) {
                        $apiResponseLog = json_encode([
                            'success' => !empty($apiRes['success']),
                            'http_code' => (int) ($apiRes['http_code'] ?? 0),
                            'message' => (string) ($apiRes['message'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '';
                    }
                    try {
                        $commanModel->add_order_status_log([
                            'order_id' => $orderLineId,
                            'status' => 'Status: cancelled (POS invoice cancelled)',
                            'changed_by' => $userId,
                            'api_response' => $apiResponseLog,
                            'change_date' => $changeDate,
                        ]);
                    } catch (\Throwable $logEx) {
                        error_log('[POS invoice cancel status log] order ' . $orderLineId . ': ' . $logEx->getMessage());
                    }
                }
            }

            $result['local_updated'] += $invoiceModel->cancelLinkedOrderLines($orderNumber, $invoiceId);
        }

        if ($result['api_failed'] > 0) {
            $result['message'] = 'Orders marked cancelled locally, but Exotic cancel status API failed for ' . $result['api_failed'] . ' item(s).';
        } elseif ($result['local_updated'] <= 0) {
            $result['message'] = 'No local POS order rows were updated (order may already be cancelled or unlinked).';
        } else {
            $result['message'] = 'Linked POS orders marked cancelled locally' . ($adminId > 0 ? ' and on Exotic.' : '.');
        }

        return $result;
    }

    /* ===============================
       PREVIEW
    =============================== */
    public function preview()
    {
        global $invoiceModel;

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $items = $invoiceModel->getLegacyPreviewItemsResult($invoiceId);

        ob_start();
        require 'views/invoices/preview_template.php';
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'html' => $html,
        ]);
    }

    public function preview_new()
    {
        global $invoiceModel;

        $data = json_decode(file_get_contents('php://input'), true);
        $invoiceId = (int)($data['invoice_id'] ?? 0);
        $orderNumber = trim((string)($data['orderid'] ?? ''));

        if (!$invoiceId && $orderNumber !== '') {
            $invoiceId = $invoiceModel->findInvoiceIdByLegacyOrderNumber($orderNumber);
        }

        if (!$invoiceId) {
            echo json_encode([
                'success' => false,
                'message' => 'Invoice not found',
            ]);
            exit;
        }

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            echo json_encode([
                'success' => false,
                'message' => 'Invoice not found',
            ]);
            exit;
        }

        if ($invoice['status'] === 'proforma') {
            $template = 'views/invoices/proforma_invoice.php';
        } else {
            $template = 'views/invoices/tax_invoice.php';
        }

        $items = $invoiceModel->getLegacyInvoiceItemsResult($invoiceId);

        ob_start();
        require $template;
        $html = ob_get_clean();

        echo json_encode([
            'success' => true,
            'html' => $html,
            'invoice_id' => $invoiceId,
        ]);
    }


    public function create_from_payment()
    {
        global $invoiceModel, $paymentModel;

        $data = json_decode(file_get_contents('php://input'), true);
        $paymentId = (int)($data['payment_id'] ?? 0);

        $payment = $paymentModel->findById($paymentId);
        if (!$payment) {
            echo json_encode(['success' => false]);
            exit;
        }

        $orderNumber = (string)($payment['order_number'] ?? '');
        $invoice = $invoiceModel->findInvoiceByOrderNumberColumn($orderNumber);

        if ($invoice) {
            if (($payment['payment_stage'] ?? '') === 'final') {
                $invoiceModel->finalizeInvoiceStatus((int)$invoice['id']);
            }

            echo json_encode([
                'success' => true,
                'invoice_id' => $invoice['id'],
            ]);
            exit;
        }

        echo json_encode([
            'success' => false,
            'message' => 'Invoice not found for update',
        ]);
    }

    /* ===============================
       GET SINGLE
    =============================== */
    public function get_single_invoice()
    {
        global $invoiceModel;

        $id = (int)($_GET['id'] ?? 0);
        $invoice = $invoiceModel->getInvoiceById($id);

        echo json_encode([
            'success' => true,
            'invoice' => $invoice,
        ]);
    }

    /* ===============================
       UPDATE STATUS
    =============================== */
    public function update_status()
    {
        global $invoiceModel;

        $id = (int)($_POST['id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');
        $invoiceModel->updateInvoiceStatus($id, $status);

        echo json_encode(['success' => true]);
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
            require_once __DIR__ . '/../helpers/app_settings.php';
            $firmSettings = app_setting_global_settings();
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

    /**
     * Printable proforma from order lines only (no vp_invoices row).
     */
    public function printProformaPreviewFromOrder(): void
    {
        is_login();
        global $conn;

        $orderNumber = trim((string)($_GET['order_number'] ?? ''));
        if ($orderNumber === '') {
            http_response_code(400);
            echo '<p>Invalid order number.</p>';
            exit;
        }

        require_once __DIR__ . '/../helpers/pos_payment_receipt.php';
        if ($conn instanceof mysqli && pos_payment_is_fully_paid($conn, $orderNumber)) {
            http_response_code(400);
            echo '<p>Order is fully paid. Use Print Invoice for the tax invoice.</p>';
            exit;
        }

        $preview = $this->buildProformaPreviewFromOrder($orderNumber);
        if (empty($preview['success'])) {
            http_response_code(404);
            echo '<p>' . htmlspecialchars((string)($preview['message'] ?? 'Could not build proforma.')) . '</p>';
            exit;
        }

        require_once __DIR__ . '/../helpers/app_settings.php';
        $firmSettings = app_setting_global_settings();
        $invoice = $preview['invoice'];
        $invoice['terms_and_conditions'] = $firmSettings['terms_and_conditions'] ?? '';

        $invoiceHtml = $this->generateInvoiceHtml($invoice, $preview['items'], 'proforma');
        $label = (string)($preview['label'] ?? $orderNumber);

        renderTemplateClean('views/posinvoice/print_preview.php', [
            'invoice_html' => $invoiceHtml,
            'invoice_number' => $label,
            'invoice_pdf_url' => '',
        ], 'Proforma - ' . $label);
    }

    /**
     * @return array{success:bool,message?:string,invoice?:array<string,mixed>,items?:list<array<string,mixed>>,label?:string}
     */
    private function buildProformaPreviewFromOrder(string $orderNumber): array
    {
        global $ordersModel;

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['success' => false, 'message' => 'Order number missing'];
        }

        $items = $ordersModel->getOrderByOrderNumber($orderNumber);
        if ($items === []) {
            return ['success' => false, 'message' => 'Order not found'];
        }

        $info = $ordersModel->getAddressInfoByOrderNumber($orderNumber);
        if (empty($info['id'])) {
            return ['success' => false, 'message' => 'Order info not found'];
        }

        $savedPost = $_POST;
        $_POST = [
            'invoice_date' => date('Y-m-d'),
            'customer_id' => (int)($items[0]['customer_id'] ?? 0),
            'vp_order_info_id' => (int)$info['id'],
            'status' => 'proforma',
            'subtotal' => 0.0,
            'tax_amount' => 0.0,
            'discount_amount' => 0.0,
            'total_amount' => 0.0,
            'pos_flag' => 1,
        ];

        $snapshot = $this->buildPosInvoiceSnapshotFromOrder($orderNumber, $items, $info);
        $lineItemsMeta = [];
        if (is_array($snapshot)) {
            $this->buildInvoicePostFromCheckoutSnapshot($items, $snapshot, $info);
            $lineItemsMeta = $this->computePosInvoiceLineMetaFromSnapshot($items, $snapshot);
        } else {
            require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
            $applyExportGst = null;
            foreach ($items as $it) {
                $_POST['order_number'][] = $it['order_number'];
                $_POST['item_code'][] = $it['item_code'];
                $_POST['item_name'][] = $it['title'];
                $_POST['hsn'][] = $it['hsn'];
                $_POST['quantity'][] = $it['quantity'];

                $qty = max(1, (int)$it['quantity']);
                $unit = pos_order_pretax_unit_price($it, 'disc');

                $_POST['unit_price'][] = $unit;
                $_POST['tax_rate'][] = $it['gst'];
                $gstPlan = invoice_resolve_gst_component_plan($info, (float)$it['gst'], $applyExportGst);
                $_POST['cgst'][] = $gstPlan['cgst_rate'];
                $_POST['sgst'][] = $gstPlan['sgst_rate'];
                $_POST['igst'][] = $gstPlan['igst_rate'];
                $_POST['box_no'][] = '';
                $_POST['currency'][] = $it['currency'];

                $_POST['subtotal'] += $unit * $qty;
                $_POST['tax_amount'] += ($unit * $qty) * ((float)$it['gst'] / 100);
            }

            $_POST['total_amount'] = (float)$_POST['subtotal'] + (float)$_POST['tax_amount'];
        }

        $discountMeta = is_array($snapshot) ? $snapshot : [
            'subtotal_goods' => round((float)$_POST['total_amount'], 2),
            'gst_total' => round((float)$_POST['tax_amount'], 2),
            'grand_total' => round((float)$_POST['total_amount'], 2),
            'coupon_discount' => 0.0,
            'cash_discount' => 0.0,
            'gift_discount' => 0.0,
            'line_discount' => 0.0,
            'discounts_absorbed' => true,
        ];

        $notesJson = $this->encodePosInvoiceNotesPayload($discountMeta, $lineItemsMeta);
        $previewItems = $this->buildPreviewItemsFromPostArrays($_POST);

        $subtotal = round((float)($_POST['subtotal'] ?? 0), 2);
        $taxAmount = round((float)($_POST['tax_amount'] ?? 0), 2);
        $totalAmount = round((float)($_POST['total_amount'] ?? 0), 2);
        $discountAmount = round((float)($_POST['discount_amount'] ?? 0), 2);
        $_POST = $savedPost;

        if ($previewItems === []) {
            return ['success' => false, 'message' => 'No billable items found for this order'];
        }

        return [
            'success' => true,
            'label' => 'PROFORMA / ' . $orderNumber,
            'invoice' => [
                'id' => 0,
                'invoice_number' => 'PROFORMA / ' . $orderNumber,
                'invoice_date' => date('Y-m-d'),
                'status' => 'proforma',
                'vp_order_info_id' => (int)$info['id'],
                'customer_id' => (int)($items[0]['customer_id'] ?? 0),
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'discount_amount' => $discountAmount,
                'pos_flag' => 1,
                'notes' => $notesJson,
            ],
            'items' => $previewItems,
        ];
    }

    /**
     * @param array<string, mixed> $discountMeta
     * @param list<array<string, mixed>> $lineItemsMeta
     */
    private function encodePosInvoiceNotesPayload(array $discountMeta, array $lineItemsMeta = []): string
    {
        $payload = [
            'pos_discounts' => [
                'subtotal_goods' => round((float)($discountMeta['subtotal_goods'] ?? 0), 2),
                'gst_total' => round((float)($discountMeta['gst_total'] ?? 0), 2),
                'coupon_discount' => round((float)($discountMeta['coupon_discount'] ?? 0), 2),
                'cash_discount' => round((float)($discountMeta['cash_discount'] ?? 0), 2),
                'gift_discount' => round((float)($discountMeta['gift_discount'] ?? 0), 2),
                'line_discount' => round((float)($discountMeta['line_discount'] ?? 0), 2),
                'grand_total' => round((float)($discountMeta['grand_total'] ?? 0), 2),
                'discounts_absorbed' => !empty($discountMeta['discounts_absorbed']),
                'custom_discount_mode' => trim((string)($discountMeta['custom_discount_mode'] ?? '')),
                'custom_discount_value' => round((float)($discountMeta['custom_discount_value'] ?? 0), 2),
                'coupon_display_name' => trim((string)($discountMeta['coupon_display_name'] ?? '')),
            ],
        ];
        if (array_key_exists('apply_export_gst', $discountMeta)) {
            $payload['pos_discounts']['apply_export_gst'] = !empty($discountMeta['apply_export_gst']) ? 1 : 0;
        }
        if ($lineItemsMeta !== []) {
            $payload['line_items'] = $lineItemsMeta;
        }

        return json_encode($payload, JSON_UNESCAPED_UNICODE) ?: '';
    }

    /**
     * @param array<string, mixed> $post
     *
     * @return list<array<string, mixed>>
     */
    private function buildPreviewItemsFromPostArrays(array $post): array
    {
        $orderNumbers = isset($post['order_number']) && is_array($post['order_number']) ? $post['order_number'] : [];
        $count = count($orderNumbers);
        if ($count === 0) {
            return [];
        }

        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $qty = max(1, (int)($post['quantity'][$i] ?? 1));
            $unitPretax = (float)($post['unit_price'][$i] ?? 0);
            $taxRate = (float)($post['tax_rate'][$i] ?? 0);
            $amount = $unitPretax * $qty;
            $cgstRate = (float)($post['cgst'][$i] ?? 0);
            $sgstRate = (float)($post['sgst'][$i] ?? 0);
            $igstRate = (float)($post['igst'][$i] ?? 0);

            if ($igstRate > 0) {
                $igstAmt = ($amount * $igstRate) / 100;
                $sgstAmt = 0.0;
                $cgstAmt = 0.0;
            } else {
                $sgstAmt = ($amount * $sgstRate) / 100;
                $cgstAmt = ($amount * $cgstRate) / 100;
                $igstAmt = 0.0;
            }

            $items[] = [
                'order_number' => (string)($post['order_number'][$i] ?? ''),
                'item_code' => (string)($post['item_code'][$i] ?? ''),
                'item_name' => (string)($post['item_name'][$i] ?? ''),
                'hsn' => (string)($post['hsn'][$i] ?? ''),
                'quantity' => $qty,
                'unit_price' => $unitPretax,
                'tax_rate' => $taxRate,
                'sgst' => round($sgstAmt, 2),
                'cgst' => round($cgstAmt, 2),
                'igst' => round($igstAmt, 2),
                'box_no' => (string)($post['box_no'][$i] ?? ''),
                'currency' => (string)($post['currency'][$i] ?? 'INR'),
                'line_total' => round($amount + $sgstAmt + $cgstAmt + $igstAmt, 2),
            ];
        }

        return $items;
    }

    /**
     * Printable tax invoice in a new browser tab (preview + window.print).
     */
    public function printPreview(): void
    {
        is_login();
        global $invoiceModel, $commanModel;

        $invoiceId = (int)($_GET['invoice_id'] ?? 0);
        if ($invoiceId <= 0) {
            http_response_code(400);
            echo '<p>Invalid invoice.</p>';
            exit;
        }

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        $items = $invoiceModel->getInvoiceItems($invoiceId);
        if (!$invoice) {
            http_response_code(404);
            echo '<p>Invoice not found.</p>';
            exit;
        }

        require_once __DIR__ . '/../helpers/app_settings.php';
        $firmSettings = app_setting_global_settings();
        $invoice['terms_and_conditions'] = $firmSettings['terms_and_conditions'] ?? '';

        $invoiceHtml = $this->generateInvoiceHtml($invoice, $items, 'tax_invoice');
        $pdfUrl = pos_invoice_pdf_url($invoiceId);

        renderTemplateClean('views/posinvoice/print_preview.php', [
            'invoice_html' => $invoiceHtml,
            'invoice_number' => (string)($invoice['invoice_number'] ?? ''),
            'invoice_pdf_url' => $pdfUrl,
        ], 'Invoice - ' . ($invoice['invoice_number'] ?? ''));
    }

    private function parsePosInvoiceDiscountMeta(?string $notes): array
    {
        if ($notes === null || trim($notes) === '') {
            return [];
        }
        $decoded = json_decode($notes, true);
        if (!is_array($decoded)) {
            return [];
        }
        $pos = $decoded['pos_discounts'] ?? null;

        return is_array($pos) ? $pos : [];
    }

    private function parsePosInvoiceLineItemsMeta(?string $notes): array
    {
        if ($notes === null || trim($notes) === '') {
            return [];
        }
        $decoded = json_decode($notes, true);
        if (!is_array($decoded)) {
            return [];
        }
        $lines = $decoded['line_items'] ?? null;

        return is_array($lines) ? $lines : [];
    }

    /**
     * @param list<array<string, mixed>> $priceRows
     *
     * @return array<string, float>
     */
    private function posInclusiveUnitPriceMap(array $priceRows): array
    {
        $map = [];
        foreach ($priceRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $key = $this->posLinePriceLookupKey(
                (string)($row['itemcode'] ?? ''),
                (string)($row['size'] ?? ''),
                (string)($row['color'] ?? '')
            );
            $map[$key] = (float)str_replace(',', '', (string)($row['price'] ?? '0'));
        }

        return $map;
    }

    private function posInclusiveUnitPriceMapByItemCode(array $priceRows): array
    {
        $map = [];
        foreach ($priceRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $code = strtolower(trim((string)($row['itemcode'] ?? '')));
            if ($code === '') {
                continue;
            }
            $map[$code] = (float)str_replace(',', '', (string)($row['price'] ?? '0'));
        }

        return $map;
    }

    /**
     * @param list<array<string, mixed>> $orderItems
     * @param array<string, mixed> $snapshot
     *
     * @return list<array{item_code: string, size: string, color: string, list_unit_pretax: float, discounted_unit_pretax: float}>
     */
    private function computePosInvoiceLineMetaFromSnapshot(array $orderItems, array $snapshot): array
    {
        $listRows = is_array($snapshot['list_line_prices'] ?? null) ? $snapshot['list_line_prices'] : [];
        $discRows = is_array($snapshot['line_prices'] ?? null) ? $snapshot['line_prices'] : [];
        $listMap = $this->posInclusiveUnitPriceMap($listRows);
        $discMap = $this->posInclusiveUnitPriceMap($discRows);
        $listMapByCode = $this->posInclusiveUnitPriceMapByItemCode($listRows);
        $discMapByCode = $this->posInclusiveUnitPriceMapByItemCode($discRows);

        $rows = [];
        foreach ($orderItems as $it) {
            $qty = max(1, (int)($it['quantity'] ?? 1));
            $gstRate = (float)($it['gst'] ?? 0);
            $itemCode = strtolower(trim((string)($it['item_code'] ?? '')));
            $key = $this->posLinePriceLookupKey(
                (string)($it['item_code'] ?? ''),
                (string)($it['size'] ?? ''),
                (string)($it['color'] ?? '')
            );

            $listInclUnit = $listMap[$key] ?? ($itemCode !== '' ? ($listMapByCode[$itemCode] ?? null) : null);
            $discInclUnit = $discMap[$key] ?? ($itemCode !== '' ? ($discMapByCode[$itemCode] ?? null) : null);

            if ($discInclUnit === null || $discInclUnit <= 0) {
                $discInclUnit = pos_order_inclusive_unit_price($it, 'disc');
            }

            if ($listInclUnit === null || $listInclUnit <= 0) {
                $listInclUnit = pos_order_inclusive_unit_price($it, 'list');
            }

            $rows[] = [
                'item_code' => (string)($it['item_code'] ?? ''),
                'size' => (string)($it['size'] ?? ''),
                'color' => (string)($it['color'] ?? ''),
                'qty' => $qty,
                'gst_rate' => $gstRate,
                'list_incl_unit' => (float)$listInclUnit,
                'disc_incl_unit' => (float)$discInclUnit,
            ];
        }

        $orderLevelDisc = $this->posInvoiceOrderLevelDiscountTotal($snapshot);
        if ($orderLevelDisc > 0.001 && $rows !== []) {
            require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
            $calcInput = array_map(
                static fn(array $row): array => [
                    'list_incl_unit' => (float)$row['list_incl_unit'],
                    'disc_incl_unit' => (float)$row['disc_incl_unit'],
                    'qty' => (float)$row['qty'],
                ],
                $rows
            );
            $adjusted = pos_invoice_apply_list_price_order_discount($calcInput, $orderLevelDisc);
            foreach ($rows as $index => &$row) {
                if (!isset($adjusted[$index])) {
                    continue;
                }
                $row['list_incl_unit'] = (float)$adjusted[$index]['list_incl_unit'];
                $row['disc_incl_unit'] = (float)$adjusted[$index]['disc_incl_unit'];
            }
            unset($row);
        }

        $out = [];
        foreach ($rows as $row) {
            $gstRate = (float)$row['gst_rate'];
            $out[] = [
                'item_code' => $row['item_code'],
                'size' => $row['size'],
                'color' => $row['color'],
                'list_unit_pretax' => $this->posInvoiceInclToPretax((float)$row['list_incl_unit'], $gstRate),
                'discounted_unit_pretax' => $this->posInvoiceInclToPretax((float)$row['disc_incl_unit'], $gstRate),
                'list_unit_incl' => round((float)$row['list_incl_unit'], 2),
                'discounted_unit_incl' => round((float)$row['disc_incl_unit'], 2),
            ];
        }

        return $out;
    }

    private function posInvoiceLineMetaForItem(array $item, int $index, array $lineItemsMeta): ?array
    {
        if (!isset($lineItemsMeta[$index]) || !is_array($lineItemsMeta[$index])) {
            foreach ($lineItemsMeta as $meta) {
                if (!is_array($meta)) {
                    continue;
                }
                if (trim((string)($meta['item_code'] ?? '')) !== trim((string)($item['item_code'] ?? ''))) {
                    continue;
                }
                if (trim((string)($meta['size'] ?? '')) !== trim((string)($item['size'] ?? ''))) {
                    continue;
                }
                if (trim((string)($meta['color'] ?? '')) !== trim((string)($item['color'] ?? ''))) {
                    continue;
                }

                return $meta;
            }

            return null;
        }

        return $lineItemsMeta[$index];
    }

    /**
     * @return array{list: float, disc: float}
     */
    private function posInvoiceResolveLineUnitPretax(array $item, int $index, array $lineItemsMeta): array
    {
        $discUnitPretax = (float)($item['unit_price'] ?? 0);
        $listUnitPretax = $discUnitPretax;
        $lineMeta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
        if (is_array($lineMeta)) {
            if ((float)($lineMeta['discounted_unit_pretax'] ?? 0) > 0) {
                $discUnitPretax = (float)$lineMeta['discounted_unit_pretax'];
            }
            if ((float)($lineMeta['list_unit_pretax'] ?? 0) > 0) {
                $listUnitPretax = (float)$lineMeta['list_unit_pretax'];
            }
        }

        return ['list' => $listUnitPretax, 'disc' => $discUnitPretax];
    }

    private function posInvoiceLineHasUnitDiscount(float $listUnitPretax, float $discUnitPretax): bool
    {
        return $listUnitPretax - $discUnitPretax > 0.001;
    }

    private function posInvoiceFormatQty($quantity): int
    {
        return max(1, (int) round((float) $quantity));
    }

    private function posInvoiceUnitIncl(float $unitPretax, float $taxRate): float
    {
        if ($taxRate > 0) {
            return round($unitPretax * (1 + ($taxRate / 100)), 2);
        }

        return round($unitPretax, 2);
    }

    /**
     * GST amounts and rate columns from a GST-inclusive discounted unit price.
     *
     * @return array{sgst: float, cgst: float, igst: float, sgst_rate: float, cgst_rate: float, igst_rate: float}
     */
    private function posInvoiceComputeLineTaxBreakdown(
        float $discUnitIncl,
        int $qty,
        float $taxRate,
        bool $useIgst
    ): array {
        require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';

        return invoice_compute_tax_breakdown_from_incl_unit($discUnitIncl, $qty, $taxRate, $useIgst);
    }

    private function posInvoiceOrderLevelDiscountTotal(array $posDiscountMeta): float
    {
        require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';

        return pos_invoice_order_level_discount_total($posDiscountMeta);
    }

    private function posInvoiceInclToPretax(float $inclUnit, float $gstRate): float
    {
        if ($gstRate > 0) {
            return round($inclUnit / (1 + ($gstRate / 100)), 4);
        }

        return round($inclUnit, 4);
    }

    private function posInvoiceLineExtendedSum(array $items): float
    {
        $sum = 0.0;
        foreach ($items as $item) {
            $sum += round((float)($item['line_total'] ?? 0), 2);
        }

        return $sum;
    }

    /**
     * Product-level line discount; skip stacking order-level discount on this line.
     */
    private function posInvoiceLineHasCatalogDiscountMeta(
        float $listInclMeta,
        float $discInclMeta,
        float $listingIncl,
        float $sharePerUnit
    ): bool {
        if ($listInclMeta <= $discInclMeta + 0.02) {
            return false;
        }

        return $discInclMeta + 0.05 < round($listingIncl - $sharePerUnit, 2);
    }

    private function posInvoiceOrderDiscountAlreadyApplied(
        float $listInclMeta,
        float $discInclMeta,
        float $listingIncl,
        float $sharePerUnit
    ): bool {
        if ($listInclMeta <= 0 || $discInclMeta <= 0) {
            return false;
        }

        $discTarget = max(0.0, round($listingIncl - $sharePerUnit, 2));

        return abs($listInclMeta - $listingIncl) < 0.03 && abs($discInclMeta - $discTarget) < 0.03;
    }

    /**
     * @return array{list: float, disc: float}
     */
    private function posInvoiceOrderDiscountLinePrices(float $listingIncl, float $sharePerUnit): array
    {
        $listingIncl = round($listingIncl, 2);

        return [
            'list' => $listingIncl,
            'disc' => max(0.0, round($listingIncl - $sharePerUnit, 2)),
        ];
    }

    /**
     * Listing unit (GST incl.) from subtotal_goods ratio when order-level discount exists.
     */
    private function posInvoiceListingInclFromSubtotalRatio(
        array $item,
        float $subtotalGoods,
        float $extendedSum,
        ?array $meta = null
    ): float {
        $qty = max(1, (float)($item['quantity'] ?? 1));
        if ($subtotalGoods > 0 && $extendedSum > 0.001) {
            $lineExtended = round((float)($item['line_total'] ?? 0), 2);

            return round(($subtotalGoods * $lineExtended / $extendedSum) / $qty, 2);
        }

        $listInclMeta = (float)($meta['list_unit_incl'] ?? 0);
        $discInclMeta = (float)($meta['discounted_unit_incl'] ?? 0);
        if ($listInclMeta > 0 || $discInclMeta > 0) {
            return round(max($listInclMeta, $discInclMeta), 2);
        }

        $lineExtended = round((float)($item['line_total'] ?? 0), 2);

        return $qty > 0 ? round($lineExtended / $qty, 2) : $lineExtended;
    }

    /**
     * Split a discount across lines by proportional extended amounts (index => amount).
     *
     * @param array<int|numeric-string, float> $extendedByIndex
     * @return array<int|numeric-string, float>
     */
    private function posInvoiceProportionalDiscountSharesByExtendedAmounts(array $extendedByIndex, float $totalDiscount): array
    {
        require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';

        return pos_invoice_proportional_discount_shares($extendedByIndex, $totalDiscount);
    }

    /**
     * Split an order-level discount across lines by line-total ratio.
     *
     * @param list<array<string, mixed>> $items each row needs line_total
     * @return array<int, float> line index => extended discount share
     */
    private function posInvoiceProportionalDiscountShares(array $items, float $totalDiscount): array
    {
        if ($totalDiscount <= 0.001 || $items === []) {
            return [];
        }

        $extendedByIndex = [];
        foreach ($items as $index => $item) {
            $extendedByIndex[$index] = round((float)($item['line_total'] ?? 0), 2);
        }

        return $this->posInvoiceProportionalDiscountSharesByExtendedAmounts($extendedByIndex, $totalDiscount);
    }

    /**
     * Apply proportional order-level discount to line meta for PDF / stored notes.
     *
     * @param list<array<string, mixed>> $items
     * @param list<array<string, mixed>> $lineItemsMeta
     */
    private function applyPosOrderLevelDiscountToLineMeta(array &$lineItemsMeta, array $items, array $posMeta): void
    {
        $totalDiscount = $this->posInvoiceOrderLevelDiscountTotal($posMeta);
        if ($totalDiscount <= 0.001 || $items === []) {
            return;
        }

        $listUnitsByIndex = [];
        foreach ($items as $index => $item) {
            $taxRate = (float)($item['tax_rate'] ?? 0);
            $meta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
            $listIncl = is_array($meta) ? (float)($meta['list_unit_incl'] ?? 0) : 0.0;
            if ($listIncl <= 0) {
                $pretax = $this->posInvoiceResolveLineUnitPretax($item, $index, $lineItemsMeta);
                $listIncl = $this->posInvoiceUnitIncl($pretax['list'], $taxRate);
            }
            if ($listIncl <= 0) {
                $listIncl = pos_order_inclusive_unit_price($item, 'list');
            }
            if ($listIncl <= 0) {
                continue;
            }

            $listUnitsByIndex[$index] = round($listIncl, 2);
        }

        if ($listUnitsByIndex === []) {
            return;
        }

        require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
        $calcInput = [];
        foreach ($items as $index => $item) {
            $listIncl = (float)($listUnitsByIndex[$index] ?? 0);
            if ($listIncl <= 0) {
                continue;
            }
            $calcInput[$index] = [
                'list_incl_unit' => $listIncl,
                'disc_incl_unit' => 0.0,
                'qty' => max(1, (float)($item['quantity'] ?? 1)),
            ];
        }
        $adjusted = pos_invoice_apply_list_price_order_discount(array_values($calcInput), $totalDiscount);
        $adjustedByIndex = [];
        $calcKeys = array_keys($calcInput);
        foreach ($adjusted as $i => $row) {
            $adjustedByIndex[$calcKeys[$i]] = $row;
        }

        foreach ($items as $index => $item) {
            $adjustedRow = $adjustedByIndex[$index] ?? null;
            if (!is_array($adjustedRow)) {
                continue;
            }

            $listIncl = (float)($adjustedRow['list_incl_unit'] ?? 0);
            $discIncl = (float)($adjustedRow['disc_incl_unit'] ?? 0);
            if ($listIncl <= 0) {
                continue;
            }

            $meta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
            $prices = [
                'list' => round($listIncl, 2),
                'disc' => round($discIncl, 2),
            ];
            $gstRate = (float)($item['tax_rate'] ?? 0);
            $entry = [
                'list_unit_pretax' => $this->posInvoiceInclToPretax($prices['list'], $gstRate),
                'discounted_unit_pretax' => $this->posInvoiceInclToPretax($prices['disc'], $gstRate),
                'list_unit_incl' => $prices['list'],
                'discounted_unit_incl' => $prices['disc'],
            ];

            if (is_array($meta)) {
                $lineItemsMeta[$index] = array_merge($meta, $entry);
            } else {
                $lineItemsMeta[$index] = array_merge([
                    'item_code' => (string)($item['item_code'] ?? ''),
                    'size' => '',
                    'color' => '',
                ], $entry);
            }
        }
    }

    /**
     * GST-inclusive unit prices for PDF List / Disc columns.
     *
     * @return array{list: float, disc: float}
     */
    private function posInvoiceResolveLineDisplayPrices(
        array $item,
        int $index,
        array $lineItemsMeta
    ): array {
        $meta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
        $taxRate = (float)($item['tax_rate'] ?? 0);
        $qtyInt = $this->posInvoiceFormatQty($item['quantity'] ?? 1);

        $discIncl = is_array($meta) ? (float)($meta['discounted_unit_incl'] ?? 0) : 0.0;
        $listIncl = is_array($meta) ? (float)($meta['list_unit_incl'] ?? 0) : 0.0;

        if ($discIncl <= 0) {
            $lineTotal = round((float)($item['line_total'] ?? 0), 2);
            $discIncl = $qtyInt > 0 ? round($lineTotal / $qtyInt, 2) : $lineTotal;
        }
        if ($discIncl <= 0) {
            $pretax = $this->posInvoiceResolveLineUnitPretax($item, $index, $lineItemsMeta);
            $discIncl = $this->posInvoiceUnitIncl($pretax['disc'], $taxRate);
        }
        if ($listIncl <= 0) {
            $pretax = $this->posInvoiceResolveLineUnitPretax($item, $index, $lineItemsMeta);
            $listIncl = $this->posInvoiceUnitIncl($pretax['list'], $taxRate);
        }
        if ($listIncl <= $discIncl + 0.02) {
            $listIncl = max($listIncl, $discIncl);
        }

        return [
            'list' => round($listIncl, 2),
            'disc' => round($discIncl, 2),
        ];
    }

    private function posLinePriceLookupKey(string $itemCode, string $size = '', string $color = ''): string
    {
        return strtolower(trim($itemCode)) . '|' . strtolower(trim($size)) . '|' . strtolower(trim($color));
    }

    /**
     * Rebuild checkout invoice snapshot from persisted order rows (payments / delayed invoice create).
     *
     * @param list<array<string, mixed>> $orderItems
     * @param array<string, mixed>|null $orderInfo vp_order_info row
     * @return array<string, mixed>|null
     */
    private function buildPosInvoiceSnapshotFromOrder(string $orderNumber, array $orderItems, ?array $orderInfo): ?array
    {
        global $conn;

        if ($orderNumber === '' || $orderItems === []) {
            return null;
        }

        require_once __DIR__ . '/../helpers/pos_payment_receipt.php';

        $listLinePrices = $this->buildPosLinePriceRowsFromOrderItems($orderItems, 'list');
        $discLinePrices = $this->buildPosLinePriceRowsFromOrderItems($orderItems, 'disc');
        if ($listLinePrices === [] && $discLinePrices === []) {
            return null;
        }

        $listSubtotal = 0.0;
        $discSubtotal = 0.0;
        foreach ($orderItems as $it) {
            $listSubtotal += pos_order_inclusive_line_total($it, 'list');
            $discSubtotal += pos_order_inclusive_line_total($it, 'disc');
        }

        $couponDiscount = round((float)($orderInfo['coupon_reduce'] ?? 0), 2);
        $giftDiscount = round((float)($orderInfo['giftvoucher_reduce'] ?? 0), 2);
        $credit = round((float)($orderInfo['credit'] ?? 0), 2);

        $cashDiscount = round(max(
            (float)($orderInfo['custom_reduce'] ?? 0),
            array_reduce($orderItems, static function (float $max, array $it): float {
                return max($max, round((float)($it['custom_reduce'] ?? 0), 2));
            }, 0.0)
        ), 2);

        $lineDiscount = max(0.0, round($listSubtotal - $discSubtotal, 2));

        $grandTotal = ($conn instanceof mysqli)
            ? pos_payment_resolve_order_total($conn, $orderNumber)
            : 0.0;
        if ($grandTotal <= 0) {
            $grandTotal = round((float)($orderInfo['total'] ?? 0), 2);
        }
        if ($grandTotal <= 0) {
            $grandTotal = max(0.0, round($discSubtotal - $couponDiscount - $cashDiscount - $giftDiscount - $credit, 2));
        }

        $subtotalGoods = round($listSubtotal, 2);
        if ($subtotalGoods <= 0) {
            $subtotalGoods = round($discSubtotal, 2);
        }
        if ($subtotalGoods <= 0 && $grandTotal > 0) {
            $subtotalGoods = $grandTotal;
        }

        $customMode = '';
        $customValue = 0.0;
        if ($cashDiscount > 0) {
            $customNote = trim((string)($orderInfo['custom_note'] ?? ''));
            if ($customNote !== '' && preg_match('/(\d+(?:\.\d+)?)\s*%/', $customNote, $matches)) {
                $customMode = 'percent';
                $customValue = round((float)$matches[1], 2);
            } else {
                $customMode = 'fixed';
                $customValue = $cashDiscount;
            }
        }

        return [
            'order_number' => $orderNumber,
            'subtotal_goods' => $subtotalGoods,
            'gst_total' => 0.0,
            'coupon_discount' => $couponDiscount,
            'cash_discount' => $cashDiscount,
            'gift_discount' => $giftDiscount,
            'line_discount' => $lineDiscount,
            'grand_total' => $grandTotal,
            'line_prices' => $discLinePrices,
            'list_line_prices' => $listLinePrices,
            'discounts_absorbed' => true,
            'custom_discount_mode' => $customMode,
            'custom_discount_value' => $customValue,
            'coupon_display_name' => trim((string)($orderInfo['coupon'] ?? '')),
        ];
    }

    /**
     * @param list<array<string, mixed>> $orderItems
     * @return list<array{itemcode: string, size: string, color: string, price: string}>
     */
    private function buildPosLinePriceRowsFromOrderItems(array $orderItems, string $kind): array
    {
        $rows = [];
        foreach ($orderItems as $it) {
            $itemCode = trim((string)($it['item_code'] ?? ''));
            if ($itemCode === '') {
                continue;
            }

            $unit = pos_order_inclusive_unit_price($it, $kind === 'list' ? 'list' : 'disc');
            if ($unit <= 0) {
                continue;
            }

            $rows[] = [
                'itemcode' => $itemCode,
                'size' => trim((string)($it['size'] ?? '')),
                'color' => trim((string)($it['color'] ?? '')),
                'price' => number_format($unit, 2, '.', ''),
            ];
        }

        return $rows;
    }

    /**
     * @param list<array<string, mixed>> $orderItems
     * @param array<string, mixed> $snapshot
     */
    private function buildInvoicePostFromCheckoutSnapshot(array $orderItems, array $snapshot, ?array $orderInfo = null): void
    {
        require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
        $applyExportGst = $snapshot['apply_export_gst'] ?? null;
        $linePrices = is_array($snapshot['line_prices'] ?? null) ? $snapshot['line_prices'] : [];
        $priceMap = [];
        foreach ($linePrices as $lp) {
            if (!is_array($lp)) {
                continue;
            }
            $key = $this->posLinePriceLookupKey(
                (string)($lp['itemcode'] ?? ''),
                (string)($lp['size'] ?? ''),
                (string)($lp['color'] ?? '')
            );
            $priceMap[$key] = (float)str_replace(',', '', (string)($lp['price'] ?? '0'));
        }

        $grandTarget = round((float)($snapshot['grand_total'] ?? 0), 2);
        $discountPool = round(
            (float)($snapshot['coupon_discount'] ?? 0)
            + (float)($snapshot['cash_discount'] ?? 0)
            + (float)($snapshot['gift_discount'] ?? 0),
            2
        );

        $_POST['order_number'] = [];
        $_POST['item_code'] = [];
        $_POST['item_name'] = [];
        $_POST['hsn'] = [];
        $_POST['quantity'] = [];
        $_POST['unit_price'] = [];
        $_POST['tax_rate'] = [];
        $_POST['cgst'] = [];
        $_POST['sgst'] = [];
        $_POST['igst'] = [];
        $_POST['box_no'] = [];
        $_POST['currency'] = [];
        $_POST['subtotal'] = 0.0;
        $_POST['tax_amount'] = 0.0;

        $computedLines = [];
        $computedInclTotal = 0.0;

        foreach ($orderItems as $it) {
            $qty = max(1, (int)($it['quantity'] ?? 1));
            $gstRate = (float)($it['gst'] ?? 0);
            $key = $this->posLinePriceLookupKey(
                (string)($it['item_code'] ?? ''),
                (string)($it['size'] ?? ''),
                (string)($it['color'] ?? '')
            );

            $inclUnit = $priceMap[$key] ?? null;
            if ($inclUnit === null || $inclUnit <= 0) {
                $inclLine = pos_order_inclusive_line_total($it, 'disc');
                if ($inclLine <= 0) {
                    $inclLine = pos_order_inclusive_line_total($it, 'list');
                }
            } else {
                $inclLine = round($inclUnit * $qty, 2);
            }

            $computedInclTotal += $inclLine;
            $computedLines[] = [
                'it' => $it,
                'qty' => $qty,
                'gstRate' => $gstRate,
                'inclLine' => $inclLine,
            ];
        }

        if ($grandTarget > 0 && $computedInclTotal > 0 && abs($computedInclTotal - $grandTarget) > 0.02) {
            if (count($computedLines) === 1) {
                $computedLines[0]['inclLine'] = $grandTarget;
            } else {
                $factor = $grandTarget / $computedInclTotal;
                $remaining = $grandTarget;
                $last = count($computedLines) - 1;
                for ($i = 0; $i < count($computedLines); $i++) {
                    if ($i === $last) {
                        $computedLines[$i]['inclLine'] = round($remaining, 2);
                    } else {
                        $share = round($computedLines[$i]['inclLine'] * $factor, 2);
                        $computedLines[$i]['inclLine'] = $share;
                        $remaining = round($remaining - $share, 2);
                    }
                }
            }
        } elseif ($grandTarget > 0 && $computedInclTotal <= 0 && count($computedLines) === 1) {
            $computedLines[0]['inclLine'] = $grandTarget;
        }

        foreach ($computedLines as $line) {
            $it = $line['it'];
            $qty = $line['qty'];
            $gstRate = $line['gstRate'];
            $inclLine = round((float)$line['inclLine'], 2);
            $inclUnit = $qty >= 1 ? $inclLine / $qty : $inclLine;
            $gstPlan = invoice_resolve_gst_component_plan($orderInfo, $gstRate, $applyExportGst);
            $applyGst = invoice_should_apply_gst($orderInfo, $applyExportGst);
            if ($applyGst && $gstRate > 0) {
                $pretaxUnit = $inclUnit / (1 + ($gstRate / 100));
            } else {
                $pretaxUnit = $inclUnit;
            }
            $pretaxLine = round($pretaxUnit * $qty, 2);
            $taxLine = $applyGst ? round($inclLine - $pretaxLine, 2) : 0.0;
            $lineTaxRate = $applyGst ? $gstRate : 0.0;

            $_POST['order_number'][] = $it['order_number'];
            $_POST['item_code'][] = $it['item_code'];
            $_POST['item_name'][] = $it['title'];
            $_POST['hsn'][] = $it['hsn'];
            $_POST['quantity'][] = $qty;
            $_POST['unit_price'][] = round($pretaxUnit, 4);
            $_POST['tax_rate'][] = $lineTaxRate;
            $_POST['cgst'][] = $gstPlan['cgst_rate'];
            $_POST['sgst'][] = $gstPlan['sgst_rate'];
            $_POST['igst'][] = $gstPlan['igst_rate'];
            $_POST['box_no'][] = '';
            $_POST['currency'][] = $it['currency'];

            $_POST['subtotal'] += $pretaxLine;
            $_POST['tax_amount'] += $taxLine;
        }

        $_POST['subtotal'] = round((float)$_POST['subtotal'], 2);
        $_POST['tax_amount'] = round((float)$_POST['tax_amount'], 2);
        $_POST['total_amount'] = round((float)$_POST['subtotal'] + (float)$_POST['tax_amount'], 2);

        if ($grandTarget > 0 && abs($_POST['total_amount'] - $grandTarget) > 0.02) {
            $_POST['total_amount'] = $grandTarget;
            $_POST['tax_amount'] = round($grandTarget - (float)$_POST['subtotal'], 2);
        }

        if ($discountPool > 0.001) {
            $_POST['discount_amount'] = 0;
        }
    }

    private function persistPosInvoiceDiscountNotes(int $invoiceId, array $discountMeta, array $lineItemsMeta = []): void
    {
        global $invoiceModel;

        if ($invoiceId <= 0) {
            return;
        }

        $payload = [
            'pos_discounts' => [
                'subtotal_goods' => round((float)($discountMeta['subtotal_goods'] ?? 0), 2),
                'gst_total' => round((float)($discountMeta['gst_total'] ?? 0), 2),
                'coupon_discount' => round((float)($discountMeta['coupon_discount'] ?? 0), 2),
                'cash_discount' => round((float)($discountMeta['cash_discount'] ?? 0), 2),
                'gift_discount' => round((float)($discountMeta['gift_discount'] ?? 0), 2),
                'line_discount' => round((float)($discountMeta['line_discount'] ?? 0), 2),
                'grand_total' => round((float)($discountMeta['grand_total'] ?? 0), 2),
                'discounts_absorbed' => !empty($discountMeta['discounts_absorbed']),
                'custom_discount_mode' => trim((string)($discountMeta['custom_discount_mode'] ?? '')),
                'custom_discount_value' => round((float)($discountMeta['custom_discount_value'] ?? 0), 2),
                'coupon_display_name' => trim((string)($discountMeta['coupon_display_name'] ?? '')),
            ],
        ];
        if (array_key_exists('apply_export_gst', $discountMeta)) {
            $payload['pos_discounts']['apply_export_gst'] = !empty($discountMeta['apply_export_gst']) ? 1 : 0;
        }
        if (count($lineItemsMeta) > 0) {
            $payload['line_items'] = $lineItemsMeta;
        }

        $hasSummary = ($payload['pos_discounts']['subtotal_goods'] > 0)
            || ($payload['pos_discounts']['coupon_discount'] > 0)
            || ($payload['pos_discounts']['cash_discount'] > 0)
            || ($payload['pos_discounts']['gift_discount'] > 0)
            || ($payload['pos_discounts']['line_discount'] > 0)
            || ($payload['pos_discounts']['grand_total'] > 0)
            || count($lineItemsMeta) > 0;

        if (!$hasSummary) {
            return;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($json === false) {
            return;
        }

        $invoiceModel->updateInvoiceNotes($invoiceId, $json);
    }

    private function buildPosInvoiceAmountSummaryRows(
        array $posMeta,
        float $grandTotal,
        float $taxAmount,
        int $colCount = 13
    ): string {
        require_once __DIR__ . '/../helpers/invoice/pos_invoice_amount_summary.php';

        return pos_invoice_render_amount_summary_html(
            pos_invoice_build_amount_summary_rows($posMeta, $grandTotal, $taxAmount),
            $colCount
        );
    }

    private function generateInvoiceHtml($invoice, $items, $type = '')
    {
        global $commanModel, $invoiceModel, $conn;

        $orderNumberForRepair = '';
        if (!empty($items[0]['order_number'])) {
            $orderNumberForRepair = trim((string)$items[0]['order_number']);
        }
        $invoiceId = (int)($invoice['id'] ?? 0);
        if ($invoiceId > 0 && $orderNumberForRepair !== '') {
            $notesEmpty = trim((string)($invoice['notes'] ?? '')) === '';
            $lineMetaEmpty = empty($this->parsePosInvoiceLineItemsMeta($invoice['notes'] ?? null));
            $parsedDiscount = $this->parsePosInvoiceDiscountMeta($invoice['notes'] ?? null);
            $discountMetaEmpty = empty($parsedDiscount);
            $discountMetaStale = !$discountMetaEmpty
                && $this->posInvoiceDiscountMetaNeedsRepair($parsedDiscount, $orderNumberForRepair);
            require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
            $lineMetaStale = !$lineMetaEmpty
                && !empty($parsedDiscount)
                && pos_invoice_line_meta_needs_repair(
                    $this->parsePosInvoiceLineItemsMeta($invoice['notes'] ?? null),
                    $parsedDiscount
                );
            if ($notesEmpty || $lineMetaEmpty || $discountMetaEmpty || $discountMetaStale || $lineMetaStale) {
                if ($this->repairPosInvoiceMetadataForOrder($invoiceId, $orderNumberForRepair)) {
                    $reloaded = $invoiceModel->getInvoiceById($invoiceId);
                    if (is_array($reloaded)) {
                        $invoice = $reloaded;
                    }
                }
            }
        }

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
        $sumLineTotals = 0.0;
        $sumListLineTotals = 0.0;
        $lineItemsMeta = $this->parsePosInvoiceLineItemsMeta($invoice['notes'] ?? null);
        $posDiscountMeta = $this->parsePosInvoiceDiscountMeta($invoice['notes'] ?? null);
        if (!empty($posDiscountMeta)) {
            $this->applyPosOrderLevelDiscountToLineMeta($lineItemsMeta, $items, $posDiscountMeta);
        }
        $usePosItemLayout = !empty($lineItemsMeta) || !empty($invoice['pos_flag']);
        $isProformaInvoice = strtolower(trim((string)($invoice['status'] ?? ''))) === 'proforma';
        $usePosItemRowLayout = $usePosItemLayout && !$isProformaInvoice;
        $showDiscPriceColumn = false;
        $posTableColCount = 13;
        if ($usePosItemLayout && (($invoice['status'] ?? '') !== 'proforma')) {
            foreach ($items as $scanIdx => $scanItem) {
                $displayPrices = $this->posInvoiceResolveLineDisplayPrices(
                    $scanItem,
                    $scanIdx,
                    $lineItemsMeta
                );
                if ($this->posInvoiceLineHasUnitDiscount($displayPrices['list'], $displayPrices['disc'])) {
                    $showDiscPriceColumn = true;
                    break;
                }
            }
            $posTableColCount = $showDiscPriceColumn ? 13 : 12;
        }
        $tableColCount = ($usePosItemLayout && (($invoice['status'] ?? '') !== 'proforma'))
            ? $posTableColCount
            : 13;

        require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
        $resolvedUseIgst = invoice_resolve_uses_igst_for_invoice($invoice, $commanModel);
        $applyGstForInvoice = invoice_should_apply_gst_for_invoice($invoice, $commanModel, $posDiscountMeta);

        // Build item rows
        foreach ($items as $idx => $item) {
            $qtyInt = $this->posInvoiceFormatQty($item['quantity'] ?? 1);
            $totalQuantity += $qtyInt;

            $displayPrices = $this->posInvoiceResolveLineDisplayPrices(
                $item,
                $idx,
                $lineItemsMeta
            );
            $listUnitDisplay = $showDiscPriceColumn ? $displayPrices['list'] : $displayPrices['disc'];
            $discUnitDisplay = $displayPrices['disc'];
            $taxRate = (float)($item['tax_rate'] ?? 0);
            $lineTotalDisplay = $showDiscPriceColumn
                ? round($discUnitDisplay * $qtyInt, 2)
                : round((float)($item['line_total'] ?? 0), 2);
            $sumLineTotals += $lineTotalDisplay;
            if ($showDiscPriceColumn) {
                $sumListLineTotals += round($listUnitDisplay * $qtyInt, 2);
            }

            $useIgst = !$applyGstForInvoice
                ? false
                : ($resolvedUseIgst !== null
                    ? $resolvedUseIgst
                    : ((float)($item['igst'] ?? 0) > 0));
            if (!$applyGstForInvoice) {
                $sgstAmt = 0.0;
                $cgstAmt = 0.0;
                $igstAmt = 0.0;
                $sgstRate = 0.0;
                $cgstRate = 0.0;
                $igstRate = 0.0;
            } elseif ($showDiscPriceColumn) {
                $taxBreakdown = $this->posInvoiceComputeLineTaxBreakdown(
                    $discUnitDisplay,
                    $qtyInt,
                    $taxRate,
                    $useIgst
                );
                $sgstAmt = $taxBreakdown['sgst'];
                $cgstAmt = $taxBreakdown['cgst'];
                $igstAmt = $taxBreakdown['igst'];
                $sgstRate = $taxBreakdown['sgst_rate'];
                $cgstRate = $taxBreakdown['cgst_rate'];
                $igstRate = $taxBreakdown['igst_rate'];
            } elseif ($resolvedUseIgst !== null) {
                $unitPretax = (float)($item['unit_price'] ?? 0);
                $taxBreakdown = invoice_compute_tax_breakdown_from_pretax(
                    $unitPretax,
                    $qtyInt,
                    $taxRate,
                    $useIgst
                );
                $sgstAmt = $taxBreakdown['sgst'];
                $cgstAmt = $taxBreakdown['cgst'];
                $igstAmt = $taxBreakdown['igst'];
                $sgstRate = $taxBreakdown['sgst_rate'];
                $cgstRate = $taxBreakdown['cgst_rate'];
                $igstRate = $taxBreakdown['igst_rate'];
            } else {
                $sgstAmt = (float)($item['sgst'] ?? 0);
                $cgstAmt = (float)($item['cgst'] ?? 0);
                $igstAmt = (float)($item['igst'] ?? 0);
                $unitPrices = $this->posInvoiceResolveLineUnitPretax($item, $idx, $lineItemsMeta);
                $rateBase = $unitPrices['disc'] > 0 ? $unitPrices['disc'] : (float)($item['unit_price'] ?? 0);
                if ($useIgst) {
                    $igstRate = $rateBase > 0 ? ($igstAmt / $qtyInt) / ($rateBase / 100) : 0;
                    $sgstRate = 0;
                    $cgstRate = 0;
                } else {
                    $sgstRate = $rateBase > 0 ? ($sgstAmt / $qtyInt) / ($rateBase / 100) : 0;
                    $cgstRate = $rateBase > 0 ? ($cgstAmt / $qtyInt) / ($rateBase / 100) : 0;
                    $igstRate = 0;
                }
            }

            $totalSgstAmt += $sgstAmt;
            $totalCgstAmt += $cgstAmt;
            $totalIgstAmt += $igstAmt;
            $totalGstAmount += $sgstAmt + $cgstAmt + $igstAmt;

            if ($usePosItemRowLayout) {
                $itemName = htmlspecialchars($item['item_name'] ?? '');
                $hsnCode = trim((string)($item['hsn'] ?? ''));
                $descHtml = $itemName;
                if ($hsnCode !== '') {
                    $descHtml .= '<br><span style="font-size:12px;color:#444;">HSN: '
                        . htmlspecialchars($hsnCode) . '</span>';
                }
                $listPriceCell = '<td class="right">' . number_format($listUnitDisplay, 2) . '</td>';
                $taxableUnitDisplay = round($this->posInvoiceInclToPretax($discUnitDisplay, $taxRate), 2);
                $taxableValueCell = $showDiscPriceColumn
                    ? '<td class="right">' . number_format($taxableUnitDisplay, 2) . '</td>'
                    : '';
                $itemsrows .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['box_no'] ?? '') . '</td>
                        <td class="desc">' . $descHtml . '</td>
                        ' . $listPriceCell . $taxableValueCell . '
                        <td>' . $qtyInt . '</td>
                        <td class="right">' . number_format($sgstRate, 2) . '</td>
                        <td class="right">' . number_format($sgstAmt, 2) . '</td>
                        <td class="right">' . number_format($cgstRate, 2) . '</td>
                        <td class="right">' . number_format($cgstAmt, 2) . '</td>
                        <td class="right">' . number_format($igstRate, 2) . '</td>
                        <td class="right">' . number_format($igstAmt, 2) . '</td>
                        <td class="right bold">' . number_format($lineTotalDisplay, 2) . '</td>
                    </tr>
            ';
            } else {
            $unitPriceDisplay = (float)($item['unit_price'] ?? 0);
            if ($isProformaInvoice && !empty($invoice['pos_flag']) && $discUnitDisplay > 0) {
                $unitPriceDisplay = $discUnitDisplay;
            }
            $itemsrows .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['box_no'] ?? '') . '</td>
                        <td class="desc">' . htmlspecialchars($item['item_name'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['hsn'] ?? '') . '</td>
                        <td>' . $qtyInt . '</td>
                        <td class="right">' . number_format($unitPriceDisplay, 2) . '</td>
                        <td class="right">' . number_format($sgstRate, 2) . '</td>
                        <td class="right">' . number_format($sgstAmt, 2) . '</td>
                        <td class="right">' . number_format($cgstRate, 2) . '</td>
                        <td class="right">' . number_format($cgstAmt, 2) . '</td>
                        <td class="right">' . number_format($igstRate, 2) . '</td>
                        <td class="right">' . number_format($igstAmt, 2) . '</td>
                        <td class="right bold">' . number_format($lineTotalDisplay, 2) . '</td>
                    </tr>
            ';
            }
        }
        if (count($items) < 3) {
            // Add empty rows to maintain table height
            $rowsToAdd = 3 - count($items);
            for ($i = 0; $i < $rowsToAdd; $i++) {
                if ($usePosItemRowLayout) {
                    $emptyPriceCells = $showDiscPriceColumn
                        ? '<td>&nbsp;</td><td>&nbsp;</td>'
                        : '<td>&nbsp;</td>';
                    $itemsrows .= '
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="desc">&nbsp;</td>
                        ' . $emptyPriceCells . '
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
                } else {
                    $itemsrows .= '
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="desc">&nbsp;</td>
                        <td>&nbsp;</td>
                        <td class="right">&nbsp;</td>
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
        }
        // Build summary rows with tax totals
        $discount = $invoice['discount_amount'] ?? 0;
        if (empty($posDiscountMeta)) {
            $posDiscountMeta = $this->parsePosInvoiceDiscountMeta($invoice['notes'] ?? null);
        }
        if (empty($posDiscountMeta) && !empty($invoice['pos_flag'])) {
            $posDiscountMeta = [
                'subtotal_goods' => round((float)($invoice['total_amount'] ?? 0), 2),
                'gst_total' => round((float)($invoice['tax_amount'] ?? 0), 2),
                'grand_total' => round((float)($invoice['total_amount'] ?? 0), 2),
                'discounts_absorbed' => true,
            ];
        }
        if ($usePosItemRowLayout && $showDiscPriceColumn && $sumListLineTotals > 0.001) {
            $existingSub = round((float)($posDiscountMeta['subtotal_goods'] ?? 0), 2);
            if ($existingSub <= 0 || abs($existingSub - $sumListLineTotals) > 0.02) {
                $posDiscountMeta['subtotal_goods'] = round($sumListLineTotals, 2);
            }
        }
        $summaryGrandTotal = round((float)($posDiscountMeta['grand_total'] ?? 0), 2);
        $summarySubtotal = round((float)($posDiscountMeta['subtotal_goods'] ?? 0), 2);
        $orderLevelDisc = $this->posInvoiceOrderLevelDiscountTotal($posDiscountMeta);
        $summaryBase = $summarySubtotal > 0 ? $summarySubtotal : round($sumLineTotals, 2);
        if ($orderLevelDisc > 0.001 && $summaryBase > 0) {
            $computedGrand = max(0.0, round($summaryBase - $orderLevelDisc, 2));
            if ($summaryGrandTotal <= 0 || abs($summaryGrandTotal - $summaryBase) < 0.02) {
                $summaryGrandTotal = $computedGrand;
            }
        }
        if ($summaryGrandTotal <= 0) {
            $summaryGrandTotal = round((float)$totalAmount, 2);
        }
        require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
        $excelGrandTotal = pos_invoice_expected_grand_total($posDiscountMeta, $sumListLineTotals);
        if ($orderLevelDisc > 0.001 && $summarySubtotal > 0.001) {
            $summaryGrandTotal = $excelGrandTotal;
        } elseif ($summaryGrandTotal <= 0 && $sumLineTotals > 0.001) {
            $summaryGrandTotal = round($sumLineTotals, 2);
        } elseif ($sumLineTotals > 0.001 && abs($sumLineTotals - $excelGrandTotal) <= 0.05) {
            $summaryGrandTotal = round($sumLineTotals, 2);
        }
        $summaryTaxAmount = round($totalSgstAmt + $totalCgstAmt + $totalIgstAmt, 2);
        if ($showDiscPriceColumn && $summaryTaxAmount > 0.001) {
            $posDiscountMeta['gst_total'] = $summaryTaxAmount;
        } elseif ($summaryTaxAmount <= 0) {
            $summaryTaxAmount = round((float)($invoice['tax_amount'] ?? 0), 2);
        }
        $tableLineTotal = ($usePosItemRowLayout && $showDiscPriceColumn && $sumLineTotals > 0.001)
            ? round($sumLineTotals, 2)
            : round((float)$totalAmount, 2);

        // Add row for tax amount totals (below each SGST/CGST/IGST column)
        $posTotalDiscEmpty = ($usePosItemRowLayout && $showDiscPriceColumn)
            ? '<td></td>'
            : '';
        $summaryrows .= '
                    <tr style="background: #e8e8e8; border-top: 2px solid #000;">
                        <td colspan="4" class="right bold">Total:</td>
                        ' . $posTotalDiscEmpty . '
                        <td class="right bold">' . $totalQuantity . '</td>
                        <td></td>
                        <td class="right bold">' . number_format($totalSgstAmt, 2) . '</td>
                        <td class="right bold"></td>
                        <td class="right bold">' . number_format($totalCgstAmt, 2) . '</td>
                        <td class="right bold"></td>
                        <td class="right bold">' . number_format($totalIgstAmt, 2) . '</td>
                        <td class="right bold">' . number_format($tableLineTotal, 2) . '</td>
                    </tr>
        ';


        $amountSummary = $this->buildPosInvoiceAmountSummaryRows(
            $posDiscountMeta,
            $summaryGrandTotal,
            $summaryTaxAmount,
            $tableColCount
        );
        if ($amountSummary !== '') {
            $summaryrows .= $amountSummary;
            $totalAmount = $summaryGrandTotal;
        } else {
            if ($discount > 0) {
                $summaryrows .= '
                    <tr style="background: #f9f9f9;">
                        <td colspan="10"></td>
                        <td class="right bold">Discount:</td>
                        <td class="right bold">-' . number_format($discount, 2) . '</td>
                    </tr>';
                $totalAmount -= $discount;
            }

            $summaryrows .= '
                    <tr style="background: #f0f0f0; border-top: 2px solid #000;">
                        <td colspan="' . ($tableColCount - 1) . '" class="right bold" style="text-align: right;">Grand Total:</td>                      
                        <td class="right bold" style="border: 1px solid #000; padding: 8px;">' . number_format($totalAmount, 2) . '</td>
                    </tr>
        ';
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
                    <td colspan="' . $tableColCount . '" style="padding: 20px;" class="right bold">' . htmlspecialchars($exchangeText) . '</td>
                    
                </tr>
                <tr style="background: #f9f9f9;">
                    <td colspan="' . ($tableColCount - 1) . '" class="right bold" style="text-align: right;">Converted Amount (INR)</td>
                    <td class="right bold">' . number_format($convertedAmount, 2) . '</td>
                </tr>';
        }

        // Fetch customer and address information
        require_once __DIR__ . '/../helpers/invoice/invoice_address_html.php';
        require_once __DIR__ . '/../helpers/invoice/invoice_footer_html.php';
        require_once __DIR__ . '/../helpers/invoice/invoice_terms_html.php';
        global $paymentModel;
        $exclusiveStoresHeader = invoice_resolve_exclusive_stores_footer_html(
            $invoice,
            $items,
            $commanModel,
            !empty($invoice['pos_flag']) ? $paymentModel : null
        );
        $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
        $addressBlocks = invoice_resolve_bill_ship_html(is_array($customer) ? $customer : null, $conn ?? null);
        $billToInfo = $addressBlocks['bill'];
        $shipToInfo = $addressBlocks['ship'];
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
        if ($usePosItemLayout && ($invoice['status'] ?? '') !== 'proforma') {
            if ($showDiscPriceColumn) {
                $temphtml = str_replace(
                    "<th>HSN</th>\n        <th>Qty</th>\n        <th>Price</th>",
                    "<th>List Price</th>\n        <th>Taxable Value</th>\n        <th>Qty</th>",
                    $temphtml
                );
            } else {
                $temphtml = str_replace(
                    "<th>HSN</th>\n        <th>Qty</th>\n        <th>Price</th>",
                    "<th>Price</th>\n        <th>Qty</th>",
                    $temphtml
                );
                $temphtml = str_replace('<th colspan="6"></th>', '<th colspan="5"></th>', $temphtml);
            }
        }

        // Replace placeholders
        $html = str_replace(
            [
                '{{INVOICE_NUMBER}}',
                '{{INVOICE_DATE}}',
                '{{BILL_TO_INFO}}',
                '{{SHIP_TO_INFO}}',
                '{{ITEM_ROWS}}',
                '{{SUMMARY_ROWS}}',
                '{{AMOUNT_IN_WORDS}}',
                '{{TERMS_AND_CONDITIONS_BLOCK}}',
                '{{EXCLUSIVE_STORES_HEADER}}',
            ],
            [
                htmlspecialchars($invoice['invoice_number'] ?? 'N/A'),
                date('d M Y', strtotime($invoice['invoice_date'])),
                $billToInfo,
                $shipToInfo,
                $itemsrows,
                $summaryrows,
                numberToWords($totalAmount ?? 0),
                invoice_format_terms_and_conditions_block($invoice['terms_and_conditions'] ?? ''),
                $exclusiveStoresHeader,
            ],
            $temphtml
        );

        return $html;
    }

    public function create_auto_from_order()
    {
        is_login();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $orderNumber = $input['orderid'] ?? null;
        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }
        echo json_encode($this->createAutoInvoiceForOrder((string)$orderNumber));
        exit;
    }

    public function updateInvoiceNumberAjax(): void
    {
        is_login();
        if (!canSrEmpAccess()) {
            vendorJsonResponse(['success' => false, 'message' => 'Access denied. Sr Emp, Top Management, or Admin access required.']);
        }

        global $invoiceModel;

        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        $newInvoiceNumber = trim((string)($_POST['new_invoice_number'] ?? ''));

        vendorJsonResponse($invoiceModel->updateInvoiceNumber($invoiceId, $newInvoiceNumber));
    }

    /**
     * Backfill POS line/discount metadata on invoices created before notes were persisted.
     */
    private function posInvoiceDiscountMetaNeedsRepair(array $meta, string $orderNumber): bool
    {
        global $ordersModel;

        $orderLevelDisc = round(
            (float)($meta['coupon_discount'] ?? 0)
            + (float)($meta['cash_discount'] ?? 0)
            + (float)($meta['gift_discount'] ?? 0),
            2
        );
        $sub = round((float)($meta['subtotal_goods'] ?? 0), 2);
        $grand = round((float)($meta['grand_total'] ?? 0), 2);

        if ($orderLevelDisc > 0.001 && $sub > 0.001) {
            $expectedGrand = max(0.0, round($sub - $orderLevelDisc, 2));
            if (abs($grand - $expectedGrand) > 0.02) {
                return true;
            }
        }

        if ($orderNumber === '') {
            return false;
        }

        $info = $ordersModel->getAddressInfoByOrderNumber($orderNumber);
        $cashOnOrder = round((float)($info['custom_reduce'] ?? 0), 2);
        if ($cashOnOrder > 0.001 && round((float)($meta['cash_discount'] ?? 0), 2) <= 0) {
            return true;
        }

        return false;
    }

    /**
     * Backfill POS line/discount metadata on invoices created before notes were persisted.
     */
    public function repairPosInvoiceMetadataForOrder(int $invoiceId, string $orderNumber): bool
    {
        global $invoiceModel, $ordersModel;

        $invoiceId = (int)$invoiceId;
        $orderNumber = trim($orderNumber);
        if ($invoiceId <= 0 || $orderNumber === '') {
            return false;
        }

        $invoice = $invoiceModel->getInvoiceById($invoiceId);
        if (!$invoice) {
            return false;
        }

        $existingLines = $this->parsePosInvoiceLineItemsMeta($invoice['notes'] ?? null);
        $existingDiscount = $this->parsePosInvoiceDiscountMeta($invoice['notes'] ?? null);
        require_once __DIR__ . '/../helpers/invoice/pos_invoice_line_calculation.php';
        if (
            !empty($existingLines)
            && !empty($existingDiscount)
            && !$this->posInvoiceDiscountMetaNeedsRepair($existingDiscount, $orderNumber)
            && !pos_invoice_line_meta_needs_repair($existingLines, $existingDiscount)
        ) {
            return false;
        }

        $items = $ordersModel->getOrderByOrderNumber($orderNumber);
        if ($items === []) {
            return false;
        }

        $info = $ordersModel->getAddressInfoByOrderNumber($orderNumber);
        $snapshot = $this->buildPosInvoiceSnapshotFromOrder($orderNumber, $items, $info);
        if (!is_array($snapshot)) {
            return false;
        }

        $lineItemsMeta = $this->computePosInvoiceLineMetaFromSnapshot($items, $snapshot);
        $this->persistPosInvoiceDiscountNotes($invoiceId, $snapshot, $lineItemsMeta);

        return true;
    }

    /**
     * Build and create a POS invoice from vp_orders (used by AJAX and checkout).
     */
    public function createAutoInvoiceForOrder(string $orderNumber, string $customInvoiceNumber = '', bool $forceFinal = false): array
    {
        global $invoiceModel, $ordersModel, $paymentModel;

        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return ['success' => false, 'message' => 'Order number missing'];
        }

        $existing = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
        if ($existing) {
            $invoiceId = (int)$existing['id'];
            $repaired = $this->repairPosInvoiceMetadataForOrder($invoiceId, $orderNumber);

            return [
                'success' => true,
                'invoice_id' => $invoiceId,
                'invoice_number' => $existing['invoice_number'] ?? '',
                'repaired' => $repaired,
            ];
        }

        $paymentStage = $paymentModel->getLatestPaymentStage($orderNumber);
        $status = $forceFinal || (strtolower(trim($paymentStage)) === 'final') ? 'final' : 'proforma';

        $items = $ordersModel->getOrderByOrderNumber($orderNumber);
        if (empty($items)) {
            return ['success' => false, 'message' => 'Order not found in vp_orders'];
        }

        $info = $ordersModel->getAddressInfoByOrderNumber($orderNumber);
        if (empty($info['id'])) {
            return ['success' => false, 'message' => 'Order info not found'];
        }

        $_POST = [
            'invoice_date' => date('Y-m-d'),
            'customer_id' => $items[0]['customer_id'],
            'vp_order_info_id' => $info['id'],
            'status' => $status,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
        ];
        $customInvoiceNumber = trim($customInvoiceNumber);
        if ($customInvoiceNumber !== '') {
            $_POST['custom_invoice_number'] = $customInvoiceNumber;
        }

        $checkoutSnapshot = $_SESSION['pos_checkout_invoice_snapshot'] ?? null;
        $useSnapshot = is_array($checkoutSnapshot)
            && trim((string)($checkoutSnapshot['order_number'] ?? '')) === $orderNumber;

        if (!$useSnapshot) {
            $rebuiltSnapshot = $this->buildPosInvoiceSnapshotFromOrder($orderNumber, $items, $info);
            if (is_array($rebuiltSnapshot)) {
                $checkoutSnapshot = $rebuiltSnapshot;
                $useSnapshot = true;
            }
        }

        if ($useSnapshot) {
            $this->buildInvoicePostFromCheckoutSnapshot($items, $checkoutSnapshot, $info);
        } else {
            require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
            $applyExportGst = null;
            foreach ($items as $it) {
                $_POST['order_number'][] = $it['order_number'];
                $_POST['item_code'][] = $it['item_code'];
                $_POST['item_name'][] = $it['title'];
                $_POST['hsn'][] = $it['hsn'];
                $_POST['quantity'][] = $it['quantity'];

                $qty = max(1, (int)$it['quantity']);
                $unit = pos_order_pretax_unit_price($it, 'disc');

                $_POST['unit_price'][] = $unit;
                $_POST['tax_rate'][] = $it['gst'];
                $gstPlan = invoice_resolve_gst_component_plan($info, (float)$it['gst'], $applyExportGst);
                $_POST['cgst'][] = $gstPlan['cgst_rate'];
                $_POST['sgst'][] = $gstPlan['sgst_rate'];
                $_POST['igst'][] = $gstPlan['igst_rate'];
                $_POST['box_no'][] = '';
                $_POST['currency'][] = $it['currency'];

                $_POST['subtotal'] += $unit * $qty;
                $_POST['tax_amount'] += ($unit * $qty) * ($it['gst'] / 100);
            }

            $_POST['total_amount'] = $_POST['subtotal'] + $_POST['tax_amount'];
        }

        $lineItemsMeta = $useSnapshot
            ? $this->computePosInvoiceLineMetaFromSnapshot($items, $checkoutSnapshot)
            : [];
        $result = $this->invoiceCreationService()->createFromPost($_POST, [
            'source' => 'pos',
            'discount_meta' => $useSnapshot ? $checkoutSnapshot : null,
            'line_items_meta' => $lineItemsMeta,
            'duplicate_order_check' => true,
            'clear_invoice_session' => false,
            'update_order_invoice_id' => true,
        ]);
        unset($_SESSION['pos_checkout_invoice_snapshot']);

        return $result;
    }
    public function create_auto_from_order1()
    {
        global $invoiceModel, $ordersModel, $paymentModel;

        $data = json_decode(file_get_contents('php://input'), true);
        $orderNumber = trim((string)($data['orderid'] ?? ''));
        if ($orderNumber === '') {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }

        $orderItems = $ordersModel->getOrderByOrderNumber($orderNumber);
        if (empty($orderItems)) {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $payment = $paymentModel->findLatestByOrderNumber($orderNumber);
        $stage = $payment['payment_stage'] ?? 'final';
        $status = ($stage === 'final') ? 'final' : 'proforma';

        $invoiceId = $invoiceModel->findInvoiceIdByOrderNumber($orderNumber);
        if ($invoiceId > 0) {
            echo json_encode([
                'success' => true,
                'invoice_id' => $invoiceId,
            ]);
            exit;
        }

        $info = $ordersModel->getAddressInfoByOrderNumber($orderNumber);
        require_once __DIR__ . '/../helpers/invoice/invoice_gst.php';
        $_POST = [
            'invoice_date' => date('Y-m-d'),
            'customer_id' => $orderItems[0]['customer_id'],
            'vp_order_info_id' => $info['id'] ?? ($orderItems[0]['vp_order_info_id'] ?? 0),
            'status' => $status,
            'subtotal' => 0,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
        ];

        foreach ($orderItems as $i => $item) {

            $_POST['order_number'][] = $item['order_number'];
            $_POST['item_code'][] = $item['item_code'];
            $_POST['item_name'][] = $item['title'];
            $_POST['hsn'][] = $item['hsn'];
            $_POST['quantity'][] = $item['quantity'];

            $unit = pos_order_pretax_unit_price($item, 'disc');

            $_POST['unit_price'][] = $unit;
            $_POST['tax_rate'][] = $item['gst'];

            $gstPlan = invoice_resolve_gst_component_plan(is_array($info) ? $info : null, (float)$item['gst'], null);
            $_POST['cgst'][] = $gstPlan['cgst_rate'];
            $_POST['sgst'][] = $gstPlan['sgst_rate'];
            $_POST['igst'][] = $gstPlan['igst_rate'];

            $_POST['box_no'][] = '';
            $_POST['currency'][] = $item['currency'];

            $_POST['subtotal'] += $unit * $item['quantity'];
            $_POST['tax_amount'] += ($unit * $item['quantity']) * ($item['gst'] / 100);
        }

        $_POST['total_amount'] = $_POST['subtotal'] + $_POST['tax_amount'];

        return $this->createPost();
    }
    public function createPost()
    {
        is_login();
        header('Content-Type: application/json');
        echo json_encode($this->createPostInternal());
        exit;
    }

    private function createPostInternal(): array
    {
        return $this->invoiceCreationService()->createFromPost($_POST, [
            'source' => 'pos',
            'duplicate_order_check' => true,
            'clear_invoice_session' => true,
            'update_order_invoice_id' => true,
        ]);
    }

    private function invoiceCreationService(): InvoiceCreationService
    {
        global $conn, $invoiceModel, $ordersModel, $commanModel;
        require_once __DIR__ . '/../helpers/invoice/InvoiceCreationService.php';

        return new InvoiceCreationService($conn, $invoiceModel, $ordersModel, $commanModel);
    }

    private function getCurrencyByCode($code)
    {
        global $commanModel;
        return $commanModel->getRecordByField('currency_master', 'currency_code', strtoupper($code));
    }
}
