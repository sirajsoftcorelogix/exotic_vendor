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
            $qty = (int)$row['quantity'];
            if ($qty <= 0) continue;
            $res = $this->reduceStock($prodId, $qty, $order_number);
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
     * Generic helper that adjusts stock and writes a movement entry.
     * Delegates most work to the product model.
     */
    public function addStockMovement($item_id, $quantity, $movement_type, $reference_id) {
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

        $data = [
            'product_id' => $prod['id'],
            'sku' => $prod['sku'],
            'item_code' => $prod['item_code'],
            'size' => $prod['size'],
            'color' => $prod['color'],
            'warehouse_id' => 0,              // default warehouse
            'location' => '',
            'movement_type' => $movement_type,
            'quantity' => $quantity,
            'user_id' => $_SESSION['user']['id'] ?? 0,
            'reason' => 'manual adjustment',
            'ref_type' => 'ORDER',
            'ref_id' => $reference_id
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

    public function reduceStock($item_id, $quantity, $reference_id = 0) {
        return $this->addStockMovement($item_id, $quantity, 'OUT', $reference_id);
    }
}

?>