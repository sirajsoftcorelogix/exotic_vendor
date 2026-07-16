<?php

class Payment
{
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
        $sql = "
SELECT
    p.id,
    p.order_number,
    p.receipt_number,
    p.payment_date,
    p.payment_amount AS amount,
    p.order_amount,
    p.pending_amount AS balance_snapshot,
    p.payment_mode,
    p.payment_stage,
    u.name AS user_name,
    w.address_title AS warehouse,
    vo.order_id AS order_id,
    vo.order_grand_total,
    inv_map.invoice_id,

    ROUND(
        IFNULL(
            NULLIF(vo.order_grand_total, 0),
            IFNULL(NULLIF(p.order_amount, 0), IFNULL(vo.order_line_subtotal, 0))
        )
        -
        IFNULL(
            (
                SELECT SUM(p2.payment_amount)
                FROM pos_payments p2
                WHERE p2.order_number COLLATE utf8mb4_unicode_ci
                      = p.order_number COLLATE utf8mb4_unicode_ci
                AND p2.id <= p.id
            ), 0
        ),
        2
    ) AS pending_balance

FROM pos_payments p

LEFT JOIN vp_users u
    ON u.id = p.user_id

LEFT JOIN exotic_address w
    ON w.id = p.warehouse_id

LEFT JOIN (
    SELECT
        agg.order_number,
        agg.order_id,
        agg.order_line_subtotal,
        COALESCE(
            NULLIF(agg.order_info_total, 0),
            GREATEST(
                agg.order_line_subtotal
                - agg.custom_reduce
                - agg.coupon_reduce
                - agg.gift_reduce
                - agg.credit,
                0
            )
        ) AS order_grand_total
    FROM (
        SELECT
            o.order_number,
            MIN(o.id) AS order_id,
            SUM(o.finalprice * o.quantity) AS order_line_subtotal,
            MAX(CASE WHEN oi.total > 0 THEN oi.total ELSE NULL END) AS order_info_total,
            IFNULL(MAX(o.custom_reduce), 0) AS custom_reduce,
            IFNULL(MAX(oi.coupon_reduce), 0) AS coupon_reduce,
            IFNULL(MAX(oi.giftvoucher_reduce), 0) AS gift_reduce,
            IFNULL(MAX(oi.credit), 0) AS credit
        FROM vp_orders o
        LEFT JOIN vp_order_info oi
            ON oi.order_number COLLATE utf8mb4_unicode_ci = o.order_number COLLATE utf8mb4_unicode_ci
        GROUP BY o.order_number
    ) agg
) vo ON vo.order_number COLLATE utf8mb4_unicode_ci = p.order_number COLLATE utf8mb4_unicode_ci

LEFT JOIN (
    SELECT
        ii.order_number,
        MAX(i.id) AS invoice_id
    FROM vp_invoice_items ii
    INNER JOIN vp_invoices i ON i.id = ii.invoice_id
    WHERE LOWER(TRIM(COALESCE(i.status, ''))) <> 'cancelled'
    GROUP BY ii.order_number
) inv_map ON inv_map.order_number COLLATE utf8mb4_unicode_ci = p.order_number COLLATE utf8mb4_unicode_ci

WHERE 1=1
";
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

        $sql .= ' ORDER BY p.id DESC';

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $this->formatListAjaxRow($row);
        }
        $stmt->close();

        return $data;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function formatListAjaxRow(array $row): array
    {
        $resolvedOrderAmount = round((float)($row['order_grand_total'] ?? 0), 2);
        if ($resolvedOrderAmount <= 0) {
            $resolvedOrderAmount = round((float)($row['order_amount'] ?? 0), 2);
        }
        if ($resolvedOrderAmount > 0) {
            $row['order_amount'] = $resolvedOrderAmount;
        }
        $row['invoice_id'] = (int)($row['invoice_id'] ?? 0);
        $row['is_settled'] = round((float)($row['pending_balance'] ?? 0), 2) <= 0.02;
        unset($row['order_grand_total'], $row['order_line_subtotal']);

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
        $stmt = $this->db->prepare('DELETE FROM pos_payments WHERE id = ?');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();

        return (bool)$ok;
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
     * @return array{title: string, lines: array<int, string>}
     */
    public function getDefaultWarehouseAddress(): array
    {
        $defaultWarehouseAddress = ['title' => '', 'lines' => []];
        $dwRes = $this->db->query(
            'SELECT address_title, display_name, address FROM exotic_address WHERE is_active = 1 ORDER BY is_default DESC, order_no ASC, id ASC LIMIT 1'
        );
        if (!$dwRes || !($dw = $dwRes->fetch_assoc())) {
            return $defaultWarehouseAddress;
        }

        $defaultWarehouseAddress['title'] = trim((string)($dw['address_title'] ?? ''));
        $addrText = trim((string)($dw['address'] ?? ''));
        if ($addrText === '') {
            $addrText = trim((string)($dw['display_name'] ?? ''));
        }
        $parts = preg_split('/\r\n|\r|\n/', $addrText);
        $lines = [];
        foreach (is_array($parts) ? $parts : [] as $ln) {
            $ln = trim((string)$ln);
            if ($ln !== '') {
                $lines[] = $ln;
            }
        }
        $defaultWarehouseAddress['lines'] = $lines;

        return $defaultWarehouseAddress;
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
            'SELECT IFNULL(SUM(payment_amount), 0) AS paid FROM pos_payments WHERE order_number = ?'
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
}
