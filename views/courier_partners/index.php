<?php
$flash = $_SESSION['courier_partner_flash'] ?? null;
if ($flash) {
    unset($_SESSION['courier_partner_flash']);
}
$rows = $rows ?? [];
$search = $search ?? '';
$statusFilter = $status_filter ?? '';
$currentPage = (int)($currentPage ?? 1);
$totalPages = (int)($totalPages ?? 1);
$limit = (int)($limit ?? 20);
$totalRecords = (int)($totalRecords ?? 0);
?>

<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-5">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-800">Courier Partner Master</h2>
        <p class="text-sm text-gray-500 mt-1">Create active courier partners (UPS, ARAMAX, DHL, FEDEX, BLUE DART, etc.) before account mapping.</p>
    </div>

    <?php if ($flash): ?>
        <div class="rounded-lg border px-4 py-3 text-sm <?php echo !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <?php echo htmlspecialchars((string)($flash['message'] ?? '')); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <form method="get" class="flex flex-wrap items-end gap-3">
            <input type="hidden" name="page" value="courier_partners">
            <input type="hidden" name="action" value="list">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Search</label>
                <input type="text" name="search_text" value="<?php echo htmlspecialchars($search); ?>" placeholder="Name or code" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-64">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="status_filter" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-44">
                    <option value="">All</option>
                    <option value="1" <?php echo $statusFilter === '1' ? 'selected' : ''; ?>>Active</option>
                    <option value="0" <?php echo $statusFilter === '0' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            <button type="submit" class="h-10 px-4 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold">Search</button>
            <a href="?page=courier_partners&action=list" class="h-10 px-4 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold inline-flex items-center">Clear</a>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Add New Partner</h3>
        <form method="post" action="?page=courier_partners&action=addRecord" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Partner Code</label>
                <input type="text" name="partner_code" maxlength="50" required placeholder="DHL" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Partner Name</label>
                <input type="text" name="partner_name" maxlength="120" required placeholder="DHL Express" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Domestic</label>
                <input type="checkbox" name="supports_domestic" value="1" checked class="h-4 w-4">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">International</label>
                <input type="checkbox" name="supports_international" value="1" checked class="h-4 w-4">
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="is_active" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full" placeholder="Optional notes"></textarea>
            </div>
            <div class="md:col-span-6">
                <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Add Partner</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Code</th>
                        <th class="px-4 py-3 text-left">Partner Name</th>
                        <th class="px-4 py-3 text-left">Domestic</th>
                        <th class="px-4 py-3 text-left">International</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="6" class="px-4 py-6 text-center text-gray-500">No courier partners found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                            <tr class="border-b border-gray-100 align-top">
                                <td class="px-4 py-3 font-semibold text-gray-800"><?php echo htmlspecialchars((string)$r['partner_code']); ?></td>
                                <td class="px-4 py-3"><?php echo htmlspecialchars((string)$r['partner_name']); ?></td>
                                <td class="px-4 py-3">
                                    <?php if ((int)$r['supports_domestic'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-800 shadow-sm ring-1 ring-inset ring-emerald-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3">
                                    <?php if ((int)$r['supports_international'] === 1): ?>
                                        <span class="inline-flex items-center rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-900 shadow-sm ring-1 ring-inset ring-sky-600/25">Yes</span>
                                    <?php else: ?>
                                        <span class="text-xs font-medium text-gray-400 tabular-nums">No</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3"><?php echo (int)$r['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2.5 min-w-[10rem]">
                                        <a href="?page=courier_accounts&amp;action=list&amp;partner_id=<?php echo (int) $r['id']; ?>"
                                            class="inline-flex items-center gap-1.5 text-sm font-semibold text-amber-800 hover:text-amber-950 hover:underline decoration-amber-800/30">
                                            <i class="fas fa-id-card-alt text-xs opacity-90" aria-hidden="true"></i>
                                            Manage accounts
                                        </a>
                                    <details>
                                        <summary class="cursor-pointer text-indigo-600">Edit</summary>
                                        <form method="post" action="?page=courier_partners&action=addRecord" class="mt-3 space-y-2 min-w-[280px]">
                                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                            <input type="text" name="partner_code" value="<?php echo htmlspecialchars((string)$r['partner_code']); ?>" required class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                            <input type="text" name="partner_name" value="<?php echo htmlspecialchars((string)$r['partner_name']); ?>" required class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                            <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="supports_domestic" value="1" <?php echo (int)$r['supports_domestic'] === 1 ? 'checked' : ''; ?>> Domestic</label>
                                            <label class="inline-flex items-center gap-2 text-xs ml-3"><input type="checkbox" name="supports_international" value="1" <?php echo (int)$r['supports_international'] === 1 ? 'checked' : ''; ?>> International</label>
                                            <select name="is_active" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                <option value="1" <?php echo (int)$r['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                                                <option value="0" <?php echo (int)$r['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                            </select>
                                            <textarea name="notes" rows="2" class="rounded border border-gray-300 px-2 py-1 text-sm w-full"><?php echo htmlspecialchars((string)($r['notes'] ?? '')); ?></textarea>
                                            <div class="flex gap-2">
                                                <button type="submit" class="h-8 px-3 rounded bg-indigo-600 text-white text-xs font-semibold">Save</button>
                                            </div>
                                        </form>
                                        <form method="post" action="?page=courier_partners&action=deleteRecord" onsubmit="return confirm('Delete this courier partner?');" class="mt-2">
                                            <input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>">
                                            <button type="submit" class="h-8 px-3 rounded bg-red-600 text-white text-xs font-semibold">Delete</button>
                                        </form>
                                    </details>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-sm text-gray-600">Total records: <?php echo (int)$totalRecords; ?></div>
    <?php if ($totalPages > 1): ?>
        <div class="flex items-center gap-2">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=courier_partners&action=list&page_no=<?php echo $p; ?>&limit=<?php echo $limit; ?>&search_text=<?php echo urlencode($search); ?>&status_filter=<?php echo urlencode($statusFilter); ?>"
                   class="h-8 min-w-8 px-2 rounded border text-sm inline-flex items-center justify-center <?php echo $p === $currentPage ? 'bg-gray-900 text-white border-gray-900' : 'bg-white border-gray-300 text-gray-700'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

