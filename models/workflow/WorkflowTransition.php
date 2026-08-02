<?php

class WorkflowTransition
{
    private mysqli $conn;

    public function __construct(mysqli $db)
    {
        $this->conn = $db;
    }

    public function isEnforcementActive(): bool
    {
        $res = $this->conn->query(
            'SELECT 1 FROM vp_workflow_transition WHERE is_active = 1 LIMIT 1'
        );

        return $res && $res->num_rows > 0;
    }

    public function getStatusIdBySlug(string $slug): int
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return 0;
        }

        $stmt = $this->conn->prepare(
            'SELECT id FROM vp_order_status WHERE slug = ? AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['id'] ?? 0);
    }

    public function getStatusTitleBySlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        if ($slug === '') {
            return '';
        }

        $stmt = $this->conn->prepare(
            'SELECT title FROM vp_order_status WHERE slug = ? LIMIT 1'
        );
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string) ($row['title'] ?? ''));
    }

    /**
     * @return list<array{id:int,title:string,slug:string,parent_id:int}>
     */
    public function getSelectableStatuses(): array
    {
        $sql = 'SELECT id, title, slug, parent_id
                FROM vp_order_status
                WHERE is_active = 1 AND parent_id != 0
                ORDER BY parent_id ASC, title ASC, id ASC';
        $res = $this->conn->query($sql);
        if (!$res) {
            return [];
        }

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    public function hasOutgoingTransitions(int $fromStatusId): bool
    {
        $fromStatusId = (int) $fromStatusId;
        if ($fromStatusId <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT 1 FROM vp_workflow_transition
             WHERE from_status_id = ? AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $fromStatusId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function hasActiveTransition(int $fromStatusId, int $toStatusId): bool
    {
        $fromStatusId = (int) $fromStatusId;
        $toStatusId = (int) $toStatusId;
        if ($fromStatusId <= 0 || $toStatusId <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT 1 FROM vp_workflow_transition
             WHERE from_status_id = ? AND to_status_id = ? AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ii', $fromStatusId, $toStatusId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    public function isTransitionAllowed(int $fromStatusId, int $toStatusId): bool
    {
        if ($fromStatusId === $toStatusId) {
            return true;
        }
        if ($fromStatusId > 0 && $this->isTerminalStatusId($fromStatusId)) {
            return false;
        }
        if ($fromStatusId <= 0 || $toStatusId <= 0) {
            return !$this->isEnforcementActive();
        }
        if (!$this->isEnforcementActive()) {
            return true;
        }
        if (!$this->hasOutgoingTransitions($fromStatusId)) {
            return true;
        }

        return $this->hasActiveTransition($fromStatusId, $toStatusId);
    }

    public function isTransitionAllowedBySlug(string $fromSlug, string $toSlug): bool
    {
        $fromSlug = strtolower(trim($fromSlug));
        $toSlug = strtolower(trim($toSlug));
        if ($fromSlug === $toSlug) {
            return true;
        }

        return $this->isTransitionAllowed(
            $this->getStatusIdBySlug($fromSlug),
            $this->getStatusIdBySlug($toSlug)
        );
    }

    /**
     * @return array{
     *   enforced:bool,
     *   filter_options:bool,
     *   allowed_slugs:list<string>,
     *   stock_affecting_slugs:list<string>
     * }
     */
    public function getAllowedTargetsForFromSlug(string $fromSlug): array
    {
        require_once dirname(__DIR__, 2) . '/helpers/order_status_stock.php';
        require_once dirname(__DIR__, 2) . '/helpers/order_workflow.php';

        $fromSlug = strtolower(trim($fromSlug));
        $empty = [
            'enforced' => false,
            'filter_options' => false,
            'allowed_slugs' => [],
            'stock_affecting_slugs' => [],
        ];
        if ($fromSlug === '') {
            return $empty;
        }

        if (is_order_workflow_terminal_status($fromSlug)) {
            return [
                'enforced' => true,
                'filter_options' => true,
                'allowed_slugs' => [$fromSlug],
                'stock_affecting_slugs' => [],
            ];
        }

        if (!$this->isEnforcementActive()) {
            return $empty;
        }

        $fromId = $this->getStatusIdBySlug($fromSlug);
        if ($fromId <= 0 || !$this->hasOutgoingTransitions($fromId)) {
            return [
                'enforced' => true,
                'filter_options' => false,
                'allowed_slugs' => [],
                'stock_affecting_slugs' => [],
            ];
        }

        $stmt = $this->conn->prepare(
            'SELECT os.slug
             FROM vp_workflow_transition wt
             INNER JOIN vp_order_status os ON os.id = wt.to_status_id
             WHERE wt.from_status_id = ? AND wt.is_active = 1 AND os.is_active = 1
             ORDER BY os.title ASC, os.id ASC'
        );
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('i', $fromId);
        $stmt->execute();
        $res = $stmt->get_result();

        $allowedSlugs = [];
        $stockSlugs = [];
        while ($row = $res->fetch_assoc()) {
            $slug = strtolower(trim((string) ($row['slug'] ?? '')));
            if ($slug === '') {
                continue;
            }
            $allowedSlugs[] = $slug;
            if (order_status_triggers_stock_restore($slug)) {
                $stockSlugs[] = $slug;
            }
        }
        $stmt->close();

        $allowedSlugs[] = $fromSlug;

        return [
            'enforced' => true,
            'filter_options' => true,
            'allowed_slugs' => array_values(array_unique($allowedSlugs)),
            'stock_affecting_slugs' => array_values(array_unique($stockSlugs)),
        ];
    }

    public function getAll(
        int $page = 1,
        int $limit = 20,
        string $search = '',
        string $statusFilter = '',
        int $fromFilter = 0,
        int $toFilter = 0
    ): array {
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
            $where[] = '(fs.title LIKE ? OR ts.title LIKE ? OR fs.slug LIKE ? OR ts.slug LIKE ?)';
            $types .= 'ssss';
            array_push($params, $like, $like, $like, $like);
        }

        if ($statusFilter !== '' && $statusFilter !== null) {
            $where[] = 'wt.is_active = ?';
            $types .= 'i';
            $params[] = (int) $statusFilter;
        }

        if ($fromFilter > 0) {
            $where[] = 'wt.from_status_id = ?';
            $types .= 'i';
            $params[] = $fromFilter;
        }

        if ($toFilter > 0) {
            $where[] = 'wt.to_status_id = ?';
            $types .= 'i';
            $params[] = $toFilter;
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total
                     FROM vp_workflow_transition wt
                     INNER JOIN vp_order_status fs ON fs.id = wt.from_status_id
                     INNER JOIN vp_order_status ts ON ts.id = wt.to_status_id
                     $whereSql";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $totalRecords = (int) ($stmtCount->get_result()->fetch_assoc()['total'] ?? 0);
        $stmtCount->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT wt.*,
                       fs.title AS from_title, fs.slug AS from_slug,
                       ts.title AS to_title, ts.slug AS to_slug,
                       u.name AS created_by_name
                FROM vp_workflow_transition wt
                INNER JOIN vp_order_status fs ON fs.id = wt.from_status_id
                INNER JOIN vp_order_status ts ON ts.id = wt.to_status_id
                LEFT JOIN vp_users u ON u.id = wt.created_by
                $whereSql
                ORDER BY fs.title ASC, ts.title ASC, wt.id ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        $typesLimit = $types . 'ii';
        $paramsLimit = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($typesLimit, ...$paramsLimit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'rows' => $rows ?: [],
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'currentPage' => $page,
        ];
    }

    public function getRecord(int $id): ?array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT wt.*, fs.title AS from_title, ts.title AS to_title
             FROM vp_workflow_transition wt
             INNER JOIN vp_order_status fs ON fs.id = wt.from_status_id
             INNER JOIN vp_order_status ts ON ts.id = wt.to_status_id
             WHERE wt.id = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string,id?:int}
     */
    public function addRecord(array $data): array
    {
        $fromId = (int) ($data['from_status_id'] ?? 0);
        $toId = (int) ($data['to_status_id'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;
        $createdBy = (int) ($data['created_by'] ?? 0);

        if ($fromId <= 0 || $toId <= 0) {
            return ['success' => false, 'message' => 'From and To status are required.'];
        }
        if ($fromId === $toId) {
            return ['success' => false, 'message' => 'From and To status must be different.'];
        }
        if (!$this->isLeafStatus($fromId) || !$this->isLeafStatus($toId)) {
            return ['success' => false, 'message' => 'Only child order statuses can be used in transitions.'];
        }

        $dup = $this->conn->prepare(
            'SELECT id FROM vp_workflow_transition WHERE from_status_id = ? AND to_status_id = ? LIMIT 1'
        );
        if (!$dup) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $dup->bind_param('ii', $fromId, $toId);
        $dup->execute();
        $existing = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($existing) {
            return ['success' => false, 'message' => 'This transition already exists.'];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_workflow_transition (from_status_id, to_status_id, is_active, created_by)
             VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to save transition.'];
        }
        $stmt->bind_param('iiii', $fromId, $toId, $isActive, $createdBy);
        $ok = $stmt->execute();
        $newId = (int) $stmt->insert_id;
        $stmt->close();

        if (!$ok) {
            return ['success' => false, 'message' => 'Failed to save transition.'];
        }

        return ['success' => true, 'message' => 'Transition added.', 'id' => $newId];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string}
     */
    public function updateRecord(int $id, array $data): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid transition ID.'];
        }

        $fromId = (int) ($data['from_status_id'] ?? 0);
        $toId = (int) ($data['to_status_id'] ?? 0);
        $isActive = isset($data['is_active']) ? (int) (bool) $data['is_active'] : 1;

        if ($fromId <= 0 || $toId <= 0) {
            return ['success' => false, 'message' => 'From and To status are required.'];
        }
        if ($fromId === $toId) {
            return ['success' => false, 'message' => 'From and To status must be different.'];
        }
        if (!$this->isLeafStatus($fromId) || !$this->isLeafStatus($toId)) {
            return ['success' => false, 'message' => 'Only child order statuses can be used in transitions.'];
        }

        $dup = $this->conn->prepare(
            'SELECT id FROM vp_workflow_transition
             WHERE from_status_id = ? AND to_status_id = ? AND id != ? LIMIT 1'
        );
        if (!$dup) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $dup->bind_param('iii', $fromId, $toId, $id);
        $dup->execute();
        $existing = $dup->get_result()->fetch_assoc();
        $dup->close();
        if ($existing) {
            return ['success' => false, 'message' => 'Another transition with the same From/To already exists.'];
        }

        $stmt = $this->conn->prepare(
            'UPDATE vp_workflow_transition
             SET from_status_id = ?, to_status_id = ?, is_active = ?
             WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to update transition.'];
        }
        $stmt->bind_param('iiii', $fromId, $toId, $isActive, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => 'Transition updated.']
            : ['success' => false, 'message' => 'Failed to update transition.'];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function setActive(int $id, bool $active): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid transition ID.'];
        }

        $flag = $active ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE vp_workflow_transition SET is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to update transition.'];
        }
        $stmt->bind_param('ii', $flag, $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $active ? 'Transition activated.' : 'Transition deactivated.']
            : ['success' => false, 'message' => 'Failed to update transition.'];
    }

    /**
     * @return array{success:bool,message:string}
     */
    public function deleteRecord(int $id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid transition ID.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM vp_workflow_transition WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Failed to delete transition.'];
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => 'Transition deleted.']
            : ['success' => false, 'message' => 'Failed to delete transition.'];
    }

    private function isLeafStatus(int $statusId): bool
    {
        $statusId = (int) $statusId;
        if ($statusId <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT id FROM vp_order_status WHERE id = ? AND parent_id != 0 AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $statusId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    private function isTerminalStatusId(int $statusId): bool
    {
        $statusId = (int) $statusId;
        if ($statusId <= 0) {
            return false;
        }

        require_once dirname(__DIR__, 2) . '/helpers/order_workflow.php';

        $stmt = $this->conn->prepare(
            'SELECT slug FROM vp_order_status WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $statusId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return is_order_workflow_terminal_status((string) ($row['slug'] ?? ''));
    }
}
