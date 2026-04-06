<?php
/** @var array $transfer @var array $items @var int $transfer_id */
$pageNo = isset($page_no) ? (int) $page_no : 1;
$limit = isset($limit) ? (int) $limit : 50;
$totalRecords = isset($total_records) ? (int) $total_records : 0;
$transfer = $transfer ?? [];
$items = $items ?? [];
$transferId = isset($transfer_id) ? (int) $transfer_id : (int) ($transfer['id'] ?? 0);
$totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;
$pgBase = '?page=products&action=stock_transfer_items&transfer_id=' . $transferId . '&limit=' . $limit;

$rawStatus = trim((string) ($transfer['status'] ?? ''));
$statusLabel = '';
if ($rawStatus !== '') {
    $norm = str_replace('_', ' ', $rawStatus);
    $lower = function_exists('mb_strtolower') ? mb_strtolower($norm, 'UTF-8') : strtolower($norm);
    $statusLabel = function_exists('mb_convert_case') ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8') : ucwords($lower);
}

$statusClass = static function (?string $status): string {
    $s = strtolower(trim((string) $status));
    $s = str_replace(['_', '-'], ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s) ?? $s;
    $s = trim($s);

    return match ($s) {
        'completed', 'complete', 'received' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
        'cancelled', 'canceled' => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        'in transit', 'dispatched' => 'bg-sky-50 text-sky-800 ring-sky-600/20',
        'pending', 'draft' => 'bg-amber-50 text-amber-900 ring-amber-600/25',
        default => 'bg-slate-50 text-slate-700 ring-slate-500/15',
    };
};

$statusRing = $statusClass($rawStatus);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="mb-6">
        <a href="?page=products&action=stock_transfer"
            class="inline-flex items-center gap-2 rounded-full border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 transition">
            <i class="fas fa-arrow-left text-xs text-gray-500" aria-hidden="true"></i>
            Stock transfer history
        </a>
    </div>

    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-list-ul text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Transfer · Line items</span>
                </div>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 md:gap-6">
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900 shrink-0">
                        Transfer <span class="text-amber-800">items</span>
                    </h1>
                    <div class="rounded-xl border border-white/80 bg-white/70 px-4 py-3 shadow-sm ring-1 ring-gray-900/5 backdrop-blur-sm w-full md:w-auto md:min-w-[14rem] md:max-w-md">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-gray-500 mb-2">Route</div>
                        <div class="flex flex-col gap-2 text-sm text-gray-800">
                            <span class="inline-flex items-start gap-2" title="Source">
                                <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs shrink-0" aria-hidden="true"></i>
                                <span class="break-words"><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></span>
                            </span>
                            <span class="inline-flex items-start gap-2" title="Destination">
                                <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs shrink-0" aria-hidden="true"></i>
                                <span class="break-words"><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
                <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-gray-600">
                    <span class="font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($transfer['transfer_order_no'] ?? '—'); ?></span>
                    <?php if (!empty($transfer['dispatch_date'])): ?>
                        <span class="text-gray-300">·</span>
                        <span>Dispatch <span class="font-medium text-gray-800"><?php echo htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))); ?></span></span>
                    <?php endif; ?>
                    <?php if ($statusLabel !== ''): ?>
                        <span class="text-gray-300">·</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset shadow-sm <?php echo $statusRing; ?>"><?php echo htmlspecialchars($statusLabel); ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-col gap-2 min-w-0 shrink-0 lg:self-center lg:pl-4">
                <a href="?page=products&action=transfer_stock&transfer_id=<?php echo urlencode($transferId); ?>"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-edit text-xs opacity-95" aria-hidden="true"></i>
                    Edit transfer
                </a>
                <a href="?page=stock_transfer_grns&action=list&transfer_id=<?php echo urlencode($transferId); ?>"
                    target="_blank" rel="noopener noreferrer"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-800 text-xs font-semibold hover:bg-gray-50 transition w-full sm:w-auto">
                    <i class="fas fa-file-invoice text-emerald-600" aria-hidden="true"></i>
                    View GRNs
                </a>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 border-b border-gray-100 bg-gradient-to-r from-amber-50/40 via-gray-50/80 to-gray-50/80 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Items</h2>
                <p class="text-xs text-gray-500 mt-0.5">SKUs, codes, and quantities for this transfer.</p>
            </div>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                <label class="sr-only" for="transferItemSearch">Search items</label>
                <div class="relative w-full sm:w-64">
                    <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" aria-hidden="true"></i>
                    <input type="search" id="transferItemSearch" autocomplete="off" placeholder="Search SKU, code, notes…"
                        class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" />
                </div>
                <p id="transferItemSearchCount" class="text-xs text-gray-600 tabular-nums whitespace-nowrap shrink-0">
                    <span class="font-semibold text-gray-900" id="transferVisibleCount"><?php echo count($items); ?></span>
                    / <?php echo count($items); ?> on this page
                </p>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">SKU / code</th>
                        <th scope="col" class="px-5 py-3.5 text-right whitespace-nowrap">Qty</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[12rem]">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-16 text-center">
                                <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-3 mx-auto">
                                    <i class="fas fa-box-open" aria-hidden="true"></i>
                                </span>
                                <p class="text-sm font-medium text-gray-900">No line items on this page</p>
                                <p class="text-sm text-gray-500 mt-1">Try another page or edit the transfer to add items.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                                $label = trim($item['sku'] ?? '');
                                if ($label === '') {
                                    $label = trim($item['item_code'] ?? '');
                                }
                                if ($label === '') {
                                    $label = '#' . (int) ($item['id'] ?? 0);
                                }
                                $rowId = (int) ($item['id'] ?? 0);
                                $notesRaw = trim($item['item_notes'] ?? '');
                                $searchBlob = strtolower(implode(' ', array_filter([
                                    (string) $rowId,
                                    (string) ($item['sku'] ?? ''),
                                    (string) ($item['item_code'] ?? ''),
                                    $label,
                                    $notesRaw,
                                ])));
                            ?>
                            <tr class="transfer-item-row hover:bg-amber-50/40 transition-colors" data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="px-5 py-3.5 text-sm text-gray-500 tabular-nums"><?php echo $rowId; ?></td>
                                <td class="px-5 py-3.5 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($label); ?></td>
                                <td class="px-5 py-3.5 text-sm text-gray-800 text-right tabular-nums font-medium"><?php echo number_format((int) ($item['transfer_qty'] ?? 0)); ?></td>
                                <td class="px-5 py-3.5 text-sm text-gray-600 max-w-[20rem]">
                                    <span class="line-clamp-2" title="<?php echo htmlspecialchars($notesRaw, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($notesRaw ?: '—'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-2xl border border-gray-200/90 bg-white px-5 py-4 shadow-sm ring-1 ring-gray-900/[0.03]">
            <p class="text-sm text-gray-600">
                Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo ($pageNo - 1) * $limit + 1; ?></span>
                –
                <span class="font-medium text-gray-900 tabular-nums"><?php echo min($pageNo * $limit, $totalRecords); ?></span>
                of <span class="font-medium text-gray-900 tabular-nums"><?php echo number_format($totalRecords); ?></span>
            </p>
            <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=1'); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-amber-50/50 hover:border-amber-200'; ?>">First</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . max(1, $pageNo - 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-amber-50/50 hover:border-amber-200'; ?>">Previous</a>
                <span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums">Page <?php echo $pageNo; ?> / <?php echo $totalPages; ?></span>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . min($totalPages, $pageNo + 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-amber-50/50 hover:border-amber-200'; ?>">Next</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . $totalPages); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-amber-50/50 hover:border-amber-200'; ?>">Last</a>
            </nav>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var input = document.getElementById('transferItemSearch');
    if (!input) return;
    var rows = document.querySelectorAll('tr.transfer-item-row');
    var visibleEl = document.getElementById('transferVisibleCount');
    var onPage = rows.length;

    function runFilter() {
        var q = input.value.trim().toLowerCase().replace(/\s+/g, ' ');
        var n = 0;
        rows.forEach(function (tr) {
            var hay = (tr.getAttribute('data-search') || '');
            var show = !q || hay.indexOf(q) !== -1;
            tr.style.display = show ? '' : 'none';
            if (show) n++;
        });
        if (visibleEl) visibleEl.textContent = String(n);
    }

    input.addEventListener('input', runFilter);
    input.addEventListener('search', runFilter);
})();
</script>
