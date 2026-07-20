<?php
/** @var array<string, mixed>|null $linePricing */
/** @var string $currencySymbol */

if (!is_array($linePricing ?? null)) {
    return;
}

$currencySymbol = (string)($currencySymbol ?? '₹');
$formatAmount = static function (float $amount) use ($currencySymbol): string {
    return $currencySymbol . ' ' . pos_order_format_pricing_amount($amount);
};

$addonRows = is_array($linePricing['addon_rows'] ?? null) ? $linePricing['addon_rows'] : [];
$addonsTotal = (float)($linePricing['addons_total'] ?? 0);
$customReduce = (float)($linePricing['custom_reduce'] ?? 0);
$baseChargeable = (float)($linePricing['base_chargeable'] ?? ($linePricing['chargeable_value'] ?? 0));
$grossIncl = (float)($linePricing['gross_incl'] ?? $baseChargeable);
$showExtended = $addonsTotal > 0.001 || $customReduce > 0.001;
?>
<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 text-[13px]">
    <p class="mb-4 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Line pricing</p>
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-3">
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Listing price (unit)</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['listing_price_unit'] ?? 0)); ?></span>
        </div>
        <?php if ($showExtended): ?>
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Base item total</span>
                <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount($baseChargeable); ?></span>
            </div>
        <?php endif; ?>
        <?php foreach ($addonRows as $addonRow): ?>
            <div class="flex items-center justify-between gap-4 py-1 sm:col-span-2">
                <span class="text-gray-600">Addon · <?php echo htmlspecialchars((string)($addonRow['name'] ?? '')); ?></span>
                <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($addonRow['line_incl'] ?? 0)); ?></span>
            </div>
        <?php endforeach; ?>
        <?php if ($addonsTotal > 0.001): ?>
            <div class="flex items-center justify-between gap-4 py-1 sm:col-span-2">
                <span class="text-gray-600">Subtotal incl. addons</span>
                <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount($grossIncl); ?></span>
            </div>
        <?php endif; ?>
        <?php if (((float)($linePricing['discount_amount'] ?? 0)) > 0.001): ?>
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">List discount</span>
                <span class="tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount((float)($linePricing['discount_amount'] ?? 0)); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($customReduce > 0.001): ?>
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Custom reduce</span>
                <span class="tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount($customReduce); ?></span>
            </div>
        <?php endif; ?>
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Taxable value</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['taxable_value'] ?? 0)); ?></span>
        </div>
        <div class="flex items-center justify-between gap-4 py-1">
            <span class="text-gray-600">Total GST</span>
            <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['total_gst'] ?? 0)); ?></span>
        </div>
        <div class="flex items-center justify-between gap-4 sm:col-span-2 border-t border-gray-200 pt-4 mt-1">
            <span class="font-semibold text-gray-800">Net chargeable amount</span>
            <span class="tabular-nums text-[15px] font-bold text-gray-900"><?php echo $formatAmount((float)($linePricing['chargeable_value'] ?? 0)); ?></span>
        </div>
    </div>
</div>
