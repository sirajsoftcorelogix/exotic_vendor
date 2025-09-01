<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow">
            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="flex items-center gap-2">
                    <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M14 1H1L5.5 6.5V12L8.5 14V6.5L14 1Z" stroke="#797A7C" stroke-width="2" stroke-linejoin="round"/>
                    </svg>
                    <span class="text-gray-600 font-medium">Filters:</span>
                </div>
                <div class="relative">
                    <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select bg-gray-50 border border-gray-300 text-gray-900 text-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option selected>All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="executive">Executive</option>
                        <option value="sales">Sales</option>
                        <option value="data-entry">Data Entry</option>
                    </select>
                </div>
                <div class="relative">
                    <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select bg-gray-50 border border-gray-300 text-gray-900 text-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option selected>All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <!-- Sort -->
            <div class="flex flex-wrap items-center gap-4">
                <div class="relative flex items-center gap-2">
                    <svg width="28" height="22" viewBox="0 0 28 22" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M7 21C7 21.5523 7.44772 22 8 22C8.55228 22 9 21.5523 9 21L8 21L7 21ZM8.70711 0.292892C8.31658 -0.0976314 7.68342 -0.0976315 7.29289 0.292892L0.928933 6.65685C0.538408 7.04738 0.538408 7.68054 0.928933 8.07107C1.31946 8.46159 1.95262 8.46159 2.34315 8.07107L8 2.41421L13.6569 8.07107C14.0474 8.46159 14.6805 8.46159 15.0711 8.07107C15.4616 7.68054 15.4616 7.04738 15.0711 6.65685L8.70711 0.292892ZM8 21L9 21L9 1L8 1L7 1L7 21L8 21Z" fill="#797A7C"/>
                        <line x1="11.5806" y1="11.6128" x2="19.9031" y2="11.6128" stroke="#797A7C" stroke-width="2" stroke-linecap="round"/>
                        <line x1="11.5806" y1="15.4839" x2="23.129" y2="15.4839" stroke="#797A7C" stroke-width="2" stroke-linecap="round"/>
                        <line x1="11.5806" y1="20" x2="26.3548" y2="20" stroke="#797A7C" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select bg-gray-50 border border-gray-300 text-gray-900 text-sm focus:ring-blue-500 focus:border-blue-500 block p-2.5">
                        <option selected>Name A-Z</option>
                        <option value="za">Name Z-A</option>
                    </select>
                </div>
            </div>
        </div>
        <!-- Add User Button -->
        <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2" id="open-vendor-popup-btn">
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
                        <!--th scope="col" class="px-6 py-3 text-left">
                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50">
                        </th-->
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Full Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Email Address</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Phone Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Last Login</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <!-- User Row 1 -->
					<?php //print_r($data);
						if (!empty($data)){
						$i=0;
						foreach($data['users'] as $item):
					?>
                    <tr class="table-content-text">
                        <!--td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-offset-0 focus:ring-indigo-200 focus:ring-opacity-50">
                        </td-->
                        <td class="px-6 py-4 whitespace-nowrap"><?= $item['id'] ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $item['name']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $item['email']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap"><?= $item['phone']; ?></td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span style="width: 75px; height: 25px;" class="px-3 py-1 inline-flex items-center justify-center text-xs leading-5 font-semibold rounded-md bg-black text-white"><?= $item['role']; ?></span>
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
                        <td class="px-6 py-4 whitespace-nowrap">23-08-2025 13:10</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-4">
                                <a href="#" class="text-gray-400 hover:text-black">
                                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M12.0465 8.20171C10.6474 9.47037 9.33829 11.0991 7.90075 12.3041C7.56581 12.5845 7.25417 12.7388 6.8125 12.7978C6.09762 12.8939 5.09165 12.9659 4.36744 12.9883C3.50508 13.0154 2.73585 12.5712 2.75448 11.6359C2.76884 10.909 2.86781 9.93098 2.95164 9.19835C2.992 8.84595 3.04983 8.53545 3.24582 8.2299L11.1585 0.415632C11.9227 -0.178697 12.8029 -0.120026 13.5279 0.491828C14.0922 0.968052 15.0966 1.93688 15.5631 2.49426C16.1484 3.19335 16.1422 4.07837 15.5631 4.77785C14.5839 5.96041 13.1029 7.05649 12.0461 8.20209L12.0465 8.20171ZM12.2572 1.03396C12.1435 1.04272 11.9914 1.11244 11.8971 1.17873C11.5144 1.44732 11.1364 2.00355 10.7525 2.30224L13.6765 5.13787C14.091 4.59726 15.3764 3.97665 14.7694 3.19678C14.2393 2.51559 13.2993 1.87897 12.7319 1.19664C12.6112 1.0972 12.416 1.02139 12.2568 1.03396H12.2572ZM3.89279 11.8744C3.9382 11.9216 4.10004 11.9635 4.17145 11.962C4.89643 11.9464 5.93228 11.858 6.65687 11.7692C6.78689 11.7532 6.92699 11.7174 7.03916 11.6492L12.8693 5.94022L9.99496 3.04591L4.13652 8.79985C4.00651 8.99529 3.98516 9.58505 3.96032 9.84602C3.9153 10.323 3.85631 10.8968 3.84195 11.368C3.83846 11.4842 3.82022 11.7989 3.8924 11.8744H3.89279Z" fill="black"/>
                                        <path d="M2.04958 2.33219C3.16732 2.20875 4.46941 2.40038 5.60695 2.32418C6.18289 2.44724 6.14176 3.26711 5.56736 3.34711C4.59787 3.48198 3.31946 3.26368 2.30922 3.34902C1.6281 3.40655 1.1127 3.92468 1.04788 4.5872V13.6953C1.10687 14.4325 1.64634 14.914 2.38684 14.9716H11.5488C13.652 14.8081 12.6526 11.8803 12.8886 10.5339C13.0523 9.99635 13.7703 9.99864 13.9326 10.5339C13.8247 12.6091 14.6599 15.6337 11.7045 16.0006H2.2316C1.06845 15.9168 0.137389 15.0177 0 13.8862L0.00620967 4.36433C0.140494 3.35931 1.00791 2.44724 2.04997 2.33219H2.04958Z" fill="black"/>
                                    </svg>
                                </a>
                                <a href="#" class="text-gray-400 hover:text-red-600">
                                    <svg width="14" height="16" viewBox="0 0 14 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M10.2198 2.46658L13.5732 2.48141C14.142 2.57241 14.1239 3.51406 13.6281 3.62287C13.4664 3.65814 13.1578 3.57143 13.1049 3.74156L11.7041 14.1792C11.4162 15.0615 10.6479 15.653 9.72717 15.7357C8.33059 15.861 5.74347 15.8501 4.33736 15.739C3.36304 15.6622 2.57373 15.0773 2.28587 14.1287L0.898821 3.74156L0.80254 3.64496C-0.0761549 3.87476 -0.309794 2.57241 0.488063 2.47482C0.982945 2.41415 3.62001 2.56813 3.78366 2.4669C4.1494 1.59977 4.1402 0.663395 5.11879 0.234443C5.84468 -0.083726 8.27177 -0.0863637 8.96973 0.277635C9.90232 0.763627 9.85106 1.60867 10.2194 2.4669L10.2198 2.46658ZM8.92636 2.47746C8.78341 2.05774 8.80214 1.41876 8.28689 1.2849C7.98818 1.20742 5.94721 1.21467 5.67216 1.30402C5.19601 1.45898 5.21934 2.07059 5.07738 2.47746H8.92636ZM11.9413 3.63605H2.06242L3.47148 13.9045C3.60687 14.2458 3.90985 14.4762 4.27558 14.5135C6.0057 14.4096 7.8919 14.6516 9.60263 14.5161C10.2805 14.4624 10.5135 14.1409 10.642 13.4993L11.9417 3.63605H11.9413Z" fill="#DF0000"/>
                                        <path d="M5.82431 5.8472C5.9058 5.92897 5.96857 6.05821 5.9781 6.17592C5.81445 8.00251 6.18709 10.1898 5.97678 11.9729C5.89627 12.6554 4.98209 12.6976 4.82436 12.0322L4.81812 6.17625C4.86741 5.69454 5.5003 5.5231 5.82464 5.84753L5.82431 5.8472Z" fill="#DF0000"/>
                                        <path d="M9.03183 5.8472C9.11332 5.92897 9.17609 6.05821 9.18562 6.17592C9.02197 8.00251 9.39461 10.1898 9.1843 11.9729C9.10379 12.6554 8.18961 12.6976 8.03188 12.0322L8.02563 6.17625C8.07493 5.69454 8.70782 5.5231 9.03216 5.84753L9.03183 5.8472Z" fill="#DF0000"/>
                                    </svg>
                                </a>
                            </div>
                        </td>
                    </tr>
					<?php endforeach; ?>
					<?php }else{ ?>
						<tr>
							<td colspan="8" class="text-center">No Users found.</td>
						</tr>
					<?php } ?>

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Pagination -->
	<?php         
		$page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
		$page = $page < 1 ? 1 : $page;
		$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5; // Orders per page, default 20
		$limit = in_array($limit, [5, 20, 50, 100]) ? $limit : 5; // Only allow specific values
		$total_orders = isset($data['total_orders']) ? (int)$data['total_orders'] : 0;
		$total_pages = $limit > 0 ? ceil($total_orders / $limit) : 1;
	?>
	<?php if ($data['total_pages'] > 1): ?>
    <div class="bg-white rounded-xl shadow-md p-4">
        <div class="flex items-center justify-center">
            <div class="flex items-center gap-4 text-sm text-gray-600">
                <span>Page</span>
                <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no <= 1 ? 'disabled' : '' ?>" >
                    <a class="page-link" href="?page=users&acton=list&page_no=<?= $pageoo-1 ?>&limit=<?= $limit ?>" tabindex="-1">
						<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
					</a>
                </button>
                <span class="w-8 h-8 flex items-center justify-center bg-black text-white rounded-full shadow-md">1</span>
                <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
                <div class="relative">
                    <select class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1">
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
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: calc(45% + 61px); min-width: 661px;">

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

                    <div class="flex items-start mb-6 pb-6 border-b">
                        <img src="https://placehold.co/100x80/e2e8f0/4a5568?text=Item" alt="Product Image" class="rounded-md w-24 h-20 object-cover">
                        <div class="ml-6 text-sm text-gray-600 space-y-1">
                            <p><strong>Order ID:</strong> 123456</p>
                            <p><strong>Order Date:</strong> 20th July 25</p>
                            <p><strong>Item:</strong> 12" Painting</p>
                            <p><strong>Vendorr ID:</strong> 47635</p>
                            <p><strong>Vendor Name:</strong> ABC Pvt. Ltd.</p>
                            <p><strong>Vendor Phone:</strong> +9810865978 <i class="fab fa-whatsapp text-green-500 ml-1"></i> <span class="text-blue-600">info@vendor1.com</span></p>
                        </div>
                    </div>

                    <form id="invoice-form">
                        <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6">
                            <div>
                                <label for="invoice-date" class="text-sm font-medium text-gray-700">Invoice Date:</label>
                                <input type="date" id="invoice-date" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="gst-reg" class="text-sm font-medium text-gray-700">GST Reg:</label>
                                <select id="gst-reg" class="form-input w-full bg-white mt-1">
                                    <option>Yes</option>
                                    <option>No</option>
                                </select>
                                <p class="text-xs text-red-500 text-right mt-1">Advance, Partial, Full</p>
                            </div>
                            <div>
                                <label for="sub-total" class="text-sm font-medium text-gray-700">Sub Total ₹:</label>
                                <input type="number" id="sub-total" value="10000" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="gst-total" class="text-sm font-medium text-gray-700">GST Total:</label>
                                <input type="number" id="gst-total" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="shipping" class="text-sm font-medium text-gray-700">Shipping ₹:</label>
                                <input type="number" id="shipping" value="10000" class="form-input w-full mt-1">
                            </div>
                            <div>
                                <label for="grand-total" class="text-sm font-medium text-gray-700">Grand Total ₹:</label>
                                <input type="number" id="grand-total" value="10000" class="form-input w-full mt-1 bg-gray-100">
                            </div>
                        </div>

                        <div class="mb-6">
                            <label class="text-sm font-medium text-gray-700 mb-1 block">Invoice PDF:</label>
                            <div id="file-drop-area" class="file-drop-area">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                                <p class="text-sm text-gray-500">Drag & Drop your invoice file here or</p>
                                <button type="button" id="choose-file-btn" class="mt-2 bg-white border border-gray-300 text-gray-700 px-4 py-1 rounded-md text-sm hover:bg-gray-50">Choose file</button>
                                <input type="file" id="file-input" class="hidden" accept=".pdf,.jpg,.jpeg,.png">
                                <p class="text-xs text-gray-400 mt-2">Only PDF, JPG, PNG</p>
                            </div>
                        </div>

                        <div id="uploaded-file-section" class="hidden mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-2">Uploaded Invoice:</h3>
                            <div id="file-info" class="border rounded-md p-3 flex items-center justify-between">
                                <!-- File info will be injected here by JS -->
                            </div>
                        </div>

                        <div class="flex justify-end items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Cancel</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
</script>