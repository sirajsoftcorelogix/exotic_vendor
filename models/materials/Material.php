<?php

class Material
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function slugify(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-') ?: 'material';
    }

    public function getNextDisplayOrder(): int
    {
        $sql = 'SELECT MAX(`display order`) AS max_val FROM material';
        $result = $this->conn->query($sql);
        if ($result && ($row = $result->fetch_assoc())) {
            return (int) ($row['max_val'] ?? 0) + 1;
        }
        return 1;
    }

    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $status_filter = ''): array
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
            $where[] = '(material_name LIKE ? OR material_slug LIKE ?)';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status_filter !== '' && $status_filter !== null) {
            $where[] = 'is_active = ?';
            $types .= 'i';
            $params[] = (int) $status_filter;
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM material $whereSql";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $countRes = $stmtCount->get_result()->fetch_assoc();
        $totalRecords = (int) ($countRes['total'] ?? 0);
        $stmtCount->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT m.*, u.name AS user_name
                FROM material m
                LEFT JOIN vp_users u ON u.id = m.user_id
                $whereSql
                ORDER BY m.`display order` ASC, m.material_name ASC, m.id ASC
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
            'materials' => $data,
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
            'SELECT id, material_name, material_slug, is_active, `display order` AS display_order, user_id, created_at, updated_at
             FROM material WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private function nameExists(string $name, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM material WHERE material_name = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $name, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function slugExists(string $slug, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM material WHERE material_slug = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $slug, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    public function addRecord(array $data, int $userId): array
    {
        $name = trim((string) ($data['addMaterialName'] ?? ''));
        $slug = trim((string) ($data['addMaterialSlug'] ?? ''));
        $displayOrder = (int) ($data['addDisplayOrder'] ?? 0);
        $isActive = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;

        if ($name === '') {
            return ['success' => false, 'message' => 'Material name is required.'];
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($this->nameExists($name)) {
            return ['success' => false, 'message' => 'Material name already exists.'];
        }

        if ($this->slugExists($slug)) {
            return ['success' => false, 'message' => 'Material slug already exists.'];
        }

        if ($displayOrder <= 0) {
            $displayOrder = $this->getNextDisplayOrder();
        }

        $isActive = $isActive ? 1 : 0;
        $userId = $userId > 0 ? $userId : 0;

        $sql = 'INSERT INTO material (material_name, material_slug, is_active, `display order`, user_id, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('ssiii', $name, $slug, $isActive, $displayOrder, $userId);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Material added successfully.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Could not save: ' . $err];
    }

    public function updateRecord(int $id, array $data): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid material id.'];
        }

        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Material not found.'];
        }

        $name = trim((string) ($data['editMaterialName'] ?? ''));
        $slug = trim((string) ($data['editMaterialSlug'] ?? ''));
        $displayOrder = (int) ($data['editDisplayOrder'] ?? 0);
        $isActive = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;

        if ($name === '') {
            return ['success' => false, 'message' => 'Material name is required.'];
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        } else {
            $slug = $this->slugify($slug);
        }

        if ($this->nameExists($name, $id)) {
            return ['success' => false, 'message' => 'Material name already exists.'];
        }

        if ($this->slugExists($slug, $id)) {
            return ['success' => false, 'message' => 'Material slug already exists.'];
        }

        if ($isActive === 0) {
            $block = $this->getDeactivateBlockReason($id);
            if ($block !== null) {
                return ['success' => false, 'message' => $block];
            }
        }

        $isActive = $isActive ? 1 : 0;

        $sql = 'UPDATE material SET material_name = ?, material_slug = ?, is_active = ?, `display order` = ?, updated_at = NOW() WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('ssiii', $name, $slug, $isActive, $displayOrder, $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Material updated successfully.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Could not update: ' . $err];
    }

    private function getDeactivateBlockReason(int $materialId): ?string
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_inbound WHERE material_code = ?');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $materialId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ((int) ($row['c'] ?? 0) > 0) {
            return 'Cannot deactivate: one or more inbound products use this material.';
        }
        return null;
    }

    public function deleteRecord(int $id): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid ID.'];
        }

        $block = $this->getDeactivateBlockReason($id);
        if ($block !== null) {
            return ['success' => false, 'message' => $block];
        }

        $sql = 'UPDATE material SET is_active = 0, updated_at = NOW() WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Material deactivated.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Update failed: ' . $err];
    }
}
