<div class="max-w-7xl mx-auto space-y-6">
    <!-- Page Header -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <!-- Header Section with Filters and Actions -->
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <!-- Filters -->
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="notifications">
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
                            <input type="text" name="search_text" placeholder="Search by message" class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" style="width: 300px; height: 37px; border-radius: 5px;" value="<?php echo $data['search'] ?? '' ?>">
                        </div>
                    </div>
                    
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=notifications&action=list';">
                    </div>
                </div>
            </form>
            <!-- Button -->
            <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]" id="btnClearAllNotifications">
                <i class="fa-solid fa-broom"></i>
            Clear All</button>
        </div>
    </div>

    <!-- Listing -->
    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
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
                        <th scope="col" class="px-2 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Date & Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Message</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($notifications)): ?>
                        <?php foreach ($notifications as $index => $tc): ?>
                            <tr class="table-content-text">
                                <td class="px-2 py-2 whitespace-nowrap"><?= date("d M Y H:i") ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if($tc["is_read"]==0) { echo "<strong style='color: black;'>".htmlspecialchars($tc['title'])."</strong>"; } else { ?>
                                    <?= htmlspecialchars($tc['title']) ?? '' ?>
                                    <?php } ?>
                                
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars($tc['message']) ?? '' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Three-dot menu container -->
                                    <div class="menu-wrapper">
                                        <button class="menu-button" onclick="toggleMenu(this)">
                                            &#x22EE; <!-- Vertical ellipsis -->
                                        </button>
                                        <ul class="menu-popup">
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
                        <a class="page-link" <?php if(($page_no-1) >= 1) { ?> href="?page=notifications&acton=list&page_no=<?= $page_no-1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?>  tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if($page_no < $total_pages) { ?> href="?page=notifications&acton=list&page_no=<?= $page_no+1 ?>&limit=<?= $limit ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?page=notifications&acton=list&page_no=1&limit=' + this.value;">
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

                fetch("?page=notifications&action=deleteRecord", {
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

    btnClearAllNotifications = document.getElementById("btnClearAllNotifications");;
    btnClearAllNotifications.addEventListener("click", () => {
        if (!confirm("Are you sure you want to clear all notifications?")) return;

        fetch("?page=notifications&action=delete_all_notifications", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" }
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

    function closeDeleteModal() {
        document.getElementById("deleteMsgBox").classList.add("hidden");
        clearTimeout(successModalTimer);
        window.location.reload();
    }

</script>