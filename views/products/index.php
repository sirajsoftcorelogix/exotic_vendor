
<div class="container mx-auto p-4">
    <!-- Top Bar: Search & Advance Search -->
   <?php
		
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50; // Orders per page, default 50
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 50; // Only allow specific values
        $total_orders = isset($data['total_records']) ? (int)$data['total_records'] : 0;
        $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;

        // Prepare query string for pagination links
        $search_params = $_GET;
        unset($search_params['page_no'], $search_params['limit'], $search_params['sort']);
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
    <!-- Advance Search Accordion -->
    <div class="mt-6 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button-search" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Advance Search</h2>
            <svg id="accordion-icon-search" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content-search" class="accordion-content hidden overflow-visible">
            <!-- Responsive Grid container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">
                <form method="GET" id="productsSearchForm" action="<?= base_url('?page=products&action=list') ?>" class="contents">
                <!-- products From/Till -->
               

                <!-- Item Code -->
                <div>
                    <label for="item-code" class="block text-sm font-medium text-gray-600 mb-1">Item Code</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['item_code'] ?? '') ?>" name="item_code" id="item-code" placeholder="Item Code" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Item Name -->
                <div>
                    <label for="item-name" class="block text-sm font-medium text-gray-600 mb-1">Item Name</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['item_name'] ?? '') ?>" name="item_name" id="item-name" placeholder="Item Name" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div class="relative">
                    <label for="vendor_autocomplete" class="block text-sm font-medium text-gray-600 mb-1">Vendor</label>
                    <input
                        type="text"
                        id="vendor_autocomplete"
                        class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500"
                        placeholder="Search vendor by name..."
                        autocomplete="off"
                        name="vendor_name"
                        value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>"
                    >
                    <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo isset($_GET['vendor_id']) ? htmlspecialchars($_GET['vendor_id']) : ''; ?>">
                    <div id="vendor_suggestions" class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded-md shadow-lg max-h-48 overflow-auto " style="display:none; top:100%;"></div>
                </div>
                <!-- <div>
                    <label for="agent" class="block text-sm font-medium text-gray-600 mb-1">Agent</label>
                    <select id="agent" name="agent" class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="" selected>-Select-</option>
                        <?php //foreach ($staff_list as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_GET['agent']) && $_GET['agent'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php //endforeach; ?>                    
                    </select>
                </div> -->
                
                
                <!-- Buttons -->
                <div class="col-span-1 sm:col-span-1 md:col-span-1 flex items-center gap-2">
                    <button type="button" onclick="cancelSearch()" class="w-full bg-gray-600 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Cancel</button>
                    <!-- <button type="button" id="clear-button" onclick="clearFilters()" class="w-full bg-gray-800 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Clear</button> -->
                    <button type="submit" class="w-full bg-amber-600 text-white font-semibold py-2 px-2 rounded-md shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition duration-150">Search</button>
                </div>
                </form>
            <!-- clear filter -->
             <script>
                // function clearFilters() {
                //     const url = new URL(window.location.href);
                //     //alert(url.search);
                //     url.search = ''; // Clear all query parameters
                //     const page = 'page=orders&action=list';
                //     window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                // }
                function cancelSearch() {
                    const url = new URL(window.location.href);
                    url.search = ''; // Clear all query parameters
                    const page = 'page=products&action=list';
                    window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                }
            </script>
            </div>
        </div>
    </div>
    <!-- products Table Section -->
    <div class="mt-4">
        <!-- Tabs -->
        <div class="relative border-b-[4px] border-white mb-6">
            <div id="tabsContainer" class="flex space-x-8 overflow-x-auto pb-1">
                <a href="#" class="tab tab-active text-center relative py-4 flex-shrink-0">
                    <span class="px-1 text-base">All SKUs</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4 flex-shrink-0">
                    <span class="px-1 text-base">Restock</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
            </div>
            <div class="absolute right-0 top-0 flex items-center space-x-4">
            <button id="importProductsBtn" title="Import products" class="flex right-0 top-0 bg-amber-600 text-white font-semibold py-2 px-4 rounded-md shadow-md hover:bg-amber-700 transition">
                Import             
            </button>
            <button id="bulkUpdateBtn" title="Update stock" class="flex right-0 top-0 bg-amber-600 text-white font-semibold py-2 px-4 rounded-md shadow-md hover:bg-amber-700 transition">
                Update
            </button>
            <select id="rows-per-page" class="text-sm right-0 pagination-select px-1 py-3 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white"
                onchange="location.href='?page=products&page_no=1&limit=' + this.value + '<?= $query_string ?>';">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?> Products per page
                    </option>
                <?php endforeach; ?>
            </select>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-spacing">
                <thead class="bg-gray-50 rounded-md">
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="p-4 w-12"><input type="checkbox" id="selectAll" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"></th>
                    <th class="px-6 py-3">SKU Details</th>
                    <th class="px-6 py-3">Product Details</th>
                    <th class="px-6 py-3">S & F Sum</th>
                    <th class="px-6 py-3">Inventory Overview</th>
                    <th class="px-6 py-3">Price</th>
                    <th class="px-6 py-3 text-right">Recommended Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if (!empty($data['products'])): ?>
                <?php foreach ($data['products'] as $product): ?>
                    <!-- Table Row 1 -->
                <tr class="bg-white rounded-md shadow-sm" data-product='<?=  htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                    <!-- Checkbox -->
                    <td class="p-4 whitespace-nowrap rounded-l-md align-top pt-6">
                        <input type="checkbox" name="product_select[]" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" value="<?php echo $product['item_code']; ?>">
                    
                    </td>

                    <!-- SKU Details -->
                    <td class="px-6 py-4 whitespace-nowrap rounded-l-md align-top">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">Item Code :</span>
                                <span class="typo-sku-value"><a href="javascript:void(0);" class="invdetails typo-sf-column text-details-link mt-1"><?php echo $product['item_code']; ?></a></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">SKU :</span>
                                <span class="typo-sku-value"><?php echo $product['sku']; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">ASIN :</span>
                                <span class="typo-sku-value"><?php echo $product['asin']; ?></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">Numsold :</span>
                                <span class="typo-sku-value"><?php echo $product['numsold']; ?></span>
                            </div>
                        </div>
                    </td>

                    <!-- Product Details -->
                    <td class="px-6 py-4 align-top">
                        <div class="flex items-start space-x-4">
                            <img class="w-16 rounded-md object-cover flex-shrink-0 cursor-pointer" onclick="openImagePopup('<?php echo $product['image']; ?>')" src="<?php echo $product['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image';?>" alt="Product Image">
                            <div class="flex flex-col justify-between h-full">
                                <p class="typo-product-title mb-2 max-w-xs"><?php echo $product['title']; ?></p>
                                <p class="typo-product-title mb-2 max-w-xs">
                                     <?php echo $product['size'] ? '<strong>Size :</strong>'.$product['size'] : ''; ?> 
                                     <?php echo $product['color'] ? ' <strong>Color :</strong>'.ucfirst($product['color']) : ''; ?>
                                </p>
                                <div class="flex items-center mt-auto">
                                    
                                    <span class="typo-vendor">Vendor : <?php echo $product['vendor']; ?></span>
                                    <!-- <a href="#" class="ml-2 text-details-link hover:text-amber-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a> -->
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- S & F Sum -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <div class="flex flex-col space-y-1">
                            <span class="typo-sf-column">₹<?php echo $product['itemprice']; ?></span>
                            <span class="typo-sf-column">CP:₹<?php echo $product['cost_price']; ?></span>
                            <!-- <a href="javascript:void(0);" class="sfdetails typo-sf-column text-details-link mt-1">details</a> -->
                        </div>
                    </td>

                    <!-- Inventory Overview -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <div class="flex flex-col space-y-1">
                            <span class="typo-sf-column">Local Stock : <?php echo $product['local_stock']; ?></span>
                            <div class="typo-sf-column">
                                FBA (US): <span class="text-healthy"><?php echo $product['fba_us']; ?></span>
                            </div>
                            <!-- <a href="javascript:void(0);" class="invdetails typo-sf-column text-details-link mt-1">details</a> -->
                        </div>
                    </td>

                    <!-- Price -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <span class="typo-sf-column"><?php echo $product['price'] ? '$'. $product['price'] : ''; ?> <br><?php echo $product['price_india'] ? '₹'. $product['price_india'] : ''; ?></span>
                    </td>

                    <!-- Recommended Action -->
                    <td class="px-6 py-4 whitespace-nowrap text-right align-top rounded-r-md">
                        
                        <form action="<?php echo base_url('?page=purchase_orders&action=custom_po'); ?>" method="post">
                        <input type="hidden" name="cpoitem[]" value="<?php echo $product['id']?>">                                        
                        <button class="bg-create-po typo-create-po px-4 py-2 rounded-full flex items-center gap-2 ml-auto transition shadow-sm">
                            <i class="fa-solid fa-cart-shopping"></i>
                            Create PO
                        </button>   
                        </form>                     
                        <span onclick="updateProductsStock('<?php echo $product['item_code']; ?>', this)" title="Update single product" class="rowUpdateBtn update-button menu-button float-right text-gray-500 hover:bg-orange-200 font-semibold py-1 px-2 cursor-pointer ">
                            <i class="fas fa-sync-alt p-1 bg-white "></i>
                        </span>
                        <span class="typo-sf-column py-2">Updated at: <br> <?php echo $product['updated_at'] ? date('d M Y H:i:s', strtotime($product['updated_at'])) : ''; ?></span>
                    </td>
                </tr>
                
                <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No products found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div id="pagination-controls" class="flex justify-center items-center space-x-4 mt-8 bottom-0 border border-[rgba(226,228,230,1)] py-4">
            <div>
                <p class="text-sm text-gray-600">Showing <span class="font-medium">
             <?php            
            //echo '****************************************  '.$query_string;
            if ($total_pages > 1): ?>          
             <!-- Prev Button -->
                <a class="page-link px-2 py-1 rounded <?php if($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                href="?page=products&action=list&page_no=<?= $page-$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                    &laquo; Prev
                </a>
                <!-- Page Slots -->
                <?php for ($i = $start; $i <= $end; $i++): ?>
                    <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                    href="?page=products&action=list&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <!-- Next Button -->
                <a class="page-link px-2 py-1 rounded <?php if($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                href="?page=products&action=list&page_no=<?= $page+$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                    Next &raquo;
                </a>
            <?php endif; ?>
            <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                onchange="location.href='?page=products&page_no=1&limit=' + this.value + '<?= $query_string ?>';">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>

    </div>
</div>
<!-- Image Popup -->
<div id="imagePopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeImagePopup(event)">
    <div class="bg-white p-4 rounded-md max-w-3xl max-h-3xl relative flex flex-col items-center" onclick="event.stopPropagation();">
        <button onclick="closeImagePopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <img id="popupImage" class="max-w-full max-h-[80vh] rounded" src="" alt="Image Preview">
    </div>
</div>

<!-- success popup -->
<div id="successPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md h-56 relative flex flex-col items-center">
        <!-- <button onclick="closeSuccessPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button> -->
        <h2 class="text-xl font-bold mb-6 text-green-600" id="successTitle">Product Update</h2>
        <p class="py-2 font-semibold min-h-[80px]" id="successMessage">Updated successfully. </p>
        <button onclick="closeSuccessPopup()" class="bg-[rgba(208,103,6,1)] text-white font-semibold py-2 px-4 rounded-md action-button">OK</button>
    </div>
</div>

<!-- Details Modal -->
<div id="details-modal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
    <div id="details-modal-slider" class="fixed top-0 right-0 h-full flex transform translate-x-full" style="width: calc(45% + 61px); min-width: 950px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-details-modal"
                    class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center -ml-[61px]"
                    style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                     stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="h-full bg-white shadow-xl px-8 overflow-y-auto flex flex-col w-full">
            <!-- Modal Content -->
            <div class="flex-grow space-y-4" id="details-modal-content">
                <!-- Dynamic content will be loaded here -->
                
            </div>
        </div>
    </div>
</div>
<!-- Import Products Modal -->
<div id="importModal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg relative flex flex-col items-center">
        <button id="closeImportModal" class="absolute top-3 right-3 text-xl">×</button>
        <h3 class="text-lg font-bold mb-3">Import Products (Max 50 SKUs, comma separated)</h3>
        <textarea id="importItemCodes" class="w-full border rounded-md p-2 mb-3" rows="5" placeholder="e.g. ITEMCODE1, ITEMCODE2"></textarea>
        <div id="importCount" class="text-sm text-gray-600 mb-3">0 SKUs</div>
        <div class="flex justify-end gap-2">
            <button id="importCancelBtn" class="bg-gray-200 px-3 py-1 rounded">Cancel</button>
            <button id="importConfirmBtn" class="bg-amber-600 text-white px-3 py-1 rounded">Import</button>
        </div>
        <div id="importMsg" class="text-sm mt-3"></div>
    </div>
</div>
<script>
// image popup functions
function openImagePopup(imageUrl) {
    const popup = document.getElementById('imagePopup');
    const popupImage = document.getElementById('popupImage');
    popupImage.src = imageUrl;
    popup.classList.remove('hidden');
}
function closeSuccessPopup() {
    const popup = document.getElementById('successPopup');
    popup.classList.add('hidden');
}
function closeImagePopup() {
    const popup = document.getElementById('imagePopup');
    popup.classList.add('hidden');
}

const successPopup = document.getElementById('successPopup');
const successMessage = document.getElementById('successMessage');
// helper: show popup with message
function showPopup(message) {
    if (!successPopup || !successMessage) return;
    successMessage.innerHTML = message;
    successPopup.classList.remove('hidden');
}
// helper: hide popup
function hidePopup() {
    if (!successPopup) return;
    successPopup.classList.add('hidden');
    if (successPopupBody) successPopupBody.innerHTML = '';
}
//updateProductsStock function and success popup
function updateProductsStock(itemCode) {
    showPopup('Updating product stock. Please wait...');
    //const updateButton = document.getElementById('updateButton');
    //updateButton.disabled = true; // Disable button to prevent multiple clicks    
    fetch(`index.php?page=products&action=update_api_call&itemCode=${itemCode}`)
        .then(response => response.json())
        .then(data => {
            //updateButton.disabled = false; // Re-enable button
            if (data.success) {
                showPopup('Success! ' + data.message);
                setTimeout(() => {                    
                    window.location.reload(); // Reload the page to reflect updated stocks
                    hidePopup();
                }, 2000); // Hide popup after 5 seconds
                
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            //updateButton.disabled = false; // Re-enable button
            //console.error('Error:', error);
            alert('An error occurred while updating the product stock.');
        });
}
// Bulk Update Stocks button functionality
document.getElementById('bulkUpdateBtn').addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('input[name="product_select[]"]:checked');
    if (checkboxes.length === 0) {
        alert('Please select at least one product to update.');
        return;
    }
    showPopup('Updating selected products. Please wait...');    
    // comma separated item codes
    const itemCodes = Array.from(checkboxes).map(checkbox => checkbox.value).join(',');
    //validate max item codes per request 50
    if (checkboxes.length > 50) {
        alert('You can update a maximum of 50 products at a time.');
        hidePopup();
        return;
    }
    updateProductsStock(itemCodes); 
});

 document.addEventListener('DOMContentLoaded', function () {
        // Modal functionality
        const openModalBtn = document.getElementById('open-details-modal');
        const closeModalBtn = document.getElementById('close-details-modal');
        const modal = document.getElementById('details-modal');
        const modalSlider = document.getElementById('details-modal-slider');

        const openModal = () => {
            if (!modal || !modalSlider) return;
            modal.classList.remove('hidden');
            setTimeout(() => {
                modalSlider.classList.remove('translate-x-full');
            }, 10);
        };

        const closeModal = () => {
            if (!modal || !modalSlider) return;
            modalSlider.classList.add('translate-x-full');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 300);
        };

        if (openModalBtn) openModalBtn.addEventListener('click', openModal);
        if (closeModalBtn) closeModalBtn.addEventListener('click', closeModal);

        if (modal) {
            modal.addEventListener('click', (event) => {
                if (event.target === modal) {
                    closeModal();
                }
            });
        }
   
    // invdetails and sfdetails click handlers
    document.querySelectorAll('.invdetails').forEach(link => {
        link.addEventListener('click', function() {
            event.preventDefault();
            openModal(); // Open the modal first
            const modalContent = document.getElementById('details-modal-content');
            const row = this.closest('tr');
            // Try to find the element with the product JSON attribute inside the row (or fall back to the row itself)
            const productEl = row.querySelector('[data-product]') || row;
            const productJson = productEl ? (productEl.getAttribute('data-product') || productEl.dataset.product) : null;

            if (!productJson) {
                console.error('Product data not found on this row.');
                return;
            }

            let data;
            try {
                data = JSON.parse(productJson);
            } catch (e) {
                console.error('Failed to parse product JSON', e);
                return;
            }
            modalContent.innerHTML = '<p>Loading...</p>'; // Show loading indicator
            fetch(`?page=products&action=get_product_details_html&item_code=${encodeURIComponent(data.item_code)}`)
                .then(response => response.text())
                .then(html => {
                    modalContent.innerHTML = html; // Insert the fetched HTML
                    // Initialize accordion triggers inside the newly injected content so they work.
                    // if (typeof initAccordionTriggers === 'function') {
                    //     initAccordionTriggers(modalContent);
                    // }
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    modalContent.innerHTML = '<p>Error loading order details.</p>';
                });
        });
    });
    // document.querySelectorAll('.sfdetails').forEach(link => {
    //     link.addEventListener('click', function() {
    //         event.preventDefault();
    //         openModal(); // Open the modal first
    //         const modalContent = document.getElementById('details-modal-content');
    //         const row = this.closest('tr');
    //         // Try to find the element with the product JSON attribute inside the row (or fall back to the row itself)
    //         const productEl = row.querySelector('[data-product]') || row;
    //         const productJson = productEl ? (productEl.getAttribute('data-product') || productEl.dataset.product) : null;

    //         if (!productJson) {
    //             console.error('Product data not found on this row.');
    //             return;
    //         }

    //         let data;
    //         try {
    //             data = JSON.parse(productJson);
    //         } catch (e) {
    //             console.error('Failed to parse product JSON', e);
    //             return;
    //         }
    //         //console.log(data);
    //         // let content = `
    //         //     <h2 class="text-2xl font-bold mb-4">S & F Details for ${data.item_code}</h2>
    //         //     <p><strong>Item Price:</strong> ₹${data.itemprice}</p>
    //         //     <p><strong>Final Price:</strong> ₹${data.finalprice}</p>
    //         //     <p><strong>Shipping Fee:</strong> ₹${data.shipping_fee}</p>            
    //         //     <p><strong>Cost price:</strong> ₹${data.cost_price}</p>                
    //         // `;
    //         // modalContent.innerHTML = content;
    //         modalContent.innerHTML = '<p>Loading...</p>'; // Show loading indicator
    //         fetch(`?page=products&action=get_product_details_html&item_code=${encodeURIComponent(data.item_code)}`)
    //             .then(response => response.text())
    //             .then(html => {
    //                 modalContent.innerHTML = html; // Insert the fetched HTML
    //                 // Initialize accordion triggers inside the newly injected content so they work.
    //                 // if (typeof initAccordionTriggers === 'function') {
    //                 //     initAccordionTriggers(modalContent);
    //                 // }
    //             })
    //             .catch(error => {
    //                 console.error('Error loading order details:', error);
    //                 modalContent.innerHTML = '<p>Error loading order details.</p>';
    //             });
            
    //     });
    // });
    // Select/Deselect all checkboxes
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const isChecked = this.checked;
            document.querySelectorAll('input[name="product_select[]"]').forEach(checkbox => {
                checkbox.checked = isChecked;
            });
        });
    }

});

// Import Products Modal functionality
document.getElementById('importProductsBtn').addEventListener('click', function() {
    document.getElementById('importItemCodes').value = '';
    document.getElementById('importCount').textContent = '0 SKUs';
    document.getElementById('importMsg').textContent = '';
    document.getElementById('importModal').classList.remove('hidden');
});

document.getElementById('closeImportModal').addEventListener('click', function() {
    document.getElementById('importModal').classList.add('hidden');
});
document.getElementById('importCancelBtn').addEventListener('click', function() {
    document.getElementById('importModal').classList.add('hidden');
});

const importInput = document.getElementById('importItemCodes');
importInput.addEventListener('input', function() {
    const codes = this.value.split(',').map(s => s.trim()).filter(Boolean);
    document.getElementById('importCount').textContent = `${codes.length} SKUs`;
    if (codes.length > 50) {
        document.getElementById('importCount').classList.add('text-red-500');
    } else {
        document.getElementById('importCount').classList.remove('text-red-500');
    }
});

document.getElementById('importConfirmBtn').addEventListener('click', function() {
    const btn = this;
    const raw = importInput.value;
    const codes = raw.split(',').map(s => s.trim()).filter(Boolean);
    const msg = document.getElementById('importMsg');
    msg.textContent = '';

    if (codes.length === 0) {
        msg.textContent = 'Please add at least one SKU.';
        msg.className = 'text-sm text-red-500 mt-3';
        return;
    }
    if (codes.length > 50) {
        msg.textContent = 'Maximum 50 SKUs only.';
        msg.className = 'text-sm text-red-500 mt-3';
        return;
    }

    btn.disabled = true;
    const originalText = btn.textContent;
    btn.textContent = 'Processing...';

    fetch('?page=products&action=import_api_call', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ itemCodes: codes })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            msg.textContent = `Imported: ${data.created}, Updated: ${data.updated}, Failed: ${data.failed.length} SKUs.`;
            msg.className = 'text-sm text-green-600 mt-3';
            setTimeout(()=> {
                document.getElementById('importModal').classList.add('hidden');
                // Optional: reload to show new products
                window.location.reload();
            }, 2100);
        } else {
            msg.textContent = data.message || 'Import failed';
            msg.className = 'text-sm text-red-500 mt-3';
        }
    })
    .catch(err => {
        msg.textContent = 'Error: ' + (err.message || 'Request failed');
        msg.className = 'text-sm text-red-500 mt-3';
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
});
document.addEventListener('DOMContentLoaded', function () {
    // Accordion functionality
    const accordionButton = document.getElementById('accordion-button-search');
    const accordionContent = document.getElementById('accordion-content-search');
    const accordionIcon = document.getElementById('accordion-icon-search');

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

    function clearFilters() {
        fromDateInput.value = '';
        toDateInput.value = '';
        toDateInput.min = null;
    }

    //clearButton.addEventListener('click', clearFilters);


    const productsSearchForm = document.getElementById('productsSearchForm');
    productsSearchForm.addEventListener('submit', function(event) {
        // You can add custom validation here if needed
        // For example, ensure that from_date is not after to_date
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
        setHidden('page', 'products');
        setHidden('action', 'list');

        form.submit();
        return false;
    });
});
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