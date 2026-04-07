<?php
$transfer = $transfer ?? null;
$grns = $grns ?? [];
$transferId = isset($transferId) ? (int) $transferId : 0;
$grnTotal = count($grns);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Page header -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-6">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 text-amber-800/90 text-sm font-medium mb-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <i class="fas fa-file-invoice" aria-hidden="true"></i>
                </span>
                <span>Stock transfer</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">GRN line items</h1>
            <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-2xl">
                Receipt lines recorded against stock transfers. Filter by item group, SKU, or item code.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 shrink-0">
            <a href="?page=products&action=stock_transfer"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-semibold hover:bg-gray-50 transition">
                <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                Transfer history
            </a>
            <?php if (!empty($transfer['id'])): ?>
                <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo (int) $transfer['id']; ?>"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-md shadow-amber-900/15 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                    Add GRN
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($transfer)): ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80">
                <h2 class="text-sm font-semibold text-gray-900">Transfer summary</h2>
                <p class="text-xs text-gray-500 mt-0.5">Details for the filtered transfer</p>
            </div>
            <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-8 gap-y-4 text-sm">
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Transfer order</div>
                    <div class="font-mono font-semibold text-gray-900 mt-0.5"><?php echo htmlspecialchars($transfer['transfer_order_no'] ?? '—'); ?></div>
                </div>
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Dispatch date</div>
                    <div class="text-gray-900 mt-0.5">
                        <?php echo !empty($transfer['dispatch_date']) ? htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))) : '—'; ?>
                    </div>
                </div>
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Status</div>
                    <div class="text-gray-900 mt-0.5"><?php echo htmlspecialchars(trim((string)($transfer['status'] ?? '')) ?: '—'); ?></div>
                </div>
                <div>
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">GRN lines (this view)</div>
                    <div class="text-gray-900 tabular-nums mt-0.5 font-medium"><?php echo number_format($grnTotal); ?></div>
                </div>
                <div class="sm:col-span-2">
                    <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Route</div>
                    <div class="mt-1 text-gray-800 flex flex-col gap-1">
                        <span class="inline-flex items-start gap-1.5">
                            <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs" aria-hidden="true"></i>
                            <span title="Source"><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></span>
                        </span>
                        <span class="inline-flex items-start gap-1.5">
                            <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs" aria-hidden="true"></i>
                            <span title="Destination"><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></span>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($transferId <= 0): ?>
        <div class="rounded-xl border border-amber-100 bg-amber-50/50 px-4 py-3 text-sm text-amber-900 mb-6">
            <strong class="font-semibold">All transfers.</strong>
            Showing GRN lines from every stock transfer. Open a transfer from
            <a href="?page=products&action=stock_transfer" class="font-medium underline decoration-amber-800/30 underline-offset-2">stock transfer history</a>
            and choose <em>View GRNs</em> to filter by one transfer.
        </div>
    <?php endif; ?>

    <?php if (empty($grns)): ?>
        <div class="bg-white border border-gray-200 p-6 rounded-2xl text-gray-600 text-center">
            No GRN records found<?php echo !empty($transfer) ? ' for this transfer.' : '.'; ?>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col gap-4">
                <form id="grnLineFilterForm" class="space-y-3" action="javascript:void(0)" novalidate>
                    <div class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Filter line items</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3 lg:gap-4 lg:items-end">
                        <div class="lg:col-span-4">
                            <label for="grnFilterItemGroup" class="block text-xs font-semibold text-gray-600 mb-1.5">Item group</label>
                            <div class="relative">
                                <i class="fas fa-layer-group absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" aria-hidden="true"></i>
                                <input type="search" name="grn_filter_group" id="grnFilterItemGroup" autocomplete="off"
                                    placeholder="Product group / category"
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" />
                            </div>
                        </div>
                        <div class="lg:col-span-4">
                            <label for="grnFilterSkuCode" class="block text-xs font-semibold text-gray-600 mb-1.5">Item code / SKU</label>
                            <div class="relative">
                                <i class="fas fa-barcode absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm pointer-events-none" aria-hidden="true"></i>
                                <input type="search" name="grn_filter_sku" id="grnFilterSkuCode" autocomplete="off"
                                    placeholder="Match code or SKU"
                                    class="w-full pl-9 pr-3 py-2.5 rounded-lg border border-gray-300 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" />
                            </div>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 lg:col-span-4">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold shadow-sm hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                                <i class="fas fa-search text-xs" aria-hidden="true"></i>
                                Search
                            </button>
                            <button type="button" id="grnFilterClear" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-800 text-sm font-semibold hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400/40 transition">
                                Clear
                            </button>
                        </div>
                    </div>
                </form>
                <p id="grnSearchCount" class="text-sm text-gray-600 tabular-nums">
                    Showing <span class="font-medium text-gray-900" id="grnVisibleCount"><?php echo (int) $grnTotal; ?></span>
                    of <span id="grnTotalCount"><?php echo (int) $grnTotal; ?></span>
                    line<?php echo $grnTotal === 1 ? '' : 's'; ?>
                </p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <tr>
                            <th class="px-4 py-3 whitespace-nowrap">GRN ID</th>
                            <th class="px-4 py-3 whitespace-nowrap">Transfer order</th>
                            <th class="px-4 py-3 whitespace-nowrap">Item group</th>
                            <th class="px-4 py-3 whitespace-nowrap">SKU</th>
                            <th class="px-4 py-3 whitespace-nowrap">Item code</th>
                            <th class="px-4 py-3 whitespace-nowrap">Received</th>
                            <th class="px-4 py-3 whitespace-nowrap">Received by</th>
                            <th class="px-4 py-3 whitespace-nowrap">Location</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Qty rec.</th>
                            <th class="px-4 py-3 text-right whitespace-nowrap">Qty ok</th>
                            <th class="px-4 py-3 min-w-[8rem]">Remarks</th>
                            <th class="px-4 py-3 whitespace-nowrap">Created</th>
                            <th class="px-4 py-3 whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($grns as $grn): ?>
                            <?php
                                $itemGroup = trim((string) ($grn['item_group'] ?? ''));
                                $groupNorm = strtolower(preg_replace('/\s+/', ' ', $itemGroup));
                                $skuNorm = strtolower(preg_replace('/\s+/', ' ', trim((string) ($grn['sku'] ?? ''))));
                                $codeNorm = strtolower(preg_replace('/\s+/', ' ', trim((string) ($grn['item_code'] ?? ''))));
                                $searchParts = [
                                    (string) ($grn['id'] ?? ''),
                                    (string) ($grn['transfer_order_no'] ?? ''),
                                    $itemGroup,
                                    (string) ($grn['sku'] ?? ''),
                                    (string) ($grn['item_code'] ?? ''),
                                    (string) ($grn['size'] ?? ''),
                                    (string) ($grn['color'] ?? ''),
                                    (string) ($grn['transfer_qty'] ?? ''),
                                    (string) ($grn['qty_received'] ?? ''),
                                    (string) ($grn['qty_acceptable'] ?? ''),
                                    (string) ($grn['received_by_name'] ?? ''),
                                    (string) ($grn['location_name'] ?? ''),
                                    (string) ($grn['remarks'] ?? ''),
                                ];
                                if (!empty($grn['received_date'])) {
                                    $searchParts[] = date('Y-m-d', strtotime($grn['received_date']));
                                    $searchParts[] = date('j M Y', strtotime($grn['received_date']));
                                }
                                if (!empty($grn['created_at'])) {
                                    $searchParts[] = date('Y-m-d', strtotime($grn['created_at']));
                                    $searchParts[] = date('j M Y', strtotime($grn['created_at']));
                                    $searchParts[] = date('Y-m-d H:i', strtotime($grn['created_at']));
                                }
                                $searchBlob = strtolower(preg_replace('/\s+/', ' ', trim(implode(' ', $searchParts))));
                            ?>
                            <tr class="grn-item-row hover:bg-amber-50/30 transition-colors"
                                data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>"
                                data-item-group="<?php echo htmlspecialchars($groupNorm, ENT_QUOTES, 'UTF-8'); ?>"
                                data-sku="<?php echo htmlspecialchars($skuNorm, ENT_QUOTES, 'UTF-8'); ?>"
                                data-item-code="<?php echo htmlspecialchars($codeNorm, ENT_QUOTES, 'UTF-8'); ?>">
                                <td class="px-4 py-3 tabular-nums text-gray-700"><?php echo (int) $grn['id']; ?></td>
                                <td class="px-4 py-3 font-mono text-xs font-medium text-gray-900"><?php echo htmlspecialchars($grn['transfer_order_no'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-700 max-w-[12rem] truncate" title="<?php echo htmlspecialchars($itemGroup, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $itemGroup !== '' ? htmlspecialchars($itemGroup) : '—'; ?></td>
                                <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($grn['sku'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($grn['item_code'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap"><?php echo !empty($grn['received_date']) ? htmlspecialchars(date('j M Y', strtotime($grn['received_date']))) : '—'; ?></td>
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($grn['received_by_name'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($grn['location_name'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-right tabular-nums"><?php echo (int) ($grn['qty_received'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right tabular-nums"><?php echo (int) ($grn['qty_acceptable'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-gray-600 max-w-[14rem] truncate" title="<?php echo htmlspecialchars((string) ($grn['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($grn['remarks'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap text-xs"><?php echo !empty($grn['created_at']) ? htmlspecialchars(date('j M Y H:i', strtotime($grn['created_at']))) : '—'; ?></td>
                                <td class="px-4 py-3">
                                    <a href="javascript:if(confirm('Delete this GRN?')) window.location='?page=stock_transfer_grns&action=delete&grn_id=<?php echo (int) $grn['id']; ?>&transfer_id=<?php echo urlencode((string) $transferId); ?>'"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('grnLineFilterForm');
    var groupInput = document.getElementById('grnFilterItemGroup');
    var skuInput = document.getElementById('grnFilterSkuCode');
    var clearBtn = document.getElementById('grnFilterClear');
    if (!form || !groupInput || !skuInput) return;

    var rows = document.querySelectorAll('tr.grn-item-row');
    var visibleEl = document.getElementById('grnVisibleCount');

    function norm(s) {
        return String(s || '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function tokens(s) {
        return norm(s)
            .split(/\s+/)
            .filter(function (t) { return t.length > 0; });
    }

    function rowMatches(tr, groupQ, skuQ) {
        var gTokens = tokens(groupQ);
        var sTokens = tokens(skuQ);
        var rowGroup = (tr.getAttribute('data-item-group') || '');
        var rowSku = (tr.getAttribute('data-sku') || '');
        var rowCode = (tr.getAttribute('data-item-code') || '');

        if (gTokens.length > 0 && !gTokens.every(function (t) { return rowGroup.indexOf(t) !== -1; })) {
            return false;
        }
        if (sTokens.length > 0) {
            var haySkuCode = rowSku + ' ' + rowCode;
            if (!sTokens.every(function (t) { return haySkuCode.indexOf(t) !== -1; })) {
                return false;
            }
        }
        return true;
    }

    function runFilter() {
        var gq = groupInput.value;
        var sq = skuInput.value;
        var n = 0;
        rows.forEach(function (tr) {
            var show = rowMatches(tr, gq, sq);
            tr.style.display = show ? '' : 'none';
            if (show) n++;
        });
        if (visibleEl) visibleEl.textContent = String(n);
    }

    function pushQueryToUrl() {
        try {
            var u = new URL(window.location.href);
            var g = norm(groupInput.value);
            var s = norm(skuInput.value);
            if (g) u.searchParams.set('grn_group', groupInput.value.trim()); else u.searchParams.delete('grn_group');
            if (s) u.searchParams.set('grn_sku', skuInput.value.trim()); else u.searchParams.delete('grn_sku');
            u.searchParams.delete('grn_search');
            u.searchParams.delete('q');
            window.history.replaceState({}, '', u.pathname + u.search + u.hash);
        } catch (e) { /* ignore */ }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        runFilter();
        pushQueryToUrl();
    });

    groupInput.addEventListener('input', runFilter);
    groupInput.addEventListener('search', runFilter);
    skuInput.addEventListener('input', runFilter);
    skuInput.addEventListener('search', runFilter);

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            groupInput.value = '';
            skuInput.value = '';
            runFilter();
            pushQueryToUrl();
            groupInput.focus();
        });
    }

    try {
        var params = new URLSearchParams(window.location.search);
        var pg = params.get('grn_group') || '';
        var ps = params.get('grn_sku') || params.get('grn_search') || params.get('q') || '';
        if (pg) groupInput.value = pg;
        if (ps) skuInput.value = ps;
        if (pg || ps) runFilter();
    } catch (e) { /* ignore */ }
})();
</script>
