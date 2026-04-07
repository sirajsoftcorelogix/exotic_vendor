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

    <?php if ($mode === 'create' || $mode === 'edit'): ?>
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
                    <input type="file" id="grnSupportingFiles" name="grn_file[]" multiple accept="application/pdf,image/*,.pdf,.png,.jpg,.jpeg,.webp"
                        class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-900 hover:file:bg-amber-100 border border-dashed border-gray-300 rounded-xl bg-gray-50/50 px-3 py-2">
                    <div id="grnFileList" class="mt-2 space-y-1.5 hidden" aria-live="polite"></div>
                    <p class="text-[11px] text-gray-400 mt-1.5">PDF or images — delivery challan, photos, etc.</p>
                </div>
                <div class="flex flex-col">
                    <label class="block text-xs font-semibold text-gray-600 mb-1.5">GRN remarks <span class="text-gray-400 font-normal">(optional)</span></label>
                    <textarea id="grnRemarks" name="grn_remarks" rows="3" placeholder="Overall notes for this GRN…"
                        class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 resize-y min-h-[5.5rem]"><?php echo $grnRemarksValue; ?></textarea>
                </div>
            </div>

            <p id="grnStatus" class="text-sm min-h-[1.25rem]" role="status"></p>
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
                Check line items, quantities, and receipt details. Saving creates a GRN for every line with a received quantity greater than zero. You can add more GRNs later until the transfer is fully received.
            <?php endif; ?>
        </p>
        <?php if ($mode === 'edit'): ?>
            <button type="submit" id="saveChanges" class="order-1 sm:order-2 inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap shrink-0">
                <i class="fas fa-save text-xs opacity-95" aria-hidden="true"></i>
                Save changes
            </button>
        <?php else: ?>
            <button type="button" onclick="saveStockTransferGrn(event)" id="saveChanges" class="order-1 sm:order-2 inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap shrink-0">
                <i class="fas fa-check text-xs opacity-95" aria-hidden="true"></i>
                Save GRN
            </button>
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

<script>
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

    document.querySelectorAll('.st-grn-thumb').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var src = btn.getAttribute('data-img') || '';
            var title = btn.getAttribute('data-title') || '';
            if (src) openImagePopup(src, title);
        });
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
            Array.prototype.forEach.call(grnFileInput.files || [], function (f) {
                var k = grnFileKey(f);
                if (!seen[k]) {
                    seen[k] = true;
                    grnStagedFiles.push(f);
                }
            });
            grnFileInput.value = '';
            grnApplyStagedToInput();
            grnRenderFileList();
        });
    }
})();

function saveStockTransferGrn(event) {
    event.preventDefault();

    var receivedBy = document.querySelector('select[name="received_by"]').value;
    var warehouse = document.querySelector('select[name="warehouse_id"]').value;
    var qtyInputs = Array.from(document.querySelectorAll('input[name="qty_received[]"]'));

    if (!warehouse) {
        alert('Please select a receiving warehouse.');
        return;
    }

    if (!receivedBy) {
        alert('Please select who received the shipment.');
        return;
    }

    if (qtyInputs.every(function (i) { return parseInt(i.value || 0, 10) <= 0; })) {
        alert('Enter a received quantity for at least one line.');
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

    var items = [];
    var itemIds = Array.from(document.querySelectorAll('input[name="item_id[]"]')).map(function (i) { return i.value; });
    var skus = Array.from(document.querySelectorAll('input[name="sku[]"]')).map(function (i) { return i.value; });
    var itemCodes = Array.from(document.querySelectorAll('input[name="item_code[]"]')).map(function (i) { return i.value; });
    var transferQtys = Array.from(document.querySelectorAll('input[name="transfer_qty[]"]')).map(function (i) { return i.value; });
    var receivedQtys = Array.from(document.querySelectorAll('input[name="qty_received[]"]')).map(function (i) { return i.value; });
    var acceptables = Array.from(document.querySelectorAll('input[name="qty_acceptable[]"]')).map(function (i) { return i.checked ? 1 : 0; });
    var itemRemarks = Array.from(document.querySelectorAll('textarea[name="item_remarks[]"]')).map(function (i) { return i.value; });

    for (var i = 0; i < itemIds.length; i++) {
        var rec = parseInt(receivedQtys[i] || 0, 10) || 0;
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

    var formData = new FormData();
    formData.append('transfer_id', parseInt(transferId, 10));
    formData.append('received_by', parseInt(receivedBy, 10));
    formData.append('warehouse_id', parseInt(warehouse, 10));
    formData.append('received_date', receivedDate);
    formData.append('grn_remarks', remarks);
    formData.append('remarks', remarks);
    formData.append('items', JSON.stringify(items));

    var fileInput = document.querySelector('input[name="grn_file[]"]');
    if (fileInput && fileInput.files.length > 0) {
        for (var f = 0; f < fileInput.files.length; f++) {
            formData.append('grn_file[]', fileInput.files[f]);
        }
    }

    var statusEl = document.getElementById('grnStatus');
    statusEl.textContent = 'Saving…';
    statusEl.classList.remove('text-red-600', 'text-green-600');
    statusEl.classList.add('text-gray-600');

    fetch('?page=stock_transfer_grns&action=create_post', {
        method: 'POST',
        body: formData
    })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                statusEl.classList.remove('text-red-600', 'text-gray-600');
                statusEl.classList.add('text-green-600');
                statusEl.textContent = res.message || 'Saved successfully. Redirecting…';
                setTimeout(function () {
                    window.location.href = '?page=stock_transfer_grns&action=list&transfer_id=' + encodeURIComponent(transferId);
                }, 900);
            } else {
                statusEl.classList.remove('text-green-600', 'text-gray-600');
                statusEl.classList.add('text-red-600');
                statusEl.textContent = res.message || 'Could not save GRN.';
            }
        })
        .catch(function (err) {
            statusEl.classList.remove('text-green-600', 'text-gray-600');
            statusEl.classList.add('text-red-600');
            statusEl.textContent = 'Request failed. Please try again.';
            console.error(err);
        });
}
</script>
