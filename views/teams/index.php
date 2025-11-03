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
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="status_filter" id="status_filter">
                            <option value="" selected>All Status</option>
                            <option value="1" <?php echo ($data['status_filter'] == "1") ? "selected" : ""?>>Active</option>
                            <option value="0" <?php echo ($data['status_filter'] == "0") ? "selected" : ""?>>Inactive</option>
                        </select>
                    </div>
                    
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=teams&action=list';">
                    </div>
                </div>
            </form>
        </div>
        <!-- Add User Button -->
        <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]" id="open-vendor-popup-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>Add</button>
    </div>

    <!-- Listing -->
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($teams_data)): ?>
                        <?php foreach ($teams_data as $index => $tc): ?>
                            <tr class="table-content-text">
                                <td class="px-6 py-4 whitespace-nowrap"><?= $index + 1 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($tc['team_name']) ?? '' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= ($tc['is_active'] == 1 ? "Active" : "Inactive") ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Three-dot menu container -->
                                    <div class="menu-wrapper">
                                        <button class="menu-button" onclick="toggleMenu(this)">
                                            &#x22EE; <!-- Vertical ellipsis -->
                                        </button>
                                        <ul class="menu-popup">
                                            <li onclick="openEditModal(<?= htmlspecialchars($tc['id']) ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <li class="delete-btn" data-id="<?php echo $tc['id']; ?>"><i class="fa-solid fa-trash"></i> Delete</li>
                                        </ul>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-6 py-4 text-center text-gray-500">No record found.</td>
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
                        <a class="page-link" <?php if(($page_no-1) >= 1) { ?> href="?page=teams&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?>  tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=teams&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=teams&acton=list&page_no=1&limit=' + this.value;">
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
        fetch('?page=teams&action=addRecord', {
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
    // Delete
    document.addEventListener("DOMContentLoaded", () => {
        const deleteButtons = document.querySelectorAll(".delete-btn");
        
        deleteButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const id = btn.getAttribute("data-id");
                window.closeAllMenus();
                if (!confirm("Are you sure you want to delete this record?")) return;

                fetch("?page=teams&action=deleteRecord", {
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
        fetch("?page=teams&action=getDetails&id=" + id)
        .then(res => res.json())
        .then(data => {
            console.log(data);
            let datas = JSON.parse(data);
            if (data.status === "error") {
                alert(data.message);
                return;
            }
            // Populate form fields data
            document.getElementById("editId").value   = datas.id;
            document.getElementById("editTeamName").value   = datas.team_name;
            document.getElementById("editTeamDescription").value = (datas.team_description !== null) ? datas.team_description : '';
            document.getElementById("editStatus").value = datas.is_active;

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
        // Ensure CKEditor updates <textarea>
        for (var instance in CKEDITOR.instances) {
            CKEDITOR.instances[instance].updateElement();
        }
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=teams&action=addRecord', {
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
                    window.location.href = '?page=teams&action=list';
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