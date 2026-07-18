<div class="min-h-screen bg-gray-50">

    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <h1 class="text-lg font-semibold">POS Sales Summary</h1>
                <p class="text-xs text-gray-500 mt-0.5">Net sales, discounts, and collections grouped by store (invoice date basis).</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="?page=posinvoice&action=list"
                   class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                    Invoice listing
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1500px] px-4 py-5">

        <style>
            #pos-sales-filters > summary { list-style: none; }
            #pos-sales-filters > summary::-webkit-details-marker { display: none; }
            #pos-sales-filters[open] > summary { border-bottom: 1px solid rgba(254, 215, 170, 0.85); }
            #pos-sales-filters:not([open]) .psf-label-open { display: none; }
            #pos-sales-filters[open] .psf-label-closed { display: none; }
            #pos-sales-filters[open] .psf-chevron { transform: rotate(180deg); }
            .ps-filter-input {
                width: 100%;
                padding: 0.625rem 0.75rem;
                border: 1px solid #d1d5db;
                border-radius: 0.5rem;
                font-size: 0.875rem;
                color: #111827;
                background: #fff;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }
            .ps-filter-input:focus {
                outline: none;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18);
            }
        </style>

        <?php if (empty($can_change_warehouse) && !empty($session_warehouse_name)): ?>
        <div class="mb-4 inline-flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-900">
            <i class="fas fa-store text-orange-700" aria-hidden="true"></i>
            <span>Showing summary for <strong><?= htmlspecialchars((string) $session_warehouse_name) ?></strong> only.</span>
        </div>
        <?php endif; ?>

        <details id="pos-sales-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-4 ring-1 ring-gray-900/[0.03]" open>
            <summary class="px-5 py-4 bg-gradient-to-r from-orange-50/60 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-orange-700 shadow-sm border border-orange-100">
                        <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900">Filters</h2>
                        <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Invoice date range, store, payment type, discount flag, and status.</p>
                    </div>
                </div>
                <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-orange-800">
                    <span id="posSalesActiveFilterCount" class="hidden rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-900"></span>
                    <span class="psf-label-closed">Show</span>
                    <span class="psf-label-open">Hide</span>
                    <i class="psf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
                </span>
            </summary>

            <form id="posSalesFiltersForm" class="p-5" autocomplete="off">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                    <div>
                        <label for="from_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice from</label>
                        <input type="date" id="from_date" class="ps-filter-input">
                    </div>
                    <div>
                        <label for="to_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice to</label>
                        <input type="date" id="to_date" class="ps-filter-input">
                    </div>
                    <?php if (!empty($can_change_warehouse)): ?>
                    <div>
                        <label for="warehouse_id" class="block text-xs font-semibold text-gray-600 mb-1">Store name</label>
                        <select id="warehouse_id" class="ps-filter-input">
                            <option value="">All stores</option>
                            <?php foreach ($warehouses as $wh): ?>
                                <?php
                                $wid = (int) ($wh['id'] ?? 0);
                                $storeLabel = trim((string) ($wh['address_title'] ?? ''));
                                if ($storeLabel === '') {
                                    $storeLabel = 'Warehouse #' . $wid;
                                }
                                ?>
                                <option value="<?= $wid ?>"><?= htmlspecialchars($storeLabel) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div>
                        <label for="type" class="block text-xs font-semibold text-gray-600 mb-1">Payment type</label>
                        <select id="type" class="ps-filter-input">
                            <option value="">All payment types</option>
                            <option value="offline">Offline</option>
                            <option value="cod">Cash</option>
                            <option value="razorpay">Razorpay</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </div>
                    <div>
                        <label for="discount_applied" class="block text-xs font-semibold text-gray-600 mb-1">Discount applied</label>
                        <select id="discount_applied" class="ps-filter-input">
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">Invoice status</label>
                        <select id="status" class="ps-filter-input">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="proforma">Proforma</option>
                            <option value="final">Final</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" id="posSalesSearchBtn"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 transition shadow-sm">
                        <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                        Apply filters
                    </button>
                    <button type="button" onclick="resetPosSalesFilters()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                        <i class="fas fa-rotate-left text-xs opacity-80" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </details>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <p id="posSalesResultSummary" class="text-sm text-gray-600">
                <span class="font-medium text-gray-900">—</span> stores
            </p>
            <button type="button" id="exportSalesSummaryBtn" onclick="exportSalesSummaryToExcel()"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                <i class="fas fa-file-excel text-xs" aria-hidden="true"></i>
                <span id="exportSalesSummaryBtnLabel">Export to Excel</span>
            </button>
        </div>

        <div class="bg-white rounded-xl border overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="p-3 text-left">Store / Warehouse</th>
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
                <tbody id="posSalesSummaryTable">
                    <tr>
                        <td colspan="9" class="p-6 text-center text-gray-400">Loading summary…</td>
                    </tr>
                </tbody>
                <tfoot id="posSalesSummaryTotals" class="bg-orange-50/70 border-t-2 border-orange-200 text-sm font-semibold hidden">
                </tfoot>
            </table>
        </div>

    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('posSalesFiltersForm');
        if (form) {
            form.addEventListener('submit', function (event) {
                event.preventDefault();
                loadSalesSummary();
            });
        }
        loadSalesSummary();
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

    function updatePosSalesFilterSummary() {
        const fields = [
            { id: 'from_date' },
            { id: 'to_date' },
            { id: 'warehouse_id' },
            { id: 'type' },
            { id: 'discount_applied' },
            { id: 'status' },
        ];

        let activeCount = 0;
        fields.forEach(function (field) {
            const el = document.getElementById(field.id);
            if (!el) return;
            if (String(el.value || '').trim() !== '') {
                activeCount++;
            }
        });

        const badge = document.getElementById('posSalesActiveFilterCount');
        if (!badge) return;
        if (activeCount > 0) {
            badge.textContent = activeCount + ' active';
            badge.classList.remove('hidden');
        } else {
            badge.textContent = '';
            badge.classList.add('hidden');
        }
    }

    function resetPosSalesFilters() {
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        document.getElementById('type').value = '';
        document.getElementById('discount_applied').value = '';
        document.getElementById('status').value = '';
        const warehouseEl = document.getElementById('warehouse_id');
        if (warehouseEl) {
            warehouseEl.value = '';
        }
        loadSalesSummary();
    }

    function buildPosSalesFilterQuery(action) {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: action,
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value,
            type: document.getElementById('type').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });

        const warehouseEl = document.getElementById('warehouse_id');
        if (warehouseEl && warehouseEl.value) {
            params.set('warehouse_id', warehouseEl.value);
        }

        return '?' + params.toString();
    }

    function buildInvoiceListingUrl(warehouseId) {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: 'list',
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value,
            type: document.getElementById('type').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });

        if (warehouseId) {
            params.set('warehouse_id', String(warehouseId));
        }

        return '?' + params.toString();
    }

    function updatePosSalesResultSummary(storeCount, loading) {
        const el = document.getElementById('posSalesResultSummary');
        if (!el) return;

        if (loading) {
            el.innerHTML = '<span class="text-gray-500">Loading summary…</span>';
            return;
        }

        const n = Number(storeCount) || 0;
        const label = n === 1 ? 'store' : 'stores';
        el.innerHTML = '<span class="font-medium text-gray-900">' + n.toLocaleString() + '</span> ' + label;
    }

    function renderSalesSummaryRow(row, isTotal) {
        const storeName = row.warehouse_name || (isTotal ? 'TOTAL' : '—');
        const warehouseId = row.warehouse_id || '';
        const rowClass = isTotal ? 'bg-orange-50/70 font-semibold' : 'border-t hover:bg-gray-50';
        const actionCell = isTotal ? '' : `
            <a href="${buildInvoiceListingUrl(warehouseId)}"
               class="inline-flex items-center gap-1 text-orange-700 hover:text-orange-900 text-xs font-semibold"
               title="View invoices for this store">
                <i class="fas fa-list-ul text-[10px]" aria-hidden="true"></i>
                Invoices
            </a>`;

        return `
            <tr class="${rowClass}">
                <td class="p-3">${storeName}</td>
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

    function loadSalesSummary() {
        updatePosSalesFilterSummary();

        const tbody = document.getElementById('posSalesSummaryTable');
        const tfoot = document.getElementById('posSalesSummaryTotals');
        updatePosSalesResultSummary(0, true);

        if (tbody) {
            tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-gray-400"><i class="fas fa-spinner fa-spin mr-2"></i>Loading summary…</td></tr>';
        }
        if (tfoot) {
            tfoot.innerHTML = '';
            tfoot.classList.add('hidden');
        }

        fetch(buildPosSalesFilterQuery('sales_summary_ajax'), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                const rows = Array.isArray(data.rows) ? data.rows : [];
                const totals = data.totals || {};

                updatePosSalesResultSummary(rows.length, false);

                if (!tbody) {
                    return;
                }

                if (rows.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-gray-400">No sales match the current filters.</td></tr>';
                    return;
                }

                let html = '';
                rows.forEach(function (row) {
                    html += renderSalesSummaryRow(row, false);
                });
                tbody.innerHTML = html;

                if (tfoot) {
                    tfoot.innerHTML = renderSalesSummaryRow(Object.assign({}, totals, { warehouse_name: 'TOTAL' }), true);
                    tfoot.classList.remove('hidden');
                }
            })
            .catch(function () {
                updatePosSalesResultSummary(0, false);
                if (tbody) {
                    tbody.innerHTML = '<tr><td colspan="9" class="p-6 text-center text-red-500">Could not load sales summary. Please try again.</td></tr>';
                }
            });
    }

    async function exportSalesSummaryToExcel() {
        const btn = document.getElementById('exportSalesSummaryBtn');
        const label = document.getElementById('exportSalesSummaryBtnLabel');
        if (!btn || btn.disabled) {
            return;
        }

        const oldLabel = label ? label.textContent : 'Export to Excel';
        btn.disabled = true;
        if (label) {
            label.textContent = 'Exporting…';
        }

        try {
            const res = await fetch(buildPosSalesFilterQuery('export_sales_summary'), { credentials: 'same-origin' });
            const contentType = (res.headers.get('content-type') || '').toLowerCase();

            if (contentType.includes('application/json')) {
                let message = 'Export failed.';
                try {
                    const data = await res.json();
                    message = data.message || message;
                } catch (err) {
                    /* keep default */
                }
                alert(message);
                return;
            }

            if (!res.ok) {
                alert('Export failed. Please try again.');
                return;
            }

            const blob = await res.blob();
            if (!blob || blob.size < 4) {
                alert('Export failed. The downloaded file was empty.');
                return;
            }

            const headerBytes = new Uint8Array(await blob.slice(0, 2).arrayBuffer());
            const looksLikeZip = headerBytes[0] === 0x50 && headerBytes[1] === 0x4b;
            if (!looksLikeZip) {
                alert('Export failed. The server did not return a valid Excel file.');
                return;
            }

            let filename = 'pos_sales_summary.xlsx';
            const disposition = res.headers.get('content-disposition') || '';
            const match = disposition.match(/filename=\"?([^\";]+)\"?/i);
            if (match && match[1]) {
                filename = match[1];
            }

            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            link.remove();
            window.URL.revokeObjectURL(url);
        } catch (err) {
            alert((err && err.message) ? err.message : 'Export failed.');
        } finally {
            btn.disabled = false;
            if (label) {
                label.textContent = oldLabel;
            }
        }
    }
</script>
