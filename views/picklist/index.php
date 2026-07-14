<?php
/** @var array $data */
$filters = $data['filters'] ?? [];
$pageNo = (int) ($data['page_no'] ?? 1);
$totalPages = (int) ($data['total_pages'] ?? 1);
$limit = (int) ($data['limit'] ?? 20);
$totalRecords = (int) ($data['total_records'] ?? 0);
$picklists = $data['picklists'] ?? [];
$staffList = $data['picker_list'] ?? [];

$flash = $_SESSION['picklist_flash'] ?? null;
if ($flash) {
    unset($_SESSION['picklist_flash']);
}

$queryString = '';
if (!empty($_GET)) {
    $params = $_GET;
    unset($params['page_no'], $params['limit']);
    if ($params !== []) {
        $queryString = '&' . http_build_query($params);
    }
}
$pgBase = '?page=picklist&action=list&limit=' . $limit . $queryString;

$statusLabels = [
    'pending' => 'Pending',
    'in_progress' => 'In Progress',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled',
];
$statusStyles = [
    'pending' => 'bg-amber-50 text-amber-800 border-amber-200',
    'in_progress' => 'bg-sky-50 text-sky-800 border-sky-200',
    'completed' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
    'cancelled' => 'bg-gray-100 text-gray-600 border-gray-200',
];
$plId = static function (array $pl): int {
    return (int) ($pl['id'] ?? 0);
};
?>
<div class="w-full px-2 py-4 sm:px-3">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-4">
        <div class="relative px-4 py-5 sm:px-5 sm:py-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 mb-3">
                    <i class="fas fa-clipboard-list text-amber-700"></i>
                    <span>Warehouse · Picklist</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Picklists</h1>
                <p class="mt-2 text-sm text-gray-600">Manage warehouse pick lists for order processing.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="?page=orders&action=list" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 shadow-sm">
                    <i class="fas fa-shopping-cart"></i> Go to Orders
                </a>
                <a href="?page=picklist&action=wiki" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-slate-300 bg-slate-50 text-slate-800 text-sm font-semibold hover:bg-slate-100 shadow-sm"
                   title="Picklist user guide">
                    <i class="fas fa-book-open"></i> User guide
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ok = ($flash['type'] ?? '') === 'success'; ?>
        <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' ?>">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="get" class="mb-4 bg-white border rounded-xl p-3 shadow-sm">
        <input type="hidden" name="page" value="picklist">
        <input type="hidden" name="action" value="list">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <input type="text" name="search_text" value="<?= htmlspecialchars((string) ($filters['search_text'] ?? '')) ?>"
                   placeholder="Picklist #, picker, creator..." class="border rounded-lg px-3 py-2 text-sm">
            <select name="status" class="border rounded-lg px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <?php foreach ($statusLabels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($filters['status'] ?? '') === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <select name="picker_id" class="border rounded-lg px-3 py-2 text-sm">
                <option value="0">All pickers</option>
                <?php foreach ($staffList as $sid => $sname): ?>
                    <option value="<?= (int) $sid ?>" <?= (int) ($filters['picker_id'] ?? 0) === (int) $sid ? 'selected' : '' ?>><?= htmlspecialchars((string) $sname) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-amber-600 text-white rounded-lg px-4 py-2 text-sm font-semibold hover:bg-amber-700">Filter</button>
        </div>
    </form>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="px-3 py-3 border-b border-gray-100 flex flex-wrap items-center justify-between gap-2">
            <p class="text-sm text-gray-600">
                <span class="font-semibold text-gray-900 tabular-nums"><?= (int) $totalRecords ?></span> picklist<?= (int) $totalRecords === 1 ? '' : 's' ?>
            </p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-3 py-3 whitespace-nowrap">Picklist #</th>
                        <th class="px-3 py-3 whitespace-nowrap">Picker</th>
                        <th class="px-3 py-3 whitespace-nowrap">Status</th>
                        <th class="px-3 py-3 whitespace-nowrap min-w-[8rem]">Progress</th>
                        <th class="px-3 py-3 whitespace-nowrap">Created</th>
                        <th class="px-3 py-3 whitespace-nowrap text-center min-w-[18rem]">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($picklists === []): ?>
                        <tr>
                            <td colspan="6" class="px-3 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No picklists found</p>
                                    <p class="mt-1 text-sm text-gray-500">Create one from the orders list using Add to Picklist.</p>
                                    <a href="?page=orders&action=list" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                                        <i class="fas fa-shopping-cart" aria-hidden="true"></i>
                                        Go to orders
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($picklists as $pl): ?>
                            <?php
                            $id = $plId($pl);
                            $picked = (int) ($pl['picked_count'] ?? 0);
                            $total = (int) ($pl['item_count'] ?? 0);
                            $pct = $total > 0 ? round(($picked / $total) * 100) : 0;
                            $st = (string) ($pl['status'] ?? 'pending');
                            $statusClass = $statusStyles[$st] ?? 'bg-gray-100 text-gray-700 border-gray-200';
                            $viewUrl = '?page=picklist&action=view&id=' . $id;
                            $tabletUrl = '?page=picklist&action=tablet&id=' . $id;
                            $printUrl = $viewUrl . '&print=1';
                            $deleteUrl = '?page=picklist&action=delete&id=' . $id;
                            $deleteConfirm = 'Delete picklist ' . (string) ($pl['picklist_number'] ?? '') . '? Orders on this list will be set back to Item Received where applicable.';
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-3 py-3 align-middle">
                                    <a href="<?= htmlspecialchars($viewUrl) ?>"
                                       class="font-mono text-sm font-semibold text-amber-800 hover:text-amber-950 hover:underline underline-offset-2">
                                        <?= htmlspecialchars((string) ($pl['picklist_number'] ?? '')) ?>
                                    </a>
                                </td>
                                <td class="px-3 py-3 align-middle text-sm text-gray-800">
                                    <?= htmlspecialchars((string) ($pl['picker_name'] ?? '—')) ?>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border <?= $statusClass ?>">
                                        <?= htmlspecialchars($statusLabels[$st] ?? $st) ?>
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <div class="flex flex-col gap-1 min-w-[7rem]">
                                        <div class="flex items-center justify-between text-xs text-gray-600 tabular-nums">
                                            <span><?= $picked ?> / <?= $total ?></span>
                                            <span class="font-medium text-gray-800"><?= $pct ?>%</span>
                                        </div>
                                        <div class="h-1.5 w-full rounded-full bg-gray-200 overflow-hidden">
                                            <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all" style="width: <?= min(100, max(0, $pct)) ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3 align-middle text-sm text-gray-700 whitespace-nowrap">
                                    <?= !empty($pl['created_at']) ? date('d M Y, H:i', strtotime($pl['created_at'])) : '—' ?>
                                </td>
                                <td class="px-3 py-3 align-middle">
                                    <div class="flex flex-wrap items-center justify-center gap-2">
                                        <a href="<?= htmlspecialchars($viewUrl) ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-800 text-xs font-semibold shadow-sm hover:bg-blue-100 hover:border-blue-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-1 transition"
                                           title="View picklist details">
                                            <i class="fas fa-eye text-[11px] opacity-90" aria-hidden="true"></i>
                                            <span>View</span>
                                        </a>
                                        <a href="<?= htmlspecialchars($tabletUrl) ?>"
                                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-800 text-xs font-semibold shadow-sm hover:bg-emerald-100 hover:border-emerald-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:ring-offset-1 transition"
                                           title="Open tablet picking mode">
                                            <i class="fas fa-tablet-alt text-[11px] opacity-90" aria-hidden="true"></i>
                                            <span>Tablet</span>
                                        </a>
                                        <a href="<?= htmlspecialchars($printUrl) ?>" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-gray-300 bg-white text-gray-800 text-xs font-semibold shadow-sm hover:bg-gray-50 hover:border-gray-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-1 transition"
                                           title="Print picklist">
                                            <i class="fas fa-print text-[11px] opacity-90" aria-hidden="true"></i>
                                            <span>Print</span>
                                        </a>
                                        <a href="<?= htmlspecialchars($deleteUrl) ?>"
                                           class="js-picklist-confirm-action inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-red-200 bg-red-50 text-red-700 text-xs font-semibold shadow-sm hover:bg-red-100 hover:border-red-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-1 transition"
                                           data-confirm="<?= htmlspecialchars($deleteConfirm, ENT_QUOTES, 'UTF-8') ?>"
                                           title="Delete picklist">
                                            <i class="fas fa-trash-alt text-[11px] opacity-90" aria-hidden="true"></i>
                                            <span>Delete</span>
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
        <div class="mt-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-3 py-3 shadow-sm">
            <p class="text-sm text-gray-600">
                Page <span class="font-medium text-gray-900 tabular-nums"><?= $pageNo ?></span>
                of <span class="font-medium text-gray-900 tabular-nums"><?= $totalPages ?></span>
            </p>
            <div class="flex gap-2">
                <?php if ($pageNo > 1): ?>
                    <a href="<?= htmlspecialchars($pgBase . '&page_no=' . ($pageNo - 1)) ?>"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        <i class="fas fa-chevron-left text-xs" aria-hidden="true"></i> Prev
                    </a>
                <?php endif; ?>
                <?php if ($pageNo < $totalPages): ?>
                    <a href="<?= htmlspecialchars($pgBase . '&page_no=' . ($pageNo + 1)) ?>"
                       class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                        Next <i class="fas fa-chevron-right text-xs" aria-hidden="true"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/partials/confirm_delete_script.php'; ?>
