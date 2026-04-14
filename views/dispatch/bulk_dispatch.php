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
                    <option value="CUSTOM">Custom Size</option>
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

<!-- Custom Box Size Modal -->
<div id="customBoxSizeModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-md bg-white shadow-lg border border-gray-300 mx-3 rounded">
        <div class="flex justify-between items-center px-4 py-3 border-b border-gray-200 bg-orange-500 text-white rounded-t">
            <h2 class="font-semibold text-sm">Enter Custom Box Dimensions</h2>
            <button type="button" data-close-custom-modal aria-label="Close" class="text-white text-xl leading-none px-2 hover:text-white/90">&times;</button>
        </div>

        <div class="px-4 py-4">
            <div class="space-y-3">
                <div>
                    <label for="modalCustomLength" class="block text-gray-700 font-medium text-sm mb-1">Length (inches)</label>
                    <input id="modalCustomLength" type="number" placeholder="22" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" step="0.5"/>
                </div>
                <div>
                    <label for="modalCustomWidth" class="block text-gray-700 font-medium text-sm mb-1">Width (inches)</label>
                    <input id="modalCustomWidth" type="number" placeholder="17" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" step="0.5"/>
                </div>
                <div>
                    <label for="modalCustomHeight" class="block text-gray-700 font-medium text-sm mb-1">Height (inches)</label>
                    <input id="modalCustomHeight" type="number" placeholder="5" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" step="0.5"/>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Enter dimensions in inches. All fields are required.</p>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 flex justify-end gap-2 bg-gray-50 rounded-b">
            <button type="button" data-close-custom-modal class="bg-gray-500 hover:bg-gray-600 text-white font-semibold px-4 py-2 rounded text-sm">
                Cancel
            </button>
            <button type="button" id="applyCustomBoxSizeBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm">
                Apply to All
            </button>
        </div>
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
                    const isExpress = false;
                    const isCOD = false;
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
                                    <span class="font-semibold text-gray-800">Box 1</span> <span class="text-xs text-green-500 express-badge hidden">EXPRESS</span> <span class="text-xs text-blue-500 cod-badge hidden">COD</span>
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
                                            <option value="CUSTOM" data-length="" data-width="" data-height="">Custom Size</option>
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
                        <div id="availableCourierCompanies" class="mt-2 sm:mt-3 border-t border-gray-200 pt-2 sm:pt-3">
                        </div>

                        <div class="mt-2 mb-4 flex flex-wrap items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm inline-flex items-center gap-2 add-box-btn">
                                    <span>+ Add Box</span>
                                </button>
                                <button type="button" class="bg-blue-500 hover:bg-blue-600 text-white font-semibold px-4 py-2 rounded text-sm list-couriers-btn">
                                    📋 List Couriers
                                </button>
                            </div>
                            <button type="button" class="remove-order-btn text-red-500 hover:text-red-700 text-sm font-semibold px-4 py-2 rounded">
                                🗑 Remove Order
                            </button>
                        </div>
                    `;
                    container.appendChild(newOrderDiv);
                    currentBox = newOrderDiv.querySelector('[data-order-number]');
                }

                rows.forEach(row => {
                    console.log('Processing row...');
                    const cb = row.querySelector('input[type="checkbox"]');
                    //data-is-express check
                    console.log(row.dataset.isExpress)
                    console.log('Checkbox found and checked?', cb && cb.checked);
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
                        const isExpress = row.dataset.isExpress || '';
                        
                        console.log('Item extracted:', { itemInfo, paymentType, orderNum });
                        console.log('COD check:', paymentType.toLowerCase().includes('cod'));
                        console.log('EXPRESS check:', itemInfo.toUpperCase().includes('EXPRESS'));

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
                        console.log('Item added, count:', added);
                        
                        // Update order_ids attribute on the current box with all item IDs
                        if (currentBox && itemId) {
                            const existingOrderIds = currentBox.getAttribute('order_ids') || '';
                            const orderIdsArray = existingOrderIds ? existingOrderIds.split(',').filter(id => id) : [];
                            if (!orderIdsArray.includes(itemId)) {
                                orderIdsArray.push(itemId);
                            }
                            currentBox.setAttribute('order_ids', orderIdsArray.join(','));
                        }
                        
                        // Track COD and Express flags
                        console.log('About to check flags, currentBox:', currentBox);
                        console.log('About to check flags, paymentType value:', paymentType, 'itemInfo value:', itemInfo);
                        
                        if (currentBox) {
                            console.log('currentBox exists, checking conditions...');
                            // Check if payment type is COD
                            const isCODFlag = paymentType.toLowerCase().includes('cod');
                            const isExpressFlag = isExpress;
                            console.log('COD flag condition result:', isCODFlag);
                            console.log('EXPRESS flag condition result:', isExpressFlag);
                            
                            if (isCODFlag) {
                                currentBox.setAttribute('data-is-cod', '1');
                                console.log('✓ Set data-is-cod=1');
                            } else {
                                console.log('✗ COD condition was false, not setting data-is-cod');
                            }
                            
                            // Check if item info contains Express keyword
                            if (isExpressFlag) {
                                currentBox.setAttribute('data-is-express', '1');
                                console.log('✓ Set data-is-express=1');
                            } else {
                                console.log('✗ EXPRESS condition was false, not setting data-is-express');
                            }
                            
                            console.log('CurrentBox attributes after flags:', {
                                'data-is-cod': currentBox.getAttribute('data-is-cod'),
                                'data-is-express': currentBox.getAttribute('data-is-express')
                            });
                        } else {
                            console.log('✗ currentBox is null/falsy');
                        }
                    }
                });
                if (added > 0) {
                    // Update box totals after adding items
                    if (currentBox) {
                        console.log('Before updateBadgeVisibility - currentBox attributes:', {
                            'data-is-cod': currentBox.getAttribute('data-is-cod'),
                            'data-is-express': currentBox.getAttribute('data-is-express')
                        });
                        console.log('Calling updateBoxTotals and fetchCouriersForBox');
                        updateBoxTotals(currentBox);
                        // Use setTimeout to ensure DOM is updated
                        setTimeout(() => {
                            console.log('setTimeout - about to call updateBadgeVisibility');
                            console.log('currentBox attributes in setTimeout:', {
                                'data-is-cod': currentBox.getAttribute('data-is-cod'),
                                'data-is-express': currentBox.getAttribute('data-is-express')
                            });
                            updateBadgeVisibility(currentBox);
                        }, 100);
                        // Fetch available couriers for this box
                        fetchCouriersForBox(currentBox);
                    }
                    selectAllCheckbox.checked = false;
                    closeModal();
                } else {
                    showAlert('Please select at least one item which is not already added in this order box.', 'warning');
                }
            });
        }

        // Helper function to update badge visibility based on data attributes
        function updateBadgeVisibility(boxElement) {
            if (!boxElement) {
                console.log('updateBadgeVisibility: boxElement is null');
                return;
            }
            
            console.log('updateBadgeVisibility - boxElement:', boxElement);
            
            // Check data attributes
            const isExpress = boxElement.getAttribute('data-is-express') === '1';
            const isCOD = boxElement.getAttribute('data-is-cod') === '1';
            
            console.log('updateBadgeVisibility - flags:', { isExpress, isCOD });
            
            // Find badges - search in the entire box element tree
            const expressBadge = boxElement.querySelector('.express-badge');
            const codBadge = boxElement.querySelector('.cod-badge');
            
            console.log('Badges found:', { expressBadge: !!expressBadge, codBadge: !!codBadge });
            console.log('Express badge element:', expressBadge);
            console.log('COD badge element:', codBadge);
            
            if (expressBadge) {
                if (isExpress) {
                    expressBadge.classList.remove('hidden');
                    expressBadge.style.display = 'inline-block';
                    console.log('EXPRESS badge shown');
                } else {
                    expressBadge.classList.add('hidden');
                    expressBadge.style.display = 'none';
                    console.log('EXPRESS badge hidden');
                }
            } else {
                console.log('EXPRESS badge not found in:', boxElement);
            }
            
            if (codBadge) {
                if (isCOD) {
                    codBadge.classList.remove('hidden');
                    codBadge.style.display = 'inline-block';
                    console.log('COD badge shown');
                } else {
                    codBadge.classList.add('hidden');
                    codBadge.style.display = 'none';
                    console.log('COD badge hidden');
                }
            } else {
                console.log('COD badge not found in:', boxElement);
            }
        }

        // Helper function to fetch couriers from Shiprocket API
        function fetchCouriersForBox(boxElement) {
            console.log('fetchCouriersForBox called with:', boxElement);
            if (!boxElement) {
                console.log('boxElement is null/undefined');
                return;
            }
            
            // Get box details
            const boxSizeSelect = boxElement.querySelector('.BoxSize');
            const weightInput = boxElement.querySelector('.weight-input');
            const orderNumber = boxElement.getAttribute('data-order-number');
            
            console.log('Box details found:', {
                boxSizeSelect: !!boxSizeSelect,
                weightInput: !!weightInput,
                orderNumber: orderNumber
            });
            
            if (!boxSizeSelect || !weightInput || !orderNumber) {
                console.log('Missing required box data for courier serviceability', {
                    boxSizeSelect: !!boxSizeSelect,
                    weightInput: !!weightInput,
                    orderNumber: !!orderNumber
                });
                return;
            }
            
            const selectedOption = boxSizeSelect.options[boxSizeSelect.selectedIndex];
            const length = parseFloat(selectedOption.getAttribute('data-length')) || 0;
            const breadth = parseFloat(selectedOption.getAttribute('data-width')) || 0;
            const height = parseFloat(selectedOption.getAttribute('data-height')) || 0;
            const weight = parseFloat(weightInput.value) || 0;
            
            console.log('Dimensions extracted:', { length, breadth, height, weight });
            
            // Only fetch if we have valid dimensions
            if (weight <= 0 || length <= 0 || breadth <= 0 || height <= 0) {
                console.log('Invalid dimensions for courier check', { weight, length, breadth, height });
                return;
            }
            
            // Show loading state in courier container
            const courierContainer = boxElement.closest('.px-4.pt-4.pb-2').querySelector('#availableCourierCompanies');
            console.log('Courier container found:', !!courierContainer);
            if (courierContainer) {
                courierContainer.innerHTML = `
                    <div class="courier-rates-panel rounded-xl border border-gray-200 bg-gradient-to-b from-slate-50 to-white shadow-sm overflow-hidden text-[13px]">
                        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 bg-white/80">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-500 text-white shadow-sm" aria-hidden="true">
                                <i class="fas fa-spinner fa-spin text-sm"></i>
                            </span>
                            <div>
                                <div class="font-semibold text-gray-900">Loading courier options</div>
                                <div class="text-[11px] text-gray-500 mt-0.5">Checking serviceability for this box…</div>
                            </div>
                        </div>
                    </div>`;
            }
            
            // Call the PHP endpoint
            const payload = {
                order_number: orderNumber,
                length: length,
                breadth: breadth,
                height: height,
                weight: weight,
                cod: boxElement.getAttribute('data-is-cod') === '1' ? 1 : 0,
                is_express: boxElement.getAttribute('data-is-express') === '1' ? 1 : 0
            };
            
            console.log('Sending payload to PHP endpoint:', payload);
            
            fetch('?page=dispatch&action=getCourierServiceability', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(payload)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Response data:', data);
                boxElement._lastCourierDebugInput = data?.debug?.input_before_filter || null;
                boxElement._lastCourierDebugOutput = data?.debug?.output_after_filter || null;
                if (data.success && data.couriers && data.couriers.length > 0) {
                    const n = data.couriers.length;
                    let courierHtml = `
                    <div class="courier-rates-panel rounded-xl border border-gray-200 bg-gradient-to-b from-slate-50 to-white shadow-sm overflow-hidden text-[13px]">
                        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between px-3 py-2.5 border-b border-gray-100 bg-white/90">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-orange-500 text-white shadow-sm" aria-hidden="true">
                                    <i class="fas fa-shipping-fast text-sm"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="font-semibold text-gray-900 leading-tight">Courier rates</div>
                                    <div class="text-[11px] text-gray-500 truncate">${n} option${n !== 1 ? 's' : ''} · best rating, then lowest price</div>
                                </div>
                                <span class="shrink-0 inline-flex items-center rounded-full bg-orange-100 px-2 py-0.5 text-[11px] font-semibold text-orange-800">${n}</span>
                            </div>
                            <p class="text-[11px] text-gray-400 sm:text-right">Scroll sideways to compare</p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 px-3 py-2 bg-gray-50/95 border-b border-gray-100">
                            <span class="text-[10px] font-semibold uppercase tracking-wide text-gray-400 mr-1 hidden sm:inline">Debug</span>
                            <button type="button" class="copy-filter-input-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <i class="fas fa-copy text-[10px] text-gray-500" aria-hidden="true"></i> Copy input (pre-filter)
                            </button>
                            <button type="button" class="toggle-filter-debug-btn inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-gray-700 shadow-sm hover:bg-gray-50 hover:border-gray-300 transition-colors">
                                <i class="fas fa-code text-[10px] text-gray-500" aria-hidden="true"></i> <span class="toggle-filter-debug-label">Show raw input / output</span>
                            </button>
                        </div>
                        <div class="filter-debug-panel hidden border-b border-gray-200 bg-slate-900 px-3 py-2">
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Input (before filter)</div>
                            <pre class="debug-input text-[11px] leading-relaxed whitespace-pre-wrap break-all max-h-48 overflow-auto rounded-md bg-slate-950/80 p-2 text-emerald-100/95 border border-slate-700 font-mono"></pre>
                            <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-3 mb-1">Output (after filter)</div>
                            <pre class="debug-output text-[11px] leading-relaxed whitespace-pre-wrap break-all max-h-48 overflow-auto rounded-md bg-slate-950/80 p-2 text-sky-100/95 border border-slate-700 font-mono"></pre>
                        </div>
                        <div class="px-2 sm:px-3 py-3">
                            <div class="flex flex-nowrap gap-3 justify-start items-stretch overflow-x-auto overflow-y-hidden w-full pb-1 scroll-smooth [scrollbar-width:thin]" style="-webkit-overflow-scrolling: touch;">`;
                    
                    data.couriers.forEach((courier, idx) => {
                        const rating = courier.rating ? (courier.rating + '/5') : 'N/A';
                        const price = courier.price ? ('₹ ' + parseFloat(courier.price).toFixed(2)) : 'N/A';
                        const etd = courier.etd || 'N/A';
                        const etdShort = (etd === 'N/A' || etd === '' || etd == null) ? '—' : String(etd);
                        courierHtml += `
                            <div class="group relative flex w-[13.5rem] sm:w-56 shrink-0 flex-col rounded-xl border border-gray-200 bg-white p-3 shadow-sm cursor-pointer transition-all duration-200 hover:border-orange-300 hover:shadow-md hover:bg-orange-50/30 focus-within:ring-2 focus-within:ring-orange-400 focus-within:ring-offset-2" data-courier-id="${courier.id}" role="button" tabindex="0">
                                ${idx === 0 ? '<span class="absolute right-2 top-2 rounded-full bg-orange-500 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">Top pick</span>' : ''}
                                <p class="pr-16 text-sm font-semibold leading-snug text-gray-900 line-clamp-2">${escapeHtml(courier.name)}</p>
                                <div class="mt-3">
                                    <div class="text-[10px] font-medium uppercase tracking-wide text-gray-400">Price</div>
                                    <div class="text-lg font-bold tabular-nums text-orange-600">${price}</div>
                                </div>
                                <div class="mt-3 flex flex-wrap gap-1.5">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                        <span class="text-slate-400">ETD</span> ${escapeHtml(etdShort)}
                                    </span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-medium text-amber-900 border border-amber-100">
                                        <i class="fas fa-star text-amber-500 text-[10px]" aria-hidden="true"></i> ${escapeHtml(rating)}
                                    </span>
                                </div>
                            </div>
                        `;
                    });
                    
                    courierHtml += `
                            </div>
                        </div>
                    </div>`;
                    if (courierContainer) {
                        courierContainer.innerHTML = courierHtml;
                        const inputPre = courierContainer.querySelector('.debug-input');
                        const outputPre = courierContainer.querySelector('.debug-output');
                        if (inputPre) {
                            inputPre.textContent = JSON.stringify(boxElement._lastCourierDebugInput || {}, null, 2);
                        }
                        if (outputPre) {
                            outputPre.textContent = JSON.stringify(boxElement._lastCourierDebugOutput || {}, null, 2);
                        }
                    }
                } else {
                    if (courierContainer) {
                        let emptyHtml = `
                        <div class="courier-rates-panel rounded-xl border border-amber-200 bg-gradient-to-b from-amber-50/80 to-white shadow-sm overflow-hidden text-[13px]">
                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between px-3 py-3 border-b border-amber-100 bg-white/70">
                                <div class="flex gap-3 min-w-0">
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700" aria-hidden="true">
                                        <i class="fas fa-inbox text-lg"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="font-semibold text-amber-950">No couriers for this route</div>
                                        <p class="text-[12px] text-amber-900/80 mt-1 leading-snug">Nothing passed the filters. Try another box size, weight, or payment type, or open debug to inspect the API payload.</p>
                                    </div>
                                </div>
                                <span class="shrink-0 self-start rounded-full bg-amber-200/80 px-2 py-0.5 text-[11px] font-semibold text-amber-950">0 options</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 px-3 py-2 bg-amber-50/50 border-b border-amber-100/80">
                                <button type="button" class="copy-filter-input-btn inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-amber-950 shadow-sm hover:bg-amber-50 transition-colors">
                                    <i class="fas fa-copy text-[10px]" aria-hidden="true"></i> Copy input (pre-filter)
                                </button>
                                <button type="button" class="toggle-filter-debug-btn inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-white px-2.5 py-1.5 text-[11px] font-medium text-amber-950 shadow-sm hover:bg-amber-50 transition-colors">
                                    <i class="fas fa-code text-[10px]" aria-hidden="true"></i> <span class="toggle-filter-debug-label">Show raw input / output</span>
                                </button>
                            </div>
                            <div class="filter-debug-panel hidden border-b border-gray-200 bg-slate-900 px-3 py-2">
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mb-1">Input (before filter)</div>
                                <pre class="debug-input text-[11px] leading-relaxed whitespace-pre-wrap break-all max-h-48 overflow-auto rounded-md bg-slate-950/80 p-2 text-emerald-100/95 border border-slate-700 font-mono"></pre>
                                <div class="text-[10px] font-semibold uppercase tracking-wider text-slate-400 mt-3 mb-1">Output (after filter)</div>
                                <pre class="debug-output text-[11px] leading-relaxed whitespace-pre-wrap break-all max-h-48 overflow-auto rounded-md bg-slate-950/80 p-2 text-sky-100/95 border border-slate-700 font-mono"></pre>
                            </div>
                        </div>`;
                        courierContainer.innerHTML = emptyHtml;
                        const inputPre = courierContainer.querySelector('.debug-input');
                        const outputPre = courierContainer.querySelector('.debug-output');
                        if (inputPre) {
                            inputPre.textContent = JSON.stringify(boxElement._lastCourierDebugInput || {}, null, 2);
                        }
                        if (outputPre) {
                            outputPre.textContent = JSON.stringify(boxElement._lastCourierDebugOutput || {}, null, 2);
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching couriers:', error);
                if (courierContainer) {
                    courierContainer.innerHTML = `
                        <div class="rounded-xl border border-red-200 bg-gradient-to-b from-red-50 to-white px-4 py-3 shadow-sm flex gap-3 items-start text-[13px]">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-red-100 text-red-600" aria-hidden="true">
                                <i class="fas fa-exclamation-triangle"></i>
                            </span>
                            <div>
                                <div class="font-semibold text-red-900">Could not load couriers</div>
                                <p class="text-[12px] text-red-800/90 mt-1">Network or server error. Check your connection and try again.</p>
                            </div>
                        </div>`;
                }
            });
        }

        // Debug actions in courier container
        document.addEventListener('click', async function (e) {
            const copyBtn = e.target.closest('.copy-filter-input-btn');
            if (copyBtn) {
                const box = copyBtn.closest('.px-4.pt-4.pb-2')?.querySelector('[data-order-number]');
                const inputBeforeFilter = box?._lastCourierDebugInput || null;
                if (!inputBeforeFilter) {
                    showAlert('No input-before-filter data available yet', 'warning');
                    return;
                }
                const raw = JSON.stringify(inputBeforeFilter, null, 2);
                try {
                    if (navigator.clipboard && window.isSecureContext) {
                        await navigator.clipboard.writeText(raw);
                    } else {
                        const temp = document.createElement('textarea');
                        temp.value = raw;
                        document.body.appendChild(temp);
                        temp.select();
                        document.execCommand('copy');
                        document.body.removeChild(temp);
                    }
                    showAlert('Input before filter copied', 'success');
                } catch (err) {
                    console.error('Copy failed:', err);
                    showAlert('Failed to copy input before filter', 'error');
                }
                return;
            }

            const toggleBtn = e.target.closest('.toggle-filter-debug-btn');
            if (toggleBtn) {
                const container = toggleBtn.closest('#availableCourierCompanies');
                const panel = container?.querySelector('.filter-debug-panel');
                if (!panel) return;
                panel.classList.toggle('hidden');
                const label = toggleBtn.querySelector('.toggle-filter-debug-label');
                const hidden = panel.classList.contains('hidden');
                if (label) {
                    label.textContent = hidden ? 'Show raw input / output' : 'Hide raw input / output';
                } else {
                    toggleBtn.textContent = hidden ? 'Show raw input / output' : 'Hide raw input / output';
                }
            }
        });
        
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
        if (e.target.matches('.list-couriers-btn') || e.target.closest('.list-couriers-btn')) {
            e.preventDefault();
            const orderContainer = e.target.closest('.px-4.pt-4.pb-2');
            if (!orderContainer) return;

            const boxes = orderContainer.querySelectorAll('[data-order-number]');
            if (!boxes.length) {
                showAlert('No boxes found for this order', 'warning');
                return;
            }

            boxes.forEach(box => fetchCouriersForBox(box));
            showAlert('Refreshing courier list...', 'success');
            return;
        }

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
                                    <span class="font-semibold text-gray-800">Box ${boxNumber}</span> <span class="text-xs text-green-500 express-badge hidden">EXPRESS</span> <span class="text-xs text-blue-500 cod-badge hidden">COD</span>
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
                                            <option value="CUSTOM" data-length="" data-width="" data-height="">Custom Size</option>
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
            
            // Fetch couriers for the newly added box
            const newBox = addBoxButton.previousElementSibling.querySelector('[data-order-number]');
            if (newBox) {
                fetchCouriersForBox(newBox);
            }
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

    // Handle custom box size modal
    const customBoxSizeModal = document.getElementById('customBoxSizeModal');
    const boxSizeSelect = document.getElementById('boxSizeApply');
    
    // Open modal when CUSTOM is selected
    if (boxSizeSelect) {
        boxSizeSelect.addEventListener('change', function() {
            if (this.value === 'CUSTOM') {
                // Clear previous values
                document.getElementById('modalCustomLength').value = '';
                document.getElementById('modalCustomWidth').value = '';
                document.getElementById('modalCustomHeight').value = '';
                // Show modal
                customBoxSizeModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }
        });
    }

    // Close custom modal button handlers
    const closeCustomModalBtns = document.querySelectorAll('[data-close-custom-modal]');
    closeCustomModalBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            customBoxSizeModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            // Reset box size select if modal was closed without applying
            boxSizeSelect.value = 'R-1';
        });
    });

    // Close modal when clicking backdrop
    customBoxSizeModal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
            boxSizeSelect.value = 'R-1';
        }
    });

    // Handle Apply to All button for custom box size (from modal)
    const applyCustomBoxSizeBtn = document.getElementById('applyCustomBoxSizeBtn');
    if (applyCustomBoxSizeBtn) {
        applyCustomBoxSizeBtn.addEventListener('click', function(e) {
            e.preventDefault();

            // Get custom dimensions from modal
            const customLength = parseFloat(document.getElementById('modalCustomLength').value);
            const customWidth = parseFloat(document.getElementById('modalCustomWidth').value);
            const customHeight = parseFloat(document.getElementById('modalCustomHeight').value);

            // Validate dimensions
            if (isNaN(customLength) || isNaN(customWidth) || isNaN(customHeight) || 
                customLength <= 0 || customWidth <= 0 || customHeight <= 0) {
                showAlert('Please enter valid dimensions for all fields', 'warning');
                return;
            }

            // Check if modal was opened from individual box select or main "Apply to All"
            if (window._targetBoxSelect) {
                // Single box - set custom size on this specific box
                window._targetBoxSelect.setAttribute('data-custom-length', customLength);
                window._targetBoxSelect.setAttribute('data-custom-width', customWidth);
                window._targetBoxSelect.setAttribute('data-custom-height', customHeight);
                window._targetBoxSelect = null;
                
                showAlert(`✓ Applied custom box size (${customLength}x${customWidth}x${customHeight}in)`, 'success');
            } else {
                // Apply to all boxes
                const container = document.getElementById('invDispatchesContainer');
                const boxSizeSelects = container.querySelectorAll('.BoxSize');

                if (boxSizeSelects.length === 0) {
                    showAlert('No boxes found to update', 'warning');
                    return;
                }

                // Update all box size selects with CUSTOM and store dimensions
                boxSizeSelects.forEach(select => {
                    select.value = 'CUSTOM';
                    select.setAttribute('data-custom-length', customLength);
                    select.setAttribute('data-custom-width', customWidth);
                    select.setAttribute('data-custom-height', customHeight);
                });

                showAlert(`✓ Applied custom box size (${customLength}x${customWidth}x${customHeight}in) to all ${boxSizeSelects.length} boxes`, 'success');
            }

            // Close modal
            customBoxSizeModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        });
    }

    // Handle Apply to All button for standard box size
    const boxSizeApplyBtn = document.getElementById('boxSizeApplyBtn');
    if (boxSizeApplyBtn) {
        boxSizeApplyBtn.addEventListener('click', function(e) {
            e.preventDefault();

            const selectedSize = boxSizeSelect.value;

            if (!selectedSize) {
                showAlert('Please select a box size', 'warning');
                return;
            }

            if (selectedSize === 'CUSTOM') {
                showAlert('Please use the custom size modal first', 'info');
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
                // Clear custom attributes if switching back to standard size
                select.removeAttribute('data-custom-length');
                select.removeAttribute('data-custom-width');
                select.removeAttribute('data-custom-height');
            });

            showAlert(`✓ Applied box size "${selectedSize}" to all ${boxSizeSelects.length} boxes`, 'success');
        });
    }

    // Handle individual box size changes (for CUSTOM size selection)
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('BoxSize') && e.target.value === 'CUSTOM') {
            // Show modal for custom size input
            document.getElementById('modalCustomLength').value = e.target.getAttribute('data-custom-length') || '';
            document.getElementById('modalCustomWidth').value = e.target.getAttribute('data-custom-width') || '';
            document.getElementById('modalCustomHeight').value = e.target.getAttribute('data-custom-height') || '';
            
            // Store reference to the selected box element for later use
            window._targetBoxSelect = e.target;
            
            customBoxSizeModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }
    });

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
    }
    
    // Add event listeners for box size change to fetch couriers
    document.addEventListener('change', function(e) {
        if (e.target.matches('.BoxSize')) {
            const boxElement = e.target.closest('[data-order-number]');
            if (boxElement) {
                // Debounce the API call
                if (boxElement._courierDebounce) {
                    clearTimeout(boxElement._courierDebounce);
                }
                boxElement._courierDebounce = setTimeout(() => {
                    fetchCouriersForBox(boxElement);
                }, 500);
            }
        }
    });
    
    // Add event listeners for weight change to fetch couriers
    document.addEventListener('change', function(e) {
        if (e.target.matches('.weight-input')) {
            const boxElement = e.target.closest('[data-order-number]');
            if (boxElement) {
                // Debounce the API call
                if (boxElement._courierDebounce) {
                    clearTimeout(boxElement._courierDebounce);
                }
                boxElement._courierDebounce = setTimeout(() => {
                    fetchCouriersForBox(boxElement);
                }, 1000);
            }
        }
    });
})();
</script>
