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
            $res = $this->reduceStock($prodId, $qty, (string)$invoice_id, 'INVOICE');
            if (empty($res['success'])) {
                $errors[] = "Product $prodId: " . ($res['message'] ?? 'error');
            } else {
                $applied++;
            }
        }
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
     * Idempotent per product_id + invoice id so cancel invoice / cancel dispatch can both call safely.
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
            $res = $this->addStockMovement($prodId, $qty, 'IN', $invoice_id, 'INVOICE_CANCEL');
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

        $defaultRef = (($movement_type === 'IN') || ($movement_type === 'TRANSFER_IN')) ? 'GRN' : 'ORDER';
        $refTypeResolved = $ref_type !== null && $ref_type !== '' ? $ref_type : $defaultRef;
        $refIdStr = $reference_id !== null && $reference_id !== '' ? (string)$reference_id : '0';
        $wh = (int)($_SESSION['warehouse_id'] ?? 0);
        $reason = 'Stock movement';
        if ($movement_type === 'OUT' && $refTypeResolved === 'INVOICE') {
            $reason = 'Invoice #' . $refIdStr;
        } elseif ($movement_type === 'OUT' && $refTypeResolved === 'ORDER') {
            $reason = 'Order ' . $refIdStr;
        } elseif ($movement_type === 'IN' && $refTypeResolved === 'INVOICE_CANCEL') {
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
            'movement_type' => $movement_type,
            'quantity' => $quantity,
            'user_id' => $_SESSION['user']['id'] ?? 0,
            'reason' => $reason,
            'ref_type' => $refTypeResolved,
            'ref_id' => $refIdStr,
        ];

        $productModel = new Product($this->conn);
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

    public function reduceStock($item_id, $quantity, $reference_id = 0, $ref_type = 'ORDER') {
        return $this->addStockMovement($item_id, $quantity, 'OUT', $reference_id, $ref_type);
    }
}

?>