<?php
/** @var array $data */
$returnRow = $data['return_row'] ?? [];
$items = $data['items'] ?? [];
$returnTypeLabel = (string) ($data['return_type_label'] ?? '');
$warehouseName = (string) ($data['warehouse_name'] ?? '—');
$canCancel = !empty($data['can_cancel']);
$returnId = (int) ($returnRow['id'] ?? 0);

$flash = $_SESSION['sales_return_flash'] ?? null;
if ($flash) {
    unset($_SESSION['sales_return_flash']);
}
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-orange-200/45 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/40 shadow-sm mb-6">
        <div class="relative px-5 py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    <?= htmlspecialchars((string) ($returnRow['return_number'] ?? 'Sales return')) ?>
                </h1>
                <p class="mt-2 text-sm text-gray-600 capitalize">
                    Status: <?= htmlspecialchars((string) ($returnRow['status'] ?? '')) ?>
                </p>
            </div>
            <div class="flex gap-2">
                <a href="?page=sales_returns&action=index" class="px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-semibold hover:bg-gray-50">All returns</a>
                <?php if ($canCancel): ?>
                    <button type="button" id="cancelSalesReturnBtn" data-id="<?= $returnId ?>"
                        class="px-4 py-2.5 rounded-xl border border-red-300 bg-red-50 text-red-800 text-sm font-semibold hover:bg-red-100">
                        Cancel return
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ring = ($flash['type'] ?? '') === 'success' ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900' : 'border-red-200/80 bg-red-50/90 text-red-900'; ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            <div><dt class="text-gray-500">Order</dt><dd class="font-semibold"><?= htmlspecialchars((string) ($returnRow['order_number'] ?? '')) ?></dd></div>
            <div><dt class="text-gray-500">Return date</dt><dd class="font-semibold"><?= !empty($returnRow['return_date']) ? date('j M Y', strtotime((string) $returnRow['return_date'])) : '—' ?></dd></div>
            <div><dt class="text-gray-500">Return type</dt><dd class="font-semibold"><?= htmlspecialchars($returnTypeLabel) ?></dd></div>
            <div><dt class="text-gray-500">Warehouse</dt><dd class="font-semibold"><?= htmlspecialchars($warehouseName) ?></dd></div>
            <div><dt class="text-gray-500">Stock applied</dt><dd class="font-semibold"><?= !empty($returnRow['stock_applied']) ? 'Yes' : 'No' ?></dd></div>
            <?php if (trim((string) ($returnRow['remarks'] ?? '')) !== ''): ?>
                <div class="sm:col-span-2"><dt class="text-gray-500">Remarks</dt><dd><?= htmlspecialchars((string) $returnRow['remarks']) ?></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 font-semibold text-gray-800">Returned lines</div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Item code</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Size / Color</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Return qty</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Stock IN qty</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($items as $it): ?>
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($it['item_code'] ?? '')) ?></td>
                            <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(trim((string) ($it['size'] ?? '') . ' / ' . (string) ($it['color'] ?? ''), ' /')) ?></td>
                            <td class="px-4 py-3 text-right tabular-nums"><?= htmlspecialchars((string) ($it['return_qty'] ?? '0')) ?></td>
                            <td class="px-4 py-3 text-right tabular-nums"><?= htmlspecialchars((string) ($it['stock_applied_qty'] ?? '0')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canCancel): ?>
<script>
(function () {
    const btn = document.getElementById('cancelSalesReturnBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        if (!confirm('Cancel this sales return and reverse stock movements?')) return;
        const id = btn.getAttribute('data-id');
        fetch('?page=sales_returns&action=cancel', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: parseInt(id, 10) })
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.message || 'Return cancelled.');
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to cancel return.');
                }
            })
            .catch(() => alert('Request failed.'));
    });
})();
</script>
<?php endif; ?>
