<?php
require_once dirname(dirname(__DIR__)) . '/helpers/sales_return_types.php';
/** @var array $data */
$returns = $data['returns'] ?? [];
$filters = $data['filters'] ?? [];
$pageNo = (int) ($data['page_no'] ?? 1);
$totalPages = (int) ($data['total_pages'] ?? 1);
$limit = (int) ($data['limit'] ?? 20);
$totalRecords = (int) ($data['total_records'] ?? 0);
$warehouses = $data['warehouses'] ?? [];
$isAdmin = !empty($data['is_admin']);

$flash = $_SESSION['sales_return_flash'] ?? null;
if ($flash) {
    unset($_SESSION['sales_return_flash']);
}

$queryString = '';
if (!empty($_GET)) {
    $params = $_GET;
    unset($params['page_no'], $params['limit']);
    if ($params !== []) {
        $queryString = '&' . http_build_query($params);
    }
}
$pgBase = '?page=sales_returns&action=index&limit=' . $limit . $queryString;
$dateMax = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$returnTypes = sales_return_type_options();
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-orange-200/45 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-orange-900/[0.04] mb-6">
        <div class="relative px-5 py-7 sm:px-8 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">Sales returns</h1>
                <p class="mt-2 text-sm text-gray-600">Partial returns against any order — stock is restored when a prior sale OUT exists.</p>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php
        $ring = ($flash['type'] ?? '') === 'success'
            ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900'
            : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="get" class="bg-white rounded-xl shadow-sm border border-gray-200 p-5 mb-6">
        <input type="hidden" name="page" value="sales_returns">
        <input type="hidden" name="action" value="index">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                <input type="text" name="search_text" value="<?= htmlspecialchars((string) ($filters['search_text'] ?? '')) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" placeholder="Return no., order, remarks">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">From date</label>
                <input type="date" name="return_date_from" max="<?= htmlspecialchars($dateMax) ?>"
                    value="<?= htmlspecialchars((string) ($filters['return_date_from'] ?? '')) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">To date</label>
                <input type="date" name="return_date_to" max="<?= htmlspecialchars($dateMax) ?>"
                    value="<?= htmlspecialchars((string) ($filters['return_date_to'] ?? '')) ?>"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">All</option>
                    <option value="finalized" <?= ($filters['status'] ?? '') === 'finalized' ? 'selected' : '' ?>>Finalized</option>
                    <option value="cancelled" <?= ($filters['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <?php if ($isAdmin && $warehouses !== []): ?>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Store</label>
                    <select name="warehouse_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <option value="">All stores</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= (int) ($wh['id'] ?? 0) ?>" <?= (int) ($filters['warehouse_id'] ?? 0) === (int) ($wh['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($wh['address_title'] ?? '')) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
        </div>
        <div class="mt-4 flex gap-2">
            <button type="submit" class="px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700">Filter</button>
            <a href="?page=sales_returns&action=index" class="px-4 py-2 rounded-lg border border-gray-300 text-sm font-medium hover:bg-gray-50">Reset</a>
        </div>
    </form>

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 text-sm text-gray-600">
            <?= (int) $totalRecords ?> record(s)
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Return #</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Date</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Order</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Invoice</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Type</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Stock</th>
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">Status</th>
                        <th class="px-4 py-3 text-right font-semibold text-gray-700">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if ($returns === []): ?>
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-gray-500">No sales returns found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($returns as $row): ?>
                            <?php
                            $typeKey = (string) ($row['return_type'] ?? '');
                            $typeLabel = $returnTypes[$typeKey] ?? $typeKey;
                            ?>
                            <tr class="hover:bg-orange-50/30">
                                <td class="px-4 py-3 font-mono text-xs"><?= htmlspecialchars((string) ($row['return_number'] ?? '')) ?></td>
                                <td class="px-4 py-3"><?= !empty($row['return_date']) ? date('j M Y', strtotime((string) $row['return_date'])) : '—' ?></td>
                                <td class="px-4 py-3 font-medium"><?= htmlspecialchars((string) ($row['order_number'] ?? '')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars((string) ($row['invoice_number'] ?? '—')) ?></td>
                                <td class="px-4 py-3"><?= htmlspecialchars($typeLabel) ?></td>
                                <td class="px-4 py-3">
                                    <?= !empty($row['stock_applied']) ? '<span class="text-emerald-700 font-medium">Applied</span>' : '<span class="text-gray-500">None</span>' ?>
                                </td>
                                <td class="px-4 py-3 capitalize"><?= htmlspecialchars((string) ($row['status'] ?? '')) ?></td>
                                <td class="px-4 py-3 text-right">
                                    <a href="?page=sales_returns&action=view&id=<?= (int) ($row['id'] ?? 0) ?>"
                                        class="text-orange-700 font-semibold hover:underline">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($totalPages > 1): ?>
            <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
                <span>Page <?= $pageNo ?> of <?= $totalPages ?></span>
                <div class="flex gap-2">
                    <?php if ($pageNo > 1): ?>
                        <a href="<?= htmlspecialchars($pgBase . '&page_no=' . ($pageNo - 1)) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50">Prev</a>
                    <?php endif; ?>
                    <?php if ($pageNo < $totalPages): ?>
                        <a href="<?= htmlspecialchars($pgBase . '&page_no=' . ($pageNo + 1)) ?>" class="px-3 py-1.5 border rounded-lg hover:bg-gray-50">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
