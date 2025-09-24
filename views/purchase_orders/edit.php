<div class="bg-white p-4 md:p-8">
    <h1>Edit Purchase Order</h1>
    <form action="<?php echo base_url('purchase_orders/edit_post'); ?>" id="edit_po" method="post">
        <input type="hidden" name="po_id" value="<?php echo $data['purchaseOrder']['id']; ?>">
        <div class="flex flex-col md:flex-row justify-between mb-8">
        <!-- Left Column -->
        <div class="space-y-2 w-full md:w-auto mt-4 md:mt-0">
            <div class="flex items-center">
                <label for="po_number" class="block text-gray-700 form-label">PO Number :</label>
                <input type="text" id="po_number" name="po_number" value="<?php echo htmlspecialchars($data['purchaseOrder']['po_number'] ?? ''); ?>" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]" readonly>
            </div>
            <div class="flex items-center">
                <label for="delivery-due-date" class="block text-gray-700 form-label">Delivery Due Date :</label>
                <input readonly type="date" id="delivery_due_date" value="<?php echo $data['purchaseOrder']['expected_delivery_date'] ? date('Y-m-d', strtotime($data['purchaseOrder']['expected_delivery_date'])) : ''; ?>" name="delivery_due_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]">
            </div>
            <div class="flex items-center">
                <label for="po_date" class="block text-gray-700 form-label">Order Date :</label>
                <input readonly type="date" id="po_date" name="po_date" value="<?php echo date('Y-m-d', strtotime($data['purchaseOrder']['po_date'])); ?>" class="mt-1 block w-full md:w-[150px] border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm form-input">
            </div>
        </div>
        <!-- Right Column -->
        <div class="space-y-2 w-full md:w-auto">
            <div class="flex items-center">
                <label for="vendor" class="block text-gray-700 form-label">Vendor :</label>
                <select id="vendor" name="vendor_id" class="mt-1 block pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md form-input w-full md:w-[300px]">
                    <option value="">Select Vendor</option>
                    <?php foreach ($data['vendors'] as $vendor): ?>
                        <option value="<?php echo $vendor['id']; ?>" <?php if ($data['purchaseOrder']['vendor_id'] == $vendor['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($vendor['vendor_name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="flex items-center">
                <label for="employee-name" class="block text-gray-700 form-label">User Name</label>
                <select name="user_id" id="employee_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 w-full md:w-[300px]">
                    <option value="">Select User</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>" <?php if ($data['purchaseOrder']['user_id'] == $id) echo 'selected'; ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center">
                <label for="delivery-address" class="block text-gray-700 form-label">Delivery Address :</label>
                <select id="delivery_address" name="delivery_address" class="mt-1 block pl-3 pr-10 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md form-input w-full md:w-[300px]">
                    <option value="">Select Delivery Address</option>
                    <?php foreach ($data['deliveryAddresses'] as $address): ?>
                        <option value="<?php echo $address['id']; ?>" <?php if ($data['purchaseOrder']['delivery_address'] == $address['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($address['address'] ?? ''); ?>
                        </option>
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
                <th class="p-2 text-left w-1/12">S.No</th>
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
            <tbody class="table-row-text">
            <?php foreach($items as $index => $item): ?>
                <tr class="bg-white shadow-sm rounded-lg">
                    <td class="p-2 align-top"><input type="hidden" name="item_ids[]" value="<?php echo $item['id']; ?>"><?php echo $index + 1; ?></td>
                    <td class="p-2 align-top"><textarea name="title[]" class="form-input h-16 w-full p-2"><?php echo htmlspecialchars($item['title'] ?? ''); ?></textarea></td>
                    <td class="p-2 align-top"><?php echo htmlspecialchars($item['hsn'] ?? ''); ?></td>
                    <td class="p-1 align-top">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image'] ?? ''); ?>" alt="Item Image" class="h-20 object-cover rounded">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td class="p-2 align-top"><input type="text" name="gst[]" value="<?php echo htmlspecialchars($item['gst'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <td class="p-2 align-top"><input type="text" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <!-- <td class="p-2 align-top"></td> -->
                    <td class="p-2 align-top"><input type="text" name="price[]" value="<?php echo htmlspecialchars($item['price'] ?? ''); ?>" class="form-input w-[80px] " />
                    </td>
                    <td class="p-2 align-top"><input type="text" name="amount[]" value="<?php echo htmlspecialchars($item['amount'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <td class="p-2 align-top text-right">
                        <!-- <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"><span class="text-lg"><i class="fa fa-trash-alt"></i></span></button> -->
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <!-- Add Item Button and Totals -->
    <div class="mt-4 flex justify-between items-start">
        <div>
            <!-- <button type="button" class="add-item bg-blue-500 text-white px-4 py-2 rounded">Add Item</button> -->
        </div> 
        <div class="w-1/3">
            <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg">
                <div class="space-y-1">
                    <div class="flex justify-between subtotal-text">
                        <span >Subtotal:</span>
                        <span class="subtotal"><?php echo number_format(($data['purchaseOrder']['subtotal'] ?? 0), 2); ?></span>
                    </div>
                    <div class="flex justify-between subtotal-text">
                        <span >Total GST:</span>
                        <span class="total-gst"><?php echo number_format(($data['purchaseOrder']['total_gst'] ?? 0), 2); ?></span>
                    </div>
                    <!-- <div class="flex justify-between">
                        <span class="font-bold">Shipping Cost:</span>
                        <span class="shipping-cost"><input type="text" name="shipping_cost" value="<?php //echo number_format(($data['purchaseOrder']['shipping_cost'] ?? 0), 2); ?>" class="form-input w-[80px] " /></span>
                    </div> -->
                </div>
                <div class="mt-1 border-t border-gray-300 pt-1">
                    <div class="flex justify-between final-total-text">
                        <span >Grand Total:</span>
                        <span class="grand-total"><?php echo number_format(($data['purchaseOrder']['total_cost'] ?? 0), 2); ?></span>
                    </div>
                </div>
                
            </div>
        </div>
        <input type="hidden" name="total_gst" value="<?php echo htmlspecialchars($data['purchaseOrder']['total_gst'] ?? 0); ?>" />
        <input type="hidden" name="grand_total" value="<?php echo htmlspecialchars($data['purchaseOrder']['total_cost'] ?? 0); ?>" />
        <input type="hidden" name="subtotal" value="<?php echo htmlspecialchars($data['purchaseOrder']['subtotal'] ?? 0); ?>" />
        
    </div>

    <hr class="my-8 border-gray-200">

    <!-- Notes and Terms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
        <div>
            <div class="flex justify-between items-center mb-1" style="height: 37px;">
                <label for="notes" class="block text-sm font-medium text-gray-700 notes-label">Add Note:</label>
            </div>
            <textarea id="notes" name="notes" class="mt-5 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important note to remember" style="min-height: 148px;"><?php echo $purchaseOrder['notes']; ?></textarea>
        </div>
        <div>
            <div class="flex justify-between items-center mb-1">
                <label for="terms" class="block text-sm font-medium text-gray-700 notes-label">Terms & Conditions:</label>
                <button id="loadTemplate" type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Load Template</button>
            </div>
            <textarea id="terms" name="terms_and_conditions" class="mt-5 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important terms & conditions to remember" style="min-height: 148px;"><?php echo htmlspecialchars($purchaseOrder['terms_and_conditions'] ?? ''); ?></textarea>
        </div>
    </div>
    <!-- Action Buttons -->
    <div class="mt-8 flex justify-end space-x-4">
        
        <?php /*if($purchaseOrder['status'] == 'draft'): ?>
        <button type="button" id="saveDraft" class="bg-yellow-500 text-white font-semibold py-2 px-4 rounded-md">Save as Draft</button>
        <button type="button" id="submitToApprove" class="bg-green-500 text-white font-semibold py-2 px-4 rounded-md">Submit to Approve</button>
        <?php endif; */?>
        <?php if($purchaseOrder['status'] == 'draft'): ?>
            <input type=checkbox id="isDraft" name="status" value="draft" checked style="transform: scale(1.5); margin-right: 8px;">
        <?php else: ?>
            <input type=checkbox id="isDraft" name="status" value="draft" style="transform: scale(1.5); margin-right: 8px;">
        <?php endif; ?>
        <label for="isDraft" class="block text-gray-700 form-label" style="margin-top: 4px;">Save as Draft</label>
        <button type="button" id="saveChanges" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">Save Changes</button>
        <a href="<?php echo base_url('?page=purchase_orders&action=list'); ?>" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md">Back</a>
    </div>
    </form>
</div>
<div id="loadTemplateModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-2xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closeLoadTemplateModal">&times;</button>
        <h2 class="text-xl font-bold mb-4">Select Terms & Conditions Template</h2>
        <div class="max-h-72 overflow-y-auto">
            <table class="w-full border">
                <thead>
                    <tr>
                        <th class="p-2 text-right">#</th>
                        <th class="p-2 text-left">Content</th>
                        <!-- <th class="p-2 text-left">Action</th> -->
                    </tr>
                </thead>
                <tbody id="templateList">
                    <?php foreach ($templates as $template): ?>
                    <tr class="border-b">
                        <td class="p-2 text-right"><input type="checkbox" class="select-template-checkbox" data-content="<?= strip_tags($template['term_conditions'] ?? '') ?>"></td>
                        <td class="p-2" onclick="this.parentNode.querySelector('.select-template-checkbox').click();"><?= strip_tags(substr($template['title'], 0, 100)) ?>...</td>                       
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
<script>
    // Load Template Modal
    document.getElementById('loadTemplate').addEventListener('click', function() {
        document.getElementById('loadTemplateModal').style.display = 'flex';
        // Pre-select checkboxes for templates that are present in the current terms_and_conditions
        // var termsValue = document.getElementById('terms').value;
        // document.querySelectorAll('.select-template-checkbox').forEach(function(checkbox) {
        //     var content = checkbox.getAttribute('data-content');
        //     // Check if the content exists in the textarea (simple substring match)
        //     if (termsValue.includes(content)) {
        //         checkbox.checked = true;
        //     } else {
        //         checkbox.checked = false;
        //     }
        // });
    });
   
    document.getElementById('closeLoadTemplateModal').onclick = function() {
        document.getElementById('loadTemplateModal').style.display = 'none';
    };  
    // Apply selected template
    document.getElementById('applyTemplate').addEventListener('click', function() {
        const checkboxes = document.querySelectorAll('.select-template-checkbox:checked');
        let combinedContent = '';
        checkboxes.forEach((checkbox, idx) => {
            combinedContent += (idx + 1) + '. ' + checkbox.getAttribute('data-content') + '\n\n';    
        });
        if (combinedContent) {
            document.getElementById('terms').value = combinedContent.trim();
        }
        document.getElementById('loadTemplateModal').style.display = 'none';
    });
    document.addEventListener('DOMContentLoaded', function () {
        const itemTable = document.querySelector('#poTable tbody');
        const subtotalElement = document.querySelector('.subtotal');
        const totalGstElement = document.querySelector('.total-gst');
        const grandTotalElement = document.querySelector('.grand-total');
        const shippingCostElement = document.querySelector('input[name="shipping_cost"]');

        function updateTotals() {
            let subtotal = 0;
            let totalGst = 0;

            itemTable.querySelectorAll('tr').forEach(row => {
                const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
                const quantity = parseFloat(row.querySelector('input[name="quantity[]"]').value) || 0;
                const gst = parseFloat(row.querySelector('input[name="gst[]"]').value) || 0;

                const amount = price * quantity;
                row.querySelector('input[name="amount[]"]').value = amount.toFixed(2);
                subtotal += amount;
                totalGst += (amount * gst) / 100;
            });

            const grandTotal = subtotal + totalGst; //+ (parseFloat(shippingCostElement.value) || 0);

            subtotalElement.textContent = `${subtotal.toFixed(2)}`;
            totalGstElement.textContent = `${totalGst.toFixed(2)}`;
            grandTotalElement.textContent = `${grandTotal.toFixed(2)}`;
            document.querySelector('input[name="subtotal"]').value = subtotal.toFixed(2);
            document.querySelector('input[name="total_gst"]').value = totalGst.toFixed(2);
            document.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
            //document.querySelector('input[name="shipping_cost"]').value = (parseFloat(shippingCostElement.textContent.replace('₹', '')) || 0).toFixed(2);
        }

        itemTable.addEventListener('input', updateTotals);
        //shippingCostElement.addEventListener('input', updateTotals);
            // Remove item row and update totals
        /*itemTable.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-row')) {
                const row = e.target.closest('tr');
                if (row) {
                    row.remove();
                    updateTotals();
                }
            }
        });*/
    });
    // document.getElementById("edit_po").addEventListener("submit", function(event) {
    //     event.preventDefault(); // Prevent default form submission
    function formSubmitHandler(event) {
        event.preventDefault(); // Prevent default form submission
        const submitBtn = this.querySelector('#saveChanges');
        submitBtn.disabled = true;
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Processing...';
   
        const formData = new FormData(this);
        
        fetch(<?php echo "'".base_url('?page=purchase_orders&action=edit_post')."'"; ?>, {
            method: "POST",
            body: formData
        })
        .then(response => response.json())  
        .then(data => {
            if (data.success) {
                alert("Purchase Order created successfully!");
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                //window.location.href = "<?php echo base_url('?page=purchase_orders&action=list'); ?>"; // Redirect to the list page
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
    //});
    // Save Changes
    document.getElementById('saveChanges').addEventListener('click', function() {
        const form = document.getElementById('edit_po');
        // Remove any existing status input to avoid duplicates
        // const existingStatusInput = form.querySelector('input[name="status"]');
        // if (existingStatusInput) {
        //     existingStatusInput.remove();
        // }
        // const statusInput = document.createElement('input');
        // statusInput.type = 'hidden';
        // statusInput.name = 'status';
        // statusInput.value = '<?php //echo $purchaseOrder['status']; ?>'; // Keep the current status
        // form.appendChild(statusInput);
        formSubmitHandler.call(form, new Event('submit'));
    });
    // // Save as Draft
    // document.getElementById('saveDraft').addEventListener('click', function() {
    //     const form = document.getElementById('edit_po');
    //     const statusInput = document.createElement('input');
    //     statusInput.type = 'hidden';
    //     statusInput.name = 'status';
    //     statusInput.value = 'draft';
    //     form.appendChild(statusInput);
    //     formSubmitHandler.call(form, new Event('submit'));
    // });
    // // Submit to Approve
    // document.getElementById('submitToApprove').addEventListener('click', function() {
    //     const form = document.getElementById('edit_po');
    //     const statusInput = document.createElement('input');
    //     statusInput.type = 'hidden';
    //     statusInput.name = 'status';
    //     statusInput.value = 'pending';
    //     form.appendChild(statusInput);
    //     formSubmitHandler.call(form, new Event('submit'));
    // });
</script>
