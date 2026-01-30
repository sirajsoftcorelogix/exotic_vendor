<?php
require_once 'models/pos/pos.php';
//require_once 'models/pos/product.php';

class POSRegisterController
{
    private $product;
    private $pos;

    public function __construct($conn)
    {
        //$this->product = new Product($conn);
        $this->pos     = new pos($conn);
    }

    public function index()
    {
        // view just needs the container & table; data comes via AJAX
        renderTemplate('views/pos_register/index.php', []);
    }

    /**
     * DataTables AJAX endpoint for products list
     */
    public function productsAjax()
    {
        // DataTables core params
        $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 0;
        $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
        $length = isset($_GET['length']) ? (int)$_GET['length'] : 12;

        // global search (not required if you only want custom filters)
        $searchValue = '';
        if (isset($_GET['search']['value'])) {
            $searchValue = trim($_GET['search']['value']);
        }

        // custom filters
        $category    = isset($_GET['category'])      ? trim($_GET['category'])      : '';
        $productCode = isset($_GET['product_code'])  ? trim($_GET['product_code'])  : '';
        $productName = isset($_GET['product_name'])  ? trim($_GET['product_name'])  : '';

        // ordering
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
        $orderDir         = isset($_GET['order'][0]['dir'])    ? $_GET['order'][0]['dir']         : 'asc';

        // map column index -> actual DB column
        $columns = [
            0 => 'image_url',      // not really used for sorting
            1 => 'product_code',
            2 => 'product_name',
            3 => 'category_slug',
            4 => 'stock_qty',
            5 => 'price',
        ];
        $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'product_name';

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $category,
            $productCode,
            $productName,
            $orderColumn,
            $orderDir
        );

        $response = [
            'draw'            => $draw,
            'recordsTotal'    => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data'            => $result['data'],
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Helper method to fetch registers
     */
    private function getRegisters()
    {
        // TODO: Implement database query
        return [];
    }
}
