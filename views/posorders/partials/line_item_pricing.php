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
$orderDiscountLines = is_array($linePricing['order_discount_lines'] ?? null) ? $linePricing['order_discount_lines'] : [];
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
        <div class="space-y-2 border-t border-gray-200 pt-3">
            <?php if ($orderDiscountLines !== []): ?>
                <?php foreach ($orderDiscountLines as $discountLine): ?>
                    <div class="flex w-full items-center justify-between gap-4 py-1">
                        <span class="text-gray-600"><?php echo htmlspecialchars((string)($discountLine['label'] ?? 'Custom Discount:')); ?></span>
                        <span class="shrink-0 text-right tabular-nums font-semibold text-emerald-700"><?php echo $formatAmount((float)($discountLine['amount'] ?? 0)); ?></span>
                    </div>
                <?php endforeach; ?>
            <?php elseif ($customReduce > 0.001): ?>
                <div class="flex w-full items-center justify-between gap-4 py-1">
                    <span class="text-gray-600"><?php echo ((float)($linePricing['order_custom_reduce'] ?? 0)) > 0.001 ? 'Custom Discount:' : 'Discount:'; ?></span>
                    <span class="shrink-0 text-right tabular-nums font-semibold text-emerald-700"><?php echo $formatAmount($customReduce); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="space-y-3">
            <div class="flex w-full items-center justify-between gap-4 py-1">
                <span class="text-gray-600">Listing price (unit)</span>
                <span class="shrink-0 text-right tabular-nums font-medium text-gray-900"><?php echo $formatAmount((float)($linePricing['listing_price_unit'] ?? 0)); ?></span>
            </div>
            <?php if (((float)($linePricing['discount_amount'] ?? 0)) > 0.001): ?>
                <div class="flex w-full items-center justify-between gap-4 py-1">
                    <span class="text-gray-600">Discount</span>
                    <span class="shrink-0 text-right tabular-nums font-semibold text-emerald-700">- <?php echo $formatAmount((float)($linePricing['discount_amount'] ?? 0)); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div class="mt-3 border-t border-gray-200 pt-3">
        <div class="flex w-full items-center justify-between gap-4">
            <span class="font-semibold text-gray-800">Net chargeable amount</span>
            <span class="shrink-0 text-right tabular-nums text-[15px] font-bold text-gray-900"><?php echo $formatAmount((float)($linePricing['finalprice'] ?? $linePricing['final_price'] ?? $linePricing['base_discounted_incl'] ?? $linePricing['chargeable_value'] ?? 0)); ?></span>
        </div>
    </div>
</div>
