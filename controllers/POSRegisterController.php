<?php
require_once 'models/pos/pos.php';
//require_once 'models/pos/product.php';

class POSRegisterController
{
    private $product;
    private $pos;

    public function __construct($conn)
    {
        $this->pos     = new pos($conn);
    }
   
    public function index()
    {
        // slug => label
        $categories = getCategories();

        // Add "All Products" (slug => label)
        // Put it first:
        $categories = ['allProducts' => 'All Products'] + $categories;

        // slug => svg icon
        $categoryIcons = [
            'allProducts' => '
                ☰
            ',
            'paintings' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="18" height="18" rx="2" />
                    <circle cx="8.5" cy="8.5" r="1.5" />
                    <path d="M21 15l-5-5L5 21" />
                </svg>
            ',
            'sculptures' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 2l3 7h7l-5.5 4.5L18 22l-6-4-6 4 1.5-8.5L2 9h7z" />
                </svg>
            ',
            'textiles' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 4l4 4-4 4M8 4L4 8l4 4M4 8h16v12H4z" />
                </svg>
            ',
            'jewelry' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="8" />
                    <path d="M12 4v8l4 4" />
                </svg>
            ',
            'homeandliving' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12l9-9 9 9M9 21V9h6v12" />
                </svg>
            ',
            'book' => '
                <svg class="h-4 w-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 19.5A2.5 2.5 0 016.5 17H20M6.5 2H20v15H6.5A2.5 2.5 0 014 14.5v-10A2.5 2.5 0 016.5 2z" />
                </svg>
            ',
        ];

        // Build final array: slug => [label, icon]
        $categoryData = [];
        foreach ($categories as $slug => $label) {
            $categoryData[$slug] = [
                'label' => $label,
                'icon'  => $categoryIcons[$slug] ?? '', // fallback
            ];
        }

        renderTemplate('views/pos_register/index.php', [
            'categories' => $categoryData
        ]);
    }
    
    /**
     * DataTables AJAX endpoint for products list
     */
    public function productsAjax()
    {
        // Prefer infinite-scroll params if provided
        $pageNo  = isset($_GET['page_no']) ? max(1, (int)$_GET['page_no']) : null;
        $perPage = isset($_GET['per_page']) ? max(1, (int)$_GET['per_page']) : null;

        if ($pageNo !== null && $perPage !== null) {
            $length = $perPage;
            $start  = ($pageNo - 1) * $perPage;
            $draw   = 0; // not needed for infinite scroll
        } else {
            // DataTables fallback
            $draw   = isset($_GET['draw'])   ? (int)$_GET['draw']   : 0;
            $start  = isset($_GET['start'])  ? (int)$_GET['start']  : 0;
            $length = isset($_GET['length']) ? (int)$_GET['length'] : 12;
            $pageNo = (int) floor($start / max(1, $length)) + 1;
        }

        // DataTables global search
        $searchValue = '';
        if (isset($_GET['search']['value'])) {
            $searchValue = trim($_GET['search']['value']);
        }

        // Custom filters (add back category/product_code if needed)
        $category    = isset($_GET['category']) ? trim($_GET['category']) : '';
        //$productCode = isset($_GET['product_code']) ? trim($_GET['product_code']) : '';
        $productName = isset($_GET['product_name']) ? trim($_GET['product_name']) : '';

        // ordering (allow simple defaults for infinite scroll)
        $orderColumnIndex = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 2;
        $orderDir         = isset($_GET['order'][0]['dir'])    ? $_GET['order'][0]['dir']         : 'asc';

        $columns = [
            0 => 'image',
            1 => 'item_code',
            2 => 'title',
            3 => 'stock_qty',
            4 => 'price',
        ];
        $orderColumn = isset($columns[$orderColumnIndex]) ? $columns[$orderColumnIndex] : 'title';

        $result = $this->pos->getProductsDataTable(
            $start,
            $length,
            $searchValue,
            $productName,
            $orderColumn,
            $orderDir,
            $category
        );

        // total_pages helpful for frontend
        $totalFiltered = (int) ($result['recordsFiltered'] ?? 0);
        $totalPages = ($length > 0) ? (int) ceil($totalFiltered / $length) : 1;

        $response = [
            'draw'            => $draw,
            'recordsTotal'    => $result['recordsTotal'] ?? 0,
            'recordsFiltered' => $result['recordsFiltered'] ?? 0,
            'data'            => $result['data'] ?? [],
            'current_page'    => $pageNo,
            'per_page'        => $length,
            'total_pages'     => $totalPages,
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }

    /**
     * Proxy: Add to cart (Exotic India API)
     */
    public function cartAdd()
    {
        $code = isset($_POST['code']) ? trim($_POST['code']) : '';
        if ($code === '') {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Missing product code.']);
            exit;
        }

        $qty = isset($_POST['qty']) ? (int)$_POST['qty'] : 1;
        if ($qty < 1) $qty = 1;

        $discountCoupon = isset($_POST['discountcoupondetails']) ? trim($_POST['discountcoupondetails']) : '';
        $giftVoucher = isset($_POST['giftvoucherdetails']) ? trim($_POST['giftvoucherdetails']) : '';
        $variation = isset($_POST['variation']) ? trim($_POST['variation']) : '';
        $options = isset($_POST['options']) ? trim($_POST['options']) : '';

        $query = [];
        if ($discountCoupon !== '') $query['discountcoupondetails'] = $discountCoupon;
        if ($giftVoucher !== '') $query['giftvoucherdetails'] = $giftVoucher;

        $url = 'https://www.exoticindia.com/cart/add';
        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        $postData = [
            'code' => $code,
            'qty'  => $qty
        ];

        if ($variation !== '') $postData['variation'] = $variation;
        if ($options !== '') $postData['options'] = $options;

        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'Content-Type: application/x-www-form-urlencoded',
            'x-adminapitest: 1',
        ];

        if (!empty($_SESSION['x_api_euid'])) {
            $headers[] = 'x-api-euid: ' . $_SESSION['x_api_euid'];
        }

        // Debug: log equivalent curl request
        $curlParts = [];
        $curlParts[] = 'curl -X POST';
        foreach ($headers as $h) {
            $curlParts[] = '-H ' . escapeshellarg($h);
        }
        $curlParts[] = escapeshellarg($url);
        $curlParts[] = '--data ' . escapeshellarg(http_build_query($postData));
        print('[POS cart-add] ' . implode(' ', $curlParts)); die;


        $capturedEuid = null;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $headerLine) use (&$capturedEuid) {
            $len = strlen($headerLine);
            $header = explode(':', $headerLine, 2);
            if (count($header) < 2) {
                return $len;
            }

            $name = strtolower(trim($header[0]));
            if ($name === 'x-api-euid') {
                $capturedEuid = trim($header[1]);
            }
            return $len;
        });

        $response = curl_exec($ch); print_r($response); die; 
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!empty($capturedEuid)) {
            $_SESSION['x_api_euid'] = $capturedEuid;
        }

        header('Content-Type: application/json');

        if ($error) {
            http_response_code(502);
            echo json_encode(['error' => $error]);
            exit;
        }

        if ($status) {
            http_response_code($status);
        }

        // Pass-through response (expected to be JSON)
        echo $response;
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
