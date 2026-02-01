<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Kolkata');

require_once 'bootstrap/init/init.php';
$page = $_GET['page'] ?? 'orders';
$action = $_GET['action'] ?? 'list';

// Prevent directory traversal
$page = basename($page); 
$viewsPath = __DIR__ . '/views';
$pageDir   = $viewsPath . '/' . $page; 
            
if (!is_dir($pageDir)) {
    // Page not implemented → coming soon
    require $viewsPath . '/pages/coming-soon.php';
    exit;
}

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
            case 'products_map':
                $controller->vendorProductsMap();
                break;
            case 'generateBlock':
                $controller->generateProductBlock();
                break;
            case 'saveProductsMap':
                $controller->saveVendorProductsMap();
                break;
            case 'UpdateVendorCode':
                $controller->UpdateVendorCode();
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
                $controller->skuUpdateImportedOrders();
                break;
            case 'update_import_bulk':
                $controller->ordersStatusImportBulk();
                break;
            case 'bulk_update_status':
                $controller->bulkUpdateStatus();
                break;
            case 'bulk_assign_order':
                $controller->bulkAssignOrder();
                break;
            case 'get_orders_customer_id':
                $controller->getOrdersCustomerId();
                break;
            case 'saveSearch':
                $controller->saveSearch();
                break;
            case 'deleteSearch':
                $controller->deleteSearch();
                break;
            case 'invoice_list':
                $controller->invoiceList();
                break;
            case 'payment_list':
                $controller->paymentList();
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
    
    case 'invoices':
        require_once 'controllers/InvoicesController.php';
        $controller = new InvoicesController();
        switch ($action) {
            case 'list':
                $controller->index();
                break;
            case 'create':
                $controller->create();
                break;
            case 'create_post':
                $controller->createPost();
                break;
            case 'view':
                $controller->view();
                break;
            case 'preview':
                $controller->previewInvoice();
                break;
            case 'generate_pdf':
                $controller->generatePdf();
                break;
            case 'fetch_items':
                $controller->fetchItems();
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
            case 'update_api_call':
                $controller->updateApiCall();
                break;   
            case 'import_api_call':
                $controller->importApiCall();         
                break;
            case 'get_product_details_html';
                $controller->getProductDetailsHTML();         
                break;  
            case 'get_vendor_edit_form':
                $controller->getVendorEditForm();         
                break;
            case 'add_vendor_map':
                $controller->addVendorMap();
                break;
            case 'remove_vendor_mapping':
                $controller->removeVendorMapping();
                break;
            case 'updatePriority':
                $controller->updatePriority();
                break;
            case 'create_purchase_list':
                $controller->createPurchaseList();
                break;
            case 'mark_purchased':
                $controller->markPurchased();
                break;
            case 'mark_unpurchased':
                $controller->markUnPurchased();
                break;
            case 'update_purchase_item':
                $controller->updatePurchaseItem();
                break;
            case 'purchase_list':
                $controller->purchaseList();
                break;
            case 'master_purchase_list':
                $controller->masterPurchaseList();
                break;
            case 'get_purchase_list_details':
                $controller->getPurchaseListDetails();
                break;
            case 'delete_purchase_list_item':
                $controller->deletePurchaseItem();
                break;
            /*case 'view':
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
                break;*/
             case 'comment_list':
                require_once 'controllers/PurchaseListCommentController.php';
                $controller = new PurchaseListCommentController($conn);
                $controller->list();
            break;
            
            case 'addComment':
                require_once 'controllers/PurchaseListCommentController.php';
                $controller = new PurchaseListCommentController($conn);
                $controller->add();
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
     case 'inbounding':        
        require_once 'controllers/InboundingController.php';
        $controller = new InboundingController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'exportSelected':
                $controller->exportSelected();
                break;
            case 'deleteSelected': 
                $controller->deleteSelected();
                break;
            case 'form1':
                $controller->getform1();
                break;
            case 'desktopform':
                $controller->getdesktopform();
                break;
            case 'inbound_product_publish':
                $controller->inbound_product_publish();
                break;
            case 'i_photos':
                $controller->i_photos();
                break;
            case 'i_raw_photos':
                $controller->i_raw_photos();
                break;
            case 'itmrawimgsave':
                $controller->itmrawimgsave();
                break;
            case 'itmimgsave':
                $controller->itmimgsave();
                break;
            case 'getNextMaterialOrderAjax':
                $controller->getNextMaterialOrderAjax();
                break;

            case 'addMaterialAjax':
                $controller->addMaterialAjax();
                break;
            // --- ADD THIS NEW CASE ---
            case 'download_photos':
                $controller->download_photos();
                break;
            case 'updatedesktopform':
                $controller->updatedesktopform();
                break;
            case 'saveform1':
                $controller->saveform1();
                break;
            case 'updateform1':
                $controller->updateform1();
                break;
            case 'form3':
                $controller->getform3();
                break;
            case 'submitStep3':
                $controller->submitStep3();
                break;
            case 'label':
                $controller->label();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'getDetails':
                $controller->getDetails();
            case 'getItamcode':
                $controller->getItamcode();
            default:
                $controller->index();
                break;
        }
        break;
    case 'notifications':
        require_once 'controllers/NotificationController.php';
        $controller = new NotificationController();
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'fetch_notifications':
                $controller->fetchNotifications();   
                break;
            case 'mark_as_read':
                $controller->markAsRead();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'getDetails':
                $controller->deleteAllNotifications();
            case 'delete_all_notifications':
                $controller->deleteAllNotifications();
                break;
            case 'is_display':
                $controller->isdisplay();
                break;
            default:
                $controller->fetchNotifications();   
                break;
        }
        break;
    case 'grns':
        require_once 'controllers/GrnsController.php';
        $controller = new GrnsController($conn);
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'view':
                $controller->viewGrn();   
                break;
            case 'create':
                $controller->createGrn();   
                break;
            case 'create_post':
                $controller->createGrnPost();   
                break;
            case 'qrcode':
                $controller->downloadQrCode();
                break;
            case 'delete':
                $controller->deleteGrn();   
                break;
            default:
                $controller->index();   
                break;
        }
        break;
    
    case 'pos_register':
        require_once 'controllers/POSRegisterController.php';
        $controller = new POSRegisterController($conn);
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'products-ajax':
                $controller->productsAjax();   
                break;
            default:
                $controller->index();   
                break;
        }    
        break;        
        default:
            require_once 'controllers/DashboardController.php';
            $controller = new DashboardController();
            $controller->index();
            break;

    case 'currency':
        require_once 'controllers/CurrencyController.php';
        $controller = new CurrencyController($conn);
        switch ($action) {
            case 'list':
                $controller->index();   
                break;
            case 'addRecord':
                $controller->addCurrencyRecord();
                break;
            case 'updateRecord':
                $controller->addCurrencyRecord();
                break;
            case 'deleteRecord':
                $controller->delete();
                break;
            case 'currencyDetails':
                $controller->getCurrencyDetails();
            case 'getRateHistory':
                require_once 'controllers/get_rate_history.php';
                break;
            default:
                $controller->index();
                break;
        }
        break;
}


