<?php

class Publisher
{
    private mysqli $conn;

    private const LIST_COLUMNS = 'id, publishers_id, publishers, contact_name, publisher_email, country_code, publisher_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, webpage, stock_replenishment_months, discount, is_active, create_at, update_at';

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array<string, string>
     */
    public function normalizePublisherFormData(array $data): array
    {
        return [
            'contact_name' => trim((string)($data['contact_name'] ?? '')),
            'publisher_email' => trim((string)($data['publisher_email'] ?? '')),
            'country_code' => trim((string)($data['country_code'] ?? '')),
            'publisher_phone' => trim((string)($data['publisher_phone'] ?? '')),
            'alt_phone' => trim((string)($data['alt_phone'] ?? '')),
            'gst_number' => trim((string)($data['gst_number'] ?? '')),
            'pan_number' => trim((string)($data['pan_number'] ?? '')),
            'address' => trim((string)($data['address'] ?? '')),
            'city' => trim((string)($data['city'] ?? '')),
            'state' => trim((string)($data['state'] ?? '')),
            'country' => trim((string)($data['country'] ?? '')),
            'postal_code' => trim((string)($data['postal_code'] ?? '')),
            'webpage' => (string)($data['webpage'] ?? '0') === '1' ? '1' : '0',
            'stock_replenishment_months' => trim((string)($data['stock_replenishment_months'] ?? '')) === ''
                ? 0
                : max(0, (int)$data['stock_replenishment_months']),
            'discount' => trim((string)($data['discount'] ?? '')) === ''
                ? 0.0
                : max(0.0, (float)$data['discount']),
        ];
    }

    private function publisherFieldExists(string $column, string $value, ?int $excludeId = null): bool
    {
        $allowed = ['publisher_phone', 'publisher_email', 'gst_number', 'pan_number'];
        if (!in_array($column, $allowed, true)) {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        if ($column === 'publisher_email') {
            $sql = "SELECT id FROM vp_publishers WHERE LOWER(TRIM(publisher_email)) = LOWER(TRIM(?)) AND TRIM(COALESCE(publisher_email, '')) <> ''";
        } else {
            $sql = 'SELECT id FROM vp_publishers WHERE ' . $column . ' = ?';
        }

        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param('si', $value, $excludeId);
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

    private function validatePublisherUniqueness(
        ?string $phone,
        ?string $email,
        ?string $gst,
        ?string $pan,
        ?int $excludeId = null
    ): ?array {
        if ($phone !== null && trim($phone) !== '' && $this->publisherFieldExists('publisher_phone', trim($phone), $excludeId)) {
            return ['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.'];
        }
        if ($email !== null && trim($email) !== '' && $this->publisherFieldExists('publisher_email', trim($email), $excludeId)) {
            return ['success' => false, 'message' => 'Email already exists. Please use a different email.'];
        }
        if ($gst !== null && trim($gst) !== '' && $this->publisherFieldExists('gst_number', trim($gst), $excludeId)) {
            return ['success' => false, 'message' => 'GST number already exists. Please use a different GST number.'];
        }
        if ($pan !== null && trim($pan) !== '' && $this->publisherFieldExists('pan_number', trim($pan), $excludeId)) {
            return ['success' => false, 'message' => 'PAN number already exists. Please use a different PAN number.'];
        }

        return null;
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
            $where[] = '(publishers LIKE ? OR publishers_id = ? OR id = ? OR city LIKE ? OR state LIKE ? OR contact_name LIKE ? OR publisher_phone LIKE ?)';
            $types .= 'siissss';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
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
            'SELECT ' . self::LIST_COLUMNS . '
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
        $stmt = $this->conn->prepare('SELECT ' . self::LIST_COLUMNS . ' FROM vp_publishers WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function publisherNameExists(string $name, ?int $excludeLocalId = null): bool
    {
        $name = trim($name);
        if ($name === '') {
            return false;
        }

        if ($excludeLocalId !== null && $excludeLocalId > 0) {
            $stmt = $this->conn->prepare(
                'SELECT id FROM vp_publishers WHERE LOWER(TRIM(publishers)) = LOWER(TRIM(?)) AND id != ? LIMIT 1'
            );
            $stmt->bind_param('si', $name, $excludeLocalId);
        } else {
            $stmt = $this->conn->prepare(
                'SELECT id FROM vp_publishers WHERE LOWER(TRIM(publishers)) = LOWER(TRIM(?)) LIMIT 1'
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

    public function checkPublisherName(string $name, ?int $excludeLocalId = null): array
    {
        return ['exists' => $this->isDuplicatePublisherName($name, $excludeLocalId)];
    }

    public function isDuplicatePublisherName(string $name, ?int $excludeLocalId = null): bool
    {
        if ($excludeLocalId !== null && $excludeLocalId > 0) {
            $existing = $this->getPublisherById($excludeLocalId);
            if ($existing && namesEqualCi($name, (string)($existing['publishers'] ?? ''))) {
                return false;
            }
        }

        return $this->publisherNameExists($name, $excludeLocalId);
    }

    public function savePublisher(?int $id, string $name, int $isActive, array $extra = []): array
    {
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        $fields = $this->normalizePublisherFormData($extra);
        if ($name === '') {
            return ['success' => false, 'message' => 'Publisher name is required.'];
        }

        if (!$id || $id <= 0) {
            return ['success' => false, 'message' => 'Publisher id is required for update.'];
        }

        $existing = $this->getPublisherById($id);
        if (!$existing) {
            return ['success' => false, 'message' => 'Publisher not found.'];
        }

        if (!namesEqualCi($name, (string)($existing['publishers'] ?? ''))
            && $this->publisherNameExists($name, $id)) {
            return ['success' => false, 'message' => 'Publisher name already exists'];
        }

        $duplicate = $this->validatePublisherUniqueness(
            $fields['publisher_phone'],
            $fields['publisher_email'],
            $fields['gst_number'],
            $fields['pan_number'],
            $id
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $stmt = $this->conn->prepare(
            'UPDATE vp_publishers SET publishers = ?, contact_name = ?, publisher_email = ?, country_code = ?, publisher_phone = ?, alt_phone = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, webpage = ?, stock_replenishment_months = ?, discount = ?, is_active = ? WHERE id = ?'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stockReplenishmentMonths = (int)$fields['stock_replenishment_months'];
        $discount = (float)$fields['discount'];
        $stmt->bind_param(
            'sssssssssssssiddi',
            $name,
            $fields['contact_name'],
            $fields['publisher_email'],
            $fields['country_code'],
            $fields['publisher_phone'],
            $fields['alt_phone'],
            $fields['gst_number'],
            $fields['pan_number'],
            $fields['address'],
            $fields['city'],
            $fields['state'],
            $fields['country'],
            $fields['postal_code'],
            $webpage,
            $stockReplenishmentMonths,
            $discount,
            $isActive,
            $id
        );

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

    public function insertPublisher(int $publishersId, string $name, int $isActive, array $extra = []): array
    {
        $publishersId = (int) $publishersId;
        $name = trim($name);
        $isActive = $isActive ? 1 : 0;
        $fields = $this->normalizePublisherFormData($extra);

        if ($publishersId <= 0) {
            return ['success' => false, 'message' => 'Remote publisher vendor_id is required.'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Publisher name is required.'];
        }

        if ($this->publisherNameExists($name)) {
            return ['success' => false, 'message' => 'Publisher name already exists'];
        }

        $duplicate = $this->validatePublisherUniqueness(
            $fields['publisher_phone'],
            $fields['publisher_email'],
            $fields['gst_number'],
            $fields['pan_number']
        );
        if ($duplicate !== null) {
            return $duplicate;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_publishers (publishers_id, publishers, contact_name, publisher_email, country_code, publisher_phone, alt_phone, gst_number, pan_number, address, city, state, country, postal_code, webpage, stock_replenishment_months, discount, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stockReplenishmentMonths = (int)$fields['stock_replenishment_months'];
        $discount = (float)$fields['discount'];
        $stmt->bind_param(
            'issssssssssssssidi',
            $publishersId,
            $name,
            $fields['contact_name'],
            $fields['publisher_email'],
            $fields['country_code'],
            $fields['publisher_phone'],
            $fields['alt_phone'],
            $fields['gst_number'],
            $fields['pan_number'],
            $fields['address'],
            $fields['city'],
            $fields['state'],
            $fields['country'],
            $fields['postal_code'],
            $webpage,
            $stockReplenishmentMonths,
            $discount,
            $isActive
        );

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

    public function getBankDetailsById(int $publisherId)
    {
        global $secretKey;
        if (!isset($secretKey)) {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot decrypt bank details.'];
        }

        $sql = "SELECT publisher_id,
                CAST(AES_DECRYPT(account_holder_name, UNHEX(SHA2(?,512))) AS CHAR) AS account_name,
                CAST(AES_DECRYPT(account_number, UNHEX(SHA2(?,512))) AS CHAR) AS account_number,
                CAST(AES_DECRYPT(ifsc_code, UNHEX(SHA2(?,512))) AS CHAR) AS ifsc_code,
                CAST(AES_DECRYPT(bank_name, UNHEX(SHA2(?,512))) AS CHAR) AS bank_name,
                CAST(AES_DECRYPT(branch_name, UNHEX(SHA2(?,512))) AS CHAR) AS branch_name,
                is_active
                FROM publisher_bank_details WHERE publisher_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('sssssi', $secretKey, $secretKey, $secretKey, $secretKey, $secretKey, $publisherId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function saveBankDetails(array $data): array
    {
        global $secretKey;
        if (!isset($secretKey)) {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }

        $sql = "INSERT INTO publisher_bank_details (publisher_id, account_holder_name, account_number, ifsc_code, bank_name, branch_name, is_active)
                VALUES (?, AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), AES_ENCRYPT(?, UNHEX(SHA2(?,512))), ?)
                ON DUPLICATE KEY UPDATE account_holder_name = VALUES(account_holder_name), account_number = VALUES(account_number), ifsc_code = VALUES(ifsc_code), bank_name = VALUES(bank_name), branch_name = VALUES(branch_name), is_active = VALUES(is_active)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param(
            'issssssssssi',
            $data['publisher_id'],
            $data['account_name'],
            $secretKey,
            $data['account_number'],
            $secretKey,
            $data['ifsc_code'],
            $secretKey,
            $data['bank_name'],
            $secretKey,
            $data['branch_name'],
            $secretKey,
            $data['bdStatus']
        );
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Bank details saved successfully.'];
        }
        $error = $stmt->error;
        $stmt->close();

        return [
            'success' => false,
            'message' => 'Insert failed: ' . $error . '. Please check your input and fill all required fields correctly.',
        ];
    }

    public function updateBankDetails(array $data): array
    {
        global $secretKey;
        if (!isset($secretKey)) {
            return ['success' => false, 'message' => 'Secret key is not set. Cannot encrypt bank details.'];
        }

        $sql = "UPDATE publisher_bank_details SET
                account_holder_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))),
                account_number = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))),
                ifsc_code = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))),
                bank_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))),
                branch_name = AES_ENCRYPT(?, UNHEX(SHA2(?, 512))),
                is_active = ?
                WHERE publisher_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param(
            'ssssssssssii',
            $data['account_name'],
            $secretKey,
            $data['account_number'],
            $secretKey,
            $data['ifsc_code'],
            $secretKey,
            $data['bank_name'],
            $secretKey,
            $data['branch_name'],
            $secretKey,
            $data['bdStatus'],
            $data['publisher_id']
        );
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Bank details updated successfully.'];
        }
        $error = $stmt->error;
        $stmt->close();

        return [
            'success' => false,
            'message' => 'Update failed: ' . $error . '. Please check your input and fill all required fields correctly.',
        ];
    }

    public function deletePublisher(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid publisher id.'];
        }

        $bankStmt = $this->conn->prepare('DELETE FROM publisher_bank_details WHERE publisher_id = ?');
        if ($bankStmt) {
            $bankStmt->bind_param('i', $id);
            $bankStmt->execute();
            $bankStmt->close();
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
