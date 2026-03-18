<style>
    body {
        background-color: #f8f9fa;
        margin: 0;
        padding: 0;
    }
    
    .transfer-container {
        width: 100%;
        margin: 0;
        padding: 1.5rem;
        box-sizing: border-box;
    }
    
    .transfer-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #e9ecef;
        max-width: 100%;
    }
    
    .transfer-header h1 {
        margin: 0;
        font-size: 1.75rem;
        font-weight: 600;
        color: #212529;
    }
    
    .form-section {
        background: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid #e9ecef;
        width: 100%;
        box-sizing: border-box;
    }
    
    .form-section-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        color: #212529;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
    }
    
    .form-group label {
        font-size: 0.875rem;
        font-weight: 600;
        margin-bottom: 0.5rem;
        color: #212529;
    }
    
    .form-group label .required {
        color: #dc3545;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        padding: 0.625rem 0.875rem;
        border: 1px solid #d1d5db;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-family: inherit;
        transition: all 0.2s ease;
    }
    
    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        outline: none;
        border-color: #d27e33;
        box-shadow: 0 0 0 3px rgba(210, 126, 51, 0.1);
    }
    
    .form-group input[readonly] {
        background-color: #f3f4f6;
        cursor: not-allowed;
    }
    
    .warehouse-selector {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        gap: 1.5rem;
        align-items: flex-start;
        margin-bottom: 1.5rem;
    }
    
    .warehouse-box {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.5rem;
        border: 1px solid #e9ecef;
    }
    
    .warehouse-arrow {
        display: flex;
        align-items: center;
        justify-content: center;
        padding-top: 1.75rem;
    }
    
    .warehouse-arrow button {
        background: #212529;
        color: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .warehouse-arrow button:hover {
        background: #d27e33;
    }
    
    .warehouse-address {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.5rem;
        line-height: 1.4;
    }
    
    .items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.875rem;
    }
    
    .items-table thead {
        background: #f3f4f6;
    }
    
    .items-table th {
        padding: 1rem;
        text-align: left;
        font-weight: 600;
        color: #212529;
        border-bottom: 2px solid #d1d5db;
    }
    
    .items-table td {
        padding: 1rem;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .items-table input,
    .items-table textarea {
        width: 100%;
        padding: 0.5rem;
        border: 1px solid #d1d5db;
        border-radius: 0.25rem;
        font-size: 0.875rem;
        box-sizing: border-box;
    }
    
    .items-table input[readonly] {
        background-color: #f3f4f6;
    }
    
    .product-image {
        width: 60px;
        height: 80px;
        object-fit: contain;
        border: 1px solid #e5e7eb;
        border-radius: 0.25rem;
        background: white;
    }
    
    .qty-display {
        background: #f3f4f6;
        padding: 0.5rem;
        border-radius: 0.25rem;
        font-size: 0.8125rem;
        margin-bottom: 0.25rem;
    }
    
    .qty-label {
        font-size: 0.7rem;
        font-weight: 600;
        color: #6b7280;
        margin-bottom: 0.25rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .delete-btn {
        background: none;
        border: none;
        color: #d1d5db;
        cursor: pointer;
        font-size: 1rem;
        padding: 0.25rem;
        transition: all 0.2s ease;
    }
    
    .delete-btn:hover {
        color: #dc3545;
    }
    
    .checkbox-group {
        display: flex;
        flex-wrap: wrap;
        gap: 2rem;
    }
    
    .checkbox-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .checkbox-item input {
        width: 1.125rem;
        height: 1.125rem;
        cursor: pointer;
        accent-color: #212529;
    }
    
    .checkbox-item label {
        margin: 0;
        cursor: pointer;
        font-size: 0.875rem;
        font-weight: normal;
    }
    
    .button-group {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 0.75rem 1.5rem;
        border: none;
        border-radius: 0.375rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }
    
    .btn-primary {
        background: #d27e33;
        color: white;
    }
    
    .btn-primary:hover {
        background: #b86d2c;
        box-shadow: 0 2px 8px rgba(210, 126, 51, 0.2);
    }
    
    .btn-secondary {
        background: #e5e7eb;
        color: #212529;
    }
    
    .btn-secondary:hover {
        background: #d1d5db;
    }
    
    .divider {
        border: none;
        border-top: 1px solid #e9ecef;
        margin: 1.5rem 0;
    }
    
    @media (max-width: 1024px) {
        .transfer-container {
            padding: 1rem;
        }
        
        .form-row {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        }
    }
    
    @media (max-width: 768px) {
        .transfer-container {
            padding: 0.75rem;
        }
        
        .transfer-header h1 {
            font-size: 1.5rem;
        }
        
        .form-row {
            grid-template-columns: 1fr;
            gap: 1rem;
        }
        
        .warehouse-selector {
            grid-template-columns: 1fr;
        }
        
        .warehouse-arrow {
            padding: 0;
            margin: -1rem 0 0 0;
        }
        
        .warehouse-arrow button {
            transform: rotate(90deg);
        }
        
        .items-table {
            font-size: 0.75rem;
        }
        
        .items-table th,
        .items-table td {
            padding: 0.75rem 0.5rem;
        }
        
        .product-image {
            width: 50px;
            height: 60px;
        }
        
        .button-group {
            justify-content: stretch;
        }
        
        .btn {
            flex: 1;
            justify-content: center;
        }
    }
    
    @media (max-width: 480px) {
        .transfer-container {
            padding: 0.5rem;
        }
        
        .transfer-header {
            margin-bottom: 1.5rem;
            gap: 0.5rem;
        }
        
        .transfer-header h1 {
            font-size: 1.25rem;
        }
        
        .form-section {
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .checkbox-group {
            gap: 1rem;
            flex-direction: column;
        }
    }
</style>

<div class="transfer-container">
    <!-- Header -->
    <div class="transfer-header">
        <div style="font-size: 1.75rem; color: #6b7280;">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h1>New Transfer Order</h1>
    </div>

    <!-- Main Form -->
    <form id="transferStockForm" class="space-y-0">
        <!-- Header Info Section -->
        <div class="form-section">
            <div class="form-row">
                <div class="form-group">
                    <label>Transfer Order No.</label>
                    <input type="text" name="transfer_order_no" readonly>
                </div>
                <div class="form-group">
                    <label>Dispatch Date <span class="required">*</span></label>
                    <input type="date" id="dispatch_date" name="dispatch_date" required>
                </div>
                <div class="form-group">
                    <label>Est Delivery Date <span class="required">*</span></label>
                    <input type="date" id="est_delivery_date" name="est_delivery_date" required>
                </div>
                <div class="form-group">
                    <label>Requested By <span class="required">*</span></label>
                    <select id="requested_by" name="requested_by" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Dispatch By <span class="required">*</span></label>
                    <select id="dispatch_by" name="dispatch_by" required>
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>"><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Warehouse Selection -->
        <div class="form-section">
            <div class="warehouse-selector">
                <div class="warehouse-box">
                    <div class="form-group">
                        <label>Source Warehouse <span class="required">*</span></label>
                        <select id="from_warehouse" name="from_warehouse" required>
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="warehouse-address" id="source_address">Select a warehouse to see address</div>
                </div>
                
                <div class="warehouse-arrow">
                    <button type="button" aria-label="Transfer direction">
                        <i class="fas fa-arrow-right"></i>
                    </button>
                </div>

                <div class="warehouse-box">
                    <div class="form-group">
                        <label>Destination Warehouse <span class="required">*</span></label>
                        <select id="to_warehouse" name="to_warehouse" required>
                            <option value="">-- Select Warehouse --</option>
                            <?php foreach ($warehouses as $warehouse): ?>
                                <option value="<?php echo htmlspecialchars($warehouse['id']); ?>"><?php echo htmlspecialchars($warehouse['address_title']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="warehouse-address" id="dest_address">Select a warehouse to see address</div>
                </div>
            </div>
        </div>

        <!-- Selected Items Section -->
        <div class="form-section">
            <div class="form-section-title">Selected Items</div>
            <div style="overflow-x: auto;">
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Item Code</th>
                            <th>SKU</th>
                            <th>Summary</th>
                            <th style="text-align: center;">Image</th>
                            <th>Quantity</th>
                            <th>Note</th>
                            <th style="width: 40px; text-align: center;"></th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <?php foreach ($products as $index => $product): ?>
                            <tr class="item-row" data-product-id="<?php echo $product['id']; ?>">
                                <td>
                                    <input type="text" value="<?php echo htmlspecialchars($product['item_code'] ?? ''); ?>" readonly>
                                    <input type="hidden" name="item_code[]" value="<?php echo htmlspecialchars($product['item_code'] ?? ''); ?>">
                                </td>
                                <td>
                                    <input type="text" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>" readonly>
                                    <input type="hidden" name="sku[]" value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                                </td>
                                <td>
                                    <div style="background: #f3f4f6; padding: 0.75rem; border-radius: 0.25rem; font-size: 0.8125rem; line-height: 1.4;">
                                        <?php echo htmlspecialchars($product['title'] ?? ''); ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <img src="<?php echo htmlspecialchars($product['image'] ?? ''); ?>" alt="Item" class="product-image" onerror="this.src='https://via.placeholder.com/60x80?text=No+Image'">
                                </td>
                                <td>
                                    <div class="qty-label">Available</div>
                                    <div class="qty-display"><?php echo htmlspecialchars($product['local_stock'] ?? 0); ?></div>
                                    <div class="qty-label">Transfer</div>
                                    <input type="number" name="transfer_qty[]" min="0" max="<?php echo htmlspecialchars($product['local_stock'] ?? 0); ?>" value="0" required>
                                </td>
                                <td>
                                    <textarea name="item_notes[]" style="min-height: 60px; resize: vertical;"></textarea>
                                </td>
                                <td style="text-align: center;">
                                    <button type="button" class="delete-btn delete-item-btn" aria-label="Delete item">
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
        <div class="form-section">
            <div class="form-section-title">Transportation Details</div>
            <div class="form-row">
                <div class="form-group">
                    <label>Booking No</label>
                    <input type="text" name="booking_no">
                </div>
                <div class="form-group">
                    <label>Vehicle No</label>
                    <input type="text" name="vehicle_no">
                </div>
                <div class="form-group">
                    <label>Vehicle Type</label>
                    <input type="text" name="vehicle_type">
                </div>
                <div class="form-group">
                    <label>Pickup Date Time</label>
                    <input type="datetime-local" name="pickup_datetime">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>E-Way Bill File</label>
                    <div style="display: flex; gap: 0.5rem;">
                        <input type="text" name="eway_bill_file" placeholder="E-Way Bill File" style="flex: 1;">
                        <button type="button" class="btn btn-secondary" style="width: auto;">
                            <i class="fas fa-folder-open"></i> Browse
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label>Driver Name</label>
                    <input type="text" name="driver_name">
                </div>
                <div class="form-group">
                    <label>Driver Mobile</label>
                    <input type="tel" name="driver_mobile">
                </div>
                <div class="form-group">
                    <label>Tracking Link</label>
                    <input type="url" name="tracking_link">
                </div>
            </div>
        </div>

        <!-- Footer Options -->
        <div class="form-section">
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">
                <div class="checkbox-group">
                    <div class="checkbox-item">
                        <input type="checkbox" id="pickup_list" name="create_pickup_list" checked>
                        <label for="pickup_list">Create Pickup List</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="picking_slip" name="create_picking_slip" checked>
                        <label for="picking_slip">Create Picking Slip</label>
                    </div>
                    <div class="checkbox-item">
                        <input type="checkbox" id="delivery_challan" name="create_delivery_challan" checked>
                        <label for="delivery_challan">Create Delivery Challan</label>
                    </div>
                </div>

                <div class="button-group">
                    <button type="button" id="saveDraftBtn" class="btn btn-secondary">
                        <i class="fas fa-save"></i> Save as Draft
                    </button>
                    <button type="submit" class="btn btn-primary">
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
        if (toField) {
            toField.value = generateTransferOrderNo();
        }
        
        // Fetch and set last warehouse as default source warehouse
        fetch('?page=products&action=get_last_warehouse', {
            method: 'GET'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.warehouse_id) {
                const fromWarehouseSelect = document.getElementById('from_warehouse');
                fromWarehouseSelect.value = data.warehouse_id;
                // Trigger change event to update address display
                fromWarehouseSelect.dispatchEvent(new Event('change'));
            }
        })
        .catch(error => console.log('No previous warehouse found:', error));
    });

    // Update warehouse addresses and transfer order number
    document.getElementById('from_warehouse').addEventListener('change', function() {
        const address = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        document.getElementById('source_address').textContent = address;
        // Generate new transfer order no when warehouse changes
        document.querySelector('input[name="transfer_order_no"]').value = generateTransferOrderNo();
    });

    document.getElementById('to_warehouse').addEventListener('change', function() {
        const address = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        document.getElementById('dest_address').textContent = address;
        // Generate new transfer order no when warehouse changes
        document.querySelector('input[name="transfer_order_no"]').value = generateTransferOrderNo();
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
        
        fetch('?page=products&action=process_transfer_stock', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
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
                window.location.href = '?page=products&action=list';
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

