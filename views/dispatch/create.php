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
                  <option value="">Select Size</option>
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
              <?php echo $invoice['address']['shipping_first_name'] ?? ''; ?><?php echo $invoice['address']['shipping_last_name'] ?? ''; ?>, <?php echo substr($invoice['address']['shipping_address_line1'] ?? '', 0, 50); ?>...
            </p>

            <p class="text-gray-600 text-sm mt-1.5 pl-3.5 text-[#1E2939]">
              <span class="font-bold">GST:</span>
              <?php echo $invoice['address']['gstin'] ?? 'N/A'; ?>
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
        <div id="labels-container-<?php echo $boxNo; ?>" class="p-4">
          <!-- <iframe src="https://docs.google.com/gview?url=https://kr-shipmultichannel-mum.s3.ap-south-1.amazonaws.com/298507/labels/d8bc2ca9af903d3f6165b74c042a54f4.pdf&embedded=true" class="w-full h-96 border border-gray-300 rounded-lg" style="display:none;"></iframe> -->
          <!-- <iframe id="label-frame-" src="https://kr-shipmultichannel-mum.s3.ap-south-1.amazonaws.com/298507/labels/d8bc2ca9af903d3f6165b74c042a54f4.pdf" class="w-full h-96 border border-gray-300 rounded-lg" style="display:none;"></iframe> -->
        <iframe id="label-frame-<?php echo $boxNo; ?>"
src=""
width="100%" class="w-full h-96 border border-gray-300 rounded-lg" style="display:none;">
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

          <button type="button" onclick="submitDispatchForm(event)"
            class="h-12 px-4 w-full sm:w-auto rounded-xl bg-black text-white font-semibold flex items-center justify-center gap-2 hover:bg-gray-900 transition">
            <img src="<?php echo base_url('images/track_order.svg'); ?>" alt="">
            Create Dispatch
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
                                    const printWindow = window.open(labelUrl, '_blank');
                                    printWindow.onload = function() {
                                        setTimeout(() => printWindow.print(), 500);
                                    };
                                };
                                container.appendChild(printBtn);
                            }
                            
                            labelFrame.style.display = 'block';
                        }
                    }
                    const awbCode = awbs[boxNo];
                    if (awbCode) {
                        const container = document.getElementById('labels-container-' + boxNo);
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
                });
            }
             // Optionally redirect after a delay
            //  setTimeout(() => {
            //     window.location.href = '<?php //echo base_url("?page=dispatch"); ?>';
            // }, 3000);
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
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


</script>