<?php
  $jobId = (int)($job['id'] ?? 0);
  $statusFilter = $status_filter ?? 'all';
  $pageNo = (int)($page_no ?? 1);
  $limitVal = (int)($limit ?? 100);
  $totalPages = max(1, (int)($total_pages ?? 1));
  $pendingItems = max(0, (int)($job['total_items'] ?? 0) - (int)($job['processed_items'] ?? 0));
  $jtTotalMs = (int)($job['total_processing_ms'] ?? 0);
  $jtLastMs = isset($job['last_batch_duration_ms']) && $job['last_batch_duration_ms'] !== null && $job['last_batch_duration_ms'] !== ''
    ? (int)$job['last_batch_duration_ms'] : null;
  $jtProc = (int)($job['processed_items'] ?? 0);
  $jtTot = (int)($job['total_items'] ?? 0);
  $jtAvgMs = ($jtProc > 0 && $jtTotalMs > 0) ? (int)round($jtTotalMs / $jtProc) : null;
  $jtEtaMs = ($pendingItems > 0 && $jtProc > 0 && $jtTotalMs > 0) ? (int)round(($jtTotalMs / $jtProc) * $pendingItems) : null;
  $jtExtrapMs = ($jtProc > 0 && $jtTotalMs > 0 && $jtTot > 0) ? (int)round(($jtTotalMs / $jtProc) * $jtTot) : null;
?>

<div class="max-w-7xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Bulk Import Detail #<?= $jobId ?></h2>
        <p class="text-sm text-gray-600">
          File: <span class="font-medium"><?= htmlspecialchars($job['file_name'] ?? '') ?></span> |
          Warehouse: <span class="font-medium"><?= htmlspecialchars($job['warehouse_name'] ?? ('#' . (int)($job['warehouse_id'] ?? 0))) ?></span> |
          Imported by: <span class="font-medium"><?= htmlspecialchars($job['created_by_name'] ?? ('User #' . (int)($job['created_by'] ?? 0))) ?></span>
        </p>
      </div>
      <a href="?page=products&action=bulk_import" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Jobs</a>
    </div>

    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm mb-4">
      <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Total</div><div class="font-semibold" id="totalItems"><?= (int)($job['total_items'] ?? 0) ?></div></div>
      <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Processed</div><div class="font-semibold" id="processedItems"><?= (int)($job['processed_items'] ?? 0) ?></div></div>
      <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Success</div><div class="font-semibold text-green-700" id="successItems"><?= (int)($job['success_items'] ?? 0) ?></div></div>
      <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Failed</div><div class="font-semibold text-red-700" id="failedItems"><?= (int)($job['failed_items'] ?? 0) ?></div></div>
      <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Pending</div><div class="font-semibold text-amber-700" id="pendingItems"><?= $pendingItems ?></div></div>
    </div>

    <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
      <?php $pct = ((int)($job['total_items'] ?? 0) > 0) ? (int)round(((int)($job['processed_items'] ?? 0) * 100) / (int)$job['total_items']) : 0; ?>
      <div id="progressBar" class="bg-amber-600 h-2.5 rounded-full" style="width: <?= $pct ?>%"></div>
    </div>

    <div class="text-sm text-gray-600 border border-gray-100 rounded-lg p-3 mb-4 bg-gray-50/80">
      <div class="font-medium text-gray-700 mb-1">Import timing (for capacity estimates)</div>
      <p id="timingDetailLine" class="tabular-nums">
        Total processing: <span id="timingTotalWall"><?= $jtTotalMs > 0 ? htmlspecialchars(sprintf('%.2fs', $jtTotalMs / 1000)) : '—' ?></span>
        <span class="mx-2 text-gray-300">|</span>
        Last batch: <span id="timingLastBatch"><?= ($jtLastMs !== null && $jtLastMs > 0) ? htmlspecialchars(sprintf('%.2fs', $jtLastMs / 1000)) : '—' ?></span>
        <span class="mx-2 text-gray-300">|</span>
        Avg / row (processed): <span id="timingAvgRow"><?= $jtAvgMs !== null ? htmlspecialchars(sprintf('%.2fs', $jtAvgMs / 1000)) : '—' ?></span>
        <span class="mx-2 text-gray-300">|</span>
        ETA (pending, linear): <span id="timingEta"><?= $jtEtaMs !== null ? htmlspecialchars(sprintf('%.2fs', $jtEtaMs / 1000)) : '—' ?></span>
        <span class="mx-2 text-gray-300">|</span>
        Extrapolated full job: <span id="timingExtrap"><?= $jtExtrapMs !== null ? htmlspecialchars(sprintf('%.2fs', $jtExtrapMs / 1000)) : '—' ?></span>
      </p>
      <p class="text-xs text-gray-500 mt-1">Extrapolation uses (total processing time ÷ processed rows). For a ~300k row job, treat this as an indicative range until you have a long steady-state sample.</p>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
      <button id="startProcessBtn" type="button" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Start / Resume Processing</button>
      <button id="reuploadBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">ReUpload</button>
      <button id="refreshStatusBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Refresh Status</button>
      <button id="retryFailedBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Retry all failed</button>
      <button id="retryPendingBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Retry Pending</button>
      <button id="deleteFailedBtn" type="button" class="px-4 py-2 border border-red-300 text-red-700 rounded hover:bg-red-50 text-sm">Delete all failed</button>
    </div>

    <form method="get" class="flex flex-wrap gap-2 items-end mb-3">
      <input type="hidden" name="page" value="products">
      <input type="hidden" name="action" value="bulk_import_detail">
      <input type="hidden" name="job_id" value="<?= $jobId ?>">
      <div>
        <label class="block text-xs text-gray-500 mb-1">Status</label>
        <select name="status" class="px-3 py-2 border rounded text-sm">
          <?php foreach (['all' => 'All', 'pending' => 'Pending', 'processing' => 'Processing', 'success' => 'Success', 'failed' => 'Failed'] as $k => $v): ?>
            <option value="<?= $k ?>" <?= $statusFilter === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-xs text-gray-500 mb-1">Rows</label>
        <select name="limit" class="px-3 py-2 border rounded text-sm">
          <?php foreach ([50, 100, 200, 500] as $l): ?>
            <option value="<?= $l ?>" <?= $limitVal === $l ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <button type="submit" class="px-3 py-2 rounded border text-sm hover:bg-gray-50">Apply</button>
    </form>

    <div class="overflow-x-auto rounded-lg border">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-left">#</th>
            <th class="px-3 py-2 text-left">SKU</th>
            <th class="px-3 py-2 text-right" title="Quantity from import file; when empty, opening stock uses product local stock">Qty</th>
            <th class="px-3 py-2 text-right" title="Matched product local_stock (used when file qty is empty)">Local stock</th>
            <th class="px-3 py-2 text-left">Location</th>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Attempts</th>
            <th class="px-3 py-2 text-left">Error</th>
            <th class="px-3 py-2 text-left">Updated</th>
            <th class="px-3 py-2 text-left">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No records found.</td></tr>
          <?php else: ?>
            <?php foreach ($rows as $r): ?>
              <?php
                $st = $r['status'] ?? 'pending';
                $stClass = 'bg-gray-100 text-gray-700';
                if ($st === 'processing') $stClass = 'bg-amber-100 text-amber-700';
                if ($st === 'success') $stClass = 'bg-green-100 text-green-700';
                if ($st === 'failed') $stClass = 'bg-red-100 text-red-700';
              ?>
              <tr class="border-t">
                <td class="px-3 py-2"><?= (int)$r['id'] ?></td>
                <td class="px-3 py-2">
                  <?php
                    $displaySku = (($r['product_sku'] ?? '') !== '') ? (string)$r['product_sku'] : (string)($r['import_sku'] ?? '');
                    $productId = (int)($r['product_id'] ?? 0);
                  ?>
                  <?php if ($displaySku !== '' && $productId > 0): ?>
                    <a href="?page=products&action=detail&id=<?= $productId ?>" target="_blank" rel="noopener noreferrer" class="text-amber-700 hover:text-amber-900 underline">
                      <?= htmlspecialchars($displaySku) ?>
                    </a>
                  <?php else: ?>
                    <?= htmlspecialchars($displaySku) ?>
                  <?php endif; ?>
                </td>
                <td class="px-3 py-2 text-right tabular-nums"><?= (int)($r['opening_qty'] ?? 0) ?></td>
                <td class="px-3 py-2 text-right tabular-nums text-gray-700"><?php
                  $pls = $r['product_local_stock'] ?? '';
                  echo ($pls !== '' && $pls !== null) ? (string)(int)$pls : '—';
                ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($r['stock_location'] ?? '') ?></td>
                <td class="px-3 py-2"><span class="text-xs px-2 py-1 rounded <?= $stClass ?>"><?= htmlspecialchars($st) ?></span></td>
                <td class="px-3 py-2"><?= (int)($r['attempt_count'] ?? 0) ?></td>
                <td class="px-3 py-2 text-red-700 text-xs"><?= htmlspecialchars($r['error_message'] ?? '') ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($r['updated_at'] ?? '') ?></td>
                <td class="px-3 py-2">
                  <?php if ($st === 'failed'): ?>
                    <button type="button" class="js-retry-item px-2 py-1 text-xs rounded border border-amber-700 text-amber-800 hover:bg-amber-50" data-item-id="<?= (int)$r['id'] ?>">Retry</button>
                    <button type="button" class="js-delete-item ml-1 px-2 py-1 text-xs rounded border border-red-400 text-red-700 hover:bg-red-50" data-item-id="<?= (int)$r['id'] ?>">Delete</button>
                  <?php else: ?>
                    <span class="text-gray-400 text-xs">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="flex flex-wrap items-center justify-between mt-3 text-sm">
      <div>Page <?= $pageNo ?> of <?= $totalPages ?></div>
      <div class="flex gap-2">
        <?php $prev = max(1, $pageNo - 1); $next = min($totalPages, $pageNo + 1); ?>
        <a class="px-3 py-1 border rounded hover:bg-gray-50 <?= $pageNo <= 1 ? 'pointer-events-none opacity-50' : '' ?>" href="?page=products&action=bulk_import_detail&job_id=<?= $jobId ?>&status=<?= urlencode($statusFilter) ?>&limit=<?= $limitVal ?>&page_no=<?= $prev ?>">Prev</a>
        <a class="px-3 py-1 border rounded hover:bg-gray-50 <?= $pageNo >= $totalPages ? 'pointer-events-none opacity-50' : '' ?>" href="?page=products&action=bulk_import_detail&job_id=<?= $jobId ?>&status=<?= urlencode($statusFilter) ?>&limit=<?= $limitVal ?>&page_no=<?= $next ?>">Next</a>
      </div>
    </div>
  </div>
</div>

<div id="blockingOverlay" class="fixed inset-0 hidden z-50">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl border p-5">
      <div id="overlayTitle" class="font-semibold text-gray-800 mb-1">Please wait…</div>
      <div id="overlayMessage" class="text-sm text-gray-600">Processing your request.</div>
      <div id="overlayProgressWrap" class="mt-3">
        <div class="flex items-center gap-2 mb-2">
          <svg id="overlaySpinner" class="h-4 w-4 text-amber-600 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
          </svg>
          <span id="overlayProgressText" class="text-xs text-gray-500">Please wait...</span>
        </div>
        <div class="w-full h-2 rounded bg-gray-200 overflow-hidden">
          <div id="overlayProgressBar" class="h-2 w-1/3 bg-amber-600 rounded animate-pulse"></div>
        </div>
      </div>
      <textarea id="overlayDetails" class="mt-3 hidden w-full border rounded p-2 text-xs text-gray-700 h-28" readonly></textarea>
      <div id="overlayActions" class="mt-3 hidden flex gap-2 justify-end">
        <button id="overlayCopyBtn" type="button" class="px-3 py-1 text-xs rounded border hover:bg-gray-50">Copy</button>
        <button id="overlayOkBtn" type="button" class="px-3 py-1 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">Close</button>
      </div>
    </div>
  </div>
</div>

<div id="confirmModal" class="fixed inset-0 hidden z-50">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl border p-5">
      <div id="confirmModalTitle" class="font-semibold text-gray-800 mb-2">Confirm Action</div>
      <div id="confirmModalMessage" class="text-sm text-gray-600">Are you sure?</div>
      <div class="mt-4 flex justify-end gap-2">
        <button id="confirmModalCancelBtn" type="button" class="px-3 py-1.5 text-xs rounded border hover:bg-gray-50">Cancel</button>
        <button id="confirmModalOkBtn" type="button" class="px-3 py-1.5 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">Continue</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    const jobId = <?= $jobId ?>;
    const overlay = document.getElementById('blockingOverlay');
    const overlayTitle = document.getElementById('overlayTitle');
    const overlayMessage = document.getElementById('overlayMessage');
    const overlayDetails = document.getElementById('overlayDetails');
    const overlayActions = document.getElementById('overlayActions');
    const overlayCopyBtn = document.getElementById('overlayCopyBtn');
    const overlayOkBtn = document.getElementById('overlayOkBtn');
    const overlayProgressWrap = document.getElementById('overlayProgressWrap');
    const overlayProgressBar = document.getElementById('overlayProgressBar');
    const overlayProgressText = document.getElementById('overlayProgressText');
    const confirmModal = document.getElementById('confirmModal');
    const confirmModalTitle = document.getElementById('confirmModalTitle');
    const confirmModalMessage = document.getElementById('confirmModalMessage');
    const confirmModalCancelBtn = document.getElementById('confirmModalCancelBtn');
    const confirmModalOkBtn = document.getElementById('confirmModalOkBtn');
    let confirmResolver = null;

    function disableUi(disabled) {
      document.querySelectorAll('button, a, input, select, textarea').forEach(el => {
        if (el.closest('#blockingOverlay')) return;
        if (el.tagName === 'A') {
          el.style.pointerEvents = disabled ? 'none' : '';
          el.style.opacity = disabled ? '0.6' : '';
        } else {
          el.disabled = disabled;
        }
      });
    }
    function showProgress(title, message) {
      overlayTitle.textContent = title || 'Please wait…';
      overlayMessage.textContent = message || 'Processing your request.';
      overlayProgressWrap.classList.remove('hidden');
      overlayProgressText.textContent = 'Please wait...';
      overlayProgressBar.style.width = '35%';
      overlayProgressBar.classList.add('animate-pulse');
      overlayDetails.value = '';
      overlayDetails.classList.add('hidden');
      overlayActions.classList.add('hidden');
      overlay.classList.remove('hidden');
      disableUi(true);
    }

    function hideOverlay() {
      overlay.classList.add('hidden');
      disableUi(false);
    }

    function confirmWithModal(title, message) {
      return new Promise(function(resolve) {
        confirmResolver = resolve;
        confirmModalTitle.textContent = title || 'Confirm Action';
        confirmModalMessage.textContent = message || 'Are you sure?';
        confirmModal.classList.remove('hidden');
      });
    }

    function showResult(type, title, message, details) {
      overlayTitle.textContent = title || (type === 'error' ? 'Error' : 'Success');
      overlayMessage.textContent = message || '';
      overlayProgressWrap.classList.add('hidden');
      overlayDetails.value = details || '';
      if (details) {
        overlayDetails.classList.remove('hidden');
      } else {
        overlayDetails.classList.add('hidden');
      }
      overlayActions.classList.remove('hidden');
      overlay.classList.remove('hidden');
      disableUi(true);
    }

    function extractServerError(err, fallback) {
      if (!err) return fallback || 'Something went wrong.';
      if (err.rawText && String(err.rawText).trim() !== '') {
        return String(err.rawText);
      }
      if (err.message && /Unexpected token|non-JSON/i.test(err.message) && err.rawText) {
        return `${fallback || 'Invalid JSON response'}\n\n${err.rawText}`;
      }
      return [err.message || fallback || 'Something went wrong.', err.stack || ''].filter(Boolean).join('\n');
    }

    function fmtSecFromMs(ms) {
      if (ms == null || ms === '' || Number(ms) <= 0) return '—';
      return (Number(ms) / 1000).toFixed(2) + 's';
    }

    function updateTimingFromJobTiming(jt) {
      if (!jt) return;
      const tWall = document.getElementById('timingTotalWall');
      const tLast = document.getElementById('timingLastBatch');
      const tAvg = document.getElementById('timingAvgRow');
      const tEta = document.getElementById('timingEta');
      const tEx = document.getElementById('timingExtrap');
      if (tWall) tWall.textContent = fmtSecFromMs(jt.total_processing_ms);
      if (tLast) tLast.textContent = fmtSecFromMs(jt.last_batch_duration_ms);
      if (tAvg) tAvg.textContent = jt.avg_ms_per_processed_item != null ? (Number(jt.avg_ms_per_processed_item) / 1000).toFixed(2) + 's' : '—';
      if (tEta) tEta.textContent = fmtSecFromMs(jt.eta_pending_ms);
      if (tEx) tEx.textContent = fmtSecFromMs(jt.extrapolated_total_job_ms);
    }

    async function fetchJson(url, options) {
      const res = await fetch(url, options || {});
      const rawText = await res.text();
      try {
        return JSON.parse(rawText);
      } catch (e) {
        const err = new Error(`Server returned non-JSON response. HTTP ${res.status}`);
        err.rawText = rawText;
        err.httpStatus = res.status;
        throw err;
      }
    }

    /**
     * Same as fetchJson but retries when the browser gets no usable response (timeouts, "Failed to fetch")
     * or when the proxy returns 502/503/504. Backoff caps so the tab can recover without clicking Resume.
     */
    async function fetchJsonWithAutoRetry(url, options, onRetry) {
      const baseDelayMs = 2000;
      const maxDelayMs = 45000;
      let delayMs = baseDelayMs;
      let attempt = 0;
      const opts = Object.assign({ cache: 'no-store' }, options || {});
      while (true) {
        try {
          const res = await fetch(url, opts);
          const rawText = await res.text();
          const retryableHttp = res.status === 502 || res.status === 503 || res.status === 504;
          let data;
          try {
            data = JSON.parse(rawText);
          } catch (parseErr) {
            if (retryableHttp) {
              attempt += 1;
              if (typeof onRetry === 'function') {
                onRetry({ attempt, delayMs, reason: 'http', status: res.status });
              }
              await new Promise(function(r) { setTimeout(r, delayMs); });
              delayMs = Math.min(maxDelayMs, Math.floor(delayMs * 1.5));
              continue;
            }
            const err = new Error('Server returned non-JSON response. HTTP ' + res.status);
            err.rawText = rawText;
            err.httpStatus = res.status;
            throw err;
          }
          return data;
        } catch (e) {
          if (e && e.httpStatus) {
            throw e;
          }
          attempt += 1;
          if (typeof onRetry === 'function') {
            onRetry({ attempt, delayMs, reason: 'network', status: null, error: e });
          }
          await new Promise(function(r) { setTimeout(r, delayMs); });
          delayMs = Math.min(maxDelayMs, Math.floor(delayMs * 1.5));
        }
      }
    }

    function copyTextFallback(text) {
      overlayDetails.classList.remove('hidden');
      overlayDetails.value = text || '';
      overlayDetails.focus();
      overlayDetails.select();
      try {
        return document.execCommand('copy');
      } catch (e) {
        return false;
      }
    }

    overlayCopyBtn.addEventListener('click', async function() {
      const textToCopy = overlayDetails.value || overlayMessage.textContent || '';
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(textToCopy);
          overlayMessage.textContent = 'Copied to clipboard.';
          return;
        }
        const ok = copyTextFallback(textToCopy);
        overlayMessage.textContent = ok ? 'Copied to clipboard.' : 'Copy failed. Please select text and copy manually.';
      } catch (e) {
        const ok = copyTextFallback(textToCopy);
        overlayMessage.textContent = ok ? 'Copied to clipboard.' : 'Copy failed. Please select text and copy manually.';
      }
    });
    overlayOkBtn.addEventListener('click', function() {
      hideOverlay();
    });
    confirmModalCancelBtn.addEventListener('click', function() {
      confirmModal.classList.add('hidden');
      if (confirmResolver) {
        confirmResolver(false);
        confirmResolver = null;
      }
    });
    confirmModalOkBtn.addEventListener('click', function() {
      confirmModal.classList.add('hidden');
      if (confirmResolver) {
        confirmResolver(true);
        confirmResolver = null;
      }
    });

    function renderStats(stats, jobTiming) {
      document.getElementById('totalItems').textContent = stats.total_items ?? 0;
      document.getElementById('processedItems').textContent = stats.processed_items ?? 0;
      document.getElementById('successItems').textContent = stats.success_items ?? 0;
      document.getElementById('failedItems').textContent = stats.failed_items ?? 0;
      document.getElementById('pendingItems').textContent = stats.pending_items ?? 0;
      const total = stats.total_items ?? 0;
      const processed = stats.processed_items ?? 0;
      const pct = total > 0 ? Math.round((processed * 100) / total) : 0;
      document.getElementById('progressBar').style.width = pct + '%';
      if (jobTiming) updateTimingFromJobTiming(jobTiming);
      if (!overlay.classList.contains('hidden')) {
        overlayProgressBar.classList.remove('animate-pulse');
        overlayProgressBar.style.width = pct + '%';
        overlayProgressText.textContent = `${processed}/${total} processed (${pct}%)`;
      }
    }

    async function fetchStatus() {
      const data = await fetchJson(`?page=products&action=bulk_import_status&job_id=${jobId}`);
      if (!data.success) throw new Error(data.message || 'Failed to fetch status');
      renderStats(data.stats || {}, data.job_timing);
      return data;
    }

    async function retry(type, itemIds) {
      const rowRetry = Array.isArray(itemIds) && itemIds.length > 0;
      showProgress(
        rowRetry ? 'Retrying import…' : 'Updating retry queue…',
        rowRetry ? 'Re-queuing selected item(s).' : 'Marking selected records for retry.'
      );
      try {
        const body = { job_id: jobId, retry_type: type };
        if (rowRetry) body.item_ids = itemIds;
        const data = await fetchJson('?page=products&action=bulk_import_retry', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(body)
        });
        if (!data.success) {
          const detailText = [data.message || 'Retry failed', data.debug || ''].filter(Boolean).join('\n');
          showResult('error', 'Retry Failed', data.message || 'Retry failed', detailText);
          return;
        }
        const retryMsg = data.message || 'Retry queue updated.';
        let retryDetail = '';
        if (typeof data.matched === 'number' && data.matched === 0) {
          if (rowRetry) {
            retryDetail = 'This row was not updated (wrong id, wrong job, or status is not failed).';
          } else if (type === 'failed') {
            retryDetail = 'No failed rows to reset. Use filters to confirm failed lines, or retry a single row from the Actions column.';
          } else if (type === 'pending') {
            retryDetail = 'No pending or in-progress rows to reset. Use "Start / Resume Processing" to continue, or "Retry all failed" for failed rows.';
          } else {
            retryDetail = 'No matching rows for this retry.';
          }
        } else if (typeof data.matched === 'number') {
          retryDetail = `Rows reset: ${data.matched} (DB updated: ${data.affected ?? '-'}). Then click Start / Resume Processing to import again.`;
        }
        showResult('success', 'Retry Updated', retryMsg, retryDetail);
        await new Promise(r => setTimeout(r, 350));
        await fetchStatus();
        window.location.reload();
      } catch (e) {
        const details = extractServerError(e, 'Retry failed');
        showResult('error', 'Retry Failed', 'Could not process server response.', details);
      }
    }

    async function deleteFailedRows(itemIds) {
      const rowDelete = Array.isArray(itemIds) && itemIds.length > 0;
      const ok = window.confirm(rowDelete
        ? 'Delete this failed row? This cannot be undone.'
        : 'Delete all failed rows for this job? This cannot be undone.');
      if (!ok) return;
      showProgress(
        rowDelete ? 'Deleting failed row…' : 'Deleting failed rows…',
        rowDelete ? 'Removing selected failed row.' : 'Removing all failed rows for this job.'
      );
      try {
        const body = { job_id: jobId };
        if (rowDelete) body.item_ids = itemIds;
        const data = await fetchJson('?page=products&action=bulk_import_delete_failed_rows', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify(body)
        });
        if (!data.success) {
          const detailText = [data.message || 'Delete failed', data.debug || ''].filter(Boolean).join('\n');
          showResult('error', 'Delete Failed', data.message || 'Delete failed', detailText);
          return;
        }
        const deletedCount = Number(data.deleted || 0);
        const details = deletedCount > 0
          ? `Deleted ${deletedCount} failed row(s).`
          : 'No failed rows matched for deletion.';
        showResult('success', 'Delete Complete', data.message || 'Failed rows deleted.', details);
        await new Promise(r => setTimeout(r, 300));
        await fetchStatus();
        window.location.reload();
      } catch (e) {
        const details = extractServerError(e, 'Delete failed');
        showResult('error', 'Delete Failed', 'Could not process server response.', details);
      }
    }

    async function processLoop() {
      showProgress(
        'Processing import…',
        'Importing item codes in batches of 50. If the connection drops or the server times out, this page will retry automatically.'
      );
      try {
        while (true) {
          const data = await fetchJsonWithAutoRetry(
            '?page=products&action=bulk_import_process_batch',
            {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ job_id: jobId })
            },
            function(info) {
              var sec = Math.max(1, Math.round(info.delayMs / 1000));
              overlayMessage.textContent =
                'Temporary network or server timeout (attempt ' + info.attempt + '). Retrying in about ' + sec + 's… ' +
                'You can leave this tab open; no need to click Resume.';
              overlayProgressText.textContent =
                (info.reason === 'http' ? 'Gateway error ' + info.status : 'Connection lost') +
                ' — auto-retry ' + info.attempt;
            }
          );
          if (!data.success) {
            const detailText = [data.message || 'Batch failed', data.debug || ''].filter(Boolean).join('\n');
            showResult('error', 'Processing Failed', data.message || 'Batch failed', detailText);
            return;
          }
          const stats = data.stats || {};
          renderStats(stats, data.job_timing);
          overlayMessage.textContent =
            'Importing item codes in batches of 50. If the connection drops or the server times out, this page will retry automatically.';
          const total = stats.total_items ?? 0;
          const processed = stats.processed_items ?? 0;
          const pct = total > 0 ? Math.round((processed * 100) / total) : 0;
          const bt = data.batch_timing || {};
          const jt = data.job_timing || {};
          let line = `${processed}/${total} (${pct}%)`;
          if (bt.batch_duration_ms != null && bt.rows_in_batch > 0) {
            line += ` · batch ${(bt.batch_duration_ms / 1000).toFixed(2)}s`;
            if (bt.rows_per_second != null) line += ` (${bt.rows_per_second} rows/s)`;
          }
          if (jt.eta_pending_ms != null && (stats.pending_items ?? 0) > 0) {
            line += ` · ETA ~${fmtSecFromMs(jt.eta_pending_ms)}`;
          }
          overlayProgressText.textContent = line;
          if ((stats.pending_items ?? 0) <= 0) break;
          await new Promise(r => setTimeout(r, 350));
        }
        showResult('success', 'Processing Complete', 'All pending item codes are processed.');
        await new Promise(r => setTimeout(r, 350));
        window.location.reload();
      } catch (e) {
        const details = extractServerError(e, 'Processing failed');
        showResult('error', 'Processing Failed', 'Could not process server response.', details);
      }
    }

    async function reuploadLoop() {
      const ok = await confirmWithModal(
        'Confirm ReUpload',
        'ReUpload will refetch all item codes from API and update product data. Continue?'
      );
      if (!ok) return;
      showProgress(
        'ReUploading products…',
        'Refetching products from API in batches of 50.'
      );
      try {
        let lastId = 0;
        let totalRows = 0;
        let updatedRows = 0;
        let failedRows = 0;
        while (true) {
          const data = await fetchJsonWithAutoRetry(
            '?page=products&action=bulk_import_refetch_batch',
            {
              method: 'POST',
              headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ job_id: jobId, last_id: lastId })
            },
            function(info) {
              const sec = Math.max(1, Math.round(info.delayMs / 1000));
              overlayMessage.textContent =
                'Temporary network or server timeout (attempt ' + info.attempt + '). Retrying in about ' + sec + 's…';
            }
          );
          if (!data.success) {
            const detailText = [data.message || 'ReUpload failed', data.debug || ''].filter(Boolean).join('\n');
            showResult('error', 'ReUpload Failed', data.message || 'ReUpload failed', detailText);
            return;
          }
          if (data.done) break;
          lastId = Number(data.last_id || lastId);
          totalRows += Number(data.batch_size || 0);
          updatedRows += Number(data.updated_rows || 0);
          failedRows += Number(data.failed_rows || 0);
          overlayProgressText.textContent = `Processed ${totalRows} rows | Updated ${updatedRows} | Failed ${failedRows}`;
          overlayProgressBar.classList.remove('animate-pulse');
          overlayProgressBar.style.width = '65%';
          await new Promise(r => setTimeout(r, 150));
        }
        const detail = `Processed rows: ${totalRows}\nUpdated rows: ${updatedRows}\nFailed rows: ${failedRows}`;
        showResult('success', 'ReUpload Complete', 'Products were refetched from API.', detail);
        await new Promise(r => setTimeout(r, 350));
        window.location.reload();
      } catch (e) {
        const details = extractServerError(e, 'ReUpload failed');
        showResult('error', 'ReUpload Failed', 'Could not process server response.', details);
      }
    }

    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
      showProgress('Refreshing status…', 'Fetching latest job summary.');
      fetchStatus()
        .then(() => showResult('success', 'Status Refreshed', 'Latest status loaded successfully.'))
        .catch(e => {
          const details = extractServerError(e, 'Status fetch failed');
          showResult('error', 'Status Fetch Failed', 'Could not process server response.', details);
        });
    });
    document.getElementById('startProcessBtn').addEventListener('click', function() {
      processLoop();
    });
    document.getElementById('reuploadBtn').addEventListener('click', function() {
      reuploadLoop();
    });
    document.getElementById('retryFailedBtn').addEventListener('click', function() {
      retry('failed');
    });
    document.getElementById('retryPendingBtn').addEventListener('click', function() {
      retry('pending');
    });
    document.getElementById('deleteFailedBtn').addEventListener('click', function() {
      deleteFailedRows();
    });
    document.querySelectorAll('.js-retry-item').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const id = parseInt(btn.getAttribute('data-item-id') || '0', 10);
        if (!id) return;
        retry('failed', [id]);
      });
    });
    document.querySelectorAll('.js-delete-item').forEach(function(btn) {
      btn.addEventListener('click', function() {
        const id = parseInt(btn.getAttribute('data-item-id') || '0', 10);
        if (!id) return;
        deleteFailedRows([id]);
      });
    });
  })();
</script>
