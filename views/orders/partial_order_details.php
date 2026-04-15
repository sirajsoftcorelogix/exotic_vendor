<?php
/**
 * Order line items for AJAX modal (type=inner). Loaded into #details-modal-content.
 * Keep markup self-contained; parent already provides scroll + padding.
 */
$orderremarks = is_array($orderremarks ?? null) ? $orderremarks : [];
$currency = '';
foreach ($order as $items => $item) {
    $currency = $item['currency'] ?? $currency;
}
$countries = country_array();
?>
<div class="order-details-modal-root max-w-full text-gray-800 -mt-2">
    <!-- Order summary (compact for drawer width) -->
    <div class="rounded-xl border border-amber-200/80 bg-gradient-to-br from-amber-600 to-amber-700 text-white shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 sm:gap-4 text-sm">
            <div class="space-y-1.5">
                <?php $link = 'index.php?page=orders&action=get_order_details_html&type=outer&order_number=' . $order[0]['order_number']; ?>
                <p class="font-semibold text-amber-50/90 text-xs uppercase tracking-wide">Order</p>
                <p class="text-base font-semibold">
                    <a href="<?php echo htmlspecialchars($link); ?>" class="text-white underline decoration-white/40 hover:decoration-white" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars((string)$order[0]['order_number']); ?></a>
                </p>
                <p class="text-amber-50/95"><span class="font-medium text-white/90">Date:</span> <?php echo htmlspecialchars(date('d M Y', strtotime($order[0]['order_date']))); ?></p>
            </div>
            <div class="space-y-1.5 sm:text-right">
                <p class="font-semibold text-amber-50/90 text-xs uppercase tracking-wide sm:text-right">Totals</p>
                <p><span class="font-medium text-white/90">Order value:</span> <?php echo htmlspecialchars(number_format((float)($orderremarks['total'] ?? 0), 2)); ?> <?php echo htmlspecialchars((string)$currency); ?></p>
                <p><span class="font-medium text-white/90">Payment:</span> <?php echo htmlspecialchars((string)($order[0]['payment_type'] ?? '')); ?></p>
            </div>
        </div>
    </div>

    <div class="mt-5 space-y-5">
        <?php foreach ($order as $items => $item): ?>
            <div class="border border-gray-200/90 rounded-xl overflow-hidden bg-white shadow-md ring-1 ring-black/[0.04]">
                <div class="accordion-trigger cursor-pointer p-3 sm:p-4 hover:bg-amber-50/40 transition-colors border-b border-gray-200">
                    <div class="flex gap-3 sm:gap-4">
                        <div class="flex-shrink-0 w-20 h-20 sm:w-24 sm:h-24 rounded-lg border border-gray-100 bg-gray-50 overflow-hidden">
                            <img src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image'); ?>" alt=""
                                 class="w-full h-full object-contain">
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs text-gray-500 truncate"><?php echo htmlspecialchars((string)($item['groupname'] ?? '')); ?> / <?php echo htmlspecialchars((string)($item['subcategories'] ?? '')); ?></p>
                            <h3 class="item-title text-sm sm:text-[15px] leading-snug mt-0.5 line-clamp-3"><?php echo htmlspecialchars((string)($item['title'] ?? '')); ?></h3>
                            <p class="item-meta mt-1">Code: <?php echo htmlspecialchars((string)($item['item_code'] ?? '')); ?> · Qty <?php echo htmlspecialchars((string)($item['quantity'] ?? '')); ?></p>
                            <p class="vendor-text mt-1 text-xs sm:text-[13px]"><strong class="vendor-title">Vendor:</strong> <span class="vendor-name"><?php echo htmlspecialchars((string)($item['vendor'] ?? '')); ?></span></p>
                            <div class="flex flex-wrap gap-2 items-center mt-2.5">
                                <div class="status-box flex items-center justify-center px-2">
                                    <span class="status-text text-xs"><?php echo htmlspecialchars((string)($statusList[$item['status']] ?? 'Unknown')); ?></span>
                                </div>
                                <div class="status-box flex items-center justify-center px-2 !w-auto min-w-0 max-w-[11rem] sm:max-w-none sm:!w-48">
                                    <span class="status-text text-[11px] sm:text-sm whitespace-nowrap truncate">Ship <?php echo $item['status'] === 'shipped' ? 'on' : 'by'; ?>: <?php echo !empty($item['esd']) ? htmlspecialchars(date('d M Y', strtotime($item['esd']))) : '—'; ?></span>
                                </div>
                                <div class="flex flex-wrap gap-2 ml-auto">
                                    <?php if (!empty($item['po_number'])): ?>
                                        <span class="po-button text-xs"><a class="text-amber-800 hover:underline" href="<?php echo htmlspecialchars(base_url('?page=purchase_orders&action=view&po_id=' . (int)$item['po_id'])); ?>"><?php echo htmlspecialchars((string)$item['po_number']); ?></a></span>
                                    <?php else: ?>
                                        <form action="<?php echo htmlspecialchars(base_url('?page=purchase_orders&action=create')); ?>" method="post" class="inline">
                                            <input type="hidden" name="poitem[]" value="<?php echo (int)$item['id']; ?>">
                                            <button type="submit" class="po-button text-xs px-2.5 py-1.5 rounded-md font-medium bg-amber-600 text-white border border-amber-700 shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-1">Create PO</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="accordion-content-details px-3 sm:px-4 pb-4 space-y-3 rounded-b-xl">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3">
                        <div class="rounded-lg border border-gray-100 bg-gray-50/90 p-3">
                            <p class="section-title block mb-1">Shipping country</p>
                            <span class="section-value text-gray-900"><?php echo htmlspecialchars((string)($countries[$item['shipping_country']] ?? '')); ?></span>
                        </div>
                        <div class="rounded-lg border border-gray-100 bg-gray-50/90 p-3">
                            <p class="section-title block mb-1">Billing country</p>
                            <span class="section-value text-gray-900"><?php echo htmlspecialchars((string)($countries[$item['country']] ?? '')); ?></span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-100 p-3">
                            <p><span class="section-title">Marketplace</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['marketplace_vendor'] ?? '')); ?></span></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 p-3">
                            <p><span class="section-title">Vendor</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['vendor'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <?php if (!empty($item['author']) || !empty($item['publisher'])): ?>
                            <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                                <p><span class="section-title">Author</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['author'] ?? '')); ?></span></p>
                                <p><span class="section-title">Publisher</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['publisher'] ?? '')); ?></span></p>
                            </div>
                            <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                                <p><span class="section-title">Shipping fee</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['shippingfee'] ?? 'N/A')); ?></span></p>
                                <p><span class="section-title">Sourcing fee</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['sourcingfee'] ?? 'N/A')); ?></span></p>
                            </div>
                        <?php else: ?>
                            <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                                <p><span class="section-title">Color</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['color'] ?? '')); ?></span></p>
                                <p><span class="section-title">Size</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['size'] ?? '')); ?></span></p>
                            </div>
                            <div class="rounded-lg border border-gray-100 p-3">
                                <p><span class="section-title">Material</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['material'] ?? '')); ?></span></p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-3">
                        <?php
                        $options = json_decode($item['options'] ?? '[]', true);
                        $optStr = is_array($options) ? implode(', ', $options) : '';
                        ?>
                        <p><span class="section-title">Addons</span><br><span class="section-value"><?php echo htmlspecialchars($optStr); ?></span></p>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Local stock</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['local_stock'] ?? '')); ?></span></p>
                            <p><span class="section-title">Location</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['location'] ?? '')); ?></span></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Sold quantity</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['numsold'] ?? '')); ?></span></p>
                            <p><span class="section-title">Order quantity</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['quantity'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 p-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <p><span class="section-title">Item price</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['itemprice'] ?? '')); ?></span></p>
                            <p><span class="section-title">Final price</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['finalprice'] ?? '')); ?></span></p>
                            <p><span class="section-title">Currency</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['currency'] ?? '')); ?></span></p>
                            <p><span class="section-title">Line total</span><br><span class="section-value"><?php echo htmlspecialchars((string)((float)($item['finalprice'] ?? 0) * (int)($item['quantity'] ?? 0))); ?></span></p>
                        </div>
                        <div class="space-y-1">
                            <p><span class="section-title">HSN code</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['hsn'] ?? '')); ?></span></p>
                            <p><span class="section-title">GST</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['gst'] ?? '')); ?></span></p>
                            <p><span class="section-title">Credit</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['credit'] ?? '')); ?></span></p>
                            <p><span class="section-title">Payment type</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['payment_type'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Coupon</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['coupon'] ?? '')); ?></span></p>
                            <p><span class="section-title">Coupon reduce</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['coupon_reduce'] ?? '')); ?></span></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Gift voucher</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['giftvoucher'] ?? '')); ?></span></p>
                            <p><span class="section-title">Gift voucher reduce</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['giftvoucher_reduce'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Backorder status</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['backorder_status'] ?? '')); ?></span></p>
                            <p><span class="section-title">Backorder %</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['backorder_percent'] ?? '')); ?></span></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Backorder delay</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['backorder_delay'] ?? '')); ?></span></p>
                            <p><span class="section-title">Expected delivery</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['delivery_due_date'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Product weight</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['product_weight'] ?? '') . (string)($item['product_weight_unit'] ?? '')); ?></span></p>
                            <p><span class="section-title">Product height</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['prod_height'] ?? '')); ?></span></p>
                        </div>
                        <div class="rounded-lg border border-gray-100 p-3 space-y-1">
                            <p><span class="section-title">Product length</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['prod_length'] ?? '') . (string)($item['length_unit'] ?? '')); ?></span></p>
                            <p><span class="section-title">Product width</span><br><span class="section-value"><?php echo htmlspecialchars((string)($item['prod_width'] ?? '') . (string)($item['length_unit'] ?? '')); ?></span></p>
                        </div>
                    </div>

                    <div class="rounded-lg border border-gray-100 bg-gray-50/80 p-3">
                        <p class="notes-title font-semibold text-gray-700 text-sm">Notes</p>
                        <p class="notes-text mt-1 text-sm text-gray-600 leading-relaxed"><?php echo nl2br(htmlspecialchars((string)($item['remarks'] ?? ''))); ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
