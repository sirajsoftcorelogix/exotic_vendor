<?php
require_once 'models/invoice/invoice.php';
require_once 'models/order/order.php';
require_once 'models/user/user.php';
require_once 'models/comman/tables.php';
require_once 'models/customer/Customer.php';
$invoiceModel = new Invoice($conn);
$ordersModel = new Order($conn);
$usersModel = new User($conn);
$commanModel = new Tables($conn);
class PosInvoiceController
{

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
    global $conn;

    $sql = "
    SELECT 
        i.id,
        i.invoice_number,
        i.invoice_date,
        i.status,
        i.total_amount,

        o.order_number,
        o.payment_type,

        c.name AS customer_name,

        IFNULL((
            SELECT SUM(amount) 
            FROM pos_payments 
            WHERE invoice_id = i.id
        ),0) AS paid_amount,

        i.total_amount - IFNULL((
            SELECT SUM(amount) 
            FROM pos_payments 
            WHERE invoice_id = i.id
        ),0) AS due_amount

    FROM vp_invoices i

    LEFT JOIN vp_order_info o 
        ON o.id = i.vp_order_info_id

    LEFT JOIN vp_customers c 
        ON c.id = i.customer_id
WHERE IFNULL(o.payment_type,'') = 'offline' 
    ";

    if (!empty($_GET['order_number'])) {
        $sql .= " AND o.order_number LIKE '%" . $conn->real_escape_string($_GET['order_number']) . "%'";
    }

    if (!empty($_GET['status'])) {
        $sql .= " AND i.status = '" . $conn->real_escape_string($_GET['status']) . "'";
    }

    if (!empty($_GET['from_date'])) {
        $sql .= " AND i.invoice_date >= '" . $_GET['from_date'] . "'";
    }

    if (!empty($_GET['to_date'])) {
        $sql .= " AND i.invoice_date <= '" . $_GET['to_date'] . "'";
    }

    if (!empty($_GET['type'])) {
        $sql .= " AND o.payment_type = '" . $conn->real_escape_string($_GET['type']) . "'";
    }

    if (!empty($_GET['customer_id'])) {
        $sql .= " AND i.customer_id = " . intval($_GET['customer_id']);
    }

    if (!empty($_GET['amount_min'])) {
        $sql .= " AND i.total_amount >= " . floatval($_GET['amount_min']);
    }

    if (!empty($_GET['amount_max'])) {
        $sql .= " AND i.total_amount <= " . floatval($_GET['amount_max']);
    }


    $sql .= " ORDER BY i.id DESC";

    $res = $conn->query($sql);

    $data = [];

    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }

    echo json_encode($data);
    exit;
}
    /* ===============================
       DELETE
    =============================== */
    public function delete()
    {
        global $conn;

        $id = $_POST['id'];

        $stmt = $conn->prepare("DELETE FROM vp_invoices WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();

        echo json_encode(["success" => true]);
    }

    /* ===============================
       PREVIEW
    =============================== */
    public function preview()
    {
        global $conn;

        $invoiceId = $_POST['invoice_id'];

        $items = $conn->query("
            SELECT * FROM invoice_items
            WHERE invoice_id = $invoiceId
        ");

        ob_start();
        require "views/invoices/preview_template.php";
        $html = ob_get_clean();

        echo json_encode([
            "success" => true,
            "html" => $html
        ]);
    }

    /* ===============================
       CREATE FROM ORDER
    =============================== */
    public function create_auto_from_order()
    {
        global $conn;

        $data = json_decode(file_get_contents("php://input"), true);
        $orderId = $data['orderid'];

        $conn->query("
        INSERT INTO vp_invoices
        (order_id, total_amount, status, created_at)
        VALUES ($orderId,0,'final',NOW())
        ");

        $invoiceId = $conn->insert_id;

        echo json_encode([
            "success" => true,
            "invoice_id" => $invoiceId
        ]);
    }

    /* ===============================
       CREATE FROM PAYMENT
    =============================== */
    public function create_from_payment()
    {
        global $conn;

        $data = json_decode(file_get_contents("php://input"), true);
        $paymentId = $data['payment_id'];

        $payment = $conn->query("
        SELECT * FROM pos_payments WHERE id=$paymentId
        ")->fetch_assoc();

        $conn->query("
        INSERT INTO vp_invoices
        (customer_id, total_amount, status, created_at)
        VALUES ({$payment['customer_id']}, {$payment['amount']}, 'final', NOW())
        ");

        $invoiceId = $conn->insert_id;

        $conn->query("
        UPDATE pos_payments SET invoice_id=$invoiceId
        WHERE id=$paymentId
        ");

        echo json_encode([
            "success" => true,
            "invoice_id" => $invoiceId
        ]);
    }

    /* ===============================
       GET SINGLE
    =============================== */
    public function get_single_invoice()
    {
        global $conn;

        $id = $_GET['id'];

        $res = $conn->query("
        SELECT * FROM vp_invoices WHERE id=$id
        ");

        echo json_encode([
            "success" => true,
            "invoice" => $res->fetch_assoc()
        ]);
    }

    /* ===============================
       UPDATE STATUS
    =============================== */
    public function update_status()
    {
        global $conn;

        $id = $_POST['id'];
        $status = $_POST['status'];

        $stmt = $conn->prepare("
        UPDATE vp_invoices SET status=? WHERE id=?
        ");

        $stmt->bind_param("si", $status, $id);
        $stmt->execute();

        echo json_encode(["success" => true]);
    }

    /* ===============================
       PDF
    =============================== */
    public function generate_pdf()
    {
        global $conn;

        $invoiceId = $_GET['invoice_id'];

        require 'views/invoices/pdf.php';
    }
}
