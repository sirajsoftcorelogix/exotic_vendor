<?php
require_once 'bootstrap/init/init.php';
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'list';
//$domain = "http://".$_SERVER['SERVER_NAME']."/VendorPortal"; 
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
            case 'addPost':
                $controller->addPost();
                break;
            case 'update':
                $controller->addEditUser();
                break;
            case 'delete':
                $controller->delete();
                break;    
            case 'forgotPassword':
                $controller->forgotPassword();
                break; 
            case 'sendResetLink':
                $controller->sendResetLink();
                break;       
            default:
                $controller->index();
                break;
        }
        break;
	case 'tenants':
        require_once 'controllers/TenantsController.php';
		$controller = new TenantsController($conn);
		
        switch ($action) {
            case 'list':
                $controller->index();
                break;
            case 'add_edit_tenant':
                $controller->add_edit_tenant();
                break;
            case 'update':
                $controller->update();
                break;
            case 'delete':
               // $controller->delete();
                break;
            default:
                $controller->index();
                break;
        }
        break;	
    case 'stores':
        require_once 'controllers/StoresController.php';
		$controller = new StoresController($conn);
        switch ($action) {
            case 'list':
                $controller->index();
                break;
            case 'add_edit_store':
                $controller->add_edit_store();
                break;
            case 'update':
                $controller->update();
                break;
            case 'delete':
                $controller->delete();
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

    default:
        require_once 'controllers/DashboardController.php';
        $controller = new DashboardController();
        $controller->index();
        break;
}