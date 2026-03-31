<div class="max-w-7xl mx-auto p-6">
  <div class="bg-white border rounded-xl shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="text-xl font-semibold text-gray-800">Bulk Product Import</h2>
      <a href="?page=products&action=list" class="px-3 py-2 text-sm rounded border border-gray-300 hover:bg-gray-50">Back to Products</a>
    </div>

    <p class="text-sm text-gray-600 mb-4">
      Level 1: Upload Excel/CSV and track all import jobs. Click any row to view item-code level details.
    </p>

    <form id="bulkImportForm" class="border rounded-lg p-4 bg-gray-50 mb-6" enctype="multipart/form-data" method="post">
      <div class="grid gap-4 md:grid-cols-2 mb-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse <span class="text-red-600">*</span></label>
          <select name="warehouse_id" id="bulk_import_warehouse_id" class="block w-full max-w-lg border rounded px-3 py-2 text-sm" required>
            <option value="">— Select warehouse —</option>
            <?php foreach ($warehouses ?? [] as $w): ?>
              <option value="<?= (int)($w['id'] ?? 0) ?>"><?= htmlspecialchars($w['address_title'] ?? '') ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Upload CSV / XLSX <span class="text-red-600">*</span></label>
          <input type="file" name="item_codes_file" id="item_codes_file" accept=".csv,.xlsx" class="block w-full text-sm max-w-lg border rounded px-2 py-1.5 bg-white" required>
        </div>
      </div>
      <div class="flex flex-wrap gap-3 items-center">
        <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 text-sm font-semibold">Upload & Create Job</button>
      </div>
      <div class="text-xs text-gray-500 mt-2">
        Expected columns: <strong>A</strong> = product code, <strong>B</strong> = SKU (leave blank to use product code), <strong>C</strong> = quantity, <strong>D</strong> = location.
        Older files with only 3 columns (code, qty, location) still work. Opening stock uses the selected warehouse when import succeeds (qty &gt; 0). Max file size: 10 MB.
        <a href="?page=products&amp;action=bulk_import_sample_csv" class="ml-1 text-amber-700 hover:text-amber-900 underline font-medium">Download sample CSV</a>
      </div>
    </form>

    <div class="overflow-x-auto rounded-lg border">
      <table class="min-w-full text-sm">
        <thead class="bg-gray-50 text-gray-600">
          <tr>
            <th class="px-3 py-2 text-left">Job ID</th>
            <th class="px-3 py-2 text-left">File</th>
            <th class="px-3 py-2 text-left">Warehouse</th>
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
            <tr><td colspan="11" class="px-3 py-8 text-center text-gray-400">No import jobs found.</td></tr>
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
                <td class="px-3 py-2"><?= htmlspecialchars($j['warehouse_name'] ?? ('#' . (int)($j['warehouse_id'] ?? 0))) ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($j['created_by_name'] ?? ('User #' . (int)$j['created_by'])) ?></td>
                <td class="px-3 py-2"><span class="text-xs px-2 py-1 rounded <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span></td>
                <td class="px-3 py-2"><?= (int)$j['total_items'] ?></td>
                <td class="px-3 py-2 text-green-700 font-semibold"><?= (int)$j['success_items'] ?></td>
                <td class="px-3 py-2 text-red-700 font-semibold"><?= (int)$j['failed_items'] ?></td>
                <td class="px-3 py-2 text-amber-700 font-semibold"><?= $pending ?></td>
                <td class="px-3 py-2"><?= htmlspecialchars($j['updated_at'] ?? '') ?></td>
                <td class="px-3 py-2">
                  <a href="?page=products&action=bulk_import_detail&job_id=<?= (int)$j['id'] ?>" class="px-2 py-1 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">View Details</a>
                  <?php if ((int)($j['failed_items'] ?? 0) > 0): ?>
                    <button type="button" class="js-retry-failed-job ml-1 px-2 py-1 text-xs rounded border border-amber-700 text-amber-800 hover:bg-amber-50" data-job-id="<?= (int)$j['id'] ?>" title="Move all failed rows back to pending">Retry failed</button>
                  <?php endif; ?>
                  <?php
                    $canRevert = (($j['status'] ?? 'pending') !== 'processing') && (((int)($j['success_items'] ?? 0) > 0) || ((int)($j['processed_items'] ?? 0) > 0));
                  ?>
                  <?php if ($canRevert): ?>
                    <button type="button" class="js-revert-job ml-1 px-2 py-1 text-xs rounded border border-red-700 text-red-800 hover:bg-red-50" data-job-id="<?= (int)$j['id'] ?>" title="Deletes opening stock movements created by this import and removes the uploaded file">Revert</button>
                  <?php endif; ?>
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
      <div id="popupProgressWrap" class="mt-3">
        <div class="flex items-center gap-2 mb-2">
          <svg class="h-4 w-4 text-amber-600 animate-spin" viewBox="0 0 24 24" fill="none">
            <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity="0.25" stroke-width="3"></circle>
            <path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
          </svg>
          <span class="text-xs text-gray-500">Please wait...</span>
        </div>
        <div class="w-full h-2 rounded bg-gray-200 overflow-hidden">
          <div id="popupProgressBar" class="h-2 w-1/3 bg-amber-600 rounded animate-pulse"></div>
        </div>
      </div>
      <textarea id="popupDetails" class="mt-3 hidden w-full border rounded p-2 text-xs text-gray-700 h-28" readonly></textarea>
      <div id="popupActions" class="mt-3 hidden flex gap-2 justify-end">
        <button id="popupCopyBtn" type="button" class="px-3 py-1 text-xs rounded border hover:bg-gray-50">Copy</button>
        <button id="popupOkBtn" type="button" class="px-3 py-1 text-xs rounded bg-amber-600 text-white hover:bg-amber-700">Close</button>
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
    const popupProgressWrap = document.getElementById('popupProgressWrap');
    const popupProgressBar = document.getElementById('popupProgressBar');

    function setDisabled(disabled) {
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
      popupTitle.textContent = title || 'Please wait…';
      popupMessage.textContent = message || 'Processing your request.';
      popupProgressWrap.classList.remove('hidden');
      popupProgressBar.style.width = '35%';
      popupProgressBar.classList.add('animate-pulse');
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
      popupProgressWrap.classList.add('hidden');
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
      if (err.rawText && String(err.rawText).trim() !== '') {
        return String(err.rawText);
      }
      if (err.message && /Unexpected token|non-JSON/i.test(err.message) && err.rawText) {
        return `${fallback || 'Invalid JSON response'}\n\n${err.rawText}`;
      }
      return [err.message || fallback || 'Something went wrong.', err.stack || ''].filter(Boolean).join('\n');
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

    function copyTextFallback(text) {
      popupDetails.classList.remove('hidden');
      popupDetails.value = text || '';
      popupDetails.focus();
      popupDetails.select();
      try {
        return document.execCommand('copy');
      } catch (e) {
        return false;
      }
    }

    popupCopyBtn.addEventListener('click', async function() {
      const textToCopy = popupDetails.value || popupMessage.textContent || '';
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(textToCopy);
          popupMessage.textContent = 'Copied to clipboard.';
          return;
        }
        const ok = copyTextFallback(textToCopy);
        popupMessage.textContent = ok ? 'Copied to clipboard.' : 'Copy failed. Please select text and copy manually.';
      } catch (e) {
        const ok = copyTextFallback(textToCopy);
        popupMessage.textContent = ok ? 'Copied to clipboard.' : 'Copy failed. Please select text and copy manually.';
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
      // Build FormData before showProgress(): setDisabled() marks inputs disabled, and
      // disabled fields are omitted from new FormData(form), so the file would not upload.
      const wh = document.getElementById('bulk_import_warehouse_id');
      const wid = wh ? wh.value : '';
      if (!wid) {
        showResult('error', 'Validation Error', 'Please select a warehouse.');
        return;
      }
      const fd = new FormData();
      const file = fileInput.files[0];
      fd.append('item_codes_file', file, file.name);
      fd.append('warehouse_id', wid);
      showProgress('Uploading file…', 'Uploading and validating your file.');
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

    document.querySelectorAll('.js-retry-failed-job').forEach(function(btn) {
      btn.addEventListener('click', async function() {
        const jobId = parseInt(btn.getAttribute('data-job-id') || '0', 10);
        if (!jobId) return;
        if (!confirm('Reset all failed rows for this job to pending so they can be imported again?')) return;
        showProgress('Retrying failed imports…', 'Moving failed rows back to the queue.');
        try {
          const data = await fetchJson('?page=products&action=bulk_import_retry', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId, retry_type: 'failed' })
          });
          if (!data.success) {
            const detailText = [data.message || 'Retry failed', data.debug || ''].filter(Boolean).join('\n');
            showResult('error', 'Retry Failed', data.message || 'Retry failed', detailText);
            return;
          }
          const detail = (typeof data.matched === 'number')
            ? `Failed rows reset: ${data.matched}. Open job details and use Start / Resume Processing to run the import.`
            : '';
          showResult('success', 'Failed rows queued', data.message || 'Retry queue updated.', detail);
          await new Promise(r => setTimeout(r, 400));
          window.location.reload();
        } catch (e4) {
          const details = extractServerError(e4, 'Retry failed');
          showResult('error', 'Retry Failed', 'Could not process server response.', details);
        }
      });
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

    document.querySelectorAll('.js-revert-job').forEach(function(btn) {
      btn.addEventListener('click', async function() {
        const jobId = parseInt(btn.getAttribute('data-job-id') || '0', 10);
        if (!jobId) return;
        if (!confirm('Revert this import?\n\nThis will:\n- Delete opening stock movements created by this job\n- Recalculate local stock for affected products\n- Delete the job and its rows\n- Delete the uploaded file (if stored)\n\nContinue?')) return;
        showProgress('Reverting import…', 'Deleting movements and cleaning up.');
        try {
          const data = await fetchJson('?page=products&action=bulk_import_revert', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ job_id: jobId })
          });
          if (!data.success) {
            const detailText = [data.message || 'Revert failed', data.debug || ''].filter(Boolean).join('\n');
            showResult('error', 'Revert Failed', data.message || 'Revert failed', detailText);
            return;
          }
          const detail = [
            typeof data.deleted_movements === 'number' ? `Deleted movements: ${data.deleted_movements}` : '',
            typeof data.recalculated_products === 'number' ? `Recalculated products: ${data.recalculated_products}` : '',
            typeof data.deleted_products === 'number' ? `Deleted products: ${data.deleted_products}` : '',
            Array.isArray(data.failed_product_deletes) && data.failed_product_deletes.length ? `Products not deleted (in use?): ${data.failed_product_deletes.join(', ')}` : '',
            (data.file_deleted === true) ? 'Uploaded file deleted: yes' : (data.file_deleted === false ? 'Uploaded file deleted: no (not stored or already missing)' : '')
          ].filter(Boolean).join('\n');
          showResult('success', 'Import Reverted', data.message || 'Import reverted.', detail);
          await new Promise(r => setTimeout(r, 450));
          window.location.reload();
        } catch (e5) {
          const details = extractServerError(e5, 'Revert failed');
          showResult('error', 'Revert Failed', 'Could not process server response.', details);
        }
      });
    });
  })();
</script>
