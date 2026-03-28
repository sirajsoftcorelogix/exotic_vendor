<?php
$qBase = ['page' => 'customer', 'action' => 'list'];
$searchVal = $filters['search'] ?? '';
$limit = isset($limit) ? (int)$limit : 50;
$limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 50;
$page_no = isset($page_no) ? max(1, (int)$page_no) : 1;
$total_pages = isset($total_pages) ? max(1, (int)$total_pages) : 1;
$total_records = isset($total_records) ? (int)$total_records : 0;
$viewUrl = function (int $id) {
    return base_url('?page=customer&action=view&customer_id=' . $id);
};
?>
<div class="max-w-[1400px] mx-auto px-4 sm:px-6 pt-[10px] pb-10 mr-4">
    <?php if (!empty($flash)): ?>
        <div class="mb-4 rounded-lg px-4 py-3 text-sm <?= ($flash['type'] ?? '') === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
            <?= htmlspecialchars($flash['message'] ?? '') ?>
        </div>
    <?php endif; ?>

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-normal text-gray-800">POS customers</h1>
            <?php if (!empty($warehouse_id)): ?>
                <p class="text-sm text-gray-500 mt-1">Warehouse: <span class="text-gray-700 font-medium"><?= htmlspecialchars($warehouse_name ?? '') ?></span></p>
            <?php else: ?>
                <p class="text-sm text-amber-700 mt-1">Select a POS warehouse (same session as the register) to see customers for that store.</p>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" action="<?= htmlspecialchars(base_url('')) ?>" class="mb-8">
        <input type="hidden" name="page" value="customer">
        <input type="hidden" name="action" value="list">
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex w-full sm:w-auto grow sm:grow-0 shadow-sm rounded-md">
                <input type="text" name="search" value="<?= htmlspecialchars($searchVal) ?>" placeholder="Search name, email, phone…"
                       class="pl-4 pr-4 py-2.5 border border-gray-200 border-r-0 rounded-l-md text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 w-full sm:w-72">
                <button type="submit" class="bg-gray-100 border border-gray-200 hover:bg-gray-200 text-gray-600 px-4 rounded-r-md transition-colors" title="Search">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>
            <div class="relative w-auto">
                <select name="limit" onchange="this.form.submit()" class="appearance-none bg-white border border-gray-200 text-gray-800 py-2.5 pl-3 pr-8 rounded shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer">
                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" <?= $limit === $opt ? 'selected' : '' ?>><?= $opt ?> per page</option>
                    <?php endforeach; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>
            <?php if ($searchVal !== ''): ?>
                <a href="<?= htmlspecialchars(base_url('?' . http_build_query($qBase + ['limit' => $limit]))) ?>" class="text-red-500 hover:text-red-700 text-sm font-medium px-2 py-2.5">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <?php if (empty($warehouse_id)): ?>
            <div class="col-span-full text-center py-12 text-gray-500 bg-white rounded-lg border border-gray-100">No warehouse context — open POS with a warehouse selected, then return here.</div>
        <?php elseif (empty($customers)): ?>
            <div class="col-span-full text-center py-12 text-gray-500">No customers found for this POS.</div>
        <?php else: ?>
            <?php foreach ($customers as $c): ?>
                <?php
                $cur = !empty($c['currency']) ? $c['currency'] : '₹';
                $totalAmt = isset($c['total_order_amount']) ? (float)$c['total_order_amount'] : 0;
                $lastDt = !empty($c['last_purchase_date']) ? date('j/n/Y', strtotime($c['last_purchase_date'])) : '—';
                ?>
                <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5 flex flex-col">
                    <div class="flex justify-between items-start mb-2 gap-2">
                        <div class="flex items-start gap-2 min-w-0">
                            <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            <div class="min-w-0">
                                <a href="<?= htmlspecialchars($viewUrl((int)$c['id'])) ?>" class="text-[#d97706] font-semibold text-base hover:underline block truncate" title="View customer"><?= htmlspecialchars($c['name'] ?? '') ?></a>
                                <p class="text-xs text-gray-500 mt-0.5">ID <?= (int)$c['id'] ?></p>
                            </div>
                        </div>
                        <?php if (!empty($warehouse_id)): ?>
                            <form method="post" action="<?= htmlspecialchars(base_url('?page=customer&action=delete_customer')) ?>" class="inline shrink-0" onsubmit="return confirm('Delete this customer? This cannot be undone.');">
                                <input type="hidden" name="customer_id" value="<?= (int)$c['id'] ?>">
                                <button type="submit" class="p-1.5 text-red-500 hover:text-red-700 rounded" title="Delete">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-1.5 text-sm text-gray-700 mb-3">
                        <div class="flex gap-2"><span class="text-gray-500 w-14 shrink-0">Email</span><span class="truncate"><?= htmlspecialchars($c['email'] ?? '—') ?></span></div>
                        <div class="flex gap-2"><span class="text-gray-500 w-14 shrink-0">Phone</span><span><?= htmlspecialchars($c['phone'] ?? '—') ?></span></div>
                    </div>
                    <div class="h-px bg-gray-200 my-3"></div>
                    <div class="space-y-2 text-sm mt-auto">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Total purchases</span>
                            <span class="font-semibold text-gray-900"><?= htmlspecialchars($cur) ?> <?= number_format($totalAmt, 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Last purchase</span>
                            <span class="font-medium text-gray-900"><?= htmlspecialchars($lastDt) ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (!empty($warehouse_id) && $total_pages > 1): ?>
        <?php
        $queryParams = array_merge($_GET, ['page' => 'customer', 'action' => 'list', 'limit' => $limit]);
        ?>
        <div class="flex justify-center items-center gap-2 flex-wrap mb-8">
            <?php if ($page_no > 1): ?>
                <?php $queryParams['page_no'] = $page_no - 1; ?>
                <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-600 text-sm">Prev</a>
            <?php endif; ?>
            <?php
            $from = max(1, $page_no - 4);
            $to = min($total_pages, $page_no + 4);
            for ($i = $from; $i <= $to; $i++):
                $queryParams['page_no'] = $i;
            ?>
                <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>"
                   class="px-3 py-1 border rounded text-sm <?= $i === $page_no ? 'bg-[#d97706] text-white border-[#d97706]' : 'hover:bg-gray-50 text-gray-600' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page_no < $total_pages): ?>
                <?php $queryParams['page_no'] = $page_no + 1; ?>
                <a href="?<?= htmlspecialchars(http_build_query($queryParams)) ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-600 text-sm">Next</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
