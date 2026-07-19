<?php

class Author
{
    private mysqli $conn;

    private const LIST_COLUMNS = 'author_id, author, contact_name, author_email, country_code, author_phone, alt_phone, address, city, state, country, postal_code, webpage, is_active, created_at, updated_at';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array<string, string>
     */
    public function normalizeAuthorFormData(array $data): array
    {
        return [
            'contact_name' => trim((string)($data['contact_name'] ?? '')),
            'author_email' => trim((string)($data['author_email'] ?? '')),
            'country_code' => trim((string)($data['country_code'] ?? '')),
            'author_phone' => trim((string)($data['author_phone'] ?? '')),
            'alt_phone' => trim((string)($data['alt_phone'] ?? '')),
            'address' => trim((string)($data['address'] ?? '')),
            'city' => trim((string)($data['city'] ?? '')),
            'state' => trim((string)($data['state'] ?? '')),
            'country' => trim((string)($data['country'] ?? '')),
            'postal_code' => trim((string)($data['postal_code'] ?? '')),
            'webpage' => (string)($data['webpage'] ?? '0') === '1' ? '1' : '0',
        ];
    }

    private function authorFieldExists(string $column, string $value, ?int $excludeAuthorId = null): bool
    {
        $allowed = ['author_phone', 'author_email'];
        if (!in_array($column, $allowed, true)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if ($column === 'author_email') {
            $sql = "SELECT author_id FROM vp_author WHERE LOWER(TRIM(author_email)) = LOWER(TRIM(?)) AND TRIM(COALESCE(author_email, '')) <> ''";
        } else {
            $sql = 'SELECT author_id FROM vp_author WHERE ' . $column . ' = ?';
        }

        if ($excludeAuthorId !== null && $excludeAuthorId > 0) {
            $sql .= ' AND author_id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $value, $excludeAuthorId);
        } else {
            $sql .= ' LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('s', $value);
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

    private function validateAuthorUniqueness(
        ?string $phone,
        ?string $email,
        ?int $excludeAuthorId = null
    ): ?array {
        if ($phone !== null && trim($phone) !== '' && $this->authorFieldExists('author_phone', trim($phone), $excludeAuthorId)) {
            return ['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.'];
        }
        if ($email !== null && trim($email) !== '' && $this->authorFieldExists('author_email', trim($email), $excludeAuthorId)) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email.'];
        }

        return null;
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
            $where[] = '(author LIKE ? OR author_id = ? OR city LIKE ? OR state LIKE ? OR contact_name LIKE ? OR author_phone LIKE ?)';
            $types .= 'sissss';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
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

        $sql = 'SELECT ' . self::LIST_COLUMNS . '
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
        $stmt = $this->conn->prepare('SELECT ' . self::LIST_COLUMNS . ' FROM vp_author WHERE author_id = ? LIMIT 1');
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
        return ['exists' => $this->isDuplicateAuthorName($name, $excludeAuthorId)];
    }

    public function isDuplicateAuthorName(string $name, ?int $excludeAuthorId = null): bool
    {
        if ($excludeAuthorId !== null && $excludeAuthorId > 0) {
            $existing = $this->getAuthorById($excludeAuthorId);
            if ($existing && namesEqualCi($name, (string)($existing['author'] ?? ''))) {
                return false;
            }
        }

        return $this->authorNameExists($name, $excludeAuthorId);
    }

    private function authorFieldValuesEqual(string $left, string $right): bool
    {
        return trim($left) === trim($right);
    }

    public function saveAuthor(?int $id, string $name, int $isActive, array $extra = []): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        $fields = $this->normalizeAuthorFormData($extra);
        if ($name === '') {
            return ['success' => false, 'message' => 'Author name is required.'];
        }

        if (!$id || $id <= 0) {
            return ['success' => false, 'message' => 'Author id is required for update.'];
        }

        $existing = $this->getAuthorById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Author not found.'];
        }

        if (!namesEqualCi($name, (string)($existing['author'] ?? ''))
            && $this->authorNameExists($name, $id)) {
            return ['success' => false, 'message' => 'Author name already exists'];
        }

        $phoneToCheck = $fields['author_phone'];
        if ($this->authorFieldValuesEqual($phoneToCheck, (string)($existing['author_phone'] ?? ''))) {
            $phoneToCheck = null;
        }
        $emailToCheck = $fields['author_email'];
        if ($this->authorFieldValuesEqual(
            mb_strtolower($emailToCheck, 'UTF-8'),
            mb_strtolower((string)($existing['author_email'] ?? ''), 'UTF-8')
        )) {
            $emailToCheck = null;
        }

        $duplicate = $this->validateAuthorUniqueness(
            $phoneToCheck,
            $emailToCheck,
            $id
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $stmt = $this->conn->prepare(
            'UPDATE vp_author SET author = ?, contact_name = ?, author_email = ?, country_code = ?, author_phone = ?, alt_phone = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, webpage = ?, is_active = ? WHERE author_id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stmt->bind_param(
            'sssssssssssiii',
            $name,
            $fields['contact_name'],
            $fields['author_email'],
            $fields['country_code'],
            $fields['author_phone'],
            $fields['alt_phone'],
            $fields['address'],
            $fields['city'],
            $fields['state'],
            $fields['country'],
            $fields['postal_code'],
            $webpage,
            $isActive,
            $id
        );

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

    public function insertAuthor(int $authorId, string $name, int $isActive, array $extra = []): array
    {
        $authorId = (int) $authorId;
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        $fields = $this->normalizeAuthorFormData($extra);

        if ($authorId <= 0) {
            return ['success' => false, 'message' => 'Remote author vendor_id is required.'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Author name is required.'];
        }

        if ($this->authorNameExists($name)) {
            return ['success' => false, 'message' => 'Author name already exists'];
        }

        $duplicate = $this->validateAuthorUniqueness(
            $fields['author_phone'],
            $fields['author_email']
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_author (author_id, author, contact_name, author_email, country_code, author_phone, alt_phone, address, city, state, country, postal_code, webpage, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stmt->bind_param(
            'issssssssssssii',
            $authorId,
            $name,
            $fields['contact_name'],
            $fields['author_email'],
            $fields['country_code'],
            $fields['author_phone'],
            $fields['alt_phone'],
            $fields['address'],
            $fields['city'],
            $fields['state'],
            $fields['country'],
            $fields['postal_code'],
            $webpage,
            $isActive
        );

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
        $imported = 0;
        $skipped = 0;
        $batch = [];
        $batchSize = 500;

        foreach ($creators as $id => $name) {
            $authorId = (int) $id;
            $author = trim((string) $name);
            if ($authorId <= 0 || $author === '') {
                ++$skipped;
                continue;
            }

            $batch[] = [$authorId, $author];
            if (count($batch) >= $batchSize) {
                $result = $this->importCreatorBatch($batch);
                $imported += $result['imported'];
                $skipped += $result['skipped'];
                $batch = [];
            }
        }

        if ($batch !== []) {
            $result = $this->importCreatorBatch($batch);
            $imported += $result['imported'];
            $skipped += $result['skipped'];
        }

        return [
            'success' => true,
            'message' => 'Author sync completed.',
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param list<array{0:int,1:string}> $rows
     * @return array{imported:int,skipped:int}
     */
    private function importCreatorBatch(array $rows): array
    {
        if ($rows === []) {
            return ['imported' => 0, 'skipped' => 0];
        }

        $placeholders = implode(',', array_fill(0, count($rows), '(?, ?, 1)'));
        $sql = 'INSERT INTO vp_author (author_id, author, is_active) VALUES ' . $placeholders
            . ' ON DUPLICATE KEY UPDATE author = VALUES(author), is_active = 1, updated_at = CURRENT_TIMESTAMP';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $this->importCreatorRowsIndividually($rows);
        }

        $types = str_repeat('is', count($rows));
        $params = [];
        foreach ($rows as [$authorId, $author]) {
            $params[] = $authorId;
            $params[] = $author;
        }
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            $imported = count($rows);
            $stmt->close();

            return ['imported' => $imported, 'skipped' => 0];
        }

        $stmt->close();

        return $this->importCreatorRowsIndividually($rows);
    }

    /**
     * @param list<array{0:int,1:string}> $rows
     * @return array{imported:int,skipped:int}
     */
    private function importCreatorRowsIndividually(array $rows): array
    {
        $stmt = $this->conn->prepare(
            'INSERT INTO vp_author (author_id, author, is_active)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE author = VALUES(author), is_active = 1, updated_at = CURRENT_TIMESTAMP'
        );
        if (!$stmt) {
            return ['imported' => 0, 'skipped' => count($rows)];
        }

        $imported = 0;
        $skipped = 0;
        foreach ($rows as [$authorId, $author]) {
            $stmt->bind_param('is', $authorId, $author);
            if ($stmt->execute()) {
                ++$imported;
            } else {
                ++$skipped;
            }
        }
        $stmt->close();

        return ['imported' => $imported, 'skipped' => $skipped];
    }
}
