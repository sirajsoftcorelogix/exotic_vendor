<?php
    $mode = $data['mode'] ?? 'create';
    $grn = $data['grn'] ?? [];
    $formAction = $mode === 'edit' ? '?page=stock_transfer_grns&action=update' : '#';
    $pageTitle = $mode === 'edit' ? 'Edit' : 'Create';
    $receivedDateValue = $mode === 'edit' ? ($grn['received_date'] ?? date('Y-m-d')) : date('Y-m-d');
    $qualityAcceptChecked = $mode === 'edit' ? ((int)($grn['qty_acceptable'] ?? 0) > 0) : true;
    $grnRemarksValue = $mode === 'edit' ? htmlspecialchars($grn['remarks'] ?? '') : '';
?>
<div class="container mx-auto px-8 bg-white">    
    <?php if ($mode === 'edit'): ?>
        <form method="post" action="<?php echo $formAction; ?>">
            <input type="hidden" name="grn_id" value="<?php echo (int)($grn['id'] ?? 0); ?>">
            <input type="hidden" name="transfer_id" value="<?php echo (int)($grn['transfer_id'] ?? $transfer['id'] ?? 0); ?>">
    <?php endif; ?>
    <div class="pt-8 pb-6 text-center">
        <h1 class="text-2xl md:text-4xl font-bold text-orange-600"><?php echo $pageTitle; ?></h1>
        <h1 class="type-page-header text-base md:text-lg text-orange-600">Goods Receipt Note</h1> 
    </div>

    <!-- Transfer Header -->
    <div class="w-full mb-8 border-b border-gray-200 pb-8">
        <div class="w-full md:w-[800px] mx-auto grid grid-cols-1 md:grid-cols-2 gap-x-12 gap-y-0">
            <div class="space-y-0">
                <div class="flex">
                    <span class="type-label-small w-32">Transfer Order</span>
                    <span class="type-data-small">: &nbsp; <?= htmlspecialchars($transfer['transfer_order_no'] ?? '') ?></span>
                </div>
                <div class="flex">
                    <span class="type-label-small w-32">From</span>
                    <span class="type-data-small">: &nbsp; <?= htmlspecialchars($transfer['source_name'] ?? '') ?></span>
                </div>
                
            </div>

            <div class="space-y-0">
                <div class="flex">
                    <span class="type-label-small w-32">Dispatch Date</span>
                    <span class="type-data-small">: &nbsp; <?= !empty($transfer['dispatch_date']) ? date('j F Y', strtotime($transfer['dispatch_date'])) : '' ?></span>
                </div>
                <div class="flex">
                    <span class="type-label-small w-32">To</span>
                    <span class="type-data-small">: &nbsp; <?= htmlspecialchars($transfer['dest_name'] ?? '') ?></span>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
        <?php if ($mode === 'create' || $mode === 'edit'): ?>
            <?php foreach ($transfer['items'] as $item): ?>
                <?php
                    $label = trim($item['sku'] ?? '');
                    if ($label === '') {
                        $label = trim($item['item_code'] ?? '');
                    }
                    $product = $item['product'] ?? null;
                    $imageUrl = $product['image'] ?? '';
                    $title = $product['title'] ?? $label;
                    $quantity = (int)$item['transfer_qty'];
                    $weight = $product['product_weight'] ?? '';
                    $weightUnit = $product['product_weight_unit'] ?? '';
                    $height = $product['prod_height'] ?? '';
                    $width = $product['prod_width'] ?? '';
                    $depth = $product['prod_length'] ?? '';
                    $lengthUnit = $product['length_unit'] ?? '';
                    $material = $product['material'] ?? '';
                    $prevReceived = isset($item['previously_received_qty']) ? (int)$item['previously_received_qty'] : ($product['local_stock'] ?? 0);
                ?>
            <div class="custom-card p-5">
                <div class="flex flex-col sm:flex-row gap-5 mb-5">
                    <div class="w-full sm:w-32 h-40 shrink-0 bg-gray-200 rounded-md overflow-hidden border border-gray-300 flex items-center justify-center">
                        <?php if (!empty($imageUrl)): ?>
                            <img onclick="openImagePopup('<?= htmlspecialchars($imageUrl) ?>')" src="<?= htmlspecialchars($imageUrl) ?>" alt="Item Image" class="max-w-full max-h-full object-contain cursor-pointer ">
                        <?php else: ?>
                            <span class="text-gray-500 text-sm">No Image</span>
                        <?php endif; ?>
                    </div>

                    <div class="flex-1">
                        <h3 class="type-item-name mb-3"><?= htmlspecialchars($title) ?></h3>

                        <div class="grid grid-cols-2 gap-x-4 gap-y-0">
                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">SKU</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['sku'] ?? 'N/A') ?></span>
                            </div>
                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Item Code</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($item['item_code'] ?? 'N/A') ?></span>
                            </div>

                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Quantity</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($quantity) ?></span>
                            </div>
                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Height</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($height) ?: '0' ?></span>
                            </div>

                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Weight</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($weight) ?> <?= htmlspecialchars($weightUnit) ?></span>
                            </div>
                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Width</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($width) ?></span>
                            </div>

                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Material</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($material) ?></span>
                            </div>
                            <div class="flex items-baseline">
                                <span class="type-label-small w-20 shrink-0">Depth</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($depth) ?> <?= htmlspecialchars($lengthUnit) ?></span>
                            </div>
                            <div class="flex items-baseline w-48">
                                <span class="type-label-small shrink-0">Previously Received Qty</span>
                                <span class="type-data-small">: &nbsp; <?= htmlspecialchars($prevReceived) ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap gap-4 mb-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" class="custom-checkbox" name="qty_acceptable[]" value="1" <?php echo ($mode === 'edit' ? ((int)($item['qty_acceptable'] ?? 0) > 0 ? 'checked' : '') : 'checked'); ?> >
                        <span class="type-checkbox-label">Quality Acceptable</span>                    
                    </label>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input name="qty_received[]" type="number" min="0" class="ml-0 w-16 px-2 py-1 border rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-gray-400" placeholder="Qty" value="<?= htmlspecialchars(($mode === 'edit' ? (int)($item['qty_received'] ?? $item['transfer_qty']) : $quantity)) ?>">
                        <span class="type-checkbox-label">Quantity Received</span>                    
                    </label>
                </div>

                <div>
                    <textarea
                            class="w-full p-3 rounded-xl border text-gray-600 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 resize-none"
                            style="border-color: rgba(163, 163, 163, 1); height: 100px;"
                            placeholder="Remarks, if any"
                            name="item_remarks[]"><?= htmlspecialchars(($mode === 'edit' ? $item['remarks'] ?? '' : '')) ?></textarea>
                </div>

                <input type="hidden" name="grn_row_id[]" value="<?= (int)($item['id'] ?? 0) ?>">
                <input type="hidden" name="item_id[]" value="<?= (int)($item['item_id'] ?? $item['id'] ?? 0) ?>">
                <input type="hidden" name="sku[]" value="<?= htmlspecialchars($item['sku'] ?? '') ?>">
                <input type="hidden" name="item_code[]" value="<?= htmlspecialchars($item['item_code'] ?? '') ?>">
                <input type="hidden" name="transfer_qty[]" value="<?= (int)($item['transfer_qty'] ?? 0) ?>">
            </div>
        <?php endforeach; ?>
    <?php else: ?>
            <div class="custom-card p-5">
                <div class="flex items-center gap-4 mb-4">
                    <p class="font-semibold">GRN ID:</p>
                    <p><?php echo (int)($grn['id'] ?? 0); ?></p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div><strong>Transfer Order</strong>: <?= htmlspecialchars($transfer['transfer_order_no'] ?? '') ?></div>
                    <div><strong>SKU</strong>: <?= htmlspecialchars($grn['sku'] ?? $transfer['items'][0]['sku'] ?? '') ?></div>
                    <div><strong>Item Code</strong>: <?= htmlspecialchars($grn['item_code'] ?? $transfer['items'][0]['item_code'] ?? '') ?></div>
                    <div><strong>Location</strong>: <?= htmlspecialchars($transfer['dest_name'] ?? '') ?></div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div><strong>Quantity Transferred</strong>: <?= (int)($transfer['items'][0]['transfer_qty'] ?? 0) ?></div>
                    <div><strong>Quantity Received</strong>: <input type="number" name="qty_received" min="0" value="<?= (int)($grn['qty_received'] ?? 0) ?>" class="w-24 border rounded px-2 py-1"></div>
                    <div><strong>Qty Acceptable</strong>: <input type="number" name="qty_acceptable" min="0" value="<?= (int)($grn['qty_acceptable'] ?? 0) ?>" class="w-24 border rounded px-2 py-1"></div>
                    <div><strong>Received Date</strong>: <input type="date" name="received_date" value="<?= htmlspecialchars($receivedDateValue) ?>" class="border rounded px-2 py-1"></div>
                </div>
                <div class="mt-4">
                    <strong>Remarks</strong>
                    <textarea name="remarks" class="w-full mt-2 p-2 border rounded" rows="3"><?= htmlspecialchars($grnRemarksValue) ?></textarea>
                </div>
            </div>
    <?php endif; ?>
    </div>

    <div class="mt-8 flex flex-col gap-8 items-center">

        <div class="flex flex-col md:flex-row gap-6 justify-center w-full">
            <div class="w-[320px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Received Date <span class="text-red-500">*</span></label>
                <div class="relative">
                    <input type="date" name="received_date" value="<?= htmlspecialchars($receivedDateValue) ?>" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-500 text-sm focus:outline-none">
                </div>
            </div>
            <div class="w-[320px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Received by <span class="text-red-500">*</span></label>
                <select name="received_by" id="receivedBy" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
                    <option value="">Select User</option>
                    <?php $defaultReceivedBy = (int)($default_received_by ?? ($_SESSION['user']['id'] ?? ($_SESSION['user_id'] ?? 0))); ?>
                    <?php foreach ($users as $id => $name): ?>
                        <option value="<?= $id ?>" <?= ($id === $defaultReceivedBy) ? 'selected' : '' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="w-[320px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Warehouse <span class="text-red-500">*</span></label>
                <select name="warehouse_id" class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
                    <option value="">Select Warehouse</option>
                    <?php $defaultWarehouseId = (int)($default_warehouse_id ?? 0); ?>
                    <?php foreach ($warehouses as $warehouse): ?>
                        <option value="<?= $warehouse['id'] ?>" <?= ((int)$warehouse['id'] === $defaultWarehouseId) ? 'selected' : '' ?>><?= htmlspecialchars($warehouse['address_title']) ?></option>
                    <?php endforeach; ?>
                </select>            
            </div>
            <div class="w-[320px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">Image <span class="text-red-500"></span></label>
                <input type="file" name="grn_file[]" multiple class="w-full px-4 py-3 rounded-full border border-gray-200 bg-gray-50 text-gray-800 text-sm focus:outline-none">
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-6 justify-center w-full">
            <div class="w-full max-w-[640px]">
                <label class="block text-sm font-medium mb-2 font-inter" style="color: rgba(5, 19, 33, 1);">GRN Remarks</label>
                <textarea id="grnRemarks" name="grn_remarks" class="w-full p-3 rounded-xl border text-gray-600 text-sm focus:outline-none focus:ring-1 focus:ring-gray-400 resize-none" style="border-color: rgba(163, 163, 163, 1); height: 80px;" placeholder="Remarks for this GRN (optional)"><?= $grnRemarksValue ?></textarea>
                <p id="grnStatus" class="text-sm text-gray-600 mt-2"></p>
            </div>
        </div>

        <div class="flex flex-col md:flex-row gap-4 justify-center w-full pb-8">
            <input type="hidden" name="transfer_id" value="<?= (int)$transfer['id'] ?>">
            <input type="hidden" name="transfer_order_no" value="<?= htmlspecialchars($transfer['transfer_order_no'] ?? '') ?>">
            <?php if ($mode === 'edit'): ?>
                <button type="submit" id="saveChanges" class="w-[320px] bg-[#d9822b] text-white font-medium py-3 px-6 rounded-full shadow hover:bg-gray-800 transition-colors">
                    Save Changes
                </button>
            <?php else: ?>
                <button type="button" onclick="saveStockTransferGrn(event)" id="saveChanges" class="w-[320px] bg-[#d9822b] text-white font-medium py-3 px-6 rounded-full shadow hover:bg-gray-800 transition-colors">
                    Save & Submit
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($mode === 'edit'): ?>
        </form>
    <?php endif; ?>
</div>

<script>
function saveStockTransferGrn(event) {
    event.preventDefault();

    const receivedBy = document.querySelector('select[name="received_by"]').value;
    const warehouse = document.querySelector('select[name="warehouse_id"]').value;
    const qtyInputs = Array.from(document.querySelectorAll('input[name="qty_received[]"]'));

    if (!warehouse) {
        alert('Please select a warehouse.');
        return;
    }

    if (!receivedBy) {
        alert('Please select received by.');
        return;
    }

    if (qtyInputs.every(i => parseInt(i.value || 0) <= 0)) {
        alert('Please enter valid quantities.');
        return;
    }

    const transferId = document.querySelector('input[name="transfer_id"]').value;
    const receivedDate = document.querySelector('input[name="received_date"]').value;
    const remarks = document.getElementById('grnRemarks').value;

    const items = [];
    const itemIds = Array.from(document.querySelectorAll('input[name="item_id[]"]')).map(i => i.value);
    const skus = Array.from(document.querySelectorAll('input[name="sku[]"]')).map(i => i.value);
    const itemCodes = Array.from(document.querySelectorAll('input[name="item_code[]"]')).map(i => i.value);
    const transferQtys = Array.from(document.querySelectorAll('input[name="transfer_qty[]"]')).map(i => i.value);
    const receivedQtys = Array.from(document.querySelectorAll('input[name="qty_received[]"]')).map(i => i.value);
    const acceptables = Array.from(document.querySelectorAll('input[name="qty_acceptable[]"]')).map(i => i.checked ? 1 : 0);
    const itemRemarks = Array.from(document.querySelectorAll('textarea[name="item_remarks[]"]')).map(i => i.value);

    for (let i = 0; i < itemIds.length; i++) {
        items.push({
            transfer_item_id: parseInt(itemIds[i]) || 0,
            sku: skus[i] || '',
            item_code: itemCodes[i] || '',
            transfer_qty: parseInt(transferQtys[i]) || 0,
            received_qty: parseInt(receivedQtys[i]) || 0,
            acceptable: acceptables[i] || 0,
            remarks: itemRemarks[i] || ''
        });
    }

    const formData = new FormData();
    formData.append('transfer_id', parseInt(transferId));
    formData.append('received_by', parseInt(receivedBy));
    formData.append('warehouse_id', parseInt(warehouse));
    formData.append('received_date', receivedDate);
    formData.append('remarks', remarks);
    formData.append('items', JSON.stringify(items));

    const fileInput = document.querySelector('input[name="grn_file[]"]');
    if (fileInput && fileInput.files.length > 0) {
        for (const file of fileInput.files) {
            formData.append('grn_file[]', file);
        }
    }

    const statusEl = document.getElementById('grnStatus');
    statusEl.textContent = 'Saving...';

    fetch('?page=stock_transfer_grns&action=create_post', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            statusEl.classList.remove('text-red-600');
            statusEl.classList.add('text-green-600');
            statusEl.textContent = 'Saved successfully. Redirecting...';
            setTimeout(() => {
                window.location.href = '?page=products&action=stock_transfer';
            }, 1200);
        } else {
            statusEl.classList.remove('text-green-600');
            statusEl.classList.add('text-red-600');
            statusEl.textContent = res.message || 'Failed to save GRN.';
        }
    })
    .catch(err => {
        statusEl.classList.remove('text-green-600');
        statusEl.classList.add('text-red-600');
        statusEl.textContent = 'Error saving GRN.';
        console.error(err);
    });
}

function openImagePopup(src) {
    const popup = document.getElementById('imagePopup');
    const img = document.getElementById('popupImage');
    img.src = src;
    popup.classList.remove('hidden');
}

function closeImagePopup() {
    const popup = document.getElementById('imagePopup');
    popup.classList.add('hidden');
}
</script>
