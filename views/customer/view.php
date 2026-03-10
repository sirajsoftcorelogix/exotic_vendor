<?php
// Extract customer data
$customerId = $customer['id'] ?? null;
$customerName = $customer['name'] ?? 'N/A';
$customerEmail = $customer['email'] ?? 'N/A';
$customerPhone = $customer['phone'] ?? 'N/A';

// Generate avatar initials
$initials = '';
if (!empty($customerName)) {
    $nameParts = explode(' ', trim($customerName));
    foreach ($nameParts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    $initials = substr($initials, 0, 2); // Limit to 2 chars
}

// Format phone and email for display
$phoneDisplay = !empty($customerPhone) ? $customerPhone : 'N/A';
$emailDisplay = !empty($customerEmail) ? $customerEmail : 'N/A';
?>

<div class="mx-auto space-y-6 mr-4 mb-10 mt-4">
<!-- Customer Profile Header (Compact) -->
<div class="bg-white rounded-lg shadow-sm border border-gray-100 p-4">
  <div class="flex flex-wrap items-center gap-4 md:gap-6">
    <!-- Avatar + Name + Contact -->
    <div class="flex items-center gap-3 flex-shrink-0">
      <div class="w-12 h-12 rounded-lg bg-orange-100 flex items-center justify-center">
        <span class="text-lg font-bold text-orange-500"><?php echo htmlspecialchars($initials); ?></span>
      </div>
      <div>
        <h1 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($customerName); ?></h1>
        <p class="text-sm text-gray-500"><?php echo htmlspecialchars($phoneDisplay); ?> · <?php echo htmlspecialchars($emailDisplay); ?></p>
      </div>
    </div>
    <!-- Stats -->
    <div class="flex flex-wrap gap-4 md:gap-6 flex-1 min-w-0">
      <div><span class="text-xs text-gray-500">Orders</span><p class="text-base font-bold text-orange-500"><?php echo $customerOrderCount ?? '0'; ?></p></div>
      <div><span class="text-xs text-gray-500">Total</span><p class="text-base font-bold text-orange-500"><?php echo $customerTotalSpent ?? '0.00'; ?></p></div>
      <div><span class="text-xs text-gray-500">Avg</span><p class="text-base font-bold text-orange-500"><?php echo $customerAverageOrderValue ?? '0.00'; ?></p></div>
      <div class="flex flex-wrap gap-1.5 items-center">
        <span class="px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">Pending —</span>
        <span class="px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">Progress —</span>
        <span class="px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">Done —</span>
        <span class="px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">Cancelled —</span>
      </div>
    </div>
  </div>
</div>


<!-- ACTION BAR -->
<div class="flex flex-wrap gap-4 items-center mt-6 relative z-10">

<div class="relative">
<button type="button" class="bg-orange-500 text-white px-5 py-2 rounded" onclick="document.getElementById('actionMenu').classList.toggle('hidden')" aria-label="Action menu">
Action
</button>
<div id="actionMenu" class="hidden absolute left-0 top-full mt-2 w-48 bg-white shadow-lg rounded border py-1 z-50">
<!-- <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Create Invoice</a> -->
<a href="<?php echo base_url('?page=dispatch&action=bulk_dispatch'); ?>" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Dispatch</a>
<!-- <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update Status</a> -->
</div>
</div>

<!-- Search bar -->
<div class="flex-1 min-w-[200px] max-w-md">
  <div class="relative">
    <form method="GET" action="<?php echo base_url('?page=customer&action=view&customer_id=' . $customerId); ?>">
        <input type="hidden" name="page" value="customer">
        <input type="hidden" name="action" value="view">
        <input type="hidden" name="customer_id" value="<?php echo htmlspecialchars($customerId); ?>">
    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
    <input type="text" name="search" id="orderSearch" placeholder="Search by order no, item code, status..." class="w-full pl-9 pr-4 py-2 border border-gray-200 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 text-sm">
    </form>  
</div>
</div>

<div class="flex gap-3">
<select class="border rounded px-3 py-2 text-sm" onchange="location.href='?page=customer&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=1&limit=<?= $limit ?>&sort=' + this.value;">
<option value="new_to_old" <?= isset($_GET['sort']) && $_GET['sort'] === 'new_to_old' ? 'selected' : '' ?>>Sort By New to Old</option>
<option value="old_to_new" <?= isset($_GET['sort']) && $_GET['sort'] === 'old_to_new' ? 'selected' : '' ?>>Sort By Old to New</option>
</select>
<!-- <select id="perPageSelect" class="border rounded px-3 py-2 text-sm" onchange="location.href='?page=customer&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=1&limit=' + this.value;">
<option value="10" selected>10 per page</option>
<option value="20">20 per page</option>
<option value="50">50 per page</option>
<option value="100">100 per page</option>
</select> -->
</div>

</div>

<!-- ORDER CARDS -->
<div id="orderCardsWrapper">
<?php 
if (!empty($orders) && is_array($orders)): 
    foreach ($orders as $order): 
        $orderNumber = $order['order_number'] ?? 'N/A';
        $itemCode = $order['item_code'] ?? 'N/A';
        $status = $order['status'] ?? 'pending';
        $orderDate = isset($order['order_date']) ? date('d M Y', strtotime($order['order_date'])) : 'N/A';
        $paymentType = $order['payment_type'] ?? 'N/A';
        $quantity = $order['quantity'] ?? 0;
        $price = $order['itemprice'] ?? 0;
        $totalPrice = $order['finalprice'] ?? 0;
        $options = $order['options'] ?? '';
        
        // Status color mapping
        $statusColors = [
            'pending' => 'bg-amber-100 text-amber-800',
            'completed' => 'bg-green-100 text-green-800',
            'shipped' => 'bg-blue-100 text-blue-800',
            'cancelled' => 'bg-red-100 text-red-800',
            'ready_for_dispatch' => 'bg-yellow-100 text-yellow-800',
        ];
        $statusClass = $statusColors[$status] ?? 'bg-gray-100 text-gray-800';
?>
    <!-- ORDER CARD -->
    <div class="order-card-item bg-white shadow rounded-lg p-5 mt-6 relative">
        <div class="absolute top-4 right-4">
            <button type="button" class="p-1.5 rounded-full hover:bg-gray-100 text-gray-500 hover:text-gray-700 focus:outline-none focus:ring-2 focus:ring-orange-500" onclick="this.nextElementSibling.classList.toggle('hidden')" aria-label="Order options">
                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="6" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="18" r="1.5"/></svg>
            </button>
            <div class="order-card-menu hidden absolute right-0 mt-1 w-48 bg-white rounded-lg shadow-lg border py-1 z-50">
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">View details</a>
                <a href="#" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Update status</a>
                <a href="#" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100">Cancel order</a>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-6 items-center">
            <div class="flex gap-3">
                <input type="checkbox">
                <img src="<?php echo htmlspecialchars($order['image'] ?? 'https://via.placeholder.com/60'); ?>" class="w-16 h-16 object-cover border rounded">
                <div>
                    <p>Order No : <span class="text-blue-600"><a href="<?php echo base_url('?page=orders&action=get_order_details_html&type=outer&order_number=' . $order['id']); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars($orderNumber); ?></a></span></p>
                    <p>Item Code : <span class="text-blue-600"><a href="<?php echo base_url('?page=products&action=get_product_details_html&type=outer&item_code=' . $order['sku'] ?? $order['item_code']); ?>" target="_blank" class="hover:underline"><?php echo htmlspecialchars($itemCode); ?></a></span></p>
                    <p>Status : <span class="<?php echo $statusClass; ?> px-2 py-0.5 rounded text-xs font-medium inline-block"><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span></p>
                </div>
            </div>

            <div>
                <p><b>Order Date :</b> <?php echo htmlspecialchars($orderDate); ?></p>
                <p><b>Ship By Date :</b> —</p>
                <p><b>Payment Type :</b> <?php echo htmlspecialchars(strtoupper($paymentType)); ?></p>
            </div>

            <div>
                <p class="font-semibold mb-2">Addon</p>
                <?php if (!empty($options)): ?>
                    <?php 
                        $optionsList = explode(',', $options);
                        foreach ($optionsList as $addon): 
                            if (!empty(trim($addon))):
                    ?>
                    <button class="bg-orange-500 text-white px-3 py-1 rounded text-sm mb-1 block"><?php echo htmlspecialchars(trim($addon)); ?></button>
                    <?php 
                            endif;
                        endforeach;
                    ?>
                <?php else: ?>
                    <p class="text-gray-500 text-sm">—</p>
                <?php endif; ?>
            </div>

            <div class="text-right mr-20">
                <p>₹<?php echo number_format($price, 2); ?> x <?php echo (int)$quantity; ?> : ₹<?php echo number_format($price * $quantity, 2); ?></p>
                <div class="bg-orange-500 text-white px-4 py-2 rounded mt-2 inline-block">Total : ₹<?php echo number_format($totalPrice, 2); ?></div>
            </div>
        </div>

        <hr class="my-4">
        <div class="p-1 flex w-full">
            <div class="grid p-4 rounded-lg grid grid-cols-8 gap-y-2">
                <!-- Step 1: Approved -->
                <div class="timeline-step completed">
                    <div class="flex flex-col items-center text-center">
                        <div class="relative w-full h-5 flex justify-center items-center">
                            <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
                        </div>
                        <p class="timeline-text mt-2">Created</p>
                        <p class="timeline-date"><?php echo date('d M, Y', strtotime($order['order_date'])); ?></p>
                    </div>
                </div>
                <?php if (!empty($order['status_log'])) {
                    foreach ($order['status_log'] as $log) { ?>
                        <div class="timeline-step completed min-w-[120px]">
                            <div class="flex flex-col items-center text-center">
                                <div class="relative w-full h-5 flex justify-center items-center">
                                    <div class="w-[18px] h-[18px] rounded-full bg-[rgba(39,174,96,1)] z-10"></div>
                                </div>
                                <p class="timeline-text mt-2"><?php echo ucfirst(str_replace('_', ' ', $log['status'])); ?></p>
                                <p class="timeline-date"><?php echo date('d M, Y', strtotime($log['change_date'])); ?></p>
                                <p><?php echo $log['changed_by_username']; ?></p>
                            </div>
                        </div>
                    <?php }
                    } ?>
            </div>
        </div>
    </div>
<?php 
    endforeach;
else:
?>
    <div class="bg-white rounded-lg p-8 text-center mt-6">
        <p class="text-gray-500 text-lg">No orders found for this customer.</p>
    </div>
<?php 
endif; 
?>
</div>

 <!-- Pagination -->
    <div class="mt-4 flex justify-center">
        <?php         
        $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
        $page = $page < 1 ? 1 : $page;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10; // Orders per page, default 10
        $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 10; // Only allow specific values
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
                        <p class="text-sm text-gray-600">Showing <span class="font-medium"><?= count($data['orders']) ?></span> of <span class="font-medium"><?= $total_records ?></span> orders</p>
                    </div>
                    <?php            
                    //echo '****************************************  '.$query_string;
                    if ($total_pages > 1): ?>          
                    <!-- Prev Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=orders&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=<?= $page-$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            &laquo; Prev
                        </a>
                        <!-- Page Slots -->
                        <?php for ($i = $start; $i <= $end; $i++): ?>
                            <a class="page-link px-2 py-1 rounded <?= $i == $page ? 'bg-black text-white font-bold' : 'bg-gray-100 text-gray-700' ?>"
                            href="?page=orders&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=<?= $i ?>&limit=<?= $limit ?><?= $query_string ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        <!-- Next Button -->
                        <a class="page-link px-2 py-1 rounded <?php if($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                        href="?page=orders&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=<?= $page+$slot_size ?>&limit=<?= $limit ?><?= $query_string ?>">
                            Next &raquo;
                        </a>
                    <?php endif; ?>
                    <select id="rows-per-page" class="pagination-select bg-transparent border-b border-gray-400 focus:outline-none focus:border-gray-800 text-gray-600"
                            onchange="location.href='?page=orders&action=view&customer_id=<?= $_GET['customer_id'] ?>&page_no=1&limit=' + this.value;">
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
    // Close action menu when clicking outside
    document.addEventListener('click', function(event) {
        const actionMenu = document.getElementById('actionMenu');
        const actionButton = event.target.closest('button[aria-label="Action menu"]');
        if (!actionButton && !event.target.closest('#actionMenu')) {
            actionMenu.classList.add('hidden');
        }
    });
    // Close order card menu when clicking outside
    document.addEventListener('click', function(event) {
        const orderMenu = event.target.closest('.order-card-menu');
        const orderButton = event.target.closest('button[aria-label="Order options"]');
        if (!orderButton && !event.target.closest('.order-card-menu')) {
            document.querySelectorAll('.order-card-menu').forEach(menu => menu.classList.add('hidden'));
        }
    });
</script>