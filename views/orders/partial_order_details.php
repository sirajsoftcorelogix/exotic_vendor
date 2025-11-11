<?php 
    //print_array($order);
	$countries = country_array();
	//print_array($countries);
    foreach ($order as $items => $item):?>
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
                                <p class="vendor-text mt-2"><strong class="vendor-title">Vendor :</strong> <span
                                        class="vendor-name"><?php echo $item['vendor']; ?></span></p>
                                <div class="flex justify-between items-center mt-3">
                                    <div class="status-box flex items-center justify-center">
                                        <span class="status-text"><?php echo $statusList[$item['status']] ?? 'Unknown'; ?></span>
                                    </div>
                                    <div class="status-box w-48 flex items-center justify-center">
                                        <span class="status-text">Shipped <?php echo $item['status'] == 'shipped' ? 'on' : ' by'; ?> : <?php echo $item['esd'] ? date('d M Y', strtotime($item['esd'])) : ' -'; ?></span>
                                    </div>
                                    <div class="flex space-x-3 justify-end">  
                                        <?php if($item['po_number']): ?>
                                        <span class="po-button"><?php echo $item['po_number']; ?></span>
                                        <?php else: ?>
                                        <form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post">
                                        <input type="hidden" name="poitem[]" id="poitem_order_id" value="<?php echo $item['id']?>">                                    
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
                                            class="item-detail-value"><?php //echo $item['item_code']; ?></span></p>
                                    <p><span class="item-detail-title">Material : </span><span
                                            class="item-detail-value"><?php //echo $item['material']; ?></span></p>
                                    <p><span class="item-detail-title">Color : </span><span class="item-detail-value"><?php echo $item['color']; ?></span>
                                    </p>
                                    <p><span class="item-detail-title">Size : </span><span
                                            class="item-detail-value"><?php //echo $item['size']; ?></span></p>
                                    <p><span class="item-detail-title">Shipping Country : </span><span
                                            class="item-detail-value"><?php //echo $item['shipping_country']; ?></span></p>
                                    <p><span class="item-detail-title">EST : </span><span class="item-detail-value"><?php echo $item['esd']; ?></span>
                                    </p>
                                </div> -->
                                
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                            <div>
                                <p><strong class="section-title">Shipping Country : </strong><span class="section-value"><?php echo $countries[$item['shipping_country']]; ?></span>
                                </p>                                
                            </div>
                            <div>
                                <p><strong class="section-title">Billing Country : </strong><span class="section-value"><?php echo $countries[$item['country']]; ?></span>
                                </p>
                            </div>
                        </div>
                        
                        <!-- Marketplace -->
                        <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
                            <div><p><span class="section-title">Marketplace : </span><span
                                    class="section-value"><?php echo $item['marketplace_vendor']; ?></span></p>
                            </div>
                            <div>
                                    <p><span class="section-title">Vendor : </span><span class="section-value"><?php echo $item['vendor']; ?></span></p>
                            </div>
                        </div>
                        <div class="bg-white p-4 rounded-lg grid grid-cols-2 gap-x-8">
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
                        </div>
                        <div class="bg-green-200 p-4 rounded-lg grid grid-cols-2 gap-x-8">
                            <div>
                                <p><span class="section-title">Addons : </span><span class="section-value"><?php $options = json_decode($item['options'], true); echo implode(', ', $options); ?></span>
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
                                <p><span class="section-title">Cost Price : </span><span
                                        class="section-value"><?php echo $item['cost_price']; ?></span></p>
                                <p><span class="section-title">Currency : </span><span 
                                        class="section-value"><?php echo $item['currency']; ?></span></p>
                                
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
                            </div>
                        </div>                       
                        
                        <!-- Notes -->
                        <div class="bg-white p-4 rounded-lg">
                            <p class="notes-title">Notes :</p>
                            <p class="notes-text mt-1"><?php echo $item['remarks']; ?>  </p>
                        </div>
                    </div>
                    <!-- /Accordion Content -->
                </div>
<?php endforeach; ?>               