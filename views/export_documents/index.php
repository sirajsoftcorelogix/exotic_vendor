<?php
/** @var array<string, string> $shipmentTypes */
/** @var array<string, string> $categories */
/** @var array<string, string> $courierPartners */
/** @var array<int, array<string, mixed>> $recentSessions */
/** @var int $totalSessions */
/** @var int $totalPages */
/** @var int $page */
/** @var array<string, mixed> $filters */
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6">
    <!-- Header Banner -->
    <div class="bg-gradient-to-r from-blue-900 to-indigo-800 rounded-xl shadow-lg p-6 text-white flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight flex items-center gap-2">
                <i class="fas fa-file-export text-blue-300"></i> Export Document Generator
            </h1>
            <p class="text-blue-100 text-sm mt-1">
                Enter invoice number to auto-pull order details, select shipment category, and generate customs documents.
            </p>
        </div>
        <div>
            <a href="#history-section" class="inline-flex items-center px-4 py-2 border border-blue-300 rounded-lg text-sm font-medium text-white hover:bg-white hover:text-blue-900 transition-colors">
                <i class="fas fa-history mr-2"></i> Session History
            </a>
        </div>
    </div>

    <!-- Main Configuration Form -->
    <form id="exportConfigForm" method="POST" action="index.php?page=export_documents&action=start_session" class="space-y-6">
        <!-- Step 1: Invoice & Order Auto-Pull -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">1</span>
                    Invoice / Order Selection
                </h2>
                <span class="text-xs text-gray-500 font-medium">Auto-populates Exporter, Consignee & Items</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                <div class="md:col-span-2 relative">
                    <label for="invoice_search" class="block text-sm font-medium text-gray-700 mb-1">
                        Invoice Number or Order Number <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="text" id="invoice_search" name="invoice_search" autocomplete="off" placeholder="e.g. INV-2026-0012 or ORD-98765..." required
                               value="<?= htmlspecialchars($initialQuery ?? '') ?>"
                               class="w-full pl-10 pr-10 py-2.5 bg-gray-50 border border-gray-300 rounded-lg text-gray-900 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white transition-all">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search"></i>
                        </div>
                        <button type="button" id="clearInvoiceSearchBtn" class="hidden absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times-circle"></i>
                        </button>
                    </div>
                    <!-- Autocomplete dropdown -->
                    <div id="autocompleteResults" class="hidden absolute z-30 w-full mt-1 bg-white rounded-lg shadow-xl border border-gray-200 max-h-60 overflow-y-auto"></div>
                </div>

                <div>
                    <button type="button" id="fetchInvoiceBtn" class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm flex items-center justify-center gap-2 transition-colors">
                        <i class="fas fa-sync-alt"></i> Auto-Pull Order Info
                    </button>
                </div>
            </div>

            <!-- Auto-pulled preview card -->
            <div id="invoicePreviewCard" class="hidden bg-blue-50/60 rounded-lg border border-blue-200 p-4 space-y-3">
                <div class="flex items-center justify-between border-b border-blue-200/80 pb-2">
                    <div class="flex items-center space-x-2">
                        <span id="previewInvoiceBadge" class="bg-blue-600 text-white text-xs font-bold px-2.5 py-0.5 rounded-full">--</span>
                        <span id="previewOrderBadge" class="text-xs text-blue-800 font-semibold">--</span>
                    </div>
                    <span id="previewAmountBadge" class="text-sm font-bold text-emerald-700">₹0.00</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-gray-700">
                    <div>
                        <span class="text-gray-500 block">Consignee Name</span>
                        <strong id="previewConsigneeName" class="text-gray-900">--</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Destination</span>
                        <strong id="previewDestination" class="text-gray-900">--</strong>
                    </div>
                    <div>
                        <span class="text-gray-500 block">Items Count</span>
                        <strong id="previewItemsCount" class="text-gray-900">0 Items</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Step 2: Shipment & Category Matrix Selector -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">2</span>
                    Export Matrix & Category Options
                </h2>
                <span class="text-xs text-gray-500 font-medium">Resolves exact document checklist</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Shipment Type -->
                <div>
                    <label for="shipment_type" class="block text-sm font-medium text-gray-700 mb-1">
                        Shipment Type <span class="text-red-500">*</span>
                    </label>
                    <select id="shipment_type" name="shipment_type" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <?php foreach ($shipmentTypes as $code => $label): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Product Category -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-1">
                        Product Category <span class="text-red-500">*</span>
                    </label>
                    <select id="category" name="category" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <?php foreach ($categories as $code => $label): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Courier Partner -->
                <div>
                    <label for="courier_partner" class="block text-sm font-medium text-gray-700 mb-1">
                        Courier / Carrier Partner <span class="text-red-500">*</span>
                    </label>
                    <select id="courier_partner" name="courier_partner" class="w-full bg-gray-50 border border-gray-300 rounded-lg py-2.5 px-3 text-sm focus:ring-2 focus:ring-blue-500 focus:bg-white">
                        <?php foreach ($courierPartners as $code => $label): ?>
                            <option value="<?= $code ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Checkbox options for incentives/declarations -->
            <div class="pt-2 flex flex-wrap gap-6 bg-gray-50/70 p-3.5 rounded-lg border border-gray-200/80">
                <label class="inline-flex items-center space-x-2 text-sm text-gray-800 cursor-pointer">
                    <input type="checkbox" id="is_drawback" name="is_drawback" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="font-medium">Duty Drawback Shipment</span>
                </label>
                <label class="inline-flex items-center space-x-2 text-sm text-gray-800 cursor-pointer">
                    <input type="checkbox" id="has_rodtep" name="has_rodtep" value="1" checked class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="font-medium">Include RODTEP Annexure</span>
                </label>
                <label class="inline-flex items-center space-x-2 text-sm text-gray-800 cursor-pointer">
                    <input type="checkbox" id="has_lacey" name="has_lacey" value="1" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="font-medium">Include Lacey Act Declaration (US Customs)</span>
                </label>
            </div>

            <!-- Dynamic Required Documents Checklist Preview Box -->
            <div class="border border-indigo-100 bg-indigo-50/50 rounded-lg p-4 space-y-2">
                <div class="flex items-center justify-between text-xs font-bold text-indigo-900 uppercase tracking-wider">
                    <span><i class="fas fa-list-check text-indigo-600 mr-1.5"></i> Documents to be Generated for this Order</span>
                    <span id="docCountBadge" class="bg-indigo-600 text-white px-2 py-0.5 rounded-full text-[10px]">0 Documents</span>
                </div>
                <div id="requiredDocsList" class="flex flex-wrap gap-2 pt-1">
                    <!-- Populated dynamically via JS -->
                </div>
            </div>
        </div>

        <!-- Step 3: Common Exporter & Consignee Details Form -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-gray-100 pb-3">
                <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    <span class="w-7 h-7 bg-blue-100 text-blue-800 rounded-full flex items-center justify-center text-xs font-bold">3</span>
                    Common Header & Address Information
                </h2>
                <span class="text-xs text-gray-500 font-medium">Verify or edit master details</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Exporter / Shipper Card -->
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                        <i class="fas fa-building text-blue-600"></i> Exporter (Shipper) Details
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div>
                            <label class="block text-gray-600 font-medium mb-0.5">Exporter Name</label>
                            <input type="text" name="common[exporter_name]" id="field_exporter_name" value="<?= htmlspecialchars($exporterDefaults['exporter_name'] ?? 'EXOTIC INDIA ART PVT LTD') ?>"
                                   class="w-full bg-gray-100 text-gray-600 border border-gray-200 rounded px-2.5 py-1.5 text-xs cursor-not-allowed" readonly>
                        </div>
                        <div>
                            <label class="block text-gray-600 font-medium mb-0.5">Exporter Address</label>
                            <input type="text" name="common[exporter_address]" id="field_exporter_address" value="<?= htmlspecialchars($exporterDefaults['exporter_address'] ?? '101, Plaza A-1, Paschim Vihar') ?>"
                                   class="w-full bg-gray-100 text-gray-600 border border-gray-200 rounded px-2.5 py-1.5 text-xs cursor-not-allowed" readonly>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-gray-600 font-medium mb-0.5">City / State</label>
                                <input type="text" name="common[exporter_city]" id="field_exporter_city" value="<?= htmlspecialchars($exporterDefaults['exporter_city'] ?? 'New Delhi') ?>"
                                       class="w-full bg-gray-100 text-gray-600 border border-gray-200 rounded px-2.5 py-1.5 text-xs cursor-not-allowed" readonly>
                            </div>
                            <div>
                                <label class="block text-gray-600 font-medium mb-0.5">IEC Number</label>
                                <input type="text" name="common[exporter_iec]" id="field_exporter_iec" value="<?= htmlspecialchars($exporterDefaults['exporter_iec'] ?? '0505012345') ?>"
                                       class="w-full bg-gray-100 text-gray-600 border border-gray-200 rounded px-2.5 py-1.5 text-xs cursor-not-allowed" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Consignee / Buyer Card -->
                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 space-y-3">
                    <h3 class="text-sm font-bold text-gray-800 flex items-center gap-1.5">
                        <i class="fas fa-user-tag text-emerald-600"></i> Consignee (Buyer) Details
                    </h3>
                    <div class="space-y-2 text-xs">
                        <div>
                            <label class="block text-gray-600 font-medium mb-0.5">Consignee Name</label>
                            <input type="text" name="common[consignee_name]" id="field_consignee_name" value="" placeholder="Consignee Name"
                                   class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                        </div>
                        <div>
                            <label class="block text-gray-600 font-medium mb-0.5">Consignee Street Address</label>
                            <input type="text" name="common[consignee_address_line1]" id="field_consignee_address_line1" value="" placeholder="Address Line 1"
                                   class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-gray-600 font-medium mb-0.5">City / Destination</label>
                                <input type="text" name="common[consignee_city]" id="field_consignee_city" value="" placeholder="City"
                                       class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                            </div>
                            <div>
                                <label class="block text-gray-600 font-medium mb-0.5">Country</label>
                                <input type="text" name="common[consignee_country]" id="field_consignee_country" value="" placeholder="Country"
                                       class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ports & Exchange Info -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 bg-gray-50/70 p-4 rounded-lg border border-gray-200/80 text-xs">
                <div>
                    <label class="block text-gray-600 font-medium mb-0.5">Port of Loading</label>
                    <input type="text" name="common[port_of_loading]" id="field_port_of_loading" value="New Delhi (INABG1)"
                           class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-0.5">Port of Discharge</label>
                    <input type="text" name="common[port_of_discharge]" id="field_port_of_discharge" value="" placeholder="Discharge Port / City"
                           class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-0.5">Invoice Currency</label>
                    <input type="text" name="common[currency]" id="field_currency" value="USD"
                           class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                </div>
                <div>
                    <label class="block text-gray-600 font-medium mb-0.5">Exchange Rate (INR)</label>
                    <input type="number" step="0.01" name="common[exchange_rate]" id="field_exchange_rate" value="83.50"
                           class="w-full bg-white border border-gray-300 rounded px-2.5 py-1.5 text-xs text-gray-900">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex justify-end">
                <button type="submit" id="submitStartSessionBtn" class="py-3 px-6 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-bold text-sm rounded-lg shadow-md flex items-center gap-2 transition-all">
                    <span>Proceed to Document Generation Wizard</span> <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </form>

    <!-- History / Recent Sessions Table -->
    <div id="history-section" class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-gray-100 pb-3">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i class="fas fa-folder-open text-blue-600"></i> Recent Export Document Sessions
            </h2>
            <form method="GET" action="index.php" class="flex items-center gap-2">
                <input type="hidden" name="page" value="export_documents">
                <input type="text" name="search" value="<?= htmlspecialchars($filters['search']) ?>" placeholder="Search session / invoice..." class="text-xs border border-gray-300 rounded px-2.5 py-1.5 focus:ring-1 focus:ring-blue-500">
                <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-medium px-3 py-1.5 rounded border border-gray-300">
                    Filter
                </button>
            </form>
        </div>

        <div class="overflow-visible">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200 text-gray-600 font-semibold uppercase tracking-wider">
                        <th class="p-3">Session Code</th>
                        <th class="p-3">Invoice #</th>
                        <th class="p-3">Order #</th>
                        <th class="p-3">Shipment Type</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Carrier</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Date</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($recentSessions)): ?>
                        <tr>
                            <td colspan="9" class="p-6 text-center text-gray-500">
                                No export sessions created yet. Use the generator above to start.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentSessions as $sess): ?>
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="p-3 font-mono font-semibold text-blue-800">
                                    <?= htmlspecialchars($sess['session_code']) ?>
                                </td>
                                <td class="p-3 font-medium text-gray-900">
                                    <?= htmlspecialchars($sess['invoice_number'] ?: '--') ?>
                                </td>
                                <td class="p-3 text-gray-600">
                                    <?= htmlspecialchars($sess['order_number'] ?: '--') ?>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-blue-100 text-blue-800 uppercase">
                                        <?= htmlspecialchars($sess['shipment_type']) ?>
                                    </span>
                                </td>
                                <td class="p-3 text-gray-700">
                                    <?= htmlspecialchars($categories[$sess['category']] ?? $sess['category']) ?>
                                </td>
                                <td class="p-3 text-gray-700 font-medium">
                                    <?= htmlspecialchars($courierPartners[$sess['courier_partner']] ?? $sess['courier_partner']) ?>
                                </td>
                                <td class="p-3">
                                    <?php if ($sess['status'] === 'completed'): ?>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">Completed</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-3 text-gray-500">
                                    <?= date('M d, Y', strtotime($sess['created_at'])) ?>
                                </td>
                                <td class="p-3 text-right export-session-action-cell">
                                    <div class="relative inline-block text-left">
                                        <button type="button"
                                                onclick="toggleExportSessionMenu(this, event)"
                                                class="export-session-menu-btn inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:text-gray-900 hover:border-gray-300 transition focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                aria-haspopup="true"
                                                aria-expanded="false"
                                                title="Session actions">
                                            <i class="fas fa-ellipsis-v text-xs" aria-hidden="true"></i>
                                        </button>
                                        <div class="export-session-menu-panel hidden absolute right-0 mt-1 w-40 bg-white border border-gray-200 rounded-xl shadow-lg z-30 p-1 text-left space-y-0.5">
                                            <a href="index.php?page=export_documents&action=generate&session_code=<?= urlencode($sess['session_code']) ?>"
                                               class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded-lg transition-colors">
                                                <i class="fas fa-edit w-4 text-center text-blue-600"></i> Edit Wizard
                                            </a>
                                            <a href="index.php?page=export_documents&action=preview&session_code=<?= urlencode($sess['session_code']) ?>" target="_blank"
                                               class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 rounded-lg transition-colors">
                                                <i class="fas fa-print w-4 text-center text-emerald-600"></i> Preview
                                            </a>
                                            <button type="button"
                                                    onclick="deleteExportSession(<?= (int)$sess['id'] ?>, '<?= htmlspecialchars($sess['session_code'], ENT_QUOTES, 'UTF-8') ?>')"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50 hover:text-red-700 rounded-lg transition-colors text-left">
                                                <i class="fas fa-trash-alt w-4 text-center text-red-500"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Page Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('invoice_search');
    const autocompleteBox = document.getElementById('autocompleteResults');
    const fetchBtn = document.getElementById('fetchInvoiceBtn');
    const clearBtn = document.getElementById('clearInvoiceSearchBtn');

    // Matrix selects & checkboxes
    const shipmentTypeSelect = document.getElementById('shipment_type');
    const categorySelect = document.getElementById('category');
    const courierSelect = document.getElementById('courier_partner');
    const drawbackCheck = document.getElementById('is_drawback');
    const rodtepCheck = document.getElementById('has_rodtep');
    const laceyCheck = document.getElementById('has_lacey');

    let searchTimeout = null;

    // Autocomplete handler
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            if (clearBtn) clearBtn.classList.toggle('hidden', query === '');

            if (query.length < 2) {
                if (autocompleteBox) autocompleteBox.classList.add('hidden');
                return;
            }

            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                fetch('index.php?page=export_documents&action=autocomplete&term=' + encodeURIComponent(query), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.results.length > 0) {
                        let html = '';
                        data.results.forEach(item => {
                            html += `<div class="p-2.5 hover:bg-blue-50 cursor-pointer border-b border-gray-100 text-xs font-medium text-gray-800" onclick="selectInvoiceResult('${item.invoice_number || item.order_number}')">
                                        <div class="font-bold text-blue-900">${item.invoice_number} ${item.order_number ? '<span class="text-gray-500 font-normal">(' + item.order_number + ')</span>' : ''}</div>
                                        <div class="text-[11px] text-gray-600">${item.customer_name} - ${item.country}</div>
                                     </div>`;
                        });
                        autocompleteBox.innerHTML = html;
                        autocompleteBox.classList.remove('hidden');
                    } else {
                        autocompleteBox.innerHTML = '<div class="p-3 text-xs text-gray-500">No matching invoice found</div>';
                        autocompleteBox.classList.remove('hidden');
                    }
                })
                .catch(() => {
                    if (autocompleteBox) autocompleteBox.classList.add('hidden');
                });
            }, 250);
        });
    }

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            searchInput.value = '';
            this.classList.add('hidden');
            if (autocompleteBox) autocompleteBox.classList.add('hidden');
        });
    }

    window.selectInvoiceResult = function (val) {
        if (searchInput) searchInput.value = val;
        if (autocompleteBox) autocompleteBox.classList.add('hidden');
        triggerFetchInvoice();
    };

    if (fetchBtn) {
        fetchBtn.addEventListener('click', triggerFetchInvoice);
    }

    // Trigger fetch invoice data & update matrix
    function triggerFetchInvoice() {
        const query = searchInput ? searchInput.value.trim() : '';
        if (!query) {
            if (window.showPosMessageModal) {
                window.showPosMessageModal({
                    title: 'Invoice Required',
                    message: 'Please enter or select an Invoice or Order number first.',
                    tone: 'warning'
                });
            } else {
                alert('Please enter or select an Invoice or Order number first.');
            }
            return;
        }

        const url = `index.php?page=export_documents&action=fetch_invoice&query=${encodeURIComponent(query)}`
                  + `&shipment_type=${encodeURIComponent(shipmentTypeSelect.value)}`
                  + `&category=${encodeURIComponent(categorySelect.value)}`
                  + `&courier_partner=${encodeURIComponent(courierSelect.value)}`
                  + `&is_drawback=${drawbackCheck.checked ? '1' : ''}`
                  + `&has_rodtep=${rodtepCheck.checked ? '1' : ''}`
                  + `&has_lacey=${laceyCheck.checked ? '1' : ''}`;

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                if (window.showPosMessageModal) {
                    window.showPosMessageModal({
                        title: 'Invoice Not Found',
                        message: data.message || 'Could not fetch order details.',
                        tone: 'error'
                    });
                } else {
                    alert(data.message || 'Could not fetch order details.');
                }
                return;
            }

            // Update preview card
            const card = document.getElementById('invoicePreviewCard');
            if (card) card.classList.remove('hidden');

            const invBadge = document.getElementById('previewInvoiceBadge');
            if (invBadge) invBadge.textContent = data.auto_pulled.invoice.invoice_number || 'INVOICE';

            const ordBadge = document.getElementById('previewOrderBadge');
            if (ordBadge) ordBadge.textContent = data.auto_pulled.invoice.order_number ? ('Order: ' + data.auto_pulled.invoice.order_number) : '';

            const amtBadge = document.getElementById('previewAmountBadge');
            if (amtBadge) amtBadge.textContent = (data.auto_pulled.invoice.currency || 'USD') + ' ' + parseFloat(data.auto_pulled.invoice.total_amount || 0).toFixed(2);

            const custName = document.getElementById('previewConsigneeName');
            if (custName) custName.textContent = data.common_data.consignee_name || 'N/A';

            const dest = document.getElementById('previewDestination');
            if (dest) dest.textContent = data.common_data.consignee_country || 'N/A';

            const itemsCnt = document.getElementById('previewItemsCount');
            if (itemsCnt) itemsCnt.textContent = (data.auto_pulled.items ? data.auto_pulled.items.length : 0) + ' Item(s)';

            // Populate common form fields
            const setVal = (id, val) => {
                const el = document.getElementById(id);
                if (el) el.value = (val !== undefined && val !== null) ? val : '';
            };

            setVal('field_exporter_name', data.common_data.exporter_name);
            setVal('field_exporter_address', data.common_data.exporter_address);
            setVal('field_exporter_city', data.common_data.exporter_city);
            setVal('field_exporter_iec', data.common_data.exporter_iec);
            setVal('field_consignee_name', data.common_data.consignee_name);
            setVal('field_consignee_address_line1', data.common_data.consignee_address_line1);
            setVal('field_consignee_city', data.common_data.consignee_city);
            setVal('field_consignee_country', data.common_data.consignee_country);
            setVal('field_port_of_discharge', data.common_data.port_of_discharge);
            setVal('field_currency', data.common_data.currency);
            setVal('field_exchange_rate', data.common_data.exchange_rate);

            // Update required documents checklist
            updateRequiredDocsList(data.required_documents);
        })
        .catch(err => {
            console.error(err);
        });
    }

    // Refresh required docs list on matrix dropdown changes
    [shipmentTypeSelect, categorySelect, courierSelect, drawbackCheck, rodtepCheck, laceyCheck].forEach(el => {
        if (el) {
            el.addEventListener('change', refreshMatrixChecklist);
        }
    });

    function refreshMatrixChecklist() {
        const query = searchInput ? searchInput.value.trim() : '';
        if (query) {
            triggerFetchInvoice();
        } else {
            // Compute matrix without query
            const shipmentType = shipmentTypeSelect.value;
            const category = categorySelect.value;
            const courier = courierSelect.value;
            const isUPS = (courier.toLowerCase() === 'ups');

            let docs = {};
            if (shipmentType === 'csb5') {
                docs['csb5_invoice'] = 'Invoice CSB-5';
                if (category === 'sculpture_painting_home') {
                    docs['origin_cert'] = 'Certificate of Origin';
                    docs['declaration_work_of_art'] = 'Work of Art Declaration';
                } else if (category === 'books') {
                    docs['declaration_book'] = 'Book Non-Objection Declaration';
                } else if (category === 'audio_cd') {
                    docs['declaration_audio_film'] = 'Audio / Film Declaration';
                } else if (category === 'handloom') {
                    docs['origin_cert'] = 'Certificate of Origin';
                    docs['declaration_textile'] = 'Handloom / Textile Declaration';
                }
                if (isUPS) docs['sli_ups_csb5'] = 'UPS CSB-5 SLI';
            } else {
                docs['commercial_invoice'] = 'Commercial Invoice';
                docs['sli_commercial'] = 'Shipper\'s Letter of Instruction (SLI) - ' + (drawbackCheck.checked ? 'Drawback' : 'Non-Drawback');
                docs['origin_cert'] = 'Certificate of Origin';
                if (rodtepCheck.checked) docs['rodtep_annexure'] = 'RODTEP Annexure';

                if (category === 'sculpture_painting_home') {
                    docs['declaration_work_of_art'] = 'Work of Art Declaration';
                    if (laceyCheck.checked) docs['declaration_lacey'] = 'Lacey Act Declaration';
                } else if (category === 'books') {
                    docs['declaration_book'] = 'Book Non-Objection Declaration';
                } else if (category === 'handloom') {
                    docs['declaration_textile'] = 'Handloom / Textile Declaration';
                }
            }
            updateRequiredDocsList(docs);
        }
    }

    function updateRequiredDocsList(docs) {
        const container = document.getElementById('requiredDocsList');
        const badge = document.getElementById('docCountBadge');
        if (!container) return;

        const keys = Object.keys(docs || {});
        if (badge) badge.textContent = keys.length + ' Document(s)';

        if (keys.length === 0) {
            container.innerHTML = '<span class="text-xs text-gray-500 italic">No documents selected</span>';
            return;
        }

        let html = '';
        keys.forEach(k => {
            html += `<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-white border border-indigo-200 text-indigo-900 shadow-2xs">
                        <i class="fas fa-file-check text-emerald-600 mr-1.5"></i> ${docs[k]}
                     </span>`;
        });
        html += `<span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-amber-50 border border-amber-200 text-amber-900 shadow-2xs" title="Air Waybill (AWB) & Customs CSB Shipping Bill are provided electronically by courier service">
                    <i class="fas fa-info-circle text-amber-600 mr-1.5"></i> AWB & CSB Shipping Bill: Supplied by Courier Service (Skipped)
                 </span>`;
        container.innerHTML = html;
    }

    // Initial matrix checklist rendering & auto-fetch if search query passed in URL
    if (searchInput && searchInput.value.trim() !== '') {
        if (clearBtn) clearBtn.classList.remove('hidden');
        triggerFetchInvoice();
    } else {
        refreshMatrixChecklist();
    }

    window.toggleExportSessionMenu = function(button, event) {
        if (event) event.stopPropagation();
        if (!button) return;
        const menu = button.nextElementSibling;
        if (!menu) return;
        document.querySelectorAll('.export-session-menu-panel').forEach(m => {
            if (m !== menu) m.classList.add('hidden');
        });
        menu.classList.toggle('hidden');
    };

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.export-session-action-cell')) {
            document.querySelectorAll('.export-session-menu-panel').forEach(menu => {
                menu.classList.add('hidden');
            });
        }
    });

    window.deleteExportSession = function (sessionId, sessionCode) {
        const confirmFn = (typeof customConfirm === 'function')
            ? customConfirm
            : function (msg) { return Promise.resolve(window.confirm(msg)); };

        confirmFn('Are you sure you want to delete session ' + sessionCode + '? All draft documents for this session will be permanently deleted.', {
            title: 'Delete Export Session',
            okText: 'Delete',
            cancelText: 'Cancel'
        }).then(function (confirmed) {
            if (!confirmed) return;

            const formData = new FormData();
            formData.append('id', sessionId);

            fetch('index.php?page=export_documents&action=delete_session', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (window.showPosMessageModal) {
                        window.showPosMessageModal({
                            title: 'Deleted',
                            message: data.message || 'Session deleted successfully.',
                            tone: 'success',
                            onClose: function () { window.location.reload(); }
                        });
                    } else {
                        window.location.reload();
                    }
                } else {
                    if (window.showPosMessageModal) {
                        window.showPosMessageModal({
                            title: 'Error',
                            message: data.message || 'Could not delete session.',
                            tone: 'error'
                        });
                    } else {
                        alert(data.message || 'Could not delete session.');
                    }
                }
            })
            .catch(err => {
                console.error(err);
            });
        });
    };
});
</script>
