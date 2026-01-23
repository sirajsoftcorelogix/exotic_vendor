<style>
    /* Hide native date icon without breaking click behavior (Chrome/Edge/Safari) */
    input[type="date"]::-webkit-calendar-picker-indicator {
        opacity: 0;
        width: 2.5rem;
        /* keep click area */
        height: 100%;
        cursor: pointer;
    }

    /* Optional: avoid weird default styling */
    input[type="date"] {
        -webkit-appearance: none;
        appearance: none;
    }
</style>
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
            <div class="gap-2 w-1/2 ml-2 flex-2">
                <label class="text-xs text-gray-600">Status</label><br>
                <select name="status" class="text-sm border rounded px-2 py-1 bg-white w-full" onchange="this.form.submit()">
                    <!-- ALL option -->
                    <option value="all" <?= (!empty($data['selected_filters']['status']) && $data['selected_filters']['status'] === 'all') ? 'selected' : '' ?>>
                        All
                    </option>
                    <?php
                    $statuses = getPurchaseStatuses(); // returns key => label list
                    $selected = $data['selected_filters']['status'] ?? 'pending'; // default fallback
                    ?>
                    <?php foreach ($statuses as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>"
                            <?= ($selected === $key) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
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
            <?php
            //print_array($data['purchase_list']);
            foreach ($data['purchase_list'] as $pl):
                //$product = $pl['product'] ?? null;
                $image = $pl['image'] ?? 'https://placehold.co/100x140/e2e8f0/4a5568?text=No+Image';
                $title = $pl['title'] ?? ($pl['item_code'] ?? 'Product');
                $item_code = $pl['item_code'] ?? ($pl['sku'] ?? '');
                $cost = isset($pl['cost_price']) ? '₹' . number_format((float)$pl['cost_price']) : '';
                $status = str_replace("_", " ", $pl['status']) ?? '';
                $agent_name = $pl['agent_name'] ?? '';
                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : '');
                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : '');

                // Build WhatsApp share text
                $waText = "Product Details:%0A";
                $waText .= "Quantity to be Purchased: " . (int)($pl['quantity'] ?? 0) . "%0A";
                $waText .= "SKU: " . urlencode($pl['sku'] ?? '') . "%0A";
                $waText .= "Color: " . urlencode($pl['product']['color'] ?? '') . "%0A";
                $waText .= "Size: " . urlencode($pl['product']['size'] ?? '') . "%0A";
                $waText .= "Dimensions (HxWxL): " . urlencode(($pl['product']['prod_height'] ?? '') . ' x ' . ($pl['product']['prod_width'] ?? '') . ' x ' . ($pl['product']['prod_length'] ?? '')) . "%0A";
                $waText .= "Weight: " . urlencode(($pl['product']['weight'] ?? '') . ' ' . ($pl['product']['weight_unit'] ?? '')) . "KG %0A";
                $waText .= "Image: " . urlencode($image) . "%0A";   
            ?>
                <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col h-full">
                    <input type="hidden" id="productId_<?= (int)$pl['id']; ?>" value="<?= (int)$pl['product_id']; ?>" />
                    <!-- Top right share -->
                    <div class="flex justify-end mb-2">
                        <a href="https://wa.me/?text=<?= $waText; ?>"
                            target="_blank"
                            class="text-amber-600 hover:text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none"
                                stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7" />
                                <path d="M12 3l5 5h-3v6h-4V8H7l5-5z" />
                            </svg>
                        </a>
                    </div>

                    <!-- Image + Title block -->
                    <div class="flex flex-col sm:flex-row gap-3">
                        <img src="<?php echo htmlspecialchars($image); ?>"
                            alt="<?php echo htmlspecialchars($title); ?>"
                            class="w-24 h-32 object-cover rounded-md mx-auto sm:mx-0" />

                        <div class="flex-1 space-y-1">
                            <div class="text-sm font-semibold text-gray-800 leading-tight">
                                <?php echo htmlspecialchars($title); ?>
                            </div>
                            <div class="text-xs text-gray-500">
                                Item Code: <strong><?php echo htmlspecialchars($item_code); ?></strong>
                            </div>
                            <div class="text-xs">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px]
                    <?php if ($status === 'purchased') echo 'bg-green-100 text-green-800';
                    else echo 'bg-amber-100 text-amber-800'; ?>">
                                    <?php echo ucwords(htmlspecialchars($status)); ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Meta Grid -->
                    <div class="mt-3 grid grid-cols-2 md:grid-cols-3 gap-y-1 text-xs text-gray-600">
                        <?php if (!empty($pl['vendor']) && strtoupper(trim($pl['vendor'])) !== 'N/A'): ?>
                            <div>Vendor: <strong><?= ucwords($pl['vendor']); ?></strong></div>
                        <?php endif; ?>

                        <div>Added By: <strong><?= htmlspecialchars($pl['added_by']); ?></strong></div>
                        <div>Added: <strong><?= htmlspecialchars($date_added); ?></strong></div>
                        <?php if (!empty($date_purchased) && $date_purchased !== 'N/A'): ?>
                            <div>Purchased: <strong><?= htmlspecialchars($date_purchased); ?></strong></div>
                        <?php endif; ?>

                        <div>SKU: <strong><?= htmlspecialchars($pl['sku']); ?></strong></div>
                        <div>Color: <strong><?= htmlspecialchars($pl['product']['color']); ?></strong></div>
                        <div>Size: <strong><?= htmlspecialchars($pl['product']['size']); ?></strong></div>
                        <div>Material: <strong><?= htmlspecialchars($pl['product']['material'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong></div>                        
                        <div>Weight:
                            <strong><?= htmlspecialchars($pl['product']['weight'] ?? '', ENT_QUOTES, 'UTF-8'); ?> <?= htmlspecialchars($pl['product']['weight_unit'] ?? '', ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div>Dimensions:
                            <strong>
                                H: <?= htmlspecialchars((string)($pl['product']['prod_height'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                × W: <?= htmlspecialchars((string)($pl['product']['prod_width'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                × L: <?= htmlspecialchars((string)($pl['product']['prod_length'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                            </strong>
                        </div>
                    </div>

                    <!-- Purchase Fields -->
                    <?php
                    $isPurchased = isset($pl['status']) && $pl['status'] === 'purchased';
                    $pQty = (int)($pl['quantity'] ?? 0);
                    ?>
                    <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            Quantity to be Purchase:
                            <span class="block bg-gray-100 border rounded px-2 py-1 mt-1 text-center">
                                <?= htmlspecialchars($pQty ?? '0'); ?>
                            </span>
                        </div>
                        <div>
                            Quantity Purchased:  
                            <input type="number" min="1" step="1"
                                id="quantity_<?= (int)$pl['id']; ?>"
                                value="<?= $isPurchased ? htmlspecialchars($pQty) : '' ?>"
                                class="no-negative w-full border rounded px-2 py-1 mt-1 <?= $isPurchased ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>"
                                <?= $isPurchased ? 'disabled' : '' ?> />    
                        </div>

                        <div class="col-span-2">
                            Status:
                            <select
                                id="status_<?= (int)$pl['id']; ?>"
                                class="w-full border rounded px-2 py-1 mt-1 text-xs <?= $isPurchased ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>"
                                <?= $isPurchased ? 'disabled' : '' ?>>
                                <?php foreach (getPurchaseStatuses() as $statusKey => $statusLabel): ?>
                                    <option value="<?= htmlspecialchars($statusKey); ?>"
                                        <?= ($pl['status'] === $statusKey ? 'selected' : ''); ?>>
                                        <?= htmlspecialchars($statusLabel); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($pl['status'] === 'item_ordered'): ?>
                        <div class="mt-3">
                            <label class="block text-xs text-gray-600">Expected Delivery</label>
                            <div class="relative mt-1">
                                <input type="date" id="edd_<?= (int)$pl['id']; ?>"
                                    value="<?= htmlspecialchars($pl['expected_time_of_delivery']); ?>"
                                    class="border rounded px-3 py-2 pr-10 text-sm w-full bg-white" />
                                <button type="button"
                                    class="absolute inset-y-0 right-2 flex items-center text-amber-500"
                                    onclick="(function(btn){ const input = btn.parentElement.querySelector('input[type=date]'); if(input.showPicker) input.showPicker(); else input.focus(); })(this)">
                                    📅
                                </button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Comments -->
                    <div class="mt-3">
                        <button type="button"
                            class="text-xs text-blue-600 hover:underline"
                            onclick="toggleComments(<?= (int)$pl['id']; ?>)">
                            Comments
                        </button>

                        <div id="commentsWrap_<?= (int)$pl['id']; ?>" class="mt-2">
                            <div id="commentsThread_<?= (int)$pl['id']; ?>" class="space-y-2 text-xs"></div>                            
                        </div>
                        <input id="commentInput_<?= (int)$pl['id']; ?>"
                                class="w-full border rounded px-2 py-1 mt-2 text-xs"
                                placeholder="Write a comment..." />
                    </div>

                    <!-- Footer Buttons -->
                    <div class="mt-4 flex justify-end gap-2 text-xs">
                        <button onclick="savePurchaseItem(<?= (int)$pl['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded">
                            Save
                        </button>

                        <?php /*if ($pl['status'] === 'pending'): ?>
                            <button onclick="markAsPurchased(<?= (int)$pl['id']; ?>)" class="px-3 py-1 bg-amber-600 text-white rounded">
                                Mark Purchased
                            </button>
                        <?php else: ?>
                            <button onclick="markUnpurchased(<?= (int)$pl['id']; ?>)" class="px-3 py-1 bg-red-600 text-white rounded">
                                Mark Unpurchased
                            </button>
                        <?php endif; */ ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <div class="mt-6 flex items-center justify-center space-x-2">
            <?php $page_no = $data['page_no'] ?? 1;
            $total_pages = $data['total_pages'] ?? 1;
            $limit = $data['limit'] ?? 50;
            $query_string = '';
            // preserve existing filters in query string (if any)
            $qs = $_GET;
            unset($qs['page_no'], $qs['limit']);
            $query_string = http_build_query($qs);
            $query_string = $query_string ? '&' . $query_string : '';
            ?>
            <?php if ($page_no > 1): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo max(1, $page_no - 1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">&laquo; Prev</a>
            <?php endif; ?>
            <span class="px-3 py-1 text-sm">Page <?php echo $page_no; ?> of <?php echo $total_pages; ?></span>
            <?php if ($page_no < $total_pages): ?>
                <a href="?page=products&action=purchase_list&page_no=<?php echo min($total_pages, $page_no + 1); ?>&limit=<?php echo $limit . $query_string; ?>" class="px-3 py-1 border rounded">Next &raquo;</a>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <div class="bg-white rounded-lg shadow-sm p-6 text-center text-gray-600">No items in purchase list.</div>
    <?php endif; ?>
</div>

<script>
    /*document.addEventListener("DOMContentLoaded", () => {
    <?php foreach ($data['purchase_list'] as $pl): ?>
        loadComments(<?= (int)$pl['id']; ?>).then(() => {
            const t = document.getElementById(`commentsThread_<?= (int)$pl['id']; ?>`);
            if (t) t.dataset.loaded = "1";
        });
    <?php endforeach; ?>
});*/
</script>
<script src="<?php echo base_url('assets/js/purchase_list.js'); ?>"></scrip