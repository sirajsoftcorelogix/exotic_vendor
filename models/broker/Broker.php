<?php

class Broker
{
    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $statusFilter = ''): array
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
            $where[] = '(b.broker_name LIKE ? OR b.state LIKE ? OR b.zone LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($statusFilter !== '' && $statusFilter !== null) {
            $where[] = 'b.is_active = ?';
            $types .= 'i';
            $params[] = (int) $statusFilter;
        }

        $whereSql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $countSql = "SELECT COUNT(*) AS total FROM vp_brokers b $whereSql";
        $countStmt = $this->conn->prepare($countSql);
        if (!$countStmt) {
            return $this->emptyListing($page, $limit, $search);
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $countRow = $countStmt->get_result()->fetch_assoc();
        $totalRecords = (int) ($countRow['total'] ?? 0);
        $countStmt->close();

        $totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

        $sql = "SELECT b.id, b.broker_name, b.state, b.zone, b.is_active, b.created_at, b.updated_at,
                       (SELECT COUNT(*) FROM vp_publishers p WHERE p.broker_id = b.id) AS publisher_count
                FROM vp_brokers b
                $whereSql
                ORDER BY b.broker_name ASC, b.id ASC
                LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $this->emptyListing($page, $limit, $search);
        }

        if ($types !== '') {
            $typesWithPaging = $types . 'ii';
            $stmt->bind_param($typesWithPaging, ...array_merge($params, [$limit, $offset]));
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();

        return [
            'brokers' => $rows,
            'totalPages' => max(1, $totalPages),
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
            'SELECT id, broker_name, state, zone, is_active, created_at, updated_at
             FROM vp_brokers WHERE id = ? LIMIT 1'
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
     * Active brokers for autocomplete (Select2: id + text).
     *
     * @return array<int, array{id:int, text:string}>
     */
    public function searchActiveBrokers(string $query, int $limit = 20): array
    {
        $query = trim($query);
        if (strlen($query) < 2) {
            return [];
        }

        $limit = max(1, min(50, $limit));
        $like = '%' . $query . '%';
        $stmt = $this->conn->prepare(
            'SELECT id, broker_name FROM vp_brokers
             WHERE is_active = 1
               AND (broker_name LIKE ? OR state LIKE ? OR zone LIKE ?)
             ORDER BY broker_name ASC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sssi', $like, $like, $like, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        $items = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $name = trim((string) ($row['broker_name'] ?? ''));
            if ($id <= 0 || $name === '') {
                continue;
            }
            $items[] = ['id' => $id, 'text' => $name];
        }

        return $items;
    }

    public function isActiveBroker(int $brokerId): bool
    {
        if ($brokerId <= 0) {
            return false;
        }

        $stmt = $this->conn->prepare(
            'SELECT id FROM vp_brokers WHERE id = ? AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $brokerId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();

        return $ok;
    }

    public function addRecord(array $data): array
    {
        $name = trim((string) ($data['addBrokerName'] ?? ''));
        $state = trim((string) ($data['addState'] ?? ''));
        $zone = trim((string) ($data['addZone'] ?? ''));
        $isActive = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;

        if ($name === '') {
            return ['success' => false, 'message' => 'Broker name is required.'];
        }

        if ($this->nameExists($name)) {
            return ['success' => false, 'message' => 'Broker name already exists.'];
        }

        $isActive = $isActive ? 1 : 0;
        $stateParam = $state !== '' ? $state : null;
        $zoneParam = $zone !== '' ? $zone : null;

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_brokers (broker_name, state, zone, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('sssi', $name, $stateParam, $zoneParam, $isActive);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Broker added successfully.'];
        }

        $err = $stmt->error;
        $stmt->close();

        return ['success' => false, 'message' => 'Could not save: ' . $err];
    }

    public function updateRecord(int $id, array $data): array
    {
        $id = (int) $id;
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid broker id.'];
        }

        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Broker not found.'];
        }

        $name = trim((string) ($data['editBrokerName'] ?? ''));
        $state = trim((string) ($data['editState'] ?? ''));
        $zone = trim((string) ($data['editZone'] ?? ''));
        $isActive = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;

        if ($name === '') {
            return ['success' => false, 'message' => 'Broker name is required.'];
        }

        if ($this->nameExists($name, $id)) {
            return ['success' => false, 'message' => 'Broker name already exists.'];
        }

        $isActive = $isActive ? 1 : 0;
        if ($isActive === 0) {
            $mappingError = $this->publisherMappingError($id);
            if ($mappingError !== null) {
                return $mappingError;
            }
        }

        $stateParam = $state !== '' ? $state : null;
        $zoneParam = $zone !== '' ? $zone : null;

        $stmt = $this->conn->prepare(
            'UPDATE vp_brokers
             SET broker_name = ?, state = ?, zone = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('sssii', $name, $stateParam, $zoneParam, $isActive, $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Broker updated successfully.'];
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
            return ['success' => false, 'message' => 'Broker not found.'];
        }

        $mappingError = $this->publisherMappingError($id);
        if ($mappingError !== null) {
            return $mappingError;
        }

        $stmt = $this->conn->prepare(
            'UPDATE vp_brokers SET is_active = 0, updated_at = NOW() WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Broker deactivated.'];
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
            return ['success' => false, 'message' => 'Broker not found.'];
        }

        $mappingError = $this->publisherMappingError($id);
        if ($mappingError !== null) {
            return $mappingError;
        }

        $stmt = $this->conn->prepare('DELETE FROM vp_brokers WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            return ['success' => true, 'message' => 'Broker deleted permanently.'];
        }

        $err = $stmt->error;
        $stmt->close();

        return ['success' => false, 'message' => 'Delete failed: ' . ($err ?: 'No rows removed.')];
    }

    private function nameExists(string $name, int $exceptId = 0): bool
    {
        $sql = 'SELECT id FROM vp_brokers WHERE broker_name = ? AND id != ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('si', $name, $exceptId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function countPublisherMappings(int $brokerId): int
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS total FROM vp_publishers WHERE broker_id = ?'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $brokerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['total'] ?? 0);
    }

    private function publisherMappingError(int $brokerId): ?array
    {
        $count = $this->countPublisherMappings($brokerId);
        if ($count <= 0) {
            return null;
        }

        $label = $count === 1 ? '1 publisher' : $count . ' publishers';

        return [
            'success' => false,
            'message' => 'Cannot delete or deactivate: broker is assigned to ' . $label . '. Remove the mapping first.',
        ];
    }

    private function emptyListing(int $page, int $limit, string $search): array
    {
        return [
            'brokers' => [],
            'totalPages' => 1,
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => 0,
            'search' => $search,
        ];
    }
}
