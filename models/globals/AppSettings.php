<?php

class AppSettings
{
    private $conn;

    private const GROUP_LABELS = [
        'company' => 'Company',
        'invoice' => 'Invoice & Billing',
        'compliance' => 'Compliance',
        'dispatch' => 'Dispatch',
        'general' => 'General',
    ];

    /** Maps app_settings keys to legacy singleton tables for backward compatibility. */
    private const LEGACY_SYNC = [
        'invoice_prefix' => ['global_settings', 'invoice_prefix'],
        'invoice_series' => ['global_settings', 'invoice_series'],
        'terms_and_conditions' => ['global_settings', 'terms_and_conditions'],
        'high_value_transaction_limit' => ['global_settings', 'high_value_transaction_limit'],
        'firm_name' => ['firm_details', 'firm_name'],
        'firm_pan' => ['firm_details', 'pan'],
        'firm_gst' => ['firm_details', 'gst'],
        'firm_address' => ['firm_details', 'address'],
        'firm_phone' => ['firm_details', 'phone'],
        'firm_city' => ['firm_details', 'city'],
        'firm_state' => ['firm_details', 'state'],
        'firm_country' => ['firm_details', 'country'],
        'firm_pin' => ['firm_details', 'pin'],
        'firm_state_code' => ['firm_details', 'state_code'],
        'firm_email' => ['firm_details', 'email'],
    ];

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function tableExists(string $table): bool
    {
        $stmt = $this->conn->prepare(
            'SELECT 1
             FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $table);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    public function getGroupLabel(string $groupKey): string
    {
        return self::GROUP_LABELS[$groupKey] ?? ucfirst(str_replace('_', ' ', $groupKey));
    }

    public function getAllGrouped(): array
    {
        if (!$this->tableExists('app_settings')) {
            return [];
        }

        $sql = 'SELECT *
                FROM app_settings
                ORDER BY group_key ASC, sort_order ASC, id ASC';
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $grouped = [];
        while ($row = $result->fetch_assoc()) {
            $groupKey = $row['group_key'];
            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'group_key' => $groupKey,
                    'group_label' => $this->getGroupLabel($groupKey),
                    'settings' => [],
                ];
            }
            $row['options'] = $this->decodeOptions($row['options_json'] ?? null);
            $grouped[$groupKey]['settings'][] = $row;
        }

        return array_values($grouped);
    }

    public function getByKeys(array $keys): array
    {
        if (!$this->tableExists('app_settings') || $keys === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT *
                FROM app_settings
                WHERE setting_key IN ($placeholders)";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $types = str_repeat('s', count($keys));
        $stmt->bind_param($types, ...$keys);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $row['options'] = $this->decodeOptions($row['options_json'] ?? null);
            $rows[$row['setting_key']] = $row;
        }
        $stmt->close();

        return $rows;
    }

    public function get(string $key, $default = null)
    {
        if (!$this->tableExists('app_settings')) {
            return $default;
        }

        $stmt = $this->conn->prepare(
            'SELECT setting_value, value_type, is_active
             FROM app_settings
             WHERE setting_key = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return $default;
        }

        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || (int) ($row['is_active'] ?? 0) !== 1) {
            return $default;
        }

        return $this->castValue($row['setting_value'], $row['value_type']);
    }

    public function updateSettings(array $submittedValues, array $submittedActive, int $userId): array
    {
        if (!$this->tableExists('app_settings')) {
            return ['success' => false, 'message' => 'Settings table is not installed. Run sql/create_app_settings_tables.sql.'];
        }

        $keys = array_unique(array_merge(array_keys($submittedActive), array_keys($submittedValues)));
        $definitions = $this->getByKeys($keys);
        if ($definitions === []) {
            return ['success' => false, 'message' => 'No settings were submitted.'];
        }

        $updated = 0;
        $errors = [];

        foreach ($definitions as $key => $definition) {
            $definition = $this->applyActiveStateUpdate(
                $key,
                $definition,
                $submittedActive,
                $userId,
                $updated,
                $errors
            );
            if ($definition === null) {
                continue;
            }

            if ((int) ($definition['is_editable'] ?? 0) !== 1) {
                continue;
            }

            if (!array_key_exists($key, $submittedValues)) {
                continue;
            }

            $rawValue = $submittedValues[$key] ?? null;
            $normalized = $this->normalizeSubmittedValue($definition, $rawValue);
            if ($normalized['error'] !== null) {
                $errors[] = $definition['label'] . ': ' . $normalized['error'];
                continue;
            }

            $newValue = $normalized['value'];
            $oldValue = (string) ($definition['setting_value'] ?? '');
            $storedValue = $this->serializeValue($newValue, $definition['value_type']);

            if ($storedValue === $oldValue) {
                continue;
            }

            $stmt = $this->conn->prepare(
                'UPDATE app_settings
                 SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE setting_key = ? AND is_editable = 1'
            );
            if (!$stmt) {
                $errors[] = $definition['label'] . ': failed to prepare update.';
                continue;
            }

            $stmt->bind_param('sis', $storedValue, $userId, $key);
            if (!$stmt->execute()) {
                $errors[] = $definition['label'] . ': update failed.';
                $stmt->close();
                continue;
            }
            $stmt->close();

            $this->logAudit($key, $oldValue, $storedValue, $userId);
            $definition['setting_value'] = $storedValue;

            if ((int) ($definition['is_active'] ?? 0) === 1) {
                $this->syncLegacyValue($key, $newValue, $definition['value_type']);
            }

            $updated++;
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
                'updated' => $updated,
            ];
        }

        return [
            'success' => true,
            'message' => $updated > 0 ? 'Settings saved successfully.' : 'No changes detected.',
            'updated' => $updated,
        ];
    }

    /** @deprecated Use updateSettings() */
    public function updateValues(array $submittedValues, int $userId): array
    {
        return $this->updateSettings($submittedValues, [], $userId);
    }

    private function applyActiveStateUpdate(
        string $key,
        array $definition,
        array $submittedActive,
        int $userId,
        int &$updated,
        array &$errors
    ): ?array {
        if (!array_key_exists($key, $submittedActive)) {
            return $definition;
        }

        $newActive = ($submittedActive[$key] ?? '0') === '1' || $submittedActive[$key] === 1 ? 1 : 0;
        $oldActive = (int) ($definition['is_active'] ?? 1);

        if ($newActive === $oldActive) {
            return $definition;
        }

        $stmt = $this->conn->prepare(
            'UPDATE app_settings
             SET is_active = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
             WHERE setting_key = ?'
        );
        if (!$stmt) {
            $errors[] = $definition['label'] . ': failed to prepare status update.';
            return null;
        }

        $stmt->bind_param('iis', $newActive, $userId, $key);
        if (!$stmt->execute()) {
            $errors[] = $definition['label'] . ': status update failed.';
            $stmt->close();
            return null;
        }
        $stmt->close();

        $this->logAudit(
            $key,
            $oldActive === 1 ? 'Active' : 'Inactive',
            $newActive === 1 ? 'Active' : 'Inactive',
            $userId
        );

        $definition['is_active'] = $newActive;
        $updated++;

        if ($newActive === 1) {
            $this->syncLegacyValue(
                $key,
                $this->castValue($definition['setting_value'] ?? '', $definition['value_type'] ?? 'string'),
                $definition['value_type'] ?? 'string'
            );
        }

        return $definition;
    }

    public function getRecentAudit(int $limit = 20): array
    {
        if (!$this->tableExists('settings_audit_log')) {
            return [];
        }

        $limit = max(1, min($limit, 100));
        $sql = "SELECT l.setting_key, l.old_value, l.new_value, l.changed_by, l.changed_at,
                       COALESCE(u.name, CONCAT('User #', l.changed_by)) AS changed_by_name
                FROM settings_audit_log l
                LEFT JOIN vp_users u ON u.id = l.changed_by
                ORDER BY l.changed_at DESC, l.id DESC
                LIMIT $limit";
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        return $rows;
    }

    private function decodeOptions($json): array
    {
        if ($json === null || $json === '') {
            return [];
        }
        if (is_array($json)) {
            return $json;
        }
        $decoded = json_decode((string) $json, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeSubmittedValue(array $definition, $rawValue): array
    {
        $inputType = $definition['input_type'] ?? 'text';
        $valueType = $definition['value_type'] ?? 'string';

        if ($inputType === 'toggle' || $valueType === 'bool') {
            return ['value' => $rawValue === '1' || $rawValue === 1 || $rawValue === true, 'error' => null];
        }

        if ($rawValue === null) {
            $rawValue = '';
        }

        $value = is_string($rawValue) ? trim($rawValue) : $rawValue;

        if ($inputType === 'select') {
            $options = $definition['options'] ?? [];
            $allowed = [];
            foreach ($options as $option) {
                if (is_array($option) && isset($option['value'])) {
                    $allowed[] = (string) $option['value'];
                } elseif (!is_array($option)) {
                    $allowed[] = (string) $option;
                }
            }
            if ($allowed !== [] && !in_array((string) $value, $allowed, true)) {
                return ['value' => null, 'error' => 'invalid option selected.'];
            }
        }

        if ($valueType === 'int') {
            if ($value === '' || !is_numeric($value)) {
                return ['value' => null, 'error' => 'must be a whole number.'];
            }
            $intValue = (int) $value;
            if ((string) $intValue !== (string) $value && (string) ((float) $value) !== (string) $value) {
                return ['value' => null, 'error' => 'must be a whole number.'];
            }

            return ['value' => $intValue, 'error' => null];
        }

        if ($valueType === 'decimal') {
            if ($value === '' || !is_numeric($value)) {
                return ['value' => null, 'error' => 'must be a number.'];
            }
            $decimal = round((float) $value, 2);
            if ($decimal <= 0) {
                return ['value' => null, 'error' => 'must be greater than zero.'];
            }

            return ['value' => $decimal, 'error' => null];
        }

        return ['value' => (string) $value, 'error' => null];
    }

    private function serializeValue($value, string $valueType): string
    {
        if ($valueType === 'bool') {
            return $value ? '1' : '0';
        }
        if ($valueType === 'json' && is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
        }

        return (string) $value;
    }

    private function castValue($value, string $valueType)
    {
        if ($value === null) {
            return null;
        }

        switch ($valueType) {
            case 'int':
                return (int) $value;
            case 'decimal':
                return (float) $value;
            case 'bool':
                return in_array((string) $value, ['1', 'true', 'yes'], true);
            case 'json':
                $decoded = json_decode((string) $value, true);

                return is_array($decoded) ? $decoded : $value;
            default:
                return (string) $value;
        }
    }

    private function logAudit(string $key, ?string $oldValue, ?string $newValue, int $userId): void
    {
        if (!$this->tableExists('settings_audit_log')) {
            return;
        }

        $stmt = $this->conn->prepare(
            'INSERT INTO settings_audit_log (setting_key, old_value, new_value, changed_by)
             VALUES (?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }

        $stmt->bind_param('sssi', $key, $oldValue, $newValue, $userId);
        $stmt->execute();
        $stmt->close();
    }

    private function syncLegacyValue(string $key, $value, string $valueType): void
    {
        if (!isset(self::LEGACY_SYNC[$key])) {
            return;
        }

        [$table, $column] = self::LEGACY_SYNC[$key];
        if (!$this->tableExists($table)) {
            return;
        }

        $storedValue = $this->serializeValue($value, $valueType);
        $sql = "UPDATE `$table` SET `$column` = ?, updated_at = CURRENT_TIMESTAMP WHERE id = 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return;
        }

        if ($valueType === 'int') {
            $intValue = (int) $storedValue;
            $stmt->bind_param('i', $intValue);
        } elseif ($valueType === 'decimal') {
            $floatValue = (float) $storedValue;
            $stmt->bind_param('d', $floatValue);
        } else {
            $stmt->bind_param('s', $storedValue);
        }

        $stmt->execute();
        $stmt->close();
    }
}
