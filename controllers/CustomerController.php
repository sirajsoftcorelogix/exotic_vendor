 <?php
require_once 'models/customer/Customer.php';
$customerModel = new Customer($conn);
class CustomerController {
    public function index() {
        is_login();
        global $customerModel;

        // 1. Capture Inputs
        $search = $_GET['search'] ?? '';
        $state  = $_GET['state'] ?? '';
        // Use 'p' or 'page_no' to avoid conflict with your main routing 'page=customer'
        $pageNo = isset($_GET['page_no']) ? max(1, intval($_GET['page_no'])) : 1; 
        $limit  = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
        
        $offset = ($pageNo - 1) * $limit;

        // 2. Fetch Data
        $customers = $customerModel->getCustomers($search, $state, $limit, $offset);
        $totalRows = $customerModel->getTotalCustomersCount($search, $state);
        
        // NEW: Fetch States for the dropdown
        $availableStates = $customerModel->getUniqueStates();

        // 3. Prepare Data
        $data = [
            'customers'   => $customers,
            'states'      => $availableStates, // Pass states here
            'filters'     => ['search' => $search, 'state' => $state, 'limit' => $limit, 'page_no' => $pageNo],
            'pagination'  => ['total_pages' => ceil($totalRows / $limit), 'total_rows' => $totalRows]
        ];

        renderTemplate('views/customer/index.php', $data, 'Manage Customer');
    }
    public function list() {
        is_login();
        global $customerModel;
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page
        $offset = ($page - 1) * $limit;
       //filter
        $filters = [];
        if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
            $filters['search'] = trim($_GET['search']);
        }
        if (isset($_GET['order_number']) && !empty(trim($_GET['order_number']))) {
            $filters['order_number'] = trim($_GET['order_number']);
        }
        if (isset($_GET['state']) && !empty(trim($_GET['state']))) {
            $filters['state'] = trim($_GET['state']);
        }

        $isAdminCustomerList = isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1;

        $warehouseId = isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : 0;
        $warehouseName = '';
        require_once 'models/user/user.php';
        $usersModel = new User($GLOBALS['conn']);
        if ($warehouseId > 0) {
            $wh = $usersModel->getWarehouseById($warehouseId);
            $warehouseName = $wh['address_title'] ?? ('Warehouse #' . $warehouseId);
        }

        $customers = $customerModel->getAllCustomersWithPurchaseStats($filters, $limit, $offset);
        $total_records = $customerModel->countAllCustomersWithPurchaseStats($filters);

        $flash = $_SESSION['customer_pos_list_flash'] ?? null;
        unset($_SESSION['customer_pos_list_flash']);

        $data = [
            'customers' => $customers,
            'page_no' => $page,
            'total_pages' => $limit > 0 ? (int)ceil($total_records / $limit) : 1,
            'total_records' => $total_records,
            'limit' => $limit,
            'filters' => $filters,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
            'is_admin_customer_list' => $isAdminCustomerList,
            'flash' => $flash,
        ];

        renderTemplate('views/customer/list.php', $data, 'Customers');
    }

    public function delete_customer()
    {
        is_login();
        global $customerModel;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customerId <= 0) {
            $_SESSION['customer_pos_list_flash'] = ['type' => 'error', 'message' => 'Invalid customer.'];
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        $res = $customerModel->deleteCustomer($customerId);
        $_SESSION['customer_pos_list_flash'] = [
            'type' => $res['success'] ? 'success' : 'error',
            'message' => $res['message'] ?? '',
        ];
        header('Location: ' . base_url('?page=customer&action=list'));
        exit;
    }

    public function view() {
        is_login();
        global $customerModel;
        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
        if ($customerId <= 0) {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        $customer = $customerModel->getCustomerById($customerId);
        if (!$customer) {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $sort = (string)($_GET['sort'] ?? 'new_to_old');
        $allowedSort = ['new_to_old', 'old_to_new', 'ship_by_date_desc', 'ship_by_date_asc'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'new_to_old';
        }

        $pageNo = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($pageNo - 1) * $limit;

        $statusGroup = (string)($_GET['status_group'] ?? 'all');
        $allowedStatusGroups = ['all', 'pending', 'progress', 'completed', 'cancelled'];
        if (!in_array($statusGroup, $allowedStatusGroups, true)) {
            $statusGroup = 'all';
        }

        $paymentType = (string)($_GET['payment_type'] ?? 'all');
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));
        $tab = (string)($_GET['tab'] ?? 'orders');
        $allowedTabs = ['orders', 'invoices', 'dispatches', 'activity'];
        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'orders';
        }
        $viewMode = (string)($_GET['view_mode'] ?? 'table');
        if (!in_array($viewMode, ['cards', 'table'], true)) {
            $viewMode = 'cards';
        }

        $filters = [
            'sort' => $sort,
            'status_group' => $statusGroup,
        ];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($paymentType !== '' && $paymentType !== 'all') {
            $filters['payment_type'] = $paymentType;
        }
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $headerSummary = $customerModel->getCustomerHeaderSummary($customerId);
        $statusCounts = [
            'pending' => (int)($headerSummary['pending'] ?? 0),
            'progress' => (int)($headerSummary['progress'] ?? 0),
            'completed' => (int)($headerSummary['completed'] ?? 0),
            'cancelled' => (int)($headerSummary['cancelled'] ?? 0),
        ];

        $orders = [];
        $totalRecords = 0;
        $totalPages = 1;
        if ($tab === 'orders') {
            $totalRecords = $customerModel->countOrderItemsByCustomerId($customerId, $filters);
            $totalPages = $limit > 0 ? (int)ceil($totalRecords / $limit) : 1;
            if ($pageNo > $totalPages && $totalPages > 0) {
                $pageNo = $totalPages;
                $offset = ($pageNo - 1) * $limit;
            }
            $orders = $customerModel->getOrderItemsByCustomerId($customerId, $limit, $offset, $filters);
        }

        $invoices = [];
        $dispatches = [];
        $activityLog = [];
        if ($tab === 'invoices') {
            $invoices = $customerModel->getInvoicesByCustomerId($customerId);
        } elseif ($tab === 'dispatches') {
            $dispatches = $customerModel->getDispatchesByCustomerId($customerId);
        } elseif ($tab === 'activity') {
            $activityLog = $customerModel->getCustomerActivityLog($customerId);
        }

        $data = [
            'customer' => $customer,
            'orders' => $orders,
            'total_records' => $totalRecords,
            'page_no' => $pageNo,
            'total_pages' => max(1, $totalPages),
            'limit' => $limit,
            'sort' => $sort,
            'filters' => $filters,
            'search' => $search,
            'status_group' => $statusGroup,
            'payment_type' => $paymentType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'tab' => $tab,
            'view_mode' => $viewMode,
            'orderDates' => [
                'first_order_date' => $headerSummary['first_order_date'] ?? null,
                'last_order_date' => $headerSummary['last_order_date'] ?? null,
            ],
            'open_order_value' => (float)($headerSummary['open_order_value'] ?? 0),
            'primary_currency' => strtoupper(trim((string)($headerSummary['primary_currency'] ?? 'INR'))) ?: 'INR',
            'customerOrderCount' => (int)($headerSummary['line_count'] ?? 0),
            'customerTotalSpent' => $headerSummary['total_spent'] ?? 0,
            'customerAverageOrderValue' => $headerSummary['average_order_value'] ?? 0,
            'statusCounts' => $statusCounts,
            'invoices' => $invoices,
            'dispatches' => $dispatches,
            'activityLog' => $activityLog,
            'customer_id' => $customerId,
        ];
        renderTemplate('views/customer/view.php', $data, 'Customer Details');
    }

    public function export_orders()
    {
        is_login();
        global $customerModel;

        $customerId = isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0;
        if ($customerId <= 0) {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }

        $customer = $customerModel->getCustomerById($customerId);
        if (!$customer) {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }

        $search = trim((string)($_GET['search'] ?? ''));
        $sort = (string)($_GET['sort'] ?? 'new_to_old');
        $allowedSort = ['new_to_old', 'old_to_new', 'ship_by_date_desc', 'ship_by_date_asc'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'new_to_old';
        }

        $statusGroup = (string)($_GET['status_group'] ?? 'all');
        $paymentType = (string)($_GET['payment_type'] ?? 'all');
        $dateFrom = trim((string)($_GET['date_from'] ?? ''));
        $dateTo = trim((string)($_GET['date_to'] ?? ''));

        $filters = ['sort' => $sort, 'status_group' => $statusGroup];
        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($paymentType !== '' && $paymentType !== 'all') {
            $filters['payment_type'] = $paymentType;
        }
        if ($dateFrom !== '') {
            $filters['date_from'] = $dateFrom;
        }
        if ($dateTo !== '') {
            $filters['date_to'] = $dateTo;
        }

        $rows = $customerModel->getOrderItemsByCustomerIdForExport($customerId, $filters);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]+/', '_', (string)($customer['name'] ?? 'customer'));
        $filename = 'customer_' . $customerId . '_' . $safeName . '_orders_' . date('Ymd_His') . '.csv';

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'w');
        if ($out === false) {
            exit;
        }

        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($out, [
            'Order Number',
            'Item Code',
            'Product Title',
            'Status',
            'Order Date',
            'Ship By Date',
            'Payment Type',
            'Quantity',
            'Unit Price',
            'Line Total',
            'Invoice Number',
        ]);

        foreach ($rows as $row) {
            $qty = (int)($row['quantity'] ?? 0);
            $unit = (float)($row['itemprice'] ?? 0);
            fputcsv($out, [
                $row['order_number'] ?? '',
                $row['item_code'] ?? '',
                $row['title'] ?? '',
                $row['status'] ?? '',
                $row['order_date'] ?? '',
                $row['esd'] ?? '',
                $row['payment_type'] ?? '',
                $qty,
                $unit,
                (float)($row['finalprice'] ?? ($unit * $qty)),
                $row['invoice_number'] ?? '',
            ]);
        }

        fclose($out);
        exit;
    }
}
?>