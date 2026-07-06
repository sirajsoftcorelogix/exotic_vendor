<?php
$defaultWarehouse = is_array($defaultWarehouse ?? null) ? $defaultWarehouse : [];
$defaultWarehouseId = (int) ($defaultWarehouse['id'] ?? 0);
$defaultWarehouseName = trim((string) ($defaultWarehouse['name'] ?? ''));
if ($defaultWarehouseName === '' && $defaultWarehouseId > 0) {
    $defaultWarehouseName = 'Warehouse #' . $defaultWarehouseId;
}
?>
<div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-4">
    <div class="bg-white border rounded-xl shadow-sm p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Warehouse Stock Rebuild</h2>
                <p class="text-sm text-gray-600 mt-1">
                    Preview and execute a scoped SKU-level stock rebuild per page, using the same product list as
                    <a href="?page=pos_register&amp;action=stock-report" class="text-amber-700 hover:underline font-medium">POS Register → Stock report</a>.
                    Run preview first; execution requires typing <strong>REBUILD</strong>.
                </p>
            </div>
            <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
        <p class="font-semibold mb-1">Before you run this</p>
        <ul class="list-disc pl-5 space-y-1 text-amber-900/90">
            <li>Product scope matches the POS stock report (latest movement balance per product in the selected warehouse). Rebuild one page at a time when the list is large.</li>
            <li>Step 4 deletes all <code class="text-xs bg-white/70 px-1 rounded">vp_stock_movements</code> and <code class="text-xs bg-white/70 px-1 rounded">vp_stock</code> rows for scoped SKUs on the current page, then replays opening stock, purchases, transfers, and invoices.</li>
            <li>Opening stock is seeded in the default warehouse from current <code class="text-xs bg-white/70 px-1 rounded">local_stock</code>.</li>
            <li>Execution is blocked if scoped SKUs also have stock history in warehouses outside the default + selected pair.</li>
        </ul>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <div class="lg:col-span-2 bg-white border rounded-xl shadow-sm p-4 sm:p-5 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="stockRebuildWarehouse" class="block text-sm font-medium text-gray-700 mb-1">Selected warehouse</label>
                    <select id="stockRebuildWarehouse" class="w-full border rounded-lg px-3 py-2 text-sm">
                        <option value="">Select warehouse</option>
                        <?php foreach (($warehouses ?? []) as $wh): ?>
                            <?php
                            $wid = (int) ($wh['id'] ?? 0);
                            $wname = (string) ($wh['name'] ?? $wh['address_title'] ?? $wh['address'] ?? ('Warehouse #' . $wid));
                            ?>
                            <option value="<?= $wid ?>" <?= ((int) ($selectedWarehouseId ?? 0) === $wid) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wname, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Default warehouse (opening / purchases)</label>
                    <div class="border rounded-lg px-3 py-2 text-sm bg-gray-50 text-gray-700 min-h-[42px] flex items-center">
                        <?php if ($defaultWarehouseId > 0): ?>
                            <?= htmlspecialchars($defaultWarehouseName, ENT_QUOTES, 'UTF-8') ?>
                            <span class="ml-2 text-xs text-gray-500">(#<?= $defaultWarehouseId ?>)</span>
                        <?php else: ?>
                            <span class="text-red-600">Default warehouse could not be resolved.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="border rounded-lg p-4 bg-gray-50/80 space-y-4">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold text-gray-800">Stock report filters (scope baseline)</h3>
                    <span class="text-xs text-gray-500">Same filters as POS stock report</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="stockRebuildSearch" class="block text-sm font-medium text-gray-700 mb-1">Keyword</label>
                        <input type="text" id="stockRebuildSearch" placeholder="Item code, SKU, title"
                            class="w-full border rounded-lg px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label for="stockRebuildCategory" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select id="stockRebuildCategory" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <?php foreach (($categories ?? ['allProducts' => 'All Products']) as $slug => $label): ?>
                                <option value="<?= htmlspecialchars((string) $slug, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="stockRebuildStockStatus" class="block text-sm font-medium text-gray-700 mb-1">Stock status</label>
                        <select id="stockRebuildStockStatus" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <option value="all">All stock</option>
                            <option value="out">Out of stock</option>
                            <option value="low">Low stock (1-5)</option>
                            <option value="in">In stock</option>
                        </select>
                    </div>
                    <div>
                        <label for="stockRebuildLimit" class="block text-sm font-medium text-gray-700 mb-1">Rows per page</label>
                        <select id="stockRebuildLimit" class="w-full border rounded-lg px-3 py-2 text-sm">
                            <?php foreach ([50, 100, 200, 500] as $limitOption): ?>
                                <option value="<?= $limitOption ?>" <?= $limitOption === 200 ? 'selected' : '' ?>><?= $limitOption ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="stockRebuildPreviewBtn"
                    class="px-4 py-2 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold">
                    Run preview
                </button>
                <span id="stockRebuildStatus" class="text-sm text-gray-500"></span>
            </div>

            <div id="stockRebuildPreviewPanel" class="hidden border rounded-lg overflow-hidden">
                <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-800">Preview summary</div>
                <div class="p-4 space-y-4 text-sm" id="stockRebuildPreviewContent"></div>
                <div id="stockRebuildPagination" class="hidden px-4 pb-4"></div>
            </div>
        </div>

        <aside class="bg-white border rounded-xl shadow-sm p-4 sm:p-5 space-y-4 h-fit lg:sticky lg:top-4">
            <div>
                <h3 class="text-sm font-semibold text-gray-800">Execute rebuild</h3>
                <p class="text-xs text-gray-500 mt-1">Available only after a successful preview with no blocking warnings.</p>
            </div>

            <label class="flex items-start gap-2 text-sm text-gray-700">
                <input type="checkbox" id="stockRebuildAck" class="mt-1" disabled>
                <span>I understand this will delete and replay stock history for the scoped SKUs.</span>
            </label>

            <div>
                <label for="stockRebuildConfirm" class="block text-sm font-medium text-gray-700 mb-1">Type REBUILD to confirm</label>
                <input type="text" id="stockRebuildConfirm" class="w-full border rounded-lg px-3 py-2 text-sm uppercase" placeholder="REBUILD" disabled>
            </div>

            <button type="button" id="stockRebuildExecuteBtn" disabled
                class="w-full px-4 py-2 rounded-lg bg-red-600 hover:bg-red-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white text-sm font-semibold">
                Execute rebuild
            </button>
        </aside>
    </div>

    <div id="stockRebuildResultPanel" class="hidden bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-800">Execution result</div>
        <div class="p-4 space-y-3 text-sm" id="stockRebuildResultContent"></div>
    </div>
</div>

<script>
(function () {
    const previewBtn = document.getElementById('stockRebuildPreviewBtn');
    const executeBtn = document.getElementById('stockRebuildExecuteBtn');
    const warehouseEl = document.getElementById('stockRebuildWarehouse');
    const statusEl = document.getElementById('stockRebuildStatus');
    const previewPanel = document.getElementById('stockRebuildPreviewPanel');
    const previewContent = document.getElementById('stockRebuildPreviewContent');
    const resultPanel = document.getElementById('stockRebuildResultPanel');
    const resultContent = document.getElementById('stockRebuildResultContent');
    const ackEl = document.getElementById('stockRebuildAck');
    const confirmEl = document.getElementById('stockRebuildConfirm');
    const searchEl = document.getElementById('stockRebuildSearch');
    const categoryEl = document.getElementById('stockRebuildCategory');
    const stockStatusEl = document.getElementById('stockRebuildStockStatus');
    const limitEl = document.getElementById('stockRebuildLimit');
    const paginationEl = document.getElementById('stockRebuildPagination');

    let lastPreview = null;
    let currentPageNo = 1;

    function collectFilterPayload(pageNo) {
        return {
            warehouse_id: parseInt(String(warehouseEl.value || '0'), 10),
            search: String(searchEl.value || '').trim(),
            category: String(categoryEl.value || 'allProducts'),
            stock_status: String(stockStatusEl.value || 'all'),
            limit: parseInt(String(limitEl.value || '200'), 10) || 200,
            page_no: Math.max(1, parseInt(String(pageNo || currentPageNo || 1), 10) || 1)
        };
    }

    function renderPagination(pagination) {
        if (!paginationEl) {
            return;
        }
        const totalPages = Math.max(1, parseInt(String(pagination.total_pages || 1), 10) || 1);
        const pageNo = Math.max(1, parseInt(String(pagination.page_no || 1), 10) || 1);
        const totalRows = parseInt(String(pagination.total_rows || 0), 10) || 0;
        const rowFrom = parseInt(String(pagination.row_from || 0), 10) || 0;
        const rowTo = parseInt(String(pagination.row_to || 0), 10) || 0;

        currentPageNo = pageNo;

        if (totalPages <= 1 && totalRows <= 0) {
            paginationEl.classList.add('hidden');
            paginationEl.innerHTML = '';
            return;
        }

        paginationEl.classList.remove('hidden');
        paginationEl.innerHTML = ''
            + '<div class="rounded-lg border border-gray-200 bg-white px-3 py-3 space-y-3">'
            + '<p class="text-sm text-gray-600">Showing '
            + '<span class="font-medium text-gray-900 tabular-nums">' + esc(rowFrom) + '</span>'
            + ' – <span class="font-medium text-gray-900 tabular-nums">' + esc(rowTo) + '</span>'
            + ' of <span class="font-medium text-gray-900 tabular-nums">' + esc(totalRows) + '</span> stock-report rows'
            + (totalPages > 1 ? ' · Page ' + esc(pageNo) + ' / ' + esc(totalPages) : '')
            + '</p>'
            + (totalPages > 1
                ? '<div class="flex flex-wrap items-center gap-2" id="stockRebuildPaginationNav">'
                    + '<button type="button" data-page="1" class="stock-rebuild-page-btn px-3 py-1.5 rounded-lg border text-sm font-medium '
                    + (pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                    + '">First</button>'
                    + '<button type="button" data-page="' + esc(Math.max(1, pageNo - 1)) + '" class="stock-rebuild-page-btn px-3 py-1.5 rounded-lg border text-sm font-medium '
                    + (pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                    + '">Previous</button>'
                    + '<span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums">Page ' + esc(pageNo) + ' / ' + esc(totalPages) + '</span>'
                    + '<button type="button" data-page="' + esc(Math.min(totalPages, pageNo + 1)) + '" class="stock-rebuild-page-btn px-3 py-1.5 rounded-lg border text-sm font-medium '
                    + (pageNo >= totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                    + '">Next</button>'
                    + '<button type="button" data-page="' + esc(totalPages) + '" class="stock-rebuild-page-btn px-3 py-1.5 rounded-lg border text-sm font-medium '
                    + (pageNo >= totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50')
                    + '">Last</button>'
                + '</div>'
                : '')
            + '</div>';

        paginationEl.querySelectorAll('.stock-rebuild-page-btn').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const targetPage = parseInt(String(btn.getAttribute('data-page') || '1'), 10);
                if (!targetPage || targetPage === currentPageNo) {
                    return;
                }
                runPreview(targetPage);
            });
        });
    }

    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    const jsonFetchHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };

    async function readJsonResponse(res) {
        const text = await res.text();
        const trimmed = text.trim();
        if (!trimmed) {
            throw new Error('Empty response from server (HTTP ' + res.status + ').');
        }
        if (trimmed.charAt(0) === '<') {
            const snippet = trimmed.replace(/\s+/g, ' ').slice(0, 500);
            throw new Error('Server returned HTML instead of JSON (HTTP ' + res.status + '): ' + snippet);
        }
        try {
            return JSON.parse(trimmed);
        } catch (parseErr) {
            throw new Error('Invalid JSON (HTTP ' + res.status + '): ' + trimmed.slice(0, 500));
        }
    }

    function renderFetchFailure(err, step) {
        const message = err && err.message ? err.message : 'Request failed.';
        renderPreview({
            success: false,
            message: message,
            error_detail: {
                step: step || 'client.fetch',
                mysql_error: message
            }
        });
    }

    function setBusy(isBusy, label) {
        previewBtn.disabled = isBusy;
        executeBtn.disabled = isBusy || !canExecuteNow();
        statusEl.textContent = label || '';
    }

    function canExecuteNow() {
        if (!lastPreview || !lastPreview.success || !lastPreview.can_execute) {
            return false;
        }
        if (!ackEl.checked) {
            return false;
        }
        return String(confirmEl.value || '').trim().toUpperCase() === 'REBUILD';
    }

    function refreshExecuteState() {
        const allowed = !!(lastPreview && lastPreview.success && lastPreview.can_execute);
        ackEl.disabled = !allowed;
        confirmEl.disabled = !allowed;
        executeBtn.disabled = !canExecuteNow();
    }

    function renderKeyValueTable(rows) {
        return '<table class="min-w-full text-sm border"><tbody>'
            + rows.map(function (row) {
                return '<tr><td class="px-3 py-2 border bg-gray-50 font-medium w-1/2">'
                    + esc(row[0]) + '</td><td class="px-3 py-2 border">' + row[1] + '</td></tr>';
            }).join('')
            + '</tbody></table>';
    }

    function renderErrorDetail(detail) {
        if (!detail || typeof detail !== 'object') {
            return '';
        }

        const rows = [];
        if (detail.step) rows.push(['Step', esc(detail.step)]);
        if (detail.phase) rows.push(['Phase', esc(detail.phase)]);
        if (detail.mysql_errno !== undefined && detail.mysql_errno !== null) {
            rows.push(['MySQL errno', esc(detail.mysql_errno)]);
        }
        if (detail.mysql_error) rows.push(['MySQL error', esc(detail.mysql_error)]);
        if (detail.database) rows.push(['Database', esc(detail.database)]);
        if (detail.connection_collation) rows.push(['Connection collation', esc(detail.connection_collation)]);
        if (detail.comparison) rows.push(['Comparison', esc(detail.comparison)]);
        if (Array.isArray(detail.tables) && detail.tables.length) {
            rows.push(['Tables', esc(detail.tables.join(', '))]);
        }
        if (Array.isArray(detail.columns) && detail.columns.length) {
            rows.push(['Columns', esc(detail.columns.join(', '))]);
        }

        let html = '';
        const failedSql = detail.failed_sql || detail.sql_preview || '';
        const failedCondition = detail.failed_condition || detail.comparison || '';

        if (failedCondition) {
            html += '<div class="mt-3 rounded-lg border border-red-300 bg-white px-3 py-2">'
                + '<p class="font-semibold text-red-900 mb-1">Failed condition</p>'
                + '<pre class="text-xs text-red-800 overflow-x-auto whitespace-pre-wrap">' + esc(failedCondition) + '</pre>'
                + '</div>';
        }
        if (failedSql) {
            html += '<div class="mt-3 rounded-lg border border-red-300 bg-white px-3 py-2">'
                + '<p class="font-semibold text-red-900 mb-1">Query that failed</p>'
                + '<pre class="text-xs text-red-800 overflow-x-auto whitespace-pre-wrap">' + esc(failedSql) + '</pre>'
                + '</div>';
        }
        if (rows.length) {
            html += '<div class="mt-3"><p class="font-semibold mb-2">Error details</p>' + renderKeyValueTable(rows) + '</div>';
        }
        if (detail.diagnostic_sql) {
            html += '<div class="mt-3"><p class="font-semibold mb-2">Run in phpMyAdmin to inspect collation</p>'
                + '<pre class="text-xs bg-gray-50 border rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">'
                + esc(detail.diagnostic_sql) + '</pre></div>';
        }

        return html;
    }

    function renderPreview(data) {
        lastPreview = data;
        previewPanel.classList.remove('hidden');
        resultPanel.classList.add('hidden');

        if (!data.success) {
            const detail = data.error_detail || {};
            const failedSql = data.failed_sql || detail.failed_sql || '';
            const stepLine = detail.step
                ? '<p class="mt-2 text-sm font-medium"><span class="opacity-80">Failed step:</span> ' + esc(detail.step) + '</p>'
                : '';
            previewContent.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">'
                + esc(data.message || 'Preview failed.')
                + stepLine
                + '</div>'
                + renderErrorDetail(Object.keys(detail).length ? detail : {
                    failed_sql: failedSql,
                    failed_condition: data.failed_condition || null,
                    mysql_error: data.message || null
                });
            refreshExecuteState();
            return;
        }

        const scope = data.scope || {};
        const pagination = data.pagination || {};
        const deleteCounts = data.delete_counts || {};
        const phase = data.phase_counts || {};
        const warnings = data.warnings || {};
        const blocking = Array.isArray(data.blocking_warnings) ? data.blocking_warnings : [];
        const otherWh = Array.isArray(warnings.other_warehouse_usage) ? warnings.other_warehouse_usage : [];

        let html = '';

        if (blocking.length) {
            html += '<div class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2 space-y-1">'
                + '<p class="font-semibold">Execution blocked</p>'
                + blocking.map(function (w) { return '<p>' + esc(w) + '</p>'; }).join('')
                + '</div>';
        }

        html += renderKeyValueTable([
            ['Selected warehouse', esc((data.selected_warehouse && data.selected_warehouse.name) || '') + ' (#' + esc((data.selected_warehouse && data.selected_warehouse.id) || '') + ')'],
            ['Default warehouse', esc((data.default_warehouse && data.default_warehouse.name) || '') + ' (#' + esc((data.default_warehouse && data.default_warehouse.id) || '') + ')'],
            ['Stock report rows (this page)', esc((pagination.row_from || 0) + '–' + (pagination.row_to || 0) + ' of ' + (pagination.total_rows || 0))],
            ['Scoped SKUs (this page)', esc(scope.sku_count || 0)],
            ['Scoped products (this page)', esc(scope.product_count || 0)],
            ['Rows to delete (movements)', esc(deleteCounts.vp_stock_movements || 0)],
            ['Rows to delete (vp_stock)', esc(deleteCounts.vp_stock || 0)],
            ['Opening candidates', esc(phase.opening_candidates || 0)],
            ['Purchase headers / lines', esc(phase.purchase_headers || 0) + ' / ' + esc(phase.purchase_lines || 0)],
            ['Return headers / lines', esc(phase.return_headers || 0) + ' / ' + esc(phase.return_lines || 0)],
            ['Transfer IN lines', esc(phase.transfer_in_lines || 0)],
            ['Transfer OUT lines', esc(phase.transfer_out_lines || 0)],
            ['Invoice headers / lines', esc(phase.invoice_headers || 0) + ' / ' + esc(phase.invoice_lines || 0)],
            ['Cancelled invoice headers', esc(phase.cancel_invoice_headers || 0)],
        ]);

        html += '<div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-amber-900 space-y-1">'
            + '<p>' + esc(warnings.local_stock_baseline || '') + '</p>'
            + '<p>' + esc(warnings.global_delete || '') + '</p>'
            + (warnings.preview_mode ? '<p>' + esc(warnings.preview_mode) + '</p>' : '')
            + '</div>';

        if (otherWh.length) {
            html += '<div><p class="font-semibold mb-2">Other warehouse usage</p><ul class="list-disc pl-5 space-y-1">'
                + otherWh.map(function (row) {
                    return '<li>' + esc(row.warehouse_name || ('Warehouse #' + (row.warehouse_id || '')))
                        + ' — ' + esc(row.row_count || 0) + ' row(s)</li>';
                }).join('')
                + '</ul></div>';
        }

        const pageProducts = Array.isArray(scope.page_products) ? scope.page_products : [];
        const sample = pageProducts.length ? pageProducts : (Array.isArray(scope.sample) ? scope.sample : []);
        if (sample.length) {
            html += '<div><p class="font-semibold mb-2">Products on this page</p>'
                + '<div class="overflow-x-auto border rounded-lg"><table class="min-w-full text-xs">'
                + '<thead class="bg-gray-50"><tr>'
                + '<th class="px-2 py-1 text-left">SKU</th>'
                + '<th class="px-2 py-1 text-left">Item code</th>'
                + '<th class="px-2 py-1 text-left">Title</th>'
                + '<th class="px-2 py-1 text-right">WH stock</th>'
                + '<th class="px-2 py-1 text-right">local_stock</th>'
                + '</tr></thead><tbody>'
                + sample.map(function (row) {
                    return '<tr>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.sku || '') + '</td>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.item_code || '') + '</td>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.title || '') + '</td>'
                        + '<td class="px-2 py-1 border-t text-right">' + esc(row.warehouse_stock != null ? row.warehouse_stock : '') + '</td>'
                        + '<td class="px-2 py-1 border-t text-right">' + esc(row.local_stock || 0) + '</td>'
                        + '</tr>';
                }).join('')
                + '</tbody></table></div></div>';
        }

        previewContent.innerHTML = html;
        renderPagination(pagination);
        ackEl.checked = false;
        confirmEl.value = '';
        refreshExecuteState();
    }

    function renderResult(data) {
        resultPanel.classList.remove('hidden');
        const stats = data.stats || {};
        const logs = Array.isArray(data.logs) ? data.logs : [];
        const ok = !!data.success;

        let html = '<div class="rounded-lg border px-3 py-2 '
            + (ok ? 'border-green-200 bg-green-50 text-green-800' : 'border-red-200 bg-red-50 text-red-800') + '">'
            + esc(data.message || (ok ? 'Completed.' : 'Failed.'))
            + '</div>'
            + renderErrorDetail(data.error_detail);

        html += renderKeyValueTable(Object.keys(stats).map(function (key) {
            return [key.replace(/_/g, ' '), esc(stats[key])];
        }));

        if (logs.length) {
            html += '<div><p class="font-semibold mb-2">Log</p><pre class="text-xs bg-gray-50 border rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">'
                + esc(logs.join('\n')) + '</pre></div>';
        }

        resultContent.innerHTML = html;
    }

    async function runPreview(pageNo) {
        const payload = collectFilterPayload(pageNo);
        if (!payload.warehouse_id) {
            alert('Please select a warehouse.');
            return;
        }

        setBusy(true, 'Running preview...');
        try {
            const res = await fetch('?page=products&action=stock_rebuild_preview', {
                method: 'POST',
                headers: jsonFetchHeaders,
                body: JSON.stringify(payload)
            });
            const data = await readJsonResponse(res);
            renderPreview(data);
            statusEl.textContent = data.success ? 'Preview ready.' : 'Preview failed.';
        } catch (err) {
            renderFetchFailure(err, 'client.stock_rebuild_preview');
            statusEl.textContent = 'Preview request failed.';
        } finally {
            setBusy(false, statusEl.textContent);
        }
    }

    previewBtn.addEventListener('click', function () {
        currentPageNo = 1;
        runPreview(1);
    });

    executeBtn.addEventListener('click', async function () {
        if (!canExecuteNow()) {
            return;
        }
        const payload = collectFilterPayload((lastPreview && lastPreview.pagination && lastPreview.pagination.page_no) || currentPageNo);
        if (!payload.warehouse_id) {
            alert('Please select a warehouse.');
            return;
        }
        if (!window.confirm('This will delete and replay stock history for scoped SKUs on this page. Continue?')) {
            return;
        }

        setBusy(true, 'Executing rebuild...');
        try {
            const res = await fetch('?page=products&action=stock_rebuild_execute', {
                method: 'POST',
                headers: jsonFetchHeaders,
                body: JSON.stringify({
                    warehouse_id: payload.warehouse_id,
                    search: payload.search,
                    category: payload.category,
                    stock_status: payload.stock_status,
                    limit: payload.limit,
                    page_no: payload.page_no,
                    confirm_text: String(confirmEl.value || '').trim().toUpperCase()
                })
            });
            const data = await readJsonResponse(res);
            renderResult(data);
            statusEl.textContent = data.success ? 'Rebuild completed.' : 'Rebuild failed.';
        } catch (err) {
            renderResult({
                success: false,
                message: err && err.message ? err.message : 'Execute request failed.',
                error_detail: {
                    step: 'client.stock_rebuild_execute',
                    mysql_error: err && err.message ? err.message : 'Execute request failed.'
                },
                stats: {},
                logs: []
            });
            statusEl.textContent = 'Execute request failed.';
        } finally {
            setBusy(false, statusEl.textContent);
        }
    });

    ackEl.addEventListener('change', refreshExecuteState);
    confirmEl.addEventListener('input', refreshExecuteState);
})();
</script>
