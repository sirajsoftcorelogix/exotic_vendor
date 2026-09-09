<?php
$searchValue = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$purchasedValue = (string) ($purchased ?? 'no');
$runDateValue = htmlspecialchars((string) ($run_date ?? ''), ENT_QUOTES, 'UTF-8');
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$limit = (int) ($limit ?? 20);
$totalRecords = (int) ($totalRecords ?? 0);
$tableReady = !empty($table_ready);
$canRun = !empty($can_run);
$rows = is_array($rows ?? null) ? $rows : [];
$queryBase = [
    'page' => 'replenishment_buy_report',
    'action' => 'list',
    'search_text' => (string) ($search ?? ''),
    'purchased' => $purchasedValue,
    'run_date' => (string) ($run_date ?? ''),
    'limit' => $limit,
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-clipboard-list text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Products · Daily replenishment</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Replenishment <span class="text-amber-800">buy report</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Books that sold yesterday and need purchase because available stock (physical + pending PO) is below the purchase threshold of period sales.
                </p>
            </div>
            <?php if ($canRun): ?>
            <button type="button" id="runReplenishmentJobBtn"
                class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22]">
                <i class="fas fa-play text-xs opacity-95" aria-hidden="true"></i>
                Run for yesterday
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$tableReady): ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Database setup required</p>
            <p class="mt-1">Run <code class="text-xs bg-white px-1 rounded">sql/create_replenishment_buy_report.sql</code>.</p>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
            <form method="get" class="p-5 border-b border-gray-100 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <input type="hidden" name="page" value="replenishment_buy_report">
                <input type="hidden" name="action" value="list">
                <div class="lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" value="<?php echo $searchValue; ?>" placeholder="SKU, item code, title"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Purchased</label>
                    <select name="purchased" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm bg-white">
                        <option value="no" <?php echo $purchasedValue === 'no' ? 'selected' : ''; ?>>No</option>
                        <option value="yes" <?php echo $purchasedValue === 'yes' ? 'selected' : ''; ?>>Yes</option>
                        <option value="all" <?php echo $purchasedValue === 'all' ? 'selected' : ''; ?>>All</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Sales date</label>
                    <input type="date" name="run_date" value="<?php echo $runDateValue; ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                </div>
                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2.5 rounded-lg bg-gray-900 text-white text-sm font-semibold">Filter</button>
                </div>
            </form>

            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Sales date</th>
                            <th class="px-4 py-3 font-semibold">SKU</th>
                            <th class="px-4 py-3 font-semibold">Title</th>
                            <th class="px-4 py-3 font-semibold text-right">Yday sold</th>
                            <th class="px-4 py-3 font-semibold text-right">Period sold</th>
                            <th class="px-4 py-3 font-semibold text-right">Physical</th>
                            <th class="px-4 py-3 font-semibold text-right">Pending PO</th>
                            <th class="px-4 py-3 font-semibold text-right">Available</th>
                            <th class="px-4 py-3 font-semibold text-right">Buy qty</th>
                            <th class="px-4 py-3 font-semibold">Purchased</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if ($rows === []): ?>
                            <tr>
                                <td colspan="10" class="px-4 py-8 text-center text-gray-500">No replenishment items for these filters.</td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $isPurchased = (int) ($row['purchased'] ?? 0) === 1; ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600"><?php echo htmlspecialchars((string) ($row['run_date'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-4 py-3">
                                    <a class="text-amber-800 font-medium hover:underline" href="<?php echo htmlspecialchars(base_url('?page=products&action=detail&id=' . (int) ($row['product_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars((string) ($row['sku'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                    <div class="text-xs text-gray-400"><?php echo htmlspecialchars((string) ($row['item_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                </td>
                                <td class="px-4 py-3 max-w-xs truncate" title="<?php echo htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                    <?php echo htmlspecialchars((string) ($row['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['yesterday_sold_qty'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['numsold_replenishment'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['physical_stock'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right"><?php echo (int) ($row['pending_po_qty'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right font-semibold"><?php echo (int) ($row['available_stock'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-lime-800"><?php echo (int) ($row['replenishment_buy_qty'] ?? 0); ?></td>
                                <td class="px-4 py-3">
                                    <button type="button"
                                        class="replenish-purchased-toggle px-3 py-1.5 rounded-lg text-xs font-semibold border <?php echo $isPurchased ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-900 border-amber-200'; ?>"
                                        data-id="<?php echo (int) ($row['id'] ?? 0); ?>"
                                        data-purchased="<?php echo $isPurchased ? '1' : '0'; ?>">
                                        <?php echo $isPurchased ? 'Yes' : 'No'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                <span><?php echo (int) $totalRecords; ?> items</span>
                <div class="flex items-center gap-2">
                    <?php if ($currentPage > 1): ?>
                        <a class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50" href="<?php echo htmlspecialchars('?' . http_build_query(array_merge($queryBase, ['page_no' => $currentPage - 1])), ENT_QUOTES, 'UTF-8'); ?>">Prev</a>
                    <?php endif; ?>
                    <span>Page <?php echo $currentPage; ?> / <?php echo $totalPages; ?></span>
                    <?php if ($currentPage < $totalPages): ?>
                        <a class="px-3 py-1.5 rounded border border-gray-200 hover:bg-gray-50" href="<?php echo htmlspecialchars('?' . http_build_query(array_merge($queryBase, ['page_no' => $currentPage + 1])), ENT_QUOTES, 'UTF-8'); ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const notice = function (message, tone) {
        if (typeof window.showToast === 'function') {
            window.showToast(message, tone || 'info');
            return;
        }
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({ title: 'Replenishment', message: message, tone: tone || 'info' });
        }
    };

    document.querySelectorAll('.replenish-purchased-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const id = parseInt(btn.getAttribute('data-id') || '0', 10);
            const currentlyYes = btn.getAttribute('data-purchased') === '1';
            fetch('index.php?page=replenishment_buy_report&action=mark_purchased', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: id, purchased: currentlyYes ? 0 : 1 })
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.success) {
                    notice(data.message || 'Could not update.', 'error');
                    return;
                }
                window.location.reload();
            })
            .catch(function () {
                notice('Could not update purchased status.', 'error');
            });
        });
    });

    const runBtn = document.getElementById('runReplenishmentJobBtn');
    if (runBtn) {
        runBtn.addEventListener('click', function () {
            runBtn.disabled = true;
            fetch('index.php?page=replenishment_buy_report&action=run', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(function (res) { return res.text(); })
            .then(function (text) {
                var data;
                try {
                    data = JSON.parse(text);
                } catch (e) {
                    throw new Error(text ? text.replace(/<[^>]+>/g, ' ').slice(0, 180) : 'Invalid server response');
                }
                notice(data.message || 'Job finished.', data.success ? 'success' : 'error');
                if (data.success) {
                    var params = new URLSearchParams(window.location.search);
                    params.set('page', 'replenishment_buy_report');
                    params.set('action', 'list');
                    params.set('purchased', 'no');
                    if (data.summary && data.summary.run_date) {
                        params.set('run_date', data.summary.run_date);
                    }
                    window.location.search = params.toString();
                } else {
                    runBtn.disabled = false;
                }
            })
            .catch(function (err) {
                runBtn.disabled = false;
                notice((err && err.message) ? err.message : 'Could not run replenishment job.', 'error');
            });
        });
    }
});
</script>
