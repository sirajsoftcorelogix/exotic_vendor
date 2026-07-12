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

    public function countOrdersUsingStatusSlug(string $slug): int
    {
        $slug = trim($slug);
        if ($slug === '') {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS c
                FROM vp_orders
                WHERE LOWER(TRIM(status)) = LOWER(TRIM(?))';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * When a status slug is renamed, keep vp_orders.status in sync (matched by slug, not id).
     */
    public function syncVpOrdersStatusSlug(string $oldSlug, string $newSlug): int
    {
        $oldSlug = trim($oldSlug);
        $newSlug = trim($newSlug);

        if ($oldSlug === '' || $newSlug === '' || strcasecmp($oldSlug, $newSlug) === 0) {
            return 0;
        }

        $sql = 'UPDATE vp_orders SET status = ? WHERE LOWER(TRIM(status)) = LOWER(TRIM(?))';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('ss', $newSlug, $oldSlug);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        return max(0, (int) $affected);
    }

    public function countOrdersUsingChildStatusSlugs(int $parentId): int
    {
        if ($parentId <= 0) {
            return 0;
        }

        $sql = 'SELECT COUNT(*) AS c
                FROM vp_orders o
                INNER JOIN vp_order_status s ON LOWER(TRIM(o.status)) = LOWER(TRIM(s.slug))
                WHERE s.parent_id = ?';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $parentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array<int, array{slug:string,title:string,order_count:int}>
     */
    public function getChildrenUsedInVpOrders(int $parentId): array
    {
        if ($parentId <= 0) {
            return [];
        }

        $sql = 'SELECT s.slug, s.title, COUNT(o.id) AS order_count
                FROM vp_order_status s
                INNER JOIN vp_orders o ON LOWER(TRIM(o.status)) = LOWER(TRIM(s.slug))
                WHERE s.parent_id = ?
                GROUP BY s.id, s.slug, s.title
                ORDER BY s.title ASC, s.slug ASC';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $parentId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = [
                'slug' => (string) ($row['slug'] ?? ''),
                'title' => (string) ($row['title'] ?? ''),
                'order_count' => (int) ($row['order_count'] ?? 0),
            ];
        }
        $stmt->close();

        return $rows;
    }

    public function countChildStatuses(int $id): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_order_status WHERE parent_id = ?');
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array{
     *   order_count:int,
     *   child_count:int,
     *   child_order_count:int,
     *   in_use:bool,
     *   used_in_vp_orders:bool,
     *   children_in_vp_orders:array<int, array{slug:string,title:string,order_count:int}>
     * }
     */
    public function getUsage(int $id): array
    {
        $record = $this->getRecord($id);
        if (!$record) {
            return [
                'order_count' => 0,
                'child_count' => 0,
                'child_order_count' => 0,
                'in_use' => false,
                'used_in_vp_orders' => false,
                'children_in_vp_orders' => [],
            ];
        }

        $orderCount = $this->countOrdersUsingStatusSlug((string) ($record['slug'] ?? ''));
        $childCount = $this->countChildStatuses($id);
        $childrenInVpOrders = $this->getChildrenUsedInVpOrders($id);
        $childOrderCount = $this->countOrdersUsingChildStatusSlugs($id);
        $usedInVpOrders = $orderCount > 0 || $childOrderCount > 0;

        return [
            'order_count' => $orderCount,
            'child_count' => $childCount,
            'child_order_count' => $childOrderCount,
            'in_use' => $usedInVpOrders || $childCount > 0,
            'used_in_vp_orders' => $usedInVpOrders,
            'children_in_vp_orders' => $childrenInVpOrders,
        ];
    }

    private function getVpOrdersBlockReason(int $id, string $actionLabel = 'delete'): ?string
    {
        $record = $this->getRecord($id);
        if (!$record) {
            return null;
        }

        $slug = (string) ($record['slug'] ?? '');
        $orderCount = $this->countOrdersUsingStatusSlug($slug);
        if ($orderCount > 0) {
            $noun = $orderCount === 1 ? 'order' : 'orders';
            return sprintf(
                'Cannot %s: status slug "%s" is used on %d %s in vp_orders.status.',
                $actionLabel,
                $slug,
                $orderCount,
                $noun
            );
        }

        $childrenInUse = $this->getChildrenUsedInVpOrders($id);
        if (!empty($childrenInUse)) {
            $parts = [];
            foreach ($childrenInUse as $child) {
                $noun = $child['order_count'] === 1 ? 'order' : 'orders';
                $label = $child['title'] !== '' ? $child['title'] : $child['slug'];
                $parts[] = sprintf('"%s" (%s) on %d %s', $label, $child['slug'], $child['order_count'], $noun);
            }

            return sprintf(
                'Cannot %s: child status slug(s) under this group are used in vp_orders.status: %s.',
                $actionLabel,
                implode('; ', $parts)
            );
        }

        return null;
    }

    private function getChildStatusBlockReason(int $id, string $actionLabel = 'delete'): ?string
    {
        $childCount = $this->countChildStatuses($id);
        if ($childCount <= 0) {
            return null;
        }

        $noun = $childCount === 1 ? 'child status' : 'child statuses';

        return sprintf(
            'Cannot %s: this group still has %d %s. Delete or reassign child statuses first.',
            $actionLabel,
            $childCount,
            $noun
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

        if ($isActive === 0) {
            $ordersBlock = $this->getVpOrdersBlockReason($id, 'deactivate');
            if ($ordersBlock !== null) {
                return ['success' => false, 'message' => $ordersBlock, 'usage' => $this->getUsage($id)];
            }
        }

        $isActive = $isActive ? 1 : 0;
        $oldSlug = (string) ($existing['slug'] ?? '');
        $slugChanged = strcasecmp($oldSlug, $slug) !== 0;
        $syncedOrders = 0;

        $this->conn->begin_transaction();

        try {
            if ($slugChanged) {
                $syncedOrders = $this->syncVpOrdersStatusSlug($oldSlug, $slug);
            }

            $sql = 'UPDATE vp_order_status SET title = ?, slug = ?, parent_id = ?, admin_id = ?, is_active = ? WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('Database error: ' . $this->conn->error);
            }

            $stmt->bind_param('ssiiii', $title, $slug, $parentId, $adminId, $isActive, $id);
            if (!$stmt->execute()) {
                $err = $stmt->error;
                $stmt->close();
                throw new RuntimeException('Could not update: ' . $err);
            }
            $stmt->close();

            $this->conn->commit();

            $message = 'Order status updated successfully.';
            if ($slugChanged && $syncedOrders > 0) {
                $noun = $syncedOrders === 1 ? 'order' : 'orders';
                $message .= ' ' . $syncedOrders . ' ' . $noun . ' in vp_orders.status were updated to the new slug.';
            }

            return ['success' => true, 'message' => $message, 'synced_orders' => $syncedOrders];
        } catch (Throwable $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
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

        $ordersBlock = $this->getVpOrdersBlockReason($id, 'deactivate');
        if ($ordersBlock !== null) {
            return ['success' => false, 'message' => $ordersBlock, 'usage' => $this->getUsage($id)];
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

        $ordersBlock = $this->getVpOrdersBlockReason($id, 'delete');
        if ($ordersBlock !== null) {
            return ['success' => false, 'message' => $ordersBlock, 'usage' => $usage];
        }

        $childBlock = $this->getChildStatusBlockReason($id, 'delete');
        if ($childBlock !== null) {
            return ['success' => false, 'message' => $childBlock, 'usage' => $usage];
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
