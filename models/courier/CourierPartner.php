<?php

class CourierPartner
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->ensureTable();
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS courier_partners (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            partner_code VARCHAR(50) NOT NULL UNIQUE,
            partner_name VARCHAR(120) NOT NULL,
            supports_domestic TINYINT(1) NOT NULL DEFAULT 1,
            supports_international TINYINT(1) NOT NULL DEFAULT 1,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_partner_name (partner_name),
            INDEX idx_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->conn->query($sql);
    }

    public function getAll(int $page = 1, int $limit = 20, string $search = '', string $status = ''): array
    {
        $page = max(1, $page);
        $limit = max(1, $limit);
        $offset = ($page - 1) * $limit;

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $where[] = "(partner_name LIKE ? OR partner_code LIKE ?)";
            $types .= 'ss';
            $searchLike = '%' . $search . '%';
            $params[] = $searchLike;
            $params[] = $searchLike;
        }
        if ($status !== '' && ($status === '0' || $status === '1')) {
            $where[] = "is_active = ?";
            $types .= 'i';
            $params[] = (int)$status;
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $countSql = "SELECT COUNT(*) AS total FROM courier_partners" . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        if ($countStmt && $types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $totalRecords = 0;
        if ($countStmt && $countStmt->execute()) {
            $res = $countStmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
            $totalRecords = (int)($row['total'] ?? 0);
        }
        if ($countStmt) {
            $countStmt->close();
        }

        $listSql = "SELECT id, partner_code, partner_name, shipper_id, supports_domestic, supports_international, is_active, notes, created_at, updated_at
                    FROM courier_partners" . $whereSql . " ORDER BY partner_name ASC LIMIT ? OFFSET ?";
        $listStmt = $this->conn->prepare($listSql);
        $rows = [];
        if ($listStmt) {
            $listTypes = $types . 'ii';
            $listParams = $params;
            $listParams[] = $limit;
            $listParams[] = $offset;
            $listStmt->bind_param($listTypes, ...$listParams);
            if ($listStmt->execute()) {
                $res = $listStmt->get_result();
                while ($res && ($r = $res->fetch_assoc())) {
                    $rows[] = $r;
                }
            }
            $listStmt->close();
        }

        return [
            'rows' => $rows,
            'currentPage' => $page,
            'limit' => $limit,
            'totalRecords' => $totalRecords,
            'totalPages' => $limit > 0 ? (int)ceil($totalRecords / $limit) : 1,
        ];
    }

    public function addRecord(array $data): array
    {
        $code = strtoupper(trim((string)($data['partner_code'] ?? '')));
        $name = trim((string)($data['partner_name'] ?? ''));
        if ($code === '' || $name === '') {
            return ['success' => false, 'message' => 'Partner code and partner name are required.'];
        }

        $dup = $this->duplicatePartnerError($code, (int) preg_replace('/\D/', '', (string) ($data['shipper_id'] ?? '')));
        if ($dup !== null) {
            return ['success' => false, 'message' => $dup];
        }

        $supportsDomestic = !empty($data['supports_domestic']) ? 1 : 0;
        $supportsInternational = !empty($data['supports_international']) ? 1 : 0;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $isActive = $isActive === 1 ? 1 : 0;
        $notes = trim((string)($data['notes'] ?? ''));
        $sid = (int) preg_replace('/\D/', '', (string) ($data['shipper_id'] ?? ''));
        $shipperIdBind = $sid > 0 ? (string) $sid : null;

        $sql = "INSERT INTO courier_partners
                (partner_code, partner_name, shipper_id, supports_domestic, supports_international, is_active, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not prepare insert statement.'];
        }
        $stmt->bind_param('sssiiis', $code, $name, $shipperIdBind, $supportsDomestic, $supportsInternational, $isActive, $notes);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Courier partner added successfully.'];
        }
        return ['success' => false, 'message' => 'Insert failed: ' . $stmt->error];
    }

    public function updateRecord(int $id, array $data): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid partner ID.'];
        }
        $code = strtoupper(trim((string)($data['partner_code'] ?? '')));
        $name = trim((string)($data['partner_name'] ?? ''));
        if ($code === '' || $name === '') {
            return ['success' => false, 'message' => 'Partner code and partner name are required.'];
        }

        $dup = $this->duplicatePartnerError(
            $code,
            (int) preg_replace('/\D/', '', (string) ($data['shipper_id'] ?? '')),
            $id
        );
        if ($dup !== null) {
            return ['success' => false, 'message' => $dup];
        }

        $supportsDomestic = !empty($data['supports_domestic']) ? 1 : 0;
        $supportsInternational = !empty($data['supports_international']) ? 1 : 0;
        $isActive = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $isActive = $isActive === 1 ? 1 : 0;
        $notes = trim((string)($data['notes'] ?? ''));
        $sid = (int) preg_replace('/\D/', '', (string) ($data['shipper_id'] ?? ''));
        $shipperIdBind = $sid > 0 ? (string) $sid : null;

        $sql = "UPDATE courier_partners
                SET partner_code = ?, partner_name = ?, shipper_id = ?, supports_domestic = ?, supports_international = ?, is_active = ?, notes = ?, updated_at = NOW()
                WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not prepare update statement.'];
        }
        $stmt->bind_param('sssiiisi', $code, $name, $shipperIdBind, $supportsDomestic, $supportsInternational, $isActive, $notes, $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Courier partner updated successfully.'];
        }
        return ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    public function deleteRecord(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid partner ID.'];
        }
        $stmt = $this->conn->prepare("DELETE FROM courier_partners WHERE id = ?");
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not prepare delete statement.'];
        }
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Courier partner deleted successfully.'];
        }
        return ['success' => false, 'message' => 'Delete failed: ' . $stmt->error];
    }

    /** @return list<array{id:int,partner_code:string,partner_name:string}> */
    public function getActivePartners(): array
    {
        $rows = [];
        $res = $this->conn->query("SELECT id, partner_code, partner_name FROM courier_partners WHERE is_active = 1 ORDER BY partner_name ASC");
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    /** @param list<array<string, mixed>> $apiRows */
    public function syncShippers(array $apiRows): array
    {
        $partners = [];
        $res = $this->conn->query('SELECT id, partner_code, partner_name, shipper_id FROM courier_partners');
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $partners[] = $r;
            }
        }

        $norm = static fn(string $s): string => preg_replace('/[^a-z0-9]/', '', strtolower($s)) ?? '';
        $updated = $skipped = 0;

        foreach ($apiRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $sid = (int) ($row['shipper_id'] ?? $row['id'] ?? 0);
            $name = trim((string) ($row['courier_name'] ?? $row['name'] ?? ''));
            if ($sid <= 0 || $name === '') {
                continue;
            }

            $code = strtoupper(substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($name)) ?? '', 0, 50));
            $key = $norm($name);

            $match = null;
            foreach ($partners as $p) {
                if ((int) ($p['shipper_id'] ?? 0) === $sid
                    || ($code !== '' && strcasecmp((string) $p['partner_code'], $code) === 0)
                    || $norm((string) $p['partner_name']) === $key
                    || $norm((string) $p['partner_code']) === $key) {
                    $match = $p;
                    break;
                }
            }

            if (!$match) {
                $skipped++;
                continue;
            }

            $pid = (int) $match['id'];
            foreach ($partners as $p) {
                if ((int) ($p['shipper_id'] ?? 0) === $sid && (int) $p['id'] !== $pid) {
                    $skipped++;
                    continue 2;
                }
            }

            if ((int) ($match['shipper_id'] ?? 0) === $sid) {
                continue;
            }

            $stmt = $this->conn->prepare('UPDATE courier_partners SET shipper_id = ? WHERE id = ?');
            $stmt->bind_param('ii', $sid, $pid);
            if ($stmt->execute()) {
                $updated++;
                $match['shipper_id'] = $sid;
            }
        }

        return [
            'success' => true,
            'message' => "Shipper sync done: {$updated} updated, {$skipped} skipped (no local match or shipper ID already used).",
        ];
    }

    private function duplicatePartnerError(string $code, int $shipperId, int $excludeId = 0): ?string
    {
        $stmt = $this->conn->prepare('SELECT id FROM courier_partners WHERE partner_code = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('s', $code);
            $stmt->execute();
            $row = $stmt->get_result()?->fetch_assoc();
            $stmt->close();
            if ($row && (int) $row['id'] !== $excludeId) {
                return 'Partner code already exists.';
            }
        }

        if ($shipperId > 0) {
            $stmt = $this->conn->prepare('SELECT id FROM courier_partners WHERE shipper_id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $shipperId);
                $stmt->execute();
                $row = $stmt->get_result()?->fetch_assoc();
                $stmt->close();
                if ($row && (int) $row['id'] !== $excludeId) {
                    return 'Shipper ID is already assigned to another partner.';
                }
            }
        }

        return null;
    }
}

