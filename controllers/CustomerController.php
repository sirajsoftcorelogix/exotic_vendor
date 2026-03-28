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
        if (isset($_GET['state']) && !empty(trim($_GET['state']))) {
            $filters['state'] = trim($_GET['state']);
        }

        $isAdminCustomerList = isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1;

        $filterWarehouseId = 0;

        $warehouseId = isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : 0;
        $warehouseName = '';
        $warehouses = [];
        $adminFilterWarehouseName = '';
        require_once 'models/user/user.php';
        $usersModel = new User($GLOBALS['conn']);
        if ($warehouseId > 0) {
            $wh = $usersModel->getWarehouseById($warehouseId);
            $warehouseName = $wh['address_title'] ?? ('Warehouse #' . $warehouseId);
        }
        if ($isAdminCustomerList) {
            $warehouses = $usersModel->getAllWarehouses();
            if (isset($_GET['filter_warehouse_id'])) {
                $rawWh = (int)$_GET['filter_warehouse_id'];
                if ($rawWh > 0) {
                    $validWhIds = array_map('intval', array_column($warehouses, 'id'));
                    if (in_array($rawWh, $validWhIds, true)) {
                        $filterWarehouseId = $rawWh;
                    }
                }
            }
            if ($filterWarehouseId > 0) {
                $whF = $usersModel->getWarehouseById($filterWarehouseId);
                $adminFilterWarehouseName = $whF['address_title'] ?? ('Warehouse #' . $filterWarehouseId);
            }
        }

        $filters['filter_warehouse_id'] = $filterWarehouseId;

        $search = isset($filters['search']) ? trim((string)$filters['search']) : '';
        if ($isAdminCustomerList) {
            if ($filterWarehouseId > 0) {
                $customers = $customerModel->getPosCustomersForWarehouse($filterWarehouseId, $search, $limit, $offset);
                $total_records = $customerModel->countPosCustomersForWarehouse($filterWarehouseId, $search);
            } else {
                $customers = $customerModel->getAllCustomersWithPurchaseStats($search, $limit, $offset);
                $total_records = $customerModel->countAllCustomersWithPurchaseStats($search);
            }
        } elseif ($warehouseId > 0) {
            $customers = $customerModel->getPosCustomersForWarehouse($warehouseId, $search, $limit, $offset);
            $total_records = $customerModel->countPosCustomersForWarehouse($warehouseId, $search);
        } else {
            $customers = [];
            $total_records = 0;
        }

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
            'warehouses' => $warehouses,
            'admin_filter_warehouse_name' => $adminFilterWarehouseName,
            'flash' => $flash,
        ];

        renderTemplate('views/customer/list.php', $data, 'Customers');
    }

    public function delete_customer()
    {
        is_login();
        global $customerModel;
        $isAdmin = isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1;
        $warehouseId = isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : 0;
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        if (!$isAdmin && $warehouseId <= 0) {
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        $customerId = isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0;
        if ($customerId <= 0) {
            $_SESSION['customer_pos_list_flash'] = ['type' => 'error', 'message' => 'Invalid customer.'];
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        if (!$isAdmin && !$customerModel->isCustomerInPosWarehouse($customerId, $warehouseId)) {
            $_SESSION['customer_pos_list_flash'] = ['type' => 'error', 'message' => 'Invalid customer for this POS.'];
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
        //require_once 'models/order/order.php';
        //$orderModel = new Order($GLOBALS['conn']);
        require_once 'models/comman/tables.php';
        $commanModel = new Tables($GLOBALS['conn']);
        $customerId = $_GET['customer_id'] ?? null;
        if (!$customerId) {
            header("Location: " . base_url('?page=customer&action=list'));
            exit;
        }
        $customer = $customerModel->getCustomerById($customerId);
        if (!$customer) {
            header("Location: " . base_url('?page=customer&action=list'));
            exit;
        }
        $isAdmin = isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1;
        $warehouseId = isset($_SESSION['warehouse_id']) ? (int)$_SESSION['warehouse_id'] : 0;
        $adminListWh = 0;
        if ($isAdmin && isset($_GET['list_wh'])) {
            $rawListWh = (int)$_GET['list_wh'];
            if ($rawListWh > 0) {
                require_once 'models/user/user.php';
                $usersModelView = new User($GLOBALS['conn']);
                $validIds = array_map('intval', array_column($usersModelView->getAllWarehouses(), 'id'));
                if (in_array($rawListWh, $validIds, true)) {
                    $adminListWh = $rawListWh;
                }
            }
        }
        if (!$customerModel->isCustomerViewableInListContext((int)$customerId, $isAdmin, $warehouseId, true, $adminListWh)) {
            $_SESSION['customer_pos_list_flash'] = [
                'type' => 'error',
                'message' => 'You do not have access to this customer.',
            ];
            header('Location: ' . base_url('?page=customer&action=list'));
            exit;
        }
        //print_array($customer);
        //search filters
        $search = $_GET['search'] ?? '';
        $filters = [];
        if (!empty($search)) {
            $filters['search'] = $search;
        }
        $sort = $_GET['sort'] ?? 'new_to_old';
        // Fetch orders for this customer
        $orders = $customerModel->getOrderItemsByCustomerId($customerId, 20, 0, $filters);
        $assignmentDates = [];          
        foreach ($orders as $key => $order) {
            $orders[$key]['status_log'] = $commanModel->get_order_status_log($order['id']);  
            $assignmentDates[$order['id']] =  $orders[$key]['status_log']['change_date'] ?? '';         
        }
        $spents = $customerModel->getCustomerTotalSpent($customerId);
        $statusCounts = $customerModel->getCustomerOrderStatusCounts($customerId);
        //print_array($spents);
        $data = [
            'customer' => $customer,
            'orders' => $orders ?? [],
            'total_records' => count($orders),
            'page_no' => 1,
            'limit' => 20,
            'sort' => $sort,
            'filters' => $filters,
            'assignmentDates' => $assignmentDates,
            'customerOrderCount' => $customerModel->getCustomerOrderCount($customerId),
            'customerTotalSpent' => $spents['total_spent'] ?? 0,
            'customerAverageOrderValue' => $spents['average_order_value'] ?? 0,
            'statusCounts' => $statusCounts
        ];
        renderTemplate('views/customer/view.php', $data, 'Customer Details');
    }
}
?>