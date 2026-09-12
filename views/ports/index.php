<?php
$searchValue = htmlspecialchars((string) ($search ?? ''), ENT_QUOTES, 'UTF-8');
$statusValue = (string) ($status_filter ?? '');
$portTypeFilterValue = (string) ($port_type_filter ?? '');
$currentPage = max(1, (int) ($currentPage ?? 1));
$totalPages = max(1, (int) ($totalPages ?? 1));
$limit = (int) ($limit ?? 20);
$totalRecords = (int) ($totalRecords ?? 0);
$portTypes = is_array($port_types ?? null) ? $port_types : [];
$countryList = is_array($countryList ?? null) ? $countryList : [];
$defaultCountryId = (int) ($default_country_id ?? 0);
$queryBase = [
    'page' => 'ports',
    'action' => 'list',
    'search_text' => (string) ($search ?? ''),
    'status_filter' => $statusValue,
    'port_type_filter' => $portTypeFilterValue,
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
                        <i class="fas fa-plane-departure text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Master data · Shipping PORTS</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Shipping <span class="text-amber-800">PORTS</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Manage sea, air, inland, and dry ports used for shipping — including city, country, and PIN.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center gap-3 flex-wrap">
                <button type="button" id="openPortModalBtn"
                    class="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add port
                </button>
            </div>
        </div>
    </div>

    <div id="portAlert" class="hidden rounded-xl border px-4 py-3 text-sm font-medium"></div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Search &amp; filters</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Find ports by name, code, city, country, PIN, type, or status.</p>
                </div>
            </div>
        </div>
        <form method="get" id="filterForm" class="p-5">
            <input type="hidden" name="page" value="ports">
            <input type="hidden" name="action" value="list">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-x-5 gap-y-4">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Search</label>
                    <input type="text" name="search_text" placeholder="Search by name, code, city, country, or PIN"
                        class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 placeholder:text-gray-400 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition"
                        value="<?php echo $searchValue; ?>" autocomplete="off">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Port type</label>
                    <select name="port_type_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All types</option>
                        <?php foreach ($portTypes as $typeKey => $typeLabel): ?>
                            <option value="<?php echo htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $portTypeFilterValue === (string) $typeKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="status_filter" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="">All status</option>
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
                    onclick="window.location='?page=ports&action=list';">
                    Reset
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden">
        <div class="p-6">
            <div class="mb-4 flex items-center justify-between gap-3">
                <div class="text-sm text-gray-600">
                    Total ports: <span class="font-semibold text-gray-900"><?php echo number_format($totalRecords); ?></span>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-left">
                    <thead>
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">#</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Type</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Port</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Code</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">City</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Country</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">PIN</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Action</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                    <?php if (!empty($ports)): ?>
                        <?php $counter = ($currentPage - 1) * $limit; ?>
                        <?php foreach ($ports as $row): ?>
                            <?php
                            $id = (int) ($row['id'] ?? 0);
                            $portName = (string) ($row['port_name'] ?? '');
                            $portCode = (string) ($row['port_code'] ?? '');
                            $portType = (string) ($row['port_type'] ?? '');
                            $portTypeLabel = (string) ($row['port_type_label'] ?? $portType);
                            $city = (string) ($row['city'] ?? '');
                            $countryName = (string) ($row['country_name'] ?? '');
                            $countryCode = (string) ($row['country_code'] ?? '');
                            $countryId = (int) ($row['country_id'] ?? 0);
                            $pincode = (string) ($row['pincode'] ?? '');
                            $active = (int) ($row['is_active'] ?? 0) === 1;
                            $countryDisplay = $countryName;
                            if ($countryCode !== '') {
                                $countryDisplay = $countryName !== '' ? $countryName . ' (' . $countryCode . ')' : $countryCode;
                            }
                            ?>
                            <tr class="hover:bg-amber-50/40 transition-colors">
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo ++$counter; ?></td>
                                <td class="px-5 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($portTypeLabel, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($portName, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm font-mono font-semibold text-gray-900"><?php echo htmlspecialchars($portCode, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-800"><?php echo htmlspecialchars($city, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($countryDisplay !== '' ? $countryDisplay : '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm font-mono text-gray-800"><?php echo htmlspecialchars($pincode, ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="px-5 py-4 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold <?php echo $active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'; ?>">
                                        <?php echo $active ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-sm text-right whitespace-nowrap">
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-700 hover:bg-gray-50"
                                        onclick='openPortModal(<?php echo json_encode([
                                            'id' => $id,
                                            'port_name' => $portName,
                                            'port_code' => $portCode,
                                            'port_type' => $portType,
                                            'city' => $city,
                                            'country_id' => $countryId,
                                            'pincode' => $pincode,
                                            'is_active' => $active ? 1 : 0,
                                        ], JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                        Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-3 py-1.5 text-xs font-semibold text-amber-700 hover:bg-amber-50"
                                        onclick="setPortStatus(<?php echo $id; ?>, <?php echo $active ? 0 : 1; ?>)">
                                        <?php echo $active ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50"
                                        onclick="askDeletePort(<?php echo $id; ?>, <?php echo json_encode($portName, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="9" class="px-5 py-12 text-center text-sm text-gray-500">No ports found.</td>
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

<div id="portModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-lg rounded-2xl bg-white shadow-xl max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b px-6 py-4">
            <h2 id="portModalTitle" class="text-lg font-semibold text-gray-900">Add port</h2>
            <button type="button" onclick="closePortModal()" class="text-gray-400 hover:text-gray-700">✕</button>
        </div>
        <form id="portForm" class="space-y-4 px-6 py-5">
            <input type="hidden" name="id" id="port_id">
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Port type</label>
                <select name="port_type" id="port_type" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="">Select port type</option>
                    <?php foreach ($portTypes as $typeKey => $typeLabel): ?>
                        <option value="<?php echo htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Port name</label>
                <input type="text" name="port_name" id="port_name" required maxlength="255"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                    placeholder="e.g. Nhava Sheva / Jawaharlal Nehru Port">
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Port code</label>
                <input type="text" name="port_code" id="port_code" required maxlength="20"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono uppercase focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                    placeholder="e.g. INNSA1">
                <span id="portCodeMsg" class="text-sm text-red-500"></span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">City</label>
                    <input type="text" name="city" id="port_city" required maxlength="120"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                        placeholder="e.g. Navi Mumbai">
                </div>
                <div>
                    <label class="mb-1 block text-sm font-semibold text-gray-700">PIN / Postal code</label>
                    <input type="text" name="pincode" id="port_pincode" required maxlength="12"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none"
                        placeholder="e.g. 400707">
                </div>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Country</label>
                <select name="country_id" id="port_country_id" required
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="">Select country</option>
                    <?php foreach ($countryList as $country): ?>
                        <?php
                        $optionId = (int) ($country['id'] ?? 0);
                        $optionName = (string) ($country['name'] ?? '');
                        $optionCode = (string) ($country['country_code'] ?? '');
                        $optionLabel = $optionName;
                        if ($optionCode !== '') {
                            $optionLabel .= ' (' . $optionCode . ')';
                        }
                        ?>
                        <option value="<?php echo $optionId; ?>"><?php echo htmlspecialchars($optionLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-gray-700">Status</label>
                <select name="is_active" id="port_is_active"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/20 outline-none">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="flex justify-end gap-3 border-t pt-4">
                <button type="button" onclick="closePortModal()" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
                <button type="submit" id="portSaveBtn" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Save port</button>
            </div>
        </form>
    </div>
</div>

<div id="portConfirmModal" class="fixed inset-0 z-[60] hidden items-center justify-center bg-black/50 px-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl p-6 text-center">
        <h3 id="portConfirmTitle" class="text-lg font-semibold text-gray-900 mb-2">Confirm</h3>
        <p id="portConfirmMessage" class="text-sm text-gray-600 mb-6">Are you sure?</p>
        <div class="flex justify-center gap-3">
            <button type="button" id="portConfirmCancel" class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="button" id="portConfirmOk" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Delete</button>
        </div>
    </div>
</div>

<script>
let portCodeExists = false;
let pendingDeleteId = 0;
const defaultCountryId = <?php echo (int) $defaultCountryId; ?>;

function showPortAlert(message, success) {
    const box = document.getElementById('portAlert');
    if (!box) return;
    box.textContent = message || '';
    box.classList.remove('hidden', 'border-green-200', 'bg-green-50', 'text-green-700', 'border-red-200', 'bg-red-50', 'text-red-700');
    box.classList.add(success ? 'border-green-200' : 'border-red-200', success ? 'bg-green-50' : 'bg-red-50', success ? 'text-green-700' : 'text-red-700');
}

function postPortAction(action, body) {
    return fetch('index.php?page=ports&action=' + action, {
        method: 'POST',
        credentials: 'same-origin',
        body: body
    }).then(function (res) { return res.json(); });
}

function openPortModal(port) {
    port = port || {};
    portCodeExists = false;
    const nameMsg = document.getElementById('portCodeMsg');
    if (nameMsg) nameMsg.textContent = '';
    document.getElementById('portModalTitle').textContent = port.id ? 'Edit port' : 'Add port';
    document.getElementById('port_id').value = port.id || '';
    document.getElementById('port_type').value = port.port_type || '';
    document.getElementById('port_name').value = port.port_name || '';
    document.getElementById('port_code').value = port.port_code || '';
    document.getElementById('port_city').value = port.city || '';
    document.getElementById('port_pincode').value = port.pincode || '';
    document.getElementById('port_country_id').value = port.country_id
        ? String(port.country_id)
        : (defaultCountryId ? String(defaultCountryId) : '');
    document.getElementById('port_is_active').value = port.is_active != null ? String(port.is_active) : '1';
    document.getElementById('portModal').classList.remove('hidden');
    document.getElementById('portModal').classList.add('flex');
    setTimeout(function () { document.getElementById('port_name').focus(); }, 50);
}

function closePortModal() {
    document.getElementById('portModal').classList.add('hidden');
    document.getElementById('portModal').classList.remove('flex');
}

function checkPortCodeDuplicate() {
    const code = document.getElementById('port_code').value.trim();
    const msgEl = document.getElementById('portCodeMsg');
    if (code.length < 2) {
        portCodeExists = false;
        if (msgEl) msgEl.textContent = '';
        return;
    }
    const excludeId = document.getElementById('port_id').value || 0;
    let url = 'index.php?page=ports&action=checkCode&port_code=' + encodeURIComponent(code);
    if (excludeId && parseInt(excludeId, 10) > 0) {
        url += '&excludeId=' + encodeURIComponent(String(excludeId));
    }
    fetch(url, { credentials: 'same-origin' })
        .then(function (res) { return res.json(); })
        .then(function (data) {
            if (data.exists) {
                portCodeExists = true;
                if (msgEl) msgEl.textContent = 'This port code already exists.';
            } else {
                portCodeExists = false;
                if (msgEl) msgEl.textContent = '';
            }
        })
        .catch(function () {});
}

document.getElementById('openPortModalBtn')?.addEventListener('click', function () {
    openPortModal();
});

document.getElementById('port_code')?.addEventListener('keyup', checkPortCodeDuplicate);
document.getElementById('port_code')?.addEventListener('blur', function () {
    this.value = this.value.trim().toUpperCase();
    checkPortCodeDuplicate();
});

document.getElementById('portForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    if (portCodeExists) {
        showPortAlert('This port code already exists.', false);
        return;
    }
    const form = new FormData(this);
    const btn = document.getElementById('portSaveBtn');
    const oldLabel = btn ? btn.textContent : '';
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Saving...';
    }
    postPortAction('save', form).then(function (res) {
        showPortAlert(res.message || (res.success ? 'Port saved.' : 'Could not save port.'), !!res.success);
        if (res.success) {
            closePortModal();
            setTimeout(function () { window.location.reload(); }, 700);
        }
    }).catch(function () {
        showPortAlert('Could not save port.', false);
    }).finally(function () {
        if (btn) {
            btn.disabled = false;
            btn.textContent = oldLabel;
        }
    });
});

function setPortStatus(id, isActive) {
    const form = new FormData();
    form.append('id', id);
    form.append('is_active', isActive);
    postPortAction('status', form).then(function (res) {
        showPortAlert(res.message || 'Status updated.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showPortAlert('Could not update status.', false);
    });
}

function askDeletePort(id, name) {
    pendingDeleteId = id;
    document.getElementById('portConfirmTitle').textContent = 'Delete port';
    document.getElementById('portConfirmMessage').textContent = 'Delete port "' + (name || '') + '"? Existing invoices keep the saved port text.';
    document.getElementById('portConfirmModal').classList.remove('hidden');
    document.getElementById('portConfirmModal').classList.add('flex');
}

function closePortConfirmModal() {
    pendingDeleteId = 0;
    document.getElementById('portConfirmModal').classList.add('hidden');
    document.getElementById('portConfirmModal').classList.remove('flex');
}

document.getElementById('portConfirmCancel')?.addEventListener('click', closePortConfirmModal);
document.getElementById('portConfirmOk')?.addEventListener('click', function () {
    const id = pendingDeleteId;
    closePortConfirmModal();
    if (!id) return;
    const form = new FormData();
    form.append('id', id);
    postPortAction('delete', form).then(function (res) {
        showPortAlert(res.message || 'Delete complete.', !!res.success);
        if (res.success) setTimeout(function () { window.location.reload(); }, 700);
    }).catch(function () {
        showPortAlert('Could not delete port.', false);
    });
});
</script>
