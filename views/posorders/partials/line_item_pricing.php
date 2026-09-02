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

$itemPriceTotal = (float)($linePricing['gross_incl'] ?? $linePricing['itemprice'] ?? $linePricing['list_price_incl'] ?? 0);
$finalPriceTotal = (float)($linePricing['chargeable_value'] ?? $linePricing['finalprice'] ?? 0);
$discountAmount = (float)($linePricing['discount_amount'] ?? $linePricing['custom_reduce'] ?? $linePricing['item_final_discount'] ?? 0);

$showComponentBreakdown = count($pricingComponents) > 0;
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
        <?php if ($discountAmount > 0.001): ?>
            <div class="space-y-2 border-t border-gray-200 pt-3">
                <div class="flex w-full items-center justify-between gap-4 py-1">
                    <span class="text-gray-600">Discount:</span>
                    <span class="shrink-0 text-right tabular-nums font-semibold text-emerald-700"><?php echo $formatAmount($discountAmount); ?></span>
                </div>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="space-y-3">
            <div class="flex w-full items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Listing price (unit)</span>
                <span class="shrink-0 text-right tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['listing_price_unit'] ?? 0)); ?></span>
            </div>
            <?php if ($discountAmount > 0.001): ?>
                <div class="flex w-full items-center justify-between gap-4 py-1">
                    <span class="text-gray-600">Discount:</span>
                    <span class="shrink-0 text-right tabular-nums font-semibold text-emerald-700"><?php echo $formatAmount($discountAmount); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-3 border-t border-gray-200 pt-3">
        <div class="flex w-full items-center justify-between gap-4">
            <span class="font-semibold text-gray-800">Net chargeable amount</span>
            <span class="shrink-0 text-right tabular-nums text-[15px] font-bold text-gray-900"><?php echo $formatAmount($finalPriceTotal); ?></span>
        </div>
    </div>
</div>
