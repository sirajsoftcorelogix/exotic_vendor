<?php

class PortMaster
{
    private mysqli $conn;

    public const TYPE_SEA = 'sea';
    public const TYPE_AIR = 'air';
    public const TYPE_INLAND = 'inland';
    public const TYPE_DRY = 'dry';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->ensureSchema();
    }

    public function ensureSchema(): void
    {
        $this->renameLegacyTable();
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS shipping_port_master (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                port_name VARCHAR(255) NOT NULL,
                port_code VARCHAR(20) NOT NULL,
                port_type VARCHAR(20) NOT NULL,
                city VARCHAR(120) NOT NULL,
                country_id INT UNSIGNED NOT NULL,
                pincode VARCHAR(20) NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_shipping_port_master_code (port_code),
                INDEX idx_shipping_port_master_type (port_type),
                INDEX idx_shipping_port_master_country (country_id),
                INDEX idx_shipping_port_master_city (city),
                INDEX idx_shipping_port_master_active (is_active),
                INDEX idx_shipping_port_master_name (port_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->ensureModule();
        $this->seedDefaultPorts();
    }

    private function renameLegacyTable(): void
    {
        $old = @$this->conn->query("SHOW TABLES LIKE 'port_master'");
        $new = @$this->conn->query("SHOW TABLES LIKE 'shipping_port_master'");
        $oldExists = $old && $old->num_rows > 0;
        $newExists = $new && $new->num_rows > 0;
        if ($oldExists && !$newExists) {
            @$this->conn->query('RENAME TABLE port_master TO shipping_port_master');
        }
    }

    /**
     * Common Indian air-cargo ports (ICEGATE ACC codes).
     *
     * @return list<array{port_name:string,port_code:string,port_type:string,city:string,pincode:string}>
     */
    public static function defaultSeedPorts(): array
    {
        return [
            [
                'port_name' => 'Delhi Air Cargo ACC',
                'port_code' => 'INDEL4',
                'port_type' => self::TYPE_AIR,
                'city' => 'Delhi',
                'pincode' => '110037',
            ],
            [
                'port_name' => 'Chennai Air Cargo ACC',
                'port_code' => 'INMAA4',
                'port_type' => self::TYPE_AIR,
                'city' => 'Chennai',
                'pincode' => '600027',
            ],
            [
                'port_name' => 'Varanasi Air Cargo (Banaras)',
                'port_code' => 'INVNS4',
                'port_type' => self::TYPE_AIR,
                'city' => 'Banaras',
                'pincode' => '221006',
            ],
            [
                'port_name' => 'Chennai Port',
                'port_code' => 'INMAA1',
                'port_type' => self::TYPE_SEA,
                'city' => 'Chennai',
                'pincode' => '600001',
            ],
            [
                'port_name' => 'Kamarajar Port (Ennore)',
                'port_code' => 'INENR1',
                'port_type' => self::TYPE_SEA,
                'city' => 'Chennai',
                'pincode' => '600057',
            ],
            [
                'port_name' => 'Kattupalli Port',
                'port_code' => 'INKAT1',
                'port_type' => self::TYPE_SEA,
                'city' => 'Chennai',
                'pincode' => '601120',
            ],
            [
                'port_name' => 'JNPT Nhava Sheva',
                'port_code' => 'INNSA1',
                'port_type' => self::TYPE_SEA,
                'city' => 'Navi Mumbai',
                'pincode' => '400707',
            ],
            [
                'port_name' => 'ICD Tughlakabad',
                'port_code' => 'INTKD6',
                'port_type' => self::TYPE_DRY,
                'city' => 'Delhi',
                'pincode' => '110020',
            ],
            [
                'port_name' => 'ICD Patparganj',
                'port_code' => 'INPPG6',
                'port_type' => self::TYPE_DRY,
                'city' => 'Delhi',
                'pincode' => '110096',
            ],
            [
                'port_name' => 'ICD Tondiarpet',
                'port_code' => 'INTVT6',
                'port_type' => self::TYPE_DRY,
                'city' => 'Chennai',
                'pincode' => '600019',
            ],
            [
                'port_name' => 'ICD Varanasi (Banaras)',
                'port_code' => 'INBSB6',
                'port_type' => self::TYPE_DRY,
                'city' => 'Banaras',
                'pincode' => '221002',
            ],
        ];
    }

    private function seedDefaultPorts(): void
    {
        $countryId = $this->getDefaultCountryId();
        if ($countryId <= 0 || !$this->countryExists($countryId)) {
            return;
        }

        foreach (self::defaultSeedPorts() as $port) {
            $code = strtoupper(trim((string) ($port['port_code'] ?? '')));
            if ($code === '' || $this->portCodeExists($code)) {
                continue;
            }
            $port['country_id'] = $countryId;
            $port['is_active'] = 1;
            $this->insertPort($port, 1);
        }
    }

    /**
     * @return array<string, string>
     */
    public static function portTypeLabels(): array
    {
        return [
            self::TYPE_SEA => 'Sea Port',
            self::TYPE_AIR => 'Air Port',
            self::TYPE_INLAND => 'Inland Port',
            self::TYPE_DRY => 'Dry Port',
        ];
    }

    /**
     * Values stored on invoices as Pre Carriage By.
     *
     * @return array<string, string>
     */
    public static function preCarriageValues(): array
    {
        return [
            self::TYPE_AIR => 'Air',
            self::TYPE_SEA => 'Sea',
            self::TYPE_INLAND => 'Inland',
            self::TYPE_DRY => 'Dry',
        ];
    }

    public static function preCarriageToPortType(string $preCarriage): string
    {
        $preCarriage = strtolower(trim($preCarriage));
        if ($preCarriage === '') {
            return self::TYPE_AIR;
        }
        $normalized = self::normalizePortType($preCarriage);
        if ($normalized !== '') {
            return $normalized;
        }
        foreach (self::preCarriageValues() as $type => $label) {
            if (strtolower($label) === $preCarriage) {
                return $type;
            }
        }
        foreach (self::portTypeLabels() as $type => $label) {
            if (strtolower($label) === $preCarriage) {
                return $type;
            }
        }

        return self::TYPE_AIR;
    }

    /**
     * @param array<string, mixed> $port
     */
    public static function formatPortOption(array $port): string
    {
        $name = trim((string) ($port['port_name'] ?? ''));
        $code = strtoupper(trim((string) ($port['port_code'] ?? '')));
        $city = trim((string) ($port['city'] ?? ''));
        $label = $name !== '' ? $name : $code;
        if ($code !== '' && stripos($label, $code) === false) {
            $label .= ' (' . $code . ')';
        }
        if ($city !== '' && stripos($label, $city) === false) {
            $label .= ' — ' . $city;
        }

        return $label;
    }

    /**
     * Align invoice export defaults with an active shipping port.
     *
     * @param array<string, mixed> $defaults
     * @return array<string, mixed>
     */
    public function applyInvoiceDefaults(array $defaults): array
    {
        $type = self::preCarriageToPortType((string) ($defaults['pre_carriage_by'] ?? 'Air'));
        $preCarriageValues = self::preCarriageValues();
        $defaults['pre_carriage_by'] = $preCarriageValues[$type] ?? 'Air';

        $ports = $this->getActivePorts($type, '', 200);
        if ($ports === []) {
            $ports = $this->getActivePorts('', '', 200);
        }
        if ($ports === []) {
            return $defaults;
        }

        $wantedCode = strtoupper(trim((string) ($defaults['shipping_port'] ?? '')));
        $wantedCity = strtolower(trim((string) ($defaults['port_of_loading'] ?? '')));
        $match = null;

        if ($wantedCode !== '' && $wantedCode !== 'INABG1') {
            foreach ($ports as $port) {
                if (strtoupper(trim((string) ($port['port_code'] ?? ''))) === $wantedCode) {
                    $match = $port;
                    break;
                }
            }
        }
        if ($match === null && $wantedCity !== '') {
            foreach ($ports as $port) {
                $city = strtolower(trim((string) ($port['city'] ?? '')));
                $name = strtolower(trim((string) ($port['port_name'] ?? '')));
                if ($city !== '' && (strpos($wantedCity, $city) !== false || strpos($city, $wantedCity) !== false)) {
                    $match = $port;
                    break;
                }
                if ($name !== '' && strpos($name, $wantedCity) !== false) {
                    $match = $port;
                    break;
                }
            }
        }
        if ($match === null) {
            $match = $ports[0];
        }

        $defaults['port_of_loading'] = self::formatPortOption($match);
        $code = strtoupper(trim((string) ($match['port_code'] ?? '')));
        if ($code !== '') {
            $defaults['shipping_port'] = $code;
        }

        return $defaults;
    }

    public static function isValidPortType(string $type): bool
    {
        return array_key_exists($type, self::portTypeLabels());
    }

    public static function normalizePortType(string $type): string
    {
        $type = strtolower(trim($type));
        $aliases = [
            'seaport' => self::TYPE_SEA,
            'sea_port' => self::TYPE_SEA,
            'sea port' => self::TYPE_SEA,
            'airport' => self::TYPE_AIR,
            'air_port' => self::TYPE_AIR,
            'air port' => self::TYPE_AIR,
            'inlandport' => self::TYPE_INLAND,
            'inland_port' => self::TYPE_INLAND,
            'inland port' => self::TYPE_INLAND,
            'inland ports' => self::TYPE_INLAND,
            'dryport' => self::TYPE_DRY,
            'dry_port' => self::TYPE_DRY,
            'dry port' => self::TYPE_DRY,
            'dry ports' => self::TYPE_DRY,
        ];
        if (isset($aliases[$type])) {
            return $aliases[$type];
        }

        return self::isValidPortType($type) ? $type : '';
    }

    public function getDefaultCountryId(): int
    {
        $stmt = $this->conn->prepare(
            "SELECT id FROM countries
             WHERE UPPER(country_code) IN ('IN', 'IND') OR name IN ('India', 'INDIA')
             ORDER BY id ASC LIMIT 1"
        );
        if (!$stmt) {
            return 105;
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['id'] ?? 105);
    }

    public function countryExists(int $countryId): bool
    {
        if ($countryId <= 0) {
            return false;
        }
        $stmt = $this->conn->prepare('SELECT id FROM countries WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $countryId);
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function getCountryRow(int $countryId): ?array
    {
        if ($countryId <= 0) {
            return null;
        }
        $stmt = $this->conn->prepare('SELECT id, name, country_code FROM countries WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $countryId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function getPorts(
        int $page = 1,
        int $limit = 20,
        string $search = '',
        string $status = '',
        string $portType = ''
    ): array {
        $page = max(1, $page);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($page - 1) * $limit;
        $portType = self::normalizePortType($portType);

        $where = [];
        $types = '';
        $params = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(pm.port_name LIKE ? OR pm.port_code LIKE ? OR pm.city LIKE ? OR pm.pincode LIKE ? OR c.name LIKE ?)';
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if ($portType !== '') {
            $where[] = 'pm.port_type = ?';
            $types .= 's';
            $params[] = $portType;
        }

        if ($status === 'active') {
            $where[] = 'pm.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'pm.is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';

        $empty = [
            'ports' => [],
            'totalRecords' => 0,
            'totalPages' => 1,
            'currentPage' => $page,
            'limit' => $limit,
        ];

        $countSql = 'SELECT COUNT(*) AS total
                     FROM shipping_port_master pm
                     LEFT JOIN countries c ON c.id = pm.country_id'
            . $whereSql;
        $countStmt = $this->conn->prepare($countSql);
        if (!$countStmt) {
            return $empty;
        }
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $totalRecords = (int) (($countStmt->get_result()->fetch_assoc()['total'] ?? 0));
        $countStmt->close();

        $sql = 'SELECT pm.id, pm.port_name, pm.port_code, pm.port_type, pm.city, pm.country_id, pm.pincode,
                       pm.is_active, pm.user_id, pm.created_at, pm.updated_at,
                       c.name AS country_name, c.country_code,
                       u.name AS user_name
                FROM shipping_port_master pm
                LEFT JOIN countries c ON c.id = pm.country_id
                LEFT JOIN vp_users u ON u.id = pm.user_id'
            . $whereSql
            . ' ORDER BY pm.port_type ASC, pm.port_name ASC
                LIMIT ? OFFSET ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            $empty['totalRecords'] = $totalRecords;
            $empty['totalPages'] = max(1, (int) ceil($totalRecords / $limit));
            return $empty;
        }

        $listTypes = $types . 'ii';
        $listParams = array_merge($params, [$limit, $offset]);
        $stmt->bind_param($listTypes, ...$listParams);
        $stmt->execute();
        $ports = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $labels = self::portTypeLabels();
        foreach ($ports as &$row) {
            $type = (string) ($row['port_type'] ?? '');
            $row['port_type_label'] = $labels[$type] ?? $type;
        }
        unset($row);

        return [
            'ports' => $ports,
            'totalRecords' => $totalRecords,
            'totalPages' => max(1, (int) ceil($totalRecords / max(1, $limit))),
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
            'SELECT pm.id, pm.port_name, pm.port_code, pm.port_type, pm.city, pm.country_id, pm.pincode,
                    pm.is_active, pm.user_id, pm.created_at, pm.updated_at,
                    c.name AS country_name, c.country_code
             FROM shipping_port_master pm
             LEFT JOIN countries c ON c.id = pm.country_id
             WHERE pm.id = ?
             LIMIT 1'
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

        $type = (string) ($row['port_type'] ?? '');
        $row['port_type_label'] = self::portTypeLabels()[$type] ?? $type;

        return $row;
    }

    /**
     * Active ports for invoice / shipping dropdowns.
     *
     * @return list<array<string, mixed>>
     */
    public function getActivePorts(string $portType = '', string $search = '', int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $portType = self::normalizePortType($portType);
        $search = trim($search);

        $where = ['pm.is_active = 1'];
        $types = '';
        $params = [];

        if ($portType !== '') {
            $where[] = 'pm.port_type = ?';
            $types .= 's';
            $params[] = $portType;
        }
        if ($search !== '') {
            $like = '%' . $search . '%';
            $where[] = '(pm.port_name LIKE ? OR pm.port_code LIKE ? OR pm.city LIKE ?)';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $sql = 'SELECT pm.id, pm.port_name, pm.port_code, pm.port_type, pm.city, pm.country_id, pm.pincode,
                       c.name AS country_name, c.country_code
                FROM shipping_port_master pm
                LEFT JOIN countries c ON c.id = pm.country_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY pm.port_name ASC
                LIMIT ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $types .= 'i';
        $params[] = $limit;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $labels = self::portTypeLabels();
        foreach ($rows as &$row) {
            $type = (string) ($row['port_type'] ?? '');
            $row['port_type_label'] = $labels[$type] ?? $type;
        }
        unset($row);

        return $rows;
    }

    public function portCodeExists(string $portCode, ?int $excludeId = null): bool
    {
        $portCode = strtoupper(trim($portCode));
        if ($portCode === '') {
            return false;
        }

        if ($excludeId !== null && $excludeId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT id FROM shipping_port_master
                 WHERE UPPER(TRIM(port_code)) = ?
                   AND id != ?
                 LIMIT 1'
            );
            $stmt->bind_param('si', $portCode, $excludeId);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT id FROM shipping_port_master
                 WHERE UPPER(TRIM(port_code)) = ?
                 LIMIT 1'
            );
            $stmt->bind_param('s', $portCode);
        }
        $stmt->execute();
        $exists = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    public function checkPortCode(string $portCode, ?int $excludeId = null): array
    {
        return ['exists' => $this->portCodeExists($portCode, $excludeId)];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string,id?:int}
     */
    public function insertPort(array $data, int $userId = 0): array
    {
        $parsed = $this->validatePayload($data);
        if (!$parsed['success']) {
            return $parsed;
        }
        $row = $parsed['row'];

        if ($this->portCodeExists($row['port_code'])) {
            return ['success' => false, 'message' => 'This port code already exists.'];
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO shipping_port_master (port_name, port_code, port_type, city, country_id, pincode, is_active, user_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $portName = $row['port_name'];
        $portCode = $row['port_code'];
        $portType = $row['port_type'];
        $city = $row['city'];
        $countryId = (int) $row['country_id'];
        $pincode = $row['pincode'];
        $isActive = (int) $row['is_active'];
        $stmt->bind_param(
            'ssssisii',
            $portName,
            $portCode,
            $portType,
            $city,
            $countryId,
            $pincode,
            $isActive,
            $userId
        );

        try {
            $ok = $stmt->execute();
            $newId = (int) $this->conn->insert_id;
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save port: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Port added successfully.', 'id' => $newId]
            : ['success' => false, 'message' => 'Could not save port: ' . $error];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string,id?:int}
     */
    public function updatePort(int $id, array $data): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid port id.'];
        }
        if (!$this->getRecord($id)) {
            return ['success' => false, 'message' => 'Port not found.'];
        }

        $parsed = $this->validatePayload($data);
        if (!$parsed['success']) {
            return $parsed;
        }
        $row = $parsed['row'];

        if ($this->portCodeExists($row['port_code'], $id)) {
            return ['success' => false, 'message' => 'This port code already exists.'];
        }

        $stmt = $this->conn->prepare(
            'UPDATE shipping_port_master
             SET port_name = ?, port_code = ?, port_type = ?, city = ?, country_id = ?, pincode = ?, is_active = ?
             WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $portName = $row['port_name'];
        $portCode = $row['port_code'];
        $portType = $row['port_type'];
        $city = $row['city'];
        $countryId = (int) $row['country_id'];
        $pincode = $row['pincode'];
        $isActive = (int) $row['is_active'];
        $stmt->bind_param(
            'ssssisii',
            $portName,
            $portCode,
            $portType,
            $city,
            $countryId,
            $pincode,
            $isActive,
            $id
        );

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
        } catch (mysqli_sql_exception $e) {
            $stmt->close();
            return ['success' => false, 'message' => 'Could not save port: ' . $e->getMessage()];
        }

        return $ok
            ? ['success' => true, 'message' => 'Port updated successfully.', 'id' => $id]
            : ['success' => false, 'message' => 'Could not save port: ' . $error];
    }

    public function setStatus(int $id, int $isActive): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid port id.'];
        }
        $isActive = $isActive ? 1 : 0;
        $stmt = $this->conn->prepare('UPDATE shipping_port_master SET is_active = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('ii', $isActive, $id);
        $ok = $stmt->execute();
        $error = $stmt->error;
        $stmt->close();

        return $ok
            ? ['success' => true, 'message' => $isActive ? 'Port activated.' : 'Port deactivated.']
            : ['success' => false, 'message' => 'Could not update status: ' . $error];
    }

    public function deletePort(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid port id.'];
        }

        $stmt = $this->conn->prepare('DELETE FROM shipping_port_master WHERE id = ?');
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
            return ['success' => false, 'message' => 'Could not delete port.'];
        }

        return $ok
            ? ['success' => true, 'message' => 'Port deleted successfully.']
            : ['success' => false, 'message' => 'Could not delete port: ' . $error];
    }

    /**
     * @param array<string, mixed> $data
     * @return array{success:bool,message:string,row?:array<string,mixed>}
     */
    private function validatePayload(array $data): array
    {
        $portName = trim((string) ($data['port_name'] ?? ''));
        $portCode = strtoupper(preg_replace('/\s+/', '', (string) ($data['port_code'] ?? '')) ?? '');
        $portType = self::normalizePortType((string) ($data['port_type'] ?? ''));
        $city = trim((string) ($data['city'] ?? ''));
        $countryId = (int) ($data['country_id'] ?? 0);
        $pincode = strtoupper(preg_replace('/\s+/', '', (string) ($data['pincode'] ?? '')) ?? '');
        $isActive = array_key_exists('is_active', $data) ? ((int) $data['is_active'] ? 1 : 0) : 1;

        if ($portName === '') {
            return ['success' => false, 'message' => 'Port name is required.'];
        }
        if (mb_strlen($portName) > 255) {
            return ['success' => false, 'message' => 'Port name is too long.'];
        }
        if ($portCode === '') {
            return ['success' => false, 'message' => 'Port code is required.'];
        }
        if (!preg_match('/^[A-Z0-9]{2,20}$/', $portCode)) {
            return ['success' => false, 'message' => 'Port code must be 2–20 letters or numbers (e.g. INABG1).'];
        }
        if ($portType === '') {
            return ['success' => false, 'message' => 'Please select a port type.'];
        }
        if ($city === '') {
            return ['success' => false, 'message' => 'City is required.'];
        }
        if (mb_strlen($city) > 120) {
            return ['success' => false, 'message' => 'City name is too long.'];
        }
        if (!$this->countryExists($countryId)) {
            return ['success' => false, 'message' => 'Please select a valid country.'];
        }
        if ($pincode === '') {
            return ['success' => false, 'message' => 'PIN / postal code is required.'];
        }

        $country = $this->getCountryRow($countryId);
        $countryCode = strtoupper(trim((string) ($country['country_code'] ?? '')));
        $countryName = strtoupper(trim((string) ($country['name'] ?? '')));
        $isIndia = in_array($countryCode, ['IN', 'IND'], true) || $countryName === 'INDIA';
        if ($isIndia) {
            if (!preg_match('/^\d{6}$/', $pincode)) {
                return ['success' => false, 'message' => 'India PIN must be a 6-digit number.'];
            }
        } elseif (strlen($pincode) < 3 || strlen($pincode) > 12) {
            return ['success' => false, 'message' => 'Postal code must be 3–12 characters.'];
        }

        return [
            'success' => true,
            'message' => '',
            'row' => [
                'port_name' => $portName,
                'port_code' => $portCode,
                'port_type' => $portType,
                'city' => $city,
                'country_id' => $countryId,
                'pincode' => $pincode,
                'is_active' => $isActive,
            ],
        ];
    }

    private function ensureModule(): void
    {
        $check = @$this->conn->query("SELECT id FROM modules WHERE slug = 'ports' LIMIT 1");
        $moduleId = 0;
        $icon = '<i class="fas fa-plane-departure mr-2"></i>';
        if ($check && ($row = $check->fetch_assoc())) {
            $moduleId = (int) ($row['id'] ?? 0);
            if ($moduleId > 0) {
                $this->ensureMenuIcon($icon);
                $this->ensurePermissions($moduleId);
                return;
            }
        }

        $parentId = 0;
        $parent = @$this->conn->query(
            "SELECT parent_id FROM modules
             WHERE slug IN ('materials', 'account_groups', 'languages', 'sizes') AND parent_id > 0
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

        $stmt = $this->conn->prepare(
            'INSERT INTO modules (parent_id, module_name, slug, `action`, font_awesome_icon, active, user_id, sort_order)
             VALUES (?, ?, ?, ?, ?, 1, 1, 225)'
        );
        if (!$stmt) {
            return;
        }
        $name = 'Port Master';
        $slug = 'ports';
        $action = 'list';
        $stmt->bind_param('issss', $parentId, $name, $slug, $action, $icon);
        try {
            $stmt->execute();
            $moduleId = (int) $this->conn->insert_id;
        } catch (Throwable $e) {
            $moduleId = 0;
        }
        $stmt->close();

        if ($moduleId > 0) {
            $this->ensurePermissions($moduleId);
        }
    }

    private function ensureMenuIcon(string $icon): void
    {
        $stmt = $this->conn->prepare(
            "UPDATE modules
             SET font_awesome_icon = ?
             WHERE slug = 'ports'
               AND (font_awesome_icon IS NULL OR font_awesome_icon NOT LIKE '%fa-plane-departure%')"
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $icon);
        try {
            $stmt->execute();
        } catch (Throwable $e) {
            // Icon update is best-effort.
        }
        $stmt->close();
    }

    private function ensurePermissions(int $moduleId): void
    {
        if ($moduleId <= 0) {
            return;
        }

        $check = $this->conn->prepare('SELECT id FROM vp_permissions WHERE module_id = ? LIMIT 1');
        if (!$check) {
            return;
        }
        $check->bind_param('i', $moduleId);
        $check->execute();
        $exists = $check->get_result()->num_rows > 0;
        $check->close();
        if ($exists) {
            return;
        }

        $accessRows = @$this->conn->query("SELECT access_name FROM vp_role_access WHERE is_active = '1' ORDER BY id ASC");
        if (!$accessRows) {
            return;
        }
        $actions = [];
        while ($row = $accessRows->fetch_assoc()) {
            $actions[] = (string) ($row['access_name'] ?? '');
        }
        $actions = array_values(array_filter($actions));
        if ($actions === []) {
            return;
        }

        $roleRows = @$this->conn->query("SELECT id FROM vp_roles WHERE is_active = '1' ORDER BY id ASC");
        $roleIds = [];
        if ($roleRows) {
            while ($row = $roleRows->fetch_assoc()) {
                $roleIds[] = (int) ($row['id'] ?? 0);
            }
        }
        $roleIds = array_values(array_filter($roleIds));

        $moduleName = 'Port Master';
        $userId = 1;
        foreach ($actions as $actionName) {
            $permStmt = $this->conn->prepare(
                'INSERT INTO vp_permissions (module_id, module_name, action_name, is_active, user_id)
                 VALUES (?, ?, ?, 1, ?)'
            );
            if (!$permStmt) {
                continue;
            }
            $permStmt->bind_param('issi', $moduleId, $moduleName, $actionName, $userId);
            try {
                if (!$permStmt->execute()) {
                    $permStmt->close();
                    continue;
                }
                $permissionId = (int) $this->conn->insert_id;
            } catch (Throwable $e) {
                $permStmt->close();
                continue;
            }
            $permStmt->close();

            foreach ($roleIds as $roleId) {
                $rpStmt = $this->conn->prepare(
                    'INSERT INTO vp_role_permissions (role_id, permission_id, user_id) VALUES (?, ?, ?)'
                );
                if (!$rpStmt) {
                    continue;
                }
                $rpStmt->bind_param('iii', $roleId, $permissionId, $userId);
                try {
                    $rpStmt->execute();
                } catch (Throwable $e) {
                    // Best-effort role assignment.
                }
                $rpStmt->close();
            }
        }
    }
}
