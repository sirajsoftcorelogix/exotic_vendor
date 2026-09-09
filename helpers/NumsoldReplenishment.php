<?php

require_once __DIR__ . '/BookPurchaseReplenishment.php';

/**
 * Period sales for stock replenishment (`numsold_replenishment`).
 *
 * Swap fetchFromExoticApi() for the live Exotic endpoint when it exists.
 * Until then this helper uses local lookback from vp_orders.
 */
class NumsoldReplenishment
{
    private mysqli $conn;

    private BookPurchaseReplenishment $lookback;

    public function __construct(mysqli $conn)
    {
        $this->conn = $conn;
        $this->lookback = new BookPurchaseReplenishment($conn);
    }

    /**
     * @param array<string, mixed> $product
     * @return array{qty:int,source:string,months:int}
     */
    public function getForProduct(array $product, int $months): array
    {
        $months = max(0, $months);
        $fromApi = $this->fetchFromExoticApi($product, $months);
        if ($fromApi !== null) {
            return $fromApi;
        }

        $qty = $months > 0 ? $this->lookback->fetchTotalSoldForLookback($product, $months) : 0;

        return [
            'qty' => max(0, $qty),
            'source' => 'local_lookback',
            'months' => $months,
        ];
    }

    /**
     * Exotic India API for units sold in stock_replenishment_months.
     * Return null to fall back to local lookback.
     *
     * @param array<string, mixed> $product
     * @return array{qty:int,source:string,months:int}|null
     */
    private function fetchFromExoticApi(array $product, int $months): ?array
    {
        // Placeholder until the Exotic period-sales API is available.
        unset($product, $months);

        return null;
    }
}
