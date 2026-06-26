<?php

/**
 * Warehouse stock (vp_stock) + vp_stock_movements for direct purchase IN and purchase return OUT.
 * vp_stock and physical_stock are derived from vp_stock_movements via product::insertStockMovement().
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
     * Remove all movements for ref, then resync vp_stock + physical_stock from the ledger.
     */
    public static function reverseMovementsForRef(\mysqli $conn, string $refType, int $refId): void
    {
        $refIdStr = (string) $refId;
        $sql = 'SELECT id, sku, warehouse_id, product_id FROM vp_stock_movements
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

        $affectedSkuWh = [];
        $affectedProductIds = [];

        foreach ($rows as $row) {
            $sku = trim((string) ($row['sku'] ?? ''));
            $wh = (int) ($row['warehouse_id'] ?? 0);
            $pid = (int) ($row['product_id'] ?? 0);
            if ($sku !== '' && $wh > 0) {
                $affectedSkuWh[$sku . '|' . $wh] = ['sku' => $sku, 'warehouse_id' => $wh];
            }
            if ($pid > 0) {
                $affectedProductIds[$pid] = $pid;
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

        if ($affectedSkuWh === [] && $affectedProductIds === []) {
            return;
        }

        require_once __DIR__ . '/../product/product.php';
        $productModel = new product($conn);
        foreach ($affectedSkuWh as $entry) {
            $productModel->syncVpStockRowFromLatestMovement($entry['sku'], $entry['warehouse_id']);
        }
        foreach ($affectedProductIds as $pid) {
            $productModel->syncPhysicalStockTotalFromWarehouses($pid);
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
        require_once __DIR__ . '/../product/product.php';
        $productModel = new product($conn);
        $refType = self::REF_PURCHASE;
        $location = self::warehouseLocationLabel($conn, $warehouseId);

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

            $result = $productModel->insertStockMovement([
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
                'warehouse_id' => $warehouseId,
                'location' => $location,
                'movement_type' => 'IN',
                'quantity' => (int) round($qty),
                'user_id' => $userId,
                'reason' => 'Direct purchase',
                'ref_type' => $refType,
                'ref_id' => (string) $purchaseId,
            ], false);
            if (empty($result['success'])) {
                throw new \RuntimeException($result['message'] ?? ('Failed to record stock IN for SKU ' . $sku));
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $lines sku, return_qty, product_id, item_code, size, color
     */
    public static function applyReturnOut(\mysqli $conn, int $returnId, int $warehouseId, array $lines, int $userId = 0): void
    {
        if ($warehouseId <= 0) {
            return;
        }
        require_once __DIR__ . '/../product/product.php';
        $productModel = new product($conn);
        $refType = self::REF_RETURN;
        $location = self::warehouseLocationLabel($conn, $warehouseId);

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

            $available = $productModel->getLatestRunningStockForSkuWarehouse($sku, $warehouseId);
            if ($available + 1e-9 < $qty) {
                throw new \RuntimeException(
                    'Insufficient stock to return SKU ' . $sku . ' (available ' . $available . ', return ' . $qty . ').'
                );
            }

            $result = $productModel->insertStockMovement([
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
                'warehouse_id' => $warehouseId,
                'location' => $location,
                'movement_type' => 'OUT',
                'quantity' => (int) round($qty),
                'user_id' => $userId,
                'reason' => 'Direct purchase return',
                'ref_type' => $refType,
                'ref_id' => (string) $returnId,
            ], false);
            if (empty($result['success'])) {
                throw new \RuntimeException($result['message'] ?? ('Failed to record stock OUT for SKU ' . $sku));
            }
        }
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
}

