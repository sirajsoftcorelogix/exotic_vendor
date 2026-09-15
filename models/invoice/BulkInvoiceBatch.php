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

    /**
     * Get next pending item for processing. Lock/reserve it to prevent concurrent worker duplication.
     */
    public function lockNextPendingItem(int $batchId = 0): ?array
    {
        $where = "status = 'pending'";
        $types = "";
        $params = [];

        if ($batchId > 0) {
            $where .= " AND batch_id = ?";
            $types .= "i";
            $params[] = $batchId;
        }

        $sql = "SELECT * FROM vp_invoice_bulk_batch_items WHERE {$where} ORDER BY id ASC LIMIT 1 FOR UPDATE";
        
        $stmt = $this->db->prepare($sql);
        if (!$stmt) return null;

        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            // Mark item as processing
            $up = $this->db->prepare("UPDATE vp_invoice_bulk_batch_items SET status = 'processing' WHERE id = ? AND status = 'pending'");
            if ($up) {
                $up->bind_param("i", $row['id']);
                $up->execute();
                $affected = $up->affected_rows;
                $up->close();
                if ($affected === 0) {
                    // Race condition hit: item was already taken
                    return null;
                }
            }

            // Also mark main batch as processing & record start time if pending
            $bId = (int)$row['batch_id'];
            $bUp = $this->db->prepare("UPDATE vp_invoice_bulk_batches SET status = 'processing', started_at = COALESCE(started_at, NOW()) WHERE id = ? AND status = 'pending'");
            if ($bUp) {
                $bUp->bind_param("i", $bId);
                $bUp->execute();
                $bUp->close();
            }
        }

        return $row ?: null;
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
