<?php
class StockTransfer
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
        $this->ensureTransferTableSchema();
    }
    
    /**
     * Ensure vp_stock_transfer has eway_bill_file column.
     */
    private function ensureTransferTableSchema()
    {
        $columnCheckSql = "SHOW COLUMNS FROM vp_stock_transfer LIKE 'eway_bill_file'";
        $result = $this->db->query($columnCheckSql);
        if ($result && $result->num_rows === 0) {
            $this->db->query("ALTER TABLE vp_stock_transfer ADD COLUMN eway_bill_file VARCHAR(1024) DEFAULT NULL");
        }
    }
    
    /**
     * Resolve a catalog product for a transfer line: product_id, sku, or item_code + size + color.
     *
     * @return array{id:int,sku:string,item_code:string,title:string,location:string,size:string,color:string}|null
     */
    public function resolveProductForTransferItem(array $item): ?array
    {
        $productId = (int)($item['product_id'] ?? 0);
        $pick = static function ($row): ?array {
            if (!$row || empty($row['id'])) {
                return null;
            }

            return [
                'id' => (int)$row['id'],
                'sku' => (string)($row['sku'] ?? ''),
                'item_code' => (string)($row['item_code'] ?? ''),
                'title' => (string)($row['title'] ?? ''),
                'location' => (string)($row['location'] ?? ''),
                'size' => (string)($row['size'] ?? ''),
                'color' => (string)($row['color'] ?? ''),
            ];
        };

        $select = 'SELECT id, sku, item_code, title, location, IFNULL(size, \'\') AS size, IFNULL(color, \'\') AS color FROM vp_products';

        if ($productId > 0) {
            $stmt = $this->db->prepare($select . ' WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $productId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                return $pick($row);
            }

            return null;
        }

        $sku = trim((string)($item['sku'] ?? ''));
        if ($sku !== '') {
            $stmt = $this->db->prepare($select . ' WHERE sku = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('s', $sku);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                return $pick($row);
            }

            return null;
        }

        $itemCode = trim((string)($item['item_code'] ?? ''));
        if ($itemCode === '') {
            return null;
        }

        $size = trim((string)($item['size'] ?? ''));
        $color = trim((string)($item['color'] ?? ''));

        $stmt = $this->db->prepare($select . ' WHERE item_code = ? AND IFNULL(TRIM(size), \'\') = ? AND IFNULL(TRIM(color), \'\') = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('sss', $itemCode, $size, $color);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $pick($row);
    }

    /**
     * Merge bulk rows (item_code, size, color, quantity) into transfer items; resolves products and sums duplicate variants.
     *
     * @param list<array<string,mixed>> $rows
     * @return array{items: list<array<string,mixed>>, errors: list<string>}
     */
    public function aggregateBulkVariantRows(array $rows): array
    {
        $merged = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $ic = trim((string)($row['item_code'] ?? ''));
            $sz = trim((string)($row['size'] ?? ''));
            $cl = trim((string)($row['color'] ?? ''));
            $qty = isset($row['quantity']) ? (int)$row['quantity'] : 0;
            if ($ic === '' || $qty <= 0) {
                continue;
            }
            $k = strtolower($ic) . "\x1e" . $sz . "\x1e" . $cl;
            if (!isset($merged[$k])) {
                $merged[$k] = ['item_code' => $ic, 'size' => $sz, 'color' => $cl, 'qty' => 0];
            }
            $merged[$k]['qty'] += $qty;
        }

        if ($merged === []) {
            return ['items' => [], 'errors' => ['No valid rows. Each row needs ItemCode and a positive Quantity.']];
        }

        $items = [];
        $errors = [];
        foreach ($merged as $m) {
            $resolved = $this->resolveProductForTransferItem([
                'product_id' => 0,
                'sku' => '',
                'item_code' => $m['item_code'],
                'size' => $m['size'],
                'color' => $m['color'],
            ]);
            if (!$resolved) {
                $errors[] = 'No product for ItemCode ' . $m['item_code']
                    . ', Size ' . ($m['size'] !== '' ? $m['size'] : '(blank)')
                    . ', Color ' . ($m['color'] !== '' ? $m['color'] : '(blank)');
                continue;
            }
            $pid = (int)$resolved['id'];
            if (isset($items[$pid])) {
                $items[$pid]['transfer_qty'] += $m['qty'];
            } else {
                $items[$pid] = [
                    'product_id' => $pid,
                    'item_code' => $resolved['item_code'],
                    'sku' => $resolved['sku'],
                    'title' => $resolved['title'],
                    'transfer_qty' => $m['qty'],
                    'item_notes' => '',
                ];
            }
        }

        return ['items' => array_values($items), 'errors' => $errors];
    }

    /**
     * Create a stock transfer record
     * @param array $data Transfer data
     * @return array Result with success status and message
     */
    public function createTransfer($data)
    {
        $this->db->begin_transaction();
        
        try {
            // Generate unique transfer order number
            $from_warehouse = (int)$data['from_warehouse'];
            $to_warehouse = (int)$data['to_warehouse'];
            $transfer_order_no = $this->generateUniqueTransferOrderNo($from_warehouse, $to_warehouse);
            
            // 1. Insert into vp_stock_transfer table
            $insertTransferSQL = "INSERT INTO vp_stock_transfer 
                (transfer_order_no, from_warehouse, to_warehouse, dispatch_date, est_delivery_date, 
                 requested_by, dispatch_by, booking_no, vehicle_no, vehicle_type, driver_name, driver_mobile, 
                 eway_bill_file, create_pickup_list, create_picking_slip, create_delivery_challan, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($insertTransferSQL);
            if (!$stmt) {
                throw new Exception('Prepare error: ' . $this->db->error);
            }
            
            $from_warehouse = (int)$data['from_warehouse'];
            $to_warehouse = (int)$data['to_warehouse'];
            $dispatch_date = $data['dispatch_date'];
            $est_delivery_date = $data['est_delivery_date'];
            $requested_by = (int)$data['requested_by'];
            $dispatch_by = (int)$data['dispatch_by'];
            $booking_no = $data['booking_no'] ?? '';
            $vehicle_no = $data['vehicle_no'] ?? '';
            $vehicle_type = $data['vehicle_type'] ?? '';
            $driver_name = $data['driver_name'] ?? '';
            $driver_mobile = $data['driver_mobile'] ?? '';
            $eway_bill_file = $data['eway_bill_file'] ?? '';
            $pickup_list = 0;
            $picking_slip = 0;
            $delivery_challan = 0;
            $status_pending = 'pending';
            $created_by = (int)($data['user_id'] ?? 1);
            
            // Type string: s i i s s i i s s s s s s i i i s i
            $stmt->bind_param(
                'siissiissssssiiisi',
                $transfer_order_no,
                $from_warehouse,
                $to_warehouse,
                $dispatch_date,
                $est_delivery_date,
                $requested_by,
                $dispatch_by,
                $booking_no,
                $vehicle_no,
                $vehicle_type,
                $driver_name,
                $driver_mobile,
                $eway_bill_file,
                $pickup_list,
                $picking_slip,
                $delivery_challan,
                $status_pending,
                $created_by
            );
            
            if (!$stmt->execute()) {
                throw new Exception('Execute error: ' . $stmt->error);
            }
            $stmt->close();
            
            // 2. Insert items and create stock movements
            if (!isset($data['items']) || empty($data['items'])) {
                throw new Exception('No items to transfer');
            }
            
            foreach ($data['items'] as $item) {
                $item_code = trim($item['item_code'] ?? '');
                $transfer_qty = (int)($item['transfer_qty'] ?? 0);

                if ($transfer_qty <= 0) {
                    continue;
                }

                $product = $this->resolveProductForTransferItem($item);
                if (!$product) {
                    $hint = trim((string)($item['sku'] ?? ''));
                    if ($hint !== '') {
                        throw new Exception('Product not found for SKU: ' . $hint);
                    }
                    $hint = trim((string)($item['item_code'] ?? ''));

                    throw new Exception('Product not found for item line (item code: ' . ($hint !== '' ? $hint : '(missing)') . ').');
                }

                $product_id = (int)$product['id'];
                $sku = $product['sku'];
                $title = $product['title'];
                $location = $product['location'] ?? '';
                $size = $product['size'] ?? '';
                $color = $product['color'] ?? '';
                if ($item_code === '') {
                    $item_code = $product['item_code'] ?? '';
                }
                $item_notes = trim($item['item_notes'] ?? '');
                
                // Insert into vp_item_stock_transfer
                $insertItemSQL = "INSERT INTO vp_item_stock_transfer 
                    (transfer_order_no, product_id, item_code, sku, title, transfer_qty, item_notes)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
                
                $istmt = $this->db->prepare($insertItemSQL);
                if (!$istmt) {
                    throw new Exception('Prepare error: ' . $this->db->error);
                }
                // Type: s(transfer_order_no) i(product_id) s(item_code) s(sku) s(title) i(transfer_qty) s(item_notes)
                $istmt->bind_param('sisssis', $transfer_order_no, $product_id, $item_code, $sku, $title, $transfer_qty, $item_notes);
                if (!$istmt->execute()) {
                    throw new Exception('Execute error: ' . $istmt->error);
                }
                $istmt->close();
                
                // Insert TRANSFER_OUT movement (decrease from source warehouse)
                // TRANSFER_IN will be handled by a different module when transfer is received
                $this->insertStockMovement(
                    $product_id,
                    $sku,
                    $item_code,
                    $from_warehouse,
                    $location,
                    $size,
                    $color,
                    'TRANSFER_OUT',
                    $transfer_qty,
                    $created_by,
                    'TRANSFER_ORDER',
                    'Transfer out to warehouse: ' . $to_warehouse,
                    $transfer_order_no
                );
            }
            
            // Commit transaction
            $this->db->commit();
            
            return [
                'success' => true,
                'message' => 'Stock transfer created successfully',
                'transfer_order_no' => $transfer_order_no
            ];
            
        } catch (Exception $e) {
            // Rollback on error
            $this->db->rollback();
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Insert stock movement record with running_stock calculation
     * @param int $product_id
     * @param string $sku
     * @param string $item_code
     * @param int $warehouse_id
     * @param string $location
     * @param string $size
     * @param string $color
     * @param string $movement_type
     * @param int $quantity (positive number, not negative)
     * @param int $user_id
     * @param string $ref_type
     * @param string $reason
     * @return bool
     */
    private function insertStockMovement($product_id, $sku, $item_code, $warehouse_id, $location, $size, $color, $movement_type, $quantity, $user_id, $ref_type, $reason, $ref_id = '')
    {
        // Get last running_stock for this specific SKU and warehouse
        $lastStockQuery = "SELECT running_stock FROM vp_stock_movements 
                           WHERE sku = ? AND warehouse_id = ? 
                           ORDER BY id DESC LIMIT 1";
        
        $lstmt = $this->db->prepare($lastStockQuery);
        if (!$lstmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        
        $lstmt->bind_param('si', $sku, $warehouse_id);
        $lstmt->execute();
        $result = $lstmt->get_result();
        $lastRow = $result->fetch_assoc();
        $lstmt->close();
        
        // If no warehouse-specific history, fallback to last SKU-based history for overall running stock sanity check.
        if (!$lastRow) {
            $fallbackSql = "SELECT running_stock, warehouse_id FROM vp_stock_movements WHERE sku = ? ORDER BY id DESC LIMIT 1";
            $fallbackStmt = $this->db->prepare($fallbackSql);
            if ($fallbackStmt) {
                $fallbackStmt->bind_param('s', $sku);
                $fallbackStmt->execute();
                $fallbackRes = $fallbackStmt->get_result();
                $fallbackRow = $fallbackRes ? $fallbackRes->fetch_assoc() : null;
                $fallbackStmt->close();

                if ($fallbackRow) {
                    $lastRow = $fallbackRow;
                }
            }
        }

        $lastRunningStock = $lastRow ? (int)$lastRow['running_stock'] : 0;

        // Fallback: if you're receiving inventory and the global SKU stock is higher than this warehouse, use global stock.
        $globalStock = 0;
        $globalSql = "SELECT running_stock FROM vp_stock_movements WHERE sku = ? ORDER BY id DESC LIMIT 1";
        $globalStmt = $this->db->prepare($globalSql);
        if ($globalStmt) {
            $globalStmt->bind_param('s', $sku);
            $globalStmt->execute();
            $globalRes = $globalStmt->get_result();
            $globalRow = $globalRes ? $globalRes->fetch_assoc() : null;
            $globalStmt->close();
            if ($globalRow) {
                $globalStock = (int)$globalRow['running_stock'];
            }
        }

        if ($movement_type === 'TRANSFER_IN' && $globalStock > $lastRunningStock) {
            $lastRunningStock = $globalStock;
        }

        $currentStock = $lastRunningStock;
        $productExists = false;
        $prodStmt = $this->db->prepare("SELECT local_stock FROM vp_products WHERE id = ?");
        if ($prodStmt) {
            $prodStmt->bind_param('i', $product_id);
            $prodStmt->execute();
            $prodRes = $prodStmt->get_result();
            $prodRow = $prodRes->fetch_assoc();
            if ($prodRow) {
                $productExists = true;
                $currentStock = (int)$prodRow['local_stock'];
            }
            $prodStmt->close();
        }

        if ($lastRow) {
            // Consistent chaining from the last movement in this SKU+warehouse context
            if ($movement_type === 'TRANSFER_OUT') {
                $runningStock = $lastRunningStock - $quantity;
            } else {
                $runningStock = $lastRunningStock + $quantity;
            }
        } else {
            // Fallback to product local_stock (or zero) when no movement history exists for this SKU/warehouse
            $base = $productExists ? $currentStock : 0;
            if ($movement_type === 'TRANSFER_OUT') {
                $runningStock = $base - $quantity;
            } else {
                $runningStock = $base + $quantity;
            }
        }

        $insertMovementSQL = "INSERT INTO vp_stock_movements 
            (product_id, sku, item_code, size, color, warehouse_id, location, movement_type, quantity, running_stock, update_by_user, ref_type, ref_id, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($insertMovementSQL);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        
        $stmt->bind_param('issssissiiisss', $product_id, $sku, $item_code, $size, $color, $warehouse_id, $location, $movement_type, $quantity, $runningStock, $user_id, $ref_type, $ref_id, $reason);
        if (!$stmt->execute()) {
            throw new Exception('Execute error: ' . $stmt->error);
        }
        $stmt->close();

        // If product exists, keep local_stock in sync
        if ($productExists) {
            $updateSql = "UPDATE vp_products SET local_stock = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            if ($updateStmt) {
                $updateStmt->bind_param('ii', $runningStock, $product_id);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }

        return true;
    }

    /**
     * Opening stock line from product bulk import (requires OPENING_STOCK in vp_stock_movements.movement_type enum).
     */
    public function recordOpeningStockFromBulkImport(
        int $product_id,
        string $sku,
        string $item_code,
        string $size,
        string $color,
        int $warehouse_id,
        string $location,
        int $quantity,
        int $user_id,
        string $ref_id
    ) {
        return $this->insertStockMovement(
            $product_id,
            $sku,
            $item_code,
            $warehouse_id,
            $location,
            $size,
            $color,
            'OPENING_STOCK',
            $quantity,
            $user_id,
            'BULK_IMPORT',
            'Opening stock from product bulk import',
            $ref_id
        );
    }

    /**
     * Remove bulk-import opening movement for this import row (if any) and fix subsequent running_stock.
     */
    public function removeBulkImportOpeningByRef(string $ref_id): bool
    {
        $existing = $this->fetchBulkImportMovementByRef($ref_id);
        if (!$existing) {
            return true;
        }
        $pid = (int)($existing['product_id'] ?? 0);
        $this->deleteMovementAndAdjustSubsequent((int)$existing['id']);
        if ($pid > 0) {
            $this->syncProductLocalStockFromLatestMovement($pid);
        }

        return true;
    }

    /**
     * Insert or update the single BULK_IMPORT opening row for a bulk import item (same ref_id).
     * Use when the product already exists and the same import line is re-run with new qty/location.
     */
    public function upsertBulkImportOpeningStock(
        int $product_id,
        string $sku,
        string $item_code,
        string $size,
        string $color,
        int $warehouse_id,
        string $location,
        int $quantity,
        int $user_id,
        string $ref_id
    ) {
        $loc = trim($location) === '' ? '-' : trim($location);
        $existing = $this->fetchBulkImportMovementByRef($ref_id);
        if (!$existing) {
            return $this->recordOpeningStockFromBulkImport(
                $product_id,
                $sku,
                $item_code,
                $size,
                $color,
                $warehouse_id,
                $loc,
                $quantity,
                $user_id,
                $ref_id
            );
        }

        $movId = (int)$existing['id'];
        $oldWh = (int)$existing['warehouse_id'];
        $oldQty = (int)$existing['quantity'];
        $skuRow = (string)$existing['sku'];
        $newWh = (int)$warehouse_id;

        if ($oldWh !== $newWh) {
            $this->deleteMovementAndAdjustSubsequent($movId);
            $this->syncProductLocalStockFromLatestMovement($product_id);

            return $this->recordOpeningStockFromBulkImport(
                $product_id,
                $sku,
                $item_code,
                $size,
                $color,
                $newWh,
                $loc,
                $quantity,
                $user_id,
                $ref_id
            );
        }

        $delta = $quantity - $oldQty;
        $oldLoc = (string)($existing['location'] ?? '');

        if ($delta !== 0 || $loc !== $oldLoc) {
            $upd = $this->db->prepare(
                'UPDATE vp_stock_movements SET quantity = ?, location = ?, running_stock = running_stock + ?, update_by_user = ? WHERE id = ?'
            );
            if (!$upd) {
                throw new Exception('Prepare error: ' . $this->db->error);
            }
            $upd->bind_param('isiii', $quantity, $loc, $delta, $user_id, $movId);
            if (!$upd->execute()) {
                throw new Exception('Execute error: ' . $upd->error);
            }
            $upd->close();

            if ($delta !== 0) {
                $sub = $this->db->prepare(
                    'UPDATE vp_stock_movements SET running_stock = running_stock + ? WHERE sku = ? AND warehouse_id = ? AND id > ?'
                );
                if (!$sub) {
                    throw new Exception('Prepare error: ' . $this->db->error);
                }
                $sub->bind_param('isii', $delta, $skuRow, $newWh, $movId);
                if (!$sub->execute()) {
                    throw new Exception('Execute error: ' . $sub->error);
                }
                $sub->close();
            }
        }

        $this->syncProductLocalStockFromLatestMovement($product_id);

        return true;
    }

    private function fetchBulkImportMovementByRef(string $ref_id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT id, product_id, sku, warehouse_id, quantity, running_stock, movement_type, location
             FROM vp_stock_movements
             WHERE ref_type = 'BULK_IMPORT' AND ref_id = ?
             LIMIT 1"
        );
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('s', $ref_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return $row ?: null;
    }

    /**
     * Delete one movement row and align running_stock for all later rows for the same sku + warehouse.
     */
    private function deleteMovementAndAdjustSubsequent(int $movementId): void
    {
        $stmt = $this->db->prepare(
            'SELECT id, sku, warehouse_id, quantity, movement_type FROM vp_stock_movements WHERE id = ? LIMIT 1'
        );
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        $stmt->bind_param('i', $movementId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        if (!$row) {
            return;
        }

        $type = (string)($row['movement_type'] ?? '');
        $qty = (int)($row['quantity'] ?? 0);
        $sku = (string)($row['sku'] ?? '');
        $wh = (int)($row['warehouse_id'] ?? 0);

        $deltaForLater = ($type === 'TRANSFER_OUT') ? $qty : -$qty;

        if ($sku !== '' && $wh >= 0) {
            $u = $this->db->prepare(
                'UPDATE vp_stock_movements SET running_stock = running_stock + ? WHERE sku = ? AND warehouse_id = ? AND id > ?'
            );
            if ($u) {
                $u->bind_param('isii', $deltaForLater, $sku, $wh, $movementId);
                $u->execute();
                $u->close();
            }
        }

        $d = $this->db->prepare('DELETE FROM vp_stock_movements WHERE id = ? LIMIT 1');
        if (!$d) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        $d->bind_param('i', $movementId);
        if (!$d->execute()) {
            throw new Exception('Execute error: ' . $d->error);
        }
        $d->close();
    }

    private function syncProductLocalStockFromLatestMovement(int $product_id): void
    {
        $last = 0;
        $stmt = $this->db->prepare(
            'SELECT running_stock FROM vp_stock_movements WHERE product_id = ? ORDER BY id DESC LIMIT 1'
        );
        if ($stmt) {
            $stmt->bind_param('i', $product_id);
            $stmt->execute();
            $res = $stmt->get_result();
            $r = $res ? $res->fetch_assoc() : null;
            $stmt->close();
            if ($r) {
                $last = (int)($r['running_stock'] ?? 0);
            }
        }
        $up = $this->db->prepare('UPDATE vp_products SET local_stock = ? WHERE id = ?');
        if ($up) {
            $up->bind_param('ii', $last, $product_id);
            $up->execute();
            $up->close();
        }
    }

    /**
     * Ensure GRN related tables exist before inserting.
     * This avoids "table does not exist" errors on first use.
     */
    private function ensureGrnTablesExist()
    {
        // Ensure the stock transfer GRN table has the expected columns, and recreate it if not.
        $requiredColumns = ['transfer_id', 'transfer_order_no', 'sku', 'item_code', 'qty_received'];
        $badColumns = ['po_id', 'po_number'];
        $this->repairGrnTableSchema('vp_stock_transfer_grns', $requiredColumns, $badColumns);

        $queries = [
            "CREATE TABLE IF NOT EXISTS `vp_stock_transfer_grns` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `transfer_id` INT NOT NULL,
              `transfer_order_no` VARCHAR(255) NOT NULL,
              `sku` VARCHAR(255) DEFAULT NULL,
              `item_code` VARCHAR(255) DEFAULT NULL,
              `size` VARCHAR(255) DEFAULT NULL,
              `color` VARCHAR(255) DEFAULT NULL,
              `transfer_qty` INT DEFAULT 0,
              `qty_received` INT DEFAULT 0,
              `qty_acceptable` TINYINT(1) DEFAULT 0,
              `remarks` TEXT,
              `location` INT DEFAULT NULL,
              `received_by` INT DEFAULT NULL,
              `received_date` DATE DEFAULT NULL,
              `created_by` INT DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              INDEX (`transfer_id`),
              INDEX (`transfer_order_no`),
              INDEX (`sku`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `vp_stock_transfer_grns_file` (
              `id` INT AUTO_INCREMENT PRIMARY KEY,
              `grn_id` INT NOT NULL,
              `file_path` VARCHAR(1024) NOT NULL,
              `original_name` VARCHAR(255) DEFAULT NULL,
              `mime_type` VARCHAR(255) DEFAULT NULL,
              `file_size` BIGINT DEFAULT 0,
              `created_by` INT DEFAULT NULL,
              `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
              INDEX (`grn_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $query) {
            $this->db->query($query);
        }
    }

    private function renameLegacyTable(string $tableName)
    {
        $res = $this->db->query("SHOW TABLES LIKE '$tableName'");
        if ($res && $res->num_rows > 0) {
            $backup = $tableName . '_removed_' . time();
            $this->db->query("RENAME TABLE `$tableName` TO `$backup`");
        }
    }

    /**
     * If table uses old PO-based columns, move it aside (backup) so we can recreate correct schema.
     * This avoids losing data in case the old schema is needed for reference.
     */
    private function repairGrnTableSchema(string $tableName, array $requiredColumns = [], array $badColumns = [])
    {
        // If the table does not exist, nothing to do.
        $res = $this->db->query("SHOW TABLES LIKE '$tableName'");
        if (!$res || $res->num_rows === 0) {
            return;
        }

        $needsBackup = false;

        // If any bad columns exist, we need to backup and recreate.
        foreach ($badColumns as $col) {
            $res = $this->db->query("SHOW COLUMNS FROM `$tableName` LIKE '$col'");
            if ($res && $res->num_rows > 0) {
                $needsBackup = true;
                break;
            }
        }

        // If required columns are missing, also backup.
        if (!$needsBackup && !empty($requiredColumns)) {
            foreach ($requiredColumns as $col) {
                $res = $this->db->query("SHOW COLUMNS FROM `$tableName` LIKE '$col'");
                if (!$res || $res->num_rows === 0) {
                    $needsBackup = true;
                    break;
                }
            }
        }

        if ($needsBackup) {
            $backupName = $tableName . '_bak_' . time();
            $this->db->query("RENAME TABLE `$tableName` TO `$backupName`");
        }
    }

    private function saveGrnFiles($grnId, $files, $userId)
    {
        if (!$files || !isset($files['tmp_name'])) {
            return;
        }

        $basePath = realpath(__DIR__ . '/../../');
        $uploadDir = $basePath . '/uploads/grn_files';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Normalize to multi-file format
        if (!is_array($files['tmp_name'])) {
            $files = [
                'name' => [$files['name']],
                'type' => [$files['type']],
                'tmp_name' => [$files['tmp_name']],
                'error' => [$files['error']],
                'size' => [$files['size']],
            ];
        }

        $insertSql = "INSERT INTO vp_stock_transfer_grns_file (grn_id, file_path, original_name, mime_type, file_size, created_by) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertSql);

        foreach ($files['tmp_name'] as $index => $tmpName) {
            if (empty($tmpName) || !is_uploaded_file($tmpName) || ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $originalName = basename($files['name'][$index] ?? '');
            $mimeType = $files['type'][$index] ?? '';
            $size = intval($files['size'][$index] ?? 0);

            $maxGrnBytes = 2 * 1024 * 1024; // 2 MiB
            if ($size > $maxGrnBytes) {
                throw new Exception('GRN attachment too large (max 2 MB): ' . $originalName);
            }

            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $allowedExt = ['pdf', 'png', 'jpg', 'jpeg'];
            if (!in_array($ext, $allowedExt, true)) {
                throw new Exception('Invalid GRN attachment type. Only PDF, PNG, and JPG are allowed: ' . $originalName);
            }

            $safeBase = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($originalName, PATHINFO_FILENAME));
            $extension = pathinfo($originalName, PATHINFO_EXTENSION);
            $fileName = sprintf('grn_%d_%d_%s', $grnId, time(), $safeBase);
            if ($extension) {
                $fileName .= '.' . $extension;
            }

            $targetPath = $uploadDir . '/' . $fileName;
            if (!move_uploaded_file($tmpName, $targetPath)) {
                throw new Exception('Failed to move uploaded file: ' . $originalName);
            }

            $relativePath = 'uploads/grn_files/' . $fileName;

            if ($stmt) {
                $stmt->bind_param('isssii', $grnId, $relativePath, $originalName, $mimeType, $size, $userId);
                $stmt->execute();
            }
        }

        if ($stmt) {
            $stmt->close();
        }
    }

    /**
     * Generate unique transfer order number
     * Format: TO-sourceId-destId-XXXX (where XXXX is an incrementing counter)
     * @param int $from_warehouse Source warehouse ID
     * @param int $to_warehouse Destination warehouse ID
     * @return string Unique transfer order number
     */
    private function generateUniqueTransferOrderNo($from_warehouse, $to_warehouse)
    {
        $basePrefix = 'TO-' . $from_warehouse . '-' . $to_warehouse . '-';
        
        // Get the last transfer order number with this prefix
        $query = "SELECT transfer_order_no FROM vp_stock_transfer 
                  WHERE transfer_order_no LIKE ? 
                  ORDER BY transfer_order_no DESC 
                  LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        
        $searchPattern = $basePrefix . '%';
        $stmt->bind_param('s', $searchPattern);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        // Extract counter from last TO number and increment
        $counter = 1;
        if ($row && !empty($row['transfer_order_no'])) {
            $lastTO = $row['transfer_order_no'];
            // Extract the counter from the last TO number
            if (preg_match('/-(\d+)$/', $lastTO, $matches)) {
                $counter = (int)$matches[1] + 1;
            }
        }
        
        // Format counter with leading zeros
        return $basePrefix . str_pad($counter, 4, '0', STR_PAD_LEFT);
      }  

    /**
     * Get next unique transfer order number (public API wrapper)
     * @param int $from_warehouse
     * @param int $to_warehouse
     * @return string
     */
    public function getNextTransferOrderNo($from_warehouse, $to_warehouse)
    {
        return $this->generateUniqueTransferOrderNo((int)$from_warehouse, (int)$to_warehouse);
    }

    /**
     * Validate that transfer quantity doesn't exceed available stock
     * @param string $sku Product SKU
     * @param int $warehouse_id Warehouse ID
     * @param int $transfer_qty Transfer quantity requested
     * @return array ['valid' => bool, 'available' => int, 'message' => string]
     */
    public function validateItemStock($sku, $warehouse_id, $transfer_qty, $existingTransferQty = 0)
    {
        $stockQuery = "SELECT running_stock FROM vp_stock_movements 
                      WHERE sku = ? AND warehouse_id = ? 
                      ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->db->prepare($stockQuery);
        if (!$stmt) {
            return ['valid' => false, 'available' => 0, 'message' => 'Database error: ' . $this->db->error];
        }
        
        $stmt->bind_param('si', $sku, $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stockRow = $result->fetch_assoc();
        $stmt->close();
        
        // If we don't have any movement record yet, fall back to current product stock.
        $availableStock = 0;
        if ($stockRow && isset($stockRow['running_stock'])) {
            $availableStock = (int)$stockRow['running_stock'];
        } else {
            // If no stock movement exists, use product local stock as initial baseline.
            $prodStmt = $this->db->prepare("SELECT local_stock FROM vp_products WHERE sku = ? LIMIT 1");
            if ($prodStmt) {
                $prodStmt->bind_param('s', $sku);
                $prodStmt->execute();
                $prodResult = $prodStmt->get_result();
                $prodRow = $prodResult->fetch_assoc();
                $prodStmt->close();
                $availableStock = $prodRow ? (int)$prodRow['local_stock'] : 0;
            }
        }

        // When editing an existing transfer, the current transfer qty may already be deducted from stock.
        // Add it back when validating the updated requested quantity.
        if ($existingTransferQty > 0) {
            $availableStock += $existingTransferQty;
        }

        if ($transfer_qty > $availableStock) {
            return [
                'valid' => false,
                'available' => $availableStock,
                'message' => "Transfer quantity ($transfer_qty) for SKU $sku exceeds available stock ($availableStock)"
            ];
        }
        
        return ['valid' => true, 'available' => $availableStock, 'message' => ''];
    }
    
    /**
     * Get last warehouse from stock movements
     * @return int|null Warehouse ID or null if none found
     */
    public function getLastWarehouse()
    {
        $query = "SELECT DISTINCT warehouse_id FROM vp_stock_movements 
                  ORDER BY id DESC LIMIT 1";
        
        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return null;
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row ? (int)$row['warehouse_id'] : null;
    }

    /**
     * Get last received movement quantity for a SKU at a warehouse (TRANSFER_IN or IN)
     * @param string $sku
     * @param int $warehouse_id
     * @return int
     */
    public function getLastReceivedQty($sku, $warehouse_id)
    {
        $query = "SELECT quantity FROM vp_stock_movements 
                  WHERE sku = ? AND warehouse_id = ? AND movement_type IN ('TRANSFER_IN','IN') 
                  ORDER BY id DESC LIMIT 1";

        $stmt = $this->db->prepare($query);
        if (!$stmt) {
            return 0;
        }

        $stmt->bind_param('si', $sku, $warehouse_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        return $row ? (int)$row['quantity'] : 0;
    }

    /**
     * Get paginated list of stock transfers. Line items are **not** loaded; use
     * line_item_count, line_preview_skus (up to 3 labels), line_total_qty from the JOIN.
     * Full lines: {@see getTransferItemsPaginated()}.
     *
     * @param int $limit
     * @param int $offset
     * @return array{records: list<array>, total: int}
     */
    public function listTransfers($limit = 50, $offset = 0, $filters = [])
    {
        // Build where clause from filters
        $whereClauses = [];
        $params = [];
        $types = '';

        if (!empty($filters['transfer_order_no'])) {
            $whereClauses[] = 't.transfer_order_no LIKE ?';
            $params[] = '%' . $filters['transfer_order_no'] . '%';
            $types .= 's';
        }
        if (!empty($filters['dispatch_date'])) {
            $whereClauses[] = 't.dispatch_date = ?';
            $params[] = $filters['dispatch_date'];
            $types .= 's';
        }
        if (!empty($filters['requested_by'])) {
            $whereClauses[] = 't.requested_by = ?';
            $params[] = (int)$filters['requested_by'];
            $types .= 'i';
        }
        if (!empty($filters['dispatch_by'])) {
            $whereClauses[] = 't.dispatch_by = ?';
            $params[] = (int)$filters['dispatch_by'];
            $types .= 'i';
        }
        if (!empty($filters['from_warehouse'])) {
            $whereClauses[] = 't.from_warehouse = ?';
            $params[] = (int)$filters['from_warehouse'];
            $types .= 'i';
        }
        if (!empty($filters['to_warehouse'])) {
            $whereClauses[] = 't.to_warehouse = ?';
            $params[] = (int)$filters['to_warehouse'];
            $types .= 'i';
        }
        $itemFilter = !empty($filters['item_number']) ? trim($filters['item_number']) : '';
        $itemExistsClause = '';
        if ($itemFilter !== '') {
            $itemExistsClause = "EXISTS (SELECT 1 FROM vp_item_stock_transfer i WHERE i.transfer_order_no = t.transfer_order_no AND (i.item_code LIKE ? OR i.sku LIKE ?))";
            $params[] = '%' . $itemFilter . '%';
            $params[] = '%' . $itemFilter . '%';
            $types .= 'ss';
        }

        if ($itemExistsClause !== '') {
            $whereClauses[] = $itemExistsClause;
        }

        $where = '';
        if (!empty($whereClauses)) {
            $where = ' WHERE ' . implode(' AND ', $whereClauses);
        }

        // Total count for pagination
        $countSql = "SELECT COUNT(*) AS total FROM vp_stock_transfer t" . $where;
        $countStmt = $this->db->prepare($countSql);
        if (!$countStmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }

        if (!empty($params)) {
            $bindCountParams = array_merge([$types], $params);
            $refs = [];
            foreach ($bindCountParams as $key => $value) {
                $refs[$key] = &$bindCountParams[$key];
            }
            call_user_func_array([$countStmt, 'bind_param'], $refs);
        }

        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $countStmt->close();
        $total = (int)($totalRow['total'] ?? 0);

        // Aggregate line-item summary only (no full line fetch — keeps list fast for large transfers)
        $sql = "SELECT t.*, 
                       f.address_title AS source_name, 
                       d.address_title AS dest_name,
                       ru.name AS requested_by_name,
                       du.name AS dispatch_by_name,
                       COALESCE(line_agg.line_item_count, 0) AS line_item_count,
                       COALESCE(line_agg.line_total_qty, 0) AS line_total_qty,
                       line_agg.line_preview_raw AS line_preview_raw
                FROM vp_stock_transfer t
                LEFT JOIN exotic_address f ON f.id = t.from_warehouse
                LEFT JOIN exotic_address d ON d.id = t.to_warehouse
                LEFT JOIN vp_users ru ON ru.id = t.requested_by
                LEFT JOIN vp_users du ON du.id = t.dispatch_by
                LEFT JOIN (
                    SELECT 
                        i.transfer_order_no,
                        COUNT(*) AS line_item_count,
                        SUM(COALESCE(i.transfer_qty, 0)) AS line_total_qty,
                        SUBSTRING_INDEX(
                            GROUP_CONCAT(
                                COALESCE(
                                    NULLIF(TRIM(i.sku), ''),
                                    NULLIF(TRIM(i.item_code), ''),
                                    CONCAT('#', i.id)
                                )
                                ORDER BY i.id ASC
                                SEPARATOR '||'
                            ),
                            '||',
                            3
                        ) AS line_preview_raw
                    FROM vp_item_stock_transfer i
                    GROUP BY i.transfer_order_no
                ) line_agg ON line_agg.transfer_order_no = t.transfer_order_no
                " . $where . "
                ORDER BY t.id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }

        // Bind params for main query
        if (!empty($params)) {
            $bindParams = $params;
            $bindTypes = $types;
        } else {
            $bindParams = [];
            $bindTypes = '';
        }
        $bindTypes .= 'ii';
        $bindParams[] = $limit;
        $bindParams[] = $offset;

        $bindArray = array_merge([$bindTypes], $bindParams);
        $refs = [];
        foreach ($bindArray as $key => $value) {
            $refs[$key] = &$bindArray[$key];
        }
        call_user_func_array([$stmt, 'bind_param'], $refs);

        $stmt->execute();
        $result = $stmt->get_result();
        $transfers = [];

        while ($row = $result->fetch_assoc()) {
            $row['items'] = [];
            $row['line_item_count'] = (int)($row['line_item_count'] ?? 0);
            $row['line_total_qty'] = (int)($row['line_total_qty'] ?? 0);
            $previewRaw = isset($row['line_preview_raw']) ? (string)$row['line_preview_raw'] : '';
            $row['line_preview_skus'] = $previewRaw !== ''
                ? array_values(array_filter(explode('||', $previewRaw), static fn ($s) => $s !== ''))
                : [];
            $transfers[] = $row;
        }
        $stmt->close();

        $records = $transfers;

        return ['records' => $records, 'total' => $total];
    }

    /**
     * Paginated line items for one transfer (by vp_stock_transfer.id). Used by the line-items detail page.
     *
     * @return array{rows: list<array<string,mixed>>, total: int, transfer: array|null} rows include qty_received_total, qty_acceptable_total (sums from vp_stock_transfer_grns)
     */
    public function getTransferItemsPaginated(int $transferId, int $limit = 50, int $offset = 0): array
    {
        $transferId = max(0, $transferId);
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $headerSql = "SELECT t.id, t.transfer_order_no, t.dispatch_date, t.status,
                             f.address_title AS source_name, d.address_title AS dest_name
                      FROM vp_stock_transfer t
                      LEFT JOIN exotic_address f ON f.id = t.from_warehouse
                      LEFT JOIN exotic_address d ON d.id = t.to_warehouse
                      WHERE t.id = ?
                      LIMIT 1";
        $hStmt = $this->db->prepare($headerSql);
        if (!$hStmt) {
            return ['rows' => [], 'total' => 0, 'transfer' => null];
        }
        $hStmt->bind_param('i', $transferId);
        $hStmt->execute();
        $hRes = $hStmt->get_result();
        $transfer = $hRes ? $hRes->fetch_assoc() : null;
        $hStmt->close();

        if (!$transfer || empty($transfer['transfer_order_no'])) {
            return ['rows' => [], 'total' => 0, 'transfer' => null];
        }

        $countSql = "SELECT COUNT(*) AS c FROM vp_item_stock_transfer WHERE transfer_order_no = ?";
        $cStmt = $this->db->prepare($countSql);
        if (!$cStmt) {
            return ['rows' => [], 'total' => 0, 'transfer' => $transfer];
        }
        $orderNo = $transfer['transfer_order_no'];
        $cStmt->bind_param('s', $orderNo);
        $cStmt->execute();
        $cRes = $cStmt->get_result();
        $totalRow = $cRes ? $cRes->fetch_assoc() : ['c' => 0];
        $cStmt->close();
        $total = (int)($totalRow['c'] ?? 0);

        // GRN aggregates per transfer line (same SKU/item_code matching as qty_received)
        $grnLineMatch = '( (NULLIF(TRIM(IFNULL(i.sku, \'\')), \'\') IS NOT NULL AND g.sku = i.sku)
            OR (NULLIF(TRIM(IFNULL(i.sku, \'\')), \'\') IS NULL
                AND NULLIF(TRIM(IFNULL(i.item_code, \'\')), \'\') IS NOT NULL
                AND g.item_code = i.item_code) )';
        $sql = "SELECT i.id, i.product_id, i.sku, i.item_code, i.transfer_qty, i.item_notes,
                COALESCE((
                    SELECT SUM(g.qty_received)
                    FROM vp_stock_transfer_grns g
                    WHERE g.transfer_id = ?
                      AND $grnLineMatch
                ), 0) AS qty_received_total,
                COALESCE((
                    SELECT SUM(g.qty_acceptable)
                    FROM vp_stock_transfer_grns g
                    WHERE g.transfer_id = ?
                      AND $grnLineMatch
                ), 0) AS qty_acceptable_total
                FROM vp_item_stock_transfer i
                WHERE i.transfer_order_no = ?
                ORDER BY i.id ASC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return ['rows' => [], 'total' => $total, 'transfer' => $transfer];
        }
        $stmt->bind_param('iisii', $transferId, $transferId, $orderNo, $limit, $offset);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($r = $res->fetch_assoc()) {
                $rows[] = $r;
            }
        }
        $stmt->close();

        return ['rows' => $rows, 'total' => $total, 'transfer' => $transfer];
    }

    /**
     * Receipt progress for all transfer lines vs GRNs (same SKU/item_code matching as line-items page).
     *
     * @return string 'empty' | 'none' | 'partial' | 'full'
     */
    public function getTransferReceiptStatus(int $transferId): string
    {
        $transferId = max(0, $transferId);
        if ($transferId <= 0) {
            return 'empty';
        }

        $hdr = $this->db->prepare('SELECT transfer_order_no FROM vp_stock_transfer WHERE id = ? LIMIT 1');
        if (!$hdr) {
            return 'empty';
        }
        $hdr->bind_param('i', $transferId);
        $hdr->execute();
        $hres = $hdr->get_result();
        $hrow = $hres ? $hres->fetch_assoc() : null;
        $hdr->close();
        if (!$hrow || empty($hrow['transfer_order_no'])) {
            return 'empty';
        }
        $orderNo = $hrow['transfer_order_no'];

        $grnLineMatchI = '( (NULLIF(TRIM(IFNULL(i.sku, \'\')), \'\') IS NOT NULL AND g.sku = i.sku)
            OR (NULLIF(TRIM(IFNULL(i.sku, \'\')), \'\') IS NULL
                AND NULLIF(TRIM(IFNULL(i.item_code, \'\')), \'\') IS NOT NULL
                AND g.item_code = i.item_code) )';

        $grnLineMatchI2 = '( (NULLIF(TRIM(IFNULL(i2.sku, \'\')), \'\') IS NOT NULL AND g2.sku = i2.sku)
            OR (NULLIF(TRIM(IFNULL(i2.sku, \'\')), \'\') IS NULL
                AND NULLIF(TRIM(IFNULL(i2.item_code, \'\')), \'\') IS NOT NULL
                AND g2.item_code = i2.item_code) )';

        $sql = "SELECT
                    (SELECT COUNT(*) FROM vp_item_stock_transfer i0 WHERE i0.transfer_order_no = ?) AS total_lines,
                    (SELECT COUNT(*) FROM vp_item_stock_transfer i
                     WHERE i.transfer_order_no = ?
                       AND COALESCE((
                           SELECT SUM(g.qty_received)
                           FROM vp_stock_transfer_grns g
                           WHERE g.transfer_id = ?
                             AND $grnLineMatchI
                       ), 0) < i.transfer_qty
                    ) AS incomplete_lines,
                    (SELECT COUNT(*) FROM vp_item_stock_transfer i2
                     WHERE i2.transfer_order_no = ?
                       AND COALESCE((
                           SELECT SUM(g2.qty_received)
                           FROM vp_stock_transfer_grns g2
                           WHERE g2.transfer_id = ?
                             AND $grnLineMatchI2
                       ), 0) > 0
                    ) AS lines_with_receipt";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 'empty';
        }
        $stmt->bind_param('ssisi', $orderNo, $orderNo, $transferId, $orderNo, $transferId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        $totalLines = (int) ($row['total_lines'] ?? 0);
        $incomplete = (int) ($row['incomplete_lines'] ?? 0);
        $linesWithReceipt = (int) ($row['lines_with_receipt'] ?? 0);

        if ($totalLines === 0) {
            return 'empty';
        }
        if ($incomplete === 0) {
            return 'full';
        }
        if ($linesWithReceipt > 0) {
            return 'partial';
        }

        return 'none';
    }

    /**
     * True when this transfer has at least one line and every line has SUM(GRN qty_received) >= transfer_qty.
     */
    public function isTransferFullyReceived(int $transferId): bool
    {
        return $this->getTransferReceiptStatus($transferId) === 'full';
    }

    /**
     * Get list of GRN rows for a specific stock transfer (or all if $transferId=0)
     *
     * @param int $transferId
     * @return array
     */
    public function listTransferGrns($transferId = 0)
    {
        $transferId = (int)$transferId;
        $sql = "SELECT g.*, t.transfer_order_no, w.address_title AS location_name, u.name AS received_by_name
                FROM vp_stock_transfer_grns g
                LEFT JOIN vp_stock_transfer t ON t.id = g.transfer_id
                LEFT JOIN exotic_address w ON w.id = g.location
                LEFT JOIN vp_users u ON u.id = g.received_by";

        if ($transferId > 0) {
            $sql .= " WHERE g.transfer_id = ?";
        }

        $sql .= " ORDER BY g.received_date DESC, g.created_at DESC";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        if ($transferId > 0) {
            $stmt->bind_param('i', $transferId);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $rows;
    }

    public function getTransferGrnById($grnId)
    {
        $grnId = (int)$grnId;
        if ($grnId <= 0) {
            return null;
        }

        $sql = "SELECT * FROM vp_stock_transfer_grns WHERE id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return null;
        }

        $stmt->bind_param('i', $grnId);
        $stmt->execute();
        $result = $stmt->get_result();
        $grn = $result ? $result->fetch_assoc() : null;
        $stmt->close();

        return $grn;
    }

    public function getTransferGrnGroup($transferId, $receivedDate, $receivedBy)
    {
        $transferId = (int)$transferId;
        $receivedBy = (int)$receivedBy;

        $sql = "SELECT * FROM vp_stock_transfer_grns WHERE transfer_id = ? AND received_date = ? AND received_by = ? ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }

        $stmt->bind_param('isi', $transferId, $receivedDate, $receivedBy);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        return $rows;
    }

    public function getReceivedQtyForTransferSku($transferId, $sku, $excludeGrnId = 0)
    {
        $transferId = (int)$transferId;
        $sku = trim($sku);
        $excludeGrnId = (int)$excludeGrnId;

        if ($transferId <= 0 || $sku === '') {
            return 0;
        }

        $sql = "SELECT SUM(qty_received) AS total_received FROM vp_stock_transfer_grns WHERE transfer_id = ? AND sku = ?";
        if ($excludeGrnId > 0) {
            $sql .= " AND id != ?";
        }

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return 0;
        }

        if ($excludeGrnId > 0) {
            $stmt->bind_param('isi', $transferId, $sku, $excludeGrnId);
        } else {
            $stmt->bind_param('is', $transferId, $sku);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        return isset($row['total_received']) ? (int)$row['total_received'] : 0;
    }

    public function updateTransferGrn($grnId, $data)
    {
        $grnId = (int)$grnId;
        if ($grnId <= 0) {
            return false;
        }

        $existingGrn = $this->getTransferGrnById($grnId);
        if (!$existingGrn) {
            return false;
        }

        $qtyReceived = isset($data['qty_received']) ? (int)$data['qty_received'] : (int)$existingGrn['qty_received'];
        $qtyAcceptable = isset($data['qty_acceptable']) ? (int)$data['qty_acceptable'] : (int)$existingGrn['qty_acceptable'];
        $receivedDate = $data['received_date'] ?? ($existingGrn['received_date'] ?? date('Y-m-d'));
        $remarks = trim($data['remarks'] ?? $existingGrn['remarks'] ?? '');

        $alreadyReceived = $this->getReceivedQtyForTransferSku($existingGrn['transfer_id'], $existingGrn['sku'], $grnId);
        $maxAllowed = max(0, (int)$existingGrn['transfer_qty'] - $alreadyReceived);

        if ($qtyReceived > $maxAllowed) {
            $qtyReceived = $maxAllowed;
        }

        if ($qtyAcceptable > $qtyReceived) {
            $qtyAcceptable = $qtyReceived;
        }

        $sql = "UPDATE vp_stock_transfer_grns SET qty_received = ?, qty_acceptable = ?, received_date = ?, remarks = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('iissi', $qtyReceived, $qtyAcceptable, $receivedDate, $remarks, $grnId);
        $result = $stmt->execute();
        $stmt->close();

        if ($result && $qtyReceived !== (int)$existingGrn['qty_received']) {
            // Adjust linked stock movement for this GRN row.
            $this->adjustGrnStockMovement($grnId, $qtyReceived);
        }

        return (bool)$result;
    }

    public function adjustGrnStockMovement($grnId, $newQty)
    {
        $grnId = (int)$grnId;
        if ($grnId <= 0) {
            return false;
        }

        $grn = $this->getTransferGrnById($grnId);
        if (!$grn) {
            return false;
        }

        $oldQty = (int)$grn['qty_received'];
        $delta = $newQty - $oldQty;
        if ($delta === 0) {
            return true;
        }

        $targetMovement = null;
        $sql = "SELECT id, quantity, running_stock, sku, warehouse_id FROM vp_stock_movements WHERE ref_type = 'GRN' AND ref_id = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $grnId);
            $stmt->execute();
            $res = $stmt->get_result();
            $targetMovement = $res ? $res->fetch_assoc() : null;
            $stmt->close();
        }

        if (!$targetMovement) {
            // fallback: maybe older records used transfer_order_no as ref_id
            $sql = "SELECT id, quantity, running_stock, sku, warehouse_id FROM vp_stock_movements WHERE ref_type = 'GRN' AND ref_id = ? ORDER BY id DESC LIMIT 1";
            $stmt = $this->db->prepare($sql);
            if ($stmt) {
                $refFallback = $grn['transfer_order_no'] ?? '';
                $stmt->bind_param('s', $refFallback);
                $stmt->execute();
                $res = $stmt->get_result();
                $targetMovement = $res ? $res->fetch_assoc() : null;
                $stmt->close();
            }
        }

        if (!$targetMovement) {
            return false;
        }

        $targetId = (int)$targetMovement['id'];
        $newRunningStock = (int)$targetMovement['running_stock'] + $delta;

        $updateSql = "UPDATE vp_stock_movements SET quantity = ?, running_stock = ? WHERE id = ?";
        $updateStmt = $this->db->prepare($updateSql);
        if ($updateStmt) {
            $updateStmt->bind_param('iii', $newQty, $newRunningStock, $targetId);
            $updateStmt->execute();
            $updateStmt->close();
        }

        $propagateSql = "UPDATE vp_stock_movements SET running_stock = running_stock + ? WHERE sku = ? AND warehouse_id = ? AND id > ?";
        $propagateStmt = $this->db->prepare($propagateSql);
        if ($propagateStmt) {
            $propagateStmt->bind_param('isii', $delta, $targetMovement['sku'], $targetMovement['warehouse_id'], $targetId);
            $propagateStmt->execute();
            $propagateStmt->close();
        }

        if (!empty($targetMovement['sku'])) {
            $this->syncProductLocalStock($targetMovement['sku'], (int)($grn['product_id'] ?? 0));
        }

        return true;
    }

    public function deleteTransferGrn($grnId)
    {
        $grnId = (int)$grnId;
        if ($grnId <= 0) {
            return false;
        }

        $sql = "DELETE FROM vp_stock_transfer_grns WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('i', $grnId);
        $result = $stmt->execute();
        $stmt->close();

        return (bool)$result;
    }

    /**
     * Get a single transfer record (with items) by its ID.
     *
     * @param int $transferId
     * @return array|null
     */
    public function getTransferById($transferId)
    {
        $sql = "SELECT t.*, 
                       f.address_title AS source_name, 
                       d.address_title AS dest_name,
                       ru.name AS requested_by_name,
                       du.name AS dispatch_by_name
                FROM vp_stock_transfer t
                LEFT JOIN exotic_address f ON f.id = t.from_warehouse
                LEFT JOIN exotic_address d ON d.id = t.to_warehouse
                LEFT JOIN vp_users ru ON ru.id = t.requested_by
                LEFT JOIN vp_users du ON du.id = t.dispatch_by
                WHERE t.id = ?
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        $stmt->bind_param('i', $transferId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transfer = $result->fetch_assoc();
        $stmt->close();

        if (!$transfer) {
            return null;
        }

        // fetch items
        $itemSql = "SELECT id, transfer_order_no, product_id, sku, item_code, transfer_qty, item_notes FROM vp_item_stock_transfer WHERE transfer_order_no = ? ORDER BY id ASC";
        $itemStmt = $this->db->prepare($itemSql);
        if ($itemStmt) {
            $itemStmt->bind_param('s', $transfer['transfer_order_no']);
            $itemStmt->execute();
            $itemResult = $itemStmt->get_result();
            $items = [];
            while ($itemRow = $itemResult->fetch_assoc()) {
                $items[] = $itemRow;
            }
            $itemStmt->close();

            // Enrich each item with product details (for display in the GRN view)
            $productStmt = $this->db->prepare("SELECT image, title, local_stock, product_weight, product_weight_unit, prod_height, prod_width, prod_length, length_unit, material FROM vp_products WHERE sku = ? LIMIT 1");
            foreach ($items as &$itemRow) {
                $itemRow['product'] = null;
                $sku = trim($itemRow['sku'] ?? '');
                if ($sku && $productStmt) {
                    $productStmt->bind_param('s', $sku);
                    $productStmt->execute();
                    $prodRes = $productStmt->get_result();
                    $prodRow = $prodRes->fetch_assoc();
                    if ($prodRow) {
                        $itemRow['product'] = $prodRow;
                    }
                }
            }
            if ($productStmt) {
                $productStmt->close();
            }

            $transfer['items'] = $items;
        } else {
            $transfer['items'] = [];
        }

        return $transfer;
    }

    /**
     * Create a GRN record for a stock transfer and apply stock movements for received items.
     *
     * @param array $data
     * @return array
     */
    public function updateTransfer($transferId, $data)
    {
        $transferId = (int)$transferId;
        if ($transferId <= 0) {
            return false;
        }

        $fields = [];
        $params = [];
        $types = '';

        $allowed = [
            'dispatch_date',
            'est_delivery_date',
            'from_warehouse',
            'to_warehouse',
            'requested_by',
            'dispatch_by',
            'booking_no',
            'vehicle_no',
            'vehicle_type',
            'driver_name',
            'driver_mobile',
            'eway_bill_file',
            'status'
        ];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                // choose type based on field
                if (in_array($field, ['from_warehouse', 'to_warehouse', 'requested_by', 'dispatch_by'])) {
                    $types .= 'i';
                } else {
                    $types .= 's';
                }
            }
        }

        if (empty($fields)) {
            return false;
        }

        $sql = "UPDATE vp_stock_transfer SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return false;
        }

        $types .= 'i';
        $params[] = $transferId;

        $bindNames = [];
        $bindNames[] = &$types;
        foreach ($params as $key => $value) {
            $bindNames[] = &$params[$key];
        }

        call_user_func_array([$stmt, 'bind_param'], $bindNames);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    public function replaceTransferItems($transferOrderNo, $items)
    {
        if (empty($transferOrderNo) || !is_array($items)) {
            return false;
        }

        // Remove existing items for this transfer
        $deleteSql = "DELETE FROM vp_item_stock_transfer WHERE transfer_order_no = ?";
        $deleteStmt = $this->db->prepare($deleteSql);
        if ($deleteStmt) {
            $deleteStmt->bind_param('s', $transferOrderNo);
            $deleteStmt->execute();
            $deleteStmt->close();
        }

        // Insert updated items
        $insertItemSql = "INSERT INTO vp_item_stock_transfer (transfer_order_no, product_id, item_code, sku, title, transfer_qty, item_notes) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $istmt = $this->db->prepare($insertItemSql);
        if (!$istmt) {
            return false;
        }

        foreach ($items as $item) {
            $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
            $itemCode = trim($item['item_code'] ?? '');
            $sku = trim($item['sku'] ?? '');
            $transferQty = isset($item['transfer_qty']) ? (int)$item['transfer_qty'] : 0;
            $title = trim($item['title'] ?? '');
            $itemNotes = trim($item['item_notes'] ?? '');

            if ($transferQty <= 0) {
                continue;
            }

            // Resolve product from id / sku / item_code+size+color when needed.
            if ($productId <= 0 || $sku === '') {
                $resolved = $this->resolveProductForTransferItem([
                    'product_id' => $productId,
                    'sku' => $sku,
                    'item_code' => $itemCode,
                    'size' => trim((string)($item['size'] ?? '')),
                    'color' => trim((string)($item['color'] ?? '')),
                ]);
                if ($resolved) {
                    $productId = (int)$resolved['id'];
                    $sku = $resolved['sku'] ?: $sku;
                    if ($itemCode === '') {
                        $itemCode = $resolved['item_code'] ?? $itemCode;
                    }
                    if ($title === '') {
                        $title = $resolved['title'] ?? $title;
                    }
                }
            }

            // Skip items we cannot link to a product (to avoid FK errors)
            if ($productId <= 0) {
                continue;
            }

            $istmt->bind_param('sisssis', $transferOrderNo, $productId, $itemCode, $sku, $title, $transferQty, $itemNotes);
            $istmt->execute();
        }

        $istmt->close();
        return true;
    }

    /**
     * Synchronize transfer OUT stock movements to match the current transfer item quantities.
     *
     * @param string $transferOrderNo
     * @param int $fromWarehouse
     * @param array $items
     * @param int $userId
     * @return bool
     */
    public function syncTransferOutMovements($transferOrderNo, $fromWarehouse, $items, $userId)
    {
        if (empty($transferOrderNo) || $fromWarehouse <= 0) {
            return false;
        }

        $this->db->begin_transaction();
        try {
            // Get existing transfer-out movements (ordered by insertion) for the transfer.
            $existingSql = "SELECT id, product_id, sku, quantity, running_stock FROM vp_stock_movements 
                            WHERE ref_type = 'TRANSFER_ORDER' AND ref_id = ? AND movement_type = 'TRANSFER_OUT' AND warehouse_id = ?
                            ORDER BY id ASC";
            $existingStmt = $this->db->prepare($existingSql);
            $existingMovements = [];
            if ($existingStmt) {
                $existingStmt->bind_param('si', $transferOrderNo, $fromWarehouse);
                $existingStmt->execute();
                $res = $existingStmt->get_result();
                while ($row = $res->fetch_assoc()) {
                    $key = trim($row['sku'] ?? '') . '|' . (int)$row['product_id'];
                    $existingMovements[$key] = $row;
                }
                $existingStmt->close();
            }

            foreach ($items as $item) {
                $transferQty = isset($item['transfer_qty']) ? (int)$item['transfer_qty'] : 0;
                if ($transferQty <= 0) {
                    continue;
                }

                $sku = trim($item['sku'] ?? '');
                if ($sku === '') {
                    continue;
                }

                // Resolve product_id if missing.
                $productId = isset($item['product_id']) ? (int)$item['product_id'] : 0;
                if ($productId <= 0) {
                    $prodStmt = $this->db->prepare("SELECT id FROM vp_products WHERE sku = ? LIMIT 1");
                    if ($prodStmt) {
                        $prodStmt->bind_param('s', $sku);
                        $prodStmt->execute();
                        $prodRes = $prodStmt->get_result();
                        $prodRow = $prodRes->fetch_assoc();
                        $prodStmt->close();
                        if ($prodRow) {
                            $productId = (int)$prodRow['id'];
                        }
                    }
                }

                if ($productId <= 0) {
                    continue;
                }

                $key = $sku . '|' . $productId;
                if (isset($existingMovements[$key])) {
                    $existing = $existingMovements[$key];
                    $oldQty = (int)$existing['quantity'];
                    $oldRunningStock = (int)$existing['running_stock'];
                    $delta = $transferQty - $oldQty; // positive = more out

                    if ($delta !== 0) {
                        // Update this movement row quantity + running_stock
                        $newRunningStock = $oldRunningStock - $delta;
                        $updateSql = "UPDATE vp_stock_movements SET quantity = ?, running_stock = ? WHERE id = ?";
                        $updateStmt = $this->db->prepare($updateSql);
                        if ($updateStmt) {
                            $updateStmt->bind_param('iii', $transferQty, $newRunningStock, $existing['id']);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }

                        // Propagate running_stock adjustment to later movements for the same SKU/warehouse
                        $adjust = -$delta; // later rows should shift by this amount
                        if ($adjust !== 0) {
                            $propagateSql = "UPDATE vp_stock_movements SET running_stock = running_stock + ? 
                                               WHERE sku = ? AND warehouse_id = ? AND id > ?";
                            $propStmt = $this->db->prepare($propagateSql);
                            if ($propStmt) {
                                $propStmt->bind_param('isii', $adjust, $sku, $fromWarehouse, $existing['id']);
                                $propStmt->execute();
                                $propStmt->close();
                            }
                        }

                        // Keep product local_stock in sync (use latest running_stock for this SKU)
                        $this->syncProductLocalStock($sku, $productId);
                    }

                    // Remove processed item so we don't insert it again
                    unset($existingMovements[$key]);
                } else {
                    // New item: insert a new movement
                    // Re-use insertStockMovement which handles running_stock and local_stock
                    $productQuery = "SELECT title, location, size, color FROM vp_products WHERE id = ? LIMIT 1";
                    $pstmt = $this->db->prepare($productQuery);
                    if ($pstmt) {
                        $pstmt->bind_param('i', $productId);
                        $pstmt->execute();
                        $product = $pstmt->get_result()->fetch_assoc();
                        $pstmt->close();
                    } else {
                        $product = null;
                    }

                    $itemCode = trim($item['item_code'] ?? '');
                    $location = $product['location'] ?? '';
                    $size = $product['size'] ?? '';
                    $color = $product['color'] ?? '';

                    $this->insertStockMovement(
                        $productId,
                        $sku,
                        $itemCode,
                        $fromWarehouse,
                        $location,
                        $size,
                        $color,
                        'TRANSFER_OUT',
                        $transferQty,
                        $userId,
                        'TRANSFER_ORDER',
                        'Updated transfer out to warehouse: ' . $fromWarehouse,
                        $transferOrderNo
                    );
                }
            }

            // If there are any leftover existing movements that are no longer part of the transfer, reduce their impact.
            // We keep them for audit but zero their quantity and adjust downstream running_stock.
            foreach ($existingMovements as $leftover) {
                $leftoverId = (int)$leftover['id'];
                $leftoverQty = (int)$leftover['quantity'];
                if ($leftoverQty === 0) {
                    continue;
                }

                $updateSql = "UPDATE vp_stock_movements SET quantity = 0, running_stock = running_stock + ? WHERE id = ?";
                $updateStmt = $this->db->prepare($updateSql);
                if ($updateStmt) {
                    $delta = $leftoverQty; // removing this outgoing amount means stock increases by leftoverQty
                    $updateStmt->bind_param('ii', $delta, $leftoverId);
                    $updateStmt->execute();
                    $updateStmt->close();
                }

                $propagateSql = "UPDATE vp_stock_movements SET running_stock = running_stock + ? WHERE sku = ? AND warehouse_id = ? AND id > ?";
                $propStmt = $this->db->prepare($propagateSql);
                if ($propStmt) {
                    $propStmt->bind_param('isii', $delta, $leftover['sku'], $fromWarehouse, $leftoverId);
                    $propStmt->execute();
                    $propStmt->close();
                }

                $this->syncProductLocalStock($leftover['sku'], (int)$leftover['product_id']);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            return false;
        }
    }

    /**
     * Update product local_stock based on the latest movement for this SKU.
     */
    private function syncProductLocalStock(string $sku, int $productId)
    {
        $stockQuery = "SELECT running_stock FROM vp_stock_movements WHERE sku = ? ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($stockQuery);
        if (!$stmt) {
            return;
        }
        $stmt->bind_param('s', $sku);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res->fetch_assoc();
        $stmt->close();

        if ($row && isset($row['running_stock'])) {
            $updateProductSql = "UPDATE vp_products SET local_stock = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateProductSql);
            if ($updateStmt) {
                $runningStock = (int)$row['running_stock'];
                $updateStmt->bind_param('ii', $runningStock, $productId);
                $updateStmt->execute();
                $updateStmt->close();
            }
        }
    }

    public function createTransferGrn($data)
    {
        $transferId = isset($data['transfer_id']) ? (int)$data['transfer_id'] : 0;
        $receivedBy = isset($data['received_by']) ? (int)$data['received_by'] : 0;
        $receivedDate = isset($data['received_date']) ? $data['received_date'] : date('Y-m-d');
        $remarks = isset($data['remarks']) ? trim($data['remarks']) : '';
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
        $userId = isset($data['user_id']) ? (int)$data['user_id'] : $receivedBy;

        if ($transferId <= 0) {
            return ['success' => false, 'message' => 'Invalid transfer id'];
        }

        $transfer = $this->getTransferById($transferId);
        if (!$transfer) {
            return ['success' => false, 'message' => 'Stock transfer not found'];
        }

        if (empty($items)) {
            return ['success' => false, 'message' => 'No items to receive'];
        }

        // Ensure we have at least one item with received quantity > 0
        $hasReceived = false;
        foreach ($items as $item) {
            if (isset($item['received_qty']) && (int)$item['received_qty'] > 0) {
                $hasReceived = true;
                break;
            }
        }
        if (!$hasReceived) {
            return ['success' => false, 'message' => 'Please enter received quantity for at least one item'];
        }

        $destinationWarehouse = (int)($transfer['to_warehouse'] ?? 0);
        if ($destinationWarehouse <= 0 && isset($data['warehouse_id']) && (int)$data['warehouse_id'] > 0) {
            $destinationWarehouse = (int)$data['warehouse_id'];
        }

        $transferOrderNo = $transfer['transfer_order_no'];

        // Ensure required GRN tables exist in the database
        $this->ensureGrnTablesExist();

        try {
            $this->db->begin_transaction();

            // Insert one row per received item into the flat GRN table
            $insertItemSql = "INSERT INTO vp_stock_transfer_grns (transfer_id, transfer_order_no, sku, item_code, size, color, transfer_qty, qty_received, qty_acceptable, remarks, location, received_by, received_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $itemStmt = $this->db->prepare($insertItemSql);
            if (!$itemStmt) {
                throw new Exception('Prepare error: ' . $this->db->error);
            }

            $firstGrnId = null;
            foreach ($items as $item) {
                $transferItemId = isset($item['transfer_item_id']) ? (int)$item['transfer_item_id'] : 0;
                $sku = trim($item['sku'] ?? '');
                $itemCode = trim($item['item_code'] ?? '');
                $transferQty = isset($item['transfer_qty']) ? (int)$item['transfer_qty'] : 0;
                $receivedQty = isset($item['received_qty']) ? (int)$item['received_qty'] : 0;

                // Ensure we do not receive more than transferred; enforce max to prevent accidental over-add.
                $alreadyReceived = $this->getReceivedQtyForTransferSku($transferId, $sku);
                $remainingQty = max(0, $transferQty - $alreadyReceived);

                if ($receivedQty > $remainingQty) {
                    $this->db->rollback();
                    return [
                        'success' => false,
                        'message' => "Received quantity for SKU '{$sku}' exceeds remaining quantity ({$remainingQty} / {$transferQty})."
                    ];
                }

                if ($receivedQty > $transferQty) {
                    $receivedQty = $transferQty;
                }

                $qtyAcceptable = isset($item['qty_acceptable']) ? (int)$item['qty_acceptable'] : 0;
                if ($qtyAcceptable > $receivedQty) {
                    $qtyAcceptable = $receivedQty;
                }

                $accepted = isset($item['acceptable']) && $item['acceptable'];
                $acceptableQty = $accepted ? $qtyAcceptable : 0;
                $itemRemarks = trim($item['remarks'] ?? '');

                // If SKU is missing, fallback to item_code so we can still create movement records
                if ($sku === '' && $itemCode !== '') {
                    $sku = $itemCode;
                }

                // Skip items with no received quantity
                if ($receivedQty <= 0) {
                    continue;
                }

                // Try to fill size/color from product details (if present)
                $size = '';
                $color = '';
                if ($sku !== '') {
                    $prodStmt = $this->db->prepare('SELECT size, color FROM vp_products WHERE sku = ? LIMIT 1');
                    if ($prodStmt) {
                        $prodStmt->bind_param('s', $sku);
                        $prodStmt->execute();
                        $prodRes = $prodStmt->get_result();
                        $prodRow = $prodRes->fetch_assoc();
                        if ($prodRow) {
                            $size = $prodRow['size'] ?? '';
                            $color = $prodRow['color'] ?? '';
                        }
                        $prodStmt->close();
                    }
                }

                $itemStmt->bind_param(
                    'isssssiiisiisi',
                    $transferId,
                    $transferOrderNo,
                    $sku,
                    $itemCode,
                    $size,
                    $color,
                    $transferQty,
                    $receivedQty,
                    $acceptableQty,
                    $itemRemarks,
                    $destinationWarehouse,
                    $receivedBy,
                    $receivedDate,
                    $userId
                );
                if (!$itemStmt->execute()) {
                    throw new Exception('Execute error: ' . $itemStmt->error);
                }

                $currentGrnId = $this->db->insert_id;
                if ($firstGrnId === null) {
                    $firstGrnId = $currentGrnId;
                }

                // Determine product id (prefer transfer item product id)
                $productId = 0;
                if ($transferItemId) {
                    $prodStmt = $this->db->prepare('SELECT product_id FROM vp_item_stock_transfer WHERE id = ? LIMIT 1');
                    if ($prodStmt) {
                        $prodStmt->bind_param('i', $transferItemId);
                        $prodStmt->execute();
                        $prodRes = $prodStmt->get_result();
                        $prodRow = $prodRes->fetch_assoc();
                        if ($prodRow) {
                            $productId = (int)$prodRow['product_id'];
                        }
                        $prodStmt->close();
                    }
                }

                if ($productId === 0 && $sku !== '') {
                    $prodStmt = $this->db->prepare('SELECT id FROM vp_products WHERE sku = ? LIMIT 1');
                    if ($prodStmt) {
                        $prodStmt->bind_param('s', $sku);
                        $prodStmt->execute();
                        $prodRes = $prodStmt->get_result();
                        $prodRow = $prodRes->fetch_assoc();
                        if ($prodRow) {
                            $productId = (int)$prodRow['id'];
                        }
                        $prodStmt->close();
                    }
                }

                if ($productId === 0 && $itemCode !== '') {
                    $prodStmt = $this->db->prepare('SELECT id FROM vp_products WHERE item_code = ? LIMIT 1');
                    if ($prodStmt) {
                        $prodStmt->bind_param('s', $itemCode);
                        $prodStmt->execute();
                        $prodRes = $prodStmt->get_result();
                        $prodRow = $prodRes->fetch_assoc();
                        if ($prodRow) {
                            $productId = (int)$prodRow['id'];
                        }
                        $prodStmt->close();
                    }
                }

                // Insert transfer in movement - add to destination warehouse
                $this->insertStockMovement(
                    $productId,
                    $sku,
                    $itemCode,
                    $destinationWarehouse,
                    '', // location
                    $size,
                    $color,
                    'TRANSFER_IN',
                    $receivedQty,
                    $userId,
                    'GRN',
                    'Received from stock transfer: ' . $transferOrderNo,
                    $currentGrnId
                );
            }

            $itemStmt->close();

            // Save uploaded GRN files (if any) against first inserted row
            if (isset($data['files']) && $firstGrnId) {
                $this->saveGrnFiles($firstGrnId, $data['files'], $userId);
            }

            // Update transfer status
            $updateSql = "UPDATE vp_stock_transfer SET status = ? WHERE id = ?";
            $updateStmt = $this->db->prepare($updateSql);
            if ($updateStmt) {
                $status = 'received';
                $updateStmt->bind_param('si', $status, $transferId);
                $updateStmt->execute();
                $updateStmt->close();
            }

            $this->db->commit();

            return ['success' => true, 'message' => 'GRN created successfully', 'grn_id' => $firstGrnId];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

