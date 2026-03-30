<?php

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
        $sql = "
SELECT 
    p.id,
    p.order_id,
    p.order_number,
    p.payment_date,
    p.amount,
       p.order_id,
    p.payment_mode,
    p.payment_stage,
    u.name AS user_name,
    w.address_title AS warehouse,

    (
        IFNULL(
            (
                SELECT SUM(o.finalprice) 
                FROM vp_orders o 
                WHERE o.order_number COLLATE utf8mb4_unicode_ci
                      = p.order_number COLLATE utf8mb4_unicode_ci
            ), 0
        )
        -
        IFNULL(
            (
                SELECT SUM(p2.amount) 
                FROM pos_payments p2 
                WHERE p2.order_number COLLATE utf8mb4_unicode_ci
                      = p.order_number COLLATE utf8mb4_unicode_ci
                AND p2.id <= p.id
            ), 0
        )
    ) AS pending_amount

FROM pos_payments p

LEFT JOIN vp_users u 
    ON u.id = p.user_id

LEFT JOIN exotic_address w 
    ON w.id = p.warehouse_id

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

        if (!empty($_GET['order_number'])) {
            $sql .= " AND p.order_number LIKE ?";
            $params[] = "%" . $_GET['order_number'] . "%";
            $types .= "s";
        }

        if (!empty($_GET['amount_min'])) {
            $sql .= " AND p.amount >= ?";
            $params[] = $_GET['amount_min'];
            $types .= "d";
        }

        if (!empty($_GET['amount_max'])) {
            $sql .= " AND p.amount <= ?";
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
                w.address_title as warehouse
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN 	exotic_address w ON w.id = p.warehouse_id
            WHERE p.id = ?
        ");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $payment = $stmt->get_result()->fetch_assoc();

        require 'views/payments/receipt.php';
    }
    public function add_payment()
    {
        global $conn;

        $order_id = $_GET['order_id'] ?? 0;

        if (!$order_id) {
            echo json_encode(["success" => false, "message" => "Order ID missing"]);
            exit;
        }

        // get order info from first payment
        $stmt = $conn->prepare("
        SELECT order_number, customer_id
        FROM pos_payments
        WHERE order_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            echo json_encode(["success" => false, "message" => "No previous payment found"]);
            exit;
        }

        // total paid
        $stmt2 = $conn->prepare("
        SELECT SUM(amount) as paid
        FROM pos_payments
        WHERE order_id = ?
    ");
        $stmt2->bind_param("i", $order_id);
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

        $order_id = $_POST['order_id'];
        // echo $order_id;
        // exit;
        $amount = $_POST['amount'];
        $mode = $_POST['payment_type'];
        $stage = $_POST['payment_stage'];
        $transaction = $_POST['transaction_id'];
        $note = $_POST['note'];

        $user_id = $_SESSION['user_id'] ?? 0;
        $warehouse_id = $_SESSION['warehouse_id'] ?? 0;

        // fetch order info from FIRST payment
        $stmt = $conn->prepare("
        SELECT order_number, customer_id
        FROM pos_payments
        WHERE order_id = ?
        ORDER BY id ASC
        LIMIT 1
    ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();

        if (!$order) {
            echo json_encode(["success" => false, "message" => "Order data missing"]);
            exit;
        }

        $order_number = $order['order_number'];
        $customer_id = $order['customer_id'];

        $stmt2 = $conn->prepare("
        INSERT INTO pos_payments
        (order_id, order_number, customer_id, payment_stage, payment_mode, amount, transaction_id, note, payment_date, user_id, warehouse_id, currency, payment_status, created_at)
        VALUES (?,?,?,?,?,?,?,?,NOW(),?,?, 'INR', 'success', NOW())
    ");

        // $stmt2->bind_param(
        //     "iisdsdssii",
        //     $order_id,
        //     $order_number,
        //     $customer_id,
        //     $stage,
        //     $mode,
        //     $amount,
        //     $transaction,
        //     $note,
        //     $user_id,
        //     $warehouse_id
        // );
        $stmt2->bind_param(
            "iiissdssii",
            $order_id,
            $order_number,
            $customer_id,
            $stage,
            $mode,
            $amount,
            $transaction,
            $note,
            $user_id,
            $warehouse_id
        );
        $stmt2->execute();
        $paymentId = $conn->insert_id;

        /* 🔥 FINAL PAYMENT → UPDATE INVOICE */
        if ($stage === 'final') {

            $order_number_safe = $conn->real_escape_string($order_number);

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
        echo json_encode(["success" => true]);
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
        SELECT SUM(amount) as paid 
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

        $stmt = $conn->prepare("
        UPDATE pos_payments 
        SET amount=?, payment_mode=?, payment_stage=?, transaction_id=?, note=?, payment_date=?
        WHERE id=?
    ");

        $stmt->bind_param(
            "dsssssi",
            $amount,
            $mode,
            $stage,
            $transaction,
            $note,
            $date,
            $id
        );

        $stmt->execute();

        echo json_encode(["success" => true]);
    }
    public function get_single_payment()
    {

        global $conn;

        $id = $_GET['id'];

        $stmt = $conn->prepare("SELECT * FROM pos_payments WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        $payment = $stmt->get_result()->fetch_assoc();

        echo json_encode([
            "success" => true,
            "payment" => $payment
        ]);
    }
}
