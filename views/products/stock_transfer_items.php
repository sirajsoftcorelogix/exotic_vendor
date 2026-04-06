<?php
/** @var array $transfer @var array $items @var int $transfer_id */
$pageNo = isset($page_no) ? (int) $page_no : 1;
$limit = isset($limit) ? (int) $limit : 50;
$totalRecords = isset($total_records) ? (int) $total_records : 0;
$transfer = $transfer ?? [];
$items = $items ?? [];
$transferId = isset($transfer_id) ? (int) $transfer_id : (int) ($transfer['id'] ?? 0);
$totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;
$pgBase = '?page=products&action=stock_transfer_items&transfer_id=' . $transferId . '&limit=' . $limit;
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="mb-6">
        <a href="?page=products&action=stock_transfer"
            class="inline-flex items-center gap-2 text-sm font-medium text-gray-600 hover:text-gray-900 transition">
            <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
            Back to stock transfers
        </a>
    </div>

    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-8">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 text-amber-800/90 text-sm font-medium mb-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <i class="fas fa-list-ul" aria-hidden="true"></i>
                </span>
                <span>Transfer</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Line items</h1>
            <p class="mt-2 text-sm text-gray-600">
                <span class="font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($transfer['transfer_order_no'] ?? ''); ?></span>
                <?php if (!empty($transfer['dispatch_date'])): ?>
                    <span class="text-gray-400">·</span>
                    Dispatch <?php echo htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))); ?>
                <?php endif; ?>
                <?php if (!empty($transfer['status'])): ?>
                    <span class="text-gray-400">·</span>
                    <?php echo htmlspecialchars($transfer['status']); ?>
                <?php endif; ?>
            </p>
            <p class="mt-1 text-sm text-gray-600">
                <span class="inline-flex items-start gap-1.5">
                    <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></span>
                </span>
                <span class="mx-1.5 text-gray-300">→</span>
                <span class="inline-flex items-start gap-1.5">
                    <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs" aria-hidden="true"></i>
                    <span><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></span>
                </span>
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm text-center sm:text-left">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lines</div>
                <div class="text-2xl font-semibold text-gray-900 tabular-nums"><?php echo number_format($totalRecords); ?></div>
            </div>
            <a href="?page=products&action=transfer_stock&transfer_id=<?php echo urlencode($transferId); ?>"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-white border border-gray-200 text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 transition">
                <i class="fas fa-edit text-blue-600" aria-hidden="true"></i>
                Edit transfer
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">SKU / code</th>
                        <th scope="col" class="px-5 py-3.5 text-right whitespace-nowrap">Qty</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[12rem]">Notes</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="4" class="px-5 py-12 text-center text-sm text-gray-500">No line items for this transfer.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                                $label = trim($item['sku'] ?? '');
                                if ($label === '') {
                                    $label = trim($item['item_code'] ?? '');
                                }
                                if ($label === '') {
                                    $label = '#' . (int)($item['id'] ?? 0);
                                }
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-3.5 text-sm text-gray-500 tabular-nums"><?php echo (int)($item['id'] ?? 0); ?></td>
                                <td class="px-5 py-3.5 text-sm font-medium text-gray-900"><?php echo htmlspecialchars($label); ?></td>
                                <td class="px-5 py-3.5 text-sm text-gray-800 text-right tabular-nums"><?php echo number_format((int)($item['transfer_qty'] ?? 0)); ?></td>
                                <td class="px-5 py-3.5 text-sm text-gray-600"><?php echo htmlspecialchars(trim($item['item_notes'] ?? '') ?: '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
            <p class="text-sm text-gray-600">
                Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo ($pageNo - 1) * $limit + 1; ?></span>
                –
                <span class="font-medium text-gray-900 tabular-nums"><?php echo min($pageNo * $limit, $totalRecords); ?></span>
                of <span class="font-medium text-gray-900 tabular-nums"><?php echo number_format($totalRecords); ?></span>
            </p>
            <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=1'); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">First</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . max(1, $pageNo - 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Previous</a>
                <span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums">Page <?php echo $pageNo; ?> / <?php echo $totalPages; ?></span>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . min($totalPages, $pageNo + 1)); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Next</a>
                <a href="<?php echo htmlspecialchars($pgBase . '&page_no=' . $totalPages); ?>"
                    class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?php echo $pageNo >= $totalPages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'; ?>">Last</a>
            </nav>
        </div>
    <?php endif; ?>
</div>
