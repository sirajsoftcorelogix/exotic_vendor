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
            case 'getTeamMembers':
                $controller->getTeamMembers();
                break;
            case 'checkVendorName':
                $controller->checkVendorName();
                break;
            case 'checkEmail':
                $controller->checkEmail();
                break;
            case 'checkPhoneNumber':
                $controller->checkPhoneNumber();
                break;
            case 'sales_analytics':
                $controller->importSalesAnalyticsData();
                break;
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
            case 'get_order_details':
                $controller->getOrderDetails();
                break;
            case 'import_orders':
                $controller->importOrders();
                break;
            case 'update_status':
                $controller->updateStatus();
                break;
            case 'get_order_details_html':
                $controller->getOrderDetailsHTML();
                break;
            case 'update_import':
                $controller->updateImportedOrders();
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
            case 'upload_invoice':
                $controller->uploadInvoice();
                break;
            case 'get_po_details':
                $controller->getPoDetails();
                break;
            case 'delete_invoice':
                $controller->deleteInvoice();
                break;
            case 'get_po':
                $controller->getPO();                
                break;
            case 'preview_pdf':
                $controller->previewPDF();
                break;
            case 'approve':
                $controller->updateStatus();
                break;
            case 'add_payment':
                $controller->addPayment();
                break;
            case 'get_payment':
                $controller->getPayments();
                break;
            case 'remove_payment':
                $controller->removePayment();
                break;
            case 'add_challan':
                $controller->addChallan();
                break;
            case 'get_challan':
                $controller->getChallans();
                break;
            case 'remove_challan':
                $controller->deleteChallan();
                break;
            case 'vendor_search':
                $controller->vendorSearch();
                break;
            case 'custom_po':
                $controller->customPO();
                break;
            case 'custompo_post':
                $controller->customPOSave();
                break;
            case 'product_items':
                $controller->productItems();
                break;
            case 'stock_purchase':
                $controller->stockPurchase();
                break;
            default:
                $controller->index();
                break;
        }
        break;
    case 'payement_terms':
        require_once 'controllers/PaymenetTermsController.php';
        $controller = new PaymenetTermsController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addRecord':
                $controller->addPTRecord();
                break;
            case 'updateRecord':
                $controller->addPTRecord();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'paymentTermsDetails':
                $controller->getPTDetails();
            default:
                $controller->index();
                break;
        }
        break;
    case 'orders_priority_status':
        require_once 'controllers/OrdersPriorityStatusController.php';
        $controller = new OrdersPriorityStatusController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addRecord':
                $controller->addOPSRecord();
                break;
            case 'updateRecord':
                $controller->addOPSRecord();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'getDetails':
                $controller->getOPSDetails();
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
    case 'products':
        require_once 'controllers/ProductsController.php';
        $controller = new ProductsController();
        $action = $_GET['action'] ?? 'list';
        switch ($action) {
            case 'list':
                $controller->product_list();
                break;
            case 'view':
                $controller->product_view();
                break;
            case 'create':
                $controller->product_create();
                break;
            case 'edit':
                $controller->product_edit();
                break;
            case 'delete':
                $controller->product_delete();
                break;
            default:
                $controller->product_list();
                break;
        }
        break;
    case 'roles':
        require_once 'controllers/RolesController.php';
        $controller = new RolesController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            /*case 'addRecord':
                $controller->addRecord();
                break;*/
            case 'add':
                $controller->addRRecord();
                break;
            case 'add_role':
                $controller->addRecord();
                break;
            case 'edit':
                $controller->editRecord();
                break;
            case 'edit_role':
                $controller->updateRecord();
                break;
            /*case 'updateRecord':
                $controller->addRecord();
                break;*/
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'roleDetails':
                $controller->getDetails();
            default:
                $controller->index();
                break;
        }
        break;
    case 'teams':
        require_once 'controllers/TeamsController.php';
        $controller = new TeamsController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addRecord':
                $controller->addRecord();
                break;
            case 'updateRecord':
                $controller->addRecord();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'getDetails':
                $controller->getDetails();
            default:
                $controller->index();
                break;
        }
        break;
    case 'modules':
        require_once 'controllers/ModulesController.php';
        $controller = new ModulesController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addRecord':
                $controller->addRecord();
                break;
            case 'updateRecord':
                $controller->addRecord();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'getDetails':
                $controller->getDetails();
            default:
                $controller->index();
                break;
        }
        break;
    case 'notifications':
        require_once 'controllers/NotificationController.php';
        $controller = new NotificationController();
        switch ($action) {
            case 'fetch_notifications':
                $controller->fetchNotifications();   
                break;
            case 'mark_as_read':
                $controller->markAsRead();
                break;
            default:
                $controller->fetchNotifications();   
                break;
        }
        break;
    default:
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
}
