<?php

class Payment
{
    private const LIST_AJAX_DEFAULT_LIMIT = 250;
    private const LIST_AJAX_EXACT_ORDER_LIMIT = 100;
    private const ORDER_NUMBER_COLLATE = 'utf8mb4_unicode_ci';

    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function countAll(): int
    {
        $res = $this->db->query('SELECT COUNT(*) AS total FROM pos_payments');
        if (!$res) {
            return 0;
        }
        $row = $res->fetch_assoc();

        return (int)($row['total'] ?? 0);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPaginatedList(int $offset, int $limit): array
    {
        $offset = max(0, $offset);
        $limit = max(1, $limit);

        $sql = "
            SELECT
                p.*,
                u.name AS user,
                w.address_title AS warehouse
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            ORDER BY p.id DESC
            LIMIT ?, ?
        ";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('ii', $offset, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array<int, array<string, mixed>>
     */
    public function searchListAjax(array $filters): array
    {
        $exactOrderNumber = $this->resolveExactOrderNumberFilter($filters);
        $where = $this->buildSearchListAjaxWhereClause($filters);
        $limit = $exactOrderNumber !== null
            ? self::LIST_AJAX_EXACT_ORDER_LIMIT
            : self::LIST_AJAX_DEFAULT_LIMIT;

        $sql = '
            SELECT
                p.id,
                p.order_number,
                p.receipt_number,
                p.payment_date,
                p.payment_amount AS amount,
                p.order_amount,
                p.payment_mode,
                p.payment_stage,
                p.payment_status,
                u.name AS user_name,
                w.address_title AS warehouse
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            WHERE 1=1' . $where['sql'] . '
            ORDER BY p.id DESC
            LIMIT ' . (int)$limit;

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($where['types'] !== '') {
            $stmt->bind_param($where['types'], ...$where['params']);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        if ($rows === []) {
            return [];
        }

        $orderNumbers = [];
        foreach ($rows as $row) {
            $orderNum = trim((string)($row['order_number'] ?? ''));
            if ($orderNum !== '') {
                $orderNumbers[$orderNum] = true;
            }
        }

        $orderNumbers = array_keys($orderNumbers);
        if ($exactOrderNumber !== null) {
            $singleContext = $this->fetchOrderContextForSingleOrder($exactOrderNumber);
            $orderContexts = $singleContext !== []
                ? [$exactOrderNumber => $singleContext]
                : [];
        } else {
            $orderContexts = $this->fetchOrderContextByNumbers($orderNumbers);
        }
        $invoiceIds = $this->fetchInvoiceIdsByOrderNumbers($orderNumbers);
        $paymentMetrics = $this->buildPaymentMetricsByOrderNumbers($orderNumbers, $orderContexts);

        $data = [];
        foreach ($rows as $row) {
            $orderNum = trim((string)($row['order_number'] ?? ''));
            $paymentId = (int)($row['id'] ?? 0);
            $context = $orderContexts[$orderNum] ?? [];
            $metrics = $paymentMetrics[$orderNum][$paymentId] ?? [];

            $row['order_id'] = (int)($context['order_id'] ?? 0);
            $row['order_grand_total'] = (float)($context['order_grand_total'] ?? 0);
            $row['invoice_id'] = (int)($invoiceIds[$orderNum] ?? 0);
            $row['pending_balance'] = (float)($metrics['pending_balance'] ?? 0);
            $row['order_collected_paid'] = (float)($metrics['order_collected_paid'] ?? 0);
            $row['order_cod_pending'] = (float)($metrics['order_cod_pending'] ?? 0);
            $row['order_receipt_total'] = (float)($metrics['order_receipt_total'] ?? 0);

            $data[] = $this->formatListAjaxRow($row);
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{sql: string, params: array<int, mixed>, types: string}
     */
    private function buildSearchListAjaxWhereClause(array $filters): array
    {
        $sql = '';
        $params = [];
        $types = '';

        if (!empty($filters['payment_mode'])) {
            $sql .= ' AND p.payment_mode = ?';
            $params[] = $filters['payment_mode'];
            $types .= 's';
        }

        if (!empty($filters['from_date'])) {
            $sql .= ' AND p.payment_date >= ?';
            $params[] = $filters['from_date'];
            $types .= 's';
        }

        if (!empty($filters['to_date'])) {
            $sql .= ' AND p.payment_date <= ?';
            $params[] = $filters['to_date'];
            $types .= 's';
        }

        $orderPkFilter = (
            isset($filters['order_id'])
            && $filters['order_id'] !== ''
            && ctype_digit((string)$filters['order_id'])
        ) ? (int)$filters['order_id'] : 0;

        if ($orderPkFilter > 0) {
            $filterOrderNum = $this->getOrderNumberByVpOrderId($orderPkFilter);
            if ($filterOrderNum !== '') {
                $sql .= ' AND p.order_number = ?';
                $params[] = $filterOrderNum;
                $types .= 's';
            }
        } elseif (!empty($filters['order_number'])) {
            $exact = !empty($filters['order_exact']);
            if ($exact) {
                $sql .= ' AND p.order_number = ?';
                $params[] = trim((string)$filters['order_number']);
                $types .= 's';
            } else {
                $sql .= ' AND p.order_number LIKE ?';
                $params[] = '%' . $filters['order_number'] . '%';
                $types .= 's';
            }
        }

        if (!empty($filters['amount_min'])) {
            $sql .= ' AND p.payment_amount >= ?';
            $params[] = $filters['amount_min'];
            $types .= 'd';
        }

        if (!empty($filters['amount_max'])) {
            $sql .= ' AND p.payment_amount <= ?';
            $params[] = $filters['amount_max'];
            $types .= 'd';
        }

        return ['sql' => $sql, 'params' => $params, 'types' => $types];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function resolveExactOrderNumberFilter(array $filters): ?string
    {
        if (empty($filters['order_exact']) || empty($filters['order_number'])) {
            return null;
        }
        if (
            isset($filters['order_id'])
            && $filters['order_id'] !== ''
            && ctype_digit((string)$filters['order_id'])
        ) {
            return null;
        }

        $orderNumber = trim((string)$filters['order_number']);

        return $orderNumber !== '' ? $orderNumber : null;
    }

    /**
     * Fast path for payment list filtered to one order (POS receipt / order history links).
     *
     * @return array{order_id: int, order_grand_total: float}
     */
    private function fetchOrderContextForSingleOrder(string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return [];
        }

        $sql = '
            SELECT
                IFNULL((
                    SELECT MIN(o.id)
                    FROM vp_orders o
                    WHERE o.order_number COLLATE ' . self::ORDER_NUMBER_COLLATE . ' = CONVERT(? USING utf8mb4) COLLATE ' . self::ORDER_NUMBER_COLLATE . '
                ), 0) AS order_id,
                COALESCE(
                    NULLIF((
                        SELECT MAX(pp.order_amount)
                        FROM pos_payments pp
                        WHERE pp.order_number COLLATE ' . self::ORDER_NUMBER_COLLATE . ' = CONVERT(? USING utf8mb4) COLLATE ' . self::ORDER_NUMBER_COLLATE . '
                          AND pp.order_amount > 0
                    ), 0),
                    NULLIF((
                        SELECT MAX(oi.total)
                        FROM vp_order_info oi
                        WHERE oi.order_number COLLATE ' . self::ORDER_NUMBER_COLLATE . ' = CONVERT(? USING utf8mb4) COLLATE ' . self::ORDER_NUMBER_COLLATE . '
                    ), 0),
                    0
                ) AS order_grand_total
        ';
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('sss', $orderNumber, $orderNumber, $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!is_array($row)) {
            return [];
        }

        return [
            'order_id' => (int)($row['order_id'] ?? 0),
            'order_grand_total' => round((float)($row['order_grand_total'] ?? 0), 2),
        ];
    }

    /**
     * @param list<string> $orderNumbers
     * @return array<string, array{order_id: int, order_grand_total: float}>
     */
    private function fetchOrderContextByNumbers(array $orderNumbers): array
    {
        if ($orderNumbers === []) {
            return [];
        }
        if (count($orderNumbers) === 1) {
            $single = $this->fetchOrderContextForSingleOrder($orderNumbers[0]);

            return $single !== [] ? [$orderNumbers[0] => $single] : [];
        }

        $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
        $types = str_repeat('s', count($orderNumbers));
        $orderNumberJoin = ' COLLATE ' . self::ORDER_NUMBER_COLLATE . ' = nums.order_number COLLATE ' . self::ORDER_NUMBER_COLLATE;

        $sql = "
            SELECT
                nums.order_number,
                IFNULL(vo.order_id, 0) AS order_id,
                COALESCE(
                    NULLIF(pay.max_order_amount, 0),
                    NULLIF(oi.max_total, 0),
                    0
                ) AS order_grand_total
            FROM (
                SELECT DISTINCT order_number
                FROM pos_payments
                WHERE order_number IN ($placeholders)
            ) nums
            LEFT JOIN (
                SELECT order_number, MIN(id) AS order_id
                FROM vp_orders
                WHERE order_number IN ($placeholders)
                GROUP BY order_number
            ) vo ON vo.order_number{$orderNumberJoin}
            LEFT JOIN (
                SELECT order_number, MAX(total) AS max_total
                FROM vp_order_info
                WHERE order_number IN ($placeholders)
                GROUP BY order_number
            ) oi ON oi.order_number{$orderNumberJoin}
            LEFT JOIN (
                SELECT order_number, MAX(order_amount) AS max_order_amount
                FROM pos_payments
                WHERE order_number IN ($placeholders) AND order_amount > 0
                GROUP BY order_number
            ) pay ON pay.order_number{$orderNumberJoin}
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $bindParams = array_merge($orderNumbers, $orderNumbers, $orderNumbers, $orderNumbers);
        $stmt->bind_param($types . $types . $types . $types, ...$bindParams);
        $stmt->execute();
        $result = $stmt->get_result();

        $contexts = [];
        while ($row = $result->fetch_assoc()) {
            $orderNum = trim((string)($row['order_number'] ?? ''));
            if ($orderNum === '') {
                continue;
            }
            $contexts[$orderNum] = [
                'order_id' => (int)($row['order_id'] ?? 0),
                'order_grand_total' => round((float)($row['order_grand_total'] ?? 0), 2),
            ];
        }
        $stmt->close();

        return $contexts;
    }

    /**
     * @param list<string> $orderNumbers
     * @return array<string, int>
     */
    private function fetchInvoiceIdsByOrderNumbers(array $orderNumbers): array
    {
        if ($orderNumbers === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
        $types = str_repeat('s', count($orderNumbers));

        $sql = "
            SELECT ii.order_number, MAX(i.id) AS invoice_id
            FROM vp_invoice_items ii
            INNER JOIN vp_invoices i ON i.id = ii.invoice_id
            WHERE COALESCE(i.status, '') <> 'cancelled'
              AND ii.order_number IN ($placeholders)
            GROUP BY ii.order_number
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$orderNumbers);
        $stmt->execute();
        $result = $stmt->get_result();

        $invoiceIds = [];
        while ($row = $result->fetch_assoc()) {
            $orderNum = trim((string)($row['order_number'] ?? ''));
            if ($orderNum !== '') {
                $invoiceIds[$orderNum] = (int)($row['invoice_id'] ?? 0);
            }
        }
        $stmt->close();

        return $invoiceIds;
    }

    /**
     * @param list<string> $orderNumbers
     * @param array<string, array{order_id: int, order_grand_total: float}> $orderContexts
     * @return array<string, array<int, array<string, float>>>
     */
    private function buildPaymentMetricsByOrderNumbers(array $orderNumbers, array $orderContexts): array
    {
        if ($orderNumbers === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderNumbers), '?'));
        $types = str_repeat('s', count($orderNumbers));

        $sql = "
            SELECT id, order_number, payment_amount, payment_mode, payment_status, order_amount
            FROM pos_payments
            WHERE order_number IN ($placeholders)
            ORDER BY order_number ASC, id ASC
        ";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param($types, ...$orderNumbers);
        $stmt->execute();
        $result = $stmt->get_result();

        $byOrder = [];
        while ($row = $result->fetch_assoc()) {
            $orderNum = trim((string)($row['order_number'] ?? ''));
            if ($orderNum === '') {
                continue;
            }
            $byOrder[$orderNum][] = $row;
        }
        $stmt->close();

        $metrics = [];
        foreach ($byOrder as $orderNum => $payments) {
            $contextTotal = (float)($orderContexts[$orderNum]['order_grand_total'] ?? 0);
            $metrics[$orderNum] = $this->computePaymentMetricsForOrder($payments, $contextTotal);
        }

        return $metrics;
    }

    /**
     * @param list<array<string, mixed>> $paymentsAsc
     * @return array<int, array<string, float>>
     */
    private function computePaymentMetricsForOrder(array $paymentsAsc, float $orderGrandTotal): array
    {
        $fullCollected = 0.0;
        $fullCodPending = 0.0;
        $fullReceipt = 0.0;

        foreach ($paymentsAsc as $payment) {
            $amount = (float)($payment['payment_amount'] ?? 0);
            $mode = strtolower(trim((string)($payment['payment_mode'] ?? '')));
            $status = strtolower(trim((string)($payment['payment_status'] ?? 'pending')));

            $fullReceipt += $amount;
            if ($mode === 'cod') {
                if ($status === 'pending') {
                    $fullCodPending += $amount;
                }
            } else {
                $fullCollected += $amount;
            }
        }

        $cumCollected = 0.0;
        $cumCodPending = 0.0;
        $byId = [];

        foreach ($paymentsAsc as $payment) {
            $id = (int)($payment['id'] ?? 0);
            $amount = (float)($payment['payment_amount'] ?? 0);
            $storedOrderAmount = round((float)($payment['order_amount'] ?? 0), 2);
            $resolvedOrderAmount = $storedOrderAmount > 0 ? $storedOrderAmount : $orderGrandTotal;

            $mode = strtolower(trim((string)($payment['payment_mode'] ?? '')));
            $status = strtolower(trim((string)($payment['payment_status'] ?? 'pending')));

            if ($mode === 'cod') {
                if ($status === 'pending') {
                    $cumCodPending += $amount;
                }
            } else {
                $cumCollected += $amount;
            }

            $byId[$id] = [
                'pending_balance' => max(0.0, round($resolvedOrderAmount - $cumCollected - $cumCodPending, 2)),
                'order_collected_paid' => round($fullCollected, 2),
                'order_cod_pending' => round($fullCodPending, 2),
                'order_receipt_total' => round($fullReceipt, 2),
            ];
        }

        return $byId;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatListAjaxRow(array $row): array
    {
        $storedOrderAmount = round((float)($row['order_amount'] ?? 0), 2);
        $resolvedOrderAmount = $storedOrderAmount;
        if ($resolvedOrderAmount <= 0) {
            $resolvedOrderAmount = round((float)($row['order_grand_total'] ?? 0), 2);
        }
        if ($resolvedOrderAmount > 0) {
            $row['order_amount'] = $resolvedOrderAmount;
        }

        $collectedPaid = round((float)($row['order_collected_paid'] ?? 0), 2);
        $receiptTotal = round((float)($row['order_receipt_total'] ?? 0), 2);
        $allocationComplete = $resolvedOrderAmount > 0
            && ($receiptTotal + 0.02 >= $resolvedOrderAmount);
        $row['is_settled'] = $allocationComplete;
        $row['can_create_proforma'] = false;
        $row['order_number'] = trim((string)($row['order_number'] ?? ''));
        $row['invoice_id'] = (int)($row['invoice_id'] ?? 0);
        unset($row['order_grand_total'], $row['order_line_subtotal'], $row['balance_snapshot'], $row['order_collected_paid'], $row['order_cod_pending'], $row['order_receipt_total']);

        return $row;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByIdWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                u.name AS user_name,
                w.address_title AS warehouse
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            WHERE p.id = ?
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $payment ?: null;
    }

    public function deleteById(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('DELETE FROM pos_payments WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $deleted = $ok && $stmt->affected_rows > 0;
        $stmt->close();

        return $deleted;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForReceipt(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.*,
                u.name AS user_name,
                w.address_title AS warehouse,
                w.address AS warehouse_address,
                c.name AS customer_name
            FROM pos_payments p
            LEFT JOIN vp_users u ON u.id = p.user_id
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            LEFT JOIN vp_customers c ON c.id = p.customer_id
            WHERE p.id = ?
        ");
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $payment ?: null;
    }

    /**
     * Warehouse where the POS order was created (first payment row).
     */
    public function getSaleWarehouseIdForOrder(string $orderNumber): int
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return 0;
        }

        $stmt = $this->db->prepare(
            'SELECT warehouse_id FROM pos_payments
             WHERE order_number = ? AND warehouse_id > 0
             ORDER BY id ASC
             LIMIT 1'
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int)($row['warehouse_id'] ?? 0);
    }

    /**
     * @return array{title: string, lines: array<int, string>}
     */
    public function getWarehouseAddressById(int $warehouseId): array
    {
        $empty = ['title' => '', 'lines' => []];
        if ($warehouseId <= 0) {
            return $empty;
        }

        $stmt = $this->db->prepare(
            'SELECT address_title, display_name, address FROM exotic_address WHERE id = ? AND is_active = 1 LIMIT 1'
        );
        if (!$stmt) {
            return $empty;
        }
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $this->formatWarehouseAddressRow(is_array($row) ? $row : null);
    }

    /**
     * Sale store address from the order's checkout payment warehouse.
     *
     * @return array{title: string, lines: array<int, string>}
     */
    public function getSaleStoreAddressForOrder(string $orderNumber): array
    {
        $warehouseId = $this->getSaleWarehouseIdForOrder($orderNumber);
        if ($warehouseId > 0) {
            $address = $this->getWarehouseAddressById($warehouseId);
            if ($address['title'] !== '' || $address['lines'] !== []) {
                return $address;
            }
        }

        return $this->getDefaultWarehouseAddress();
    }

    /**
     * @return array{title: string, lines: array<int, string>}
     */
    public function getDefaultWarehouseAddress(): array
    {
        $dwRes = $this->db->query(
            'SELECT address_title, display_name, address FROM exotic_address WHERE is_active = 1 ORDER BY is_default DESC, order_no ASC, id ASC LIMIT 1'
        );
        if (!$dwRes || !($dw = $dwRes->fetch_assoc())) {
            return ['title' => '', 'lines' => []];
        }

        return $this->formatWarehouseAddressRow($dw);
    }

    /**
     * @param array<string, mixed>|null $row
     *
     * @return array{title: string, lines: array<int, string>}
     */
    private function formatWarehouseAddressRow(?array $row): array
    {
        $result = ['title' => '', 'lines' => []];
        if (!is_array($row)) {
            return $result;
        }

        $result['title'] = trim((string)($row['address_title'] ?? ''));
        $addrText = trim((string)($row['address'] ?? ''));
        if ($addrText === '') {
            $addrText = trim((string)($row['display_name'] ?? ''));
        }
        $parts = preg_split('/\r\n|\r|\n/', $addrText);
        $lines = [];
        foreach (is_array($parts) ? $parts : [] as $ln) {
            $ln = trim((string)$ln);
            if ($ln !== '') {
                $lines[] = $ln;
            }
        }
        $result['lines'] = $lines;

        return $result;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVpOrderById(int $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT order_number, customer_id FROM vp_orders WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVpOrderRowById(int $orderId): ?array
    {
        $stmt = $this->db->prepare('SELECT id, order_number, customer_id FROM vp_orders WHERE id = ? ORDER BY id ASC LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findVpOrderRowByNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare('SELECT id, order_number, customer_id FROM vp_orders WHERE order_number = ? ORDER BY id ASC LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPaymentAnchorByOrderKey(string $orderKey): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT order_number, customer_id FROM pos_payments WHERE order_number = ? ORDER BY id ASC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderKey);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function getOrderNumberByVpOrderId(int $orderId): string
    {
        $stmt = $this->db->prepare('SELECT order_number FROM vp_orders WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string)($row['order_number'] ?? ''));
    }

    public function sumPaidByOrderNumber(string $orderNumber): float
    {
        $stmt = $this->db->prepare(
            'SELECT IFNULL(SUM(payment_amount), 0) AS paid FROM pos_payments WHERE order_number = ? AND LOWER(TRIM(payment_mode)) <> \'cod\''
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return round((float)($row['paid'] ?? 0), 2);
    }

    public function getOrderNumberByPaymentId(int $paymentId): string
    {
        $stmt = $this->db->prepare('SELECT order_number FROM pos_payments WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $paymentId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string)($row['order_number'] ?? ''));
    }

    public function updatePayment(
        int $id,
        float $amount,
        string $mode,
        string $stage,
        string $transaction,
        string $note,
        string $date,
        int $editorUserId
    ): bool {
        $stmt = $this->db->prepare('
            UPDATE pos_payments
            SET payment_amount = ?, payment_mode = ?, payment_stage = ?, transaction_id = ?, note = ?, payment_date = ?, user_id = ?
            WHERE id = ?
        ');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param(
            'dsssssii',
            $amount,
            $mode,
            $stage,
            $transaction,
            $note,
            $date,
            $editorUserId,
            $id
        );
        $ok = $stmt->execute();
        $stmt->close();

        return (bool)$ok;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findSingleWithOrderId(int $id): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT p.*,
                (
                    SELECT MIN(o.id) FROM vp_orders o
                    WHERE o.order_number COLLATE utf8mb4_unicode_ci = p.order_number COLLATE utf8mb4_unicode_ci
                ) AS order_id
             FROM pos_payments p WHERE p.id = ?'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $payment ?: null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pos_payments WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function getLatestPaymentStage(string $orderNumber): string
    {
        $stmt = $this->db->prepare(
            'SELECT payment_stage FROM pos_payments WHERE order_number = ? ORDER BY id DESC LIMIT 1'
        );
        if (!$stmt) {
            return 'final';
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string)($row['payment_stage'] ?? 'final')) ?: 'final';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestByOrderNumber(string $orderNumber): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM pos_payments WHERE order_number = ? ORDER BY id DESC LIMIT 1'
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    public function getWarehouseIdForOrder(string $orderNumber): int
    {
        return $this->getSaleWarehouseIdForOrder($orderNumber);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByOrderNumber(string $orderNumber): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return [];
        }

        $stmt = $this->db->prepare("
            SELECT
                p.id,
                p.receipt_number,
                p.payment_date,
                p.payment_amount,
                p.payment_mode,
                p.payment_stage,
                p.transaction_id,
                p.note,
                p.order_amount,
                p.pending_amount,
                p.currency,
                w.address_title AS warehouse
            FROM pos_payments p
            LEFT JOIN exotic_address w ON w.id = p.warehouse_id
            WHERE p.order_number = ?
            ORDER BY p.payment_date ASC, p.id ASC
        ");
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Payment rows for one POS checkout receipt (same receipt_number group).
     *
     * @return list<array<string, mixed>>
     */
    public function findCheckoutReceiptPayments(string $orderNumber = '', ?string $receiptNumber = null): array
    {
        $receiptNumber = trim((string)$receiptNumber);
        $orderNumber = trim($orderNumber);

        if ($receiptNumber !== '') {
            if ($orderNumber !== '') {
                $stmt = $this->db->prepare(
                    'SELECT * FROM pos_payments
                     WHERE receipt_number = ? AND order_number = ?
                     ORDER BY id ASC'
                );
                if (!$stmt) {
                    return [];
                }
                $stmt->bind_param('ss', $receiptNumber, $orderNumber);
            } else {
                $stmt = $this->db->prepare(
                    'SELECT * FROM pos_payments WHERE receipt_number = ? ORDER BY id ASC'
                );
                if (!$stmt) {
                    return [];
                }
                $stmt->bind_param('s', $receiptNumber);
            }
        } elseif ($orderNumber !== '') {
            $stmt = $this->db->prepare(
                'SELECT p.*
                 FROM pos_payments p
                 INNER JOIN (
                     SELECT receipt_number
                     FROM pos_payments
                     WHERE order_number = ?
                     ORDER BY id DESC
                     LIMIT 1
                 ) latest ON latest.receipt_number = p.receipt_number
                 ORDER BY p.id ASC'
            );
            if (!$stmt) {
                return [];
            }
            $stmt->bind_param('s', $orderNumber);
        } else {
            return [];
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return $rows;
    }
}
