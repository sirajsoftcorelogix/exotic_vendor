<?php

class Publisher
{
    private mysqli $conn;

    private const LIST_COLUMNS = 'p.id, p.publishers_id, p.publishers, p.display_name, p.website, p.contact_name, p.publisher_email, p.publisher_email_is_primary, p.country_code, p.publisher_phone, p.publisher_phone_is_whatsapp, p.gst_number, p.pan_number, p.address, p.city, p.state, p.country, p.postal_code, p.webpage, p.stock_replenishment_months, p.discount, p.broker_id, bu.name AS broker_name, p.is_active, p.create_at, p.update_at';

    private const LIST_FROM = ' FROM vp_publishers p LEFT JOIN vp_users bu ON bu.id = p.broker_id AND bu.is_deleted = 0';

    private const MAX_ALT_PHONES = 5;

    private const MAX_ALT_EMAILS = 5;

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
            'display_name' => trim((string)($data['display_name'] ?? '')),
            'website' => trim((string)($data['website'] ?? '')),
            'contact_name' => trim((string)($data['contact_name'] ?? '')),
            'publisher_email' => trim((string)($data['publisher_email'] ?? '')),
            'publisher_email_is_primary' => (string)($data['publisher_email_is_primary'] ?? '0') === '1' ? 1 : 0,
            'country_code' => trim((string)($data['country_code'] ?? '')),
            'publisher_phone' => trim((string)($data['publisher_phone'] ?? '')),
            'publisher_phone_is_whatsapp' => (string)($data['publisher_phone_is_whatsapp'] ?? '0') === '1' ? 1 : 0,
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
            'broker_id' => trim((string)($data['broker_id'] ?? '')) === ''
                ? null
                : max(0, (int)$data['broker_id']),
            'alt_phones' => $this->parseAlternatePhonesFromPost($data),
            'alt_emails' => $this->parseAlternateEmailsFromPost($data),
        ];
    }

    /**
     * @return array<int, array{phone:string,is_whatsapp:int}>
     */
    public function parseAlternatePhonesFromPost(array $data): array
    {
        $rows = [];
        $raw = $data['alt_phones'] ?? [];
        if (!is_array($raw)) {
            return $rows;
        }

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $phone = preg_replace('/\D+/', '', trim((string)($item['phone'] ?? '')));
            if ($phone === '') {
                continue;
            }
            if (strlen($phone) > 10) {
                $phone = substr($phone, 0, 10);
            }
            $rows[] = [
                'phone' => $phone,
                'is_whatsapp' => !empty($item['is_whatsapp']) ? 1 : 0,
            ];
            if (count($rows) >= self::MAX_ALT_PHONES) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{email:string,is_primary:int}>
     */
    public function parseAlternateEmailsFromPost(array $data): array
    {
        $rows = [];
        $raw = $data['alt_emails'] ?? [];
        if (!is_array($raw)) {
            return $rows;
        }

        foreach ($raw as $item) {
            if (!is_array($item)) {
                continue;
            }
            $email = trim((string)($item['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $rows[] = [
                'email' => $email,
                'is_primary' => !empty($item['is_primary']) ? 1 : 0,
            ];
            if (count($rows) >= self::MAX_ALT_EMAILS) {
                break;
            }
        }

        return $rows;
    }

    /**
     * @return array<int, array{phone:string,is_whatsapp:int}>
     */
    public function getPhonesByPublisherId(int $publisherId): array
    {
        if ($publisherId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT phone, is_whatsapp FROM publisher_phones WHERE publisher_id = ? ORDER BY sort_order ASC, id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $publisherId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = [
                'phone' => (string)($row['phone'] ?? ''),
                'is_whatsapp' => (int)($row['is_whatsapp'] ?? 0),
            ];
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<int, array{email:string,is_primary:int}>
     */
    public function getEmailsByPublisherId(int $publisherId): array
    {
        if ($publisherId <= 0) {
            return [];
        }

        $stmt = $this->conn->prepare(
            'SELECT email, is_primary FROM publisher_emails WHERE publisher_id = ? ORDER BY sort_order ASC, id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $publisherId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $rows[] = [
                'email' => (string)($row['email'] ?? ''),
                'is_primary' => (int)($row['is_primary'] ?? 0),
            ];
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @param array<int, array{phone:string,is_whatsapp:int}> $phones
     * @param array<int, array{email:string,is_primary:int}> $emails
     */
    private function replacePublisherContacts(int $publisherId, array $phones, array $emails): ?array
    {
        if ($publisherId <= 0) {
            return ['success' => false, 'message' => 'Invalid publisher id for contacts.'];
        }

        $deletePhones = $this->conn->prepare('DELETE FROM publisher_phones WHERE publisher_id = ?');
        $deleteEmails = $this->conn->prepare('DELETE FROM publisher_emails WHERE publisher_id = ?');
        if (!$deletePhones || !$deleteEmails) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }

        $deletePhones->bind_param('i', $publisherId);
        $deleteEmails->bind_param('i', $publisherId);
        if (!$deletePhones->execute() || !$deleteEmails->execute()) {
            $error = $deletePhones->error ?: $deleteEmails->error;
            $deletePhones->close();
            $deleteEmails->close();

            return ['success' => false, 'message' => 'Could not update publisher contacts: ' . $error];
        }
        $deletePhones->close();
        $deleteEmails->close();

        if ($phones !== []) {
            $insertPhone = $this->conn->prepare(
                'INSERT INTO publisher_phones (publisher_id, phone, is_whatsapp, sort_order) VALUES (?, ?, ?, ?)'
            );
            if (!$insertPhone) {
                return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
            }
            foreach ($phones as $index => $row) {
                $phone = $row['phone'];
                $isWhatsapp = (int)$row['is_whatsapp'];
                $sortOrder = $index;
                $insertPhone->bind_param('isii', $publisherId, $phone, $isWhatsapp, $sortOrder);
                if (!$insertPhone->execute()) {
                    $error = $insertPhone->error;
                    $insertPhone->close();

                    return ['success' => false, 'message' => 'Could not save alternate phone: ' . $error];
                }
            }
            $insertPhone->close();
        }

        if ($emails !== []) {
            $insertEmail = $this->conn->prepare(
                'INSERT INTO publisher_emails (publisher_id, email, is_primary, sort_order) VALUES (?, ?, ?, ?)'
            );
            if (!$insertEmail) {
                return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
            }
            foreach ($emails as $index => $row) {
                $email = $row['email'];
                $isPrimary = (int)$row['is_primary'];
                $sortOrder = $index;
                $insertEmail->bind_param('isii', $publisherId, $email, $isPrimary, $sortOrder);
                if (!$insertEmail->execute()) {
                    $error = $insertEmail->error;
                    $insertEmail->close();

                    return ['success' => false, 'message' => 'Could not save alternate email: ' . $error];
                }
            }
            $insertEmail->close();
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $publishers
     */
    private function attachContactsToPublishers(array &$publishers): void
    {
        if ($publishers === []) {
            return;
        }

        $ids = [];
        foreach ($publishers as $publisher) {
            $id = (int)($publisher['id'] ?? 0);
            if ($id > 0) {
                $ids[] = $id;
            }
        }
        if ($ids === []) {
            return;
        }

        $phonesByPublisher = array_fill_keys($ids, []);
        $emailsByPublisher = array_fill_keys($ids, []);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));

        $phoneStmt = $this->conn->prepare(
            "SELECT publisher_id, phone, is_whatsapp FROM publisher_phones WHERE publisher_id IN ($placeholders) ORDER BY sort_order ASC, id ASC"
        );
        if ($phoneStmt) {
            $phoneStmt->bind_param($types, ...$ids);
            $phoneStmt->execute();
            $phoneResult = $phoneStmt->get_result();
            while ($phoneResult && ($row = $phoneResult->fetch_assoc())) {
                $publisherId = (int)($row['publisher_id'] ?? 0);
                if ($publisherId <= 0) {
                    continue;
                }
                $phonesByPublisher[$publisherId][] = [
                    'phone' => (string)($row['phone'] ?? ''),
                    'is_whatsapp' => (int)($row['is_whatsapp'] ?? 0),
                ];
            }
            $phoneStmt->close();
        }

        $emailStmt = $this->conn->prepare(
            "SELECT publisher_id, email, is_primary FROM publisher_emails WHERE publisher_id IN ($placeholders) ORDER BY sort_order ASC, id ASC"
        );
        if ($emailStmt) {
            $emailStmt->bind_param($types, ...$ids);
            $emailStmt->execute();
            $emailResult = $emailStmt->get_result();
            while ($emailResult && ($row = $emailResult->fetch_assoc())) {
                $publisherId = (int)($row['publisher_id'] ?? 0);
                if ($publisherId <= 0) {
                    continue;
                }
                $emailsByPublisher[$publisherId][] = [
                    'email' => (string)($row['email'] ?? ''),
                    'is_primary' => (int)($row['is_primary'] ?? 0),
                ];
            }
            $emailStmt->close();
        }

        foreach ($publishers as &$publisher) {
            $publisherId = (int)($publisher['id'] ?? 0);
            $publisher['alt_phones'] = $phonesByPublisher[$publisherId] ?? [];
            $publisher['alt_emails'] = $emailsByPublisher[$publisherId] ?? [];
        }
        unset($publisher);
    }

    private function attachContactsToPublisher(array &$publisher): void
    {
        $publisherId = (int)($publisher['id'] ?? 0);
        if ($publisherId <= 0) {
            $publisher['alt_phones'] = [];
            $publisher['alt_emails'] = [];

            return;
        }

        $publisher['alt_phones'] = $this->getPhonesByPublisherId($publisherId);
        $publisher['alt_emails'] = $this->getEmailsByPublisherId($publisherId);
    }

    private function contactPhoneExistsGlobally(string $phone, ?int $excludePublisherId = null): bool
    {
        $phone = trim($phone);
        if ($phone === '') {
            return false;
        }

        if ($this->publisherFieldExists('publisher_phone', $phone, $excludePublisherId)) {
            return true;
        }

        $sql = 'SELECT pp.id FROM publisher_phones pp INNER JOIN vp_publishers p ON p.id = pp.publisher_id WHERE BINARY pp.phone = ?';
        if ($excludePublisherId !== null && $excludePublisherId > 0) {
            $sql .= ' AND p.id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('si', $phone, $excludePublisherId);
        } else {
            $sql .= ' LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('s', $phone);
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    private function contactEmailExistsGlobally(string $email, ?int $excludePublisherId = null): bool
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return false;
        }

        if ($this->publisherFieldExists('publisher_email', $email, $excludePublisherId)) {
            return true;
        }

        $sql = 'SELECT pe.id FROM publisher_emails pe INNER JOIN vp_publishers p ON p.id = pe.publisher_id WHERE CONVERT(LOWER(TRIM(pe.email)) USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci';
        if ($excludePublisherId !== null && $excludePublisherId > 0) {
            $sql .= ' AND p.id != ? LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('si', $email, $excludePublisherId);
        } else {
            $sql .= ' LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('s', $email);
        }
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        return $exists;
    }

    /**
     * @param array<string, mixed> $fields
     */
    private function validatePublisherContacts(array $fields, ?int $excludePublisherId = null): ?array
    {
        $phones = [];
        $primaryPhone = trim((string)($fields['publisher_phone'] ?? ''));
        if ($primaryPhone !== '') {
            $phones[] = $primaryPhone;
        }
        foreach ($fields['alt_phones'] ?? [] as $row) {
            $phones[] = (string)($row['phone'] ?? '');
        }

        $normalizedPhones = array_values(array_filter($phones, static fn($value) => trim((string)$value) !== ''));
        if (count($normalizedPhones) !== count(array_unique($normalizedPhones))) {
            return ['success' => false, 'message' => 'Duplicate phone numbers are not allowed for the same publisher.'];
        }
        foreach ($normalizedPhones as $phone) {
            if ($this->contactPhoneExistsGlobally($phone, $excludePublisherId)) {
                return ['success' => false, 'message' => 'Phone number already exists. Please use a different phone number.'];
            }
        }

        $emails = [];
        $primaryEmail = trim((string)($fields['publisher_email'] ?? ''));
        if ($primaryEmail !== '') {
            if (!filter_var($primaryEmail, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Primary email address is invalid.'];
            }
            $emails[] = strtolower($primaryEmail);
        }
        foreach ($fields['alt_emails'] ?? [] as $row) {
            $emails[] = strtolower(trim((string)($row['email'] ?? '')));
        }

        $normalizedEmails = array_values(array_filter($emails, static fn($value) => trim((string)$value) !== ''));
        if (count($normalizedEmails) !== count(array_unique($normalizedEmails))) {
            return ['success' => false, 'message' => 'Duplicate email addresses are not allowed for the same publisher.'];
        }
        foreach ($normalizedEmails as $email) {
            if ($this->contactEmailExistsGlobally($email, $excludePublisherId)) {
                return ['success' => false, 'message' => 'Email already exists. Please use a different email.'];
            }
        }

        return null;
    }

    private function normalizeBrokerId(?int $brokerId): ?int
    {
        if ($brokerId === null || $brokerId <= 0) {
            return null;
        }

        return $brokerId;
    }

    private function validateBrokerId(?int $brokerId): ?array
    {
        $brokerId = $this->normalizeBrokerId($brokerId);
        if ($brokerId === null) {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT id FROM vp_users WHERE id = ? AND is_active = 1 AND is_deleted = 0 LIMIT 1'
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Could not validate broker.'];
        }
        $stmt->bind_param('i', $brokerId);
        $stmt->execute();
        $stmt->store_result();
        $ok = $stmt->num_rows > 0;
        $stmt->close();

        return $ok ? null : ['success' => false, 'message' => 'Selected broker is invalid or inactive.'];
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
            $where[] = '(p.publishers LIKE ? OR p.display_name LIKE ? OR p.website LIKE ? OR p.publishers_id = ? OR p.id = ? OR p.city LIKE ? OR p.state LIKE ? OR p.contact_name LIKE ? OR p.publisher_phone LIKE ? OR bu.name LIKE ?)';
            $types .= 'ssssiissss';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = (int)$search;
            $params[] = (int)$search;
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        if ($status === 'active') {
            $where[] = 'p.is_active = 1';
        } elseif ($status === 'inactive') {
            $where[] = 'p.is_active = 0';
        }

        $whereSql = $where ? (' WHERE ' . implode(' AND ', $where)) : '';
        $countStmt = $this->conn->prepare('SELECT COUNT(*) AS total FROM vp_publishers p LEFT JOIN vp_users bu ON bu.id = p.broker_id AND bu.is_deleted = 0' . $whereSql);
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
            'SELECT ' . self::LIST_COLUMNS . self::LIST_FROM . $whereSql . '
             ORDER BY p.publishers ASC
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

        $this->attachContactsToPublishers($publishers);

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
        $stmt = $this->conn->prepare('SELECT ' . self::LIST_COLUMNS . self::LIST_FROM . ' WHERE p.id = ? LIMIT 1');
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

        $this->attachContactsToPublisher($row);

        return $row;
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

        $contactError = $this->validatePublisherContacts($fields, $id);
        if ($contactError !== null) {
            return $contactError;
        }

        $brokerError = $this->validateBrokerId($fields['broker_id']);
        if ($brokerError !== null) {
            return $brokerError;
        }
        $brokerId = $this->normalizeBrokerId($fields['broker_id']);

        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            'UPDATE vp_publishers SET publishers = ?, display_name = ?, website = ?, contact_name = ?, publisher_email = ?, publisher_email_is_primary = ?, country_code = ?, publisher_phone = ?, publisher_phone_is_whatsapp = ?, gst_number = ?, pan_number = ?, address = ?, city = ?, state = ?, country = ?, postal_code = ?, webpage = ?, stock_replenishment_months = ?, discount = ?, broker_id = ?, is_active = ? WHERE id = ?'
        );
        if (!$stmt) {
            $this->conn->rollback();

            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stockReplenishmentMonths = (int)$fields['stock_replenishment_months'];
        $discount = (float)$fields['discount'];
        $publisherEmailIsPrimary = (int)$fields['publisher_email_is_primary'];
        $publisherPhoneIsWhatsapp = (int)$fields['publisher_phone_is_whatsapp'];
        $stmt->bind_param(
            'sssssississsssssiddiii',
            $name,
            $fields['display_name'],
            $fields['website'],
            $fields['contact_name'],
            $fields['publisher_email'],
            $publisherEmailIsPrimary,
            $fields['country_code'],
            $fields['publisher_phone'],
            $publisherPhoneIsWhatsapp,
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
            $brokerId,
            $isActive,
            $id
        );

        try {
            $ok = $stmt->execute();
            $error = $stmt->error;
            $stmt->close();
            if (!$ok) {
                $this->conn->rollback();

                return ['success' => false, 'message' => 'Could not save publisher: ' . $error];
            }

            $contactSaveError = $this->replacePublisherContacts(
                $id,
                $fields['alt_phones'],
                $fields['alt_emails']
            );
            if ($contactSaveError !== null) {
                $this->conn->rollback();

                return $contactSaveError;
            }

            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();

            return ['success' => false, 'message' => 'Could not save publisher: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Publisher saved successfully.', 'id' => $id];
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

        $contactError = $this->validatePublisherContacts($fields);
        if ($contactError !== null) {
            return $contactError;
        }

        $brokerError = $this->validateBrokerId($fields['broker_id']);
        if ($brokerError !== null) {
            return $brokerError;
        }
        $brokerId = $this->normalizeBrokerId($fields['broker_id']);

        $this->conn->begin_transaction();

        $stmt = $this->conn->prepare(
            'INSERT INTO vp_publishers (publishers_id, publishers, display_name, website, contact_name, publisher_email, publisher_email_is_primary, country_code, publisher_phone, publisher_phone_is_whatsapp, gst_number, pan_number, address, city, state, country, postal_code, webpage, stock_replenishment_months, discount, broker_id, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            $this->conn->rollback();

            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $webpage = (int)$fields['webpage'];
        $stockReplenishmentMonths = (int)$fields['stock_replenishment_months'];
        $discount = (float)$fields['discount'];
        $publisherEmailIsPrimary = (int)$fields['publisher_email_is_primary'];
        $publisherPhoneIsWhatsapp = (int)$fields['publisher_phone_is_whatsapp'];
        $stmt->bind_param(
            'isssssississsssssiddii',
            $publishersId,
            $name,
            $fields['display_name'],
            $fields['website'],
            $fields['contact_name'],
            $fields['publisher_email'],
            $publisherEmailIsPrimary,
            $fields['country_code'],
            $fields['publisher_phone'],
            $publisherPhoneIsWhatsapp,
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
            $brokerId,
            $isActive
        );

        try {
            $ok = $stmt->execute();
            $newId = (int) $stmt->insert_id;
            $error = $stmt->error;
            $stmt->close();
            if (!$ok || $newId <= 0) {
                $this->conn->rollback();

                return ['success' => false, 'message' => 'Could not save publisher: ' . $error];
            }

            $contactSaveError = $this->replacePublisherContacts(
                $newId,
                $fields['alt_phones'],
                $fields['alt_emails']
            );
            if ($contactSaveError !== null) {
                $this->conn->rollback();

                return $contactSaveError;
            }

            $this->conn->commit();
        } catch (mysqli_sql_exception $e) {
            $this->conn->rollback();

            return ['success' => false, 'message' => 'Could not save publisher: ' . $e->getMessage()];
        }

        return ['success' => true, 'message' => 'Publisher saved successfully.', 'id' => $newId, 'publishers_id' => $publishersId];
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

        foreach (['publisher_phones', 'publisher_emails'] as $contactTable) {
            $contactStmt = $this->conn->prepare("DELETE FROM {$contactTable} WHERE publisher_id = ?");
            if ($contactStmt) {
                $contactStmt->bind_param('i', $id);
                $contactStmt->execute();
                $contactStmt->close();
            }
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

    /**
     * @return array{id:string,name:string}|null
     */
    public function findBestMatchByName(string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        $stmt = $this->conn->prepare(
            'SELECT publishers_id AS id, publishers AS name
             FROM vp_publishers
             WHERE is_active = 1 AND publishers = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $name);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (is_array($row) && !empty($row['id'])) {
            return ['id' => (string) $row['id'], 'name' => (string) $row['name']];
        }

        $search = '%' . $name . '%';
        $stmt = $this->conn->prepare(
            'SELECT publishers_id AS id, publishers AS name
             FROM vp_publishers
             WHERE is_active = 1 AND publishers LIKE ?
             ORDER BY CHAR_LENGTH(publishers) ASC
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $search);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (is_array($row) && !empty($row['id'])) {
            return ['id' => (string) $row['id'], 'name' => (string) $row['name']];
        }

        return null;
    }

}
