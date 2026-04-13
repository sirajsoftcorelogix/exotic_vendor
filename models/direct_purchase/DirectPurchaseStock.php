<?php

/**
 * Warehouse stock (vp_stock) + vp_stock_movements for direct purchase IN and purchase return OUT.
 * Mirrors controllers/GrnsController.php GRN flow (8-column movement insert).
 */
final class DirectPurchaseStock
{
    public static function resolveProductId(\mysqli $conn, string $sku, string $itemCode, string $color, string $size): int
    {
        $sku = trim($sku);
        $itemCode = trim($itemCode);
        $color = trim($color);
        $size = trim($size);

        if ($itemCode !== '') {
            $sql = 'SELECT id FROM vp_products WHERE item_code = ? AND COALESCE(TRIM(color), \'\') = ? AND COALESCE(TRIM(size), \'\') = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('sss', $itemCode, $color, $size);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($row['id'])) {
                    return (int) $row['id'];
                }
            }
        }

        if ($sku !== '') {
            $sql = 'SELECT id FROM vp_products WHERE sku = ? LIMIT 1';
            $stmt = $conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $sku);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if (!empty($row['id'])) {
                    return (int) $row['id'];
                }
            }
        }

        return 0;
    }

    /**
     * @param array<int, array<string, mixed>> $postedItems Same shape as collectLineItemsFromPost
     * @return array<int, array{sku: string, qty: float, product_id: int}>
     */
    public static function buildStockLinesFromPostedItems(\mysqli $conn, array $postedItems): array
    {
        $out = [];
        foreach ($postedItems as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $qty = (float) ($row['qty'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }
            $pid = self::resolveProductId(
                $conn,
                $sku,
                (string) ($row['item_code'] ?? ''),
                (string) ($row['color'] ?? ''),
                (string) ($row['size'] ?? '')
            );
            $out[] = ['sku' => $sku, 'qty' => $qty, 'product_id' => $pid];
        }

        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $dbItems Rows from vp_direct_purchase_items
     * @return array<int, array{sku: string, qty: float, product_id: int}>
     */
    public static function buildStockLinesFromDbItems(\mysqli $conn, array $dbItems): array
    {
        $out = [];
        foreach ($dbItems as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $qty = (float) ($row['qty'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }
            $pid = self::resolveProductId(
                $conn,
                $sku,
                (string) ($row['item_code'] ?? ''),
                (string) ($row['color'] ?? ''),
                (string) ($row['size'] ?? '')
            );
            $out[] = ['sku' => $sku, 'qty' => $qty, 'product_id' => $pid];
        }

        return $out;
    }

    public const REF_PURCHASE = 'DIRECT_PURCHASE';

    public const REF_RETURN = 'DIRECT_PURCHASE_RETURN';

    /**
     * Remove all movements for ref and adjust vp_stock to match (best-effort; does not recalc running_stock on later rows).
     */
    public static function reverseMovementsForRef(\mysqli $conn, string $refType, int $refId): void
    {
        $refIdStr = (string) $refId;
        $sql = 'SELECT id, sku, warehouse_id, movement_type, quantity FROM vp_stock_movements
            WHERE ref_type = ? AND (ref_id = ? OR CAST(ref_id AS UNSIGNED) = ?)';
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            throw new \RuntimeException('reverseMovementsForRef prepare failed: ' . $conn->error);
        }
        $stmt->bind_param('ssi', $refType, $refIdStr, $refId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        foreach ($rows as $row) {
            $sku = (string) ($row['sku'] ?? '');
            $wh = (int) ($row['warehouse_id'] ?? 0);
            $qty = (float) ($row['quantity'] ?? 0);
            $movType = (string) ($row['movement_type'] ?? '');
            if ($sku === '' || $wh <= 0 || $qty <= 0) {
                continue;
            }
            if ($movType === 'IN') {
                self::adjustVpStock($conn, $sku, $wh, -$qty);
            } elseif ($movType === 'OUT') {
                self::adjustVpStock($conn, $sku, $wh, $qty);
            }
            $mid = (int) ($row['id'] ?? 0);
            if ($mid > 0) {
                $del = $conn->prepare('DELETE FROM vp_stock_movements WHERE id = ? LIMIT 1');
                if ($del) {
                    $del->bind_param('i', $mid);
                    $del->execute();
                    $del->close();
                }
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $lines Each: sku, qty, product_id (int)
     */
    public static function applyPurchaseIn(\mysqli $conn, int $purchaseId, int $warehouseId, array $lines, int $userId = 0): void
    {
        if ($warehouseId <= 0) {
            return;
        }
        $refType = self::REF_PURCHASE;
        $selectStock = $conn->prepare('SELECT id, current_stock FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        $updateStock = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
        $insertStock = $conn->prepare('INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)');
        $insertMov = $conn->prepare('INSERT INTO vp_stock_movements (product_id, sku, warehouse_id, movement_type, quantity, running_stock, ref_type, ref_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$selectStock || !$updateStock || !$insertStock || !$insertMov) {
            throw new \RuntimeException('applyPurchaseIn prepare failed: ' . $conn->error);
        }

        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (float) ($line['qty'] ?? 0);
            $productId = (int) ($line['product_id'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }

            $selectStock->bind_param('si', $sku, $warehouseId);
            $selectStock->execute();
            $r = $selectStock->get_result();
            $running = $qty;
            if ($r && ($stockRow = $r->fetch_assoc())) {
                $stockId = (int) $stockRow['id'];
                $current = (float) $stockRow['current_stock'];
                $running = $current + $qty;
                $updateStock->bind_param('dii', $running, $purchaseId, $stockId);
                if (!$updateStock->execute()) {
                    throw new \RuntimeException('vp_stock update failed: ' . $updateStock->error);
                }
            } else {
                $insertStock->bind_param('sidi', $sku, $warehouseId, $running, $purchaseId);
                if (!$insertStock->execute()) {
                    throw new \RuntimeException('vp_stock insert failed: ' . $insertStock->error);
                }
            }

            $movementType = 'IN';
            $pidBind = $productId > 0 ? $productId : 0;
            $refIdBind = $purchaseId;
            if (!$insertMov->bind_param('isisddsi', $pidBind, $sku, $warehouseId, $movementType, $qty, $running, $refType, $refIdBind)) {
                throw new \RuntimeException('movement bind_param failed');
            }
            if (!$insertMov->execute()) {
                throw new \RuntimeException('vp_stock_movements insert failed: ' . $insertMov->error);
            }
        }

        $selectStock->close();
        $updateStock->close();
        $insertStock->close();
        $insertMov->close();
    }

    /**
     * @param array<int, array<string, mixed>> $lines sku, return_qty, product_id
     */
    public static function applyReturnOut(\mysqli $conn, int $returnId, int $warehouseId, array $lines): void
    {
        if ($warehouseId <= 0) {
            return;
        }
        $refType = self::REF_RETURN;
        $selectStock = $conn->prepare('SELECT id, current_stock FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        $updateStock = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
        $insertMov = $conn->prepare('INSERT INTO vp_stock_movements (product_id, sku, warehouse_id, movement_type, quantity, running_stock, ref_type, ref_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$selectStock || !$updateStock || !$insertMov) {
            throw new \RuntimeException('applyReturnOut prepare failed: ' . $conn->error);
        }

        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (float) ($line['return_qty'] ?? 0);
            $productId = (int) ($line['product_id'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }

            $selectStock->bind_param('si', $sku, $warehouseId);
            $selectStock->execute();
            $r = $selectStock->get_result();
            if (!$r || !($stockRow = $r->fetch_assoc())) {
                throw new \RuntimeException('Insufficient warehouse stock row for return (SKU ' . $sku . '). Receive stock via direct purchase or GRN first.');
            }
            $stockId = (int) $stockRow['id'];
            $current = (float) $stockRow['current_stock'];
            if ($current + 1e-9 < $qty) {
                throw new \RuntimeException('Insufficient stock to return SKU ' . $sku . ' (available ' . $current . ', return ' . $qty . ').');
            }
            $running = $current - $qty;
            $updateStock->bind_param('dii', $running, $returnId, $stockId);
            if (!$updateStock->execute()) {
                throw new \RuntimeException('vp_stock update (return) failed: ' . $updateStock->error);
            }

            $movementType = 'OUT';
            $pidBind = $productId > 0 ? $productId : 0;
            if (!$insertMov->bind_param('isisddsi', $pidBind, $sku, $warehouseId, $movementType, $qty, $running, $refType, $returnId)) {
                throw new \RuntimeException('return movement bind_param failed');
            }
            if (!$insertMov->execute()) {
                throw new \RuntimeException('vp_stock_movements insert (return) failed: ' . $insertMov->error);
            }
        }

        $selectStock->close();
        $updateStock->close();
        $insertMov->close();
    }

    private static function adjustVpStock(\mysqli $conn, string $sku, int $warehouseId, float $delta): void
    {
        $stmt = $conn->prepare('SELECT id, current_stock FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('si', $sku, $warehouseId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && ($row = $res->fetch_assoc())) {
            $id = (int) $row['id'];
            $cur = (float) $row['current_stock'];
            $new = $cur + $delta;
            if ($new < 0) {
                $new = 0.0;
            }
            $stmt->close();
            $u = $conn->prepare('UPDATE vp_stock SET current_stock = ? WHERE id = ?');
            if ($u) {
                $u->bind_param('di', $new, $id);
                $u->execute();
                $u->close();
            }
        } else {
            $stmt->close();
        }
    }
}
