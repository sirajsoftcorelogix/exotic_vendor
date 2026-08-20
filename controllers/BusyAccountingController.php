<?php

require_once __DIR__ . '/../models/busy/BusyAccounting.php';
require_once __DIR__ . '/../helpers/html_helpers.php';

class BusyAccountingController
{
    private $busyModel;

    public function __construct()
    {
        global $conn;
        $this->busyModel = new BusyAccounting($conn);
    }

    /**
     * Access Control: Strictly Admin or users with assigned 'BUSY Accounting' role permission.
     */
    private function checkAccess(): void
    {
        is_login();
        $userId = (int)($_SESSION['user']['id'] ?? 0);

        $hasAccess = isAdministratorUser() ||
            hasPermission($userId, 'BUSY Accounting', 'view') ||
            hasPermission($userId, 'BUSY Accounting', 'list') ||
            canTopManagementAccess();

        if (!$hasAccess) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $isAjax = (isset($headers['X-Requested-With']) && strtolower($headers['X-Requested-With']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
            $accept = $_SERVER['HTTP_ACCEPT'] ?? ($headers['Accept'] ?? '');
            $wantsJson = $isAjax || stripos($accept, 'application/json') !== false;

            if ($wantsJson) {
                vendorJsonResponse(['success' => false, 'message' => 'Access denied. You do not have permission to access BUSY Accounting.'], 403);
            } else {
                $_SESSION['flash_error'] = 'Access denied. You do not have permission to access BUSY Accounting.';
                header('Location: ' . base_url('index.php?page=dashboard'));
                exit;
            }
        }
    }

    /**
     * Main Dashboard Page (Organization-wide BUSY Accounting)
     */
    public function index(): void
    {
        $this->checkAccess();

        $today = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        $filters = [
            'start_date'   => isset($_GET['start_date']) ? trim($_GET['start_date']) : $firstOfMonth,
            'end_date'     => isset($_GET['end_date']) ? trim($_GET['end_date']) : $today,
            'voucher_type' => isset($_GET['voucher_type']) ? trim($_GET['voucher_type']) : 'all',
            'search'       => isset($_GET['search']) ? trim($_GET['search']) : '',
            'page'         => isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1,
            'limit'        => isset($_GET['limit']) ? (int)$_GET['limit'] : 25
        ];

        $vouchersData = $this->busyModel->getAccountingVouchers($filters);
        $kpis         = $this->busyModel->getKPIs($filters);

        renderTemplate('views/busy/accounting_review.php', [
            'filters'     => $filters,
            'vouchers'    => $vouchersData['vouchers'],
            'pagination'  => [
                'total_records' => $vouchersData['total_records'],
                'total_pages'   => $vouchersData['total_pages'],
                'current_page'  => $vouchersData['current_page'],
                'limit'         => $vouchersData['limit']
            ],
            'kpis'        => $kpis,
            'is_admin'    => isAdministratorUser()
        ]);
    }

    /**
     * AJAX endpoint to inspect voucher line items, actual BUSY XML, and JSON preview
     */
    public function get_details_ajax(): void
    {
        $this->checkAccess();

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        $type = isset($_GET['type']) ? trim($_GET['type']) : 'sales';

        if ($id <= 0) {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid voucher ID'], 400);
        }

        require_once __DIR__ . '/../generate-xml.php';
        $generator = new BusyXmlGenerator();

        if ($type === 'sales_return') {
            $details = $this->busyModel->getSalesReturnDetails($id);
            if (!$details) {
                vendorJsonResponse(['success' => false, 'message' => 'Sales return not found'], 404);
            }

            // Generate exact XML exported to BUSY
            $xmlPreview = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $generator->generateSalesReturn($details, $details['items'] ?? []);

            // Generate JSON preview structure matching api_fetch_vouchers
            $jsonPreview = [
                'id' => (int)$details['id'],
                'return_number' => (string)$details['return_number'],
                'SalesReturnVchNo' => (string)($details['invoice_number'] ?? ''),
                'order_number' => (string)($details['order_number'] ?? ''),
                'invoice_id' => isset($details['invoice_id']) ? (int)$details['invoice_id'] : null,
                'return_date' => (string)$details['return_date'],
                'return_type' => (string)($details['return_type'] ?? ''),
                'remarks' => (string)($details['remarks'] ?? ''),
                'status' => (string)($details['status'] ?? ''),
                'stock_applied' => (int)($details['stock_applied'] ?? 0),
                'created_at' => (string)($details['created_at'] ?? ''),
                'updated_at' => (string)($details['updated_at'] ?? ''),
                'sales_return_items' => array_map(function ($item) {
                    return [
                        'id' => (int)($item['id'] ?? 0),
                        'sales_return_id' => (int)($item['sales_return_id'] ?? 0),
                        'invoice_item_id' => isset($item['invoice_item_id']) ? (int)$item['invoice_item_id'] : null,
                        'product_id' => isset($item['product_id']) ? (int)$item['product_id'] : null,
                        'item_code' => (string)($item['item_code'] ?? $item['item_name'] ?? ''),
                        'return_qty' => (float)($item['return_qty'] ?? 0),
                        'stock_applied_qty' => (float)($item['stock_applied_qty'] ?? 0),
                        'sort_order' => (int)($item['sort_order'] ?? 0)
                    ];
                }, $details['items'] ?? [])
            ];
        } else {
            $details = $this->busyModel->getInvoiceDetails($id);
            if (!$details) {
                vendorJsonResponse(['success' => false, 'message' => 'Invoice not found'], 404);
            }

            // Generate exact XML exported to BUSY
            $xmlPreview = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $generator->generate($details, $details['items'] ?? []);

            $customerName = trim(($details['first_name'] ?? '') . ' ' . ($details['last_name'] ?? ''));
            if ($customerName === '') $customerName = 'Walk-in Customer';

            $partyName = $details['party_name'] ?? $details['payment_type'] ?? $customerName;

            // Generate JSON preview structure matching api_fetch_vouchers
            $jsonPreview = [
                'Series Name' => 'Main Company',
                'VchDate' => date('d-M-Y', strtotime($details['invoice_date'])),
                'VchNo.' => $details['invoice_number'],
                'Sales Type' => 'Sales',
                'Party Name' => $partyName,
                'GSTIN' => $details['gstin'] ?? '',
                'Material Centre' => 'Main Location',
                'Currency' => $details['currency'] ?? 'INR',
                'Shipping Details' => [
                    //'Party Name' => $customerName,
                    'Party Name' => $partyName,
                    'Address' => trim(($details['address_line1'] ?? '') . ' ' . ($details['address_line2'] ?? '')),
                    'PinCode' => $details['zipcode'] ?? '',
                    'State' => $details['state'] ?? '',
                    'Country' => $details['country'] ?? 'INDIA',
                    'Email ID' => $details['email'] ?? '',
                    'Mobile No.' => $details['mobile'] ?? '',
                    'GSTIN' => $details['gstin'] ?? ''
                ],
                'Item Details' => array_map(function ($item) {
                    $qty = (float)($item['quantity'] ?? 0);
                    $price = (float)($item['unit_price'] ?? 0);
                    $amt = (float)($item['line_total'] ?? ($qty * $price));
                    return [
                        'Item Name' => $item['item_name'] ?? '',
                        'HSN Code' => $item['hsn'] ?? '',
                        'Qty' => $qty,
                        'Unit' => 'PCS',
                        'MRP' => $price,
                        'Sales Price' => $price,
                        'Amount' => round($amt, 2),
                        'GST Rate' => (float)($item['tax_rate'] ?? 0),
                        'Discount' => 0.0,
                        'account_group' => $item['account_group_name'] ?? ''
                    ];
                }, $details['items'] ?? [])
            ];
        }

        vendorJsonResponse([
            'success' => true,
            'type' => $type,
            'details' => $details,
            'xml_preview' => $xmlPreview,
            'json_preview' => $jsonPreview
        ]);
    }

    /**
     * Download Batch BUSY XML (ZIP or Consolidated file) for selected date range and filters
     */
    public function download_xml_batch(): void
    {
        $this->checkAccess();

        $today = date('Y-m-d');
        $firstOfMonth = date('Y-m-01');

        $filters = [
            'start_date'   => isset($_GET['start_date']) ? trim($_GET['start_date']) : $firstOfMonth,
            'end_date'     => isset($_GET['end_date']) ? trim($_GET['end_date']) : $today,
            'voucher_type' => isset($_GET['voucher_type']) ? trim($_GET['voucher_type']) : 'all',
            'search'       => isset($_GET['search']) ? trim($_GET['search']) : '',
            'page'         => 1,
            'limit'        => 5000 // Get all for download
        ];

        $format = isset($_GET['format']) ? trim($_GET['format']) : 'zip'; // zip or consolidated

        $vouchersData = $this->busyModel->getAccountingVouchers($filters);
        $vouchersList = $vouchersData['vouchers'];

        if (empty($vouchersList)) {
            http_response_code(404);
            exit('No accounting vouchers found for the selected filters.');
        }

        require_once __DIR__ . '/../generate-xml.php';
        $generator = new BusyXmlGenerator();

        // Mode 1: Consolidated XML single file
        if ($format === 'consolidated') {
            $preparedVouchers = [];
            foreach ($vouchersList as $v) {
                if ($v['voucher_type'] === 'sales_return') {
                    $details = $this->busyModel->getSalesReturnDetails($v['id']);
                    if ($details) {
                        $preparedVouchers[] = [
                            'type' => 'sales_return',
                            'data' => $details,
                            'items' => $details['items'] ?? []
                        ];
                    }
                } else {
                    $details = $this->busyModel->getInvoiceDetails($v['id']);
                    if ($details) {
                        $preparedVouchers[] = [
                            'type' => 'invoice',
                            'data' => $details,
                            'items' => $details['items'] ?? []
                        ];
                    }
                }
            }

            $xmlContent = $generator->generateConsolidated($preparedVouchers);
            $filename = 'busy_vouchers_' . $filters['start_date'] . '_to_' . $filters['end_date'] . '.xml';

            header('Content-Type: application/xml; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($filename) . '"');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            echo $xmlContent;
            exit;
        }

        // Mode 2: ZIP archive of individual files
        $tempDir = sys_get_temp_dir() . '/busy_batch_' . uniqid();
        if (!mkdir($tempDir, 0755, true)) {
            http_response_code(500);
            exit('Failed to create temporary directory for ZIP export.');
        }

        $xmlFiles = [];

        try {
            foreach ($vouchersList as $v) {
                if ($v['voucher_type'] === 'sales_return') {
                    $details = $this->busyModel->getSalesReturnDetails($v['id']);
                    if ($details) {
                        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $generator->generateSalesReturn($details, $details['items'] ?? []);
                        $filename = 'SR_' . preg_replace('/[\/\\:*?"<>|]/', '_', $v['voucher_no']) . '.xml';
                        $filepath = $tempDir . '/' . $filename;
                        if (file_put_contents($filepath, $xml) !== false) {
                            $xmlFiles[] = ['path' => $filepath, 'name' => $filename];
                        }
                    }
                } else {
                    $details = $this->busyModel->getInvoiceDetails($v['id']);
                    if ($details) {
                        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $generator->generate($details, $details['items'] ?? []);
                        $filename = 'INV_' . preg_replace('/[\/\\:*?"<>|]/', '_', $v['voucher_no']) . '.xml';
                        $filepath = $tempDir . '/' . $filename;
                        if (file_put_contents($filepath, $xml) !== false) {
                            $xmlFiles[] = ['path' => $filepath, 'name' => $filename];
                        }
                    }
                }
            }

            if (empty($xmlFiles)) {
                http_response_code(404);
                exit('No valid XML files generated');
            }

            $zipFilename = 'busy_vouchers_' . $filters['start_date'] . '_to_' . $filters['end_date'] . '.zip';
            $zipPath = sys_get_temp_dir() . '/' . $zipFilename;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                http_response_code(500);
                exit('Failed to create ZIP archive');
            }

            foreach ($xmlFiles as $file) {
                $zip->addFile($file['path'], $file['name']);
            }
            $zip->close();

            // Stream ZIP file
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . htmlspecialchars($zipFilename) . '"');
            header('Content-Length: ' . filesize($zipPath));
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
            header('Expires: 0');

            readfile($zipPath);

            // Cleanup
            foreach ($xmlFiles as $file) {
                @unlink($file['path']);
            }
            @rmdir($tempDir);
            @unlink($zipPath);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            exit('Error creating ZIP download: ' . $e->getMessage());
        }
    }
}
