<?php
function material_nav_query($search, $status_filter, array $extra = [])
{
    $params = array_merge([
        'page' => 'materials',
        'action' => 'list',
    ], $extra);
    if (($search ?? '') !== '') {
        $params['search_text'] = $search;
    }
    if (($status_filter ?? '') !== '') {
        $params['status_filter'] = $status_filter;
    }
    return http_build_query($params);
}

$page = isset($page_no) ? (int) $page_no : 1;
$page = $page < 1 ? 1 : $page;
$limit = isset($limit) ? (int) $limit : 20;
$limit = in_array($limit, [10, 20, 50, 100], true) ? $limit : 20;
$total_records = isset($totalRecords) ? (int) $totalRecords : 0;
$total_pages = $limit > 0 ? (int) ceil($total_records / $limit) : 1;
$slot_size = 10;
$start = max(1, $page - (int) floor($slot_size / 2));
$end = min($total_pages, $start + $slot_size - 1);
if ($end - $start < $slot_size - 1) {
    $start = max(1, $end - $slot_size + 1);
}
$next_display_order = isset($next_display_order) ? (int) $next_display_order : 1;
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-layer-group text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Catalog · Materials</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Material <span class="text-amber-800">listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage product materials used on inbound forms: names, URL slugs, display order, and active status.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <button type="button" id="open-material-popup-btn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add material
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find materials by name, slug, or active status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="materials">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by material name or slug"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo htmlspecialchars($search ?? ''); ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" id="status_filter"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="" <?php echo (($status_filter ?? '') === '') ? 'selected' : ''; ?>>All status</option>
                        <option value="1" <?php echo (($status_filter ?? '') === '1') ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (($status_filter ?? '') === '0') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="document.getElementById('filterForm').reset();window.location='?page=materials&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
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
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Name</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Slug</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Display order</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Created by</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($materials_data)): ?>
                        <?php $counter = ($page - 1) * $limit; ?>
                        <?php foreach ($materials_data as $row): ?>
                            <?php
                            $isActive = !empty($row['is_active']);
                            $statusClass = $isActive
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-600/20'
                                : 'bg-amber-50 text-amber-900 ring-amber-600/25';
                            $updatedAt = $row['updated_at'] ?? $row['created_at'] ?? '';
                            $updatedLabel = $updatedAt ? date('d M Y', strtotime($updatedAt)) : '—';
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700"><?= ++$counter ?></td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900"><?= htmlspecialchars((string) ($row['material_name'] ?? '')) ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-mono text-gray-700"><?= htmlspecialchars((string) ($row['material_slug'] ?? '')) ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700"><?= (int) ($row['display order'] ?? $row['display_order'] ?? 0) ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?= htmlspecialchars((string) ($row['user_name'] ?? '—')) ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($updatedLabel) ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?= $statusClass ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="menu-wrapper">
                                        <button type="button" class="menu-button" onclick="toggleMenu(this)">&#x22EE;</button>
                                        <ul class="menu-popup">
                                            <li onclick="openEditModal(<?= (int) $row['id'] ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <li class="deactivate-btn" data-id="<?php echo (int) $row['id']; ?>" data-name="<?php echo htmlspecialchars((string) ($row['material_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-ban"></i> Deactivate</li>
                                            <li class="permanent-delete-btn text-red-700" data-id="<?php echo (int) $row['id']; ?>" data-name="<?php echo htmlspecialchars((string) ($row['material_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-trash"></i> Delete permanently</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center text-gray-500">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No materials match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try changing filters or add a new material.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if (!empty($materials_data)): ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-4">
            <div class="flex flex-wrap items-center justify-center gap-3 text-sm text-gray-600">
                <?php if ($total_pages > 1): ?>
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if ($page <= 1) {
                        echo 'opacity-50 pointer-events-none';
                    } ?>"
                        href="?<?= htmlspecialchars(material_nav_query($search ?? '', $status_filter ?? '', ['page_no' => max(1, $page - $slot_size), 'limit' => $limit])) ?>">
                        &laquo; Prev
                    </a>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a class="inline-flex items-center px-3 py-1.5 rounded-lg <?= $i === $page ? 'bg-amber-600 text-white font-bold' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>"
                            href="?<?= htmlspecialchars(material_nav_query($search ?? '', $status_filter ?? '', ['page_no' => $i, 'limit' => $limit])) ?>">
                            <?= (int) $i ?>
                        </a>
                    <?php endfor; ?>
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if ($page >= $total_pages) {
                        echo 'opacity-50 pointer-events-none';
                    } ?>"
                        href="?<?= htmlspecialchars(material_nav_query($search ?? '', $status_filter ?? '', ['page_no' => min($total_pages, $page + $slot_size), 'limit' => $limit])) ?>">
                        Next &raquo;
                    </a>
                <?php endif; ?>
                <select id="rows-per-page" class="px-2 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                    onchange="location.href='?<?= htmlspecialchars(material_nav_query($search ?? '', $status_filter ?? '', ['page_no' => 1])) ?>&limit=' + encodeURIComponent(this.value);">
                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                        <option value="<?= (int) $opt ?>" <?= (int) $limit === (int) $opt ? 'selected' : '' ?>><?= (int) $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div id="popup-wrapper" class="hidden">
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div>
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-material-popup-btn" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div id="material-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add material</h2>
                    <div id="addMaterialMsg" class="text-sm font-bold"></div>
                    <form id="addMaterialForm">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Material name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="addMaterialName" id="addMaterialName" placeholder="e.g. Cotton Fabric" onkeyup="generateMaterialSlug(this.value, 'addMaterialSlug')" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Slug</label>
                                <input type="text" class="form-input w-full mt-1 font-mono" name="addMaterialSlug" id="addMaterialSlug" placeholder="e.g. cotton-fabric" />
                                <p class="text-xs text-gray-500 mt-1">URL-friendly identifier; auto-generated from name if left blank.</p>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Display order</label>
                                <input type="number" class="form-input w-full mt-1" name="addDisplayOrder" id="addDisplayOrder" value="<?= (int) $next_display_order ?>" min="0" />
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
                            <button type="button" id="cancel-material-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade hidden" id="editMaterialModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-material-popup-btn-edit" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit material</h2>
                    <div id="editMaterialMsg"></div>
                    <form id="editMaterialForm">
                        <input type="hidden" id="editId" name="id" value="">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Material name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="editMaterialName" id="editMaterialName" onkeyup="generateMaterialSlug(this.value, 'editMaterialSlug')" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Slug</label>
                                <input type="text" class="form-input w-full mt-1 font-mono" name="editMaterialSlug" id="editMaterialSlug" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Display order</label>
                                <input type="number" class="form-input w-full mt-1" name="editDisplayOrder" id="editDisplayOrder" value="0" min="0" />
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
                            <button type="button" id="cancel-material-btn-edit" class="action-btn cancel-btn">Back</button>
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
    function generateMaterialSlug(name, targetId) {
        const slug = name.toLowerCase().trim()
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
        const el = document.getElementById(targetId);
        if (el && !el.dataset.manualEdit) {
            el.value = slug;
        }
    }

    document.getElementById('addMaterialSlug')?.addEventListener('input', function() {
        this.dataset.manualEdit = '1';
    });
    document.getElementById('editMaterialSlug')?.addEventListener('input', function() {
        this.dataset.manualEdit = '1';
    });

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

    const openMaterialPopupBtn = document.getElementById('open-material-popup-btn');
    const popupWrapper = document.getElementById('popup-wrapper');
    const modalSlider = document.getElementById('modal-slider');
    const cancelMaterialBtn = document.getElementById('cancel-material-btn');
    const closeMaterialPopupBtn = document.getElementById('close-material-popup-btn');

    function openMaterialPopup() {
        document.getElementById('addMaterialForm').reset();
        document.getElementById('addDisplayOrder').value = <?= (int) $next_display_order ?>;
        const slugEl = document.getElementById('addMaterialSlug');
        if (slugEl) delete slugEl.dataset.manualEdit;
        document.getElementById('addMaterialMsg').innerHTML = '';
        popupWrapper.classList.remove('hidden');
        setTimeout(() => modalSlider.classList.remove('translate-x-full'), 10);
    }

    function closeMaterialPopup() {
        modalSlider.classList.add('translate-x-full');
    }

    modalSlider.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSlider.classList.contains('translate-x-full')) {
            popupWrapper.classList.add('hidden');
        }
    });

    openMaterialPopupBtn.addEventListener('click', openMaterialPopup);
    cancelMaterialBtn.addEventListener('click', closeMaterialPopup);
    closeMaterialPopupBtn.addEventListener('click', closeMaterialPopup);

    document.getElementById('addMaterialForm').onsubmit = function(e) {
        e.preventDefault();
        const form = new FormData(this);
        const params = new URLSearchParams(form).toString();
        fetch('?page=materials&action=addRecord', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById('addMaterialMsg');
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

    function showMaterialActionResult(data) {
        const title = document.getElementById('modalTitle');
        title.innerText = data.success ? 'Success' : 'Error';
        title.className = data.success ? 'text-2xl font-bold text-green-600 mb-4' : 'text-2xl font-bold text-red-600 mb-4';
        document.getElementById('showMessage').innerText = data.message || 'Unknown error';
        document.getElementById('deleteMsgBox').classList.remove('hidden');
        clearTimeout(successModalTimer);
        if (data.success) {
            successModalTimer = setTimeout(() => closeDeleteModal(), 1500);
        }
    }

    function checkMaterialUsage(id) {
        return fetch('?page=materials&action=checkUsage&id=' + encodeURIComponent(id))
            .then(res => res.json());
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.deactivate-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name') || 'this material';
                window.closeAllMenus();

                const usage = await checkMaterialUsage(id);
                if (usage.in_use) {
                    showMaterialActionResult({
                        success: false,
                        message: 'Cannot deactivate: "' + name + '" is used on ' + usage.inbound_count + ' inbound product(s).'
                    });
                    return;
                }

                if (!confirm('Deactivate "' + name + '"? It will be marked inactive but kept in the database.')) return;

                fetch('?page=materials&action=deleteRecord', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(showMaterialActionResult);
            });
        });

        document.querySelectorAll('.permanent-delete-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name') || 'this material';
                window.closeAllMenus();

                const usage = await checkMaterialUsage(id);
                if (usage.in_use) {
                    showMaterialActionResult({
                        success: false,
                        message: 'Cannot delete: "' + name + '" is used on ' + usage.inbound_count + ' inbound product(s). Change the material on those records first.'
                    });
                    return;
                }

                if (!confirm('Permanently delete "' + name + '"? This cannot be undone.')) return;

                fetch('?page=materials&action=permanentDelete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id: id })
                })
                .then(res => res.json())
                .then(showMaterialActionResult);
            });
        });
    });

    function closeDeleteModal() {
        document.getElementById('deleteMsgBox').classList.add('hidden');
        clearTimeout(successModalTimer);
        window.location.reload();
    }

    const popupWrapperEdit = document.getElementById('editMaterialModal');
    const modalSliderEdit = document.getElementById('modal-slider-edit');
    const cancelMaterialBtnEdit = document.getElementById('cancel-material-btn-edit');
    const closeMaterialPopupBtnEdit = document.getElementById('close-material-popup-btn-edit');

    function openEditModal(id) {
        closeAllMenus();
        fetch('?page=materials&action=getDetails&id=' + id)
        .then(res => res.json())
        .then(data => {
            if (data.status === 'error') {
                alert(data.message);
                return;
            }
            document.getElementById('editId').value = data.id;
            document.getElementById('editMaterialName').value = data.material_name || '';
            const editSlug = document.getElementById('editMaterialSlug');
            editSlug.value = data.material_slug || '';
            delete editSlug.dataset.manualEdit;
            document.getElementById('editDisplayOrder').value = data.display_order != null ? data.display_order : 0;
            document.getElementById('editStatus').value = data.is_active ? '1' : '0';

            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => modalSliderEdit.classList.remove('translate-x-full'), 10);
        });
    }

    function closeMaterialPopupEdit() {
        modalSliderEdit.classList.add('translate-x-full');
    }

    closeMaterialPopupBtnEdit.addEventListener('click', closeMaterialPopupEdit);
    cancelMaterialBtnEdit.addEventListener('click', closeMaterialPopupEdit);

    modalSliderEdit.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSliderEdit.classList.contains('translate-x-full')) {
            popupWrapperEdit.classList.add('hidden');
        }
    });

    document.getElementById('editMaterialForm').onsubmit = function(e) {
        e.preventDefault();
        const form = new FormData(this);
        const params = new URLSearchParams(form).toString();
        fetch('?page=materials&action=addRecord', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById('editMaterialMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = '<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">✅ ' + data.message + '</div>';
                setTimeout(() => { window.location.href = '?page=materials&action=list'; }, 1000);
            } else {
                msgBox.innerHTML = '<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">❌ ' + data.message + '</div>';
            }
        });
    };
</script>
