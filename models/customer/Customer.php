<?php
class Customer
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
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
        $sql = "SELECT 
                    oi.*, 
                    o.*
                FROM vp_order_info AS oi
                JOIN vp_orders AS o ON oi.order_number = o.order_number WHERE o.customer_id = ?";
        $params = [];
        $types = "i";
        array_push($params, $customer_id);

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR o.item_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        //sort
        $sort = $filters['sort'] ?? 'new_to_old';
        $sql .= " ORDER BY o.order_date " . ($sort === 'old_to_new' ? 'ASC' : 'DESC') . " LIMIT ? OFFSET ?";
        array_push($params, $limit, $offset);
        $types .= "ii";
        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    public function getCustomerOrderCount($customer_id, $filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM vp_order_info AS oi JOIN vp_orders AS o ON oi.order_number = o.order_number WHERE o.customer_id = ?";
        $params = [];
        $types = "i";
        array_push($params, $customer_id);

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE ? OR o.title LIKE ? OR o.item_code LIKE ?)";
            $searchTerm = "%" . $filters['search'] . "%";
            array_push($params, $searchTerm, $searchTerm, $searchTerm);
            $types .= "sss";
        }

        $stmt = $this->conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['total'];
    }
    public function getCustomerTotalSpent($customer_id)
    {
        $sql = "SELECT 
                    COUNT(*) AS total_orders, 
                    SUM(finalprice) AS total_spent, 
                    AVG(finalprice) AS average_order_value
                FROM vp_orders
                WHERE customer_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getCustomerOrderStatusCounts($customer_id)
    {
        $sql = "SELECT 
                    COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending,
                    COUNT(CASE WHEN o.status IN ('ready_for_packing', 'po_pending', 'po_approved', 'po_inprogress', 'item_received', 'added_to_picklist', 'store_transfer', 'ready_for_qc', 'sent_for_repair') THEN 1 END) as progress,
                    COUNT(CASE WHEN o.status = 'shipped' THEN 1 END) as completed,
                    COUNT(CASE WHEN o.status = 'cancelled' THEN 1 END) as cancelled
                FROM vp_orders o
                WHERE o.customer_id = ?";

        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
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

    /** All customers with purchase totals across all orders (customer list). */
    public function getAllCustomersWithPurchaseStats(string $search, int $limit, int $offset): array
    {
        $search = trim($search);
        $params = [];
        $types = '';
        $searchSql = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $searchSql = ' WHERE (vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?) ';
            $params = [$term, $term, $term];
            $types = 'sss';
        }

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
                $searchSql
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

    public function countAllCustomersWithPurchaseStats(string $search): int
    {
        $search = trim($search);
        $params = [];
        $types = '';
        $searchSql = '';
        if ($search !== '') {
            $term = '%' . $search . '%';
            $searchSql = ' WHERE (vc.name LIKE ? OR vc.email LIKE ? OR vc.phone LIKE ?) ';
            $params = [$term, $term, $term];
            $types = 'sss';
        }

        $sql = "SELECT COUNT(*) AS c FROM (
                    SELECT vc.id
                    FROM vp_customers vc
                    $searchSql
                    GROUP BY vc.id
                ) t";

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
