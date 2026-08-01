<?php
function broker_nav_query($search, $status_filter, array $extra = [])
{
    $params = array_merge([
        'page' => 'brokers',
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
$stateList = is_array($stateList ?? null) ? $stateList : [];

function broker_location_label(array $location): string
{
    $state = trim((string) ($location['state'] ?? ''));
    $zone = trim((string) ($location['zone'] ?? ''));
    if ($state !== '' && $zone !== '') {
        return $state . ' / ' . $zone;
    }
    if ($state !== '') {
        return $state;
    }
    if ($zone !== '') {
        return $zone;
    }

    return '';
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-user-tie text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Master · Brokers</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Broker <span class="text-amber-800">master</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage brokers used on publisher records: name, locations (state and zone), and active status.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <a href="?page=publishers&action=list"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-800 text-sm font-semibold shadow-sm hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap">
                    <i class="fas fa-book-open text-xs opacity-95" aria-hidden="true"></i>
                    Publisher listing
                </a>
                <button type="button" id="open-broker-popup-btn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add broker
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="brokers">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by broker name, state, or zone"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo htmlspecialchars($search ?? '', ENT_QUOTES, 'UTF-8'); ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="" <?php echo (($status_filter ?? '') === '') ? 'selected' : ''; ?>>All status</option>
                        <option value="1" <?php echo (($status_filter ?? '') === '1') ? 'selected' : ''; ?>>Active</option>
                        <option value="0" <?php echo (($status_filter ?? '') === '0') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filters
                </button>
                <button type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition"
                    onclick="window.location='?page=brokers&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm ring-1 ring-gray-900/[0.03]">
        <div class="p-6 overflow-visible">
                <table id="broker-list-table" class="w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Broker name</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Locations</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Updated</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($brokers_data)): ?>
                        <?php $counter = ($page - 1) * $limit; ?>
                        <?php foreach ($brokers_data as $row): ?>
                            <?php
                            $isActive = !empty($row['is_active']);
                            $statusClass = $isActive
                                ? 'bg-emerald-50 text-emerald-800 ring-emerald-600/20'
                                : 'bg-amber-50 text-amber-900 ring-amber-600/25';
                            $updatedAt = $row['updated_at'] ?? $row['created_at'] ?? '';
                            $updatedLabel = $updatedAt ? date('d M Y', strtotime($updatedAt)) : '—';
                            $publisherCount = (int) ($row['publisher_count'] ?? 0);
                            $isMapped = $publisherCount > 0;
                            $locations = is_array($row['locations'] ?? null) ? $row['locations'] : [];
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-700"><?= ++$counter ?></td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900"><?= htmlspecialchars((string) ($row['broker_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700">
                                    <?php if ($locations !== []): ?>
                                        <div class="flex flex-wrap gap-1.5">
                                            <?php foreach ($locations as $location): ?>
                                                <?php
                                                $locationLabel = broker_location_label($location);
                                                if ($locationLabel === '') {
                                                    continue;
                                                }
                                                ?>
                                                <span class="inline-flex items-center rounded-full border border-amber-200 bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-900">
                                                    <?= htmlspecialchars($locationLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm text-gray-600"><?= htmlspecialchars($updatedLabel, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ring-1 ring-inset <?= $statusClass ?>">
                                        <?= $isActive ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="menu-wrapper">
                                        <button type="button" class="menu-button" aria-label="Broker actions">&#x22EE;</button>
                                        <ul class="menu-popup text-left">
                                            <li class="broker-edit-btn" data-id="<?= (int) $row['id'] ?>"><i class="fa-solid fa-pencil"></i> Edit</li>
                                            <?php if ($isMapped): ?>
                                                <li class="text-gray-400 cursor-not-allowed" title="Assigned to <?= (int) $publisherCount ?> publisher(s)">
                                                    <i class="fa-solid fa-ban"></i> Deactivate (mapped)
                                                </li>
                                                <li class="text-gray-400 cursor-not-allowed" title="Assigned to <?= (int) $publisherCount ?> publisher(s)">
                                                    <i class="fa-solid fa-trash"></i> Delete (mapped)
                                                </li>
                                            <?php else: ?>
                                                <li class="deactivate-btn" data-id="<?php echo (int) $row['id']; ?>" data-name="<?php echo htmlspecialchars((string) ($row['broker_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-ban"></i> Deactivate</li>
                                                <li class="permanent-delete-btn text-red-700" data-id="<?php echo (int) $row['id']; ?>" data-name="<?php echo htmlspecialchars((string) ($row['broker_name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><i class="fa-solid fa-trash"></i> Delete permanently</li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center text-gray-500">
                                <p class="text-base font-medium text-gray-900">No brokers match</p>
                                <p class="mt-1 text-sm text-gray-500">Try changing filters or add a new broker.</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
        </div>
    </div>

    <?php if (!empty($brokers_data)): ?>
        <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm p-4">
            <div class="flex flex-wrap items-center justify-center gap-3 text-sm text-gray-600">
                <?php if ($total_pages > 1): ?>
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if ($page <= 1) { echo 'opacity-50 pointer-events-none'; } ?>"
                        href="?<?= htmlspecialchars(broker_nav_query($search ?? '', $status_filter ?? '', ['page_no' => max(1, $page - $slot_size), 'limit' => $limit])) ?>">
                        &laquo; Prev
                    </a>
                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <a class="inline-flex items-center px-3 py-1.5 rounded-lg <?= $i === $page ? 'bg-amber-600 text-white font-bold' : 'border border-gray-300 bg-white text-gray-700 hover:bg-gray-50' ?>"
                            href="?<?= htmlspecialchars(broker_nav_query($search ?? '', $status_filter ?? '', ['page_no' => $i, 'limit' => $limit])) ?>">
                            <?= (int) $i ?>
                        </a>
                    <?php endfor; ?>
                    <a class="inline-flex items-center px-3 py-1.5 rounded-lg border border-gray-300 bg-white hover:bg-gray-50 <?php if ($page >= $total_pages) { echo 'opacity-50 pointer-events-none'; } ?>"
                        href="?<?= htmlspecialchars(broker_nav_query($search ?? '', $status_filter ?? '', ['page_no' => min($total_pages, $page + $slot_size), 'limit' => $limit])) ?>">
                        Next &raquo;
                    </a>
                <?php endif; ?>
                <select class="px-2 py-1.5 rounded-lg border border-gray-300 bg-white text-gray-700"
                    onchange="location.href='?<?= htmlspecialchars(broker_nav_query($search ?? '', $status_filter ?? '', ['page_no' => 1])) ?>&limit=' + encodeURIComponent(this.value);">
                    <?php foreach ([10, 20, 50, 100] as $opt): ?>
                        <option value="<?= (int) $opt ?>" <?= (int) $limit === (int) $opt ? 'selected' : '' ?>><?= (int) $opt ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
$stateOptionsHtml = '<option value="">Select state...</option>';
foreach ($stateList as $item) {
    $stateName = htmlspecialchars((string) ($item['name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $stateOptionsHtml .= '<option value="' . $stateName . '">' . $stateName . '</option>';
}
?>

<div id="popup-wrapper" class="hidden">
    <div id="popup-overlay" class="fixed inset-0 bg-black bg-opacity-25 z-40"></div>
    <div id="modal-slider" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-broker-popup-btn" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Add broker</h2>
                    <div id="addBrokerMsg" class="text-sm font-bold"></div>
                    <form id="addBrokerForm">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Broker name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="addBrokerName" id="addBrokerName" placeholder="Broker name" />
                            </div>
                            <section class="rounded-xl border-2 border-amber-300/80 bg-gradient-to-br from-amber-50 via-amber-50/40 to-white p-4 space-y-4 shadow-sm ring-1 ring-amber-200/60">
                                <div class="flex items-start justify-between gap-3 border-b border-amber-200/70 pb-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 shadow-sm">
                                            <i class="fas fa-location-dot text-sm" aria-hidden="true"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-amber-950">Locations</h3>
                                            <p class="text-xs text-amber-900/70 mt-0.5">Add state and zone combinations for this broker.</p>
                                        </div>
                                    </div>
                                    <button type="button" id="addBrokerLocationRowBtn"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100/80">
                                        <i class="fas fa-plus text-[10px]" aria-hidden="true"></i>
                                        Add location
                                    </button>
                                </div>
                                <div id="addBrokerLocationsList" class="space-y-3"></div>
                                <p class="text-xs text-amber-900/60">The first location row is shown by default. Use <span class="font-semibold">Add location</span> for more.</p>
                            </section>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="addStatus" id="addStatus">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-center items-center gap-4 pt-6 border-t mt-6">
                            <button type="button" id="cancel-broker-btn" class="action-btn cancel-btn">Back</button>
                            <button type="submit" class="action-btn save-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade hidden" id="editBrokerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
    <div id="modal-slider-edit" class="popup-transition fixed top-0 right-0 h-full flex transform translate-x-full z-50" style="width: 35%; min-width: 400px;">
        <div class="flex-shrink-0 flex items-start pt-5">
            <button id="close-broker-popup-btn-edit" type="button" class="bg-white text-gray-800 hover:bg-gray-100 transition flex items-center justify-center shadow-lg" style="width: 61px; height: 61px; border-top-left-radius: 8px; border-bottom-left-radius: 8px;">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="h-full bg-white shadow-2xl" style="width: 100%;">
            <div class="h-full w-full overflow-y-auto">
                <div class="p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 pb-6 border-b">Edit broker</h2>
                    <div id="editBrokerMsg"></div>
                    <form id="editBrokerForm">
                        <input type="hidden" id="editId" name="id" value="">
                        <div class="pt-4 space-y-4">
                            <div>
                                <label class="text-sm font-medium text-gray-700">Broker name <span class="text-red-500">*</span></label>
                                <input type="text" class="form-input w-full mt-1" required name="editBrokerName" id="editBrokerName" />
                            </div>
                            <section class="rounded-xl border-2 border-amber-300/80 bg-gradient-to-br from-amber-50 via-amber-50/40 to-white p-4 space-y-4 shadow-sm ring-1 ring-amber-200/60">
                                <div class="flex items-start justify-between gap-3 border-b border-amber-200/70 pb-3">
                                    <div class="flex items-start gap-3 min-w-0">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700 shadow-sm">
                                            <i class="fas fa-location-dot text-sm" aria-hidden="true"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <h3 class="text-sm font-bold text-amber-950">Locations</h3>
                                            <p class="text-xs text-amber-900/70 mt-0.5">Update state and zone combinations for this broker.</p>
                                        </div>
                                    </div>
                                    <button type="button" id="editBrokerLocationRowBtn"
                                        class="inline-flex shrink-0 items-center gap-1 rounded-lg border border-amber-300 bg-white px-3 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100/80">
                                        <i class="fas fa-plus text-[10px]" aria-hidden="true"></i>
                                        Add location
                                    </button>
                                </div>
                                <div id="editBrokerLocationsList" class="space-y-3"></div>
                                <p class="text-xs text-amber-900/60">Each row is one state and zone combination.</p>
                            </section>
                            <div>
                                <label class="text-sm font-medium text-gray-700">Status <span class="text-red-500">*</span></label>
                                <select class="form-input w-full mt-1" required name="editStatus" id="editStatus">
                                    <option value="1">Active</option>
                                    <option value="0">Inactive</option>
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-center items-center gap-4 pt-6 border-t mt-6">
                            <button type="button" id="cancel-broker-btn-edit" class="action-btn cancel-btn">Back</button>
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
const brokerStateOptionsHtml = <?= json_encode($stateOptionsHtml, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const brokerMaxLocations = 20;

function buildBrokerLocationRowHtml(index, fieldPrefix) {
    return ''
        + '<div class="broker-location-row rounded-lg border border-amber-200 bg-white/90 p-3 space-y-3 shadow-sm" data-index="' + index + '">'
        + '  <div class="flex items-center justify-between gap-2">'
        + '    <span class="broker-location-row-label inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-amber-900">Location ' + (index + 1) + '</span>'
        + '    <button type="button" class="remove-broker-location-row text-xs font-semibold text-red-600 hover:text-red-700">Remove</button>'
        + '  </div>'
        + '  <div>'
        + '    <label class="text-xs font-medium text-gray-600">State</label>'
        + '    <select class="form-input w-full mt-1" name="' + fieldPrefix + '[' + index + '][state]">' + brokerStateOptionsHtml + '</select>'
        + '  </div>'
        + '  <div>'
        + '    <label class="text-xs font-medium text-gray-600">Zone</label>'
        + '    <input type="text" class="form-input w-full mt-1" name="' + fieldPrefix + '[' + index + '][zone]" placeholder="Zone" />'
        + '  </div>'
        + '</div>';
}

function renderBrokerLocationRows(containerId, locations, fieldPrefix) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }
    container.innerHTML = '';
    const rows = Array.isArray(locations) && locations.length ? locations : [{ state: '', zone: '' }];
    rows.forEach(function (location, index) {
        container.insertAdjacentHTML('beforeend', buildBrokerLocationRowHtml(index, fieldPrefix));
        const row = container.lastElementChild;
        const stateSelect = row ? row.querySelector('select') : null;
        const zoneInput = row ? row.querySelector('input[type="text"]') : null;
        if (stateSelect && location.state) {
            stateSelect.value = location.state;
        }
        if (zoneInput) {
            zoneInput.value = location.zone || '';
        }
    });
    bindBrokerLocationRowHandlers(container);
}

function bindBrokerLocationRowHandlers(container) {
    container.querySelectorAll('.remove-broker-location-row').forEach(function (btn) {
        btn.onclick = function () {
            const rows = container.querySelectorAll('.broker-location-row');
            if (rows.length <= 1) {
                const stateSelect = rows[0].querySelector('select');
                const zoneInput = rows[0].querySelector('input[type="text"]');
                if (stateSelect) stateSelect.value = '';
                if (zoneInput) zoneInput.value = '';
                return;
            }
            btn.closest('.broker-location-row').remove();
            reindexBrokerLocationRows(container);
        };
    });
}

function reindexBrokerLocationRows(container) {
    const fieldPrefix = container.id === 'editBrokerLocationsList' ? 'locations' : 'locations';
    container.querySelectorAll('.broker-location-row').forEach(function (row, index) {
        row.dataset.index = String(index);
        row.querySelector('.broker-location-row-label').textContent = 'Location ' + (index + 1);
        const stateSelect = row.querySelector('select');
        const zoneInput = row.querySelector('input[type="text"]');
        if (stateSelect) stateSelect.name = fieldPrefix + '[' + index + '][state]';
        if (zoneInput) zoneInput.name = fieldPrefix + '[' + index + '][zone]';
    });
}

function addBrokerLocationRow(containerId) {
    const container = document.getElementById(containerId);
    if (!container) {
        return;
    }
    const count = container.querySelectorAll('.broker-location-row').length;
    if (count >= brokerMaxLocations) {
        alert('Maximum ' + brokerMaxLocations + ' locations allowed.');
        return;
    }
    container.insertAdjacentHTML('beforeend', buildBrokerLocationRowHtml(count, 'locations'));
    bindBrokerLocationRowHandlers(container);
}

document.addEventListener('DOMContentLoaded', () => {
    const menuButtons = document.querySelectorAll('#broker-list-table .menu-button, .menu-wrapper .menu-button');
    window.currentOpenMenu = null;
    const menuMargin = 8;

    window.closeAllMenus = function () {
        if (window.currentOpenMenu) {
            window.currentOpenMenu.classList.remove('active');
            window.currentOpenMenu.removeAttribute('style');
            window.currentOpenMenu = null;
        }
        document.querySelectorAll('.menu-popup').forEach(function (menu) {
            menu.style.display = 'none';
        });
    };

    document.addEventListener('click', function (e) {
        if (e.target.closest('.menu-button') || e.target.closest('.menu-popup')) {
            return;
        }
        closeAllMenus();
    });

    menuButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();
            const dropdown = button.nextElementSibling;
            if (!dropdown) {
                return;
            }

            const isActive = dropdown.classList.contains('active');
            if (window.currentOpenMenu && window.currentOpenMenu !== dropdown) {
                closeAllMenus();
            }

            if (!isActive) {
                dropdown.style.display = 'block';
                const buttonRect = button.getBoundingClientRect();
                const dropdownWidth = dropdown.offsetWidth;
                const dropdownHeight = dropdown.offsetHeight;
                const viewportHeight = window.innerHeight;
                const viewportWidth = window.innerWidth;

                dropdown.style.position = 'fixed';
                dropdown.style.top = '';
                dropdown.style.left = '';
                dropdown.style.zIndex = '1000';
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
                window.currentOpenMenu = dropdown;
            } else {
                closeAllMenus();
            }
        });
    });

    const openBrokerPopupBtn = document.getElementById('open-broker-popup-btn');
    const popupWrapper = document.getElementById('popup-wrapper');
    const modalSlider = document.getElementById('modal-slider');
    const cancelBrokerBtn = document.getElementById('cancel-broker-btn');
    const closeBrokerPopupBtn = document.getElementById('close-broker-popup-btn');

    function openBrokerPopup() {
        document.getElementById('addBrokerForm').reset();
        document.getElementById('addBrokerMsg').innerHTML = '';
        renderBrokerLocationRows('addBrokerLocationsList', [{ state: '', zone: '' }], 'locations');
        popupWrapper.classList.remove('hidden');
        setTimeout(() => modalSlider.classList.remove('translate-x-full'), 10);
    }

    function closeBrokerPopup() {
        modalSlider.classList.add('translate-x-full');
    }

    modalSlider.addEventListener('transitionend', (event) => {
        if (event.propertyName === 'transform' && modalSlider.classList.contains('translate-x-full')) {
            popupWrapper.classList.add('hidden');
        }
    });

    openBrokerPopupBtn.addEventListener('click', openBrokerPopup);
    cancelBrokerBtn.addEventListener('click', closeBrokerPopup);
    closeBrokerPopupBtn.addEventListener('click', closeBrokerPopup);
    document.getElementById('addBrokerLocationRowBtn')?.addEventListener('click', function () {
        addBrokerLocationRow('addBrokerLocationsList');
    });
    document.getElementById('editBrokerLocationRowBtn')?.addEventListener('click', function () {
        addBrokerLocationRow('editBrokerLocationsList');
    });
    renderBrokerLocationRows('addBrokerLocationsList', [{ state: '', zone: '' }], 'locations');

    document.getElementById('addBrokerForm').onsubmit = function(e) {
        e.preventDefault();
        const form = new FormData(this);
        fetch('?page=brokers&action=addRecord', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: form
        })
        .then(r => r.json())
        .then(data => {
            const msgBox = document.getElementById('addBrokerMsg');
            msgBox.innerHTML = '';
            if (data.success) {
                msgBox.innerHTML = '<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">' + data.message + '</div>';
                setTimeout(() => location.reload(), 1500);
            } else {
                msgBox.innerHTML = '<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">' + data.message + '</div>';
            }
        });
    };

    document.querySelectorAll('.broker-edit-btn').forEach(function (item) {
        item.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            const id = parseInt(String(this.dataset.id || '0'), 10);
            if (id > 0) {
                openEditModal(id);
            }
        });
    });

    document.querySelectorAll('.deactivate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof closeAllMenus === 'function') {
                closeAllMenus();
            }
            const id = this.dataset.id;
            const name = this.dataset.name || 'this broker';
            if (!confirm('Deactivate ' + name + '?')) return;
            fetch('?page=brokers&action=deleteRecord', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message || (data.success ? 'Deactivated.' : 'Could not deactivate.'));
                if (data.success) location.reload();
            });
        });
    });

    document.querySelectorAll('.permanent-delete-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            if (typeof closeAllMenus === 'function') {
                closeAllMenus();
            }
            const id = this.dataset.id;
            const name = this.dataset.name || 'this broker';
            if (!confirm('Permanently delete ' + name + '? This cannot be undone.')) return;
            fetch('?page=brokers&action=permanentDelete', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: id})
            })
            .then(r => r.json())
            .then(data => {
                alert(data.message || (data.success ? 'Deleted.' : 'Could not delete.'));
                if (data.success) location.reload();
            });
        });
    });
});

function brokerFetchJson(url, options) {
    const opts = Object.assign({
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    }, options || {});
    opts.headers = Object.assign({
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }, (options && options.headers) ? options.headers : {});

    return fetch(url, opts).then(function (response) {
        if (!response.ok) {
            throw new Error('Request failed');
        }
        return response.json();
    });
}

function openEditModal(id) {
    if (typeof closeAllMenus === 'function') {
        closeAllMenus();
    }
    brokerFetchJson('?page=brokers&action=getDetails&id=' + encodeURIComponent(id))
        .then(function (data) {
            if (!data || !data.id) {
                alert(data && data.message ? data.message : 'Could not load broker.');
                return;
            }
            document.getElementById('editId').value = data.id;
            document.getElementById('editBrokerName').value = data.broker_name || '';
            document.getElementById('editStatus').value = String(data.is_active != null ? data.is_active : 1);
            renderBrokerLocationRows('editBrokerLocationsList', data.locations || [{ state: '', zone: '' }], 'locations');
            document.getElementById('editBrokerMsg').innerHTML = '';
            const modal = document.getElementById('editBrokerModal');
            modal.classList.remove('hidden');
            const slider = document.getElementById('modal-slider-edit');
            setTimeout(function () { slider.classList.remove('translate-x-full'); }, 10);
        })
        .catch(function () {
            alert('Could not load broker. Please refresh and try again.');
        });
}

const editSlider = document.getElementById('modal-slider-edit');
document.getElementById('cancel-broker-btn-edit').addEventListener('click', () => {
    editSlider.classList.add('translate-x-full');
});
document.getElementById('close-broker-popup-btn-edit').addEventListener('click', () => {
    editSlider.classList.add('translate-x-full');
});
editSlider.addEventListener('transitionend', (event) => {
    if (event.propertyName === 'transform' && editSlider.classList.contains('translate-x-full')) {
        document.getElementById('editBrokerModal').classList.add('hidden');
    }
});

document.getElementById('editBrokerForm').onsubmit = function(e) {
    e.preventDefault();
    const form = new FormData(this);
    fetch('?page=brokers&action=addRecord', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: form
    })
    .then(r => r.json())
    .then(data => {
        const msgBox = document.getElementById('editBrokerMsg');
        msgBox.innerHTML = '';
        if (data.success) {
            msgBox.innerHTML = '<div style="color: green; padding: 10px; background: #e0ffe0; border: 1px solid #0a0;">' + data.message + '</div>';
            setTimeout(() => location.reload(), 1500);
        } else {
            msgBox.innerHTML = '<div style="color: red; padding: 10px; background: #ffe0e0; border: 1px solid #a00;">' + data.message + '</div>';
        }
    });
};
</script>
