<?php
class StockTransfer
{
    private $db;
    
    public function __construct($db)
    {
        $this->db = $db;
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
                 create_pickup_list, create_picking_slip, create_delivery_challan, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
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
            $pickup_list = isset($data['create_pickup_list']) ? 1 : 0;
            $picking_slip = isset($data['create_picking_slip']) ? 1 : 0;
            $delivery_challan = isset($data['create_delivery_challan']) ? 1 : 0;
            $status_pending = 'pending';
            $created_by = (int)($data['user_id'] ?? 1);
            
            // Type string: s i i s s i i s s s s s i i i s i
            $stmt->bind_param(
                'siissiisssssiiisi',
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
                $sku = trim($item['sku'] ?? '');
                $transfer_qty = (int)($item['transfer_qty'] ?? 0);
                
                if ($transfer_qty <= 0) {
                    continue;
                }
                
                // Get product details by SKU (more specific than item_code)
                $productQuery = "SELECT id, sku, title, location, size, color FROM vp_products WHERE sku = ?";
                $pstmt = $this->db->prepare($productQuery);
                if (!$pstmt) {
                    throw new Exception('Prepare error: ' . $this->db->error);
                }
                $pstmt->bind_param('s', $sku);
                $pstmt->execute();
                $result = $pstmt->get_result();
                $product = $result->fetch_assoc();
                $pstmt->close();
                
                if (!$product) {
                    throw new Exception('Product not found for SKU: ' . $sku);
                }
                
                $product_id = $product['id'];
                $sku = $product['sku'];
                $title = $product['title'];
                $location = $product['location'] ?? '';
                $size = $product['size'] ?? '';
                $color = $product['color'] ?? '';
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
        
        // Calculate new running_stock
        // For TRANSFER_OUT: subtract the quantity
        // For other types: add the quantity
        $lastRunningStock = $lastRow ? (int)$lastRow['running_stock'] : 0;

        // Try to get current stock from vp_products if product exists
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

        if ($movement_type === 'TRANSFER_OUT') {
            $runningStock = $currentStock - $quantity;
        } else {
            $runningStock = $currentStock + $quantity;
        }

        // Prefer last running stock (from movements) if available, to stay consistent
        if ($lastRow) {
            if ($movement_type === 'TRANSFER_OUT') {
                $runningStock = $lastRunningStock - $quantity;
            } else {
                $runningStock = $lastRunningStock + $quantity;
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
     * Get paginated list of stock transfers with item details.
     *
     * @param int $limit
     * @param int $offset
     * @return array [
     *   'records' => array of transfers,
     *   'total' => int total transfer count
     * ]
     */
    public function listTransfers($limit = 50, $offset = 0)
    {
        // Total count for pagination
        $countSql = "SELECT COUNT(*) AS total FROM vp_stock_transfer";
        $countStmt = $this->db->prepare($countSql);
        if (!$countStmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $totalRow = $countResult->fetch_assoc();
        $countStmt->close();
        $total = (int)($totalRow['total'] ?? 0);

        // Fetch transfers with source/destination names and requester/dispatcher names
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
                ORDER BY t.id DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        $stmt->bind_param('ii', $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $transfers = [];
        $transferNos = [];

        while ($row = $result->fetch_assoc()) {
            $transfers[$row['transfer_order_no']] = $row;
            $transfers[$row['transfer_order_no']]['items'] = [];
            $transferNos[] = $row['transfer_order_no'];
        }
        $stmt->close();

        if (!empty($transferNos)) {
            // Fetch items for these transfers
            $placeholders = implode(',', array_fill(0, count($transferNos), '?'));
            $types = str_repeat('s', count($transferNos));
            $itemSql = "SELECT transfer_order_no, product_id, sku, item_code, transfer_qty, item_notes FROM vp_item_stock_transfer WHERE transfer_order_no IN ($placeholders) ORDER BY id ASC";
            $itemStmt = $this->db->prepare($itemSql);
            if ($itemStmt) {
                // bind_param requires references, so we build a reference array
                $bindParams = array_merge([$types], $transferNos);
                $refs = [];
                foreach ($bindParams as $key => $value) {
                    $refs[$key] = &$bindParams[$key];
                }
                call_user_func_array([$itemStmt, 'bind_param'], $refs);

                $itemStmt->execute();
                $itemResult = $itemStmt->get_result();
                while ($itemRow = $itemResult->fetch_assoc()) {
                    $orderNo = $itemRow['transfer_order_no'];
                    if (isset($transfers[$orderNo])) {
                        $transfers[$orderNo]['items'][] = $itemRow;
                    }
                }
                $itemStmt->close();
            }
        }

        // Convert to indexed array so view can loop easily
        $records = array_values($transfers);

        return ['records' => $records, 'total' => $total];
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

            // If product_id is missing, try to resolve it from SKU.
            if ($productId <= 0 && $sku !== '') {
                $prodStmt = $this->db->prepare("SELECT id, title FROM vp_products WHERE sku = ? LIMIT 1");
                if ($prodStmt) {
                    $prodStmt->bind_param('s', $sku);
                    $prodStmt->execute();
                    $prodRes = $prodStmt->get_result();
                    $prodRow = $prodRes->fetch_assoc();
                    $prodStmt->close();

                    if ($prodRow) {
                        $productId = (int)$prodRow['id'];
                        if (empty($title)) {
                            $title = $prodRow['title'] ?? $title;
                        }
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

        $destinationWarehouse = isset($data['warehouse_id']) && (int)$data['warehouse_id'] > 0 ? (int)$data['warehouse_id'] : (int)$transfer['to_warehouse'];
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
                $accepted = isset($item['acceptable']) && $item['acceptable'];
                $acceptableQty = $accepted ? $receivedQty : 0;
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

                if ($firstGrnId === null) {
                    $firstGrnId = $this->db->insert_id;
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
                    $transferOrderNo
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

