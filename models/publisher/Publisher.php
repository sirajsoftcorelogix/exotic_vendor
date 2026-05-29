<?php

class Publisher
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getPublishers(int $page = 1, int $limit = 20, string $search = '', string $status = ''): array
    {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $where[] = '(publishers LIKE ? OR publishers_id = ? OR id = ?)';
            $types .= 'sii';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
            $params[] = (int)$search;
        }

        if ($status === 'active') {
            $where[] = 'is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $countStmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM vp_publishers' . $whereSql);
        if (!$countStmt) {
            return ['publishers' => [], 'totalRecords' => 0, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = (int)(($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
        $countStmt->close();

        $stmt = $this->conn->prepare(
            'SELECT id, publishers_id, publishers, is_active, create_at, update_at
             FROM vp_publishers' . $whereSql . '
             ORDER BY publishers ASC
             LIMIT ? OFFSET ?'
        );
        if (!$stmt) {
            return ['publishers' => [], 'totalRecords' => $totalRecords, 'totalPages' => 1, 'currentPage' => $page, 'limit' => $limit];
        }

        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $publishers = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return [
            'publishers' => $publishers,
            'totalRecords' => $totalRecords,
            'totalPages' => max(1, (int)ceil($totalRecords / $limit)),
            'currentPage' => $page,
            'limit' => $limit,
        ];
    }

    public function getPublisherById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT id, publishers_id, publishers, is_active, create_at, update_at FROM vp_publishers WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function savePublisher(?int $id, string $name, int $isActive): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        if ($name === '') {
            return ['success' => false, 'message' => 'Publisher name is required.'];
        }

        if (!$id || $id <= 0) {
            return ['success' => false, 'message' => 'Publisher id is required for update.'];
        }

        $stmt = $this->conn->prepare('UPDATE vp_publishers SET publishers = ?, is_active = ? WHERE id = ?');
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
            return ['success' => false, 'message' => 'Could not save publisher: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Publisher saved successfully.', 'id' => $id]
            : ['success' => false, 'message' => 'Could not save publisher: ' . $error];
    }

    public function insertPublisher(int $publishersId, string $name, int $isActive): array
    {
        $publishersId = (int) $publishersId;
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;

        if ($publishersId <= 0) {
            return ['success' => false, 'message' => 'Remote publisher vendor_id is required.'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Publisher name is required.'];
        }

        $stmt = $this->conn->prepare('INSERT INTO vp_publishers (publishers_id, publishers, is_active) VALUES (?, ?, ?)');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('isi', $publishersId, $name, $isActive);

        try {
            $ok = $stmt->execute();
            $newId = (int) $stmt->insert_id;
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save publisher: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Publisher saved successfully.', 'id' => $newId, 'publishers_id' => $publishersId]
            : ['success' => false, 'message' => 'Could not save publisher: ' . $error];
    }

    public function updatePublisherRemoteId(int $localId, int $publishersId): array
    {
        if ($localId <= 0 || $publishersId <= 0) {
            return ['success' => false, 'message' => 'Invalid publisher ids.'];
        }

        $stmt = $this->conn->prepare('UPDATE vp_publishers SET publishers_id = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $publishersId, $localId);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => 'Publisher remote id updated.']
            : ['success' => false, 'message' => 'Could not update publisher remote id: ' . $error];
    }

    public function setStatus(int $id, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid publisher id.'];
        }
        $isActive = $isActive ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE vp_publishers SET is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $isActive, $id);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $isActive ? 'Publisher activated.' : 'Publisher deactivated.']
            : ['success' => false, 'message' => 'Could not update status: ' . $error];
    }

    public function deletePublisher(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid publisher id.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM vp_publishers WHERE id = ?');
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
            return ['success' => false, 'message' => 'Could not delete publisher. It may be used by inbound records.'];
        }

        return $ok
            ? ['success' => true, 'message' => 'Publisher deleted successfully.']
            : ['success' => false, 'message' => 'Could not delete publisher: ' . $error];
    }

    /**
     * @param array<int|string, string> $creators API id => publisher name
     */
    public function importFromCreators(array $creators): array
    {
        $selectStmt = $this->conn->prepare('SELECT id FROM vp_publishers WHERE publishers_id = ? LIMIT 1');
        $updateStmt = $this->conn->prepare('UPDATE vp_publishers SET publishers = ?, is_active = 1 WHERE id = ?');
        $insertStmt = $this->conn->prepare('INSERT INTO vp_publishers (publishers_id, publishers, is_active) VALUES (?, ?, 1)');
        if (!$selectStmt || !$updateStmt || !$insertStmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }

        $imported = 0;
        $skipped = 0;
        foreach ($creators as $id => $name) {
            $publisherId = (int)$id;
            $publisher = trim((string)$name);
            if ($publisherId <= 0 || $publisher === '') {
                ++$skipped;
                continue;
            }
            $selectStmt->bind_param('i', $publisherId);
            if (!$selectStmt->execute()) {
                ++$skipped;
                continue;
            }

            $existing = $selectStmt->get_result()->fetch_assoc();
            if ($existing) {
                $localId = (int)$existing['id'];
                $updateStmt->bind_param('si', $publisher, $localId);
                $ok = $updateStmt->execute();
            } else {
                $insertStmt->bind_param('is', $publisherId, $publisher);
                $ok = $insertStmt->execute();
            }

            if ($ok) {
                ++$imported;
            } else {
                ++$skipped;
            }
        }
        $selectStmt->close();
        $updateStmt->close();
        $insertStmt->close();

        return [
            'success' => true,
            'message' => 'Publisher sync completed.',
            'imported' => $imported,
            'skipped' => $skipped,
        ];
    }

}
