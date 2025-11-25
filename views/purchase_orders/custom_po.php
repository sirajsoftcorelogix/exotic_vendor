<div class="bg-white p-4 md:p-8">
    <h1 class="text-2xl font-bold mb-6">Create Custom PO</h1>
    <form action="<?php echo base_url('purchase_orders/custompo_post'); ?>" id="create_po" method="post">
    <div class="flex flex-col md:flex-row justify-between mb-8">
        <!-- Left Column -->
         <div class="space-y-2 w-full md:w-auto mt-4 md:mt-0">
            <div class="flex items-center">
                <label for="delivery-due-date" class="block text-gray-700 form-label">Delivery Due Date :<span class="text-red-500"> *</span></label>
                <input type="date" id="delivery_due_date" name="delivery_due_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <!-- <div class="flex items-center">
                <label for="order-id" class="block text-gray-700 form-label">Order ID</label>
                <input type="text" name="order_id" id="order_id" placeholder="2142086" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 placeholder-gray-400 w-full md:w-[150px]">
            </div> -->
            
            <div class="flex items-center">
                <label for="employee-name" class="block text-gray-700 form-label">User Name: <span class="text-red-500"> *</span></label>
                <select name="user_id" id="employee_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 w-full md:w-[150px]">
                    <option value="">Select User</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($id == $_SESSION['user']['id']) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        

        <!-- Right Column -->
        <div class="space-y-2 w-full md:w-auto">
            <div class="flex items-center">
                <label for="vendor" class="block text-gray-700 form-label">Vendor : <span class="text-red-500"> *</span></label>
                <!-- <select id="vendor" name="vendor" class="mt-1 block pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md form-input w-full md:w-[300px]">
                    <option value="">Select Vendor</option>
                    <?php /*foreach ($vendors as $vendor): ?>
                        <option value="<?= $vendor['id'] ?>"><?= htmlspecialchars($vendor['vendor_name']) ?></option>
                    <?php endforeach;*/ ?>

                </select> -->
                <!-- replaced select with autocomplete input -->
                <div style="position:relative; width:100%; max-width:600px;">
                    <input
                        type="text"
                        id="vendor_autocomplete"
                        class="mt-1 block pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md form-input w-full md:w-[300px]"
                        placeholder="Search vendor by name..."
                        autocomplete="off"
                        value=""
                    >
                    <input type="hidden" name="vendor" id="vendor" value="">
                    <div id="vendor_suggestions" class="bg-white border rounded-md shadow-lg mt-1" style="display:none; position:absolute; left:0; right:0; z-index:50; max-height:240px; overflow:auto;"></div>
                </div>
                
            </div>

            <div class="flex items-center">
                <label for="delivery-address" class="block text-gray-700 form-label">Delivery Address : <span class="text-red-500"> *</span></label>
                <select id="delivery_address" name="delivery_address" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[300px]">
                    <option value="">Select Delivery Address</option>
                    <?php foreach ($exotic_address as $address): ?>
                        <option value="<?= $address['id'] ?>" <?= $address['is_default'] ? 'selected' : '' ?>><?= htmlspecialchars($address['address_title']) ?></option>
                    <?php endforeach; ?>
                </select>   
            </div>
        </div>
    </div>

    <!-- Item Table -->
    <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
        <table class="w-full border-separate" id="poTable" style="border-spacing: 0 5px;">
            <thead class="table-header">
            <tr>
                <th class="p-2 text-left w-1/12">Item Code</th>
                <th class="p-2 text-left w-4/12">Item Summary</th>
                <th class="p-2 text-left w-1/12">HSN</th>
                <th class="p-2 text-left w-1/12">Image</th>
                <th class="p-2 text-left w-1/12">GST %</th>
                <th class="p-2 text-left w-1/12">Quantity</th>
                <!-- <th class="p-2 text-left w-1/12">Unit</th> -->
                <th class="p-2 text-left w-1/12">Rate</th>
                <th class="p-2 text-left w-1/12">Amount</th>
                <th class="p-2 text-right w-1/12"></th>
            </tr>
            </thead>
            <tbody class="table-row-text " id="poTableBody">
                
            <tr class="bg-white">
                <td class="p-2 position-relative">
                    <input type="hidden" name="product_id[]" value="<?php echo $data[0]['id'] ?? ''; ?>">
                    <input type="text" name="item_code[]" class="item_code w-[90px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $data[0]['item_code'] ?? ''; ?>" placeholder="Item code" onblur="fetchProductDetails(this)">
                    <!--suggestion box-->
                    <div class="suggestion-box position-absolute z-50 w-64 bg-white border rounded-md shadow-lg mt-1" style="display:none; position:absolute; max-height:240px; overflow:auto;"></div>
                    
                </td>
                
                <td class="p-1 "><textarea name="title[]" class="w-[280px] h-[60px] border rounded-md focus:ring-0 form-input align-middle p-2"><?php echo $data[0]['title'] ?? ''; ?></textarea></td>
                <td class="p-1"><input type="text" name="hsn[]" class="w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $data[0]['hsn'] ?? ''; ?>"></td>
                <td class="p-1">
                    <?php if(isset($data[0]['image']) && !empty($data[0]['image'])){ ?>
                       <img src="<?php echo $data[0]['image']; ?>" alt="Product Image" class="rounded-lg cursor-pointer" >
                        <input type="hidden" name="img[]" value="<?php echo $data[0]['image']; ?>">
                       <?php }else{ ?>
                    <input type="hidden" name="img[]" value="">
                    <div class="flex items-center space-x-2">
                        <img onclick="this.parentElement.querySelector('.img-upload').click();" src="https://placehold.co/100x100/e2e8f0/4a5568?text=Upload" class="rounded-lg cursor-pointer">
                        <input type="file" name="img_upload[]" class="img-upload hidden" accept="image/*" onchange="handleImageUpload(this)">
                        <!-- <button type="button" class="bg-blue-500 text-white px-2 py-1 rounded text-xs" onclick="">Upload</button> -->
                    </div>
                    <?php } ?>
                </td>
                <td class="p-1"><input type="number" name="gst[]" min="0" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $data[0]['gst'] ?? ''; ?>" oninput="calculateTotals()" required></td>
                <td class="p-1">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="quantity[]" min="0" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="<?php echo $data[0]['quantity'] ?? ''; ?>" oninput="calculateTotals()" required>                       
                    </div>
                </td>
                <!-- <td class="p-4">Nos</td> -->
                <td class="p-1">
                    <div class="flex items-center space-x-2">
                        <input type="number" min="0" step="0.01" inputmode="decimal" name="rate[]" value="<?php echo $data[0]['rate'] ?? ''; ?>" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
                        <!-- <input type="checkbox" name="gst_inclusive[]" class="gst_inclusive" value="1" onchange="calculateTotals()">
                        <label for="gst_inclusive">GST inclusive</label> -->
                        
                    </div>
                </td>
                <td class="p-1 rowTotal"></td>
                <td class="p-4 text-right rounded-r-lg">
                        <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"> <span class="text-lg"><i class="fa fa-trash-alt"></i></span> </button>
                </td>
               
                
            </tr>
            
            </tbody>
        </table>
    </div>

    <!-- Add Item Button and Totals -->
    <div class="mt-4 flex justify-between items-start">
        <!-- Add Item Button -->
        <div>
            <!-- + button to add blank row for item -->
            <button type="button" id="addRowBtn" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button-row"><i class="fa fa-plus"></i> Row</button>
            <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Add Item</button>
        </div>
        <!-- Totals Section -->
        <div class="w-1/3">
            <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
                <div class="space-y-1">
                    <div class="flex justify-between subtotal-text">
                        <span>Subtotal :</span>
                        <span id="subtotal_view"></span>
                    </div>
                    <!-- <div class="flex justify-between subtotal-text">
                        <span>Shipping :</span> 
                        <input type="text" name="shipping_cost" id="shipping_cost" class="w-[100px] h-[25px] text-right border rounded-md focus:ring-0 form-input" value="0" oninput="calculateTotals()" required>
                    </div> -->
                    <div class="flex justify-between subtotal-text">
                        <span>GST :</span>
                        <span id="total_gst_view"></span>
                    </div>
                </div>
                <div class="mt-1 border-t border-gray-300 pt-1">
                    <div class="flex justify-between final-total-text">
                        <span>Grand Total :</span>
                        <span id="grand_total_view"></span>
                    </div>
                </div>
                <input type="hidden" name="subtotal" id="subtotal" class="form-control" value="" >
                <input type="hidden" name="total_gst" id="total_gst" class="form-control" value="" >
                <input type="hidden" name="grand_total" id="grandTotal" class="form-control" value="" >
            </div>
        </div>
    </div>

    <hr class="my-8 border-gray-200">

    <!-- Notes and Terms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="flex justify-between items-center mb-1" style="height: 37px;">
                <label for="notes" class="block text-sm font-medium text-gray-700 notes-label">Add Note:</label>
            </div>
            <textarea id="notes" name="notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important note to remember" style="min-height: 148px;"></textarea>
        </div>
        <div>
            <div class="flex justify-between items-center mb-1">
                <label for="terms" class="block text-sm font-medium text-gray-700 notes-label">Terms & Conditions: </label>
                <button id="loadTemplate" type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Load Template</button>
            </div>
            <textarea id="terms" name="terms_and_conditions" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important terms & conditions to remember" style="min-height: 148px;"></textarea>
        </div>  
    </div>

    <!-- Action Buttons -->
    <div id="addPoMsg" style="margin-bottom:10px;"></div>
    <div class="mt-8 flex justify-end space-x-4">
        <input type=checkbox id="isDraft" name="status" value="draft" style="transform: scale(1.5); margin-right: 4px;">
        <label for="isDraft" class="block text-gray-700 form-label" style="margin-top: 4px;">Save as Draft</label>
        <button type="submit" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Create</button>
        <!-- <button type="button" id="draftButton" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md action-button">Draft</button> -->
        <button type="button" id="previewButton" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Preview</button>
        <button type="button" onclick="window.location.href='?page=orders&action=list'" class="bg-black text-white font-semibold py-2 px-4 rounded-md action-button">Cancel</button>
    </div>
    </form>
</div>
<!-- Order Item Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closeOrderModal">&times;</button>
        <h2 class="text-xl font-bold mb-4">Select Product Item</h2>
        <input type="text" id="orderSearch" class="border p-2 w-full mb-4" placeholder="Search with item code, or title...">
        <div class="max-h-80 overflow-y-auto">
            <table class="w-full border">
                <thead>
                    <tr class="sticky top-0 bg-white">
                        <th class="p-2 text-left">Item code</th>
                        <th class="p-2 text-left">Title</th>
                       
                        <th class="p-2 text-left">Size & Color</th>
                        <th class="p-2 text-left">Image</th>
                        <th class="p-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody id="orderList">
                    <!-- Dynamic rows here -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<div id="loadTemplateModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closeLoadTemplateModal">&times;</button>
        <h2 class="text-xl font-bold mb-4">Select Terms & Conditions Template</h2>
        <div class="max-h-72 overflow-y-auto">
            <table class="w-full border">
                <thead>
                    <tr>
                        <th class="p-2 text-right"> # </th>
                        <th class="p-2 text-left">Content</th>
                        <!-- <th class="p-2 text-left">Action</th> -->
                    </tr>
                </thead>
                <tbody id="templateList">
                    <?php foreach ($templates as $template): ?>
                    <tr class="border-b">
                        <td class="p-2 text-right"><input type="checkbox" class="select-template-checkbox" data-content="<?= strip_tags($template['term_conditions'] ?? '') ?>"></td>
                        <td class="p-2 cursor-pointer" onclick="this.parentNode.querySelector('.select-template-checkbox').click();">
                            <?= strip_tags(substr($template['title'], 0, 100)) ?>...
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <tr>
                        <td colspan="3" class="p-2 text-right">
                            <button id="applyTemplate" type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Apply Selected</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>
<!-- Preview PDF Popup -->
<div id="previewPdfModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closePreviewPdf">&times;</button>
        <h2 class="text-xl font-bold mb-4">Custom PO Preview</h2>
        <iframe id="previewPdfFrame" src="" style="width:100%;height:70vh;border:none;"></iframe>
    </div>
</div>
<!-- success popup -->
<div id="successPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md h-56 relative flex flex-col items-center">
        <!-- <button onclick="closeSuccessPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button> -->
        <h2 class="text-xl font-bold mb-6 text-green-600" id="successTitle">Success!</h2>
        <p class="py-8 font-semibold" id="successMessage">Custom PO created successfully. </p>
        <button onclick="closeSuccessPopup()" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">OK</button>
    </div>
</div>
<script>
// Preview button handler
document.querySelector('#previewButton').addEventListener('click', function() {
    // Collect form data
    const form = document.getElementById('create_po');
    const formData = new FormData(form);

    // Send data to backend to generate PDF preview (without saving)
    fetch('<?php echo base_url("?page=purchase_orders&action=preview_pdf"); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.pdf_url) {
            document.getElementById('previewPdfFrame').src = data.pdf_url + '?t=' + Date.now();
            document.getElementById('previewPdfModal').style.display = 'flex';
        } else {
            alert('Failed to generate preview.');
        }
    })
    .catch(() => alert('Error generating preview.'));
});

// Close preview popup
document.getElementById('closePreviewPdf').onclick = function() {
    document.getElementById('previewPdfModal').style.display = 'none';
    document.getElementById('previewPdfFrame').src = '';
};

// Image popup functionality
function openImagePopup(imageUrl) {
    popupImage.src = imageUrl;
    document.getElementById('imagePopup').classList.remove('hidden');
}
function closeImagePopup(e) {
    // If called from button or outside click
    document.getElementById('imagePopup').classList.add('hidden');
}

// Load Template Modal
document.getElementById('loadTemplate').addEventListener('click', function() {
    document.getElementById('loadTemplateModal').style.display = 'flex';
});
document.getElementById('closeLoadTemplateModal').onclick = function() {
    document.getElementById('loadTemplateModal').style.display = 'none';
};  
// Apply selected template
document.getElementById('applyTemplate').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('.select-template-checkbox:checked');
    let combinedContent = '';
    checkboxes.forEach((checkbox, idx) => {
        combinedContent += (idx + 1) + '. ' + checkbox.getAttribute('data-content') + '\n';    
    });
    if (combinedContent) {
        document.getElementById('terms').value = combinedContent.trim();
    }
    document.getElementById('loadTemplateModal').style.display = 'none';
});
// Calculate totals
function calculateTotals() {
    let subtotal = 0;
    let totalGST = 0;
    let grandTotal = 0;
    

    document.querySelectorAll("#poTable tbody tr").forEach(tr => {
        const amount = parseFloat(tr.querySelector(".amount").value) || 0;
        const gstPercent = parseFloat(tr.querySelector(".gst").value) || 0;
        const quantity = parseFloat(tr.querySelector(".quantity").value) || 0;        

        const lineSubtotal = amount * quantity;
        const gstAmount = (lineSubtotal * gstPercent) / 100;
        const rowTotal = lineSubtotal + gstAmount;

        tr.querySelector(".rowTotal").innerText = rowTotal.toFixed(2);

        subtotal += lineSubtotal;
        totalGST += gstAmount;
        grandTotal += rowTotal;
    });

    // const shipping_cost = parseFloat(document.getElementById("shipping_cost").value) || 0;
    // grandTotal += shipping_cost;

    document.getElementById("subtotal").value = subtotal.toFixed(2);
    document.getElementById("total_gst").value = totalGST.toFixed(2);
    document.getElementById("grandTotal").value = grandTotal.toFixed(2);
    document.getElementById("subtotal_view").innerText = "₹" + subtotal.toFixed(2);
    document.getElementById("total_gst_view").innerText = "₹" + totalGST.toFixed(2);
    document.getElementById("grand_total_view").innerText = "₹" + grandTotal.toFixed(2);
}
document.querySelectorAll(".gst, .quantity, .amount").forEach(input => {
    input.addEventListener("input", calculateTotals);
});
document.addEventListener('DOMContentLoaded', function () { 
    const itemTable = document.querySelector('#poTable tbody');
    itemTable.addEventListener('click', function(e) {
            const removeBtn = e.target.closest('.remove-row');
            if (removeBtn) {
                const row = removeBtn.closest('tr');
                if (row) {
                    row.remove();
                    calculateTotals();
                }
            }
        });
    });

// function openPOPopup() {
//     // Show modal
//     new bootstrap.Modal(document.getElementById("createPOModal")).show();
// }
document.getElementById("create_po").addEventListener("submit", function(event) {
    event.preventDefault(); // Prevent default form submission
    // Disable the button and change text
    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    const originalText = submitBtn.textContent;
    submitBtn.textContent = 'Processing...';
    successPopupTitle = document.getElementById("successTitle");
    successPopupMessage = document.getElementById("successMessage");
    const formData = new FormData(this);

    fetch(<?php echo "'".base_url('?page=purchase_orders&action=custompo_post')."'"; ?>, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())  
    .then(data => {
        if (data.success) {
            //alert("Custom PO created successfully!");
            successPopupTitle.innerHTML = "Success <i class=\"fas fa-check-circle\"></i>";
            successPopupMessage.innerHTML = 'Custom PO created successfully 🎉';
            document.getElementById("successPopup").classList.remove("hidden");
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            setTimeout(() => {
                document.getElementById("successPopup").classList.add("hidden");
                window.location.href = "<?php echo base_url('?page=purchase_orders&action=stock_purchase'); ?>"; // Redirect to the list page
            }, 3000);
            
        } else {
            alert("Error: " + data.message);
            // Re-enable the button and restore text
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while creating the Custom PO.");
        submitBtn.disabled = false;
        submitBtn.textContent = originalText;
    });
});

// add item
// Show modal and fetch order items
document.querySelector('.action-button').addEventListener('click', function(e) {
    if (e.target.textContent.trim() === 'Add Item') {
        document.getElementById('orderModal').style.display = 'flex';
        document.getElementById('orderSearch').value = '';
        fetchOrderItems('');
    }
});

// Close modal
document.getElementById('closeOrderModal').onclick = function() {
    document.getElementById('orderModal').style.display = 'none';
};

// Search filter (fetches filtered items)
document.getElementById('orderSearch').addEventListener('input', function() {
    if (this.value.length < 3 && this.value.length > 0) return; // Minimum 3 characters to search
    fetchOrderItems(this.value);
});

// Fetch order items dynamically
function fetchOrderItems(query) {
    const loadingIcon = document.createElement('img');
    loadingIcon.src = '<?php echo base_url("images/loading.gif"); ?>';
    loadingIcon.alt = 'Loading...';
    loadingIcon.className = 'mx-2 my-4 text-center';
    const tbody = document.getElementById('orderList');
    tbody.innerHTML = loadingIcon.outerHTML;
    fetch('?page=purchase_orders&action=product_items&search=' + encodeURIComponent(query))
        .then(r => r.json())
        .then(data => {
            //console.log(data);
            //const tbody = document.getElementById('orderList');
            tbody.innerHTML = '';
            if (Array.isArray(data) && data.length > 0) {
                data.forEach(item => {
                    //console.log(item);
                    const tr = document.createElement('tr');
                    tr.innerHTML = `     
                        <td class="p-2">${item.item_code}</td>                   
                        <td class="p-2">${item.title} ${item.item_code}</td>                        
                        <td class="p-2">${item.size ? item.size : ''} ${item.color ? '- ' + item.color : ''}</td>
                        <td class="p-2"><img src="${item.image}" alt="" class="w-10 h-10 rounded"></td>
                        <td class="p-2">
                            <button type="button" title="Select" class="select-order bg-blue-500 text-white px-3 py-1 rounded"
                                data-id="${item.id}"
                                data-item-code="${(item.item_code ? item.item_code : '').replace(/"/g, '&quot;')}"
                                data-size="${(item.size ? item.size : '').replace(/"/g, '&quot;')}"
                                data-color="${(item.color ? item.color : '').replace(/"/g, '&quot;')}"
                                data-title="${item.title.replace(/"/g, '&quot;')}"
                                data-hsn="${(item.hsn ? item.hsn : '').replace(/"/g, '&quot;')}"
                                data-image="${item.image.replace(/"/g, '&quot;')}"
                                data-gst="${item.gst || 18}"
                                >+</button>
                        </td>
                    `;
                    tbody.appendChild(tr);
                    //alert('Item added to the list.');
                });
            } else {
                //alert('No items found.');
                tbody.innerHTML = '<tr><td colspan="4" class="text-center p-4 text-gray-500">No items found.</td></tr>';
            }
            addSelectOrderListeners();
        });
}

// Insert selected order into poTable
function addSelectOrderListeners() {
    document.querySelectorAll('.select-order').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const id = this.getAttribute('data-id');
            const itemCode = this.getAttribute('data-item-code');
            const orderNumber = this.getAttribute('data-order-number');
            const title = this.getAttribute('data-title');
            const hsn = this.getAttribute('data-hsn');
            const image = this.getAttribute('data-image');
            const poTable = document.querySelector('#poTable tbody');
            const rowCount = poTable.querySelectorAll('tr').length + 1;
            const gst = this.getAttribute('data-gst') || 18; // Default GST

            // Prevent duplicate items
            let exists = false;
            poTable.querySelectorAll('input[name="orderid[]"]').forEach(function(input) {
                if (input.value == id) exists = true;
            });
            if (exists) {
                alert('This item is already added.');
                return;
            }

            const tr = document.createElement('tr');
            tr.className = 'bg-white';
            tr.innerHTML = `
                <td class="p-2 rounded-l-lg"><input type="hidden" name="product_id[]" value="${id}"><input name="item_code[]" value="${itemCode}"></td>
                <td class="p-1"><textarea class="w-[280px] h-[60px] border rounded-md focus:ring-0 form-input align-middle p-2" name="title[]">${title}</textarea></td>
                <td class="p-1"><input name="hsn[]" class="w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="${hsn}"></td>
                <td class="p-1"><input type="hidden" name="img[]" class="w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="${image}"><img src="${image}" onclick="openImagePopup('${image}')" class="rounded-lg cursor-pointer" ></td>
                <td class="p-1"><input type="number" name="gst[]" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="${gst}" oninput="calculateTotals()" required></td>
                <td class="p-1">
                    <div class="flex items-center space-x-2">
                        <input type="number" name="quantity[]" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="1" oninput="calculateTotals()" required>
                    </div>
                </td>
                
                <td class="p-4">
                    <div class="flex items-center space-x-2">
                        <input type="number" min="0" step="0.01" inputmode="decimal" name="rate[]" value="" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
                        
                    </div>
                </td>
                <td class="p-4 rowTotal"></td>
                <td class="">
                    <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"><span class="text-lg"><i class="fa fa-trash-alt"></i></span></button>
                </td>
            `;
            poTable.appendChild(tr);

            // Add event listeners for new inputs
            tr.querySelectorAll('.gst, .quantity, .amount').forEach(input => {
                input.addEventListener('input', calculateTotals);
            });
            tr.querySelector('.remove-row').addEventListener('click', function() {
                tr.remove();
                calculateTotals();
            });

            calculateTotals();
            document.getElementById('orderModal').style.display = 'none';
        });
    });
}

//draft button
/*document.getElementById("draftButton").addEventListener("click", function() {
    const form = document.getElementById('create_po');
    const formData = new FormData(form);
    formData.append('status', 'draft'); // Add status=draft

    fetch(<?php //echo "'".base_url('?page=purchase_orders&action=create_post')."'"; ?>, {
        method: "POST",
        body: formData
    })
    .then(response => response.json())  
    .then(data => {
        if (data.success) {
            alert("Purchase Order saved as draft!");
            window.location.href = "<?php //echo base_url('?page=purchase_orders&action=list&viewpo=true'); ?>"; // Redirect to the list page
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("An error occurred while saving the Purchase Order.");
    });
});*/
// Vendor Autocomplete
document.getElementById('vendor_autocomplete').addEventListener('input', function() {
    const query = this.value;
    const suggestionsBox = document.getElementById('vendor_suggestions');
    const vendorIdInput = document.getElementById('vendor');

    if (query.length < 2) {
        suggestionsBox.style.display = 'none';
        return;
    }

    fetch('<?php echo base_url("?page=purchase_orders&action=vendor_search&query="); ?>' + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {            
            suggestionsBox.innerHTML = '';
            if (Array.isArray(data.data) && data.data.length > 0) {
                data.data.forEach(vendor => {
                    const div = document.createElement('div');
                    div.className = 'p-2 hover:bg-gray-200 cursor-pointer';
                    div.textContent = vendor.vendor_name;
                    div.addEventListener('click', function() {
                        document.getElementById('vendor_autocomplete').value = vendor.vendor_name;
                        vendorIdInput.value = vendor.id;
                        suggestionsBox.style.display = 'none';
                    });
                    suggestionsBox.appendChild(div);
                });
                suggestionsBox.style.display = 'block';
            } else {
                suggestionsBox.style.display = 'none';
            }
        });
});

</script>
<script>
// Reusable: fill row fields from item data
function fillRowFromProduct(tr, item) {
    // Title
    //console.log(item);
    const titleEl = tr.querySelector('textarea[name="title[]"]');
    const itmSummary = item.title + (item.color ? ' - color: ' + item.color : '') + (item.size ? ' - size: ' + item.size : '');
    if (titleEl) titleEl.value = itmSummary || '';

    // HSN
    const hsnEl = tr.querySelector('input[name="hsn[]"]');
    if (hsnEl) hsnEl.value = item.hsn || '';

    // Image hidden and preview
    const imgHidden = tr.querySelector('input[name="img[]"]');
    if (imgHidden) imgHidden.value = item.image || '';
    const imgTag = tr.querySelector('img');
    if (imgTag) {
        imgTag.src = item.image || '';
        imgTag.onclick = function() { openImagePopup(item.image || '') };
    }

    // GST
    const gstEl = tr.querySelector('input[name="gst[]"]');
    if (gstEl) gstEl.value = (item.gst !== undefined) ? item.gst : gstEl.value || 0;

    // Rate
    const rateEl = tr.querySelector('input[name="rate[]"]');
    if (rateEl && item.cost_price !== undefined) rateEl.value = parseFloat(item.cost_price).toFixed(2);

    // Order id / product id
    const orderIdHidden = tr.querySelector('input[name="product_id[]"]');
    if (orderIdHidden) orderIdHidden.value = item.id;

    // item_code field value
    const codeEl = tr.querySelector('input.item_code');
    if (codeEl) codeEl.value = item.item_code || '';

    // Recalculate totals
    if (typeof calculateTotals === 'function') calculateTotals();
}

// Suggestion/autocomplete for a given item_code input
function initItemCodeInput(input) {
    const tr = input.closest('tr');
    const suggBox = tr.querySelector('.suggestion-box');
    let debounceTimer = null;
    let activeIndex = -1;

    function clearSuggestions() {
        if (suggBox) {
            suggBox.innerHTML = '';
            suggBox.style.display = 'none';
        }
        activeIndex = -1;
    }

    function renderSuggestions(list) {
        if (!suggBox) return;
        if (!Array.isArray(list) || list.length === 0) {
            clearSuggestions();
            return;
        }
        suggBox.innerHTML = list.map((v, i) => {
            return `<div class="sugg-item position-relative z-10 w-64 p-2 cursor-pointer hover:bg-gray-300" data-index="${i}" data-id="${escapeHtml(v.id)}" data-json='${escapeHtml(JSON.stringify(v))}' style="padding:8px 10px;">
                        <div style="font-weight:600;">${escapeHtml(v.item_code || '')} — ${escapeHtml(v.title || '')}</div>
                        <div style="font-size:11px;color:#6b7280;">HSN: ${escapeHtml(v.hsn || '')} • GST: ${escapeHtml(String(v.gst || ''))}%</div>
                    </div>`;
        }).join('');
        suggBox.style.display = 'block';
        activeIndex = -1;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s];
        });
    }

    function fetchSuggestions(q) {
        if (!q || q.length < 2) { clearSuggestions(); return; }
        fetch('<?php echo base_url("?page=purchase_orders&action=product_items&type=item_code&search="); ?>' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                renderSuggestions(data || []);
            })
            .catch(err => {
                console.error('Suggestion fetch error', err);
                clearSuggestions();
            });
    }

    // input event: fetch suggestions
    input.addEventListener('input', function() {
        clearSuggestions();
        const q = this.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => fetchSuggestions(q), 180);
    });

    // keyboard navigation & selection
    input.addEventListener('keydown', function(e) {
        const items = suggBox ? suggBox.querySelectorAll('.sugg-item') : [];
        if (!items || items.length === 0) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = Math.min(activeIndex + 1, items.length - 1);
            updateActive(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = Math.max(activeIndex - 1, 0);
            updateActive(items);
        } else if (e.key === 'Enter') {
            if (activeIndex >= 0 && items[activeIndex]) {
                e.preventDefault();
                selectSuggestion(items[activeIndex]);
            }
        } else if (e.key === 'Escape') {
            clearSuggestions();
        }
    });

    function updateActive(items) {
        items.forEach(i => i.style.background = '');
        if (activeIndex >= 0 && items[activeIndex]) {
            items[activeIndex].style.background = '#d2d5dbff';
            items[activeIndex].scrollIntoView({block:'nearest'});
        }
    }

    // click selection
    if (suggBox) {
        suggBox.addEventListener('click', function(e) {
            const itemEl = e.target.closest('.sugg-item');
            if (itemEl) selectSuggestion(itemEl);
        });
    }

    function selectSuggestion(itemEl) {
        try {
            const json = itemEl.getAttribute('data-json') || '{}';
            const item = JSON.parse(json);
            // fill row
            fillRowFromProduct(tr, item);
        } catch (ex) {
            console.error('Invalid item JSON', ex);
        } finally {
            clearSuggestions();
        }
    }

    // hide suggestions when input loses focus (allow click to register)
    input.addEventListener('blur', function() {
        setTimeout(clearSuggestions, 180);
    });
}

// Attach suggestion behavior to all existing item_code inputs
document.querySelectorAll('.item_code').forEach(input => {
    initItemCodeInput(input);
});

// Keep previous fetchProductDetails for cases where code is typed fully and blurred
function fetchProductDetails(el) {
    const code = el.value.trim();
    if (!code) return;
    fetch('<?php echo base_url("?page=purchase_orders&action=product_items&search="); ?>' + encodeURIComponent(code))
        .then(r => r.json())
        .then(data => {
            if (!data[0] || !data[0].id) {
                // no exact match
                return;
            }
            const tr = el.closest('tr');
            if (!tr) return;
            fillRowFromProduct(tr, data[0]);
        })
        .catch(err => {
            console.error('Error fetching product by code', err);
        });
}

// When adding new row, initialize suggestion on its item_code
document.getElementById('addRowBtn').addEventListener('click', function() {
    const poTableBody = document.getElementById('poTableBody');
    const newRow = document.createElement('tr');
    newRow.className = 'bg-white ';
    newRow.innerHTML = `
        <td class="p-2" style="position:relative;">
            <input type="hidden" name="product_id[]" value="">
            <input type="text" name="item_code[]" class="item_code w-[90px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" placeholder="Item code">
            <div class="suggestion-box position-absolute z-10 w-64 bg-white border rounded-md shadow-lg mt-1" style="display:none; position:absolute; z-index:50; max-height:240px; overflow:auto; left:0; right:0;"></div>
        </td>
        <td class="p-1 "><textarea name="title[]" class="w-[280px] h-[60px] border rounded-md focus:ring-0 form-input align-middle p-2"></textarea></td>
        <td class="p-1"><input type="text" name="hsn[]" class="w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value=""></td>
        <td class="p-1">
        <input type="hidden" name="img[]" value=""><img onclick="this.parentElement.querySelector('.img-upload').click()" src="https://placehold.co/100x100/e2e8f0/4a5568?text=Upload" class="rounded-lg cursor-pointer">
        <input type="file" name="img_upload[]" class="img-upload hidden" accept="image/*" onchange="handleImageUpload(this)">        
        </td>
        <td class="p-1"><input type="number" name="gst[]" min="0" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" oninput="calculateTotals()" required></td>
        <td class="p-1">
            <div class="flex items-center space-x-2">
                <input type="number" name="quantity[]" min="0" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" oninput="calculateTotals()" required>  
            </div>
        </td>
        <td class="p-1">
            <div class="flex items-center space-x-2">
                <input type="number" min="0" step="0.01" inputmode="decimal" name="rate[]" value="" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
            </div>
        </td>
        <td class="p-1 rowTotal"></td>
        <td class="p-4 text-right rounded-r-lg">
                <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"> <span class="text-lg"><i class="fa fa-trash-alt"></i></span> </button>
        </td>
    `;
    poTableBody.appendChild(newRow);
    // Attach suggestion init for the new item_code input
    const newInput = newRow.querySelector('.item_code');
    if (newInput) initItemCodeInput(newInput);

    // attach remove-row handler
    newRow.querySelector('.remove-row').addEventListener('click', function() {
        newRow.remove();
        calculateTotals();
    });
});
// image handling
function handleImageUpload(input) {
    const tr = input.closest('tr');
    const imgHidden = tr.querySelector('input[name="img[]"]');
    const imgTag = tr.querySelector('img');
    const file = input.files[0];
    
    if (file) {
        // Validate file type
        if (!file.type.startsWith('image/')) {
            alert('Please select a valid image file');
            return;
        }
        
        // Validate file size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
            alert('Image size should not exceed 5MB');
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const imgData = e.target.result;
            if (imgHidden) imgHidden.value = imgData;
            if (imgTag) {
                imgTag.src = imgData;
                imgTag.style.display = 'block';
                imgTag.onclick = function() { openImagePopup(imgData); };
            }
            // Add remove icon to the image
            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute top-0 right-0 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm font-bold hover:bg-red-700';
            removeBtn.innerHTML = '✕';
            removeBtn.onclick = function(e) {
                e.preventDefault();
                imgTag.src = 'https://placehold.co/100x100/e2e8f0/4a5568?text=Upload';
                imgHidden.value = '';
                input.value = '';
                imgTag.parentElement.removeChild(removeBtn);
                //upload on click again
                imgTag.onclick = function() {
                    input.click();
                };
            };
            imgTag.parentElement.style.position = 'relative';
            imgTag.parentElement.appendChild(removeBtn);
        };
        reader.onerror = function() {
            alert('Error reading file');
        };
        reader.readAsDataURL(file);
    }
}
function closeSuccessPopup() {
    document.getElementById("successPopup").classList.add("hidden");
}
// function fetchProductDetails(el) {
//     const code = el.value.trim();
//     if (!code) return;
//     fetch('<?php //echo base_url("?page=purchase_orders&action=product_items&search="); ?>' + encodeURIComponent(code))
//         .then(r => r.json())
//         .then(data => {
//             console.log(data[0]);
//             if (!data[0] || !data[0].id) {
//                 alert('Product not found');
//                 return;
//             }
//             const tr = el.closest('tr');
//             if (!tr) return;

//             // Title
//             const titleEl = tr.querySelector('textarea[name="title[]"]');
//             if (titleEl) titleEl.value = data[0].title || '';

//             // HSN
//             const hsnEl = tr.querySelector('input[name="hsn[]"]');
//             if (hsnEl) hsnEl.value = data[0].hsn || '';

//             // Image hidden and preview
//             const imgHidden = tr.querySelector('input[name="img[]"]');
//             if (imgHidden) imgHidden.value = data[0].image || '';
//             const imgTag = tr.querySelector('img');
//             if (imgTag) {
//                 imgTag.src = data[0].image || '';
//                 imgTag.onclick = function() { openImagePopup(data[0].image || '') };
//             }

//             // GST
//             const gstEl = tr.querySelector('input[name="gst[]"]');
//             if (gstEl) gstEl.value = (data[0].gst !== undefined) ? data[0].gst : gstEl.value || 0;

//             // Rate
//             const rateEl = tr.querySelector('input[name="rate[]"]');
//             if (rateEl && data[0].rate !== undefined) rateEl.value = parseFloat(data[0].rate).toFixed(2);

//             // Optionally set orderid hidden if present
//             const orderIdHidden = tr.querySelector('input[name="orderid[]"]');
//             if (orderIdHidden) orderIdHidden.value = data[0].id;

//             // Recalculate totals
//             if (typeof calculateTotals === 'function') calculateTotals();
//         })
//         .catch(err => {
//             console.error('Error fetching product by code', err);
//         });
// }
// // Attach event listener to existing item_code inputs
// document.querySelectorAll('.item_code').forEach(input => {
//     input.addEventListener('blur', function() {
//         fetchProductDetails(this);
//     });
// });
// // Add Row button functionality
// document.getElementById('addRowBtn').addEventListener('click', function() {
//     const poTableBody = document.getElementById('poTableBody');
//     const newRow = document.createElement('tr');
//     newRow.className = 'bg-white ';
//     newRow.innerHTML = `
//         <td class="p-2">
//             <input type="text" name="item_code[]" class="item_code w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" placeholder="Item code" onblur="fetchProductDetails(this)">
//         </td>
        
//         <td class="p-1 "><textarea name="title[]" class="w-[280px] h-[60px] border rounded-md focus:ring-0 form-input align-middle p-2"></textarea></td>
//         <td class="p-1"><input type="text" name="hsn[]" class="w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value=""></td>
//         <td class="p-1"><input type="hidden" name="img[]" value=""><img onclick="openImagePopup('')" src="" class="rounded-lg cursor-pointer"></td>
//         <td class="p-1"><input type="number" name="gst[]" min="0" class="gst w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" oninput="calculateTotals()" required></td>
//         <td class="p-1">
//             <div class="flex items-center space-x-2">
//                 <input type="number" name="quantity[]" min="0" class="quantity w-[80px] h-[25px] text-center border rounded-md focus:ring-0 form-input" value="" oninput="calculateTotals()" required>  
//             </div>
//         </td>
//         <td class="p-1">
//             <div class="flex items-center space-x-2">
//                 <input type="number" min="0" step="0.01" inputmode="decimal" name="rate[]" value="" oninput="calculateTotals()" required class="amount w-[105px] h-[25px] text-center border rounded-md focus:ring-0 form-input">
//             </div>
//         </td>
//         <td class="p-1 rowTotal"></td>
//         <td class="p-4 text-right rounded-r-lg">
//                 <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"> <span class="text-lg"><i class="fa fa-trash-alt"></i></span> </button>
//         </td>
//     `;
//     poTableBody.appendChild(newRow);
//     // Attach event listener to the new item_code input
//     newRow.querySelector('.item_code').addEventListener('blur', function() {
//         fetchProductDetails(this);
//     });
// });

</script>