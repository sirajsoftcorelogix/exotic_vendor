<?php
function category_nav_url(array $params = []): string
{
    $defaults = [
        'page' => 'category',
        'action' => 'list',
        'search_text' => $_GET['search_text'] ?? '',
        'sort_by' => $_GET['sort_by'] ?? 'category',
        'sort_dir' => $_GET['sort_dir'] ?? 'ASC',
        'page_no' => 1,
        'limit' => $_GET['limit'] ?? 20,
    ];
    $merged = array_merge($defaults, $params);
    // Remove empty parameters
    return 'index.php?' . http_build_query(array_filter($merged, function ($val) {
        return $val !== '' && $val !== null;
    }));
}

function category_sort_url(string $column, string $currentSortBy, string $currentSortDir): string
{
    $newDir = ($currentSortBy === $column && strtoupper($currentSortDir) === 'ASC') ? 'DESC' : 'ASC';
    return category_nav_url([
        'sort_by' => $column,
        'sort_dir' => $newDir,
        'page_no' => 1,
    ]);
}

$currentPage = isset($currentPage) ? (int) $currentPage : 1;
$totalPages = isset($totalPages) ? (int) $totalPages : 1;
$totalRecords = isset($totalRecords) ? (int) $totalRecords : 0;
$limit = isset($limit) ? (int) $limit : 20;
$search = isset($search) ? (string) $search : '';
$sortBy = isset($sortBy) ? (string) $sortBy : 'category';
$sortDir = isset($sortDir) ? (string) $sortDir : 'ASC';

$slot_size = 10;
$start_page = max(1, $currentPage - (int) floor($slot_size / 2));
$end_page = min($totalPages, $start_page + $slot_size - 1);
if ($end_page - $start_page < $slot_size - 1) {
    $start_page = max(1, $end_page - $slot_size + 1);
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <!-- Header Banner -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-sitemap text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Masters · Categories</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Category <span class="text-amber-800">Master</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Categories are maintained by Exotic India and synchronized from the Vendor API. Use this module to view, edit, or pull the latest categories.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <button type="button" id="pull-categories-btn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-cloud-download-alt text-xs opacity-95" id="pull-btn-icon" aria-hidden="true"></i>
                    <span id="pull-btn-text">Pull Latest Categories</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Search & Toolbar Grid -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center justify-between gap-3 min-w-0 flex-wrap">
                <div class="flex items-center gap-3 min-w-0">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                        <i class="fas fa-search text-sm" aria-hidden="true"></i>
                    </span>
                    <div class="min-w-0">
                        <h2 class="text-sm font-semibold text-gray-900">Search &amp; Toolbar</h2>
                        <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Filter by Category ID, Name, or Display Name.</p>
                    </div>
                </div>
            </div>
        </div>

        <form method="get" action="index.php" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="category">
            <input type="hidden" name="action" value="list">
            <input type="hidden" name="sort_by" value="<?= htmlspecialchars($sortBy) ?>">
            <input type="hidden" name="sort_dir" value="<?= htmlspecialchars($sortDir) ?>">

            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-4 gap-x-5 gap-y-4 items-end">
                <div class="sm:col-span-2 lg:col-span-3">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search Box</label>
                    <div class="relative">
                        <input type="text" name="search_text" id="search_text_input" placeholder="Search by Category ID, Name, or Display Name..."
                            class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                            value="<?= htmlspecialchars($search) ?>" autocomplete="off">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <i class="fas fa-search text-xs"></i>
                        </div>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Per Page</label>
                    <select name="limit" onchange="this.form.submit()"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="10" <?= $limit === 10 ? 'selected' : '' ?>>10 records</option>
                        <option value="20" <?= $limit === 20 ? 'selected' : '' ?>>20 records</option>
                        <option value="50" <?= $limit === 50 ? 'selected' : '' ?>>50 records</option>
                        <option value="100" <?= $limit === 100 ? 'selected' : '' ?>>100 records</option>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Search
                </button>
                <button type="button" id="refresh-grid-btn"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="window.location.href='index.php?page=category&action=list'">
                    <i class="fas fa-sync-alt text-xs text-gray-500"></i>
                    Refresh Grid
                </button>
            </div>
        </form>
    </div>

    <!-- Category Data Table -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left divide-y divide-gray-200">
                <thead>
                    <tr class="bg-gray-50/95 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-6 py-3.5 w-16">ID</th>
                        <th scope="col" class="px-6 py-3.5">
                            <a href="<?= category_sort_url('category', $sortBy, $sortDir) ?>" class="inline-flex items-center gap-1.5 hover:text-amber-800 transition">
                                <span>Category ID</span>
                                <?php if ($sortBy === 'category'): ?>
                                    <i class="fas fa-sort-<?= strtolower($sortDir) === 'asc' ? 'up' : 'down' ?> text-amber-700"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort text-gray-400 opacity-50"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3.5">
                            <a href="<?= category_sort_url('name', $sortBy, $sortDir) ?>" class="inline-flex items-center gap-1.5 hover:text-amber-800 transition">
                                <span>Name</span>
                                <?php if ($sortBy === 'name'): ?>
                                    <i class="fas fa-sort-<?= strtolower($sortDir) === 'asc' ? 'up' : 'down' ?> text-amber-700"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort text-gray-400 opacity-50"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3.5">
                            <a href="<?= category_sort_url('display_name', $sortBy, $sortDir) ?>" class="inline-flex items-center gap-1.5 hover:text-amber-800 transition">
                                <span>Display Name</span>
                                <?php if ($sortBy === 'display_name'): ?>
                                    <i class="fas fa-sort-<?= strtolower($sortDir) === 'asc' ? 'up' : 'down' ?> text-amber-700"></i>
                                <?php else: ?>
                                    <i class="fas fa-sort text-gray-400 opacity-50"></i>
                                <?php endif; ?>
                            </a>
                        </th>
                        <th scope="col" class="px-6 py-3.5">Parent</th>
                        <th scope="col" class="px-6 py-3.5 w-24">Initial</th>
                        <th scope="col" class="px-6 py-3.5 w-24">Active</th>
                        <th scope="col" class="px-6 py-3.5 text-right w-36">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white text-sm">
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $row): ?>
                            <tr class="hover:bg-amber-50/30 transition-colors">
                                <td class="px-6 py-4 font-mono text-xs text-gray-500">
                                    #<?= htmlspecialchars((string)$row['id']) ?>
                                </td>
                                <td class="px-6 py-4 font-semibold text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200">
                                        <?= htmlspecialchars((string)($row['category'] ?? '')) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-gray-900 font-medium">
                                    <?= htmlspecialchars((string)($row['name'] ?? '')) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-700">
                                    <?= htmlspecialchars((string)($row['display_name'] ?? '')) ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600">
                                    <?= htmlspecialchars((string)($row['parent'] ?? '')) ?>
                                    <?php if (!empty($row['parent_id'])): ?>
                                        <span class="text-xs text-gray-400 font-mono">(ID: <?= (int)$row['parent_id'] ?>)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-gray-600 font-mono text-xs">
                                    <?= htmlspecialchars((string)($row['initial'] ?? '')) ?>
                                </td>
                                <td class="px-6 py-4">
                                    <?php if ((int)($row['is_active'] ?? 1) === 1): ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            Yes
                                        </span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-rose-100 text-rose-800 border border-rose-200">
                                            No
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1.5">
                                        <button type="button" onclick="openEditModal(<?= (int)$row['id'] ?>)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-gray-300 bg-white text-xs font-medium text-gray-700 hover:bg-amber-50 hover:text-amber-800 hover:border-amber-300 transition"
                                            title="Edit Category">
                                            <i class="fas fa-edit text-xs"></i>
                                            <span>Edit</span>
                                        </button>
                                        <button type="button" onclick="confirmDeleteCategory(<?= (int)$row['id'] ?>, '<?= htmlspecialchars(addslashes($row['name'] ?? ''), ENT_QUOTES) ?>')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg border border-rose-200 bg-white text-xs font-medium text-rose-700 hover:bg-rose-50 hover:border-rose-300 transition"
                                            title="Delete Category">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                            <span>Delete</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center justify-center space-y-3">
                                    <div class="h-12 w-12 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                                        <i class="fas fa-sitemap text-xl"></i>
                                    </div>
                                    <p class="text-base font-semibold text-gray-900">No categories found</p>
                                    <p class="text-sm text-gray-500 max-w-sm">Click "Pull Latest Categories" in the toolbar above to synchronize categories from Exotic India.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination Bar -->
        <?php if ($totalRecords > 0): ?>
            <div class="px-6 py-4 bg-gray-50/80 border-t border-gray-200 flex flex-col sm:flex-row items-center justify-between gap-4 text-xs text-gray-600">
                <div>
                    Showing <span class="font-semibold text-gray-900"><?= min(($currentPage - 1) * $limit + 1, $totalRecords) ?></span>
                    to <span class="font-semibold text-gray-900"><?= min($currentPage * $limit, $totalRecords) ?></span>
                    of <span class="font-semibold text-gray-900"><?= number_format($totalRecords) ?></span> entries
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="inline-flex items-center gap-1 flex-wrap">
                        <!-- First Page -->
                        <?php if ($currentPage > 1): ?>
                            <a href="<?= category_nav_url(['page_no' => 1]) ?>"
                                class="px-2.5 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 transition" title="First Page">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="<?= category_nav_url(['page_no' => $currentPage - 1]) ?>"
                                class="px-2.5 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 transition" title="Previous Page">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php endif; ?>

                        <!-- Page Slot Numbers -->
                        <?php for ($p = $start_page; $p <= $end_page; $p++): ?>
                            <?php if ($p === $currentPage): ?>
                                <span class="px-3 py-1.5 rounded-md bg-amber-600 text-white font-semibold shadow-sm"><?= $p ?></span>
                            <?php else: ?>
                                <a href="<?= category_nav_url(['page_no' => $p]) ?>"
                                    class="px-3 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 transition"><?= $p ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Next / Last Page -->
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="<?= category_nav_url(['page_no' => $currentPage + 1]) ?>"
                                class="px-2.5 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 transition" title="Next Page">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="<?= category_nav_url(['page_no' => $totalPages]) ?>"
                                class="px-2.5 py-1.5 rounded-md border border-gray-300 bg-white hover:bg-gray-100 text-gray-700 transition" title="Last Page">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Synchronization Summary -->
<!-- ========================================== -->
<div id="syncSummaryModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-100 transform transition-all">
        <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-5 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 backdrop-blur-md">
                    <i class="fas fa-check-circle text-xl"></i>
                </span>
                <div>
                    <h3 class="text-lg font-bold">Synchronization Completed</h3>
                    <p class="text-xs text-amber-100">Category sync output summary</p>
                </div>
            </div>
            <button type="button" onclick="closeSyncSummaryModal()" class="text-white/80 hover:text-white transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <div class="p-6 space-y-4">
            <div class="bg-slate-50 border border-slate-200/80 rounded-xl p-5 font-mono text-sm space-y-2 text-slate-800">
                <div class="flex justify-between border-b border-slate-200 pb-2">
                    <span class="text-slate-500 font-sans">Categories received</span>
                    <span class="font-bold text-slate-900" id="summary-received">0</span>
                </div>
                <div class="flex justify-between border-b border-slate-200 pb-2">
                    <span class="text-slate-500 font-sans">Already Exists</span>
                    <span class="font-bold text-amber-700" id="summary-existing">0</span>
                </div>
                <div class="flex justify-between border-b border-slate-200 pb-2">
                    <span class="text-slate-500 font-sans">New Categories Added</span>
                    <span class="font-bold text-emerald-700" id="summary-added">0</span>
                </div>
                <div class="flex justify-between border-b border-slate-200 pb-2">
                    <span class="text-slate-500 font-sans">Failed</span>
                    <span class="font-bold text-rose-700" id="summary-failed">0</span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-slate-500 font-sans">Execution Time</span>
                    <span class="font-bold text-slate-900" id="summary-time">0.00 Seconds</span>
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end">
            <button type="button" onclick="closeSyncSummaryModal()"
                class="px-5 py-2.5 rounded-xl bg-amber-600 text-white font-semibold text-sm hover:bg-amber-700 transition shadow-sm">
                Close &amp; Refresh Grid
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Edit Category -->
<!-- ========================================== -->
<div id="editCategoryModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-xl w-full overflow-hidden border border-gray-100">
        <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4 text-white flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-edit text-lg"></i>
                <h3 class="text-base font-bold">Edit Category</h3>
            </div>
            <button type="button" onclick="closeEditModal()" class="text-white/80 hover:text-white transition">
                <i class="fas fa-times text-lg"></i>
            </button>
        </div>

        <form id="editCategoryForm" onsubmit="submitCategoryEdit(event)" class="p-6 space-y-4">
            <input type="hidden" name="id" id="edit_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Category ID (API)</label>
                    <input type="text" id="edit_category_api" readonly
                        class="w-full px-3 py-2 bg-gray-100 border border-gray-300 rounded-lg text-sm text-gray-600 font-mono font-semibold cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Parent ID</label>
                    <input type="number" name="parent_id" id="edit_parent_id"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Name <span class="text-rose-500">*</span></label>
                <input type="text" name="name" id="edit_name" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Display Name</label>
                <input type="text" name="display_name" id="edit_display_name"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Parent Name</label>
                    <input type="text" name="parent" id="edit_parent"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">Initial (Max 3 chars)</label>
                    <input type="text" name="initial" id="edit_initial" maxlength="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">Active Status</label>
                <select name="is_active" id="edit_is_active"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                    <option value="1">Active (Yes)</option>
                    <option value="0">Inactive (No)</option>
                </select>
            </div>

            <div class="pt-4 border-t border-gray-200 flex justify-end gap-3">
                <button type="button" onclick="closeEditModal()"
                    class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                    Cancel
                </button>
                <button type="submit" id="save-edit-btn"
                    class="px-5 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Delete Category Confirmation -->
<!-- ========================================== -->
<div id="deleteCategoryModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden border border-gray-100">
        <div class="p-6 text-center space-y-4">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600">
                <i class="fas fa-exclamation-triangle text-xl"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Delete Category</h3>
                <p class="text-sm text-gray-500 mt-1">Are you sure you want to delete category <strong id="delete-cat-name" class="text-gray-800"></strong>?</p>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 flex justify-end gap-3 border-t border-gray-100">
            <button type="button" onclick="closeDeleteModal()"
                class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 transition">
                Cancel
            </button>
            <button type="button" id="confirm-delete-btn" onclick="executeCategoryDelete()"
                class="px-5 py-2 bg-rose-600 text-white rounded-lg text-sm font-semibold hover:bg-rose-700 transition shadow-sm">
                Delete
            </button>
        </div>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL: Generic Alert Box -->
<!-- ========================================== -->
<div id="categoryAlertModal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm z-50 flex items-center justify-center hidden p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-sm w-full overflow-hidden border border-gray-100 p-6 text-center space-y-4">
        <div id="alert-modal-icon" class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600">
            <i class="fas fa-info-circle text-xl"></i>
        </div>
        <div>
            <h3 id="alert-modal-title" class="text-lg font-bold text-gray-900">Notice</h3>
            <p id="alert-modal-msg" class="text-sm text-gray-600 mt-1"></p>
        </div>
        <div class="pt-2">
            <button type="button" onclick="closeCategoryAlertModal()"
                class="px-5 py-2 bg-amber-600 text-white rounded-lg text-sm font-semibold hover:bg-amber-700 transition shadow-sm w-full">
                OK
            </button>
        </div>
    </div>
</div>

<script>
let deleteCategoryId = null;

function showCategoryAlert(title, message, isError = false) {
    const iconContainer = document.getElementById('alert-modal-icon');
    if (isError) {
        iconContainer.className = 'mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-rose-100 text-rose-600';
        iconContainer.innerHTML = '<i class="fas fa-exclamation-circle text-xl"></i>';
    } else {
        iconContainer.className = 'mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-600';
        iconContainer.innerHTML = '<i class="fas fa-info-circle text-xl"></i>';
    }
    document.getElementById('alert-modal-title').innerText = title || 'Notice';
    document.getElementById('alert-modal-msg').innerText = message || '';
    document.getElementById('categoryAlertModal').classList.remove('hidden');
}

function closeCategoryAlertModal() {
    document.getElementById('categoryAlertModal').classList.add('hidden');
}

// Pull Latest Categories AJAX
document.getElementById('pull-categories-btn').addEventListener('click', function () {
    const btn = this;
    const icon = document.getElementById('pull-btn-icon');
    const text = document.getElementById('pull-btn-text');

    btn.disabled = true;
    icon.className = 'fas fa-spinner fa-spin text-xs';
    text.innerText = 'Synchronizing...';

    fetch('index.php?page=category&action=pullCategories', {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
    .then(response => response.json())
    .then(data => {
        btn.disabled = false;
        icon.className = 'fas fa-cloud-download-alt text-xs opacity-95';
        text.innerText = 'Pull Latest Categories';

        if (data.success && data.summary) {
            document.getElementById('summary-received').innerText = data.summary.categories_received || 0;
            document.getElementById('summary-existing').innerText = data.summary.already_exists || 0;
            document.getElementById('summary-added').innerText = data.summary.new_categories_added || 0;
            document.getElementById('summary-failed').innerText = data.summary.failed || 0;
            document.getElementById('summary-time').innerText = data.summary.execution_time || '0.00 Seconds';

            document.getElementById('syncSummaryModal').classList.remove('hidden');
        } else {
            showCategoryAlert('Sync Failed', data.message || 'Unable to synchronize categories.', true);
        }
    })
    .catch(err => {
        btn.disabled = false;
        icon.className = 'fas fa-cloud-download-alt text-xs opacity-95';
        text.innerText = 'Pull Latest Categories';
        showCategoryAlert('Error', 'Network or server error during category synchronization: ' + err.message, true);
    });
});

function closeSyncSummaryModal() {
    document.getElementById('syncSummaryModal').classList.add('hidden');
    window.location.reload();
}

// Open Edit Modal
function openEditModal(id) {
    fetch('index.php?page=category&action=getDetails&id=' + id, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.category) {
            const cat = data.category;
            document.getElementById('edit_id').value = cat.id;
            document.getElementById('edit_category_api').value = cat.category || '';
            document.getElementById('edit_parent_id').value = cat.parent_id || 0;
            document.getElementById('edit_name').value = cat.name || '';
            document.getElementById('edit_display_name').value = cat.display_name || '';
            document.getElementById('edit_parent').value = cat.parent || '';
            document.getElementById('edit_initial').value = cat.initial || '';
            document.getElementById('edit_is_active').value = (cat.is_active !== undefined) ? cat.is_active : 1;

            document.getElementById('editCategoryModal').classList.remove('hidden');
        } else {
            showCategoryAlert('Error', data.message || 'Failed to fetch category details.', true);
        }
    })
    .catch(err => {
        showCategoryAlert('Error', 'Network error fetching category details: ' + err.message, true);
    });
}

function closeEditModal() {
    document.getElementById('editCategoryModal').classList.add('hidden');
}

function submitCategoryEdit(e) {
    e.preventDefault();
    const form = document.getElementById('editCategoryForm');
    const formData = new FormData(form);
    const saveBtn = document.getElementById('save-edit-btn');

    saveBtn.disabled = true;
    saveBtn.innerText = 'Saving...';

    fetch('index.php?page=category&action=edit', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save Changes';

        if (data.success) {
            closeEditModal();
            window.location.reload();
        } else {
            showCategoryAlert('Error', data.message || 'Failed to update category.', true);
        }
    })
    .catch(err => {
        saveBtn.disabled = false;
        saveBtn.innerText = 'Save Changes';
        showCategoryAlert('Error', 'Network error while updating category: ' + err.message, true);
    });
}

// Delete Confirmation
function confirmDeleteCategory(id, name) {
    fetch('index.php?page=category&action=checkUsage&id=' + id, {
        method: 'GET',
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.in_use) {
            showCategoryAlert('Cannot Delete Category', data.message, true);
        } else {
            deleteCategoryId = id;
            document.getElementById('delete-cat-name').innerText = name;
            document.getElementById('deleteCategoryModal').classList.remove('hidden');
        }
    })
    .catch(err => {
        // Fallback: open delete modal and let backend handle validation
        deleteCategoryId = id;
        document.getElementById('delete-cat-name').innerText = name;
        document.getElementById('deleteCategoryModal').classList.remove('hidden');
    });
}

function closeDeleteModal() {
    deleteCategoryId = null;
    document.getElementById('deleteCategoryModal').classList.add('hidden');
}

function executeCategoryDelete() {
    if (!deleteCategoryId) return;

    const btn = document.getElementById('confirm-delete-btn');
    btn.disabled = true;
    btn.innerText = 'Deleting...';

    fetch('index.php?page=category&action=delete', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: deleteCategoryId })
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerText = 'Delete';

        if (data.success) {
            closeDeleteModal();
            window.location.reload();
        } else {
            closeDeleteModal();
            showCategoryAlert('Error', data.message || 'Failed to delete category.', true);
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'Delete';
        closeDeleteModal();
        showCategoryAlert('Error', 'Network error while deleting category: ' + err.message, true);
    });
}
</script>
