<?php

/**
 * Model layer for Export Document Generation Module.
 * Handles database operations and invoice auto-pull logic.
 */
class ExportDocument
{
    private \mysqli $conn;

    public function __construct(\mysqli $conn)
    {
        $this->conn = $conn;
    }

    /**
     * Search invoices/orders for autocomplete suggestions.
     *
     * @param string $term
     * @param int $limit
     * @return array<int, array<string, mixed>>
     */
    public function searchInvoicesForAutocomplete(string $term, int $limit = 20): array
    {
        $term = trim($term);
        if ($term === '') {
            return [];
        }

        $like = '%' . $term . '%';
        $sql = "SELECT DISTINCT i.id AS invoice_id, i.invoice_number, COALESCE(i.order_number, oi.order_number) AS order_number,
                       oi.first_name, oi.last_name, oi.shipping_first_name, oi.shipping_last_name, oi.country, oi.shipping_country
                FROM vp_invoices i
                LEFT JOIN vp_order_info oi ON (oi.id = i.vp_order_info_id OR ((i.vp_order_info_id IS NULL OR i.vp_order_info_id = 0) AND CONVERT(oi.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(i.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci))
                WHERE CONVERT(i.invoice_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? 
                   OR CONVERT(i.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? 
                   OR CONVERT(oi.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?
                ORDER BY i.id DESC
                LIMIT ?";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('sssi', $like, $like, $like, $limit);
        $stmt->execute();
        $res = $stmt->get_result();

        $results = [];
        while ($row = $res->fetch_assoc()) {
            $custName = trim(($row['shipping_first_name'] ?? '') . ' ' . ($row['shipping_last_name'] ?? ''));
            if ($custName === '') {
                $custName = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            }
            $destCountry = $row['shipping_country'] ?: ($row['country'] ?: '');

            $results[] = [
                'invoice_id' => (int)$row['invoice_id'],
                'invoice_number' => $row['invoice_number'],
                'order_number' => $row['order_number'] ?? '',
                'customer_name' => $custName,
                'country' => $destCountry,
                'label' => $row['invoice_number'] . ($row['order_number'] ? ' (' . $row['order_number'] . ')' : '') . ($custName ? ' - ' . $custName : '')
            ];
        }
        $stmt->close();

        return $results;
    }

    /**
     * Auto-pull invoice, order, customer, line items, and international defaults for document generation.
     *
     * @param string $query Invoice number or Order number
     * @return array<string, mixed>|null
     */
    /**
     * Auto-pull invoice, order, customer, line items, and international defaults for document generation.
     *
     * @param string $query Invoice number or Order number
     * @return array<string, mixed>|null
     */
    public function findInvoiceAndOrderDetails(string $query): ?array
    {
        $query = trim($query);
        if ($query === '') {
            return null;
        }

        $invoiceHeader = null;
        $orderInfo = null;

        // 1. Try to find invoice in vp_invoices by invoice_number
        $stmt = $this->conn->prepare("SELECT * FROM vp_invoices WHERE CONVERT(invoice_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $query);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $invoiceHeader = $row;
            }
            $stmt->close();
        }

        // 2. If no invoice found by invoice_number, try to find invoice by order_number via vp_order_info or vp_invoice_items
        if (!$invoiceHeader) {
            $stmt = $this->conn->prepare("SELECT i.* FROM vp_invoices i 
                                          LEFT JOIN vp_order_info oi ON oi.id = i.vp_order_info_id 
                                          LEFT JOIN vp_invoice_items ii ON ii.invoice_id = i.id 
                                          WHERE CONVERT(oi.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                                             OR CONVERT(ii.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                                          ORDER BY i.id DESC LIMIT 1");
            if ($stmt) {
                $stmt->bind_param('ss', $query, $query);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) {
                    $invoiceHeader = $row;
                }
                $stmt->close();
            }
        }

        // 3. Locate vp_order_info record
        if ($invoiceHeader) {
            $vpOrderInfoId = (int)($invoiceHeader['vp_order_info_id'] ?? 0);
            if ($vpOrderInfoId > 0) {
                $oiStmt = $this->conn->prepare("SELECT * FROM vp_order_info WHERE id = ? LIMIT 1");
                if ($oiStmt) {
                    $oiStmt->bind_param('i', $vpOrderInfoId);
                    $oiStmt->execute();
                    $oiRes = $oiStmt->get_result();
                    if ($oiRow = $oiRes->fetch_assoc()) {
                        $orderInfo = $oiRow;
                    }
                    $oiStmt->close();
                }
            }

            // If not found by vp_order_info_id, find order_number from invoice items or query and search vp_order_info
            if (!$orderInfo) {
                $invOrderNum = '';
                $itemOrdStmt = $this->conn->prepare("SELECT order_number FROM vp_invoice_items WHERE invoice_id = ? AND order_number IS NOT NULL AND TRIM(order_number) != '' LIMIT 1");
                if ($itemOrdStmt) {
                    $itemOrdStmt->bind_param('i', $invoiceHeader['id']);
                    $itemOrdStmt->execute();
                    $itemRes = $itemOrdStmt->get_result();
                    if ($itemRow = $itemRes->fetch_assoc()) {
                        $invOrderNum = trim((string)$itemRow['order_number']);
                    }
                    $itemOrdStmt->close();
                }

                if ($invOrderNum !== '') {
                    $oiStmt = $this->conn->prepare("SELECT * FROM vp_order_info WHERE CONVERT(order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci ORDER BY id DESC LIMIT 1");
                    if ($oiStmt) {
                        $oiStmt->bind_param('s', $invOrderNum);
                        $oiStmt->execute();
                        $oiRes = $oiStmt->get_result();
                        if ($oiRow = $oiRes->fetch_assoc()) {
                            $orderInfo = $oiRow;
                        }
                        $oiStmt->close();
                    }
                }
            }
        }

        // If orderInfo still not found, try searching vp_order_info directly by $query
        if (!$orderInfo) {
            $oiStmt = $this->conn->prepare("SELECT * FROM vp_order_info WHERE CONVERT(order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci ORDER BY id DESC LIMIT 1");
            if ($oiStmt) {
                $oiStmt->bind_param('s', $query);
                $oiStmt->execute();
                $oiRes = $oiStmt->get_result();
                if ($oiRow = $oiRes->fetch_assoc()) {
                    $orderInfo = $oiRow;
                }
                $oiStmt->close();
            }
        }

        // 4. If neither invoiceHeader nor orderInfo was found, return null
        if (!$invoiceHeader && !$orderInfo) {
            return null;
        }

        // 5. Merge orderInfo and invoiceHeader (orderInfo first, invoiceHeader second)
        $header = array_merge($orderInfo ?? [], $invoiceHeader ?? []);

        if (empty($header['order_number']) && !empty($orderInfo['order_number'])) {
            $header['order_number'] = $orderInfo['order_number'];
        }
        if (empty($header['invoice_number']) && !empty($invoiceHeader['invoice_number'])) {
            $header['invoice_number'] = $invoiceHeader['invoice_number'];
        }

        // 6. Merge vp_customers details if customer_id is present
        $customerId = (int)($header['customer_id'] ?? 0);
        if ($customerId > 0) {
            $cStmt = $this->conn->prepare("SELECT name AS customer_master_name, email AS customer_master_email, phone AS customer_master_phone FROM vp_customers WHERE id = ? LIMIT 1");
            if ($cStmt) {
                $cStmt->bind_param('i', $customerId);
                $cStmt->execute();
                $cRes = $cStmt->get_result();
                if ($cRow = $cRes->fetch_assoc()) {
                    $header = array_merge($cRow, $header);
                }
                $cStmt->close();
            }

            // Also check if vp_order_info has any record for this customer_id if orderInfo was missing address
            if (empty($header['shipping_address_line1']) && empty($header['address_line1'])) {
                $custOrdStmt = $this->conn->prepare("SELECT * FROM vp_order_info WHERE customer_id = ? ORDER BY id DESC LIMIT 1");
                if ($custOrdStmt) {
                    $custOrdStmt->bind_param('i', $customerId);
                    $custOrdStmt->execute();
                    $coRes = $custOrdStmt->get_result();
                    if ($coRow = $coRes->fetch_assoc()) {
                        foreach ($coRow as $k => $v) {
                            if (empty($header[$k]) && !empty($v)) {
                                $header[$k] = $v;
                            }
                        }
                    }
                    $custOrdStmt->close();
                }
            }
        }

        $invoiceId = (int)($invoiceHeader['id'] ?? 0);

        // 7. Fetch international invoice extension if present
        $intlDetails = [];
        if ($invoiceId > 0) {
            $intlStmt = $this->conn->prepare("SELECT * FROM vp_invoices_international WHERE invoice_id = ? LIMIT 1");
            if ($intlStmt) {
                $intlStmt->bind_param('i', $invoiceId);
                $intlStmt->execute();
                $intlRes = $intlStmt->get_result();
                if ($row = $intlRes->fetch_assoc()) {
                    $intlDetails = $row;
                }
                $intlStmt->close();
            }
        }

        // 8. Fetch line items
        $lineItems = [];
        if ($invoiceId > 0) {
            $itemSql = "SELECT ii.*, 
                               COALESCE(NULLIF(ii.hsn, ''), NULLIF(p.hsn, ''), NULLIF(p.hscode, ''), NULLIF(o.hsn, ''), '') AS hsn_code, 
                               COALESCE(NULLIF(p.product_weight, 0), NULLIF(o.product_weight, 0), 0.25) AS product_weight, 
                               o.sku AS order_sku, o.item_code AS order_item_code, o.title AS order_title
                        FROM vp_invoice_items ii
                        LEFT JOIN vp_products p ON p.id = ii.product_id
                        LEFT JOIN vp_orders o ON (CONVERT(o.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(ii.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci AND CONVERT(o.item_code USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(ii.item_code USING utf8mb4) COLLATE utf8mb4_unicode_ci)
                        WHERE ii.invoice_id = ?
                        ORDER BY ii.id ASC";

            $itemStmt = $this->conn->prepare($itemSql);
            if ($itemStmt) {
                $itemStmt->bind_param('i', $invoiceId);
                $itemStmt->execute();
                $itemRes = $itemStmt->get_result();
                while ($item = $itemRes->fetch_assoc()) {
                    $lineItems[] = $item;
                }
                $itemStmt->close();
            }
        }

        // Fallback to vp_orders if no vp_invoice_items exist
        $targetOrderNum = $header['order_number'] ?? '';
        if (empty($lineItems) && $targetOrderNum !== '') {
            $ordStmt = $this->conn->prepare("SELECT o.*, 
                                             COALESCE(NULLIF(o.hsn, ''), NULLIF(p.hsn, ''), NULLIF(p.hscode, ''), '') AS hsn_code, 
                                             COALESCE(NULLIF(o.product_weight, 0), NULLIF(p.product_weight, 0), 0.25) AS product_weight 
                                             FROM vp_orders o 
                                             LEFT JOIN vp_products p ON CONVERT(p.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(o.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci 
                                             WHERE CONVERT(o.order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci AND o.status != 'cancelled'
                                             ORDER BY o.id ASC");
            if ($ordStmt) {
                $ordStmt->bind_param('s', $targetOrderNum);
                $ordStmt->execute();
                $ordRes = $ordStmt->get_result();
                while ($item = $ordRes->fetch_assoc()) {
                    $lineItems[] = [
                        'id' => $item['id'],
                        'invoice_id' => $invoiceId,
                        'order_number' => $item['order_number'],
                        'item_code' => $item['item_code'],
                        'title' => $item['title'],
                        'quantity' => (int)($item['quantity'] ?? 1),
                        'unit_price' => (float)($item['price'] ?? $item['total_price'] ?? 0),
                        'total_price' => (float)($item['total_price'] ?? 0),
                        'hsn_code' => $item['hsn_code'] ?? $item['hsn'] ?? '',
                        'product_weight' => (float)($item['product_weight'] ?? 0),
                        'order_sku' => $item['sku'] ?? ''
                    ];
                }
                $ordStmt->close();
            }
        }

        // Fetch firm details from app_settings
        $firmDetails = [];
        $firmRes = $this->conn->query("SELECT setting_key, setting_value FROM app_settings");
        if ($firmRes) {
            while ($row = $firmRes->fetch_assoc()) {
                if (!empty($row['setting_key'])) {
                    $firmDetails[$row['setting_key']] = $row['setting_value'];
                }
            }
        }

        return [
            'invoice' => $header,
            'international' => $intlDetails,
            'items' => $lineItems,
            'firm' => $firmDetails
        ];
    }

    /**
     * Create a new export document session.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createSession(array $data): array
    {
        $sessionCode = 'EXP-DOC-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 6));
        
        $invoiceId = !empty($data['invoice_id']) ? (int)$data['invoice_id'] : null;
        $invoiceNum = !empty($data['invoice_number']) ? trim($data['invoice_number']) : null;
        $orderNum = !empty($data['order_number']) ? trim($data['order_number']) : null;
        $shipmentType = $data['shipment_type'] ?? 'csb5';
        $category = $data['category'] ?? 'sculpture_painting_home';
        $courierPartner = $data['courier_partner'] ?? 'ups';
        $isDrawback = !empty($data['is_drawback']) ? 1 : 0;
        $hasRodtep = !empty($data['has_rodtep']) ? 1 : 0;
        $hasLacey = !empty($data['has_lacey']) ? 1 : 0;
        $commonDataJson = is_array($data['common_data'] ?? null) ? json_encode($data['common_data'], JSON_UNESCAPED_UNICODE) : null;
        $status = $data['status'] ?? 'draft';
        $createdBy = !empty($data['created_by']) ? (int)$data['created_by'] : null;

        $sql = "INSERT INTO vp_export_document_sessions
                (session_code, invoice_id, invoice_number, order_number, shipment_type, category, courier_partner, is_drawback, has_rodtep, has_lacey, common_data_json, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Database error preparing session insert: ' . $this->conn->error);
        }

        $stmt->bind_param(
            'sisssssiiissi',
            $sessionCode,
            $invoiceId,
            $invoiceNum,
            $orderNum,
            $shipmentType,
            $category,
            $courierPartner,
            $isDrawback,
            $hasRodtep,
            $hasLacey,
            $commonDataJson,
            $status,
            $createdBy
        );

        $stmt->execute();
        $id = $stmt->insert_id;
        $stmt->close();

        return $this->getSessionById($id);
    }

    /**
     * Get a session by ID.
     *
     * @param int $id
     * @return array<string, mixed>|null
     */
    public function getSessionById(int $id): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM vp_export_document_sessions WHERE id = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $session = $res->fetch_assoc();
        $stmt->close();

        if ($session) {
            $session['common_data'] = !empty($session['common_data_json']) ? json_decode($session['common_data_json'], true) : [];
        }

        return $session;
    }

    /**
     * Get a session by session_code.
     *
     * @param string $sessionCode
     * @return array<string, mixed>|null
     */
    public function getSessionByCode(string $sessionCode): ?array
    {
        $stmt = $this->conn->prepare("SELECT * FROM vp_export_document_sessions WHERE session_code = ? LIMIT 1");
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('s', $sessionCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $session = $res->fetch_assoc();
        $stmt->close();

        if ($session) {
            $session['common_data'] = !empty($session['common_data_json']) ? json_decode($session['common_data_json'], true) : [];
        }

        return $session;
    }

    /**
     * Update session header & common info.
     *
     * @param int $id
     * @param array<string, mixed> $data
     * @return bool
     */
    public function updateSession(int $id, array $data): bool
    {
        $fields = [];
        $types = '';
        $values = [];

        if (isset($data['shipment_type'])) {
            $fields[] = 'shipment_type = ?';
            $types .= 's';
            $values[] = $data['shipment_type'];
        }
        if (isset($data['category'])) {
            $fields[] = 'category = ?';
            $types .= 's';
            $values[] = $data['category'];
        }
        if (isset($data['courier_partner'])) {
            $fields[] = 'courier_partner = ?';
            $types .= 's';
            $values[] = $data['courier_partner'];
        }
        if (isset($data['is_drawback'])) {
            $fields[] = 'is_drawback = ?';
            $types .= 'i';
            $values[] = !empty($data['is_drawback']) ? 1 : 0;
        }
        if (isset($data['has_rodtep'])) {
            $fields[] = 'has_rodtep = ?';
            $types .= 'i';
            $values[] = !empty($data['has_rodtep']) ? 1 : 0;
        }
        if (isset($data['has_lacey'])) {
            $fields[] = 'has_lacey = ?';
            $types .= 'i';
            $values[] = !empty($data['has_lacey']) ? 1 : 0;
        }
        if (array_key_exists('common_data', $data)) {
            $fields[] = 'common_data_json = ?';
            $types .= 's';
            $values[] = is_array($data['common_data']) ? json_encode($data['common_data'], JSON_UNESCAPED_UNICODE) : null;
        }
        if (isset($data['status'])) {
            $fields[] = 'status = ?';
            $types .= 's';
            $values[] = $data['status'];
        }

        if (empty($fields)) {
            return true;
        }

        $sql = "UPDATE vp_export_document_sessions SET " . implode(', ', $fields) . " WHERE id = ?";
        $types .= 'i';
        $values[] = $id;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$values);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * Save/upsert data for a specific document in a session.
     *
     * @param int $sessionId
     * @param string $docCode
     * @param string $docTitle
     * @param array<string, mixed> $formData
     * @param bool $isCompleted
     * @return bool
     */
    public function saveForm(int $sessionId, string $docCode, string $docTitle, array $formData, bool $isCompleted = false): bool
    {
        $json = json_encode($formData, JSON_UNESCAPED_UNICODE);
        $completedInt = $isCompleted ? 1 : 0;

        $sql = "INSERT INTO vp_export_document_forms (session_id, document_code, document_title, form_data_json, is_completed)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                document_title = VALUES(document_title),
                form_data_json = VALUES(form_data_json),
                is_completed = VALUES(is_completed),
                updated_at = NOW()";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('isssi', $sessionId, $docCode, $docTitle, $json, $completedInt);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * Get form data for a single document in a session.
     *
     * @param int $sessionId
     * @param string $docCode
     * @return array<string, mixed>|null
     */
    public function getForm(int $sessionId, string $docCode): ?array
    {
        $sql = "SELECT * FROM vp_export_document_forms WHERE session_id = ? AND document_code = ? LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('is', $sessionId, $docCode);
        $stmt->execute();
        $res = $stmt->get_result();
        $form = $res->fetch_assoc();
        $stmt->close();

        if ($form) {
            $form['form_data'] = !empty($form['form_data_json']) ? json_decode($form['form_data_json'], true) : [];
        }

        return $form;
    }

    /**
     * Get all form documents for a session keyed by document_code.
     *
     * @param int $sessionId
     * @return array<string, array<string, mixed>>
     */
    public function getAllFormsForSession(int $sessionId): array
    {
        $sql = "SELECT * FROM vp_export_document_forms WHERE session_id = ? ORDER BY id ASC";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('i', $sessionId);
        $stmt->execute();
        $res = $stmt->get_result();

        $forms = [];
        while ($row = $res->fetch_assoc()) {
            $row['form_data'] = !empty($row['form_data_json']) ? json_decode($row['form_data_json'], true) : [];
            $forms[$row['document_code']] = $row;
        }
        $stmt->close();

        return $forms;
    }

    /**
     * List recent export document sessions for the history table.
     *
     * @param array<string, mixed> $filters
     * @param int $limit
     * @param int $offset
     * @return array<int, array<string, mixed>>
     */
    public function listSessions(array $filters = [], int $limit = 20, int $offset = 0): array
    {
        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $where[] = '(CONVERT(session_code USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? OR CONVERT(invoice_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? OR CONVERT(order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?)';
            $like = '%' . trim($filters['search']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }

        if (!empty($filters['shipment_type'])) {
            $where[] = 'shipment_type = ?';
            $params[] = $filters['shipment_type'];
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT s.*, u.name AS created_by_name
                FROM vp_export_document_sessions s
                LEFT JOIN vp_users u ON u.id = s.created_by
                WHERE {$whereSql}
                ORDER BY s.id DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();

        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $row['common_data'] = !empty($row['common_data_json']) ? json_decode($row['common_data_json'], true) : [];
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Count total export document sessions for pagination.
     *
     * @param array<string, mixed> $filters
     * @return int
     */
    public function countSessions(array $filters = []): int
    {
        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['search'])) {
            $where[] = '(CONVERT(session_code USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? OR CONVERT(invoice_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ? OR CONVERT(order_number USING utf8mb4) COLLATE utf8mb4_unicode_ci LIKE ?)';
            $like = '%' . trim($filters['search']) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $types .= 'sss';
        }

        if (!empty($filters['shipment_type'])) {
            $where[] = 'shipment_type = ?';
            $params[] = $filters['shipment_type'];
            $types .= 's';
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }

        $whereSql = implode(' AND ', $where);
        $sql = "SELECT COUNT(*) AS total FROM vp_export_document_sessions WHERE {$whereSql}";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }
}
