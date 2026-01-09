<div class="mx-auto space-y-6 mr-4">
    <h2 class="text-2xl font-bold my-4">Payments</h2>
    <!--Advance Search-->
    <div class="mt-6 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Search</h2>
            <svg id="accordion-icon" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content" class="accordion-content hidden">
            <!-- Responsive Grid container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">
                <form method="GET" onsubmit="submitSearchForm()" class="contents">
                <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-2 flex items-end gap-2">    
                    <div class="w-1/2">
                        <label for="payment-date_from" class="block text-sm font-medium text-gray-600 mb-1">Payment Date</label>
                        <input type="date" name="payment_date_from" id="payment-date_from" placeholder="Payment Date" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="payment-date-to" class="block text-sm font-medium text-gray-600 mb-1">To</label>
                        <input type="date" name="payment_date_to" id="payment-date-to" placeholder="To Date" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                 <!-- Min/Max Amount -->
                <div class="col-span-1 sm:col-span-2 md:col-span-1 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="po-amount-from" class="block text-sm font-medium text-gray-600 mb-1">Payment Amount From</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['amount_min'] ?? '') ?>" name="amount_min" id="po-amount-from" placeholder="Amount Min" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="po-amount-to" class="block text-sm font-medium text-gray-600 mb-1">Payment Amount To</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['amount_max'] ?? '') ?>" name="amount_max" id="po-amount-to" placeholder="Amount Max" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>                

                
                <!-- Order Number -->
                <div>
                    <label for="po-number" class="block text-sm font-medium text-gray-600 mb-1">PO Number</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['po_number'] ?? '') ?>" name="po_number" id="po-number" placeholder="PO Number" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>                
                
                 <!-- Status -->
                
                <div class="relative">
                    <label for="vendor-name" class="block text-sm font-medium text-gray-600 mb-1">Vendor Name</label>
                    <input
                        type="text"
                        id="vendor_autocomplete"
                        class="w-full px-2 py-2 text-xs border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Search vendor by name..."
                        autocomplete="off"
                        value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>"
                    >
                    <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo isset($_GET['vendor_id']) ? htmlspecialchars($_GET['vendor_id']) : ''; ?>">
                    <div id="vendor_suggestions" class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded-md shadow-lg max-h-48 overflow-auto " style="display:none; top:100%;"></div>
                
                </div>
                <div>
                    <label for="utr-number" class="block text-sm font-medium text-gray-600 mb-1">UTR Num</label>
                    <input type="text" name="utr_number" id="utr-number" placeholder="UTR Number" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <!-- Buttons -->
                <div class="col-span-1 sm:col-span-2 md:col-span-1 flex items-center gap-2">
                    <button type="submit" class="w-full bg-amber-600 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition duration-150">Search</button>
                    <button type="button" id="clear-button" onclick="clearFilters()" class="w-full bg-gray-800 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Clear</button>
                </div>
                </form>
            <!-- clear filter -->
             <script>
                function clearFilters() {
                    const url = new URL(window.location.href);
                    //alert(url.search);
                    url.search = ''; // Clear all query parameters
                    const page = 'page=orders&action=payment_list'; // Retain only page and action parameters
                    window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                }
            </script>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-md ">
        <div class="p-6 ">
            <div class="table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Payment Date</th>                        
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">PO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Invoice No.</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Invoice Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Vendor Name</th>                                               
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Invoice Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">UTR No.</th> 
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Paid Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Payment Type</th>
                                                
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200 text-xs font-medium">
                <?php foreach ($payments as $payment): ?>
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap"><?= date('d M Y', strtotime($payment['payment_date'])) ?></td>                    
                    <td class="px-6 py-4 whitespace-nowrap">
                        <a target="_blank" href="<?= base_url('?page=purchase_orders&action=view&po_id=' . $payment['po_id']) ?>" class="text-blue-600 hover:underline"><?= htmlspecialchars($payment['po_number'] ?? '') ?></a><br>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['invoice_no'] ?? '') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $payment['invoice_date'] ? date('d M Y', strtotime($payment['invoice_date'])) : '' ?></td>
                    <td class="px-6 py-4 whitespace-normal break-words" title="<?= htmlspecialchars($payment['vendor_phone'] ?? '') ?>  <?= htmlspecialchars($payment['vendor_city'] ?? '') ?>"><?= htmlspecialchars($payment['vendor_name'] ?? '') ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= $payment['invoice_grand_total'] ? '₹'.$payment['invoice_grand_total'] : '' ; ?></td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['bank_transaction_reference_no'] ?? '') ?></td>  
                    <td class="px-6 py-4 whitespace-nowrap">
                        ₹<?= number_format($payment['amount_paid'], 2) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($payment['payment_type'] ?? '') ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>
    <!-- Pagination -->
    <div class="mt-4 flex justify-center">
        <?php         
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page, default 20
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // Only allow specific values
        $total_orders = isset($data['total_payments']) ? (int)$data['total_payments'] : 0;
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
        
        // Prepare query string for pagination links
        $search_params = $_GET;
        unset($search_params['page_no'], $search_params['limit']);
        $query_string = http_build_query($search_params);
        $query_string = $query_string ? '&' . $query_string : '';

        // Calculate start/end slot for 10 pages
        $slot_size = 10;
        $start = max(1, $page - floor($slot_size / 2));
        $end = min($total_pages, $start + $slot_size - 1);
        if ($end - $start < $slot_size - 1) {
            $start = max(1, $end - $slot_size + 1);
        }
        ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-center">
                <div id="pagination-controls" class="flex items-center gap-4 text-sm text-gray-600">
                    <div>
                        <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($payments) ?></span> of <span class="font-medium"><?= $total_payments ?></span> Payment</p>
                    </div>
                    <?php            
                    //echo '****************************************  '.$query_string;
                    if ($total_pages > 1): ?>          
                    <!-- Prev Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=purchase_orders&action=list&page_no=<?= $page-$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            &laquo; Prev
                        </a>
                        <!-- Page Slots -->
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                            href="?page=purchase_orders&action=list&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <!-- Next Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=purchase_orders&action=list&page_no=<?= $page+$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                    <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                            onchange="location.href='?page=purchase_orders&action=list&page_no=1&limit=' + this.value;">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    
// Accordion functionality
document.addEventListener('DOMContentLoaded', function () {
    // Accordion functionality
    const accordionButton = document.getElementById('accordion-button');
    const accordionContent = document.getElementById('accordion-content');
    const accordionIcon = document.getElementById('accordion-icon');

    accordionButton.addEventListener('click', () => {
        const isExpanded = accordionButton.getAttribute('aria-expanded') === 'true';
        accordionButton.setAttribute('aria-expanded', !isExpanded);
        if (accordionContent.classList.contains('hidden')) {
            accordionContent.classList.remove('hidden');
            accordionIcon.classList.add('rotate-180');
        } else {
            accordionContent.classList.add('hidden');
            accordionIcon.classList.remove('rotate-180');
        }
    });

    // Tab functionality
    // const tabs = document.querySelectorAll('.tab');
    // tabs.forEach(tab => {
    //     tab.addEventListener('click', function (event) {
    //         event.preventDefault();
    //         tabs.forEach(t => t.classList.remove('tab-active'));
    //         this.classList.add('tab-active');
    //     });
    // });

    // po_from po_to validation and clear functionality
    const fromDateInput = document.getElementById('po-amount-from');
    const toDateInput = document.getElementById('po-amount-to');
    const clearButton = document.getElementById('clear-button');
    fromDateInput.addEventListener('change', () => {
        if (fromDateInput.value) {
            toDateInput.min = fromDateInput.value;
            if (toDateInput.value && toDateInput.value < fromDateInput.value) {
                toDateInput.value = fromDateInput.value;
            }
        } else {
            toDateInput.min = null;
        }
    });

    function clearFilters() {
        fromDateInput.value = '';
        toDateInput.value = '';
        toDateInput.min = null;
    }

    clearButton.addEventListener('click', clearFilters);

    //search form submit

});
function submitSearchForm() {    
    const form = document.getElementById('search-form') || document.querySelector('#accordion-content form') || document.querySelector('form[method="GET"]');
    if (!form) return false;
    
    function setHidden(name, value){
        let inp = form.querySelector('input[name="' + name + '"]');
        if (!inp) {
            inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = name;
            form.appendChild(inp);
        }
        inp.value = value;
    }

    // ensure page and action are present in submitted query
    setHidden('page', 'orders');
    setHidden('action', 'payment_list');

    form.submit();
    return false;
}

//vendor auto complete
document.getElementById('vendor_autocomplete').addEventListener('input', function() {
const query = this.value;
const suggestionsBox = document.getElementById('vendor_suggestions');
const vendorIdInput = document.getElementById('vendor_id');
// close suggestions if clicked outside
document.addEventListener('click', function(event) {
    if (!suggestionsBox.contains(event.target) && event.target !== document.getElementById('vendor_autocomplete')) {
        suggestionsBox.style.display = 'none';
    }
    if (event.target === document.getElementById('vendor_autocomplete')) {
        if (suggestionsBox.children.length > 0) {
            suggestionsBox.style.display = 'block';
        }
    }
});

if (query.length < 2) {
    suggestionsBox.style.display = 'none';
    return;
}

fetch('<?php echo base_url("?page=purchase_orders&action=vendor_search&query="); ?>' + encodeURIComponent(query))
    .then(response => response.json())
    .then(data => {            
        suggestionsBox.innerHTML = '';
        if (Array.isArray(data.data) && data.data.length > 0) {
            data.data.forEach(vendor => {
                const div = document.createElement('div');
                div.className = 'p-2 hover:bg-gray-200 cursor-pointer';
                div.textContent = vendor.vendor_name;
                div.addEventListener('click', function() {
                    document.getElementById('vendor_autocomplete').value = vendor.vendor_name;
                    vendorIdInput.value = vendor.id;
                    suggestionsBox.style.display = 'none';
                });
                suggestionsBox.appendChild(div);
            });
            suggestionsBox.style.display = 'block';
        } else {
            suggestionsBox.style.display = 'none';
        }
    });
});
</script>