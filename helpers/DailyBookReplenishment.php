<?php

require_once __DIR__ . '/../models/globals/AppSettings.php';
require_once __DIR__ . '/../models/product/product.php';
require_once __DIR__ . '/../models/product/StockMovement.php';
require_once __DIR__ . '/../models/order/order.php';
require_once __DIR__ . '/../models/order/purchaseOrder.php';
require_once __DIR__ . '/../models/replenishment/ReplenishmentBuyReport.php';
require_once __DIR__ . '/BookPurchaseReplenishment.php';
require_once __DIR__ . '/NumsoldReplenishment.php';

/**
 * Daily book replenishment: yesterday's sales → numsold_replenishment → buy qty / report.
 */
class DailyBookReplenishment
{
    private const DEFAULT_MIN_STOCK_PERCENT = 50;
    private const DEFAULT_PURCHASE_THRESHOLD_PERCENT = 25;

    private mysqli $conn;

    private product $productModel;

    private Order $orderModel;

    private PurchaseOrder $purchaseOrderModel;

    private ReplenishmentBuyReport $reportModel;

    private BookPurchaseReplenishment $lookback;

    private NumsoldReplenishment $numsold;

    private AppSettings $settings;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->productModel = new product($conn);
        $this->orderModel = new Order($conn);
        $this->purchaseOrderModel = new PurchaseOrder($conn);
        $this->reportModel = new ReplenishmentBuyReport($conn);
        $this->lookback = new BookPurchaseReplenishment($conn);
        $this->numsold = new NumsoldReplenishment($conn);
        $this->settings = new AppSettings($conn);
    }

    /**
     * @return array<string, mixed>
     */
    public function runForDate(string $salesDate, bool $dryRun = false, int $limit = 0): array
    {
        $sales = $this->orderModel->sumBookSalesForDate($salesDate);
        $minStockPercent = $this->percentSetting('stock_replenishment_min_stock_percent', self::DEFAULT_MIN_STOCK_PERCENT);
        $purchaseThresholdPercent = $this->percentSetting(
            'stock_replenishment_purchase_threshold_percent',
            self::DEFAULT_PURCHASE_THRESHOLD_PERCENT
        );

        $summary = [
            'run_date' => $salesDate,
            'dry_run' => $dryRun,
            'scanned' => 0,
            'books' => 0,
            'triggered' => 0,
            'written' => 0,
            'skipped' => 0,
            'min_stock_percent' => $minStockPercent,
            'purchase_threshold_percent' => $purchaseThresholdPercent,
        ];

        foreach ($sales as $sale) {
            if ($limit > 0 && $summary['scanned'] >= $limit) {
                break;
            }
            $summary['scanned']++;

            $sku = trim((string) ($sale['sku'] ?? ''));
            if ($sku === '' || !method_exists($this->productModel, 'getProductByskuExact')) {
                $summary['skipped']++;
                continue;
            }

            $product = $this->resolveProductForSale($sku, trim((string) ($sale['item_code'] ?? '')));
            // Yesterday's book-sales query already classified this SKU as a book.
            if ($product === null) {
                $summary['skipped']++;
                continue;
            }
            $summary['books']++;

            $lookback = $this->lookback->resolveLookbackMonths($product);
            $numsold = $this->numsold->getForProduct($product, (int) $lookback['months']);
            $numsoldQty = max(0, (int) $numsold['qty']);
            $productId = (int) ($product['id'] ?? 0);

            $physical = $productId > 0
                ? max(0, (int) StockMovement::getPhysicalStockTotalFromMovements($this->conn, $productId))
                : max(0, (int) ($product['physical_stock'] ?? 0));
            $pendingPo = $this->purchaseOrderModel->getPendingQtyForSku($sku);
            $available = $physical + $pendingPo;
            $thresholdQty = (int) floor($numsoldQty * ($purchaseThresholdPercent / 100));
            $buyQty = (int) max(0, round($numsoldQty * ($minStockPercent / 100)));
            $shouldBuy = $available < $thresholdQty && $buyQty > 0;

            if (!$dryRun && $productId > 0) {
                $this->productModel->setProductNumsoldReplenishment($productId, $numsoldQty);
            }

            if (!$shouldBuy) {
                continue;
            }

            $summary['triggered']++;

            if ($dryRun) {
                continue;
            }

            $this->productModel->setProductReplenishmentBuyQty($productId, $buyQty);
            $saved = $this->reportModel->upsertRecommendation([
                'run_date' => $salesDate,
                'product_id' => $productId,
                'sku' => $sku,
                'item_code' => trim((string) ($product['item_code'] ?? $sale['item_code'] ?? '')),
                'title' => trim((string) ($product['title'] ?? '')),
                'yesterday_sold_qty' => max(0, (int) ($sale['yesterday_qty'] ?? 0)),
                'numsold_replenishment' => $numsoldQty,
                'lookback_months' => (int) $lookback['months'],
                'lookback_source' => (string) $lookback['source'],
                'numsold_source' => (string) $numsold['source'],
                'physical_stock' => $physical,
                'pending_po_qty' => $pendingPo,
                'available_stock' => $available,
                'purchase_threshold_percent' => $purchaseThresholdPercent,
                'purchase_threshold_qty' => $thresholdQty,
                'min_stock_percent' => $minStockPercent,
                'replenishment_buy_qty' => $buyQty,
            ]);
            if ($saved) {
                $summary['written']++;
            }
        }

        return $summary;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolveProductForSale(string $sku, string $itemCode): ?array
    {
        if ($sku !== '' && method_exists($this->productModel, 'getProductByskuExact')) {
            $product = $this->productModel->getProductByskuExact($sku);
            if (is_array($product) && (int) ($product['id'] ?? 0) > 0) {
                return $product;
            }
        }

        if ($itemCode === '' || !method_exists($this->productModel, 'getProductByItemCode')) {
            return null;
        }

        $found = $this->productModel->getProductByItemCode($itemCode);
        if (!is_array($found) || $found === []) {
            return null;
        }

        if (isset($found['id'])) {
            return $found;
        }

        $match = null;
        foreach ($found as $row) {
            if (!is_array($row) || (int) ($row['id'] ?? 0) <= 0) {
                continue;
            }
            if ($match === null) {
                $match = $row;
            }
            if ($sku !== '' && strcasecmp(trim((string) ($row['sku'] ?? '')), $sku) === 0) {
                return $row;
            }
        }

        return $match;
    }

    private function percentSetting(string $key, int $default): int
    {
        return max(0, min(100, (int) $this->settings->get($key, $default)));
    }
}
