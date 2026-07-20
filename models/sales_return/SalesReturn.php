<?php

require_once __DIR__ . '/SalesReturnSchema.php';
require_once __DIR__ . '/../PosInvoice/invoice.php';
require_once __DIR__ . '/../posorder/order.php';
require_once __DIR__ . '/../product/product.php';
require_once __DIR__ . '/../../helpers/sales_return_types.php';
require_once __DIR__ . '/../../helpers/pos_payment_receipt.php';

class SalesReturn
{
    /** @var mysqli */
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureSchema();
    }

    private function ensureSchema(): void
    {
        SalesReturnSchema::ensureAll($this->conn);
    }

    public function generateNextReturnNumber(int $warehouseId): string
    {
        $shortCode = pos_payment_resolve_short_code_for_warehouse($this->conn, $warehouseId);
        $ymd = pos_payment_receipt_ymd_suffix();
        $base = 'SR' . $shortCode . $ymd;

        if (!$this->conn->begin_transaction()) {
            throw new RuntimeException('Unable to begin transaction for return number');
        }

        try {
            $like = $base . '%';
            $stmt = $this->conn->prepare(
                'SELECT return_number FROM vp_sales_returns
                 WHERE return_number LIKE ?
                 ORDER BY return_number DESC
                 LIMIT 1
                 FOR UPDATE'
            );
            if (!$stmt) {
                throw new RuntimeException('Prepare failed for return number');
            }
            $stmt->bind_param('s', $like);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $next = 1;
            if (!empty($row['return_number'])) {
                $suffix = substr((string) $row['return_number'], strlen($base));
                if (ctype_digit($suffix)) {
                    $next = (int) $suffix + 1;
                }
            }
            if ($next > 99) {
                throw new RuntimeException('Daily sales return sequence exceeded 99 for ' . $base);
            }

            $this->conn->commit();

            return $base . str_pad((string) $next, 2, '0', STR_PAD_LEFT);
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getById(int $id): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM vp_sales_returns WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getItems(int $returnId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM vp_sales_return_items WHERE sales_return_id = ? ORDER BY sort_order ASC, id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $returnId);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return $rows;
    }

    /**
     * @return array<int, float> order_row_id => qty
     */
    public function sumReturnedQtyByOrderRow(string $orderNumber, int $excludeReturnId = 0): array
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return [];
        }

        $sql = 'SELECT ri.order_row_id, SUM(ri.return_qty) AS sq
            FROM vp_sales_return_items ri
            INNER JOIN vp_sales_returns r ON r.id = ri.sales_return_id
            WHERE r.order_number = ? AND r.status = \'finalized\' AND ri.order_row_id IS NOT NULL';
        if ($excludeReturnId > 0) {
            $sql .= ' AND r.id <> ?';
        }
        $sql .= ' GROUP BY ri.order_row_id';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($excludeReturnId > 0) {
            $stmt->bind_param('si', $orderNumber, $excludeReturnId);
        } else {
            $stmt->bind_param('s', $orderNumber);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $map = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[(int) $row['order_row_id']] = (float) ($row['sq'] ?? 0);
            }
        }
        $stmt->close();

        return $map;
    }

    /**
     * @return array<int, float> invoice_item_id => qty
     */
    public function sumReturnedQtyByInvoiceItem(int $invoiceId, int $excludeReturnId = 0): array
    {
        if ($invoiceId <= 0) {
            return [];
        }

        $sql = 'SELECT ri.invoice_item_id, SUM(ri.return_qty) AS sq
            FROM vp_sales_return_items ri
            INNER JOIN vp_sales_returns r ON r.id = ri.sales_return_id
            WHERE r.invoice_id = ? AND r.status = \'finalized\' AND ri.invoice_item_id IS NOT NULL';
        if ($excludeReturnId > 0) {
            $sql .= ' AND r.id <> ?';
        }
        $sql .= ' GROUP BY ri.invoice_item_id';

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        if ($excludeReturnId > 0) {
            $stmt->bind_param('ii', $invoiceId, $excludeReturnId);
        } else {
            $stmt->bind_param('i', $invoiceId);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $map = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $map[(int) $row['invoice_item_id']] = (float) ($row['sq'] ?? 0);
            }
        }
        $stmt->close();

        return $map;
    }

    /**
     * @return array{
     *   order_number:string,
     *   invoice:array<string,mixed>|null,
     *   warehouse_id:int,
     *   lines:array<int,array<string,mixed>>
     * }
     */
    public function getReturnContext(string $orderNumber, ?int $invoiceId = null): array
    {
        $orderNumber = trim($orderNumber);
        $orderModel = new POSOrder($this->conn);
        $invoiceModel = new POSInvoice($this->conn);
        $productModel = new product($this->conn);

        $orderRows = $orderModel->getOrderByOrderNumber($orderNumber);
        if (empty($orderRows)) {
            return [
                'order_number' => $orderNumber,
                'invoice' => null,
                'warehouse_id' => 0,
                'lines' => [],
            ];
        }

        $invoice = null;
        if ($invoiceId !== null && $invoiceId > 0) {
            $invoice = $invoiceModel->getInvoiceById($invoiceId);
        }
        if (!$invoice) {
            $invoice = $invoiceModel->getActiveInvoiceForOrderNumber($orderNumber);
        }

        $invoiceIdResolved = $invoice ? (int) ($invoice['id'] ?? 0) : 0;
        $warehouseId = $invoice ? (int) ($invoice['warehouse_id'] ?? 0) : (int) ($_SESSION['warehouse_id'] ?? 0);
        if ($warehouseId <= 0 && !empty($_SESSION['user']['warehouse_id'])) {
            $warehouseId = (int) $_SESSION['user']['warehouse_id'];
        }

        $invoiceItems = $invoiceIdResolved > 0 ? $invoiceModel->getInvoiceItems($invoiceIdResolved) : [];
        $invoiceItemsByKey = [];
        foreach ($invoiceItems as $invItem) {
            $key = $this->lineMatchKey(
                (string) ($invItem['item_code'] ?? ''),
                '',
                '',
                (int) ($invItem['id'] ?? 0)
            );
            $orderMatch = $this->findOrderRowForInvoiceItem($orderRows, $invItem);
            if ($orderMatch) {
                $key = $this->lineMatchKey(
                    (string) ($orderMatch['item_code'] ?? ''),
                    (string) ($orderMatch['size'] ?? ''),
                    (string) ($orderMatch['color'] ?? ''),
                    (int) ($orderMatch['id'] ?? 0)
                );
            }
            $invoiceItemsByKey[$key] = $invItem;
        }

        $returnedByOrderRow = $this->sumReturnedQtyByOrderRow($orderNumber);
        $returnedByInvoiceItem = $invoiceIdResolved > 0
            ? $this->sumReturnedQtyByInvoiceItem($invoiceIdResolved)
            : [];

        $lines = [];
        $sort = 0;
        foreach ($orderRows as $orderRow) {
            $orderRowId = (int) ($orderRow['id'] ?? 0);
            $itemCode = trim((string) ($orderRow['item_code'] ?? ''));
            $size = trim((string) ($orderRow['size'] ?? ''));
            $color = trim((string) ($orderRow['color'] ?? ''));
            $soldQty = (float) ($orderRow['quantity'] ?? 0);

            $invoiceItem = $this->findInvoiceItemForOrderRow($invoiceItems, $orderRow);
            $invoiceItemId = $invoiceItem ? (int) ($invoiceItem['id'] ?? 0) : 0;
            if ($invoiceItem) {
                $soldQty = (float) ($invoiceItem['quantity'] ?? $soldQty);
            }

            $alreadyReturned = (float) ($returnedByOrderRow[$orderRowId] ?? 0);
            if ($invoiceItemId > 0 && isset($returnedByInvoiceItem[$invoiceItemId])) {
                $alreadyReturned = max($alreadyReturned, (float) $returnedByInvoiceItem[$invoiceItemId]);
            }

            $maxReturn = max(0, $soldQty - $alreadyReturned);
            if ($maxReturn <= 0) {
                continue;
            }

            $productId = 0;
            if ($invoiceItem && !empty($invoiceItem['product_id'])) {
                $productId = (int) $invoiceItem['product_id'];
            }
            if ($productId <= 0 && $orderNumber !== '' && $itemCode !== '') {
                $productId = (int) $productModel->getProductIdForInvoiceLine($orderNumber, $itemCode, $size, $color);
            }

            $lines[] = [
                'order_row_id' => $orderRowId,
                'invoice_item_id' => $invoiceItemId > 0 ? $invoiceItemId : null,
                'item_code' => $itemCode,
                'item_name' => trim((string) ($orderRow['itemname'] ?? $orderRow['item_name'] ?? ($invoiceItem['item_name'] ?? ''))),
                'size' => $size,
                'color' => $color,
                'sku' => trim((string) ($orderRow['sku'] ?? '')),
                'sold_qty' => $soldQty,
                'already_returned_qty' => $alreadyReturned,
                'max_return_qty' => $maxReturn,
                'product_id' => $productId,
                'sort_order' => $sort++,
            ];
        }

        return [
            'order_number' => $orderNumber,
            'invoice' => $invoice,
            'warehouse_id' => $warehouseId,
            'lines' => $lines,
        ];
    }

    /**
     * @param array<string, mixed> $header
     * @param array<int, array<string, mixed>> $lines
     * @return array{valid:bool,errors:array<int,string>,normalized_lines:array<int,array<string,mixed>>}
     */
    public function validateReturnLines(array $header, array $lines, int $sessionWarehouseId, bool $isAdmin): array
    {
        $errors = [];
        $orderNumber = trim((string) ($header['order_number'] ?? ''));
        if ($orderNumber === '') {
            return ['valid' => false, 'errors' => ['Order number is required.'], 'normalized_lines' => []];
        }

        $context = $this->getReturnContext($orderNumber, isset($header['invoice_id']) ? (int) $header['invoice_id'] : null);
        if ($context['lines'] === []) {
            return ['valid' => false, 'errors' => ['Order not found or has no returnable lines.'], 'normalized_lines' => []];
        }

        $warehouseId = (int) ($context['warehouse_id'] ?? 0);
        if ($warehouseId <= 0) {
            $warehouseId = $sessionWarehouseId;
        }
        if ($warehouseId <= 0) {
            $errors[] = 'Warehouse could not be determined. Select a warehouse in your session.';
        } elseif (!$isAdmin && $sessionWarehouseId > 0 && $warehouseId !== $sessionWarehouseId) {
            $errors[] = 'This order belongs to a different warehouse.';
        }

        $contextLinesByOrderRow = [];
        foreach ($context['lines'] as $ctxLine) {
            $contextLinesByOrderRow[(int) ($ctxLine['order_row_id'] ?? 0)] = $ctxLine;
        }

        $normalized = [];
        $hasPositive = false;
        foreach ($lines as $idx => $line) {
            $orderRowId = (int) ($line['order_row_id'] ?? 0);
            $returnQty = (float) ($line['return_qty'] ?? 0);
            if ($returnQty <= 0) {
                continue;
            }
            $hasPositive = true;

            if ($orderRowId <= 0 || !isset($contextLinesByOrderRow[$orderRowId])) {
                $errors[] = 'Invalid return line at row ' . ($idx + 1) . '.';
                continue;
            }

            $ctx = $contextLinesByOrderRow[$orderRowId];
            $max = (float) ($ctx['max_return_qty'] ?? 0);
            if ($returnQty > $max + 0.0001) {
                $errors[] = sprintf(
                    'Return qty %.3f exceeds returnable qty %.3f for item %s.',
                    $returnQty,
                    $max,
                    (string) ($ctx['item_code'] ?? '')
                );
                continue;
            }

            $normalized[] = [
                'order_row_id' => $orderRowId,
                'invoice_item_id' => !empty($ctx['invoice_item_id']) ? (int) $ctx['invoice_item_id'] : null,
                'product_id' => (int) ($ctx['product_id'] ?? 0),
                'item_code' => (string) ($ctx['item_code'] ?? ''),
                'size' => (string) ($ctx['size'] ?? ''),
                'color' => (string) ($ctx['color'] ?? ''),
                'return_qty' => $returnQty,
                'sort_order' => (int) ($ctx['sort_order'] ?? 0),
            ];
        }

        if (!$hasPositive) {
            $errors[] = 'Enter a return quantity for at least one line.';
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'normalized_lines' => $normalized,
            'warehouse_id' => $warehouseId,
            'invoice_id' => !empty($context['invoice']['id']) ? (int) $context['invoice']['id'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $header
     * @param array<int, array<string, mixed>> $lines
     */
    public function insertReturn(array $header, array $lines): int
    {
        $this->ensureSchema();
        $warehouseId = (int) ($header['warehouse_id'] ?? 0);
        $returnNumber = trim((string) ($header['return_number'] ?? ''));
        if ($returnNumber === '') {
            $returnNumber = $this->generateNextReturnNumber($warehouseId);
        }

        $this->conn->begin_transaction();
        try {
            $sql = 'INSERT INTO vp_sales_returns (
                return_number, order_number, invoice_id, warehouse_id, return_date,
                return_type, remarks, status, stock_applied, created_by
            ) VALUES (?,?,?,?,?,?,?,?,?,?)';
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new RuntimeException('Prepare failed: ' . $this->conn->error);
            }

            $orderNumber = (string) $header['order_number'];
            $invoiceId = !empty($header['invoice_id']) ? (int) $header['invoice_id'] : null;
            $returnDate = (string) $header['return_date'];
            $returnType = sales_return_normalize_type($header['return_type'] ?? '');
            $remarks = (string) ($header['remarks'] ?? '');
            $status = (string) ($header['status'] ?? 'finalized');
            $stockApplied = 0;
            $createdBy = (int) ($header['created_by'] ?? 0);

            $stmt->bind_param(
                'ssiissssii',
                $returnNumber,
                $orderNumber,
                $invoiceId,
                $warehouseId,
                $returnDate,
                $returnType,
                $remarks,
                $status,
                $stockApplied,
                $createdBy
            );
            $stmt->execute();
            $returnId = (int) $this->conn->insert_id;
            $stmt->close();

            $ins = $this->conn->prepare(
                'INSERT INTO vp_sales_return_items (
                    sales_return_id, invoice_item_id, order_row_id, product_id,
                    item_code, size, color, return_qty, stock_applied_qty, sort_order
                ) VALUES (?,?,?,?,?,?,?,?,?,?)'
            );
            if (!$ins) {
                throw new RuntimeException('Prepare failed for return items: ' . $this->conn->error);
            }

            foreach ($lines as $ln) {
                $invoiceItemId = !empty($ln['invoice_item_id']) ? (int) $ln['invoice_item_id'] : null;
                $orderRowId = (int) ($ln['order_row_id'] ?? 0);
                $productId = (int) ($ln['product_id'] ?? 0);
                $itemCode = (string) ($ln['item_code'] ?? '');
                $size = (string) ($ln['size'] ?? '');
                $color = (string) ($ln['color'] ?? '');
                $returnQty = (float) ($ln['return_qty'] ?? 0);
                $stockAppliedQty = 0.0;
                $sortOrder = (int) ($ln['sort_order'] ?? 0);

                $ins->bind_param(
                    'iiiisssddi',
                    $returnId,
                    $invoiceItemId,
                    $orderRowId,
                    $productId,
                    $itemCode,
                    $size,
                    $color,
                    $returnQty,
                    $stockAppliedQty,
                    $sortOrder
                );
                $ins->execute();
            }
            $ins->close();

            $this->conn->commit();

            return $returnId;
        } catch (Throwable $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    /**
     * @param array<string, mixed> $stockResult
     */
    public function updateStockAppliedFlags(int $returnId, array $stockResult): void
    {
        $lineResults = is_array($stockResult['line_results'] ?? null) ? $stockResult['line_results'] : [];
        foreach ($lineResults as $lr) {
            $itemId = (int) ($lr['item_id'] ?? 0);
            $appliedQty = (float) ($lr['applied_qty'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $stmt = $this->conn->prepare(
                'UPDATE vp_sales_return_items SET stock_applied_qty = ? WHERE id = ? AND sales_return_id = ?'
            );
            if ($stmt) {
                $stmt->bind_param('dii', $appliedQty, $itemId, $returnId);
                $stmt->execute();
                $stmt->close();
            }
        }

        $appliedLines = (int) ($stockResult['applied_lines'] ?? 0);
        if ($appliedLines > 0) {
            $stmt = $this->conn->prepare('UPDATE vp_sales_returns SET stock_applied = 1 WHERE id = ?');
            if ($stmt) {
                $stmt->bind_param('i', $returnId);
                $stmt->execute();
                $stmt->close();
            }
        }
    }

    public function updateOrderReturnStatus(string $orderNumber): void
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return;
        }

        $orderModel = new POSOrder($this->conn);
        $orderRows = $orderModel->getOrderByOrderNumber($orderNumber);
        if (empty($orderRows)) {
            return;
        }

        $returnedByOrderRow = $this->sumReturnedQtyByOrderRow($orderNumber);

        foreach ($orderRows as $row) {
            $orderRowId = (int) ($row['id'] ?? 0);
            $soldQty = (float) ($row['quantity'] ?? 0);
            $returnedQty = (float) ($returnedByOrderRow[$orderRowId] ?? 0);
            if ($soldQty > 0 && $returnedQty >= $soldQty - 0.0001) {
                $stmt = $this->conn->prepare('UPDATE vp_orders SET status = ? WHERE id = ?');
                if ($stmt) {
                    $status = 'returned';
                    $stmt->bind_param('si', $status, $orderRowId);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $filters
     * @return array{rows:array<int,array<string,mixed>>,total:int}
     */
    public function searchReturns(array $filters, int $pageNo, int $limit): array
    {
        $pageNo = max(1, $pageNo);
        $limit = max(1, min(100, $limit));
        $offset = ($pageNo - 1) * $limit;

        $where = ['1=1'];
        $types = '';
        $params = [];

        $search = trim((string) ($filters['search_text'] ?? ''));
        if ($search !== '') {
            $where[] = '(r.return_number LIKE ? OR r.order_number LIKE ? OR r.remarks LIKE ?)';
            $like = '%' . $search . '%';
            $types .= 'sss';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        if (!empty($filters['return_date_from'])) {
            $where[] = 'r.return_date >= ?';
            $types .= 's';
            $params[] = (string) $filters['return_date_from'];
        }
        if (!empty($filters['return_date_to'])) {
            $where[] = 'r.return_date <= ?';
            $types .= 's';
            $params[] = (string) $filters['return_date_to'];
        }

        $warehouseId = (int) ($filters['warehouse_id'] ?? 0);
        if ($warehouseId > 0) {
            $where[] = 'r.warehouse_id = ?';
            $types .= 'i';
            $params[] = $warehouseId;
        }

        $status = trim((string) ($filters['status'] ?? ''));
        if ($status !== '') {
            $where[] = 'r.status = ?';
            $types .= 's';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $countSql = "SELECT COUNT(*) AS c FROM vp_sales_returns r WHERE {$whereSql}";
        $stmt = $this->conn->prepare($countSql);
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $total = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        $listSql = "SELECT r.*, u.name AS created_by_name, i.invoice_number
            FROM vp_sales_returns r
            LEFT JOIN vp_users u ON u.id = r.created_by AND u.is_deleted = 0
            LEFT JOIN vp_invoices i ON i.id = r.invoice_id
            WHERE {$whereSql}
            ORDER BY r.return_date DESC, r.id DESC
            LIMIT ? OFFSET ?";

        $stmt = $this->conn->prepare($listSql);
        if ($types !== '') {
            $typesList = $types . 'ii';
            $paramsList = array_merge($params, [$limit, $offset]);
            $stmt->bind_param($typesList, ...$paramsList);
        } else {
            $stmt->bind_param('ii', $limit, $offset);
        }
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $rows[] = $row;
            }
        }
        $stmt->close();

        return ['rows' => $rows, 'total' => $total];
    }

    public function cancelReturn(int $id): array
    {
        $row = $this->getById($id);
        if (!$row) {
            return ['success' => false, 'message' => 'Return not found.'];
        }
        if (strtolower((string) ($row['status'] ?? '')) === 'cancelled') {
            return ['success' => true, 'message' => 'Return already cancelled.'];
        }

        require_once __DIR__ . '/../order/stock.php';
        $stockModel = new Stock($this->conn);
        $warehouseId = (int) ($row['warehouse_id'] ?? 0);

        if (!empty($row['stock_applied'])) {
            $reverse = $stockModel->reverseSalesReturnStock($id, $warehouseId);
            if (empty($reverse['success'])) {
                return ['success' => false, 'message' => $reverse['message'] ?? 'Stock reversal failed.'];
            }
        }

        $stmt = $this->conn->prepare('UPDATE vp_sales_returns SET status = ? WHERE id = ?');
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed.'];
        }
        $status = 'cancelled';
        $stmt->bind_param('si', $status, $id);
        $stmt->execute();
        $stmt->close();

        return ['success' => true, 'message' => 'Sales return cancelled.'];
    }

    public function countForOrder(string $orderNumber): int
    {
        $orderNumber = trim($orderNumber);
        if ($orderNumber === '') {
            return 0;
        }
        $stmt = $this->conn->prepare(
            'SELECT COUNT(*) AS c FROM vp_sales_returns WHERE order_number = ? AND status = \'finalized\''
        );
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $orderNumber);
        $stmt->execute();
        $count = (int) ($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();

        return $count;
    }

    /**
     * @param array<int, array<string, mixed>> $orderRows
     * @param array<string, mixed> $invItem
     */
    private function findOrderRowForInvoiceItem(array $orderRows, array $invItem): ?array
    {
        $itemCode = trim((string) ($invItem['item_code'] ?? ''));
        foreach ($orderRows as $row) {
            if (trim((string) ($row['item_code'] ?? '')) === $itemCode) {
                return $row;
            }
        }

        return null;
    }

    /**
     * @param array<int, array<string, mixed>> $invoiceItems
     * @param array<string, mixed> $orderRow
     */
    private function findInvoiceItemForOrderRow(array $invoiceItems, array $orderRow): ?array
    {
        $itemCode = trim((string) ($orderRow['item_code'] ?? ''));
        foreach ($invoiceItems as $invItem) {
            if (trim((string) ($invItem['item_code'] ?? '')) === $itemCode) {
                return $invItem;
            }
        }

        return null;
    }

    private function lineMatchKey(string $itemCode, string $size, string $color, int $orderRowId): string
    {
        if ($orderRowId > 0) {
            return 'row:' . $orderRowId;
        }

        return strtolower($itemCode . '|' . $size . '|' . $color);
    }
}
