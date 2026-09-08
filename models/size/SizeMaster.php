<?php

require_once __DIR__ . '/../account_group/AccountGroup.php';

class SizeMaster
{
    private mysqli $conn;
    private AccountGroup $accountGroupModel;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->accountGroupModel = new AccountGroup($conn);
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->ensureModule();
        $this->conn->query(
            'CREATE TABLE IF NOT EXISTS size_master (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                item_group VARCHAR(120) NOT NULL,
                size_code VARCHAR(80) NOT NULL,
                size_label VARCHAR(255) NOT NULL,
                display_order INT NOT NULL DEFAULT 0,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_size_master_group_code (item_group, size_code),
                INDEX idx_size_master_group_active (item_group, is_active),
                INDEX idx_size_master_active (is_active),
                INDEX idx_size_master_display_order (display_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci'
        );
    }

    private function ensureModule(): void
    {
        $check = @$this->conn->query("SELECT id FROM modules WHERE slug = 'sizes' LIMIT 1");
        if ($check && $check->num_rows > 0) {
            return;
        }

        $parentId = 0;
        $parent = @$this->conn->query(
            "SELECT parent_id FROM modules
             WHERE slug IN ('materials', 'account_groups', 'languages') AND parent_id > 0
             ORDER BY id ASC LIMIT 1"
        );
        if ($parent && ($row = $parent->fetch_assoc())) {
            $parentId = (int) ($row['parent_id'] ?? 0);
        }
        if ($parentId <= 0) {
            $top = @$this->conn->query(
                "SELECT id FROM modules WHERE parent_id = 0 AND slug IN ('materials', 'account_groups') LIMIT 1"
            );
            if ($top && ($row = $top->fetch_assoc())) {
                $parentId = (int) ($row['id'] ?? 0);
            }
        }

        $icon = '<i class="fas fa-ruler-combined mr-2"></i>';
        $stmt = $this->conn->prepare(
            'INSERT INTO modules (parent_id, module_name, slug, `action`, font_awesome_icon, active, user_id, sort_order)
             VALUES (?, ?, ?, ?, ?, 1, 1, 220)'
        );
        if (!$stmt) {
            return;
        }
        $name = 'Sizes';
        $slug = 'sizes';
        $action = 'list';
        $stmt->bind_param('issss', $parentId, $name, $slug, $action, $icon);
        try {
            $stmt->execute();
        } catch (Throwable $e) {
            // Menu insert is best-effort.
        }
        $stmt->close();
    }

    /**
     * Lookup aliases so clothing/textiles (and jewelry/jewellery) share lists.
     *
     * @return array<string, list<string>>
     */
    public static function itemGroupAliasMap(): array
    {
        return [
            'textiles' => ['textiles', 'textile', 'clothing', 'clothes'],
            'textile' => ['textiles', 'textile', 'clothing', 'clothes'],
            'clothing' => ['clothing', 'clothes', 'textiles', 'textile'],
            'clothes' => ['clothing', 'clothes', 'textiles', 'textile'],
            'jewelry' => ['jewelry', 'jewellery'],
            'jewellery' => ['jewelry', 'jewellery'],
            'footwear' => ['footwear', 'shoes', 'shoe'],
            'shoes' => ['footwear', 'shoes', 'shoe'],
            'shoe' => ['footwear', 'shoes', 'shoe'],
        ];
    }

    /**
     * @return list<string>
     */
    public static function resolveItemGroupKeys(string $itemGroup): array
    {
        $itemGroup = strtolower(trim($itemGroup));
        if ($itemGroup === '') {
            return [];
        }

        $compact = preg_replace('/[^a-z0-9]/', '', $itemGroup) ?? $itemGroup;
        $map = self::itemGroupAliasMap();
        $keys = $map[$itemGroup] ?? $map[$compact] ?? [$itemGroup];
        if ($compact !== '' && $compact !== $itemGroup && !in_array($compact, $keys, true)) {
            $keys[] = $compact;
        }

        return array_values(array_unique($keys));
    }

    /**
     * Same parent groups as Account Group → Add Account Group → Item Group
     * (category.name / category.display_name where parent_id = 0).
     */
    public function getParentItemGroups(): array
    {
        return $this->accountGroupModel->getParentItemGroups();
    }

    public function getItemGroupLabelMap(): array
    {
        return $this->accountGroupModel->getItemGroupLabelMap();
    }

    public function isValidItemGroup(?string $itemGroup): bool
    {
        $itemGroup = trim((string) $itemGroup);
        if ($itemGroup === '') {
            return false;
        }

        return $this->accountGroupModel->isValidItemGroup($itemGroup);
    }

    public function getSizes(int $page = 1, int $limit = 20, string $search = '', string $status = '', string $itemGroup = ''): array
    {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;
        $itemGroup = trim($itemGroup);

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(sm.size_code LIKE ? OR sm.size_label LIKE ? OR sm.item_group LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($itemGroup !== '') {
            $where[] = 'sm.item_group = ?';
            $types .= 's';
            $params[] = $itemGroup;
        }

        if ($status === 'active') {
            $where[] = 'sm.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'sm.is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $countSql = 'SELECT COUNT(*) AS total FROM size_master sm' . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        if (!$countStmt) {
            return ['sizes' => [], 'totalRecords' => 0, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = (int) (($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
        $countStmt->close();

        $sql = 'SELECT sm.id, sm.item_group, sm.size_code, sm.size_label, sm.display_order, sm.is_active,
                       sm.user_id, sm.created_at, sm.updated_at, u.name AS user_name
                FROM size_master sm
                LEFT JOIN vp_users u ON u.id = sm.user_id'
            . $whereSql
            . ' ORDER BY sm.item_group ASC, sm.display_order ASC, sm.size_code ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['sizes' => [], 'totalRecords' => $totalRecords, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }

        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $sizes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $labelMap = $this->getItemGroupLabelMap();
        foreach ($sizes as &$row) {
            $stored = trim((string) ($row['item_group'] ?? ''));
            $row['item_group_display'] = $stored !== ''
                ? ($labelMap[$stored] ?? $stored)
                : '';
        }
        unset($row);

        return [
            'sizes' => $sizes,
            'totalRecords' => $totalRecords,
            'totalPages' => max(1, (int) ceil($totalRecords / $limit)),
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }

    public function getRecord(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare(
            'SELECT id, item_group, size_code, size_label, display_order, is_active, user_id, created_at, updated_at
             FROM size_master WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$row) {
            return null;
        }

        $stored = trim((string) ($row['item_group'] ?? ''));
        $labelMap = $this->getItemGroupLabelMap();
        $row['item_group_display'] = $stored !== '' ? ($labelMap[$stored] ?? $stored) : '';

        return $row;
    }

    /**
     * Active sizes as code => label for inbound dropdowns.
     *
     * @return array<string, string>
     */
    public function getActiveOptionsForItemGroup(string $itemGroup): array
    {
        $keys = self::resolveItemGroupKeys($itemGroup);
        if ($keys === []) {
            return [];
        }

        $grouped = $this->getActiveGroupedByItemGroup();
        foreach ($keys as $key) {
            if (!empty($grouped[$key])) {
                return $grouped[$key];
            }
        }

        return [];
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function getActiveGroupedByItemGroup(): array
    {
        $stmt = $this->conn->prepare(
            'SELECT item_group, size_code, size_label
             FROM size_master
             WHERE is_active = 1
             ORDER BY item_group ASC, display_order ASC, size_code ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $group = strtolower(trim((string) ($row['item_group'] ?? '')));
            $code = trim((string) ($row['size_code'] ?? ''));
            if ($group === '' || $code === '') {
                continue;
            }
            $grouped[$group][$code] = (string) ($row['size_label'] ?? $code);
        }
        $stmt->close();

        return $grouped;
    }

    public function getNextDisplayOrder(string $itemGroup = ''): int
    {
        $itemGroup = trim($itemGroup);
        if ($itemGroup !== '') {
            $stmt = $this->conn->prepare('SELECT MAX(display_order) AS max_val FROM size_master WHERE item_group = ?');
            if ($stmt) {
                $stmt->bind_param('s', $itemGroup);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                return (int) ($row['max_val'] ?? 0) + 10;
            }
        }

        $res = $this->conn->query('SELECT MAX(display_order) AS max_val FROM size_master');
        if ($res && ($row = $res->fetch_assoc())) {
            return (int) ($row['max_val'] ?? 0) + 10;
        }

        return 10;
    }

    public function sizeCodeExists(string $itemGroup, string $sizeCode, ?int $excludeId = null): bool
    {
        $itemGroup = trim($itemGroup);
        $sizeCode = trim($sizeCode);
        if ($itemGroup === '' || $sizeCode === '') {
            return false;
        }

        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT id FROM size_master
                 WHERE LOWER(TRIM(item_group)) = LOWER(TRIM(?))
                   AND LOWER(TRIM(size_code)) = LOWER(TRIM(?))
                   AND id != ?
                 LIMIT 1'
            );
            $stmt->bind_param('ssi', $itemGroup, $sizeCode, $excludeId);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT id FROM size_master
                 WHERE LOWER(TRIM(item_group)) = LOWER(TRIM(?))
                   AND LOWER(TRIM(size_code)) = LOWER(TRIM(?))
                 LIMIT 1'
            );
            $stmt->bind_param('ss', $itemGroup, $sizeCode);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function checkSizeCode(string $itemGroup, string $sizeCode, ?int $excludeId = null): array
    {
        return ['exists' => $this->sizeCodeExists($itemGroup, $sizeCode, $excludeId)];
    }

    public function insertSize(string $itemGroup, string $sizeCode, string $sizeLabel, int $displayOrder, int $isActive, int $userId = 0): array
    {
        $itemGroup = trim($itemGroup);
        $sizeCode = trim($sizeCode);
        $sizeLabel = trim($sizeLabel);
        $isActive = $isActive ? 1 : 0;

        if ($itemGroup === '') {
            return ['success' => false, 'message' => 'Item group is required.'];
        }
        if (!$this->isValidItemGroup($itemGroup)) {
            return ['success' => false, 'message' => 'Please select a valid item group.'];
        }
        if ($sizeCode === '') {
            return ['success' => false, 'message' => 'Size code is required.'];
        }
        if ($sizeLabel === '') {
            return ['success' => false, 'message' => 'Size label is required.'];
        }
        if ($this->sizeCodeExists($itemGroup, $sizeCode)) {
            return ['success' => false, 'message' => 'This size code already exists for the selected group.'];
        }
        if ($displayOrder <= 0) {
            $displayOrder = $this->getNextDisplayOrder($itemGroup);
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO size_master (item_group, size_code, size_label, display_order, is_active, user_id)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('sssiii', $itemGroup, $sizeCode, $sizeLabel, $displayOrder, $isActive, $userId);

        try {
            $ok = $stmt->execute();
            $newId = (int) $this->conn->insert_id;
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save size: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Size added successfully.', 'id' => $newId]
            : ['success' => false, 'message' => 'Could not save size: ' . $error];
    }

    public function updateSize(int $id, string $itemGroup, string $sizeCode, string $sizeLabel, int $displayOrder, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid size id.'];
        }
        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Size not found.'];
        }

        $itemGroup = trim($itemGroup);
        $sizeCode = trim($sizeCode);
        $sizeLabel = trim($sizeLabel);
        $isActive = $isActive ? 1 : 0;

        if ($itemGroup === '') {
            return ['success' => false, 'message' => 'Item group is required.'];
        }
        if (!$this->isValidItemGroup($itemGroup)) {
            return ['success' => false, 'message' => 'Please select a valid item group.'];
        }
        if ($sizeCode === '') {
            return ['success' => false, 'message' => 'Size code is required.'];
        }
        if ($sizeLabel === '') {
            return ['success' => false, 'message' => 'Size label is required.'];
        }
        if ($this->sizeCodeExists($itemGroup, $sizeCode, $id)) {
            return ['success' => false, 'message' => 'This size code already exists for the selected group.'];
        }
        if ($displayOrder <= 0) {
            $displayOrder = $this->getNextDisplayOrder($itemGroup);
        }

        $stmt = $this->conn->prepare(
            'UPDATE size_master
             SET item_group = ?, size_code = ?, size_label = ?, display_order = ?, is_active = ?
             WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('sssiii', $itemGroup, $sizeCode, $sizeLabel, $displayOrder, $isActive, $id);

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save size: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Size updated successfully.', 'id' => $id]
            : ['success' => false, 'message' => 'Could not save size: ' . $error];
    }

    public function setStatus(int $id, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid size id.'];
        }
        $isActive = $isActive ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE size_master SET is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $isActive, $id);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $isActive ? 'Size activated.' : 'Size deactivated.']
            : ['success' => false, 'message' => 'Could not update status: ' . $error];
    }

    public function deleteSize(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid size id.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM size_master WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $id);
        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not delete size.'];
        }

        return $ok
            ? ['success' => true, 'message' => 'Size deleted successfully.']
            : ['success' => false, 'message' => 'Could not delete size: ' . $error];
    }
}
