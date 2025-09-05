<div class="max-w-7xl mx-auto space-y-6" style="padding-right: 15px;">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <!-- Filters -->
            <form method="get" action="">
                <input type="hidden" name="page" value="users">
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
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="role_filter" id="role_filter">
                            <option value="" selected>All Roles</option>
                            <option value="admin" <?php echo ($data['role_filter']=="admin") ? "selected" : ""?>>Admin</option>
                            <option value="onboarding_executive" <?php echo ($data['role_filter']=="onboarding_executive") ? "selected" : ""?>>Onboarding Executive</option>
                        </select>
                    </div>
                    <div class="relative">
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="status_filter" id="status_filter">
                            <option value="" selected>All Status</option>
                            <option value="active" <?php echo ($data['status_filter'] == "active") ? "selected" : ""?>>Active</option>
                            <option value="inactive" <?php echo ($data['status_filter'] == "inactive") ? "selected" : ""?>>Inactive</option>
                        </select>
                    </div>
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=users&action=list';">
                    </div>
                </div>
            </form>
        </div>
        <!-- Add User Button -->
        <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]" id="open-vendor-popup-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>Add User</button>
    </div>

    <!-- Users Table Container -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Full Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Email Address</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Phone Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Status</th>
                        <!-- <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Last Login</th> -->
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
					<?php
						if (!empty($data['users'])) {
						    $i = 0;
						    foreach($data['users'] as $item):
					?>
                                <tr class="table-content-text">
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $item["id"] ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $item['name']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $item['email']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap"><?= $item['phone']; ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span style="width: 145px; height: 25px; padding:15px 0px 15px 0px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md bg-black text-white"><?= ($item['role'] != "") ? ucwords(str_replace("_", " ", $item['role'])) : '' ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center gap-2">
                                            <?php if ($item['is_active'] == 1): ?>
                                                <span class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold text-white text-[13px]"
                                                    style="width: 75px; height: 25px; border-radius: 5px; background: rgba(208, 103, 6, 1);">
                                                    Active
                                                </span>
                                            <?php else: ?>
                                                <span class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-full bg-gray-200 text-gray-800"
                                                    style="width: 75px; height: 25px; border-radius: 5px;">
                                                    Inactive
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <!-- <td class="px-6 py-4 whitespace-nowrap">23-08-2025 13:10</td> ?page=users&action=updateUser&id=<?= $item['id']; ?> data-toggle="modal" data-target="#editModal" data-id="<?php echo $item['id'];?>"-->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <div class="flex items-center space-x-4">
                                            <a href="#" onclick="openEditModal(<?php echo $item['id']; ?>)" class="text-gray-400 hover:text-black" title="Edit User">
                                                <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M12.0465 8.20171C10.6474 9.47037 9.33829 11.0991 7.90075 12.3041C7.56581 12.5845 7.25417 12.7388 6.8125 12.7978C6.09762 12.8939 5.09165 12.9659 4.36744 12.9883C3.50508 13.0154 2.73585 12.5712 2.75448 11.6359C2.76884 10.909 2.86781 9.93098 2.95164 9.19835C2.992 8.84595 3.04983 8.53545 3.24582 8.2299L11.1585 0.415632C11.9227 -0.178697 12.8029 -0.120026 13.5279 0.491828C14.0922 0.968052 15.0966 1.93688 15.5631 2.49426C16.1484 3.19335 16.1422 4.07837 15.5631 4.77785C14.5839 5.96041 13.1029 7.05649 12.0461 8.20209L12.0465 8.20171ZM12.2572 1.03396C12.1435 1.04272 11.9914 1.11244 11.8971 1.17873C11.5144 1.44732 11.1364 2.00355 10.7525 2.30224L13.6765 5.13787C14.091 4.59726 15.3764 3.97665 14.7694 3.19678C14.2393 2.51559 13.2993 1.87897 12.7319 1.19664C12.6112 1.0972 12.416 1.02139 12.2568 1.03396H12.2572ZM3.89279 11.8744C3.9382 11.9216 4.10004 11.9635 4.17145 11.962C4.89643 11.9464 5.93228 11.858 6.65687 11.7692C6.78689 11.7532 6.92699 11.7174 7.03916 11.6492L12.8693 5.94022L9.99496 3.04591L4.13652 8.79985C4.00651 8.99529 3.98516 9.58505 3.96032 9.84602C3.9153 10.323 3.85631 10.8968 3.84195 11.368C3.83846 11.4842 3.82022 11.7989 3.8924 11.8744H3.89279Z" fill="black"/>
                                                    <path d="M2.04958 2.33219C3.16732 2.20875 4.46941 2.40038 5.60695 2.32418C6.18289 2.44724 6.14176 3.26711 5.56736 3.34711C4.59787 3.48198 3.31946 3.26368 2.30922 3.34902C1.6281 3.40655 1.1127 3.92468 1.04788 4.5872V13.6953C1.10687 14.4325 1.64634 14.914 2.38684 14.9716H11.5488C13.652 14.8081 12.6526 11.8803 12.8886 10.5339C13.0523 9.99635 13.7703 9.99864 13.9326 10.5339C13.8247 12.6091 14.6599 15.6337 11.7045 16.0006H2.2316C1.06845 15.9168 0.137389 15.0177 0 13.8862L0.00620967 4.36433C0.140494 3.35931 1.00791 2.44724 2.04997 2.33219H2.04958Z" fill="black"/>
                                                </svg>
                                            </a>
                                            <a href="#" class="delete-btn" data-id="<?php echo $item['id']; ?>">
                                                <svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                    <path d="M10.2198 2.46658L13.5732 2.48141C14.142 2.57241 14.1239 3.51406 13.6281 3.62287C13.4664 3.65814 13.1578 3.57143 13.1049 3.74156L11.7041 14.1792C11.4162 15.0615 10.6479 15.653 9.72717 15.7357C8.33059 15.861 5.74347 15.8501 4.33736 15.739C3.36304 15.6622 2.57373 15.0773 2.28587 14.1287L0.898821 3.74156L0.80254 3.64496C-0.0761549 3.87476 -0.309794 2.57241 0.488063 2.47482C0.982945 2.41415 3.62001 2.56813 3.78366 2.4669C4.1494 1.59977 4.1402 0.663395 5.11879 0.234443C5.84468 -0.083726 8.27177 -0.0863637 8.96973 0.277635C9.90232 0.763627 9.85106 1.60867 10.2194 2.4669L10.2198 2.46658ZM8.92636 2.47746C8.78341 2.05774 8.80214 1.41876 8.28689 1.2849C7.98818 1.20742 5.94721 1.21467 5.67216 1.30402C5.19601 1.45898 5.21934 2.07059 5.07738 2.47746H8.92636ZM11.9413 3.63605H2.06242L3.47148 13.9045C3.60687 14.2458 3.90985 14.4762 4.27558 14.5135C6.0057 14.4096 7.8919 14.6516 9.60263 14.5161C10.2805 14.4624 10.5135 14.1409 10.642 13.4993L11.9417 3.63605H11.9413Z" fill="#DF0000"/>
                                                    <path d="M5.82431 5.8472C5.9058 5.92897 5.96857 6.05821 5.9781 6.17592C5.81445 8.00251 6.18709 10.1898 5.97678 11.9729C5.89627 12.6554 4.98209 12.6976 4.82436 12.0322L4.81812 6.17625C4.86741 5.69454 5.5003 5.5231 5.82464 5.84753L5.82431 5.8472Z" fill="#DF0000"/>
                                                    <path d="M9.03183 5.8472C9.11332 5.92897 9.17609 6.05821 9.18562 6.17592C9.02197 8.00251 9.39461 10.1898 9.1843 11.9729C9.10379 12.6554 8.18961 12.6976 8.03188 12.0322L8.02563 6.17625C8.07493 5.69454 8.70782 5.5231 9.03216 5.84753L9.03183 5.8472Z" fill="#DF0000"/>
                                                </svg>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
					<?php
                            $i++;
                            endforeach; ?>
					<?php } else { ?>
						<tr><td colspan="8" class="text-center">No Users found.</td></tr>
					<?php } ?>
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
                        <a class="page-link" href="?page=users&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=users&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=users&acton=list&page_no=1&limit=' + this.value;">
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

<!-- Right Side Popup Wrapper -->
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
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit User</h2>
                    <div id="addUserMsg" style="margin-top:10px;"></div>
                    <form id="addUserForm">
                        <input type="hidden" name="page" value="users">
                        <input type="hidden" name="action" value="addUser">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="name" class="text-sm font-medium text-gray-700">Name:</label>
                                <input type="text" id="name" name="name" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="email" class="text-sm font-medium text-gray-700">Email:</label>
                                <input type="email" id="email" name="email" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="phone" class="text-sm font-medium text-gray-700">Phone:</label>
                                <input type="number" id="phone" name="phone" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="password" class="text-sm font-medium text-gray-700">Password:</label>
                                <input type="password" id="password" name="password" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="role" class="text-sm font-medium text-gray-700">Role:</label>
                                <select id="role" name="role" class="form-input w-full bg-white mt-1" required>
                                    <option value="admin">Admin</option>
                                    <option value="onboarding_executive">Onboarding Executive</option>
                                </select>
                            </div>

                            <div>
                                <label for="is_active" class="text-sm font-medium text-gray-700">Active:</label>
                                <select id="is_active" name="is_active" class="form-input w-full bg-white mt-1" required>
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
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


<!-- Edit User Modal -->
<div class="modal fade hidden" id="editUserModal" tabindex="-1" aria-hidden="true">
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
                    <div id="editUserMsg" style="margin-top:10px;"></div>
                    <form id="editUserForm">
                        <input type="hidden" id="editUserId" name="id" value="">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="name" class="text-sm font-medium text-gray-700">Name:</label>
                                <input type="text" id="editName" name="name" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="email" class="text-sm font-medium text-gray-700">Email:</label>
                                <input type="email" id="editEmail" name="email" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="phone" class="text-sm font-medium text-gray-700">Phone:</label>
                                <input type="number" id="EditPhone" name="phone" class="form-input w-full mt-1" required>
                            </div>

                            <div>
                                <label for="password" class="text-sm font-medium text-gray-700">Password:</label>
                                <input type="password" id="editPassword" name="password" class="form-input w-full mt-1">
                            </div>

                            <div>
                                <label for="role" class="text-sm font-medium text-gray-700">Role:</label>
                                <select id="editRole" name="role" class="form-input w-full bg-white mt-1">
                                    <option value="admin">Admin</option>
                                    <option value="onboarding_executive">Onboarding Executive</option>
                                </select>
                            </div>

                            <div>
                                <label for="is_active" class="text-sm font-medium text-gray-700">Active:</label>
                                <select id="editIs_active" name="is_active" class="form-input w-full bg-white mt-1">
                                    <option value="1">Yes</option>
                                    <option value="0">No</option>
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
<!-- End Model Popup -->


<!-- JavaScript to handle popup and form submission -->
<script>
    
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

    document.getElementById('addUserForm').onsubmit = function(e) {
        e.preventDefault();
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        var msgDiv = document.getElementById('addUserMsg');
        msgDiv.textContent = '';
        fetch('?page=users&action=addUser', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById("addUserMsg");
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                    ✅ ${data.message}
                </div>`;
            } else {
                msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                    ❌ ${data.message}
                </div>`;                    
            }
            setTimeout(() => {
                location.reload();
            }, 1000); // refresh after 1 sec
        });
    };

    document.addEventListener("DOMContentLoaded", () => {
        const deleteButtons = document.querySelectorAll(".delete-btn");

        deleteButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const id = btn.getAttribute("data-id");

                if (!confirm("Are you sure you want to delete this record?")) return;

                fetch("?page=users&action=deleteUser", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + id
                })
                .then(res => res.json())
                .then(data => {
                    const msgBox = document.getElementById("addUserMsg");

                    if (data.success) {

                        msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                            ✅ ${data.message}
                        </div>`;
                    } else {
                        msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                            ❌ ${data.message}
                        </div>`;                   
                    }
                    setTimeout(() => {
                        location.reload();
                    }, 1000); // refresh after 1 sec
                })
                .catch(err => {
                    console.error("AJAX Error:", err);
                });
            });
        });
    });

    // Edit User Modal Logic    
    const popupWrapperEdit = document.getElementById('editUserModal');
    const modalSliderEdit = document.getElementById('modal-slider-edit');
    const cancelVendorBtnEdit = document.getElementById('cancel-vendor-btn-edit');
    const closeVendorPopupBtnEdit = document.getElementById('close-vendor-popup-btn-edit');

    function openEditModal(id) {
        fetch("?page=users&action=userDetails&id=" + id)
        .then(res => res.json())
        .then(user => {
            if (user.status === "error") {
                alert(user.message);
                return;
            }
            document.getElementById("editUserId").value   = user.id;
            document.getElementById("editName").value = user.name;
            document.getElementById("editEmail").value= user.email;
            document.getElementById("EditPhone").value= user.phone;

            document.getElementById("editRole").value= user.role;
            document.getElementById("editIs_active").value= user.is_active;

            
            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => {
                modalSliderEdit.classList.remove('translate-x-full');
            }, 10);
            //document.getElementById('editUserModal').show();
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
        fetch('?page=users&action=addUser', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('editUserMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                                    ✅ ${data.message}
                </div>`;
            } else {
                msgBox.innerHTML = `<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">
                    ❌ ${data.message}
                </div>`;
            }
            setTimeout(() => {
                window.location.href = '?page=users&action=list';
            }, 1000); // redirect after 1 sec
        });
    };
</script>