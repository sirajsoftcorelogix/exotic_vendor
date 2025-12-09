<div class="container mx-auto px-4">    
    <div class="pt-8 pb-6 text-center">
        <h1 class="type-page-header text-base md:text-lg">Create</h1>
        <h1 class="text-2xl md:text-4xl font-bold">Goods Receipt Note</h1> 
    </div>
    <!-- Vendor Info Section -->
     <?php //print_array($purchaseOrder);?>
    <!-- Added border-b border-gray-200 to this container so line takes full width of main container -->
    <div class="w-full mb-8 border-b border-gray-200 pb-8">
        <!-- Inner content centered with specific width -->
        <div class="w-full md:w-[800px] mx-auto grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-0">
            <!-- Left Column -->
            <div class="space-y-0">
                <div class="flex">
                    <span class="type-label-small w-32">PO Number</span>
                    <span class="type-data-small">: &nbsp; <?= htmlspecialchars($purchaseOrder['po_number'] ?? '') ?></span>
                </div>
                <div class="flex">
                    <span class="type-label-small w-32">Vendor Name</span>
                    <span class="type-data-small">: &nbsp; <?= htmlspecialchars($purchaseOrder['vendor_name'] ?? '') ?></span>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-0">
                <div class="flex">
                    <span class="type-label-small w-32">Vendor Phone</span>
                    <span class="type-data-small">: &nbsp;<a href="tel:<?= htmlspecialchars($purchaseOrder['vendor_phone'] ?? '') ?>"><?= htmlspecialchars($purchaseOrder['vendor_phone'] ?? '') ?></a></span>
                </div>
                <div class="flex">
                    <span class="type-label-small w-32">Vendor Email</span>
                    <span class="type-data-small">: &nbsp; <a href="mailto:<?= htmlspecialchars($purchaseOrder['vendor_email'] ?? '') ?>"><?= htmlspecialchars($purchaseOrder['vendor_email'] ?? '') ?></a></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Item Summary Header -->
    <div class="mb-4">
        <h2 class="type-page-header inline-block">Item Summary</h2>
    </div>
    <form method="post" id="createGrnForm" action="">
    <!-- Cards Container -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
    <?php foreach($items as $item): ?>
        <!-- Item Cards -->
        <div class="custom-card p-5">
            <div class="flex flex-col sm:flex-row gap-5 mb-5">
                <!-- Image Placeholder -->
                <div class="w-full sm:w-32 h-40 shrink-0 bg-gray-200 rounded-md overflow-hidden border border-gray-300 flex items-center justify-center">
                    <?php if (!empty($item['image'])): ?>
                        <img onclick="openImagePopup('<?php echo $item['image']; ?>')" src="<?php echo $item['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image';?>" alt="Item Image" class="max-w-full max-h-full object-contain cursor-pointer ">
                    <?php else: ?>
                        <span class="text-gray-500 text-sm">No Image</span>
                    <?php endif; ?>
                </div>
                
                <!-- Details -->
                <div class="flex-1">
                    <h3 class="type-item-name mb-3"><?= htmlspecialchars($item['title'] ?? '') ?></h3>

                    <div class="grid grid-cols-2 gap-x-4 gap-y-0">
                        <!-- Row 1 -->
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Quantity</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['quantity'] ?? '') ?></span>
                        </div>
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Height</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['prod_height'] ?? '') ?> cm</span>
                        </div>

                        <!-- Row 2 -->
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Weight</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['product_weight'] ?? '') ?> <?= htmlspecialchars($item['product_weight_unit'] ?? '') ?></span>
                        </div>
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Width</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['prod_width'] ?? '') ?></span>
                        </div>

                        <!-- Row 3 -->
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Material</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['material'] ?? '') ?></span>
                        </div>
                        
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Depth</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['prod_length'] ?? '') ?> <?= htmlspecialchars($item['length_unit'] ?? '') ?></span>
                        </div>
                        <!-- Row 4 -->
                        <div class="flex items-baseline">
                            <span class="type-label-small w-20 shrink-0">Previously Received Qty</span>
                            <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['current_stock'] ?? '') ?></span>
                        </div>
                        
                        <!-- Row 5 -->
                        
                    </div>
                </div>
            </div>

            <!-- Checkboxes -->
            <div class="flex flex-wrap gap-4 mb-5">
                <!-- <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" class="custom-checkbox">
                    <span class="type-checkbox-label">Received</span>
                </label> -->
                  
                <label class="flex items-center gap-2 cursor-pointer">
                    <input name="qty_acceptable[]" type="checkbox" class="custom-checkbox" value="1">
                    <span class="type-checkbox-label">Quality Acceptable</span>                    
                </label>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input name="qty_received[]" type="number" min="0" class="ml-0 w-16 px-2 py-1 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-gray-400" placeholder="Qty">
                    <span class="type-checkbox-label">Quantity Received</span>                    
                </label>
                <!-- <select name="warehouse_id[]" class="flex items-center gap-2 w-auto px-2 py-1 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 border rounded-md">
                    <option value="">Select Delivery Address</option>
                    <?php /*foreach ($exotic_address as $address): ?>
                        <option value="<?= $address['id'] ?>" <?= $address['is_default'] ? 'selected' : '' ?>><?= htmlspecialchars($address['address_title']) ?></option>
                    <?php endforeach; */?>
                </select>  -->
            </div>

            <!-- Remarks Area -->
            <div>
                    <textarea
                            class="w-full p-3 rounded-xl border text-gray-600 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 resize-none"
                            style="border-color: rgba(163, 163, 163, 1); height: 100px;"
                            placeholder="Remarks, if any"
                            name="remarks[]"></textarea>
            </div>
            
            <input type="hidden" name="item_code[]" value="<?php echo htmlspecialchars($item['item_code'] ?? '') ?>">
            <input type="hidden" name="color[]" value="<?php echo htmlspecialchars($item['color'] ?? '') ?>">
            <input type="hidden" name="size[]" value="<?php echo htmlspecialchars($item['size'] ?? '') ?>">
        </div>
    <?php endforeach; ?>
       

    </div>

    <!-- Footer Actions - Centered & Fixed Widths -->
    <div class="mt-8 flex flex-col gap-8 items-center">

        <!-- Row 1: Inputs (Centered with fixed 210px width) -->
        <div class="flex flex-col md:flex-row gap-6 justify-center w-full">
            <div class="w-[210px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Received Date <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="date" name="received_date" value="<?php echo htmlspecialchars(date('Y-m-d')); ?>" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-500 text-sm focus:outline-none">
                    
                </div>
            </div>
            <div class="w-[210px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Received by <span class="text-red-500">*</span></label>
                <select name="received_by" id="employee_name" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
                    <option value="">Select User</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($id == $_SESSION['user']['id']) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
                <!-- <input type="text" name="received_by" value="<?php //echo $_SESSION['user']['name'] ?? '' ?>" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none"> -->
            </div>
            <div class="w-[210px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Warehouse <span class="text-red-500">*</span></label>
                <select name="warehouse_id" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
                    <option value="">Select Warehouse</option>
                    <?php foreach ($exotic_address as $warehouse): ?>
                        <option value="<?= $warehouse['id'] ?>"><?= htmlspecialchars($warehouse['address_title']) ?></option>
                    <?php endforeach; ?>
                </select>            
            </div>
            <div class="w-[210px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Image <span class="text-red-500"></span></label>
                <input type="file" name="grn_file[]" multiple class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
            </div>
        </div>

        <!-- Row 2: Buttons (Centered with fixed 210px width) -->
        <div class="flex flex-col md:flex-row gap-4 justify-center w-full">
            <input type="hidden" name="purchase_order_id" value="<?= $purchaseOrder['id'] ?>">
            <input type="hidden" name="po_id" value="<?php echo htmlspecialchars($purchaseOrder['id'] ?? '') ?>">
            <input type="hidden" name="po_number" value="<?php echo htmlspecialchars($purchaseOrder['po_number'] ?? '') ?>">
            <button onclick="savegrn(event)" id="saveChanges" class="w-[210px] bg-black text-white font-medium py-3 px-6 rounded-full shadow hover:bg-gray-800 transition-colors">
                Save & Submit
            </button>
            <button class="w-[210px] bg-black text-white font-medium py-3 px-6 rounded-full shadow hover:bg-gray-800 transition-colors">
                Back to Scan
            </button>
        </div>
    </form>
    </div>
</div>
<!-- success popup -->
<div id="successPopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg shadow-lg text-center">
        <h2 class="text-2xl font-bold mb-4">Success!</h2>
        <p class="mb-6">The Goods Receipt Note has been created successfully.</p>
        <button onclick="closePopup()" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Close</button>
    </div>
</div>
<!-- Main Success Content -->
<div id="success-message" class="container mx-auto p-4 bg-white w-full max-w-lg flex flex-col items-center text-center" style="display: none;">
    <!-- Success Icon -->
    <div class="w-32 h-32 flex items-center justify-center mb-6">
        <svg class="w-full h-full" viewBox="0 0 127 127" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd" d="M95.1275 39.4239C97.0002 41.289 97.0063 44.3192 95.1412 46.1919L54.8166 86.6804C52.9515 88.5531 49.9215 88.5593 48.0487 86.6943L33.3426 72.0491C31.4698 70.1841 31.4635 67.1539 33.3285 65.2811C35.1936 63.4083 38.2237 63.402 40.0965 65.267L51.4118 76.5354L88.3595 39.4376C90.2246 37.5649 93.2547 37.5588 95.1275 39.4239Z" fill="#D06706"/>
            <path fill-rule="evenodd" clip-rule="evenodd" d="M63.7211 9.65676C38.3533 9.65676 17.0715 27.3704 11.6085 51.1002L11.6085 51.1003C10.7192 54.9628 10.2483 58.9889 10.2483 63.1296C10.2483 92.6394 34.2113 116.602 63.7211 116.602C93.2309 116.602 117.194 92.6394 117.194 63.1296C117.194 33.6198 93.2309 9.65676 63.7211 9.65676ZM2.28104 48.9529C8.72138 20.9777 33.7928 0.0853271 63.7211 0.0853271C98.5171 0.0853271 126.765 28.3336 126.765 63.1296C126.765 97.9255 98.5171 126.174 63.7211 126.174C28.9252 126.174 0.67688 97.9255 0.67688 63.1296C0.67688 58.2608 1.23082 53.5145 2.28104 48.9529Z" fill="#D06706" fill-opacity="0.32"/>
        </svg>
    </div>

    <h2 class="text-3xl font-bold text-gray-800 mb-2">Success!</h2>
    <p class="text-gray-500 mb-8">Thank you for creating the Goods Receipt Note (GRN). Your submission has been successfully recorded.</p>

    <!-- <button class="w-full bg-[#D06706] text-white font-bold py-3 rounded-lg shadow-lg hover:bg-orange-700 transition duration-300">
        Continue
    </button> -->
    <div class="mt-4">
        <a href="?page=purchase_orders&action=list" class="create-po-btn">Continue</a>
    </div>
</div>
<div id="imagePopup" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-75 hidden">
    <div class="relative">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" src="" alt="Popup Image" class="max-w-full max-h-screen rounded-lg shadow-lg">
    </div>
</div>
<script>
        function closePopup() {
            const successPopup = document.getElementById('successPopup');
            successPopup.classList.add('hidden');
            //window.location.href = "<?php echo base_url('?page=purchase_orders&action=list'); ?>"; // Redirect to the list page
        }
        function savegrn(event){
            event.preventDefault(); // Prevent default form submission
            //qty received validation for at least one item
            const qtyReceivedInputs = document.querySelectorAll('input[name="qty_received[]"]');
            let valid = false;
            qtyReceivedInputs.forEach(input => {
                if (input.value && parseInt(input.value) > 0) {
                    valid = true;
                }
            });
            if (!valid) {
                alert("Please enter valid quantities.");
                return;
            }
            //wherehouse validation
            const warehouseSelect = document.querySelector('select[name="warehouse_id"]');
            if (!warehouseSelect || !warehouseSelect.value) {
                alert("Please select a warehouse.");
                return;
            }
            const submitBtn = document.getElementById('saveChanges');
            submitBtn.disabled = true;
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Processing...';
    
            const formElement = document.getElementById('createGrnForm');
            const formData = new FormData(formElement);
            
            fetch(<?php echo "'".base_url('?page=grns&action=create_post')."'"; ?>, {
                method: "POST",
                body: formData
            })
            .then(response => response.json())  
            .then(data => {
                if (data.success) {
                    //alert("GRN created successfully!");
                    //const successPopup = document.getElementById('successPopup');
                    //successPopup.classList.remove('hidden');
                    document.getElementById('createGrnForm').style.display = 'none';
                    document.getElementById('success-message').style.display = 'flex';
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                    // 5 seconds redirect
                    setTimeout(() => {
                    window.location.href = "<?php echo base_url('?page=purchase_orders&action=list'); ?>"; // Redirect to the reffer page
                    }, 5000);
                } else {
                    alert("Error: " + data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
                alert("An error occurred while creating the Purchase Order.");
            });
        }
        function openImagePopup(imageUrl) {
            const popup = document.getElementById('imagePopup');
            const popupImage = document.getElementById('popupImage');
            popupImage.src = imageUrl;
            popup.classList.remove('hidden');
        }
        function closeImagePopup() {
            const popup = document.getElementById('imagePopup');
            popup.classList.add('hidden');
            popupImage.src = '';
        }
    </script>