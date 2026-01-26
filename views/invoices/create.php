<div class="bg-white p-4 md:p-8">
    <form action="<?php echo base_url('?page=invoices&action=create_post'); ?>" id="create_invoice" method="post">
    <div class="flex flex-col md:flex-row justify-between mb-8">
        <!-- Left Column -->
        <div class="space-y-2 w-full md:w-auto mt-4 md:mt-0">
            <div class="flex items-center">
                <label for="invoice-date" class="block text-gray-700 form-label">Invoice Date :<span class="text-red-500"> *</span></label>
                <div class="ml-2"><?php echo date('d M Y'); ?></div>
                <input type="hidden" id="invoice_date" name="invoice_date" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]" value="<?php echo date('Y-m-d'); ?>">
            </div>

            
            <!-- Bill To and Ship To Addresses -->
            <?php
            // Helper function to format single-line address
            function formatAddress($addr) {
                $parts = [];
                if (!empty($addr['first_name'])) $parts[] = $addr['first_name'] . ' ' . $addr['last_name'];               
                if (!empty($addr['address_line1'])) $parts[] = $addr['address_line1'];
                if (!empty($addr['address_line2'])) $parts[] = $addr['address_line2'];
                if (!empty($addr['city'])) $parts[] = $addr['city'];
                if (!empty($addr['state'])) $parts[] = $addr['state'];
                if (!empty($addr['zipcode'])) $parts[] = $addr['zipcode'];
                if (!empty($addr['country'])) $parts[] = $addr['country'];
                return implode(', ', $parts);
            }

            // Build Bill To addresses list (unique)
            $billToAddresses = [];
            $shipToAddresses = [];
            $billingState = $customer_address[0]['state'] ?? '';
            $firmState = $firm['state'] ?? '';
            
            // Store firm state for JavaScript
            $firmStateJS = json_encode($firmState);
            if (isset($customer_address) && is_array($customer_address) && count($customer_address) > 0) {
                foreach ($customer_address as $addr) {
                    // Build billing address
                    $billAddr = formatAddress($addr);
                    if (!empty($billAddr) && !in_array($billAddr, $billToAddresses)) {
                        $billToAddresses[] = $billAddr;
                    }
                    
                    // Build shipping address if exists
                    if (!empty($addr['shipping_address_line1'])) {
                        $shipParts = [];
                        if (!empty($addr['shipping_first_name'])) $shipParts[] = $addr['shipping_first_name'] . ' ' . $addr['shipping_last_name'];                        
                        if (!empty($addr['shipping_address_line1'])) $shipParts[] = $addr['shipping_address_line1'];
                        if (!empty($addr['shipping_address_line2'])) $shipParts[] = $addr['shipping_address_line2'];
                        if (!empty($addr['shipping_city'])) $shipParts[] = $addr['shipping_city'];
                        if (!empty($addr['shipping_state'])) $shipParts[] = $addr['shipping_state'];
                        if (!empty($addr['shipping_zipcode'])) $shipParts[] = $addr['shipping_zipcode'];
                        if (!empty($addr['shipping_country'])) $shipParts[] = $addr['shipping_country'];
                        $shipAddr = implode(', ', $shipParts);
                        if (!empty($shipAddr) && !in_array($shipAddr, $shipToAddresses)) {
                            $shipToAddresses[] = $shipAddr;
                        }
                    }else {
                        // No shipping address, use billing address
                        if (!in_array($billAddr, $shipToAddresses)) {
                            $shipToAddresses[] = $billAddr;
                        }
                    }
                }
            }
            
            $defaultBillTo = !empty($billToAddresses) ? $billToAddresses[0] : '';
            $defaultShipTo = !empty($shipToAddresses) ? $shipToAddresses[0] : '';
            $showGSTContainer = isset($customer_address[0]['country']) && strtolower($customer_address[0]['country']) !== 'in';
            ?>
            
            <div class="space-y-3 text-sm">
                <div>
                    <label class="block text-gray-700 form-label font-semibold">Bill To : <span class="text-red-500"> *</span> <?php echo count($billToAddresses) > 0 ? '<span class="text-blue-600 cursor-pointer hover:underline" onclick="openAddressSelector()"> Change Addresses</span>' : ''; ?></label>
                    
                    <input type="hidden" name="customer_address" id="billToSelect" value="<?= htmlspecialchars($defaultBillTo) ?>">
                    <input type="hidden" name="vp_order_info_id" id="vp_order_info_id" value="<?= $customer_address[0]['id'] ?>">
                    <p class=" text-gray-700 mt-1"><?= htmlspecialchars($defaultBillTo) ?></p>
                    
                    <input type="hidden" id="billToDisplay" value="<?= htmlspecialchars($defaultBillTo) ?>">
                </div>
                <div id="supplystate">Supply State : <?= $billingState ?></div>
                <?php if ($showGSTContainer): ?>
                <div id="applyGSTContainer">Apply GST <input type="checkbox" id="applyGST" name="applyGST" value="1" checked></div>
                <?php endif; ?>
            </div>
            
            <!-- Address Selector Modal -->
            <div id="addressSelectorModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50" onclick="closeAddressSelector()">
                <div class="bg-white max-w-2xl w-full max-h-[80vh] overflow-y-auto rounded-lg" onclick="event.stopPropagation()">
                    <div class="sticky top-0 bg-gray-100 p-4 border-b flex justify-between items-center">
                        <h2 class="text-xl font-bold">Select Delivery Address</h2>
                        <button type="button" onclick="closeAddressSelector()" class="text-red-600 hover:text-red-800 text-2xl">&times;</button>
                    </div>
                    <div class="p-4">
                        <table class="w-full border-collapse">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="border p-3 text-left w-1/12">Select</th>
                                    <th class="border p-3 text-left w-1/2">Bill To Address</th>
                                    <th class="border p-3 text-left w-1/2">Ship To Address</th>
                                </tr>
                            </thead>
                            <tbody id="addressTableBody">
                                <!-- Populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                    <div class="sticky bottom-0 bg-gray-100 p-4 border-t flex justify-end space-x-2">
                        <button type="button" onclick="closeAddressSelector()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Close</button>
                        <button type="button" onclick="applyAddressSelection()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="space-y-2 w-full md:w-auto">
            <div class="flex items-center">
                <label for="customer-name" class="block text-gray-700 form-label">Customer Name: <span class="text-red-500"> *</span></label>
                <div class="ml-2">
                    <?php if (isset($customer) && is_array($customer)): ?>
                        <span class="font-semibold"><?php echo htmlspecialchars($customer['name']); ?></span>
                        <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                    <?php else: ?>
                        <span class="text-red-500">Customer not found</span>
                    <?php endif; ?>
                    
                </div>
                <div><?php echo $customer_address[0]['gstin'] ?? "Customer GST : ".$customer_address[0]['gstin']; ?></div>
            </div>

            <!-- <div class="flex items-center">
                <label for="currency" class="block text-gray-700 form-label">Currency : <span class="text-red-500"> *</span></label>
                <select name="currency" id="currency" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]">
                    <option value="INR" selected>INR</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                </select>
            </div>

            <div class="flex items-center">
                <label for="status" class="block text-gray-700 form-label">Status : <span class="text-red-500"> *</span></label>
                <select name="status" id="status" class="mt-1 focus:ring-indigo-500 focus:border-indigo-500 block shadow-sm sm:text-sm border-gray-300 rounded-md form-input px-3 w-full md:w-[150px]">
                    <option value="draft" selected>Draft</option>
                    <option value="final">Final</option>                  
                </select>
            </div> -->

            
            <?php if (count($shipToAddresses) > 0): ?>
            <div>
                <label class="block text-gray-700 form-label font-semibold">Ship To :</label>                
                <p class="text-sm text-gray-700 mt-1" id="shipToDisplay"><?= htmlspecialchars($defaultShipTo) ?></p>
                
                <input type="hidden" id="shipToDisplay" value="<?= htmlspecialchars($defaultShipTo) ?>">
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Item Table -->
    <div class="bg-[rgba(245,245,245,1)] p-4 rounded-lg overflow-x-auto">
        <table class="w-full border-separate" id="invoiceTable" style="border-spacing: 0 5px;">
            <thead class="table-header">
            <tr>
                <th class="p-2 text-left w-0.5/12">S.No</th>
                <th class="p-2 text-left w-0.5/12">Box No</th>
                <th class="p-2 text-left w-1/12">SKU</th>
                <th class="p-2 text-left w-3/12" colspan="2">Item Name</th>
                <th class="p-2 text-left w-0.5/12">HSN </th>
                <th class="p-2 text-left w-0.5/12">Qty</th>
                <th class="p-2 text-left w-1/12">Unit Price</th>
                <th class="p-2 text-left w-0.5/12">Discount</th>
                <th class="p-2 text-left w-1/12">CGST %</th>
                <th class="p-2 text-left w-1/12">SGST %</th>
                <th class="p-2 text-left w-1/12">IGST %</th>
                <th class="p-2 text-left w-1/12">Amount</th>
                <th class="p-2 text-right w-0.5/12"></th>
            </tr>
            </thead>
            <tbody class="table-row-text">
                <?php
                //print_r($data);
                if(isset($data) && is_array($data)) { 
                    foreach ($data as $index => $item): ?>
            <tr class="bg-white">
                <input type="hidden" name="order_number[]" value="<?= $item['order_number'] ?>">
                <input type="hidden" name="item_code[]" value="<?= $item['item_code'] ?>">
                <input type="hidden" name="gst[]" value="<?= $item['gst'] ?>">
                <input type="hidden" name="tax_rate[]" value="<?= $item['gst'] ?>">
                <td class="p-2 rounded-l-lg"><?php echo $index + 1; ?></td>
                <td class="p-2">
                    <input type="text" name="box_no[]" class="w-full border rounded-md form-input p-2" value="1" required>
                </td>
                <td class="p-2"><span><?= $item['sku'] ?></span></td>
                <td class="p-2 " colspan="2"><span><?= htmlspecialchars($item['title'] ?? '') ?></span>
                    <input type="hidden" name="item_name[]" value="<?= htmlspecialchars($item['title'] ?? '') ?>" required>
                </td>
                <td class="p-2"><span><?= $item['hsn'] ?></span>
                    <input type="hidden" name="hsn[]" value="<?= $item['hsn'] ?>" > 
                </td>
                <td class="p-2"><span><?= $item['quantity'] ?? 1 ?></span>
                    <input type="hidden" name="quantity[]"  value="<?= $item['quantity'] ?? 1 ?>">
                </td>
                <td class="p-2"><span><?= $item['unit_price'] ? "₹".$item['unit_price'] : '0.00' ?></span>
                    <input type="hidden" name="unit_price[]"  value="<?= $item['unit_price'] ?? 0 ?>" >
                </td>
                <td class="p-2"><span>0%</span>
                    <input type="hidden" name="discount[]"  value="0" >
                </td>
                <td class="p-2"><span>0%</span>
                    <input type="hidden" name="cgst[]"  value="0" >
                </td>
                <td class="p-2"><span>0%</span>
                    <input type="hidden" name="sgst[]"  value="0" >
                </td>
                <td class="p-2"><span>0%</span>
                    <input type="hidden" name="igst[]"  value="0" >
                </td>
                <td class="p-2"><span><?= $item['unit_price'] ? "₹".$item['unit_price'] : '0.00' ?></span>
                    <input type="hidden" name="line_total[]" step="0.01" >
                </td>
                <td class="p-2 rounded-r-lg text-center">
                    <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
                <?php endforeach; } ?>
            </tbody>
        </table>
    </div>

    <!-- Totals Section -->
    <div class="mt-6 flex justify-end">
        <!--Show total of sgst cgst igst -->
        <div class="flex-grow" id="taxTotalsDisplay">
            <!-- Populated by JavaScript if needed -->
           
        </div>
         <!-- Add Item Button -->
        <div class="flex-grow">
            <button type="button" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">Add Item</button>
        </div>
        <div class="w-full md:w-1/3 space-y-4">
            <div class="flex justify-between border-t pt-4">
                <span class="font-semibold">Subtotal:</span>
                <input type="number" name="subtotal" id="subtotal" step="0.01" class="w-32 text-right border rounded-md form-input" readonly>
            </div>
            <div class="flex justify-between">
                <span class="font-semibold">Tax Amount:</span>
                <input type="number" name="tax_amount" id="tax_amount" step="0.01" class="w-32 text-right border rounded-md form-input" readonly>
            </div>
            <div class="flex justify-between">
                <span class="font-semibold">Discount:</span>
                <input type="number" name="discount_amount" id="discount_amount" step="0.01" class="w-32 text-right border rounded-md form-input" value="0" oninput="calculateTotals()" readonly>
            </div>
            <div class="flex justify-between border-t pt-4 text-lg font-bold">
                <span>Total Amount:</span>
                <input type="number" name="total_amount" id="total_amount" step="0.01" class="w-32 text-right border-2 border-indigo-500 rounded-md form-input" readonly>
            </div>
        </div>
    </div>

    <!-- Form Actions -->
    <div class="mt-8 flex justify-end space-x-4">
        <a href="<?php echo base_url('?page=orders&action=list'); ?>" class="px-6 py-2 bg-gray-300 text-gray-800 rounded-md hover:bg-gray-400">Cancel</a>
        <button type="button" onclick="previewInvoice()" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Preview</button>
        <button type="submit" class="px-6 py-2 bg-orange-500 text-white rounded-md hover:bg-orange-600">Create Invoice</button>
    </div>
    </form>
</div>

<!-- Invoice Preview Modal -->
<div id="invoicePreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50" onclick="closePreviewModal()">
    <div class="bg-white max-w-4xl w-full max-h-[90vh] overflow-y-auto rounded-lg" onclick="event.stopPropagation()">
        <div class="sticky top-0 bg-gray-100 p-4 border-b flex justify-between items-center">
            <h2 class="text-xl font-bold">Invoice Preview</h2>
            <button type="button" onclick="closePreviewModal()" class="text-red-600 hover:text-red-800 text-2xl">&times;</button>
        </div>
        <div id="invoicePreviewContent" class="p-4"></div>
        <div class="sticky bottom-0 bg-gray-100 p-4 border-t flex justify-end space-x-2">
            <button type="button" onclick="closePreviewModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Close</button>
            <button type="button" onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Print</button>
        </div>
    </div>
</div>
<!-- Order Item Modal -->
<div id="orderModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center z-50" style="display:none;">
    <div class="bg-white rounded-lg shadow-lg w-full max-w-3xl p-6 relative">
        <button type="button" class="absolute top-2 right-3 text-2xl font-bold text-gray-500 hover:text-black" id="closeOrderModal">&times;</button>
        <h2 class="text-xl font-bold mb-4">Select Order Item</h2>
        <input type="text" id="orderSearch" class="border p-2 w-full mb-4" placeholder="Search with order id, item code, or title...">
        <div class="max-h-72 overflow-y-auto">
            <table class="w-full border">
                <thead>
                    <tr>
                        <!-- <th class="p-2 text-left"> </th> -->
                        <th class="p-2 text-left">Order ID</th>
                        <th class="p-2 text-left">SKU</th>
                        <th class="p-2 text-left">Title</th>
                        <th class="p-2 text-left">Price</th>
                        <th class="p-2 text-left">Action</th>
                    </tr>
                </thead>
                <tbody id="orderItemsTableBody">
                    <!-- Dynamic rows here -->
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
// Store firm state for GST calculation
const firmState = <?php echo $firmStateJS; ?>;

// Store address data for modal
const addressData = <?php echo json_encode(array_map(function($addr) {
    $billParts = [];
    if (!empty($addr['first_name'])) $billParts[] = $addr['first_name'] . ' ' . $addr['last_name'];               
    if (!empty($addr['address_line1'])) $billParts[] = $addr['address_line1'];
    if (!empty($addr['address_line2'])) $billParts[] = $addr['address_line2'];
    if (!empty($addr['city'])) $billParts[] = $addr['city'];
    if (!empty($addr['state'])) $billParts[] = $addr['state'];
    if (!empty($addr['zipcode'])) $billParts[] = $addr['zipcode'];
    if (!empty($addr['country'])) $billParts[] = $addr['country'];
    $billAddr = implode(', ', $billParts);
    
    $shipParts = [];
    if (!empty($addr['shipping_address_line1'])) {
        if (!empty($addr['shipping_first_name'])) $shipParts[] = $addr['shipping_first_name'] . ' ' . $addr['shipping_last_name'];                        
        if (!empty($addr['shipping_address_line1'])) $shipParts[] = $addr['shipping_address_line1'];
        if (!empty($addr['shipping_address_line2'])) $shipParts[] = $addr['shipping_address_line2'];
        if (!empty($addr['shipping_city'])) $shipParts[] = $addr['shipping_city'];
        if (!empty($addr['shipping_state'])) $shipParts[] = $addr['shipping_state'];
        if (!empty($addr['shipping_zipcode'])) $shipParts[] = $addr['shipping_zipcode'];
        if (!empty($addr['shipping_country'])) $shipParts[] = $addr['shipping_country'];
    }
    $shipAddr = implode(', ', $shipParts);
    
    return [
        'id' => $addr['id'],
        'order_number' => $addr['order_number'],
        'bill_to' => $billAddr,
        'ship_to' => $shipAddr,
        'state' => $addr['state'] ?? ''
    ];
}, $customer_address)) ?>;

// Function to determine tax type based on states
function calculateGSTType(billingState) {
    if (!billingState || !firmState) return null;
    
    // Same state = CGST + SGST, Different state = IGST
    return (billingState.trim().toUpperCase() === firmState.trim().toUpperCase()) ? 'same' : 'different';
}

function openAddressSelector() {
    const modal = document.getElementById('addressSelectorModal');
    const tableBody = document.getElementById('addressTableBody');
    const currentAddressId = document.getElementById('vp_order_info_id').value;
    
    tableBody.innerHTML = '';
    
    addressData.forEach((addr, idx) => {
        const row = document.createElement('tr');
        row.className = 'border hover:bg-gray-50';
        row.innerHTML = `
            <td class="border p-3 text-center">
                <input type="radio" name="addressRadio" value="${addr.id}" data-bill-to="${addr.bill_to}" data-ship-to="${addr.ship_to}" ${addr.id == currentAddressId ? 'checked' : ''}>
                ${addr.order_number ? `${addr.order_number}` : ''}
            </td>
            <td class="border p-3 text-sm">${addr.bill_to}</td>
            <td class="border p-3 text-sm">${addr.ship_to || '<span class="text-gray-400">No shipping address</span>'}</td>
        `;
        tableBody.appendChild(row);
    });
    
    modal.classList.remove('hidden');
}

function closeAddressSelector() {
    document.getElementById('addressSelectorModal').classList.add('hidden');
}

function applyAddressSelection() {
    const selectedRadio = document.querySelector('input[name="addressRadio"]:checked');
    
    if (!selectedRadio) {
        alert('Please select an address');
        return;
    }
    
    const addressId = selectedRadio.value;
    const billTo = selectedRadio.getAttribute('data-bill-to');
    const shipTo = selectedRadio.getAttribute('data-ship-to');
    
    // Update form fields
    document.getElementById('vp_order_info_id').value = addressId;
    document.getElementById('billToSelect').value = billTo;
    document.getElementById('billToDisplay').value = billTo;
    
    // Update Bill To display text
    const billToDisplayElements = document.querySelectorAll('.space-y-3 p.text-sm');
    if (billToDisplayElements.length > 0) {
        billToDisplayElements[0].textContent = billTo;
    }
    
    // Update Ship To display if it exists
    const shipToDisplay = Array.from(document.querySelectorAll('#shipToDisplay')).find(el => el.tagName === 'P');
    if (shipToDisplay) {
        shipToDisplay.textContent = shipTo || '';
    }
    
    // Auto-populate GST fields based on state comparison
    const selectedAddress = addressData.find(a => a.id == addressId);
    if (selectedAddress) {
        const gstType = calculateGSTType(selectedAddress.state);
        updateGSTFields(gstType);
    }
    
    closeAddressSelector();
}


function previewInvoice() {
    const formData = new FormData(document.getElementById('create_invoice'));
    
    // Collect item data
    const items = [];
    document.querySelectorAll('#invoiceTable tbody tr').forEach((row, idx) => {
        items.push({
            order_number: row.querySelector('input[name="order_number[]"]')?.value || '',
            box_no: row.querySelector('input[name="box_no[]"]')?.value || '',
            item_code: row.querySelector('input[name="item_code[]"]')?.value || '',
            item_name: row.querySelector('input[name="item_name[]"]')?.value || '',
            hsn: row.querySelector('input[name="hsn[]"]')?.value || '',
            quantity: row.querySelector('input[name="quantity[]"]')?.value || 0,
            unit_price: row.querySelector('input[name="unit_price[]"]')?.value || 0,
            cgst: row.querySelector('input[name="cgst[]"]')?.value || 0,
            sgst: row.querySelector('input[name="sgst[]"]')?.value || 0,
            igst: row.querySelector('input[name="igst[]"]')?.value || 0,
            tax_amount: row.querySelector('input[name="tax_amount[]"]')?.value || 0,
            line_total: row.querySelector('input[name="line_total[]"]')?.value || 0
        });
    });
    
    if (items.length === 0) {
        alert('Please add at least one item to preview');
        return;
    }
    
    // Get selected address
    const vp_order_info_id = document.getElementById('vp_order_info_id').value;
    //const vpAddressInfoId = billToSelect && billToSelect.tagName === 'SELECT' ? billToSelect.value : '';
    
    const previewData = {
        invoice_date: formData.get('invoice_date') || new Date().toISOString().split('T')[0],
        customer_id: formData.get('customer_id') || 0,
        vp_order_info_id: vp_order_info_id || 0,
        currency: formData.get('currency') || 'INR',
        subtotal: document.getElementById('subtotal')?.value || 0,
        tax_amount: document.getElementById('tax_amount')?.value || 0,
        discount_amount: document.getElementById('discount_amount')?.value || 0,
        total_amount: document.getElementById('total_amount')?.value || 0,
        status: formData.get('status') || 'draft',
        items: items
    };
    
    // Send to server for preview using template
    fetch('<?php echo base_url('?page=invoices&action=preview'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(previewData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Display the HTML preview in modal
            const modal = document.getElementById('invoicePreviewModal');
            const previewContent = document.getElementById('invoicePreviewContent');
            
            // Set the HTML content from the tax invoice template
            previewContent.innerHTML = `<div style="max-height: 500px; overflow-y: auto; background: white;">${data.html}</div>`;
            
            modal.classList.remove('hidden');
        } else {
            alert('Error generating preview: ' + data.message);
        }
    })
    .catch(err => {
        console.error('Preview error:', err);
        alert('Failed to generate preview');
    });
}


function closePreviewModal() {
    document.getElementById('invoicePreviewModal').classList.add('hidden');
}

function removeRow(btn) {
    btn.closest('tr').remove();
    calculateTotals();
}

function updateGSTFields(gstType) {
    const rows = document.querySelectorAll('#invoiceTable tbody tr');
    
    rows.forEach(row => {
        const gstValue = parseFloat(row.querySelector('input[name="gst[]"]')?.value) || 0;
        const cgstInput = row.querySelector('input[name="cgst[]"]');
        const sgstInput = row.querySelector('input[name="sgst[]"]');
        const igstInput = row.querySelector('input[name="igst[]"]');
        console.log('Updating GST for row:', row, 'GST Type:', gstType, 'GST Value:', gstValue);
        if (gstType === 'same') {
            // Same state: Split GST between CGST and SGST (50% each)
            const halfGst = gstValue / 2;
            if (cgstInput) cgstInput.value = halfGst.toFixed(2);
            if (sgstInput) sgstInput.value = halfGst.toFixed(2);
            if (igstInput) igstInput.value = '0';
        } else if (gstType === 'different') {
            // Different state: All GST goes to IGST
            if (cgstInput) cgstInput.value = '0';
            if (sgstInput) sgstInput.value = '0';
            if (igstInput) igstInput.value = gstValue.toFixed(2);
        }
        
        // Update display spans
        const cgstSpan = row.querySelector('input[name="cgst[]"]')?.previousElementSibling;
        const sgstSpan = row.querySelector('input[name="sgst[]"]')?.previousElementSibling;
        const igstSpan = row.querySelector('input[name="igst[]"]')?.previousElementSibling;
        
        if (cgstSpan) cgstSpan.textContent = (cgstInput?.value || '0') + '%';
        if (sgstSpan) sgstSpan.textContent = (sgstInput?.value || '0') + '%';
        if (igstSpan) igstSpan.textContent = (igstInput?.value || '0') + '%';
    });
    
    calculateTotals();
}

function calculateTotals() {
    const rows = document.querySelectorAll('#invoiceTable tbody tr');
    let subtotal = 0;
    let totalTax = 0;
    let totalsgst = 0;
    let totalcgst = 0;
    let totaligst = 0;

    rows.forEach(row => {
        const qty = parseFloat(row.querySelector('input[name="quantity[]"]')?.value) || 0;
        const unitPrice = parseFloat(row.querySelector('input[name="unit_price[]"]')?.value) || 0;
        const cgst = parseFloat(row.querySelector('input[name="cgst[]"]')?.value) || 0;
        const sgst = parseFloat(row.querySelector('input[name="sgst[]"]')?.value) || 0;
        const igst = parseFloat(row.querySelector('input[name="igst[]"]')?.value) || 0;

        const lineTotal = qty * unitPrice;
        const lineTax = (lineTotal * (cgst + sgst + igst)) / 100;

        const lineTotalInput = row.querySelector('input[name="line_total[]"]');
        if (lineTotalInput) {
            lineTotalInput.value = lineTotal.toFixed(2);
        }
        
        // Update display span if exists
        // const displaySpan = row.querySelector('td:nth-child(10) span');
        // if (displaySpan) {
        //     displaySpan.textContent = '₹' + lineTotal.toFixed(2);
        // }

        subtotal += lineTotal;
        totalTax += lineTax;
        totalsgst += (lineTotal * sgst) / 100;
        totalcgst += (lineTotal * cgst) / 100;
        totaligst += (lineTotal * igst) / 100;
    });

    const discount = parseFloat(document.getElementById('discount_amount').value) || 0;
    const totalAmount = subtotal + totalTax - discount;

    document.getElementById('subtotal').value = subtotal.toFixed(2);
    document.getElementById('tax_amount').value = totalTax.toFixed(2);
    document.getElementById('total_amount').value = totalAmount.toFixed(2);
    // Update tax totals display
    const taxTotalsDisplay = document.getElementById('taxTotalsDisplay');
    taxTotalsDisplay.innerHTML = `
        <div class="mb-2">
            <span class="font-semibold">CGST Total:</span> ₹${totalcgst.toFixed(2)}
        </div>
        <div class="mb-2">
            <span class="font-semibold">SGST Total:</span> ₹${totalsgst.toFixed(2)}
        </div>
        <div class="mb-2">
            <span class="font-semibold">IGST Total:</span> ₹${totaligst.toFixed(2)}
        </div>
    `;
}

// Initialize calculation on page load
document.addEventListener('DOMContentLoaded', function() {
    // Set initial GST based on default billing state
    const gstType = calculateGSTType('<?php echo $billingState; ?>');
    updateGSTFields(gstType);
});

// Form submission
document.getElementById('create_invoice').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('<?php echo base_url('?page=invoices&action=create_post'); ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // if (window.showGlobalToast) {
            //     window.showGlobalToast('Invoice created successfully!', 'success');
            // } else {
            //     alert('Invoice created successfully!');
            // }
            localStorage.removeItem('selected_po_orders');
            showAlert('Invoice created successfully!', 'success');
            // Generate PDF after a short delay
            setTimeout(() => {
                fetch('<?php echo base_url('?page=invoices&action=generate_pdf'); ?>', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({invoice_id: data.invoice_id})
                })
                .then(response => response.blob())
                .then(blob => {
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = 'invoice_' + data.invoice_id + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    
                    // Redirect to invoice view
                    setTimeout(() => {
                        window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';
                    }, 1000);
                })
                .catch(err => {
                    console.error('PDF generation error:', err);
                    window.location.href = '<?php echo base_url('?page=orders&action=list'); ?>';
                });
            }, 1000);
        } else {
            if (window.showGlobalToast) {
                window.showGlobalToast('Error: ' + data.message, 'error');
            } else {
                alert('Error: ' + data.message);
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        if (window.showGlobalToast) {
            window.showGlobalToast('Network error occurred', 'error');
        } else {
            alert('Network error occurred');
        }
    });
});

// Add event listener for applyGST checkbox
document.addEventListener('DOMContentLoaded', function() {
    // Set initial GST based on default billing state
    const gstType = calculateGSTType('<?php echo $billingState; ?>');
    updateGSTFields(gstType);
    
    // Add listener for GST checkbox
    const applyGSTCheckbox = document.getElementById('applyGST');
    if (applyGSTCheckbox) {
        applyGSTCheckbox.addEventListener('change', function() {
            if (this.checked) {
                const gstType = calculateGSTType('<?php echo $billingState; ?>');
                updateGSTFields(gstType);
            } else {
                // Clear all GST values
                clearGSTFields();
            }
        });
    }
});

// New function to clear GST fields
function clearGSTFields() {
    const rows = document.querySelectorAll('#invoiceTable tbody tr');
    
    rows.forEach(row => {
        const cgstInput = row.querySelector('input[name="cgst[]"]');
        const sgstInput = row.querySelector('input[name="sgst[]"]');
        const igstInput = row.querySelector('input[name="igst[]"]');
        
        if (cgstInput) cgstInput.value = '0';
        if (sgstInput) sgstInput.value = '0';
        if (igstInput) igstInput.value = '0';
        
        // Update display spans
        const cgstSpan = row.querySelector('input[name="cgst[]"]')?.previousElementSibling;
        const sgstSpan = row.querySelector('input[name="sgst[]"]')?.previousElementSibling;
        const igstSpan = row.querySelector('input[name="igst[]"]')?.previousElementSibling;
        
        if (cgstSpan) cgstSpan.textContent = '0%';
        if (sgstSpan) sgstSpan.textContent = '0%';
        if (igstSpan) igstSpan.textContent = '0%';
    });
    
    calculateTotals();
}


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
function fetchOrderItems(searchTerm) {
    fetch('<?php echo base_url('?page=invoices&action=fetch_items'); ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({search: searchTerm, customer_id: <?php echo isset($customer['id']) ? (int)$customer['id'] : 0; ?>})
    })
    .then(response => response.json())
    .then(data => {
        const tbody = document.getElementById('orderItemsTableBody');
        tbody.innerHTML = '';
        
        if (data.items && data.items.length > 0) {
            data.items.forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `                    
                    <td class="border p-2" data-item='${JSON.stringify(item)}'>${item.order_number || ''}</td>
                    <td class="border p-2">${item.sku || ''}</td>
                    <td class="border p-2">${item.title || ''}</td>
                    <td class="border p-2 text-right">${item.unit_price ? "₹"+item.unit_price : '0.00'}</td>
                    <td class="border p-2 text-center">
                        <button type="button" class="px-3 py-1 bg-green-500 text-white rounded hover:bg-green-600 select-item-button" id="selectItemBtn">Select</button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        } else {
            const row = document.createElement('tr');
            row.innerHTML = `<td class="border p-2 text-center" colspan="5">No items found</td>`;
            tbody.appendChild(row);
        }
    })
    .catch(err => {
        console.error('Error fetching order items:', err);
    });
}
// Handle item selection
document.getElementById('orderItemsTableBody').addEventListener('click', function(e) {
    if (e.target.classList.contains('select-item-button')) {
        const itemData = JSON.parse(e.target.closest('tr').querySelector('td').getAttribute('data-item'));
        
        // Add item to invoice table
        const tbody = document.querySelector('#invoiceTable tbody');
        const newRow = document.createElement('tr');
        newRow.className = 'bg-white';
        newRow.innerHTML = `
            <input type="hidden" name="order_number[]" value="${itemData.order_number || ''}">
            <input type="hidden" name="item_code[]" value="${itemData.item_code || ''}">
            <input type="hidden" name="gst[]" value="${itemData.gst || '0'}">
            <input type="hidden" name="tax_rate[]" value="${itemData.gst || '0'}">
            <td class="p-2 rounded-l-lg">${tbody.children.length + 1}</td>
            <td class="p-2">
                <input type="text" name="box_no[]" class="w-full border rounded-md form-input p-2" value="1" required>
            </td>
            <td class="p-2">${itemData.sku || ''}</td>
            <td class="p-2 " colspan="2">${itemData.title ? htmlspecialchars(itemData.title) : ''}
                <input type="hidden" name="item_name[]" value="${itemData.title ? htmlspecialchars(itemData.title) : ''}" required>
            </td>
            <td class="p-2">${itemData.hsn || ''}
                <input type="hidden" name="hsn[]" value="${itemData.hsn || ''}" > 
            </td>
            <td class="p-2">1
                <input type="hidden" name="quantity[]"  value="1">
            </td>
            <td class="p-2">${itemData.unit_price ? "₹"+itemData.unit_price : '0.00'}
                <input type="hidden" name="unit_price[]"  value="${itemData.unit_price || 0}" >
            </td>
            <td class="p-2">0%
                <input type="hidden" name="discount[]"  value="0" >
            </td>
            <td class="p-2">0%
                <input type="hidden" name="cgst[]"  value="0" >
            </td>   
            <td class="p-2">0%
                <input type="hidden" name="sgst[]"  value="0" >
            </td>
            <td class="p-2">0%
                <input type="hidden" name="igst[]"  value="0" >
            </td>
            <td class="p-2">${itemData.unit_price ? "₹"+itemData.unit_price : '0.00'}
                <input type="hidden" name="line_total[]" step="0.01" >
            </td>
            <td class="p-2 rounded-r-lg text-center">
                <button type="button" onclick="removeRow(this)" class="text-red-500 hover:text-red-700">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        // Update GST fields based on current billing state
        const gstType = calculateGSTType('<?php echo $billingState; ?>');
        updateGSTFields(gstType);
        
        calculateTotals();
        // Close modal
        document.getElementById('orderModal').style.display = 'none';
    }
});
</script>
