 <?php
require_once 'models/inbounding/Inbounding.php';

$inboundingModel = new Inbounding($conn);

global $root_path;
global $domain;
class InboundingController {

    public function index() {
        is_login();
        global $inboundingModel;
        
        // 1. Capture Filter Inputs
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        
        $filters = [
            'vendor_code'         => $_GET['vendor_code'] ?? '',
            'received_by_user_id' => $_GET['agent_id'] ?? '',
            'group_name'          => $_GET['group_name'] ?? '',
            'status_step'         => $_GET['status_step'] ?? ''
        ];

        // 2. Pagination Logic
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $valid_limits = [10, 25, 50, 100]; 
        if (!in_array($limit, $valid_limits)) $limit = 10;

        // 3. Fetch Main Data
        $pt_data = $inboundingModel->getAll($page_no, $limit, $search, $filters); 
        
        // 4. Fetch Dynamic Dropdown Data (The function we just updated)
        $dropdowns = $inboundingModel->getFilterDropdowns();

        $data = [
            'inbounding_data' => $pt_data["inbounding"],
            'page_no'         => $page_no,
            'total_pages'     => $pt_data["totalPages"],
            'search'          => $search,
            'totalPages'      => $pt_data["totalPages"],
            'currentPage'     => $pt_data["currentPage"],
            'limit'           => $limit,
            'totalRecords'    => $pt_data["totalRecords"],
            
            // Pass the filter data to the View
            'filters'         => $filters,
            'vendor_list'     => $dropdowns['vendors'],
            'user_list'       => $dropdowns['users'],
            'group_list'      => $dropdowns['groups']
        ];
        
        renderTemplate('views/inbounding/index.php', $data, 'Manage Inbounding');
    }
    // In your Inbounding Controller

    public function exportSelected() {
        global $inboundingModel; // Use the global model instance

        // 1. Clean buffer
        if (ob_get_level()) ob_end_clean();

        // 2. Get IDs
        $ids_string = $_GET['ids'] ?? '';
        
        if (empty($ids_string)) {
            echo "<script>alert('No items selected!'); window.history.back();</script>";
            exit;
        }

        // 3. Sanitization
        $ids_array = explode(',', $ids_string);
        
        // 4. Fetch Data using the Model (This handles the JOINs for names)
        $result = $inboundingModel->getExportData($ids_array);

        // 5. Generate CSV
        if ($result && $result->num_rows > 0) {
            $filename = "inbound_export_" . date('Y-m-d_H-i') . ".csv";
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');

            // A. Define Excel Column Headers
            $excel_headers = [
                'itemcode', 'groupname', 'category', 'itemtype', 'title', 
                'image', 'redirect', 'snippet_description', 'long_description', 'long_description_india', 
                'important_info', 'description_icons', 'bundled_items', 'keywords', 'usblock', 
                'indiablock', 'numsold', 'lastsold', 'qty_step', 'related_items', 
                'search_term', 'search_category', 'hscode', 'vendor', 'cp', 
                'isbn', 'author', 'publisher', 'language', 'pages', 
                'cover_type', 'edition', 'publication_date', 'description', 'weight_to_show', 
                'variation_name_color', 'variation_name_size', 'india_net_qty', 'author_description', 'publisher_description', 
                'publisher_field_name', 'material', 'pweight_to_show', 'optionals', 'pvariation_name_color', 
                'pvariation_name_size', 'amazon_dimensionunit', 'amazon_diameter', 'amazon_height', 'amazon_width', 
                'amazon_length', 'amazon_metalweight', 'amazon_metalweightunit', 'amazon_metaltype', 'amazon_metalstamp', 
                'amazon_settingtype', 'amazon_necklace_clasptype', 'amazon_necklace_chaintype', 'amazon_earrings_backfinding', 'amazon_gemtypes', 
                'amazon_gemtypes_shape', 'amazon_gemtypes_totalgemweight', 'amazon_gemtypes_numberofstones', 'amazon_gemtypes_stonewidth', 'amazon_gemtypes_stoneweight', 
                'amazon_language'
            ];

            // Write Headers
            fputcsv($output, $excel_headers);

            // B. Data Rows (Mapping DB fields to Headers)
            while ($row = $result->fetch_assoc()) {
                
                // Logic: Y = 1, N = 0
                $us_block_val = ($row['us_block'] === 'Y') ? '1' : '0';
                $india_block_val = ($row['india_block'] === 'Y') ? '1' : '0';

                // Logic: Combined Weight + Unit
                $weight_display = $row['weight'] . ' ' . ($row['weight_unit'] ?? '');

                $csv_row = [
                    $row['Item_code'] ?? '',                
                    $row['group_real_name'] ?? '',          // Fetched via Model JOIN
                    $row['category_code'] ?? '',       // Fetched via Model JOIN
                    'product',                              // Fixed value as per your code
                    $row['product_title'] ?? '',            
                    
                    $row['product_photo'] ?? '',            
                    '',                                     
                    $row['snippet_description'] ?? '',      
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    $row['description_icons'] ?? '',        
                    '',                                     
                    $row['key_words'] ?? '',                
                    $us_block_val,                          
                    
                    $india_block_val,                       
                    '0',                                     
                    '0',                                     
                    '1',                                     
                    '',                                     
                    
                    '',                                     
                    '',                                     
                    $row['hsn_code'] ?? '',                 
                    $row['vendor_real_name'] ?? '',         // Fetched via Model JOIN
                    $row['cp'] ?? '',                       
                    
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    $weight_display, 
                    
                    $row['color'] ?? '',                    
                    $row['size'] ?? '',                     
                    '1',                                     
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    $row['material_real_name'] ?? '',       // Fetched via Model JOIN
                    '',                                     
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    $row['dimention_unit'] ?? '',           
                    '',                                     
                    $row['height'] ?? '',                   
                    $row['width'] ?? '',                    
                    
                    $row['depth'] ?? '',                    
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    '',                                     
                    
                    ''                                      
                ];

                fputcsv($output, $csv_row);
            }
            fclose($output);
            exit;
        } else {
            echo "No records found.";
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
        $data = $inboundingModel->getlabeldata($id);
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
        $data['form2']['icon_data'] = $this->geticonList();
        $data['images'] = $inboundingModel->getitem_imgs($id);
        // echo "<pre>";print_r($data['icon_data']);exit;
        renderTemplate('views/inbounding/desktopform.php', $data, 'desktopform inbounding');
    }
    function geticonList() {
        $url = 'https://www.exoticindia.com/vendor-api/product/descriptionicons';
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
                $logData = [
                        'userid_log' => $_POST['userid_log'] ?? '',
                        'i_id' => $insertId,
                        'stat' => 'inbound'
                    ];
                $log_res =  $inboundingModel->stat_logs($logData);
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
        $logData = [
                'userid_log' => $_POST['userid_log'] ?? '',
                'i_id' => $id,
                'stat' => 'inbound'
            ];
        $log_res =  $inboundingModel->stat_logs($logData);
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

        renderTemplate('views/inbounding/i_photos.php', $data, 'Item Photos');
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
            $logData = [
                    'userid_log' => $_POST['userid_log'] ?? '',
                    'i_id' => $id,
                    'stat' => 'Editing'
                ];
            $log_res =  $inboundingModel->stat_logs($logData);
            header("Location: " . base_url("?page=inbounding&action=list"));
            exit;
        }
    }

    // ACTION: Download All Photos as ZIP
    public function download_photos() {
        global $inboundingModel;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // 1. Get images from DB
        $images = $inboundingModel->get_raw_item_imgs($id);
        
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

        $basePath = __DIR__ . '/../uploads/itm_raw_img/';
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
    public function i_raw_photos() {
        is_login();
        global $inboundingModel;
        
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // 1. Get Raw Images
        $data['images'] = $inboundingModel->get_raw_imgs($id);
        
        // 2. Get Item Details (Reusing your existing function for the header)
        $data['item'] = $inboundingModel->getItemDetails($id);
        $data['record_id'] = $id;

        // Load the new view file
        renderTemplate('views/inbounding/i_raw_photos.php', $data, 'Raw Photos');
    }

    public function itmrawimgsave() {
        global $inboundingModel;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            
            // 1. Handle Deletions
            if (!empty($_POST['delete_ids'])) {
                foreach ($_POST['delete_ids'] as $del_id) {
                    $inboundingModel->delete_raw_image(intval($del_id));
                }
            }

            // 2. Handle New File Uploads
            if (!empty($_FILES['new_photos']['name'][0])) {
                // NEW FOLDER PATH
                $uploadDir = __DIR__ . '/../uploads/itm_raw_img/';
                
                // Create folder if it doesn't exist
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                foreach ($_FILES['new_photos']['name'] as $key => $name) {
                    if ($_FILES['new_photos']['error'][$key] === 0) {
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        // Unique name for raw image
                        $newName = 'raw_' . $id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                        
                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            $inboundingModel->add_raw_image($id, $newName);
                        }
                    }
                }
            }
            $logData = [
                    'userid_log' => $_POST['userid_log'] ?? '',
                    'i_id' => $id,
                    'stat' => 'Photoshoot'
                ];
            $log_res =  $inboundingModel->stat_logs($logData);
            header("Location: " . base_url("?page=inbounding&action=list"));
            exit;
        }
    }
    public function updatedesktopform() {
        global $inboundingModel;

        // 1. Setup & Checks
        $id = $_GET['id'] ?? 0;
        $oldData = $inboundingModel->getform1data($id);

        if (!$oldData) { echo "Record not found."; exit; }

        // --- File Upload Logic ---
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
        $cat_input = $_POST['category_code'] ?? '';
        // Convert array to comma-separated string (e.g., "1,5,8")
        $category_val = is_array($cat_input) ? implode(',', $cat_input) : $cat_input;

        // --- Auto-Generate Item Code Logic (For Non-Variants) ---
        // --- Auto-Generate Item Code Logic ---
        // --- Auto-Generate Item Code Logic ---
        if ($is_variant === 'N' && (empty($item_code) || $old_is_variant === 'Y')) {

            // 1. Get Group Name (Use the NEW function to search by 'category' column)
            $group_val = $_POST['group_name'] ?? ''; 
            $group_real_name = $inboundingModel->getGroupNameByCode($group_val); 

            // 2. Get Category Name (Use the EXISTING function to search by 'id' column)
            $category_id = $_POST['category_code'] ?? 0;
            $cat_real_name = $inboundingModel->getCategoryName($category_id);
            
            $next_count = $inboundingModel->getNextProductCount();

            // 3. Generate Chars
            // If name is found, take 1st letter. If not found (empty), default to 'X'
            $char1  = !empty($group_real_name) ? strtoupper(substr($group_real_name, 0, 1)) : 'X';
            $char23 = !empty($cat_real_name)   ? strtoupper(substr($cat_real_name, 0, 2)) : 'XX';
            $increment_str = str_pad($next_count, 3, '0', STR_PAD_LEFT);

            $item_code = $char1 . $char23 . $increment_str;
        }

        // --- SKU GENERATION LOGIC ---
        $size  = trim($_POST['size'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $generated_sku = '';

        if ($is_variant === 'N') {
            // Rule: If Variant is No, SKU is exactly the Item Code
            $generated_sku = $item_code;
        } elseif ($is_variant === 'Y') {
            // Rule: If Variant is Yes, SKU is ItemCode-Size-Color
            // Note: We use the POSTed Item_code here, which comes from the selection
            $generated_sku = $item_code . '-' . $size . '-' . $color;
        }

        // --- Handle Array Inputs ---
        $sub_cat_input = $_POST['sub_category_code'] ?? '';
        $sub_cat_val   = is_array($sub_cat_input) ? implode(',', $sub_cat_input) : $sub_cat_input;

        $sub_sub_input = $_POST['sub_sub_category_code'] ?? '';
        $sub_sub_val   = is_array($sub_sub_input) ? implode(',', $sub_sub_input) : $sub_sub_input;


        $icons_raw = $_POST['description_icons'] ?? ''; 
        $icons_val = is_array($icons_raw) ? implode(',', $icons_raw) : $icons_raw;

        // 3. Data Array
        $data = [
            'invoice_image'       => $invoicePath,
            'is_variant'          => $is_variant,
            'Item_code'           => $item_code,
            'sku'                 => $generated_sku, // <--- ADDED SKU HERE
            'group_name'          => $_POST['group_name'] ?? '', 
            'category_code'       => $category_val,
            'sub_category_code'   => $sub_cat_val, 
            'sub_sub_category_code' => $sub_sub_val,
            'stock_added_date'    => $_POST['stock_added_date'] ?? '',
            'received_by_user_id' => $_POST['received_by_user_id'] ?? '',
            'updated_by_user_id'  => $_POST['updated_by_user_id'] ?? '',
            'invoice_no'          => $_POST['invoice_no'] ?? '',
            'material_code'       => $_POST['material_code'] ?? '',
            'product_title'       => $_POST['product_title'] ?? '',
            'key_words'           => $_POST['key_words'] ?? '',
            'snippet_description' => $_POST['snippet_description'] ?? '',
            'vendor_code'         => $_POST['vendor_code'] ?? '',
            'inr_pricing'         => $_POST['inr_pricing'] ?? '',
            'cp'                  => $_POST['cp'] ?? '',
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
            'permanently_available'=> $_POST['permanently_available'] ?? '',
            'ware_house_code'     => $_POST['ware_house_code'] ?? '',
            'store_location'      => $_POST['store_location'] ?? '',
            'back_order'          => $_POST['back_order'] ?? '',
            'lead_time_days'      => $_POST['lead_time_days'] ?? '',
            'in_stock_leadtime_days' => $_POST['in_stock_leadtime_days'] ?? '',
            'description_icons'   => $icons_val, 
            'us_block'            => $_POST['us_block'] ?? '',
            'dimention_unit'      => $_POST['dimention_unit'] ?? '',
            'weight_unit'         => $_POST['weight_unit'] ?? '',
        ];

        // 4. Save
        $result = $inboundingModel->updatedesktopform($id, $data);
        if (isset($_POST['photo_order']) && is_array($_POST['photo_order'])) {
            foreach ($_POST['photo_order'] as $img_id => $order_num) {
                // Call the new specific function
                $inboundingModel->update_image_order($img_id, $order_num);
            }
        }
        if ($result['success']) {
            $logData = [
                    'userid_log' => $_POST['userid_log'] ?? '',
                    'i_id' => $id,
                    'stat' => 'Data Entry'
                ];
            $log_res =  $inboundingModel->stat_logs($logData);
            header("location: " . base_url('?page=inbounding&action=list'));
            exit;
        } else {
            echo "Update failed: " . $result['message'];
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
            
            $saveData = [
                'vendor_id' => $vendor_id,
                'invoice'    => $invoicePath,
                'invoice_no' => $invoice_no
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
    public function saveform3() {
        global $inboundingModel;
        $record_id = $_POST['record_id'] ?? '';
        
        // 1. Fetch Existing Data for Logic (We need Category & Material IDs)
        // Assuming getById returns the row from vp_inbound
        $existingData = $inboundingModel->getById($record_id); 
        
        // 2. Fetch Names for Initials
        // A. Get Category Name (First Char)
        $categoryName = '';
        if (!empty($existingData['group_name'])) {
            // Fetch from 'vp_categories' table using group_name (which is category ID)
            $catData = $inboundingModel->getCategoryById($existingData['group_name']);
            $categoryName = $catData['display_name'] ?? ''; // Assuming column is 'category' or 'name'
        }

        // B. Get Material Name (First Char)
        $materialId = $_POST['material_code'] ?? '';
        $materialName = '';
        if (!empty($materialId)) {
            $matData = $inboundingModel->getMaterialById($materialId);
            $materialName = $matData['material_name'] ?? '';
        }

        // C. Get Color (First Char)
        $colorName = $_POST['color'] ?? '';

        // 3. Generate Prefix (e.g., "BSB")
        $char1 = !empty($categoryName) ? strtoupper(substr($categoryName, 0, 1)) : 'X';
        $char2 = !empty($materialName) ? strtoupper(substr($materialName, 0, 1)) : 'X';
        $char3 = !empty($colorName)    ? strtoupper(substr($colorName, 0, 1))    : 'X';
        $prefix = $char1 . $char2 . $char3;

        // 4. Generate Full Temp Code (e.g., "BSB001")
        // This function checks the DB for the last code starting with this prefix
        $temp_code = $inboundingModel->generateNextTempCode($prefix);

        // 5. Prepare Save Data
        $gate_entry_date_time = date("Y-m-d H:i:s", strtotime($_POST['gate_entry_date_time'] ?? 'now'));
        
        $saveData = [
            'gate_entry_date_time' => $gate_entry_date_time,
            'material_code'        => $materialId,
            'height'               => $_POST['height'] ?? '',
            'width'                => $_POST['width'] ?? '',
            'depth'                => $_POST['depth'] ?? '',
            'weight'               => $_POST['weight'] ?? '',
            'color'                => $colorName,
            'Quantity'             => $_POST['quantity_received'] ?? '',
            'size'                 => $_POST['size'] ?? '',
            'cp'                   => $_POST['cp'] ?? '',
            'received_by_user_id'  => $_POST['received_by_user_id'] ?? '',
            'temp_code'            => $temp_code // Add generated code
        ];

        $insertId = $inboundingModel->saveform3($record_id, $saveData);
        
        if ($insertId) {
            header("location: " . base_url('?page=inbounding&action=print&id=' . $record_id));
            exit;
        } else {
            echo "Database error.";
        }
    }

    public function updateform3() {
        global $inboundingModel;
        $record_id = $_POST['record_id'] ?? '';
        
        if (empty($record_id)) {
            echo "Record ID missing.";
            exit;
        }

        // --- 1. TEMP CODE GENERATION LOGIC ---
        
        // A. Fetch existing inbound data to get the Group ID (which maps to Category)
        $existingData = $inboundingModel->getById($record_id); 
        
        // B. Get Category Name (First Char)
        $categoryName = '';
        if (!empty($existingData['group_name'])) {
            // Fetch from 'vp_categories' table using group_name (which is category ID)
            $catData = $inboundingModel->getCategoryById($existingData['group_name']);
            $categoryName = $catData['display_name'] ?? ''; // Assuming column is 'category' or 'name'
        }

        // C. Get Material Name (First Char)
        $materialId = $_POST['material_code'] ?? '';
        $materialName = '';
        if (!empty($materialId)) {
            $matData = $inboundingModel->getMaterialById($materialId);
            $materialName = $matData['material_name'] ?? '';
        }

        // D. Get Color (First Char)
        $colorName = $_POST['color'] ?? '';

        // E. Construct Prefix (e.g., "BSB")
        $char1 = !empty($categoryName) ? strtoupper(substr($categoryName, 0, 1)) : 'X';
        $char2 = !empty($materialName) ? strtoupper(substr($materialName, 0, 1)) : 'X';
        $char3 = !empty($colorName)    ? strtoupper(substr($colorName, 0, 1))    : 'X';
        $prefix = $char1 . $char2 . $char3;

        // F. Generate Full Temp Code (e.g., "BSB001")
        $temp_code = $inboundingModel->generateNextTempCode($prefix);
        // --- 2. PREPARE UPDATE DATA ---

        $gate_entry_date_time = $_POST['gate_entry_date_time'] ?? '';
        
        $updateData = [
            'gate_entry_date_time' => $gate_entry_date_time,
            'material_code'        => $materialId,
            'height'               => $_POST['height'] ?? '',
            'width'                => $_POST['width'] ?? '',
            'depth'                => $_POST['depth'] ?? '',
            'weight'               => $_POST['weight'] ?? '',
            'color'                => $colorName,
            'quantity_received'    => $_POST['quantity_received'] ?? '',
            'size'                 => $_POST['size'] ?? '',
            'cp'                   => $_POST['cp'] ?? '',
            'received_by_user_id'  => $_POST['received_by_user_id'] ?? '',
            'temp_code'            => $temp_code // Add Generated Code here
        ];

        // Call model update
        $updated = $inboundingModel->updateForm3($record_id, $updateData);

        if ($updated['success']) {
            // Redirect to label page
            header("Location: " . base_url("?page=inbounding&action=label&id=" . $record_id));
            exit;
        } else {
            echo "Update failed: " . $updated['message'];
            exit;
        }
    }
    public function getNextMaterialOrderAjax() {
        global $inboundingModel;
        header('Content-Type: application/json');
        
        $nextOrder = $inboundingModel->getNextMaterialOrder();
        
        // Ensure we send a valid integer, defaulting to 1 on error
        echo json_encode(['next_order' => $nextOrder ? $nextOrder : 1]);
        exit;
    }

    public function addMaterialAjax() {
        global $inboundingModel;
        header('Content-Type: application/json');
        
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $name   = trim($_POST['material_name'] ?? '');
        $slug   = trim($_POST['material_slug'] ?? '');
        $active = (int)($_POST['is_active'] ?? 1);
        $order  = (int)($_POST['display_order'] ?? 0);
        $userId = !empty($_POST['user_id']) ? $_POST['user_id'] : $_SESSION['user_id'];

        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => 'Material Name is required']);
            exit;
        }

        // Call Model
        $result = $inboundingModel->insertMaterial($name, $slug, $active, $order, $userId);

        if ($result === "DUPLICATE") {
            echo json_encode(['success' => false, 'message' => 'Material name already exists!']);
        } elseif ($result) {
            echo json_encode([
                'success' => true, 
                'id'      => $result, 
                'name'    => $name,
                'message' => 'Material added successfully'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Database error. Check table structure.']);
        }
        exit;
    }
}
?>