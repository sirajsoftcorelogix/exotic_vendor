<?php

require_once __DIR__ . '/../product/StockMovement.php';
require_once __DIR__ . '/../direct_purchase/DirectPurchaseStock.php';

/**
 * Purchase Order GRN — adds purchased stock at warehouse (movement IN).
 * Distinct from stock transfer GRN (TRANSFER_IN, ref_type GRN on vp_stock_transfer_grns).
 */
final class PoGrnStock
{
    public const REF_PURCHASE_GRN = 'PURCHASE_GRN';

    /**
     * Record one PO GRN line as warehouse IN via the central movement ledger.
     *
     * @param array{sku: string, qty: float, product_id?: int, item_code?: string, size?: string, color?: string} $line
     */
    public static function applyGrnReceipt(
        \mysqli $conn,
        int $grnId,
        int $warehouseId,
        array $line,
        int $userId = 0,
        string $poNumber = ''
    ): void {
        if ($grnId <= 0 || $warehouseId <= 0) {
            throw new \RuntimeException('Invalid GRN or warehouse for stock receipt.');
        }

        $sku = trim((string) ($line['sku'] ?? ''));
        $qty = (float) ($line['qty'] ?? 0);
        $itemCode = trim((string) ($line['item_code'] ?? ''));
        $size = trim((string) ($line['size'] ?? ''));
        $color = trim((string) ($line['color'] ?? ''));
        $productId = (int) ($line['product_id'] ?? 0);

        if ($sku === '' || $qty <= 0) {
            return;
        }

        if ($productId <= 0) {
            $productId = DirectPurchaseStock::resolveProductId($conn, $sku, $itemCode, $color, $size);
        }

        $location = self::warehouseLocationLabel($conn, $warehouseId);
        $reason = 'PO GRN receipt';
        if ($poNumber !== '') {
            $reason .= ' (' . $poNumber . ')';
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
            'ref_type' => self::REF_PURCHASE_GRN,
            'ref_id' => (string) $grnId,
            'update_by_user' => $userId,
            'reason' => $reason,
            'strict_stock_check' => false,
            'sync_physical_stock' => true,
        ]);

        self::syncLegacyVpStock($conn, $sku, $warehouseId, $movement['running_stock'], $grnId);
    }

    private static function syncLegacyVpStock(
        \mysqli $conn,
        string $sku,
        int $warehouseId,
        float $runningStock,
        int $grnId
    ): void {
        $select = $conn->prepare('SELECT id FROM vp_stock WHERE sku = ? AND warehouse_id = ? LIMIT 1');
        if (!$select) {
            return;
        }
        $select->bind_param('si', $sku, $warehouseId);
        $select->execute();
        $row = $select->get_result()->fetch_assoc();
        $select->close();

        if ($row) {
            $stockId = (int) $row['id'];
            $update = $conn->prepare('UPDATE vp_stock SET current_stock = ?, last_trans_id = ? WHERE id = ?');
            if (!$update) {
                return;
            }
            $update->bind_param('dii', $runningStock, $grnId, $stockId);
            $update->execute();
            $update->close();
        } else {
            $insert = $conn->prepare('INSERT INTO vp_stock (sku, warehouse_id, current_stock, last_trans_id) VALUES (?, ?, ?, ?)');
            if (!$insert) {
                return;
            }
            $insert->bind_param('sidi', $sku, $warehouseId, $runningStock, $grnId);
            $insert->execute();
            $insert->close();
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
