<?php 

class DashboardController {
        public function index() {
            is_login();
            global $addonsModel;
            $data = [];
            renderTemplate('views/dashboard/index.php', $data, 'Dashboard');
        }
		
		public function indexheader() {
            is_login();
        
            $type  = isset($_GET['type']) ? strtolower(trim($_GET['type'])) : 'orders';
            $query = isset($_GET['q']) ? trim($_GET['q']) : '';
        
            $params = [];

            switch ($type) {
                case 'purchase_orders':
                    // Purchase Orders list – search by PO number
                    $params = [
                        'page'   => 'purchase_orders',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['po_number'] = $query;
                    }
                    break;
        
                case 'invoice':
                    // Customer invoices in dispatch – search by invoice number
                    $params = [
                        'page'   => 'dispatch',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['invoice_number'] = $query;
                    }
                    break;
        
                case 'awb':
                    // Dispatch list filtered by AWB
                    $params = [
                        'page'   => 'dispatch',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['awb_number'] = $query;
                    }
                    break;
        
                case 'customer':
                    // Customer list – uses ?search=
                    $params = [
                        'page'   => 'customer',
                        'action' => 'index',
                    ];
                    if ($query !== '') {
                        $params['search'] = $query;
                    }
                    break;
        
                case 'product':
                    // Sales Order list – search by product (item code, SKU, product title)
                    $params = [
                        'page'   => 'orders',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['q'] = $query;
                    }
                    break;

                case 'orders':
                default:
                    // Orders list – search sales order based on order_number, itemCode, SKU, product title, PO number
                    $params = [
                        'page'   => 'orders',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['q'] = $query;
                    }
                    break;
            }

            $redirectUrl = 'index.php?' . http_build_query($params);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
?>