<?php
$currentUserId = $_SESSION['user']['id'] ?? 0;
$selectedRequestedBy = (int)($transfer['requested_by'] ?? $currentUserId);
$selectedDispatchBy = (int)($transfer['dispatch_by'] ?? $currentUserId);
$defaultDispatchDate = !empty($transfer['dispatch_date']) ? $transfer['dispatch_date'] : date('Y-m-d');
$gridRowCount = 40;
?>
<div class="min-h-screen bg-gradient-to-b from-slate-50 via-white to-amber-50/25">
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">

    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/80 via-white to-slate-50/50 shadow-sm ring-1 ring-amber-900/[0.04] mb-8">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/15 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-16 -left-12 h-48 w-48 rounded-full bg-sky-200/10 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/80 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-table text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Warehouse · New transfer</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Bulk <span class="text-amber-800">stock transfer</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Set dispatch dates and route, add line items in the grid (or upload a file), then add transport details and submit.
                </p>
            </div>
            <div class="flex shrink-0 flex-col sm:flex-row gap-3 lg:pl-4 lg:self-center">
                <a href="?page=products&action=stock_transfer" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-200/90 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap">
                    <i class="fas fa-history text-xs text-amber-700" aria-hidden="true"></i>
                    Transfer history
                </a>
            </div>
        </div>
    </div>

    <form id="bulkTransferForm" class="space-y-8" method="POST" enctype="multipart/form-data" action="?page=products&action=process_transfer_stock_bulk">
        <input type="hidden" name="bulk_mode" id="bulk_mode" value="grid">

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-gradient-to-r from-amber-50/40 via-white to-slate-50/30">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-amber-700 shadow-sm border border-amber-100">
                        <i class="fas fa-file-invoice text-sm" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Order &amp; schedule</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Transfer number is generated from the route. Dates and owners are required.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-5 sm:gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Transfer order no.</label>
                        <input type="text" name="transfer_order_no" id="bulk_transfer_order_no" readonly value="" class="w-full px-3 py-2.5 border border-amber-100 rounded-lg bg-amber-50/40 text-sm text-gray-800 font-medium cursor-not-allowed shadow-sm" title="Assigned when warehouses are selected">
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
            <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-gradient-to-r from-sky-50/30 via-white to-amber-50/20">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-sky-700 shadow-sm border border-sky-100">
                        <i class="fas fa-route text-sm" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Route</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Choose source and destination. Order number updates when both are set.</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-4 lg:gap-5 lg:grid-cols-[1fr_auto_1fr] lg:items-stretch items-start">
                    <div class="rounded-xl border border-amber-100/80 bg-gradient-to-b from-amber-50/50 to-white p-4 sm:p-5 shadow-sm">
                        <label class="block text-xs font-semibold text-amber-900/80 mb-1.5 uppercase tracking-wide">From <span class="text-red-500 normal-case">*</span></label>
                        <select id="from_warehouse" name="from_warehouse" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select warehouse…</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-2.5 leading-relaxed min-h-[2.5rem]" id="source_address">Select a warehouse to see the full address.</p>
                    </div>
                    <div class="flex items-center justify-center py-2 lg:py-0 lg:pt-10">
                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-amber-600 to-amber-700 text-white shadow-md shadow-amber-900/20 ring-4 ring-white" aria-hidden="true"><i class="fas fa-arrow-right text-sm"></i></span>
                    </div>
                    <div class="rounded-xl border border-slate-200/90 bg-gradient-to-b from-slate-50/80 to-white p-4 sm:p-5 shadow-sm">
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5 uppercase tracking-wide">To <span class="text-red-500 normal-case">*</span></label>
                        <select id="to_warehouse" name="to_warehouse" required class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                            <option value="">Select warehouse…</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-2.5 leading-relaxed min-h-[2.5rem]" id="dest_address">Select a warehouse to see the full address.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Line items</div>
            <div class="flex flex-wrap gap-2 mb-4">
                <button type="button" id="tab_grid" class="bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300">1. Excel-style grid</button>
                <button type="button" id="tab_upload" class="bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200">2. Upload spreadsheet</button>
            </div>

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
                </div>
            </div>

            <div id="panel_grid" class="bulk-panel">
                <p class="text-sm text-gray-600 mb-3">Type a <strong>SKU</strong> to search—matching products appear below the cell. Pick one to fill <strong>item code</strong>, size, and color automatically. Enter quantity in the last column. You can still adjust size or color manually if needed.</p>
                <div class="overflow-x-auto border border-gray-200 rounded-lg max-h-[420px] overflow-y-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100 sticky top-0 z-10">
                            <tr>
                                <th class="px-2 py-2 text-center font-semibold text-gray-700 w-10 min-w-[2.5rem]" scope="col">#</th>
                                <th class="px-1 py-2 text-center font-semibold text-gray-700 w-12 min-w-[3rem]" scope="col">Image</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-32">SKU <span class="font-normal text-gray-500">(search)</span></th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-32">Size</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-32">Color</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-24">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="bulk_grid_body">
                            <?php for ($i = 0; $i < $gridRowCount; $i++): ?>
                                <tr class="bulk-grid-row border-b border-gray-100">
                                    <td class="bulk-row-num px-2 py-1.5 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-top border-r border-gray-100 pt-2"><?php echo $i + 1; ?></td>
                                    <td class="bulk-img-cell w-12 min-w-[3rem] p-1 align-top bg-gray-50/80 border-r border-gray-100">
                                        <div class="bulk-row-img-wrap relative flex min-h-[2.5rem] items-center justify-center pt-0.5">
                                            <img alt="" title="Click to enlarge" class="bulk-row-img hidden max-h-11 max-w-[2.35rem] w-full cursor-pointer object-contain rounded border border-gray-200 bg-white" width="40" height="48" decoding="async" loading="lazy">
                                            <span class="bulk-row-img-ph pointer-events-none text-gray-300 text-[10px] select-none leading-none py-1" aria-hidden="true">—</span>
                                        </div>
                                    </td>
                                    <td class="p-1 align-top w-32 min-w-0">
                                        <div class="relative w-full min-w-0">
                                            <input type="hidden" class="bulk-inp-item-code" value="" autocomplete="off">
                                            <input type="text" class="bulk-inp-sku w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" placeholder="Type SKU…" autocomplete="off">
                                            <div class="bulk-ac-menu hidden absolute left-0 right-0 z-30 mt-0.5 max-h-52 min-w-[12rem] overflow-y-auto rounded-md border border-gray-300 bg-white text-xs shadow-lg" role="listbox"></div>
                                        </div>
                                    </td>
                                    <td class="p-1 align-top w-32 min-w-0"><input type="text" class="bulk-inp-size w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                    <td class="p-1 align-top w-32 min-w-0"><input type="text" class="bulk-inp-color w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                    <td class="p-1 align-top"><input type="number" min="0" class="bulk-inp-qty w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="bulk_add_rows" class="mt-3 text-sm font-semibold text-amber-700 hover:text-amber-900">+ Add 10 rows</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03] overflow-hidden">
            <div class="px-5 py-4 sm:px-6 border-b border-gray-100 bg-gradient-to-r from-slate-50/50 via-white to-amber-50/15">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white text-gray-700 shadow-sm border border-gray-200">
                        <i class="fas fa-truck text-sm" aria-hidden="true"></i>
                    </span>
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Transportation</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Vehicle, driver, and e-way documentation (all optional unless your process requires them).</p>
                    </div>
                </div>
            </div>
            <div class="p-5 sm:p-6">
                <div class="grid gap-5 sm:gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Booking no.</label>
                        <input type="text" name="booking_no" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Vehicle no.</label>
                        <input type="text" name="vehicle_no" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Vehicle type</label>
                        <input type="text" name="vehicle_type" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Driver name</label>
                        <input type="text" name="driver_name" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">Driver mobile</label>
                        <input type="tel" name="driver_mobile" class="w-full px-3 py-2.5 border border-gray-200 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    </div>
                    <div class="flex flex-col">
                        <label class="block text-xs font-semibold text-gray-600 mb-1.5">E-way bill</label>
                        <input type="file" name="eway_bill_file" accept="application/pdf,image/*" class="w-full px-3 py-2.5 border border-dashed border-gray-300 rounded-lg text-sm text-gray-600 bg-gray-50/50 transition hover:border-amber-300/60">
                        <input type="hidden" name="existing_eway_bill_file" value="">
                        <input type="hidden" name="remove_eway_bill_file" value="0">
                        <p class="text-[11px] text-gray-400 mt-1.5">PDF or image</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-amber-200/50 bg-gradient-to-r from-amber-50/40 via-white to-amber-50/30 p-5 sm:p-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 shadow-sm ring-1 ring-amber-900/[0.04]">
            <p class="text-sm text-gray-600 max-w-xl"><span class="font-semibold text-gray-800">Ready?</span> Check line items and warehouses, then create the transfer. You can review it later in transfer history.</p>
            <button type="submit" class="inline-flex items-center justify-center gap-2 px-8 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap shrink-0">
                <i class="fas fa-check text-xs opacity-95" aria-hidden="true"></i>
                Create bulk transfer
            </button>
        </div>

        <textarea name="bulk_rows_json" id="bulk_rows_json" class="hidden" aria-hidden="true"></textarea>
    </form>
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

    function refreshOrderNo() {
        if (!orderInput || !fromSel.value || !toSel.value) return;
        fetchNextTransferOrderNo(fromSel.value, toSel.value).then(function (no) { orderInput.value = no; });
    }

    fromSel.addEventListener('change', function () {
        document.getElementById('source_address').textContent = warehouseData[this.value]?.address || 'Select a warehouse to see the full address.';
        refreshOrderNo();
    });
    toSel.addEventListener('change', function () {
        document.getElementById('dest_address').textContent = warehouseData[this.value]?.address || 'Select a warehouse to see the full address.';
        refreshOrderNo();
    });

    document.addEventListener('DOMContentLoaded', function () {
        fetch(apiUrl('get_last_warehouse'), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.success && data.warehouse_id) {
                    fromSel.value = data.warehouse_id;
                    fromSel.dispatchEvent(new Event('change'));
                }
            })
            .catch(function () {});

        if (fromSel.value && toSel.value) refreshOrderNo();
    });

    const tabUpload = document.getElementById('tab_upload');
    const tabGrid = document.getElementById('tab_grid');
    const panelUpload = document.getElementById('panel_upload');
    const panelGrid = document.getElementById('panel_grid');
    const bulkMode = document.getElementById('bulk_mode');
    const bulkFile = document.getElementById('bulk_file');

    function setTab(mode) {
        bulkMode.value = mode;
        if (mode === 'upload') {
            panelUpload.classList.remove('hidden');
            panelGrid.classList.add('hidden');
            tabUpload.className = 'bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300';
            tabGrid.className = 'bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200';
            bulkFile.removeAttribute('data-optional');
        } else {
            panelUpload.classList.add('hidden');
            panelGrid.classList.remove('hidden');
            tabGrid.className = 'bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-amber-100 text-amber-900 ring-1 ring-amber-300';
            tabUpload.className = 'bulk-tab px-4 py-2 rounded-lg text-sm font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200';
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
            tr.className = 'bulk-grid-row border-b border-gray-100';
            tr.innerHTML = '<td class="bulk-row-num px-2 py-1.5 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-top border-r border-gray-100 pt-2"></td>' +
                '<td class="bulk-img-cell w-12 min-w-[3rem] p-1 align-top bg-gray-50/80 border-r border-gray-100">' +
                '<div class="bulk-row-img-wrap relative flex min-h-[2.5rem] items-center justify-center pt-0.5">' +
                '<img alt="" title="Click to enlarge" class="bulk-row-img hidden max-h-11 max-w-[2.35rem] w-full cursor-pointer object-contain rounded border border-gray-200 bg-white" width="40" height="48" decoding="async" loading="lazy">' +
                '<span class="bulk-row-img-ph pointer-events-none text-gray-300 text-[10px] select-none leading-none py-1" aria-hidden="true">—</span></div></td>' +
                '<td class="p-1 align-top w-32 min-w-0"><div class="relative w-full min-w-0">' +
                '<input type="hidden" class="bulk-inp-item-code" value="" autocomplete="off">' +
                '<input type="text" class="bulk-inp-sku w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" placeholder="Type SKU…" autocomplete="off">' +
                '<div class="bulk-ac-menu hidden absolute left-0 right-0 z-30 mt-0.5 max-h-52 min-w-[12rem] overflow-y-auto rounded-md border border-gray-300 bg-white text-xs shadow-lg" role="listbox"></div>' +
                '</div></td>' +
                '<td class="p-1 align-top w-32 min-w-0"><input type="text" class="bulk-inp-size w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>' +
                '<td class="p-1 align-top w-32 min-w-0"><input type="text" class="bulk-inp-color w-full min-w-0 px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>' +
                '<td class="p-1 align-top"><input type="number" min="0" class="bulk-inp-qty w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>';
            tbody.appendChild(tr);
        }
        renumberBulkGrid();
    });
    renumberBulkGrid();

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
            if (bulkFile) bulkFile.value = '';
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
                    alert('Stock transfer created successfully.');
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
