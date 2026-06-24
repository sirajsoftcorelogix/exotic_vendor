<?php

class Language
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
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
            $where[] = '(language_name LIKE ? OR iso LIKE ?)';
            $types .= 'ss';
            $params[] = $like;
            $params[] = $like;
        }

        if ($status_filter !== '' && $status_filter !== null) {
            $where[] = 'active = ?';
            $types .= 'i';
            $params[] = (int) $status_filter;
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM book_languages $whereSql";
        $stmtCount = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmtCount->bind_param($types, ...$params);
        }
        $stmtCount->execute();
        $countRes = $stmtCount->get_result()->fetch_assoc();
        $totalRecords = (int) ($countRes['total'] ?? 0);
        $stmtCount->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT id, iso, language_name, active, created_at, updated_at
                FROM book_languages
                $whereSql
                ORDER BY language_name ASC, id ASC
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
            'languages' => $data,
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
            'SELECT id, iso, language_name, active, created_at, updated_at
             FROM book_languages WHERE id = ? LIMIT 1'
        );
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    private function isoExists(string $iso, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM book_languages WHERE iso = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $iso, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function nameExists(string $name, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM book_languages WHERE language_name = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('si', $name, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $exists;
    }

    private function normalizeIso(string $iso): string
    {
        return strtolower(trim($iso));
    }

    public function addRecord(array $data): array
    {
        $iso = $this->normalizeIso((string) ($data['addIso'] ?? ''));
        $name = trim((string) ($data['addLanguageName'] ?? ''));
        $active = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;

        if ($iso === '') {
            return ['success' => false, 'message' => 'ISO code is required.'];
        }

        if (strlen($iso) > 10) {
            return ['success' => false, 'message' => 'ISO code must be 10 characters or fewer.'];
        }

        if ($name === '') {
            return ['success' => false, 'message' => 'Language name is required.'];
        }

        if ($this->isoExists($iso)) {
            return ['success' => false, 'message' => 'ISO code already exists.'];
        }

        if ($this->nameExists($name)) {
            return ['success' => false, 'message' => 'Language name already exists.'];
        }

        $active = $active ? 1 : 0;

        $sql = 'INSERT INTO book_languages (iso, language_name, active, created_at, updated_at)
                VALUES (?, ?, ?, NOW(), NOW())';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('ssi', $iso, $name, $active);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Language added successfully.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Could not save: ' . $err];
    }

    public function updateRecord(int $id, array $data): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid language id.'];
        }

        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $iso = $this->normalizeIso((string) ($data['editIso'] ?? ''));
        $name = trim((string) ($data['editLanguageName'] ?? ''));
        $active = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;

        if ($iso === '') {
            return ['success' => false, 'message' => 'ISO code is required.'];
        }

        if (strlen($iso) > 10) {
            return ['success' => false, 'message' => 'ISO code must be 10 characters or fewer.'];
        }

        if ($name === '') {
            return ['success' => false, 'message' => 'Language name is required.'];
        }

        if ($this->isoExists($iso, $id)) {
            return ['success' => false, 'message' => 'ISO code already exists.'];
        }

        if ($this->nameExists($name, $id)) {
            return ['success' => false, 'message' => 'Language name already exists.'];
        }

        $active = $active ? 1 : 0;

        $sql = 'UPDATE book_languages SET iso = ?, language_name = ?, active = ?, updated_at = NOW() WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('ssii', $iso, $name, $active, $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Language updated successfully.'];
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
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $sql = 'UPDATE book_languages SET active = 0, updated_at = NOW() WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Language deactivated.'];
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
            return ['success' => false, 'message' => 'Language not found.'];
        }

        $sql = 'DELETE FROM book_languages WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            return ['success' => true, 'message' => 'Language deleted permanently.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Delete failed: ' . ($err ?: 'No rows removed.')];
    }

    /** @return array<int, array{id:int,iso:string,language_name:string}> */
    public function getActiveLanguages(): array
    {
        $result = $this->conn->query(
            'SELECT id, iso, language_name FROM book_languages WHERE active = 1 ORDER BY language_name ASC'
        );
        $rows = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        return $rows;
    }
}
