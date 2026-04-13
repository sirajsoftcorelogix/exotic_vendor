<?php
require_once dirname(dirname(__DIR__)) . '/helpers/direct_purchase_currency.php';

$purchase = $data['purchase'] ?? [];
$returns = $data['returns'] ?? [];
$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}
$dpId = (int) ($purchase['id'] ?? 0);
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="relative px-5 py-6 sm:px-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">Purchase returns</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Invoice <span class="font-mono font-semibold text-gray-900"><?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?></span>
                    · <?= htmlspecialchars((string) ($purchase['vendor_name'] ?? '')) ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="?page=direct_purchase&action=return_add&amp;dp_id=<?= $dpId ?>"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm">
                    <i class="fas fa-plus text-xs" aria-hidden="true"></i>
                    New return
                </a>
                <a href="?page=direct_purchase&action=edit&amp;id=<?= $dpId ?>"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    View purchase
                </a>
                <a href="?page=direct_purchase&action=list"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    All purchases
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ft = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
        $ring = $ft === 'success' ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900' : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">#</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Return date</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-700">Grand total</th>
                    <th class="px-4 py-3 text-center font-semibold text-gray-700 w-24">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if ($returns === []): ?>
                    <tr>
                        <td colspan="4" class="px-4 py-10 text-center text-gray-500">No returns yet. Use “New return” to record stock going back to the vendor.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($returns as $idx => $r): ?>
                        <?php
                        $cur = strtoupper(trim((string) ($r['currency'] ?? 'INR')));
                        $sym = dp_currency_symbol($cur);
                        $dec = dp_currency_decimals($cur);
                        ?>
                        <tr class="hover:bg-amber-50/30">
                            <td class="px-4 py-3 tabular-nums text-gray-700"><?= $idx + 1 ?></td>
                            <td class="px-4 py-3 text-gray-800"><?= !empty($r['return_date']) ? htmlspecialchars(date('j M Y', strtotime($r['return_date']))) : '—' ?></td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900 tabular-nums">
                                <?= htmlspecialchars($sym) ?> <?= number_format((float) ($r['grand_total'] ?? 0), $dec) ?>
                                <span class="text-gray-500 text-xs font-normal ml-1"><?= htmlspecialchars($cur) ?></span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <a href="?page=direct_purchase&action=return_delete&amp;id=<?= (int) ($r['id'] ?? 0) ?>"
                                    onclick="return confirm('Delete this return and reverse stock OUT movements?');"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 bg-white text-red-600 hover:bg-red-50"
                                    title="Delete return" aria-label="Delete return">
                                    <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
