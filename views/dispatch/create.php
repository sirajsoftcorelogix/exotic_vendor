<div class="max-w-7xl mx-auto space-y-6 px-2 sm:px-4 lg:px-6">
    <!-- Header -->
    <div class="shadow-[0px_10px_15px_-3px_#0000001A] bg-white rounded-2xl shadow mt-6 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
      <div class="flex items-center gap-4">
        <div class="bg-orange-500 text-white p-3 rounded-xl shadow-[0px_10px_15px_-3px_#0000001A]">
          <img src="<?php echo base_url('images/icons.svg'); ?>" alt="Dispatch Icon">
        </div>
        <div>
          <h1 class="text-2xl font-bold bg-gradient-to-r from-[#1E2939] to-[#4A5565] bg-clip-text text-transparent">
            Ship Order
          </h1>
          <p class="text-gray-500 text-sm text-[#6A7282]">
            Process dispatch & courier booking for Invoice #<?php echo htmlspecialchars($_GET['invoice_id'] ?? ''); ?>
          </p>
        </div>
      </div>
      <a href="<?php echo base_url('?page=dispatch&action=list'); ?>" class="text-sm font-semibold text-orange-600 hover:text-orange-700 underline">
        ← Back to Dispatch List
      </a>
    </div>

    <?php if (isset($_GET['status']) && $_GET['status'] === 'error' && isset($_GET['message'])): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-red-600 font-semibold text-sm"><?php echo htmlspecialchars($_GET['message']); ?></p>
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
            <p class="text-green-600 font-semibold text-sm">Dispatch created successfully!</p>
        </div>
    <?php endif; ?>

    <?php 
    $isInternational = !empty($is_international);
    $primaryOrderNumber = (string) ($primary_order_number ?? '');
    $dispatchRecords = is_array($dispatchRecords ?? null) ? $dispatchRecords : [];

    // IF DISPATCH RECORDS ALREADY EXIST (Already created / shipped)
    if (count($dispatchRecords) > 0): 
    ?>
        <div class="bg-green-50 border border-green-200 rounded-xl p-6 mb-6">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 mb-4">
                <div>
                    <p class="text-green-800 font-bold text-lg">✓ Dispatch Already Processed</p>
                    <p class="text-green-700 text-sm mt-0.5">Below are the shipment details, AWB numbers, and shipping labels for this invoice.</p>
                </div>
                <a href="<?php echo base_url('?page=dispatch&action=list'); ?>" class="text-green-700 hover:text-green-800 underline font-semibold text-sm">
                    ← Go to Dispatch List
                </a>
            </div>
        </div>

        <!-- Dispatch Records Grid -->
        <div class="grid grid-cols-1 gap-6">
            <?php foreach ($dispatchRecords as $dispatch): ?>
            <div class="bg-white rounded-2xl shadow-[0px_10px_15px_-3px_#0000001A] overflow-hidden border border-gray-200">
                <div class="bg-orange-500 p-5 text-white flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h3 class="text-lg font-bold mb-0.5">Box <?php echo htmlspecialchars($dispatch['box_no'] ?? '1'); ?></h3>
                        <p class="text-orange-100 text-xs">Dispatch ID: #<?php echo htmlspecialchars($dispatch['id'] ?? 'N/A'); ?> | Order: <?php echo htmlspecialchars($dispatch['order_number'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="flex flex-wrap items-center gap-3 text-xs">
                        <?php if (!empty($dispatch['courier_name'])): ?>
                            <span class="bg-white/20 backdrop-blur px-3 py-1 rounded-md font-semibold">Courier: <?php echo htmlspecialchars($dispatch['courier_name']); ?></span>
                        <?php endif; ?>
                        <?php if (!empty($dispatch['awb_code'])): ?>
                            <span class="bg-white text-gray-900 px-3 py-1 rounded-md font-bold">AWB: <?php echo htmlspecialchars($dispatch['awb_code']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm text-gray-700 border-b border-gray-100 bg-gray-50/50">
                    <div>
                        <span class="text-xs text-gray-500 block">Courier Partner</span>
                        <strong class="font-semibold text-gray-900"><?php echo htmlspecialchars($dispatch['courier_name'] ?? 'N/A'); ?></strong>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Status</span>
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-emerald-100 text-emerald-800 uppercase">
                            <?php echo htmlspecialchars($dispatch['shipment_status'] ?? 'created'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Dimensions & Weight</span>
                        <span class="font-medium text-gray-900">
                            <?php echo htmlspecialchars(($dispatch['length'] ?? '0') . 'x' . ($dispatch['width'] ?? '0') . 'x' . ($dispatch['height'] ?? '0') . ' in | ' . ($dispatch['weight'] ?? '0') . ' kg'); ?>
                        </span>
                    </div>
                    <div>
                        <span class="text-xs text-gray-500 block">Pickup Location</span>
                        <span class="font-medium text-gray-900"><?php echo htmlspecialchars($dispatch['pickup_location'] ?? 'Head Off'); ?></span>
                    </div>
                </div>

                <div class="p-6 flex flex-wrap items-center justify-between gap-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <?php if (!empty($dispatch['label_url'])): ?>
                            <a href="<?php echo htmlspecialchars($dispatch['label_url']); ?>" target="_blank" rel="noopener"
                               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2">
                                📄 View Shipping Label
                            </a>
                        <?php endif; ?>
                        <button type="button" onclick="generateEInvoice(<?php echo (int)($dispatch['invoice_id'] ?? $_GET['invoice_id'] ?? 0); ?>, this)"
                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2 transition">
                            ⚡ Generate E-Invoice
                        </button>
                        <?php if (!empty($dispatch['tracking_url'])): ?>
                            <a href="<?php echo htmlspecialchars($dispatch['tracking_url']); ?>" target="_blank" rel="noopener"
                               class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2">
                                🔗 Track Shipment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

    <?php 
    // ELSE: NEW DISPATCH FORM (Bulk Dispatch Style single order card)
    else: 
    ?>

        <div id="invDispatchesContainer" class="space-y-6">
            <!-- Dynamically populated via JS using window.SINGLE_DISPATCH_PAYLOAD -->
        </div>

        <div class="border-t border-gray-200 px-6 py-4 flex flex-wrap justify-between items-center gap-4 bg-white rounded-2xl shadow-sm">
            <div id="bluedartExcelExportHint" class="hidden min-w-0 flex-1 flex flex-col gap-0.5 text-left pr-3">
                <span class="text-xs font-semibold text-sky-900">Blue Dart Items Selected</span>
                <span class="text-[11px] text-gray-500">Export box details to Excel for manual booking on the Blue Dart dashboard.</span>
            </div>
            <div id="singleDispatchActionSlot" class="shrink-0 ml-auto flex items-center gap-3">
                <button id="singleDispatchSubmitBtn" type="button" disabled
                        class="bg-orange-500 hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-semibold px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2 shadow-sm transition">
                    <span>🚚</span>
                    <span>Confirm &amp; Process Dispatch</span>
                </button>
                <button type="button" id="downloadBlueDartExcelBtn" class="hidden bg-sky-600 hover:bg-sky-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm inline-flex items-center gap-2 transition">
                    <span>📥</span>
                    <span>Export to Excel</span>
                </button>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Modal: Select Items for Box -->
<div id="selectItemsModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div data-modal-backdrop class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-2xl max-h-[80vh] bg-white shadow-xl border border-gray-300 mx-3 sm:mx-6 rounded-xl overflow-hidden my-auto top-12">
        <div class="flex justify-between items-center px-5 py-3 border-b border-gray-200 bg-orange-500 text-white">
            <span class="font-semibold text-sm">Select Items for Box</span>
            <button type="button" data-close-select-items aria-label="Close" class="text-white text-xl leading-none px-2 hover:text-white/80">&times;</button>
        </div>
        <div class="px-5 py-4 text-xs text-gray-800 overflow-y-auto max-h-[60vh]">
            <table class="w-full text-left border border-gray-200 text-xs rounded-lg overflow-hidden">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="p-2.5 border-b border-gray-200 w-10 text-center">
                            <input id="selectAllModal" type="checkbox"/>
                        </th>
                        <th class="p-2.5 border-b border-gray-200">Item Name</th>
                        <th class="p-2.5 border-b border-gray-200">Item Code</th>
                        <th class="p-2.5 border-b border-gray-200 text-right">Qty</th>
                        <th class="p-2.5 border-b border-gray-200 text-right">Weight</th>
                    </tr>
                </thead>
                <tbody id="modalItemsTbody">
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 flex justify-between items-center bg-gray-50">
            <button type="button" data-close-select-items class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-1.5 rounded text-xs">
                Cancel
            </button>
            <button type="button" id="addToInvoiceBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-5 py-1.5 rounded text-xs">
                Save Selected Items
            </button>
        </div>
    </div>
</div>

<!-- Modal: Custom Box Size -->
<div id="customBoxSizeModal" class="fixed inset-0 z-50 hidden" aria-hidden="true">
    <div data-modal-backdrop class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-md bg-white shadow-xl border border-gray-300 mx-3 rounded-xl overflow-hidden my-auto top-20">
        <div class="flex justify-between items-center px-4 py-3 border-b border-gray-200 bg-orange-500 text-white">
            <h2 class="font-semibold text-sm">Enter Custom Box Dimensions</h2>
            <button type="button" data-close-custom-modal aria-label="Close" class="text-white text-xl leading-none px-2 hover:text-white/80">&times;</button>
        </div>
        <div class="px-5 py-4 space-y-3">
            <div>
                <label for="modalCustomLength" class="block text-gray-700 font-medium text-xs mb-1">Length (inches)</label>
                <input id="modalCustomLength" type="number" placeholder="22" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-orange-500 outline-none" step="0.5"/>
            </div>
            <div>
                <label for="modalCustomWidth" class="block text-gray-700 font-medium text-xs mb-1">Width (inches)</label>
                <input id="modalCustomWidth" type="number" placeholder="17" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-orange-500 outline-none" step="0.5"/>
            </div>
            <div>
                <label for="modalCustomHeight" class="block text-gray-700 font-medium text-xs mb-1">Height (inches)</label>
                <input id="modalCustomHeight" type="number" placeholder="5" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-1 focus:ring-orange-500 outline-none" step="0.5"/>
            </div>
            <p class="text-xs text-gray-500">Enter dimensions in inches. All fields are required.</p>
        </div>
        <div class="px-5 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50">
            <button type="button" data-close-custom-modal class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-1.5 rounded text-xs">
                Cancel
            </button>
            <button type="button" id="applyCustomBoxSizeBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1.5 rounded text-xs">
                Apply Custom Dimensions
            </button>
        </div>
    </div>
</div>

<script>
window.SINGLE_DISPATCH_PAYLOAD = <?php echo json_encode($single_order_payload ?? null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;

(function () {
    const payload = window.SINGLE_DISPATCH_PAYLOAD;
    if (!payload || !payload.invoice_id) return;

    const container = document.getElementById('invDispatchesContainer');
    if (!container) return;

    let currentBoxElementForModal = null;

    function escapeHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function showPosModalMessage(title, message, tone = 'info') {
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({ title, message, tone });
        } else {
            alert(title + ': ' + message);
        }
    }

    function singleDispatchTheme(isInternational) {
        if (isInternational) {
            return {
                attr: 'international',
                orderHeader: 'bg-violet-600 text-white',
                intlPill: '<span class="shrink-0 rounded-md bg-white/20 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">International</span>',
                boxBorder: 'border-violet-400',
                boxToolbar: 'bg-violet-50 border-violet-200',
                boxIcon: 'bg-violet-500',
                btnItem: 'bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold px-3 py-1 rounded-lg',
                btnAddBox: 'bg-violet-600 hover:bg-violet-700 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2',
                btnListCourier: 'bg-fuchsia-600 hover:bg-fuchsia-700 text-white font-semibold px-4 py-2 rounded-lg text-sm',
                courierShipIcon: 'bg-violet-600',
                courierCountBadge: 'bg-violet-100 text-violet-800',
                courierPrice: 'text-violet-700',
                courierRadio: 'text-violet-600 focus:ring-violet-500',
                courierTileChecked: 'has-[:checked]:border-violet-500 has-[:checked]:bg-violet-50/40 has-[:checked]:ring-2 has-[:checked]:ring-violet-400 has-[:checked]:ring-offset-1',
                courierTileHover: 'hover:border-violet-300',
                courierTopPick: 'bg-violet-600',
                loadingSpinner: 'border-violet-600',
                emptyPanelBorder: 'border-violet-200',
                emptyPanelGradient: 'from-violet-50/80',
                emptyHeaderBorder: 'border-violet-100',
                emptyHeaderBg: 'bg-white/70',
                emptyIconBg: 'bg-violet-100 text-violet-700',
                emptyTitle: 'text-violet-950',
                emptyText: 'text-violet-900/80',
                emptyBadge: 'bg-violet-200/80 text-violet-950',
                emptyToolbarBg: 'bg-violet-50/50 border-violet-100/80',
                emptyBtn: 'border-violet-200 bg-white text-violet-950 shadow-sm hover:bg-violet-50',
            };
        }
        return {
            attr: 'domestic',
            orderHeader: 'bg-orange-500 text-white',
            intlPill: '',
            boxBorder: 'border-orange-400',
            boxToolbar: 'bg-orange-50 border-orange-200',
            boxIcon: 'bg-orange-400',
            btnItem: 'bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1 rounded-lg',
            btnAddBox: 'bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded-lg text-sm inline-flex items-center gap-2',
            btnListCourier: 'bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded-lg text-sm',
            courierShipIcon: 'bg-orange-500',
            courierCountBadge: 'bg-orange-100 text-orange-800',
            courierPrice: 'text-orange-600',
            courierRadio: 'text-orange-500 focus:ring-orange-400',
            courierTileChecked: 'has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50/40 has-[:checked]:ring-2 has-[:checked]:ring-orange-400 has-[:checked]:ring-offset-1',
            courierTileHover: 'hover:border-orange-300',
            courierTopPick: 'bg-orange-500',
            loadingSpinner: 'border-orange-500',
            emptyPanelBorder: 'border-orange-200',
            emptyPanelGradient: 'from-orange-50/80',
            emptyHeaderBorder: 'border-orange-100',
            emptyHeaderBg: 'bg-white/70',
            emptyIconBg: 'bg-orange-100 text-orange-800',
            emptyTitle: 'text-gray-900',
            emptyText: 'text-gray-600',
            emptyBadge: 'bg-orange-100 text-orange-900',
            emptyToolbarBg: 'bg-orange-50/50 border-orange-100/80',
            emptyBtn: 'border-orange-200 bg-white text-gray-900 shadow-sm hover:bg-orange-50',
        };
    }

    function renderOrderCard() {
        const isIntl = !!payload.is_international;
        const tm = singleDispatchTheme(isIntl);
        
        let pickupOptsHtml = '<option value="Head Off" selected>Headoffice (Default)</option>';
        if (Array.isArray(payload.pickup_locations) && payload.pickup_locations.length > 0) {
            pickupOptsHtml = payload.pickup_locations.map(loc => {
                const val = loc.pickup_location || loc.address || loc.location_name || 'Head Off';
                const label = loc.display_name || loc.address || loc.location_name || val;
                return `<option value="${escapeHtml(val)}">${escapeHtml(label)}</option>`;
            }).join('');
        }

        let itemsRowsHtml = '';
        let totalItemsWeight = 0;
        if (Array.isArray(payload.items)) {
            payload.items.forEach(it => {
                const w = parseFloat(it.weight || 0.5);
                totalItemsWeight += w;
                itemsRowsHtml += `
                    <div class="grid grid-cols-12 gap-2 items-center py-2 border-b border-gray-100 text-xs item-row"
                         data-item-id="${it.id}"
                         data-item-code="${escapeHtml(it.item_code)}"
                         data-weight="${w}"
                         data-groupname="${escapeHtml(it.groupname)}">
                        <div class="col-span-2 font-medium">${escapeHtml(it.order_number || payload.order_number)}</div>
                        <div class="col-span-3 font-semibold text-gray-800 truncate">${escapeHtml(it.groupname)}</div>
                        <div class="col-span-2 text-right text-gray-600 font-mono">${escapeHtml(it.item_code)}</div>
                        <div class="col-span-1 text-right">${it.quantity || 1}</div>
                        <div class="col-span-2 text-right font-medium">${w.toFixed(3)} kg</div>
                        <div class="col-span-2 text-right font-semibold">₹${parseFloat(it.unit_price || 0).toFixed(2)}</div>
                    </div>
                `;
            });
        }

        if (totalItemsWeight <= 0) totalItemsWeight = 0.5;

        const orderCardHtml = `
            <div class="bg-white rounded-2xl shadow-sm border ${tm.boxBorder} overflow-hidden single-dispatch-card"
                 data-invoice-id="${payload.invoice_id}"
                 data-order-number="${escapeHtml(payload.order_number)}"
                 data-order-theme="${tm.attr}">
                
                <!-- Order Card Header -->
                <div class="${tm.orderHeader} px-6 py-4 flex flex-wrap justify-between items-center gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        ${tm.intlPill}
                        <h2 class="text-base font-bold truncate">
                            Order #${escapeHtml(payload.order_number)} · ${escapeHtml(payload.customer_name)}
                        </h2>
                    </div>
                    <div class="flex items-center gap-2 text-xs font-semibold bg-white/20 px-3 py-1.5 rounded-lg backdrop-blur">
                        <span>Invoice #${escapeHtml(payload.invoice_number || payload.invoice_id)}</span>
                    </div>
                </div>

                <!-- Shipping Address Strip -->
                <div class="px-6 py-3 bg-gray-50 border-b border-gray-200 text-xs text-gray-700 flex items-start gap-2">
                    <span class="font-bold shrink-0 text-gray-900">📍 Ship To:</span>
                    <span class="font-medium text-gray-800">${escapeHtml(payload.shipping_address)}</span>
                </div>

                <!-- Boxes Container -->
                <div class="boxes-container p-6 space-y-6">
                    <div class="bulk-dispatch-box border border-gray-200 rounded-xl overflow-hidden bg-white shadow-sm"
                         data-box-no="1"
                         data-order-number="${escapeHtml(payload.order_number)}">
                        
                        <!-- Box Toolbar -->
                        <div class="px-5 py-3 ${tm.boxToolbar} border-b border-gray-200 flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full ${tm.boxIcon} text-white text-xs font-bold">
                                    📦
                                </span>
                                <span class="font-bold text-gray-800 text-sm">Box 1</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-gray-700">Weight (kg):</span>
                                    <input type="number" name="weight" value="${totalItemsWeight.toFixed(3)}" step="0.1" min="0.1"
                                           class="weight-input border border-gray-300 rounded-md px-2 py-1 w-20 text-xs font-medium focus:ring-1 focus:ring-orange-500 outline-none"/>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-gray-700">Box Size:</span>
                                    <select class="BoxSize border border-gray-300 rounded-md px-2 py-1 text-xs w-36 focus:ring-1 focus:ring-orange-500 outline-none bg-white">
                                        <option value="R-1" data-length="22" data-width="17" data-height="5" selected>R-1 (22x17x5 in)</option>
                                        <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 in)</option>
                                        <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 in)</option>
                                        <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 in)</option>
                                        <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 in)</option>
                                        <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 in)</option>
                                        <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 in)</option>
                                        <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 in)</option>
                                        <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 in)</option>
                                        <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 in)</option>
                                        <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 in)</option>
                                        <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 in)</option>
                                        <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 in)</option>
                                        <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 in)</option>
                                        <option value="CUSTOM">Custom Dimensions</option>
                                    </select>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span class="font-semibold text-gray-700">Pickup:</span>
                                    <select class="pickup-location-select border border-gray-300 rounded-md px-2 py-1 text-xs w-44 focus:ring-1 focus:ring-orange-500 outline-none bg-white">
                                        ${pickupOptsHtml}
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Items Table Header -->
                        <div class="px-5 py-2.5 bg-gray-100/80 border-b border-gray-200 text-xs font-semibold text-gray-600">
                            <div class="grid grid-cols-12 gap-2">
                                <div class="col-span-2">Order</div>
                                <div class="col-span-3">Item Name</div>
                                <div class="col-span-2 text-right">SKU</div>
                                <div class="col-span-1 text-right">Qty</div>
                                <div class="col-span-2 text-right">Weight</div>
                                <div class="col-span-2 text-right">Price</div>
                            </div>
                        </div>

                        <!-- Items Rows Container -->
                        <div class="items-container px-5 divide-y divide-gray-100">
                            ${itemsRowsHtml}
                        </div>

                        <!-- Courier Serviceability Section -->
                        <div class="courier-serviceability-panel p-5 bg-gray-50/50 border-t border-gray-200">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-bold text-gray-800 uppercase tracking-wider flex items-center gap-1.5">
                                    🚚 Available Courier Rates
                                </span>
                                <button type="button" class="btn-list-courier ${tm.btnListCourier}">
                                    ⚡ Calculate Courier Rates
                                </button>
                            </div>
                            <div class="available-courier-container min-h-[60px] text-xs text-gray-500 flex items-center justify-center border border-dashed border-gray-300 rounded-xl p-4 bg-white">
                                Click "Calculate Courier Rates" to load live rates for this box.
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        `;

        container.innerHTML = orderCardHtml;
        bindCardEvents();
    }

    function bindCardEvents() {
        // List Couriers button
        const btnList = container.querySelector('.btn-list-courier');
        if (btnList) {
            btnList.addEventListener('click', function () {
                const boxEl = this.closest('.bulk-dispatch-box');
                if (boxEl) fetchCouriersForBox(boxEl);
            });
        }

        // Weight / Size change triggers courier rate refresh
        const weightInput = container.querySelector('.weight-input');
        const boxSizeSelect = container.querySelector('.BoxSize');
        const pickupSelect = container.querySelector('.pickup-location-select');

        if (weightInput) {
            weightInput.addEventListener('change', function () {
                const boxEl = this.closest('.bulk-dispatch-box');
                if (boxEl) fetchCouriersForBox(boxEl);
            });
        }

        if (boxSizeSelect) {
            boxSizeSelect.addEventListener('change', function () {
                const boxEl = this.closest('.bulk-dispatch-box');
                if (this.value === 'CUSTOM') {
                    openCustomBoxSizeModal(boxEl);
                } else if (boxEl) {
                    fetchCouriersForBox(boxEl);
                }
            });
        }

        if (pickupSelect) {
            pickupSelect.addEventListener('change', function () {
                const boxEl = this.closest('.bulk-dispatch-box');
                if (boxEl) fetchCouriersForBox(boxEl);
            });
        }

        // Auto fetch rates on initial page boot
        const initialBox = container.querySelector('.bulk-dispatch-box');
        if (initialBox) {
            setTimeout(() => fetchCouriersForBox(initialBox), 300);
        }
    }

    function openCustomBoxSizeModal(boxEl) {
        currentBoxElementForModal = boxEl;
        const modal = document.getElementById('customBoxSizeModal');
        if (modal) modal.classList.remove('hidden');
    }

    function closeCustomBoxSizeModal() {
        const modal = document.getElementById('customBoxSizeModal');
        if (modal) modal.classList.add('hidden');
    }

    // Modal close listeners
    document.querySelectorAll('[data-close-custom-modal]').forEach(btn => {
        btn.addEventListener('click', closeCustomBoxSizeModal);
    });

    const applyCustomBtn = document.getElementById('applyCustomBoxSizeBtn');
    if (applyCustomBtn) {
        applyCustomBtn.addEventListener('click', function () {
            const length = parseFloat(document.getElementById('modalCustomLength').value) || 0;
            const width = parseFloat(document.getElementById('modalCustomWidth').value) || 0;
            const height = parseFloat(document.getElementById('modalCustomHeight').value) || 0;

            if (length <= 0 || width <= 0 || height <= 0) {
                showPosModalMessage('Validation', 'Please enter valid custom dimensions in inches.', 'warning');
                return;
            }

            if (currentBoxElementForModal) {
                currentBoxElementForModal.dataset.customLength = length;
                currentBoxElementForModal.dataset.customWidth = width;
                currentBoxElementForModal.dataset.customHeight = height;
                closeCustomBoxSizeModal();
                fetchCouriersForBox(currentBoxElementForModal);
            }
        });
    }

    function fetchCouriersForBox(boxElement) {
        if (!boxElement) return;

        const isIntl = !!payload.is_international;
        const tm = singleDispatchTheme(isIntl);
        const courierContainer = boxElement.querySelector('.available-courier-container');
        if (!courierContainer) return;

        const orderNumber = payload.order_number;
        const weight = parseFloat(boxElement.querySelector('.weight-input')?.value) || 0.5;
        const pickupLocation = boxElement.querySelector('.pickup-location-select')?.value || 'Head Off';

        const sizeSelect = boxElement.querySelector('.BoxSize');
        let length = 22, width = 17, height = 5;

        if (sizeSelect && sizeSelect.value === 'CUSTOM') {
            length = parseFloat(boxElement.dataset.customLength) || 22;
            width = parseFloat(boxElement.dataset.customWidth) || 17;
            height = parseFloat(boxElement.dataset.customHeight) || 5;
        } else if (sizeSelect && sizeSelect.selectedIndex >= 0) {
            const opt = sizeSelect.options[sizeSelect.selectedIndex];
            length = parseFloat(opt.getAttribute('data-length')) || 22;
            width = parseFloat(opt.getAttribute('data-width')) || 17;
            height = parseFloat(opt.getAttribute('data-height')) || 5;
        }

        courierContainer.innerHTML = `
            <div class="flex items-center gap-2 text-gray-600 py-3 font-medium text-xs">
                <div class="w-4 h-4 border-2 ${tm.loadingSpinner} border-t-transparent rounded-full animate-spin"></div>
                <span>Fetching live courier rates for Box 1...</span>
            </div>
        `;

        const serviceabilityPayload = {
            order_number: orderNumber,
            weight: weight,
            length: length,
            breadth: width,
            height: height,
            pickup_location: pickupLocation,
            cod: 0
        };

        if (isIntl) {
            // International Aramex fetch
            fetch('?page=dispatch&action=getCourierServiceability', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify(serviceabilityPayload)
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.couriers || !data.couriers.length) {
                    courierContainer.innerHTML = `<div class="text-red-600 font-semibold p-2">${escapeHtml(data.message || 'No Aramex rates returned.')}</div>`;
                    checkCouriersSelected();
                    return;
                }
                renderCourierTiles(boxElement, courierContainer, tm, data.couriers, 'aramex');
            })
            .catch(err => {
                courierContainer.innerHTML = `<div class="text-red-600 font-semibold p-2">Error fetching rates: ${escapeHtml(err.message)}</div>`;
                checkCouriersSelected();
            });
        } else {
            // Domestic fetch (Shiprocket + Delhivery + Blue Dart in parallel)
            Promise.all([
                fetch('?page=dispatch&action=getCourierServiceability', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(serviceabilityPayload)
                }).then(r => r.json()).catch(e => ({ success: false, message: e.message })),

                fetch('?page=dispatch&action=getDirectCourierRates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ...serviceabilityPayload, partner_code: 'delhivery' })
                }).then(r => r.json()).catch(e => ({ success: false, message: e.message })),

                fetch('?page=dispatch&action=getDirectCourierRates', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify({ ...serviceabilityPayload, partner_code: 'bluedart' })
                }).then(r => r.json()).catch(e => ({ success: false, message: e.message }))
            ])
            .then(([srRes, delhiveryRes, bdRes]) => {
                let mergedCouriers = [];

                if (srRes && srRes.success && Array.isArray(srRes.couriers)) {
                    srRes.couriers.forEach(c => {
                        mergedCouriers.push({ ...c, partner_code: 'shiprocket', rate_source: 'shiprocket' });
                    });
                }
                if (delhiveryRes && delhiveryRes.success && Array.isArray(delhiveryRes.couriers)) {
                    delhiveryRes.couriers.forEach(c => {
                        mergedCouriers.push({ ...c, partner_code: 'delhivery', rate_source: 'delhivery' });
                    });
                }
                if (bdRes && bdRes.success && Array.isArray(bdRes.couriers)) {
                    bdRes.couriers.forEach(c => {
                        mergedCouriers.push({ ...c, partner_code: 'bluedart', rate_source: 'bluedart' });
                    });
                }

                if (mergedCouriers.length === 0) {
                    courierContainer.innerHTML = `<div class="text-red-600 font-semibold p-2">No domestic courier rates available for pincode/weight.</div>`;
                    checkCouriersSelected();
                    return;
                }

                // Sort by price ascending
                mergedCouriers.sort((a, b) => (parseFloat(a.price) || 0) - (parseFloat(b.price) || 0));

                renderCourierTiles(boxElement, courierContainer, tm, mergedCouriers, 'domestic');
            })
            .catch(err => {
                courierContainer.innerHTML = `<div class="text-red-600 font-semibold p-2">Error loading rates: ${escapeHtml(err.message)}</div>`;
                checkCouriersSelected();
            });
        }
    }

    function renderCourierTiles(boxElement, courierContainer, tm, couriers, mode) {
        const boxNo = boxElement.getAttribute('data-box-no') || '1';
        const groupName = 'courier_pick_box_' + boxNo;

        let tilesHtml = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 w-full">';

        couriers.forEach((courier, idx) => {
            const isTopPick = idx === 0;
            const currency = (courier.currency || 'INR').toUpperCase() === 'INR' ? '₹' : (courier.currency || '$');
            const priceVal = parseFloat(courier.price || 0).toFixed(2);
            const partnerCode = (courier.partner_code || 'shiprocket').toLowerCase();
            const rating = courier.rating ? (courier.rating + '/5') : 'N/A';
            const etd = courier.etd || 'N/A';
            const etdShort = (etd === 'N/A' || etd === '' || etd == null) ? '—' : String(etd);

            let providerBadge = '<span class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold text-violet-800 border border-violet-200">Shiprocket</span>';
            if (partnerCode === 'delhivery') {
                providerBadge = '<span class="inline-flex items-center rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-800 border border-red-200">Delhivery</span>';
            } else if (partnerCode === 'bluedart') {
                providerBadge = '<span class="inline-flex items-center rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-900 border border-sky-200">Blue Dart</span>';
            } else if (partnerCode === 'aramex') {
                providerBadge = '<span class="inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[10px] font-semibold text-orange-900 border border-orange-200">Aramex</span>';
            }

            tilesHtml += `
                <label class="relative flex flex-col justify-between p-3.5 rounded-xl border-2 border-gray-200 bg-white cursor-pointer transition ${tm.courierTileHover} ${tm.courierTileChecked}">
                    ${isTopPick ? `<span class="absolute -top-2.5 right-3 ${tm.courierTopPick} text-white text-[10px] font-extrabold px-2 py-0.5 rounded-full uppercase tracking-wider">Cheapest</span>` : ''}
                    <div>
                        <div class="flex items-center justify-between gap-2 mb-1.5">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="${groupName}" value="${escapeHtml(courier.id || idx)}" ${idx === 0 ? 'checked' : ''}
                                       class="courier-radio-input ${tm.courierRadio}"
                                       data-courier-name="${escapeHtml(courier.name)}"
                                       data-partner-code="${escapeHtml(courier.partner_code || 'shiprocket')}"
                                       data-partner-account-id="${escapeHtml(courier.partner_account_id || '')}"
                                       data-product-group="${escapeHtml(courier.product_group || '')}"
                                       data-product-type="${escapeHtml(courier.product_type || '')}"
                                       data-courier-etd="${escapeHtml(courier.etd || '')}"/>
                                <span class="font-bold text-gray-900 text-xs">${escapeHtml(courier.name)}</span>
                            </div>
                            ${providerBadge}
                        </div>
                        <div class="mt-2.5 flex flex-wrap gap-1.5">
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                <span class="text-slate-400">ETD</span> ${escapeHtml(etdShort)}
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-900 border border-amber-100">
                                <span class="text-amber-500 text-[11px]">★</span> ${escapeHtml(rating)}
                            </span>
                        </div>
                    </div>
                    <div class="mt-3 text-right border-t border-gray-100 pt-2">
                        <span class="text-base font-extrabold ${tm.courierPrice}">${currency} ${priceVal}</span>
                    </div>
                </label>
            `;
        });

        tilesHtml += '</div>';
        courierContainer.innerHTML = tilesHtml;

        // Bind radio change listeners
        courierContainer.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function () {
                updateBoxCourierData(boxElement, this);
                checkCouriersSelected();
            });
        });

        // Set initial selected courier data
        const initialChecked = courierContainer.querySelector('input[type="radio"]:checked');
        if (initialChecked) {
            updateBoxCourierData(boxElement, initialChecked);
        }

        checkCouriersSelected();
    }

    function updateBoxCourierData(boxElement, radio) {
        if (!boxElement || !radio) return;
        boxElement.dataset.partnerCode = radio.getAttribute('data-partner-code') || 'shiprocket';
        boxElement.dataset.partnerAccountId = radio.getAttribute('data-partner-account-id') || '';
        boxElement.dataset.productGroup = radio.getAttribute('data-product-group') || '';
        boxElement.dataset.productType = radio.getAttribute('data-product-type') || '';
        boxElement.dataset.courierName = radio.getAttribute('data-courier-name') || '';
        boxElement.dataset.courierEtd = radio.getAttribute('data-courier-etd') || '';
        boxElement.dataset.courierId = radio.value || '';
    }

    function checkCouriersSelected() {
        const submitBtn = document.getElementById('singleDispatchSubmitBtn');
        const bdExcelBtn = document.getElementById('downloadBlueDartExcelBtn');
        const bdHint = document.getElementById('bluedartExcelExportHint');

        if (!submitBtn) return;

        const boxes = container.querySelectorAll('.bulk-dispatch-box');
        let allSelected = true;
        let isBlueDartSelected = false;

        boxes.forEach(box => {
            const checkedRadio = box.querySelector('.available-courier-container input[type="radio"]:checked');
            if (!checkedRadio) {
                allSelected = false;
            } else if (checkedRadio.getAttribute('data-partner-code') === 'bluedart') {
                isBlueDartSelected = true;
            }
        });

        submitBtn.disabled = !allSelected;

        if (isBlueDartSelected) {
            if (bdExcelBtn) bdExcelBtn.classList.remove('hidden');
            if (bdHint) bdHint.classList.remove('hidden');
        } else {
            if (bdExcelBtn) bdExcelBtn.classList.add('hidden');
            if (bdHint) bdHint.classList.add('hidden');
        }
    }

    // Single Dispatch Form Submit
    const submitBtn = document.getElementById('singleDispatchSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', function () {
            if (this.disabled) return;

            const boxes = container.querySelectorAll('.bulk-dispatch-box');
            const boxPayloads = [];

            boxes.forEach((boxEl, idx) => {
                const bNo = idx + 1;
                const weight = parseFloat(boxEl.querySelector('.weight-input')?.value) || 0.5;
                const sizeSelect = boxEl.querySelector('.BoxSize');
                let length = 22, width = 17, height = 5;

                if (sizeSelect && sizeSelect.value === 'CUSTOM') {
                    length = parseFloat(boxEl.dataset.customLength) || 22;
                    width = parseFloat(boxEl.dataset.customWidth) || 17;
                    height = parseFloat(boxEl.dataset.customHeight) || 5;
                } else if (sizeSelect && sizeSelect.selectedIndex >= 0) {
                    const opt = sizeSelect.options[sizeSelect.selectedIndex];
                    length = parseFloat(opt.getAttribute('data-length')) || 22;
                    width = parseFloat(opt.getAttribute('data-width')) || 17;
                    height = parseFloat(opt.getAttribute('data-height')) || 5;
                }

                const pickupLocation = boxEl.querySelector('.pickup-location-select')?.value || 'Head Off';
                const checkedRadio = boxEl.querySelector('.available-courier-container input[type="radio"]:checked');

                const itemIds = [];
                boxEl.querySelectorAll('.item-row').forEach(r => {
                    const id = r.getAttribute('data-item-id');
                    if (id) itemIds.push(id);
                });

                boxPayloads.push({
                    box_no: bNo,
                    box_size: sizeSelect?.value || 'R-1',
                    length: length,
                    width: width,
                    height: height,
                    weight: weight,
                    items: itemIds,
                    partner_code: checkedRadio ? checkedRadio.getAttribute('data-partner-code') : 'shiprocket',
                    partner_account_id: checkedRadio ? checkedRadio.getAttribute('data-partner-account-id') : '',
                    courier_id: checkedRadio ? checkedRadio.value : '',
                    courier_name: checkedRadio ? checkedRadio.getAttribute('data-courier-name') : '',
                    courier_etd: checkedRadio ? checkedRadio.getAttribute('data-courier-etd') : '',
                    product_group: checkedRadio ? checkedRadio.getAttribute('data-product-group') : '',
                    product_type: checkedRadio ? checkedRadio.getAttribute('data-product-type') : '',
                    pickup_location: pickupLocation
                });
            });

            submitBtn.disabled = true;
            submitBtn.innerHTML = `<span>⌛</span> <span>Processing Dispatch...</span>`;

            fetch('?page=dispatch&action=create', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    invoice_id: payload.invoice_id,
                    boxes: boxPayloads
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.status === 'success') {
                    showPosModalMessage('Success', 'Dispatch processed successfully!', 'success');
                    setTimeout(() => {
                        window.location.reload();
                    }, 1200);
                } else {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = `<span>🚚</span> <span>Confirm &amp; Process Dispatch</span>`;
                    showPosModalMessage('Dispatch Failed', data.message || 'Failed to process dispatch.', 'error');
                }
            })
            .catch(err => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = `<span>🚚</span> <span>Confirm &amp; Process Dispatch</span>`;
                showPosModalMessage('Error', 'Network or server error: ' + err.message, 'error');
            });
        });
    }

    // Initialize Single Dispatch Card
    renderOrderCard();
})();

function generateEInvoice(invoiceId, btn) {
    if (!invoiceId) {
        showPosMessageModal({
            title: 'Error',
            message: 'Invalid Invoice ID.',
            tone: 'error'
        });
        return;
    }

    const originalText = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = `<span>⌛</span> <span>Generating E-Invoice...</span>`;
    }

    fetch('?page=invoices&action=regenerate_irn', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ invoice_id: invoiceId })
    })
    .then(r => r.json())
    .then(data => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        if (data.success) {
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({
                    title: 'E-Invoice Success',
                    message: data.message || 'E-Invoice (IRN) generated successfully!',
                    tone: 'success'
                });
            } else {
                alert(data.message || 'E-Invoice (IRN) generated successfully!');
            }
        } else {
            if (typeof window.showPosMessageModal === 'function') {
                window.showPosMessageModal({
                    title: 'E-Invoice Generation Failed',
                    message: data.message || 'Failed to generate E-Invoice.',
                    tone: 'error'
                });
            } else {
                alert(data.message || 'Failed to generate E-Invoice.');
            }
        }
    })
    .catch(err => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = originalText;
        }
        if (typeof window.showPosMessageModal === 'function') {
            window.showPosMessageModal({
                title: 'Error',
                message: 'Failed to trigger E-Invoice generation: ' + err.message,
                tone: 'error'
            });
        } else {
            alert('Failed to trigger E-Invoice generation: ' + err.message);
        }
    });
}
</script>
