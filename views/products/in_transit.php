<?php
/** @var string $view @var array $records @var array $breakdown @var array $summary @var array $filters @var array $warehouses @var int $page_no @var int $total_records @var int $limit @var string $sort @var int $user_warehouse_id @var bool $is_admin */
$pageNo = isset($page_no) ? (int) $page_no : 1;
$limit = isset($limit) ? (int) $limit : 50;
$totalRecords = isset($total_records) ? (int) $total_records : 0;
$view = in_array(($view ?? 'item'), ['item', 'transfer'], true) ? $view : 'item';
$records = $records ?? [];
$breakdown = $breakdown ?? [];
$summary = $summary ?? [];
$filters = $filters ?? [];
$warehouses = $warehouses ?? [];
$sort = $sort ?? 'oldest_dispatch';
$userWarehouseId = isset($user_warehouse_id) ? (int) $user_warehouse_id : 0;
$isAdmin = !empty($is_admin);
$totalPages = $limit > 0 ? (int) ceil($totalRecords / $limit) : 1;

$filtersPanelOpen =
    trim((string) ($filters['search'] ?? '')) !== ''
    || (isset($filters['from_warehouse']) && (int) $filters['from_warehouse'] > 0)
    || (isset($filters['to_warehouse']) && (int) $filters['to_warehouse'] > 0)
    || trim((string) ($filters['direction'] ?? '')) !== ''
    || trim((string) ($filters['receipt_state'] ?? 'any')) !== 'any'
    || trim((string) ($filters['dispatched_from'] ?? '')) !== ''
    || trim((string) ($filters['dispatched_to'] ?? '')) !== ''
    || (int) ($filters['age_min_days'] ?? 0) > 0;

$queryParams = $_GET;
unset($queryParams['page_no']);
$baseQuery = http_build_query(array_merge($queryParams, ['page' => 'products']));
$queryString = $baseQuery !== '' ? '&' . $baseQuery : '';
$pgBase = '?page=products&action=in_transit&limit=' . $limit . $queryString;
$exportBase = '?page=products&action=in_transit_export' . $queryString;

$viewTabBase = static function (string $tabView) use ($queryParams): string {
    $params = $queryParams;
    $params['page'] = 'products';
    $params['action'] = 'in_transit';
    $params['view'] = $tabView;
    unset($params['page_no']);
    return '?' . http_build_query($params);
};

$formatDate = static function (?string $date): string {
    if ($date === null || trim($date) === '') {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('j M Y', $ts) : htmlspecialchars($date);
};

$agedClass = static function (int $days): string {
    if ($days >= 14) {
        return 'text-red-700 font-semibold';
    }
    if ($days >= 7) {
        return 'text-amber-800 font-medium';
    }
    return 'text-gray-700';
};
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-sky-200/50 bg-gradient-to-br from-sky-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-sky-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-sky-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-sky-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-sky-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-sky-100 text-sky-700">
                        <i class="fas fa-truck-loading text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Warehouse · In transit inventory</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    In transit <span class="text-sky-800">inventory</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Find stock dispatched on transfer orders that has not yet been fully received at the destination warehouse.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row shrink-0 gap-2 lg:pl-4 lg:self-center">
                <a href="?page=products&action=stock_transfer"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-200 bg-white text-gray-700 text-sm font-semibold shadow-sm hover:bg-gray-50 transition whitespace-nowrap">
                    <i class="fas fa-history text-xs text-gray-500" aria-hidden="true"></i>
                    Transfer history
                </a>
                <a href="<?php echo htmlspecialchars($exportBase); ?>"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-b from-emerald-600 to-emerald-700 text-white text-sm font-semibold shadow-lg shadow-emerald-900/15 hover:from-emerald-700 hover:to-emerald-800 transition whitespace-nowrap">
                    <i class="fas fa-file-csv text-xs opacity-95" aria-hidden="true"></i>
                    Export CSV
                </a>
            </div>
        </div>
    </div>

    <!-- KPI summary -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
        <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm ring-1 ring-gray-900/[0.03]">
            <div class="text-[11px] font-bold uppercase tracking-wider text-sky-700/80">Units in transit</div>
            <div class="mt-2 text-3xl font-bold tabular-nums text-gray-900"><?php echo number_format((int) ($summary['pending_units'] ?? 0)); ?></div>
            <p class="mt-1 text-xs text-gray-500">Pending receipt across all open lines</p>
        </div>
        <div class="rounded-2xl border border-gray-200/80 bg-white p-5 shadow-sm ring-1 ring-gray-900/[0.03]">
            <div class="text-[11px] font-bold uppercase tracking-wider text-gray-500">Open transfers</div>
            <div class="mt-2 text-3xl font-bold tabular-nums text-gray-900"><?php echo number_format((int) ($summary['open_transfers'] ?? 0)); ?></div>
            <p class="mt-1 text-xs text-gray-500">Transfer orders with pending qty</p>
        </div>
        <div class="rounded-2xl border border-amber-100 bg-white p-5 shadow-sm ring-1 ring-gray-900/[0.03]">
            <div class="text-[11px] font-bold uppercase tracking-wider text-amber-800/80">Partial receipts</div>
            <div class="mt-2 text-3xl font-bold tabular-nums text-gray-900"><?php echo number_format((int) ($summary['partial_transfers'] ?? 0)); ?></div>
            <p class="mt-1 text-xs text-gray-500">Some GRN recorded, not complete</p>
        </div>
        <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm ring-1 ring-gray-900/[0.03]">
            <div class="text-[11px] font-bold uppercase tracking-wider text-red-700/80">Aged 14+ days</div>
            <div class="mt-2 text-3xl font-bold tabular-nums text-red-800"><?php echo number_format((int) ($summary['aged_14_plus'] ?? 0)); ?></div>
            <p class="mt-1 text-xs text-gray-500">Transfers dispatched over 2 weeks ago</p>
        </div>
    </div>

    <?php if (!empty($summary['by_destination'])): ?>
        <div class="mb-6 flex flex-wrap items-center gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 mr-1">By destination</span>
            <?php foreach ($summary['by_destination'] as $dest): ?>
                <?php
                    $destId = (int) ($dest['warehouse_id'] ?? 0);
                    $destParams = $queryParams;
                    $destParams['page'] = 'products';
                    $destParams['action'] = 'in_transit';
                    $destParams['to_warehouse'] = $destId;
                    unset($destParams['page_no']);
                    $destUrl = '?' . http_build_query($destParams);
                ?>
                <a href="<?php echo htmlspecialchars($destUrl); ?>"
                    class="inline-flex items-center gap-1.5 rounded-full border border-sky-100 bg-sky-50/80 px-3 py-1 text-xs font-medium text-sky-900 hover:bg-sky-100 transition">
                    <span><?php echo htmlspecialchars((string) ($dest['warehouse_name'] ?? '')); ?></span>
                    <span class="tabular-nums font-bold"><?php echo number_format((int) ($dest['pending_qty'] ?? 0)); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- View tabs -->
    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm mb-6 overflow-hidden">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 px-4 sm:px-5 border-b border-gray-100">
            <nav class="flex gap-6" aria-label="View mode">
                <?php
                    $tabs = ['item' => 'By item', 'transfer' => 'By transfer'];
                    foreach ($tabs as $tabKey => $tabLabel):
                        $isActive = $view === $tabKey;
                ?>
                    <a href="<?php echo htmlspecialchars($viewTabBase($tabKey)); ?>"
                        class="<?php echo $isActive
                            ? 'border-sky-500 text-sky-700 font-bold border-b-2'
                            : 'border-transparent text-gray-500 hover:text-gray-700 font-medium border-b-2'; ?> whitespace-nowrap py-4 text-sm transition-colors">
                        <?php echo htmlspecialchars($tabLabel); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <form method="GET" class="flex items-center gap-2 py-3 sm:py-0">
                <input type="hidden" name="page" value="products">
                <input type="hidden" name="action" value="in_transit">
                <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                <?php foreach ($filters as $fk => $fv): ?>
                    <?php if (is_scalar($fv) && (string) $fv !== '' && $fk !== 'age_min_days'): ?>
                        <input type="hidden" name="<?php echo htmlspecialchars((string) $fk); ?>" value="<?php echo htmlspecialchars((string) $fv); ?>">
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ((int) ($filters['age_min_days'] ?? 0) > 0): ?>
                    <input type="hidden" name="aged_only" value="1">
                <?php endif; ?>
                <label for="itSort" class="text-xs font-semibold text-gray-500 whitespace-nowrap">Sort</label>
                <select id="itSort" name="sort" onchange="this.form.submit()"
                    class="text-sm border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                    <?php if ($view === 'item'): ?>
                        <option value="oldest_dispatch" <?php echo $sort === 'oldest_dispatch' ? 'selected' : ''; ?>>Oldest dispatch first</option>
                        <option value="pending_qty_desc" <?php echo $sort === 'pending_qty_desc' ? 'selected' : ''; ?>>Largest pending qty</option>
                        <option value="item_code_asc" <?php echo $sort === 'item_code_asc' ? 'selected' : ''; ?>>Item code A–Z</option>
                    <?php else: ?>
                        <option value="oldest_dispatch" <?php echo $sort === 'oldest_dispatch' ? 'selected' : ''; ?>>Oldest dispatch first</option>
                        <option value="newest_dispatch" <?php echo $sort === 'newest_dispatch' ? 'selected' : ''; ?>>Newest dispatch first</option>
                        <option value="pending_qty_desc" <?php echo $sort === 'pending_qty_desc' ? 'selected' : ''; ?>>Largest pending qty</option>
                    <?php endif; ?>
                </select>
            </form>
        </div>
    </div>

    <style>
        #it-filters > summary { list-style: none; }
        #it-filters > summary::-webkit-details-marker { display: none; }
        #it-filters[open] > summary { border-bottom: 1px solid rgba(224, 242, 254, 0.9); }
        #it-filters:not([open]) .itf-label-open { display: none; }
        #it-filters[open] .itf-label-closed { display: none; }
        #it-filters[open] .itf-chevron { transform: rotate(180deg); }
    </style>

    <details id="it-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?php echo $filtersPanelOpen ? 'open' : ''; ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-sky-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-sky-700 shadow-sm border border-sky-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">SKU, item code, transfer order, route, receipt state, or dispatch dates.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-sky-800">
                <span class="itf-label-closed">Show</span>
                <span class="itf-label-open">Hide</span>
                <i class="itf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>
        <form method="GET" action="" class="p-5">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="in_transit">
            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
            <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort); ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-x-5 gap-y-4">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search" value="<?php echo htmlspecialchars((string) ($filters['search'] ?? '')); ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition"
                        placeholder="SKU, item code, transfer order, product title"
                        autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Source location</label>
                    <select name="from_warehouse" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                        <option value="">All</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo (int) $wh['id']; ?>" <?php echo (isset($filters['from_warehouse']) && (int) $filters['from_warehouse'] === (int) $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['address_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Destination location</label>
                    <select name="to_warehouse" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                        <option value="">All</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?php echo (int) $wh['id']; ?>" <?php echo (isset($filters['to_warehouse']) && (int) $filters['to_warehouse'] === (int) $wh['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($wh['address_title']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($userWarehouseId > 0): ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Direction</label>
                        <select name="direction" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                            <option value="" <?php echo ($filters['direction'] ?? '') === '' ? 'selected' : ''; ?>>All (my warehouses)</option>
                            <option value="incoming" <?php echo ($filters['direction'] ?? '') === 'incoming' ? 'selected' : ''; ?>>Incoming to my warehouse</option>
                            <option value="outgoing" <?php echo ($filters['direction'] ?? '') === 'outgoing' ? 'selected' : ''; ?>>Outgoing from my warehouse</option>
                        </select>
                    </div>
                <?php elseif ($isAdmin): ?>
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">Direction (admin)</label>
                        <select name="direction" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                            <option value="" <?php echo ($filters['direction'] ?? '') === '' ? 'selected' : ''; ?>>All</option>
                            <option value="incoming" <?php echo ($filters['direction'] ?? '') === 'incoming' ? 'selected' : ''; ?>>Incoming only</option>
                            <option value="outgoing" <?php echo ($filters['direction'] ?? '') === 'outgoing' ? 'selected' : ''; ?>>Outgoing only</option>
                        </select>
                    </div>
                <?php endif; ?>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Receipt state</label>
                    <select name="receipt_state" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                        <option value="any" <?php echo ($filters['receipt_state'] ?? 'any') === 'any' ? 'selected' : ''; ?>>Any in transit</option>
                        <option value="none" <?php echo ($filters['receipt_state'] ?? '') === 'none' ? 'selected' : ''; ?>>Not received yet</option>
                        <option value="partial" <?php echo ($filters['receipt_state'] ?? '') === 'partial' ? 'selected' : ''; ?>>Partially received</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dispatched from</label>
                    <input type="date" name="dispatched_from" value="<?php echo htmlspecialchars((string) ($filters['dispatched_from'] ?? '')); ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Dispatched to</label>
                    <input type="date" name="dispatched_to" value="<?php echo htmlspecialchars((string) ($filters['dispatched_to'] ?? '')); ?>"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500 transition">
                </div>
                <div class="flex items-end">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer pb-2.5">
                        <input type="checkbox" name="aged_only" value="1" <?php echo (int) ($filters['age_min_days'] ?? 0) > 0 ? 'checked' : ''; ?>
                            class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                        <span>Aged 14+ days only</span>
                    </label>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <a href="?page=products&action=in_transit&view=<?php echo urlencode($view); ?>" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </details>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <?php if ($view === 'item'): ?>
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <th scope="col" class="w-10 px-3 py-3.5"></th>
                            <th scope="col" class="px-4 py-3.5 min-w-[8rem]">Item</th>
                            <th scope="col" class="px-4 py-3.5 min-w-[10rem]">Product</th>
                            <th scope="col" class="px-4 py-3.5 whitespace-nowrap">In transit</th>
                            <th scope="col" class="px-4 py-3.5 min-w-[8rem]">Sent / Received</th>
                            <th scope="col" class="px-4 py-3.5 min-w-[10rem]">Route(s)</th>
                            <th scope="col" class="px-4 py-3.5 whitespace-nowrap">Oldest dispatch</th>
                            <th scope="col" class="w-0 px-2 py-3.5 text-center"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="px-5 py-16 text-center">
                                    <div class="mx-auto flex max-w-sm flex-col items-center">
                                        <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-sky-50 text-sky-400 text-xl mb-4">
                                            <i class="fas fa-truck" aria-hidden="true"></i>
                                        </span>
                                        <p class="text-base font-medium text-gray-900">No in-transit items match</p>
                                        <p class="mt-1 text-sm text-gray-500">Try adjusting filters or check transfer history for completed moves.</p>
                                        <a href="?page=products&action=stock_transfer" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-sky-700 hover:text-sky-800">
                                            <i class="fas fa-history" aria-hidden="true"></i>
                                            Stock transfer history
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $row): ?>
                                <?php
                                    $itemKey = (string) ($row['item_key'] ?? '');
                                    $itemCode = trim((string) ($row['item_code'] ?? ''));
                                    $sku = trim((string) ($row['sku'] ?? ''));
                                    $label = $itemCode !== '' ? $itemCode : ($sku !== '' ? $sku : '—');
                                    $pending = (int) ($row['pending_qty'] ?? 0);
                                    $sent = (int) ($row['total_sent'] ?? 0);
                                    $received = (int) ($row['total_received'] ?? 0);
                                    $pct = $sent > 0 ? min(100, (int) round(($received / $sent) * 100)) : 0;
                                    $days = (int) ($row['days_in_transit'] ?? 0);
                                    $routes = $row['route_preview'] ?? [];
                                    $routeCount = (int) ($row['route_count'] ?? 0);
                                    $transfers = $breakdown[$itemKey] ?? [];
                                ?>
                                <tr class="hover:bg-sky-50/40 transition-colors it-item-row" data-item-key="<?php echo htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8'); ?>">
                                    <td class="px-3 py-4 align-top">
                                        <?php if ($transfers !== []): ?>
                                            <button type="button"
                                                class="it-expand-btn inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50"
                                                aria-expanded="false"
                                                aria-label="Show transfers for this item">
                                                <i class="fas fa-chevron-right text-xs transition-transform it-expand-icon" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="font-mono text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($label); ?></div>
                                        <?php if ($sku !== '' && $sku !== $itemCode): ?>
                                            <div class="text-xs text-gray-500 mt-0.5">SKU <?php echo htmlspecialchars($sku); ?></div>
                                        <?php endif; ?>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo number_format((int) ($row['transfer_count'] ?? 0)); ?> transfer<?php echo (int) ($row['transfer_count'] ?? 0) === 1 ? '' : 's'; ?></div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700 max-w-[14rem]">
                                        <span class="line-clamp-2" title="<?php echo htmlspecialchars((string) ($row['product_title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars(trim((string) ($row['product_title'] ?? '')) ?: '—'); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="text-lg font-bold tabular-nums text-sky-800"><?php echo number_format($pending); ?></span>
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm">
                                        <div class="text-gray-700 tabular-nums">
                                            <?php echo number_format($sent); ?> sent · <?php echo number_format($received); ?> received
                                        </div>
                                        <div class="mt-2 h-1.5 w-full max-w-[8rem] rounded-full bg-gray-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-1"><?php echo $pct; ?>% received</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm text-gray-700">
                                        <?php foreach ($routes as $route): ?>
                                            <div class="text-xs leading-snug"><?php echo htmlspecialchars($route); ?></div>
                                        <?php endforeach; ?>
                                        <?php if ($routeCount > count($routes)): ?>
                                            <div class="text-xs text-gray-400 mt-0.5">+<?php echo number_format($routeCount - count($routes)); ?> more route<?php echo ($routeCount - count($routes)) === 1 ? '' : 's'; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-4 align-top text-sm whitespace-nowrap">
                                        <div><?php echo $formatDate(isset($row['oldest_dispatch']) ? (string) $row['oldest_dispatch'] : null); ?></div>
                                        <?php if ($days > 0): ?>
                                            <div class="text-xs mt-0.5 <?php echo $agedClass($days); ?>"><?php echo $days; ?> day<?php echo $days === 1 ? '' : 's'; ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="w-0 px-2 py-4 align-middle text-center whitespace-nowrap">
                                        <?php if (!empty($transfers[0]['transfer_id'])): ?>
                                            <a href="?page=products&action=stock_transfer_items&transfer_id=<?php echo urlencode((string) $transfers[0]['transfer_id']); ?>"
                                                target="_blank" rel="noopener noreferrer"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-sky-700 hover:bg-gray-50"
                                                title="View transfer items" aria-label="View transfer items">
                                                <i class="fas fa-external-link-alt text-xs" aria-hidden="true"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php if ($transfers !== []): ?>
                                    <tr class="it-detail-row hidden bg-slate-50/80" data-item-key="<?php echo htmlspecialchars($itemKey, ENT_QUOTES, 'UTF-8'); ?>">
                                        <td colspan="8" class="px-4 py-4">
                                            <div class="rounded-xl border border-gray-200 bg-white overflow-hidden">
                                                <table class="min-w-full text-left text-sm">
                                                    <thead>
                                                        <tr class="bg-gray-50 text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                            <th class="px-4 py-2">Transfer order</th>
                                                            <th class="px-4 py-2">Route</th>
                                                            <th class="px-4 py-2">Dispatch</th>
                                                            <th class="px-4 py-2 text-right">Pending</th>
                                                            <th class="px-4 py-2 text-right">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="divide-y divide-gray-100">
                                                        <?php foreach ($transfers as $tr): ?>
                                                            <?php
                                                                $trDays = (int) ($tr['days_in_transit'] ?? 0);
                                                                $canReceive = $userWarehouseId > 0 && (int) ($tr['to_warehouse'] ?? 0) === $userWarehouseId;
                                                            ?>
                                                            <tr>
                                                                <td class="px-4 py-2.5 font-mono font-semibold text-gray-900"><?php echo htmlspecialchars((string) ($tr['transfer_order_no'] ?? '')); ?></td>
                                                                <td class="px-4 py-2.5 text-gray-700">
                                                                    <?php echo htmlspecialchars((string) ($tr['source_name'] ?? '—')); ?>
                                                                    <span class="text-gray-300 mx-1">→</span>
                                                                    <?php echo htmlspecialchars((string) ($tr['dest_name'] ?? '—')); ?>
                                                                </td>
                                                                <td class="px-4 py-2.5 whitespace-nowrap">
                                                                    <?php echo $formatDate(isset($tr['dispatch_date']) ? (string) $tr['dispatch_date'] : null); ?>
                                                                    <?php if ($trDays > 0): ?>
                                                                        <span class="text-xs ml-1 <?php echo $agedClass($trDays); ?>">(<?php echo $trDays; ?>d)</span>
                                                                    <?php endif; ?>
                                                                </td>
                                                                <td class="px-4 py-2.5 text-right tabular-nums font-semibold text-sky-800"><?php echo number_format((int) ($tr['pending_qty'] ?? 0)); ?></td>
                                                                <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                                                    <a href="?page=products&action=stock_transfer_items&transfer_id=<?php echo urlencode((string) ($tr['transfer_id'] ?? '')); ?>"
                                                                        class="text-xs font-medium text-gray-700 hover:text-gray-900 mr-3">View</a>
                                                                    <?php if ($canReceive || $isAdmin): ?>
                                                                        <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo urlencode((string) ($tr['transfer_id'] ?? '')); ?>"
                                                                            class="inline-flex items-center gap-1 rounded-lg bg-orange-500 px-2.5 py-1 text-xs font-semibold text-white hover:bg-orange-600">
                                                                            <i class="fas fa-clipboard-check text-[10px]" aria-hidden="true"></i>
                                                                            Receive
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <table class="min-w-full text-left">
                    <thead>
                        <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Dispatch</th>
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Order</th>
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Receipt</th>
                            <th scope="col" class="px-5 py-3.5 min-w-[8rem]">Route</th>
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Pending</th>
                            <th scope="col" class="px-5 py-3.5 min-w-[8rem]">Progress</th>
                            <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Age</th>
                            <th scope="col" class="w-0 px-2 py-3.5 text-center"><span class="sr-only">Actions</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($records)): ?>
                            <tr>
                                <td colspan="8" class="px-5 py-16 text-center text-sm text-gray-500">No in-transit transfers match your filters.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($records as $transfer): ?>
                                <?php
                                    $sent = (int) ($transfer['sent_qty'] ?? 0);
                                    $received = (int) ($transfer['received_qty'] ?? 0);
                                    $pending = (int) ($transfer['pending_qty'] ?? 0);
                                    $pct = (int) ($transfer['received_percent'] ?? 0);
                                    $days = (int) ($transfer['days_in_transit'] ?? 0);
                                    $receiptStatus = (string) ($transfer['receipt_status'] ?? 'none');
                                    $canReceive = $userWarehouseId > 0 && (int) ($transfer['to_warehouse'] ?? 0) === $userWarehouseId;
                                ?>
                                <tr class="hover:bg-sky-50/40 transition-colors">
                                    <td class="px-5 py-4 align-top text-sm text-gray-700 whitespace-nowrap">
                                        <?php echo $formatDate(isset($transfer['dispatch_date']) ? (string) $transfer['dispatch_date'] : null); ?>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <span class="font-mono text-sm font-semibold text-gray-900"><?php echo htmlspecialchars((string) ($transfer['transfer_order_no'] ?? '')); ?></span>
                                        <div class="text-xs text-gray-500 mt-1"><?php echo number_format((int) ($transfer['pending_line_count'] ?? 0)); ?> pending line<?php echo (int) ($transfer['pending_line_count'] ?? 0) === 1 ? '' : 's'; ?></div>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <?php if ($receiptStatus === 'partial'): ?>
                                            <span class="inline-flex items-center rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900 ring-1 ring-inset ring-amber-600/25">Partial</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-0.5 text-xs font-medium text-sky-800 ring-1 ring-inset ring-sky-600/20">Not received</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm text-gray-700">
                                        <div class="flex flex-col gap-1">
                                            <span class="inline-flex items-start gap-1.5">
                                                <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                                <?php echo htmlspecialchars((string) ($transfer['source_name'] ?? '—')); ?>
                                            </span>
                                            <span class="inline-flex items-start gap-1.5">
                                                <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                                <?php echo htmlspecialchars((string) ($transfer['dest_name'] ?? '—')); ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 align-top">
                                        <div class="text-lg font-bold tabular-nums text-sky-800"><?php echo number_format($pending); ?></div>
                                        <div class="text-xs text-gray-500 tabular-nums"><?php echo number_format($received); ?> / <?php echo number_format($sent); ?> received</div>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm min-w-[7rem]">
                                        <div class="h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
                                            <div class="h-full rounded-full bg-emerald-500" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                        <div class="text-[11px] text-gray-500 mt-1"><?php echo $pct; ?>%</div>
                                    </td>
                                    <td class="px-5 py-4 align-top text-sm whitespace-nowrap <?php echo $agedClass($days); ?>">
                                        <?php echo $days > 0 ? $days . ' day' . ($days === 1 ? '' : 's') : '—'; ?>
                                    </td>
                                    <td class="w-0 px-2 py-4 align-middle text-center whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1.5">
                                            <a href="?page=products&action=stock_transfer_items&transfer_id=<?php echo urlencode((string) ($transfer['id'] ?? '')); ?>"
                                                target="_blank" rel="noopener noreferrer"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-sky-700 hover:bg-gray-50"
                                                title="View items" aria-label="View items">
                                                <i class="fas fa-list-ul text-xs" aria-hidden="true"></i>
                                            </a>
                                            <?php if ($canReceive || $isAdmin): ?>
                                                <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo urlencode((string) ($transfer['id'] ?? '')); ?>"
                                                    class="inline-flex h-7 w-7 items-center justify-center rounded bg-orange-500 text-white hover:bg-orange-600"
                                                    title="Record GRN" aria-label="Record GRN">
                                                    <i class="fas fa-clipboard-check text-xs" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                            <a href="?page=stock_transfer_grns&action=list&transfer_id=<?php echo urlencode((string) ($transfer['id'] ?? '')); ?>"
                                                class="inline-flex h-7 w-7 items-center justify-center rounded bg-emerald-600 text-white hover:bg-emerald-700"
                                                title="View GRNs" aria-label="View GRNs">
                                                <i class="fas fa-clipboard-list text-xs" aria-hidden="true"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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

<script>
(function () {
    document.querySelectorAll('.it-expand-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var row = btn.closest('.it-item-row');
            if (!row) return;
            var key = row.getAttribute('data-item-key');
            var detail = document.querySelector('.it-detail-row[data-item-key="' + CSS.escape(key) + '"]');
            if (!detail) return;
            var open = detail.classList.toggle('hidden') === false;
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
            var icon = btn.querySelector('.it-expand-icon');
            if (icon) {
                icon.style.transform = open ? 'rotate(90deg)' : '';
            }
        });
    });
})();
</script>
