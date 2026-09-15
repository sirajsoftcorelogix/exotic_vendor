<?php
// views/purchase_orders/import_csv.php
$result = $result ?? null;
?>

<div class="container mx-auto px-4 py-6 max-w-6xl">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-file-import text-amber-600"></i>
                Import Purchase Orders from Excel / CSV
            </h1>
            <p class="text-sm text-gray-600 mt-1">
                Migrate existing purchase orders from your old software into the Vendor Portal.
            </p>
        </div>
        <div class="flex items-center gap-3">
            <a href="<?php echo base_url('?page=purchase_orders&action=download_import_sample'); ?>" 
               class="bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 transition">
                <i class="fa-solid fa-file-csv text-base"></i>
                Download Sample Template (.CSV)
            </a>
            <a href="<?php echo base_url('?page=purchase_orders&action=stock_purchase'); ?>" 
               class="bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold py-2 px-4 rounded-lg shadow-sm flex items-center gap-2 transition">
                <i class="fa-solid fa-arrow-left"></i>
                Back to Purchase Orders
            </a>
        </div>
    </div>

    <!-- Main Card & Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- File Upload Form (Left 2 columns) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-cloud-arrow-up text-amber-600"></i>
                    Upload File (.csv, .xlsx, .xls)
                </h2>

                <form id="importPoForm" action="?page=purchase_orders&action=import_csv_post" method="POST" enctype="multipart/form-data">
                    <div class="border-2 border-dashed border-gray-300 hover:border-amber-500 rounded-xl p-8 text-center bg-gray-50 hover:bg-amber-50/30 transition-all cursor-pointer relative" id="dropZone">
                        <input type="file" name="import_file" id="import_file" accept=".csv, .xlsx, .xls" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" required onchange="updateFileName(this)">
                        
                        <div class="flex flex-col items-center justify-center space-y-3" id="dropZoneContent">
                            <div class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center text-2xl shadow-inner">
                                <i class="fa-solid fa-file-excel"></i>
                            </div>
                            <div>
                                <p class="text-base font-semibold text-gray-700">
                                    Click to browse or drag and drop your file here
                                </p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Supports CSV, Excel (.xlsx), and Excel 97-2003 (.xls) files
                                </p>
                            </div>
                            <div id="selectedFileName" class="hidden font-medium text-amber-700 bg-amber-100/70 border border-amber-300 px-4 py-1.5 rounded-lg text-sm flex items-center gap-2">
                                <i class="fa-solid fa-file-circle-check"></i>
                                <span id="fileNameText">No file chosen</span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="submit" id="submitBtn" class="bg-amber-600 hover:bg-amber-700 text-white font-bold py-2.5 px-6 rounded-lg shadow transition duration-150 flex items-center gap-2">
                            <i class="fa-solid fa-upload"></i>
                            Start Import Process
                        </button>
                    </div>
                </form>
            </div>

            <!-- Import Results Section -->
            <div id="resultsContainer" class="<?php echo $result ? '' : 'hidden'; ?> bg-white rounded-xl shadow-md border border-gray-200 p-6 space-y-4">
                <h2 class="text-lg font-bold text-gray-800 border-b border-gray-100 pb-3 flex items-center gap-2">
                    <i class="fa-solid fa-square-poll-vertical text-amber-600"></i>
                    Import Report Summary
                </h2>

                <div id="statusMessage" class="p-4 rounded-lg text-sm font-medium">
                    <?php if ($result): ?>
                        <div class="p-4 rounded-lg text-sm font-medium <?php echo $result['success'] ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200'; ?>">
                            <i class="fa-solid <?php echo $result['success'] ? 'fa-circle-check text-emerald-600' : 'fa-circle-xmark text-rose-600'; ?> mr-2"></i>
                            <?php echo htmlspecialchars($result['message']); ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Stats Grid -->
                <div id="statsGrid" class="grid grid-cols-3 gap-4 text-center">
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3">
                        <div id="statPos" class="text-2xl font-bold text-amber-700"><?php echo $result['imported_pos'] ?? 0; ?></div>
                        <div class="text-xs font-semibold text-amber-800 uppercase tracking-wider mt-1">POs Created</div>
                    </div>
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                        <div id="statItems" class="text-2xl font-bold text-blue-700"><?php echo $result['imported_items'] ?? 0; ?></div>
                        <div class="text-xs font-semibold text-blue-800 uppercase tracking-wider mt-1">Line Items</div>
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <div id="statSkipped" class="text-2xl font-bold text-gray-700"><?php echo $result['skipped_pos'] ?? 0; ?></div>
                        <div class="text-xs font-semibold text-gray-600 uppercase tracking-wider mt-1">Skipped / Failed</div>
                    </div>
                </div>

                <!-- Details Table -->
                <div id="detailsTableWrapper" class="overflow-x-auto max-h-80 overflow-y-auto border border-gray-200 rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">PO Number</th>
                                <th class="px-4 py-2 text-left text-xs font-bold text-gray-600 uppercase">Vendor</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 uppercase">Items</th>
                                <th class="px-4 py-2 text-right text-xs font-bold text-gray-600 uppercase">Total Amount</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 uppercase">Status</th>
                                <th class="px-4 py-2 text-center text-xs font-bold text-gray-600 uppercase">Action</th>
                            </tr>
                        </thead>
                        <tbody id="detailsTableBody" class="bg-white divide-y divide-gray-100">
                            <?php if (!empty($result['details'])): ?>
                                <?php foreach ($result['details'] as $row): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2 font-bold text-amber-700"><?php echo htmlspecialchars($row['po_number']); ?></td>
                                        <td class="px-4 py-2 text-gray-700"><?php echo htmlspecialchars($row['vendor_name']); ?></td>
                                        <td class="px-4 py-2 text-center text-gray-700"><?php echo (int)$row['items_count']; ?></td>
                                        <td class="px-4 py-2 text-right font-semibold text-gray-800">₹<?php echo number_format((float)$row['total_cost'], 2); ?></td>
                                        <td class="px-4 py-2 text-center">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-bold capitalize bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <a href="?page=purchase_orders&action=view&po_id=<?php echo (int)$row['po_id']; ?>" target="_blank" class="text-blue-600 hover:underline text-xs font-semibold">
                                                View PO
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Errors / Diagnostics List -->
                <div id="errorsSection" class="<?php echo !empty($result['errors']) ? '' : 'hidden'; ?> bg-rose-50 border border-rose-200 rounded-lg p-4">
                    <h3 class="font-bold text-rose-800 text-sm mb-2 flex items-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        Import Errors / Warnings
                    </h3>
                    <ul id="errorsList" class="list-disc list-inside text-xs text-rose-700 space-y-1">
                        <?php if (!empty($result['errors'])): ?>
                            <?php foreach ($result['errors'] as $err): ?>
                                <li><?php echo htmlspecialchars($err); ?></li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Format & Guidelines Side Panel (Right 1 column) -->
        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6">
                <h2 class="text-base font-bold text-gray-800 mb-3 flex items-center gap-2 border-b border-gray-100 pb-2">
                    <i class="fa-solid fa-circle-info text-amber-600"></i>
                    Supported Excel/CSV Columns
                </h2>

                <p class="text-xs text-gray-600 mb-3">
                    Your file column headers are matched automatically. Supported field titles include:
                </p>

                <div class="space-y-2 text-xs">
                    <div class="bg-gray-50 p-2.5 rounded border border-gray-200">
                        <strong class="text-amber-800 block mb-0.5">PO Header Fields:</strong>
                        <ul class="list-disc list-inside text-gray-700 space-y-0.5">
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">PO Number</code> (e.g. PO-OLD-1001)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Vendor Code</code> (e.g. VEND-101 / lookup ID)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Vendor Name</code> (e.g. Exotic Vendor)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">PO Date</code> (YYYY-MM-DD)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Expected Delivery Date</code></li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Status</code> (pending, ordered, completed)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Warehouse Code</code> (e.g. WH-DELHI-01)</li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Shipping Cost</code></li>
                        </ul>
                    </div>

                    <div class="bg-gray-50 p-2.5 rounded border border-gray-200">
                        <strong class="text-amber-800 block mb-0.5">Line Item Fields:</strong>
                        <ul class="list-disc list-inside text-gray-700 space-y-0.5">
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Item Code</code></li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">SKU</code></li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Quantity</code></li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Unit Price / Rate</code></li>
                            <li><code class="bg-gray-200 px-1 rounded text-gray-800">Size</code> & <code class="bg-gray-200 px-1 rounded text-gray-800">Color</code></li>
                            <li class="text-gray-500 italic"><code class="bg-gray-200 px-1 rounded text-gray-600">Item Title</code> (Optional)</li>
                            <li class="text-gray-500 italic"><code class="bg-gray-200 px-1 rounded text-gray-600">GST Percent</code> (Optional)</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-xs text-amber-900 space-y-2">
                <h3 class="font-bold text-sm text-amber-800 flex items-center gap-1.5">
                    <i class="fa-solid fa-lightbulb"></i>
                    Migration Tips
                </h3>
                <p>
                    • Rows with the same <strong>PO Number</strong> will be grouped into a single Purchase Order with multiple item lines.
                </p>
                <p>
                    • If vendor names in the file do not exist in the database, new vendor profiles will be created automatically.
                </p>
                <p>
                    • If <strong>PO Number</strong> is left blank, the system will automatically generate a unique PO number formatted as <code>PO-YYYY-XXXXXX</code>.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileName(input) {
    const selectedDiv = document.getElementById('selectedFileName');
    const fileNameText = document.getElementById('fileNameText');
    if (input.files && input.files.length > 0) {
        fileNameText.textContent = input.files[0].name;
        selectedDiv.classList.remove('hidden');
    } else {
        selectedDiv.classList.add('hidden');
        fileNameText.textContent = 'No file chosen';
    }
}

document.getElementById('importPoForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const fileInput = document.getElementById('import_file');
    if (!fileInput.files || fileInput.files.length === 0) {
        if (window.showPosMessageModal) {
            window.showPosMessageModal({
                title: 'File Required',
                message: 'Please select a CSV or Excel file to import.',
                tone: 'warning'
            });
        } else {
            console.error('File required');
        }
        return;
    }

    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Processing Import...';

    const formData = new FormData(this);

    fetch('?page=purchase_orders&action=import_csv_post', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Start Import Process';

        const resultsContainer = document.getElementById('resultsContainer');
        const statusMessage = document.getElementById('statusMessage');
        const statPos = document.getElementById('statPos');
        const statItems = document.getElementById('statItems');
        const statSkipped = document.getElementById('statSkipped');
        const tbody = document.getElementById('detailsTableBody');
        const errorsSection = document.getElementById('errorsSection');
        const errorsList = document.getElementById('errorsList');

        resultsContainer.classList.remove('hidden');

        if (data.success) {
            statusMessage.innerHTML = `<div class="p-4 rounded-lg text-sm font-medium bg-emerald-50 text-emerald-800 border border-emerald-200"><i class="fa-solid fa-circle-check text-emerald-600 mr-2"></i>${escapeHtml(data.message)}</div>`;
        } else {
            statusMessage.innerHTML = `<div class="p-4 rounded-lg text-sm font-medium bg-rose-50 text-rose-800 border border-rose-200"><i class="fa-solid fa-circle-xmark text-rose-600 mr-2"></i>${escapeHtml(data.message || 'Import failed.')}</div>`;
        }

        statPos.textContent = data.imported_pos || 0;
        statItems.textContent = data.imported_items || 0;
        statSkipped.textContent = data.skipped_pos || 0;

        tbody.innerHTML = '';
        if (data.details && data.details.length > 0) {
            data.details.forEach(row => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                tr.innerHTML = `
                    <td class="px-4 py-2 font-bold text-amber-700">${escapeHtml(row.po_number)}</td>
                    <td class="px-4 py-2 text-gray-700">${escapeHtml(row.vendor_name)}</td>
                    <td class="px-4 py-2 text-center text-gray-700">${row.items_count}</td>
                    <td class="px-4 py-2 text-right font-semibold text-gray-800">₹${parseFloat(row.total_cost || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="px-4 py-2 text-center"><span class="px-2 py-0.5 rounded-full text-xs font-bold capitalize bg-blue-100 text-blue-800">${escapeHtml(row.status)}</span></td>
                    <td class="px-4 py-2 text-center"><a href="?page=purchase_orders&action=view&po_id=${row.po_id}" target="_blank" class="text-blue-600 hover:underline text-xs font-semibold">View PO</a></td>
                `;
                tbody.appendChild(tr);
            });
        }

        errorsList.innerHTML = '';
        if (data.errors && data.errors.length > 0) {
            errorsSection.classList.remove('hidden');
            data.errors.forEach(err => {
                const li = document.createElement('li');
                li.textContent = err;
                errorsList.appendChild(li);
            });
        } else {
            errorsSection.classList.add('hidden');
        }

        // Scroll to results
        resultsContainer.scrollIntoView({ behavior: 'smooth' });
    })
    .catch(error => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fa-solid fa-upload"></i> Start Import Process';
        console.error('Import error:', error);
        if (window.showPosMessageModal) {
            window.showPosMessageModal({
                title: 'Import Error',
                message: 'An unexpected error occurred while uploading. Please check the file and try again.',
                tone: 'error'
            });
        }
    });
});

function escapeHtml(text) {
    if (!text) return '';
    return String(text)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
</script>
