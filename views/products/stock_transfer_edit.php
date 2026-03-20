<div class="container mx-auto p-4">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900">Edit Stock Transfer</h1>
            <p class="text-sm text-gray-600">Transfer Order: <strong><?php echo htmlspecialchars($transfer['transfer_order_no']); ?></strong></p>
        </div>
        <a href="?page=products&action=stock_transfer" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700 transition">
            <i class="fas fa-arrow-left"></i>
            Back to List
        </a>
    </div>

    <form method="post" action="?page=products&action=stock_transfer_update" class="space-y-6 bg-white p-6 rounded-xl border border-gray-200">
        <input type="hidden" name="transfer_id" value="<?php echo (int)$transfer['id']; ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Dispatch Date</label>
                <input type="date" name="dispatch_date" value="<?php echo htmlspecialchars($transfer['dispatch_date']); ?>" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Est. Delivery Date</label>
                <input type="date" name="est_delivery_date" value="<?php echo htmlspecialchars($transfer['est_delivery_date']); ?>" class="w-full px-4 py-2 border rounded-lg" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">From Warehouse</label>
                <select name="from_warehouse" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo (int)$w['id']; ?>" <?php echo ((int)$w['id'] === (int)$transfer['from_warehouse']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($w['address_title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">To Warehouse</label>
                <select name="to_warehouse" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?php echo (int)$w['id']; ?>" <?php echo ((int)$w['id'] === (int)$transfer['to_warehouse']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($w['address_title']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Requested By</label>
                <select name="requested_by" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?php echo (int)$id; ?>" <?php echo ((int)$id === (int)$transfer['requested_by']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Dispatched By</label>
                <select name="dispatch_by" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?php echo (int)$id; ?>" <?php echo ((int)$id === (int)$transfer['dispatch_by']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-medium mb-2">Booking No</label>
                <input type="text" name="booking_no" value="<?php echo htmlspecialchars($transfer['booking_no']); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Vehicle No.</label>
                <input type="text" name="vehicle_no" value="<?php echo htmlspecialchars($transfer['vehicle_no']); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Vehicle Type</label>
                <input type="text" name="vehicle_type" value="<?php echo htmlspecialchars($transfer['vehicle_type']); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Driver Name</label>
                <input type="text" name="driver_name" value="<?php echo htmlspecialchars($transfer['driver_name']); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Driver Mobile</label>
                <input type="text" name="driver_mobile" value="<?php echo htmlspecialchars($transfer['driver_mobile']); ?>" class="w-full px-4 py-2 border rounded-lg">
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border rounded-lg">
                    <?php $statuses = ['pending', 'dispatched', 'received']; ?>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?php echo $status; ?>" <?php echo ($status === ($transfer['status'] ?? 'pending')) ? 'selected' : ''; ?>><?php echo ucfirst($status); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <a href="?page=products&action=stock_transfer" class="px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Cancel</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700">Save Changes</button>
        </div>
    </form>
</div>
