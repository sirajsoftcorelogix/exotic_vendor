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
                <input type="date" id="delivery_due_date" value="<?php echo $data['purchaseOrder']['expected_delivery_date'] ? date('Y-m-d', strtotime($data['purchaseOrder']['expected_delivery_date'])) : ''; ?>" name="delivery_due_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]">
            </div>
            <div class="flex items-center">
                <label for="employee-name" class="block text-gray-700 form-label">User Name</label>
                <select name="user_id" id="employee_name" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md bg-white form-input px-3 w-full md:w-[150px]">
                    <option value="">Select User</option>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>" <?php if ($data['purchaseOrder']['user_id'] == $id) echo 'selected'; ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
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
                            <?php echo htmlspecialchars($vendor['contact_name'] ?? ''); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-center">
                <label for="po_date" class="block text-gray-700 form-label">Order Date :</label>
                <input type="date" id="po_date" name="po_date" value="<?php echo date('Y-m-d', strtotime($data['purchaseOrder']['po_date'])); ?>" class="mt-1 block w-full md:w-[300px] border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm form-input">
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
                <th class="p-2 text-left w-3/12">Item Summary</th>
                <th class="p-2 text-left w-1/12">HSN</th>
                <th class="p-2 text-left w-1/12">Image</th>
                <th class="p-2 text-left w-1/12">GST %</th>
                <th class="p-2 text-left w-2/12">Quantity</th>
                <th class="p-2 text-left w-1/12">Unit</th>
                <th class="p-2 text-left w-2/12">Rate</th>
                <th class="p-2 text-left w-1/12">Amount</th>
                <th class="p-2 text-right w-1/12"></th>
            </tr>
            </thead>
            <tbody class="table-row-text">
            <?php foreach($items as $item): ?>
                <tr class="bg-white shadow-sm rounded-lg">
                    <td class="p-2 align-top"><input type="hidden" name="item_ids[]" value="<?php echo $item['id']; ?>"><?php echo $item['id']; ?></td>
                    <td class="p-2 align-top"> <?php echo htmlspecialchars($item['title'] ?? ''); ?></td>
                    <td class="p-2 align-top"><?php echo htmlspecialchars($item['hsn'] ?? ''); ?></td>
                    <td class="p-2 align-top">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image'] ?? ''); ?>" alt="Item Image" class="h-12 w-12 object-cover rounded">
                        <?php else: ?>
                            N/A
                        <?php endif; ?>
                    </td>
                    <td class="p-2 align-top"><input type="text" name="gst[]" value="<?php echo htmlspecialchars($item['gst'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <td class="p-2 align-top"><input type="text" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <td class="p-2 align-top"></td>
                    <td class="p-2 align-top"><input type="text" name="price[]" value="<?php echo htmlspecialchars($item['price'] ?? ''); ?>" class="form-input w-[80px] " />
                    </td>
                    <td class="p-2 align-top"><input type="text" name="amount[]" value="<?php echo htmlspecialchars($item['amount'] ?? ''); ?>" class="form-input w-[80px] " /></td>
                    <td class="p-2 align-top text-right">
                        <button type="button" class="remove-row text-gray-500 hover:text-red-700" title="Remove Item"><span class="text-lg"><i class="fa fa-trash-alt"></i></span></button>
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
        <div class="totals">
            <div class="flex justify-between">
                <span class="font-bold">Subtotal:</span>
                <span class="subtotal"><?php echo number_format(($data['purchaseOrder']['subtotal'] ?? 0), 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="font-bold">Total GST:</span>
                <span class="total-gst"><?php echo number_format(($data['purchaseOrder']['total_gst'] ?? 0), 2); ?></span>
            </div>
            <div class="flex justify-between">
                <span class="font-bold">Shipping Cost:</span>
                <span class="shipping-cost"><input type="text" name="shipping_cost" value="<?php echo number_format(($data['purchaseOrder']['shipping_cost'] ?? 0), 2); ?>" class="form-input w-[80px] " /></span>
            </div>
            <div class="flex justify-between">
                <span class="font-bold">Grand Total:</span>
                <span class="grand-total"><?php echo number_format(($data['purchaseOrder']['total_cost'] ?? 0), 2); ?></span>
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
            <textarea id="notes" name="notes" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important note to remember" style="min-height: 148px;"><?php echo $purchaseOrder['notes']; ?></textarea>
        </div>
        <div>
            <div class="flex justify-between items-center mb-1">
                <label for="terms" class="block text-sm font-medium text-gray-700 notes-label">Terms & Conditions:</label>
                <!-- <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Load Template</button> -->
            </div>
            <textarea id="terms" name="terms_and_conditions" class="mt-5 block w-full border border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm p-2" placeholder="Important terms & conditions to remember" style="min-height: 148px;"><?php echo htmlspecialchars($purchaseOrder['terms_and_conditions'] ?? ''); ?></textarea>
        </div>
    </div>
    <!-- Action Buttons -->
    <div class="mt-8 flex justify-end space-x-4">
        <button type="submit" class="bg-blue-500 text-white font-semibold py-2 px-4 rounded-md">Save Changes</button>
        <a href="<?php echo base_url('?page=purchase_orders&action=list'); ?>" class="bg-gray-300 text-gray-700 font-semibold py-2 px-4 rounded-md">Cancel</a>
    </div>
    </form>
</div>
<script>
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

            const grandTotal = subtotal + totalGst + (parseFloat(shippingCostElement.value) || 0);

            subtotalElement.textContent = `${subtotal.toFixed(2)}`;
            totalGstElement.textContent = `${totalGst.toFixed(2)}`;
            grandTotalElement.textContent = `${grandTotal.toFixed(2)}`;
            document.querySelector('input[name="subtotal"]').value = subtotal.toFixed(2);
            document.querySelector('input[name="total_gst"]').value = totalGst.toFixed(2);
            document.querySelector('input[name="grand_total"]').value = grandTotal.toFixed(2);
            //document.querySelector('input[name="shipping_cost"]').value = (parseFloat(shippingCostElement.textContent.replace('₹', '')) || 0).toFixed(2);
        }

        itemTable.addEventListener('input', updateTotals);
        shippingCostElement.addEventListener('input', updateTotals);
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
    document.getElementById("edit_po").addEventListener("submit", function(event) {
        event.preventDefault(); // Prevent default form submission
        const submitBtn = this.querySelector('button[type="submit"]');
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
                window.location.href = "<?php echo base_url('?page=purchase_orders&acton=list'); ?>"; // Redirect to the list page
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
    });
</script>
