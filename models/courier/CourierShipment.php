<?php

class CourierShipment
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
        $this->ensureTables();
    }

    private function ensureTables(): void
    {
        $sql1 = "CREATE TABLE IF NOT EXISTS courier_shipments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            invoice_id INT UNSIGNED NULL,
            box_no INT UNSIGNED NULL,
            order_number VARCHAR(80) NULL,
            legacy_dispatch_id INT UNSIGNED NULL,
            partner_code VARCHAR(50) NOT NULL,
            partner_account_id INT UNSIGNED NULL,
            partner_shipment_id VARCHAR(120) NULL,
            awb VARCHAR(80) NULL,
            tracking_url TEXT NULL,
            product_group VARCHAR(20) NULL,
            product_type VARCHAR(20) NULL,
            service_level VARCHAR(80) NULL,
            payment_mode VARCHAR(20) NULL DEFAULT 'prepaid',
            cod_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
            is_international TINYINT(1) NOT NULL DEFAULT 0,
            currency VARCHAR(3) NULL,
            charges_total DECIMAL(12,2) NULL,
            label_url TEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'draft',
            status_text VARCHAR(255) NULL,
            metadata_json MEDIUMTEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_partner (partner_code),
            INDEX idx_awb (awb),
            INDEX idx_order (order_number),
            INDEX idx_invoice_box (invoice_id, box_no),
            INDEX idx_legacy_dispatch (legacy_dispatch_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->conn->query($sql1);
        $this->ensureColumn('courier_shipments', 'metadata_json', 'MEDIUMTEXT NULL');

        $sql2 = "CREATE TABLE IF NOT EXISTS courier_api_logs (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            partner_code VARCHAR(50) NOT NULL,
            partner_account_id INT UNSIGNED NULL,
            action VARCHAR(80) NOT NULL,
            reference_key VARCHAR(120) NULL,
            request_json MEDIUMTEXT NULL,
            response_json MEDIUMTEXT NULL,
            http_code INT NULL,
            success TINYINT(1) NOT NULL DEFAULT 0,
            error_message TEXT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_partner_action (partner_code, action),
            INDEX idx_reference (reference_key),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $this->conn->query($sql2);
    }

    private function ensureColumn(string $table, string $column, string $definition): void
    {
        $safeTable = preg_replace('/[^a-z0-9_]/i', '', $table);
        $safeColumn = preg_replace('/[^a-z0-9_]/i', '', $column);
        if ($safeTable === '' || $safeColumn === '') {
            return;
        }
        $res = $this->conn->query("SHOW COLUMNS FROM `{$safeTable}` LIKE '{$safeColumn}'");
        if ($res && $res->num_rows === 0) {
            $this->conn->query("ALTER TABLE `{$safeTable}` ADD COLUMN `{$safeColumn}` {$definition}");
            return;
        }
        if ($safeTable === 'courier_shipments' && $safeColumn === 'metadata_json') {
            $row = $res ? $res->fetch_assoc() : null;
            $type = strtolower((string) ($row['Type'] ?? ''));
            if ($type === 'text') {
                $this->conn->query("ALTER TABLE `{$safeTable}` MODIFY COLUMN `{$safeColumn}` MEDIUMTEXT NULL");
            }
        }
    }

    /** @param mixed $metadata */
    private function normalizeMetadataJson($metadata): ?string
    {
        if ($metadata === null || $metadata === '') {
            return null;
        }
        $json = is_string($metadata)
            ? $metadata
            : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || $json === '') {
            return null;
        }

        // Guard against oversized blobs (full API responses belong in courier_api_logs).
        $maxBytes = 60000;
        if (strlen($json) > $maxBytes) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                unset($decoded['packing_slip'], $decoded['create_response'], $decoded['debug']);
                $json = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $json;
            }
            if (strlen($json) > $maxBytes) {
                $json = json_encode([
                    'truncated' => true,
                    'note' => 'Metadata trimmed — see courier_api_logs for full Delhivery responses.',
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $json;
    }

    public function logApiCall(
        string $partnerCode,
        string $action,
        ?int $accountId,
        ?string $referenceKey,
        $request,
        $response,
        bool $success,
        ?string $errorMessage = null,
        ?int $httpCode = null
    ): void {
        $reqJson = is_string($request) ? $request : json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $resJson = is_string($response) ? $response : json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmt = $this->conn->prepare(
            'INSERT INTO courier_api_logs
             (partner_code, partner_account_id, action, reference_key, request_json, response_json, http_code, success, error_message)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        if (!$stmt) {
            return;
        }
        $successInt = $success ? 1 : 0;
        $stmt->bind_param(
            'sissssiis',
            $partnerCode,
            $accountId,
            $action,
            $referenceKey,
            $reqJson,
            $resJson,
            $httpCode,
            $successInt,
            $errorMessage
        );
        $stmt->execute();
        $stmt->close();
    }

    /** @param array<string, mixed> $row */
    public function saveShipment(array $row): ?int
    {
        $sql = 'INSERT INTO courier_shipments
            (invoice_id, box_no, order_number, legacy_dispatch_id, partner_code, partner_account_id,
             partner_shipment_id, awb, tracking_url, product_group, product_type, service_level,
             payment_mode, cod_amount, is_international, currency, charges_total, label_url, status, status_text, metadata_json)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $invoiceId = isset($row['invoice_id']) ? (int) $row['invoice_id'] : null;
        $boxNo = isset($row['box_no']) ? (int) $row['box_no'] : null;
        $legacyDispatchId = isset($row['legacy_dispatch_id']) ? (int) $row['legacy_dispatch_id'] : null;
        $partnerAccountId = isset($row['partner_account_id']) ? (int) $row['partner_account_id'] : null;
        $codAmount = (float) ($row['cod_amount'] ?? 0);
        $isInternational = !empty($row['is_international']) ? 1 : 0;
        $chargesTotal = isset($row['charges_total']) ? (float) $row['charges_total'] : 0.0;
        $metadataJson = $this->normalizeMetadataJson($row['metadata_json'] ?? null);

        $orderNumber = (string) ($row['order_number'] ?? '');
        $partnerCode = (string) ($row['partner_code'] ?? '');
        $partnerShipmentId = (string) ($row['partner_shipment_id'] ?? '');
        $awb = (string) ($row['awb'] ?? '');
        $trackingUrl = (string) ($row['tracking_url'] ?? '');
        $productGroup = (string) ($row['product_group'] ?? '');
        $productType = (string) ($row['product_type'] ?? '');
        $serviceLevel = (string) ($row['service_level'] ?? '');
        $paymentMode = (string) ($row['payment_mode'] ?? 'prepaid');
        $currency = (string) ($row['currency'] ?? '');
        $labelUrl = (string) ($row['label_url'] ?? '');
        $status = (string) ($row['status'] ?? 'created');
        $statusText = (string) ($row['status_text'] ?? '');

        $stmt->bind_param(
            'iisississsssssdidssss',
            $invoiceId,
            $boxNo,
            $orderNumber,
            $legacyDispatchId,
            $partnerCode,
            $partnerAccountId,
            $partnerShipmentId,
            $awb,
            $trackingUrl,
            $productGroup,
            $productType,
            $serviceLevel,
            $paymentMode,
            $codAmount,
            $isInternational,
            $currency,
            $chargesTotal,
            $labelUrl,
            $status,
            $statusText,
            $metadataJson
        );

        if (!$stmt->execute()) {
            $stmt->close();
            return null;
        }

        $id = (int) $this->conn->insert_id;
        $stmt->close();
        return $id > 0 ? $id : null;
    }
}
