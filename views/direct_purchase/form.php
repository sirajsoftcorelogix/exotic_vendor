<?php
require_once dirname(dirname(__DIR__)) . '/helpers/direct_purchase_currency.php';

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
$inp = 'w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition';
$inpSm = 'px-2 py-2 text-sm border border-gray-300 rounded-lg text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 transition';
$dpCurrencies = dp_currency_form_options();
$dpCurrencyVal = strtoupper(trim((string) ($pData['currency'] ?? 'INR')));
if (!isset($dpCurrencies[$dpCurrencyVal])) {
    $dpCurrencyVal = 'INR';
}
$dpCurrencySym = dp_currency_symbol($dpCurrencyVal);
$dpDateMax = (new DateTimeImmutable('now', new DateTimeZone('Asia/Kolkata')))->format('Y-m-d');
$dpThumbPlaceholder = 'https://placehold.co/48x48/e2e8f0/94a3b8?text=%E2%80%94';
$warehouses = $data['warehouses'] ?? [];
$defWh = (int) ($data['default_warehouse_id'] ?? 0);
$dpLocked = !empty($data['purchase_locked']);
$whSelected = (int) ($pData['warehouse_id'] ?? $defWh);
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <!-- Header band (stock transfer style) -->
    <div class="relative overflow-hidden rounded-2xl border border-amber-200/45 bg-gradient-to-br from-amber-50/70 via-white to-slate-50/40 shadow-sm ring-1 ring-amber-900/[0.04] mb-6">
        <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-amber-300/20 blur-3xl" aria-hidden="true"></div>
        <div class="relative px-5 py-6 sm:px-7 sm:py-7 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full border border-amber-200/60 bg-white/70 px-3 py-1 text-xs font-semibold text-amber-900/90 shadow-sm backdrop-blur-sm mb-3">
                    <span class="flex h-6 w-6 items-center justify-center rounded-md bg-amber-100 text-amber-700">
                        <i class="fas fa-receipt text-[11px]" aria-hidden="true"></i>
                    </span>
                    <span><?= $isEdit ? 'Edit entry' : 'New entry' ?></span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight text-gray-900">
                    <?= $isEdit ? 'Edit direct purchase' : 'Add direct purchase' ?>
                </h1>
                <?php if ($isEdit && $purchase): ?>
                    <p class="mt-2 text-sm text-gray-600">
                        Invoice <strong class="font-mono text-gray-900"><?= htmlspecialchars($pData['invoice_number'] ?? '') ?></strong>
                        <?php if (!empty($pData['invoice_date'])): ?>
                            · <span class="text-gray-500"><?= htmlspecialchars(date('j M Y', strtotime($pData['invoice_date']))) ?></span>
                        <?php endif; ?>
                    </p>
                <?php else: ?>
                    <p class="mt-2 text-sm text-gray-600 max-w-2xl leading-relaxed">
                        Select vendor, upload the invoice, and enter lines—totals update automatically like a standard invoice.
                    </p>
                <?php endif; ?>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2 justify-end">
                <?php if ($isEdit && $purchase): ?>
                    <a href="?page=direct_purchase&action=return_list&amp;dp_id=<?= (int) $purchase['id'] ?>"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-amber-200 bg-white text-amber-900 text-sm font-semibold hover:bg-amber-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap">
                        <i class="fas fa-undo-alt text-xs opacity-90" aria-hidden="true"></i>
                        Returns
                    </a>
                <?php endif; ?>
                <a href="?page=direct_purchase&action=list"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition whitespace-nowrap w-full sm:w-auto">
                    <i class="fas fa-arrow-left text-xs opacity-90" aria-hidden="true"></i>
                    Back to list
                </a>
            </div>
        </div>
    </div>

    <?php if (is_array($flash) && trim((string) ($flash['text'] ?? '')) !== ''): ?>
        <?php $flashType = ($flash['type'] ?? '') === 'success' ? 'success' : 'error';
        $flashRing = $flashType === 'success'
            ? 'border-emerald-200/80 bg-emerald-50/90 text-emerald-900'
            : 'border-red-200/80 bg-red-50/90 text-red-900';
        ?>
        <div class="mb-6 rounded-xl border px-4 py-3 text-sm font-medium shadow-sm <?= $flashRing ?>" role="status">
            <?= htmlspecialchars((string) $flash['text']) ?>
        </div>
    <?php endif; ?>

    <?php if ($dpLocked && $purchase): ?>
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 shadow-sm" role="status">
            This purchase has returns and cannot be edited. Delete all returns for this purchase first, then you can change lines again.
            <a href="?page=direct_purchase&action=return_list&amp;dp_id=<?= (int) $purchase['id'] ?>"
                class="ml-2 font-semibold text-amber-900 underline underline-offset-2 decoration-amber-700/40 hover:text-amber-950">View returns</a>
        </div>
    <?php endif; ?>

    <form method="post" action="?page=direct_purchase&action=save" enctype="multipart/form-data" id="dp-form" class="space-y-6" <?= $dpLocked ? 'onsubmit="return false;"' : '' ?>>
        <?php if ($isEdit && $purchase): ?>
            <input type="hidden" name="id" value="<?= (int) $purchase['id'] ?>">
        <?php endif; ?>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="text-lg font-semibold text-gray-800 border-b border-gray-200 pb-3 mb-4">Vendor &amp; invoice</div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Vendor <span class="text-red-500">*</span></label>
                    <select name="vendor_id" id="vendor_id" required class="<?= $inp ?> bg-white">
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
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Warehouse (stock) <span class="text-red-500">*</span></label>
                    <select name="warehouse_id" id="warehouse_id" class="<?= $inp ?> bg-white" <?= $dpLocked ? 'disabled' : 'required' ?>>
                        <option value="">Select warehouse</option>
                        <?php foreach ($warehouses as $wh): ?>
                            <option value="<?= (int) ($wh['id'] ?? 0) ?>"
                                <?= $whSelected === (int) ($wh['id'] ?? 0) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($wh['address_title'] ?? ('#' . (int) ($wh['id'] ?? 0)))) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($dpLocked && $purchase): ?>
                        <input type="hidden" name="warehouse_id" value="<?= (int) ($purchase['warehouse_id'] ?? 0) ?>">
                    <?php endif; ?>
                    <p class="mt-1 text-xs text-gray-500">Goods receipt stock is posted to this warehouse (same pattern as GRN).</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice file</label>
                    <input type="file" name="invoice_file" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,application/pdf,image/*"
                        class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-50 file:text-amber-900 file:font-medium">
                    <p class="mt-1 text-xs text-gray-500">PDF or image only. Maximum file size 2 MB.</p>
                    <?php if ($purchase && !empty($purchase['invoice_file'])): ?>
                        <p class="mt-2 text-sm text-gray-600">Current:
                            <a href="<?= htmlspecialchars($purchase['invoice_file']) ?>" target="_blank" rel="noopener noreferrer"
                                class="font-medium text-amber-800/90 hover:text-amber-950 hover:underline underline-offset-2 decoration-amber-800/30">View attachment</a>
                            <span class="text-gray-400">(leave empty to keep)</span>
                        </p>
                    <?php endif; ?>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice number <span class="text-red-500">*</span></label>
                    <input type="text" name="invoice_number" required class="<?= $inp ?>"
                        value="<?= htmlspecialchars($pData['invoice_number'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Invoice date <span class="text-red-500">*</span></label>
                    <input type="date" name="invoice_date" required class="<?= $inp ?>" max="<?= htmlspecialchars($dpDateMax) ?>"
                        value="<?= htmlspecialchars($pData['invoice_date'] ?? '') ?>">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Currency</label>
                    <select name="currency" id="dp_currency" class="<?= $inp ?> bg-white">
                        <?php foreach ($dpCurrencies as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code) ?>" <?= $dpCurrencyVal === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
            <div class="border-b border-gray-200 pb-3 mb-4">
                <div class="text-lg font-semibold text-gray-800">Line Items</div>
            </div>
            <div class="overflow-x-auto" id="line-items-x-scroll">
                <table class="min-w-full divide-y divide-gray-200 text-sm" id="line-items-table">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-2 py-3 text-center font-semibold text-gray-700 border-b border-gray-200 w-16">Image</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[14rem]">SKU</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[8rem]">Cost / item</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[6rem]">Qty</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[8rem]">HSN</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[6rem]">GST %</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[6rem]">Unit</th>
                            <th class="px-3 py-3 text-left font-semibold text-gray-700 border-b border-gray-200 min-w-[8rem]">Line total</th>
                            <th class="px-3 py-3 text-center font-semibold text-gray-700 border-b border-gray-200 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="line-items-body" class="divide-y divide-gray-100">
                        <?php foreach ($items as $idx => $it): ?>
                            <?php
                            $lineImg = trim((string) ($it['product_image'] ?? ''));
                            $thumbShow = $lineImg !== '' ? $lineImg : $dpThumbPlaceholder;
                            ?>
                            <tr class="dp-line hover:bg-amber-50/30 transition-colors">
                                <td class="px-2 py-2 align-top text-center w-16">
                                    <button type="button" class="dp-thumb-trigger mx-auto flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-0 shadow-sm transition hover:ring-2 hover:ring-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 <?= $lineImg === '' ? 'opacity-60 cursor-not-allowed' : 'cursor-zoom-in' ?>"
                                        data-full-src="<?= htmlspecialchars($lineImg) ?>"
                                        title="<?= $lineImg !== '' ? 'View larger' : '' ?>"
                                        aria-label="<?= $lineImg !== '' ? 'Enlarge product image' : 'No product image' ?>">
                                        <img src="<?= htmlspecialchars($thumbShow) ?>" alt="" class="dp-line-thumb-img h-full w-full object-cover" loading="lazy" data-placeholder="<?= htmlspecialchars($dpThumbPlaceholder) ?>"
                                            onerror="this.onerror=null;this.src=this.getAttribute('data-placeholder')||'';">
                                    </button>
                                </td>
                                <td class="px-3 py-2 align-top min-w-[14rem]">
                                    <div class="relative dp-sku-cell">
                                        <input type="hidden" name="item_code[]" class="dp-h-item-code" value="<?= htmlspecialchars($it['item_code'] ?? '') ?>">
                                        <input type="hidden" name="color[]" class="dp-h-color" value="<?= htmlspecialchars($it['color'] ?? '') ?>">
                                        <input type="hidden" name="size[]" class="dp-h-size" value="<?= htmlspecialchars($it['size'] ?? '') ?>">
                                        <input type="hidden" name="gst_amount[]" class="dp-gst" value="<?= htmlspecialchars((string) ($it['gst_amount'] ?? '')) ?>">
                                        <input type="text" name="sku[]" autocomplete="off" placeholder="Search by SKU…"
                                            class="dp-sku w-full min-w-[12rem] <?= $inpSm ?>"
                                            value="<?= htmlspecialchars($it['sku'] ?? '') ?>">
                                        <div class="dp-sku-suggestions max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg hidden text-left"></div>
                                    </div>
                                </td>
                                <td class="px-3 py-2 align-top min-w-[8rem]"><input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost w-full min-w-[7rem] <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['cost_per_item'] ?? '')) ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.001" name="qty[]" class="dp-qty w-full min-w-[5rem] <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['qty'] ?? '')) ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[8rem]"><input name="hsn[]" class="w-full min-w-[7rem] <?= $inpSm ?>" value="<?= htmlspecialchars($it['hsn'] ?? '') ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-full min-w-[5rem] <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['gst_rate'] ?? '')) ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[6rem]"><input name="unit[]" class="w-full min-w-[5rem] <?= $inpSm ?>" value="<?= htmlspecialchars($it['unit'] ?? '') ?>"></td>
                                <td class="px-3 py-2 align-top min-w-[8rem]"><input type="number" step="0.01" name="line_total[]" class="dp-line-total w-full min-w-[7rem] <?= $inpSm ?>" value="<?= htmlspecialchars((string) ($it['line_total'] ?? '')) ?>"></td>
                                <td class="px-3 py-2 text-center align-top">
                                    <button type="button" class="dp-remove inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition" title="Remove row" aria-label="Remove row">
                                        <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <button type="button" id="add-line-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-amber-600 text-white text-sm font-semibold hover:bg-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition shadow-sm">
                    <i class="fas fa-plus text-xs opacity-95" aria-hidden="true"></i>
                    Add row
                </button>
            </div>
            <p class="mt-3 text-xs text-gray-500 leading-relaxed">
                Type at least 2 characters to search products by SKU only. Line amounts recalc from cost, qty, and GST %; invoice totals below update automatically.
            </p>

            <div class="mt-6 pt-5 border-t border-gray-200">
                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-end gap-6">
                    <div class="w-full lg:max-w-md rounded-xl border border-gray-200 bg-gray-50/80 p-4 sm:p-5">
                        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-3">Invoice summary</div>
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-200/80">
                                <tr>
                                    <td class="py-2 pr-3 text-gray-600">Subtotal <span class="text-gray-400 font-normal">(taxable)</span></td>
                                    <td class="py-2 text-right font-medium text-gray-900 w-44">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="dp-summary-cur-sym text-gray-500 tabular-nums shrink-0" aria-hidden="true"><?= htmlspecialchars($dpCurrencySym) ?></span>
                                            <input type="number" step="0.01" name="subtotal" id="f_subtotal" readonly tabindex="-1"
                                                class="min-w-0 flex-1 text-right rounded-lg border border-gray-200 bg-white px-2 py-2 text-gray-900 shadow-sm"
                                                value="<?= htmlspecialchars((string) ($pData['subtotal'] ?? '0')) ?>">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-3 text-gray-600">GST <span class="text-gray-400 font-normal">(total)</span></td>
                                    <td class="py-2 text-right font-medium text-gray-900 w-44">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="dp-summary-cur-sym text-gray-500 tabular-nums shrink-0" aria-hidden="true"><?= htmlspecialchars($dpCurrencySym) ?></span>
                                            <input type="number" step="0.01" name="igst_total" id="f_igst_total" readonly tabindex="-1"
                                                class="min-w-0 flex-1 text-right rounded-lg border border-gray-200 bg-white px-2 py-2 text-gray-900 shadow-sm"
                                                value="<?= htmlspecialchars((string) ($pData['igst_total'] ?? '0')) ?>">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-3 text-gray-600">Discount</td>
                                    <td class="py-2 text-right font-medium text-gray-900 w-44">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="dp-summary-cur-sym text-gray-500 tabular-nums shrink-0" aria-hidden="true"><?= htmlspecialchars($dpCurrencySym) ?></span>
                                            <input type="number" step="0.01" name="discount" id="f_discount"
                                                class="min-w-0 flex-1 text-right rounded-lg border border-gray-200 bg-white px-2 py-2 text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 outline-none transition"
                                                value="<?= htmlspecialchars((string) ($pData['discount'] ?? '0')) ?>">
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="py-2 pr-3 text-gray-600">Round off</td>
                                    <td class="py-2 text-right font-medium text-gray-900 w-44">
                                        <div class="flex items-center justify-end gap-1">
                                            <span class="dp-summary-cur-sym text-gray-500 tabular-nums shrink-0" aria-hidden="true"><?= htmlspecialchars($dpCurrencySym) ?></span>
                                            <input type="number" step="0.01" name="round_off" id="f_round_off"
                                                class="min-w-0 flex-1 text-right rounded-lg border border-gray-200 bg-white px-2 py-2 text-gray-900 shadow-sm focus:ring-2 focus:ring-amber-500/30 focus:border-amber-500 outline-none transition"
                                                value="<?= htmlspecialchars((string) ($pData['round_off'] ?? '0')) ?>">
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <input type="hidden" name="sgst_total" id="f_sgst_total" value="<?= htmlspecialchars((string) ($pData['sgst_total'] ?? '0')) ?>">
                        <input type="hidden" name="cgst_total" id="f_cgst_total" value="<?= htmlspecialchars((string) ($pData['cgst_total'] ?? '0')) ?>">
                        <div class="mt-4 pt-3 border-t border-gray-200/80">
                            <div class="text-amber-900/90 text-sm font-semibold mb-2">Grand total</div>
                            <div class="flex items-center gap-1.5 rounded-lg border border-amber-200/80 bg-amber-50/90 px-3 py-2 shadow-sm">
                                <span class="dp-summary-cur-sym text-amber-900/80 text-lg tabular-nums shrink-0 font-bold" aria-hidden="true"><?= htmlspecialchars($dpCurrencySym) ?></span>
                                <input type="number" step="0.01" name="grand_total" id="f_grand_total" readonly tabindex="-1"
                                    class="min-w-0 flex-1 border-0 bg-transparent text-right text-base font-bold text-amber-950 p-0 focus:ring-0"
                                    value="<?= htmlspecialchars((string) ($pData['grand_total'] ?? '0')) ?>">
                            </div>
                        </div>
                        <p class="mt-3 text-[11px] text-gray-500 leading-snug">
                            Grand total uses the sum of line totals, minus discount, plus round off—match discount and round off to the vendor invoice if needed.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
            <a href="?page=direct_purchase&action=list"
                class="inline-flex justify-center items-center px-5 py-2.5 rounded-xl border border-gray-300 bg-white text-sm font-semibold text-gray-800 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-gray-400 focus-visible:ring-offset-2 transition">
                Cancel
            </a>
            <button type="submit" <?= $dpLocked ? 'disabled' : '' ?>
                class="inline-flex justify-center items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-b from-[#d9822b] to-[#c57526] text-white text-sm font-semibold shadow-lg shadow-amber-900/15 hover:from-[#c57526] hover:to-[#b86a22] focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 transition <?= $dpLocked ? 'opacity-50 cursor-not-allowed pointer-events-none' : '' ?>">
                <i class="fas fa-save text-xs opacity-95" aria-hidden="true"></i>
                <?= $isEdit ? 'Update purchase' : 'Save purchase' ?>
            </button>
        </div>
    </form>
</div>

<div id="dp-img-lightbox" class="fixed inset-0 z-[200] hidden flex-col items-center justify-center bg-black/85 p-6" role="dialog" aria-modal="true" aria-labelledby="dp-img-lightbox-title">
    <p id="dp-img-lightbox-title" class="sr-only">Enlarged product image</p>
    <button type="button" id="dp-img-lightbox-close" class="absolute right-4 top-4 inline-flex h-10 w-10 items-center justify-center rounded-full border border-white/30 bg-white/10 text-white text-xl font-light hover:bg-white/20 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-400" aria-label="Close">&times;</button>
    <img id="dp-img-lightbox-img" src="" alt="" class="max-h-[90vh] max-w-full rounded-lg object-contain shadow-2xl ring-1 ring-white/10">
</div>

<table class="hidden">
    <tbody id="line-item-template">
        <tr class="dp-line hover:bg-amber-50/30 transition-colors">
            <td class="px-2 py-2 align-top text-center w-16">
                <button type="button" class="dp-thumb-trigger mx-auto flex h-12 w-12 items-center justify-center overflow-hidden rounded-lg border border-gray-200 bg-gray-50 p-0 opacity-60 cursor-not-allowed shadow-sm"
                    data-full-src="" title="" aria-label="No product image">
                    <img src="<?= htmlspecialchars($dpThumbPlaceholder) ?>" alt="" class="dp-line-thumb-img h-full w-full object-cover" loading="lazy" data-placeholder="<?= htmlspecialchars($dpThumbPlaceholder) ?>"
                        onerror="this.onerror=null;this.src=this.getAttribute('data-placeholder')||'';">
                </button>
            </td>
            <td class="px-3 py-2 align-top min-w-[14rem]">
                <div class="relative dp-sku-cell">
                    <input type="hidden" name="item_code[]" class="dp-h-item-code" value="">
                    <input type="hidden" name="color[]" class="dp-h-color" value="">
                    <input type="hidden" name="size[]" class="dp-h-size" value="">
                    <input type="hidden" name="gst_amount[]" class="dp-gst" value="">
                    <input type="text" name="sku[]" autocomplete="off" placeholder="Search by SKU…"
                        class="dp-sku w-full min-w-[12rem] <?= $inpSm ?>" value="">
                    <div class="dp-sku-suggestions max-h-52 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg hidden text-left"></div>
                </div>
            </td>
            <td class="px-3 py-2 align-top min-w-[8rem]"><input type="number" step="0.0001" name="cost_per_item[]" class="dp-cost w-full min-w-[7rem] <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.001" name="qty[]" class="dp-qty w-full min-w-[5rem] <?= $inpSm ?>" value="1"></td>
            <td class="px-3 py-2 align-top min-w-[8rem]"><input name="hsn[]" class="w-full min-w-[7rem] <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 align-top min-w-[6rem]"><input type="number" step="0.01" name="gst_rate[]" class="dp-rate w-full min-w-[5rem] <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 align-top min-w-[6rem]"><input name="unit[]" class="w-full min-w-[5rem] <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 align-top min-w-[8rem]"><input type="number" step="0.01" name="line_total[]" class="dp-line-total w-full min-w-[7rem] <?= $inpSm ?>" value=""></td>
            <td class="px-3 py-2 text-center align-top">
                <button type="button" class="dp-remove inline-flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 hover:text-red-600 hover:border-red-200 hover:bg-red-50 transition" title="Remove row" aria-label="Remove row">
                    <i class="fas fa-trash-alt text-xs" aria-hidden="true"></i>
                </button>
            </td>
        </tr>
    </tbody>
</table>

<script>
(function () {
    var DP_THUMB_PLACEHOLDER = <?= json_encode($dpThumbPlaceholder, JSON_UNESCAPED_UNICODE) ?>;
    var DP_CUR_SYM = <?= json_encode(dp_currency_symbol_map(), JSON_UNESCAPED_UNICODE) ?>;

    function syncSummaryCurrencySymbols() {
        var sel = document.getElementById('dp_currency');
        var code = sel && sel.value ? String(sel.value) : 'INR';
        var sym = (DP_CUR_SYM && DP_CUR_SYM[code]) ? DP_CUR_SYM[code] : (DP_CUR_SYM.INR || '');
        document.querySelectorAll('.dp-summary-cur-sym').forEach(function (el) {
            el.textContent = sym;
        });
    }

    function productSearchUrl(q) {
        var u = new URL(window.location.href);
        u.searchParams.set('page', 'direct_purchase');
        u.searchParams.set('action', 'product_search');
        u.searchParams.set('q', q);
        return u.toString();
    }

    function parseNum(el) {
        if (!el) return 0;
        var v = parseFloat(el.value);
        return isNaN(v) ? 0 : v;
    }
    function setLineThumb(tr, url) {
        var btn = tr.querySelector('.dp-thumb-trigger');
        var img = tr.querySelector('.dp-line-thumb-img');
        if (!btn || !img) return;
        var u = (url && String(url).trim()) ? String(url).trim() : '';
        btn.setAttribute('data-full-src', u);
        if (u) {
            img.src = u;
            btn.classList.remove('opacity-60', 'cursor-not-allowed');
            btn.classList.add('cursor-zoom-in');
            btn.setAttribute('title', 'View larger');
            btn.setAttribute('aria-label', 'Enlarge product image');
        } else {
            img.src = DP_THUMB_PLACEHOLDER;
            btn.classList.add('opacity-60', 'cursor-not-allowed');
            btn.classList.remove('cursor-zoom-in');
            btn.removeAttribute('title');
            btn.setAttribute('aria-label', 'No product image');
        }
    }

    function recalcRow(tr) {
        var cost = parseNum(tr.querySelector('.dp-cost'));
        var qty = parseNum(tr.querySelector('.dp-qty'));
        var rate = parseNum(tr.querySelector('.dp-rate'));
        var taxable = cost * qty;
        var gst = taxable * (rate / 100);
        var gstIn = tr.querySelector('.dp-gst');
        var lineIn = tr.querySelector('.dp-line-total');
        if (gstIn) gstIn.value = gst.toFixed(2);
        if (lineIn) lineIn.value = (taxable + gst).toFixed(2);
        recalcInvoiceTotals();
    }

    function recalcInvoiceTotals() {
        var taxableSum = 0;
        var gstSum = 0;
        var lineSum = 0;
        document.querySelectorAll('#line-items-body .dp-line').forEach(function (tr) {
            var cost = parseNum(tr.querySelector('.dp-cost'));
            var qty = parseNum(tr.querySelector('.dp-qty'));
            taxableSum += cost * qty;
            gstSum += parseNum(tr.querySelector('.dp-gst'));
            lineSum += parseNum(tr.querySelector('.dp-line-total'));
        });
        var sub = document.getElementById('f_subtotal');
        var igst = document.getElementById('f_igst_total');
        var sgst = document.getElementById('f_sgst_total');
        var cgst = document.getElementById('f_cgst_total');
        var discount = parseNum(document.getElementById('f_discount'));
        var roundOff = parseNum(document.getElementById('f_round_off'));
        var grand = document.getElementById('f_grand_total');
        if (sub) sub.value = taxableSum.toFixed(2);
        if (igst) igst.value = gstSum.toFixed(2);
        if (sgst) sgst.value = '0.00';
        if (cgst) cgst.value = '0.00';
        var g = lineSum - discount + roundOff;
        if (grand) grand.value = g.toFixed(2);
    }

    function escapeHtml(str) {
        return String(str || '').replace(/[&<>"']/g, function (s) {
            return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[s];
        });
    }
    function b64EncodeUtf8(str) {
        return btoa(unescape(encodeURIComponent(str)));
    }

    function syncSkuSuggestBox(box, skuInput) {
        if (!box || !skuInput || box.classList.contains('hidden')) return;
        var r = skuInput.getBoundingClientRect();
        box.style.position = 'fixed';
        box.style.left = r.left + 'px';
        box.style.top = (r.bottom + 4) + 'px';
        box.style.width = Math.max(r.width, 200) + 'px';
        box.style.zIndex = '10000';
    }

    function syncAllOpenSkuSuggestBoxes() {
        document.querySelectorAll('.dp-sku-suggestions').forEach(function (box) {
            if (box.classList.contains('hidden')) return;
            var cell = box.closest('.dp-sku-cell');
            var inp = cell && cell.querySelector('.dp-sku');
            if (inp) syncSkuSuggestBox(box, inp);
        });
    }

    function dpFillRowFromProduct(tr, item) {
        var cell = tr.querySelector('.dp-sku-cell');
        if (!cell || !item) return;
        var ic = cell.querySelector('.dp-h-item-code');
        var col = cell.querySelector('.dp-h-color');
        var sz = cell.querySelector('.dp-h-size');
        var skuEl = cell.querySelector('.dp-sku');
        if (ic) ic.value = item.item_code != null ? String(item.item_code) : '';
        if (col) col.value = item.color != null ? String(item.color) : '';
        if (sz) sz.value = item.size != null ? String(item.size) : '';
        if (skuEl) skuEl.value = item.sku != null ? String(item.sku) : '';
        var cost = tr.querySelector('.dp-cost');
        if (cost && item.cost_price != null && item.cost_price !== '') cost.value = item.cost_price;
        var hsn = tr.querySelector('input[name="hsn[]"]');
        if (hsn && item.hsn != null) hsn.value = String(item.hsn);
        var rate = tr.querySelector('.dp-rate');
        if (rate && item.gst != null && item.gst !== '') rate.value = item.gst;
        setLineThumb(tr, item.image != null && item.image !== '' ? item.image : '');
        recalcRow(tr);
    }

    function initSkuSearch(skuInput) {
        var tr = skuInput.closest('tr');
        if (!tr) return;
        var cell = skuInput.closest('.dp-sku-cell');
        var box = cell ? cell.querySelector('.dp-sku-suggestions') : null;
        var debounce = null;
        var fetchAbort = null;

        function clearBox() {
            if (!box) return;
            box.innerHTML = '';
            box.classList.add('hidden');
            box.style.position = '';
            box.style.left = '';
            box.style.top = '';
            box.style.width = '';
            box.style.zIndex = '';
        }

        function render(list) {
            if (!box) return;
            if (!list || !list.length) {
                clearBox();
                return;
            }
            box.innerHTML = list.slice(0, 18).map(function (it) {
                var line1 = escapeHtml(it.sku || '') + (it.item_code ? ' · ' + escapeHtml(it.item_code) : '');
                var line2 = escapeHtml((it.title || '').substring(0, 80));
                var b64 = b64EncodeUtf8(JSON.stringify(it));
                return '<button type="button" class="dp-sku-pick w-full text-left px-3 py-2 text-sm hover:bg-amber-50 border-b border-gray-100 last:border-0" data-b64="' + b64 + '">' +
                    '<span class="font-semibold text-gray-900">' + line1 + '</span><br><span class="text-xs text-gray-600">' + line2 + '</span></button>';
            }).join('');
            box.classList.remove('hidden');
            syncSkuSuggestBox(box, skuInput);
        }

        skuInput.addEventListener('input', function () {
            clearTimeout(debounce);
            if (fetchAbort) {
                fetchAbort.abort();
            }
            var q = skuInput.value.trim();
            if (q.length < 2) {
                if (fetchAbort) {
                    fetchAbort.abort();
                    fetchAbort = null;
                }
                clearBox();
                return;
            }
            debounce = setTimeout(function () {
                var ctrl = new AbortController();
                fetchAbort = ctrl;
                fetch(productSearchUrl(q), {
                    credentials: 'same-origin',
                    signal: ctrl.signal,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (fetchAbort !== ctrl) return;
                        if (!Array.isArray(data)) { clearBox(); return; }
                        render(data);
                    })
                    .catch(function (err) {
                        if (err && err.name === 'AbortError') return;
                        if (fetchAbort !== ctrl) return;
                        clearBox();
                    })
                    .finally(function () {
                        if (fetchAbort === ctrl) {
                            fetchAbort = null;
                        }
                    });
            }, 120);
        });

        if (box) {
            box.addEventListener('mousedown', function (e) {
                var btn = e.target.closest('.dp-sku-pick');
                if (!btn) return;
                e.preventDefault();
                try {
                    var raw = btn.getAttribute('data-b64') || '';
                    var item = JSON.parse(decodeURIComponent(escape(atob(raw))));
                    dpFillRowFromProduct(tr, item);
                } catch (err) { console.error(err); }
                clearBox();
                skuInput.focus();
            });
        }

        skuInput.addEventListener('blur', function () {
            setTimeout(clearBox, 150);
        });
    }

    function bindRow(tr) {
        ['.dp-cost', '.dp-qty', '.dp-rate'].forEach(function (sel) {
            var el = tr.querySelector(sel);
            if (el) el.addEventListener('input', function () { recalcRow(tr); });
        });
        var lineTot = tr.querySelector('.dp-line-total');
        if (lineTot) lineTot.addEventListener('input', function () { recalcInvoiceTotals(); });
        var rm = tr.querySelector('.dp-remove');
        if (rm) rm.addEventListener('click', function () {
            var body = document.getElementById('line-items-body');
            if (body.querySelectorAll('.dp-line').length > 1) {
                tr.remove();
                recalcInvoiceTotals();
            }
        });
        var sku = tr.querySelector('.dp-sku');
        if (sku) initSkuSearch(sku);
    }

    function initDpImageLightbox() {
        var lb = document.getElementById('dp-img-lightbox');
        var lbImg = document.getElementById('dp-img-lightbox-img');
        var lbClose = document.getElementById('dp-img-lightbox-close');
        if (!lb || !lbImg) return;
        function openLb(url) {
            if (!url || !String(url).trim()) return;
            lbImg.src = url;
            lb.classList.remove('hidden');
            lb.classList.add('flex');
            document.body.style.overflow = 'hidden';
        }
        function closeLb() {
            lb.classList.add('hidden');
            lb.classList.remove('flex');
            lbImg.src = '';
            document.body.style.overflow = '';
        }
        document.getElementById('line-items-body').addEventListener('click', function (e) {
            var btn = e.target.closest('.dp-thumb-trigger');
            if (!btn) return;
            var url = btn.getAttribute('data-full-src') || '';
            if (!String(url).trim()) return;
            e.preventDefault();
            openLb(url);
        });
        if (lbClose) lbClose.addEventListener('click', closeLb);
        lb.addEventListener('click', function (e) {
            if (e.target === lb) closeLb();
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !lb.classList.contains('hidden')) closeLb();
        });
    }

    document.querySelectorAll('#line-items-body .dp-line').forEach(bindRow);
    initDpImageLightbox();
    window.addEventListener('resize', syncAllOpenSkuSuggestBoxes);
    window.addEventListener('scroll', syncAllOpenSkuSuggestBoxes, true);
    var lineXScroll = document.getElementById('line-items-x-scroll');
    if (lineXScroll) {
        lineXScroll.addEventListener('scroll', syncAllOpenSkuSuggestBoxes, true);
    }
    document.getElementById('add-line-btn').addEventListener('click', function () {
        var tpl = document.querySelector('#line-item-template tr');
        var body = document.getElementById('line-items-body');
        var tr = tpl.cloneNode(true);
        body.appendChild(tr);
        bindRow(tr);
        recalcInvoiceTotals();
    });
    var fDisc = document.getElementById('f_discount');
    var fRo = document.getElementById('f_round_off');
    if (fDisc) fDisc.addEventListener('input', recalcInvoiceTotals);
    if (fRo) fRo.addEventListener('input', recalcInvoiceTotals);
    recalcInvoiceTotals();
    var dpCurSel = document.getElementById('dp_currency');
    if (dpCurSel) {
        dpCurSel.addEventListener('change', syncSummaryCurrencySymbols);
    }
    if (window.jQuery && jQuery.fn.select2) {
        jQuery('#vendor_id').select2({ width: '100%', placeholder: 'Select vendor' });
        jQuery('#dp_currency').select2({ width: '100%', minimumResultsForSearch: 6 });
        jQuery('#dp_currency').on('select2:select change', syncSummaryCurrencySymbols);
    }
    syncSummaryCurrencySymbols();
})();
</script>
