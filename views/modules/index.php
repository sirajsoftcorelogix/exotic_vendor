<?php
if (!function_exists('modules_list_query')) {
    function modules_list_query($search, $status_filter, $parent_filter = '', array $extra = []) {
        $params = array_merge([
            'page' => 'modules',
            'action' => 'list',
        ], $extra);
        if (($search ?? '') !== '') {
            $params['search_text'] = $search;
        }
        if (($status_filter ?? '') !== '') {
            $params['status_filter'] = $status_filter;
        }
        if (($parent_filter ?? '') !== '') {
            $params['parent_filter'] = $parent_filter;
        }
        return http_build_query($params);
    }
}
$total_records = (int) ($totalRecords ?? 0);
$range_from = $total_records > 0 ? (($page_no - 1) * $limit + 1) : 0;
$range_to = $total_records > 0 ? min($page_no * $limit, $total_records) : 0;
$parent_filter = $parent_filter ?? '';
$has_list_filters = ($search ?? '') !== '' || ($status_filter ?? '') !== '' || $parent_filter !== '';
$parent_filter_summary = '';
if ($parent_filter === '0') {
    $parent_filter_summary = 'Top-level only';
} elseif ($parent_filter !== '' && isset($parent_menus)) {
    foreach ($parent_menus as $_pm) {
        if ((string) $_pm['id'] === (string) $parent_filter) {
            $parent_filter_summary = (string) ($_pm['module_name'] ?? '');
            break;
        }
    }
}
?>
<div class="max-w-7xl mx-auto space-y-6">
    <div class="rounded-2xl border border-slate-200/80 bg-gradient-to-br from-slate-50 to-white px-5 py-4 shadow-sm">
        <h1 class="text-xl font-semibold text-slate-900 tracking-tight">Menu modules</h1>
        <p class="mt-1 text-sm text-slate-600">Configure portal navigation items, slugs, and access structure.</p>
    </div>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-stretch lg:justify-between lg:gap-6">
        <div class="bg-white rounded-xl shadow-md border border-slate-100 p-5 flex-grow min-w-0">
            <form method="get" id="filterForm" class="space-y-4" autocomplete="off">
                <input type="hidden" name="page" value="modules">
                <input type="hidden" name="action" value="list">
                <input type="hidden" name="page_no" value="1">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Find modules</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Search matches display name, page slug, action, or parent label. <span class="text-slate-600">Parent and status refilter immediately</span>; use <strong class="font-medium text-slate-700">Apply</strong> or <strong class="font-medium text-slate-700">Enter</strong> after editing the search box.</p>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-end">
                    <div class="sm:col-span-2 lg:col-span-5 space-y-1.5">
                        <label for="modules_search" class="text-xs font-semibold text-slate-600">Search</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            </span>
                            <input id="modules_search" type="search" name="search_text" enterkeyhint="search"
                                placeholder="Name, slug, action, parent…"
                                class="custom-input w-full min-h-[42px] rounded-lg border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30"
                                value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                    </div>
                    <div class="space-y-1.5 lg:col-span-4">
                        <label for="parent_filter" class="text-xs font-semibold text-slate-600">Parent menu</label>
                        <select id="parent_filter" name="parent_filter" class="custom-select w-full min-h-[42px] rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-400/30">
                            <option value="" <?= $parent_filter === '' ? 'selected' : '' ?>>All modules</option>
                            <option value="0" <?= $parent_filter === '0' ? 'selected' : '' ?>>Top-level only</option>
                            <?php if (!empty($parent_menus)): ?>
                                <?php foreach ($parent_menus as $pmenu): ?>
                                    <option value="<?= (int) $pmenu['id'] ?>" <?= ((string) $parent_filter === (string) $pmenu['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pmenu['module_name'], ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="space-y-1.5 lg:col-span-3">
                        <span class="text-xs font-semibold text-slate-600">Status</span>
                        <div class="flex rounded-lg border border-slate-200 bg-slate-50 p-0.5" role="group" aria-label="Filter by status">
                            <?php
                            $st = (string)($status_filter ?? '');
                            ?>
                            <label class="flex-1 cursor-pointer rounded-md px-2 py-2 text-center text-xs font-medium transition <?= $st === '' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600 hover:text-slate-900' ?>">
                                <input type="radio" name="status_filter" value="" class="sr-only" <?= $st === '' ? 'checked' : '' ?>> All
                            </label>
                            <label class="flex-1 cursor-pointer rounded-md px-2 py-2 text-center text-xs font-medium transition <?= $st === '1' ? 'bg-white text-emerald-800 shadow-sm ring-1 ring-emerald-200' : 'text-slate-600 hover:text-slate-900' ?>">
                                <input type="radio" name="status_filter" value="1" class="sr-only" <?= $st === '1' ? 'checked' : '' ?>> Active
                            </label>
                            <label class="flex-1 cursor-pointer rounded-md px-2 py-2 text-center text-xs font-medium transition <?= $st === '0' ? 'bg-white text-slate-700 shadow-sm ring-1 ring-slate-200' : 'text-slate-600 hover:text-slate-900' ?>">
                                <input type="radio" name="status_filter" value="0" class="sr-only" <?= $st === '0' ? 'checked' : '' ?>> Inactive
                            </label>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="inline-flex min-h-[42px] items-center justify-center gap-2 rounded-lg bg-slate-800 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-900 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:ring-offset-2">
                            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                            Apply filters
                        </button>
                        <a href="?page=modules&amp;action=list" class="inline-flex min-h-[42px] items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Clear all</a>
                    </div>
                    <?php if ($has_list_filters): ?>
                        <p class="text-xs text-slate-600">
                            <span class="font-medium text-slate-700">Applied:</span>
                            <?php if (($search ?? '') !== ''): ?>
                                <?php
                                $__q = (string)($search ?? '');
                                $__short = strlen($__q) > 48 ? substr($__q, 0, 48) . '…' : $__q;
                                ?>
                                <span class="ml-1 inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 font-medium text-indigo-900 ring-1 ring-indigo-100">“<?= htmlspecialchars($__short, ENT_QUOTES, 'UTF-8') ?>”</span>
                            <?php endif; ?>
                            <?php if ($parent_filter !== ''): ?>
                                <span class="ml-1 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-800 ring-1 ring-slate-200/80"><?= htmlspecialchars($parent_filter_summary !== '' ? $parent_filter_summary : 'Parent #' . $parent_filter, ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                            <?php if (($status_filter ?? '') === '1'): ?>
                                <span class="ml-1 inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-medium text-emerald-900 ring-1 ring-emerald-100">Active</span>
                            <?php elseif (($status_filter ?? '') === '0'): ?>
                                <span class="ml-1 inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 font-medium text-slate-800 ring-1 ring-slate-200/80">Inactive</span>
                            <?php endif; ?>
                            <a class="ml-2 text-indigo-600 underline decoration-indigo-200 underline-offset-2 hover:text-indigo-800" href="?page=modules&amp;action=list">Reset</a>
                        </p>
                    <?php endif; ?>
                </div>
            </form>
        </div>
        <button type="button" class="shrink-0 h-[48px] px-5 rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-sm font-semibold inline-flex items-center justify-center gap-2 shadow-md transition self-start lg:self-stretch lg:h-auto min-w-[140px]" id="open-vendor-popup-btn" title="Add a new module">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Add module
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-md border border-slate-100 overflow-hidden">
        <div class="p-0 sm:p-0">
            <div id="deleteMsgBox" class="fixed inset-0 flex items-center justify-center bg-black/50 hidden z-50" role="alertdialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="showMessage">
                <div class="bg-white rounded-xl shadow-xl w-[min(100%,400px)] mx-4 p-8 text-center">
                    <h2 id="modalTitle" class="text-lg font-semibold text-emerald-600 mb-3">Alert</h2>
                    <p id="showMessage" class="text-slate-600 text-sm"></p>
                    <div class="mt-6">
                        <button type="button" onclick="closeDeleteModal()" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 text-white px-5 py-2.5 text-sm font-medium hover:bg-emerald-700 transition">OK</button>
                    </div>
                </div>
            </div>
            <div class="overflow-x-auto table-container">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50/90">
                    <tr>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text w-12">#</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text min-w-[8rem]">Parent</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text min-w-[10rem]">Display name</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text min-w-[7rem]">Page</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text min-w-[6rem]">Action</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-center text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text w-24">Icon</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-left text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text w-28">Status</th>
                        <th scope="col" class="px-4 sm:px-6 py-3.5 text-right text-xs font-semibold text-slate-600 uppercase tracking-wider table-header-text w-20">Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                    <?php if (!empty($modules_data)): ?>
                        <?php foreach ($modules_data as $index => $tc): ?>
                            <?php
                            $isActive = (int)($tc['active'] ?? 0) === 1;
                            $slug = trim((string)($tc['slug'] ?? ''));
                            $actionName = trim((string)($tc['action'] ?? ''));
                            $parentDisplay = trim((string)($tc['parent_display_name'] ?? ''));
                            $iconHtml = trim((string)($tc['font_awesome_icon'] ?? ''));
                            ?>
                            <tr class="table-content-text transition hover:bg-slate-50/80">
                                <td class="px-4 sm:px-6 py-3.5 whitespace-nowrap text-sm text-slate-500 tabular-nums"><?= (int) $index + 1 ?></td>
                                <td class="px-4 sm:px-6 py-3.5 text-sm text-slate-700 max-w-[14rem]">
                                    <?php if ($parentDisplay !== ''): ?>
                                        <span class="line-clamp-2 break-words" title="<?= htmlspecialchars($parentDisplay, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($parentDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400" title="Top-level menu (no parent)">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-sm font-medium text-slate-900 min-w-[10rem] max-w-xs break-words"><?= htmlspecialchars($tc['module_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-4 sm:px-6 py-3.5 text-sm font-mono text-slate-800">
                                    <?= $slug !== '' ? htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') : '<span class="text-slate-400">—</span>' ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-sm text-slate-800">
                                    <?= $actionName !== '' ? htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8') : '<span class="text-slate-400">—</span>' ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 text-center align-middle">
                                    <?php if ($iconHtml !== ''): ?>
                                        <span class="inline-flex items-center justify-center text-slate-600 text-lg leading-none [&_i]:text-base" aria-hidden="true"><?= $iconHtml ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 whitespace-nowrap">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-600/15">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600 ring-1 ring-inset ring-slate-500/10">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3.5 whitespace-nowrap text-sm font-medium text-right">
                                    <div class="menu-wrapper inline-flex justify-end">
                                        <button type="button" class="menu-button rounded-md text-slate-500 hover:bg-slate-100 hover:text-slate-800" onclick="toggleMenu(this)" aria-haspopup="true" aria-expanded="false" aria-label="Row actions">
                                            &#x22EE;
                                        </button>
                                        <ul class="menu-popup text-left">
                                            <li onclick="openEditModal(<?= (int) $tc['id'] ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <li class="delete-btn text-red-700" data-id="<?php echo (int) $tc['id']; ?>"><i class="fa-solid fa-trash"></i> Delete</li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center gap-2 text-slate-500">
                                    <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    <p class="text-sm font-medium text-slate-700">No modules match your filters</p>
                                    <p class="text-xs text-slate-500 max-w-sm">Try another search or clear filters. Add a module with the button above.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php
        $total_pages = $limit > 0 ? (int) ceil($total_records / $limit) : 1;
        if ($total_pages < 1) {
            $total_pages = 1;
        }
        $pagination_base = modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', []);
    ?>
	<?php if ($total_records > 0): ?>
        <div class="bg-white rounded-xl shadow-md border border-slate-100 p-4">
            <div class="flex flex-col sm:flex-row flex-wrap items-center justify-between gap-4 text-sm text-slate-600">
                <p class="tabular-nums order-2 sm:order-1">
                    Showing <span class="font-medium text-slate-800"><?= (int) $range_from ?></span>–<span class="font-medium text-slate-800"><?= (int) $range_to ?></span> of <span class="font-medium text-slate-800"><?= (int) $total_records ?></span>
                </p>
                <div class="flex flex-wrap items-center justify-center gap-3 order-1 sm:order-2">
                    <?php if ($total_pages > 1): ?>
                    <span class="text-slate-500 hidden sm:inline">Page</span>
                    <button type="button" class="p-2 rounded-full hover:bg-slate-100 disabled:opacity-40 disabled:pointer-events-none <?= $page_no <= 1 ? 'opacity-40 pointer-events-none' : '' ?>">
                        <a class="page-link block text-slate-700" <?php if ($page_no > 1): ?>href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => $page_no - 1, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"<?php else: ?>href="#" aria-disabled="true"<?php endif ?> tabindex="-1" title="Previous page">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                    </button>
                    <span id="page-number" class="inline-flex items-center justify-center min-w-[2rem] h-8 px-2 rounded-full bg-slate-900 text-white text-sm font-semibold shadow-sm tabular-nums"><?= (int) $page_no ?></span>
                    <button type="button" class="p-2 rounded-full hover:bg-slate-100 <?= $page_no >= $total_pages ? 'opacity-40 pointer-events-none' : '' ?>">
                        <a class="page-link block text-slate-700" <?php if ($page_no < $total_pages): ?>href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => $page_no + 1, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"<?php else: ?>href="#" aria-disabled="true"<?php endif ?> tabindex="-1" title="Next page">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </button>
                    <?php endif; ?>
                    <div class="relative flex items-center gap-2 border-l border-slate-200 pl-3 ml-1">
                        <label for="rows-per-page" class="text-slate-500 whitespace-nowrap">Rows</label>
                        <select id="rows-per-page" name="limit" class="custom-select rounded-lg border border-slate-300 text-slate-900 text-sm py-1.5 pl-2 pr-8 focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 bg-white min-w-[4.5rem]" data-query-base="<?= htmlspecialchars($pagination_base, ENT_QUOTES, 'UTF-8') ?>" onchange="location.href='?' + this.getAttribute('data-query-base') + '&page_no=1&limit=' + encodeURIComponent(this.value);">
                            <?php foreach ([5, 20, 50, 100] as $opt): ?>
                                <option value="<?= (int) $opt ?>" <?= (int) $opt === (int) $limit ? 'selected' : '' ?>><?= (int) $opt ?></option>
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
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add Module</h2>
                    <div id="addVendorMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="addVendorForm">
                        <div class="pt-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Parent Menu <span class="text-red-500">*</span></label>
                                <select style="width: 100%; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="addParentMenu" id="addParentMenu" required>
                                    <option value="0" selected>-- Select Parent Menu --</option>
                                    <?php foreach ($parent_menus as $pmenu): ?>
                                        <option value="<?= htmlspecialchars($pmenu['id']) ?>"><?= htmlspecialchars($pmenu['module_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Module Name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="addModuleName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Slug <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="addSlug" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Action <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="addAction" value="list" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Font Awesome Icon</label>
                                <input type="text" class="form-input w-full mt-1 required" name="addFontAwesomeIcon" placeholder="fa fa-clipboard-list" />
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
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit Module</h2>
                    <div id="editVendorMsg" style="margin-top:10px;"></div>
                    <form id="editUserForm">
                        <input type="hidden" id="editId" name="id" value="">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Parent Menu <span class="text-red-500">*</span></label>
                                <select style="width: 100%; height: 37px; border-radius: 5px;" class="custom-select border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-400 focus:border-indigo-400 transition bg-white" name="editParentMenu" id="editParentMenu" required>
                                    <option value="0" selected>-- Select Parent Menu --</option>
                                    <?php foreach ($parent_menus as $pmenu): ?>
                                        <option value="<?= htmlspecialchars($pmenu['id']) ?>"><?= htmlspecialchars($pmenu['module_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Module Name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="editModuleName" id="editModuleName" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Slug <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="editSlug" id="editSlug" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Action <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" required name="editAction" id="editAction" />
                            </div>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Font Awesome Icon <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1 required" name="editFontAwesomeIcon" id="editFontAwesomeIcon" />
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
    const requiredFields = document.querySelectorAll('.required');
    // Optional: Auto-trim leading spaces on input
    requiredFields.forEach(field => {
        field.addEventListener('input', function() {
            if (this.value.charAt(0) === ' ') {
                this.value = this.value.trimStart(); // Remove leading spaces
            }
        });
    });

    // List filters: changing parent or status reloads results (search uses Apply / Enter).
    (function () {
        const listForm = document.getElementById('filterForm');
        if (!listForm) return;
        const parentSel = document.getElementById('parent_filter');
        if (parentSel) {
            parentSel.addEventListener('change', function () {
                listForm.requestSubmit();
            });
        }
        listForm.querySelectorAll('input[name="status_filter"]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                listForm.requestSubmit();
            });
        });
    })();

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
        fetch('?page=modules&action=addRecord', {
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

                fetch("?page=modules&action=deleteRecord", {
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
        fetch("?page=modules&action=getDetails&id=" + id)
        .then(res => res.json())
        .then(data => {
            const datas = typeof data === 'string' ? JSON.parse(data) : data;
            if (datas.status === "error") {
                alert(datas.message);
                return;
            }
            // Populate form fields data
            document.getElementById("editId").value   = datas.id;
            document.getElementById("editParentMenu").value   = datas.parent_id;
            document.getElementById("editModuleName").value   = datas.module_name;
            document.getElementById("editSlug").value   = datas.slug;
            document.getElementById("editAction").value   = datas.action;
            document.getElementById("editFontAwesomeIcon").value = (datas.font_awesome_icon !== null) ? unescapeString(datas.font_awesome_icon) : '';
            document.getElementById("editStatus").value = datas.active;

            popupWrapperEdit.classList.remove('hidden');
            setTimeout(() => {
                modalSliderEdit.classList.remove('translate-x-full');
            }, 10);
        });
    }
    function unescapeString(str) {
        if (typeof str !== 'string') {
            return str; // Return as-is if not a string
        }
        return str
            .replace(/\\'/g, "'")       // Unescape single quote
            .replace(/\\"/g, '"')       // Unescape double quote
            .replace(/\\\\/g, '\\')     // Unescape backslash (must be done last to avoid double-unescaping)
            .replace(/\\n/g, '\n')      // Unescape newline
            .replace(/\\r/g, '\r')      // Unescape carriage return
            .replace(/\\x00/g, '\x00')  // Unescape null byte
            .replace(/\\x1a/g, '\x1a'); // Unescape control-Z
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
        fetch('?page=modules&action=addRecord', {
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
                    window.location.href = '?page=modules&action=list';
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