<div class="max-w-7xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold text-gray-800">Bulk Product Import</h2>
      <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Level 1: Upload Excel/CSV and track all import jobs. Click any row to view item-code level details.
    </p>

    <form id="bulkImportForm" class="border rounded-lg p-4 bg-gray-50 mb-6">
      <label class="block text-sm font-medium text-gray-700 mb-2">Upload CSV / XLSX</label>
      <div class="flex flex-wrap gap-3 items-center">
        <input type="file" name="item_codes_file" id="item_codes_file" accept=".csv,.xlsx" class="block w-full text-sm max-w-lg" required>
        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Upload & Create Job</button>
      </div>
      <div class="text-xs text-gray-500 mt-2">Expected format: first column contains item codes.</div>
    </form>

    <div class="overflow-x-auto rounded-lg border">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-left">Job ID</th>
            <th class="px-3 py-2 text-left">File</th>
            <th class="px-3 py-2 text-left">Imported By</th>
            <th class="px-3 py-2 text-left">Status</th>
            <th class="px-3 py-2 text-left">Total</th>
            <th class="px-3 py-2 text-left">Imported</th>
            <th class="px-3 py-2 text-left">Failed</th>
            <th class="px-3 py-2 text-left">Pending</th>
            <th class="px-3 py-2 text-left">Updated</th>
            <th class="px-3 py-2 text-left">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($jobs)): ?>
            <tr><td colspan="10" class="px-3 py-8 text-center text-gray-400">No import jobs found.</td></tr>
          <?php else: ?>
            <?php foreach ($jobs as $j): ?>
              <?php
                $pending = max(0, (int)$j['total_items'] - (int)$j['processed_items']);
                $status = $j['status'] ?? 'pending';
                $statusClass = 'bg-gray-100 text-gray-700';
                if ($status === 'processing') $statusClass = 'bg-amber-100 text-amber-700';
                if ($status === 'completed') $statusClass = 'bg-green-100 text-green-700';
                if ($status === 'failed') $statusClass = 'bg-red-100 text-red-700';
              ?>
              <tr class="border-t hover:bg-gray-50">
                <td class="px-3 py-2 font-semibold"><?= (int)$j['id'] ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($j['file_name'] ?? '') ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($j['created_by_name'] ?? ('User #' . (int)$j['created_by'])) ?></td>
                <td class="px-3 py-2"><span class="text-xs px-2 py-1 rounded <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></td>
                <td class="px-3 py-2"><?= (int)$j['total_items'] ?></td>
                <td class="px-3 py-2 text-green-700 font-semibold"><?= (int)$j['success_items'] ?></td>
                <td class="px-3 py-2 text-red-700 font-semibold"><?= (int)$j['failed_items'] ?></td>
                <td class="px-3 py-2 text-amber-700 font-semibold"><?= $pending ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($j['updated_at'] ?? '') ?></td>
                <td class="px-3 py-2">
                  <a href="?page=products&action=bulk_import_detail&job_id=<?= (int)$j['id'] ?>" class="px-2 py-1 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">View Details</a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="blockingOverlay" class="fixed inset-0 hidden z-50">
  <div class="absolute inset-0 bg-black/40"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-xl bg-white shadow-2xl border p-5">
      <div class="font-semibold text-gray-800 mb-1">Uploading file…</div>
      <div class="text-sm text-gray-600">Please wait, do not refresh the page.</div>
    </div>
  </div>
</div>

<script>
  (function() {
    const form = document.getElementById('bulkImportForm');
    const fileInput = document.getElementById('item_codes_file');
    const overlay = document.getElementById('blockingOverlay');

    function setDisabled(disabled) {
      document.querySelectorAll('button, a, input, select, textarea').forEach(el => {
        if (el.tagName === 'A') {
          el.style.pointerEvents = disabled ? 'none' : '';
          el.style.opacity = disabled ? '0.6' : '';
        } else {
          el.disabled = disabled;
        }
      });
    }

    function showOverlay() {
      overlay.classList.remove('hidden');
      setDisabled(true);
    }
    function hideOverlay() {
      overlay.classList.add('hidden');
      setDisabled(false);
    }

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        alert('Please select a file.');
        return;
      }
      showOverlay();
      const fd = new FormData(form);
      try {
        const res = await fetch('?page=products&action=bulk_import_upload', { method: 'POST', body: fd });
        const data = await res.json();
        if (!data.success) {
          alert(data.message || 'Upload failed');
          return;
        }
        window.location.href = `?page=products&action=bulk_import_detail&job_id=${data.job_id}`;
      } catch (e2) {
        alert(e2.message || 'Upload failed');
      } finally {
        hideOverlay();
      }
    });
  })();
</script>
