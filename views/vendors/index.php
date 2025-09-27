<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <!-- Filters -->
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="vendors">
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
                            <input type="text" name="search_text" placeholder="Search by name, email or phone" class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" style="width: 300px; height: 37px; border-radius: 5px;" value="<?php echo $data['search'] ?? '' ?>">
                        </div>
                    </div>
                    <div class="relative">
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="status_filter" id="status_filter">
                            <option value="" selected>All Status</option>
                            <option value="active" <?php echo ($data['status_filter'] == "active") ? "selected" : ""?>>Active</option>
                            <option value="inactive" <?php echo ($data['status_filter'] == "inactive") ? "selected" : ""?>>Inactive</option>
                            <option value="blacklisted" <?php echo ($data['status_filter'] == "blacklisted") ? "selected" : ""?>>Blacklisted</option>
                        </select>
                    </div>
                    
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=vendors&action=list';">
                    </div>
                </div>
            </form>
        </div>
        <!-- Add User Button -->
        <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]" id="open-vendor-popup-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>Add Vendor</button>
    </div>

    <!-- Vendor Listing -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <!-- <div id="deleteMsgBox" style="margin-top: 10px; margin: botton 10px;" class="text-sm font-bold"></div> -->
            <!-- Success Modal -->
            <div id="deleteMsgBox" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
                <div class="bg-white rounded-lg shadow-lg w-[400px] p-8 text-center">
                    <h2 id="modalTitle" class="text-xl font-bold text-green-600 mb-4">Alert Box</h2>
                    <p id="showMessage" class="text-gray-700"></p>

                    <div class="mt-6">
                    <button onclick="closeDeleteModal()" 
                            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        OK
                    </button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">#</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Vendor Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Contact Person</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Phone</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">City</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">State</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($vendors)): ?>
                        <?php foreach ($vendors as $index => $vendor): ?>
                            <tr class="table-content-text">
                                <td class="px-6 py-4 whitespace-nowrap"><?= $index + 1 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['vendor_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['contact_name']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['vendor_phone']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['vendor_email']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['city']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($vendor['state']) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(ucfirst($vendor['is_active'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Three-dot menu container -->
                                    <div class="menu-wrapper">
                                        <button class="menu-button" onclick="toggleMenu(this)">
                                            &#x22EE; <!-- Vertical ellipsis -->
                                        </button>
                                        <ul class="menu-popup">
                                            <li onclick="openEditModal(<?= htmlspecialchars($vendor['id']) ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <li onclick="openBankDtlsModal(<?= htmlspecialchars($vendor['id']) ?>)"><i class="fa-solid fa-building-columns"></i> Bank Details</li>
                                            <li class="delete-btn" data-id="<?php echo $vendor['id']; ?>"><i class="fa-solid fa-trash"></i> Delete</li>
                                            <li style="color: lightgray;"><i class="fa-solid fa-cart-shopping"></i> Purchase Order</li>
                                            <li style="color: lightgray;"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</li>
                                            <li style="color: lightgray;"><i class="fa-solid fa-indian-rupee-sign"></i> Payments</li>
                                        </ul>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-gray-500">No vendors found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
                        <a class="page-link" <?php if(($page_no-1) >= 1) { ?> href="?page=vendors&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?>  tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=vendors&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=vendors&acton=list&page_no=1&limit=' + this.value;">
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

<!-- Add Vendor Modal -->
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
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit Vendor</h2>
                    <div id="addVendorMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="addVendorForm">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="addVendor">

                        <!-- Basic Information -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Basic Information</h3>
                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="addVendorName" id="addVendorName" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Contact Person <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="addContactPerson" id="addContactPerson" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Phone <span class="text-red-500">*</span></label>
                                    <input type="number" class="form-input w-full mt-1" required name="addPhone" id="addPhone" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" class="form-input w-full mt-1" name="addEmail" id="addEmail" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Alternate Phone (optional)</label>
                                    <input type="number" class="form-input w-full mt-1" name="addAltPhone" id="addAltPhone" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" required name="addStatus" id="addStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive </option>
                                        <option value="blacklisted">Blacklisted</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Category</label>
                                    <select class="form-input w-full mt-1 h-32" multiple name="addVendorCategory" id="addVendorCategory">
                                        <option value="">Select Categories</option>
                                        <?php foreach(getVendorCategory() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Address</h3>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="addAddress" id="addAddress" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">City <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="addCity" id="addCity" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">State <span class="text-red-500">*</span></label>
                                    <span id="addStateBlock">
                                        <select class="form-input w-full mt-1" required name="addState" id="addState">
                                            <?php foreach($stateList as $item): ?>
                                                <option value="<?php echo $item["name"];?>"><?php echo $item["name"];?></option>
                                            <?php endforeach?>
                                        </select>
                                    </span>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" required name="addCountry" id="addCountry" onchange="fetchStates(this.value, 'AddForm');">
                                        <?php foreach($countryList as $item): ?>
                                            <option value="<?php echo $item["name"];?>" <?php if($item["name"]=="India") { echo "selected"; }?>><?php echo $item["name"];?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Postal Code <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="addPostalCode" id="addPostalCode" />
                                </div>
                            </div>
                        </div>

                        <!-- Tax Information -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Tax Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">GST Number <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="addGstNumber" id="addGstNumber" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">PAN Number</label>
                                    <input type="text" class="form-input w-full mt-1" name="addPanNumber" id="addPanNumber" />
                                </div>
                            </div>
                        </div>

                        <!-- Ratings & Notes -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Ratings & Notes</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">
                                        Rating <span class="text-red-500">*</span>
                                    </label>
                                    <select class="form-input w-full mt-1" required name="addRating" id="addRating">
                                        <option>5 Star</option>
                                        <option>4 Star</option>
                                        <option>3 Star</option>
                                        <option>2 Star</option>
                                        <option>1 Star</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Notes</label>
                                <textarea class="w-full min-h-[120px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="addNotes" id="addNotes"></textarea>
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

<!-- Edit Vendor Modal -->
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
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit User</h2>
                    <div id="editVendorMsg" style="margin-top:10px;"></div>
                    <form id="editUserForm">
                        <input type="hidden" id="editVendorId" name="id" value="">
                        <input type="text" id="editPreviousState" name="editPreviousState" value="">
                        <!-- Basic Information -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Basic Information</h3>
                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Vendor Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="editVendorName" id="editVendorName" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Contact Person <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="editContactPerson" id="editContactPerson" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Phone <span class="text-red-500">*</span></label>
                                    <input type="number" class="form-input w-full mt-1" required name="editPhone" id="editPhone" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Email</label>
                                    <input type="email" class="form-input w-full mt-1" name="editEmail" id="editEmail" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Alternate Phone (optional)</label>
                                    <input type="number" class="form-input w-full mt-1" name="editAltPhone" id="editAltPhone" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" required name="editStatus" id="editStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive </option>
                                        <option value="blacklisted">Blacklisted</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Category</label>
                                    <select class="form-input w-full mt-1 h-32" multiple name="addVendorCategory" id="addVendorCategory">
                                        <option value="">Select Categories</option>
                                        <?php foreach(getVendorCategory() as $key => $value): ?>
                                            <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Address -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Address</h3>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="editAddress" id="editAddress" />
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">City <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="editCity" id="editCity" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">State <span class="text-red-500">*</span></label>
                                    <span id="editStateBlock">
                                        <select class="form-input w-full mt-1" required name="editState" id="editState">
                                            <?php foreach($stateList as $item): ?>
                                                <option value="<?php echo $item["name"];?>"><?php echo $item["name"];?></option>
                                            <?php endforeach?>
                                        </select>
                                    </span>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Country <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" required name="editCountry" id="editCountry" onchange="fetchStates(this.value, 'EditForm');">
                                        <?php foreach($countryList as $item): ?>
                                            <option value="<?php echo $item["name"];?>" <?php if($item["name"]=="India") { echo "selected"; }?>><?php echo $item["name"];?></option>
                                        <?php endforeach?>
                                    </select>
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Postal Code <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="editPostalCode" id="editPostalCode" />
                                </div>
                            </div>
                        </div>

                        <!-- Tax Information -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Tax Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">GST Number <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="editGstNumber" id="editGstNumber" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">PAN Number</label>
                                    <input type="text" class="form-input w-full mt-1" name="editPanNumber" id="editPanNumber" />
                                </div>
                            </div>
                        </div>

                        <!-- Ratings & Notes -->
                        <div class="pt-4">
                            <h3 class="text-sm font-bold text-gray-800 mb-2">Ratings & Notes</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">
                                        Rating <span class="text-red-500">*</span>
                                    </label>
                                    <select class="form-input w-full mt-1" required name="editRating" id="editRating">
                                        <option>5 Star</option>
                                        <option>4 Star</option>
                                        <option>3 Star</option>
                                        <option>2 Star</option>
                                        <option>1 Star</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Notes</label>
                                <textarea class="w-full min-h-[120px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" name="editNotes" id="editNotes"></textarea>
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


<!-- Bank Detail Modal -->
<div class="modal fade hidden" id="bankDetailModal" tabindex="-1" aria-hidden="true">
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
        <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit Bank Details</h2>
                    <div id="bankDetailMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="bankDetailForm">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="addBankDetails">
                        <input type="hidden" name="vendor_id" id="vendor_id" value="">

                        <!-- Basic Information -->
                        <div class="pt-4">

                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Account Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="account_name" id="account_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Account Number <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="account_number" id="account_number" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Bank Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="bank_name" id="bank_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Branch Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" name="branch_name" id="branch_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700"> IFSC Code <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" name="ifsc_code" id="ifsc_code" />
                                </div>
                                <?php /* ?><div>
                                    <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" name="bdStatus" id="bdStatus">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive </option>
                                    </select>
                                </div><? */?>
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
<!-- End Edit Model Popup -->

<!-- JavaScript to handle popup and form submission -->
<script>
    
    // Toggle menu visibility
    function toggleMenu(button) {
        const popup = button.nextElementSibling;
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';

        // Close other open menus
        document.querySelectorAll('.menu-popup').forEach(menu => {
            if (menu !== popup) menu.style.display = 'none';
        });
    }

    // Three dot menu functionality
    document.addEventListener('DOMContentLoaded', () => {
        const menuButtons = document.querySelectorAll('.menu-button');
        const body = document.body;
        window.currentOpenMenu = null;
        const menuMargin = 8; // Margin from button in pixels

        // Function to close all menus
        window.closeAllMenus = function () {
            if (window.currentOpenMenu) {
                window.currentOpenMenu.classList.add('hidden');
                window.currentOpenMenu.classList.remove('active');
                window.currentOpenMenu.removeAttribute('style');
                window.currentOpenMenu = null;
            }
        };

        // Event listener to close menus when clicking anywhere else on the document
        document.addEventListener('click', function(e) {
            if (currentOpenMenu && !currentOpenMenu.contains(e.target)) {
                closeAllMenus();
            }
        });

        // Event listeners for each menu button
        menuButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();

                const dropdown = button.nextElementSibling;
                if (!dropdown) {
                    return; // Prevents the TypeError if dropdown is not found
                }

                const isActive = dropdown.classList.contains('active');

                if (currentOpenMenu && currentOpenMenu !== dropdown) {
                    closeAllMenus();
                }

                if (!isActive) {
                    // Temporarily show the dropdown to get its dimensions
                    dropdown.classList.remove('hidden');

                    const buttonRect = button.getBoundingClientRect();
                    const dropdownWidth = dropdown.offsetWidth;
                    const dropdownHeight = dropdown.offsetHeight;
                    const viewportHeight = window.innerHeight;
                    const viewportWidth = window.innerWidth;

                    // Reset position styles
                    dropdown.style.position = 'fixed';
                    dropdown.style.top = '';
                    dropdown.style.left = '';
                    dropdown.style.bottom = '';
                    dropdown.style.right = '';

                    // Vertical positioning logic
                    // If there is enough space to open downwards
                    if (buttonRect.bottom + dropdownHeight + menuMargin < viewportHeight) {
                        dropdown.style.top = `${buttonRect.bottom + menuMargin}px`;
                    } else {
                        // Not enough space, open upwards
                        dropdown.style.top = `${buttonRect.top - dropdownHeight - menuMargin}px`;
                    }

                    // Horizontal positioning logic
                    // If there is enough space to open on the right
                    if (buttonRect.left + dropdownWidth < viewportWidth) {
                        dropdown.style.left = `${buttonRect.left}px`;
                    } else {
                        // Not enough space, open leftwards
                        dropdown.style.left = `${buttonRect.left - dropdownWidth + buttonRect.width}px`;
                    }

                    // Show the menu and set it as the current active one
                    dropdown.classList.add('active');
                    currentOpenMenu = dropdown;

                } else {
                    // Close the menu if it's already active
                    closeAllMenus();
                }
            });
        });
    });

    function fetchStates(countryId, formType) {
        if(countryId === "") return;
        if(countryId !== "India") {
            if(formType === 'AddForm') {
                document.getElementById('addStateBlock').innerHTML = '<input type="text" class="form-input w-full mt-1" required name="addState" id="addState" />';
            } else {
                document.getElementById('editStateBlock').innerHTML = '<input type="text" class="form-input w-full mt-1" required name="editState" id="editState" />';
            }
            return;
        } else {
            // Fetch states from the server
            fetch('?page=vendors&action=getStates')
            .then(response => response.json())
            .then(data => {
                if (formType === 'AddForm') {
                    // Create a new select element
                    let stateSelect = document.createElement('select');

                    // add attributes
                    stateSelect.id = "addState";
                    stateSelect.name = "addState";
                    stateSelect.className = "form-input w-full mt-1"; // Tailwind or custom CSS
                    stateSelect.required = true;

                    // Populate the select element with options
                    let states = Array.isArray(data) ? data : [data]; 
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.name;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                    document.getElementById('addStateBlock').innerHTML = stateSelect.outerHTML;
                } else {
                    // Create a new select element
                    let stateSelect = document.createElement('select');

                    // add attributes
                    stateSelect.id = "editState";
                    stateSelect.name = "editState";
                    stateSelect.className = "form-input w-full mt-1"; // Tailwind or custom CSS
                    stateSelect.required = true;

                    // Populate the select element with options
                    let states = Array.isArray(data) ? data : [data]; 
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.name;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });
                    document.getElementById('editStateBlock').innerHTML = stateSelect.outerHTML;
                    document.getElementById("editState").value = document.getElementById("editPreviousState").value;
                }
                return;
            });
        }
    }

    const openVendorPopupBtn = document.getElementById('open-vendor-popup-btn');
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

    openVendorPopupBtn.addEventListener('click', openVendorPopup);
    cancelVendorBtn.addEventListener('click', closeVendorPopup);
    closeVendorPopupBtn.addEventListener('click', closeVendorPopup);

    document.getElementById('addVendorForm').onsubmit = function(e) {
        e.preventDefault();
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=addPost', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById("addVendorMsg");
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
                setTimeout(() => {
                    location.reload();
                }, 1500); // refresh after 1 sec
            } else {
                msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                    ❌ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    };

    let successModalTimer;
    // Delete Vendor
    document.addEventListener("DOMContentLoaded", () => {
        const deleteButtons = document.querySelectorAll(".delete-btn");
        
        deleteButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const id = btn.getAttribute("data-id");
                window.closeAllMenus();
                if (!confirm("Are you sure you want to delete this record?")) return;

                fetch("?page=vendors&action=delete", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + id
                })
                .then(res => res.json())
                .then(data => {
                    const title = document.getElementById("modalTitle");
                    var type = "error";
                    title.innerText = "Error ⚠️";
                    title.className = "text-2xl font-bold text-red-600 mb-4";
                    if(data.success) {
                        title.innerText = "Success 🎉";
                        title.className = "text-2xl font-bold text-green-600 mb-4";
                    }

                    document.getElementById("showMessage").innerText = data.message;
                    const modal = document.getElementById("deleteMsgBox");
                    modal.classList.remove("hidden");

                    // Auto-close after 3 seconds
                    clearTimeout(successModalTimer);
                    successModalTimer = setTimeout(() => {
                        closeDeleteModal();
                    }, 1500);
                    
                })
                .catch(err => {
                    console.error("AJAX Error:", err);
                });
            });
        });
    });

    function closeDeleteModal() {
        document.getElementById("deleteMsgBox").classList.add("hidden");
        clearTimeout(successModalTimer);
        window.location.reload();
    }

    // Edit User Modal Logic    
    const popupWrapperEdit = document.getElementById('editVendorModal');
    const modalSliderEdit = document.getElementById('modal-slider-edit');
    const cancelVendorBtnEdit = document.getElementById('cancel-vendor-btn-edit');
    const closeVendorPopupBtnEdit = document.getElementById('close-vendor-popup-btn-edit');

    function openEditModal(id) {
        closeAllMenus();
        fetch("?page=vendors&action=vendorDetails&id=" + id)
        .then(res => res.json())
        .then(vendor => {
            if (vendor.status === "error") {
                alert(vendor.message);
                return;
            }
            // Populate form fields with vendor data
            document.getElementById("editVendorId").value   = vendor.id;
            document.getElementById("editVendorName").value = vendor.vendor_name;
            document.getElementById("editContactPerson").value = vendor.contact_name;
            document.getElementById("editEmail").value = vendor.vendor_email;
            document.getElementById("editPhone").value = vendor.vendor_phone;
            document.getElementById("editAltPhone").value = vendor.alt_phone;
            document.getElementById("editGstNumber").value = vendor.gst_number;
            document.getElementById("editPanNumber").value = vendor.pan_number;
            document.getElementById("editAddress").value = vendor.address;
            document.getElementById("editCity").value = vendor.city;
            document.getElementById("editCountry").value = vendor.country;

            if(vendor.country !== "India") {
                document.getElementById("editStateBlock").innerHTML = '<input type="text" class="form-input w-full mt-1" required name="editState" id="editState" value="' + vendor.state + '" />';
                document.getElementById("editPreviousState").value = vendor.state;
            } else {
                // Fetch states from the server
                fetch('?page=vendors&action=getStates')
                .then(response => response.json())
                .then(data => {
                    // Create a new select element
                    let stateSelect = document.createElement('select');

                    // add attributes
                    stateSelect.id = "editState";
                    stateSelect.name = "editState";
                    stateSelect.className = "form-input w-full mt-1"; // Tailwind or custom CSS
                    stateSelect.required = true;

                    // Populate the select element with options
                    let states = Array.isArray(data) ? data : [data]; 
                    states.forEach(state => {
                        const option = document.createElement('option');
                        option.value = state.name;
                        option.textContent = state.name;
                        stateSelect.appendChild(option);
                    });

                    document.getElementById('editStateBlock').innerHTML = stateSelect.outerHTML;
                    document.getElementById("editState").value = vendor.state;
                    document.getElementById("editPreviousState").value = vendor.state;
                    return;
                });
            }

            document.getElementById("editPostalCode").value = vendor.postal_code;
            document.getElementById("editRating").value = vendor.rating;
            document.getElementById("editNotes").value = vendor.notes;
            document.getElementById("editStatus").value = vendor.is_active;

            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => {
                modalSliderEdit.classList.remove('translate-x-full');
            }, 10);
        });
    }

    function closeVendorPopupEdit() {
        modalSliderEdit.classList.add('translate-x-full');
    }

    closeVendorPopupBtnEdit.addEventListener('click', closeVendorPopupEdit);
    cancelVendorBtnEdit.addEventListener('click', closeVendorPopupEdit);

    document.getElementById('editUserForm').onsubmit = function(e) {
        e.preventDefault();
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=addPost', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('editVendorMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
                setTimeout(() => {
                    window.location.href = '?page=vendors&action=list';
                }, 1000); // redirect after 1 sec
            } else {
                msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                    ❌ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    };

    // Bank Details Modal Logic    
    const popupWrapperBD = document.getElementById('bankDetailModal');
    const modalSliderBD = document.getElementById('modal-slider-bd');
    const cancelBankDetailBtn = document.getElementById('cancel-bd-btn');
    const closeBankDetailsPopupBtn = document.getElementById('close-bd-popup-btn-bd');

    function openBankDtlsModal(id) {
        console.log("Id: " + id);
        closeAllMenus();
        fetch("?page=vendors&action=getBankDetails&id=" + id)
        .then(res => res.json())
        .then(bankdtls => {
            if (bankdtls.status === "error") {
                alert(bankdtls.message);
                return;
            }
            document.getElementById("vendor_id").value = id;
            
            if (bankdtls && bankdtls.account_name) {
                console.log(bankdtls);
                // Populate form fields with bank details data
                document.getElementById("account_name").value = bankdtls.account_name;
                document.getElementById("account_number").value = bankdtls.account_number;
                document.getElementById("ifsc_code").value = bankdtls.ifsc_code;
                document.getElementById("bank_name").value = bankdtls.bank_name;
                document.getElementById("branch_name").value = bankdtls.branch_name;
            } else {
                document.getElementById("account_name").value = "";
                document.getElementById("account_number").value = "";
                document.getElementById("ifsc_code").value = "";
                document.getElementById("bank_name").value = "";
                document.getElementById("branch_name").value = "";
            }

            popupWrapperBD.classList.remove('hidden');
            popupWrapperBD.classList.remove('active');
            setTimeout(() => {
                modalSliderBD.classList.remove('translate-x-full');
            }, 10);
        });
    }
    
    function closeBankDetailPopup() {
        modalSliderBD.classList.add('translate-x-full');
    }
    
    closeBankDetailsPopupBtn.addEventListener('click', closeBankDetailPopup);
    cancelBankDetailBtn.addEventListener('click', closeBankDetailPopup);
    

    document.getElementById('bankDetailForm').onsubmit = function(e) {
        e.preventDefault();
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=bankDetails', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('bankDetailMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
                setTimeout(() => {
                    window.location.href = '?page=vendors&action=list';
                }, 1000); // redirect after 1 sec
            } else {
                msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                    ❌ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
            }
        });
    };
</script>