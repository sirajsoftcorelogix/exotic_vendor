<?php

require_once __DIR__ . '/../helpers/pos_payment_receipt.php';
require_once __DIR__ . '/../models/payment/Payment.php';

class PaymentsController
{
    /** @var Payment */
    private $paymentModel;

    public function __construct()
    {
        global $conn;
        $this->paymentModel = new Payment($conn);
    }

    /* =========================
       PAYMENT LIST PAGE
    ==========================*/
    public function index()
    {
        renderTemplate('views/payments/index.php', []);
    }

    /* =========================
       AJAX PAYMENT LIST
    ==========================*/
    public function list_ajax()
    {
        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $filters = [
            'payment_mode' => $_GET['payment_mode'] ?? '',
            'from_date' => $_GET['from_date'] ?? '',
            'to_date' => $_GET['to_date'] ?? '',
            'order_id' => $_GET['order_id'] ?? '',
            'order_number' => $_GET['order_number'] ?? '',
            'order_exact' => isset($_GET['order_exact']) && (string)$_GET['order_exact'] === '1',
            'amount_min' => $_GET['amount_min'] ?? '',
            'amount_max' => $_GET['amount_max'] ?? '',
        ];

        echo json_encode($this->paymentModel->searchListAjax($filters));
        exit;
    }

    /* =========================
       VIEW SINGLE PAYMENT
    ==========================*/
    public function view()
    {
        $id = (int)($_GET['id'] ?? 0);
        $payment = $this->paymentModel->findByIdWithDetails($id);

        renderTemplate('views/payments/view.php', [
            'payment' => $payment,
        ]);
    }

    /* =========================
       DELETE PAYMENT
    ==========================*/
    public function delete()
    {
        is_login();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            vendorJsonResponse(['success' => false, 'message' => 'Payment id missing']);
        }

        $orderNumber = $this->paymentModel->getOrderNumberByPaymentId($id);
        if ($orderNumber === '') {
            vendorJsonResponse(['success' => false, 'message' => 'Payment not found']);
        }

        if (!$this->paymentModel->deleteById($id)) {
            vendorJsonResponse(['success' => false, 'message' => 'Could not delete payment']);
        }

        global $conn;
        pos_payment_refresh_order_snapshots($conn, $orderNumber);

        vendorJsonResponse(['success' => true]);
    }

    /* =========================
       PRINT RECEIPT
    ==========================*/
    public function receipt()
    {
        $id = (int)($_GET['id'] ?? 0);
        $payment = $this->paymentModel->findForReceipt($id);
        if (!$payment) {
            http_response_code(404);
            echo 'Payment not found';
            exit;
        }

        $receiptNumber = trim((string)($payment['receipt_number'] ?? ''));
        $orderNumber = trim((string)($payment['order_number'] ?? ''));
        if ($receiptNumber !== '' && $orderNumber !== '') {
            header(
                'Location: index.php?page=pos_register&action=checkout-receipt'
                . '&order_number=' . rawurlencode($orderNumber)
                . '&receipt_number=' . rawurlencode($receiptNumber)
            );
            exit;
        }

        $defaultWarehouseAddress = $this->paymentModel->getDefaultWarehouseAddress();

        require 'views/payments/receipt.php';
        exit;
    }

    public function add_payment()
    {
        $order_id = (int)($_GET['order_id'] ?? 0);

        if ($order_id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Order ID missing']);
            exit;
        }

        $order = $this->paymentModel->findVpOrderById($order_id);
        if (!$order || trim((string)($order['order_number'] ?? '')) === '') {
            echo json_encode(['success' => false, 'message' => 'Order not found']);
            exit;
        }

        $orderNumKey = trim((string)$order['order_number']);
        $paid = $this->paymentModel->sumPaidByOrderNumber($orderNumKey);

        echo json_encode([
            'success' => true,
            'order_number' => $order['order_number'],
            'customer_id' => $order['customer_id'],
            'paid' => $paid,
        ]);
        exit;
    }

    public function save_payment()
    {
        global $conn;

        is_login();
        header('Content-Type: application/json; charset=utf-8');

        $postOrderKey = trim((string)($_POST['order_id'] ?? ''));
        $stage = strtolower(trim((string)($_POST['payment_stage'] ?? 'final')));
        $note = trim((string)($_POST['note'] ?? ''));

        $user_id = pos_payment_resolve_session_user_id();
        $warehouse_id = (int)($_SESSION['warehouse_id'] ?? 0);
        $postOrderInt = (ctype_digit($postOrderKey) && $postOrderKey !== '') ? (int)$postOrderKey : 0;

        $orderNumberStr = '';
        $customerId = 0;

        if ($postOrderInt > 0) {
            $vpRow = $this->paymentModel->findVpOrderRowById($postOrderInt);
        } else {
            $vpRow = null;
        }

        if (!$vpRow && $postOrderKey !== '') {
            $vpRow = $this->paymentModel->findVpOrderRowByNumber($postOrderKey);
        }

        if ($vpRow) {
            $orderNumberStr = (string)($vpRow['order_number'] ?? '');
            $customerId = (int)($vpRow['customer_id'] ?? 0);
        } elseif ($postOrderKey !== '') {
            $anchor = $this->paymentModel->findPaymentAnchorByOrderKey($postOrderKey);
            if ($anchor) {
                $orderNumberStr = (string)($anchor['order_number'] ?? '');
                $customerId = (int)($anchor['customer_id'] ?? 0);
            }
        }

        if ($orderNumberStr === '') {
            echo json_encode(['success' => false, 'message' => 'Order data missing']);
            exit;
        }

        $orderTotal = pos_payment_resolve_order_total($conn, $orderNumberStr);
        $paidTotal = $this->paymentModel->sumPaidByOrderNumber($orderNumberStr);
        $targetTotal = max(0.0, round($orderTotal - $paidTotal, 2));

        $splitBundle = pos_payment_resolve_splits_from_payload($_POST);
        $splitErrors = pos_payment_validate_splits($splitBundle, $targetTotal, $stage);
        if ($splitErrors !== []) {
            echo json_encode(['success' => false, 'message' => $splitErrors[0]]);
            exit;
        }

        $splits = $splitBundle['splits'];
        if (pos_payment_split_cod_total($splits) > 0.001) {
            $stage = 'advance';
        }

        if (count($splits) > 1 && $note !== '') {
            $note .= "\n";
        }
        if (count($splits) > 1) {
            $parts = [];
            foreach ($splits as $split) {
                $parts[] = strtoupper((string)$split['mode']) . ' ₹' . number_format((float)$split['amount'], 2);
            }
            $note .= 'Payment split: ' . implode(', ', $parts);
        }

        try {
            $short = pos_payment_resolve_short_code_for_warehouse($conn, $warehouse_id);
            $receiptNumber = pos_payment_generate_next_receipt_number($conn, $short);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Receipt number error: ' . $e->getMessage()]);
            exit;
        }

        $sortedSplits = $splits;
        usort($sortedSplits, static function (array $a, array $b): int {
            $aCod = pos_payment_is_cod_mode((string)($a['mode'] ?? '')) ? 1 : 0;
            $bCod = pos_payment_is_cod_mode((string)($b['mode'] ?? '')) ? 1 : 0;

            return $aCod <=> $bCod;
        });

        $paymentIds = [];
        $lastInsert = null;
        foreach ($sortedSplits as $split) {
            $insertRes = pos_payment_insert_row(
                $conn,
                $orderNumberStr,
                $receiptNumber,
                $customerId,
                $stage,
                (string)$split['mode'],
                (float)$split['amount'],
                (string)$split['transaction_id'],
                $note,
                $user_id,
                $warehouse_id,
                true,
                $orderTotal
            );
            if (!$insertRes['success']) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment save failed: ' . ($insertRes['error'] ?? 'unknown'),
                    'warehouse_id_used' => $insertRes['warehouse_id_used'],
                    'order_amount' => $insertRes['order_amount'] ?? null,
                    'pending_amount' => $insertRes['pending_amount'] ?? null,
                ]);
                exit;
            }
            $lastInsert = $insertRes;
            $paymentIds[] = (int)($insertRes['payment_id'] ?? 0);
        }

        pos_payment_refresh_order_snapshots($conn, $orderNumberStr);

        $invoiceMeta = [
            'success' => true,
            'invoice_id' => 0,
            'created' => false,
        ];
        try {
            $invoiceStatus = pos_payment_resolve_auto_invoice_status($conn, $orderNumberStr);
            if ($invoiceStatus === 'final') {
                $invoiceMeta = pos_payment_finalize_invoice_for_order($conn, $orderNumberStr);
            } elseif ($invoiceStatus === 'proforma') {
                $invoiceMeta = pos_payment_ensure_proforma_invoice_for_order($conn, $orderNumberStr);
            }
        } catch (Throwable $invoiceError) {
            $invoiceMeta = [
                'success' => false,
                'invoice_id' => 0,
                'created' => false,
                'message' => 'Payment saved but invoice step failed: ' . $invoiceError->getMessage(),
            ];
        }

        echo json_encode([
            'success' => true,
            'receipt_number' => $receiptNumber,
            'payment_id' => $paymentIds[0] ?? 0,
            'payment_ids' => $paymentIds,
            'order_amount' => $lastInsert['order_amount'] ?? null,
            'pending_amount' => $lastInsert['pending_amount'] ?? null,
            'invoice_id' => (int)($invoiceMeta['invoice_id'] ?? 0),
            'invoice_created' => !empty($invoiceMeta['created']),
            'invoice_message' => $invoiceMeta['message'] ?? null,
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }

    public function create_from_payment()
    {
        global $conn;

        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data)) {
            vendorJsonResponse(['success' => false, 'message' => 'Invalid request body']);
        }

        $paymentId = (int)($data['payment_id'] ?? 0);
        if ($paymentId <= 0) {
            vendorJsonResponse(['success' => false, 'message' => 'Payment id missing']);
        }

        $orderNumber = $this->paymentModel->getOrderNumberByPaymentId($paymentId);
        if ($orderNumber === '') {
            vendorJsonResponse(['success' => false, 'message' => 'Payment not found']);
        }

        $invoiceStatus = pos_payment_resolve_auto_invoice_status($conn, $orderNumber);
        if ($invoiceStatus === 'final') {
            $invoiceMeta = pos_payment_finalize_invoice_for_order($conn, $orderNumber);
            if (!empty($invoiceMeta['success']) && !empty($invoiceMeta['invoice_id'])) {
                vendorJsonResponse([
                    'success' => true,
                    'invoice_id' => (int)$invoiceMeta['invoice_id'],
                    'created' => !empty($invoiceMeta['created']),
                    'proforma' => false,
                ]);
            }
            vendorJsonResponse([
                'success' => false,
                'message' => $invoiceMeta['message'] ?? 'Final invoice could not be created.',
            ]);
        }

        if ($invoiceStatus === 'proforma') {
            $invoiceMeta = pos_payment_ensure_proforma_invoice_for_order($conn, $orderNumber);
            if (!empty($invoiceMeta['success']) && !empty($invoiceMeta['invoice_id'])) {
                vendorJsonResponse([
                    'success' => true,
                    'invoice_id' => (int)$invoiceMeta['invoice_id'],
                    'created' => !empty($invoiceMeta['created']),
                    'proforma' => true,
                ]);
            }
            vendorJsonResponse([
                'success' => false,
                'message' => $invoiceMeta['message'] ?? 'Proforma invoice could not be created.',
            ]);
        }

        vendorJsonResponse([
            'success' => false,
            'message' => 'No payments recorded for this order.',
        ]);
    }

    public function get_payment_summary()
    {
        global $conn;

        $orderNumber = trim((string)($_GET['order_number'] ?? ''));
        if ($orderNumber === '') {
            echo json_encode(['success' => false]);
            exit;
        }

        $orderTotal = pos_payment_resolve_order_total($conn, $orderNumber);
        $paid = $this->paymentModel->sumPaidByOrderNumber($orderNumber);
        $pending = round($orderTotal - $paid, 2);

        echo json_encode([
            'success' => true,
            'order_total' => $orderTotal,
            'paid' => $paid,
            'pending' => $pending,
        ]);
        exit;
    }

    public function update_payment()
    {
        global $conn;

        $id = (int)($_POST['id'] ?? 0);
        $amount = (float)($_POST['amount'] ?? 0);
        $mode = (string)($_POST['payment_type'] ?? '');
        $stage = (string)($_POST['payment_stage'] ?? 'final');
        $transaction = (string)($_POST['transaction_id'] ?? '');
        $note = (string)($_POST['note'] ?? '');
        $date = (string)($_POST['payment_date'] ?? '');

        if ($id <= 0) {
            echo json_encode(['success' => false, 'message' => 'Payment id missing']);
            exit;
        }

        $orderNumber = $this->paymentModel->getOrderNumberByPaymentId($id);
        $editorUserId = pos_payment_resolve_session_user_id();

        if (!$this->paymentModel->updatePayment($id, $amount, $mode, $stage, $transaction, $note, $date, $editorUserId)) {
            echo json_encode(['success' => false, 'message' => 'Payment update failed']);
            exit;
        }

        $invoiceMeta = [
            'success' => true,
            'attempted' => false,
            'fully_paid' => false,
            'invoice_id' => 0,
            'created' => false,
        ];
        if ($orderNumber !== '') {
            pos_payment_refresh_order_snapshots($conn, $orderNumber);
            $invoiceMeta = pos_payment_finalize_invoice_for_order($conn, $orderNumber);
        }

        echo json_encode([
            'success' => true,
            'invoice_id' => (int)($invoiceMeta['invoice_id'] ?? 0),
            'invoice_created' => !empty($invoiceMeta['created']),
            'invoice_message' => $invoiceMeta['message'] ?? null,
        ]);
        exit;
    }

    public function get_single_payment()
    {
        $id = (int)($_GET['id'] ?? 0);
        $payment = $this->paymentModel->findSingleWithOrderId($id);

        echo json_encode([
            'success' => true,
            'payment' => $payment,
        ]);
        exit;
    }
}
