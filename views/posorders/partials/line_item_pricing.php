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

$pricingComponents = is_array($linePricing['pricing_components'] ?? null) ? $linePricing['pricing_components'] : [];
$customReduce = (float)($linePricing['custom_reduce'] ?? 0);
$grossIncl = (float)($linePricing['gross_incl'] ?? 0);
$showComponentBreakdown = count($pricingComponents) > 0 && ($customReduce > 0.001 || count($pricingComponents) > 1);
?>
<div class="mt-5 rounded-xl border border-gray-200 bg-gray-50 px-4 py-4 text-[13px]">
    <p class="mb-4 text-[11px] font-semibold uppercase tracking-wide text-gray-500">Line pricing</p>

    <?php if ($showComponentBreakdown): ?>
        <div class="mb-4 overflow-x-auto">
            <table class="min-w-full text-left text-[12px]">
                <thead class="text-[11px] uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="pb-2 pr-3 font-semibold">Item</th>
                        <th class="pb-2 font-semibold text-right">List price</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php foreach ($pricingComponents as $component): ?>
                        <tr>
                            <td class="py-2 pr-3 align-top text-gray-800"><?php echo htmlspecialchars((string)($component['name'] ?? '')); ?></td>
                            <td class="py-2 align-top text-right tabular-nums"><?php echo $formatAmount((float)($component['list_incl'] ?? 0)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 sm:gap-x-8 border-t border-gray-200 pt-3">
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Total before discount (incl. GST)</span>
                <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount($grossIncl); ?></span>
            </div>
            <?php if ($customReduce > 0.001): ?>
                <div class="flex items-center justify-between gap-4 py-1">
                    <span class="text-gray-600">Fixed discount (custom reduce)</span>
                    <span class="tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount($customReduce); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-3">
            <div class="flex items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Listing price (unit)</span>
                <span class="tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['listing_price_unit'] ?? 0)); ?></span>
            </div>
            <?php if (((float)($linePricing['discount_amount'] ?? 0)) > 0.001): ?>
                <div class="flex items-center justify-between gap-4 py-1">
                    <span class="text-gray-600">List discount</span>
                    <span class="tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount((float)($linePricing['discount_amount'] ?? 0)); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-x-8 sm:gap-y-3 mt-3 border-t border-gray-200 pt-3">
        <div class="flex items-center justify-between gap-4 sm:col-span-2">
            <span class="font-semibold text-gray-800">Net chargeable amount</span>
            <span class="tabular-nums text-[15px] font-bold text-gray-900"><?php echo $formatAmount((float)($linePricing['chargeable_value'] ?? 0)); ?></span>
        </div>
    </div>
</div>
