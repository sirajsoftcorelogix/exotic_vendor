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

        $customers = $customerModel->getAllCustomers($limit, $offset, $filters);
        $total_records = $customerModel->countAllCustomers($filters);
        
        $data = [
            'customers' => $customers,
            'page_no' => $page,
            'total_pages' => ceil($total_records / $limit),
            'total_records' => $total_records,
            'limit' => $limit,
            'filters' => $filters
        ];
        
        renderTemplate('views/customer/list.php', $data, 'Customers');
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