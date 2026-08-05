<?php
$selectedCurrency = null;
$rateHistory = [];
?>

<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f8fafc; padding: 20px; color: #1e293b; }
    .container { max-width: 1100px; margin: 0 auto; }
    .header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; margin-top: 10px; flex-wrap: wrap; gap: 10px; }
    h1 { color: #0f172a; font-size: 24px; font-weight: 700; }
    .btn-group { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-add { background: #10b981; color: white; padding: 9px 16px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; border: none; cursor: pointer; }
    .btn-add:hover { background: #059669; }
    .btn-pdf { background: #3b82f6; color: white; padding: 9px 16px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; border: none; cursor: pointer; }
    .btn-pdf:hover { background: #2563eb; }
    .btn-icegate { background: #8b5cf6; color: white; padding: 9px 16px; text-decoration: none; border-radius: 6px; font-weight: 600; font-size: 14px; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; border: none; cursor: pointer; }
    .btn-icegate:hover { background: #7c3aed; }
    
    .success-alert { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
    .error-alert { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; }
    
    table.data-table { width: 100%; background: white; border-collapse: collapse; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
    table.data-table th { background: #f8fafc; padding: 14px 16px; text-align: left; font-weight: 600; color: #475569; border-bottom: 1px solid #e2e8f0; font-size: 13px; text-transform: uppercase; letter-spacing: 0.05em; }
    table.data-table td { padding: 14px 16px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
    table.data-table tr:hover { background: #f8fafc; }
    
    .btn { padding: 6px 12px; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 500; text-decoration: none; display: inline-block; }
    .btn-edit { background: #e0f2fe; color: #0369a1; }
    .btn-edit:hover { background: #bae6fd; }
    .btn-delete { background: #fee2e2; color: #991b1b; }
    .btn-delete:hover { background: #fca5a5; }
    .btn-history { background: #fef3c7; color: #92400e; }
    .btn-history:hover { background: #fde68a; }
    
    .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); }
    .modal.active { display: flex; align-items: center; justify-content: center; }
    .modal-content { background: white; padding: 24px; border-radius: 12px; max-width: 800px; width: 92%; max-height: 85vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px; }
    .modal-header h2 { font-size: 18px; color: #0f172a; font-weight: 700; }
    .close-modal { background: none; border: none; font-size: 24px; cursor: pointer; color: #64748b; padding: 0 4px; }
    .close-modal:hover { color: #0f172a; }
    
    .file-drop-area { border: 2px dashed #cbd5e1; border-radius: 8px; padding: 24px; text-align: center; background: #f8fafc; cursor: pointer; transition: border-color 0.2s; margin-bottom: 16px; }
    .file-drop-area:hover { border-color: #3b82f6; background: #eff6ff; }
    
    .meta-badge { display: inline-flex; gap: 12px; background: #f1f5f9; padding: 8px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #334155; margin-bottom: 16px; border: 1px solid #cbd5e1; }
    
    .preview-table { width: 100%; border-collapse: collapse; margin-top: 12px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; font-size: 13px; }
    .preview-table th, .preview-table td { padding: 10px 12px; text-align: left; border-bottom: 1px solid #f1f5f9; }
    .preview-table th { background: #f8fafc; font-weight: 600; color: #475569; }
    .rate-changed { font-weight: 700; color: #2563eb; background: #eff6ff; padding: 2px 6px; border-radius: 4px; }
    .rate-new { font-weight: 700; color: #059669; background: #ecfdf5; padding: 2px 6px; border-radius: 4px; }
    
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 14px; margin-bottom: 12px; }
    .form-control:focus { border-color: #3b82f6; outline: none; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
    textarea.form-control { min-height: 120px; font-family: monospace; font-size: 13px; }

    .modal-footer { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 16px; }
    .btn-submit { background: #2563eb; color: white; padding: 9px 18px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
    .btn-submit:hover { background: #1d4ed8; }
    .btn-cancel { background: #f1f5f9; color: #475569; padding: 9px 18px; border-radius: 6px; font-weight: 600; border: none; cursor: pointer; }
    .btn-cancel:hover { background: #e2e8f0; }
    
    .empty { text-align: center; color: #64748b; padding: 40px; background: white; border-radius: 8px; border: 1px solid #e2e8f0; }
</style>

<div class="container">
    <div class="header-row">
        <h1>Currency Management</h1>
        <div class="btn-group">
            <button class="btn-pdf" onclick="openPdfModal()">📄 Upload CBIC PDF (Method 3)</button>
            <button class="btn-icegate" onclick="openIcegateModal()">⚡ Sync / Paste ICEGATE Table (Method 1)</button>
            <a href="index.php?page=currency&action=addRecord" class="btn-add">+ Add New Currency</a>
        </div>
    </div>

    <div id="noticeBox"></div>
    
    <?php if (isset($_GET['success'])): ?>
        <div class="success-alert">Operation completed successfully!</div>
    <?php endif; ?>
    
    <?php if (empty($currencies)): ?>
        <div class="empty">No currencies found. <a href="index.php?page=currency&action=addRecord">Add one now</a></div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Name</th>
                    <th>Unit</th>
                    <th>Import Rate (₹)</th>
                    <th>Export Rate (₹)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($currencies as $curr): ?>
                    <tr>
                        <td><strong><?php echo strtoupper($curr['currency_code']); ?></strong></td>
                        <td><?php echo htmlspecialchars($curr['currency_name']); ?></td>
                        <td><?php echo htmlspecialchars($curr['currency_unit']); ?></td>
                        <td><strong><?php echo number_format($curr['rate_import'], 6); ?></strong></td>
                        <td><strong><?php echo number_format($curr['rate_export'], 6); ?></strong></td>
                        <td>
                            <a href="index.php?page=currency&action=addRecord&id=<?php echo $curr['id']; ?>" class="btn btn-edit">Edit</a>
                            <button class="btn btn-history" onclick="openHistory(<?php echo htmlspecialchars(json_encode($curr)); ?>)">History</button>
                            <a href="index.php?page=currency&action=deleteRecord&id=<?php echo $curr['id']; ?>" class="btn btn-delete" onclick="confirmDeactivate(event, '<?php echo $curr['id']; ?>')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- PDF Upload Modal (Method 3) -->
<div id="pdfModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>📄 Upload CBIC Exchange Rate PDF (Method 3)</h2>
            <button class="close-modal" onclick="closeModal('pdfModal')">&times;</button>
        </div>
        
        <form id="pdfUploadForm" onsubmit="handlePdfParse(event)">
            <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">Option A: Upload PDF File</label>
            <div class="file-drop-area" onclick="document.getElementById('pdfFileInput').click()">
                <div style="font-size: 28px; margin-bottom: 8px;">📂</div>
                <div style="font-weight: 600; color: #334155;">Click to select CBIC Exchange Rate PDF</div>
                <div style="font-size: 12px; color: #64748b; margin-top: 4px;">Upload official notification PDF from CBIC / ICEGATE</div>
                <input type="file" id="pdfFileInput" accept=".pdf" style="display: none;" onchange="updateFileName(this)">
                <div id="pdfFileName" style="margin-top: 10px; font-weight: 600; color: #2563eb;"></div>
            </div>

            <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">Option B: Local File Path (or Sample Download)</label>
            <input type="text" id="pdfFilePathInput" class="form-control" placeholder="e.g. /workspace/exchange_rate.pdf or c:\Users\Admin\Downloads\exchange_rate.pdf" value="">

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-submit" id="parsePdfBtn">🔍 Parse PDF & Preview Rates</button>
            </div>
        </form>

        <div id="pdfPreviewSection" style="display: none; margin-top: 20px;">
            <div id="pdfMetaBadge" class="meta-badge"></div>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 10px;">Review rates parsed from PDF. Uncheck any currency you do not wish to update.</p>
            <div style="max-height: 350px; overflow-y: auto;">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllPdf" checked onclick="toggleAllCheckboxes('pdfModal', this.checked)"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Import Rate</th>
                            <th>Export Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="pdfPreviewBody"></tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('pdfModal')">Cancel</button>
                <button class="btn-submit" id="applyPdfRatesBtn" onclick="applyParsedRates('pdfModal', 'PDF')">✅ Apply Selected Rates to Database</button>
            </div>
        </div>
    </div>
</div>

<!-- ICEGATE Table Sync / Paste Modal (Method 1) -->
<div id="icegateModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⚡ Sync / Paste ICEGATE Table (Method 1)</h2>
            <button class="close-modal" onclick="closeModal('icegateModal')">&times;</button>
        </div>

        <p style="font-size: 13px; color: #475569; margin-bottom: 14px; line-height: 1.5;">
            Visit official <a href="https://foservices.icegate.gov.in/#/services/viewExchangeRate" target="_blank" style="color: #2563eb; font-weight: 600;">ICEGATE View Exchange Rate Portal ↗</a>, complete the CAPTCHA, copy the table, and paste it below for instant parsing and 1-click database update.
        </p>

        <form id="pasteTableForm" onsubmit="handlePasteParse(event)">
            <label style="font-size: 13px; font-weight: 600; margin-bottom: 6px; display: block;">Paste Copied Table Text from ICEGATE / Excel:</label>
            <textarea id="rawTableText" class="form-control" placeholder="Paste ICEGATE currency exchange rate table here...
Example:
USD US Dollar 1.0 97.20 95.45
EUR EURO 1.0 112.35 108.60
JPY Japanese Yen 100.0 60.35 58.50"></textarea>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-submit" id="parsePasteBtn">⚡ Parse Pasted Table</button>
            </div>
        </form>

        <div id="pastePreviewSection" style="display: none; margin-top: 20px;">
            <div id="pasteMetaBadge" class="meta-badge"></div>
            <div style="max-height: 350px; overflow-y: auto;">
                <table class="preview-table">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAllPaste" checked onclick="toggleAllCheckboxes('icegateModal', this.checked)"></th>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Unit</th>
                            <th>Import Rate</th>
                            <th>Export Rate</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="pastePreviewBody"></tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button class="btn-cancel" onclick="closeModal('icegateModal')">Cancel</button>
                <button class="btn-submit" id="applyPasteRatesBtn" onclick="applyParsedRates('icegateModal', 'ICEGATE_PASTE')">✅ Apply Selected Rates to Database</button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="historyTitle">Rate History</h2>
            <button class="close-modal" onclick="closeModal('historyModal')">&times;</button>
        </div>
        <table class="preview-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Import Rate (₹)</th>
                    <th>Export Rate (₹)</th>
                </tr>
            </thead>
            <tbody id="historyBody"></tbody>
        </table>
    </div>
</div>

<!-- Message / Notice Modal (Adhering to No JS alert rule) -->
<div id="noticeModal" class="modal">
    <div class="modal-content" style="max-width: 450px;">
        <div class="modal-header">
            <h2 id="noticeTitle">Notice</h2>
            <button class="close-modal" onclick="closeModal('noticeModal')">&times;</button>
        </div>
        <div id="noticeMessage" style="font-size: 14px; color: #334155; line-height: 1.5; margin-bottom: 20px;"></div>
        <div style="display: flex; justify-content: flex-end; gap: 10px;">
            <button class="btn-submit" id="noticeConfirmBtn" onclick="closeModal('noticeModal')">OK</button>
        </div>
    </div>
</div>

<script>
    let currentParsedData = {
        pdfModal: null,
        icegateModal: null
    };

    function showNotice(title, message) {
        document.getElementById('noticeTitle').textContent = title;
        document.getElementById('noticeMessage').innerHTML = message;
        document.getElementById('noticeConfirmBtn').style.display = 'inline-block';
        document.getElementById('noticeModal').classList.add('active');
    }

    function confirmDeactivate(event, id) {
        event.preventDefault();
        document.getElementById('noticeTitle').textContent = 'Confirm Deactivation';
        document.getElementById('noticeMessage').textContent = 'Are you sure you want to deactivate this currency?';
        
        const btn = document.getElementById('noticeConfirmBtn');
        btn.textContent = 'Yes, Deactivate';
        btn.onclick = function() {
            window.location.href = 'index.php?page=currency&action=deleteRecord&id=' + id;
        };
        
        document.getElementById('noticeModal').classList.add('active');
    }

    function openPdfModal() {
        document.getElementById('pdfModal').classList.add('active');
    }

    function openIcegateModal() {
        document.getElementById('icegateModal').classList.add('active');
    }

    function closeModal(modalId) {
        document.getElementById(modalId).classList.remove('active');
    }

    function updateFileName(input) {
        if (input.files && input.files[0]) {
            document.getElementById('pdfFileName').textContent = 'Selected File: ' + input.files[0].name;
            document.getElementById('pdfFilePathInput').value = '';
        }
    }

    function handlePdfParse(event) {
        event.preventDefault();
        const fileInput = document.getElementById('pdfFileInput');
        const pathInput = document.getElementById('pdfFilePathInput').value.trim();
        
        if (!fileInput.files.length && !pathInput) {
            showNotice('Validation Error', 'Please select a PDF file to upload or specify a local file path.');
            return;
        }

        const formData = new FormData();
        if (fileInput.files.length > 0) {
            formData.append('exchange_rate_pdf', fileInput.files[0]);
        } else {
            formData.append('file_path', pathInput);
        }

        const btn = document.getElementById('parsePdfBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Parsing PDF...';

        fetch('index.php?page=currency&action=uploadPdfPreview', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '🔍 Parse PDF & Preview Rates';

            if (!data.success) {
                showNotice('Parse Failed', data.message || 'Could not parse currency rates from PDF.');
                return;
            }

            currentParsedData.pdfModal = data;
            renderPreviewTable('pdfModal', data);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = '🔍 Parse PDF & Preview Rates';
            showNotice('Error', 'Server error while parsing PDF: ' + err.message);
        });
    }

    function handlePasteParse(event) {
        event.preventDefault();
        const rawText = document.getElementById('rawTableText').value.trim();
        
        if (!rawText) {
            showNotice('Validation Error', 'Please paste the ICEGATE table text first.');
            return;
        }

        const formData = new FormData();
        formData.append('raw_text', rawText);

        const btn = document.getElementById('parsePasteBtn');
        btn.disabled = true;
        btn.textContent = '⏳ Parsing Pasted Table...';

        fetch('index.php?page=currency&action=pasteTablePreview', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            btn.textContent = '⚡ Parse Pasted Table';

            if (!data.success) {
                showNotice('Parse Failed', data.message || 'Could not parse rates from pasted text.');
                return;
            }

            currentParsedData.icegateModal = data;
            renderPreviewTable('icegateModal', data);
        })
        .catch(err => {
            btn.disabled = false;
            btn.textContent = '⚡ Parse Pasted Table';
            showNotice('Error', 'Server error while parsing table: ' + err.message);
        });
    }

    function renderPreviewTable(modalId, data) {
        const isPdf = modalId === 'pdfModal';
        const section = document.getElementById(isPdf ? 'pdfPreviewSection' : 'pastePreviewSection');
        const metaBadge = document.getElementById(isPdf ? 'pdfMetaBadge' : 'pasteMetaBadge');
        const tbody = document.getElementById(isPdf ? 'pdfPreviewBody' : 'pastePreviewBody');

        metaBadge.innerHTML = `
            <span><strong>Notification:</strong> ${data.notification_no || 'N/A'}</span>
            <span><strong>Effective Date:</strong> ${data.effective_date || 'Today'}</span>
            <span><strong>Found:</strong> ${data.rates.length} currencies</span>
        `;

        tbody.innerHTML = '';
        data.rates.forEach((item, idx) => {
            const importChange = item.import_changed ? `<span class="rate-changed">₹${item.rate_import}</span> <small>(was ₹${item.current_import_rate})</small>` : `₹${item.rate_import}`;
            const exportChange = item.export_changed ? `<span class="rate-changed">₹${item.rate_export}</span> <small>(was ₹${item.current_export_rate})</small>` : `₹${item.rate_export}`;
            const statusBadge = !item.exists_in_db ? `<span class="rate-new">NEW</span>` : (item.import_changed || item.export_changed ? `<span style="color:#2563eb; font-weight:600;">UPDATED</span>` : `<span style="color:#64748b;">SAME</span>`);

            tbody.innerHTML += `
                <tr>
                    <td><input type="checkbox" class="rate-checkbox-${modalId}" data-index="${idx}" checked></td>
                    <td><strong>${item.currency_code}</strong></td>
                    <td>${item.currency_name}</td>
                    <td>${item.currency_unit}</td>
                    <td>${importChange}</td>
                    <td>${exportChange}</td>
                    <td>${statusBadge}</td>
                </tr>
            `;
        });

        section.style.display = 'block';
    }

    function toggleAllCheckboxes(modalId, isChecked) {
        document.querySelectorAll(`.rate-checkbox-${modalId}`).forEach(cb => cb.checked = isChecked);
    }

    function applyParsedRates(modalId, defaultSource) {
        const data = currentParsedData[modalId];
        if (!data || !data.rates) return;

        const selectedRates = [];
        document.querySelectorAll(`.rate-checkbox-${modalId}:checked`).forEach(cb => {
            const idx = parseInt(cb.getAttribute('data-index'));
            if (data.rates[idx]) {
                selectedRates.push(data.rates[idx]);
            }
        });

        if (selectedRates.length === 0) {
            showNotice('Selection Required', 'Please select at least one currency rate to apply.');
            return;
        }

        const payload = {
            rates: selectedRates,
            effective_date: data.effective_date || '',
            notification_no: data.notification_no || '',
            source: defaultSource
        };

        fetch('index.php?page=currency&action=applyBulkRates', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                closeModal(modalId);
                window.location.href = 'index.php?page=currency&action=list&success=1';
            } else {
                showNotice('Update Failed', res.message || 'Failed to update database.');
            }
        })
        .catch(err => {
            showNotice('Error', 'Error applying rates: ' + err.message);
        });
    }

    function openHistory(currency) {
        document.getElementById('historyTitle').textContent = currency.currency_code + ' - Rate History';
        
        fetch('index.php?page=currency&action=getRateHistory&code=' + currency.currency_code)
            .then(response => response.json())
            .then(data => {
                const tbody = document.getElementById('historyBody');
                tbody.innerHTML = '';
                
                if (data.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;">No history found</td></tr>';
                } else {
                    data.forEach(record => {
                        const row = `<tr>
                            <td>${record.rate_date}</td>
                            <td>₹${parseFloat(record.rate_import).toFixed(6)}</td>
                            <td>₹${parseFloat(record.rate_export).toFixed(6)}</td>
                        </tr>`;
                        tbody.innerHTML += row;
                    });
                }
                
                document.getElementById('historyModal').classList.add('active');
            });
    }

    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.classList.remove('active');
        }
    }
</script>
