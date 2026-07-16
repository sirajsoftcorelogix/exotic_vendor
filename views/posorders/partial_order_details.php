<div class="flex-grow ">
    <?php
    $total_price = 0;
    $courrency = '';
    foreach ($order as $items => $item):
        $total_price += $item['finalprice'] * $item['quantity'];
        $currency = $item['currency'];
    endforeach;
    ?>
    <div class="flex-grow space-4 bg-white p-6">
        <div class="max-w-4xl mx-auto text-white rounded-lg grid grid-cols-2 p-4" style="background-color: rgba(208, 103, 6, 1);">
            <div>
                <?php /* $link = base_url('index.php?page=orders&action=get_order_details_html&type=outer&order_number='.$order[0]['order_number']); */ ?>
                <?php $link = 'index.php?page=posorders&action=get_order_details_html&type=outer&order_number=' . $order[0]['order_number']; ?>
                <p><span class="font-bold">Order number : </span><span class="inline-flex items-center gap-1">
                    <a href="<?php echo $link; ?>" class="text-blue-600 hover:underline" target="_blank"><?php echo htmlspecialchars($order[0]['order_number']); ?></a>
                    <?php if (!empty($canEditOrderNumber)): ?>
                        <button type="button"
                            onclick="openOrderNumberEditPopup('<?php echo htmlspecialchars($order[0]['order_number'], ENT_QUOTES); ?>')"
                            class="inline-flex items-center justify-center rounded p-0.5 text-gray-500 hover:text-blue-600"
                            title="Edit order number">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </button>
                    <?php endif; ?>
                </span></p>
                <p><span class="font-bold">Order Date : </span><span class=""><?php echo date('d-M-Y', strtotime($order[0]['order_date'])); ?></span>
                </p>
            </div>
            <div>
                <p><span class="font-bold">Total Order Value : </span><span class=""><?php echo number_format($total_price, 2); ?> <?php echo $currency; ?></span></p>
                <p><span class="font-bold">Payment Mode : </span><span class=""><?php echo $order[0]['payment_type']; ?></span></p>
            </div>
        </div>
    </div>
    <div class="flex-grow space-4 p-4 bg-white">
        <?php
        //print_array($order);
        $countries = country_array();
        //print_array($countries);
        foreach ($order as $items => $item): ?>
            <!-- Accordion Item 1 -->
            <div>
                <div class="accordion-trigger cursor-pointer border-b pb-4">
                    <div class="flex items-start space-x-4">
                        <div class="flex-shrink-0 w-36 h-36">
                            <img src="<?php echo $item['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image'; ?>" alt="Product Image"
                                class="max-w-full max-h-full object-contain rounded-lg object-cover flex-shrink-0">
                        </div>
                        <div class="flex-grow">
                            <div>
                                <p> <span class="section-value"><?php echo $item['groupname']; ?> / <?php echo $item['subcategories']; ?></span></p>
                            </div>
                            <div class="flex justify-between items-start">

                                <h3 class="item-title pr-4"><?php echo $item['title']; ?></h3>
                                <div class="flex flex-col items-center space-y-2 flex-shrink-0 ml-4">
                                    <!-- <button class="text-gray-500 hover:text-gray-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                                 viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path>
                                            </svg>
                                        </button>
                                        <button class="text-gray-500 hover:text-gray-800">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
                                            </svg>
                                        </button> -->
                                </div>
                            </div>
                            <p class="item-meta mt-0">Item Code: <?php echo $item['item_code']; ?></p>
                            <p class="item-meta mt-0">Quantity: <?php echo $item['quantity']; ?></p>
                            <div class="flex justify-between items-center mt-3">
                                <div class="status-box flex items-center justify-center">
                                    <span class="status-text"><?php echo $statusList[$item['status']] ?? 'Unknown'; ?></span>
                                </div>
                                <div class="status-box w-48 flex items-center justify-center">
                                    <span class="status-text">Ship <?php echo $item['status'] == 'shipped' ? 'on' : ' by'; ?> : <?php echo $item['esd'] ? date('d M Y', strtotime($item['esd'])) : ' -'; ?></span>
                                </div>
                                <div class="flex space-x-3 justify-end">
                                    <?php if ($item['po_number']): ?>
                                        <span class="po-button"><a href="<?php echo base_url('?page=purchase_orders&action=view&po_id=' . $item['po_id']); ?>"><?php echo $item['po_number']; ?></a></span>
                                    <?php else: ?>
                                        <form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post">
                                            <input type="hidden" name="poitem[]" id="poitem_order_id" value="<?php echo $item['id'] ?>">
                                            <button type="submit" class="po-button">Create PO</button>
                                        </form>
                                    <?php endif; ?>
                                    <!-- <span class="shipping-button">Express Shipping</span> -->
                                </div>
                            </div>


                        </div>
                    </div>
                </div>
                <!-- Accordion Content -->
                <div class="accordion-content-details p-4 rounded-b-lg space-y-4">
                    <!-- Item Details -->
                    <div class="p-1 rounded-lg" style="background-color: rgba(245, 245, 245, 1);">
                        <div class="grid grid-cols-2 gap-x-1 items-end">
                            <!-- <div class="space-y-1">
                                    <p><span class="item-detail-title">Item Code : </span><span
                                            class="item-detail-value"><?php //echo $item['item_code']; 
                                                                        ?></span></p>
                                    <p><span class="item-detail-title">Material : </span><span
                                            class="item-detail-value"><?php //echo $item['material']; 
                                                                        ?></span></p>
                                    <p><span class="item-detail-title">Color : </span><span class="item-detail-value"><?php echo $item['color']; ?></span>
                                    </p>
                                    <p><span class="item-detail-title">Size : </span><span
                                            class="item-detail-value"><?php //echo $item['size']; 
                                                                        ?></span></p>
                                    <p><span class="item-detail-title">Shipping Country : </span><span
                                            class="item-detail-value"><?php //echo $item['shipping_country']; 
                                                                        ?></span></p>
                                    <p><span class="item-detail-title">EST : </span><span class="item-detail-value"><?php echo $item['esd']; ?></span>
                                    </p>
                                </div> -->

                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><strong class="section-title">Shipping Country : </strong><span class="section-value"><?php echo $countries[$item['shipping_country']] ?? ''; ?></span>
                            </p>
                        </div>
                        <div>
                            <p><strong class="section-title">Billing Country : </strong><span class="section-value"><?php echo $countries[$item['country']] ?? ''; ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Marketplace -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Marketplace : </span><span
                                    class="section-value"><?php echo $item['marketplace_vendor']; ?></span></p>
                        </div>
                    </div>
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <?php if (!empty($order['author'])) { ?>
                            <div>
                                <p><strong class="section-title">Author : </strong><span class="section-value"><?php echo $item['author']; ?></span>
                                </p>
                                <p><strong class="section-title">Publisher : </strong><span class="section-value"><?php echo $item['publisher']; ?></span>
                                </p>
                            </div>
                            <div>
                                <p><strong class="section-title">shippingfee : </strong><span class="section-value"><?php echo $item['shippingfee'] ?? 'N/A'; ?></span>
                                </p>
                                <p><strong class="section-title">sourcingfee : </strong><span class="section-value"><?php echo $item['sourcingfee'] ?? 'N/A'; ?></span>
                                </p>
                            </div>
                        <?php } else { ?>
                            <div>
                                <p><strong class="section-title">Color : </strong><span class="section-value"><?php echo $item['color']; ?></span>
                                </p>
                                <p><strong class="section-title">Size : </strong><span class="section-value"><?php echo $item['size']; ?></span>
                                </p>
                            </div>
                            <div>
                                <p><strong class="section-title">Material : </strong><span class="section-value"><?php echo $item['material']; ?></span>
                                </p>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="bg-green-200 p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Addons : </span><span class="section-value"><?php $options = json_decode($item['options'], true);
                                                                                                        echo implode(', ', $options); ?></span>
                            </p>
                        </div>
                    </div>
                    <!-- Stock -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Local Stock : </span><span
                                    class="section-value"><?php echo $item['local_stock']; ?></span></p>
                            <p><span class="section-title">Location : </span><span class="section-value"><?php echo $item['location']; ?></span>
                            </p>
                        </div>
                        <div>
                            <p><span class="section-title">Sold Quantity : </span><span
                                    class="section-value"><?php echo $item['numsold']; ?></span></p>
                            <p><span class="section-title">Order Quantity : </span><span
                                    class="section-value"><?php echo $item['quantity']; ?></span></p>
                        </div>
                    </div>
                    <!-- Pricing -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>

                            <p><span class="section-title">Item Price : </span><span
                                    class="section-value"><?php echo $item['itemprice']; ?></span></p>
                            <p><span class="section-title">Final Price : </span><span
                                    class="section-value"><?php echo $item['finalprice']; ?></span></p>
                            <p><span class="section-title">Currency : </span><span
                                    class="section-value"><?php echo $item['currency']; ?></span></p>
                            <p><span class="section-title">item Total : </span><span
                                    class="section-value"><?php echo $item['finalprice'] * $item['quantity']; ?></span></p>

                        </div>
                        <div>
                            <p><span class="section-title">HSN Code : </span><span
                                    class="section-value"><?php echo $item['hsn']; ?></span></p>
                            <p><span class="section-title">GST : </span><span class="section-value"><?php echo $item['gst']; ?></span></p>
                            <p><span class="section-title">Credit : </span><span class="section-value"><?php echo $item['credit']; ?></span></p>
                            <p><span class="section-title">Payment Type : </span><span
                                    class="section-value"><?php echo $item['payment_type']; ?></span></p>
                            <p><span class="section-title">Credit : </span><span
                                    class="section-value"><?php echo $item['credit']; ?></span></p>
                        </div>
                    </div>
                    <!-- Coupon -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Coupon : </span><span class="section-value"><?php echo $item['coupon']; ?></span>
                            </p>
                            <p><span class="section-title">Coupon Reduce : </span><span
                                    class="section-value"><?php echo $item['coupon_reduce']; ?></span></p>
                            <p><span class="section-title">Custom Reduce : </span><span class="section-value"><?php echo $item['custom_reduce']; ?></span></p>
                        </div>
                        <div>
                            <p><span class="section-title">Gift Voucher : </span><span
                                    class="section-value"><?php echo $item['giftvoucher']; ?></span></p>
                            <p><span class="section-title">Gift Voucher Reduce : </span><span class="section-value"><?php echo $item['giftvoucher_reduce']; ?></span>
                            </p>
                        </div>
                    </div>
                    <!-- Backorder -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Backorder Status : </span><span
                                    class="section-value"><?php echo $item['backorder_status']; ?></span></p>
                            <p><span class="section-title">Backorder Percentage : </span><span
                                    class="section-value"><?php echo $item['backorder_percent']; ?></span></p>
                        </div>
                        <div>
                            <p><span class="section-title">Backorder Delay : </span><span class="section-value"><?php echo $item['backorder_delay']; ?></span>
                            </p>
                            <p><span class="section-title">Expected Delivery Date : </span><span
                                    class="section-value"><?php echo $item['delivery_due_date']; ?></span></p>
                        </div>
                    </div>
                    <!-- Dimensions -->
                    <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                        <div>
                            <p><span class="section-title">Product Weight : </span><span class="section-value"><?php echo $item['product_weight'] . $item['product_weight_unit']; ?></span>
                            </p>
                            <p><span class="section-title">Product Height : </span><span class="section-value"><?php echo $item['prod_height']; ?></span>
                            </p>
                        </div>
                        <div>
                            <p><span class="section-title">Product Length : </span><span class="section-value"><?php echo $item['prod_length'] . $item['length_unit']; ?></span>
                            </p>
                            <p><span class="section-title">Product Width : </span><span class="section-value"><?php echo $item['prod_width'] . $item['length_unit']; ?></span>
                            </p>
                        </div>
                    </div>

                    <!-- Notes -->
                    <div class="bg-white p-4 rounded-lg">
                        <p class="notes-title">Notes :</p>
                        <p class="notes-text mt-1"><?php echo $item['remarks']; ?> </p>
                    </div>
                </div>
                <!-- /Accordion Content -->
            </div>
        <?php endforeach; ?>
    </div>
<?php if (!empty($canEditOrderNumber)): ?>
<div id="innerOrderNumberEditPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-[10000] p-4">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4 p-6 relative">
        <button type="button" onclick="closeInnerOrderNumberEditPopup()" class="absolute top-3 right-4 text-gray-500 hover:text-gray-800">&times;</button>
        <h2 class="text-lg font-bold mb-4">Edit Order Number</h2>
        <form id="innerOrderNumberEditForm">
            <input type="hidden" id="inner_old_order_number" name="old_order_number">
            <input type="text" id="inner_new_order_number" name="new_order_number" class="w-full px-3 py-2 border border-gray-300 rounded-md" required>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" onclick="closeInnerOrderNumberEditPopup()" class="px-4 py-2 bg-gray-200 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded">Save</button>
            </div>
        </form>
    </div>
</div>
<script>
    function openOrderNumberEditPopup(orderNumber) {
        document.getElementById('inner_old_order_number').value = orderNumber;
        document.getElementById('inner_new_order_number').value = orderNumber;
        document.getElementById('innerOrderNumberEditPopup').classList.remove('hidden');
        document.getElementById('inner_new_order_number').focus();
    }
    function closeInnerOrderNumberEditPopup() {
        document.getElementById('innerOrderNumberEditPopup').classList.add('hidden');
    }
    document.getElementById('innerOrderNumberEditForm')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const oldOrderNumber = document.getElementById('inner_old_order_number').value.trim();
        const newOrderNumber = document.getElementById('inner_new_order_number').value.trim();
        const formData = new FormData();
        formData.append('old_order_number', oldOrderNumber);
        formData.append('new_order_number', newOrderNumber);
        fetch('index.php?page=posorders&action=update_order_number_ajax', { method: 'POST', body: formData })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    alert(data.message || 'Could not update order number.');
                    return;
                }
                closeInnerOrderNumberEditPopup();
                window.location.href = `index.php?page=posorders&action=get_order_details_html&type=outer&order_number=${encodeURIComponent(data.order_number || newOrderNumber)}`;
            })
            .catch(() => alert('Request failed.'));
    });
</script>
<?php endif; ?>