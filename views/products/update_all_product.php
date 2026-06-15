<?php
$bulkMaxLimit = (int) ($bulk_max_limit ?? 5000);
$bulkCatalog = $bulk_catalog ?? [];
$bulkSession = $bulk_session ?? ['api_products_this_run' => 0, 'db_row_offset' => 0, 'db_rows_updated_this_run' => 0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk product update from API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .progress-bar { transition: width 0.3s ease; }
        .status-log {
            max-height: 400px;
            overflow-y: auto;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
            padding: 1rem;
            background-color: #f9fafb;
        }
        .log-entry { font-size: 0.875rem; margin-bottom: 0.5rem; line-height: 1.5; }
        .log-success { color: #059669; }
        .log-error { color: #dc2626; }
        .log-info { color: #2563eb; }
        .log-detail {
            font-size: 0.8125rem;
            margin: 0.25rem 0 0.75rem 1rem;
            padding: 0.75rem;
            border-radius: 0.375rem;
            background: #fff7ed;
            border: 1px solid #fdba74;
            color: #9a3412;
            white-space: pre-wrap;
            word-break: break-word;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <div class="bg-white rounded-lg shadow-lg p-6">
            <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">Bulk product update from API</h1>
                    <p class="text-gray-600 max-w-2xl">
                        Syncs product fields from Exotic India <code class="text-sm bg-gray-100 px-1 rounded">product/fetch</code>
                        into <code class="text-sm bg-gray-100 px-1 rounded">vp_products</code>.
                        Each run processes up to <strong><?php echo number_format($bulkMaxLimit); ?></strong> API products, then stops.
                    </p>
                </div>
                <a href="index.php?page=products&action=list" class="text-sm text-amber-700 hover:underline font-semibold">← Back to products</a>
            </div>

            <!-- Catalog overview -->
            <div class="mb-6 rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Catalog overview</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">Total rows in vp_products</p>
                        <p class="text-xl font-bold text-slate-800" id="statTotalDbRows">—</p>
                        <p class="text-xs text-gray-400 mt-1">Every size/color variant row</p>
                    </div>
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">Pending sync (update_flag 0/null)</p>
                        <p class="text-xl font-bold text-amber-700" id="statPendingDbRows">—</p>
                        <p class="text-xs text-gray-400 mt-1">Rows still in the queue</p>
                    </div>
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">Already synced (update_flag 1)</p>
                        <p class="text-xl font-bold text-green-700" id="statUpdatedDbRows">—</p>
                    </div>
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">Distinct item codes (total)</p>
                        <p class="text-xl font-bold text-slate-800" id="statDistinctTotal">—</p>
                    </div>
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">Distinct item codes (pending)</p>
                        <p class="text-xl font-bold text-amber-700" id="statDistinctPending">—</p>
                    </div>
                    <div class="bg-white rounded-md p-3 border border-slate-100">
                        <p class="text-gray-500">This run limit</p>
                        <p class="text-xl font-bold text-indigo-700"><?php echo number_format($bulkMaxLimit); ?></p>
                        <p class="text-xs text-gray-400 mt-1">API products per session</p>
                    </div>
                </div>
                <p class="text-xs text-gray-500 mt-3" id="catalogNote">
                    One API item code often updates multiple DB rows (size/color variants), so “DB rows updated” can be higher than “API products processed”.
                </p>
            </div>

            <!-- Current session -->
            <div class="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-4">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Current run progress</h2>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-semibold text-gray-700">API products this run</label>
                    <span class="text-sm font-bold text-orange-600">
                        <span id="progressPercent">0</span>%
                        (<span id="importedCount">0</span> / <span id="maxLimit"><?php echo $bulkMaxLimit; ?></span>)
                    </span>
                </div>
                <div class="w-full bg-gray-200 rounded-full overflow-hidden mb-3">
                    <div id="progressBar" class="progress-bar bg-orange-600 h-6 flex items-center justify-center text-white text-xs font-bold" style="width: 0%">0%</div>
                </div>
                <p class="text-xs text-gray-600">DB scan offset this run: <span id="sessionDbOffset">0</span> rows</p>
            </div>

            <div class="mb-8">
                <h2 class="text-lg font-semibold text-gray-800 mb-3">Status messages</h2>
                <div id="statusLog" class="status-log">
                    <div class="log-entry log-info">Loading catalog stats…</div>
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
                <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
                    <p class="text-sm text-gray-600 font-semibold">API products (this batch)</p>
                    <p class="text-2xl font-bold text-blue-600"><span id="batchImported">0</span></p>
                </div>
                <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                    <p class="text-sm text-gray-600 font-semibold">API products (this run total)</p>
                    <p class="text-2xl font-bold text-green-600"><span id="totalImported">0</span></p>
                </div>
                <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
                    <p class="text-sm text-gray-600 font-semibold">API items in batch response</p>
                    <p class="text-2xl font-bold text-purple-600"><span id="batchTotal">0</span></p>
                </div>
                <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
                    <p class="text-sm text-gray-600 font-semibold">DB rows updated (this run)</p>
                    <p class="text-2xl font-bold text-indigo-600"><span id="affectedRows">0</span></p>
                </div>
                <div class="bg-orange-50 rounded-lg p-4 border border-orange-200 md:col-span-2">
                    <p class="text-sm text-gray-600 font-semibold">Status</p>
                    <p class="text-lg font-bold text-orange-600"><span id="statusText">Ready</span></p>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 mb-6">
                <button id="startBtn" onclick="startProcess()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold transition">
                    Start / Continue run
                </button>
                <button id="pauseBtn" onclick="pauseProcess()" class="px-6 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 font-semibold transition hidden">Pause</button>
                <button id="resumeBtn" onclick="resumeProcess()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold transition hidden">Resume</button>
                <button id="stopBtn" onclick="stopProcess()" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 font-semibold transition hidden">Stop</button>
                <button id="reloadBtn" onclick="location.reload()" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 font-semibold transition hidden">Reload page</button>
            </div>

            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                <h3 class="font-semibold text-amber-900 mb-2">Start fresh</h3>
                <p class="text-sm text-amber-900 mb-3">Use when you want to reset this page’s run counters or re-queue the entire catalog for another full sync.</p>
                <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center">
                    <label class="flex items-center gap-2 text-sm text-amber-950">
                        <input type="checkbox" id="requeueAllCheckbox" class="rounded border-amber-400">
                        Also re-queue all products (sets update_flag = 0 on every row)
                    </label>
                    <button id="resetBtn" onclick="startFresh()" class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold text-sm whitespace-nowrap">
                        Reset &amp; start fresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const CONFIG = {
            SECRET_KEY: '<?php echo EXPECTED_SECRET_KEY; ?>',
            MAX_LIMIT: <?php echo (int) $bulkMaxLimit; ?>,
            SLEEP_BUFFER: 1000,
            INITIAL_CATALOG: <?php echo json_encode($bulkCatalog, JSON_UNESCAPED_UNICODE); ?>,
            INITIAL_SESSION: <?php echo json_encode($bulkSession, JSON_UNESCAPED_UNICODE); ?>
        };

        let state = {
            isRunning: false,
            isPaused: false,
            totalImported: CONFIG.INITIAL_SESSION.api_products_this_run || 0,
            maxLimit: CONFIG.MAX_LIMIT,
            batchImported: 0,
            batchTotal: 0,
            progressPercent: 0,
            completed: false,
            errorCount: 0,
            affectedRows: CONFIG.INITIAL_SESSION.db_rows_updated_this_run || 0,
            catalog: CONFIG.INITIAL_CATALOG || {},
            sessionDbOffset: CONFIG.INITIAL_SESSION.db_row_offset || 0
        };

        function fmt(n) {
            return Number(n || 0).toLocaleString();
        }

        function applyCatalogStats(catalog) {
            if (!catalog) return;
            state.catalog = catalog;
            document.getElementById('statTotalDbRows').textContent = fmt(catalog.total_db_rows);
            document.getElementById('statPendingDbRows').textContent = fmt(catalog.pending_db_rows);
            document.getElementById('statUpdatedDbRows').textContent = fmt(catalog.updated_db_rows);
            document.getElementById('statDistinctTotal').textContent = fmt(catalog.distinct_item_codes_total);
            document.getElementById('statDistinctPending').textContent = fmt(catalog.distinct_item_codes_pending);
            if (!catalog.has_update_flag) {
                document.getElementById('catalogNote').textContent =
                    'Note: update_flag column is missing — all rows are eligible each run.';
            }
        }

        function applySessionStats(session) {
            if (!session) return;
            if (typeof session.api_products_this_run === 'number') {
                state.totalImported = session.api_products_this_run;
            }
            if (typeof session.db_rows_updated_this_run === 'number') {
                state.affectedRows = session.db_rows_updated_this_run;
            }
            if (typeof session.db_row_offset === 'number') {
                state.sessionDbOffset = session.db_row_offset;
            }
            state.progressPercent = state.maxLimit > 0
                ? Math.min(100, Math.round((state.totalImported / state.maxLimit) * 100))
                : 0;
        }

        function addLog(message, type = 'info') {
            const statusLog = document.getElementById('statusLog');
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.className = `log-entry log-${type}`;
            logEntry.textContent = `[${timestamp}] ${message}`;
            statusLog.appendChild(logEntry);
            statusLog.scrollTop = statusLog.scrollHeight;
        }

        function addLogDetail(title, body) {
            if (!body) return;
            const detail = document.createElement('div');
            detail.className = 'log-detail';
            detail.textContent = (title ? title + '\n\n' : '') + body;
            document.getElementById('statusLog').appendChild(detail);
        }

        function stripHtml(html) {
            const el = document.createElement('div');
            el.innerHTML = html;
            return (el.textContent || el.innerText || '').replace(/\s+/g, ' ').trim();
        }

        function extractPhpErrorsFromHtml(rawBody) {
            const errors = [];
            const pattern = /<b>(Fatal error|Parse error|Warning|Notice|Deprecated)<\/b>:\s*([^<]+)/gi;
            let match;
            while ((match = pattern.exec(rawBody)) !== null) {
                errors.push((match[1] || 'Error') + ': ' + (match[2] || '').trim());
            }
            return [...new Set(errors)];
        }

        function logServerFailure(data, rawBody, httpStatus) {
            const parts = [];
            if (data && data.message) parts.push(data.message);
            if (data && data.php_errors) parts.push(...data.php_errors);
            if (data && data.errors) parts.push(...data.errors);
            if (!parts.length && rawBody) {
                parts.push(...extractPhpErrorsFromHtml(rawBody));
                if (!parts.length) parts.push(stripHtml(rawBody).slice(0, 2000));
            }
            if (!parts.length) parts.push('HTTP ' + (httpStatus || 'unknown'));
            addLog('✗ ' + parts[0], 'error');
            if (parts.length > 1) addLogDetail('Details', parts.join('\n'));
            else if (rawBody) addLogDetail('Raw server response', stripHtml(rawBody).slice(0, 4000));
        }

        function updateUI() {
            document.getElementById('progressPercent').textContent = state.progressPercent;
            document.getElementById('importedCount').textContent = fmt(state.totalImported);
            document.getElementById('maxLimit').textContent = fmt(state.maxLimit);
            document.getElementById('progressBar').style.width = state.progressPercent + '%';
            document.getElementById('progressBar').textContent = state.progressPercent + '%';
            document.getElementById('batchImported').textContent = fmt(state.batchImported);
            document.getElementById('totalImported').textContent = fmt(state.totalImported);
            document.getElementById('batchTotal').textContent = fmt(state.batchTotal);
            document.getElementById('affectedRows').textContent = fmt(state.affectedRows);
            document.getElementById('sessionDbOffset').textContent = fmt(state.sessionDbOffset);

            const st = document.getElementById('statusText');
            if (!state.isRunning && !state.completed) st.textContent = 'Ready';
            else if (state.isPaused) st.textContent = 'Paused';
            else if (state.completed) st.textContent = 'Completed';
            else st.textContent = 'Processing…';
        }

        async function refreshStats() {
            const response = await fetch('index.php?page=products&action=bulk_product_update_stats', {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();
            if (data.success) {
                applyCatalogStats(data.catalog);
                applySessionStats(data.session);
                if (data.max_limit) state.maxLimit = data.max_limit;
                updateUI();
            }
            return data;
        }

        async function startFresh() {
            if (state.isRunning) {
                alert('Stop the current run before resetting.');
                return;
            }
            const requeueAll = document.getElementById('requeueAllCheckbox').checked;
            let msg = requeueAll
                ? 'Reset run progress AND mark all product rows pending again (update_flag = 0)? This can take a while on large catalogs.'
                : 'Reset run progress only? Pending queue in the database stays as-is.';
            if (!confirm(msg)) return;

            document.getElementById('resetBtn').disabled = true;
            try {
                const body = new FormData();
                if (requeueAll) body.append('requeue_all', '1');
                const response = await fetch('index.php?page=products&action=bulk_product_update_reset', {
                    method: 'POST',
                    body,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await response.json();
                if (!data.success) {
                    addLog('Reset failed: ' + (data.message || 'Unknown error'), 'error');
                    return;
                }
                state.totalImported = 0;
                state.affectedRows = 0;
                state.batchImported = 0;
                state.batchTotal = 0;
                state.progressPercent = 0;
                state.completed = false;
                state.errorCount = 0;
                state.sessionDbOffset = 0;
                applyCatalogStats(data.catalog);
                applySessionStats(data.session);
                updateUI();
                addLog(data.message || 'Reset complete.', 'success');
            } catch (e) {
                addLog('Reset failed: ' + e.message, 'error');
            } finally {
                document.getElementById('resetBtn').disabled = false;
            }
        }

        function setRunningUi(running) {
            document.getElementById('startBtn').classList.toggle('hidden', running);
            document.getElementById('pauseBtn').classList.toggle('hidden', !running);
            document.getElementById('stopBtn').classList.toggle('hidden', !running);
            document.getElementById('reloadBtn').classList.add('hidden');
            document.getElementById('resetBtn').disabled = running;
        }

        function startProcess() {
            state.isRunning = true;
            state.isPaused = false;
            state.completed = false;
            setRunningUi(true);
            addLog('Starting bulk product update…', 'success');
            updateUI();
            processBatch();
        }

        async function processBatch() {
            if (state.completed || !state.isRunning) return;
            if (state.isPaused) {
                setTimeout(processBatch, 1000);
                return;
            }

            try {
                const url = `index.php?page=products&action=updateAllProductScript&secret_key=${CONFIG.SECRET_KEY}`;
                addLog('Fetching batch from server…', 'info');

                const response = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const rawBody = await response.text();
                let data;
                try { data = JSON.parse(rawBody); } catch (e) {
                    logServerFailure(null, rawBody, response.status);
                    state.errorCount++;
                    state.completed = true;
                    finishProcess();
                    return;
                }

                if (!response.ok || !data.success) {
                    logServerFailure(data, rawBody, response.status);
                    state.errorCount++;
                    state.completed = true;
                    finishProcess();
                    return;
                }

                applyCatalogStats(data.catalog);
                applySessionStats(data.session);

                state.batchImported = data.batch_imported || 0;
                state.batchTotal = data.batch_total_items || 0;
                state.totalImported = data.total_imported ?? state.totalImported;
                state.progressPercent = data.progress_percent || 0;
                state.completed = data.completed || false;
                state.maxLimit = data.max_limit || CONFIG.MAX_LIMIT;
                if (typeof data.session_affected_rows === 'number') {
                    state.affectedRows = data.session_affected_rows;
                } else {
                    state.affectedRows += (data.batch_affected_rows || data.affected_rows || 0);
                }

                if (state.batchImported > 0) {
                    addLog(`Batch: ${state.batchImported} API products → ${data.batch_affected_rows || 0} DB rows updated`, 'success');
                } else {
                    addLog(`Batch checked ${state.batchTotal} API items`, 'info');
                }
                addLog(`Run progress: ${state.totalImported} / ${state.maxLimit} API products (${state.progressPercent}%)`, 'info');
                if (data.catalog) {
                    addLog(`Catalog: ${fmt(data.catalog.pending_db_rows)} rows still pending of ${fmt(data.catalog.total_db_rows)} total`, 'info');
                }

                if (data.errors && data.errors.length) {
                    data.errors.forEach(err => addLog('⚠ ' + err, 'error'));
                    state.errorCount += data.error_count || 0;
                }

                updateUI();

                if (state.completed) {
                    addLog('Run finished.', 'success');
                    finishProcess();
                    return;
                }

                if (data.next_action) {
                    const waitMs = ((data.next_action.wait_seconds || 5) * 1000) + CONFIG.SLEEP_BUFFER;
                    document.getElementById('statusText').textContent = `Waiting (${data.next_action.wait_seconds || 5}s)`;
                    setTimeout(processBatch, waitMs);
                } else {
                    setTimeout(processBatch, 100);
                }
            } catch (error) {
                addLog('✗ ' + error.message, 'error');
                state.errorCount++;
                state.completed = true;
                finishProcess();
            }
        }

        function finishProcess() {
            state.isRunning = false;
            state.completed = true;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            document.getElementById('stopBtn').classList.add('hidden');
            document.getElementById('reloadBtn').classList.remove('hidden');
            document.getElementById('resetBtn').disabled = false;
            addLog(`Summary: ${state.totalImported} API products, ${state.affectedRows} DB rows updated, ${state.errorCount} errors`, state.errorCount ? 'error' : 'success');
            refreshStats();
            updateUI();
        }

        function pauseProcess() {
            state.isPaused = true;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.remove('hidden');
            addLog('Paused.', 'info');
            updateUI();
        }

        function resumeProcess() {
            state.isPaused = false;
            document.getElementById('pauseBtn').classList.remove('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            addLog('Resumed.', 'info');
            processBatch();
        }

        function stopProcess() {
            state.isRunning = false;
            document.getElementById('pauseBtn').classList.add('hidden');
            document.getElementById('resumeBtn').classList.add('hidden');
            document.getElementById('stopBtn').classList.add('hidden');
            document.getElementById('reloadBtn').classList.remove('hidden');
            document.getElementById('resetBtn').disabled = false;
            addLog('Stopped by user.', 'error');
            updateUI();
        }

        window.addEventListener('load', async function() {
            document.getElementById('statusLog').innerHTML = '';
            applyCatalogStats(CONFIG.INITIAL_CATALOG);
            applySessionStats(CONFIG.INITIAL_SESSION);
            updateUI();
            addLog('Ready. Use Start to sync pending products, or Start fresh to reset this run.', 'info');
            try { await refreshStats(); } catch (e) { /* initial PHP stats already shown */ }
        });

        window.addEventListener('beforeunload', function(e) {
            if (state.isRunning && !state.completed) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>
