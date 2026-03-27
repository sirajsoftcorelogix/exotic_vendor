<?php
  $jobId = (int)($job['id'] ?? 0);
  $statusFilter = $status_filter ?? 'all';
  $pageNo = (int)($page_no ?? 1);
  $limitVal = (int)($limit ?? 100);
  $totalPages = max(1, (int)($total_pages ?? 1));
  $pendingItems = max(0, (int)($job['total_items'] ?? 0) - (int)($job['processed_items'] ?? 0));
?>

<div class="max-w-7xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
      <div>
        <h2 class="text-xl font-semibold text-gray-800">Bulk Import Detail #<?= $jobId ?></h2>
        <p class="text-sm text-gray-600">
          File: <span class="font-medium"><?= htmlspecialchars($job['file_name'] ?? '') ?></span> |
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

    <div class="flex flex-wrap gap-2 mb-4">
      <button id="startProcessBtn" type="button" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Start / Resume Processing</button>
      <button id="refreshStatusBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Refresh Status</button>
      <button id="retryFailedBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Retry Failed</button>
      <button id="retryPendingBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Retry Pending</button>
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
            <th class="px-3 py-2 text-left">Item Code</th>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Attempts</th>
            <th class="px-3 py-2 text-left">Error</th>
            <th class="px-3 py-2 text-left">Updated</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows)): ?>
            <tr><td colspan="6" class="px-3 py-8 text-center text-gray-400">No records found.</td></tr>
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
                <td class="px-3 py-2 font-medium"><?= htmlspecialchars($r['item_code'] ?? '') ?></td>
                <td class="px-3 py-2"><span class="text-xs px-2 py-1 rounded <?= $stClass ?>"><?= htmlspecialchars($st) ?></span></td>
                <td class="px-3 py-2"><?= (int)($r['attempt_count'] ?? 0) ?></td>
                <td class="px-3 py-2 text-red-700 text-xs"><?= htmlspecialchars($r['error_message'] ?? '') ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($r['updated_at'] ?? '') ?></td>
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
    </div>
  </div>
</div>

<script>
  (function() {
    const jobId = <?= $jobId ?>;
    const overlay = document.getElementById('blockingOverlay');

    function disableUi(disabled) {
      document.querySelectorAll('button, a, input, select, textarea').forEach(el => {
        if (el.tagName === 'A') {
          el.style.pointerEvents = disabled ? 'none' : '';
          el.style.opacity = disabled ? '0.6' : '';
        } else {
          el.disabled = disabled;
        }
      });
    }
    function showOverlay(title, message) {
      document.getElementById('overlayTitle').textContent = title || 'Please wait…';
      document.getElementById('overlayMessage').textContent = message || 'Processing your request.';
      overlay.classList.remove('hidden');
      disableUi(true);
    }
    function hideOverlay() {
      overlay.classList.add('hidden');
      disableUi(false);
    }

    function renderStats(stats) {
      document.getElementById('totalItems').textContent = stats.total_items ?? 0;
      document.getElementById('processedItems').textContent = stats.processed_items ?? 0;
      document.getElementById('successItems').textContent = stats.success_items ?? 0;
      document.getElementById('failedItems').textContent = stats.failed_items ?? 0;
      document.getElementById('pendingItems').textContent = stats.pending_items ?? 0;
      const total = stats.total_items ?? 0;
      const processed = stats.processed_items ?? 0;
      const pct = total > 0 ? Math.round((processed * 100) / total) : 0;
      document.getElementById('progressBar').style.width = pct + '%';
    }

    async function fetchStatus() {
      const res = await fetch(`?page=products&action=bulk_import_status&job_id=${jobId}`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to fetch status');
      renderStats(data.stats || {});
      return data;
    }

    async function retry(type) {
      showOverlay('Updating retry queue…', 'Marking selected records for retry.');
      try {
        const res = await fetch('?page=products&action=bulk_import_retry', {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ job_id: jobId, retry_type: type })
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.message || 'Retry failed');
        await fetchStatus();
        window.location.reload();
      } finally {
        hideOverlay();
      }
    }

    async function processLoop() {
      showOverlay('Processing import…', 'Importing item codes in batches of 50.');
      try {
        while (true) {
          const res = await fetch('?page=products&action=bulk_import_process_batch', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ job_id: jobId })
          });
          const data = await res.json();
          if (!data.success) throw new Error(data.message || 'Batch failed');
          const stats = data.stats || {};
          renderStats(stats);
          if ((stats.pending_items ?? 0) <= 0) break;
          await new Promise(r => setTimeout(r, 350));
        }
        window.location.reload();
      } finally {
        hideOverlay();
      }
    }

    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
      fetchStatus().catch(e => alert(e.message || 'Status fetch failed'));
    });
    document.getElementById('startProcessBtn').addEventListener('click', function() {
      processLoop().catch(e => alert(e.message || 'Processing failed'));
    });
    document.getElementById('retryFailedBtn').addEventListener('click', function() {
      retry('failed').catch(e => alert(e.message || 'Retry failed'));
    });
    document.getElementById('retryPendingBtn').addEventListener('click', function() {
      retry('pending').catch(e => alert(e.message || 'Retry failed'));
    });
  })();
</script>
