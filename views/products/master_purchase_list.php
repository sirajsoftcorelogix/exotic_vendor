<div class="mx-auto space-y-6 mr-4">
    <div class="mt-6 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Master Purchase List Advanced Search</h2>
            <svg id="accordion-icon" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>
        <!-- Advance search and filters can be added here -->
        <?php
        $hasFilters = !empty($_GET['search'])
            || !empty($_GET['added_by'])
            || !empty($_GET['assigned_to'])
            || !empty($_GET['date_from'])
            || !empty($_GET['date_to'])
            || (!empty($_GET['category']) && $_GET['category'] !== 'all')
            || (!empty($_GET['status']) && $_GET['status'] !== 'all');
        ?> 
        <div id="accordion-content" class="accordion-content <?= $hasFilters ? '' : 'hidden' ?>">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-6 items-end">
                <form method="GET" class="contents">
                    <input type="hidden" name="page" value="products">
                    <input type="hidden" name="action" value="master_purchase_list">

                    <!-- Search -->
                    <div class="col-span-3">
                        <label class="text-sm text-gray-600">Search:</label>
                        <input type="text" name="search"
                            placeholder="Search by Item Code, Title..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                            class="border rounded px-3 py-2 w-full">
                    </div>

                    <!-- Added By -->
                    <div>
                        <label class="text-sm text-gray-600">Added By:</label>
                        <select name="added_by" class="border border-gray-300 rounded px-3 py-2 w-full">
                            <option value="">Select</option>
                            <?php foreach ($staff_list as $id => $name): ?>
                                <option value="<?= $id ?>" <?= ($_GET['added_by'] ?? '') == $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Assigned To -->
                    <div>
                        <label class="text-sm text-gray-600">Assigned To:</label>
                        <select name="assigned_to" class="border border-gray-300 rounded px-3 py-2 w-full">
                            <option value="">Select</option>
                            <?php foreach ($staff_list as $id => $name): ?>
                                <option value="<?= $id ?>" <?= ($_GET['assigned_to'] ?? '') == $id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($name) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Date Range Type -->
                    <div>
                        <label class="text-sm text-gray-600">Date Range Type:</label>
                        <select name="date_type" class="border rounded px-3 py-2 w-full">
                            <option value="">Select</option>

                            <option value="added" <?= ($_GET['date_type'] ?? '') === 'added' ? 'selected' : '' ?>>
                                Added Date
                            </option>

                            <option value="purchased" <?= ($_GET['date_type'] ?? '') === 'purchased' ? 'selected' : '' ?>>
                                Purchased Date
                            </option>
                        </select>
                    </div>

                    <!-- Date From -->
                    <div>
                        <label class="text-sm text-gray-600">Date From:</label>
                        <input type="date" name="date_from"
                            value="<?= htmlspecialchars($_GET['date_from'] ?? '') ?>"
                            class="border rounded px-3 py-2 w-full">
                    </div>

                    <!-- Date To -->
                    <div>
                        <label class="text-sm text-gray-600">Date To:</label>
                        <input type="date" name="date_to"
                            value="<?= htmlspecialchars($_GET['date_to'] ?? '') ?>"
                            class="border rounded px-3 py-2 w-full">
                    </div>


                    <!-- Category -->
                    <div>
                        <label class="text-sm text-gray-600">Category:</label>
                        <select name="category" class="border rounded px-3 py-2 w-full">
                            <option value="all">All</option>
                            <?php foreach (($data['categories'] ?? []) as $cat): ?>
                                <option value="<?= htmlspecialchars($cat) ?>"
                                    <?= ($_GET['category'] ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="text-sm text-gray-600">Status:</label>
                        <select name="status" class="border rounded px-3 py-2 w-full">
                            <option value="all">All</option>
                            <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="purchased" <?= ($_GET['status'] ?? '') === 'purchased' ? 'selected' : '' ?>>Purchased</option>
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="mt-4 flex items-center gap-4">
                        <button type="button"
                            onclick="window.location.href='?page=products&action=master_purchase_list'"
                            class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700">
                            Reset
                        </button>
                        <button type="submit"
                            class="bg-orange-400 text-white px-8 py-2 rounded hover:bg-orange-600">
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                            <!-- <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Agent Name</th> -->
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Added By</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                <button class="flex items-center gap-2 hover:text-gray-700"
                                    onclick="sortTableByDate()">
                                    Date Added
                                    <svg xmlns="http://www.w3.org/2000/svg"
                                        class="h-8 w-8 text-gray-400"
                                        viewBox="0 0 20 20"
                                        fill="currentColor">
                                        <path d="M5 8l5-5 5 5H5z" />
                                        <path d="M5 12l5 5 5-5H5z" />
                                    </svg>
                                </button>
                            </th>

                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Purchased</th>
                            <th class="px-0 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($data['purchase_list'] as $pl): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-orange-500 hover:text-orange-700 cursor-pointer"
                                    onclick="viewPurchaseListDetails('<?php echo $pl['id']; ?>')">
                                    <?php echo htmlspecialchars($pl['item_code'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-normal text-sm text-gray-900 break-words">
                                    <?php echo htmlspecialchars($pl['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($pl['category'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo (int)($pl['quantity'] ?? 0); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo ucfirst(htmlspecialchars($pl['status'] ?? '', ENT_QUOTES, 'UTF-8')); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo htmlspecialchars($pl['added_by'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php
                                    $date_added = $pl['date_added_readable']
                                        ?? (!empty($pl['date_added']) ? date('d M Y', strtotime($pl['date_added'])) : 'N/A');

                                    echo htmlspecialchars($date_added, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </td>

                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php
                                    $date_purchased = $pl['date_purchased_readable']
                                        ?? (!empty($pl['date_purchased']) ? date('d M Y', strtotime($pl['date_purchased'])) : 'N/A');

                                    echo htmlspecialchars($date_purchased, ENT_QUOTES, 'UTF-8');
                                    ?>
                                </td>

                                <!-- action dropdown -->
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-center">
                                    <div class="menu-wrapper">
                                        <button class="menu-button text-gray-500 hover:text-gray-700" onclick="toggleMenu(this)">
                                            &#x22EE;
                                        </button>
                                        <ul class="menu-popup text-left">
                                            <li>
                                                <a href="javascript:void(0);"
                                                    onclick="viewPurchaseListDetails('<?php echo $pl['id']; ?>')"
                                                    class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                    View / Edit Details
                                                </a>
                                            </li>
                                            <li>
                                                <a href="javascript:void(0);"
                                                    class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                                                    onclick="deletePurchaseListItem('<?php echo $pl['id']; ?>')">
                                                    Delete
                                                </a>
                                            </li>
                                        </ul>
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
                        <a class="page-link px-2 py-1 rounded <?php if ($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                            href="?page=products&action=master_purchase_list&page_no=<?= $page - $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            &laquo; Prev
                        </a>
                        <!-- Page Slots -->
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                                href="?page=products&action=master_purchase_list&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <!-- Next Button -->
                        <a class="page-link px-2 py-1 rounded <?php if ($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                            href="?page=products&action=master_purchase_list&page_no=<?= $page + $slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                    <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                        onchange="location.href='?page=products&action=master_purchase_list&page_no=1&limit=' + this.value;">
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
<div id="DetailModal" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden">
    <div id="modal-slider-bd" class="fixed top-0 right-0 h-full flex transform translate-x-full" style="width: 500px; max-width: 100%; transition: transform 0.3s ease-in-out;">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-pl-modal"
                class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center -ml-[61px]"
                style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="purchase-list-popup-panel" class="h-full bg-white shadow-xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-1xl font-bold text-gray-800 mb-6 pb-6 border-b p-title"></h2>
                    <div id="plDetailMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="plDetailForm">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="editPlDetails">
                        <input type="hidden" name="purchase_list_id" id="purchase_list_id" value="">

                        <!-- Basic Information -->
                        <div class="pt-4">

                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4 text-sm" id="pl-detail-fields">
                                <!-- Dynamic fields will be inserted here -->

                                <div><strong>Title : </strong></div>
                                <div><strong>Assigned Agent : </strong> </div>
                                <div><strong>Date Added : </strong></div>
                                <div><strong>Date Purchased : </strong></div>
                                <div><strong>SKU : </strong></div>
                                <div><strong>Color : </strong></div>
                                <div><strong>Size : </strong></div>
                                <div><strong>Material : </strong></div>
                                <div><strong>Dimensions : </strong></div>
                                <div><strong>Weight : </strong></div>
                                <div><strong>Quantity : </strong> <input type="number" value="" class="border rounded px-2 py-1 mt-1 w-16"></div>
                                <div class="col-span-2"><strong>Remark : </strong> <textarea class="border rounded px-2 py-1 mt-1 w-full"></textarea></div>

                            </div>
                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-bd-btn" class="action-btn cancel-btn">Close</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteConfirmModal"
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    
    <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6">
        <h2 class="text-lg font-semibold text-gray-800 mb-2">
            Confirm Deletion
        </h2>

        <p class="text-sm text-gray-600 mb-6">
            Are you sure you want to delete this purchase list item?
            This action cannot be undone.
        </p>

        <div class="flex justify-end gap-3">
            <button onclick="closeDeleteModal()"
                    class="px-4 py-2 rounded bg-gray-200 text-gray-700 hover:bg-gray-300">
                Cancel
            </button>

            <button onclick="confirmDelete()"
                    class="px-4 py-2 rounded bg-red-600 text-white hover:bg-red-700">
                Delete
            </button>
        </div>
    </div>
</div>
<!-- Alert Message -->
</div>
</div>
<script>
    //right side popup on item_code click 
    function viewPurchaseListDetails(plId) {
        //DetailModal show
        document.getElementById('DetailModal').classList.remove('hidden');
        document.getElementById('modal-slider-bd').classList.remove('translate-x-full');

        // Clear previous messages
        document.getElementById('plDetailMsg').innerHTML = '';

        // Fetch purchase list details via AJAX
        fetch(`<?php echo base_url('?page=products&action=get_purchase_list_details'); ?>&id=${plId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const plDetails = data.purchaseItem;
                    console.log(plDetails);
                    const fieldsContainer = document.getElementById('pl-detail-fields');
                    //fieldsContainer.innerHTML = ''; // Clear previous fields

                    // Populate fields dynamically
                    document.querySelector('.p-title').innerText = `${plDetails.title || 'N/A'}`;
                    fieldsContainer.innerHTML = `
                        <!-- Product Image -->
                        <div class="col-span-2">
                            <strong>Product Image : </strong>
                            ${
                                plDetails.image
                                    ? `<div class="mt-2">
                                        <img src="${plDetails.image}" alt="Product Image"
                                            class="h-32 w-32 object-cover rounded border" />
                                    </div>`
                                    : `<span>N/A</span>`
                            }
                        </div>
                        <div><strong>Assigned Agent : </strong> ${plDetails.agent_name || 'N/A'}</div>
                        <div><strong>Date Added : </strong> ${plDetails.date_added_readable || 'N/A'}</div>
                        <div><strong>Add By : </strong> ${plDetails.added_by_name || 'N/A'}</div>
                        <div><strong>Purchased Date: </strong> ${plDetails.date_purchased_readable || 'N/A'}</div>
                        <div><strong>SKU : </strong> ${plDetails.sku || 'N/A'}</div>
                        <div><strong>Color : </strong> ${plDetails.color || 'N/A'}</div>
                        <div><strong>Size : </strong> ${plDetails.size || 'N/A'}</div>
                        <div><strong>Material : </strong> ${plDetails.material || 'N/A'}</div>
                        <div><strong>Dimensions : </strong> ${plDetails.dimensions || 'N/A'}</div>
                        <div><strong>Weight : </strong> ${plDetails.weight || 'N/A'}</div>

                        <!-- Quantity -->
                        <div>
                            <strong>Quantity : </strong>
                            <input type="number"
                                id="pl-quantity"
                                value="${plDetails.quantity || 0}"
                                class="border rounded px-2 py-1 mt-1 w-16">
                        </div>

                        <!-- Status dropdown -->
                        <div>
                            <strong>Status : </strong>
                            <select id="pl-status" class="border rounded px-2 py-1 mt-1 w-32">
                                <option value="purchased" ${plDetails.status === 'purchased' ? 'selected' : ''}>Purchased</option>
                                <option value="pending" ${plDetails.status === 'pending' ? 'selected' : ''}>Pending</option>
                            </select>
                        </div>

                        <!-- Remark -->
                        <div class="col-span-2">
                            <strong>Remark : </strong>
                            <textarea id="pl-remark"
                                rows="3"
                                class="border rounded px-2 py-1 mt-1 w-full">${plDetails.remarks || ''}</textarea>
                        </div>
                    `;

                    // Set hidden field value
                    document.getElementById('purchase_list_id').value = plId;

                } else {
                    showAlert('Failed to fetch purchase list details.');
                }
            })
            .catch(error => {
                console.error('Error fetching purchase list details:', error);
                showAlert('An error occurred while fetching purchase list details.');
            });
    }
    // Handle form submission for editing purchase list details
    document.getElementById('plDetailForm').addEventListener('submit', function(event) {
        event.preventDefault();

        const plId = document.getElementById('purchase_list_id').value;
        const quantity = document.getElementById('pl-quantity').value;
        const remark = document.getElementById('pl-remark').value;
        const status = document.getElementById('pl-status').value;

        // Prepare data to send
        const formData = new FormData();
        formData.append('page', 'products');
        formData.append('action', 'editPlDetails');
        formData.append('id', plId);
        formData.append('quantity', quantity);
        formData.append('status', status);
        formData.append('remarks', remark);

        // Send AJAX request to update details
        fetch(`<?php echo base_url('?page=products&action=update_purchase_item'); ?>`, {
                method: 'POST',
                body: JSON.stringify(Object.fromEntries(formData))
            })
            .then(response => response.json())
            .then(data => {
                const msgDiv = document.getElementById('plDetailMsg');
                if (data.success) {
                    msgDiv.innerHTML = '<span class="text-green-600">Purchase list details updated successfully.</span>';
                } else {
                    msgDiv.innerHTML = '<span class="text-red-600">Failed to update purchase list details.</span>';
                }
            })
            .catch(error => {
                console.error('Error updating purchase list details:', error);
                const msgDiv = document.getElementById('plDetailMsg');
                msgDiv.innerHTML = '<span class="text-red-600">An error occurred while updating purchase list details.</span>';
            });
    });

    // show/hide popup
    document.getElementById('close-pl-modal').addEventListener('click', function() {
        document.getElementById('DetailModal').classList.add('hidden');
        document.getElementById('modal-slider-bd').classList.add('translate-x-full');
    });


    document.getElementById('cancel-bd-btn').addEventListener('click', function() {
        document.getElementById('DetailModal').classList.add('hidden');
        document.getElementById('modal-slider-bd').classList.add('translate-x-full');
    });

    //toggleMenu
    function toggleMenu(button) {
        const popup = button.nextElementSibling;
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';

        // Close other open menus
        document.querySelectorAll('.menu-popup').forEach(menu => {
            if (menu !== popup) menu.style.display = 'none';
        });
    }
    // Close menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.menu-wrapper')) {
            document.querySelectorAll('.menu-popup').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    // Delete purchase list item
    /*function deletePurchaseListItem(plId) {
        if (confirm('Are you sure you want to delete this purchase list item?')) {
            fetch(`<?php echo base_url('?page=products&action=delete_purchase_list_item'); ?>`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: plId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert('Purchase list item deleted successfully.');
                        setTimeout(() => {
                            location.reload();

                        }, 3000);
                    } else {
                        showAlert('Failed to delete purchase list item.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting purchase list item:', error);
                    showAlert('An error occurred while deleting the purchase list item.');
                });
        }
    }*/

    let deletePlId = null;

    function deletePurchaseListItem(plId) {
        deletePlId = plId;
        document.getElementById('deleteConfirmModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        deletePlId = null;
        document.getElementById('deleteConfirmModal').classList.add('hidden');
    }

    function confirmDelete() {
        if (!deletePlId) return;

        fetch(`<?php echo base_url('?page=products&action=delete_purchase_list_item'); ?>`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ id: deletePlId })
        })
        .then(response => response.json())
        .then(data => {
            closeDeleteModal();

            if (data.success) {
                showAlert('Purchase list item deleted successfully.');
                setTimeout(() => location.reload(), 1500);
            } else {
                showAlert('Failed to delete purchase list item.');
            }
        })
        .catch(error => {
            closeDeleteModal();
            console.error('Error deleting purchase list item:', error);
            showAlert('An error occurred while deleting the purchase list item.');
        });
    }

    // Accordion functionality
    const accordionButton = document.getElementById('accordion-button');
    const accordionContent = document.getElementById('accordion-content');
    const accordionIcon = document.getElementById('accordion-icon');
    accordionButton.addEventListener('click', () => {
        accordionContent.classList.toggle('hidden');
        accordionIcon.classList.toggle('rotate-180');
    });

    function sortTableByDate() {
        const url = new URL(window.location.href);
        const current = url.searchParams.get('sort_by_date') || 'desc';
        const next = current === 'asc' ? 'desc' : 'asc';

        url.searchParams.set('sort_by_date', next);
        window.location.href = url.toString();
    }
</script>