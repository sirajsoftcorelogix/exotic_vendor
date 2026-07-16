<?php
$searchValue = htmlspecialchars((string)($search ?? ''), ENT_QUOTES, 'UTF-8');
$statusValue = (string)($status_filter ?? '');
$currentPage = max(1, (int)($currentPage ?? 1));
$totalPages = max(1, (int)($totalPages ?? 1));
$limit = (int)($limit ?? 20);
$totalRecords = (int)($totalRecords ?? 0);
$countryList = is_array($countryList ?? null) ? $countryList : [];
$stateList = is_array($stateList ?? null) ? $stateList : [];
$queryBase = [
    'page' => 'publishers',
    'action' => 'list',
    'search_text' => (string)($search ?? ''),
    'status_filter' => $statusValue,
    'limit' => $limit,
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-book-open text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Books - Publisher management</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Publisher <span class="text-amber-800">Listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage publisher names for inbounding book metadata. Add, edit, and delete sync with Exotic India (<code class="text-xs bg-amber-50 px-1 rounded">vendorcreate</code>, <code class="text-xs bg-amber-50 px-1 rounded">vendormodify</code>, <code class="text-xs bg-amber-50 px-1 rounded">vendordelete</code>).
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <?php if (isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1): ?>
                    <button id="syncPublishersBtn"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-amber-300 bg-white text-amber-800 text-sm font-semibold shadow-sm hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap">
                        <i class="fas fa-sync-alt text-xs opacity-95" aria-hidden="true"></i>
                        Sync from admin
                    </button>
                <?php endif; ?>
                <button id="openPublisherModalBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add Publisher
                </button>
            </div>
        </div>
    </div>

    <div id="publisherAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find publishers by name, contact, phone, city, state, or id.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="publishers">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by name, contact, phone, city, state, or id"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo $searchValue; ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $statusValue === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $statusValue === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Per page</label>
                    <select name="limit" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?php echo $opt; ?>" <?php echo $limit === $opt ? 'selected' : ''; ?>><?php echo $opt; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="window.location='?page=publishers&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Total publishers: <span class="font-semibold text-gray-900"><?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Publisher ID</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Publisher Name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Contact</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Phone</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">City</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">State</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($publishers)): ?>
                        <?php $counter = ($currentPage - 1) * $limit; ?>
                        <?php foreach ($publishers as $publisher): ?>
                            <?php
                            $id = (int)($publisher['id'] ?? 0);
                            $publisherExternalId = (int)($publisher['publishers_id'] ?? 0);
                            $name = (string)($publisher['publishers'] ?? '');
                            $active = (int)($publisher['is_active'] ?? 0) === 1;
                            $contactName = (string)($publisher['contact_name'] ?? '');
                            $phone = (string)($publisher['publisher_phone'] ?? '');
                            $city = (string)($publisher['city'] ?? '');
                            $state = (string)($publisher['state'] ?? '');
                            $publisherPayload = [
                                'id' => $id,
                                'publishers_id' => $publisherExternalId,
                                'publishers' => $name,
                                'contact_name' => $contactName,
                                'publisher_email' => (string)($publisher['publisher_email'] ?? ''),
                                'country_code' => (string)($publisher['country_code'] ?? ''),
                                'publisher_phone' => $phone,
                                'alt_phone' => (string)($publisher['alt_phone'] ?? ''),
                                'gst_number' => (string)($publisher['gst_number'] ?? ''),
                                'pan_number' => (string)($publisher['pan_number'] ?? ''),
                                'address' => (string)($publisher['address'] ?? ''),
                                'city' => $city,
                                'state' => $state,
                                'country' => (string)($publisher['country'] ?? ''),
                                'postal_code' => (string)($publisher['postal_code'] ?? ''),
                                'webpage' => (int)($publisher['webpage'] ?? 0),
                                'stock_replenishment_months' => (int)($publisher['stock_replenishment_months'] ?? 0),
                                'is_active' => $active ? 1 : 0,
                            ];
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo ++$counter; ?></td>
                                <td class="px-5 py-4 text-sm font-medium text-gray-800"><?php echo $publisherExternalId; ?></td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($name, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($contactName, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                                        <?php echo $active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-gray-600"><?php echo htmlspecialchars((string)($publisher['update_at'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        onclick='openPublisherModal(<?php echo json_encode($publisherPayload, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                        onclick="setPublisherStatus(<?php echo $id; ?>, <?php echo $active ? 0 : 1; ?>)">
                                        <?php echo $active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        onclick="deletePublisher(<?php echo $id; ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" class="px-5 py-12 text-center text-sm text-gray-500">No publishers found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4">
                    <div class="text-sm text-gray-500">Page <?php echo $currentPage; ?> of <?php echo $totalPages; ?></div>
                    <div class="flex flex-wrap gap-2">
                        <?php
                        $start = max(1, $currentPage - 3);
                        $end = min($totalPages, $currentPage + 3);
                        for ($p = $start; $p <= $end; $p++):
                            $queryBase['page_no'] = $p;
                            $url = '?' . http_build_query($queryBase);
                        ?>
                            <a href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"
                               class="rounded-lg border px-3 py-1.5 text-sm <?php echo $p === $currentPage ? 'border-amber-500 bg-amber-600 text-white' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div id="publisherModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4 py-6">
    <div class="w-full max-w-3xl max-h-[92vh] overflow-hidden rounded-2xl bg-white shadow-xl flex flex-col">
        <div class="flex items-center justify-between border-b px-6 py-4 shrink-0">
            <h2 id="publisherModalTitle" class="text-lg font-semibold text-gray-900">Add Publisher</h2>
            <button type="button" onclick="closePublisherModal()" class="text-gray-400 hover:text-gray-700">x</button>
        </div>
        <form id="publisherForm" class="overflow-y-auto px-6 py-5 space-y-5">
            <input type="hidden" name="id" id="publisher_id">

            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3">Basic Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Publisher Name</label>
                        <input type="text" name="publishers" id="publisher_name" required
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                        <span id="publisherNameMsg" class="text-sm text-red-500"></span>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                        <select name="is_active" id="publisher_is_active"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Stock Replenishment Months</label>
                        <input type="number" name="stock_replenishment_months" id="publisher_stock_replenishment_months" min="0" step="1"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                            placeholder="e.g. 30">
                        <p class="mt-1 text-xs text-gray-500">Expected months to replenish stock for this publisher. Leave empty or 0 if not set.</p>
                    </div>
                </div>
                <div class="mt-3">
                    <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                        <input type="checkbox" name="webpage" id="publisher_webpage" value="1"
                            class="rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                        <span>Allow webpage on Exotic India</span>
                    </label>
                    <p class="mt-1 text-xs text-gray-500">Maps to the <code>webpage</code> parameter (0 or 1) on the vendor API.</p>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3">Contact Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Contact Person</label>
                        <input type="text" name="contact_name" id="publisher_contact_name"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Email</label>
                        <input type="email" name="publisher_email" id="publisher_email"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Country Code</label>
                        <select name="country_code" id="publisher_country_code"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                            <option value="">Select Code</option>
                            <?php foreach ($countryList as $cl): ?>
                                <option value="<?php echo htmlspecialchars((string)($cl['phone_code'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($cl['name'] ?? '') === 'India') ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string)($cl['name'] ?? ''), ENT_QUOTES, 'UTF-8') . ' (+' . (string)($cl['phone_code'] ?? '') . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Phone</label>
                        <input type="number" name="publisher_phone" id="publisher_phone" oninput="limitPublisherPhoneDigits(this)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Alternate Phone (optional)</label>
                        <input type="number" name="alt_phone" id="publisher_alt_phone" oninput="limitPublisherPhoneDigits(this)"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3">Address</h3>
                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">Address</label>
                        <input type="text" name="address" id="publisher_address"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">City</label>
                            <input type="text" name="city" id="publisher_city"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">State</label>
                            <span id="publisherStateBlock">
                                <select name="state" id="publisher_state"
                                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                                    <?php foreach ($stateList as $item): ?>
                                        <option value="<?php echo htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </span>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Country</label>
                            <select name="country" id="publisher_country" onchange="fetchPublisherStates(this.value);"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                                <?php foreach ($countryList as $item): ?>
                                    <option value="<?php echo htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" <?php echo (($item['name'] ?? '') === 'India') ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars((string)($item['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-semibold text-gray-700">Zip Code</label>
                            <input type="text" name="postal_code" id="publisher_postal_code"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-bold text-gray-800 mb-3">Tax Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">GST Number</label>
                        <input type="text" name="gst_number" id="publisher_gst_number"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-gray-700">PAN Number</label>
                        <input type="text" name="pan_number" id="publisher_pan_number"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closePublisherModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="publisherSaveBtn" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save Publisher</button>
            </div>
        </form>
    </div>
</div>

<script>
let publisherNameExists = false;

function limitPublisherPhoneDigits(input) {
    if (!input) return;
    if (input.value.length > 10) {
        input.value = input.value.slice(0, 10);
    }
}

function setPublisherStateControl(countryName, stateValue) {
    const block = document.getElementById('publisherStateBlock');
    if (!block) return;

    if (countryName !== 'India') {
        block.innerHTML = '<input type="text" name="state" id="publisher_state" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none" />';
        const input = document.getElementById('publisher_state');
        if (input) input.value = stateValue || '';
        return;
    }

    fetch('index.php?page=vendors&action=getStates', { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            const states = Array.isArray(data) ? data : [];
            const select = document.createElement('select');
            select.id = 'publisher_state';
            select.name = 'state';
            select.className = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none';
            states.forEach(function (state) {
                const option = document.createElement('option');
                option.value = state.name;
                option.textContent = state.name;
                if (stateValue && state.name === stateValue) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
            block.innerHTML = '';
            block.appendChild(select);
        })
        .catch(function (err) { console.error('State fetch error:', err); });
}

function fetchPublisherStates(countryName) {
    setPublisherStateControl(countryName, '');
}

function setSelectValueByTextOrValue(selectEl, value) {
    if (!selectEl || value == null || value === '') return;
    const normalized = String(value);
    for (let i = 0; i < selectEl.options.length; i++) {
        if (selectEl.options[i].value === normalized || selectEl.options[i].text === normalized) {
            selectEl.selectedIndex = i;
            return;
        }
    }
}

function bindCreatorNameDuplicateCheck(inputEl, msgEl, page, existsFlagSetter, excludeIdGetter, duplicateMessage) {
    if (!inputEl || !msgEl) return;
    inputEl.addEventListener('keyup', function () {
        const value = inputEl.value.trim();
        if (value.length < 2) {
            existsFlagSetter(false);
            msgEl.textContent = '';
            return;
        }
        const excludeId = excludeIdGetter ? excludeIdGetter() : 0;
        let url = 'index.php?page=' + page + '&action=checkName&name=' + encodeURIComponent(value);
        if (excludeId && parseInt(excludeId, 10) > 0) {
            url += '&excludeId=' + encodeURIComponent(String(excludeId));
        }
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data.exists) {
                    msgEl.textContent = duplicateMessage;
                    existsFlagSetter(true);
                } else {
                    msgEl.textContent = '';
                    existsFlagSetter(false);
                }
            })
            .catch(function (err) { console.error('Duplicate check error:', err); });
    });
}

function showPublisherAlert(message, success) {
    const box = document.getElementById('publisherAlert');
    if (!box) return;
    box.textContent = message || '';
    box.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
    box.classList.add(success ? 'border-green-200' : 'border-red-200', success ? 'bg-green-50' : 'bg-red-50', success ? 'text-green-700' : 'text-red-700');
}

function openPublisherModal(publisher) {
    publisher = publisher || {};
    publisherNameExists = false;
    const publisherNameMsg = document.getElementById('publisherNameMsg');
    if (publisherNameMsg) publisherNameMsg.textContent = '';
    document.getElementById('publisherModalTitle').textContent = publisher.id ? 'Edit Publisher' : 'Add Publisher';
    document.getElementById('publisher_id').value = publisher.id || '';
    document.getElementById('publisher_name').value = publisher.publishers || '';
    document.getElementById('publisher_is_active').value = publisher.is_active != null ? String(publisher.is_active) : '1';
    document.getElementById('publisher_webpage').checked = publisher.webpage === 1 || publisher.webpage === '1';
    document.getElementById('publisher_contact_name').value = publisher.contact_name || '';
    document.getElementById('publisher_email').value = publisher.publisher_email || '';
    setSelectValueByTextOrValue(document.getElementById('publisher_country_code'), publisher.country_code || '');
    document.getElementById('publisher_phone').value = publisher.publisher_phone || '';
    document.getElementById('publisher_alt_phone').value = publisher.alt_phone || '';
    document.getElementById('publisher_address').value = publisher.address || '';
    document.getElementById('publisher_city').value = publisher.city || '';
    document.getElementById('publisher_postal_code').value = publisher.postal_code || '';
    document.getElementById('publisher_gst_number').value = publisher.gst_number || '';
    document.getElementById('publisher_pan_number').value = publisher.pan_number || '';
    document.getElementById('publisher_stock_replenishment_months').value =
        publisher.stock_replenishment_months != null && publisher.stock_replenishment_months !== 0
            ? String(publisher.stock_replenishment_months)
            : '';

    const countrySelect = document.getElementById('publisher_country');
    const countryValue = publisher.country || 'India';
    if (countrySelect) {
        setSelectValueByTextOrValue(countrySelect, countryValue);
    }
    setPublisherStateControl(countryValue, publisher.state || '');

    document.getElementById('publisherModal').classList.remove('hidden');
    document.getElementById('publisherModal').classList.add('flex');
    setTimeout(function () { document.getElementById('publisher_name').focus(); }, 50);
}

function closePublisherModal() {
    document.getElementById('publisherModal').classList.add('hidden');
    document.getElementById('publisherModal').classList.remove('flex');
}

function postPublisherAction(action, body) {
    return fetch('index.php?page=publishers&action=' + action, {
        method: 'POST',
        credentials: 'same-origin',
        body: body
    }).then(function (res) { return res.json(); });
}

document.getElementById('openPublisherModalBtn')?.addEventListener('click', function () {
    openPublisherModal();
});

bindCreatorNameDuplicateCheck(
    document.getElementById('publisher_name'),
    document.getElementById('publisherNameMsg'),
    'publishers',
    function (v) { publisherNameExists = v; },
    function () { return document.getElementById('publisher_id') ? document.getElementById('publisher_id').value : 0; },
    'Publisher name already exists'
);

document.getElementById('publisherForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (publisherNameExists) {
        showPublisherAlert('Publisher name already exists', false);
        return;
    }
    const form = new FormData(this);
    if (!document.getElementById('publisher_webpage').checked) {
        form.set('webpage', '0');
    }
    const btn = document.getElementById('publisherSaveBtn');
    const oldLabel = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
    }
    postPublisherAction('save', form).then(function (res) {
        showPublisherAlert(res.message || (res.success ? 'Publisher saved.' : 'Could not save publisher.'), !!res.success);
        if (res.success) {
            closePublisherModal();
            setTimeout(function () { window.location.reload(); }, 700);
        }
    }).catch(function () {
        showPublisherAlert('Could not save publisher.', false);
    }).finally(function () {
        if (btn) {
            btn.disabled = false;
            btn.textContent = oldLabel;
        }
    });
});

function setPublisherStatus(id, isActive) {
    const form = new FormData();
    form.append('id', id);
    form.append('is_active', isActive);
    postPublisherAction('status', form).then(function (res) {
        showPublisherAlert(res.message || 'Status updated.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showPublisherAlert('Could not update status.', false);
    });
}

function deletePublisher(id) {
    if (!confirm('Delete this publisher on Exotic India and locally? This cannot be undone.')) return;
    const form = new FormData();
    form.append('id', id);
    postPublisherAction('delete', form).then(function (res) {
        showPublisherAlert(res.message || 'Delete complete.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showPublisherAlert('Could not delete publisher.', false);
    });
}

document.getElementById('syncPublishersBtn')?.addEventListener('click', function () {
    if (!confirm('Sync publishers from Admin now? Existing publisher names will be updated by publisher ID.')) return;
    const btn = this;
    const oldHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs"></i> Syncing...';
    postPublisherAction('syncFromAdmin', new FormData()).then(function (res) {
        const msg = res.success
            ? ((res.message || 'Publisher sync completed.') + ' Imported/updated: ' + (res.imported || 0) + '. Skipped: ' + (res.skipped || 0) + '.')
            : (res.message || 'Publisher sync failed.');
        showPublisherAlert(msg, !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 1200);
    }).catch(function () {
        showPublisherAlert('Publisher sync failed.', false);
    }).finally(function () {
        btn.disabled = false;
        btn.innerHTML = oldHtml;
    });
});
</script>
