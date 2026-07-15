<?php
$groups = $groups ?? [];
$audit_rows = $audit_rows ?? [];
$table_ready = $table_ready ?? false;
$active_group = $active_group ?? '';
$groupKeys = array_column($groups, 'group_key');
if ($active_group === '' && $groupKeys !== []) {
    $active_group = $groupKeys[0];
}
if (!in_array($active_group, $groupKeys, true) && $groupKeys !== []) {
    $active_group = $groupKeys[0];
}

function globals_setting_input_id(string $key): string
{
    return 'setting_' . preg_replace('/[^a-z0-9_]+/i', '_', $key);
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 space-y-6">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04]">
        <div class="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="pointer-events-none absolute -bottom-20 -left-16 h-48 w-48 rounded-full bg-sky-200/15 blur-2xl" aria-hidden="true"></div>
        <div class="relative px-5 py-7 sm:px-8 sm:py-9 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-8">
            <div class="min-w-0 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-4">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-sliders-h text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span>Administration · App configuration</span>
                </div>
                <h1 class="text-3xl sm:text-4xl font-bold tracking-tight text-gray-900">
                    Global <span class="text-amber-800">settings</span>
                </h1>
                <p class="mt-3 text-sm sm:text-base text-gray-600 leading-relaxed max-w-2xl">
                    Values are stored in <code class="text-xs bg-gray-100 px-1 rounded">app_settings</code>;
                    changes are logged in <code class="text-xs bg-gray-100 px-1 rounded">settings_audit_log</code>.
                    Keys and UI metadata are defined by developers in
                    <code class="text-xs bg-gray-100 px-1 rounded">config/app_settings_registry.php</code>
                    and the database.
                </p>
            </div>
        </div>
    </div>

    <?php if (!$table_ready): ?>
        <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900">
            <p class="font-semibold">Database setup required</p>
            <p class="mt-1">Run <code class="text-xs bg-white px-1 rounded">sql/app_settings_module.sql</code> to enable this module.
            </p>
        </div>
    <?php elseif ($groups === []): ?>
        <div class="rounded-2xl border border-gray-200 bg-white px-5 py-8 text-center text-sm text-gray-600">
            No settings found. Developers must add rows to <code class="text-xs bg-gray-100 px-1 rounded">app_settings</code>
            and register them in <code class="text-xs bg-gray-100 px-1 rounded">config/app_settings_registry.php</code>.
        </div>
    <?php else: ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($groups as $group): ?>
                <?php
                $groupKey = $group['group_key'];
                $isActiveTab = $groupKey === $active_group;
                $tabUrl = base_url('?page=globals&action=settings&group=' . urlencode($groupKey));
                ?>
                <a href="<?php echo htmlspecialchars($tabUrl); ?>"
                    class="inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold transition <?php echo $isActiveTab ? 'bg-amber-600 text-white shadow-sm' : 'bg-white text-gray-700 border border-gray-200 hover:border-amber-200 hover:text-amber-800'; ?>">
                    <i class="fas fa-folder-open text-xs opacity-80" aria-hidden="true"></i>
                    <?php echo htmlspecialchars($group['group_label']); ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php foreach ($groups as $group): ?>
            <?php if ($group['group_key'] !== $active_group) {
                continue;
            } ?>
            <form action="<?php echo base_url('?page=globals&action=update_settings'); ?>" method="post" class="space-y-6">
                <input type="hidden" name="group" value="<?php echo htmlspecialchars($group['group_key']); ?>">

                <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
                    <div class="px-5 py-4 bg-gradient-to-r from-amber-50/50 via-gray-50/90 to-gray-50/90 border-b border-amber-100/80">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-white text-amber-700 shadow-sm border border-amber-100">
                                <i class="fas fa-pen-to-square text-sm" aria-hidden="true"></i>
                            </span>
                            <div class="min-w-0">
                                <h2 class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($group['group_label']); ?></h2>
                                <p class="text-xs text-gray-500 mt-0.5">Admins can update values only. Keys cannot be added from this screen.</p>
                            </div>
                        </div>
                    </div>

                    <div class="divide-y divide-gray-100">
                        <?php foreach ($group['settings'] as $setting): ?>
                            <?php
                            $key = $setting['setting_key'];
                            $inputId = globals_setting_input_id($key);
                            $isEditable = (int) ($setting['is_editable'] ?? 1) === 1;
                            $value = $setting['setting_value'] ?? '';
                            $inputType = $setting['input_type'] ?? 'text';
                            $valueType = $setting['value_type'] ?? 'string';
                            ?>
                            <div class="px-5 py-5 sm:px-6">
                                <div class="flex flex-col lg:flex-row lg:items-start gap-4 lg:gap-8">
                                    <div class="lg:w-2/5 min-w-0">
                                        <label for="<?php echo htmlspecialchars($inputId); ?>" class="block text-sm font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($setting['label']); ?>
                                        </label>
                                        <div class="mt-1.5 inline-flex items-center gap-2 flex-wrap">
                                            <code class="text-[11px] bg-slate-100 text-slate-700 px-2 py-0.5 rounded"><?php echo htmlspecialchars($key); ?></code>
                                            <?php if (!$isEditable): ?>
                                                <span class="text-[11px] font-medium text-gray-500 uppercase tracking-wide">Read only</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (!empty($setting['description'])): ?>
                                            <p class="mt-2 text-xs text-gray-500 leading-relaxed"><?php echo htmlspecialchars($setting['description']); ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <div class="lg:flex-1 min-w-0">
                                        <?php if (!$isEditable): ?>
                                            <div class="rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-700 whitespace-pre-wrap break-words">
                                                <?php
                                                if ($valueType === 'bool') {
                                                    echo in_array((string) $value, ['1', 'true', 'yes'], true) ? 'Enabled' : 'Disabled';
                                                } else {
                                                    echo htmlspecialchars((string) $value);
                                                }
                                                ?>
                                            </div>
                                        <?php elseif ($inputType === 'textarea' || $valueType === 'text'): ?>
                                            <textarea
                                                id="<?php echo htmlspecialchars($inputId); ?>"
                                                name="values[<?php echo htmlspecialchars($key); ?>]"
                                                rows="4"
                                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500"><?php echo htmlspecialchars((string) $value); ?></textarea>
                                        <?php elseif ($inputType === 'toggle' || $valueType === 'bool'): ?>
                                            <label class="inline-flex items-center gap-3 cursor-pointer">
                                                <input type="hidden" name="values[<?php echo htmlspecialchars($key); ?>]" value="0">
                                                <input
                                                    type="checkbox"
                                                    id="<?php echo htmlspecialchars($inputId); ?>"
                                                    name="values[<?php echo htmlspecialchars($key); ?>]"
                                                    value="1"
                                                    class="h-5 w-5 rounded border-gray-300 text-amber-600 focus:ring-amber-500"
                                                    <?php echo in_array((string) $value, ['1', 'true', 'yes'], true) ? 'checked' : ''; ?>>
                                                <span class="text-sm text-gray-700"><?php echo in_array((string) $value, ['1', 'true', 'yes'], true) ? 'Enabled' : 'Disabled'; ?></span>
                                            </label>
                                        <?php elseif ($inputType === 'select' && !empty($setting['options'])): ?>
                                            <select
                                                id="<?php echo htmlspecialchars($inputId); ?>"
                                                name="values[<?php echo htmlspecialchars($key); ?>]"
                                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                                <?php foreach ($setting['options'] as $option): ?>
                                                    <?php
                                                    $optionValue = is_array($option) ? ($option['value'] ?? '') : $option;
                                                    $optionLabel = is_array($option) ? ($option['label'] ?? $optionValue) : $option;
                                                    ?>
                                                    <option value="<?php echo htmlspecialchars((string) $optionValue); ?>" <?php echo (string) $value === (string) $optionValue ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars((string) $optionLabel); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        <?php elseif ($inputType === 'number' || in_array($valueType, ['int', 'decimal'], true)): ?>
                                            <input
                                                type="number"
                                                id="<?php echo htmlspecialchars($inputId); ?>"
                                                name="values[<?php echo htmlspecialchars($key); ?>]"
                                                value="<?php echo htmlspecialchars((string) $value); ?>"
                                                <?php echo $valueType === 'decimal' ? 'step="0.01" min="0.01"' : 'step="1"'; ?>
                                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <?php else: ?>
                                            <input
                                                type="text"
                                                id="<?php echo htmlspecialchars($inputId); ?>"
                                                name="values[<?php echo htmlspecialchars($key); ?>]"
                                                value="<?php echo htmlspecialchars((string) $value); ?>"
                                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm shadow-sm focus:border-amber-500 focus:ring-amber-500">
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="px-5 py-4 sm:px-6 border-t border-gray-100 bg-gray-50/70 flex justify-end">
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/20 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition">
                            <i class="fas fa-save text-xs opacity-95" aria-hidden="true"></i>
                            Save <?php echo htmlspecialchars($group['group_label']); ?>
                        </button>
                    </div>
                </div>
            </form>
        <?php endforeach; ?>

        <?php if ($audit_rows !== []): ?>
            <div class="bg-white rounded-2xl border border-gray-200/80 shadow-sm overflow-hidden ring-1 ring-gray-900/[0.03]">
                <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/80">
                    <h2 class="text-sm font-semibold text-gray-900">Recent changes</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Logged in <code class="text-[11px] bg-gray-100 px-1 rounded">settings_audit_log</code>.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                            <tr>
                                <th class="px-5 py-3 font-semibold">Setting key</th>
                                <th class="px-5 py-3 font-semibold">Old value</th>
                                <th class="px-5 py-3 font-semibold">New value</th>
                                <th class="px-5 py-3 font-semibold">Changed by</th>
                                <th class="px-5 py-3 font-semibold">When</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($audit_rows as $row): ?>
                                <tr>
                                    <td class="px-5 py-3"><code class="text-xs bg-slate-100 px-1.5 py-0.5 rounded"><?php echo htmlspecialchars($row['setting_key']); ?></code></td>
                                    <td class="px-5 py-3 text-gray-600 max-w-xs truncate" title="<?php echo htmlspecialchars((string) ($row['old_value'] ?? '')); ?>"><?php echo htmlspecialchars((string) ($row['old_value'] ?? '—')); ?></td>
                                    <td class="px-5 py-3 text-gray-900 max-w-xs truncate" title="<?php echo htmlspecialchars((string) ($row['new_value'] ?? '')); ?>"><?php echo htmlspecialchars((string) ($row['new_value'] ?? '—')); ?></td>
                                    <td class="px-5 py-3 text-gray-700"><?php echo htmlspecialchars($row['changed_by_name'] ?? ('User #' . ($row['changed_by'] ?? ''))); ?></td>
                                    <td class="px-5 py-3 text-gray-500 whitespace-nowrap"><?php echo htmlspecialchars((string) ($row['changed_at'] ?? '')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        if (typeof window.showToast === 'function') {
            window.showToast('Settings saved successfully.', 'success');
        }
    <?php elseif (isset($_GET['status']) && $_GET['status'] === 'error'): ?>
        if (typeof window.showToast === 'function') {
            window.showToast(<?php echo json_encode($_GET['message'] ?? 'Unable to save settings.'); ?>, 'error');
        }
    <?php endif; ?>
});
</script>
