<?php

require_once __DIR__ . '/../helpers/pos_payment_receipt.php';
require_once 'models/user/user.php';
require_once 'models/PosInvoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/comman/tables.php';

$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$commanModel = new Tables($conn);

class PaymentsController
{
    /* =========================
       PAYMENT LIST PAGE
    ==========================*/
    public function index()
    {
        // echo '123'; exit;
        global $conn;

        $page_no = $_GET['page_no'] ?? 1;
        $per_page = 20;

        $offset = ($page_no - 1) * $per_page;

        $sql = "SELECT COUNT(*) as total FROM pos_payments";
        $total = $conn->query($sql)->fetch_assoc()['total'];

        $total_pages = ceil($total / $per_page);

        $payments = $conn->query("
        SELECT 
            p.*,
            u.name as user,
            w.address_title as warehouse
        FROM pos_payments p
        LEFT JOIN vp_users u ON u.id = p.user_id
        LEFT JOIN exotic_address w ON w.id = p.warehouse_id
        ORDER BY p.id DESC
        LIMIT $offset,$per_page
    ");

        $rows = [];

        while ($r = $payments->fetch_assoc()) {
            $rows[] = $r;
        }

        renderTemplate('views/payments/index.php', [
            'payments' => $rows,
            'total_pages' => $total_pages,
            'page_no' => $page_no
        ]);
    }

    /* =========================
       AJAX PAYMENT LIST
    ==========================*/
    public function list_ajax()
    {
        global $conn;

        // $sql = "
        // SELECT 
        //     p.*,
        //     u.name as user_name,
        //     w.address_title as warehouse
        // FROM pos_payments p
        // LEFT JOIN vp_users u ON u.id = p.user_id
        // LEFT JOIN 	exotic_address w ON w.id = p.warehouse_id
        // WHERE 1=1
        // ";
        // Warehouse: already from LEFT JOIN exotic_address (pos_payments.warehouse_id).
        // vp_orders can have many rows per order_number (line items), so we join an aggregate
        // of vp_orders instead of correlating subqueries or joining vp_orders raw (row duplication).
        $sql = "
SELECT 
    p.id,
    p.order_number,
    p.receipt_number,
    p.payment_date,
    p.payment_amount AS amount,
    p.order_amount,
    p.pending_amount AS balance_snapshot,
    p.payment_mode,
    p.payment_stage,
    u.name AS user_name,
    w.address_title AS warehouse,
    vo.order_id AS order_id,

    (
        IFNULL(vo.order_line_total, 0)
        -
        IFNULL(
            (
                SELECT SUM(p2.payment_amount) 
                FROM pos_payments p2 
                WHERE p2.order_number COLLATE utf8mb4_unicode_ci
                      = p.order_number COLLATE utf8mb4_unicode_ci
                AND p2.id <= p.id
            ), 0
        )
    ) AS pending_balance

FROM pos_payments p

LEFT JOIN vp_users u 
    ON u.id = p.user_id

LEFT JOIN exotic_address w 
    ON w.id = p.warehouse_id

LEFT JOIN (
    SELECT 
        order_number,
        MIN(id) AS order_id,
        SUM(finalprice) AS order_line_total
    FROM vp_orders
    GROUP BY order_number
) vo ON vo.order_number COLLATE utf8mb4_unicode_ci = p.order_number COLLATE utf8mb4_unicode_ci

WHERE 1=1
";
        $params = [];
        $types = "";

        if (!empty($_GET['payment_mode'])) {
            $sql .= " AND p.payment_mode = ?";
            $params[] = $_GET['payment_mode'];
            $types .= "s";
        }

        if (!empty($_GET['from_date'])) {
            $sql .= " AND p.payment_date >= ?";
            $params[] = $_GET['from_date'];
            $types .= "s";
        }

        if (!empty($_GET['to_date'])) {
            $sql .= " AND p.payment_date <= ?";
            $params[] = $_GET['to_date'];
            $types .= "s";
        }

        $orderPkFilter = (
            isset($_GET['order_id'])
            && $_GET['order_id'] !== ''
            && ctype_digit((string)$_GET['order_id'])
        ) ? (int)$_GET['order_id'] : 0;

        if ($orderPkFilter > 0) {
            $onStmt = $conn->prepare('SELECT order_number FROM vp_orders WHERE id = ? LIMIT 1');
            $filterOrderNum = '';
            if ($onStmt) {
                $onStmt->bind_param('i', $orderPkFilter);
                $onStmt->execute();
                $onRow = $onStmt->get_result()->fetch_assoc();
                $onStmt->close();
                $filterOrderNum = trim((string)($onRow['order_number'] ?? ''));
            }
            if ($filterOrderNum !== '') {
                $sql .= ' AND p.order_number = ?';
                $params[] = $filterOrderNum;
                $types .= 's';
            }
        } elseif (!empty($_GET['order_number'])) {
            $exact = isset($_GET['order_exact']) && (string)$_GET['order_exact'] === '1';
            if ($exact) {
                $sql .= ' AND p.order_number = ?';
                $params[] = trim((string)$_GET['order_number']);
                $types .= 's';
            } else {
                $sql .= ' AND p.order_number LIKE ?';
                $params[] = '%' . $_GET['order_number'] . '%';
                $types .= 's';
            }
        }

        if (!empty($_GET['amount_min'])) {
            $sql .= " AND p.payment_amount >= ?";
            $params[] = $_GET['amount_min'];
            $types .= "d";
        }

        if (!empty($_GET['amount_max'])) {
            $sql .= " AND p.payment_amount <= ?";
            $params[] = $_GET['amount_max'];
            $types .= "d";
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $conn->prepare($sql);

        if ($types) {
            $stmt->bind_param($types, ...$params);
        }

        // $stmt->execute();
        // $result = $stmt->get_result();
        $stmt->execute();
        $result = $stmt->get_result();

        // echo '<pre>';
        // print_r($result->fetch_all(MYSQLI_ASSOC));
        // exit;

        $data = [];

        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        echo json_encode($data);
        exit;
    }

    /* =========================
       VIEW SINGLE PAYMENT
    ==========================*/
    public function view()
    {
        global $conn;

        $id = $_GET['id'] ?? 0;

        $stmt = $conn->prepare("
            SELECT 
                p.*,
                u.name as user_name,
                w.address_title as warehouse
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN 	exotic_address w ON w.id = p.warehouse_id
            WHERE p.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $payment = $stmt->get_result()->fetch_assoc();

        renderTemplate('views/payments/view.php', [
            'payment' => $payment
        ]);
    }

    /* =========================
       DELETE PAYMENT
    ==========================*/
    public function delete()
    {
        global $conn;

        $id = $_POST['id'] ?? 0;

        $stmt = $conn->prepare("DELETE FROM pos_payments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode([
            "success" => true
        ]);
        exit;
    }

    /* =========================
       PRINT RECEIPT
    ==========================*/
    public function receipt()
    {
        global $conn;

        $id = $_GET['id'] ?? 0;

        $stmt = $conn->prepare("
            SELECT 
                p.*,
                u.name as user_name,
                w.address_title as warehouse,
                w.address as warehouse_address,
                c.name AS customer_name
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            LEFT JOIN vp_customers c ON c.id = p.customer_id
            WHERE p.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $payment = $stmt->get_result()->fetch_assoc();

        $defaultWarehouseAddress = ['title' => '', 'lines' => []];
        if ($conn instanceof \mysqli) {
            $dwRes = $conn->query(
                'SELECT address_title, display_name, address FROM exotic_address WHERE is_active = 1 ORDER BY is_default DESC, order_no ASC, id ASC LIMIT 1'
            );
            if ($dwRes && ($dw = $dwRes->fetch_assoc())) {
                $defaultWarehouseAddress['title'] = trim((string)($dw['address_title'] ?? ''));
                $addrText = trim((string)($dw['address'] ?? ''));
                if ($addrText === '') {
                    $addrText = trim((string)($dw['display_name'] ?? ''));
                }
                $parts = preg_split('/\r\n|\r|\n/', $addrText);
                $lines = [];
                foreach (is_array($parts) ? $parts : [] as $ln) {
                    $ln = trim((string)$ln);
                    if ($ln !== '') {
                        $lines[] = $ln;
                    }
                }
                $defaultWarehouseAddress['lines'] = $lines;
            }
        }

        require 'views/payments/receipt.php';
        exit;
    }
    public function add_payment()
    {
        global $conn;

        $order_id = $_GET['order_id'] ?? 0;

        if (!$order_id) {
            echo json_encode(["success" => false, "message" => "Order ID missing"]);
            exit;
        }

        $order_id = (int)$order_id;
        $stmtVo = $conn->prepare('SELECT order_number, customer_id FROM vp_orders WHERE id = ? LIMIT 1');
        $order = null;
        if ($stmtVo) {
            $stmtVo->bind_param('i', $order_id);
            $stmtVo->execute();
            $order = $stmtVo->get_result()->fetch_assoc();
            $stmtVo->close();
        }

        if (!$order || trim((string)($order['order_number'] ?? '')) === '') {
            echo json_encode(["success" => false, "message" => "Order not found"]);
            exit;
        }

        $orderNumKey = trim((string)$order['order_number']);

        // total paid
        $stmt2 = $conn->prepare('
        SELECT SUM(amount) as paid
        FROM pos_payments
        WHERE order_number = ?
    ');
        $stmt2->bind_param('s', $orderNumKey);
        $stmt2->execute();

        $paid = $stmt2->get_result()->fetch_assoc()['paid'] ?? 0;

        echo json_encode([
            "success" => true,
            "order_number" => $order['order_number'],
            "customer_id" => $order['customer_id'],
            "paid" => $paid
        ]);
        exit;
    }
    public function save_payment()
    {
        global $conn;

        $postOrderKey = trim((string)($_POST['order_id'] ?? ''));
        $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
        $mode = $_POST['payment_type'] ?? '';
        $stage = $_POST['payment_stage'] ?? 'final';
        $transaction = (string)($_POST['transaction_id'] ?? '');
        $note = (string)($_POST['note'] ?? '');

        $user_id = pos_payment_resolve_session_user_id();
        $warehouse_id = (int)($_SESSION['warehouse_id'] ?? 0);
        $postOrderInt = (ctype_digit($postOrderKey) && $postOrderKey !== '') ? (int)$postOrderKey : 0;

        $orderNumberStr = '';
        $customerId = 0;
        $vpRow = null;
        $anchor = null;

        if ($postOrderInt > 0) {
            $stmt = $conn->prepare('SELECT id, order_number, customer_id FROM vp_orders WHERE id = ? ORDER BY id ASC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $postOrderInt);
                $stmt->execute();
                $vpRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }
        if (!$vpRow && $postOrderKey !== '') {
            $stmt = $conn->prepare('SELECT id, order_number, customer_id FROM vp_orders WHERE order_number = ? ORDER BY id ASC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $postOrderKey);
                $stmt->execute();
                $vpRow = $stmt->get_result()->fetch_assoc();
                $stmt->close();
            }
        }

        if ($vpRow) {
            $orderNumberStr = (string)($vpRow['order_number'] ?? '');
            $customerId = (int)($vpRow['customer_id'] ?? 0);
        } elseif ($postOrderKey !== '') {
            $stmt = $conn->prepare('SELECT order_number, customer_id FROM pos_payments WHERE order_number = ? ORDER BY id ASC LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $postOrderKey);
                $stmt->execute();
                $anchor = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($anchor) {
                    $orderNumberStr = (string)($anchor['order_number'] ?? '');
                    $customerId = (int)($anchor['customer_id'] ?? 0);
                }
            }
        }

        if ($orderNumberStr === '') {
            echo json_encode(["success" => false, "message" => "Order data missing"]);
            exit;
        }

        try {
            $short = pos_payment_resolve_short_code_for_warehouse($conn, $warehouse_id);
            $receiptNumber = pos_payment_generate_next_receipt_number($conn, $short);
        } catch (Throwable $e) {
            echo json_encode(['success' => false, 'message' => 'Receipt number error: ' . $e->getMessage()]);
            exit;
        }

        $insertRes = pos_payment_insert_row(
            $conn,
            $orderNumberStr,
            $receiptNumber,
            $customerId,
            $stage,
            $mode,
            $amount,
            $transaction,
            $note,
            $user_id,
            $warehouse_id,
            true
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
        $newPaymentId = $insertRes['payment_id'];

        /* 🔥 FINAL PAYMENT → UPDATE INVOICE */
        if ($stage === 'final') {

            $order_number_safe = $conn->real_escape_string($orderNumberStr);

            $inv = $conn->query("
            SELECT id 
            FROM vp_invoices 
            WHERE order_number = '$order_number_safe'
            ORDER BY id DESC 
            LIMIT 1
        ")->fetch_assoc();

            if ($inv) {
                $conn->query("
                UPDATE vp_invoices
                SET status = 'final',
                    invoice_date = NOW()
                WHERE id = {$inv['id']}
            ");
            }
        }

        /* ================= FINAL PAYMENT → UPDATE INVOICE DATE ================= */

        //     if ($stage === 'final') {

        //         // get invoice id for this order
        //         $inv = $conn->query("
        //     SELECT id 
        //     FROM vp_invoices 
        //     WHERE order_id = $order_id 
        //     ORDER BY id DESC 
        //     LIMIT 1
        // ")->fetch_assoc();

        //         if ($inv) {

        //             $conn->query("
        //         UPDATE vp_invoices
        //         SET invoice_date = NOW(),
        //             status = 'final'
        //         WHERE id = {$inv['id']}
        //     ");
        //         }
        //     }
        echo json_encode([
            'success' => true,
            'receipt_number' => $receiptNumber,
            'payment_id' => $newPaymentId,
            'order_amount' => $insertRes['order_amount'] ?? null,
            'pending_amount' => $insertRes['pending_amount'] ?? null,
        ]);
        exit;
    }



    public function get_payment_summary()
    {
        global $conn;

        $orderNumber = $_GET['order_number'] ?? '';
        if (!$orderNumber) {
            echo json_encode(['success' => false]);
            exit;
        }

        // total order value
        $res = $conn->query("
        SELECT SUM(finalprice) as total 
        FROM vp_orders 
        WHERE order_number = '$orderNumber'
    ");
        $orderTotal = $res->fetch_assoc()['total'] ?? 0;

        // total paid
        $res2 = $conn->query("
        SELECT SUM(payment_amount) as paid 
        FROM pos_payments 
        WHERE order_number = '$orderNumber'
    ");
        $paid = $res2->fetch_assoc()['paid'] ?? 0;

        $pending = $orderTotal - $paid;

        echo json_encode([
            'success' => true,
            'order_total' => $orderTotal,
            'paid' => $paid,
            'pending' => $pending
        ]);
        exit;
    }
    public function update_payment()
    {

        global $conn;

        $id = $_POST['id'];
        $amount = $_POST['amount'];
        $mode = $_POST['payment_type'];
        $stage = $_POST['payment_stage'];
        $transaction = $_POST['transaction_id'];
        $note = $_POST['note'];
        $date = $_POST['payment_date'];

        $editorUserId = pos_payment_resolve_session_user_id();

        $stmt = $conn->prepare('
        UPDATE pos_payments 
        SET payment_amount=?, payment_mode=?, payment_stage=?, transaction_id=?, note=?, payment_date=?, user_id=?
        WHERE id=?
    ');

        $stmt->bind_param(
            'dsssssii',
            $amount,
            $mode,
            $stage,
            $transaction,
            $note,
            $date,
            $editorUserId,
            $id
        );

        $stmt->execute();

        echo json_encode(["success" => true]);
        exit;
    }
    public function get_single_payment()
    {

        global $conn;

        $id = $_GET['id'];

        $stmt = $conn->prepare(
            'SELECT p.*,
                (
                    SELECT MIN(o.id) FROM vp_orders o
                    WHERE o.order_number COLLATE utf8mb4_unicode_ci = p.order_number COLLATE utf8mb4_unicode_ci
                ) AS order_id
             FROM pos_payments p WHERE p.id = ?'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();

        $payment = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            'success' => true,
            'payment' => $payment,
        ]);
        exit;
    }
}
