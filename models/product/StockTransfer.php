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
                    'Transfer out to warehouse: ' . $to_warehouse
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
    private function insertStockMovement($product_id, $sku, $item_code, $warehouse_id, $location, $size, $color, $movement_type, $quantity, $user_id, $ref_type, $reason)
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
        $lastRunningStock = $lastRow ? $lastRow['running_stock'] : 0;
        if ($movement_type === 'TRANSFER_OUT') {
            $runningStock = $lastRunningStock - $quantity;  // Subtract for outgoing
        } else {
            $runningStock = $lastRunningStock + $quantity;  // Add for incoming
        }
        
        $insertMovementSQL = "INSERT INTO vp_stock_movements 
            (product_id, sku, item_code, size, color, warehouse_id, location, movement_type, quantity, running_stock, update_by_user, ref_type, reason)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($insertMovementSQL);
        if (!$stmt) {
            throw new Exception('Prepare error: ' . $this->db->error);
        }
        
        $stmt->bind_param('issssissiiiss', $product_id, $sku, $item_code, $size, $color, $warehouse_id, $location, $movement_type, $quantity, $runningStock, $user_id, $ref_type, $reason);
        if (!$stmt->execute()) {
            throw new Exception('Execute error: ' . $stmt->error);
        }
        $stmt->close();
        
        return true;
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
    public function validateItemStock($sku, $warehouse_id, $transfer_qty)
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
        
        $availableStock = $stockRow ? (int)$stockRow['running_stock'] : 0;
        
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
}
