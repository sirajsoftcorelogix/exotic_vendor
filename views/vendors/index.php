<?php
$vfInput = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition';
$vfLabel = 'mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-600';
$vfSection = 'rounded-xl border border-gray-200/90 bg-gradient-to-b from-gray-50/70 to-white p-4 sm:p-5 space-y-4';
$vfHint = 'mt-1 text-xs text-gray-500';
$vendorModalWidth = 'width: 42%; min-width: 520px; max-width: 680px;';
$vendorRatingOptions = ['5 Star', '4 Star', '3 Star', '2 Star', '1 Star'];
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <!-- Page Header -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-truck-loading text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Procurement · Vendor management</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Vendor's <span class="text-amber-800">Listing</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Search suppliers by vendor profile details, group, team, and status with quick actions for editing and banking.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <a href="?page=brokers&action=list"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap">
                    <i class="fas fa-user-tie text-xs opacity-95" aria-hidden="true"></i>
                    Manage brokers
                </a>
                <?php if (isset($_SESSION['user']['role_id']) && (int)$_SESSION['user']['role_id'] === 1): ?>
                    <button id="sync-vendors-api-btn"
                        class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-amber-300 bg-white text-amber-800 text-sm font-semibold shadow-sm hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50/50 transition whitespace-nowrap">
                        <i class="fas fa-sync-alt text-xs opacity-95" aria-hidden="true"></i>
                        Pull from Admin
                    </button>
                <?php endif; ?>
                <?php if (hasPermission($_SESSION["user"]["id"], 'Vendors', 'add')): ?>
                    <button id="open-vendor-popup-btn"
                        class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 focus-visible:ring-offset-amber-50/50 transition whitespace-nowrap w-full sm:w-auto">
                        <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                        Add Vendor
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="hidden text-sm font-bold mb-4" id="messageDiv" role="status"><?php echo $_SESSION["mapping_message"] ?? ""; unset($_SESSION["mapping_message"]); ?></div>

    <!-- Vendor API debug modal (shown from sync error banner) -->
    <div id="vendorApiDebugModal" class="fixed inset-0 flex items-center justify-center bg-black/50 hidden z-[60]" role="dialog" aria-modal="true" aria-labelledby="vendorApiDebugTitle">
        <div class="bg-white rounded-xl shadow-xl w-[min(920px,95vw)] max-h-[90vh] flex flex-col p-6 m-4">
            <h2 id="vendorApiDebugTitle" class="text-lg font-bold text-gray-900">Exotic India API details</h2>
            <p id="vendorApiDebugMeta" class="text-sm text-gray-600 mt-1 mb-4"></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 overflow-auto flex-1 min-h-0">
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Request JSON</h3>
                    <pre id="vendorApiDebugRequest" class="text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-auto max-h-72 whitespace-pre-wrap break-words"></pre>
                </div>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-gray-800 mb-2">Response</h3>
                    <pre id="vendorApiDebugResponse" class="text-xs font-mono bg-slate-50 border border-slate-200 rounded-lg p-3 overflow-auto max-h-72 whitespace-pre-wrap break-words"></pre>
                </div>
            </div>
            <div class="mt-5 flex justify-end">
                <button type="button" id="vendorApiDebugCloseBtn" class="px-4 py-2 rounded-lg bg-gray-800 text-white text-sm font-medium hover:bg-gray-900">Close</button>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-200 mt-0.5 hidden sm:block">Find vendors by name, id, group, category, team, and status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="vendors">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by name, vendor id, groupname, email, or phone"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo htmlspecialchars($data['search'] ?? ''); ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Group Name</label>
                    <?php $groupnameOptions = array('textiles','paintings','sculptures','jewelry','homeandliving',''); ?>
                    <select name="groupname_filter"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All Groups</option>
                        <?php foreach ($groupnameOptions as $groupOpt): ?>
                            <?php if ($groupOpt === '') continue; ?>
                            <option value="<?php echo htmlspecialchars($groupOpt); ?>" <?php echo (($data['groupname_filter'] ?? '') === $groupOpt) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars(function_exists('mb_convert_case') ? mb_convert_case($groupOpt, MB_CASE_TITLE, 'UTF-8') : ucwords($groupOpt)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Category</label>
                    <select class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" name="category_filter" id="category_filter">
                        <option value="" selected>All Categories</option>
                        <?php foreach($category[0] as $key => $value): ?>
                            <?php if ($value['parent_id'] == 0): ?>
                                <optgroup label="<?php echo $value['category_name']; ?>">
                                    <?php foreach($category[$value['id']] as $subKey => $subValue):
                                        if ($subValue['parent_id'] == $value['id']): ?>
                                            <option value="<?php echo $subValue['id']; ?>" title="<?php echo $subValue['category_name']; ?>" <?php echo ($data['category_filter'] == $subValue['id']) ? "selected" : "";?>><?php echo $subValue['category_name']; ?></option>
                                    <?php endif; endforeach; ?>
                                </optgroup>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Team</label>
                    <select class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" name="team_filter" id="team_filter">
                        <option value="" selected>All Teams</option>
                        <?php foreach($teamList as $team): ?>
                            <option value="<?php echo $team['id']; ?>" <?php echo ($data['team_filter'] == $team['id']) ? "selected" : "";?>><?php echo $team['team_name']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" name="status_filter" id="status_filter">
                        <option value="" selected>All Status</option>
                        <option value="active" <?php echo ($data['status_filter'] == "active") ? "selected" : "";?>>Active</option>
                        <option value="inactive" <?php echo ($data['status_filter'] == "inactive") ? "selected" : "";?>>Inactive</option>
                        <option value="blacklisted" <?php echo ($data['status_filter'] == "blacklisted") ? "selected" : "";?>>Blacklisted</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="document.getElementById('filterForm').reset();window.location='?page=vendors&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <!-- Vendor Listing -->
    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
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
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Vendor Name</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Groupname</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap" nowrap>Vendor ID</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Phone</th>
                        <!-- <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider table-header-text">Email</th> -->
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">City</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">State</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Broker</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th scope="col" class="px-5 py-3.5 whitespace-nowrap">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($vendors)): ?>
                        <?php
                            $page = isset($_GET['page_no']) ? (int)$_GET['page_no'] : 1;
                            $page = $page < 1 ? 1 : $page;
                            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20; // records per page, default 20
                            $limit = in_array($limit, [10, 20, 50, 100]) ? $limit : 20; // Only allow specific values
                            $total_records = isset($data['totalRecords']) ? (int)$data['totalRecords'] : 0;
                            $total_pages = $limit > 0 ? ceil($total_records / $limit) : 1;

                            // Calculate start/end slot for 10 pages
                            $slot_size = 10;
                            $start = max(1, $page - floor($slot_size / 2));
                            $end = min($total_pages, $start + $slot_size - 1);
                            if ($end - $start < $slot_size - 1) {
                                $start = max(1, $end - $slot_size + 1);
                            }
                            $counter = ($page - 1) * $limit;
                        ?>
                        <?php foreach ($vendors as $index => $vendor): ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?= ++$counter ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm font-semibold text-gray-900"><?= htmlspecialchars($vendor['vendor_name'] ?? '') ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700">
                                    <?php
                                        $groupNameRaw = trim((string)($vendor['groupname'] ?? ''));
                                        $groupNameDisplay = '';
                                        if ($groupNameRaw !== '') {
                                            $parts = array_filter(array_map('trim', explode(',', $groupNameRaw)), static function ($v) {
                                                return $v !== '';
                                            });
                                            $normalized = [];
                                            foreach ($parts as $part) {
                                                $slug = strtolower($part);
                                                $map = [
                                                    'painting' => 'Paintings',
                                                    'sculpture' => 'Sculptures',
                                                    'textile' => 'Textiles',
                                                    'jewelry' => 'Jewelry',
                                                    'book' => 'Book',
                                                    'homeandliving' => 'Home And Living',
                                                ];
                                                if (isset($map[$slug])) {
                                                    $normalized[] = $map[$slug];
                                                } elseif (function_exists('mb_convert_case')) {
                                                    $normalized[] = mb_convert_case($part, MB_CASE_TITLE, 'UTF-8');
                                                } else {
                                                    $normalized[] = ucwords(strtolower($part));
                                                }
                                            }
                                            $groupNameDisplay = implode(', ', $normalized);
                                        }
                                    ?>
                                    <?= htmlspecialchars($groupNameDisplay) ?>
                                </td>
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?= !empty($vendor['vendor_id']) ? htmlspecialchars((string)$vendor['vendor_id']) : '-' ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?= htmlspecialchars($vendor['vendor_phone'] ?? '') ?></td>
                                <!-- <td class="px-6 py-4 whitespace-wrap"><?= htmlspecialchars($vendor['vendor_email'] ?? '') ?></td> -->
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?= htmlspecialchars($vendor['city'] ?? '') ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?= htmlspecialchars($vendor['state'] ?? '') ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm text-gray-700"><?php
                                    $brokerName = trim((string)($vendor['broker_name'] ?? ''));
                                    echo htmlspecialchars($brokerName !== '' ? $brokerName : '-', ENT_QUOTES, 'UTF-8');
                                ?></td>
                                <td class="px-5 py-4 whitespace-wrap text-sm">
                                    <?php
                                        $vendorStatus = strtolower(trim((string)($vendor['is_active'] ?? '')));
                                        $vendorStatusClass = 'bg-slate-50 text-slate-700 ring-slate-500/15';
                                        if ($vendorStatus === 'active') {
                                            $vendorStatusClass = 'bg-emerald-50 text-emerald-800 ring-emerald-600/20';
                                        } elseif ($vendorStatus === 'inactive') {
                                            $vendorStatusClass = 'bg-amber-50 text-amber-900 ring-amber-600/25';
                                        } elseif ($vendorStatus === 'blacklisted') {
                                            $vendorStatusClass = 'bg-gray-100 text-gray-700 ring-gray-500/20';
                                        }
                                    ?>
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?php echo $vendorStatusClass; ?>">
                                        <?= htmlspecialchars(ucfirst($vendor['is_active'] ?? '')) ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <!-- Three-dot menu container -->
                                    <div class="menu-wrapper">
                                        <button class="menu-button" onclick="toggleMenu(this)">
                                            &#x22EE; <!-- Vertical ellipsis -->
                                        </button> 
                                        <ul class="menu-popup">
                                            <?php if (hasPermission($_SESSION["user"]["id"], 'Vendors', 'edit')) { ?>
                                                <li onclick="openEditModal(<?= htmlspecialchars($vendor['id']) ?>)"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <?php } ?>
                                            <li onclick="openBankDtlsModal(<?= htmlspecialchars($vendor['id']) ?>)"><i class="fa-solid fa-building-columns"></i> Bank Details</li>
                                            <?php if (hasPermission($_SESSION["user"]["id"], 'Vendors', 'delete')) { ?>
                                                <li class="delete-btn" data-id="<?php echo $vendor['id']; ?>"><i class="fa-solid fa-trash"></i> Delete</li>
                                            <?php } ?>
                                            <li style="color: lightgray;"><i class="fa-solid fa-cart-shopping"></i> Purchase Order</li>
                                            <li style="color: lightgray;"><i class="fa-solid fa-file-invoice-dollar"></i> Invoices</li>
                                            <li style="color: lightgray;"><i class="fa-solid fa-indian-rupee-sign"></i> Payments</li>
                                        </ul>
                                    </div>

                                </td>
                            </tr>
                        <?php endforeach; ?>
                        
                    <?php else: ?>
                        <tr>
                            <td colspan="12" class="px-6 py-16 text-center text-gray-500">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No vendors match</p>
                                    <p class="mt-1 text-sm text-gray-500">Try changing filters or add a new vendor.</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination Logic -->
    <?php if (!empty($vendors)): ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-4">
            <div class="flex flex-wrap items-center justify-center gap-3 text-sm text-gray-600">
                    <?php
                    if ($total_pages > 1): ?>          
                    <!-- Prev Button -->
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if($page <= 1) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=vendors&action=list&page_no=<?= $page-$slot_size ?>&limit=<?= $limit ?>">
                        &laquo; Prev
                    </a>
                    <!-- Page Slots -->
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a class="inline-flex items-center px-3 py-1.5 rounded-lg <?= $i == $page ? 'bg-amber-600 text-white font-bold' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>"
                        href="?page=vendors&action=list&page_no=<?= $i ?>&limit=<?= $limit ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>
                    <!-- Next Button -->
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if($page >= $total_pages) echo 'opacity-50 pointer-events-none'; ?>"
                    href="?page=vendors&action=list&page_no=<?= $page+$slot_size ?>&limit=<?= $limit ?>">
                        Next &raquo;
                    </a>
                    <?php endif; ?>
                    <select id="rows-per-page" class="px-2 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700 focus:outline-none focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500"
                            onchange="location.href='?page=vendors&action=list&page_no=1&limit=' + this.value;">
                        <?php foreach ([10, 20, 50, 100] as $opt): ?>
                            <option value="<?= $opt ?>" <?= $opt === $limit ? 'selected' : '' ?>>
                                <?= $opt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Vendor Modal -->
<div id="popup-wrapper" class="hidden">
    <!-- Background Overlay -->
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div>

    <!-- Sliding Container -->
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="<?= htmlspecialchars($vendorModalWidth, ENT_QUOTES, 'UTF-8') ?>">

        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <!-- Popup Panel -->
        <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl flex flex-col" style="width: 100%;">
            <div class="flex-1 overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50/60 to-white">
                    <h2 class="text-xl font-bold text-gray-900">Add vendor</h2>
                    <p class="mt-1 text-sm text-gray-500">Create a supplier profile and sync with Exotic India when groups are selected.</p>
                </div>
                <div class="px-6 py-5">
                    <div id="addVendorMsg" class="text-sm font-semibold mb-4"></div>
                    <form id="addVendorForm" class="space-y-5">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="addVendor">

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                    <i class="fas fa-building text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Basic information</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Vendor identity, contact, and assignment.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Vendor name <span class="text-red-500 normal-case">*</span></label>
                                    <input type="text" class="<?= $vfInput ?>" required name="addVendorName" id="addVendorName" placeholder="Official vendor name" />
                                    <span id="addVendorNameMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Contact person <span class="text-red-500 normal-case">*</span></label>
                                    <input type="text" class="<?= $vfInput ?>" required name="addContactPerson" id="addContactPerson" placeholder="Primary contact" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="<?= $vfLabel ?>">Phone <span class="text-red-500 normal-case">*</span></label>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <select class="<?= $vfInput ?> sm:max-w-[220px]" name="addCountryCode" id="addCountryCode" required>
                                            <option value="" disabled>Select code</option>
                                            <?php foreach ($countryList as $cl): ?>
                                                <option value="<?php echo $cl['phone_code']; ?>" <?php if ($cl['name'] === 'India') { echo 'selected'; } ?>>
                                                    <?php echo $cl['name'] . ' (+' . $cl['phone_code'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" class="<?= $vfInput ?> flex-1" required oninput="limitToTenDigits(this)" name="addPhone" id="addPhone" placeholder="10-digit mobile" />
                                    </div>
                                    <span id="addPhoneMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Email</label>
                                    <input type="email" class="<?= $vfInput ?>" name="addEmail" id="addEmail" placeholder="name@vendor.com" />
                                    <span id="addEmailMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Alternate phone</label>
                                    <input type="number" class="<?= $vfInput ?>" name="addAltPhone" id="addAltPhone" oninput="limitToTenDigits(this)" placeholder="Optional" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Broker</label>
                                    <select name="broker_id" id="add_broker_id" class="<?= $vfInput ?>">
                                        <option value="">Select broker...</option>
                                    </select>
                                    <p class="<?= $vfHint ?>">Search active brokers from Broker Master.</p>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Status <span class="text-red-500 normal-case">*</span></label>
                                    <select class="<?= $vfInput ?>" required name="addStatus" id="addStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="blacklisted">Blacklisted</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-handshake text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Commercial &amp; groups</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Categories, product groups, and default terms.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Stock replenishment (months)</label>
                                    <input type="number" class="<?= $vfInput ?>" name="stock_replenishment_months" id="add_stock_replenishment_months" min="0" step="1" placeholder="e.g. 3" />
                                    <p class="<?= $vfHint ?>">Leave empty or 0 if not set.</p>
                                </div>
                                <div id="addBookDiscountField" class="hidden">
                                    <label class="<?= $vfLabel ?>">Discount (%)</label>
                                    <input type="number" class="<?= $vfInput ?>" name="discount" id="add_discount" min="0" step="0.01" placeholder="e.g. 10" />
                                    <p class="<?= $vfHint ?>">For book vendors only.</p>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Group name <span class="text-red-500 normal-case">*</span></label>
                                <div id="groupname" class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-amber-200/80 bg-amber-50/40 p-3 max-h-36 overflow-y-auto">
                                    <?php foreach ($groupnameList as $groupSlug): ?>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 vendor-group-checkbox" name="groupname[]" value="<?php echo htmlspecialchars($groupSlug); ?>">
                                            <span><?php echo htmlspecialchars(function_exists('mb_convert_case') ? mb_convert_case($groupSlug, MB_CASE_TITLE, 'UTF-8') : ucwords($groupSlug)); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <span id="addGroupnameMsg" class="text-sm text-red-500"></span>
                                <p class="<?= $vfHint ?>">Required for Exotic India API sync.</p>
                            </div>
                            <label class="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50/50 px-3 py-2.5 cursor-pointer">
                                <input type="hidden" name="addWebpage" value="0">
                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" name="addWebpage" id="addWebpage" value="1" checked>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Allow webpage on Exotic India</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Whether this vendor may have a public webpage.</span>
                                </span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Team</label>
                                    <select class="<?= $vfInput ?> min-h-[7rem]" multiple name="addTeam[]" id="addTeam" onchange="fillTeamAgent(this.value, 'AddForm');">
                                        <option value="" disabled>Select team</option>
                                        <?php foreach ($teamList as $team): ?>
                                            <option value="<?php echo $team['id']; ?>"><?php echo $team['team_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Agent</label>
                                    <span id="addTeamMemberBlock">
                                        <select class="<?= $vfInput ?>" name="addTeamMember" id="addTeamMember">
                                            <option value="" disabled selected>Select team member</option>
                                        </select>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Category</label>
                                <select class="<?= $vfInput ?> min-h-[7rem]" multiple name="addVendorCategory[]" id="addVendorCategory">
                                    <option value="" disabled>Select categories</option>
                                    <?php foreach ($category[0] as $key => $value): ?>
                                        <?php if ($value['parent_id'] == 0): ?>
                                            <optgroup label="<?php echo $value['category_name']; ?>">
                                                <?php foreach ($category[$value['id']] as $subKey => $subValue):
                                                    if ($subValue['parent_id'] == $value['id']): ?>
                                                        <option value="<?php echo $subValue['id']; ?>"><?php echo $subValue['category_name']; ?></option>
                                                <?php endif; endforeach; ?>
                                            </optgroup>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                    <i class="fas fa-map-marker-alt text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Address</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Location details for the vendor.</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Street address</label>
                                    <input type="text" class="<?= $vfInput ?>" name="addAddress" id="addAddress" placeholder="Building, street, area" />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="<?= $vfLabel ?>">City</label>
                                        <input type="text" class="<?= $vfInput ?>" name="addCity" id="addCity" />
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">State</label>
                                        <span id="addStateBlock">
                                            <select class="<?= $vfInput ?>" name="addState" id="addState">
                                                <option value="">Select State</option>
                                                <?php foreach ($stateList as $item): ?>
                                                    <option value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">Country</label>
                                        <select class="<?= $vfInput ?>" name="addCountry" id="addCountry" onchange="fetchStates(this.value, 'AddForm');">
                                            <?php foreach ($countryList as $item): ?>
                                                <option value="<?php echo $item['name']; ?>" <?php if ($item['name'] === 'India') { echo 'selected'; } ?>><?php echo $item['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">Postal code</label>
                                        <input type="text" class="<?= $vfInput ?>" name="addPostalCode" id="addPostalCode" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                                    <i class="fas fa-file-invoice text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Tax &amp; notes</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">GST/PAN and internal notes.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">GST number</label>
                                    <input type="text" class="<?= $vfInput ?>" name="addGstNumber" id="addGstNumber" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">PAN number</label>
                                    <input type="text" class="<?= $vfInput ?>" name="addPanNumber" id="addPanNumber" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Rating</label>
                                    <select class="<?= $vfInput ?>" name="addRating" id="addRating">
                                        <option value="">Select rating (optional)</option>
                                        <?php foreach ($vendorRatingOptions as $ratingOption): ?>
                                            <option value="<?php echo htmlspecialchars($ratingOption, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ratingOption, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Notes</label>
                                <textarea class="<?= $vfInput ?> min-h-[100px] resize-y" name="addNotes" id="addNotes" placeholder="Internal notes about this vendor"></textarea>
                            </div>
                        </section>

                        <div class="sticky bottom-0 -mx-6 px-6 py-4 bg-white/95 backdrop-blur-sm border-t border-gray-200 flex justify-end gap-3">
                            <button type="button" id="cancel-vendor-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save vendor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End Add Model Popup -->

<!-- Edit Vendor Modal -->
<div class="modal fade hidden" id="editVendorModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <!-- Sliding Container -->
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="<?= htmlspecialchars($vendorModalWidth, ENT_QUOTES, 'UTF-8') ?>">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-vendor-popup-btn-edit" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div class="h-full bg-white shadow-2xl flex flex-col" style="width: 100%;">
            <div class="flex-1 overflow-y-auto">
                <div class="px-6 py-5 border-b border-gray-100 bg-gradient-to-r from-amber-50/60 to-white">
                    <h2 class="text-xl font-bold text-gray-900">Edit vendor</h2>
                    <p class="mt-1 text-sm text-gray-500">Update supplier profile, groups, and contact details.</p>
                </div>
                <div class="px-6 py-5">
                    <div id="editVendorMsg" class="text-sm font-semibold mb-4"></div>
                    <form id="editUserForm" class="space-y-5">
                        <input type="hidden" id="editVendorId" name="id" value="">
                        <input type="hidden" id="editAgentIds" value="">
                        <input type="hidden" id="editPreviousState" name="editPreviousState" value="">

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-800">
                                    <i class="fas fa-building text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Basic information</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Vendor identity, contact, and assignment.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Vendor name <span class="text-red-500 normal-case">*</span></label>
                                    <input type="text" class="<?= $vfInput ?>" required name="editVendorName" id="editVendorName" />
                                    <span id="editVendorNameMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Contact person <span class="text-red-500 normal-case">*</span></label>
                                    <input type="text" class="<?= $vfInput ?>" required name="editContactPerson" id="editContactPerson" />
                                </div>
                                <div class="md:col-span-2">
                                    <label class="<?= $vfLabel ?>">Phone <span class="text-red-500 normal-case">*</span></label>
                                    <div class="flex flex-col sm:flex-row gap-2">
                                        <select class="<?= $vfInput ?> sm:max-w-[220px]" name="editCountryCode" id="editCountryCode" required>
                                            <option value="" disabled>Select code</option>
                                            <?php foreach ($countryList as $cl): ?>
                                                <option value="<?php echo $cl['phone_code']; ?>">
                                                    <?php echo $cl['name'] . ' (+' . $cl['phone_code'] . ')'; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="number" class="<?= $vfInput ?> flex-1" required name="editPhone" id="editPhone" oninput="limitToTenDigits(this)" placeholder="10-digit mobile" />
                                    </div>
                                    <span id="editPhoneMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Email</label>
                                    <input type="email" class="<?= $vfInput ?>" name="editEmail" id="editEmail" />
                                    <span id="editEmailMsg" class="text-sm text-red-500"></span>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Alternate phone</label>
                                    <input type="number" class="<?= $vfInput ?>" name="editAltPhone" id="editAltPhone" oninput="limitToTenDigits(this)" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Broker</label>
                                    <select name="broker_id" id="edit_broker_id" class="<?= $vfInput ?>">
                                        <option value="">Select broker...</option>
                                    </select>
                                    <p class="<?= $vfHint ?>">Search active brokers from Broker Master.</p>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Status <span class="text-red-500 normal-case">*</span></label>
                                    <select class="<?= $vfInput ?>" required name="editStatus" id="editStatus">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="blacklisted">Blacklisted</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                                    <i class="fas fa-handshake text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Commercial &amp; groups</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Categories, product groups, and default terms.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Stock replenishment (months)</label>
                                    <input type="number" class="<?= $vfInput ?>" name="stock_replenishment_months" id="edit_stock_replenishment_months" min="0" step="1" placeholder="e.g. 3" />
                                    <p class="<?= $vfHint ?>">Leave empty or 0 if not set.</p>
                                </div>
                                <div id="editBookDiscountField" class="hidden">
                                    <label class="<?= $vfLabel ?>">Discount (%)</label>
                                    <input type="number" class="<?= $vfInput ?>" name="discount" id="edit_discount" min="0" step="0.01" placeholder="e.g. 10" />
                                    <p class="<?= $vfHint ?>">For book vendors only.</p>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Group name <span class="text-red-500 normal-case">*</span></label>
                                <div id="editGroupname" class="mt-1 grid grid-cols-2 gap-2 rounded-lg border border-amber-200/80 bg-amber-50/40 p-3 max-h-36 overflow-y-auto">
                                    <?php foreach ($groupnameList as $groupSlug): ?>
                                        <label class="inline-flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                                            <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500 vendor-group-checkbox" name="editGroupname[]" value="<?php echo htmlspecialchars($groupSlug); ?>">
                                            <span><?php echo htmlspecialchars(function_exists('mb_convert_case') ? mb_convert_case($groupSlug, MB_CASE_TITLE, 'UTF-8') : ucwords($groupSlug)); ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <span id="editGroupnameMsg" class="text-sm text-red-500"></span>
                                <p class="<?= $vfHint ?>">Required for Exotic India API sync.</p>
                            </div>
                            <label class="flex items-start gap-3 rounded-lg border border-amber-100 bg-amber-50/50 px-3 py-2.5 cursor-pointer">
                                <input type="hidden" name="editWebpage" value="0">
                                <input type="checkbox" class="mt-0.5 rounded border-gray-300 text-amber-600 focus:ring-amber-500" name="editWebpage" id="editWebpage" value="1" checked>
                                <span>
                                    <span class="block text-sm font-medium text-gray-800">Allow webpage on Exotic India</span>
                                    <span class="block text-xs text-gray-500 mt-0.5">Whether this vendor may have a public webpage.</span>
                                </span>
                            </label>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Team</label>
                                    <select class="<?= $vfInput ?> min-h-[7rem]" multiple name="editTeam[]" id="editTeam" onchange="fillTeamAgent(this.value, 'EditForm');">
                                        <option value="" disabled>Select team</option>
                                        <?php foreach ($teamList as $team): ?>
                                            <option value="<?php echo $team['id']; ?>"><?php echo $team['team_name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Agent</label>
                                    <span id="editTeamMemberBlock">
                                        <select class="<?= $vfInput ?>" name="editTeamMember" id="editTeamMember">
                                            <option value="" disabled selected>Select agent</option>
                                        </select>
                                    </span>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Category</label>
                                <select class="<?= $vfInput ?> min-h-[7rem]" multiple name="addVendorCategory[]" id="editVendorCategory">
                                    <option value="" disabled>Select categories</option>
                                    <?php
                                    if (isset($category[0]) && is_array($category[0])) {
                                        foreach ($category[0] as $parent) {
                                            $parentId = $parent['id'];
                                            $parentName = $parent['category_name'];
                                            echo '<optgroup label="' . htmlspecialchars($parentName) . '">';
                                            if (isset($category[$parentId]) && is_array($category[$parentId])) {
                                                foreach ($category[$parentId] as $child) {
                                                    echo '<option value="' . (int) $child['id'] . '">' . htmlspecialchars($child['category_name']) . '</option>';
                                                }
                                            }
                                            echo '</optgroup>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700">
                                    <i class="fas fa-map-marker-alt text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Address</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">Location details for the vendor.</p>
                                </div>
                            </div>
                            <div class="space-y-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">Street address</label>
                                    <input type="text" class="<?= $vfInput ?>" name="editAddress" id="editAddress" />
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="<?= $vfLabel ?>">City</label>
                                        <input type="text" class="<?= $vfInput ?>" name="editCity" id="editCity" />
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">State</label>
                                        <span id="editStateBlock">
                                            <select class="<?= $vfInput ?>" name="editState" id="editState">
                                                <option value="">Select State</option>
                                                <?php foreach ($stateList as $item): ?>
                                                    <option value="<?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </span>
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">Country</label>
                                        <select class="<?= $vfInput ?>" name="editCountry" id="editCountry" onchange="fetchStates(this.value, 'EditForm');">
                                            <?php foreach ($countryList as $item): ?>
                                                <option value="<?php echo $item['name']; ?>" <?php if ($item['name'] === 'India') { echo 'selected'; } ?>><?php echo $item['name']; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="<?= $vfLabel ?>">Postal code</label>
                                        <input type="text" class="<?= $vfInput ?>" name="editPostalCode" id="editPostalCode" />
                                    </div>
                                </div>
                            </div>
                        </section>

                        <section class="<?= $vfSection ?>">
                            <div class="flex items-start gap-3 border-b border-gray-200/70 pb-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-violet-100 text-violet-700">
                                    <i class="fas fa-file-invoice text-sm" aria-hidden="true"></i>
                                </span>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900">Tax &amp; notes</h3>
                                    <p class="text-xs text-gray-500 mt-0.5">GST/PAN and internal notes.</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="<?= $vfLabel ?>">GST number</label>
                                    <input type="text" class="<?= $vfInput ?>" name="editGstNumber" id="editGstNumber" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">PAN number</label>
                                    <input type="text" class="<?= $vfInput ?>" name="editPanNumber" id="editPanNumber" />
                                </div>
                                <div>
                                    <label class="<?= $vfLabel ?>">Rating</label>
                                    <select class="<?= $vfInput ?>" name="editRating" id="editRating">
                                        <option value="">Select rating (optional)</option>
                                        <?php foreach ($vendorRatingOptions as $ratingOption): ?>
                                            <option value="<?php echo htmlspecialchars($ratingOption, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ratingOption, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="<?= $vfLabel ?>">Notes</label>
                                <textarea class="<?= $vfInput ?> min-h-[100px] resize-y" name="editNotes" id="editNotes"></textarea>
                            </div>
                        </section>

                        <div class="sticky bottom-0 -mx-6 px-6 py-4 bg-white/95 backdrop-blur-sm border-t border-gray-200 flex justify-end gap-3">
                            <button type="button" id="cancel-vendor-btn-edit" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save changes</button>
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

<!-- Bank Detail Modal -->
<div class="modal fade hidden" id="bankDetailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <!-- Sliding Container -->
    <div id="modal-slider-bd" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <!-- Close Button -->
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-bd-popup-btn-bd" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Popup Panel -->
        <div id="vendor-popup-panel" class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add / Edit Bank Details</h2>
                    <div id="bankDetailMsg" style="margin-top:10px;" class="text-sm font-bold"></div>
                    <form id="bankDetailForm">
                        <input type="hidden" name="page" value="vendors">
                        <input type="hidden" name="action" value="addBankDetails">
                        <input type="hidden" name="vendor_id" id="vendor_id" value="">

                        <!-- Basic Information -->
                        <div class="pt-4">

                            <div class="grid grid-cols-2 gap-x-8 gap-y-4 mb-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Account Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="account_name" id="account_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Account Number <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="account_number" id="account_number" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Bank Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" required name="bank_name" id="bank_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700">Branch Name <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" name="branch_name" id="branch_name" />
                                </div>
                                <div>
                                    <label class="text-sm font-medium text-gray-700"> IFSC Code <span class="text-red-500">*</span></label>
                                    <input type="text" class="form-input w-full mt-1" name="ifsc_code" id="ifsc_code" />
                                </div>
                                <?php /* ?><div>
                                    <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                    <select class="form-input w-full mt-1" name="bdStatus" id="bdStatus">
                                        <option value="1">Active</option>
                                        <option value="0">Inactive </option>
                                    </select>
                                </div><? */?>
                            </div>
                        </div>

                        <div class="flex justify-center items-center gap-4 pt-6 border-t">
                            <button type="button" id="cancel-bd-btn" class="action-btn cancel-btn">Back</button>
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
    const myDiv = document.getElementById('messageDiv');
    const VENDOR_FLASH_KEY = 'vendor_list_flash_message';
    const VENDOR_FLASH_BANNER_CLASSES = [
        'text-green-600', 'text-red-600',
        'bg-green-50', 'bg-red-50',
        'border', 'border-green-200', 'border-red-200',
        'rounded-lg', 'px-4', 'py-3', 'shadow-sm'
    ];

    function persistVendorFlash(message, isSuccess, persistent, apiDebug) {
        try {
            const payload = {
                message: String(message || ''),
                isSuccess: !!isSuccess,
                persistent: persistent !== undefined ? !!persistent : !isSuccess
            };
            if (apiDebug) {
                payload.apiDebug = apiDebug;
            }
            localStorage.setItem(VENDOR_FLASH_KEY, JSON.stringify(payload));
        } catch (e) {}
    }

    function formatVendorApiDebugJson(value) {
        if (value === null || value === undefined) {
            return '';
        }
        if (typeof value === 'string') {
            return value;
        }
        try {
            return JSON.stringify(value, null, 2);
        } catch (e) {
            return String(value);
        }
    }

    function extractVendorApiDebug(apiData) {
        if (!apiData || typeof apiData !== 'object') {
            return null;
        }
        return {
            action: apiData.api_action || '',
            url: apiData.api_url || '',
            http_code: apiData.http_code || 0,
            request: apiData.request || null,
            response: apiData.response !== undefined ? apiData.response : (apiData.raw || '')
        };
    }

    function showVendorApiDebugModal(debug) {
        const modal = document.getElementById('vendorApiDebugModal');
        const meta = document.getElementById('vendorApiDebugMeta');
        const reqEl = document.getElementById('vendorApiDebugRequest');
        const resEl = document.getElementById('vendorApiDebugResponse');
        if (!modal || !meta || !reqEl || !resEl || !debug) {
            return;
        }
        const action = debug.action || 'vendor API';
        const url = debug.url || '';
        const httpCode = debug.http_code ? ('HTTP ' + debug.http_code) : '';
        meta.textContent = [action, url, httpCode].filter(Boolean).join(' · ');
        reqEl.textContent = formatVendorApiDebugJson(debug.request);
        resEl.textContent = formatVendorApiDebugJson(debug.response);
        modal.classList.remove('hidden');
    }

    function closeVendorApiDebugModal() {
        const modal = document.getElementById('vendorApiDebugModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }

    function vendorApiMessageIsNoChanges(message) {
        const msg = String(message || '').trim().toLowerCase().replace(/\.$/, '');
        return msg === 'no changes detected';
    }

    function isVendorApiResponseOk(apiData) {
        if (!apiData || typeof apiData !== 'object') {
            return true;
        }
        if (apiData.success === true) {
            return true;
        }
        if (vendorApiMessageIsNoChanges(apiData.message)) {
            return true;
        }
        if (apiData.data && vendorApiMessageIsNoChanges(apiData.data.message)) {
            return true;
        }
        if (apiData.response && vendorApiMessageIsNoChanges(apiData.response.message)) {
            return true;
        }
        return false;
    }

    function parseVendorApiResponse(data) {
        let apiSuccess = true;
        let apiErrorMessage = '';
        let apiDebug = null;
        if (!data || !data.api_response) {
            return { apiSuccess, apiErrorMessage, apiDebug };
        }
        try {
            const apiData = typeof data.api_response === 'string' ? JSON.parse(data.api_response) : data.api_response;
            apiSuccess = isVendorApiResponseOk(apiData);
            if (!apiSuccess && apiData && apiData.message) {
                apiErrorMessage = String(apiData.message);
            }
            if (!apiSuccess) {
                apiDebug = extractVendorApiDebug(apiData);
            }
        } catch (e) {
            apiSuccess = false;
        }
        if (!apiSuccess && !apiErrorMessage) {
            apiErrorMessage = 'Vendor API sync failed.';
        }
        return { apiSuccess, apiErrorMessage, apiDebug };
    }

    function showPersistedVendorFlashIfAny() {
        try {
            const raw = localStorage.getItem(VENDOR_FLASH_KEY);
            if (!raw) return;
            localStorage.removeItem(VENDOR_FLASH_KEY);
            const payload = JSON.parse(raw);
            if (payload && payload.message) {
                const persistent = payload.persistent !== undefined ? !!payload.persistent : !payload.isSuccess;
                showVendorTopMessage(payload.message, !!payload.isSuccess, {
                    persistent: persistent,
                    apiDebug: payload.apiDebug || null
                });
            }
        } catch (e) {}
    }

    function showVendorTopMessage(message, isSuccess, options) {
        if (!myDiv) return;
        options = options || {};
        const persistent = options.persistent !== undefined ? !!options.persistent : !isSuccess;
        myDiv.classList.remove('hidden', ...VENDOR_FLASH_BANNER_CLASSES);
        myDiv.classList.add(isSuccess ? 'text-green-600' : 'text-red-600');
        myDiv.classList.add(isSuccess ? 'bg-green-50' : 'bg-red-50');
        myDiv.classList.add('border', isSuccess ? 'border-green-200' : 'border-red-200', 'rounded-lg', 'px-4', 'py-3', 'shadow-sm');
        myDiv.innerHTML = '';
        const textSpan = document.createElement('span');
        textSpan.textContent = message || '';
        myDiv.appendChild(textSpan);
        if (!isSuccess && options.apiDebug) {
            const link = document.createElement('button');
            link.type = 'button';
            link.className = 'ml-2 underline font-semibold text-red-800 hover:text-red-950 whitespace-nowrap';
            link.textContent = 'View API request & response';
            link.addEventListener('click', function () {
                showVendorApiDebugModal(options.apiDebug);
            });
            myDiv.appendChild(link);
        }
        myDiv.setAttribute('role', isSuccess ? 'status' : 'alert');
        if (persistent) {
            myDiv.dataset.persistent = '1';
        } else {
            delete myDiv.dataset.persistent;
        }
        myDiv.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function reloadVendorListWithFlash(message, isSuccess, apiDebug) {
        persistVendorFlash(message, isSuccess, !isSuccess, apiDebug);
        window.location.reload();
    }

    function redirectToVendorListWithFlash(message, isSuccess) {
        persistVendorFlash(message, isSuccess, !isSuccess);
        window.location.href = '?page=vendors&action=list';
    }

    showPersistedVendorFlashIfAny();
    if (myDiv && myDiv.textContent.trim() !== '' && !localStorage.getItem(VENDOR_FLASH_KEY)) {
        const isError = myDiv.classList.contains('text-red-600');
        showVendorTopMessage(myDiv.textContent.trim(), !isError, { persistent: isError });
    }

    const vendorApiDebugCloseBtn = document.getElementById('vendorApiDebugCloseBtn');
    if (vendorApiDebugCloseBtn) {
        vendorApiDebugCloseBtn.addEventListener('click', closeVendorApiDebugModal);
    }
    const vendorApiDebugModal = document.getElementById('vendorApiDebugModal');
    if (vendorApiDebugModal) {
        vendorApiDebugModal.addEventListener('click', function (e) {
            if (e.target === vendorApiDebugModal) {
                closeVendorApiDebugModal();
            }
        });
    }

    const syncVendorsApiBtn = document.getElementById('sync-vendors-api-btn');
    if (syncVendorsApiBtn) {
        syncVendorsApiBtn.addEventListener('click', function () {
            if (syncVendorsApiBtn.dataset.loading === '1') return;
            syncVendorsApiBtn.dataset.loading = '1';
            syncVendorsApiBtn.disabled = true;
            const originalHtml = syncVendorsApiBtn.innerHTML;
            syncVendorsApiBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-xs opacity-95" aria-hidden="true"></i> Syncing...';
            showVendorTopMessage('Syncing vendors from API, please wait...', true);

            fetch('index.php?page=vendors&action=fetchAllVendors', {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (data && data.success) {
                    const inserted = Number(data.inserted || 0);
                    const updated = Number(data.updated || 0);
                    const total = Number(data.total || 0);
                    showVendorTopMessage('Vendor sync complete. Inserted: ' + inserted + ', Updated: ' + updated + ', Total: ' + total + '.', true);
                    setTimeout(function () {
                        window.location.reload();
                    }, 900);
                    return;
                }
                const errMsg = (data && data.message) ? data.message : 'Vendor sync failed.';
                reloadVendorListWithFlash(errMsg, false);
            })
            .catch(function () {
                const errMsg = 'Vendor sync request failed. Please try again.';
                reloadVendorListWithFlash(errMsg, false);
            })
            .finally(function () {
                syncVendorsApiBtn.dataset.loading = '0';
                syncVendorsApiBtn.disabled = false;
                syncVendorsApiBtn.innerHTML = originalHtml;
            });
        });
    }

    // Auto-clear only transient success banners (errors stay until page reload/navigation)
    if (myDiv) {
        setTimeout(() => {
            if (myDiv.dataset.persistent === '1') return;
            if (myDiv.textContent.trim() !== '') {
                myDiv.textContent = '';
                myDiv.classList.add('hidden');
                myDiv.classList.remove(...VENDOR_FLASH_BANNER_CLASSES);
                delete myDiv.dataset.persistent;
            }
        }, 8000);
    }

    // Function to limit input to six digits
    window.limitToTenDigits = function (input) {
        // Remove non-digit characters
        input.value = input.value.replace(/\D/g, '');

        // Allow only 8 digits
        if (input.value.length > 10) {
            input.value = input.value.slice(0, 10);
        }

        // Prevent leading zero
        if (input.value.startsWith('0')) {
            input.value = input.value.replace(/^0+/, '');
        }
    }

    let vendorNameExists = false;
    let emailExists = false;
    let phoneExists = false;
    let editVendorNameExists = false;
    let editEmailExists = false;
    let editPhoneExists = false;

    function buildVendorDuplicateUrl(action, paramName, value, excludeId) {
        let url = '?page=vendors&action=' + action + '&' + paramName + '=' + encodeURIComponent(value);
        if (excludeId && parseInt(excludeId, 10) > 0) {
            url += '&excludeId=' + encodeURIComponent(String(excludeId));
        }
        return url;
    }

    function destroyVendorBrokerSelect2(selectId) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return;
        }
        const $broker = jQuery('#' + selectId);
        if ($broker.length && $broker.hasClass('select2-hidden-accessible')) {
            $broker.select2('destroy');
        }
    }

    function initVendorBrokerSelect2(selectId, dropdownParentSelector, brokerId, brokerName) {
        if (!window.jQuery || !jQuery.fn.select2) {
            return;
        }

        const $broker = jQuery('#' + selectId);
        if (!$broker.length) {
            return;
        }

        destroyVendorBrokerSelect2(selectId);
        $broker.empty().append(new Option('Select broker...', '', true, false));

        const selectedId = parseInt(String(brokerId || '0'), 10);
        const selectedName = String(brokerName || '').trim();
        if (selectedId > 0 && selectedName !== '') {
            $broker.append(new Option(selectedName, String(selectedId), true, true));
        }

        $broker.select2({
            width: '100%',
            placeholder: 'Type at least 2 characters to search...',
            allowClear: true,
            minimumInputLength: 2,
            dropdownParent: jQuery(dropdownParentSelector),
            ajax: {
                url: 'index.php?page=brokers&action=searchBrokers',
                type: 'GET',
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    return { q: params.term || '' };
                },
                processResults: function (data) {
                    return { results: Array.isArray(data) ? data : [] };
                },
                cache: true
            }
        });
    }

    function bindVendorDuplicateCheck(inputEl, msgEl, action, paramName, minLen, existsFlagSetter, excludeIdGetter, duplicateMessage) {
        if (!inputEl || !msgEl) return;
        inputEl.addEventListener('keyup', () => {
            const value = inputEl.value.trim();
            if (value.length < minLen) {
                existsFlagSetter(false);
                msgEl.textContent = '';
                return;
            }

            const excludeId = excludeIdGetter ? excludeIdGetter() : 0;
            fetch(buildVendorDuplicateUrl(action, paramName, value, excludeId), { credentials: 'include' })
                .then(response => response.json())
                .then(data => {
                    if (data.exists) {
                        msgEl.textContent = duplicateMessage;
                        msgEl.style.color = 'red';
                        existsFlagSetter(true);
                    } else {
                        msgEl.textContent = '';
                        existsFlagSetter(false);
                    }
                })
                .catch(err => console.error('Duplicate check error:', err));
        });
    }

    bindVendorDuplicateCheck(
        document.getElementById('addVendorName'),
        document.getElementById('addVendorNameMsg'),
        'checkVendorName',
        'vendorName',
        2,
        (v) => { vendorNameExists = v; },
        null,
        'Vendor name already exists'
    );
    bindVendorDuplicateCheck(
        document.getElementById('addPhone'),
        document.getElementById('addPhoneMsg'),
        'checkPhoneNumber',
        'phone',
        10,
        (v) => { phoneExists = v; },
        null,
        'Phone number already exists'
    );
    bindVendorDuplicateCheck(
        document.getElementById('addEmail'),
        document.getElementById('addEmailMsg'),
        'checkEmail',
        'email',
        5,
        (v) => { emailExists = v; },
        null,
        'Email already exists'
    );
    bindVendorDuplicateCheck(
        document.getElementById('editVendorName'),
        document.getElementById('editVendorNameMsg'),
        'checkVendorName',
        'vendorName',
        2,
        (v) => { editVendorNameExists = v; },
        () => document.getElementById('editVendorId') ? document.getElementById('editVendorId').value : 0,
        'Vendor name already exists'
    );
    bindVendorDuplicateCheck(
        document.getElementById('editPhone'),
        document.getElementById('editPhoneMsg'),
        'checkPhoneNumber',
        'phone',
        10,
        (v) => { editPhoneExists = v; },
        () => document.getElementById('editVendorId') ? document.getElementById('editVendorId').value : 0,
        'Phone number already exists'
    );
    bindVendorDuplicateCheck(
        document.getElementById('editEmail'),
        document.getElementById('editEmailMsg'),
        'checkEmail',
        'email',
        5,
        (v) => { editEmailExists = v; },
        () => document.getElementById('editVendorId') ? document.getElementById('editVendorId').value : 0,
        'Email already exists'
    );

    function vendorDuplicateBlocked(isEdit) {
        if (isEdit) {
            return editVendorNameExists || editPhoneExists || editEmailExists;
        }
        return vendorNameExists || phoneExists || emailExists;
    }

    const VENDOR_API_ALLOWED_GROUPS = <?php echo json_encode(array_values($groupnameList), JSON_UNESCAPED_UNICODE); ?>;

    function getSelectedVendorGroups(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return [];
        return Array.from(container.querySelectorAll('input[type="checkbox"]:checked'))
            .map(function (cb) { return String(cb.value || '').trim(); })
            .filter(Boolean);
    }

    function vendorHasBookGroup(containerId) {
        return getSelectedVendorGroups(containerId).some(function (g) {
            return g.toLowerCase() === 'book';
        });
    }

    function updateVendorBookDiscountVisibility() {
        const addVisible = vendorHasBookGroup('groupname');
        const editVisible = vendorHasBookGroup('editGroupname');
        const addBlock = document.getElementById('addBookDiscountField');
        const editBlock = document.getElementById('editBookDiscountField');

        if (addBlock) {
            addBlock.classList.toggle('hidden', !addVisible);
        }
        if (editBlock) {
            editBlock.classList.toggle('hidden', !editVisible);
        }
    }

    function clearVendorDiscountIfNotBook(isEdit) {
        const containerId = isEdit ? 'editGroupname' : 'groupname';
        const inputId = isEdit ? 'edit_discount' : 'add_discount';
        if (vendorHasBookGroup(containerId)) {
            return;
        }
        const input = document.getElementById(inputId);
        if (input) {
            input.value = '';
        }
    }

    function bindVendorBookDiscountToggle(containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;
        container.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', updateVendorBookDiscountVisibility);
        });
    }

    function validateVendorApiFormFields(isEdit) {
        const nameInput = document.getElementById(isEdit ? 'editVendorName' : 'addVendorName');
        const nameMsg = document.getElementById(isEdit ? 'editVendorNameMsg' : 'addVendorNameMsg');
        const groupMsg = document.getElementById(isEdit ? 'editGroupnameMsg' : 'addGroupnameMsg');
        const groupContainerId = isEdit ? 'editGroupname' : 'groupname';
        let ok = true;

        const name = nameInput ? nameInput.value.trim() : '';
        if (!name) {
            if (nameMsg) nameMsg.textContent = 'Vendor name is required.';
            ok = false;
        } else if (nameMsg && !vendorDuplicateBlocked(isEdit)) {
            nameMsg.textContent = '';
        }

        const groups = getSelectedVendorGroups(groupContainerId);
        if (groups.length === 0) {
            if (groupMsg) groupMsg.textContent = 'Select at least one group name.';
            ok = false;
        } else {
            const allowed = new Set(VENDOR_API_ALLOWED_GROUPS.map(function (g) { return String(g).toLowerCase(); }));
            const invalid = groups.filter(function (g) { return !allowed.has(g.toLowerCase()); });
            if (invalid.length > 0) {
                if (groupMsg) groupMsg.textContent = 'Invalid group: ' + invalid.join(', ') + '.';
                ok = false;
            } else if (groupMsg) {
                groupMsg.textContent = '';
            }
        }

        return ok;
    }

    function bindVendorGroupCheckboxValidation(containerId, msgId) {
        const container = document.getElementById(containerId);
        const msgEl = document.getElementById(msgId);
        if (!container) return;
        container.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
            cb.addEventListener('change', function () {
                if (getSelectedVendorGroups(containerId).length > 0 && msgEl) {
                    msgEl.textContent = '';
                }
            });
        });
    }

    bindVendorGroupCheckboxValidation('groupname', 'addGroupnameMsg');
    bindVendorGroupCheckboxValidation('editGroupname', 'editGroupnameMsg');
    bindVendorBookDiscountToggle('groupname');
    bindVendorBookDiscountToggle('editGroupname');
    updateVendorBookDiscountVisibility();

    const addForm = document.getElementById('addVendorForm');
    addForm.addEventListener('submit', (e) => {
        if (!validateVendorApiFormFields(false)) {
            e.preventDefault();
            return;
        }
        if (vendorDuplicateBlocked(false)) {
            e.preventDefault();
            alert('Duplicate vendor details detected. Please use a different vendor name, phone, or email.');
        }
        clearVendorDiscountIfNotBook(false);
    });

    function fillTeamAgent(teamId, formType) {
        if(teamId === "") return;
        
        if(formType === 'AddForm') {
            // Get the select element
            var selectElement = document.getElementById('addTeam');
            // Get all selected options
            var selectedOptions = selectElement.selectedOptions;
            // Extract values into an array
            var selectedValues = Array.from(selectedOptions).map(option => option.value);
        }

        if(formType === 'AddForm') {
            fetch('?page=vendors&action=getTeamMembers&teamId=' + selectedValues.join(','))
            .then(response => response.json())
            .then(data => {
                let html = '';
                let teams = Array.isArray(data) ? data : [data]; // safe guard
                teams.forEach(team => {
                    html += `<optgroup label="${team.team_name}">`;
                    if (team.agents && team.agents.length > 0) {
                        team.agents.forEach(agent => {
                            html += `<option value="${agent.id}">${agent.name}</option>`;
                        });
                    }
                    html += `</optgroup>`;
                });

                $('#addTeamMember').html(html).trigger('change');
            })
            .catch(error => {
                console.error("Error loading team members:", error);
            });
        } else if(formType === 'EditForm') {
            // Get the select element
            var selectElement = document.getElementById('editTeam');
            // Get all selected options
            var selectedOptions = selectElement.selectedOptions;
            // Extract values into an array
            var selectedValues = Array.from(selectedOptions).map(option => option.value);

            fetch('?page=vendors&action=getTeamMembers&teamId=' + selectedValues.join(','))
            .then(response => response.json())
            .then(data => {
                let html = '';
                let teams = Array.isArray(data) ? data : [data]; // safe guard
                teams.forEach(team => {
                    html += `<optgroup label="${team.team_name}">`;
                    if (team.agents && team.agents.length > 0) {
                        team.agents.forEach(agent => {
                            html += `<option value="${agent.id}">${agent.name}</option>`;
                        });
                    }
                    html += `</optgroup>`;
                });
                $('#editTeamMember').html(html).trigger('change');
                if(document.getElementById("editAgentIds").value) {
                    document.getElementById("editTeamMember").value = document.getElementById("editAgentIds").value;
                }
            })
            .catch(error => {
                console.error("Error loading team members:", error);
            });
        }  else {
            fetch('?page=vendors&action=getTeamMembers&teamId=' + teamId)
            .then(response => response.json())
            .then(data => {
                let html = '';
                let teams = Array.isArray(data) ? data : [data]; // safe guard
                teams.forEach(team => {
                    html += `<optgroup label="${team.team_name}">`;
                    if (team.agents && team.agents.length > 0) {
                        team.agents.forEach(agent => {
                            html += `<option value="${agent.id}">${agent.name}</option>`;
                        });
                    }
                    html += `</optgroup>`;
                });

                $('#editTeamMember').html(html).trigger('change');
                document.getElementById("editTeamMember").value = formType;
            })
            .catch(error => {
                console.error("Error loading team members:", error);
            });
        }
    }
    
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

    const vendorFieldInputClass = 'w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none transition';

    function populateVendorStateSelect(stateSelect, states, selectedValue) {
        const blankOption = document.createElement('option');
        blankOption.value = '';
        blankOption.textContent = 'Select State';
        stateSelect.appendChild(blankOption);

        const stateRows = Array.isArray(states) ? states : [states];
        stateRows.forEach(function (state) {
            if (!state || !state.name) {
                return;
            }
            const option = document.createElement('option');
            option.value = state.name;
            option.textContent = state.name;
            stateSelect.appendChild(option);
        });

        const normalizedSelected = String(selectedValue || '').trim();
        if (normalizedSelected !== '') {
            stateSelect.value = normalizedSelected;
            if (stateSelect.value !== normalizedSelected) {
                const fallback = document.createElement('option');
                fallback.value = normalizedSelected;
                fallback.textContent = normalizedSelected;
                fallback.selected = true;
                stateSelect.appendChild(fallback);
            }
        } else {
            stateSelect.value = '';
        }
    }

    function fetchStates(countryId, formType) {
        if(countryId === "") return;
        if(countryId !== "India") {
            const inputClass = vendorFieldInputClass;
            if(formType === 'AddForm') {
                document.getElementById('addStateBlock').innerHTML = '<input type="text" class="' + inputClass + '" name="addState" id="addState" placeholder="State / province" />';
            } else {
                const previous = document.getElementById('editPreviousState') ? document.getElementById('editPreviousState').value : '';
                document.getElementById('editStateBlock').innerHTML = '<input type="text" class="' + inputClass + '" name="editState" id="editState" value="' + String(previous || '').replace(/"/g, '&quot;') + '" placeholder="State / province" />';
            }
            return;
        } else {
            // Fetch states from the server
            fetch('?page=vendors&action=getStates')
            .then(response => response.json())
            .then(data => {
                if (formType === 'AddForm') {
                    let stateSelect = document.createElement('select');
                    stateSelect.id = 'addState';
                    stateSelect.name = 'addState';
                    stateSelect.className = vendorFieldInputClass;
                    populateVendorStateSelect(stateSelect, data, '');
                    document.getElementById('addStateBlock').innerHTML = stateSelect.outerHTML;
                } else {
                    let stateSelect = document.createElement('select');
                    stateSelect.id = 'editState';
                    stateSelect.name = 'editState';
                    stateSelect.className = vendorFieldInputClass;
                    const previousState = document.getElementById('editPreviousState') ? document.getElementById('editPreviousState').value : '';
                    populateVendorStateSelect(stateSelect, data, previousState);
                    document.getElementById('editStateBlock').innerHTML = stateSelect.outerHTML;
                }
                return;
            });
        }
    }

    const openVendorPopupBtn = document.getElementById('open-vendor-popup-btn');
    const popupWrapper = document.getElementById('popup-wrapper');
    const modalSlider = document.getElementById('modal-slider');
    const cancelVendorBtn = document.getElementById('cancel-vendor-btn');
    const closeVendorPopupBtn = document.getElementById('close-vendor-popup-btn');

    function openVendorPopup() {
        popupWrapper.classList.remove('hidden');
        updateVendorBookDiscountVisibility();
        initVendorBrokerSelect2('add_broker_id', '#vendor-popup-panel', '', '');
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
        if (!validateVendorApiFormFields(false)) {
            return;
        }
        if (vendorDuplicateBlocked(false)) {
            alert('Duplicate vendor details detected. Please use a different vendor name, phone, or email.');
            return;
        }
        clearVendorDiscountIfNotBook(false);
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=addPost', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById("addVendorMsg");
            if (msgBox) msgBox.innerHTML = '';
            if (!data.success) {
                reloadVendorListWithFlash(data.message || 'Add vendor failed.', false);
                return;
            }
            const { apiSuccess, apiErrorMessage, apiDebug } = parseVendorApiResponse(data);
            if (!apiSuccess && data.api_response) {
                const syncError = apiErrorMessage || 'Exotic India API did not return a Vendor ID.';
                reloadVendorListWithFlash(
                    data.success ? ('Vendor saved locally, but Exotic India sync failed: ' + syncError) : syncError,
                    false,
                    apiDebug
                );
                return;
            }
            if (msgBox) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            setTimeout(() => {
                location.reload();
            }, 1500);
        });
    };

    let successModalTimer;
    // Delete Vendor
    document.addEventListener("DOMContentLoaded", () => {
        const deleteButtons = document.querySelectorAll(".delete-btn");
        
        deleteButtons.forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.preventDefault();
                const id = btn.getAttribute("data-id");
                window.closeAllMenus();
                if (!confirm("Are you sure you want to delete this record?")) return;

                fetch("?page=vendors&action=delete", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "id=" + id
                })
                .then(res => res.json())
                .then(data => {
                    if (!data.success) {
                        redirectToVendorListWithFlash(data.message || 'Delete vendor failed.', false);
                        return;
                    }
                    const title = document.getElementById("modalTitle");
                    var type = "error";
                    title.innerText = "Error ⚠️";
                    title.className = "text-2xl font-bold text-red-600 mb-4";
                    title.innerText = "Success 🎉";
                    title.className = "text-2xl font-bold text-green-600 mb-4";

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
                    redirectToVendorListWithFlash('Delete request failed. Please try again.', false);
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
        fetch("?page=vendors&action=vendorDetails&id=" + id)
        .then(res => res.json())
        .then(vendor => {
            if (vendor.status === "error") {
                alert(vendor.message);
                return;
            }
            editVendorNameExists = false;
            editEmailExists = false;
            editPhoneExists = false;
            ['editVendorNameMsg', 'editEmailMsg', 'editPhoneMsg'].forEach(function (msgId) {
                const el = document.getElementById(msgId);
                if (el) el.textContent = '';
            });
            // Populate form fields with vendor data
            document.getElementById("editVendorId").value   = vendor.id;
            document.getElementById("editVendorName").value = vendor.vendor_name;
            document.getElementById("editContactPerson").value = vendor.contact_name;
            document.getElementById("editEmail").value = vendor.vendor_email;
            document.getElementById("editCountryCode").value = vendor.country_code;
            document.getElementById("editPhone").value = vendor.vendor_phone;
            document.getElementById("editAltPhone").value = vendor.alt_phone;
            document.getElementById("edit_stock_replenishment_months").value =
                vendor.stock_replenishment_months != null && vendor.stock_replenishment_months !== 0
                    ? String(vendor.stock_replenishment_months)
                    : '';
            document.getElementById("editGstNumber").value = vendor.gst_number;
            document.getElementById("editPanNumber").value = vendor.pan_number;
            document.getElementById("editAddress").value = vendor.address;
            document.getElementById("editCity").value = vendor.city;
            document.getElementById("editCountry").value = vendor.country;
            document.getElementById("editWebpage").checked = String(vendor.webpage ?? '1') === '1';
            const selectedGroups = String(vendor.groupname || '')
                .split(',')
                .map(s => s.trim())
                .filter(Boolean);
            document.querySelectorAll('#editGroupname input[name="editGroupname[]"]').forEach(function (cb) {
                cb.checked = selectedGroups.includes(cb.value);
            });
            updateVendorBookDiscountVisibility();
            document.getElementById('edit_discount').value =
                vendor.discount != null && vendor.discount !== 0
                    ? String(vendor.discount)
                    : '';
            initVendorBrokerSelect2(
                'edit_broker_id',
                '#editVendorModal',
                vendor.broker_id,
                vendor.broker_name
            );
            
            document.getElementById("editAgentIds").value = vendor.agent_id;
            
            fillTeamAgent(vendor.teamIds, vendor.agent_id);

            //document.getElementById("editTeamMember").value = vendor.agent_id;

            document.getElementById("editPreviousState").value = vendor.state || '';

            if(vendor.country !== "India") {
                document.getElementById("editStateBlock").innerHTML = '<input type="text" class="' + vendorFieldInputClass + '" name="editState" id="editState" value="' + String(vendor.state || '').replace(/"/g, '&quot;') + '" placeholder="State / province" />';
            } else {
                fetch('?page=vendors&action=getStates')
                .then(response => response.json())
                .then(data => {
                    let stateSelect = document.createElement('select');
                    stateSelect.id = 'editState';
                    stateSelect.name = 'editState';
                    stateSelect.className = vendorFieldInputClass;
                    populateVendorStateSelect(stateSelect, data, vendor.state || '');
                    document.getElementById('editStateBlock').innerHTML = stateSelect.outerHTML;
                    document.getElementById('editPreviousState').value = vendor.state || '';
                    return;
                });
            }

            document.getElementById("editPostalCode").value = vendor.postal_code;
            document.getElementById("editRating").value = vendor.rating || '';
            document.getElementById("editTeam").value = vendor.team_id;
            document.getElementById("editNotes").value = vendor.notes;
            document.getElementById("editStatus").value = vendor.is_active;
            //vendor category
            // Fix for vendor.categories type
           
            const categorySelect = document.getElementById("editVendorCategory");
            // Ensure vendor.categories is always an array
            let categoriesArr = Array.isArray(vendor.categories)
                ? vendor.categories
                : (typeof vendor.categories === "string" && vendor.categories.length > 0
                    ? vendor.categories.split(",")
                    : []);

            // Pre-select options
            Array.from(categorySelect.options).forEach(option => {
                option.selected = categoriesArr.map(String).includes(String(option.value));
            });

            const teamSelect = document.getElementById("editTeam");
            // Ensure vendor.categories is always an array
            let teamArr = Array.isArray(vendor.teamIds)
                ? vendor.teamIds
                : (typeof vendor.teamIds === "string" && vendor.teamIds.length > 0
                    ? vendor.teamIds.split(",")
                    : []);

            // Pre-select options
            Array.from(teamSelect.options).forEach(option => {
                option.selected = teamArr.map(String).includes(String(option.value));
            });
            
            // Initialize Select2
            $(document).ready(function() {
                $('#editVendorCategory').select2({
                    placeholder: "Select Categories",
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });
                $('#editTeam').select2({
                    placeholder: "Select Teams",
                    allowClear: true,
                    width: '100%',
                    closeOnSelect: false
                });
            });

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
        if (!validateVendorApiFormFields(true)) {
            return;
        }
        if (vendorDuplicateBlocked(true)) {
            alert('Duplicate vendor details detected. Please use a different vendor name, phone, or email.');
            return;
        }
        clearVendorDiscountIfNotBook(true);
        var form = new FormData(this);        
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=addPost', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('editVendorMsg');
            if (msgBox) msgBox.innerHTML = '';
            if (!data.success) {
                reloadVendorListWithFlash(data.message || 'Edit vendor failed.', false);
                return;
            }
            const { apiSuccess, apiErrorMessage, apiDebug } = parseVendorApiResponse(data);
            if (!apiSuccess && data.api_response) {
                const syncError = apiErrorMessage || 'Exotic India API did not return a Vendor ID.';
                reloadVendorListWithFlash(
                    data.success ? ('Vendor saved locally, but Exotic India sync failed: ' + syncError) : syncError,
                    false,
                    apiDebug
                );
                return;
            }
            if (msgBox) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
            }
            setTimeout(() => {
                window.location.href = '?page=vendors&action=list';
            }, 1000);
        });
    };

    // Bank Details Modal Logic    
    const popupWrapperBD = document.getElementById('bankDetailModal');
    const modalSliderBD = document.getElementById('modal-slider-bd');
    const cancelBankDetailBtn = document.getElementById('cancel-bd-btn');
    const closeBankDetailsPopupBtn = document.getElementById('close-bd-popup-btn-bd');

    function openBankDtlsModal(id) {
        console.log("Id: " + id);
        closeAllMenus();
        fetch("?page=vendors&action=getBankDetails&id=" + id)
        .then(res => res.json())
        .then(bankdtls => {
            if (bankdtls.status === "error") {
                alert(bankdtls.message);
                return;
            }
            document.getElementById("vendor_id").value = id;
            
            if (bankdtls && bankdtls.account_name) {
                console.log(bankdtls);
                // Populate form fields with bank details data
                document.getElementById("account_name").value = bankdtls.account_name;
                document.getElementById("account_number").value = bankdtls.account_number;
                document.getElementById("ifsc_code").value = bankdtls.ifsc_code;
                document.getElementById("bank_name").value = bankdtls.bank_name;
                document.getElementById("branch_name").value = bankdtls.branch_name;
            } else {
                document.getElementById("account_name").value = "";
                document.getElementById("account_number").value = "";
                document.getElementById("ifsc_code").value = "";
                document.getElementById("bank_name").value = "";
                document.getElementById("branch_name").value = "";
            }

            popupWrapperBD.classList.remove('hidden');
            popupWrapperBD.classList.remove('active');
            setTimeout(() => {
                modalSliderBD.classList.remove('translate-x-full');
            }, 10);
        });
    }
    
    function closeBankDetailPopup() {
        modalSliderBD.classList.add('translate-x-full');
    }
    
    closeBankDetailsPopupBtn.addEventListener('click', closeBankDetailPopup);
    cancelBankDetailBtn.addEventListener('click', closeBankDetailPopup);
    

    document.getElementById('bankDetailForm').onsubmit = function(e) {
        e.preventDefault();
        var form = new FormData(this);
        var params = new URLSearchParams(form).toString();
        fetch('?page=vendors&action=bankDetails', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        })
        .then(r => r.json())
        .then(data => {
            var msgBox = document.getElementById('bankDetailMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = `<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">
                                    ✅ ${data.message}
                </div>`;
                msgBox.focus();
                msgBox.scrollIntoView({ behavior: "smooth", block: "center" });
                setTimeout(() => {
                    window.location.href = '?page=vendors&action=list';
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

    $(document).ready(function() {
        $('#addVendorCategory').select2({
            placeholder: "Select Categories",
            allowClear: true,
            width: '400',
            closeOnSelect: false
        });
    });
    $(document).ready(function() {
        $('#addTeam').select2({
            placeholder: "Select Teams",
            allowClear: true,
            width: '400',
            closeOnSelect: false
        });
    });
</script>