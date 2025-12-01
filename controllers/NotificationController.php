 <?php
require_once 'models/notifications/Notifications.php';

$notificationsModel = new Notifications($conn);

global $root_path;
global $domain;
class NotificationController {
    public function index() {
        is_login();
        global $notificationsModel;
        $search = isset($_GET['search_text']) ? trim($_GET['search_text']) : '';
        
        $page_no = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Users per page, default 5
        $limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 20; // If user select value from dropdown

        $nt_data = $notificationsModel->getAll($page_no, $limit, $search); 

        $data = [
            'notifications' => $nt_data["notifications"],
            'page_no' => $page_no,
            'total_pages' => $nt_data["totalPages"],
            'search' => $search,
            'totalPages'   => $nt_data["totalPages"],
            'currentPage'  => $nt_data["currentPage"],
            'limit'        => $limit,
            'totalRecords' => $nt_data["totalRecords"],
        ];
        
        renderTemplate('views/notifications/index.php', $data, 'Manage Notifications');
    }
    public function delete() {
        is_login();
        global $notificationsModel;
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id > 0) {
            $response = $notificationsModel->deleteRecord($id);
            echo json_encode($response);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid ID provided.']);
        }
        exit;
    }
    public function deleteAllNotifications() {
        is_login();
        global $notificationsModel;
        $user_id = $_SESSION["user"]["id"];

        if(!isset($user_id) || $user_id <= 0){
            echo json_encode(['success' => false, 'message' => 'Invalid User ID.']);
            exit;
        }
        $response = $notificationsModel->deleteAllNotifications($user_id);
        echo json_encode($response);
        exit;
    }
    public function fetchNotifications() {
        is_login();
        global $conn;
        $user_id = $_SESSION["user"]["id"];
        $sql = "SELECT * FROM vp_notifications WHERE user_id=$user_id AND is_read=0 ORDER BY id DESC";
        $result = $conn->query($sql);
        $notifications = [];
        if ($result->num_rows > 0) {
            while($row = mysqli_fetch_assoc($result)){
                $notifications[] = $row;
            }
        }
        echo json_encode($notifications);
        exit;
    }
    public function markAsRead() {
        global $conn;
        $ids = $_POST['ids'];  // array of ids from AJAX
        if (!empty($ids)) {
            $ids_str = implode(",", array_map('intval', $ids));

            $sql = "UPDATE vp_notifications SET is_read = 1, read_at=now() WHERE id IN ($ids_str)";
            mysqli_query($conn, $sql);
        }
        echo "success";
        exit;
    }
    public function fetchAllNotifications() {
        is_login();
        global $conn;
        $sql = "SELECT * FROM vp_notifications ORDER BY id DESC";
        $result = $conn->query($sql);
        $notifications = [];
        if ($result->num_rows > 0) {
            while($row = mysqli_fetch_assoc($result)){
                $notifications[] = $row;
            }
        }
        echo json_encode($notifications);
        exit;
    }
}