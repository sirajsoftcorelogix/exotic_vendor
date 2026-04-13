<?php

require_once 'models/direct_purchase/directPurchase.php';
require_once 'models/vendor/vendor.php';

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

        $header = [
            'vendor_id' => $vendorId,
            'invoice_number' => $invoiceNumber,
            'invoice_date' => $invoiceDate,
            'invoice_file' => $invoiceFile,
            'subtotal' => (float) ($_POST['subtotal'] ?? 0),
            'discount' => (float) ($_POST['discount'] ?? 0),
            'igst_total' => (float) ($_POST['igst_total'] ?? 0),
            'sgst_total' => (float) ($_POST['sgst_total'] ?? 0),
            'cgst_total' => (float) ($_POST['cgst_total'] ?? 0),
            'round_off' => (float) ($_POST['round_off'] ?? 0),
            'grand_total' => (float) ($_POST['grand_total'] ?? 0),
            'payment_mode' => trim((string) ($_POST['payment_mode'] ?? '')) ?: null,
            'payment_reference' => trim((string) ($_POST['payment_reference'] ?? '')) ?: null,
            'payment_date' => trim((string) ($_POST['payment_date'] ?? '')) ?: null,
            'payment_notes' => trim((string) ($_POST['payment_notes'] ?? '')) ?: null,
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
     * @return array<int, array<string, mixed>>
     */
    private function collectLineItemsFromPost()
    {
        $codes = $_POST['item_code'] ?? [];
        if (!is_array($codes)) {
            return [];
        }
        $skus = $_POST['sku'] ?? [];
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
        $n = count($codes);
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
