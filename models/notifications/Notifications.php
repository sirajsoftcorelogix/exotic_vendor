<?php
class Notifications {
    private $conn;
    public function __construct($db) {
        $this->conn = $db;
    }
    public function getAll($page = 1, $limit = 10, $search = '') {
		$page = (int)$page;
        if ($page < 1) $page = 1;

        $limit = (int)$limit;
        if ($limit < 1) $limit = 10;

        // calculate offset
        $offset = ($page - 1) * $limit;
		$where = "WHERE user_id = " . $_SESSION["user"]["id"];

        if (!empty($search)) {
            $search = $this->conn->real_escape_string($search);
            $where = "AND `message` LIKE('%$search%')";
        }

		// total records
        $resultCount = $this->conn->query("SELECT COUNT(*) AS total FROM vp_notifications $where");
        $rowCount = $resultCount->fetch_assoc();
        $totalRecords = $rowCount['total'];

        $totalPages = ceil($totalRecords / $limit);

        // fetch data
        $sql = "SELECT vn.*, vu.name FROM vp_notifications AS vn INNER JOIN vp_users AS vu ON vu.id = vn.user_id $where LIMIT $limit OFFSET $offset";
        $result = $this->conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }

        // return structured data
        return [
            'notifications' => $data,
            'totalPages'   => $totalPages,
            'currentPage'  => $page,
            'limit'        => $limit,
            'totalRecords' => $totalRecords,
            'search'       => $search
        ];
	}
    public function getUnreadCount($user_id) {
        $user_id = (int)$user_id;
        $sql = "SELECT COUNT(*) AS unread_count FROM vp_notifications WHERE user_id = ? AND is_read = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return (int)$row['unread_count'];
    }
    public function deleteRecord($id) {
        $sql = "DELETE FROM vp_notifications WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Record deleted successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Delete failed: ' . $stmt->error . '. Please try again later.'
        ];
    }
    public function deleteAllNotifications($user_id) {
        $sql = "DELETE FROM vp_notifications WHERE user_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'All notifications deleted successfully.'];
        }
        return [
            'success' => false,
            'message' => 'Delete failed: ' . $stmt->error . '. Please try again later.'
        ];
    }
}
?>