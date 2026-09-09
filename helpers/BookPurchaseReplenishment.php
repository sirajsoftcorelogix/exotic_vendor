<?php

require_once __DIR__ . '/../models/globals/AppSettings.php';
require_once __DIR__ . '/../models/product/StockMovement.php';

/**
 * Book purchase / stock replenishment algorithm (order import + product preview).
 *
 * Logic 1 — numsold <= 1 → replenishment_buy_qty = order quantity (0 on product detail).
 * Logic 2 — numsold > 1:
 *   lookback months: product (>0) → publisher (>0) → vendor (>0) → app_settings
 *   min stock % of lookback sales → fill replenishment_buy_qty (50% of lookback sales)
 *   purchase threshold % of lookback sales → generate PO for replenishment_buy_qty
 */
class BookPurchaseReplenishment
{
    private const NUMSOLD_THRESHOLD = 1;
    private const DEFAULT_MIN_STOCK_PERCENT = 50;
    private const DEFAULT_PURCHASE_THRESHOLD_PERCENT = 25;
    private const BUY_RATIO = 0.50;

    private mysqli $conn;

    private ?AppSettings $appSettings = null;

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
            'min_stock_percent' => 0,
            'purchase_threshold_percent' => 0,
            'stock_threshold' => 0,
            'purchase_threshold' => 0,
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
        $minStockPercent = $this->getPercentSetting(
            'stock_replenishment_min_stock_percent',
            self::DEFAULT_MIN_STOCK_PERCENT
        );
        $purchaseThresholdPercent = $this->getPercentSetting(
            'stock_replenishment_purchase_threshold_percent',
            self::DEFAULT_PURCHASE_THRESHOLD_PERCENT
        );

        $base['total_sold_lookback'] = $totalSold;
        $base['branch'] = 'demand_based';
        $base['min_stock_percent'] = $minStockPercent;
        $base['purchase_threshold_percent'] = $purchaseThresholdPercent;
        $base['stock_threshold'] = (int) floor($totalSold * ($minStockPercent / 100));
        $base['purchase_threshold'] = (int) floor($totalSold * ($purchaseThresholdPercent / 100));

        $computedBuyQty = 0;
        if ($physicalStock <= $base['stock_threshold']) {
            $computedBuyQty = (int) max(0, round($totalSold * self::BUY_RATIO));
        }
        $base['recommended_buy_qty'] = $computedBuyQty;
        $base['should_buy'] = $physicalStock <= $base['purchase_threshold'] && $computedBuyQty > 0;

        if ($computedBuyQty <= 0) {
            $base['reason'] = $physicalStock > $base['stock_threshold']
                ? 'Physical stock is above ' . $minStockPercent . '% of lookback sales — replenishment buy qty is 0.'
                : 'Lookback sales are zero — nothing to buy.';

            return $base;
        }

        $base['reason'] = $base['should_buy']
            ? 'Physical stock is at or below ' . $purchaseThresholdPercent
                . '% of lookback sales — generate PO for replenishment buy qty (' . $computedBuyQty . ').'
            : 'Physical stock is at or below ' . $minStockPercent
                . '% of lookback sales — replenishment buy qty set to 50% of lookback sales; no PO yet.';

        return $base;
    }

    /**
     * Evaluate replenishment and persist recommended_buy_qty on vp_products.
     *
     * @return array<string, mixed>
     */
    public function evaluateAndStore(array $product, object $productModel, int $orderQty = 0, ?int $physicalStock = null): array
    {
        $evaluation = $this->evaluate($product, $orderQty, $physicalStock);
        $this->persistRecommendedBuyQty($productModel, (int) ($product['id'] ?? 0), $evaluation);

        return $evaluation;
    }

    public function persistRecommendedBuyQty(object $productModel, int $productId, array $evaluation): bool
    {
        if ($productId <= 0 || !method_exists($productModel, 'setProductReplenishmentBuyQty')) {
            return false;
        }

        return (bool) $productModel->setProductReplenishmentBuyQty(
            $productId,
            (int) ($evaluation['recommended_buy_qty'] ?? 0)
        );
    }

    /**
     * Order-import hook. Buy qty is owned by the daily replenishment job.
     *
     * @return array<string, mixed>
     */
    public function processOrderImportLine(
        array $product,
        array $orderContext,
        object $productModel
    ): array {
        return [
            'evaluation' => null,
            'purchase_list' => null,
            'skipped' => true,
            'reason' => 'Daily replenishment job owns buy qty and the buy report.',
        ];
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
     * Lookback months: product → publisher → vendor → app_settings.
     * A value is used only when it is greater than 0.
     *
     * @return array{months:int,source:string}
     */
    public function resolveLookbackMonths(array $product): array
    {
        $productMonths = max(0, (int) ($product['stock_replenishment_months'] ?? 0));
        if ($productMonths > 0) {
            return ['months' => $productMonths, 'source' => 'product'];
        }

        $publisherMonths = $this->getPublisherLookbackMonths($product);
        if ($publisherMonths > 0) {
            return ['months' => $publisherMonths, 'source' => 'publisher'];
        }

        $vendorMonths = $this->getVendorLookbackMonths($product);
        if ($vendorMonths > 0) {
            return ['months' => $vendorMonths, 'source' => 'vendor'];
        }

        $globalMonths = max(0, (int) $this->settings()->get('stock_replenishment_months', 1));
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

    private function settings(): AppSettings
    {
        if ($this->appSettings === null) {
            $this->appSettings = new AppSettings($this->conn);
        }

        return $this->appSettings;
    }

    private function getPercentSetting(string $key, int $default): int
    {
        return max(0, min(100, (int) $this->settings()->get($key, $default)));
    }

    private function resolvePhysicalStock(array $product): int
    {
        $productId = (int) ($product['id'] ?? 0);
        if ($productId > 0) {
            return max(0, (int) StockMovement::getPhysicalStockTotalIncludingInTransit($this->conn, $productId));
        }

        return max(0, (int) ($product['physical_stock'] ?? 0));
    }

    /**
     * Publisher months from the product's publisher, then from the publisher
     * mapped to the product's primary vendor. Zero is ignored so vendor/global can apply.
     */
    private function getPublisherLookbackMonths(array $product): int
    {
        $keys = [];
        $raw = trim((string) ($product['publisher'] ?? ''));
        if ($raw !== '') {
            $keys[] = $raw;
        }
        $selectedId = trim((string) ($product['book_detail_selected_publisher_id'] ?? ''));
        if ($selectedId !== '' && !in_array($selectedId, $keys, true)) {
            $keys[] = $selectedId;
        }

        foreach ($keys as $key) {
            $months = $this->fetchPublisherMonthsByKey($key);
            if ($months > 0) {
                return $months;
            }
        }

        return $this->fetchPublisherMonthsViaVendorMap(trim((string) ($product['item_code'] ?? '')));
    }

    private function fetchPublisherMonthsByKey(string $key): int
    {
        $key = trim($key);
        if ($key === '') {
            return 0;
        }

        $sql = 'SELECT stock_replenishment_months
                FROM vp_publishers
                WHERE CAST(publishers_id AS CHAR) = ?
                   OR CAST(id AS CHAR) = ?
                   OR publishers = ?
                LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('sss', $key, $key, $key);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['stock_replenishment_months'] ?? 0));
    }

    private function fetchPublisherMonthsViaVendorMap(string $itemCode): int
    {
        $itemCode = trim($itemCode);
        if ($itemCode === '') {
            return 0;
        }

        $sql = 'SELECT pub.stock_replenishment_months AS publisher_months
                FROM product_vendor_map pvm
                INNER JOIN publisher_vendor_mapping map ON map.vendor_id = pvm.vendor_id
                INNER JOIN vp_publishers pub ON pub.id = map.publisher_id
                WHERE pvm.item_code = ?
                ORDER BY pvm.priority ASC, pvm.id ASC, map.sort_order ASC, map.id ASC
                LIMIT 1';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return 0;
        }
        $stmt->bind_param('s', $itemCode);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        return max(0, (int) ($row['publisher_months'] ?? 0));
    }

    /**
     * Vendor months from the product's primary supplier in product_vendor_map.
     */
    private function getVendorLookbackMonths(array $product): int
    {
        $itemCode = trim((string) ($product['item_code'] ?? ''));
        if ($itemCode === '') {
            return 0;
        }

        $sql = 'SELECT v.stock_replenishment_months AS vendor_months
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

        return max(0, (int) ($row['vendor_months'] ?? 0));
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
