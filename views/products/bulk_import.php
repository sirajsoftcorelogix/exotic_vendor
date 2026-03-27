<div class="max-w-5xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold text-gray-800">Bulk Product Import</h2>
      <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Upload a file with item codes in first column. System queues all codes and processes in batches of 50 per API call.
    </p>

    <form id="bulkImportForm" class="border rounded-lg p-4 bg-gray-50">
      <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV / XLSX</label>
      <input type="file" name="item_codes_file" id="item_codes_file" accept=".csv,.xlsx" class="block w-full text-sm mb-3" required>
      <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Upload & Create Job</button>
    </form>

    <div id="jobPanel" class="mt-6 hidden border rounded-lg p-4">
      <div class="flex items-center justify-between mb-2">
        <h3 class="font-semibold text-gray-800">Import Job: <span id="jobIdText">-</span></h3>
        <span id="jobStatusBadge" class="text-xs px-2 py-1 rounded bg-gray-100 text-gray-700">pending</span>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm mb-4">
        <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Total</div><div id="totalItems" class="font-semibold">0</div></div>
        <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Processed</div><div id="processedItems" class="font-semibold">0</div></div>
        <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Success</div><div id="successItems" class="font-semibold text-green-700">0</div></div>
        <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Failed</div><div id="failedItems" class="font-semibold text-red-700">0</div></div>
        <div class="p-2 rounded bg-gray-50"><div class="text-gray-500">Pending</div><div id="pendingItems" class="font-semibold text-amber-700">0</div></div>
      </div>

      <div class="w-full bg-gray-200 rounded-full h-2.5 mb-4">
        <div id="progressBar" class="bg-amber-600 h-2.5 rounded-full" style="width: 0%"></div>
      </div>

      <div class="flex gap-2 mb-4">
        <button id="startProcessBtn" type="button" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Start / Resume Processing</button>
        <button id="refreshStatusBtn" type="button" class="px-4 py-2 border rounded hover:bg-gray-50 text-sm">Refresh Status</button>
      </div>

      <div>
        <h4 class="font-medium text-sm text-gray-700 mb-2">Recent Failed Codes</h4>
        <div id="failedPreview" class="text-xs text-gray-600 border rounded p-3 max-h-52 overflow-y-auto bg-gray-50">No failures yet.</div>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    let currentJobId = <?= (int)($job_id ?? 0) ?>;
    let processing = false;

    const form = document.getElementById('bulkImportForm');
    const panel = document.getElementById('jobPanel');
    const startBtn = document.getElementById('startProcessBtn');
    const refreshBtn = document.getElementById('refreshStatusBtn');

    function setBadge(status) {
      const badge = document.getElementById('jobStatusBadge');
      badge.textContent = status || 'pending';
      badge.className = 'text-xs px-2 py-1 rounded ';
      if (status === 'completed') badge.className += 'bg-green-100 text-green-700';
      else if (status === 'processing') badge.className += 'bg-amber-100 text-amber-700';
      else if (status === 'failed') badge.className += 'bg-red-100 text-red-700';
      else badge.className += 'bg-gray-100 text-gray-700';
    }

    function renderStats(stats) {
      panel.classList.remove('hidden');
      document.getElementById('jobIdText').textContent = currentJobId;
      document.getElementById('totalItems').textContent = stats.total_items ?? 0;
      document.getElementById('processedItems').textContent = stats.processed_items ?? 0;
      document.getElementById('successItems').textContent = stats.success_items ?? 0;
      document.getElementById('failedItems').textContent = stats.failed_items ?? 0;
      document.getElementById('pendingItems').textContent = stats.pending_items ?? 0;
      const pct = (stats.total_items ?? 0) > 0 ? Math.round(((stats.processed_items ?? 0) * 100) / stats.total_items) : 0;
      document.getElementById('progressBar').style.width = pct + '%';
      setBadge(stats.status || 'pending');
    }

    function renderFailedPreview(list) {
      const box = document.getElementById('failedPreview');
      if (!list || !list.length) {
        box.textContent = 'No failures yet.';
        return;
      }
      box.innerHTML = list.map(x => `<div class="mb-1"><strong>${x.item_code}</strong>${x.error_message ? ' - ' + x.error_message : ''}</div>`).join('');
    }

    async function fetchStatus() {
      if (!currentJobId) return;
      const res = await fetch(`?page=products&action=bulk_import_status&job_id=${currentJobId}`);
      const data = await res.json();
      if (!data.success) throw new Error(data.message || 'Failed to fetch status');
      renderStats(data.stats || {});
      renderFailedPreview(data.failed_preview || []);
      return data;
    }

    async function processLoop() {
      if (!currentJobId) return;
      processing = true;
      startBtn.disabled = true;
      startBtn.textContent = 'Processing...';
      try {
        while (processing) {
          const res = await fetch('?page=products&action=bulk_import_process_batch', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: currentJobId })
          });
          const data = await res.json();
          if (!data.success) throw new Error(data.message || 'Batch failed');
          const stats = data.stats || {};
          renderStats(stats);
          await fetchStatus();
          if ((stats.pending_items ?? 0) <= 0 || stats.status === 'completed') {
            processing = false;
          } else {
            await new Promise(r => setTimeout(r, 400));
          }
        }
      } catch (e) {
        alert(e.message || 'Processing stopped due to error.');
      } finally {
        startBtn.disabled = false;
        startBtn.textContent = 'Start / Resume Processing';
        processing = false;
      }
    }

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      const fd = new FormData(form);
      const res = await fetch('?page=products&action=bulk_import_upload', { method: 'POST', body: fd });
      const data = await res.json();
      if (!data.success) {
        alert(data.message || 'Upload failed');
        return;
      }
      currentJobId = data.job_id;
      history.replaceState({}, '', `?page=products&action=bulk_import&job_id=${currentJobId}`);
      await fetchStatus();
    });

    startBtn.addEventListener('click', processLoop);
    refreshBtn.addEventListener('click', fetchStatus);

    if (currentJobId > 0) {
      fetchStatus().catch(() => {});
    }
  })();
</script>
