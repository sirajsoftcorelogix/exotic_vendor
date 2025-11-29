 <?php
require_once 'models/modules/Modules.php';

$modulesModel = new Modules($conn);

global $root_path;
global $domain;
class ModulesController {

    public function index() {
        is_login();
        global $modulesModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $pt_data = $modulesModel->getAll($page_no, $limit, $search, $status_filter);
        $p_menus = $modulesModel->getAllParentMenus();
        $data = [
            'modules_data' => $pt_data["modules"],
            'parent_menus' => $p_menus,
            'page_no' => $page_no,
            'total_pages' => $pt_data["totalPages"],
            'search' => $search,
            'totalPages'   => $pt_data["totalPages"],
            'currentPage'  => $pt_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $pt_data["totalRecords"],
            'status_filter'=> $status_filter,
        ];
        
        renderTemplate('views/modules/index.php', $data, 'Manage Modules');
    }
    public function addRecord() {
        is_login();
        global $modulesModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if ($id > 0) {
                $result = $modulesModel->updateRecord($id, $data);
            } else {
                $result = $modulesModel->addRecord($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function delete() {
        global $modulesModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $result = $modulesModel->deleteRecord($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.'.$id]);
        }
        exit;
    }
    public function getDetails() {
        global $modulesModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $ptRecord = $modulesModel->getRecord($id);
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