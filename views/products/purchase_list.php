<div class="container mx-auto p-4">
    <div class="mb-4 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div class="flex justify-between items-center">
            <h1 class="text-2xl font-bold">Purchase List</h1>
            <div class="text-sm text-gray-600 ml-auto px-2">Showing <strong><?php echo isset($data['total_records']) ? (int)$data['total_records'] : 0; ?></strong> items</div>
        </div>
        <form method="GET" action="" class="flex items-center gap-3">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="purchase_list">
            <div class=" gap-2 w-1/2 flex-1">
                <label class="text-xs text-gray-600">Category</label><br>
                <select name="category" class="text-sm border rounded px-2 py-1 bg-white" onchange="this.form.submit()">
                    <option value="all">All</option>
                    <?php foreach (($data['categories'] ?? []) as $cat): ?>
                        <option value="<?= htmlspecialchars($cat) ?>" <?= (isset($data['selected_filters']['category']) && $data['selected_filters']['category'] === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class=" gap-2 w-1/2 ml-2 flex-2">
                <label class="text-xs text-gray-600">Status</label><br>
                <select name="status" class="text-sm border rounded px-2 py-1 bg-white w-full" onchange="this.form.submit()">
                    <option value="all" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'all') ? 'selected' : '' ?>>All</option>
                    <option value="pending" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'pending') ? 'selected' : '' ?> <?= (!isset($data['selected_filters']['status']) ? 'selected' : '') ?>>Pending</option>
                    <option value="purchased" <?= (isset($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'purchased') ? 'selected' : '' ?>>Purchased</option>
                </select>
            </div>
            <!-- <div class="flex items-center gap-2">
                <button type="submit" class="bg-amber-600 text-white px-3 py-1 rounded">Filter</button>
                <a href="?page=products&action=purchase_list" class="px-3 py-1 border rounded text-sm">Clear</a>
            </div> -->
        </form>
    </div>

    <?php if (!empty($data['purchase_list'])): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($data['purchase_list'] as $pl):
                //$product = $pl['product'] ?? null;
                $image = $pl['image'] ?? 'https://placehold.co/100x140/e2e8f0/4a5568?text=No+Image';
                $title = $pl['title'] ?? ($pl['item_code'] ?? 'Product');
                $item_code = $pl['item_code'] ?? ($pl['sku'] ?? '');
                $cost = isset($pl['cost_price']) ? '₹' . number_format((float)$pl['cost_price']) : '';
                $status = $pl['status'] ?? '';
                $agent_name = $pl['agent_name'] ?? '';
                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : '');
                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : '');
            ?>
                <div class="bg-white border border-gray-300 rounded-3xl shadow-lg p-4">
                    <div class="flex space-x-4">
                        <img src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" class="w-24 h-32 object-cover rounded-md flex-shrink-0">
                        <div class="flex-1">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($title); ?></div>
                                    <div class="text-xs text-gray-500 mt-1">Item Code: <strong><?php echo htmlspecialchars($item_code); ?></strong></div>
                                    <div class="text-xs text-gray-500">Status: <span class="font-medium px-2 bg-<?php echo $status === 'purchased' ? 'green' : 'yellow'; ?>-100 text-<?php echo $status === 'purchased' ? 'green' : 'yellow'; ?>-800"><?php echo htmlspecialchars($status); ?></span></div>
                                </div>
                                <div class="text-right">
                                    <!-- <div class="text-sm font-bold text-gray-900"><?php //echo $cost; ?></div> -->
                                    
                                </div>
                            </div>

                            
                        </div>
                    </div>
                    <div>
                        <div class="mt-3 grid grid-cols-2 gap-2 text-xs text-gray-600">
                            <div>Assigned Agent : <strong><?php echo htmlspecialchars($agent_name); ?></strong></div>
                            <div>Date Added : <strong><?php echo htmlspecialchars($date_added); ?></strong></div>
                            <div>Date Purchased : <strong><?php echo htmlspecialchars($date_purchased); ?></strong></div>
                            <div>SKU : <strong><?php echo htmlspecialchars($pl['sku'] ?? ''); ?></strong></div>
                            <div>Color : <strong><?php echo htmlspecialchars($pl['color'] ?? ''); ?></strong></div>
                            <div>Size : <strong><?php echo htmlspecialchars($pl['size'] ?? ''); ?></strong></div>
                            <div>Material : <strong><?php echo htmlspecialchars($pl['material'] ?? ''); ?></strong></div>
                            <div>Dimensions : <strong><?php echo htmlspecialchars($pl['prod_height'] ?? ''); ?> x <?php echo htmlspecialchars($pl['prod_width'] ?? ''); ?> x <?php echo htmlspecialchars($pl['prod_length'] ?? ''); ?></strong></div>
                            <div>Weight : <strong><?php echo htmlspecialchars($pl['product_weight'] ?? '').' '.htmlspecialchars($pl['product_weight_unit'] ?? ''); ?></strong></div>
                            <label class="block">Quantity: <input type="number" id="quantity_<?php echo (int)$pl['id']; ?>" value="<?php echo htmlspecialchars($pl['quantity'] ?? ''); ?>" class="border rounded px-2 py-1 mt-1 w-16"></label>
                        </div>

                        <div class="mt-3 grid grid-cols-1 gap-2 text-xs text-gray-600">                            
                            <label class="block">Remarks: <textarea id="remarks_<?php echo (int)$pl['id']; ?>" class="border rounded px-2 py-1 mt-1 w-full" rows="2"><?php echo htmlspecialchars($pl['remarks'] ?? ''); ?></textarea></label>
                        </div>

                        <div class="mt-4 flex items-center justify-end space-x-2">
                            <button onclick="savePurchaseItem(<?php echo (int)$pl['id']; ?>, this)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm">Save</button>
                            <button onclick="markAsPurchased(<?php echo (int)$pl['id']; ?>)" class="px-3 py-1 bg-amber-600 text-white rounded text-sm">Mark Purchased</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-center space-x-2">
            <?php $page_no = $data['page_no'] ?? 1; $total_pages = $data['total_pages'] ?? 1; $limit = $data['limit'] ?? 50; $query_string = '';
                // preserve existing filters in query string (if any)
                $qs = $_GET; unset($qs['page_no'], $qs['limit']); $query_string = http_build_query($qs);
                $query_string = $query_string ? '&' . $query_string : '';
            ?>
            <?php if ($page_no > 1): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo max(1, $page_no-1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">&laquo; Prev</a>
            <?php endif; ?>
            <span class="px-3 py-1 text-sm">Page <?php echo $page_no; ?> of <?php echo $total_pages; ?></span>
            <?php if ($page_no < $total_pages): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo min($total_pages, $page_no+1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">Next &raquo;</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-600">No items in purchase list.</div>
    <?php endif; ?>
</div>

<script>
    // Use global helpers when available (defined in layout). Fallbacks included.
    function showAlertP(message, type = 'info', timeout = 3000) {
        if (window.showGlobalToast) return window.showGlobalToast(message, type, timeout);
        alert(message);
    }

    function savePurchaseItem(id, btn) {
        const qty = document.getElementById('quantity_' + id).value;
        const remarks = document.getElementById('remarks_' + id).value;
        if (!btn) btn = {};
        btn.disabled = true;
        const originalText = btn.innerHTML || '';
        btn.innerHTML = 'Saving...';
        fetch('?page=products&action=update_purchase_item', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: id, quantity: qty, remarks: remarks })
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                showAlert('Saved successfully', 'success');
                //if (window.showGlobalToast) window.showGlobalToast('Saved successfully', 'success'); else alert('Saved successfully');
                setTimeout(() => { location.reload(); }, 800);
            } else {
                showAlert('Failed: ' + (data.message || 'Error'), 'error');
                //if (window.showGlobalToast) window.showGlobalToast('Failed: ' + (data.message || 'Error'), 'error'); else alert('Failed');
            }
        }).catch(err => { if (window.showGlobalToast) window.showGlobalToast('Network error', 'error'); else alert('Network error'); })
        .finally(() => { btn.disabled = false; btn.innerHTML = originalText; });
    }

    async function markAsPurchased(id) {
        const confirmed = window.customConfirm ? await window.customConfirm('Mark this item as purchased?') : confirm('Mark this item as purchased?');
        if (!confirmed) return;
        fetch('?page=products&action=mark_purchased', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: id })
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                showAlert('Marked as purchased', 'success');
                //if (window.showGlobalToast) window.showGlobalToast('Marked as purchased', 'success'); else alert('Marked as purchased');
                setTimeout(() => location.reload(), 900);
            } else {
                if (window.showGlobalToast) window.showGlobalToast('Failed: ' + (data.message || 'Error'), 'error'); else alert('Failed');
            }
        }).catch(err => { if (window.showGlobalToast) window.showGlobalToast('Network error', 'error'); else alert('Network error'); });
    }
</script>
