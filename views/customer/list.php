<?php
$qBase = ['page' => 'customer', 'action' => 'list'];
$searchVal = $filters['search'] ?? '';
$limit = isset($limit) ? (int)$limit : 50;
$limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 50;
$page_no = isset($page_no) ? max(1, (int)$page_no) : 1;
$total_pages = isset($total_pages) ? max(1, (int)$total_pages) : 1;
$total_records = isset($total_records) ? (int)$total_records : 0;
$is_admin_customer_list = !empty($is_admin_customer_list);
$has_list_scope = true;
$clearSearchParams = $qBase + ['limit' => $limit];
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

    <header class="relative overflow-hidden rounded-2xl border border-amber-100/90 bg-gradient-to-br from-amber-50/95 via-white to-orange-50/80 shadow-sm mb-8">
        <div class="pointer-events-none absolute -top-24 right-0 h-56 w-56 rounded-full bg-gradient-to-bl from-orange-200/25 to-transparent sm:h-72 sm:w-72"></div>
        <div class="pointer-events-none absolute bottom-0 left-0 h-32 w-32 rounded-full bg-amber-100/40 blur-2xl"></div>
        <div class="relative flex flex-col gap-8 px-5 py-8 sm:flex-row sm:items-center sm:justify-between sm:px-8 sm:py-10">
            <div class="flex min-w-0 flex-1 items-start gap-4 sm:gap-5">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-[#d97706] to-[#b45309] text-white shadow-lg shadow-amber-900/20 sm:h-16 sm:w-16">
                    <svg class="h-8 w-8 sm:h-9 sm:w-9 opacity-95" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
                <div class="min-w-0 pt-0.5">
                    <div class="flex flex-wrap items-center gap-2 gap-y-1">
                        <h1 class="text-2xl font-semibold tracking-tight text-gray-900 sm:text-3xl">Customers</h1>
                        <?php if ($is_admin_customer_list): ?>
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-900 ring-1 ring-inset ring-amber-200/80">Admin</span>
                        <?php elseif (!empty($warehouse_id)): ?>
                            <span class="inline-flex items-center rounded-full bg-white/90 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-amber-200/60 shadow-sm">POS</span>
                        <?php endif; ?>
                    </div>
                    <p class="mt-2 max-w-2xl text-sm leading-relaxed text-gray-600">
                        Full customer directory. Search by name, email, or phone. Purchase totals include all linked orders.
                    </p>
                </div>
            </div>
            <?php if ($has_list_scope): ?>
                <div class="flex shrink-0 sm:justify-end">
                    <div class="rounded-xl border border-amber-200/60 bg-gradient-to-br from-[#d97706] to-[#b45309] px-6 py-4 text-center text-white shadow-md shadow-amber-900/15 sm:min-w-[8rem]">
                        <p class="text-[10px] font-semibold uppercase tracking-wider text-white/90">Total</p>
                        <p class="mt-0.5 text-2xl font-bold tabular-nums"><?= number_format($total_records) ?></p>
                        <p class="text-xs text-white/80">matching</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </header>

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
                <a href="<?= htmlspecialchars(base_url('?' . http_build_query($clearSearchParams))) ?>" class="text-red-500 hover:text-red-700 text-sm font-medium px-2 py-2.5 whitespace-nowrap">Clear search</a>
            <?php endif; ?>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <?php if (empty($customers)): ?>
            <div class="col-span-full text-center py-12 text-gray-500 bg-white rounded-lg border border-dashed border-gray-200"><?= $searchVal !== '' ? 'No customers match your search.' : 'No customers found.' ?></div>
        <?php else: ?>
            <?php foreach ($customers as $c): ?>
                <?php
                $cur = !empty($c['currency']) ? $c['currency'] : '₹';
                $totalAmt = isset($c['total_order_amount']) ? (float)$c['total_order_amount'] : 0;
                $lastDt = !empty($c['last_purchase_date']) ? date('j/n/Y', strtotime($c['last_purchase_date'])) : '—';
                $orderCount = isset($c['order_count']) ? (int)$c['order_count'] : 0;
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
                        <?php if ($has_list_scope): ?>
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
                            <span class="text-gray-600">Total orders</span>
                            <span class="font-semibold text-gray-900 tabular-nums"><?= number_format($orderCount) ?></span>
                        </div>
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

    <?php if ($has_list_scope && $total_pages > 1): ?>
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
