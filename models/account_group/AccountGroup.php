<?php

class AccountGroup
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getAccountGroups(int $page = 1, int $limit = 20, string $search = '', string $status = '', string $itemGroup = ''): array
    {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;
        $itemGroup = trim($itemGroup);

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $where[] = '(ag.account_group_name LIKE ? OR ag.id = ?)';
            $types .= 'si';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
        }

        if ($itemGroup !== '') {
            $where[] = 'ag.item_group = ?';
            $types .= 's';
            $params[] = $itemGroup;
        }

        if ($status === 'active') {
            $where[] = 'ag.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'ag.is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $countSql = 'SELECT COUNT(*) AS total FROM account_group ag' . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        if (!$countStmt) {
            return ['account_groups' => [], 'totalRecords' => 0, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = (int)(($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
        $countStmt->close();

        $sql = 'SELECT ag.id, ag.account_group_name, ag.item_group, ag.is_active, ag.created_at, ag.updated_at
                FROM account_group ag' . $whereSql . '
                ORDER BY ag.account_group_name ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['account_groups' => [], 'totalRecords' => $totalRecords, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }

        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $accountGroups = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $labelMap = $this->getItemGroupLabelMap();
        foreach ($accountGroups as &$group) {
            $stored = trim((string)($group['item_group'] ?? ''));
            $group['item_group_display'] = $stored !== ''
                ? ($labelMap[$stored] ?? $stored)
                : '';
        }
        unset($group);

        return [
            'account_groups' => $accountGroups,
            'totalRecords' => $totalRecords,
            'totalPages' => max(1, (int)ceil($totalRecords / $limit)),
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }

    public function getAccountGroupById(int $id): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT ag.id, ag.account_group_name, ag.item_group, ag.is_active, ag.created_at, ag.updated_at
             FROM account_group ag
             WHERE ag.id = ? LIMIT 1'
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

        $stored = trim((string)($row['item_group'] ?? ''));
        if ($stored !== '') {
            $labelMap = $this->getItemGroupLabelMap();
            $row['item_group_display'] = $labelMap[$stored] ?? $stored;
        } else {
            $row['item_group_display'] = '';
        }

        return $row;
    }

    public function getItemGroupLabelMap(): array
    {
        $map = [];
        foreach ($this->getParentItemGroups() as $row) {
            $name = (string)($row['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $map[$name] = (string)($row['display_name'] ?? $name);
        }

        return $map;
    }

    public function resolveItemGroupDisplay(?string $itemGroup, ?string $joinedLabel = null): string
    {
        $itemGroup = trim((string)$itemGroup);
        if ($itemGroup === '') {
            return '';
        }

        if ($joinedLabel !== null && trim($joinedLabel) !== '') {
            return trim($joinedLabel);
        }

        $map = $this->getItemGroupLabelMap();

        return $map[$itemGroup] ?? $itemGroup;
    }

    public function getParentItemGroups(): array
    {
        $stmt = $this->conn->prepare(
            'SELECT name, display_name
             FROM category
             WHERE parent_id = 0 AND is_active = 1
             ORDER BY display_name ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * Active account groups for a parent item group slug (category.name / account_group.item_group).
     *
     * @return list<array{id:int,account_group_name:string}>
     */
    public function getActiveByItemGroup(string $itemGroup): array
    {
        $itemGroup = trim($itemGroup);
        if ($itemGroup === '') {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT id, account_group_name
             FROM account_group
             WHERE is_active = 1
               AND LOWER(TRIM(COALESCE(item_group, \'\'))) = LOWER(TRIM(?))
             ORDER BY account_group_name ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $itemGroup);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function isValidItemGroup(?string $itemGroup): bool
    {
        if ($itemGroup === null || $itemGroup === '') {
            return true;
        }

        $stmt = $this->conn->prepare(
            'SELECT name FROM category WHERE name COLLATE utf8mb4_unicode_ci = ? AND parent_id = 0 AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $itemGroup);
        $stmt->execute();
        $stmt->store_result();
        $valid = $stmt->num_rows > 0;
        $stmt->close();

        return $valid;
    }

    public function accountGroupNameExists(string $name, ?int $excludeId = null): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT id FROM account_group WHERE LOWER(TRIM(account_group_name)) = LOWER(TRIM(?)) AND id != ? LIMIT 1'
            );
            $stmt->bind_param('si', $name, $excludeId);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT id FROM account_group WHERE LOWER(TRIM(account_group_name)) = LOWER(TRIM(?)) LIMIT 1'
            );
            $stmt->bind_param('s', $name);
        }
        if (!$stmt) {
            return false;
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function checkAccountGroupName(string $name, ?int $excludeId = null): array
    {
        return ['exists' => $this->accountGroupNameExists($name, $excludeId)];
    }

    public function saveAccountGroup(int $id, string $name, ?string $itemGroup, int $isActive): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        if ($name === '') {
            return ['success' => false, 'message' => 'Account group name is required.'];
        }
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Account group id is required for update.'];
        }
        if (!$this->isValidItemGroup($itemGroup)) {
            return ['success' => false, 'message' => 'Please select a valid item group.'];
        }
        if ($this->accountGroupNameExists($name, $id)) {
            return ['success' => false, 'message' => 'Account group name already exists'];
        }

        $stmt = $this->conn->prepare('UPDATE account_group SET account_group_name = ?, item_group = ?, is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ssii', $name, $itemGroup, $isActive, $id);

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save account group: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Account group saved successfully.', 'id' => $id]
            : ['success' => false, 'message' => 'Could not save account group: ' . $error];
    }

    public function insertAccountGroup(string $name, ?string $itemGroup, int $isActive): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        if ($name === '') {
            return ['success' => false, 'message' => 'Account group name is required.'];
        }
        if (!$this->isValidItemGroup($itemGroup)) {
            return ['success' => false, 'message' => 'Please select a valid item group.'];
        }
        if ($this->accountGroupNameExists($name)) {
            return ['success' => false, 'message' => 'Account group name already exists'];
        }

        $stmt = $this->conn->prepare('INSERT INTO account_group (account_group_name, item_group, is_active) VALUES (?, ?, ?)');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ssi', $name, $itemGroup, $isActive);

        try {
            $ok = $stmt->execute();
            $newId = (int)$this->conn->insert_id;
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save account group: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Account group saved successfully.', 'id' => $newId]
            : ['success' => false, 'message' => 'Could not save account group: ' . $error];
    }

    public function setStatus(int $id, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid account group id.'];
        }
        $isActive = $isActive ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE account_group SET is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $isActive, $id);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $isActive ? 'Account group activated.' : 'Account group deactivated.']
            : ['success' => false, 'message' => 'Could not update status: ' . $error];
    }

    public function deleteAccountGroup(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid account group id.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM account_group WHERE id = ?');
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
            return ['success' => false, 'message' => 'Could not delete account group. It may be in use elsewhere.'];
        }

        return $ok
            ? ['success' => true, 'message' => 'Account group deleted successfully.']
            : ['success' => false, 'message' => 'Could not delete account group: ' . $error];
    }
}
