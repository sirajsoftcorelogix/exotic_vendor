<?php

class CourierAccount
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $sql1 = "CREATE TABLE IF NOT EXISTS courier_partner_accounts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            partner_id INT UNSIGNED NOT NULL,
            account_code VARCHAR(80) NOT NULL,
            account_name VARCHAR(140) NOT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            priority INT NOT NULL DEFAULT 100,
            tags_json TEXT NULL,
            notes TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_partner_account_code (partner_id, account_code),
            INDEX idx_partner (partner_id),
            INDEX idx_active (is_active),
            CONSTRAINT fk_courier_partner_accounts_partner
                FOREIGN KEY (partner_id) REFERENCES courier_partners(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->conn->query($sql1);

        $sql2 = "CREATE TABLE IF NOT EXISTS courier_partner_account_credentials (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            account_id INT UNSIGNED NOT NULL,
            cred_key VARCHAR(120) NOT NULL,
            cred_value TEXT NULL,
            is_secret TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_account_cred_key (account_id, cred_key),
            INDEX idx_account (account_id),
            CONSTRAINT fk_courier_account_creds_account
                FOREIGN KEY (account_id) REFERENCES courier_partner_accounts(id)
                ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->conn->query($sql2);
    }

    public function listAccounts(int $partnerId = 0): array
    {
        $rows = [];
        if ($partnerId > 0) {
            $stmt = $this->conn->prepare(
                "SELECT a.*, p.partner_code, p.partner_name
                 FROM courier_partner_accounts a
                 JOIN courier_partners p ON p.id = a.partner_id
                 WHERE a.partner_id = ?
                 ORDER BY a.is_active DESC, a.priority ASC, a.account_name ASC"
            );
            $stmt->bind_param('i', $partnerId);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($res && ($r = $res->fetch_assoc())) {
                    $rows[] = $r;
                }
            }
            $stmt->close();
            return $rows;
        }

        $res = $this->conn->query(
            "SELECT a.*, p.partner_code, p.partner_name
             FROM courier_partner_accounts a
             JOIN courier_partners p ON p.id = a.partner_id
             ORDER BY a.is_active DESC, a.priority ASC, a.account_name ASC"
        );
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        return $rows;
    }

    public function getAccount(int $id): ?array
    {
        if ($id <= 0) return null;
        $stmt = $this->conn->prepare(
            "SELECT a.*, p.partner_code, p.partner_name
             FROM courier_partner_accounts a
             JOIN courier_partners p ON p.id = a.partner_id
             WHERE a.id = ? LIMIT 1"
        );
        $stmt->bind_param('i', $id);
        $row = null;
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            $row = $res ? $res->fetch_assoc() : null;
        }
        $stmt->close();
        return $row ?: null;
    }

    /** @return list<array{cred_key:string,cred_value:?string,is_secret:int}> */
    public function getCredentials(int $accountId): array
    {
        $rows = [];
        if ($accountId <= 0) return $rows;
        $stmt = $this->conn->prepare(
            "SELECT cred_key, cred_value, is_secret
             FROM courier_partner_account_credentials
             WHERE account_id = ?
             ORDER BY cred_key ASC"
        );
        $stmt->bind_param('i', $accountId);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            while ($res && ($r = $res->fetch_assoc())) {
                $rows[] = $r;
            }
        }
        $stmt->close();
        return $rows;
    }

    public function upsertAccount(int $id, array $data): array
    {
        $partnerId = (int)($data['partner_id'] ?? 0);
        $accountCode = trim((string)($data['account_code'] ?? ''));
        $accountName = trim((string)($data['account_name'] ?? ''));
        if ($partnerId <= 0 || $accountCode === '' || $accountName === '') {
            return ['success' => false, 'message' => 'Partner, account code, and account name are required.'];
        }

        $isActive = isset($data['is_active']) ? ((int)$data['is_active'] === 1 ? 1 : 0) : 1;
        $priority = isset($data['priority']) ? (int)$data['priority'] : 100;
        $tagsJson = trim((string)($data['tags_json'] ?? ''));
        $notes = trim((string)($data['notes'] ?? ''));

        if ($id > 0) {
            $stmt = $this->conn->prepare(
                "UPDATE courier_partner_accounts
                 SET partner_id = ?, account_code = ?, account_name = ?, is_active = ?, priority = ?, tags_json = ?, notes = ?, updated_at = NOW()
                 WHERE id = ?"
            );
            if (!$stmt) return ['success' => false, 'message' => 'Could not prepare update statement.'];
            $stmt->bind_param('issiissi', $partnerId, $accountCode, $accountName, $isActive, $priority, $tagsJson, $notes, $id);
            if ($stmt->execute()) {
                $stmt->close();
                return ['success' => true, 'message' => 'Account updated successfully.'];
            }
            $err = $stmt->error;
            $stmt->close();
            return ['success' => false, 'message' => 'Update failed: ' . $err];
        }

        $stmt = $this->conn->prepare(
            "INSERT INTO courier_partner_accounts
             (partner_id, account_code, account_name, is_active, priority, tags_json, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        if (!$stmt) return ['success' => false, 'message' => 'Could not prepare insert statement.'];
        $stmt->bind_param('issiiss', $partnerId, $accountCode, $accountName, $isActive, $priority, $tagsJson, $notes);
        if ($stmt->execute()) {
            $stmt->close();
            return ['success' => true, 'message' => 'Account added successfully.'];
        }
        $err = $stmt->error;
        $stmt->close();
        return ['success' => false, 'message' => 'Insert failed: ' . $err];
    }

    public function deleteAccount(int $id): array
    {
        if ($id <= 0) return ['success' => false, 'message' => 'Invalid account id.'];
        $stmt = $this->conn->prepare("DELETE FROM courier_partner_accounts WHERE id = ?");
        if (!$stmt) return ['success' => false, 'message' => 'Could not prepare delete statement.'];
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) return ['success' => true, 'message' => 'Account deleted.'];
        return ['success' => false, 'message' => 'Delete failed: ' . $stmt->error];
    }

    public function saveCredentials(int $accountId, array $keys, array $values, array $secrets): array
    {
        if ($accountId <= 0) return ['success' => false, 'message' => 'Invalid account id.'];

        $map = [];
        $n = max(count($keys), count($values));
        for ($i = 0; $i < $n; $i++) {
            $k = isset($keys[$i]) ? trim((string)$keys[$i]) : '';
            if ($k === '') continue;
            $v = isset($values[$i]) ? (string)$values[$i] : '';
            $isSecret = !empty($secrets[$i]) ? 1 : 0;
            $map[$k] = ['v' => $v, 's' => $isSecret];
        }

        // Replace existing credentials for simplicity.
        $del = $this->conn->prepare("DELETE FROM courier_partner_account_credentials WHERE account_id = ?");
        if ($del) {
            $del->bind_param('i', $accountId);
            $del->execute();
            $del->close();
        }

        $ins = $this->conn->prepare(
            "INSERT INTO courier_partner_account_credentials (account_id, cred_key, cred_value, is_secret)
             VALUES (?, ?, ?, ?)"
        );
        if (!$ins) return ['success' => false, 'message' => 'Could not prepare credential insert.'];

        foreach ($map as $k => $row) {
            $v = $row['v'];
            $s = (int)$row['s'];
            $ins->bind_param('issi', $accountId, $k, $v, $s);
            $ins->execute();
        }
        $ins->close();
        return ['success' => true, 'message' => 'Credentials saved.'];
    }
}

