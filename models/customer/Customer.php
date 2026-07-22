<?php

require_once __DIR__ . '/../../helpers/order_list_filters.php';

class Customer
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array{sql:string,types:string,params:array<int,mixed>}
     */
    private function buildCustomerOrdersFilterClause(int $customerId, array $filters): array
    {
        $types = 'i';
        $params = [$customerId];
        $sql = '';

        if (!empty($filters['search'])) {
            $searchTerm = '%' . $filters['search'] . '%';
            $sql .= ' AND (o.order_number LIKE ? OR o.title LIKE ? OR o.item_code LIKE ? OR o.sku LIKE ? OR o.status LIKE ?)';
            array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            $types .= 'sssss';
        }

        $statusGroup = $filters['status_group'] ?? 'all';
        if ($statusGroup !== '' && $statusGroup !== 'all') {
            if ($statusGroup === 'cancelled') {
                $sql .= " AND o.status = 'cancelled'";
            } elseif ($statusGroup === 'progress') {
                $sql .= " AND o.status IN ('ready_for_packing','po_pending','po_approved','po_inprogress','item_received','added_to_picklist','store_transfer','ready_for_qc','sent_for_repair','ready_for_dispatch')";
            } elseif ($statusGroup === 'completed') {
                appendOrderStatusFilterSql($sql, $params, ['status_filter' => 'shipped'], 'o.status');
            } elseif ($statusGroup === 'pending') {
                appendOrderStatusFilterSql($sql, $params, ['status_filter' => 'pending'], 'o.status');
            }
        }

        if (!empty($filters['payment_type']) && $filters['payment_type'] !== 'all') {
            appendOrderPaymentTypeFilterSql($sql, $params, $filters, 'o.payment_type');
        }

        if (!empty($filters['date_from'])) {
            $sql .= ' AND o.order_date >= ?';
            $params[] = $filters['date_from'];
            $types .= 's';
        }
        if (!empty($filters['date_to'])) {
            $sql .= ' AND o.order_date <= ?';
            $params[] = $filters['date_to'] . ' 23:59:59';
            $types .= 's';
        }

        return ['sql' => $sql, 'types' => $types, 'params' => $params];
    }

    private function customerOrdersOrderBy(array $filters): string
    {
        $sort = $filters['sort'] ?? 'new_to_old';
        if ($sort === 'old_to_new') {
            return ' ORDER BY o.order_date ASC, o.id ASC';
        }
        if ($sort === 'ship_by_date_desc') {
            return ' ORDER BY o.esd DESC, o.order_date DESC, o.id ASC';
        }
        if ($sort === 'ship_by_date_asc') {
            return ' ORDER BY o.esd ASC, o.order_date ASC, o.id ASC';
        }

        return ' ORDER BY o.order_date DESC, o.id DESC';
    }

    public function countOrderItemsByCustomerId(int $customerId, array $filters = []): int
    {
        $clause = $this->buildCustomerOrdersFilterClause($customerId, $filters);
        $sql = 'SELECT COUNT(*) AS total
                FROM vp_orders AS o
                WHERE o.customer_id = ?' . $clause['sql'];

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param($clause['types'], ...$clause['params']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOrderItemsByCustomerIdForExport(int $customerId, array $filters = []): array
    {
        $clause = $this->buildCustomerOrdersFilterClause($customerId, $filters);
        $sql = 'SELECT o.*, oi.*, inv.invoice_number, inv.id AS linked_invoice_id
                FROM vp_orders AS o
                LEFT JOIN vp_order_info AS oi ON oi.order_number = o.order_number
                LEFT JOIN vp_invoices inv ON inv.id = o.invoice_id
                WHERE o.customer_id = ?' . $clause['sql'] . $this->customerOrdersOrderBy($filters);

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($clause['types'], ...$clause['params']);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function getCustomerOrderDateRange(int $customerId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT MIN(order_date) AS first_order_date, MAX(order_date) AS last_order_date
             FROM vp_orders WHERE customer_id = ?'
        );
        if (!$stmt) {
            return ['first_order_date' => null, 'last_order_date' => null];
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        return [
            'first_order_date' => $row['first_order_date'] ?? null,
            'last_order_date' => $row['last_order_date'] ?? null,
        ];
    }

    public function getCustomerInsights(int $customerId): array
    {
        $insights = [
            'open_order_value' => 0.0,
            'cancellation_rate' => 0.0,
            'preferred_payment_type' => '',
            'is_repeat_customer' => false,
            'avg_days_between_orders' => null,
            'top_items' => [],
            'distinct_order_count' => 0,
        ];

        $stmt = $this->conn->prepare(
            "SELECT
                COUNT(*) AS line_count,
                COUNT(DISTINCT order_number) AS distinct_orders,
                SUM(CASE WHEN status NOT IN ('shipped','cancelled') THEN finalprice ELSE 0 END) AS open_value,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
             FROM vp_orders WHERE customer_id = ?"
        );
        if (!$stmt) {
            return $insights;
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $summary = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $lineCount = (int)($summary['line_count'] ?? 0);
        $distinctOrders = (int)($summary['distinct_orders'] ?? 0);
        $cancelledCount = (int)($summary['cancelled_count'] ?? 0);

        $insights['open_order_value'] = (float)($summary['open_value'] ?? 0);
        $insights['distinct_order_count'] = $distinctOrders;
        $insights['is_repeat_customer'] = $distinctOrders >= 2;
        $insights['cancellation_rate'] = $lineCount > 0 ? round(($cancelledCount / $lineCount) * 100, 1) : 0.0;

        $payStmt = $this->conn->prepare(
            "SELECT payment_type, COUNT(*) AS cnt FROM vp_orders
             WHERE customer_id = ? AND payment_type IS NOT NULL AND payment_type <> ''
             GROUP BY payment_type ORDER BY cnt DESC LIMIT 1"
        );
        if ($payStmt) {
            $payStmt->bind_param('i', $customerId);
            $payStmt->execute();
            $payRow = $payStmt->get_result()->fetch_assoc();
            $payStmt->close();
            $insights['preferred_payment_type'] = (string)($payRow['payment_type'] ?? '');
        }

        $avgStmt = $this->conn->prepare(
            'SELECT order_date FROM vp_orders WHERE customer_id = ? ORDER BY order_date ASC'
        );
        if ($avgStmt) {
            $avgStmt->bind_param('i', $customerId);
            $avgStmt->execute();
            $dates = $avgStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $avgStmt->close();
            if (count($dates) >= 2) {
                $totalDays = 0;
                $gaps = 0;
                for ($i = 1, $n = count($dates); $i < $n; $i++) {
                    $prev = strtotime((string)$dates[$i - 1]['order_date']);
                    $curr = strtotime((string)$dates[$i]['order_date']);
                    if ($prev && $curr && $curr >= $prev) {
                        $totalDays += (int)round(($curr - $prev) / 86400);
                        $gaps++;
                    }
                }
                if ($gaps > 0) {
                    $insights['avg_days_between_orders'] = round($totalDays / $gaps, 1);
                }
            }
        }

        $topStmt = $this->conn->prepare(
            "SELECT item_code, title, COUNT(*) AS order_count
             FROM vp_orders WHERE customer_id = ? AND item_code IS NOT NULL AND item_code <> ''
             GROUP BY item_code, title ORDER BY order_count DESC LIMIT 3"
        );
        if ($topStmt) {
            $topStmt->bind_param('i', $customerId);
            $topStmt->execute();
            $insights['top_items'] = $topStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $topStmt->close();
        }

        return $insights;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getInvoicesByCustomerId(int $customerId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->conn->prepare(
            'SELECT id, invoice_number, invoice_date, total_amount, status, currency, created_at
             FROM vp_invoices WHERE customer_id = ? ORDER BY invoice_date DESC, id DESC LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $customerId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getDispatchesByCustomerId(int $customerId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $stmt = $this->conn->prepare(
            'SELECT d.id, d.order_number, d.awb_code, d.courier_name, d.shipment_status,
                    d.dispatch_date, d.tracking_url, d.etd, d.edd, i.invoice_number, i.id AS invoice_id
             FROM vp_dispatch_details d
             INNER JOIN vp_invoices i ON i.id = d.invoice_id
             WHERE i.customer_id = ?
             ORDER BY d.dispatch_date DESC, d.id DESC
             LIMIT ?'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $customerId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getCustomerActivityLog(int $customerId, int $limit = 30): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->conn->prepare(
            "SELECT order_number, status, remarks, updated_at, order_date
             FROM vp_orders
             WHERE customer_id = ?
               AND (
                    (remarks IS NOT NULL AND TRIM(remarks) <> '')
                    OR status IN ('shipped','cancelled')
               )
             ORDER BY COALESCE(updated_at, order_date) DESC
             LIMIT ?"
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $customerId, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }

    public function getCustomers($search = '', $state = '', $limit = 10, $offset = 0)
    {
        $sql = "SELECT 
                    vc.*,
                    COALESCE(SUM(o.finalprice), 0) AS total_order_amount,
                    MAX(o.order_date) AS last_purchase_date,
                    SUBSTRING_INDEX(GROUP_CONCAT(o.currency ORDER BY o.id DESC SEPARATOR ','), ',', 1) AS currency,
                    SUBSTRING_INDEX(GROUP_CONCAT(voi.state ORDER BY o.id DESC SEPARATOR ','), ',', 1) AS state
                FROM vp_customers AS vc
                LEFT JOIN vp_orders AS o ON o.customer_id = vc.id
                LEFT JOIN vp_order_info AS voi ON voi.order_number = o.order_number";

        // Build WHERE Clause
        $where = [];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $where[] = "(vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?)";
            $searchTerm = "%" . $search . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($state)) {
            $where[] = "voi.state = ?";
            $params[] = $state;
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        // --- FIX: GROUP BY vc.id prevents duplicates from order_info ---
        $sql .= " GROUP BY vc.id ";

        // Add Pagination
        $sql .= " ORDER BY vc.id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTotalCustomersCount($search = '', $state = '')
    {
        // --- FIX: Use DISTINCT to count unique customers only ---
        $sql = "SELECT COUNT(DISTINCT vc.id) as total FROM vp_customers AS vc
                LEFT JOIN vp_orders AS o ON o.customer_id = vc.id
                LEFT JOIN vp_order_info AS voi ON voi.order_number = o.order_number";

        $where = [];
        $params = [];
        $types = "";

        if (!empty($search)) {
            $where[] = "(vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?)";
            $searchTerm = "%" . $search . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($state)) {
            $where[] = "voi.state = ?";
            $params[] = $state;
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }

    public function getUniqueStates()
    {
        // Get distinct states from order info so the dropdown only shows valid options
        $query = "SELECT DISTINCT state FROM vp_order_info WHERE state IS NOT NULL AND state != '' ORDER BY state ASC";
        $result = $this->conn->query($query);

        $states = [];
        while ($row = $result->fetch_assoc()) {
            $states[] = $row['state'];
        }
        return $states;
    }
    public function getAllCustomers($limit, $offset = 0, $filters = [])
    {
        $sql = "SELECT * FROM vp_customers";
        $params = [];
        $types = "";
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($filters['state'])) {
            $where[] = "state = ?";
            $params[] = $filters['state'];
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        array_push($params, $limit, $offset);
        $types .= "ii";

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * POS customer autocomplete: search vp_customers by name, email, or phone (LIKE).
     * Escapes LIKE wildcards in the user's input. Intended to be called when query length >= 2.
     *
     * @return array<int, array{id:int,name:string,email:string,phone:string,display:string}>
     */
    public function searchCustomersForPos(string $search, int $limit = 40): array
    {
        $search = trim($search);
        $len = function_exists('mb_strlen') ? mb_strlen($search, 'UTF-8') : strlen($search);
        if ($len < 2) {
            return [];
        }

        $limit = max(1, min(80, $limit));

        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
        $term = '%' . $escaped . '%';

        $sql = 'SELECT id, name, email, phone FROM vp_customers
                WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?
                ORDER BY id DESC
                LIMIT ?';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('sssi', $term, $term, $term, $limit);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $out = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $name = (string)($row['name'] ?? '');
            $email = (string)($row['email'] ?? '');
            $phone = (string)($row['phone'] ?? '');
            $display = $name . ' | ' . $phone . ($email !== '' ? ' | ' . $email : '');
            $out[] = [
                'id' => $id,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'display' => $display,
            ];
        }

        return $out;
    }

    public function countAllCustomers($filters = [])
    {

        $sql = "SELECT COUNT(*) as total FROM vp_customers";
        $params = [];
        $types = "";
        $where = [];

        if (!empty($filters['search'])) {
            $where[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        if (!empty($filters['state'])) {
            $where[] = "state = ?";
            $params[] = $filters['state'];
            $types .= "s";
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    public function getCustomerById($customer_id)
    {
        $sql = "SELECT * FROM vp_customers WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    /**
     * Billing/shipping arrays for POS address modal: vp_customers + pos_customer_details.
     * Keys match POSRegisterController::customer_order_info() / setAddressConfirmFields() expectations.
     *
     * @return array{billing: array<string, string>, shipping: array<string, string>}
     */
    public function getCustomerBillingShippingForPos(int $customerId): array
    {
        $billing = [];
        $shipping = [];
        if ($customerId <= 0) {
            return ['billing' => $billing, 'shipping' => $shipping];
        }
        $row = $this->getCustomerById($customerId);
        if (!$row || empty($row['id'])) {
            return ['billing' => $billing, 'shipping' => $shipping];
        }

        $fullName = trim((string)($row['name'] ?? ''));
        $parts = $fullName !== '' ? preg_split('/\s+/u', $fullName, 2, PREG_SPLIT_NO_EMPTY) : [];
        $first = $parts[0] ?? '';
        $last = isset($parts[1]) ? trim((string)$parts[1]) : '';

        $billing = [
            'first_name' => $first,
            'last_name' => $last,
            'email' => trim((string)($row['email'] ?? '')),
            'phone' => trim((string)($row['phone'] ?? '')),
            'address1' => '',
            'address2' => '',
            'city' => '',
            'state' => '',
            'zip' => '',
            'country' => 'IN',
            'gstin' => '',
        ];

        // Optional extended columns on vp_customers (if present in DB)
        $vcExtras = [
            'billing_address_line1' => 'address1',
            'billing_address_line2' => 'address2',
            'billing_city' => 'city',
            'billing_state' => 'state',
            'billing_zip' => 'zip',
            'billing_zipcode' => 'zip',
            'billing_country' => 'country',
            'gstin' => 'gstin',
        ];
        foreach ($vcExtras as $col => $key) {
            if (!empty($row[$col])) {
                $billing[$key] = trim((string)$row[$col]);
            }
        }

        $this->ensurePosCustomerDetailsTable();
        $stmt = $this->conn->prepare('SELECT * FROM pos_customer_details WHERE customer_id = ? LIMIT 1');
        if ($stmt) {
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $det = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (is_array($det)) {
                if (trim((string)($det['bill_line1'] ?? '')) !== '') {
                    $billing['address1'] = trim((string)$det['bill_line1']);
                }
                if (trim((string)($det['bill_line2'] ?? '')) !== '') {
                    $billing['address2'] = trim((string)$det['bill_line2']);
                }
                if (trim((string)($det['bill_city'] ?? '')) !== '') {
                    $billing['city'] = trim((string)$det['bill_city']);
                }
                if (trim((string)($det['bill_state'] ?? '')) !== '') {
                    $billing['state'] = trim((string)$det['bill_state']);
                }
                if (trim((string)($det['bill_pin'] ?? '')) !== '') {
                    $billing['zip'] = trim((string)$det['bill_pin']);
                }
                if (trim((string)($det['bill_country'] ?? '')) !== '') {
                    $billing['country'] = trim((string)$det['bill_country']);
                }
                if (trim((string)($det['gstin'] ?? '')) !== '') {
                    $billing['gstin'] = trim((string)$det['gstin']);
                }

                $shipping = [
                    'shipping_first_name' => $first,
                    'shipping_last_name' => $last,
                    'sname' => trim($first . ' ' . $last),
                    'saddress1' => trim((string)($det['ship_line1'] ?? '')),
                    'saddress2' => trim((string)($det['ship_line2'] ?? '')),
                    'scity' => trim((string)($det['ship_city'] ?? '')),
                    'sstate' => trim((string)($det['ship_state'] ?? '')),
                    'szip' => trim((string)($det['ship_pin'] ?? '')),
                    'scountry' => trim((string)($det['ship_country'] ?? '')) !== '' ? trim((string)$det['ship_country']) : 'IN',
                    'sphone' => $billing['phone'],
                ];
            }
        }

        if ($shipping === []) {
            $shipping = [
                'shipping_first_name' => $first,
                'shipping_last_name' => $last,
                'sname' => trim($first . ' ' . $last),
                'saddress1' => '',
                'saddress2' => '',
                'scity' => '',
                'sstate' => '',
                'szip' => '',
                'scountry' => 'IN',
                'sphone' => $billing['phone'],
            ];
        }

        return ['billing' => $billing, 'shipping' => $shipping];
    }
    public function getOrderItemsByCustomerId($customer_id, $limit = 10, $offset = 0, $filters = [])
    {
        $customerId = (int)$customer_id;
        $clause = $this->buildCustomerOrdersFilterClause($customerId, $filters);
        $sql = 'SELECT o.*, oi.*, inv.invoice_number, inv.id AS linked_invoice_id
                FROM vp_orders AS o
                LEFT JOIN vp_order_info AS oi ON oi.order_number = o.order_number
                LEFT JOIN vp_invoices inv ON inv.id = o.invoice_id
                WHERE o.customer_id = ?' . $clause['sql'] . $this->customerOrdersOrderBy($filters) . ' LIMIT ? OFFSET ?';

        $types = $clause['types'] . 'ii';
        $params = array_merge($clause['params'], [(int)$limit, (int)$offset]);

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        return $rows;
    }
    public function getCustomerOrderCount($customer_id, $filters = [])
    {
        return $this->countOrderItemsByCustomerId((int)$customer_id, $filters);
    }
    public function getCustomerTotalSpent($customer_id)
    {
        $summary = $this->getCustomerHeaderSummary((int)$customer_id);
        return [
            'total_orders' => $summary['line_count'],
            'total_spent' => $summary['total_spent'],
            'average_order_value' => $summary['average_order_value'],
        ];
    }

    /**
     * Single-query header stats for customer detail (avoids multiple round-trips).
     *
     * @return array<string, mixed>
     */
    public function getCustomerHeaderSummary(int $customerId): array
    {
        static $cache = [];
        if (isset($cache[$customerId])) {
            return $cache[$customerId];
        }

        $defaults = [
            'line_count' => 0,
            'total_spent' => 0.0,
            'average_order_value' => 0.0,
            'first_order_date' => null,
            'last_order_date' => null,
            'open_order_value' => 0.0,
            'pending' => 0,
            'progress' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'primary_currency' => 'INR',
        ];
        if ($customerId <= 0) {
            return $defaults;
        }

        $sql = "SELECT
                    COUNT(*) AS line_count,
                    COALESCE(SUM(finalprice), 0) AS total_spent,
                    COALESCE(AVG(finalprice), 0) AS average_order_value,
                    MIN(order_date) AS first_order_date,
                    MAX(order_date) AS last_order_date,
                    COALESCE(SUM(CASE WHEN status NOT IN ('shipped','cancelled') THEN finalprice ELSE 0 END), 0) AS open_order_value,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) AS pending,
                    COUNT(CASE WHEN status IN ('ready_for_packing','po_pending','po_approved','po_inprogress','item_received','added_to_picklist','store_transfer','ready_for_qc','sent_for_repair','ready_for_dispatch') THEN 1 END) AS progress,
                    COUNT(CASE WHEN status = 'shipped' THEN 1 END) AS completed,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) AS cancelled,
                    SUBSTRING_INDEX(GROUP_CONCAT(currency ORDER BY order_date DESC SEPARATOR ','), ',', 1) AS primary_currency
                FROM vp_orders
                WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return $defaults;
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc() ?: [];
        $stmt->close();

        $cache[$customerId] = array_merge($defaults, $row);
        return $cache[$customerId];
    }

    public function getCustomerOrderStatusCounts($customer_id)
    {
        $summary = $this->getCustomerHeaderSummary((int)$customer_id);
        return [
            'pending' => (int)($summary['pending'] ?? 0),
            'progress' => (int)($summary['progress'] ?? 0),
            'completed' => (int)($summary['completed'] ?? 0),
            'cancelled' => (int)($summary['cancelled'] ?? 0),
        ];
    }

    /** Used when deleting a customer (cleans optional extended rows). */
    public function ensurePosCustomerDetailsTable(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $this->conn->query(
            "CREATE TABLE IF NOT EXISTS pos_customer_details (
                customer_id INT UNSIGNED NOT NULL PRIMARY KEY,
                bill_line1 VARCHAR(255) NOT NULL DEFAULT '',
                bill_line2 VARCHAR(255) NOT NULL DEFAULT '',
                bill_city VARCHAR(128) NOT NULL DEFAULT '',
                bill_state VARCHAR(128) NOT NULL DEFAULT '',
                bill_country VARCHAR(128) NOT NULL DEFAULT '',
                bill_pin VARCHAR(32) NOT NULL DEFAULT '',
                ship_line1 VARCHAR(255) NOT NULL DEFAULT '',
                ship_line2 VARCHAR(255) NOT NULL DEFAULT '',
                ship_city VARCHAR(128) NOT NULL DEFAULT '',
                ship_state VARCHAR(128) NOT NULL DEFAULT '',
                ship_country VARCHAR(128) NOT NULL DEFAULT '',
                ship_pin VARCHAR(32) NOT NULL DEFAULT '',
                gstin VARCHAR(64) NOT NULL DEFAULT '',
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $done = true;
    }

    /**
     * Persist Add Customer modal billing/shipping + GSTIN to pos_customer_details (UPSERT by customer_id).
     *
     * @param array<string, mixed> $post Typically $_POST from POS add-customer form.
     */
    public function upsertPosCustomerDetailsFromPost(int $customerId, array $post): bool
    {
        if ($customerId <= 0) {
            return false;
        }
        $this->ensurePosCustomerDetailsTable();

        $bill1 = trim((string)($post['address_line1'] ?? ''));
        $bill2 = trim((string)($post['address_line2'] ?? ''));
        $billCity = trim((string)($post['city'] ?? ''));
        $billState = trim((string)($post['state'] ?? ''));
        $billPin = trim((string)($post['zipcode'] ?? ''));
        $billCountry = trim((string)($post['country'] ?? ''));
        if ($billCountry === '') {
            $billCountry = 'IN';
        }

        $ship1 = trim((string)($post['shipping_address_line1'] ?? ''));
        $ship2 = trim((string)($post['shipping_address_line2'] ?? ''));
        $shipCity = trim((string)($post['shipping_city'] ?? ''));
        $shipState = trim((string)($post['shipping_state'] ?? ''));
        $shipPin = trim((string)($post['shipping_zipcode'] ?? ''));
        $shipCountry = trim((string)($post['shipping_country'] ?? ''));
        if ($shipCountry === '') {
            $shipCountry = 'IN';
        }

        $gstin = trim((string)($post['gstin'] ?? ''));

        $sql = 'INSERT INTO pos_customer_details (
            customer_id, bill_line1, bill_line2, bill_city, bill_state, bill_country, bill_pin,
            ship_line1, ship_line2, ship_city, ship_state, ship_country, ship_pin, gstin
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ON DUPLICATE KEY UPDATE
            bill_line1 = VALUES(bill_line1),
            bill_line2 = VALUES(bill_line2),
            bill_city = VALUES(bill_city),
            bill_state = VALUES(bill_state),
            bill_country = VALUES(bill_country),
            bill_pin = VALUES(bill_pin),
            ship_line1 = VALUES(ship_line1),
            ship_line2 = VALUES(ship_line2),
            ship_city = VALUES(ship_city),
            ship_state = VALUES(ship_state),
            ship_country = VALUES(ship_country),
            ship_pin = VALUES(ship_pin),
            gstin = VALUES(gstin)';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $types = 'i' . str_repeat('s', 13);
        $stmt->bind_param(
            $types,
            $customerId,
            $bill1,
            $bill2,
            $billCity,
            $billState,
            $billCountry,
            $billPin,
            $ship1,
            $ship2,
            $shipCity,
            $shipState,
            $shipCountry,
            $shipPin,
            $gstin
        );

        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    /**
     * Maps POS "Confirm Billing & Shipping" JSON payload into pos_customer_details upsert.
     *
     * @param array<string, mixed> $p Keys confirm_* from checkout-create JSON body.
     */
    public function upsertPosCustomerDetailsFromConfirmPayload(int $customerId, array $p): bool
    {
        $post = [
            'address_line1' => $p['confirm_address1'] ?? '',
            'address_line2' => $p['confirm_address2'] ?? '',
            'city' => $p['confirm_city'] ?? '',
            'state' => $p['confirm_state'] ?? '',
            'zipcode' => $p['confirm_zip'] ?? '',
            'country' => $p['confirm_country'] ?? 'IN',
            'gstin' => $p['confirm_gstin'] ?? '',
            'shipping_address_line1' => $p['confirm_saddress1'] ?? '',
            'shipping_address_line2' => $p['confirm_saddress2'] ?? '',
            'shipping_city' => $p['confirm_scity'] ?? '',
            'shipping_state' => $p['confirm_sstate'] ?? '',
            'shipping_zipcode' => $p['confirm_szip'] ?? '',
            'shipping_country' => $p['confirm_scountry'] ?? 'IN',
        ];

        return $this->upsertPosCustomerDetailsFromPost($customerId, $post);
    }

    /**
     * Build WHERE clauses for customer list filters (name/email/phone, order number).
     *
     * @return array{where:string,params:array<int,mixed>,types:string}
     */
    private function buildCustomerListFilterClauses(array $filters): array
    {
        $where = [];
        $params = [];
        $types = '';

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $term = '%' . $search . '%';
            $where[] = '(vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?)';
            array_push($params, $term, $term, $term);
            $types .= 'sss';
        }

        $orderNumber = trim((string)($filters['order_number'] ?? ''));
        if ($orderNumber !== '') {
            $where[] = 'EXISTS (
                SELECT 1 FROM vp_orders o_f
                WHERE o_f.customer_id = vc.id AND o_f.order_number LIKE ?
            )';
            $params[] = '%' . $orderNumber . '%';
            $types .= 's';
        }

        return [
            'where' => !empty($where) ? ' WHERE ' . implode(' AND ', $where) : '',
            'params' => $params,
            'types' => $types,
        ];
    }

    /** All customers with purchase totals across all orders (customer list). */
    public function getAllCustomersWithPurchaseStats(array $filters, int $limit, int $offset): array
    {
        $filterClauses = $this->buildCustomerListFilterClauses($filters);
        $params = $filterClauses['params'];
        $types = $filterClauses['types'];

        $sql = "SELECT 
                    vc.id,
                    vc.name,
                    vc.email,
                    vc.phone,
                    COALESCE(SUM(o.finalprice), 0) AS total_order_amount,
                    MAX(o.order_date) AS last_purchase_date,
                    MAX(o.currency) AS currency,
                    COUNT(DISTINCT o.order_number) AS order_count
                FROM vp_customers vc
                LEFT JOIN vp_orders o ON o.customer_id = vc.id
                {$filterClauses['where']}
                GROUP BY vc.id, vc.name, vc.email, vc.phone
                ORDER BY vc.id DESC
                LIMIT ? OFFSET ?";

        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    public function countAllCustomersWithPurchaseStats(array $filters): int
    {
        $filterClauses = $this->buildCustomerListFilterClauses($filters);
        $params = $filterClauses['params'];
        $types = $filterClauses['types'];

        $sql = "SELECT COUNT(*) AS c
                FROM vp_customers vc
                {$filterClauses['where']}";

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    public function countOrdersForCustomer(int $customerId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_orders WHERE customer_id = ?');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $customerId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return (int)($row['c'] ?? 0);
    }

    public function deleteCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return ['success' => false, 'message' => 'Invalid customer.'];
        }
        if ($this->countOrdersForCustomer($customerId) > 0) {
            return ['success' => false, 'message' => 'This customer has orders and cannot be deleted.'];
        }
        $this->ensurePosCustomerDetailsTable();
        $stmt = $this->conn->prepare('DELETE FROM pos_customer_details WHERE customer_id = ?');
        if ($stmt) {
            $stmt->bind_param('i', $customerId);
            $stmt->execute();
            $stmt->close();
        }
        $stmt = $this->conn->prepare('DELETE FROM vp_customers WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Database error.'];
        }
        $stmt->bind_param('i', $customerId);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok
            ? ['success' => true, 'message' => 'Customer removed.']
            : ['success' => false, 'message' => 'Delete failed.'];
    }

}
