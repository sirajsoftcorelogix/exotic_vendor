<?php
/** @var list<array<string, mixed>> $batches */
/** @var int $page */
/** @var int $limit */
/** @var int $totalBatches */
/** @var int $totalPages */
/** @var array<string, string> $filters */

$statusBadges = [
    'completed' => 'bg-green-100 text-green-800 border-green-300',
    'partially_completed' => 'bg-amber-100 text-amber-800 border-amber-300',
    'processing' => 'bg-blue-100 text-blue-800 border-blue-300 animate-pulse',
    'pending' => 'bg-gray-100 text-gray-800 border-gray-300',
    'failed' => 'bg-red-100 text-red-800 border-red-300',
];

$batchNoFilter = htmlspecialchars((string)($filters['batch_no'] ?? ''));
$statusFilter = htmlspecialchars((string)($filters['status'] ?? ''));
$dateFilter = htmlspecialchars((string)($filters['date'] ?? ''));
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header Breadcrumb & Actions -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div>
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-1">
                <a href="<?= base_url('?page=orders&action=list') ?>" class="hover:text-amber-600">Orders</a>
                <span>&rsaquo;</span>
                <span class="font-medium text-gray-700">Bulk Invoice Batches</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                📦 Bulk Invoice Batches
            </h1>
        </div>

        <div class="flex items-center gap-2">
            <a href="<?= base_url('?page=orders&action=list') ?>" 
               class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-bold shadow-sm transition">
                <span>+</span> New Bulk Invoice Job
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" action="<?= base_url('index.php') ?>" class="flex flex-wrap items-end gap-3 text-sm">
            <input type="hidden" name="page" value="invoices">
            <input type="hidden" name="action" value="batch_list">

            <div>
                <label for="batch_no" class="block text-xs font-semibold text-gray-600 mb-1">Batch Number</label>
                <input type="text" id="batch_no" name="batch_no" value="<?= $batchNoFilter ?>" 
                       placeholder="e.g. INV-BATCH-..." 
                       class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select id="status" name="status" class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm w-40 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none bg-white">
                    <option value="">All Statuses</option>
                    <option value="completed" <?= $statusFilter === 'completed' ? 'selected' : '' ?>>Completed</option>
                    <option value="partially_completed" <?= $statusFilter === 'partially_completed' ? 'selected' : '' ?>>Partially Completed</option>
                    <option value="processing" <?= $statusFilter === 'processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="failed" <?= $statusFilter === 'failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div>
                <label for="date" class="block text-xs font-semibold text-gray-600 mb-1">Created Date</label>
                <input type="date" id="date" name="date" value="<?= $dateFilter ?>" 
                       class="border border-gray-300 rounded-lg px-3 py-1.5 text-sm focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none">
            </div>

            <div class="flex items-center gap-2">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-1.5 rounded-lg text-sm shadow-sm transition">
                    Filter
                </button>
                <a href="<?= base_url('?page=invoices&action=batch_list') ?>" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-1.5 rounded-lg text-sm transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Batches Table -->
    <div class="bg-white shadow-sm border border-gray-200 rounded-xl overflow-hidden mb-6">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-100 text-xs font-semibold text-gray-600 uppercase tracking-wider text-left">
                    <tr>
                        <th class="px-6 py-3">Batch Number</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3">Customers / Items</th>
                        <th class="px-6 py-3">Success / Errors</th>
                        <th class="px-6 py-3">Created By</th>
                        <th class="px-6 py-3">Created At</th>
                        <th class="px-6 py-3 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    <?php if (empty($batches)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">No bulk invoice batches found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($batches as $row): 
                            $bId = (int)$row['id'];
                            $bNo = htmlspecialchars((string)($row['batch_no'] ?? ''));
                            $st = strtolower(trim((string)($row['status'] ?? 'pending')));
                            $badgeClass = $statusBadges[$st] ?? $statusBadges['pending'];
                            $totCust = (int)($row['total_customers'] ?? 0);
                            $totOrd = (int)($row['total_orders'] ?? 0);
                            $totItems = (int)($row['total_items'] ?? 0);
                            $succ = (int)($row['success_count'] ?? 0);
                            $err = (int)($row['error_count'] ?? 0);
                            $creator = htmlspecialchars((string)($row['creator_name'] ?? ('User #' . $row['created_by'])));
                            $createdAt = date('d-M-Y H:i', strtotime((string)$row['created_at']));
                        ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-mono font-bold text-amber-700">
                                    <a href="<?= base_url('?page=invoices&action=batch_report&batch_id=' . $bId) ?>" class="hover:underline">
                                        <?= $bNo ?>
                                    </a>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border <?= $badgeClass ?>">
                                        <?= strtoupper(str_replace('_', ' ', $st)) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-700 text-xs">
                                    <span class="font-semibold text-gray-900"><?= $totCust ?></span> customers &middot; 
                                    <span><?= $totOrd ?></span> orders &middot; 
                                    <span><?= $totItems ?></span> items
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold">
                                    <span class="text-green-700">✓ <?= $succ ?> Success</span>
                                    <?php if ($err > 0): ?>
                                        &middot; <span class="text-red-600">❌ <?= $err ?> Failed</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-600">
                                    <?= $creator ?>
                                </td>
                                <td class="px-6 py-4 text-xs text-gray-500 whitespace-nowrap">
                                    <?= $createdAt ?>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="<?= base_url('?page=invoices&action=batch_report&batch_id=' . $bId) ?>" 
                                           class="px-2.5 py-1 bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-200 rounded text-xs font-bold transition"
                                           title="View Summary Report">
                                            📊 Report
                                        </a>

                                        <!-- Export Excel Dropdown -->
                                        <div class="relative inline-block text-left" x-data="{ open: false }" @click.away="open = false">
                                            <button type="button" @click="open = !open" 
                                                    class="px-2.5 py-1 bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-300 rounded text-xs font-bold inline-flex items-center gap-1 transition">
                                                <span>📥 Excel</span>
                                                <span>▼</span>
                                            </button>
                                            <div x-show="open" class="origin-top-right absolute right-0 mt-1 w-44 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 z-20 focus:outline-none" style="display: none;">
                                                <div class="py-1 text-xs text-left">
                                                    <a href="<?= base_url('?page=invoices&action=export_batch_dispatch_excel&batch_id=' . $bId . '&format=shiprocket') ?>" class="block px-4 py-2 text-gray-700 hover:bg-amber-50 hover:text-amber-900 font-medium">📦 Shiprocket Format</a>
                                                    <a href="<?= base_url('?page=invoices&action=export_batch_dispatch_excel&batch_id=' . $bId . '&format=delhivery') ?>" class="block px-4 py-2 text-gray-700 hover:bg-emerald-50 hover:text-emerald-900 font-medium">🚚 Delhivery Format</a>
                                                    <a href="<?= base_url('?page=invoices&action=export_batch_dispatch_excel&batch_id=' . $bId . '&format=bluedart') ?>" class="block px-4 py-2 text-gray-700 hover:bg-sky-50 hover:text-sky-900 font-medium">✈️ BlueDart Format</a>
                                                    <a href="<?= base_url('?page=invoices&action=export_batch_dispatch_excel&batch_id=' . $bId . '&format=standard') ?>" class="block px-4 py-2 text-gray-700 hover:bg-gray-100 font-medium">📄 Standard Manifest</a>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($succ > 0 && $st !== 'failed'): ?>
                                            <button type="button" onclick="cancelBatchInvoices(<?= $bId ?>, '<?= $bNo ?>')" 
                                                    class="px-2 py-1 bg-red-50 text-red-700 hover:bg-red-100 border border-red-200 rounded text-xs font-bold transition"
                                                    title="Cancel All Invoices in Batch">
                                                ❌ Cancel
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($totalPages > 1): ?>
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between text-xs text-gray-600">
                <div>
                    Showing page <span class="font-bold"><?= $page ?></span> of <span class="font-bold"><?= $totalPages ?></span> (<?= $totalBatches ?> total batches)
                </div>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="<?= base_url('?page=invoices&action=batch_list&page_no=' . ($page - 1) . '&limit=' . $limit . '&batch_no=' . urlencode($filters['batch_no']) . '&status=' . urlencode($filters['status']) . '&date=' . urlencode($filters['date'])) ?>" 
                           class="px-3 py-1.5 bg-white border border-gray-300 rounded hover:bg-gray-50 font-semibold">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="<?= base_url('?page=invoices&action=batch_list&page_no=' . $i . '&limit=' . $limit . '&batch_no=' . urlencode($filters['batch_no']) . '&status=' . urlencode($filters['status']) . '&date=' . urlencode($filters['date'])) ?>" 
                           class="px-3 py-1.5 rounded font-semibold <?= $i === $page ? 'bg-amber-600 text-white' : 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-700' ?>"><?= $i ?></a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="<?= base_url('?page=invoices&action=batch_list&page_no=' . ($page + 1) . '&limit=' . $limit . '&batch_no=' . urlencode($filters['batch_no']) . '&status=' . urlencode($filters['status']) . '&date=' . urlencode($filters['date'])) ?>" 
                           class="px-3 py-1.5 bg-white border border-gray-300 rounded hover:bg-gray-50 font-semibold">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
function cancelBatchInvoices(batchId, batchNo) {
    if (!confirm(`Are you sure you want to CANCEL ALL generated invoices in Batch #${batchNo}?\n\nThis will restore stock and mark all invoices in this batch as cancelled.`)) {
        return;
    }

    fetch('index.php?page=invoices&action=cancel_batch_invoices', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ batch_id: batchId })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(data.message || 'Batch invoices cancelled successfully.');
            window.location.reload();
        } else {
            alert('Cancellation failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error('Cancel batch error:', err);
        alert('Network error while cancelling batch invoices.');
    });
}
</script>
