<?php
/** @var array<string, mixed> $batch */
/** @var list<array<string, mixed>> $items */

$batchId = (int)($batch['id'] ?? 0);
$batchNo = htmlspecialchars((string)($batch['batch_no'] ?? ''));
$status = strtolower(trim((string)($batch['status'] ?? 'pending')));
$totalCust = (int)($batch['total_customers'] ?? 0);
$procCust = (int)($batch['processed_customers'] ?? 0);
$succCount = (int)($batch['success_count'] ?? 0);
$errCount = (int)($batch['error_count'] ?? 0);
$pct = $totalCust > 0 ? min(100, round(($procCust / $totalCust) * 100)) : 0;

$statusBadges = [
    'completed' => 'bg-green-100 text-green-800 border-green-300',
    'partially_completed' => 'bg-amber-100 text-amber-800 border-amber-300',
    'processing' => 'bg-blue-100 text-blue-800 border-blue-300 animate-pulse',
    'pending' => 'bg-gray-100 text-gray-800 border-gray-300',
    'failed' => 'bg-red-100 text-red-800 border-red-300',
];
$badgeClass = $statusBadges[$status] ?? $statusBadges['pending'];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header Breadcrumb & Controls -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="<?= base_url('?page=orders&action=list') ?>" class="hover:text-amber-600">Orders</a>
                <span>&rsaquo;</span>
                <span class="font-medium text-gray-700">Bulk Invoice Report</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                Bulk Invoice Batch #<?= $batchNo ?>
                <span id="batchStatusBadge" class="text-xs font-semibold px-2.5 py-1 rounded-full border <?= $badgeClass ?>">
                    <?= strtoupper(str_replace('_', ' ', $status)) ?>
                </span>
            </h1>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="<?= base_url('?page=invoices&action=export_batch_csv&batch_id=' . $batchId) ?>" 
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition">
                <span>📄</span> Export CSV
            </a>
            <a href="<?= base_url('?page=invoices&action=download_batch_zip&batch_id=' . $batchId) ?>" 
               class="inline-flex items-center gap-1.5 px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 shadow-sm transition">
                <span>📥</span> Download Invoices (ZIP)
            </a>
            <a href="<?= base_url('?page=dispatch&action=bulk_dispatch&invoice_batch_id=' . $batchId) ?>" 
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-bold shadow-sm transition">
                <span>🚚</span> Proceed to Bulk Dispatch &amp; Labels
            </a>
        </div>
    </div>

    <!-- Summary Statistics Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Total Customers</p>
            <p class="text-2xl font-extrabold text-gray-900" id="statTotalCust"><?= $totalCust ?></p>
            <p class="text-xs text-gray-500 mt-1"><?= (int)($batch['total_items'] ?? 0) ?> line items total</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Invoices Created</p>
            <p class="text-2xl font-extrabold text-green-600" id="statSuccCount"><?= $succCount ?></p>
            <p class="text-xs text-green-700 mt-1">Successfully generated</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Failed Items</p>
            <p class="text-2xl font-extrabold text-red-600" id="statErrCount"><?= $errCount ?></p>
            <p class="text-xs text-red-700 mt-1">Errors logged</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-5">
            <p class="text-xs font-semibold uppercase tracking-wider text-gray-500 mb-1">Progress</p>
            <p class="text-2xl font-extrabold text-amber-600" id="statPctText"><?= $pct ?>%</p>
            <p class="text-xs text-gray-500 mt-1"><span id="statProcCust"><?= $procCust ?></span> of <?= $totalCust ?> completed</p>
        </div>
    </div>

    <!-- Progress Bar -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex justify-between items-center mb-1 text-xs font-semibold text-gray-700">
            <span class="flex items-center gap-2">
                <span>Processing Status</span>
                <span id="processingSpinner" class="inline-block animate-spin text-amber-600 text-sm <?= ($status === 'completed' || $status === 'failed' || $status === 'partially_completed') ? 'hidden' : '' ?>">⚙</span>
            </span>
            <span id="progressBarLabel"><?= $procCust ?> / <?= $totalCust ?> Customers Processed (<?= $pct ?>%)</span>
        </div>
        <div class="w-full bg-gray-200 rounded-full h-3 overflow-hidden">
            <div id="progressBarFill" class="bg-amber-500 h-3 rounded-full transition-all duration-500" style="width: <?= $pct ?>%;"></div>
        </div>
    </div>

    <!-- Customer Line Items Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-gray-50">
            <h2 class="text-base font-bold text-gray-800">Customer Invoice Breakdown</h2>
            <div class="flex items-center gap-2">
                <button id="retryFailedBtn" type="button" onclick="retryFailedItems()" 
                        class="text-xs font-semibold bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 px-3 py-1.5 rounded-lg transition <?= $errCount > 0 ? '' : 'hidden' ?>">
                    🔄 Retry Failed Items (<span id="retryErrCount"><?= $errCount ?></span>)
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 text-xs font-semibold text-gray-600 uppercase tracking-wider text-left">
                    <tr>
                        <th class="px-6 py-3">Customer</th>
                        <th class="px-6 py-3">Order Numbers</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Invoice Number</th>
                        <th class="px-6 py-3 text-right">Invoice Amount</th>
                        <th class="px-6 py-3">Details / Errors</th>
                        <th class="px-6 py-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody id="batchItemsBody" class="divide-y divide-gray-200 bg-white">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No items found for this batch job.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $row): 
                            $itemStat = strtolower(trim((string)($row['status'] ?? 'pending')));
                            $itemBadgeClass = [
                                'completed' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                                'processing' => 'bg-blue-100 text-blue-800 animate-pulse',
                                'pending' => 'bg-gray-100 text-gray-700',
                            ][$itemStat] ?? 'bg-gray-100 text-gray-700';

                            $orderNos = json_decode((string)($row['order_numbers'] ?? '[]'), true);
                            $orderNoStr = is_array($orderNos) ? implode(', ', $orderNos) : '-';
                            $invId = (int)($row['invoice_id'] ?? 0);
                            $invNo = htmlspecialchars((string)($row['invoice_number'] ?? ''));
                        ?>
                            <tr id="batch-item-row-<?= (int)$row['id'] ?>" class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    <?= htmlspecialchars((string)($row['customer_name'] ?? ('Customer #' . $row['customer_id']))) ?>
                                    <span class="block text-xs font-normal text-gray-400">ID: #<?= (int)$row['customer_id'] ?></span>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded"><?= htmlspecialchars($orderNoStr) ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold <?= $itemBadgeClass ?>">
                                        <?= strtoupper($itemStat) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-indigo-600">
                                    <?= $invNo !== '' ? $invNo : '-' ?>
                                </td>
                                <td class="px-6 py-4 text-right font-semibold text-gray-900">
                                    <?= (float)($row['invoice_amount'] ?? 0) > 0 ? ('₹' . number_format((float)$row['invoice_amount'], 2)) : '-' ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600 max-w-xs truncate">
                                    <?php if (!empty($row['error_message'])): ?>
                                        <span class="text-red-600 font-semibold" title="<?= htmlspecialchars($row['error_message']) ?>">
                                            ❌ <?= htmlspecialchars($row['error_message']) ?>
                                        </span>
                                    <?php elseif ($itemStat === 'completed'): ?>
                                        <span class="text-green-600">✓ Invoice created successfully</span>
                                    <?php elseif ($itemStat === 'processing'): ?>
                                        <span class="text-blue-600 font-semibold animate-pulse">⏳ Processing invoice...</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">Queued for background worker</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <?php if ($invId > 0): ?>
                                        <a href="<?= base_url('?page=invoices&action=generate_pdf&invoice_id=' . $invId) ?>" 
                                           target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 hover:text-amber-700 underline">
                                            📄 View PDF
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-xs">-</span>
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

<script>
(function() {
    const batchId = <?= $batchId ?>;
    let isFinished = <?= ($status === 'completed' || $status === 'failed' || $status === 'partially_completed') ? 'true' : 'false' ?>;

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function pollStatus() {
        if (isFinished) return;

        fetch('index.php?page=invoices&action=process_batch_chunk&batch_id=' + batchId)
            .then(res => {
                if (res.redirected || (res.headers.get('content-type') && res.headers.get('content-type').includes('text/html'))) {
                    window.location.reload();
                    return null;
                }
                return res.json();
            })
            .then(data => {
                if (!data) return;
                if (data.success && data.batch) {
                    updateUI(data.batch, data.items || []);
                    if (data.batch.status === 'completed' || data.batch.status === 'failed' || data.batch.status === 'partially_completed') {
                        isFinished = true;
                        const spinner = document.getElementById('processingSpinner');
                        if (spinner) spinner.classList.add('hidden');
                    } else {
                        setTimeout(pollStatus, 800);
                    }
                } else {
                    setTimeout(pollStatus, 2000);
                }
            })
            .catch(err => {
                console.error('Processing poll error:', err);
                setTimeout(pollStatus, 3000);
            });
    }

    function updateUI(batch, items) {
        const total = parseInt(batch.total_customers, 10) || 0;
        const proc = parseInt(batch.processed_customers, 10) || 0;
        const succ = parseInt(batch.success_count, 10) || 0;
        const err = parseInt(batch.error_count, 10) || 0;
        const pct = total > 0 ? Math.min(100, Math.round((proc / total) * 100)) : 0;

        document.getElementById('statTotalCust').textContent = total;
        document.getElementById('statSuccCount').textContent = succ;
        document.getElementById('statErrCount').textContent = err;
        document.getElementById('statProcCust').textContent = proc;
        document.getElementById('statPctText').textContent = pct + '%';
        document.getElementById('progressBarLabel').textContent = proc + ' / ' + total + ' Customers Processed (' + pct + '%)';
        document.getElementById('progressBarFill').style.width = pct + '%';

        const retryBtn = document.getElementById('retryFailedBtn');
        const retryErrCount = document.getElementById('retryErrCount');
        if (retryBtn && retryErrCount) {
            retryErrCount.textContent = err;
            if (err > 0) {
                retryBtn.classList.remove('hidden');
            } else {
                retryBtn.classList.add('hidden');
            }
        }

        const badge = document.getElementById('batchStatusBadge');
        if (badge) {
            const st = (batch.status || 'pending').toLowerCase();
            badge.textContent = st.toUpperCase().replace('_', ' ');
            const badgeClasses = {
                'completed': 'bg-green-100 text-green-800 border-green-300',
                'partially_completed': 'bg-amber-100 text-amber-800 border-amber-300',
                'processing': 'bg-blue-100 text-blue-800 border-blue-300 animate-pulse',
                'pending': 'bg-gray-100 text-gray-800 border-gray-300',
                'failed': 'bg-red-100 text-red-800 border-red-300'
            };
            badge.className = 'text-xs font-semibold px-2.5 py-1 rounded-full border ' + (badgeClasses[st] || badgeClasses.pending);
        }

        const tbody = document.getElementById('batchItemsBody');
        if (tbody && Array.isArray(items) && items.length > 0) {
            let html = '';
            items.forEach(row => {
                const itemStat = (row.status || 'pending').toLowerCase();
                const badgeClass = {
                    'completed': 'bg-green-100 text-green-800',
                    'failed': 'bg-red-100 text-red-800',
                    'processing': 'bg-blue-100 text-blue-800 animate-pulse',
                    'pending': 'bg-gray-100 text-gray-700'
                }[itemStat] || 'bg-gray-100 text-gray-700';

                let orderNos = [];
                try {
                    orderNos = JSON.parse(row.order_numbers || '[]');
                } catch(e) {
                    orderNos = [];
                }
                const orderNoStr = Array.isArray(orderNos) && orderNos.length > 0 ? orderNos.join(', ') : '-';
                const invId = parseInt(row.invoice_id, 10) || 0;
                const invNo = escapeHtml(row.invoice_number || '');
                const amount = parseFloat(row.invoice_amount || 0);

                let statusDetail = '<span class="text-gray-400">Queued for background worker</span>';
                if (itemStat === 'processing') {
                    statusDetail = '<span class="text-blue-600 font-semibold animate-pulse">⏳ Processing invoice...</span>';
                } else if (itemStat === 'completed') {
                    statusDetail = '<span class="text-green-600">✓ Invoice created successfully</span>';
                } else if (row.error_message) {
                    statusDetail = `<span class="text-red-600 font-semibold" title="${escapeHtml(row.error_message)}">❌ ${escapeHtml(row.error_message)}</span>`;
                }

                let actionHtml = '<span class="text-gray-400 text-xs">-</span>';
                if (invId > 0) {
                    actionHtml = `<a href="index.php?page=invoices&action=generate_pdf&invoice_id=${invId}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 hover:text-amber-700 underline">📄 View PDF</a>`;
                }

                html += `<tr id="batch-item-row-${row.id}" class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-semibold text-gray-900">
                        ${escapeHtml(row.customer_name || ('Customer #' + row.customer_id))}
                        <span class="block text-xs font-normal text-gray-400">ID: #${row.customer_id}</span>
                    </td>
                    <td class="px-6 py-4 text-gray-700">
                        <span class="font-mono text-xs bg-gray-100 px-2 py-1 rounded">${escapeHtml(orderNoStr)}</span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold ${badgeClass}">
                            ${itemStat.toUpperCase()}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-mono font-bold text-indigo-600">
                        ${invNo !== '' ? invNo : '-'}
                    </td>
                    <td class="px-6 py-4 text-right font-semibold text-gray-900">
                        ${amount > 0 ? ('₹' + amount.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})) : '-'}
                    </td>
                    <td class="px-6 py-4 text-xs text-gray-600 max-w-xs truncate">
                        ${statusDetail}
                    </td>
                    <td class="px-6 py-4 text-center">
                        ${actionHtml}
                    </td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }
    }

    window.retryFailedItems = function() {
        if (!confirm('Re-queue all failed invoice items in this batch?')) return;
        fetch('index.php?page=invoices&action=retry_failed_batch_items&batch_id=' + batchId)
            .then(res => res.json())
            .then(data => {
                alert(data.message || 'Retried items.');
                isFinished = false;
                const spinner = document.getElementById('processingSpinner');
                if (spinner) spinner.classList.remove('hidden');
                pollStatus();
            });
    };

    if (!isFinished) {
        setTimeout(pollStatus, 500);
    }
})();
</script>
