<?php
require_once dirname(dirname(__DIR__)) . '/helpers/direct_purchase_currency.php';

$purchase = $data['purchase'] ?? [];
$returns = $data['returns'] ?? [];
$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}
$dpId = (int) ($purchase['id'] ?? 0);
$returnItems = $data['return_items'] ?? [];

$dpFormatDate = static function ($value): string {
    if (empty($value)) {
        return '—';
    }
    return date('j M Y', strtotime((string) $value));
};
$purchaseAddedBy = trim((string) ($purchase['purchase_created_by_name'] ?? ''));
if ($purchaseAddedBy === '' && !empty($purchase['created_by'])) {
    $purchaseAddedBy = 'User #' . (int) $purchase['created_by'];
}
if ($purchaseAddedBy === '') {
    $purchaseAddedBy = '—';
}
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-undo-alt text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Purchasing · Direct purchase</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Purchase returns</h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed">
                    Invoice <span class="font-mono font-semibold text-gray-900"><?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?></span>
                    · <?= htmlspecialchars((string) ($purchase['vendor_name'] ?? '')) ?>
                </p>
                <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-gray-600">
                    <div class="flex items-center gap-2">
                        <dt class="font-semibold text-gray-700">Invoice date</dt>
                        <dd><?= htmlspecialchars($dpFormatDate($purchase['invoice_date'] ?? '')) ?></dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="font-semibold text-gray-700">Purchase added</dt>
                        <dd><?= htmlspecialchars($dpFormatDate($purchase['created_at'] ?? '')) ?></dd>
                    </div>
                    <div class="flex items-center gap-2">
                        <dt class="font-semibold text-gray-700">Purchase added by</dt>
                        <dd><?= htmlspecialchars($purchaseAddedBy) ?></dd>
                    </div>
                </dl>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0 lg:pt-1">
                <a href="?page=direct_purchase&action=return_add&amp;dp_id=<?= $dpId ?>"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm">
                    <i class="fas fa-plus text-xs" aria-hidden="true"></i>
                    New return
                </a>
                <a href="?page=direct_purchase&action=edit&amp;id=<?= $dpId ?>"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    View purchase
                </a>
                <a href="?page=direct_purchase&action=returns"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                    All returns
                </a>
                <a href="?page=direct_purchase&action=list"
                    class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
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

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Return date</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Return added</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Grand total</th>
                        <th class="px-5 py-3.5 text-center w-24">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($returns === []): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center text-gray-500">No returns yet. Use “New return” to record stock going back to the vendor.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $idx => $r): ?>
                            <?php
                            $returnId = (int) ($r['id'] ?? 0);
                            $cur = strtoupper(trim((string) ($r['currency'] ?? 'INR')));
                            $sym = dp_currency_symbol($cur);
                            $dec = dp_currency_decimals($cur);
                            $lines = $returnItems[$returnId] ?? [];
                            ?>
                            <tr class="hover:bg-amber-50/30">
                                <td class="px-5 py-4 tabular-nums text-gray-700"><?= $idx + 1 ?></td>
                                <td class="px-5 py-4 text-gray-800 whitespace-nowrap"><?= htmlspecialchars($dpFormatDate($r['return_date'] ?? '')) ?></td>
                                <td class="px-5 py-4 text-gray-700 whitespace-nowrap"><?= htmlspecialchars($dpFormatDate($r['created_at'] ?? '')) ?></td>
                                <td class="px-5 py-4 text-right font-medium text-gray-900 tabular-nums">
                                    <?= htmlspecialchars($sym) ?> <?= number_format((float) ($r['grand_total'] ?? 0), $dec) ?>
                                    <span class="text-gray-500 text-xs font-normal ml-1"><?= htmlspecialchars($cur) ?></span>
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <a href="?page=direct_purchase&action=return_delete&amp;id=<?= $returnId ?>"
                                        onclick="return confirm('Delete this return and reverse stock OUT movements?');"
                                        class="inline-flex h-8 w-8 items-center justify-center rounded border border-red-200 bg-white text-red-600 hover:bg-red-50"
                                        title="Delete return" aria-label="Delete return">
                                        <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                                    </a>
                                </td>
                            </tr>
                            <tr class="bg-gray-50/60">
                                <td colspan="5" class="px-5 py-3">
                                    <div class="text-xs font-semibold text-gray-600 mb-2">Returned items</div>
                                    <?php $items = $lines;
                                    $currency = $cur;
                                    require __DIR__ . '/_return_items_table.php'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
