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
            'status_step'         => $_GET['status_step'] ?? '',
            'updated_by_user_id'  => $_GET['updated_by'] ?? ''
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
            'updated_user_list' => $dropdowns['updated_users'],
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
                'important_info', 'optionals', 'bundled_items', 'keywords', 'usblock', 
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
                    $row['optionals'] ?? '',        
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
        $data['variation'] = $inboundingModel->getVariations($id);
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
        $data['form2']['gecolormaps'] = $this->gecolormaps();
        $data['form2']['optionals_data'] = $this->getoptionals();
        $data['form2']['getimgdir'] = $this->getimgdir();
        $data['images'] = $inboundingModel->getitem_imgs($id);
        $data['markup_list'] = $inboundingModel->getMarkupData();
        // echo "<pre>";print_r($data['getimgdir']);exit;
        renderTemplate('views/inbounding/desktopform.php', $data, 'desktopform inbounding');
    }
    function getimgdir() {
        $url = 'https://www.exoticindia.com/vendor-api/product/image-directories';
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
    function gecolormaps() {
        $url = 'https://www.exoticindia.com/vendor-api/product/colormaps';
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
    function getoptionals() {
        $url = 'https://www.exoticindia.com/vendor-api/product/optionals';
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
    public function getform3() {
        is_login();
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        if (isset($id) && $id != 0) {
            $data = $inboundingModel->getform2data($id);
            $data['form2']['gecolormaps'] = $this->gecolormaps();
            renderTemplateClean('views/inbounding/form3.php', $data, 'form3 inbounding');
        }else{
            header("location: " . base_url('?page=inbounding&action=list'));
        }
    }
    public function saveform1() {
        global $inboundingModel;
        
        $vendor_id  = $_POST['vendor_id'] ?? '';
        $record_id  = $_POST['record_id'] ?? '';
        $invoice_no = $_POST['invoice_no'] ?? '';
        
        // Default invoice path is empty
        $invoicePath = ''; 

        // CHECK: Only try to upload if a file was actually sent and has no errors
        if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] === 0) {
            
            $uploadDir = __DIR__ . '/../uploads/invoice/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmp  = $_FILES['invoice']['tmp_name'];
            $fileName = $_FILES['invoice']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // ADDED 'pdf' to allowed list
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

            if (!in_array($fileExt, $allowed)) {
                echo "Only JPG, PNG, WEBP, and PDF allowed.";
                exit;
            }

            $newFile = "IMG_" . time() . "." . $fileExt;
            $dest    = $uploadDir . $newFile;

            if (move_uploaded_file($fileTmp, $dest)) {
                $invoicePath = "uploads/invoice/" . $newFile;
            } else {
                echo "Upload failed.";
                exit;
            }
        }

        // Prepare data (Invoice path will be empty string if no file uploaded)
        $saveData = [
            'vendor_id'  => $vendor_id,
            'invoice'    => $invoicePath,
            'invoice_no' => $invoice_no
        ];

        $insertId = $inboundingModel->saveform1($record_id, $saveData);

        if ($insertId) {
            header("location: " . base_url('?page=inbounding&action=form3&id=' . $insertId));
            exit;
        } else {
            echo "Database error.";
        }
    }

    public function updateform1() {
        global $inboundingModel;

        $id         = $_GET['id'] ?? 0;
        $vendor_id  = $_POST['vendor_id'] ?? '';
        $invoice_no = $_POST['invoice_no'] ?? '';

        $oldData = $inboundingModel->getform1data($id);

        if (!$oldData) {
            echo "Record not found.";
            exit;
        }

        // Keep old image by default
        $invoicePath = $oldData['form1']['invoice_image'];

        // Only update image if a NEW file is uploaded
        if (isset($_FILES['invoice']) && $_FILES['invoice']['error'] === 0) {

            $uploadDir = __DIR__ . '/../uploads/invoice/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $fileTmp  = $_FILES['invoice']['tmp_name'];
            $fileName = $_FILES['invoice']['name'];
            $fileExt  = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // ADDED 'pdf' to allowed list
            $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];

            if (!in_array($fileExt, $allowed)) {
                echo "Only JPG, PNG, WEBP, and PDF allowed.";
                exit;
            }

            $newFile = "IMG_" . time() . "." . $fileExt;
            $dest    = $uploadDir . $newFile;

            if (move_uploaded_file($fileTmp, $dest)) {
                $invoicePath = "uploads/invoice/" . $newFile;
            }
        }

        $data = [
            'id'         => $id,
            'vendor_id'  => $vendor_id,
            'invoice'    => $invoicePath,
            'invoice_no' => $invoice_no
        ];

        $updated = $inboundingModel->updateform1($data);

        if ($updated) {
            header("location: " . base_url('?page=inbounding&action=form3&id=' . $id));
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
            
            // 1. Deletions
            if (!empty($_POST['delete_ids'])) {
                foreach ($_POST['delete_ids'] as $del_id) {
                    $inboundingModel->delete_image(intval($del_id));
                }
            }

            // 2. Update Existing Images (Order & Caption)
            if (!empty($_POST['image_ids_ordered'])) {
                $counter = 1; 
                foreach ($_POST['image_ids_ordered'] as $img_id) {
                    if(isset($_POST['captions'][$img_id])) {
                        $caption = $_POST['captions'][$img_id] ?? '';
                        $inboundingModel->update_image_meta($img_id, $caption, $counter);
                        $counter++;
                    }
                }
            }

            // 3. Handle NEW File Uploads with Variation ID
            if (!empty($_FILES['new_photos']['name'][0])) {
                $uploadDir = __DIR__ . '/../uploads/itm_img/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                // Iterate over uploaded files
                foreach ($_FILES['new_photos']['name'] as $key => $name) {
                    if ($_FILES['new_photos']['error'][$key] === 0) {
                        
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $newName = 'img_' . $id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                        
                        // Retrieve Caption AND Variation ID for this specific file index
                        $newCaption = $_POST['new_captions'][$key] ?? '';
                        $varId = $_POST['new_image_variation_id'][$key] ?? -1; // Default to Base (-1)

                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            // Pass Variation ID to Model
                            $inboundingModel->add_image($id, $newName, $newCaption, 0, $varId);
                        }
                    }
                }
            }
            
            // 4. Log and Redirect
            $logData = ['userid_log' => $_POST['userid_log']??'', 'i_id' => $id, 'stat' => 'Editing'];
            $inboundingModel->stat_logs($logData);
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
        $item_code = $inboundingModel->get_temp_code($id);
        if(empty($images)) {
            echo "<script>alert('No images found for this item.'); history.back();</script>";
            exit;
        }

        // 2. Setup Zip
        $zip = new ZipArchive();
        $zipName = "Item_{$item_code['temp_code']}_Photos_" . date('Ymd_His') . ".zip";
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
        
        // 1. Get Raw Images (Now includes variation_id column)
        $data['images'] = $inboundingModel->get_raw_imgs($id);
        // 2. Get Item Details
        $data['item'] = $inboundingModel->getItemDetails($id);
        $data['record_id'] = $id;

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
                // FOLDER PATH
                $uploadDir = __DIR__ . '/../uploads/itm_raw_img/';
                
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
                
                foreach ($_FILES['new_photos']['name'] as $key => $name) {
                    if ($_FILES['new_photos']['error'][$key] === 0) {
                        
                        $tmpName = $_FILES['new_photos']['tmp_name'][$key];
                        $ext = pathinfo($name, PATHINFO_EXTENSION);
                        $newName = 'raw_' . $id . '_' . time() . '_' . rand(100,999) . '.' . $ext;
                        
                        // --- NEW: Retrieve Variation ID ---
                        // This comes from the hidden input generated by JS for this specific file index
                        $varId = $_POST['new_image_variation_id'][$key] ?? -1; 

                        if (move_uploaded_file($tmpName, $uploadDir . $newName)) {
                            // Pass Variation ID to Model
                            $inboundingModel->add_raw_image($id, $newName, $varId);
                        }
                    }
                }
            }
            
            $logData = [
                'userid_log' => $_POST['userid_log'] ?? '',
                'i_id' => $id,
                'stat' => 'Photoshoot'
            ];
            $inboundingModel->stat_logs($logData);
            
            header("Location: " . base_url("?page=inbounding&action=list"));
            exit;
        }
    }
    private function renameImagesToItemCode($itemId, $itemCode, $currentData) {
        global $inboundingModel;
        $itemCode = strtolower($itemCode);
        // 1. Setup Directories
        $mainPhotoDir = __DIR__ . '/../uploads/products/';
        $altPhotoDir  = __DIR__ . '/../uploads/itm_img/';

        // 2. Fetch Variations from DB
        $variations = $inboundingModel->getVariations($itemId);
        $hasVariations = !empty($variations);

        // 3. Determine Case & Suffix Logic
        $isVariant = ($currentData['is_variant'] === 'Y'); 

        // Generate Base Name Logic
        if (!$isVariant && !$hasVariations) {
            // CASE 1: Simple Product -> abc1234
            $baseName = $itemCode; 
        } elseif (!$isVariant && $hasVariations) {
            // CASE 2: Complex Product (Base) -> abc1234
            $baseName = $itemCode; 
        } else {
            // CASE 3 & 4: Variation -> abc1234-color
            $suffix = $this->getNamingSuffix($currentData['color'], $currentData['size']);
            $baseName = $itemCode . ($suffix ? '-' . $suffix : '');
        }

        // =================================================================
        // EXECUTION A: Rename Base Images (Variation ID -1 or 0)
        // =================================================================
        
        // 1. Rename Main Product Photo (inbound_item table)
        $itemData = $inboundingModel->getform1data($itemId);
        $mainPhotoPath = $itemData['form2']['product_photo'] ?? '';
        
        if (!empty($mainPhotoPath)) {
            // Main photo always gets the base name
            $this->processRename($mainPhotoPath, $mainPhotoDir, $baseName, $itemId, 'main', $inboundingModel);
        }

        // 2. Rename Gallery Images
        $baseImages = $inboundingModel->get_item_images_by_variation($itemId, -1);
        if (empty($baseImages)) $baseImages = $inboundingModel->get_item_images_by_variation($itemId, 0);

        if (!empty($baseImages)) {
            $counter = 0; // Start at 0 to track the "First" image
            foreach ($baseImages as $img) {
                
                // Logic: 
                // 1st Image (0) -> abc1234.jpg
                // 2nd Image (1) -> abc1234_a01.jpg
                if ($counter === 0) {
                    $newName = $baseName; 
                } else {
                    $newName = $baseName . "_a" . str_pad($counter, 2, '0', STR_PAD_LEFT);
                }

                $this->processRename($img['file_name'], $altPhotoDir, $newName, $img['id'], 'gallery', $inboundingModel);
                $counter++;
            }
        }

        // =================================================================
        // EXECUTION B: Rename Variations
        // =================================================================
        if ($hasVariations) {
            foreach ($variations as $var) {
                
                // Generate Suffix: abc1234-blue
                $varSuffix = $this->getNamingSuffix($var['color'], $var['size']);
                $varBaseName = $itemCode . ($varSuffix ? '-' . $varSuffix : '');

                // 1. Rename Variation Main Photo
                if (!empty($var['variation_image'])) {
                    $this->processRename($var['variation_image'], $mainPhotoDir, $varBaseName, $var['id'], 'variation_main', $inboundingModel);
                }

                // 2. Rename Variation Gallery Images
                $varImages = $inboundingModel->get_item_images_by_variation($itemId, $var['id']);
                if (!empty($varImages)) {
                    $vCounter = 0; // Start at 0
                    foreach ($varImages as $vImg) {
                        
                        // Logic:
                        // 1st Image (0) -> abc1234-blue.jpg
                        // 2nd Image (1) -> abc1234-blue_a01.jpg
                        if ($vCounter === 0) {
                            $vNewName = $varBaseName;
                        } else {
                            $vNewName = $varBaseName . "_a" . str_pad($vCounter, 2, '0', STR_PAD_LEFT);
                        }

                        $this->processRename($vImg['file_name'], $altPhotoDir, $vNewName, $vImg['id'], 'gallery', $inboundingModel);
                        $vCounter++;
                    }
                }
            }
        }
    }

    // --- HELPER 1: Suffix Logic (Color > Size) ---
    private function getNamingSuffix($color, $size) {
        // Priority 1: Color
        if (!empty($color)) {
            // Lowercase and remove spaces (e.g. "Light Blue" -> "lightblue")
            return strtolower(str_replace(' ', '', trim($color)));
        }
        // Priority 2: Size
        if (!empty($size)) {
            return strtolower(str_replace(' ', '', trim($size)));
        }
        return ''; // No suffix
    }

    // --- HELPER 2: File Renaming & DB Update ---
    private function processRename($oldPathOrName, $directory, $newBaseName, $dbId, $type, $model) {
        
        // Handle input: oldPathOrName might be "uploads/products/img.jpg" or just "img.jpg"
        $oldFileName = basename($oldPathOrName);
        $fullOldPath = $directory . $oldFileName;

        if (file_exists($fullOldPath)) {
            $ext = strtolower(pathinfo($oldFileName, PATHINFO_EXTENSION));
            $finalName = $newBaseName . "." . $ext;
            $fullNewPath = $directory . $finalName;

            // Skip if name is already correct
            if ($oldFileName === $finalName) return;

            if (rename($fullOldPath, $fullNewPath)) {
                // Update Database based on type
                if ($type === 'main') {
                    $dbPath = "uploads/products/" . $finalName;
                    $model->update_main_product_photo($dbId, $dbPath);
                } elseif ($type === 'variation_main') {
                    $dbPath = "uploads/products/" . $finalName;
                    $model->update_variation_photo($dbId, $dbPath);
                } elseif ($type === 'gallery') {
                    // Gallery stores just filename usually, or check your logic
                    // Your current code stores just filename in item_images? 
                    // Based on "uploads/itm_img/filename" in your view, it likely stores just filename.
                    $model->update_image_filename_direct($dbId, $finalName);
                }
            }
        }
    }
    public function updatedesktopform() {
        global $inboundingModel;

        // 1. Setup & Checks
        $id = $_GET['id'] ?? 0;
        $oldData = $inboundingModel->getform1data($id);

        if (!$oldData) { echo "Record not found."; exit; }

        // --- Main Invoice File Upload Logic ---
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

        // 2. Capture Inputs & Change Detection
        $is_variant = $_POST['is_variant'] ?? '';
        $item_code  = $_POST['Item_code'] ?? '';

        $old_is_variant = $oldData['form2']['is_variant'] ?? '';
        
        $old_group_val = $oldData['form2']['group_name'] ?? '';
        $old_cat_val   = $oldData['form2']['category_code'] ?? '';
        $new_group_val = $_POST['group_name'] ?? '';
        
        $raw_cat = $_POST['category_code'] ?? 0;
        $category_id = is_array($raw_cat) ? $raw_cat[0] : $raw_cat;

        // Flag: Should we rename images?
        $shouldRename = false; 

        // If Group or Category changes, reset item code to trigger generation
        if ((($new_group_val != $old_group_val) || ($category_id != $old_cat_val)) && $is_variant === 'N') {
             $item_code = ''; 
        }

        // 2. Generate Prefix Logic
        $group_val = $_POST['group_name'] ?? ''; 
        $group_real_name = trim($inboundingModel->getGroupNameByCode($group_val)); 
        $cat_real_name = trim($inboundingModel->getCategoryName($category_id));
        $char1 = !empty($group_real_name) ? strtoupper(substr($group_real_name, 0, 1)) : 'X';
        $char2 = !empty($cat_real_name)   ? strtoupper(substr($cat_real_name, 0, 1)) : 'X';
        $current_prefix = $char1 . $char2; 

        // 3. GENERATE NEW ITEM CODE (If needed)
        if ($is_variant === 'N' && (empty($item_code) || $old_is_variant === 'Y')) {

            $last_code = $inboundingModel->getLastItemCodeGlobal();
            $is_unique = false;
            $attempts = 0;

            if (empty($last_code)) {
                $letters = 'AA'; $number = 0; 
            } else {
                $last_sequence = substr($last_code, 2); 
                if (preg_match('/^([A-Z]+)(\d+)$/', $last_sequence, $matches)) {
                    $letters = $matches[1]; $number = intval($matches[2]);
                } else {
                    $letters = 'AA'; $number = 0; 
                }
            }

            do {
                $attempts++;
                if ($number < 99) { $number++; } else { $number = 1; $letters++; }
                $new_seq = $letters . str_pad($number, 2, '0', STR_PAD_LEFT);
                $candidate_code = $current_prefix . $new_seq;
                $exists = $inboundingModel->checkItemCodeExists($candidate_code);

                if (!$exists) {
                    $item_code = $candidate_code;
                    $is_unique = true;
                    $shouldRename = true; // Trigger renaming since code changed
                }
                if ($attempts > 100) die("Error: Unable to generate unique item code.");
            } while (!$is_unique);
        } 
        // IF ITEM CODE EXISTED and didn't change, we still might want to rename images 
        // to match current colors/sizes if they were updated.
        else if (!empty($item_code)) {
            $shouldRename = true; 
        }

        // --- SKU GENERATION ---
        $size  = trim($_POST['size'] ?? '');
        $color = trim($_POST['color'] ?? '');
        $generated_sku = '';
        if ($is_variant === 'N') {
            $generated_sku = $item_code;
        } elseif ($is_variant === 'Y') {
            if(!empty($item_code)) {
                $generated_sku = $item_code . '-' . $size . '-' . $color;
            } else {
                echo "Error: Parent Item Code is missing."; exit;
            }
        }

        // --- Handle Inputs ---
        $cat_input = $_POST['category_code'] ?? '';
        $category_val = is_array($cat_input) ? implode(',', $cat_input) : $cat_input;
        $sub_cat_input = $_POST['sub_category_code'] ?? '';
        $sub_cat_val   = is_array($sub_cat_input) ? implode(',', $sub_cat_input) : $sub_cat_input;
        $sub_sub_input = $_POST['sub_sub_category_code'] ?? '';
        $sub_sub_val   = is_array($sub_sub_input) ? implode(',', $sub_sub_input) : $sub_sub_input;
        $icons_raw = $_POST['optionals'] ?? ''; 
        $icons_val = is_array($icons_raw) ? implode(',', $icons_raw) : $icons_raw;
        $back_order_input = $_POST['back_order'] ?? '0'; 
        $percent_val = ($back_order_input == '1') ? (!empty($_POST['backorder_percent']) ? intval($_POST['backorder_percent']) : 0) : 0;
        $day_val     = ($back_order_input == '1') ? (!empty($_POST['backorder_day'])     ? intval($_POST['backorder_day'])     : 0) : 0;

        // --- Main Photo Upload ---
        $mainProductPhoto = $_POST['old_product_photo_main'] ?? ($oldData['form2']['product_photo'] ?? ''); 
        if (isset($_FILES['product_photo_main']) && $_FILES['product_photo_main']['error'] === UPLOAD_ERR_OK) {
            $tmpName = $_FILES['product_photo_main']['tmp_name'];
            $name    = $_FILES['product_photo_main']['name'];
            $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $uploadDir = __DIR__ . '/../uploads/products/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $newFileName = "MAIN_" . $id . "_" . time() . "_" . rand(100,999) . "." . $ext;
                if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                    $mainProductPhoto = "uploads/products/" . $newFileName;
                }
            }
        }
        
        // Search String Logic
        $s_group   = $_POST['search_group'] ?? '';
        $s_cat_arr = $_POST['search_cat'] ?? [];
        $s_cat     = is_array($s_cat_arr) ? implode(',', $s_cat_arr) : $s_cat_arr;
        $s_sub_arr = $_POST['search_sub'] ?? [];
        $s_sub     = is_array($s_sub_arr) ? implode(',', $s_sub_arr) : $s_sub_arr;
        $s_subsub_arr = $_POST['search_sub_sub'] ?? [];
        $s_subsub     = is_array($s_subsub_arr) ? implode(',', $s_subsub_arr) : $s_subsub_arr;
        $search_category_string = $s_subsub . '|' . $s_sub . '|' . $s_cat . '|' . $s_group;
        $search_term = $_POST['search_term'] ?? '';

        // 3. Prepare Data
        $data = [
            'product_photo'       => $mainProductPhoto,
            'invoice_image'       => $invoicePath,
            'search_term'         => $search_term,
            'search_category_string' => $search_category_string,
            'is_variant'          => $is_variant,
            'Item_code'           => $item_code,
            'sku'                 => $generated_sku,
            'group_name'          => $_POST['group_name'] ?? '', 
            'colormaps'           => $_POST['colormaps'] ?? '',
            'image_directory'     => $_POST['image_directory'] ?? '',
            'category_code'       => $category_val,
            'sub_category_code'   => $sub_cat_val, 
            'sub_sub_category_code' => $sub_sub_val,
            'added_date'    => $_POST['added_date'] ?? '',
            'received_by_user_id' => $_POST['received_by_user_id'] ?? '',
            'updated_by_user_id'  => $_POST['updated_by_user_id'] ?? '',
            'invoice_no'          => $_POST['invoice_no'] ?? '',
            'material_code'       => $_POST['material_code'] ?? '',
            'product_title'       => $_POST['product_title'] ?? '',
            'key_words'           => $_POST['key_words'] ?? '',
            'snippet_description' => $_POST['snippet_description'] ?? '',
            'vendor_code'         => $_POST['vendor_code'] ?? '',
            'cp'                  => $_POST['cp'] ?? '',
            'price_india_mrp'     => $_POST['price_india_mrp'] ?? '',
            'price_india'         => $_POST['price_india'] ?? '',
            'usd_price'             => !empty($_POST['usd_price']) ? $_POST['usd_price'] : 0,
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
            'marketplace'         => $_POST['marketplace'] ?? '',
            'india_net_qty'       => $_POST['india_net_qty'] ?? '',
            'lead_time_days'      => $_POST['lead_time_days'] ?? '',
            'in_stock_leadtime_days' => $_POST['in_stock_leadtime_days'] ?? '',
            'optionals'   => $icons_val, 
            'back_order'          => $back_order_input,
            'backorder_percent'   => $percent_val,
            'backorder_day'       => $day_val,
            'us_block'            => $_POST['us_block'] ?? '',
            'dimention_unit'      => $_POST['dimention_unit'] ?? '',
            'weight_unit'         => $_POST['weight_unit'] ?? '',
            'feedback'         => $_POST['feedback'] ?? '',
        ];
        
        // 4. Update Main Record
        $result = $inboundingModel->updatedesktopform($id, $data);

        // --- VARIATIONS LOGIC ---
        $allVariations = $_POST['variations'] ?? [];
        foreach ($allVariations as $key => &$variant) {
            $variant['id'] = $variant['id'] ?? '';
            $uploadError = $_FILES['variations']['error'][$key]['photo'] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_OK) {
                 $tmpName = $_FILES['variations']['tmp_name'][$key]['photo'];
                 $name    = $_FILES['variations']['name'][$key]['photo'];
                 $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                 if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                     $uploadDir = __DIR__ . '/../uploads/products/';
                     if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                     $newFileName = "VAR_" . $id . "_" . $key . "_" . time() . "_" . rand(100,999) . "." . $ext;
                     if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                         $variant['photo'] = "uploads/products/" . $newFileName;
                     }
                 }
            } else {
                 $variant['photo'] = $variant['old_photo'] ?? '';
            }
        }
        unset($variant);
        $inboundingModel->saveVariations($id, $allVariations, $item_code);

        // 5. Update Image Order & Assignment
        if (isset($_POST['photo_order']) && is_array($_POST['photo_order'])) {
            foreach ($_POST['photo_order'] as $img_id => $order_num) {
                $inboundingModel->update_image_order($img_id, $order_num);
            }
        }
        if (isset($_POST['photo_variation']) && is_array($_POST['photo_variation'])) {
            foreach ($_POST['photo_variation'] as $img_id => $var_id) {
                $inboundingModel->update_image_variation($img_id, $var_id);
            }
        }

        if ($result['success']) {
            
            // =========================================================
            // START: ADVANCED RENAMING LOGIC (4 CASES)
            // =========================================================
            if (!empty($item_code) && $shouldRename) {
                // Pass current POST data to helper so we use fresh color/size values
                $currentDataForRename = [
                    'is_variant' => $is_variant,
                    'color'      => $_POST['color'] ?? '',
                    'size'       => $_POST['size'] ?? ''
                ];
                $this->renameImagesToItemCode($id, $item_code, $currentDataForRename);
            }
            // =========================================================
            
            $logData = ['userid_log' => $_POST['userid_log'] ?? '', 'i_id' => $id, 'stat' => 'Data Entry'];
            $inboundingModel->stat_logs($logData);

            $action_clicked = $_POST['save_action'] ?? '';
            if ($action_clicked === 'draft') {
                header("location: " . base_url('?page=inbounding&action=desktopform&id=' . $id . '&msg=draft_saved'));
            } else {
                header("location: " . base_url('?page=inbounding&action=list'));
            }
            exit;
        } else {
            echo "Update failed: " . $result['message'];
        }
    }
    public function submitStep3() {
        global $inboundingModel;

        // 1. Basic Setup
        $record_id = $_POST['record_id'] ?? '';
        if (empty($record_id)) { echo "Record ID missing"; exit; }

        // 2. Process Variations
        $allVariations = array_values($_POST['variations'] ?? []);
        foreach ($allVariations as $index => &$variant) {
            $variant['id'] = $_POST['variations'][$index]['id'] ?? '';
            $variant['cp']              = !empty($variant['cp']) ? $variant['cp'] : 0;
            $variant['price_india']     = !empty($variant['price_india']) ? $variant['price_india'] : 0;
            $variant['price_india_mrp'] = !empty($variant['price_india_mrp']) ? $variant['price_india_mrp'] : 0;
            $variant['usd_price']       = !empty($variant['usd_price']) ? $variant['usd_price'] : 0;
            $variant['quantity']        = !empty($variant['quantity']) ? $variant['quantity'] : 0;
            // Handle File Uploads (Same as before)
            $uploadError = $_FILES['variations']['error'][$index]['photo'] ?? UPLOAD_ERR_NO_FILE;
            if ($uploadError === UPLOAD_ERR_OK) {
                $tmpName = $_FILES['variations']['tmp_name'][$index]['photo'];
                $name    = $_FILES['variations']['name'][$index]['photo'];
                $ext     = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                    $uploadDir = __DIR__ . '/../uploads/products/';
                    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                    $newFileName = "VAR_" . $record_id . "_" . $index . "_" . time() . "." . $ext;
                    if (move_uploaded_file($tmpName, $uploadDir . $newFileName)) {
                        $variant['photo'] = "uploads/products/" . $newFileName;
                    }
                }
            } else {
                $variant['photo'] = $variant['old_photo'] ?? '';
            }
        }
        unset($variant); 

        // 3. Extract Base Variant (Index 0)
        $mainVariant = $allVariations[0] ?? []; 

        // 4. TEMP CODE LOGIC (Same as before)
        $existingData = $inboundingModel->getById($record_id); 
        if (!empty($existingData['temp_code']) && $existingData['temp_code'] !== '0') {
            $temp_code = $existingData['temp_code'];
        } else {
            $categoryName = '';
            if (!empty($existingData['group_name'])) {
                $catData = $inboundingModel->getCategoryById($existingData['group_name']);
                $categoryName = $catData['display_name'] ?? ''; 
            }
            $materialId = $_POST['material_code'] ?? '';
            $materialName = '';
            if (!empty($materialId)) {
                $matData = $inboundingModel->getMaterialById($materialId);
                $materialName = $matData['material_name'] ?? '';
            }
            $colorName = $mainVariant['color'] ?? '';
            $char1 = !empty($categoryName) ? strtoupper(substr($categoryName, 0, 1)) : 'X';
            $char2 = !empty($materialName) ? strtoupper(substr($materialName, 0, 1)) : 'X';
            $char3 = !empty($colorName)    ? strtoupper(substr($colorName, 0, 1))    : 'X';
            $prefix = $char1 . $char2 . $char3;
            $temp_code = $inboundingModel->generateNextTempCode($prefix);
        }

        // 5. PREPARE MAIN UPDATE DATA
        // FIX: Mapping HTML inputs to exact DB Column names here
        $gate_entry = date("Y-m-d H:i:s", strtotime($_POST['gate_entry_date_time'] ?? 'now'));
        $mainUpdateData = [
            'gate_entry_date_time' => $gate_entry,
            'material_code'        => $_POST['material_code'] ?? '',
            'group_name'           => $_POST['category'] ?? '',
            'received_by_user_id'  => $_POST['received_by_user_id'] ?? '',
            'Item_code'             => $_POST['Item_code'] ?? '',
            'is_variant'             => $_POST['is_variant'] ?? '',
            'feedback'             => $_POST['feedback'] ?? '',
            'temp_code'            => $temp_code,
            
            // Map Index 0 Data to DB Columns
            'height'               => $mainVariant['height'] ?? 0,
            'width'                => $mainVariant['width'] ?? 0,
            'depth'                => $mainVariant['depth'] ?? 0,
            'weight'               => $mainVariant['weight'] ?? 0,
            'color'                => $mainVariant['color'] ?? '',
            'size'                 => $mainVariant['size'] ?? '',
            'cp'                   => $mainVariant['cp'] ?? 0,
            'product_photo'        => $mainVariant['photo'] ?? '',
            'store_location'       => $mainVariant['store_location'] ?? '',
            'price_india'          => $mainVariant['price_india'] ?? '',
            'price_india_mrp'      => $mainVariant['price_india_mrp'] ?? '',

            // CRITICAL FIX: Map 'quantity' from HTML to 'quantity_received' for DB
            'quantity_received'    => $mainVariant['quantity'] ?? 0, 
        ];
        // 6. Update Database
        $res = $inboundingModel->updateMainInbound($record_id, $mainUpdateData);

        if ($res['success']) {
            // Log logic...
            $userid_log = $_POST['userid_log'] ?? 0;
            if(method_exists($inboundingModel, 'stat_logs')) {
                 $inboundingModel->stat_logs(['stat'=>'inbound', 'userid_log'=>$userid_log, 'i_id'=>$record_id]);
            }

            // Save extra variations
            $inboundingModel->saveVariations($record_id, $allVariations, $temp_code);
            
            header("Location: " . base_url("?page=inbounding&action=label&id=" . $record_id));
            exit;
        } else {
            echo "Database Error: " . $res['message'];
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
        if (!isset($_SESSION['user']['id'])) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $name   = trim($_POST['material_name'] ?? '');
        $slug   = trim($_POST['material_slug'] ?? '');
        $active = (int)($_POST['is_active'] ?? 1);
        $order  = (int)($_POST['display_order'] ?? 0);
        $userId = !empty($_POST['user_id']) ? $_POST['user_id'] : $_SESSION['user']['id'];

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
    public function deleteSelected() {
        // 1. Use the Global variable (Like in your sample code)
        // Make sure this matches the variable name defined in your index.php
        global $inboundingModel; 
        global $conn; // Sometimes needed if the model relies on it implicitly

        // 2. Security Check
        if (!isset($_SESSION['user']['id'])) {
            // Redirect to login or show error
            header("Location: " . base_url('?page=inbounding&action=list&msg=unauthorized'));
            exit;
        }

        // 3. Get IDs from POST
        $ids_string = $_POST['ids'] ?? '';

        if (!empty($ids_string)) {
            // Convert string "1,2,3" into array
            $ids_array = explode(',', $ids_string);
            $ids_array = array_map('intval', $ids_array); // Security: Force integers
            $ids_array = array_filter($ids_array); // Remove empty values

            if (!empty($ids_array)) {
                // 4. Call Model using the global variable
                // Ensure you added the 'deleteInboundItems' function to your Model class!
                $result = $inboundingModel->deleteInboundItems($ids_array);

                if ($result) {
                    // Success: Redirect back to the list
                    header("Location: " . base_url('?page=inbounding&action=list&msg=deleted_success'));
                    exit;
                } else {
                    // Database Error
                    header("Location: " . base_url('?page=inbounding&action=list&msg=error_db'));
                    exit;
                }
            }
        }
        
        // Fallback: No IDs selected or other error
        header("Location: " . base_url('?page=inbounding&action=list&msg=no_selection'));
        exit;
    }
    public function inbound_product_publish(){
        global $inboundingModel;
        $API_data = array();

        // top level data
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $data = $inboundingModel->getpublishdata($id);

        // --- HELPER: Get Current Date in Y-m-d format ---
        $current_date_formatted = date("Y-m-d"); 
        // 1. Add all other fields FIRST
        $API_data['itemcode'] = $data['data']['Item_code'];
        $API_data['groupname'] = $data['data']['groupname'];
        $API_data['category'] = $data['data']['final_cat_ids'];
        $API_data['itemtype'] ='product';
        $API_data['title'] = $data['data']['product_title'];
        $API_data['status'] = 1;
        $API_data['snippet_description'] = $data['data']['snippet_description'];
        // $API_data['creator'] = $data['data']['received_by_user_id'];
        // $API_data['optionals'] = $data['data']['optionals'];
        $API_data['india_net_qty'] = (int)$data['data']['india_net_qty'];
        $API_data['keywords'] = $data['data']['key_words'];
        // Convert 'Y' to 1, and 'N' (or anything else) to 0
        $API_data['usblock']    = ($data['data']['us_block'] === 'Y') ? 1 : 0;
        $API_data['indiablock'] = ($data['data']['india_block'] === 'Y') ? 1 : 0;
        $API_data['hscode'] = $data['data']['hsn_code'];
        $API_data['date_first_added'] = date("Y-m-d", strtotime($data['data']['gate_entry_date_time']));
        $API_data['search_term'] = $data['data']['search_term'];
        $API_data['long_description'] = '';
        $API_data['long_description_india'] = '';
        $API_data['aplus_content_ids'] = '';
        if (isset($data['data']['optionals']) && !empty($data['data']['optionals'])) {
            $API_data['optionals'] = str_replace(',', '|', $data['data']['optionals']);
        }
        $API_data['material'] = $data['data']['material_name'];
        $API_data['discrete_vendors'][0]['vendor'] = $data['data']['vendor_code'];
        $API_data['discrete_vendors'][0]['priority'] = 1;
        $stock_price_temp = array();
        if ($data['data']['is_variant'] == 'N') {
            
            if (!empty($data['data']['var_rows'])) {
                $stock_price_temp[0]['size'] = "";
                $stock_price_temp[0]['color'] = "";
                $stock_price_temp[0]['item_level'] = 'parent';
            }else{
                $stock_price_temp[0]['size'] = "";
                $stock_price_temp[0]['color'] = "";
                $stock_price_temp[0]['item_level'] = 'standalone';
            }
        }else{
            $stock_price_temp[0]['size'] = $data['data']['size'];
            $stock_price_temp[0]['color'] = $data['data']['color'];
            $stock_price_temp[0]['item_level'] = 'variation';
        }
        $stock_price_temp[0]['marketplace_vendor'] = $data['data']['Marketplace'];
        $stock_price_temp[0]['colormap'] = $data['data']['colormaps'];
        $stock_price_temp[0]['product_weight'] = $data['data']['weight'];
        $stock_price_temp[0]['product_weight_unit'] = 'kg';
        $stock_price_temp[0]['prod_length'] = $data['data']['depth'];
        $stock_price_temp[0]['prod_width'] = $data['data']['width'];
        $stock_price_temp[0]['prod_height'] = $data['data']['height'];
        $stock_price_temp[0]['length_unit'] = 'inch';
        $input_date = $data['data']['added_date'] ?? null;
        if (!empty($input_date) && $input_date != '0000-00-00') {
            $stock_price_temp[0]['date_added'] = date('Y-m-d', strtotime($input_date));
        } else {
            $stock_price_temp[0]['date_added'] = date('Y-m-d');
        } 
        $stock_price_temp[0]['stock_date_added'] =date("Y-m-d", strtotime($current_date_formatted)); 
        $stock_price_temp[0]['local_stock'] = $data['data']['quantity_received'];
        $stock_price_temp[0]['flex_status'] = '0';
        $stock_price_temp[0]['fba_in'] = '0';
        $stock_price_temp[0]['fba_us'] = '0';
        $stock_price_temp[0]['fba_eu'] = '0';
        $stock_price_temp[0]['vendor_us'] = '0';
        $stock_price_temp[0]['price'] = (int) $data['data']['price_india'];
        $stock_price_temp[0]['price_india'] = (int) $data['data']['price_india'];
        $stock_price_temp[0]['price_india_suggested'] = (int) $data['data']['price_india'];
        $stock_price_temp[0]['mrp_india'] = (int) $data['data']['price_india_mrp'];
        $stock_price_temp[0]['gst'] = $data['data']['gst_rate'];
        $stock_price_temp[0]['permanent_discount'] = '1';
        $stock_price_temp[0]['discount_global'] = '0';
        $stock_price_temp[0]['today_global'] = '0';
        $stock_price_temp[0]['discount_india'] = '0';
        $stock_price_temp[0]['today_india'] = '0';
        $stock_price_temp[0]['upc'] = '';
        $stock_price_temp[0]['asin'] = '';
        $stock_price_temp[0]['location'] = $data['data']['store_location'];
        $stock_price_temp[0]['topurchase'] = '0';
        $stock_price_temp[0]['backorder_percent'] = $data['data']['backorder_percent'];
        $stock_price_temp[0]['backorder_weeks'] = $data['data']['backorder_day'];
        $stock_price_temp[0]['leadtime'] = $data['data']['lead_time_days'];
        $stock_price_temp[0]['instock_leadtime'] = $data['data']['in_stock_leadtime_days'];
        $stock_price_temp[0]['cp'] = $data['data']['cp'];
        $stock_price_temp[0]['permanently_available'] = ($data['data']['permanently_available'] === 'Y') ? 1 : 0;
        $stock_price_temp[0]['amazon_sold'] = '0';
        $stock_price_temp[0]['amazon_leadtime'] = '10';
        $stock_price_temp[0]['amazon_itemcode_alias'] = '';
        $stock_price_temp[0]['youtube_links'] = '';
        $stock_price_temp[0]['sketchfab_links'] = '';

        // Variation Records [1..n]
        if (!empty($data['data']['var_rows'])) {
            $i = 0;
            foreach ($data['data']['var_rows'] as $key => $value) {
                $i++;
                $stock_price_temp[$i]['size'] = $value['size'];
                $stock_price_temp[$i]['color'] = $value['color'];
                $stock_price_temp[$i]['marketplace_vendor'] = $data['data']['Marketplace'];
                $stock_price_temp[$i]['item_level'] = 'variation';
                $stock_price_temp[$i]['colormap'] = $value['colormaps'];
                $stock_price_temp[$i]['product_weight'] = $value['weight'];
                $stock_price_temp[$i]['product_weight_unit'] = 'kg';
                $stock_price_temp[$i]['prod_length'] = $value['depth'];
                $stock_price_temp[$i]['prod_width'] = $value['width'];
                $stock_price_temp[$i]['prod_height'] = $value['height'];
                $stock_price_temp[$i]['length_unit'] = 'inch';
                $stock_price_temp[$i]['date_added'] = $data['data']['added_date']; 
                $stock_price_temp[$i]['stock_date_added'] = date("Y-m-d", strtotime($current_date_formatted));
                $stock_price_temp[$i]['local_stock'] = $value['quantity_received'];
                $stock_price_temp[$i]['flex_status'] = '0';
                $stock_price_temp[$i]['fba_in'] = '0';
                $stock_price_temp[$i]['fba_us'] = '0';
                $stock_price_temp[$i]['fba_eu'] = '0';
                $stock_price_temp[$i]['vendor_us'] = '0';
                $stock_price_temp[$i]['price'] = (int) $value['price_india'];
                $stock_price_temp[$i]['price_india'] = (int) $value['price_india'];
                $stock_price_temp[$i]['price_india_suggested'] = (int) $data['data']['price_india'];
                $stock_price_temp[$i]['mrp_india'] = (int) $value['price_india_mrp'];
                $stock_price_temp[$i]['gst'] = $value['gst_rate'];
                $stock_price_temp[$i]['permanent_discount'] = '1';
                $stock_price_temp[$i]['discount_global'] = '0';
                $stock_price_temp[$i]['today_global'] = '0';
                $stock_price_temp[$i]['discount_india'] = '0';
                $stock_price_temp[$i]['today_india'] = '0';
                $stock_price_temp[$i]['upc'] = '';
                $stock_price_temp[$i]['asin'] = '';
                $stock_price_temp[$i]['location'] = $value['store_location'];
                $stock_price_temp[$i]['topurchase'] = '0';
                $stock_price_temp[$i]['backorder_percent'] = $data['data']['backorder_percent'];
                $stock_price_temp[$i]['backorder_weeks'] = $data['data']['backorder_day'];
                $stock_price_temp[$i]['leadtime'] = $data['data']['lead_time_days'];
                $stock_price_temp[$i]['instock_leadtime'] = $data['data']['in_stock_leadtime_days'];
                $stock_price_temp[$i]['cp'] = $value['cp'];
                $stock_price_temp[$i]['permanently_available'] = ($data['data']['permanently_available'] === 'Y') ? 1 : 0;
                $stock_price_temp[$i]['amazon_sold'] = '0';
                $stock_price_temp[$i]['amazon_leadtime'] = '10';
                $stock_price_temp[$i]['amazon_itemcode_alias'] = '';
                $stock_price_temp[$i]['youtube_links'] = '';
                $stock_price_temp[$i]['sketchfab_links'] = '';
            }
        }

        // 3. Assign item_stock_price to main array
        // array_values ensures JSON encodes this as a list [...] and not an object {"0":..}
        $API_data['item_stock_price'] = array_values($stock_price_temp);


        // 4. Handle Images (The major fix)
       
        $isVariant = $data['data']['is_variant']; // <-- FIX: Define $isVariant here    

        $images_payload = array();
        $img_directory = ($isVariant == 'N') ? ($data['data']['image_directory'] ?? '') : ''; 
        $images_payload['image_directory'] = $img_directory;
        $images_payload['images'] = array(); // Initialize as empty ARRAY, not string

        if (!empty($data['data']['img'])) {
            $images_payload['image_directory'] = $data['data']['image_directory'] ?? '';
            
            // WARNING: __DIR__ creates a server file path (e.g., /var/www/html/...). 
            // If you need a clickable URL for a browser, change this to your website URL.
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $domain = $_SERVER['HTTP_HOST'];

            $baseUrl = $protocol . '://' . $domain;
            $imgDir = $baseUrl.'/uploads/itm_img/'; 
            // 1. Get the list of filenames
            $raw_images = array_column($data['data']['img'], 'file_name');

            // 2. Concatenate $imgDir to each filename
            $images_payload['images'] = array_map(function($filename) use ($imgDir) {
                return $imgDir . $filename;
            }, $raw_images);
        }

        $API_data['images'] = $images_payload;

        $jsonString = json_encode($API_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); // Pretty print for easier reading
        echo "<pre>";print_r($jsonString);
        $apiurl =  '';
        
        $hasRows   = !empty($data['data']['var_rows']);
        $baseUrl   = 'https://www.exoticindia.com/vendor-api/product/create';

        $apiurl = ($isVariant == 'Y') 
            ? $baseUrl . '?new_variation=1'
            : $baseUrl;

        $url = $apiurl;
        $headers = [
            'x-api-key: K7mR9xQ3pL8vN2sF6wE4tY1uI0oP5aZ9',
            'x-adminapitest: 1',
            'Accept: application/json',
            'Content-Type: application/json',
            'User-Agent: VendorApiClient/1.0',
        ];

        $ch = curl_init();
        //echo $jsonString; // Debug: Output JSON payload
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            // Note: You have both GET and POST set. POST usually overrides GET, 
            // but it is safer to remove CURLOPT_HTTPGET if you are doing a POST.
            //CURLOPT_HTTPGET => true, 
            CURLOPT_POST => true,              
            CURLOPT_POSTFIELDS => $jsonString,
            CURLOPT_RETURNTRANSFER => true,
            
            // --- REDIRECT HANDLING ---
            //CURLOPT_FOLLOWLOCATION => true,
            //CURLOPT_MAXREDIRS => 5,         // Reduced from 10 to prevent infinite loops
            //CURLOPT_POSTREDIR => 3,         // Removed duplicate
            // ----------------------

            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false, // Disable if SSL issue occurs
        ]);

        $response = curl_exec($ch);
        
        // echo "<pre>";print_r($response);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            $error = "cURL Error: " . curl_error($ch);
            curl_close($ch);
            echo json_encode(['status' => 'error', 'message' => $error]);
            exit;
        }
        
        curl_close($ch);

        if ($httpCode != 200 && $httpCode != 201) {
            echo json_encode(['status' => 'error', 'message' => "API Error HTTP found.", 'debug' => $response]);
            exit;
        }

        header('Content-Type: application/json');

        if (empty($response) || trim($response) === '') {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Product Published Successfully!'
            ]);
        } else {
            echo $response; 
        }
        exit;
    }
}
?>