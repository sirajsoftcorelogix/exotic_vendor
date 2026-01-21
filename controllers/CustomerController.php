 <?php
require_once 'models/customer/Customer.php';
$customerModel = new Customer($conn);
class CustomerController {
    public function index() {
        is_login();
        global $customerModel;
        $data = array();
        $data = $customerModel->getCustomers(); 
        renderTemplate('views/customer/index.php', $data, 'Manage Customer');
    }
}
?>