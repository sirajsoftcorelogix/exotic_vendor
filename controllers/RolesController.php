 <?php
require_once 'models/roles/Roles.php';

$rolesModel = new Roles($conn);

global $root_path;
global $domain;
class RolesController {
    public function __construct() {
        is_login();
    }

    public function index() {
        global $rolesModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $pt_data = $rolesModel->getAll($page_no, $limit, $search, $status_filter); 

        $data = [
            'roles' => $pt_data["roles"],
            'modules_str' => $pt_data["modules_str"],
            'page_no' => $page_no,
            'total_pages' => $pt_data["totalPages"],
            'search' => $search,
            'totalPages'   => $pt_data["totalPages"],
            'currentPage'  => $pt_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $pt_data["totalRecords"],
            'status_filter'=> $status_filter,
        ];
        renderTemplate('views/roles/index.php', $data, 'Manage Roles');
    }
    public function addRecord() {
        global $rolesModel;
        $data = $_POST;
        $result = $rolesModel->addRecord($data);      
        $_SESSION["role_message"] = $result['message'];
        header("location: " . base_url('?page=roles&action=list'));
        exit;
    }
    public function addRRecord() {
        global $rolesModel;
        $result = $rolesModel->getRecord(0);
        $data = [
            'roles' => $result["roles"],
            'modules_str' => $result["modules_str"],
        ];
        renderTemplate('views/roles/add.php', $data, 'Add Role');
    }
    public function editRecord() {
        global $rolesModel;
        $roleId = isset($_GET['role_id']) ? $_GET['role_id'] : 0;
        if (!$roleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Request.']);
            exit;
        }
        $data = $rolesModel->getRecord($roleId);
        $data = [
            'roles' => $data["roles"],
            'modules_str' => $data["modules_str"],
        ];
        renderTemplate('views/roles/edit.php', $data, 'Edit Role');
    }
    public function updateRecord() {
        global $rolesModel;
        $roleId = isset($_POST['role_id']) ? $_POST['role_id'] : 0;
        if (!$roleId) {
            echo json_encode(['success' => false, 'message' => 'Invalid Purchase Order ID.']);
            exit;
        }
        $data = $_POST;
        $result = $rolesModel->updateRecord($roleId, $data);
        $_SESSION["role_message"] = $result['message'];
        header("location: " . base_url('?page=roles&action=list'));
        exit;
    }
    public function delete() {
        global $rolesModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $result = $rolesModel->deleteRecord($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.'.$id]);
        }
        exit;
    }
    public function getDetails() {
        global $rolesModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $ptRecord = $rolesModel->getRecord($id);
            if ($ptRecord) {
                echo json_encode($ptRecord);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Record not found.']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid ID.']);
        }
        exit;
    }
}
?>