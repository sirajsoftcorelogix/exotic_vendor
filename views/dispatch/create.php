<div class="max-w-7xl mx-auto space-y-6 px-2 sm:px-4 lg:px-6">
    <!-- Header -->
    <div class="shadow-[0px_10px_15px_-3px_#0000001A] bg-white rounded-2xl shadow mt-6 p-6 flex flex-col sm:flex-row items-start sm:items-center gap-4">
      <div class="bg-orange-500 text-white p-3 rounded-xl shadow-[0px_10px_15px_-3px_#0000001A]">
        <img src="<?php echo base_url('images/icons.svg'); ?>" alt="">
      </div>
      <div>
        <h1 class="text-2xl font-bold bg-gradient-to-r from-[#1E2939] to-[#4A5565] bg-clip-text text-transparent">
          Ship Order</h1>
        <p class="text-gray-500 text-sm text-[#6A7282]">Create and manage shipping orders</p>
      </div>
    </div>
    <?php if (isset($_GET['status']) && $_GET['status'] === 'error' && isset($_GET['message'])): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
            <p class="text-red-600 font-semibold"><?php echo htmlspecialchars($_GET['message']); ?></p>
        </div>
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="bg-green-50 border border-green-200 rounded-lg p-6 text-center">
            <p class="text-green-600 font-semibold">Dispatch created successfully!</p>
        </div>
    <?php endif; ?>
     <?php 
    //if dispatch records not found for this invoice, show form to create dispatch, else show dispatch details and labels
    if(isset($dispatchRecords) && count($dispatchRecords) == 0) {
        //dispatch records not found, show form to create dispatch
    ?>
    <form id="dispatchForm" method="POST" action="">
      <input type="hidden" name="invoice_id" value="<?php echo htmlspecialchars($_GET['invoice_id'] ?? ''); ?>">

      <?php if($invoices && count($invoices) > 0){
          $invoice = $invoices[0];
          $items = $invoice['items'] ?? [];
          $groupedItems = [];
          
          // Group items by box_no
          foreach($items as $item) {
              $boxNo = $item['box_no'] ?? 1;
              if(!isset($groupedItems[$boxNo])) {
                  $groupedItems[$boxNo] = [];
              }
              $groupedItems[$boxNo][] = $item;
          }
          
          ksort($groupedItems);
      ?>
  
      <!-- DYNAMIC BOXES -->
      <?php foreach($groupedItems as $boxNo => $boxItems): ?>
      <div id="box-section-<?php echo $boxNo; ?>" class="shadow-[0px_10px_15px_-3px_#0000001A] bg-white rounded-2xl shadow overflow-hidden box-section mb-6" data-box-no="<?php echo $boxNo; ?>">

        <div class="bg-orange-500 p-6 text-white">

          <div class="flex flex-col lg:flex-row justify-between lg:items-center gap-4 mb-6">

            <span class="bg-[#FFFFFF33] backdrop-blur-[8px] px-4 py-1 rounded-lg text-lg font-bold leading-relaxed w-fit">
              Box <?php echo $boxNo; ?>
            </span>

            <div class="bg-[#FFFFFF33] backdrop-blur-[8px] flex flex-col sm:flex-row gap-4 sm:gap-8 px-6 py-3 rounded-xl text-center w-full lg:w-auto">
              <div>
                <p class="text-xs opacity-80 leading-relaxed">Volumetric Weight</p>
                <p class="font-semibold volumetric-weight">0.0 kg</p>
              </div>

              <div class="border-x px-8 border-white/30">
                <p class="text-xs opacity-80 leading-relaxed">Billable Weight</p>
                <p class="font-semibold billable-weight">1 kg</p>
              </div>

              <div>
                <p class="text-xs opacity-80 leading-relaxed">Shipping Charges</p>
                <p class="font-semibold shipping-charges">₹197</p>
              </div>
            </div>
          </div>

          <!-- Inputs -->
          <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <div class="flex flex-col gap-2">
              <label class="text-white text-sm font-medium">Box Size</label>
              <select name="box_size[<?php echo $boxNo; ?>]" class="h-10 rounded-lg px-3 bg-gray-100 text-gray-700 outline-none box-size-select" onchange="populateDimensions(this)">              
                  <option value="">Custom Size</option>
                  <option value="R-1" data-length="22" data-width="17" data-height="5">R-1 (22x17x5 inch)</option>
                  <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 inch)</option>
                  <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 inch)</option>
                  <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 inch)</option>
                  <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 inch)</option>
                  <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 inch)</option>
                  <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 inch)</option>
                  <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 inch)</option>
                  <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 inch)</option>
                  <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 inch)</option>
                  <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 inch)</option>
                  <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 inch)</option>
                  <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 inch)</option>
                  <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 inch)</option>
              </select>
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-white text-sm font-medium">Length</label>
              <input type="text" name="box_length[<?php echo $boxNo; ?>]" placeholder="Inch" class="h-10 rounded-lg px-3 bg-gray-100 text-gray-700 outline-none box-length" onchange="calculateWeight(this)">
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-white text-sm font-medium">Width</label>
              <input type="text" name="box_width[<?php echo $boxNo; ?>]" placeholder="Inch" class="h-10 rounded-lg px-3 bg-gray-100 text-gray-700 outline-none box-width" onchange="calculateWeight(this)">
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-white text-sm font-medium">Height</label>
              <input type="text" name="box_height[<?php echo $boxNo; ?>]" placeholder="Inch" class="h-10 rounded-lg px-3 bg-gray-100 text-gray-700 outline-none box-height" onchange="calculateWeight(this)">
            </div>

            <div class="flex flex-col gap-2">
              <label class="text-white text-sm font-medium">Weight (kg)</label>
              <input type="text" name="box_weight[<?php echo $boxNo; ?>]" placeholder="kg" class="h-10 rounded-lg px-3 bg-gray-100 text-gray-700 outline-none box-actual-weight" onchange="calculateWeight(this)">
            </div>

          </div>
        </div>

        <div class="p-6">

          <!-- Shipping Details -->
          <div class="border border-[#D1D1D2] bg-[#F3F3F3] p-4 rounded-[14px] mb-6">
            <div class="flex items-center gap-2 mb-2">
              <span class="w-2 h-5 bg-black rounded-md"></span>
              <h2 class="text-md font-bold text-[#1E2939]">Shipping Details</h2>
            </div>

            <p class="text-gray-600 text-sm pl-3.5 text-[#1E2939]">
              <span class="font-bold">Ship To:</span>
              <?php echo $invoice['address']['shipping_first_name'] ?? ''; ?><?php echo $invoice['address']['shipping_last_name'] ?? ''; ?>, <?php echo $invoice['address']['shipping_address_line1'].' '.$invoice['address']['shipping_address_line2']; ?>, <?php echo $invoice['address']['shipping_city']; ?>, <?php echo $invoice['address']['shipping_state']; ?> - <?php echo $invoice['address']['shipping_zipcode']; ?>
            </p>

            <p class="text-gray-600 text-sm mt-1.5 pl-3.5 text-[#1E2939]">
                <?php if(isset($invoice['address']['gstin']) && !empty($invoice['address']['gstin'])): ?>
              <span class="font-bold">GST:</span>
              <?php echo $invoice['address']['gstin'] ?? 'N/A'; ?>
              <?php endif; ?>
            </p>
          </div>

          <!-- SKU GRID -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
            <?php foreach($boxItems as $index => $item): ?>
              <!-- CARD -->
              <div class="border-2 border-gray-200 rounded-xl p-4 flex flex-col sm:flex-row gap-4">
                <div class="relative">
                  <img src="<?php echo $item['image_url'] ?? 'https://placehold.co/90x120'; ?>"
                    class="w-full sm:w-[96px] h-[180px] sm:h-[112px] border-2 border-gray-100 object-cover rounded-lg">
                  <span
                    class="absolute -top-2 -right-2 bg-orange-600 text-white text-xs font-semibold w-6 h-6 flex items-center justify-center rounded-full">
                    <?php echo str_pad($index + 1, 2, '0', STR_PAD_LEFT); ?>
                  </span>
                </div>

                <div class="flex-1 text-sm text-[#1E2939] space-y-1.5">
                  <p><strong class="text-gray-600 text-[#4A5565]">SKU</strong> : <?php echo $item['sku'] ?? 'N/A'; ?></p>
                  <p><strong class="text-gray-600 text-[#4A5565]">Quantity</strong> : <?php echo $item['quantity'] ?? '01'; ?></p>
                  <p><strong class="text-gray-600 text-[#4A5565]">Weight</strong> : <?php echo $item['weight'] ?? '0.5'; ?> kg</p>
                  <?php if(isset($item['size'])): ?>
                    <p><strong class="text-gray-600 text-[#4A5565]">Size</strong> : <?php echo $item['size']; ?></p>
                  <?php endif; ?>
                </div>
                <input type="hidden" name="box_items[<?php echo $boxNo; ?>][]" value="<?php echo $item['id']; ?>">
                <input type="hidden" name="order_numbers[<?php echo $boxNo; ?>][]" value="<?php echo $item['order_number']; ?>">
                <input type="hidden" name="item_weights[<?php echo $boxNo; ?>][]" value="<?php echo $item['weight'] ?? '0.5'; ?>">
                <input type="hidden" name="item_billable_weights[<?php echo $boxNo; ?>][]" value="<?php echo $item['weight'] ?? '0.5'; ?>">
                <input type="hidden" name="item_shipping_charges[<?php echo $boxNo; ?>][]" value="<?php echo $item['shipping_charges'] ?? '0'; ?>">
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-5">
            <div id="labels-container-<?php echo $boxNo; ?>" class="p-4">
            <!-- <iframe src="https://docs.google.com/gview?url=https://kr-shipmultichannel-mum.s3.ap-south-1.amazonaws.com/298507/labels/d8bc2ca9af903d3f6165b74c042a54f4.pdf&embedded=true" class="w-full h-96 border border-gray-300 rounded-lg" ></iframe> -->
            <!-- <iframe id="label-frame-" src="https://kr-shipmultichannel-mum.s3.ap-south-1.amazonaws.com/298507/labels/d8bc2ca9af903d3f6165b74c042a54f4.pdf" class="w-full h-96 border border-gray-300 rounded-lg" style="display:none;"></iframe> -->
            <iframe id="label-frame-<?php echo $boxNo; ?>" src="" width="100%" class="w-full h-96 border border-gray-300 rounded-lg" style="display:none;">
            </iframe>
            
            </div>
            
      </div>
      <?php endforeach; ?>

      <!-- DELIVERY INFO -->
      <div class="shadow-[0px_10px_15px_-3px_#0000001A] bg-white rounded-2xl shadow overflow-hidden">

        <div class="bg-orange-500 py-4 px-7 text-white">
          <div class="flex justify-start items-center gap-2">
            <img src="<?php echo base_url('images/info_img.svg'); ?>" alt="">
            <span class="text-lg font-bold leading-relaxed">
              Delivery Information
            </span>
          </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 items-end p-7">

          <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2 text-[#364153] font-bold text-sm">
              <img src="<?php echo base_url('images/order.svg'); ?>" alt="">
              Delivery Partner
            </label>

            <input type="text" name="delivery_partner" value="Shiprocket"
              class="h-12 px-4 rounded-xl border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-200 text-[#0A0A0A]">
          </div>

          <!-- <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2 text-[#364153] font-bold text-sm">
              <img src="<?php //echo base_url('images/cart.svg'); ?>" alt="">
              Shipment Type
            </label>

            <select name="shipment_type" class="h-12 px-4 rounded-xl border-2 border-gray-300 focus:outline-none text-[#0A0A0A] focus:ring-2 focus:ring-gray-200">
              <option value="Light / Surface">Light / Surface</option>
              <option value="Standard">Standard</option>
              <option value="Express">Express</option>
            </select>
          </div> -->

          <div class="flex flex-col gap-2 col-span-2">
            <label class="flex items-center gap-2 text-[#364153] font-bold text-sm">
              <img src="<?php echo base_url('images/location.svg'); ?>" alt="">
              Pickup Location
            </label>
            <?php $pickupLocations = $invoice['pickup_locations'] ?? []; ?>
            <select name="pickup_location" class="h-12 px-4 rounded-xl border-2 border-gray-300 focus:outline-none text-[#0A0A0A] focus:ring-2 focus:ring-gray-200">
              <option value="">Select Pickup Location</option>
              <?php foreach($pickupLocations as $location): ?>
                <option value="<?php echo htmlspecialchars($location['pickup_location'] ?? ''); ?>" <?php echo (isset($location['pickup_location']) && 'Head Off' == $location['pickup_location']) ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($location['address'] ?? ''); ?>
                </option>
              <?php endforeach; ?>
            </select>
            <!-- <input type="text" name="pickup_location" value="Wazirpur"
              class="h-12 px-4 rounded-xl border-2 border-gray-300 focus:outline-none text-[#0A0A0A] focus:ring-2 focus:ring-gray-200"> -->
          </div>

          <!-- <div class="flex flex-col gap-2">
            <label class="flex items-center gap-2 text-[#364153] font-bold text-sm">
              <img src="<?php //echo base_url('images/notes.svg'); ?>" alt="">
              GST No.
            </label>

            <input type="text" name="exotic_gst_no" value="<?php //echo $invoice['firm_details']['gst'] ?? ''; ?>"
              class="h-12 px-4 rounded-xl border-2 border-gray-300 focus:outline-none text-[#0A0A0A] focus:ring-2 focus:ring-gray-200">
          </div> -->
        <div class="flex flex-col gap-2">
            <div id="invoice-container" class="p-4 invoice-container" style="display:none;">
            </div>
        </div>
        
          <button type="button" onclick="submitDispatchForm(event)"
            class="h-12 px-4 w-full sm:w-auto rounded-xl bg-black text-white font-semibold flex items-center justify-center gap-2 hover:bg-gray-900 transition">
            <img src="<?php echo base_url('images/track_order.svg'); ?>" alt="">
            Dispatch
          </button>
       
        </div>
      </div>

      <?php } else { ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
          <p class="text-red-600 font-semibold">Invoice not found or no items available</p>
          <a href="<?php echo base_url('?page=dispatch'); ?>" class="text-red-500 underline mt-2 inline-block">Go back</a>
        </div>
      <?php } ?>

    </form>
    <?php }else{ ?>
            <div class="col-span-full">
                <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <p class="text-green-600 font-semibold text-lg">✓ Dispatch Already Created</p>
                            <p class="text-green-600 text-sm mt-1">Below are the dispatch details and labels for this invoice.</p>
                        </div>
                        <a href="<?php echo base_url('?page=dispatch'); ?>" class="text-green-600 hover:text-green-700 underline font-semibold">← Back to Dispatch List</a>
                    </div>
                </div>

                <!-- Dispatch Records Grid -->
                <div class="grid grid-cols-1 gap-6 mt-6">
                    <?php foreach($dispatchRecords as $dispatch): ?>
                    <div class="bg-white rounded-2xl shadow-[0px_10px_15px_-3px_#0000001A] overflow-hidden">
                        
                        <!-- Dispatch Header -->
                        <div class="bg-orange-500 p-6 text-white">
                            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                <div>
                                    <h3 class="text-lg font-bold mb-1">Box <?php echo htmlspecialchars($dispatch['box_no'] ?? 'N/A'); ?></h3>
                                    <p class="text-blue-100 text-sm">Dispatch ID: <?php echo htmlspecialchars($dispatch['id'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <?php if(isset($dispatch['awb_number'])): ?>
                                    <div class="bg-white bg-opacity-20 rounded-lg px-4 py-2">
                                        <p class="text-xs text-blue-100">AWB Number</p>
                                        <p class="font-bold text-white"><?php echo htmlspecialchars($dispatch['awb_number']); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Dispatch Details -->
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 border-b border-gray-200">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Box Size</label>
                                <p class="text-gray-800 font-medium mt-1"><?php echo $dispatch['length'] ?> x <?php echo $dispatch['width'] ?> x <?php echo $dispatch['height'] ?> INCH</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Billable Weight</label>
                                <p class="text-gray-800 font-medium mt-1"><?php echo htmlspecialchars($dispatch['billing_weight'] ?? 'N/A'); ?> kg</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Volumetric Weight</label>
                                <p class="text-gray-800 font-medium mt-1"><?php echo htmlspecialchars($dispatch['volumetric_weight'] ?? '0'); ?> kg</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</label>
                                <p class="text-gray-800 font-medium mt-1">
                                    <?php 
                                        $status = isset($dispatch['status']) ? strtolower($dispatch['status']) : 'pending';
                                        $statusClass = $status === 'completed' ? 'bg-green-100 text-green-700' : ($status === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                                    ?>
                                    <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                        <?php echo htmlspecialchars(ucfirst($status)); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Box Dimensions -->
                        <div class="p-6 bg-gray-50 border-b border-gray-200">
                            <h4 class="font-bold text-gray-800 mb-4">Box Dimensions & Weight</h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                <div class="bg-white rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Length</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($dispatch['length'] ?? '0'); ?> in</p>
                                </div>
                                <div class="bg-white rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Width</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($dispatch['width'] ?? '0'); ?> in</p>
                                </div>
                                <div class="bg-white rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Height</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($dispatch['height'] ?? '0'); ?> in</p>
                                </div>
                                <div class="bg-white rounded-lg p-3">
                                    <p class="text-xs text-gray-500">Actual Weight</p>
                                    <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($dispatch['weight'] ?? '0'); ?> kg</p>
                                </div>
                            </div>
                        </div>

                        <!-- Label Display -->
                        <?php if(isset($dispatch['label_url']) && !empty($dispatch['label_url'])): ?>
                        <div class="p-6 bg-white">
                            <h4 class="font-bold text-gray-800 mb-4">Shipping Label</h4>
                            <div class="flex flex-col gap-4">
                                <iframe 
                                    src="https://docs.google.com/gview?url=<?php echo urlencode($dispatch['label_url']); ?>&embedded=true" 
                                    class="w-full h-96 border-2 border-gray-300 rounded-lg" 
                                    frameborder="0">
                                </iframe>
                                
                                <div class="flex flex-col sm:flex-row gap-3">
                                    <button type="button" onclick="printLabel('<?php echo htmlspecialchars($dispatch['label_url']); ?>')" 
                                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-semibold flex items-center justify-center gap-2">
                                        🖨️ Print Label
                                    </button>
                                    <a href="<?php echo htmlspecialchars($dispatch['label_url']); ?>" target="_blank" 
                                        class="flex-1 px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition font-semibold flex items-center justify-center gap-2">
                                        📥 Download Label
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php else: 
                          // button to retry
                          ?>
                        <div class="p-6 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p class="text-yellow-700 font-semibold">Label not generated yet.</p>
                            <button id="retryLabelBtn" onclick="genLabel('<?php echo htmlspecialchars($dispatch['id']); ?>')" class="mt-2 px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition font-semibold">
                                Retry Label Generation
                            </button>
                        </div>
                        <?php endif; ?>

                        <!-- Items in Dispatch -->
                        <?php if(isset($dispatch['items']) && count($dispatch['items']) > 0): ?>
                        <div class="p-6 bg-gray-50 border-t border-gray-200">
                            <h4 class="font-bold text-gray-800 mb-4">Items in Dispatch</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <?php foreach($dispatch['items'] as $itemIndex => $item): ?>
                                <div class="bg-white rounded-lg p-4 border border-gray-200">
                                    <div class="flex gap-3">
                                        <?php if(isset($item['image_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="product" class="w-16 h-20 object-cover rounded border border-gray-200">
                                        <?php endif; ?>
                                        <div class="flex-1">
                                            <p class="text-xs text-gray-500 font-semibold">SKU</p>
                                            <p class="font-medium text-gray-800"><?php echo htmlspecialchars($item['sku'] ?? 'N/A'); ?></p>
                                            <p class="text-xs text-gray-600 mt-2">Qty: <span class="font-semibold"><?php echo htmlspecialchars($item['quantity'] ?? '1'); ?></span></p>
                                            <p class="text-xs text-gray-600">Wt: <span class="font-semibold"><?php echo htmlspecialchars($item['weight'] ?? '0'); ?> kg</span></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Delivery Info -->
                        <div class="p-6 border-t border-gray-200">
                            <h4 class="font-bold text-gray-800 mb-4">Delivery Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Courier name</p>
                                    <p class="text-gray-800 font-medium mt-1"><?php echo htmlspecialchars($dispatch['courier_name'] ?? 'N/A'); ?></p>
                                </div>
                                <!-- <div>
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Pickup Location</p>
                                    <p class="text-gray-800 font-medium mt-1"><?php //echo htmlspecialchars($dispatch['pickup_location'] ?? 'N/A'); ?></p>
                                </div> -->
                                <?php if(isset($dispatch['created_at'])): ?>
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Created Date</p>
                                    <p class="text-gray-800 font-medium mt-1"><?php echo htmlspecialchars(date('d M Y, H:i', strtotime($dispatch['created_at']))); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if(isset($dispatch['tracking_url'])): ?>
                                <div>
                                    <p class="text-xs text-gray-500 font-semibold uppercase tracking-wide">Tracking</p>
                                    <a href="<?php echo htmlspecialchars($dispatch['tracking_url']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800 font-medium mt-1 inline-block">
                                        Track Shipment →
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php } ?>
</div>

<script>
function populateDimensions(element) {
    const boxSection = element.closest('.box-section');
    const selectedOption = element.options[element.selectedIndex];
    
    if (selectedOption.value) {
        const length = selectedOption.getAttribute('data-length');
        const width = selectedOption.getAttribute('data-width');
        const height = selectedOption.getAttribute('data-height');
        
        boxSection.querySelector('.box-length').value = length;
        boxSection.querySelector('.box-width').value = width;
        boxSection.querySelector('.box-height').value = height;
        
        calculateWeight(element);
    }
}

function calculateWeight(element) {
    const boxSection = element.closest('.box-section');
    const length = parseFloat(boxSection.querySelector('.box-length').value) || 0;
    const width = parseFloat(boxSection.querySelector('.box-width').value) || 0;
    const height = parseFloat(boxSection.querySelector('.box-height').value) || 0;
    const actualWeight = parseFloat(boxSection.querySelector('.box-actual-weight').value) || 1;
    
    // Convert inches to cm and calculate volumetric weight
    const lengthCm = length * 2.54;
    const widthCm = width * 2.54;
    const heightCm = height * 2.54;
    const volumetricWeight = (lengthCm * widthCm * heightCm) / 5000;
    
    const billableWeight = Math.max(volumetricWeight, actualWeight);
    console.log('Calculated weights - Volumetric:', volumetricWeight.toFixed(2), 'kg, Actual:', actualWeight.toFixed(2), 'kg, Billable:', billableWeight.toFixed(2), 'kg');
    boxSection.querySelector('.volumetric-weight').textContent = volumetricWeight.toFixed(2) + ' kg';
    boxSection.querySelector('.billable-weight').textContent = billableWeight.toFixed(2) + ' kg';
    //item weight is used as actual weight for shipping charges calculation
    const shippingCharges = calculateShippingCharges(billableWeight);
    boxSection.querySelector('.shipping-charges').textContent = '₹' + shippingCharges;
    
    //item_shipping_charges
    const shippingChargesInputs = boxSection.querySelectorAll('input[name^="item_shipping_charges"]');
    shippingChargesInputs.forEach(input => {
        input.value = shippingCharges;
        console.log('Updated shipping charges input:', input.name, input.value);
    });

    //item_billable_weights
    const billableWeightInputs = boxSection.querySelectorAll('input[name^="item_billable_weights"]');
    billableWeightInputs.forEach(input => {
        input.value = billableWeight.toFixed(2);
        console.log('Updated billable weight input:', input.name, input.value);
    });

}
function calculateShippingCharges(weight) {
    // Simple flat rate calculation for demonstration
    if (weight <= 1) return 197;
    if (weight <= 3) return 247;
    if (weight <= 5) return 347;
    return 347 + Math.ceil((weight - 5) / 5) * 100; // Additional ₹100 for every extra 5kg
}
// Calculate weight on page load
document.querySelectorAll('.box-section').forEach(section => {
    calculateWeight(section.querySelector('input'));
});
</script>

<script>
function submitDispatchForm(event) {
    event.preventDefault();
    
    const form = document.getElementById('dispatchForm');
    const submitBtn = event.target;
    const originalBtnText = submitBtn.innerHTML;
    //validation
    const boxSections = document.querySelectorAll('.box-section');
    let isValid = true;
    boxSections.forEach(section => {
        const length = parseFloat(section.querySelector('.box-length').value) || 0;
        const width = parseFloat(section.querySelector('.box-width').value) || 0;
        const height = parseFloat(section.querySelector('.box-height').value) || 0;
        const actualWeight = parseFloat(section.querySelector('.box-actual-weight').value) || 1;
        
        if (length <= 0 || width <= 0 || height <= 0 || actualWeight <= 0) {
            isValid = false;
            showAlert('All box dimensions and weights must be greater than zero.','error');
            return false; // Stop processing further sections
        }
    });
    if (!isValid) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        return; // Stop form submission if validation fails
    }
    // Disable button and show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-spin">⏳</span> Processing...';
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Make AJAX request
    fetch('<?php echo base_url('?page=dispatch&action=create'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .catch(() => {
        // If JSON parsing fails, assume form submission (redirect case)
       // return { status: 'redirect' };
       showAlert('Unexpected response from server. Please try again.','error');
    })
    .then(data => {
        if (data.status === 'success') {
            // Show success message
            showAlert('' + (data.message ? ' ' + data.message : ' Dispatch created successfully!'),'success');
            // handle nested dispatches structure: { awb: {...}, labelUrl: {...}, ids: {...} }
            if (data.dispatches) {
                const ids = data.dispatches.ids || {};
                const labelUrls = data.dispatches.labelUrl || {};
                const awbs = data.dispatches.awb || {};
                Object.keys(ids).forEach(boxNo => {
                    const labelUrl = labelUrls[boxNo];
                    if (labelUrl) {
                        const labelFrame = document.getElementById('label-frame-' + boxNo);
                        if (labelFrame) {
                            labelFrame.src = 'https://docs.google.com/gview?url=' + encodeURIComponent(labelUrl) + '&embedded=true';
                            
                            // Create print button
                            const container = labelFrame.parentNode;
                            let printBtn = document.getElementById('print-btn-' + boxNo);
                            if (!printBtn) {
                                printBtn = document.createElement('button');
                                printBtn.id = 'print-btn-' + boxNo;
                                printBtn.type = 'button';
                                printBtn.className = 'mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 print-label-btn';
                                printBtn.innerHTML = '🖨️ Print Label';
                                printBtn.onclick = function(e) {
                                    e.preventDefault();
                                    const printWindow = window.open('https://docs.google.com/gview?url=' + encodeURIComponent(labelUrl), '_blank');
                                    printWindow.onload = function() {
                                        setTimeout(() => printWindow.print(), 2000);
                                    };
                                };
                                container.appendChild(printBtn);
                            }
                            
                            labelFrame.style.display = 'block';
                        }
                    }
                    const awbCode = awbs[boxNo];
                    const container = document.getElementById('labels-container-' + boxNo);
                    if (awbCode) {                       
                        if (container) {
                            let awbEl = document.getElementById('awb-' + boxNo);
                            if (!awbEl) {
                                awbEl = document.createElement('p');
                                awbEl.id = 'awb-' + boxNo;
                                awbEl.className = 'mt-2 text-sm text-gray-700';
                                container.appendChild(awbEl);
                            }
                            awbEl.textContent = 'AWB: ' + awbCode;
                        }
                    }
                    //awb_assign_status and label_created is 0 then show warning message and link to retry api call for that box
                      const awbAssignStatus = data.dispatches.awb_assign_status ? data.dispatches.awb_assign_status[boxNo] : null;
                      const labelCreated = data.dispatches.label_created ? data.dispatches.label_created[boxNo] : null;
                      if (awbAssignStatus === 0 || labelCreated === 0) {
                          showAlert('AWB assignment or label creation failed for Box ' + boxNo + '. Please retry.','error');
                          // Optionally, you can add a retry button here that calls an API to retry the failed step for this box
                      }
                      //button to retry failed api calls for this box
                      const retryBtn = document.createElement('button');
                      retryBtn.type = 'button';
                      retryBtn.className = 'mt-2 px-3 py-1 bg-red-600 text-white rounded text-sm hover:bg-red-700';
                      retryBtn.innerHTML = 'Retry API Call';
                      retryBtn.onclick = function(e) {
                          e.preventDefault();
                          // Call API to retry failed dispatch for this box
                          fetch('<?php echo base_url('?page=dispatch&action=retry_dispatch'); ?>', {
                              method: 'POST',
                              headers: {'Content-Type': 'application/json'},
                              body: JSON.stringify({
                                  invoice_id: formData.get('invoice_id'),
                                  box_no: boxNo,
                                  dispatch_id: ids[boxNo] || null
                              })
                          })
                          .then(response => response.json())
                          .then(retryData => {
                              if (retryData.status === 'success') {
                                  showAlert('Retry successful for Box ' + boxNo, 'success');
                                  //upadte label and awb on page without refreshing
                                  if (retryData.labelUrl) {
                                      const labelFrame = document.getElementById('label-frame-' + boxNo);
                                      if (labelFrame) {
                                          labelFrame.src = 'https://docs.google.com/gview?url=' + encodeURIComponent(retryData.labelUrl) + '&embedded=true';
                                      }
                                  }
                              } else {
                                  showAlert('Retry failed for Box ' + boxNo + ': ' + (retryData.message || ''), 'error');
                              }
                          })
                          .catch(error => {
                              console.error('Error retrying dispatch:', error);
                              showAlert('Error retrying dispatch for Box ' + boxNo, 'error');
                          });
                      };
                      container.appendChild(retryBtn);

                });
            }
            const invoiceContainer = document.getElementById('invoice-container');
            if (invoiceContainer) {
                invoiceContainer.style.display = 'block';
                const invoiceUrl = '<?php echo base_url('?page=invoices&action=generate_pdf'); ?>' + '&invoice_id=' + formData.get('invoice_id') + '&dispatch=true';
                // Generate and load invoice PDF
                setTimeout(() => {                
                    fetch('<?php echo base_url('?page=invoices&action=generate_pdf'); ?>', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({invoice_id: formData.get('invoice_id')})
                    })
                    .then(response => response.blob())
                    .then(blob => {
                        const url = window.URL.createObjectURL(blob);                       
                
                        // Create invoice print button
                        let invoicePrintBtn = document.getElementById('invoice-print-btn');
                        if (!invoicePrintBtn) {
                            invoicePrintBtn = document.createElement('button');
                            invoicePrintBtn.id = 'invoice-print-btn';
                            invoicePrintBtn.type = 'button';
                            invoicePrintBtn.className = 'mt-3 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700';
                            invoicePrintBtn.innerHTML = '🖨️ Print Invoice';
                            invoicePrintBtn.onclick = function(e) {
                                e.preventDefault();
                                const printWindow = window.open(url, '_blank');
                                printWindow.onload = function() {
                                    setTimeout(() => printWindow.print(), 2000);
                                };
                            };
                            invoiceContainer.appendChild(invoicePrintBtn);
                        }
                    })
                    .catch(error => {
                        console.error('Error generating invoice PDF:', error);
                        showAlert('Failed to generate invoice PDF','error');
                    });
                }, 1000);
                
            }     
             
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
        submitBtn.style.display = 'none'; // Hide the dispatch button after successful submission
        } else if (data.status === 'error') {
            showAlert(data.message || 'An error occurred','error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnText;
        } else if (data.status === 'redirect') {
            showAlert('Dispatch created successfully! Redirecting...','success');
            // Handle redirect response
          //  window.location.href = data.redirect || '<?php //echo base_url("?page=dispatch"); ?>';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('Failed to submit form','error');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
    });
}


// Calculate weight on page load
document.querySelectorAll('.box-section').forEach(section => {
    calculateWeight(section.querySelector('input'));
});
//print-label-btn

// Function to print label
function printLabel(labelUrl) {
    const printWindow = window.open('https://docs.google.com/gview?url=' + encodeURIComponent(labelUrl) + '&embedded=true', '_blank');
    printWindow.onload = function() {
        setTimeout(() => printWindow.print(), 2000);
    };
}
function genLabel(dispatchId) {
    const retryLabelBtn = document.getElementById('retryLabelBtn');
    retryLabelBtn.disabled = true;
    retryLabelBtn.innerHTML = '<span class="animate-spin">⏳</span> Regenerating...';
    // Call API to regenerate label for this dispatch
    fetch('<?php echo base_url('?page=dispatch&action=retry_dispatch'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            dispatch_id: dispatchId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success === true) {
            showAlert('Label regeneration successful!', 'success');
            // Update label URL on page if provided
            location.reload(); // Reload the page to reflect updated label and AWB info
        } else {
            showAlert('Label regeneration failed: ' + (data.message || ''), 'error');
        }
        retryLabelBtn.disabled = false;
        retryLabelBtn.innerHTML = 'Retry Label Generation';
    })
    .catch(error => {
        console.error('Error regenerating label:', error);
        showAlert('Error regenerating label', 'error');
    });
}

</script>