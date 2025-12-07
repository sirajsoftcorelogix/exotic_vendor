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
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <!-- Filters -->
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
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=inbounding&action=list';">
                    </div>
                </div>
            </form>
        </div>
        <!-- Add User Button -->
        <a href="<?php echo base_url('?page=inbounding&action=form1'); ?>"
           style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;"
           class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add
        </a>

    </div>

    <!-- Listing -->
    <!-- <div class="bg-white rounded-xl shadow-md overflow-hidden"> -->
        <!-- <div class="p-6"> -->
            
            <div class="overflow-x-auto mt-4 ">
                    <?php if (!empty($inbounding_data)): ?>
                        <?php foreach ($inbounding_data as $index => $tc): ?>
                            <div class="max-w-7xl mx-auto bg-white rounded-lg shadow-md border border-gray-200" style="margin: 0px 0px 10px 0px">
                                <div class="flex items-start p-4 gap-4">
                                    <!-- Main two-column layout -->
                                    <div class="grid grid-cols-[max-content,1fr] gap-x-4 w-full">
                                        <!-- COLUMN 1 -->
                                        <div class="flex flex-col gap-4">
                                            <!-- Col 1, Row 1: Image and Title -->
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
                                                    $filled = 0;
                                                    $total = count($tc);
                                                    foreach ($tc as $key => $t) {
                                                        if (isset($tc[$key]) && isFilled($tc[$key])) {
                                                            $filled++;
                                                        }
                                                    }

                                                    $percentage = ($filled / $total) * 100;
                                                    $meterValue = $percentage / 100;
                                                    echo '<meter id="disk_d" min="0" max="1" value="'.$meterValue.'">'.$percentage.'%</meter>';
                                                    ?>
                                                    <?php echo round($percentage, 2) . "%"; ?>
                                                </div>
                                                <div class="pt-1 w-full max-w-xs">
                                                    <p class="item-code">Weight: <?php echo ($tc['weight']); ?></p>
                                                    <p class="quantity">Quantity : <?php echo ($tc['quantity_received']); ?></p>
                                                    <p class="quantity">Vendor : <?php print_r($tc['vendor_name']); ?></p>
                                                    <a href="<?php echo base_url('?page=inbounding&action=i_photos&id=' . $tc['id']); ?>" style="width:120px;height:40px;font-family:Inter;font-weight:500;font-size:13px;text-decoration:none;" class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded-lg hidden md:inline-flex items-center justify-center gap-2 transition-colors">Photos</a>
                                                    <a href="<?php echo base_url('?page=inbounding&action=form1&id=' . $tc['id']); ?>"
                                                       style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; text-decoration: none;"
                                                       class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded-lg inline-flex md:hidden items-center justify-center gap-2 transition-colors">
                                                        Update
                                                    </a>

                                                    <a href="<?php echo base_url('?page=inbounding&action=desktopform&id=' . $tc['id']); ?>"
                                                       style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; text-decoration: none;"
                                                       class="bg-[#d9822b] hover:bg-[#bf7326] text-white rounded-lg hidden md:inline-flex items-center justify-center gap-2 transition-colors">
                                                        Process
                                                    </a>

                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div>No record found.</div>
                    <?php endif; ?>
                    
            </div>
        <!-- </div> -->
    <!-- </div> -->

    <!-- Pagination -->
	<?php         
		$page_no = $data["page_no"];
		$limit = $data["limit"];
		$total_records = $data["totalRecords"] ?? 0;
        $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;
	?>
	<?php if ($total_pages > 1): ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>Page</span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no <= 1 ? 'disabled' : '' ?>" >
                        <a class="page-link" <?php if(($page_no-1) >= 1) { ?> href="?page=inbounding&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?>  tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=inbounding&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=inbounding&acton=list&page_no=1&limit=' + this.value;">
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
	<?php endif; ?>

</div>

<!-- Add Modal -->
<div id="popup-wrapper" class="hidden">
    <!-- Background Overlay -->
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div>

    <!-- Sliding Container -->
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">

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
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add Team</h2>
                    <div id="addVendorMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="addVendorForm">
                        <div class="pt-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Team Name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="addTeamName" id="addTeamName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Description</label>
                                <textarea class="w-full min-h-[120px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="addTeamDescription" id="addTeamDescription"></textarea>
                            </div>
                        </div>
                        <div class="pt-4 grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
							<div>
								<label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
								<select class="form-input w-full mt-1" required name="addStatus" id="addStatus">
									<option value="1">Active</option>
									<option value="0">Inactive </option>
								</select>
							</div>
                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Add Model Popup -->

<!-- Edit Modal -->
<div class="modal fade hidden" id="editVendorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <!-- Sliding Container -->
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn-edit" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit Team</h2>
                    <div id="editVendorMsg" style="margin-top:10px;"></div>
                    <form id="editUserForm">
                        <input type="hidden" id="editId" name="id" value="">
                        <div class="pt-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Team Name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="editTeamName" id="editTeamName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Description</label>
                                <textarea class="w-full min-h-[120px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="editTeamDescription" id="editTeamDescription"></textarea>
                            </div>
                        </div>
                        <div class="pt-4 grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
							<div>
								<label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
								<select class="form-input w-full mt-1" required name="editStatus" id="editStatus">
									<option value="1">Active</option>
									<option value="0">Inactive </option>
								</select>
							</div>
                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-vendor-btn-edit" class="action-btn cancel-btn">Back</button>
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
<!-- End Edit Model Popup -->

<!-- JavaScript to handle popup and form submission -->
<?php
 function isFilled($value) {
    if ($value === null) return false;
    if ($value === "") return false;
    if ($value === 0 || $value === "0") return false;
    if ($value === "0000-00-00" || $value === "0000-00-00 00:00:00") return false;

    return true;
}
?>