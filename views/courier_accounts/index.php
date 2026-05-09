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
                    Map API accounts per partner (production/sandbox, regions). Store credential keys after saving—secrets should be encrypted in a future release.
                </p>
                <p class="mt-2 text-xs text-amber-800/90 font-medium rounded-lg border border-amber-200/80 bg-amber-50/80 px-3 py-2 inline-block">
                    Note: secrets are stored as plain text today; plan encryption at rest for production.
                </p>
            </div>
            <div class="flex shrink-0 lg:pl-4 lg:self-center">
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

    <div class="rounded-2xl border border-gray-200/80 bg-white shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03] mb-6">
        <div class="px-5 py-4 border-b border-amber-100 bg-gradient-to-r from-amber-50/40 via-white to-slate-50/30">
            <h2 class="text-sm font-semibold text-gray-900">Add new account</h2>
            <p class="text-xs text-gray-500 mt-0.5">Create the account shell first; use Edit on a row to attach credential keys.</p>
        </div>
        <div class="p-5">
            <form method="post" action="?page=courier_accounts&action=saveAccount" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Partner</label>
                    <select name="partner_id" required class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition bg-white">
                        <option value="">Select partner</option>
                        <?php foreach ($partners as $p): ?>
                            <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) $p['id'] === $partnerId ? 'selected' : ''; ?>>
                                <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Account code</label>
                    <input name="account_code" required class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" placeholder="DHL_AU">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Account name</label>
                    <input name="account_name" required class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" placeholder="DHL Account - Australia">
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Status</label>
                    <select name="is_active" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm bg-white shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
                <div class="md:col-span-1">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Priority</label>
                    <input type="number" name="priority" value="100" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition">
                </div>
                <div class="md:col-span-5">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Tags JSON (optional)</label>
                    <input name="tags_json" class="h-11 w-full rounded-lg border border-gray-300 px-3 text-sm shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition" placeholder='["AU","intl","express"]'>
                </div>
                <div class="md:col-span-6">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Notes</label>
                    <textarea name="notes" rows="2" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition resize-y min-h-[4rem]" placeholder="Optional notes"></textarea>
                </div>
                <div class="md:col-span-6 flex flex-wrap gap-2">
                    <button type="submit" class="inline-flex items-center gap-2 h-11 px-5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-sm font-semibold shadow-sm transition">
                        <i class="fas fa-plus text-xs opacity-90" aria-hidden="true"></i>
                        Add account
                    </button>
                </div>
            </form>
        </div>
    </div>

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
                                    <p class="mt-1 text-sm text-gray-500">Add an account above or change the partner filter.</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $a): ?>
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
                                    <details class="group">
                                        <summary class="cursor-pointer list-none inline-flex items-center gap-1.5 text-sm font-semibold text-amber-800 hover:text-amber-950">
                                            <i class="fas fa-pen text-xs text-indigo-600" aria-hidden="true"></i>
                                            Edit
                                            <i class="fas fa-chevron-down text-[10px] text-gray-400 group-open:rotate-180 transition-transform ml-0.5" aria-hidden="true"></i>
                                        </summary>
                                        <div class="mt-3 p-4 rounded-xl border border-gray-200 bg-gray-50/90 shadow-inner">
                                            <form method="post" action="?page=courier_accounts&action=saveAccount" class="space-y-3">
                                                <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                                                <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                                                    <div class="md:col-span-2">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Partner</label>
                                                        <select name="partner_id" required class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm bg-white">
                                                            <?php foreach ($partners as $p): ?>
                                                                <option value="<?php echo (int) $p['id']; ?>" <?php echo (int) $p['id'] === (int) $a['partner_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Code</label>
                                                        <input name="account_code" required value="<?php echo h($a['account_code']); ?>" class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm">
                                                    </div>
                                                    <div class="md:col-span-2">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Name</label>
                                                        <input name="account_name" required value="<?php echo h($a['account_name']); ?>" class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm">
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Status</label>
                                                        <select name="is_active" class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm bg-white">
                                                            <option value="1" <?php echo (int) $a['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                                                            <option value="0" <?php echo (int) $a['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Priority</label>
                                                        <input type="number" name="priority" value="<?php echo (int) $a['priority']; ?>" class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm">
                                                    </div>
                                                    <div class="md:col-span-5">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Tags JSON</label>
                                                        <input name="tags_json" value="<?php echo h($a['tags_json'] ?? ''); ?>" class="h-10 rounded-lg border border-gray-300 px-2 text-sm w-full shadow-sm">
                                                    </div>
                                                    <div class="md:col-span-6">
                                                        <label class="block text-[11px] font-semibold text-gray-600 mb-1">Notes</label>
                                                        <textarea name="notes" rows="2" class="rounded-lg border border-gray-300 px-2 py-1.5 text-sm w-full shadow-sm resize-y"><?php echo h($a['notes'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="rounded-lg border border-amber-100 bg-amber-50/40 px-3 py-3">
                                                    <div class="text-xs font-semibold text-gray-800 mb-2">Credentials (key / value)</div>
                                                    <div class="space-y-2">
                                                        <div class="grid grid-cols-12 gap-2 text-[11px] font-semibold text-gray-500 uppercase tracking-wide">
                                                            <div class="col-span-4">Key</div>
                                                            <div class="col-span-6">Value</div>
                                                            <div class="col-span-2">Secret</div>
                                                        </div>
                                                        <?php for ($i = 0; $i < 6; $i++): ?>
                                                            <div class="grid grid-cols-12 gap-2">
                                                                <input name="cred_key[]" class="col-span-4 h-10 rounded-lg border border-gray-300 px-2 text-sm shadow-sm" placeholder="api_key">
                                                                <input name="cred_value[]" class="col-span-6 h-10 rounded-lg border border-gray-300 px-2 text-sm shadow-sm" placeholder="value">
                                                                <label class="col-span-2 inline-flex items-center gap-2 text-xs text-gray-600 pt-2">
                                                                    <input type="checkbox" name="cred_secret[<?php echo $i; ?>]" value="1" class="h-4 w-4 rounded border-gray-300"> Yes
                                                                </label>
                                                            </div>
                                                        <?php endfor; ?>
                                                        <p class="text-[11px] text-gray-500 pt-1">Examples: <span class="font-mono text-gray-600">api_key</span>, <span class="font-mono text-gray-600">client_id</span>, <span class="font-mono text-gray-600">base_url</span>, …</p>
                                                    </div>
                                                </div>

                                                <div class="flex flex-wrap gap-2 pt-1">
                                                    <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm">Save account</button>
                                                </div>
                                            </form>
                                            <form method="post" action="?page=courier_accounts&action=deleteAccount" onsubmit="return confirm('Delete this account?');" class="mt-3 pt-3 border-t border-gray-200">
                                                <input type="hidden" name="id" value="<?php echo (int) $a['id']; ?>">
                                                <button type="submit" class="h-9 px-3 rounded-lg bg-red-600 text-white text-xs font-semibold hover:bg-red-700">Delete account</button>
                                            </form>
                                        </div>
                                    </details>
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
