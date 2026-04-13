<?php

require_once 'models/direct_purchase/directPurchase.php';
require_once 'models/vendor/vendor.php';
require_once 'models/product/product.php';

$directPurchaseModel = new DirectPurchase($conn);
$directPurchaseVendorModel = new vendor($conn);

class DirectPurchaseController
{
    public function index()
    {
        is_login();
        global $directPurchaseModel;

        $pageNo = isset($_GET['page_no']) ? (int) $_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20;

        $filters = [
            'search_text' => isset($_GET['search_text']) ? trim((string) $_GET['search_text']) : '',
            'invoice_date_from' => isset($_GET['invoice_date_from']) ? trim((string) $_GET['invoice_date_from']) : '',
            'invoice_date_to' => isset($_GET['invoice_date_to']) ? trim((string) $_GET['invoice_date_to']) : '',
            'vendor_id' => isset($_GET['vendor_id']) ? (int) $_GET['vendor_id'] : 0,
        ];

        $filters = $this->sanitizeDirectPurchaseListDateFilters($filters);

        $result = $directPurchaseModel->searchPurchases($filters, $pageNo, $limit);
        $totalPages = $limit > 0 ? (int) ceil($result['total'] / $limit) : 1;

        global $directPurchaseVendorModel;
        $vendors = $directPurchaseVendorModel->getAllVendors();

        renderTemplate('views/direct_purchase/index.php', [
            'purchases' => $result['rows'],
            'total_records' => $result['total'],
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'filters' => $filters,
            'vendors' => $vendors,
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

    public function add()
    {
        is_login();
        global $directPurchaseVendorModel;
        $vendors = $directPurchaseVendorModel->getAllVendors();
        renderTemplate('views/direct_purchase/form.php', [
            'purchase' => null,
            'items' => [],
            'vendors' => $vendors,
            'is_edit' => false,
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
        $items = $directPurchaseModel->getItems($id);
        $productModel = new product($conn);
        foreach ($items as &$it) {
            $img = $productModel->getImageForPurchaseLine(
                (string) ($it['item_code'] ?? ''),
                (string) ($it['sku'] ?? ''),
                (string) ($it['color'] ?? ''),
                (string) ($it['size'] ?? '')
            );
            $it['product_image'] = $img ?? '';
        }
        unset($it);
        $vendors = $directPurchaseVendorModel->getAllVendors();

        renderTemplate('views/direct_purchase/form.php', [
            'purchase' => $purchase,
            'items' => $items,
            'vendors' => $vendors,
            'is_edit' => true,
        ], 'Edit direct purchase');
    }

    public function save()
    {
        is_login();
        global $directPurchaseModel;

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ?page=direct_purchase&action=list');
            exit;
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $vendorId = isset($_POST['vendor_id']) ? (int) $_POST['vendor_id'] : 0;
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

        $items = $this->collectLineItemsFromPost();
        if (empty($items)) {
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Add at least one line item.'];
            $redir = $id > 0 ? '?page=direct_purchase&action=edit&id=' . $id : '?page=direct_purchase&action=add';
            header('Location: ' . $redir);
            exit;
        }

        $invoiceFile = null;
        if ($id > 0) {
            $existing = $directPurchaseModel->getById($id);
            $invoiceFile = $existing['invoice_file'] ?? null;
        }

        if (!empty($_FILES['invoice_file']['name']) && isset($_FILES['invoice_file']['error']) && $_FILES['invoice_file']['error'] === UPLOAD_ERR_OK) {
            $invoiceFile = $this->storeInvoiceUpload($_FILES['invoice_file']);
            if ($invoiceFile === null) {
                $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Invoice upload failed. Use PDF, JPG or PNG.'];
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

        $header = [
            'vendor_id' => $vendorId,
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
            'grand_total' => (float) ($_POST['grand_total'] ?? 0),
            'created_by' => isset($_SESSION['user']['id']) ? (int) $_SESSION['user']['id'] : null,
        ];

        try {
            if ($id > 0) {
                $directPurchaseModel->updatePurchase($id, $header, $items);
                $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => 'Purchase updated.'];
            } else {
                $directPurchaseModel->insertPurchase($header, $items);
                $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => 'Purchase saved.'];
            }
        } catch (Throwable $e) {
            error_log('DirectPurchase save: ' . $e->getMessage());
            $_SESSION['direct_purchase_flash'] = ['type' => 'error', 'text' => 'Could not save purchase.'];
        }

        header('Location: ?page=direct_purchase&action=list');
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
        $directPurchaseModel->delete($id);
        $_SESSION['direct_purchase_flash'] = ['type' => 'success', 'text' => 'Purchase deleted.'];
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

        foreach (['invoice_date_from', 'invoice_date_to'] as $key) {
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
        $units = $_POST['unit'] ?? [];
        $gstAmts = $_POST['gst_amount'] ?? [];
        $lineTots = $_POST['line_total'] ?? [];

        $out = [];
        $n = max(count($skus), is_array($qtys) ? count($qtys) : 0);
        for ($i = 0; $i < $n; $i++) {
            $qty = isset($qtys[$i]) ? (float) $qtys[$i] : 0;
            if ($qty <= 0) {
                continue;
            }
            $out[] = [
                'item_code' => isset($codes[$i]) ? trim((string) $codes[$i]) : '',
                'sku' => isset($skus[$i]) ? trim((string) $skus[$i]) : '',
                'color' => isset($colors[$i]) ? trim((string) $colors[$i]) : '',
                'size' => isset($sizes[$i]) ? trim((string) $sizes[$i]) : '',
                'cost_per_item' => isset($costs[$i]) ? (float) $costs[$i] : 0,
                'qty' => $qty,
                'hsn' => isset($hsns[$i]) ? trim((string) $hsns[$i]) : '',
                'gst_rate' => isset($rates[$i]) ? (float) $rates[$i] : 0,
                'unit' => isset($units[$i]) ? trim((string) $units[$i]) : '',
                'gst_amount' => isset($gstAmts[$i]) ? (float) $gstAmts[$i] : 0,
                'line_total' => isset($lineTots[$i]) ? (float) $lineTots[$i] : 0,
            ];
        }
        return $out;
    }

    /**
     * @param array<string, mixed> $file $_FILES element
     */
    private function storeInvoiceUpload($file)
    {
        $uploadDir = __DIR__ . '/../uploads/direct_purchase/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $name = $file['name'];
        $tmp = $file['tmp_name'];
        $parts = explode('.', $name);
        $ext = strtolower(end($parts));
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        if (!in_array($ext, $allowed, true)) {
            return null;
        }
        $newName = 'dp_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $dest = $uploadDir . $newName;
        if (!move_uploaded_file($tmp, $dest)) {
            return null;
        }
        return 'uploads/direct_purchase/' . $newName;
    }
}
