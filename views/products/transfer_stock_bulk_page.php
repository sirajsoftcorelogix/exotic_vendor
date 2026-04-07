<?php
$currentUserId = $_SESSION['user']['id'] ?? 0;
$selectedRequestedBy = (int)($transfer['requested_by'] ?? $currentUserId);
$selectedDispatchBy = (int)($transfer['dispatch_by'] ?? $currentUserId);
$defaultDispatchDate = !empty($transfer['dispatch_date']) ? $transfer['dispatch_date'] : date('Y-m-d');
$gridRowCount = 40;
?>
<div class="min-h-screen bg-gray-50 p-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 pb-4 border-b border-gray-200">
        <div class="flex items-center gap-4">
            <div class="text-2xl text-gray-500">
                <i class="fas fa-table"></i>
            </div>
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Bulk stock transfer</h1>
                <p class="text-sm text-gray-600 mt-1">Fill the grid (default) or upload a spreadsheet—plus dispatch, route, and transport details.</p>
            </div>
        </div>
        <div class="flex flex-wrap gap-3">
            <a href="?page=products&action=stock_transfer" class="text-sm font-semibold text-gray-600 hover:text-gray-900 whitespace-nowrap">
                Transfer history
            </a>
        </div>
    </div>

    <form id="bulkTransferForm" class="space-y-6" method="POST" enctype="multipart/form-data" action="?page=products&action=process_transfer_stock_bulk">
        <input type="hidden" name="bulk_mode" id="bulk_mode" value="grid">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Transfer Order No.</label>
                    <input type="text" name="transfer_order_no" id="bulk_transfer_order_no" readonly value="" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch Date <span class="text-red-500">*</span></label>
                    <input type="date" id="dispatch_date" name="dispatch_date" value="<?php echo htmlspecialchars($defaultDispatchDate); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Est Delivery Date <span class="text-red-500">*</span></label>
                    <input type="date" id="est_delivery_date" name="est_delivery_date" value="<?php echo htmlspecialchars($transfer['est_delivery_date'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Requested By <span class="text-red-500">*</span></label>
                    <select id="requested_by" name="requested_by" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $selectedRequestedBy ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch By <span class="text-red-500">*</span></label>
                    <select id="dispatch_by" name="dispatch_by" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $selectedDispatchBy ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid gap-6 lg:grid-cols-3 mb-6 items-start">
                <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">Source Warehouse <span class="text-red-500">*</span></label>
                    <select id="from_warehouse" name="from_warehouse" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select Warehouse --</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="text-xs text-gray-500 mt-2 leading-relaxed" id="source_address">Select a warehouse to see address</div>
                </div>
                <div class="flex items-center justify-center pt-6">
                    <span class="bg-gray-800 text-white w-10 h-10 rounded-full flex items-center justify-center"><i class="fas fa-arrow-right"></i></span>
                </div>
                <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                    <label class="text-sm font-semibold text-gray-700 mb-2 block">Destination Warehouse <span class="text-red-500">*</span></label>
                    <select id="to_warehouse" name="to_warehouse" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select Warehouse --</option>
                        <?php foreach ($warehouses as $warehouse): ?>
                            <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="text-xs text-gray-500 mt-2 leading-relaxed" id="dest_address">Select a warehouse to see address</div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
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
                                <th class="px-1 py-2 text-center font-semibold text-gray-700 w-14 min-w-[3.5rem]" scope="col">Image</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 min-w-[11rem]">SKU <span class="font-normal text-gray-500">(search)</span></th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-32">Size</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-32">Color</th>
                                <th class="px-3 py-2 text-left font-semibold text-gray-700 w-24">Qty</th>
                            </tr>
                        </thead>
                        <tbody id="bulk_grid_body">
                            <?php for ($i = 0; $i < $gridRowCount; $i++): ?>
                                <tr class="bulk-grid-row border-b border-gray-100">
                                    <td class="bulk-row-num px-2 py-2 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-middle border-r border-gray-100"><?php echo $i + 1; ?></td>
                                    <td class="bulk-img-cell p-1 align-middle w-14 text-center bg-gray-50/80 border-r border-gray-100">
                                        <div class="bulk-row-img-wrap relative flex min-h-[3.5rem] items-center justify-center">
                                            <img alt="" class="bulk-row-img hidden max-h-14 w-full max-w-[3rem] object-contain rounded border border-gray-200 bg-white" width="48" height="56" decoding="async" loading="lazy">
                                            <span class="bulk-row-img-ph pointer-events-none text-gray-300 text-xs select-none" aria-hidden="true">—</span>
                                        </div>
                                    </td>
                                    <td class="p-1 align-top">
                                        <div class="relative">
                                            <input type="hidden" class="bulk-inp-item-code" value="" autocomplete="off">
                                            <input type="text" class="bulk-inp-sku w-full px-2 py-1.5 border border-gray-200 rounded text-sm" placeholder="Type SKU…" autocomplete="off">
                                            <div class="bulk-ac-menu hidden absolute left-0 right-0 mt-0.5 bg-white border border-gray-300 rounded-md shadow-lg max-h-52 overflow-y-auto z-30 text-xs" role="listbox"></div>
                                        </div>
                                    </td>
                                    <td class="p-1"><input type="text" class="bulk-inp-size w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                    <td class="p-1"><input type="text" class="bulk-inp-color w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                    <td class="p-1"><input type="number" min="0" class="bulk-inp-qty w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>
                                </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
                <button type="button" id="bulk_add_rows" class="mt-3 text-sm font-semibold text-amber-700 hover:text-amber-900">+ Add 10 rows</button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Transportation details</div>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Booking No</label>
                    <input type="text" name="booking_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Vehicle No</label>
                    <input type="text" name="vehicle_no" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Vehicle Type</label>
                    <input type="text" name="vehicle_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Driver Name</label>
                    <input type="text" name="driver_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Driver Mobile</label>
                    <input type="tel" name="driver_mobile" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">E-Way Bill</label>
                    <input type="file" name="eway_bill_file" accept="application/pdf,image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <input type="hidden" name="existing_eway_bill_file" value="">
                    <input type="hidden" name="remove_eway_bill_file" value="0">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6 flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 px-6 py-2.5 rounded-lg bg-orange-500 text-white font-semibold hover:bg-orange-600 transition">
                <i class="fas fa-check"></i> Create bulk transfer
            </button>
        </div>

        <textarea name="bulk_rows_json" id="bulk_rows_json" class="hidden" aria-hidden="true"></textarea>
    </form>
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
        document.getElementById('source_address').textContent = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        refreshOrderNo();
    });
    toSel.addEventListener('change', function () {
        document.getElementById('dest_address').textContent = warehouseData[this.value]?.address || 'Select a warehouse to see address';
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
            tr.innerHTML = '<td class="bulk-row-num px-2 py-2 text-center text-xs font-semibold text-gray-500 tabular-nums select-none bg-gray-50 align-middle border-r border-gray-100"></td>' +
                '<td class="bulk-img-cell p-1 align-middle w-14 text-center bg-gray-50/80 border-r border-gray-100">' +
                '<div class="bulk-row-img-wrap relative flex min-h-[3.5rem] items-center justify-center">' +
                '<img alt="" class="bulk-row-img hidden max-h-14 w-full max-w-[3rem] object-contain rounded border border-gray-200 bg-white" width="48" height="56" decoding="async" loading="lazy">' +
                '<span class="bulk-row-img-ph pointer-events-none text-gray-300 text-xs select-none" aria-hidden="true">—</span></div></td>' +
                '<td class="p-1 align-top"><div class="relative">' +
                '<input type="hidden" class="bulk-inp-item-code" value="" autocomplete="off">' +
                '<input type="text" class="bulk-inp-sku w-full px-2 py-1.5 border border-gray-200 rounded text-sm" placeholder="Type SKU…" autocomplete="off">' +
                '<div class="bulk-ac-menu hidden absolute left-0 right-0 mt-0.5 bg-white border border-gray-300 rounded-md shadow-lg max-h-52 overflow-y-auto z-30 text-xs" role="listbox"></div>' +
                '</div></td>' +
                '<td class="p-1"><input type="text" class="bulk-inp-size w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>' +
                '<td class="p-1"><input type="text" class="bulk-inp-color w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>' +
                '<td class="p-1"><input type="number" min="0" class="bulk-inp-qty w-full px-2 py-1.5 border border-gray-200 rounded text-sm" autocomplete="off"></td>';
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

    const gridBody = document.getElementById('bulk_grid_body');
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
