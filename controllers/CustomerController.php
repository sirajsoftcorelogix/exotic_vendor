 <?php
require_once 'models/customer/Customer.php';
$customer = new Customer($conn);
class CustomerController {
    public function index() {
        is_login();
        global $customer;
        $data = array();
        renderTemplate('views/customer/index.php', $data, 'Manage Customer');
    }
}
?>