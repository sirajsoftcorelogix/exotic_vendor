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
                            value="<?php echo isset($_GET['vendor_name']) ? htmlspecialchars($_GET['vendor_name']) : ''; ?>">
                        <input type="hidden" name="vendor_id" id="vendor_id" value="<?php echo isset($_GET['vendor_id']) ? htmlspecialchars($_GET['vendor_id']) : ''; ?>">
                        <div id="vendor_suggestions" class="absolute left-0 right-0 mt-1 z-50 bg-white border rounded-md shadow-lg max-h-48 overflow-auto " style="display:none; top:100%;"></div>
                    </div>
                    <div>
                        <label for="item-group" class="block text-sm font-medium text-gray-600 mb-1">Item Group / Category</label>
                        <select name="item_group" id="item-group" class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="">-Select-</option>
                            <?php
                            $groups = ['sculptures', 'book', 'jewelry', 'textiles', 'paintings'];
                            $selectedGroup = (string)($_GET['item_group'] ?? '');
                            foreach ($groups as $g) {
                                $sel = ($selectedGroup === $g) ? 'selected' : '';
                                echo '<option value="' . htmlspecialchars($g) . '" ' . $sel . '>' . htmlspecialchars($g) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-600 mb-1">SKU</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['sku'] ?? '') ?>" name="sku" id="sku" placeholder="SKU" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label for="low_stock" class="block text-sm font-medium text-gray-600 mb-1">Low Stock</label>
                        <select id="low_stock" name="low_stock" class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="">-Select-</option>
                            <option value="1" <?= (isset($_GET['low_stock']) && $_GET['low_stock'] === '1') ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (isset($_GET['low_stock']) && $_GET['low_stock'] === '0') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="permanently_available" class="block text-sm font-medium text-gray-600 mb-1">Permanently Available</label>
                        <select id="permanently_available" name="permanently_available" class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                            <option value="">-Select-</option>
                            <option value="1" <?= (isset($_GET['permanently_available']) && $_GET['permanently_available'] === '1') ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (isset($_GET['permanently_available']) && $_GET['permanently_available'] === '0') ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>
                    <div>
                        <label for="size" class="block text-sm font-medium text-gray-600 mb-1">Size</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['size'] ?? '') ?>" name="size" id="size" placeholder="Size" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label for="color" class="block text-sm font-medium text-gray-600 mb-1">Color</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['color'] ?? '') ?>" name="color" id="color" placeholder="Color" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label for="local_stock" class="block text-sm font-medium text-gray-600 mb-1">Local Stock</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['local_stock'] ?? '') ?>" name="local_stock" id="local_stock" placeholder="Local Stock" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div>
                        <label for="marketplace" class="block text-sm font-medium text-gray-600 mb-1">Marketplace</label>
                        <input type="text" value="<?= htmlspecialchars($_GET['marketplace'] ?? '') ?>" name="marketplace" id="marketplace" placeholder="Marketplace" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <!-- <div>
                    <label for="agent" class="block text-sm font-medium text-gray-600 mb-1">Agent</label>
                    <select id="agent" name="agent" class="w-full px-2 py-1.5 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="" selected>-Select-</option>
                        <?php //foreach ($staff_list as $key => $value): 
                        ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_GET['agent']) && $_GET['agent'] == $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php //endforeach; 
                        ?>                    
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
                <div class="relative inline-block text-left">
                    <button id="bulk-action-toggle" type="button" class="btn btn-success inline-flex items-center px-4 py-2" aria-haspopup="true" aria-expanded="false">
                        Actions
                        <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div id="bulk-action-menu" class="hidden absolute left-0 mt-2 w-48 bg-white border rounded shadow z-50">
                        <!-- <a href="#" id="action-create-po" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create PO</a>
                    <a href="#" id="action-update-status" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Status</a> -->
                        <a href="javascript:void(0)" id="importProductsBtn" title="Import products" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Import Products</a>
                        <a href="?page=products&action=bulk_import" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Bulk Import (Excel)</a>
                        <a href="javascript:void(0)" id="bulkUpdateBtn" title="Update stock" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Products</a>
                        <a href="javascript:void(0)" id="action-assign-to" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Add to purchase list</a>
                        <a href="javascript:void(0)" id="action-transfer-stock" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Transfer Stock</a>
                    </div>
                </div>
                <!-- <button id="importProductsBtn" title="Import products" class="flex right-0 top-0 bg-amber-600 text-white font-semibold py-2 px-4 rounded-md shadow-md hover:bg-amber-700 transition">
                Import             
            </button>
            <button id="bulkUpdateBtn" title="Update stock" class="flex right-0 top-0 bg-amber-600 text-white font-semibold py-2 px-4 rounded-md shadow-md hover:bg-amber-700 transition">
                Update
            </button> -->
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
                        <th class="px-6 py-3">Inventory Overview</th>
                        <th class="px-6 py-3">Price</th>
                        <th class="px-6 py-3 text-right">Recommended Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($data['products'])): ?>
                        <?php foreach ($data['products'] as $product): ?>
                            <!-- Table Row 1 -->
                            <tr class="bg-white rounded-md shadow-sm" id="product-item-code<?php echo $product['item_code']; ?>" data-product='<?= htmlspecialchars(json_encode($product), ENT_QUOTES, 'UTF-8') ?>'>
                                <!-- Checkbox -->
                                <td class="p-4 whitespace-nowrap rounded-l-md align-top pt-6">
                                    <input type="checkbox" id="product_<?php echo $product['item_code']; ?>" name="product_select[]" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" value="<?php echo $product['item_code']; ?>">

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
                                            <span class="typo-sku-value"><a href="<?php echo base_url('?page=products&action=detail&id=' . $product['id'] ?? '#'); ?>" target="_blank" class="typo-sf-column text-details-link mt-1"><?php echo $product['sku']; ?></a></span>
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
                                        <img class="w-16 rounded-md object-cover flex-shrink-0 cursor-pointer" onclick="openImagePopup('<?php echo $product['image']; ?>')" src="<?php echo $product['image'] ?? 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image'; ?>" alt="Product Image">
                                        <div class="flex flex-col justify-between h-full">
                                            <p class="typo-product-title mb-2 max-w-xs"><?php echo $product['title']; ?></p>
                                            <p class="typo-product-title mb-2 max-w-xs">
                                                <?php echo $product['size'] ? '<strong>Size :</strong>' . $product['size'] : ''; ?>
                                                <?php echo $product['color'] ? ' <strong>Color :</strong>' . ucfirst($product['color']) : ''; ?>
                                            </p>
                                            <div class="flex items-center mt-auto">

                                                <span class="typo-vendor">Vendor : <?php echo $product['vendor']; ?></span>
                                                <a href="javascript:void(0);" class="ml-2 text-details-link hover:text-amber-700" title="Edit Vendor" onclick="openEditVendorModal('<?php echo $product['item_code']; ?>', '<?php echo htmlspecialchars(addslashes($product['vendor'] ?? '')); ?>')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
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
                                    <span class="typo-sf-column"><?php echo $product['price'] ? '$' . $product['price'] : ''; ?> <br><?php echo $product['price_india'] ? '₹' . $product['price_india'] : ''; ?></span>
                                </td>

                                <!-- Recommended Action -->
                                <td class="px-6 py-4 whitespace-nowrap text-right align-top rounded-r-md">

                                    <form action="<?php echo base_url('?page=purchase_orders&action=custom_po'); ?>" method="post">
                                        <input type="hidden" name="cpoitem[]" value="<?php echo $product['id'] ?>">
                                        <button class="bg-create-po typo-create-po px-4 py-2 rounded-full flex items-center gap-2 ml-auto transition shadow-sm">
                                            <i class="fa-solid fa-cart-shopping"></i>
                                            Create PO
                                        </button>
                                    </form>
                                    <span onclick="updateProductsStock('<?php echo $product['item_code']; ?>', this)" title="Update single product" class="rowUpdateBtn update-button menu-button float-right text-gray-500 hover:bg-orange-200 font-semibold py-1 px-2 cursor-pointer ">
                                        <i class="fas fa-sync-alt p-1 bg-white "></i>
                                    </span>
                                    <span class="typo-sf-column py-2">Updated at: <br> <?php echo $product['updated_at'] ? date('d M Y H:i:s', strtotime($product['updated_at'])) : ''; ?></span>
                                    <!-- Modify button-->
                                    <button class="bg-modify typo-modify px-4 py-2 rounded-full flex items-center gap-2 ml-auto transition shadow-sm mt-2 openEditModal"
                                        data-product-id="<?php echo $product['id']; ?>"
                                        data-item-code="<?php echo $product['item_code']; ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                        Modify
                                    </button>
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
                <p class="text-sm text-gray-600">Showing <span class="font-medium"> <?php echo ($total_orders > 0) ? (($page - 1) * $limit + 1) : 0; ?></span> to <span class="font-medium"><?php echo min($page * $limit, $total_orders); ?></span> of <span class="font-medium"><?php echo $total_orders; ?></span> products</p>
            </div>
            <?php
            //echo '****************************************  '.$query_string;
            if ($total_pages > 1): ?>
                <!-- Prev Button -->
                <a class="page-link px-2 py-1 rounded <?php if ($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=products&action=list&page_no=<?= $page - $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
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
                <a class="page-link px-2 py-1 rounded <?php if ($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=products&action=list&page_no=<?= $page + $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
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
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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

<!-- Edit Product Modal -->
<div id="editProductModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h2 class="text-2xl font-bold text-gray-900">Edit Product</h2>
            <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <form id="editProductForm" class="p-6 space-y-4">
            <input type="hidden" id="editProductId" name="product_id">

            <!-- Read-only Fields -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Item Code</label>
                    <input type="text" id="editItemCode" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                    <input type="text" id="editSku" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Size</label>
                    <input type="text" id="editSize" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
                    <input type="text" id="editColor" readonly class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-100 text-gray-600">
                </div>
            </div>

            <!-- Editable Fields -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                <input type="text" id="editTitle" name="title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea id="editDescription" name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (USD)</label>
                    <input type="number" id="editPrice" name="price" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Price (INR)</label>
                    <input type="number" id="editPriceIndia" name="price_india" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">GST</label>
                    <input type="text" id="editGst" name="gst" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Group Name</label>
                    <select name="editGroupName" id="editGroupName" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">Select Group</option>
                        <?php foreach ($groupnameList as $groupname) { ?>
                            <option value="<?php echo $groupname; ?>"><?php echo ucfirst($groupname); ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
                    <input type="hidden" name="editVendorID" id="editVendorID">
                    <input type="text" id="editVendor" name="vendor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" autocomplete="off">
                    <div id="editVendor_suggestions" class="absolute z-10 w-full bg-white border border-gray-300 rounded-b-md shadow-lg hidden max-h-60 overflow-y-auto"></div>
                </div>
                <!-- <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Image URL</label>
                    <input type="url" id="editImage" name="image" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                </div> -->
                <!-- <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><input type="text" id="editCategory" name="category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div> -->
            </div>

            <!-- Additional Fields -->
            <div class="grid grid-cols-2 gap-4 mt-4 pt-4 border-t border-gray-200">

                <div><label class="block text-sm font-medium text-gray-700 mb-1">Item Type</label><input type="text" id="editItemtype" name="itemtype" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Snippet Description</label><textarea id="editSnippetDescription" name="snippet_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">India Net Qty</label><input type="number" id="editIndiaNetQty" name="india_net_qty" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Keywords</label><input type="text" id="editKeywords" name="keywords" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">US Block</label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="usblock" value="1" class="form-radio text-amber-600 focus:ring-amber-500 h-4 w-4 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Yes</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="usblock" value="0" class="form-radio text-amber-600 focus:ring-amber-500 h-4 w-4 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">No</span>
                        </label>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">India Block</label>
                    <div class="flex items-center space-x-4 mt-2">
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="indiablock" value="1" class="form-radio text-amber-600 focus:ring-amber-500 h-4 w-4 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">Yes</span>
                        </label>
                        <label class="inline-flex items-center cursor-pointer">
                            <input type="radio" name="indiablock" value="0" class="form-radio text-amber-600 focus:ring-amber-500 h-4 w-4 border-gray-300">
                            <span class="ml-2 text-sm text-gray-700">No</span>
                        </label>
                    </div>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">HS Code</label><input type="text" id="editHscode" name="hscode" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Date First Added</label><input type="date" id="editDateFirstAdded" name="date_first_added" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Search Term</label><input type="text" id="editSearchTerm" name="search_term" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Search Category</label><input type="text" id="editSearchCategory" name="search_category" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div class="col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Long Description</label><textarea id="editLongDescription" name="long_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea></div>
                <div class="col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Long Description (India)</label><textarea id="editLongDescriptionIndia" name="long_description_india" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></textarea></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">A+ Content IDs</label><input type="text" id="editAplusContentIds" name="aplus_content_ids" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Material</label><input type="text" id="editMaterial" name="material" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Item Level</label><input type="text" id="editItemLevel" name="item_level" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Marketplace Vendor</label><input type="text" id="editMarketplaceVendor" name="marketplace_vendor" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Color Map</label><input type="text" id="editColormap" name="colormap" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Flex Status</label><input type="text" id="editFlexStatus" name="flex_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Vendor US</label><input type="text" id="editVendorUs" name="vendor_us" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Price India Suggested</label><input type="number" id="editPriceIndiaSuggested" name="price_india_suggested" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">MRP India</label><input type="number" id="editMrpIndia" name="mrp_india" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Permanent Discount</label><input type="number" id="editPermanentDiscount" name="permanent_discount" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Discount Global</label><input type="number" id="editDiscountGlobal" name="discount_global" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Today Global</label><input type="text" id="editTodayGlobal" name="today_global" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Discount India</label><input type="number" id="editDiscountIndia" name="discount_india" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Today India</label><input type="text" id="editTodayIndia" name="today_india" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">To Purchase</label><input type="number" id="editTopurchase" name="topurchase" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Back Order</label>
                    <select id="editBackorderFlag" name="backorder_flag" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500" onchange="toggleBackorderFields()">
                        <option value="0">No</option>
                        <option value="1">Yes</option>
                    </select>
                </div>
                <div id="containerBackorderPercent" class="hidden relative w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Backorder Percent</label>
                    <input type="text" id="editBackorderPercent" name="backorder_percent" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">%</span>
                    </span>
                </div>
                <div id="containerBackorderWeeks" class="hidden relative w-full">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Backorder Weeks</label>
                    <input type="text" id="editBackorderWeeks" name="backorder_weeks" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">Weeks</span>
                    </span>
                </div>
                <!-- add Days in right side of text field-->
                <div class="relative w-full"><label class="block text-sm font-medium text-gray-700 mb-1">Leadtime</label><input type="text" id="editLeadtime" name="leadtime" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">Days</span>
                    </span>
                </div>
                <div class="relative w-full"><label class="block text-sm font-medium text-gray-700 mb-1">Instock Leadtime</label><input type="text" id="editInstockLeadtime" name="instock_leadtime" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                    <span class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <span class="text-gray-500">Days</span>
                    </span>
                </div>

                <div><label class="block text-sm font-medium text-gray-700 mb-1">CP</label><input type="number" id="editCp" name="cp" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">USD</label><input type="number" id="editUsd" name="usd" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Permanently Available</label>
                    <select name="permanently_available" id="editPermanentlyAvailable" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500">
                        <option value="">Select</option>
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Amazon Sold</label><input type="number" id="editAmazonSold" name="amazon_sold" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Amazon Leadtime</label><input type="number" id="editAmazonLeadtime" name="amazon_leadtime" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Amazon Itemcode Alias</label><input type="text" id="editAmazonItemcodeAlias" name="amazon_itemcode_alias" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Youtube Links</label><input type="text" id="editYoutubeLinks" name="youtube_links" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Sketchfab Links</label><input type="text" id="editSketchfabLinks" name="sketchfab_links" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
                <div><label class="block text-sm font-medium text-gray-700 mb-1">Dimensions</label><input type="text" id="editDimensions" name="dimensions" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-amber-500"></div>
            </div>

            <!-- Modal Footer -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-md hover:bg-gray-300 transition">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 text-white bg-amber-600 rounded-md hover:bg-amber-700 transition">
                    Save Changes
                </button>
            </div>
        </form>
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
<!-- vendor update Modal -->
<div id="vendor-modal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
    <div id="vendor-modal-slider" class="fixed top-0 right-0 h-full flex transform translate-x-full" style="width: calc(45% + 61px); min-width: 950px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-modal"
                class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center -ml-[61px]"
                style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="h-full bg-white shadow-xl px-8 overflow-y-auto flex flex-col w-full">
            <!-- Modal Content -->
            <div class="px-6 py-4 md:px-8">
                <div class="flex items-center gap-4">
                    <!-- Circular Icon -->
                    <div class="w-10 h-10 md:w-12 md:h-12 bg-brand-orange rounded-full flex items-center justify-center shadow-sm flex-shrink-0">
                        <i class="fas fa-link text-white text-xl md:text-2xl transform -rotate-45"></i>
                    </div>
                    <!-- Title -->
                    <h1 class="text-xl md:text-xl font-bold text-gray-900">Product Mapping ⇌ Vendor</h1>
                </div>
            </div>

            <!-- Purple Separator Line -->
            <div class="h-1 bg-brand-purple w-full"></div>

            <div class="p-5 md:p-7 flex-grow">

                <!-- ================= PRODUCT INFO CARD (ORANGE ROUNDED BOX) ================= -->
                <div class="bg-brand-orange rounded-xl p-6 mb-8 flex flex-col md:flex-row items-center gap-6 shadow-md text-white sticky top-0 z-10">
                    <!-- Product Icon/Image Placeholder -->
                    <div class="w-20 h-20 bg-white bg-opacity-20 rounded-lg flex items-center justify-center flex-shrink-0 border border-white border-opacity-30">
                        <!-- <i class="fas fa-box-open text-3xl text-white opacity-90"></i> -->
                        <img id="product_image_mapped" src="" alt="Product Image" class="max-w-full max-h-full rounded-lg">
                    </div>

                    <!-- Product Details -->
                    <div class="flex-grow">
                        <!-- Item Code: Reduced font size as requested -->
                        <div class="text-xl md:text-lg font-bold mb-2" id="item_code_mapped">Item Code : </div>
                        <!-- Description in regular font -->
                        <p class="text-white text-md font-normal opacity-95 leading-snug" id="current_vendor_mapped">

                        </p>
                    </div>
                </div>


                <!-- ================= VENDOR SEARCH SECTION (GRAY CARD) ================= -->
                <div class="bg-brand-light-gray rounded-lg p-6 mb-6 border border-gray-200 shadow-sm relative">
                    <label for="vendorSearch" class="block font-bold text-gray-800 mb-2 text-lg">Vendor :</label>
                    <div class="flex gap-0 shadow-sm w-full">
                        <input type="text" id="vendorSearch" placeholder="Search Vendor Name or Code..."
                            class="flex-grow border border-gray-300 rounded-l px-4 py-3 focus:outline-none focus:border-brand-orange focus:ring-1 focus:ring-brand-orange w-full">
                        <input type="hidden" name="vendor_id" id="vendorId" value="">

                        <!-- Changed button text to + Add -->
                        <button id="addVendorButton" class="bg-black text-white px-8 py-3 rounded-r font-bold hover:bg-gray-800 transition-colors uppercase tracking-wide whitespace-nowrap">
                            + Add
                        </button>
                    </div>
                    <div id="vendorSuggestionsList" class="px-6 " style="display:none; position:absolute; left:0; right:0; z-index:50; max-height:240px; overflow:auto;"></div>

                </div>
                <div class="flex-grow space-y-4" id="vendor-modal-content">
                    <!-- Dynamic content will be loaded here -->

                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Assign To Modal -->
<div id="bulkAssignPopup" class="fixed inset-0 bg-black bg-opacity-50 hidden flex justify-center items-center z-50" onclick="closeBulkAssignPopup(event)">
    <div class="bg-white p-4 rounded-md max-w-2xl w-full relative" onclick="event.stopPropagation();">
        <button onclick="closeBulkAssignPopup()" class="absolute top-2 right-2 bg-red-500 text-white px-3 py-1 rounded-full text-sm">✕</button>
        <h2 class="text-xl font-bold mb-4">Create Purchase List</h2>
        <form id="bulkAssignForm" method="post" action="">
            <div class="mb-4 flex gap-4 w-full">
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-2">Assign To</label>
                    <select id="bulkAssignAgent" name="agent_id" class="border rounded px-3 py-2 w-full">
                        <option value="">-- Select Agent --</option>
                        <?php foreach ($user as $id => $name): ?>
                            <option value="<?= $id ?>"><?= htmlspecialchars($name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label class="block text-sm font-bold mb-2">Date Purchased</label>
                    <input type="date" id="bulkAssignDatePurchased" name="date_purchased" class="border rounded px-3 py-2 w-full">
                </div>
            </div>
            <div class="mb-4">
                <!-- <label class="block text-sm font-bold mb-2">Notes</label>
                <textarea id="bulkStatusNotes" name="notes" class="border rounded px-3 py-2 w-full" rows="3"></textarea> -->
                <!--list selected item image-->
                <div id="bulkAssignSelectedItems" class="flex flex-wrap gap-2 max-h-48 overflow-y-auto border p-2">
                    <!-- Selected item images will be displayed here -->
                </div>
            </div>
            <div id="bulkAssignError" class="text-red-500 text-sm hidden mb-2"></div>
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeBulkAssignPopup()" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Create</button>
            </div>
        </form>
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
        fetch('index.php?page=products&action=update_api_call&itemCode=' + encodeURIComponent(itemCode))
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
            showAlert('Please select at least one product to update.', 'warning');
            return;
        }
        showPopup('Updating selected products. Please wait...');
        // comma separated item codes
        const itemCodes = Array.from(checkboxes).map(checkbox => checkbox.value).join(',');
        //validate max item codes per request 50
        if (checkboxes.length > 50) {
            showAlert('You can update a maximum of 50 products at a time.', 'warning');
            hidePopup();
            return;
        }
        updateProductsStock(itemCodes);
    });

    document.addEventListener('DOMContentLoaded', function() {
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
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    itemCodes: codes
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    let timingPart = '';
                    if (data.timing && typeof data.timing.total_ms === 'number') {
                        const t = data.timing;
                        const cps = t.codes_per_second != null ? ` ~${t.codes_per_second} codes/s` : '';
                        timingPart = ` — ${(t.total_ms / 1000).toFixed(2)}s total (${(t.api_ms / 1000).toFixed(2)}s API)${cps}.`;
                    }
                    msg.textContent = `Imported: ${data.created}, Updated: ${data.updated}, Failed: ${data.failed.length} SKUs.${timingPart}`;
                    msg.className = 'text-sm text-green-600 mt-3';
                    setTimeout(() => {
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
    document.addEventListener('DOMContentLoaded', function() {
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
            const form = this; // Use the form that triggered the event

            function setHidden(name, value) {
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

            // Let the form submit normally
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

    document.getElementById('editVendor').addEventListener('input', function() {
        const query = this.value;
        const suggestionsBox = document.getElementById('editVendor_suggestions');

        // close suggestions if clicked outside
        document.addEventListener('click', function(event) {
            if (!suggestionsBox.contains(event.target) && event.target !== document.getElementById('editVendor')) {
                suggestionsBox.style.display = 'none';
            }
            if (event.target === document.getElementById('editVendor')) {
                if (suggestionsBox.children.length > 0) {
                    suggestionsBox.style.display = 'block';
                }
            }
        });

        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        fetch('<?php echo base_url("?page=purchase_orders&action=remote_vendor_search&query="); ?>' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                console.log(data);
                suggestionsBox.innerHTML = '';
                if (Array.isArray(data) && data.length > 0) {
                    data.forEach(vendor => {
                        const div = document.createElement('div');
                        div.className = 'p-2 hover:bg-gray-200 cursor-pointer text-sm text-gray-800';
                        div.textContent = vendor.vendor_name;
                        div.addEventListener('click', function() {
                            document.getElementById('editVendor').value = vendor.vendor_name;
                            document.getElementById('editVendorID').value = vendor.id;
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

    function openEditVendorModal(itemCode, currentVendor) {
        //const newVendor = prompt(`Edit Vendor for Item Code: ${itemCode}`, currentVendor);
        //if (newVendor === null) return; // User cancelled
        //use vendor-modal-content to show loading
        const modalContent = document.getElementById('vendor-modal-content');
        const vendorModal = document.getElementById('vendor-modal');
        const vendorModalSlider = document.getElementById('vendor-modal-slider');
        const productImage = document.getElementById('product_image_mapped');
        document.getElementById('item_code_mapped').innerText = `Item Code : ${itemCode}`;
        document.getElementById('current_vendor_mapped').innerText = `${currentVendor}`;

        productImage.src = 'https://placehold.co/100x100/e2e8f0/4a5568?text=Image'; // set default image
        //get product image from the row product-item-code attribute
        //const row = document.getElementById(`product-item-code${itemCode}`);
        const productData = JSON.parse(document.getElementById(`product-item-code${itemCode}`).getAttribute('data-product'));
        if (productData) {
            const productImageSrc = productData.image;
            if (productImageSrc) {
                productImage.src = productImageSrc;
            }
        }

        modalContent.innerHTML = '<p>Updating vendor. Please wait...</p>';
        vendorModal.classList.remove('hidden');
        setTimeout(() => {
            vendorModalSlider.classList.remove('translate-x-full');
        }, 300);

        //fetch form
        fetch(`?page=products&action=get_vendor_edit_form&item_code=${encodeURIComponent(itemCode)}&current_vendor=${encodeURIComponent(currentVendor)}`)
            .then(response => response.text())
            .then(html => {
                modalContent.innerHTML = html; // Insert the fetched HTML

            })
            .catch(error => {
                console.error('Error loading vendor edit form:', error);
                modalContent.innerHTML = '<p>Error loading vendor edit form.</p>';
            });

        // fetch(`index.php?page=products&action=update_vendor`, {
        //     method: 'POST',
        //     headers: {'Content-Type': 'application/json'},
        //     body: JSON.stringify({ item_code: itemCode, vendor: newVendor })
        // })
        // .then(response => response.json())
        // .then(data => {
        //     if (data.success) {
        //         alert('Vendor updated successfully.');
        //         window.location.reload(); // Reload to reflect changes
        //     } else {
        //         alert('Error updating vendor: ' + data.message);
        //     }
        // })
        // .catch(error => {
        //     console.error('Error:', error);
        //     alert('An error occurred while updating the vendor.');
        // });
    }
    // Close vendor modal
    document.getElementById('close-vendor-modal').addEventListener('click', function() {
        const vendorModal = document.getElementById('vendor-modal');
        const vendorModalSlider = document.getElementById('vendor-modal-slider');
        vendorModalSlider.classList.add('translate-x-full');
        setTimeout(() => {
            vendorModal.classList.add('hidden');
        }, 300);
    });
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    'brand-orange': 'rgba(208, 103, 6, 1)',
                    'brand-purple': '#A855F7',
                    'brand-red': 'rgba(204, 0, 0, 1)',
                    'brand-gray': '#E5E7EB',
                    'brand-light-gray': '#F3F4F6',
                },
                boxShadow: {
                    'card': '0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)',
                }
            }
        }
    }

    function removeVendor(button, id) {
        // Confirm and delete vendor mapping via AJAX
        const itemCode = button.getAttribute('data-item-code');
        if (!itemCode) return;
        if (!confirm('Are you sure you want to remove the vendor mapping for Item Code: ' + itemCode + '?')) {
            return;
        }
        fetch(`index.php?page=products&action=remove_vendor_mapping`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    id: id
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Vendor mapping removed successfully.');
                    //window.location.reload(); // Reload to reflect changes
                } else {
                    alert('Error removing vendor mapping: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the vendor mapping.');
            });

        //remove vendor mapping visually    
        const item = button.closest('.vendor-item');
        if (item) {
            // if(confirm('Remove this vendor from the product?')) {
            item.style.transition = 'all 0.3s ease';
            item.style.opacity = '0';
            item.style.transform = 'translateX(10px)';
            setTimeout(() => {
                item.remove();
            }, 300);
            // }
        }
    }
    //vendorSearch suggession list autocomplete and add new vendor 
    // Vendor Autocomplete
    document.getElementById('vendorSearch').addEventListener('input', function() {
        const query = this.value;
        const suggestionsBox = document.getElementById('vendorSuggestionsList');
        const vendorIdInput = document.getElementById('vendorId');

        if (query.length < 2) {
            suggestionsBox.style.display = 'none';
            return;
        }

        fetch('<?php echo base_url("?page=purchase_orders&action=vendor_search&query="); ?>' + encodeURIComponent(query))
            .then(response => response.json())
            .then(data => {
                suggestionsBox.innerHTML = '';
                renderSuggestions(data.data);
                // if (Array.isArray(data.data) && data.data.length > 0) {
                //     data.data.forEach(vendor => {
                //         const div = document.createElement('div');
                //         div.className = 'p-2 hover:bg-gray-200 cursor-pointer';
                //         div.textContent = vendor.vendor_name;
                //         div.addEventListener('click', function() {
                //             document.getElementById('vendor_autocomplete').value = vendor.vendor_name;
                //             vendorIdInput.value = vendor.id;
                //             suggestionsBox.style.display = 'none';
                //         });
                //         suggestionsBox.appendChild(div);
                //     });
                //     suggestionsBox.style.display = 'block';
                // } else {
                //     suggestionsBox.style.display = 'none';
                // }
            });
    });

    function renderSuggestions(list) {
        const suggBox = document.getElementById('vendorSuggestionsList');
        if (!suggBox) return;
        if (!Array.isArray(list) || list.length === 0) {
            suggBox.innerHTML = '';
            suggBox.style.display = 'none';
            return;
        }
        suggBox.innerHTML = list.map((v, i) => {
            return `<div class="sugg-item position-relative z-10 w-full p-2 cursor-pointer hover:bg-gray-300 bg-white px-6 border border-gray-300 rounded-b" data-index="${i}" data-id="${escapeHtml(v.id)}" data-json='${escapeHtml(JSON.stringify(v))}' style="padding:8px 10px;">
                    <div style="font-weight:600;">${escapeHtml(v.vendor_name || '')} — ${escapeHtml(v.city || '')} — ${escapeHtml(v.state || '')}</div>
                    <div style="font-size:11px;color:#6b7280;">Phone: ${escapeHtml(v.vendor_phone || '')} • Agent name: ${escapeHtml(v.agent_name || '-')}</div>
                </div>`;
        }).join('');
        suggBox.style.display = 'block';
        activeIndex = -1;
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function(s) {
            return ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            })[s];
        });
    }

    function unescapeHtml(str) {
        if (!str) return str;
        return str.replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>').replace(/&quot;/g, '"').replace(/&#39;/g, "'");
    }

    // Click on suggestion -> select vendor
    const vendorSuggestionsContainer = document.getElementById('vendorSuggestionsList');
    let selectedVendorData = null;
    if (vendorSuggestionsContainer) {
        vendorSuggestionsContainer.addEventListener('click', function(event) {
            const item = event.target.closest('.sugg-item');
            if (!item) return;
            const json = item.getAttribute('data-json') || item.dataset.json;
            let v = {};
            try {
                v = JSON.parse(unescapeHtml(json));
            } catch (e) {
                try {
                    v = JSON.parse(json);
                } catch (err) {
                    console.error('Failed parse vendor json', err);
                }
            }
            if (!v) return;
            document.getElementById('vendorId').value = v.id || '';
            document.getElementById('vendorSearch').value = v.vendor_name || '';
            document.getElementById('vendorSearch').dataset.vendorCode = v.vendor_code || v.vendor_code || v.code || '';
            selectedVendorData = v;
            // hide suggestions
            vendorSuggestionsContainer.style.display = 'none';
        });
    }
    // Keyboard navigation for suggestions
    let activeIndex = -1;
    document.getElementById('vendorSearch').addEventListener('keydown', function(e) {
        const suggBox = document.getElementById('vendorSuggestionsList');
        const items = suggBox.querySelectorAll('.sugg-item');
        if (suggBox.style.display === 'none' || items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIndex = (activeIndex + 1) % items.length;
            updateActiveSuggestion(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIndex = (activeIndex - 1 + items.length) % items.length;
            updateActiveSuggestion(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (activeIndex >= 0 && activeIndex < items.length) {
                items[activeIndex].click();
            }
        }
    });

    function updateActiveSuggestion(items) {
        items.forEach((item, index) => {
            if (index === activeIndex) {
                item.classList.add('bg-gray-300');
                item.scrollIntoView({
                    block: 'nearest'
                });
            } else {
                item.classList.remove('bg-gray-300');
            }
        });
    }
    //clear suggestions when clicking outside
    document.addEventListener('click', function(event) {
        const suggBox = document.getElementById('vendorSuggestionsList');
        const vendorSearchInput = document.getElementById('vendorSearch');
        if (!suggBox.contains(event.target) && event.target !== vendorSearchInput) {
            suggBox.style.display = 'none';
        }
        if (event.target === vendorSearchInput) {
            if (suggBox.children.length > 0) {
                suggBox.style.display = 'block';
            }
        }
    });

    // Add vendor button -> save mapping via AJAX
    const addVendorButton = document.getElementById('addVendorButton');
    if (addVendorButton) {
        addVendorButton.addEventListener('click', function(e) {
            e.preventDefault();
            const itemText = document.getElementById('item_code_mapped').innerText || '';
            const item_code = itemText.split(':').slice(1).join(':').trim();
            const vendor_id = document.getElementById('vendorId').value;
            const vendor_code = document.getElementById('vendorSearch').dataset.vendorCode || '';
            if (!vendor_id || vendor_id === '') {
                alert('Please select a vendor from the suggestions list.');
                return;
            }
            const btn = this;
            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Saving...';

            fetch('?page=products&action=add_vendor_map', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        item_code: item_code,
                        vendor_id: vendor_id,
                        vendor_code: vendor_code
                    })
                })
                .then(r => r.json())
                .then(data => {
                    if (data && data.success) {
                        // refresh vendor list in modal
                        const modalContent = document.getElementById('vendor-modal-content');
                        fetch(`?page=products&action=get_vendor_edit_form&item_code=${encodeURIComponent(item_code)}&current_vendor=${encodeURIComponent(document.getElementById('current_vendor_mapped').innerText)}`)
                            .then(r => r.text()).then(html => {
                                modalContent.innerHTML = html;
                            });
                    } else {
                        alert((data && data.message) ? data.message : 'Failed to save vendor mapping.');
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Error saving vendor mapping.');
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = originalText || '+ Add';
                });
        });
    }
</script>
<script>
    // Global function to update vendor priority via AJAX
    window.updateVendorPriority = function(id, item_code, priority, el) {
        // try {
        //     el = el || document.querySelector('[data-vendor-id="' + id + '"] select');
        // } catch (e) {}
        // if (!el) {
        //     // fallback: find select by vendor id attribute
        //     el = document.querySelector('[data-vendor-id="' + id + '"] select');
        // }
        // if (el) el.disabled = true;
        var params = new URLSearchParams();
        params.append('id', id);
        params.append('priority', priority);
        fetch('<?php echo base_url('?page=products&action=updatePriority') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: params.toString()
        }).then(function(r) {
            return r.json();
        }).then(function(data) {
            if (el) {
                el.disabled = false;
                if (data && data.success) {
                    el.classList.add('ring-4', 'ring-green-300');
                    setTimeout(function() {
                        el.classList.remove('ring-4', 'ring-green-300');
                    }, 1200);
                    const modalContent = document.getElementById('vendor-modal-content');
                    fetch(`?page=products&action=get_vendor_edit_form&item_code=${encodeURIComponent(item_code)}&current_vendor=${encodeURIComponent(document.getElementById('current_vendor_mapped').innerText)}`)
                        .then(r => r.text()).then(html => {
                            modalContent.innerHTML = html;
                        });
                } else {
                    alert('Unable to update priority: ' + (data && data.message ? data.message : ''));
                }
            }
        }).catch(function(err) {
            if (el) {
                el.disabled = false;
            }
            alert('Network error');
        });
    };
</script>
<script>
    // Helper to get selected product objects (id, image, item_code, sku)
    function getSelectedProductIds() {
        const checkboxes = document.querySelectorAll('input[name="product_select[]"]:checked');
        //const element = document.querySelector('#order-id-' + id);
        //get product data from data-product attribute

        const products = Array.from(checkboxes).map(checkbox => {
            const row = checkbox.closest('tr');
            const productData = row ? JSON.parse(row.getAttribute('data-product') || '{}') : {};
            return {
                id: productData.id || null,
                image: productData.image || '',
                item_code: productData.item_code || productData.itemcode || '',
                sku: productData.sku || productData.item_code || productData.itemcode || ''
            };
        });
        return products;
    }
    // Toggle bulk actions menu
    document.getElementById('bulk-action-toggle').addEventListener('click', function(e) {
        e.stopPropagation();
        const menu = document.getElementById('bulk-action-menu');
        menu.classList.toggle('hidden');
    });

    // Close menu on outside click
    document.addEventListener('click', function(e) {
        const menu = document.getElementById('bulk-action-menu');
        const toggle = document.getElementById('bulk-action-toggle');
        if (!toggle.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add('hidden');
        }
    });

    // Transfer Stock handler
    document.getElementById('action-transfer-stock').addEventListener('click', function(e) {
        e.preventDefault();
        const products = getSelectedProductIds();
        if (products.length === 0) {
            showAlert('Please select at least one product for stock transfer.', 'warning');
            return;
        }

        const productIds = products.map(p => p.id).join(',');
        window.location.href = '?page=products&action=transfer_stock&product_ids=' + encodeURIComponent(productIds);
    });


    // Bulk Assign handlers
    document.getElementById('action-assign-to').addEventListener('click', function(e) {
        e.preventDefault();
        const products = getSelectedProductIds();
        console.log('Selected products to create purchase list:', products);
        if (products.length === 0) {
            showAlert('Please select at least one product to create purchase list.', 'warning');
            return;
        }
        const form = document.getElementById('bulkAssignForm');
        form.querySelectorAll('input.poitem_hidden').forEach(el => el.remove());
        products.forEach(p => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'poitem[]';
            input.value = p.id;
            input.className = 'poitem_hidden';
            form.appendChild(input);
            // also include sku for each product so backend can store it
            const skuInput = document.createElement('input');
            skuInput.type = 'hidden';
            skuInput.name = 'sku[]';
            skuInput.value = p.sku || p.item_code || '';
            skuInput.className = 'poitem_hidden';
            form.appendChild(skuInput);
        });
        // populate selected items list with image and sku/item_code
        const selectedItemsContainer = document.getElementById('bulkAssignSelectedItems');
        selectedItemsContainer.innerHTML = '';
        products.forEach(p => {
            const div = document.createElement('div');
            div.classList.add('rounded-md', 'flex-shrink-0', 'flex', 'flex-col', 'items-center', 'justify-start', 'bg-gray-50', 'overflow-hidden', 'w-32', 'h-32', 'm-2', 'mb-2', 'text-center', 'p-2');
            // image
            const img = document.createElement('img');
            img.src = p.image || 'default-image.png';
            img.classList.add('max-w-full', 'h-24', 'object-contain');
            div.appendChild(img);
            // sku / item code label
            const label = document.createElement('div');
            label.classList.add('text-sm', 'mt-2', 'break-words');
            label.textContent = p.sku || p.item_code || ('ID ' + (p.id || ''));
            div.appendChild(label);
            // append hidden product id
            // const hiddenInput = document.createElement('input');
            // hiddenInput.type = 'hidden';
            // hiddenInput.name = 'order_ids[]';
            // hiddenInput.value = p.id;
            // div.appendChild(hiddenInput);
            selectedItemsContainer.appendChild(div);
        });
        document.getElementById('bulkAssignError').classList.add('hidden');
        document.getElementById('bulkAssignPopup').classList.remove('hidden');
    });

    function closeBulkAssignPopup(e) {
        document.getElementById('bulkAssignPopup').classList.add('hidden');
    }

    //bulk assign submit
    document.getElementById('bulkAssignForm').addEventListener('submit', function(e) {
        const agent = document.getElementById('bulkAssignAgent').value;
        if (!agent) {
            e.preventDefault();
            document.getElementById('bulkAssignError').textContent = 'Please select an agent.';
            document.getElementById('bulkAssignError').classList.remove('hidden');
            return;
        }
        //ajax submit
        document.getElementById('bulkAssignError').textContent = 'Processing..'
        document.getElementById('bulkAssignError').classList.remove('hidden');
        e.preventDefault();
        const formData = new FormData(this);
        fetch('index.php?page=products&action=create_purchase_list', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    //alert(data.message);
                    document.getElementById('bulkAssignError').classList.remove('text-red-500');
                    document.getElementById('bulkAssignError').classList.add('text-green-500');
                    document.getElementById('bulkAssignError').textContent = 'Purchase List created successfully.';
                    //poitem clear from localStorage
                    //localStorage.removeItem('selected_po_orders');

                    //timeout to close popup and reload
                    setTimeout(() => {
                        closeBulkAssignPopup();
                        location.reload();
                    }, 3000);
                    //bulkStatusError.classList.remove('hidden');
                    //location.reload();
                } else {
                    alert(data.message);
                }
            });
    });

    // Edit Product Modal Functions
    function toggleBackorderFields() {
        const flag = document.getElementById('editBackorderFlag').value;
        const percentContainer = document.getElementById('containerBackorderPercent');
        const weeksContainer = document.getElementById('containerBackorderWeeks');
        if (flag === '1') {
            percentContainer.classList.remove('hidden');
            weeksContainer.classList.remove('hidden');
        } else {
            percentContainer.classList.add('hidden');
            weeksContainer.classList.add('hidden');
            document.getElementById('editBackorderPercent').value = '';
            document.getElementById('editBackorderWeeks').value = '';
        }
    }

    function openEditModal(productData) {
        const modal = document.getElementById('editProductModal');

        // Populate read-only fields
        document.getElementById('editProductId').value = productData.id || '';
        document.getElementById('editItemCode').value = productData.item_code || '';
        document.getElementById('editSku').value = productData.sku || '';
        document.getElementById('editSize').value = productData.size || '';
        document.getElementById('editColor').value = productData.color || '';

        // Populate editable fields
        document.getElementById('editTitle').value = productData.title || '';
        document.getElementById('editDescription').value = productData.description || '';
        document.getElementById('editPrice').value = productData.price || '';
        document.getElementById('editPriceIndia').value = productData.price_india || '';
        document.getElementById('editVendor').value = productData.vendor || '';
        //document.getElementById('editImage').value = productData.image || '';
        document.getElementById('editGst').value = productData.gst || '';
        document.getElementById('editGroupName').value = productData.groupname || '';

        //document.getElementById('editCategory').value = productData.category || '';
        document.getElementById('editItemtype').value = productData.itemtype || '';
        document.getElementById('editSnippetDescription').value = productData.snippet_description || '';
        document.getElementById('editIndiaNetQty').value = productData.india_net_qty || '';
        document.getElementById('editKeywords').value = productData.keywords || '';
        const usblockVal = productData.usblock !== undefined && productData.usblock !== null ? productData.usblock.toString() : '';
        document.querySelectorAll('input[name="usblock"]').forEach(r => r.checked = (r.value === usblockVal));
        const indiablockVal = productData.indiablock !== undefined && productData.indiablock !== null ? productData.indiablock.toString() : '';
        document.querySelectorAll('input[name="indiablock"]').forEach(r => r.checked = (r.value === indiablockVal));
        document.getElementById('editHscode').value = productData.hscode || '';
        document.getElementById('editDateFirstAdded').value = productData.date_first_added || '';
        document.getElementById('editSearchTerm').value = productData.search_term || '';
        document.getElementById('editSearchCategory').value = productData.search_category || '';
        document.getElementById('editLongDescription').value = productData.long_description || '';
        document.getElementById('editLongDescriptionIndia').value = productData.long_description_india || '';
        document.getElementById('editAplusContentIds').value = productData.aplus_content_ids || '';
        document.getElementById('editMaterial').value = productData.material || '';
        document.getElementById('editItemLevel').value = productData.item_level || '';
        document.getElementById('editMarketplaceVendor').value = productData.marketplace_vendor || '';
        document.getElementById('editColormap').value = productData.colormap || '';
        document.getElementById('editFlexStatus').value = productData.flex_status || '';
        document.getElementById('editVendorUs').value = productData.vendor_us || '';
        document.getElementById('editPriceIndiaSuggested').value = productData.price_india_suggested || '';
        document.getElementById('editMrpIndia').value = productData.mrp_india || '';
        document.getElementById('editPermanentDiscount').value = productData.permanent_discount || '';
        document.getElementById('editDiscountGlobal').value = productData.discount_global || '';
        document.getElementById('editTodayGlobal').value = productData.today_global || '';
        document.getElementById('editDiscountIndia').value = productData.discount_india || '';
        document.getElementById('editTodayIndia').value = productData.today_india || '';
        document.getElementById('editTopurchase').value = productData.topurchase || '';
        const backorderPercent = productData.backorder_percent || 0;
        const backorderWeeks = productData.backorder_weeks || 0;
        document.getElementById('editBackorderPercent').value = productData.backorder_percent || '';
        document.getElementById('editBackorderWeeks').value = productData.backorder_weeks || '';
        document.getElementById('editBackorderFlag').value = (backorderPercent > 0 || backorderWeeks > 0) ? '1' : '0';
        toggleBackorderFields();
        document.getElementById('editLeadtime').value = productData.leadtime || '';
        document.getElementById('editInstockLeadtime').value = productData.instock_leadtime || '';
        document.getElementById('editCp').value = productData.cp || '';
        document.getElementById('editUsd').value = productData.usd || '';
        document.getElementById('editPermanentlyAvailable').value = productData.permanently_available || '';
        document.getElementById('editAmazonSold').value = productData.amazon_sold || '';
        document.getElementById('editAmazonLeadtime').value = productData.amazon_leadtime || '';
        document.getElementById('editAmazonItemcodeAlias').value = productData.amazon_itemcode_alias || '';
        document.getElementById('editYoutubeLinks').value = productData.youtube_links || '';
        document.getElementById('editSketchfabLinks').value = productData.sketchfab_links || '';
        document.getElementById('editDimensions').value = productData.dimensions || '';

        modal.classList.remove('hidden');
    }

    function closeEditModal() {
        const modal = document.getElementById('editProductModal');
        modal.classList.add('hidden');
    }

    // Save Product Changes
    document.getElementById('editProductForm').addEventListener('submit', function(e) {
        e.preventDefault();

        const productId = document.getElementById('editProductId').value;
        const formData = new FormData(this);

        // Show loading state
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving...';

        fetch('?page=products&action=update_product', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Product updated successfully!');
                    closeEditModal();
                    // Reload page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                } else {
                    alert('Error: ' + (data.message || 'Failed to update product'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving product: ' + error.message);
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
    });

    // Open edit modal when Modify button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.openEditModal').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const productId = this.getAttribute('data-product-id');
                const itemCode = this.getAttribute('data-item-code');

                // Get the product data from the table row
                const row = this.closest('tr');
                const productDataJson = row.getAttribute('data-product');

                if (productDataJson) {
                    try {
                        const productData = JSON.parse(productDataJson);
                        openEditModal(productData);
                    } catch (err) {
                        console.error('Error parsing product data:', err);
                        alert('Error loading product data');
                    }
                } else {
                    alert('Product data not found');
                }
            });
        });
    });
</script>