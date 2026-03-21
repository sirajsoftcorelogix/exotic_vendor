<div class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-gray-900">Stock Transfer History</h1>
        <a href="?page=products&action=transfer_stock" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition">
            <i class="fas fa-plus"></i>
            Create Stock Transfer
        </a>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 mb-4">
        <form method="GET" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="stock_transfer">

            <div>
                <label class="text-xs font-semibold text-gray-600">TO Number</label>
                <input type="text" name="transfer_order_no" value="<?= htmlspecialchars($data['filters']['transfer_order_no'] ?? '') ?>" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm" placeholder="TO-..">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Dispatch Date</label>
                <input type="date" name="dispatch_date" value="<?= htmlspecialchars($data['filters']['dispatch_date'] ?? '') ?>" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Requested By</label>
                <select name="requested_by" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($data['users'] ?? [] as $user): ?>
                        <option value="<?= (int)$user['id'] ?>" <?= (isset($data['filters']['requested_by']) && (int)$data['filters']['requested_by'] === (int)$user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Dispatch By</label>
                <select name="dispatch_by" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($data['users'] ?? [] as $user): ?>
                        <option value="<?= (int)$user['id'] ?>" <?= (isset($data['filters']['dispatch_by']) && (int)$data['filters']['dispatch_by'] === (int)$user['id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Source Location</label>
                <select name="from_warehouse" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($data['warehouses'] ?? [] as $wh): ?>
                        <option value="<?= (int)$wh['id'] ?>" <?= (isset($data['filters']['from_warehouse']) && (int)$data['filters']['from_warehouse'] === (int)$wh['id']) ? 'selected' : '' ?>><?= htmlspecialchars($wh['address_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Destination Location</label>
                <select name="to_warehouse" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm">
                    <option value="">All</option>
                    <?php foreach ($data['warehouses'] ?? [] as $wh): ?>
                        <option value="<?= (int)$wh['id'] ?>" <?= (isset($data['filters']['to_warehouse']) && (int)$data['filters']['to_warehouse'] === (int)$wh['id']) ? 'selected' : '' ?>><?= htmlspecialchars($wh['address_title']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="text-xs font-semibold text-gray-600">Item Number</label>
                <input type="text" name="item_number" value="<?= htmlspecialchars($data['filters']['item_number'] ?? '') ?>" class="mt-1 w-full px-3 py-2 border rounded-lg text-sm" placeholder="Item code or SKU">
            </div>
            <div class="md:col-span-4 flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Search</button>
                <a href="?page=products&action=stock_transfer" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg">Reset</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm">
        <table class="min-w-full">
            <thead class="bg-gray-50">
                <tr class="text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                    <th class="px-6 py-3">Transfer Order</th>
                    <th class="px-6 py-3">Dispatch Date</th>
                    <th class="px-6 py-3">Requested By</th>
                    <th class="px-6 py-3">Dispatch By</th>
                    <th class="px-6 py-3">Source</th>
                    <th class="px-6 py-3">Destination</th>
                    <th class="px-6 py-3">Items (SKU / qty / note)</th>
                    <th class="px-6 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                <?php if (empty($data['transfers'])): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">No transfers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($data['transfers'] as $transfer): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-medium text-gray-800"><?php echo htmlspecialchars($transfer['transfer_order_no']); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo !empty($transfer['dispatch_date']) ? date('j F Y', strtotime($transfer['dispatch_date'])) : ''; ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transfer['requested_by_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transfer['dispatch_by_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transfer['source_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-600"><?php echo htmlspecialchars($transfer['dest_name'] ?? ''); ?></td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php if (!empty($transfer['items'])): ?>
                                    <ul class="space-y-1">
                                        <?php foreach ($transfer['items'] as $item): ?>
                                            <?php
                                                $label = trim($item['sku'] ?? '');
                                                if ($label === '') {
                                                    $label = trim($item['item_code'] ?? '');
                                                }
                                            ?>
                                            <li class="break-words">
                                                <span class="font-semibold"><?php echo htmlspecialchars($label ?: 'N/A'); ?></span>
                                                <span class="text-gray-500">(<?php echo (int)$item['transfer_qty']; ?><?php if (!empty(trim($item['item_notes'] ?? ''))): ?>, <?php echo htmlspecialchars($item['item_notes']); ?><?php endif; ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <span class="text-gray-500">No items</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 space-x-2">
                                <?php
                                    $productIds = [];
                                    foreach ($transfer['items'] as $item) {
                                        if (!empty($item['product_id'])) {
                                            $productIds[] = (int)$item['product_id'];
                                        }
                                    }
                                    $productIdsParam = urlencode(implode(',', array_unique($productIds)));
                                ?>
                                <a href="?page=products&action=transfer_stock&transfer_id=<?php echo urlencode($transfer['id']); ?>&product_ids=<?php echo $productIdsParam; ?>" class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-blue-600 text-white hover:bg-blue-700 transition">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <a href="?page=stock_transfer_grns&action=create&transfer_id=<?php echo urlencode($transfer['id']); ?>" class="inline-flex items-center gap-2 px-3 py-1 rounded-md bg-amber-600 text-white hover:bg-amber-700 transition">
                                    <i class="fas fa-file-invoice"></i> GRN
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php
        $page_no = isset($data['page_no']) ? (int)$data['page_no'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 50;
        $total_records = isset($data['total_records']) ? (int)$data['total_records'] : 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
        $queryString = '';
        // Preserve existing query params other than page_no & limit
        if (!empty($_GET)) {
            $params = $_GET;
            unset($params['page_no'], $params['limit']);
            if (!empty($params)) {
                $queryString = '&' . http_build_query($params);
            }
        }
    ?>

    <?php if ($total_pages > 1): ?>
        <div class="mt-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-gray-600">
                Showing <?php echo ($page_no - 1) * $limit + 1; ?> - <?php echo min($page_no * $limit, $total_records); ?> of <?php echo $total_records; ?> transfers
            </div>
            <div class="flex items-center gap-2">
                <a href="?page=products&action=stock_transfer_list&page_no=1&limit=<?php echo $limit . $queryString; ?>" class="px-3 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 <?php echo $page_no === 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">First</a>
                <a href="?page=products&action=stock_transfer_list&page_no=<?php echo max(1, $page_no - 1); ?>&limit=<?php echo $limit . $queryString; ?>" class="px-3 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 <?php echo $page_no === 1 ? 'opacity-50 cursor-not-allowed' : ''; ?>">Previous</a>
                <span class="px-3 py-1 text-sm text-gray-700">Page <?php echo $page_no; ?> of <?php echo $total_pages; ?></span>
                <a href="?page=products&action=stock_transfer_list&page_no=<?php echo min($total_pages, $page_no + 1); ?>&limit=<?php echo $limit . $queryString; ?>" class="px-3 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 <?php echo $page_no === $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>">Next</a>
                <a href="?page=products&action=stock_transfer_list&page_no=<?php echo $total_pages; ?>&limit=<?php echo $limit . $queryString; ?>" class="px-3 py-1 rounded-md border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 <?php echo $page_no === $total_pages ? 'opacity-50 cursor-not-allowed' : ''; ?>">Last</a>
            </div>
        </div>
    <?php endif; ?>
</div>
