<div class="mx-auto space-y-6 mr-4">
    <h2 class="text-2xl font-bold my-4">Master Purchase List</h2>
    <!-- Advance search and filters can be added here -->
    <div class="overflow-x-auto">
        <form method="GET" class="mb-4">
            <input type="hidden" name="page" value="products">
            <input type="hidden" name="action" value="master_purchase_list">
            <div class="flex items-center gap-4">
                <input type="text" name="search" placeholder="Search by Item Code, Title..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" class="border rounded px-3 py-2 w-64">
                
            </div>
            <div class="mt-4 flex items-center gap-4">
                <label class="text-sm text-gray-600">Date From:</label>
                <input type="date" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>" class="border rounded px-3 py-2">
                
                <label class="text-sm text-gray-600">Date To:</label>
                <input type="date" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>" class="border rounded px-3 py-2">
            </div>
            <div class="mt-4 flex items-center gap-4">
                <label class="text-sm text-gray-600">Category:</label>
                <select name="category" class="border rounded px-3 py-2">
                    <option value="all" <?php echo (isset($_GET['category']) && $_GET['category'] === 'all') ? 'selected' : ''; ?>>All</option>
                    <?php foreach (($data['categories'] ?? []) as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo (isset($_GET['category']) && $_GET['category'] === $cat) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>

                <label class="text-sm text-gray-600">Status:</label>
                <select name="status" class="border rounded px-3 py-2">
                    <option value="all" <?php echo (isset($_GET['status']) && $_GET['status'] === 'all') ? 'selected' : ''; ?>>All</option>
                    <option value="pending" <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                    <option value="purchased" <?php echo (isset($_GET['status']) && $_GET['status'] === 'purchased') ? 'selected' : ''; ?>>Purchased</option>
                </select>
            </div>
            <div class="mt-4 flex items-center gap-4">  
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Filter</button>  
            </div>  
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-md ">
        <div class="p-6 ">
            <div class="table-container">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cost Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Added</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Purchased</th>
                        <th> </th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($data['purchase_list'] as $pl): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" onclick="viewPurchaseListDetails('<?php echo $pl['id']; ?>')"><?php echo htmlspecialchars($pl['item_code']); ?></td>
                            <td class="px-6 py-4 whitespace-normal text-sm text-gray-900 break-words"><?php echo htmlspecialchars($pl['title']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($pl['category']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo (int)$pl['quantity']; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo $pl['cost_price'] ? "₹".htmlspecialchars($pl['cost_price']) : 'N/A'; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo ucfirst(htmlspecialchars($pl['status'])); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php echo htmlspecialchars($pl['added_by']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php 
                                $date_added = $pl['date_added_readable'] ?? ($pl['date_added'] ? date('d M Y', strtotime($pl['date_added'])) : 'N/A');
                                echo htmlspecialchars($date_added); 
                            ?></td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900"><?php 
                                $date_purchased = $pl['date_purchased_readable'] ?? ($pl['date_purchased'] ? date('d M Y', strtotime($pl['date_purchased'])) : 'N/A');
                                echo htmlspecialchars($date_purchased); 
                            ?></td>
                            <!--action dropdown menu -->
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="relative inline-block text-left">
                                    <button type="button" class="inline-flex justify-center w-full rounded-md border border-gray-300 shadow-sm px-2 py-1 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none" id="menu-button" aria-expanded="true" aria-haspopup="true">
                                        Actions
                                        <svg class="-mr-1 ml-2 h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.584l3.71-4.354a.75.75 0 111.14.976l-4.25 5a.75.75 0 01-1.14 0l-4.25-5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    <div class="origin-top-right absolute right-0 mt-2 w-40 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 focus:outline-none hidden" role="menu" aria-orientation="vertical" aria-labelledby="menu-button" tabindex="-1">
                                        <div class="py-1" role="none">
                                            <a href="#" onclick="viewPurchaseListDetails('<?php echo $pl['id']; ?>'); return false;" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-0">View / Edit</a>
                                            <a href="<?php echo base_url('?page=products&action=delete_purchase_list&id=' . urlencode($pl['id'])); ?>" class="text-gray-700 block px-4 py-2 text-sm hover:bg-gray-100" role="menuitem" tabindex="-1" id="menu-item-1" onclick="return confirm('Are you sure you want to delete this purchase list item?');">Delete</a>
                                            <!-- More actions can be added here -->
                                        </div>
                                    </div>
                                </div>
                            </td>
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
        $total_orders = isset($data['total_records']) ? (int)$data['total_records'] : 0;
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
                        <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($purchase_list) ?></span> of <span class="font-medium"><?= $total_orders ?></span> Payment</p>
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
<!--detail popup-->
<div class="modal fade hidden" id="DetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <!-- Sliding Container -->
    <div id="modal-slider-bd" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-bd-popup-btn-bd" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="purchase-list-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Purchase list Details</h2>
                    <div id="plDetailMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="plDetailForm">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="editPlDetails">
                        <input type="hidden" name="purchase_list_id" id="purchase_list_id" value="">

                        <!-- Basic Information -->
                        <div class="pt-4">

                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4" id="pl-detail-fields">
                                <!-- Dynamic fields will be inserted here -->
                                
                            </div>
                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-bd-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
    </div>
  </div>
</div>
<script>
    //right side popup on item_code click 
    function viewPurchaseListDetails(plId) {
        //DetailModal show
        
        const modalSlider = document.getElementById('modal-slider-bd');
        modalSlider.classList.remove('translate-x-full');

        // Clear previous messages
        document.getElementById('plDetailMsg').innerHTML = '';

        // Fetch purchase list details via AJAX
        fetch(`<?php echo base_url('?page=products&action=get_purchase_list_details'); ?>&id=${plId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const plDetails = data.purchaseItem;
                    const fieldsContainer = document.getElementById('pl-detail-fields');
                    fieldsContainer.innerHTML = ''; // Clear previous fields

                    // Populate fields dynamically
                    for (const [key, value] of Object.entries(plDetails)) {
                        const fieldDiv = document.createElement('div');
                        fieldDiv.className = 'flex flex-col';

                        const label = document.createElement('label');
                        label.className = 'text-sm font-medium text-gray-700 mb-1';
                        label.textContent = key.replace(/_/g, ' ').toUpperCase();

                        const input = document.createElement('input');
                        input.type = 'text';
                        input.name = key;
                        input.value = value || '';
                        input.className = 'border rounded px-3 py-2 w-full';

                        fieldDiv.appendChild(label);
                        fieldDiv.appendChild(input);
                        fieldsContainer.appendChild(fieldDiv);
                    }

                    // Set hidden field value
                    document.getElementById('purchase_list_id').value = plId;
                   
                } else {
                    alert('Failed to fetch purchase list details.');
                }
            })
            .catch(error => {
                console.error('Error fetching purchase list details:', error);
                alert('An error occurred while fetching purchase list details.');
            });
    }
    // show/hide popup
    document.getElementById('close-bd-popup-btn-bd').addEventListener('click', function() {
        const modalSlider = document.getElementById('modal-slider-bd');
        modalSlider.classList.add('translate-x-full');
        const detailModal = bootstrap.Modal.getInstance(document.getElementById('DetailModal'));
        detailModal.hide();
    });


</script>