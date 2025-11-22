 <?php
require_once 'models/inbounding/inbounding.php';

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
        
        renderTemplate('views/inbounding/index.php', $data, 'Manage Inbounding');
    }
    public function label($value=''){
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform2data($id);
        renderTemplate('views/inbounding/label.php', $data, 'label');
    }
    public function getform1() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = array();
        $data = $inboundingModel->getform1data($id);
        renderTemplate('views/inbounding/form1.php', $data, 'form1 inbounding');
    }
    public function getform2() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = $inboundingModel->getform1data($id);
        renderTemplate('views/inbounding/form2.php', $data, 'form2 inbounding');
    }
    public function getform3() {
        global $inboundingModel;
        $id = $_GET['id'] ?? 0;
        $data = $inboundingModel->getform2data($id);
        renderTemplate('views/inbounding/form3.php', $data, 'form3 inbounding');
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
            'invoice'    => $invoicePath
        ];

        $updated = $inboundingModel->updateform2($data);

        if ($updated) {
            header("location: " . base_url('?page=inbounding&action=form3&id='.$id));
            exit;
        } else {
            echo "Update failed.";
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
            'received_by_user_id'            => $received_by_user_id,
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
        renderTemplate('views/inbounding/edit.php', $data, 'Edit inbounding');
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