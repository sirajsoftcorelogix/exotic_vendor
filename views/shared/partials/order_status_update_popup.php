<?php
$order_status_list = is_array($order_status_list ?? null) ? $order_status_list : [];
$staff_list = is_array($staff_list ?? null) ? $staff_list : [];
$showOrderVendorName = (bool)($showOrderVendorName ?? false);
?>
<div id="statusPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-[250] p-4" onclick="closeStatusPopup(event)">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-y-auto relative" onclick="event.stopPropagation();">
        <button type="button" onclick="closeStatusPopup()" class="absolute top-3 right-3 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <div class="grid grid-cols-1 md:grid-cols-[38%_62%] gap-0">
            <div class="p-6 border-b md:border-b-0 md:border-r border-gray-200">
                <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md border h-36 w-full max-w-[220px] object-cover mb-4">
                <p class="text-sm text-gray-600 space-y-1">
                    <strong>Order Number:</strong> <span id="status_order_number"></span><br>
                    <strong>Item Code:</strong> <span id="status_item_code"></span><br>
                    <?php if ($showOrderVendorName): ?>
                    <strong>Vendor Name:</strong> <span id="status_vendor_name"></span><br>
                    <?php endif; ?>
                    <span id="status_category"></span> / <span id="status_sub_category"></span><br>
                    <span id="status_item" class="font-bold"></span>
                </p>
            </div>
            <div class="p-6">
                <h2 class="text-2xl font-bold mb-4">Update Order</h2>
                <form id="statusForm" enctype="multipart/form-data" method="post" action="?page=orders&action=update_status">
                    <input type="hidden" name="status_order_id" id="status_order_id">
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="orderStatus" class="block text-gray-700 font-bold mb-2">Order Status</label>
                            <select id="orderStatus" name="orderStatus" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-- Order Status --</option>
                                <?php renderPartial('views/shared/partials/order_status_select_options.php', [
                                    'order_status_list' => $order_status_list,
                                ]); ?>
                            </select>
                            <input type="hidden" id="previousStatus" name="previousStatus" value="">
                        </div>
                        <div>
                            <label for="statusESD" class="block text-gray-700 font-bold mb-2">Ship By Date</label>
                            <input type="date" id="statusESD" name="esd" class="border border-gray-300 rounded px-2 py-1.5 w-full">
                            <input type="hidden" id="previousESD" name="previous_esd" value="">
                        </div>
                    </div>
                    <div class="mb-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="agentId" class="block text-gray-700 font-bold mb-2">Assign agent</label>
                            <select name="agent_id" id="agentId" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">Select User</option>
                                <?php foreach ($staff_list as $id => $name): ?>
                                    <option value="<?= (int)$id ?>"><?= htmlspecialchars((string)$name) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="agentName" name="agent_name" value="">
                            <input type="hidden" id="previousAgent" name="previous_agent" value="">
                        </div>
                        <div>
                            <label for="orderPriority" class="block text-gray-700 font-bold mb-2">Priority</label>
                            <select id="orderPriority" name="orderPriority" class="border border-gray-300 rounded px-3 py-2 w-full">
                                <option value="">-Select-</option>
                                <option value="critical">Critical</option>
                                <option value="urgent">Urgent</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                            <input type="hidden" id="previousPriority" name="previous_priority" value="">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="orderRemarks" class="block text-gray-700 font-bold mb-2">Notes</label>
                        <textarea id="orderRemarks" name="orderRemarks" class="border border-gray-300 rounded px-3 py-2 w-full" rows="4"></textarea>
                        <input type="hidden" id="previousRemarks" name="previous_remarks" value="">
                    </div>
                    <p class="text-xs text-gray-500 mb-3">Saving updates the local status and syncs to Exotic India when supported for this status.</p>
                    <div id="orderStatusError" class="text-red-500 text-sm mt-1 hidden">Please select a status.</div>
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeStatusPopup()" class="px-4 py-2 bg-gray-800 text-white rounded hover:bg-gray-600">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-orange-600 text-white rounded hover:bg-orange-700">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
(function() {
    if (window.__orderStatusPopupInit) {
        return;
    }
    window.__orderStatusPopupInit = true;

    window.openStatusPopup = function(orderId) {
        const popup = document.getElementById('statusPopup');
        const orderEl = document.getElementById('order-id-' + orderId);
        if (!popup || !orderEl) {
            alert('Order data not found.');
            return;
        }

        let orderData;
        try {
            orderData = JSON.parse(orderEl.getAttribute('data-order') || '{}');
        } catch (err) {
            alert('Order data is invalid.');
            return;
        }

        document.getElementById('status_order_id').value = orderId;
        document.getElementById('orderRemarks').value = orderData.remarks || '';
        document.getElementById('orderStatus').value = orderData.status || '';
        document.getElementById('status_order_number').textContent = orderData.order_number || 'N/A';
        document.getElementById('status_item_code').textContent = orderData.item_code || 'N/A';
        <?php if ($showOrderVendorName): ?>
        document.getElementById('status_vendor_name').textContent = orderData.vendor_name || orderData.vendor || 'N/A';
        <?php endif; ?>
        document.getElementById('status_category').textContent = orderData.groupname || 'N/A';
        document.getElementById('status_sub_category').textContent = orderData.subcategories || 'N/A';
        document.getElementById('status_item').textContent = orderData.title || 'N/A';
        document.getElementById('orderPriority').value = orderData.priority || '';
        document.getElementById('previousStatus').value = orderData.status || '';
        document.getElementById('previousAgent').value = orderData.agent_id || '';
        document.getElementById('agentId').value = orderData.agent_id || '';
        document.getElementById('previousPriority').value = orderData.priority || '';
        document.getElementById('previousRemarks').value = orderData.remarks || '';
        document.getElementById('previousESD').value = orderData.esd || '';

        const statusESD = document.getElementById('statusESD');
        const raw = orderData.esd || '';
        if (statusESD) {
            const m = raw.match(/^(\d{4})-(\d{2})-(\d{2})/);
            statusESD.value = m ? m[0] : (raw || '');
        }

        const imgElem = document.querySelector('#statusPopup img');
        if (imgElem) {
            imgElem.src = orderData.image || 'https://placehold.co/100x80/e2e8f0/4a5568?text=Item';
        }

        const errorDiv = document.getElementById('orderStatusError');
        if (errorDiv) {
            errorDiv.textContent = '';
            errorDiv.classList.add('hidden');
        }

        popup.classList.remove('hidden');
    };

    window.closeStatusPopup = function(e) {
        if (e && e.target && e.currentTarget !== e.target) {
            return;
        }
        const popup = document.getElementById('statusPopup');
        if (popup) {
            popup.classList.add('hidden');
        }
    };

    document.getElementById('agentId')?.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        document.getElementById('agentName').value = selectedOption ? selectedOption.text : '';
    });

    document.getElementById('statusForm')?.addEventListener('submit', function(e) {
        const statusSelect = document.getElementById('orderStatus');
        const errorDiv = document.getElementById('orderStatusError');
        if (!statusSelect || statusSelect.value === '') {
            e.preventDefault();
            if (errorDiv) {
                errorDiv.textContent = 'Please select a status.';
                errorDiv.classList.remove('hidden');
            }
            return;
        }
        e.preventDefault();
        if (errorDiv) {
            errorDiv.classList.add('hidden');
        }

        const formData = new FormData(document.getElementById('statusForm'));
        fetch('index.php?page=orders&action=update_status', {
            method: 'POST',
            body: formData
        })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    window.closeStatusPopup();
                    window.location.reload();
                } else if (errorDiv) {
                    errorDiv.textContent = data.message || 'Error updating order status.';
                    errorDiv.classList.remove('hidden');
                }
            })
            .catch(function() {
                alert('An error occurred while updating order status.');
            });
    });
})();
</script>
