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
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <!-- Page header (aligned with stock transfer history) -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-layer-group text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Portal · Navigation</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Menu <span class="text-amber-800">modules</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Configure vendor portal menu entries, URL slugs, route actions, and sidebar icons—in one place.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center">
                <button type="button" id="open-vendor-popup-btn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50/50 transition whitespace-nowrap w-full sm:w-auto"
                    title="Add a new module">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add module
                </button>
            </div>
        </div>
    </div>

    <style>
        #modules-list-filters > summary { list-style: none; }
        #modules-list-filters > summary::-webkit-details-marker { display: none; }
        #modules-list-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
        #modules-list-filters:not([open]) .mlf-label-open { display: none; }
        #modules-list-filters[open] .mlf-label-closed { display: none; }
        #modules-list-filters[open] .mlf-chevron { transform: rotate(180deg); }
    </style>
    <details id="modules-list-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]" <?= $has_list_filters ? 'open' : ''; ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Match name, slug, action, or parent. Parent and status apply on change; use Apply or Enter for text search.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
                <span class="mlf-label-closed">Show</span>
                <span class="mlf-label-open">Hide</span>
                <i class="mlf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>
        <form method="get" id="filterForm" class="p-5" autocomplete="off">
            <input type="hidden" name="page" value="modules">
            <input type="hidden" name="action" value="list">
            <input type="hidden" name="page_no" value="1">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-x-5 gap-y-4 lg:items-end">
                <div class="sm:col-span-2 lg:col-span-5">
                    <label for="modules_search" class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                            <i class="fas fa-search text-sm opacity-80" aria-hidden="true"></i>
                        </span>
                        <input id="modules_search" type="search" name="search_text" enterkeyhint="search"
                            placeholder="Name, slug, action, parent…"
                            class="w-full min-h-[42px] rounded-lg border border-gray-300 bg-white pl-10 pr-3 text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                            value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="lg:col-span-4">
                    <label for="parent_filter" class="block text-xs font-semibold text-gray-600 mb-1">Parent menu</label>
                    <select id="parent_filter" name="parent_filter" class="w-full min-h-[42px] px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="" <?= $parent_filter === '' ? 'selected' : '' ?>>All modules</option>
                        <option value="0" <?= $parent_filter === '0' ? 'selected' : '' ?>>Top-level only</option>
                        <?php if (!empty($parent_menus)): ?>
                            <?php foreach ($parent_menus as $pmenu): ?>
                                <option value="<?= (int) $pmenu['id'] ?>" <?= ((string) $parent_filter === (string) $pmenu['id']) ? 'selected' : '' ?>><?= htmlspecialchars($pmenu['module_name'], ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="lg:col-span-3">
                    <label for="status_filter" class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <?php $st = (string)($status_filter ?? ''); ?>
                    <select id="status_filter" name="status_filter" class="w-full min-h-[42px] px-3 rounded-lg border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" aria-label="Filter by status">
                        <option value="" <?= $st === '' ? 'selected' : '' ?>>All statuses</option>
                        <option value="1" <?= $st === '1' ? 'selected' : '' ?>>Active</option>
                        <option value="0" <?= $st === '0' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <a href="?page=modules&amp;action=list" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
                <?php if ($has_list_filters): ?>
                    <?php
                    $__q = (string)($search ?? '');
                    $__short = strlen($__q) > 48 ? substr($__q, 0, 48) . '…' : $__q;
                    ?>
                    <span class="text-xs text-gray-600 flex flex-wrap items-center gap-x-2 gap-y-1">
                        <span class="font-medium text-gray-700">Applied:</span>
                        <?php if (($search ?? '') !== ''): ?>
                            <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 font-medium text-amber-950 ring-1 ring-amber-100">“<?= htmlspecialchars($__short, ENT_QUOTES, 'UTF-8') ?>”</span>
                        <?php endif; ?>
                        <?php if ($parent_filter !== ''): ?>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-medium text-gray-800 ring-1 ring-gray-200/80"><?= htmlspecialchars($parent_filter_summary !== '' ? $parent_filter_summary : 'Parent #' . $parent_filter, ENT_QUOTES, 'UTF-8') ?></span>
                        <?php endif; ?>
                        <?php if (($status_filter ?? '') === '1'): ?>
                            <span class="inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 font-medium text-emerald-900 ring-1 ring-emerald-100">Active</span>
                        <?php elseif (($status_filter ?? '') === '0'): ?>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 font-medium text-gray-800 ring-1 ring-gray-200/80">Inactive</span>
                        <?php endif; ?>
                    </span>
                <?php endif; ?>
            </div>
        </form>
    </details>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
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
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap min-w-[8rem]">Parent</th>
                        <th scope="col" class="px-5 py-3.5 min-w-[10rem]">Display name</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap text-right w-24" title="Menu order within parent">Sort order</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap min-w-[7rem]">Page</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap min-w-[6rem]">Action</th>
                        <th scope="col" class="px-5 py-3.5 text-center w-24">Icon</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap w-28">Status</th>
                        <th scope="col" class="px-5 py-3.5 text-right w-20"><span class="sr-only">Actions</span></th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($modules_data)): ?>
                        <?php foreach ($modules_data as $index => $tc): ?>
                            <?php
                            $isActive = (int)($tc['active'] ?? 0) === 1;
                            $slug = trim((string)($tc['slug'] ?? ''));
                            $actionName = trim((string)($tc['action'] ?? ''));
                            $parentDisplay = trim((string)($tc['parent_display_name'] ?? ''));
                            $iconHtml = trim((string)($tc['font_awesome_icon'] ?? ''));
                            $sortOrder = array_key_exists('sort_order', $tc) ? (int) $tc['sort_order'] : null;
                            ?>
                            <tr class="table-content-text hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 align-top text-sm text-gray-600 tabular-nums whitespace-nowrap"><?= (int) $index + 1 ?></td>
                                <td class="px-5 py-4 align-top text-sm text-gray-700 max-w-[14rem]">
                                    <?php if ($parentDisplay !== ''): ?>
                                        <span class="line-clamp-2 break-words" title="<?= htmlspecialchars($parentDisplay, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($parentDisplay, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400" title="Top-level menu (no parent)">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-semibold text-gray-900 min-w-[10rem] max-w-xs break-words"><?= htmlspecialchars($tc['module_name'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 align-top text-sm text-gray-800 tabular-nums text-right whitespace-nowrap">
                                    <?php if ($sortOrder !== null): ?>
                                        <span class="inline-flex min-w-[2rem] justify-end font-medium text-gray-900"><?= $sortOrder ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm font-mono text-gray-900">
                                    <?= $slug !== '' ? htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') : '<span class="text-gray-400">—</span>' ?>
                                </td>
                                <td class="px-5 py-4 align-top text-sm text-gray-800">
                                    <?= $actionName !== '' ? htmlspecialchars($actionName, ENT_QUOTES, 'UTF-8') : '<span class="text-gray-400">—</span>' ?>
                                </td>
                                <td class="px-5 py-4 align-middle text-center">
                                    <?php if ($iconHtml !== ''): ?>
                                        <span class="inline-flex items-center justify-center text-gray-600 text-lg leading-none [&_i]:text-base" aria-hidden="true"><?= $iconHtml ?></span>
                                    <?php else: ?>
                                        <span class="text-gray-400 text-sm">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-top whitespace-nowrap">
                                    <?php if ($isActive): ?>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset bg-amber-50 text-amber-900 ring-amber-600/25">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-500/20">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 align-middle text-right whitespace-nowrap text-sm font-medium">
                                    <div class="menu-wrapper inline-flex justify-end">
                                        <button type="button" class="menu-button rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-800" onclick="toggleMenu(this)" aria-haspopup="true" aria-expanded="false" aria-label="Row actions">
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
                            <td colspan="9" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No modules match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try adjusting filters or add a module.</p>
                                    <button type="button" onclick="document.getElementById('open-vendor-popup-btn')?.click()" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-amber-700 hover:text-amber-800">
                                        <i class="fas fa-plus-circle" aria-hidden="true"></i>
                                        New module
                                    </button>
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
        <div class="mt-6 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm">
            <p class="text-sm text-gray-600 tabular-nums">
                Showing <span class="font-medium text-gray-900"><?= (int) $range_from ?></span>
                –
                <span class="font-medium text-gray-900"><?= (int) $range_to ?></span>
                of <span class="font-medium text-gray-900"><?= number_format((int) $total_records) ?></span>
            </p>
            <div class="flex flex-col sm:flex-row flex-wrap items-stretch sm:items-center gap-3 sm:gap-4">
                <?php if ($total_pages > 1): ?>
                <nav class="flex flex-wrap items-center gap-2" aria-label="Pagination">
                    <a href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => 1, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $page_no <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">First</a>
                    <a href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => max(1, $page_no - 1), 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $page_no <= 1 ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Previous</a>
                    <span class="px-3 py-1.5 text-sm text-gray-700 tabular-nums">Page <?= (int) $page_no ?> / <?= (int) $total_pages ?></span>
                    <a href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => min($total_pages, $page_no + 1), 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $page_no >= $total_pages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Next</a>
                    <a href="?<?= htmlspecialchars(modules_list_query($search ?? '', $status_filter ?? '', $parent_filter ?? '', ['page_no' => $total_pages, 'limit' => $limit]), ENT_QUOTES, 'UTF-8') ?>"
                        class="px-3 py-1.5 rounded-lg border text-sm font-medium transition <?= $page_no >= $total_pages ? 'pointer-events-none opacity-40 border-gray-200 text-gray-400' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' ?>">Last</a>
                </nav>
                <?php endif; ?>
                <div class="flex items-center gap-2 border-t border-gray-100 pt-3 sm:border-t-0 sm:pt-0 sm:border-l sm:border-gray-200 sm:pl-4">
                    <label for="rows-per-page" class="text-xs font-semibold text-gray-600 whitespace-nowrap">Rows per page</label>
                    <select id="rows-per-page" name="limit" class="rounded-lg border border-gray-300 bg-white text-gray-900 text-sm py-1.5 pl-2 pr-8 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 min-w-[4.5rem]" data-query-base="<?= htmlspecialchars($pagination_base, ENT_QUOTES, 'UTF-8') ?>" onchange="location.href='?' + this.getAttribute('data-query-base') + '&page_no=1&limit=' + encodeURIComponent(this.value);">
                        <?php foreach ([5, 20, 50, 100] as $opt): ?>
                            <option value="<?= (int) $opt ?>" <?= (int) $opt === (int) $limit ? 'selected' : '' ?>><?= (int) $opt ?></option>
                        <?php endforeach; ?>
                    </select>
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
								<label class="text-sm font-medium text-gray-700" for="addSortOrder">Sort order</label>
								<input type="number" class="form-input w-full mt-1" name="addSortOrder" id="addSortOrder" min="0" step="1" value="0" placeholder="0" />
								<p class="text-xs text-gray-500 mt-1 leading-snug">Within the same parent, lower numbers list first.</p>
							</div>
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
								<label class="text-sm font-medium text-gray-700" for="editSortOrder">Sort order</label>
								<input type="number" class="form-input w-full mt-1" name="editSortOrder" id="editSortOrder" min="0" step="1" value="0" placeholder="0" />
								<p class="text-xs text-gray-500 mt-1 leading-snug">Within the same parent, lower numbers list first.</p>
							</div>
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
        const statusSel = document.getElementById('status_filter');
        if (statusSel) {
            statusSel.addEventListener('change', function () {
                listForm.requestSubmit();
            });
        }
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
            var soEl = document.getElementById("editSortOrder");
            if (soEl) {
                soEl.value = (datas.sort_order !== undefined && datas.sort_order !== null && datas.sort_order !== '') ? String(datas.sort_order) : '0';
            }

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