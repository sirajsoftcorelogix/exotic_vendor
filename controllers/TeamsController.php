 <?php
require_once 'models/teams/Teams.php';

$teamsModel = new Teams($conn);

global $root_path;
global $domain;
class TeamsController {

    public function index() {
        is_login();
        global $teamsModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        $status_filter = isset($_GET['status_filter']) ? trim($_GET['status_filter']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $pt_data = $teamsModel->getAll($page_no, $limit, $search, $status_filter); 

        $data = [
            'teams_data' => $pt_data["teams"],
            'page_no' => $page_no,
            'total_pages' => $pt_data["totalPages"],
            'search' => $search,
            'totalPages'   => $pt_data["totalPages"],
            'currentPage'  => $pt_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $pt_data["totalRecords"],
            'status_filter'=> $status_filter,
        ];
        
        renderTemplate('views/teams/index.php', $data, 'Manage Teams');
    }
    public function addRecord() {
        is_login();
        global $teamsModel;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = $_POST;
            $id = isset($data['id']) ? (int)$data['id'] : 0;
            if ($id > 0) {
                $result = $teamsModel->updateRecord($id, $data);
            } else {
                $result = $teamsModel->addRecord($data);            
            }
            echo json_encode($result);
        }
        exit;
    }
    public function delete() {
        global $teamsModel;
        // Try to get id from JSON or POST
        $data = json_decode(file_get_contents('php://input'), true);
        $id = isset($data['id']) ? (int)$data['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
        if ($id > 0) {
            $result = $teamsModel->deleteRecord($id);
            echo json_encode($result);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID.'.$id]);
        }
        exit;
    }
    public function getDetails() {
        global $teamsModel;
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id > 0) {
            $ptRecord = $teamsModel->getRecord($id);
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