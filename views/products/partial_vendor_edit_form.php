
    

        <!-- ================= SELECTED VENDORS LIST (INDIVIDUAL GRAY CARDS) ================= -->
       
            
            <?php 
            if ($vendors) {
            foreach ($vendors as $vendor): ?>
            <!-- Vendor Item 1 -->
            <!-- Changed items-start to items-center to center the cross button vertically -->
            <div class="vendor-item bg-brand-light-gray border border-gray-200 rounded-lg p-5 flex justify-between items-center shadow-sm hover:shadow-md transition-shadow">
                <div class="flex-grow">
                    <!-- Vendor Name (Orange) -->
                    <div class="text-brand-orange font-bold text-xl mb-3">
                        <?php echo htmlspecialchars($vendor['vendor_name']); ?>
                    </div>
                    <!-- Vendor Details Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-y-2 gap-x-4 text-sm text-gray-600">
                        <div>
                            <!-- Removed 'uppercase' class -->
                            <span class="block text-xs font-bold text-gray-400">Contact Person</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vendor['contact_name']); ?></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-gray-400">Phone</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vendor['vendor_phone']); ?></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-gray-400">City</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vendor['city']); ?></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-gray-400">State</span>
                            <span class="font-medium text-gray-800"><?php echo htmlspecialchars($vendor['state']); ?></span>
                        </div>
                        <div>
                            <span class="block text-xs font-bold text-gray-400">Agent</span>
                            <span class="font-medium text-gray-800">Direct</span>
                        </div>
                    </div>
                </div>

                <!-- Remove Button: Centered via flex parent, removed mt-1 -->
                <button onclick="removeVendor(this,<?php echo $vendor['id']; ?>)" data-item-code="<?php echo $vendor['item_code']; ?>" class="ml-4 w-8 h-8 bg-brand-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm flex-shrink-0">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <?php endforeach; 
            } else {
                echo '<p>Vendors not found.</p>';
            }
            ?>

        
