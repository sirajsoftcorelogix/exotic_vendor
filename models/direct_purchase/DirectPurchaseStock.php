<?php

require_once __DIR__ . '/../product/StockMovement.php';

/**
 * Warehouse stock (vp_stock) + vp_stock_movements for direct purchase IN and purchase return OUT.
 */
final class DirectPurchaseStock
{
    public const REF_PURCHASE = 'DIRECT_PURCHASE';

    public const REF_RETURN = 'DIRECT_PURCHASE_RETURN';

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
     * @return array<int, array{sku: string, qty: float, product_id: int, item_code: string, size: string, color: string}>
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
            $itemCode = trim((string) ($row['item_code'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            $size = trim((string) ($row['size'] ?? ''));
            $pid = self::resolveProductId($conn, $sku, $itemCode, $color, $size);
            $out[] = [
                'sku' => $sku,
                'qty' => $qty,
                'product_id' => $pid,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
            ];
        }

        return $out;
    }

    /**
     * @param list<array{sku?:string,return_qty?:float}> $lines
     */
    public static function validateWarehouseStockForReturn(\mysqli $conn, int $warehouseId, array $lines): ?string
    {
        if ($warehouseId <= 0) {
            return 'Warehouse is required for purchase return.';
        }

        $qtyBySku = [];
        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (float) ($line['return_qty'] ?? 0);
            if ($sku === '' || $qty <= 0) {
                continue;
            }
            if (!isset($qtyBySku[$sku])) {
                $qtyBySku[$sku] = 0.0;
            }
            $qtyBySku[$sku] += $qty;
        }

        if ($qtyBySku === []) {
            return null;
        }

        $shortfalls = [];
        foreach ($qtyBySku as $sku => $returnQty) {
            $available = StockMovement::getLastRunningStock($conn, $sku, $warehouseId);
            if ($returnQty > $available + 1e-9) {
                $shortfalls[] = $sku . ' (available ' . self::formatStockQty($available)
                    . ', return ' . self::formatStockQty($returnQty) . ')';
            }
        }

        if ($shortfalls === []) {
            return null;
        }

        $shown = array_slice($shortfalls, 0, 5);
        $suffix = count($shortfalls) > 5 ? ' (and ' . (count($shortfalls) - 5) . ' more)' : '';

        return 'Insufficient warehouse stock. ' . implode('; ', $shown) . $suffix . '.';
    }

    private static function formatStockQty(float $qty): string
    {
        $formatted = number_format($qty, 3, '.', '');

        return rtrim(rtrim($formatted, '0'), '.') ?: '0';
    }

    /**
     * @param array<int, array<string, mixed>> $dbItems Rows from vp_direct_purchase_items
     * @return array<int, array{sku: string, qty: float, product_id: int, item_code: string, size: string, color: string}>
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
            $itemCode = trim((string) ($row['item_code'] ?? ''));
            $color = trim((string) ($row['color'] ?? ''));
            $size = trim((string) ($row['size'] ?? ''));
            $pid = self::resolveProductId($conn, $sku, $itemCode, $color, $size);
            $out[] = [
                'sku' => $sku,
                'qty' => $qty,
                'product_id' => $pid,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
            ];
        }

        return $out;
    }

    /**
     * Remove all movements for ref and adjust vp_stock to match (best-effort; does not recalc running_stock on later rows).
     */
    public static function reverseMovementsForRef(\mysqli $conn, string $refType, int $refId): void
    {
        $refIdStr = (string) $refId;
        $sql = 'SELECT id, sku, warehouse_id, movement_type, quantity, product_id FROM vp_stock_movements
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

        $productIdsToSync = [];
        foreach ($rows as $row) {
            $sku = (string) ($row['sku'] ?? '');
            $wh = (int) ($row['warehouse_id'] ?? 0);
            $qty = (float) ($row['quantity'] ?? 0);
            $movType = (string) ($row['movement_type'] ?? '');
            $productId = (int) ($row['product_id'] ?? 0);
            if ($sku === '' || $wh <= 0 || $qty <= 0) {
                continue;
            }
            if ($movType === 'IN') {
                self::adjustVpStock($conn, $sku, $wh, -$qty);
            } elseif ($movType === 'OUT') {
                self::adjustVpStock($conn, $sku, $wh, $qty);
            }
            if ($productId > 0) {
                $productIdsToSync[$productId] = true;
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

        foreach (array_keys($productIdsToSync) as $productId) {
            StockMovement::syncProductPhysicalStock($conn, (int) $productId);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $lines Each: sku, qty, product_id, item_code, size, color
     */
    public static function applyPurchaseIn(\mysqli $conn, int $purchaseId, int $warehouseId, array $lines, int $userId = 0): void
    {
        if ($warehouseId <= 0) {
            return;
        }
        $refType = self::REF_PURCHASE;
        $location = self::warehouseLocationLabel($conn, $warehouseId);
        $selectStock = $conn->prepare('SELECT id, current_stock FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        $updateStock = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
        $insertStock = $conn->prepare('INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)');
        if (!$selectStock || !$updateStock || !$insertStock) {
            throw new \RuntimeException('applyPurchaseIn prepare failed: ' . $conn->error);
        }

        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (float) ($line['qty'] ?? 0);
            $productId = (int) ($line['product_id'] ?? 0);
            $itemCode = trim((string) ($line['item_code'] ?? ''));
            $size = trim((string) ($line['size'] ?? ''));
            $color = trim((string) ($line['color'] ?? ''));
            if ($sku === '' || $qty <= 0) {
                continue;
            }

            $movement = StockMovement::insert($conn, [
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
                'warehouse_id' => $warehouseId,
                'location' => $location,
                'movement_type' => 'IN',
                'quantity' => $qty,
                'ref_type' => $refType,
                'ref_id' => (string) $purchaseId,
                'update_by_user' => $userId,
                'reason' => 'Direct purchase',
                'strict_stock_check' => false,
            ]);
            $running = $movement['running_stock'];

            $selectStock->bind_param('si', $sku, $warehouseId);
            $selectStock->execute();
            $r = $selectStock->get_result();
            if ($r && ($stockRow = $r->fetch_assoc())) {
                $stockId = (int) $stockRow['id'];
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
        }

        $selectStock->close();
        $updateStock->close();
        $insertStock->close();
    }

    /**
     * @param array<int, array<string, mixed>> $lines sku, return_qty, product_id, item_code, size, color
     */
    public static function applyReturnOut(\mysqli $conn, int $returnId, int $warehouseId, array $lines, int $userId = 0): void
    {
        if ($warehouseId <= 0) {
            return;
        }
        $refType = self::REF_RETURN;
        $location = self::warehouseLocationLabel($conn, $warehouseId);
        $selectStock = $conn->prepare('SELECT id, current_stock FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        $updateStock = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
        if (!$selectStock || !$updateStock) {
            throw new \RuntimeException('applyReturnOut prepare failed: ' . $conn->error);
        }

        foreach ($lines as $line) {
            $sku = trim((string) ($line['sku'] ?? ''));
            $qty = (float) ($line['return_qty'] ?? 0);
            $productId = (int) ($line['product_id'] ?? 0);
            $itemCode = trim((string) ($line['item_code'] ?? ''));
            $size = trim((string) ($line['size'] ?? ''));
            $color = trim((string) ($line['color'] ?? ''));
            if ($sku === '' || $qty <= 0) {
                continue;
            }

            $movement = StockMovement::insert($conn, [
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
                'warehouse_id' => $warehouseId,
                'location' => $location,
                'movement_type' => 'OUT',
                'quantity' => $qty,
                'ref_type' => $refType,
                'ref_id' => (string) $returnId,
                'update_by_user' => $userId,
                'reason' => 'Direct purchase return',
            ]);
            $running = $movement['running_stock'];

            $selectStock->bind_param('si', $sku, $warehouseId);
            $selectStock->execute();
            $r = $selectStock->get_result();
            if ($r && ($stockRow = $r->fetch_assoc())) {
                $stockId = (int) $stockRow['id'];
                $updateStock->bind_param('dii', $running, $returnId, $stockId);
                if (!$updateStock->execute()) {
                    throw new \RuntimeException('vp_stock update (return) failed: ' . $updateStock->error);
                }
            } else {
                $insertStock = $conn->prepare('INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)');
                if (!$insertStock) {
                    throw new \RuntimeException('applyReturnOut vp_stock insert prepare failed: ' . $conn->error);
                }
                $insertStock->bind_param('sidi', $sku, $warehouseId, $running, $returnId);
                if (!$insertStock->execute()) {
                    throw new \RuntimeException('vp_stock insert (return) failed: ' . $insertStock->error);
                }
                $insertStock->close();
            }
        }

        $selectStock->close();
        $updateStock->close();
    }

    private static function warehouseLocationLabel(\mysqli $conn, int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return '';
        }
        $stmt = $conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return trim((string) ($row['address_title'] ?? ''));
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
