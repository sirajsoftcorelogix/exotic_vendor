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
                    Preview and execute a scoped SKU-level stock rebuild for one warehouse. Run preview first; execution requires typing <strong>REBUILD</strong>.
                </p>
            </div>
            <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
        <p class="font-semibold mb-1">Before you run this</p>
        <ul class="list-disc pl-5 space-y-1 text-amber-900/90">
            <li>Step 4 deletes all <code class="text-xs bg-white/70 px-1 rounded">vp_stock_movements</code> and <code class="text-xs bg-white/70 px-1 rounded">vp_stock</code> rows for scoped SKUs, then replays opening stock, purchases, transfers, and invoices.</li>
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

    let lastPreview = null;

    function esc(text) {
        const div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
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
        if (rows.length) {
            html += '<div class="mt-3"><p class="font-semibold mb-2">Error details</p>' + renderKeyValueTable(rows) + '</div>';
        }
        if (detail.sql_preview) {
            html += '<div class="mt-3"><p class="font-semibold mb-2">SQL preview</p>'
                + '<pre class="text-xs bg-gray-50 border rounded-lg p-3 overflow-x-auto whitespace-pre-wrap">'
                + esc(detail.sql_preview) + '</pre></div>';
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
            previewContent.innerHTML = '<div class="rounded-lg border border-red-200 bg-red-50 text-red-800 px-3 py-2">'
                + esc(data.message || 'Preview failed.') + '</div>'
                + renderErrorDetail(data.error_detail);
            refreshExecuteState();
            return;
        }

        const scope = data.scope || {};
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
            ['Scoped SKUs', esc(scope.sku_count || 0)],
            ['Scoped products', esc(scope.product_count || 0)],
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
            + '</div>';

        if (otherWh.length) {
            html += '<div><p class="font-semibold mb-2">Other warehouse usage</p><ul class="list-disc pl-5 space-y-1">'
                + otherWh.map(function (row) {
                    return '<li>' + esc(row.warehouse_name || ('Warehouse #' + (row.warehouse_id || '')))
                        + ' — ' + esc(row.row_count || 0) + ' row(s)</li>';
                }).join('')
                + '</ul></div>';
        }

        const sample = Array.isArray(scope.sample) ? scope.sample : [];
        if (sample.length) {
            html += '<div><p class="font-semibold mb-2">Sample scoped SKUs</p>'
                + '<div class="overflow-x-auto border rounded-lg"><table class="min-w-full text-xs">'
                + '<thead class="bg-gray-50"><tr>'
                + '<th class="px-2 py-1 text-left">SKU</th>'
                + '<th class="px-2 py-1 text-left">Item code</th>'
                + '<th class="px-2 py-1 text-left">Title</th>'
                + '<th class="px-2 py-1 text-right">local_stock</th>'
                + '</tr></thead><tbody>'
                + sample.map(function (row) {
                    return '<tr>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.sku || '') + '</td>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.item_code || '') + '</td>'
                        + '<td class="px-2 py-1 border-t">' + esc(row.title || '') + '</td>'
                        + '<td class="px-2 py-1 border-t text-right">' + esc(row.local_stock || 0) + '</td>'
                        + '</tr>';
                }).join('')
                + '</tbody></table></div></div>';
        }

        previewContent.innerHTML = html;
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

    previewBtn.addEventListener('click', async function () {
        const warehouseId = parseInt(String(warehouseEl.value || '0'), 10);
        if (!warehouseId) {
            alert('Please select a warehouse.');
            return;
        }

        setBusy(true, 'Running preview...');
        try {
            const res = await fetch('?page=products&action=stock_rebuild_preview', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ warehouse_id: warehouseId })
            });
            const data = await res.json();
            renderPreview(data);
            statusEl.textContent = data.success ? 'Preview ready.' : 'Preview failed.';
        } catch (err) {
            renderPreview({ success: false, message: err && err.message ? err.message : 'Preview request failed.' });
            statusEl.textContent = 'Preview request failed.';
        } finally {
            setBusy(false, statusEl.textContent);
        }
    });

    executeBtn.addEventListener('click', async function () {
        if (!canExecuteNow()) {
            return;
        }
        const warehouseId = parseInt(String(warehouseEl.value || '0'), 10);
        if (!warehouseId) {
            alert('Please select a warehouse.');
            return;
        }
        if (!window.confirm('This will delete and replay stock history for scoped SKUs. Continue?')) {
            return;
        }

        setBusy(true, 'Executing rebuild...');
        try {
            const res = await fetch('?page=products&action=stock_rebuild_execute', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    warehouse_id: warehouseId,
                    confirm_text: String(confirmEl.value || '').trim().toUpperCase()
                })
            });
            const data = await res.json();
            renderResult(data);
            statusEl.textContent = data.success ? 'Rebuild completed.' : 'Rebuild failed.';
        } catch (err) {
            renderResult({
                success: false,
                message: err && err.message ? err.message : 'Execute request failed.',
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
