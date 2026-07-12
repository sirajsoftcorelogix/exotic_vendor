<?php
/** @var array $data */
$filters = $data['filters'] ?? [];
$pageNo = (int) ($data['page_no'] ?? 1);
$totalPages = (int) ($data['total_pages'] ?? 1);
$limit = (int) ($data['limit'] ?? 20);
$totalRecords = (int) ($data['total_records'] ?? 0);
$picklists = $data['picklists'] ?? [];
$staffList = $data['staff_list'] ?? [];

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
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 mb-3">
                    <i class="fas fa-clipboard-list text-amber-700"></i>
                    <span>Warehouse · Picklist</span>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Picklists</h1>
                <p class="mt-2 text-sm text-gray-600">Manage warehouse pick lists for order processing.</p>
            </div>
            <a href="?page=orders&action=list" class="inline-flex items-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50">
                <i class="fas fa-shopping-cart"></i> Go to Orders
            </a>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ok = ($flash['type'] ?? '') === 'success'; ?>
        <div class="mb-4 rounded-xl border px-4 py-3 text-sm <?= $ok ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-red-200 bg-red-50 text-red-900' ?>">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="get" class="mb-6 bg-white border rounded-xl p-4 shadow-sm">
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

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b text-sm text-gray-600"><?= (int) $totalRecords ?> picklist(s)</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-gray-600">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Picklist #</th>
                        <th class="px-4 py-3 font-semibold">Picker</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Progress</th>
                        <th class="px-4 py-3 font-semibold">Created</th>
                        <th class="px-4 py-3 font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php if ($picklists === []): ?>
                        <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">No picklists found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($picklists as $pl): ?>
                            <?php
                            $picked = (int) ($pl['picked_count'] ?? 0);
                            $total = (int) ($pl['item_count'] ?? 0);
                            $pct = $total > 0 ? round(($picked / $total) * 100) : 0;
                            $st = (string) ($pl['status'] ?? 'pending');
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium">
                                    <a href="?page=picklist&action=view&id=<?= (int) $pl['id'] ?>" class="text-blue-600 hover:underline">
                                        <?= htmlspecialchars((string) ($pl['picklist_number'] ?? '')) ?>
                                    </a>
                                </td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($pl['picker_name'] ?? '—')) ?></td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100"><?= htmlspecialchars($statusLabels[$st] ?? $st) ?></span>
                                </td>
                                <td class="px-4 py-3"><?= $picked ?> / <?= $total ?> (<?= $pct ?>%)</td>
                                <td class="px-4 py-3"><?= !empty($pl['created_at']) ? date('d M Y H:i', strtotime($pl['created_at'])) : '—' ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        <a href="?page=picklist&action=view&id=<?= (int) $pl['id'] ?>" class="text-blue-600 hover:underline">View</a>
                                        <a href="?page=picklist&action=tablet&id=<?= (int) $pl['id'] ?>" class="text-green-600 hover:underline">Tablet</a>
                                        <a href="?page=picklist&action=view&id=<?= (int) $pl['id'] ?>&print=1" target="_blank" class="text-gray-600 hover:underline">Print</a>
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
        <div class="mt-4 flex items-center justify-between text-sm">
            <span>Page <?= $pageNo ?> of <?= $totalPages ?></span>
            <div class="flex gap-2">
                <?php if ($pageNo > 1): ?>
                    <a href="<?= $pgBase ?>&page_no=<?= $pageNo - 1 ?>" class="px-3 py-1 border rounded">Prev</a>
                <?php endif; ?>
                <?php if ($pageNo < $totalPages): ?>
                    <a href="<?= $pgBase ?>&page_no=<?= $pageNo + 1 ?>" class="px-3 py-1 border rounded">Next</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>
