<?php
$customerId = (int)($customer_id ?? $customer['id'] ?? 0);
$customerName = trim((string)($customer['name'] ?? 'N/A'));
$customerEmail = trim((string)($customer['email'] ?? ''));
$customerPhone = trim((string)($customer['phone'] ?? ''));

$pageNo = max(1, (int)($page_no ?? 1));
$limitVal = (int)($limit ?? 20);
$limitVal = in_array($limitVal, [10, 20, 50, 100], true) ? $limitVal : 20;
$tab = $tab ?? 'orders';
$viewMode = $view_mode ?? 'cards';
$searchVal = (string)($search ?? '');
$sortVal = (string)($sort ?? 'new_to_old');
$statusGroup = (string)($status_group ?? 'all');
$paymentType = (string)($payment_type ?? 'all');
$dateFrom = (string)($date_from ?? '');
$dateTo = (string)($date_to ?? '');
$billing = is_array($billing ?? null) ? $billing : [];
$shipping = is_array($shipping ?? null) ? $shipping : [];
$orderDates = is_array($orderDates ?? null) ? $orderDates : [];
$insights = is_array($insights ?? null) ? $insights : [];
$invoices = is_array($invoices ?? null) ? $invoices : [];
$dispatches = is_array($dispatches ?? null) ? $dispatches : [];
$activityLog = is_array($activityLog ?? null) ? $activityLog : [];
$orders = is_array($orders ?? null) ? $orders : [];
$totalRecords = (int)($total_records ?? 0);
$totalPages = max(1, (int)($total_pages ?? 1));
$statusCounts = is_array($statusCounts ?? null) ? $statusCounts : [];

$initials = '';
if ($customerName !== '' && $customerName !== 'N/A') {
    foreach (preg_split('/\s+/u', $customerName) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    $initials = substr($initials, 0, 2);
}

$fmtDate = static function ($dt, string $fallback = '—'): string {
    if (empty($dt)) {
        return $fallback;
    }
    $ts = strtotime((string)$dt);
    return $ts ? date('d M Y', $ts) : $fallback;
};

$fmtMoney = static function ($amount): string {
    return '₹' . number_format((float)$amount, 2);
};

$formatAddress = static function (array $addr, bool $shipping = false): string {
    if ($shipping) {
        $parts = array_filter([
            trim((string)($addr['saddress1'] ?? '')),
            trim((string)($addr['saddress2'] ?? '')),
            trim((string)($addr['scity'] ?? '')),
            trim((string)($addr['sstate'] ?? '')),
            trim((string)($addr['szip'] ?? '')),
            trim((string)($addr['scountry'] ?? '')),
        ]);
    } else {
        $parts = array_filter([
            trim((string)($addr['address1'] ?? '')),
            trim((string)($addr['address2'] ?? '')),
            trim((string)($addr['city'] ?? '')),
            trim((string)($addr['state'] ?? '')),
            trim((string)($addr['zip'] ?? '')),
            trim((string)($addr['country'] ?? '')),
        ]);
    }
    return $parts !== [] ? implode(', ', $parts) : '—';
};

$buildViewParams = static function (array $overrides = []) use (
    $customerId,
    $pageNo,
    $limitVal,
    $sortVal,
    $tab,
    $viewMode,
    $statusGroup,
    $paymentType,
    $searchVal,
    $dateFrom,
    $dateTo
): array {
    $params = [
        'page' => 'customer',
        'action' => 'view',
        'customer_id' => $customerId,
        'page_no' => $pageNo,
        'limit' => $limitVal,
        'sort' => $sortVal,
        'tab' => $tab,
        'view_mode' => $viewMode,
    ];
    if ($statusGroup !== '' && $statusGroup !== 'all') {
        $params['status_group'] = $statusGroup;
    }
    if ($paymentType !== '' && $paymentType !== 'all') {
        $params['payment_type'] = $paymentType;
    }
    if ($searchVal !== '') {
        $params['search'] = $searchVal;
    }
    if ($dateFrom !== '') {
        $params['date_from'] = $dateFrom;
    }
    if ($dateTo !== '') {
        $params['date_to'] = $dateTo;
    }
    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    return $params;
};

$viewUrl = static function (array $overrides = []) use ($buildViewParams): string {
    return base_url('?' . http_build_query($buildViewParams($overrides)));
};

$exportUrl = static function () use ($buildViewParams): string {
    $params = $buildViewParams(['page_no' => null, 'limit' => null, 'tab' => null, 'view_mode' => null]);
    $params['page'] = 'customer';
    $params['action'] = 'export_orders';
    unset($params['page_no'], $params['limit'], $params['tab'], $params['view_mode']);
    return base_url('?' . http_build_query($params));
};

$statusColors = [
    'pending' => 'bg-amber-100 text-amber-800',
    'shipped' => 'bg-green-100 text-green-800',
    'cancelled' => 'bg-red-100 text-red-800',
    'ready_for_dispatch' => 'bg-yellow-100 text-yellow-800',
    'ready_for_packing' => 'bg-blue-100 text-blue-800',
    'po_pending' => 'bg-blue-100 text-blue-800',
    'po_approved' => 'bg-blue-100 text-blue-800',
    'po_inprogress' => 'bg-blue-100 text-blue-800',
];

$statusChipDefs = [
    'all' => ['label' => 'All', 'count' => $customerOrderCount ?? 0],
    'pending' => ['label' => 'Pending', 'count' => $statusCounts['pending'] ?? 0, 'class' => 'bg-amber-100 text-amber-800 ring-amber-200'],
    'progress' => ['label' => 'In progress', 'count' => $statusCounts['progress'] ?? 0, 'class' => 'bg-blue-100 text-blue-800 ring-blue-200'],
    'completed' => ['label' => 'Shipped', 'count' => $statusCounts['completed'] ?? 0, 'class' => 'bg-green-100 text-green-800 ring-green-200'],
    'cancelled' => ['label' => 'Cancelled', 'count' => $statusCounts['cancelled'] ?? 0, 'class' => 'bg-red-100 text-red-800 ring-red-200'],
];

$tabDefs = [
    'orders' => ['label' => 'Orders', 'count' => $customerOrderCount ?? 0],
    'invoices' => ['label' => 'Invoices', 'count' => count($invoices)],
    'dispatches' => ['label' => 'Dispatches', 'count' => count($dispatches)],
    'activity' => ['label' => 'Activity', 'count' => count($activityLog)],
];

$firstOrderDate = $orderDates['first_order_date'] ?? null;
$lastOrderDate = $orderDates['last_order_date'] ?? null;
$billingAddress = $formatAddress($billing, false);
$shippingAddress = $formatAddress($shipping, true);
$gstin = trim((string)($billing['gstin'] ?? ''));
$hasActiveFilters = $searchVal !== '' || $statusGroup !== 'all' || $paymentType !== 'all' || $dateFrom !== '' || $dateTo !== '';

$slotSize = 10;
$start = max(1, $pageNo - (int)floor($slotSize / 2));
$end = min($totalPages, $start + $slotSize - 1);
if ($end - $start < $slotSize - 1) {
    $start = max(1, $end - $slotSize + 1);
}
?>

<div class="max-w-[1400px] mx-auto px-4 sm:px-6 pt-[10px] pb-10 mr-4 space-y-6">

    <nav class="text-sm text-gray-500">
        <a href="<?= htmlspecialchars(base_url('?page=customer&action=list')) ?>" class="text-[#d97706] hover:underline">Customers</a>
        <span class="mx-2">/</span>
        <span class="text-gray-800 font-medium"><?= htmlspecialchars($customerName) ?></span>
    </nav>

    <!-- Profile header -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
        <div class="flex flex-wrap items-start gap-5">
            <div class="flex items-start gap-3 min-w-0 flex-1">
                <div class="w-14 h-14 rounded-xl bg-orange-100 flex items-center justify-center shrink-0">
                    <span class="text-xl font-bold text-orange-500"><?= htmlspecialchars($initials ?: '?') ?></span>
                </div>
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($customerName) ?></h1>
                        <?php if (!empty($insights['is_repeat_customer'])): ?>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">Repeat customer</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Customer #<?= $customerId ?></p>
                    <div class="flex flex-wrap gap-x-4 gap-y-1 mt-2 text-sm text-gray-600">
                        <?php if ($customerPhone !== ''): ?>
                            <a href="tel:<?= htmlspecialchars(preg_replace('/\s+/', '', $customerPhone)) ?>" class="hover:text-[#d97706]"><?= htmlspecialchars($customerPhone) ?></a>
                        <?php else: ?>
                            <span>—</span>
                        <?php endif; ?>
                        <?php if ($customerEmail !== ''): ?>
                            <a href="mailto:<?= htmlspecialchars($customerEmail) ?>" class="hover:text-[#d97706] truncate"><?= htmlspecialchars($customerEmail) ?></a>
                        <?php endif; ?>
                    </div>
                    <div class="flex flex-wrap gap-4 mt-3 text-xs text-gray-500">
                        <span>First order: <strong class="text-gray-700"><?= $fmtDate($firstOrderDate) ?></strong></span>
                        <span>Last order: <strong class="text-gray-700"><?= $fmtDate($lastOrderDate) ?></strong></span>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 shrink-0">
                <div class="rounded-lg bg-orange-50 px-4 py-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-orange-700/80">Orders</p>
                    <p class="text-lg font-bold text-orange-600 tabular-nums"><?= (int)($customerOrderCount ?? 0) ?></p>
                </div>
                <div class="rounded-lg bg-orange-50 px-4 py-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-orange-700/80">Lifetime</p>
                    <p class="text-lg font-bold text-orange-600 tabular-nums"><?= $fmtMoney($customerTotalSpent ?? 0) ?></p>
                </div>
                <div class="rounded-lg bg-orange-50 px-4 py-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-orange-700/80">Avg order</p>
                    <p class="text-lg font-bold text-orange-600 tabular-nums"><?= $fmtMoney($customerAverageOrderValue ?? 0) ?></p>
                </div>
                <div class="rounded-lg bg-orange-50 px-4 py-3 text-center">
                    <p class="text-[10px] uppercase tracking-wide text-orange-700/80">Open value</p>
                    <p class="text-lg font-bold text-orange-600 tabular-nums"><?= $fmtMoney($insights['open_order_value'] ?? 0) ?></p>
                </div>
            </div>
        </div>
        <div class="flex flex-wrap gap-1.5 mt-4 pt-4 border-t border-gray-100">
            <?php foreach (array_slice($statusChipDefs, 1) as $key => $chip): ?>
                <span class="px-2 py-0.5 rounded text-xs font-medium <?= $chip['class'] ?? 'bg-gray-100 text-gray-800' ?>">
                    <?= htmlspecialchars($chip['label']) ?> <?= (int)$chip['count'] ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Addresses + insights -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Addresses</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Billing</p>
                    <p class="text-gray-700 leading-relaxed"><?= htmlspecialchars($billingAddress) ?></p>
                    <?php if ($gstin !== ''): ?>
                        <p class="mt-2 text-xs text-gray-500">GSTIN: <span class="font-medium text-gray-800"><?= htmlspecialchars($gstin) ?></span></p>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-xs font-medium text-gray-500 mb-1">Shipping</p>
                    <p class="text-gray-700 leading-relaxed"><?= htmlspecialchars($shippingAddress) ?></p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
            <h2 class="text-sm font-semibold text-gray-900 uppercase tracking-wide">Insights</h2>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Distinct orders</dt>
                    <dd class="font-medium text-gray-800 tabular-nums"><?= (int)($insights['distinct_order_count'] ?? 0) ?></dd>
                </div>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Cancellation rate</dt>
                    <dd class="font-medium text-gray-800"><?= number_format((float)($insights['cancellation_rate'] ?? 0), 1) ?>%</dd>
                </div>
                <?php if (!empty($insights['preferred_payment_type'])): ?>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Preferred payment</dt>
                    <dd class="font-medium text-gray-800 uppercase"><?= htmlspecialchars((string)$insights['preferred_payment_type']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($insights['avg_days_between_orders'] !== null): ?>
                <div class="flex justify-between gap-3">
                    <dt class="text-gray-500">Avg days between orders</dt>
                    <dd class="font-medium text-gray-800"><?= number_format((float)$insights['avg_days_between_orders'], 1) ?></dd>
                </div>
                <?php endif; ?>
            </dl>
            <?php if (!empty($insights['top_items'])): ?>
                <div class="pt-2 border-t border-gray-100">
                    <p class="text-xs font-medium text-gray-500 mb-2">Top items</p>
                    <ul class="space-y-1 text-sm">
                        <?php foreach ($insights['top_items'] as $item): ?>
                            <li class="flex justify-between gap-2">
                                <span class="text-gray-700 truncate" title="<?= htmlspecialchars((string)($item['title'] ?? '')) ?>">
                                    <?= htmlspecialchars((string)($item['item_code'] ?? '')) ?>
                                </span>
                                <span class="text-gray-500 shrink-0"><?= (int)($item['order_count'] ?? 0) ?>×</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabs -->
    <div class="border-b border-gray-200">
        <nav class="flex flex-wrap gap-1 -mb-px">
            <?php foreach ($tabDefs as $tabKey => $tabInfo): ?>
                <?php $isActiveTab = $tab === $tabKey; ?>
                <a href="<?= htmlspecialchars($viewUrl(['tab' => $tabKey, 'page_no' => 1])) ?>"
                   class="inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors <?= $isActiveTab ? 'border-[#d97706] text-[#d97706]' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>">
                    <?= htmlspecialchars($tabInfo['label']) ?>
                    <span class="rounded-full px-2 py-0.5 text-xs <?= $isActiveTab ? 'bg-orange-100 text-orange-700' : 'bg-gray-100 text-gray-600' ?>"><?= (int)$tabInfo['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </nav>
    </div>

    <?php if ($tab === 'orders'): ?>

    <!-- Toolbar -->
    <div class="flex flex-wrap gap-3 items-center relative z-10">
        <div class="relative">
            <button type="button" class="bg-[#d97706] hover:bg-[#b45309] text-white px-5 py-2 rounded-lg text-sm font-medium" onclick="document.getElementById('actionMenu').classList.toggle('hidden')" aria-label="Action menu">Actions</button>
            <div id="actionMenu" class="hidden absolute left-0 top-full mt-2 w-52 bg-white shadow-lg rounded-lg border py-1 z-50">
                <a href="<?= htmlspecialchars(base_url('?page=pos_register&action=list')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create POS order</a>
                <a href="<?= htmlspecialchars(base_url('?page=dispatch&action=bulk_dispatch')) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Bulk dispatch</a>
                <a href="<?= htmlspecialchars($exportUrl()) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Export orders (CSV)</a>
            </div>
        </div>
        <div class="flex rounded-lg border border-gray-200 overflow-hidden text-sm">
            <a href="<?= htmlspecialchars($viewUrl(['view_mode' => 'cards', 'page_no' => 1])) ?>"
               class="px-3 py-2 <?= $viewMode === 'cards' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">Cards</a>
            <a href="<?= htmlspecialchars($viewUrl(['view_mode' => 'table', 'page_no' => 1])) ?>"
               class="px-3 py-2 border-l border-gray-200 <?= $viewMode === 'table' ? 'bg-gray-900 text-white' : 'bg-white text-gray-600 hover:bg-gray-50' ?>">Table</a>
        </div>
    </div>

    <!-- Filters -->
    <form method="GET" action="<?= htmlspecialchars(base_url('')) ?>" class="bg-white rounded-xl border border-gray-200 p-4 shadow-sm space-y-4">
        <input type="hidden" name="page" value="customer">
        <input type="hidden" name="action" value="view">
        <input type="hidden" name="customer_id" value="<?= $customerId ?>">
        <input type="hidden" name="tab" value="orders">
        <input type="hidden" name="view_mode" value="<?= htmlspecialchars($viewMode) ?>">

        <div class="flex flex-wrap gap-2">
            <?php foreach ($statusChipDefs as $chipKey => $chip): ?>
                <?php $chipActive = $statusGroup === $chipKey; ?>
                <a href="<?= htmlspecialchars($viewUrl(['status_group' => $chipKey, 'page_no' => 1, 'tab' => 'orders'])) ?>"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium ring-1 ring-inset transition-colors <?= $chipActive ? 'bg-gray-900 text-white ring-gray-900' : (($chip['class'] ?? 'bg-gray-100 text-gray-700 ring-gray-200') . ' hover:opacity-90') ?>">
                    <?= htmlspecialchars($chip['label']) ?>
                    <span class="tabular-nums"><?= (int)$chip['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div class="lg:col-span-2">
                <label for="orderSearch" class="block text-xs font-medium text-gray-600 mb-1">Search</label>
                <input type="text" name="search" id="orderSearch" value="<?= htmlspecialchars($searchVal) ?>"
                       placeholder="Order no, item code, title, status…"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
            </div>
            <div>
                <label for="paymentTypeFilter" class="block text-xs font-medium text-gray-600 mb-1">Payment type</label>
                <select name="payment_type" id="paymentTypeFilter" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <?php
                    $paymentOptions = ['all' => 'All', 'cod' => 'COD', 'prepaid' => 'Prepaid', 'online' => 'Online', 'upi' => 'UPI'];
                    foreach ($paymentOptions as $val => $label):
                    ?>
                        <option value="<?= htmlspecialchars($val) ?>" <?= $paymentType === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="dateFrom" class="block text-xs font-medium text-gray-600 mb-1">From</label>
                <input type="date" name="date_from" id="dateFrom" value="<?= htmlspecialchars($dateFrom) ?>"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
                <label for="dateTo" class="block text-xs font-medium text-gray-600 mb-1">To</label>
                <input type="date" name="date_to" id="dateTo" value="<?= htmlspecialchars($dateTo) ?>"
                       class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
            </div>
            <div>
                <label for="sortSelect" class="block text-xs font-medium text-gray-600 mb-1">Sort</label>
                <select name="sort" id="sortSelect" class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm">
                    <option value="new_to_old" <?= $sortVal === 'new_to_old' ? 'selected' : '' ?>>Newest first</option>
                    <option value="old_to_new" <?= $sortVal === 'old_to_new' ? 'selected' : '' ?>>Oldest first</option>
                    <option value="ship_by_date_desc" <?= $sortVal === 'ship_by_date_desc' ? 'selected' : '' ?>>Ship-by (latest)</option>
                    <option value="ship_by_date_asc" <?= $sortVal === 'ship_by_date_asc' ? 'selected' : '' ?>>Ship-by (earliest)</option>
                </select>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <input type="hidden" name="status_group" value="<?= htmlspecialchars($statusGroup) ?>">
            <input type="hidden" name="limit" value="<?= $limitVal ?>">
            <button type="submit" class="inline-flex items-center gap-2 bg-[#d97706] hover:bg-[#b45309] text-white px-4 py-2 rounded-lg text-sm font-medium">Apply filters</button>
            <?php if ($hasActiveFilters): ?>
                <a href="<?= htmlspecialchars($viewUrl(['search' => null, 'status_group' => 'all', 'payment_type' => 'all', 'date_from' => null, 'date_to' => null, 'page_no' => 1, 'tab' => 'orders'])) ?>" class="text-sm text-red-600 hover:text-red-700 font-medium">Clear filters</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Orders -->
    <div id="orderCardsWrapper">
        <?php if ($orders === []): ?>
            <div class="bg-white rounded-xl border border-dashed border-gray-200 p-10 text-center">
                <p class="text-gray-500"><?= $hasActiveFilters ? 'No orders match your filters.' : 'No orders found for this customer.' ?></p>
                <?php if (!$hasActiveFilters): ?>
                    <a href="<?= htmlspecialchars(base_url('?page=pos_register&action=list')) ?>" class="inline-block mt-4 text-sm text-[#d97706] hover:underline font-medium">Create first order in POS</a>
                <?php endif; ?>
            </div>
        <?php elseif ($viewMode === 'table'): ?>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Order</th>
                            <th class="px-4 py-3">Item</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Order date</th>
                            <th class="px-4 py-3">Ship by</th>
                            <th class="px-4 py-3">Payment</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3">Invoice</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($orders as $order):
                            $orderNumber = (string)($order['order_number'] ?? '');
                            $itemCode = (string)($order['item_code'] ?? $order['sku'] ?? '');
                            $status = (string)($order['status'] ?? 'pending');
                            $statusClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                            $orderDetailUrl = base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . rawurlencode($orderNumber));
                            $productUrl = base_url('?page=products&action=get_product_details_html&type=outer&item_code=' . rawurlencode($itemCode));
                            $shipBy = $fmtDate($order['esd'] ?? null);
                        ?>
                        <tr class="hover:bg-gray-50/80">
                            <td class="px-4 py-3"><a href="<?= htmlspecialchars($orderDetailUrl) ?>" target="_blank" class="text-blue-600 hover:underline font-medium"><?= htmlspecialchars($orderNumber) ?></a></td>
                            <td class="px-4 py-3"><a href="<?= htmlspecialchars($productUrl) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars($itemCode) ?></a></td>
                            <td class="px-4 py-3 max-w-[200px] truncate" title="<?= htmlspecialchars((string)($order['title'] ?? '')) ?>"><?= htmlspecialchars((string)($order['title'] ?? '—')) ?></td>
                            <td class="px-4 py-3"><span class="<?= $statusClass ?> px-2 py-0.5 rounded text-xs font-medium"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?></span></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= $fmtDate($order['order_date'] ?? null) ?></td>
                            <td class="px-4 py-3 whitespace-nowrap"><?= $shipBy ?></td>
                            <td class="px-4 py-3 uppercase"><?= htmlspecialchars((string)($order['payment_type'] ?? '—')) ?></td>
                            <td class="px-4 py-3 text-right font-medium tabular-nums"><?= $fmtMoney($order['finalprice'] ?? 0) ?></td>
                            <td class="px-4 py-3">
                                <?php if (!empty($order['linked_invoice_id'])): ?>
                                    <a href="<?= htmlspecialchars(base_url('?page=invoices&action=generate_pdf&invoice_id=' . (int)$order['linked_invoice_id'])) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars((string)($order['invoice_number'] ?? '')) ?></a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order):
                $orderNumber = (string)($order['order_number'] ?? 'N/A');
                $itemCode = (string)($order['item_code'] ?? $order['sku'] ?? 'N/A');
                $productTitle = trim((string)($order['title'] ?? ''));
                $status = (string)($order['status'] ?? 'pending');
                $orderDate = $fmtDate($order['order_date'] ?? null);
                $shipByDate = $fmtDate($order['esd'] ?? null);
                $paymentTypeLabel = strtoupper((string)($order['payment_type'] ?? 'N/A'));
                $quantity = (int)($order['quantity'] ?? 0);
                $price = (float)($order['itemprice'] ?? 0);
                $totalPrice = (float)($order['finalprice'] ?? 0);
                $options = (string)($order['options'] ?? '');
                $statusClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
                $orderDetailUrl = base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . rawurlencode($orderNumber));
                $productUrl = base_url('?page=products&action=get_product_details_html&type=outer&item_code=' . rawurlencode($itemCode));
                $ordersListUrl = base_url('?page=orders&action=list&search=' . rawurlencode($orderNumber));
            ?>
            <div class="order-card-item bg-white shadow-sm border border-gray-100 rounded-xl p-5 mt-4 relative">
                <div class="absolute top-4 right-4">
                    <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-label="Order options">
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="6" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="18" r="1.5"/></svg>
                    </button>
                    <div class="order-card-menu hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                        <a href="<?= htmlspecialchars($orderDetailUrl) ?>" target="_blank" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View details</a>
                        <a href="<?= htmlspecialchars($ordersListUrl) ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Find in orders</a>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 items-start pr-10">
                    <div class="flex gap-3 min-w-0">
                        <img src="<?= htmlspecialchars((string)($order['image'] ?? 'https://via.placeholder.com/60')) ?>" alt="" class="w-16 h-16 object-cover border rounded-lg shrink-0">
                        <div class="min-w-0">
                            <p class="text-sm">Order: <a href="<?= htmlspecialchars($orderDetailUrl) ?>" target="_blank" class="text-blue-600 hover:underline font-medium"><?= htmlspecialchars($orderNumber) ?></a></p>
                            <p class="text-sm mt-1">Item: <a href="<?= htmlspecialchars($productUrl) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars($itemCode) ?></a></p>
                            <?php if ($productTitle !== ''): ?>
                                <p class="text-sm text-gray-600 mt-1 truncate" title="<?= htmlspecialchars($productTitle) ?>"><?= htmlspecialchars($productTitle) ?></p>
                            <?php endif; ?>
                            <p class="mt-2"><span class="<?= $statusClass ?> px-2 py-0.5 rounded text-xs font-medium"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $status))) ?></span></p>
                        </div>
                    </div>

                    <div class="text-sm space-y-1">
                        <p><span class="text-gray-500">Order date:</span> <?= htmlspecialchars($orderDate) ?></p>
                        <p><span class="text-gray-500">Ship by:</span> <?= htmlspecialchars($shipByDate) ?></p>
                        <p><span class="text-gray-500">Payment:</span> <?= htmlspecialchars($paymentTypeLabel) ?></p>
                        <?php if (!empty($order['invoice_number'])): ?>
                            <p><span class="text-gray-500">Invoice:</span>
                                <a href="<?= htmlspecialchars(base_url('?page=invoices&action=generate_pdf&invoice_id=' . (int)($order['linked_invoice_id'] ?? 0))) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars((string)$order['invoice_number']) ?></a>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="text-sm">
                        <p class="font-semibold mb-2 text-gray-700">Addons</p>
                        <?php if ($options !== ''): ?>
                            <?php foreach (array_filter(array_map('trim', explode(',', $options))) as $addon): ?>
                                <span class="inline-block bg-orange-500 text-white px-2.5 py-0.5 rounded text-xs mb-1 mr-1"><?= htmlspecialchars($addon) ?></span>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-gray-400">—</p>
                        <?php endif; ?>
                    </div>

                    <div class="text-right lg:pr-4">
                        <p class="text-sm text-gray-600"><?= $fmtMoney($price) ?> × <?= $quantity ?> = <?= $fmtMoney($price * $quantity) ?></p>
                        <div class="inline-block bg-[#d97706] text-white px-4 py-2 rounded-lg mt-2 text-sm font-semibold">Total <?= $fmtMoney($totalPrice) ?></div>
                    </div>
                </div>

                <?php if (!empty($order['status_log']) && is_array($order['status_log'])): ?>
                <hr class="my-4 border-gray-100">
                <div class="overflow-x-auto">
                    <div class="flex gap-6 min-w-max px-1 py-2">
                        <div class="text-center min-w-[100px]">
                            <div class="w-[18px] h-[18px] rounded-full bg-emerald-500 mx-auto"></div>
                            <p class="text-xs font-medium mt-2">Created</p>
                            <p class="text-xs text-gray-500"><?= $fmtDate($order['order_date'] ?? null) ?></p>
                        </div>
                        <?php foreach ($order['status_log'] as $log): ?>
                            <div class="text-center min-w-[100px]">
                                <div class="w-[18px] h-[18px] rounded-full bg-emerald-500 mx-auto"></div>
                                <p class="text-xs font-medium mt-2"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($log['status'] ?? '')))) ?></p>
                                <p class="text-xs text-gray-500"><?= $fmtDate($log['change_date'] ?? null) ?></p>
                                <?php if (!empty($log['changed_by_username'])): ?>
                                    <p class="text-xs text-gray-400"><?= htmlspecialchars((string)$log['changed_by_username']) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalRecords > 0): ?>
    <div class="flex justify-center">
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4">
            <div class="flex flex-wrap items-center justify-center gap-4 text-sm text-gray-600">
                <p>Showing <span class="font-medium tabular-nums"><?= count($orders) ?></span> of <span class="font-medium tabular-nums"><?= $totalRecords ?></span> orders</p>
                <?php if ($totalPages > 1): ?>
                    <a class="px-2 py-1 rounded hover:bg-gray-100 <?= $pageNo <= 1 ? 'opacity-50 pointer-events-none' : '' ?>"
                       href="<?= htmlspecialchars($viewUrl(['page_no' => max(1, $pageNo - 1), 'tab' => 'orders'])) ?>">&laquo; Prev</a>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a class="px-2.5 py-1 rounded <?= $i === $pageNo ? 'bg-gray-900 text-white font-bold' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>"
                           href="<?= htmlspecialchars($viewUrl(['page_no' => $i, 'tab' => 'orders'])) ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a class="px-2 py-1 rounded hover:bg-gray-100 <?= $pageNo >= $totalPages ? 'opacity-50 pointer-events-none' : '' ?>"
                       href="<?= htmlspecialchars($viewUrl(['page_no' => min($totalPages, $pageNo + 1), 'tab' => 'orders'])) ?>">Next &raquo;</a>
                <?php endif; ?>
                <select class="bg-transparent border-b border-gray-300 text-sm focus:outline-none"
                        onchange="if (this.options[this.selectedIndex].dataset.url) location.href=this.options[this.selectedIndex].dataset.url">
                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                        <option value="<?= $opt ?>" data-url="<?= htmlspecialchars($viewUrl(['page_no' => 1, 'tab' => 'orders', 'limit' => $opt])) ?>" <?= $opt === $limitVal ? 'selected' : '' ?>><?= $opt ?> / page</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php elseif ($tab === 'invoices'): ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <?php if ($invoices === []): ?>
            <p class="p-8 text-center text-gray-500">No invoices for this customer.</p>
        <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Amount</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($invoices as $inv): ?>
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string)($inv['invoice_number'] ?? $inv['id'] ?? '')) ?></td>
                        <td class="px-4 py-3"><?= $fmtDate($inv['invoice_date'] ?? null) ?></td>
                        <td class="px-4 py-3 tabular-nums"><?= $fmtMoney($inv['total_amount'] ?? 0) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($inv['status'] ?? '—')) ?></td>
                        <td class="px-4 py-3">
                            <a href="<?= htmlspecialchars(base_url('?page=invoices&action=generate_pdf&invoice_id=' . (int)($inv['id'] ?? 0))) ?>" target="_blank" class="text-blue-600 hover:underline">Download PDF</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'dispatches'): ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <?php if ($dispatches === []): ?>
            <p class="p-8 text-center text-gray-500">No dispatches for this customer.</p>
        <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Invoice</th>
                        <th class="px-4 py-3">AWB</th>
                        <th class="px-4 py-3">Courier</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Dispatch date</th>
                        <th class="px-4 py-3">Tracking</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($dispatches as $dispatch): ?>
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($dispatch['order_number'] ?? '—')) ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($dispatch['invoice_id'])): ?>
                                <a href="<?= htmlspecialchars(base_url('?page=invoices&action=generate_pdf&invoice_id=' . (int)$dispatch['invoice_id'])) ?>" target="_blank" class="text-blue-600 hover:underline"><?= htmlspecialchars((string)($dispatch['invoice_number'] ?? '')) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($dispatch['awb_code'] ?? '—')) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($dispatch['courier_name'] ?? '—')) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($dispatch['shipment_status'] ?? '—')) ?></td>
                        <td class="px-4 py-3"><?= $fmtDate($dispatch['dispatch_date'] ?? null) ?></td>
                        <td class="px-4 py-3">
                            <?php if (!empty($dispatch['tracking_url'])): ?>
                                <a href="<?= htmlspecialchars((string)$dispatch['tracking_url']) ?>" target="_blank" rel="noopener" class="text-blue-600 hover:underline">Track</a>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php elseif ($tab === 'activity'): ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto">
        <?php if ($activityLog === []): ?>
            <p class="p-8 text-center text-gray-500">No activity recorded for this customer.</p>
        <?php else: ?>
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-3">Date</th>
                        <th class="px-4 py-3">Order</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Remarks</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($activityLog as $entry): ?>
                    <tr class="hover:bg-gray-50/80">
                        <td class="px-4 py-3 whitespace-nowrap"><?= $fmtDate($entry['updated_at'] ?? $entry['order_date'] ?? null) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars((string)($entry['order_number'] ?? '—')) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)($entry['status'] ?? '')))) ?></td>
                        <td class="px-4 py-3 text-gray-600"><?= htmlspecialchars(trim((string)($entry['remarks'] ?? '')) ?: '—') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <?php endif; ?>

</div>

<script>
document.addEventListener('click', function(event) {
    const actionMenu = document.getElementById('actionMenu');
    if (actionMenu && !event.target.closest('button[aria-label="Action menu"]') && !event.target.closest('#actionMenu')) {
        actionMenu.classList.add('hidden');
    }
    if (!event.target.closest('button[aria-label="Order options"]') && !event.target.closest('.order-card-menu')) {
        document.querySelectorAll('.order-card-menu').forEach(function(menu) {
            menu.classList.add('hidden');
        });
    }
});
</script>
