<div class="container mx-auto p-4">
    <div class="mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-bold">Purchase List</h1>
        <div class="text-sm text-gray-600">Showing <strong><?php echo isset($data['total_records']) ? (int)$data['total_records'] : 0; ?></strong> items</div>
    </div>

    <?php if (!empty($data['purchase_list'])): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <?php foreach ($data['purchase_list'] as $pl):
                $product = $pl['product'] ?? null;
                $image = $product['image'] ?? 'https://placehold.co/100x140/e2e8f0/4a5568?text=No+Image';
                $title = $product['title'] ?? ($product['item_code'] ?? 'Product');
                $item_code = $product['item_code'] ?? ($pl['sku'] ?? '');
                $cost = isset($product['cost_price']) ? '₹' . number_format((float)$product['cost_price']) : '';
                $status = $pl['status'] ?? '';
                $agent_name = $pl['agent_name'] ?? '';
                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : '');
                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : '');
            ?>
                <div class="bg-white border border-gray-300 rounded-lg shadow-lg p-4">
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
                        </div>

                        <div class="mt-4 flex items-center justify-end space-x-2">
                            <!-- <a href="?page=products&action=view&product_id=<?php echo (int)$pl['product_id']; ?>" class="px-3 py-1 bg-gray-100 rounded text-sm">View Product</a> -->
                            <a target="_blank" href="<?php echo base_url('?page=products&action=get_product_details_html&type=outer&item_code=') . $item_code; ?>" class="px-3 py-1 bg-gray-100 rounded text-sm">View Product</a>
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
    // Global toast container and showAlert
    function ensureToastContainer() {
        let c = document.getElementById('globalToastContainer');
        if (!c) {
            c = document.createElement('div');
            c.id = 'globalToastContainer';
            c.style.position = 'fixed';
            c.style.right = '20px';
            c.style.top = '20px';
            c.style.zIndex = 99999;
            document.body.appendChild(c);
        }
        return c;
    }

    function showAlertP(message, type = 'info', timeout = 3000) {
        const container = ensureToastContainer();
        const toast = document.createElement('div');
        toast.className = 'rounded px-4 py-2 mb-2 shadow-md text-sm flex items-center';
        const colors = {
            success: {bg: 'rgba(16,185,129,0.12)', color: '#065f46'},
            error: {bg: 'rgba(239,68,68,0.12)', color: '#991b1b'},
            info: {bg: 'rgba(99,102,241,0.08)', color: '#3730a3'}
        };
        const cfg = colors[type] || colors.info;
        toast.style.background = cfg.bg;
        toast.style.color = cfg.color;
        toast.style.border = '1px solid rgba(0,0,0,0.04)';
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(() => {
            toast.style.transition = 'opacity 0.25s ease-out, transform 0.25s ease-out';
            toast.style.opacity = '0';
            toast.style.transform = 'translateY(-6px)';
            setTimeout(() => toast.remove(), 300);
        }, timeout);
    }

    // Global confirm modal (returns Promise<boolean>)
    function ensureConfirmModal() {
        let m = document.getElementById('globalConfirmModal');
        if (!m) {
            m = document.createElement('div');
            m.id = 'globalConfirmModal';
            m.style.position = 'fixed';
            m.style.left = '0'; m.style.top = '0'; m.style.right = '0'; m.style.bottom = '0';
            m.style.display = 'none';
            m.style.zIndex = 100000;
            m.style.alignItems = 'center';
            m.style.justifyContent = 'center';
            m.style.background = 'rgba(0,0,0,0.35)';

            m.innerHTML = '<div id="globalConfirmBox" style="background:#fff;padding:16px;border-radius:8px;box-shadow:0 10px 25px rgba(0,0,0,0.2);min-width:420px;max-width:90%;">' +
                '<div id="globalConfirmMessage" style="margin-bottom:12px;color:#111"></div>' +
                '<div style="text-align:right">' +
                '<button id="globalConfirmCancel" style="margin-right:8px;padding:8px 12px;border-radius:6px;border:1px solid #e5e7eb;background:#fff;">Cancel</button>' +
                '<button id="globalConfirmOk" style="padding:8px 12px;border-radius:6px;border:0;background:#059669;color:#fff;">OK</button>' +
                '</div></div>';
            document.body.appendChild(m);
        }
        return m;
    }

    function showConfirm(message) {
        return new Promise(resolve => {
            const modal = ensureConfirmModal();
            const msg = document.getElementById('globalConfirmMessage');
            const ok = document.getElementById('globalConfirmOk');
            const cancel = document.getElementById('globalConfirmCancel');
            msg.textContent = message;
            modal.style.display = 'flex';
            ok.focus();
            function cleanup(result) {
                modal.style.display = 'none';
                ok.removeEventListener('click', onOk);
                cancel.removeEventListener('click', onCancel);
                resolve(result);
            }
            function onOk() { cleanup(true); }
            function onCancel() { cleanup(false); }
            ok.addEventListener('click', onOk);
            cancel.addEventListener('click', onCancel);
        });
    }

    // Use showConfirm instead of native confirm so we can show consistent UI
    async function markAsPurchased(id) {
        const confirmed = await showConfirm('Mark this item as purchased?');
        if (!confirmed) return;
        fetch('?page=products&action=mark_purchased', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: id })
        }).then(r => r.json()).then(data => {
            if (data && data.success) {
                showAlert('Marked as purchased', 'success');
                setInterval(() => location.reload(), 4000);
                
            } else {
                showAlert('Failed: ' + (data.message || 'Error'), 'error');
            }
        }).catch(err => showAlert('Network error', 'error'));
    }
</script>
