<div class="mx-auto space-y-6 mr-4">
    <!-- <div class="p-8">
        
    </div> -->
    <!-- Page Header -->
    <!-- <div class="flex flex-wrap items-center justify-between gap-4 mt-5"> -->
        <!-- Header Section with Filters and Actions -->
        <!-- <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow">
           <span class="text-gray-600 font-medium">Purchase orders</span> -->
        
        <!-- Filters -->
            <!-- <form method="get" id="filterForm">
                <input type="hidden" name="page" value="purchase_orders">
                <input type="hidden" name="action" value="list">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 1H1L5.5 6.5V12L8.5 14V6.5L14 1Z" stroke="#797A7C" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-gray-600 font-medium">Filters:</span>
                    </div>
                    <div class="flex flex-wrap items-left gap-4">
                        <div class="relative flex items-left gap-2">
                            <input type="text" name="search_text" placeholder="Search by PO Number or Vendor" class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" style="width: 300px; height: 37px; border-radius: 5px;" value="<?php echo $data['search'] ?? '' ?>">
                        </div>
                    </div>
                     <div class="relative">
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="status_filter" id="status_filter">
                            <option value="" selected>All Status</option>
                            <option value="draft" <?php echo ($data['status_filter'] == "draft") ? "selected" : ""?>>Draft</option>
                            <option value="pending" <?php echo ($data['status_filter'] == "pending") ? "selected" : ""?>>Pending</option>
                            <option value="ordered" <?php echo ($data['status_filter'] == "ordered") ? "selected" : ""?>>Ordered</option>
                            <option value="received" <?php echo ($data['status_filter'] == "received") ? "selected" : ""?>>Received</option>
                            <option value="cancelled" <?php echo ($data['status_filter'] == "cancelled") ? "selected" : ""?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=purchase_orders&action=list';">
                    </div>
                </div>
            </form> -->
        <!-- </div> -->
        <!-- Advance Search Accordion -->
    <div class="mt-6 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Purchase Order Advanced Search</h2>
            <svg id="accordion-icon" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content" class="accordion-content hidden">
            <!-- Responsive Grid container -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">
                <form method="GET" onsubmit="submitSearchForm()" class="contents">
                <!-- Orders From/Till -->
                <div class="col-span-1 sm:col-span-2 md:col-span-3 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="po-from" class="block text-sm font-medium text-gray-600 mb-1">Order From</label>
                        <input type="date" value="<?= htmlspecialchars($_GET['po_from'] ?? '') ?>" name="po_from" id="po-from" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="po-to" class="block text-sm font-medium text-gray-600 mb-1">Order To</label>
                        <input type="date" value="<?= htmlspecialchars($_GET['po_to'] ?? '') ?>" name="po_to" id="po-to" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                

                <!-- Item Code -->
                <div>
                    <label for="item-code" class="block text-sm font-medium text-gray-600 mb-1">Item Code</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['item_code'] ?? '') ?>" name="item_code" id="item-code" placeholder="Item Code" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <!-- PO No -->
                <div>
                    <label for="item-category" class="block text-sm font-medium text-gray-600 mb-1">Item Category</label>
                    <select id="item-category" name="item_category" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="" selected >All Categories</option>
                        <?php foreach (getCategories() as $key => $value): ?>
                            <option value="<?php echo $key; ?>" <?php echo (isset($_GET['item_category']) && $_GET['item_category'] === $key) ? 'selected' : ''; ?>><?php echo $value; ?></option>
                        <?php endforeach; ?>   
                    </select>
                </div>

                <!-- Item Name -->
                <div>
                    <label for="item-sub-category" class="block text-sm font-medium text-gray-600 mb-1">Item Sub Category</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['item_sub_category'] ?? '') ?>" name="item_sub_category" id="item-sub-category" placeholder="Item Sub Category" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <!-- Min/Max Amount -->
                <div class="col-span-1 sm:col-span-2 md:col-span-1 lg:col-span-2 flex items-end gap-2">
                    <div class="w-1/2">
                        <label for="po-amount-from" class="block text-sm font-medium text-gray-600 mb-1">PO Amount From</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['po_amount_from'] ?? '') ?>" name="po_amount_from" id="po-amount-from" placeholder="PO Amount From" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                    <span class="text-gray-500 pb-2">→</span>
                    <div class="w-1/2">
                        <label for="po-amount-to" class="block text-sm font-medium text-gray-600 mb-1">PO Amount To</label>
                        <input type="number" value="<?= htmlspecialchars($_GET['po_amount_to'] ?? '') ?>" name="po_amount_to" id="po-amount-to" placeholder="PO Amount To" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                    </div>
                </div>
                <!-- Order Number -->
                <div>
                    <label for="po-number" class="block text-sm font-medium text-gray-600 mb-1">PO No</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['po_number'] ?? '') ?>" name="po_number" id="po-number" placeholder="PO Number" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                <div>
                    <label for="due-date" class="block text-sm font-medium text-gray-600 mb-1">Due Date</label>
                    <input type="date" name="due_date" id="due-date" placeholder="Due Date" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
                </div>
                 <!-- Status -->
                <div>
                    <label for="status" class="block text-sm font-medium text-gray-600 mb-1">Status</label>
                    <select id="status" name="status" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white">
                        <option value="" selected>All Status</option>
                        <option value="draft" <?php echo ($_GET['status'] ?? '') == "draft" ? "selected" : ""?>>Draft</option>
                        <option value="pending" <?php echo ($_GET['status'] ?? '') == "pending" ? "selected" : ""?>>Pending</option>
                        <option value="ordered" <?php echo ($_GET['status'] ?? '') == "ordered" ? "selected" : ""?>>Ordered</option>
                        <option value="received" <?php echo ($_GET['status'] ?? '') == "received" ? "selected" : ""?>>Received</option>
                        <option value="cancelled" <?php echo ($_GET['status'] ?? '') == "cancelled" ? "selected" : ""?>>Cancelled</option>                    
                    </select>
                </div>
                <div>
                    <label for="vendor-name" class="block text-sm font-medium text-gray-600 mb-1">Vendor Name</label>
                    <input type="text" value="<?= htmlspecialchars($_GET['vendor_name'] ?? '') ?>" name="vendor_name" id="vendor-name" placeholder="Vendor Name" class="w-full px-2 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500">
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
                    const page = 'page=purchase_orders&action=list';
                    window.location.href = url.toString() + '?' + page; // Redirect to the updated URL
                }
            </script>
            </div>
        </div>
    </div>
    <!-- End of Advance Search Accordion -->
    <!-- </div> -->
    <!-- custom po button -->
    <div class="flex items-center justify-between mb-4">
        <div class=" justify-left">
            <a href="<?php echo base_url('?page=purchase_orders&action=custom_po'); ?>" class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded-md shadow-sm flex items-center gap-2">
                <i class="fa fa-plus"></i>
                <span> Custom PO</span>
            </a>
        </div>
        <!-- <div class="justify-end">
            <select id="po_type" class="text-sm items-right pagination-select px-2 py-1.5 border border-gray-300 rounded-xl shadow-sm focus:outline-none focus:ring-1 focus:ring-amber-500 focus:border-amber-500 bg-white" onchange="location.href='?page=purchase_orders&action=list&po_type=' + this.value ;">                    
                <option value="" <?php //echo !isset($_GET['po_type']) || $_GET['po_type'] === '' ? 'selected' : '' ?>>All </option>
                <option value="normal" <?php //echo (isset($_GET['po_type']) && $_GET['po_type'] === 'normal') ? 'selected' : '' ?>>Normal PO</option>
                <option value="custom" <?php //echo (isset($_GET['po_type']) && $_GET['po_type'] === 'custom') ? 'selected' : '' ?>>Custom PO</option>
        </select>
        </div> -->
    </div>
    <!-- PO Table Container -->
    <div class="bg-white rounded-xl shadow-md ">
        <div class="p-6 ">
            <div class="table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th><i class="fa fa-star"></i></th>
                        
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">PO Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">PO Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Delivery Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Vendor</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Invoice</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Grand Total</th>
                        <th scope="col" class="relative px-6 py-3"> <span class="table-header-text">Status</span></th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <!-- User Row 1 -->
                     <?php if (!empty($purchaseOrders)): ?>
                        <?php foreach ($purchaseOrders as $order): ?>
                    <tr class="table-content-text">
                        <td class="whitespace-nowrap cursor-pointer text-lg text-yellow-400" title="<?= $order['flag_star'] ? 'Unmark as Important' : 'Mark as Important' ?>">
                            <?= $order['flag_star'] 
                                ? '<i class="fa fa-star cursor-pointer" onclick="toggleStar(' . $order['id'] . ')" title=\'Unmark as Important\'></i>' 
                                : '<i class="far fa-star cursor-pointer" onclick="toggleStar(' . $order['id'] . ')" title=\'Mark as Important\'></i>' 
                            ?>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap"><a href="#" class="text-blue-600" onclick="handleAction('View', <?= htmlspecialchars($order['id']) ?>, this)"><?= htmlspecialchars($order['po_number'] ?? 'N/A') ?></a></td>
						
                        <td class="px-6 py-4 whitespace-nowrap"><?= date('d M Y', strtotime($order['po_date'] ?? '')) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= date('d M Y', strtotime($order['expected_delivery_date'] ?? '')) ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?= htmlspecialchars($order['vendor_name'] ?? 'N/A') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <?php if (!empty($order['vendor_invoice'])): ?>
                                <a href="<?= base_url($order['vendor_invoice']) ?>" target="_blank" class="text-blue-600 hover:underline">View Invoice</a>
                            <?php else: ?>
                                <span class="text-gray-400">No Invoice</span>
                            <?php endif; ?>
                        </td>   
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                        <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md bg-black text-white"><?= htmlspecialchars($order['total_cost']) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <?php
                                $status = strtolower($order['status']);
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'ordered' => 'bg-blue-100 text-blue-800',
                                    'received' => 'bg-green-100 text-green-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    'cancelled' => 'bg-red-100 text-red-800'
                                ];
                                $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                            ?>
                            <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md <?= $badgeClass ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                            <!-- Three-dot menu container -->
                            <div class="menu-wrapper ">
                            <button class="menu-button text-gray-500 hover:text-gray-700 " onclick="toggleMenu(this)">
                                &#x22EE; <!-- Vertical ellipsis -->
                            </button>
                            <ul class="menu-popup text-left">
                                <li onclick="handleAction('View', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-eye"></i> View PO</li>
                                <?php if ($order['status'] == 'pending' || $order['status'] == 'draft'): ?>
                                <li onclick="handleAction('Edit', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-pencil-alt"></i> Edit PO</li>
                                <?php endif; ?>
                                <?php /*if ($order['status'] == 'draft'): ?>
                                <li onclick="handleAction('Approved', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-check-circle"></i> Draft to Create PO</li>                                
                                <?php else: */?>
                                <li onclick="handleAction('ChangeStatus', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-exchange-alt"></i> Change Status</li>
                                <?php if ($order['status'] == 'cancelled'): ?>
                                <li onclick="handleAction('Delete', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-trash-alt"></i> Delete PO</li>
                                <?php endif; ?>
                                <li onclick="handleAction('Download', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-download"></i> Download PO</li>
                                <li onclick="handleAction('Email', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-envelope"></i> Email PO to Vendor</li>
                                <li onclick="handleAction('UploadPerforma', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-upload"></i> Upload Performa</li>
                                <li onclick="handleAction('UploadInvoice', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-upload"></i> Upload Vendor Invoice</li>
                                <li onclick="handleAction('AddGRN', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-plus-circle"></i> Add GRN</li>
                                <li onclick="handleAction('qrcode', <?= htmlspecialchars($order['id']) ?>, this)"><i class="fa fa-qrcode"></i> QR Code Download</li>
                                
                                <?php //endif; ?>
                            </ul>
                            </div>
                            
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No purchase orders found.</td>
                        </tr>
                    <?php endif; ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Pagination -->
        <?php         
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page, default 20
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values
        $total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
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
            <?php /*if ($total_pages > 1): ?>
            <span >Page</span>
            <button id="prev-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=purchase_orders&acton=list&page_no=<?= $page-1 ?>&limit=<?= $limit ?>" tabindex="-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
            </button>
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><a class="page-link" href="?page=orders&page_no=<?= $i ?>&limit=<?= $limit ?>"><?= $i ?></a></span>
            <?php endfor; ?>
            <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page ?></span>
            <?php if ($page < $total_pages): ?>
            <button id="next-page" class="p-2 rounded-full hover:bg-gray-100">
                <a class="page-link" href="?page=purchase_orders&acton=list&page_no=<?= $page+1 ?>&limit=<?= $limit ?>">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
            </button>
            <span class="text-sm text-gray-600">of <?= $total_pages ?></span>
            <?php endif; ?>
            <?php endif; */?>
            <div>
                <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($purchaseOrders) ?></span> of <span class="font-medium"><?= $total_orders ?></span> orders</p>
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

<!-- Right Side Popup Wrapper -->
<div id="popup-wrapper" class="hidden">
    <!-- Background Overlay -->
    <!-- <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div> -->

    <!-- Sliding Container -->
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b" id="invoice-title">Vendor Invoice</h2>

                    <div class="flex items-start mb-6 pb-6 border-b">
                        <!-- <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md w-24 h-20 object-cover"> -->
                        <div class="ml-6 text-sm text-gray-600 space-y-1">
                            <p><strong>PO Number:</strong> </p>
                            <p><strong>PO Date:</strong> </p>
                            <!-- <p><strong>Item:</strong> </p>                             -->
                            <p><strong>Vendor Name:</strong> </p>
                            <p><strong>Vendor Phone:</strong>  <a href="https://wa.me/"><i class="fab fa-whatsapp text-green-500 ml-1"></i></a> <span class="text-blue-600"><a href="mailto:info@vendor1.com">info@vendor1.com</a></span></p>
                        </div>
                    </div>

                    <form id="invoice-form" enctype="multipart/form-data">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                             <div>
                                <label for="invoice_no" class="text-sm font-medium text-gray-700"><span id="invoice-no-label">Invoice No: </span><span class="text-red-500">*</span></label>
                                <input type="text" id="invoice_no" name="invoice_no" value="" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="invoice_date" class="text-sm font-medium text-gray-700"><span id="invoice-date-label">Invoice Date: </span><span class="text-red-500">*</span></label>
                                <input type="date" id="invoice_date" name="invoice_date" class="form-input w-full mt-1" max="<?= date('Y-m-d') ?>">
                            </div>
                            <div>
                                <label for="gst_reg" class="text-sm font-medium text-gray-700">GST Reg: <span class="text-red-500">*</span></label>
                                <select id="gst_reg" name="gst_reg" class="form-input w-full bg-white mt-1">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
                                </select>
                                <!-- <p class="text-xs text-gray-500 mt-1">If yes, please provide your GST number</p> -->
                            </div>
                            <div>
                                <label for="sub_total" class="text-sm font-medium text-gray-700">Sub Total ₹: <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" inputmode="decimal" id="sub_total" name="sub_total" value="" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="gst_total" class="text-sm font-medium text-gray-700">GST Total ₹: </label>
                                <input type="number" step="0.01" inputmode="decimal" id="gst_total" name="gst_total" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="shipping" class="text-sm font-medium text-gray-700">Shipping ₹: </label>
                                <input type="number" step="0.01" inputmode="decimal" id="shipping" name="shipping" value="" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="grand_total" class="text-sm font-medium text-gray-700">Grand Total ₹: <span class="text-red-500">*</span></label>
                                <input type="number" step="0.01" inputmode="decimal" id="grand_total" name="grand_total" value="" class="form-input w-full mt-1 bg-gray-100" readonly>
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="text-sm font-medium text-gray-700 mb-1 block"><span id="invoice-pdf-label">Invoice PDF: </span><span class="text-red-500">*</span></label>
                            <div id="file-drop-area" class="file-drop-area">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500" id="file-drop-area-text">Drag & Drop your invoice file here or</p>
                                <button type="button" id="choose-file-btn" class="mt-2 bg-white border border-gray-300 text-gray-700 px-4 py-1 rounded-md text-sm hover:bg-gray-50">Choose file</button>
                                <input type="file" id="file-input" name="file_input" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                <p class="text-xs text-gray-400 mt-2">Only PDF, JPG, PNG</p>
                            </div>
                        </div>

                        <div id="uploaded-file-section" class="hidden mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2" id="uploaded-file-label">Uploaded Invoice:</h3>
                            <div id="file-info" class="border rounded-md p-3 flex items-center justify-between">
                                <!-- File info will be injected here by JS -->
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <input type="hidden" id="invoice-type" name="invoice_type" value="invoice">
                            <input type="hidden" id="invoice-upload-po-id" name="po_id" >
                            <input type="hidden" id="invoice-id" name="id" >
                            <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Cancel</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- Change Status Popup -->
<div id="status-popup-overlay" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xs">
    <h3 class="text-lg font-bold mb-4">Change Order Status</h3>
    <form id="status-form">
      <input type="hidden" id="status-po-id">
      <label for="status-select" class="block mb-2 text-sm font-medium text-gray-700">Select Status:</label>
      <select id="status-select" class="w-full border rounded-md p-2 mb-4">
        <option value="" disabled selected>Select status</option>
        <option value="draft">Draft</option>
        <option value="pending">Pending</option>
        <option value="ordered">Ordered</option>
        <option value="received">Received</option>
        <option value="completed">Completed</option>
        <option value="cancelled">Cancelled</option>
      </select>
      <textarea name="cancelation_reason" id="cancelation_reason" class="w-full border rounded-md p-2 mb-4 hidden" rows="3" placeholder="Enter cancellation reason..."></textarea>
      <div class="flex justify-end gap-2">
        <button type="button" id="status-cancel-btn" class="bg-gray-200 px-4 py-1 rounded">Cancel</button>
        <button type="submit" class="bg-blue-600 text-white px-4 py-1 rounded">Update</button>
      </div>
    </form>
    <div id="status-msg" class="text-sm mt-2"></div>
  </div>
</div>
<!-- email popup -->
<div id="email-popup" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
        <h3 class="text-lg font-bold mb-4">Email PO to Vendor</h3>
        <div class="mb-4 flex items-center">
            <label for="po-date-emailToPo" class="w-36 text-sm font-medium text-gray-700 mr-2">PO Date:</label>
            <span id="po-date-emailToPo" class="text-gray-800 font-medium"></span>
        </div>
        <div class="mb-4 flex items-center">
            <label for="PO-numberToPo" class="w-36 text-sm font-medium text-gray-700 mr-2">PO Number:</label>
            <span id="PO-numberToPo" class="text-gray-800 font-medium"></span>
        </div>
        <div class="mb-4 flex items-center">
            <label for="vendorToPo" class="w-36 text-sm font-medium text-gray-700 mr-2">Vendor:</label>
            <span id="vendorToPo" class="text-gray-800 font-medium"></span>
        </div>
        <div class="mb-4 flex items-center">
            <label for="contactToPo" class="w-36 text-sm font-medium text-gray-700 mr-2">Contact:</label>
            <span id="contactToPo" class="text-gray-800 font-medium"></span>
        </div>
        <div class="mb-4 flex items-center">
            <label for="vendor-emailToPo" class="w-36 text-sm font-medium text-gray-700 mr-2">Vendor Email:</label>
            <span id="vendor-emailToPo" class="text-gray-800 font-medium"></span>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" id="emailToPo-cancel-btn" class="bg-gray-200 px-4 py-1 rounded">Cancel</button>
            <button type="button" id="emailToPo-submit-btn" class="bg-blue-600 text-white px-4 py-1 rounded">Send Email</button>
        </div>
        <div id="emailToPo-msg" class="text-sm mt-2"></div>
    </div>
</div>
<!-- -- Download Format Popup -->
<div id="download-popup-overlay" class="fixed inset-0 bg-black bg-opacity-30 z-50 hidden flex items-center justify-center">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-xs">
    <h3 class="text-lg font-bold mb-4">Download Purchase Order</h3>
    <form id="download-form" method="post" action="?page=purchase_orders&action=download" target="_blank">
      <input type="hidden" id="download-po-id" name="po_id">
      <label for="design-format-select" class="block mb-2 text-sm font-medium text-gray-700">Select Format:</label>
      <select id="design-format-select" name="design_format" class="w-full border rounded-md p-2 mb-4">
        
        <option value="smallImageWithPrice">Small Image with Price</option>        
        <option value="smallImageWithoutPrice">Small Image without Price</option>        
        <option value="largeImageWithPrice">Large Image with Price</option>
        <option value="largeImageWithoutPrice">Large Image without Price</option>
      </select>
      <div class="flex justify-end gap-2">
        <button type="button" id="download-cancel-btn" onclick="closeDownloadPopup()" class="bg-gray-200 px-4 py-1 rounded">Close</button>
        <button type="submit" class="bg-green-600 text-white px-4 py-1 rounded">Download</button>
      </div>
    </form>
  </div>
</div>
<script>
//calculate grand total
document.getElementById('sub_total').addEventListener('input', calculateGrandTotal);    
document.getElementById('shipping').addEventListener('input', calculateGrandTotal);
document.getElementById('gst_total').addEventListener('input', calculateGrandTotal);

function calculateGrandTotal() {
    const subTotal = parseFloat(document.getElementById('sub_total').value) || 0;
    const shipping = parseFloat(document.getElementById('shipping').value) || 0;
    const gstTotal = parseFloat(document.getElementById('gst_total').value) || 0;
    const grandTotal = subTotal + shipping + gstTotal;
    document.getElementById('grand_total').value = grandTotal.toFixed(2);
}
//gst reg change handler
document.getElementById('gst_reg').addEventListener('change', function() {
    const gstReg = this.value;
    if (gstReg === '0') {
        document.getElementById('gst_total').value = '0.00';
        document.getElementById('gst_total').setAttribute('readonly', true);
    } else {
        document.getElementById('gst_total').removeAttribute('readonly');
    }
    calculateGrandTotal();
});
    // Toggle menu visibility
function toggleMenu(button) {
  const popup = button.nextElementSibling;
  popup.style.display = popup.style.display === 'block' ? 'none' : 'block';

  // Close other open menus
  document.querySelectorAll('.menu-popup').forEach(menu => {
    if (menu !== popup) menu.style.display = 'none';
  });
}

// Handle action clicks
function handleAction(action, poId, el) {
  // Close the menu after action
  if (el && el.parentElement && el.parentElement.parentElement && el.parentElement.parentElement.classList.contains('menu-popup')) {
    el.parentElement.parentElement.style.display = 'none';
  } else if (el && el.parentElement && el.parentElement.classList.contains('menu-popup')) {
    el.parentElement.style.display = 'none';
  }
  if (action === 'View') {
      // Redirect to view page
      window.location.href = '?page=purchase_orders&action=view&po_id='+ poId;
  } else if (action === 'Edit') {
      // Redirect to edit page
      window.location.href = '?page=purchase_orders&action=edit&po_id='+ poId;
  } else if (action === 'Delete') {
      // Confirm and redirect to delete action
      if (confirm('Are you sure you want to delete this cancelled purchase order?')) {
          //window.location.href = '?page=purchase_orders&action=delete&po_id='+ poId;
            fetch('?page=purchase_orders&action=delete', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'po_id=' + encodeURIComponent(poId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Purchase order deleted successfully.');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to delete purchase order.');
                }
            })
            .catch(() => {
                alert('Error deleting purchase order.');
            });
      }
  } else if (action === 'Download') {
    //open popup to ask for download format
    document.getElementById('download-po-id').value = poId;
    document.getElementById('download-popup-overlay').classList.remove('hidden');

      // Redirect to download action
      //window.open('?page=purchase_orders&action=download&po_id='+ poId, '_blank');
  } else if (action === 'ChangeStatus') {
        document.getElementById('status-po-id').value = poId;
        document.getElementById('status-popup-overlay').classList.remove('hidden');
        document.getElementById('status-msg').textContent = '';
 
  } else if (action === 'Email') {
    // Show email popup
        fetch('?page=purchase_orders&action=get_po', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'po_id=' + encodeURIComponent(poId)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('emailToPo-submit-btn').disabled = false;
                document.getElementById('po-date-emailToPo').textContent = data.data.po_date;
                if (data.data.vendor_email) {
                    document.getElementById('vendor-emailToPo').textContent = data.data.vendor_email;
                } else {
                    document.getElementById('vendor-emailToPo').textContent = 'N/A';                    
                    document.getElementById('emailToPo-submit-btn').disabled = true;
                }
                
                document.getElementById('vendorToPo').textContent = data.data.vendor_name;
                document.getElementById('PO-numberToPo').textContent = data.data.po_number;
                document.getElementById('contactToPo').textContent = data.data.vendor_phone;
            } else {
                alert(data.message || 'Failed to retrieve purchase order details.');
            }
        })
        .catch(() => {
            alert('Error retrieving purchase order details.');
        });
        document.getElementById('PO-numberToPo').textContent = poId;
        document.getElementById('vendorToPo').textContent = poId;
        document.getElementById('email-popup').classList.remove('hidden');
        // Close email popup handlers
        document.getElementById('emailToPo-cancel-btn').addEventListener('click', function() {
            document.getElementById('email-popup').classList.add('hidden');
        });
        document.getElementById('emailToPo-submit-btn').addEventListener('click', function() {   
            document.getElementById('emailToPo-msg').textContent = 'Please wait...';
            // Disable buttons to prevent multiple clicks
            document.getElementById('emailToPo-submit-btn').textContent = 'Sending...';
            document.getElementById('emailToPo-submit-btn').disabled = true; 
            document.getElementById('emailToPo-cancel-btn').disabled = true;        
            // Send email request
            fetch('?page=purchase_orders&action=emailToVendor', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'po_id=' + encodeURIComponent(poId)
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('emailToPo-submit-btn').disabled = false;
                    document.getElementById('emailToPo-cancel-btn').disabled = false;
                    document.getElementById('emailToPo-msg').textContent = 'Email sent successfully.';
                    document.getElementById('emailToPo-submit-btn').textContent = 'Sent';                    
                    alert('Purchase order emailed to vendor successfully.');
                    document.getElementById('email-popup').classList.add('hidden');
                } else {
                    alert(data.message || 'Failed to email purchase order.');
                }
            })
            .catch(() => {
                alert('Error emailing purchase order.');
            });
        });
      // Redirect to email action
    //   if (confirm('Send purchase order to vendor via email?')) {
    //       fetch('?page=purchase_orders&action=emailToVendor', {
    //           method: 'POST',
    //           headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    //           body: 'po_id=' + encodeURIComponent(poId)
    //       })
    //       .then(r => r.json())
    //       .then(data => {
    //           if (data.success) {
    //               alert('Purchase order emailed to vendor successfully.');
    //           } else {
    //               alert(data.message || 'Failed to email purchase order.');
    //           }
    //       })
    //       .catch(() => {
    //           alert('Error emailing purchase order.');
    //       });
    //   }
  } else if (action === 'UploadInvoice' || action === 'UploadPerforma') {
       document.getElementById('invoice-type').value = action === 'UploadInvoice' ? 'invoice' : 'performa';
       document.getElementById('invoice-title').textContent = action === 'UploadInvoice' ? 'Vendor Invoice' : 'Performa Invoice';
       document.getElementById('invoice-no-label').textContent = action === 'UploadInvoice' ? 'Invoice No: ' : 'Performa No: ';
       document.getElementById('invoice-date-label').textContent = action === 'UploadInvoice' ? 'Invoice Date: ' : 'Performa Date: ';
       document.getElementById('invoice-pdf-label').textContent = action === 'UploadInvoice' ? 'Invoice PDF: ' : 'Performa PDF: ';
       document.getElementById('file-drop-area-text').textContent = action === 'UploadInvoice' ? 'Drag & Drop your invoice file here or' : 'Drag & Drop your performa file here or';
       document.getElementById('uploaded-file-label').textContent = action === 'UploadInvoice' ? 'Uploaded Invoice:' : 'Uploaded Performa:';

      // Open the invoice upload popup
      //alert('Feature coming soon!');
        const popupWrapper = document.getElementById('popup-wrapper');
        const modalSlider = document.getElementById('modal-slider');
        const cancelVendorBtn = document.getElementById('cancel-vendor-btn');
        const closeVendorPopupBtn = document.getElementById('close-vendor-popup-btn');
        
        function openVendorPopup() {
            popupWrapper.classList.remove('hidden');
            setTimeout(() => {
                modalSlider.classList.remove('translate-x-full');
            }, 10);
        }
        function closeVendorPopup() {
            modalSlider.classList.add('translate-x-full');
        }

        modalSlider.addEventListener('transitionend', (event) => {
            if (event.propertyName === 'transform' && modalSlider.classList.contains('translate-x-full')) {
                popupWrapper.classList.add('hidden');
            }
        });
        openVendorPopup();
        cancelVendorBtn.addEventListener('click', closeVendorPopup);
        closeVendorPopupBtn.addEventListener('click', closeVendorPopup);
        // Store the PO ID if needed for form submission
        document.getElementById('invoice-upload-po-id').value = poId;
        // File upload handling
        const fileDropArea = document.getElementById('file-drop-area');
        const fileInput = document.getElementById('file-input');
        const chooseFileBtn = document.getElementById('choose-file-btn');
        const uploadedFileSection = document.getElementById('uploaded-file-section');
        const fileInfoDiv = document.getElementById('file-info');
        const invoiceType = document.getElementById('invoice-type').value;
        // Fetch and populate invoice po details
        fetchPoDetails(poId, invoiceType).then(data => {
            if (data.success) {
                //console.log(data.data);
                const purchaseOrder = data.data.purchaseOrder;
                const invoiceData = data.data.invoiceData;
                const invItem = Array.isArray(data.data.items) && data.data.items.length > 0 ? data.data.items[0] : {};
                document.querySelector('#vendor-popup-panel div.mb-6 p:nth-child(1)').innerHTML = `<strong>PO Number:</strong> ${purchaseOrder.po_number}`;
                document.querySelector('#vendor-popup-panel div.mb-6 p:nth-child(2)').innerHTML = `<strong>PO Date:</strong> ${new Date(purchaseOrder.po_date).toLocaleDateString()}`;
                // document.querySelector('#vendor-popup-panel div.mb-6 p:nth-child(3)').innerHTML = `<strong>Item:</strong> ${invItem.title || 'N/A'}`;
                document.querySelector('#vendor-popup-panel div.mb-6 p:nth-child(3)').innerHTML = `<strong>Vendor Name:</strong> ${purchaseOrder.vendor_name || 'N/A'}`;
                document.querySelector('#vendor-popup-panel div.mb-6 p:nth-child(4)').innerHTML = `<strong>Vendor Phone:</strong> ${purchaseOrder.vendor_phone || 'N/A'} <a href="https://wa.me/${purchaseOrder.vendor_phone || 'N/A'}"><i class="fab fa-whatsapp text-green-500 ml-1"></i></a> <span class="text-blue-600"><a href="mailto:${purchaseOrder.vendor_email || 'N/A'}">${purchaseOrder.vendor_email || 'N/A'}</a></span>`;
                document.getElementById('sub_total').value = invoiceData.sub_total || 0.00;
                document.getElementById('gst_total').value = invoiceData.gst_total || 0.00;
                document.getElementById('shipping').value = invoiceData.shipping || 0.00;
                document.getElementById('grand_total').value = invoiceData.grand_total || 0.00;
                document.getElementById('gst_reg').value = invoiceData && invoiceData.gst_reg ? invoiceData.gst_reg : '1';
                document.getElementById('invoice_date').value = invoiceData && invoiceData.invoice_date ? invoiceData.invoice_date : '';
                document.getElementById('invoice_no').value = invoiceData && invoiceData.invoice_no ? invoiceData.invoice_no : '';
                document.getElementById('invoice-id').value = invoiceData && invoiceData.id ? invoiceData.id : '';
                //uploadedFileSection.innerHTML = '';
                // If invoice type is invoice and existing invoice is performa, clear invoice no/date
                // if(document.getElementById('invoice-type').value === 'invoice' && invoiceData.invoice_type === 'performa') {
                //     document.getElementById('invoice_no').value = '';
                //     document.getElementById('invoice_date').value = '';
                // } 
                //const invoiceName = action === 'UploadPerforma' ? invoiceData.performa : invoiceData.invoice;
                const invoiceName = invoiceData.invoice;
                if (invoiceData && invoiceName) {                    
                    //document.getElementById('invoice-title').textContent = invoiceName + (invoiceData.invoice ? ' - ' + invoiceData.invoice : '');                    
                    const file = { name: invoiceName, size: 0, type: invoiceName.endsWith('.pdf') ? 'application/pdf' : 'image/*', id: invoiceData.id, invoice_type: document.getElementById('invoice-type').value };
                    let iconClass = 'fa-file';
                    if (file.type.includes('pdf')) iconClass = 'fa-file-pdf';
                    if (file.type.includes('image')) iconClass = 'fa-file-image';
                    fileInfoDiv.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas ${iconClass} text-2xl text-gray-600"></i>
                                <div class="ml-3 text-sm">
                                    <div class="font-medium">${file.name}</div>
                                    <div class="text-gray-500">${formatFileSize(file.size)}</div>
                                </div>
                            </div>
                        `;
                    fileInfoDiv.innerHTML += `
                        <button type="button" id="delete-file-btn" class="text-gray-500 hover:text-red-600">
                            <i class="fas fa-trash-alt"></i>
                        </button>
                    `;                    
                    uploadedFileSection.classList.remove('hidden');
                    document.getElementById('delete-file-btn')?.addEventListener('click', () => {
                        if (confirm('Are you sure you want to delete the uploaded file?')) {
                            fetch('?page=purchase_orders&action=delete_invoice', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                                body: 'invoice_id=' + encodeURIComponent(file.id) + '&po_id=' + encodeURIComponent(poId)
                            })
                            .then(r => r.json())
                            .then(data => {
                                if (data.success) {
                                    alert('File deleted successfully.');
                                    fileInput.value = '';
                                    uploadedFileSection.classList.add('hidden');
                                    fileInfoDiv.innerHTML = '';
                                } else {
                                    alert(data.message || 'Failed to delete file.');
                                }
                            })
                            .catch(() => {
                                alert('Error deleting file.');
                            });
                        }
                    });
                } else {
                    fileInput.value = '';
                    uploadedFileSection.classList.add('hidden');
                    fileInfoDiv.innerHTML = '';
                }

            } else {
                console.debug('PO details fetch error:', data);
                alert(data.message || 'Failed to fetch purchase order details.');
            }
        }).catch((err) => {            
            console.debug('Fetch error:', err);
            alert('Error fetching purchase order details.' + err.message);
        }); 

        chooseFileBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, preventDefaults, false);
        });
        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, () => fileDropArea.classList.add('dragover'), false);
        });
        ['dragleave', 'drop'].forEach(eventName => {
            fileDropArea.addEventListener(eventName, () => fileDropArea.classList.remove('dragover'), false);
        });

        fileDropArea.addEventListener('drop', (e) => handleFiles(e.dataTransfer.files), false);

        function handleFiles(files) {
            if (files.length === 0) return;
            const file = files[0];

            const fileType = file.type;
            let iconClass = 'fa-file';
            if (fileType.includes('pdf')) iconClass = 'fa-file-pdf';
            if (fileType.includes('image')) iconClass = 'fa-file-image';

            fileInfoDiv.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas ${iconClass} text-2xl text-gray-600"></i>
                        <div class="ml-3 text-sm">
                            <p class="font-medium text-gray-800">${file.name}</p>
                            <p class="text-gray-500">${(file.size / 1024).toFixed(1)} KB</p>
                        </div>
                    </div>
                    <button type="button" id="delete-file-btn" class="text-gray-500 hover:text-red-600">
                        <i class="fas fa-trash-alt"></i>
                    </button>
                `;
            uploadedFileSection.classList.remove('hidden');

            document.getElementById('delete-file-btn').addEventListener('click', () => {
                fileInput.value = '';
                uploadedFileSection.classList.add('hidden');
                fileInfoDiv.innerHTML = '';
            });
        }

        document.getElementById('invoice-form').addEventListener('submit', (e) => {
            e.preventDefault();
            //console.log('Invoice form submitted');
            //validation is added here
            const invoiceDate = document.getElementById('invoice_date').value;
            const subTotal = document.getElementById('sub_total').value;
            const grandTotal = document.getElementById('grand_total').value;
            const file = document.getElementById('file-input').files[0];
            const invoiceType = document.getElementById('invoice-type').value;
            let isValid = true;

            if (!invoiceDate || !subTotal || !grandTotal || (!file && !document.getElementById('invoice-id').value)) {
                isValid = false;
            }
            //alert('Validation status: ' + isValid);
            if (!isValid) {
                alert('Please fill in all required fields.');
                return;
            }
            // Here we handle the form submission, e.g., via AJAX
            const formData = new FormData(document.getElementById('invoice-form'));
            fetch('?page=purchase_orders&action=upload_invoice', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {                   
                    if (invoiceType === 'invoice') {
                        alert('Invoice uploaded successfully!');
                    } else {
                        alert('Performa invoice uploaded successfully!');
                    }
                    closeVendorPopup();
                    location.reload();
                } else {
                    alert(data.message || 'Failed to upload invoice.');
                }
            })
            .catch(() => {
                alert('Error uploading invoice.');
            });
            
        });

  } else if (action === 'Approved') {
        // Redirect to approve action
        if (confirm('Mark this purchase order as approved?')) {
            fetch('?page=purchase_orders&action=approve', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'po_id=' + encodeURIComponent(poId) + '&status=pending'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Purchase order marked as approved.');
                    location.reload();
                } else {
                    alert(data.message || 'Failed to approve purchase order.');
                }
            })
            .catch(() => {
                alert('Error approving purchase order.');
            });
        }
  } else if (action === 'AddGRN') {
        // Redirect to add GRN page
        window.location.href = '?page=grns&action=create&po_id='+ poId;
   } else if (action === 'qrcode') {
        // QR code download 
        window.location.href = '?page=grns&action=qrcode&po_id='+ poId;
   } else {
      alert('Unknown action: ' + action);
      console.debug('Unknown action:', action);
      // Optionally, you could also log the entire data object
      console.debug('Fetch data:', data);
    }
}
// Close menu on outside click
document.addEventListener('click', function (e) {
  if (!e.target.closest('.menu-wrapper')) {
    document.querySelectorAll('.menu-popup').forEach(menu => {
      menu.style.display = 'none';
    });
  }
});
// Close popup
document.getElementById('status-cancel-btn').onclick = function() {
  document.getElementById('status-popup-overlay').classList.add('hidden');
};
// Submit status change
document.getElementById('status-form').onsubmit = function(e) {
  e.preventDefault();
  const poId = document.getElementById('status-po-id').value;
  const status = document.getElementById('status-select').value;
  const msgDiv = document.getElementById('status-msg');
  if (status === 'cancelled') {
      const reason = document.getElementById('cancelation_reason').value.trim();
      if (!reason) {
          msgDiv.textContent = 'Please provide a cancellation reason.';
          msgDiv.style.color = 'red';
          return;
      }
  }
  msgDiv.textContent = 'Updating...';

  fetch('?page=purchase_orders&action=update_status', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'po_id=' + encodeURIComponent(poId) + '&status=' + encodeURIComponent(status) + (status === 'cancelled' ? '&reason=' + encodeURIComponent(document.getElementById('cancelation_reason').value.trim()) : '')   
  })
  .then(r => r.json())
  .then(data => {
    if (data.success) {
      msgDiv.textContent = 'Status updated!';
      setTimeout(() => {
        document.getElementById('status-popup-overlay').classList.add('hidden');
        location.reload();
      }, 800);
    } else {
      msgDiv.textContent = data.message || 'Failed to update status.';
    }
  })
  .catch(() => {
    msgDiv.textContent = 'Error updating status.';
  });
};
// Show/hide cancelation reason based on status
document.getElementById('status-select').addEventListener('change', function() {
    const reasonField = document.getElementById('cancelation_reason');
    if (this.value === 'cancelled') {
        reasonField.classList.remove('hidden');
    } else {
        reasonField.classList.add('hidden');
    }
    });

// Optional: Close popup on overlay click
document.getElementById('status-popup-overlay').addEventListener('click', function(e) {
  if (e.target === this) this.classList.add('hidden');
});
   
// document.addEventListener('click', (event) => {
//     if (!event.target.closest('.menu-popup') && !event.target.closest('.menu-button')) {
//         document.querySelectorAll('.menu-popup').forEach(menu => {
//             menu.style.display = 'none';
//         });
//     }
// });
// Toggle star flag
function toggleStar(poId) {
    fetch('?page=purchase_orders&action=toggle_star', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'po_id=' + encodeURIComponent(poId)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'Failed to update star flag.');
        }
    })
    .catch(() => {
        alert('Error updating star flag.');
    });
}
function fetchPoDetails(poId, invoiceType) {
    return fetch('?page=purchase_orders&action=get_po_details&po_id=' + encodeURIComponent(poId) + '&invoice_type=' + encodeURIComponent(invoiceType))
        .then(r => r.json());
}
function formatFileSize(size) {
    if (size >= 1048576) {
        return (size / 1048576).toFixed(2) + ' MB';
    } else if (size >= 1024) {
        return (size / 1024).toFixed(2) + ' KB';
    } else {
        return size + ' bytes';
    }
}
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
    const fromDateInput = document.getElementById('po-from');
    const toDateInput = document.getElementById('po-to');
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
    setHidden('page', 'purchase_orders');
    setHidden('action', 'list');

    form.submit();
    return false;
}
//downoad popup
function closeDownloadPopup() {
    document.getElementById('download-popup-overlay').classList.add('hidden');
}   
</script>