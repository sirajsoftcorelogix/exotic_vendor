 <?php
require_once 'models/payment_terms/PaymentTerms.php';

$paymentTermsModel = new PaymentTerms($conn);

global $root_path;
global $domain;
class NotificationController {

    public function fetchNotifications() {
        is_login();
        global $conn;
        $user_id = 13; //$_SESSION["user"]["id"]; // dynamically from session

        $sql = "SELECT * FROM vp_notifications WHERE user_id=$user_id AND is_read=0 ORDER BY id DESC";
        $result = mysqli_query($conn, $sql);

        $notifications = [];
        while($row = mysqli_fetch_assoc($result)){
            $notifications[] = $row;
        }

        echo json_encode($notifications);
    }
    public function markAsRead() {
        global $conn;
        $ids = $_POST['ids'];  // array of ids from AJAX

        if (!empty($ids)) {
            $ids_str = implode(",", array_map('intval', $ids));

            $sql = "UPDATE vp_notifications SET is_read=1 WHERE id IN ($ids_str)";
            mysqli_query($conn, $sql);
        }

        echo "success";
    }
}