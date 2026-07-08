<?php

require_once 'models/direct_purchase/directPurchase.php';
require_once 'models/direct_purchase/directPurchaseReturn.php';
require_once 'models/vendor/vendor.php';
require_once 'models/product/product.php';
require_once 'models/comman/tables.php';
require_once 'helpers/exotic_india_api.php';

$directPurchaseModel = new DirectPurchase($conn);
$directPurchaseReturnModel = new DirectPurchaseReturn($conn);
$directPurchaseVendorModel = new vendor($conn);

class DirectPurchaseController
{
    public function index()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20;

        $filters = [
            'search_text' => isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '',
            'invoice_date_from' => isset($_GET['invoice_date_from']) ? trim((string) $_GET['invoice_date_from']) : '',
            'invoice_date_to' => isset($_GET['invoice_date_to']) ? trim((string) $_GET['invoice_date_to']) : '',
            'added_date_from' => isset($_GET['added_date_from']) ? trim((string) $_GET['added_date_from']) : '',
            'added_date_to' => isset($_GET['added_date_to']) ? trim((string) $_GET['added_date_to']) : '',
            'vendor_id' => isset($_GET['vendor_id']) ? (int) $_GET['vendor_id'] : 0,
            'created_by' => isset($_GET['created_by']) ? (int) $_GET['created_by'] : 0,
        ];

        $filters = $this->sanitizeDirectPurchaseListDateFilters($filters);

        $result = $directPurchaseModel->searchPurchases($filters, $pageNo, $limit);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        global $directPurchaseVendorModel;
        $vendors = $directPurchaseVendorModel->getAllVendors();

        $users = [];
        $res = $conn->query(
            'SELECT DISTINCT p.created_by AS id, cu.name
             FROM vp_direct_purchases p
             LEFT JOIN vp_users cu ON cu.id = p.created_by AND cu.is_deleted = 0
             WHERE p.created_by > 0
             ORDER BY cu.name'
        );
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $users[(int) $row['id']] = $row['name'] ?: ('User #' . (int) $row['id']);
            }
        }

        renderTemplate('views/direct_purchase/index.php', [
            'purchases' => $result['rows'],
            'total_records' => $result['total'],
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'filters' => $filters,
            'vendors' => $vendors,
            'users' => $users,
        ], 'Direct purchases');
    }

    public function productSearch()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;
        $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
        if (function_exists('mb_strlen')) {
            if (mb_strlen($q) < 2) {
                echo json_encode([]);
                exit;
            }
        } elseif (strlen($q) < 2) {
            echo json_encode([]);
            exit;
        }
        $productModel = new product($conn);
        $items = $productModel->getProductItemsForAutocomplete($q, 40);
        echo json_encode(is_array($items) ? $items : []);
        exit;
    }

    public function fetchLinePrice()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;

        $itemCode = trim((string) ($_GET['item_code'] ?? $_POST['item_code'] ?? ''));
        $sku = trim((string) ($_GET['sku'] ?? $_POST['sku'] ?? ''));
        $color = trim((string) ($_GET['color'] ?? $_POST['color'] ?? ''));
        $size = trim((string) ($_GET['size'] ?? $_POST['size'] ?? ''));

        $productModel = new product($conn);

        if ($itemCode === '' && $sku !== '') {
            $bySku = $productModel->getProductByskuExact($sku);
            if (is_array($bySku)) {
                $itemCode = trim((string) ($bySku['item_code'] ?? ''));
                if ($color === '') {
                    $color = trim((string) ($bySku['color'] ?? ''));
                }
                if ($size === '') {
                    $size = trim((string) ($bySku['size'] ?? ''));
                }
            }
        }

        if ($itemCode === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Select a product or enter a SKU with a linked item code first.',
            ]);
            exit;
        }

        $result = $productModel->refreshVariantCostFromVendorApi($itemCode, $size, $color);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function fetchPendingOrdersForSku()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;

        $itemCode = trim((string) ($_GET['item_code'] ?? $_POST['item_code'] ?? ''));
        $sku = trim((string) ($_GET['sku'] ?? $_POST['sku'] ?? ''));
        $color = trim((string) ($_GET['color'] ?? $_POST['color'] ?? ''));
        $size = trim((string) ($_GET['size'] ?? $_POST['size'] ?? ''));

        $productModel = new product($conn);
        if ($itemCode === '' && $sku !== '') {
            $bySku = $productModel->getProductByskuExact($sku);
            if (is_array($bySku)) {
                $itemCode = trim((string) ($bySku['item_code'] ?? ''));
                if ($color === '') {
                    $color = trim((string) ($bySku['color'] ?? ''));
                }
                if ($size === '') {
                    $size = trim((string) ($bySku['size'] ?? ''));
                }
            }
        }

        if ($itemCode === '') {
            echo json_encode([
                'success' => false,
                'message' => 'Select a product or enter a SKU with a linked item code first.',
            ]);
            exit;
        }

        $toDate = isset($_GET['to_date']) ? (int) $_GET['to_date'] : time();
        $fromDate = isset($_GET['from_date']) ? (int) $_GET['from_date'] : strtotime('-90 days');
        if ($fromDate <= 0) {
            $fromDate = strtotime('-90 days');
        }
        if ($toDate <= 0) {
            $toDate = time();
        }
        if ($fromDate > $toDate) {
            $tmp = $fromDate;
            $fromDate = $toDate;
            $toDate = $tmp;
        }

        $postFields = [
            'itemcode' => $itemCode,
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
        if ($size !== '') {
            $postFields['size'] = $size;
        }
        if ($color !== '') {
            $postFields['color'] = $color;
        }

        $api = exotic_india_api_post(
            'order/itemfetch',
            http_build_query($postFields),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if (!$api['success']) {
            echo json_encode([
                'success' => false,
                'message' => $api['message'] ?? 'Could not fetch pending orders from vendor API.',
            ]);
            exit;
        }

        $apiOrders = $this->normalizeItemFetchOrders($api['data'] ?? [], $api['raw'] ?? '');
        $apiOrders = $this->enrichItemFetchOrdersWithQty($itemCode, $size, $color, $apiOrders, $sku);
        $orders = $this->compareItemFetchOrdersWithLocal($conn, $itemCode, $size, $color, $apiOrders, $sku);
        $needsImport = array_values(array_filter($orders, static function (array $row): bool {
            return !empty($row['needs_import']);
        }));

        echo json_encode([
            'success' => true,
            'item_code' => $itemCode,
            'sku' => $sku,
            'size' => $size,
            'color' => $color,
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'orders' => $orders,
            'needs_import_count' => count($needsImport),
            'total' => count($orders),
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function importOrderForDirectPurchase()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $orderNumber = trim((string) ($_GET['orderid'] ?? $_POST['orderid'] ?? $_GET['order_number'] ?? $_POST['order_number'] ?? ''));
        if ($orderNumber === '') {
            echo json_encode(['success' => false, 'message' => 'Order number missing']);
            exit;
        }

        require_once __DIR__ . '/OrdersController.php';
        $ordersController = new OrdersController();
        $result = $ordersController->importSingleOrderForCheckoutWithRetry($orderNumber);
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * itemfetch returns either order detail objects or a bare JSON array of order IDs, e.g. [2938539, 2931833].
     *
     * @param array<string, mixed> $decoded
     * @return list<array{order_number:string,qty:float}>
     */
    private function normalizeItemFetchOrders(array $decoded, string $raw = ''): array
    {
        if ($decoded === [] && $raw !== '') {
            $fromRaw = json_decode($raw, true);
            if (is_array($fromRaw)) {
                $decoded = $fromRaw;
            }
        }

        if ($decoded !== [] && array_keys($decoded) === range(0, count($decoded) - 1)) {
            $first = reset($decoded);
            if (is_scalar($first) && !is_array($first)) {
                $out = [];
                foreach ($decoded as $id) {
                    if (!is_scalar($id)) {
                        continue;
                    }
                    $orderNumber = trim((string) $id);
                    if ($orderNumber === '') {
                        continue;
                    }
                    $out[] = [
                        'order_number' => $orderNumber,
                        'qty' => 0.0,
                    ];
                }
                return $out;
            }
        }

        $list = [];
        if (isset($decoded['orders']) && is_array($decoded['orders'])) {
            $ordersNode = $decoded['orders'];
            if ($ordersNode !== [] && array_keys($ordersNode) !== range(0, count($ordersNode) - 1)) {
                $list = array_values($ordersNode);
            } else {
                $list = $ordersNode;
            }
        } elseif (isset($decoded['data']) && is_array($decoded['data'])) {
            $list = $decoded['data'];
        } elseif (isset($decoded['result']) && is_array($decoded['result'])) {
            $list = $decoded['result'];
        } elseif ($decoded !== [] && array_keys($decoded) === range(0, count($decoded) - 1)) {
            $list = $decoded;
        }

        $out = [];
        foreach ($list as $row) {
            if (is_scalar($row)) {
                $orderNumber = trim((string) $row);
                if ($orderNumber === '') {
                    continue;
                }
                $out[] = [
                    'order_number' => $orderNumber,
                    'qty' => 0.0,
                ];
                continue;
            }
            if (!is_array($row)) {
                continue;
            }
            $orderNumber = trim((string) ($row['orderid'] ?? $row['order_id'] ?? $row['order_number'] ?? $row['order_no'] ?? ''));
            if ($orderNumber === '') {
                continue;
            }
            $qty = (float) ($row['qty'] ?? $row['quantity'] ?? $row['order_qty'] ?? $row['item_qty'] ?? 0);
            $lineSku = trim((string) ($row['sku'] ?? $row['itemcode'] ?? $row['item_code'] ?? ''));
            $out[] = [
                'order_number' => $orderNumber,
                'qty' => $qty,
                'sku' => $lineSku,
            ];
        }

        return $out;
    }

    /**
     * itemfetch only returns order IDs; load line qty from order/fetch cart when missing.
     *
     * @param list<array{order_number:string,qty:float,sku?:string}> $orders
     * @return list<array{order_number:string,qty:float,sku?:string}>
     */
    private function enrichItemFetchOrdersWithQty(
        string $itemCode,
        string $size,
        string $color,
        array $orders,
        string $fallbackSku = ''
    ): array {
        $maxFetches = 25;
        $fetched = 0;

        foreach ($orders as &$row) {
            $hasQty = (float) ($row['qty'] ?? 0) > 0;
            $hasSku = trim((string) ($row['sku'] ?? '')) !== '';
            if ($hasQty && $hasSku) {
                continue;
            }
            if ($fetched >= $maxFetches) {
                break;
            }

            $orderNumber = trim((string) ($row['order_number'] ?? ''));
            if ($orderNumber === '') {
                continue;
            }

            $details = $this->fetchLineDetailsForOrderFromVendorApi($orderNumber, $itemCode, $size, $color);
            if ($details !== null) {
                $row['qty'] = $details['qty'];
                $row['sku'] = $details['sku'];
            } elseif ($fallbackSku !== '' && !$hasSku) {
                $row['sku'] = $fallbackSku;
            }
            $fetched++;
        }
        unset($row);

        return $orders;
    }

    /**
     * @return array{qty:float,sku:string}|null
     */
    private function fetchLineDetailsForOrderFromVendorApi(
        string $orderNumber,
        string $itemCode,
        string $size,
        string $color
    ): ?array {
        $api = exotic_india_api_post(
            'order/fetch',
            http_build_query([
                'makeRequestOf' => 'vendors-orderjson',
                'orderid' => $orderNumber,
            ]),
            ['Content-Type: application/x-www-form-urlencoded']
        );

        if (!$api['success']) {
            return null;
        }

        $order = $this->extractVendorOrderNode($api['data'] ?? [], $orderNumber);
        if ($order === null) {
            return null;
        }

        $cart = $order['cart'] ?? [];
        if (!is_array($cart)) {
            return null;
        }

        $totalQty = 0.0;
        $lineSku = '';
        $matched = false;
        foreach ($cart as $item) {
            if (!is_array($item)) {
                continue;
            }
            $lineItemCode = trim((string) ($item['itemcode'] ?? $item['item_code'] ?? ''));
            if (strcasecmp($lineItemCode, $itemCode) !== 0) {
                continue;
            }
            $lineSize = trim((string) ($item['size'] ?? ''));
            $lineColor = trim((string) ($item['color'] ?? ''));
            if ($size !== '' && strcasecmp($lineSize, $size) !== 0) {
                continue;
            }
            if ($color !== '' && strcasecmp($lineColor, $color) !== 0) {
                continue;
            }
            $totalQty += (float) ($item['qty'] ?? $item['quantity'] ?? 0);
            if ($lineSku === '') {
                $lineSku = trim((string) ($item['sku'] ?? $item['itemcode'] ?? $item['item_code'] ?? ''));
            }
            $matched = true;
        }

        if (!$matched) {
            return null;
        }

        return [
            'qty' => $totalQty,
            'sku' => $lineSku !== '' ? $lineSku : $itemCode,
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    private function extractVendorOrderNode(array $data, string $orderNumber): ?array
    {
        if (isset($data['orders']) && is_array($data['orders'])) {
            $orders = $data['orders'];
            if (isset($orders[$orderNumber]) && is_array($orders[$orderNumber])) {
                return $orders[$orderNumber];
            }
            foreach ($orders as $order) {
                if (!is_array($order)) {
                    continue;
                }
                if ((string) ($order['orderid'] ?? '') === $orderNumber) {
                    return $order;
                }
            }
        }

        if (isset($data['orderid']) || isset($data['cart'])) {
            return $data;
        }

        return null;
    }

    /**
     * @param list<array{order_number:string,qty:float}> $apiOrders
     * @return list<array<string, mixed>>
     */
    private function compareItemFetchOrdersWithLocal(
        mysqli $conn,
        string $itemCode,
        string $size,
        string $color,
        array $apiOrders,
        string $fallbackSku = ''
    ): array {
        $stmt = $conn->prepare(
            'SELECT COALESCE(SUM(quantity), 0) AS local_qty, COUNT(*) AS line_count
             FROM vp_orders
             WHERE order_number = ? AND item_code = ?
               AND (TRIM(COALESCE(size, "")) = ? OR ? = "")
               AND (TRIM(COALESCE(color, "")) = ? OR ? = "")'
        );
        if (!$stmt) {
            return array_map(static function (array $row): array {
                return array_merge($row, [
                    'in_local_db' => false,
                    'local_qty' => 0.0,
                    'needs_import' => true,
                    'match_status' => 'unknown',
                ]);
            }, $apiOrders);
        }

        $out = [];
        foreach ($apiOrders as $row) {
            $orderNumber = $row['order_number'];
            $apiQty = (float) $row['qty'];
            $localQty = 0.0;
            $lineCount = 0;

            $stmt->bind_param('ssssss', $orderNumber, $itemCode, $size, $size, $color, $color);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && ($dbRow = $res->fetch_assoc())) {
                $localQty = (float) ($dbRow['local_qty'] ?? 0);
                $lineCount = (int) ($dbRow['line_count'] ?? 0);
            }

            $inLocal = $lineCount > 0;
            $qtyMatch = $inLocal && abs($localQty - $apiQty) < 0.001;
            $needsImport = !$inLocal || !$qtyMatch;

            if (!$inLocal) {
                $matchStatus = 'missing';
            } elseif (!$qtyMatch) {
                $matchStatus = 'qty_mismatch';
            } else {
                $matchStatus = 'matched';
            }

            $out[] = [
                'order_number' => $orderNumber,
                'sku' => trim((string) ($row['sku'] ?? '')) !== ''
                    ? trim((string) $row['sku'])
                    : ($fallbackSku !== '' ? $fallbackSku : $itemCode),
                'qty' => $apiQty,
                'in_local_db' => $inLocal,
                'local_qty' => $localQty,
                'needs_import' => $needsImport,
                'match_status' => $matchStatus,
            ];
        }
        $stmt->close();

        return $out;
    }

    public function add()
    {
        is_login();
        global $conn;
        global $directPurchaseVendorModel;
        $vendors = $directPurchaseVendorModel->getActiveVendorsWithExoticVendorId();
        $commanModel = new Tables($conn);
        $warehouses = $commanModel->get_exotic_address();
        renderTemplate('views/direct_purchase/form.php', [
            'purchase' => null,
            'items' => [],
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'is_edit' => false,
            'purchase_locked' => false,
        ], 'Add direct purchase');
    }

    public function edit()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;
        global $directPurchaseVendorModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $purchase = $directPurchaseModel->getById($id);
        if (!$purchase) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $items = $directPurchaseModel->getItemsWithReturnable($id);
        $productModel = new product($conn);
        require_once 'models/direct_purchase/DirectPurchaseStock.php';
        foreach ($items as &$it) {
            $img = $productModel->getImageForPurchaseLine(
                (string) ($it['item_code'] ?? ''),
                (string) ($it['sku'] ?? ''),
                (string) ($it['color'] ?? ''),
                (string) ($it['size'] ?? '')
            );
            $it['product_image'] = $img ?? '';
            $it['product_id'] = DirectPurchaseStock::resolveProductId(
                $conn,
                (string) ($it['sku'] ?? ''),
                (string) ($it['item_code'] ?? ''),
                (string) ($it['color'] ?? ''),
                (string) ($it['size'] ?? '')
            );
        }
        unset($it);
        $vendors = $directPurchaseVendorModel->getAllVendors();
        $commanModel = new Tables($conn);
        $warehouses = $commanModel->get_exotic_address();

        renderTemplate('views/direct_purchase/form.php', [
            'purchase' => $purchase,
            'items' => $items,
            'vendors' => $vendors,
            'warehouses' => $warehouses,
            'is_edit' => true,
        ], 'Edit direct purchase');
    }

    public function save()
    {
        is_login();
        global $directPurchaseModel;
        global $directPurchaseReturnModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

        $vendorId = isset($_POST['vendor_id']) ? (int) $_POST['vendor_id'] : 0;
        $warehouseId = isset($_POST['warehouse_id']) ? (int) $_POST['warehouse_id'] : 0;
        $invoiceNumber = trim((string) ($_POST['invoice_number'] ?? ''));
        $invoiceDate = trim((string) ($_POST['invoice_date'] ?? ''));

        if ($vendorId <= 0 || $invoiceNumber === '' || $invoiceDate === '') {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Vendor, invoice number and invoice date are required.'];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        $dateErr = $this->validateDirectPurchaseDateNotFuture($invoiceDate, 'Invoice date');
        if ($dateErr !== null) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $dateErr];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        global $conn;
        $lineFieldsError = $this->validateDirectPurchaseLinesFromPost($conn);
        if ($lineFieldsError !== null) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $lineFieldsError];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        $items = $this->collectLineItemsFromPost();
        if (empty($items)) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Add at least one line item.'];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        if ($id > 0) {
            $returnedLineError = $this->validateDirectPurchaseReturnedLines($id, $items, $directPurchaseReturnModel);
            if ($returnedLineError !== null) {
                $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $returnedLineError];
                header('Location: ?page=direct_purchase&action=edit&id=' . $id);
                exit;
            }
        }

        if ($warehouseId <= 0) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Select a warehouse for stock movement.'];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        $invoiceFile = null;
        if ($id > 0) {
            $existing = $directPurchaseModel->getById($id);
            $invoiceFile = $existing['invoice_file'] ?? null;
        }

        if (!empty($_FILES['invoice_file']['name'])) {
            $invErr = (int) ($_FILES['invoice_file']['error'] ?? UPLOAD_ERR_NO_FILE);
            if ($invErr === UPLOAD_ERR_OK) {
                $invoiceFile = $this->storeInvoiceUpload($_FILES['invoice_file']);
                if ($invoiceFile === null) {
                    $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Invoice file must be a PDF or image (JPG, PNG, GIF, WebP), max 2 MB.'];
                    $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
                    header('Location: ' . $redir);
                    exit;
                }
            } elseif ($invErr === UPLOAD_ERR_INI_SIZE || $invErr === UPLOAD_ERR_FORM_SIZE) {
                $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Invoice file is too large. Maximum size is 2 MB.'];
                $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
                header('Location: ' . $redir);
                exit;
            } elseif ($invErr !== UPLOAD_ERR_NO_FILE) {
                $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Invoice upload failed. Try again.'];
                $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
                header('Location: ' . $redir);
                exit;
            }
        }

        $allowedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'AED', 'SGD', 'HKD', 'JPY', 'CNY', 'AUD', 'CAD', 'CHF', 'NZD', 'SAR', 'THB'];
        $currency = strtoupper(preg_replace('/[^A-Za-z]/', '', (string) ($_POST['currency'] ?? 'INR')));
        if ($currency === '' || !in_array($currency, $allowedCurrencies, true)) {
            $currency = 'INR';
        }

        $grandTotal = (float) ($_POST['grand_total'] ?? 0);
        if ($grandTotal <= 0) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Grand total must be greater than zero.'];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        $header = [
            'vendor_id' => $vendorId,
            'warehouse_id' => $warehouseId,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'invoice_file' => $invoiceFile,
            'currency' => $currency,
            'subtotal' => (float) ($_POST['subtotal'] ?? 0),
            'discount' => (float) ($_POST['discount'] ?? 0),
            'igst_total' => (float) ($_POST['igst_total'] ?? 0),
            'sgst_total' => (float) ($_POST['sgst_total'] ?? 0),
            'cgst_total' => (float) ($_POST['cgst_total'] ?? 0),
            'round_off' => (float) ($_POST['round_off'] ?? 0),
            'grand_total' => $grandTotal,
            'created_by' => (int) ($_SESSION['user']['id'] ?? 0),
        ];

        try {
            $isEdit = $id > 0;
            $previousItems = $isEdit ? $directPurchaseModel->getItems($id) : [];
            $purchaseId = $id;
            if ($isEdit) {
                $directPurchaseModel->updatePurchase($id, $header, $items);
                $flashText = 'Purchase updated.';
            } else {
                $purchaseId = $directPurchaseModel->insertPurchase($header, $items);
                $flashText = 'Purchase saved.';
            }
            $cpSyncNote = $this->syncDirectPurchaseCpToVendor($items, $previousItems, $isEdit, $purchaseId);
            if ($cpSyncNote !== '') {
                $flashText .= ' ' . $cpSyncNote;
            }
            $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => $flashText];
            header('Location: ?page=direct_purchase&action=post_save&id=' . $purchaseId);
            exit;
        } catch (Throwable $e) {
            error_log('DirectPurchase save: ' . $e->getMessage());
            $detail = trim($e->getMessage());
            $_SESSION['direct_purchase_flash'] = [
                'type' => 'error',
                'text' => $detail !== '' ? ('Could not save purchase. ' . $detail) : 'Could not save purchase.',
            ];
        }

        header('Location: ?page=direct_purchase&action=list');
        exit;
    }

    public function postSave()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;

        $purchaseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($purchaseId <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $purchase = $directPurchaseModel->getById($purchaseId);
        if (!$purchase) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $items = $directPurchaseModel->getItems($purchaseId);
        $warehouseId = (int) ($purchase['warehouse_id'] ?? 0);
        $warehouseName = $this->resolveDirectPurchaseWarehouseLabel($conn, $warehouseId);
        $lists = $this->buildPostSaveStockLists($conn, $items, $warehouseId, $warehouseName);

        renderTemplate('views/direct_purchase/post_save.php', [
            'purchase' => $purchase,
            'warehouse_name' => $warehouseName,
            'putaway_items' => $lists['putaway'],
            'fulfillment_items' => $lists['fulfillment'],
        ], 'Stock putaway & order fulfillment');
    }

    public function syncVendorQtyForItem()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;
        global $directPurchaseModel;

        $itemId = (int) ($_GET['item_id'] ?? $_POST['item_id'] ?? 0);
        $purchaseId = (int) ($_GET['purchase_id'] ?? $_POST['purchase_id'] ?? 0);
        if ($itemId <= 0 || $purchaseId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid purchase line.']);
            exit;
        }

        $item = $directPurchaseModel->getItemById($itemId);
        if (!$item || (int) ($item['direct_purchase_id'] ?? 0) !== $purchaseId) {
            echo json_encode(['success' => false, 'message' => 'Purchase line not found.']);
            exit;
        }

        $currentQty = (float) ($item['qty'] ?? 0);
        if ($currentQty <= 0) {
            echo json_encode(['success' => false, 'message' => 'Line qty must be greater than zero.']);
            exit;
        }

        $syncedQty = isset($item['vendor_qty_synced_qty']) && $item['vendor_qty_synced_qty'] !== null
            ? (float) $item['vendor_qty_synced_qty']
            : 0.0;
        $isSynced = (int) ($item['vendor_qty_synced'] ?? 0) === 1;
        if ($isSynced && abs($currentQty - $syncedQty) < 0.0001) {
            echo json_encode([
                'success' => true,
                'message' => 'Qty already synced to vendor API.',
                'vendor_qty_synced' => 1,
            ]);
            exit;
        }

        $stockDelta = $isSynced ? ($currentQty - $syncedQty) : $currentQty;
        if (abs($stockDelta) < 0.0001) {
            echo json_encode([
                'success' => true,
                'message' => 'No qty change to sync.',
                'vendor_qty_synced' => 1,
            ]);
            exit;
        }

        $productModel = new product($conn);
        $variant = $this->resolveDirectPurchaseVariant($item, $productModel);
        if ($variant === null) {
            echo json_encode(['success' => false, 'message' => 'Could not resolve item code for this line.']);
            exit;
        }

        $product = $productModel->resolveProductForVendorSync(
            $variant['item_code'],
            $variant['size'],
            $variant['color']
        );

        $sync = $productModel->syncCpToVendorFrontend($product, 0.0, $stockDelta);
        if (empty($sync['success'])) {
            echo json_encode([
                'success' => false,
                'message' => trim((string) ($sync['message'] ?? 'Vendor qty sync failed.')),
            ]);
            exit;
        }

        $directPurchaseModel->markItemVendorQtySynced($itemId);

        echo json_encode([
            'success' => true,
            'message' => 'Qty synced to vendor API.',
            'vendor_qty_synced' => 1,
            'local_stock_delta' => $stockDelta,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function verifyVendorLine()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');
        global $conn;
        global $directPurchaseModel;

        $itemId = (int) ($_GET['item_id'] ?? $_POST['item_id'] ?? 0);
        $purchaseId = (int) ($_GET['purchase_id'] ?? $_POST['purchase_id'] ?? 0);
        $formCost = (float) ($_GET['cost_per_item'] ?? $_POST['cost_per_item'] ?? 0);

        $line = [];
        if ($itemId > 0 && $purchaseId > 0) {
            $item = $directPurchaseModel->getItemById($itemId);
            if (!$item || (int) ($item['direct_purchase_id'] ?? 0) !== $purchaseId) {
                echo json_encode(['success' => false, 'message' => 'Purchase line not found.']);
                exit;
            }
            $line = $item;
        } else {
            $line = [
                'item_code' => trim((string) ($_GET['item_code'] ?? $_POST['item_code'] ?? '')),
                'sku' => trim((string) ($_GET['sku'] ?? $_POST['sku'] ?? '')),
                'size' => trim((string) ($_GET['size'] ?? $_POST['size'] ?? '')),
                'color' => trim((string) ($_GET['color'] ?? $_POST['color'] ?? '')),
                'cost_per_item' => $formCost,
            ];
        }

        $productModel = new product($conn);
        $itemCode = trim((string) ($line['item_code'] ?? ''));
        $sku = trim((string) ($line['sku'] ?? ''));
        $size = trim((string) ($line['size'] ?? ''));
        $color = trim((string) ($line['color'] ?? ''));

        if ($itemCode === '' && $sku !== '') {
            $bySku = $productModel->getProductByskuExact($sku);
            if (is_array($bySku)) {
                if ($itemCode === '') {
                    $itemCode = trim((string) ($bySku['item_code'] ?? ''));
                }
                if ($size === '') {
                    $size = trim((string) ($bySku['size'] ?? ''));
                }
                if ($color === '') {
                    $color = trim((string) ($bySku['color'] ?? ''));
                }
            }
        }

        if ($itemCode === '' && $sku !== '') {
            $itemCode = $sku;
        }
        if ($itemCode === '') {
            echo json_encode(['success' => false, 'message' => 'Select a product or enter a SKU with a linked item code first.']);
            exit;
        }

        $expectedCp = $formCost > 0 ? $formCost : (float) ($line['cost_per_item'] ?? 0);

        $expectedLocalStock = null;
        $product = $productModel->resolveProductForVendorSync($itemCode, $size, $color);
        if (array_key_exists('local_stock', $product)) {
            $expectedLocalStock = (float) $product['local_stock'];
        }

        $result = $productModel->verifyVendorCpAndStockAgainstExpected(
            $itemCode,
            $size,
            $color,
            $expectedCp,
            $expectedLocalStock,
            $sku
        );

        if (is_array($result)) {
            $result['sku'] = $sku;
        }

        echo json_encode($result, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function delete()
    {
        is_login();
        global $directPurchaseModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        if ($directPurchaseModel->countReturns($id) > 0) {
            $_SESSION['direct_purchase_flash'] = [
                'type' => 'error',
                'text' => 'This purchase has linked purchase returns. Delete all returns for this purchase first.',
            ];
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        try {
            $items = $directPurchaseModel->getItems($id);
            $directPurchaseModel->delete($id);
            $vendorNote = $this->syncDirectPurchaseCpToVendor([], $items, true, 0);
            $flashText = 'Purchase deleted.';
            if ($vendorNote !== '') {
                $flashText .= ' ' . $vendorNote;
            }
            $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => $flashText];
        } catch (Throwable $e) {
            error_log('DirectPurchase delete: ' . $e->getMessage());
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Could not delete purchase.'];
        }
        header('Location: ?page=direct_purchase&action=list');
        exit;
    }

    /**
     * Drop invalid dates and cap filter "to"/"from" at today so list search cannot target the future.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    private function directPurchaseTodayYmd(): string
    {
        $tz = new \DateTimeZone('Asia/Kolkata');

        return (new \DateTimeImmutable('now', $tz))->format('Y-m-d');
    }

    private function isValidYmdCalendarDate(string $ymd): bool
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ymd, $m)) {
            return false;
        }
        $y = (int) $m[1];
        $mo = (int) $m[2];
        $d = (int) $m[3];

        return checkdate($mo, $d, $y);
    }

    private function sanitizeDirectPurchaseListDateFilters(array $filters): array
    {
        $todayStr = $this->directPurchaseTodayYmd();

        foreach (['invoice_date_from', 'invoice_date_to', 'added_date_from', 'added_date_to', 'return_date_from', 'return_date_to'] as $key) {
            $v = isset($filters[$key]) ? trim((string) $filters[$key]) : '';
            if ($v === '') {
                $filters[$key] = '';
                continue;
            }
            if (!$this->isValidYmdCalendarDate($v)) {
                $filters[$key] = '';
                continue;
            }
            if ($v > $todayStr) {
                $filters[$key] = $todayStr;
            }
        }

        return $filters;
    }

    /**
     * @return string|null Error message, or null if date is valid and not after today (Asia/Kolkata calendar day)
     */
    private function validateDirectPurchaseDateNotFuture(string $dateStr, string $fieldLabel): ?string
    {
        $dateStr = trim($dateStr);
        if (!$this->isValidYmdCalendarDate($dateStr)) {
            return $fieldLabel . ' must be a valid date.';
        }
        $todayStr = $this->directPurchaseTodayYmd();
        if ($dateStr > $todayStr) {
            return $fieldLabel . ' cannot be in the future.';
        }

        return null;
    }

    private function resolveDirectPurchaseWarehouseLabel(\mysqli $conn, int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return '—';
        }

        $stmt = $conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $warehouseId);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $title = trim((string) ($row['address_title'] ?? ''));
            if ($title !== '') {
                return $title;
            }
        }

        return 'Warehouse #' . $warehouseId;
    }

    public function returns()
    {
        is_login();
        global $directPurchaseReturnModel;
        global $directPurchaseVendorModel;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20;

        $filters = [
            'search_text' => isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '',
            'return_date_from' => isset($_GET['return_date_from']) ? trim((string) $_GET['return_date_from']) : '',
            'return_date_to' => isset($_GET['return_date_to']) ? trim((string) $_GET['return_date_to']) : '',
            'vendor_id' => isset($_GET['vendor_id']) ? (int) $_GET['vendor_id'] : 0,
        ];
        $filters = $this->sanitizeDirectPurchaseListDateFilters($filters);

        $result = $directPurchaseReturnModel->searchReturns($filters, $pageNo, $limit);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        renderTemplate('views/direct_purchase/returns_index.php', [
            'returns' => $result['rows'],
            'total_records' => $result['total'],
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'filters' => $filters,
            'vendors' => $directPurchaseVendorModel->getAllVendors(),
        ], 'Purchase returns');
    }

    public function returnList()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;
        global $directPurchaseReturnModel;

        $dpId = isset($_GET['dp_id']) ? (int) $_GET['dp_id'] : 0;
        if ($dpId <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $purchase = $directPurchaseModel->getById($dpId);
        if (!$purchase) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $returns = $directPurchaseReturnModel->listForPurchase($dpId);
        $returnIds = array_map(static function (array $row): int {
            return (int) ($row['id'] ?? 0);
        }, $returns);
        $returnItems = $directPurchaseReturnModel->getItemsGroupedByReturnIds($returnIds);

        renderTemplate('views/direct_purchase/return_list.php', [
            'purchase' => $purchase,
            'returns' => $returns,
            'return_items' => $returnItems,
        ], 'Purchase returns');
    }

    public function returnAdd()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;
        global $directPurchaseVendorModel;

        $dpId = isset($_GET['dp_id']) ? (int) $_GET['dp_id'] : 0;
        if ($dpId <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $purchase = $directPurchaseModel->getById($dpId);
        if (!$purchase) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $lines = $directPurchaseModel->getItemsWithReturnable($dpId);
        $warehouseId = (int) ($purchase['warehouse_id'] ?? 0);
        $warehouseName = $this->resolveDirectPurchaseWarehouseLabel($conn, $warehouseId);

        renderTemplate('views/direct_purchase/return_form.php', [
            'purchase' => $purchase,
            'lines' => $lines,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
        ], 'New purchase return');
    }

    public function returnSave()
    {
        is_login();
        global $directPurchaseModel;
        global $directPurchaseReturnModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $dpId = isset($_POST['direct_purchase_id']) ? (int) $_POST['direct_purchase_id'] : 0;
        $returnDate = trim((string) ($_POST['return_date'] ?? ''));
        $remarks = trim((string) ($_POST['remarks'] ?? ''));

        if ($dpId <= 0 || $returnDate === '') {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Purchase and return date are required.'];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . max(1, $dpId));
            exit;
        }

        $dateErr = $this->validateDirectPurchaseDateNotFuture($returnDate, 'Return date');
        if ($dateErr !== null) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $dateErr];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
            exit;
        }

        $purchase = $directPurchaseModel->getById($dpId);
        if (!$purchase) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Purchase not found.'];
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $warehouseId = (int) ($purchase['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            $_SESSION['direct_purchase_flash'] = [
                'type' => 'error',
                'text' => 'This purchase has no warehouse. Set the warehouse on the purchase before creating a return.',
            ];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
            exit;
        }

        $itemIds = $_POST['dp_item_id'] ?? [];
        $returnQtys = $_POST['return_qty'] ?? [];
        if (!is_array($itemIds) || !is_array($returnQtys)) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Invalid return line data.'];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
            exit;
        }

        $itemsById = [];
        foreach ($directPurchaseModel->getItems($dpId) as $row) {
            $itemsById[(int) $row['id']] = $row;
        }
        $alreadyReturned = $directPurchaseReturnModel->sumReturnedQtyByItem($dpId, 0);

        $returnLines = [];
        $sumLineTotal = 0.0;
        $sumGst = 0.0;
        $n = max(count($itemIds), count($returnQtys));
        for ($i = 0; $i < $n; $i++) {
            $iid = isset($itemIds[$i]) ? (int) $itemIds[$i] : 0;
            $rq = isset($returnQtys[$i]) ? (float) $returnQtys[$i] : 0;
            if ($iid <= 0 || $rq <= 0) {
                continue;
            }
            if (!isset($itemsById[$iid])) {
                continue;
            }
            $dpLine = $itemsById[$iid];
            $origQty = (float) ($dpLine['qty'] ?? 0);
            $prevRet = (float) ($alreadyReturned[$iid] ?? 0);
            $maxRet = max(0.0, $origQty - $prevRet);
            if ($rq > $maxRet + 1e-9) {
                $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Return quantity exceeds remaining quantity for one or more lines.'];
                header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
                exit;
            }
            $ratio = $origQty > 0 ? ($rq / $origQty) : 0;
            $lineTotal = round((float) ($dpLine['line_total'] ?? 0) * $ratio, 2);
            $gstAmt = round((float) ($dpLine['gst_amount'] ?? 0) * $ratio, 2);
            $sumLineTotal += $lineTotal;
            $sumGst += $gstAmt;
            $returnLines[] = [
                'direct_purchase_item_id' => $iid,
                'return_qty' => $rq,
                'gst_amount' => $gstAmt,
                'line_total' => $lineTotal,
                'sort_order' => count($returnLines),
            ];
        }

        if ($returnLines === []) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Enter a return quantity on at least one line.'];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
            exit;
        }

        global $conn;
        require_once 'models/direct_purchase/DirectPurchaseStock.php';
        $stockCheckLines = [];
        foreach ($returnLines as $returnLine) {
            $itemId = (int) ($returnLine['direct_purchase_item_id'] ?? 0);
            if ($itemId <= 0 || !isset($itemsById[$itemId])) {
                continue;
            }
            $sku = trim((string) ($itemsById[$itemId]['sku'] ?? ''));
            if ($sku === '') {
                continue;
            }
            $stockCheckLines[] = [
                'sku' => $sku,
                'return_qty' => (float) ($returnLine['return_qty'] ?? 0),
            ];
        }
        $stockError = DirectPurchaseStock::validateWarehouseStockForReturn($conn, $warehouseId, $stockCheckLines);
        if ($stockError !== null) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $stockError];
            header('Location: ?page=direct_purchase&action=return_add&dp_id=' . $dpId);
            exit;
        }

        $currency = strtoupper(trim((string) ($purchase['currency'] ?? 'INR')));
        $subtotal = max(0.0, round($sumLineTotal - $sumGst, 2));
        $pSub = (float) ($purchase['subtotal'] ?? 0);
        $ratioTax = $pSub > 0 ? ($subtotal / $pSub) : 0;
        $igst = round((float) ($purchase['igst_total'] ?? 0) * $ratioTax, 2);
        $sgst = round((float) ($purchase['sgst_total'] ?? 0) * $ratioTax, 2);
        $cgst = round((float) ($purchase['cgst_total'] ?? 0) * $ratioTax, 2);
        $discount = 0.0;
        $roundOff = round($sumLineTotal - ($subtotal + $igst + $sgst + $cgst), 2);
        $grandTotal = round($sumLineTotal, 2);
        $roundOff = round($grandTotal - ($subtotal + $igst + $sgst + $cgst + $discount), 2);

        $header = [
            'direct_purchase_id' => $dpId,
            'warehouse_id' => $warehouseId,
            'return_date' => $returnDate,
            'remarks' => $remarks,
            'currency' => $currency,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'igst_total' => $igst,
            'sgst_total' => $sgst,
            'cgst_total' => $cgst,
            'round_off' => $roundOff,
            'grand_total' => $grandTotal,
            'created_by' => (int) ($_SESSION['user']['id'] ?? 0),
        ];

        try {
            $directPurchaseReturnModel->insertReturn($header, $returnLines);
            global $conn;
            $productModel = new product($conn);
            $vendorNote = $this->syncDirectPurchaseReturnLocalStockToVendor($returnLines, $itemsById, $productModel);
            $flashText = 'Purchase return saved and stock updated.';
            if ($vendorNote !== '') {
                $flashText .= ' ' . $vendorNote;
            }
            $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => $flashText];
        } catch (Throwable $e) {
            error_log('DirectPurchase returnSave: ' . $e->getMessage());
            $errorText = $e->getMessage();
            if (stripos($errorText, 'Insufficient stock') !== false) {
                $errorText = 'Insufficient warehouse stock. Reduce return quantity or check stock in the purchase warehouse.';
            } else {
                $errorText = 'Could not save return. ' . $errorText;
            }
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => $errorText];
        }

        header('Location: ?page=direct_purchase&action=return_list&dp_id=' . $dpId);
        exit;
    }

    public function returnDelete()
    {
        is_login();
        global $conn;
        global $directPurchaseModel;
        global $directPurchaseReturnModel;

        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($id <= 0) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $row = $directPurchaseReturnModel->getById($id);
        if (!$row) {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }
        $dpId = (int) ($row['direct_purchase_id'] ?? 0);

        $returnLines = [];
        foreach ($directPurchaseReturnModel->getItems($id) as $returnItem) {
            $itemId = (int) ($returnItem['direct_purchase_item_id'] ?? 0);
            $returnQty = (float) ($returnItem['return_qty'] ?? 0);
            if ($itemId <= 0 || $returnQty <= 0) {
                continue;
            }
            $returnLines[] = [
                'direct_purchase_item_id' => $itemId,
                'return_qty' => $returnQty,
            ];
        }

        $itemsById = [];
        if ($dpId > 0) {
            foreach ($directPurchaseModel->getItems($dpId) as $purchaseItem) {
                $itemsById[(int) $purchaseItem['id']] = $purchaseItem;
            }
        }

        try {
            $directPurchaseReturnModel->deleteReturn($id);
            $productModel = new product($conn);
            $vendorNote = $this->syncDirectPurchaseReturnLocalStockToVendor(
                $returnLines,
                $itemsById,
                $productModel,
                1
            );
            $flashText = 'Return deleted and stock restored.';
            if ($vendorNote !== '') {
                $flashText .= ' ' . $vendorNote;
            }
            $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => $flashText];
        } catch (Throwable $e) {
            error_log('DirectPurchase returnDelete: ' . $e->getMessage());
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Could not delete return.'];
        }

        header('Location: ?page=direct_purchase&action=return_list&dp_id=' . $dpId);
        exit;
    }

    /**
     * Each line with qty &gt; 0 must have SKU (existing in vp_products), cost, qty, GST %, and line total. HSN is optional.
     */
    private function validateDirectPurchaseLinesFromPost(\mysqli $conn): ?string
    {
        $skus = $_POST['sku'] ?? [];
        if (!is_array($skus)) {
            return 'Add at least one line item.';
        }

        $costs = $_POST['cost_per_item'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $rates = $_POST['gst_rate'] ?? [];
        $lineTots = $_POST['line_total'] ?? [];

        $productModel = new product($conn);
        $errors = [];
        $invalidSkus = [];
        $n = max(count($skus), is_array($qtys) ? count($qtys) : 0);
        $activeLines = 0;

        for ($i = 0; $i < $n; $i++) {
            $qty = isset($qtys[$i]) ? (float) $qtys[$i] : 0;
            if ($qty <= 0) {
                continue;
            }
            $activeLines++;
            $label = 'Line ' . ($i + 1);

            $sku = isset($skus[$i]) ? trim((string) $skus[$i]) : '';
            if ($sku === '') {
                $errors[] = $label . ': SKU is required';
            } else {
                $row = $productModel->getProductByskuExact($sku);
                if (!is_array($row) || empty($row['id'])) {
                    $invalidSkus[] = $sku;
                }
            }

            $costRaw = isset($costs[$i]) ? trim((string) $costs[$i]) : '';
            if ($costRaw === '') {
                $errors[] = $label . ': cost per item is required';
            } elseif ((float) $costRaw <= 0) {
                $errors[] = $label . ': cost per item must be greater than zero';
            }

            $gstRaw = isset($rates[$i]) ? trim((string) $rates[$i]) : '';
            if ($gstRaw === '') {
                $errors[] = $label . ': GST % is required';
            } elseif ((float) $gstRaw < 0) {
                $errors[] = $label . ': GST % cannot be negative';
            }

            $lineTotalRaw = isset($lineTots[$i]) ? trim((string) $lineTots[$i]) : '';
            if ($lineTotalRaw === '') {
                $errors[] = $label . ': line total is required';
            } elseif ((float) $lineTotalRaw <= 0) {
                $errors[] = $label . ': line total must be greater than zero';
            }
        }

        if ($activeLines === 0) {
            return 'Add at least one line item with qty greater than zero.';
        }

        if ($invalidSkus !== []) {
            $unique = array_values(array_unique($invalidSkus));
            $shown = array_slice($unique, 0, 5);
            $suffix = count($unique) > 5 ? ' (and ' . (count($unique) - 5) . ' more)' : '';
            $errors[] = 'invalid SKU(s): ' . implode(', ', $shown) . $suffix;
        }

        if ($errors === []) {
            return null;
        }

        $shown = array_slice($errors, 0, 5);
        $suffix = count($errors) > 5 ? ' (and ' . (count($errors) - 5) . ' more)' : '';

        return 'Complete all required line fields (HSN is optional). ' . implode('; ', $shown) . $suffix . '.';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function collectLineItemsFromPost()
    {
        $codes = $_POST['item_code'] ?? [];
        $skus = $_POST['sku'] ?? [];
        if (!is_array($skus)) {
            return [];
        }
        $colors = $_POST['color'] ?? [];
        $sizes = $_POST['size'] ?? [];
        $costs = $_POST['cost_per_item'] ?? [];
        $qtys = $_POST['qty'] ?? [];
        $hsns = $_POST['hsn'] ?? [];
        $rates = $_POST['gst_rate'] ?? [];
        $gstAmts = $_POST['gst_amount'] ?? [];
        $lineTots = $_POST['line_total'] ?? [];
        $lineIds = $_POST['dp_line_id'] ?? [];

        $out = [];
        $n = max(count($skus), is_array($qtys) ? count($qtys) : 0);
        for ($i = 0; $i < $n; $i++) {
            $qty = isset($qtys[$i]) ? (float) $qtys[$i] : 0;
            if ($qty <= 0) {
                continue;
            }
            $out[] = [
                'id' => isset($lineIds[$i]) ? (int) $lineIds[$i] : 0,
                'item_code' => isset($codes[$i]) ? trim((string) $codes[$i]) : '',
                'sku' => isset($skus[$i]) ? trim((string) $skus[$i]) : '',
                'color' => isset($colors[$i]) ? trim((string) $colors[$i]) : '',
                'size' => isset($sizes[$i]) ? trim((string) $sizes[$i]) : '',
                'cost_per_item' => isset($costs[$i]) ? (float) $costs[$i] : 0,
                'qty' => $qty,
                'hsn' => isset($hsns[$i]) ? trim((string) $hsns[$i]) : '',
                'gst_rate' => isset($rates[$i]) ? (float) $rates[$i] : 0,
                'gst_amount' => isset($gstAmts[$i]) ? (float) $gstAmts[$i] : 0,
                'line_total' => isset($lineTots[$i]) ? (float) $lineTots[$i] : 0,
            ];
        }
        return $out;
    }

    /**
     * Lines with linked purchase returns must remain on the purchase and stay unchanged.
     *
     * @param list<array<string, mixed>> $postedItems
     */
    private function validateDirectPurchaseReturnedLines(
        int $purchaseId,
        array $postedItems,
        directPurchaseReturn $returnModel
    ): ?string {
        global $directPurchaseModel;

        $returnedByItem = $returnModel->sumReturnedQtyByItem($purchaseId, 0);
        if ($returnedByItem === []) {
            return null;
        }

        $postedById = [];
        foreach ($postedItems as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            if ($itemId > 0) {
                $postedById[$itemId] = $row;
            }
        }

        foreach ($returnedByItem as $itemId => $returnedQty) {
            if ($returnedQty <= 0) {
                continue;
            }
            if (!isset($postedById[$itemId])) {
                return 'Cannot remove a line item that has linked purchase returns.';
            }

            $dbLine = $directPurchaseModel->getItemById($itemId);
            if (!$dbLine || (int) ($dbLine['direct_purchase_id'] ?? 0) !== $purchaseId) {
                return 'Invalid purchase line for return validation.';
            }

            if (!$this->directPurchaseLineFieldsMatch($dbLine, $postedById[$itemId])) {
                $sku = trim((string) ($dbLine['sku'] ?? ''));
                $label = $sku !== '' ? $sku : ('line #' . $itemId);

                return 'Line item ' . $label . ' has linked purchase returns and cannot be edited.';
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $dbLine
     * @param array<string, mixed> $postedLine
     */
    private function directPurchaseLineFieldsMatch(array $dbLine, array $postedLine): bool
    {
        foreach (['item_code', 'sku', 'color', 'size', 'hsn'] as $field) {
            if (trim((string) ($dbLine[$field] ?? '')) !== trim((string) ($postedLine[$field] ?? ''))) {
                return false;
            }
        }

        foreach (['cost_per_item', 'qty', 'gst_rate', 'gst_amount', 'line_total'] as $field) {
            if (abs((float) ($dbLine[$field] ?? 0) - (float) ($postedLine[$field] ?? 0)) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string, mixed> $file $_FILES element
     */
    private function storeInvoiceUpload($file)
    {
        $maxBytes = 2 * 1024 * 1024;
        if (!isset($file['tmp_name'], $file['name'], $file['size']) || !is_uploaded_file($file['tmp_name'])) {
            return null;
        }
        if ((int) $file['size'] > $maxBytes) {
            return null;
        }

        $uploadDir = __DIR__ . '/../uploads/direct_purchase/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $name = $file['name'];
        $tmp = $file['tmp_name'];
        $parts = explode('.', $name);
        $ext = strtolower(end($parts));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }

        $mime = null;
        if (function_exists('mime_content_type')) {
            $mime = @mime_content_type($tmp) ?: null;
        }
        if ($mime === null && class_exists('finfo')) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($tmp) ?: null;
        }
        $allowedMime = ['application/pdf', 'image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if ($mime !== null && $mime !== 'application/octet-stream' && !in_array($mime, $allowedMime, true)) {
            return null;
        }

        $newName = 'dp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }
        return 'uploads/direct_purchase/' . $newName;
    }

    /**
     * After a successful purchase save, push pricing and qty to vendor product/modify.
     * Books: price_india (ex-GST) in DB + vendor API. Other items: cp in DB + vendor API.
     * Both: local_stock_delta when qty changes.
     *
     * New purchase: local_stock_delta = line qty (positive).
     * Edit purchase: local_stock_delta = new qty − previous qty (positive or negative).
     * Full delete: pass empty $items and saved lines as $previousItems — negative delta per removed line.
     *
     * @param list<array<string, mixed>> $items
     * @param list<array<string, mixed>> $previousItems Saved lines before update (edit only)
     */
    private function syncDirectPurchaseCpToVendor(
        array $items,
        array $previousItems = [],
        bool $isEdit = false,
        int $purchaseId = 0
    ): string {
        global $conn;
        global $directPurchaseModel;
        $productModel = new product($conn);
        $failures = [];

        $currentByVariant = $this->aggregateDirectPurchaseVendorLines($items, $productModel);
        $previousByVariant = $isEdit
            ? $this->aggregateDirectPurchaseQtyByVariant($previousItems, $productModel)
            : [];

        $allKeys = array_unique(array_merge(array_keys($currentByVariant), array_keys($previousByVariant)));

        foreach ($allKeys as $dedupeKey) {
            $current = $currentByVariant[$dedupeKey] ?? null;
            $previous = $previousByVariant[$dedupeKey] ?? null;

            $newQty = (float) ($current['qty'] ?? 0);
            $oldQty = (float) ($previous['qty'] ?? 0);
            $stockDelta = $isEdit ? ($newQty - $oldQty) : $newQty;
            $cost = (float) ($current['cost'] ?? 0);
            $line = is_array($current) ? $current : $previous;

            if ($cost <= 0 && abs($stockDelta) < 0.0001) {
                if (
                    $purchaseId > 0
                    && $isEdit
                    && $newQty > 0
                    && abs($newQty - $oldQty) < 0.0001
                    && is_array($line)
                ) {
                    $directPurchaseModel->markItemsVendorQtySyncedByVariant(
                        $purchaseId,
                        $line['item_code'],
                        $line['size'],
                        $line['color']
                    );
                }
                continue;
            }

            if (!is_array($line)) {
                continue;
            }

            $itemCode = $line['item_code'];
            $size = $line['size'];
            $color = $line['color'];

            $stockDeltaArg = abs($stockDelta) > 0.0001 ? $stockDelta : null;
            if ($cost <= 0 && $stockDeltaArg !== null) {
                $product = $productModel->resolveProductForVendorSync($itemCode, $size, $color);
                $sync = $productModel->syncCpToVendorFrontend($product, 0.0, $stockDeltaArg);
            } else {
                $sync = $productModel->applyDirectPurchaseLinePricingToVendor(
                    $itemCode,
                    $size,
                    $color,
                    $cost,
                    $stockDeltaArg
                );
            }
            if (empty($sync['success'])) {
                $failures[] = $itemCode . ' — ' . trim((string) ($sync['message'] ?? 'vendor product sync failed'));
                continue;
            }

            if ($purchaseId > 0) {
                $qtyUnchangedOnEdit = $isEdit && $newQty > 0 && abs($newQty - $oldQty) < 0.0001;
                if ($stockDeltaArg !== null || $qtyUnchangedOnEdit) {
                    $directPurchaseModel->markItemsVendorQtySyncedByVariant($purchaseId, $itemCode, $size, $color);
                }
            }
        }

        if ($failures === []) {
            return '';
        }

        $shown = array_slice($failures, 0, 3);
        $suffix = count($failures) > 3 ? ' (and ' . (count($failures) - 3) . ' more)' : '';

        return 'Vendor product update issue: ' . implode('; ', $shown) . $suffix . '.';
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array<string, array{item_code:string,size:string,color:string,qty:float,cost:float,key:string}>
     */
    private function aggregateDirectPurchaseVendorLines(array $items, product $productModel): array
    {
        $out = [];
        foreach ($items as $line) {
            $normalized = $this->normalizeDirectPurchaseVendorLine($line, $productModel);
            if ($normalized === null) {
                continue;
            }
            $key = $normalized['key'];
            if (!isset($out[$key])) {
                $out[$key] = $normalized;
                $out[$key]['qty'] = 0.0;
                $out[$key]['cost'] = 0.0;
            }
            $out[$key]['qty'] += $normalized['qty'];
            if ($normalized['cost'] > 0) {
                $out[$key]['cost'] = $normalized['cost'];
            }
        }

        return $out;
    }

    /**
     * Qty totals per variant from saved purchase lines (edit comparison; includes lines with qty only).
     *
     * @param list<array<string, mixed>> $items
     * @return array<string, array{item_code:string,size:string,color:string,qty:float,key:string}>
     */
    private function aggregateDirectPurchaseQtyByVariant(array $items, product $productModel): array
    {
        $out = [];
        foreach ($items as $line) {
            $variant = $this->resolveDirectPurchaseVariant($line, $productModel);
            if ($variant === null) {
                continue;
            }
            $qty = (float) ($line['qty'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $key = $variant['key'];
            if (!isset($out[$key])) {
                $out[$key] = $variant;
                $out[$key]['qty'] = 0.0;
            }
            $out[$key]['qty'] += $qty;
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $line
     * @return array{item_code:string,sku:string,size:string,color:string,key:string}|null
     */
    private function resolveDirectPurchaseVariant(array $line, product $productModel): ?array
    {
        $itemCode = trim((string) ($line['item_code'] ?? ''));
        $sku = trim((string) ($line['sku'] ?? ''));
        if ($itemCode === '' && $sku !== '') {
            $bySku = $productModel->getProductByskuExact($sku);
            if (is_array($bySku)) {
                $itemCode = trim((string) ($bySku['item_code'] ?? ''));
            }
            if ($itemCode === '') {
                $itemCode = $sku;
            }
        }
        if ($itemCode === '') {
            return null;
        }

        $size = trim((string) ($line['size'] ?? ''));
        $color = trim((string) ($line['color'] ?? ''));

        return [
            'item_code' => $itemCode,
            'sku' => $sku,
            'size' => $size,
            'color' => $color,
            'key' => strtolower($itemCode) . '|' . $size . '|' . $color,
        ];
    }

    /**
     * @param array<string, mixed> $line
     * @return array{item_code:string,sku:string,size:string,color:string,qty:float,cost:float,key:string}|null
     */
    private function normalizeDirectPurchaseVendorLine(array $line, product $productModel): ?array
    {
        $variant = $this->resolveDirectPurchaseVariant($line, $productModel);
        if ($variant === null) {
            return null;
        }

        $cost = (float) ($line['cost_per_item'] ?? 0);
        $qty = (float) ($line['qty'] ?? 0);
        if ($cost <= 0 && $qty <= 0) {
            return null;
        }

        return array_merge($variant, [
            'qty' => $qty,
            'cost' => $cost,
        ]);
    }

    /**
     * Push local_stock_delta to vendor product/modify for purchase return lines.
     * Save uses negative delta (stock out); delete uses positive delta (stock restored).
     * Does not send absolute local_stock — only the delta field expected by the vendor API.
     *
     * @param list<array<string, mixed>> $returnLines
     * @param array<int, array<string, mixed>> $itemsById
     * @param int $deltaSign -1 on return save, +1 on return delete
     */
    private function syncDirectPurchaseReturnLocalStockToVendor(
        array $returnLines,
        array $itemsById,
        product $productModel,
        int $deltaSign = -1
    ): string {
        if ($deltaSign !== -1 && $deltaSign !== 1) {
            $deltaSign = -1;
        }
        $byVariant = [];
        foreach ($returnLines as $returnLine) {
            $itemId = (int) ($returnLine['direct_purchase_item_id'] ?? 0);
            $returnQty = (float) ($returnLine['return_qty'] ?? 0);
            if ($itemId <= 0 || $returnQty <= 0 || !isset($itemsById[$itemId])) {
                continue;
            }
            $variant = $this->resolveDirectPurchaseVariant($itemsById[$itemId], $productModel);
            if ($variant === null) {
                continue;
            }
            $key = $variant['key'];
            if (!isset($byVariant[$key])) {
                $byVariant[$key] = $variant;
                $byVariant[$key]['return_qty'] = 0.0;
            }
            $byVariant[$key]['return_qty'] += $returnQty;
        }

        $deltasByVariant = [];
        foreach ($byVariant as $variant) {
            $localStockDelta = $deltaSign * (int) round((float) ($variant['return_qty'] ?? 0));
            if ($localStockDelta === 0) {
                continue;
            }
            $deltasByVariant[] = [
                'item_code' => $variant['item_code'],
                'size' => $variant['size'],
                'color' => $variant['color'],
                'local_stock_delta' => (float) $localStockDelta,
            ];
        }

        return $this->pushDirectPurchaseLocalStockDeltasToVendor($productModel, $deltasByVariant);
    }

    /**
     * @param list<array{item_code:string,size:string,color:string,local_stock_delta:float}> $deltasByVariant
     */
    private function pushDirectPurchaseLocalStockDeltasToVendor(product $productModel, array $deltasByVariant): string
    {
        $failures = [];
        foreach ($deltasByVariant as $variant) {
            $localStockDelta = (float) ($variant['local_stock_delta'] ?? 0);
            if (abs($localStockDelta) < 0.0001) {
                continue;
            }

            $product = $productModel->resolveProductForVendorSync(
                $variant['item_code'],
                $variant['size'],
                $variant['color']
            );

            $sync = $productModel->syncCpToVendorFrontend($product, 0.0, $localStockDelta);
            if (empty($sync['success'])) {
                $failures[] = $variant['item_code'] . ' — ' . trim((string) ($sync['message'] ?? 'vendor local_stock_delta sync failed'));
            }
        }

        if ($failures === []) {
            return '';
        }

        $shown = array_slice($failures, 0, 3);
        $suffix = count($failures) > 3 ? ' (and ' . (count($failures) - 3) . ' more)' : '';

        return 'Vendor local stock update issue: ' . implode('; ', $shown) . $suffix . '.';
    }

    /**
     * @param list<array<string, mixed>> $items
     * @return array{putaway: list<array<string, mixed>>, fulfillment: list<array<string, mixed>>}
     */
    private function buildPostSaveStockLists(
        \mysqli $conn,
        array $items,
        int $warehouseId,
        string $warehouseName
    ): array {
        require_once __DIR__ . '/../models/direct_purchase/DirectPurchaseStock.php';
        $productModel = new product($conn);

        $putaway = [];
        $fulfillment = [];

        foreach ($items as $item) {
            $sku = trim((string) ($item['sku'] ?? ''));
            $itemCode = trim((string) ($item['item_code'] ?? ''));
            $color = trim((string) ($item['color'] ?? ''));
            $size = trim((string) ($item['size'] ?? ''));
            $purchaseQty = (float) ($item['qty'] ?? 0);
            if ($sku === '' || $purchaseQty <= 0) {
                continue;
            }

            $image = $productModel->getImageForPurchaseLine($itemCode, $sku, $color, $size) ?? '';
            $productId = DirectPurchaseStock::resolveProductId($conn, $sku, $itemCode, $color, $size);
            $location = $this->resolveWarehouseItemLocation($conn, $productId, $warehouseId, $warehouseName);

            $pendingOrders = $this->fetchFulfillmentPendingOrdersForLine($conn, $sku, $itemCode, $size, $color);
            $totalOrderQty = 0.0;
            foreach ($pendingOrders as $orderRow) {
                $totalOrderQty += (float) ($orderRow['quantity'] ?? 0);
            }

            $fulfillQty = min($purchaseQty, $totalOrderQty);
            $putawayQty = max(0.0, $purchaseQty - $fulfillQty);
            $remainingOrderQty = max(0.0, $totalOrderQty - $fulfillQty);

            $lineBase = [
                'sku' => $sku,
                'item_code' => $itemCode,
                'color' => $color,
                'size' => $size,
                'image' => $image,
                'purchase_qty' => $purchaseQty,
            ];

            if ($putawayQty > 0) {
                $putaway[] = array_merge($lineBase, [
                    'qty' => $putawayQty,
                    'location' => $location,
                ]);
            }

            if ($fulfillQty > 0) {
                $fulfillment[] = array_merge($lineBase, [
                    'order_qty' => $totalOrderQty,
                    'fulfill_qty' => $fulfillQty,
                    'remaining_order_qty' => $remainingOrderQty,
                    'orders' => $pendingOrders,
                ]);
            }
        }

        return [
            'putaway' => $putaway,
            'fulfillment' => $fulfillment,
        ];
    }

    /**
     * Pending / unfulfilled / partially shipped / backordered order lines for a purchase item (not warehouse-scoped).
     *
     * @return list<array{order_number:string,quantity:float,status:string,backorder_status:int}>
     */
    private function fetchFulfillmentPendingOrdersForLine(
        \mysqli $conn,
        string $sku,
        string $itemCode,
        string $size,
        string $color
    ): array {
        $terminal = ['delivered', 'cancelled', 'returned', 'shipped'];
        $inFlight = [
            'ready_for_packing',
            'po_pending',
            'po_approved',
            'po_inprogress',
            'item_received',
            'added_to_picklist',
            'store_transfer',
            'ready_for_qc',
            'sent_for_repair',
            'ready_for_dispatch',
        ];

        $sql = 'SELECT order_number, quantity, status, COALESCE(backorder_status, 0) AS backorder_status
                FROM vp_orders
                WHERE sku = ?
                  AND (TRIM(COALESCE(item_code, "")) = ? OR ? = "")
                  AND (TRIM(COALESCE(size, "")) = ? OR ? = "")
                  AND (TRIM(COALESCE(color, "")) = ? OR ? = "")
                  AND status NOT IN (?, ?, ?, ?)
                ORDER BY order_date ASC, order_number ASC, id ASC';

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $t0 = $terminal[0];
        $t1 = $terminal[1];
        $t2 = $terminal[2];
        $t3 = $terminal[3];
        $stmt->bind_param(
            'sssssssssss',
            $sku,
            $itemCode,
            $itemCode,
            $size,
            $size,
            $color,
            $color,
            $t0,
            $t1,
            $t2,
            $t3
        );
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $status = strtolower(trim((string) ($row['status'] ?? '')));
                $backorderStatus = (int) ($row['backorder_status'] ?? 0);
                $eligible = $status === 'pending'
                    || in_array($status, $inFlight, true)
                    || $backorderStatus > 0;
                if (!$eligible) {
                    continue;
                }
                $qty = (float) ($row['quantity'] ?? 0);
                if ($qty <= 0) {
                    continue;
                }
                $rows[] = [
                    'order_number' => trim((string) ($row['order_number'] ?? '')),
                    'quantity' => $qty,
                    'status' => $status,
                    'backorder_status' => $backorderStatus,
                ];
            }
        }
        $stmt->close();

        return $rows;
    }

    private function resolveWarehouseItemLocation(
        \mysqli $conn,
        int $productId,
        int $warehouseId,
        string $warehouseName
    ): ?string {
        if ($productId > 0) {
            $stmt = $conn->prepare('SELECT TRIM(COALESCE(location, "")) AS location FROM vp_products WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $productId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $productLocation = trim((string) ($row['location'] ?? ''));
                if ($productLocation !== '') {
                    return $productLocation;
                }
            }
        }

        if ($productId <= 0 || $warehouseId <= 0) {
            return null;
        }

        $stmt = $conn->prepare(
            'SELECT TRIM(sm.location) AS location
             FROM vp_stock_movements sm
             INNER JOIN (
                 SELECT MAX(id) AS max_id
                 FROM vp_stock_movements
                 WHERE product_id = ? AND warehouse_id = ?
             ) latest ON latest.max_id = sm.id
             WHERE sm.product_id = ? AND sm.warehouse_id = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('iiii', $productId, $warehouseId, $productId, $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $movementLocation = trim((string) ($row['location'] ?? ''));
        if ($movementLocation === '') {
            return null;
        }
        if ($warehouseName !== '' && strcasecmp($movementLocation, $warehouseName) === 0) {
            return null;
        }

        return $movementLocation;
    }
}
