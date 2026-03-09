<div class="mx-auto space-y-6 mr-4">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Customer List</h1>
        <a href="<?php echo base_url('?page=customer&action=create'); ?>" class="bg-indigo-600 text-white px-6 py-2 rounded-md hover:bg-indigo-700">+ Create Customer</a>
    </div>
    <div class="mt-6 mb-8 bg-white rounded-xl p-4 ">
        <button id="accordion-button" class="w-full flex justify-between items-center mb-2">
            <h2 class="text-xl font-bold text-gray-900">Customer Search</h2>
            <svg id="accordion-icon" class="w-6 h-6 transition-transform transform" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
        </button>

        <div id="accordion-content" class="accordion-content hidden">
             <form method="GET" action="<?php echo base_url('?page=customer&action=list'); ?>">
                <input type="hidden" name="page" value="customer">
                <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-semibold">Customer Name:</label>
                    <input type="text" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" name="search" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2" placeholder="Search by name, email...">
                </div>

                <div>
                    <label class="text-sm font-semibold">State:</label>
                    <select name="state" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2">
                        <option value="">All States</option>
                        <?php foreach ($states as $state): ?>
                            <option value="<?= htmlspecialchars($state) ?>" <?= (isset($filters['state']) && $filters['state'] === $state) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-sm font-semibold">Rows per page:</label>
                    <select name="limit" class="w-full mt-1 border border-gray-300 rounded-md px-3 py-2">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= (isset($filters['limit']) && $filters['limit'] == $opt) ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex items-end">
                    <button class="w-full bg-orange-500 hover:bg-orange-600 text-white font-semibold py-2 rounded-md transition">
                        Search
                    </button>
                </div>
            </div>
            </form>
        </div>
    </div>
    <!-- Customers Table -->
    <div class="overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full">
            <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Customer Name</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Email</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Phone</th>
                <th class="px-6 py-3 text-left text-sm font-semibold text-gray-900">Total Amount</th>
               
                <th class="px-6 py-3 text-right text-sm font-semibold text-gray-900">Actions</th>
            </tr>
            </thead>
            <tbody>
                <?php if (!empty($customers)): ?>
                    <?php foreach ($customers as $customer): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($customer['name']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($customer['email']) ?></td>
                            <td class="px-6 py-4 text-sm"><?= htmlspecialchars($customer['phone']) ?></td>
                            <td class="px-6 py-4 text-sm font-semibold"></td>
                            
                            <td class="px-6 py-4 text-right text-sm space-x-2">
                                <a href="<?php echo base_url('?page=customer&action=view&customer_id=' . $customer['id']); ?>" class="text-indigo-600 hover:text-indigo-900">View</a>
                                <a href="<?php echo base_url('?page=customer&action=edit&customer_id=' . $customer['id']); ?>" class="text-green-600 hover:text-green-900">Edit</a>
                                <a href="<?php echo base_url('?page=customer&action=delete&customer_id=' . $customer['id']); ?>" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this customer?');">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
            <tr>
                <td colspan="6" class="px-6 py-8 text-center text-gray-500">No customers found.</td>
            </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4 flex justify-center">
        <?php         
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // Orders per page, default 20
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values
        $total_records = isset($data['total_records']) ? (int)$data['total_records'] : 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
        
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
                        <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($data['customers']) ?></span> of <span class="font-medium"><?= $total_records ?></span> customers</p>
                    </div>
                    <?php            
                    //echo '****************************************  '.$query_string;
                    if ($total_pages > 1): ?>          
                    <!-- Prev Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=orders&action=customer&page_no=<?= $page-$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            &laquo; Prev
                        </a>
                        <!-- Page Slots -->
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                            href="?page=orders&action=customer&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <!-- Next Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=orders&action=customer&page_no=<?= $page+$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                    <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                            onchange="location.href='?page=orders&action=customer&page_no=1&limit=' + this.value;">
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
    const accordionButton = document.getElementById('accordion-button');
    const accordionContent = document.getElementById('accordion-content');
    const accordionIcon = document.getElementById('accordion-icon');

    accordionButton.addEventListener('click', () => {
        accordionContent.classList.toggle('hidden');
        accordionIcon.classList.toggle('rotate-180');
    });
</script>
