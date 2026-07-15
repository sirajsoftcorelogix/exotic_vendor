<?php

class AppSettings
{
    private $conn;

    private ?array $registry = null;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function getRegistry(): array
    {
        if ($this->registry !== null) {
            return $this->registry;
        }

        $path = __DIR__ . '/../../config/app_settings_registry.php';
        if (!is_file($path)) {
            $this->registry = [];

            return $this->registry;
        }

        $registry = require $path;

        $this->registry = is_array($registry) ? $registry : [];

        return $this->registry;
    }

    public function getRegistryEntry(string $key): ?array
    {
        $registry = $this->getRegistry();

        return $registry[$key] ?? null;
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

    public function getAllSettings(): array
    {
        if (!$this->tableExists('app_settings')) {
            return [];
        }

        $sql = 'SELECT id, setting_key, setting_value, updated_by, updated_at
                FROM app_settings
                ORDER BY setting_key ASC';
        $result = $this->conn->query($sql);
        if (!$result) {
            return [];
        }

        $registry = $this->getRegistry();
        $settings = [];

        while ($row = $result->fetch_assoc()) {
            $key = $row['setting_key'];
            $meta = $registry[$key] ?? null;
            if ($meta === null || empty($meta['active'])) {
                continue;
            }

            $settings[] = $this->mergeSettingRow($row, $meta);
        }

        usort($settings, static function (array $a, array $b): int {
            $sort = ($a['sort_order'] ?? 0) <=> ($b['sort_order'] ?? 0);
            if ($sort !== 0) {
                return $sort;
            }

            return strcmp($a['setting_key'], $b['setting_key']);
        });

        return $settings;
    }

    /** @deprecated Use getAllSettings() */
    public function getAllGrouped(): array
    {
        return $this->getAllSettings();
    }

    public function getByKeys(array $keys): array
    {
        if (!$this->tableExists('app_settings') || $keys === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $sql = "SELECT id, setting_key, setting_value, updated_by, updated_at
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
            $key = $row['setting_key'];
            $meta = $this->getRegistryEntry($key);
            if ($meta === null) {
                continue;
            }
            $rows[$key] = $this->mergeSettingRow($row, $meta);
        }
        $stmt->close();

        return $rows;
    }

    public function get(string $key, $default = null)
    {
        $meta = $this->getRegistryEntry($key);
        if ($meta === null || empty($meta['active'])) {
            return $default ?? ($meta['default'] ?? null);
        }

        $stored = $this->getStoredValue($key, null);
        if ($stored === null) {
            return $default ?? ($meta['default'] ?? null);
        }

        return $stored;
    }

    public function getStoredValue(string $key, $default = null)
    {
        if (!$this->tableExists('app_settings')) {
            $meta = $this->getRegistryEntry($key);

            return $default ?? ($meta['default'] ?? null);
        }

        $stmt = $this->conn->prepare(
            'SELECT setting_value
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

        $meta = $this->getRegistryEntry($key);
        if (!$row) {
            return $default ?? ($meta['default'] ?? null);
        }

        return $this->castValue($row['setting_value'], $meta['type'] ?? 'string');
    }

    public function setValue(string $key, $value, int $userId = 0, bool $force = false): bool
    {
        if (!$this->tableExists('app_settings')) {
            return false;
        }

        $meta = $this->getRegistryEntry($key);
        if ($meta === null) {
            return false;
        }

        if (!$force && empty($meta['editable'])) {
            return false;
        }

        $definition = $this->mergeSettingRow($this->fetchDbRow($key) ?? ['setting_key' => $key, 'setting_value' => null], $meta);
        $normalized = $this->normalizeSubmittedValue($definition, $value);
        if ($normalized['error'] !== null) {
            return false;
        }

        $storedValue = $this->serializeValue($normalized['value'], $definition['value_type']);
        $oldValue = (string) ($definition['setting_value'] ?? '');
        if ($storedValue === $oldValue) {
            return 'unchanged';
        }

        if ($this->fetchDbRow($key) === null) {
            $stmt = $this->conn->prepare(
                'INSERT INTO app_settings (setting_key, setting_value, updated_by)
                 VALUES (?, ?, ?)'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('ssi', $key, $storedValue, $userId);
        } else {
            $stmt = $this->conn->prepare(
                'UPDATE app_settings
                 SET setting_value = ?, updated_by = ?, updated_at = CURRENT_TIMESTAMP
                 WHERE setting_key = ?'
            );
            if (!$stmt) {
                return false;
            }
            $stmt->bind_param('sis', $storedValue, $userId, $key);
        }

        $ok = $stmt->execute();
        $stmt->close();
        if (!$ok) {
            return false;
        }

        $this->logAudit($key, $oldValue, $storedValue, $userId);

        return true;
    }

    public function getFirmDetailsRow(): array
    {
        return [
            'id' => 1,
            'firm_name' => (string) $this->get('firm_name', ''),
            'pan' => (string) $this->get('firm_pan', ''),
            'gst' => (string) $this->get('firm_gst', ''),
            'address' => (string) $this->get('firm_address', ''),
            'phone' => (string) $this->get('firm_phone', ''),
            'city' => (string) $this->get('firm_city', ''),
            'state' => (string) $this->get('firm_state', ''),
            'country' => (string) $this->get('firm_country', ''),
            'pin' => (string) $this->get('firm_pin', ''),
            'state_code' => $this->get('firm_state_code', null),
            'email' => (string) $this->get('firm_email', ''),
        ];
    }

    public function getGlobalSettingsRow(): array
    {
        return [
            'id' => 1,
            'invoice_prefix' => (string) $this->get('invoice_prefix', 'INV'),
            'invoice_series' => (int) $this->getStoredValue('invoice_series', 1),
            'terms_and_conditions' => (string) $this->get('terms_and_conditions', ''),
            'high_value_transaction_limit' => (float) $this->get('high_value_transaction_limit', 200000.00),
        ];
    }

    public function updateSettings(array $submittedValues, int $userId): array
    {
        if (!$this->tableExists('app_settings')) {
            return ['success' => false, 'message' => 'Settings table is not installed. Run sql/app_settings_module.sql.'];
        }

        $keys = array_keys($submittedValues);
        $definitions = $this->getByKeys($keys);
        if ($definitions === []) {
            return ['success' => false, 'message' => 'No editable settings were submitted.'];
        }

        $updated = 0;
        $errors = [];

        foreach ($definitions as $key => $definition) {
            if (empty($definition['is_editable'])) {
                continue;
            }

            if (!array_key_exists($key, $submittedValues)) {
                continue;
            }

            $result = $this->setValue($key, $submittedValues[$key], $userId);
            if ($result === false) {
                $errors[] = ($definition['label'] ?? $key) . ': update failed.';
                continue;
            }
            if ($result === true) {
                $updated++;
            }
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

    private function fetchDbRow(string $key): ?array
    {
        $stmt = $this->conn->prepare(
            'SELECT id, setting_key, setting_value, updated_by, updated_at
             FROM app_settings
             WHERE setting_key = ?
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    private function mergeSettingRow(array $row, array $meta): array
    {
        return [
            'id' => $row['id'] ?? null,
            'setting_key' => $row['setting_key'],
            'setting_value' => $row['setting_value'],
            'updated_by' => $row['updated_by'] ?? null,
            'updated_at' => $row['updated_at'] ?? null,
            'label' => $meta['label'] ?? $row['setting_key'],
            'description' => $meta['description'] ?? '',
            'input_type' => $meta['input'] ?? 'text',
            'value_type' => $meta['type'] ?? 'string',
            'is_editable' => !empty($meta['editable']) ? 1 : 0,
            'is_active' => !empty($meta['active']) ? 1 : 0,
            'sort_order' => (int) ($meta['sort'] ?? 0),
            'options' => $meta['options'] ?? [],
        ];
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

            return ['value' => (int) $value, 'error' => null];
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
}
