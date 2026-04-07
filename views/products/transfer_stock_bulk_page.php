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
                    <?php echo $isBulkEdit ? 'Edit' : 'Bulk'; ?> <span class="text-amber-800">stock transfer</span>
                </h1>
                <p class="mt-2 sm:mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    <?php echo $isBulkEdit
                        ? 'Update dispatch schedule, route, line items, and transport details, then save.'
                        : 'Set dispatch dates and route, add line items in the grid (or upload a file), then add transport details and submit.'; ?>
                </p>
            </div>
            <div class="flex shrink-0 flex-col sm:flex-row sm:items-center gap-2.5 sm:gap-3 lg:pl-2 xl:pl-6 w-full sm:w-auto">
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

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
(function () {
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

    function fetchNextTransferOrderNo(fromW, toW) {
        return fetch(apiUrl('get_transfer_order_no', '&from_warehouse=' + encodeURIComponent(fromW) + '&to_warehouse=' + encodeURIComponent(toW)), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
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
                .then(function (r) { return r.json(); })
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
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.success) {
                            window.location.reload();
                        } else {
                            alert(data.message || 'Could not remove this line.');
                        }
                    })
                    .catch(function () {
                        alert('Could not remove this line. Check your connection and try again.');
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
            .then(function (r) { return r.json(); })
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
                .then(function (r) { return r.json(); })
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

    document.getElementById('bulkTransferForm').addEventListener('submit', function (e) {
        e.preventDefault();
        if (!fromSel.value || !toSel.value) {
            alert('Please select source and destination warehouses.');
            return;
        }
        if (fromSel.value === toSel.value) {
            alert('Source and destination warehouses must be different.');
            return;
        }

        const form = document.getElementById('bulkTransferForm');
        const fd = new FormData(form);

        if (bulkMode.value === 'grid') {
            const gridData = collectGridRows();
            if (gridData.length === 0) {
                alert('Add at least one row with a resolved product (search SKU and pick a match, or type an exact SKU) and quantity.');
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
            if (!bulkFile.files || !bulkFile.files.length) {
                alert('Please choose a spreadsheet file, or switch to the grid tab.');
                return;
            }
        }

        fetch(apiUrl('process_transfer_stock_bulk'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: fd
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success) {
                    alert(data.message || (isBulkEdit ? 'Stock transfer updated successfully.' : 'Stock transfer created successfully.'));
                    window.location.href = '?page=products&action=stock_transfer';
                } else {
                    alert(data.message || 'Could not create transfer');
                }
            })
            .catch(function (err) {
                alert('Request failed: ' + err.message);
            });
    });
})();
</script>
