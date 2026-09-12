/**
 * Batch order-status sync runner with All / Partial modes, progress, and Stop.
 */
(function (window) {
    function escapeHtml(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function chunk(list, size) {
        var out = [];
        var n = Math.max(1, parseInt(size, 10) || 50);
        for (var i = 0; i < list.length; i += n) {
            out.push(list.slice(i, i + n));
        }
        return out;
    }

    function createOrderStatusSyncController(config) {
        var ids = config.ids || {};
        var state = {
            running: false,
            stopRequested: false,
            abortController: null,
            totals: { checked: 0, updated: 0, unchanged: 0, skipped: 0 },
            details: [],
            errors: []
        };

        function el(key) {
            return document.getElementById(ids[key] || key);
        }

        function setBusy(isBusy) {
            var startBtn = el('startBtn');
            var stopBtn = el('stopBtn');
            var formEls = document.querySelectorAll(config.formSelector || '.oss-field');
            if (startBtn) {
                startBtn.disabled = isBusy;
                startBtn.innerHTML = isBusy
                    ? '<i class="fas fa-spinner fa-spin"></i> Running…'
                    : (config.startLabel || '<i class="fas fa-play"></i> Start sync');
            }
            if (stopBtn) {
                stopBtn.disabled = !isBusy;
            }
            formEls.forEach(function (node) {
                node.disabled = isBusy;
            });
        }

        function setProgress(done, total, label) {
            var wrap = el('progressWrap');
            var bar = el('progressBar');
            var pctEl = el('progressPercent');
            var labelEl = el('progressLabel');
            var countEl = el('progressCount');
            if (wrap) {
                wrap.classList.remove('hidden');
                wrap.hidden = false;
            }
            var pct = total > 0 ? Math.round((done / total) * 100) : 0;
            if (bar) {
                bar.style.width = pct + '%';
            }
            if (pctEl) {
                pctEl.textContent = pct + '%';
            }
            if (labelEl) {
                labelEl.textContent = label || '';
            }
            if (countEl) {
                countEl.textContent = done + ' / ' + total;
            }
        }

        function appendLog(message, tone) {
            var log = el('log');
            if (!log) {
                return;
            }
            var line = document.createElement('div');
            line.className = 'oss-log-line oss-log-' + (tone || 'info');
            line.innerHTML = '<span class="oss-log-time">[' + new Date().toLocaleTimeString() + ']</span> ' + message;
            log.appendChild(line);
            log.scrollTop = log.scrollHeight;
        }

        function renderSummary(statusLabel) {
            var badges = el('badges');
            var details = el('details');
            var resultWrap = el('resultWrap');
            if (resultWrap) {
                resultWrap.classList.remove('hidden');
                resultWrap.hidden = false;
            }
            if (badges) {
                badges.innerHTML =
                    '<span class="oss-badge oss-badge-status">' + escapeHtml(statusLabel) + '</span>' +
                    '<span class="oss-badge oss-badge-blue">Checked: ' + state.totals.checked + '</span>' +
                    '<span class="oss-badge oss-badge-green">Changed: ' + state.totals.updated + '</span>' +
                    '<span class="oss-badge oss-badge-gray">Unchanged: ' + state.totals.unchanged + '</span>' +
                    '<span class="oss-badge oss-badge-amber">Skipped: ' + state.totals.skipped + '</span>';
            }
            if (details) {
                if (!state.details.length && !state.errors.length) {
                    details.innerHTML = '<div class="oss-muted">No status changes in the batches that ran.</div>';
                    return;
                }
                var html = '';
                state.details.forEach(function (d) {
                    html += '<div>• Order #' + escapeHtml(d.order_number) +
                        ' (' + escapeHtml(d.item_code || '') + '): ' +
                        '<span class="oss-old">' + escapeHtml(d.old_status) + '</span> → ' +
                        '<span class="oss-new">' + escapeHtml(d.new_status) + '</span></div>';
                });
                state.errors.forEach(function (err) {
                    html += '<div class="oss-log-error">! ' + escapeHtml(err) + '</div>';
                });
                details.innerHTML = html;
            }
        }

        async function postJson(payload) {
            state.abortController = new AbortController();
            var response = await fetch(config.endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify(payload),
                signal: state.abortController.signal
            });
            var data = await response.json();
            if (!response.ok || data.success === false) {
                throw new Error(data.message || ('Request failed (' + response.status + ')'));
            }
            return data;
        }

        function resetRun() {
            state.totals = { checked: 0, updated: 0, unchanged: 0, skipped: 0 };
            state.details = [];
            state.errors = [];
            var log = el('log');
            if (log) {
                log.innerHTML = '';
            }
        }

        async function refreshCount() {
            var countEl = el('pendingCount');
            if (!countEl) {
                return;
            }
            try {
                var data = await postJson({ op: 'count' });
                countEl.textContent = String(data.total != null ? data.total : 0);
            } catch (err) {
                countEl.textContent = '—';
            }
        }

        async function start() {
            if (state.running) {
                return;
            }
            var modeInput = document.querySelector(config.modeSelector || 'input[name="oss_mode"]:checked');
            var mode = modeInput ? modeInput.value : 'partial';
            var limitEl = el('limit');
            var batchEl = el('batchSize');
            var orderEl = el('orderIds');
            var dryEl = el('dryRun');
            var limit = limitEl ? parseInt(limitEl.value, 10) : 250;
            var batchSize = batchEl ? parseInt(batchEl.value, 10) : 50;
            var orderIds = orderEl ? String(orderEl.value || '').trim() : '';
            var dryRun = !!(dryEl && dryEl.checked);

            if (mode === 'specific' && orderIds === '') {
                appendLog('Enter at least one order number for a specific run.', 'error');
                renderSummary('Need order numbers');
                return;
            }

            state.running = true;
            state.stopRequested = false;
            resetRun();
            setBusy(true);
            setProgress(0, 1, 'Loading candidate orders…');
            appendLog('Starting ' + (mode === 'all' ? 'ALL' : mode === 'specific' ? 'specific' : 'partial') +
                ' sync' + (dryRun ? ' (dry run)' : ' (live write)') + '.');

            try {
                var candidates = await postJson({
                    op: 'candidates',
                    mode: mode,
                    limit: limit,
                    order_id: orderIds
                });
                if (state.stopRequested) {
                    setProgress(0, candidates.total || 0, 'Stopped before first batch');
                    renderSummary('Stopped');
                    appendLog('Stopped by user before any batch ran.', 'warn');
                    return;
                }

                var orders = candidates.order_numbers || [];
                if (!orders.length) {
                    setProgress(0, 0, 'Nothing to sync');
                    renderSummary('No candidates');
                    appendLog('No non-terminal orders found.', 'warn');
                    return;
                }

                var batches = chunk(orders, batchSize);
                appendLog('Queued ' + orders.length + ' order(s) in ' + batches.length + ' batch(es). Oldest first.');

                for (var i = 0; i < batches.length; i++) {
                    if (state.stopRequested) {
                        break;
                    }
                    var batchNum = i + 1;
                    setProgress(i, batches.length, 'Batch ' + batchNum + ' of ' + batches.length + ' (' + batches[i].length + ' orders)…');
                    appendLog('Processing batch ' + batchNum + '/' + batches.length + '…');

                    var batchRes;
                    try {
                        batchRes = await postJson({
                            op: 'sync_batch',
                            order_numbers: batches[i],
                            dry_run: dryRun
                        });
                    } catch (batchErr) {
                        if (state.stopRequested || (batchErr && batchErr.name === 'AbortError')) {
                            break;
                        }
                        throw batchErr;
                    }

                    var summary = batchRes.summary || {};
                    state.totals.checked += parseInt(summary.checked_orders || 0, 10);
                    state.totals.updated += parseInt(summary.updated_lines || 0, 10);
                    state.totals.unchanged += parseInt(summary.unchanged_lines || 0, 10);
                    state.totals.skipped += parseInt(summary.skipped_lines || 0, 10);
                    if (summary.details && summary.details.length) {
                        state.details = state.details.concat(summary.details);
                    }
                    if (summary.errors && summary.errors.length) {
                        state.errors = state.errors.concat(summary.errors);
                        summary.errors.forEach(function (err) {
                            appendLog(escapeHtml(err), 'error');
                        });
                    }
                    appendLog(
                        'Batch ' + batchNum + ' done. Changed ' + (summary.updated_lines || 0) +
                        ', unchanged ' + (summary.unchanged_lines || 0) + '.',
                        'ok'
                    );
                    setProgress(batchNum, batches.length, 'Batch ' + batchNum + ' of ' + batches.length + ' complete');
                    renderSummary(state.stopRequested ? 'Stopping…' : 'Running');
                }

                if (state.stopRequested) {
                    setProgress(Math.min(i, batches.length), batches.length, 'Stopped by user');
                    renderSummary('Stopped by user');
                    appendLog('Stopped. Remaining batches were not sent.', 'warn');
                } else {
                    setProgress(batches.length, batches.length, 'Completed');
                    renderSummary(dryRun ? 'Dry run complete' : 'Completed');
                    appendLog('Sync finished.', 'ok');
                }
            } catch (err) {
                if (state.stopRequested || (err && err.name === 'AbortError')) {
                    renderSummary('Stopped by user');
                    appendLog('Stopped by user.', 'warn');
                } else {
                    renderSummary('Failed');
                    appendLog(escapeHtml(err.message || err), 'error');
                }
            } finally {
                state.running = false;
                state.abortController = null;
                setBusy(false);
                refreshCount();
            }
        }

        function stop() {
            if (!state.running) {
                return;
            }
            state.stopRequested = true;
            if (state.abortController) {
                state.abortController.abort();
            }
            appendLog('Stop requested. Current API call will be cancelled; no further batches will start.', 'warn');
        }

        function bind() {
            var form = el('form');
            var startBtn = el('startBtn');
            var stopBtn = el('stopBtn');
            if (form) {
                form.addEventListener('submit', function (e) {
                    e.preventDefault();
                    start();
                });
            }
            if (startBtn && !form) {
                startBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    start();
                });
            }
            if (stopBtn) {
                stopBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    stop();
                });
            }
            document.querySelectorAll(config.modeSelector || 'input[name="oss_mode"]').forEach(function (input) {
                input.addEventListener('change', syncModeFields);
            });
            syncModeFields();
            refreshCount();
        }

        function syncModeFields() {
            var modeInput = document.querySelector((config.modeSelector || 'input[name="oss_mode"]') + ':checked');
            var mode = modeInput ? modeInput.value : 'partial';
            var partialFields = el('partialFields');
            var specificFields = el('specificFields');
            if (partialFields) {
                partialFields.classList.toggle('hidden', mode !== 'partial');
                partialFields.hidden = mode !== 'partial';
            }
            if (specificFields) {
                specificFields.classList.toggle('hidden', mode !== 'specific');
                specificFields.hidden = mode !== 'specific';
            }
        }

        return { start: start, stop: stop, bind: bind, refreshCount: refreshCount };
    }

    window.createOrderStatusSyncController = createOrderStatusSyncController;
})(window);
