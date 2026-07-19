<?php
/** @var array<string, float>|null $linePricing */
/** @var string $currencySymbol */

if (!is_array($linePricing ?? null)) {
    return;
}

$currencySymbol = (string)($currencySymbol ?? '₹');
$formatAmount = static function (float $amount) use ($currencySymbol): string {
    return $currencySymbol . ' ' . pos_order_format_pricing_amount($amount);
};
?>
<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 text-[13px]">
    <p class="mb-4 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Line pricing</p>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-3">
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Listing price (unit)</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['listing_price_unit'] ?? 0)); ?></span>
        </div>
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Taxable value</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['taxable_value'] ?? 0)); ?></span>
        </div>
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Total GST</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['total_gst'] ?? 0)); ?></span>
        </div>
        <?php if (((float)($linePricing['discount_amount'] ?? 0)) > 0.001): ?>
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Discount</span>
                <span class="tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount((float)($linePricing['discount_amount'] ?? 0)); ?></span>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between gap-4 sm:col-span-2 border-t border-gray-200 pt-4 mt-1">
            <span class="font-semibold text-gray-800">Chargeable value</span>
            <span class="tabular-nums text-[15px] font-bold text-gray-900"><?php echo $formatAmount((float)($linePricing['chargeable_value'] ?? 0)); ?></span>
        </div>
    </div>
</div>
