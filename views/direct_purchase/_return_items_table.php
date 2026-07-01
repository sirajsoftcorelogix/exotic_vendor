<?php
/** @var array<int, array<string, mixed>> $items */
/** @var string|null $currency */
$items = $items ?? [];
$lineCur = strtoupper(trim((string) ($currency ?? 'INR')));
if ($lineCur === '') {
    $lineCur = 'INR';
}
$sym = dp_currency_symbol($lineCur);
$dec = dp_currency_decimals($lineCur);
?>
<?php if ($items === []): ?>
    <p class="text-xs text-gray-500 py-1">No line items recorded.</p>
<?php else: ?>
    <table class="min-w-full text-xs">
        <thead>
            <tr class="text-gray-500 uppercase tracking-wide">
                <th class="py-2 pr-4 text-left font-semibold">Item code</th>
                <th class="py-2 pr-4 text-left font-semibold">SKU</th>
                <th class="py-2 pr-4 text-right font-semibold">Return qty</th>
                <th class="py-2 text-right font-semibold">Line total</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100/80">
            <?php foreach ($items as $ln): ?>
                <tr>
                    <td class="py-2 pr-4 font-mono text-gray-800"><?= htmlspecialchars((string) ($ln['item_code'] ?? '—')) ?></td>
                    <td class="py-2 pr-4 font-mono text-gray-800"><?= htmlspecialchars((string) ($ln['sku'] ?? '—')) ?></td>
                    <td class="py-2 pr-4 text-right tabular-nums text-gray-900"><?= htmlspecialchars(rtrim(rtrim(number_format((float) ($ln['return_qty'] ?? 0), 3), '0'), '.')) ?></td>
                    <td class="py-2 text-right tabular-nums text-gray-900">
                        <?= htmlspecialchars($sym) ?> <?= number_format((float) ($ln['line_total'] ?? 0), $dec) ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>
