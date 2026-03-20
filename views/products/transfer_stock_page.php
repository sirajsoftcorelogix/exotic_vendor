<div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8 pb-4 border-b border-gray-200">
        <div class="text-2xl text-gray-500">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900"><?php echo isset($transfer) ? 'Edit Transfer Order' : 'New Transfer Order'; ?></h1>
    </div>

    <!-- Main Form -->
    <form id="transferStockForm" class="space-y-6" method="POST" action="?page=products&action=process_transfer_stock">
        <!-- Header Info Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Transfer Order No.</label>
                    <input type="text" name="transfer_order_no" readonly value="<?php echo htmlspecialchars($transfer['transfer_order_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                    <?php if (!empty($transfer['id'])): ?>
                        <input type="hidden" name="transfer_id" value="<?php echo (int)$transfer['id']; ?>">
                    <?php endif; ?>
                    <noscript><input type="hidden" name="noscript_fallback" value="1"></noscript>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch Date <span class="text-red-500">*</span></label>
                    <input type="date" id="dispatch_date" name="dispatch_date" value="<?php echo htmlspecialchars($transfer['dispatch_date'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Est Delivery Date <span class="text-red-500">*</span></label>
                    <input type="date" id="est_delivery_date" name="est_delivery_date" value="<?php echo htmlspecialchars($transfer['est_delivery_date'] ?? ''); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Requested By <span class="text-red-500">*</span></label>
                    <select id="requested_by" name="requested_by" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo (!empty($transfer['requested_by']) && $transfer['requested_by'] == $user['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch By <span class="text-red-500">*</span></label>
                    <select id="dispatch_by" name="dispatch_by" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo (!empty($transfer['dispatch_by']) && $transfer['dispatch_by'] == $user['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Warehouse Selection -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid gap-6 lg:grid-cols-3 mb-6 items-start">
                <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-700 mb-2">Source Warehouse <span class="text-red-500">*</span></label>
                        <select id="from_warehouse" name="from_warehouse" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>" <?php echo (!empty($transfer['from_warehouse']) && $transfer['from_warehouse'] == $warehouse['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 leading-relaxed" id="source_address">Select a warehouse to see address</div>
                </div>
                
                <div class="flex items-center justify-center pt-6">
                    <button type="button" aria-label="Transfer direction" class="bg-gray-800 text-white w-10 h-10 rounded-full flex items-center justify-center hover:bg-orange-600 transition">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                    <div class="flex flex-col">
                        <label class="text-sm font-semibold text-gray-700 mb-2">Destination Warehouse <span class="text-red-500">*</span></label>
                        <select id="to_warehouse" name="to_warehouse" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>" <?php echo (!empty($transfer['to_warehouse']) && $transfer['to_warehouse'] == $warehouse['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="text-xs text-gray-500 mt-2 leading-relaxed" id="dest_address">Select a warehouse to see address</div>
                </div>
            </div>
        </div>

        <!-- Selected Items Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Selected Items</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">Item Code</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">SKU</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">Summary</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b border-gray-200">Image</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">Quantity</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 border-b border-gray-200">Note</th>
                            <th class="px-4 py-3 text-center font-semibold text-gray-700 border-b border-gray-200 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <?php foreach ($products as $index => $product): ?>
                            <tr class="item-row" data-product-id="<?php echo $product['id']; ?>">
                                <td class="px-4 py-3 border-b border-gray-200">
                                    <input type="text" value="<?php echo htmlspecialchars($product['item_code'] ?? ''); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                                    <input type="hidden" name="item_code[]" value="<?php echo htmlspecialchars($product['item_code'] ?? ''); ?>">
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200">
                                    <input type="text" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                                    <input type="hidden" name="sku[]" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200">
                                    <div class="bg-gray-100 p-3 rounded-md text-sm leading-relaxed">
                                        <?php echo htmlspecialchars($product['title'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 text-center">
                                    <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" alt="Item" class="w-14 h-20 object-contain border border-gray-200 rounded bg-white" onerror="this.src='https://via.placeholder.com/60x80?text=No+Image'">
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200">
                                    <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Available</div>
                                    <div class="bg-gray-100 px-2 py-1 rounded text-xs text-gray-700 mb-2"><?php echo htmlspecialchars($product['local_stock'] ?? 0); ?></div>
                                    <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Transfer</div>
                                    <input type="number" name="transfer_qty[]" min="0" max="<?php echo htmlspecialchars($product['local_stock'] ?? 0); ?>" value="<?php echo htmlspecialchars($product['transfer_qty'] ?? 0); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200">
                                    <textarea name="item_notes[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-y min-h-[60px]"><?php echo htmlspecialchars($product['item_notes'] ?? ''); ?></textarea>
                                </td>
                                <td class="px-4 py-3 border-b border-gray-200 text-center">
                                    <button type="button" class="text-gray-400 hover:text-red-500 transition delete-item-btn" aria-label="Delete item">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Transportation Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Transportation Details</div>
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Booking No</label>
                    <input type="text" name="booking_no" value="<?php echo htmlspecialchars($transfer['booking_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Vehicle No</label>
                    <input type="text" name="vehicle_no" value="<?php echo htmlspecialchars($transfer['vehicle_no'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Vehicle Type</label>
                    <input type="text" name="vehicle_type" value="<?php echo htmlspecialchars($transfer['vehicle_type'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Pickup Date Time</label>
                    <input type="datetime-local" name="pickup_datetime" value="<?php echo htmlspecialchars($transfer['pickup_datetime'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
            </div>
            
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">E-Way Bill File</label>
                    <div class="flex gap-2">
                        <input type="text" name="eway_bill_file" placeholder="E-Way Bill File" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <button type="button" class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition">
                            <i class="fas fa-folder-open"></i> Browse
                        </button>
                    </div>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Driver Name</label>
                    <input type="text" name="driver_name" value="<?php echo htmlspecialchars($transfer['driver_name'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Driver Mobile</label>
                    <input type="tel" name="driver_mobile" value="<?php echo htmlspecialchars($transfer['driver_mobile'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Tracking Link</label>
                    <input type="url" name="tracking_link" value="<?php echo htmlspecialchars($transfer['tracking_link'] ?? ''); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                </div>
            </div>
        </div>

        <!-- Footer Options -->
<div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex flex-col gap-6">
                    <div class="flex flex-wrap gap-6">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="pickup_list" name="create_pickup_list" <?php echo (!empty($transfer['create_pickup_list']) ? 'checked' : ''); ?> class="h-4 w-4 text-orange-500 border-gray-300 rounded">
                            Create Pickup List
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="picking_slip" name="create_picking_slip" <?php echo (!empty($transfer['create_picking_slip']) ? 'checked' : ''); ?> class="h-4 w-4 text-orange-500 border-gray-300 rounded">
                            Create Picking Slip
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" id="delivery_challan" name="create_delivery_challan" <?php echo (!empty($transfer['create_delivery_challan']) ? 'checked' : ''); ?> class="h-4 w-4 text-orange-500 border-gray-300 rounded">
                            Create Delivery Challan
                        </label>
                    </div>

                    <div class="flex flex-wrap justify-end gap-4">
                        <button type="button" id="saveDraftBtn" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-gray-200 text-gray-800 hover:bg-gray-300 transition">
                            <i class="fas fa-save"></i> Save as Draft
                        </button>
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden field -->
        <input type="hidden" name="product_ids" value="<?php echo htmlspecialchars($product_ids); ?>">
    </form>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<script>
    // Warehouse address mapping and data
    const warehouseData = {
        <?php foreach ($warehouses as $warehouse): ?>
            <?php echo $warehouse['id']; ?>: {
                name: '<?php echo htmlspecialchars($warehouse['address_title']); ?>',
                address: '<?php echo htmlspecialchars($warehouse['address']); ?>'
            },
        <?php endforeach; ?>
    };

    // Generate transfer order number
    function generateTransferOrderNo() {
        const fromWarehouse = document.getElementById('from_warehouse').value;
        const toWarehouse = document.getElementById('to_warehouse').value;
        
        if (!fromWarehouse || !toWarehouse) {
            return 'TO-' + String(Math.floor(Math.random() * 10000)).padStart(4, '0');
        }
        
        // Format: TO-sourceId-destId-0001
        return 'TO-' + fromWarehouse + '-' + toWarehouse + '-0001';
    }

    // Initialize transfer order no field on page load
    window.addEventListener('DOMContentLoaded', function() {
        const toField = document.querySelector('input[name="transfer_order_no"]');
        const isEdit = !!document.querySelector('input[name="transfer_id"]');

        // Set transfer order no when missing:
        // - keep existing transfer value when editing
        // - generate when creating new OR if missing due empty DB value
        const fromWarehouseSelect = document.getElementById('from_warehouse');
        const toWarehouseSelect = document.getElementById('to_warehouse');

        if (toField) {
            if (!toField.value) {
                if (fromWarehouseSelect && toWarehouseSelect && fromWarehouseSelect.value && toWarehouseSelect.value) {
                    toField.value = generateTransferOrderNo();
                } else if (!isEdit) {
                    toField.value = generateTransferOrderNo();
                }
            }
        }

        // Update warehouse address displays when editing an existing transfer
        if (fromWarehouseSelect && toWarehouseSelect) {
            const fromWarehouseValue = fromWarehouseSelect.value;
            const toWarehouseValue = toWarehouseSelect.value;
            document.getElementById('source_address').textContent = warehouseData[fromWarehouseValue]?.address || 'Select a warehouse to see address';
            document.getElementById('dest_address').textContent = warehouseData[toWarehouseValue]?.address || 'Select a warehouse to see address';
        }

        // Fetch and set last warehouse as default source warehouse when creating a new transfer
        if (!isEdit) {
            fetch('?page=products&action=get_last_warehouse', {
                method: 'GET'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.warehouse_id) {
                    fromWarehouseSelect.value = data.warehouse_id;
                    // Trigger change event to update address display
                    fromWarehouseSelect.dispatchEvent(new Event('change'));
                }
            })
            .catch(error => console.log('No previous warehouse found:', error));
        }
    });

    // Update warehouse addresses and transfer order number
    document.getElementById('from_warehouse').addEventListener('change', function() {
        const address = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        document.getElementById('source_address').textContent = address;
        // Generate new transfer order no when warehouse changes (only for new transfers)
        if (!document.querySelector('input[name="transfer_id"]')) {
            document.querySelector('input[name="transfer_order_no"]').value = generateTransferOrderNo();
        }
    });

    document.getElementById('to_warehouse').addEventListener('change', function() {
        const address = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        document.getElementById('dest_address').textContent = address;
        // Generate new transfer order no when warehouse changes (only for new transfers)
        if (!document.querySelector('input[name="transfer_id"]')) {
            document.querySelector('input[name="transfer_order_no"]').value = generateTransferOrderNo();
        }
    });

    // Delete item row
    document.querySelectorAll('.delete-item-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Are you sure you want to remove this item?')) {
                this.closest('.item-row').remove();
            }
        });
    });

    // Save as Draft
    document.getElementById('saveDraftBtn').addEventListener('click', function() {
        alert('Transfer order saved as draft!');
    });

    // Form submission
    document.getElementById('transferStockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fromWarehouse = document.getElementById('from_warehouse').value;
        const toWarehouse = document.getElementById('to_warehouse').value;
        
        if (!fromWarehouse || !toWarehouse) {
            alert('Please select both source and destination warehouses');
            return;
        }
        
        if (fromWarehouse === toWarehouse) {
            alert('Source and destination warehouses must be different');
            return;
        }

        const transferQtys = document.querySelectorAll('input[name="transfer_qty[]"]');
        const hasItems = Array.from(transferQtys).some(input => parseInt(input.value) > 0);
        
        if (!hasItems) {
            alert('Please enter transfer quantity for at least one item');
            return;
        }
        
        const transferIdInput = document.querySelector('input[name="transfer_id"]');
        fetch('?page=products&action=process_transfer_stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                transfer_id: transferIdInput ? parseInt(transferIdInput.value) : 0,
                transfer_order_no: document.querySelector('input[name="transfer_order_no"]').value,
                from_warehouse: fromWarehouse,
                to_warehouse: toWarehouse,
                product_ids: document.querySelector('input[name="product_ids"]').value,
                dispatch_date: document.getElementById('dispatch_date').value,
                est_delivery_date: document.getElementById('est_delivery_date').value,
                requested_by: document.getElementById('requested_by').value,
                dispatch_by: document.getElementById('dispatch_by').value,
                items: Array.from(document.querySelectorAll('.item-row')).map(row => ({
                    item_code: row.querySelector('input[name="item_code[]"]').value,
                    sku: row.querySelector('input[name="sku[]"]').value,
                    transfer_qty: parseInt(row.querySelector('input[name="transfer_qty[]"]').value) || 0,
                    item_notes: row.querySelector('textarea[name="item_notes[]"]').value || ''
                })),
                booking_no: document.querySelector('input[name="booking_no"]').value,
                vehicle_no: document.querySelector('input[name="vehicle_no"]').value,
                vehicle_type: document.querySelector('input[name="vehicle_type"]').value,
                driver_name: document.querySelector('input[name="driver_name"]').value,
                driver_mobile: document.querySelector('input[name="driver_mobile"]').value,
                create_pickup_list: document.getElementById('pickup_list').checked,
                create_picking_slip: document.getElementById('picking_slip').checked,
                create_delivery_challan: document.getElementById('delivery_challan').checked
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Stock transfer submitted successfully!');
                window.location.href = '?page=products&action=stock_transfer';
            } else {
                alert('Error: ' + (data.message || 'Unknown error occurred'));
                console.error('Transfer error:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred: ' + error.message);
        });
    });
</script>

