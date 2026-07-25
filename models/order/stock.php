<?php
require_once __DIR__ . '/../product/product.php';

class Stock {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Decrement stock for every invoice item matching the supplied order_number.
     * Calls addStockMovement() with movement_type='OUT' and reference_type='ORDER'.
     */
    public function updateStockByOrderNumber($order_number) {
        $order_number = trim($order_number);
        if ($order_number === '') {
            return ['success' => false, 'message' => 'Empty order number'];
        }
        $sql = "SELECT product_id, quantity FROM vp_invoice_items WHERE order_number = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('s', $order_number);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            return ['success' => false, 'message' => 'Query failed'];
        }
        $errors = [];
        while ($row = $result->fetch_assoc()) {
            $prodId = intval($row['product_id']);
            $qty = (int) round((float)($row['quantity'] ?? 0));
            if ($qty <= 0) continue;
            $res = $this->reduceStock($prodId, $qty, $order_number, 'ORDER');
            if (empty($res['success'])) {
                $errors[] = "Item $prodId: " . ($res['message'] ?? 'error');
            }
        }
        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors)];
        }
        return ['success' => true, 'message' => 'Stock updated for order'];
    }

    /**
     * Decrement stock for each invoice line that has product_id set (after invoice is saved).
     */
    /**
     * Deduct physical stock when a final invoice is created (skipped for proforma).
     */
    public function applyInvoiceStockOnCreate(int $invoice_id, string $status = 'final') {
        if (strtolower(trim($status)) === 'proforma') {
            return ['success' => true, 'message' => 'Proforma invoice — stock not deducted', 'applied' => 0, 'skipped_lines' => 0];
        }
        return $this->updateStockByInvoiceId($invoice_id);
    }

    public function updateStockByInvoiceId(int $invoice_id) {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id'];
        }

        $refStr = (string)$invoice_id;
        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements WHERE ref_type = 'INVOICE' AND ref_id = ? AND product_id = ? LIMIT 1"
        );
        if (!$dupStmt) {
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error];
        }

        $errors = [];
        $skipped = 0;
        $applied = 0;
        $linesWithQty = 0;
        foreach ($this->fetchInvoiceStockLines($invoice_id) as $line) {
            $prodId = (int)($line['product_id'] ?? 0);
            $qty = (int)($line['quantity'] ?? 0);
            if ($qty <= 0) {
                continue;
            }
            $linesWithQty++;
            if ($prodId <= 0) {
                $skipped++;
                continue;
            }
            $dupStmt->bind_param('si', $refStr, $prodId);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                $skipped++;
                continue;
            }
            $res = $this->reduceStock($prodId, $qty, $refStr, 'INVOICE');
            if (empty($res['success'])) {
                $errors[] = "Product $prodId: " . ($res['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
        $dupStmt->close();

        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied, 'skipped_lines' => $skipped];
        }
        if ($linesWithQty > 0 && $applied === 0) {
            return ['success' => false, 'message' => 'Stock not reduced: invoice lines missing product_id (could not match vp_orders to vp_products).', 'applied' => 0, 'skipped_lines' => $skipped];
        }
        return ['success' => true, 'message' => 'Stock updated for invoice', 'applied' => $applied, 'skipped_lines' => $skipped];
    }

    /**
     * Reverse invoice stock deductions: one IN movement per line (ref_type INVOICE_CANCEL).
     * Idempotent per product_id + invoice id for INVOICE_CANCEL rows.
     */
    public function restoreStockByInvoiceId(int $invoice_id) {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id'];
        }

        $refStr = (string)$invoice_id;
        $errors = [];
        $applied = 0;
        $skipped = 0;
        $skippedAlreadyRestored = 0;
        $skippedNoPriorOut = 0;
        $linesWithQty = 0;
        $linesResolvable = 0;

        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements WHERE ref_type = 'INVOICE_CANCEL' AND ref_id = ? AND product_id = ? LIMIT 1"
        );
        if (!$dupStmt) {
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error];
        }

        foreach ($this->fetchInvoiceStockLines($invoice_id) as $line) {
            $prodId = (int)($line['product_id'] ?? 0);
            $qty = (int)($line['quantity'] ?? 0);
            $orderNumber = trim((string)($line['order_number'] ?? ''));
            if ($qty <= 0) {
                continue;
            }
            $linesWithQty++;
            if ($prodId <= 0) {
                $skipped++;
                continue;
            }
            $linesResolvable++;

            $dupStmt->bind_param('si', $refStr, $prodId);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                $skipped++;
                $skippedAlreadyRestored++;
                continue;
            }

            if (!$this->hasPriorInvoiceStockOut($prodId, $invoice_id, $orderNumber)) {
                $skipped++;
                $skippedNoPriorOut++;
                continue;
            }

            $res = $this->addStockMovement($prodId, $qty, 'IN', $invoice_id, 'INVOICE_CANCEL');
            if (empty($res['success'])) {
                $errors[] = "Product $prodId: " . ($res['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
        $dupStmt->close();

        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied, 'skipped_lines' => $skipped];
        }
        if ($linesResolvable > 0 && $applied === 0 && $skippedNoPriorOut > 0) {
            return [
                'success' => false,
                'message' => 'Stock not restored: no matching invoice OUT movement found (stock may not have been deducted when invoice was created).',
                'applied' => 0,
                'skipped_lines' => $skipped,
            ];
        }
        if ($applied === 0 && $skippedAlreadyRestored > 0 && $skippedNoPriorOut === 0) {
            return [
                'success' => true,
                'message' => 'Stock already restored for this invoice',
                'applied' => 0,
                'skipped_lines' => $skipped,
            ];
        }
        return ['success' => true, 'message' => 'Stock restored for invoice', 'applied' => $applied, 'skipped_lines' => $skipped];
    }

    /**
     * @return list<array{product_id:int,quantity:int,order_number:string,item_code:string,size:string,color:string}>
     */
    private function fetchInvoiceStockLines(int $invoice_id): array
    {
        $invoice_id = (int) $invoice_id;
        if ($invoice_id <= 0) {
            return [];
        }

        $sql = 'SELECT id, product_id, quantity, order_number, item_code FROM vp_invoice_items WHERE invoice_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        if ($rows === []) {
            return [];
        }

        $productModel = new product($this->conn);
        $orderByInvoiceStmt = $this->conn->prepare(
            'SELECT size, color FROM vp_orders WHERE invoice_id = ? AND order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1'
        );
        $orderByNumberStmt = $this->conn->prepare(
            'SELECT size, color FROM vp_orders WHERE order_number = ? AND item_code = ? ORDER BY id ASC LIMIT 1'
        );

        $lines = [];
        foreach ($rows as $row) {
            $orderNumber = trim((string)($row['order_number'] ?? ''));
            $itemCode = trim((string)($row['item_code'] ?? ''));
            $size = '';
            $color = '';

            if ($orderByInvoiceStmt && $orderNumber !== '' && $itemCode !== '') {
                $orderByInvoiceStmt->bind_param('iss', $invoice_id, $orderNumber, $itemCode);
                $orderByInvoiceStmt->execute();
                $orderRow = $orderByInvoiceStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string)($orderRow['size'] ?? ''));
                    $color = trim((string)($orderRow['color'] ?? ''));
                }
            }
            if ($size === '' && $color === '' && $orderByNumberStmt && $orderNumber !== '' && $itemCode !== '') {
                $orderByNumberStmt->bind_param('ss', $orderNumber, $itemCode);
                $orderByNumberStmt->execute();
                $orderRow = $orderByNumberStmt->get_result()->fetch_assoc();
                if ($orderRow) {
                    $size = trim((string)($orderRow['size'] ?? ''));
                    $color = trim((string)($orderRow['color'] ?? ''));
                }
            }

            $prodId = (int)($row['product_id'] ?? 0);
            if ($prodId <= 0 && $orderNumber !== '' && $itemCode !== '') {
                $prodId = $productModel->getProductIdForInvoiceLine($orderNumber, $itemCode, $size, $color);
            }

            $lines[] = [
                'product_id' => $prodId,
                'quantity' => (int) round((float)($row['quantity'] ?? 0)),
                'order_number' => $orderNumber,
                'item_code' => $itemCode,
                'size' => $size,
                'color' => $color,
            ];
        }

        if ($orderByInvoiceStmt) {
            $orderByInvoiceStmt->close();
        }
        if ($orderByNumberStmt) {
            $orderByNumberStmt->close();
        }

        return $lines;
    }

    private function hasPriorInvoiceStockOut(int $productId, int $invoiceId, string $orderNumber): bool
    {
        $productId = (int) $productId;
        $invoiceId = (int) $invoiceId;
        $orderNumber = trim($orderNumber);
        if ($productId <= 0) {
            return false;
        }

        $invoiceRef = (string) $invoiceId;
        $stmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements
             WHERE product_id = ? AND movement_type = 'OUT'
             AND (
                 (ref_type = 'INVOICE' AND ref_id = ?)
                 OR (ref_type = 'ORDER' AND ref_id = ?)
             )
             LIMIT 1"
        );
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('iss', $productId, $invoiceRef, $orderNumber);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return !empty($row);
    }

    /**
     * Generic helper that adjusts stock and writes a movement entry.
     * Delegates most work to the product model.
     */
    public function addStockMovement($item_id, $quantity, $movement_type, $reference_id, $ref_type = null) {
        // retrieve product data
        $sql = "SELECT id, sku, item_code, size, color FROM vp_products WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        if (!$prod) {
            return ['success' => false, 'message' => 'Product not found'];
        }

        $movementTypeNormalized = strtoupper(trim((string)$movement_type));
        $quantity = abs((int)$quantity);
        $defaultRef = (($movementTypeNormalized === 'IN') || ($movementTypeNormalized === 'TRANSFER_IN')) ? 'GRN' : 'ORDER';
        $refTypeResolved = strtoupper(trim((string)($ref_type !== null && $ref_type !== '' ? $ref_type : $defaultRef)));
        // Safety: cancellation flow must always restore stock, never deduct.
        if ($refTypeResolved === 'INVOICE_CANCEL') {
            $movementTypeNormalized = 'IN';
        }
        $refIdStr = $reference_id !== null && $reference_id !== '' ? (string)$reference_id : '0';
        $wh = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($refTypeResolved === 'INVOICE' || $refTypeResolved === 'INVOICE_CANCEL') {
            $wh = $this->resolveWarehouseIdForInvoice((int)$refIdStr);
        } elseif ($refTypeResolved === 'SALES_RETURN' || $refTypeResolved === 'SALES_RETURN_CANCEL') {
            $wh = $this->resolveWarehouseIdForSalesReturn((int)$refIdStr);
        }
        $location = $wh > 0 ? $this->warehouseLocationLabel($wh) : '';
        $reason = 'Stock movement';
        if ($movementTypeNormalized === 'OUT' && $refTypeResolved === 'INVOICE') {
            $reason = 'Invoice #' . $refIdStr;
        } elseif ($movementTypeNormalized === 'OUT' && $refTypeResolved === 'ORDER') {
            $reason = 'Order ' . $refIdStr;
        } elseif ($movementTypeNormalized === 'IN' && $refTypeResolved === 'INVOICE_CANCEL') {
            $reason = 'Invoice cancelled / dispatch cancelled #' . $refIdStr;
        } elseif ($movementTypeNormalized === 'IN' && $refTypeResolved === 'SALES_RETURN') {
            $reason = 'Sales return #' . $refIdStr;
        } elseif ($movementTypeNormalized === 'OUT' && $refTypeResolved === 'SALES_RETURN_CANCEL') {
            $reason = 'Sales return cancelled #' . $refIdStr;
        }

        $data = [
            'product_id' => $prod['id'],
            'sku' => $prod['sku'],
            'item_code' => $prod['item_code'],
            'size' => $prod['size'],
            'color' => $prod['color'],
            'warehouse_id' => $wh,
            'location' => $location,
            'movement_type' => $movementTypeNormalized,
            'strict_stock_check' => true,
            'quantity' => $quantity,
            'user_id' => $_SESSION['user']['id'] ?? 0,
            'reason' => $reason,
            'ref_type' => $refTypeResolved,
            'ref_id' => $refIdStr,
        ];

        $productModel = new product($this->conn);
        return $productModel->insertStockMovement($data);
    }

    private function resolveWarehouseIdForInvoice(int $invoiceId): int
    {
        if ($invoiceId > 0) {
            $stmt = $this->conn->prepare('SELECT warehouse_id FROM vp_invoices WHERE id = ? LIMIT 1');
            if ($stmt) {
                $stmt->bind_param('i', $invoiceId);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                if ($row && (int)($row['warehouse_id'] ?? 0) > 0) {
                    return (int)$row['warehouse_id'];
                }
            }
        }
        return (int)($_SESSION['warehouse_id'] ?? 0);
    }

    private function warehouseLocationLabel(int $warehouseId): string
    {
        if ($warehouseId <= 0) {
            return '';
        }
        $stmt = $this->conn->prepare('SELECT address_title FROM exotic_address WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return '';
        }
        $stmt->bind_param('i', $warehouseId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return trim((string)($row['address_title'] ?? ''));
    }

    /**
     * Return stock record by product/item id (from vp_stock table).
     */
    public function getStockByItemId($item_id) {
        $sql = "SELECT * FROM vp_stock WHERE sku IN (SELECT sku FROM vp_products WHERE id = ?) LIMIT 1";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param('i', $item_id);
        $stmt->execute();
        $res = $stmt->get_result();
        return $res ? $res->fetch_assoc() : null;
    }

    /**
     * Convenience wrapper for adding stock (movement_type=IN).
     */
    public function addStockByItemId($item_id, $quantity) {
        return $this->addStockMovement($item_id, $quantity, 'IN', 0);
    }

    /**
     * Generic high-level helpers for callers.
     */
    public function addStock($item_id, $quantity, $reference_id = 0) {
        return $this->addStockMovement($item_id, $quantity, 'IN', $reference_id);
    }

    public function reduceStock($item_id, $quantity, $reference_id = 0, $ref_type = 'ORDER') {
        return $this->addStockMovement($item_id, $quantity, 'OUT', $reference_id, $ref_type);
    }

    /**
     * Restore stock for a finalized sales return (partial qty per line).
     *
     * @return array{success:bool,message:string,applied_lines:int,skipped_lines:int,line_results:array<int,array<string,mixed>>}
     */
    public function applySalesReturnStockIn(int $salesReturnId, int $warehouseId): array
    {
        $salesReturnId = (int) $salesReturnId;
        if ($salesReturnId <= 0) {
            return ['success' => false, 'message' => 'Invalid sales return id', 'applied_lines' => 0, 'skipped_lines' => 0, 'line_results' => []];
        }

        $header = $this->fetchSalesReturnHeader($salesReturnId);
        if (!$header) {
            return ['success' => false, 'message' => 'Sales return not found', 'applied_lines' => 0, 'skipped_lines' => 0, 'line_results' => []];
        }

        $invoiceId = (int) ($header['invoice_id'] ?? 0);
        $orderNumber = trim((string) ($header['order_number'] ?? ''));
        if ($warehouseId <= 0) {
            $warehouseId = (int) ($header['warehouse_id'] ?? 0);
        }

        $lines = $this->fetchSalesReturnItems($salesReturnId);
        $productModel = new product($this->conn);
        $refStr = (string) $salesReturnId;
        $applied = 0;
        $skipped = 0;
        $errors = [];
        $lineResults = [];

        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements
             WHERE ref_type = 'SALES_RETURN' AND ref_id = ? AND product_id = ?
             LIMIT 1"
        );

        foreach ($lines as $line) {
            $itemId = (int) ($line['id'] ?? 0);
            $returnQty = (int) round((float) ($line['return_qty'] ?? 0));
            if ($returnQty <= 0) {
                continue;
            }

            if ((float) ($line['stock_applied_qty'] ?? 0) > 0) {
                $skipped++;
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => 'Already applied'];
                continue;
            }

            $prodId = (int) ($line['product_id'] ?? 0);
            if ($prodId <= 0) {
                $prodId = (int) $productModel->getProductIdForInvoiceLine(
                    $orderNumber,
                    (string) ($line['item_code'] ?? ''),
                    (string) ($line['size'] ?? ''),
                    (string) ($line['color'] ?? '')
                );
            }

            if ($prodId <= 0) {
                $skipped++;
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => 'Product not resolved'];
                continue;
            }

            if ($dupStmt) {
                $dupStmt->bind_param('si', $refStr, $prodId);
                $dupStmt->execute();
                $dupRes = $dupStmt->get_result();
                if ($dupRes && $dupRes->num_rows > 0) {
                    $skipped++;
                    $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => 'Already applied'];
                    continue;
                }
            }

            if (!$this->hasPriorInvoiceStockOut($prodId, $invoiceId, $orderNumber)) {
                $skipped++;
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => 'No prior stock OUT'];
                continue;
            }

            $remainingOut = $this->getRemainingReturnableStockQty($prodId, $invoiceId, $orderNumber);
            if ($remainingOut <= 0) {
                $skipped++;
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => 'No returnable stock remaining'];
                continue;
            }

            $qtyToApply = min($returnQty, $remainingOut);
            $res = $this->addStockMovement($prodId, $qtyToApply, 'IN', $salesReturnId, 'SALES_RETURN');
            if (empty($res['success'])) {
                $errors[] = 'Product ' . $prodId . ': ' . ($res['message'] ?? 'error');
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => 0, 'skipped' => true, 'message' => $res['message'] ?? 'error'];
            } else {
                $applied++;
                $lineResults[] = ['item_id' => $itemId, 'applied_qty' => $qtyToApply, 'skipped' => false, 'message' => 'Stock IN applied'];
            }
        }

        if ($dupStmt) {
            $dupStmt->close();
        }

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode('; ', $errors),
                'applied_lines' => $applied,
                'skipped_lines' => $skipped,
                'line_results' => $lineResults,
            ];
        }

        return [
            'success' => true,
            'message' => $applied > 0 ? 'Stock restored for sales return' : 'Return saved; no stock movements applied',
            'applied_lines' => $applied,
            'skipped_lines' => $skipped,
            'line_results' => $lineResults,
        ];
    }

    /**
     * Reverse stock IN from a cancelled sales return.
     */
    public function reverseSalesReturnStock(int $salesReturnId, int $warehouseId): array
    {
        $salesReturnId = (int) $salesReturnId;
        if ($salesReturnId <= 0) {
            return ['success' => false, 'message' => 'Invalid sales return id'];
        }

        $stmt = $this->conn->prepare(
            "SELECT product_id, SUM(quantity) AS qty
             FROM vp_stock_movements
             WHERE ref_type = 'SALES_RETURN' AND ref_id = ? AND movement_type = 'IN'
             GROUP BY product_id"
        );
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed'];
        }
        $refStr = (string) $salesReturnId;
        $stmt->bind_param('s', $refStr);
        $stmt->execute();
        $res = $stmt->get_result();
        $errors = [];
        $applied = 0;
        while ($row = $res->fetch_assoc()) {
            $prodId = (int) ($row['product_id'] ?? 0);
            $qty = (int) round((float) ($row['qty'] ?? 0));
            if ($prodId <= 0 || $qty <= 0) {
                continue;
            }
            $out = $this->addStockMovement($prodId, $qty, 'OUT', $salesReturnId, 'SALES_RETURN_CANCEL');
            if (empty($out['success'])) {
                $errors[] = 'Product ' . $prodId . ': ' . ($out['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
        $stmt->close();

        if ($errors !== []) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied];
        }

        return ['success' => true, 'message' => 'Sales return stock reversed', 'applied' => $applied];
    }

    private function resolveWarehouseIdForSalesReturn(int $salesReturnId): int
    {
        if ($salesReturnId <= 0) {
            return (int) ($_SESSION['warehouse_id'] ?? 0);
        }
        $stmt = $this->conn->prepare('SELECT warehouse_id FROM vp_sales_returns WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return (int) ($_SESSION['warehouse_id'] ?? 0);
        }
        $stmt->bind_param('i', $salesReturnId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($row && (int) ($row['warehouse_id'] ?? 0) > 0) {
            return (int) $row['warehouse_id'];
        }

        return (int) ($_SESSION['warehouse_id'] ?? 0);
    }

    /**
     * Remaining qty that can be returned IN for product/order (OUT minus prior return IN).
     */
    private function getRemainingReturnableStockQty(int $productId, int $invoiceId, string $orderNumber): int
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return 0;
        }

        $orderNumber = trim($orderNumber);
        $invoiceRef = (string) $invoiceId;
        $outQty = 0;

        $stmt = $this->conn->prepare(
            "SELECT COALESCE(SUM(quantity), 0) AS sq FROM vp_stock_movements
             WHERE product_id = ? AND movement_type = 'OUT'
             AND (
                 (ref_type = 'INVOICE' AND ref_id = ?)
                 OR (ref_type = 'ORDER' AND ref_id = ?)
             )"
        );
        if ($stmt) {
            $stmt->bind_param('iss', $productId, $invoiceRef, $orderNumber);
            $stmt->execute();
            $outQty = (int) round((float) ($stmt->get_result()->fetch_assoc()['sq'] ?? 0));
            $stmt->close();
        }

        $cancelIn = 0;
        if ($invoiceId > 0) {
            $stmt = $this->conn->prepare(
                "SELECT COALESCE(SUM(quantity), 0) AS sq FROM vp_stock_movements
                 WHERE product_id = ? AND movement_type = 'IN'
                 AND ref_type = 'INVOICE_CANCEL' AND ref_id = ?"
            );
            if ($stmt) {
                $stmt->bind_param('is', $productId, $invoiceRef);
                $stmt->execute();
                $cancelIn = (int) round((float) ($stmt->get_result()->fetch_assoc()['sq'] ?? 0));
                $stmt->close();
            }
        }

        $returnIn = 0;
        if ($orderNumber !== '') {
            $stmt = $this->conn->prepare(
                "SELECT COALESCE(SUM(sm.quantity), 0) AS sq
                 FROM vp_stock_movements sm
                 INNER JOIN vp_sales_returns sr ON sr.id = CAST(sm.ref_id AS UNSIGNED)
                 WHERE sm.product_id = ? AND sm.movement_type = 'IN'
                 AND sm.ref_type = 'SALES_RETURN' AND sr.order_number = ?
                 AND sr.status = 'finalized'"
            );
            if ($stmt) {
                $stmt->bind_param('is', $productId, $orderNumber);
                $stmt->execute();
                $returnIn = (int) round((float) ($stmt->get_result()->fetch_assoc()['sq'] ?? 0));
                $stmt->close();
            }
        }

        return max(0, $outQty - $cancelIn - $returnIn);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchSalesReturnHeader(int $salesReturnId): ?array
    {
        $stmt = $this->conn->prepare('SELECT * FROM vp_sales_returns WHERE id = ? LIMIT 1');
        if (!$stmt) {
            return null;
        }
        $stmt->bind_param('i', $salesReturnId);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $row ?: null;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchSalesReturnItems(int $salesReturnId): array
    {
        $stmt = $this->conn->prepare(
            'SELECT * FROM vp_sales_return_items WHERE sales_return_id = ? ORDER BY sort_order ASC, id ASC'
        );
        if (!$stmt) {
            return [];
        }
        $stmt->bind_param('i', $salesReturnId);
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
}

?>