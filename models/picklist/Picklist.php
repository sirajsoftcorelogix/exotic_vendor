<?php

class Picklist
{
    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function generatePicklistNumber(): string
    {
        $prefix = 'PL-' . date('Ymd') . '-';
        $sql = "SELECT picklist_number FROM vp_picklists
                WHERE picklist_number LIKE ?
                ORDER BY id DESC LIMIT 1";
        $like = $prefix . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $seq = 1;
        if ($result && $row = $result->fetch_assoc()) {
            $parts = explode('-', (string) $row['picklist_number']);
            $last = (int) end($parts);
            if ($last > 0) {
                $seq = $last + 1;
            }
        }
        $stmt->close();
        return $prefix . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * @return array{in_picklist: bool, picklist_id?: int, picklist_number?: string, status?: string}
     */
    public function getActivePicklistForOrder(int $orderId): array
    {
        if ($orderId <= 0) {
            return ['in_picklist' => false];
        }
        $sql = "SELECT pli.picklist_id, pl.picklist_number, pl.status
                FROM vp_picklist_items pli
                INNER JOIN vp_picklists pl ON pl.id = pli.picklist_id
                WHERE pli.order_id = ?
                  AND pl.status IN ('pending', 'in_progress')
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $orderId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'in_picklist' => true,
                'picklist_id' => (int) $row['picklist_id'],
                'picklist_number' => (string) $row['picklist_number'],
                'status' => (string) $row['status'],
            ];
        }
        $stmt->close();
        return ['in_picklist' => false];
    }

    /**
     * @param int[] $orderIds
     * @return array{success: bool, message?: string, picklist_id?: int, picklist_number?: string, added?: int, skipped?: array}
     */
    public function createFromOrders(array $orderIds, int $pickerId, int $createdBy, string $notes = ''): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return ['success' => false, 'message' => 'No orders selected.'];
        }

        require_once __DIR__ . '/../order/order.php';
        require_once __DIR__ . '/../product/product.php';
        $orderModel = new Order($this->db);
        $productModel = new product($this->db);

        $itemsToAdd = [];
        $skipped = [];

        foreach ($orderIds as $orderId) {
            $active = $this->getActivePicklistForOrder($orderId);
            if ($active['in_picklist']) {
                $skipped[] = [
                    'order_id' => $orderId,
                    'message' => 'Already on picklist ' . ($active['picklist_number'] ?? ''),
                ];
                continue;
            }

            $order = $orderModel->getOrderById($orderId);
            if (!$order) {
                $skipped[] = ['order_id' => $orderId, 'message' => 'Order not found'];
                continue;
            }

            $sku = trim((string) ($order['sku'] ?? ''));
            $itemCode = trim((string) ($order['item_code'] ?? ''));
            $location = '';
            $product = null;
            if ($sku !== '') {
                $product = $productModel->getProductByskuExact($sku);
            }
            if (!$product && $itemCode !== '') {
                $product = $productModel->getProductByItemCode($itemCode);
            }
            if ($product && !empty($product['location'])) {
                $location = trim((string) $product['location']);
            }

            $qty = (int) ($order['quantity'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }

            $itemsToAdd[] = [
                'order_id' => $orderId,
                'order_number' => (string) ($order['order_number'] ?? ''),
                'item_code' => $itemCode,
                'sku' => $sku,
                'size' => trim((string) ($order['size'] ?? '')),
                'color' => trim((string) ($order['color'] ?? '')),
                'title' => trim((string) ($order['title'] ?? '')),
                'image' => trim((string) ($order['image'] ?? '')),
                'warehouse_location' => $location,
                'quantity' => $qty,
            ];
        }

        if ($itemsToAdd === []) {
            return [
                'success' => false,
                'message' => 'No eligible orders to add.',
                'skipped' => $skipped,
            ];
        }

        usort($itemsToAdd, static function ($a, $b) {
            return strcasecmp((string) $a['warehouse_location'], (string) $b['warehouse_location']);
        });

        $this->db->begin_transaction();
        try {
            $picklistNumber = $this->generatePicklistNumber();
            $status = 'pending';
            $warehouseId = 0;
            if ($pickerId < 0) {
                $pickerId = 0;
            }

            $sql = "INSERT INTO vp_picklists
                    (picklist_number, picker_id, warehouse_id, status, notes, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed: ' . $this->db->error);
            }
            $stmt->bind_param('siissi', $picklistNumber, $pickerId, $warehouseId, $status, $notes, $createdBy);
            if (!$stmt->execute()) {
                throw new RuntimeException('Insert picklist failed: ' . $stmt->error);
            }
            $picklistId = (int) $this->db->insert_id;
            $stmt->close();

            $itemSql = "INSERT INTO vp_picklist_items
                (picklist_id, order_id, order_number, item_code, sku, size, color, title, image,
                 warehouse_location, quantity, status, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)";
            $itemStmt = $this->db->prepare($itemSql);
            if (!$itemStmt) {
                throw new RuntimeException('Prepare items failed: ' . $this->db->error);
            }

            $sortOrder = 0;
            foreach ($itemsToAdd as $item) {
                $sortOrder++;
                $itemStmt->bind_param(
                    'iissssssssii',
                    $picklistId,
                    $item['order_id'],
                    $item['order_number'],
                    $item['item_code'],
                    $item['sku'],
                    $item['size'],
                    $item['color'],
                    $item['title'],
                    $item['image'],
                    $item['warehouse_location'],
                    $item['quantity'],
                    $sortOrder
                );
                if (!$itemStmt->execute()) {
                    throw new RuntimeException('Insert item failed: ' . $itemStmt->error);
                }
            }
            $itemStmt->close();

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Picklist created successfully.',
                'picklist_id' => $picklistId,
                'picklist_number' => $picklistNumber,
                'added' => count($itemsToAdd),
                'order_ids' => array_column($itemsToAdd, 'order_id'),
                'skipped' => $skipped,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage(), 'skipped' => $skipped];
        }
    }

    public function searchPicklists(array $filters, int $pageNo = 1, int $limit = 20): array
    {
        $pageNo = max(1, $pageNo);
        $limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
        $offset = ($pageNo - 1) * $limit;

        $where = ['1=1'];
        $params = [];
        $types = '';

        if (!empty($filters['search_text'])) {
            $where[] = '(pl.picklist_number LIKE ? OR pu.name LIKE ? OR cu.name LIKE ?)';
            $q = '%' . $filters['search_text'] . '%';
            $params[] = $q;
            $params[] = $q;
            $params[] = $q;
            $types .= 'sss';
        }
        if (!empty($filters['status'])) {
            $where[] = 'pl.status = ?';
            $params[] = $filters['status'];
            $types .= 's';
        }
        if (!empty($filters['picker_id'])) {
            $where[] = 'pl.picker_id = ?';
            $params[] = (int) $filters['picker_id'];
            $types .= 'i';
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS cnt
                     FROM vp_picklists pl
                     LEFT JOIN vp_users pu ON pu.id = pl.picker_id
                     LEFT JOIN vp_users cu ON cu.id = pl.created_by
                     WHERE {$whereSql}";
        $countStmt = $this->db->prepare($countSql);
        if ($types !== '') {
            $countStmt->bind_param($types, ...$params);
        }
        $countStmt->execute();
        $total = (int) ($countStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $countStmt->close();

        $sql = "SELECT pl.*,
                       pu.name AS picker_name,
                       cu.name AS created_by_name,
                       (SELECT COUNT(*) FROM vp_picklist_items i WHERE i.picklist_id = pl.id) AS item_count,
                       (SELECT COUNT(*) FROM vp_picklist_items i WHERE i.picklist_id = pl.id AND i.status = 'picked') AS picked_count
                FROM vp_picklists pl
                LEFT JOIN vp_users pu ON pu.id = pl.picker_id
                LEFT JOIN vp_users cu ON cu.id = pl.created_by
                WHERE {$whereSql}
                ORDER BY pl.created_at DESC
                LIMIT ? OFFSET ?";
        $stmt = $this->db->prepare($sql);
        $allParams = array_merge($params, [$limit, $offset]);
        $allTypes = $types . 'ii';
        $stmt->bind_param($allTypes, ...$allParams);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();

        return ['rows' => $rows, 'total' => $total];
    }

    public function getPicklistById(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }
        $sql = "SELECT pl.*, pu.name AS picker_name, cu.name AS created_by_name
                FROM vp_picklists pl
                LEFT JOIN vp_users pu ON pu.id = pl.picker_id
                LEFT JOIN vp_users cu ON cu.id = pl.created_by
                WHERE pl.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = ($result && $result->num_rows > 0) ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row;
    }

    public function getPicklistItems(int $picklistId): array
    {
        if ($picklistId <= 0) {
            return [];
        }
        $sql = "SELECT pli.*, pu.name AS picked_by_name
                FROM vp_picklist_items pli
                LEFT JOIN vp_users pu ON pu.id = pli.picked_by
                WHERE pli.picklist_id = ?
                ORDER BY pli.sort_order ASC, pli.warehouse_location ASC, pli.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $picklistId);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }

    /**
     * @return array{success: bool, message?: string, order_id?: int, picklist_completed?: bool}
     */
    public function markItemPicked(int $itemId, int $userId): array
    {
        if ($itemId <= 0) {
            return ['success' => false, 'message' => 'Invalid item.'];
        }

        $sql = "SELECT pli.*, pl.status AS picklist_status
                FROM vp_picklist_items pli
                INNER JOIN vp_picklists pl ON pl.id = pli.picklist_id
                WHERE pli.id = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $itemId);
        $stmt->execute();
        $item = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$item) {
            return ['success' => false, 'message' => 'Picklist item not found.'];
        }
        if (($item['status'] ?? '') === 'picked') {
            return ['success' => true, 'message' => 'Already picked.', 'order_id' => (int) $item['order_id']];
        }
        if (($item['picklist_status'] ?? '') === 'cancelled') {
            return ['success' => false, 'message' => 'Picklist is cancelled.'];
        }

        $now = date('Y-m-d H:i:s');
        $picklistId = (int) $item['picklist_id'];

        $this->db->begin_transaction();
        try {
            $upd = $this->db->prepare(
                "UPDATE vp_picklist_items SET status = 'picked', picked_by = ?, picked_at = ? WHERE id = ? AND status = 'pending'"
            );
            $upd->bind_param('isi', $userId, $now, $itemId);
            if (!$upd->execute() || $upd->affected_rows === 0) {
                throw new RuntimeException('Failed to update item.');
            }
            $upd->close();

            if (($item['picklist_status'] ?? '') === 'pending') {
                $plUpd = $this->db->prepare("UPDATE vp_picklists SET status = 'in_progress' WHERE id = ? AND status = 'pending'");
                $plUpd->bind_param('i', $picklistId);
                $plUpd->execute();
                $plUpd->close();
            }

            $pendingSql = "SELECT COUNT(*) AS cnt FROM vp_picklist_items WHERE picklist_id = ? AND status = 'pending'";
            $pendingStmt = $this->db->prepare($pendingSql);
            $pendingStmt->bind_param('i', $picklistId);
            $pendingStmt->execute();
            $pendingCount = (int) ($pendingStmt->get_result()->fetch_assoc()['cnt'] ?? 0);
            $pendingStmt->close();

            $picklistCompleted = false;
            if ($pendingCount === 0) {
                $completeStmt = $this->db->prepare("UPDATE vp_picklists SET status = 'completed' WHERE id = ?");
                $completeStmt->bind_param('i', $picklistId);
                $completeStmt->execute();
                $completeStmt->close();
                $picklistCompleted = true;
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Item marked as picked.',
                'order_id' => (int) $item['order_id'],
                'picklist_id' => $picklistId,
                'picklist_completed' => $picklistCompleted,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function assignPicker(int $picklistId, int $pickerId): array
    {
        if ($picklistId <= 0) {
            return ['success' => false, 'message' => 'Invalid picklist.'];
        }
        if ($pickerId < 0) {
            $pickerId = 0;
        }
        $sql = 'UPDATE vp_picklists SET picker_id = ? WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ii', $pickerId, $picklistId);
        if (!$stmt->execute()) {
            return ['success' => false, 'message' => $stmt->error];
        }
        $stmt->close();
        return ['success' => true, 'message' => 'Picker assigned.'];
    }
}
