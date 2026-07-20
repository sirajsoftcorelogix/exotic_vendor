<?php
/** @var array<int, array{name: string, price: float}> $addons */
/** @var string $currencySymbol */
/** @var string $layout compact|stacked */

$addons = is_array($addons ?? null) ? $addons : [];
if ($addons === []) {
    return;
}

$currencySymbol = (string) ($currencySymbol ?? '₹');
$layout = (string) ($layout ?? 'compact');
$formatPrice = static function (float $amount) use ($currencySymbol): string {
    return $currencySymbol . number_format($amount, 2);
};
?>
<div class="<?php echo $layout === 'stacked' ? 'mt-3 rounded-lg border border-emerald-100 bg-emerald-50/60 px-3 py-2.5' : 'mt-2.5'; ?>">
    <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500 mb-1.5">Addons</p>
    <ul class="space-y-1 <?php echo $layout === 'stacked' ? '' : 'text-[13px]'; ?>">
        <?php foreach ($addons as $addon): ?>
            <li class="flex items-start justify-between gap-3">
                <span class="text-gray-800"><?php echo htmlspecialchars((string) ($addon['name'] ?? '')); ?></span>
                <span class="tabular-nums font-medium text-gray-900 shrink-0"><?php echo htmlspecialchars($formatPrice((float) ($addon['price'] ?? 0))); ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
