<div class="max-w-[1400px] mx-auto">
    <div class="flex flex-col md:flex-row justify-between items-center mb-8 gap-4">
        <h1 class="text-2xl font-normal text-gray-800">Customer Management</h1>
        
        <div class="flex items-center gap-4">
            <button class="bg-[#d97706] hover:bg-[#b45309] text-white px-6 py-2.5 rounded shadow-sm text-sm font-medium transition-colors">
                Add New Customer
            </button>
            
            <div class="relative">
                <select class="appearance-none bg-gray-50 border border-gray-200 text-gray-800 py-2.5 pl-4 pr-10 rounded shadow-sm text-sm focus:outline-none focus:ring-1 focus:ring-orange-500 cursor-pointer min-w-[140px]">
                    <option>Mumbai</option>
                    <option>Delhi</option>
                    <option>Bangalore</option>
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-gray-500">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
    	<?php foreach ($data as $key => $value) { ?>
        <div class="bg-white rounded-lg shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-start mb-2">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-9 3-9m-9 9c-1.657 0-3-9-3-9m9 9V3m0 18v-9" />
                    </svg>
                    <h3 class="text-[#d97706] font-semibold text-base"><?php echo $value['name']; ?></h3>
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
                        <span class="w-20 text-gray-500">Email</span>
                        <span class="text-gray-500 mr-2">:</span>
                        <span class="text-gray-700 truncate"><?php echo $value['email']; ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-20 text-gray-500">Mobile</span>
                        <span class="text-gray-500 mr-2">:</span>
                        <span class="text-gray-700"><?php echo $value['phone']; ?></span>
                    </div>
                    <div class="flex">
                        <span class="w-20 text-gray-500">Location</span>
                        <span class="text-gray-500 mr-2">:</span>
                        <span class="text-gray-700">Mumbai</span>
                    </div>
                </div>
            </div>

            <div class="h-px bg-gray-200 my-4"></div>

            <div class="space-y-2 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-900">Total Purchases</span>
                    <span class="font-medium text-gray-900">₹45,680</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-900">Last Purchase</span>
                    <span class="font-medium text-gray-900">8/12/2025</span>
                </div>
            </div>
        </div>
       	<?php } ?>
    </div>
</div>
<div><?php echo "<pre>"; print_r($data);  ?></div>