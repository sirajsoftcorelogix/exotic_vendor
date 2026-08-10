<?php
/** @var array $data */
$filters     = $data['filters'] ?? [];
$vouchers    = $data['vouchers'] ?? [];
$pagination  = $data['pagination'] ?? [];
$kpis        = $data['kpis'] ?? [];
$isAdmin     = !empty($data['is_admin']);

$startDate   = htmlspecialchars($filters['start_date'] ?? '');
$endDate     = htmlspecialchars($filters['end_date'] ?? '');
$voucherType = htmlspecialchars($filters['voucher_type'] ?? 'all');
$search      = htmlspecialchars($filters['search'] ?? '');

$today       = date('Y-m-d');
$yesterday   = date('Y-m-d', strtotime('-1 day'));
$firstMonth  = date('Y-m-01');
$lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
$lastMonthEnd   = date('Y-m-t', strtotime('last day of last month'));

$downloadZipUrl = base_url("?page=busy_accounting&action=download_xml_batch&start_date={$startDate}&end_date={$endDate}&voucher_type={$voucherType}&search=" . urlencode($search) . "&format=zip");
$downloadConsolidatedUrl = base_url("?page=busy_accounting&action=download_xml_batch&start_date={$startDate}&end_date={$endDate}&voucher_type={$voucherType}&search=" . urlencode($search) . "&format=consolidated");
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-indigo-200/50 bg-gradient-to-br from-indigo-900 via-slate-900 to-indigo-950 text-white shadow-lg p-6 sm:p-8 mb-6">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-500/20 text-indigo-200 border border-indigo-400/30">
                        <i class="fas fa-calculator"></i> Bookkeeping & ERP
                    </span>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        <i class="fas fa-check-circle"></i> BUSY API Integrated (Org-wide)
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mt-3">BUSY Accounting & Reconciliation</h1>
                <p class="mt-2 text-sm text-indigo-200/90 max-w-3xl">
                    Organization-wide review and audit of all sales invoices and sales returns sharing the same company GSTIN. Download BUSY-compatible XML batches or verify JSON API payloads.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <a href="<?= $downloadZipUrl ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-md transition-all">
                    <i class="fas fa-file-archive"></i> Download ZIP Batch
                </a>
                <a href="<?= $downloadConsolidatedUrl ?>" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-100 border border-slate-700 text-sm font-semibold transition-all">
                    <i class="fas fa-file-code"></i> Consolidated XML
                </a>
            </div>
        </div>
    </div>

    <!-- KPI Metric Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <!-- Gross Sales -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Gross Sales</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600"><i class="fas fa-file-invoice"></i></span>
            </div>
            <div>
                <div class="text-2xl font-bold text-slate-900">₹<?= number_format($kpis['gross_sales'] ?? 0, 2) ?></div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    <span class="text-emerald-600 font-semibold"><?= (int)($kpis['sales_count'] ?? 0) ?></span> Sales Invoices
                </div>
            </div>
        </div>

        <!-- Sales Returns -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Sales Returns</span>
                <span class="p-2 rounded-lg bg-orange-50 text-orange-600"><i class="fas fa-undo-alt"></i></span>
            </div>
            <div>
                <div class="text-2xl font-bold text-orange-700">₹<?= number_format($kpis['sales_returns'] ?? 0, 2) ?></div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    <span class="text-orange-600 font-semibold"><?= (int)($kpis['return_count'] ?? 0) ?></span> Credit Notes
                </div>
            </div>
        </div>

        <!-- Net Revenue -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Net Taxable Revenue</span>
                <span class="p-2 rounded-lg bg-blue-50 text-blue-600"><i class="fas fa-chart-line"></i></span>
            </div>
            <div>
                <div class="text-2xl font-bold text-blue-900">₹<?= number_format($kpis['net_revenue'] ?? 0, 2) ?></div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    Sales minus Returns
                </div>
            </div>
        </div>

        <!-- Total GST -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200/80 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between text-slate-500 mb-2">
                <span class="text-xs font-semibold uppercase tracking-wider">Net GST Amount</span>
                <span class="p-2 rounded-lg bg-purple-50 text-purple-600"><i class="fas fa-receipt"></i></span>
            </div>
            <div>
                <div class="text-2xl font-bold text-purple-900">₹<?= number_format($kpis['net_tax'] ?? 0, 2) ?></div>
                <div class="mt-1 text-xs text-slate-500 font-medium">
                    Output GST minus Return Credit GST
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Controls Bar -->
    <form method="get" class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6">
        <input type="hidden" name="page" value="busy_accounting">
        <input type="hidden" name="action" value="index">

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Start Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="start_date" id="filter_start_date" value="<?= $startDate ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- End Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">To Date</label>
                <input type="date" name="end_date" id="filter_end_date" value="<?= $endDate ?>"
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Voucher Type -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Voucher Type</label>
                <select name="voucher_type" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="all" <?= $voucherType === 'all' ? 'selected' : '' ?>>All Vouchers (Invoices & Returns)</option>
                    <option value="sales" <?= $voucherType === 'sales' ? 'selected' : '' ?>>Sales Invoices Only</option>
                    <option value="sales_return" <?= $voucherType === 'sales_return' ? 'selected' : '' ?>>Sales Returns Only</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Search</label>
                <input type="text" name="search" value="<?= $search ?>"
                       placeholder="Voucher #, Customer, GSTIN..."
                       class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>
        </div>

        <!-- Quick Date Range Presets -->
        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-1.5 text-xs text-slate-600">
                <span class="font-semibold mr-1">Quick Presets:</span>
                <button type="button" onclick="setDates('<?= $today ?>', '<?= $today ?>')" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Today</button>
                <button type="button" onclick="setDates('<?= $yesterday ?>', '<?= $yesterday ?>')" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Yesterday</button>
                <button type="button" onclick="setDates('<?= $firstMonth ?>', '<?= $today ?>')" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">This Month</button>
                <button type="button" onclick="setDates('<?= $lastMonthStart ?>', '<?= $lastMonthEnd ?>')" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium">Last Month</button>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm transition-all">
                    <i class="fas fa-filter text-xs mr-1"></i> Apply Filters
                </button>
                <a href="?page=busy_accounting&action=index" class="px-4 py-2 rounded-lg border border-slate-300 text-slate-700 text-sm font-medium hover:bg-slate-50 transition-all">
                    Reset
                </a>
            </div>
        </div>
    </form>

    <!-- Vouchers Data Table -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="font-bold text-slate-800 text-base">Accounting Vouchers Log</h2>
            <span class="text-xs text-slate-500 font-medium">Showing <?= count($vouchers) ?> of <?= (int)($pagination['total_records'] ?? 0) ?> vouchers</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-700 uppercase text-[11px] tracking-wider font-semibold border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Type</th>
                        <th class="px-4 py-3">Voucher No</th>
                        <th class="px-4 py-3">Ref / Invoice No</th>
                        <th class="px-4 py-3">Customer / Party</th>
                        <th class="px-4 py-3">GSTIN</th>
                        <th class="px-4 py-3 text-right">Taxable</th>
                        <th class="px-4 py-3 text-right">GST</th>
                        <th class="px-4 py-3 text-right">Total Amt</th>
                        <th class="px-4 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!empty($vouchers)): ?>
                        <?php foreach ($vouchers as $v): ?>
                            <?php 
                            $isReturn = $v['voucher_type'] === 'sales_return'; 
                            $badgeStyle = $isReturn 
                                ? 'bg-orange-100 text-orange-800 border-orange-200' 
                                : 'bg-emerald-100 text-emerald-800 border-emerald-200';
                            ?>
                            <tr class="hover:bg-slate-50/80 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-slate-600 font-medium">
                                    <?= date('d M Y', strtotime($v['voucher_date'])) ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $badgeStyle ?>">
                                        <i class="fas <?= $isReturn ? 'fa-undo' : 'fa-file-invoice' ?> text-[10px]"></i>
                                        <?= $isReturn ? 'Sales Return' : 'Sales Invoice' ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono font-bold text-slate-800">
                                    <?= htmlspecialchars($v['voucher_no']) ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap font-mono text-xs text-slate-500">
                                    <?= htmlspecialchars($v['ref_order_no']) ?>
                                </td>
                                <td class="px-4 py-3 font-medium text-slate-900">
                                    <?= htmlspecialchars($v['customer_name']) ?>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-slate-500">
                                    <?= htmlspecialchars($v['gstin']) ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-slate-700 whitespace-nowrap">
                                    ₹<?= number_format($v['taxable_amount'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-purple-700 whitespace-nowrap">
                                    ₹<?= number_format($v['tax_amount'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-slate-900 whitespace-nowrap">
                                    ₹<?= number_format($v['total_amount'], 2) ?>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap space-x-2">
                                    <button type="button" 
                                            onclick="inspectVoucher(<?= $v['id'] ?>, '<?= $v['voucher_type'] ?>')"
                                            class="inline-flex items-center gap-1 px-3 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold border border-indigo-200 transition-all">
                                        <i class="fas fa-eye"></i> Inspect
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="px-6 py-12 text-center text-slate-500">
                                <i class="fas fa-folder-open text-3xl text-slate-300 mb-2"></i>
                                <p class="font-medium text-slate-600">No accounting vouchers found.</p>
                                <p class="text-xs text-slate-400 mt-1">Try expanding your date range or clearing filters.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if (($pagination['total_pages'] ?? 1) > 1): ?>
            <div class="px-5 py-4 border-t border-slate-100 flex items-center justify-center space-x-2">
                <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                    <a href="?page=busy_accounting&action=index&start_date=<?= $startDate ?>&end_date=<?= $endDate ?>&voucher_type=<?= $voucherType ?>&search=<?= urlencode($search) ?>&page_no=<?= $i ?>"
                       class="px-3.5 py-1.5 rounded-lg text-xs font-semibold transition-all <?= $i == $pagination['current_page'] ? 'bg-indigo-600 text-white shadow-sm' : 'bg-slate-100 text-slate-700 hover:bg-slate-200' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Inspection Drawer -->
<div id="voucherInspectModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl border border-slate-200 max-w-4xl w-full max-h-[90vh] flex flex-col overflow-hidden">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <h3 id="modalVoucherTitle" class="font-bold text-lg text-slate-900">Voucher Details</h3>
                <p id="modalVoucherSubtitle" class="text-xs text-slate-500">BUSY Line Items & JSON Payload Inspector</p>
            </div>
            <button type="button" onclick="closeInspectModal()" class="text-slate-400 hover:text-slate-600 p-2 text-lg">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <!-- Modal Tabs -->
        <div class="px-6 pt-3 border-b border-slate-100 flex gap-4 bg-slate-50/30">
            <button type="button" id="tabItemsBtn" onclick="switchTab('items')" class="pb-3 text-xs font-bold border-b-2 border-indigo-600 text-indigo-600">
                <i class="fas fa-list mr-1"></i> Line Items & Summary
            </button>
            <button type="button" id="tabXmlBtn" onclick="switchTab('xml')" class="pb-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                <i class="fas fa-file-code mr-1"></i> BUSY XML Output
            </button>
            <button type="button" id="tabJsonBtn" onclick="switchTab('json')" class="pb-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800">
                <i class="fas fa-code mr-1"></i> BUSY JSON Output
            </button>
        </div>

        <!-- Modal Content Body -->
        <div class="p-6 overflow-y-auto flex-1">
            <!-- Loading Spinner -->
            <div id="modalLoading" class="text-center py-12">
                <i class="fas fa-circle-notch fa-spin text-3xl text-indigo-600"></i>
                <p class="mt-2 text-xs font-medium text-slate-500">Loading voucher details...</p>
            </div>

            <!-- Tab 1: Line Items -->
            <div id="tabItemsContent" class="hidden">
                <div id="customerHeaderBox" class="p-4 rounded-xl bg-slate-50 border border-slate-200/80 mb-4 text-xs grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <!-- Populated via JS -->
                </div>

                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 text-slate-700 font-semibold uppercase text-[10px]">
                            <tr>
                                <th class="px-3 py-2">Item Name / Code</th>
                                <th class="px-3 py-2">HSN</th>
                                <th class="px-3 py-2 text-right">Qty</th>
                                <th class="px-3 py-2 text-right">Unit Price</th>
                                <th class="px-3 py-2 text-right">GST %</th>
                                <th class="px-3 py-2 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody id="itemsTableBody" class="divide-y divide-slate-100">
                            <!-- Populated via JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tab 2: BUSY XML Output -->
            <div id="tabXmlContent" class="hidden">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-semibold text-slate-600">Exported XML Structure (BUSY POS/Inventory Schema)</span>
                    <button type="button" onclick="copyXmlPayload()" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        <i class="fas fa-copy mr-1"></i> Copy XML
                    </button>
                </div>
                <pre id="xmlPreBlock" class="p-4 rounded-xl bg-slate-950 text-amber-300 font-mono text-xs overflow-x-auto max-h-80 border border-slate-800"></pre>
            </div>

            <!-- Tab 3: BUSY JSON Output -->
            <div id="tabJsonContent" class="hidden">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-semibold text-slate-600">Exported JSON Structure (`api_fetch_vouchers`)</span>
                    <button type="button" onclick="copyJsonPayload()" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">
                        <i class="fas fa-copy mr-1"></i> Copy JSON
                    </button>
                </div>
                <pre id="jsonPreBlock" class="p-4 rounded-xl bg-slate-950 text-emerald-400 font-mono text-xs overflow-x-auto max-h-80 border border-slate-800"></pre>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50/50 flex justify-end">
            <button type="button" onclick="closeInspectModal()" class="px-4 py-2 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-800 text-xs font-semibold">
                Close
            </button>
        </div>
    </div>
</div>

<script>
let currentXmlData = null;
let currentJsonData = null;

function setDates(start, end) {
    document.getElementById('filter_start_date').value = start;
    document.getElementById('filter_end_date').value = end;
}

function inspectVoucher(id, type) {
    const modal = document.getElementById('voucherInspectModal');
    const loading = document.getElementById('modalLoading');
    const tabItems = document.getElementById('tabItemsContent');
    const tabXml = document.getElementById('tabXmlContent');
    const tabJson = document.getElementById('tabJsonContent');

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    loading.classList.remove('hidden');
    tabItems.classList.add('hidden');
    tabXml.classList.add('hidden');
    tabJson.classList.add('hidden');

    switchTab('items');

    fetch(`?page=busy_accounting&action=get_details_ajax&id=${id}&type=${type}`)
        .then(r => r.json())
        .then(res => {
            loading.classList.add('hidden');
            if (!res.success) {
                if (window.showPosMessageModal) {
                    showPosMessageModal({ title: 'Error', message: res.message || 'Failed to load details', tone: 'error' });
                } else {
                    console.error(res.message);
                }
                closeInspectModal();
                return;
            }

            currentXmlData = res.xml_preview || '';
            currentJsonData = res.json_preview || {};

            document.getElementById('xmlPreBlock').textContent = res.xml_preview || '';
            document.getElementById('jsonPreBlock').textContent = JSON.stringify(res.json_preview || {}, null, 2);

            const details = res.details;
            const isReturn = (type === 'sales_return');

            document.getElementById('modalVoucherTitle').textContent = isReturn 
                ? `Sales Return: ${details.return_number || ''}` 
                : `Invoice: ${details.invoice_number || ''}`;

            document.getElementById('modalVoucherSubtitle').textContent = isReturn
                ? `Original Invoice Ref: ${details.invoice_number || 'None'} | Return Date: ${details.return_date || ''}`
                : `Invoice Date: ${details.invoice_date || ''} | Currency: ${details.currency || 'INR'}`;

            // Populate Customer Header Box
            const custName = (details.first_name || '') + ' ' + (details.last_name || '');
            document.getElementById('customerHeaderBox').innerHTML = `
                <div><span class="text-slate-400 block font-medium">Customer:</span><strong class="text-slate-800">${custName || 'Walk-in Customer'}</strong></div>
                <div><span class="text-slate-400 block font-medium">Customer GSTIN:</span><strong class="text-slate-800">${details.gstin || 'N/A'}</strong></div>
                <div><span class="text-slate-400 block font-medium">Currency / Scope:</span><strong class="text-slate-800">${details.currency || 'INR'} (Org-wide)</strong></div>
            `;

            // Populate Items Table
            const tbody = document.getElementById('itemsTableBody');
            tbody.innerHTML = '';

            const items = details.items || [];
            items.forEach(it => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50';

                const name = it.item_name || it.item_code || 'Item';
                const hsn = it.hsn || '—';
                const qty = parseFloat(it.quantity || it.return_qty || 0);
                const price = parseFloat(it.unit_price || 0);
                const gst = parseFloat(it.tax_rate || it.tax_percent || 0);
                const total = (qty * price).toFixed(2);

                tr.innerHTML = `
                    <td class="px-3 py-2 font-medium text-slate-800">${escapeHtml(name)}</td>
                    <td class="px-3 py-2 font-mono text-slate-500">${escapeHtml(hsn)}</td>
                    <td class="px-3 py-2 text-right font-bold text-slate-900">${qty}</td>
                    <td class="px-3 py-2 text-right text-slate-700">₹${price.toFixed(2)}</td>
                    <td class="px-3 py-2 text-right text-purple-700 font-semibold">${gst}%</td>
                    <td class="px-3 py-2 text-right font-bold text-slate-900">₹${total}</td>
                `;
                tbody.appendChild(tr);
            });

            tabItems.classList.remove('hidden');
        })
        .catch(err => {
            console.error(err);
            loading.classList.add('hidden');
            closeInspectModal();
        });
}

function switchTab(tab) {
    const tabItemsBtn = document.getElementById('tabItemsBtn');
    const tabXmlBtn   = document.getElementById('tabXmlBtn');
    const tabJsonBtn  = document.getElementById('tabJsonBtn');

    const tabItemsContent = document.getElementById('tabItemsContent');
    const tabXmlContent   = document.getElementById('tabXmlContent');
    const tabJsonContent  = document.getElementById('tabJsonContent');

    const activeClass   = 'pb-3 text-xs font-bold border-b-2 border-indigo-600 text-indigo-600';
    const inactiveClass = 'pb-3 text-xs font-bold border-b-2 border-transparent text-slate-500 hover:text-slate-800';

    tabItemsBtn.className = (tab === 'items') ? activeClass : inactiveClass;
    tabXmlBtn.className   = (tab === 'xml')   ? activeClass : inactiveClass;
    tabJsonBtn.className  = (tab === 'json')  ? activeClass : inactiveClass;

    tabItemsContent.classList.toggle('hidden', tab !== 'items');
    tabXmlContent.classList.toggle('hidden', tab !== 'xml');
    tabJsonContent.classList.toggle('hidden', tab !== 'json');
}

function closeInspectModal() {
    const modal = document.getElementById('voucherInspectModal');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
}

function copyXmlPayload() {
    if (!currentXmlData) return;
    navigator.clipboard.writeText(currentXmlData)
        .then(() => {
            if (window.showPosMessageModal) {
                showPosMessageModal({ title: 'Copied', message: 'BUSY XML output copied to clipboard', tone: 'success' });
            }
        });
}

function copyJsonPayload() {
    if (!currentJsonData) return;
    navigator.clipboard.writeText(JSON.stringify(currentJsonData, null, 2))
        .then(() => {
            if (window.showPosMessageModal) {
                showPosMessageModal({ title: 'Copied', message: 'BUSY JSON payload copied to clipboard', tone: 'success' });
            }
        });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
