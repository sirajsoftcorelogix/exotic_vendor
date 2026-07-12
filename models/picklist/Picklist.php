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
            if ($location === '' && !empty($order['location'])) {
                $location = trim((string) $order['location']);
            }

            $qty = (int) ($order['quantity'] ?? 1);
            if ($qty < 1) {
                $qty = 1;
            }

            $snapshot = $this->buildPicklistItemSnapshot($order, $product, $location, $qty);
            $itemsToAdd[] = $snapshot;
        }

        if ($itemsToAdd === []) {
            return [
                'success' => false,
                'message' => 'No eligible orders to add.',
                'skipped' => $skipped,
            ];
        }

        $mixError = $this->validateNoMixedBookPicklist($itemsToAdd);
        if ($mixError !== null) {
            $mixError['skipped'] = $skipped;
            return $mixError;
        }

        usort($itemsToAdd, [$this, 'comparePicklistItemLocations']);

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

            $itemSql = $this->picklistItemHasDetailColumns()
                ? "INSERT INTO vp_picklist_items
                    (picklist_id, order_id, order_number, item_code, sku, size, color, title, image,
                     publisher, cover_type, physical_qty, is_book,
                     warehouse_location, quantity, status, sort_order)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)"
                : "INSERT INTO vp_picklist_items
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
                if ($this->picklistItemHasDetailColumns()) {
                    $isBook = (int) ($item['is_book'] ?? 0);
                    $physicalQty = (int) ($item['physical_qty'] ?? 0);
                    $itemStmt->bind_param(
                        'iissssssssssiisii',
                        $picklistId,
                        $item['order_id'],
                        $item['order_number'],
                        $item['item_code'],
                        $item['sku'],
                        $item['size'],
                        $item['color'],
                        $item['title'],
                        $item['image'],
                        $item['publisher'],
                        $item['cover_type'],
                        $physicalQty,
                        $isBook,
                        $item['warehouse_location'],
                        $item['quantity'],
                        $sortOrder
                    );
                } else {
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
                }
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
                ORDER BY pli.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $picklistId);
        $stmt->execute();
        $rows = [];
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $this->normalizePicklistItemRow($row);
        }
        $stmt->close();
        return $this->sortPicklistItemsByLocation($rows);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, array<string, mixed>>
     */
    private function sortPicklistItemsByLocation(array $items): array
    {
        usort($items, [$this, 'comparePicklistItemLocations']);
        return $items;
    }

    /**
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    public function comparePicklistItemLocations(array $a, array $b): int
    {
        $locA = trim((string) ($a['warehouse_location'] ?? ''));
        $locB = trim((string) ($b['warehouse_location'] ?? ''));

        if ($locA === '' && $locB === '') {
            return strnatcasecmp(
                (string) ($a['order_number'] ?? ''),
                (string) ($b['order_number'] ?? '')
            );
        }
        if ($locA === '') {
            return 1;
        }
        if ($locB === '') {
            return -1;
        }

        $cmp = strnatcasecmp($locA, $locB);
        if ($cmp !== 0) {
            return $cmp;
        }

        return strnatcasecmp(
            (string) ($a['order_number'] ?? ''),
            (string) ($b['order_number'] ?? '')
        );
    }

    /**
     * @param array<string, mixed>|null $product
     * @return array<string, mixed>
     */
    private function buildPicklistItemSnapshot(array $order, ?array $product, string $location, int $qty): array
    {
        $itemCode = trim((string) ($order['item_code'] ?? ''));
        $sku = trim((string) ($order['sku'] ?? ''));
        $title = trim((string) ($order['title'] ?? ''));
        $image = trim((string) ($order['image'] ?? ''));
        if ($image === '' && is_array($product) && !empty($product['image'])) {
            $image = trim((string) $product['image']);
        }

        $publisher = trim((string) ($order['publisher'] ?? ''));
        if ($publisher === '' && is_array($product) && !empty($product['publisher'])) {
            $publisher = trim((string) $product['publisher']);
        }

        $coverType = is_array($product) ? trim((string) ($product['cover_type'] ?? '')) : '';
        $physicalQty = is_array($product) ? (int) ($product['physical_stock'] ?? 0) : 0;
        $author = trim((string) ($order['author'] ?? ''));
        if ($author === '' && is_array($product) && !empty($product['author'])) {
            $author = trim((string) $product['author']);
        }

        $itemtype = trim((string) ($order['itemtype'] ?? ''));
        if ($itemtype === '' && is_array($product) && !empty($product['itemtype'])) {
            $itemtype = trim((string) $product['itemtype']);
        }

        $groupname = trim((string) ($order['groupname'] ?? ''));
        if ($groupname === '' && is_array($product) && !empty($product['groupname'])) {
            $groupname = trim((string) $product['groupname']);
        }

        $isBook = $this->detectBookItem($author, $publisher, $itemtype, $groupname);

        return [
            'order_id' => (int) ($order['id'] ?? 0),
            'order_number' => (string) ($order['order_number'] ?? ''),
            'item_code' => $itemCode,
            'sku' => $sku,
            'size' => trim((string) ($order['size'] ?? '')),
            'color' => trim((string) ($order['color'] ?? '')),
            'title' => $title,
            'image' => $image,
            'warehouse_location' => $location,
            'quantity' => $qty,
            'publisher' => $publisher,
            'cover_type' => $coverType,
            'physical_qty' => $physicalQty,
            'is_book' => $isBook ? 1 : 0,
            'author' => $author,
            'itemtype' => $itemtype,
            'groupname' => $groupname,
        ];
    }

    private function detectBookItem(string $author, string $publisher, string $itemtype, string $groupname): bool
    {
        if ($author !== '' || $publisher !== '') {
            return true;
        }
        foreach ([$itemtype, $groupname] as $val) {
            $norm = strtolower(trim($val));
            if ($norm !== '' && str_contains($norm, 'book')) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array{success: false, message: string}|null
     */
    private function validateNoMixedBookPicklist(array $items): ?array
    {
        if (count($items) < 2) {
            return null;
        }

        $hasBook = false;
        $hasNonBook = false;
        foreach ($items as $item) {
            if (!empty($item['is_book'])) {
                $hasBook = true;
            } else {
                $hasNonBook = true;
            }
            if ($hasBook && $hasNonBook) {
                return [
                    'success' => false,
                    'message' => 'Cannot mix book and non-book items in one picklist. Create separate picklists for books and other items.',
                ];
            }
        }

        return null;
    }

    private function picklistItemHasDetailColumns(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }
        $res = $this->db->query("SHOW COLUMNS FROM vp_picklist_items LIKE 'physical_qty'");
        $has = $res && $res->num_rows > 0;
        return $has;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizePicklistItemRow(array $row): array
    {
        $publisher = trim((string) ($row['publisher'] ?? ''));
        $coverType = trim((string) ($row['cover_type'] ?? ''));
        $physicalQty = (int) ($row['physical_qty'] ?? 0);
        $author = trim((string) ($row['author'] ?? ''));
        $itemtype = trim((string) ($row['itemtype'] ?? ''));
        $groupname = trim((string) ($row['groupname'] ?? ''));
        $isBook = !empty($row['is_book']);

        if ($publisher === '' || $coverType === '' || $physicalQty === 0 || !$isBook) {
            require_once __DIR__ . '/../order/order.php';
            require_once __DIR__ . '/../product/product.php';
            $orderModel = new Order($this->db);
            $productModel = new product($this->db);

            $order = !empty($row['order_id']) ? $orderModel->getOrderById((int) $row['order_id']) : null;
            $product = null;
            $sku = trim((string) ($row['sku'] ?? ''));
            $itemCode = trim((string) ($row['item_code'] ?? ''));
            if ($sku !== '') {
                $product = $productModel->getProductByskuExact($sku);
            }
            if (!$product && $itemCode !== '') {
                $product = $productModel->getProductByItemCode($itemCode);
            }

            if (is_array($order)) {
                if ($author === '') {
                    $author = trim((string) ($order['author'] ?? ''));
                }
                if ($publisher === '') {
                    $publisher = trim((string) ($order['publisher'] ?? ''));
                }
                if ($itemtype === '') {
                    $itemtype = trim((string) ($order['itemtype'] ?? ''));
                }
                if ($groupname === '') {
                    $groupname = trim((string) ($order['groupname'] ?? ''));
                }
                if (trim((string) ($row['image'] ?? '')) === '' && !empty($order['image'])) {
                    $row['image'] = trim((string) $order['image']);
                }
            }

            if (is_array($product)) {
                if ($publisher === '' && !empty($product['publisher'])) {
                    $publisher = trim((string) $product['publisher']);
                }
                if ($coverType === '' && !empty($product['cover_type'])) {
                    $coverType = trim((string) $product['cover_type']);
                }
                if ($physicalQty === 0) {
                    $physicalQty = (int) ($product['physical_stock'] ?? 0);
                }
                if ($author === '' && !empty($product['author'])) {
                    $author = trim((string) $product['author']);
                }
                if ($itemtype === '' && !empty($product['itemtype'])) {
                    $itemtype = trim((string) $product['itemtype']);
                }
                if ($groupname === '' && !empty($product['groupname'])) {
                    $groupname = trim((string) $product['groupname']);
                }
                if (trim((string) ($row['image'] ?? '')) === '' && !empty($product['image'])) {
                    $row['image'] = trim((string) $product['image']);
                }
                if (trim((string) ($row['warehouse_location'] ?? '')) === '' && !empty($product['location'])) {
                    $row['warehouse_location'] = trim((string) $product['location']);
                }
            }
        }

        if (!$isBook) {
            $isBook = $this->detectBookItem($author, $publisher, $itemtype, $groupname);
        }

        $row['publisher'] = $publisher;
        $row['cover_type'] = $coverType;
        $row['physical_qty'] = $physicalQty;
        $row['author'] = $author;
        $row['itemtype'] = $itemtype;
        $row['groupname'] = $groupname;
        $row['is_book'] = $isBook ? 1 : 0;

        return $row;
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
