<?php

class Broker
{
    private mysqli $conn;

    private const MAX_LOCATIONS = 20;

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
            $where[] = '(b.broker_name LIKE ?
                OR EXISTS (
                    SELECT 1 FROM vp_broker_locations bl
                    WHERE bl.broker_id = b.id
                      AND (bl.state LIKE ? OR bl.zone LIKE ?)
                ))';
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

        $sql = "SELECT b.id, b.broker_name, b.is_active, b.created_at, b.updated_at,
                       (SELECT COUNT(*) FROM vp_vendors v WHERE v.broker_id = b.id) AS vendor_count
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

        $this->attachLocationsToBrokers($rows);

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
            'SELECT id, broker_name, is_active, created_at, updated_at
             FROM vp_brokers WHERE id = ? LIMIT 1'
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

        $row['locations'] = $this->getLocationsByBrokerId($id);

        return $row;
    }

    /**
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
            'SELECT DISTINCT b.id, b.broker_name
             FROM vp_brokers b
             LEFT JOIN vp_broker_locations bl ON bl.broker_id = b.id
             WHERE b.is_active = 1
               AND (b.broker_name LIKE ? OR bl.state LIKE ? OR bl.zone LIKE ?)
             ORDER BY b.broker_name ASC
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
        $isActive = isset($data['addStatus']) ? (int) $data['addStatus'] : 1;
        $locations = $this->parseLocationsFromPost($data['locations'] ?? null);

        if ($name === '') {
            return ['success' => false, 'message' => 'Broker name is required.'];
        }

        if ($this->nameExists($name)) {
            return ['success' => false, 'message' => 'Broker name already exists.'];
        }

        $locationError = $this->validateLocations($locations);
        if ($locationError !== null) {
            return $locationError;
        }

        $isActive = $isActive ? 1 : 0;

        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_brokers (broker_name, is_active, created_at, updated_at)
             VALUES (?, ?, NOW(), NOW())'
        );
        if (!$stmt) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('si', $name, $isActive);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Could not save: ' . $err];
        }
        $brokerId = (int) $stmt->insert_id;
        $stmt->close();

        $saveLocations = $this->replaceLocations($brokerId, $locations);
        if (!$saveLocations['success']) {
            $this->conn->rollback();
            return $saveLocations;
        }

        $this->conn->commit();

        return ['success' => true, 'message' => 'Broker added successfully.'];
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
        $isActive = isset($data['editStatus']) ? (int) $data['editStatus'] : 1;
        $locations = $this->parseLocationsFromPost($data['locations'] ?? null);

        if ($name === '') {
            return ['success' => false, 'message' => 'Broker name is required.'];
        }

        if ($this->nameExists($name, $id)) {
            return ['success' => false, 'message' => 'Broker name already exists.'];
        }

        $locationError = $this->validateLocations($locations);
        if ($locationError !== null) {
            return $locationError;
        }

        $isActive = $isActive ? 1 : 0;
        if ($isActive === 0) {
            $mappingError = $this->vendorMappingError($id);
            if ($mappingError !== null) {
                return $mappingError;
            }
        }

        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            'UPDATE vp_brokers
             SET broker_name = ?, is_active = ?, updated_at = NOW()
             WHERE id = ?'
        );
        if (!$stmt) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('sii', $name, $isActive, $id);
        if (!$stmt->execute()) {
            $err = $stmt->error;
            $stmt->close();
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Could not update: ' . $err];
        }
        $stmt->close();

        $saveLocations = $this->replaceLocations($id, $locations);
        if (!$saveLocations['success']) {
            $this->conn->rollback();
            return $saveLocations;
        }

        $this->conn->commit();

        return ['success' => true, 'message' => 'Broker updated successfully.'];
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

        $mappingError = $this->vendorMappingError($id);
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

        $mappingError = $this->vendorMappingError($id);
        if ($mappingError !== null) {
            return $mappingError;
        }

        $this->conn->begin_transaction();

        $deleteLocations = $this->conn->prepare('DELETE FROM vp_broker_locations WHERE broker_id = ?');
        if ($deleteLocations) {
            $deleteLocations->bind_param('i', $id);
            $deleteLocations->execute();
            $deleteLocations->close();
        }

        $stmt = $this->conn->prepare('DELETE FROM vp_brokers WHERE id = ?');
        if (!$stmt) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $stmt->close();
            $this->conn->commit();
            return ['success' => true, 'message' => 'Broker deleted permanently.'];
        }

        $err = $stmt->error;
        $stmt->close();
        $this->conn->rollback();

        return ['success' => false, 'message' => 'Delete failed: ' . ($err ?: 'No rows removed.')];
    }

    /**
     * @return list<array{state:string, zone:string}>
     */
    public function parseLocationsFromPost($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $rows = [];
        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $state = trim((string) ($item['state'] ?? ''));
            $zone = trim((string) ($item['zone'] ?? ''));
            if ($state === '' && $zone === '') {
                continue;
            }
            $rows[] = [
                'state' => $state,
                'zone' => $zone,
            ];
            if (count($rows) >= self::MAX_LOCATIONS) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return list<array{id:int, state:?string, zone:?string, sort_order:int}>
     */
    public function getLocationsByBrokerId(int $brokerId): array
    {
        if ($brokerId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT id, state, zone, sort_order
             FROM vp_broker_locations
             WHERE broker_id = ?
             ORDER BY sort_order ASC, id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $brokerId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'state' => (string) ($row['state'] ?? ''),
                'zone' => (string) ($row['zone'] ?? ''),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];
        }
        $stmt->close();

        return $rows;
    }

    private function replaceLocations(int $brokerId, array $locations): array
    {
        $deleteStmt = $this->conn->prepare('DELETE FROM vp_broker_locations WHERE broker_id = ?');
        if (!$deleteStmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
        $deleteStmt->bind_param('i', $brokerId);
        if (!$deleteStmt->execute()) {
            $err = $deleteStmt->error;
            $deleteStmt->close();
            return ['success' => false, 'message' => 'Could not update locations: ' . $err];
        }
        $deleteStmt->close();

        if ($locations === []) {
            return ['success' => true];
        }

        $insertStmt = $this->conn->prepare(
            'INSERT INTO vp_broker_locations (broker_id, state, zone, sort_order, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())'
        );
        if (!$insertStmt) {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }

        $sortOrder = 1;
        foreach ($locations as $location) {
            $state = trim((string) ($location['state'] ?? ''));
            $zone = trim((string) ($location['zone'] ?? ''));
            $stateParam = $state !== '' ? $state : null;
            $zoneParam = $zone !== '' ? $zone : null;
            $insertStmt->bind_param('issi', $brokerId, $stateParam, $zoneParam, $sortOrder);
            if (!$insertStmt->execute()) {
                $err = $insertStmt->error;
                $insertStmt->close();
                return ['success' => false, 'message' => 'Could not save location: ' . $err];
            }
            $sortOrder++;
        }
        $insertStmt->close();

        return ['success' => true];
    }

    /**
     * @param list<array{state:string, zone:string}> $locations
     */
    private function validateLocations(array $locations): ?array
    {
        if (count($locations) > self::MAX_LOCATIONS) {
            return ['success' => false, 'message' => 'A broker can have at most ' . self::MAX_LOCATIONS . ' locations.'];
        }

        $seen = [];
        foreach ($locations as $location) {
            $state = mb_strtolower(trim((string) ($location['state'] ?? '')), 'UTF-8');
            $zone = mb_strtolower(trim((string) ($location['zone'] ?? '')), 'UTF-8');
            $key = $state . '|' . $zone;
            if (isset($seen[$key])) {
                return ['success' => false, 'message' => 'Duplicate location rows are not allowed for the same state and zone.'];
            }
            $seen[$key] = true;
        }

        return null;
    }

    private function attachLocationsToBrokers(array &$brokers): void
    {
        foreach ($brokers as &$broker) {
            $brokerId = (int) ($broker['id'] ?? 0);
            $broker['locations'] = $this->getLocationsByBrokerId($brokerId);
        }
        unset($broker);
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

    private function countVendorMappings(int $brokerId): int
    {
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS total FROM vp_vendors WHERE broker_id = ?'
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

    private function vendorMappingError(int $brokerId): ?array
    {
        $count = $this->countVendorMappings($brokerId);
        if ($count <= 0) {
            return null;
        }

        $label = $count === 1 ? '1 vendor' : $count . ' vendors';

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
