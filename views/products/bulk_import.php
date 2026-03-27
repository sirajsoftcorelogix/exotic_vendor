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
      <div class="text-xs text-gray-500 mt-2">Expected format: first column contains item codes. Max file size: 10 MB.</div>
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
                  <?php
                    $canDelete = ((int)$j['success_items'] === 0) && ((int)$j['failed_items'] === 0) && (($j['status'] ?? 'pending') !== 'processing');
                  ?>
                  <?php if ($canDelete): ?>
                    <button type="button" class="js-delete-job ml-1 px-2 py-1 text-xs rounded bg-red-600 text-white hover:bg-red-700" data-job-id="<?= (int)$j['id'] ?>">Delete</button>
                  <?php endif; ?>
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
      <div id="popupTitle" class="font-semibold text-gray-800 mb-1">Uploading file…</div>
      <div id="popupMessage" class="text-sm text-gray-600">Please wait, do not refresh the page.</div>
      <textarea id="popupDetails" class="mt-3 hidden w-full border rounded p-2 text-xs text-gray-700 h-28" readonly></textarea>
      <div id="popupActions" class="mt-3 hidden flex gap-2 justify-end">
        <button id="popupCopyBtn" type="button" class="px-3 py-1 text-xs rounded border hover:bg-gray-50">Copy</button>
        <button id="popupOkBtn" type="button" class="px-3 py-1 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">OK</button>
      </div>
    </div>
  </div>
</div>

<script>
  (function() {
    const MAX_FILE_BYTES = 10 * 1024 * 1024;
    const form = document.getElementById('bulkImportForm');
    const fileInput = document.getElementById('item_codes_file');
    const overlay = document.getElementById('blockingOverlay');
    const popupTitle = document.getElementById('popupTitle');
    const popupMessage = document.getElementById('popupMessage');
    const popupDetails = document.getElementById('popupDetails');
    const popupActions = document.getElementById('popupActions');
    const popupCopyBtn = document.getElementById('popupCopyBtn');
    const popupOkBtn = document.getElementById('popupOkBtn');

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

    function showProgress(title, message) {
      popupTitle.textContent = title || 'Please wait…';
      popupMessage.textContent = message || 'Processing your request.';
      popupDetails.value = '';
      popupDetails.classList.add('hidden');
      popupActions.classList.add('hidden');
      overlay.classList.remove('hidden');
      setDisabled(true);
    }

    function hidePopup() {
      overlay.classList.add('hidden');
      setDisabled(false);
    }

    function showResult(type, title, message, details) {
      popupTitle.textContent = title || (type === 'error' ? 'Error' : 'Success');
      popupMessage.textContent = message || '';
      popupDetails.value = details || '';
      if (details) {
        popupDetails.classList.remove('hidden');
      } else {
        popupDetails.classList.add('hidden');
      }
      popupActions.classList.remove('hidden');
      overlay.classList.remove('hidden');
      setDisabled(true);
    }

    function extractServerError(err, fallback) {
      if (!err) return fallback || 'Something went wrong.';
      if (err.message && /Unexpected token/i.test(err.message) && err.rawText) {
        return `${fallback || 'Invalid JSON response'}\n\n${err.rawText}`;
      }
      return err.message || fallback || 'Something went wrong.';
    }

    async function fetchJson(url, options) {
      const res = await fetch(url, options || {});
      const rawText = await res.text();
      try {
        return JSON.parse(rawText);
      } catch (e) {
        const err = new Error('Server returned non-JSON response.');
        err.rawText = rawText;
        throw err;
      }
    }

    popupCopyBtn.addEventListener('click', async function() {
      try {
        await navigator.clipboard.writeText(popupDetails.value || popupMessage.textContent || '');
        popupMessage.textContent = 'Copied to clipboard.';
      } catch (e) {
        popupMessage.textContent = 'Copy failed. Please select and copy manually.';
      }
    });
    popupOkBtn.addEventListener('click', function() {
      hidePopup();
    });

    form.addEventListener('submit', async function(e) {
      e.preventDefault();
      if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
        showResult('error', 'Validation Error', 'Please select a file.');
        return;
      }
      if ((fileInput.files[0].size || 0) > MAX_FILE_BYTES) {
        showResult('error', 'Validation Error', 'File size must be 10 MB or less.');
        return;
      }
      showProgress('Uploading file…', 'Uploading and validating your file.');
      const fd = new FormData(form);
      try {
        const data = await fetchJson('?page=products&action=bulk_import_upload', { method: 'POST', body: fd });
        if (!data.success) {
          const detailText = [data.message || 'Upload failed', data.debug || ''].filter(Boolean).join('\n');
          showResult('error', 'Upload Failed', data.message || 'Upload failed', detailText);
          return;
        }
        showResult('success', 'Upload Successful', `Job #${data.job_id} created successfully.`);
        await new Promise(r => setTimeout(r, 350));
        window.location.href = `?page=products&action=bulk_import_detail&job_id=${data.job_id}`;
      } catch (e2) {
        const details = extractServerError(e2, 'Upload failed');
        showResult('error', 'Upload Failed', 'Could not process server response.', details);
      }
    });

    document.querySelectorAll('.js-delete-job').forEach(function(btn) {
      btn.addEventListener('click', async function() {
        const jobId = parseInt(btn.getAttribute('data-job-id') || '0', 10);
        if (!jobId) return;
        if (!confirm('Delete this unprocessed import job? This will remove uploaded codes for this file.')) return;
        showProgress('Deleting job…', 'Removing unprocessed import job.');
        try {
          const data = await fetchJson('?page=products&action=bulk_import_delete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId })
          });
          if (!data.success) {
            const detailText = [data.message || 'Delete failed', data.debug || ''].filter(Boolean).join('\n');
            showResult('error', 'Delete Failed', data.message || 'Delete failed', detailText);
            return;
          }
          showResult('success', 'Delete Successful', data.message || 'Job deleted successfully.');
          await new Promise(r => setTimeout(r, 350));
          window.location.reload();
        } catch (e3) {
          const details = extractServerError(e3, 'Delete failed');
          showResult('error', 'Delete Failed', 'Could not process server response.', details);
        }
      });
    });
  })();
</script>
