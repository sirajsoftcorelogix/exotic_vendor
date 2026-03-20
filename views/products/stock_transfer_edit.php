<div class="container mx-auto p-4">
    <?php $currentUserId = $_SESSION['user']['id'] ?? 0; ?>
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
                <label class="block text-sm font-medium mb-2">Transfer Order No.</label>
                <input type="text" name="transfer_order_no" value="<?php echo htmlspecialchars($transfer['transfer_order_no']); ?>" class="w-full px-4 py-2 border rounded-lg bg-gray-100 cursor-not-allowed" readonly>
            </div>
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
                        <option value="<?php echo (int)$id; ?>" <?php echo ((int)$id === (int)$transfer['requested_by'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">Dispatched By</label>
                <select name="dispatch_by" class="w-full px-4 py-2 border rounded-lg">
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?php echo (int)$id; ?>" <?php echo ((int)$id === (int)$transfer['dispatch_by'] ? 'selected' : ''); ?>><?php echo htmlspecialchars($name); ?></option>
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
                <label class="block text-sm font-medium mb-2">E-Way Bill</label>
                <?php if (!empty($transfer['eway_bill_file'])): ?>
                    <div class="mb-2 p-3 rounded border border-gray-200 bg-gray-50" id="existingEwayFileContainer">
                        <a href="/<?php echo htmlspecialchars($transfer['eway_bill_file']); ?>" target="_blank" class="text-sm text-blue-600 hover:underline" id="existingEwayLink">View/Download existing file</a>
                        <div class="mt-2">
                            <label class="inline-flex items-center gap-2">
                                <input type="checkbox" name="remove_eway_bill_file" value="1" id="remove_eway_bill_file_edit" class="form-checkbox text-red-500"> Remove existing file
                            </label>
                        </div>
                    </div>
                <?php endif; ?>
                <input type="file" name="eway_bill_file" accept="application/pdf,image/*" class="w-full px-4 py-2 border rounded-lg" id="eway_bill_file_edit">
                <input type="hidden" name="existing_eway_bill_file" id="existing_eway_bill_file_edit" value="<?php echo htmlspecialchars($transfer['eway_bill_file'] ?? ''); ?>">
                <div id="ewayBillPreviewEdit" class="mt-2"></div>
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

    <script>
        const ewayFileInput = document.getElementById('eway_bill_file_edit');
        const existingEwayInput = document.getElementById('existing_eway_bill_file_edit');
        const removeEwayCheckbox = document.getElementById('remove_eway_bill_file_edit');
        const ewayPreviewEdit = document.getElementById('ewayBillPreviewEdit');
        const existingContainer = document.getElementById('existingEwayFileContainer');

        function renderPreviewEdit(fileUrl, fileName, isExisting) {
            if (!ewayPreviewEdit) return;
            let html = '<div class="border border-gray-300 rounded-lg p-3 bg-gray-50"><div class="text-sm font-medium text-gray-700 mb-2">' + (fileName || 'E-Way Bill') + '</div>';
            const lower = (fileUrl || '').toLowerCase();
            if (lower.endsWith('.pdf')) {
                html += '<embed src="' + fileUrl + '" type="application/pdf" width="100%" height="240px" />';
            } else if (fileUrl.match(/\.(jpg|jpeg|png|gif)$/i)) {
                html += '<img src="' + fileUrl + '" class="max-w-full max-h-[240px] object-contain rounded" alt="E-Way Bill" />';
            } else if (isExisting) {
                html += '<a href="' + fileUrl + '" target="_blank" class="text-sm text-blue-600 hover:underline">Download file</a>';
            } else {
                html += '<span class="text-sm text-gray-700">Uploaded file: ' + fileName + '</span>';
            }
            html += '</div>';
            ewayPreviewEdit.innerHTML = html;
        }

        if (existingEwayInput && existingEwayInput.value) {
            const existingFileUrl = existingEwayInput.value.startsWith('http') ? existingEwayInput.value : '/' + existingEwayInput.value;
            renderPreviewEdit(existingFileUrl, existingEwayInput.value.split('/').pop(), true);
        }

        if (ewayFileInput) {
            ewayFileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const fileUrl = URL.createObjectURL(file);
                    renderPreviewEdit(fileUrl, file.name, false);
                    if (existingContainer) existingContainer.style.display = 'none';
                    if (removeEwayCheckbox) removeEwayCheckbox.checked = false;
                    if (existingEwayInput) existingEwayInput.value = '';
                } else {
                    ewayPreviewEdit.innerHTML = '';
                }
            });
        }

        if (removeEwayCheckbox) {
            removeEwayCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    if (existingContainer) existingContainer.style.display = 'none';
                    existingEwayInput.value = '';
                    ewayPreviewEdit.innerHTML = '';
                } else if (existingEwayInput && existingEwayInput.value) {
                    const existingFileUrl = existingEwayInput.value.startsWith('http') ? existingEwayInput.value : '/' + existingEwayInput.value;
                    renderPreviewEdit(existingFileUrl, existingEwayInput.value.split('/').pop(), true);
                }
            });
        }
    </script>
</div>
