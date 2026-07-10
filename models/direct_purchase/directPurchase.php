<?php

require_once __DIR__ . '/DirectPurchaseStock.php';
require_once __DIR__ . '/DirectPurchaseSchema.php';
require_once __DIR__ . '/../../helpers/direct_purchase_supplier.php';

class DirectPurchase
{
    /** @var mysqli */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    private function ensureSchema(): void
    {
        DirectPurchaseSchema::ensureAll($this->conn);
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
            $where[] = '(p.invoice_number LIKE ? OR v.vendor_name LIKE ? OR v.contact_name LIKE ? OR pub.publishers LIKE ? OR i.item_code LIKE ? OR i.sku LIKE ?)';
            $like = '%' . $filters['search_text'] . '%';
            $types .= 'ssssss';
            $params[] = $like;
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
        if (!empty($filters['added_date_from'])) {
            $where[] = 'DATE(p.created_at) >= ?';
            $types .= 's';
            $params[] = $filters['added_date_from'];
        }
        if (!empty($filters['added_date_to'])) {
            $where[] = 'DATE(p.created_at) <= ?';
            $types .= 's';
            $params[] = $filters['added_date_to'];
        }
        if (!empty($filters['vendor_id'])) {
            $where[] = 'p.vendor_id = ?';
            $types .= 'i';
            $params[] = (int) $filters['vendor_id'];
        }
        if (!empty($filters['created_by'])) {
            $where[] = 'p.created_by = ?';
            $types .= 'i';
            $params[] = (int) $filters['created_by'];
        }

        $joinItems = '';
        if (!empty($filters['search_text'])) {
            $joinItems = 'LEFT JOIN vp_direct_purchase_items i ON i.direct_purchase_id = p.id';
        }

        $whereSql = implode(' AND ', $where);

        $supplierJoin = dp_supplier_join('p');

        $countSql = "SELECT COUNT(DISTINCT p.id) AS c FROM vp_direct_purchases p
            {$supplierJoin}
            $joinItems
            WHERE $whereSql";

        $stmt = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        $supplierSelect = dp_supplier_select('p');
        $listSql = "SELECT DISTINCT p.*, {$supplierSelect},
                cu.name AS created_by_name,
                (SELECT COUNT(*) FROM vp_direct_purchase_returns dr WHERE dr.direct_purchase_id = p.id) AS return_count
            FROM vp_direct_purchases p
            {$supplierJoin}
            LEFT JOIN vp_users cu ON cu.id = p.created_by AND cu.is_deleted = 0
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
        $supplierJoin = dp_supplier_join('p');
        $supplierSelect = dp_supplier_select('p');
        $sql = "SELECT p.*, {$supplierSelect}, pu.name AS purchase_created_by_name
            FROM vp_direct_purchases p
            {$supplierJoin}
            LEFT JOIN vp_users pu ON pu.id = p.created_by AND pu.is_deleted = 0
            WHERE p.id = ?";
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

    public function getItemById(int $itemId): ?array
    {
        $this->ensureSchema();
        $stmt = $this->conn->prepare('SELECT * FROM vp_direct_purchase_items WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    public function markItemVendorQtySynced(int $itemId): bool
    {
        $this->ensureSchema();
        $stmt = $this->conn->prepare(
            'UPDATE vp_direct_purchase_items
             SET vendor_qty_synced = 1, vendor_qty_synced_qty = qty
             WHERE id = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $itemId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function markItemsVendorQtySyncedByVariant(
        int $purchaseId,
        string $itemCode,
        string $size,
        string $color
    ): bool {
        $this->ensureSchema();
        $stmt = $this->conn->prepare(
            'UPDATE vp_direct_purchase_items
             SET vendor_qty_synced = 1, vendor_qty_synced_qty = qty
             WHERE direct_purchase_id = ?
               AND item_code = ?
               AND COALESCE(TRIM(size), \'\') = ?
               AND COALESCE(TRIM(color), \'\') = ?'
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('isss', $purchaseId, $itemCode, $size, $color);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }

    public function countReturns(int $purchaseId): int
    {
        $this->ensureSchema();
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
        $this->ensureSchema();
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
        $this->ensureSchema();
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
        $this->ensureSchema();
        if ($this->countReturns($id) > 0) {
            throw new \RuntimeException('Cannot delete purchase with linked purchase returns.');
        }
        $this->conn->begin_transaction();
        try {
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
        $this->ensureSchema();
        $this->conn->begin_transaction();
        try {
            $sql = 'INSERT INTO vp_direct_purchases (
                vendor_id, vendor_type, warehouse_id, invoice_number, invoice_date, invoice_file, currency,
                subtotal, discount, igst_total, sgst_total, cgst_total, round_off, grand_total,
                created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->conn->prepare($sql);
            $bindTypes = 'i' . 's' . 'i' . 'ssss' . str_repeat('d', 7) . 'i';
            $stmt->bind_param(
                $bindTypes,
                $header['vendor_id'],
                $header['vendor_type'],
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
            DirectPurchaseStock::applyPurchaseIn(
                $this->conn,
                $pid,
                (int) $header['warehouse_id'],
                $stockLines,
                (int) ($header['created_by'] ?? 0)
            );
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
        $this->ensureSchema();
        require_once __DIR__ . '/directPurchaseReturn.php';
        $returnModel = new directPurchaseReturn($this->conn);
        $returnedQtyByItem = $returnModel->sumReturnedQtyByItem($id, 0);

        $existingById = [];
        foreach ($this->getItems($id) as $existingRow) {
            $existingById[(int) ($existingRow['id'] ?? 0)] = $existingRow;
        }

        $postedExistingIds = [];
        foreach ($items as $row) {
            $itemId = (int) ($row['id'] ?? 0);
            if ($itemId > 0) {
                $postedExistingIds[$itemId] = true;
                if (!isset($existingById[$itemId])) {
                    throw new \RuntimeException('Invalid purchase line in save request.');
                }
            }
        }

        foreach ($returnedQtyByItem as $itemId => $returnedQty) {
            if ($returnedQty > 0 && empty($postedExistingIds[$itemId])) {
                throw new \RuntimeException('Cannot remove a line item that has linked purchase returns.');
            }
        }

        $this->conn->begin_transaction();
        try {
            DirectPurchaseStock::reverseMovementsForRef($this->conn, DirectPurchaseStock::REF_PURCHASE, $id);

            $sql = 'UPDATE vp_direct_purchases SET
                vendor_id = ?, vendor_type = ?, warehouse_id = ?, invoice_number = ?, invoice_date = ?, invoice_file = ?, currency = ?,
                subtotal = ?, discount = ?, igst_total = ?, sgst_total = ?, cgst_total = ?, round_off = ?, grand_total = ?
                WHERE id = ?';
            $stmt = $this->conn->prepare($sql);
            $bindTypes = 'i' . 's' . 'i' . 'ssss' . str_repeat('d', 7) . 'i';
            $stmt->bind_param(
                $bindTypes,
                $header['vendor_id'],
                $header['vendor_type'],
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

            $sortOrder = 0;
            foreach ($items as $row) {
                $itemId = (int) ($row['id'] ?? 0);
                if ($itemId > 0 && isset($existingById[$itemId])) {
                    $this->updateItemRow($itemId, $row, $sortOrder);
                } else {
                    $this->insertItemRow($id, $row, $sortOrder);
                }
                $sortOrder++;
            }

            foreach ($existingById as $itemId => $existingRow) {
                if (!empty($postedExistingIds[$itemId])) {
                    continue;
                }
                if (($returnedQtyByItem[$itemId] ?? 0) > 0) {
                    throw new \RuntimeException('Cannot remove a line item that has linked purchase returns.');
                }
                $this->deleteItemRow($itemId);
            }

            $fresh = $this->getItems($id);
            $stockLines = DirectPurchaseStock::buildStockLinesFromDbItems($this->conn, $fresh);
            DirectPurchaseStock::applyPurchaseIn(
                $this->conn,
                $id,
                (int) $header['warehouse_id'],
                $stockLines,
                (int) ($header['created_by'] ?? 0)
            );
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    private function updateItemRow(int $itemId, array $row, int $sortOrder): void
    {
        $sql = 'UPDATE vp_direct_purchase_items SET
            item_code = ?, sku = ?, color = ?, size = ?, cost_per_item = ?, qty = ?, hsn = ?, gst_rate = ?, unit = ?,
            gst_amount = ?, line_total = ?, sort_order = ?
            WHERE id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Failed to prepare purchase line update.');
        }

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
            'ssssddsdsddii',
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
            $sortOrder,
            $itemId
        );
        $stmt->execute();
        $stmt->close();
    }

    private function insertItemRow(int $purchaseId, array $row, int $sortOrder): void
    {
        $sql = 'INSERT INTO vp_direct_purchase_items (
            direct_purchase_id, item_code, sku, color, size, cost_per_item, qty, hsn, gst_rate, unit, gst_amount, line_total, sort_order, vendor_qty_synced, vendor_qty_synced_qty
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,NULL)';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new \RuntimeException('Failed to prepare purchase line insert.');
        }

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
            $sortOrder
        );
        $stmt->execute();
        $stmt->close();
    }

    private function deleteItemRow(int $itemId): void
    {
        $stmt = $this->conn->prepare('DELETE FROM vp_direct_purchase_items WHERE id = ?');
        if (!$stmt) {
            throw new \RuntimeException('Failed to prepare purchase line delete.');
        }
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    private function insertItemRows(int $purchaseId, array $items): void
    {
        $sql = 'INSERT INTO vp_direct_purchase_items (
            direct_purchase_id, item_code, sku, color, size, cost_per_item, qty, hsn, gst_rate, unit, gst_amount, line_total, sort_order, vendor_qty_synced, vendor_qty_synced_qty
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,0,NULL)';
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
