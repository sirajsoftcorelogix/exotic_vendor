<?php
$purchase = $data['purchase'] ?? null;
$pData = $purchase ?? [];
$items = $data['items'] ?? [];
$vendors = $data['vendors'] ?? [];
$isEdit = !empty($data['is_edit']);
if (empty($items)) {
    $items = [[
        'item_code' => '', 'sku' => '', 'color' => '', 'size' => '',
        'cost_per_item' => '', 'qty' => '1', 'hsn' => '', 'gst_rate' => '',
        'unit' => '', 'gst_amount' => '', 'line_total' => '',
    ]];
}
$flash = $_SESSION['direct_purchase_flash'] ?? null;
if ($flash) {
    unset($_SESSION['direct_purchase_flash']);
}
?>
<div class="max-w-[1600px] mx-auto space-y-6 mr-4 pb-10">
    <?php if ($flash): ?>
        <?php $cls = ($flash['type'] ?? '') === 'success' ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200'; ?>
        <div class="rounded-lg border px-4 py-3 <?= $cls ?>"><?= htmlspecialchars($flash['text'] ?? '') ?></div>
    <?php endif; ?>

    <div class="flex flex-wrap items-center justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900"><?= $isEdit ? 'Edit direct purchase' : 'Add direct purchase' ?></h1>
        <a href="index.php?page=direct_purchase&action=list" class="text-amber-800 hover:underline text-sm font-medium">← Back to list</a>
    </div>

    <form method="post" action="index.php?page=direct_purchase&action=save" enctype="multipart/form-data" id="dp-form" class="space-y-6">
        <?php if ($isEdit && $purchase): ?>
            <input type="hidden" name="id" value="<?= (int) $purchase['id'] ?>">
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Vendor &amp; invoice</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" id="vendor_id" required class="w-full dp-select">
                        <option value="">Select vendor</option>
                        <?php foreach ($vendors as $v): ?>
                            <option value="<?= (int) $v['id'] ?>"
                                <?= ($purchase && (int) ($purchase['vendor_id'] ?? 0) === (int) $v['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['vendor_name'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Invoice file</label>
                    <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700">
                    <?php if ($purchase && !empty($purchase['invoice_file'])): ?>
                        <p class="mt-1 text-sm">Current: <a href="<?= htmlspecialchars($purchase['invoice_file']) ?>" target="_blank" class="text-amber-700 hover:underline">View</a> (leave empty to keep)</p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Invoice number <span class="text-red-500">*</span></label>
                    <input type="text" name="invoice_number" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars($pData['invoice_number'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Invoice date <span class="text-red-500">*</span></label>
                    <input type="date" name="invoice_date" required class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars($pData['invoice_date'] ?? '') ?>">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b pb-2">
                <h2 class="text-lg font-semibold text-gray-800">Line items</h2>
                <button type="button" id="add-line-btn" class="text-sm bg-gray-100 hover:bg-gray-200 text-gray-800 font-medium px-3 py-1.5 rounded-lg">+ Add row</button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm" id="line-items-table">
                    <thead>
                    <tr class="text-left text-xs font-medium text-gray-500 uppercase border-b">
                        <th class="py-2 pr-2">Item code</th>
                        <th class="py-2 pr-2">SKU</th>
                        <th class="py-2 pr-2">Color</th>
                        <th class="py-2 pr-2">Size</th>
                        <th class="py-2 pr-2">Cost / item</th>
                        <th class="py-2 pr-2">Qty</th>
                        <th class="py-2 pr-2">HSN</th>
                        <th class="py-2 pr-2">GST %</th>
                        <th class="py-2 pr-2">Unit</th>
                        <th class="py-2 pr-2">GST amt</th>
                        <th class="py-2 pr-2">Line total</th>
                        <th class="py-2 w-10"></th>
                    </tr>
                    </thead>
                    <tbody id="line-items-body">
                    <?php foreach ($items as $idx => $it): ?>
                        <tr class="dp-line border-b border-gray-100">
                            <td class="py-1 pr-1"><input name="item_code[]" class="w-[88px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['item_code'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input name="sku[]" class="w-[88px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['sku'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input name="color[]" class="w-[72px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['color'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input name="size[]" class="w-[72px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['size'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost w-[88px] px-1 py-1 border rounded" value="<?= htmlspecialchars((string) ($it['cost_per_item'] ?? '')) ?>"></td>
                            <td class="py-1 pr-1"><input type="number" step="0.001" name="qty[]" class="dp-qty w-[72px] px-1 py-1 border rounded" value="<?= htmlspecialchars((string) ($it['qty'] ?? '')) ?>"></td>
                            <td class="py-1 pr-1"><input name="hsn[]" class="w-[72px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['hsn'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-[64px] px-1 py-1 border rounded" value="<?= htmlspecialchars((string) ($it['gst_rate'] ?? '')) ?>"></td>
                            <td class="py-1 pr-1"><input name="unit[]" class="w-[56px] px-1 py-1 border rounded" value="<?= htmlspecialchars($it['unit'] ?? '') ?>"></td>
                            <td class="py-1 pr-1"><input type="number" step="0.01" name="gst_amount[]" class="dp-gst w-[80px] px-1 py-1 border rounded" value="<?= htmlspecialchars((string) ($it['gst_amount'] ?? '')) ?>"></td>
                            <td class="py-1 pr-1"><input type="number" step="0.01" name="line_total[]" class="dp-line-total w-[88px] px-1 py-1 border rounded" value="<?= htmlspecialchars((string) ($it['line_total'] ?? '')) ?>"></td>
                            <td class="py-1"><button type="button" class="dp-remove text-red-600 hover:text-red-800 text-xs px-1" title="Remove">&times;</button></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-gray-500">Line totals recalculate when cost, quantity or GST % change (GST amount = taxable &times; GST% &divide; 100, line total = taxable + GST).</p>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Totals</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Subtotal</label>
                    <input type="number" step="0.01" name="subtotal" id="f_subtotal" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['subtotal'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Discount</label>
                    <input type="number" step="0.01" name="discount" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['discount'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">IGST total</label>
                    <input type="number" step="0.01" name="igst_total" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['igst_total'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">SGST total</label>
                    <input type="number" step="0.01" name="sgst_total" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['sgst_total'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">CGST total</label>
                    <input type="number" step="0.01" name="cgst_total" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['cgst_total'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Round off</label>
                    <input type="number" step="0.01" name="round_off" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['round_off'] ?? '0')) ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Grand total</label>
                    <input type="number" step="0.01" name="grand_total" id="f_grand_total" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars((string) ($pData['grand_total'] ?? '0')) ?>">
                </div>
                <div class="flex items-end">
                    <button type="button" id="sum-lines-btn" class="text-sm bg-amber-50 text-amber-900 font-medium px-3 py-2 rounded-lg border border-amber-200 hover:bg-amber-100">Sum line totals → subtotal</button>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-md p-6 space-y-4">
            <h2 class="text-lg font-semibold text-gray-800 border-b pb-2">Payment details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Mode</label>
                    <select name="payment_mode" class="w-full px-3 py-2 border border-gray-300 rounded-lg bg-white">
                        <?php
                        $pm = $pData['payment_mode'] ?? '';
                        $modes = ['' => '— Select —', 'Cash' => 'Cash', 'Bank transfer' => 'Bank transfer', 'UPI' => 'UPI', 'Cheque' => 'Cheque', 'Credit' => 'Credit', 'Other' => 'Other'];
                        foreach ($modes as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $pm === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Reference / txn id</label>
                    <input type="text" name="payment_reference" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars($pData['payment_reference'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Payment date</label>
                    <input type="date" name="payment_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg"
                           value="<?= htmlspecialchars($pData['payment_date'] ?? '') ?>">
                </div>
                <div class="md:col-span-2 lg:col-span-4">
                    <label class="block text-sm font-medium text-gray-600 mb-1">Notes</label>
                    <textarea name="payment_notes" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?= htmlspecialchars($pData['payment_notes'] ?? '') ?></textarea>
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="bg-gray-800 hover:bg-gray-900 text-white font-semibold px-6 py-2.5 rounded-lg"><?= $isEdit ? 'Update purchase' : 'Save purchase' ?></button>
            <a href="index.php?page=direct_purchase&action=list" class="inline-flex items-center px-6 py-2.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">Cancel</a>
        </div>
    </form>
</div>

<table class="hidden">
 <tbody id="line-item-template">
    <tr class="dp-line border-b border-gray-100">
        <td class="py-1 pr-1"><input name="item_code[]" class="w-[88px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input name="sku[]" class="w-[88px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input name="color[]" class="w-[72px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input name="size[]" class="w-[72px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost w-[88px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input type="number" step="0.001" name="qty[]" class="dp-qty w-[72px] px-1 py-1 border rounded" value="1"></td>
        <td class="py-1 pr-1"><input name="hsn[]" class="w-[72px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-[64px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input name="unit[]" class="w-[56px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input type="number" step="0.01" name="gst_amount[]" class="dp-gst w-[80px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1 pr-1"><input type="number" step="0.01" name="line_total[]" class="dp-line-total w-[88px] px-1 py-1 border rounded" value=""></td>
        <td class="py-1"><button type="button" class="dp-remove text-red-600 hover:text-red-800 text-xs px-1" title="Remove">&times;</button></td>
    </tr>
    </tbody>
</table>

<script>
(function () {
    function parseNum(el) {
        var v = parseFloat(el.value);
        return isNaN(v) ? 0 : v;
    }
    function recalcRow(tr) {
        var cost = parseNum(tr.querySelector('.dp-cost'));
        var qty = parseNum(tr.querySelector('.dp-qty'));
        var rate = parseNum(tr.querySelector('.dp-rate'));
        var taxable = cost * qty;
        var gst = taxable * (rate / 100);
        var gstIn = tr.querySelector('.dp-gst');
        var lineIn = tr.querySelector('.dp-line-total');
        gstIn.value = gst.toFixed(2);
        lineIn.value = (taxable + gst).toFixed(2);
    }
    function bindRow(tr) {
        ['.dp-cost', '.dp-qty', '.dp-rate'].forEach(function (sel) {
            var el = tr.querySelector(sel);
            if (el) el.addEventListener('input', function () { recalcRow(tr); });
        });
        var rm = tr.querySelector('.dp-remove');
        if (rm) rm.addEventListener('click', function () {
            var body = document.getElementById('line-items-body');
            if (body.querySelectorAll('.dp-line').length > 1) tr.remove();
        });
    }
    document.querySelectorAll('#line-items-body .dp-line').forEach(bindRow);
    document.getElementById('add-line-btn').addEventListener('click', function () {
        var tpl = document.querySelector('#line-item-template tr');
        var body = document.getElementById('line-items-body');
        var tr = tpl.cloneNode(true);
        body.appendChild(tr);
        bindRow(tr);
    });
    document.getElementById('sum-lines-btn').addEventListener('click', function () {
        var sum = 0;
        document.querySelectorAll('.dp-line-total').forEach(function (el) {
            var v = parseFloat(el.value);
            if (!isNaN(v)) sum += v;
        });
        var sub = document.getElementById('f_subtotal');
        if (sub) sub.value = sum.toFixed(2);
        var gt = document.getElementById('f_grand_total');
        if (gt && (!gt.value || parseFloat(gt.value) === 0)) gt.value = sum.toFixed(2);
    });
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#vendor_id').select2({ width: '100%', placeholder: 'Select vendor' });
    }
})();
</script>
