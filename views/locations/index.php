<?php
function location_type_label($type)
{
    return $type === 'warehouse' ? 'Warehouse' : 'Retail store';
}

function location_nav_query($search, $status_filter, $type_filter, array $extra = [])
{
    $params = array_merge([
        'page' => 'locations',
        'action' => 'list',
    ], $extra);
    if (($search ?? '') !== '') {
        $params['search_text'] = $search;
    }
    if (($status_filter ?? '') !== '') {
        $params['status_filter'] = $status_filter;
    }
    if (($type_filter ?? '') !== '') {
        $params['type_filter'] = $type_filter;
    }
    return http_build_query($params);
}
?>

<div class="max-w-7xl mx-auto space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="bg-white rounded-xl shadow-md p-4 flex flex-wrap items-center justify-between gap-4 flex-grow mt-[10px]">
            <form method="get" id="filterForm">
                <input type="hidden" name="page" value="locations">
                <input type="hidden" name="action" value="list">
                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <svg width="15" height="15" viewBox="0 0 15 15" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M14 1H1L5.5 6.5V12L8.5 14V6.5L14 1Z" stroke="#797A7C" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        <span class="text-gray-600 font-medium">Filters:</span>
                    </div>
                    <div class="relative flex items-left gap-2">
                        <input type="text" name="search_text" placeholder="Search title, display name, or address" class="custom-input border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" style="width: 300px; height: 37px; border-radius: 5px;" value="<?php echo htmlspecialchars($search ?? '') ?>">
                    </div>
                    <div class="relative">
                        <select style="width: 152px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="status_filter" id="status_filter">
                            <option value="" <?php echo (($status_filter ?? '') === '') ? 'selected' : '' ?>>All status</option>
                            <option value="1" <?php echo (($status_filter ?? '') === '1') ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?php echo (($status_filter ?? '') === '0') ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="relative">
                        <select style="width: 170px; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="type_filter" id="type_filter">
                            <option value="" <?php echo (($type_filter ?? '') === '') ? 'selected' : '' ?>>All types</option>
                            <option value="retail_store" <?php echo (($type_filter ?? '') === 'retail_store') ? 'selected' : '' ?>>Retail store</option>
                            <option value="warehouse" <?php echo (($type_filter ?? '') === 'warehouse') ? 'selected' : '' ?>>Warehouse</option>
                        </select>
                    </div>
                    <div class="relative">
                        <input type="submit" value="Search" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2">
                    </div>
                    <div class="relative">
                        <input type="button" value="Clear" style="width: 100px; height: 37px; border-radius: 5px; font-family: Inter; font-weight: 800; font-size: 13px; line-height: 100%; letter-spacing: 0%;" class="font-bold rounded-lg flex items-center justify-center gap-2 bg-red-500 hover:bg-red-600 text-white" onclick="document.getElementById('filterForm').reset();window.location='?page=locations&action=list';">
                    </div>
                </div>
            </form>
        </div>
        <button style="width: 120px; height: 40px; font-family: Inter; font-weight: 500; font-size: 13px; line-height: 100%; letter-spacing: 0%; margin-right:10px;" class="bg-gray-800 hover:bg-gray-900 text-white font-bold rounded-lg flex items-center justify-center gap-2 mt-[10px]" id="open-location-popup-btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>Add</button>
    </div>

    <div class="bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-6">
            <div id="deleteMsgBox" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
                <div class="bg-white rounded-lg shadow-lg w-[400px] p-8 text-center">
                    <h2 id="modalTitle" class="text-xl font-bold text-green-600 mb-4">Alert Box</h2>
                    <p id="showMessage" class="text-gray-700"></p>
                    <div class="mt-6">
                        <button onclick="closeDeleteModal()" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">OK</button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">#</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Type</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Title</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Display name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Address</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Order</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Default</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Action</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($locations_data)): ?>
                        <?php foreach ($locations_data as $index => $row): ?>
                            <?php
                            $addr = $row['address'] ?? '';
                            $addrShort = strlen($addr) > 80 ? substr($addr, 0, 80) . '…' : $addr;
                            ?>
                            <tr class="table-content-text">
                                <td class="px-6 py-4 whitespace-nowrap"><?= (int) $index + 1 ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars(location_type_label($row['address_type'] ?? '')) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars((string) ($row['address_title'] ?? '')) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= htmlspecialchars((string) ($row['display_name'] ?? '')) ?></td>
                                <td class="px-6 py-4 max-w-xs" title="<?= htmlspecialchars($addr) ?>"><?= htmlspecialchars($addrShort) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= (int) ($row['order_no'] ?? 0) ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= !empty($row['is_default']) ? 'Yes' : 'No' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap"><?= !empty($row['is_active']) ? 'Active' : 'Inactive' ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="menu-wrapper">
                                        <button class="menu-button" onclick="toggleMenu(this)">&#x22EE;</button>
                                        <ul class="menu-popup">
                                            <li onclick="openEditModal(<?= (int) $row['id'] ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <li class="delete-btn" data-id="<?php echo (int) $row['id']; ?>"><i class="fa-solid fa-ban"></i> Deactivate</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-6 py-4 text-center text-gray-500">No record found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
    $page_no = $page_no ?? 1;
    $limit = $limit ?? 20;
    $total_records = $totalRecords ?? 0;
    $total_pages = $limit > 0 ? (int) ceil($total_records / $limit) : 1;
    ?>
    <?php if ($total_pages > 1): ?>
        <div class="bg-white rounded-xl shadow-md p-4">
            <div class="flex items-center justify-center">
                <div class="flex items-center gap-4 text-sm text-gray-600">
                    <span>Page</span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if ($page_no - 1 >= 1) { ?> href="?<?php echo htmlspecialchars(location_nav_query($search ?? '', $status_filter ?? '', $type_filter ?? '', ['page_no' => $page_no - 1, 'limit' => $limit])) ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                    </button>
                    <span id="page-number" class="bg-black text-white rounded-full h-8 w-8 flex items-center justify-center text-sm font-bold shadow-lg"><?= (int) $page_no ?></span>
                    <button class="p-2 rounded-full hover:bg-gray-100 <?= $page_no >= $total_pages ? 'disabled' : '' ?>">
                        <a class="page-link" <?php if ($page_no < $total_pages) { ?> href="?<?php echo htmlspecialchars(location_nav_query($search ?? '', $status_filter ?? '', $type_filter ?? '', ['page_no' => $page_no + 1, 'limit' => $limit])) ?>" <?php } else { ?> href="#" <?php } ?> tabindex="-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg></a>
                    </button>
                    <div class="relative">
                        <select id="rows-per-page" class="custom-select bg-transparent border-b border-gray-300 text-gray-900 text-sm focus:ring-0 focus:border-gray-500 block w-full p-1" onchange="location.href='?<?php echo htmlspecialchars(location_nav_query($search ?? '', $status_filter ?? '', $type_filter ?? '', ['page_no' => 1])) ?>&limit=' + encodeURIComponent(this.value);">
                            <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                <option value="<?= (int) $opt ?>" <?= (int) $limit === (int) $opt ? 'selected' : '' ?>><?= (int) $opt ?></option>
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
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div>
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 40%; min-width: 420px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-location-popup-btn" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="location-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add location</h2>
                    <div id="addLocationMsg" class="text-sm font-bold"></div>
                    <form id="addLocationForm">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="addAddressType" id="addAddressType">
                                    <option value="retail_store">Retail store</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address title</label>
                                <input type="text" class="form-input w-full mt-1" name="addAddressTitle" id="addAddressTitle" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Display name</label>
                                <input type="text" class="form-input w-full mt-1" name="addDisplayName" id="addDisplayName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                                <textarea class="w-full min-h-[100px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" required name="addAddress" id="addAddress"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Sort order</label>
                                    <input type="number" class="form-input w-full mt-1" name="addOrderNo" id="addOrderNo" value="0" min="0" max="127" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Default location</label>
                                    <select class="form-input w-full mt-1" name="addIsDefault" id="addIsDefault">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="addStatus" id="addStatus">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-center items-center gap-4 pt-6 border-t mt-6">
                            <button type="button" id="cancel-location-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade hidden" id="editLocationModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 40%; min-width: 420px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-location-popup-btn-edit" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit location</h2>
                    <div id="editLocationMsg"></div>
                    <form id="editLocationForm">
                        <input type="hidden" id="editId" name="id" value="">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Type <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="editAddressType" id="editAddressType">
                                    <option value="retail_store">Retail store</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address title</label>
                                <input type="text" class="form-input w-full mt-1" name="editAddressTitle" id="editAddressTitle" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Display name</label>
                                <input type="text" class="form-input w-full mt-1" name="editDisplayName" id="editDisplayName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Address <span class="text-red-500">*</span></label>
                                <textarea class="w-full min-h-[100px] p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition" required name="editAddress" id="editAddress"></textarea>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Sort order</label>
                                    <input type="number" class="form-input w-full mt-1" name="editOrderNo" id="editOrderNo" value="0" min="0" max="127" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Default location</label>
                                    <select class="form-input w-full mt-1" name="editIsDefault" id="editIsDefault">
                                        <option value="0">No</option>
                                        <option value="1">Yes</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="editStatus" id="editStatus">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-center items-center gap-4 pt-6 border-t mt-6">
                            <button type="button" id="cancel-location-btn-edit" class="action-btn cancel-btn">Back</button>
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

<script>
    function toggleMenu(button) {
        const popup = button.nextElementSibling;
        popup.style.display = popup.style.display === 'block' ? 'none' : 'block';
        document.querySelectorAll('.menu-popup').forEach(menu => {
            if (menu !== popup) menu.style.display = 'none';
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const menuButtons = document.querySelectorAll('.menu-button');
        window.currentOpenMenu = null;
        const menuMargin = 8;

        window.closeAllMenus = function () {
            if (window.currentOpenMenu) {
                window.currentOpenMenu.classList.add('hidden');
                window.currentOpenMenu.classList.remove('active');
                window.currentOpenMenu.removeAttribute('style');
                window.currentOpenMenu = null;
            }
        };

        document.addEventListener('click', function(e) {
            if (currentOpenMenu && !currentOpenMenu.contains(e.target)) {
                closeAllMenus();
            }
        });

        menuButtons.forEach(button => {
            button.addEventListener('click', (event) => {
                event.stopPropagation();
                const dropdown = button.nextElementSibling;
                if (!dropdown) return;

                const isActive = dropdown.classList.contains('active');

                if (currentOpenMenu && currentOpenMenu !== dropdown) {
                    closeAllMenus();
                }

                if (!isActive) {
                    dropdown.classList.remove('hidden');
                    const buttonRect = button.getBoundingClientRect();
                    const dropdownWidth = dropdown.offsetWidth;
                    const dropdownHeight = dropdown.offsetHeight;
                    const viewportHeight = window.innerHeight;
                    const viewportWidth = window.innerWidth;

                    dropdown.style.position = 'fixed';
                    dropdown.style.top = '';
                    dropdown.style.left = '';
                    if (buttonRect.bottom + dropdownHeight + menuMargin < viewportHeight) {
                        dropdown.style.top = (buttonRect.bottom + menuMargin) + 'px';
                    } else {
                        dropdown.style.top = (buttonRect.top - dropdownHeight - menuMargin) + 'px';
                    }
                    if (buttonRect.left + dropdownWidth < viewportWidth) {
                        dropdown.style.left = buttonRect.left + 'px';
                    } else {
                        dropdown.style.left = (buttonRect.left - dropdownWidth + buttonRect.width) + 'px';
                    }

                    dropdown.classList.add('active');
                    currentOpenMenu = dropdown;
                } else {
                    closeAllMenus();
                }
            });
        });
    });

    const openLocationPopupBtn = document.getElementById('open-location-popup-btn');
    const popupWrapper = document.getElementById('popup-wrapper');
    const modalSlider = document.getElementById('modal-slider');
    const cancelLocationBtn = document.getElementById('cancel-location-btn');
    const closeLocationPopupBtn = document.getElementById('close-location-popup-btn');

    function openLocationPopup() {
        popupWrapper.classList.remove('hidden');
        setTimeout(() => modalSlider.classList.remove('translate-x-full'), 10);
    }

    function closeLocationPopup() {
        modalSlider.classList.add('translate-x-full');
    }

    modalSlider.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSlider.classList.contains('translate-x-full')) {
            popupWrapper.classList.add('hidden');
        }
    });

    openLocationPopupBtn.addEventListener('click', openLocationPopup);
    cancelLocationBtn.addEventListener('click', closeLocationPopup);
    closeLocationPopupBtn.addEventListener('click', closeLocationPopup);

    document.getElementById('addLocationForm').onsubmit = function(e) {
        e.preventDefault();
        const form = new FormData(this);
        const params = new URLSearchParams(form).toString();
        fetch('?page=locations&action=addRecord', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById('addLocationMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = '<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">✅ ' + data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgBox.innerHTML = '<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">❌ ' + data.message + '</div>';
            }
        });
    };

    let successModalTimer;
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                window.closeAllMenus();
                if (!confirm('Deactivate this location? It will be marked inactive.')) return;

                fetch('?page=locations&action=deleteRecord', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(data => {
                    const title = document.getElementById('modalTitle');
                    title.innerText = data.success ? 'Success' : 'Error';
                    title.className = data.success ? 'text-2xl font-bold text-green-600 mb-4' : 'text-2xl font-bold text-red-600 mb-4';
                    document.getElementById('showMessage').innerText = data.message;
                    document.getElementById('deleteMsgBox').classList.remove('hidden');
                    clearTimeout(successModalTimer);
                    successModalTimer = setTimeout(() => closeDeleteModal(), 1500);
                });
            });
        });
    });

    function closeDeleteModal() {
        document.getElementById('deleteMsgBox').classList.add('hidden');
        clearTimeout(successModalTimer);
        window.location.reload();
    }

    const popupWrapperEdit = document.getElementById('editLocationModal');
    const modalSliderEdit = document.getElementById('modal-slider-edit');
    const cancelLocationBtnEdit = document.getElementById('cancel-location-btn-edit');
    const closeLocationPopupBtnEdit = document.getElementById('close-location-popup-btn-edit');

    function openEditModal(id) {
        closeAllMenus();
        fetch('?page=locations&action=getDetails&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                alert(data.message);
                return;
            }
            document.getElementById('editId').value = data.id;
            document.getElementById('editAddressType').value = data.address_type || 'retail_store';
            document.getElementById('editAddressTitle').value = data.address_title || '';
            document.getElementById('editDisplayName').value = data.display_name || '';
            document.getElementById('editAddress').value = data.address || '';
            document.getElementById('editOrderNo').value = data.order_no != null ? data.order_no : 0;
            document.getElementById('editIsDefault').value = data.is_default ? '1' : '0';
            document.getElementById('editStatus').value = data.is_active ? '1' : '0';

            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => modalSliderEdit.classList.remove('translate-x-full'), 10);
        });
    }

    function closeLocationPopupEdit() {
        modalSliderEdit.classList.add('translate-x-full');
    }

    closeLocationPopupBtnEdit.addEventListener('click', closeLocationPopupEdit);
    cancelLocationBtnEdit.addEventListener('click', closeLocationPopupEdit);

    modalSliderEdit.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSliderEdit.classList.contains('translate-x-full')) {
            popupWrapperEdit.classList.add('hidden');
        }
    });

    document.getElementById('editLocationForm').onsubmit = function(e) {
        e.preventDefault();
        const form = new FormData(this);
        const params = new URLSearchParams(form).toString();
        fetch('?page=locations&action=addRecord', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById('editLocationMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = '<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">✅ ' + data.message + '</div>';
                setTimeout(() => { window.location.href = '?page=locations&action=list'; }, 1000);
            } else {
                msgBox.innerHTML = '<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">❌ ' + data.message + '</div>';
            }
        });
    };
</script>
