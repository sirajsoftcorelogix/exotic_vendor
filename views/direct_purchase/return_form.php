<?php
$purchase = $data['purchase'] ?? [];
$lines = $data['lines'] ?? [];
$warehouses = $data['warehouses'] ?? [];
$defWh = (int) ($data['default_warehouse_id'] ?? 0);
$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}
$dpId = (int) ($purchase['id'] ?? 0);
$dpDateMax = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$inp = 'w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition';
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="relative px-5 py-6 sm:px-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">New purchase return</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Invoice <span class="font-mono font-semibold"><?= htmlspecialchars((string) ($purchase['invoice_number'] ?? '')) ?></span>
                    · Stock will be reduced (OUT) for each line with a return quantity.
                </p>
            </div>
            <a href="?page=direct_purchase&action=return_list&amp;dp_id=<?= $dpId ?>"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 shrink-0">
                <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                Back to returns
            </a>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ring = ($flash['type'] ?? '') === 'success' ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900' : 'border-red-200/80 bg-red-50/90 text-red-900'; ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="?page=direct_purchase&action=return_save" class="space-y-6">
        <input type="hidden" name="direct_purchase_id" value="<?= $dpId ?>">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Return date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" required class="<?= $inp ?>" max="<?= htmlspecialchars($dpDateMax) ?>"
                        value="<?= htmlspecialchars($dpDateMax) ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Warehouse (stock OUT) <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" required class="<?= $inp ?> bg-white">
                        <option value="">Select warehouse</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= (int) ($wh['id'] ?? 0) ?>"
                                <?= $defWh === (int) ($wh['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wh['address_title'] ?? ('#' . (int) ($wh['id'] ?? 0)))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                    <input type="text" name="remarks" class="<?= $inp ?>" placeholder="Optional notes" maxlength="500">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Lines</div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700">SKU</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Purchased</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Already returned</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Returnable</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700 w-36">Return qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($lines as $ln): ?>
                            <?php
                            $iid = (int) ($ln['id'] ?? 0);
                            $ret = (float) ($ln['returnable_qty'] ?? 0);
                            ?>
                            <tr class="hover:bg-amber-50/20">
                                <td class="px-3 py-2 font-mono text-xs text-gray-900"><?= htmlspecialchars((string) ($ln['sku'] ?? '')) ?></td>
                                <td class="px-3 py-2 text-right tabular-nums"><?= htmlspecialchars((string) ($ln['qty'] ?? '0')) ?></td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-600"><?= htmlspecialchars((string) ($ln['already_returned_qty'] ?? '0')) ?></td>
                                <td class="px-3 py-2 text-right tabular-nums font-medium"><?= htmlspecialchars((string) $ret) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <input type="hidden" name="dp_item_id[]" value="<?= $iid ?>">
                                    <input type="number" name="return_qty[]" step="0.001" min="0" max="<?= htmlspecialchars((string) $ret) ?>"
                                        class="w-full max-w-[8rem] ml-auto px-2 py-2 border border-gray-300 rounded-lg text-sm text-right tabular-nums"
                                        value="" placeholder="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="mt-3 text-xs text-gray-500">Enter return quantity only where goods leave stock. Totals and GST on the return are prorated from the original purchase lines.</p>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <a href="?page=direct_purchase&action=return_list&amp;dp_id=<?= $dpId ?>"
                class="inline-flex justify-center items-center px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-800 hover:bg-gray-50">Cancel</a>
            <button type="submit"
                class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 shadow-sm">
                <i class="fas fa-save text-xs" aria-hidden="true"></i>
                Save return
            </button>
        </div>
    </form>
</div>
