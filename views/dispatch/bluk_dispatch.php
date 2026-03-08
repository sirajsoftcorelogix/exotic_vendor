<div class="max-w-7xl mx-auto bg-white shadow-md border border-gray-200">

    <div class="border-b border-gray-200 px-6 py-3 bg-white">
        <h1 class="text-lg font-semibold text-gray-800 mb-3">Bulk Dispatch</h1>
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <label for="orderNumber" class="text-gray-700 font-medium">Order Number:</label>
                <input id="orderNumber" type="text" class="border border-gray-300 rounded px-2 py-1 w-40 text-sm"/>
                <button id="addOrderBtn" class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-1.5 rounded text-sm">+ Add            </button>
            </div>
            <div class="flex items-center gap-2">
                <label for="weight" class="text-gray-700 font-medium">Weight (kg):</label>
                <input id="weight" type="text" class="border border-gray-300 rounded px-2 py-1 w-20 text-sm"/>
                <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-1.5 rounded text-sm">
                Apply to All
            </button>
            </div>
            <div class="flex items-center gap-2">
                <label for="boxSize" class="text-gray-700 font-medium">Box Size</label>
                <select id="boxSize" class="border border-gray-300 rounded px-2 py-1 text-sm w-28">
                    <option>R1 - 7x4x1</option>
                </select>
            </div>
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1 rounded text-sm">
                Apply to All
            </button>
        </div>
    </div>

    <div class="px-4 pt-2 pb-4 border-t border-gray-200">
        <div class="bg-orange-500 text-white px-4 py-2 flex flex-wrap justify-between items-center rounded-t">
            <div class="font-semibold">
                Customer - 263920
            </div>
            <div class="text-xs sm:text-sm">
                <span class="font-semibold">Shipping to:</span>
                Sujan reddy, 1-100/12, near SBI kismatpur, maruti nagar, kismatpur, K.V.Rangareddy, Telangana, 500030, IN
            </div>
        </div>
        <div class="border border-orange-400 border-t-0 rounded-b bg-white">
            <div class="px-4 py-2 flex flex-wrap items-center justify-between bg-orange-50 border-b border-orange-200">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-orange-400 text-white text-sm">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M21 8.5v7a2 2 0 0 1-1.1 1.79l-7 3.5a2 2 0 0 1-1.8 0l-7-3.5A2 2 0 0 1 3 15.5v-7"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M3.3 8.2 12 12.5l8.7-4.3"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M12 12.5v9"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M21 8.5 12 4 3 8.5"
                                  stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span class="font-semibold text-gray-800">Box 1</span>
                </div>
                <div class="flex flex-wrap items-center gap-6 text-xs sm:text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">Total Weight (kg):</span>
                        <input type="text" value="0.500"
                               class="ml-1 border border-gray-300 rounded px-2 py-0.5 w-20 text-xs"/>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-gray-700">Box Size:</span>
                        <select class="border border-gray-300 rounded px-2 py-0.5 text-xs w-28">
                            <option>R1 - 7x4x1</option>
                        </select>
                    </div>
                    <button type="button" data-open-select-items class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold px-3 py-1 rounded">
                        + Item
                    </button>
                </div>
            </div>

            <div class="px-4 py-2 text-xs">
                <div class="space-y-1">
                    <div class="grid grid-cols-1 sm:grid-cols-12 gap-x-3 gap-y-1 items-center">
                        <span class="sm:col-span-2">Order: 2564719</span>
                        <span class="sm:col-span-2">Item: <span class="font-semibold">Book</span> | HZA99</span>
                        <span class="sm:col-span-1 sm:text-right">Qty: 1</span>
                        <span class="sm:col-span-1 sm:text-right">Wt: 0.11 kg</span>
                        <span class="sm:col-span-1 sm:text-right">7x4x1</span>
                        <span class="sm:col-span-1 sm:text-right">GST: 5%</span>
                        <span class="sm:col-span-2 sm:text-right">Item Total₹ 205</span>
                        <span class="sm:col-span-1 sm:text-right">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-semibold bg-red-100 text-red-700">
                                COD
                            </span>
                        </span>

                        <div class="sm:col-span-1 flex items-center sm:justify-end gap-2 pt-1 sm:pt-0">
                            <button type="button"
                                    class="inline-flex items-center rounded border border-gray-200 p-1.5 text-gray-700 hover:bg-gray-50"
                                    title="Package">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M21 8.5v7a2 2 0 0 1-1.1 1.79l-7 3.5a2 2 0 0 1-1.8 0l-7-3.5A2 2 0 0 1 3 15.5v-7"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M3.3 8.2 12 12.5l8.7-4.3"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M12 12.5v9"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M21 8.5 12 4 3 8.5"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>

                            <button type="button"
                                    class="inline-flex items-center rounded border border-red-200 p-1.5 text-red-600 hover:bg-red-50"
                                    title="Delete row">
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                    <path d="M4 7h16"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M10 11v6"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M14 11v6"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M6 7l1 14a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-14"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3"
                                          stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="mt-2 border-t border-gray-200 pt-2 flex flex-wrap justify-between text-xs bg-orange-50 -mx-4 px-4 pb-2">
                    <div class="flex flex-wrap gap-4 text-gray-700">
                        <span><span class="font-semibold">Order:</span> 1</span>
                        <span><span class="font-semibold">SKU Count:</span> 3</span>
                        <span><span class="font-semibold">Total Quantity:</span> 3</span>
                        <span><span class="font-semibold">Total Weight:</span> 0.11 kg</span>
                        <span><span class="font-semibold">Max:</span> 7x4x1</span>
                    </div>
                    <div class="flex flex-wrap items-center gap-4 text-gray-800">
                        <span class="inline-flex items-center gap-2 rounded px-2 py-0.5 bg-green-100 text-green-800">
                            <span class="text-[11px] font-bold">Net Total</span>
                            <span class="text-xs font-semibold">₹ 615</span>
                        </span>

                        <span class="inline-flex items-center gap-2">
                            <span class="inline-flex items-center rounded px-2 py-0.5 text-[11px] font-semibold bg-red-100 text-red-700">
                                COD Amount:₹ 205</span>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-2">
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm inline-flex items-center gap-2">
                <span>+ Add Box</span>
            </button>
        </div>
    </div>

    <div class="border-t border-gray-200 px-4 py-3 flex justify-end bg-white">
        <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-2 rounded text-sm inline-flex items-center gap-2">
            <span>🚚</span>
            <span>Invoice &amp; Dispatch</span>
        </button>
    </div>
</div>

<div id="selectItemsModal"
     class="fixed inset-0 z-50 hidden"
     aria-hidden="true">
    <div data-modal-backdrop class="absolute inset-0 bg-black/40"></div>
    <div class="relative z-10 w-full max-w-xl bg-white shadow-lg border border-gray-300 mx-3 sm:mx-6 rounded">
        <div class="flex justify-between items-center px-4 py-2 border-b border-gray-200 bg-orange-500 text-white rounded-t">
            <span class="font-semibold text-sm">Select Items for Dispatch</span>
            <button type="button" data-close-select-items aria-label="Close"
                    class="text-white text-xl leading-none px-2 hover:text-white/90">&times;</button>
        </div>

        <div class="px-4 py-3 text-xs text-gray-800">
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
                        <input type="checkbox"/>
                    </th>
                    <th class="p-2 border-b border-gray-200">Order</th>
                    <th class="p-2 border-b border-gray-200">Item</th>
                    <th class="p-2 border-b border-gray-200 text-right">Quantity</th>
                </tr>
                </thead>
                <tbody>
                <tr class="border-b border-gray-100">
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                </tr>
                <tr class="border-b border-gray-100">
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                </tr>
                <tr>
                    <td class="p-2">
                        <input type="checkbox"/>
                    </td>
                    <td class="p-2">2729831</td>
                    <td class="p-2">Book | HZA99</td>
                    <td class="p-2 text-right">1</td>
                </tr>
                </tbody>
            </table>
        </div>

        <div class="px-4 py-2 border-t border-gray-200 flex justify-between items-center bg-gray-50 rounded-b">
            <div class="flex items-center gap-2 text-xs text-gray-700">
                <input id="selectAllModal" type="checkbox"/>
                <label for="selectAllModal" class="cursor-pointer">Select All</label>
            </div>
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-1.5 rounded text-sm">
                Add to Dispatch
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const modal = document.getElementById('selectItemsModal');
        if (!modal) return;

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
                
                // Get the closest order box parent
                const orderBox = e.target.closest('[data-order-number]');
                if (!orderBox) {
                    showAlert('Error: Order information not found');
                    return;
                }

                const orderNumber = orderBox.getAttribute('data-order-number');
                const customerId = orderBox.getAttribute('data-customer-id');
                const customerName = orderBox.getAttribute('data-customer-name');

                // Fetch items for this order
                fetch('?page=orders&action=get_order_items_for_dispatch&order_number=' + encodeURIComponent(orderNumber))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
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

        closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
        if (backdrop) backdrop.addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();

    // Handle Add Order button
    document.getElementById('addOrderBtn').addEventListener('click', function() {
        const orderNumber = document.getElementById('orderNumber').value.trim();
        
        if (!orderNumber) {
            showAlert('Please enter an order number');
            return;
        }

        // Fetch order details
        fetch('?page=orders&action=get_order_details_for_dispatch&order_number=' + encodeURIComponent(orderNumber))
            .then(response => response.json())
            .then(data => {
                if (data.success && data.html) {
                    const container = document.getElementById('invDispatchesContainer');
                    const newOrderDiv = document.createElement('div');
                    newOrderDiv.className = 'px-4 pt-4 pb-2';
                    newOrderDiv.innerHTML = data.html;
                    container.appendChild(newOrderDiv);
                    document.getElementById('orderNumber').value = '';
                } else {
                    showAlert(data.message || 'Failed to fetch order details');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error fetching order: ' + error.message);
            });
    });

    // Allow Enter key to add order
    document.getElementById('orderNumber').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.getElementById('addOrderBtn').click();
        }
    });
</script>
