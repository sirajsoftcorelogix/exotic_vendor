<?php
class SavedSearch {
    private $conn;
    private $table = 'saved_searches';

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function add($data) {
        $sql = "INSERT INTO {$this->table} (user_id, page, name, `query`, created_at) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            return ['success' => false, 'error' => $this->conn->error];
        }
        $now = date('Y-m-d H:i:s');
        // types: i = user_id, s = page, s = name, s = query, s = created_at
        $stmt->bind_param('issss', $data['user_id'], $data['page'], $data['name'], $data['query'], $now);
        $ok = $stmt->execute();
        if ($ok) {
            return ['success' => true, 'insert_id' => $this->conn->insert_id];
        }
        return ['success' => false, 'error' => $stmt->error];
    }

    public function delete($id, $user_id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ? AND user_id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return false;
        $stmt->bind_param('ii', $id, $user_id);
        return $stmt->execute();
    }

    public function getByUser($user_id, $page = 'orders') {
        $sql = "SELECT id, user_id, page, name, `query`, created_at FROM {$this->table} WHERE user_id = ? AND page = ? ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return [];
        $stmt->bind_param('is', $user_id, $page);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    public function get($id, $user_id) {
        $sql = "SELECT id, user_id, page, name, `query`, created_at FROM {$this->table} WHERE id = ? AND user_id = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) return null;
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result ? $result->fetch_assoc() : null;
    }
}

