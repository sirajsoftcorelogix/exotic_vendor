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
        
                case 'orders':
                default:
                    // Orders list – search by order_number
                    $params = [
                        'page'   => 'orders',
                        'action' => 'list',
                    ];
                    if ($query !== '') {
                        $params['order_number'] = $query;
                    }
                    break;
            }
        
            $redirectUrl = 'index.php?' . http_build_query($params);
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
?>