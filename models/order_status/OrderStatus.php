<?php

class OrderStatus
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function slugify(string $value): string
    {
        $slug = strtolower(trim($value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'status';
    }

    public function getParentGroups(int $exceptId = 0): array
    {
        $sql = 'SELECT id, title, slug FROM vp_order_status WHERE parent_id = 0';
        if ($exceptId > 0) {
            $sql .= ' AND id != ?';
        }
        $sql .= ' ORDER BY title ASC, id ASC';

        $stmt = $this->conn->prepare($sql);
        if ($exceptId > 0) {
            $stmt->bind_param('i', $exceptId);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return $data;
    }

    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $status_filter = '', string $parent_filter = ''): array
    {
        if ($page < 1) {
            $page = 1;
        }
        if ($limit < 1) {
            $limit = 20;
        }

        $offset = ($page - 1) * $limit;
        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(os.title LIKE ? OR os.slug LIKE ?)';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status_filter !== '' && $status_filter !== null) {
            $where[] = 'os.is_active = ?';
            $types .= 'i';
            $params[] = (int) $status_filter;
        }

        if ($parent_filter !== '' && $parent_filter !== null) {
            if ($parent_filter === '0') {
                $where[] = 'os.parent_id = 0';
            } else {
                $where[] = 'os.parent_id = ?';
                $types .= 'i';
                $params[] = (int) $parent_filter;
            }
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM vp_order_status os $whereSql";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $countRes = $stmtCount->get_result()->fetch_assoc();
        $totalRecords = (int) ($countRes['total'] ?? 0);
        $stmtCount->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT os.*, parent.title AS parent_title
                FROM vp_order_status os
                LEFT JOIN vp_order_status parent ON parent.id = os.parent_id
                $whereSql
                ORDER BY os.parent_id ASC, os.id ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        if ($types !== '') {
            $types2 = $types . 'ii';
            $stmt->bind_param($types2, ...array_merge($params, [$limit, $offset]));
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $stmt->close();

        return [
            'rows' => $data,
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => $totalRecords,
            'search' => $search,
        ];
    }

    public function getRecord(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT id, title, slug, parent_id, admin_id, is_active
             FROM vp_order_status WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private function slugExists(string $slug, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM vp_order_status WHERE slug = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $slug, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function titleExists(string $title, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM vp_order_status WHERE title = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $title, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function adminIdExists(int $adminId, int $exceptId = 0): bool
    {
        if ($adminId <= 0) {
            return false;
        }

        $sql = 'SELECT id FROM vp_order_status WHERE admin_id = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('ii', $adminId, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function validateParentId(int $parentId, int $recordId = 0): ?string
    {
        if ($parentId < 0) {
            return 'Invalid parent group.';
        }

        if ($parentId === 0) {
            return null;
        }

        if ($recordId > 0 && $parentId === $recordId) {
            return 'A status cannot be its own parent.';
        }

        $stmt = $this->conn->prepare('SELECT id, parent_id FROM vp_order_status WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $parentId);
        $stmt->execute();
        $parent = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$parent) {
            return 'Selected parent group was not found.';
        }

        if ((int) ($parent['parent_id'] ?? -1) !== 0) {
            return 'Parent must be a top-level group.';
        }

        return null;
    }

    /**
     * @return array{order_count:int,child_count:int,in_use:bool}
     */
    public function getUsage(int $id): array
    {
        $record = $this->getRecord($id);
        if (!$record) {
            return ['order_count' => 0, 'child_count' => 0, 'in_use' => false];
        }

        $orderCount = 0;
        $childCount = 0;

        $stmtOrders = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_orders WHERE status = ?');
        if ($stmtOrders) {
            $slug = (string) $record['slug'];
            $stmtOrders->bind_param('s', $slug);
            $stmtOrders->execute();
            $row = $stmtOrders->get_result()->fetch_assoc();
            $stmtOrders->close();
            $orderCount = (int) ($row['c'] ?? 0);
        }

        $stmtChildren = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_order_status WHERE parent_id = ?');
        if ($stmtChildren) {
            $stmtChildren->bind_param('i', $id);
            $stmtChildren->execute();
            $row = $stmtChildren->get_result()->fetch_assoc();
            $stmtChildren->close();
            $childCount = (int) ($row['c'] ?? 0);
        }

        return [
            'order_count' => $orderCount,
            'child_count' => $childCount,
            'in_use' => $orderCount > 0 || $childCount > 0,
        ];
    }

    private function getUsageBlockReason(int $id, string $actionLabel = 'modify'): ?string
    {
        $usage = $this->getUsage($id);
        if (!$usage['in_use']) {
            return null;
        }

        $parts = [];
        if ($usage['order_count'] > 0) {
            $noun = $usage['order_count'] === 1 ? 'order' : 'orders';
            $parts[] = $usage['order_count'] . ' ' . $noun;
        }
        if ($usage['child_count'] > 0) {
            $noun = $usage['child_count'] === 1 ? 'child status' : 'child statuses';
            $parts[] = $usage['child_count'] . ' ' . $noun;
        }

        return sprintf(
            'Cannot %s: this record is linked to %s.',
            $actionLabel,
            implode(' and ', $parts)
        );
    }

    public function addRecord(array $data): array
    {
        $title = trim((string) ($data['addTitle'] ?? ''));
        $slug = trim((string) ($data['addSlug'] ?? ''));
        $parentId = isset($data['addParentId']) ? (int) $data['addParentId'] : 0;
        $adminId = isset($data['addAdminId']) ? (int) $data['addAdminId'] : 0;
        $isActive = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;

        if ($title === '') {
            return ['success' => false, 'message' => 'Title is required.'];
        }

        if ($slug === '') {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($this->titleExists($title)) {
            return ['success' => false, 'message' => 'Title already exists.'];
        }

        if ($this->slugExists($slug)) {
            return ['success' => false, 'message' => 'Slug already exists.'];
        }

        if ($this->adminIdExists($adminId)) {
            return ['success' => false, 'message' => 'Admin ID already exists on another status.'];
        }

        $parentError = $this->validateParentId($parentId);
        if ($parentError !== null) {
            return ['success' => false, 'message' => $parentError];
        }

        if ($parentId === 0 && $adminId > 0) {
            return ['success' => false, 'message' => 'Top-level groups should use Admin ID 0.'];
        }

        $isActive = $isActive ? 1 : 0;

        $sql = 'INSERT INTO vp_order_status (title, slug, parent_id, admin_id, is_active) VALUES (?, ?, ?, ?, ?)';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }

        $stmt->bind_param('ssiii', $title, $slug, $parentId, $adminId, $isActive);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Order status added successfully.'];
        }

        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Could not save: ' . $err];
    }

    public function updateRecord(int $id, array $data): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid record id.'];
        }

        $existing = $this->getRecord($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        $title = trim((string) ($data['editTitle'] ?? ''));
        $slug = trim((string) ($data['editSlug'] ?? ''));
        $parentId = isset($data['editParentId']) ? (int) $data['editParentId'] : 0;
        $adminId = isset($data['editAdminId']) ? (int) $data['editAdminId'] : 0;
        $isActive = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;

        if ($title === '') {
            return ['success' => false, 'message' => 'Title is required.'];
        }

        if ($slug === '') {
            $slug = $this->slugify($title);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($this->titleExists($title, $id)) {
            return ['success' => false, 'message' => 'Title already exists.'];
        }

        if ($this->slugExists($slug, $id)) {
            return ['success' => false, 'message' => 'Slug already exists.'];
        }

        if ($this->adminIdExists($adminId, $id)) {
            return ['success' => false, 'message' => 'Admin ID already exists on another status.'];
        }

        $parentError = $this->validateParentId($parentId, $id);
        if ($parentError !== null) {
            return ['success' => false, 'message' => $parentError];
        }

        if ($parentId === 0 && $adminId > 0) {
            return ['success' => false, 'message' => 'Top-level groups should use Admin ID 0.'];
        }

        $usage = $this->getUsage($id);
        if ($usage['child_count'] > 0 && $parentId !== 0) {
            return ['success' => false, 'message' => 'Cannot move a group under another group while it has child statuses.'];
        }

        if ($slug !== (string) $existing['slug'] && $usage['order_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot change slug while orders still use the current slug.'];
        }

        if ($isActive === 0) {
            $block = $this->getUsageBlockReason($id, 'deactivate');
            if ($block !== null) {
                return ['success' => false, 'message' => $block, 'usage' => $usage];
            }
        }

        $isActive = $isActive ? 1 : 0;

        $sql = 'UPDATE vp_order_status SET title = ?, slug = ?, parent_id = ?, admin_id = ?, is_active = ? WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }

        $stmt->bind_param('ssiiii', $title, $slug, $parentId, $adminId, $isActive, $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Order status updated successfully.'];
        }

        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Could not update: ' . $err];
    }

    public function deleteRecord(int $id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid ID.'];
        }

        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        $block = $this->getUsageBlockReason($id, 'deactivate');
        if ($block !== null) {
            return ['success' => false, 'message' => $block, 'usage' => $this->getUsage($id)];
        }

        $sql = 'UPDATE vp_order_status SET is_active = 0 WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Order status deactivated.'];
        }

        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Update failed: ' . $err];
    }

    public function permanentlyDeleteRecord(int $id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid ID.'];
        }

        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Record not found.'];
        }

        $usage = $this->getUsage($id);
        if ($usage['in_use']) {
            $block = $this->getUsageBlockReason($id, 'delete');
            return ['success' => false, 'message' => $block, 'usage' => $usage];
        }

        $sql = 'DELETE FROM vp_order_status WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }

        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            return ['success' => true, 'message' => 'Order status deleted permanently.'];
        }

        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Delete failed: ' . ($err ?: 'No rows removed.')];
    }
}
