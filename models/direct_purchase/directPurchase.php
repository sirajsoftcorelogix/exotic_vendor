<?php

class DirectPurchase
{
    /** @var mysqli */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, total: int}
     */
    public function searchPurchases(array $filters, int $page, int $limit): array
    {
        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $where = ['1=1'];
        $types = '';
        $params = [];

        if (!empty($filters['search_text'])) {
            $where[] = '(p.invoice_number LIKE ? OR v.vendor_name LIKE ? OR v.contact_name LIKE ? OR i.item_code LIKE ? OR i.sku LIKE ?)';
            $like = '%' . $filters['search_text'] . '%';
            $types .= 'sssss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['invoice_date_from'])) {
            $where[] = 'p.invoice_date >= ?';
            $types .= 's';
            $params[] = $filters['invoice_date_from'];
        }
        if (!empty($filters['invoice_date_to'])) {
            $where[] = 'p.invoice_date <= ?';
            $types .= 's';
            $params[] = $filters['invoice_date_to'];
        }
        if (!empty($filters['vendor_id'])) {
            $where[] = 'p.vendor_id = ?';
            $types .= 'i';
            $params[] = (int) $filters['vendor_id'];
        }

        $joinItems = '';
        if (!empty($filters['search_text'])) {
            $joinItems = 'LEFT JOIN vp_direct_purchase_items i ON i.direct_purchase_id = p.id';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(DISTINCT p.id) AS c FROM vp_direct_purchases p
            JOIN vp_vendors v ON v.id = p.vendor_id
            $joinItems
            WHERE $whereSql";

        $stmt = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        $listSql = "SELECT DISTINCT p.*, v.vendor_name, v.contact_name
            FROM vp_direct_purchases p
            JOIN vp_vendors v ON v.id = p.vendor_id
            $joinItems
            WHERE $whereSql
            ORDER BY p.invoice_date DESC, p.id DESC
            LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($listSql);
        if ($types !== '') {
            $typesList = $types . 'ii';
            $paramsList = array_merge($params, [$limit, $offset]);
            $stmt->bind_param($typesList, ...$paramsList);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();

        return ['rows' => $rows, 'total' => $total];
    }

    public function getById(int $id): ?array
    {
        $sql = 'SELECT p.*, v.vendor_name, v.contact_name
            FROM vp_direct_purchases p
            JOIN vp_vendors v ON v.id = p.vendor_id
            WHERE p.id = ?';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $purchaseId): array
    {
        $sql = 'SELECT * FROM vp_direct_purchase_items WHERE direct_purchase_id = ? ORDER BY sort_order ASC, id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $purchaseId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($row = $res->fetch_assoc()) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->conn->prepare('DELETE FROM vp_direct_purchases WHERE id = ?');
        $stmt->bind_param('i', $id);
        $ok = $stmt->execute();
        $stmt->close();
        return $ok;
    }

    /**
     * @param array<string, mixed> $header
     * @param array<int, array<string, mixed>> $items
     */
    public function insertPurchase(array $header, array $items): int
    {
        $this->conn->begin_transaction();
        try {
            $sql = 'INSERT INTO vp_direct_purchases (
                vendor_id, invoice_number, invoice_date, invoice_file, currency,
                subtotal, discount, igst_total, sgst_total, cgst_total, round_off, grand_total,
                created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'issssdddddddi',
                $header['vendor_id'],
                $header['invoice_number'],
                $header['invoice_date'],
                $header['invoice_file'],
                $header['currency'],
                $header['subtotal'],
                $header['discount'],
                $header['igst_total'],
                $header['sgst_total'],
                $header['cgst_total'],
                $header['round_off'],
                $header['grand_total'],
                $header['created_by']
            );
            $stmt->execute();
            $pid = (int) $this->conn->insert_id;
            $stmt->close();

            $this->insertItemRows($pid, $items);
            $this->conn->commit();
            return $pid;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $header
     * @param array<int, array<string, mixed>> $items
     */
    public function updatePurchase(int $id, array $header, array $items): void
    {
        $this->conn->begin_transaction();
        try {
            $sql = 'UPDATE vp_direct_purchases SET
                vendor_id = ?, invoice_number = ?, invoice_date = ?, invoice_file = ?, currency = ?,
                subtotal = ?, discount = ?, igst_total = ?, sgst_total = ?, cgst_total = ?, round_off = ?, grand_total = ?
                WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param(
                'issssdddddddi',
                $header['vendor_id'],
                $header['invoice_number'],
                $header['invoice_date'],
                $header['invoice_file'],
                $header['currency'],
                $header['subtotal'],
                $header['discount'],
                $header['igst_total'],
                $header['sgst_total'],
                $header['cgst_total'],
                $header['round_off'],
                $header['grand_total'],
                $id
            );
            $stmt->execute();
            $stmt->close();

            $del = $this->conn->prepare('DELETE FROM vp_direct_purchase_items WHERE direct_purchase_id = ?');
            $del->bind_param('i', $id);
            $del->execute();
            $del->close();

            $this->insertItemRows($id, $items);
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function insertItemRows(int $purchaseId, array $items): void
    {
        $sql = 'INSERT INTO vp_direct_purchase_items (
            direct_purchase_id, item_code, sku, color, size, cost_per_item, qty, hsn, gst_rate, unit, gst_amount, line_total, sort_order
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $this->conn->prepare($sql);
        $order = 0;
        foreach ($items as $row) {
            $itemCode = (string) ($row['item_code'] ?? '');
            $sku = (string) ($row['sku'] ?? '');
            $color = (string) ($row['color'] ?? '');
            $size = (string) ($row['size'] ?? '');
            $costPer = (float) ($row['cost_per_item'] ?? 0);
            $qty = (float) ($row['qty'] ?? 0);
            $hsn = (string) ($row['hsn'] ?? '');
            $gstRate = (float) ($row['gst_rate'] ?? 0);
            $unit = (string) ($row['unit'] ?? '');
            $gstAmt = (float) ($row['gst_amount'] ?? 0);
            $lineTot = (float) ($row['line_total'] ?? 0);
            $stmt->bind_param(
                'isssddsdsddi',
                $purchaseId,
                $itemCode,
                $sku,
                $color,
                $size,
                $costPer,
                $qty,
                $hsn,
                $gstRate,
                $unit,
                $gstAmt,
                $lineTot,
                $order
            );
            $stmt->execute();
            $order++;
        }
        $stmt->close();
    }
}
