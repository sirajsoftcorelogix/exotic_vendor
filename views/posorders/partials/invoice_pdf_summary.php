<?php
/** @var list<array{label: string, amount: float, note: string, is_grand: bool}>|null $summaryRows */
/** @var string $currencySymbol */

$summaryRows = is_array($summaryRows ?? null) ? $summaryRows : [];
if ($summaryRows === []) {
    return;
}

$currencySymbol = (string)($currencySymbol ?? '₹');
?>
<div class="rounded-lg border border-gray-200 bg-white">
    <div class="border-b border-gray-200 bg-gray-50 px-4 py-2.5">
        <p class="text-[11px] font-bold uppercase tracking-[0.08em] text-gray-700">Summary</p>
    </div>
    <div class="divide-y divide-gray-100 px-4 py-1 text-sm">
        <?php foreach ($summaryRows as $summaryRow):
            $label = trim((string)($summaryRow['label'] ?? ''));
            $amount = number_format((float)($summaryRow['amount'] ?? 0), 2);
            $note = trim((string)($summaryRow['note'] ?? ''));
            $isGrand = !empty($summaryRow['is_grand']);
        ?>
            <div class="flex items-start justify-between gap-4 py-2.5 <?php echo $isGrand ? 'border-t-2 border-gray-900 bg-gray-50 -mx-4 px-4 mt-1' : ''; ?>">
                <div class="min-w-0">
                    <span class="<?php echo $isGrand ? 'text-sm font-bold text-gray-900' : 'text-gray-700'; ?>">
                        <?php echo htmlspecialchars($label); ?>
                    </span>
                    <?php if ($note !== ''): ?>
                        <p class="mt-0.5 text-[11px] leading-snug text-gray-500"><?php echo htmlspecialchars($note); ?></p>
                    <?php endif; ?>
                </div>
                <span class="shrink-0 tabular-nums <?php echo $isGrand ? 'text-base font-bold text-gray-900' : 'font-medium text-gray-900'; ?>">
                    <?php echo htmlspecialchars($currencySymbol); ?> <?php echo $amount; ?>
                </span>
            </div>
        <?php endforeach; ?>
    </div>
</div>
