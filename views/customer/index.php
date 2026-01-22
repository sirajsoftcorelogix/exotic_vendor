<?php 
// 1. Set action to empty or base url
$formAction = base_url(); 
?>

<div class="max-w-[1400px] mx-auto px-4 sm:px-6 pt-[10px]">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <h1 class="text-2xl font-normal text-gray-800">Customer Management</h1>
        
        <button type="button" class="bg-[#d97706] hover:bg-[#b45309] text-white px-5 py-2.5 rounded shadow-sm text-sm font-medium transition-colors whitespace-nowrap">
            + Add New
        </button>
    </div>

    <form method="GET" action="<?php echo $formAction; ?>" class="mb-8">
        <input type="hidden" name="page" value="customer">
        <input type="hidden" name="action" value="index">

        <div class="flex flex-wrap items-center gap-3">
            
            <div class="flex w-full sm:w-auto grow sm:grow-0 shadow-sm rounded-md">
                <input type="text" name="search" value="<?php echo htmlspecialchars($data['filters']['search']); ?>" placeholder="Search..." 
                       class="pl-4 pr-4 py-2.5 border border-gray-200 border-r-0 rounded-l-md text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 w-full sm:w-64">
                
                <button type="submit" class="bg-gray-100 border border-gray-200 hover:bg-gray-200 text-gray-600 px-4 rounded-r-md transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </div>

            <div class="relative w-full sm:w-auto grow sm:grow-0">
                <select name="state" onchange="this.form.submit()" class="appearance-none bg-white border border-gray-200 text-gray-800 py-2.5 pl-4 pr-8 rounded shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer w-full sm:w-40">
                    <option value="">All Locations</option>
                    <?php if (!empty($data['states'])): ?>
                        <?php foreach ($data['states'] as $stateOption): ?>
                            <option value="<?php echo htmlspecialchars($stateOption); ?>" 
                                <?php echo ($data['filters']['state'] == $stateOption) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($stateOption); ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <div class="relative w-auto">
                <select name="limit" onchange="this.form.submit()" class="appearance-none bg-white border border-gray-200 text-gray-800 py-2.5 pl-3 pr-8 rounded shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer">
                    <option value="20" <?php echo ($data['filters']['limit'] == 20) ? 'selected' : ''; ?>>20 rows</option>
                    <option value="50" <?php echo ($data['filters']['limit'] == 50) ? 'selected' : ''; ?>>50 rows</option>
                    <option value="100" <?php echo ($data['filters']['limit'] == 100) ? 'selected' : ''; ?>>100 rows</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                </div>
            </div>

            <?php if(!empty($data['filters']['search']) || !empty($data['filters']['state']) || $data['filters']['limit'] != 20): ?>
            <a href="?page=customer&action=index" class="text-red-500 hover:text-red-700 text-sm font-medium px-2 py-2.5 transition-colors flex items-center gap-1" title="Reset Filters">
                <span>Clear</span>
                <span class="text-lg leading-none">&times;</span>
            </a>
            <?php endif; ?>
        </div>
    </form>

   <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
        <?php if(empty($data['customers'])): ?>
            <div class="col-span-full text-center py-10 text-gray-500">No customers found matching your criteria.</div>
        <?php else: ?>
            <?php foreach ($data['customers'] as $value) { ?>
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
                
                <div class="flex justify-between items-start mb-2">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-9 3-9m-9 9c-1.657 0-3-9-3-9m9 9V3m0 18v-9" />
                        </svg>
                        <h3 class="text-[#d97706] font-semibold text-base">
                            <?php echo $value['name']; ?>
                        </h3>
                    </div>
                    
                    <button class="text-gray-500 hover:text-gray-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                </div>

                <div class="relative">
                    <div class="flex justify-between items-center mb-4">
                        <p class="text-sm text-gray-500">Customer ID : <span class="text-gray-800"><?php echo $value['id']; ?></span></p>
                        
                        <button class="text-red-500 hover:text-red-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <div class="space-y-2 text-sm">
                        <div class="flex">
                            <span class="w-20 text-gray-500">Email</span><span class="text-gray-500 mr-2">:</span>
                            <span class="text-gray-700 truncate"><?php echo $value['email'] ?? '-'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-20 text-gray-500">Mobile</span><span class="text-gray-500 mr-2">:</span>
                            <span class="text-gray-700"><?php echo $value['phone'] ?? '-'; ?></span>
                        </div>
                        <div class="flex">
                            <span class="w-20 text-gray-500">Location</span><span class="text-gray-500 mr-2">:</span>
                            <span class="text-gray-700"><?php echo $value['state'] ?? 'N/A'; ?></span>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-gray-200 my-4"></div>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-900">Total Purchases</span>
                        <span class="font-medium text-gray-900">
                            <?php echo $value['currency'] ?? '₹'; ?> <?php echo number_format($value['total_order_amount'] ?? 0, 2); ?>
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-900">Last Purchase</span>
                        <span class="font-medium text-gray-900">
                            <?php echo !empty($value['last_purchase_date']) ? date('j/n/Y', strtotime($value['last_purchase_date'])) : '-'; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php } ?>
        <?php endif; ?>
    </div>

    <?php if ($data['pagination']['total_pages'] > 1): ?>
    <div class="flex justify-center items-center gap-2 mb-8">
        <?php 
            $queryParams = array_merge($_GET, ['page' => 'customer', 'action' => 'index']);
            $currentPage = $data['filters']['page_no'];
            $totalPages = $data['pagination']['total_pages'];
        ?>

        <?php if ($currentPage > 1): ?>
            <?php $queryParams['page_no'] = $currentPage - 1; ?>
            <a href="?<?php echo http_build_query($queryParams); ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-600">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <?php $queryParams['page_no'] = $i; ?>
            <a href="?<?php echo http_build_query($queryParams); ?>" 
               class="px-3 py-1 border rounded <?php echo ($i == $currentPage) ? 'bg-[#d97706] text-white border-[#d97706]' : 'hover:bg-gray-50 text-gray-600'; ?>">
                <?php echo $i; ?>
            </a>
        <?php endfor; ?>

        <?php if ($currentPage < $totalPages): ?>
            <?php $queryParams['page_no'] = $currentPage + 1; ?>
            <a href="?<?php echo http_build_query($queryParams); ?>" class="px-3 py-1 border rounded hover:bg-gray-50 text-gray-600">Next</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>