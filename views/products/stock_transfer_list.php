<?php
/** @var array $filters @var array $transfers @var array $users @var array $warehouses */
$pageNo = isset($page_no) ? (int) $page_no : 1;
$limit = isset($limit) ? (int) $limit : 50;
$totalRecords = isset($total_records) ? (int) $total_records : 0;
$filters = $filters ?? [];
$transfers = $transfers ?? [];
$users = $users ?? [];
$warehouses = $warehouses ?? [];
$totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;
$queryString = '';
if (!empty($_GET)) {
    $params = $_GET;
    unset($params['page_no'], $params['limit']);
    if ($params !== []) {
        $queryString = '&' . http_build_query($params);
    }
}
$pgBase = '?page=products&action=stock_transfer&limit=' . $limit . $queryString;

$statusClass = static function (?string $status): string {
    $s = strtolower(trim((string) $status));
    return match ($s) {
        'completed', 'complete', 'received' => 'bg-emerald-50 text-emerald-800 ring-emerald-600/20',
        'cancelled', 'canceled' => 'bg-gray-100 text-gray-700 ring-gray-500/20',
        'in transit', 'in_transit', 'dispatched' => 'bg-sky-50 text-sky-800 ring-sky-600/20',
        'pending', 'draft' => 'bg-amber-50 text-amber-900 ring-amber-600/25',
        default => 'bg-slate-50 text-slate-700 ring-slate-500/15',
    };
};
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Page header -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-6 mb-8">
        <div class="min-w-0">
            <div class="inline-flex items-center gap-2 text-amber-800/90 text-sm font-medium mb-2">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                    <i class="fas fa-exchange-alt" aria-hidden="true"></i>
                </span>
                <span>Warehouse</span>
            </div>
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 tracking-tight">Stock transfer history</h1>
            <p class="mt-2 text-sm sm:text-base text-gray-600 max-w-2xl">
                Review transfers between locations, open a transfer to edit, or record GRNs against a transfer order.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3 shrink-0">
            <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-sm text-center sm:text-left">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">Total (this view)</div>
                <div class="text-2xl font-semibold text-gray-900 tabular-nums"><?php echo number_format($totalRecords); ?></div>
            </div>
            <a href="?page=products&action=transfer_stock"
                class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-md shadow-amber-900/15 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                <i class="fas fa-plus" aria-hidden="true"></i>
                Create stock transfer
            </a>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80 flex items-center gap-3">
            <i class="fas fa-filter text-gray-400" aria-hidden="true"></i>
            <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
        </div>
        <form method="GET" action="" class="p-5">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="stock_transfer">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Transfer order no.</label>
                    <input type="text" name="transfer_order_no" value="<?= htmlspecialchars($filters['transfer_order_no'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        placeholder="e.g. TO-…"
                        autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dispatch date</label>
                    <input type="date" name="dispatch_date" value="<?= htmlspecialchars($filters['dispatch_date'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Requested by</label>
                    <select name="requested_by" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int)$user['id'] ?>" <?= (isset($filters['requested_by']) && (int)$filters['requested_by'] === (int)$user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dispatch by</label>
                    <select name="dispatch_by" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int)$user['id'] ?>" <?= (isset($filters['dispatch_by']) && (int)$filters['dispatch_by'] === (int)$user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Source location</label>
                    <select name="from_warehouse" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= (int)$wh['id'] ?>" <?= (isset($filters['from_warehouse']) && (int)$filters['from_warehouse'] === (int)$wh['id']) ? 'selected' : '' ?>><?= htmlspecialchars($wh['address_title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Destination location</label>
                    <select name="to_warehouse" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= (int)$wh['id'] ?>" <?= (isset($filters['to_warehouse']) && (int)$filters['to_warehouse'] === (int)$wh['id']) ? 'selected' : '' ?>><?= htmlspecialchars($wh['address_title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm:col-span-2 lg:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Item code or SKU</label>
                    <input type="text" name="item_number" value="<?= htmlspecialchars($filters['item_number'] ?? '') ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        placeholder="Search line items"
                        autocomplete="off">
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <a href="?page=products&action=stock_transfer" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Order</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Dispatch</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Requested</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Dispatch by</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[8rem]">Route</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[12rem]">Line items</th>
                        <th scope="col" class="px-5 py-3.5 text-right whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($transfers)): ?>
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No transfers match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try adjusting filters or create a new stock transfer.</p>
                                    <a href="?page=products&action=transfer_stock" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        New transfer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transfers as $transfer): ?>
                            <?php
                                $rawStatus = trim((string)($transfer['status'] ?? ''));
                                $statusLabel = $rawStatus !== '' ? $rawStatus : '—';
                                $statusRing = $statusClass($rawStatus);
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 align-top">
                                    <span class="font-mono text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($transfer['transfer_order_no']); ?></span>
                                </td>
                                <td class="px-5 py-4 align-top">
                                    <?php if ($rawStatus !== ''): ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?php echo $statusRing; ?>">
                                            <?php echo htmlspecialchars($statusLabel); ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-sm text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-700 whitespace-nowrap">
                                    <?php echo !empty($transfer['dispatch_date']) ? htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))) : '—'; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-700"><?php echo htmlspecialchars($transfer['requested_by_name'] ?? ''); ?></td>
                                <td class="px-5 py-4 align-top text-sm text-gray-700"><?php echo htmlspecialchars($transfer['dispatch_by_name'] ?? ''); ?></td>
                                <td class="px-5 py-4 align-top text-sm">
                                    <div class="flex flex-col gap-1.5 text-gray-700">
                                        <span class="inline-flex items-start gap-1.5">
                                            <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></span>
                                        </span>
                                        <span class="inline-flex items-start gap-1.5">
                                            <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-800">
                                    <?php if (!empty($transfer['items'])): ?>
                                        <ul class="space-y-2">
                                            <?php foreach ($transfer['items'] as $item): ?>
                                                <?php
                                                    $label = trim($item['sku'] ?? '');
                                                    if ($label === '') {
                                                        $label = trim($item['item_code'] ?? '');
                                                    }
                                                ?>
                                                <li class="rounded-lg bg-gray-50 px-2.5 py-1.5 border border-gray-100">
                                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($label ?: 'N/A'); ?></span>
                                                    <span class="text-gray-600"> · <?php echo (int)$item['transfer_qty']; ?> qty</span>
                                                    <?php if (!empty(trim($item['item_notes'] ?? ''))): ?>
                                                        <span class="block text-xs text-gray-500 mt-0.5"><?php echo htmlspecialchars($item['item_notes']); ?></span>
                                                    <?php endif; ?>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-gray-400">No items</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-right">
                                    <?php
                                    $productIds = [];
                                    foreach ($transfer['items'] as $item) {
                                        if (!empty($item['product_id'])) {
                                            $productIds[] = (int)$item['product_id'];
                                        }
                                    }
                                    $productIdsParam = urlencode(implode(',', array_unique($productIds)));
                                    ?>
                                    <div class="inline-flex flex-col gap-2 items-end">
                                        <a href="?page=products&action=transfer_stock&transfer_id=<?php echo urlencode($transfer['id']); ?>&product_ids=<?php echo $productIdsParam; ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-gray-200 text-gray-800 text-xs font-semibold hover:bg-gray-50 hover:border-gray-300 transition shadow-sm">
                                            <i class="fas fa-edit text-blue-600" aria-hidden="true"></i>
                                            Edit
                                        </a>
                                        <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-orange-500 text-white text-xs font-semibold hover:bg-orange-600 transition shadow-sm">
                                            <i class="fas fa-file-invoice" aria-hidden="true"></i>
                                            Add GRN
                                        </a>
                                        <a href="?page=stock_transfer_grns&action=list&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-emerald-600 text-white text-xs font-semibold hover:bg-emerald-700 transition shadow-sm">
                                            <i class="fas fa-list" aria-hidden="true"></i>
                                            View GRNs
                                        </a>
                                    </div>
                                </td>
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
                Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo($pageNo - 1) * $limit + 1; ?></span>
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
