<?php
$warehouses = $warehouses ?? [];
$userWarehouseId = (int)($userWarehouseId ?? 0);
$isAdmin = (bool)($isAdmin ?? false);

// Find warehouse name for default selection
$selectedWarehouseName = 'Selected Store';
foreach ($warehouses as $wh) {
    if ((int)$wh['id'] === $userWarehouseId) {
        $selectedWarehouseName = $wh['address_title'];
        break;
    }
}
?>
<div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-amber-50/20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">

        <!-- Title & Page Header -->
        <div class="relative overflow-hidden rounded-2xl border border-amber-200/50 bg-gradient-to-br from-amber-50/80 via-white to-slate-50/50 shadow-sm ring-1 ring-amber-900/[0.04] mb-6 sm:mb-8">
            <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 -left-12 h-48 w-48 rounded-full bg-sky-200/10 blur-2xl" aria-hidden="true"></div>
            <div class="relative px-5 py-6 sm:px-8 sm:py-7 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5 sm:gap-8">
                <div class="min-w-0 max-w-3xl">
                    <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-3">
                        <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                            <i class="fas fa-boxes-stacked text-[11px]" aria-hidden="true"></i>
                        </span>
                        <span>Inventory Management · Bulk Stock Adjustment</span>
                    </div>
                    <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                        Bulk Stock <span class="text-amber-800">Adjustment</span>
                    </h1>
                    <p class="mt-2 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                        Search and select SKUs to update physical inventory stock levels across your warehouse. Set adjustment type (Increase / Decrease) and reasons for each line item.
                    </p>
                </div>
                <div class="flex shrink-0 flex-col sm:flex-row sm:items-center gap-2.5 w-full sm:w-auto">
                    <a href="?page=products&action=transfer_stock_bulk" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-900 text-sm font-semibold shadow-sm hover:bg-amber-100 transition whitespace-nowrap">
                        <i class="fas fa-exchange-alt text-xs text-amber-700" aria-hidden="true"></i>
                        Bulk Stock Transfer
                    </a>
                    <a href="?page=products&action=stock_transfer" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 transition whitespace-nowrap">
                        <i class="fas fa-history text-xs text-gray-500" aria-hidden="true"></i>
                        Transfer History
                    </a>
                </div>
            </div>
        </div>

        <!-- Top Control Bar (Warehouse, Adjustment Type, Reason, Copy Button) -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden mb-6 sm:mb-8">
            <div class="px-5 py-3.5 border-b border-gray-100 bg-slate-50/90 flex items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                        <i class="fas fa-sliders-h text-xs" aria-hidden="true"></i>
                    </span>
                    <h2 class="text-sm font-semibold text-gray-900">Top-Level Adjustment Controls</h2>
                </div>
                <span class="text-xs text-gray-500 hidden sm:inline">Set store &amp; default settings to apply across rows</span>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-12 gap-5 items-start">
                    
                    <!-- Warehouse Dropdown -->
                    <div class="lg:col-span-4 flex flex-col">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5 flex items-center justify-between">
                            <span>Warehouse / Store <span class="text-red-500">*</span></span>
                            <?php if (!$isAdmin): ?>
                                <span class="inline-flex items-center gap-1 text-[11px] text-amber-700 font-medium">
                                    <i class="fas fa-lock text-[10px]"></i> Store Locked
                                </span>
                            <?php endif; ?>
                        </label>
                        <?php if ($isAdmin): ?>
                            <select id="top_warehouse_id" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo (int)$wh['id']; ?>" <?php echo ((int)$wh['id'] === $userWarehouseId ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($wh['address_title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <select id="top_warehouse_id" disabled class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-600 bg-gray-100/90 shadow-sm cursor-not-allowed">
                                <?php foreach ($warehouses as $wh): ?>
                                    <option value="<?php echo (int)$wh['id']; ?>" <?php echo ((int)$wh['id'] === $userWarehouseId ? 'selected' : ''); ?>>
                                        <?php echo htmlspecialchars($wh['address_title']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="top_warehouse_id_hidden" value="<?php echo $userWarehouseId; ?>">
                        <?php endif; ?>
                        <p class="text-[11px] text-gray-400 mt-1">Stock adjustments will be logged against this warehouse.</p>
                    </div>

                    <!-- Default Adjustment Type -->
                    <div class="lg:col-span-3 flex flex-col">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Default Adjustment Type</label>
                        <select id="top_adjustment_type" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="IN" selected>+ Increase (Stock In)</option>
                            <option value="OUT">- Decrease (Stock Out)</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Default for new &amp; copied line items.</p>
                    </div>

                    <!-- Default Update Local Stock -->
                    <div class="lg:col-span-2 flex flex-col">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Update Local Stock?</label>
                        <select id="top_update_local_stock" class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-medium text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="0" selected>No (Default)</option>
                            <option value="1">Yes (Sync Storefront)</option>
                        </select>
                        <p class="text-[11px] text-gray-400 mt-1">Default is No.</p>
                    </div>

                    <!-- Default Reason -->
                    <div class="lg:col-span-3 flex flex-col">
                        <label class="block text-xs font-semibold text-gray-700 mb-1.5">Default Reason</label>
                        <div class="flex gap-2">
                            <textarea id="top_reason" rows="1" placeholder="e.g. Physical audit / Damaged count" class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition resize-none"></textarea>
                            <button type="button" id="btn_copy_defaults" class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 bg-amber-800 hover:bg-amber-900 text-white text-xs font-semibold rounded-xl shadow-sm hover:shadow transition shrink-0 whitespace-nowrap" title="Copy adjustment type, local stock flag, and reason to all rows">
                                <i class="fas fa-copy text-[11px]"></i>
                                Apply All
                            </button>
                        </div>
                        <p class="text-[11px] text-gray-400 mt-1">Copy defaults to all rows.</p>
                    </div>

                </div>
            </div>
        </div>

        <!-- Line Items Grid Card -->
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-slate-50/90 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-800 shadow-sm">
                        <i class="fas fa-list-ol text-xs" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">Line Items Grid</h2>
                        <p class="text-xs text-gray-500">Type a SKU in any row to search and select products. Current stock will load automatically.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span id="grid_active_rows_count" class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-800 border border-amber-200/60">
                        0 line items
                    </span>
                </div>
            </div>

            <!-- Table Container -->
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-200 bg-slate-100/70 text-[11px] font-bold tracking-wider text-gray-600 uppercase">
                            <th class="py-3 px-3 w-12 text-center">#</th>
                            <th class="py-3 px-3 w-16 text-center">Image</th>
                            <th class="py-3 px-3 w-48 sm:w-56">SKU <span class="normal-case font-normal text-gray-400">(search)</span></th>
                            <th class="py-3 px-3 w-24">Size</th>
                            <th class="py-3 px-3 w-28">Color</th>
                            <th class="py-3 px-3 w-28 text-right">Current Qty</th>
                            <th class="py-3 px-3 w-36">Adjustment Type</th>
                            <th class="py-3 px-3 w-28 text-right">New Qty <span class="text-red-500">*</span></th>
                            <th class="py-3 px-3 w-36 text-center">Update Local Stock?</th>
                            <th class="py-3 px-3 min-w-[10rem]">Reason</th>
                            <th class="py-3 px-3 w-12 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="adj_grid_body" class="divide-y divide-gray-100 text-sm">
                        <!-- 10 default blank rows will be rendered via JS on boot -->
                    </tbody>
                </table>
            </div>

            <!-- Footer Buttons (Add Row & Submit) -->
            <div class="p-4 sm:p-5 border-t border-gray-100 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 w-full sm:w-auto">
                    <button type="button" id="btn_add_row" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-xs font-semibold shadow-sm hover:bg-gray-50 transition w-1/2 sm:w-auto">
                        <i class="fas fa-plus text-[10px] text-amber-700"></i>
                        Add Row
                    </button>
                    <button type="button" id="btn_add_5_rows" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-slate-100 text-gray-700 text-xs font-semibold hover:bg-slate-200 transition w-1/2 sm:w-auto">
                        <i class="fas fa-layer-group text-[10px] text-gray-500"></i>
                        +5 Rows
                    </button>
                </div>
                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <button type="button" id="btn_reset_grid" class="px-4 py-2.5 text-xs font-semibold text-gray-500 hover:text-gray-800 transition">
                        Clear Grid
                    </button>
                    <button type="button" id="btn_submit_adjustment" class="inline-flex items-center justify-center gap-2 px-7 py-3 rounded-xl bg-gradient-to-r from-amber-700 to-amber-800 text-white text-sm font-bold shadow-md hover:from-amber-800 hover:to-amber-900 focus:outline-none focus:ring-2 focus:ring-amber-500/50 transition w-full sm:w-auto">
                        <i class="fas fa-check-circle text-xs"></i>
                        Submit Adjustments
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Image Lightbox Modal -->
<div id="imageLightboxModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true">
    <div class="relative max-w-lg w-full bg-white rounded-2xl overflow-hidden shadow-2xl">
        <div class="p-3 border-b border-gray-100 flex items-center justify-between">
            <h3 id="lightboxTitle" class="text-xs font-semibold text-gray-800 truncate">Product Image</h3>
            <button type="button" id="closeLightbox" class="text-gray-400 hover:text-gray-700 text-lg px-2 py-0.5">&times;</button>
        </div>
        <div class="p-4 flex items-center justify-center bg-slate-50 min-h-[250px]">
            <img id="lightboxImg" src="" alt="Product Image" class="max-h-[70vh] max-w-full object-contain rounded-lg">
        </div>
    </div>
</div>

<!-- Validation Error Modal -->
<div id="validationModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true">
    <div class="relative max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-red-100">
        <div class="px-6 py-4 bg-red-50 border-b border-red-100 flex items-center gap-3">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-100 text-red-700 font-bold shrink-0">
                <i class="fas fa-exclamation-triangle text-sm"></i>
            </span>
            <div>
                <h3 class="text-base font-bold text-red-900">Validation Required</h3>
                <p class="text-xs text-red-700">Please review the issues before submitting</p>
            </div>
        </div>
        <div class="p-6 max-h-[60vh] overflow-y-auto">
            <ul id="validation_issues_list" class="space-y-2 text-xs text-gray-700">
                <!-- Error bullets -->
            </ul>
        </div>
        <div class="px-6 py-3.5 bg-slate-50 border-t border-gray-100 text-right">
            <button type="button" id="closeValidationModal" class="px-5 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-xl transition">
                Got it
            </button>
        </div>
    </div>
</div>

<!-- Confirmation Modal Before Execution -->
<div id="confirmationModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/60 backdrop-blur-sm p-4" role="dialog" aria-modal="true">
    <div class="relative max-w-3xl w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-amber-100 max-h-[90vh] flex flex-col">
        
        <div class="px-6 py-5 bg-gradient-to-r from-amber-950 to-amber-900 text-white flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-800/80 text-amber-200 border border-amber-700">
                    <i class="fas fa-clipboard-check text-base"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold">Confirm Bulk Stock Adjustment</h3>
                    <p class="text-xs text-amber-200/90">Review adjustment summary before updating inventory</p>
                </div>
            </div>
            <button type="button" id="closeConfirmModal" class="text-amber-200 hover:text-white text-2xl font-bold leading-none">&times;</button>
        </div>

        <div class="p-6 overflow-y-auto space-y-5 flex-1">
            <!-- Metadata summary badges -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="p-3 rounded-xl bg-slate-50 border border-gray-200/80">
                    <span class="text-[11px] font-semibold text-gray-500 uppercase block">Selected Store</span>
                    <span id="confirm_wh_name" class="text-sm font-bold text-gray-900 mt-0.5 block truncate">—</span>
                </div>
                <div class="p-3 rounded-xl bg-emerald-50/80 border border-emerald-200/80">
                    <span class="text-[11px] font-semibold text-emerald-800 uppercase block">Total Increases (+)</span>
                    <span id="confirm_total_increase" class="text-sm font-bold text-emerald-900 mt-0.5 block">0 items</span>
                </div>
                <div class="p-3 rounded-xl bg-rose-50/80 border border-rose-200/80">
                    <span class="text-[11px] font-semibold text-rose-800 uppercase block">Total Decreases (-)</span>
                    <span id="confirm_total_decrease" class="text-sm font-bold text-rose-900 mt-0.5 block">0 items</span>
                </div>
            </div>

            <!-- Preview items list table -->
            <div>
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Adjustment Line Items Preview</h4>
                <div class="border border-gray-200 rounded-xl overflow-x-auto max-h-[300px]">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-100 text-gray-600 font-semibold uppercase text-[10px] border-b border-gray-200">
                                <th class="py-2.5 px-3">#</th>
                                <th class="py-2.5 px-3">SKU</th>
                                <th class="py-2.5 px-3">Variant</th>
                                <th class="py-2.5 px-3 text-right">Current Qty</th>
                                <th class="py-2.5 px-3 text-center">Adjustment</th>
                                <th class="py-2.5 px-3 text-right">Qty</th>
                                <th class="py-2.5 px-3 text-right">New Est. Qty</th>
                                <th class="py-2.5 px-3 text-center">Update Local Stock?</th>
                                <th class="py-2.5 px-3">Reason</th>
                            </tr>
                        </thead>
                        <tbody id="confirm_preview_tbody" class="divide-y divide-gray-100 font-medium">
                            <!-- Preview rows dynamically inserted -->
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed">
                <i class="fas fa-info-circle text-amber-700 mr-1"></i>
                <strong>Note:</strong> Upon execution, stock movements will be immediately recorded in the ledger and storefront sync will trigger automatically.
            </div>
        </div>

        <div class="px-6 py-4 bg-slate-50 border-t border-gray-100 flex items-center justify-end gap-3 shrink-0">
            <button type="button" id="btn_cancel_confirm" class="px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-xs font-semibold hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="button" id="btn_execute_adjustment" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-amber-800 hover:bg-amber-900 text-white text-xs font-bold shadow-md transition">
                <i class="fas fa-check"></i>
                Confirm &amp; Execute
            </button>
        </div>

    </div>
</div>

<!-- Success / Result Modal -->
<div id="resultModal" class="fixed inset-0 z-[120] hidden items-center justify-center bg-black/60 p-4" role="dialog" aria-modal="true">
    <div class="relative max-w-md w-full bg-white rounded-2xl shadow-2xl overflow-hidden border border-emerald-100">
        <div class="p-6 text-center space-y-4">
            <div id="result_icon_wrap" class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 text-2xl">
                <i id="result_icon" class="fas fa-check"></i>
            </div>
            <h3 id="result_title" class="text-lg font-bold text-gray-900">Success!</h3>
            <p id="result_message" class="text-sm text-gray-600 leading-relaxed"></p>
            <div class="pt-2">
                <button type="button" id="closeResultModal" class="w-full px-5 py-2.5 bg-gray-900 hover:bg-black text-white text-xs font-semibold rounded-xl transition">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const INITIAL_ROW_COUNT = 10;
    let rowCounter = 0;

    // DOM Elements
    const gridBody = document.getElementById('adj_grid_body');
    const topWhSel = document.getElementById('top_warehouse_id');
    const topWhHidden = document.getElementById('top_warehouse_id_hidden');
    const topAdjSel = document.getElementById('top_adjustment_type');
    const topUpdateLocalSel = document.getElementById('top_update_local_stock');
    const topReasonInp = document.getElementById('top_reason');
    const btnCopyDefaults = document.getElementById('btn_copy_defaults');
    const btnAddRow = document.getElementById('btn_add_row');
    const btnAdd5Rows = document.getElementById('btn_add_5_rows');
    const btnResetGrid = document.getElementById('btn_reset_grid');
    const btnSubmit = document.getElementById('btn_submit_adjustment');
    const activeRowsBadge = document.getElementById('grid_active_rows_count');

    // Modals
    const validationModal = document.getElementById('validationModal');
    const validationList = document.getElementById('validation_issues_list');
    const closeValidationBtn = document.getElementById('closeValidationModal');

    const confirmationModal = document.getElementById('confirmationModal');
    const confirmWhName = document.getElementById('confirm_wh_name');
    const confirmTotalIncrease = document.getElementById('confirm_total_increase');
    const confirmTotalDecrease = document.getElementById('confirm_total_decrease');
    const confirmPreviewTbody = document.getElementById('confirm_preview_tbody');
    const btnCancelConfirm = document.getElementById('btn_cancel_confirm');
    const closeConfirmModalBtn = document.getElementById('closeConfirmModal');
    const btnExecuteAdjustment = document.getElementById('btn_execute_adjustment');

    const resultModal = document.getElementById('resultModal');
    const resultIconWrap = document.getElementById('result_icon_wrap');
    const resultIcon = document.getElementById('result_icon');
    const resultTitle = document.getElementById('result_title');
    const resultMsg = document.getElementById('result_message');
    const closeResultModalBtn = document.getElementById('closeResultModal');

    const lightboxModal = document.getElementById('imageLightboxModal');
    const lightboxImg = document.getElementById('lightboxImg');
    const lightboxTitle = document.getElementById('lightboxTitle');
    const closeLightboxBtn = document.getElementById('closeLightbox');

    function getSelectedWarehouseId() {
        if (topWhSel && !topWhSel.disabled) {
            return parseInt(topWhSel.value, 10) || 0;
        }
        if (topWhHidden) {
            return parseInt(topWhHidden.value, 10) || 0;
        }
        if (topWhSel) {
            return parseInt(topWhSel.value, 10) || 0;
        }
        return 0;
    }

    function getSelectedWarehouseTitle() {
        if (topWhSel) {
            const opt = topWhSel.options[topWhSel.selectedIndex];
            if (opt) return opt.text.trim();
        }
        return 'Selected Warehouse';
    }

    // Escape HTML for security
    function escapeHtml(str) {
        if (str == null) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Build Single Row HTML
    function createRowElement(index) {
        rowCounter++;
        const tr = document.createElement('tr');
        tr.className = 'adj-grid-row hover:bg-slate-50/80 transition group';
        tr.dataset.rowIdx = rowCounter;

        const defaultAdjType = topAdjSel ? topAdjSel.value : 'IN';
        const defaultUpdateLocal = topUpdateLocalSel ? topUpdateLocalSel.value : '0';
        const defaultReason = topReasonInp ? topReasonInp.value.trim() : '';

        tr.innerHTML = `
            <td class="py-2.5 px-3 text-center align-middle font-mono text-xs text-gray-400 font-semibold row-num-label">${index + 1}</td>
            <td class="py-2.5 px-3 text-center align-middle">
                <div class="relative w-10 h-10 mx-auto rounded-lg overflow-hidden border border-gray-200 bg-gray-50 flex items-center justify-center shrink-0">
                    <img class="bulk-row-img hidden w-full h-full object-cover cursor-pointer hover:opacity-90 transition" src="" alt="" />
                    <span class="bulk-row-img-ph text-gray-300 text-xs"><i class="fas fa-image"></i></span>
                </div>
            </td>
            <td class="py-2.5 px-3 align-middle relative">
                <input type="hidden" class="bulk-inp-product-id" value="" />
                <input type="hidden" class="bulk-inp-item-code" value="" />
                <input type="text" class="bulk-inp-sku w-full px-3 py-2 border border-gray-200 rounded-lg text-xs font-mono text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" placeholder="Type SKU..." autocomplete="off" />
                <div class="bulk-ac-menu hidden absolute left-3 right-3 top-full z-30 mt-1 max-h-60 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-xl py-1"></div>
            </td>
            <td class="py-2.5 px-3 align-middle">
                <span class="bulk-inp-size font-medium text-xs text-gray-600">—</span>
            </td>
            <td class="py-2.5 px-3 align-middle">
                <span class="bulk-inp-color font-medium text-xs text-gray-600">—</span>
            </td>
            <td class="py-2.5 px-3 align-middle text-right">
                <span class="bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-gray-700 bg-gray-100 border border-gray-200">
                    —
                </span>
            </td>
            <td class="py-2.5 px-3 align-middle">
                <select class="bulk-inp-adj-type w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    <option value="IN" ${defaultAdjType === 'IN' ? 'selected' : ''}>+ Increase</option>
                    <option value="OUT" ${defaultAdjType === 'OUT' ? 'selected' : ''}>- Decrease</option>
                </select>
            </td>
            <td class="py-2.5 px-3 align-middle text-right">
                <input type="number" min="1" step="1" class="bulk-inp-qty w-full px-2.5 py-1.5 border border-gray-200 rounded-lg text-xs text-right font-bold text-gray-900 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" placeholder="Qty" />
            </td>
            <td class="py-2.5 px-3 align-middle text-center">
                <select class="bulk-inp-update-local-stock w-full px-2 py-1.5 border border-gray-200 rounded-lg text-xs font-semibold text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    <option value="0" ${defaultUpdateLocal === '1' ? '' : 'selected'}>No</option>
                    <option value="1" ${defaultUpdateLocal === '1' ? 'selected' : ''}>Yes</option>
                </select>
            </td>
            <td class="py-2.5 px-3 align-middle">
                <input type="text" class="bulk-inp-reason w-full px-3 py-1.5 border border-gray-200 rounded-lg text-xs text-gray-800 bg-white focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" value="${escapeHtml(defaultReason)}" placeholder="Reason" />
            </td>
            <td class="py-2.5 px-3 text-center align-middle">
                <button type="button" class="bulk-btn-del text-gray-300 hover:text-red-600 p-1.5 transition rounded-lg hover:bg-red-50" title="Remove row">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </td>
        `;

        attachRowEvents(tr);
        return tr;
    }

    // Attach Row Input & Search Events
    function attachRowEvents(tr) {
        const skuInput = tr.querySelector('.bulk-inp-sku');
        const acMenu = tr.querySelector('.bulk-ac-menu');
        const delBtn = tr.querySelector('.bulk-btn-del');
        const imgEl = tr.querySelector('.bulk-row-img');
        const qtyInp = tr.querySelector('.bulk-inp-qty');
        const adjTypeSel = tr.querySelector('.bulk-inp-adj-type');

        let debounceTimer = null;

        // SKU input autocomplete
        if (skuInput) {
            skuInput.addEventListener('input', function () {
                const q = this.value.trim();
                clearTimeout(debounceTimer);
                
                // Clear state if input erased
                if (!q) {
                    clearRowProduct(tr);
                    if (acMenu) {
                        acMenu.classList.add('hidden');
                        acMenu.innerHTML = '';
                    }
                    updateActiveRowsBadge();
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetchSkuSearch(q, tr, acMenu);
                }, 220);
            });

            skuInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const q = this.value.trim();
                    if (q) {
                        fetchExactSkuMatch(q, tr, acMenu);
                    }
                }
            });
        }

        // Qty or Adj Type input triggers active count update
        if (qtyInp) {
            qtyInp.addEventListener('input', updateActiveRowsBadge);
        }
        if (adjTypeSel) {
            adjTypeSel.addEventListener('change', updateActiveRowsBadge);
        }

        // Delete Row
        if (delBtn) {
            delBtn.addEventListener('click', function () {
                tr.remove();
                renumberRows();
                updateActiveRowsBadge();
            });
        }

        // Thumbnail Click -> Open Lightbox
        if (imgEl) {
            imgEl.addEventListener('click', function () {
                if (this.src) {
                    lightboxImg.src = this.src;
                    const sku = skuInput ? skuInput.value.trim() : '';
                    lightboxTitle.textContent = sku ? `SKU: ${sku}` : 'Product Image';
                    lightboxModal.classList.remove('hidden');
                    lightboxModal.classList.add('flex');
                }
            });
        }
    }

    // Clear Product State on a Row
    function clearRowProduct(tr) {
        tr.querySelector('.bulk-inp-product-id').value = '';
        tr.querySelector('.bulk-inp-item-code').value = '';
        tr.querySelector('.bulk-inp-size').textContent = '—';
        tr.querySelector('.bulk-inp-color').textContent = '—';
        
        const qtyBadge = tr.querySelector('.bulk-row-current-qty');
        qtyBadge.textContent = '—';
        qtyBadge.className = 'bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-gray-700 bg-gray-100 border border-gray-200';
        delete qtyBadge.dataset.currentQty;

        const img = tr.querySelector('.bulk-row-img');
        const ph = tr.querySelector('.bulk-row-img-ph');
        img.src = '';
        img.classList.add('hidden');
        if (ph) ph.classList.remove('hidden');

        tr.classList.remove('bg-red-50/40', 'border-red-300');
    }

    // Fetch SKU Autocomplete Options
    function fetchSkuSearch(query, tr, acMenu) {
        if (!acMenu) return;
        const whId = getSelectedWarehouseId();
        const url = `?page=products&action=search_product&q=${encodeURIComponent(query)}&by=sku&warehouse_id=${whId}`;

        fetch(url, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (!data.success || !Array.isArray(data.products) || data.products.length === 0) {
                    acMenu.innerHTML = `<div class="px-3 py-2 text-xs text-gray-400 font-medium">No matching SKU found</div>`;
                    acMenu.classList.remove('hidden');
                    return;
                }

                let html = '';
                data.products.slice(0, 10).forEach(p => {
                    const sku = escapeHtml(p.sku || '');
                    const ic = escapeHtml(p.item_code || '');
                    const size = escapeHtml(p.size || 'N/A');
                    const color = escapeHtml(p.color || 'N/A');
                    const curQty = p.current_qty != null ? parseFloat(p.current_qty) : 0;
                    const jsonStr = escapeHtml(JSON.stringify(p));

                    html += `
                        <button type="button" class="ac-item-btn w-full text-left px-3 py-2 hover:bg-amber-50 transition border-b border-gray-100 last:border-b-0 flex items-center justify-between gap-2" data-product="${jsonStr}">
                            <div class="min-w-0">
                                <div class="font-mono text-xs font-bold text-gray-900 truncate">${sku}</div>
                                <div class="text-[11px] text-gray-500 truncate">${ic} · Size: ${size}, Color: ${color}</div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-800 shrink-0">
                                Stock: ${curQty}
                            </span>
                        </button>
                    `;
                });

                acMenu.innerHTML = html;
                acMenu.classList.remove('hidden');

                // Attach click handler for AC buttons
                acMenu.querySelectorAll('.ac-item-btn').forEach(btn => {
                    btn.addEventListener('click', function (e) {
                        e.preventDefault();
                        const prodData = JSON.parse(this.dataset.product);
                        applyProductToRow(tr, prodData);
                        acMenu.classList.add('hidden');
                        acMenu.innerHTML = '';
                    });
                });
            })
            .catch(() => {
                acMenu.classList.add('hidden');
            });
    }

    // Exact SKU fetch on Enter or Blur
    function fetchExactSkuMatch(query, tr, acMenu) {
        const whId = getSelectedWarehouseId();
        const url = `?page=products&action=search_product&q=${encodeURIComponent(query)}&exact=1&warehouse_id=${whId}`;

        fetch(url, { credentials: 'same-origin' })
            .then(res => res.json())
            .then(data => {
                if (data.success && data.product) {
                    applyProductToRow(tr, data.product);
                    if (acMenu) {
                        acMenu.classList.add('hidden');
                        acMenu.innerHTML = '';
                    }
                }
            });
    }

    // Apply Product Data to Row
    function applyProductToRow(tr, p) {
        if (!tr || !p) return;

        tr.querySelector('.bulk-inp-product-id').value = p.id || '';
        tr.querySelector('.bulk-inp-item-code').value = p.item_code || '';
        tr.querySelector('.bulk-inp-sku').value = p.sku || '';
        tr.querySelector('.bulk-inp-size').textContent = p.size != null && String(p.size).trim() !== '' ? p.size : '—';
        tr.querySelector('.bulk-inp-color').textContent = p.color != null && String(p.color).trim() !== '' ? p.color : '—';

        // Set Current Stock
        const curQty = p.current_qty != null ? parseFloat(p.current_qty) : 0;
        const qtyBadge = tr.querySelector('.bulk-row-current-qty');
        qtyBadge.textContent = curQty;
        qtyBadge.dataset.currentQty = curQty;
        
        if (curQty > 0) {
            qtyBadge.className = 'bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200';
        } else {
            qtyBadge.className = 'bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-rose-800 bg-rose-50 border border-rose-200';
        }

        // Set Image
        const img = tr.querySelector('.bulk-row-img');
        const ph = tr.querySelector('.bulk-row-img-ph');
        if (p.image && String(p.image).trim()) {
            img.src = String(p.image).trim();
            img.classList.remove('hidden');
            if (ph) ph.classList.add('hidden');
        } else {
            img.src = '';
            img.classList.add('hidden');
            if (ph) ph.classList.remove('hidden');
        }

        tr.classList.remove('bg-red-50/40', 'border-red-300');
        updateActiveRowsBadge();
    }

    // Renumber Table Rows
    function renumberRows() {
        const rows = gridBody.querySelectorAll('.adj-grid-row');
        rows.forEach((tr, i) => {
            const numLabel = tr.querySelector('.row-num-label');
            if (numLabel) numLabel.textContent = i + 1;
        });
    }

    // Update Active Rows Counter Badge
    function updateActiveRowsBadge() {
        const activeRows = getActiveGridRows();
        activeRowsBadge.textContent = `${activeRows.length} active row${activeRows.length === 1 ? '' : 's'}`;
    }

    // Get Active Grid Rows (where SKU or product ID is populated)
    function getActiveGridRows() {
        const rows = gridBody.querySelectorAll('.adj-grid-row');
        const active = [];
        rows.forEach(tr => {
            const sku = tr.querySelector('.bulk-inp-sku').value.trim();
            const pId = tr.querySelector('.bulk-inp-product-id').value.trim();
            if (sku || pId) {
                active.push(tr);
            }
        });
        return active;
    }

    // Initialize 10 Blank Rows
    function initGrid() {
        gridBody.innerHTML = '';
        for (let i = 0; i < INITIAL_ROW_COUNT; i++) {
            gridBody.appendChild(createRowElement(i));
        }
        updateActiveRowsBadge();
    }

    // Add N Rows
    function addRows(count) {
        const currentCount = gridBody.querySelectorAll('.adj-grid-row').length;
        for (let i = 0; i < count; i++) {
            gridBody.appendChild(createRowElement(currentCount + i));
        }
        updateActiveRowsBadge();
    }

    // Apply Top-Level Defaults to All Rows
    function copyTopDefaultsToAllRows() {
        const adjType = topAdjSel ? topAdjSel.value : 'IN';
        const updateLocal = topUpdateLocalSel ? topUpdateLocalSel.value : '0';
        const reason = topReasonInp ? topReasonInp.value.trim() : '';

        const rows = gridBody.querySelectorAll('.adj-grid-row');
        rows.forEach(tr => {
            const sel = tr.querySelector('.bulk-inp-adj-type');
            const updateLocalSel = tr.querySelector('.bulk-inp-update-local-stock');
            const reasonInp = tr.querySelector('.bulk-inp-reason');
            if (sel) sel.value = adjType;
            if (updateLocalSel) updateLocalSel.value = updateLocal;
            if (reasonInp) reasonInp.value = reason;
        });
    }

    // Refresh Current Qty for filled rows if top-level warehouse changes
    function refreshCurrentStockForWarehouse() {
        const whId = getSelectedWarehouseId();
        if (whId <= 0) return;

        const activeRows = getActiveGridRows();
        const pIds = [];
        activeRows.forEach(tr => {
            const pId = parseInt(tr.querySelector('.bulk-inp-product-id').value, 10);
            if (pId > 0) pIds.push(pId);
        });

        if (pIds.length === 0) return;

        fetch('?page=products&action=get_products_warehouse_stock', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ warehouse_id: whId, product_ids: pIds }),
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.stock_map) {
                activeRows.forEach(tr => {
                    const pId = parseInt(tr.querySelector('.bulk-inp-product-id').value, 10);
                    if (pId > 0) {
                        const curQty = data.stock_map[pId] != null ? parseFloat(data.stock_map[pId]) : 0;
                        const qtyBadge = tr.querySelector('.bulk-row-current-qty');
                        qtyBadge.textContent = curQty;
                        qtyBadge.dataset.currentQty = curQty;
                        if (curQty > 0) {
                            qtyBadge.className = 'bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-emerald-800 bg-emerald-50 border border-emerald-200';
                        } else {
                            qtyBadge.className = 'bulk-row-current-qty inline-flex items-center justify-end px-2.5 py-1 rounded-md text-xs font-bold text-rose-800 bg-rose-50 border border-rose-200';
                        }
                    }
                });
            }
        });
    }

    // Close AC menus on click outside
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.bulk-ac-menu') && !e.target.closest('.bulk-inp-sku')) {
            document.querySelectorAll('.bulk-ac-menu').forEach(m => m.classList.add('hidden'));
        }
    });

    // Validate Grid Data Before Submission
    function validateGrid() {
        const issues = [];
        const activeRows = getActiveGridRows();

        if (activeRows.length === 0) {
            issues.push('Please enter at least one line item SKU to perform stock adjustment.');
            return { valid: false, issues, items: [] };
        }

        const items = [];

        activeRows.forEach((tr, idx) => {
            const rowNum = tr.querySelector('.row-num-label').textContent;
            const pId = parseInt(tr.querySelector('.bulk-inp-product-id').value, 10) || 0;
            const sku = tr.querySelector('.bulk-inp-sku').value.trim();
            const itemCode = tr.querySelector('.bulk-inp-item-code').value.trim();
            const size = tr.querySelector('.bulk-inp-size').textContent.trim();
            const color = tr.querySelector('.bulk-inp-color').textContent.trim();
            const curQty = parseFloat(tr.querySelector('.bulk-row-current-qty').dataset.currentQty || '0') || 0;
            const type = tr.querySelector('.bulk-inp-adj-type').value;
            const qtyRaw = tr.querySelector('.bulk-inp-qty').value.trim();
            const quantity = parseInt(qtyRaw, 10) || 0;
            const reason = tr.querySelector('.bulk-inp-reason').value.trim();
            const img = tr.querySelector('.bulk-row-img').src;

            let rowValid = true;

            if (!pId) {
                issues.push(`Row #${rowNum} (SKU "${sku}"): Product not resolved. Select a valid product from SKU search.`);
                rowValid = false;
            }

            if (!quantity || quantity <= 0) {
                issues.push(`Row #${rowNum} (SKU "${sku || 'Unknown'}"): Please enter a valid quantity greater than 0.`);
                rowValid = false;
            }

            if (!rowValid) {
                tr.classList.add('bg-red-50/40', 'border-red-300');
            } else {
                tr.classList.remove('bg-red-50/40', 'border-red-300');
                const updateLocalStock = tr.querySelector('.bulk-inp-update-local-stock').value === '1';
                items.push({
                    product_id: pId,
                    sku,
                    item_code: itemCode,
                    size: size !== '—' ? size : '',
                    color: color !== '—' ? color : '',
                    current_qty: curQty,
                    type,
                    quantity,
                    update_local_stock: updateLocalStock ? 1 : 0,
                    reason,
                    image: img
                });
            }
        });

        return {
            valid: issues.length === 0,
            issues,
            items
        };
    }

    // Open Confirmation Modal
    function openConfirmationModal(items) {
        const whName = getSelectedWarehouseTitle();
        confirmWhName.textContent = whName;

        let incCount = 0;
        let decCount = 0;

        let tbodyHtml = '';
        items.forEach((item, idx) => {
            const isInc = item.type === 'IN';
            if (isInc) incCount += item.quantity;
            else decCount += item.quantity;

            const badgeClass = isInc
                ? 'bg-emerald-100 text-emerald-800 border-emerald-200'
                : 'bg-rose-100 text-rose-800 border-rose-200';

            const typeLabel = isInc ? '+ Increase' : '- Decrease';
            const rawEstQty = isInc ? (item.current_qty + item.quantity) : (item.current_qty - item.quantity);
            const estQty = Math.max(0, rawEstQty);
            const isClamped = rawEstQty < 0;

            const estQtyDisplay = isClamped
                ? `<span class="font-bold text-rose-700">${estQty}</span><span class="text-[10px] font-semibold text-rose-600 block">(Clamped to 0)</span>`
                : `<span class="font-bold text-amber-900">${estQty}</span>`;

            tbodyHtml += `
                <tr class="border-b border-gray-100 hover:bg-slate-50">
                    <td class="py-2.5 px-3 font-mono text-gray-400 text-center">${idx + 1}</td>
                    <td class="py-2.5 px-3 font-mono font-bold text-gray-900">${escapeHtml(item.sku)}</td>
                    <td class="py-2.5 px-3 text-gray-500">${escapeHtml(item.size || 'N/A')} / ${escapeHtml(item.color || 'N/A')}</td>
                    <td class="py-2.5 px-3 text-right text-gray-700 font-semibold">${item.current_qty}</td>
                    <td class="py-2.5 px-3 text-center">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border ${badgeClass}">
                            ${typeLabel}
                        </span>
                    </td>
                    <td class="py-2.5 px-3 text-right font-bold text-gray-900">${item.quantity}</td>
                    <td class="py-2.5 px-3 text-right">${estQtyDisplay}</td>
                    <td class="py-2.5 px-3 text-center">
                        ${item.update_local_stock ? '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-200">Yes</span>' : '<span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">No</span>'}
                    </td>
                    <td class="py-2.5 px-3 text-gray-600 truncate max-w-[150px]">${escapeHtml(item.reason || 'Bulk stock adjustment')}</td>
                </tr>
            `;
        });

        confirmTotalIncrease.textContent = `${incCount} total units`;
        confirmTotalDecrease.textContent = `${decCount} total units`;
        confirmPreviewTbody.innerHTML = tbodyHtml;

        confirmationModal.classList.remove('hidden');
        confirmationModal.classList.add('flex');
    }

    // Execute Submission via AJAX
    function executeAdjustment(items) {
        const whId = getSelectedWarehouseId();
        
        btnExecuteAdjustment.disabled = true;
        btnExecuteAdjustment.innerHTML = `<i class="fas fa-circle-notch fa-spin"></i> Processing...`;

        fetch('?page=products&action=process_bulk_stock_adjustment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                warehouse_id: whId,
                items: items
            }),
            credentials: 'same-origin'
        })
        .then(res => res.json())
        .then(data => {
            btnExecuteAdjustment.disabled = false;
            btnExecuteAdjustment.innerHTML = `<i class="fas fa-check"></i> Confirm &amp; Execute`;

            confirmationModal.classList.add('hidden');
            confirmationModal.classList.remove('flex');

            if (data.success) {
                resultIconWrap.className = 'mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-700 text-2xl';
                resultIcon.className = 'fas fa-check';
                resultTitle.textContent = 'Adjustment Successful!';
                resultMsg.textContent = data.message || 'Stock adjustments have been successfully recorded.';
                
                // Clear grid on success
                initGrid();
            } else {
                resultIconWrap.className = 'mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 text-2xl';
                resultIcon.className = 'fas fa-exclamation-triangle';
                resultTitle.textContent = 'Execution Failed';
                resultMsg.textContent = data.message || 'Failed to process stock adjustment. Please try again.';
            }

            resultModal.classList.remove('hidden');
            resultModal.classList.add('flex');
        })
        .catch(err => {
            btnExecuteAdjustment.disabled = false;
            btnExecuteAdjustment.innerHTML = `<i class="fas fa-check"></i> Confirm &amp; Execute`;

            confirmationModal.classList.add('hidden');
            confirmationModal.classList.remove('flex');

            resultIconWrap.className = 'mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-rose-100 text-rose-700 text-2xl';
            resultIcon.className = 'fas fa-exclamation-triangle';
            resultTitle.textContent = 'Network Error';
            resultMsg.textContent = 'Server connection failed: ' + err.message;

            resultModal.classList.remove('hidden');
            resultModal.classList.add('flex');
        });
    }

    // --- Event Listeners ---

    // Boot
    initGrid();

    // Top warehouse change (for admins)
    if (topWhSel) {
        topWhSel.addEventListener('change', refreshCurrentStockForWarehouse);
    }

    // Apply Top Defaults to All Rows
    btnCopyDefaults.addEventListener('click', function () {
        copyTopDefaultsToAllRows();
    });

    // Add Row
    btnAddRow.addEventListener('click', function () {
        addRows(1);
    });

    // Add 5 Rows
    btnAdd5Rows.addEventListener('click', function () {
        addRows(5);
    });

    // Reset / Clear Grid
    btnResetGrid.addEventListener('click', function () {
        if (getActiveGridRows().length === 0 || confirm('Clear all grid rows and reset?')) {
            initGrid();
        }
    });

    // Submit Adjustments
    btnSubmit.addEventListener('click', function () {
        const validation = validateGrid();

        if (!validation.valid) {
            validationList.innerHTML = validation.issues.map(iss => `<li class="flex items-start gap-1.5"><i class="fas fa-times-circle text-red-500 mt-0.5 shrink-0"></i><span>${escapeHtml(iss)}</span></li>`).join('');
            validationModal.classList.remove('hidden');
            validationModal.classList.add('flex');
            return;
        }

        openConfirmationModal(validation.items);
    });

    // Confirm & Execute
    let pendingItems = [];
    btnSubmit.addEventListener('click', function () {
        const val = validateGrid();
        if (val.valid) {
            pendingItems = val.items;
        }
    });

    btnExecuteAdjustment.addEventListener('click', function () {
        if (pendingItems.length > 0) {
            executeAdjustment(pendingItems);
        }
    });

    // Modal Close Buttons
    closeValidationBtn.addEventListener('click', function () {
        validationModal.classList.add('hidden');
        validationModal.classList.remove('flex');
    });

    closeConfirmModalBtn.addEventListener('click', function () {
        confirmationModal.classList.add('hidden');
        confirmationModal.classList.remove('flex');
    });

    btnCancelConfirm.addEventListener('click', function () {
        confirmationModal.classList.add('hidden');
        confirmationModal.classList.remove('flex');
    });

    closeResultModalBtn.addEventListener('click', function () {
        resultModal.classList.add('hidden');
        resultModal.classList.remove('flex');
    });

    closeLightboxBtn.addEventListener('click', function () {
        lightboxModal.classList.add('hidden');
        lightboxModal.classList.remove('flex');
    });

    lightboxModal.addEventListener('click', function (e) {
        if (e.target === lightboxModal) {
            lightboxModal.classList.add('hidden');
            lightboxModal.classList.remove('flex');
        }
    });

})();
</script>
