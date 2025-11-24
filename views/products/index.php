
<div class="container mx-auto p-4">
    <!-- Top Bar: Search & Advance Search -->
   

    <!-- Orders Table Section -->
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
        </div>

        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full table-spacing">
                <thead class="bg-gray-50 rounded-md">
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                    <th class="p-4 w-12"><input type="checkbox" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500"></th>
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
                <tr class="bg-white rounded-md shadow-sm">
                    <!-- Checkbox -->
                    <td class="p-4 whitespace-nowrap rounded-l-md align-top pt-6">
                        <input type="checkbox" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500" value="<?php echo $product['id']; ?>">
                    </td>

                    <!-- SKU Details -->
                    <td class="px-6 py-4 whitespace-nowrap rounded-l-md align-top">
                        <div class="flex flex-col">
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">SKU :</span>
                                <span class="typo-sku-value">STG8721-Beige</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">ASIN :</span>
                                <span class="typo-sku-value">B0091YZ55I</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="typo-sku-label w-[70px]">Location :</span>
                                <span class="typo-sku-value">First Floor C21</span>
                            </div>
                        </div>
                    </td>

                    <!-- Product Details -->
                    <td class="px-6 py-4 align-top">
                        <div class="flex items-start space-x-4">
                            <img class="h-20 w-16 rounded-md object-cover flex-shrink-0" src="<?php echo $product['image'];?>" alt="Product Image">
                            <div class="flex flex-col justify-between h-full">
                                <p class="typo-product-title mb-2 max-w-xs"><?php echo $product['title']; ?></p>
                                <div class="flex items-center mt-auto">
                                    <span class="typo-vendor">Vendor : Kuber</span>
                                    <a href="#" class="ml-2 text-details-link hover:text-amber-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </td>

                    <!-- S & F Sum -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <div class="flex flex-col space-y-1">
                            <span class="typo-sf-column">₹<?php echo $product['finalprice']; ?></span>
                            <span class="typo-sf-column">622 units</span>
                            <a href="#" class="typo-sf-column text-details-link mt-1">details</a>
                        </div>
                    </td>

                    <!-- Inventory Overview -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <div class="flex flex-col space-y-1">
                            <span class="typo-sf-column">Local Stock : 277</span>
                            <div class="typo-sf-column">
                                FBA (US): <span class="text-healthy">Healthy</span>
                            </div>
                            <a href="#" class="typo-sf-column text-details-link mt-1">details</a>
                        </div>
                    </td>

                    <!-- Price -->
                    <td class="px-6 py-4 whitespace-nowrap align-top">
                        <span class="typo-sf-column">₹23,000</span>
                    </td>

                    <!-- Recommended Action -->
                    <td class="px-6 py-4 whitespace-nowrap text-right align-top rounded-r-md">
                        <button class="bg-create-po typo-create-po px-4 py-2 rounded-full flex items-center gap-2 ml-auto transition shadow-sm">
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M0.145379 9.40113L7.21764 12.2427C7.11773 12.6084 7.20811 13.1939 7.31144 13.5665C7.70191 14.976 9.31564 15.1497 10.2487 14.1642C11.2584 13.0983 12.3715 9.54856 12.9031 8.0457C13.5048 6.34437 14.0607 4.62284 14.6072 2.90284L6.58847 1.32646L3.68208 9.24418C3.27445 9.59503 2.69829 9.37865 2.62012 8.84913L5.66721 0.375224C5.78847 0.0997958 6.0573 -0.0274423 6.3551 0.00493869L15.7034 1.90132C16.2632 2.2057 15.8846 2.96075 15.745 3.43999C15.2249 5.22246 14.5404 7.11084 13.9135 8.86627C13.3576 10.4232 12.7429 12.3627 11.9654 13.7973C9.36406 18.5962 4.8245 14.2286 1.34957 13.0872C0.0736919 12.2743 -0.218777 10.8011 0.145379 9.40113Z" fill="white"/>
                                <path d="M6.94735 9.09858C7.09759 9.06506 7.20321 9.08677 7.34735 9.1142C7.79577 9.19954 9.73248 9.81477 10.1039 10.0193C10.7056 10.3504 10.4795 11.2593 9.65469 11.1626C9.34544 11.1264 7.14563 10.3904 6.85736 10.2342C6.35593 9.96182 6.43219 9.21363 6.94697 9.09858H6.94735Z" fill="white"/>
                                <path d="M8.02384 6.57367C8.22479 6.53938 8.40553 6.62357 8.59238 6.67157C9.03508 6.78548 10.8848 7.3569 11.18 7.5569C11.7226 7.92452 11.4454 8.73938 10.6652 8.63843C10.2877 8.58967 8.24996 7.91919 7.90982 7.73252C7.33632 7.41748 7.46559 6.6689 8.02422 6.57367H8.02384Z" fill="white"/>
                            </svg>
                            Create PO
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
        <div id="pagination-controls" class="flex justify-center items-center space-x-4 mt-8 mb-12">
            <span class="text-gray-600">Page</span>
            <button class="text-gray-600 hover:text-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
            <span class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg">1</span>
            <button class="text-gray-600 hover:text-gray-900">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
            <select class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600">
                <option value="10">10</option>
                <option value="20">20</option>
                <option value="30" selected>30</option>
            </select>
        </div>

    </div>
</div>  