<?php

require_once __DIR__ . '/DirectPurchaseStock.php';

class DirectPurchaseReturn
{
    /** @var mysqli */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function countForPurchase(int $purchaseId): int
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
    public function getIdsForPurchase(int $purchaseId): array
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

    public function getById(int $id): ?array
    {
        $sql = 'SELECT r.*, p.invoice_number, p.vendor_id
            FROM vp_direct_purchase_returns r
            JOIN vp_direct_purchases p ON p.id = r.direct_purchase_id
            WHERE r.id = ?';
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
    public function getItems(int $returnId): array
    {
        $sql = 'SELECT ri.*, i.sku, i.item_code, i.color, i.size, i.qty AS purchased_qty, i.cost_per_item, i.gst_rate, i.line_total AS purchase_line_total, i.gst_amount AS purchase_gst_amount
            FROM vp_direct_purchase_return_items ri
            JOIN vp_direct_purchase_items i ON i.id = ri.direct_purchase_item_id
            WHERE ri.direct_purchase_return_id = ?
            ORDER BY ri.sort_order ASC, ri.id ASC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $returnId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForPurchase(int $purchaseId): array
    {
        $sql = 'SELECT * FROM vp_direct_purchase_returns WHERE direct_purchase_id = ? ORDER BY return_date DESC, id DESC';
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param('i', $purchaseId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }

    /**
     * Sum of return_qty per direct_purchase_item_id for a purchase (optionally excluding one return document).
     *
     * @return array<int, float> item_id => qty
     */
    public function sumReturnedQtyByItem(int $purchaseId, int $excludeReturnId = 0): array
    {
        $sql = 'SELECT ri.direct_purchase_item_id, SUM(ri.return_qty) AS sq
            FROM vp_direct_purchase_return_items ri
            INNER JOIN vp_direct_purchase_returns r ON r.id = ri.direct_purchase_return_id
            WHERE r.direct_purchase_id = ?';
        if ($excludeReturnId > 0) {
            $sql .= ' AND r.id <> ?';
        }
        $sql .= ' GROUP BY ri.direct_purchase_item_id';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($excludeReturnId > 0) {
            $stmt->bind_param('ii', $purchaseId, $excludeReturnId);
        } else {
            $stmt->bind_param('i', $purchaseId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $map = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[(int) $row['direct_purchase_item_id']] = (float) ($row['sq'] ?? 0);
            }
        }
        $stmt->close();

        return $map;
    }

    /**
     * @param array<string, mixed> $header keys: direct_purchase_id, warehouse_id, return_date, remarks, currency, subtotal, discount, igst_total, sgst_total, cgst_total, round_off, grand_total, created_by
     * @param array<int, array<string, mixed>> $lines keys: direct_purchase_item_id, return_qty, gst_amount, line_total, sort_order
     */
    public function insertReturn(array $header, array $lines): int
    {
        $this->conn->begin_transaction();
        try {
            $sql = 'INSERT INTO vp_direct_purchase_returns (
                direct_purchase_id, warehouse_id, return_date, remarks, currency,
                subtotal, discount, igst_total, sgst_total, cgst_total, round_off, grand_total, created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->conn->prepare($sql);
            $bt = 'ii' . 'sss' . str_repeat('d', 7) . 'i';
            $stmt->bind_param(
                $bt,
                $header['direct_purchase_id'],
                $header['warehouse_id'],
                $header['return_date'],
                $header['remarks'],
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
            $rid = (int) $this->conn->insert_id;
            $stmt->close();

            $ins = $this->conn->prepare('INSERT INTO vp_direct_purchase_return_items (
                direct_purchase_return_id, direct_purchase_item_id, return_qty, gst_amount, line_total, sort_order
            ) VALUES (?,?,?,?,?,?)');
            $ord = 0;
            foreach ($lines as $ln) {
                $iid = (int) ($ln['direct_purchase_item_id'] ?? 0);
                $rq = (float) ($ln['return_qty'] ?? 0);
                if ($iid <= 0 || $rq <= 0) {
                    continue;
                }
                $gst = (float) ($ln['gst_amount'] ?? 0);
                $lt = (float) ($ln['line_total'] ?? 0);
                $ins->bind_param('iidddi', $rid, $iid, $rq, $gst, $lt, $ord);
                $ins->execute();
                $ord++;
            }
            $ins->close();

            $stockLines = $this->buildReturnStockLines($rid);
            DirectPurchaseStock::applyReturnOut($this->conn, $rid, (int) $header['warehouse_id'], $stockLines);

            $this->conn->commit();

            return $rid;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function deleteReturn(int $id): void
    {
        $row = $this->getById($id);
        if (!$row) {
            return;
        }
        $this->conn->begin_transaction();
        try {
            DirectPurchaseStock::reverseMovementsForRef($this->conn, DirectPurchaseStock::REF_RETURN, $id);
            $stmt = $this->conn->prepare('DELETE FROM vp_direct_purchase_returns WHERE id = ?');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
            $this->conn->commit();
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * @return array<int, array{sku: string, return_qty: float, product_id: int}>
     */
    private function buildReturnStockLines(int $returnId): array
    {
        $items = $this->getItems($returnId);
        $out = [];
        foreach ($items as $it) {
            $sku = trim((string) ($it['sku'] ?? ''));
            $rq = (float) ($it['return_qty'] ?? 0);
            if ($sku === '' || $rq <= 0) {
                continue;
            }
            $pid = DirectPurchaseStock::resolveProductId(
                $this->conn,
                $sku,
                (string) ($it['item_code'] ?? ''),
                (string) ($it['color'] ?? ''),
                (string) ($it['size'] ?? '')
            );
            $out[] = ['sku' => $sku, 'return_qty' => $rq, 'product_id' => $pid];
        }

        return $out;
    }
}
