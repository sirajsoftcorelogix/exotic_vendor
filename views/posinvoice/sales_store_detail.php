<?php
$initialFilters = is_array($initial_filters ?? null) ? $initial_filters : [];
?>
<div class="min-h-screen bg-gray-50">

    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold"><?= htmlspecialchars((string) ($warehouse_name ?? 'Store')) ?></h1>
                <p class="text-xs text-gray-500 mt-0.5">Daily POS sales breakdown by invoice date.</p>
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
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            .ps-filter-input:focus {
                outline: none;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18);
            }
        </style>

        <input type="hidden" id="detail_warehouse_id" value="<?= (int) ($warehouse_id ?? 0) ?>">

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

        <p id="posStoreDetailResultSummary" class="text-sm text-gray-600 mb-3">
            <span class="font-medium text-gray-900">—</span> days
        </p>

        <div class="bg-white rounded-xl border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="p-3 text-left">Invoice date</th>
                        <th class="p-3 text-right">Invoices</th>
                        <th class="p-3 text-right">Net sales</th>
                        <th class="p-3 text-right">Discounts</th>
                        <th class="p-3 text-right">Collected</th>
                        <th class="p-3 text-right">Pending</th>
                        <th class="p-3 text-right">Gross</th>
                        <th class="p-3 text-right">Avg ticket</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>
                <tbody id="posStoreDetailTable">
                    <tr>
                        <td colspan="9" class="p-6 text-center text-gray-400">Loading daily breakdown…</td>
                    </tr>
                </tbody>
                <tfoot id="posStoreDetailTotals" class="bg-orange-50/70 border-t-2 border-orange-200 text-sm font-semibold hidden">
                </tfoot>
            </table>
        </div>

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

    function buildInvoiceListingUrl(summaryDate) {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: 'list',
            warehouse_id: String(POS_STORE_DETAIL_WAREHOUSE_ID),
            from_date: summaryDate || document.getElementById('from_date').value,
            to_date: summaryDate || document.getElementById('to_date').value,
            type: document.getElementById('type').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });
        return '?' + params.toString();
    }

    function renderDetailRow(row, isTotal) {
        const label = isTotal ? 'TOTAL' : (row.summary_date || '—');
        const rowClass = isTotal ? 'bg-orange-50/70 font-semibold' : 'border-t hover:bg-gray-50';
        const actionCell = isTotal ? '' : `
            <a href="${buildInvoiceListingUrl(row.summary_date)}"
               class="inline-flex items-center gap-1 text-orange-700 hover:text-orange-900 text-xs font-semibold"
               title="View invoices for this date">
                <i class="fas fa-list-ul text-[10px]" aria-hidden="true"></i>
                Invoices
            </a>`;

        return `
            <tr class="${rowClass}">
                <td class="p-3">${label}</td>
                <td class="p-3 text-right tabular-nums">${formatCount(row.invoice_count)}</td>
                <td class="p-3 text-right tabular-nums">₹ ${formatMoney(row.net_sales)}</td>
                <td class="p-3 text-right tabular-nums text-amber-700">₹ ${formatMoney(row.discount_total)}</td>
                <td class="p-3 text-right tabular-nums text-green-700">₹ ${formatMoney(row.collected_total)}</td>
                <td class="p-3 text-right tabular-nums text-red-600">₹ ${formatMoney(row.pending_total)}</td>
                <td class="p-3 text-right tabular-nums text-gray-700">₹ ${formatMoney(row.gross_total)}</td>
                <td class="p-3 text-right tabular-nums">₹ ${formatMoney(row.avg_ticket)}</td>
                <td class="p-3">${actionCell}</td>
            </tr>`;
    }

    function loadStoreDetail() {
        const tbody = document.getElementById('posStoreDetailTable');
        const tfoot = document.getElementById('posStoreDetailTotals');
        const summaryEl = document.getElementById('posStoreDetailResultSummary');

        if (summaryEl) {
            summaryEl.innerHTML = '<span class="text-gray-500">Loading daily breakdown…</span>';
        }
        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading…</td></tr>';
        }
        if (tfoot) {
            tfoot.innerHTML = '';
            tfoot.classList.add('hidden');
        }

        fetch(buildStoreDetailAjaxQuery(), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.success === false && data.message) {
                    if (tbody) {
                        tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-red-500">' + data.message + '</td></tr>';
                    }
                    if (summaryEl) {
                        summaryEl.innerHTML = '<span class="text-red-500">Could not load data.</span>';
                    }
                    return;
                }

                const rows = Array.isArray(data.rows) ? data.rows : [];
                const totals = data.totals || {};

                if (summaryEl) {
                    const n = rows.length;
                    summaryEl.innerHTML = '<span class="font-medium text-gray-900">' + n.toLocaleString() + '</span> ' + (n === 1 ? 'day' : 'days');
                }

                if (!tbody) {
                    return;
                }

                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-gray-400">No daily sales match the current filters.</td></tr>';
                    return;
                }

                let html = '';
                rows.forEach(function (row) {
                    html += renderDetailRow(row, false);
                });
                tbody.innerHTML = html;

                if (tfoot) {
                    tfoot.innerHTML = renderDetailRow(Object.assign({}, totals, { summary_date: 'TOTAL' }), true);
                    tfoot.classList.remove('hidden');
                }
            })
            .catch(function () {
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-red-500">Could not load daily breakdown. Please try again.</td></tr>';
                }
                if (summaryEl) {
                    summaryEl.innerHTML = '<span class="text-red-500">Could not load data.</span>';
                }
            });
    }
</script>
