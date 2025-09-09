<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

require_once 'bootstrap/init/init.php';
$page = $_GET['page'] ?? 'orders';
$action = $_GET['action'] ?? 'list';
//$domain = "http://".$_SERVER['SERVER_NAME']."/exotic_vendor"; 

switch ($page) {
	
	case 'users':
        require_once 'controllers/UsersController.php';
		$controller = new UsersController($conn);
		
        switch ($action) {
            case 'login':
                $controller->login();
                break;
            case 'loginProcess':
                $controller->loginProcess();
                break;
            case 'logout':
                $controller->logout();
                break;
            case 'list':
                $controller->index();
                break;
            case 'add':
                $controller->addEditUser();
                break;
            case 'addUser':
                $controller->addPost();
                break;
            case 'updateUser':
                $controller->addEditUser();
                break;
            case 'userDetails':
                $controller->getUserDetails();
                break;
            case 'deleteUser':
                $controller->delete();
                break;
            case 'updateProfile':
                $controller->updateUserProfile();
                break;    
            case 'forgotPassword':
                $controller->forgotPassword();
                break; 
            case 'sendResetLink':
                $controller->sendResetLink();
                break;  
            case 'resetPassword':
                $controller->resetPassword();
                break;
            case 'updateCaptcha':
                $controller->updateCaptcha();
                break;            
            case 'validateCaptcha':
                $controller->validateCaptcha();
                break;
            case 'resetPasswordProcess':
                $controller->resetPasswordProcess();
                break;
            case 'verifyResetToken':
                $controller->verifyResetToken();
                break;
            default:
                $controller->index();
                break;
        }
        break;
	case 'vendors':
        require_once 'controllers/VendorsController.php';
        $controller = new VendorsController($conn);
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addPost': // Method to Add / Edit Vendor
                $controller->addVendorRecord();
                break;
            case 'delete':
                $controller->delete();
                break;
            case 'vendorDetails':
                $controller->getVendorDetails();
            case 'allCountries':
                $controller->getAllCountries();
                break;
            case 'getStates':
                $controller->getStatesByCountry(105); // India ID = 105
                break;
            case 'getBankDetails':
                $controller->getBankDetails();
                break;
            case 'bankDetails':
                $controller->addBankDetails();
            /*case 'add':
                $controller->addEditVendor();
                break;*/
            
            /*case 'update':
                $controller->addEditVendor();
                break;*/
            default:
                $controller->index();
                break;
        }
        break;
    case 'orders':
        require_once 'controllers/OrdersController.php';
        $controller = new OrdersController($conn);
        switch ($action) {
            case 'import':
                $controller->importOrders();
                break;
            case 'list':
                $controller->index();   
                break;
            case 'view':
                $controller->viewOrder();   
                break;
           
            default:
                $controller->index();
                break;
        }
        break;
    case 'purchase_orders':
        require_once 'controllers/PurchaseOrdersController.php';
        $controller = new PurchaseOrdersController($conn);        
        switch ($action) {
            case 'list':
                $controller->index();
                break;
            case 'view':
                $controller->viewPurchaseOrder();
                break;
            case 'create':
                $controller->createPurchaseOrder();
                break;
            case 'edit':
                $controller->editPurchaseOrder();
                break;
            case 'create_post':
                $controller->createPurchaseOrderPost();
                break;
            case 'edit_post':
                $controller->updatePurchaseOrderPost();
                break;
            case 'order_items':
                $controller->viewOrderItems();
                break;
            case 'download':
                $controller->downloadPurchaseOrder();
                break;
            case 'delete':
                $controller->deletePurchaseOrder(); 
                break;
            case 'update_status':
                $controller->updateStatus();
                break;
            case 'toggle_star':
                $controller->toggleStar();
                break;
            case 'emailToVendor':
                $controller->emailToVendor();
                break;
            default:
                $controller->index();
                break;
        }
        break;
    case 'dashboard':        
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
    default:
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
}
