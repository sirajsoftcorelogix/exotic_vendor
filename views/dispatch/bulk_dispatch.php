<div class="max-w-7xl mx-auto bg-white shadow-md border border-gray-200 mt-6 rounded">

    <div class="border-b border-gray-200 px-6 py-3 flex items-center justify-between bg-white">
        <h2 class="text-lg font-semibold text-gray-800">Bulk Dispatch</h2>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2">
                <label for="orderNumber" class="text-gray-700 font-medium">Order Number:</label>
                <input id="orderNumber" type="text" class="border border-gray-300 rounded px-2 py-1 w-40 text-sm"/>
                <button id="addOrderBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-1.5 rounded text-sm">+ Add            </button>
            </div>
            <div class="flex items-center gap-2">
                <label for="weightApply" class="text-gray-700 font-medium">Weight (kg):</label>
                <input id="weightApply" type="text" class="border border-gray-300 rounded px-2 py-1 w-20 text-sm"/>
                <button id="weightApplyBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1 rounded text-sm">
                Apply to All
            </button>
            </div>
            <div class="flex items-center gap-2">
                <label for="boxSizeApply" class="text-gray-700 font-medium">Box Size</label>
                <select id="boxSizeApply" class="border border-gray-300 rounded px-2 py-1 text-sm w-28">
                    <!-- <option value="">Custom Size</option> -->
                    <option value="R-1" data-length="22" data-width="17" data-height="5">R-1 (22x17x5 inch)</option>
                    <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 inch)</option>
                    <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 inch)</option>
                    <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 inch)</option>
                    <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 inch)</option>
                    <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 inch)</option>
                    <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 inch)</option>
                    <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 inch)</option>
                    <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 inch)</option>
                    <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 inch)</option>
                    <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 inch)</option>
                    <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 inch)</option>
                    <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 inch)</option>
                    <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 inch)</option>
                </select>
            </div>
            <button id="boxSizeApplyBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1 rounded text-sm">
                Apply to All
            </button>
        </div>
    </div>
    <div id="invDispatchesContainer">
   
    </div>
    

    <div class="border-t border-gray-200 px-4 py-3 flex justify-end bg-white">
        <button id="bulkCreateInvoiceDispatchBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2 rounded text-sm inline-flex items-center gap-2">
            <span>🚚</span>
            <span>Invoice &amp; Dispatch</span>
        </button>
    </div>
</div>

<div id="selectItemsModal"
     class="fixed inset-0 z-50 hidden"
     aria-hidden="true">
    <div data-modal-backdrop class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-2xl max-h-[80vh] bg-white shadow-lg border border-gray-300 mx-3 sm:mx-6 rounded">
        <div class="flex justify-between items-center px-4 py-2 border-b border-gray-200 bg-orange-500 text-white rounded-t">
            <span class="font-semibold text-sm">Select Items for Dispatch</span>
            <button type="button" data-close-select-items aria-label="Close"
                    class="text-white text-xl leading-none px-2 hover:text-white/90">&times;</button>
        </div>

        <div class="px-4 py-3 text-xs text-gray-800 overflow-y-auto max-h-[60vh]">
            <div class="flex justify-between mb-2">
                <div>
                    <span class="font-semibold">Order No:</span>
                    <a href="#" class="text-blue-600 underline ml-1">2729831</a>
                </div>
                <div>
                    <span class="font-semibold">Customer:</span>
                    <a href="#" class="text-blue-600 underline ml-1">Sujan Reddy - 239482</a>
                </div>
            </div>

            <table class="w-full text-left border border-gray-200 text-xs">
                <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border-b border-gray-200 w-10">
                        <!-- <input type="checkbox"/> -->
                    </th>
                    <th class="p-2 border-b border-gray-200">Order</th>
                    <th class="p-2 border-b border-gray-200">Item</th>
                    <th class="p-2 border-b border-gray-200 text-right">Item Code</th>
                    <th class="p-2 border-b border-gray-200 text-right">Quantity</th>
                    <th class="p-2 border-b border-gray-200 text-right">Weight</th>
                    <th class="p-2 border-b border-gray-200 text-right">GST</th>
                    <th class="p-2 border-b border-gray-200 text-right">Item Total</th>
                    <th class="p-2 border-b border-gray-200 text-right">Payment Type</th>
                </tr>
                </thead>
                <tbody >
                <tr class="border-b border-gray-100">
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                    <td class="p-2 text-right">0.500 kg</td>
                    <td class="p-2 text-right">18%</td>
                    <td class="p-2 text-right">₹ 100</td>
                    <td class="p-2 text-right">Prepaid</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                    <td class="p-2 text-right">0.500 kg</td>
                    <td class="p-2 text-right">18%</td>
                    <td class="p-2 text-right">₹ 100</td>
                    <td class="p-2 text-right">Prepaid</td>
                </tr>
                <tr>
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                    <td class="p-2 text-right">0.500 kg</td>
                    <td class="p-2 text-right">18%</td>
                    <td class="p-2 text-right">₹ 100</td>
                    <td class="p-2 text-right">Prepaid</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-gray-200 flex justify-between items-center bg-gray-50 rounded-b">
            <div class="flex items-center gap-2 text-xs text-gray-700">
                <input id="selectAllModal" type="checkbox"/>
                <label for="selectAllModal" class="cursor-pointer">Select All</label>
            </div>
            <button id="addToInvoiceBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1.5 rounded text-sm">
                Add to Invoice
            </button>
        </div>
    </div>
</div>
<!--Dispatched list with status--->
<div id="dispatchListContainer" class="max-w-7xl mx-auto mt-6 bg-white shadow-md border border-gray-200 rounded hidden">
    <div class="border-b border-gray-200 px-6 py-3 flex items-center justify-between bg-white">
        <h2 class="text-lg font-semibold text-gray-800">Dispatch List</h2>
        <a href="<?php echo base_url('?page=dispatch&action=list'); ?>" class="text-blue-600 hover:text-blue-700 underline font-semibold">View All Dispatches</a>
    </div>
     <div class="flex flex-col md:flex-row justify-end items-center gap-3 mb-5 mt-4 px-4">
      <!--clear all check-->
      <button id="clear-selection-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md transition" onclick="localStorage.removeItem('selected_dispatch_invoices'); document.querySelectorAll('input.label-checkbox').forEach(cb => cb.checked = false);">
        Clear Selection
      </button>
      <button id="bulk-print-labels-btn" class="bg-orange-500 hover:bg-orange-600 text-white px-4 py-2 rounded-md transition">
        Print Label
      </button>
     </div>
    <div id="dispatchListContent" class="px-4 py-3 text-sm text-gray-700">
        <table class="w-full text-left border border-gray-200 text-xs">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-2 border-b border-gray-200 w-10">
                        <input type="checkbox" name="selectall" id="selectalldispatched"/>
                    </th>                   
                    <th class="p-2 border-b border-gray-200">Batch No.</th>
                    <th class="p-2 border-b border-gray-200 text-right">Order Number.</th>
                    <th class="p-2 border-b border-gray-200 text-right">Invoice No.</th>
                    <th class="p-2 border-b border-gray-200 text-right">Shipment ID</th>
                    <th class="p-2 border-b border-gray-200 text-right">AWB</th>
                    <!-- <th class="p-2 border-b border-gray-200 text-right">Action</th>                     -->
                </tr>
            </thead>
            <tbody id="dispatchListBody">
            <!-- Dispatch records will be injected here via AJAX -->
            </tbody>
        </table>        
    </div>

</div>
<script>
    (function () {
        const modal = document.getElementById('selectItemsModal');
        let currentBox = null; // element where items should be added
        let currentShippingAddress = ''; // Store shipping address from API response
        if (!modal) return;

        // Function to update box totals
        function updateBoxTotals(boxElement) {
            if (!boxElement) return;
            
            const itemRows = boxElement.querySelectorAll('.items-container > div.px-4.py-1');
            let skuCount = 0;
            let totalQuantity = 0;
            let totalWeight = 0;
            let orderCount = new Set(); // for unique orders
            let netTotal = 0;
            
            itemRows.forEach(row => {
                skuCount++;
                
                const cols = row.querySelectorAll('[class*="col-span"]');
                // Order is in col-span-2 (index 0)
                const orderNum = cols[0]?.textContent.trim() || '';
                if (orderNum) orderCount.add(orderNum);
                
                // Quantity is in col-span-1 (index 3)
                const quantityCol = cols[3];
                const quantity = parseInt(quantityCol?.textContent.trim() || '0') || 0;
                totalQuantity += quantity;
                
                // Weight is in col-span-1 (index 4)
                const weightCol = cols[4];
                const weightText = weightCol?.textContent.trim() || '0';
                const weight = parseFloat(weightText.replace(/[^0-9.]/g, '')) || 0;
                totalWeight += weight;
                //console.log('Row:', row, 'Order:', orderNum, 'Quantity:', quantity, 'Weight:', weight, 'totalWeight:', totalWeight);
                // Net Total is in col-span-1 (index 7)
                const netTotalCol = cols[7];
                const netTotalText = netTotalCol?.textContent.trim() || '0';
                const itemNetTotal = parseFloat(netTotalText.replace(/[^0-9.]/g, '')) || 0;
                netTotal += itemNetTotal;
            });
            
            // Update the summary display
            const summary = boxElement.querySelector('.px-4.py-3');
            if (summary) {
                //console.log('Updating summary:', { orderCount: orderCount.size, skuCount, totalQuantity, totalWeight });
                const orderSummary = boxElement.querySelector('.order-summary');
                const skuSummary = boxElement.querySelector('.sku-summary');
                const qtySummary = boxElement.querySelector('.qty-summary');
                const weightSummary = boxElement.querySelector('.weight-summary');
                const netTotalSummary = boxElement.querySelector('.net-total');
                if (orderSummary) orderSummary.innerHTML = '<span class="font-semibold">Order:</span> ' + (orderCount.size > 0 ? orderCount.size : '0');
                if (skuSummary) skuSummary.innerHTML = '<span class="font-semibold">SKU Count:</span> ' + skuCount;
                if (qtySummary) qtySummary.innerHTML = '<span class="font-semibold">Total Quantity:</span> ' + totalQuantity;
                if (weightSummary) weightSummary.innerHTML = '<span class="font-semibold">Total Weight:</span> ' + totalWeight.toFixed(3) + ' kg';
                if (netTotalSummary) netTotalSummary.innerHTML = '<span class="font-semibold">Net Total:</span> ₹ ' + netTotal.toFixed(2);
                //const spans = summary.querySelectorAll('span');
                //if (spans[0]) spans[0].innerHTML = '<span class="font-semibold">Order:</span>' + (orderCount.size > 0 ? orderCount.size : '0');
                //if (spans[1]) spans[1].innerHTML = '<span class="font-semibold">SKU:</span> ' + skuCount;                
                //if (spans[2]) spans[2].innerHTML = '<span class="font-semibold">Quantity:</span> ' + totalQuantity;
                //if (spans[3]) spans[3].innerHTML = '<span class="font-semibold">Weight:</span> ' + totalWeight.toFixed(3) + ' kg';
            }
        }

        // Function to show errors with retry option
        function showErrorsWithRetry(data, orders) {
            const errorModal = document.createElement('div');
            errorModal.className = 'fixed inset-0 z-50 flex items-center justify-center';
            errorModal.innerHTML = `
                <div class="absolute inset-0 bg-black/40"></div>
                <div class="relative z-10 w-full max-w-2xl bg-white shadow-lg border border-gray-300 mx-3 rounded">
                    <div class="flex justify-between items-center px-4 py-3 border-b border-gray-200 bg-red-500 text-white rounded-t">
                        <h2 class="font-semibold text-sm">⚠️ Shipment Creation Errors</h2>
                        <button type="button" class="text-white text-xl leading-none px-2 hover:text-white/90 close-error-modal">&times;</button>
                    </div>

                    <div class="px-4 py-3">
                        <div class="mb-3 text-sm text-gray-700">
                            <p><strong>Batch #${data.batch_no}</strong></p>
                            <p class="text-xs text-gray-600 mt-1">✓ ${data.invoices_created} invoices and ${data.dispatches_created} dispatch records created successfully.</p>
                            <p class="text-xs text-gray-600">✗ ${data.errors.length} Shiprocket shipments failed. Click "Retry" to try creating shipments again.</p>
                        </div>

                        <div class="bg-red-50 border border-red-200 rounded p-3 max-h-64 overflow-y-auto">
                            <div class="text-xs text-red-800">
                                ${data.errors.map(err => `<div class="mb-2 pb-2 border-b border-red-200 last:border-b-0">• ${escapeHtml(err)}</div>`).join('')}
                            </div>
                        </div>
                    </div>

                    <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b">
                        <button type="button" class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded text-sm close-error-modal">
                            Close
                        </button>
                        <button type="button" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-semibold px-4 py-2 rounded text-sm continue-without-shipments-btn" data-close-error-modal>
                            Continue without Shipments
                        </button>

                        <button type="button" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm retry-shipments-btn" data-batch-no="${data.batch_no}">
                            🔄 Retry Shipments
                        </button>
                    </div>
                </div>
            `;
            
            document.body.appendChild(errorModal);

            // Close modal handlers
            const closeButtons = errorModal.querySelectorAll('.close-error-modal');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    errorModal.remove();
                });
            });

            const backdrop = errorModal.querySelector('.absolute.inset-0');
            if (backdrop) {
                backdrop.addEventListener('click', () => {
                    errorModal.remove();
                });
            }

            // Retry button handler
            const retryBtn = errorModal.querySelector('.retry-shipments-btn');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    const batchNo = this.getAttribute('data-batch-no');
                    retryShipmentCreation(batchNo, errorModal);
                });
            }

            // Continue without shipments button handler
            const continueBtn = errorModal.querySelector('.continue-without-shipments-btn');
            if (continueBtn) {
                continueBtn.addEventListener('click', function() {
                    // Show the dispatch list container
                    // const dispatchListContainer = document.getElementById('dispatchListContainer');
                    // if (dispatchListContainer) {
                    //     dispatchListContainer.classList.remove('hidden');
                    // }
                    // Close the error modal
                    errorModal.remove();
                    // Display dispatches in the dispatch list table
                        const dispatchListBody = document.getElementById('dispatchListBody');
                        const dispatchListContainer = document.getElementById('dispatchListContainer');
                        
                        if (dispatchListBody && data.dispatches && data.dispatches.length > 0) {
                            // Clear previous data
                            dispatchListBody.innerHTML = '';
                            
                            // Populate dispatch rows
                            data.dispatches.forEach(dispatch => {
                                const row = document.createElement('tr');
                                row.className = 'border-b border-gray-100 hover:bg-gray-50';
                                row.innerHTML = `
                                    <td class="p-2 border-b border-gray-200 w-10">
                                        <input type="checkbox" class="label-checkbox dispatched-checkbox" value="${dispatch.dispatch_id || ''}"/>
                                    </td>
                                    <td class="p-2 border-b border-gray-200">${data.batch_no || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">${dispatch.order_number || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">
                                        <a href="?page=invoices&action=generate_pdf&invoice_id=${dispatch.invoice_id}" target="_blank" class="text-blue-600 hover:underline">
                                            ${dispatch.invoice.invoice_number || '-'}
                                        </a>
                                    </td>
                                    <td class="p-2 border-b border-gray-200 text-right">${dispatch.shiprocket_shipment_id || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">
                                        ${dispatch.awb_code ? `<a href="${dispatch.label_url || '#'}" target="_blank" class="text-blue-600 hover:underline">${dispatch.awb_code}</a>` : '-'}
                                    </td>
                                    
                                `;
                                dispatchListBody.appendChild(row);
                            });
                            
                            // Show the dispatch list container
                            if (dispatchListContainer) {
                                dispatchListContainer.classList.remove('hidden');
                            }
                        } else {
                            if (dispatchListBody) {
                                dispatchListBody.innerHTML = '<tr><td colspan="8" class="p-4 text-center text-gray-500">No dispatch records found.</td></tr>';
                            }
                        }
                });
            }
        }

        // Function to retry shipment creation
        function retryShipmentCreation(batchNo, errorModal) {
            const retryBtn = errorModal.querySelector('.retry-shipments-btn');
            const originalText = retryBtn.innerHTML;
            retryBtn.disabled = true;
            retryBtn.innerHTML = '<span>⏳</span><span>Retrying...</span>';

            fetch('?page=dispatch&action=retry_shipments', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ batch_no: batchNo })
            })
            .then(response => response.json())
            .then(data => {
                retryBtn.disabled = false;
                retryBtn.innerHTML = originalText;

                if (data.success) {
                    let message = `✓ Retry completed for Batch #${batchNo}`;
                    
                    if (data.successful_count > 0) {
                        message += `\n✓ ${data.successful_count} shipment(s) created successfully`;
                    }
                    
                    if (data.failed_count > 0) {
                        message += `\n✗ ${data.failed_count} shipment(s) still failed`;
                    }

                    if (data.failed_count === 0) {
                        // All retries successful
                        showAlert('✓ All shipments created successfully! Closing error window...', 'success');
                        setTimeout(() => {
                            errorModal.remove();
                        }, 2000);
                    } else {
                        // Partial success - update error list
                        showAlert(message, data.failed_count === 0 ? 'success' : 'warning');
                        
                        if (data.errors && data.errors.length > 0) {
                            // Update the error list and keep modal open
                            const errorList = errorModal.querySelector('.bg-red-50 .text-red-800');
                            if (errorList) {
                                errorList.innerHTML = data.errors.map(err => `<div class="mb-2 pb-2 border-b border-red-200 last:border-b-0">• ${escapeHtml(err)}</div>`).join('');
                            }
                            
                            // Update status message
                            const statusDiv = errorModal.querySelector('.text-gray-700');
                            if (statusDiv) {
                                statusDiv.innerHTML = `
                                    <p><strong>Batch #${batchNo}</strong></p>
                                    <p class="text-xs text-gray-600 mt-1">✓ Invoices and dispatch records created.</p>
                                    <p class="text-xs text-gray-600">✓ ${data.successful_count || 0} shipment(s) created on retry</p>
                                    <p class="text-xs text-red-600">✗ ${data.failed_count || 0} shipment(s) still failing</p>
                                `;
                            }
                        }
                    }
                } else {
                    showAlert('Error during retry: ' + (data.message || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                retryBtn.disabled = false;
                retryBtn.innerHTML = originalText;
                console.error('Retry error:', error);
                showAlert('Error during retry: ' + error.message, 'error');
            });
        }

        // Helper function to escape HTML in error messages
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        }

        const closeButtons = modal.querySelectorAll('[data-close-select-items]');
        const backdrop = modal.querySelector('[data-modal-backdrop]');

        function openModal() {
            modal.classList.remove('hidden');
            modal.classList.add('flex', 'items-center', 'justify-center');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal() {
            modal.classList.add('hidden');
            modal.classList.remove('flex', 'items-center', 'justify-center');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('overflow-hidden');
        }

        // Use event delegation for dynamically added buttons
        document.addEventListener('click', function(e) {
            if (e.target.matches('[data-open-select-items]')) {
                e.preventDefault();
                
                // Get the closest order box parent where items will be added
                const orderBox = e.target.closest('[data-order-number]');
                if (!orderBox) {
                    showAlert('Error: Order information not found');
                    return;
                }

                // remember which box opened the modal so we can return items to it
                currentBox = orderBox;

                const orderNumber = orderBox.getAttribute('data-order-number');
                const customerId = orderBox.getAttribute('data-customer-id');
                const customerName = orderBox.getAttribute('data-customer-name');

                // Fetch items for this order
                fetch('?page=orders&action=get_order_items_for_dispatch&order_number=' + encodeURIComponent(orderNumber))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Store shipping address from API response
                            currentShippingAddress = data.shipping_address || '';
                            
                            // Update modal header
                            const orderNoLink = modal.querySelector('.flex.justify-between div:first-child a');
                            const customerLink = modal.querySelector('.flex.justify-between div:last-child a');
                            
                            if (orderNoLink) orderNoLink.textContent = data.order_number;
                            if (customerLink) customerLink.textContent = customerName + ' - ' + customerId;

                            // Update modal body with items
                            const tbody = modal.querySelector('tbody');
                            if (tbody) {
                                tbody.innerHTML = data.items_html;
                            }

                            // Check all checkboxes on load
                            // const checkboxes = modal.querySelectorAll('tbody input[type="checkbox"]');
                            // checkboxes.forEach(cb => cb.checked = true);
                            // const selectAllCheckbox = modal.querySelector('#selectAllModal');
                            // if (selectAllCheckbox) selectAllCheckbox.checked = true;

                            // Open modal
                            openModal();
                        } else {
                            showAlert(data.message || 'Failed to fetch items');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showAlert('Error fetching items: ' + error.message);
                    });
            }
        });

        closeButtons.forEach(btn => btn.addEventListener('click', function(){ currentBox = null; closeModal(); }));
        if (backdrop) backdrop.addEventListener('click', function(){ currentBox = null; closeModal(); });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });

        // select all checkboxes
        const selectAllCheckbox = modal.querySelector('#selectAllModal');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checked = this.checked;
                modal.querySelectorAll('tbody input[type="checkbox"]').forEach(cb => cb.checked = checked);
            });
        }

        // add to invoice action
        const addToInvoiceBtn = modal.querySelector('#addToInvoiceBtn');
        if (addToInvoiceBtn) {
            addToInvoiceBtn.addEventListener('click', function() {
                const rows = modal.querySelectorAll('tbody tr');
                let added = 0;
                let orderNumber = '';
                let customerName = '';
                let customerId = '';

                // Get order info from modal header
                const orderNoLink = modal.querySelector('.flex.justify-between div:first-child a');
                const customerLink = modal.querySelector('.flex.justify-between div:last-child a');
                if (orderNoLink) orderNumber = orderNoLink.textContent.trim();
                if (customerLink) {
                    const customerText = customerLink.textContent.trim();
                    const parts = customerText.split(' - ');
                    customerName = parts[0] || '';
                    customerId = parts[1] || '';
                }

                // Validate order number before adding
                if (!orderNumber) {
                    showAlert('Order number is missing or invalid', 'error');
                    return;
                }

                // When adding a brand new order from the modal, prevent duplicates
                if (!currentBox) {
                    const existingOrderBox = document.querySelector('[data-order-number="' + orderNumber + '"]');
                    if (existingOrderBox) {
                        showAlert('This order is already added. Use "+ Item" to add more items.', 'warning');
                        return;
                    }
                }

                if (!currentBox) {
                    // Create new order structure
                    const container = document.getElementById('invDispatchesContainer');
                    const newOrderDiv = document.createElement('div');
                    newOrderDiv.className = 'px-4 pt-4 pb-2';
                    newOrderDiv.innerHTML = `
                        <div class="bg-orange-500 text-white px-4 py-2 flex flex-wrap justify-between items-center rounded-t">
                            <div class="font-semibold">
                                ${customerName} - ${customerId}
                            </div>
                            <div class="text-xs sm:text-sm">
                                <span class="font-semibold">Shipping to:</span>
                                ${currentShippingAddress || 'Address not available'}
                            </div>
                        </div>

                        <div class="border border-orange-400 border-t-0 rounded-b bg-white" data-order-number="${orderNumber}" data-customer-id="${customerId}" data-customer-name="${customerName}">
                            <div class="px-4 py-2 flex flex-wrap items-center justify-between bg-orange-50 border-b border-orange-200">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-400 text-white text-sm">
                                        📦
                                    </span>
                                    <span class="font-semibold text-gray-800">Box 1</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm">
                                    <div>
                                        <span class="font-semibold text-gray-700">Total Weight (kg):</span>
                                        <input type="text" name="weight" value="0.000"
                                               class="weight-input ml-1 border border-gray-300 rounded px-2 py-0.5 w-20 text-xs"/>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-700">Box Size:</span>
                                        <select class="BoxSize border border-gray-300 rounded px-2 py-0.5 text-xs w-28">
                                            <option value="R-1" data-length="22" data-width="17" data-height="5">R-1 (22x17x5 inch)</option>
                                            <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 inch)</option>
                                            <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 inch)</option>
                                            <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 inch)</option>
                                            <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 inch)</option>
                                            <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 inch)</option>
                                            <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 inch)</option>
                                            <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 inch)</option>
                                            <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 inch)</option>
                                            <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 inch)</option>
                                            <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 inch)</option>
                                            <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 inch)</option>
                                            <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 inch)</option>
                                            <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 inch)</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-open-select-items class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1 rounded">
                                            + Item
                                        </button>
                                        <button type="button" class="remove-box-btn text-red-500 hover:text-red-700 text-xs font-semibold px-3 py-1 rounded border border-red-300 bg-white">
                                            Remove Box
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-200">
                                <div class="grid grid-cols-12 gap-2 font-semibold">
                                    <div class="col-span-2">Order</div>
                                    <div class="col-span-2">Item</div>
                                    <div class="col-span-1 text-right">Item Code</div>
                                    <div class="col-span-1 text-right">Quantity</div>
                                    <div class="col-span-1 text-right">Weight</div>
                                    <div class="col-span-1 text-right">Box Size</div>
                                    <div class="col-span-1 text-right">GST</div>
                                    <div class="col-span-1 text-right">Item Total</div>
                                    <div class="col-span-1 text-right">Payment Type</div>
                                    <div class="col-span-1 text-center">Actions</div>
                                </div>
                            </div>

                            <div class="items-container"></div>

                            <div class="px-4 py-3 flex flex-wrap justify-between items-center text-xs bg-orange-50">
                                <div class="flex flex-wrap gap-4 text-gray-700 summary-info">
                                    <span class="order-summary"><span class="font-semibold">Order:-</span> 0</span>
                                    <span class="sku-summary"><span class="font-semibold">SKU Count:</span> 0</span>
                                    <span class="qty-summary"><span class="font-semibold">Total Quantity:</span> 0</span>
                                    <span class="weight-summary"><span class="font-semibold">Total Weight:-</span> 0.000 kg</span>
                                </div>
                                <div class="flex flex-wrap gap-4 text-gray-800">
                                    <span class="net-total"><span class="font-semibold">Net Total:</span> ₹ 0</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 mb-4 flex flex-wrap items-center justify-between">
                            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm inline-flex items-center gap-2 add-box-btn">
                                <span>+ Add Box</span>
                            </button>
                            <button type="button" class="remove-order-btn text-red-500 hover:text-red-700 text-sm font-semibold px-4 py-2 rounded">
                                🗑 Remove Order
                            </button>
                        </div>
                    `;
                    container.appendChild(newOrderDiv);
                    currentBox = newOrderDiv.querySelector('[data-order-number]');
                }

                rows.forEach(row => {
                    const cb = row.querySelector('input[type="checkbox"]');
                    if (cb && cb.checked) {
                        const cols = row.querySelectorAll('td');
                        const orderNum = cols[1]?.textContent.trim() || '';
                        const itemInfo = cols[2]?.textContent.trim() || '';
                        const itemCode = cols[3]?.textContent.trim() || '';
                        const quantity = cols[4]?.textContent.trim() || '';
                        const weight = cols[5]?.textContent.trim() || '';
                        const gst = cols[6]?.textContent.trim() || '';
                        const itemTotal = cols[7]?.textContent.trim() || '';
                        const paymentType = cols[8]?.textContent.trim() || '';
                        const groupname = row.dataset.groupname || '';
                        const itemId = row.dataset.itemId || '';

                        // Skip if this exact item is already added to the current box
                        if (currentBox && itemId) {
                            const duplicate = currentBox.querySelector('[data-item-id="' + itemId + '"]');
                            if (duplicate) {
                                showAlert('This item is already added in this order box.', 'warning');
                                return;
                            }
                        }
                        const itemRow = document.createElement('div');
                        itemRow.className = 'px-4 py-1 text-xs text-gray-700 border-b border-gray-100';
                        if (groupname) {
                            itemRow.setAttribute('data-groupname', groupname);
                        }
                        if (itemId) {
                            itemRow.setAttribute('data-item-id', itemId);
                        }
                        itemRow.innerHTML = `
                            <div class="grid grid-cols-12 gap-2 items-center">
                                <div class="col-span-2">${orderNum}</div>
                                <div class="col-span-2">${itemInfo}</div>
                                <div class="col-span-1 text-right">${itemCode}</div>
                                <div class="col-span-1 text-right">${quantity}</div>
                                <div class="col-span-1 text-right">${weight}</div>
                                <div class="col-span-1 text-right">-</div>
                                <div class="col-span-1 text-right">${gst}</div>
                                <div class="col-span-1 text-right">${itemTotal}</div>
                                <div class="col-span-1 text-right">${paymentType}</div>
                                <div class="col-span-1 flex justify-center gap-2 text-lg">
                                    <button class="text-gray-500 hover:text-gray-700 move-item-btn" title="Move">📦</button>
                                    <button class="remove-item-btn text-red-500 hover:text-red-700" title="Remove">🗑</button>
                                </div>
                            </div>
                        `;
                        const itemsContainer = currentBox.querySelector('.items-container');
                        if (itemsContainer) {
                            itemsContainer.appendChild(itemRow);
                        } else {
                            const summary = currentBox.querySelector('.px-4.py-3');
                            if (summary) summary.insertAdjacentElement('beforebegin', itemRow);
                        }
                        added++;
                        
                        // Update order_ids attribute on the current box with all item IDs
                        if (currentBox && itemId) {
                            const existingOrderIds = currentBox.getAttribute('order_ids') || '';
                            const orderIdsArray = existingOrderIds ? existingOrderIds.split(',').filter(id => id) : [];
                            if (!orderIdsArray.includes(itemId)) {
                                orderIdsArray.push(itemId);
                            }
                            currentBox.setAttribute('order_ids', orderIdsArray.join(','));
                        }
                    }
                });
                if (added > 0) {
                    // Update box totals after adding items
                    if (currentBox) {
                        updateBoxTotals(currentBox);
                    }
                    selectAllCheckbox.checked = false;
                    closeModal();
                } else {
                    showAlert('Please select at least one item which is not already added in this order box.', 'warning');
                }
            });
        }

        // Handle Add Order button
        document.getElementById('addOrderBtn').addEventListener('click', function() {
            const orderNumber = document.getElementById('orderNumber').value.trim();
            
            if (!orderNumber) {
                showAlert('Please enter an order number', 'warning');
                return;
            }

            // Fetch items for this order and open modal
            fetch('?page=orders&action=get_order_items_for_dispatch&order_number=' + encodeURIComponent(orderNumber))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Store shipping address from API response
                        currentShippingAddress = data.shipping_address || '';
                        
                        // Update modal header
                        const orderNoLink = modal.querySelector('.flex.justify-between div:first-child a');
                        const customerLink = modal.querySelector('.flex.justify-between div:last-child a');
                        
                        if (orderNoLink) orderNoLink.textContent = data.order_number;
                        if (customerLink) customerLink.textContent = data.customer_name + ' - ' + data.customer_id;

                        // Update modal body with items
                        const tbody = modal.querySelector('tbody');
                        if (tbody) {
                            tbody.innerHTML = data.items_html;
                        }

                        // Clear currentBox since we're adding a new order
                        currentBox = null;

                        // Open modal
                        openModal();
                        document.getElementById('orderNumber').value = '';
                    } else {
                        showAlert(data.message || 'Failed to fetch items', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Error fetching items: ' + error.message, 'error');
                });
        });

        // Allow Enter key to add order
        document.getElementById('orderNumber').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.getElementById('addOrderBtn').click();
            }
        });

    // Handle Add Box button for dynamically added orders
    document.addEventListener('click', function(e) {
        if (e.target.matches('.add-box-btn') || e.target.closest('.add-box-btn')) {
            e.preventDefault();
            
            // Find the order container
            const orderContainer = e.target.closest('.px-4.pt-4.pb-2');
            if (!orderContainer) return;
            
            // Get order data from the order box
            const orderBox = orderContainer.querySelector('[data-order-number]');
            if (!orderBox) return;
            
            const orderNumber = orderBox.getAttribute('data-order-number');
            const customerId = orderBox.getAttribute('data-customer-id');
            const customerName = orderBox.getAttribute('data-customer-name');
            
            // Count existing boxes in this order
            const existingBoxes = orderContainer.querySelectorAll('.border.border-orange-400');
            const boxNumber = existingBoxes.length + 1;
            
            // Create new box HTML
            const newBoxHtml = `
                <div class="border border-orange-400 border-t-0 rounded-b bg-white" data-order-number="${orderNumber}" data-customer-id="${customerId}" data-customer-name="${customerName}">
                            <div class="px-4 py-2 flex flex-wrap items-center justify-between bg-orange-50 border-b border-orange-200">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-400 text-white text-sm">
                                        📦
                                    </span>
                                    <span class="font-semibold text-gray-800">Box ${boxNumber}</span>
                                </div>
                                <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm">
                                    <div>
                                        <span class="font-semibold text-gray-700">Total Weight (kg):</span>
                                        <input type="text" name="weight" value="0.000"
                                               class="weight-input ml-1 border border-gray-300 rounded px-2 py-0.5 w-20 text-xs"/>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <span class="font-semibold text-gray-700">Box Size:</span>
                                        <select class="BoxSize border border-gray-300 rounded px-2 py-0.5 text-xs w-28">
                                            <option value="R-1" data-length="22" data-width="17" data-height="5">R-1 (22x17x5 inch)</option>
                                            <option value="R-2" data-length="16" data-width="13" data-height="13">R-2 (16x13x13 inch)</option>
                                            <option value="R-3" data-length="16" data-width="11" data-height="7">R-3 (16x11x7 inch)</option>
                                            <option value="R-4" data-length="13" data-width="10" data-height="7">R-4 (13x10x7 inch)</option>
                                            <option value="R-5" data-length="21" data-width="11" data-height="7">R-5 (21x11x7 inch)</option>
                                            <option value="R-6" data-length="11" data-width="10" data-height="8">R-6 (11x10x8 inch)</option>
                                            <option value="R-7" data-length="8" data-width="6" data-height="5">R-7 (8x6x5 inch)</option>
                                            <option value="R-8" data-length="12" data-width="12" data-height="1.5">R-8 (12x12x1.5 inch)</option>
                                            <option value="R-9" data-length="17" data-width="12" data-height="2">R-9 (17x12x2 inch)</option>
                                            <option value="R-10" data-length="12" data-width="9" data-height="2">R-10 (12x9x2 inch)</option>
                                            <option value="R-11" data-length="10" data-width="10" data-height="2">R-11 (10x10x2 inch)</option>
                                            <option value="R-12" data-length="13" data-width="9" data-height="5">R-12 (13x9x5 inch)</option>
                                            <option value="R-13" data-length="11" data-width="8" data-height="5">R-13 (11x8x5 inch)</option>
                                            <option value="R-14" data-length="14" data-width="12" data-height="10">R-14 (14x12x10 inch)</option>
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" data-open-select-items class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1 rounded">
                                            + Item
                                        </button>
                                        <button type="button" class="remove-box-btn text-red-500 hover:text-red-700 text-xs font-semibold px-3 py-1 rounded border border-red-300 bg-white">
                                            Remove Box
                                        </button>
                                    </div>
                                </div>
                            </div>

                    <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-200">
                        <div class="grid grid-cols-12 gap-2 font-semibold">
                            <div class="col-span-2">Order</div>
                            <div class="col-span-2">Item</div>
                            <div class="col-span-1 text-right">Item Code</div>
                            <div class="col-span-1 text-right">Quantity</div>
                            <div class="col-span-1 text-right">Weight</div>
                            <div class="col-span-1 text-right">Box Size</div>
                            <div class="col-span-1 text-right">GST</div>
                            <div class="col-span-1 text-right">Item Total</div>
                            <div class="col-span-1 text-right">Payment Type</div>
                            <div class="col-span-1 text-center">Actions</div>
                        </div>
                    </div>

                    <div class="items-container"></div>

                    <div class="px-4 py-3 flex flex-wrap justify-between items-center text-xs bg-orange-50">
                        <div class="flex flex-wrap gap-4 text-gray-700">
                            <span class="order-summary"><span class="font-semibold">Order:</span> 0</span>
                            <span class="sku-summary"><span class="font-semibold">SKU Count:</span> 0</span>
                            <span class="qty-summary"><span class="font-semibold">Total Quantity:</span> 0</span>
                            <span class="weight-summary"><span class="font-semibold">Total Weight:</span> 0.000 kg</span>
                        </div>
                        <div class="flex flex-wrap gap-4 text-gray-800">
                            <span class="net-total"><span class="font-semibold">Net Total:</span> ₹ 0</span>
                        </div>
                    </div>
                </div>
            `;
            
            // Insert the new box before the "Add Box" button
            const addBoxButton = orderContainer.querySelector('.add-box-btn').closest('.mt-2.mb-4');
            addBoxButton.insertAdjacentHTML('beforebegin', newBoxHtml);
        }
    });

    // handle order removal
    document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-order-btn') || e.target.closest('.remove-order-btn')) {
            e.preventDefault();
            const orderWrapper = e.target.closest('.px-4.pt-4.pb-2');
            if (orderWrapper) orderWrapper.remove();
            return;
        }

        if (e.target.matches('.remove-box-btn') || e.target.closest('.remove-box-btn')) {
            e.preventDefault();
            const box = e.target.closest('[data-order-number]');
            if (box) {
                box.remove();
            }
        }
    });

    // handle individual item removal and moving inside boxes
    document.addEventListener('click', function(e) {
        if (e.target.matches('.remove-item-btn') || e.target.closest('.remove-item-btn')) {
            e.preventDefault();
            // each item row has px-4 py-1 classes
            const itemRow = e.target.closest('.px-4.py-1');
            if (itemRow) {
                // Get the box element to update totals
                const boxElement = itemRow.closest('[data-order-number]');
                
                // Remove item ID from order_ids attribute
                if (boxElement) {
                    const itemId = itemRow.getAttribute('data-item-id');
                    if (itemId) {
                        const existingOrderIds = boxElement.getAttribute('order_ids') || '';
                        const orderIdsArray = existingOrderIds.split(',').filter(id => id && id !== itemId);
                        if (orderIdsArray.length > 0) {
                            boxElement.setAttribute('order_ids', orderIdsArray.join(','));
                        } else {
                            boxElement.removeAttribute('order_ids');
                        }
                    }
                }
                
                itemRow.remove();
                // Update totals after removing the item
                if (boxElement) {
                    updateBoxTotals(boxElement);
                }
            }
            return;
        }
        if (e.target.matches('.move-item-btn') || e.target.closest('.move-item-btn')) {
            e.preventDefault();
            const itemRow = e.target.closest('.px-4.py-1');
            if (!itemRow) return;
            // find the containing order section
            const orderSection = itemRow.closest('.px-4.pt-4.pb-2');
            if (!orderSection) return;
            const boxContainers = orderSection.querySelectorAll('[data-order-number]');
            if (boxContainers.length <= 1) {
                showAlert('No other box available to move to');
                return;
            }

            // Find current box
            const currentBoxElement = itemRow.closest('[data-order-number]');
            
            // Create dropdown menu
            const dropdown = document.createElement('div');
            dropdown.className = 'absolute bg-white border border-gray-300 rounded shadow-lg z-50 min-w-max';
            dropdown.style.position = 'fixed';
            const rect = e.target.getBoundingClientRect();
            dropdown.style.top = (rect.bottom + 5) + 'px';
            dropdown.style.left = rect.left + 'px';
            
            let hasOptions = false;
            boxContainers.forEach((box, index) => {
                if (box !== currentBoxElement) {
                    hasOptions = true;
                    const option = document.createElement('div');
                    option.className = 'px-4 py-2 cursor-pointer hover:bg-orange-100 text-sm';
                    option.textContent = 'Box ' + (index + 1);
                    option.addEventListener('click', function() {
                        const itemId = itemRow.getAttribute('data-item-id');
                        
                        // Remove item ID from source box order_ids
                        if (itemId && currentBoxElement) {
                            const sourceOrderIds = currentBoxElement.getAttribute('order_ids') || '';
                            const sourceIdsArray = sourceOrderIds.split(',').filter(id => id && id !== itemId);
                            if (sourceIdsArray.length > 0) {
                                currentBoxElement.setAttribute('order_ids', sourceIdsArray.join(','));
                            } else {
                                currentBoxElement.removeAttribute('order_ids');
                            }
                        }
                        
                        const targetItemsContainer = box.querySelector('.items-container');
                        if (targetItemsContainer) {
                            targetItemsContainer.appendChild(itemRow);
                        } else {
                            const summary = box.querySelector('.px-4.py-3');
                            if (summary) summary.insertAdjacentElement('beforebegin', itemRow);
                        }
                        
                        // Add item ID to destination box order_ids
                        if (itemId && box) {
                            const targetOrderIds = box.getAttribute('order_ids') || '';
                            const targetIdsArray = targetOrderIds ? targetOrderIds.split(',').filter(id => id) : [];
                            if (!targetIdsArray.includes(itemId)) {
                                targetIdsArray.push(itemId);
                            }
                            box.setAttribute('order_ids', targetIdsArray.join(','));
                        }
                        
                        // Update totals for source and destination boxes
                        updateBoxTotals(currentBoxElement);
                        updateBoxTotals(box);
                        document.body.removeChild(dropdown);
                    });
                    dropdown.appendChild(option);
                }
            });

            if (!hasOptions) {
                showAlert('No other box available to move to');
                return;
            }

            document.body.appendChild(dropdown);
            
            // Close dropdown on outside click
            const closeDropdown = function(evt) {
                if (!dropdown.contains(evt.target) && !e.target.contains(evt.target)) {
                    if (dropdown.parentNode) document.body.removeChild(dropdown);
                    document.removeEventListener('click', closeDropdown);
                }
            };
            setTimeout(() => document.addEventListener('click', closeDropdown), 10);
            return;
        }
    });

    // Handle bulk create invoices and dispatch
    const bulkCreateBtn = document.getElementById('bulkCreateInvoiceDispatchBtn');
    if (bulkCreateBtn) {
        bulkCreateBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const container = document.getElementById('invDispatchesContainer');
            const orderSections = container.querySelectorAll('.px-4.pt-4.pb-2');

            if (orderSections.length === 0) {
                showAlert('Please add at least one order', 'warning');
                return;
            }

            // Collect all orders data
            const orders = [];
            orderSections.forEach(orderSection => {
                const orderBox = orderSection.querySelector('[data-order-number]');
                if (!orderBox) return;

                const order_number = orderBox.getAttribute('data-order-number');
                const customer_id = orderBox.getAttribute('data-customer-id');
                const customer_name = orderBox.getAttribute('data-customer-name');

                // Collect all boxes for this order and track order_ids
                const boxes = [];
                const boxElements = orderSection.querySelectorAll('[data-order-number]');
                
                boxElements.forEach((boxElement, boxIndex) => {
                    const itemRows = boxElement.querySelectorAll('.items-container > div.px-4.py-1');
                    const items = [];
                    let totalWeight = 0;
                    let boxGroupname = '';

                    itemRows.forEach((row, idx) => {
                        const cols = row.querySelectorAll('[class*="col-span"]');
                        const itemInfo = cols[1]?.textContent.trim() || '';
                        const itemId = row.dataset.itemId || itemInfo; // fallback to info if no id
                        items.push(itemId);

                        if (idx === 0) {
                            boxGroupname = row.dataset.groupname || '';
                        }

                        // Extract weight (col-span-1 at index 3)
                        const weightCol = cols[3];
                        const weightText = weightCol?.textContent.trim() || '0';
                        const weight = parseFloat(weightText.replace(/[^0-9.]/g, '')) || 0;
                        totalWeight += weight;
                    });

                    if (items.length > 0) {
                        // Get weight input value
                        const weightInput = boxElement.querySelector('input[type="text"][value*="0."]');
                        const weight = weightInput ? parseFloat(weightInput.value) : totalWeight;
                        
                        // Get box size
                        const boxSizeSelect = boxElement.querySelector('select');
                        const box_size = boxSizeSelect ? boxSizeSelect.value : 'R1 - 7x4x1';

                        boxes.push({
                            weight: weight,
                            box_size: box_size,
                            items: items,
                            groupname: boxGroupname,
                            pickup_location: null
                        });
                    }
                });

                if (boxes.length > 0) {
                    // Collect order_ids from all boxes in this order
                    const allOrderIds = new Set();
                    boxElements.forEach(boxElement => {
                        const order_ids_attr = boxElement.getAttribute('order_ids') || '';
                        if (order_ids_attr) {
                            order_ids_attr.split(',').filter(id => id).forEach(id => allOrderIds.add(id));
                        }
                    });
                    
                    const order_ids = Array.from(allOrderIds);
                    
                    orders.push({
                        order_number: order_number,
                        customer_id: customer_id,
                        customer_name: customer_name,
                        order_ids: order_ids,  // Include order IDs from all boxes
                        boxes: boxes
                    });
                }
            });

            if (orders.length === 0) {
                showAlert('Please add items to at least one box', 'warning');
                return;
            }

            // Validate weights
            let hasInvalidWeight = false;
            orders.forEach(order => {
                order.boxes.forEach(box => {
                    if (box.weight > 20 || box.weight < 0) {
                        hasInvalidWeight = true;
                    }
                });
            });
            if (hasInvalidWeight) {
                showAlert('All box weights must be below 20 kg', 'warning');
                return;
            }

            // Show loading state
            bulkCreateBtn.disabled = true;
            bulkCreateBtn.innerHTML = '<span>⏳</span><span>Creating...</span>';

            // Create and show overlay lock screen
            const overlayId = 'bulkDispatchOverlay';
            let overlay = document.getElementById(overlayId);
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.id = overlayId;
                overlay.className = 'fixed inset-0 bg-black/60 z-[9999] flex items-center justify-center';
                overlay.innerHTML = `
                    <div class="bg-white rounded-lg shadow-2xl p-8 text-center max-w-md">
                        <div class="mb-4">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-100">
                                <div class="animate-spin">
                                    <svg class="w-8 h-8 text-orange-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>
                            </div>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800 mb-2">Processing Dispatch</h2>
                        <p class="text-gray-600 mb-4 text-sm">Please wait while we create your invoices and dispatch records...</p>
                        <div class="bg-red-50 border border-red-200 rounded-md p-3">
                            <p class="text-xs text-red-700 font-semibold">⚠️ Important</p>
                            <p class="text-xs text-red-600 mt-1">Do <strong>NOT</strong> click Back</p>
                            <p class="text-xs text-red-600">Do <strong>NOT</strong> Close this Window</p>
                            <p class="text-xs text-red-600 mt-2">Closing may cause incomplete data</p>
                        </div>
                    </div>
                `;
                overlay.style.pointerEvents = 'none';
                document.body.appendChild(overlay);
            }
            overlay.style.display = 'flex';

            // Send data to server
            fetch('?page=dispatch&action=bulk_create_invoices_dispatch', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ orders: orders })
            })
            .then(response => response.json())
            .then(data => {
                // Hide overlay
                overlay.style.display = 'none';
                
                bulkCreateBtn.disabled = false;
                bulkCreateBtn.innerHTML = '<span>🚚</span><span>Invoice &amp; Dispatch</span>';

                if (data.success) {
                    if (data.errors && data.errors.length > 0) {
                        // Show error modal with retry option
                        showErrorsWithRetry(data, orders);
                    } else {
                        showAlert(`✓ Successfully created ${data.invoices_created} invoices and ${data.dispatches_created} dispatch records with Batch #${data.batch_no}`, 'success');
                        
                        // Clear the container
                        container.innerHTML = '';
                        
                        // Display dispatches in the dispatch list table
                        const dispatchListBody = document.getElementById('dispatchListBody');
                        const dispatchListContainer = document.getElementById('dispatchListContainer');
                        
                        if (dispatchListBody && data.dispatches && data.dispatches.length > 0) {
                            // Clear previous data
                            dispatchListBody.innerHTML = '';
                            
                            // Populate dispatch rows
                            data.dispatches.forEach(dispatch => {
                                const row = document.createElement('tr');
                                row.className = 'border-b border-gray-100 hover:bg-gray-50';
                                row.innerHTML = `
                                    <td class="p-2 border-b border-gray-200 w-10">
                                        <input type="checkbox" class="label-checkbox dispatched-checkbox" value="${dispatch.dispatch_id || ''}"/>
                                    </td>
                                    <td class="p-2 border-b border-gray-200">${data.batch_no || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">${dispatch.order_number || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">
                                        <a href="?page=invoices&action=generate_pdf&invoice_id=${dispatch.invoice_id}" target="_blank" class="text-blue-600 hover:underline">
                                            ${dispatch.invoice.invoice_number || '-'}
                                        </a>
                                    </td>
                                    <td class="p-2 border-b border-gray-200 text-right">${dispatch.shiprocket_shipment_id || '-'}</td>
                                    <td class="p-2 border-b border-gray-200 text-right">
                                        ${dispatch.awb_code ? `<a href="${dispatch.label_url || '#'}" target="_blank" class="text-blue-600 hover:underline">${dispatch.awb_code}</a>` : '-'}
                                    </td>
                                    <td class="p-2 border-b border-gray-200 text-right">
                                        <button class="text-blue-600 hover:text-blue-800 text-sm font-semibold" onclick="alert('View dispatch: ${dispatch.dispatch_id}')">View</button>
                                    </td>
                                `;
                                dispatchListBody.appendChild(row);
                            });
                            
                            // Show the dispatch list container
                            if (dispatchListContainer) {
                                dispatchListContainer.classList.remove('hidden');
                            }
                        }

                        // Show summary
                        console.log('Created Invoices:', data.invoices);
                        console.log('Created Dispatches:', data.dispatches);
                    }
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to create invoices'), 'error');
                    if (data.errors) {
                        console.error('Errors:', data.errors);
                    }
                }
            })
            .catch(error => {
                // Hide overlay
                overlay.style.display = 'none';
                
                bulkCreateBtn.disabled = false;
                bulkCreateBtn.innerHTML = '<span>🚚</span><span>Invoice &amp; Dispatch</span>';
                console.error('Error:', error);
                showAlert('Error creating invoices: ' + error.message, 'error');
            });
        });
    }

    // Handle bulk print labels
    const bulkPrintLabelsBtn = document.getElementById('bulk-print-labels-btn');
    if (bulkPrintLabelsBtn) {
        bulkPrintLabelsBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Get selected checkboxes
            const selectedCheckboxes = document.querySelectorAll('input.label-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                showAlert('Please select at least one dispatch to print labels', 'warning');
                return;
            }

            // Collect dispatch IDs
            const dispatchIds = Array.from(selectedCheckboxes).map(cb => cb.value).filter(id => id);

            if (dispatchIds.length === 0) {
                showAlert('No valid dispatch IDs selected', 'warning');
                return;
            }

            // Show loading state
            bulkPrintLabelsBtn.disabled = true;
            bulkPrintLabelsBtn.innerHTML = '<span>⏳</span><span>Processing...</span>';

            // Send request to merge and get labels
            fetch('?page=dispatch&action=bulk_print_labels', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ dispatch_ids: dispatchIds })
            })
            .then(response => response.json())
            .then(data => {
                bulkPrintLabelsBtn.disabled = false;
                bulkPrintLabelsBtn.innerHTML = 'Print Label';

                if (data.success) {
                    if (data.label_url) {
                        // Open merged label in new window for printing
                        const printWindow = window.open(data.label_url, '_blank');
                        if (printWindow) {
                            // Wait a bit for the document to load, then trigger print
                            printWindow.onload = function() {
                                printWindow.print();
                            };
                            // Also trigger print after a short delay as fallback
                            setTimeout(() => {
                                if (!printWindow.closed) {
                                    printWindow.print();
                                }
                            }, 1000);
                        } else {
                            showAlert('Please allow popups to print labels', 'warning');
                        }
                    } else {
                        showAlert('No label URL received', 'warning');
                    }
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to generate labels'), 'error');
                }
            })
            .catch(error => {
                bulkPrintLabelsBtn.disabled = false;
                bulkPrintLabelsBtn.innerHTML = 'Print Label';
                console.error('Error:', error);
                showAlert('Error generating labels: ' + error.message, 'error');
            });
        });
    }
    //selectall checkbox handler
    const selectalldispatched = document.getElementById('selectalldispatched');
    if (selectalldispatched) {
        selectalldispatched.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.dispatched-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    }

    // Handle Apply to All button for box size
    const boxSizeApplyBtn = document.getElementById('boxSizeApplyBtn');
    if (boxSizeApplyBtn) {
        boxSizeApplyBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const boxSizeSelect = document.getElementById('boxSizeApply');
            const selectedSize = boxSizeSelect.value;

            if (!selectedSize) {
                showAlert('Please select a box size', 'warning');
                return;
            }

            // Get all box size selects in the container
            const container = document.getElementById('invDispatchesContainer');
            const boxSizeSelects = container.querySelectorAll('.BoxSize');

            if (boxSizeSelects.length === 0) {
                showAlert('No boxes found to update', 'warning');
                return;
            }

            // Update all box size selects
            boxSizeSelects.forEach(select => {
                select.value = selectedSize;
            });

            showAlert(`✓ Applied box size "${selectedSize}" to all ${boxSizeSelects.length} boxes`, 'success');
        });
    }

    // Handle Apply to All button for weight
    const weightApplyBtn = document.getElementById('weightApplyBtn');
    if (weightApplyBtn) {
        weightApplyBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const weightInput = document.getElementById('weightApply');
            const weight = weightInput.value.trim();

            if (!weight) {
                showAlert('Please enter a weight value', 'warning');
                return;
            }

            // Validate weight is a number
            if (isNaN(weight)) {
                showAlert('Please enter a valid weight number', 'warning');
                return;
            }

            const weightValue = parseFloat(weight);
            if (weightValue > 20) {
                showAlert('Weight cannot exceed 20 kg', 'warning');
                return;
            }
            if (weightValue < 0) {
                showAlert('Weight cannot be negative', 'warning');
                return;
            }

            // Get all weight inputs in the container
            const container = document.getElementById('invDispatchesContainer');
            const weightInputs = container.querySelectorAll('.weight-input');

            if (weightInputs.length === 0) {
                showAlert('No boxes found to update', 'warning');
                return;
            }

            // Update all weight inputs
            let updatedCount = 0;
            weightInputs.forEach(input => {
                if (input && input.getAttribute('value') !== undefined) {
                    input.value = parseFloat(weight).toFixed(3);
                    updatedCount++;
                }
            });

            showAlert(`✓ Applied weight ${weight} kg to ${updatedCount} boxes`, 'success');
        });
    }})();
</script>
