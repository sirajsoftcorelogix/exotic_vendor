<?php
$transfer = $transfer ?? null;
$grns = $grns ?? [];
$transferId = isset($transferId) ? (int) $transferId : 0;
$grnTotal = count($grns);

if (!function_exists('grn_item_group_camel_case')) {
    /**
     * Display item group as camelCase (e.g. "Home Decor" → "homeDecor").
     */
    function grn_item_group_camel_case($label) {
        $label = trim((string) $label);
        if ($label === '') {
            return '';
        }
        $parts = preg_split('/[\s\-_\/|&,.]+/u', $label, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false || $parts === []) {
            return $label;
        }
        $out = function_exists('mb_strtolower') ? mb_strtolower($parts[0], 'UTF-8') : strtolower($parts[0]);
        for ($i = 1, $n = count($parts); $i < $n; $i++) {
            $w = $parts[$i];
            if ($w === '') {
                continue;
            }
            if (function_exists('mb_substr')) {
                $out .= mb_strtoupper(mb_substr($w, 0, 1, 'UTF-8'), 'UTF-8')
                    . mb_strtolower(mb_substr($w, 1, null, 'UTF-8'), 'UTF-8');
            } else {
                $out .= strtoupper(substr($w, 0, 1)) . strtolower(substr($w, 1));
            }
        }
        return $out;
    }
}
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
        <?php
        $grnGroupOptions = [];
        foreach ($grns as $_grn) {
            $_ig = trim((string) ($_grn['item_group'] ?? ''));
            $_gn = strtolower(preg_replace('/\s+/', ' ', $_ig));
            $_key = $_gn === '' ? '__none__' : $_gn;
            if (!isset($grnGroupOptions[$_key])) {
                $grnGroupOptions[$_key] = $_ig;
            }
        }
        uksort($grnGroupOptions, function ($a, $b) {
            if ($a === '__none__') {
                return 1;
            }
            if ($b === '__none__') {
                return -1;
            }
            return strcmp($a, $b);
        });
        ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 flex flex-col gap-4">
                <form id="grnLineFilterForm" class="space-y-3" action="javascript:void(0)" novalidate>
                    <div class="text-xs font-semibold text-gray-700 uppercase tracking-wide">Filter line items</div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-3 lg:gap-4 lg:items-end">
                        <div class="lg:col-span-4 relative">
                            <span id="grnGroupMultiselectLabel" class="block text-xs font-semibold text-gray-600 mb-1.5">Item group (category)</span>
                            <button type="button" id="grnGroupMultiselectBtn"
                                class="flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-left text-sm text-gray-900 shadow-sm hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                                aria-expanded="false" aria-haspopup="listbox" aria-controls="grnGroupMultiselectPanel">
                                <span class="flex min-w-0 items-center gap-2">
                                    <i class="fas fa-layer-group shrink-0 text-gray-400 text-sm" aria-hidden="true"></i>
                                    <span id="grnGroupMultiselectSummary" class="truncate min-w-0">All categories</span>
                                </span>
                                <i class="fas fa-chevron-down shrink-0 text-xs text-gray-400" style="transition: transform 0.15s ease" id="grnGroupMultiselectChevron" aria-hidden="true"></i>
                            </button>
                            <div id="grnGroupMultiselectPanel" role="listbox" aria-multiselectable="true"
                                class="hidden absolute left-0 right-0 z-30 mt-1 max-h-60 overflow-y-auto rounded-lg border border-gray-200 bg-white py-2 shadow-lg ring-1 ring-black/5">
                                <div class="sticky top-0 z-10 border-b border-gray-100 bg-white px-2 pb-2 mb-1">
                                    <div class="flex gap-2 justify-end">
                                        <button type="button" id="grnGroupSelectAll" class="text-[11px] font-semibold text-amber-700 hover:text-amber-900 px-1 py-0.5">Select all</button>
                                        <button type="button" id="grnGroupSelectNone" class="text-[11px] font-semibold text-gray-600 hover:text-gray-900 px-1 py-0.5">Clear</button>
                                    </div>
                                </div>
                                <div class="px-2 space-y-0.5">
                                    <?php foreach ($grnGroupOptions as $optKey => $optLabel): ?>
                                        <?php
                                            $cbId = 'grn_grp_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $optKey);
                                            if (strlen($cbId) > 80) {
                                                $cbId = 'grn_grp_' . md5($optKey);
                                            }
                                            $titleLabel = $optKey === '__none__' ? 'No group' : ($optLabel !== '' ? $optLabel : $optKey);
                                            $showLabel = $optKey === '__none__' ? 'noGroup' : grn_item_group_camel_case($optLabel !== '' ? $optLabel : $optKey);
                                        ?>
                                        <label class="flex cursor-pointer items-center gap-2.5 rounded-md px-2 py-2 text-sm text-gray-800 hover:bg-amber-50/80 font-mono text-xs" for="<?php echo htmlspecialchars($cbId); ?>"
                                            title="<?php echo htmlspecialchars($titleLabel, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="checkbox" name="grn_filter_group[]" id="<?php echo htmlspecialchars($cbId); ?>"
                                                value="<?php echo htmlspecialchars($optKey, ENT_QUOTES, 'UTF-8'); ?>"
                                                class="grn-group-cb h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500/50" />
                                            <span class="min-w-0 flex-1 leading-snug capitalize"><?php echo htmlspecialchars($showLabel); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
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

                <div class="rounded-xl border border-amber-200/90 bg-gradient-to-br from-amber-50/90 via-white to-stone-50/60 p-4 sm:p-5 shadow-sm ring-1 ring-amber-900/5">
                    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                        <div class="min-w-0 space-y-1">
                            <div class="text-xs font-semibold uppercase tracking-wide text-amber-950/85">Label printing</div>
                            <p class="text-xs sm:text-sm text-stone-600 max-w-xl leading-relaxed">
                                Every line below starts in the print queue (checked). Adjust labels-per-line, pick a template, then print. Lines without a matching catalog product are skipped.
                            </p>
                        </div>
                        <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-end gap-3 shrink-0">
                            <div class="min-w-[11rem]">
                                <label for="grnLabelTemplate" class="block text-[11px] font-semibold text-stone-600 mb-1">Template</label>
                                <select id="grnLabelTemplate" class="w-full rounded-lg border border-stone-200 bg-white px-3 py-2.5 text-sm text-stone-800 shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/30">
                                    <option value="jewelry">Jewelry — 100 × 12.9 mm</option>
                                    <option value="textile">Textile — 64 × 34 mm</option>
                                    <option value="mg_store">MG Road — 75 × 50 mm</option>
                                </select>
                            </div>
                            <button type="button" id="grnLabelPrintBtn"
                                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-amber-600 to-amber-500 text-white text-sm font-semibold shadow-md shadow-amber-600/20 hover:from-amber-500 hover:to-amber-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                                <i class="fas fa-print text-xs opacity-90" aria-hidden="true"></i>
                                Print labels
                            </button>
                        </div>
                    </div>
                    <div class="mt-4 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-stone-600 border-t border-amber-200/50 pt-3">
                        <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" id="grnLabelUncheckHidden" class="h-4 w-4 rounded border-stone-300 text-amber-600 focus:ring-amber-500/40" checked />
                            <span>Uncheck lines hidden by filters</span>
                        </label>
                        <span class="hidden sm:inline text-stone-300" aria-hidden="true">|</span>
                        <button type="button" id="grnLabelSelectAll" class="font-semibold text-amber-800 hover:text-amber-950 underline decoration-amber-300 underline-offset-2">Select all</button>
                        <button type="button" id="grnLabelSelectVisible" class="font-semibold text-amber-800 hover:text-amber-950 underline decoration-amber-300 underline-offset-2">Select visible only</button>
                        <button type="button" id="grnLabelSelectNone" class="font-semibold text-stone-600 hover:text-stone-900 underline decoration-stone-300 underline-offset-2">Clear selection</button>
                        <span id="grnLabelQueueSummary" class="ml-auto tabular-nums text-stone-500"></span>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left text-sm">
                    <thead class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <tr>
                            <th class="px-3 py-3 whitespace-nowrap w-[7.5rem]" title="Queue for label printing">Print</th>
                            <th class="px-4 py-3 whitespace-nowrap">GRN ID</th>
                            <th class="px-4 py-3 whitespace-nowrap">Item group</th>
                            <th class="px-4 py-3 whitespace-nowrap">SKU</th>
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
                            <?php $groupKey = $groupNorm === '' ? '__none__' : $groupNorm; ?>
                            <?php
                                $labelPid = (int)($grn['label_product_id'] ?? 0);
                                $labelQty = (int)($grn['label_default_qty'] ?? 1);
                                if ($labelQty < 1) {
                                    $labelQty = 1;
                                }
                                if ($labelQty > 99) {
                                    $labelQty = 99;
                                }
                                $labelResolvable = $labelPid > 0;
                            ?>
                            <tr class="grn-item-row hover:bg-amber-50/30 transition-colors"
                                data-search="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>"
                                data-item-group="<?php echo htmlspecialchars($groupNorm, ENT_QUOTES, 'UTF-8'); ?>"
                                data-item-group-key="<?php echo htmlspecialchars($groupKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-sku="<?php echo htmlspecialchars($skuNorm, ENT_QUOTES, 'UTF-8'); ?>"
                                data-item-code="<?php echo htmlspecialchars($codeNorm, ENT_QUOTES, 'UTF-8'); ?>"
                                data-label-product-id="<?php echo $labelPid; ?>">
                                <td class="px-3 py-3 align-middle">
                                    <div class="flex items-center gap-2">
                                        <input type="checkbox" class="grn-label-cb h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500/50 shrink-0" <?php echo $labelResolvable ? 'checked' : 'disabled'; ?>
                                            <?php echo !$labelResolvable ? ' title="No matching product in catalog for this line"' : ''; ?> />
                                        <label class="sr-only">Labels qty</label>
                                        <input type="number" min="1" max="99" value="<?php echo $labelQty; ?>"
                                            class="grn-label-qty w-12 rounded border border-gray-200 px-1.5 py-1 text-xs tabular-nums text-center <?php echo !$labelResolvable ? 'opacity-40 cursor-not-allowed bg-gray-50' : ''; ?>"
                                            <?php echo !$labelResolvable ? 'disabled' : ''; ?> />
                                    </div>
                                    <?php if (!$labelResolvable): ?>
                                        <div class="text-[10px] text-amber-800/90 mt-1 max-w-[6rem] leading-tight" title="Resolve item code / SKU + size / color in catalog">No match</div>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-gray-700"><?php echo (int) $grn['id']; ?></td>
                                <td class="px-4 py-3 text-gray-700 max-w-[12rem] truncate capitalize"
                                    <?php echo $itemGroup !== '' ? 'title="' . htmlspecialchars($itemGroup, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>><?php echo $itemGroup !== '' ? htmlspecialchars(grn_item_group_camel_case($itemGroup)) : '—'; ?></td>
                                <td class="px-4 py-3 text-gray-800"><?php echo htmlspecialchars($grn['sku'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-700 whitespace-nowrap"><?php echo !empty($grn['received_date']) ? htmlspecialchars(date('j M Y', strtotime($grn['received_date']))) : '—'; ?></td>
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($grn['received_by_name'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-700"><?php echo htmlspecialchars($grn['location_name'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-right tabular-nums"><?php echo (int) ($grn['qty_received'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-right tabular-nums"><?php echo (int) ($grn['qty_acceptable'] ?? 0); ?></td>
                                <td class="px-4 py-3 text-gray-600 max-w-[14rem] truncate" title="<?php echo htmlspecialchars((string) ($grn['remarks'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($grn['remarks'] ?? ''); ?></td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap text-xs"><?php echo !empty($grn['created_at']) ? htmlspecialchars(date('j M Y H:i', strtotime($grn['created_at']))) : '—'; ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                        $_delUrl = '?page=stock_transfer_grns&action=delete&grn_id=' . (int) $grn['id'] . '&transfer_id=' . urlencode((string) $transferId);
                                        $_delSku = trim((string) ($grn['sku'] ?? ''));
                                    ?>
                                    <button type="button"
                                        class="grn-delete-open inline-flex items-center gap-1 px-2.5 py-1 rounded-md bg-red-600 text-white text-xs font-semibold hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition"
                                        data-delete-url="<?php echo htmlspecialchars($_delUrl, ENT_QUOTES, 'UTF-8'); ?>"
                                        data-grn-id="<?php echo (int) $grn['id']; ?>"
                                        data-grn-sku="<?php echo htmlspecialchars($_delSku, ENT_QUOTES, 'UTF-8'); ?>">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<div id="grnDeleteModal" class="fixed inset-0 z-[200] hidden" role="dialog" aria-modal="true" aria-labelledby="grnDeleteModalTitle">
    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px] transition-opacity" data-grn-delete-backdrop tabindex="-1"></div>
    <div class="relative flex min-h-full items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-md rounded-2xl border border-gray-200/80 bg-white shadow-2xl shadow-slate-900/15 ring-1 ring-slate-900/5 overflow-hidden">
            <div class="px-5 pt-5 pb-4 border-b border-gray-100 bg-gradient-to-br from-red-50/80 to-white">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <i class="fas fa-exclamation-triangle text-lg" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <h3 id="grnDeleteModalTitle" class="text-base font-semibold text-gray-900 leading-snug">Delete this GRN line?</h3>
                        <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">This removes the receipt line and related records cannot be recovered from this screen.</p>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 bg-white">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-3">You are about to delete</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl border-2 border-slate-200 bg-slate-50/90 px-4 py-3.5 ring-1 ring-slate-900/[0.04] shadow-sm">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">GRN ID</div>
                        <div id="grnDeleteMetaId" class="text-2xl sm:text-[1.65rem] font-bold tabular-nums tracking-tight text-slate-900 font-mono leading-none">—</div>
                    </div>
                    <div class="rounded-xl border-2 border-amber-300/80 bg-gradient-to-br from-amber-50 to-amber-100/50 px-4 py-3.5 ring-1 ring-amber-900/10 shadow-sm">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-amber-900/70 mb-1.5">SKU</div>
                        <div id="grnDeleteMetaSku" class="text-lg sm:text-xl font-bold text-amber-950 font-mono break-all leading-snug">—</div>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 bg-gray-50/80 border-t border-gray-100">
                <button type="button" data-grn-delete-cancel
                    class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="button" data-grn-delete-confirm
                    class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-b from-red-600 to-red-700 text-white text-sm font-semibold shadow-md shadow-red-900/20 hover:from-red-700 hover:to-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition">
                    <i class="fas fa-trash-alt text-xs opacity-90" aria-hidden="true"></i>
                    Delete GRN
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('grnDeleteModal');
    var metaIdEl = document.getElementById('grnDeleteMetaId');
    var metaSkuEl = document.getElementById('grnDeleteMetaSku');
    var pendingUrl = '';

    function openGrnDeleteModal(url, id, sku) {
        pendingUrl = url || '';
        if (metaIdEl) metaIdEl.textContent = id ? String(id) : '—';
        if (metaSkuEl) metaSkuEl.textContent = sku && String(sku).trim() !== '' ? String(sku).trim() : '—';
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        var c = modal.querySelector('[data-grn-delete-confirm]');
        if (c) c.focus();
    }

    function closeGrnDeleteModal() {
        pendingUrl = '';
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.grn-delete-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openGrnDeleteModal(
                btn.getAttribute('data-delete-url') || '',
                btn.getAttribute('data-grn-id') || '',
                btn.getAttribute('data-grn-sku') || ''
            );
        });
    });

    if (modal) {
        modal.querySelector('[data-grn-delete-cancel]') && modal.querySelector('[data-grn-delete-cancel]').addEventListener('click', closeGrnDeleteModal);
        modal.querySelector('[data-grn-delete-backdrop]') && modal.querySelector('[data-grn-delete-backdrop]').addEventListener('click', closeGrnDeleteModal);
        modal.querySelector('[data-grn-delete-confirm]') && modal.querySelector('[data-grn-delete-confirm]').addEventListener('click', function () {
            if (pendingUrl) window.location.href = pendingUrl;
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeGrnDeleteModal();
            }
        });
    }
})();
</script>

<script>
(function () {
    var form = document.getElementById('grnLineFilterForm');
    var skuInput = document.getElementById('grnFilterSkuCode');
    var clearBtn = document.getElementById('grnFilterClear');
    var groupBtn = document.getElementById('grnGroupMultiselectBtn');
    var groupPanel = document.getElementById('grnGroupMultiselectPanel');
    var groupSummary = document.getElementById('grnGroupMultiselectSummary');
    var groupChevron = document.getElementById('grnGroupMultiselectChevron');
    var groupSelectAll = document.getElementById('grnGroupSelectAll');
    var groupSelectNone = document.getElementById('grnGroupSelectNone');
    if (!form || !skuInput) return;

    var rows = document.querySelectorAll('tr.grn-item-row');
    var visibleEl = document.getElementById('grnVisibleCount');
    var groupCbs = function () { return document.querySelectorAll('.grn-group-cb'); };

    function norm(s) {
        return String(s || '').trim().toLowerCase().replace(/\s+/g, ' ');
    }

    function tokens(s) {
        return norm(s)
            .split(/\s+/)
            .filter(function (t) { return t.length > 0; });
    }

    function selectedGroupKeys() {
        var keys = [];
        groupCbs().forEach(function (cb) {
            if (cb.checked) keys.push(cb.value);
        });
        return keys;
    }

    function updateGroupSummary() {
        if (!groupSummary) return;
        var total = groupCbs().length;
        var sel = selectedGroupKeys();
        if (sel.length === 0 || sel.length === total) {
            groupSummary.textContent = 'All categories';
            return;
        }
        if (sel.length === 1) {
            var spanText = '1 selected';
            groupCbs().forEach(function (cb) {
                if (cb.value === sel[0]) {
                    var lbl = cb.closest('label');
                    var span = lbl && lbl.querySelector('span:last-child');
                    if (span) spanText = span.textContent.trim();
                }
            });
            groupSummary.textContent = spanText;
            return;
        }
        groupSummary.textContent = sel.length + ' categories';
    }

    function setGroupPanelOpen(open) {
        if (!groupPanel || !groupBtn) return;
        if (open) {
            groupPanel.classList.remove('hidden');
            groupBtn.setAttribute('aria-expanded', 'true');
            if (groupChevron) groupChevron.style.transform = 'rotate(180deg)';
        } else {
            groupPanel.classList.add('hidden');
            groupBtn.setAttribute('aria-expanded', 'false');
            if (groupChevron) groupChevron.style.transform = '';
        }
    }

    function rowMatches(tr, selectedKeys, skuQ) {
        var sTokens = tokens(skuQ);
        var rowKey = tr.getAttribute('data-item-group-key') || '';
        if (selectedKeys.length > 0) {
            if (selectedKeys.indexOf(rowKey) === -1) return false;
        }
        if (sTokens.length > 0) {
            var rowSku = (tr.getAttribute('data-sku') || '');
            var rowCode = (tr.getAttribute('data-item-code') || '');
            var haySkuCode = rowSku + ' ' + rowCode;
            if (!sTokens.every(function (t) { return haySkuCode.indexOf(t) !== -1; })) {
                return false;
            }
        }
        return true;
    }

    function runFilter() {
        var keys = selectedGroupKeys();
        var totalCb = groupCbs().length;
        var effectiveKeys = (keys.length === 0 || keys.length === totalCb) ? [] : keys;
        var sq = skuInput.value;
        var n = 0;
        rows.forEach(function (tr) {
            var show = rowMatches(tr, effectiveKeys, sq);
            tr.style.display = show ? '' : 'none';
            if (show) n++;
        });
        if (visibleEl) visibleEl.textContent = String(n);
        updateGroupSummary();
        if (typeof window.grnLabelAfterFilter === 'function') {
            window.grnLabelAfterFilter();
        }
    }

    function pushQueryToUrl() {
        try {
            var u = new URL(window.location.href);
            var keys = selectedGroupKeys();
            var totalCb = groupCbs().length;
            if (keys.length > 0 && keys.length < totalCb) {
                u.searchParams.set('grn_group', keys.map(encodeURIComponent).join(','));
            } else {
                u.searchParams.delete('grn_group');
            }
            var s = norm(skuInput.value);
            if (s) u.searchParams.set('grn_sku', skuInput.value.trim()); else u.searchParams.delete('grn_sku');
            u.searchParams.delete('grn_search');
            u.searchParams.delete('q');
            window.history.replaceState({}, '', u.pathname + u.search + u.hash);
        } catch (e) { /* ignore */ }
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        setGroupPanelOpen(false);
        runFilter();
        pushQueryToUrl();
    });

    skuInput.addEventListener('input', runFilter);
    skuInput.addEventListener('search', runFilter);

    groupCbs().forEach(function (cb) {
        cb.addEventListener('change', function () {
            runFilter();
            pushQueryToUrl();
        });
    });

    if (groupBtn && groupPanel) {
        groupBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = groupPanel.classList.contains('hidden');
            setGroupPanelOpen(open);
        });
        document.addEventListener('click', function () {
            setGroupPanelOpen(false);
        });
        groupPanel.addEventListener('click', function (e) {
            e.stopPropagation();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && groupPanel && !groupPanel.classList.contains('hidden')) {
                setGroupPanelOpen(false);
            }
        });
    }

    if (groupSelectAll) {
        groupSelectAll.addEventListener('click', function (e) {
            e.preventDefault();
            groupCbs().forEach(function (cb) { cb.checked = true; });
            runFilter();
            pushQueryToUrl();
        });
    }
    if (groupSelectNone) {
        groupSelectNone.addEventListener('click', function (e) {
            e.preventDefault();
            groupCbs().forEach(function (cb) { cb.checked = false; });
            runFilter();
            pushQueryToUrl();
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            groupCbs().forEach(function (cb) { cb.checked = false; });
            skuInput.value = '';
            setGroupPanelOpen(false);
            runFilter();
            pushQueryToUrl();
            skuInput.focus();
        });
    }

    try {
        var params = new URLSearchParams(window.location.search);
        var pg = params.get('grn_group') || '';
        var ps = params.get('grn_sku') || params.get('grn_search') || params.get('q') || '';
        if (pg) {
            var wanted = pg.split(',').map(function (k) {
                try { return decodeURIComponent(k.trim()); } catch (err) { return k.trim(); }
            }).filter(Boolean);
            if (wanted.length > 0) {
                groupCbs().forEach(function (cb) {
                    cb.checked = wanted.indexOf(cb.value) !== -1;
                });
            }
        }
        if (ps) skuInput.value = ps;
        runFilter();
    } catch (e) { runFilter(); }
})();
</script>

<script>
(function () {
    var printBtn = document.getElementById('grnLabelPrintBtn');
    var templateSel = document.getElementById('grnLabelTemplate');
    var uncheckHidden = document.getElementById('grnLabelUncheckHidden');
    var selAll = document.getElementById('grnLabelSelectAll');
    var selVisible = document.getElementById('grnLabelSelectVisible');
    var selNone = document.getElementById('grnLabelSelectNone');
    var summaryEl = document.getElementById('grnLabelQueueSummary');
    if (!printBtn || !templateSel) return;

    function rowVisible(tr) {
        return tr.style.display !== 'none';
    }

    function eachRow(cb) {
        document.querySelectorAll('tr.grn-item-row').forEach(cb);
    }

    function setChecked(tr, on) {
        var cb = tr.querySelector('.grn-label-cb');
        if (cb && !cb.disabled) cb.checked = !!on;
    }

    function uncheckNonVisibleRows() {
        if (!uncheckHidden || !uncheckHidden.checked) return;
        eachRow(function (tr) {
            if (!rowVisible(tr)) setChecked(tr, false);
        });
    }

    window.grnLabelAfterFilter = function () {
        uncheckNonVisibleRows();
        updateSummary();
    };

    function updateSummary() {
        if (!summaryEl) return;
        var nQueued = 0;
        var nUnresolved = 0;
        eachRow(function (tr) {
            var cb = tr.querySelector('.grn-label-cb');
            if (!cb) return;
            if (cb.disabled) {
                nUnresolved++;
                return;
            }
            if (cb.checked) nQueued++;
        });
        summaryEl.textContent = nQueued + ' queued · ' + nUnresolved + ' unresolved';
    }

    eachRow(function (tr) {
        var cb = tr.querySelector('.grn-label-cb');
        var qty = tr.querySelector('.grn-label-qty');
        if (cb) cb.addEventListener('change', updateSummary);
        if (qty) qty.addEventListener('change', updateSummary);
    });
    if (uncheckHidden) uncheckHidden.addEventListener('change', function () {
        uncheckNonVisibleRows();
        updateSummary();
    });
    if (selAll) selAll.addEventListener('click', function () {
        eachRow(function (tr) { setChecked(tr, true); });
        updateSummary();
    });
    if (selVisible) selVisible.addEventListener('click', function () {
        eachRow(function (tr) { setChecked(tr, rowVisible(tr)); });
        updateSummary();
    });
    if (selNone) selNone.addEventListener('click', function () {
        eachRow(function (tr) { setChecked(tr, false); });
        updateSummary();
    });

    function openPrintHtmlInFrame(html) {
        var old = document.getElementById('grn-label-print-frame');
        if (old && old.parentNode) old.parentNode.removeChild(old);
        var frame = document.createElement('iframe');
        frame.id = 'grn-label-print-frame';
        frame.setAttribute('title', 'GRN label print');
        frame.style.cssText = 'position:fixed;left:-9999px;top:0;width:120mm;height:80mm;border:0;opacity:0;pointer-events:none;z-index:-1;';
        document.body.appendChild(frame);
        var d = frame.contentWindow.document;
        d.open();
        d.write(html || '');
        d.close();
    }

    function collectPayload() {
        var products = [];
        eachRow(function (tr) {
            var cb = tr.querySelector('.grn-label-cb');
            if (!cb || !cb.checked || cb.disabled) return;
            var pid = parseInt(tr.getAttribute('data-label-product-id') || '0', 10);
            if (pid <= 0) return;
            var qtyEl = tr.querySelector('.grn-label-qty');
            var qty = qtyEl ? parseInt(qtyEl.value, 10) : 1;
            if (isNaN(qty) || qty < 1) qty = 1;
            if (qty > 99) qty = 99;
            products.push({ id: pid, qty: qty });
        });
        return {
            template: templateSel.value,
            products: products
        };
    }

    printBtn.addEventListener('click', async function () {
        var payload = collectPayload();
        if (payload.products.length < 1) {
            alert('Select at least one line with a resolved catalog product (unchecked “No match” rows cannot print).');
            return;
        }
        var prev = printBtn.textContent;
        printBtn.disabled = true;
        printBtn.textContent = 'Preparing…';
        try {
            var res = await fetch('?page=products&action=bulk_label_print_generate', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            });
            var data = await res.json();
            if (!data || !data.success || !data.html) {
                alert((data && data.message) ? data.message : 'Could not generate labels.');
                return;
            }
            openPrintHtmlInFrame(data.html);
        } catch (e) {
            alert('Could not generate labels. Please try again.');
        } finally {
            printBtn.disabled = false;
            printBtn.textContent = prev;
        }
    });

    uncheckNonVisibleRows();
    updateSummary();
})();
</script>
