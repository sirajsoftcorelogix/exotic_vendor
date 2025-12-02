<?php 
//print_array($products);
$countries = country_array();
//print_array($countries);
?>
<div class="p-8">

    <!-- Header Content -->
    <div id="product-details-content" class="space-y-6">

        <!-- Top Info -->
        <div>
            <div class="text-breadcrumb mb-1"><?php echo $products[0]['groupname'];?> / <?php echo $products[0]['subcategories'];?></div>
            <div class="text-product-title mb-1"><?php echo $products[0]['title'];?></div>
            <div class="flex items-center gap-2">
                <span class="text-item-code-label">Item Code :</span>
                <span class="text-item-code-value"><?php echo $products[0]['item_code'];?></span>
            </div>
        </div>

        <!-- HR -->
        <hr style="border-color: rgba(155, 155, 157, 1); border-width: 1px;">

        <!-- Variation Details Section -->
        <div>
            <h3 class="text-section-title mb-4">Variation Details</h3>
            
            <!-- Variation List -->
            <div class="space-y-4">
            <?php foreach ($products as $items => $item):?>
                <!-- Card 1 -->
                <div class="variation-card p-6 flex">
                    <!-- Image -->
                    <div class="flex-shrink-0 w-32 h-40 bg-gray-200 rounded-lg overflow-hidden mr-6">
                        <img src="<?php echo $item['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image'; ?>" alt="Product Image" class="w-full h-full object-cover">
                    </div>

                    <!-- Details Container -->
                    <div class="flex-grow flex">

                        <!-- Column 1 (Data) -->
                        <div class="flex-grow grid grid-cols-[80px_10px_1fr] items-baseline gap-y-1 content-start">
                            <span class="grid-label">Color</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['color'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Size</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['size'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Cost Price</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['cost_price'] ? "₹".$item['cost_price'] : 'N/A'; ?></span>

                            <span class="grid-label">Item Price</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['itemprice'] ? "₹".$item['itemprice'] : 'N/A'; ?></span>

                            <span class="grid-label">Local Stock</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['local_stock'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Location</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['location'] ?? 'N/A'; ?></span>
                        </div>

                        <!-- Divider -->
                        <div class="vertical-divider mx-6 self-stretch"></div>

                        <!-- Column 2 (Data) -->
                        <div class="flex-grow grid grid-cols-[130px_10px_1fr] items-baseline gap-y-1 content-start">
                            <span class="grid-label">Num Sold</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['numsold'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Num Sold (India)</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['numsold_india'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Num Sold (Global)</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['numsold_global'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Num Sold (Last)</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['lastsold'] ?? 'N/A'; ?></span>

                            <span class="grid-label">Lead Time</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['leadtime'] ?? 'N/A'; ?></span>

                            <span class="grid-label">In Stock Lead Time</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['instock_leadtime'] ?? 'N/A'; ?></span>
                        </div>

                        <!-- Divider -->
                        <div class="vertical-divider mx-6 self-stretch"></div>

                        <!-- Column 3 (Actions) -->
                        <div class="w-[180px] flex flex-col items-start pt-1">
                            <form action="<?php echo base_url('?page=purchase_orders&action=custom_po'); ?>" method="post">
                        <input type="hidden" name="cpoitem[]" value="<?php echo $item['id']?>">                                        
                        <button class="create-po-btn mb-6 w-full">
                            <i class="fas fa-file-invoice mr-2"></i>
                            Create PO
                        </button>   
                        </form>                            

                            <div class="grid grid-cols-[90px_10px_1fr] w-full gap-y-1">
                                <span class="grid-label">FBA (India)</span> <span class="grid-label">:</span> <span class="grid-value"><?php echo $item['fba_in'] ?? 'N/A'; ?></span>
                                <span class="grid-label">FBA (US)</span> <span class="grid-label">:</span> <span class="grid-value"><<?php echo $item['fba_us'] ?? 'N/A'; ?>/span>
                            </div>
                        </div>

                    </div>
                </div>                
            <?php endforeach; ?> 
            </div>
           
        </div>
    </div>

</div>