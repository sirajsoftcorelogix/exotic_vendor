
<div class="container mx-auto p-4">

    <!-- Advance Search Accordion -->
    <div class="mb-8">
        <button id="accordion-button" class="w-full flex justify-between items-center mb-6">
            <h2 class="text-xl font-bold text-gray-900">Advance Search</h2>
            <svg id="accordion-icon" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content" class="accordion-content hidden">
            <!-- Responsive Grid container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">

                <!-- Orders From/Till -->
                <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="orders-from" class="block text-sm font-medium text-gray-600 mb-1">Orders From</label>
                        <input type="date" id="orders-from" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="orders-till" class="block text-sm font-medium text-gray-600 mb-1">Orders Till</label>
                        <input type="date" id="orders-till" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <!-- PO Date -->
                <div>
                    <label for="po-date" class="block text-sm font-medium text-gray-600 mb-1">PO Date</label>
                    <input type="text" id="po-date" placeholder="PO Date" onfocus="(this.type='date')" onblur="(this.type='text')" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Receipt Date -->
                <div>
                    <label for="receipt-date" class="block text-sm font-medium text-gray-600 mb-1">Receipt Date</label>
                    <input type="text" id="receipt-date" placeholder="Receipt Date" onfocus="(this.type='date')" onblur="(this.type='text')" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Order Number -->
                <div>
                    <label for="order-number" class="block text-sm font-medium text-gray-600 mb-1">Order Number</label>
                    <input type="text" id="order-number" placeholder="Order Number" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <select id="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="" disabled selected>Status</option>
                        <option>Pending</option>
                        <option>In Progress</option>
                        <option>Completed</option>
                        <option>Disputed</option>
                        <option>Returned</option>
                    </select>
                </div>

                <!-- Min/Max Amount -->
                <div class="col-span-1 sm:col-span-2 md:col-span-1 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="min-amount" class="block text-sm font-medium text-gray-600 mb-1">Min Amount</label>
                        <input type="number" id="min-amount" placeholder="Min Amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <div class="w-1/2">
                        <label for="max-amount" class="block text-sm font-medium text-gray-600 mb-1">Max Amount</label>
                        <input type="number" id="max-amount" placeholder="Max Amount" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>

                <!-- Customer -->
                <div>
                    <label for="customer" class="block text-sm font-medium text-gray-600 mb-1">Customer</label>
                    <input type="text" id="customer" placeholder="Customer" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Item Name -->
                <div>
                    <label for="item-name" class="block text-sm font-medium text-gray-600 mb-1">Item Name</label>
                    <input type="text" id="item-name" placeholder="Item Name" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>

                <!-- Buttons -->
                <div class="col-span-1 sm:col-span-2 md:col-span-1 flex items-center gap-2">
                    <button class="w-full bg-amber-600 text-white font-semibold py-2 px-4 rounded-md shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition duration-150">Search</button>
                    <button id="clear-button" class="w-full bg-gray-800 text-white font-semibold py-2 px-4 rounded-md shadow-sm hover:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-700 transition duration-150">Clear</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table Section -->
    <div class="mt-5">
        <form action="<?php echo base_url('?page=purchase_orders&action=create'); ?>" method="post">
        <button type="submit" onclick="checkPoItmes()" class="btn btn-success">Create PO</button>
    

        <!-- Tabs -->
        <div class="relative border-b-[4px] border-white">
            <div id="tabsContainer" class="flex space-x-8" aria-label="Tabs">
                <a href="#" class="tab tab-active text-center relative py-4">
                    <span class="px-1 text-sm">All Orders</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">No PO</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">PO Sent</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">In Progress</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">Late</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">Received</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">Disputed</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
                <a href="#" class="tab text-gray-500 hover:text-gray-700 text-center relative py-4">
                    <span class="px-1 text-sm">Returned</span>
                    <div class="underline-pill w-full absolute left-0 bottom-[-4px]"></div>
                </a>
            </div>
        </div>

        <!-- Table -->
        <div class="overflow-x-auto mt-4 h-96 overflow-y-scroll">
            <table class="min-w-full table-spacing ">
                <thead class="bg-gray-50 rounded-md ">
                <tr class="text-left text-xs font-medium text-gray-500 uppercase tracking-wider sticky">
                    <th class="p-4">#</th>
                    <th class="px-6 py-3">Order Date</th>
                    <th class="px-6 py-3">Order ID</th>
                    <!-- <th class="px-6 py-3">Customer</th>
                    <th class="px-6 py-3">Vendor Name</th> -->
                    <th class="px-6 py-3">Item</th>
                    <th class="px-6 py-3">Image</th>
                    <th class="px-6 py-3">Status</th>
                    <!-- <th class="px-6 py-3">PO Date</th> -->
                    <!-- <th class="px-6 py-3">Receipt Due</th> -->
                    <!-- <th class="px-6 py-3">Staff</th> -->
                    <th class="px-6 py-3">Amount</th>
                    <th class="px-6 py-3">Unit</th>
                    <th class="px-6 py-3">Location</th>
                    <!-- <th class="relative px-6 py-3"><span class="sr-only">Actions</span></th> -->
                </tr>
                </thead>
                <tbody>
                <!-- Table Row 1 -->
                 <?php 
                    if (!empty($data['orders'])) {
                        foreach ($data['orders'] as $order) { 
                    ?> 
                <tr class="bg-white rounded-md shadow-sm" data-id="<?= $order['id'] ?>">
                    <td class="p-4 whitespace-nowrap rounded-l-md"><input type="checkbox" name="poitem[]" value="<?=$order['id']?>" class="h-4 w-4 text-amber-600 border-gray-300 rounded focus:ring-amber-500">  <?= $order['id'] ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-amber-600">21/05/25</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <a href="#" class="order-detail-link" 
                               data-order='<?= htmlspecialchars(json_encode($order), ENT_QUOTES, 'UTF-8') ?>'>
                               <?= htmlspecialchars($order['order_number']) ?>
                            </a>
                    </td>
                    <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Swati Nagar</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="#" class="icon-link">
                            <span>Kuber</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.0462 8.20171C10.6471 9.47037 9.33805 11.0991 7.90051 12.3041C7.56557 12.5845 7.25392 12.7388 6.81226 12.7978C6.09737 12.8939 5.0914 12.9659 4.3672 12.9883C3.50483 13.0154 2.73561 12.5712 2.75424 11.6359C2.7686 10.909 2.86756 9.93098 2.95139 9.19835C2.99176 8.84595 3.04959 8.53545 3.24558 8.2299L11.1583 0.415632C11.9224 -0.178697 12.8027 -0.120026 13.5276 0.491828C14.0919 0.968052 15.0964 1.93688 15.5629 2.49426C16.1481 3.19335 16.1419 4.07837 15.5629 4.77785C14.5837 5.96041 13.1027 7.05649 12.0459 8.20209L12.0462 8.20171ZM12.257 1.03396C12.1433 1.04272 11.9911 1.11244 11.8968 1.17873C11.5141 1.44732 11.1361 2.00355 10.7523 2.30224L13.6763 5.13787C14.0908 4.59726 15.3762 3.97665 14.7692 3.19678C14.239 2.51559 13.299 1.87897 12.7316 1.19664C12.6109 1.0972 12.4157 1.02139 12.2566 1.03396H12.257ZM3.89255 11.8744C3.93796 11.9216 4.0998 11.9635 4.17121 11.962C4.89619 11.9464 5.93204 11.858 6.65663 11.7692C6.78664 11.7532 6.92675 11.7174 7.03891 11.6492L12.869 5.94022L9.99472 3.04591L4.13628 8.79985C4.00626 8.99529 3.98492 9.58505 3.96008 9.84602C3.91506 10.323 3.85607 10.8968 3.84171 11.368C3.83821 11.4842 3.81997 11.7989 3.89216 11.8744H3.89255Z" fill="#D06706"/><path d="M2.04958 2.33194C3.16732 2.2085 4.46941 2.40014 5.60695 2.32394C6.18289 2.447 6.14176 3.26687 5.56736 3.34687C4.59787 3.48174 3.31946 3.26344 2.30922 3.34878C1.6281 3.4063 1.1127 3.92444 1.04788 4.58696V13.695C1.10687 14.4322 1.64634 14.9138 2.38684 14.9713H11.5488C13.652 14.8079 12.6526 11.8801 12.8886 10.5337C13.0523 9.99611 13.7703 9.99839 13.9326 10.5337C13.8247 12.6089 14.6599 15.6335 11.7045 16.0003H2.2316C1.06845 15.9165 0.137389 15.0174 0 13.8859L0.00620967 4.36409C0.140494 3.35906 1.00791 2.447 2.04997 2.33194H2.04958Z" fill="#D06706"/></svg>
                        </a>
                    </td> -->
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs"><?= htmlspecialchars($order['title']) ?></td>
                    <td class="px-6 py-4"><img class="h-12 w-12 rounded-md object-cover" src="<?= htmlspecialchars($order['image']) ?>" alt=""></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="#" class="icon-link">
                            <span><?= htmlspecialchars($order['status']) ?></span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12.0462 8.20171C10.6471 9.47037 9.33805 11.0991 7.90051 12.3041C7.56557 12.5845 7.25392 12.7388 6.81226 12.7978C6.09737 12.8939 5.0914 12.9659 4.3672 12.9883C3.50483 13.0154 2.73561 12.5712 2.75424 11.6359C2.7686 10.909 2.86756 9.93098 2.95139 9.19835C2.99176 8.84595 3.04959 8.53545 3.24558 8.2299L11.1583 0.415632C11.9224 -0.178697 12.8027 -0.120026 13.5276 0.491828C14.0919 0.968052 15.0964 1.93688 15.5629 2.49426C16.1481 3.19335 16.1419 4.07837 15.5629 4.77785C14.5837 5.96041 13.1027 7.05649 12.0459 8.20209L12.0462 8.20171ZM12.257 1.03396C12.1433 1.04272 11.9911 1.11244 11.8968 1.17873C11.5141 1.44732 11.1361 2.00355 10.7523 2.30224L13.6763 5.13787C14.0908 4.59726 15.3762 3.97665 14.7692 3.19678C14.239 2.51559 13.299 1.87897 12.7316 1.19664C12.6109 1.0972 12.4157 1.02139 12.2566 1.03396H12.257ZM3.89255 11.8744C3.93796 11.9216 4.0998 11.9635 4.17121 11.962C4.89619 11.9464 5.93204 11.858 6.65663 11.7692C6.78664 11.7532 6.92675 11.7174 7.03891 11.6492L12.869 5.94022L9.99472 3.04591L4.13628 8.79985C4.00626 8.99529 3.98492 9.58505 3.96008 9.84602C3.91506 10.323 3.85607 10.8968 3.84171 11.368C3.83821 11.4842 3.81997 11.7989 3.89216 11.8744H3.89255Z" fill="#D06706"/><path d="M2.04958 2.33194C3.16732 2.2085 4.46941 2.40014 5.60695 2.32394C6.18289 2.447 6.14176 3.26687 5.56736 3.34687C4.59787 3.48174 3.31946 3.26344 2.30922 3.34878C1.6281 3.4063 1.1127 3.92444 1.04788 4.58696V13.695C1.10687 14.4322 1.64634 14.9138 2.38684 14.9713H11.5488C13.652 14.8079 12.6526 11.8801 12.8886 10.5337C13.0523 9.99611 13.7703 9.99839 13.9326 10.5337C13.8247 12.6089 14.6599 15.6335 11.7045 16.0003H2.2316C1.06845 15.9165 0.137389 15.0174 0 13.8859L0.00620967 4.36409C0.140494 3.35906 1.00791 2.447 2.04997 2.33194H2.04958Z" fill="#D06706"/></svg>
                        </a>
                    </td>
                    <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <a href="#" class="icon-link">
                            <span>22/05/25</span>
                            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M0.145379 9.40113L7.21764 12.2427C7.11773 12.6084 7.20811 13.1939 7.31144 13.5665C7.70191 14.976 9.31564 15.1497 10.2487 14.1642C11.2584 13.0983 12.3715 9.54856 12.9031 8.0457C13.5048 6.34437 14.0607 4.62284 14.6072 2.90284L6.58847 1.32646L3.68208 9.24418C3.27445 9.59503 2.69829 9.37865 2.62012 8.84913L5.66721 0.375224C5.78847 0.0997958 6.0573 -0.0274423 6.3551 0.00493869L15.7034 1.90132C16.2632 2.2057 15.8846 2.96075 15.745 3.43999C15.2249 5.22246 14.5404 7.11084 13.9135 8.86627C13.3576 10.4232 12.7429 12.3627 11.9654 13.7973C9.36406 18.5962 4.8245 14.2286 1.34957 13.0872C0.0736919 12.2743 -0.218777 10.8011 0.145379 9.40113Z" fill="#D06706"/><path d="M6.94735 9.09858C7.09759 9.06506 7.20321 9.08677 7.34735 9.1142C7.79577 9.19954 9.73248 9.81477 10.1039 10.0193C10.7056 10.3504 10.4795 11.2593 9.65469 11.1626C9.34544 11.1264 7.14563 10.3904 6.85736 10.2342C6.35593 9.96182 6.43219 9.21363 6.94697 9.09858H6.94735Z" fill="#D06706"/><path d="M8.02384 6.57367C8.22479 6.53938 8.40553 6.62357 8.59238 6.67157C9.03508 6.78548 10.8848 7.3569 11.18 7.5569C11.7226 7.92452 11.4454 8.73938 10.6652 8.63843C10.2877 8.58967 8.24996 7.91919 7.90982 7.73252C7.33632 7.41748 7.46559 6.6689 8.02422 6.57367H8.02384Z" fill="#D06706"/><path d="M8.75214 5.02982C8.38875 4.59477 8.73384 4.00163 9.33174 4.03744C9.73288 4.06144 11.7207 4.61267 12.1249 4.78296C12.7933 5.06448 12.637 5.93687 11.9293 5.98944C11.3786 5.7502 9.04957 5.38601 8.75214 5.0302V5.02982Z" fill="#D06706"/><path d="M6.88249 6.20261C7.34007 6.69594 6.80432 7.5009 6.08058 7.16147C5.17038 6.73442 6.2007 5.46737 6.88249 6.20261Z" fill="#D06706"/><path d="M5.2448 8.40496C6.19847 8.25067 6.3304 9.56001 5.46596 9.64039C4.70485 9.71124 4.43564 8.53601 5.2448 8.40496Z" fill="#D06706"/><path d="M6.86078 3.61815C7.35535 3.11872 8.34029 3.71491 7.91436 4.38691C7.42513 5.15872 6.22933 4.25586 6.86078 3.61815Z" fill="#D06706"/></svg>
                        </a>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">20/06/25</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">Mukul</td> -->
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">₹<?= htmlspecialchars($order['total_price']) ?></td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?= htmlspecialchars($order['quantity']) ?></td>
                    <td class="px-6 py-4 text-sm text-gray-500 max-w-xs"><?= $order['shipping_address'] ?></td>
                    <!-- <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium rounded-r-md">
                        <a href="#" class="text-gray-500 hover:text-gray-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" /></svg>
                        </a>
                    </td> -->
                </tr>
                <?php
                        }
                    } else {
                        echo "<tr><td colspan='10' class='text-center'>No orders found.</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        </form>
        <!-- Pagination -->
         <?php
            $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
            $page = $page < 1 ? 1 : $page;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page, default 20
            $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values
            $total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
            $total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
            ?>
        <div id="pagination-controls" class="flex justify-center items-center space-x-4 mt-8">
            <?php if ($total_pages > 1): ?>
            <span class="text-gray-600">Page</span>
            <button id="prev-page" class="text-gray-600 hover:text-gray-900">
                <a class="page-link" href="?page=orders&page_no=<?= $page-1 ?>&limit=<?= $limit ?>" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            </button>
            <?php /*for ($i = 1; $i <= $total_pages; $i++): ?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><a class="page-link" href="?page=orders&page_no=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a></span>
            <?php endfor; */?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page ?></span>

            <button id="next-page" class="text-gray-600 hover:text-gray-900">
                <a class="page-link" href="?page=orders&page_no=<?= $page+1 ?>&limit=<?= $limit ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </button>
            <?php endif; ?>
            <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                    onchange="location.href='?page=orders&page_no=1&limit=' + this.value;">
                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                    <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                        <?= $opt ?>
                    </option>
                <?php endforeach; ?>
            </select>

        </div>

    </div>
</div>
<!-- Order Details Popup Modal -->
<div class="fixed inset-y-0 right-0 w-[400px] bg-white shadow-lg p-4" id="orderDetailOffcanvas" style="display: none; z-index: 1000;">
  <!-- Popup content goes here -->
  <h2 class="text-xl font-bold mb-4">Order Details</h2>
  <div id="orderDetailOffcanvasBody">
      <!-- Order details will be populated here -->
  </div>
  <button class="mt-4 bg-blue-500 text-white px-4 py-2 rounded">Close</button>
</div>

<script>
    function checkPoItmes() {
        const checkedRows = document.querySelectorAll('input[name="poitem[]"]:checked');
        if (checkedRows.length === 0) {
            alert("Please select at least one order to create a Purchase Order.");
            event.preventDefault(); // Prevent form submission
            return false;
        }
        return true; // Allow form submission if at least one item is checked
    }
    // Popup for order detail
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.order-detail-link').forEach(function(link) {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const order = JSON.parse(this.getAttribute('data-order'));
                let html = `
                    <p><strong>Order Number:</strong> ${order.order_number}</p>
                    <p><strong>Title:</strong> ${order.title}</p>
                    <p><strong>Item Code:</strong> ${order.item_code}</p>
                    <p><strong>Size:</strong> ${order.size}</p>
                    <p><strong>Color:</strong> ${order.color}</p>
                    <p><strong>Marketplace Vendor:</strong> ${order.marketplace_vendor}</p>
                    <p><strong>Quantity:</strong> ${order.quantity}</p>
                    <p><strong>Status:</strong> ${order.status}</p>
                `;
                document.getElementById('orderDetailOffcanvasBody').innerHTML = html;
                document.getElementById('orderDetailOffcanvas').style.display = 'block';
                
            });
        });
    });
    // Close button functionality
    document.getElementById('orderDetailOffcanvas').querySelector('button').addEventListener('click', function() {
        document.getElementById('orderDetailOffcanvas').style.display = 'none';
    });
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
        const tabs = document.querySelectorAll('.tab');
        tabs.forEach(tab => {
            tab.addEventListener('click', function (event) {
                event.preventDefault();
                tabs.forEach(t => t.classList.remove('tab-active'));
                this.classList.add('tab-active');
            });
        });

        // Date validation and clear functionality
        const fromDateInput = document.getElementById('orders-from');
        const toDateInput = document.getElementById('orders-till');
        const clearButton = document.getElementById('clear-button');

        fromDateInput.addEventListener('input', function() {
            if (fromDateInput.value) {
                toDateInput.min = fromDateInput.value;
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
    });
</script>