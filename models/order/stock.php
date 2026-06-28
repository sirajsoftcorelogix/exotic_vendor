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
        $dupStmt = $this->conn->prepare(
            "SELECT id FROM vp_stock_movements WHERE ref_type = 'INVOICE' AND ref_id = ? AND product_id = ? LIMIT 1"
        );
        if (!$dupStmt) {
            $stmt->close();
            return ['success' => false, 'message' => 'Prepare failed (dup check): ' . $this->conn->error];
        }
        $errors = [];
        $skipped = 0;
        $applied = 0;
        $linesWithQty = 0;
        while ($row = $result->fetch_assoc()) {
            $prodId = (int)($row['product_id'] ?? 0);
            $qty = (int) round((float)($row['quantity'] ?? 0));
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
        $stmt->close();
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
     * Only runs when a matching OUT movement exists for this invoice (ref_type INVOICE); otherwise the line is skipped.
     * Idempotent per product_id + invoice id for INVOICE_CANCEL rows.
     */
    public function restoreStockByInvoiceId(int $invoice_id) {
        $invoice_id = (int)$invoice_id;
        if ($invoice_id <= 0) {
            return ['success' => false, 'message' => 'Invalid invoice id'];
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
            "SELECT id FROM vp_stock_movements WHERE product_id = ? AND movement_type = 'OUT' AND ref_type = 'INVOICE' AND ref_id = ? LIMIT 1"
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
            if (!$priorOutRes || $priorOutRes->num_rows < 1) {
                $skipped++;
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
        $priorOutStmt->close();
        $stmt->close();
        if (count($errors)) {
            return ['success' => false, 'message' => implode('; ', $errors), 'applied' => $applied, 'skipped_lines' => $skipped];
        }
        return ['success' => true, 'message' => 'Stock restored for invoice', 'applied' => $applied, 'skipped_lines' => $skipped];
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
        }
        $location = $wh > 0 ? $this->warehouseLocationLabel($wh) : '';
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
}

?>