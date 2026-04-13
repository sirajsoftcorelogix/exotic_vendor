<div class="max-w-[1600px] mx-auto space-y-6 mr-4">
    <?php
    $flash = $_SESSION['direct_purchase_flash'] ?? null;
    if ($flash) {
        unset($_SESSION['direct_purchase_flash']);
        $cls = ($flash['type'] ?? '') === 'success' ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200';
        echo '<div class="rounded-lg border px-4 py-3 ' . $cls . '">' . htmlspecialchars($flash['text'] ?? '') . '</div>';
    }
    $filters = $data['filters'] ?? [];
    $page = (int) ($data['page_no'] ?? 1);
    $totalPages = (int) ($data['total_pages'] ?? 1);
    $limit = (int) ($data['limit'] ?? 20);
    $listBaseQuery = array_filter([
        'page' => 'direct_purchase',
        'action' => 'list',
        'search_text' => $filters['search_text'] ?? '',
        'invoice_date_from' => $filters['invoice_date_from'] ?? '',
        'invoice_date_to' => $filters['invoice_date_to'] ?? '',
        'vendor_id' => !empty($filters['vendor_id']) ? $filters['vendor_id'] : '',
        'limit' => $limit,
    ], function ($v) {
        return $v !== '' && $v !== null;
    });
    $listBaseQuery['limit'] = $limit;
    ?>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Direct purchases</h1>
        <a href="index.php?page=direct_purchase&action=add"
           class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white font-semibold px-4 py-2 rounded-lg text-sm">
            <i class="fas fa-plus"></i> Add purchase
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-md p-4">
        <h2 class="text-lg font-semibold text-gray-800 mb-3">Search</h2>
        <form method="get" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-end">
            <input type="hidden" name="page" value="direct_purchase">
            <input type="hidden" name="action" value="list">
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-600 mb-1">Keyword</label>
                <input type="text" name="search_text" placeholder="Invoice no., vendor, item code, SKU…"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500"
                       value="<?= htmlspecialchars($filters['search_text'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Vendor</label>
                <select name="vendor_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white focus:ring-2 focus:ring-amber-500">
                    <option value="">All vendors</option>
                    <?php foreach ($data['vendors'] ?? [] as $v): ?>
                        <option value="<?= (int) $v['id'] ?>" <?= (!empty($filters['vendor_id']) && (int) $filters['vendor_id'] === (int) $v['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($v['vendor_name'] ?? '') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Invoice from</label>
                <input type="date" name="invoice_date_from" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                       value="<?= htmlspecialchars($filters['invoice_date_from'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Invoice to</label>
                <input type="date" name="invoice_date_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                       value="<?= htmlspecialchars($filters['invoice_date_to'] ?? '') ?>">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">Rows</label>
                <select name="limit" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                    <?php foreach ([10, 20, 50, 100] as $l): ?>
                        <option value="<?= $l ?>" <?= $limit === $l ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold px-4 py-2 rounded-lg">Search</button>
                <a href="index.php?page=direct_purchase&action=list" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-4 py-2 rounded-lg inline-flex items-center">Clear</a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Grand total</th>
                    <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Invoice file</th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                <?php
                $purchases = $data['purchases'] ?? [];
                if (empty($purchases)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">No purchases found.</td>
                    </tr>
                <?php else:
                    $rowNum = ($page - 1) * $limit;
                    foreach ($purchases as $p):
                        $rowNum++;
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700"><?= $rowNum ?></td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900"><?= htmlspecialchars($p['invoice_number'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($p['invoice_date'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($p['vendor_name'] ?? '') ?></td>
                            <td class="px-4 py-3 text-sm text-right text-gray-900"><?= number_format((float) ($p['grand_total'] ?? 0), 2) ?></td>
                            <td class="px-4 py-3 text-sm text-center">
                                <?php if (!empty($p['invoice_file'])): ?>
                                    <a href="<?= htmlspecialchars($p['invoice_file']) ?>" target="_blank" class="text-amber-700 hover:underline">View</a>
                                <?php else: ?>
                                    <span class="text-gray-400">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-right whitespace-nowrap">
                                <a href="index.php?page=direct_purchase&action=edit&id=<?= (int) $p['id'] ?>"
                                   class="text-amber-700 hover:underline mr-3">Edit</a>
                                <a href="index.php?page=direct_purchase&action=delete&id=<?= (int) $p['id'] ?>"
                                   onclick="return confirm('Delete this purchase and all line items?');"
                                   class="text-red-600 hover:underline">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2 text-sm">
                <span class="text-gray-600">Page <?= $page ?> of <?= $totalPages ?> (<?= (int) ($data['total_records'] ?? 0) ?> records)</span>
                <div class="flex gap-2">
                    <?php if ($page > 1): ?>
                        <a class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50"
                           href="index.php?<?= htmlspecialchars(http_build_query(array_merge($listBaseQuery, ['page_no' => $page - 1]))) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($page < $totalPages): ?>
                        <a class="px-3 py-1 rounded border border-gray-300 hover:bg-gray-50"
                           href="index.php?<?= htmlspecialchars(http_build_query(array_merge($listBaseQuery, ['page_no' => $page + 1]))) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
