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

                $orderLink = '';
                if (isset($pl['order_number']) && !empty($pl['order_number'])) {
                    $orderLink = base_url('index.php?order_number=' . $pl['order_number']); // adjust id field
                }

                $cost = isset($pl['cost_price']) ? '₹' . number_format((float)$pl['cost_price']) : '';
                $status = str_replace("_", " ", $pl['status']) ?? '';
                $agent_name = $pl['agent_name'] ?? '';
                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : '');
                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : '');
                
                $product = $pl['product'] ?? [];
                $details = [];
                if (!empty($product['color']) && $product['color'] !== '-') {
                    $details[] = 'Color: ' . $product['color'];
                }
                if (!empty($product['size']) && $product['size'] !== '-') {
                    $details[] = 'Size: ' . $product['size'];
                }
                if (!empty($product['material']) && $product['material'] !== '-') {
                    $details[] = 'Material: ' . $product['material'];
                }
                if (!empty($product['product_weight'])) {
                    $weight = $product['product_weight'];
                    $unit   = $product['product_weight_unit'] ?? '';
                    $details[] = 'Weight: ' . trim($weight . ' ' . $unit);
                }

                // Dimensions (only if at least one exists)
                $height = $product['prod_height'] ?? null;
                $width  = $product['prod_width'] ?? null;
                $length = $product['prod_length'] ?? null;

                if ($height || $width || $length) {
                    $dims = array_filter([$height, $width, $length]);
                    $details[] = 'Dimensions: ' . implode(' × ', $dims) . ' in';
                }
                         
                $productDetailsText = implode(', ', $details);
                // Build the share URL
                //$shareUrl = full_url('share.php?id=' . base64_encode((int)$pl['product_id']).'&v='.time());                                
                // Alternative: Even simpler approach (recommended)
                //$waHrefSimple = "https://wa.me/?text=" . urlencode($shareUrl);

                // Product details text (already built earlier)
                $productDetailsText = trim($productDetailsText);

                // Build share URL
                $shareUrl = full_url(
                    'share.php?id=' . base64_encode((int)$pl['product_id']) . '&v=' . time()
                );

                // Combine text + link (WhatsApp-friendly)
                $whatsappMessage = "". $productDetailsText
                    . "\n\n🔗 View Product\n"
                    . $shareUrl;

                // Encode ONCE at the end
                $waHrefSimple = 'https://wa.me/?text=' . urlencode($whatsappMessage);


            ?>
            <div class="bg-white border border-gray-200 rounded-xl shadow-md p-4 flex flex-col h-full relative">
                <!-- hidden fields -->
                <input type="hidden" id="productId_<?= (int)$pl['id']; ?>" value="<?= (int)$pl['product_id']; ?>"/>
                <input type="hidden" id="minStock_<?= (int)$pl['id']; ?>" value="<?= $pl['product']['min_stock'] ?? ''; ?>" />

                <!-- HEADER -->
                <div class="flex gap-4 relative">

                    <!-- SHARE -->
                    <a href="<?= $waHrefSimple ?>"
                        target="_blank"
                        rel="noopener"
                        class="absolute top-0 right-0 w-8 h-8 flex items-center justify-center
                                rounded-full bg-white 
                                text-amber-600 hover:bg-amber-50 hover:border-yellow-400
                                shadow-sm transition">

                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="w-6 h-6"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24">
                                <path d="M4 12v7a1 1 0 001 1h14a1 1 0 001-1v-7" />
                                <path d="M12 3l5 5h-3v6h-4V8H7l5-5z" />
                            </svg>
                        </a>


                    <!-- IMAGE -->
                    <img src="<?= htmlspecialchars($image); ?>"
                        onclick="openImagePopup('<?= htmlspecialchars($image); ?>')"
                        class="w-28 h-30 rounded-xl object-cover border cursor-pointer"/>

                    <!-- BASIC INFO -->
                    <div class="flex-1 space-y-1">
                        <h3 class="text-base font-semibold text-gray-900 leading-snug">
                            <?= htmlspecialchars($title); ?>
                        </h3>

                        <div class="text-base text-gray">
                            Item Code:
                            <span class="font-medium text-gray-800">
                                <?= htmlspecialchars($item_code); ?>
                            </span>
                        </div>

                        <?php if(!empty($orderLink)) { ?>
                            <div class="text-base">
                                Order:
                                <a href="<?= htmlspecialchars($orderLink); ?>" target="_blank"
                                class="text-blue-600 font-medium hover:underline">
                                    <?= htmlspecialchars($pl['order_number']); ?>
                                </a>
                            </div>
                        <?php } ?>

                        <!-- STATUS -->
                        <span class="inline-block mt-2 px-2 py-0.5 rounded-full text-[11px]
                            <?= $status === 'purchased'
                                ? 'bg-green-100 text-green-700'
                                : ($status === 'partially_purchased'
                                    ? 'bg-amber-100 text-amber-700'
                                    : 'bg-gray-100 text-gray-600'); ?>">
                            <?= ucwords(str_replace('_',' ',$status)); ?>
                        </span>
                    </div>
                </div>

                <!-- DETAILS GRID -->
                <div class="mt-4 grid grid-cols-[140px_12px_1fr] gap-x-2 gap-y-2 text-base">

                    <div class="text-gray">Added By</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($pl['added_by']); ?></div>

                    <div class="text-gray">Added On</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($date_added); ?></div>

                    <?php if (!empty($pl['vendor']) && strtoupper(trim($pl['vendor'])) !== 'N/A'): ?>
                        <div class="text-gray">Vendor</div>
                        <div class="text-gray">:</div>
                        <div class="font-medium"><?= ucwords($pl['vendor']); ?></div>
                    <?php endif; ?>

                    <div class="text-gray">SKU</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($pl['sku']); ?></div>

                    <div class="text-gray">Color</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($pl['product']['color'] ?? '-'); ?></div>

                    <div class="text-gray">Size</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($pl['product']['size'] ?? '-'); ?></div>

                    <div class="text-gray">Material</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium"><?= htmlspecialchars($pl['product']['material'] ?? '-'); ?></div>

                    <div class="text-gray">Weight</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium">
                        <?= htmlspecialchars($pl['product']['product_weight'] ?? ''); ?>
                        <?= htmlspecialchars($pl['product']['product_weight_unit'] ?? ''); ?>
                    </div>

                    <div class="text-gray">Dimensions</div>
                    <div class="text-gray">:</div>
                    <div class="font-medium">
                        <?= htmlspecialchars($pl['product']['prod_height'] ?? ''); ?> ×
                        <?= htmlspecialchars($pl['product']['prod_width'] ?? ''); ?> ×
                        <?= htmlspecialchars($pl['product']['prod_length'] ?? ''); ?> in
                    </div>

                    <?php if ($status === 'purchased'): ?>
                        <div class="text-gray">Purchased By</div>
                        <div class="text-gray">:</div>
                        <div class="font-medium"><?= htmlspecialchars($agent_name); ?></div>

                        <div class="text-gray">Purchased On</div>
                        <div class="text-gray">:</div>
                        <div class="font-medium"><?= htmlspecialchars($date_purchased); ?></div>
                    <?php endif; ?>
                </div>



                <!-- PURCHASE CONTROLS -->
                <?php
                    $isPurchased = $pl['status'] === 'purchased';
                    $pQty = (int)($pl['quantity'] ?? 0);
                ?>
                <div class="mt-4 grid grid-cols-2 gap-3 text-base">

                    <div>
                        <label class="text-gray">Qty To Purchase</label>
                        <div class="mt-1 bg-gray-100 border rounded px-2 py-1 text-center font-semibold">
                            <?= $pQty; ?>
                        </div>
                    </div>

                    <div>
                        <label class="text-gray">Qty Purchased</label>
                        <input type="number" min="1" step="1"
                            onblur="checkMinStock(<?= (int)$pl['id']; ?>)"
                            id="quantity_<?= (int)$pl['id']; ?>"
                            value="<?= $isPurchased ? $pQty : ''; ?>"
                            class="mt-1 w-full border rounded px-2 py-1
                                    <?= $isPurchased ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>"
                            <?= $isPurchased ? 'disabled' : '' ?> />
                    </div>

                    <div class="col-span-2 text-base">
                        <label class="text-gray">Status</label>
                        <select id="status_<?= (int)$pl['id']; ?>"
                                class="mt-1 w-full border rounded px-2 py-1
                                    <?= $isPurchased ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>"
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

                <!-- COMMENTS -->
                <div class="mt-4">
                    <button type="button"
                            class="text-base text-blue-600 hover:underline"
                            onclick="toggleComments(<?= (int)$pl['id']; ?>)">
                        Comments
                    </button>

                    <div id="commentsWrap_<?= (int)$pl['id']; ?>" class="mt-2">
                        <div id="commentsThread_<?= (int)$pl['id']; ?>" class="space-y-2 text-xs"></div>
                    </div>

                    <input id="commentInput_<?= (int)$pl['id']; ?>"
                        class="mt-2 w-full border rounded px-2 py-1 text-base"
                        placeholder="Write a comment..." />
                </div>

                <!-- FOOTER -->
                <div class="mt-5 flex justify-end">
                    <button onclick="savePurchaseItem(<?= (int)$pl['id']; ?>)"
                            class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-base     font-medium">
                        Save
                    </button>
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
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<div id="errorModal" class="fixed inset-0 hidden bg-black bg-opacity-40 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 w-96 shadow-lg">
        <h3 class="text-lg font-semibold text-red-600 mb-2">Invalid Quantity</h3>
        <p id="errorMessage" class="text-gray-700 mb-4"></p>
        <button onclick="closeErrorModal()"
                class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
            OK
        </button>
    </div>
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
<script src="<?php echo base_url('assets/js/purchase_list.js'); ?>"></script>