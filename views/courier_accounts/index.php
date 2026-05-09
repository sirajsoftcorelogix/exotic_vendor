<?php
$flash = $_SESSION['courier_account_flash'] ?? null;
if ($flash) {
    unset($_SESSION['courier_account_flash']);
}
$partners = $partners ?? [];
$accounts = $accounts ?? [];
$partnerId = (int)($partner_id ?? 0);
$accountCount = is_array($accounts) ? count($accounts) : 0;
$filtersPanelOpen = $partnerId > 0;
$caCredRows = 12;

function h($v)
{
    return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-id-card-alt text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Courier · Accounts &amp; credentials</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">Courier accounts</h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Map API accounts per partner (production/sandbox, regions). Store credential keys in the form below—encrypt at rest for production.
                </p>
                <p class="mt-2 text-xs text-amber-800/90 font-medium rounded-lg border border-amber-200/80 bg-amber-50/80 px-3 py-2 inline-block">
                    Note: secrets are stored as plain text today; plan encryption at rest for production.
                </p>
            </div>
            <div class="flex flex-col sm:flex-row shrink-0 lg:pl-4 lg:self-center gap-2">
                <button type="button" id="caBtnOpenAdd"
                    class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add account
                </button>
                <a href="?page=courier_partners&amp;action=list" class="inline-flex items-center justify-center gap-2 px-5 py-3 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition whitespace-nowrap">
                    <i class="fas fa-truck text-xs text-amber-600" aria-hidden="true"></i>
                    Courier partners
                </a>
            </div>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="rounded-xl border px-4 py-3 text-sm mb-6 <?php echo !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <?php echo h($flash['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <style>
        #ca-filters > summary { list-style: none; }
        #ca-filters > summary::-webkit-details-marker { display: none; }
        #ca-filters[open] > summary { border-bottom: 1px solid rgba(251, 243, 219, 0.85); }
        #ca-filters:not([open]) .caf-label-open { display: none; }
        #ca-filters[open] .caf-label-closed { display: none; }
        #ca-filters[open] .caf-chevron { transform: rotate(180deg); }
    </style>

    <details id="ca-filters" class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden mb-6 ring-1 ring-gray-900/[0.03]" <?php echo $filtersPanelOpen ? 'open' : ''; ?>>
        <summary class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 flex items-center justify-between gap-4 cursor-pointer">
            <div class="flex items-center gap-3 min-w-0">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                    <i class="fas fa-filter text-sm" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-gray-900">Filter by partner</h2>
                    <p class="text-xs text-gray-500 mt-0.5 hidden sm:block">Narrow accounts to one courier partner.</p>
                </div>
            </div>
            <span class="shrink-0 inline-flex items-center gap-2 text-xs font-semibold text-amber-800">
                <span class="caf-label-closed">Show</span>
                <span class="caf-label-open">Hide</span>
                <i class="caf-chevron fas fa-chevron-down text-[10px] transition-transform duration-200" aria-hidden="true"></i>
            </span>
        </summary>

        <form method="get" action="index.php" class="p-5">
            <input type="hidden" name="page" value="courier_accounts">
            <input type="hidden" name="action" value="list">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-x-5 gap-y-4">
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Partner</label>
                    <select name="partner_id" class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="0">All partners</option>
                        <?php foreach ($partners as $p): ?>
                            <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) $p['id'] === $partnerId ? 'selected' : ''; ?>>
                                <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center gap-3">
                <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-search text-xs opacity-90" aria-hidden="true"></i>
                    Apply filter
                </button>
                <a href="?page=courier_accounts&action=list" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg border border-gray-300 bg-white text-gray-700 text-sm font-medium hover:bg-gray-50 transition">
                    Reset
                </a>
            </div>
        </form>
    </details>

    <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
        <div class="overflow-x-auto">
            <table class="min-w-full text-left">
                <thead class="sticky top-0 z-10">
                    <tr class="bg-gray-50/95 border-b border-gray-200 text-xs font-semibold uppercase tracking-wider text-gray-600">
                        <th class="px-5 py-3.5 whitespace-nowrap">Partner</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Account</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Status</th>
                        <th class="px-5 py-3.5 whitespace-nowrap text-right">Priority</th>
                        <th class="px-5 py-3.5 whitespace-nowrap">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (!$accounts): ?>
                        <tr>
                            <td colspan="5" class="px-5 py-16 text-center">
                                <div class="mx-auto flex max-w-sm flex-col items-center">
                                    <span class="inline-flex h-14 w-14 items-center justify-center rounded-full bg-gray-100 text-gray-400 text-xl mb-4">
                                        <i class="fas fa-inbox" aria-hidden="true"></i>
                                    </span>
                                    <p class="text-base font-medium text-gray-900">No accounts found</p>
                                    <p class="mt-1 text-sm text-gray-500">Add an account or change the partner filter.</p>
                                    <button type="button" class="ca-open-add mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700">
                                        Add account
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $a): ?>
                            <?php
                                $credList = [];
                                foreach ($a['credentials'] ?? [] as $c) {
                                    $credList[] = [
                                        'cred_key' => (string) ($c['cred_key'] ?? ''),
                                        'cred_value' => (string) ($c['cred_value'] ?? ''),
                                        'is_secret' => (int) ($c['is_secret'] ?? 0),
                                    ];
                                }
                                $payload = [
                                    'id' => (int) $a['id'],
                                    'partner_id' => (int) $a['partner_id'],
                                    'account_code' => (string) $a['account_code'],
                                    'account_name' => (string) $a['account_name'],
                                    'is_active' => (int) $a['is_active'],
                                    'priority' => (int) $a['priority'],
                                    'tags_json' => (string) ($a['tags_json'] ?? ''),
                                    'notes' => (string) ($a['notes'] ?? ''),
                                    'credentials' => $credList,
                                ];
                                $payloadJson = json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
                            ?>
                            <tr class="odd:bg-white even:bg-gray-50/40 hover:bg-amber-50/50 transition-colors align-top">
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-900"><?php echo h($a['partner_name']); ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5"><?php echo h($a['partner_code']); ?></div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="font-semibold text-gray-900"><?php echo h($a['account_name']); ?></div>
                                    <div class="text-xs text-gray-500 mt-0.5 font-mono"><?php echo h($a['account_code']); ?></div>
                                </td>
                                <td class="px-5 py-4">
                                    <?php if ((int) $a['is_active'] === 1): ?>
                                        <span class="inline-flex rounded-full bg-green-100 px-3 py-1.5 text-xs font-semibold text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-5 py-4 text-right tabular-nums text-sm font-medium text-gray-800"><?php echo (int) $a['priority']; ?></td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <button type="button" class="ca-btn-edit inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-gray-800 hover:bg-gray-50 transition"
                                            data-account="<?php echo $payloadJson; ?>">
                                            <i class="fas fa-pen text-[10px] text-indigo-600" aria-hidden="true"></i>
                                            Edit
                                        </button>
                                        <form method="post" action="?page=courier_accounts&action=deleteAccount" class="inline" onsubmit="return confirm('Delete this account?');">
                                            <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50 transition">
                                                <i class="fas fa-trash-alt text-[10px]" aria-hidden="true"></i>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-xl border border-gray-200 bg-white px-4 py-4 shadow-sm ring-1 ring-gray-900/[0.03]">
        <p class="text-sm text-gray-600">
            <?php if ($partnerId > 0): ?>
                Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo $accountCount; ?></span> account<?php echo $accountCount === 1 ? '' : 's'; ?> for the selected partner.
            <?php else: ?>
                Showing <span class="font-medium text-gray-900 tabular-nums"><?php echo $accountCount; ?></span> account<?php echo $accountCount === 1 ? '' : 's'; ?> across all partners.
            <?php endif; ?>
        </p>
    </div>
</div>

<!-- Modal: Add / Edit account -->
<div id="courierAccountModal" class="fixed inset-0 z-[100] hidden opacity-0 transition-opacity duration-200" aria-hidden="true" role="dialog" aria-labelledby="caModalTitle">
    <div class="absolute inset-0 bg-gray-900/55 backdrop-blur-[2px] ca-modal-backdrop" aria-hidden="true"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 pointer-events-none overflow-y-auto">
        <div class="pointer-events-auto w-full max-w-2xl rounded-2xl border border-gray-200/90 bg-white shadow-2xl shadow-gray-900/20 ring-1 ring-black/5 my-8 overflow-hidden transform transition-all scale-95 opacity-0 ca-modal-panel max-h-[calc(100vh-4rem)] flex flex-col">
            <div class="relative px-6 pt-6 pb-4 border-b border-amber-100 bg-gradient-to-br from-amber-50/90 via-white to-slate-50/40 shrink-0">
                <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-amber-200/20 blur-3xl pointer-events-none -translate-y-1/2 translate-x-1/3" aria-hidden="true"></div>
                <div class="relative flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[11px] font-bold uppercase tracking-widest text-amber-800/80 mb-1">Courier account</p>
                        <h3 id="caModalTitle" class="text-xl font-bold text-gray-900 tracking-tight">Add account</h3>
                        <p id="caModalSubtitle" class="text-sm text-gray-500 mt-1">Choose partner, codes, and optional credential keys.</p>
                    </div>
                    <button type="button" class="ca-modal-close rounded-xl p-2 text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition -mr-1 -mt-1 shrink-0" aria-label="Close">
                        <i class="fas fa-times text-lg" aria-hidden="true"></i>
                    </button>
                </div>
            </div>

            <form method="post" action="?page=courier_accounts&action=saveAccount" id="caAccountForm" class="flex flex-col flex-1 min-h-0">
                <input type="hidden" name="id" id="ca_field_id" value="">

                <div class="px-6 py-5 space-y-5 overflow-y-auto flex-1">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label for="ca_field_partner" class="block text-xs font-semibold text-gray-600 mb-1.5">Partner <span class="text-red-500">*</span></label>
                            <select name="partner_id" id="ca_field_partner" required class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm bg-white shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                                <option value="">Select partner</option>
                                <?php foreach ($partners as $p): ?>
                                    <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) $p['id'] === $partnerId ? 'selected' : ''; ?>>
                                        <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="ca_field_code" class="block text-xs font-semibold text-gray-600 mb-1.5">Account code <span class="text-red-500">*</span></label>
                            <input type="text" name="account_code" id="ca_field_code" required autocomplete="off" placeholder="DHL_AU"
                                class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                        </div>
                        <div>
                            <label for="ca_field_status" class="block text-xs font-semibold text-gray-600 mb-1.5">Status</label>
                            <select name="is_active" id="ca_field_status" class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm bg-white shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                                <option value="1">Active</option>
                                <option value="0">Inactive</option>
                            </select>
                        </div>
                        <div class="sm:col-span-2">
                            <label for="ca_field_name" class="block text-xs font-semibold text-gray-600 mb-1.5">Account name <span class="text-red-500">*</span></label>
                            <input type="text" name="account_name" id="ca_field_name" required placeholder="DHL Account - Australia"
                                class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition">
                        </div>
                        <div>
                            <label for="ca_field_priority" class="block text-xs font-semibold text-gray-600 mb-1.5">Priority</label>
                            <input type="number" name="priority" id="ca_field_priority" value="100"
                                class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition tabular-nums">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="ca_field_tags" class="block text-xs font-semibold text-gray-600 mb-1.5">Tags JSON (optional)</label>
                            <input type="text" name="tags_json" id="ca_field_tags" placeholder='["AU","intl"]'
                                class="h-11 w-full rounded-xl border border-gray-300 px-3.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition font-mono text-sm">
                        </div>
                        <div class="sm:col-span-2">
                            <label for="ca_field_notes" class="block text-xs font-semibold text-gray-600 mb-1.5">Notes</label>
                            <textarea name="notes" id="ca_field_notes" rows="2" placeholder="Optional internal notes…"
                                class="w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm shadow-sm focus:border-amber-500 focus:ring-2 focus:ring-amber-500/25 transition resize-y min-h-[4rem]"></textarea>
                        </div>
                    </div>

                    <div class="rounded-xl border border-amber-100 bg-amber-50/50 px-4 py-3">
                        <div class="text-xs font-semibold text-gray-800 mb-2">Credentials (key / value)</div>
                        <p class="text-[11px] text-gray-500 mb-3">Up to <?php echo (int) $caCredRows; ?> rows. Leave key empty to skip.</p>
                        <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                            <?php for ($cr = 0; $cr < $caCredRows; $cr++): ?>
                                <div class="grid grid-cols-12 gap-2 items-center">
                                    <input type="text" name="cred_key[]" id="ca_cred_key_<?php echo $cr; ?>" placeholder="api_key"
                                        class="col-span-12 sm:col-span-4 h-10 rounded-lg border border-gray-300 px-2 text-xs sm:text-sm shadow-sm font-mono">
                                    <input type="text" name="cred_value[]" id="ca_cred_val_<?php echo $cr; ?>" placeholder="value"
                                        class="col-span-12 sm:col-span-6 h-10 rounded-lg border border-gray-300 px-2 text-xs sm:text-sm shadow-sm">
                                    <label class="col-span-12 sm:col-span-2 inline-flex items-center gap-2 text-xs text-gray-600 whitespace-nowrap">
                                        <input type="checkbox" name="cred_secret[<?php echo $cr; ?>]" id="ca_cred_secret_<?php echo $cr; ?>" value="1" class="h-4 w-4 rounded border-gray-300 text-amber-600 focus:ring-amber-500">
                                        Secret
                                    </label>
                                </div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/80 flex flex-col-reverse sm:flex-row sm:justify-end gap-2.5 shrink-0">
                    <button type="button" class="ca-modal-close h-11 px-5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button type="submit" id="caSubmitBtn" class="h-11 px-6 rounded-xl bg-gradient-to-b from-indigo-600 to-indigo-700 text-white text-sm font-semibold shadow-md hover:from-indigo-500 hover:to-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 transition">
                        Save account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
(function () {
    var modal = document.getElementById('courierAccountModal');
    var panel = modal ? modal.querySelector('.ca-modal-panel') : null;
    var form = document.getElementById('caAccountForm');
    var titleEl = document.getElementById('caModalTitle');
    var subtitleEl = document.getElementById('caModalSubtitle');
    var submitBtn = document.getElementById('caSubmitBtn');
    var CRED_N = <?php echo (int) $caCredRows; ?>;
    var defaultPartnerId = <?php echo (int) $partnerId; ?>;

    function openModal() {
        if (!modal || !panel) return;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        requestAnimationFrame(function () {
            modal.classList.remove('opacity-0');
            modal.classList.add('opacity-100');
            panel.classList.remove('scale-95', 'opacity-0');
            panel.classList.add('scale-100', 'opacity-100');
        });
        modal.setAttribute('aria-hidden', 'false');
        var first = document.getElementById('ca_field_partner');
        if (first) first.focus();
    }

    function closeModal() {
        if (!modal || !panel) return;
        modal.classList.add('opacity-0');
        modal.classList.remove('opacity-100');
        panel.classList.add('scale-95', 'opacity-0');
        panel.classList.remove('scale-100', 'opacity-100');
        document.body.classList.remove('overflow-hidden');
        setTimeout(function () {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
        }, 200);
    }

    function clearCredRows() {
        for (var i = 0; i < CRED_N; i++) {
            var k = document.getElementById('ca_cred_key_' + i);
            var v = document.getElementById('ca_cred_val_' + i);
            var s = document.getElementById('ca_cred_secret_' + i);
            if (k) k.value = '';
            if (v) v.value = '';
            if (s) s.checked = false;
        }
    }

    function fillCredRows(creds) {
        clearCredRows();
        if (!creds || !creds.length) return;
        for (var i = 0; i < CRED_N && i < creds.length; i++) {
            var row = creds[i];
            var k = document.getElementById('ca_cred_key_' + i);
            var v = document.getElementById('ca_cred_val_' + i);
            var s = document.getElementById('ca_cred_secret_' + i);
            if (k) k.value = row.cred_key || '';
            if (v) v.value = row.cred_value || '';
            if (s) s.checked = !!row.is_secret;
        }
        var extra = creds.length - CRED_N;
        if (extra > 0 && subtitleEl) {
            subtitleEl.textContent = 'Note: only the first ' + CRED_N + ' credentials can be edited in this form (' + extra + ' more exist in DB — contact admin).';
        }
    }

    function resetFormAdd() {
        form.reset();
        document.getElementById('ca_field_id').value = '';
        titleEl.textContent = 'Add account';
        subtitleEl.textContent = 'Choose partner, codes, and optional credential keys.';
        submitBtn.textContent = 'Save account';
        document.getElementById('ca_field_status').value = '1';
        document.getElementById('ca_field_priority').value = '100';
        var sel = document.getElementById('ca_field_partner');
        if (sel && defaultPartnerId > 0) {
            sel.value = String(defaultPartnerId);
        }
        clearCredRows();
    }

    function fillFormEdit(p) {
        document.getElementById('ca_field_id').value = p.id || '';
        document.getElementById('ca_field_partner').value = String(p.partner_id || '');
        document.getElementById('ca_field_code').value = p.account_code || '';
        document.getElementById('ca_field_name').value = p.account_name || '';
        document.getElementById('ca_field_status').value = String(p.is_active === 1 ? 1 : 0);
        document.getElementById('ca_field_priority').value = String(p.priority != null ? p.priority : 100);
        document.getElementById('ca_field_tags').value = p.tags_json || '';
        document.getElementById('ca_field_notes').value = p.notes || '';
        titleEl.textContent = 'Edit account';
        subtitleEl.textContent = 'Update ' + (p.account_name || p.account_code || 'account') + '.';
        submitBtn.textContent = 'Update account';
        fillCredRows(p.credentials || []);
    }

    var btnAdd = document.getElementById('caBtnOpenAdd');
    if (btnAdd) btnAdd.addEventListener('click', function () {
        resetFormAdd();
        openModal();
    });

    document.querySelectorAll('.ca-open-add').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetFormAdd();
            openModal();
        });
    });

    document.querySelectorAll('.ca-btn-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var raw = btn.getAttribute('data-account');
            if (!raw) return;
            try {
                var p = JSON.parse(raw);
                fillFormEdit(p);
                openModal();
            } catch (e) {}
        });
    });

    modal.querySelectorAll('.ca-modal-close').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });
    modal.querySelector('.ca-modal-backdrop').addEventListener('click', closeModal);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
    });
})();
</script>
