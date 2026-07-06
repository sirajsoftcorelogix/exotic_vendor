<?php

/**
 * Central vp_stock_movements writer — running_stock is always derived here from the ledger chain.
 */
final class StockMovement
{
    public const INBOUND_TYPES = ['IN', 'TRANSFER_IN', 'OPENING_STOCK'];

    /** Movement types shown in ledger "Stock In" column */
    public const STOCK_IN_DISPLAY_TYPES = ['IN', 'OPENING_STOCK', 'TRANSFER_IN'];

    /** Movement types shown in ledger "Stock Out" column */
    public const STOCK_OUT_DISPLAY_TYPES = ['OUT', 'TRANSFER_OUT'];

    public static function isTransferMovement(string $movementType): bool
    {
        $type = strtoupper(trim($movementType));

        return $type === 'TRANSFER_IN' || $type === 'TRANSFER_OUT';
    }

    public static function isStockInDisplayType(string $movementType): bool
    {
        return in_array(strtoupper(trim($movementType)), self::STOCK_IN_DISPLAY_TYPES, true);
    }

    public static function isStockOutDisplayType(string $movementType): bool
    {
        return in_array(strtoupper(trim($movementType)), self::STOCK_OUT_DISPLAY_TYPES, true);
    }

    public static function isInbound(string $movementType): bool
    {
        return in_array(strtoupper(trim($movementType)), self::INBOUND_TYPES, true);
    }

    public static function getLastRunningStock(\mysqli $conn, string $sku, int $warehouseId): float
    {
        if ($warehouseId <= 0 || trim($sku) === '') {
            return 0.0;
        }
        $stmt = $conn->prepare(
            'SELECT running_stock FROM vp_stock_movements
             WHERE sku = ? AND warehouse_id = ?
             ORDER BY id DESC LIMIT 1'
        );
        if (!$stmt) {
            return 0.0;
        }
        $stmt->bind_param('si', $sku, $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return (float) ($row['running_stock'] ?? 0);
    }

    /**
     * Sum of latest running_stock per warehouse for a product.
     */
    public static function getPhysicalStockTotalFromMovements(\mysqli $conn, int $productId): int
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }
        $sql = '
            SELECT COALESCE(SUM(sm.running_stock), 0) AS total_stock
            FROM vp_stock_movements sm
            INNER JOIN (
                SELECT warehouse_id, product_id, MAX(id) AS max_id
                FROM vp_stock_movements
                WHERE product_id = ?
                GROUP BY warehouse_id, product_id
            ) latest ON sm.warehouse_id = latest.warehouse_id
                AND sm.product_id = latest.product_id
                AND sm.id = latest.max_id
            WHERE sm.product_id = ?';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('ii', $productId, $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['total_stock'] ?? 0));
    }

    /**
     * Qty dispatched but not yet GRN-received at destination — keeps product physical total stable during transfer.
     */
    public static function getInTransitQtyForProduct(\mysqli $conn, int $productId): int
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }
        $sql = '
            SELECT COALESCE(SUM(GREATEST(0, ist.transfer_qty - COALESCE(gr.received_qty, 0))), 0) AS in_transit
            FROM vp_item_stock_transfer ist
            INNER JOIN vp_stock_transfer st
                ON st.transfer_order_no COLLATE utf8mb4_unicode_ci = ist.transfer_order_no COLLATE utf8mb4_unicode_ci
            LEFT JOIN (
                SELECT transfer_id, sku, SUM(qty_received) AS received_qty
                FROM vp_stock_transfer_grns
                GROUP BY transfer_id, sku
            ) gr ON gr.transfer_id = st.id
                AND gr.sku COLLATE utf8mb4_unicode_ci = ist.sku COLLATE utf8mb4_unicode_ci
            WHERE ist.product_id = ?
              AND ist.transfer_qty > COALESCE(gr.received_qty, 0)';
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['in_transit'] ?? 0));
    }

    /**
     * Warehouse on-hand + in-transit transfer qty (total physical inventory for a product).
     */
    public static function getPhysicalStockTotalIncludingInTransit(\mysqli $conn, int $productId): int
    {
        return self::getPhysicalStockTotalFromMovements($conn, $productId)
            + self::getInTransitQtyForProduct($conn, $productId);
    }

    /**
     * Physical stock may be initialized from website local_stock only when the product has
     * never had warehouse stock: physical_stock is 0 and vp_stock_movements has no rows.
     */
    public static function isPhysicalStockUninitialized(\mysqli $conn, int $productId): bool
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return false;
        }

        $stmt = $conn->prepare('SELECT physical_stock FROM vp_products WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('i', $productId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || (int) ($row['physical_stock'] ?? 0) !== 0) {
            return false;
        }

        $mov = $conn->prepare('SELECT 1 FROM vp_stock_movements WHERE product_id = ? LIMIT 1');
        if (!$mov) {
            return false;
        }
        $mov->bind_param('i', $productId);
        $mov->execute();
        $hasMovement = (bool) $mov->get_result()->fetch_assoc();
        $mov->close();

        return !$hasMovement;
    }

    /**
     * @param array{strict_stock_check?: bool, product_id?: int} $options
     * @return array{running_stock: float, last_running: float}
     */
    public static function computeRunningStock(
        \mysqli $conn,
        string $sku,
        int $warehouseId,
        string $movementType,
        float $quantity,
        array $options = []
    ): array {
        $qty = max(0.0, $quantity);
        $movementType = strtoupper(trim($movementType));
        $isInbound = self::isInbound($movementType);
        $lastRunning = 0.0;

        if ($warehouseId > 0 && trim($sku) !== '') {
            $lastRunning = self::getLastRunningStock($conn, $sku, $warehouseId);
        } elseif (!$isInbound && !empty($options['product_id'])) {
            $lastRunning = (float) self::getPhysicalStockTotalFromMovements($conn, (int) $options['product_id']);
        }

        $strict = !array_key_exists('strict_stock_check', $options) || !empty($options['strict_stock_check']);
        if (!$isInbound && $strict && $qty > $lastRunning + 1e-9) {
            throw new \RuntimeException(
                'Insufficient stock: available ' . $lastRunning . ', requested ' . $qty
            );
        }

        if ($warehouseId > 0 && trim($sku) !== '') {
            $running = $isInbound ? $lastRunning + $qty : max(0.0, $lastRunning - $qty);
        } elseif (!$isInbound) {
            $running = max(0.0, $lastRunning - $qty);
        } else {
            $running = $qty;
        }

        return ['running_stock' => $running, 'last_running' => $lastRunning];
    }

    /**
     * Insert a movement row; running_stock is computed inside this method.
     *
     * Expected keys: product_id, sku, item_code, size, color, warehouse_id, location,
     * movement_type, quantity, ref_type, ref_id, reason, update_by_user (or user_id).
     * Optional: strict_stock_check (bool), sync_physical_stock (bool, default true when product_id > 0).
     *
     * @return array{running_stock: float, movement_id: int}
     */
    public static function insert(\mysqli $conn, array $data): array
    {
        $productId = (int) ($data['product_id'] ?? 0);
        $sku = trim((string) ($data['sku'] ?? ''));
        $itemCode = trim((string) ($data['item_code'] ?? ''));
        $size = trim((string) ($data['size'] ?? ''));
        $color = trim((string) ($data['color'] ?? ''));
        $warehouseId = (int) ($data['warehouse_id'] ?? 0);
        $location = trim((string) ($data['location'] ?? ''));
        $movementType = strtoupper(trim((string) ($data['movement_type'] ?? 'OUT')));
        $quantity = (float) ($data['quantity'] ?? 0);
        $refType = isset($data['ref_type']) && $data['ref_type'] !== ''
            ? (string) $data['ref_type']
            : 'MANUAL';
        $refId = array_key_exists('ref_id', $data) ? (string) $data['ref_id'] : '0';
        $reason = trim((string) ($data['reason'] ?? ''));
        $userId = isset($data['update_by_user'])
            ? (int) $data['update_by_user']
            : (isset($data['user_id']) ? (int) $data['user_id'] : 0);

        $refTypeUpper = strtoupper(trim($refType));
        $movementTypeUpper = strtoupper(trim($movementType));
        if (
            $productId > 0
            && $refTypeUpper === 'API_REFRESH'
            && $movementTypeUpper === 'OPENING_STOCK'
            && !self::isPhysicalStockUninitialized($conn, $productId)
        ) {
            throw new \RuntimeException(
                'Physical stock cannot be initialized from API refresh: product already has warehouse stock or movement history.'
            );
        }

        $options = [
            'strict_stock_check' => $data['strict_stock_check'] ?? true,
        ];
        if ($productId > 0) {
            $options['product_id'] = $productId;
        }

        $computed = self::computeRunningStock($conn, $sku, $warehouseId, $movementType, $quantity, $options);
        $runningStock = $computed['running_stock'];

        $itemCodeCol = self::resolveItemCodeColumn($conn);
        $safeItemCol = '`' . str_replace('`', '``', $itemCodeCol) . '`';
        $pidBind = $productId > 0 ? $productId : 0;
        $qtyBind = (string) $quantity;
        $runningBind = (float) $runningStock;

        $fullSql = "INSERT INTO vp_stock_movements
            (product_id, sku, {$safeItemCol}, size, color, warehouse_id, location, movement_type, quantity, running_stock, ref_type, ref_id, reason, update_by_user, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($fullSql);
        if ($stmt) {
            $stmt->bind_param(
                'issssisssdsssi',
                $pidBind,
                $sku,
                $itemCode,
                $size,
                $color,
                $warehouseId,
                $location,
                $movementType,
                $qtyBind,
                $runningBind,
                $refType,
                $refId,
                $reason,
                $userId
            );
            if ($stmt->execute()) {
                $movementId = (int) $conn->insert_id;
                $stmt->close();
            } else {
                $fullErr = $stmt->error;
                $stmt->close();
                $movementId = self::insertMinimal($conn, $pidBind, $sku, $warehouseId, $movementType, $qtyBind, $runningBind, $refType, $refId, $fullErr);
            }
        } else {
            $movementId = self::insertMinimal($conn, $pidBind, $sku, $warehouseId, $movementType, $qtyBind, $runningBind, $refType, $refId, $conn->error);
        }

        $syncPhysical = array_key_exists('sync_physical_stock', $data)
            ? !empty($data['sync_physical_stock'])
            : ($productId > 0 && !self::isTransferMovement($movementType));
        if ($syncPhysical && $productId > 0) {
            self::syncProductPhysicalStock($conn, $productId);
        }

        return ['running_stock' => $runningStock, 'movement_id' => $movementId];
    }

    /**
     * Sync vp_products.physical_stock from the movement ledger (+ in-transit).
     * Optional only_if_uninitialized: skip when product already has physical_stock or movements
     * (used to block local-stock-only flows from altering warehouse stock).
     *
     * @param array{only_if_uninitialized?: bool} $options
     */
    public static function syncProductPhysicalStock(\mysqli $conn, int $productId, array $options = []): void
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return;
        }
        if (!empty($options['only_if_uninitialized']) && !self::isPhysicalStockUninitialized($conn, $productId)) {
            return;
        }
        $physicalTotal = self::getPhysicalStockTotalIncludingInTransit($conn, $productId);
        $stmt = $conn->prepare('UPDATE vp_products SET physical_stock = ? WHERE id = ?');
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('ii', $physicalTotal, $productId);
        $stmt->execute();
        $stmt->close();
    }

    private static function insertMinimal(
        \mysqli $conn,
        int $productId,
        string $sku,
        int $warehouseId,
        string $movementType,
        string $quantity,
        float $runningStock,
        string $refType,
        string $refId,
        string $priorError
    ): int {
        $minimalSql = 'INSERT INTO vp_stock_movements (product_id, sku, warehouse_id, movement_type, quantity, running_stock, ref_type, ref_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)';
        $min = $conn->prepare($minimalSql);
        if (!$min) {
            throw new \RuntimeException('vp_stock_movements insert failed: ' . ($priorError ?: $conn->error));
        }
        $min->bind_param('isissdss', $productId, $sku, $warehouseId, $movementType, $quantity, $runningStock, $refType, $refId);
        if (!$min->execute()) {
            $err = $min->error;
            $min->close();
            throw new \RuntimeException('vp_stock_movements insert failed: ' . ($err ?: $priorError));
        }
        $movementId = (int) $conn->insert_id;
        $min->close();

        return $movementId;
    }

    private static function resolveItemCodeColumn(\mysqli $conn): string
    {
        static $cached = [];
        $key = spl_object_id($conn);
        if (isset($cached[$key])) {
            return $cached[$key];
        }
        $col = 'item_code';
        $res = @$conn->query('SHOW COLUMNS FROM vp_stock_movements');
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $field = trim((string) ($row['Field'] ?? ''));
                if (strcasecmp($field, 'item_code') === 0) {
                    $col = $field;
                    break;
                }
            }
            $res->free();
        }
        $cached[$key] = $col;

        return $col;
    }
}
