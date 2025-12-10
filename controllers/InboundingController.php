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
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Default to 10

        // Update this array to match your HTML dropdown values (10, 25, 50, 100)
        $valid_limits = [10, 25, 50, 100]; 

        if (in_array($limit, $valid_limits)) {
            $limit = $limit;
        } else {
            $limit = 10; // Fallback to 10 if invalid value passed
        }

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
    // In your Inbounding Controller

    public function exportSelected() {
        // 1. Clean buffer to prevent empty lines at start of CSV
        if (ob_get_level()) ob_end_clean();

        // 2. Get IDs
        $ids_string = $_GET['ids'] ?? '';
        
        if (empty($ids_string)) {
            echo "<script>alert('No items selected!'); window.history.back();</script>";
            exit;
        }

        // 3. Sanitization
        $ids_array = explode(',', $ids_string);
        $sanitized_ids = array_map('intval', $ids_array);
        
        // Safety check
        if(empty($sanitized_ids)) { exit; }
        
        $ids_sql = implode(',', $sanitized_ids);

        // 4. Fetch Data (Direct Query)
        $conn = Database::getConnection(); 
        $sql = "SELECT * FROM vp_inbound WHERE id IN ($ids_sql)";
        $result = $conn->query($sql);

        // 5. Generate CSV
        if ($result && $result->num_rows > 0) {
            $filename = "inbound_export_" . date('Y-m-d_H-i') . ".csv";
            
            // Headers to force download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');

            // Column Headers
            fputcsv($output, ['ID', 'Item Code', 'Temp Code', 'Title', 'Keywords', 'Quantity', 'Vendor Code', 'Received Date']);

            // Data Rows
            while ($row = $result->fetch_assoc()) {
                fputcsv($output, [
                    $row['id'],
                    $row['Item_code'],
                    $row['temp_code'],
                    $row['product_title'],
                    $row['key_words'],
                    $row['quantity_received'],
                    $row['vendor_code'],
                    $row['gate_entry_date_time']
                ]);
            }
            fclose($output);
            exit;
        } else {
            echo "No records found or SQL Error: " . $conn->error;
            exit;
        }
    }
    public function getItamcode(){
        global $inboundingModel;
        $data = $inboundingModel->getItamcode();
        echo json_encode($data);exit;
    }
    public function label($value=''){
        is_login();
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform2data($id);
        renderTemplateClean('views/inbounding/label.php', $data, 'label');
    }
    public function getform1() {
        is_login();
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform1data($id);
        renderTemplateClean('views/inbounding/form1.php', $data, 'form1 inbounding');
    }
    public function getdesktopform() {
        is_login();
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
        is_login();
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
        is_login();
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

        // 1. Check if file uploaded
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== 0) {
            echo "Photo upload error.";
            exit;
        }

        // 2. Validate Size (50KB Limit)
        if ($_FILES['photo']['size'] > 51200) { // 50 * 1024
            echo "File is too large. Maximum allowed size is 50KB.";
            exit;
        }

        $uploadDir = __DIR__ . '/../uploads/products/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 3. Process & Resize Image
        // We generate a unique name, resize it, and save it.
        $fileName = $_FILES['photo']['name'];
        $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','webp'];

        if (!in_array($fileExt, $allowed)) {
            echo "Only JPG, PNG, WEBP allowed.";
            exit;
        }

        // Define new filename (Saving as JPG is usually best for size optimization)
        $newFile = "IMG_" . time() . ".jpg";
        $dest    = $uploadDir . $newFile;

        // CALL THE RESIZE FUNCTION
        $processed = $this->processAndResizeImage($_FILES['photo']['tmp_name'], $dest, 500, 500);

        if ($processed) {
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
            echo "Image processing failed.";
        }        
    }

    public function updateform1() {
        global $inboundingModel;

        $id       = $_GET['id'] ?? 0;
        $category = $_POST['category'] ?? '';

        $oldData = $inboundingModel->getform1data($id);
        if (!$oldData) {
            echo "Record not found.";
            exit;
        }

        $photoPath = $oldData['form1']['product_photo'];

        // If new photo uploaded
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {
            
            // 1. Validate Size
            if ($_FILES['photo']['size'] > 51200) {
                echo "File is too large. Maximum allowed size is 50KB.";
                exit;
            }

            $uploadDir = __DIR__ . '/../uploads/products/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

            $fileName = $_FILES['photo']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed  = ['jpg','jpeg','png','webp'];

            if (!in_array($fileExt, $allowed)) {
                echo "Only JPG, PNG, WEBP allowed.";
                exit;
            }

            $newFile = "IMG_" . time() . ".jpg";
            $dest    = $uploadDir . $newFile;

            // 2. Process & Resize
            $processed = $this->processAndResizeImage($_FILES['photo']['tmp_name'], $dest, 500, 500);

            if ($processed) {
                $photoPath = "uploads/products/" . $newFile;
                
                // Optional: Unlink (delete) old file here if needed
                // if(file_exists(__DIR__ . '/../' . $oldData['form1']['product_photo'])) { ... }
            }
        }

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

    /**
     * Helper function to resize image to fixed width/height
     * and compress it to keep file size low.
     */
    private function processAndResizeImage($sourcePath, $destPath, $fixedW, $fixedH) {
        // Get original dimensions
        list($width, $height, $type) = getimagesize($sourcePath);

        // Load image based on type
        switch ($type) {
            case IMAGETYPE_JPEG: $src = imagecreatefromjpeg($sourcePath); break;
            case IMAGETYPE_PNG:  $src = imagecreatefrompng($sourcePath); break;
            case IMAGETYPE_WEBP: $src = imagecreatefromwebp($sourcePath); break;
            default: return false; 
        }

        if (!$src) return false;

        // Create new blank image with fixed dimensions
        $dst = imagecreatetruecolor($fixedW, $fixedH);

        // Maintain transparency for PNG/WEBP (though we convert to JPG later, good practice)
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);

        // Resize (Stretch to fit fixed size - as requested "set in fix size")
        // If you want to maintain aspect ratio, logic needs to change slightly.
        // This strictly forces 500x500.
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $fixedW, $fixedH, $width, $height);

        // Save as JPEG with 75% Quality (Good balance for <50KB)
        // We force JPG because it handles compression better than PNG for photos
        $result = imagejpeg($dst, $destPath, 75);

        imagedestroy($src);
        imagedestroy($dst);

        return $result;
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

    // In Inbounding Controller

    public function i_photos() {
        is_login();
        global $inboundingModel;
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // 1. Get Images
        $data['images'] = $inboundingModel->getitem_imgs($id);
        
        // 2. Get Item Details (For the top header info)
        $data['item'] = $inboundingModel->getItemDetails($id);
        $data['record_id'] = $id;

        renderTemplateClean('views/inbounding/i_photos.php', $data, 'Item Photos');
    }

    // ACTION: Save (Uploads, Deletions, AND Updates)
    public function itmimgsave() {
        global $inboundingModel;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Handle Deletions
            if (!empty($_POST['delete_ids'])) {
                foreach ($_POST['delete_ids'] as $del_id) {
                    $inboundingModel->delete_image(intval($del_id));
                }
            }

            // 2. Handle Metadata Updates (Caption & Order)
            if (!empty($_POST['captions']) && is_array($_POST['captions'])) {
                foreach ($_POST['captions'] as $img_id => $caption) {
                    $order = $_POST['orders'][$img_id] ?? 0;
                    $inboundingModel->update_image_meta($img_id, $caption, $order);
                }
            }

            // 3. Handle New File Uploads
            if (!empty($_FILES['new_photos']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/itm_img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                foreach ($_FILES['new_photos']['name'] as $key => $name) {
                    if ($_FILES['new_photos']['error'][$key] === 0) {
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $newName = 'img_' . $id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                        
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            $inboundingModel->add_image($id, $newName);
                        }
                    }
                }
            }

            header("Location: " . base_url("?page=inbounding&action=i_photos&id=$id"));
            exit;
        }
    }

    // ACTION: Download All Photos as ZIP
    public function download_photos() {
        global $inboundingModel;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // 1. Get images from DB
        $images = $inboundingModel->getitem_imgs($id);
        
        if(empty($images)) {
            echo "<script>alert('No images found for this item.'); history.back();</script>";
            exit;
        }

        // 2. Setup Zip
        $zip = new ZipArchive();
        $zipName = "Item_{$id}_Photos_" . date('Ymd_His') . ".zip";
        $tmp_file = sys_get_temp_dir() . '/' . $zipName;

        if ($zip->open($tmp_file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            exit("Error: Cannot create zip file at $tmp_file");
        }

        $basePath = __DIR__ . '/../uploads/itm_img/';
        $filesAdded = 0;

        foreach ($images as $img) {
            $filePath = $basePath . $img['file_name'];
            if (file_exists($filePath)) {
                // Add file to zip
                $zip->addFile($filePath, $img['file_name']);
                $filesAdded++;
            }
        }
        
        // Close the zip
        $zip->close();

        // 3. Check if files were actually added
        if ($filesAdded === 0) {
            echo "<script>alert('Image files missing from server.'); history.back();</script>";
            exit;
        }

        // 4. CRITICAL FIX: Clean the Output Buffer
        // This removes any accidental spaces/newlines echoed before this point
        if (file_exists($tmp_file)) {
            // Clear all output buffers
            while (ob_get_level()) {
                ob_end_clean();
            }

            // Force Download Headers
            header('Content-Description: File Transfer');
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="'.basename($zipName).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($tmp_file));
            
            // Send file
            readfile($tmp_file);
            
            // Cleanup
            unlink($tmp_file);
            exit;
        } else {
            exit("Error: Zip file was not created.");
        }
    }
    public function updatedesktopform() {
        global $inboundingModel;

        // 1. Setup & Checks
        $id = $_GET['id'] ?? 0;
        $oldData = $inboundingModel->getform1data($id);

        if (!$oldData) { echo "Record not found."; exit; }

        // --- File Upload Logic (Standard) ---
        $invoicePath = $oldData['form1']['invoice_image'] ?? '';
        if (isset($_FILES['invoice_image']) && $_FILES['invoice_image']['error'] === 0) {
            $uploadDir = __DIR__ . '/../uploads/invoice/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $fileExt = strtolower(pathinfo($_FILES['invoice_image']['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                $newFile = "IMG_" . time() . "." . $fileExt;
                if (move_uploaded_file($_FILES['invoice_image']['tmp_name'], $uploadDir . $newFile)) {
                    $invoicePath = "uploads/invoice/" . $newFile;
                }
            }
        }

        // 2. Capture Inputs
        $is_variant = $_POST['is_variant'] ?? '';
        $item_code  = $_POST['Item_code'] ?? '';
        $old_is_variant = $oldData['form2']['is_variant'] ?? '';

        // --- Auto-Generate Item Code Logic ---
        if ($is_variant === 'N' && (empty($item_code) || $old_is_variant === 'Y')) {

            // A. Get Group Name (This is now the Direct Value from POST)
            $group_name_str = $_POST['group_name'] ?? ''; 
            
            // B. Get Category Name (Still need to look this up via ID)
            $category_id = $_POST['category_code'] ?? 0;
            $cat_name_str = $inboundingModel->getCategoryName($category_id);
            
            $next_count = $inboundingModel->getNextProductCount();

            // C. Generate Chars
            $char1  = !empty($group_name_str) ? strtoupper(substr($group_name_str, 0, 1)) : 'X';
            $char23 = !empty($cat_name_str)   ? strtoupper(substr($cat_name_str, 0, 2)) : 'XX';
            $increment_str = str_pad($next_count, 3, '0', STR_PAD_LEFT);

            $item_code = $char1 . $char23 . $increment_str;
        }

        // --- Handle Array Inputs (Multi-select) ---
        $sub_cat_input = $_POST['sub_category_code'] ?? '';
        $sub_cat_val   = is_array($sub_cat_input) ? implode(',', $sub_cat_input) : $sub_cat_input;

        $sub_sub_input = $_POST['sub_sub_category_code'] ?? '';
        $sub_sub_val   = is_array($sub_sub_input) ? implode(',', $sub_sub_input) : $sub_sub_input;

        // 3. Data Array
        $data = [
            'invoice_image'       => $invoicePath,
            'is_variant'          => $is_variant,
            'Item_code'           => $item_code,
            'group_name'          => $_POST['group_name'] ?? '', // Stores "Category Index" string
            'category_code'       => $_POST['category_code'] ?? '',
            'sub_category_code'     => $sub_cat_val, // Stores "1,2,3" string
            'sub_sub_category_code' => $sub_sub_val, // Stores "5,8,9" string
            
            // ... (Rest of your fields) ...
            'stock_added_date'    => $_POST['stock_added_date'] ?? '',
            'received_by_user_id' => $_POST['received_by_user_id'] ?? '',
            'updated_by_user_id'  => $_POST['updated_by_user_id'] ?? '',
            'invoice_no'          => $_POST['invoice_no'] ?? '',
            'material_code'       => $_POST['material_code'] ?? '',
            'product_title'       => $_POST['product_title'] ?? '',
            'key_words'           => $_POST['key_words'] ?? '',
            'vendor_code'         => $_POST['vendor_code'] ?? '',
            'inr_pricing'         => $_POST['inr_pricing'] ?? '',
            'amazon_price'        => $_POST['amazon_price'] ?? '',
            'usd_price'           => $_POST['usd_price'] ?? '',
            'hsn_code'            => $_POST['hsn_code'] ?? '',
            'gst_rate'            => $_POST['gst_rate'] ?? '',
            'height'              => $_POST['height'] ?? '',
            'width'               => $_POST['width'] ?? '',
            'depth'               => $_POST['depth'] ?? '',
            'weight'              => $_POST['weight'] ?? '',
            'size'                => $_POST['size'] ?? '',
            'color'               => $_POST['color'] ?? '',
            'quantity_received'   => $_POST['quantity_received'] ?? '',
            'permanently_available' => $_POST['permanently_available'] ?? '',
            'ware_house_code'     => $_POST['ware_house_code'] ?? '',
            'store_location'      => $_POST['store_location'] ?? '',
            'local_stock'         => $_POST['local_stock'] ?? '',
            'lead_time_days'      => $_POST['lead_time_days'] ?? '',
            'us_block'            => $_POST['us_block'] ?? '',
            'dimention_unit'      => $_POST['dimention_unit'] ?? '',
            'weight_unit'         => $_POST['weight_unit'] ?? '',
        ];

        // 4. Save
        $result = $inboundingModel->updatedesktopform($id, $data);

        if ($result['success']) {
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
        $received_by_user_id  = $_POST['received_by_user_id'] ?? '';

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
            'received_by_user_id'  => $received_by_user_id,
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