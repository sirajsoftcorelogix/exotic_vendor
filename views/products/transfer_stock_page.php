<?php
$transferOrderNo = $transfer['transfer_order_no'] ?? '';
if (empty($transferOrderNo) && !empty($transfer['from_warehouse']) && !empty($transfer['to_warehouse'])) {
    $transferOrderNo = 'TO-' . intval($transfer['from_warehouse']) . '-' . intval($transfer['to_warehouse']) . '-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
if (empty($transferOrderNo)) {
    $transferOrderNo = 'TO-' . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
}
?>
<div class="min-h-screen bg-gray-50 p-6">
    <!-- Header -->
    <div class="flex items-center gap-4 mb-8 pb-4 border-b border-gray-200">
        <div class="text-2xl text-gray-500">
            <i class="fas fa-shipping-fast"></i>
        </div>
        <h1 class="text-2xl font-semibold text-gray-900"><?php echo isset($transfer) ? 'Edit Transfer Order' : 'New Transfer Order'; ?></h1>
    </div>

    <!-- Main Form -->
    <?php
        $currentUserId = $_SESSION['user']['id'] ?? 0;
        $isEditMode = !empty($transfer['id']);
        $selectedRequestedBy = $isEditMode ? ((int)($transfer['requested_by'] ?? 0)) : ((int)($transfer['requested_by'] ?? $currentUserId));
        $selectedDispatchBy = $isEditMode ? ((int)($transfer['dispatch_by'] ?? 0)) : ((int)($transfer['dispatch_by'] ?? $currentUserId));
        $defaultDispatchDate = !empty($transfer['dispatch_date']) ? $transfer['dispatch_date'] : date('Y-m-d');
    ?>
    <form id="transferStockForm" class="space-y-6" method="POST" enctype="multipart/form-data" action="?page=products&action=process_transfer_stock">
        <!-- Header Info Section -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="grid gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 mb-6">
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Transfer Order No.</label>
                    <input type="text" name="transfer_order_no" readonly value="<?php echo htmlspecialchars($transferOrderNo); ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                    <?php if (!empty($transfer['id'])): ?>
                        <input type="hidden" name="transfer_id" value="<?php echo (int)$transfer['id']; ?>">
                    <?php endif; ?>
                    <noscript><input type="hidden" name="noscript_fallback" value="1"></noscript>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch Date <span class="text-red-500">*</span></label>
                    <input type="date" id="dispatch_date" name="dispatch_date" value="<?php echo htmlspecialchars($defaultDispatchDate); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                    <p id="dispatchDateFormatted" class="text-xs text-gray-500 mt-1"></p>
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
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $selectedRequestedBy ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flex flex-col">
                    <label class="text-sm font-semibold text-gray-700 mb-2">Dispatch By <span class="text-red-500">*</span></label>
                    <select id="dispatch_by" name="dispatch_by" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                        <option value="">-- Select User --</option>
                        <?php foreach ($users as $user): ?>
                            <?php $dispatchBy = isset($transfer['dispatch_by']) && (int)$transfer['dispatch_by'] > 0 ? (int)$transfer['dispatch_by'] : null; ?>
                            <?php $defaultDispatch = $dispatchBy ?? (int)$currentUserId; ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo ((int)$user['id'] === $defaultDispatch ? 'selected' : ''); ?>><?php echo htmlspecialchars($user['name']); ?></option>
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
                                    <input type="number" name="transfer_qty[]" min="1" max="<?php echo htmlspecialchars($product['local_stock'] ?? 0); ?>" data-available="<?php echo htmlspecialchars($product['local_stock'] ?? 0); ?>" value="<?php echo htmlspecialchars($product['transfer_qty'] ?? 0); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
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

        <div class="mb-6 flex justify-end">
            <button id="addItemBtn" type="button" onclick="window.openAddItemModal ? openAddItemModal() : null" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition">
                <i class="fas fa-plus"></i> Add Item
            </button>
        </div>

        <!-- Add Item Modal -->
        <div id="addItemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-lg w-[90%] max-w-2xl p-4">
                <div class="flex justify-between items-center mb-3">
                    <h2 class="text-lg font-semibold">Add Product to Transfer</h2>
                    <button type="button" id="addItemModalClose" class="text-gray-500 hover:text-gray-800">✕</button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <input type="text" id="addItemSearchInput" placeholder="Enter item code or SKU" class="w-full px-3 py-2 border border-gray-300 rounded-lg" />
                    <button id="addItemSearchBtn" type="button" class="px-3 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700">Search</button>
                </div>

                <div id="addItemSearchMessage" class="text-sm text-red-600 mb-3 hidden"></div>

                <div id="addItemSearchResult" class="space-y-3"></div>
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
                    <label class="text-sm font-semibold text-gray-700 mb-2">E-Way Bill</label>
                    <input type="file" id="eway_bill_file" name="eway_bill_file" accept="application/pdf,image/*" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
                    <input type="hidden" name="existing_eway_bill_file" id="existing_eway_bill_file" value="<?php echo htmlspecialchars($transfer['eway_bill_file'] ?? ''); ?>">
                    <input type="hidden" name="remove_eway_bill_file" id="remove_eway_bill_file" value="0">
                    <div id="ewayBillPreview" class="mt-2"></div>
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
                    <div class="flex flex-wrap justify-end gap-4">
                        <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-orange-500 text-white hover:bg-orange-600 transition">
                        <i class="fas fa-check"></i> Submit
                    </button>
                </div>
            </div>
        </div>

        <!-- Hidden field -->
        <input type="hidden" id="product_ids" name="product_ids" value="<?php echo htmlspecialchars($product_ids); ?>">
    </form>
</div>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div id="transferNoticeModal" class="fixed inset-0 z-[110] hidden items-center justify-center bg-black/50 p-4" role="dialog" aria-modal="true" aria-labelledby="transferNoticeTitle">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-2xl ring-1 ring-gray-900/10">
        <div class="px-5 py-4 border-b border-gray-100">
            <h3 id="transferNoticeTitle" class="text-base font-semibold text-gray-900">Stock Transfer</h3>
        </div>
        <div class="px-5 py-4">
            <p id="transferNoticeMessage" class="text-sm text-gray-700 leading-relaxed"></p>
        </div>
        <div class="px-5 py-4 border-t border-gray-100 flex justify-end">
            <button type="button" id="transferNoticeOk" class="inline-flex items-center justify-center rounded-lg bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2">OK</button>
        </div>
    </div>
</div>

<script>
    const transferNoticeModal = document.getElementById('transferNoticeModal');
    const transferNoticeMessage = document.getElementById('transferNoticeMessage');
    const transferNoticeOk = document.getElementById('transferNoticeOk');

    function showTransferNotice(message) {
        if (!transferNoticeModal || !transferNoticeMessage) {
            alert(message);
            return;
        }
        transferNoticeMessage.textContent = String(message || 'Something went wrong.');
        transferNoticeModal.classList.remove('hidden');
        transferNoticeModal.classList.add('flex');
        if (transferNoticeOk) transferNoticeOk.focus();
    }

    function closeTransferNotice() {
        if (!transferNoticeModal) return;
        transferNoticeModal.classList.add('hidden');
        transferNoticeModal.classList.remove('flex');
    }

    if (transferNoticeOk) {
        transferNoticeOk.addEventListener('click', closeTransferNotice);
    }
    if (transferNoticeModal) {
        transferNoticeModal.addEventListener('click', function (e) {
            if (e.target === transferNoticeModal) closeTransferNotice();
        });
    }

    // Warehouse address mapping and data
    const warehouseData = {
        <?php foreach ($warehouses as $warehouse): 
            $name = trim($warehouse['address_title'] ?? '');
            $addr = trim($warehouse['address'] ?? '');
        ?>
            <?php echo (int)$warehouse['id']; ?>: {
                name: <?php echo json_encode($name, JSON_UNESCAPED_UNICODE); ?>,
                address: <?php echo json_encode($addr, JSON_UNESCAPED_UNICODE); ?>
            },
        <?php endforeach; ?>
    };

    // Generate transfer order number in constant source-dest format (fallback if endpoint unavailable)
    function generateTransferOrderNo() {
        const fromWarehouse = document.getElementById('from_warehouse').value;
        const toWarehouse = document.getElementById('to_warehouse').value;

        if (fromWarehouse && toWarehouse) {
            return 'TO-' + fromWarehouse + '-' + toWarehouse + '-0001';
        }
        return 'TO-' + String(Math.floor(Math.random() * 10000)).padStart(4, '0');
    }

    function apiUrl(action, query = '') {
        const basePath = window.location.pathname.replace(/\/$/, '');
        return `${basePath}?page=products&action=${encodeURIComponent(action)}${query}`;
    }

    function fetchNextTransferOrderNo(fromWarehouse, toWarehouse) {
        return fetch(apiUrl('get_transfer_order_no', `&from_warehouse=${encodeURIComponent(fromWarehouse)}&to_warehouse=${encodeURIComponent(toWarehouse)}`), {
            credentials: 'same-origin'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.transfer_order_no) {
                    return data.transfer_order_no;
                }
                return generateTransferOrderNo();
            })
            .catch(() => generateTransferOrderNo());
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
            if (!toField.value && fromWarehouseSelect && toWarehouseSelect && fromWarehouseSelect.value && toWarehouseSelect.value) {
                fetchNextTransferOrderNo(fromWarehouseSelect.value, toWarehouseSelect.value).then(no => {
                    toField.value = no;
                });
            } else if (!toField.value && !isEdit) {
                toField.value = generateTransferOrderNo();
            }
        }

        // Update warehouse address displays when editing an existing transfer
        if (fromWarehouseSelect && toWarehouseSelect) {
            const fromWarehouseValue = fromWarehouseSelect.value;
            const toWarehouseValue = toWarehouseSelect.value;
            document.getElementById('source_address').textContent = warehouseData[fromWarehouseValue]?.address || 'Select a warehouse to see address';
            document.getElementById('dest_address').textContent = warehouseData[toWarehouseValue]?.address || 'Select a warehouse to see address';

            // ensure order no is present when both warehouses selected
            if (toField && fromWarehouseValue && toWarehouseValue) {
                fetchNextTransferOrderNo(fromWarehouseValue, toWarehouseValue).then(no => {
                    toField.value = no;
                });
            }
        }

        // E-Way Bill preview + preloaded file (if editing)
        const ewayInput = document.getElementById('eway_bill_file');
        const ewayPreview = document.getElementById('ewayBillPreview');
        const existingEwayInput = document.getElementById('existing_eway_bill_file');
        const removeEwayInput = document.getElementById('remove_eway_bill_file');

        function showEwayPreview(fileUrl, fileName, isExisting = false) {
            if (!ewayPreview) return;
            let html = '<div class="border border-gray-300 rounded-lg p-3 bg-gray-50">';
            html += '<div class="flex items-center justify-between gap-3 mb-2">';
            html += '<span class="text-sm font-medium text-gray-700">' + (fileName || 'Uploaded E-Way Bill') + '</span>';
            html += '<button type="button" id="removeEwayBtn" class="text-sm text-red-600 hover:text-red-800">Remove</button>';
            html += '</div>';

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
            ewayPreview.innerHTML = html;

            document.getElementById('removeEwayBtn').addEventListener('click', function() {
                existingEwayInput.value = '';
                removeEwayInput.value = '1';
                ewayPreview.innerHTML = '';
                ewayInput.value = '';
            });
        }

        if (existingEwayInput && existingEwayInput.value) {
            const existingUrl = existingEwayInput.value;
            showEwayPreview(existingUrl, existingUrl.split('/').pop(), true);
        }

        if (ewayInput) {
            ewayInput.addEventListener('change', function () {
                removeEwayInput.value = '0';
                if (this.files && this.files[0]) {
                    const file = this.files[0];
                    const fileUrl = URL.createObjectURL(file);
                    showEwayPreview(fileUrl, file.name);
                } else {
                    ewayPreview.innerHTML = '';
                }
            });
        }

        // Fetch and set last warehouse as default source warehouse when creating a new transfer
        if (!isEdit) {
            fetch(apiUrl('get_last_warehouse'), {
                method: 'GET',
                credentials: 'same-origin'
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
        const orderInput = document.querySelector('input[name="transfer_order_no"]');
        const toWarehouseValue = document.getElementById('to_warehouse').value;
        if (orderInput && this.value && toWarehouseValue) {
            fetchNextTransferOrderNo(this.value, toWarehouseValue).then(no => {
                orderInput.value = no;
            });
        }
    });

    document.getElementById('to_warehouse').addEventListener('change', function() {
        const address = warehouseData[this.value]?.address || 'Select a warehouse to see address';
        document.getElementById('dest_address').textContent = address;
        const orderInput = document.querySelector('input[name="transfer_order_no"]');
        const fromWarehouseValue = document.getElementById('from_warehouse').value;
        if (orderInput && fromWarehouseValue && this.value) {
            fetchNextTransferOrderNo(fromWarehouseValue, this.value).then(no => {
                orderInput.value = no;
            });
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

    function formatDateLabel(dateString) {
        if (!dateString) return '';
        const d = new Date(dateString);
        if (Number.isNaN(d.getTime())) return dateString;
        const options = { day: 'numeric', month: 'long', year: 'numeric' };
        return d.toLocaleDateString('en-GB', options); // 21 March 2026
    }

    const dispatchDateInput = document.getElementById('dispatch_date');
    const dispatchDateFormatted = document.getElementById('dispatchDateFormatted');
    if (dispatchDateInput && dispatchDateFormatted) {
        const updateFormatted = () => {
            dispatchDateFormatted.textContent = formatDateLabel(dispatchDateInput.value);
        };
        dispatchDateInput.addEventListener('change', updateFormatted);
        updateFormatted();
    }

    // Form submission
    document.getElementById('transferStockForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fromWarehouse = document.getElementById('from_warehouse').value;
        const toWarehouse = document.getElementById('to_warehouse').value;
        
        if (!fromWarehouse || !toWarehouse) {
            showTransferNotice('Please select both source and destination warehouses');
            return;
        }
        
        if (fromWarehouse === toWarehouse) {
            showTransferNotice('Source and destination warehouses must be different');
            return;
        }

        const transferQtys = document.querySelectorAll('input[name="transfer_qty[]"]');
        let hasValidQty = false;

        for (const input of transferQtys) {
            const val = parseInt(input.value, 10);
            const available = parseInt(input.dataset.available, 10);

            if (isNaN(val)) {
                showTransferNotice('Please enter a valid transfer quantity for each item.');
                return;
            }

            if (val <= 0) {
                showTransferNotice('Transfer quantity must be greater than zero for each item used in transfer.');
                input.focus();
                return;
            }

            if (!isNaN(available) && val > available) {
                showTransferNotice(`Transfer quantity for an item cannot exceed available stock (${available}).`);
                input.focus();
                return;
            }

            if (val > 0) {
                hasValidQty = true;
            }
        }

        if (!hasValidQty) {
            showTransferNotice('Please enter transfer quantity for at least one item');
            return;
        }

        const formData = new FormData(document.getElementById('transferStockForm'));
        fetch(apiUrl('process_transfer_stock'), {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showTransferNotice('Stock transfer submitted successfully!');
                window.location.href = '?page=products&action=stock_transfer';
            } else {
                showTransferNotice('Error: ' + (data.message || 'Unknown error occurred'));
                console.error('Transfer error:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showTransferNotice('An error occurred: ' + error.message);
        });
    });

    let addItemModal = null;
    let addItemBtn = null;
    let addItemModalClose = null;
    let addItemSearchBtn = null;
    let addItemSearchInput = null;
    let addItemSearchMessage = null;
    let addItemSearchResult = null;
    let itemsTableBody = null;

    window.openAddItemModal = function() {
        const addItemModal = document.getElementById('addItemModal');
        const addItemSearchInput = document.getElementById('addItemSearchInput');
        const addItemSearchMessage = document.getElementById('addItemSearchMessage');
        const addItemSearchResult = document.getElementById('addItemSearchResult');

        if (!addItemModal || !addItemSearchInput || !addItemSearchMessage || !addItemSearchResult) return;

        addItemSearchInput.value = '';
        addItemSearchMessage.classList.add('hidden');
        addItemSearchResult.innerHTML = '';
        addItemModal.classList.remove('hidden');
        addItemModal.classList.add('flex');
    };

    window.closeAddItemModal = function() {
        const addItemModal = document.getElementById('addItemModal');
        if (!addItemModal) return;
        addItemModal.classList.add('hidden');
        addItemModal.classList.remove('flex');
    };

    document.addEventListener('DOMContentLoaded', function() {
        addItemModal = document.getElementById('addItemModal');
        addItemBtn = document.getElementById('addItemBtn');
        addItemModalClose = document.getElementById('addItemModalClose');
        addItemSearchBtn = document.getElementById('addItemSearchBtn');
        addItemSearchInput = document.getElementById('addItemSearchInput');
        addItemSearchMessage = document.getElementById('addItemSearchMessage');
        addItemSearchResult = document.getElementById('addItemSearchResult');
        itemsTableBody = document.getElementById('itemsTableBody');

        if (addItemBtn) addItemBtn.addEventListener('click', openAddItemModal);
        if (addItemModalClose) addItemModalClose.addEventListener('click', closeAddItemModal);
        if (addItemModal) {
            addItemModal.addEventListener('click', function(event) {
                if (event.target === addItemModal) closeAddItemModal();
            });
        }

        if (addItemSearchBtn) addItemSearchBtn.addEventListener('click', searchAddItem);
        if (addItemSearchInput) addItemSearchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchAddItem();
            }
        });

        if (itemsTableBody) {
            itemsTableBody.addEventListener('click', function(e) {
                const btn = e.target.closest('.delete-item-btn');
                if (!btn) return;
                const row = btn.closest('tr');
                if (row) row.remove();
            });
        }
    });


    function decodeHtml(html) {
        const txt = document.createElement('textarea');
        txt.innerHTML = html;
        return txt.value;
    }

    function addNewItemToTable(product) {
        const itemsTableBody = document.getElementById('itemsTableBody');
        if (!itemsTableBody || !product) return;

        const existingSku = Array.from(itemsTableBody.querySelectorAll('input[name="sku[]"]')).some(i => i.value.trim() === product.sku);
        if (existingSku) {
            showTransferNotice('This product is already in the transfer list.');
            return;
        }

        const tr = document.createElement('tr');
        tr.className = 'item-row';

        tr.innerHTML = `
            <td class="px-4 py-3 border-b border-gray-200">
                <input type="text" value="${decodeHtml(product.item_code || product.itemcode || '')}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                <input type="hidden" name="item_code[]" value="${decodeHtml(product.item_code || product.itemcode || '')}">
            </td>
            <td class="px-4 py-3 border-b border-gray-200">
                <input type="text" value="${decodeHtml(product.sku || '')}" readonly class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-gray-100 text-sm text-gray-700 cursor-not-allowed">
                <input type="hidden" name="sku[]" value="${decodeHtml(product.sku || '')}">
            </td>
            <td class="px-4 py-3 border-b border-gray-200">
                <div class="bg-gray-100 p-3 rounded-md text-sm leading-relaxed">${decodeHtml(product.title || product.item_name || '')}</div>
                <input type="hidden" name="title[]" value="${decodeHtml(product.title || product.item_name || '')}">
            </td>
            <td class="px-4 py-3 border-b border-gray-200 text-center">
                <img src="${decodeHtml(product.image || 'https://via.placeholder.com/60x80?text=No+Image')}" alt="Item" class="w-14 h-20 object-contain border border-gray-200 rounded bg-white" onerror="this.src='https://via.placeholder.com/60x80?text=No+Image'">
            </td>
            <td class="px-4 py-3 border-b border-gray-200">
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Available</div>
                <div class="bg-gray-100 px-2 py-1 rounded text-xs text-gray-700 mb-2">${parseInt(product.local_stock ?? product.stock ?? 0)}</div>
                <div class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Transfer</div>
                <input type="number" name="transfer_qty[]" min="1" max="${parseInt(product.local_stock ?? product.stock ?? 0)}" data-available="${parseInt(product.local_stock ?? product.stock ?? 0)}" value="1" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-orange-300 focus:border-orange-500">
            </td>
            <td class="px-4 py-3 border-b border-gray-200">
                <textarea name="item_notes[]" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-y min-h-[60px]"></textarea>
            </td>
            <td class="px-4 py-3 border-b border-gray-200 text-center">
                <button type="button" class="text-gray-400 hover:text-red-500 transition delete-item-btn" aria-label="Delete item"><i class="fas fa-trash-alt"></i></button>
            </td>
        `;

        itemsTableBody.appendChild(tr);

        const productIdsInput = document.getElementById('product_ids');
        if (productIdsInput) {
            const existingIds = productIdsInput.value ? productIdsInput.value.split(',').map(id => id.trim()).filter(Boolean) : [];
            const newId = product.id ? String(product.id) : '';
            if (newId && !existingIds.includes(newId)) {
                existingIds.push(newId);
                productIdsInput.value = existingIds.join(',');
            }
        }
    }


    async function searchAddItem() {
        if (!addItemSearchInput || !addItemSearchMessage || !addItemSearchResult) return;
        const query = addItemSearchInput.value.trim();
        addItemSearchMessage.classList.add('hidden');
        addItemSearchResult.innerHTML = '';

        if (!query) {
            addItemSearchMessage.textContent = 'Please enter item code or SKU to search.';
            addItemSearchMessage.classList.remove('hidden');
            return;
        }

        const resp = await fetch(apiUrl('search_product', `&q=${encodeURIComponent(query)}`), {
            headers: { 'Accept': 'application/json' },
            credentials: 'same-origin'
        });
        const data = await resp.json();
        if (!data.success) {
            addItemSearchMessage.textContent = data.message || 'Product not found';
            addItemSearchMessage.classList.remove('hidden');
            return;
        }

        const products = Array.isArray(data.products) ? data.products : [];
        if (products.length === 0) {
            addItemSearchMessage.textContent = 'Product not found';
            addItemSearchMessage.classList.remove('hidden');
            return;
        }

        addItemSearchResult.innerHTML = '';

        for (const prod of products) {
            const stock = parseInt(prod.local_stock ?? prod.stock ?? 0, 10);
            const img = prod.image ? prod.image : 'https://via.placeholder.com/60x80?text=No+Image';
            const card = document.createElement('div');
            card.className = 'bg-gray-50 border border-gray-200 p-3 rounded-lg flex gap-3 items-center justify-between';
            card.innerHTML = `
                <div class="flex items-center gap-3">
                    <img src="${decodeHtml(img)}" class="w-16 h-20 object-contain border border-gray-200 rounded" onerror="this.src='https://via.placeholder.com/60x80?text=No+Image'">
                    <div>
                        <div class="font-semibold text-gray-800">${decodeHtml(prod.title || prod.item_name || '')}</div>
                        <div class="text-xs text-gray-600">Item Code: ${decodeHtml(prod.item_code || prod.itemcode || '')}</div>
                        <div class="text-xs text-gray-600">SKU: ${decodeHtml(prod.sku || '')}</div>
                        <div class="text-xs text-gray-600">Available: ${stock}</div>
                    </div>
                </div>
                <button type="button" class="addSearchedProductBtn px-3 py-2 rounded-lg bg-amber-600 text-white hover:bg-amber-700">Add Item</button>
            `;

            const addBtn = card.querySelector('.addSearchedProductBtn');
            addBtn.addEventListener('click', function() {
                addNewItemToTable(prod);
                closeAddItemModal();
            });

            addItemSearchResult.appendChild(card);
        }

    }

</script>

