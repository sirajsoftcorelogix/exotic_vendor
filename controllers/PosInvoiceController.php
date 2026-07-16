<?php
require_once 'models/PosInvoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/customer/Customer.php';
require_once 'models/product/product.php';
require_once __DIR__ . '/../models/payment/Payment.php';
// Register in $GLOBALS so methods work when this file is required from a function scope (e.g. payments â†’ create invoice).
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
        global $invoiceModel;

        is_login();

        $isAdminUser = $this->isPosInvoiceAdminUser();
        $filters = [
            'order_number' => $_GET['order_number'] ?? '',
            'status' => $_GET['status'] ?? '',
            'from_date' => $_GET['from_date'] ?? '',
            'to_date' => $_GET['to_date'] ?? '',
            'type' => $_GET['type'] ?? '',
            'customer_id' => $_GET['customer_id'] ?? '',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
            'warehouse_id' => null,
        ];

        if (!$isAdminUser) {
            $filters['warehouse_id'] = $this->getSessionWarehouseId();
        }

        echo json_encode($invoiceModel->searchPosListAjax($filters));
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
        ], 'Invoice â€” ' . ($invoice['invoice_number'] ?? ''));
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
        $totalDiscount = round(
            (float)($snapshot['coupon_discount'] ?? 0)
            + (float)($snapshot['cash_discount'] ?? 0)
            + (float)($snapshot['gift_discount'] ?? 0)
            + (float)($snapshot['line_discount'] ?? 0),
            2
        );

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
                $discInclLine = (float)($it['finalprice'] ?? 0);
                $discInclUnit = $qty >= 1 ? $discInclLine / $qty : $discInclLine;
            }

            if ($listInclUnit === null || $listInclUnit <= 0) {
                $listInclLine = (float)($it['finalprice'] ?? 0);
                if ($listInclLine <= 0) {
                    $listInclLine = (float)($it['itemprice'] ?? 0) * $qty;
                }
                $listInclUnit = $qty >= 1 ? $listInclLine / $qty : $listInclLine;
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

        if ($totalDiscount > 0.001 && count($rows) > 0) {
            $discExtendedSum = 0.0;
            foreach ($rows as $row) {
                $discExtendedSum += round($row['disc_incl_unit'] * $row['qty'], 2);
            }
            if ($discExtendedSum <= 0) {
                $discExtendedSum = round((float)($snapshot['grand_total'] ?? 0), 2);
            }

            $remainingDiscount = $totalDiscount;
            $lastIndex = count($rows) - 1;
            foreach ($rows as $index => &$row) {
                $share = $index === $lastIndex
                    ? round($remainingDiscount, 2)
                    : round(($totalDiscount * round($row['disc_incl_unit'] * $row['qty'], 2)) / max($discExtendedSum, 0.001), 2);
                $remainingDiscount = round($remainingDiscount - $share, 2);

                if ($row['list_incl_unit'] <= $row['disc_incl_unit'] + 0.02) {
                    $row['list_incl_unit'] = round(
                        $row['disc_incl_unit'] + ($share / max(1, $row['qty'])),
                        4
                    );
                }
            }
            unset($row);
        }

        $toPretax = static function (float $inclUnit, float $gstRate): float {
            if ($gstRate > 0) {
                return round($inclUnit / (1 + ($gstRate / 100)), 4);
            }

            return round($inclUnit, 4);
        };

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'item_code' => $row['item_code'],
                'size' => $row['size'],
                'color' => $row['color'],
                'list_unit_pretax' => $toPretax((float)$row['list_incl_unit'], (float)$row['gst_rate']),
                'discounted_unit_pretax' => $toPretax((float)$row['disc_incl_unit'], (float)$row['gst_rate']),
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
     * GST-inclusive unit prices for PDF List / Disc columns.
     *
     * @return array{list: float, disc: float}
     */
    private function posInvoiceResolveLineDisplayPrices(
        array $item,
        int $index,
        array $lineItemsMeta,
        array $posDiscountMeta = []
    ): array {
        $meta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
        $taxRate = (float)($item['tax_rate'] ?? 0);
        $qtyInt = $this->posInvoiceFormatQty($item['quantity'] ?? 1);

        $discIncl = 0.0;
        $listIncl = 0.0;
        if (is_array($meta)) {
            $discIncl = (float)($meta['discounted_unit_incl'] ?? 0);
            $listIncl = (float)($meta['list_unit_incl'] ?? 0);
        }

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
            $totalDiscount = round(
                (float)($posDiscountMeta['coupon_discount'] ?? 0)
                + (float)($posDiscountMeta['cash_discount'] ?? 0)
                + (float)($posDiscountMeta['gift_discount'] ?? 0)
                + (float)($posDiscountMeta['line_discount'] ?? 0),
                2
            );
            if ($totalDiscount > 0.001) {
                $listIncl = round($discIncl + ($totalDiscount / $qtyInt), 2);
            }
        }

        return [
            'list' => round($listIncl, 2),
            'disc' => round($discIncl, 2),
        ];
    }

    /**
     * When stored line meta has list = discounted, rebuild list pretax from summary discounts.
     *
     * @param list<array<string, mixed>> $items
     */
    private function applyPosListPriceFallbackFromDiscountMeta(array &$lineItemsMeta, array $items, array $posMeta): void
    {
        $totalDiscount = round(
            (float)($posMeta['coupon_discount'] ?? 0)
            + (float)($posMeta['cash_discount'] ?? 0)
            + (float)($posMeta['gift_discount'] ?? 0)
            + (float)($posMeta['line_discount'] ?? 0),
            2
        );
        if ($totalDiscount <= 0.001 || count($items) === 0) {
            return;
        }

        $discExtendedSum = 0.0;
        foreach ($items as $item) {
            $discExtendedSum += round((float)($item['line_total'] ?? 0), 2);
        }
        if ($discExtendedSum <= 0) {
            $discExtendedSum = round((float)($posMeta['grand_total'] ?? 0), 2);
        }

        $remainingDiscount = $totalDiscount;
        $lastIndex = count($items) - 1;
        foreach ($items as $index => $item) {
            $qty = max(1, (float)($item['quantity'] ?? 1));
            $gstRate = (float)($item['tax_rate'] ?? 0);
            $discPretax = (float)($item['unit_price'] ?? 0);
            $meta = $this->posInvoiceLineMetaForItem($item, $index, $lineItemsMeta);
            $listPretax = is_array($meta) ? (float)($meta['list_unit_pretax'] ?? 0) : 0.0;
            if ($listPretax <= 0) {
                $listPretax = $discPretax;
            }

            if ($listPretax > $discPretax + 0.02) {
                continue;
            }

            $share = $index === $lastIndex
                ? round($remainingDiscount, 2)
                : round(($totalDiscount * round((float)($item['line_total'] ?? 0), 2)) / max($discExtendedSum, 0.001), 2);
            $remainingDiscount = round($remainingDiscount - $share, 2);

            $discInclUnit = $gstRate > 0 ? $discPretax * (1 + ($gstRate / 100)) : $discPretax;
            $listInclUnit = round($discInclUnit + ($share / $qty), 4);
            $listPretax = $gstRate > 0 ? round($listInclUnit / (1 + ($gstRate / 100)), 4) : round($listInclUnit, 4);

            if (is_array($meta)) {
                $lineItemsMeta[$index]['list_unit_pretax'] = $listPretax;
                $lineItemsMeta[$index]['list_unit_incl'] = round($listInclUnit, 2);
                if ((float)($meta['discounted_unit_incl'] ?? 0) <= 0) {
                    $lineItemsMeta[$index]['discounted_unit_incl'] = round($discInclUnit, 2);
                }
            } else {
                $lineItemsMeta[$index] = [
                    'item_code' => (string)($item['item_code'] ?? ''),
                    'size' => '',
                    'color' => '',
                    'list_unit_pretax' => $listPretax,
                    'discounted_unit_pretax' => $discPretax,
                    'list_unit_incl' => round($listInclUnit, 2),
                    'discounted_unit_incl' => round($discInclUnit, 2),
                ];
            }
        }
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
            $qty = max(1, (int)($it['quantity'] ?? 1));
            $listUnit = (float)($it['itemprice'] ?? 0);
            if ($listUnit <= 0) {
                $listUnit = (float)($it['finalprice'] ?? 0) / $qty;
            }
            $listSubtotal += round($listUnit * $qty, 2);
            $discSubtotal += round((float)($it['finalprice'] ?? 0), 2);
        }

        $couponDiscount = round((float)($orderInfo['coupon_reduce'] ?? 0), 2);
        $giftDiscount = round((float)($orderInfo['giftvoucher_reduce'] ?? 0), 2);
        $credit = round((float)($orderInfo['credit'] ?? 0), 2);

        $cashDiscount = 0.0;
        foreach ($orderItems as $it) {
            $cashDiscount = max($cashDiscount, round((float)($it['custom_reduce'] ?? 0), 2));
        }

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

            $qty = max(1, (int)($it['quantity'] ?? 1));
            if ($kind === 'list') {
                $unit = (float)($it['itemprice'] ?? 0);
                if ($unit <= 0) {
                    $unit = (float)($it['finalprice'] ?? 0) / $qty;
                }
            } else {
                $unit = (float)($it['finalprice'] ?? 0) / $qty;
                if ($unit <= 0) {
                    $unit = (float)($it['itemprice'] ?? 0);
                }
            }
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
    private function buildInvoicePostFromCheckoutSnapshot(array $orderItems, array $snapshot): void
    {
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
                $listInclLine = (float)($it['finalprice'] ?? 0);
                if ($listInclLine <= 0) {
                    $listInclLine = (float)($it['itemprice'] ?? 0) * $qty;
                }
                $inclLine = $listInclLine;
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
            $pretaxUnit = $gstRate > 0 ? $inclUnit / (1 + ($gstRate / 100)) : $inclUnit;
            $pretaxLine = round($pretaxUnit * $qty, 2);
            $taxLine = round($inclLine - $pretaxLine, 2);

            $_POST['order_number'][] = $it['order_number'];
            $_POST['item_code'][] = $it['item_code'];
            $_POST['item_name'][] = $it['title'];
            $_POST['hsn'][] = $it['hsn'];
            $_POST['quantity'][] = $qty;
            $_POST['unit_price'][] = round($pretaxUnit, 4);
            $_POST['tax_rate'][] = $gstRate;
            $_POST['cgst'][] = $gstRate / 2;
            $_POST['sgst'][] = $gstRate / 2;
            $_POST['igst'][] = 0;
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

    private function posInvoiceSummaryLabelRow(
        string $label,
        float $amount,
        string $note = '',
        bool $isGrand = false,
        int $colCount = 13,
        bool $largeFont = false
    ): string {
        $colCount = max(3, $colCount);
        $labelSpan = $colCount - 2;
        $noteHtml = $note !== ''
            ? '<br><span style="font-size:11px;font-weight:normal;color:#555;">' . htmlspecialchars($note) . '</span>'
            : '';
        $bg = $isGrand ? '#f0f0f0' : '#f9f9f9';
        $weight = $largeFont ? 'font-weight:bold;font-size:14px;' : 'font-weight:bold;';
        $borderTop = $isGrand ? 'border-top:2px solid #000;' : '';

        return '
                    <tr style="background:' . $bg . ';' . $borderTop . '">
                        <td colspan="' . $labelSpan . '" class="right" style="text-align:right;padding:8px 10px;border:1px solid #ddd;">'
                            . '<span style="' . $weight . '">' . htmlspecialchars($label) . '</span>' . $noteHtml .
                        '</td>
                        <td colspan="2" class="right" style="text-align:right;padding:8px 10px;border:1px solid #ddd;' . $weight . '">'
                            . number_format($amount, 2) .
                        '</td>
                    </tr>';
    }

    private function posInvoiceCustomDiscountLabel(array $posMeta): string
    {
        $mode = trim((string)($posMeta['custom_discount_mode'] ?? ''));
        $value = round((float)($posMeta['custom_discount_value'] ?? 0), 2);
        if ($mode === 'percent' && $value > 0) {
            $pct = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');

            return 'Custom Discount (' . $pct . '%)';
        }

        return 'Custom Discount (fixed â‚¹)';
    }

    private function posInvoiceCouponLabel(array $posMeta): string
    {
        $name = trim((string)($posMeta['coupon_display_name'] ?? ''));

        return $name !== '' ? 'Coupon (' . $name . ')' : 'Coupon';
    }

    private function buildPosInvoiceAmountSummaryRows(
        array $posMeta,
        float $grandTotal,
        float $taxAmount,
        int $colCount = 13
    ): string {
        $subInclGst = round((float)($posMeta['subtotal_goods'] ?? 0), 2);
        if ($subInclGst <= 0 && $grandTotal > 0) {
            $subInclGst = $grandTotal;
        }
        $coupon = round((float)($posMeta['coupon_discount'] ?? 0), 2);
        $cash = round((float)($posMeta['cash_discount'] ?? 0), 2);
        $gift = round((float)($posMeta['gift_discount'] ?? 0), 2);
        $line = round((float)($posMeta['line_discount'] ?? 0), 2);
        $gst = round((float)($posMeta['gst_total'] ?? 0), 2);
        if ($gst <= 0 && $taxAmount > 0) {
            $gst = round($taxAmount, 2);
        }
        if ($grandTotal <= 0) {
            $grandTotal = $subInclGst;
        }

        $absorbed = !empty($posMeta['discounts_absorbed']);
        $absorbedNote = $absorbed ? '(included in line totals)' : '';
        $hasAnyDiscount = ($coupon + $cash + $gift + $line) > 0.001;
        $showSummary = $subInclGst > 0 || $hasAnyDiscount || $grandTotal > 0;
        if (!$showSummary) {
            return '';
        }

        $rows = '
                    <tr style="background:#ffffff;">
                        <td colspan="' . $colCount . '" style="text-align:left;padding:14px 8px 6px;border:none;border-top:2px solid #000;">
                            <span style="font-size:13px;font-weight:bold;letter-spacing:0.08em;text-transform:uppercase;color:#333;">Summary</span>
                        </td>
                    </tr>';

        if ($absorbed) {
            if ($grandTotal <= 0) {
                $grandTotal = $subInclGst;
            }
            if ($subInclGst <= 0) {
                $subInclGst = $grandTotal;
            }

            $totalDiscount = round($coupon + $cash + $gift + $line, 2);

            $rows .= $this->posInvoiceSummaryLabelRow(
                'Sub total (incl. GST)',
                $subInclGst,
                '',
                false,
                $colCount,
                true
            );
            if ($totalDiscount > 0.001) {
                $rows .= $this->posInvoiceSummaryLabelRow(
                    'Total Discount',
                    $totalDiscount,
                    $absorbedNote,
                    false,
                    $colCount
                );
            }
            if ($gst > 0.001) {
                $rows .= $this->posInvoiceSummaryLabelRow(
                    'Total GST',
                    $gst,
                    $absorbedNote,
                    false,
                    $colCount
                );
            }
            $rows .= $this->posInvoiceSummaryLabelRow('GRAND Total', $grandTotal, '', true, $colCount);

            return $rows;
        }

        $rows .= $this->posInvoiceSummaryLabelRow(
            'Sub total (incl. GST)',
            $subInclGst,
            '',
            false,
            $colCount,
            true
        );
        if ($line > 0.001) {
            $rows .= $this->posInvoiceSummaryLabelRow('Line Discount', $line, '', false, $colCount);
        }

        if ($cash > 0) {
            $rows .= $this->posInvoiceSummaryLabelRow(
                $this->posInvoiceCustomDiscountLabel($posMeta),
                $cash,
                $absorbedNote,
                false,
                $colCount
            );
        }

        if ($coupon > 0) {
            $rows .= $this->posInvoiceSummaryLabelRow(
                $this->posInvoiceCouponLabel($posMeta),
                $coupon,
                $absorbedNote,
                false,
                $colCount
            );
        }

        if ($gift > 0) {
            $rows .= $this->posInvoiceSummaryLabelRow('Gift Voucher', $gift, $absorbedNote, false, $colCount);
        }

        $rows .= $this->posInvoiceSummaryLabelRow('Total GST', $gst, '', false, $colCount);
        $rows .= $this->posInvoiceSummaryLabelRow('GRAND Total', $grandTotal, '', true, $colCount);

        return $rows;
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
        $sumLineTotals = 0.0;
        $lineItemsMeta = $this->parsePosInvoiceLineItemsMeta($invoice['notes'] ?? null);
        $posDiscountMeta = $this->parsePosInvoiceDiscountMeta($invoice['notes'] ?? null);
        if (!empty($posDiscountMeta)) {
            $this->applyPosListPriceFallbackFromDiscountMeta($lineItemsMeta, $items, $posDiscountMeta);
        }
        $usePosItemLayout = !empty($lineItemsMeta) || !empty($invoice['pos_flag']);
        $showDiscPriceColumn = false;
        $posTableColCount = 13;
        if ($usePosItemLayout && (($invoice['status'] ?? '') !== 'proforma')) {
            foreach ($items as $scanIdx => $scanItem) {
                $displayPrices = $this->posInvoiceResolveLineDisplayPrices(
                    $scanItem,
                    $scanIdx,
                    $lineItemsMeta,
                    $posDiscountMeta
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

        // Build item rows
        foreach ($items as $idx => $item) {
            // $amount = $item['quantity'] * $item['unit_price'];
            // $taxAmount = ($amount * $item['tax_rate']) / 100;
            // $lineTotal = $amount + $taxAmount;

            // $totalSubtotal += $amount;
            // $totalTax += $taxAmount;
            // $totalAmount += $lineTotal;
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
            $qtyInt = $this->posInvoiceFormatQty($item['quantity'] ?? 1);
            $totalQuantity += $qtyInt;
            $totalSgstAmt += $item['sgst'];
            $totalCgstAmt += $item['cgst'];
            $totalIgstAmt += $item['igst'];

            $unitPrices = $this->posInvoiceResolveLineUnitPretax($item, $idx, $lineItemsMeta);
            $discUnitPretax = $unitPrices['disc'];
            $displayPrices = $this->posInvoiceResolveLineDisplayPrices($item, $idx, $lineItemsMeta, $posDiscountMeta);
            $listUnitDisplay = $showDiscPriceColumn ? $displayPrices['list'] : $unitPrices['list'];
            $discUnitDisplay = $showDiscPriceColumn ? $displayPrices['disc'] : $discUnitPretax;
            $taxRate = (float)($item['tax_rate'] ?? 0);
            $rateBase = $discUnitPretax > 0 ? $discUnitPretax : (float)($item['unit_price'] ?? 0);
            $lineTotalDisplay = $showDiscPriceColumn
                ? round($discUnitDisplay * $qtyInt, 2)
                : round((float)($item['line_total'] ?? 0), 2);
            $sumLineTotals += $lineTotalDisplay;

            if ($item['igst'] > 0) {
                $igstRate = $rateBase > 0 ? ($item['igst'] / $qtyInt) / ($rateBase / 100) : 0;
                $sgstRate = 0;
                $cgstRate = 0;
            } else {
                $sgstRate = $rateBase > 0 ? ($item['sgst'] / $qtyInt) / ($rateBase / 100) : 0;
                $cgstRate = $rateBase > 0 ? ($item['cgst'] / $qtyInt) / ($rateBase / 100) : 0;
                $igstRate = 0;
            }

            if ($usePosItemLayout) {
                $itemName = htmlspecialchars($item['item_name'] ?? '');
                $hsnCode = trim((string)($item['hsn'] ?? ''));
                $descHtml = $itemName;
                if ($hsnCode !== '') {
                    $descHtml .= '<br><span style="font-size:12px;color:#444;">HSN: '
                        . htmlspecialchars($hsnCode) . '</span>';
                }
                $listPriceCell = '<td class="right">' . number_format($listUnitDisplay, 2) . '</td>';
                $discPriceCell = $showDiscPriceColumn
                    ? '<td class="right">' . number_format($discUnitDisplay, 2) . '</td>'
                    : '';
                $itemsrows .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['box_no'] ?? '') . '</td>
                        <td class="desc">' . $descHtml . '</td>
                        ' . $listPriceCell . $discPriceCell . '
                        <td>' . $qtyInt . '</td>
                        <td class="right">' . number_format($sgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['sgst'], 2) . '</td>
                        <td class="right">' . number_format($cgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['cgst'], 2) . '</td>
                        <td class="right">' . number_format($igstRate, 2) . '</td>
                        <td class="right">' . number_format($item['igst'], 2) . '</td>
                        <td class="right bold">' . number_format($lineTotalDisplay, 2) . '</td>
                    </tr>
            ';
            } else {
            $itemsrows .= '
                    <tr>
                        <td>' . ($idx + 1) . '</td>
                        <td>' . htmlspecialchars($item['box_no'] ?? '') . '</td>
                        <td class="desc">' . htmlspecialchars($item['item_name'] ?? '') . '</td>
                        <td>' . htmlspecialchars($item['hsn'] ?? '') . '</td>
                        <td>' . $qtyInt . '</td>
                        <td class="right">' . number_format($item['unit_price'], 2) . '</td>
                        <td class="right">' . number_format($sgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['sgst'], 2) . '</td>
                        <td class="right">' . number_format($cgstRate, 2) . '</td>
                        <td class="right">' . number_format($item['cgst'], 2) . '</td>
                        <td class="right">' . number_format($igstRate, 2) . '</td>
                        <td class="right">' . number_format($item['igst'], 2) . '</td>
                        <td class="right bold">' . number_format($lineTotalDisplay, 2) . '</td>
                    </tr>
            ';
            }
        }
        if (count($items) < 3) {
            // Add empty rows to maintain table height
            $rowsToAdd = 3 - count($items);
            $emptyPriceCells = $showDiscPriceColumn
                ? '<td>&nbsp;</td><td>&nbsp;</td>'
                : '<td>&nbsp;</td>';
            for ($i = 0; $i < $rowsToAdd; $i++) {
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
        if ($usePosItemLayout && $showDiscPriceColumn && $sumLineTotals > 0.001) {
            $posDiscountMeta['subtotal_goods'] = round($sumLineTotals, 2);
            $posDiscountMeta['grand_total'] = round($sumLineTotals, 2);
        }
        $summaryGrandTotal = round((float)($posDiscountMeta['grand_total'] ?? 0), 2);
        if ($summaryGrandTotal <= 0) {
            $summaryGrandTotal = round((float)$totalAmount, 2);
        }
        $summaryTaxAmount = round((float)($invoice['tax_amount'] ?? 0), 2);
        $tableLineTotal = ($usePosItemLayout && $showDiscPriceColumn && $sumLineTotals > 0.001)
            ? round($sumLineTotals, 2)
            : round((float)$totalAmount, 2);

        // Add row for tax amount totals (below each SGST/CGST/IGST column)
        $posTotalDiscEmpty = ($usePosItemLayout && $showDiscPriceColumn)
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
        $customer = $commanModel->getRecordById('vp_order_info', $invoice['vp_order_info_id'] ?? 0);
        $addressBlocks = invoice_resolve_bill_ship_html(is_array($customer) ? $customer : null);
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
                    "<th>List Price</th>\n        <th>Disc. Price</th>\n        <th>Qty</th>",
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
        $input = json_decode(file_get_contents('php://input'), true);
        $orderNumber = $input['orderid'] ?? null;
        if (!$orderNumber) {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }
        echo json_encode($this->createAutoInvoiceForOrder((string)$orderNumber));
        exit;
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
            return [
                'success' => true,
                'invoice_id' => (int)$existing['id'],
                'invoice_number' => $existing['invoice_number'] ?? '',
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
            $this->buildInvoicePostFromCheckoutSnapshot($items, $checkoutSnapshot);
        } else {
            foreach ($items as $it) {
                $_POST['order_number'][] = $it['order_number'];
                $_POST['item_code'][] = $it['item_code'];
                $_POST['item_name'][] = $it['title'];
                $_POST['hsn'][] = $it['hsn'];
                $_POST['quantity'][] = $it['quantity'];

                $qty = max(1, (int)$it['quantity']);
                $unit = ($it['finalprice'] / (1 + ($it['gst'] / 100))) / $qty;

                $_POST['unit_price'][] = $unit;
                $_POST['tax_rate'][] = $it['gst'];
                $_POST['cgst'][] = $it['gst'] / 2;
                $_POST['sgst'][] = $it['gst'] / 2;
                $_POST['igst'][] = 0;
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

        // âœ… CALL MAIN INVOICE LOGIC ðŸ”¥
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
