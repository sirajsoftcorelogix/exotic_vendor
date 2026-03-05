<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Dispatch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #f8f8f8; }
    </style>
</head>
<body class="min-h-screen flex justify-center py-6 text-sm font-sans">
<div class="w-full max-w-6xl bg-white shadow-md border border-gray-200">

    <div class="border-b border-gray-200 px-6 py-3 bg-white">
        <h1 class="text-lg font-semibold text-gray-800 mb-3">Bulk Dispatch</h1>
        <div class="flex items-center gap-3 flex-wrap">
            <div class="flex items-center gap-2">
                <label for="orderNumber" class="text-gray-700 font-medium">Order Number:</label>
                <input id="orderNumber" type="text" class="border border-gray-300 rounded px-2 py-1 w-40 text-sm"/>
                <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-6 py-1.5 rounded text-sm">+ Add            </button>
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

    <div class="px-4 pt-4 pb-2">
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
                        📦
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

            <div class="px-4 py-2 text-xs text-gray-500 border-b border-gray-200">
                <div class="grid grid-cols-12 gap-2 font-semibold">
                    <div class="col-span-2">Order</div>
                    <div class="col-span-3">Item</div>
                    <div class="col-span-1 text-right">Quantity</div>
                    <div class="col-span-1 text-right">Weight</div>
                    <div class="col-span-1 text-right">Box Size</div>
                    <div class="col-span-1 text-right">GST</div>
                    <div class="col-span-1 text-right">Item Total</div>
                    <div class="col-span-1 text-right">Payment Type</div>
                    <div class="col-span-1 text-center">Actions</div>
                </div>
            </div>

            <div class="px-4 py-1 text-xs text-gray-700 border-b border-gray-100">
                <div class="grid grid-cols-12 gap-2 items-center">
                    <div class="col-span-2">2564719</div>
                    <div class="col-span-3">
                        <span class="font-semibold">Book</span> | HZA99
                    </div>
                    <div class="col-span-1 text-right">1</div>
                    <div class="col-span-1 text-right">0.11 kg</div>
                    <div class="col-span-1 text-right">7x4x1</div>
                    <div class="col-span-1 text-right">5%</div>
                    <div class="col-span-1 text-right">₹ 205</div>
                    <div class="col-span-1 text-right">Prepaid</div>
                    <div class="col-span-1 flex justify-center gap-2 text-lg">
                        <button class="text-gray-500 hover:text-gray-700" title="Move">📦</button>
                        <button class="text-red-500 hover:text-red-700" title="Remove">🗑</button>
                    </div>
                </div>
            </div>

            <div class="px-4 py-3 flex flex-wrap justify-between items-center text-xs bg-orange-50">
                <div class="flex flex-wrap gap-4 text-gray-700">
                    <span><span class="font-semibold">Order:</span> 1</span>
                    <span><span class="font-semibold">SKU Count:</span> 3</span>
                    <span><span class="font-semibold">Total Quantity:</span> 3</span>
                    <span><span class="font-semibold">Total Weight:</span> 0.11 kg</span>
                    <span><span class="font-semibold">Max:</span> 7x4x1</span>
                </div>
                <div class="flex flex-wrap gap-4 text-gray-800">
                    <span><span class="font-semibold">Net Total:</span> ₹ 615</span>
                    <span><span class="font-semibold">COD Amount:</span> ₹ 205</span>
                </div>
            </div>
        </div>

        <div class="mt-2 mb-4">
            <button class="bg-orange-500 hover:bg-orange-600 text-white font-semibold px-4 py-2 rounded text-sm inline-flex items-center gap-2">
                <span>+ Add Box</span>
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
                        📦
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
                    <div class="flex justify-between">
                        <span>Order: 2564719</span>
                        <span>Item: <span class="font-semibold">Book</span> | HZA99</span>
                    </div>
                    <div class="flex flex-wrap justify-between gap-4 text-gray-700">
                        <span>Quantity: 1</span>
                        <span>Weight: 0.11 kg</span>
                        <span>Box Size: 7x4x1</span>
                        <span>GST: 5%</span>
                        <span>Item Total: 205</span>
                        <span>Payment Type: COD</span>
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
                    <div class="flex flex-wrap gap-4 text-gray-800">
                        <span><span class="font-semibold">Net Total:</span> ₹ 615</span>
                        <span><span class="font-semibold">COD Amount:</span> ₹ 205</span>
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

        const openButtons = document.querySelectorAll('[data-open-select-items]');
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

        openButtons.forEach(btn => btn.addEventListener('click', openModal));
        closeButtons.forEach(btn => btn.addEventListener('click', closeModal));
        if (backdrop) backdrop.addEventListener('click', closeModal);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
        });
    })();
</script>
</body>
</html>
