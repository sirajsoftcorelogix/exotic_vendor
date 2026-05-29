<?php

class Author
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getAuthors(int $page = 1, int $limit = 20, string $search = '', string $status = ''): array
    {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $where[] = '(author LIKE ? OR author_id = ?)';
            $types .= 'si';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
        }

        if ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $countSql = 'SELECT COUNT(*) AS total FROM vp_author' . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        if (!$countStmt) {
            return ['authors' => [], 'totalRecords' => 0, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = (int)(($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
        $countStmt->close();

        $sql = 'SELECT author_id, author, is_active, created_at, updated_at
                FROM vp_author' . $whereSql . '
                ORDER BY author ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['authors' => [], 'totalRecords' => $totalRecords, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }

        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $authors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'authors' => $authors,
            'totalRecords' => $totalRecords,
            'totalPages' => max(1, (int)ceil($totalRecords / $limit)),
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }

    public function getAuthorById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT author_id, author, is_active, created_at, updated_at FROM vp_author WHERE author_id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function authorNameExists(string $name, ?int $excludeAuthorId = null): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if ($excludeAuthorId !== null && $excludeAuthorId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT author_id FROM vp_author WHERE LOWER(TRIM(author)) = LOWER(TRIM(?)) AND author_id != ? LIMIT 1'
            );
            $stmt->bind_param('si', $name, $excludeAuthorId);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT author_id FROM vp_author WHERE LOWER(TRIM(author)) = LOWER(TRIM(?)) LIMIT 1'
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

    public function checkAuthorName(string $name, ?int $excludeAuthorId = null): array
    {
        return ['exists' => $this->authorNameExists($name, $excludeAuthorId)];
    }

    public function saveAuthor(?int $id, string $name, int $isActive): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        if ($name === '') {
            return ['success' => false, 'message' => 'Author name is required.'];
        }

        if (!$id || $id <= 0) {
            return ['success' => false, 'message' => 'Author id is required for update.'];
        }

        if ($this->authorNameExists($name, $id)) {
            return ['success' => false, 'message' => 'Author name already exists'];
        }

        $stmt = $this->conn->prepare('UPDATE vp_author SET author = ?, is_active = ? WHERE author_id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('sii', $name, $isActive, $id);

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save author: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Author saved successfully.', 'author_id' => $id]
            : ['success' => false, 'message' => 'Could not save author: ' . $error];
    }

    public function insertAuthor(int $authorId, string $name, int $isActive): array
    {
        $authorId = (int) $authorId;
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;

        if ($authorId <= 0) {
            return ['success' => false, 'message' => 'Remote author vendor_id is required.'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Author name is required.'];
        }

        if ($this->authorNameExists($name)) {
            return ['success' => false, 'message' => 'Author name already exists'];
        }

        $stmt = $this->conn->prepare('INSERT INTO vp_author (author_id, author, is_active) VALUES (?, ?, ?)');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('isi', $authorId, $name, $isActive);

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save author: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Author saved successfully.', 'author_id' => $authorId]
            : ['success' => false, 'message' => 'Could not save author: ' . $error];
    }

    public function setStatus(int $id, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid author id.'];
        }
        $isActive = $isActive ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE vp_author SET is_active = ? WHERE author_id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $isActive, $id);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $isActive ? 'Author activated.' : 'Author deactivated.']
            : ['success' => false, 'message' => 'Could not update status: ' . $error];
    }

    public function deleteAuthor(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid author id.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM vp_author WHERE author_id = ?');
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
            return ['success' => false, 'message' => 'Could not delete author. It may be used by inbound records.'];
        }

        return $ok
            ? ['success' => true, 'message' => 'Author deleted successfully.']
            : ['success' => false, 'message' => 'Could not delete author: ' . $error];
    }

    /**
     * @param array<int|string, string> $creators API id => author name
     */
    public function importFromCreators(array $creators): array
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO vp_author (author_id, author, is_active)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE author = VALUES(author), is_active = 1, updated_at = CURRENT_TIMESTAMP'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }

        $imported = 0;
        $skipped = 0;
        foreach ($creators as $id => $name) {
            $authorId = (int)$id;
            $author = trim((string)$name);
            if ($authorId <= 0 || $author === '') {
                ++$skipped;
                continue;
            }
            $stmt->bind_param('is', $authorId, $author);
            if ($stmt->execute()) {
                ++$imported;
            } else {
                ++$skipped;
            }
        }
        $stmt->close();

        return [
            'success' => true,
            'message' => 'Author sync completed.',
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }
}
