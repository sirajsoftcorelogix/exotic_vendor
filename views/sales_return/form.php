<?php
/** @var array $data */
$context = $data['context'] ?? [];
$returnTypes = $data['return_types'] ?? sales_return_type_options();
$warehouseName = (string) ($data['warehouse_name'] ?? '—');
$lines = $context['lines'] ?? [];
$orderNumber = (string) ($context['order_number'] ?? '');
$invoice = is_array($context['invoice'] ?? null) ? $context['invoice'] : null;
$invoiceId = $invoice ? (int) ($invoice['id'] ?? 0) : 0;
$invoiceNumber = $invoice ? (string) ($invoice['invoice_number'] ?? '') : '';

$flash = $_SESSION['sales_return_flash'] ?? null;
if ($flash) {
    unset($_SESSION['sales_return_flash']);
}

$dateMax = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$inp = 'w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500';
?>
<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="relative overflow-hidden rounded-2xl border border-orange-200/45 bg-gradient-to-br from-orange-50/70 via-white to-slate-50/40 shadow-sm mb-6">
        <div class="relative px-5 py-6 sm:px-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">New sales return</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Order <span class="font-mono font-semibold"><?= htmlspecialchars($orderNumber) ?></span>
                    <?php if ($invoiceNumber !== ''): ?>
                        · Invoice <span class="font-mono font-semibold"><?= htmlspecialchars($invoiceNumber) ?></span>
                    <?php endif; ?>
                </p>
            </div>
            <a href="?page=sales_returns&action=index"
                class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 shrink-0">
                <i class="fas fa-arrow-left text-xs" aria-hidden="true"></i>
                All returns
            </a>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $ring = ($flash['type'] ?? '') === 'success' ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900' : 'border-red-200/80 bg-red-50/90 text-red-900'; ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $ring ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <form method="post" action="?page=sales_returns&action=save" class="space-y-6">
        <input type="hidden" name="order_number" value="<?= htmlspecialchars($orderNumber) ?>">
        <?php if ($invoiceId > 0): ?>
            <input type="hidden" name="invoice_id" value="<?= $invoiceId ?>">
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Return date <span class="text-red-500">*</span></label>
                    <input type="date" name="return_date" required class="<?= $inp ?>" max="<?= htmlspecialchars($dateMax) ?>"
                        value="<?= htmlspecialchars($dateMax) ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Warehouse (stock IN)</label>
                    <div class="<?= $inp ?> bg-gray-50 text-gray-800"><?= htmlspecialchars($warehouseName) ?></div>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Return type</label>
                    <select name="return_type" class="<?= $inp ?>">
                        <?php foreach ($returnTypes as $key => $label): ?>
                            <option value="<?= htmlspecialchars($key) ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Remarks</label>
                    <input type="text" name="remarks" class="<?= $inp ?>" maxlength="500" placeholder="Optional notes">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Lines</div>
            <p class="text-xs text-gray-500 mb-4">Stock is restored only when a prior sale OUT movement exists for the line.</p>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold text-gray-700">Item</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Sold</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Already returned</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700">Returnable</th>
                            <th class="px-3 py-2 text-right font-semibold text-gray-700 w-36">Return qty</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($lines as $ln): ?>
                            <?php
                            $orderRowId = (int) ($ln['order_row_id'] ?? 0);
                            $maxRet = (float) ($ln['max_return_qty'] ?? 0);
                            $itemLabel = trim((string) ($ln['item_name'] ?? ''));
                            if ($itemLabel === '') {
                                $itemLabel = (string) ($ln['item_code'] ?? '');
                            }
                            ?>
                            <tr class="hover:bg-orange-50/20">
                                <td class="px-3 py-2">
                                    <div class="font-medium text-gray-900"><?= htmlspecialchars($itemLabel) ?></div>
                                    <div class="text-xs text-gray-500 font-mono"><?= htmlspecialchars((string) ($ln['item_code'] ?? '')) ?></div>
                                </td>
                                <td class="px-3 py-2 text-right tabular-nums"><?= htmlspecialchars((string) ($ln['sold_qty'] ?? '0')) ?></td>
                                <td class="px-3 py-2 text-right tabular-nums text-gray-600"><?= htmlspecialchars((string) ($ln['already_returned_qty'] ?? '0')) ?></td>
                                <td class="px-3 py-2 text-right tabular-nums font-medium"><?= htmlspecialchars((string) $maxRet) ?></td>
                                <td class="px-3 py-2 text-right">
                                    <input type="hidden" name="order_row_id[]" value="<?= $orderRowId ?>">
                                    <input type="number" name="return_qty[]" step="0.001" min="0" max="<?= htmlspecialchars((string) $maxRet) ?>"
                                        class="w-full max-w-[8rem] ml-auto px-2 py-2 border border-gray-300 rounded-lg text-sm text-right tabular-nums"
                                        value="" placeholder="0">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="?page=sales_returns&action=index" class="px-5 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold hover:bg-gray-50">Cancel</a>
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-orange-600 text-white text-sm font-semibold hover:bg-orange-700">
                Save return
            </button>
        </div>
    </form>
</div>
