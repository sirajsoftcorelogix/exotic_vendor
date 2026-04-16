<?php

require_once __DIR__ . '/DirectPurchaseStock.php';

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

    public function countReturns(int $purchaseId): int
    {
        $stmt = $this->conn->prepare('SELECT COUNT(*) AS c FROM vp_direct_purchase_returns WHERE direct_purchase_id = ?');
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $purchaseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (int) ($row['c'] ?? 0);
    }

    /**
     * @return array<int, int>
     */
    public function getReturnIdsForPurchase(int $purchaseId): array
    {
        $stmt = $this->conn->prepare('SELECT id FROM vp_direct_purchase_returns WHERE direct_purchase_id = ? ORDER BY id ASC');
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $purchaseId);
        $stmt->execute();
        $res = $stmt->get_result();
        $ids = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $ids[] = (int) $row['id'];
            }
        }
        $stmt->close();

        return $ids;
    }

    /**
     * Purchase lines with how much has already been returned (any return doc).
     *
     * @return array<int, array<string, mixed>>
     */
    public function getItemsWithReturnable(int $purchaseId): array
    {
        $items = $this->getItems($purchaseId);
        $stmt = $this->conn->prepare(
            'SELECT ri.direct_purchase_item_id, SUM(ri.return_qty) AS sq
            FROM vp_direct_purchase_return_items ri
            INNER JOIN vp_direct_purchase_returns r ON r.id = ri.direct_purchase_return_id
            WHERE r.direct_purchase_id = ?
            GROUP BY ri.direct_purchase_item_id'
        );
        $sums = [];
        if ($stmt) {
            $stmt->bind_param('i', $purchaseId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    $sums[(int) $row['direct_purchase_item_id']] = (float) ($row['sq'] ?? 0);
                }
            }
            $stmt->close();
        }
        foreach ($items as &$it) {
            $iid = (int) ($it['id'] ?? 0);
            $already = (float) ($sums[$iid] ?? 0);
            $it['already_returned_qty'] = $already;
            $it['returnable_qty'] = max(0.0, (float) ($it['qty'] ?? 0) - $already);
        }
        unset($it);

        return $items;
    }

    public function delete(int $id): bool
    {
        $this->conn->begin_transaction();
        try {
            foreach ($this->getReturnIdsForPurchase($id) as $rid) {
                DirectPurchaseStock::reverseMovementsForRef($this->conn, DirectPurchaseStock::REF_RETURN, $rid);
            }
            DirectPurchaseStock::reverseMovementsForRef($this->conn, DirectPurchaseStock::REF_PURCHASE, $id);
            $stmt = $this->conn->prepare('DELETE FROM vp_direct_purchases WHERE id = ?');
            $stmt->bind_param('i', $id);
            $ok = $stmt->execute();
            $stmt->close();
            $this->conn->commit();

            return $ok;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
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
                vendor_id, warehouse_id, invoice_number, invoice_date, invoice_file, currency,
                subtotal, discount, igst_total, sgst_total, cgst_total, round_off, grand_total,
                created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->conn->prepare($sql);
            $bindTypes = 'ii' . 'ssss' . str_repeat('d', 7) . 'i';
            $stmt->bind_param(
                $bindTypes,
                $header['vendor_id'],
                $header['warehouse_id'],
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
            $stockLines = DirectPurchaseStock::buildStockLinesFromPostedItems($this->conn, $items);
            DirectPurchaseStock::applyPurchaseIn($this->conn, $pid, (int) $header['warehouse_id'], $stockLines);
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
            DirectPurchaseStock::reverseMovementsForRef($this->conn, DirectPurchaseStock::REF_PURCHASE, $id);

            $sql = 'UPDATE vp_direct_purchases SET
                vendor_id = ?, warehouse_id = ?, invoice_number = ?, invoice_date = ?, invoice_file = ?, currency = ?,
                subtotal = ?, discount = ?, igst_total = ?, sgst_total = ?, cgst_total = ?, round_off = ?, grand_total = ?
                WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $bindTypes = 'ii' . 'ssss' . str_repeat('d', 7) . 'i';
            $stmt->bind_param(
                $bindTypes,
                $header['vendor_id'],
                $header['warehouse_id'],
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
            $fresh = $this->getItems($id);
            $stockLines = DirectPurchaseStock::buildStockLinesFromDbItems($this->conn, $fresh);
            DirectPurchaseStock::applyPurchaseIn($this->conn, $id, (int) $header['warehouse_id'], $stockLines);
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
                'issssddsdsddi',
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
