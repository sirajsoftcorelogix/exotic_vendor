<?php
$flash = $_SESSION['courier_account_flash'] ?? null;
if ($flash) unset($_SESSION['courier_account_flash']);
$partners = $partners ?? [];
$accounts = $accounts ?? [];
$partnerId = (int)($partner_id ?? 0);

function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
?>

<div class="max-w-7xl mx-auto p-4 sm:p-6 space-y-5">
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h2 class="text-lg font-semibold text-gray-800">Courier Accounts & Credentials</h2>
        <p class="text-sm text-gray-500 mt-1">Manage multiple API accounts per courier partner. Add any credential keys required by that partner (API key, username, password, account number, token URL, etc.).</p>
        <p class="text-xs text-amber-700 mt-2">Note: secrets are stored as plain text currently. Next step should be encryption at rest.</p>
    </div>

    <?php if ($flash): ?>
        <div class="rounded-lg border px-4 py-3 text-sm <?php echo !empty($flash['success']) ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-red-200 bg-red-50 text-red-700'; ?>">
            <?php echo h($flash['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <form method="get" class="flex flex-wrap gap-3 items-end">
            <input type="hidden" name="page" value="courier_accounts">
            <input type="hidden" name="action" value="list">
            <div>
                <label class="block text-xs font-semibold text-gray-500 mb-1">Partner</label>
                <select name="partner_id" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-72">
                    <option value="0">All partners</option>
                    <?php foreach ($partners as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === $partnerId ? 'selected' : ''; ?>>
                            <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="h-10 px-4 rounded-lg bg-gray-800 hover:bg-gray-900 text-white text-sm font-semibold">Filter</button>
            <a href="?page=courier_accounts&action=list" class="h-10 px-4 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-semibold inline-flex items-center">Clear</a>
        </form>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Add New Account</h3>
        <form method="post" action="?page=courier_accounts&action=saveAccount" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Partner</label>
                <select name="partner_id" required class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
                    <option value="">Select partner</option>
                    <?php foreach ($partners as $p): ?>
                        <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === $partnerId ? 'selected' : ''; ?>>
                            <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Account Code</label>
                <input name="account_code" required class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full" placeholder="DHL_AU">
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Account Name</label>
                <input name="account_name" required class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full" placeholder="DHL Account - Australia">
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Status</label>
                <select name="is_active" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>
            <div class="md:col-span-1">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Priority</label>
                <input type="number" name="priority" value="100" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full">
            </div>
            <div class="md:col-span-5">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Tags JSON (optional)</label>
                <input name="tags_json" class="h-10 rounded-lg border border-gray-300 px-3 text-sm w-full" placeholder='["AU","intl","express"]'>
            </div>
            <div class="md:col-span-6">
                <label class="block text-xs font-semibold text-gray-500 mb-1">Notes</label>
                <textarea name="notes" rows="2" class="rounded-lg border border-gray-300 px-3 py-2 text-sm w-full" placeholder="Optional notes"></textarea>
            </div>
            <div class="md:col-span-6">
                <button type="submit" class="h-10 px-4 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold">Add Account</button>
            </div>
        </form>
        <p class="text-xs text-gray-500 mt-2">After creating an account, use “Edit” to add credential key/value rows.</p>
    </div>

    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 border-b border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left">Partner</th>
                        <th class="px-4 py-3 text-left">Account</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-left">Priority</th>
                        <th class="px-4 py-3 text-left">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$accounts): ?>
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No accounts found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($accounts as $a): ?>
                            <tr class="border-b border-gray-100 align-top">
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800"><?php echo h($a['partner_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo h($a['partner_code']); ?></div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-semibold text-gray-800"><?php echo h($a['account_name']); ?></div>
                                    <div class="text-xs text-gray-500"><?php echo h($a['account_code']); ?></div>
                                </td>
                                <td class="px-4 py-3"><?php echo (int)$a['is_active'] === 1 ? 'Active' : 'Inactive'; ?></td>
                                <td class="px-4 py-3"><?php echo (int)$a['priority']; ?></td>
                                <td class="px-4 py-3">
                                    <details>
                                        <summary class="cursor-pointer text-indigo-600">Edit</summary>
                                        <div class="mt-3 p-3 rounded-lg border border-gray-200 bg-gray-50">
                                            <form method="post" action="?page=courier_accounts&action=saveAccount" class="space-y-2">
                                                <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                                <div class="grid grid-cols-1 md:grid-cols-6 gap-2">
                                                    <div class="md:col-span-2">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Partner</label>
                                                        <select name="partner_id" required class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                            <?php foreach ($partners as $p): ?>
                                                                <option value="<?php echo (int)$p['id']; ?>" <?php echo (int)$p['id'] === (int)$a['partner_id'] ? 'selected' : ''; ?>>
                                                                    <?php echo h($p['partner_name']); ?> (<?php echo h($p['partner_code']); ?>)
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Code</label>
                                                        <input name="account_code" required value="<?php echo h($a['account_code']); ?>" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                    </div>
                                                    <div class="md:col-span-2">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Name</label>
                                                        <input name="account_name" required value="<?php echo h($a['account_name']); ?>" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Status</label>
                                                        <select name="is_active" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                            <option value="1" <?php echo (int)$a['is_active'] === 1 ? 'selected' : ''; ?>>Active</option>
                                                            <option value="0" <?php echo (int)$a['is_active'] === 0 ? 'selected' : ''; ?>>Inactive</option>
                                                        </select>
                                                    </div>
                                                    <div class="md:col-span-1">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Priority</label>
                                                        <input type="number" name="priority" value="<?php echo (int)$a['priority']; ?>" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                    </div>
                                                    <div class="md:col-span-5">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Tags JSON</label>
                                                        <input name="tags_json" value="<?php echo h($a['tags_json'] ?? ''); ?>" class="h-9 rounded border border-gray-300 px-2 text-sm w-full">
                                                    </div>
                                                    <div class="md:col-span-6">
                                                        <label class="block text-[11px] font-semibold text-gray-500 mb-1">Notes</label>
                                                        <textarea name="notes" rows="2" class="rounded border border-gray-300 px-2 py-1 text-sm w-full"><?php echo h($a['notes'] ?? ''); ?></textarea>
                                                    </div>
                                                </div>

                                                <div class="mt-3">
                                                    <div class="text-xs font-semibold text-gray-700 mb-2">Credentials (key/value)</div>
                                                    <div class="space-y-2">
                                                        <div class="grid grid-cols-12 gap-2 text-[11px] text-gray-500">
                                                            <div class="col-span-4">Key</div>
                                                            <div class="col-span-6">Value</div>
                                                            <div class="col-span-2">Secret?</div>
                                                        </div>
                                                        <?php
                                                        // minimal: show 6 empty rows; this can be upgraded to dynamic JS later
                                                        for ($i = 0; $i < 6; $i++):
                                                        ?>
                                                            <div class="grid grid-cols-12 gap-2">
                                                                <input name="cred_key[]" class="col-span-4 h-9 rounded border border-gray-300 px-2 text-sm" placeholder="api_key">
                                                                <input name="cred_value[]" class="col-span-6 h-9 rounded border border-gray-300 px-2 text-sm" placeholder="value">
                                                                <label class="col-span-2 inline-flex items-center gap-2 text-xs text-gray-600">
                                                                    <input type="checkbox" name="cred_secret[<?php echo $i; ?>]" value="1" class="h-4 w-4"> Yes
                                                                </label>
                                                            </div>
                                                        <?php endfor; ?>
                                                        <p class="text-[11px] text-gray-500">Add keys like `api_key`, `username`, `password`, `client_id`, `client_secret`, `account_number`, `base_url`, etc.</p>
                                                    </div>
                                                </div>

                                                <button type="submit" class="h-9 px-3 rounded bg-indigo-600 text-white text-xs font-semibold">Save Account</button>
                                            </form>
                                            <form method="post" action="?page=courier_accounts&action=deleteAccount" onsubmit="return confirm('Delete this account?');" class="mt-2">
                                                <input type="hidden" name="id" value="<?php echo (int)$a['id']; ?>">
                                                <button type="submit" class="h-9 px-3 rounded bg-red-600 text-white text-xs font-semibold">Delete</button>
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
</div>

