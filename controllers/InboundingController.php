 <?php
require_once 'models/inbounding/Inbounding.php';

$inboundingModel = new Inbounding($conn);

global $root_path;
global $domain;
class InboundingController {

    public function index() {
        is_login();
        global $inboundingModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $pt_data = $inboundingModel->getAll($page_no, $limit, $search, $status_filter); 
        // echo"<pre>";print_r($pt_data);exit;
        $data = [
            'inbounding_data' => $pt_data["inbounding"],
            'page_no' => $page_no,
            'total_pages' => $pt_data["totalPages"],
            'search' => $search,
            'totalPages'   => $pt_data["totalPages"],
            'currentPage'  => $pt_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $pt_data["totalRecords"],
            'status_filter'=> $status_filter,
        ];
        
        renderTemplateClean('views/inbounding/index.php', $data, 'Manage Inbounding');
    }
    public function getItamcode(){
        global $inboundingModel;
        $data = $inboundingModel->getItamcode();
        echo json_encode($data);exit;
    }
    public function label($value=''){
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform2data($id);
        renderTemplateClean('views/inbounding/label.php', $data, 'label');
    }
    public function getform1() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform1data($id);
        renderTemplateClean('views/inbounding/form1.php', $data, 'form1 inbounding');
    }
    public function getdesktopform() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform2data($id);
        renderTemplateClean('views/inbounding/desktopform.php', $data, 'desktopform inbounding');
    }
    function getCategoryList() {
        $url = 'https://www.exoticindia.com/vendor-api/product/categorylist';
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json'
        ];
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Disable if SSL issue occurs
        ]);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("cURL Error: " . curl_error($ch));
            curl_close($ch);
            return false;
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode != 200) {
            error_log("API HTTP Status: " . $httpCode . " - Response: " . $response);
            return false;
        }
        return json_decode($response, true);
    }
    public function getform2() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        if (isset($id) && $id != 0) {
            $data = $inboundingModel->getform1data($id);
            renderTemplateClean('views/inbounding/form2.php', $data, 'form2 inbounding');
        }else{
            header("location: " . base_url('?page=inbounding&action=list'));
        }
    }
    public function getform3() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        if (isset($id) && $id != 0) {
            $data = $inboundingModel->getform2data($id);
            renderTemplateClean('views/inbounding/form3.php', $data, 'form3 inbounding');
        }else{
            header("location: " . base_url('?page=inbounding&action=list'));
        }
    }
    public function saveform1() {
         global $inboundingModel;
        $category = $_POST['category'] ?? '';
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
            echo "Photo upload error.";
            exit;
        }
        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileTmp  = $_FILES['photo']['tmp_name'];
        $fileName = $_FILES['photo']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($fileExt, $allowed)) {
            echo "Only JPG, PNG, WEBP allowed.";
            exit;
        }
        $newFile = "IMG_" . time() . "." . $fileExt;
        $dest    = $uploadDir . $newFile;
        if (move_uploaded_file($fileTmp, $dest)) {
            $photoPath = "uploads/products/" . $newFile;
            $saveData = [
                'category' => $category,
                'photo'    => $photoPath
            ];
            $insertId = $inboundingModel->saveform1($saveData);
            if ($insertId) {
                header("location: " . base_url('?page=inbounding&action=form2&id='.$insertId));
                exit;
            } else {
                echo "Database error.";
            }
        } else {
            echo "Upload failed.";
        }        
    }
    public function updateform1() {
        global $inboundingModel;

        $id       = $_GET['id'] ?? 0;
        $category = $_POST['category'] ?? '';

        // Get old record
        $oldData = $inboundingModel->getform1data($id);;

        if (!$oldData) {
            echo "Record not found.";
            exit;
        }

        // Keep old image if new one not uploaded
        $photoPath = $oldData['form1']['product_photo'];

        // If new photo uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

            $uploadDir = __DIR__ . '/../uploads/products/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmp  = $_FILES['photo']['tmp_name'];
            $fileName = $_FILES['photo']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];

            if (!in_array($fileExt, $allowed)) {
                echo "Only JPG, PNG, WEBP allowed.";
                exit;
            }

            // Rename
            $newFile = "IMG_" . time() . "." . $fileExt;
            $dest    = $uploadDir . $newFile;

            if (move_uploaded_file($fileTmp, $dest)) {
                $photoPath = "uploads/products/" . $newFile;
            }
        }

        // Save data
        $data = [
            'id'       => $id,
            'category' => $category,
            'photo'    => $photoPath
        ];

        $updated = $inboundingModel->updateform1($data);

        if ($updated) {
            header("location: " . base_url('?page=inbounding&action=form2&id='.$id));
            exit;
        } else {
            echo "Update failed.";
        }
    }
    public function updateform2() {
        global $inboundingModel;

        $id       = $_GET['id'] ?? 0;
        $vendor_id = $_POST['vendor_id'] ?? '';
        $invoice_no = $_POST['invoice_no'] ?? '';

        // Get old record
        $oldData = $inboundingModel->getform1data($id);;

        if (!$oldData) {
            echo "Record not found.";
            exit;
        }

        // Keep old image if new one not uploaded
        $invoicePath = $oldData['form1']['invoice_image'];

        // If new photo uploaded
        if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] === 0) {

            $uploadDir = __DIR__ . '/../uploads/invoice/';

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmp  = $_FILES['invoice']['tmp_name'];
            $fileName = $_FILES['invoice']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];

            if (!in_array($fileExt, $allowed)) {
                echo "Only JPG, PNG, WEBP allowed.";
                exit;
            }

            // Rename
            $newFile = "IMG_" . time() . "." . $fileExt;
            $dest    = $uploadDir . $newFile;

            if (move_uploaded_file($fileTmp, $dest)) {
                $invoicePath = "uploads/invoice/" . $newFile;
            }
        }
        // Save data
        $data = [
            'id'       => $id,
            'vendor_id' => $vendor_id,
            'invoice'    => $invoicePath,
            'invoice_no' => $invoice_no
        ];

        $updated = $inboundingModel->updateform2($data);

        if ($updated) {
            header("location: " . base_url('?page=inbounding&action=form3&id='.$id));
            exit;
        } else {
            echo "Update failed.";
        }
    }

    // PAGE: Display the photos form
    public function i_photos() {
        global $inboundingModel;
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // Fetch data
        $data['images'] = $inboundingModel->getitem_imgs($id);
        $data['record_id'] = $id;

        // Render View
        renderTemplateClean('views/inbounding/i_photos.php', $data, 'desktopform inbounding');
    }

    // ACTION: Save (Uploads & Deletions)
    public function itmimgsave() {
        global $inboundingModel;
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Handle Deletions (User removed existing images)
            if (!empty($_POST['delete_ids'])) {
                foreach ($_POST['delete_ids'] as $del_id) {
                    $inboundingModel->delete_image(intval($del_id));
                }
            }

            // 2. Handle New File Uploads
            if (!empty($_FILES['new_photos']['name'][0])) {
                
                // Define Upload Directory
                $uploadDir = __DIR__ . '/../uploads/itm_img/';
                
                // Auto-create directory if missing
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                foreach ($_FILES['new_photos']['name'] as $key => $name) {
                    if ($_FILES['new_photos']['error'][$key] === 0) {
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        
                        // Generate Unique Filename: img_ID_TIMESTAMP_RANDOM.ext
                        $newName = 'img_' . $id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                        
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            // Save to DB
                            $inboundingModel->add_image($id, $newName);
                        }
                    }
                }
            }

            // Redirect with success message
            header("Location: " . base_url("?page=inbounding&action=list"));
            exit;
        }
    }
    public function updatedesktopform() {
        global $inboundingModel;

        // 1. Basic Setup & File Upload (Keep your existing code here)
        $id = $_GET['id'] ?? 0;
        $oldData = $inboundingModel->getform1data($id);

        if (!$oldData) {
            echo "Record not found.";
            exit;
        }

        // --- (Your File Upload Code Here) --- 
        $invoicePath = $oldData['form1']['invoice_image'];
        if (isset($_FILES['invoice_image']) && $_FILES['invoice_image']['error'] === 0) {
            $uploadDir = __DIR__ . '/../uploads/invoice/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            
            $fileTmp  = $_FILES['invoice_image']['tmp_name'];
            $fileName = $_FILES['invoice_image']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // ... (Your validation) ...
            
            $newFile = "IMG_" . time() . "." . $fileExt;
            if (move_uploaded_file($fileTmp, $uploadDir . $newFile)) {
                $invoicePath = "uploads/invoice/" . $newFile;
            }
        }

        // 2. Capture Inputs
        $is_variant = $_POST['is_variant'] ?? '';
        $item_code  = $_POST['Item_code'] ?? '';
        $old_is_variant = $oldData['form2']['is_variant'] ?? '';
        // ---------------------------------------------------------
        //  AUTO-GENERATE LOGIC (Updated to use Model)
        // ---------------------------------------------------------
        if ($is_variant === 'N' && (empty($item_code) || $old_is_variant === 'Y')) {

            // A. Get IDs
            $group_id    = $_POST['group_name'] ?? 0;
            $category_id = $_POST['category_code'] ?? 0;

            // B. Get Names & Count
            $group_name_str = $inboundingModel->getCategoryName($group_id);
            $cat_name_str   = $inboundingModel->getCategoryName($category_id);
            $next_count     = $inboundingModel->getNextProductCount();

            // C. Generate Characters
            $char1  = !empty($group_name_str) ? strtoupper(substr($group_name_str, 0, 1)) : 'X';
            $char23 = !empty($cat_name_str)   ? strtoupper(substr($cat_name_str, 0, 2)) : 'XX';
            $increment_str = str_pad($next_count, 3, '0', STR_PAD_LEFT);

            // D. Assign the NEW generated code
            $item_code = $char1 . $char23 . $increment_str;
        }
        // ---------------------------------------------------------

        // 3. Build Data Array
        $data = [
            'id'                => $id,
            'invoice_image'     => $invoicePath,
            'is_variant'        => $is_variant,
            'Item_code'         => $item_code, // Now contains the generated code
            'stock_added_date'  => $_POST['stock_added_date'] ?? '',
            'received_by_user_id' => $_POST['received_by_user_id'] ?? '',
            'updated_by_user_id' => $_POST['updated_by_user_id'] ?? '',
            'invoice_no'        => $_POST['invoice_no'] ?? '',
            'material_code'     => $_POST['material_code'] ?? '',
            'product_title'     => $_POST['product_title'] ?? '',
            'key_words'         => $_POST['key_words'] ?? '',
            'vendor_code'       => $_POST['vendor_code'] ?? '',
            'inr_pricing'       => $_POST['inr_pricing'] ?? '',
            'amazon_price'      => $_POST['amazon_price'] ?? '',
            'usd_price'         => $_POST['usd_price'] ?? '',
            'hsn_code'          => $_POST['hsn_code'] ?? '',
            'gst_rate'          => $_POST['gst_rate'] ?? '',
            'height'            => $_POST['height'] ?? '',
            'width'             => $_POST['width'] ?? '',
            'depth'             => $_POST['depth'] ?? '',
            'weight'            => $_POST['weight'] ?? '',
            'size'              => $_POST['size'] ?? '',
            'color'             => $_POST['color'] ?? '',
            'quantity_received' => $_POST['quantity_received'] ?? '',
            'permanently_available' => $_POST['permanently_available'] ?? '',
            'ware_house_code'   => $_POST['ware_house_code'] ?? '',
            'store_location'    => $_POST['store_location'] ?? '',
            'local_stock'       => $_POST['local_stock'] ?? '',
            'lead_time_days'    => $_POST['lead_time_days'] ?? '',
            'us_block'          => $_POST['us_block'] ?? '',
            'group_name'        => $_POST['group_name'] ?? '',
            'category_code'     => $_POST['category_code'] ?? '',
            'sub_category_code' => $_POST['sub_category_code'] ?? '',
            'sub_sub_category_code' => $_POST['sub_sub_category_code'] ?? '',
            'dimention_unit'    => $_POST['dimention_unit'] ?? '',
            'weight_unit'       => $_POST['weight_unit'] ?? '',
        ];

        // 4. Save
        $result = $inboundingModel->updatedesktopform($id, $data);

        if ($result['success']) { // Adjusted based on your model returning an array
            header("location: " . base_url('?page=inbounding&action=list'));
            exit;
        } else {
            echo "Update failed: " . $result['message'];
        }
    }
    public function updateform3()
    {
        global $inboundingModel;

        // Read record ID
        $record_id = $_POST['record_id'] ?? '';
        if (empty($record_id)) {
            echo "Record ID missing.";
            exit;
        }

        // Read form fields
        $gate_entry_date_time = $_POST['gate_entry_date_time'] ?? '';
        $material_code        = $_POST['material_code'] ?? '';
        $height               = $_POST['height'] ?? '';
        $width                = $_POST['width'] ?? '';
        $depth                = $_POST['depth'] ?? '';
        $weight               = $_POST['weight'] ?? '';
        $color                = $_POST['color'] ?? '';
        $quantity_received    = $_POST['quantity_received'] ?? '';
        $item_code            = $_POST['Item_code'] ?? '';
        $received_by_user_id  = $_POST['received_by_user_id'] ?? '';
        $weight_unit  = $_POST['weight_unit'] ?? '';
        $dimention_unit  = $_POST['dimention_unit'] ?? '';

        // Prepare data array for update
        $updateData = [
            'gate_entry_date_time' => $gate_entry_date_time,
            'material_code'        => $material_code,
            'height'               => $height,
            'width'                => $width,
            'depth'                => $depth,
            'weight'               => $weight,
            'color'                => $color,
            'quantity_received'    => $quantity_received,
            'item_code'            => $item_code,
            'received_by_user_id'  => $received_by_user_id,
            'weight_unit'          => $weight_unit,
            'dimention_unit'       => $dimention_unit,
        ];

        // Call model update
        $updated = $inboundingModel->updateForm3($record_id, $updateData);

        if ($updated['success']) {
            // redirect to label
            header("Location: " . base_url("?page=inbounding&action=label&id=" . $record_id));
            exit;
        } else {
            echo "Update failed: " . $updated['message'];
            exit;
        }
    }
    public function saveform2() {
         global $inboundingModel;
        $vendor_id = $_POST['vendor_id'] ?? '';
        $record_id = $_POST['record_id'] ?? '';
        $invoice_no = $_POST['invoice_no'] ?? '';
        if (!isset($_FILES['invoice']) || $_FILES['invoice']['error'] !== 0) {
            echo "invoice upload error.";
            exit;
        }
        $uploadDir = __DIR__ . '/../uploads/invoice/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $fileTmp  = $_FILES['invoice']['tmp_name'];
        $fileName = $_FILES['invoice']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (!in_array($fileExt, $allowed)) {
            echo "Only JPG, PNG, WEBP allowed.";
            exit;
        }
        $newFile = "IMG_" . time() . "." . $fileExt;
        $dest    = $uploadDir . $newFile;
        if (move_uploaded_file($fileTmp, $dest)) {
            $invoicePath = "uploads/invoice/" . $newFile;
            
            $temp_code = $this->generateTeamCode();
            $saveData = [
                'vendor_id' => $vendor_id,
                'invoice'    => $invoicePath,
                'invoice_no' => $invoice_no,
                'temp_code' => $temp_code
            ];
            $insertId = $inboundingModel->saveform2($record_id,$saveData);
            if ($insertId) {
                header("location: " . base_url('?page=inbounding&action=form3&id='.$record_id));
                exit;
            } else {
                echo "Database error.";
            }
        } else {
            echo "Upload failed.";
        }        
    }
    public function saveform3(){
        global $inboundingModel;
        $record_id = $_POST['record_id'] ?? '';
        $gate_entry_date_time = date("Y-m-d H:i:s", strtotime($_POST['gate_entry_date_time'] ?? 'now'));
        $saveData = [
            'gate_entry_date_time' => $gate_entry_date_time ?? '',
            'material_code'        => $_POST['material_code'] ?? '',
            'height'               => $_POST['height'] ?? '',
            'width'                => $_POST['width'] ?? '',
            'depth'                => $_POST['depth'] ?? '',
            'weight'               => $_POST['weight'] ?? '',
            'color'                => $_POST['color'] ?? '',
            'Quantity'             => $_POST['quantity_received'] ?? '',
            'Item_code'            => $_POST['Item_code'] ?? '',
            'received_by_user_id'            => $_POST['received_by_user_id'] ?? '',
        ];
        $insertId = $inboundingModel->saveform3($record_id,$saveData);
        if ($insertId) {
            header("location: " . base_url('?page=inbounding&action=print&id='.$record_id));
            exit;
        } else {
            echo "Database error.";
        }
    }
    public function editRecord() {
        global $inboundingModel;
        $inboundingId = isset($_GET['id']) ? $_GET['id'] : 0;
        if (!$inboundingId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
            exit;
        }
        $data = $inboundingModel->getRecord($inboundingId);
        $data = [
            'inbounding' => $data["inbounding"],
        ];
        renderTemplateClean('views/inbounding/edit.php', $data, 'Edit inbounding');
    }
    public function updateRecord() {
        global $inboundingModel;
        $Id = isset($_POST['id']) ? $_POST['id'] : 0;
        if (!$Id) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $data = $_POST;
        $result = $inboundingModel->updateRecord($Id, $data);
        $_SESSION["role_message"] = $result['message'];
        header("location: " . base_url('?page=inbounding&action=list'));
        exit;
    }
    function generateTeamCode()
    {
        global $inboundingModel;
        do {
            // Generate random prefix
            $prefix = '';
            for ($i = 0; $i < 3; $i++) {
                $prefix .= chr(rand(65, 90));
            }

            // Generate random 6 digits
            $number = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);

            $code = $prefix . $number;

        } while ($inboundingModel->isCodeExists($code));

        return $code;
    }




}
?>