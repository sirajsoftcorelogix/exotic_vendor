<?php

/**
 * Pending Exotic order/create payload for local POS temp orders (1:1 with vp_order_info).
 */
class PosOrderExoticSync
{
    private mysqli $db;

    public function __construct(mysqli $db)
    {
        $this->db = $db;
    }

    public function resolveOrderInfoId(string $orderNumber): int
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return 0;
        }

        $stmt = $this->db->prepare('SELECT id FROM vp_order_info WHERE order_number = ? LIMIT 1');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['id'] ?? 0);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function savePending(string $orderNumber, array $payload, string $apiError = ''): bool
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return false;
        }

        $orderInfoId = $this->resolveOrderInfoId($orderNumber);
        if ($orderInfoId <= 0) {
            error_log('[PosOrderExoticSync] vp_order_info not found for order ' . $orderNumber);

            return false;
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            return false;
        }

        $apiError = trim($apiError);
        if (strlen($apiError) > 500) {
            $apiError = substr($apiError, 0, 497) . '...';
        }

        $sql = 'INSERT INTO vp_order_exotic_sync
            (vp_order_info_id, order_number, sync_payload, api_error, sync_status)
            VALUES (?, ?, ?, ?, \'pending\')
            ON DUPLICATE KEY UPDATE
                order_number = VALUES(order_number),
                sync_payload = VALUES(sync_payload),
                api_error = VALUES(api_error),
                sync_status = \'pending\',
                updated_at = CURRENT_TIMESTAMP';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            error_log('[PosOrderExoticSync] save prepare failed: ' . $this->db->error);

            return false;
        }

        $stmt->bind_param('isss', $orderInfoId, $orderNumber, $json, $apiError);
        $ok = $stmt->execute();
        if (!$ok) {
            error_log('[PosOrderExoticSync] save execute failed: ' . $stmt->error);
        }
        $stmt->close();

        return $ok;
    }

    /**
     * @return array<string, mixed>|null Decoded sync payload (same shape as legacy JSON file).
     */
    public function loadPending(string $orderNumber): ?array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            'SELECT sync_payload, api_error, sync_status
             FROM vp_order_exotic_sync
             WHERE order_number = ? AND sync_status = \'pending\'
             LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $decoded = json_decode((string)($row['sync_payload'] ?? ''), true);
        if (!is_array($decoded)) {
            return null;
        }

        if (!isset($decoded['api_error']) && trim((string)($row['api_error'] ?? '')) !== '') {
            $decoded['api_error'] = (string)$row['api_error'];
        }

        return $decoded;
    }

    public function hasPending(string $orderNumber): bool
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM vp_order_exotic_sync WHERE order_number = ? AND sync_status = \'pending\' LIMIT 1'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $found = (bool)$stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $found;
    }

    public function markPublished(string $orderNumber): void
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return;
        }

        $stmt = $this->db->prepare(
            'UPDATE vp_order_exotic_sync SET sync_status = \'published\', updated_at = CURRENT_TIMESTAMP WHERE order_number = ?'
        );
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $stmt->close();
    }

    public function deletePending(string $orderNumber): void
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return;
        }

        $stmt = $this->db->prepare('DELETE FROM vp_order_exotic_sync WHERE order_number = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $stmt->close();
    }
}
