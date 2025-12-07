<?php

require_once 'settings/database/database.php';
require_once 'models/vendor/vendor.php';
require_once 'models/user/user.php';
$conn = Database::getConnection();
$vendorsModel = new Vendor($conn);
$usersModel = new User($conn);

foreach ($inbounding_data as $key => $value) {
    $vendor = $vendorsModel->getVendorById($value['vendor_code']);
    $inbounding_data[$key]['vendor_name'] = $vendor ? $vendor['vendor_name'] : '';
    $userDetails = $usersModel->getUserById($value['received_by_user_id']);
    $inbounding_data[$key]['received_name'] = $userDetails ? $userDetails['name'] : '';
}

unset($usersModel);
unset($vendorsModel);
?>

<div class="max-w-7xl mx-auto space-y-6">

    <div class="md:hidden bg-white rounded-xl overflow-hidden shadow-sm mb-4">
        <div class="bg-[#d9822b] px-4 py-3 flex items-center relative">
            <a href="javascript:history.back()" class="text-white absolute left-4 p-1 hover:bg-white/10 rounded-full transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-5 h-5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
                </svg>
            </a>
            
            <h1 class="text-white font-bold text-lg w-full text-center">Inbound Today</h1>
        </div>

        <div class="bg-white p-3 flex justify-end border-b border-gray-100">
            <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>" 
               class="border border-gray-300 text-gray-700 px-3 py-1.5 rounded-md text-sm font-bold flex items-center gap-1 hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Inbound
            </a>
        </div>
    </div>

    <div class="hidden md:flex flex-wrap items-center justify-between gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="teams">
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
                            <input type="text" name="search_text" placeholder="Search by name" class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" style="width: 300px; height: 37px; border-radius: 5px;" value="<?php echo $data['search'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=inbounding&action=list';">
                    </div>
                </div>
            </form>
        </div>

        <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>"
           style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; margin-right:10px;"
           class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add
        </a>
    </div>

    <div class="overflow-x-auto mt-4">
        <?php if (!empty($inbounding_data)): ?>
            <?php foreach ($inbounding_data as $index => $tc): ?>
                <?php
                // Calculate Percentage
                $filled = 0;
                $total = count($tc);
                foreach ($tc as $key => $t) {
                    if (isset($tc[$key]) && isFilled($tc[$key])) {
                        $filled++;
                    }
                }
                $percentage = ($filled / $total) * 100;
                ?>

                <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md border border-gray-200 hover:shadow-lg transition-shadow duration-200" style="margin: 0px 0px 10px 0px">
                    
                    <div class="flex md:hidden p-3 gap-3 items-center relative">
                        <div class="w-20 h-20 rounded-lg flex-shrink-0 bg-gray-50 border border-gray-100 overflow-hidden">
                            <img src="<?php echo base_url($tc['product_photo']); ?>" alt="Product" class="w-full h-full object-cover">
                        </div>
                        <div class="flex-1 min-w-0 flex flex-col justify-center space-y-1">
                            <p class="text-sm font-bold text-gray-800 truncate">
                                Category: <span class="font-normal"><?php echo ($tc['category_code']); ?></span>
                            </p>
                            <p class="text-xs text-gray-500">
                                Entry at: <span class="text-gray-700"><?php echo date('d M Y h:i A', strtotime($tc['gate_entry_date_time'])); ?></span>
                            </p>
                            <p class="text-xs font-bold text-gray-800">
                                Temp Code: <span class="text-gray-600 font-medium"><?php echo ($tc['temp_code']); ?></span>
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="<?php echo base_url('?page=inbounding&action=form1&id=' . $tc['id']); ?>" 
                               class="bg-[#d9822b] hover:bg-[#bf7326] text-white text-xs font-medium px-4 py-2 rounded-lg shadow-sm transition-colors">
                                Update
                            </a>
                        </div>
                    </div>

                    <div class="hidden md:flex items-start p-4 gap-4">
                        <div class="grid grid-cols-[max-content,1fr] gap-x-4 w-full">
                            <div class="flex flex-col gap-4">
                                <div class="flex items-start gap-4 ">
                                    <div class="w-24 h-24 rounded-md flex-shrink-0 flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <img src="<?php echo base_url($tc['product_photo']); ?>" alt="" class="max-w-full max-h-full object-contain cursor-pointer">
                                    </div>
                                    <div class="w-24 h-24 rounded-md flex-shrink-0 flex items-center justify-center bg-gray-50 overflow-hidden">
                                        <img src="<?php echo base_url($tc['invoice_image']); ?>" alt="" class="max-w-full max-h-full object-contain cursor-pointer">
                                    </div>
                                    
                                    <div class="pt-1 w-full max-w-xs">
                                        <p class="item-code">Temp Code: <?php echo ($tc['temp_code']); ?></p> 
                                        <p class="quantity">Category : <?php echo ($tc['category_code']); ?></p>
                                        <p class="quantity">Received_by : <?php print_r($tc['received_name']); ?></p>
                                        <p class="quantity">Gate Entry DateTime : <?php print_r($tc['gate_entry_date_time']); ?></p>
                                    </div>
                                    
                                    <div class="pt-1 w-full max-w-xs">
                                        <p class="item-code">Height: <?php echo ($tc['height']); ?></p>
                                        <p class="quantity">Width : <?php echo ($tc['width']); ?></p>
                                        <p class="quantity">Depth : <?php print_r($tc['depth']); ?></p>
                                        
                                        <?php
                                        $meterValue = $percentage / 100;
                                        echo '<meter id="disk_d" min="0" max="1" value="'.$meterValue.'">'.$percentage.'%</meter>';
                                        ?>
                                        <?php echo round($percentage, 2) . "%"; ?>
                                    </div>

                                    <div class="pt-1 w-full max-w-xs flex flex-col">
                                        <div class="mb-3 space-y-1">
                                            <p class="item-code font-medium text-gray-700">Weight: <?php echo ($tc['weight']); ?></p>
                                            <p class="quantity font-medium text-gray-700">Quantity : <?php echo ($tc['quantity_received']); ?></p>
                                            <p class="quantity font-medium text-gray-700">Vendor : <?php print_r($tc['vendor_name']); ?></p>
                                        </div>
                                        <div class="mt-1"> 
                                            <div class="hidden md:flex flex-row gap-2">
                                                <a href="<?php echo base_url('?page=inbounding&action=i_photos&id=' . $tc['id']); ?>" 
                                                   class="w-[120px] h-[40px] bg-[#d9822b] hover:bg-[#bf7326] text-white rounded-lg flex items-center justify-center gap-2 transition-colors text-[13px] font-medium no-underline">
                                                    Photos
                                                </a>
                                                <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . $tc['id']); ?>" 
                                                   class="w-[120px] h-[40px] bg-[#d9822b] hover:bg-[#bf7326] text-white rounded-lg flex items-center justify-center gap-2 transition-colors text-[13px] font-medium no-underline">
                                                    Process
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="p-4 text-center text-gray-500">No records found.</div>
        <?php endif; ?>
    </div>

    <?php         
        $page_no = $data["page_no"] ?? 1;
        $limit = $data["limit"] ?? 10;
        $total_records = $data["totalRecords"] ?? 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
    ?>
    <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>Page</span>
                    
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no <= 1 ? 'opacity-50 cursor-not-allowed' : '' ?>" >
                        <a class="page-link" <?php if(($page_no-1) >= 1) { ?> href="?page=inbounding&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?>  tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'opacity-50 cursor-not-allowed' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=inbounding&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=inbounding&acton=list&page_no=1&limit=' + this.value;">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= $opt ?>" <?= $opt == $limit ? 'selected' : '' ?>>
                                    <?= $opt ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<?php
function isFilled($value) {
    if ($value === null) return false;
    if ($value === "") return false;
    if ($value === 0 || $value === "0") return false;
    if ($value === "0000-00-00" || $value === "0000-00-00 00:00:00") return false;
    return true;
}
?>