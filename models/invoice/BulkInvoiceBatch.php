<?php

class BulkInvoiceBatch
{
    private $db;

    public function __construct($conn)
    {
        $this->db = $conn;
        $this->ensureTablesExist();
    }

    private function ensureTablesExist(): void
    {
        if (!$this->db) return;

        $sql1 = "CREATE TABLE IF NOT EXISTS `vp_invoice_bulk_batches` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `batch_no` VARCHAR(64) NOT NULL UNIQUE,
            `total_customers` INT NOT NULL DEFAULT 0,
            `total_orders` INT NOT NULL DEFAULT 0,
            `total_items` INT NOT NULL DEFAULT 0,
            `processed_customers` INT NOT NULL DEFAULT 0,
            `success_count` INT NOT NULL DEFAULT 0,
            `error_count` INT NOT NULL DEFAULT 0,
            `status` ENUM('pending', 'processing', 'completed', 'partially_completed', 'failed') DEFAULT 'pending',
            `created_by` INT NOT NULL DEFAULT 0,
            `started_at` DATETIME NULL,
            `completed_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX (`status`),
            INDEX (`batch_no`),
            INDEX (`created_by`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        $sql2 = "CREATE TABLE IF NOT EXISTS `vp_invoice_bulk_batch_items` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `batch_id` INT NOT NULL,
            `customer_id` INT NOT NULL,
            `customer_name` VARCHAR(255) NULL,
            `order_item_ids` TEXT NOT NULL,
            `order_numbers` TEXT NULL,
            `status` ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
            `invoice_id` INT NULL,
            `invoice_number` VARCHAR(100) NULL,
            `invoice_amount` DECIMAL(12,2) DEFAULT 0.00,
            `error_message` TEXT NULL,
            `processed_at` DATETIME NULL,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX (`batch_id`, `status`),
            INDEX (`customer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

        @$this->db->query($sql1);
        @$this->db->query($sql2);
    }

    /**
     * Create a new bulk invoice batch job and item rows.
     *
     * @param array $batchHeader Metadata for main batch record
     * @param array $customerGroups Array of customer_id => ['name' => string, 'item_ids' => array, 'order_numbers' => array]
     * @return int Created batch_id
     */
    public function createBatch(array $batchHeader, array $customerGroups): int
    {
        $batchNo = (string)($batchHeader['batch_no'] ?? ('INV-BATCH-' . date('YmdHis') . '-' . mt_rand(1000, 9999)));
        $totalCustomers = count($customerGroups);
        $totalOrders = (int)($batchHeader['total_orders'] ?? 0);
        $totalItems = (int)($batchHeader['total_items'] ?? 0);
        $createdBy = (int)($batchHeader['created_by'] ?? 0);

        $stmt = $this->db->prepare("INSERT INTO vp_invoice_bulk_batches (batch_no, total_customers, total_orders, total_items, status, created_by, created_at) VALUES (?, ?, ?, ?, 'pending', ?, NOW())");
        if (!$stmt) {
            throw new Exception("Failed to prepare batch insert: " . $this->db->error);
        }

        $stmt->bind_param("siiii", $batchNo, $totalCustomers, $totalOrders, $totalItems, $createdBy);
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute batch insert: " . $stmt->error);
        }

        $batchId = (int)$stmt->insert_id;
        $stmt->close();

        $itemStmt = $this->db->prepare("INSERT INTO vp_invoice_bulk_batch_items (batch_id, customer_id, customer_name, order_item_ids, order_numbers, status, created_at) VALUES (?, ?, ?, ?, ?, 'pending', NOW())");
        if (!$itemStmt) {
            throw new Exception("Failed to prepare batch item insert: " . $this->db->error);
        }

        foreach ($customerGroups as $customerId => $group) {
            $custName = (string)($group['name'] ?? ('Customer #' . $customerId));
            $itemIdsJson = json_encode(array_values(array_map('intval', $group['item_ids'] ?? [])));
            $orderNosJson = json_encode(array_values(array_unique(array_filter($group['order_numbers'] ?? []))));

            $itemStmt->bind_param("iisss", $batchId, $customerId, $custName, $itemIdsJson, $orderNosJson);
            $itemStmt->execute();
        }

        $itemStmt->close();
        return $batchId;
    }

    public function getBatchById(int $batchId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_invoice_bulk_batches WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    public function getBatchByNo(string $batchNo): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_invoice_bulk_batches WHERE batch_no = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param("s", $batchNo);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    public function getBatchItems(int $batchId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_invoice_bulk_batch_items WHERE batch_id = ? ORDER BY id ASC");
        if (!$stmt) return [];
        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        $res = $stmt->get_result();
        $items = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $items[] = $row;
            }
        }
        $stmt->close();
        return $items;
    }

    public function getItemById(int $itemId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM vp_invoice_bulk_batch_items WHERE id = ? LIMIT 1");
        if (!$stmt) return null;
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    /**
     * Get next pending item for processing (or retry stuck item).
     * Uses atomic UPDATE to prevent concurrent worker duplication.
     */
    public function lockNextPendingItem(int $batchId = 0): ?array
    {
        $batchWhere = $batchId > 0 ? "AND batch_id = " . (int)$batchId . " " : "";

        // 1. Fetch candidate pending item IDs
        $candRes = $this->db->query("SELECT id FROM vp_invoice_bulk_batch_items WHERE status = 'pending' {$batchWhere} ORDER BY id ASC LIMIT 10");
        if ($candRes && $candRes->num_rows > 0) {
            while ($cRow = $candRes->fetch_assoc()) {
                $candId = (int)$cRow['id'];
                $upSql = "UPDATE vp_invoice_bulk_batch_items SET status = 'processing', processed_at = NOW() WHERE id = {$candId} AND status = 'pending'";
                @$this->db->query($upSql);
                if ($this->db->affected_rows > 0) {
                    $item = $this->getItemById($candId);
                    if ($item) {
                        $bId = (int)$item['batch_id'];
                        $bUp = $this->db->prepare("UPDATE vp_invoice_bulk_batches SET status = 'processing', started_at = COALESCE(started_at, NOW()) WHERE id = ? AND status = 'pending'");
                        if ($bUp) {
                            $bUp->bind_param("i", $bId);
                            $bUp->execute();
                            $bUp->close();
                        }
                    }
                    return $item;
                }
            }
        }

        // 2. Retry stuck 'processing' items older than 30 seconds
        $stuckRes = $this->db->query("SELECT id FROM vp_invoice_bulk_batch_items WHERE status = 'processing' AND (processed_at IS NULL OR processed_at < NOW() - INTERVAL 30 SECOND) {$batchWhere} ORDER BY id ASC LIMIT 10");
        if ($stuckRes && $stuckRes->num_rows > 0) {
            while ($sRow = $stuckRes->fetch_assoc()) {
                $stuckId = (int)$sRow['id'];
                $upSql = "UPDATE vp_invoice_bulk_batch_items SET processed_at = NOW() WHERE id = {$stuckId} AND status = 'processing' AND (processed_at IS NULL OR processed_at < NOW() - INTERVAL 30 SECOND)";
                @$this->db->query($upSql);
                if ($this->db->affected_rows > 0) {
                    $item = $this->getItemById($stuckId);
                    if ($item) {
                        $bId = (int)$item['batch_id'];
                        $bUp = $this->db->prepare("UPDATE vp_invoice_bulk_batches SET status = 'processing', started_at = COALESCE(started_at, NOW()) WHERE id = ? AND status = 'pending'");
                        if ($bUp) {
                            $bUp->bind_param("i", $bId);
                            $bUp->execute();
                            $bUp->close();
                        }
                    }
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Execute invoice creation for a single batch item.
     */
    public function processItem(array $item, $creationService, $ordersModel): array
    {
        $itemId = (int)$item['id'];
        $batchId = (int)$item['batch_id'];
        $customerId = (int)$item['customer_id'];
        $orderItemIds = json_decode((string)($item['order_item_ids'] ?? '[]'), true);

        if (!is_array($orderItemIds) || empty($orderItemIds)) {
            $this->markItemFailed($itemId, "No order item IDs provided for customer #{$customerId}");
            return ['success' => false, 'item_id' => $itemId, 'message' => "No order item IDs provided"];
        }

        try {
            $orderLines = $ordersModel->getOrdersByIds($orderItemIds);
            if (empty($orderLines)) {
                $this->markItemFailed($itemId, "Order lines not found for IDs: " . implode(', ', $orderItemIds));
                return ['success' => false, 'item_id' => $itemId, 'message' => "Order lines not found"];
            }

            // Expand orderLines to ensure ALL items for the order numbers are included
            $orderNos = [];
            $allLinesMap = [];
            foreach ($orderLines as $ol) {
                $lid = (int)($ol['id'] ?? 0);
                if ($lid > 0) $allLinesMap[$lid] = $ol;
                $ono = trim((string)($ol['order_number'] ?? ''));
                if ($ono !== '') $orderNos[$ono] = true;
            }

            foreach (array_keys($orderNos) as $ono) {
                $linesForNo = $ordersModel->getOrderByOrderNumber($ono);
                if (is_array($linesForNo)) {
                    foreach ($linesForNo as $lfn) {
                        $lid = (int)($lfn['id'] ?? 0);
                        if ($lid > 0 && !isset($allLinesMap[$lid])) {
                            $allLinesMap[$lid] = $lfn;
                        }
                    }
                }
            }
            $orderLines = array_values($allLinesMap);

            // Filter lines to keep only uninvoiced items & check active existing invoices
            $uninvoicedOrderLines = [];
            $firstActiveInvRow = null;

            foreach ($orderLines as $orderLine) {
                $existingInvId = (int)($orderLine['invoice_id'] ?? 0);
                if ($existingInvId > 0) {
                    $invRes = $this->db->query("SELECT id, invoice_number, total_amount, status FROM vp_invoices WHERE id = " . $existingInvId . " LIMIT 1");
                    if ($invRes && $invRow = $invRes->fetch_assoc()) {
                        if (strtolower(trim((string)$invRow['status'])) !== 'cancelled') {
                            if ($firstActiveInvRow === null) {
                                $firstActiveInvRow = $invRow;
                            }
                            continue; // Skip already-invoiced line item
                        }
                    }
                }
                $uninvoicedOrderLines[] = $orderLine;
            }

            if (empty($uninvoicedOrderLines)) {
                // All lines for this customer already have active invoices
                $invId = (int)($firstActiveInvRow['id'] ?? 0);
                $invNo = (string)($firstActiveInvRow['invoice_number'] ?? '');
                $invAmt = (float)($firstActiveInvRow['total_amount'] ?? 0.0);
                $this->markItemCompleted($itemId, $invId, $invNo, $invAmt);
                return [
                    'success' => true,
                    'item_id' => $itemId,
                    'invoice_id' => $invId,
                    'invoice_number' => $invNo,
                    'amount' => $invAmt
                ];
            }

            $batchHeader = $this->getBatchById($batchId);
            $batchNo = $batchHeader ? (string)$batchHeader['batch_no'] : '';

            $firstOrderNo = (string)($uninvoicedOrderLines[0]['order_number'] ?? '');
            $orderInfo = $ordersModel->getRemarksByOrderNumber($firstOrderNo);

            // Update customer_name in item row if generic or empty
            if (empty($item['customer_name']) || strpos($item['customer_name'], 'Customer #') === 0) {
                if (is_array($orderInfo)) {
                    $fullName = trim(($orderInfo['first_name'] ?? '') . ' ' . ($orderInfo['last_name'] ?? ''));
                    if ($fullName !== '') {
                        $upName = $this->db->prepare("UPDATE vp_invoice_bulk_batch_items SET customer_name = ? WHERE id = ?");
                        if ($upName) {
                            $upName->bind_param("si", $fullName, $itemId);
                            $upName->execute();
                            $upName->close();
                        }
                    }
                }
            }

            require_once __DIR__ . '/../../helpers/app_settings.php';
            require_once __DIR__ . '/../../helpers/invoice/invoice_gst.php';
            $useIgst = invoice_order_info_uses_igst(is_array($orderInfo) ? $orderInfo : null, app_setting_firm_details());
            $vpOrderInfoId = (is_array($orderInfo) && isset($orderInfo['id'])) ? (int)$orderInfo['id'] : 0;

            $headerOverrides = [
                'customer_id' => $customerId,
                'vp_order_info_id' => $vpOrderInfoId,
                'batch_no' => $batchNo,
                'created_by' => (int)($batchHeader['created_by'] ?? 0),
            ];

            require_once __DIR__ . '/../../helpers/invoice/InvoiceRequestBuilder.php';
            $request = InvoiceRequestBuilder::fromOrderLines(
                $uninvoicedOrderLines,
                $headerOverrides,
                [
                    'source' => 'bulk_background',
                    'use_igst' => $useIgst,
                    'duplicate_order_check' => false,
                    'update_order_invoice_id' => true,
                    'update_order_by' => 'vp_order_id',
                ]
            );

            $res = $creationService->create($request);

            if (!empty($res['success']) && !empty($res['invoice_id'])) {
                $invoiceId = (int)$res['invoice_id'];
                $invoiceNumber = (string)($res['invoice_number'] ?? '');
                $amount = (float)($res['total_amount'] ?? $request['header']['total_amount'] ?? 0.0);

                $this->markItemCompleted($itemId, $invoiceId, $invoiceNumber, $amount);
                return [
                    'success' => true,
                    'item_id' => $itemId,
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoiceNumber,
                    'amount' => $amount
                ];
            } else {
                $errMsg = (string)($res['message'] ?? 'Invoice creation failed with unknown error.');
                $this->markItemFailed($itemId, $errMsg);
                return ['success' => false, 'item_id' => $itemId, 'message' => $errMsg];
            }
        } catch (Throwable $e) {
            $this->markItemFailed($itemId, $e->getMessage());
            return ['success' => false, 'item_id' => $itemId, 'message' => $e->getMessage()];
        }
    }

    /**
     * Lock and process the next pending item for a given batch.
     */
    public function processNextPendingItem(int $batchId, $creationService, $ordersModel): ?array
    {
        $item = $this->lockNextPendingItem($batchId);
        if (!$item) {
            return null;
        }
        return $this->processItem($item, $creationService, $ordersModel);
    }

    public function markItemCompleted(int $itemId, int $invoiceId, string $invoiceNumber, float $amount = 0.00): void
    {
        $stmt = $this->db->prepare("UPDATE vp_invoice_bulk_batch_items SET status = 'completed', invoice_id = ?, invoice_number = ?, invoice_amount = ?, error_message = NULL, processed_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("isdi", $invoiceId, $invoiceNumber, $amount, $itemId);
            $stmt->execute();
            $stmt->close();
        }
        $this->syncBatchTotalsByItemId($itemId);
    }

    public function markItemFailed(int $itemId, string $errorMessage): void
    {
        $stmt = $this->db->prepare("UPDATE vp_invoice_bulk_batch_items SET status = 'failed', error_message = ?, processed_at = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("si", $errorMessage, $itemId);
            $stmt->execute();
            $stmt->close();
        }
        $this->syncBatchTotalsByItemId($itemId);
    }

    private function syncBatchTotalsByItemId(int $itemId): void
    {
        $res = $this->db->query("SELECT batch_id FROM vp_invoice_bulk_batch_items WHERE id = " . (int)$itemId . " LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            $this->updateBatchTotals((int)$row['batch_id']);
        }
    }

    public function updateBatchTotals(int $batchId): void
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as success_cnt,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as error_cnt,
                    SUM(CASE WHEN status IN ('completed', 'failed') THEN 1 ELSE 0 END) as processed_cnt,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_cnt,
                    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_cnt
                FROM vp_invoice_bulk_batch_items 
                WHERE batch_id = ?";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) return;

        $stmt->bind_param("i", $batchId);
        $stmt->execute();
        $counts = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$counts) return;

        $total = (int)$counts['total'];
        $success = (int)$counts['success_cnt'];
        $error = (int)$counts['error_cnt'];
        $processed = (int)$counts['processed_cnt'];
        $pending = (int)$counts['pending_cnt'];
        $processing = (int)$counts['processing_cnt'];

        $newBatchStatus = 'processing';
        if ($processed >= $total && $total > 0) {
            if ($error === 0) {
                $newBatchStatus = 'completed';
            } else if ($success === 0) {
                $newBatchStatus = 'failed';
            } else {
                $newBatchStatus = 'partially_completed';
            }
        } else if ($processed === 0 && $processing === 0) {
            $newBatchStatus = 'pending';
        }

        $completedAtClause = ($newBatchStatus !== 'processing' && $newBatchStatus !== 'pending') ? ", completed_at = COALESCE(completed_at, NOW())" : "";

        $up = $this->db->prepare("UPDATE vp_invoice_bulk_batches SET processed_customers = ?, success_count = ?, error_count = ?, status = ? {$completedAtClause} WHERE id = ?");
        if ($up) {
            $up->bind_param("iiisi", $processed, $success, $error, $newBatchStatus, $batchId);
            $up->execute();
            $up->close();
        }
    }

    public function resetFailedItem(int $itemId): bool
    {
        $stmt = $this->db->prepare("UPDATE vp_invoice_bulk_batch_items SET status = 'pending', error_message = NULL, processed_at = NULL WHERE id = ? AND status = 'failed'");
        if (!$stmt) return false;
        $stmt->bind_param("i", $itemId);
        $stmt->execute();
        $ok = ($stmt->affected_rows > 0);
        $stmt->close();

        if ($ok) {
            $this->syncBatchTotalsByItemId($itemId);
        }

        return $ok;
    }
}
