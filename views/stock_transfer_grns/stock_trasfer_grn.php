<?php
$mode = $data['mode'] ?? 'create';
$grn = $data['grn'] ?? [];
$formAction = $mode === 'edit' ? '?page=stock_transfer_grns&action=update' : '#';
$pageTitle = $mode === 'edit' ? 'Edit' : 'Create';
$grnRemarksValue = $mode === 'edit' ? htmlspecialchars($grn['remarks'] ?? '') : '';
$transfer = $transfer ?? ($data['transfer'] ?? []);
$transferId = (int)($transfer['id'] ?? 0);
$dispatchDateMin = '';
if (!empty($transfer['dispatch_date'])) {
    $dTs = strtotime((string)$transfer['dispatch_date']);
    if ($dTs !== false) {
        $dispatchDateMin = date('Y-m-d', $dTs);
    }
}
if ($mode === 'edit') {
    $receivedDateValue = $grn['received_date'] ?? date('Y-m-d');
} else {
    $today = date('Y-m-d');
    $receivedDateValue = ($dispatchDateMin !== '' && $today < $dispatchDateMin) ? $dispatchDateMin : $today;
}
$transferItems = $transfer['items'] ?? [];
$totalLineCount = (int)($total_line_count ?? count($transferItems));
$defaultReceivedBy = (int)($default_received_by ?? ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0)));
$defaultWarehouseId = (int)($default_warehouse_id ?? 0);
$users = $users ?? [];
$warehouses = $warehouses ?? [];
?>
<div class="min-h-full bg-gradient-to-b from-slate-50 via-white to-amber-50/25">
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">

    <?php if ($mode === 'edit'): ?>
    <form method="post" action="<?php echo htmlspecialchars($formAction); ?>">
        <input type="hidden" name="grn_id" value="<?php echo (int)($grn['id'] ?? 0); ?>">
        <input type="hidden" name="transfer_id" value="<?php echo (int)($grn['transfer_id'] ?? $transferId); ?>">
    <?php endif; ?>

    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/80 via-white to-slate-50/50 shadow-sm ring-1 ring-amber-900/[0.04] mb-8">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-12 h-48 w-48 rounded-full bg-emerald-200/10 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-8 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6">
            <div class="min-w-0 max-w-2xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-clipboard-check text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Stock transfer · Goods receipt</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    <?php echo htmlspecialchars($pageTitle); ?> <span class="text-amber-800">GRN</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed">
                    Record quantities received at the destination warehouse. You can receive in multiple GRNs until each line is fully received.
                </p>
            </div>
            <div class="flex shrink-0 flex-col sm:flex-row gap-2 flex-wrap lg:pt-1">
                <a href="?page=products&action=stock_transfer"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200/90 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 transition whitespace-nowrap">
                    <i class="fas fa-history text-xs text-amber-700" aria-hidden="true"></i>
                    Transfer history
                </a>
                <?php if ($transferId > 0): ?>
                <a href="?page=stock_transfer_grns&action=list&transfer_id=<?php echo $transferId; ?>"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-emerald-200 bg-white text-emerald-800 text-sm font-semibold shadow-sm hover:bg-emerald-50/80 transition whitespace-nowrap">
                    <i class="fas fa-clipboard-list text-xs" aria-hidden="true"></i>
                    GRNs for this transfer
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Transfer summary -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden mb-8">
        <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-slate-50/90 flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm border border-sky-100">
                <i class="fas fa-route text-sm" aria-hidden="true"></i>
            </span>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Transfer summary</h2>
                <p class="text-xs text-gray-500 mt-0.5">Order reference and route for this receipt.</p>
            </div>
        </div>
        <div class="p-5 sm:p-6 grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-6 text-sm text-left">
            <dl class="space-y-4">
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-medium">Transfer order</dt>
                    <dd class="mt-1 font-mono font-semibold text-gray-900 break-words"><?php echo htmlspecialchars($transfer['transfer_order_no'] ?? '—'); ?></dd>
                </div>
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-medium">From</dt>
                    <dd class="mt-1 text-gray-900 break-words"><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></dd>
                </div>
            </dl>
            <dl class="space-y-4">
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-medium">Dispatch date</dt>
                    <dd class="mt-1 text-gray-900"><?php echo !empty($transfer['dispatch_date']) ? htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))) : '—'; ?></dd>
                </div>
                <div class="py-2 border-b border-gray-100">
                    <dt class="text-gray-500 font-medium">To</dt>
                    <dd class="mt-1 text-gray-900 break-words"><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <?php if ($mode === 'create'): ?>
    <div class="space-y-6 mb-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="text-lg font-semibold text-gray-900">Line items to receive</h2>
            <span id="grnLineCountBadge" class="text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-3 py-1"><?php echo number_format($totalLineCount); ?> line<?php echo $totalLineCount === 1 ? '' : 's'; ?></span>
        </div>

        <div id="grnLinesLoading" class="rounded-2xl border border-gray-200 bg-white shadow-sm p-8 flex flex-col items-center justify-center gap-3 text-sm text-gray-600" role="status" aria-live="polite">
            <i class="fas fa-spinner fa-spin text-amber-700 text-xl" aria-hidden="true"></i>
            <span>Loading first batch of line items…</span>
        </div>

        <div id="grnLinesContainer" class="space-y-6 hidden"></div>

        <div id="grnLoadMoreWrap" class="hidden">
            <button type="button" id="grnLoadMoreBtn"
                class="w-full rounded-2xl border border-amber-200/80 bg-gradient-to-r from-amber-50/80 via-white to-amber-50/50 px-5 py-4 text-center shadow-sm hover:border-amber-300 hover:bg-amber-50/90 transition disabled:opacity-60 disabled:cursor-not-allowed">
                <span id="grnLoadMoreLabel" class="block text-sm font-semibold text-amber-950">Load more</span>
                <span id="grnLoadMoreSub" class="block text-xs text-gray-500 mt-1">Showing 0 of <?php echo number_format($totalLineCount); ?> lines loaded</span>
            </button>
        </div>

        <p id="grnLinesAllLoaded" class="hidden text-center text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3" role="status"></p>
    </div>

    <?php elseif ($mode === 'edit'): ?>
    <div class="space-y-6 mb-8">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="text-lg font-semibold text-gray-900">Line items to receive</h2>
            <span class="text-xs font-medium text-gray-500 bg-gray-100 rounded-full px-3 py-1"><?php echo count($transferItems); ?> line<?php echo count($transferItems) === 1 ? '' : 's'; ?></span>
        </div>

        <?php foreach ($transferItems as $grnLineIdx => $item): ?>
            <?php
                $label = trim($item['sku'] ?? '');
                if ($label === '') {
                    $label = trim($item['item_code'] ?? '');
                }
                $product = $item['product'] ?? null;
                $imageUrl = $product['image'] ?? '';
                $title = $product['title'] ?? $label;
                $quantity = (int)$item['transfer_qty'];
                $already = (int)($item['already_received_on_transfer'] ?? $item['previously_received_qty'] ?? 0);
                $remaining = isset($item['remaining_to_receive']) ? (int)$item['remaining_to_receive'] : max(0, $quantity - $already);
                $weight = $product['product_weight'] ?? '';
                $weightUnit = $product['product_weight_unit'] ?? '';
                $material = $product['material'] ?? '';
            ?>
        <div class="group rounded-2xl border border-gray-200/90 bg-white shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden hover:border-amber-200/60 transition-colors">
            <div class="p-4 sm:p-5">
                <div class="flex flex-col sm:flex-row gap-5">
                    <button type="button"
                        class="st-grn-thumb w-full sm:w-28 h-36 shrink-0 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"
                        data-img="<?php echo htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8'); ?>"
                        data-title="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>"
                        <?php echo $imageUrl === '' ? 'disabled' : ''; ?>>
                        <?php if (!empty($imageUrl)): ?>
                            <img src="<?php echo htmlspecialchars($imageUrl); ?>" alt="" class="max-w-full max-h-full object-contain">
                        <?php else: ?>
                            <span class="text-gray-400 text-xs font-medium">No image</span>
                        <?php endif; ?>
                    </button>

                    <div class="flex-1 min-w-0 space-y-3 sm:space-y-3.5">
                        <!-- Product title + primary SKU (compact, mockup-style) -->
                        <div class="min-w-0">
                            <h3 class="text-[15px] sm:text-base font-semibold text-slate-900 leading-snug pr-1"><?php echo htmlspecialchars($title); ?></h3>
                            <p class="mt-1.5 text-xs text-gray-500">
                                <span class="text-gray-400 font-medium">SKU</span>
                                <?php echo htmlspecialchars($item['sku'] ?? '—'); ?>
                                <?php if (trim((string)($item['item_code'] ?? '')) !== ''): ?>
                                    <span class="text-gray-300 mx-1.5">·</span>
                                    <span class="text-gray-400 font-medium">Item code</span>
                                    <?php echo htmlspecialchars($item['item_code']); ?>
                                <?php endif; ?>
                            </p>
                            <?php if ($material !== '' || $weight !== ''): ?>
                                <p class="mt-1 text-[11px] text-gray-400">
                                    <?php if ($material !== ''): ?><?php echo htmlspecialchars($material); ?><?php endif; ?>
                                    <?php if ($material !== '' && $weight !== ''): ?> · <?php endif; ?>
                                    <?php if ($weight !== ''): ?><?php echo htmlspecialchars((string)$weight . ' ' . (string)$weightUnit); ?><?php endif; ?>
                                </p>
                            <?php endif; ?>
                        </div>

                        <!-- One row: stats + qty received + quality OK — equal-height chips -->
                        <div class="flex flex-wrap items-stretch gap-2 sm:gap-2.5">
                            <div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">
                                <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Shipped</p>
                                <div class="flex-1 flex items-end min-h-0 pt-1">
                                    <p class="text-lg sm:text-xl font-bold text-gray-900 tabular-nums leading-none w-full"><?php echo number_format($quantity); ?></p>
                                </div>
                            </div>
                            <div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">
                                <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Prior GRN</p>
                                <div class="flex-1 flex items-end min-h-0 pt-1">
                                    <p class="text-lg sm:text-xl font-bold text-gray-900 tabular-nums leading-none w-full"><?php echo number_format($already); ?></p>
                                </div>
                            </div>
                            <div class="st-grn-metric rounded-md bg-amber-50 border border-amber-200/90 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 ring-1 ring-amber-900/5 flex flex-col box-border">
                                <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-amber-900/80 leading-tight shrink-0">To receive</p>
                                <div class="flex-1 flex items-end min-h-0 pt-1">
                                    <p class="text-lg sm:text-xl font-bold text-amber-950 tabular-nums leading-none w-full"><?php echo number_format($remaining); ?></p>
                                </div>
                            </div>

                            <div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">
                                <label for="st-grn-qty-<?php echo (int)$grnLineIdx; ?>" class="block text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight cursor-pointer shrink-0">
                                    Qty received<span class="text-red-500 ml-px">*</span>
                                </label>
                                <div class="flex-1 flex items-end min-h-0 pt-1">
                                    <input id="st-grn-qty-<?php echo (int)$grnLineIdx; ?>" name="qty_received[]" type="number" min="0" max="<?php echo (int)$remaining; ?>"
                                        placeholder="0"
                                        value="<?php echo htmlspecialchars($mode === 'edit' ? (string)(int)($item['qty_received'] ?? 0) : ($remaining > 0 ? (string)$remaining : '0')); ?>"
                                        class="st-grn-qty w-full px-1.5 py-1.5 border border-gray-200/90 rounded bg-white text-center text-base sm:text-lg font-bold text-gray-900 tabular-nums shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                                </div>
                            </div>

                            <div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">
                                <p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Quality OK</p>
                                <div class="flex-1 flex items-end min-h-0 pt-1 w-full">
                                    <label class="flex min-h-[2.125rem] sm:min-h-10 w-full items-center justify-center gap-2 cursor-pointer select-none rounded bg-white/90 border border-gray-200/80 px-1.5 py-1 shadow-sm">
                                        <input type="checkbox" name="qty_acceptable[]" value="1" class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 shrink-0"
                                            <?php echo ($mode === 'edit' ? ((int)($item['qty_acceptable'] ?? 0) > 0 ? 'checked' : '') : 'checked'); ?>>
                                        <span class="text-xs font-bold text-gray-800">OK</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-500 mb-1">Line remarks <span class="text-gray-400 font-normal">(optional)</span></label>
                            <textarea name="item_remarks[]" rows="1" placeholder="Damage notes, batch, etc."
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder:text-gray-400 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-y min-h-[2.75rem] max-h-32"><?php echo htmlspecialchars(($mode === 'edit' ? ($item['remarks'] ?? '') : '')); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <input type="hidden" name="grn_row_id[]" value="<?php echo (int)($item['id'] ?? 0); ?>">
            <input type="hidden" name="item_id[]" value="<?php echo (int)($item['item_id'] ?? $item['id'] ?? 0); ?>">
            <input type="hidden" name="sku[]" value="<?php echo htmlspecialchars($item['sku'] ?? ''); ?>">
            <input type="hidden" name="item_code[]" value="<?php echo htmlspecialchars($item['item_code'] ?? ''); ?>">
            <input type="hidden" name="transfer_qty[]" value="<?php echo (int)($item['transfer_qty'] ?? 0); ?>">
        </div>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6 mb-8">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">GRN details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm mb-4">
            <div><span class="text-gray-500">GRN ID</span> <span class="font-mono font-semibold"><?php echo (int)($grn['id'] ?? 0); ?></span></div>
            <div><span class="text-gray-500">Transfer order</span> <?php echo htmlspecialchars($transfer['transfer_order_no'] ?? ''); ?></div>
            <div><span class="text-gray-500">SKU</span> <?php echo htmlspecialchars($grn['sku'] ?? $transferItems[0]['sku'] ?? ''); ?></div>
            <div><span class="text-gray-500">Item code</span> <?php echo htmlspecialchars($grn['item_code'] ?? $transferItems[0]['item_code'] ?? ''); ?></div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><strong>Qty transferred</strong>: <?php echo (int)($transferItems[0]['transfer_qty'] ?? 0); ?></div>
            <div><strong>Qty received</strong>: <input type="number" name="qty_received" min="0" value="<?php echo (int)($grn['qty_received'] ?? 0); ?>" class="w-24 border rounded-lg px-2 py-1"></div>
            <div><strong>Qty acceptable</strong>: <input type="number" name="qty_acceptable" min="0" value="<?php echo (int)($grn['qty_acceptable'] ?? 0); ?>" class="w-24 border rounded-lg px-2 py-1"></div>
            <div><strong>Received date</strong>: <input type="date" name="received_date" value="<?php echo htmlspecialchars($receivedDateValue); ?>"<?php echo $dispatchDateMin !== '' ? ' min="' . htmlspecialchars($dispatchDateMin) . '"' : ''; ?> class="border rounded-lg px-2 py-1"></div>
        </div>
        <div class="mt-4">
            <strong>Remarks</strong>
            <textarea name="remarks" class="w-full mt-2 p-3 border border-gray-200 rounded-lg text-sm" rows="3"><?php echo htmlspecialchars($grnRemarksValue); ?></textarea>
        </div>
    </div>
    <?php endif; ?>

    <!-- Receipt + submit -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden mb-8">
        <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-slate-50/90 flex items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-gray-700 shadow-sm border border-gray-200">
                <i class="fas fa-warehouse text-sm" aria-hidden="true"></i>
            </span>
            <div>
                <h2 class="text-base font-semibold text-gray-900">Receipt details</h2>
                <p class="text-xs text-gray-500 mt-0.5">Where and when the shipment was received.</p>
            </div>
        </div>
        <div class="p-5 sm:p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div class="flex flex-col">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Received date <span class="text-red-500">*</span></label>
                    <input type="date" name="received_date" value="<?php echo htmlspecialchars($receivedDateValue); ?>"<?php echo $dispatchDateMin !== '' ? ' min="' . htmlspecialchars($dispatchDateMin) . '"' : ''; ?>
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                </div>
                <div class="flex flex-col">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Received by <span class="text-red-500">*</span></label>
                    <select name="received_by" id="receivedBy" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">
                        <option value="">Select user…</option>
                        <?php foreach ($users as $id => $name): ?>
                            <option value="<?php echo (int)$id; ?>"<?php echo ((int)$id === $defaultReceivedBy) ? ' selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col md:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Receiving warehouse <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"<?php echo (count($warehouses) === 1) ? ' title="Transfer destination warehouse"' : ''; ?>>
                        <?php if (count($warehouses) !== 1): ?>
                        <option value="">Select warehouse…</option>
                        <?php endif; ?>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"<?php echo ((int)$warehouse['id'] === $defaultWarehouseId) ? ' selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <div class="flex flex-col">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">Supporting files <span class="text-gray-400 font-normal">(optional)</span></label>
                    <input type="file" id="grnSupportingFiles" name="grn_file[]" multiple accept=".pdf,.png,.jpg,.jpeg,application/pdf,image/png,image/jpeg"
                        class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-900 hover:file:bg-amber-100 border border-dashed border-gray-300 rounded-xl bg-gray-50/50 px-3 py-2">
                    <div id="grnFileList" class="mt-2 space-y-1.5 hidden" aria-live="polite"></div>
                    <p class="text-[11px] text-gray-400 mt-1.5">PNG, JPG, or PDF only — max 2 MB per file.</p>
                </div>
                <div class="flex flex-col">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">GRN remarks <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="grnRemarks" name="grn_remarks" rows="3" placeholder="Overall notes for this GRN…"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-y min-h-[5.5rem]"><?php echo $grnRemarksValue; ?></textarea>
                </div>
            </div>

            <p id="grnStatus" class="text-sm min-h-[1.25rem]" role="status"></p>
            <div id="grnSaveProgressWrap" class="hidden mt-3" aria-live="polite">
                <div class="flex items-center justify-between text-xs text-gray-600 mb-1.5">
                    <span id="grnSaveProgressLabel">Saving…</span>
                    <span id="grnSaveProgressPct" class="font-semibold tabular-nums">0%</span>
                </div>
                <div class="h-2.5 bg-gray-200 rounded-full overflow-hidden">
                    <div id="grnSaveProgressBar" class="h-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-300 ease-out" style="width:0%"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-2xl border border-amber-200/50 bg-gradient-to-r from-amber-50/40 via-white to-amber-50/30 px-5 py-5 sm:px-7 sm:py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6 shadow-sm ring-1 ring-amber-900/[0.04] mb-8">
        <input type="hidden" id="transferDispatchDateMin" value="<?php echo htmlspecialchars($dispatchDateMin); ?>">
        <?php if ($mode !== 'edit'): ?>
        <input type="hidden" name="transfer_id" value="<?php echo $transferId; ?>">
        <input type="hidden" name="transfer_order_no" value="<?php echo htmlspecialchars($transfer['transfer_order_no'] ?? ''); ?>">
        <?php endif; ?>
        <p class="text-sm text-gray-600 max-w-xl leading-relaxed order-2 sm:order-1">
            <span class="font-semibold text-gray-800">Ready?</span>
            <?php if ($mode === 'edit'): ?>
                Check line items and receipt details, then save changes.
            <?php else: ?>
                Check receipt details below. Use <strong>Save GRN</strong> for loaded lines only, or <strong>Receive all remaining</strong> to GRN every outstanding line from the server without loading them in the browser.
            <?php endif; ?>
        </p>
        <?php if ($mode === 'edit'): ?>
            <button type="submit" id="saveChanges" class="order-1 sm:order-2 inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap shrink-0">
                <i class="fas fa-save text-xs opacity-95" aria-hidden="true"></i>
                Save changes
            </button>
        <?php else: ?>
            <input type="hidden" id="grnTotalLineCount" value="<?php echo (int) $totalLineCount; ?>">
            <input type="hidden" id="grnTransferId" value="<?php echo (int) $transferId; ?>">
            <div class="order-1 sm:order-2 flex flex-col sm:flex-row gap-3 w-full sm:w-auto shrink-0">
                <button type="button" onclick="saveReceiveAllRemaining(event)" id="receiveAllRemaining"
                    class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-6 py-3.5 rounded-xl border-2 border-amber-600/80 bg-white text-amber-900 text-sm font-semibold hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none">
                    <i class="fas fa-truck-loading text-xs opacity-90" aria-hidden="true"></i>
                    Receive all remaining
                </button>
                <button type="button" onclick="saveStockTransferGrn(event)" id="saveChanges"
                    class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap disabled:opacity-60 disabled:cursor-not-allowed disabled:pointer-events-none">
                    <i class="fas fa-check text-xs opacity-95" aria-hidden="true"></i>
                    Save GRN
                </button>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($mode === 'edit'): ?>
    </form>
    <?php endif; ?>

</div>
</div>

<div id="imagePopup" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 sm:p-8" role="dialog" aria-modal="true" aria-label="Product image">
    <button type="button" onclick="closeImagePopup()" class="absolute top-3 right-3 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-2xl leading-none text-white hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Close">&times;</button>
    <img id="popupImage" alt="" class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-2xl">
</div>

<div id="grnReceiveAllConfirmModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="grnReceiveAllConfirmTitle">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-xl ring-1 ring-gray-900/5 overflow-hidden">
        <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-emerald-50/80">
            <h3 id="grnReceiveAllConfirmTitle" class="text-base font-semibold text-gray-900">Receive all remaining lines</h3>
            <p class="text-xs text-gray-600 mt-1">Full transfer receipt without loading every line in the browser.</p>
        </div>
        <div class="px-5 py-5 sm:px-6 space-y-4">
            <dl class="grid grid-cols-1 gap-3 text-sm">
                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3">
                    <dt class="text-gray-600">Total transfer lines</dt>
                    <dd id="grnReceiveAllTotalCount" class="font-bold text-gray-900 tabular-nums">0</dd>
                </div>
                <div class="flex items-center justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50/60 px-4 py-3">
                    <dt class="text-emerald-900/90 font-medium">Server save batches</dt>
                    <dd id="grnReceiveAllBatchCount" class="font-bold text-emerald-950 tabular-nums">0</dd>
                </div>
            </dl>
            <p id="grnReceiveAllNote" class="text-xs text-gray-500 leading-relaxed">
                Each line gets its full remaining quantity with quality marked OK. Supporting files and receipt details from the form above are included. Processing runs in batches of 50 on the server.
            </p>
            <p id="grnReceiveAllWarning" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 leading-relaxed"></p>
        </div>
        <div class="px-5 py-4 sm:px-6 border-t border-gray-100 bg-gray-50/80 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" id="grnReceiveAllConfirmCancel" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="button" id="grnReceiveAllConfirmProceed" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-b from-emerald-600 to-emerald-700 text-white text-sm font-semibold shadow-md hover:from-emerald-700 hover:to-emerald-800 transition">
                <i class="fas fa-truck-loading text-xs opacity-95" aria-hidden="true"></i>
                Receive all remaining
            </button>
        </div>
    </div>
</div>

<div id="grnSaveConfirmModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4 sm:p-6" role="dialog" aria-modal="true" aria-labelledby="grnSaveConfirmTitle">
    <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white shadow-xl ring-1 ring-gray-900/5 overflow-hidden">
        <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-amber-50/80">
            <h3 id="grnSaveConfirmTitle" class="text-base font-semibold text-gray-900">Confirm GRN save</h3>
            <p class="text-xs text-gray-600 mt-1">Review the counts below before saving.</p>
        </div>
        <div class="px-5 py-5 sm:px-6 space-y-4">
            <dl class="grid grid-cols-1 gap-3 text-sm">
                <div class="flex items-center justify-between gap-4 rounded-xl border border-gray-200 bg-slate-50/80 px-4 py-3">
                    <dt class="text-gray-600">Total GRN items</dt>
                    <dd id="grnConfirmTotalCount" class="font-bold text-gray-900 tabular-nums">0</dd>
                </div>
                <div class="flex items-center justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50/60 px-4 py-3">
                    <dt class="text-amber-900/90 font-medium">Ready to save</dt>
                    <dd id="grnConfirmReadyCount" class="font-bold text-amber-950 tabular-nums">0</dd>
                </div>
            </dl>
            <p id="grnConfirmNote" class="text-xs text-gray-500 leading-relaxed"></p>
            <p id="grnConfirmWarning" class="hidden text-xs text-amber-800 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 leading-relaxed"></p>
        </div>
        <div class="px-5 py-4 sm:px-6 border-t border-gray-100 bg-gray-50/80 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="button" id="grnSaveConfirmCancel" class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="button" id="grnSaveConfirmProceed" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-md hover:from-[#c57526] hover:to-[#b86a22] transition">
                <i class="fas fa-check text-xs opacity-95" aria-hidden="true"></i>
                Save GRN
            </button>
        </div>
    </div>
</div>

<script>
var GRN_SUPPORTING_FILE_MAX_BYTES = 2097152; // 2 MiB

function grnIsAllowedSupportingFile(file) {
    var n = file.name || '';
    var i = n.lastIndexOf('.');
    var ext = i >= 0 ? n.slice(i).toLowerCase() : '';
    return ext === '.pdf' || ext === '.png' || ext === '.jpg' || ext === '.jpeg';
}

function grnSupportingFileWithinSizeLimit(file) {
    return file && file.size <= GRN_SUPPORTING_FILE_MAX_BYTES;
}

(function () {
    function openImagePopup(src, alt) {
        var popup = document.getElementById('imagePopup');
        var img = document.getElementById('popupImage');
        if (!popup || !img || !src) return;
        img.src = src;
        img.alt = alt || '';
        popup.classList.remove('hidden');
        popup.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    window.closeImagePopup = function () {
        var popup = document.getElementById('imagePopup');
        var img = document.getElementById('popupImage');
        if (!popup || !img) return;
        popup.classList.add('hidden');
        popup.classList.remove('flex');
        img.removeAttribute('src');
        img.alt = '';
        document.body.style.overflow = '';
    };

    document.getElementById('imagePopup').addEventListener('click', function (e) {
        if (e.target === this) window.closeImagePopup();
    });
    document.addEventListener('keydown', function (e) {
        var popup = document.getElementById('imagePopup');
        if (e.key === 'Escape' && popup && !popup.classList.contains('hidden')) {
            window.closeImagePopup();
        }
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.st-grn-thumb');
        if (!btn || btn.disabled) {
            return;
        }
        var src = btn.getAttribute('data-img') || '';
        var title = btn.getAttribute('data-title') || '';
        if (src) {
            openImagePopup(src, title);
        }
    });

    var grnFileInput = document.getElementById('grnSupportingFiles');
    var grnFileListEl = document.getElementById('grnFileList');
    if (grnFileInput && grnFileListEl) {
        var grnStagedFiles = [];

        function grnFileKey(file) {
            return file.name + '|' + file.size + '|' + file.lastModified;
        }

        function grnApplyStagedToInput() {
            var dt = new DataTransfer();
            grnStagedFiles.forEach(function (f) {
                dt.items.add(f);
            });
            grnFileInput.files = dt.files;
        }

        function grnRenderFileList() {
            grnFileListEl.innerHTML = '';
            if (grnStagedFiles.length === 0) {
                grnFileListEl.classList.add('hidden');
                return;
            }
            grnFileListEl.classList.remove('hidden');
            grnStagedFiles.forEach(function (file, idx) {
                var row = document.createElement('div');
                row.className = 'flex items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 shadow-sm';
                var name = document.createElement('span');
                name.className = 'min-w-0 truncate font-medium text-gray-800';
                name.textContent = file.name;
                name.title = file.name;
                var meta = document.createElement('span');
                meta.className = 'shrink-0 text-gray-400 tabular-nums';
                if (file.size < 1024) {
                    meta.textContent = file.size + ' B';
                } else if (file.size < 1048576) {
                    meta.textContent = (file.size / 1024).toFixed(1) + ' KB';
                } else {
                    meta.textContent = (file.size / 1048576).toFixed(1) + ' MB';
                }
                var left = document.createElement('div');
                left.className = 'flex min-w-0 flex-1 items-center gap-2';
                left.appendChild(name);
                left.appendChild(meta);
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'shrink-0 inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-semibold text-red-700 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400/50';
                removeBtn.setAttribute('aria-label', 'Remove ' + file.name);
                removeBtn.innerHTML = '<i class="fas fa-times text-[10px]" aria-hidden="true"></i> Remove';
                removeBtn.addEventListener('click', function () {
                    grnStagedFiles.splice(idx, 1);
                    grnApplyStagedToInput();
                    grnRenderFileList();
                });
                row.appendChild(left);
                row.appendChild(removeBtn);
                grnFileListEl.appendChild(row);
            });
        }

        grnFileInput.addEventListener('change', function () {
            var seen = {};
            grnStagedFiles.forEach(function (f) {
                seen[grnFileKey(f)] = true;
            });
            var rejectedType = [];
            var rejectedSize = [];
            Array.prototype.forEach.call(grnFileInput.files || [], function (f) {
                if (!grnIsAllowedSupportingFile(f)) {
                    rejectedType.push(f.name);
                    return;
                }
                if (!grnSupportingFileWithinSizeLimit(f)) {
                    rejectedSize.push(f.name);
                    return;
                }
                var k = grnFileKey(f);
                if (!seen[k]) {
                    seen[k] = true;
                    grnStagedFiles.push(f);
                }
            });
            grnFileInput.value = '';
            grnApplyStagedToInput();
            grnRenderFileList();
            if (rejectedType.length > 0) {
                alert('Only PNG, JPG, and PDF files are allowed. Skipped:\n' + rejectedType.join('\n'));
            }
            if (rejectedSize.length > 0) {
                alert('Each file must be 2 MB or smaller. Skipped:\n' + rejectedSize.join('\n'));
            }
        });
    }
})();

var GRN_SAVE_CHUNK_SIZE = 50;
var GRN_LOAD_PAGE_SIZE = 50;
var grnSaveInProgress = false;

function grnSetSaveButtonsDisabled(disabled) {
    grnSaveInProgress = !!disabled;
    ['saveChanges', 'receiveAllRemaining'].forEach(function (id) {
        var btn = document.getElementById(id);
        if (!btn) {
            return;
        }
        btn.disabled = disabled;
        btn.setAttribute('aria-busy', disabled ? 'true' : 'false');
    });
}

var grnLinesState = {
    transferId: 0,
    offset: 0,
    totalCount: 0,
    loadedCount: 0,
    pendingCount: 0,
    hasMore: false,
    loading: false
};

function grnEscapeHtml(value) {
    var div = document.createElement('div');
    div.textContent = value == null ? '' : String(value);
    return div.innerHTML;
}

function grnBuildLineCardHtml(item, lineIdx) {
    var imageUrl = item.image || '';
    var title = item.title || item.sku || item.item_code || '—';
    var remaining = parseInt(item.remaining, 10) || 0;
    var defaultQty = remaining > 0 ? String(remaining) : '0';
    var sku = item.sku || '—';
    var itemCode = item.item_code || '';
    var material = item.material || '';
    var weight = item.product_weight || '';
    var weightUnit = item.product_weight_unit || '';
    var quantity = parseInt(item.transfer_qty, 10) || 0;
    var already = parseInt(item.already_received, 10) || 0;
    var metaLine = '';
    if (material || weight) {
        metaLine = '<p class="mt-1 text-[11px] text-gray-400">' +
            grnEscapeHtml(material) +
            (material && weight ? ' · ' : '') +
            (weight ? grnEscapeHtml(String(weight) + (weightUnit ? ' ' + weightUnit : '')) : '') +
            '</p>';
    }
    var itemCodeHtml = itemCode
        ? '<span class="text-gray-300 mx-1.5">·</span><span class="text-gray-400 font-medium">Item code</span> ' + grnEscapeHtml(itemCode)
        : '';
    var thumbInner = imageUrl
        ? '<img src="' + grnEscapeHtml(imageUrl) + '" alt="" loading="lazy" class="max-w-full max-h-full object-contain">'
        : '<span class="text-gray-400 text-xs font-medium">No image</span>';
    var thumbDisabled = imageUrl ? '' : ' disabled';

    return '' +
        '<div class="group rounded-2xl border border-gray-200/90 bg-white shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden hover:border-amber-200/60 transition-colors grn-line-card">' +
            '<div class="p-4 sm:p-5">' +
                '<div class="flex flex-col sm:flex-row gap-5">' +
                    '<button type="button" class="st-grn-thumb w-full sm:w-28 h-36 shrink-0 rounded-xl overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center cursor-pointer focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500"' +
                        ' data-img="' + grnEscapeHtml(imageUrl) + '" data-title="' + grnEscapeHtml(title) + '"' + thumbDisabled + '>' +
                        thumbInner +
                    '</button>' +
                    '<div class="flex-1 min-w-0 space-y-3 sm:space-y-3.5">' +
                        '<div class="min-w-0">' +
                            '<h3 class="text-[15px] sm:text-base font-semibold text-slate-900 leading-snug pr-1">' + grnEscapeHtml(title) + '</h3>' +
                            '<p class="mt-1.5 text-xs text-gray-500">' +
                                '<span class="text-gray-400 font-medium">SKU</span> ' + grnEscapeHtml(sku) + itemCodeHtml +
                            '</p>' + metaLine +
                        '</div>' +
                        '<div class="flex flex-wrap items-stretch gap-2 sm:gap-2.5">' +
                            '<div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">' +
                                '<p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Shipped</p>' +
                                '<div class="flex-1 flex items-end min-h-0 pt-1"><p class="text-lg sm:text-xl font-bold text-gray-900 tabular-nums leading-none w-full">' + grnFormatCount(quantity) + '</p></div>' +
                            '</div>' +
                            '<div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">' +
                                '<p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Prior GRN</p>' +
                                '<div class="flex-1 flex items-end min-h-0 pt-1"><p class="text-lg sm:text-xl font-bold text-gray-900 tabular-nums leading-none w-full">' + grnFormatCount(already) + '</p></div>' +
                            '</div>' +
                            '<div class="st-grn-metric rounded-md bg-amber-50 border border-amber-200/90 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 ring-1 ring-amber-900/5 flex flex-col box-border">' +
                                '<p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-amber-900/80 leading-tight shrink-0">To receive</p>' +
                                '<div class="flex-1 flex items-end min-h-0 pt-1"><p class="text-lg sm:text-xl font-bold text-amber-950 tabular-nums leading-none w-full">' + grnFormatCount(remaining) + '</p></div>' +
                            '</div>' +
                            '<div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">' +
                                '<label for="st-grn-qty-' + lineIdx + '" class="block text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight cursor-pointer shrink-0">Qty received<span class="text-red-500 ml-px">*</span></label>' +
                                '<div class="flex-1 flex items-end min-h-0 pt-1">' +
                                    '<input id="st-grn-qty-' + lineIdx + '" name="qty_received[]" type="number" min="0" max="' + remaining + '" placeholder="0" value="' + grnEscapeHtml(defaultQty) + '" class="st-grn-qty w-full px-1.5 py-1.5 border border-gray-200/90 rounded bg-white text-center text-base sm:text-lg font-bold text-gray-900 tabular-nums shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500">' +
                                '</div>' +
                            '</div>' +
                            '<div class="st-grn-metric rounded-md bg-slate-100/90 border border-slate-200/80 p-2 w-28 h-28 sm:w-32 sm:h-32 shrink-0 flex flex-col box-border">' +
                                '<p class="text-[8px] sm:text-[9px] font-bold uppercase tracking-wide text-sky-800/90 leading-tight shrink-0">Quality OK</p>' +
                                '<div class="flex-1 flex items-end min-h-0 pt-1 w-full">' +
                                    '<label class="flex min-h-[2.125rem] sm:min-h-10 w-full items-center justify-center gap-2 cursor-pointer select-none rounded bg-white/90 border border-gray-200/80 px-1.5 py-1 shadow-sm">' +
                                        '<input type="checkbox" name="qty_acceptable[]" value="1" checked class="h-4 w-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 shrink-0">' +
                                        '<span class="text-xs font-bold text-gray-800">OK</span>' +
                                    '</label>' +
                                '</div>' +
                            '</div>' +
                        '</div>' +
                        '<div>' +
                            '<label class="block text-xs font-semibold text-gray-500 mb-1">Line remarks <span class="text-gray-400 font-normal">(optional)</span></label>' +
                            '<textarea name="item_remarks[]" rows="1" placeholder="Damage notes, batch, etc." class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-800 placeholder:text-gray-400 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-y min-h-[2.75rem] max-h-32"></textarea>' +
                        '</div>' +
                    '</div>' +
                '</div>' +
            '</div>' +
            '<input type="hidden" name="grn_row_id[]" value="' + (parseInt(item.id, 10) || 0) + '">' +
            '<input type="hidden" name="item_id[]" value="' + (parseInt(item.id, 10) || 0) + '">' +
            '<input type="hidden" name="sku[]" value="' + grnEscapeHtml(item.sku || '') + '">' +
            '<input type="hidden" name="item_code[]" value="' + grnEscapeHtml(item.item_code || '') + '">' +
            '<input type="hidden" name="transfer_qty[]" value="' + quantity + '">' +
        '</div>';
}

function grnUpdateLoadMoreBar() {
    var wrap = document.getElementById('grnLoadMoreWrap');
    var btn = document.getElementById('grnLoadMoreBtn');
    var label = document.getElementById('grnLoadMoreLabel');
    var sub = document.getElementById('grnLoadMoreSub');
    var allLoaded = document.getElementById('grnLinesAllLoaded');
    if (!wrap || !btn || !label || !sub) {
        return;
    }

    sub.textContent = 'Showing ' + grnFormatCount(grnLinesState.loadedCount) + ' of ' + grnFormatCount(grnLinesState.totalCount) + ' lines loaded';

    if (grnLinesState.hasMore) {
        wrap.classList.remove('hidden');
        btn.disabled = grnLinesState.loading;
        label.textContent = grnLinesState.loading
            ? 'Loading next ' + GRN_LOAD_PAGE_SIZE + '…'
            : ('Load more — ' + grnFormatCount(grnLinesState.pendingCount) + ' items remaining');
        if (allLoaded) {
            allLoaded.classList.add('hidden');
        }
    } else {
        wrap.classList.add('hidden');
        if (allLoaded && grnLinesState.loadedCount > 0) {
            allLoaded.textContent = 'All ' + grnFormatCount(grnLinesState.loadedCount) + ' lines loaded.';
            allLoaded.classList.remove('hidden');
        }
    }
}

function grnAppendLineItems(items) {
    var container = document.getElementById('grnLinesContainer');
    if (!container || !items || !items.length) {
        return;
    }
    var html = '';
    items.forEach(function (item, idx) {
        html += grnBuildLineCardHtml(item, grnLinesState.offset + idx);
    });
    container.insertAdjacentHTML('beforeend', html);
    container.classList.remove('hidden');
}

function grnLoadMoreLines(isInitial) {
    if (grnLinesState.loading) {
        return Promise.resolve(false);
    }
    if (!isInitial && !grnLinesState.hasMore) {
        return Promise.resolve(false);
    }

    grnLinesState.loading = true;
    grnUpdateLoadMoreBar();

    var loadingEl = document.getElementById('grnLinesLoading');
    if (isInitial && loadingEl) {
        loadingEl.classList.remove('hidden');
    }

    var url = '?page=stock_transfer_grns&action=create_items&transfer_id=' + encodeURIComponent(grnLinesState.transferId) +
        '&offset=' + encodeURIComponent(grnLinesState.offset) +
        '&limit=' + encodeURIComponent(GRN_LOAD_PAGE_SIZE);

    return fetch(url, { credentials: 'include' })
        .then(function (r) { return grnParseJsonResponse(r); })
        .then(function (data) {
            if (!data || !data.success) {
                throw new Error((data && data.message) ? data.message : 'Could not load line items.');
            }

            grnAppendLineItems(data.items || []);
            grnLinesState.offset = parseInt(data.loaded_count, 10) || (grnLinesState.offset + (data.items || []).length);
            grnLinesState.totalCount = parseInt(data.total_count, 10) || grnLinesState.totalCount;
            grnLinesState.loadedCount = grnLinesState.offset;
            grnLinesState.pendingCount = parseInt(data.pending_count, 10) || Math.max(0, grnLinesState.totalCount - grnLinesState.loadedCount);
            grnLinesState.hasMore = !!data.has_more;

            var totalInput = document.getElementById('grnTotalLineCount');
            if (totalInput && grnLinesState.totalCount > 0) {
                totalInput.value = String(grnLinesState.totalCount);
            }

            if (loadingEl) {
                loadingEl.classList.add('hidden');
            }
            grnLinesState.loading = false;
            grnUpdateLoadMoreBar();
            return true;
        })
        .catch(function (err) {
            grnLinesState.loading = false;
            grnUpdateLoadMoreBar();
            if (loadingEl) {
                loadingEl.innerHTML = '<span class="text-red-600">' + grnEscapeHtml(err.message || 'Failed to load line items.') + '</span>' +
                    ' <button type="button" class="ml-2 text-amber-800 font-semibold underline" onclick="grnRetryInitialLoad()">Retry</button>';
            }
            console.error(err);
            return false;
        });
}

function grnRetryInitialLoad() {
    var loadingEl = document.getElementById('grnLinesLoading');
    if (loadingEl) {
        loadingEl.innerHTML = '<i class="fas fa-spinner fa-spin text-amber-700 text-xl" aria-hidden="true"></i><span>Loading first batch of line items…</span>';
        loadingEl.classList.remove('hidden');
    }
    grnLinesState.offset = 0;
    grnLinesState.loadedCount = 0;
    grnLinesState.hasMore = true;
    var container = document.getElementById('grnLinesContainer');
    if (container) {
        container.innerHTML = '';
    }
    grnLoadMoreLines(true);
}

function grnGetLoadedLineCount() {
    var container = document.getElementById('grnLinesContainer');
    if (!container) {
        return document.querySelectorAll('input[name="item_id[]"]').length;
    }
    return container.querySelectorAll('input[name="item_id[]"]').length;
}

function grnChunkArray(arr, size) {
    var chunks = [];
    for (var i = 0; i < arr.length; i += size) {
        chunks.push(arr.slice(i, i + size));
    }
    return chunks;
}

function grnUpdateSaveProgress(batchIndex, batchTotal, linesInBatch) {
    var wrap = document.getElementById('grnSaveProgressWrap');
    var label = document.getElementById('grnSaveProgressLabel');
    var pctEl = document.getElementById('grnSaveProgressPct');
    var bar = document.getElementById('grnSaveProgressBar');
    if (!wrap || !label || !pctEl || !bar) {
        return;
    }
    wrap.classList.remove('hidden');
    var completed = batchIndex + 1;
    var pct = batchTotal > 0 ? Math.min(100, Math.round((completed / batchTotal) * 100)) : 100;
    label.textContent = 'Saving batch ' + completed + ' of ' + batchTotal + (linesInBatch ? ' (' + linesInBatch + ' lines)' : '') + '…';
    pctEl.textContent = pct + '%';
    bar.style.width = pct + '%';
}

function grnResetSaveProgress() {
    var wrap = document.getElementById('grnSaveProgressWrap');
    var bar = document.getElementById('grnSaveProgressBar');
    if (wrap) {
        wrap.classList.add('hidden');
    }
    if (bar) {
        bar.style.width = '0%';
    }
}

function grnParseJsonResponse(response) {
    return response.text().then(function (text) {
        var trimmed = (text || '').trim();
        if (!trimmed) {
            throw new Error('Empty server response (HTTP ' + response.status + ').');
        }
        try {
            return JSON.parse(trimmed);
        } catch (e) {
            throw new Error('Invalid server response (HTTP ' + response.status + ').');
        }
    });
}

function grnBuildBatchFormData(baseFields, batchItems, batchIndex, batchTotal, fileInput, options) {
    options = options || {};
    var formData = new FormData();
    formData.append('transfer_id', baseFields.transferId);
    formData.append('received_by', baseFields.receivedBy);
    formData.append('warehouse_id', baseFields.warehouse);
    formData.append('received_date', baseFields.receivedDate);
    formData.append('grn_remarks', baseFields.remarks);
    formData.append('remarks', baseFields.remarks);
    formData.append('batch_index', batchIndex);
    formData.append('batch_total', batchTotal);
    formData.append('finalize_transfer', batchIndex === batchTotal - 1 ? '1' : '0');

    if (options.receiveAllRemaining) {
        formData.append('receive_all_remaining', '1');
        formData.append('batch_size', GRN_SAVE_CHUNK_SIZE);
    } else {
        formData.append('items', JSON.stringify(batchItems));
    }

    if (batchIndex === 0 && fileInput && fileInput.files.length > 0) {
        for (var f = 0; f < fileInput.files.length; f++) {
            formData.append('grn_file[]', fileInput.files[f]);
        }
    }

    return formData;
}

function grnFormatCount(n) {
    return Number(n || 0).toLocaleString('en-IN');
}

function grnGetTotalLineCount() {
    var el = document.getElementById('grnTotalLineCount');
    if (el) {
        return parseInt(el.value || '0', 10) || 0;
    }
    return document.querySelectorAll('input[name="item_id[]"]').length;
}

function grnCollectSaveItems() {
    var root = document.getElementById('grnLinesContainer') || document;
    var items = [];
    var itemIds = Array.from(root.querySelectorAll('input[name="item_id[]"]')).map(function (i) { return i.value; });
    var skus = Array.from(root.querySelectorAll('input[name="sku[]"]')).map(function (i) { return i.value; });
    var itemCodes = Array.from(root.querySelectorAll('input[name="item_code[]"]')).map(function (i) { return i.value; });
    var transferQtys = Array.from(root.querySelectorAll('input[name="transfer_qty[]"]')).map(function (i) { return i.value; });
    var receivedQtys = Array.from(root.querySelectorAll('input[name="qty_received[]"]')).map(function (i) { return i.value; });
    var acceptables = Array.from(root.querySelectorAll('input[name="qty_acceptable[]"]')).map(function (i) { return i.checked ? 1 : 0; });
    var itemRemarks = Array.from(root.querySelectorAll('textarea[name="item_remarks[]"]')).map(function (i) { return i.value; });

    for (var i = 0; i < itemIds.length; i++) {
        var rec = parseInt(receivedQtys[i] || 0, 10) || 0;
        if (rec <= 0) {
            continue;
        }
        var ok = acceptables[i] || 0;
        items.push({
            transfer_item_id: parseInt(itemIds[i], 10) || 0,
            sku: skus[i] || '',
            item_code: itemCodes[i] || '',
            transfer_qty: parseInt(transferQtys[i] || 0, 10) || 0,
            received_qty: rec,
            acceptable: ok,
            qty_acceptable: ok ? rec : 0,
            remarks: itemRemarks[i] || ''
        });
    }
    return items;
}

var grnSaveConfirmCallback = null;
var grnReceiveAllConfirmCallback = null;

function grnCloseReceiveAllConfirmModal() {
    var modal = document.getElementById('grnReceiveAllConfirmModal');
    if (!modal) {
        return;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
    grnReceiveAllConfirmCallback = null;
    var proceedBtn = document.getElementById('grnReceiveAllConfirmProceed');
    if (proceedBtn) {
        proceedBtn.disabled = false;
    }
}

function grnShowReceiveAllConfirmModal(totalCount, batchCount, onConfirm) {
    var modal = document.getElementById('grnReceiveAllConfirmModal');
    var totalEl = document.getElementById('grnReceiveAllTotalCount');
    var batchEl = document.getElementById('grnReceiveAllBatchCount');
    var warnEl = document.getElementById('grnReceiveAllWarning');
    if (!modal || !totalEl || !batchEl || !warnEl) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
        return;
    }

    totalEl.textContent = grnFormatCount(totalCount);
    batchEl.textContent = grnFormatCount(batchCount) + ' server batch' + (batchCount === 1 ? '' : 'es');

    if (totalCount <= 0) {
        warnEl.textContent = 'This transfer has no line items to receive.';
        warnEl.classList.remove('hidden');
    } else {
        warnEl.textContent = '';
        warnEl.classList.add('hidden');
    }

    grnReceiveAllConfirmCallback = onConfirm;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    var proceedBtn = document.getElementById('grnReceiveAllConfirmProceed');
    if (proceedBtn) {
        proceedBtn.disabled = totalCount <= 0;
        proceedBtn.focus();
    }
}

function grnCloseSaveConfirmModal() {
    var modal = document.getElementById('grnSaveConfirmModal');
    if (!modal) {
        return;
    }
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.style.overflow = '';
    grnSaveConfirmCallback = null;
}

function grnShowSaveConfirmModal(totalCount, readyCount, loadedCount, onConfirm) {
    var modal = document.getElementById('grnSaveConfirmModal');
    var totalEl = document.getElementById('grnConfirmTotalCount');
    var readyEl = document.getElementById('grnConfirmReadyCount');
    var noteEl = document.getElementById('grnConfirmNote');
    var warnEl = document.getElementById('grnConfirmWarning');
    if (!modal || !totalEl || !readyEl || !noteEl || !warnEl) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
        return;
    }

    totalEl.textContent = grnFormatCount(totalCount);
    readyEl.textContent = grnFormatCount(readyCount);
    noteEl.textContent = 'Only loaded lines with a received quantity greater than zero will be included in this GRN. Large saves are processed in batches automatically.';

    var warnings = [];
    var notLoaded = Math.max(0, totalCount - loadedCount);
    if (notLoaded > 0) {
        warnings.push(notLoaded + ' line' + (notLoaded === 1 ? '' : 's') + ' are not loaded yet and will not be saved.');
    }
    var skipped = Math.max(0, loadedCount - readyCount);
    if (skipped > 0) {
        warnings.push(skipped + ' loaded line' + (skipped === 1 ? '' : 's') + ' have no received quantity and will not be saved.');
    }

    if (warnings.length > 0) {
        warnEl.textContent = warnings.join(' ');
        warnEl.classList.remove('hidden');
    } else {
        warnEl.textContent = '';
        warnEl.classList.add('hidden');
    }

    grnSaveConfirmCallback = onConfirm;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
    var proceedBtn = document.getElementById('grnSaveConfirmProceed');
    if (proceedBtn) {
        proceedBtn.focus();
    }
}

function grnExecuteSave(items, transferId, receivedBy, warehouse, receivedDate, remarks) {
    grnRunBatchedSave({
        transferId: transferId,
        receivedBy: receivedBy,
        warehouse: warehouse,
        receivedDate: receivedDate,
        remarks: remarks,
        items: items,
        receiveAllRemaining: false
    });
}

function grnExecuteReceiveAllRemaining(transferId, receivedBy, warehouse, receivedDate, remarks) {
    var totalLines = grnGetTotalLineCount();
    var batchTotal = Math.max(1, Math.ceil(totalLines / GRN_SAVE_CHUNK_SIZE));

    grnRunBatchedSave({
        transferId: transferId,
        receivedBy: receivedBy,
        warehouse: warehouse,
        receivedDate: receivedDate,
        remarks: remarks,
        items: [],
        receiveAllRemaining: true,
        batchTotal: batchTotal,
        totalLines: totalLines
    });
}

function grnRunBatchedSave(config) {
    var fileInput = document.querySelector('input[name="grn_file[]"]');
    if (fileInput && fileInput.files.length > 0) {
        for (var f = 0; f < fileInput.files.length; f++) {
            if (!grnIsAllowedSupportingFile(fileInput.files[f])) {
                alert('Only PNG, JPG, and PDF files are allowed. Remove or replace: ' + fileInput.files[f].name);
                grnSetSaveButtonsDisabled(false);
                return;
            }
            if (!grnSupportingFileWithinSizeLimit(fileInput.files[f])) {
                alert('Each file must be 2 MB or smaller. Remove or replace: ' + fileInput.files[f].name);
                grnSetSaveButtonsDisabled(false);
                return;
            }
        }
    }

    grnSetSaveButtonsDisabled(true);

    var receiveAll = !!config.receiveAllRemaining;
    var items = config.items || [];
    var chunks = receiveAll ? [] : grnChunkArray(items, GRN_SAVE_CHUNK_SIZE);
    var totalBatches = receiveAll ? (config.batchTotal || 1) : chunks.length;
    var lineCountLabel = receiveAll ? (config.totalLines || totalBatches * GRN_SAVE_CHUNK_SIZE) : items.length;

    var baseFields = {
        transferId: parseInt(config.transferId, 10),
        receivedBy: parseInt(config.receivedBy, 10),
        warehouse: parseInt(config.warehouse, 10),
        receivedDate: config.receivedDate,
        remarks: config.remarks
    };

    var statusEl = document.getElementById('grnStatus');

    statusEl.textContent = totalBatches > 1
        ? (receiveAll
            ? ('Receiving all remaining lines (' + grnFormatCount(lineCountLabel) + ' lines) in ' + totalBatches + ' server batches…')
            : ('Saving ' + items.length + ' lines in ' + totalBatches + ' batches…'))
        : (receiveAll ? 'Receiving all remaining lines…' : 'Saving…');
    statusEl.classList.remove('text-red-600', 'text-green-600');
    statusEl.classList.add('text-gray-600');
    if (totalBatches > 1) {
        grnUpdateSaveProgress(-1, totalBatches, 0);
        document.getElementById('grnSaveProgressLabel').textContent = 'Preparing batch 1 of ' + totalBatches + '…';
    } else {
        grnResetSaveProgress();
    }

    var batchIndex = 0;
    var saveOptions = { receiveAllRemaining: receiveAll };

    function runNextBatch() {
        if (batchIndex >= totalBatches) {
            return Promise.resolve({ success: true });
        }

        var currentBatch = batchIndex;
        var batchItems = receiveAll ? [] : chunks[currentBatch];
        grnUpdateSaveProgress(currentBatch, totalBatches, receiveAll ? GRN_SAVE_CHUNK_SIZE : batchItems.length);

        var formData = grnBuildBatchFormData(baseFields, batchItems, currentBatch, totalBatches, fileInput, saveOptions);

        return fetch('?page=stock_transfer_grns&action=create_post', {
            method: 'POST',
            body: formData
        })
            .then(function (r) { return grnParseJsonResponse(r); })
            .then(function (res) {
                if (!res || !res.success) {
                    var failMsg = (res && res.message) ? res.message : 'Could not save GRN.';
                    if (totalBatches > 1) {
                        failMsg = 'Batch ' + (currentBatch + 1) + ' of ' + totalBatches + ' failed: ' + failMsg;
                    }
                    throw new Error(failMsg);
                }
                batchIndex++;
                if (batchIndex < totalBatches) {
                    return runNextBatch();
                }
                return res;
            });
    }

    runNextBatch()
        .then(function (res) {
            statusEl.classList.remove('text-red-600', 'text-gray-600');
            statusEl.classList.add('text-green-600');
            statusEl.textContent = (res && res.message) ? res.message : 'Saved successfully. Redirecting…';
            if (totalBatches > 1) {
                grnUpdateSaveProgress(totalBatches - 1, totalBatches, 0);
                document.getElementById('grnSaveProgressLabel').textContent = 'All batches saved.';
                document.getElementById('grnSaveProgressPct').textContent = '100%';
                document.getElementById('grnSaveProgressBar').style.width = '100%';
            }
            setTimeout(function () {
                window.location.href = '?page=stock_transfer_grns&action=list&transfer_id=' + encodeURIComponent(config.transferId);
            }, 900);
        })
        .catch(function (err) {
            statusEl.classList.remove('text-green-600', 'text-gray-600');
            statusEl.classList.add('text-red-600');
            var msg = err && err.message ? err.message : 'Request failed. Please try again.';
            if (totalBatches > 1 && batchIndex > 0) {
                msg += receiveAll
                    ? ' Earlier batches may already be saved — refresh and use Receive all remaining again for outstanding lines.'
                    : ' Earlier batches may already be saved — refresh this page and save only the remaining lines before retrying.';
            }
            statusEl.textContent = msg;
            console.error(err);
            grnSetSaveButtonsDisabled(false);
        });
}

function saveStockTransferGrn(event) {
    event.preventDefault();

    if (grnSaveInProgress) {
        return;
    }

    var receivedBy = document.querySelector('select[name="received_by"]').value;
    var warehouse = document.querySelector('select[name="warehouse_id"]').value;

    if (!warehouse) {
        alert('Please select a receiving warehouse.');
        return;
    }

    if (!receivedBy) {
        alert('Please select who received the shipment.');
        return;
    }

    var transferId = document.querySelector('input[name="transfer_id"]').value;
    var receivedDate = document.querySelector('input[name="received_date"]').value;
    var dispatchMinEl = document.getElementById('transferDispatchDateMin');
    var dispatchMin = dispatchMinEl ? String(dispatchMinEl.value || '').trim() : '';
    if (!receivedDate) {
        alert('Please enter the received date.');
        return;
    }
    if (dispatchMin && receivedDate < dispatchMin) {
        alert('Received date cannot be before the transfer dispatch date.');
        return;
    }

    var remarks = document.getElementById('grnRemarks') ? document.getElementById('grnRemarks').value : '';
    var items = grnCollectSaveItems();

    if (items.length === 0) {
        alert('Enter a received quantity for at least one line.');
        return;
    }

    var totalCount = grnGetTotalLineCount();
    var readyCount = items.length;
    var loadedCount = grnGetLoadedLineCount();

    grnShowSaveConfirmModal(totalCount, readyCount, loadedCount, function () {
        grnCloseSaveConfirmModal();
        grnExecuteSave(items, transferId, receivedBy, warehouse, receivedDate, remarks);
    });
}

function saveReceiveAllRemaining(event) {
    event.preventDefault();

    if (grnSaveInProgress) {
        return;
    }

    var receivedBy = document.querySelector('select[name="received_by"]').value;
    var warehouse = document.querySelector('select[name="warehouse_id"]').value;

    if (!warehouse) {
        alert('Please select a receiving warehouse.');
        return;
    }

    if (!receivedBy) {
        alert('Please select who received the shipment.');
        return;
    }

    var transferId = document.querySelector('input[name="transfer_id"]').value;
    var receivedDate = document.querySelector('input[name="received_date"]').value;
    var dispatchMinEl = document.getElementById('transferDispatchDateMin');
    var dispatchMin = dispatchMinEl ? String(dispatchMinEl.value || '').trim() : '';
    if (!receivedDate) {
        alert('Please enter the received date.');
        return;
    }
    if (dispatchMin && receivedDate < dispatchMin) {
        alert('Received date cannot be before the transfer dispatch date.');
        return;
    }

    var remarks = document.getElementById('grnRemarks') ? document.getElementById('grnRemarks').value : '';
    var totalCount = grnGetTotalLineCount();
    var batchCount = Math.max(1, Math.ceil(totalCount / GRN_SAVE_CHUNK_SIZE));

    grnShowReceiveAllConfirmModal(totalCount, batchCount, function () {
        grnCloseReceiveAllConfirmModal();
        grnExecuteReceiveAllRemaining(transferId, receivedBy, warehouse, receivedDate, remarks);
    });
}

(function () {
    var modal = document.getElementById('grnSaveConfirmModal');
    var cancelBtn = document.getElementById('grnSaveConfirmCancel');
    var proceedBtn = document.getElementById('grnSaveConfirmProceed');
    if (!modal) {
        return;
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', grnCloseSaveConfirmModal);
    }
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            if (typeof grnSaveConfirmCallback === 'function') {
                grnSaveConfirmCallback();
            }
        });
    }
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            grnCloseSaveConfirmModal();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            grnCloseSaveConfirmModal();
        }
    });
})();

(function () {
    var modal = document.getElementById('grnReceiveAllConfirmModal');
    var cancelBtn = document.getElementById('grnReceiveAllConfirmCancel');
    var proceedBtn = document.getElementById('grnReceiveAllConfirmProceed');
    if (!modal) {
        return;
    }

    if (cancelBtn) {
        cancelBtn.addEventListener('click', grnCloseReceiveAllConfirmModal);
    }
    if (proceedBtn) {
        proceedBtn.addEventListener('click', function () {
            if (typeof grnReceiveAllConfirmCallback === 'function') {
                grnReceiveAllConfirmCallback();
            }
        });
    }
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            grnCloseReceiveAllConfirmModal();
        }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            grnCloseReceiveAllConfirmModal();
        }
    });
})();

(function initGrnLazyLines() {
    var container = document.getElementById('grnLinesContainer');
    if (!container) {
        return;
    }

    var transferInput = document.getElementById('grnTransferId');
    grnLinesState.transferId = transferInput ? (parseInt(transferInput.value, 10) || 0) : 0;
    grnLinesState.totalCount = grnGetTotalLineCount();
    grnLinesState.offset = 0;
    grnLinesState.loadedCount = 0;
    grnLinesState.pendingCount = Math.max(0, grnLinesState.totalCount);
    grnLinesState.hasMore = grnLinesState.totalCount > 0;

    var loadBtn = document.getElementById('grnLoadMoreBtn');
    if (loadBtn) {
        loadBtn.addEventListener('click', function () {
            grnLoadMoreLines(false);
        });
    }

    if (grnLinesState.transferId > 0) {
        grnLoadMoreLines(true);
    } else if (document.getElementById('grnLinesLoading')) {
        document.getElementById('grnLinesLoading').classList.add('hidden');
    }
})();
</script>
