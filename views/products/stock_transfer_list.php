<?php
/** @var array $filters @var array $transfers @var array $users @var array $warehouses @var array|null $flash */
$pageNo = isset($page_no) ? (int) $page_no : 1;
$limit = isset($limit) ? (int) $limit : 50;
$totalRecords = isset($total_records) ? (int) $total_records : 0;
$filters = $filters ?? [];
$transfers = $transfers ?? [];
$filtersPanelOpen =
    trim((string) ($filters['transfer_order_no'] ?? '')) !== ''
    || trim((string) ($filters['dispatch_date'] ?? '')) !== ''
    || (isset($filters['requested_by']) && (int) $filters['requested_by'] > 0)
    || (isset($filters['dispatch_by']) && (int) $filters['dispatch_by'] > 0)
    || (isset($filters['from_warehouse']) && (int) $filters['from_warehouse'] > 0)
    || (isset($filters['to_warehouse']) && (int) $filters['to_warehouse'] > 0)
    || trim((string) ($filters['item_number'] ?? '')) !== '';
$users = $users ?? [];
$warehouses = $warehouses ?? [];
$flash = $flash ?? null;
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

/** Title-style label: underscores → spaces, each word capitalized (e.g. in_transit → In Transit). */
$formatStatusLabel = static function (string $status): string {
    $s = trim(str_replace('_', ' ', $status));
    if ($s === '') {
        return '';
    }
    $lower = function_exists('mb_strtolower') ? mb_strtolower($s, 'UTF-8') : strtolower($s);
    return function_exists('mb_convert_case')
        ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8')
        : ucwords($lower);
};
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Page header -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-exchange-alt text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Warehouse · Stock transfers</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Stock transfer <span class="text-amber-800">history</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Review moves between locations, edit open transfers, and record GRNs against transfer orders—all in one place.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-2">
                <a href="?page=products&action=in_transit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3.5 rounded-xl border border-sky-200 bg-white text-sky-800 text-sm font-semibold shadow-sm hover:bg-sky-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-truck-loading text-xs opacity-95" aria-hidden="true"></i>
                    In transit
                </a>
                <a href="?page=products&action=transfer_stock_bulk"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50/50 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Create stock transfer
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string)($flash['message'] ?? '')) !== ''): ?>
        <?php
            $flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
            $flashRing = $flashType === 'success'
                ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900'
                : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?php echo $flashRing; ?>" role="status">
            <?php echo htmlspecialchars((string)$flash['message']); ?>
        </div>
    <?php endif; ?>

    <style>
        #st-transfer-filters > summary { list-style: none; }
        #st-transfer-filters > summary::-webkit-details-marker { display: none; }
        #st-transfer-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
        #st-transfer-filters:not([open]) .stf-label-open { display: none; }
        #st-transfer-filters[open] .stf-label-closed { display: none; }
        #st-transfer-filters[open] .stf-chevron { transform: rotate(180deg); }
    </style>
    <!-- Filters (collapsed by default; opens automatically when any filter is applied) -->
    <details id="st-transfer-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?php echo $filtersPanelOpen ? 'open' : ''; ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Narrow the list by order, dates, people, routes, or item.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
                <span class="stf-label-closed">Show</span>
                <span class="stf-label-open">Hide</span>
                <i class="stf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>
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
    </details>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Dispatch</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Order</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[10rem]">Requested / Dispatched by</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[8rem]">Route</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[12rem]">Line items</th>
                        <th scope="col" class="w-0 px-2 py-3.5 text-center"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($transfers)): ?>
                        <tr>
                            <td colspan="7" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No transfers match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try adjusting filters or create a new stock transfer.</p>
                                    <a href="?page=products&action=transfer_stock_bulk" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
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
                                $statusNorm = strtolower(str_replace('_', ' ', $rawStatus));
                                $statusLabel = $rawStatus !== '' ? $formatStatusLabel($rawStatus) : '—';
                                $statusRing = $statusClass($rawStatus);
                                $canReplayGrn = in_array($statusNorm, ['received', 'completed', 'complete'], true)
                                    && (int)($transfer['grn_count'] ?? 0) > 0;
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 align-top text-sm text-gray-700 whitespace-nowrap">
                                    <?php echo !empty($transfer['dispatch_date']) ? htmlspecialchars(date('j M Y', strtotime($transfer['dispatch_date']))) : '—'; ?>
                                </td>
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
                                <td class="px-5 py-4 align-top text-sm">
                                    <div class="flex flex-col gap-1.5 text-gray-700">
                                        <span class="inline-flex flex-col gap-0.5">
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Requested</span>
                                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars(trim((string)($transfer['requested_by_name'] ?? '')) ?: '—'); ?></span>
                                        </span>
                                        <span class="inline-flex flex-col gap-0.5">
                                            <span class="text-[11px] font-semibold uppercase tracking-wide text-gray-500">Dispatched by</span>
                                            <span class="font-bold text-gray-900"><?php echo htmlspecialchars(trim((string)($transfer['dispatch_by_name'] ?? '')) ?: '—'); ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-sm">
                                    <?php
                                        $sourceLabel = trim((string)($transfer['source_name'] ?? ''));
                                        $destLabel = trim((string)($transfer['dest_name'] ?? ''));
                                        $sourceTitle = 'Source' . ($sourceLabel !== '' && $sourceLabel !== '—' ? ': ' . $sourceLabel : '');
                                        $destTitle = 'Destination' . ($destLabel !== '' && $destLabel !== '—' ? ': ' . $destLabel : '');
                                    ?>
                                    <div class="flex flex-col gap-1.5 text-gray-700">
                                        <span class="inline-flex items-start gap-1.5 cursor-help" title="<?php echo htmlspecialchars($sourceTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-arrow-up text-emerald-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($transfer['source_name'] ?? '—'); ?></span>
                                        </span>
                                        <span class="inline-flex items-start gap-1.5 cursor-help" title="<?php echo htmlspecialchars($destTitle, ENT_QUOTES, 'UTF-8'); ?>">
                                            <i class="fas fa-arrow-down text-sky-600 mt-0.5 text-xs" aria-hidden="true"></i>
                                            <span><?php echo htmlspecialchars($transfer['dest_name'] ?? '—'); ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-800">
                                    <?php
                                        $lineCount = (int)($transfer['line_item_count'] ?? 0);
                                        $lineTotalQty = (int)($transfer['line_total_qty'] ?? 0);
                                        $preview = $transfer['line_preview_skus'] ?? [];
                                    ?>
                                    <?php if ($lineCount > 0): ?>
                                        <div class="max-w-[14rem] text-sm leading-snug">
                                            <div class="text-gray-900 tabular-nums">
                                                <?php echo number_format($lineCount); ?> line<?php echo $lineCount === 1 ? '' : 's'; ?>
                                                <span class="text-gray-300 mx-1">·</span>
                                                <?php echo number_format($lineTotalQty); ?> qty
                                            </div>
                                            <?php
                                                $previewLine = '';
                                                if (!empty($preview)) {
                                                    $previewLine = implode(', ', array_map('htmlspecialchars', $preview));
                                                    if ($lineCount > count($preview)) {
                                                        $previewLine .= '<span class="text-gray-400">, +' . number_format($lineCount - count($preview)) . '</span>';
                                                    }
                                                }
                                            ?>
                                            <?php if ($previewLine !== ''): ?>
                                                <p class="mt-0.5 text-xs text-gray-600 break-words"><?php echo $previewLine; ?></p>
                                            <?php endif; ?>
                                            <a href="?page=products&action=stock_transfer_items&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                                target="_blank" rel="noopener noreferrer"
                                                class="mt-1 inline-block text-xs font-medium text-amber-800/90 hover:text-amber-950 hover:underline underline-offset-2 decoration-amber-800/30">
                                                View items
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-gray-400">No items</span>
                                    <?php endif; ?>
                                </td>
                                <td class="w-0 px-2 py-4 align-middle text-center whitespace-nowrap">
                                    <div class="flex items-center justify-center gap-1.5">
                                        <a href="?page=products&action=transfer_stock_bulk&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-blue-600 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
                                            title="Edit transfer"
                                            aria-label="Edit transfer">
                                            <i class="fas fa-edit text-xs" aria-hidden="true"></i>
                                        </a>
                                        <?php if ((int)($transfer['grn_count'] ?? 0) === 0): ?>
                                            <button type="button"
                                                class="st-transfer-delete-open inline-flex h-7 w-7 items-center justify-center rounded border border-red-200 bg-white text-red-600 hover:bg-red-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-400 focus-visible:ring-offset-1"
                                                title="Delete transfer (no GRN yet)"
                                                aria-label="Delete transfer"
                                                data-st-transfer-id="<?php echo (int)$transfer['id']; ?>"
                                                data-st-order-no="<?php echo htmlspecialchars((string)($transfer['transfer_order_no'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded bg-orange-500 text-white hover:bg-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
                                            title="Add GRN"
                                            aria-label="Add GRN">
                                            <i class="fas fa-clipboard-check text-xs" aria-hidden="true"></i>
                                        </a>
                                        <a href="?page=stock_transfer_grns&action=list&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded bg-emerald-600 text-white hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
                                            title="View GRNs"
                                            aria-label="View GRNs">
                                            <i class="fas fa-clipboard-list text-xs" aria-hidden="true"></i>
                                        </a>
                                        <?php if ($canReplayGrn): ?>
                                            <button type="button"
                                                class="st-transfer-replay-grn-btn inline-flex h-7 w-7 items-center justify-center rounded border border-violet-200 bg-violet-50 text-violet-700 hover:bg-violet-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-violet-500 focus-visible:ring-offset-1 disabled:opacity-50 disabled:pointer-events-none"
                                                title="Replay transfer stock (source OUT + destination GRN IN)"
                                                aria-label="Replay transfer stock (source OUT and destination GRN IN)"
                                                data-transfer-id="<?php echo (int)$transfer['id']; ?>"
                                                data-order-no="<?php echo htmlspecialchars((string)($transfer['transfer_order_no'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                data-grn-count="<?php echo (int)($transfer['grn_count'] ?? 0); ?>">
                                                <i class="fas fa-redo text-xs" aria-hidden="true"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="?page=products&action=stock_transfer_print&transfer_id=<?php echo urlencode($transfer['id']); ?>"
                                            target="_blank" rel="noopener noreferrer"
                                            class="inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 bg-white text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-1"
                                            title="Print transfer order"
                                            aria-label="Print transfer order">
                                            <i class="fas fa-print text-xs" aria-hidden="true"></i>
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

<div
  id="stTransferReplayProgressModal"
  class="fixed inset-0 hidden z-[210] items-center justify-center bg-black/50 p-4"
  role="dialog"
  aria-modal="true"
  aria-labelledby="stTransferReplayProgressTitle">
  <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl">
    <div class="flex items-start gap-3">
      <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-violet-100 text-violet-700">
        <i id="stTransferReplayProgressIcon" class="fas fa-redo fa-spin text-sm" aria-hidden="true"></i>
      </span>
      <div class="min-w-0 flex-1">
        <h3 id="stTransferReplayProgressTitle" class="text-base font-semibold text-gray-900">Replaying transfer stock</h3>
        <p id="stTransferReplayProgressText" class="mt-1 text-sm text-gray-600">Preparing…</p>
      </div>
    </div>
    <div class="mt-5">
      <div class="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
        <div id="stTransferReplayProgressBar" class="h-full rounded-full bg-violet-500 transition-all duration-300" style="width: 0%"></div>
      </div>
      <div class="mt-3 flex flex-wrap items-center justify-between gap-2 text-xs text-gray-500">
        <span id="stTransferReplayProgressBatch">Batch 0 of 0</span>
        <span id="stTransferReplayProgressStats">Replayed: 0 · Failed: 0</span>
      </div>
      <p id="stTransferReplayProgressHint" class="mt-3 text-xs text-gray-500">
        Rebuilding TRANSFER_IN movements and vp_stock in small batches. Please keep this page open.
      </p>
    </div>
  </div>
</div>

<form id="stTransferDeleteForm" method="post" action="?page=products&action=stock_transfer_delete" class="hidden" aria-hidden="true">
    <input type="hidden" name="transfer_id" id="stTransferDeleteTransferIdInput" value="">
</form>

<div id="stTransferDeleteModal" class="fixed inset-0 z-[200] hidden" role="dialog" aria-modal="true" aria-labelledby="stTransferDeleteModalTitle">
    <div class="absolute inset-0 bg-slate-900/55 backdrop-blur-[2px] transition-opacity" data-st-transfer-delete-backdrop tabindex="-1"></div>
    <div class="relative flex min-h-full items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-md rounded-2xl border border-gray-200/80 bg-white shadow-2xl shadow-slate-900/15 ring-1 ring-slate-900/5 overflow-hidden">
            <div class="px-5 pt-5 pb-4 border-b border-gray-100 bg-gradient-to-br from-red-50/80 to-white">
                <div class="flex items-start gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600">
                        <i class="fas fa-exclamation-triangle text-lg" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0 pt-0.5">
                        <h3 id="stTransferDeleteModalTitle" class="text-base font-semibold text-gray-900 leading-snug">Delete this stock transfer?</h3>
                        <p class="text-sm text-gray-600 mt-1.5 leading-relaxed">This removes the transfer and all its line items. Outbound quantities will be restored at the source warehouse. This cannot be undone.</p>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 bg-white">
                <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-500 mb-3">You are about to delete</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="rounded-xl border-2 border-slate-200 bg-slate-50/90 px-4 py-3.5 ring-1 ring-slate-900/[0.04] shadow-sm">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-slate-500 mb-1.5">Transfer ID</div>
                        <div id="stTransferDeleteMetaId" class="text-2xl sm:text-[1.65rem] font-bold tabular-nums tracking-tight text-slate-900 font-mono leading-none">—</div>
                    </div>
                    <div class="rounded-xl border-2 border-amber-300/80 bg-gradient-to-br from-amber-50 to-amber-100/50 px-4 py-3.5 ring-1 ring-amber-900/10 shadow-sm">
                        <div class="text-[10px] font-bold uppercase tracking-widest text-amber-900/70 mb-1.5">Order no.</div>
                        <div id="stTransferDeleteMetaOrder" class="text-lg sm:text-xl font-bold text-amber-950 font-mono break-all leading-snug">—</div>
                    </div>
                </div>
            </div>
            <div class="px-5 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-2 sm:gap-3 bg-gray-50/80 border-t border-gray-100">
                <button type="button" data-st-transfer-delete-cancel
                    class="w-full sm:w-auto inline-flex justify-center items-center px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition">
                    Cancel
                </button>
                <button type="button" data-st-transfer-delete-confirm
                    class="w-full sm:w-auto inline-flex justify-center items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-b from-red-600 to-red-700 text-white text-sm font-semibold shadow-md shadow-red-900/20 hover:from-red-700 hover:to-red-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500 focus-visible:ring-offset-2 transition">
                    <i class="fas fa-trash-alt text-xs opacity-90" aria-hidden="true"></i>
                    Delete transfer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('stTransferDeleteModal');
    var form = document.getElementById('stTransferDeleteForm');
    var input = document.getElementById('stTransferDeleteTransferIdInput');
    var metaIdEl = document.getElementById('stTransferDeleteMetaId');
    var metaOrderEl = document.getElementById('stTransferDeleteMetaOrder');
    var pendingId = '';

    function openStTransferDeleteModal(id, orderNo) {
        pendingId = id ? String(id) : '';
        if (input) input.value = pendingId;
        if (metaIdEl) metaIdEl.textContent = pendingId || '—';
        if (metaOrderEl) metaOrderEl.textContent = orderNo && String(orderNo).trim() !== '' ? String(orderNo).trim() : '—';
        if (!modal) return;
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        var c = modal.querySelector('[data-st-transfer-delete-confirm]');
        if (c) c.focus();
    }

    function closeStTransferDeleteModal() {
        pendingId = '';
        if (input) input.value = '';
        if (modal) modal.classList.add('hidden');
        document.body.style.overflow = '';
    }

    document.querySelectorAll('.st-transfer-delete-open').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openStTransferDeleteModal(
                btn.getAttribute('data-st-transfer-id') || '',
                btn.getAttribute('data-st-order-no') || ''
            );
        });
    });

    if (modal) {
        var cancelBtn = modal.querySelector('[data-st-transfer-delete-cancel]');
        var backdrop = modal.querySelector('[data-st-transfer-delete-backdrop]');
        var confirmBtn = modal.querySelector('[data-st-transfer-delete-confirm]');
        if (cancelBtn) cancelBtn.addEventListener('click', closeStTransferDeleteModal);
        if (backdrop) backdrop.addEventListener('click', closeStTransferDeleteModal);
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (form && input && input.value) {
                    form.submit();
                }
            });
        }
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                closeStTransferDeleteModal();
            }
        });
    }
})();

(function () {
    var BATCH_SIZE = 5;
    var modal = document.getElementById('stTransferReplayProgressModal');
    var textEl = document.getElementById('stTransferReplayProgressText');
    var barEl = document.getElementById('stTransferReplayProgressBar');
    var batchEl = document.getElementById('stTransferReplayProgressBatch');
    var statsEl = document.getElementById('stTransferReplayProgressStats');
    var hintEl = document.getElementById('stTransferReplayProgressHint');
    var iconEl = document.getElementById('stTransferReplayProgressIcon');

    function showReplayModal() {
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.style.overflow = 'hidden';
    }

    function hideReplayModal() {
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }

    function updateReplayProgress(state) {
        var total = Math.max(0, Number(state.total || 0));
        var processed = Math.max(0, Number(state.processed || 0));
        var replayed = Math.max(0, Number(state.replayed || 0));
        var failed = Math.max(0, Number(state.failed || 0));
        var batchNo = Math.max(0, Number(state.batchNo || 0));
        var batchTotal = Math.max(0, Number(state.batchTotal || 0));
        var percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : 0;
        var done = !!state.done;

        if (textEl) {
            textEl.textContent = 'Processed ' + processed + ' of ' + total + ' GRN line(s)';
        }
        if (barEl) barEl.style.width = percent + '%';
        if (batchEl) {
            batchEl.textContent = batchTotal > 0
                ? ('Batch ' + batchNo + ' of ' + batchTotal + ' · ' + BATCH_SIZE + ' lines per batch')
                : 'Preparing batches…';
        }
        if (statsEl) {
            var stats = 'Replayed: ' + replayed + ' · Failed: ' + failed;
            if (state.transferOutReplayed > 0) {
                stats = 'Source OUT: ' + state.transferOutReplayed + ' · ' + stats;
            }
            statsEl.textContent = stats;
        }
        if (hintEl && state.hint) hintEl.textContent = state.hint;
        if (iconEl) {
            iconEl.classList.toggle('fa-spin', !done);
            iconEl.classList.toggle('fa-redo', !done);
            iconEl.classList.toggle('fa-check', done);
        }
    }

    async function fetchReplayBatch(transferId, offset, phase) {
        var res = await fetch('index.php?page=products&action=stock_transfer_replay_grn', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                transfer_id: transferId,
                phase: phase || 'grn',
                offset: offset,
                batch_size: BATCH_SIZE,
            }),
        });
        var rawText = await res.text();
        var data = null;
        try {
            data = rawText ? JSON.parse(rawText) : null;
        } catch (e) {
            data = null;
        }
        if (!res.ok) {
            throw new Error((data && data.message) ? data.message : ('Replay failed with HTTP ' + res.status));
        }
        if (!data) {
            throw new Error('Invalid replay response.');
        }
        return data;
    }

    async function runTransferGrnReplay(btn) {
        var transferId = parseInt(btn.getAttribute('data-transfer-id') || '0', 10);
        var orderNo = btn.getAttribute('data-order-no') || ('#' + transferId);
        var grnCount = parseInt(btn.getAttribute('data-grn-count') || '0', 10);
        if (transferId <= 0 || grnCount <= 0) return;

        var confirmed = window.confirm(
            'Replay stock for transfer ' + orderNo + '?\n\n'
            + 'Step 1: TRANSFER_OUT — deduct stock from the source warehouse.\n'
            + 'Step 2: GRN — add stock at the destination from the existing '
            + grnCount + ' GRN line(s).\n\n'
            + 'GRN records themselves are not deleted.'
        );
        if (!confirmed) return;

        btn.disabled = true;
        showReplayModal();

        var offset = 0;
        var totalGrns = grnCount;
        var totalReplayed = 0;
        var totalFailed = 0;
        var transferOutReplayed = 0;
        var batchTotal = Math.max(1, Math.ceil(totalGrns / BATCH_SIZE));
        var batchNo = 0;
        var lastMessage = '';

        updateReplayProgress({
            total: totalGrns,
            processed: 0,
            replayed: 0,
            failed: 0,
            batchNo: 0,
            batchTotal: batchTotal,
            done: false,
            hint: 'Step 1: Replaying TRANSFER_OUT at source for ' + orderNo + '…',
        });

        try {
            updateReplayProgress({
                total: totalGrns,
                processed: 0,
                replayed: 0,
                failed: 0,
                batchNo: 0,
                batchTotal: batchTotal,
                done: false,
                hint: 'Step 1: Deducting stock from source warehouse…',
            });

            var outData = await fetchReplayBatch(transferId, 0, 'transfer_out');
            if (!outData || !outData.success) {
                throw new Error((outData && outData.message) ? outData.message : 'TRANSFER_OUT replay failed.');
            }
            transferOutReplayed = parseInt(outData.transfer_out_replayed || 0, 10);
            if (typeof outData.total_grns === 'number' && outData.total_grns > 0) {
                totalGrns = outData.total_grns;
                batchTotal = Math.max(1, Math.ceil(totalGrns / BATCH_SIZE));
            }
            lastMessage = outData.message || lastMessage;

            updateReplayProgress({
                total: totalGrns,
                processed: 0,
                replayed: 0,
                failed: 0,
                transferOutReplayed: transferOutReplayed,
                batchNo: 0,
                batchTotal: batchTotal,
                done: false,
                hint: 'Step 1 complete. Step 2: Replaying GRN at destination…',
            });

            while (offset < totalGrns) {
                batchNo++;
                updateReplayProgress({
                    total: totalGrns,
                    processed: offset,
                    replayed: totalReplayed,
                    failed: totalFailed,
                    transferOutReplayed: transferOutReplayed,
                    batchNo: batchNo,
                    batchTotal: batchTotal,
                    done: false,
                    hint: 'Step 2: GRN batch ' + batchNo + ' of ' + batchTotal + '…',
                });

                var data = await fetchReplayBatch(transferId, offset, 'grn');
                if (typeof data.total_grns === 'number' && data.total_grns > 0) {
                    totalGrns = data.total_grns;
                    batchTotal = Math.max(1, Math.ceil(totalGrns / BATCH_SIZE));
                }

                totalReplayed += parseInt(data.replayed || 0, 10);
                totalFailed += parseInt(data.failed || 0, 10);
                lastMessage = data.message || lastMessage;

                var processed = parseInt(data.processed_grns || offset, 10);
                if (data.next_offset === null || data.is_complete || processed >= totalGrns) {
                    processed = totalGrns;
                }
                offset = processed;

                updateReplayProgress({
                    total: totalGrns,
                    processed: processed,
                    replayed: totalReplayed,
                    failed: totalFailed,
                    transferOutReplayed: transferOutReplayed,
                    batchNo: batchNo,
                    batchTotal: batchTotal,
                    done: !!data.is_complete,
                    hint: data.is_complete ? 'Replay complete. Reloading…' : ('Batch ' + batchNo + ' complete. Continuing…'),
                });

                if (data.is_complete || data.next_offset === null || processed >= totalGrns) {
                    break;
                }
                if (!data.success && totalFailed > 0) {
                    throw new Error(data.message || 'Transfer stock replay failed in batch ' + batchNo + '.');
                }
            }

            if (totalFailed > 0) {
                window.alert((lastMessage || 'Transfer stock replay completed with errors.') + '\nFailed lines: ' + totalFailed);
            }

            window.setTimeout(function () { window.location.reload(); }, totalFailed > 0 ? 1200 : 600);
        } catch (err) {
            hideReplayModal();
            window.alert(err && err.message ? err.message : 'Transfer stock replay failed.');
            btn.disabled = false;
        }
    }

    document.querySelectorAll('.st-transfer-replay-grn-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            runTransferGrnReplay(btn);
        });
    });
})();
</script>
