<div class="min-h-screen bg-gray-50">

    <header class="border-b bg-white">
        <div class="mx-auto flex max-w-[1500px] items-center justify-between gap-3 px-4 py-3">
            <h1 class="text-lg font-semibold">POS Invoice Listing</h1>
            <div class="flex flex-wrap items-center gap-2">
                <a href="?page=sales_returns&action=index"
                   class="inline-flex items-center gap-2 rounded-lg border border-orange-300 bg-white px-3 py-2 text-xs font-semibold text-orange-800 hover:bg-orange-50"
                   title="View sales returns">
                    <i class="fas fa-rotate-left text-xs" aria-hidden="true"></i>
                    Sales returns
                </a>
                <a href="?page=posinvoice&action=sales_summary"
                   class="inline-flex items-center gap-2 rounded-lg border border-orange-300 bg-white px-3 py-2 text-xs font-semibold text-orange-800 hover:bg-orange-50"
                   title="View POS sales summary by store">
                    <i class="fas fa-chart-column text-xs" aria-hidden="true"></i>
                    Sales summary
                </a>
                <a href="?page=posinvoice&action=user_guide"
               target="_blank"
               rel="noopener noreferrer"
               class="inline-flex items-center gap-2 rounded-lg border border-orange-200 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-800 hover:bg-orange-100"
               title="Open invoice module user guide">
                <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
                User guide
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-[1500px] px-4 py-5">

        <style>
            #pos-invoice-filters > summary { list-style: none; }
            #pos-invoice-filters > summary::-webkit-details-marker { display: none; }
            #pos-invoice-filters[open] > summary { border-bottom: 1px solid rgba(254, 215, 170, 0.85); }
            #pos-invoice-filters:not([open]) .pif-label-open { display: none; }
            #pos-invoice-filters[open] .pif-label-closed { display: none; }
            #pos-invoice-filters[open] .pif-chevron { transform: rotate(180deg); }
            .pi-filter-input {
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
            .pi-filter-input:focus {
                outline: none;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.18);
            }
            .pi-filter-input::placeholder { color: #9ca3af; }
            #pos-invoice-filters .select2-container--default .select2-selection--single {
                min-height: 42px;
                border-color: #d1d5db;
                border-radius: 0.5rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            }
            #pos-invoice-filters .select2-container--default .select2-selection--single .select2-selection__rendered {
                line-height: 40px;
                padding-left: 0.75rem;
                font-size: 0.875rem;
                color: #111827;
            }
            #pos-invoice-filters .select2-container--default .select2-selection--single .select2-selection__arrow {
                height: 40px;
            }
        </style>

        <details id="pos-invoice-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-4 ring-1 ring-gray-900/[0.03]" open>
            <summary class="px-5 py-4 bg-gradient-to-r from-orange-50/60 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-orange-700 shadow-sm border border-orange-100">
                        <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                        <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Invoice date, order, invoice no., store, customer, payment type, discount flag, amount range, and status.</p>
                    </div>
                </div>
                <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-orange-800">
                    <span id="posInvoiceActiveFilterCount" class="hidden rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-900"></span>
                    <span class="pif-label-closed">Show</span>
                    <span class="pif-label-open">Hide</span>
                    <i class="pif-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
                </span>
            </summary>

            <form id="posInvoiceFiltersForm" class="p-5" autocomplete="off">
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                    <div>
                        <label for="from_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice from</label>
                        <input type="date" id="from_date" class="pi-filter-input">
                    </div>
                    <div>
                        <label for="to_date" class="block text-xs font-semibold text-gray-600 mb-1">Invoice to</label>
                        <input type="date" id="to_date" class="pi-filter-input">
                    </div>
                    <div>
                        <label for="order_number" class="block text-xs font-semibold text-gray-600 mb-1">Order number</label>
                        <input type="text" id="order_number" class="pi-filter-input" placeholder="Search order number">
                    </div>
                    <div>
                        <label for="invoice_number" class="block text-xs font-semibold text-gray-600 mb-1">Invoice number</label>
                        <input type="text" id="invoice_number" class="pi-filter-input" placeholder="Search invoice number">
                    </div>
                    <?php if (!empty($can_change_warehouse)): ?>
                    <div>
                        <label for="warehouse_id" class="block text-xs font-semibold text-gray-600 mb-1">Store name</label>
                        <select id="warehouse_id" class="pi-filter-input">
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
                        <select id="type" class="pi-filter-input">
                            <option value="">All payment types</option>
                            <option value="offline">Offline</option>
                            <option value="cod">Cash</option>
                            <option value="razorpay">Razorpay</option>
                            <option value="bank_transfer">Bank transfer</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <label for="customer_id" class="block text-xs font-semibold text-gray-600 mb-1">Customer</label>
                        <select id="customer_id" name="customer_id" class="w-full">
                            <option value="">All customers</option>
                            <?php foreach ($customers as $c): ?>
                                <option value="<?= $c['id'] ?>"
                                    data-name="<?= htmlspecialchars($c['name']) ?>"
                                    data-phone="<?= htmlspecialchars($c['phone']) ?>"
                                    data-email="<?= htmlspecialchars($c['email']) ?>">
                                    <?= htmlspecialchars($c['name']) ?> | <?= $c['phone'] ?> | <?= $c['email'] ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="amount_min" class="block text-xs font-semibold text-gray-600 mb-1">Amount min (net payable)</label>
                        <input type="number" id="amount_min" class="pi-filter-input" placeholder="0.00" min="0" step="0.01">
                    </div>
                    <div>
                        <label for="amount_max" class="block text-xs font-semibold text-gray-600 mb-1">Amount max (net payable)</label>
                        <input type="number" id="amount_max" class="pi-filter-input" placeholder="0.00" min="0" step="0.01">
                    </div>
                    <div>
                        <label for="discount_applied" class="block text-xs font-semibold text-gray-600 mb-1">Discount applied</label>
                        <select id="discount_applied" class="pi-filter-input">
                            <option value="">All</option>
                            <option value="1">Yes</option>
                            <option value="0">No</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">Invoice status</label>
                        <select id="status" class="pi-filter-input">
                            <option value="">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="proforma">Proforma</option>
                            <option value="final">Final</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <button type="submit" id="posInvoiceSearchBtn"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 focus-visible:ring-offset-2 transition shadow-sm">
                        <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                        Apply filters
                    </button>
                    <button type="button" onclick="resetPosInvoiceFilters()"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                        <i class="fas fa-rotate-left text-xs opacity-80" aria-hidden="true"></i>
                        Reset
                    </button>
                </div>
            </form>
        </details>

        <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
            <p id="posInvoiceResultSummary" class="text-sm text-gray-600">
                <span class="font-medium text-gray-900">—</span> invoices
            </p>
            <button type="button" id="exportInvoicesBtn" onclick="exportInvoicesToExcel()"
                class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-800 hover:bg-emerald-100">
                <i class="fas fa-file-excel text-xs" aria-hidden="true"></i>
                <span id="exportInvoicesBtnLabel">Export to Excel</span>
            </button>
        </div>

        <div class="bg-white rounded-xl border overflow-hidden">

            <table class="w-full text-sm">

                <thead class="bg-gray-100 text-xs">
                    <tr>
                        <th class="p-3 text-left">ID</th>
                        <th class="p-3 text-left">Date</th>
                        <th class="p-3 text-left">Order</th>
                        <th class="p-3 text-left">Invoice</th>
                        <th class="p-3 text-left">Store / Warehouse</th>
                        <th class="p-3 text-left">Customer</th>
                        <th class="p-3 text-left">Amount</th>
                        <th class="p-3 text-left">Disc.</th>
                        <th class="p-3 text-left">Discount</th>
                        <th class="p-3 text-left">Paid</th>
                        <th class="p-3 text-left">Pending</th>
                        <th class="p-3 text-left">Status</th>
                        <th class="p-3 text-left">Action</th>
                    </tr>
                </thead>

                <tbody id="invoiceTable"></tbody>

            </table>

        </div>

    </main>
</div>
<div id="deleteConfirmModal" class="fixed inset-0 z-[9999] hidden">

    <div class="absolute inset-0 bg-black/40"></div>

    <div class="relative mx-auto mt-40 w-[90%] max-w-md bg-white rounded-2xl shadow-xl">

        <div class="p-6 text-center">
            <h3 class="text-lg font-semibold mb-2">Confirm Delete</h3>
            <p class="text-sm text-gray-600 mb-5" id="deleteConfirmText">
                Are you sure you want to delete ?
            </p>

            <div class="flex justify-center gap-4">
                <button onclick="closeDeleteModal()"
                    class="px-5 py-2 bg-gray-300 rounded">
                    Cancel
                </button>

                <button onclick="confirmDelete()"
                    class="px-5 py-2 bg-red-600 text-white rounded">
                    Delete
                </button>
            </div>
        </div>

    </div>

</div>
<script>
    $(document).ready(function() {

        $('#customer_id').select2({
            placeholder: "Search customer",
            allowClear: true,
            width: '100%',
            dropdownParent: $('#pos-invoice-filters'),

            templateResult: formatCustomer,
            templateSelection: formatCustomerSelection
        });

        $('#posInvoiceFiltersForm').on('submit', function (e) {
            e.preventDefault();
            loadInvoices();
        });

    });

    function formatCustomer(data) {

        if (!data.id) return data.text;

        let el = $(data.element);

        let name = el.data('name');
        let phone = el.data('phone');
        let email = el.data('email');

        return $(`
        <div style="line-height:1.3">
            <div style="font-weight:600">${name}</div>
            <div style="font-size:11px;color:#888">
                ${phone} ${email ? ' | ' + email : ''}
            </div>
        </div>
    `);
    }

    function formatCustomerSelection(data) {

        if (!data.id) return data.text;

        let el = $(data.element);
        return el.data('name');
    }
    document.addEventListener("DOMContentLoaded", loadInvoices);

    function formatInvoiceAmount(value) {
        const n = parseFloat(value);
        if (Number.isNaN(n)) {
            return '0.00';
        }
        return n.toFixed(2);
    }

    function invoiceHasDiscount(value) {
        const n = parseFloat(value);
        return !Number.isNaN(n) && n > 0.001;
    }

    function formatDiscountAppliedFlag(value) {
        if (invoiceHasDiscount(value)) {
            return '<span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold bg-amber-100 text-amber-800">Yes</span>';
        }
        return '<span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">No</span>';
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildOrderNumberLinkHtml(orderNumber) {
        const label = String(orderNumber ?? '').trim();
        if (label === '') {
            return '';
        }

        const href = `?page=posorders&action=get_order_details_html&type=outer&order_number=${encodeURIComponent(label)}`;
        return `<a href="${href}" target="_blank" class="text-blue-600 hover:text-blue-800 hover:underline font-medium" title="View order details">${escapeHtml(label)}</a>`;
    }

    function formatCustomerCell(invoice) {
        const name = escapeHtml(invoice.customer_name ?? '');
        const state = String(invoice.customer_billing_state ?? '').trim();
        const country = String(invoice.customer_billing_country ?? '').trim();
        let location = '';

        if (state && country) {
            location = `${state}, ${country}`;
        } else if (state) {
            location = state;
        } else if (country) {
            location = country;
        }

        if (!location) {
            return name;
        }

        return `
            <div style="line-height:1.35">
                <div>${name}</div>
                <div style="font-size:11px;color:#6b7280">${escapeHtml(location)}</div>
            </div>
        `;
    }

    function updatePosInvoiceFilterSummary() {
        const fields = [
            { id: 'from_date', type: 'value' },
            { id: 'to_date', type: 'value' },
            { id: 'order_number', type: 'value' },
            { id: 'invoice_number', type: 'value' },
            { id: 'warehouse_id', type: 'value' },
            { id: 'type', type: 'value' },
            { id: 'customer_id', type: 'value' },
            { id: 'amount_min', type: 'value' },
            { id: 'amount_max', type: 'value' },
            { id: 'discount_applied', type: 'value' },
            { id: 'status', type: 'value' },
        ];

        let activeCount = 0;
        fields.forEach(function (field) {
            const el = document.getElementById(field.id);
            if (!el) return;
            const val = String(el.value || '').trim();
            if (val !== '') {
                activeCount++;
            }
        });

        const badge = document.getElementById('posInvoiceActiveFilterCount');
        if (badge) {
            if (activeCount > 0) {
                badge.textContent = activeCount + ' active';
                badge.classList.remove('hidden');
            } else {
                badge.textContent = '';
                badge.classList.add('hidden');
            }
        }
    }

    function updatePosInvoiceResultSummary(count, loading) {
        const el = document.getElementById('posInvoiceResultSummary');
        if (!el) return;

        if (loading) {
            el.innerHTML = '<span class="text-gray-500">Loading invoices…</span>';
            return;
        }

        const n = Number(count) || 0;
        const label = n === 1 ? 'invoice' : 'invoices';
        el.innerHTML = '<span class="font-medium text-gray-900">' + n.toLocaleString() + '</span> ' + label;
    }

    function resetPosInvoiceFilters() {
        document.getElementById('from_date').value = '';
        document.getElementById('to_date').value = '';
        document.getElementById('order_number').value = '';
        document.getElementById('invoice_number').value = '';
        const warehouseEl = document.getElementById('warehouse_id');
        if (warehouseEl) {
            warehouseEl.value = '';
        }
        document.getElementById('type').value = '';
        document.getElementById('amount_min').value = '';
        document.getElementById('amount_max').value = '';
        document.getElementById('discount_applied').value = '';
        document.getElementById('status').value = '';
        $('#customer_id').val(null).trigger('change');
        loadInvoices();
    }

    function buildPosInvoiceFilterQuery(action) {
        const params = new URLSearchParams({
            page: 'posinvoice',
            action: action,
            from_date: document.getElementById('from_date').value,
            to_date: document.getElementById('to_date').value,
            order_number: document.getElementById('order_number').value,
            invoice_number: document.getElementById('invoice_number').value,
            type: document.getElementById('type').value,
            customer_id: document.getElementById('customer_id').value,
            amount_min: document.getElementById('amount_min').value,
            amount_max: document.getElementById('amount_max').value,
            discount_applied: document.getElementById('discount_applied').value,
            status: document.getElementById('status').value,
        });

        const warehouseEl = document.getElementById('warehouse_id');
        if (warehouseEl && warehouseEl.value) {
            params.set('warehouse_id', warehouseEl.value);
        }

        return '?' + params.toString();
    }

    async function exportInvoicesToExcel() {
        const btn = document.getElementById('exportInvoicesBtn');
        const label = document.getElementById('exportInvoicesBtnLabel');
        if (!btn || btn.disabled) {
            return;
        }

        const oldLabel = label ? label.textContent : 'Export to Excel';
        btn.disabled = true;
        if (label) {
            label.textContent = 'Exporting…';
        }

        try {
            const res = await fetch(buildPosInvoiceFilterQuery('export_excel'), { credentials: 'same-origin' });
            const contentType = (res.headers.get('content-type') || '').toLowerCase();

            if (contentType.includes('application/json')) {
                let message = 'Export failed.';
                try {
                    const data = await res.json();
                    message = data.message || message;
                } catch (err) {
                    /* keep default message */
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

            let filename = 'pos_invoices.xlsx';
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

    function loadInvoices() {
        updatePosInvoiceFilterSummary();
        updatePosInvoiceResultSummary(0, true);

        const tbody = document.getElementById('invoiceTable');
        if (tbody) {
            tbody.innerHTML = `<tr>
<td colspan="13" class="p-6 text-center text-gray-400">
<i class="fas fa-spinner fa-spin mr-2" aria-hidden="true"></i>Loading…
</td>
</tr>`;
        }

        let url = buildPosInvoiceFilterQuery('list_ajax');

        fetch(url)
            .then(res => res.json())
            .then(data => {
                updatePosInvoiceResultSummary(Array.isArray(data) ? data.length : 0, false);

                let html = '';

                if (!data.length) {
                    html = `<tr>
<td colspan="13" class="p-6 text-center text-gray-400">
No invoices
</td>
</tr>`;
                }

                data.forEach(i => {

                    let badge = '';
                    const invStatus = String(i.status || '').toLowerCase();
                    const isCancelled = invStatus === 'cancelled';

                    if (isCancelled)
                        badge = `<span class="px-2 py-1 bg-red-100 text-red-700 rounded text-xs">Cancelled</span>`;
                    else if (i.status === 'final')
                        badge = `<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Final</span>`;
                    else if (i.status === 'proforma')
                        badge = `<span class="px-2 py-1 bg-yellow-100 text-yellow-700 rounded text-xs">Proforma</span>`;
                    else if (i.status === 'draft')
                        badge = `<span class="px-2 py-1 bg-gray-100 text-gray-700 rounded text-xs">Draft</span>`;

                    const invoiceCell = isCancelled
                        ? `<span class="line-through text-gray-500">${i.invoice_number ?? ''}</span>`
                        : `<span class="font-semibold">${i.invoice_number ?? ''}</span>`;

                    const pdfLink = isCancelled ? '' : `
<a href="/?page=posinvoice&action=generate_pdf&invoice_id=${i.id}"
target="_blank"
class="inline-flex items-center text-blue-600 hover:text-blue-800 font-semibold text-xs"
title="Download PDF">

<svg width="15" height="15" viewBox="0 0 15 15" fill="none">
<path d="M2.62925 10.3889C1.64271 9.68768 1 8.54159 1 7.24672C1 5.47783 2.3 3.84375 4.25 3.52778C4.86168 2.07349 6.30934 1 7.99783 1C10.1607 1 11.9284 2.67737 12.05 4.79167C13.1978 5.29352 14 6.52522 14 7.85887C14 8.98648 13.4266 9.98004 12.5556 10.5634M7.5 14V6.77778M7.5 14L5.33333 11.8333M7.5 14L9.66667 11.8333"
stroke="currentColor"
stroke-width="1.5"
stroke-linecap="round"
stroke-linejoin="round"/>
</svg>

</a>`;

                    const cancelBtn = isCancelled ? '' : `
<button type="button" onclick="cancelPosInvoice(${i.id})"
        class="inline-flex items-center text-amber-700 hover:text-amber-900 text-xs font-semibold"
        title="Cancel invoice">
    Cancel
</button>`;

                    const orderNum = (i.order_number || '').trim();
                    const returnBtn = (!isCancelled && orderNum) ? `
<button type="button"
   data-sales-return-create
   data-sales-return-url="?page=sales_returns&action=create&order_number=${encodeURIComponent(orderNum)}&invoice_id=${i.id}"
   data-order-number="${escapeHtml(orderNum)}"
   class="inline-flex items-center text-orange-700 hover:text-orange-900 text-xs font-semibold border-0 bg-transparent cursor-pointer p-0">
    Return
</button>` : '';

                    const deleteBtn = isCancelled ? '' : `
  <button onclick="openDeleteModal(${i.id}, '?page=posinvoice&action=delete', 'Delete this invoice?')"
        class="flex items-center gap-1 text-red-600 hover:text-red-800 text-xs font-semibold"
        title="Delete invoice">
        <i class="fa-solid fa-trash"></i>
    </button>`;

                    html += `
<tr class="border-t hover:bg-gray-50">

<td class="p-3">${i.id ?? ''}</td>
<td class="p-3">${i.invoice_date ?? ''}</td>
<td class="p-3">${buildOrderNumberLinkHtml(i.order_number)}</td>
<td class="p-3">${invoiceCell}</td>
<td class="p-3 text-gray-700">${i.warehouse_name ?? ''}</td>
<td class="p-3">${formatCustomerCell(i)}</td>

<td class="p-3 font-semibold tabular-nums">₹ ${formatInvoiceAmount(i.payable_amount)}</td>
<td class="p-3">${formatDiscountAppliedFlag(i.discount_amount)}</td>
<td class="p-3 text-amber-700 tabular-nums">₹ ${formatInvoiceAmount(i.discount_amount)}</td>
<td class="p-3 text-green-600 tabular-nums">₹ ${formatInvoiceAmount(i.paid_amount)}</td>
<td class="p-3 text-red-600 tabular-nums">₹ ${formatInvoiceAmount(i.pending_amount)}</td>

<td class="p-3">${badge}</td>

 <td class="p-3 flex flex-wrap gap-3 items-center">
${pdfLink}
${returnBtn}
${cancelBtn}
${deleteBtn}
</td>

</tr>`;
                });

                if (tbody) {
                    tbody.innerHTML = html;
                }

            })
            .catch(function () {
                updatePosInvoiceResultSummary(0, false);
                if (tbody) {
                    tbody.innerHTML = `<tr>
<td colspan="13" class="p-6 text-center text-red-500">
Could not load invoices. Please try again.
</td>
</tr>`;
                }
            });
    }

    function cancelPosInvoice(invoiceId) {
        const confirmFn = (typeof customConfirm === 'function')
            ? customConfirm
            : (msg) => Promise.resolve(window.confirm(msg));

        confirmFn(
            'Cancelling this invoice will restore stock and cancel any associated dispatch. This action cannot be undone. Are you sure you want to continue?',
            { okText: 'Confirm Cancellation' }
        ).then(confirmed => {
            if (!confirmed) return;

            fetch('?page=posinvoice&action=cancel_invoice', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ invoice_id: invoiceId })
            })
            .then(async res => {
                const text = await res.text();
                let data = null;
                try {
                    data = text ? JSON.parse(text) : null;
                } catch (parseErr) {
                    console.error('Cancel invoice JSON parse failed:', text);
                    throw new Error('Invalid server response');
                }
                if (!data) {
                    throw new Error('Empty server response');
                }
                return data;
            })
            .then(data => {
                if (data.success) {
                    if (typeof showAlert === 'function') {
                        showAlert(data.message || 'Invoice cancelled successfully.', 'success');
                    } else {
                        alert(data.message || 'Invoice cancelled successfully.');
                    }
                    loadInvoices();
                } else {
                    alert('Error: ' + (data.message || 'Failed to cancel invoice'));
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error canceling invoice');
            });
        });
    }
</script>
<script>
    function deleteInvoice(id) {

        if (!confirm("Are you sure to delete this invoice?")) return;

        let form = new FormData();
        form.append("id", id);

        fetch("?page=posinvoice&action=delete", {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    alert("Invoice deleted");
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });

    }

    function confirmDelete() {

        let form = new FormData();
        form.append("id", deleteId);

        fetch("?page=posinvoice&action=delete", {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    alert("Invoice deleted");
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });

    }

    function openDeleteModal(id, url, text = "Are you sure?") {

        deleteId = id;
        deleteUrl = url;

        document.getElementById("deleteConfirmText").innerText = text;
        document.getElementById("deleteConfirmModal").classList.remove("hidden");
    }

    function closeDeleteModal() {
        document.getElementById("deleteConfirmModal").classList.add("hidden");
    }

    function confirmDelete() {

        let form = new FormData();
        form.append("id", deleteId);

        fetch(deleteUrl, {
                method: "POST",
                body: form
            })
            .then(res => res.json())
            .then(data => {

                if (data.success) {
                    closeDeleteModal();
                    loadInvoices();
                } else {
                    alert(data.message || "Delete failed");
                }

            });
    }
</script>