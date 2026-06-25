<?php
require_once __DIR__ . '/../product/product.php';

class Stock {
    private $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    /**
     * Decrement physical stock for each invoice line that has product_id set (after invoice is saved).
     * Idempotent: skips lines that already have an OUT movement (ref_type INVOICE) for this invoice.
     *
     * @param int $invoice_id
     * @param int|null $warehouse_id Falls back to vp_invoices.warehouse_id, then session warehouse_id
     * @param bool $warehouse_only When true, never fall back to session; warehouse must be resolved
     */
    public function updateStockByInvoiceId(int $invoice_id, ?int $warehouse_id = null, bool $warehouse_only = false)
    {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id'];
        }

        $warehouse_id = $this->resolveInvoiceWarehouseId($invoice_id, $warehouse_id, $warehouse_only);
        if ($warehouse_only && $warehouse_id <= 0) {
            return ['success' => false, 'message' => 'Warehouse required for stock deduction.', 'applied' => 0];
        }

        $sql = "SELECT product_id, quantity FROM vp_invoice_items WHERE invoice_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            $stmt->close();
            return ['success' => false, 'message' => 'Query failed'];
        }

        $refStr = (string)$invoice_id;
        $errors = [];
        $skipped = 0;
        $applied = 0;
        $linesWithQty = 0;
        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements
             WHERE product_id = ? AND movement_type = 'OUT' AND ref_type = 'INVOICE' AND ref_id = ?
             LIMIT 1"
        );
        if (!$dupStmt) {
            $stmt->close();
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error];
        }

        while ($row = $result->fetch_assoc()) {
            $prodId = (int)($row['product_id'] ?? 0);
            $qty = (int) round((float)($row['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $linesWithQty++;
            if ($prodId <= 0) {
                $errors[] = 'Invoice line (qty ' . $qty . '): product_id missing — cannot deduct stock';
                continue;
            }
            $dupStmt->bind_param('is', $prodId, $refStr);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                $skipped++;
                continue;
            }
            $res = $this->reduceStock($prodId, $qty, $invoice_id, 'INVOICE', $warehouse_id);
            if (empty($res['success'])) {
                $errors[] = "Product $prodId: " . ($res['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
        $dupStmt->close();
        $stmt->close();
        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied, 'skipped_lines' => $skipped];
        }
        if ($linesWithQty > 0 && $applied === 0 && $skipped === 0) {
            return ['success' => false, 'message' => 'Physical stock not reduced: no invoice lines could be processed.', 'applied' => 0, 'skipped_lines' => $skipped];
        }
        return ['success' => true, 'message' => 'Physical stock updated for invoice', 'applied' => $applied, 'skipped_lines' => $skipped];
    }

    /**
     * Reverse invoice stock deductions: one IN movement per line (ref_type INVOICE_CANCEL).
     * Only runs when a matching OUT movement exists for this invoice (ref_type INVOICE); otherwise the line is skipped.
     * Idempotent per product_id + invoice id for INVOICE_CANCEL rows.
     */
    /**
     * @param int|null $warehouse_id Explicit warehouse; when null uses invoice / prior OUT movement
     * @param bool $warehouse_only When true, never fall back to session; each line must resolve a warehouse
     */
    public function restoreStockByInvoiceId(int $invoice_id, ?int $warehouse_id = null, bool $warehouse_only = false) {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id'];
        }
        $defaultWarehouseId = $this->resolveInvoiceWarehouseId($invoice_id, $warehouse_id, $warehouse_only);
        $sql = "SELECT product_id, quantity FROM vp_invoice_items WHERE invoice_id = ?";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error];
        }
        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            $stmt->close();
            return ['success' => false, 'message' => 'Query failed'];
        }
        $refStr = (string)$invoice_id;
        $errors = [];
        $applied = 0;
        $skipped = 0;
        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements WHERE ref_type = 'INVOICE_CANCEL' AND ref_id = ? AND product_id = ? LIMIT 1"
        );
        if (!$dupStmt) {
            $stmt->close();
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error];
        }
        $priorOutStmt = $this->conn->prepare(
            "SELECT id, warehouse_id FROM vp_stock_movements WHERE product_id = ? AND movement_type = 'OUT' AND ref_type = 'INVOICE' AND ref_id = ? LIMIT 1"
        );
        if (!$priorOutStmt) {
            $dupStmt->close();
            $stmt->close();
            return ['success' => false, 'message' => 'Prepare failed (prior OUT check): ' . $this->conn->error];
        }
        while ($row = $result->fetch_assoc()) {
            $prodId = (int)($row['product_id'] ?? 0);
            $qty = (int) round((float)($row['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            if ($prodId <= 0) {
                continue;
            }
            $dupStmt->bind_param('si', $refStr, $prodId);
            $dupStmt->execute();
            $dupRes = $dupStmt->get_result();
            if ($dupRes && $dupRes->num_rows > 0) {
                $skipped++;
                continue;
            }
            $priorOutStmt->bind_param('is', $prodId, $refStr);
            $priorOutStmt->execute();
            $priorOutRes = $priorOutStmt->get_result();
            $priorOutRow = $priorOutRes ? $priorOutRes->fetch_assoc() : null;
            if (!$priorOutRow) {
                $skipped++;
                continue;
            }
            $lineWarehouseId = (int)($priorOutRow['warehouse_id'] ?? 0);
            if ($lineWarehouseId <= 0) {
                $lineWarehouseId = $defaultWarehouseId;
            }
            if ($warehouse_only && $lineWarehouseId <= 0) {
                $errors[] = "Product $prodId: warehouse could not be resolved for restore";
                continue;
            }
            $res = $this->addStockMovement($prodId, $qty, 'IN', $invoice_id, 'INVOICE_CANCEL', $lineWarehouseId);
            if (empty($res['success'])) {
                $errors[] = "Product $prodId: " . ($res['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
        $dupStmt->close();
        $priorOutStmt->close();
        $stmt->close();
        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied, 'skipped_lines' => $skipped];
        }
        return ['success' => true, 'message' => 'Stock restored for invoice', 'applied' => $applied, 'skipped_lines' => $skipped];
    }

    /**
     * Resolve warehouse for invoice stock: explicit param → vp_invoices.warehouse_id → session (unless warehouse_only).
     */
    private function resolveInvoiceWarehouseId(int $invoice_id, ?int $warehouse_id, bool $warehouse_only): int
    {
        if ($warehouse_id !== null && (int)$warehouse_id > 0) {
            return (int)$warehouse_id;
        }
        $whStmt = $this->conn->prepare('SELECT warehouse_id FROM vp_invoices WHERE id = ? LIMIT 1');
        if ($whStmt) {
            $whStmt->bind_param('i', $invoice_id);
            $whStmt->execute();
            $whRes = $whStmt->get_result();
            $whRow = $whRes ? $whRes->fetch_assoc() : null;
            $whStmt->close();
            if ($whRow && (int)($whRow['warehouse_id'] ?? 0) > 0) {
                return (int)$whRow['warehouse_id'];
            }
        }
        if ($warehouse_only) {
            return 0;
        }
        $sessionWh = (int)($_SESSION['warehouse_id'] ?? 0);
        if ($sessionWh <= 0 && !empty($_SESSION['user']['warehouse_id'])) {
            $sessionWh = (int)$_SESSION['user']['warehouse_id'];
        }
        return $sessionWh;
    }

    /**
     * @param list<array{product_id:int, quantity:float|int, item_code?:string, order_number?:string}> $lines
     * @return array{success:bool, message:string, issues?:list<array<string,mixed>>}
     */
    public function validateStockAvailabilityForLines(array $lines, ?int $warehouse_id = null, bool $warehouse_only = false): array
    {
        if ($warehouse_only && ($warehouse_id === null || (int)$warehouse_id <= 0)) {
            return [
                'success' => false,
                'message' => 'Warehouse required for stock validation.',
                'issues' => [],
            ];
        }

        $wh = ($warehouse_id !== null && (int)$warehouse_id > 0) ? (int)$warehouse_id : 0;
        $issues = [];
        $productModel = new product($this->conn);

        foreach ($lines as $line) {
            $qty = (int)round((float)($line['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            $productId = (int)($line['product_id'] ?? 0);
            $label = trim((string)($line['item_code'] ?? ''));
            if ($label === '') {
                $label = trim((string)($line['order_number'] ?? ''));
            }
            if ($label === '') {
                $label = 'product #' . $productId;
            }

            if ($productId <= 0) {
                $issues[] = [
                    'product_id' => 0,
                    'item_code' => $label,
                    'requested' => $qty,
                    'available' => 0,
                    'message' => $label . ': product not linked (missing product_id)',
                ];
                continue;
            }

            $available = $this->getAvailableStockForProduct($productId, $wh, $productModel);
            if ($qty > $available) {
                $issues[] = [
                    'product_id' => $productId,
                    'item_code' => $label,
                    'requested' => $qty,
                    'available' => $available,
                    'message' => $label . ': insufficient stock (available ' . $available . ', requested ' . $qty . ')',
                ];
            }
        }

        if ($issues !== []) {
            return [
                'success' => false,
                'message' => $this->formatStockValidationMessage($issues),
                'issues' => $issues,
            ];
        }

        return ['success' => true, 'message' => 'Stock available for all lines'];
    }

    /**
     * Validate invoice lines before finalizing (skips lines already deducted for this invoice).
     */
    public function validateStockAvailabilityForInvoiceId(int $invoice_id, ?int $warehouse_id = null, bool $warehouse_only = false): array
    {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id', 'issues' => []];
        }

        $warehouse_id = $this->resolveInvoiceWarehouseId($invoice_id, $warehouse_id, $warehouse_only);
        if ($warehouse_only && $warehouse_id <= 0) {
            return [
                'success' => false,
                'message' => 'Warehouse required for stock validation.',
                'issues' => [],
            ];
        }

        $sql = 'SELECT product_id, quantity, item_code, order_number FROM vp_invoice_items WHERE invoice_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'Prepare failed: ' . $this->conn->error, 'issues' => []];
        }
        $stmt->bind_param('i', $invoice_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result) {
            $stmt->close();
            return ['success' => false, 'message' => 'Query failed', 'issues' => []];
        }

        $refStr = (string)$invoice_id;
        $linesToValidate = [];
        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements
             WHERE product_id = ? AND movement_type = 'OUT' AND ref_type = 'INVOICE' AND ref_id = ?
             LIMIT 1"
        );
        if (!$dupStmt) {
            $stmt->close();
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error, 'issues' => []];
        }

        while ($row = $result->fetch_assoc()) {
            $prodId = (int)($row['product_id'] ?? 0);
            $qty = (int)round((float)($row['quantity'] ?? 0));
            if ($qty <= 0) {
                continue;
            }
            if ($prodId > 0) {
                $dupStmt->bind_param('is', $prodId, $refStr);
                $dupStmt->execute();
                $dupRes = $dupStmt->get_result();
                if ($dupRes && $dupRes->num_rows > 0) {
                    continue;
                }
            }
            $linesToValidate[] = [
                'product_id' => $prodId,
                'quantity' => $qty,
                'item_code' => (string)($row['item_code'] ?? ''),
                'order_number' => (string)($row['order_number'] ?? ''),
            ];
        }
        $dupStmt->close();
        $stmt->close();

        if ($linesToValidate === []) {
            return ['success' => true, 'message' => 'No stock deduction required'];
        }

        return $this->validateStockAvailabilityForLines(
            $linesToValidate,
            $warehouse_id > 0 ? $warehouse_id : null,
            $warehouse_only
        );
    }

    private function getAvailableStockForProduct(int $productId, int $warehouseId, ?product $productModel = null): int
    {
        if ($productId <= 0) {
            return 0;
        }
        if ($productModel === null) {
            $productModel = new product($this->conn);
        }
        if ($warehouseId > 0) {
            $skuStmt = $this->conn->prepare('SELECT sku FROM vp_products WHERE id = ? LIMIT 1');
            if (!$skuStmt) {
                return 0;
            }
            $skuStmt->bind_param('i', $productId);
            $skuStmt->execute();
            $skuRow = $skuStmt->get_result()->fetch_assoc();
            $skuStmt->close();
            $sku = trim((string)($skuRow['sku'] ?? ''));
            if ($sku === '') {
                return 0;
            }

            return max(0, (int)floor($productModel->getLatestRunningStockForSkuWarehouse($sku, $warehouseId)));
        }

        return $productModel->getTotalPhysicalStockAcrossWarehouses($productId);
    }

    /**
     * @param list<array<string,mixed>> $issues
     */
    private function formatStockValidationMessage(array $issues): string
    {
        $messages = [];
        foreach ($issues as $issue) {
            $msg = (string)($issue['message'] ?? '');
            if ($msg !== '') {
                $messages[] = $msg;
            }
        }
        if ($messages === []) {
            return 'Insufficient stock for one or more invoice lines.';
        }
        $summary = implode('; ', array_slice($messages, 0, 5));
        if (count($messages) > 5) {
            $summary .= '; and ' . (count($messages) - 5) . ' more';
        }

        return 'Cannot create final invoice: ' . $summary;
    }

    /**
     * Generic helper that adjusts stock and writes a movement entry.
     * Delegates most work to the product model.
     */
    public function addStockMovement($item_id, $quantity, $movement_type, $reference_id, $ref_type = null, $warehouse_id = null) {
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
        $wh = ($warehouse_id !== null && (int)$warehouse_id > 0)
            ? (int)$warehouse_id
            : (int)($_SESSION['warehouse_id'] ?? 0);
        $reason = 'Stock movement';
        if ($movementTypeNormalized === 'OUT' && $refTypeResolved === 'INVOICE') {
            $reason = 'Invoice #' . $refIdStr;
        } elseif ($movementTypeNormalized === 'OUT' && $refTypeResolved === 'ORDER') {
            $reason = 'Order ' . $refIdStr;
        } elseif ($movementTypeNormalized === 'IN' && $refTypeResolved === 'INVOICE_CANCEL') {
            $reason = 'Invoice cancelled / dispatch cancelled #' . $refIdStr;
        }

        $data = [
            'product_id' => $prod['id'],
            'sku' => $prod['sku'],
            'item_code' => $prod['item_code'],
            'size' => $prod['size'],
            'color' => $prod['color'],
            'warehouse_id' => $wh,
            'location' => '',
            'movement_type' => $movementTypeNormalized,
            'quantity' => $quantity,
            'user_id' => $_SESSION['user']['id'] ?? 0,
            'reason' => $reason,
            'ref_type' => $refTypeResolved,
            'ref_id' => $refIdStr,
            'strict_stock_check' => in_array($refTypeResolved, ['INVOICE', 'ORDER'], true),
        ];

        $productModel = new product($this->conn);
        return $productModel->insertStockMovement($data);
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

    public function reduceStock($item_id, $quantity, $reference_id = 0, $ref_type = 'INVOICE', $warehouse_id = null) {
        return $this->addStockMovement($item_id, $quantity, 'OUT', $reference_id, $ref_type, $warehouse_id);
    }
}

?>