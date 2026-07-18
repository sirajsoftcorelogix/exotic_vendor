<?php
$initialFilters = is_array($initial_filters ?? null) ? $initial_filters : [];
?>
<div class="min-h-screen bg-gray-50">

    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold"><?= htmlspecialchars((string) ($warehouse_name ?? 'Store')) ?></h1>
                <p class="text-xs text-gray-500 mt-0.5">Sales summary for this store — totals and breakdowns, not individual invoices.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="<?= htmlspecialchars('?' . http_build_query([
                    'page' => 'posinvoice',
                    'action' => 'sales_summary',
                    'from_date' => $initialFilters['from_date'] ?? '',
                    'to_date' => $initialFilters['to_date'] ?? '',
                    'type' => $initialFilters['type'] ?? '',
                    'discount_applied' => $initialFilters['discount_applied'] ?? '',
                    'status' => $initialFilters['status'] ?? '',
                ])) ?>"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                    All stores summary
                </a>
                <a id="posStoreInvoiceListingLink" href="?page=posinvoice&action=list"
                   class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-800 hover:bg-orange-100">
                    <i class="fas fa-list-ul text-xs" aria-hidden="true"></i>
                    Invoice listing
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1500px] px-4 py-5">

        <style>
            #pos-store-detail-filters > summary { list-style: none; }
            #pos-store-detail-filters > summary::-webkit-details-marker { display: none; }
            #pos-store-detail-filters[open] > summary { border-bottom: 1px solid rgba(254, 215, 170, 0.85); }
            #pos-store-detail-filters:not([open]) .psdf-label-open { display: none; }
            #pos-store-detail-filters[open] .psdf-label-closed { display: none; }
            #pos-store-detail-filters[open] .psdf-chevron { transform: rotate(180deg); }
            .ps-filter-input {
                width: 100%;
                padding: 0.625rem 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                color: #111827;
                background: #fff;
            }
            .ps-filter-input:focus {
                outline: none;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18);
            }
            .ps-kpi-card {
                border-radius: 0.75rem;
                border: 1px solid #e5e7eb;
                background: #fff;
                padding: 1rem 1.125rem;
            }
            .ps-summary-table th { font-size: 0.7rem; }
        </style>

        <details id="pos-store-detail-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-4 ring-1 ring-gray-900/[0.03]" open>
            <summary class="px-5 py-4 bg-gradient-to-r from-orange-50/60 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-orange-700 shadow-sm border border-orange-100">
                        <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900">Filters</h2>
                        <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Invoice date range, payment type, discount flag, and status.</p>
                    </div>
                </div>
                <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-orange-800">
                    <span class="psdf-label-closed">Show</span>
                    <span class="psdf-label-open">Hide</span>
                    <i class="psdf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
                </span>
            </summary>

            <form id="posStoreDetailFiltersForm" class="p-5" autocomplete="off">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                    <div>
                        <label for="from_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice from</label>
                        <input type="date" id="from_date" class="ps-filter-input" value="<?= htmlspecialchars((string) ($initialFilters['from_date'] ?? '')) ?>">
                    </div>
                    <div>
                        <label for="to_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice to</label>
                        <input type="date" id="to_date" class="ps-filter-input" value="<?= htmlspecialchars((string) ($initialFilters['to_date'] ?? '')) ?>">
                    </div>
                    <div>
                        <label for="type" class="block text-xs font-semibold text-gray-600 mb-1">Payment type</label>
                        <select id="type" class="ps-filter-input">
                            <option value="">All payment types</option>
                            <option value="offline" <?= (($initialFilters['type'] ?? '') === 'offline') ? 'selected' : '' ?>>Offline</option>
                            <option value="cod" <?= (($initialFilters['type'] ?? '') === 'cod') ? 'selected' : '' ?>>Cash</option>
                            <option value="razorpay" <?= (($initialFilters['type'] ?? '') === 'razorpay') ? 'selected' : '' ?>>Razorpay</option>
                            <option value="bank_transfer" <?= (($initialFilters['type'] ?? '') === 'bank_transfer') ? 'selected' : '' ?>>Bank transfer</option>
                        </select>
                    </div>
                    <div>
                        <label for="discount_applied" class="block text-xs font-semibold text-gray-600 mb-1">Discount applied</label>
                        <select id="discount_applied" class="ps-filter-input">
                            <option value="">All</option>
                            <option value="1" <?= (($initialFilters['discount_applied'] ?? '') === '1') ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (($initialFilters['discount_applied'] ?? '') === '0') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">Invoice status</label>
                        <select id="status" class="ps-filter-input">
                            <option value="">All statuses</option>
                            <option value="draft" <?= (($initialFilters['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Draft</option>
                            <option value="proforma" <?= (($initialFilters['status'] ?? '') === 'proforma') ? 'selected' : '' ?>>Proforma</option>
                            <option value="final" <?= (($initialFilters['status'] ?? '') === 'final') ? 'selected' : '' ?>>Final</option>
                            <option value="cancelled" <?= (($initialFilters['status'] ?? '') === 'cancelled') ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 transition shadow-sm">
                        <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                        Apply filters
                    </button>
                    <a href="?page=posinvoice&action=sales_store_detail&detail_warehouse_id=<?= (int) ($warehouse_id ?? 0) ?>"
                       class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                        <i class="fas fa-rotate-left text-xs opacity-80" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </details>

        <div id="posStoreDetailLoading" class="text-sm text-gray-500 mb-4">Loading store summary…</div>

        <div id="posStoreDetailContent" class="hidden space-y-6">
            <section>
                <h2 class="text-sm font-semibold text-gray-900 mb-3">Overview</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3">
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Invoices</p>
                        <p id="kpiInvoiceCount" class="mt-1 text-xl font-bold text-gray-900 tabular-nums">—</p>
                    </div>
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Net sales</p>
                        <p id="kpiNetSales" class="mt-1 text-xl font-bold text-gray-900 tabular-nums">—</p>
                    </div>
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Discounts</p>
                        <p id="kpiDiscounts" class="mt-1 text-xl font-bold text-amber-700 tabular-nums">—</p>
                    </div>
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Collected</p>
                        <p id="kpiCollected" class="mt-1 text-xl font-bold text-green-700 tabular-nums">—</p>
                    </div>
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Pending</p>
                        <p id="kpiPending" class="mt-1 text-xl font-bold text-red-600 tabular-nums">—</p>
                    </div>
                    <div class="ps-kpi-card">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Avg ticket</p>
                        <p id="kpiAvgTicket" class="mt-1 text-xl font-bold text-gray-900 tabular-nums">—</p>
                    </div>
                </div>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <section class="bg-white rounded-xl border overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">By payment type</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ps-summary-table w-full text-sm">
                            <thead class="bg-gray-100 text-xs">
                                <tr>
                                    <th class="p-3 text-left">Payment type</th>
                                    <th class="p-3 text-right">Invoices</th>
                                    <th class="p-3 text-right">Net sales</th>
                                    <th class="p-3 text-right">Collected</th>
                                    <th class="p-3 text-right">Pending</th>
                                </tr>
                            </thead>
                            <tbody id="summaryByPaymentType"></tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded-xl border overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">By invoice status</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ps-summary-table w-full text-sm">
                            <thead class="bg-gray-100 text-xs">
                                <tr>
                                    <th class="p-3 text-left">Status</th>
                                    <th class="p-3 text-right">Invoices</th>
                                    <th class="p-3 text-right">Net sales</th>
                                    <th class="p-3 text-right">Discounts</th>
                                    <th class="p-3 text-right">Pending</th>
                                </tr>
                            </thead>
                            <tbody id="summaryByStatus"></tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded-xl border overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">By discount</h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ps-summary-table w-full text-sm">
                            <thead class="bg-gray-100 text-xs">
                                <tr>
                                    <th class="p-3 text-left">Discount</th>
                                    <th class="p-3 text-right">Invoices</th>
                                    <th class="p-3 text-right">Net sales</th>
                                    <th class="p-3 text-right">Discount amount</th>
                                    <th class="p-3 text-right">Avg ticket</th>
                                </tr>
                            </thead>
                            <tbody id="summaryByDiscount"></tbody>
                        </table>
                    </div>
                </section>

                <section class="bg-white rounded-xl border overflow-hidden">
                    <div class="px-4 py-3 border-b bg-gray-50">
                        <h2 class="text-sm font-semibold text-gray-900">Daily totals</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Aggregated sales per invoice date — not individual invoice rows.</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="ps-summary-table w-full text-sm">
                            <thead class="bg-gray-100 text-xs">
                                <tr>
                                    <th class="p-3 text-left">Date</th>
                                    <th class="p-3 text-right">Invoices</th>
                                    <th class="p-3 text-right">Net sales</th>
                                    <th class="p-3 text-right">Discounts</th>
                                    <th class="p-3 text-right">Collected</th>
                                </tr>
                            </thead>
                            <tbody id="summaryByDate"></tbody>
                            <tfoot id="summaryByDateTotals" class="bg-orange-50/70 border-t-2 border-orange-200 text-sm font-semibold hidden"></tfoot>
                        </table>
                    </div>
                </section>
            </div>
        </div>

        <div id="posStoreDetailError" class="hidden rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"></div>

    </main>
</div>

<script>
    const POS_STORE_DETAIL_WAREHOUSE_ID = <?= (int) ($warehouse_id ?? 0) ?>;

    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('posStoreDetailFiltersForm');
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                loadStoreDetail();
            });
        }
        updateInvoiceListingLink();
        loadStoreDetail();
    });

    function formatMoney(value) {
        const n = parseFloat(value);
        if (Number.isNaN(n)) {
            return '0.00';
        }
        return n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function formatCount(value) {
        const n = Number(value) || 0;
        return n.toLocaleString('en-IN');
    }

    function moneyCell(value, className) {
        return `<td class="p-3 text-right tabular-nums ${className || ''}">₹ ${formatMoney(value)}</td>`;
    }

    function buildStoreDetailAjaxQuery() {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: 'sales_store_detail_ajax',
            detail_warehouse_id: String(POS_STORE_DETAIL_WAREHOUSE_ID),
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value,
            type: document.getElementById('type').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });
        return '?' + params.toString();
    }

    function buildInvoiceListingUrl() {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: 'list',
            warehouse_id: String(POS_STORE_DETAIL_WAREHOUSE_ID),
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value,
            type: document.getElementById('type').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });
        return '?' + params.toString();
    }

    function updateInvoiceListingLink() {
        const link = document.getElementById('posStoreInvoiceListingLink');
        if (link) {
            link.href = buildInvoiceListingUrl();
        }
    }

    function renderSummaryRows(rows, columns, emptyColspan, emptyMessage) {
        if (!rows || rows.length === 0) {
            return `<tr><td colspan="${emptyColspan}" class="p-6 text-center text-gray-400">${emptyMessage}</td></tr>`;
        }
        let html = '';
        rows.forEach(function (row) {
            html += '<tr class="border-t hover:bg-gray-50">';
            columns.forEach(function (col) {
                html += col(row);
            });
            html += '</tr>';
        });
        return html;
    }

    function renderOverview(overview) {
        const data = overview || {};
        document.getElementById('kpiInvoiceCount').textContent = formatCount(data.invoice_count);
        document.getElementById('kpiNetSales').textContent = '₹ ' + formatMoney(data.net_sales);
        document.getElementById('kpiDiscounts').textContent = '₹ ' + formatMoney(data.discount_total);
        document.getElementById('kpiCollected').textContent = '₹ ' + formatMoney(data.collected_total);
        document.getElementById('kpiPending').textContent = '₹ ' + formatMoney(data.pending_total);
        document.getElementById('kpiAvgTicket').textContent = '₹ ' + formatMoney(data.avg_ticket);
    }

    function loadStoreDetail() {
        const loadingEl = document.getElementById('posStoreDetailLoading');
        const contentEl = document.getElementById('posStoreDetailContent');
        const errorEl = document.getElementById('posStoreDetailError');

        if (loadingEl) loadingEl.classList.remove('hidden');
        if (contentEl) contentEl.classList.add('hidden');
        if (errorEl) errorEl.classList.add('hidden');
        updateInvoiceListingLink();

        fetch(buildStoreDetailAjaxQuery(), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (loadingEl) loadingEl.classList.add('hidden');

                if (data.success === false && data.message) {
                    if (errorEl) {
                        errorEl.textContent = data.message;
                        errorEl.classList.remove('hidden');
                    }
                    return;
                }

                if (contentEl) contentEl.classList.remove('hidden');
                renderOverview(data.overview);

                document.getElementById('summaryByPaymentType').innerHTML = renderSummaryRows(
                    (data.by_payment_type || {}).rows || [],
                    [
                        function (row) { return `<td class="p-3 font-medium text-gray-900">${row.group_label || '—'}</td>`; },
                        function (row) { return `<td class="p-3 text-right tabular-nums">${formatCount(row.invoice_count)}</td>`; },
                        function (row) { return moneyCell(row.net_sales); },
                        function (row) { return moneyCell(row.collected_total, 'text-green-700'); },
                        function (row) { return moneyCell(row.pending_total, 'text-red-600'); },
                    ],
                    5,
                    'No payment type data for these filters.'
                );

                document.getElementById('summaryByStatus').innerHTML = renderSummaryRows(
                    (data.by_status || {}).rows || [],
                    [
                        function (row) { return `<td class="p-3 font-medium text-gray-900">${row.group_label || '—'}</td>`; },
                        function (row) { return `<td class="p-3 text-right tabular-nums">${formatCount(row.invoice_count)}</td>`; },
                        function (row) { return moneyCell(row.net_sales); },
                        function (row) { return moneyCell(row.discount_total, 'text-amber-700'); },
                        function (row) { return moneyCell(row.pending_total, 'text-red-600'); },
                    ],
                    5,
                    'No status data for these filters.'
                );

                document.getElementById('summaryByDiscount').innerHTML = renderSummaryRows(
                    (data.by_discount || {}).rows || [],
                    [
                        function (row) { return `<td class="p-3 font-medium text-gray-900">${row.group_label || '—'}</td>`; },
                        function (row) { return `<td class="p-3 text-right tabular-nums">${formatCount(row.invoice_count)}</td>`; },
                        function (row) { return moneyCell(row.net_sales); },
                        function (row) { return moneyCell(row.discount_total, 'text-amber-700'); },
                        function (row) { return moneyCell(row.avg_ticket); },
                    ],
                    5,
                    'No discount breakdown for these filters.'
                );

                const dateRows = (data.by_date || {}).rows || [];
                const dateTotals = (data.by_date || {}).totals || {};
                document.getElementById('summaryByDate').innerHTML = renderSummaryRows(
                    dateRows,
                    [
                        function (row) { return `<td class="p-3 font-medium text-gray-900">${row.group_label || row.summary_date || '—'}</td>`; },
                        function (row) { return `<td class="p-3 text-right tabular-nums">${formatCount(row.invoice_count)}</td>`; },
                        function (row) { return moneyCell(row.net_sales); },
                        function (row) { return moneyCell(row.discount_total, 'text-amber-700'); },
                        function (row) { return moneyCell(row.collected_total, 'text-green-700'); },
                    ],
                    5,
                    'No daily totals for these filters.'
                );

                const dateFoot = document.getElementById('summaryByDateTotals');
                if (dateFoot) {
                    if (dateRows.length > 0) {
                        dateFoot.innerHTML = `
                            <tr class="font-semibold">
                                <td class="p-3">TOTAL</td>
                                <td class="p-3 text-right tabular-nums">${formatCount(dateTotals.invoice_count)}</td>
                                <td class="p-3 text-right tabular-nums">₹ ${formatMoney(dateTotals.net_sales)}</td>
                                <td class="p-3 text-right tabular-nums text-amber-700">₹ ${formatMoney(dateTotals.discount_total)}</td>
                                <td class="p-3 text-right tabular-nums text-green-700">₹ ${formatMoney(dateTotals.collected_total)}</td>
                            </tr>`;
                        dateFoot.classList.remove('hidden');
                    } else {
                        dateFoot.innerHTML = '';
                        dateFoot.classList.add('hidden');
                    }
                }
            })
            .catch(function () {
                if (loadingEl) loadingEl.classList.add('hidden');
                if (errorEl) {
                    errorEl.textContent = 'Could not load store summary. Please try again.';
                    errorEl.classList.remove('hidden');
                }
            });
    }
</script>
