<?php
$transfer = $transfer ?? [];
$bulk_grid_prefill = $bulk_grid_prefill ?? [];
$transfer_grn_count = (int)($transfer_grn_count ?? 0);
$isBulkEdit = !empty($transfer['id']);
$currentUserId = $_SESSION['user']['id'] ?? 0;
$selectedRequestedBy = (int)($transfer['requested_by'] ?? $currentUserId);
$selectedDispatchBy = (int)($transfer['dispatch_by'] ?? $currentUserId);
$defaultDispatchDate = !empty($transfer['dispatch_date']) ? $transfer['dispatch_date'] : date('Y-m-d');
$gridRowCount = max(40, count($bulk_grid_prefill));
$fromWhId = isset($transfer['from_warehouse']) ? (int)$transfer['from_warehouse'] : 0;
$toWhId = isset($transfer['to_warehouse']) ? (int)$transfer['to_warehouse'] : 0;
?>
<div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-amber-50/25">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/80 via-white to-slate-50/50 shadow-sm ring-1 ring-amber-900/[0.04] mb-6 sm:mb-8">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-12 h-48 w-48 rounded-full bg-sky-200/10 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-6 sm:px-8 sm:py-7 flex flex-col lg:flex-row lg:items-start lg:justify-between gap-5 sm:gap-8">
            <div class="min-w-0 max-w-3xl lg:pt-0.5">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-3 sm:mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas <?php echo $isBulkEdit ? 'fa-edit' : 'fa-table'; ?> text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Warehouse · <?php echo $isBulkEdit ? 'Edit transfer' : 'New transfer'; ?></span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    <?php echo $isBulkEdit ? 'Edit' : 'Stock'; ?> <span class="text-amber-800">Transfer</span>
                </h1>
                <p class="mt-2 sm:mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    <?php echo $isBulkEdit
                        ? 'Update dispatch schedule, route, line items, and transport details, then save.'
                        : 'Set dispatch dates and route, add line items in the grid (or upload a file), then add transport details and submit.'; ?>
                </p>
            </div>
            <div class="flex shrink-0 flex-col sm:flex-row sm:items-center gap-2.5 sm:gap-3 lg:pl-2 xl:pl-6 w-full sm:w-auto">
                <a href="?page=products&action=in_transit" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 sm:py-3 rounded-xl border border-sky-200 bg-sky-50 text-sky-800 text-sm font-semibold shadow-sm hover:bg-sky-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-truck-loading text-xs" aria-hidden="true"></i>
                    In transit
                </a>
                <a href="?page=products&action=stock_transfer" class="inline-flex items-center justify-center gap-2 px-5 py-2.5 sm:py-3 rounded-xl border border-gray-200/90 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-history text-xs text-amber-700" aria-hidden="true"></i>
                    Transfer history
                </a>
            </div>
        </div>
    </div>

    <form id="bulkTransferForm" class="space-y-6 sm:space-y-8" method="POST" enctype="multipart/form-data" action="?page=products&action=process_transfer_stock_bulk">
        <?php if ($isBulkEdit): ?>
            <input type="hidden" name="transfer_id" id="bulk_transfer_id" value="<?php echo (int)$transfer['id']; ?>">
        <?php endif; ?>
        <input type="hidden" name="bulk_mode" id="bulk_mode" value="grid">

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-3.5 sm:px-6 sm:py-4 border-b border-gray-100 bg-slate-50/90">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm border border-sky-100 mt-0.5 sm:mt-0">
                        <i class="fas fa-file-invoice text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 leading-snug">Order &amp; schedule</h2>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed max-w-prose">Transfer number is generated from the route. Dates and owners are required.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6 lg:p-7">
                <div class="grid gap-x-5 gap-y-5 sm:gap-x-6 sm:gap-y-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col gap-0">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Transfer order no.</label>
                        <input type="text" name="transfer_order_no" id="bulk_transfer_order_no" readonly value="<?php echo htmlspecialchars($isBulkEdit ? ($transfer['transfer_order_no'] ?? '') : ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed shadow-sm" title="<?php echo $isBulkEdit ? 'Order number is fixed for this transfer' : 'Assigned when warehouses are selected'; ?>">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Dispatch date <span class="text-red-500">*</span></label>
                        <input type="date" id="dispatch_date" name="dispatch_date" value="<?php echo htmlspecialchars($defaultDispatchDate); ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Est. delivery date <span class="text-red-500">*</span></label>
                        <input type="date" id="est_delivery_date" name="est_delivery_date" value="<?php echo htmlspecialchars($transfer['est_delivery_date'] ?? ''); ?>" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Requested by <span class="text-red-500">*</span></label>
                        <select id="requested_by" name="requested_by" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select user…</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $selectedRequestedBy ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex flex-col md:col-span-2 lg:col-span-1">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Dispatch by <span class="text-red-500">*</span></label>
                        <select id="dispatch_by" name="dispatch_by" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select user…</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $selectedDispatchBy ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-3.5 sm:px-6 sm:py-4 border-b border-gray-100 bg-slate-50/90">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm border border-sky-100 mt-0.5 sm:mt-0">
                        <i class="fas fa-route text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 leading-snug">Route</h2>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed max-w-prose">Choose source and destination. Order number updates when both are set.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6 lg:p-7">
                <div class="grid gap-5 lg:gap-6 lg:grid-cols-[1fr_auto_1fr] lg:items-center items-stretch">
                    <div class="rounded-xl border border-amber-100/80 bg-gradient-to-b from-amber-50/50 to-white p-4 sm:p-5 shadow-sm flex flex-col">
                        <label class="block text-xs font-semibold text-amber-900/80 mb-1.5 uppercase tracking-wide">From <span class="text-red-500 normal-case">*</span></label>
                        <select id="from_warehouse" name="from_warehouse" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select warehouse…</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"<?php echo ($fromWhId > 0 && (int)$warehouse['id'] === $fromWhId) ? ' selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-3 leading-relaxed flex-1 min-h-[3rem]" id="source_address">Select a warehouse to see the full address.</p>
                    </div>
                    <div class="flex items-center justify-center py-2 lg:py-0" aria-hidden="true">
                        <span class="inline-flex h-11 w-11 sm:h-12 sm:w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-600 to-amber-700 text-white shadow-md shadow-amber-900/20 ring-4 ring-white lg:translate-y-0"><i class="fas fa-arrow-right text-sm max-lg:rotate-90 transition-transform" aria-hidden="true"></i></span>
                    </div>
                    <div class="rounded-xl border border-slate-200/90 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5 shadow-sm flex flex-col">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wide">To <span class="text-red-500 normal-case">*</span></label>
                        <select id="to_warehouse" name="to_warehouse" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select warehouse…</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"<?php echo ($toWhId > 0 && (int)$warehouse['id'] === $toWhId) ? ' selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-3 leading-relaxed flex-1 min-h-[3rem]" id="dest_address">Select a warehouse to see the full address.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-3.5 sm:px-6 sm:py-4 border-b border-gray-100 bg-slate-50/90">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sm:gap-4">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 leading-snug">Line items</h2>
                        <p class="text-xs text-gray-500 mt-1">Grid entry or spreadsheet upload.</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 sm:gap-2.5">
                        <button type="button" id="tab_grid" class="bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300">1. Excel-style grid</button>
                        <button type="button" id="tab_upload" class="bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">2. Upload spreadsheet</button>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6 lg:p-7">

            <div id="panel_upload" class="bulk-panel space-y-4 hidden">
                <p class="text-sm text-gray-600">Use columns: <strong>ItemCode</strong>, <strong>Size</strong>, <strong>Color</strong>, <strong>Quantity</strong>. Headers can use spaces (e.g. <code class="bg-gray-100 px-1 rounded">Item Code</code>). Size and Color can be blank if they match empty variants in your catalog.</p>
                <div class="flex flex-wrap gap-3 items-center">
                    <a href="?page=products&action=transfer_bulk_template" class="inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900">
                        <i class="fas fa-download"></i> Download CSV template
                    </a>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">File (.csv, .xlsx, .xls)</label>
                    <input type="file" name="bulk_file" id="bulk_file" accept=".csv,.xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel,text/csv" class="w-full max-w-md text-sm">
                    <div id="bulk_file_selection_row" class="mt-2 flex flex-wrap items-center gap-2 hidden">
                        <span id="bulk_file_name" class="text-sm text-gray-700 truncate max-w-[min(100%,28rem)]" title=""></span>
                        <button type="button" id="bulk_file_clear" class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-amber-500/30">
                            <i class="fas fa-times text-[10px]" aria-hidden="true"></i>
                            Remove file
                        </button>
                    </div>
                </div>
            </div>

            <div id="panel_grid" class="bulk-panel">
                <p class="text-sm text-gray-600 mb-4 leading-relaxed max-w-4xl">Type a <strong>SKU</strong> to search—matching products appear below the cell. Pick one to fill <strong>item code</strong>, size, and color automatically. Enter quantity in the last column. You can still adjust size or color manually if needed.</p>
                <div class="-mx-0.5 sm:mx-0 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm max-h-[min(28rem,55vh)] sm:max-h-[420px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100 sticky top-0 z-10 shadow-[0_1px_0_0_rgb(229_231_235)]">
                            <tr>
                                <th class="px-2 sm:px-3 py-2.5 text-center font-semibold text-gray-700 w-10 min-w-[2.5rem]" scope="col">#</th>
                                <th class="px-2 py-2.5 text-center font-semibold text-gray-700 w-12 min-w-[3rem]" scope="col">Image</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-700 min-w-[7rem]">SKU <span class="font-normal text-gray-500">(search)</span></th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-700 min-w-[5rem]">Size</th>
                                <th class="px-3 py-2.5 text-left font-semibold text-gray-700 min-w-[5rem]">Color</th>
                                <th class="px-3 py-2.5 text-right font-semibold text-gray-700 w-24 min-w-[5.5rem]">Qty</th>
                                <?php if ($isBulkEdit): ?>
                                    <th class="px-2 py-2.5 text-center font-semibold text-gray-700 w-14 min-w-[3.5rem]" scope="col">Remove</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody id="bulk_grid_body">
                            <?php for ($i = 0; $i < $gridRowCount; $i++):
                                $pf = $bulk_grid_prefill[$i] ?? null;
                                $pf = is_array($pf) ? $pf : null;
                                $pfIc = $pf !== null ? (string)($pf['item_code'] ?? '') : '';
                                $pfSku = $pf !== null ? (string)($pf['sku'] ?? '') : '';
                                $pfSize = $pf !== null ? (string)($pf['size'] ?? '') : '';
                                $pfColor = $pf !== null ? (string)($pf['color'] ?? '') : '';
                                $pfQty = ($pf !== null && array_key_exists('qty', $pf)) ? (int)$pf['qty'] : null;
                                $pfImg = $pf !== null ? trim((string)($pf['image'] ?? '')) : '';
                                $pfLineId = $pf !== null ? (int)($pf['transfer_line_id'] ?? 0) : 0;
                                $pfGrnLocked = $pf !== null && !empty($pf['line_grn_locked']);
                                $imgHas = $pfImg !== '';
                                $imgAlt = htmlspecialchars($pfSku !== '' ? $pfSku : ($pfIc !== '' ? $pfIc : 'Product'), ENT_QUOTES, 'UTF-8');
                                ?>
                                <tr class="bulk-grid-row border-b border-gray-100 hover:bg-amber-50/20 transition-colors">
                                    <td class="bulk-row-num px-2 sm:px-3 py-2 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-middle border-r border-gray-100"><?php echo $i + 1; ?></td>
                                    <td class="bulk-img-cell w-12 min-w-[3rem] p-1.5 align-middle bg-gray-50/80 border-r border-gray-100">
                                        <div class="bulk-row-img-wrap relative flex min-h-[2.5rem] items-center justify-center">
                                            <?php if ($imgHas): ?>
                                                <img alt="<?php echo $imgAlt; ?>" title="Click to enlarge" class="bulk-row-img max-h-11 max-w-[2.35rem] w-full cursor-pointer object-contain rounded border border-gray-200 bg-white" src="<?php echo htmlspecialchars($pfImg, ENT_QUOTES, 'UTF-8'); ?>" width="40" height="48" decoding="async" loading="lazy">
                                                <span class="bulk-row-img-ph pointer-events-none text-gray-300 text-[10px] select-none leading-none py-1 hidden" aria-hidden="true">—</span>
                                            <?php else: ?>
                                                <img alt="" title="Click to enlarge" class="bulk-row-img hidden max-h-11 max-w-[2.35rem] w-full cursor-pointer object-contain rounded border border-gray-200 bg-white" width="40" height="48" decoding="async" loading="lazy">
                                                <span class="bulk-row-img-ph pointer-events-none text-gray-300 text-[10px] select-none leading-none py-1" aria-hidden="true">—</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0">
                                        <div class="relative w-full min-w-0">
                                            <input type="hidden" class="bulk-inp-item-code" value="<?php echo htmlspecialchars($pfIc, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                                            <input type="text" class="bulk-inp-sku w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" placeholder="Type SKU…" value="<?php echo htmlspecialchars($pfSku, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                                            <div class="bulk-ac-menu hidden absolute left-0 right-0 z-30 mt-0.5 max-h-52 min-w-[12rem] overflow-y-auto rounded-md border border-gray-300 bg-white text-xs shadow-lg" role="listbox"></div>
                                        </div>
                                    </td>
                                    <td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0"><input type="text" class="bulk-inp-size w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" value="<?php echo htmlspecialchars($pfSize, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off"></td>
                                    <td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0"><input type="text" class="bulk-inp-color w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" value="<?php echo htmlspecialchars($pfColor, ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off"></td>
                                    <td class="px-2 sm:px-3 py-2 align-middle"><input type="number" min="0" class="bulk-inp-qty w-full max-w-[6rem] sm:max-w-none ml-auto block px-2.5 py-2 border border-gray-200 rounded-md text-sm tabular-nums text-right leading-tight" value="<?php echo $pfQty !== null ? (int)$pfQty : ''; ?>" autocomplete="off"></td>
                                    <?php if ($isBulkEdit): ?>
                                        <td class="px-1 py-2 align-middle text-center w-14">
                                            <?php if ($pfLineId > 0 && !$pfGrnLocked): ?>
                                                <button type="button"
                                                    class="bulk-line-delete inline-flex h-8 w-8 items-center justify-center rounded-md border border-red-200 bg-white text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400"
                                                    data-line-id="<?php echo $pfLineId; ?>"
                                                    title="Remove this line (no GRN for this item)"
                                                    aria-label="Remove line item">
                                                    <i class="fas fa-times text-xs" aria-hidden="true"></i>
                                                </button>
                                            <?php elseif ($pfLineId > 0 && $pfGrnLocked): ?>
                                                <span class="inline-flex text-gray-400 text-xs cursor-help" title="This item has a GRN and cannot be removed here.">—</span>
                                            <?php else: ?>
                                                <span class="text-gray-300 text-xs" aria-hidden="true">·</span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="bulk_add_rows" class="mt-4 inline-flex items-center gap-1.5 text-sm font-semibold text-amber-700 hover:text-amber-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 rounded-lg px-1 -mx-1 py-0.5">+ Add 10 rows</button>
            </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-3.5 sm:px-6 sm:py-4 border-b border-gray-100 bg-slate-50/90">
                <div class="flex items-start sm:items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-gray-700 shadow-sm border border-gray-200 mt-0.5 sm:mt-0">
                        <i class="fas fa-truck text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900 leading-snug">Transportation</h2>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed max-w-prose">Vehicle, driver, and e-way documentation (all optional unless your process requires them).</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6 lg:p-7">
                <div class="grid gap-x-5 gap-y-5 sm:gap-x-6 sm:gap-y-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Booking no.</label>
                        <input type="text" name="booking_no" value="<?php echo htmlspecialchars($transfer['booking_no'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Vehicle no.</label>
                        <input type="text" name="vehicle_no" value="<?php echo htmlspecialchars($transfer['vehicle_no'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Vehicle type</label>
                        <input type="text" name="vehicle_type" value="<?php echo htmlspecialchars($transfer['vehicle_type'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Driver name</label>
                        <input type="text" name="driver_name" value="<?php echo htmlspecialchars($transfer['driver_name'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Driver mobile</label>
                        <input type="tel" name="driver_mobile" value="<?php echo htmlspecialchars($transfer['driver_mobile'] ?? ''); ?>" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">E-way bill</label>
                        <input type="file" name="eway_bill_file" accept="application/pdf,image/*" class="w-full px-3 py-2.5 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 bg-gray-50/50 transition hover:border-amber-300/60">
                        <input type="hidden" name="existing_eway_bill_file" value="<?php echo htmlspecialchars($transfer['eway_bill_file'] ?? ''); ?>">
                        <input type="hidden" name="remove_eway_bill_file" value="0">
                        <p class="text-[11px] text-gray-400 mt-1.5">PDF or image</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200/50 bg-gradient-to-r from-amber-50/40 via-white to-amber-50/30 px-5 py-5 sm:px-7 sm:py-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sm:gap-6 shadow-sm ring-1 ring-amber-900/[0.04]">
            <p class="text-sm text-gray-600 max-w-xl leading-relaxed order-2 sm:order-1"><span class="font-semibold text-gray-800">Ready?</span> <?php echo $isBulkEdit
                ? 'Check line items and route, then save changes.'
                : 'Check line items and warehouses, then create the transfer. You can review it later in transfer history.'; ?></p>
            <button type="submit" class="order-1 sm:order-2 inline-flex items-center justify-center gap-2 w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap shrink-0">
                <i class="fas fa-check text-xs opacity-95" aria-hidden="true"></i>
                <?php echo $isBulkEdit ? 'Save transfer' : 'Create bulk transfer'; ?>
            </button>
        </div>

        <textarea name="bulk_rows_json" id="bulk_rows_json" class="hidden" aria-hidden="true"></textarea>
    </form>

    <?php if ($isBulkEdit && $transfer_grn_count === 0): ?>
        <div class="mt-8 rounded-2xl border border-red-200/80 bg-red-50/40 px-5 py-5 sm:px-7 sm:py-6 shadow-sm ring-1 ring-red-900/[0.06]">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-red-900">Delete this transfer</h2>
                    <p class="mt-1 text-xs sm:text-sm text-red-800/90 leading-relaxed max-w-xl">
                        Allowed only while no GRN has been recorded. This removes the transfer and restores outbound stock at the source warehouse.
                    </p>
                </div>
                <form method="post" action="?page=products&action=stock_transfer_delete" class="shrink-0"
                    onsubmit="return confirm('Delete this stock transfer permanently? Outbound stock will be restored.');">
                    <input type="hidden" name="transfer_id" value="<?php echo (int)$transfer['id']; ?>">
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 w-full sm:w-auto px-5 py-2.5 rounded-xl border border-red-300 bg-white text-red-700 text-sm font-semibold hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition">
                        <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                        Delete transfer
                    </button>
                </form>
            </div>
        </div>
    <?php elseif ($isBulkEdit && $transfer_grn_count > 0): ?>
        <p class="mt-8 text-xs text-gray-500 max-w-2xl leading-relaxed">
            This transfer has GRN activity and cannot be deleted from here. Remove or adjust GRNs first if your process allows it.
        </p>
    <?php endif; ?>
</div>
</div>

<div id="bulkImageLightbox" class="fixed inset-0 z-[100] hidden items-center justify-center bg-black/80 p-4 sm:p-8" role="dialog" aria-modal="true" aria-label="Enlarged product image">
    <button type="button" id="bulkImageLightboxClose" class="absolute top-3 right-3 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-2xl leading-none text-white hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-white" aria-label="Close">&times;</button>
    <img id="bulkImageLightboxImg" alt="" class="max-h-[90vh] max-w-full object-contain rounded-lg shadow-2xl">
</div>

<div id="stockTransferNoticeModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="stockTransferNoticeTitle">
    <div id="stockTransferNoticePanel" class="w-full max-w-2xl rounded-2xl bg-white shadow-2xl ring-1 ring-gray-900/10 max-h-[85vh] flex flex-col transition-[max-width] duration-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-start gap-3 shrink-0">
            <span id="stockTransferNoticeIconWrap" class="mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <i id="stockTransferNoticeIcon" class="fas fa-exclamation-triangle text-sm" aria-hidden="true"></i>
            </span>
            <div class="min-w-0 flex-1">
                <h3 id="stockTransferNoticeTitle" class="text-base font-semibold text-gray-900">Stock Transfer Validation</h3>
                <p id="stockTransferNoticeSubtitle" class="text-xs text-gray-500 mt-0.5">Please review and fix the highlighted issue.</p>
                <p id="stockTransferNoticeCount" class="hidden mt-1.5 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800"></p>
            </div>
        </div>
        <div class="px-5 py-4 overflow-y-auto min-h-0">
            <p id="stockTransferNoticeMessage" class="text-sm text-gray-700 leading-relaxed whitespace-pre-line"></p>
            <div id="stockTransferNoticeTableWrap" class="mt-4 hidden">
                <div class="rounded-xl border border-red-200/70 bg-red-50/30 overflow-hidden">
                    <div class="overflow-x-auto max-h-[min(42vh,22rem)] overflow-y-auto">
                        <table class="min-w-full divide-y divide-red-200/60 text-sm">
                            <thead class="bg-red-100/60 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-900/80 w-10">#</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-900/80 min-w-[6rem]">Item code</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-900/80 min-w-[5rem]">Size <span class="font-normal normal-case text-red-800/60">(upload)</span></th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-900/80 min-w-[5rem]">Color <span class="font-normal normal-case text-red-800/60">(upload)</span></th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-red-900/80 w-14">Qty</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-red-900/80 min-w-[10rem]">In catalog</th>
                                </tr>
                            </thead>
                            <tbody id="stockTransferNoticeTableBody" class="divide-y divide-red-100/80 bg-white"></tbody>
                        </table>
                    </div>
                </div>
                <p id="stockTransferNoticeTableMore" class="hidden mt-2 text-xs font-medium text-red-800/90"></p>
                <div class="mt-3 rounded-lg border border-sky-200/80 bg-sky-50/50 px-3 py-2.5 text-xs text-sky-900 leading-relaxed">
                    <p class="font-semibold text-sky-950 mb-1"><i class="fas fa-lightbulb text-sky-600 mr-1.5" aria-hidden="true"></i>How to fix</p>
                    <ul class="list-disc pl-4 space-y-1 text-sky-900/90">
                        <li>Match <strong>Size</strong> and <strong>Color</strong> exactly to a variant listed under “In catalog” (blank means empty in the catalog).</li>
                        <li>Some products store the dimension in <strong>Color</strong> with Size blank — move values from Size to Color if needed.</li>
                        <li>If the item code is missing from catalog, click <strong>Refresh from API</strong>, then submit again.</li>
                    </ul>
                </div>
            </div>
            <div id="stockTransferNoticeStockWrap" class="mt-4 hidden">
                <div class="rounded-xl border border-amber-200/80 bg-amber-50/25 overflow-hidden">
                    <div class="overflow-x-auto max-h-[min(42vh,22rem)] overflow-y-auto">
                        <table class="min-w-full divide-y divide-amber-200/60 text-sm">
                            <thead class="bg-amber-100/70 sticky top-0 z-10">
                                <tr>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-amber-900/80 w-10">#</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-amber-900/80 min-w-[8rem]">SKU</th>
                                    <th scope="col" class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-amber-900/80 min-w-[5rem]">Item code</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-amber-900/80 w-20">Requested</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-amber-900/80 w-20">Available</th>
                                    <th scope="col" class="px-3 py-2 text-right text-xs font-semibold uppercase tracking-wide text-amber-900/80 w-20">Short</th>
                                </tr>
                            </thead>
                            <tbody id="stockTransferNoticeStockBody" class="divide-y divide-amber-100/80 bg-white"></tbody>
                        </table>
                    </div>
                </div>
                <p id="stockTransferNoticeStockMore" class="hidden mt-2 text-xs font-medium text-amber-900/90"></p>
                <p class="mt-3 text-xs text-amber-900/80 leading-relaxed">Reduce transfer quantity for these lines, or add stock at the <strong>source warehouse</strong> before retrying.</p>
            </div>
            <div id="stockTransferNoticeListWrap" class="mt-3 hidden">
                <div id="stockTransferNoticeListBox" class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <p id="stockTransferNoticeListHeading" class="text-xs font-semibold text-slate-800 mb-1.5">Technical details</p>
                    <ul id="stockTransferNoticeList" class="list-disc pl-5 space-y-1.5 text-xs text-slate-800 font-mono break-all max-h-[42vh] overflow-y-auto pr-2"></ul>
                </div>
            </div>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex justify-end shrink-0">
            <button type="button" id="stockTransferNoticeRefreshApi" class="hidden mr-2 inline-flex items-center justify-center rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2">Refresh from API</button>
            <button type="button" id="stockTransferNoticeOk" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">OK</button>
        </div>
    </div>
</div>

<div id="bulkTransferProcessingOverlay" class="fixed inset-0 z-[120] hidden flex-col items-center justify-center bg-slate-900/80 backdrop-blur-sm p-6" aria-hidden="true" aria-live="polite" role="status">
    <div class="w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl ring-1 ring-gray-900/10 text-center">
        <div class="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <i class="fas fa-truck-loading text-2xl animate-pulse" aria-hidden="true"></i>
        </div>
        <h2 class="text-lg font-semibold text-gray-900">Processing your stock transfer</h2>
        <p class="mt-3 text-sm text-gray-600 leading-relaxed">Large transfers can take several minutes while we validate stock, sync data, and update movements. Please keep this tab open and do not refresh or close the browser.</p>
        <div class="mt-6 text-left">
            <div class="relative h-2.5 w-full overflow-hidden rounded-full bg-gray-200">
                <div class="bulk-transfer-progress-bar absolute top-0 bottom-0 w-[38%] rounded-full bg-gradient-to-r from-amber-500 via-amber-600 to-amber-500 shadow-sm"></div>
            </div>
            <p class="mt-2 text-center text-xs font-medium text-amber-800/90">Working on the server…</p>
        </div>
    </div>
</div>
<style>
@keyframes bulk-transfer-progress-sweep {
    0% { left: -38%; }
    100% { left: 100%; }
}
.bulk-transfer-progress-bar {
    left: -38%;
    animation: bulk-transfer-progress-sweep 2.2s ease-in-out infinite;
}
</style>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
(function () {
    const stockTransferNoticeModal = document.getElementById('stockTransferNoticeModal');
    const stockTransferNoticePanel = document.getElementById('stockTransferNoticePanel');
    const stockTransferNoticeMessage = document.getElementById('stockTransferNoticeMessage');
    const stockTransferNoticeTitle = document.getElementById('stockTransferNoticeTitle');
    const stockTransferNoticeSubtitle = document.getElementById('stockTransferNoticeSubtitle');
    const stockTransferNoticeCount = document.getElementById('stockTransferNoticeCount');
    const stockTransferNoticeListWrap = document.getElementById('stockTransferNoticeListWrap');
    const stockTransferNoticeList = document.getElementById('stockTransferNoticeList');
    const stockTransferNoticeTableWrap = document.getElementById('stockTransferNoticeTableWrap');
    const stockTransferNoticeTableBody = document.getElementById('stockTransferNoticeTableBody');
    const stockTransferNoticeTableMore = document.getElementById('stockTransferNoticeTableMore');
    const stockTransferNoticeStockWrap = document.getElementById('stockTransferNoticeStockWrap');
    const stockTransferNoticeStockBody = document.getElementById('stockTransferNoticeStockBody');
    const stockTransferNoticeStockMore = document.getElementById('stockTransferNoticeStockMore');
    const stockTransferNoticeIconWrap = document.getElementById('stockTransferNoticeIconWrap');
    const stockTransferNoticeIcon = document.getElementById('stockTransferNoticeIcon');
    const stockTransferNoticeRefreshApi = document.getElementById('stockTransferNoticeRefreshApi');
    const stockTransferNoticeOk = document.getElementById('stockTransferNoticeOk');
    let refreshCodesPending = [];

    const bulkTransferProcessingOverlay = document.getElementById('bulkTransferProcessingOverlay');

    function showBulkTransferProcessingOverlay() {
        if (!bulkTransferProcessingOverlay) return;
        bulkTransferProcessingOverlay.setAttribute('aria-hidden', 'false');
        bulkTransferProcessingOverlay.classList.remove('hidden');
        bulkTransferProcessingOverlay.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function hideBulkTransferProcessingOverlay() {
        if (!bulkTransferProcessingOverlay) return;
        bulkTransferProcessingOverlay.setAttribute('aria-hidden', 'true');
        bulkTransferProcessingOverlay.classList.add('hidden');
        bulkTransferProcessingOverlay.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function formatUploadDim(value) {
        const v = String(value == null ? '' : value).trim();
        if (v === '') {
            return '<span class="text-gray-400 italic">blank</span>';
        }
        return '<span class="text-gray-900">' + escapeHtml(v) + '</span>';
    }

    function renderCatalogVariantsCell(variants) {
        if (!Array.isArray(variants) || variants.length === 0) {
            return '<span class="inline-flex items-center rounded-md bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Not in catalog</span>';
        }
        const maxShow = 4;
        const visible = variants.slice(0, maxShow);
        const parts = visible.map(function (v) {
            const sku = escapeHtml(v.sku || '');
            const sz = String(v.size || '').trim();
            const cl = String(v.color || '').trim();
            let dims = [];
            if (sz !== '') dims.push('Size: ' + escapeHtml(sz));
            if (cl !== '') dims.push('Color: ' + escapeHtml(cl));
            if (dims.length === 0) dims.push('no size/color');
            return '<div class="leading-snug"><span class="font-mono text-[11px] text-gray-800">' + sku + '</span>'
                + '<span class="block text-[11px] text-gray-500">' + dims.join(' · ') + '</span></div>';
        });
        let html = '<div class="space-y-1.5">' + parts.join('') + '</div>';
        if (variants.length > maxShow) {
            html += '<p class="mt-1 text-[11px] text-gray-500">+' + (variants.length - maxShow) + ' more variant(s)</p>';
        }
        return html;
    }

    function renderNotFoundProductsTable(items, maxRows) {
        if (!stockTransferNoticeTableBody) return;
        const limit = Number.isFinite(maxRows) ? maxRows : 50;
        const visible = items.slice(0, limit);
        stockTransferNoticeTableBody.innerHTML = '';

        visible.forEach(function (row, idx) {
            const tr = document.createElement('tr');
            tr.className = idx % 2 === 0 ? 'bg-white' : 'bg-red-50/20';
            tr.innerHTML =
                '<td class="px-3 py-2.5 text-xs font-semibold text-gray-500 tabular-nums align-top">' + (idx + 1) + '</td>' +
                '<td class="px-3 py-2.5 align-top"><span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-900">' + escapeHtml(row.item_code || '—') + '</span></td>' +
                '<td class="px-3 py-2.5 align-top text-sm">' + formatUploadDim(row.size) + '</td>' +
                '<td class="px-3 py-2.5 align-top text-sm">' + formatUploadDim(row.color) + '</td>' +
                '<td class="px-3 py-2.5 align-top text-sm text-right tabular-nums font-semibold text-gray-900">' + escapeHtml(parseInt(row.quantity || 0, 10)) + '</td>' +
                '<td class="px-3 py-2.5 align-top text-sm">' + renderCatalogVariantsCell(row.catalog_variants) + '</td>';
            stockTransferNoticeTableBody.appendChild(tr);
        });

        if (stockTransferNoticeTableMore) {
            if (items.length > limit) {
                stockTransferNoticeTableMore.textContent = 'Showing first ' + limit + ' of ' + items.length + ' unmatched rows.';
                stockTransferNoticeTableMore.classList.remove('hidden');
            } else {
                stockTransferNoticeTableMore.textContent = '';
                stockTransferNoticeTableMore.classList.add('hidden');
            }
        }
    }

    function renderInsufficientStockTable(items, maxRows) {
        if (!stockTransferNoticeStockBody) return;
        const limit = Number.isFinite(maxRows) ? maxRows : 50;
        const visible = items.slice(0, limit);
        stockTransferNoticeStockBody.innerHTML = '';

        visible.forEach(function (row, idx) {
            const sku = String(row.sku || '').trim();
            const itemCode = String(row.item_code || '').trim();
            const requested = parseInt(row.requested_qty || 0, 10);
            const available = parseInt(row.available_qty || 0, 10);
            const shortfall = Math.max(0, requested - available);
            const tr = document.createElement('tr');
            tr.className = idx % 2 === 0 ? 'bg-white' : 'bg-amber-50/30';
            tr.innerHTML =
                '<td class="px-3 py-2.5 text-xs font-semibold text-gray-500 tabular-nums align-middle">' + (idx + 1) + '</td>' +
                '<td class="px-3 py-2.5 align-middle"><span class="font-mono text-xs text-gray-900 break-all">' + escapeHtml(sku || '—') + '</span></td>' +
                '<td class="px-3 py-2.5 align-middle">' +
                    (itemCode
                        ? '<span class="inline-flex rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs font-semibold text-gray-800">' + escapeHtml(itemCode) + '</span>'
                        : '<span class="text-gray-400 text-xs">—</span>') +
                '</td>' +
                '<td class="px-3 py-2.5 align-middle text-right tabular-nums text-sm font-semibold text-gray-900">' + requested + '</td>' +
                '<td class="px-3 py-2.5 align-middle text-right tabular-nums text-sm text-amber-800">' + available + '</td>' +
                '<td class="px-3 py-2.5 align-middle text-right tabular-nums text-sm font-bold text-red-700">' + shortfall + '</td>';
            stockTransferNoticeStockBody.appendChild(tr);
        });

        if (stockTransferNoticeStockMore) {
            if (items.length > limit) {
                stockTransferNoticeStockMore.textContent = 'Showing first ' + limit + ' of ' + items.length + ' SKU(s) with insufficient stock.';
                stockTransferNoticeStockMore.classList.remove('hidden');
            } else {
                stockTransferNoticeStockMore.textContent = '';
                stockTransferNoticeStockMore.classList.add('hidden');
            }
        }
    }

    function summarizeInsufficientStockMessage(items) {
        const count = Array.isArray(items) ? items.length : 0;
        if (count === 0) {
            return 'One or more items do not have enough stock at the source warehouse.';
        }
        if (count === 1) {
            return '1 product does not have enough stock at the source warehouse.';
        }
        return count + ' products do not have enough stock at the source warehouse.';
    }

    function showTransferNotice(message, opts) {
        opts = opts || {};
        if (!stockTransferNoticeModal || !stockTransferNoticeMessage) {
            alert(message);
            return;
        }
        const isProductNotFound = String(opts.errorType || '') === 'product_not_found'
            || (Array.isArray(opts.notFoundItems) && opts.notFoundItems.length > 0);
        const insufficientItems = Array.isArray(opts.insufficientItems) ? opts.insufficientItems : [];
        const isInsufficientStock = insufficientItems.length > 0 && !isProductNotFound;

        if (stockTransferNoticePanel) {
            if (isProductNotFound || isInsufficientStock) {
                stockTransferNoticePanel.classList.remove('max-w-2xl');
                stockTransferNoticePanel.classList.add('max-w-5xl');
            } else {
                stockTransferNoticePanel.classList.add('max-w-2xl');
                stockTransferNoticePanel.classList.remove('max-w-5xl');
            }
        }

        if (stockTransferNoticeTitle) {
            stockTransferNoticeTitle.textContent = String(opts.title || (isProductNotFound ? 'Products Not Found' : (isInsufficientStock ? 'Insufficient Warehouse Stock' : 'Stock Transfer Validation')));
        }
        if (stockTransferNoticeSubtitle) {
            stockTransferNoticeSubtitle.textContent = String(opts.subtitle || (isProductNotFound
                ? 'These rows do not match any product variant in your catalog.'
                : (isInsufficientStock
                    ? 'Compare requested vs available quantities below, then adjust your transfer.'
                    : 'Please review and fix the issue below.')));
        }
        if (stockTransferNoticeCount) {
            const notFoundCount = Array.isArray(opts.notFoundItems) ? opts.notFoundItems.length : 0;
            if (isInsufficientStock) {
                stockTransferNoticeCount.className = 'mt-1.5 inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-900';
                stockTransferNoticeCount.textContent = insufficientItems.length + ' SKU' + (insufficientItems.length === 1 ? '' : 's') + ' short';
                stockTransferNoticeCount.classList.remove('hidden');
            } else if (isProductNotFound && notFoundCount > 0) {
                stockTransferNoticeCount.className = 'mt-1.5 inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800';
                stockTransferNoticeCount.textContent = notFoundCount + ' unmatched row' + (notFoundCount === 1 ? '' : 's');
                stockTransferNoticeCount.classList.remove('hidden');
            } else {
                stockTransferNoticeCount.textContent = '';
                stockTransferNoticeCount.classList.add('hidden');
            }
        }
        stockTransferNoticeMessage.textContent = String(message || 'Something went wrong.');

        const notFoundItems = Array.isArray(opts.notFoundItems) ? opts.notFoundItems : [];
        if (stockTransferNoticeStockWrap && stockTransferNoticeStockBody) {
            if (isInsufficientStock) {
                renderInsufficientStockTable(insufficientItems, opts.maxInsufficientRows || 50);
                stockTransferNoticeStockWrap.classList.remove('hidden');
            } else {
                stockTransferNoticeStockBody.innerHTML = '';
                stockTransferNoticeStockWrap.classList.add('hidden');
                if (stockTransferNoticeStockMore) {
                    stockTransferNoticeStockMore.classList.add('hidden');
                }
            }
        }
        if (stockTransferNoticeTableWrap && stockTransferNoticeTableBody) {
            if (notFoundItems.length > 0) {
                renderNotFoundProductsTable(notFoundItems, opts.maxNotFoundRows || 50);
                stockTransferNoticeTableWrap.classList.remove('hidden');
            } else {
                stockTransferNoticeTableBody.innerHTML = '';
                stockTransferNoticeTableWrap.classList.add('hidden');
                if (stockTransferNoticeTableMore) {
                    stockTransferNoticeTableMore.classList.add('hidden');
                }
            }
        }

        if (stockTransferNoticeList && stockTransferNoticeListWrap) {
            stockTransferNoticeList.innerHTML = '';
            const listItems = Array.isArray(opts.listItems) ? opts.listItems : [];
            if (listItems.length > 0 && notFoundItems.length === 0 && !isInsufficientStock) {
                listItems.forEach(function (text) {
                    const li = document.createElement('li');
                    li.textContent = String(text);
                    stockTransferNoticeList.appendChild(li);
                });
                stockTransferNoticeListWrap.classList.remove('hidden');
            } else {
                stockTransferNoticeListWrap.classList.add('hidden');
            }
        }

        if (stockTransferNoticeIconWrap && stockTransferNoticeIcon) {
            const tone = String(opts.tone || (isProductNotFound ? 'error' : 'warning'));
            stockTransferNoticeIconWrap.className = 'mt-0.5 inline-flex h-8 w-8 items-center justify-center rounded-full';
            if (tone === 'success') {
                stockTransferNoticeIconWrap.classList.add('bg-emerald-100', 'text-emerald-700');
                stockTransferNoticeIcon.className = 'fas fa-check-circle text-sm';
            } else if (tone === 'error') {
                stockTransferNoticeIconWrap.classList.add('bg-red-100', 'text-red-700');
                stockTransferNoticeIcon.className = 'fas fa-times-circle text-sm';
            } else {
                stockTransferNoticeIconWrap.classList.add('bg-amber-100', 'text-amber-700');
                stockTransferNoticeIcon.className = 'fas fa-exclamation-triangle text-sm';
            }
        }

        refreshCodesPending = Array.isArray(opts.refreshableCodes) ? opts.refreshableCodes : [];
        if (stockTransferNoticeRefreshApi) {
            if (refreshCodesPending.length > 0) {
                stockTransferNoticeRefreshApi.classList.remove('hidden');
                stockTransferNoticeRefreshApi.textContent = String(opts.refreshButtonLabel || 'Refresh from API');
            } else {
                stockTransferNoticeRefreshApi.classList.add('hidden');
                stockTransferNoticeRefreshApi.textContent = 'Refresh from API';
            }
        }

        stockTransferNoticeModal.classList.remove('hidden');
        stockTransferNoticeModal.classList.add('flex');
        if (stockTransferNoticeOk) stockTransferNoticeOk.focus();
    }

    function clampNoticeList(listItems, maxItems) {
        if (!Array.isArray(listItems)) return [];
        const limit = Number.isFinite(maxItems) ? maxItems : 20;
        if (listItems.length <= limit) return listItems;
        const visible = listItems.slice(0, limit);
        visible.push('...and ' + (listItems.length - limit) + ' more row(s).');
        return visible;
    }

    function showBulkTransferValidationError(preview) {
        const notFoundItems = Array.isArray(preview && preview.not_found_items) ? preview.not_found_items : [];
        const insufficientItems = Array.isArray(preview && preview.insufficient_items) ? preview.insufficient_items : [];
        const isProductNotFound = String((preview && preview.error_type) || '') === 'product_not_found' || notFoundItems.length > 0;
        const isInsufficientStock = insufficientItems.length > 0 && !isProductNotFound;
        const isEmptyResponse = String((preview && preview.error_type) || '') === 'empty_response';
        const phpErrors = Array.isArray(preview && preview.php_errors) ? preview.php_errors.filter(Boolean) : [];
        let extraList = [];
        if (phpErrors.length > 0) {
            extraList = clampNoticeList(phpErrors, 30);
        }
        if (isEmptyResponse && preview && preview.action) {
            extraList.unshift('Action: ' + preview.action);
        }

        let title = 'Stock Transfer Validation';
        let subtitle = 'Please review and fix the issue below.';
        let tone = 'warning';
        let message = (preview && preview.message) ? preview.message : 'Something went wrong.';

        if (isProductNotFound) {
            title = 'Products Not Found';
            subtitle = 'Compare your upload with catalog variants below, then fix the file or grid and retry.';
            tone = 'error';
            message = (preview && preview.message) || 'Some rows could not be matched to products in your catalog.';
        } else if (isInsufficientStock) {
            title = 'Insufficient Warehouse Stock';
            subtitle = 'Compare requested vs available quantities below, then adjust your transfer.';
            tone = 'warning';
            message = summarizeInsufficientStockMessage(insufficientItems);
        } else if (isEmptyResponse || phpErrors.length > 0) {
            title = 'Server Error';
            subtitle = isEmptyResponse
                ? 'The server did not finish the request. PHP error details (if captured):'
                : 'The server reported PHP errors while processing this request.';
            tone = 'error';
        } else if (insufficientItems.length === 0 && Array.isArray(preview && preview.details) && preview.details.length) {
            extraList = clampNoticeList(preview.details, 20);
        }

        showTransferNotice(message, {
            title: title,
            subtitle: subtitle,
            tone: tone,
            errorType: isProductNotFound ? 'product_not_found' : '',
            notFoundItems: notFoundItems,
            insufficientItems: isInsufficientStock ? insufficientItems : [],
            listItems: extraList,
            refreshableCodes: Array.isArray(preview && preview.refreshable_item_codes) ? preview.refreshable_item_codes : [],
        });
    }

    function closeTransferNotice() {
        if (!stockTransferNoticeModal) return;
        stockTransferNoticeModal.classList.add('hidden');
        stockTransferNoticeModal.classList.remove('flex');
    }

    if (stockTransferNoticeOk) {
        stockTransferNoticeOk.addEventListener('click', closeTransferNotice);
    }
    if (stockTransferNoticeRefreshApi) {
        stockTransferNoticeRefreshApi.addEventListener('click', function () {
            if (!Array.isArray(refreshCodesPending) || refreshCodesPending.length === 0) {
                return;
            }
            const btn = stockTransferNoticeRefreshApi;
            const previousLabel = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Refreshing...';

            const fd = new FormData();
            fd.append('item_codes_json', JSON.stringify(refreshCodesPending));
            fetch(apiUrl('refresh_transfer_items_from_api'), {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                body: fd
            })
                .then(function (r) { return parseFetchJsonResponse(r); })
                .then(function (res) {
                    if (res && res.success) {
                        showTransferNotice(res.message || 'API refresh completed. Please submit again.', {
                            title: 'API Refresh Completed',
                            subtitle: 'Latest stock and product mapping were synced.',
                            tone: 'success',
                            listItems: [],
                        });
                    } else {
                        showTransferNotice((res && res.message) ? res.message : 'API refresh failed.', {
                            title: 'API Refresh Failed',
                            subtitle: 'Please fix the listed codes and retry.',
                            tone: 'error',
                            refreshableCodes: refreshCodesPending,
                            refreshButtonLabel: 'Retry Again',
                        });
                    }
                })
                .catch(function (err) {
                    showTransferNotice('API refresh failed: ' + err.message, {
                        title: 'API Refresh Failed',
                        subtitle: 'Please try again.',
                        tone: 'error',
                        refreshableCodes: refreshCodesPending,
                        refreshButtonLabel: 'Retry Again',
                    });
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = previousLabel;
                });
        });
    }
    if (stockTransferNoticeModal) {
        stockTransferNoticeModal.addEventListener('click', function (e) {
            if (e.target === stockTransferNoticeModal) closeTransferNotice();
        });
    }

    const warehouseData = {
        <?php foreach ($warehouses as $warehouse):
            $name = trim($warehouse['address_title'] ?? '');
            $addr = trim($warehouse['address'] ?? '');
        ?>
        <?php echo (int)$warehouse['id']; ?>: { name: <?php echo json_encode($name, JSON_UNESCAPED_UNICODE); ?>, address: <?php echo json_encode($addr, JSON_UNESCAPED_UNICODE); ?> },
        <?php endforeach; ?>
    };

    function apiUrl(action, query) {
        query = query || '';
        const basePath = window.location.pathname.replace(/\/$/, '');
        return basePath + '?page=products&action=' + encodeURIComponent(action) + query;
    }

    function buildFetchDiagnostics(response, rawText) {
        const lines = [];
        const status = response && response.status ? response.status : 0;
        const statusText = response && response.statusText ? response.statusText : '';
        lines.push('HTTP status: ' + status + (statusText ? (' ' + statusText) : ''));
        if (response && response.url) {
            lines.push('Request URL: ' + response.url);
        }
        const raw = rawText == null ? '' : String(rawText);
        lines.push('Response body size: ' + raw.length + ' bytes');
        if (response && response.headers && typeof response.headers.get === 'function') {
            const ct = response.headers.get('content-type');
            const cl = response.headers.get('content-length');
            if (ct) lines.push('Content-Type: ' + ct);
            if (cl) lines.push('Content-Length: ' + cl);
        }
        if (raw.trim()) {
            const preview = raw.trim().length > 800 ? raw.trim().substring(0, 800) + '…' : raw.trim();
            lines.push('Response body preview: ' + preview);
        } else {
            lines.push('Response body: (empty — PHP may have crashed, timed out, or the web server closed the connection before output)');
            lines.push('Tip: deploy latest ProductsController.php and check PHP-FPM / Nginx error logs on the server');
        }
        return lines;
    }

    function extractServerErrorFromText(text) {
        const trimmed = String(text || '').trim();
        if (!trimmed) return null;
        try {
            return JSON.parse(trimmed);
        } catch (e1) {
            const match = trimmed.match(/\{[\s\S]*\}/);
            if (match) {
                try {
                    return JSON.parse(match[0]);
                } catch (e2) {
                    return null;
                }
            }
        }
        return null;
    }

    function showFetchErrorNotice(err, title) {
        title = title || 'Request Failed';
        const baseMsg = (err && err.message) ? String(err.message) : 'Request failed.';
        let listItems = [];
        let detailMessage = '';

        if (err && err.serverPayload && typeof err.serverPayload === 'object') {
            if (err.serverPayload.message) {
                detailMessage = String(err.serverPayload.message);
            }
            if (Array.isArray(err.serverPayload.php_errors) && err.serverPayload.php_errors.length) {
                listItems = listItems.concat(err.serverPayload.php_errors);
            }
        }

        if (err && Array.isArray(err.fetchDiagnostics)) {
            listItems = listItems.concat(err.fetchDiagnostics);
        }
        if (err && err.parseError) {
            listItems.push('JSON parse error: ' + err.parseError);
        }

        const fullMessage = detailMessage
            ? (baseMsg + '\n\nServer error: ' + detailMessage)
            : baseMsg;

        showTransferNotice(fullMessage, {
            title: title,
            subtitle: listItems.length ? 'Technical details:' : 'No additional details were returned by the server.',
            tone: 'error',
            listItems: listItems,
        });
    }

    function parseFetchJsonResponse(response) {
        return response.text().then(function (text) {
            const raw = text || '';
            const trimmed = raw.trim();
            const diagnostics = buildFetchDiagnostics(response, raw);

            if (!trimmed) {
                const err = new Error('Server returned an empty response (HTTP ' + (response.status || 0) + '). The request may have timed out or hit a PHP error — check PHP error logs and try again.');
                err.fetchDiagnostics = diagnostics;
                throw err;
            }

            try {
                return JSON.parse(trimmed);
            } catch (parseErr) {
                const embedded = extractServerErrorFromText(trimmed);
                if (embedded && typeof embedded === 'object' && embedded.success === false) {
                    return embedded;
                }
                const err = new Error('Server returned invalid JSON (HTTP ' + (response.status || 0) + ').');
                err.fetchDiagnostics = diagnostics;
                err.parseError = parseErr.message;
                err.serverPayload = embedded;
                throw err;
            }
        });
    }

    function fetchNextTransferOrderNo(fromW, toW) {
        return fetch(apiUrl('get_transfer_order_no', '&from_warehouse=' + encodeURIComponent(fromW) + '&to_warehouse=' + encodeURIComponent(toW)), { credentials: 'same-origin' })
            .then(function (r) { return parseFetchJsonResponse(r); })
            .then(function (data) {
                if (data.success && data.transfer_order_no) return data.transfer_order_no;
                return 'TO-' + fromW + '-' + toW + '-0001';
            })
            .catch(function () { return 'TO-' + fromW + '-' + toW + '-0001'; });
    }

    const fromSel = document.getElementById('from_warehouse');
    const toSel = document.getElementById('to_warehouse');
    const orderInput = document.getElementById('bulk_transfer_order_no');
    const bulkTransferIdInput = document.getElementById('bulk_transfer_id');
    const bulkEditTransferId = bulkTransferIdInput ? parseInt(String(bulkTransferIdInput.value || '').trim(), 10) : 0;
    const isBulkEdit = bulkEditTransferId > 0;

    function updateWarehouseAddresses() {
        const srcEl = document.getElementById('source_address');
        const dstEl = document.getElementById('dest_address');
        if (srcEl) {
            srcEl.textContent = warehouseData[fromSel.value]?.address || 'Select a warehouse to see the full address.';
        }
        if (dstEl) {
            dstEl.textContent = warehouseData[toSel.value]?.address || 'Select a warehouse to see the full address.';
        }
    }

    function refreshOrderNo() {
        if (isBulkEdit) return;
        if (!orderInput || !fromSel.value || !toSel.value) return;
        fetchNextTransferOrderNo(fromSel.value, toSel.value).then(function (no) { orderInput.value = no; });
    }

    fromSel.addEventListener('change', function () {
        updateWarehouseAddresses();
        refreshOrderNo();
    });
    toSel.addEventListener('change', function () {
        updateWarehouseAddresses();
        refreshOrderNo();
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (!isBulkEdit) {
            fetch(apiUrl('get_last_warehouse'), { credentials: 'same-origin' })
                .then(function (r) { return parseFetchJsonResponse(r); })
                .then(function (data) {
                    if (data.success && data.warehouse_id) {
                        fromSel.value = data.warehouse_id;
                        fromSel.dispatchEvent(new Event('change'));
                    }
                })
                .catch(function () {});
        }

        updateWarehouseAddresses();
        if (!isBulkEdit && fromSel.value && toSel.value) refreshOrderNo();
    });

    const tabUpload = document.getElementById('tab_upload');
    const tabGrid = document.getElementById('tab_grid');
    const panelUpload = document.getElementById('panel_upload');
    const panelGrid = document.getElementById('panel_grid');
    const bulkMode = document.getElementById('bulk_mode');
    const bulkFile = document.getElementById('bulk_file');
    const bulkFileSelectionRow = document.getElementById('bulk_file_selection_row');
    const bulkFileName = document.getElementById('bulk_file_name');
    const bulkFileClear = document.getElementById('bulk_file_clear');

    function updateBulkFileSelectionUi() {
        if (!bulkFile || !bulkFileSelectionRow || !bulkFileName) return;
        const f = bulkFile.files && bulkFile.files[0];
        if (f) {
            bulkFileName.textContent = f.name;
            bulkFileName.title = f.name;
            bulkFileSelectionRow.classList.remove('hidden');
        } else {
            bulkFileName.textContent = '';
            bulkFileName.title = '';
            bulkFileSelectionRow.classList.add('hidden');
        }
    }
    if (bulkFile) {
        bulkFile.addEventListener('change', updateBulkFileSelectionUi);
    }
    if (bulkFileClear && bulkFile) {
        bulkFileClear.addEventListener('click', function () {
            bulkFile.value = '';
            updateBulkFileSelectionUi();
        });
    }

    function setTab(mode) {
        bulkMode.value = mode;
        if (mode === 'upload') {
            panelUpload.classList.remove('hidden');
            panelGrid.classList.add('hidden');
            tabUpload.className = 'bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300';
            tabGrid.className = 'bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200';
            bulkFile.removeAttribute('data-optional');
        } else {
            panelUpload.classList.add('hidden');
            panelGrid.classList.remove('hidden');
            tabGrid.className = 'bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300';
            tabUpload.className = 'bulk-tab px-3.5 sm:px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200';
            bulkFile.setAttribute('data-optional', '1');
        }
    }
    tabUpload.addEventListener('click', function () { setTab('upload'); });
    tabGrid.addEventListener('click', function () { setTab('grid'); });
    setTab('grid');

    function renumberBulkGrid() {
        document.querySelectorAll('#bulk_grid_body tr.bulk-grid-row').forEach(function (tr, idx) {
            const cell = tr.querySelector('.bulk-row-num');
            if (cell) cell.textContent = String(idx + 1);
        });
    }

    document.getElementById('bulk_add_rows').addEventListener('click', function () {
        const tbody = document.getElementById('bulk_grid_body');
        for (let k = 0; k < 10; k++) {
            const tr = document.createElement('tr');
            tr.className = 'bulk-grid-row border-b border-gray-100 hover:bg-amber-50/20 transition-colors';
            tr.innerHTML = '<td class="bulk-row-num px-2 sm:px-3 py-2 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-middle border-r border-gray-100"></td>' +
                '<td class="bulk-img-cell w-12 min-w-[3rem] p-1.5 align-middle bg-gray-50/80 border-r border-gray-100">' +
                '<div class="bulk-row-img-wrap relative flex min-h-[2.5rem] items-center justify-center">' +
                '<img alt="" title="Click to enlarge" class="bulk-row-img hidden max-h-11 max-w-[2.35rem] w-full cursor-pointer object-contain rounded border border-gray-200 bg-white" width="40" height="48" decoding="async" loading="lazy">' +
                '<span class="bulk-row-img-ph pointer-events-none text-gray-300 text-[10px] select-none leading-none py-1" aria-hidden="true">—</span></div></td>' +
                '<td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0"><div class="relative w-full min-w-0">' +
                '<input type="hidden" class="bulk-inp-item-code" value="" autocomplete="off">' +
                '<input type="text" class="bulk-inp-sku w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" placeholder="Type SKU…" autocomplete="off">' +
                '<div class="bulk-ac-menu hidden absolute left-0 right-0 z-30 mt-0.5 max-h-52 min-w-[12rem] overflow-y-auto rounded-md border border-gray-300 bg-white text-xs shadow-lg" role="listbox"></div>' +
                '</div></td>' +
                '<td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0"><input type="text" class="bulk-inp-size w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" autocomplete="off"></td>' +
                '<td class="px-2 sm:px-3 py-2 align-middle w-32 min-w-0"><input type="text" class="bulk-inp-color w-full min-w-0 px-2.5 py-2 border border-gray-200 rounded-md text-sm leading-tight" autocomplete="off"></td>' +
                '<td class="px-2 sm:px-3 py-2 align-middle"><input type="number" min="0" class="bulk-inp-qty w-full max-w-[6rem] sm:max-w-none ml-auto block px-2.5 py-2 border border-gray-200 rounded-md text-sm tabular-nums text-right leading-tight" autocomplete="off"></td>' +
                (bulkEditTransferId > 0
                    ? '<td class="px-1 py-2 align-middle text-center w-14"><span class="text-gray-300 text-xs" aria-hidden="true">·</span></td>'
                    : '');
            tbody.appendChild(tr);
        }
        renumberBulkGrid();
    });
    renumberBulkGrid();

    if (bulkEditTransferId > 0) {
        const lineDelTbody = document.getElementById('bulk_grid_body');
        if (lineDelTbody) {
            lineDelTbody.addEventListener('click', function (ev) {
                const btn = ev.target.closest('.bulk-line-delete');
                if (!btn || !lineDelTbody.contains(btn)) {
                    return;
                }
                const lineId = parseInt(btn.getAttribute('data-line-id'), 10);
                if (!lineId) {
                    return;
                }
                ev.preventDefault();
                if (!confirm('Remove this line from the transfer? Stock at the source warehouse will be restored for this line.')) {
                    return;
                }
                const fd = new FormData();
                fd.append('transfer_id', String(bulkEditTransferId));
                fd.append('line_item_id', String(lineId));
                fetch(apiUrl('stock_transfer_delete_line'), { method: 'POST', body: fd, credentials: 'same-origin' })
                    .then(function (r) { return parseFetchJsonResponse(r); })
                    .then(function (data) {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            showTransferNotice(data.message || 'Could not remove this line.');
                        }
                    })
                    .catch(function () {
                        showTransferNotice('Could not remove this line. Check your connection and try again.');
                    });
            });
        }
    }

    function hideAllBulkAcMenus(exceptMenu) {
        document.querySelectorAll('#bulk_grid_body .bulk-ac-menu').forEach(function (el) {
            if (el !== exceptMenu) {
                el.classList.add('hidden');
                el.innerHTML = '';
            }
        });
    }

    function clearBulkRowImage(tr) {
        if (!tr) return;
        const img = tr.querySelector('.bulk-row-img');
        const ph = tr.querySelector('.bulk-row-img-ph');
        if (img) {
            img.removeAttribute('src');
            img.removeAttribute('alt');
            img.title = 'Click to enlarge';
            img.classList.add('hidden');
            img.onerror = null;
        }
        if (ph) ph.classList.remove('hidden');
    }

    function setBulkRowImage(tr, url, altText) {
        if (!tr) return;
        const img = tr.querySelector('.bulk-row-img');
        const ph = tr.querySelector('.bulk-row-img-ph');
        url = (url != null ? String(url).trim() : '');
        if (!img) return;
        if (!url) {
            clearBulkRowImage(tr);
            return;
        }
        img.onerror = function () {
            img.onerror = null;
            img.removeAttribute('src');
            img.classList.add('hidden');
            if (ph) ph.classList.remove('hidden');
        };
        img.alt = altText || '';
        img.title = 'Click to enlarge';
        img.src = url;
        img.classList.remove('hidden');
        if (ph) ph.classList.add('hidden');
    }

    function applyBulkSkuProduct(tr, product) {
        if (!tr || !product) return;
        const ic = tr.querySelector('.bulk-inp-item-code');
        const skuIn = tr.querySelector('.bulk-inp-sku');
        const sizeIn = tr.querySelector('.bulk-inp-size');
        const colorIn = tr.querySelector('.bulk-inp-color');
        if (ic) ic.value = product.item_code || '';
        if (skuIn) skuIn.value = product.sku || '';
        if (sizeIn) sizeIn.value = product.size != null ? String(product.size) : '';
        if (colorIn) colorIn.value = product.color != null ? String(product.color) : '';
        const imgAlt = (product.sku || product.item_code || '').trim() || 'Product';
        setBulkRowImage(tr, product.image || product.image_url || '', imgAlt);
        const menu = tr.querySelector('.bulk-ac-menu');
        if (menu) {
            menu.classList.add('hidden');
            menu.innerHTML = '';
        }
    }

    function tryBulkExactSku(tr, sku) {
        sku = (sku || '').trim();
        if (!sku) return Promise.resolve();
        return fetch(apiUrl('search_product', '&q=' + encodeURIComponent(sku) + '&exact=1'), { credentials: 'same-origin' })
            .then(function (r) { return parseFetchJsonResponse(r); })
            .then(function (data) {
                if (data.success && data.product) {
                    applyBulkSkuProduct(tr, data.product);
                }
            })
            .catch(function () {});
    }

    const bulkImageLightbox = document.getElementById('bulkImageLightbox');
    const bulkImageLightboxImg = document.getElementById('bulkImageLightboxImg');
    const bulkImageLightboxClose = document.getElementById('bulkImageLightboxClose');

    function openBulkImageLightbox(src, alt) {
        if (!src || !bulkImageLightbox || !bulkImageLightboxImg) return;
        bulkImageLightboxImg.src = src;
        bulkImageLightboxImg.alt = alt || '';
        bulkImageLightbox.classList.remove('hidden');
        bulkImageLightbox.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function closeBulkImageLightbox() {
        if (!bulkImageLightbox || !bulkImageLightboxImg) return;
        bulkImageLightbox.classList.add('hidden');
        bulkImageLightbox.classList.remove('flex');
        bulkImageLightboxImg.removeAttribute('src');
        bulkImageLightboxImg.alt = '';
        document.body.style.overflow = '';
    }

    if (bulkImageLightboxClose) {
        bulkImageLightboxClose.addEventListener('click', function (e) {
            e.stopPropagation();
            closeBulkImageLightbox();
        });
    }
    if (bulkImageLightbox) {
        bulkImageLightbox.addEventListener('click', function (e) {
            if (e.target === bulkImageLightbox) closeBulkImageLightbox();
        });
    }
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && bulkImageLightbox && !bulkImageLightbox.classList.contains('hidden')) {
            closeBulkImageLightbox();
        }
    });

    const gridBody = document.getElementById('bulk_grid_body');
    gridBody.addEventListener('click', function (e) {
        const thumb = e.target.closest('.bulk-row-img');
        if (!thumb || !gridBody.contains(thumb)) return;
        if (thumb.classList.contains('hidden')) return;
        const src = thumb.currentSrc || thumb.getAttribute('src');
        if (!src) return;
        e.preventDefault();
        e.stopPropagation();
        openBulkImageLightbox(src, thumb.alt || '');
    });

    gridBody.addEventListener('input', function (e) {
        const inp = e.target;
        if (!inp.classList || !inp.classList.contains('bulk-inp-sku')) return;
        const tr = inp.closest('tr');
        const menu = tr && tr.querySelector('.bulk-ac-menu');
        if (!tr || !menu) return;

        if (inp._bulkTimer) clearTimeout(inp._bulkTimer);
        const ic = tr.querySelector('.bulk-inp-item-code');
        if (ic) ic.value = '';
        clearBulkRowImage(tr);

        const q = inp.value.trim();
        if (q.length < 2) {
            menu.classList.add('hidden');
            menu.innerHTML = '';
            return;
        }

        inp._bulkReqSeq = (inp._bulkReqSeq || 0) + 1;
        const mySeq = inp._bulkReqSeq;
        inp._bulkTimer = setTimeout(function () {
            fetch(apiUrl('search_product', '&q=' + encodeURIComponent(q) + '&by=sku'), { credentials: 'same-origin' })
                .then(function (r) { return parseFetchJsonResponse(r); })
                .then(function (data) {
                    if (mySeq !== inp._bulkReqSeq) return;
                    if (!data.success || !data.products || data.products.length === 0) {
                        menu.innerHTML = '<div class="px-2 py-2 text-gray-500">No SKU matches</div>';
                        menu.classList.remove('hidden');
                        hideAllBulkAcMenus(menu);
                        return;
                    }
                    hideAllBulkAcMenus(menu);
                    menu.innerHTML = '';
                    data.products.forEach(function (p) {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        btn.className = 'bulk-ac-opt w-full text-left px-2 py-1.5 hover:bg-amber-50 border-b border-gray-100 last:border-0';
                        btn.setAttribute('role', 'option');
                        const sku = (p.sku || '').replace(/</g, '&lt;');
                        const icv = (p.item_code || '').replace(/</g, '&lt;');
                        const sz = (p.size != null && String(p.size) !== '') ? ', ' + String(p.size).replace(/</g, '&lt;') : '';
                        const cl = (p.color != null && String(p.color) !== '') ? ', ' + String(p.color).replace(/</g, '&lt;') : '';
                        btn.innerHTML = '<span class="font-semibold text-gray-900">' + sku + '</span>' +
                            '<span class="text-gray-600"> · ' + icv + sz + cl + '</span>';
                        btn.addEventListener('mousedown', function (ev) {
                            ev.preventDefault();
                            applyBulkSkuProduct(tr, p);
                        });
                        menu.appendChild(btn);
                    });
                    menu.classList.remove('hidden');
                })
                .catch(function () {
                    if (mySeq !== inp._bulkReqSeq) return;
                    menu.innerHTML = '<div class="px-2 py-2 text-red-600">Search failed</div>';
                    menu.classList.remove('hidden');
                    hideAllBulkAcMenus(menu);
                });
        }, 280);
    });

    gridBody.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') hideAllBulkAcMenus(null);
    });

    gridBody.addEventListener('focusout', function (e) {
        const inp = e.target;
        if (!inp.classList || !inp.classList.contains('bulk-inp-sku')) return;
        const tr = inp.closest('tr');
        setTimeout(function () {
            if (!tr) return;
            const menu = tr.querySelector('.bulk-ac-menu');
            if (menu && document.activeElement && menu.contains(document.activeElement)) return;
            if (menu) {
                menu.classList.add('hidden');
                menu.innerHTML = '';
            }
            const ic = tr.querySelector('.bulk-inp-item-code');
            if (ic && !ic.value.trim()) {
                tryBulkExactSku(tr, inp.value);
            }
        }, 180);
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('#bulk_grid_body .bulk-ac-menu') || e.target.closest('.bulk-inp-sku')) return;
        hideAllBulkAcMenus(null);
    });

    function collectGridRows() {
        const rows = [];
        document.querySelectorAll('#bulk_grid_body tr.bulk-grid-row').forEach(function (tr) {
            const item = tr.querySelector('.bulk-inp-item-code')?.value.trim() || '';
            const size = tr.querySelector('.bulk-inp-size')?.value.trim() || '';
            const color = tr.querySelector('.bulk-inp-color')?.value.trim() || '';
            const qty = parseInt(tr.querySelector('.bulk-inp-qty')?.value || '0', 10);
            if (item && qty > 0) {
                rows.push({ item_code: item, size: size, color: color, quantity: qty });
            }
        });
        return rows;
    }

    function validateBulkStockPreview(rows, fromWarehouse, transferId) {
        const fd = new FormData();
        fd.append('from_warehouse', String(fromWarehouse || ''));
        fd.append('transfer_id', String(transferId || 0));
        fd.append('bulk_mode', 'grid');
        fd.append('rows_json', JSON.stringify(rows || []));
        fd.append('bulk_rows_json', JSON.stringify(rows || []));
        return fetch(apiUrl('validate_transfer_stock_bulk_preview'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        }).then(function (r) { return parseFetchJsonResponse(r); });
    }

    function validateBulkUploadPreview(fromWarehouse, transferId, file) {
        const fd = new FormData();
        fd.append('from_warehouse', String(fromWarehouse || ''));
        fd.append('transfer_id', String(transferId || 0));
        fd.append('bulk_mode', 'upload');
        fd.append('bulk_file', file);
        return fetch(apiUrl('validate_transfer_stock_bulk_preview'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        }).then(function (r) { return parseFetchJsonResponse(r); });
    }

    document.getElementById('bulkTransferForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (!fromSel.value || !toSel.value) {
            showTransferNotice('Please select source and destination warehouses.');
            return;
        }
        if (fromSel.value === toSel.value) {
            showTransferNotice('Source and destination warehouses must be different.');
            return;
        }

        const form = document.getElementById('bulkTransferForm');
        const fd = new FormData(form);

        if (bulkMode.value === 'grid') {
            const gridData = collectGridRows();
            if (gridData.length === 0) {
                showTransferNotice('Add at least one row with a resolved product (search SKU and pick a match, or type an exact SKU) and quantity.');
                return;
            }

            try {
                const preview = await validateBulkStockPreview(gridData, fromSel.value, bulkEditTransferId);
                if (!preview || preview.success !== true) {
                    showBulkTransferValidationError(preview);
                    return;
                }
            } catch (err) {
                showFetchErrorNotice(err, 'Validation Error');
                return;
            }

            document.getElementById('bulk_rows_json').value = JSON.stringify(gridData);
            fd.set('bulk_rows_json', JSON.stringify(gridData));
            if (bulkFile) {
                bulkFile.value = '';
                updateBulkFileSelectionUi();
            }
        } else {
            document.getElementById('bulk_rows_json').value = '[]';
            fd.set('bulk_rows_json', '[]');
            fd.set('bulk_mode', 'upload');
            if (!bulkFile.files || !bulkFile.files.length) {
                showTransferNotice('Please choose a spreadsheet file, or switch to the grid tab.');
                return;
            }

            try {
                const preview = await validateBulkUploadPreview(fromSel.value, bulkEditTransferId, bulkFile.files[0]);
                if (!preview || preview.success !== true) {
                    showBulkTransferValidationError(preview);
                    return;
                }
            } catch (err) {
                showFetchErrorNotice(err, 'Validation Error');
                return;
            }
        }

        showBulkTransferProcessingOverlay();

        fetch(apiUrl('process_transfer_stock_bulk'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
            .then(function (r) { return parseFetchJsonResponse(r); })
            .then(function (data) {
                if (data && data.redirect && data.success === false) {
                    hideBulkTransferProcessingOverlay();
                    window.location.href = data.redirect;
                    return;
                }
                if (data.success) {
                    window.location.href = '?page=products&action=stock_transfer';
                    return;
                }
                hideBulkTransferProcessingOverlay();
                showBulkTransferValidationError(data);
            })
            .catch(function (err) {
                hideBulkTransferProcessingOverlay();
                showFetchErrorNotice(err, 'Stock Transfer Validation');
            });
    });
})();
</script>
