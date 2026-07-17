<?php
$candidateTotal = (int) ($candidateTotal ?? 0);
?>
<div class="max-w-6xl mx-auto p-4 sm:p-6 space-y-4">
    <div class="bg-white border rounded-xl shadow-sm p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">Batch stock refresh</h2>
                <p class="text-sm text-gray-600 mt-1 max-w-2xl">
                    Finds active products with <strong>physical_stock = 0</strong>, <strong>≤ 1</strong> movement row,
                    and <strong>no</strong> <code class="text-xs bg-gray-100 px-1 rounded">vp_stock</code> rows for the SKU,
                    then runs the same <em>Refresh stock</em> action as on the
                    <a href="?page=products&action=detail&amp;id=465565" class="text-orange-700 hover:underline font-medium">product detail</a>
                    page (clear ledger → fetch API local stock → reseed default warehouse).
                    Processes <strong>10 items per batch</strong>.
                </p>
            </div>
            <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50 shrink-0">Back to Products</a>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-900">
        <p class="font-semibold mb-1">Admin only</p>
        <p class="text-amber-900/90">Each refresh deletes <code class="text-xs bg-white/70 px-1 rounded">vp_stock_movements</code> and <code class="text-xs bg-white/70 px-1 rounded">vp_stock</code> for that SKU, resets physical stock, pulls latest local stock from the API, then seeds opening stock in the default warehouse.</p>
    </div>

    <div class="bg-white border rounded-xl shadow-sm p-4 sm:p-5 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-sm font-semibold text-gray-800">Eligible products</p>
                <p id="stockRefreshCandidateSummary" class="text-xs text-gray-500 mt-0.5">
                    <?php if ($candidateTotal > 0): ?>
                        About <?= number_format($candidateTotal) ?> product(s) match the criteria.
                    <?php else: ?>
                        No matching products found.
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" id="stockRefreshLoadBtn"
                    class="px-3 py-2 text-sm rounded-lg border border-gray-300 bg-white hover:bg-gray-50 font-medium">
                    Reload list
                </button>
                <button type="button" id="stockRefreshRunBtn" disabled
                    class="px-4 py-2 text-sm rounded-lg bg-orange-600 hover:bg-orange-700 disabled:bg-gray-300 disabled:cursor-not-allowed text-white font-semibold">
                    Refresh selected
                </button>
            </div>
        </div>

        <div id="stockRefreshProgressWrap" class="hidden rounded-lg border border-orange-200 bg-orange-50/50 p-3 space-y-2">
            <div class="flex items-center justify-between text-xs font-medium text-orange-900">
                <span id="stockRefreshProgressText">Processing…</span>
                <span id="stockRefreshProgressStats"></span>
            </div>
            <div class="h-2 rounded-full bg-orange-100 overflow-hidden">
                <div id="stockRefreshProgressBar" class="h-full bg-orange-500 transition-all duration-300" style="width:0%"></div>
            </div>
            <p id="stockRefreshProgressBatch" class="text-xs text-orange-800/80"></p>
        </div>

        <div class="overflow-x-auto rounded-lg border border-gray-200 max-h-[min(32rem,60vh)] overflow-y-auto">
            <table class="min-w-full text-sm divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0 z-10">
                    <tr>
                        <th class="px-3 py-2 text-left w-10">
                            <input type="checkbox" id="stockRefreshSelectAll" title="Select all loaded rows" disabled>
                        </th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">SKU</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Item code</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-700 w-16">Mvt</th>
                        <th class="px-3 py-2 text-right font-semibold text-gray-700 w-20">Local</th>
                        <th class="px-3 py-2 text-left font-semibold text-gray-700 w-24">Status</th>
                    </tr>
                </thead>
                <tbody id="stockRefreshTableBody" class="divide-y divide-gray-100 bg-white">
                    <tr>
                        <td colspan="6" class="px-3 py-8 text-center text-gray-500 text-sm">Click “Reload list” to load eligible products.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="stockRefreshLoadMoreWrap" class="hidden text-center">
            <button type="button" id="stockRefreshLoadMoreBtn"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 hover:bg-gray-50 font-medium">
                Load more…
            </button>
        </div>
    </div>

    <div id="stockRefreshLogPanel" class="hidden bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-800">Run log</div>
        <ul id="stockRefreshLogList" class="p-4 space-y-1.5 text-xs font-mono text-gray-700 max-h-64 overflow-y-auto"></ul>
    </div>
</div>

<script>
(function () {
    const BATCH_SIZE = 10;
    const PAGE_SIZE = 100;

    const loadBtn = document.getElementById('stockRefreshLoadBtn');
    const runBtn = document.getElementById('stockRefreshRunBtn');
    const selectAll = document.getElementById('stockRefreshSelectAll');
    const tbody = document.getElementById('stockRefreshTableBody');
    const summaryEl = document.getElementById('stockRefreshCandidateSummary');
    const loadMoreWrap = document.getElementById('stockRefreshLoadMoreWrap');
    const loadMoreBtn = document.getElementById('stockRefreshLoadMoreBtn');
    const progressWrap = document.getElementById('stockRefreshProgressWrap');
    const progressText = document.getElementById('stockRefreshProgressText');
    const progressStats = document.getElementById('stockRefreshProgressStats');
    const progressBar = document.getElementById('stockRefreshProgressBar');
    const progressBatch = document.getElementById('stockRefreshProgressBatch');
    const logPanel = document.getElementById('stockRefreshLogPanel');
    const logList = document.getElementById('stockRefreshLogList');

    let items = [];
    let currentPage = 0;
    let totalPages = 0;
    let totalCount = 0;
    let loading = false;
    let running = false;
    const statusById = {};

    function escapeHtml(text) {
        return String(text == null ? '' : text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function selectedProductIds() {
        return items
            .filter(function (row) {
                const cb = document.querySelector('.stock-refresh-row-cb[data-product-id="' + row.id + '"]');
                return cb && cb.checked;
            })
            .map(function (row) { return row.id; });
    }

    function updateRunButton() {
        const count = selectedProductIds().length;
        runBtn.disabled = running || count === 0;
        runBtn.textContent = count > 0 ? ('Refresh selected (' + count + ')') : 'Refresh selected';
    }

    function renderRows(append) {
        if (!append) {
            tbody.innerHTML = '';
        }
        if (items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="px-3 py-8 text-center text-gray-500 text-sm">No eligible products found.</td></tr>';
            selectAll.disabled = true;
            updateRunButton();
            return;
        }

        const frag = document.createDocumentFragment();
        const startIdx = append ? tbody.querySelectorAll('tr[data-product-id]').length : 0;
        for (let i = startIdx; i < items.length; i++) {
            const row = items[i];
            const id = parseInt(row.id, 10);
            const sku = String(row.sku || '').trim();
            const itemCode = String(row.item_code || '').trim();
            const mvt = parseInt(row.movement_count, 10) || 0;
            const local = parseInt(row.local_stock, 10) || 0;
            const status = statusById[id] || '';
            const tr = document.createElement('tr');
            tr.setAttribute('data-product-id', String(id));
            tr.className = 'hover:bg-orange-50/30';
            tr.innerHTML =
                '<td class="px-3 py-2 align-middle">' +
                    '<input type="checkbox" class="stock-refresh-row-cb rounded border-gray-300" data-product-id="' + id + '" checked>' +
                '</td>' +
                '<td class="px-3 py-2 align-middle">' +
                    '<a href="?page=products&amp;action=detail&amp;id=' + id + '" class="font-mono text-xs text-orange-800 hover:underline">' + escapeHtml(sku || '—') + '</a>' +
                '</td>' +
                '<td class="px-3 py-2 align-middle font-mono text-xs text-gray-700">' + escapeHtml(itemCode || '—') + '</td>' +
                '<td class="px-3 py-2 align-middle text-right tabular-nums text-gray-600">' + mvt + '</td>' +
                '<td class="px-3 py-2 align-middle text-right tabular-nums text-gray-600">' + local + '</td>' +
                '<td class="px-3 py-2 align-middle text-xs stock-refresh-status" data-product-id="' + id + '">' +
                    (status ? escapeHtml(status) : '<span class="text-gray-400">—</span>') +
                '</td>';
            frag.appendChild(tr);
        }
        if (!append) {
            tbody.innerHTML = '';
        }
        tbody.appendChild(frag);
        selectAll.disabled = false;
        selectAll.checked = true;
        updateRunButton();
    }

    function appendLog(line) {
        logPanel.classList.remove('hidden');
        const li = document.createElement('li');
        li.textContent = line;
        logList.appendChild(li);
        logList.scrollTop = logList.scrollHeight;
    }

    function setRowStatus(productId, text, tone) {
        statusById[productId] = text;
        const cell = document.querySelector('.stock-refresh-status[data-product-id="' + productId + '"]');
        if (!cell) return;
        const cls = tone === 'ok' ? 'text-green-700' : (tone === 'err' ? 'text-red-700' : 'text-gray-600');
        cell.innerHTML = '<span class="' + cls + '">' + escapeHtml(text) + '</span>';
    }

    function chunkIds(ids, size) {
        const out = [];
        for (let i = 0; i < ids.length; i += size) {
            out.push(ids.slice(i, i + size));
        }
        return out;
    }

    async function fetchCandidates(page, append) {
        const url = '?page=products&action=stock_rebuild_candidates&page=' + page + '&limit=' + PAGE_SIZE;
        const res = await fetch(url, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data || !data.success) {
            throw new Error((data && data.message) ? data.message : 'Could not load candidates.');
        }
        totalCount = parseInt(data.total, 10) || 0;
        totalPages = parseInt(data.pages, 10) || 0;
        currentPage = parseInt(data.page, 10) || page;
        const newItems = Array.isArray(data.items) ? data.items : [];
        if (append) {
            items = items.concat(newItems);
        } else {
            items = newItems;
        }
        if (summaryEl) {
            summaryEl.textContent = totalCount + ' eligible product(s) · showing ' + items.length + ' loaded';
        }
        renderRows(append);
        if (loadMoreWrap && loadMoreBtn) {
            if (currentPage < totalPages) {
                loadMoreWrap.classList.remove('hidden');
            } else {
                loadMoreWrap.classList.add('hidden');
            }
        }
    }

    async function loadAllCandidates() {
        loading = true;
        loadBtn.disabled = true;
        loadBtn.textContent = 'Loading…';
        try {
            await fetchCandidates(1, false);
            while (currentPage < totalPages) {
                await fetchCandidates(currentPage + 1, true);
            }
        } finally {
            loading = false;
            loadBtn.disabled = running;
            loadBtn.textContent = 'Reload list';
        }
    }

    async function runBatch(productIds) {
        const res = await fetch('?page=products&action=stock_rebuild_refresh_batch', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ product_ids: productIds }),
        });
        const raw = await res.text();
        let data = null;
        try {
            data = raw ? JSON.parse(raw) : null;
        } catch (e) {
            data = null;
        }
        if (!data || !Array.isArray(data.results)) {
            throw new Error((data && data.message) ? data.message : 'Batch refresh failed.');
        }
        return data;
    }

    function updateProgress(completed, total, succeeded, failed, batchNo, batchTotal) {
        progressWrap.classList.remove('hidden');
        const pct = total > 0 ? Math.min(100, Math.round((completed / total) * 100)) : 0;
        progressText.textContent = 'Processed ' + completed + ' of ' + total;
        progressStats.textContent = 'OK: ' + succeeded + ' · Failed: ' + failed;
        progressBar.style.width = pct + '%';
        progressBatch.textContent = batchTotal > 0
            ? ('Batch ' + batchNo + ' of ' + batchTotal + ' · ' + BATCH_SIZE + ' per batch')
            : '';
    }

    async function runSelectedRefresh() {
        const ids = selectedProductIds();
        if (ids.length === 0) {
            window.alert('Select at least one product.');
            return;
        }
        if (!window.confirm(
            'Refresh stock for ' + ids.length + ' selected product(s)?\n\n'
            + 'Same as product detail “Refresh stock”: clears movements, fetches API local stock, reseeds default warehouse.'
        )) {
            return;
        }

        running = true;
        runBtn.disabled = true;
        loadBtn.disabled = true;
        selectAll.disabled = true;
        document.querySelectorAll('.stock-refresh-row-cb').forEach(function (cb) { cb.disabled = true; });

        logList.innerHTML = '';
        logPanel.classList.remove('hidden');

        const batches = chunkIds(ids, BATCH_SIZE);
        let completed = 0;
        let succeeded = 0;
        let failed = 0;

        try {
            for (let b = 0; b < batches.length; b++) {
                updateProgress(completed, ids.length, succeeded, failed, b + 1, batches.length);
                appendLog('Starting batch ' + (b + 1) + '/' + batches.length + ' (' + batches[b].length + ' items)…');

                const data = await runBatch(batches[b]);
                (data.results || []).forEach(function (result) {
                    const pid = parseInt(result.product_id, 10) || 0;
                    if (result.success) {
                        succeeded++;
                        setRowStatus(pid, 'OK', 'ok');
                        appendLog('OK #' + pid + ' ' + (result.label || result.sku || ''));
                    } else {
                        failed++;
                        setRowStatus(pid, 'Failed', 'err');
                        appendLog('FAIL #' + pid + ' ' + ((result.message) ? result.message : 'Unknown error'));
                    }
                    completed++;
                });
                updateProgress(completed, ids.length, succeeded, failed, b + 1, batches.length);
            }

            appendLog('Done. ' + succeeded + ' succeeded, ' + failed + ' failed.');
            window.alert('Batch refresh finished.\n' + succeeded + ' succeeded, ' + failed + ' failed.');
        } catch (err) {
            appendLog('Error: ' + ((err && err.message) ? err.message : 'Request failed'));
            window.alert((err && err.message) ? err.message : 'Batch refresh failed.');
        } finally {
            running = false;
            loadBtn.disabled = false;
            selectAll.disabled = items.length === 0;
            document.querySelectorAll('.stock-refresh-row-cb').forEach(function (cb) { cb.disabled = false; });
            updateRunButton();
        }
    }

    loadBtn.addEventListener('click', function () {
        if (loading || running) return;
        loadAllCandidates().catch(function (err) {
            window.alert((err && err.message) ? err.message : 'Load failed.');
        });
    });

    loadMoreBtn.addEventListener('click', function () {
        if (loading || running || currentPage >= totalPages) return;
        loading = true;
        loadMoreBtn.disabled = true;
        fetchCandidates(currentPage + 1, true)
            .catch(function (err) {
                window.alert((err && err.message) ? err.message : 'Load failed.');
            })
            .finally(function () {
                loading = false;
                loadMoreBtn.disabled = false;
            });
    });

    runBtn.addEventListener('click', runSelectedRefresh);

    selectAll.addEventListener('change', function () {
        const checked = !!selectAll.checked;
        document.querySelectorAll('.stock-refresh-row-cb').forEach(function (cb) {
            cb.checked = checked;
        });
        updateRunButton();
    });

    tbody.addEventListener('change', function (e) {
        if (e.target && e.target.classList.contains('stock-refresh-row-cb')) {
            updateRunButton();
        }
    });

    if (<?= $candidateTotal > 0 ? 'true' : 'false' ?>) {
        loadAllCandidates().catch(function () {});
    }
})();
</script>
