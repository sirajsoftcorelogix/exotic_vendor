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
     * @return array{on_picklist: bool, picklist_id?: int, picklist_number?: string, status?: string, sku?: string, order_number?: string}
     */
    public function findItemOnAnyPicklist(string $orderNumber, string $sku, string $itemCode = ''): array
    {
        $orderNumber = trim($orderNumber);
        $sku = trim($sku);
        $itemCode = trim($itemCode);

        if ($orderNumber === '') {
            return ['on_picklist' => false];
        }

        $sql = "SELECT pli.id AS item_id, pli.picklist_id, pl.picklist_number, pl.status, pli.sku, pli.order_number, pli.item_code
                FROM vp_picklist_items pli
                INNER JOIN vp_picklists pl ON pl.id = pli.picklist_id
                WHERE pl.status != 'cancelled'
                  AND TRIM(COALESCE(pli.order_number, '')) = ?
                  AND (
                        (? != '' AND TRIM(COALESCE(pli.sku, '')) = ?)
                     OR (? != '' AND TRIM(COALESCE(pli.item_code, '')) = ?)
                     OR (? = '' AND ? = '' AND TRIM(COALESCE(pli.sku, '')) = '' AND TRIM(COALESCE(pli.item_code, '')) = '')
                  )
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('sssssss', $orderNumber, $sku, $sku, $itemCode, $itemCode, $sku, $itemCode);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $row = $result->fetch_assoc()) {
            $stmt->close();
            return [
                'on_picklist' => true,
                'item_id' => (int) ($row['item_id'] ?? 0),
                'picklist_id' => (int) ($row['picklist_id'] ?? 0),
                'picklist_number' => (string) ($row['picklist_number'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
                'sku' => (string) ($row['sku'] ?? ''),
                'order_number' => (string) ($row['order_number'] ?? ''),
            ];
        }
        $stmt->close();
        return ['on_picklist' => false];
    }

    /**
     * @param int[] $orderIds
     * @return array<int, array{item_id: int, picklist_id: int, picklist_number: string, status: string}>
     */
    public function getPicklistItemsByOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));
        $sql = "SELECT pli.id AS item_id, pli.order_id, pli.picklist_id, pl.picklist_number, pl.status
                FROM vp_picklist_items pli
                INNER JOIN vp_picklists pl ON pl.id = pli.picklist_id
                WHERE pl.status != 'cancelled'
                  AND pli.order_id IN ({$placeholders})";
        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $types = str_repeat('i', count($orderIds));
        $stmt->bind_param($types, ...$orderIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $map = [];
        while ($result && ($row = $result->fetch_assoc())) {
            $orderId = (int) ($row['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }
            $map[$orderId] = [
                'item_id' => (int) ($row['item_id'] ?? 0),
                'picklist_id' => (int) ($row['picklist_id'] ?? 0),
                'picklist_number' => (string) ($row['picklist_number'] ?? ''),
                'status' => (string) ($row['status'] ?? ''),
            ];
        }
        $stmt->close();
        return $map;
    }

    /**
     * @return array{item_id: int, picklist_id: int, picklist_number: string, status: string}|null
     */
    public function getPicklistItemByOrderId(int $orderId): ?array
    {
        if ($orderId <= 0) {
            return null;
        }
        $map = $this->getPicklistItemsByOrderIds([$orderId]);
        if (isset($map[$orderId])) {
            return $map[$orderId];
        }

        require_once __DIR__ . '/../order/order.php';
        $orderModel = new Order($this->db);
        $order = $orderModel->getOrderById($orderId);
        if (!$order) {
            return null;
        }

        $existing = $this->findItemOnAnyPicklist(
            (string) ($order['order_number'] ?? ''),
            (string) ($order['sku'] ?? ''),
            (string) ($order['item_code'] ?? '')
        );
        if (empty($existing['on_picklist']) || (int) ($existing['item_id'] ?? 0) <= 0) {
            return null;
        }

        return [
            'item_id' => (int) $existing['item_id'],
            'picklist_id' => (int) ($existing['picklist_id'] ?? 0),
            'picklist_number' => (string) ($existing['picklist_number'] ?? ''),
            'status' => (string) ($existing['status'] ?? ''),
        ];
    }

    /**
     * @param int[] $orderIds
     * @return array{blocked: array<int, array<string, mixed>>, allowed_order_ids: int[]}
     */
    public function checkOrdersForPicklist(array $orderIds): array
    {
        require_once __DIR__ . '/../order/order.php';
        $orderModel = new Order($this->db);

        $blocked = [];
        $allowed = [];

        foreach (array_values(array_unique(array_filter(array_map('intval', $orderIds)))) as $orderId) {
            $order = $orderModel->getOrderById($orderId);
            if (!$order) {
                $blocked[] = [
                    'order_id' => $orderId,
                    'order_number' => '',
                    'sku' => '',
                    'message' => 'Order not found.',
                    'reason' => 'not_found',
                ];
                continue;
            }

            $orderNumber = trim((string) ($order['order_number'] ?? ''));
            $sku = trim((string) ($order['sku'] ?? ''));
            $itemCode = trim((string) ($order['item_code'] ?? ''));
            $existing = $this->findItemOnAnyPicklist($orderNumber, $sku, $itemCode);
            if ($existing['on_picklist']) {
                $displaySku = $sku !== '' ? $sku : ($itemCode !== '' ? $itemCode : 'N/A');
                $blocked[] = [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'sku' => $displaySku,
                    'picklist_id' => (int) ($existing['picklist_id'] ?? 0),
                    'picklist_number' => (string) ($existing['picklist_number'] ?? ''),
                    'message' => 'SKU ' . $displaySku . ' / Order ' . $orderNumber . ' is already on picklist ' . ($existing['picklist_number'] ?? ''),
                    'reason' => 'duplicate_picklist',
                ];
                continue;
            }

            $allowed[] = $orderId;
        }

        return ['blocked' => $blocked, 'allowed_order_ids' => $allowed];
    }

    /**
     * @param array<int, array<string, mixed>> $blocked
     */
    public function formatDuplicatePicklistMessage(array $blocked): string
    {
        $lines = [];
        foreach ($blocked as $row) {
            if (($row['reason'] ?? '') !== 'duplicate_picklist') {
                continue;
            }
            $lines[] = (string) ($row['message'] ?? 'Item already on a picklist.');
        }
        if ($lines === []) {
            return 'One or more items are already on a picklist.';
        }
        return implode(' ', $lines);
    }

    /**
     * @param int[] $orderIds
     * @return array{success: bool, message?: string, picklist_id?: int, picklist_number?: string, added?: int, skipped?: array, order_ids?: int[]}
     */
    public function createFromOrders(array $orderIds, int $pickerId, int $createdBy, string $notes = '', ?string $picklistName = null): array
    {
        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return ['success' => false, 'message' => 'No orders selected.'];
        }

        $prepared = $this->prepareOrdersForPicklist($orderIds);
        $itemsToAdd = $prepared['items'];
        $skipped = $prepared['skipped'];

        $duplicateBlocked = array_values(array_filter($skipped, static function (array $row): bool {
            return ($row['reason'] ?? '') === 'duplicate_picklist';
        }));
        if ($duplicateBlocked !== []) {
            return [
                'success' => false,
                'message' => $this->formatDuplicatePicklistMessage($duplicateBlocked),
                'blocked' => $duplicateBlocked,
                'skipped' => $skipped,
            ];
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

        $numberResult = $this->resolvePicklistNumber($picklistName);
        if (empty($numberResult['success'])) {
            $numberResult['skipped'] = $skipped;
            return $numberResult;
        }
        $picklistNumber = (string) $numberResult['picklist_number'];

        $this->db->begin_transaction();
        try {
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

            $this->insertPicklistItems($picklistId, $itemsToAdd, 0);

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

    /**
     * @param int[] $orderIds
     * @return array{success: bool, message?: string, picklist_id?: int, picklist_number?: string, added?: int, skipped?: array, order_ids?: int[]}
     */
    public function addOrdersToExistingPicklist(int $picklistId, array $orderIds, ?int $pickerId = null): array
    {
        $picklistId = (int) $picklistId;
        if ($picklistId <= 0) {
            return ['success' => false, 'message' => 'Please select a picklist.'];
        }

        $picklist = $this->getPicklistById($picklistId);
        if (!$picklist) {
            return ['success' => false, 'message' => 'Picklist not found.'];
        }

        $picklistStatus = (string) ($picklist['status'] ?? '');
        if (!in_array($picklistStatus, ['pending', 'in_progress'], true)) {
            return ['success' => false, 'message' => 'Only pending or in-progress picklists can receive new items.'];
        }

        $orderIds = array_values(array_unique(array_filter(array_map('intval', $orderIds))));
        if ($orderIds === []) {
            return ['success' => false, 'message' => 'No orders selected.'];
        }

        $prepared = $this->prepareOrdersForPicklist($orderIds);
        $itemsToAdd = $prepared['items'];
        $skipped = $prepared['skipped'];

        $duplicateBlocked = array_values(array_filter($skipped, static function (array $row): bool {
            return ($row['reason'] ?? '') === 'duplicate_picklist';
        }));
        if ($duplicateBlocked !== []) {
            return [
                'success' => false,
                'message' => $this->formatDuplicatePicklistMessage($duplicateBlocked),
                'blocked' => $duplicateBlocked,
                'skipped' => $skipped,
            ];
        }

        if ($itemsToAdd === []) {
            return [
                'success' => false,
                'message' => 'No eligible orders to add.',
                'skipped' => $skipped,
            ];
        }

        $existingItems = $this->getPicklistItems($picklistId);
        $mixError = $this->validateBookMixWithExisting($itemsToAdd, $existingItems);
        if ($mixError !== null) {
            $mixError['skipped'] = $skipped;
            return $mixError;
        }

        usort($itemsToAdd, [$this, 'comparePicklistItemLocations']);

        $maxSort = 0;
        foreach ($existingItems as $existing) {
            $maxSort = max($maxSort, (int) ($existing['sort_order'] ?? 0));
        }

        $this->db->begin_transaction();
        try {
            $this->insertPicklistItems($picklistId, $itemsToAdd, $maxSort);

            if ($pickerId !== null && $pickerId > 0 && (int) ($picklist['picker_id'] ?? 0) === 0) {
                $upd = $this->db->prepare('UPDATE vp_picklists SET picker_id = ? WHERE id = ?');
                $upd->bind_param('ii', $pickerId, $picklistId);
                $upd->execute();
                $upd->close();
            }

            $this->recalculatePicklistStatus($picklistId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Orders added to picklist successfully.',
                'picklist_id' => $picklistId,
                'picklist_number' => (string) ($picklist['picklist_number'] ?? ''),
                'added' => count($itemsToAdd),
                'order_ids' => array_column($itemsToAdd, 'order_id'),
                'skipped' => $skipped,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage(), 'skipped' => $skipped];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getOpenPicklistsForSelect(): array
    {
        $hasIsBook = false;
        if ($this->picklistItemHasDetailColumns()) {
            $colRes = $this->db->query("SHOW COLUMNS FROM vp_picklist_items LIKE 'is_book'");
            $hasIsBook = $colRes && $colRes->num_rows > 0;
        }

        $bookSelect = $hasIsBook
            ? ", (SELECT MAX(i.is_book) FROM vp_picklist_items i WHERE i.picklist_id = pl.id) AS has_book,
                   (SELECT MIN(i.is_book) FROM vp_picklist_items i WHERE i.picklist_id = pl.id) AS has_non_book"
            : ", NULL AS has_book, NULL AS has_non_book";

        $sql = "SELECT pl.id, pl.picklist_number, pl.status, pl.picker_id,
                       pu.name AS picker_name,
                       (SELECT COUNT(*) FROM vp_picklist_items i WHERE i.picklist_id = pl.id) AS item_count,
                       (SELECT COUNT(*) FROM vp_picklist_items i WHERE i.picklist_id = pl.id AND i.status = 'picked') AS picked_count
                       {$bookSelect}
                FROM vp_picklists pl
                LEFT JOIN vp_users pu ON pu.id = pl.picker_id
                WHERE pl.status IN ('pending', 'in_progress')
                ORDER BY pl.created_at DESC
                LIMIT 200";

        $res = $this->db->query($sql);
        $rows = [];
        if (!$res) {
            return $rows;
        }

        while ($row = $res->fetch_assoc()) {
            $itemCount = (int) ($row['item_count'] ?? 0);
            $picklistNumber = trim((string) ($row['picklist_number'] ?? ''));
            if ($picklistNumber === '') {
                $picklistNumber = 'Picklist #' . (int) ($row['id'] ?? 0);
            }
            $contentType = 'empty';
            if ($itemCount > 0 && $hasIsBook) {
                $hasBook = (int) ($row['has_book'] ?? 0) === 1;
                $hasNonBook = (int) ($row['has_non_book'] ?? 1) === 0;
                $contentType = ($hasBook && !$hasNonBook) ? 'book' : 'non_book';
            }
            $rows[] = [
                'id' => (int) ($row['id'] ?? 0),
                'picklist_number' => $picklistNumber,
                'status' => (string) ($row['status'] ?? ''),
                'picker_name' => (string) ($row['picker_name'] ?? ''),
                'item_count' => $itemCount,
                'picked_count' => (int) ($row['picked_count'] ?? 0),
                'content_type' => $contentType,
            ];
        }

        return $rows;
    }

    /**
     * @return array{success: bool, message?: string, picklist_number?: string}
     */
    private function resolvePicklistNumber(?string $picklistName): array
    {
        $custom = trim((string) $picklistName);
        if ($custom === '') {
            return [
                'success' => true,
                'picklist_number' => $this->generatePicklistNumber(),
            ];
        }

        if (strlen($custom) > 50) {
            return ['success' => false, 'message' => 'Picklist name must be 50 characters or fewer.'];
        }

        if (!preg_match('/^[A-Za-z0-9][A-Za-z0-9\s._\-\/]*$/', $custom)) {
            return ['success' => false, 'message' => 'Picklist name contains invalid characters. Use letters, numbers, spaces, dashes, dots, slashes, or underscores.'];
        }

        if ($this->picklistNumberExists($custom)) {
            return ['success' => false, 'message' => 'Picklist name "' . $custom . '" is already in use. Choose a different name.'];
        }

        return ['success' => true, 'picklist_number' => $custom];
    }

    private function picklistNumberExists(string $number): bool
    {
        $stmt = $this->db->prepare('SELECT id FROM vp_picklists WHERE picklist_number = ? LIMIT 1');
        $stmt->bind_param('s', $number);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $exists;
    }

    /**
     * @param int[] $orderIds
     * @return array{items: array<int, array<string, mixed>>, skipped: array<int, array<string, mixed>>}
     */
    private function prepareOrdersForPicklist(array $orderIds): array
    {
        require_once __DIR__ . '/../order/order.php';
        require_once __DIR__ . '/../product/product.php';
        $orderModel = new Order($this->db);
        $productModel = new product($this->db);

        $itemsToAdd = [];
        $skipped = [];

        foreach ($orderIds as $orderId) {
            $order = $orderModel->getOrderById($orderId);
            if (!$order) {
                $skipped[] = ['order_id' => $orderId, 'message' => 'Order not found', 'reason' => 'not_found'];
                continue;
            }

            $orderNumber = trim((string) ($order['order_number'] ?? ''));
            $sku = trim((string) ($order['sku'] ?? ''));
            $itemCode = trim((string) ($order['item_code'] ?? ''));
            $existing = $this->findItemOnAnyPicklist($orderNumber, $sku, $itemCode);
            if ($existing['on_picklist']) {
                $displaySku = $sku !== '' ? $sku : ($itemCode !== '' ? $itemCode : 'N/A');
                $skipped[] = [
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'sku' => $displaySku,
                    'picklist_number' => (string) ($existing['picklist_number'] ?? ''),
                    'message' => 'SKU ' . $displaySku . ' / Order ' . $orderNumber . ' is already on picklist ' . ($existing['picklist_number'] ?? ''),
                    'reason' => 'duplicate_picklist',
                ];
                continue;
            }

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

            $itemsToAdd[] = $this->buildPicklistItemSnapshot($order, $product, $location, $qty);
        }

        return ['items' => $itemsToAdd, 'skipped' => $skipped];
    }

    /**
     * @param array<int, array<string, mixed>> $itemsToAdd
     */
    private function insertPicklistItems(int $picklistId, array $itemsToAdd, int $startSortOrder = 0): void
    {
        if ($itemsToAdd === []) {
            return;
        }

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

        $sortOrder = $startSortOrder;
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
    }

    /**
     * @param array<int, array<string, mixed>> $newItems
     * @param array<int, array<string, mixed>> $existingItems
     * @return array{success: false, message: string}|null
     */
    private function validateBookMixWithExisting(array $newItems, array $existingItems): ?array
    {
        $combined = $existingItems;
        foreach ($newItems as $item) {
            $combined[] = $item;
        }
        return $this->validateNoMixedBookPicklist($combined);
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

    /**
     * @return array{success: bool, message?: string, order_id?: int, picklist_id?: int, picklist_number?: string}
     */
    public function revertItemPick(int $itemId): array
    {
        if ($itemId <= 0) {
            return ['success' => false, 'message' => 'Invalid item.'];
        }

        $sql = "SELECT pli.*, pl.status AS picklist_status, pl.picklist_number
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
        if (($item['status'] ?? '') !== 'picked') {
            return ['success' => true, 'message' => 'Item is not marked as picked.', 'order_id' => (int) $item['order_id']];
        }
        if (($item['picklist_status'] ?? '') === 'cancelled') {
            return ['success' => false, 'message' => 'Picklist is cancelled.'];
        }

        $picklistId = (int) ($item['picklist_id'] ?? 0);
        $picklistNumber = (string) ($item['picklist_number'] ?? '');

        $this->db->begin_transaction();
        try {
            $upd = $this->db->prepare(
                "UPDATE vp_picklist_items
                 SET status = 'pending', picked_by = NULL, picked_at = NULL
                 WHERE id = ? AND status = 'picked'"
            );
            $upd->bind_param('i', $itemId);
            if (!$upd->execute() || $upd->affected_rows === 0) {
                throw new RuntimeException('Failed to revert pick.');
            }
            $upd->close();

            $this->recalculatePicklistStatus($picklistId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Pick reverted.',
                'order_id' => (int) ($item['order_id'] ?? 0),
                'picklist_id' => $picklistId,
                'picklist_number' => $picklistNumber,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param int[] $itemIds
     * @return array{success: bool, message?: string, processed?: int, skipped?: int, order_ids?: int[], picklist_id?: int, picklist_number?: string, picklist_completed?: bool}
     */
    public function markItemsPickedBulk(array $itemIds, int $userId): array
    {
        $itemIds = $this->normalizeItemIds($itemIds);
        if ($itemIds === []) {
            return ['success' => false, 'message' => 'No items selected.'];
        }

        $items = $this->fetchItemsWithPicklistByIds($itemIds);
        if ($items === []) {
            return ['success' => false, 'message' => 'Selected items not found.'];
        }

        foreach ($items as $item) {
            if (($item['picklist_status'] ?? '') === 'cancelled') {
                return ['success' => false, 'message' => 'Picklist is cancelled.'];
            }
        }

        $pendingIds = [];
        foreach ($items as $item) {
            if (($item['status'] ?? '') === 'pending') {
                $pendingIds[] = (int) $item['id'];
            }
        }
        if ($pendingIds === []) {
            return ['success' => false, 'message' => 'No pending items in selection.'];
        }

        $now = date('Y-m-d H:i:s');
        $picklistIds = [];
        $orderIds = [];

        $this->db->begin_transaction();
        try {
            foreach ($items as $item) {
                $plId = (int) ($item['picklist_id'] ?? 0);
                if ($plId > 0) {
                    $picklistIds[$plId] = true;
                }
            }

            foreach (array_keys($picklistIds) as $picklistId) {
                $plUpd = $this->db->prepare(
                    "UPDATE vp_picklists SET status = 'in_progress' WHERE id = ? AND status = 'pending'"
                );
                $plUpd->bind_param('i', $picklistId);
                $plUpd->execute();
                $plUpd->close();
            }

            $placeholders = $this->buildInPlaceholders(count($pendingIds));
            $types = 'is' . str_repeat('i', count($pendingIds));
            $params = array_merge([$userId, $now], $pendingIds);
            $sql = "UPDATE vp_picklist_items
                    SET status = 'picked', picked_by = ?, picked_at = ?
                    WHERE id IN ($placeholders) AND status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $bind = [$types];
            foreach ($params as $i => $val) {
                $bind[] = &$params[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind);
            if (!$stmt->execute()) {
                throw new RuntimeException('Failed to update items.');
            }
            $processed = $stmt->affected_rows;
            $stmt->close();

            foreach ($items as $item) {
                if (($item['status'] ?? '') !== 'pending') {
                    continue;
                }
                $oid = (int) ($item['order_id'] ?? 0);
                if ($oid > 0) {
                    $orderIds[$oid] = true;
                }
            }

            $picklistCompleted = false;
            foreach (array_keys($picklistIds) as $picklistId) {
                $this->recalculatePicklistStatus($picklistId);
                $pl = $this->getPicklistById($picklistId);
                if ($pl && ($pl['status'] ?? '') === 'completed') {
                    $picklistCompleted = true;
                }
            }

            $this->db->commit();

            $first = $items[0];
            return [
                'success' => true,
                'message' => $processed . ' item(s) marked as picked.',
                'processed' => $processed,
                'skipped' => count($itemIds) - $processed,
                'order_ids' => array_keys($orderIds),
                'picklist_id' => (int) ($first['picklist_id'] ?? 0),
                'picklist_number' => (string) ($first['picklist_number'] ?? ''),
                'picklist_completed' => $picklistCompleted,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * @param int[] $itemIds
     * @return array{success: bool, message?: string, processed?: int, skipped?: int, order_ids?: int[], picklist_id?: int, picklist_number?: string}
     */
    public function revertItemsPickBulk(array $itemIds): array
    {
        $itemIds = $this->normalizeItemIds($itemIds);
        if ($itemIds === []) {
            return ['success' => false, 'message' => 'No items selected.'];
        }

        $items = $this->fetchItemsWithPicklistByIds($itemIds);
        if ($items === []) {
            return ['success' => false, 'message' => 'Selected items not found.'];
        }

        foreach ($items as $item) {
            if (($item['picklist_status'] ?? '') === 'cancelled') {
                return ['success' => false, 'message' => 'Picklist is cancelled.'];
            }
        }

        $pickedIds = [];
        foreach ($items as $item) {
            if (($item['status'] ?? '') === 'picked') {
                $pickedIds[] = (int) $item['id'];
            }
        }
        if ($pickedIds === []) {
            return ['success' => false, 'message' => 'No picked items in selection.'];
        }

        $picklistIds = [];
        $orderIds = [];

        $this->db->begin_transaction();
        try {
            $placeholders = $this->buildInPlaceholders(count($pickedIds));
            $types = str_repeat('i', count($pickedIds));
            $sql = "UPDATE vp_picklist_items
                    SET status = 'pending', picked_by = NULL, picked_at = NULL
                    WHERE id IN ($placeholders) AND status = 'picked'";
            $stmt = $this->db->prepare($sql);
            $bind = [$types];
            foreach ($pickedIds as $i => $val) {
                $bind[] = &$pickedIds[$i];
            }
            call_user_func_array([$stmt, 'bind_param'], $bind);
            if (!$stmt->execute()) {
                throw new RuntimeException('Failed to revert items.');
            }
            $processed = $stmt->affected_rows;
            $stmt->close();

            foreach ($items as $item) {
                if (($item['status'] ?? '') !== 'picked') {
                    continue;
                }
                $plId = (int) ($item['picklist_id'] ?? 0);
                if ($plId > 0) {
                    $picklistIds[$plId] = true;
                }
                $oid = (int) ($item['order_id'] ?? 0);
                if ($oid > 0) {
                    $orderIds[$oid] = true;
                }
            }

            foreach (array_keys($picklistIds) as $picklistId) {
                $this->recalculatePicklistStatus($picklistId);
            }

            $this->db->commit();

            $first = $items[0];
            return [
                'success' => true,
                'message' => $processed . ' item(s) reverted to pending.',
                'processed' => $processed,
                'skipped' => count($itemIds) - $processed,
                'order_ids' => array_keys($orderIds),
                'picklist_id' => (int) ($first['picklist_id'] ?? 0),
                'picklist_number' => (string) ($first['picklist_number'] ?? ''),
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

    /**
     * @return array{success: bool, message?: string, order_ids?: int[], picklist_number?: string}
     */
    public function deletePicklist(int $id): array
    {
        if ($id <= 0) {
            return ['success' => false, 'message' => 'Invalid picklist.'];
        }

        $picklist = $this->getPicklistById($id);
        if (!$picklist) {
            return ['success' => false, 'message' => 'Picklist not found.'];
        }

        $orderIds = [];
        $stmt = $this->db->prepare('SELECT DISTINCT order_id FROM vp_picklist_items WHERE picklist_id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($res && ($row = $res->fetch_assoc())) {
            $oid = (int) ($row['order_id'] ?? 0);
            if ($oid > 0) {
                $orderIds[] = $oid;
            }
        }
        $stmt->close();
        $orderIds = array_values(array_unique($orderIds));

        $del = $this->db->prepare('DELETE FROM vp_picklists WHERE id = ? LIMIT 1');
        if (!$del) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->db->error];
        }
        $del->bind_param('i', $id);
        if (!$del->execute() || $del->affected_rows === 0) {
            $del->close();
            return ['success' => false, 'message' => 'Failed to delete picklist.'];
        }
        $del->close();

        return [
            'success' => true,
            'message' => 'Picklist deleted.',
            'order_ids' => $orderIds,
            'picklist_number' => (string) ($picklist['picklist_number'] ?? ''),
        ];
    }

    /**
     * @return array{success: bool, message?: string, order_id?: int, picklist_id?: int, picklist_number?: string, picklist_deleted?: bool}
     */
    public function deletePicklistItem(int $itemId): array
    {
        if ($itemId <= 0) {
            return ['success' => false, 'message' => 'Invalid item.'];
        }

        $sql = "SELECT pli.*, pl.picklist_number, pl.status AS picklist_status
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

        $picklistId = (int) ($item['picklist_id'] ?? 0);
        $orderId = (int) ($item['order_id'] ?? 0);
        $picklistNumber = (string) ($item['picklist_number'] ?? '');

        $this->db->begin_transaction();
        try {
            $del = $this->db->prepare('DELETE FROM vp_picklist_items WHERE id = ? LIMIT 1');
            $del->bind_param('i', $itemId);
            if (!$del->execute() || $del->affected_rows === 0) {
                throw new RuntimeException('Failed to remove item.');
            }
            $del->close();

            $remaining = $this->countPicklistItems($picklistId);
            $picklistDeleted = false;
            if ($remaining === 0) {
                $plDel = $this->db->prepare('DELETE FROM vp_picklists WHERE id = ? LIMIT 1');
                $plDel->bind_param('i', $picklistId);
                $plDel->execute();
                $plDel->close();
                $picklistDeleted = true;
            } else {
                $this->recalculatePicklistStatus($picklistId);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Item removed from picklist.',
                'order_id' => $orderId,
                'picklist_id' => $picklistId,
                'picklist_number' => $picklistNumber,
                'picklist_deleted' => $picklistDeleted,
            ];
        } catch (Throwable $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function countPicklistItems(int $picklistId): int
    {
        if ($picklistId <= 0) {
            return 0;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) AS cnt FROM vp_picklist_items WHERE picklist_id = ?');
        $stmt->bind_param('i', $picklistId);
        $stmt->execute();
        $cnt = (int) ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
        $stmt->close();
        return $cnt;
    }

    private function recalculatePicklistStatus(int $picklistId): void
    {
        if ($picklistId <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
             FROM vp_picklist_items WHERE picklist_id = ?"
        );
        $stmt->bind_param('i', $picklistId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        $total = (int) ($row['total'] ?? 0);
        $pending = (int) ($row['pending'] ?? 0);
        if ($total === 0) {
            return;
        }

        if ($pending === 0) {
            $status = 'completed';
        } elseif ($pending === $total) {
            $status = 'pending';
        } else {
            $status = 'in_progress';
        }

        $upd = $this->db->prepare('UPDATE vp_picklists SET status = ? WHERE id = ?');
        $upd->bind_param('si', $status, $picklistId);
        $upd->execute();
        $upd->close();
    }

    /**
     * @param int[] $itemIds
     * @return int[]
     */
    private function normalizeItemIds(array $itemIds): array
    {
        $normalized = [];
        foreach ($itemIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalized[$id] = $id;
            }
        }
        return array_values($normalized);
    }

    private function buildInPlaceholders(int $count): string
    {
        if ($count <= 0) {
            return '0';
        }
        return implode(',', array_fill(0, $count, '?'));
    }

    /**
     * @param int[] $itemIds
     * @return array<int, array<string, mixed>>
     */
    private function fetchItemsWithPicklistByIds(array $itemIds): array
    {
        if ($itemIds === []) {
            return [];
        }
        $placeholders = $this->buildInPlaceholders(count($itemIds));
        $types = str_repeat('i', count($itemIds));
        $sql = "SELECT pli.*, pl.status AS picklist_status, pl.picklist_number
                FROM vp_picklist_items pli
                INNER JOIN vp_picklists pl ON pl.id = pli.picklist_id
                WHERE pli.id IN ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $bind = [$types];
        foreach ($itemIds as $i => $val) {
            $bind[] = &$itemIds[$i];
        }
        call_user_func_array([$stmt, 'bind_param'], $bind);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        $stmt->close();
        return $rows;
    }
}
