<?php

require_once __DIR__ . '/../models/globals/AppSettings.php';
require_once __DIR__ . '/../models/product/StockMovement.php';

/**
 * Book purchase / stock replenishment algorithm (order import + product preview).
 *
 * numsold <= 1  → buy order quantity
 * numsold > 1   → if physical stock <= 25% of lookback sales, buy 50% of lookback sales
 */
class BookPurchaseReplenishment
{
    private const NUMSOLD_THRESHOLD = 1;
    private const STOCK_LOW_RATIO = 0.25;
    private const BUY_RATIO = 0.50;

    private mysqli $conn;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
    }

    public function isBookProduct(array $product): bool
    {
        $group = strtolower(trim((string) ($product['groupname'] ?? '')));

        return $group !== '' && strpos($group, 'book') !== false;
    }

    /**
     * @return array<string, mixed>
     */
    public function evaluate(array $product, int $orderQty = 0, ?int $physicalStock = null): array
    {
        $numsold = max(0, (int) ($product['numsold'] ?? 0));
        $physicalStock = $physicalStock ?? $this->resolvePhysicalStock($product);
        $orderQty = max(0, $orderQty);

        $base = [
            'eligible' => $this->isBookProduct($product),
            'numsold' => $numsold,
            'physical_stock' => $physicalStock,
            'order_qty' => $orderQty,
            'lookback_months' => 0,
            'lookback_source' => '',
            'total_sold_lookback' => 0,
            'stock_threshold' => 0,
            'recommended_buy_qty' => 0,
            'should_buy' => false,
            'branch' => 'none',
            'reason' => '',
        ];

        if (!$base['eligible']) {
            $base['reason'] = 'Not a book product.';

            return $base;
        }

        if ($numsold <= self::NUMSOLD_THRESHOLD) {
            $buyQty = $orderQty > 0 ? $orderQty : 0;
            $base['branch'] = 'direct_order_qty';
            $base['recommended_buy_qty'] = $buyQty;
            $base['should_buy'] = $buyQty > 0;
            $base['reason'] = $orderQty > 0
                ? 'Lifetime numsold is at or below 1 — buy the order quantity.'
                : 'Lifetime numsold is at or below 1 — replenishment uses order quantity on import.';

            return $base;
        }

        $lookback = $this->resolveLookbackMonths($product);
        $base['lookback_months'] = $lookback['months'];
        $base['lookback_source'] = $lookback['source'];

        if ($lookback['months'] <= 0) {
            $base['branch'] = 'demand_based';
            $base['reason'] = 'No stock replenishment months configured (product, publisher, vendor, or global).';

            return $base;
        }

        $totalSold = $this->fetchTotalSoldForLookback($product, $lookback['months']);
        $base['total_sold_lookback'] = $totalSold;
        $base['branch'] = 'demand_based';

        $threshold = (int) floor($totalSold * self::STOCK_LOW_RATIO);
        $base['stock_threshold'] = $threshold;

        if ($physicalStock > $threshold) {
            $base['reason'] = 'Physical stock is above 25% of lookback sales — no replenishment needed.';

            return $base;
        }

        $buyQty = (int) max(0, round($totalSold * self::BUY_RATIO));
        $base['recommended_buy_qty'] = $buyQty;
        $base['should_buy'] = $buyQty > 0;
        $base['reason'] = $buyQty > 0
            ? 'Physical stock is at or below 25% of lookback sales — buy 50% of lookback sales.'
            : 'Lookback sales are zero — nothing to buy.';

        return $base;
    }

    /**
     * After a successful order import line, optionally add to purchase_list.
     *
     * @return array<string, mixed>
     */
    public function processOrderImportLine(
        array $product,
        array $orderContext,
        object $productModel
    ): array {
        $orderQty = max(1, (int) ($orderContext['quantity'] ?? 1));
        $evaluation = $this->evaluate($product, $orderQty);

        $result = [
            'evaluation' => $evaluation,
            'purchase_list' => null,
        ];

        if (!$evaluation['should_buy'] || (int) $evaluation['recommended_buy_qty'] <= 0) {
            return $result;
        }

        $sku = trim((string) ($product['sku'] ?? $orderContext['sku'] ?? ''));
        $orderNumber = trim((string) ($orderContext['order_number'] ?? ''));
        $productId = (int) ($product['id'] ?? 0);

        if ($sku === '' || $productId <= 0) {
            $result['purchase_list'] = ['success' => false, 'message' => 'Missing SKU or product id.'];

            return $result;
        }

        if ($this->purchaseListEntryExists($sku, $orderNumber)) {
            $result['purchase_list'] = ['success' => true, 'message' => 'Purchase list entry already exists.', 'skipped' => true];

            return $result;
        }

        $agentId = $this->resolvePurchaseAgentId($product, $orderContext);
        if ($agentId <= 0) {
            $result['purchase_list'] = ['success' => false, 'message' => 'No agent available for auto purchase list.'];

            return $result;
        }

        $payload = [
            'user_id' => $agentId,
            'product_id' => $productId,
            'order_id' => $orderNumber,
            'sku' => $sku,
            'date_purchased' => date('Y-m-d'),
            'status' => 'pending',
            'edit_by' => (int) ($orderContext['edit_by'] ?? 0),
            'quantity' => (int) $evaluation['recommended_buy_qty'],
        ];

        if (!method_exists($productModel, 'createPurchaseList')) {
            $result['purchase_list'] = ['success' => false, 'message' => 'createPurchaseList not available.'];

            return $result;
        }

        $result['purchase_list'] = $productModel->createPurchaseList($payload);

        return $result;
    }

    /**
     * Convenience wrapper for order-import hooks.
     *
     * @param array<string, mixed> $orderLine
     */
    public static function tryProcessImportedOrderLine(mysqli $conn, object $productModel, array $orderLine): array
    {
        $sku = trim((string) ($orderLine['sku'] ?? ''));
        if ($sku === '' || !method_exists($productModel, 'getProductByskuExact')) {
            return ['skipped' => true, 'reason' => 'Missing SKU or product lookup.'];
        }

        $product = $productModel->getProductByskuExact($sku);
        if (!$product || !is_array($product)) {
            return ['skipped' => true, 'reason' => 'Product not found.'];
        }

        $service = new self($conn);
        if (!$service->isBookProduct($product)) {
            return ['skipped' => true, 'reason' => 'Not a book.'];
        }

        return $service->processOrderImportLine($product, [
            'quantity' => (int) ($orderLine['quantity'] ?? 1),
            'sku' => $sku,
            'order_number' => (string) ($orderLine['order_number'] ?? ''),
            'agent_id' => (int) ($orderLine['agent_id'] ?? 0),
            'edit_by' => 0,
        ], $productModel);
    }

    /**
     * @return array{months:int,source:string}
     */
    public function resolveLookbackMonths(array $product): array
    {
        $productMonths = max(0, (int) ($product['stock_replenishment_months'] ?? 0));
        if ($productMonths > 0) {
            return ['months' => $productMonths, 'source' => 'product'];
        }

        $publisherName = trim((string) ($product['publisher'] ?? ''));
        if ($publisherName === '' && !empty($product['book_details']['publisher'])) {
            $publisherName = trim((string) $product['book_details']['publisher']);
        }
        if ($publisherName !== '') {
            $publisherMonths = $this->getPublisherReplenishmentMonths($publisherName);
            if ($publisherMonths > 0) {
                return ['months' => $publisherMonths, 'source' => 'publisher'];
            }
        }

        $vendorMonths = $this->getPrimaryVendorReplenishmentMonths((string) ($product['item_code'] ?? ''));
        if ($vendorMonths > 0) {
            return ['months' => $vendorMonths, 'source' => 'vendor'];
        }

        $settings = new AppSettings($this->conn);
        $globalMonths = max(0, (int) $settings->get('stock_replenishment_months', 1));
        if ($globalMonths > 0) {
            return ['months' => $globalMonths, 'source' => 'global'];
        }

        return ['months' => 0, 'source' => ''];
    }

    /**
     * Total sold in the lookback window from imported Exotic order history (vp_orders).
     */
    public function fetchTotalSoldForLookback(array $product, int $months): int
    {
        $months = max(1, $months);
        $fromDate = date('Y-m-d', strtotime('-' . $months . ' months'));
        $toDate = date('Y-m-d');

        return $this->fetchTotalSoldFromLocalOrders($product, $fromDate, $toDate);
    }

    private function fetchTotalSoldFromLocalOrders(array $product, string $fromDate, string $toDate): int
    {
        $sku = trim((string) ($product['sku'] ?? ''));
        $itemCode = trim((string) ($product['item_code'] ?? ''));
        $size = trim((string) ($product['size'] ?? ''));
        $color = trim((string) ($product['color'] ?? ''));

        if ($sku !== '') {
            $sql = "SELECT COALESCE(SUM(quantity), 0) AS total_qty
                    FROM vp_orders
                    WHERE sku = ?
                      AND order_date >= ?
                      AND order_date <= ?
                      AND status NOT IN ('cancelled', 'returned')";
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                return 0;
            }
            $stmt->bind_param('sss', $sku, $fromDate, $toDate);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            return max(0, (int) ($row['total_qty'] ?? 0));
        }

        if ($itemCode === '') {
            return 0;
        }

        $sql = "SELECT COALESCE(SUM(quantity), 0) AS total_qty
                FROM vp_orders
                WHERE item_code = ?
                  AND size = ?
                  AND color = ?
                  AND order_date >= ?
                  AND order_date <= ?
                  AND status NOT IN ('cancelled', 'returned')";
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('sssss', $itemCode, $size, $color, $fromDate, $toDate);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['total_qty'] ?? 0));
    }

    private function resolvePhysicalStock(array $product): int
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId > 0) {
            return max(0, (int) StockMovement::getPhysicalStockTotalIncludingInTransit($this->conn, $productId));
        }

        return max(0, (int) ($product['physical_stock'] ?? 0));
    }

    private function getPublisherReplenishmentMonths(string $publisherName): int
    {
        $sql = 'SELECT stock_replenishment_months
                FROM vp_publishers
                WHERE TRIM(publishers) = ?
                   OR TRIM(publishers) LIKE ?
                ORDER BY (TRIM(publishers) = ?) DESC, id DESC
                LIMIT 1';
        $like = '%' . $publisherName . '%';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('sss', $publisherName, $like, $publisherName);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['stock_replenishment_months'] ?? 0));
    }

    private function getPrimaryVendorReplenishmentMonths(string $itemCode): int
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return 0;
        }

        $sql = 'SELECT v.stock_replenishment_months
                FROM product_vendor_map pvm
                INNER JOIN vp_vendors v ON v.id = pvm.vendor_id
                WHERE pvm.item_code = ?
                ORDER BY pvm.priority ASC, pvm.id ASC
                LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $itemCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['stock_replenishment_months'] ?? 0));
    }

    private function purchaseListEntryExists(string $sku, string $orderNumber): bool
    {
        if ($orderNumber === '') {
            return false;
        }

        $sql = 'SELECT id FROM purchase_list WHERE sku = ? AND order_id = ? LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('ss', $sku, $orderNumber);
        $stmt->execute();
        $exists = (bool) $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return $exists;
    }

    /**
     * @param array<string, mixed> $product
     * @param array<string, mixed> $orderContext
     */
    private function resolvePurchaseAgentId(array $product, array $orderContext): int
    {
        $orderAgent = (int) ($orderContext['agent_id'] ?? 0);
        if ($orderAgent > 0) {
            return $orderAgent;
        }

        $itemCode = trim((string) ($product['item_code'] ?? ''));
        if ($itemCode !== '') {
            $sql = 'SELECT v.agent_id
                    FROM product_vendor_map pvm
                    INNER JOIN vp_vendors v ON v.id = pvm.vendor_id
                    WHERE pvm.item_code = ?
                    ORDER BY pvm.priority ASC, pvm.id ASC
                    LIMIT 1';
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param('s', $itemCode);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $stmt->close();
                $vendorAgent = (int) ($row['agent_id'] ?? 0);
                if ($vendorAgent > 0) {
                    return $vendorAgent;
                }
            }
        }

        return 0;
    }
}
